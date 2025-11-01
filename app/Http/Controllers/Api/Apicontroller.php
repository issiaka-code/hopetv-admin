<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Emission;
use App\Models\EmissionItem;
use App\Models\Enseignement;
use App\Models\Etablissement;
use App\Models\HomeCharity;
use App\Models\InfoBulle;
use App\Models\Media;
use App\Models\Playlist;
use App\Models\Podcast;
use App\Models\Priere;
use App\Models\Programme;
use App\Models\Prophetie;
use App\Models\Temoignage;
use App\Models\Video;
use Illuminate\Http\Request;
use Carbon\Carbon;

class Apicontroller extends Controller
{

    public function getInfoBulles()
    {
        $infoBulles = InfoBulle::where('is_deleted', false)
            ->where('is_active', true)
            ->get();

        return response()->json([
            'status' => 'success',
            'count' => $infoBulles->count(),
            'data' => $infoBulles,
            'status' => 200
        ], 200);
    }

    public function getPlaylistDuJour()
    {
        $today = Carbon::today();

        // On cherche la playlist du jour
        $playlist = Playlist::whereDate('date_debut', $today)
            ->where('is_deleted', false)
            ->where('etat', true)
            ->with([
                'items.video.media' // On charge les vidéos et leurs médias
            ])
            ->first();

        // Si aucune playlist aujourd’hui → prendre la dernière playlist active
        if (!$playlist) {
            $playlist = Playlist::where('is_deleted', false)
                ->where('etat', true)
                ->whereDate('date_debut', '<', $today)
                ->with([
                    'items.video.media'
                ])
                ->latest('date_debut')
                ->first();
        }

        if (!$playlist) {
            return response()->json([
                'status' => 'error',
                'message' => 'Aucune playlist disponible',
                'status' => 404
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'playlist' => $playlist,
            'status' => 200
        ], 200);
    }



    public function getVideos()
    {
        $videos = Video::where('is_deleted', false)
            ->with('media') // relation vers medias
            ->latest()
            ->paginate(10); // pagination de 10 vidéos

        if ($videos->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Aucune vidéo disponible',
                'status' => 404
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'videos' => $videos,
            'status' => 200,
            'pagination' => [
                'current_page' => $videos->currentPage(),
                'per_page' => $videos->perPage(),
                'total' => $videos->total(),
                'last_page' => $videos->lastPage(),
            ]

        ], 200);
    }

    public function getVideossearch(Request $request)
    {
        $search = $request->query('search'); // mot-clé envoyé par ?search=

        $videos = Video::where('is_deleted', false)
            ->with('media') // relation vers medias
            ->when($search, function ($query, $search) {
                $query->where('nom', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10);

        if ($videos->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Aucune vidéo trouvée'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'videos' => $videos
        ], 200);
    }

    public function getVideo($id)
    {
        // Récupère la vidéo avec le média associé
        $video = Video::with(['media'])  // relation media
            ->where('id', $id)
            ->where('is_deleted', false)
            ->first();

        if (!$video) {
            return response()->json([
                'message' => 'Vidéo introuvable.'
            ], 404);
        }

        return response()->json($video);
    }

    public function getTemoignages(Request $request)
    {
        $perPage = $request->query('per_page', 10); // par défaut 10 témoignages par page

        $temoignages = Temoignage::with('media')
            ->where('is_deleted', false)
            ->paginate($perPage); // 🔹 pagination

        return response()->json([
            'status' => 200,
            'data' => $temoignages->items(),
            'pagination' => [
                'current_page' => $temoignages->currentPage(),
                'per_page' => $temoignages->perPage(),
                'total' => $temoignages->total(),
                'last_page' => $temoignages->lastPage(),
            ]
        ]);
    }

    // 🔹 Récupérer le détail d’un témoignage
    public function getTemoignageDetail($id)
    {
        $temoignage = Temoignage::with('media')
            ->where('is_deleted', false)
            ->find($id);

        if (!$temoignage) {
            return response()->json([
                'status' => 'error',
                'message' => 'Témoignage non trouvé'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $temoignage
        ]);
    }

    // TemoignageController.php
    public function similairesParNom($id)
    {
        $temoignage = Temoignage::find($id);
        if (!$temoignage || $temoignage->is_deleted) {
            return response()->json(['message' => 'Témoignage introuvable'], 404);
        }

        $keywords = explode(' ', $temoignage->nom); // sépare les mots du titre

        $query = Temoignage::with('media')
            ->where('id', '!=', $id)
            ->where('is_deleted', false);

        foreach ($keywords as $word) {
            $query->orWhere('nom', 'like', "%{$word}%");
        }

        $similaires = $query->take(10)->get(); // limite à 5 résultats

        return response()->json(['data' => $similaires]);
    }

    public function video(Request $request)
    {
        $videos = Video::with(['media', 'insertedBy', 'updatedBy'])
            ->where('is_deleted', false) // éviter les vidéos supprimées
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $videos->items(),
            'status' => 200,
            'pagination' => [
                'current_page' => $videos->currentPage(),
                'per_page' => $videos->perPage(),
                'total' => $videos->total(),
                'last_page' => $videos->lastPage(),
            ]

        ], 200);
    }

