@extends('admin.master')

@section('title', 'Détails de la Playlist')

@push('styles')
    <style>
        .playlist-details-container {
            padding: 20px;
        }

        .playlist-header {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .playlist-info-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
            overflow: hidden;
        }

        .playlist-info-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
            font-weight: 600;
            color: #4e73df;
        }

        .playlist-info-body {
            padding: 20px;
        }

        .info-row {
            display: flex;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        .info-row:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #495057;
            min-width: 150px;
        }

        .info-value {
            color: #6c757d;
            flex: 1;
        }

        .videos-table {
            width: 100%;
            border-collapse: collapse;
        }

        .videos-table th {
            background: #4e73df;
            color: white;
            padding: 12px 15px;
            text-align: left;
        }

        .videos-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .videos-table tr:nth-child(even) {
            background: #f8f9fa;
        }

        .videos-table tr:hover {
            background: #e9ecef;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-edit {
            background: #4e73df;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            color: white;
            transition: all 0.3s;
        }

        .btn-edit:hover {
            background: #2e59d9;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(78, 115, 223, 0.3);
        }

        .btn-back {
            background: #6c757d;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            color: white;
            transition: all 0.3s;
        }

        .btn-back:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .btn-play {
            background: #1cc88a;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            color: white;
            transition: all 0.3s;
        }

        .btn-play:hover {
            background: #17a673;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(28, 200, 138, 0.3);
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #dee2e6;
        }

        /* Modal Styles */
        .playlist-modal .modal-content {
            border-radius: 10px;
            overflow: hidden;
        }

        .playlist-modal .modal-header {
            background: #4e73df;
            color: white;
        }

        .video-player-container {
            position: relative;
            width: 100%;
            height: 0;
            padding-bottom: 56.25%; /* 16:9 Aspect Ratio */
            margin-bottom: 20px;
        }

        .video-player {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
            border-radius: 8px;
        }

        .playlist-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .playlist-progress {
            flex-grow: 1;
            margin: 0 15px;
        }

        .playlist-items {
            max-height: 300px;
            overflow-y: auto;
        }

        .playlist-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background 0.3s;
        }

        .playlist-item:hover {
            background: #f0f4f8;
        }

        .playlist-item.active {
            background: #e3f2fd;
            border-left: 4px solid #4e73df;
        }

        .playlist-item-thumb {
            width: 120px;
            height: 68px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 15px;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
        }

        .playlist-item-info {
            flex-grow: 1;
        }

        .playlist-item-title {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .playlist-item-duration {
            color: #6c757d;
            font-size: 0.85rem;
        }

        .current-playing {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 15px;
            padding: 10px;
            background: #e8f4fd;
            border-radius: 5px;
            border-left: 4px solid #4e73df;
        }

        @media (max-width: 768px) {
            .info-row {
                flex-direction: column;
            }

            .info-label {
                margin-bottom: 5px;
                min-width: auto;
            }

            .action-buttons {
                flex-direction: column;
            }

            .videos-table {
                display: block;
                overflow-x: auto;
            }

            .playlist-controls {
                flex-direction: column;
                gap: 10px;
            }

            .playlist-progress {
                width: 100%;
                margin: 10px 0;
            }
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid playlist-details-container">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="section-title">
                        <i class="fas fa-list-ol mr-2"></i>Détails de la playlist
                    </h2>
                    <a href="{{ route('playlists.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nouvelle Playlist
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <!-- Carte d'informations -->
                <div class="playlist-info-card">
                    <div class="playlist-info-header">
                        <i class="fas fa-info-circle"></i> Informations de la playlist
                    </div>
                    <div class="playlist-info-body">
                        <div class="info-row">
                            <div class="info-label">Nom:</div>
                            <div class="info-value">{{ $playlist->nom }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Description:</div>
                            <div class="info-value">{{ $playlist->description ?: 'Aucune description' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Date de début:</div>
                            <div class="info-value">{{ $playlist->date_debut->format('d/m/Y à H:i') }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Nombre de vidéos:</div>
                            <div class="info-value">{{ $playlist->items->count() }}</div>
                        </div>
                    </div>
                </div>

                <!-- Tableau des vidéos -->
                <div class="playlist-info-card">
                    <div class="playlist-info-header d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-film"></i> Vidéos dans la playlist
                        </div>
                        @if ($playlist->items->count() > 0)
                            <button type="button" class="btn btn-sm btn-success bg-success" data-toggle="modal"
                                data-target="#playPlaylistModal">
                                <i class="fas fa-play"></i> Lire la playlist
                            </button>
                        @endif
                    </div>
                    <div class="playlist-info-body">
                        @if ($playlist->items->count() > 0)
                            <div class="table-responsive">
                                <table class="videos-table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Titre de la vidéo</th>
                                            <th>Durée</th>
                                            <th>Ordre de lecture</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($playlist->items->sortBy('order') as $item)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $item->video->nom }}</td>
                                                <td>{{ $item->duree_video ?? 'N/A' }}</td>
                                                <td>{{ $item->position }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="empty-state">
                                <i class="fas fa-video-slash"></i>
                                <h4>Aucune vidéo dans cette playlist</h4>
                                <p>Ajoutez des vidéos pour commencer à utiliser cette playlist</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Boutons d'action -->
                <div class="action-buttons">
                    <a href="{{ route('playlists.edit', $playlist->id) }}" class="btn-edit">
                        <i class="fas fa-edit"></i> Modifier la playlist
                    </a>
                    <a href="{{ route('playlists.index') }}" class="btn-back">
                        <i class="fas fa-arrow-left"></i> Retour à la liste
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal pour lire la playlist -->
    <div class="modal fade playlist-modal" id="playPlaylistModal" tabindex="-1" role="dialog"
        aria-labelledby="playPlaylistModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="playPlaylistModalLabel">
                        <i class="fas fa-play-circle"></i> Lecture de la playlist: {{ $playlist->nom }}
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="current-playing" id="currentVideoTitle">
                        Chargement...
                    </div>

                    <div class="video-player-container">
                        <!-- Utilisation d'une balise video HTML5 au lieu d'un iframe -->
                        <video class="video-player" id="videoPlayer" controls>
                            Votre navigateur ne supporte pas la lecture de vidéos.
                        </video>
                    </div>

                    <div class="playlist-controls">
                        <button class="btn bg-secondary" id="prevVideo">
                            <i class="fas fa-step-backward"></i> Précédent
                        </button>

                        <div class="playlist-progress">
                            <div class="progress">
                                <div class="progress-bar" id="playlistProgress" role="progressbar" style="width: 0%"
                                    aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small id="progressText">Video 1 sur {{ $playlist->items->count() }}</small>
                        </div>

                        <button class="btn bg-secondary " id="nextVideo">
                            Suivant <i class="fas fa-step-forward"></i>
                        </button>
                    </div>

                    <h6 class="d-none">Liste de lecture:</h6>
                    <div class="d-none playlist-items">
                        @foreach ($playlist->items->sortBy('order') as $index => $item)
                            <div class="playlist-item" data-video-id="{{ $item->video->id }}"
                                data-video-src="{{ asset('storage/' . $item->video->media->url_fichier) }}"
                                data-order="{{ $item->order }}">
                                <div class="playlist-item-thumb">
                                    <i class="fas fa-play-circle"></i>
                                </div>
                                <div class="playlist-item-info">
                                    <div class="playlist-item-title">{{ $item->video->nom }}</div>
                                    <div class="playlist-item-duration">{{ $item->duree_video ?? 'N/A' }}</div>
                                </div>
                                <div class="playlist-item-order">{{ $item->position }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>
        </div>  
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Variables pour la gestion de la playlist
            let currentVideoIndex = 0;
            let isAutoplayEnabled = false;
            const playlistItems = document.querySelectorAll('.playlist-item');
            const videoPlayer = document.getElementById('videoPlayer');

            // Récupération des données via les attributs data- des éléments HTML
            const videoItems = [];
            playlistItems.forEach(item => {
                videoItems.push({
                    id: item.dataset.videoId,
                    title: item.querySelector('.playlist-item-title').textContent,
                    url: item.dataset.videoSrc,
                    order: parseInt(item.dataset.order),
                    duration: item.querySelector('.playlist-item-duration').textContent
                });
            });

            // Trier par ordre
            videoItems.sort((a, b) => a.order - b.order);

            // Éléments DOM
            const currentVideoTitle = document.getElementById('currentVideoTitle');
            const prevButton = document.getElementById('prevVideo');
            const nextButton = document.getElementById('nextVideo');
            const progressBar = document.getElementById('playlistProgress');
            const progressText = document.getElementById('progressText');
            const toggleAutoplayButton = document.getElementById('toggleAutoplay');

            // Initialiser le lecteur avec la première vidéo
            if (videoItems.length > 0) {
                loadVideo(0);
            }

            // Charger une vidéo spécifique
            function loadVideo(index) {
                if (index < 0 || index >= videoItems.length) return;

                currentVideoIndex = index;
                const video = videoItems[index];

                // Mettre à jour l'interface
                currentVideoTitle.innerHTML = `<i class="fas fa-play-circle"></i> En cours: ${video.title}`;
                
                // Configurer la source vidéo
                videoPlayer.innerHTML = `<source src="${video.url}" type="video/mp4">`;
                videoPlayer.load();
                
                progressBar.style.width = `${((index + 1) / videoItems.length) * 100}%`;
                progressText.textContent = `Video ${index + 1} sur ${videoItems.length}`;

                // Mettre à jour les éléments de la playlist
                playlistItems.forEach((item, i) => {
                    if (i === index) {
                        item.classList.add('active');
                    } else {
                        item.classList.remove('active');
                    }
                });

                // Désactiver/activer les boutons de navigation
                prevButton.disabled = index === 0;
                nextButton.disabled = index === videoItems.length - 1;
                
                // Lire automatiquement la vidéo
                if (isAutoplayEnabled) {
                    const playPromise = videoPlayer.play();
                    if (playPromise !== undefined) {
                        playPromise.catch(error => {
                            console.log("La lecture automatique a été empêchée:", error);
                        });
                    }
                }
            }

            // Événement lorsque la vidéo est terminée
            videoPlayer.addEventListener('ended', function() {
                if (isAutoplayEnabled && currentVideoIndex < videoItems.length - 1) {
                    loadVideo(currentVideoIndex + 1);
                }
            });

            // Gestionnaires d'événements
            prevButton.addEventListener('click', function() {
                if (currentVideoIndex > 0) {
                    loadVideo(currentVideoIndex - 1);
                }
            });

            nextButton.addEventListener('click', function() {
                if (currentVideoIndex < videoItems.length - 1) {
                    loadVideo(currentVideoIndex + 1);
                }
            });

            toggleAutoplayButton.addEventListener('click', function() {
                isAutoplayEnabled = !isAutoplayEnabled;
                this.innerHTML = isAutoplayEnabled ?
                    '<i class="fas fa-pause"></i> Désactiver lecture auto' :
                    '<i class="fas fa-play"></i> Lecture automatique';
                this.classList.toggle('btn-success', isAutoplayEnabled);
                this.classList.toggle('btn-primary', !isAutoplayEnabled);
                
                // Si on active la lecture auto et qu'une vidéo est chargée, la lire
                if (isAutoplayEnabled && videoPlayer.src) {
                    videoPlayer.play();
                }
            });

            // Clic sur un élément de la playlist
            playlistItems.forEach((item, index) => {
                item.addEventListener('click', function() {
                    loadVideo(index);
                });
            });

            // Réinitialiser la vidéo lorsque le modal est fermé
            $('#playPlaylistModal').on('hidden.bs.modal', function() {
                videoPlayer.pause();
                videoPlayer.currentTime = 0;
                isAutoplayEnabled = false;
                toggleAutoplayButton.innerHTML = '<i class="fas fa-play"></i> Lecture automatique';
                toggleAutoplayButton.classList.remove('btn-success');
                toggleAutoplayButton.classList.add('btn-primary');
            });
        });
    </script>
@endpush