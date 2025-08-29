@extends('admin.master')

@section('title', 'Gestion des Vidéos')

@push('styles')
    <style>
        .btn-group-toggle .btn {
            border-radius: 5px;
        }

        .btn-group-toggle .btn.active {
            background-color: #4e73df;
            color: white;
            border-color: #4e73df;
        }

        #videoFileSection,
        #videoLinkSection {
            transition: opacity 0.3s ease;
        }

        .video-card {
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: none;
            transition: transform 0.2s, box-shadow 0.2s;
            overflow: hidden;
            height: 100%;
        }

        .video-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .video-thumbnail-container {
            overflow: hidden;
            height: 180px;
            position: relative;
        }

        .video-thumbnail {
            cursor: pointer;
            height: 100%;
            width: 100%;
        }

        .video-thumbnail video,
        .video-thumbnail img {
            object-fit: cover;
            height: 100%;
            width: 100%;
            transition: transform 0.3s;
        }

        .video-card:hover .video-thumbnail video,
        .video-card:hover .video-thumbnail img {
            transform: scale(1.05);
        }

        .thumbnail-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
            cursor: pointer;
        }

        .thumbnail-overlay i {
            font-size: 3rem;
            color: white;
        }

        .video-thumbnail:hover .thumbnail-overlay {
            opacity: 1;
        }

        .video-card .card-title {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .video-card .card-text {
            height: 40px;
            overflow: hidden;
            margin-bottom: 1rem;
        }

        .btn-group .btn {
            border-radius: 5px;
            margin-left: 2px;
            padding: 0.25rem 0.5rem;
        }

        .empty-state {
            padding: 3rem 1rem;
        }

        /* Styles pour la grille responsive */
        #videos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .video-grid-item {
            width: 100%;
        }

        /* Personnalisation du file input */
        .custom-file-label::after {
            content: "Parcourir";
        }

        /* Responsive improvements */
        @media (max-width: 1400px) {
            #videos-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }

        @media (max-width: 1200px) {
            #videos-grid {
                grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
                gap: 1.25rem;
            }

            .video-thumbnail-container {
                height: 160px;
            }
        }

        @media (max-width: 992px) {
            #videos-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 1rem;
            }

            .video-thumbnail-container {
                height: 140px;
            }

            .section-title {
                font-size: 1.35rem;
            }

            .card-title {
                font-size: 1rem;
            }
        }

        @media (max-width: 768px) {
            #videos-grid {
                grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
                gap: 0.875rem;
            }

            .video-thumbnail-container {
                height: 120px;
            }

            .section-title {
                font-size: 1.25rem;
            }

            .btn-group .btn {
                padding: 0.2rem 0.4rem;
                font-size: 0.8rem;
            }
        }

        @media (max-width: 576px) {
            #videos-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
                max-width: 400px;
                margin: 0 auto;
            }

            .video-thumbnail-container {
                height: 180px;
            }

            .section-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .section-title {
                font-size: 1.4rem;
            }

            .modal-dialog {
                margin: 0.5rem;
            }

            .btn-group {
                width: 100%;
                justify-content: center;
                margin-top: 0.5rem;
            }

            .btn-group .btn {
                margin: 0 2px;
                flex: 1;
                max-width: 45px;
            }
        }

        @media (max-width: 400px) {
            .video-thumbnail-container {
                height: 150px;
            }

            .modal-dialog {
                margin: 0.25rem;
            }

            .btn {
                padding: 0.375rem 0.75rem;
                font-size: 0.875rem;
            }
        }

        /* Mobile first improvements */
        .modal-header .close {
            padding: 0.5rem;
            margin: -0.5rem -0.5rem -0.5rem auto;
        }

        /* Touch device improvements */
        @media (hover: none) {
            .video-card:hover {
                transform: none;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            }

            .thumbnail-overlay {
                opacity: 0.7;
            }

            .btn-group .btn {
                padding: 0.4rem 0.6rem;
            }
        }

        /* High density screens */
        @media (min-resolution: 2dppx) {
            .thumbnail-overlay i {
                font-size: 2.5rem;
            }
        }
    </style>
@endpush