    public function showAvecSimilaires($id)
    {
        $video = Video::with('media')->find($id);

        if (!$video || $video->is_deleted) {
            return response()->json(['message' => 'Vidéo introuvable'], 404);
        }

        // Séparer le titre en mots-clés
        $keywords = explode(' ', $video->nom);

        // Construire la requête pour les vidéos similaires
        $similairesQuery = Video::with('media')
            ->where('id', '!=', $id)
            ->where('is_deleted', false)
            ->where(function ($q) use ($keywords) {
                foreach ($keywords as $word) {
                    $q->orWhere('nom', 'like', "%{$word}%")
                        ->orWhere('description', 'like', "%{$word}%");
                }
            });

        $similaires = $similairesQuery->take(10)->get();

        return response()->json([
            'video' => $video,
            'similaires' => $similaires
        ]);
    }

    public function podcast(Request $request)
    {
        $pageSize = 10;
        $podcasts = Podcast::with('media')
            ->where('is_deleted', false)
            ->paginate($pageSize);

        return response()->json([
            'data' => $podcasts->items(),
            'pagination' => [
                'current_page' => $podcasts->currentPage(),
                'last_page' => $podcasts->lastPage(),
                'per_page' => $podcasts->perPage(),
                'total' => $podcasts->total(),
            ],
        ]);
    }

    // 🔹 Détails d’un podcast + podcasts similaires
    public function showWithSimilairesPodcast($id)
    {
        $podcast = Podcast::with('media')->find($id);

        if (!$podcast || $podcast->is_deleted) {
            return response()->json(['message' => 'Podcast introuvable'], 404);
        }

        // Extraire les mots du titre
        $keywords = explode(' ', $podcast->nom);

        // Requête pour podcasts similaires
        $similairesQuery = Podcast::with('media')
            ->where('id', '!=', $id)
            ->where('is_deleted', false)
            ->where(function ($q) use ($keywords) {
                foreach ($keywords as $word) {
                    $q->orWhere('nom', 'like', "%{$word}%")
                        ->orWhere('description', 'like', "%{$word}%");
                }
            });

        $similaires = $similairesQuery->take(10)->get();

        return response()->json([
            'podcast' => $podcast,
            'similaires' => $similaires
        ]);
    }

    // 🔹 Liste des émissions avec pagination
    public function emissions(Request $request)
    {
        $pageSize = 10;
        $emissions = Emission::notDeleted()
            // ->published()
            ->paginate($pageSize);

        return response()->json([
            'data' => $emissions->items(),
            'pagination' => [
                'current_page' => $emissions->currentPage(),
                'last_page' => $emissions->lastPage(),
                'per_page' => $emissions->perPage(),
                'total' => $emissions->total(),
            ],
        ]);
    }



