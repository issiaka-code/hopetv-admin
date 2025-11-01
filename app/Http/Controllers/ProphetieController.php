<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessVideoJob;
use App\Models\Media;
use App\Models\Prophetie;
use App\Services\MediaService;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

class ProphetieController extends Controller
{
    protected $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    public function index(Request $request)
    {
        $query = Prophetie::with('media')->where('is_deleted', false)->latest();

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

        $propheties = $query->paginate(12);

        // Préparer chaque variable pour la vue et JS
        $prophetiesData = collect($propheties->items())->map(function ($prophetie) {
            $isAudio = $prophetie->media && $prophetie->media->type === 'audio';
            $isVideoLink = $prophetie->media && $prophetie->media->type === 'link';
            $isVideoFile = $prophetie->media && $prophetie->media->type === 'video';
            $isPdf = $prophetie->media && $prophetie->media->type === 'pdf';
            $isImages = $prophetie->media && $prophetie->media->type === 'images';


            $thumbnailUrl = null;

            if ($isVideoLink) {
                $rawUrl = $prophetie->media->url_fichier;

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
                if ($prophetie->media->thumbnail) {
                    $thumbnailUrl = asset('storage/' . $prophetie->media->thumbnail);
                } else {
                    $thumbnailUrl = asset('storage/' . $prophetie->media->url_fichier);
                }
            } elseif ($isAudio || $isPdf) {
                // Pour les audios et PDFs, utiliser l'image de couverture si disponible
                if ($prophetie->media->thumbnail) {
                    $thumbnailUrl = asset('storage/' . $prophetie->media->thumbnail);
                } else {
                    $thumbnailUrl = null; // Pas d'image, on utilisera l'icône par défaut
                }
            } elseif ($isImages) {
                // Pour les images, utiliser la couverture si dispo, sinon la première image de url_fichier (JSON)
                if ($prophetie->media->thumbnail) {
                    $thumbnailUrl = asset('storage/' . $prophetie->media->thumbnail);
                } else {
                    $imagesArr = [];
                    if (!empty($prophetie->media->url_fichier)) {
                        $decoded = json_decode($prophetie->media->url_fichier, true);
                        $imagesArr = is_array($decoded) ? $decoded : [];
                    }
                    $first = count($imagesArr) > 0 ? $imagesArr[0] : null;
                    $thumbnailUrl = $first ? asset('storage/' . $first) : null;
                }
            }
            return (object)[
                'id' => $prophetie->id,
                'nom' => $prophetie->nom,
                'description' => $prophetie->description,
                'created_at' => $prophetie->created_at,
                'media_type' => $isAudio ? 'audio' : ($isVideoLink ? 'video_link' : ($isVideoFile ? 'video_file' : ($isPdf ? 'pdf' : ($isImages ? 'images' : null)))),
                'thumbnail_url' => $thumbnailUrl,
                'video_url' => $isVideoFile ? asset('storage/' . $prophetie->media->url_fichier) : $thumbnailUrl,
                'media_url' => $prophetie->media && !$isImages ? asset('storage/' . $prophetie->media->url_fichier) : null,
                'has_thumbnail' => $prophetie->media && $prophetie->media->thumbnail ? true : ($isImages && !empty(json_decode($prophetie->media->url_fichier ?? '[]', true))),
                'is_published' => $prophetie->media->is_published ?? true,
                'images' => $isImages ? array_map(function ($p) {
                    return asset('storage/' . $p);
                }, (array)(json_decode($prophetie->media->url_fichier ?? '[]', true) ?: [])) : [],
            ];
        });
        // Envoyer chaque prophétie comme variable séparée
        return view('admin.medias.propheties.index', [
            'propheties' => $propheties,
            'prophetiesData' => $prophetiesData,
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

        $data = Prophetie::create([
            'id_media' => $media->id,
            'nom' => $request->nom,
            'description' => $request->description,
            'insert_by' => auth()->id(),
            'update_by' => auth()->id(),
        ]);


        notify()->success('Succès', 'propheties ajoutée avec succès.');
        return redirect()->route('propheties.index');
    }

    public function update(Request $request, $id)
    {
        $data = Prophetie::findOrFail($id);
        $media = $data->media;

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


        $data->update([
            'nom' => $request->nom,
            'description' => $request->description,
            'update_by' => auth()->id(),
        ]);

        notify()->success('Succès', 'propheties mise à jour avec succès.');
        return redirect()->route('propheties.index');
    }


    public function edit($id)
    {
        $prophetie = Prophetie::with('media')->findOrFail($id);
        return response()->json([
            'nom' => $prophetie->nom,
            'description' => $prophetie->description,
            'media' => $prophetie->media
        ]);
    }





    public function destroy($id)
    {
        $prophetie = Prophetie::findOrFail($id);
        try {
            DB::beginTransaction();

            $prophetie->update([
                'is_deleted' => true,
                'update_by' => auth()->id(),
            ]);

            // Marquer également le média comme supprimé
            if ($prophetie->media) {
                $prophetie->media->update([
                    'is_deleted' => true,
                    'update_by' => auth()->id(),
                ]);
            }

            DB::commit();
            notify()->success('Succès', 'Prophétie supprimée avec succès.');
            return redirect()->route('propheties.index');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }

    public function publish(Request $request, $id)
    {
        $prophetie = Prophetie::findOrFail($id);

        // Vérifier que c'est une vidéo
        if (!$prophetie->media || !in_array($prophetie->media->type, ['video', 'link'])) {
            Alert::error('Erreur', 'Seules les vidéos peuvent être publiées/dépubliées.');
            return redirect()->back();
        }

        try {
            $prophetie->media->update([
                'is_published' => true,
                'update_by' => auth()->id(),
            ]);

            notify()->success('Succès', 'Prophétie vidéo publiée avec succès.');
            if ($request->ajax()) {
                return response()->json(['success' => true]);
            }
            return redirect()->back();
        } catch (\Exception $e) {
            Log::error('Erreur lors de la publication: ' . $e->getMessage());
            Alert::error('Erreur', 'Impossible de publier la prophétie.');
            if ($request->ajax()) {
                return response()->json(['success' => false], 500);
            }
            return redirect()->back();
        }
    }

    public function unpublish(Request $request, $id)
    {
        $prophetie = Prophetie::findOrFail($id);

        // Vérifier que c'est une vidéo
        if (!$prophetie->media || !in_array($prophetie->media->type, ['video', 'link'])) {
            Alert::error('Erreur', 'Seules les vidéos peuvent être publiées/dépubliées.');
            return redirect()->back();
        }

        try {
            $prophetie->media->update([
                'is_published' => false,
                'update_by' => auth()->id(),
            ]);

            notify()->success('Succès', 'Prophétie vidéo dépubliée avec succès.');
            if ($request->ajax()) {
                return response()->json(['success' => true]);
            }
            return redirect()->back();
        } catch (\Exception $e) {
            Log::error('Erreur lors de la dépublication: ' . $e->getMessage());
            Alert::error('Erreur', 'Impossible de dépublier la prophétie.');
            if ($request->ajax()) {
                return response()->json(['success' => false], 500);
            }
            return redirect()->back();
        }
    }

    public function voirProphetie($id)
    {
        try {
            $prophetie = Prophetie::with('media')->findOrFail($id);
            $media = $prophetie->media;

            if ($media->status !== 'ready') {
                return response()->json([
                    'status' => 'processing',
                    'message' => 'Le média est en cours de traitement. Veuillez réessayer plus tard.'
                ], 200);
            }

            $url = $media->url_fichier;

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

            if ($media->type === 'images') {
                $images = json_decode($media->url_fichier, true) ?? [];
                $imageUrls = array_map(fn($path) => asset('storage/' . $path), $images);
            }

            return response()->json([
                'status' => 'ready',
                'prophetie' => [
                    'id' => $prophetie->id,
                    'titre' => $prophetie->titre,
                    'description' => $prophetie->description,
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
                'message' => 'Erreur lors du chargement de la prophétie : ' . $e->getMessage(),
            ], 500);
        }
    }
}
