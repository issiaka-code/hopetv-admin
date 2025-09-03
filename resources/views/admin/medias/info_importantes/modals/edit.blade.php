<!-- Modal pour ÉDITER une information importante -->
<div class="modal fade" id="editInfoModal" tabindex="-1" role="dialog" aria-labelledby="editInfoModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-static" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-dark">
                <h5 class="modal-title" id="editInfoModalLabel">Modifier l'information importante</h5>
                <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editInfoForm" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="form-group">
                        <label class="font-weight-bold">Nom <span class="text-danger">*</span></label>
                        <input type="text" name="nom" id="editInfoNom"
                            class="form-control @error('nom') is-invalid @enderror" required>
                        @error('nom')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Description <span class="text-danger">*</span></label>
                        <textarea name="description" id="editInfoDescription" class="form-control @error('description') is-invalid @enderror"
                            rows="3" required></textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Sélection du type de média pour ÉDITION -->
                    <div class="form-group">
                        <label class="font-weight-bold">Type de média <span class="text-danger">*</span></label>
                        <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                            <label class="btn btn-outline-primary" id="editMediaTypeAudioLabel">
                                <input type="radio" name="media_type" id="editMediaTypeAudio" value="audio"
                                    autocomplete="off">
                                <i class="fas fa-music mr-1"></i> Audio
                            </label>
                            <label class="btn btn-outline-primary" id="editMediaTypeVideoLabel">
                                <input type="radio" name="media_type" id="editMediaTypeVideo" value="video"
                                    autocomplete="off">
                                <i class="fas fa-video mr-1"></i> Vidéo
                            </label>
                        </div>
                    </div>

                    <!-- Section Fichier Audio pour ÉDITION -->
                    <div id="editAudioFileSection" class="d-none">
                        <div class="form-group">
                            <label class="font-weight-bold">Fichier Audio</label>
                            <div class="custom-file">
                                <input type="file" name="fichier_audio" id="editAudioFile"
                                    class="custom-file-input @error('fichier_audio') is-invalid @enderror"
                                    accept="audio/*">
                                <label class="custom-file-label" for="editAudioFile">Choisir un nouveau fichier
                                    audio</label>
                                @error('fichier_audio')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="form-text text-muted">Formats acceptés: MP3, WAV, AAC, etc. (max 50MB)</small>

                            <div id="editCurrentAudio" class="mt-2">
                                <small class="text-muted">Fichier actuel: <strong
                                        id="editCurrentAudioName"></strong></small>
                            </div>
                        </div>
                    </div>

                    <!-- Section Fichier Vidéo pour ÉDITION -->
                    <div id="editVideoFileSection" class="d-none">
                        <div class="form-group">
                            <label class="font-weight-bold">Fichier Vidéo</label>
                            <div class="custom-file">
                                <input type="file" name="fichier_video" id="editVideoFile"
                                    class="custom-file-input @error('fichier_video') is-invalid @enderror"
                                    accept="video/*">
                                <label class="custom-file-label" for="editVideoFile">Choisir un nouveau fichier
                                    vidéo</label>
                                @error('fichier_video')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="form-text text-muted">Formats acceptés: MP4, AVI, MOV, etc. (max
                                100MB)</small>

                            <div id="editCurrentVideo" class="mt-2">
                                <small class="text-muted">Fichier actuel: <strong
                                        id="editCurrentVideoName"></strong></small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary bg-secondary"
                        data-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-info bg-info">
                        <i class="fas fa-save"></i> Mettre à jour
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
