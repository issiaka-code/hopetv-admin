<div class="modal fade" id="addEtabModal" tabindex="-1" role="dialog" aria-labelledby="addEtabModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-static" role="document">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="addEtabModalLabel">Ajouter un établissement</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="addEtabForm" method="POST" action="{{ route('etablissements.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="modal-body">
          <div class="form-group">
            <label class="font-weight-bold">Type <span class="text-danger">*</span></label>
            <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
              <label class="btn btn-outline-primary active" id="addTypeSiegeLabel">
                <input type="radio" name="type" id="addTypeSiege" value="siege" autocomplete="off" checked>
                <i class="fas fa-university mr-1"></i> Siège
              </label>
              <label class="btn btn-outline-primary" id="addTypeAnnexeLabel">
                <input type="radio" name="type" id="addTypeAnnexe" value="annexe" autocomplete="off">
                <i class="fas fa-building mr-1"></i> Annexe
              </label>
            </div>
          </div>

          <div class="form-group">
            <label class="font-weight-bold">Nom <span class="text-danger">*</span></label>
            <input type="text" name="nom" id="addNom" class="form-control @error('nom') is-invalid @enderror" required>
            @error('nom')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="form-row">
            <div class="form-group col-md-6">
              <label class="font-weight-bold">Téléphone</label>
              <input type="text" name="telephone" id="addTelephone" class="form-control @error('telephone') is-invalid @enderror">
              @error('telephone')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group col-md-6">
              <label class="font-weight-bold">Email</label>
              <input type="email" name="email" id="addEmail" class="form-control @error('email') is-invalid @enderror">
              @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>

          <div class="form-group">
            <label class="font-weight-bold">Adresse</label>
            <textarea name="adresse" id="addAdresse" class="form-control @error('adresse') is-invalid @enderror" rows="3" placeholder="Entrez l'adresse complète de l'établissement"></textarea>
            @error('adresse')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="form-group">
            <label class="font-weight-bold">Lien Google Maps (optionnel)</label>
            <input type="url" name="maps_link" id="addMapsLink" class="form-control @error('maps_link') is-invalid @enderror" placeholder="https://maps.google.com/...">
            @error('maps_link')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>



          <div class="form-group">
            <label class="font-weight-bold">Image <span class="text-danger">*</span></label>
            <div class="custom-file">
              <input type="file" name="image" id="addImage" class="custom-file-input @error('image') is-invalid @enderror" accept="image/*" required>
              <label class="custom-file-label" for="addImage">Choisir une image</label>
              @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <small class="form-text text-muted">Formats: JPG, PNG, GIF, WEBP (max 2MB)</small>
          </div>

          <div class="form-group form-check">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" class="form-check-input" id="addIsActive" name="is_active" value="1" checked>
            <label class="form-check-label" for="addIsActive">Actif</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary bg-secondary" data-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Ajouter</button>
        </div>
      </form>
    </div>
  </div>
</div>
