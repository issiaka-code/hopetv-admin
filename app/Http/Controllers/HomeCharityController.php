<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessVideoJob;
use App\Models\Media;
use App\Models\HomeCharity;
use App\Services\MediaService;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

class HomeCharityController extends Controller
{

     protected $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }


    public function index(Request $request)
    {
        $query = HomeCharity::with('media')->where('is_deleted', false)->latest();

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

        $homeCharities = $query->paginate(12);

        // Préparer chaque variable pour la vue et JS
           $homeCharitiesData = collect($homeCharities->items())->map(function ($homeCharity) {
            $isAudio = $homeCharity->media && $homeCharity->media->type === 'audio';
            $isVideoLink = $homeCharity->media && $homeCharity->media->type === 'link';
            $isVideoFile = $homeCharity->media && $homeCharity->media->type === 'video';
            $isPdf = $homeCharity->media && $homeCharity->media->type === 'pdf';
            $isImages = $homeCharity->media && $homeCharity->media->type === 'images';
         

            return (object)[
                'id' => $homeCharity->id,
                'nom' => $homeCharity->nom,
                'description' => $homeCharity->description,
                'created_at' => $homeCharity->created_at,
                'media_type' => $isAudio ? 'audio' : ($isVideoLink ? 'video_link' : ($isVideoFile ? 'video_file' : ($isPdf ? 'pdf' : ($isImages ? 'images' : null)))),
                'thumbnail_url' => asset('storage/' . $homeCharity->media->thumbnail),
                'media_url' => $homeCharity->media && !$isImages ? asset('storage/' . $homeCharity->media->url_fichier) : null,
                'has_thumbnail' => $homeCharity->media && $homeCharity->media->thumbnail ? true : ($isImages && !empty(json_decode($homeCharity->media->url_fichier ?? '[]', true))),
                'is_published' => $homeCharity->media->is_published ?? true,
                'images' => $isImages ? array_map(function ($p) {
                    return asset('storage/' . $p);
                }, (array)(json_decode($homeCharity->media->url_fichier ?? '[]', true) ?: [])) : [],
            ];
        });
        // Envoyer chaque charité comme variable séparée
        return view('admin.medias.home-charities.index', [
            'homeCharities' => $homeCharities,
            'homeCharitiesData' => $homeCharitiesData,
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


        // === CRÉATION DU HOMECHARITY ===
        $home = HomeCharity::create([
            'id_media' => $media->id,
            'nom' => $request->nom,
            'description' => $request->description,
            'insert_by' => auth()->id(),
            'update_by' => auth()->id(),
        ]);



        notify()->success('Succès', 'Home Charity ajouté avec succès.');
        return redirect()->route('home-charities.index');
    }

    public function update(Request $request, $id)
    {
        $data = HomeCharity::findOrFail($id);
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

        notify()->success('Succès', 'hope-charities mise à jour avec succès.');
        return redirect()->route('home-charities.index');
    }



    public function edit(HomeCharity $homeCharity)
    {
        $homeCharity->load('media');
        return response()->json([
            'nom' => $homeCharity->nom,
            'description' => $homeCharity->description,
            'media' => $homeCharity->media
        ]);
    }



    public function destroy($id)
    {
        $homeCharity = HomeCharity::findOrFail($id);
        try {
            DB::beginTransaction();

            $homeCharity->update([
                'is_deleted' => true,
                'update_by' => auth()->id(),
            ]);

            // Marquer également le média comme supprimé
            if ($homeCharity->media) {
                $homeCharity->media->update([
                    'is_deleted' => true,
                    'update_by' => auth()->id(),
                ]);
            }

            DB::commit();
            notify()->success('Succès', 'Charité supprimée avec succès.');
            return redirect()->route('home-charities.index');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }

    public function publish(Request $request, $id)
    {
        $homeCharity = HomeCharity::findOrFail($id);

        // Vérifier que c'est une vidéo
        if (!$homeCharity->media || !in_array($homeCharity->media->type, ['video', 'link'])) {
            Alert::error('Erreur', 'Seules les vidéos peuvent être publiées/dépubliées.');
            return redirect()->back();
        }

        try {
            $homeCharity->media->update([
                'is_published' => true,
                'update_by' => auth()->id(),
            ]);

            notify()->success('Succès', 'Charité vidéo publiée avec succès.');
            if ($request->ajax()) {
                return response()->json(['success' => true]);
            }
            return redirect()->back();
        } catch (\Exception $e) {
            Log::error('Erreur lors de la publication: ' . $e->getMessage());
            Alert::error('Erreur', 'Impossible de publier la charité.');
            if ($request->ajax()) {
                return response()->json(['success' => false], 500);
            }
            return redirect()->back();
        }
    }

    public function unpublish(Request $request, $id)
    {
        $homeCharity = HomeCharity::findOrFail($id);

        // Vérifier que c'est une vidéo
        if (!$homeCharity->media || !in_array($homeCharity->media->type, ['video', 'link'])) {
            Alert::error('Erreur', 'Seules les vidéos peuvent être publiées/dépubliées.');
            return redirect()->back();
        }

        try {
            $homeCharity->media->update([
                'is_published' => false,
                'update_by' => auth()->id(),
            ]);

            notify()->success('Succès', 'Charité vidéo dépubliée avec succès.');
            if ($request->ajax()) {
                return response()->json(['success' => true]);
            }
            return redirect()->back();
        } catch (\Exception $e) {
            Log::error('Erreur lors de la dépublication: ' . $e->getMessage());
            Alert::error('Erreur', 'Impossible de dépublier la charité.');
            if ($request->ajax()) {
                return response()->json(['success' => false], 500);
            }
            return redirect()->back();
        }
    }

    public function voirHomeCharity($id)
    {
        try {
            $homeCharity = HomeCharity::with('media')->findOrFail($id);
            $media = $homeCharity->media;

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
                'data' => [
                    'id' => $homeCharity->id,
                    'titre' => $homeCharity->titre,
                    'description' => $homeCharity->description,
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
                'message' => 'Erreur lors du chargement de Home Charity : ' . $e->getMessage(),
            ], 500);
        }
    }
}
