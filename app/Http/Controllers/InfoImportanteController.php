<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\InfoImportante;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

class InfoImportanteController extends Controller
{
    public function index(Request $request)
    {
        $query = InfoImportante::with('media')->where('is_deleted', false)->latest();

        // Recherche
        if ($request->filled('search')) {
            $query->where('nom', 'like', "%{$request->search}%")
                ->orWhere('description', 'like', "%{$request->search}%");
        }

        // Filtrage par type de média
        if ($request->filled('type')) {
            $query->whereHas('media', function ($q) use ($request) {
                if ($request->type === 'audio') {
                    $q->where('type', 'audio');
                } elseif ($request->type === 'video') {
                    $q->where('type', 'video');
                }
            });
        }

        // Filtrage par statut
        if ($request->filled('status')) {
            $query->where('is_active', $request->status);
        }

        $infoImportantes = $query->paginate(12);

        // Préparer les données pour la vue
        $infoData = $infoImportantes->map(function ($info) {
            $isAudio = $info->media && $info->media->type === 'audio';
            $isVideo = $info->media && $info->media->type === 'video';

            $thumbnailUrl = null;

            if ($isVideo) {
                // Pour les vidéos, utiliser l'image de couverture si disponible
                if ($info->media->thumbnail) {
                    $thumbnailUrl = asset('storage/' . $info->media->thumbnail);
                } else {
                    $thumbnailUrl = asset('storage/' . $info->media->url_fichier);
                }
            } elseif ($isAudio) {
                $thumbnailUrl = asset('storage/' . $info->media->url_fichier);
            }

            return (object)[
                'id' => $info->id,
                'nom' => $info->nom,
                'description' => $info->description,
                'is_active' => $info->is_active,
                'created_at' => $info->created_at,
                'media_type' => $isAudio ? 'audio' : 'video',
                'thumbnail_url' => $thumbnailUrl,
                'video_url' => $isVideo ? asset('storage/' . $info->media->url_fichier) : $thumbnailUrl,
                'has_thumbnail' => $isVideo && $info->media->thumbnail ? true : false,
            ];
        });

        return view('admin.medias.info_importantes.index', [
            'infoImportantes' => $infoImportantes,
            'infoData' => $infoData,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'required|string',
            'media_type' => 'required|in:audio,video',
            'is_active' => 'boolean',
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
                $filePath = $file->storeAs('info_importantes/audios', $uniqueName, 'public');
                $type = 'audio';
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
                    ->save('info_importantes/videos/' . $uniqueName);

                // Nettoyage du fichier temporaire
                Storage::disk('local')->delete($tempPath);

                $filePath = 'info_importantes/videos/' . $uniqueName;
                $type = 'video';
            }

            // Traitement de l'image de couverture pour les vidéos
            $thumbnailPath = null;
            if ($request->media_type === 'video' && $request->hasFile('image_couverture')) {
                $thumbnailFile = $request->file('image_couverture');
                $thumbnailName = pathinfo($thumbnailFile->getClientOriginalName(), PATHINFO_FILENAME);
                $thumbnailUniqueName = $thumbnailName . '_thumb_' . now()->format('Ymd_His') . '.' . $thumbnailFile->getClientOriginalExtension();
                $thumbnailPath = $thumbnailFile->storeAs('thumbnails', $thumbnailUniqueName, 'public');
            }

            // Créer l'enregistrement média
            $media = Media::create([
                'url_fichier' => $filePath,
                'thumbnail' => $thumbnailPath,
                'type' => $type,
                'insert_by' => auth()->id(),
                'update_by' => auth()->id(),
            ]);

            // Créer l'information importante
            InfoImportante::create([
                'id_media' => $media->id,
                'nom' => $request->nom,
                'description' => $request->description,
                'is_active' => $request->has('is_active') ? true : false,
                'insert_by' => auth()->id(),
                'update_by' => auth()->id(),
            ]);

            DB::commit();
            notify()->success('Succès', 'Information importante ajoutée avec succès.');
            return redirect()->route('info_importantes.index');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la création de l\'information importante: ' . $e->getMessage());
            Alert::error('Erreur', 'Impossible de créer l\'information importante: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    public function edit(InfoImportante $infoImportante)
    {
        $infoImportante->load('media');
        return response()->json([
            'nom' => $infoImportante->nom,
            'description' => $infoImportante->description,
            'is_active' => $infoImportante->is_active,
            'media' => $infoImportante->media
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'required|string',
            'media_type' => 'required|in:audio,video',
            'image_couverture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            DB::beginTransaction();

            // Récupérer l'information importante existante
            $infoImportante = InfoImportante::findOrFail($id);
            $media = $infoImportante->media;

            if (!$media) {
                throw new \Exception('Média introuvable pour cette information importante');
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
                    $filePath = $file->storeAs('info_importantes/audios', $uniqueName, 'public');
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
                        ->save('info_importantes/videos/' . $uniqueName);

                    Storage::disk('local')->delete($tempPath);

                    $filePath = 'info_importantes/videos/' . $uniqueName;
                    $type = 'video';
                }

                // Traitement de l'image de couverture pour les vidéos
                if ($request->media_type === 'video' && $request->hasFile('image_couverture')) {
                    // Supprimer l'ancienne image de couverture
                    if ($media->thumbnail && Storage::disk('public')->exists($media->thumbnail)) {
                        Storage::disk('public')->delete($media->thumbnail);
                    }

                    $thumbnailFile = $request->file('image_couverture');
                    $thumbnailName = pathinfo($thumbnailFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $thumbnailUniqueName = $thumbnailName . '_thumb_' . now()->format('Ymd_His') . '.' . $thumbnailFile->getClientOriginalExtension();
                    $thumbnailPath = $thumbnailFile->storeAs('thumbnails', $thumbnailUniqueName, 'public');
                }
            }

            // Mise à jour du média
            $media->update([
                'url_fichier' => $filePath,
                'thumbnail' => $thumbnailPath,
                'type' => $type,
                'update_by' => auth()->id(),
            ]);

            // Mise à jour de l'information importante
            $infoImportante->update([
                'nom' => $request->nom,
                'description' => $request->description,
                'update_by' => auth()->id(),
            ]);

            DB::commit();
            notify()->success('Succès', 'Information importante mise à jour avec succès.');
            return redirect()->route('info_importantes.index');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la mise à jour de l\'information importante: ' . $e->getMessage());
            Alert::error('Erreur', 'Impossible de mettre à jour l\'information importante: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    public function toggleStatus(Request $request, $id)
    {
        try {
            $infoImportante = InfoImportante::findOrFail($id);
            
            $infoImportante->update([
                'is_active' => $request->is_active,
                'update_by' => auth()->id(),
            ]);

            $status = $request->is_active ? 'activée' : 'désactivée';
            notify()->success('Succès', "Information importante {$status} avec succès.");
            return response()->json([
                'success' => true,
            ]);

        } catch (\Exception $e) {
            notify()->error('Erreur', 'Erreur lors du changement de statut: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du changement de statut'
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $infoImportante = InfoImportante::findOrFail($id);

            // Supprimer l'information importante
            $infoImportante->is_deleted = true;
            $infoImportante->save();

            DB::commit();
            notify()->success('Succès', 'Information importante supprimée avec succès.');
            return redirect()->route('info_importantes.index');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la suppression: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }

    public function show(InfoImportante $infoImportante)
    {
        $infoImportante->load('media');
        return response()->json($infoImportante);
    }
}