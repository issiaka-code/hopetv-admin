<?php

namespace App\Http\Controllers;

use Log;
use App\Models\Media;
use App\Models\Podcast;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use FFMpeg\Format\Video\X264;
use FFMpeg\Format\Audio\Aac;
use FFMpeg\Coordinate\Dimension;
use Illuminate\Support\Facades\DB;
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
            $query->where('titre', 'like', "%{$request->search}%")
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
            } elseif ($isVideoFile || $isAudio) {
                $thumbnailUrl = asset('storage/' . $podcast->media->url_fichier);
            }

            return (object)[
                'id' => $podcast->id,
                'titre' => $podcast->titre,
                'description' => $podcast->description,
                'created_at' => $podcast->created_at,
                'media_type' => $isAudio ? 'audio' : ($isVideoLink ? 'video_link' : 'video_file'),
                'thumbnail_url' => $thumbnailUrl,
            ];
        });

        // Envoyer chaque podcast comme variable séparée
        return view('admin.medias.podcasts.index', [
            'podcasts' => $podcasts,
            'podcastsData' => $podcastsData,
        ]);
    }


    public function store(Request $request)
    {
        // Validation de base
        $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'required|string',
            'media_type' => 'required|in:audio,video_file,video_link',
        ]);

        // Validation conditionnelle selon le type
        if ($request->media_type === 'audio') {
            $request->validate([
                'fichier_audio' => 'required|file|mimes:mp3,wav,aac,ogg,flac|max:512000', // 500MB max
            ]);
        } elseif ($request->media_type === 'video_file') {
            $request->validate([
                'fichier_video' => 'required|file|mimes:mp4,avi,mov,wmv,flv,mkv,webm|max:1024000', // 1GB max
            ]);
        } elseif ($request->media_type === 'video_link') {
            $request->validate([
                'lien_video' => 'required|url',
            ]);
        }

        try {
            DB::beginTransaction();
            $filePath = null;
            $type = $request->media_type === 'audio' ? 'audio' : ($request->media_type === 'video_file' ? 'video' : 'link');

            if ($request->media_type === 'audio' && $request->hasFile('fichier_audio')) {
                $file = $request->file('fichier_audio');

                if (!$file->isValid()) {
                    throw new \Exception('Fichier audio invalide');
                }

                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $uniqueName = $originalName . '_' . now()->format('Ymd_His');

                // Optimiser le fichier audio
                $filePath = $this->optimizeAudioFile($file, $uniqueName);
            } elseif ($request->media_type === 'video_file' && $request->hasFile('fichier_video')) {
                $file = $request->file('fichier_video');

                if (!$file->isValid()) {
                    throw new \Exception('Fichier vidéo invalide');
                }

                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $uniqueName = $originalName . '_' . now()->format('Ymd_His');

                // Optimiser le fichier vidéo
                $filePath = $this->optimizeVideoFile($file, $uniqueName);
            } elseif ($request->media_type === 'video_link') {
                $filePath = $request->lien_video;

                if (!filter_var($filePath, FILTER_VALIDATE_URL)) {
                    throw new \Exception('URL de vidéo invalide');
                }
            } else {
                throw new \Exception('Type de média ou fichier manquant');
            }

            // Vérification finale
            if (empty($filePath)) {
                throw new \Exception('Aucun fichier ou lien fourni');
            }

            // Création du média
            $media = Media::create([
                'url_fichier' => $filePath,
                'type' => $type,
                'insert_by' => auth()->id(),
                'update_by' => auth()->id(),
            ]);

            if (!$media) {
                throw new \Exception('Erreur lors de la création du média');
            }

            // Création du podcast
            $podcast = Podcast::create([
                'id_media' => $media->id,
                'titre' => $request->titre,
                'description' => $request->description,
                'insert_by' => auth()->id(),
                'update_by' => auth()->id(),
            ]);

            if (!$podcast) {
                throw new \Exception('Erreur lors de la création du podcast');
            }

            DB::commit();
            Alert::success('Succès', 'Podcast créé avec succès');
            return redirect()->route('podcasts.index');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Alert::error('Erreur', $e->getMessage());
            return redirect()->back()
                ->with('error', 'Erreur lors de la création du podcast: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Optimise un fichier audio pour le web
     */
    private function optimizeAudioFile($file, $uniqueName)
    {
        try {
            // Stocker temporairement le fichier original
            $tempPath = $file->storeAs('temp/audio', $uniqueName . '_original.' . $file->getClientOriginalExtension());

            // Convertir en format optimisé (MP3 128kbps)
            $format = new Aac();
            $format->setAudioKiloBitrate(128);

            FFMpeg::fromDisk('local')
                ->open($tempPath)
                ->export()
                ->toDisk('public')
                ->inFormat($format)
                ->save("medias/podcasts/audio/{$uniqueName}.mp3");

            // Supprimer le fichier temporaire
            Storage::delete($tempPath);

            return "medias/podcasts/audio/{$uniqueName}.mp3";
        } catch (\Exception $e) {
            // Fallback: utiliser le fichier original
            $originalFile = Storage::get($tempPath);
            $fallbackPath = "medias/podcasts/audio/{$uniqueName}." . $file->getClientOriginalExtension();
            Storage::disk('public')->put($fallbackPath, $originalFile);

            // Supprimer le temporaire
            Storage::delete($tempPath);

            return $fallbackPath;
        }
    }

    /**
     * Optimise un fichier vidéo pour le web
     */
    private function optimizeVideoFile($file, $uniqueName)
    {
        try {
            // Stocker temporairement le fichier original
            $tempPath = $file->storeAs('temp/videos', $uniqueName . '_original.' . $file->getClientOriginalExtension());

            // Format avec bas débit et résolution réduite
            $optimizedFormat = (new X264('aac'))->setKiloBitrate(500); // 500 kbps

            FFMpeg::fromDisk('local')
                ->open($tempPath)
                ->export()
                ->toDisk('public')
                ->inFormat($optimizedFormat)
                ->addFilter('-vf', 'scale=640:360') // Réduire la résolution
                ->addFilter('-r', '24') // Réduire le framerate
                ->addFilter('-crf', '28') // Augmenter la compression
                ->save("medias/podcasts/video/{$uniqueName}_optimized.mp4");

            // Supprimer le fichier temporaire
            Storage::delete($tempPath);

            return "medias/podcasts/video/{$uniqueName}_optimized.mp4";
        } catch (\Exception $e) {
            // Fallback: utiliser le fichier original
            $originalFile = Storage::get($tempPath);
            $fallbackPath = "medias/podcasts/video/{$uniqueName}." . $file->getClientOriginalExtension();
            Storage::disk('public')->put($fallbackPath, $originalFile);

            // Supprimer le temporaire
            Storage::delete($tempPath);

            return $fallbackPath;
        }
    }

    public function edit(Podcast $podcast)
    {
        $podcast->load('media');
        return response()->json([
            'titre' => $podcast->titre,
            'description' => $podcast->description,
            'media' => $podcast->media
        ]);
    }

    public function update(Request $request, $id)
    {
        // Validation de base
        $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'required|string',
            'media_type' => 'required|in:audio,video_file,video_link',
        ]);

        // Validation conditionnelle selon le type
        if ($request->media_type === 'audio') {
            $request->validate([
                'fichier_audio' => 'nullable|file|mimes:mp3,wav,aac,ogg,flac|max:512000',
            ]);
        } elseif ($request->media_type === 'video_file') {
            $request->validate([
                'fichier_video' => 'nullable|file|mimes:mp4,avi,mov,wmv,flv,mkv,webm|max:1024000',
            ]);
        } elseif ($request->media_type === 'video_link') {
            $request->validate([
                'lien_video' => 'required|url',
            ]);
        }

        try {
            DB::beginTransaction();

            // Récupérer le podcast existant
            $podcast = Podcast::findOrFail($id);
            $media = $podcast->media;

            if (!$media) {
                throw new \Exception('Média introuvable pour ce podcast');
            }

            $filePath = $media->url_fichier;
            $type = $request->media_type === 'audio' ? 'audio' : ($request->media_type === 'video_file' ? 'video' : 'link');

            // Gestion des fichiers selon le type
            if ($request->media_type === 'audio' && $request->hasFile('fichier_audio')) {
                $file = $request->file('fichier_audio');

                if (!$file->isValid()) {
                    throw new \Exception('Fichier audio invalide');
                }

                // Supprimer l'ancien fichier audio
                if ($media->type === 'audio' && Storage::disk('public')->exists($media->url_fichier)) {
                    Storage::disk('public')->delete($media->url_fichier);
                }

                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $uniqueName = $originalName . '_' . now()->format('Ymd_His');

                // Optimiser le nouveau fichier audio
                $filePath = $this->optimizeAudioFile($file, $uniqueName);
            } elseif ($request->media_type === 'video_file' && $request->hasFile('fichier_video')) {
                $file = $request->file('fichier_video');

                if (!$file->isValid()) {
                    throw new \Exception('Fichier vidéo invalide');
                }

                // Supprimer l'ancien fichier vidéo
                if ($media->type === 'video' && Storage::disk('public')->exists($media->url_fichier)) {
                    Storage::disk('public')->delete($media->url_fichier);
                }

                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $uniqueName = $originalName . '_' . now()->format('Ymd_His');

                // Optimiser le nouveau fichier vidéo
                $filePath = $this->optimizeVideoFile($file, $uniqueName);
            } elseif ($request->media_type === 'video_link') {
                $filePath = $request->lien_video;

                if (!filter_var($filePath, FILTER_VALIDATE_URL)) {
                    throw new \Exception('URL de vidéo invalide');
                }

                // Si on passe d'un fichier à un lien, supprimer l'ancien fichier
                if (($media->type === 'audio' || $media->type === 'video') &&
                    Storage::disk('public')->exists($media->url_fichier)
                ) {
                    Storage::disk('public')->delete($media->url_fichier);
                }
            }

            // Vérification finale
            if (empty($filePath)) {
                throw new \Exception('Aucun fichier ou lien fourni');
            }

            // Mise à jour du média
            $media->update([
                'url_fichier' => $filePath,
                'type' => $type,
                'update_by' => auth()->id(),
            ]);

            // Mise à jour du podcast
            $podcast->update([
                'titre' => $request->titre,
                'description' => $request->description,
                'update_by' => auth()->id(),
            ]);

            DB::commit();
            Alert::success('Succès', 'Podcast mis à jour avec succès');
            return redirect()->route('podcasts.index');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Alert::error('Erreur', $e->getMessage());
            return redirect()->back()
                ->with('error', 'Erreur lors de la mise à jour du podcast: ' . $e->getMessage())
                ->withInput();
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
            Alert::success('Succès', 'Podcast supprimé avec succès');
            return redirect()->route('podcasts.index');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }
}
