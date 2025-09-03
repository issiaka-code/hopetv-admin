<div class="modal fade" id="addInfoModal" tabindex="-1" role="dialog" aria-labelledby="addInfoModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-static" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addInfoModalLabel">Ajouter une information importante</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="addInfoForm" method="POST" action="{{ route('info_importantes.store') }}"
                enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="font-weight-bold">Nom <span class="text-danger">*</span></label>
                        <input type="text" name="nom" id="addInfoNom"
                            class="form-control @error('nom') is-invalid @enderror" required>
                        @error('nom')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Description <span class="text-danger">*</span></label>
                        <textarea name="description" id="addInfoDescription" class="form-control @error('description') is-invalid @enderror"
                            rows="3" required></textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Sélection du type de média -->
                    <div class="form-group">
                        <label class="font-weight-bold">Type de média <span class="text-danger">*</span></label>
                        <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                            <label class="btn btn-outline-primary active" id="addMediaTypeAudioLabel">
                                <input type="radio" name="media_type" id="addMediaTypeAudio" value="audio"
                                    autocomplete="off" checked>
                                <i class="fas fa-music mr-1"></i> Audio
                            </label>
                            <label class="btn btn-outline-primary" id="addMediaTypeVideoLabel">
                                <input type="radio" name="media_type" id="addMediaTypeVideo" value="video"
                                    autocomplete="off">
                                <i class="fas fa-video mr-1"></i> Vidéo
                            </label>
                        </div>
                    </div>

                    <!-- Section Fichier Audio -->
                    <div id="addAudioFileSection">
                        <div class="form-group">
                            <label class="font-weight-bold">Fichier Audio <span class="text-danger">*</span></label>
                            <div class="custom-file">
                                <input type="file" name="fichier_audio" id="addAudioFile"
                                    class="custom-file-input @error('fichier_audio') is-invalid @enderror"
                                    accept="audio/*" required>
                                <label class="custom-file-label" for="addAudioFile">Choisir un fichier audio</label>
                                @error('fichier_audio')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="form-text text-muted">Formats acceptés: MP3, WAV, AAC, etc. (max 50MB)</small>
                        </div>
                    </div>

                    <!-- Section Fichier Vidéo -->
                    <div id="addVideoFileSection" class="d-none">
                        <div class="form-group">
                            <label class="font-weight-bold">Fichier Vidéo <span class="text-danger">*</span></label>
                            <div class="custom-file">
                                <input type="file" name="fichier_video" id="addVideoFile"
                                    class="custom-file-input @error('fichier_video') is-invalid @enderror"
                                    accept="video/*">
                                <label class="custom-file-label" for="addVideoFile">Choisir un fichier vidéo</label>
                                @error('fichier_video')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="form-text text-muted">Formats acceptés: MP4, AVI, MOV, etc. (max
                                100MB)</small>
                        </div>
                    </div>

                    <!-- Status de l'information -->
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <!-- valeur par défaut false -->
                            <input type="hidden" name="is_active" value="0">

                            <!-- checkbox -->
                            <input type="checkbox" class="custom-control-input" name="is_active" id="addInfoIsActive"
                                value="1" checked>
                            <label class="custom-control-label font-weight-bold" for="addInfoIsActive">Information
                                active</label>
                        </div>
                        <small class="form-text text-muted">
                            Si désactivé, l'information ne sera pas visible publiquement
                        </small>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary bg-secondary"
                        data-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> Ajouter
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
