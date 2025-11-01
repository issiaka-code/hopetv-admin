<?php

namespace App\Http\Controllers;

use App\Models\Emission;
use App\Models\EmissionItem;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\Validator;

class EmissionController extends Controller
{
    /**
     * Afficher la liste des émissions (utilise la table Emissions)
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $emissions = Emission::where('is_deleted', false)
            ->withCount(['items' => function ($query) {
                $query->where('is_deleted', false);
            }])
            ->when($search, function ($query, $search) {
                return $query->where('nom', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return view('admin.medias.emissions.index', compact('emissions', 'search'));
    }

    /**
     * Créer une nouvelle émission (juste nom + description)
     */
    public function store(Request $request)
    {
        Log::info('Début de la création d’une émission', ['user_id' => auth()->id()]);

        $validator = Validator::make(
            $request->all(),
            [
                'nom' => 'required|string|max:255',
                'description' => 'required|string',
                'image_couverture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ],
            [
                'nom.required' => 'Le nom est obligatoire.',
                'nom.string' => 'Le nom doit être une chaîne de caractères.',
                'nom.max' => 'Le nom ne doit pas dépasser 255 caractères.',

                'description.required' => 'La description est obligatoire.',
                'description.string' => 'La description doit être une chaîne de caractères.',

                'image_couverture.image' => "L'image de couverture doit être une image.",
                'image_couverture.mimes' => "L'image de couverture doit être au format : jpeg, png, jpg ou gif.",
                'image_couverture.max' => "L'image de couverture ne doit pas dépasser 2 Mo.",
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                notify()->error($error);
            }

            return [
                'success' => false,
                'errors' => $validator->errors(),
            ];
        }


        try {
            if (! auth()->check()) {
                Log::warning('Tentative de création d’émission sans authentification.');

                return back()->withErrors(['auth' => 'Vous devez être connecté pour créer une émission.'])->withInput();
            }

            // Traitement de l'image de couverture
            $thumbnailPath = null;
            if ($request->hasFile('image_couverture')) {
                Log::info('Fichier image de couverture détecté.');

                $thumbnailFile = $request->file('image_couverture');
                $thumbnailName = pathinfo($thumbnailFile->getClientOriginalName(), PATHINFO_FILENAME);
                $thumbnailUniqueName = $thumbnailName . '_thumb_' . now()->format('Ymd_His') . '.' . $thumbnailFile->getClientOriginalExtension();

                $thumbnailPath = $thumbnailFile->storeAs('thumbnails', $thumbnailUniqueName, 'public');

                Log::info('Image de couverture enregistrée.', [
                    'nom_original' => $thumbnailFile->getClientOriginalName(),
                    'nom_enregistre' => $thumbnailUniqueName,
                    'chemin' => $thumbnailPath,
                ]);
            } else {
                Log::info('Aucune image de couverture téléchargée.');
            }

            // Création du média
            Log::info('Création du média associé à l’émission...');
            $media = Media::create([
                'url_fichier' => null,
                'thumbnail' => $thumbnailPath ?? null,
                'type' => 'emission',
                'insert_by' => auth()->id(),
                'update_by' => auth()->id(),
            ]);
            Log::info('Média créé avec succès.', ['media_id' => $media->id]);

            // Création de l’émission
            Log::info('Création de l’émission...');
            $emission = Emission::create([
                'id_media' => $media->id,
                'nom' => $request->nom,
                'description' => $request->description,
                'insert_by' => auth()->id(),
                'update_by' => auth()->id(),
            ]);
            Log::info('Émission créée avec succès.', [
                'emission_id' => $emission->id,
                'nom' => $emission->nom,
            ]);

            notify()->success('Succès', 'Émission "' . $emission->nom . '" créée avec succès.');

            Log::info('Fin du processus de création d’émission avec succès.');

            return redirect()->route('emissions.index');
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de l’émission : ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            Alert::error('Erreur', 'Impossible de créer l’émission. ' . $e->getMessage());

            return back()->withInput();
        }
    }

    /**
     * Afficher les détails d'une émission avec ses vidéos
     */
    public function show($id)
    {
        $emission = Emission::where('is_deleted', false)
            ->findOrFail($id);
        $emission->load('media');

        return view('admin.medias.emissions.show', compact('emission'));
    }

    /**
     * Éditer une émission
     */
    public function edit($id)
    {
        $emission = Emission::findOrFail($id);
        $emission->load('media');

        return response()->json([
            'nom' => $emission->nom,
            'description' => $emission->description,
            'media' => $emission->media,
        ]);
    }

    /**
     * Mettre à jour une émission
     */
    public function update(Request $request, Emission $emission)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'required|string',
            'image_couverture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            if (! auth()->check()) {
                return back()->withErrors(['auth' => 'Vous devez être connecté pour modifier une émission.'])->withInput();
            }

            // Récupérer le média associé
            $media = Media::find($emission->id_media);

            if (! $media) {
                return back()->withErrors(['media' => 'Média associé non trouvé.'])->withInput();
            }

            // Traitement de la nouvelle image de couverture
            $thumbnailPath = $media->thumbnail; // Conserver l'ancienne image par défaut

            if ($request->hasFile('image_couverture')) {
                // Supprimer l'ancienne image si elle existe
                if ($media->thumbnail && Storage::disk('public')->exists($media->thumbnail)) {
                    Storage::disk('public')->delete($media->thumbnail);
                }

                $thumbnailFile = $request->file('image_couverture');
                $thumbnailName = pathinfo($thumbnailFile->getClientOriginalName(), PATHINFO_FILENAME);
                $thumbnailUniqueName = $thumbnailName . '_thumb_' . now()->format('Ymd_His') . '.' . $thumbnailFile->getClientOriginalExtension();
                $thumbnailPath = $thumbnailFile->storeAs('thumbnails', $thumbnailUniqueName, 'public');
            }

            // Mettre à jour l'enregistrement média
            $media->update([
                'thumbnail' => $thumbnailPath,
                'update_by' => auth()->id(),
                'updated_at' => now(),
            ]);

            // Mettre à jour l'émission
            $emission->update([
                'nom' => $request->nom,
                'description' => $request->description,
                'update_by' => auth()->id(),
                'updated_at' => now(),
            ]);

            notify()->success('Succès', 'Émission "' . $emission->nom . '" modifiée avec succès.');

            return redirect()->route('emissions.index');
        } catch (\Exception $e) {
            Log::error('Erreur lors de la modification de l\'émission: ' . $e->getMessage());
            Alert::error('Erreur', 'Impossible de modifier l\'émission. ' . $e->getMessage());

            return back()->withInput();
        }
    }

    /**
     * Supprimer une émission (soft delete)
     */
    public function destroy($id)
    {
        $emission = Emission::findOrFail($id);

        try {
            DB::beginTransaction();

            // Marquer l'émission comme supprimée
            $emission->update([
                'is_deleted' => true,
                'update_by' => auth()->id(),
            ]);

            // Marquer tous les items de cette émission comme supprimés
            EmissionItem::where('id_Emission', $emission->id)->update([
                'is_deleted' => true,
                'update_by' => auth()->id(),
            ]);

            DB::commit();
            notify()->success('Succès', 'Émission "' . $emission->nom . '" supprimée avec succès.');

            return redirect()->route('emissions.index');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }
}
