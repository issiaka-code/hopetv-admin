<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\Podcast;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

class PodcastController extends Controller
{
    public function index(Request $request)
    {
        $query = Podcast::with('media')->where('is_deleted', false)->latest();

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
                } elseif ($request->type === 'video_file') {
                    $q->where('type', 'video');
                } elseif ($request->type === 'video_link') {
                    $q->where('type', 'link');
                }
            });
        }

        $podcasts = $query->paginate(12);

        // Préparer chaque variable pour la vue et JS
        $podcastsData = $podcasts->map(function ($podcast) {
            $isAudio = $podcast->media && $podcast->media->type === 'audio';
            $isVideoLink = $podcast->media && $podcast->media->type === 'link';
            $isVideoFile = $podcast->media && $podcast->media->type === 'video';

            $thumbnailUrl = null;

            if ($isVideoLink) {
                $rawUrl = $podcast->media->url_fichier;

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
                if ($podcast->media->thumbnail) {
                    $thumbnailUrl = asset('storage/' . $podcast->media->thumbnail);
                } else {
                    $thumbnailUrl = asset('storage/' . $podcast->media->url_fichier);
                }
            } elseif ($isAudio) {
                $thumbnailUrl = asset('storage/' . $podcast->media->url_fichier);
            }

            return (object)[
                'id' => $podcast->id,
                'nom' => $podcast->nom,
                'description' => $podcast->description,
                'created_at' => $podcast->created_at,
                'media_type' => $isAudio ? 'audio' : ($isVideoLink ? 'video_link' : 'video_file'),
                'thumbnail_url' => $thumbnailUrl,
                'video_url' => $isVideoFile ? asset('storage/' . $podcast->media->url_fichier) : $thumbnailUrl,
                'has_thumbnail' => $isVideoFile && $podcast->media->thumbnail ? true : false,
            ];
        });

        return view('admin.medias.podcasts.index', [
            'podcasts' => $podcasts,
            'podcastsData' => $podcastsData,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'required|string',
            'media_type' => 'required|in:audio,video_file,video_link',
            'image_couverture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            if ($request->media_type === 'audio') {
                $request->validate([
                    'fichier_audio' => 'required|file|mimes:mp3,wav,aac,ogg,flac|max:512000',
                ]);

                $file = $request->file('fichier_audio');
                $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $uniqueName = $filename . '_' . now()->format('Ymd_His') . '.mp3';

                // Stockage direct sans optimisation
                $filePath = $file->storeAs('audios', $uniqueName, 'public');
            } elseif ($request->media_type === 'video_file') {
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
            } elseif ($request->media_type === 'video_link') {
                $request->validate([
                    'lien_video' => 'required|url',
                ]);
                $filePath = $request->lien_video;
            }

            // Déterminer le type pour la base de données
            $type = $request->media_type === 'audio' ? 'audio' : ($request->media_type === 'video_file' ? 'video' : 'link');

            // Traitement de l'image de couverture pour les vidéos fichiers
            $thumbnailPath = null;
            if ($request->media_type === 'video_file' && $request->hasFile('image_couverture')) {
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

            // Créer le podcast
            Podcast::create([
                'id_media' => $media->id,
                'nom' => $request->nom,
                'description' => $request->description,
                'insert_by' => auth()->id(),
                'update_by' => auth()->id(),
            ]);

            notify()->success('Succès', 'Podcast ajouté avec succès.');
            return redirect()->route('podcasts.index');
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création: ' . $e->getMessage());
            Alert::error('Erreur', 'Impossible de créer le podcast: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    public function edit(Podcast $podcast)
    {
        $podcast->load('media');
        return response()->json([
            'nom' => $podcast->nom,
            'description' => $podcast->description,
            'media' => $podcast->media
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'required|string',
            'media_type' => 'required|in:audio,video_file,video_link',
            'image_couverture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            DB::beginTransaction();

            // Récupérer le podcast existant
            $podcast = Podcast::findOrFail($id);
            $media = $podcast->media;

            if (!$media) {
                throw new \Exception('Média introuvable pour ce podcast');
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
            } elseif ($request->media_type === 'video_file') {
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

                // Traitement de l'image de couverture pour les vidéos fichiers
                if ($request->media_type === 'video_file' && $request->hasFile('image_couverture')) {
                    // Supprimer l'ancienne image de couverture
                    if ($media->thumbnail && Storage::disk('public')->exists($media->thumbnail)) {
                        Storage::disk('public')->delete($media->thumbnail);
                    }

                    $thumbnailFile = $request->file('image_couverture');
                    $thumbnailName = pathinfo($thumbnailFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $thumbnailUniqueName = $thumbnailName . '_thumb_' . now()->format('Ymd_His') . '.' . $thumbnailFile->getClientOriginalExtension();
                    $thumbnailPath = $thumbnailFile->storeAs('thumbnails', $thumbnailUniqueName, 'public');
                }
            } elseif ($request->media_type === 'video_link') {
                $request->validate([
                    'lien_video' => 'required|url',
                ]);

                $filePath = $request->lien_video;
                $type = 'link';
            }

            // Mise à jour du média
            $media->update([
                'url_fichier' => $filePath,
                'thumbnail' => $thumbnailPath,
                'type' => $type,
                'update_by' => auth()->id(),
            ]);

            // Mise à jour du podcast
            $podcast->update([
                'nom' => $request->nom,
                'description' => $request->description,
                'update_by' => auth()->id(),
            ]);

            DB::commit();
            notify()->success('Succès', 'Podcast mis à jour avec succès.');
            return redirect()->route('podcasts.index');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la mise à jour: ' . $e->getMessage());
            Alert::error('Erreur', 'Impossible de mettre à jour le podcast: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    public function destroy($id)
    {
        $podcast = Podcast::findOrFail($id);
        try {
            DB::beginTransaction();

            $podcast->update([
                'is_deleted' => true,
                'update_by' => auth()->id(),
            ]);

            // Marquer également le média comme supprimé
            if ($podcast->media) {
                $podcast->media->update([
                    'is_deleted' => true,
                    'update_by' => auth()->id(),
                ]);
            }

            DB::commit();
            notify()->success('Succès', 'Podcast supprimé avec succès.');
            return redirect()->route('podcasts.index');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }
}