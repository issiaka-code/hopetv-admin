<div class="modal fade" id="addstoreModal" tabindex="-1" role="dialog" aria-labelledby="addstoreModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-static" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addstoreModalLabel">Ajouter un témoignage</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="addstoreForm" method="POST" action="{{ $route ?? '#' }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <!-- NOM -->
                    <div class="form-group">
                        <label class="font-weight-bold">Nom <span class="text-danger">*</span></label>
                        <input type="text" name="nom" id="addstoreNom" class="form-control @error('nom') is-invalid @enderror" required>
                        @error('nom')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- DESCRIPTION -->
                    <div class="form-group">
                        <label class="font-weight-bold">Description <span class="text-danger">*</span></label>
                        <textarea name="description" id="addstoreDescription" class="form-control @error('description') is-invalid @enderror" rows="3" required></textarea>
                        @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- ✅ TYPE DE MEDIA -->
                    <div class="form-group">
                        <label class="font-weight-bold">Type de média <span class="text-danger">*</span></label>
                        
                        <!-- ✅ Ajout important : data-toggle="buttons" -->
                        <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                            
                            <!-- Audio -->
                            <label class="btn btn-outline-primary" id="addMediaTypeAudioLabel">
                                <input type="radio" name="media_type" id="addMediaTypeAudio" value="audio" autocomplete="off">
                                <i class="fas fa-music mr-1"></i> Audio
                            </label>
                            
                            <!-- Vidéo Fichier -->
                            <label class="btn btn-outline-primary" id="addMediaTypeVideoFileLabel">
                                <input type="radio" name="media_type" id="addMediaTypeVideoFile" value="video_file" autocomplete="off">
                                <i class="fas fa-video mr-1"></i> Fichier vidéo
                            </label>
                            
                            <!-- Lien Vidéo -->
                            <label class="btn btn-outline-primary" id="addMediaTypeVideoLinkLabel">
                                <input type="radio" name="media_type" id="addMediaTypeVideoLink" value="video_link" autocomplete="off">
                                <i class="fas fa-link mr-1"></i> Lien vidéo
                            </label>
                            
                            <!-- PDF -->
                            <label class="btn btn-outline-primary" id="addMediaTypePdfLabel">
                                <input type="radio" name="media_type" id="addMediaTypePdf" value="pdf" autocomplete="off">
                                <i class="fas fa-file-pdf mr-1"></i> PDF
                            </label>
                            
                            <!-- Images -->
                            <label class="btn btn-outline-primary" id="addMediaTypeImagesLabel">
                                <input type="radio" name="media_type" id="addMediaTypeImages" value="images" autocomplete="off">
                                <i class="fas fa-images mr-1"></i> Images
                            </label>
                        </div>
                    </div>

                    <!-- ✅ SECTION AUDIO -->
                    <div id="addAudioFileSection" class="media-section">
                        <div class="form-group">
                            <label class="font-weight-bold">Fichier Audio <span class="text-danger">*</span></label>
                            <div class="custom-file">
                                <input type="file" name="fichier_audio" id="addAudioFile" class="custom-file-input @error('fichier_audio') is-invalid @enderror" accept="audio/*" required>
                                <label class="custom-file-label" for="addAudioFile">Choisir un fichier audio</label>
                            </div>
                            <small class="form-text text-muted">Formats: MP3, WAV, AAC...</small>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Image de couverture</label>
                            <div class="custom-file">
                                <input type="file" name="image_couverture_audio" id="addAudioImageFile" class="custom-file-input" accept="image/*">
                                <label class="custom-file-label" for="addAudioImageFile">Choisir une image</label>
                            </div>
                        </div>
                    </div>

                    <!-- ✅ SECTION VIDEO FICHIER -->
                    <div id="addVideoFileSection" class="media-section d-none">
                        <div class="form-group">
                            <label class="font-weight-bold">Fichier Vidéo</label>
                            <div class="custom-file">
                                <input type="file" name="fichier_video" id="addVideoFile" class="custom-file-input" accept="video/*">
                                <label class="custom-file-label" for="addVideoFile">Choisir un fichier vidéo</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Image de couverture</label>
                            <div class="custom-file">
                                <input type="file" name="image_couverture_video" id="addVideoImageFile" class="custom-file-input" accept="image/*">
                                <label class="custom-file-label" for="addVideoImageFile">Choisir une image</label>
                            </div>
                        </div>
                    </div>

                    <!-- ✅ SECTION VIDEO LIEN -->
                    <div id="addVideoLinkSection" class="media-section d-none">
                        <div class="form-group">
                            <label class="font-weight-bold">Lien Vidéo</label>
                            <input type="url" name="lien_video" id="addVideoLink" class="form-control" placeholder="https://www.youtube.com/watch?v=...">
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Image de couverture</label>
                            <div class="custom-file">
                                <input type="file" name="image_couverture_link" id="addlinkImageFile" class="custom-file-input" accept="image/*">
                                <label class="custom-file-label" for="addlinkImageFile">Choisir une image</label>
                            </div>
                        </div>
                    </div>

                    <!-- ✅ SECTION PDF -->
                    <div id="addPdfFileSection" class="media-section d-none">
                        <div class="form-group">
                            <label class="font-weight-bold">Fichier PDF</label>
                            <div class="custom-file">
                                <input type="file" name="fichier_pdf" id="addPdfFile" class="custom-file-input" accept=".pdf">
                                <label class="custom-file-label" for="addPdfFile">Choisir un fichier PDF</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Image de couverture</label>
                            <div class="custom-file">
                                <input type="file" name="image_couverture_pdf" id="addPdfImageFile" class="custom-file-input" accept="image/*">
                                <label class="custom-file-label" for="addPdfImageFile">Choisir une image</label>
                            </div>
                        </div>
                    </div>

                    <!-- ✅ SECTION IMAGES -->
                    <div id="addImageFileSection" class="media-section d-none">
                        <div class="form-group">
                            <label class="font-weight-bold">Images multiples</label>
                            <div class="custom-file">
                                <input type="file" name="images[]" id="addImageFiles" class="custom-file-input" multiple accept="image/*">
                                <label class="custom-file-label" for="addImageFiles">Choisir des images</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Image de couverture</label>
                            <div class="custom-file">
                                <input type="file" name="image_couverture_images" id="addImageCoverFile" class="custom-file-input" accept="image/*">
                                <label class="custom-file-label" for="addImageCoverFile">Choisir une image de couverture</label>
                            </div>
                        </div>
                    </div>
                </div>

                <input type="hidden" id="input-emission-id" name="inputemissionid" >
                <!-- ✅ FOOTER -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary bg-secondary" data-dismiss="modal">Annuler</button>
                    <button type="submit" onclick="this.disabled=true; this.form.submit();" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> Ajouter
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
