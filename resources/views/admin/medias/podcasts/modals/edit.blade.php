<!-- Modal pour ÉDITER un podcast -->
<div class="modal fade" id="editPodcastModal" tabindex="-1" role="dialog" aria-labelledby="editPodcastModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-static" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-dark">
                <h5 class="modal-title" id="editPodcastModalLabel">Modifier le podcast</h5>
                <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editPodcastForm" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="form-group">
                        <label class="font-weight-bold">Nom <span class="text-danger">*</span></label>
                        <input type="text" name="nom" id="editPodcastnom"
                            class="form-control @error('nom') is-invalid @enderror" required>
                        @error('nom')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Description <span class="text-danger">*</span></label>
                        <textarea name="description" id="editPodcastDescription"
                            class="form-control @error('description') is-invalid @enderror" rows="3" required></textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Sélection du type de média pour ÉDITION -->
                    <div class="form-group">
                        <label class="font-weight-bold">Type de média <span class="text-danger">*</span></label>
                        <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                            <label class="btn btn-outline-primary" id="editMediaTypeAudioLabel">
                                <input type="radio" name="media_type" id="editMediaTypeAudio" value="audio" autocomplete="off">
                                <i class="fas fa-music"></i> Audio
                            </label>
                            <label class="btn btn-outline-primary" id="editMediaTypeVideoFileLabel">
                                <input type="radio" name="media_type" id="editMediaTypeVideoFile" value="video_file" autocomplete="off">
                                <i class="fas fa-video"></i> Fichier vidéo
                            </label>
                            <label class="btn btn-outline-primary" id="editMediaTypeVideoLinkLabel">
                                <input type="radio" name="media_type" id="editMediaTypeVideoLink" value="video_link" autocomplete="off">
                                <i class="fas fa-link"></i> Lien vidéo
                            </label>
                            <label class="btn btn-outline-primary" id="editMediaTypePdfLabel">
                                <input type="radio" name="media_type" id="editMediaTypePdf" value="pdf" autocomplete="off">
                                <i class="fas fa-file-pdf"></i> PDF
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
                                <label class="custom-file-label" for="editAudioFile">Choisir un nouveau fichier audio</label>
                                @error('fichier_audio')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="form-text text-muted">Formats acceptés: MP3, WAV, AAC, etc. (max 50MB)</small>

                            <div id="editCurrentAudio" class="mt-2">
                                <small>Fichier actuel: <span id="editCurrentAudioName"></span></small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="font-weight-bold">Image de couverture <span class="text-danger">*</span></label>
                            <div class="custom-file">
                                <input type="file" name="image_couverture_audio" id="editAudioThumbnail"
                                    class="custom-file-input @error('image_couverture_audio') is-invalid @enderror"
                                    accept="image/*">
                                <label class="custom-file-label" for="editAudioThumbnail">Choisir une nouvelle image</label>
                                @error('image_couverture_audio')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="form-text text-muted">Formats acceptés: JPG, PNG, GIF (max 2MB)</small>
                            
                            <div id="editCurrentAudioThumbnail" class="mt-2">
                                <small>Image actuelle: <span id="editCurrentAudioThumbnailName"></span></small>
                                <div class="mt-1">
                                    <img id="editCurrentAudioThumbnailPreview" src="" alt="Aperçu" class="img-thumbnail" style="max-width: 100px; max-height: 60px; display: none;">
                                </div>
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
                                <label class="custom-file-label" for="editVideoFile">Choisir un nouveau fichier vidéo</label>
                                @error('fichier_video')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="form-text text-muted">Formats acceptés: MP4, AVI, MOV, etc. (max 100MB)</small>

                            <div id="editCurrentVideo" class="mt-2">
                                <small>Fichier actuel: <span id="editCurrentVideoName"></span></small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="font-weight-bold">Image de couverture <span class="text-danger">*</span></label>
                            <div class="custom-file">
                                <input type="file" name="image_couverture_video" id="editVideoThumbnail"
                                    class="custom-file-input @error('image_couverture_video') is-invalid @enderror"
                                    accept="image/*">
                                <label class="custom-file-label" for="editVideoThumbnail">Choisir une nouvelle image</label>
                                @error('image_couverture_video')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="form-text text-muted">Formats acceptés: JPG, PNG, GIF (max 2MB)</small>
                            
                            <div id="editCurrentVideoThumbnail" class="mt-2">
                                <small>Image actuelle: <span id="editCurrentVideoThumbnailName"></span></small>
                                <div class="mt-1">
                                    <img id="editCurrentVideoThumbnailPreview" src="" alt="Aperçu" class="img-thumbnail" style="max-width: 100px; max-height: 60px; display: none;">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section Lien Vidéo pour ÉDITION -->
                    <div id="editVideoLinkSection" class="d-none">
                        <div class="form-group">
                            <label class="font-weight-bold">Lien Vidéo <span class="text-danger">*</span></label>
                            <input type="url" name="lien_video" id="editVideoLink"
                                class="form-control @error('lien_video') is-invalid @enderror"
                                placeholder="https://www.youtube.com/watch?v=..." pattern="https?://.+" 
                                title="Veuillez entrer une URL valide commençant par http:// ou https://">
                            @error('lien_video')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">URL YouTube, Vimeo, ou autre service de streaming vidéo</small>

                            <div id="editCurrentLink" class="mt-2">
                                <small>Lien actuel: <span id="editCurrentLinkValue"></span></small>
                            </div>
                        </div>
                    </div>

                    <!-- Section Fichier PDF pour ÉDITION -->
                    <div id="editPdfFileSection" class="d-none">
                        <div class="form-group">
                            <label class="font-weight-bold">Fichier PDF</label>
                            <div class="custom-file">
                                <input type="file" name="fichier_pdf" id="editPdfFile"
                                    class="custom-file-input @error('fichier_pdf') is-invalid @enderror"
                                    accept=".pdf">
                                <label class="custom-file-label" for="editPdfFile">Choisir un nouveau fichier PDF</label>
                                @error('fichier_pdf')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="form-text text-muted">Format accepté: PDF (max 20MB)</small>

                            <div id="editCurrentPdf" class="mt-2">
                                <small>Fichier actuel: <span id="editCurrentPdfName"></span></small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="font-weight-bold">Image de couverture</label>
                            <div class="custom-file">
                                <input type="file" name="image_couverture_pdf" id="editPdfThumbnail"
                                    class="custom-file-input @error('image_couverture_pdf') is-invalid @enderror"
                                    accept="image/*">
                                <label class="custom-file-label" for="editPdfThumbnail">Choisir une nouvelle image</label>
                                @error('image_couverture_pdf')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="form-text text-muted">Formats acceptés: JPG, PNG, GIF (max 2MB)</small>
                            
                            <div id="editCurrentPdfThumbnail" class="mt-2">
                                <small>Image actuelle: <span id="editCurrentPdfThumbnailName"></span></small>
                                <div class="mt-1">
                                    <img id="editCurrentPdfThumbnailPreview" src="" alt="Aperçu" class="img-thumbnail" style="max-width: 100px; max-height: 60px; display: none;">
                                </div>
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