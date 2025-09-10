@extends('admin.master')

@section('title', 'Gestion des Émissions')

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

        #audioFileSection,
        #videoFileSection,
        #videoLinkSection,
        #pdfFileSection {
            transition: opacity 0.3s ease;
        }

        .section-title {
            font-size: 1.5rem;
            color: #4e73df;
            margin-bottom: 0;
        }

        .emission-card {
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: none;
            transition: transform 0.2s, box-shadow 0.2s;
            overflow: hidden;
            height: 100%;
        }

        .emission-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .emission-thumbnail-container {
            overflow: hidden;
            height: 180px;
            position: relative;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .emission-thumbnail {
            cursor: pointer;
            height: 100%;
            width: 100%;
        }

        .emission-thumbnail video,
        .emission-thumbnail img,
        .emission-thumbnail iframe {
            object-fit: cover;
            height: 100%;
            width: 100%;
            transition: transform 0.3s;
        }

        .audio-thumbnail,
        .pdf-thumbnail {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            font-size: 4rem;
            color: #4e73df;
        }

        .pdf-thumbnail {
            color: #e74a3b;
        }

        .emission-card:hover .emission-thumbnail video,
        .emission-card:hover .emission-thumbnail img {
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

        .emission-thumbnail:hover .thumbnail-overlay {
            opacity: 1;
        }

        .emission-card .card-title {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .emission-card .card-text {
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
        #emissions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .emission-grid-item {
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

        /* Responsive improvements */
        @media (max-width: 1400px) {
            #emissions-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }

        @media (max-width: 1200px) {
            #emissions-grid {
                grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
                gap: 1.25rem;
            }

            .emission-thumbnail-container {
                height: 160px;
            }
        }

        @media (max-width: 992px) {
            #emissions-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 1rem;
            }

            .emission-thumbnail-container {
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
            #emissions-grid {
                grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
                gap: 0.875rem;
            }

            .emission-thumbnail-container {
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
            #emissions-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
                max-width: 400px;
                margin: 0 auto;
            }

            .emission-thumbnail-container {
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
            .emission-thumbnail-container {
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
            .emission-card:hover {
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
                        <h2 class="section-title">Émissions disponibles</h2>
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addEmissionModal">
                            <i class="fas fa-plus"></i> Ajouter une émission
                        </button>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <form method="GET" action="{{ route('emissions.index') }}" class="w-100">
                    <div class="row g-2 d-flex flex-row justify-content-end align-items-center">
                        <!-- Champ recherche -->
                        <div class="col-3">
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                                placeholder="Rechercher une émission...">
                        </div>

                        <!-- Filtre par type -->
                        <div class="col-2">
                            <select name="type" class="form-control">
                                <option value="">Tous</option>
                                <option value="audio" {{ request('type') === 'audio' ? 'selected' : '' }}>Audio</option>
                                <option value="video" {{ request('type') === 'video' ? 'selected' : '' }}>Fichiers
                                    vidéo</option>
                                <option value="link" {{ request('type') === 'link' ? 'selected' : '' }}>Liens
                                    vidéo</option>
                                <option value="pdf" {{ request('type') === 'pdf' ? 'selected' : '' }}>PDF</option>
                            </select>
                        </div>

                        <!-- Bouton recherche -->
                        <div class="col-2">
                            <button class="btn btn-primary w-100" type="submit">
                                <i class="fas fa-filter py-2"></i> Filtrer
                            </button>
                        </div>
                        <div class="col-md-2 my-1">
                            <a href="{{ route('emissions.index') }}" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-sync py-2"></i> Réinitialiser
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Grille d'émissions -->
            <div class="row">
                <div class="col-12">
                    <div id="emissions-grid">
                        @forelse($emissionsData as $emission)
                            @php
                                $id = $emission->id;
                                $nom = $emission->nom;
                                $description = $emission->description;
                                $created_at = $emission->created_at;
                                $media_type = $emission->media_type;
                                $media_url = $emission->video_url;
                                $thumbnail_url = $emission->thumbnail_url;
                            @endphp

                            <div class="emission-grid-item">
                                <div class="card emission-card">
                                    <div class="emission-thumbnail-container">
                                        <div class="emission-thumbnail position-relative"
                                            data-emission-url="{{ $thumbnail_url }}"
                                            data-video-url="{{ $media_type === 'video_file' ? $media_url : '' }}"
                                            data-emission-name="{{ $nom }}" data-media-type="{{ $media_type }}"
                                            data-has-thumbnail="{{ $emission->has_thumbnail ? 'true' : 'false' }}">

                                            @if ($media_type === 'audio')
                                                <div class="audio-thumbnail"><i class="fas fa-music"></i></div>
                                            @elseif ($media_type === 'video_link')
                                                <iframe src="{{ $thumbnail_url }}" frameborder="0"
                                                    allowfullscreen></iframe>
                                            @elseif ($media_type === 'pdf')
                                                <div class="pdf-thumbnail"><i class="fas fa-file-pdf"></i></div>
                                            @elseif ($media_type === 'video_file')
                                                @if ($emission->has_thumbnail)
                                                    <img src="{{ $thumbnail_url }}" alt="{{ $nom }}"
                                                        style="width: 100%; height: 100%; object-fit: cover;">
                                                @else
                                                    <video src="{{ $media_url }}"></video>
                                                @endif
                                            @endif

                                            <div class="thumbnail-overlay">
                                                <i class="fas fa-play-circle"></i>
                                            </div>

                                            <span class="badge badge-primary media-type-badge">
                                                {{ ucfirst(
                                                    $media_type === 'audio'
                                                        ? 'Audio'
                                                        : ($media_type === 'pdf'
                                                            ? 'PDF'
                                                            : ($media_type === 'video_link'
                                                                ? 'Lien vidéo'
                                                                : ($media_type === 'video_file'
                                                                    ? 'Fichier vidéo'
                                                                    : $media_type))),
                                                ) }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="card-body">
                                        <h5 class="card-title" title="{{ $nom }}">{{ Str::limit($nom, 25) }}</h5>
                                        <p class="card-text text-muted small" title="{{ $description }}">
                                            {{ Str::limit($description, 30) }}</p>

                                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                                            <small class="text-muted mb-1">{{ $created_at->format('d/m/Y') }}</small>

                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-outline-info view-emission-btn rounded"
                                                    data-emission-url="{{ $thumbnail_url }}"
                                                    data-emission-name="{{ $nom }}"
                                                    data-title="{{ $nom }}"
                                                    data-description="{{ $description }}"
                                                    data-media-type="{{ $media_type }}">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button
                                                    class="btn btn-sm btn-outline-primary edit-emission-btn mx-1 rounded"
                                                    data-emission-id="{{ $id }}">
                                                    <i class="fas fa-edit"></i>
                                                </button>

                                                <form action="{{ route('emissions.destroy', $id) }}" method="POST"
                                                    class="d-inline delete-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded"
                                                        onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette émission ?')">
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
                            <i class="fas fa-broadcast-tower fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted">Aucune émission disponible</h4>
                        </div>
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Pagination si nécessaire -->
            @if ($emissions->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    {{ $emissions->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </section>

    <!-- Modals -->
    @include('admin.medias.emissions.modals.add')
    @include('admin.medias.emissions.modals.edit')
    @include('admin.medias.emissions.modals.view')
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // ===== GESTION DU FORMULAIRE D'AJOUT =====
            $('input[name="media_type"]', '#addEmissionForm').change(function() {
                const selectedType = $(this).val();
                $('#addAudioFileSection, #addVideoFileSection, #addVideoLinkSection, #addPdfFileSection')
                    .addClass('d-none');
                $('#addAudioFile, #addVideoFile, #addVideoLink, #addPdfFile').removeAttr('required');

                if (selectedType === 'audio') {
                    $('#addAudioFileSection').removeClass('d-none');
                    $('#addAudioFile').attr('required', 'required');
                } else if (selectedType === 'video') {
                    $('#addVideoFileSection').removeClass('d-none');
                    $('#addVideoFile').attr('required', 'required');
                } else if (selectedType === 'link') {
                    $('#addVideoLinkSection').removeClass('d-none');
                    $('#addVideoLink').attr('required', 'required');
                } else if (selectedType === 'pdf') {
                    $('#addPdfFileSection').removeClass('d-none');
                    $('#addPdfFile').attr('required', 'required');
                }
            });

            $('#addAudioFile, #addVideoFile, #addPdfFile, #addEmissionThumbnail').on('change', function() {
                let fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').addClass("selected").html(fileName);
            });

            $('#addEmissionModal').on('hidden.bs.modal', function() {
                $('#addEmissionForm')[0].reset();
                $('#addAudioFile, #addVideoFile, #addPdfFile').next('.custom-file-label').html(
                    'Choisir un fichier');
                $('#addEmissionThumbnail').next('.custom-file-label').html('Choisir une image');
                $('#addMediaTypeAudio').prop('checked', true).trigger('change');
            });

            // ===== GESTION DU FORMULAIRE D'ÉDITION =====
            $('input[name="media_type"]', '#editEmissionForm').change(function() {
                const selectedType = $(this).val();
                $('#editAudioFileSection, #editVideoFileSection, #editVideoLinkSection, #editPdfFileSection')
                    .addClass('d-none');
                $('#editAudioFile, #editVideoFile, #editVideoLink, #editPdfFile').removeAttr('required');

                if (selectedType === 'audio') {
                    $('#editAudioFileSection').removeClass('d-none');
                } else if (selectedType === 'video') {
                    $('#editVideoFileSection').removeClass('d-none');
                } else if (selectedType === 'link') {
                    $('#editVideoLinkSection').removeClass('d-none');
                    $('#editVideoLink').attr('required', 'required');
                } else if (selectedType === 'pdf') {
                    $('#editPdfFileSection').removeClass('d-none');
                }
            });

            $('#editAudioFile, #editVideoFile, #editPdfFile, #editEmissionThumbnail').on('change', function() {
                let fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').addClass("selected").html(fileName ||
                    'Choisir un nouveau fichier');
            });

            $(document).on('click', '.edit-emission-btn', function() {
                const emissionId = $(this).data('emission-id');
                $.ajax({
                    url: "{{ route('emissions.edit', ':id') }}".replace(':id', emissionId),
                    method: 'GET',
                    success: function(data) {
                        $('#editEmissionNom').val(data.nom);
                        $('#editEmissionDescription').val(data.description);
                        $('#editEmissionForm').attr('action',
                            "{{ route('emissions.update', ':id') }}".replace(':id',
                                emissionId));

                        if (data.media) {
                            let mediaType = data.media.type;
                            if (mediaType === 'audio') {
                                $('#editMediaTypeAudio').prop('checked', true).trigger(
                                    'change');
                                $('#editCurrentAudioName').text(data.media.url_fichier.split(
                                    '/').pop());
                                $('#editCurrentAudio').show();
                                $('#editCurrentVideo, #editCurrentLink, #editCurrentPdf')
                                    .hide();
                            } else if (mediaType === 'video') {
                                $('#editMediaTypeVideo').prop('checked', true).trigger(
                                    'change');
                                $('#editCurrentVideoName').text(data.media.url_fichier.split(
                                    '/').pop());
                                $('#editCurrentVideo').show();
                                $('#editCurrentAudio, #editCurrentLink, #editCurrentPdf')
                                    .hide();

                                // Gestion de l'image de couverture
                                if (data.media.thumbnail) {
                                    const thumbnailName = data.media.thumbnail.split('/').pop();
                                    $('#editCurrentThumbnailName').text(thumbnailName);
                                    $('#editCurrentThumbnailPreview').attr('src', '/storage/' +
                                        data.media.thumbnail).show();
                                    $('#editCurrentThumbnail').show();
                                } else {
                                    $('#editCurrentThumbnail').hide();
                                }
                            } else if (mediaType === 'link') {
                                $('#editMediaTypeLink').prop('checked', true).trigger(
                                    'change');
                                $('#editVideoLink').val(data.media.url_fichier);
                                $('#editCurrentLinkValue').text(data.media.url_fichier);
                                $('#editViewCurrentLink').attr('href', data.media.url_fichier);
                                $('#editCurrentLink').show();
                                $('#editCurrentAudio, #editCurrentVideo, #editCurrentPdf')
                                    .hide();
                            } else if (mediaType === 'pdf') {
                                $('#editMediaTypePdf').prop('checked', true).trigger(
                                    'change');
                                $('#editCurrentPdfName').text(data.media.url_fichier.split(
                                    '/').pop());
                                $('#editCurrentPdf').show();
                                $('#editCurrentAudio, #editCurrentVideo, #editCurrentLink')
                                    .hide();
                            }
                        }
                        $('#editEmissionModal').modal('show');
                    },
                    error: function() {
                        alert('Erreur lors du chargement des données de l\'émission');
                    }
                });
            });

            // ===== VISUALISATION DES ÉMISSIONS =====
            $(document).on('click', '.view-emission-btn, .emission-thumbnail', function() {
                const emissionUrl = $(this).data('emission-url');
                const emissionName = $(this).data('emission-name');
                const mediaType = $(this).data('media-type');
                const emissionDescription = $(this).closest('.emission-card').find('.card-text').attr(
                    'title') || '';

                // Masquer tous les lecteurs et réinitialiser
                $('#audioPlayerContainer, #videoPlayerContainer, #iframePlayerContainer, #pdfViewerContainer')
                    .addClass(
                        'd-none');
                $('#modalAudioPlayer').attr('src', '').get(0).load();
                $('#modalVideoPlayer').attr('src', '').get(0).load();
                $('#modalIframePlayer').attr('src', '');
                $('#modalPdfViewer').attr('src', '');

                if (mediaType === 'audio') {
                    $('#modalAudioPlayer').attr('src', emissionUrl).get(0).load();
                    $('#audioPlayerContainer').removeClass('d-none');
                    $('#mediaTypeBadge').text('Audio').removeClass('d-none');
                } else if (mediaType === 'video_link') {
                    $('#modalIframePlayer').attr('src', emissionUrl);
                    $('#iframePlayerContainer').removeClass('d-none');
                    $('#mediaTypeBadge').text('Vidéo en ligne').removeClass('d-none');
                } else if (mediaType === 'video_file') {
                    // Utiliser l'URL de la vidéo pour la lecture
                    const videoUrl = $(this).data('video-url') || emissionUrl;
                    $('#modalVideoPlayer').attr('src', videoUrl).get(0).load();
                    $('#videoPlayerContainer').removeClass('d-none');
                    $('#mediaTypeBadge').text('Vidéo locale').removeClass('d-none');
                } else if (mediaType === 'pdf') {
                    $('#modalPdfViewer').attr('src', emissionUrl);
                    $('#pdfViewerContainer').removeClass('d-none');
                    $('#mediaTypeBadge').text('PDF').removeClass('d-none');
                }

                $('#emissionTitle').text(emissionName);
                $('#emissionDescription').text(emissionDescription);
                $('#emissionViewModal').modal('show');
            });

            // ===== NETTOYAGE DU MODAL =====
            $('#emissionViewModal').on('hidden.bs.modal', function() {
                // Arrêter tous les médias
                $('#modalAudioPlayer').get(0).pause();
                $('#modalVideoPlayer').get(0).pause();

                // Réinitialiser les sources
                $('#modalAudioPlayer').attr('src', '');
                $('#modalVideoPlayer').attr('src', '');
                $('#modalIframePlayer').attr('src', '');
                $('#modalPdfViewer').attr('src', '');

                // Masquer tous les lecteurs
                $('#audioPlayerContainer, #videoPlayerContainer, #iframePlayerContainer, #pdfViewerContainer')
                    .addClass(
                        'd-none');

                // Vider les informations
                $('#emissionTitle, #emissionDescription, #mediaTypeBadge').text('');
            });

            // ===== LECTURE AUTOMATIQUE =====
            $('#emissionViewModal').on('shown.bs.modal', function() {
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
