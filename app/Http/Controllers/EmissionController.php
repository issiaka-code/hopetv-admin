<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\Emission;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

class EmissionController extends Controller
{
    public function index(Request $request)
    {
        $query = Emission::with('media')->where('is_deleted', false)->latest();

        // Recherche
        if ($request->filled('search')) {
            $query->where('nom', 'like', "%{$request->search}%")
                ->orWhere('description', 'like', "%{$request->search}%");
        }

        // Filtrage par type
        if ($request->filled('type')) {
            $query->whereHas('media', function ($q) use ($request) {
                if ($request->type === 'audio') {
                    $q->where('type', 'audio');
                } elseif ($request->type === 'video') {
                    $q->where('type', 'video');
                } elseif ($request->type === 'link') {
                    $q->where('type', 'link');
                } elseif ($request->type === 'pdf') {
                    $q->where('type', 'pdf');
                }
            });
        }

        $emissions = $query->paginate(12);

        // Préparer chaque variable pour la vue et JS
        $emissionsData = $emissions->map(function ($emission) {
            $isAudio = $emission->media && $emission->media->type === 'audio';
            $isVideoLink = $emission->media && $emission->media->type === 'link';
            $isVideoFile = $emission->media && $emission->media->type === 'video';
            $isPdf = $emission->media && $emission->media->type === 'pdf';

            $thumbnailUrl = null;

            if ($isVideoLink) {
                $rawUrl = $emission->media->url_fichier;

                if (Str::contains($rawUrl, 'youtube.com/watch?v=')) {
                    $videoId = explode('v=', parse_url($rawUrl, PHP_URL_QUERY))[1] ?? null;
                    $videoId = explode('&', $videoId)[0];
                    $thumbnailUrl = $videoId ? "https://www.youtube.com/embed/$videoId" : $rawUrl;
                } elseif (Str::contains($rawUrl, 'youtu.be/')) {
                    $videoId = basename(parse_url($rawUrl, PHP_URL_PATH));
                    $thumbnailUrl = "https://www.youtube.com/embed/$videoId";
                } else {
                    $thumbnailUrl = $rawUrl;
                }
            } elseif ($isVideoFile) {
                // Pour les vidéos fichiers, utiliser l'image de couverture si disponible
                if ($emission->media->thumbnail) {
                    $thumbnailUrl = asset('storage/' . $emission->media->thumbnail);
                } else {
                    $thumbnailUrl = asset('storage/' . $emission->media->url_fichier);
                }
            } elseif ($isAudio || $isPdf) {
                // Pour audio/PDF, utiliser l'image de couverture si disponible
                if ($emission->media->thumbnail) {
                    $thumbnailUrl = asset('storage/' . $emission->media->thumbnail);
                } else {
                    $thumbnailUrl = null; // Pas de thumbnail personnalisé, on utilisera l'icône par défaut
                }
            }

            return (object)[
                'id' => $emission->id,
                'nom' => $emission->nom,
                'description' => $emission->description,
                'created_at' => $emission->created_at,
                'media_type' => $isAudio ? 'audio' : ($isVideoLink ? 'video_link' : ($isVideoFile ? 'video_file' : 'pdf')),
                'thumbnail_url' => $thumbnailUrl,
                'video_url' => $isVideoFile ? asset('storage/' . $emission->media->url_fichier) : $thumbnailUrl,
                'has_thumbnail' => $emission->media->thumbnail ? true : false,
            ];
        });

        // Envoyer chaque émission comme variable séparée
        return view('admin.medias.emissions.index', [
            'emissions' => $emissions,
            'emissionsData' => $emissionsData,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'required|string',
            'media_type' => 'required|in:audio,video,link,pdf',
            'image_couverture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            DB::beginTransaction();

            if ($request->media_type === 'audio') {
                $request->validate([
                    'fichier_audio' => 'required|file|mimes:mp3,wav,aac,ogg,flac|max:512000',
                ]);

                $file = $request->file('fichier_audio');
                $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $uniqueName = $filename . '_' . now()->format('Ymd_His') . '.mp3';

                // Stockage direct sans optimisation
                $filePath = $file->storeAs('audios', $uniqueName, 'public');
            } elseif ($request->media_type === 'video') {
                $request->validate([
                    'fichier_video' => 'required|file|mimes:mp4,avi,mov,wmv,flv,mkv,webm|max:1024000',
                    'image_couverture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
                ]);

                $file = $request->file('fichier_video');
                $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $uniqueName = $filename . '_' . now()->format('Ymd_His') . '.mp4';

                // Stockage temporaire
                $tempPath = $file->storeAs('temp/videos', "tmp_{$uniqueName}");

                // Traitement avec FFmpeg
                FFMpeg::fromDisk('local')
                    ->open($tempPath)
                    ->export()
                    ->toDisk('public')
                    ->inFormat(new \FFMpeg\Format\Video\X264('aac', 'libx264'))
                    ->resize(1280, 720)
                    ->save('videos/' . $uniqueName);

                // Nettoyage du fichier temporaire
                Storage::disk('local')->delete($tempPath);

                $filePath = 'videos/' . $uniqueName;
            } elseif ($request->media_type === 'link') {
                $request->validate([
                    'lien_video' => 'required|url',
                ]);
                $filePath = $request->lien_video;
            } elseif ($request->media_type === 'pdf') {
                $request->validate([
                    'fichier_pdf' => 'required|file|mimes:pdf|max:20480',
                ]);

                $file = $request->file('fichier_pdf');
                $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $uniqueName = $filename . '_' . now()->format('Ymd_His') . '.pdf';

                // Stockage direct du PDF
                $filePath = $file->storeAs('pdfs', $uniqueName, 'public');
            }

            // Traitement de l'image de couverture pour tous les types de médias
            $thumbnailPath = null;
            if (in_array($request->media_type, ['video', 'audio', 'pdf']) && $request->hasFile('image_couverture')) {
                $thumbnailFile = $request->file('image_couverture');
                $thumbnailName = pathinfo($thumbnailFile->getClientOriginalName(), PATHINFO_FILENAME);
                $thumbnailUniqueName = $thumbnailName . '_thumb_' . now()->format('Ymd_His') . '.' . $thumbnailFile->getClientOriginalExtension();
                $thumbnailPath = $thumbnailFile->storeAs('thumbnails', $thumbnailUniqueName, 'public');
            }

            // Créer l'enregistrement média
            $media = Media::create([
                'url_fichier' => $filePath,
                'thumbnail' => $thumbnailPath,
                'type' => $request->media_type,
                'insert_by' => auth()->id(),
                'update_by' => auth()->id(),
            ]);

            // Créer l'émission
            Emission::create([
                'id_media' => $media->id,
                'nom' => $request->nom,
                'description' => $request->description,
                'insert_by' => auth()->id(),
                'update_by' => auth()->id(),
            ]);

            DB::commit();
            notify()->success('Succès', 'Émission ajoutée avec succès.');
            return redirect()->route('emissions.index');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la création: ' . $e->getMessage());
            Alert::error('Erreur', 'Impossible de créer l\'émission: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    public function edit(Emission $emission)
    {
        $emission->load('media');
        return response()->json([
            'nom' => $emission->nom,
            'description' => $emission->description,
            'media' => $emission->media
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'required|string',
            'media_type' => 'required|in:audio,video,link,pdf',
            'image_couverture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            DB::beginTransaction();

            // Récupérer l'émission existante
            $emission = Emission::findOrFail($id);
            $media = $emission->media;

            if (!$media) {
                throw new \Exception('Média introuvable pour cette émission');
            }

            $filePath = $media->url_fichier; // par défaut, garder l'ancien fichier
            $thumbnailPath = $media->thumbnail; // par défaut, garder l'ancienne image
            $type = $media->type;

            if ($request->media_type === 'audio') {
                $request->validate([
                    'fichier_audio' => 'nullable|file|mimes:mp3,wav,aac,ogg,flac|max:512000',
                ]);

                if ($request->hasFile('fichier_audio')) {
                    $file = $request->file('fichier_audio');
                    $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $uniqueName = $filename . '_' . now()->format('Ymd_His') . '.mp3';

                    // Supprimer ancien fichier audio
                    if ($media->type === 'audio' && Storage::disk('public')->exists($media->url_fichier)) {
                        Storage::disk('public')->delete($media->url_fichier);
                    }

                    // Stockage
                    $filePath = $file->storeAs('audios', $uniqueName, 'public');
                    $type = 'audio';
                }
            } elseif ($request->media_type === 'video') {
                $request->validate([
                    'fichier_video' => 'nullable|file|mimes:mp4,avi,mov,wmv,flv,mkv,webm|max:1024000',
                ]);

                if ($request->hasFile('fichier_video')) {
                    $file = $request->file('fichier_video');
                    $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $uniqueName = $filename . '_' . now()->format('Ymd_His') . '.mp4';

                    // Supprimer ancien fichier vidéo
                    if ($media->type === 'video' && Storage::disk('public')->exists($media->url_fichier)) {
                        Storage::disk('public')->delete($media->url_fichier);
                    }

                    // Stockage temporaire
                    $tempPath = $file->storeAs('temp/videos', "tmp_{$uniqueName}");

                    // Compression et export avec FFmpeg
                    FFMpeg::fromDisk('local')
                        ->open($tempPath)
                        ->export()
                        ->toDisk('public')
                        ->inFormat(new \FFMpeg\Format\Video\X264('aac', 'libx264'))
                        ->resize(1280, 720)
                        ->save('videos/' . $uniqueName);

                    Storage::disk('local')->delete($tempPath);

                    $filePath = 'videos/' . $uniqueName;
                    $type = 'video';
                }

            // Traitement de l'image de couverture pour tous les types de médias
            if (in_array($request->media_type, ['video', 'audio', 'pdf']) && $request->hasFile('image_couverture')) {
                // Supprimer l'ancienne image de couverture
                if ($media->thumbnail && Storage::disk('public')->exists($media->thumbnail)) {
                    Storage::disk('public')->delete($media->thumbnail);
                }

                $thumbnailFile = $request->file('image_couverture');
                $thumbnailName = pathinfo($thumbnailFile->getClientOriginalName(), PATHINFO_FILENAME);
                $thumbnailUniqueName = $thumbnailName . '_thumb_' . now()->format('Ymd_His') . '.' . $thumbnailFile->getClientOriginalExtension();
                $thumbnailPath = $thumbnailFile->storeAs('thumbnails', $thumbnailUniqueName, 'public');
            }
            } elseif ($request->media_type === 'link') {
                $request->validate([
                    'lien_video' => 'required|url',
                ]);

                $filePath = $request->lien_video;
                $type = 'link';
            } elseif ($request->media_type === 'pdf') {
                $request->validate([
                    'fichier_pdf' => 'nullable|file|mimes:pdf|max:20480',
                ]);

                if ($request->hasFile('fichier_pdf')) {
                    $file = $request->file('fichier_pdf');
                    $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $uniqueName = $filename . '_' . now()->format('Ymd_His') . '.pdf';

                    // Supprimer ancien PDF
                    if ($media->type === 'pdf' && Storage::disk('public')->exists($media->url_fichier)) {
                        Storage::disk('public')->delete($media->url_fichier);
                    }

                    // Stockage
                    $filePath = $file->storeAs('pdfs', $uniqueName, 'public');
                    $type = 'pdf';
                }
            }

            // Mise à jour du média
            $media->update([
                'url_fichier' => $filePath,
                'thumbnail' => $thumbnailPath,
                'type' => $type,
                'update_by' => auth()->id(),
            ]);

            // Mise à jour de l'émission
            $emission->update([
                'nom' => $request->nom,
                'description' => $request->description,
                'update_by' => auth()->id(),
            ]);

            DB::commit();
            notify()->success('Succès', 'Émission mise à jour avec succès.');
            return redirect()->route('emissions.index');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la mise à jour: ' . $e->getMessage());
            Alert::error('Erreur', 'Impossible de mettre à jour l\'émission: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    public function destroy($id)
    {
        $emission = Emission::findOrFail($id);
        try {
            DB::beginTransaction();

            $emission->update([
                'is_deleted' => true,
                'update_by' => auth()->id(),
            ]);

            // Marquer également le média comme supprimé
            if ($emission->media) {
                $emission->media->update([
                    'is_deleted' => true,
                    'update_by' => auth()->id(),
                ]);
            }

            DB::commit();
            notify()->success('Succès', 'Émission supprimée avec succès.');
            return redirect()->route('emissions.index');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }
}