<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessVideoJob;
use App\Models\Media;
use App\Models\Enseignement;
use App\Services\MediaService;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;


class EnseignementController extends Controller
{

      protected $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }
    public function index(Request $request)
    {
        $query = Enseignement::with('media')->where('is_deleted', false)->latest();

        if ($request->filled('search')) {
            $query->where('nom', 'like', "%{$request->search}%")
                ->orWhere('description', 'like', "%{$request->search}%");
        }

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

        $enseignements = $query->paginate(12);

        $enseignementsData = collect($enseignements->items())->map(function ($enseignement) {
            $isAudio = $enseignement->media && $enseignement->media->type === 'audio';
            $isVideoLink = $enseignement->media && $enseignement->media->type === 'link';
            $isVideoFile = $enseignement->media && $enseignement->media->type === 'video';
            $isPdf = $enseignement->media && $enseignement->media->type === 'pdf';
            $isImages = $enseignement->media && $enseignement->media->type === 'images';

            
            return (object) [
                'id' => $enseignement->id,
                'nom' => $enseignement->nom,
                'description' => $enseignement->description,
                'created_at' => $enseignement->created_at,
                'media_type' => $isAudio ? 'audio' : ($isVideoLink ? 'video_link' : ($isVideoFile ? 'video_file' : ($isPdf ? 'pdf' : ($isImages ? 'images' : null)))),
                'thumbnail_url' => asset('storage/' . $enseignement->media->thumbnail),
               // 'video_url' => $isVideoFile ? asset('storage/' . $enseignement->media->url_fichier) : $thumbnailUrl,
                'media_url' => $enseignement->media && !$isImages ? asset('storage/' . $enseignement->media->url_fichier) : null,
                'has_thumbnail' => $enseignement->media && $enseignement->media->thumbnail ? true : ($isImages && !empty(json_decode($enseignement->media->url_fichier ?? '[]', true))),
                'is_published' => $enseignement->media->is_published ?? true,
                'images' => $isImages ? array_map(function ($p) {
                    return asset('storage/' . $p);
                }, (array) (json_decode($enseignement->media->url_fichier ?? '[]', true) ?: [])) : [],
            ];
        });

        return view('admin.medias.enseignements.index', [
            'enseignements' => $enseignements,
            'enseignementsData' => $enseignementsData,
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

        $enseignement = Enseignement::create([
            'id_media' => $media->id,
            'nom' => $request->nom,
            'description' => $request->description,
            'insert_by' => auth()->id(),
            'update_by' => auth()->id(),
        ]);


        notify()->success('Succès', 'Enseignement ajouté avec succès.');
        return redirect()->route('enseignements.index');
    }

    public function update(Request $request, $id)
    {

        $data = Enseignement::findOrFail($id);
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

        notify()->success('Succès', 'Enseignement mis à jour avec succès.');
        return redirect()->route('enseignements.index');
    }


    public function edit(Enseignement $enseignement)
    {
        $enseignement->load('media');
        return response()->json([
            'nom' => $enseignement->nom,
            'description' => $enseignement->description,
            'media' => $enseignement->media,
        ]);
    }



    public function destroy($id)
    {
        $enseignement = Enseignement::findOrFail($id);
        try {
            DB::beginTransaction();
            $enseignement->update([
                'is_deleted' => true,
                'update_by' => auth()->id(),
            ]);
            if ($enseignement->media) {
                $enseignement->media->update([
                    'is_deleted' => true,
                    'update_by' => auth()->id(),
                ]);
            }
            DB::commit();
            notify()->success('Succès', 'Enseignement supprimé avec succès.');
            return redirect()->route('enseignements.index');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }

    public function publish(Request $request, $id)
    {
        $enseignement = Enseignement::findOrFail($id);
        if (!$enseignement->media || !in_array($enseignement->media->type, ['video', 'link'])) {
            Alert::error('Erreur', 'Seules les vidéos peuvent être publiées/dépubliées.');
            return redirect()->back();
        }
        try {
            $enseignement->media->update([
                'is_published' => true,
                'update_by' => auth()->id(),
            ]);
            notify()->success('Succès', 'Vidéo d\'enseignement publiée avec succès.');
            if ($request->ajax()) {
                return response()->json(['success' => true]);
            }
            return redirect()->back();
        } catch (\Exception $e) {
            Log::error('Erreur publication enseignement: ' . $e->getMessage());
            Alert::error('Erreur', 'Impossible de publier.');
            if ($request->ajax()) {
                return response()->json(['success' => false], 500);
            }
            return redirect()->back();
        }
    }

    public function unpublish(Request $request, $id)
    {
        $enseignement = Enseignement::findOrFail($id);
        if (!$enseignement->media || !in_array($enseignement->media->type, ['video', 'link'])) {
            Alert::error('Erreur', 'Seules les vidéos peuvent être publiées/dépubliées.');
            return redirect()->back();
        }
        try {
            $enseignement->media->update([
                'is_published' => false,
                'update_by' => auth()->id(),
            ]);
            notify()->success('Succès', 'Vidéo d\'enseignement dépubliée avec succès.');
            if ($request->ajax()) {
                return response()->json(['success' => true]);
            }
            return redirect()->back();
        } catch (\Exception $e) {
            Log::error('Erreur dépublication enseignement: ' . $e->getMessage());
            Alert::error('Erreur', 'Impossible de dépublier.');
            if ($request->ajax()) {
                return response()->json(['success' => false], 500);
            }
            return redirect()->back();
        }
    }

    public function voirEnseignement($id)
    {
        try {
            $enseignement = Enseignement::with('media')->findOrFail($id);
            $media = $enseignement->media;

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
                    'id' => $enseignement->id,
                    'titre' => $enseignement->titre,
                    'description' => $enseignement->description,
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
                'message' => 'Erreur lors du chargement de l’enseignement : ' . $e->getMessage(),
            ], 500);
        }
    }
}
