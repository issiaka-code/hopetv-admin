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
use App\Jobs\ProcessVideoJob;

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
        $videosData = collect($videos->items())->map(function ($video) {
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
                // Pour les vidéos fichiers, utiliser l'image de couverture si elle existe
                if ($video->media->thumbnail) {
                    $thumbnailUrl = asset('storage/' . $video->media->thumbnail);
                } else {
                    // Fallback sur la vidéo si pas d'image de couverture
                    $thumbnailUrl = asset('storage/' . $video->media->url_fichier);
                }
            }

            return (object)[
                'id' => $video->id,
                'nom' => $video->nom,
                'description' => $video->description,
                'created_at' => $video->created_at,
                'media_type' => $isVideoLink ? 'video_link' : 'video_file',
                'thumbnail_url' => $thumbnailUrl,
                'video_url' => $isVideoFile ? asset('storage/' . $video->media->url_fichier) : $thumbnailUrl,
                'has_thumbnail' => $isVideoFile && $video->media->thumbnail ? true : false,
                'is_published' => $video->media->is_published ?? true,
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
            'image_couverture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            if ($request->video_type === 'file') {
                $request->validate([
                    'fichier_video' => 'required|file|mimes:mp4,avi,mov,wmv,flv,mkv,webm|max:1024000',
                    'image_couverture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
                ]);

                $file = $request->file('fichier_video');
                $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $uniqueName = $filename . '_' . now()->format('Ymd_His') . '.mp4';
                $tempPath = $file->storeAs('temp/videos', "tmp_{$uniqueName}");

                $thumbnailPath = null;
                if ($request->hasFile('image_couverture')) {
                    $thumbnailFile = $request->file('image_couverture');
                    $thumbnailName = pathinfo($thumbnailFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $thumbnailUniqueName = $thumbnailName . '_thumb_' . now()->format('Ymd_His') . '.' . $thumbnailFile->getClientOriginalExtension();
                    $thumbnailPath = $thumbnailFile->storeAs('thumbnails', $thumbnailUniqueName, 'public');
                }

                // Création du média temporaire
                $media = Media::create([
                    'url_fichier' => null,
                    'thumbnail' => $thumbnailPath,
                    'type' => 'video',
                    'status' => 'processing',
                    'insert_by' => auth()->id(),
                    'update_by' => auth()->id(),
                ]);

                // Création de la vidéo en statut "processing"
                $video = Video::create([
                    'id_media' => $media->id,
                    'nom' => $request->nom,
                    'description' => $request->description,
                    'insert_by' => auth()->id(),
                    'update_by' => auth()->id(),
                    'status' => 'processing',
                ]);

                // Lancement du job asynchrone
                ProcessVideoJob::dispatch(
                    $tempPath,
                    $uniqueName,
                    [
                        'media_id' => $media->id,
                        'video_id' => $video->id,
                        'insert_by' => auth()->id(),
                        'update_by' => auth()->id(),
                    ],
                    $thumbnailPath
                );

                notify()->success('Succès', 'La vidéo est en cours de traitement et sera disponible bientôt.');
            } else {

                $request->validate(
                    ['lien_video' => 'required|url'],
                    ['image_couverture_link' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',]
                );

                $thumbnailPath = null;
                if ($request->hasFile('image_couverture_link')) {
                    $thumbnailFile = $request->file('image_couverture_link');
                    $thumbnailName = pathinfo($thumbnailFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $thumbnailUniqueName = $thumbnailName . '_thumb_' . now()->format('Ymd_His') . '.' . $thumbnailFile->getClientOriginalExtension();
                    $thumbnailPath = $thumbnailFile->storeAs('thumbnails', $thumbnailUniqueName, 'public');
                }

                $media = Media::create([
                    'url_fichier' => $request->lien_video,
                    'thumbnail' => $thumbnailPath,
                    'type' => 'link',
                    'insert_by' => auth()->id(),
                    'update_by' => auth()->id(),
                    'status' => 'ready',
                ]);

                $video = Video::create([
                    'id_media' => $media->id,
                    'nom' => $request->nom,
                    'description' => $request->description,
                    'insert_by' => auth()->id(),
                    'update_by' => auth()->id(),
                    'status' => 'ready',
                ]);



                notify()->success('Succès', 'Vidéo ajoutée avec succès (lien).');
            }

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
            'image_couverture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            DB::beginTransaction();

            $video = Video::findOrFail($id);
            $media = $video->media;

            if (!$media) {
                throw new \Exception('Média introuvable pour cette vidéo');
            }

            $filePath = $media->url_fichier;
            $thumbnailPath = $media->thumbnail;
            $type = $media->type;

            if ($request->video_type === 'file') {
                $request->validate([
                    'fichier_video' => 'nullable|file|mimes:mp4,avi,mov,wmv,flv,mkv,webm|max:1024000',
                    'image_couverture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                ]);

                if ($request->hasFile('fichier_video')) {
                    $file = $request->file('fichier_video');
                    $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $uniqueName = $filename . '_' . now()->format('Ymd_His') . '.mp4';
                    $tempPath = $file->storeAs('temp/videos', "tmp_{$uniqueName}");

                    // Supprimer ancien fichier vidéo
                    if ($media->type === 'video' && Storage::disk('public')->exists($media->url_fichier)) {
                        Storage::disk('public')->delete($media->url_fichier);
                    }

                    $filePath = null; // restera null jusqu'à la fin du job
                    $type = 'video';

                    // Gestion miniature
                    if ($request->hasFile('image_couverture')) {
                        if ($media->thumbnail && Storage::disk('public')->exists($media->thumbnail)) {
                            Storage::disk('public')->delete($media->thumbnail);
                        }
                        $thumbnailFile = $request->file('image_couverture');
                        $thumbnailName = pathinfo($thumbnailFile->getClientOriginalName(), PATHINFO_FILENAME);
                        $thumbnailUniqueName = $thumbnailName . '_thumb_' . now()->format('Ymd_His') . '.' . $thumbnailFile->getClientOriginalExtension();
                        $thumbnailPath = $thumbnailFile->storeAs('thumbnails', $thumbnailUniqueName, 'public');
                    }

                    // Mettre à jour statut vidéo en processing
                   
                    $media->update([
                        'thumbnail' => $thumbnailPath,
                        'type' => $type,
                        'status' => 'processing',
                        'update_by' => auth()->id(),
                    ]);

                    // Lancer le job asynchrone
                    ProcessVideoJob::dispatch(
                        $tempPath,
                        $uniqueName,
                        [
                            'media_id' => $media->id,
                            'video_id' => $video->id,
                            'insert_by' => auth()->id(),
                            'update_by' => auth()->id(),
                        ],
                        $thumbnailPath
                    );
                } else {
                    // Pas de nouveau fichier : juste mettre à jour la miniature si fournie
                    if ($request->hasFile('image_couverture')) {
                        if ($media->thumbnail && Storage::disk('public')->exists($media->thumbnail)) {
                            Storage::disk('public')->delete($media->thumbnail);
                        }
                        $thumbnailFile = $request->file('image_couverture');
                        $thumbnailName = pathinfo($thumbnailFile->getClientOriginalName(), PATHINFO_FILENAME);
                        $thumbnailUniqueName = $thumbnailName . '_thumb_' . now()->format('Ymd_His') . '.' . $thumbnailFile->getClientOriginalExtension();
                        $thumbnailPath = $thumbnailFile->storeAs('thumbnails', $thumbnailUniqueName, 'public');
                        $media->update(['thumbnail' => $thumbnailPath]);
                    }
                }
            } elseif ($request->video_type === 'link') {
                $request->validate([
                    'lien_video' => 'required|url',
                ],['editimage_couverture_link' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',]
            );


                 if ($request->hasFile('editimage_couverture_link')) {
                        if ($media->thumbnail && Storage::disk('public')->exists($media->thumbnail)) {
                            Storage::disk('public')->delete($media->thumbnail);
                        }
                        $thumbnailFile = $request->file('editimage_couverture_link');
                        $thumbnailName = pathinfo($thumbnailFile->getClientOriginalName(), PATHINFO_FILENAME);
                        $thumbnailUniqueName = $thumbnailName . '_thumb_' . now()->format('Ymd_His') . '.' . $thumbnailFile->getClientOriginalExtension();
                        $thumbnailPath = $thumbnailFile->storeAs('thumbnails', $thumbnailUniqueName, 'public');
                        $media->update(['thumbnail' => $thumbnailPath]);
                    }

                $filePath = $request->lien_video;
                $type = 'link';
                $video->update(['status' => 'ready']);
            }

            // Mise à jour média et vidéo
            $media->update([
                'url_fichier' => $filePath,
                'type' => $type,
                'update_by' => auth()->id(),
            ]);

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

    public function publish(Request $request, $id)
    {
        $video = Video::findOrFail($id);
        try {
            DB::beginTransaction();

            if ($video->media) {
                $video->media->update([
                    'is_published' => true,
                    'update_by' => auth()->id(),
                ]);
            }

            DB::commit();
            notify()->success('Succès', 'Vidéo publiée avec succès.');
            if ($request->ajax()) {
                return response()->json(['success' => true]);
            }
            return redirect()->route('videos.index');
        } catch (\Exception $e) {
            DB::rollBack();
            notify()->error('Erreur', 'Impossible de publier la vidéo: ' . $e->getMessage());
            if ($request->ajax()) {
                return response()->json(['success' => false], 500);
            }
            return redirect()->back();
        }
    }

    public function unpublish(Request $request, $id)
    {
        $video = Video::findOrFail($id);
        try {
            DB::beginTransaction();

            if ($video->media) {
                $video->media->update([
                    'is_published' => false,
                    'update_by' => auth()->id(),
                ]);
            }

            DB::commit();
            notify()->success('Succès', 'Vidéo dépubliée avec succès.');
            if ($request->ajax()) {
                return response()->json(['success' => true]);
            }
            return redirect()->route('videos.index');
        } catch (\Exception $e) {
            DB::rollBack();
            notify()->error('Erreur', 'Impossible de dépublier la vidéo: ' . $e->getMessage());
            if ($request->ajax()) {
                return response()->json(['success' => false], 500);
            }
            return redirect()->back();
        }
    }

    public function voirVideo($id)
    {
        try {
            $video = Video::with('media')->findOrFail($id);

            // Vérifie le statut de la vidéo
            if ($video->media->status !== 'ready') {
                return response()->json([
                    'success' => false,
                    'message' => 'La vidéo est encore en cours de traitement. Veuillez réessayer plus tard.',
                    'status' => $video->status,
                ]);
            }

            $media = $video->media;
            $url = $media->url_fichier;

            // 🔹 Si c’est un lien externe, on le traite
            if ($media->type === 'link') {

                // Conversion automatique pour les liens YouTube
                if (str_contains($url, 'youtube.com/watch?v=')) {
                    $url = str_replace('watch?v=', 'embed/', $url);
                } elseif (str_contains($url, 'youtu.be/')) {
                    $url = str_replace('youtu.be/', 'www.youtube.com/embed/', $url);
                }

                // (Optionnel) conversion Vimeo
                if (str_contains($url, 'vimeo.com/')) {
                    $videoId = basename(parse_url($url, PHP_URL_PATH));
                    $url = "https://player.vimeo.com/video/" . $videoId;
                }
            } else {
                // 🔹 Si c’est un fichier local, on le met depuis le dossier storage
                $url = asset('storage/' . $media->url_fichier);
            }

            return response()->json([
                'success' => true,
                'video' => [
                    'id' => $video->id,
                    'nom' => $video->nom,
                    'description' => $video->description,
                    'url' => $url,
                    'thumbnail' => $media->thumbnail ? asset('storage/' . $media->thumbnail) : null,
                    'type' => $media->type,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement de la vidéo : ' . $e->getMessage(),
            ], 500);
        }
    }
}
