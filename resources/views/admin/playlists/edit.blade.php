@extends('admin.master')

@section('title', 'Modifier la Playlist')

@push('styles')
<style>
    .section-title {
        font-size: 1.5rem;
        color: #4e73df;
        margin-bottom: 0;
    }

    /* Wizard Styles */
    .wizard {
        background: white;
        border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }

    .wizard .steps {
        background: #f8f9fa;
        padding: 1rem 0;
        border-bottom: 1px solid #e9ecef;
    }

    .wizard .steps ul {
        display: flex;
        justify-content: center;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .wizard .steps li {
        position: relative;
        margin: 0 1rem;
        text-align: center;
        min-width: 120px;
    }

    .wizard .steps li:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 50%;
        right: -1rem;
        width: 2rem;
        height: 2px;
        background: #dee2e6;
        transform: translateY(-50%);
    }

    .wizard .steps li.done::after {
        background: #28a745;
    }

    .wizard .steps li a {
        display: block;
        color: #6c757d;
        text-decoration: none;
        padding: 0.5rem;
        border-radius: 5px;
        transition: all 0.3s;
    }

    .wizard .steps li.current a,
    .wizard .steps li.done a {
        color: #495057;
        background: white;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .wizard .steps li.current a {
        color: #4e73df;
        font-weight: bold;
    }

    .wizard .steps .number {
        display: inline-block;
        width: 30px;
        height: 30px;
        line-height: 30px;
        background: #dee2e6;
        color: white;
        border-radius: 50%;
        margin-right: 0.5rem;
        font-weight: bold;
    }

    .wizard .steps li.current .number,
    .wizard .steps li.done .number {
        background: #4e73df;
    }

    .wizard .content {
        padding: 2rem;
        min-height: 400px;
    }

    .wizard .actions {
        background: #f8f9fa;
        padding: 1rem 2rem;
        border-top: 1px solid #e9ecef;
        display: flex;
        justify-content: space-between;
    }

    /* Video Selection Styles */
    .video-selection-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .video-card {
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 1rem;
        transition: all 0.3s;
        cursor: pointer;
        position: relative;
    }

    .video-card:hover {
        border-color: #4e73df;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .video-card.selected {
        border-color: #28a745;
        background-color: #f8fff9;
    }

    .video-card .selection-order {
        position: absolute;
        top: -10px;
        right: -10px;
        background: #28a745;
        color: white;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 0.9rem;
    }

    .selected-videos-list {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 1.5rem;
        margin-top: 2rem;
    }

    .selected-video-item {
        background: white;
        border-radius: 5px;
        padding: 1rem;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: move;
        border: 1px solid #e9ecef;
    }

    .selected-video-item:hover {
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .drag-handle {
        color: #6c757d;
        margin-right: 1rem;
        cursor: move;
    }

    .video-info {
        flex: 1;
    }

    .video-title {
        font-weight: bold;
        margin-bottom: 0.25rem;
    }

    .video-duration {
        color: #6c757d;
        font-size: 0.9rem;
    }

    .remove-video {
        color: #dc3545;
        background: none;
        border: none;
        font-size: 1.2rem;
        cursor: pointer;
        padding: 0.25rem;
    }

    .remove-video:hover {
        color: #c82333;
    }

    /* Preview Styles */
    .playlist-preview {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 2rem;
    }

    .playlist-info {
        background: white;
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        border-left: 4px solid #4e73df;
    }

    .playlist-videos {
        background: white;
        border-radius: 10px;
        padding: 1.5rem;
    }

    .preview-video-item {
        display: flex;
        align-items: center;
        padding: 1rem;
        border-bottom: 1px solid #e9ecef;
    }

    .preview-video-item:last-child {
        border-bottom: none;
    }

    .video-order {
        background: #4e73df;
        color: white;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
        font-weight: bold;
    }

    /* Modal Styles */
    .video-player-modal .modal-dialog {
        max-width: 90%;
        width: 1000px;
    }

    .video-player-modal .modal-body {
        padding: 0;
    }

    .video-player-container {
        position: relative;
        background: #000;
    }

    .video-player-container video {
        width: 100%;
        height: 500px;
        object-fit: contain;
    }

    .video-playlist-sidebar {
        background: #f8f9fa;
        padding: 1rem;
        height: 500px;
        overflow-y: auto;
    }

    .playlist-video-item {
        display: flex;
        align-items: center;
        padding: 0.75rem;
        margin-bottom: 0.5rem;
        background: white;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s;
        border: 2px solid transparent;
    }

    .playlist-video-item:hover {
        background: #e9ecef;
    }

    .playlist-video-item.active {
        border-color: #4e73df;
        background: #e3f2fd;
    }

    .playlist-video-item .video-number {
        background: #6c757d;
        color: white;
        border-radius: 50%;
        width: 25px;
        height: 25px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 0.75rem;
        font-size: 0.8rem;
        font-weight: bold;
    }

    .playlist-video-item.active .video-number {
        background: #4e73df;
        color: white;
    }

    .playlist-video-info {
        flex: 1;
    }

    .playlist-video-title {
        font-weight: 600;
        margin-bottom: 0.25rem;
        font-size: 0.9rem;
    }

    .playlist-video-duration {
        color: #6c757d;
        font-size: 0.8rem;
    }

    .video-controls {
        position: absolute;
        bottom: 10px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0, 0, 0, 0.7);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 25px;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .control-btn {
        background: none;
        border: none;
        color: white;
        font-size: 1.2rem;
        cursor: pointer;
        padding: 0.25rem;
        border-radius: 3px;
        transition: all 0.3s;
    }

    .control-btn:hover {
        background: rgba(255, 255, 255, 0.2);
    }

    .control-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .wizard .steps ul {
            flex-direction: column;
            align-items: center;
        }

        .wizard .steps li {
            margin: 0.25rem 0;
            min-width: auto;
        }

        .wizard .steps li:not(:last-child)::after {
            display: none;
        }

        .video-selection-grid {
            grid-template-columns: 1fr;
        }

        .wizard .content {
            padding: 1rem;
        }

        .wizard .actions {
            padding: 1rem;
            flex-direction: column;
            gap: 0.5rem;
        }

        .video-player-modal .modal-dialog {
            max-width: 95%;
        }

        .video-player-container video {
            height: 250px;
        }

        .video-playlist-sidebar {
            height: 300px;
        }
    }

    .hidden {
        display: none !important;
    }

    .form-group label {
        font-weight: 600;
        color: #495057;
    }

    .test-playlist-btn {
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 25px;
        transition: all 0.3s;
    }

    .test-playlist-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(78, 115, 223, 0.3);
    }

    .edit-playlist-header {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        color: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 25px;
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
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="section-title">Modifier la playlist</h2>
                    <a href="{{ route('playlists.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Assistant de modification de playlist</h4>
                    </div>
                    <div class="card-body">
                        <div id="wizard_playlist" role="application" class="wizard clearfix">
                            <!-- Steps Navigation -->
                            <div class="steps clearfix">
                                <ul role="tablist">
                                    <li role="tab" class="first current" aria-disabled="false" aria-selected="true">
                                        <a href="#step-1" aria-controls="step-1">
                                            <span class="number">1.</span> Informations
                                        </a>
                                    </li>
                                    <li role="tab" aria-disabled="true" aria-selected="false">
                                        <a href="#step-2" aria-controls="step-2">
                                            <span class="number">2.</span> Sélection vidéos
                                        </a>
                                    </li>
                                    <li role="tab" aria-disabled="true" aria-selected="false">
                                        <a href="#step-3" aria-controls="step-3">
                                            <span class="number">3.</span> Organisation
                                        </a>
                                    </li>
                                    <li role="tab" class="last" aria-disabled="true" aria-selected="false">
                                        <a href="#step-4" aria-controls="step-4">
                                            <span class="number">4.</span> Confirmation
                                        </a>
                                    </li>
                                </ul>
                            </div>

                            <!-- Form Content -->
                            <form id="playlistForm" action="{{ route('playlists.update', $playlist->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="content clearfix">
                                    <!-- Étape 1: Informations de la playlist -->
                                    <section id="step-1" class="body current" aria-hidden="false">
                                        <h3 class="mb-4">Informations de la playlist</h3>

                                        <div class="row">
                                            <div class="col-md-8">
                                                <div class="form-group">
                                                    <label for="nom">Nom de la playlist *</label>
                                                    <input type="text" class="form-control" id="nom"
                                                        name="nom" placeholder="Ex: Playlist du matin" 
                                                        value="{{ old('nom', $playlist->nom) }}" required>
                                                </div>

                                                <div class="form-group">
                                                    <label for="description">Description</label>
                                                    <textarea class="form-control" id="description" name="description" rows="4"
                                                        placeholder="Décrivez le contenu ou l'objectif de cette playlist">{{ old('description', $playlist->description) }}</textarea>
                                                </div>

                                                <div class="form-group">
                                                    <label for="etat">Etat de la playlist</label>
                                                    <select class="form-control" id="etat" name="etat">
                                                        <option value="1" {{ $playlist->etat ? 'selected' : '' }}>Actif</option>
                                                        <option value="0" {{ !$playlist->etat ? 'selected' : '' }}>Inactif</option>
                                                    </select>
                                                </div>

                                                <div class="form-group">
                                                    <label for="date_debut">Date de début de lecture *</label>
                                                    <input type="datetime-local" class="form-control" id="date_debut"
                                                        name="date_debut" value="{{ old('date_debut', $playlist->date_debut->format('Y-m-d\TH:i')) }}" required>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="alert alert-info">
                                                    <h6><i class="fas fa-info-circle"></i> Informations</h6>
                                                    <p class="mb-2">Cette étape permet de modifier les informations de base de votre playlist.</p>
                                                    <small class="text-dark">Les vidéos actuelles sont conservées jusqu'à modification.</small>
                                                </div>
                                            </div>
                                        </div>
                                    </section>

                                    <!-- Étape 2: Sélection des vidéos -->
                                    <section id="step-2" class="body hidden" aria-hidden="true">
                                        <h3 class="mb-4">Sélection des vidéos</h3>

                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <input type="text" id="searchVideos" class="form-control"
                                                    placeholder="Rechercher une vidéo...">
                                            </div>
                                            <div class="col-md-6 text-right">
                                                <span class="badge badge-primary" id="selectedCount">0 vidéo(s) sélectionnée(s)</span>
                                            </div>
                                        </div>

                                        <div class="video-selection-grid" id="videosGrid">
                                            @foreach ($videos as $video)
                                                @php
                                                    $isSelected = $playlist->items->contains('id_video', $video->id);
                                                @endphp
                                                <div class="video-card {{ $isSelected ? 'selected' : '' }}" 
                                                    data-video-id="{{ $video->id }}"
                                                    data-video-title="{{ $video->nom }}"
                                                    data-video-duration="{{ $video->duree ?? '00:00:00' }}"
                                                    data-video-url="{{ asset('storage/' . $video->media->url_fichier ?? '') }}">
                                                    <div class="video-thumbnail" style="position: relative; height: 150px; overflow: hidden; border-radius: 8px; background: #000; margin-bottom: 10px;">
                                                        <video style="width: 100%; height: 100%; object-fit: cover;" 
                                                               preload="metadata" 
                                                               onloadeddata="this.currentTime = 10">
                                                            <source src="{{ asset('storage/' . $video->media->url_fichier ?? '') }}#t=10" type="video/mp4">
                                                        </video>
                                                        <div class="play-overlay" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: white; font-size: 2rem; opacity: 0.8;">
                                                            <i class="fas fa-play-circle"></i>
                                                        </div>
                                                    </div>
                                                    <div class="selection-order {{ $isSelected ? '' : 'hidden' }}">
                                                        @if($isSelected)
                                                            {{ $playlist->items->where('video_id', $video->id)->first() }}
                                                        @endif
                                                    </div>
                                                    <div class="d-flex align-items-center mb-2">
                                                        <i class="fas fa-play-circle text-primary mr-2"></i>
                                                        <h6 class="mb-0">{{ Str::limit($video->nom, 30) }}</h6>
                                                    </div>
                                                    <p class="text-muted mb-2" style="font-size: 0.9rem;">
                                                        {{ Str::limit($video->description ?? 'Aucune description', 50) }}
                                                    </p>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <small class="text-muted">
                                                            <i class="fas fa-clock"></i> {{ $video->duree ?? 'N/A' }}
                                                        </small>
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox"
                                                                class="custom-control-input video-checkbox"
                                                                id="video_{{ $video->id }}"
                                                                value="{{ $video->id }}"
                                                                {{ $isSelected ? 'checked' : '' }}>
                                                            <label class="custom-control-label"
                                                                for="video_{{ $video->id }}"></label>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </section>

                                    <!-- Étape 3: Organisation des vidéos -->
                                    <section id="step-3" class="body hidden" aria-hidden="true">
                                        <h3 class="mb-4">Organisation des vidéos</h3>

                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle"></i>
                                            Glissez-déposez les vidéos pour réorganiser l'ordre de lecture de votre playlist.
                                        </div>

                                        <div class="selected-videos-list">
                                            <h5 class="mb-3">Vidéos sélectionnées</h5>
                                            <div id="sortableVideosList" class="sortable-list">
                                                <!-- Les vidéos sélectionnées apparaîtront ici -->
                                            </div>
                                            <div id="noVideosSelected" class="text-center py-4 text-muted">
                                                <i class="fas fa-video fa-3x mb-3"></i>
                                                <p>Aucune vidéo sélectionnée. Retournez à l'étape précédente pour sélectionner des vidéos.</p>
                                            </div>
                                        </div>
                                    </section>

                                    <!-- Étape 4: Confirmation -->
                                    <section id="step-4" class="body hidden" aria-hidden="true">
                                        <h3 class="mb-4">Confirmation et aperçu</h3>

                                        <div class="playlist-preview">
                                            <div class="playlist-info">
                                                <h4 class="text-primary mb-3">
                                                    <i class="fas fa-list-alt"></i> Informations de la playlist
                                                </h4>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <p><strong>Nom:</strong> <span id="preview-nom"></span></p>
                                                        <p><strong>Date de début:</strong> <span id="preview-date"></span></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p><strong>Nombre de vidéos:</strong> <span id="preview-count"></span></p>
                                                        <p><strong>État:</strong> <span id="preview-etat"></span></p>
                                                    </div>
                                                </div>
                                                <p><strong>Description:</strong> <span id="preview-description"></span></p>
                                            </div>

                                            <div class="playlist-videos">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <h5 class="mb-0">
                                                        <i class="fas fa-play"></i> Ordre de lecture
                                                    </h5>
                                                    <button type="button" class="btn btn-info bg-info test-playlist-btn" id="testPlaylistBtn">
                                                        <i class="fas fa-play"></i> Prévisualiser la playlist
                                                    </button>
                                                </div>
                                                <div id="previewVideosList">
                                                    <!-- Liste des vidéos en aperçu -->
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Champs cachés pour la soumission -->
                                        <input type="hidden" name="selected_videos" id="selectedVideosInput">
                                    </section>
                                </div>

                                <!-- Actions du wizard -->
                                <div class="actions clearfix">
                                    <button type="button" id="prevBtn" class="btn btn-outline-secondary hidden">
                                        <i class="fas fa-arrow-left"></i> Précédent
                                    </button>
                                    <div>
                                        <button type="button" id="nextBtn" class="btn btn-primary">
                                            Suivant <i class="fas fa-arrow-right"></i>
                                        </button>
                                        <button type="submit" id="finishBtn" class="btn btn-success bg-success hidden">
                                            <i class="fas fa-save"></i> Mettre à jour la playlist
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal de prévisualisation vidéo -->
<div class="modal fade video-player-modal" id="videoPlayerModal" tabindex="-1" role="dialog"
    aria-labelledby="videoPlayerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="videoPlayerModalLabel">
                    <i class="fas fa-play-circle"></i> Prévisualisation de la playlist
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="video-player-container">
                            <video id="modalVideoPlayer" controls controlsList="nodownload">
                                <source src="" type="video/mp4">
                                Votre navigateur ne supporte pas la lecture vidéo.
                            </video>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="video-playlist-sidebar">
                            <h6 class="mb-3">
                                <i class="fas fa-list"></i> Playlist
                                <span class="badge badge-primary ml-2" id="modalPlaylistCount">0</span>
                            </h6>
                            <div id="modalPlaylistItems">
                                <!-- Items de la playlist apparaîtront ici -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary bg-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Fermer
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    $(document).ready(function() {
        let currentStep = 1;
        let selectedVideos = [];
        let sortable;
        let currentVideoIndex = 0;

        // Initialiser les vidéos sélectionnées depuis la playlist existante
        @foreach($playlist->items as $item)
        selectedVideos.push({
            id: '{{ $item->video->id }}',
            title: '{{ addslashes($item->video->nom) }}',
            duration: '{{ $item->duree_video ?? "00:00:00" }}',
            url: '{{ asset("storage/" . $item->video->media->url_fichier ?? "") }}',
            order: {{ $item->position }}
        });
        @endforeach

        // Navigation du wizard
        function showStep(step) {
            // Masquer toutes les sections
            $('.wizard .body').addClass('hidden').attr('aria-hidden', 'true');

            // Afficher la section courante
            $(`#step-${step}`).removeClass('hidden').attr('aria-hidden', 'false');

            // Mettre à jour la navigation
            $('.wizard .steps li').removeClass('current done');

            for (let i = 1; i <= 4; i++) {
                const $li = $(`.wizard .steps li:nth-child(${i})`);
                if (i < step) {
                    $li.addClass('done');
                } else if (i === step) {
                    $li.addClass('current');
                }
            }

            // Gérer les boutons
            if (step === 1) {
                $('#prevBtn').addClass('hidden');
            } else {
                $('#prevBtn').removeClass('hidden');
            }

            if (step === 4) {
                $('#nextBtn').addClass('hidden');
                $('#finishBtn').removeClass('hidden');
                updatePreview();
            } else {
                $('#nextBtn').removeClass('hidden');
                $('#finishBtn').addClass('hidden');
            }

            currentStep = step;
        }

        // Validation des étapes
        function validateStep(step) {
            switch (step) {
                case 1:
                    const nom = $('#nom').val().trim();
                    const dateDebut = $('#date_debut').val();

                    if (!nom || !dateDebut) {
                        alert('Veuillez remplir tous les champs obligatoires.');
                        return false;
                    }
                    break;

                case 2:
                    if (selectedVideos.length === 0) {
                        alert('Veuillez sélectionner au moins une vidéo.');
                        return false;
                    }
                    break;
            }
            return true;
        }

        // Gestion des clics sur les boutons
        $('#nextBtn').click(function() {
            if (validateStep(currentStep) && currentStep < 4) {
                if (currentStep === 2) {
                    updateSortableList();
                }
                showStep(currentStep + 1);
            }
        });

        $('#prevBtn').click(function() {
            if (currentStep > 1) {
                showStep(currentStep - 1);
            }
        });

        // Gestion de la sélection des vidéos
        $(document).on('change', '.video-checkbox', function() {
            const videoId = $(this).val();
            const videoCard = $(this).closest('.video-card');
            const videoTitle = videoCard.data('video-title');
            const videoDuration = videoCard.data('video-duration');
            const videoUrl = videoCard.data('video-url');

            if ($(this).is(':checked')) {
                // Ajouter la vidéo
                selectedVideos.push({
                    id: videoId,
                    title: videoTitle,
                    duration: videoDuration,
                    url: videoUrl,
                    order: selectedVideos.length + 1
                });

                videoCard.addClass('selected');
            } else {
                // Retirer la vidéo
                selectedVideos = selectedVideos.filter(v => v.id !== videoId);
                videoCard.removeClass('selected');
            }

            updateSelectionOrder();
            updateSelectedCount();
        });

        // Recherche de vidéos
        $('#searchVideos').on('input', function() {
            const searchTerm = $(this).val().toLowerCase();

            $('.video-card').each(function() {
                const title = $(this).data('video-title').toLowerCase();
                if (title.includes(searchTerm)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });

        // Mise à jour de l'ordre de sélection
        function updateSelectionOrder() {
            // D'abord, masquer tous les badges d'ordre
            $('.video-card .selection-order').addClass('hidden');

            // Réorganiser les indices dans selectedVideos
            selectedVideos.forEach((video, index) => {
                video.order = index + 1;
            });

            // Afficher les nouveaux numéros d'ordre
            selectedVideos.forEach((video) => {
                const $card = $(`.video-card[data-video-id="${video.id}"]`);
                const $orderBadge = $card.find('.selection-order');
                $orderBadge.text(video.order).removeClass('hidden');
            });
        }

        // Mise à jour du compteur
        function updateSelectedCount() {
            $('#selectedCount').text(`${selectedVideos.length} vidéo(s) sélectionnée(s)`);
        }

        // Création de la liste triable
        function updateSortableList() {
            const $list = $('#sortableVideosList');
            $list.empty();

            if (selectedVideos.length === 0) {
                $('#noVideosSelected').show();
                return;
            }

            $('#noVideosSelected').hide();

            selectedVideos.forEach((video, index) => {
                const item = `
                    <div class="selected-video-item" data-video-id="${video.id}">
                        <div class="drag-handle">
                            <i class="fas fa-grip-vertical"></i>
                        </div>
                        <div class="video-info">
                            <div class="video-title">${video.title}</div>
                            <div class="video-duration">
                                <i class="fas fa-clock"></i> ${video.duration}
                            </div>
                        </div>
                        <div class="video-order-number">
                            <span class="badge badge-primary">#${video.order}</span>
                        </div>
                        <button type="button" class="remove-video" data-video-id="${video.id}">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
                $list.append(item);
            });

            // Initialiser Sortable
            if (sortable) {
                sortable.destroy();
            }

            sortable = Sortable.create(document.getElementById('sortableVideosList'), {
                handle: '.drag-handle',
                animation: 150,
                onEnd: function() {
                    updateVideoOrder();
                }
            });
        }

        // Mise à jour de l'ordre après drag & drop
        function updateVideoOrder() {
            const newOrder = [];
            $('#sortableVideosList .selected-video-item').each(function(index) {
                const videoId = $(this).data('video-id');
                const video = selectedVideos.find(v => v.id == videoId);
                if (video) {
                    video.order = index + 1;
                    newOrder.push(video);
                    $(this).find('.video-order-number .badge').text(`#${index + 1}`);
                }
            });
            selectedVideos = newOrder;
            updateSelectionOrder();
        }

        // Suppression d'une vidéo de la liste
        $(document).on('click', '.remove-video', function() {
            const videoId = $(this).data('video-id');

            // Retirer de la liste sélectionnée
            selectedVideos = selectedVideos.filter(v => v.id != videoId);

            // Décocher la checkbox correspondante
            $(`.video-checkbox[value="${videoId}"]`).prop('checked', false);
            $(`.video-card[data-video-id="${videoId}"]`).removeClass('selected');

            // Mettre à jour l'affichage
            updateSortableList();
            updateSelectionOrder();
            updateSelectedCount();
        });

        // Mise à jour de l'aperçu
        function updatePreview() {
            $('#preview-nom').text($('#nom').val());
            $('#preview-description').text($('#description').val() || 'Aucune description');
            $('#preview-date').text(formatDateTime($('#date_debut').val()));
            $('#preview-count').text(selectedVideos.length);
            $('#preview-etat').text($('#etat').val() == 1 ? 'Actif' : 'Inactif');

            const $previewList = $('#previewVideosList');
            $previewList.empty();

            selectedVideos.forEach((video, index) => {
                const item = `
                    <div class="preview-video-item">
                        <div class="video-order">${video.order}</div>
                        <div class="video-info">
                            <div class="video-title">${video.title}</div>
                            <div class="video-duration">
                                <i class="fas fa-clock"></i> ${video.duration}
                            </div>
                        </div>
                    </div>
                `;
                $previewList.append(item);
            });

            // Préparer les données pour la soumission
            $('#selectedVideosInput').val(JSON.stringify(selectedVideos));
        }

        // Test de la playlist avec modal vidéo
        $('#testPlaylistBtn').click(function() {
            if (selectedVideos.length === 0) {
                alert('Aucune vidéo à prévisualiser.');
                return;
            }

            showVideoModal();
        });

        // Affichage du modal vidéo
        function showVideoModal() {
            currentVideoIndex = 0;
            updateModalPlaylist();
            loadVideoInModal(currentVideoIndex);
            $('#videoPlayerModal').modal('show');
        }

        // Mise à jour de la liste dans le modal
        function updateModalPlaylist() {
            const $container = $('#modalPlaylistItems');
            $container.empty();

            $('#modalPlaylistCount').text(selectedVideos.length);

            selectedVideos.forEach((video, index) => {
                const item = `
                    <div class="playlist-video-item ${index === currentVideoIndex ? 'active' : ''}" data-index="${index}">
                        <div class="video-number">${video.order}</div>
                        <div class="playlist-video-info">
                            <div class="playlist-video-title">${video.title}</div>
                            <div class="playlist-video-duration">
                                <i class="fas fa-clock"></i> ${video.duration}
                            </div>
                        </div>
                    </div>
                `;
                $container.append(item);
            });
        }

        // Chargement d'une vidéo dans le modal
        function loadVideoInModal(index) {
            if (index < 0 || index >= selectedVideos.length) return;

            currentVideoIndex = index;
            const video = selectedVideos[index];

            // Charger la vidéo
            const $player = $('#modalVideoPlayer');
            $player.find('source').attr('src', video.url);
            $player[0].load();

            // Lecture automatique
            $player[0].play();

            // Mettre à jour le titre du modal
            $('#videoPlayerModalLabel').html(`
                <i class="fas fa-play-circle"></i> 
                ${video.title} (${index + 1}/${selectedVideos.length})
            `);

            // Mettre à jour l'état actif dans la playlist
            $('.playlist-video-item').removeClass('active');
            $(`.playlist-video-item[data-index="${index}"]`).addClass('active');

            // Faire défiler vers l'élément actif
            const $activeItem = $(`.playlist-video-item[data-index="${index}"]`);
            if ($activeItem.length) {
                $activeItem[0].scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }
        }

        // Lecture automatique de la vidéo suivante
        $('#modalVideoPlayer').on('ended', function() {
            if (currentVideoIndex < selectedVideos.length - 1) {
                loadVideoInModal(currentVideoIndex + 1);
            } else {
                $('#videoPlayerModal').modal('hide');
            }
        });

        // Clic sur un élément de la playlist dans le modal
        $(document).on('click', '.playlist-video-item', function() {
            const index = parseInt($(this).data('index'));
            loadVideoInModal(index);
        });

        // Bouton retour à l'édition depuis le modal
        $('#backToEditBtn').click(function() {
            $('#videoPlayerModal').modal('hide');
        });

        // Réinitialiser la vidéo quand le modal se ferme
        $('#videoPlayerModal').on('hidden.bs.modal', function() {
            const $player = $('#modalVideoPlayer');
            $player[0].pause();
            $player[0].currentTime = 0;
        });

        // Formatage de la date
        function formatDateTime(dateTimeString) {
            if (!dateTimeString) return '';
            const date = new Date(dateTimeString);
            return date.toLocaleString('fr-FR', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // Soumission du formulaire
        $('#playlistForm').submit(function(e) {
            if (selectedVideos.length === 0) {
                e.preventDefault();
                alert('Veuillez sélectionner au moins une vidéo.');
                return false;
            }

            $('#selectedVideosInput').val(JSON.stringify(selectedVideos));
            return true;
        });

        // Initialiser la première étape
        showStep(1);
        updateSelectedCount();
        updateSelectionOrder();
    });
</script>
@endpush