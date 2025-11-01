@extends('admin.master')

@section('title', 'Gestion des émissions')

@push('styles')
<style>
    /* (tes styles restent inchangés) */

</style>
@endpush

@section('content')
<section class="section" style="margin-top: -25px;">
    <div class="section-body">

        {{-- Messages d’erreur --}}
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

        {{-- Titre + bouton --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center section-header">
                    <h2 class="section-title">Médias disponibles</h2>
                    <button type="button" class="btn btn-primary" data-toggle="modal" 
                    data-target="#addstoreModal" data-route="{{ route('emissionsitem.store') }}" 
                    data-media-types="audio,video_link,video_file,images,pdf">
                        <i class="fas fa-plus"></i> Ajouter un média
                    </button>
                </div>
            </div>
        </div>

        {{-- Filtres --}}
        <div class="row mb-4">
            <form method="GET" action="{{ route('emissions.index') }}" class="w-100">
                <div class="row g-2 d-flex flex-row justify-content-end align-items-center">
                    <div class="col-3">
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Rechercher un emission...">
                    </div>

                    <div class="col-3">
                        <select name="type" class="form-control">
                            <option value="">Tous</option>
                            <option value="audio" {{ request('type') === 'audio' ? 'selected' : '' }}>Audio</option>
                            <option value="video_file" {{ request('type') === 'video_file' ? 'selected' : '' }}>Fichiers vidéo</option>
                            <option value="video_link" {{ request('type') === 'video_link' ? 'selected' : '' }}>Liens vidéo</option>
                            <option value="pdf" {{ request('type') === 'pdf' ? 'selected' : '' }}>PDF</option>
                            <option value="images" {{ request('type') === 'images' ? 'selected' : '' }}>Images</option>
                        </select>
                    </div>

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

        {{-- Liste des éléments --}}
        <div class="row">
            <div class="col-12">
                <div id="emissions-grid">
                    @forelse($emissionItems as $item)
                    @php
                    $emissionid= $item->emission->id;
                    $id = $item->id;
                    $nom = $item->nom;
                    $description = $item->description;
                    $created_at = $item->created_at;
                    $media = $item->media;
                    $media_type = $media->type ?? '';
                    $media_url = $media->url_fichier ?? '';
                    $thumbnail_url = $media->thumbnail_url ?? '';
                    $is_published = $item->is_active ?? false;
                    @endphp

                    <div class="emission-grid-item">
                        <div class="card emission-card">
                            <div class="emission-thumbnail-container">
                                <div class="emission-thumbnail position-relative" data-emission-id="{{ $emissionid }}"  data-media-id="{{ $id }}">

                                    @if ($thumbnail_url)
                                    <img src="{{ $thumbnail_url }}" alt="{{ $nom }}" style="width: 100%; height: 100%; object-fit: cover;">
                                    @else
                                    <div class="default-thumbnail d-flex align-items-center justify-content-center" style="width: 100%; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                        @if ($media_type === 'audio')
                                        <i class="fas fa-music text-white" style="font-size: 3rem;"></i>
                                        @elseif($media_type === 'video_link' || $media_type === 'video_file')
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
                                        {{ ucfirst(str_replace('_', ' ', $media_type)) }}
                                    </span>

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
                                    {{ Str::limit($description, 30) }}
                                </p>

                                <div class="d-flex justify-content-between align-items-center flex-wrap">
                                    <small class="text-muted mb-1">{{ $created_at->format('d/m/Y') }}</small>

                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-info view-emission-btn rounded" title="Voir" data-media-id="{{ $id }}">
                                            <i class="fas fa-eye"></i>
                                        </button>

                                        <button class="btn btn-sm btn-outline-primary edit-emission-btn mx-1 rounded" title="Modifier" data-media-id="{{ $id }}">
                                            <i class="fas fa-edit"></i>
                                        </button>

                                        <form action="{{ route('items.destroy', $id) }}" method="POST" class="d-inline delete-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet emission ?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>

                                        {{-- @if (in_array($media_type, ['video_link', 'video_file']))
                                        <button class="btn btn-sm btn-outline-{{ $is_published ? 'success' : 'secondary' }} toggle-publish-btn mx-1 rounded"
                                        title="{{ $is_published ? 'Dépublier' : 'Publier' }}"
                                        data-emission-id="{{ $id }}"
                                        data-status="{{ $is_published ? 1 : 0 }}">
                                        <i class="fas fa-{{ $is_published ? 'toggle-on' : 'toggle-off' }}"></i>
                                        <span class="p-1">{{ $is_published ? 'Publié' : 'Non publié' }}</span>
                                        </button>
                                        @endif --}}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="col-12 text-center py-5">
                        <div class="empty-state">
                            <i class="fas fa-comments fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted">Aucun emission disponible</h4>
                        </div>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="d-flex justify-content-center mt-3">
            {{ $emissionItems->appends(request()->query())->links('pagination::bootstrap-5') }}
        </div>

    </div>
</section>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {

        // Toggle publication
        $(document).on('click', '.toggle-publish-btn', function() {
            const $btn = $(this);
            const id = $btn.data('emission-id');
            const isPublished = Number($btn.data('status')) === 1;
            const url = isPublished ?
                "{{ url('emissions') }}/" + id + "/unpublish" :
                "{{ url('emissions') }}/" + id + "/publish";

            $.post(url, {
                    _token: '{{ csrf_token() }}'
                })
                .done(() => window.location.reload())
                .fail(() => alert('Erreur lors du changement de statut de publication'));
        });

        // Edit
        $(document).on('click', '.edit-emission-btn', function() {
            handleEditMedia($(this)
                , "{{ route('emissionsitem.edit', ':id') }}"
                , "{{ route('emissionsitem.update', ':id') }}"
                , '#editModal');
        });

        // View
        $(document).on('click', '.view-emission-btn, .emission-thumbnail', function() {
            handleMediaView($(this), "{{ route('emissions.items.voir', ':id') }}");
        });

        $('#addstoreForm').on('submit', function(e) {
             let emissionId = $('.emission-thumbnail').data('emission-id');
            if (emissionId) {
                $('#input-emission-id').val(emissionId);
            } else {
                e.preventDefault();
                alert("Impossible d’envoyer le formulaire : ID d’émission introuvable !");
            }
        });

         $('#editForm').on('submit', function(e) {
             let emissionId = $('.emission-thumbnail').data('emission-id');
            if (emissionId) {
                $('#input-emission-id').val(emissionId);
            } else {
                e.preventDefault();
                alert("Impossible d’envoyer le formulaire : ID d’émission introuvable !");
            }
        });
    });

</script>
@endpush
