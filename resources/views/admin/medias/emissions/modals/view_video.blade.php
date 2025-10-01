<!-- Modal pour visualiser la vidéo (aligné sur module vidéos) -->
<div class="modal fade" id="videoViewModal" tabindex="-1" role="dialog" aria-labelledby="videoViewModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-static" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title mb-2" id="videoViewModalLabel">Vidéo</h5>
                <button type="button" class="close text-white fw-bold" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <!-- Lecteur Vidéo Fichier -->
                <div id="videoPlayerContainer" class="text-center overflow-hidden mb-4 d-none">
                    <video id="modalVideoPlayer" controls class="w-100">
                        Votre navigateur ne supporte pas l'élément vidéo.
                    </video>
                </div>
                
                <!-- Lecteur Vidéo Lien (iframe) -->
                <div id="iframePlayerContainer" class="text-center overflow-hidden mb-4 d-none">
                    <div class="embed-responsive embed-responsive-16by9">
                        <iframe id="modalIframePlayer" class="embed-responsive-item" 
                            frameborder="0" allowfullscreen allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture">
                        </iframe>
                    </div>
                </div>
                
                <!-- Indicateur de type de média -->
                <div class="text-center mb-3">
                    <span id="mediaTypeBadge" class="badge badge-pill badge-info"></span>
                </div>
                
                <!-- Infos vidéo -->
                <div class="mt-2 text-justify">
                    <h4 id="videoTitle" class="font-weight-bold text-center"></h4>
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Description</h6>
                        </div>
                        <div class="card-body">
                            <p id="videoDescription" class="text-muted mb-0"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
