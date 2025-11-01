@extends('admin.master')

@section('title', 'Gestion des Prières')

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

    .priere-card {
        border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        border: none;
        transition: transform 0.2s, box-shadow 0.2s;
        overflow: hidden;
        height: 100%;
    }

    .priere-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    .priere-thumbnail-container {
        overflow: hidden;
        height: 180px;
        position: relative;
        background-color: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .priere-thumbnail {
        cursor: pointer;
        height: 100%;
        width: 100%;
    }

    .priere-thumbnail video,
    .priere-thumbnail img,
    .priere-thumbnail iframe {
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

    .priere-card:hover .priere-thumbnail video,
    .priere-card:hover .priere-thumbnail img {
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

    .priere-thumbnail:hover .thumbnail-overlay {
        opacity: 1;
    }

    .priere-card .card-title {
        font-size: 1.1rem;
        margin-bottom: 0.5rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .priere-card .card-text {
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
    #prieres-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1.5rem;
    }

    .priere-grid-item {
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
        #prieres-grid {
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        }
    }

    @media (max-width: 1200px) {
        #prieres-grid {
            grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
            gap: 1.25rem;
        }

        .priere-thumbnail-container {
            height: 160px;
        }
    }

    @media (max-width: 992px) {
        #prieres-grid {
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }

        .priere-thumbnail-container {
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
        #prieres-grid {
            grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
            gap: 0.875rem;
        }

        .priere-thumbnail-container {
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
        #prieres-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
            max-width: 400px;
            margin: 0 auto;
        }

        .priere-thumbnail-container {
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
        .priere-thumbnail-container {
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
        .priere-card:hover {
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
                    <h2 class="section-title">Prières disponibles</h2>
                    <button type="button" class="btn btn-primary" data-toggle="modal" 
                    data-target="#addstoreModal" data-route="{{ route('prieres.store') }}"
                     data-media-types="audio,video_link,video_file,images,pdf">
                        <i class="fas fa-plus"></i> Ajouter une prière
                    </button>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <form method="GET" action="{{ route('prieres.index') }}" class="w-100">
                <div class="row g-2 d-flex flex-row justify-content-end align-items-center">
                    <!-- Champ recherche -->
                    <div class="col-3">
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Rechercher une prière...">
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
                        <a href="{{ route('prieres.index') }}" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-sync py-2"></i> Réinitialiser
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Grille de prières -->
        <div class="row">
            <div class="col-12">
                <div id="prieres-grid">
                    @forelse($prieresData as $priereData)
                    @php
                    $id = $priereData->id;
                    $nom = $priereData->nom;
                    $description = $priereData->description;
                    $created_at = $priereData->created_at;
                    $media_type = $priereData->media_type;
                    $thumbnail_url = $priereData->thumbnail_url;
                    $media_url = $priereData->media_url;
                    $is_published = $priereData->is_published ?? false;

                    @endphp

                    <div class="priere-grid-item">
                        <div class="card priere-card">
                            <div class="priere-thumbnail-container">
                                <div class="priere-thumbnail position-relative" data-priere-id="{{ $id }}">

                                    <!-- Afficher l'image de couverture ou icône par défaut -->
                                    @if ($priereData->has_thumbnail)
                                    <img src="{{ $thumbnail_url }}" alt="{{ $nom }}" style="width: 100%; height: 100%; object-fit: cover;">
                                    @else
                                    <div class="default-thumbnail d-flex align-items-center justify-content-center" style="width: 100%; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                        @if ($media_type === 'audio')
                                        <i class="fas fa-music text-white" style="font-size: 3rem;"></i>
                                        @elseif($media_type === 'video_link')
                                        <iframe src="{{ $thumbnail_url }}" width="100%" height="100%" frameborder="0"></iframe>
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
                                                                    : ($media_type === 'images' ? 'Images' : $media_type)))),
                                                )}}
                                    </span>

                                    <!-- Badge statut publication (uniquement pour les vidéos) -->
                                    @if (in_array($media_type, ['video_link', 'video_file']))
                                    <span class="badge {{ $is_published ? 'badge-success' : 'badge-secondary' }}" style="position: absolute; top: 10px; left: 10px; z-index: 10;">
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
                                        <button class="btn btn-sm btn-outline-info view-priere-btn rounded" title="Voir la prière" data-priere-id="{{ $id }}">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-primary edit-priere-btn mx-1 rounded" title="Modifier la prière" data-priere-id="{{ $id }}">
                                            <i class="fas fa-edit"></i>
                                        </button>

                                        <form action="{{ route('prieres.destroy', $id) }}" method="POST" class="d-inline delete-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded" title="Supprimer la prière" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette prière ?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>

                                        <!-- Switch Publication (uniquement pour les vidéos) -->
                                        @if (in_array($media_type, ['video_link', 'video_file']))
                                        <button class="btn btn-sm btn-outline-{{ $is_published ? 'success' : 'secondary' }} toggle-publish-btn mx-1 rounded" title="{{ $is_published ? 'Dépublier' : 'Publier' }} la vidéo" data-priere-id="{{ $id }}" data-status="{{ $is_published ? 1 : 0 }}">
                                            <i class="fas fa-{{ $is_published ? 'toggle-on' : 'toggle-off' }}"></i>
                                            <span class="p-1">{{ $is_published ? 'Publié' : 'Non publié' }}</span>
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
                        <i class="fas fa-comments fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">Aucune prière disponible</h4>
                    </div>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Pagination si nécessaire -->
        @if ($prieres->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $prieres->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</section>

<!-- Modals -->

@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // ===== TOGGLE PUBLICATION (comme établissements) =====
        $(document).on('click', '.toggle-publish-btn', function() {
            const $btn = $(this);
            const id = $btn.data('priere-id');
            const isPublished = Number($btn.data('status')) === 1;
            const url = isPublished ?
                "{{ url('prieres') }}/" + id + "/unpublish" :
                "{{ url('prieres') }}/" + id + "/publish";

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

        $(document).on('click', '.view-priere-btn , .priere-thumbnail', function() {

            handleMediaView($(this), "{{ route('prieres.voir', ':id')  }}");
        });

        $(document).on('click', '.edit-priere-btn', function() {
            handleEditMedia(
                $(this)
                , "{{ route('prieres.edit', ':id') }}"
                , "{{ route('prieres.update', ':id') }}"
                , '#editModal'
            );
        });

    });

</script>
@endpush
