<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\Video;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

class VideoController extends Controller
{
    public function index(Request $request)
    {
        $query = Video::with('media')->where('is_deleted', false)->latest();

        // Recherche
        if ($request->filled('search')) {
            $query->where('nom', 'like', "%{$request->search}%")
                ->orWhere('description', 'like', "%{$request->search}%");
        }

        // Filtrage par type
        if ($request->filled('type')) {
            $query->whereHas('media', function ($q) use ($request) {
                if ($request->type === 'file') {
                    $q->where('type', 'video');
                } elseif ($request->type === 'link') {
                    $q->where('type', 'link');
                }
            });
        }

        $videos = $query->paginate(12);

        // Préparer chaque variable pour la vue et JS
        $videosData = $videos->map(function ($video) {
            $isVideoLink = $video->media && $video->media->type === 'link';
            $isVideoFile = $video->media && $video->media->type === 'video';

            $thumbnailUrl = null;

            if ($isVideoLink) {
                $rawUrl = $video->media->url_fichier;

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
                $thumbnailUrl = asset('storage/' . $video->media->url_fichier);
            }

            return (object)[
                'id' => $video->id,
                'nom' => $video->nom,
                'description' => $video->description,
                'created_at' => $video->created_at,
                'media_type' => $isVideoLink ? 'video_link' : 'video_file',
                'thumbnail_url' => $thumbnailUrl,
            ];
        });

        return view('admin.medias.videos.index', [
            'videos' => $videos,
            'videosData' => $videosData,
        ]);
    }

        public function store(Request $request)
        {
            $request->validate([
                'nom' => 'required|string|max:255',
                'description' => 'required|string',
                'video_type' => 'required|in:file,link',
            ]);

            try {
                if ($request->video_type === 'file') {
                    $request->validate([
                        'fichier_video' => 'required|file|mimes:mp4,avi,mov,wmv,flv,mkv,webm|max:1024000',
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
                } elseif ($request->video_type === 'link') {
                    $request->validate([
                        'lien_video' => 'required|url',
                    ]);
                    $filePath = $request->lien_video;
                }

                // Déterminer le type pour la base de données
                $type = $request->video_type === 'file' ? 'video' : 'link';

                // Créer l'enregistrement média
                $media = Media::create([
                    'url_fichier' => $filePath,
                    'type' => $type,
                    'insert_by' => auth()->id(),
                    'update_by' => auth()->id(),
                ]);

                // Créer la vidéo
                Video::create([
                    'id_media' => $media->id,
                    'nom' => $request->nom,
                    'description' => $request->description,
                    'insert_by' => auth()->id(),
                    'update_by' => auth()->id(),
                ]);
                notify()->success('Succès', 'Vidéo ajoutée avec succès.');
                return redirect()->route('videos.index');
            } catch (\Exception $e) {
                Log::error('Erreur lors de la création: ' . $e->getMessage());
                notify()->error('Erreur', 'Impossible d\'ajouter la vidéo: ' . $e->getMessage());
                return back()->withInput();
            }
        }

    public function edit(Video $video)
    {
        $video->load('media');
        return response()->json([
            'nom' => $video->nom,
            'description' => $video->description,
            'media' => $video->media
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'required|string',
            'video_type' => 'required|in:file,link',
        ]);

        try {
            DB::beginTransaction();

            // Récupérer la vidéo existante
            $video = Video::findOrFail($id);
            $media = $video->media;

            if (!$media) {
                throw new \Exception('Média introuvable pour cette vidéo');
            }

            $filePath = $media->url_fichier; // par défaut, garder l'ancien fichier
            $type = $media->type;

            if ($request->video_type === 'file') {
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
            } elseif ($request->video_type === 'link') {
                $request->validate([
                    'lien_video' => 'required|url',
                ]);

                $filePath = $request->lien_video;
                $type = 'link';
            }

            // Mise à jour du média
            $media->update([
                'url_fichier' => $filePath,
                'type' => $type,
                'update_by' => auth()->id(),
            ]);

            // Mise à jour de la vidéo
            $video->update([
                'nom' => $request->nom,
                'description' => $request->description,
                'update_by' => auth()->id(),
            ]);

            DB::commit();
            notify()->success('Succès', 'Vidéo mise à jour avec succès.');
            return redirect()->route('videos.index');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la mise à jour: ' . $e->getMessage());
            notify()->error('Erreur', 'Impossible de mettre à jour la vidéo: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    public function destroy($id)
    {
        $video = Video::findOrFail($id);
        try {
            DB::beginTransaction();

            $video->update([
                'is_deleted' => true,
                'update_by' => auth()->id(),
            ]);

            // Marquer également le média comme supprimé
            if ($video->media) {
                $video->media->update([
                    'is_deleted' => true,
                    'update_by' => auth()->id(),
                ]);
            }

            DB::commit();
            notify()->success('Succès', 'Vidéo supprimée avec succès.');
            return redirect()->route('videos.index');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }
}
