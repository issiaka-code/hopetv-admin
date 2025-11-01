<?php

namespace App\Http\Controllers;

use App\Models\InfoBulle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RealRashid\SweetAlert\Facades\Alert;
use App\Events\InfoBulleChanged;
use Illuminate\Support\Facades\Broadcast;

class InfoBulleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = InfoBulle::where('is_deleted', false);
        
        // Recherche
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('titre', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('texte', 'LIKE', "%{$searchTerm}%");
            });
        }
        
        // Filtre par statut
        if ($request->has('statut') && !empty($request->statut)) {
            if ($request->statut === 'actif') {
                $query->where('is_active', true);
            } elseif ($request->statut === 'inactif') {
                $query->where('is_active', false);
            }
        }
        
        $infoBulles = $query->orderBy('created_at', 'desc')->paginate(12);
        
        return view('admin.info_bulles.index', compact('infoBulles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'texte' => 'required|string',
            'is_active' => 'boolean:1,0'
        ]);

        try {
             $infoBulle=InfoBulle::create([
                'titre' => $validated['titre'],
                'texte' => $validated['texte'],
                'is_active' => $validated['is_active'] ?? true,
                'insert_by' => Auth::id(),
                'update_by' => Auth::id(),
            ]);
            broadcast(new InfoBulleChanged('create', $infoBulle->id))->toOthers();
            notify()->success('Succès', 'Info-bulle ajoutée avec succès.');
            return redirect()->route('info-bulles.index');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erreur lors de l\'ajout de l\'info-bulle: ' . $e->getMessage());
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $infoBulle = InfoBulle::where('id', $id)->where('is_deleted', false)->firstOrFail();
        
        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'texte' => 'required|string',
            'is_active' => 'boolean'
        ]);

        try {
            $infoBulle->update([
                'titre' => $validated['titre'],
                'texte' => $validated['texte'],
                'is_active' => $validated['is_active'] ?? $infoBulle->is_active,
                'update_by' => Auth::id(),
            ]);

            broadcast(new InfoBulleChanged('update', $infoBulle->id))->toOthers();
            notify()->success('Succès', 'Info-bulle modifiée avec succès.');
            
            return redirect()->route('info-bulles.index');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erreur lors de la modification de l\'info-bulle: ' . $e->getMessage());
        }
    }

    /**
     * Toggle the status of the specified resource.
     */
    public function toggleStatus(string $id)
    {
        $infoBulle = InfoBulle::where('id', $id)->where('is_deleted', false)->firstOrFail();
        
        try {
            $infoBulle->update([
                'is_active' => !$infoBulle->is_active,
                'update_by' => Auth::id(),
            ]);

            broadcast(new InfoBulleChanged('toggle', $infoBulle->id))->toOthers();

            notify()->success('Succès', 'Statut de l\'info-bulle modifié avec succès.');

            return redirect()->route('info-bulles.index');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erreur lors de la modification du statut: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $infoBulle = InfoBulle::where('id', $id)->where('is_deleted', false)->firstOrFail();
        
        try {
            // Soft delete
            $infoBulle->update([
                'is_deleted' => true,
                'update_by' => Auth::id(),
            ]);
            broadcast(new InfoBulleChanged('delete', $infoBulle->id))->toOthers();
            notify()->success('Succès', 'Info-bulle supprimée avec succès.');

            return redirect()->route('info-bulles.index');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erreur lors de la suppression de l\'info-bulle: ' . $e->getMessage());
        }
    }
}