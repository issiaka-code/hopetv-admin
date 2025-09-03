<!-- Modal pour visualiser l'information importante -->
<div class="modal fade" id="infoViewModal" tabindex="-1" role="dialog" aria-labelledby="infoViewModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-static" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title mb-2" id="infoViewModalLabel">Information Importante</h5>
                <button type="button" class="close text-white fw-bold" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <!-- Lecteur Audio -->
                <div id="audioPlayerContainer" class="text-center overflow-hidden mb-4 d-none">
                    <div class="audio-icon-large mb-3">
                        <i class="fas fa-music fa-4x text-primary"></i>
                    </div>
                    <audio id="modalAudioPlayer" controls class="w-100 custom-audio-player">
                        Votre navigateur ne supporte pas l'élément audio.
                    </audio>
                </div>
                
                <!-- Lecteur Vidéo -->
                <div id="videoPlayerContainer" class="text-center overflow-hidden mb-4 d-none">
                    <video id="modalVideoPlayer" controls class="w-100" style="max-height: 400px;">
                        Votre navigateur ne supporte pas l'élément vidéo.
                    </video>
                </div>
                
                <!-- Indicateur de type de média -->
                <div class="text-center mb-3">
                    <span id="mediaTypeBadge" class="badge badge-pill badge-info"></span>
                </div>
                
                <!-- Infos information importante -->
                <div class="mt-2 text-justify">
                    <h4 id="infoTitle" class="font-weight-bold text-center mb-3"></h4>
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">
                                <i class="fas fa-info-circle mr-2"></i>Description
                            </h6>
                        </div>
                        <div class="card-body">
                            <p id="infoDescription" class="text-muted mb-0"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles supplémentaires pour le modal de visualisation */
.custom-audio-player {
    border-radius: 10px;
    background: transparent;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.audio-icon-large {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    background-clip: text;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
    100% {
        transform: scale(1);
    }
}

#modalVideoPlayer {
    border-radius: 10px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.modal-header {
    border-bottom: 2px solid rgba(255, 255, 255, 0.2);
}

.card {
    border: none;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
    border-radius: 10px;
}

.card-header {
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    border-radius: 10px 10px 0 0 !important;
}

#mediaTypeBadge {
    font-size: 0.9rem;
    padding: 0.5rem 1rem;
    letter-spacing: 0.5px;
    font-weight: 600;
}
</style>