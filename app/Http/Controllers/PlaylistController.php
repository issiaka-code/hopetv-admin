<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Video;
use App\Models\Playlist;
use App\Models\PlaylistItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

class PlaylistController extends Controller
{
    /**
     * Afficher la liste des playlists
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $playlists = Playlist::notDeleted()
            ->with(['user', 'items' => function ($query) {
                $query->notDeleted()->with('video')->orderBy('created_at', 'asc');
            }])
            ->when($search, function ($query, $search) {
                return $query->where('nom', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('admin.playlists.index', compact('playlists', 'search'));
    }

    /**
     * Afficher le formulaire de création avec wizard
     */

    public function create()
    {
        $videos = Video::where('is_deleted', false)->whereHas(
            'media',
            function ($query) {
                $query->where('type', 'video');
            }
        )->orderBy('nom', 'asc')->get();

        // Exemple : calculer la durée pour chaque vidéo si c'est un fichier local
        foreach ($videos as $video) {
            if ($video->media && $video->media->type === 'video') {
                try {
                    $seconds = FFMpeg::fromDisk('public')
                        ->open($video->media->url_fichier)
                        ->getDurationInSeconds();

                    // Conversion en H:i:s
                    $hours = floor($seconds / 3600);
                    $minutes = floor(($seconds % 3600) / 60);
                    $secs = $seconds % 60;

                    // Réécriture de la variable dure
                    $video->duree = sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
                } catch (\Exception $e) {
                    $video->duree = null; // erreur de lecture
                }
            } else {
                $video->duree = null; // liens externes
            }
        }

        return view('admin.playlists.create', compact('videos'));
    }


