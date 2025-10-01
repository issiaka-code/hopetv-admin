<div class="modal fade" id="editEmissionVideoModal" tabindex="-1" role="dialog" aria-labelledby="editEmissionVideoModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-static" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-dark">
                <h5 class="modal-title" id="editEmissionVideoModalLabel">Modifier la vidéo</h5>
                <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editEmissionVideoForm" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="form-group">
                        <label class="font-weight-bold">Titre <span class="text-danger">*</span></label>
                        <input type="text" name="titre_video" id="editEmissionVideoTitre"
                            class="form-control @error('titre_video') is-invalid @enderror" required>
                        @error('titre_video')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Description</label>
                        <textarea name="description_video" id="editEmissionVideoDescription"
                            class="form-control @error('description_video') is-invalid @enderror" rows="3"></textarea>
                        @error('description_video')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Type de vidéo <span class="text-danger">*</span></label>
                        <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                            <label class="btn btn-outline-info active" id="editEmissionVideoTypeFileLabel">
                                <input type="radio" name="type_video" id="editEmissionVideoTypeFile" value="upload"
                                    autocomplete="off" checked> Fichier
                            </label>
                            <label class="btn btn-outline-info" id="editEmissionVideoTypeLinkLabel">
                                <input type="radio" name="type_video" id="editEmissionVideoTypeLink" value="link"
                                    autocomplete="off"> Lien
                            </label>
                        </div>
                    </div>

                    <!-- Section Fichier Vidéo pour ÉDITION -->
                    <div id="editEmissionVideoFileSection">
                        <div class="form-group">
                            <label class="font-weight-bold">Fichier Vidéo</label>
                            <div class="custom-file">
                                <input type="file" name="video_file" id="editEmissionVideoFichier"
                                    class="custom-file-input @error('video_file') is-invalid @enderror"
                                    accept="video/*">
                                <label class="custom-file-label" for="editEmissionVideoFichier">Choisir un nouveau fichier</label>
                                @error('video_file')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="form-text text-muted">Formats acceptés: MP4, MOV, OGG, QT. (max 100MB)</small>
                        </div>
                    </div>

                    <!-- Section Lien Vidéo pour ÉDITION -->
                    <div id="editEmissionVideoLinkSection" style="display: none;">
                        <div class="form-group">
                            <label class="font-weight-bold">URL de la vidéo <span class="text-danger">*</span></label>
                            <input type="url" name="video_url" id="editEmissionVideoUrl"
                                class="form-control @error('video_url') is-invalid @enderror">
                            @error('video_url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Miniature</label>
                        <div class="custom-file">
                            <input type="file" name="thumbnail" id="editEmissionVideoThumbnail"
                                class="custom-file-input @error('thumbnail') is-invalid @enderror"
                                accept="image/*">
                            <label class="custom-file-label" for="editEmissionVideoThumbnail">Choisir une image</label>
                            @error('thumbnail')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <small class="form-text text-muted">Formats acceptés: JPEG, PNG, JPG, GIF, SVG. (max 2MB)</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-info">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    $(function () {
        $('input[name="type_video"]').change(function () {
            if ($(this).val() === 'upload') {
                $('#editEmissionVideoFileSection').show();
                $('#editEmissionVideoLinkSection').hide();
            } else {
                $('#editEmissionVideoFileSection').hide();
                $('#editEmissionVideoLinkSection').show();
            }
        });
        // Affichage du nom du fichier sélectionné
        $('.custom-file-input').on('change', function () {
            var fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').addClass("selected").html(fileName);
        });
    });
</script>
