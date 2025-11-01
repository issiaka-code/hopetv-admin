<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessVideoJob;
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

        $podcastsData = collect($podcasts->items())->map(function ($podcast) {
            $isAudio = $podcast->media && $podcast->media->type === 'audio';
            $isVideoLink = $podcast->media && $podcast->media->type === 'link';
            $isVideoFile = $podcast->media && $podcast->media->type === 'video';

            $thumbnailUrl = null;
            $hasThumbnail = false;

            // Priorité à l'image de couverture si elle existe
            if ($podcast->media && $podcast->media->thumbnail) {
                $thumbnailUrl = asset('storage/' . $podcast->media->thumbnail);
                $hasThumbnail = true;
            }
            // Sinon, utiliser les thumbnails par défaut selon le type
            else {
                if ($isVideoLink) {
                    $rawUrl = $podcast->media->url_fichier;
                     $thumbnailUrl = asset('storage/' . $podcast->media->thumbnail);
                    
                }
            }

            return (object)[
                'id' => $podcast->id,
                'nom' => $podcast->nom,
                'description' => $podcast->description,
                'created_at' => $podcast->created_at,
                'media_type' => $isAudio ? 'audio' : ($isVideoLink ? 'video_link' : 'video_file'),
                'thumbnail_url' => $thumbnailUrl,
                'media_url' => $isVideoFile || $isAudio ? asset('storage/' . $podcast->media->url_fichier) : $podcast->media->url_fichier,
                'has_thumbnail' => $hasThumbnail,
                'is_published' => $podcast->media->is_published ?? true,
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
        ]);

        try {
            // Variables pour le fichier et la miniature
            $filePath = null;
            $thumbnailPath = null;
            $type = null;
            $status = 'ready';

            if ($request->media_type === 'audio') {
                $request->validate([
                    'fichier_audio' => 'required|file|mimes:mp3,wav,aac,ogg,flac|max:512000',
                    'image_couverture_audio' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
                ]);

                // Traitement du fichier audio
                $file = $request->file('fichier_audio');
                $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $uniqueName = $filename . '_' . now()->format('Ymd_His') . '.mp3';
                $filePath = $file->storeAs('audios', $uniqueName, 'public');

                // Traitement de l'image de couverture
                if ($request->hasFile('image_couverture_audio')) {
                    $thumbnailFile = $request->file('image_couverture_audio');
                    $thumbnailName = pathinfo($thumbnailFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $thumbnailUniqueName = $thumbnailName . '_thumb_' . now()->format('Ymd_His') . '.' . $thumbnailFile->getClientOriginalExtension();
                    $thumbnailPath = $thumbnailFile->storeAs('thumbnails', $thumbnailUniqueName, 'public');
                }

                $type = 'audio';
            } elseif ($request->media_type === 'video_file') {
                $request->validate([
                    'fichier_video' => 'required|file|mimes:mp4,avi,mov,wmv,flv,mkv,webm|max:1024000',
                    'image_couverture_video' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
                ]);

                // Traitement du fichier vidéo
                $file = $request->file('fichier_video');
                $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $uniqueName = $filename . '_' . now()->format('Ymd_His') . '.mp4';

                // Stockage temporaire
                $tempPath = $file->storeAs('temp/videos', "tmp_{$uniqueName}");

                // Traitement avec FFmpeg


                // Traitement de l'image de couverture
                if ($request->hasFile('image_couverture_video')) {
                    $thumbnailFile = $request->file('image_couverture_video');
                    $thumbnailName = pathinfo($thumbnailFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $thumbnailUniqueName = $thumbnailName . '_thumb_' . now()->format('Ymd_His') . '.' . $thumbnailFile->getClientOriginalExtension();
                    $thumbnailPath = $thumbnailFile->storeAs('thumbnails', $thumbnailUniqueName, 'public');
                }
                $status = 'processing';
                $type = 'video';
            } elseif ($request->media_type === 'video_link') {
                $request->validate([
                    'lien_video' => 'required|url',
                    'image_couverture_videolink' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
                ]);


                if ($request->hasFile('image_couverture_videolink')) {
                    $thumbnailFile = $request->file('image_couverture_videolink');
                    $thumbnailName = pathinfo($thumbnailFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $thumbnailUniqueName = $thumbnailName . '_thumb_' . now()->format('Ymd_His') . '.' . $thumbnailFile->getClientOriginalExtension();
                    $thumbnailPath = $thumbnailFile->storeAs('thumbnails', $thumbnailUniqueName, 'public');
                }

                $filePath = $request->lien_video;
                $type = 'link';
            }

            // Créer l'enregistrement média
            $media = Media::create([
                'url_fichier' => $filePath,
                'thumbnail' => $thumbnailPath,
                'type' => $type,
                'insert_by' => auth()->id(),
                'update_by' => auth()->id(),
                'status' => $status
            ]);

            // Créer le podcast
            $podcast = Podcast::create([
                'id_media' => $media->id,
                'nom' => $request->nom,
                'description' => $request->description,
                'insert_by' => auth()->id(),
                'update_by' => auth()->id(),
            ]);

            if ($type === 'video') {
                ProcessVideoJob::dispatch(
                    $tempPath,
                    $uniqueName,
                    [
                        'media_id' => $media->id,
                        'video_id' => null,
                        'insert_by' => auth()->id(),
                        'update_by' => auth()->id(),
                    ],
                    $thumbnailPath
                );
            }

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

    public function update(Request $request, Podcast $podcast)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'required|string',
            'media_type' => 'required|in:audio,video_file,video_link',
        ]);

        try {
            $media = $podcast->media;
            $filePath = $media->url_fichier;
            $thumbnailPath = $media->thumbnail;
            $type = $media->type;
            $status = $media->status ?? 'ready';

            // ====== GESTION DU TYPE MEDIA ======
            if ($request->media_type === 'audio') {
                $request->validate([
                    'fichier_audio' => 'nullable|file|mimes:mp3,wav,aac,ogg,flac|max:512000',
                    'image_couverture_audio' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                ]);

                if ($request->hasFile('fichier_audio')) {
                    if ($media->type === 'audio' && Storage::disk('public')->exists($filePath)) {
                        Storage::disk('public')->delete($filePath);
                    }

                    $file = $request->file('fichier_audio');
                    $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $uniqueName = $filename . '_' . now()->format('Ymd_His') . '.mp3';
                    $filePath = $file->storeAs('audios', $uniqueName, 'public');
                }

                if ($request->hasFile('image_couverture_audio')) {
                    if ($thumbnailPath && Storage::disk('public')->exists($thumbnailPath)) {
                        Storage::disk('public')->delete($thumbnailPath);
                    }

                    $thumbnailFile = $request->file('image_couverture_audio');
                    $thumbnailName = pathinfo($thumbnailFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $thumbnailUniqueName = $thumbnailName . '_thumb_' . now()->format('Ymd_His') . '.' . $thumbnailFile->getClientOriginalExtension();
                    $thumbnailPath = $thumbnailFile->storeAs('thumbnails', $thumbnailUniqueName, 'public');
                }

                $type = 'audio';
                $status = 'ready';
            } elseif ($request->media_type === 'video_file') {
                $request->validate([
                    'fichier_video' => 'nullable|file|mimes:mp4,avi,mov,wmv,flv,mkv,webm|max:1024000',
                    'image_couverture_video' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                ]);

                if ($request->hasFile('fichier_video')) {
                    if ($media->type === 'video' && Storage::disk('public')->exists($filePath)) {
                        Storage::disk('public')->delete($filePath);
                    }

                    $file = $request->file('fichier_video');
                    $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $uniqueName = $filename . '_' . now()->format('Ymd_His') . '.mp4';
                    $tempPath = $file->storeAs('temp/videos', "tmp_{$uniqueName}");

                    // Statut en cours de traitement
                    $status = 'processing';

                    // Lancer le job de traitement
                    ProcessVideoJob::dispatch(
                        $tempPath,
                        $uniqueName,
                        [
                            'media_id' => $media->id,
                            'video_id' => $podcast->id,
                            'insert_by' => auth()->id(),
                            'update_by' => auth()->id(),
                        ],
                        $thumbnailPath
                    );
                }

                if ($request->hasFile('image_couverture_video')) {
                    if ($thumbnailPath && Storage::disk('public')->exists($thumbnailPath)) {
                        Storage::disk('public')->delete($thumbnailPath);
                    }

                    $thumbnailFile = $request->file('image_couverture_video');
                    $thumbnailName = pathinfo($thumbnailFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $thumbnailUniqueName = $thumbnailName . '_thumb_' . now()->format('Ymd_His') . '.' . $thumbnailFile->getClientOriginalExtension();
                    $thumbnailPath = $thumbnailFile->storeAs('thumbnails', $thumbnailUniqueName, 'public');
                }

                $type = 'video';
            } elseif ($request->media_type === 'video_link') {
                $request->validate([
                    'lien_video' => 'required|url',
                    'image_couverture_videolink' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                ]);

                $filePath = $request->lien_video;

                if ($request->hasFile('image_couverture_videolink')) {
                    if ($thumbnailPath && Storage::disk('public')->exists($thumbnailPath)) {
                        Storage::disk('public')->delete($thumbnailPath);
                    }

                    $thumbnailFile = $request->file('image_couverture_videolink');
                    $thumbnailName = pathinfo($thumbnailFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $thumbnailUniqueName = $thumbnailName . '_thumb_' . now()->format('Ymd_His') . '.' . $thumbnailFile->getClientOriginalExtension();
                    $thumbnailPath = $thumbnailFile->storeAs('thumbnails', $thumbnailUniqueName, 'public');
                }

                $type = 'link';
                $status = 'ready';
            }

            // ====== MISE À JOUR BDD ======
            $media->update([
                'url_fichier' => $filePath,
                'thumbnail'   => $thumbnailPath,
                'type'        => $type,
                'status'      => $status,
                'update_by'   => auth()->id(),
            ]);

            $podcast->update([
                'nom'         => $request->nom,
                'description' => $request->description,
                'update_by'   => auth()->id(),
            ]);

            notify()->success('Succès', 'Podcast mis à jour avec succès.');
            return redirect()->route('podcasts.index');
        } catch (\Exception $e) {
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

    public function publish(Request $request, $id)
    {
        $podcast = Podcast::findOrFail($id);

        // Vérifier que c'est une vidéo
        if (!$podcast->media || !in_array($podcast->media->type, ['video', 'link'])) {
            Alert::error('Erreur', 'Seules les vidéos peuvent être publiées/dépubliées.');
            return redirect()->back();
        }

        try {
            $podcast->media->update([
                'is_published' => true,
                'update_by' => auth()->id(),
            ]);

            notify()->success('Succès', 'Podcast vidéo publié avec succès.');
            if ($request->ajax()) {
                return response()->json(['success' => true]);
            }
            return redirect()->back();
        } catch (\Exception $e) {
            Log::error('Erreur lors de la publication: ' . $e->getMessage());
            Alert::error('Erreur', 'Impossible de publier le podcast.');
            if ($request->ajax()) {
                return response()->json(['success' => false], 500);
            }
            return redirect()->back();
        }
    }

    public function unpublish(Request $request, $id)
    {
        $podcast = Podcast::findOrFail($id);

        // Vérifier que c'est une vidéo
        if (!$podcast->media || !in_array($podcast->media->type, ['video', 'link'])) {
            Alert::error('Erreur', 'Seules les vidéos peuvent être publiées/dépubliées.');
            return redirect()->back();
        }

        try {
            $podcast->media->update([
                'is_published' => false,
                'update_by' => auth()->id(),
            ]);

            notify()->success('Succès', 'Podcast vidéo dépublié avec succès.');
            if ($request->ajax()) {
                return response()->json(['success' => true]);
            }
            return redirect()->back();
        } catch (\Exception $e) {
            Log::error('Erreur lors de la dépublication: ' . $e->getMessage());
            Alert::error('Erreur', 'Impossible de dépublier le podcast.');
            if ($request->ajax()) {
                return response()->json(['success' => false], 500);
            }
            return redirect()->back();
        }
    }

    public function voirpodcast($id)
    {
        try {
            $podcast = Podcast::with('media')->findOrFail($id);
            $media = $podcast->media;

            // Vérifier si le média est en traitement
            if ($media->status !== 'ready') {
                return response()->json([
                    'status' => 'processing',
                    'message' => 'La vidéo ou le média est en cours de traitement. Veuillez réessayer plus tard.'
                ], 200);
            }

            // Préparer l'URL selon le type
            $url = $media->url_fichier;

            if ($media->type === 'link' && !empty($url)) {
                // Conversion automatique pour les liens YouTube
                if (str_contains($url, 'youtube.com/watch?v=')) {
                    $url = str_replace('watch?v=', 'embed/', $url);
                } elseif (str_contains($url, 'youtu.be/')) {
                    $url = str_replace('youtu.be/', 'www.youtube.com/embed/', $url);
                }

                // Conversion Vimeo
                if (str_contains($url, 'vimeo.com/')) {
                    $videoId = basename(parse_url($url, PHP_URL_PATH));
                    $url = "https://player.vimeo.com/video/" . $videoId;
                }
            }

            // Si le média est prêt
            return response()->json([
                'status' => 'ready',
                'podcast' => [
                    'id' => $podcast->id,
                    'nom' => $podcast->nom,
                    'description' => $podcast->description,
                    'media' => [
                        'url' => $url,
                        'thumbnail' => $media->thumbnail ? asset('storage/' . $media->thumbnail) : null,
                        'type' => $media->type,
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors du chargement du podcast : ' . $e->getMessage(),
            ], 500);
        }
    }
}
