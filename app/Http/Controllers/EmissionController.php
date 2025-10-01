<?php

namespace App\Http\Controllers;

use App\Models\Emission;
use App\Models\EmissionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use RealRashid\SweetAlert\Facades\Alert;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

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
        $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        try {
            if (! auth()->check()) {
                return back()->withErrors(['auth' => "Vous devez être connecté pour créer une émission."])->withInput();
            }

            $emission = Emission::create([
                'nom' => $request->nom,
                'description' => $request->description,
                'insert_by' => auth()->id(),
                'update_by' => auth()->id(),
            ]);

            notify()->success('Succès', 'Émission "' . $emission->nom . '" créée avec succès.');
            return redirect()->route('emissions.index');
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de l\'émission: ' . $e->getMessage());
            Alert::error('Erreur', 'Impossible de créer l\'émission. ' . $e->getMessage());
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
        return view('admin.medias.emissions.show', compact('emission'));
    }

    /**
     * Éditer une émission
     */
    public function edit($id)
    {
        $emission = Emission::findOrFail($id);

        return response()->json([
            'nom' => $emission->nom,
            'description' => $emission->description,
        ]);
    }

    /**
     * Mettre à jour une émission
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        try {
            $emission = Emission::findOrFail($id);

            $emission->update([
                'nom' => $request->nom,
                'description' => $request->description,
                'update_by' => auth()->id(),
            ]);

            notify()->success('Succès', 'Émission "' . $emission->nom . '" mise à jour avec succès.');
            return redirect()->route('emissions.index');
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour de l\'émission: ' . $e->getMessage());
            Alert::error('Erreur', 'Impossible de mettre à jour l\'émission. Veuillez vérifier les logs.');
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