    public function afficherPdf($fichier)
    {
        $media = Media::find($fichier);

        $path = storage_path('app/public/' . $media->url_fichier);

        if (!file_exists($path)) {
            abort(404);
        }

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fichier . '"'
        ]);
    }


    public function etablisement()
    {
        $etablissements = Etablissement::where('is_deleted', false)
            ->orderBy('created_at', 'desc')
            ->take(5) // ou ->limit(5)
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Liste des 5 derniers établissements récupérée avec succès',
            'data' => $etablissements
        ]);
    }

    public function priere(Request $request)
    {
        // Récupérer la taille de la page depuis la requête (par défaut 10)
        $perPage = $request->get('per_page', 10);

        // Récupérer les prières non supprimées
        $prieres = Priere::with(['media', 'insertedBy', 'updatedBy'])
            ->where('is_deleted', false)
            ->latest()
            ->paginate($perPage);

        // Retour JSON avec pagination
        return response()->json([
            'data' => $prieres->items(),
            'pagination' => [
                'current_page' => $prieres->currentPage(),
                'last_page' => $prieres->lastPage(),
                'per_page' => $prieres->perPage(),
                'total' => $prieres->total(),
            ],
        ]);
    }

    public function showWithSimilaires($id)
    {
        // Charger la prière avec sa relation média
        $priere = Priere::with('media')
            ->where('is_deleted', false)
            ->find($id);

        if (!$priere) {
            return response()->json(['message' => 'Prière introuvable'], 404);
        }

        // Extraire les mots du titre (nom) de la prière
        $keywords = explode(' ', $priere->nom);

        // Construire la requête pour trouver des prières similaires
        $similairesQuery = Priere::with('media')
            ->where('is_deleted', false)
            ->where('id', '!=', $id) // exclure la prière courante
            ->where(function ($q) use ($keywords) {
                foreach ($keywords as $word) {
                    $q->orWhere('nom', 'like', "%{$word}%")
                        ->orWhere('description', 'like', "%{$word}%");
                }
            });

        // Limiter à 10 prières similaires
        $similaires = $similairesQuery->take(10)->get();

        return response()->json([
            'priere' => $priere,
            'similaires' => $similaires
        ]);
    }

    public function getHomeCharities(Request $request)
    {
        $perPage = $request->query('per_page', 10); // par défaut 10 éléments par page

        $homeCharities = HomeCharity::with('media')
            ->where('is_deleted', false)
            ->paginate($perPage);

        return response()->json([
            'status' => 200,
            'data' => $homeCharities->items(),
            'pagination' => [
                'current_page' => $homeCharities->currentPage(),
                'per_page' => $homeCharities->perPage(),
                'total' => $homeCharities->total(),
                'last_page' => $homeCharities->lastPage(),
            ]
        ]);
    }

    /**
     * 🔹 Récupère les HomeCharities similaires par nom
     */
    public function showHomeCharities($id)
    {
        $homeCharity = HomeCharity::with('media')
            ->where('is_deleted', false)
            ->find($id);


        if (!$homeCharity || $homeCharity->is_deleted) {
            return response()->json(['message' => 'Élément introuvable'], 404);
        }

        $keywords = explode(' ', $homeCharity->nom);

        $query = HomeCharity::with('media')
            ->where('id', '!=', $id)
            ->where('is_deleted', false);

        foreach ($keywords as $word) {
            $query->orWhere('nom', 'like', "%{$word}%");
        }

        $similaires = $query->take(10)->get();

        return response()->json([
            'homeCharity' => $homeCharity,
            'similaires' => $similaires
        ]);
    }

    public function getEnseignements(Request $request)
    {
        $perPage = $request->query('per_page', 10);

        $enseignements = Enseignement::with('media')
            ->where('is_deleted', false)
            ->paginate($perPage);

        return response()->json([
            'status' => 200,
            'data' => $enseignements->items(),
            'pagination' => [
                'current_page' => $enseignements->currentPage(),
                'per_page' => $enseignements->perPage(),
                'total' => $enseignements->total(),
                'last_page' => $enseignements->lastPage(),
            ],
        ]);
    }

    public function showEnseignement($id)
    {
        $enseignement = Enseignement::with('media')
            ->where('is_deleted', false)
            ->find($id);

        if (!$enseignement) {
            return response()->json(['message' => 'Enseignement introuvable'], 404);
        }

        // Mots clés du nom pour trouver des similaires
        $keywords = explode(' ', $enseignement->nom);

        $similairesQuery = Enseignement::with('media')
            ->where('is_deleted', false)
            ->where('id', '!=', $id)
            ->where(function ($q) use ($keywords) {
                foreach ($keywords as $word) {
                    $q->orWhere('nom', 'like', "%{$word}%")
                        ->orWhere('description', 'like', "%{$word}%");
                }
            });

        $similaires = $similairesQuery->take(10)->get();

        return response()->json([
            'enseignement' => $enseignement,
            'similaires' => $similaires
        ]);
    }

    public function getEmissionItems(Request $request, $emissionId)
    {
        $perPage = $request->query('per_page', 10);

        $items = EmissionItem::with('emission')
            ->where('id_Emission', $emissionId)
            ->where('is_deleted', false)
            ->orderBy('id', 'desc') // ou 'created_at' si tu veux par date
            ->paginate($perPage);

        return response()->json([
            'status' => 200,
            'data' => $items->items(),
            'pagination' => [
                'current_page' => $items->currentPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'last_page' => $items->lastPage(),
            ],
        ]);
    }

    // 🔹 Détail d’une émission + émissions similaires
    public function showWithSimilairesEmission($id)
    {
        $item = EmissionItem::with('emission', 'insertedBy', 'updatedBy')
            ->where('is_deleted', false)
            ->find($id);

        if (!$item) {
            return response()->json(['message' => 'Item d’émission introuvable'], 404);
        }

        // 🔹 Récupération des mots-clés à partir du titre de la vidéo
        $keywords = explode(' ', $item->titre_video);

        // 🔹 Recherche des émissions similaires
        $similaires = EmissionItem::with('emission')
            ->where('id', '!=', $item->id)
            ->where('is_deleted', false)
            ->where(function ($query) use ($keywords) {
                foreach ($keywords as $word) {
                    $query->orWhere('titre_video', 'like', "%{$word}%");
                }
            })
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

        return response()->json([
            'status' => 200,
            'data' => $item,
            'video_url' => $item->isUploadedVideo() ? $item->videoFileUrl : $item->video_url,
            'thumbnail_url' => $item->thumbnailUrl,
            'similaires' => $similaires,
        ]);
    }

    public function getProgrammes(Request $request)
    {
        $perPage = $request->query('per_page', 10);

        $programmes = Programme::with(['media'])->where('is_deleted', false)
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        return response()->json([
            'status' => 200,
            'data' => $programmes->items(),
            'pagination' => [
                'current_page' => $programmes->currentPage(),
                'per_page' => $programmes->perPage(),
                'total' => $programmes->total(),
                'last_page' => $programmes->lastPage(),
            ],
        ]);
    }

    public function showProgramme($id)
    {
        $programme = Programme::with('media')
            ->where('is_deleted', false)
            ->find($id);

        if (!$programme) {
            return response()->json(['message' => 'Programme introuvable'], 404);
        }

        // Découper le nom en mots-clés
        $keywords = explode(' ', $programme->nom);

        // Chercher des programmes similaires
        $similairesQuery = Programme::with('media')
            ->where('is_deleted', false)
            ->where('id', '!=', $id)
            ->where(function ($q) use ($keywords) {
                foreach ($keywords as $word) {
                    $q->orWhere('nom', 'like', "%{$word}%")
                        ->orWhere('description', 'like', "%{$word}%");
                }
            });

        $similaires = $similairesQuery->take(10)->get();

        return response()->json([
            'programme' => $programme,
            'similaires' => $similaires
        ]);
    }

    public function search(Request $request)
    {
        $type = $request->query('type'); // type de recherche : priere, video, temoignage, podcast, etc.
        $query = $request->query('query'); // mot-clé recherché
        $perPage = $request->query('per_page', 10); // pagination par défaut 10
        $startDate = $request->query('startDate') ? Carbon::parse($request->query('startDate')) : null;
        $endDate = $request->query('endDate') ? Carbon::parse($request->query('endDate')) : null;

        if (!$type) {
            return response()->json([
                'status' => 'error',
                'message' => 'Le type et la query sont requis.'
            ], 400);
        }

        switch (strtolower($type)) {
            case 'priere':
                $results = Priere::with('media')
                    ->where('is_deleted', false)
                    ->where(function ($q) use ($query) {
                        $q->where('nom', 'like', "%{$query}%")
                            ->orWhere('description', 'like', "%{$query}%");
                    });
                break;

            case 'video':
                $results = Video::with('media')
                    ->where('is_deleted', false)
                    ->where(function ($q) use ($query) {
                        $q->where('nom', 'like', "%{$query}%")
                            ->orWhere('description', 'like', "%{$query}%");
                    });
                break;

            case 'temoignage':
                $results = Temoignage::with('media')
                    ->where('is_deleted', false)
                    ->where(function ($q) use ($query) {
                        $q->where('nom', 'like', "%{$query}%")
                            ->orWhere('description', 'like', "%{$query}%");
                    });
                break;

            case 'podcast':
                $results = Podcast::with('media')
                    ->where('is_deleted', false)
                    ->where(function ($q) use ($query) {
                        $q->where('nom', 'like', "%{$query}%")
                            ->orWhere('description', 'like', "%{$query}%");
                    });
                break;

            case 'homecharity':
                $results = HomeCharity::with('media')
                    ->where('is_deleted', false)
                    ->where('nom', 'like', "%{$query}%");
                break;

            case 'enseignement':
                $results = Enseignement::with('media')
                    ->where('is_deleted', false)
                    ->where(function ($q) use ($query) {
                        $q->where('nom', 'like', "%{$query}%")
                            ->orWhere('description', 'like', "%{$query}%");
                    });
                break;

            case 'emission':
                $results = Emission::where('is_deleted', false)
                    ->where(function ($q) use ($query) {
                        $q->where('nom', 'like', "%{$query}%")
                            ->orWhere('description', 'like', "%{$query}%");
                    });
                break;

            case 'prophetie':
                $results = Prophetie::where('is_deleted', false)
                    ->where(function ($q) use ($query) {
                        $q->where('nom', 'like', "%{$query}%")
                            ->orWhere('description', 'like', "%{$query}%");
                    });
                break;    

            default:
                return response()->json([
                    'status' => 'error',
                    'message' => 'Type de recherche invalide.'
                ], 400);
        }

        // 🔹 Application du filtre de date (correction)
        if ($startDate && $endDate) {
            $results->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()]);
        } elseif ($startDate && !$endDate) {
            $results->whereDate('created_at', $startDate->toDateString());
        } elseif (!$startDate && $endDate) {
            $results->whereDate('created_at', '<=', $endDate->toDateString());
        }

        $results = $results->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'type' => $type,
            'query' => $query,
            'data' => $results->items(),
            'pagination' => [
                'current_page' => $results->currentPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
                'last_page' => $results->lastPage(),
            ]
        ], 200);
    }

    public function globalSearch(Request $request)
    {
        $query = $request->query('query'); // mot-clé recherché
        $limit = 10; // max 10 éléments par type

        // 🔹 Conversion des dates en Carbon
        $startDate = $request->query('startDate') ? Carbon::parse($request->query('startDate')) : null;
        $endDate = $request->query('endDate') ? Carbon::parse($request->query('endDate')) : null;

        // 🔹 Prière
        $prieres = Priere::with('media')
            ->where('is_deleted', false)
            ->where(function ($q) use ($query) {
                $q->where('nom', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            })
            ->when($startDate && $endDate, fn($q) => $q->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()]))
            ->when($startDate && !$endDate, fn($q) => $q->whereDate('created_at', $startDate->toDateString()))
            ->when(!$startDate && $endDate, fn($q) => $q->whereDate('created_at', '<=', $endDate->toDateString()))
            ->latest()
            ->take($limit)
            ->get();

        // 🔹 Vidéo
        $videos = Video::with('media')
            ->where('is_deleted', false)
            ->where(function ($q) use ($query) {
                $q->where('nom', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            })
            ->when($startDate && $endDate, fn($q) => $q->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()]))
            ->when($startDate && !$endDate, fn($q) => $q->whereDate('created_at', $startDate->toDateString()))
            ->when(!$startDate && $endDate, fn($q) => $q->whereDate('created_at', '<=', $endDate->toDateString()))
            ->latest()
            ->take($limit)
            ->get();

        // 🔹 Témoignage
        $temoignages = Temoignage::with('media')
            ->where('is_deleted', false)
            ->where(function ($q) use ($query) {
                $q->where('nom', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            })
            ->when($startDate && $endDate, fn($q) => $q->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()]))
            ->when($startDate && !$endDate, fn($q) => $q->whereDate('created_at', $startDate->toDateString()))
            ->when(!$startDate && $endDate, fn($q) => $q->whereDate('created_at', '<=', $endDate->toDateString()))
            ->latest()
            ->take($limit)
            ->get();

        // 🔹 Podcast
        $podcasts = Podcast::with('media')
            ->where('is_deleted', false)
            ->where(function ($q) use ($query) {
                $q->where('nom', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            })
            ->when($startDate && $endDate, fn($q) => $q->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()]))
            ->when($startDate && !$endDate, fn($q) => $q->whereDate('created_at', $startDate->toDateString()))
            ->when(!$startDate && $endDate, fn($q) => $q->whereDate('created_at', '<=', $endDate->toDateString()))
            ->latest()
            ->take($limit)
            ->get();

        // 🔹 HomeCharity
        $homeCharities = HomeCharity::with('media')
            ->where('is_deleted', false)
            ->where('nom', 'like', "%{$query}%")
            ->when($startDate && $endDate, fn($q) => $q->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()]))
            ->when($startDate && !$endDate, fn($q) => $q->whereDate('created_at', $startDate->toDateString()))
            ->when(!$startDate && $endDate, fn($q) => $q->whereDate('created_at', '<=', $endDate->toDateString()))
            ->latest()
            ->take($limit)
            ->get();

            $prophetie = Prophetie ::with('media')
            ->where('is_deleted', false)
            ->where('nom', 'like', "%{$query}%")
            ->when($startDate && $endDate, fn($q) => $q->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()]))
            ->when($startDate && !$endDate, fn($q) => $q->whereDate('created_at', $startDate->toDateString()))
            ->when(!$startDate && $endDate, fn($q) => $q->whereDate('created_at', '<=', $endDate->toDateString()))
            ->latest()
            ->take($limit)
            ->get();


        // 🔹 Enseignement
        $enseignements = Enseignement::with('media')
            ->where('is_deleted', false)
            ->where(function ($q) use ($query) {
                $q->where('nom', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            })
            ->when($startDate && $endDate, fn($q) => $q->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()]))
            ->when($startDate && !$endDate, fn($q) => $q->whereDate('created_at', $startDate->toDateString()))
            ->when(!$startDate && $endDate, fn($q) => $q->whereDate('created_at', '<=', $endDate->toDateString()))
            ->latest()
            ->take($limit)
            ->get();

        // 🔹 Emission
        $emissions = Emission::where('is_deleted', false)
            ->where(function ($q) use ($query) {
                $q->where('nom', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            })
            ->when($startDate && $endDate, fn($q) => $q->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()]))
            ->when($startDate && !$endDate, fn($q) => $q->whereDate('created_at', $startDate->toDateString()))
            ->when(!$startDate && $endDate, fn($q) => $q->whereDate('created_at', '<=', $endDate->toDateString()))
            ->latest()
            ->take($limit)
            ->get();

        // 🔹 Structure finale par type
        $data = [
            ['type' => 'Prière', 'items' => $prieres],
            ['type' => 'Vidéo', 'items' => $videos],
            ['type' => 'Témoignage', 'items' => $temoignages],
            ['type' => 'Podcast', 'items' => $podcasts],
            ['type' => 'Homecharity', 'items' => $homeCharities],
            ['type' => 'Enseignement', 'items' => $enseignements],
            ['type' => 'Emission', 'items' => $emissions],
            ['type'=> 'Prophetie' ,'items'=> $prophetie]
        ];

        // 🔹 Supprimer les types vides
        $filteredData = collect($data)->filter(fn($group) => $group['items']->isNotEmpty())->values();

        return response()->json([
            'status' => 'success',
            'query' => $query,
            'start_date' => $startDate?->toDateString(),
            'end_date' => $endDate?->toDateString(),
            'data' => $filteredData,
        ], 200);
    }

     public function getPropheties(Request $request)
    {
        $perPage = $request->query('per_page', 10);

        $propheties = Prophetie::with('media')
            ->where('is_deleted', false)
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'status' => 200,
            'data' => $propheties->items(),
            'pagination' => [
                'current_page' => $propheties->currentPage(),
                'per_page' => $propheties->perPage(),
                'total' => $propheties->total(),
                'last_page' => $propheties->lastPage(),
            ],
        ]);
    }

    /**
     * Afficher une prophétie avec ses similaires.
     */
    public function showProphetie($id)
    {
        $prophetie = Prophetie::with('media')
            ->where('is_deleted', false)
            ->find($id);

        if (!$prophetie) {
            return response()->json(['message' => 'Prophétie introuvable'], 404);
        }

        // Extraction des mots-clés du nom pour trouver les similaires
        $keywords = explode(' ', $prophetie->nom);

        $similairesQuery = Prophetie::with('media')
            ->where('is_deleted', false)
            ->where('id', '!=', $id)
            ->where(function ($q) use ($keywords) {
                foreach ($keywords as $word) {
                    $q->orWhere('nom', 'like', "%{$word}%")
                      ->orWhere('description', 'like', "%{$word}%");
                }
            });

        $similaires = $similairesQuery->take(10)->get();

        return response()->json([
            'prophetie' => $prophetie,
            'similaires' => $similaires
        ]);
    }
}