    /**
     * Stocker une nouvelle playlist
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'date_debut' => 'required|date|after_or_equal:now',
            'etat' => 'required|boolean',
            'selected_videos' => 'required|json'
        ], [
            'nom.required' => 'Le nom de la playlist est obligatoire.',
            'nom.max' => 'Le nom ne peut pas dépasser 255 caractères.',
            'date_debut.required' => 'La date de début est obligatoire.',
            'date_debut.after_or_equal' => 'La date de début ne peut pas être dans le passé.',
            'selected_videos.required' => 'Veuillez sélectionner au moins une vidéo.',
            'selected_videos.json' => 'Format des vidéos sélectionnées invalide.'
        ]);

        if ($validator->fails()) {
            notify()->error('Erreur', 'Veuillez corriger les erreurs.');
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Décoder les vidéos sélectionnées
            $selectedVideos = json_decode($request->selected_videos, true);

            if (empty($selectedVideos)) {
                notify()->error('Erreur', 'Veuillez sélectionner au moins une vidéo.');
                return redirect()->back();
            }

            DB::beginTransaction();

            // Créer la playlist
            $playlist = Playlist::create([
                'nom' => $request->nom,
                'description' => $request->description,
                'date_debut' => $request->date_debut,
                'etat' => $request->etat,
                'insert_by' => Auth::id(),
                'update_by' => Auth::id(),
            ]);

            // Ajouter les vidéos à la playlist dans l'ordre spécifié
            foreach ($selectedVideos as $index => $videoData) {

                PlaylistItem::create([
                    'id_playlist' => $playlist->id,
                    'id_video' => $videoData['id'],
                    'duree_video' => $videoData['duration'],
                    'position' => $videoData['order'],
                    'insert_by' => Auth::id(),
                    'update_by' => Auth::id(),
                ]);
            }

            DB::commit();

            notify()->success('Succès', 'Playlist "' . $playlist->nom . '" créée avec succès avec ' . count($selectedVideos) . ' vidéo(s).');
            return redirect()->route('playlists.index');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la création de la playlist. Veuillez réessayer.')
                ->withInput();
        }
    }

    /**
     * Afficher une playlist
     */
    public function show($id)
    {
        $playlist = Playlist::notDeleted()
            ->with([
                'items' => function ($query) {
                    $query->notDeleted()
                        ->with('video', 'video.media')
                        ->orderBy('created_at', 'asc');
                }
            ])
            ->findOrFail($id);

        // Calculer la durée totale de la playlist
        $totalDuration = $this->calculateTotalDuration($playlist->items);

        return view('admin.playlists.show', compact('playlist', 'totalDuration'));
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit($id)
    {
        $playlist = Playlist::notDeleted()
            ->with(['items' => function ($query) {
                $query->notDeleted()->with('video')->orderBy('created_at', 'asc');
            }])
            ->findOrFail($id);

        $videos = Video::where('is_deleted', false)->whereHas(
            'media',
            function ($query) {
                $query->where('type', 'video');
            }
        )->orderBy('nom', 'asc')->get();
        // Exemple : calculer la durée pour chaque vidéo si c'est un fichier local
        foreach ($videos as $video) {
            if ($video->media && $video->media->type === 'video') {
                try {
                    $seconds = FFMpeg::fromDisk('public')
                        ->open($video->media->url_fichier)
                        ->getDurationInSeconds();

                    // Conversion en H:i:s
                    $hours = floor($seconds / 3600);
                    $minutes = floor(($seconds % 3600) / 60);
                    $secs = $seconds % 60;

                    // Réécriture de la variable dure
                    $video->duree = sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
                } catch (\Exception $e) {
                    $video->duree = null; // erreur de lecture
                }
            } else {
                $video->duree = null; // liens externes
            }
        }
        return view('admin.playlists.edit', compact('playlist', 'videos'));
    }

    /**
     * Mettre à jour une playlist
     */
    /**
     * Mettre à jour une playlist existante
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'date_debut' => 'required|date',
            'etat' => 'required|boolean',
            'selected_videos' => 'required|json'
        ], [
            'nom.required' => 'Le nom de la playlist est obligatoire.',
            'nom.max' => 'Le nom ne peut pas dépasser 255 caractères.',
            'date_debut.required' => 'La date de début est obligatoire.',
            'selected_videos.required' => 'Veuillez sélectionner au moins une vidéo.',
            'selected_videos.json' => 'Format des vidéos sélectionnées invalide.'
        ]);

        if ($validator->fails()) {
            notify()->error('Erreur', 'Veuillez corriger les erreurs.');
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Trouver la playlist
            $playlist = Playlist::findOrFail($id);

            // Décoder les vidéos sélectionnées
            $selectedVideos = json_decode($request->selected_videos, true);

            if (empty($selectedVideos)) {
                notify()->error('Erreur', 'Veuillez sélectionner au moins une vidéo.');
                return redirect()->back();
            }

            DB::beginTransaction();

            // Mettre à jour la playlist
            $playlist->update([
                'nom' => $request->nom,
                'description' => $request->description,
                'date_debut' => $request->date_debut,
                'etat' => $request->etat,
                'update_by' => Auth::id(),
                'update_date' => now(),
            ]);

            // Supprimer les anciens éléments de playlist
            PlaylistItem::where('id_playlist', $playlist->id)->delete();

            // Ajouter les nouvelles vidéos à la playlist dans l'ordre spécifié
            foreach ($selectedVideos as $videoData) {
                PlaylistItem::create([
                    'id_playlist' => $playlist->id,
                    'id_video' => $videoData['id'],
                    'duree_video' => $videoData['duration'],
                    'position' => $videoData['order'],
                    'insert_by' => Auth::id(),
                    'update_by' => Auth::id(),
                    'insert_date' => now(),
                    'update_date' => now(),
                ]);
            }

            DB::commit();

            notify()->success('Succès', 'Playlist "' . $playlist->nom . '" mise à jour avec succès avec ' . count($selectedVideos) . ' vidéo(s).');
            return redirect()->route('playlists.index');
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Erreur lors de la mise à jour de la playlist: ' . $e->getMessage());

            notify()->error('Erreur', 'Une erreur est survenue lors de la mise à jour de la playlist. Veuillez réessayer.');
            return redirect()->back()
                ->withInput();
        }
    }

    /**
     * Supprimer une playlist (soft delete)
     */
    public function destroy($id)
    {
        $playlist = Playlist::notDeleted()->findOrFail($id);

        try {
            DB::beginTransaction();

            // Marquer la playlist comme supprimée
            $playlist->update([
                'is_deleted' => true,
                'update_by' => Auth::id(),
            ]);

            // Marquer tous les éléments de la playlist comme supprimés
            PlaylistItem::where('id_playlist', $playlist->id)
                ->update([
                    'is_deleted' => true,
                    'update_by' => Auth::id(),
                ]);

            DB::commit();

            return redirect()->route('playlists.index')
                ->with('success', 'Playlist "' . $playlist->nom . '" supprimée avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Erreur lors de la suppression de la playlist: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la suppression de la playlist.');
        }
    }

    /**
     * Dupliquer une playlist
     */
    public function duplicate($id)
    {
        $originalPlaylist = Playlist::notDeleted()
            ->with(['items' => function ($query) {
                $query->notDeleted()->with('video');
            }])
            ->findOrFail($id);

        try {
            DB::beginTransaction();

            // Créer une copie de la playlist
            $newPlaylist = Playlist::create([
                'nom' => 'Copie de ' . $originalPlaylist->nom,
                'description' => $originalPlaylist->description,
                'date_debut' => now()->addDay(), // Programmer pour demain par défaut
                'id_user' => $originalPlaylist->id_user,
                'insert_by' => Auth::id(),
                'update_by' => Auth::id(),
            ]);

            // Copier tous les éléments de la playlist
            foreach ($originalPlaylist->items as $item) {
                if (!$item->is_deleted && $item->video && !$item->video->is_deleted) {
                    PlaylistItem::create([
                        'id_playlist' => $newPlaylist->id,
                        'id_video' => $item->id_video,
                        'duree_video' => $item->duree_video,
                        'insert_by' => Auth::id(),
                        'update_by' => Auth::id(),
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('playlists.index')
                ->with('success', 'Playlist dupliquée avec succès sous le nom "' . $newPlaylist->nom . '".');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Erreur lors de la duplication de la playlist: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la duplication de la playlist.');
        }
    }

    /**
     * Calculer la durée totale d'une playlist
     */
    private function calculateTotalDuration($items)
    {
        $totalSeconds = 0;

        foreach ($items as $item) {
            if (!$item->is_deleted && $item->duree_video) {
                // Convertir le format time en secondes
                $timeParts = explode(':', $item->duree_video);
                if (count($timeParts) === 3) {
                    $hours = (int)$timeParts[0];
                    $minutes = (int)$timeParts[1];
                    $seconds = (int)$timeParts[2];
                    $totalSeconds += ($hours * 3600) + ($minutes * 60) + $seconds;
                }
            }
        }

        // Reconvertir en format H:i:s
        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);
        $seconds = $totalSeconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    /**
     * API: Récupérer les détails d'une playlist pour la lecture
     */
    public function apiShow($id)
    {
        $playlist = Playlist::notDeleted()
            ->with([
                'user',
                'items' => function ($query) {
                    $query->notDeleted()
                        ->with('video')
                        ->orderBy('created_at', 'asc');
                }
            ])
            ->findOrFail($id);

        // Filtrer les items qui ont des vidéos valides
        $validItems = $playlist->items->filter(function ($item) {
            return $item->video && !$item->video->is_deleted;
        });

        $playlist->items = $validItems;
        $playlist->total_duration = $this->calculateTotalDuration($validItems);
        $playlist->video_count = $validItems->count();

        return response()->json($playlist);
    }

    /**
     * API: Récupérer toutes les playlists actives
     */
    public function apiIndex(Request $request)
    {
        $userId = $request->input('user_id');
        $upcoming = $request->input('upcoming'); // Pour les playlists à venir

        $playlists = Playlist::notDeleted()
            ->with([
                'user',
                'items' => function ($query) {
                    $query->notDeleted()->with('video');
                }
            ])
            ->when($userId, function ($query, $userId) {
                return $query->where('id_user', $userId);
            })
            ->when($upcoming, function ($query) {
                return $query->where('date_debut', '>=', now());
            })
            ->orderBy('date_debut', 'asc')
            ->get();

        // Ajouter des informations calculées
        $playlists->transform(function ($playlist) {
            $validItems = $playlist->items->filter(function ($item) {
                return $item->video && !$item->video->is_deleted;
            });

            $playlist->total_duration = $this->calculateTotalDuration($validItems);
            $playlist->video_count = $validItems->count();
            $playlist->status = $this->getPlaylistStatus($playlist);

            return $playlist;
        });

        return response()->json($playlists);
    }

    /**
     * Déterminer le statut d'une playlist
     */
    private function getPlaylistStatus($playlist)
    {
        $now = now();
        $dateDebut = \Carbon\Carbon::parse($playlist->date_debut);

        if ($dateDebut->isFuture()) {
            return 'À venir';
        } elseif ($dateDebut->isToday()) {
            return 'Aujourd\'hui';
        } else {
            return 'Passée';
        }
    }

    /**
     * Marquer une playlist comme active/inactive
     */
    public function toggleStatus($id)
    {
        $playlist = Playlist::notDeleted()->findOrFail($id);

        try {
            // On peut ajouter un champ 'is_active' à la migration si nécessaire
            // Pour l'instant, on utilise la date de début pour déterminer le statut

            $newDate = $playlist->date_debut <= now() ? now()->addMinutes(5) : now()->subDay();

            $playlist->update([
                'date_debut' => $newDate,
                'update_by' => Auth::id(),
            ]);

            $status = $newDate > now() ? 'programmée' : 'désactivée';

            return redirect()->back()
                ->with('success', 'Playlist "' . $playlist->nom . '" ' . $status . ' avec succès.');
        } catch (\Exception $e) {
            Log::error('Erreur lors du changement de statut: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors du changement de statut.');
        }
    }

    /**
     * API: Récupérer les vidéos d'une playlist dans l'ordre de lecture
     */
    public function getPlaylistVideos($id)
    {
        $playlist = Playlist::notDeleted()->findOrFail($id);

        $videos = PlaylistItem::where('id_playlist', $playlist->id)
            ->notDeleted()
            ->with('video')
            ->orderBy('created_at', 'asc')
            ->get()
            ->filter(function ($item) {
                return $item->video && !$item->video->is_deleted;
            })
            ->map(function ($item, $index) {
                return [
                    'id' => $item->video->id,
                    'titre' => $item->video->titre,
                    'description' => $item->video->description,
                    'duree' => $item->duree_video,
                    'chemin_fichier' => $item->video->chemin_fichier,
                    'ordre' => $index + 1,
                ];
            })
            ->values();

        return response()->json([
            'playlist' => $playlist,
            'videos' => $videos,
            'total_count' => $videos->count()
        ]);
    }

    /**
     * API: Obtenir les statistiques des playlists
     */
    public function getStats()
    {
        $stats = [
            'total_playlists' => Playlist::notDeleted()->count(),
            'playlists_today' => Playlist::notDeleted()
                ->whereDate('date_debut', today())
                ->count(),
            'upcoming_playlists' => Playlist::notDeleted()
                ->where('date_debut', '>', now())
                ->count(),
            'total_videos_in_playlists' => PlaylistItem::notDeleted()
                ->whereHas('playlist', function ($query) {
                    $query->notDeleted();
                })
                ->count(),
        ];

        return response()->json($stats);
    }
}
