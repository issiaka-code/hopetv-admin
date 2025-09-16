@extends('admin.master')

@section('title', 'Gestion des Témoignages')

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

        .temoignage-card {
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: none;
            transition: transform 0.2s, box-shadow 0.2s;
            overflow: hidden;
            height: 100%;
        }

        .temoignage-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .temoignage-thumbnail-container {
            overflow: hidden;
            height: 180px;
            position: relative;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .temoignage-thumbnail {
            cursor: pointer;
            height: 100%;
            width: 100%;
        }

        .temoignage-thumbnail video,
        .temoignage-thumbnail img,
        .temoignage-thumbnail iframe {
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

        .temoignage-card:hover .temoignage-thumbnail video,
        .temoignage-card:hover .temoignage-thumbnail img {
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

        .temoignage-thumbnail:hover .thumbnail-overlay {
            opacity: 1;
        }

        .temoignage-card .card-title {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .temoignage-card .card-text {
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
        #temoignages-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .temoignage-grid-item {
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
            #temoignages-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }

        @media (max-width: 1200px) {
            #temoignages-grid {
                grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
                gap: 1.25rem;
            }

            .temoignage-thumbnail-container {
                height: 160px;
            }
        }

        @media (max-width: 992px) {
            #temoignages-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 1rem;
            }

            .temoignage-thumbnail-container {
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
            #temoignages-grid {
                grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
                gap: 0.875rem;
            }

            .temoignage-thumbnail-container {
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
            #temoignages-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
                max-width: 400px;
                margin: 0 auto;
            }

            .temoignage-thumbnail-container {
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
            .temoignage-thumbnail-container {
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
            .temoignage-card:hover {
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
                        <h2 class="section-title">Témoignages disponibles</h2>
                        <button type="button" class="btn btn-primary" data-toggle="modal"
                            data-target="#addTemoignageModal">
                            <i class="fas fa-plus"></i> Ajouter un témoignage
                        </button>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <form method="GET" action="{{ route('temoignages.index') }}" class="w-100">
                    <div class="row g-2 d-flex flex-row justify-content-end align-items-center">
                        <!-- Champ recherche -->
                        <div class="col-3">
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                                placeholder="Rechercher un témoignage...">
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
                            <a href="{{ route('temoignages.index') }}" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-sync py-2"></i> Réinitialiser
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Grille de témoignages -->
            <div class="row">
                <div class="col-12">
                    <div id="temoignages-grid">
                        @forelse($temoignagesData as $temoignageData)
                            @php
                                $id = $temoignageData->id;
                                $nom = $temoignageData->nom;
                                $description = $temoignageData->description;
                                $created_at = $temoignageData->created_at;
                                $media_type = $temoignageData->media_type;
                                $thumbnail_url = $temoignageData->thumbnail_url;
                                $media_url = $temoignageData->media_url;
                                $is_published = $temoignageData->is_published ?? false;
                            @endphp

                            <div class="temoignage-grid-item">
                                <div class="card temoignage-card">
                                    <div class="temoignage-thumbnail-container">
                                        <div class="temoignage-thumbnail position-relative"
                                            data-temoignage-url="{{ $thumbnail_url }}"
                                            data-video-url="{{ $temoignageData->video_url ?? '' }}"
                                            data-temoignage-name="{{ $nom }}"
                                            data-media-url="{{ $media_url }}"
                                            data-media-type="{{ $media_type }}"
                                            data-has-thumbnail="{{ $temoignageData->has_thumbnail ? 'true' : 'false' }}">

                                            <!-- Afficher l'image de couverture ou icône par défaut -->
                                            @if ($temoignageData->has_thumbnail)
                                                <img src="{{ $thumbnail_url }}" alt="{{ $nom }}"
                                                    style="width: 100%; height: 100%; object-fit: cover;">
                                            @else
                                                <div class="default-thumbnail d-flex align-items-center justify-content-center"
                                                    style="width: 100%; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                                    @if ($media_type === 'audio')
                                                        <i class="fas fa-music text-white" style="font-size: 3rem;"></i>
                                                    @elseif($media_type === 'video_link')
                                                        <iframe src="{{ $thumbnail_url }}" width="100%"
                                                            height="100%" frameborder="0"></iframe>
                                                    @elseif($media_type === 'video_file')
                                                        <i class="fas fa-video text-white" style="font-size: 3rem;"></i>
                                                    @elseif($media_type === 'pdf')
                                                        <i class="fas fa-file-pdf text-white" style="font-size: 3rem;"></i>
                                                    @endif
                                                </div>
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

                                            <!-- Badge statut publication (uniquement pour les vidéos) -->
                                            @if(in_array($media_type, ['video_link', 'video_file']))
                                                <span class="badge {{ $is_published ? 'badge-success' : 'badge-secondary' }}" 
                                                      style="position: absolute; top: 10px; left: 10px; z-index: 10;">
                                                    {{ $is_published ? 'Publié' : 'Non publié' }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="card-body">
                                        <h5 class="card-title" title="{{ $nom }}">{{ Str::limit($nom, 25) }}</h5>
                                        <p class="card-text text-muted small" title="{{ $description }}">
                                            {{ Str::limit($description, 30) }}</p>

                                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                                            <small class="text-muted mb-1">{{ $created_at->format('d/m/Y') }}</small>

                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-outline-info view-temoignage-btn rounded" title="Voir le témoignage"
                                                    data-temoignage-url="{{ $thumbnail_url }}"
                                                    data-media-url="{{ $media_url }}"
                                                    data-temoignage-name="{{ $nom }}"
                                                    data-title="{{ $nom }}"
                                                    data-description="{{ $description }}"
                                                    data-media-type="{{ $media_type }}">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-primary edit-temoignage-btn mx-1 rounded" title="Modifier le témoignage"
                                                    data-temoignage-id="{{ $id }}">
                                                    <i class="fas fa-edit"></i>
                                                </button>

                                                <form action="{{ route('temoignages.destroy', $id) }}" method="POST"
                                                    class="d-inline delete-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded" title="Supprimer le témoignage"
                                                        onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce témoignage ?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>

                                                <!-- Boutons Publication/Dépublication (uniquement pour les vidéos) -->
                                                @if(in_array($media_type, ['video_link', 'video_file']))
                                                    @if($is_published)
                                                        <form action="{{ route('temoignages.unpublish', $id) }}" method="POST"
                                                            class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-outline-warning rounded mx-1"
                                                                title="Dépublier la vidéo">
                                                                <i class="fas fa-power-off"></i>
                                                            </button>
                                                        </form>
                                                    @else
                                                        <form action="{{ route('temoignages.publish', $id) }}" method="POST"
                                                            class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-outline-success rounded mx-1"
                                                                title="Publier la vidéo">
                                                                <i class="fas fa-power-off"></i>
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
                            <i class="fas fa-comments fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted">Aucun témoignage disponible</h4>
                        </div>
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Pagination si nécessaire -->
            @if ($temoignages->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    {{ $temoignages->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </section>

    <!-- Modals -->
    @include('admin.medias.temoignages.modals.add')
    @include('admin.medias.temoignages.modals.edit')
    @include('admin.medias.temoignages.modals.view')
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // ===== GESTION DU FORMULAIRE D'AJOUT =====
            $('input[name="media_type"]', '#addTemoignageForm').change(function() {
                const selectedType = $(this).val();
                $('#addAudioFileSection, #addVideoFileSection, #addVideoLinkSection, #addPdfFileSection')
                    .addClass('d-none');
                $('#addAudioFile, #addVideoFile, #addVideoLink, #addPdfFile, #addAudioImageFile, #addVideoImageFile, #addPdfImageFile').removeAttr('required');

                if (selectedType === 'audio') {
                    $('#addAudioFileSection').removeClass('d-none');
                    $('#addAudioFile, #addAudioImageFile').attr('required', 'required');
                } else if (selectedType === 'video_file') {
                    $('#addVideoFileSection').removeClass('d-none');
                    $('#addVideoFile, #addVideoImageFile').attr('required', 'required');
                } else if (selectedType === 'video_link') {
                    $('#addVideoLinkSection').removeClass('d-none');
                    $('#addVideoLink').attr('required', 'required');
                } else if (selectedType === 'pdf') {
                    $('#addPdfFileSection').removeClass('d-none');
                    $('#addPdfFile, #addPdfImageFile').attr('required', 'required');
                }
            });

            $('#addAudioFile, #addVideoFile, #addPdfFile, #addAudioImageFile, #addVideoImageFile, #addPdfImageFile').on('change', function() {
                let fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').addClass("selected").html(fileName);
            });

            $('#addTemoignageModal').on('hidden.bs.modal', function() {
                $('#addTemoignageForm')[0].reset();
                $('#addAudioFile, #addVideoFile, #addPdfFile').next('.custom-file-label').html(
                    'Choisir un fichier');
                $('#addAudioImageFile, #addVideoImageFile, #addPdfImageFile').next('.custom-file-label').html('Choisir une image');
                $('#addMediaTypeAudio').prop('checked', true).trigger('change');
            });

            // ===== GESTION DU FORMULAIRE D'ÉDITION =====
            $('input[name="media_type"]', '#editTemoignageForm').change(function() {
                const selectedType = $(this).val();
                $('#editAudioFileSection, #editVideoFileSection, #editVideoLinkSection, #editPdfFileSection')
                    .addClass('d-none');
                $('#editAudioFile, #editVideoFile, #editVideoLink, #editPdfFile').removeAttr('required');

                if (selectedType === 'audio') {
                    $('#editAudioFileSection').removeClass('d-none');
                } else if (selectedType === 'video_file') {
                    $('#editVideoFileSection').removeClass('d-none');
                } else if (selectedType === 'video_link') {
                    $('#editVideoLinkSection').removeClass('d-none');
                    $('#editVideoLink').attr('required', 'required');
                } else if (selectedType === 'pdf') {
                    $('#editPdfFileSection').removeClass('d-none');
                }
            });

            $('#editAudioFile, #editVideoFile, #editPdfFile, #editAudioImageFile, #editVideoImageFile, #editPdfImageFile').on('change', function() {
                let fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').addClass("selected").html(fileName ||
                    'Choisir un nouveau fichier');
            });

            $(document).on('click', '.edit-temoignage-btn', function() {
                const temoignageId = $(this).data('temoignage-id');
                $.ajax({
                    url: "{{ route('temoignages.edit', ':id') }}".replace(':id', temoignageId),
                    method: 'GET',
                    success: function(data) {
                        $('#editTemoignageNom').val(data.nom);
                        $('#editTemoignageDescription').val(data.description);
                        $('#editTemoignageForm').attr('action',
                            "{{ route('temoignages.update', ':id') }}".replace(':id',
                                temoignageId));

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
                                $('#editMediaTypeVideoFile').prop('checked', true).trigger(
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
                                    $('#editCurrentThumbnailPreview').attr('src', '/storage/' + data.media.thumbnail).show();
                                    $('#editCurrentThumbnail').show();
                                } else {
                                    $('#editCurrentThumbnail').hide();
                                }
                            } else if (mediaType === 'link') {
                                $('#editMediaTypeVideoLink').prop('checked', true).trigger(
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
                        $('#editTemoignageModal').modal('show');
                    },
                    error: function() {
                        alert('Erreur lors du chargement des données du témoignage');
                    }
                });
            });

            // ===== VISUALISATION DES TÉMOIGNAGES =====
            $(document).on('click', '.view-temoignage-btn, .temoignage-thumbnail', function() {
                const temoignageUrl = $(this).data('temoignage-url');
                const temoignageName = $(this).data('temoignage-name');
                const mediaType = $(this).data('media-type');
                const temoignageDescription = $(this).closest('.temoignage-card').find('.card-text').attr(
                    'title') || '';
                // Masquer tous les lecteurs et réinitialiser
                $('#audioPlayerContainer, #videoPlayerContainer, #iframePlayerContainer, #pdfViewerContainer')
                    .addClass(
                        'd-none');
                $('#modalAudioPlayer').attr('src', '').get(0).load();
                $('#modalVideoPlayer').attr('src', '').get(0).load();
                $('#modalIframePlayer').attr('src', '');
                $('#modalPdfViewer').attr('src', '');

                // Récupérer l'URL du média réel
                const mediaUrl = $(this).data('media-url');
                
                
                if (mediaType === 'audio') {
                    if (mediaUrl) {
                        console.log('Loading audio:', mediaUrl);
                        $('#modalAudioPlayer').attr('src', mediaUrl);
                        $('#modalAudioPlayer')[0].load();
                        $('#audioPlayerContainer').removeClass('d-none');
                        $('#mediaTypeBadge').text('Audio').removeClass('d-none');
                    } else {
                        console.log('No audio URL found');
                    }
                } else if (mediaType === 'video_link') {
                    $('#modalIframePlayer').attr('src', temoignageUrl);
                    $('#iframePlayerContainer').removeClass('d-none');
                    $('#mediaTypeBadge').text('Vidéo en ligne').removeClass('d-none');
                } else if (mediaType === 'video_file') {
                    // Utiliser l'URL de la vidéo pour la lecture
                    const videoUrl = $(this).data('video-url') || mediaUrl;
                    if (videoUrl) {
                        $('#modalVideoPlayer').attr('src', videoUrl);
                        $('#modalVideoPlayer')[0].load();
                        $('#videoPlayerContainer').removeClass('d-none');
                        $('#mediaTypeBadge').text('Vidéo locale').removeClass('d-none');
                    }
                } else if (mediaType === 'pdf') {
                    if (mediaUrl) {
                        console.log('Loading PDF:', mediaUrl);
                        // Charger le PDF avec les contrôles natifs du navigateur
                        $('#modalPdfViewer').attr('src', mediaUrl + '#view=FitH&toolbar=1&navpanes=1');
                        $('#pdfDownload').attr('href', mediaUrl);
                        $('#pdfViewerContainer').removeClass('d-none');
                        $('#mediaTypeBadge').text('PDF').removeClass('d-none');
                        
                        // Initialiser les contrôles PDF
                        initPdfControls(mediaUrl);
                    } else {
                        console.log('No PDF URL found');
                    }
                }

                $('#temoignageTitle').text(temoignageName);
                $('#temoignageDescription').text(temoignageDescription);
                $('#temoignageViewModal').modal('show');
            });

            // ===== NETTOYAGE DU MODAL =====
            $('#temoignageViewModal').on('hidden.bs.modal', function() {
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
                $('#temoignageTitle, #temoignageDescription, #mediaTypeBadge').text('');
            });

            // ===== LECTURE AUTOMATIQUE =====
            $('#temoignageViewModal').on('shown.bs.modal', function() {
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
