@extends('admin.master')

@section('title', 'Gestion des Home Charities')

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

        .home-charity-card {
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: none;
            transition: transform 0.2s, box-shadow 0.2s;
            overflow: hidden;
            height: 100%;
        }

        .home-charity-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .home-charity-thumbnail-container {
            overflow: hidden;
            height: 180px;
            position: relative;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .home-charity-thumbnail {
            cursor: pointer;
            height: 100%;
            width: 100%;
        }

        .home-charity-thumbnail video,
        .home-charity-thumbnail img,
        .home-charity-thumbnail iframe {
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

        .home-charity-card:hover .home-charity-thumbnail video,
        .home-charity-card:hover .home-charity-thumbnail img {
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

        .home-charity-thumbnail:hover .thumbnail-overlay {
            opacity: 1;
        }

        .home-charity-card .card-title {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .home-charity-card .card-text {
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
        #home-charities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .home-charity-grid-item {
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
            #home-charities-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }

        @media (max-width: 1200px) {
            #home-charities-grid {
                grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
                gap: 1.25rem;
            }

            .home-charity-thumbnail-container {
                height: 160px;
            }
        }

        @media (max-width: 992px) {
            #home-charities-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 1rem;
            }

            .home-charity-thumbnail-container {
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
            #home-charities-grid {
                grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
                gap: 0.875rem;
            }

            .home-charity-thumbnail-container {
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
            #home-charities-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
                max-width: 400px;
                margin: 0 auto;
            }

            .home-charity-thumbnail-container {
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
            .home-charity-thumbnail-container {
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
            .home-charity-card:hover {
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
                        <h2 class="section-title">Home Charities disponibles</h2>
                        <button type="button" class="btn btn-primary" data-toggle="modal"
                            data-target="#addHomeCharityModal">
                            <i class="fas fa-plus"></i> Ajouter une charité
                        </button>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <form method="GET" action="{{ route('home-charities.index') }}" class="w-100">
                    <div class="row g-2 d-flex flex-row justify-content-end align-items-center">
                        <!-- Champ recherche -->
                        <div class="col-3">
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                                placeholder="Rechercher une charité...">
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
                                <option value="images" {{ request('type') === 'images' ? 'selected' : '' }}>Images</option>

                            </select>
                        </div>
                        <!-- Bouton recherche -->
                        <div class="col-2">
                            <button class="btn btn-primary w-100" type="submit">
                                <i class="fas fa-filter py-2"></i> Filtrer
                            </button>
                        </div>
                        <div class="col-md-2 my-1">
                            <a href="{{ route('home-charities.index') }}" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-sync py-2"></i> Réinitialiser
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Grille de charités -->
            <div class="row">
                <div class="col-12">
                    <div id="home-charities-grid">
                        @forelse($homeCharitiesData as $homeCharityData)
                            @php
                                $id = $homeCharityData->id;
                                $nom = $homeCharityData->nom;
                                $description = $homeCharityData->description;
                                $created_at = $homeCharityData->created_at;
                                $media_type = $homeCharityData->media_type;
                                $thumbnail_url = $homeCharityData->thumbnail_url;
                                $media_url = $homeCharityData->media_url;
                                $is_published = $homeCharityData->is_published ?? false;

                            @endphp

                            <div class="home-charity-grid-item">
                                <div class="card home-charity-card">
                                    <div class="home-charity-thumbnail-container">
                                        <div class="home-charity-thumbnail position-relative"
                                            data-home-charity-url="{{ $thumbnail_url }}"
                                            data-video-url="{{ $homeCharityData->video_url ?? '' }}"
                                            data-home-charity-name="{{ $nom }}" data-title="{{ $nom }}"
                                            data-description="{{ $description }}" data-media-url="{{ $media_url }}"
                                            data-media-type="{{ $media_type }}"
                                            data-has-thumbnail="{{ $homeCharityData->has_thumbnail ? 'true' : 'false' }}"
                                            data-images='@json($homeCharityData->images ?? [])'>

                                            <!-- Afficher l'image de couverture ou icône par défaut -->
                                            @if ($homeCharityData->has_thumbnail)
                                                <img src="{{ $thumbnail_url }}" alt="{{ $nom }}"
                                                    style="width: 100%; height: 100%; object-fit: cover;">
                                            @else
                                                <div class="default-thumbnail d-flex align-items-center justify-content-center"
                                                    style="width: 100%; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                                    @if ($media_type === 'audio')
                                                        <i class="fas fa-music text-white" style="font-size: 3rem;"></i>
                                                    @elseif($media_type === 'video_link')
                                                        <iframe src="{{ $thumbnail_url }}" width="100%" height="100%"
                                                            frameborder="0"></iframe>
                                                    @elseif($media_type === 'video_file')
                                                        <i class="fas fa-video text-white" style="font-size: 3rem;"></i>
                                                    @elseif($media_type === 'pdf')
                                                        <i class="fas fa-file-pdf text-white" style="font-size: 3rem;"></i>
                                                    @elseif($media_type === 'images')
                                                        <i class="fas fa-images text-white" style="font-size: 3rem;"></i>
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
                                                                    : ($media_type === 'images'
                                                                        ? 'Images'
                                                                        : $media_type)))),
                                                ) }}
                                            </span>

                                            <!-- Badge statut publication (uniquement pour les vidéos) -->
                                            @if (in_array($media_type, ['video_link', 'video_file']))
                                                <span
                                                    class="badge {{ $is_published ? 'badge-success' : 'badge-secondary' }}"
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
                                                <button class="btn btn-sm btn-outline-info view-home-charity-btn rounded"
                                                    title="Voir la charité" data-home-charity-url="{{ $thumbnail_url }}"
                                                    data-media-url="{{ $media_url }}"
                                                    data-home-charity-name="{{ $nom }}"
                                                    data-title="{{ $nom }}"
                                                    data-description="{{ $description }}"
                                                    data-media-type="{{ $media_type }}"
                                                    data-images='@json($homeCharityData->images ?? [])'>
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button
                                                    class="btn btn-sm btn-outline-primary edit-home-charity-btn mx-1 rounded"
                                                    title="Modifier la charité"
                                                    data-home-charity-id="{{ $id }}">
                                                    <i class="fas fa-edit"></i>
                                                </button>

                                                <form action="{{ route('home-charities.destroy', $id) }}" method="POST"
                                                    class="d-inline delete-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded"
                                                        title="Supprimer la charité"
                                                        onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette charité ?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>

                                                <!-- Switch Publication (uniquement pour les vidéos) -->
                                                @if (in_array($media_type, ['video_link', 'video_file']))
                                                    <button
                                                        class="btn btn-sm btn-outline-{{ $is_published ? 'success' : 'secondary' }} toggle-publish-btn mx-1 rounded"
                                                        title="{{ $is_published ? 'Dépublier' : 'Publier' }} la vidéo"
                                                        data-home-charity-id="{{ $id }}"
                                                        data-status="{{ $is_published ? 1 : 0 }}">
                                                        <i
                                                            class="fas fa-{{ $is_published ? 'toggle-on' : 'toggle-off' }}"></i>
                                                        <span
                                                            class="p-1">{{ $is_published ? 'Publié' : 'Non publié' }}</span>
                                                    </button>
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
                            <i class="fas fa-heart fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted">Aucune charité disponible</h4>
                        </div>
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Pagination si nécessaire -->
            @if ($homeCharities->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    {{ $homeCharities->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </section>

    <!-- Modals -->
    @include('admin.medias.home-charities.modals.add')
    @include('admin.medias.home-charities.modals.edit')
    @include('admin.medias.home-charities.modals.view')
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // ===== TOGGLE PUBLICATION (comme établissements) =====
            $(document).on('click', '.toggle-publish-btn', function() {
                const $btn = $(this);
                const id = $btn.data('home-charity-id');
                const isPublished = Number($btn.data('status')) === 1;
                const url = isPublished ?
                    "{{ url('home-charities') }}/" + id + "/unpublish" :
                    "{{ url('home-charities') }}/" + id + "/publish";

                $.post(url, {
                        _token: '{{ csrf_token() }}'
                    })
                    .done(function() {
                        // Rafraîchir pour refléter l'état (simple et robuste)
                        window.location.reload();
                    })
                    .fail(function() {
                        alert('Erreur lors du changement de statut de publication');
                    });
            });
            // Accumulate multiple selections for images[]
            let addImageFilesDT = null;
            // ===== GESTION DU FORMULAIRE D'AJOUT =====
            $('input[name="media_type"]', '#addHomeCharityForm').change(function() {
                const selectedType = $(this).val();
                $('#addAudioFileSection, #addVideoFileSection, #addVideoLinkSection, #addPdfFileSection, #addImageFileSection')
                    .addClass('d-none');
                $('#addAudioFile, #addVideoFile, #addVideoLink, #addPdfFile, #addImageFiles, #addAudioImageFile, #addVideoImageFile, #addPdfImageFile, #addImageCoverFile')
                    .removeAttr('required');

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
                } else if (selectedType === 'images') {
                    $('#addImageFileSection').removeClass('d-none');
                    $('#addImageFiles, #addImageCoverFile').attr('required', 'required');
                }
            });

            $('#addAudioFile, #addVideoFile, #addPdfFile, #addImageFiles, #addImageCoverFile, #addAudioImageFile, #addVideoImageFile, #addPdfImageFile')
                .on('change', function() {
                    const isMultiple = !!$(this).attr('multiple');

                    // Special handling to ACCUMULATE selections for images[]
                    if (this.id === 'addImageFiles') {
                        const newFiles = Array.from(this.files || []);
                        if (!addImageFilesDT) {
                            addImageFilesDT = new DataTransfer();
                        }
                        newFiles.forEach(function(file) {
                            addImageFilesDT.items.add(file);
                        });
                        this.files = addImageFilesDT.files;
                    }

                    const files = Array.from(this.files || []);
                    const names = files.map(f => f.name).filter(Boolean);

                    // Déterminer le libellé par défaut selon l'input
                    const id = this.id;
                    const defaultLabel = (function() {
                        switch (id) {
                            case 'addImageFiles':
                                return 'Choisir des images';
                            case 'addImageCoverFile':
                                return 'Choisir une image de couverture';
                            case 'addAudioImageFile':
                            case 'addVideoImageFile':
                            case 'addPdfImageFile':
                                return 'Choisir une image';
                            case 'addAudioFile':
                            case 'addVideoFile':
                            case 'addPdfFile':
                            default:
                                return 'Choisir un fichier';
                        }
                    })();

                    // Mettre à jour le label
                    let labelText = defaultLabel;
                    if (isMultiple) {
                        if (names.length === 0) {
                            labelText = defaultLabel;
                        } else if (names.length === 1) {
                            labelText = names[0];
                        } else {
                            labelText = `${names.length} fichiers sélectionnés`;
                        }
                    } else {
                        labelText = names[0] || defaultLabel;
                    }
                    $(this).next('.custom-file-label').addClass('selected').html(labelText);

                    // Afficher la liste détaillée uniquement pour le champ multiple des images (#addImageFiles)
                    const $customFile = $(this).closest('.custom-file');
                    let $info = $customFile.next('.file-selected-info');
                    if (id === 'addImageFiles') {
                        if ($info.length === 0) {
                            $info = $('<div class="file-selected-info mt-1 small text-muted"></div>');
                            $customFile.after($info);
                        }
                        if (names.length > 0) {
                            // Limiter l'affichage à 5 noms et indiquer s'il y en a plus
                            const maxShow = 5;
                            const shown = names.slice(0, maxShow);
                            const extra = names.length - shown.length;
                            const list = shown.join(', ');
                            $info.html(extra > 0 ? `Sélection : ${list} et +${extra} autre(s)` :
                                `Sélection : ${list}`);
                        } else {
                            $info.empty();
                        }
                    } else {
                        // Pour les autres inputs, pas de liste détaillée, on supprime si existante
                        if ($info.length) {
                            $info.remove();
                        }
                    }
                });

            $('#addHomeCharityModal').on('hidden.bs.modal', function() {
                $('#addHomeCharityForm')[0].reset();
                $('#addAudioFile, #addVideoFile, #addPdfFile')
                    .next('.custom-file-label').html('Choisir un fichier');
                $('#addAudioImageFile, #addVideoImageFile, #addPdfImageFile')
                    .next('.custom-file-label').html('Choisir une image');
                $('#addImageFiles')
                    .next('.custom-file-label').html('Choisir des images');
                $('#addImageCoverFile')
                    .next('.custom-file-label').html('Choisir une image de couverture');
                // Nettoyer les infos de sélection détaillées
                $('.file-selected-info').empty();
                $('#addMediaTypeAudio').prop('checked', true).trigger('change');

                // Reset accumulated images selection
                addImageFilesDT = null;
            });

            // ===== GESTION DU FORMULAIRE D'ÉDITION =====
            $('input[name="media_type"]', '#editHomeCharityForm').change(function() {
                const selectedType = $(this).val();
                $('#editAudioFileSection, #editVideoFileSection, #editVideoLinkSection, #editPdfFileSection, #editImageFileSection')
                    .addClass('d-none');

                if (selectedType === 'audio') {
                    $('#editAudioFileSection').removeClass('d-none');
                } else if (selectedType === 'video_file') {
                    $('#editVideoFileSection').removeClass('d-none');
                } else if (selectedType === 'video_link') {
                    $('#editVideoLinkSection').removeClass('d-none');
                } else if (selectedType === 'pdf') {
                    $('#editPdfFileSection').removeClass('d-none');
                } else if (selectedType === 'images') {
                    $('#editImageFileSection').removeClass('d-none');
                }
            });

            // ===== GESTION DES BOUTONS D'ÉDITION =====
            $(document).on('click', '.edit-home-charity-btn', function() {
                const homeCharityId = $(this).data('home-charity-id');

                // Récupérer les données de la charité via AJAX
                $.get(`{{ url('home-charities') }}/${homeCharityId}/edit`)
                    .done(function(data) {
                        // Remplir le formulaire d'édition
                        $('#editHomeCharityNom').val(data.nom);
                        $('#editHomeCharityDescription').val(data.description);

                        // Définir l'action du formulaire
                        $('#editHomeCharityForm').attr('action',
                            `{{ url('home-charities') }}/${homeCharityId}`);

                        // Sélectionner le type de média approprié
                        if (data.media) {
                            const mediaType = data.media.type === 'link' ? 'video_link' :
                                data.media.type === 'video' ? 'video_file' :
                                data.media.type;

                            $(`#editMediaType${mediaType.charAt(0).toUpperCase() + mediaType.slice(1).replace('_', '')}`)
                                .prop('checked', true);
                            $(`#editMediaType${mediaType.charAt(0).toUpperCase() + mediaType.slice(1).replace('_', '')}Label`)
                                .addClass('active');
                            $('input[name="media_type"]', '#editHomeCharityForm').trigger('change');
                        }

                        // Afficher le modal
                        $('#editHomeCharityModal').modal('show');
                    })
                    .fail(function() {
                        alert('Erreur lors du chargement des données de la charité');
                    });
            });

            // ===== FONCTION POUR OUVRIR LE MODAL DE VISUALISATION =====
            function openHomeCharityViewModal(element) {
                const title = element.data('home-charity-name') || element.data('title');
                const description = element.data('description');
                const mediaType = element.data('media-type');
                const mediaUrl = element.data('media-url');
                const homeCharityUrl = element.data('home-charity-url');
                const images = element.data('images') || [];

                // Mettre à jour le titre et la description
                $('#homeCharityViewModalLabel').text(title);
                $('#homeCharityDescription').text(description);

                // Cacher tous les lecteurs
                $('#audioPlayerContainer, #videoPlayerContainer, #iframePlayerContainer, #pdfViewerContainer, #imageCarouselContainer')
                    .addClass('d-none');

                // Afficher le bon lecteur selon le type
                if (mediaType === 'audio') {
                    const audioPlayer = $('#modalAudioPlayer')[0];
                    audioPlayer.src = mediaUrl;
                    $('#audioPlayerContainer').removeClass('d-none');
                    $('#mediaTypeBadge').text('Audio').removeClass().addClass('badge badge-pill badge-info');

                    // Forcer l'autoplay pour l'audio
                    setTimeout(() => {
                        audioPlayer.play().catch(e => {
                            console.log('Autoplay audio bloqué par le navigateur:', e);
                        });
                    }, 500);
                } else if (mediaType === 'video_file') {
                    const videoPlayer = $('#modalVideoPlayer')[0];
                    videoPlayer.src = mediaUrl;
                    $('#videoPlayerContainer').removeClass('d-none');
                    $('#mediaTypeBadge').text('Fichier Vidéo').removeClass().addClass(
                        'badge badge-pill badge-success');

                    // Forcer l'autoplay pour la vidéo
                    setTimeout(() => {
                        videoPlayer.play().catch(e => {
                            console.log('Autoplay vidéo bloqué par le navigateur:', e);
                        });
                    }, 500);
                } else if (mediaType === 'video_link') {
                    // Pour les vidéos YouTube/Vimeo, ajouter autoplay dans l'URL
                    let autoplayUrl = homeCharityUrl;
                    if (homeCharityUrl.includes('youtube.com/embed/')) {
                        autoplayUrl += (homeCharityUrl.includes('?') ? '&' : '?') + 'autoplay=1';
                    } else if (homeCharityUrl.includes('vimeo.com/')) {
                        autoplayUrl += (homeCharityUrl.includes('?') ? '&' : '?') + 'autoplay=1';
                    }
                    $('#modalIframePlayer').attr('src', autoplayUrl);
                    $('#iframePlayerContainer').removeClass('d-none');
                    $('#mediaTypeBadge').text('Lien Vidéo').removeClass().addClass(
                        'badge badge-pill badge-primary');
                } else if (mediaType === 'pdf') {
                    $('#modalPdfViewer').attr('src', mediaUrl);
                    $('#pdfViewerContainer').removeClass('d-none');
                    $('#mediaTypeBadge').text('PDF').removeClass().addClass('badge badge-pill badge-danger');
                } else if (mediaType === 'images') {
                    // Construire le carrousel d'images
                    const carouselInner = $('#imageCarouselInner');
                    carouselInner.empty();

                    images.forEach((imageUrl, index) => {
                        const activeClass = index === 0 ? 'active' : '';
                        carouselInner.append(`
                            <div class="carousel-item ${activeClass}">
                                <img src="${imageUrl}" class="d-block w-100" alt="Image ${index + 1}" style="max-height: 400px; object-fit: contain;">
                            </div>
                        `);
                    });

                    $('#imageCarouselContainer').removeClass('d-none');
                    $('#mediaTypeBadge').text('Images').removeClass().addClass('badge badge-pill badge-warning');
                }

                // Afficher le modal
                $('#homeCharityViewModal').modal('show');
            }

            // ===== GESTION DES BOUTONS DE VISUALISATION =====
            $(document).on('click', '.view-home-charity-btn', function() {
                openHomeCharityViewModal($(this));
            });

            // ===== GESTION DU CLIC SUR L'IMAGE DE COUVERTURE =====
            $(document).on('click', '.home-charity-thumbnail', function() {
                openHomeCharityViewModal($(this));
            });

            // Nettoyer les lecteurs quand le modal se ferme
            $('#homeCharityViewModal').on('hidden.bs.modal', function() {
                $('#modalAudioPlayer, #modalVideoPlayer').each(function() {
                    this.pause();
                    this.currentTime = 0;
                });
                $('#modalIframePlayer').attr('src', '');
                $('#modalPdfViewer').attr('src', '');
            });
        });
    </script>
@endpush
