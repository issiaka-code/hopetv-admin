<?php

namespace App\Http\Controllers;

use App\Models\Etablissement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;

class EtablissementController extends Controller
{
    public function index(Request $request)
    {
        $query = Etablissement::query()->where('is_deleted', false)->latest();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('nom', 'like', "%$s%")
                  ->orWhere('telephone', 'like', "%$s%")
                  ->orWhere('email', 'like', "%$s%")
                  ->orWhere('adresse', 'like', "%$s%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type); // siege | annexe
        }

        if ($request->filled('status')) {
            $query->where('is_active', (bool) $request->status);
        }

        $etablissements = $query->paginate(12);

        return view('admin.medias.etablissements.index', [
            'etablissements' => $etablissements,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:siege,annexe',
            'nom' => 'required|string|max:255',
            'telephone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'adresse' => 'nullable|string|max:500',
            'maps_link' => 'nullable|url|max:500',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            DB::beginTransaction();

            $imagePath = null;
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $uniqueName = $filename . '_' . now()->format('Ymd_His') . '.' . $file->getClientOriginalExtension();
                $imagePath = $file->storeAs('etablissements/images', $uniqueName, 'public');
            }

            Etablissement::create([
                'type' => $request->type,
                'nom' => $request->nom,
                'telephone' => $request->telephone,
                'email' => $request->email,
                'adresse' => $request->adresse,
                'maps_link' => $request->maps_link,
                'image_path' => $imagePath,
                'is_active' => $request->boolean('is_active', true),
                'insert_by' => auth()->id(),
                'update_by' => auth()->id(),
            ]);

            DB::commit();
            notify()->success('Succès', 'Établissement ajouté avec succès.');
            return redirect()->route('etablissements.index');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur création établissement: ' . $e->getMessage());
            Alert::error('Erreur', "Impossible d'ajouter l'établissement: " . $e->getMessage());
            return back()->withInput();
        }
    }

    public function edit(Etablissement $etablissement)
    {
        return response()->json($etablissement);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'type' => 'required|in:siege,annexe',
            'nom' => 'required|string|max:255',
            'telephone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'adresse' => 'nullable|string|max:500',
            'maps_link' => 'nullable|url|max:500',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            DB::beginTransaction();

            $etab = Etablissement::findOrFail($id);
            $imagePath = $etab->image_path;

            if ($request->hasFile('image')) {
                if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                    Storage::disk('public')->delete($imagePath);
                }
                $file = $request->file('image');
                $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $uniqueName = $filename . '_' . now()->format('Ymd_His') . '.' . $file->getClientOriginalExtension();
                $imagePath = $file->storeAs('etablissements/images', $uniqueName, 'public');
            }

            $etab->update([
                'type' => $request->type,
                'nom' => $request->nom,
                'telephone' => $request->telephone,
                'email' => $request->email,
                'adresse' => $request->adresse,
                'maps_link' => $request->maps_link,
                'image_path' => $imagePath,
                'is_active' => $request->boolean('is_active', $etab->is_active),
                'update_by' => auth()->id(),
            ]);

            DB::commit();
            notify()->success('Succès', 'Établissement mis à jour avec succès.');
            return redirect()->route('etablissements.index');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur mise à jour établissement: ' . $e->getMessage());
            Alert::error('Erreur', "Impossible de mettre à jour l'établissement: " . $e->getMessage());
            return back()->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $etab = Etablissement::findOrFail($id);
            $etab->update([
                'is_deleted' => true,
                'update_by' => auth()->id(),
            ]);
            DB::commit();
            notify()->success('Succès', 'Établissement supprimé avec succès.');
            return redirect()->route('etablissements.index');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur suppression établissement: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    public function toggleStatus(Request $request, $id)
    {
        try {
            $etab = Etablissement::findOrFail($id);
            $etab->update([
                'is_active' => $request->boolean('is_active', !$etab->is_active),
                'update_by' => auth()->id(),
            ]);
            notify()->success('Succès', 'Statut mis à jour.');
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false], 500);
        }
    }
}
