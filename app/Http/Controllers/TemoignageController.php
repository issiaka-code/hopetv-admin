<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessVideoJob;
use App\Models\Media;
use App\Models\Temoignage;
use App\Services\MediaService;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use Illuminate\Support\Facades\Validator;

class TemoignageController extends Controller
{

    protected $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }


    public function index(Request $request)
    {
        $query = Temoignage::with('media')->where('is_deleted', false)->latest();

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

        $temoignages = $query->paginate(12);

        // Préparer chaque variable pour la vue et JS
        $temoignagesData = collect($temoignages->items())->map(function ($temoignage) {
            $isAudio = $temoignage->media && $temoignage->media->type === 'audio';
            $isVideoLink = $temoignage->media && $temoignage->media->type === 'link';
            $isVideoFile = $temoignage->media && $temoignage->media->type === 'video';
            $isPdf = $temoignage->media && $temoignage->media->type === 'pdf';
            $isImages = $temoignage->media && $temoignage->media->type === 'images';


            
            return (object)[
                'id' => $temoignage->id,
                'nom' => $temoignage->nom,
                'description' => $temoignage->description,
                'created_at' => $temoignage->created_at,
                'media_type' => $isAudio ? 'audio' : ($isVideoLink ? 'video_link' : ($isVideoFile ? 'video_file' : ($isPdf ? 'pdf' : ($isImages ? 'images' : null)))),
                'thumbnail_url' => asset('storage/' . $temoignage->media->thumbnail),
                //'video_url' => $isVideoFile ? asset('storage/' . $temoignage->media->url_fichier) : $thumbnailUrl,
                'media_url' => $temoignage->media && !$isImages ? asset('storage/' . $temoignage->media->url_fichier) : null,
                'has_thumbnail' => $temoignage->media && $temoignage->media->thumbnail ? true : ($isImages && !empty(json_decode($temoignage->media->url_fichier ?? '[]', true))),
                'is_published' => $temoignage->media->is_published ?? true,
                'images' => $isImages ? array_map(function ($p) {
                    return asset('storage/' . $p);
                }, (array)(json_decode($temoignage->media->url_fichier ?? '[]', true) ?: [])) : [],
            ];
        });
        // Envoyer chaque témoignage comme variable séparée
        return view('admin.medias.temoignages.index', [
            'temoignages' => $temoignages,
            'temoignagesData' => $temoignagesData,
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

        $temoignage = Temoignage::create([
            'id_media' => $media->id,
            'nom' => $request->nom,
            'description' => $request->description,
            'insert_by' => auth()->id(),
            'update_by' => auth()->id(),
        ]);

        notify()->success('Succès', 'Témoignage ajouté avec succès.');
        return redirect()->route('temoignages.index');
    }

    public function update(Request $request, $id)
    {

        $temoignage = Temoignage::findOrFail($id);
        $media = $temoignage->media;

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

        $temoignage->update([
            'nom' => $request->nom,
            'description' => $request->description,
            'update_by' => auth()->id(),
        ]);

        notify()->success('Succès', 'Témoignage mis à jour avec succès.');
        return redirect()->route('temoignages.index');
    }



    public function edit(Temoignage $temoignage)
    {
        $temoignage->load('media');
        return response()->json([
            'nom' => $temoignage->nom,
            'description' => $temoignage->description,
            'media' => $temoignage->media
        ]);
    }




    public function destroy($id)
    {
        $temoignage = Temoignage::findOrFail($id);
        try {
            DB::beginTransaction();

            $temoignage->update([
                'is_deleted' => true,
                'update_by' => auth()->id(),
            ]);

            // Marquer également le média comme supprimé
            if ($temoignage->media) {
                $temoignage->media->update([
                    'is_deleted' => true,
                    'update_by' => auth()->id(),
                ]);
            }

            DB::commit();
            notify()->success('Succès', 'Témoignage supprimé avec succès.');
            return redirect()->route('temoignages.index');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }

    public function publish(Request $request, $id)
    {
        $temoignage = Temoignage::findOrFail($id);

        // Vérifier que c'est une vidéo
        if (!$temoignage->media || !in_array($temoignage->media->type, ['video', 'link'])) {
            Alert::error('Erreur', 'Seules les vidéos peuvent être publiées/dépubliées.');
            return redirect()->back();
        }

        try {
            $temoignage->media->update([
                'is_published' => true,
                'update_by' => auth()->id(),
            ]);

            notify()->success('Succès', 'Témoignage vidéo publié avec succès.');
            if ($request->ajax()) {
                return response()->json(['success' => true]);
            }
            return redirect()->back();
        } catch (\Exception $e) {
            Log::error('Erreur lors de la publication: ' . $e->getMessage());
            Alert::error('Erreur', 'Impossible de publier le témoignage.');
            if ($request->ajax()) {
                return response()->json(['success' => false], 500);
            }
            return redirect()->back();
        }
    }

    public function unpublish(Request $request, $id)
    {
        $temoignage = Temoignage::findOrFail($id);

        // Vérifier que c'est une vidéo
        if (!$temoignage->media || !in_array($temoignage->media->type, ['video', 'link'])) {
            Alert::error('Erreur', 'Seules les vidéos peuvent être publiées/dépubliées.');
            return redirect()->back();
        }

        try {
            $temoignage->media->update([
                'is_published' => false,
                'update_by' => auth()->id(),
            ]);

            notify()->success('Succès', 'Témoignage vidéo dépublié avec succès.');
            if ($request->ajax()) {
                return response()->json(['success' => true]);
            }
            return redirect()->back();
        } catch (\Exception $e) {
            Log::error('Erreur lors de la dépublication: ' . $e->getMessage());
            Alert::error('Erreur', 'Impossible de dépublier le témoignage.');
            if ($request->ajax()) {
                return response()->json(['success' => false], 500);
            }
            return redirect()->back();
        }
    }


    public function voirTemoignage($id)
    {
        try {
            $temoignage = Temoignage::with('media')->findOrFail($id);
            $media = $temoignage->media;

            // Vérifier si le média est en cours de traitement
            if ($media->status !== 'ready') {
                return response()->json([
                    'status' => 'processing',
                    'message' => 'Le média est en cours de traitement. Veuillez réessayer plus tard.'
                ], 200);
            }

            $url = $media->url_fichier;

            // ✅ Traitement spécial pour les liens (YouTube, Vimeo, etc.)
            if ($media->type === 'link' && !empty($url)) {
                // YouTube - formats divers
                if (str_contains($url, 'youtube.com/watch?v=')) {
                    $url = str_replace('watch?v=', 'embed/', $url);
                } elseif (str_contains($url, 'youtu.be/')) {
                    $url = str_replace('youtu.be/', 'www.youtube.com/embed/', $url);
                }
                // Vimeo
                elseif (str_contains($url, 'vimeo.com/')) {
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
                'temoignage' => [
                    'id' => $temoignage->id,
                    'nom' => $temoignage->nom,
                    'description' => $temoignage->description,
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
                'message' => 'Erreur lors du chargement du témoignage : ' . $e->getMessage(),
            ], 500);
        }
    }
}
