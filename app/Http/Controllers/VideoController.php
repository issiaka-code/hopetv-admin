<?php

namespace App\Http\Controllers;

use Log;
use App\Models\Media;
use App\Models\Video;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use FFMpeg\Format\Video\X264;
use FFMpeg\Coordinate\Dimension;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;


class VideoController extends Controller
{
    public function index(Request $request)
    {
        $query = Video::with('media')->where('is_deleted', false)
            ->latest();

        // Recherche
        if ($request->filled('search')) {
            $query->where('nom', 'like', "%{$request->search}%")
            ->orWhere('description', 'like', "%{$request->search}%");    
        }

        // Filtrage par type
        if ($request->filled('type')) {
            $query->whereHas('media', function ($q) use ($request) {
                $q->where('type', $request->type === 'file' ? 'video' : 'link');
            });
        }

        $videos = $query->paginate(12);

        return view('admin.medias.videos.index', compact('videos'));
    }


    public function store(Request $request)
    {
        // Validation de base
        $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'required|string',
            'video_type' => 'required|in:file,link',
        ]);

        // Validation conditionnelle selon le type
        if ($request->video_type === 'file') {
            $request->validate([
                'fichier_video' => 'required|file|mimes:mp4,avi,mov,wmv,flv,mkv,webm|max:1024000', // Augmentez la taille si nécessaire
            ]);
        } elseif ($request->video_type === 'link') {
            $request->validate([
                'lien_video' => 'required|url',
            ]);
        }

        try {
            DB::beginTransaction();
            $filePaths = [];
            $type = $request->video_type === 'file' ? 'video' : 'link';

            if ($request->video_type === 'file' && $request->hasFile('fichier_video')) {
                $file = $request->file('fichier_video');

                if (!$file->isValid()) {
                    throw new \Exception('Fichier vidéo invalide');
                }

                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $uniqueName = $originalName . '_' . now()->format('Ymd_His');

                // Stocker le fichier original temporairement
                $tempPath = $file->storeAs('temp/videos', $uniqueName . '_original.' . $file->getClientOriginalExtension());

                // Convertir la vidéo avec qualité réduite
                $filePath = $this->reduceVideoQuality($tempPath, $uniqueName);

                // Supprimer le fichier temporaire
                Storage::delete($tempPath);

                $filePaths = $filePath;
            } elseif ($request->video_type === 'link') {
                $filePaths = $request->lien_video;

                if (!filter_var($filePaths, FILTER_VALIDATE_URL)) {
                    throw new \Exception('URL de vidéo invalide');
                }
            } else {
                throw new \Exception('Type de vidéo ou fichier manquant');
            }

            // Vérification finale
            if (empty($filePaths)) {
                throw new \Exception('Aucun fichier ou lien de vidéo fourni');
            }

            // Création du média
            $media = Media::create([
                'url_fichier' => $filePaths, // Stocker toutes les qualités
                'type' => $type,
                'insert_by' => auth()->id(),
                'update_by' => auth()->id(),
            ]);

            if (!$media) {
                throw new \Exception('Erreur lors de la création du média');
            }

            // Création de la vidéo
            $video = Video::create([
                'id_media' => $media->id,
                'nom' => $request->nom,
                'description' => $request->description,
                'insert_by' => auth()->id(),
                'update_by' => auth()->id(),
            ]);

            if (!$video) {
                throw new \Exception('Erreur lors de la création de la vidéo');
            }

            DB::commit();
            Alert::success('Succès', 'Vidéo créée avec succès');
            return redirect()->route('videos.index');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Alert::error('Erreur', $e->getMessage());
            return redirect()->back()
                ->with('error', 'Erreur lors de la création de la vidéo: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Convertit une vidéo pour le streaming avec différentes qualités
     */
    private function reduceVideoQuality($tempPath, $uniqueName)
    {
        try {
            // Format avec bas débit et résolution réduite
            $optimizedFormat = (new X264('aac'))->setKiloBitrate(500); // 500 kbps

            FFMpeg::fromDisk('local')
                ->open($tempPath)
                ->export()
                ->toDisk('public')
                ->inFormat($optimizedFormat)
                ->addFilter('-vf', 'scale=640:360') // Réduire la résolution
                ->addFilter('-r', '24') // Réduire le framerate
                ->addFilter('-crf', '28') // Augmenter la compression (23 par défaut)
                ->save("medias/videos/{$uniqueName}_optimized.mp4");

            return "medias/videos/{$uniqueName}_optimized.mp4";
        } catch (\Exception $e) {
            // Fallback: utiliser le fichier original si la conversion échoue
            $originalFile = Storage::get($tempPath);
            $fallbackPath = "medias/videos/{$uniqueName}.mp4";
            Storage::disk('public')->put($fallbackPath, $originalFile);

            return $fallbackPath;
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
        // Validation de base
        $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'required|string',
            'video_type' => 'required|in:file,link',
        ]);

        // Validation conditionnelle selon le type
        if ($request->video_type === 'file') {
            $request->validate([
                'fichier_video' => 'nullable|file|mimes:mp4,avi,mov,wmv,flv,mkv,webm|max:1024000',
            ]);
        } elseif ($request->video_type === 'link') {
            $request->validate([
                'lien_video' => 'required|url',
            ]);
        }

        try {
            DB::beginTransaction();

            // Récupérer la vidéo existante
            $video = Video::findOrFail($id);
            $media = $video->media;

            if (!$media) {
                throw new \Exception('Média introuvable pour cette vidéo');
            }

            $filePaths = $media->url_fichier;
            $type = $request->video_type === 'file' ? 'video' : 'link';

            // Si fichier uploadé
            if ($request->video_type === 'file' && $request->hasFile('fichier_video')) {
                $file = $request->file('fichier_video');

                if (!$file->isValid()) {
                    throw new \Exception('Fichier vidéo invalide');
                }

                // Supprimer l’ancien fichier
                if ($media->type === 'video' && Storage::exists($media->url_fichier)) {
                    Storage::delete($media->url_fichier);
                }

                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $uniqueName = $originalName . '_' . now()->format('Ymd_His');

                // Stocker temporairement
                $tempPath = $file->storeAs('temp/videos', $uniqueName . '_original.' . $file->getClientOriginalExtension());

                // Conversion qualité réduite
                $filePath = $this->reduceVideoQuality($tempPath, $uniqueName);

                // Supprimer le temporaire
                Storage::delete($tempPath);

                $filePaths = $filePath;
            } elseif ($request->video_type === 'link') {
                $filePaths = $request->lien_video;

                if (!filter_var($filePaths, FILTER_VALIDATE_URL)) {
                    throw new \Exception('URL de vidéo invalide');
                }
            }

            // Vérification finale
            if (empty($filePaths)) {
                throw new \Exception('Aucun fichier ou lien de vidéo fourni');
            }

            // Mise à jour du média
            $media->update([
                'url_fichier' => $filePaths,
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
            Alert::success('Succès', 'Vidéo mise à jour avec succès');
            return redirect()->route('videos.index');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Alert::error('Erreur', $e->getMessage());
            return redirect()->back()
                ->with('error', 'Erreur lors de la mise à jour de la vidéo: ' . $e->getMessage())
                ->withInput();
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
            Alert::success('Succès', 'Vidéo supprimée avec succès');
            return redirect()->route('videos.index');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }
}