@section('content')
    <section class="section" style="margin-top: -25px;">
        <div class="section-body">
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible show fade">
                    <div class="alert-body">
                        <button class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <div class="row ">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center section-header">
                        <h2 class="section-title">Vidéos disponibles</h2>
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addVideoModal">
                            <i class="fas fa-plus"></i> Ajouter une vidéo
                        </button>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <form method="GET" action="{{ route('videos.index') }}" class="w-100">
                    <div class="row g-2 d-flex flex-row justify-content-end align-items-center">
                        <!-- Champ recherche -->
                        <div class="col-md-3 my-1">
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                                placeholder="Rechercher une vidéo...">
                        </div>

                        <!-- Filtre par type -->
                        <div class="col-md-3 my-1">
                            <select name="type" class="form-control">
                                <option value="">Tous</option>
                                <option value="file" {{ request('type') === 'file' ? 'selected' : '' }}>Fichiers</option>
                                <option value="link" {{ request('type') === 'link' ? 'selected' : '' }}>Liens</option>
                            </select>
                        </div> <!-- Bouton recherche -->
                        <div class="col-md-2 my-1">
                            <button class="btn btn-primary w-100" type="submit">
                                <i class="fas fa-filter py-2"></i> Filtrer
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Grille de vidéos -->
            <div class="row">
                <div class="col-12">
                    <div id="videos-grid">
                        @forelse($videos as $video)
                            @php
                                $isLink = $video->media && $video->media->type === 'link';

                                if ($isLink) {
                                    // Récupération de l’URL brute
                                    $rawUrl = $video->media->url_fichier;

                                    // Conversion d’un lien watch en embed
                                    if (Str::contains($rawUrl, 'youtube.com/watch?v=')) {
                                        $videoId = explode('v=', parse_url($rawUrl, PHP_URL_QUERY))[1] ?? null;
                                        $thumbnailUrl = $videoId ? "https://www.youtube.com/embed/$videoId" : $rawUrl;
                                    } elseif (Str::contains($rawUrl, 'youtu.be/')) {
                                        $videoId = basename(parse_url($rawUrl, PHP_URL_PATH));
                                        $thumbnailUrl = "https://www.youtube.com/embed/$videoId";
                                    } else {
                                        // Dans le cas où c’est déjà un embed ou un autre type de lien
                                        $thumbnailUrl = $rawUrl;
                                    }
                                } else {
                                    $thumbnailUrl = asset('storage/' . $video->media->url_fichier);
                                }
                            @endphp


                            <div class="video-grid-item">
                                <div class="card video-card">
                                    <!-- Miniature -->
                                    <div class="video-thumbnail-container">
                                        <div class="video-thumbnail position-relative" data-video-url="{{ $thumbnailUrl }}"
                                            data-video-name="{{ $video->nom }}"
                                            data-is-link="{{ $isLink ? 'true' : 'false' }}">

                                            @if ($isLink)
                                                <iframe src="{{ $thumbnailUrl }}" frameborder="0" allowfullscreen></iframe>
                                            @else
                                                <video src="{{ $thumbnailUrl }}" controls></video>
                                            @endif

                                            <div class="thumbnail-overlay">
                                                <i class="fas fa-play-circle"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Corps de la carte -->
                                    <div class="card-body">
                                        <h5 class="card-title" title="{{ $video->nom }}">
                                            {{ Str::limit($video->nom, 25) }}
                                        </h5>

                                        <p class="card-text text-muted small" title="{{ $video->description }}">
                                            {{ Str::limit($video->description, 30) }}
                                        </p>

                                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                                            <small class="text-muted mb-1">
                                                {{ $video->created_at->format('d/m/Y') }}
                                            </small>

                                            <div class="btn-group">
                                                <!-- Bouton Voir -->
                                                <button class="btn btn-sm btn-outline-info view-video-btn rounded"
                                                    data-video-url="{{ $thumbnailUrl }}"
                                                    data-video-name="{{ $video->nom }}" data-title="{{ $video->nom }}"
                                                    data-description="{{ $video->description }}"
                                                    data-is-link="{{ $isLink }}">
                                                    <i class="fas fa-eye"></i>
                                                </button>

                                                <!-- Bouton Modifier -->
                                                <button class="btn btn-sm btn-outline-primary edit-video-btn mx-1 rounded"
                                                    data-video-id="{{ $video->id }}">
                                                    <i class="fas fa-edit"></i>
                                                </button>

                                                <!-- Bouton Supprimer -->
                                                <form action="{{ route('videos.destroy', $video->id) }}" method="POST"
                                                    class="d-inline delete-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded"
                                                        onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette vidéo ?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                    </div>
                    <div class="col-12 text-center py-5">
                        <div class="empty-state">
                            <i class="fas fa-video-slash fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted">Aucune vidéo disponible</h4>
                            <p class="text-muted">Commencez par ajouter votre première vidéo</p>
                        </div>
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Pagination si nécessaire -->
            @if ($videos->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    {{ $videos->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </section>

    <!-- Modals (à garder tels quels) -->
    @include('admin.medias.videos.modals.add')
    @include('admin.medias.videos.modals.edit')
    @include('admin.medias.videos.modals.view')

@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // ===== GESTION DU FORMULAIRE D'AJOUT =====

            // Basculer entre fichier et lien pour l'ajout
            $('input[name="video_type"]', '#addVideoForm').change(function() {
                if ($(this).val() === 'file') {
                    $('#addVideoFileSection').removeClass('d-none');
                    $('#addVideoLinkSection').addClass('d-none');
                    $('#addVideoFichier').attr('required', 'required');
                    $('#addVideoLink').removeAttr('required');
                } else {
                    $('#addVideoFileSection').addClass('d-none');
                    $('#addVideoLinkSection').removeClass('d-none');
                    $('#addVideoFichier').removeAttr('required');
                    $('#addVideoLink').attr('required', 'required');
                }
            });

            // Gestion du fichier sélectionné pour l'ajout
            $('#addVideoFichier').on('change', function() {
                let fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').addClass("selected").html(fileName);
            });

            // Réinitialiser le formulaire d'ajout à la fermeture
            $('#addVideoModal').on('hidden.bs.modal', function() {
                $('#addVideoForm')[0].reset();
                $('#addVideoFichier').next('.custom-file-label').html('Choisir un fichier');
                $('#addVideoTypeFile').prop('checked', true).trigger('change');
            });

            // ===== GESTION DU FORMULAIRE D'ÉDITION =====

            // Basculer entre fichier et lien pour l'édition
            $('input[name="video_type"]', '#editVideoForm').change(function() {
                if ($(this).val() === 'file') {
                    $('#editVideoFileSection').removeClass('d-none');
                    $('#editVideoLinkSection').addClass('d-none');
                    $('#editVideoLink').removeAttr('required');
                } else {
                    $('#editVideoFileSection').addClass('d-none');
                    $('#editVideoLinkSection').removeClass('d-none');
                    $('#editVideoLink').attr('required', 'required');
                }
            });

            // Gestion du fichier sélectionné pour l'édition
            $('#editVideoFichier').on('change', function() {
                let fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').addClass("selected").html(fileName ||
                    'Choisir un nouveau fichier');
            });

            // Ouvrir le modal d'édition et charger les données
            $(document).on('click', '.edit-video-btn', function() {
                const videoId = $(this).data('video-id');

                // Charger les données de la vidéo via AJAX
                $.ajax({
                    url: "{{ route('videos.edit', ':id') }}".replace(':id', videoId),
                    method: 'GET',
                    success: function(data) {
                        // Remplir les champs
                        $('#editVideoNom').val(data.nom);
                        $('#editVideoDescription').val(data.description);

                        // Configurer l'action du formulaire
                        $('#editVideoForm').attr('action',
                            "{{ route('videos.update', ':id') }}".replace(':id', videoId));

                        // Déterminer le type et configurer les sections
                        if (data.media) {
                            if (data.media.type === 'link') {
                                // C'est un lien
                                $('#editVideoTypeLink').prop('checked', true);
                                $('#editVideoTypeLinkLabel').addClass('active');
                                $('#editVideoTypeFileLabel').removeClass('active');
                                $('#editVideoTypeLink').trigger('change');

                                $('#editVideoLink').val(data.media.url_fichier);
                                $('#editCurrentLinkValue').text(data.media.url_fichier);
                                $('#editViewCurrentLink').attr('href', data.media.url_fichier);
                                $('#editCurrentLink').show();
                                $('#editCurrentVideo').hide();
                            } else {
                                // C'est un fichier
                                $('#editVideoTypeFile').prop('checked', true);
                                $('#editVideoTypeFileLabel').addClass('active');
                                $('#editVideoTypeLinkLabel').removeClass('active');
                                $('#editVideoTypeFile').trigger('change');

                                const fileName = data.media.url_fichier.split('/').pop();
                                $('#editCurrentVideoName').text(fileName);
                                $('#editViewCurrentVideo').data('video-url', '/storage/' + data
                                    .media.url_fichier);
                                $('#editCurrentVideo').show();
                                $('#editCurrentLink').hide();
                            }
                        }

                        // Ouvrir le modal
                        $('#editVideoModal').modal('show');
                    },
                    error: function() {
                        alert('Erreur lors du chargement des données de la vidéo');
                    }
                });
            });

            $(document).on('click', '#editViewCurrentVideo', function() {
                const videoUrl = $(this).data('video-url');
                $('#modalVideoPlayer').attr('src', videoUrl);
                $('#videoViewModal').modal('show');
            });


            // ===== GESTION COMMUNE =====

            // Visualisation des vidéos dans la grille
            $(document).on('click', '.view-video-btn, .video-thumbnail', function() {
                const videoUrl = $(this).data('video-url');
                const videoName = $(this).data('video-name');
                const isLink = $(this).data('is-link') === 'true' || $(this).data('is-link') === true;
                const videoDescription = $(this).closest('.video-card').find('.card-text').attr('title') ||
                    '';

                // Masquer les deux lecteurs initialement
                $('#modalVideoPlayer').addClass('d-none').attr('src', '');
                $('#modalIframePlayer').addClass('d-none').attr('src', '');

                if (isLink) {
                    // C'est un lien externe
                    let embedUrl = videoUrl;

                    // Afficher l'iframe
                    $('#modalIframePlayer')
                        .attr('src', embedUrl)
                        .removeClass('d-none');

                } else {
                    // C'est un fichier local
                    $('#modalVideoPlayer')
                        .attr('src', videoUrl)
                        .removeClass('d-none');
                }

                // Mettre à jour les informations de la vidéo
                $('#videoTitle').text(videoName);
                $('#videoDescription').text(videoDescription);

                // Afficher le modal
                $('#videoViewModal').modal('show');
            });

            // Nettoyage quand le modal se ferme
            $('#videoViewModal').on('hidden.bs.modal', function() {
                // Arrêter et réinitialiser la vidéo
                const videoPlayer = $('#modalVideoPlayer')[0];
                if (videoPlayer) {
                    videoPlayer.pause();
                    videoPlayer.currentTime = 0;
                }

                // Supprimer la source de l'iframe pour libérer la mémoire
                $('#modalIframePlayer').attr('src', '');

                // Masquer les deux lecteurs
                $('#modalVideoPlayer').addClass('d-none');
                $('#modalIframePlayer').addClass('d-none');

                // Vider les informations
                $('#videoTitle').text('');
                $('#videoDescription').text('');
            });

            // Gestion de la lecture automatique (optionnelle)
            $('#videoViewModal').on('shown.bs.modal', function() {
                // Tentative de lecture automatique pour les vidéos locales
                const videoPlayer = $('#modalVideoPlayer')[0];
                if (videoPlayer && !videoPlayer.classList.contains('d-none')) {
                    videoPlayer.play().catch(function(error) {
                        console.log('Lecture automatique bloquée:', error);
                        // La lecture automatique peut être bloquée par le navigateur
                    });
                }
            });

            // Amélioration de l'expérience mobile
            if ('ontouchstart' in document.documentElement) {
                $('.btn').addClass('touch-optimized');
                $('.modal').addClass('touch-modal');
            }
        });
    </script>
@endpush
