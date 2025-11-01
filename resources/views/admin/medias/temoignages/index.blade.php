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
                    <button type="button" class="btn btn-primary" 
                    data-toggle="modal" 
                    data-target="#addstoreModal" data-route="{{ route('temoignages.store') }}" 
                    data-media-types="audio,video_link,video_file,images,pdf">

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
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Rechercher un témoignage...">
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
                                <div class="temoignage-thumbnail position-relative" data-temoignage-id="{{ $id }}" >
                                    <!-- Afficher l'image de couverture ou icône par défaut -->
                                    @if ($temoignageData->has_thumbnail)

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
                                        <button class="btn btn-sm btn-outline-info view-temoignage-btn rounded" data-temoignage-id="{{ $id }}" title="Voir le témoignage" data-temoignage-url="{{ $thumbnail_url }}" data-media-url="{{ $media_url }}" data-temoignage-name="{{ $nom }}" data-title="{{ $nom }}" data-description="{{ $description }}" data-media-type="{{ $media_type }}" data-images='@json($temoignageData->images ?? [])'>
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-primary edit-temoignage-btn mx-1 rounded" title="Modifier le témoignage" data-temoignage-id="{{ $id }}">
                                            <i class="fas fa-edit"></i>
                                        </button>

                                        <form action="{{ route('temoignages.destroy', $id) }}" method="POST" class="d-inline delete-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded" title="Supprimer le témoignage" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce témoignage ?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>

                                        <!-- Switch Publication (uniquement pour les vidéos) -->
                                        @if (in_array($media_type, ['video_link', 'video_file']))
                                        <button class="btn btn-sm btn-outline-{{ $is_published ? 'success' : 'secondary' }} toggle-publish-btn mx-1 rounded" title="{{ $is_published ? 'Dépublier' : 'Publier' }} la vidéo" data-temoignage-id="{{ $id }}" data-status="{{ $is_published ? 1 : 0 }}">
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

@endsection

@push('scripts')

<script>
    $(document).ready(function() {
        // ===== TOGGLE PUBLICATION (comme établissements) =====
        $(document).on('click', '.toggle-publish-btn', function() {
            const $btn = $(this);
            const id = $btn.data('temoignage-id');
            const isPublished = Number($btn.data('status')) === 1;
            const url = isPublished ?
                "{{ url('temoignages') }}/" + id + "/unpublish" :
                "{{ url('temoignages') }}/" + id + "/publish";

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


        $(document).on('click', '.view-temoignage-btn , .temoignage-thumbnail', function() {

            handleMediaView($(this), "{{ route('temoignage.voir', ':id')  }}");
        });

        $(document).on('click', '.edit-temoignage-btn', function() {
            handleEditMedia(
                $(this)
                , "{{ route('temoignages.edit', ':id') }}"
                , "{{ route('temoignages.update', ':id') }}"
                , '#editModal'
            );
        });

    });

</script>
@endpush
