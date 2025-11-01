<?php

namespace App\Http\Controllers;


use App\Models\Emission;
use App\Models\EmissionItem;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

class EmissionItemController extends Controller
{
    protected $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    public function index($id)
    {
        $emissionItems = EmissionItem::with('media','emission')
            ->where('is_deleted', false)
            ->latest()
            ->paginate(10); // 10 éléments par page

        return view('admin.medias.emissions.show', compact('emissionItems'));
    }

    public function store(Request $request)
    {
        $emission = Emission::where('is_deleted', false)
            ->findOrFail($request->inputemissionid);

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

        $created = EmissionItem::create([
            'id_Emission' => $emission->id,
            'nom' => $request->nom,
            'description' => $request->description,
            'id_media' => $media->id,
            'insert_by' => Auth::id(),
            'update_by' => Auth::id(),
            'is_active' => true,
        ]);

        notify()->success('Succès', 'Vidéo ajoutée avec succès à l\'émission "' . $emission->nom . '".');
        return redirect()->route('emissions.show', $emission->id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Emission $emission, EmissionItem $item)
    {
        return response()->json($item);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request,EmissionItem $item)
    {
         $emission = Emission::where('is_deleted', false)
            ->findOrFail($request->inputemissionid);
            
        $media = $item->media;
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

        $item->update([
            'nom' => $request->nom,
            'description' => $request->description,
            'id_media' => $media->id,
            'update_by' => Auth::id(),
        ]);

        notify()->success('Succès', 'Vidéo mise à jour avec succès.');
        return redirect()->route('emissions.show', $emission->id);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Emission $emission, EmissionItem $item)
    {
        try {
            DB::beginTransaction();

            $item->update([
                'is_deleted' => true,
                'update_by' => auth()->id(),
            ]);

            DB::commit();
            notify()->success('Succès', 'Vidéo supprimée avec succès.');
            return redirect()->route('emissions.show', $emission->id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la suppression de la vidéo: ' . $e->getMessage());
            Alert::error('Erreur', 'Impossible de supprimer la vidéo.');
            return back();
        }
    }

    /**
     * Toggle the status of an emission item.
     */
    public function toggleStatus(Request $request, Emission $emission, EmissionItem $item)
    {
        try {
            $item->update([
                'is_active' => !$item->is_active,
                'update_by' => auth()->id(),
            ]);

            $status = $item->is_active ? 'activée' : 'désactivée';
            notify()->success('Succès', "Vidéo {$status} avec succès.");

            if ($request->ajax()) {
                return response()->json(['success' => true, 'new_status' => $item->is_active]);
            }
            return redirect()->route('emissions.show', $emission->id);
        } catch (\Exception $e) {
            Log::error('Erreur lors du changement de statut de la vidéo: ' . $e->getMessage());
            Alert::error('Erreur', 'Impossible de changer le statut.');
            if ($request->ajax()) {
                return response()->json(['success' => false], 500);
            }
            return back();
        }
    }

    public function voir($id)
{
    try {
        // Charger l'item avec son média et l'émission associée
        $item = EmissionItem::with(['media', 'emission'])->findOrFail($id);
        $media = $item->media;

        // Vérifie si le média est prêt (si tu gères un statut de traitement)
        if (isset($media->status) && $media->status !== 'ready') {
            return response()->json([
                'status' => 'processing',
                'message' => 'Le média est en cours de traitement. Veuillez réessayer plus tard.'
            ], 200);
        }

        $url = $media->url_fichier;

        // 🎥 Si le média est un lien (YouTube, Vimeo, etc.)
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

        // 🖼️ Si le média contient plusieurs images
        if ($media->type === 'images') {
            $images = json_decode($media->url_fichier, true) ?? [];
            $imageUrls = array_map(fn($path) => asset('storage/' . $path), $images);
        }

        // 📦 Construction de la réponse JSON
        return response()->json([
            'status' => 'ready',
            'data' => [
                'id' => $item->id,
                'nom' => $item->nom,
                'description' => $item->description,
                'emission' => [
                    'id' => $item->emission->id,
                    'titre' => $item->emission->nom ?? 'Sans titre',
                ],
                'media' => [
                    'url' => $media->type === 'images'
                        ? ($imageUrls ?? [])
                        : (in_array($media->type, ['audio', 'video', 'pdf'])
                            ? asset('storage/' . $url)
                            : $url),
                    'thumbnail' => $media->thumbnail ? asset('storage/' . $media->thumbnail) : null,
                    'type' => $media->type,
                ],
            ],
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Erreur lors du chargement de l’émission : ' . $e->getMessage(),
        ], 500);
    }
}

}
