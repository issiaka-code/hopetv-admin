<div class="modal fade" id="addEmissionVideoModal" tabindex="-1" role="dialog" aria-labelledby="addEmissionVideoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEmissionVideoModalLabel">Ajouter une Vidéo</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('emissions.items.store', $emission->id) }}" method="POST" enctype="multipart/form-data" id="addVideoForm">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="titre_video">Titre de la vidéo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="titre_video" name="titre_video" required>
                    </div>
                    <div class="form-group">
                        <label for="description_video">Description</label>
                        <textarea class="form-control" id="description_video" name="description_video" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Type de vidéo <span class="text-danger">*</span></label>
                        <div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" id="type_upload" name="type_video" class="custom-control-input" value="upload" checked>
                                <label class="custom-control-label" for="type_upload">Uploader un fichier</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" id="type_link" name="type_video" class="custom-control-input" value="link">
                                <label class="custom-control-label" for="type_link">Lien externe (YouTube, etc.)</label>
                            </div>
                        </div>
                    </div>

                    <div class="video-upload-section">
                        <div class="form-group">
                            <label for="video_file">Fichier vidéo <span class="text-danger">*</span></label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="video_file" name="video_file" accept="video/*" required>
                                <label class="custom-file-label" for="video_file">Choisir un fichier...</label>
                            </div>
                            <small class="form-text text-muted">Formats acceptés: MP4, AVI, MOV, WMV (max 100MB)</small>
                        </div>
                    </div>

                    <div class="video-link-section" style="display: none;">
                        <div class="form-group">
                            <label for="video_url">Lien de la vidéo <span class="text-danger">*</span></label>
                            <input type="url" class="form-control" id="video_url" name="video_url" placeholder="https://www.youtube.com/watch?v=...">
                        </div>
                    </div>

                    <div class="form-group thumbnail-section">
                        <label for="thumbnail">Image de couverture</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="thumbnail" name="thumbnail" accept="image/*">
                            <label class="custom-file-label" for="thumbnail">Choisir une image...</label>
                        </div>
                        <small class="form-text text-muted">Si vous n'en fournissez pas, une miniature sera générée automatiquement pour certains liens.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn bg-secondary" data-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Ajouter la vidéo</button>
                </div>
            </form>
        </div>
    </div>
</div>