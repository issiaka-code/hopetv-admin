<!-- Modal pour visualiser la vidéo -->
<div class="modal fade" id="videoViewModal" tabindex="-1" role="dialog" aria-labelledby="videoViewModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-static" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title mb-2" id="videoViewModalLabel">Visualisation de la vidéo</h5>
                <button type="button" class="close text-white fw-bold" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <!-- Lecteur vidéo -->
                <div id="videoPlayerContainer" class="text-center overflow-hidden">
                    <video id="modalVideoPlayer" controls class="w-100 d-none" style="height: 30vh; border-radius: 5px;"></video>
                    <iframe id="modalIframePlayer" class="w-100 d-none" style="height: 30vh; border-radius: 10px;"
                        allowfullscreen></iframe>
                </div>
                
                <!-- Infos vidéo -->
                <div class="mt-2 text-justify">
                    <h4 id="videoTitle" class="font-weight-bold text-center mb-3"></h4>
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
