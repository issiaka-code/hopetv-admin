@extends('admin.master')

@section('title', 'Détail de l\'émission')

@push('styles')
    <style>
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
        .video-thumbnail img,
        .video-thumbnail iframe {
            object-fit: cover;
            height: 100%;
            width: 100%;
            transition: transform 0.3s;
        }

        .video-card:hover .video-thumbnail video,
        .video-card:hover .video-thumbnail img,
        .video-card:hover .video-thumbnail iframe {
            transform: scale(1.05);
        }
        .thumbnail-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
            cursor: pointer;
        }
        .thumbnail-overlay i {
            font-size: 2rem;
            color: white;
        }
        .video-thumbnail-container:hover .thumbnail-overlay {
            opacity: 1;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-8">
                <h2 class="font-weight-bold">{{ $emission->nom }}</h2>
                <p class="text-muted">{{ $emission->description }}</p>
            </div>
            <div class="col-md-4 text-right">
                <button class="btn btn-primary" data-toggle="modal" data-target="#addEmissionVideoModal">
                    <i class="fa fa-plus"></i> Ajouter une vidéo
                </button>
            </div>
        </div>
        <div class="row">
            @forelse($emission->items as $item)
                <div class="col-md-3 mb-4">
                    <div class="card video-card">
                        <div class="video-thumbnail-container">
                            @if (!empty($item->thumbnail))
                                <img class="video-thumbnail" src="{{ $item->thumbnail_url }}" alt="Couverture vidéo">
                                <div class="thumbnail-overlay open-video" data-video-url="{{ $item->type_video === 'video' ? asset('storage/emissions/videos/' . $item->video_url) : $item->video_url }}" data-video-type="{{ $item->type_video }}" data-description="{{ $item->description_video }}" data-title="{{ $item->titre_video }}">
                                    <i class="fas fa-play-circle"></i>
                                </div>
                            @elseif ($item->type_video === 'video' && $item->video_url)
                                <video class="video-thumbnail"
                                    src="{{ asset('storage/emissions/videos/' . $item->video_url) }}" controls
                                    muted></video>
                                <div class="thumbnail-overlay open-video" data-video-url="{{ asset('storage/emissions/videos/' . $item->video_url) }}" data-video-type="video" data-description="{{ $item->description_video }}" data-title="{{ $item->titre_video }}">
                                    <i class="fas fa-play-circle"></i>
                                </div>
                            @elseif($item->type_video === 'link' && $item->video_url)
                                @php
                                    $ytId = null;
                                    $embedUrl = $item->video_url;
                                    if (strpos($item->video_url, 'youtube.com') !== false || strpos($item->video_url, 'youtu.be') !== false) {
                                        // Extraire l'ID YouTube côté Blade pour afficher une couverture
                                        $pattern = '/^.*((youtu.be\\/)|(v\\/)|(\\/u\\/\\w\\/)|(embed\\/)|(watch\\?))\\??v?=?([^#&?]*).*/';
                                        if (preg_match($pattern, $item->video_url, $matches) && strlen($matches[7]) === 11) {
                                            $ytId = $matches[7];
                                            $embedUrl = 'https://www.youtube.com/embed/' . $ytId;
                                        }
                                    }
                                @endphp
                                @if($ytId)
                                    <img class="video-thumbnail" src="https://img.youtube.com/vi/{{ $ytId }}/hqdefault.jpg" alt="Couverture vidéo">
                                    <div class="thumbnail-overlay open-video" data-video-url="{{ $embedUrl }}" data-video-type="link" data-description="{{ $item->description_video }}" data-title="{{ $item->titre_video }}">
                                        <i class="fas fa-play-circle"></i>
                                    </div>
                                @else
                                    <div class="bg-light d-flex align-items-center justify-content-center h-100 position-relative">
                                        <span class="text-muted">Aperçu indisponible</span>
                                        <div class="thumbnail-overlay open-video" data-video-url="{{ $embedUrl }}" data-video-type="link" data-description="{{ $item->description_video }}" data-title="{{ $item->titre_video }}">
                                            <i class="fas fa-play-circle"></i>
                                        </div>
                                    </div>
                                @endif
                            @else
                                <div class="bg-light d-flex align-items-center justify-content-center h-100">
                                    <span class="text-muted">Aucun média</span>
                                </div>
                            @endif
                        </div>
                        <div class="card-body">
                            <h5 class="card-title font-weight-bold">{{ $item->titre_video }}</h5>
                            <p class="card-text text-muted">{{ Str::limit($item->description_video, 30) }}</p>
                            <div class="d-flex justify-content-between align-items-center my">
                                <button class="btn btn-info btn-sm view-video-btn rounded"
                                    data-video-url="{{ $item->type_video === 'video' ? asset('storage/emissions/videos/' . $item->video_url) : (isset($embedUrl) ? $embedUrl : $item->video_url) }}"
                                    data-video-type="{{ $item->type_video }}"
                                    data-description="{{ $item->description_video }}"
                                    data-title="{{ $item->titre_video }}">
                                    <i class="fa fa-eye"></i> Voir
                                </button>
                                
                                <form action="{{ route('items.destroy', $item->id) }}"
                                    method="POST" style="display:inline-block;" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm bg-danger"
                                        onclick="return confirm('Supprimer cette vidéo ?')">
                                        <i class="fa fa-trash"></i> Supprimer
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info">Aucune vidéo pour cette émission.</div>
                </div>
            @endforelse
        </div>
    </div>

    @include('admin.medias.emissions.modals.add_video')
    
    @include('admin.medias.emissions.modals.view_video')
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Gestion du changement de type de vidéo pour l'ajout
            $('input[name="type_video"]').change(function() {
                if ($(this).val() === 'upload') {
                    $('.video-upload-section').show();
                    $('.video-link-section').hide();
                    $('#video_file').prop('required', true);
                    $('#video_url').prop('required', false);
                    $('.thumbnail-section').show();
                } else {
                    $('.video-upload-section').hide();
                    $('.video-link-section').show();
                    $('#video_file').prop('required', false);
                    $('#video_url').prop('required', true);
                    // Masquer le champ image de couverture pour les liens
                    $('.thumbnail-section').hide();
                }
            });

            // Gestion du changement de type de vidéo pour l'édition
            $('input[name="type_video"]').on('change', function() {
                if ($(this).val() === 'upload') {
                    $('.edit-video-upload-section').show();
                    $('.edit-video-link-section').hide();
                } else {
                    $('.edit-video-upload-section').hide();
                    $('.edit-video-link-section').show();
                }
            });

            // Gestion des labels de fichiers
            $('.custom-file-input').on('change', function() {
                let fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').addClass("selected").html(fileName);
            });

            

            // Bouton de visionnage de vidéo (via bouton et via overlay)
            $('.view-video-btn').click(function() {
                const videoUrl = $(this).data('video-url');
                const videoType = $(this).data('video-type');

                let videoHtml = '';

                if (videoType === 'video') {
                    videoHtml = `
                <video controls class="w-100" style="max-height: 70vh;">
                    <source src="${videoUrl}" type="video/mp4">
                    Votre navigateur ne supporte pas la lecture vidéo.
                </video>
            `;
                } else {
                    // Pour les liens YouTube
                    if (videoUrl.includes('youtube.com') || videoUrl.includes('youtu.be')) {
                        const videoId = getYouTubeId(videoUrl);
                        videoHtml = `
                    <iframe width="100%" height="400" 
                        src="https://www.youtube.com/embed/${videoId}" 
                        frameborder="0" 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                        allowfullscreen>
                    </iframe>
                `;
                    } else {
                        // Fallback iframe générique (peut ne pas fonctionner pour toutes les plateformes)
                        videoHtml = `
                    <iframe width="100%" height="400" src="${videoUrl}" frameborder="0" allowfullscreen></iframe>
                `;
                    }
                }

                // Aligner avec le modal du module vidéos
                if (videoType === 'video') {
                    $('#iframePlayerContainer').addClass('d-none');
                    $('#modalIframePlayer').attr('src', '');
                    $('#videoPlayerContainer').removeClass('d-none');
                    // Activer l'autoplay (muted requis par les navigateurs)
                    $('#modalVideoPlayer')
                        .attr('src', videoUrl)
                        .prop('muted', true)
                        .attr('autoplay', true)[0].play().catch(() => {});
                    $('#mediaTypeBadge').text('Fichier vidéo');
                } else {
                    $('#videoPlayerContainer').addClass('d-none');
                    $('#modalVideoPlayer').attr('src', '');
                    $('#iframePlayerContainer').removeClass('d-none');
                    if (videoUrl.includes('youtube.com') || videoUrl.includes('youtu.be')) {
                        const videoId = getYouTubeId(videoUrl);
                        if (videoId) {
                            // Autoplay + mute + modestbranding
                            $('#modalIframePlayer').attr('src', `https://www.youtube.com/embed/${videoId}?autoplay=1&mute=1&rel=0&modestbranding=1`);
                        } else {
                            // Fallback à l'URL brute si extraction échoue
                            const sep = videoUrl.includes('?') ? '&' : '?';
                            $('#modalIframePlayer').attr('src', `${videoUrl}${sep}autoplay=1&mute=1`);
                        }
                        $('#mediaTypeBadge').text('Vidéo en ligne');
                    } else {
                        // Tentative d'autoplay pour iframe générique (peut être limité par la plateforme)
                        const sep = videoUrl.includes('?') ? '&' : '?';
                        $('#modalIframePlayer').attr('src', `${videoUrl}${sep}autoplay=1`);
                        $('#mediaTypeBadge').text('Vidéo en ligne');
                    }
                }
                $('#videoTitle').text($(this).data('title') || $(this).closest('.card').find('.card-title').text());
                $('#videoDescription').text($(this).data('description') || $(this).closest('.card').find('.card-text').text());
                $('#videoViewModal').modal('show');
            });

            $('.open-video').click(function() {
                const videoUrl = $(this).data('video-url');
                const videoType = $(this).data('video-type');

                if (videoType === 'video') {
                    $('#iframePlayerContainer').addClass('d-none');
                    $('#modalIframePlayer').attr('src', '');
                    $('#videoPlayerContainer').removeClass('d-none');
                    $('#modalVideoPlayer')
                        .attr('src', videoUrl)
                        .prop('muted', true)
                        .attr('autoplay', true)[0].play().catch(() => {});
                    $('#mediaTypeBadge').text('Fichier vidéo');
                } else {
                    $('#videoPlayerContainer').addClass('d-none');
                    $('#modalVideoPlayer').attr('src', '');
                    $('#iframePlayerContainer').removeClass('d-none');
                    if (videoUrl.includes('youtube.com') || videoUrl.includes('youtu.be')) {
                        const videoId = getYouTubeId(videoUrl);
                        if (videoId) {
                            $('#modalIframePlayer').attr('src', `https://www.youtube.com/embed/${videoId}?autoplay=1&mute=1&rel=0&modestbranding=1`);
                        } else {
                            const sep = videoUrl.includes('?') ? '&' : '?';
                            $('#modalIframePlayer').attr('src', `${videoUrl}${sep}autoplay=1&mute=1`);
                        }
                        $('#mediaTypeBadge').text('Vidéo en ligne');
                    } else {
                        const sep = videoUrl.includes('?') ? '&' : '?';
                        $('#modalIframePlayer').attr('src', `${videoUrl}${sep}autoplay=1`);
                        $('#mediaTypeBadge').text('Vidéo en ligne');
                    }
                }
                const $card = $(this).closest('.card');
                $('#videoTitle').text($(this).data('title') || $card.find('.card-title').text());
                $('#videoDescription').text($(this).data('description') || $card.find('.card-text').text());
                $('#videoViewModal').modal('show');
            });

            // Nettoyage/pause à la fermeture du modal
            $('#videoViewModal').on('hidden.bs.modal', function() {
                const $video = $('#modalVideoPlayer');
                try { $video[0].pause(); } catch(e) {}
                $video.attr('src', '');
                $('#modalIframePlayer').attr('src', '');
            });

            // Fonction robuste pour extraire l'ID d'une vidéo YouTube (watch, youtu.be, embed, shorts)
            function getYouTubeId(url) {
                try {
                    const u = new URL(url);
                    const host = u.hostname.replace('www.', '');
                    // Cas standard: ?v=ID
                    if ((host === 'youtube.com' || host === 'm.youtube.com' || host === 'youtu.be' || host === 'youtube-nocookie.com')) {
                        if (u.searchParams.has('v') && u.searchParams.get('v').length === 11) {
                            return u.searchParams.get('v');
                        }
                        // youtu.be/ID
                        const parts = u.pathname.split('/').filter(Boolean);
                        if (host === 'youtu.be' && parts.length >= 1 && parts[0].length === 11) {
                            return parts[0];
                        }
                        // /embed/ID
                        const embedIdx = parts.indexOf('embed');
                        if (embedIdx !== -1 && parts[embedIdx + 1] && parts[embedIdx + 1].length === 11) {
                            return parts[embedIdx + 1];
                        }
                        // /shorts/ID
                        const shortsIdx = parts.indexOf('shorts');
                        if (shortsIdx !== -1 && parts[shortsIdx + 1] && parts[shortsIdx + 1].length === 11) {
                            return parts[shortsIdx + 1];
                        }
                    }
                } catch (e) {
                    // ignore
                }
                // Regex fallback
                const regExp = /^.*((youtu.be\/)|(v\/)|(\/u\/\w\/)|(embed\/)|(shorts\/)|(watch\?))\??v?=?([^#&?]*).*/;
                const match = (url || '').match(regExp);
                return (match && match[8] && match[8].length === 11) ? match[8] : false;
            }

            // Gestion de la soumission du formulaire d'ajout
            $('#addVideoForm').submit(function(e) {
                const typeVideo = $('input[name="type_video"]:checked').val();

                if (typeVideo === 'upload' && !$('#video_file').val()) {
                    e.preventDefault();
                    alert('Veuillez sélectionner un fichier vidéo');
                    return false;
                }

                if (typeVideo === 'link' && !$('#video_url').val()) {
                    e.preventDefault();
                    alert('Veuillez saisir un lien vidéo');
                    return false;
                }
            });

            // Nettoyer le modal de visionnage quand il se ferme
            $('#viewVideoModal').on('hidden.bs.modal', function() {
                $('#videoPlayer').html('');
            });

            // Réinitialiser le formulaire d'ajout quand le modal se ferme
            $('#addEmissionVideoModal').on('hidden.bs.modal', function() {
                $('#addVideoForm')[0].reset();
                $('.custom-file-label').html('Choisir un fichier...');
                $('.video-upload-section').show();
                $('.video-link-section').hide();
                $('#video_file').prop('required', true);
                $('#video_url').prop('required', false);
                $('.thumbnail-section').show();
            });
        });
    </script>
@endpush
