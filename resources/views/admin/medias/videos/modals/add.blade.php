
    <div class="modal fade" id="addVideoModal" tabindex="-1" role="dialog" aria-labelledby="addVideoModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-static" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addVideoModalLabel">Ajouter une vidéo</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="addVideoForm" method="POST" action="{{ route('videos.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="font-weight-bold">Nom <span class="text-danger">*</span></label>
                            <input type="text" name="nom" id="addVideoNom"
                                class="form-control @error('nom') is-invalid @enderror" required>
                            @error('nom')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Description <span class="text-danger">*</span></label>
                            <textarea name="description" id="addVideoDescription" class="form-control @error('description') is-invalid @enderror"
                                rows="3" required></textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Type de vidéo <span class="text-danger">*</span></label>
                            <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                                <label class="btn btn-outline-primary active">
                                    <input type="radio" name="video_type" id="addVideoTypeFile" value="file"
                                        autocomplete="off" checked> Fichier
                                </label>
                                <label class="btn btn-outline-primary">
                                    <input type="radio" name="video_type" id="addVideoTypeLink" value="link"
                                        autocomplete="off"> Lien
                                </label>
                            </div>
                        </div>

                        <!-- Section Fichier Vidéo pour AJOUT -->
                        <div id="addVideoFileSection">
                            <div class="form-group">
                                <label class="font-weight-bold">Fichier Vidéo <span class="text-danger">*</span></label>
                                <div class="custom-file">
                                    <input type="file" name="fichier_video" id="addVideoFichier"
                                        class="custom-file-input @error('fichier_video') is-invalid @enderror"
                                        accept="video/*" required>
                                    <label class="custom-file-label" for="addVideoFichier">Choisir un fichier</label>
                                    @error('fichier_video')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="form-text text-muted">Formats acceptés: MP4, AVI, MOV, etc. (max
                                    100MB)</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="font-weight-bold">Image de couverture <span class="text-danger">*</span></label>
                                <div class="custom-file">
                                    <input type="file" name="image_couverture" id="addVideoThumbnail"
                                        class="custom-file-input @error('image_couverture') is-invalid @enderror"
                                        accept="image/*" required>
                                    <label class="custom-file-label" for="addVideoThumbnail">Choisir une image</label>
                                    @error('image_couverture')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="form-text text-muted">Formats acceptés: JPG, PNG, GIF (max 2MB)</small>
                            </div>
                        </div>

                        <!-- Section Lien Vidéo pour AJOUT -->
                        <div id="addVideoLinkSection" class="d-none">
                            <div class="form-group">
                                <label class="font-weight-bold">Lien de la vidéo <span
                                        class="text-danger">*</span></label>
                                <input type="url" name="lien_video" id="addVideoLink"
                                    class="form-control @error('lien_video') is-invalid @enderror"
                                    placeholder="https://example.com/video.mp4">
                                @error('lien_video')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Entrez l'URL complète de la vidéo (YouTube, Vimeo, ou
                                    lien direct)</small>
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