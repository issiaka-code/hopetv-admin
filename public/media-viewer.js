function handleMediaView($element, routeTemplate) {
    const id = $element.data('temoignage-id') || $element.data('priere-id') || 
                $element.data('prophetie-id') || $element.data('podcast-id') ||
                $element.data('home-charity-id') ||  $element.data('home-charity-id') ||
                $element.data('media-id');
                ; 


    const url = routeTemplate.replace(':id', id);

    $.ajax({
        url: url,
        method: 'GET',
        dataType: 'json',
        beforeSend: function() {
            $('#loader').removeClass('d-none');
        },
        success: function(response) {
            $('#loader').addClass('d-none');

            if (response.status === 'processing') {
                Swal.fire({ icon: 'info', title: 'En traitement', text: response.message });
                return;
            }
            if (response.status === 'error') {
                Swal.fire({ icon: 'error', title: 'Erreur', text: response.message });
                return;
            }

            const data = response.temoignage || response.priere || response.prophetie || response.podcast || response.data ;
            const media = data.media;
            
            // Affichage du contenu
            $('#ViewModalLabel').text(data.nom);
            $('#Description').text(data.description);

            // Masquer tous les lecteurs
            $('#audioPlayerContainer, #videoPlayerContainer, #iframePlayerContainer, #pdfViewerContainer, #imageCarouselContainer').addClass('d-none');

            // Gérer chaque type de média
            if (media.type === 'audio') {
                $('#modalAudioPlayer').attr('src', media.url);
                $('#audioPlayerContainer').removeClass('d-none');
                $('#mediaTypeBadge').text('Audio').removeClass().addClass('badge badge-pill badge-info');
            } 
            else if (media.type === 'video') {
                $('#modalVideoPlayer').attr('src', media.url);
                $('#videoPlayerContainer').removeClass('d-none');
                $('#mediaTypeBadge').text('Vidéo').removeClass().addClass('badge badge-pill badge-primary');
            } 
            else if (media.type === 'link') {
                $('#modalIframePlayer').attr('src', media.url);
                $('#iframePlayerContainer').removeClass('d-none');
                $('#mediaTypeBadge').text('Lien vidéo').removeClass().addClass('badge badge-pill badge-secondary');
            } 
            else if (media.type === 'pdf') {
                $('#modalPdfViewer').attr('src', media.url + '#view=FitH');
                $('#pdfViewerContainer').removeClass('d-none');
                $('#mediaTypeBadge').text('PDF').removeClass().addClass('badge badge-pill badge-danger');
            } 
            else if (media.type === 'images') {
                $('#imageCarouselInner').empty();
                media.url.forEach(function(url, idx) {
                    const active = idx === 0 ? 'active' : '';
                    $('#imageCarouselInner').append(`
                        <div class="carousel-item ${active}">
                            <img class="d-block w-100" src="${url}" alt="Image ${idx + 1}">
                        </div>
                    `);
                });
                $('#imageCarouselContainer').removeClass('d-none');
                $('#mediaTypeBadge').text('Images').removeClass().addClass('badge badge-pill badge-warning');
            }

            // Afficher le modal
            $('#viewModal').modal('show');
        },
        error: function() {
            $('#loader').addClass('d-none');
            Swal.fire({ icon: 'error', title: 'Erreur', text: 'Impossible de charger le média.' });
        }
    });
}

// ===== NETTOYAGE DU MODAL =====
$('#viewModal').on('hidden.bs.modal', function() {
    // Arrêter tous les médias
    const audioPlayer = $('#modalAudioPlayer').get(0);
    const videoPlayer = $('#modalVideoPlayer').get(0);
    if (audioPlayer) audioPlayer.pause();
    if (videoPlayer) videoPlayer.pause();

    // Réinitialiser les sources
    $('#modalAudioPlayer').attr('src', '');
    $('#modalVideoPlayer').attr('src', '');
    $('#modalIframePlayer').attr('src', '');
    $('#modalPdfViewer').attr('src', '');

    // Masquer tous les lecteurs
    $('#audioPlayerContainer, #videoPlayerContainer, #iframePlayerContainer, #pdfViewerContainer, #imageCarouselContainer')
        .addClass('d-none');

    // Vider les infos
    $('#ViewModalLabel').text('Témoignage');
    $('#Description, #mediaTypeBadge').text('');
});

// ===== LECTURE AUTOMATIQUE =====
$('#viewModal').on('shown.bs.modal', function() {
    const audioPlayer = $('#modalAudioPlayer').get(0);
    const videoPlayer = $('#modalVideoPlayer').get(0);

    if (audioPlayer && !$('#audioPlayerContainer').hasClass('d-none')) {
        audioPlayer.play().catch(function(error) {
            console.log('Lecture audio automatique bloquée:', error);
        });
    } 
    else if (videoPlayer && !$('#videoPlayerContainer').hasClass('d-none')) {
        videoPlayer.play().catch(function(error) {
            console.log('Lecture vidéo automatique bloquée:', error);
        });
    }
});
