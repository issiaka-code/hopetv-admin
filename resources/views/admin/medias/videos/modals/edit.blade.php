    <!-- Modal pour ÉDITER une vidéo -->
    <div class="modal fade" id="editVideoModal" tabindex="-1" role="dialog" aria-labelledby="editVideoModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-static" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info text-dark">
                    <h5 class="modal-title" id="editVideoModalLabel">Modifier la vidéo</h5>
                    <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="editVideoForm" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="font-weight-bold">Nom <span class="text-danger">*</span></label>
                            <input type="text" name="nom" id="editVideoNom"
                                class="form-control @error('nom') is-invalid @enderror" required>
                            @error('nom')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Description <span class="text-danger">*</span></label>
                            <textarea name="description" id="editVideoDescription"
                                class="form-control @error('description') is-invalid @enderror" rows="3" required></textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Type de vidéo <span class="text-danger">*</span></label>
                            <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                                <label class="btn btn-outline-info active" id="editVideoTypeFileLabel">
                                    <input type="radio" name="video_type" id="editVideoTypeFile" value="file"
                                        autocomplete="off" checked> Fichier
                                </label>
                                <label class="btn btn-outline-info" id="editVideoTypeLinkLabel">
                                    <input type="radio" name="video_type" id="editVideoTypeLink" value="link"
                                        autocomplete="off"> Lien
                                </label>
                            </div>
                        </div>

                        <!-- Section Fichier Vidéo pour ÉDITION -->
                        <div id="editVideoFileSection">
                            <div class="form-group">
                                <label class="font-weight-bold">Fichier Vidéo</label>
                                <div class="custom-file">
                                    <input type="file" name="fichier_video" id="editVideoFichier"
                                        class="custom-file-input @error('fichier_video') is-invalid @enderror"
                                        accept="video/*">
                                    <label class="custom-file-label" for="editVideoFichier">Choisir un nouveau
                                        fichier</label>
                                    @error('fichier_video')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="form-text text-muted">Formats acceptés: MP4, AVI, MOV, etc. (max
                                    100MB)</small>

                                <div id="editCurrentVideo" class="mt-2">
                                    <small>Fichier actuel: <span id="editCurrentVideoName"></span></small>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="font-weight-bold">Image de couverture</label>
                                <div class="custom-file">
                                    <input type="file" name="image_couverture" id="editVideoThumbnail"
                                        class="custom-file-input @error('image_couverture') is-invalid @enderror"
                                        accept="image/*">
                                    <label class="custom-file-label" for="editVideoThumbnail">Choisir une nouvelle image</label>
                                    @error('image_couverture')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="form-text text-muted">Formats acceptés: JPG, PNG, GIF (max 2MB)</small>
                                
                                <div id="editCurrentThumbnail" class="mt-2">
                                    <small>Image actuelle: <span id="editCurrentThumbnailName"></span></small>
                                    <div class="mt-1">
                                        <img id="editCurrentThumbnailPreview" src="" alt="Aperçu" class="img-thumbnail" style="max-width: 100px; max-height: 60px; display: none;">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section Lien Vidéo pour ÉDITION -->
                        <div id="editVideoLinkSection" class="d-none">
                            <div class="form-group">
                                <label class="font-weight-bold">Lien de la vidéo <span
                                        class="text-danger">*</span></label>
                                <input type="url" name="lien_video" id="editVideoLink"
                                    class="form-control @error('lien_video') is-invalid @enderror"
                                    placeholder="https://example.com/video.mp4">
                                @error('lien_video')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Entrez l'URL complète de la vidéo</small>

                                <div id="editCurrentLink" class="mt-2">
                                    <small>Lien actuel: <span id="editCurrentLinkValue"></span></small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary bg-secondary" data-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-info bg-info">
                            <i class="fas fa-save"></i> Mettre à jour
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>