<div class="modal fade" id="addPodcastModal" tabindex="-1" role="dialog" aria-labelledby="addPodcastModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-static" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addPodcastModalLabel">Ajouter un podcast</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="addPodcastForm" method="POST" action="{{ route('podcasts.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="font-weight-bold">Titre <span class="text-danger">*</span></label>
                            <input type="text" name="nom" id="addPodcastTitre"
                                class="form-control @error('nom') is-invalid @enderror" required>
                            @error('nom')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Description <span class="text-danger">*</span></label>
                            <textarea name="description" id="addPodcastDescription" class="form-control @error('description') is-invalid @enderror"
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
                                    <input type="radio" name="media_type" id="addMediaTypeAudio" value="audio" autocomplete="off" checked>
                                    <i class="fas fa-music mr-1"></i> Audio
                                </label>
                                <label class="btn btn-outline-primary" id="addMediaTypeVideoFileLabel">
                                    <input type="radio" name="media_type" id="addMediaTypeVideoFile" value="video_file" autocomplete="off">
                                    <i class="fas fa-video mr-1"></i> Fichier vidéo
                                </label>
                                <label class="btn btn-outline-primary" id="addMediaTypeVideoLinkLabel">
                                    <input type="radio" name="media_type" id="addMediaTypeVideoLink" value="video_link" autocomplete="off">
                                    <i class="fas fa-link mr-1"></i> Lien vidéo
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
                            
                            <div class="form-group">
                                <label class="font-weight-bold">Image de couverture <span class="text-danger">*</span></label>
                                <div class="custom-file">
                                    <input type="file" name="image_couverture_audio" id="addAudioThumbnail"
                                        class="custom-file-input @error('image_couverture_audio') is-invalid @enderror"
                                        accept="image/*" required>
                                    <label class="custom-file-label" for="addAudioThumbnail">Choisir une image</label>
                                    @error('image_couverture_audio')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="form-text text-muted">Formats acceptés: JPG, PNG, GIF (max 2MB)</small>
                            </div>
                        </div>

                        <!-- Section Fichier Vidéo -->
                        <div id="addVideoFileSection" class="d-none">
                            <div class="form-group">
                                <label class="font-weight-bold">Fichier Vidéo <span class="text-danger">*</span></label>
                                <div class="custom-file">
                                    <input type="file" name="fichier_video" id="addVideoFile"
                                        class="custom-file-input @error('fichier_video') is-invalid @enderror"
                                        accept="video/*" required>
                                    <label class="custom-file-label" for="addVideoFile">Choisir un fichier vidéo</label>
                                    @error('fichier_video')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="form-text text-muted">Formats acceptés: MP4, AVI, MOV, etc. (max 100MB)</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="font-weight-bold">Image de couverture <span class="text-danger">*</span></label>
                                <div class="custom-file">
                                    <input type="file" name="image_couverture_video" id="addVideoThumbnail"
                                        class="custom-file-input @error('image_couverture_video') is-invalid @enderror"
                                        accept="image/*" required>
                                    <label class="custom-file-label" for="addVideoThumbnail">Choisir une image</label>
                                    @error('image_couverture_video')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="form-text text-muted">Formats acceptés: JPG, PNG, GIF (max 2MB)</small>
                            </div>
                        </div>

                        <!-- Section Lien Vidéo -->
                        <div id="addVideoLinkSection" class="d-none">
                            <div class="form-group">
                                <label class="font-weight-bold">Lien vidéo (YouTube, Vimeo, etc.) <span class="text-danger">*</span></label>
                                <input type="url" name="lien_video" id="addVideoLink"
                                    class="form-control @error('lien_video') is-invalid @enderror"
                                    placeholder="https://www.youtube.com/watch?v=..." pattern="https?://.+" title="Veuillez entrer une URL valide commençant par http:// ou https://" required>
                                @error('lien_video')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Collez le lien complet de la vidéo (YouTube, Vimeo, etc.)</small>
                            </div> 
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary bg-secondary" data-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i> Ajouter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>