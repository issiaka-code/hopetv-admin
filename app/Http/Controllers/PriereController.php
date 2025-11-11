<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessVideoJob;
use App\Models\Media;
use App\Models\Priere;
use App\Services\MediaService;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

class PriereController extends Controller
{
    protected $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }
    public function index(Request $request)
    {
        $query = Priere::with('media')->where('is_deleted', false)->latest();

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
                } elseif ($request->type === 'pdf') {
                    $q->where('type', 'pdf');
                } elseif ($request->type === 'images') {
                    $q->where('type', 'images');
                }
            });
        }

        $prieres = $query->paginate(12);

        // Préparer chaque variable pour la vue et JS
        $prieresData = collect($prieres->items())->map(function ($priere) {
            $isAudio = $priere->media && $priere->media->type === 'audio';
            $isVideoLink = $priere->media && $priere->media->type === 'link';
            $isVideoFile = $priere->media && $priere->media->type === 'video';
            $isPdf = $priere->media && $priere->media->type === 'pdf';
            $isImages = $priere->media && $priere->media->type === 'images';

            return (object)[
                'id' => $priere->id,
                'nom' => $priere->nom,
                'description' => $priere->description,
                'created_at' => $priere->created_at,
                'media_type' => $isAudio ? 'audio' : ($isVideoLink ? 'video_link' : ($isVideoFile ? 'video_file' : ($isPdf ? 'pdf' : ($isImages ? 'images' : null)))),
                'thumbnail_url' => asset('storage/' . $priere->media->thumbnail),
               // 'video_url' => $isVideoFile ? asset('storage/' . $priere->media->url_fichier) : $thumbnailUrl,
                'media_url' => $priere->media && !$isImages ? asset('storage/' . $priere->media->url_fichier) : null,
                'has_thumbnail' => $priere->media && $priere->media->thumbnail ? true : ($isImages && !empty(json_decode($priere->media->url_fichier ?? '[]', true))),
                'is_published' => $priere->media->is_published ?? true,
                'images' => $isImages ? array_map(function ($p) {
                    return asset('storage/' . $p);
                }, (array)(json_decode($priere->media->url_fichier ?? '[]', true) ?: [])) : [],
            ];
        });
        // Envoyer chaque prière comme variable séparée
        return view('admin.medias.prieres.index', [
            'prieres' => $prieres,
            'prieresData' => $prieresData,
        ]);
    }
    public function store(Request $request)
    {
        $result = $this->mediaService->createMedia($request);

        if (is_array($result) && isset($result['success']) && $result['success'] === false) {
            $errors = $result['errors'];

            if ($errors instanceof \Illuminate\Support\MessageBag) {
                // Erreurs de validation Laravel
                foreach ($errors->all() as $error) {
                    notify()->error($error);
                }
            } elseif (is_array($errors)) {
                // Si jamais tu retournes un tableau d’erreurs
                foreach ($errors as $error) {
                    notify()->error($error);
                }
            } elseif (is_string($errors)) {
                // Erreur simple sous forme de message texte
                notify()->error($errors);
            }

            return back()->withInput();
        }
        $media = $result;

        $priere = Priere::create([
            'id_media' => $media->id,
            'nom' => $request->nom,
            'description' => $request->description,
            'insert_by' => auth()->id(),
            'update_by' => auth()->id(),
        ]);


        notify()->success('Succès', 'Prière ajoutée avec succès.');
        return redirect()->route('prieres.index');
    }

    public function update(Request $request, $id)
    {
        $priere = Priere::findOrFail($id);
        $media = $priere->media;

        $result = $this->mediaService->updateMedia($request, $media);

        if (is_array($result) && isset($result['success']) && $result['success'] === false) {
            $errors = $result['errors'];

            if ($errors instanceof \Illuminate\Support\MessageBag) {
                // Erreurs de validation Laravel
                foreach ($errors->all() as $error) {
                    notify()->error($error);
                }
            } elseif (is_array($errors)) {
                // Si jamais tu retournes un tableau d’erreurs
                foreach ($errors as $error) {
                    notify()->error($error);
                }
            } elseif (is_string($errors)) {
                // Erreur simple sous forme de message texte
                notify()->error($errors);
            }

            return back()->withInput();
        }


        $priere->update([
            'nom' => $request->nom,
            'description' => $request->description,
            'update_by' => auth()->id(),
        ]);

        notify()->success('Succès', 'Prière mise à jour avec succès.');
        return redirect()->route('prieres.index');
    }


    public function edit(Priere $priere)
    {
        $priere->load('media');
        return response()->json([
            'nom' => $priere->nom,
            'description' => $priere->description,
            'media' => $priere->media
        ]);
    }



    public function destroy($id)
    {
        $priere = Priere::findOrFail($id);
        try {
            DB::beginTransaction();

            $priere->update([
                'is_deleted' => true,
                'update_by' => auth()->id(),
            ]);

            DB::commit();
            notify()->success('Succès', 'Prière supprimée avec succès.');
            return redirect()->route('prieres.index');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PriereController@destroy: erreur', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            Alert::error('Erreur', 'Impossible de supprimer la prière: ' . $e->getMessage());
            return back();
        }
    }

    public function publish($id)
    {
        try {
            $priere = Priere::findOrFail($id);
            $priere->media->update(['is_published' => true]);

            notify()->success('Succès', 'Prière publiée avec succès.');
            return redirect()->route('prieres.index');
        } catch (\Exception $e) {
            Alert::error('Erreur', 'Impossible de publier la prière.');
            return back();
        }
    }

    public function unpublish($id)
    {
        try {
            $priere = Priere::findOrFail($id);
            $priere->media->update(['is_published' => false]);

            notify()->success('Succès', 'Prière dépubliée avec succès.');
            return redirect()->route('prieres.index');
        } catch (\Exception $e) {
            Alert::error('Erreur', 'Impossible de dépublier la prière.');
            return back();
        }
    }

    public function voirPriere($id)
    {
        try {
            $priere = Priere::with('media')->findOrFail($id);
            $media = $priere->media;

            if ($media->status !== 'ready') {
                return response()->json([
                    'status' => 'processing',
                    'message' => 'Le média est en cours de traitement. Veuillez réessayer plus tard.'
                ], 200);
            }

            $url = $media->url_fichier;

            // ✅ Traitement spécial pour les liens vidéo
            if ($media->type === 'link' && !empty($url)) {
                if (str_contains($url, 'youtube.com/watch?v=')) {
                    $url = str_replace('watch?v=', 'embed/', $url);
                } elseif (str_contains($url, 'youtu.be/')) {
                    $url = str_replace('youtu.be/', 'www.youtube.com/embed/', $url);
                } elseif (str_contains($url, 'vimeo.com/')) {
                    $videoId = basename(parse_url($url, PHP_URL_PATH));
                    $url = "https://player.vimeo.com/video/" . $videoId;
                }
            }

            // ✅ Si le média contient plusieurs images
            if ($media->type === 'images') {
                $images = json_decode($media->url_fichier, true) ?? [];
                $imageUrls = array_map(fn($path) => asset('storage/' . $path), $images);
            }

            // ✅ Préparation de la réponse finale
            return response()->json([
                'status' => 'ready',
                'priere' => [
                    'id' => $priere->id,
                    'nom' => $priere->nom,
                    'description' => $priere->description,
                    'media' => [
                        'url' => $media->type === 'images' ? ($imageUrls ?? []) : (
                            in_array($media->type, ['audio', 'video', 'pdf']) ? asset('storage/' . $url) : $url
                        ),
                        'thumbnail' => $media->thumbnail ? asset('storage/' . $media->thumbnail) : null,
                        'type' => $media->type,
                    ],
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors du chargement de la prière : ' . $e->getMessage(),
            ], 500);
        }
    }
}
