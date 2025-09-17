@extends('admin.master')

@section('title', 'Gestion des Podcasts')

@push('styles')
    <style>
        /* Styles CSS identiques à ceux des témoignages mais adaptés pour podcasts */
        .btn-group-toggle .btn {
            border-radius: 5px;
        }

        .btn-group-toggle .btn.active {
            background-color: #4e73df;
            color: white;
            border-color: #4e73df;
        }

        #audioFileSection,
        #videoFileSection,
        #videoLinkSection {
            transition: opacity 0.3s ease;
        }

        .section-title {
            font-size: 1.5rem;
            color: #4e73df;
            margin-bottom: 0;
        }

        .podcast-card {
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: none;
            transition: transform 0.2s, box-shadow 0.2s;
            overflow: hidden;
            height: 100%;
        }

        .podcast-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .podcast-thumbnail-container {
            overflow: hidden;
            height: 180px;
            position: relative;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .podcast-thumbnail {
            cursor: pointer;
            height: 100%;
            width: 100%;
        }

        .podcast-thumbnail video,
        .podcast-thumbnail img,
        .podcast-thumbnail iframe {
            object-fit: cover;
            height: 100%;
            width: 100%;
            transition: transform 0.3s;
        }

        .audio-thumbnail {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            font-size: 4rem;
            color: #4e73df;
        }

        .podcast-card:hover .podcast-thumbnail video,
        .podcast-card:hover .podcast-thumbnail img {
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

        .podcast-thumbnail:hover .thumbnail-overlay {
            opacity: 1;
        }

        .podcast-card .card-title {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .podcast-card .card-text {
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
        #podcasts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .podcast-grid-item {
            width: 100%;
        }

        /* Personnalisation du file input */
        .custom-file-label::after {
            content: "Parcourir";
        }

        /* Media type badge */
        .media-type-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
        }

        /* Responsive improvements - mêmes que témoignages */
        @media (max-width: 1400px) {
            #podcasts-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }

        @media (max-width: 1200px) {
            #podcasts-grid {
                grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
                gap: 1.25rem;
            }

            .podcast-thumbnail-container {
                height: 160px;
            }
        }

        @media (max-width: 992px) {
            #podcasts-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 1rem;
            }

            .podcast-thumbnail-container {
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
            #podcasts-grid {
                grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
                gap: 0.875rem;
            }

            .podcast-thumbnail-container {
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
            #podcasts-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
                max-width: 400px;
                margin: 0 auto;
            }

            .podcast-thumbnail-container {
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
            .podcast-thumbnail-container {
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
            .podcast-card:hover {
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

            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center section-header">
                        <h2 class="section-title">Podcasts disponibles</h2>
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addPodcastModal">
                            <i class="fas fa-plus"></i> Ajouter un podcast
                        </button>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <form method="GET" action="{{ route('podcasts.index') }}" class="w-100">
                    <div class="row g-2 d-flex flex-row justify-content-end align-items-center">
                        <!-- Champ recherche -->
                        <div class="col-3">
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                                placeholder="Rechercher un podcast...">
                        </div>

                        <!-- Filtre par type -->
                        <div class="col-3">
                            <select name="type" class="form-control">
                                <option value="">Tous</option>
                                <option value="audio" {{ request('type') === 'audio' ? 'selected' : '' }}>Audio</option>
                                <option value="video_file" {{ request('type') === 'video_file' ? 'selected' : '' }}>Fichiers
                                    vidéo</option>
                                <option value="video_link" {{ request('type') === 'video_link' ? 'selected' : '' }}>Liens
                                    vidéo</option>
                            </select>
                        </div>
                        <!-- Bouton recherche -->
                        <div class="col-2">
                            <button class="btn btn-primary w-100" type="submit">
                                <i class="fas fa-filter py-2"></i> Filtrer
                            </button>
                        </div>
                        <div class="col-2">
                            <a href="{{ route('podcasts.index') }}" class="btn btn-secondary w-100">
                                <i class="fas fa-sync py-2"></i> Réinitialiser
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Grille de podcasts -->
            <div class="row">
                <div class="col-12">
                    <div id="podcasts-grid">
                        @forelse($podcastsData as $podcast)
                            <div class="podcast-grid-item">
                                <div class="card podcast-card">
                                    <div class="podcast-thumbnail-container">
                                        <div class="podcast-thumbnail position-relative"
                                            data-podcast-url="{{ $podcast->thumbnail_url }}"
                                            data-media-url="{{ $podcast->media_url }}"
                                            data-podcast-name="{{ $podcast->nom }}"
                                            data-media-type="{{ $podcast->media_type }}"
                                            data-has-thumbnail="{{ $podcast->has_thumbnail ? 'true' : 'false' }}">

                                            <!-- Afficher l'image de couverture ou icône par défaut -->
                                            @if ($podcast->has_thumbnail)
                                                <img src="{{ $podcast->thumbnail_url }}" alt="{{ $podcast->nom }}"
                                                    style="width: 100%; height: 100%; object-fit: cover;">
                                            @else
                                                <div class="default-thumbnail d-flex align-items-center justify-content-center"
                                                    style="width: 100%; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                                    @if ($podcast->media_type === 'audio')
                                                        <i class="fas fa-music text-white" style="font-size: 3rem;"></i>
                                                    @elseif($podcast->media_type === 'video_link')
                                                        <iframe src="{{ $podcast->thumbnail_url }}" width="100%"
                                                            height="100%" frameborder="0"></iframe>
                                                    @elseif($podcast->media_type === 'video_file')
                                                        <i class="fas fa-video text-white" style="font-size: 3rem;"></i>
                                                    @endif
                                                </div>
                                            @endif

                                            <div class="thumbnail-overlay">
                                                <i class="fas fa-play-circle"></i>
                                            </div>

                                            <span class="badge badge-primary media-type-badge">
                                                @if ($podcast->media_type === 'audio')
                                                    Audio
                                                @elseif ($podcast->media_type === 'video_link')
                                                    Lien vidéo
                                                @elseif ($podcast->media_type === 'video_file')
                                                    Fichier vidéo
                                                @endif
                                            </span>

                                            <!-- Badge statut publication (uniquement pour les vidéos) -->
                                            @if(in_array($podcast->media_type, ['video_link', 'video_file']))
                                                <span class="badge {{ $podcast->is_published ? 'badge-success' : 'badge-secondary' }}" 
                                                      style="position: absolute; top: 10px; left: 10px; z-index: 10;">
                                                    {{ $podcast->is_published ? 'Publié' : 'Non publié' }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="card-body">
                                        <h5 class="card-title" title="{{ $podcast->nom }}">
                                            {{ Str::limit($podcast->nom, 25) }}
                                        </h5>
                                        <p class="card-text text-muted small" title="{{ $podcast->description }}">
                                            {{ Str::limit($podcast->description, 30) }}
                                        </p>

                                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                                            <small class="text-muted mb-1">
                                                {{ $podcast->created_at->format('d/m/Y') }}
                                            </small>

                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-outline-info view-podcast-btn rounded" title="Voir le podcast"
                                                    data-podcast-url="{{ $podcast->thumbnail_url }}"
                                                    data-media-url="{{ $podcast->media_url }}"
                                                    data-podcast-name="{{ $podcast->nom }}"
                                                    data-title="{{ $podcast->nom }}"
                                                    data-description="{{ $podcast->description }}"
                                                    data-media-type="{{ $podcast->media_type }}"
                                                    data-has-thumbnail="{{ $podcast->has_thumbnail ? 'true' : 'false' }}">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-primary edit-podcast-btn mx-1 rounded" title="Modifier le podcast"
                                                    data-podcast-id="{{ $podcast->id }}">
                                                    <i class="fas fa-edit"></i>
                                                </button>

                                                <form action="{{ route('podcasts.destroy', $podcast->id) }}" method="POST"
                                                    class="d-inline delete-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded" title="Supprimer le podcast"
                                                        onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce podcast ?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                                <!-- Boutons Publication/Dépublication (uniquement pour les vidéos) -->
                                                @if(in_array($podcast->media_type, ['video_link', 'video_file']))
                                                    @if($podcast->is_published)
                                                        <form action="{{ route('podcasts.unpublish', $podcast->id) }}" method="POST"
                                                            class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-outline-warning rounded mx-1"
                                                                title="Dépublier la vidéo">
                                                                <i class="fas fa-power-off"></i> Dépublier
                                                            </button>
                                                        </form>
                                                    @else
                                                        <form action="{{ route('podcasts.publish', $podcast->id) }}" method="POST"
                                                            class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-outline-success rounded mx-1"
                                                                title="Publier la vidéo">
                                                                <i class="fas fa-power-off"></i> Publier
                                                            </button>
                                                        </form>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                    </div>
                    <div class="col-12 text-center py-5">
                        <div class="empty-state">
                            <i class="fas fa-podcast fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted">Aucun podcast disponible</h4>
                        </div>
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Pagination si nécessaire -->
            @if ($podcasts->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    {{ $podcasts->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </section>

    <!-- Modals -->
    @include('admin.medias.podcasts.modals.add')
    @include('admin.medias.podcasts.modals.edit')
    @include('admin.medias.podcasts.modals.view')
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // ===== GESTION DU FORMULAIRE D'AJOUT =====
            $('input[name="media_type"]', '#addPodcastForm').change(function() {
                const selectedType = $(this).val();

                // Masquer toutes les se    ctions
                $('#addAudioFileSection, #addVideoFileSection, #addVideoLinkSection').addClass('d-none');

                // Réinitialiser les validations
                $('#addAudioFile, #addVideoFile, #addVideoLink').removeAttr('required');
                $('#addAudioThumbnail, #addVideoThumbnail').removeAttr('required');

                if (selectedType === 'audio') {
                    $('#addAudioFileSection').removeClass('d-none');
                    $('#addAudioFile').attr('required', 'required');
                    $('#addAudioThumbnail').attr('required', 'required');
                } else if (selectedType === 'video_file') {
                    $('#addVideoFileSection').removeClass('d-none');
                    $('#addVideoFile').attr('required', 'required');
                    $('#addVideoThumbnail').attr('required', 'required');
                } else if (selectedType === 'video_link') {
                    $('#addVideoLinkSection').removeClass('d-none');
                    $('#addVideoLink').attr('required', 'required');
                    // Note: addLinkThumbnail n'est pas requis pour les liens vidéo
                }
            });

            // Gestion des labels de fichiers
            $('#addAudioFile, #addVideoFile, #addAudioThumbnail, #addVideoThumbnail, #addLinkThumbnail').on(
                'change',
                function() {
                    let fileName = $(this).val().split('\\').pop();
                    $(this).next('.custom-file-label').addClass("selected").html(fileName);
                });

            // Réinitialisation du modal
            $('#addPodcastModal').on('hidden.bs.modal', function() {
                $('#addPodcastForm')[0].reset();
                $('.custom-file-label').html('Choisir un fichier');
                $('#addMediaTypeAudio').prop('checked', true).trigger('change');
            });

            // ===== GESTION DU FORMULAIRE D'ÉDITION =====
            $('input[name="media_type"]', '#editPodcastForm').change(function() {
                const selectedType = $(this).val();
                $('#editAudioFileSection, #editVideoFileSection, #editVideoLinkSection').addClass('d-none');
                $('#editAudioFile, #editVideoFile, #editVideoLink').removeAttr('required');
                $('#editAudioThumbnail, #editVideoThumbnail').removeAttr('required');

                if (selectedType === 'audio') {
                    $('#editAudioFileSection').removeClass('d-none');
                } else if (selectedType === 'video_file') {
                    $('#editVideoFileSection').removeClass('d-none');
                } else if (selectedType === 'video_link') {
                    $('#editVideoLinkSection').removeClass('d-none');
                    $('#editVideoLink').attr('required', 'required');
                }
            });

            $('#editAudioFile, #editVideoFile, #editAudioThumbnail, #editVideoThumbnail, #editLinkThumbnail').on(
                'change',
                function() {
                    let fileName = $(this).val().split('\\').pop();
                    $(this).next('.custom-file-label').addClass("selected").html(fileName ||
                        'Choisir un nouveau fichier');
                });

            // ===== ÉDITION DES PODCASTS =====
            $(document).on('click', '.edit-podcast-btn', function() {
                const podcastId = $(this).data('podcast-id');
                $.ajax({
                    url: "{{ route('podcasts.edit', ':id') }}".replace(':id', podcastId),
                    method: 'GET',
                    success: function(data) {
                        $('#editPodcastnom').val(data.nom);
                        $('#editPodcastDescription').val(data.description);
                        $('#editPodcastForm').attr('action',
                            "{{ route('podcasts.update', ':id') }}".replace(':id',
                                podcastId));

                        if (data.media) {
                            let mediaType = data.media.type;
                            if (mediaType === 'audio') {
                                $('#editMediaTypeAudio').prop('checked', true).trigger(
                                'change');
                                $('#editCurrentAudioName').text(data.media.url_fichier.split(
                                    '/').pop());
                                $('#editCurrentAudio').show();
                                $('#editCurrentVideo, #editCurrentLink').hide();

                                // Afficher l'image de couverture audio
                                if (data.media.thumbnail) {
                                    const thumbnailName = data.media.thumbnail.split('/').pop();
                                    $('#editCurrentAudioThumbnailName').text(thumbnailName);
                                    $('#editCurrentAudioThumbnailPreview').attr('src',
                                        '/storage/' + data.media.thumbnail).show();
                                    $('#editCurrentAudioThumbnail').show();
                                } else {
                                    $('#editCurrentAudioThumbnail').hide();
                                }
                            } else if (mediaType === 'video') {
                                $('#editMediaTypeVideoFile').prop('checked', true).trigger(
                                    'change');
                                $('#editCurrentVideoName').text(data.media.url_fichier.split(
                                    '/').pop());
                                $('#editCurrentVideo').show();
                                $('#editCurrentAudio, #editCurrentLink').hide();

                                // Afficher l'image de couverture vidéo
                                if (data.media.thumbnail) {
                                    const thumbnailName = data.media.thumbnail.split('/').pop();
                                    $('#editCurrentVideoThumbnailName').text(thumbnailName);
                                    $('#editCurrentVideoThumbnailPreview').attr('src',
                                        '/storage/' + data.media.thumbnail).show();
                                    $('#editCurrentVideoThumbnail').show();
                                } else {
                                    $('#editCurrentVideoThumbnail').hide();
                                }
                            } else if (mediaType === 'link') {
                                $('#editMediaTypeVideoLink').prop('checked', true).trigger(
                                    'change');
                                $('#editVideoLink').val(data.media.url_fichier);
                                $('#editCurrentLinkValue').text(data.media.url_fichier);
                                $('#editViewCurrentLink').attr('href', data.media.url_fichier);
                                $('#editCurrentLink').show();
                                $('#editCurrentAudio, #editCurrentVideo').hide();

                                // Afficher l'image de couverture pour lien (si existe)
                                if (data.media.thumbnail) {
                                    const thumbnailName = data.media.thumbnail.split('/').pop();
                                    $('#editCurrentLinkThumbnailName').text(thumbnailName);
                                    $('#editCurrentLinkThumbnailPreview').attr('src',
                                        '/storage/' + data.media.thumbnail).show();
                                    $('#editCurrentLinkThumbnail').show();
                                } else {
                                    $('#editCurrentLinkThumbnail').hide();
                                }
                            }
                        }
                        $('#editPodcastModal').modal('show');
                    },
                    error: function() {
                        alert('Erreur lors du chargement des données du podcast');
                    }
                });
            });
            // ===== VISUALISATION DES PODCASTS =====
            $(document).on('click', '.view-podcast-btn, .podcast-thumbnail', function() {
                const mediaUrl = $(this).data('media-url');
                const thumbnailUrl = $(this).data('podcast-url');
                const podcastName = $(this).data('podcast-name');
                const mediaType = $(this).data('media-type');
                const podcastDescription = $(this).closest('.podcast-card').find('.card-text').attr(
                    'title') || '';

                // Masquer tous les lecteurs et réinitialiser
                $('#audioPlayerContainer, #videoPlayerContainer, #iframePlayerContainer').addClass(
                    'd-none');
                $('#modalAudioPlayer').attr('src', '').get(0).load();
                $('#modalVideoPlayer').attr('src', '').get(0).load();
                $('#modalIframePlayer').attr('src', '');

                if (mediaType === 'audio') {
                    $('#modalAudioPlayer').attr('src', mediaUrl).get(0).load();
                    $('#audioPlayerContainer').removeClass('d-none');
                    $('#mediaTypeBadge').text('Audio').removeClass('d-none');
                } else if (mediaType === 'video_link') {
                    $('#modalIframePlayer').attr('src', thumbnailUrl);
                    $('#iframePlayerContainer').removeClass('d-none');
                    $('#mediaTypeBadge').text('Vidéo en ligne').removeClass('d-none');
                } else if (mediaType === 'video_file') {
                    $('#modalVideoPlayer').attr('src', mediaUrl).get(0).load();
                    $('#videoPlayerContainer').removeClass('d-none');
                    $('#mediaTypeBadge').text('Vidéo locale').removeClass('d-none');
                }

                $('#podcastTitle').text(podcastName);
                $('#podcastDescription').text(podcastDescription);
                $('#podcastViewModal').modal('show');
            });

            // ===== NETTOYAGE DU MODAL =====
            $('#podcastViewModal').on('hidden.bs.modal', function() {
                // Arrêter tous les médias
                $('#modalAudioPlayer').get(0).pause();
                $('#modalVideoPlayer').get(0).pause();

                // Réinitialiser les sources
                $('#modalAudioPlayer').attr('src', '');
                $('#modalVideoPlayer').attr('src', '');
                $('#modalIframePlayer').attr('src', '');

                // Masquer tous les lecteurs
                $('#audioPlayerContainer, #videoPlayerContainer, #iframePlayerContainer').addClass(
                    'd-none');

                // Vider les informations
                $('#podcastTitle, #podcastDescription, #mediaTypeBadge').text('');
            });

            // ===== TÉLÉCHARGEMENT =====
            $('#downloadPodcastBtn').on('click', function() {
                const mediaType = $('#mediaTypeBadge').text();
                let downloadUrl = '';

                if (mediaType === 'Audio') {
                    downloadUrl = $('#modalAudioPlayer').attr('src');
                } else if (mediaType === 'Vidéo locale') {
                    downloadUrl = $('#modalVideoPlayer').attr('src');
                }

                if (downloadUrl) {
                    const link = document.createElement('a');
                    link.href = downloadUrl;
                    link.download = $('#podcastTitle').text() +
                        (mediaType === 'Audio' ? '.mp3' : '.mp4');
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                } else if (mediaType === 'Vidéo en ligne') {
                    window.open($('#modalIframePlayer').attr('src'), '_blank');
                } else {
                    alert('Téléchargement non disponible');
                }
            });

            // ===== LECTURE AUTOMATIQUE =====
            $('#podcastViewModal').on('shown.bs.modal', function() {
                const audioPlayer = $('#modalAudioPlayer').get(0);
                const videoPlayer = $('#modalVideoPlayer').get(0);

                if (audioPlayer && !$('#audioPlayerContainer').hasClass('d-none')) {
                    audioPlayer.play().catch(function(error) {
                        console.log('Lecture audio automatique bloquée:', error);
                    });
                } else if (videoPlayer && !$('#videoPlayerContainer').hasClass('d-none')) {
                    videoPlayer.play().catch(function(error) {
                        console.log('Lecture vidéo automatique bloquée:', error);
                    });
                }
            });
        });
    </script>
@endpush
