<div class="modal fade" id="editEtabModal" tabindex="-1" role="dialog" aria-labelledby="editEtabModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-static" role="document">
    <div class="modal-content">
      <div class="modal-header bg-info text-dark">
        <h5 class="modal-title" id="editEtabModalLabel">Modifier l'établissement</h5>
        <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="editEtabForm" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="modal-body">
          <div class="form-group">
            <label class="font-weight-bold">Type <span class="text-danger">*</span></label>
            <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
              <label class="btn btn-outline-primary" id="editTypeSiegeLabel">
                <input type="radio" name="type" id="editTypeSiege" value="siege" autocomplete="off">
                <i class="fas fa-university mr-1"></i> Siège
              </label>
              <label class="btn btn-outline-primary" id="editTypeAnnexeLabel">
                <input type="radio" name="type" id="editTypeAnnexe" value="annexe" autocomplete="off">
                <i class="fas fa-building mr-1"></i> Annexe
              </label>
            </div>
          </div>

          <div class="form-group">
            <label class="font-weight-bold">Nom <span class="text-danger">*</span></label>
            <input type="text" name="nom" id="editNom" class="form-control @error('nom') is-invalid @enderror" required>
            @error('nom')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="form-row">
            <div class="form-group col-md-6">
              <label class="font-weight-bold">Téléphone</label>
              <input type="text" name="telephone" id="editTelephone" class="form-control @error('telephone') is-invalid @enderror">
              @error('telephone')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group col-md-6">
              <label class="font-weight-bold">Email</label>
              <input type="email" name="email" id="editEmail" class="form-control @error('email') is-invalid @enderror">
              @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>

          <div class="form-group">
            <label class="font-weight-bold">Adresse</label>
            <textarea name="adresse" id="editAdresse" class="form-control @error('adresse') is-invalid @enderror" rows="3" placeholder="Entrez l'adresse complète de l'établissement"></textarea>
            @error('adresse')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="form-group">
            <label class="font-weight-bold">Image</label>
            <div class="custom-file">
              <input type="file" name="image" id="editImage" class="custom-file-input @error('image') is-invalid @enderror" accept="image/*">
              <label class="custom-file-label" for="editImage">Choisir une nouvelle image</label>
              @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div id="editCurrentImage" class="mt-2">
              <small>Image actuelle: <span id="editCurrentImageName"></span></small>
              <div class="mt-1">
                <img id="editCurrentImagePreview" src="" alt="Aperçu" class="img-thumbnail" style="max-width: 120px; max-height: 80px; display: none;">
              </div>
            </div>
          </div>

          <div class="form-group form-check">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" class="form-check-input" id="editIsActive" name="is_active" value="1">
            <label class="form-check-label" for="editIsActive">Actif</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary bg-secondary" data-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-info bg-info"><i class="fas fa-save"></i> Mettre à jour</button>
        </div>
      </form>
    </div>
  </div>
</div>
