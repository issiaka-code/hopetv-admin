<div class="modal fade" id="viewEtabModal" tabindex="-1" role="dialog" aria-labelledby="viewEtabModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="viewEtabModalLabel">Détails de l'établissement</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-5 d-flex align-items-center justify-content-center">
            <img id="viewEtabImage" src="" alt="Image" class="img-fluid rounded" style="max-height: 260px; display: none;">
          </div>
          <div class="col-md-7">
            <h5 id="viewEtabNom" class="mb-2"></h5>
            <p class="mb-1"><strong>Type:</strong> <span id="viewEtabType"></span></p>
            <p class="mb-1"><strong>Téléphone:</strong> <span id="viewEtabTel"></span></p>
            <p class="mb-1"><strong>Email:</strong> <span id="viewEtabEmail"></span></p>
            <p class="mb-1"><strong>Adresse:</strong> <span id="viewEtabAdresse"></span></p>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary bg-secondary" data-dismiss="modal">Fermer</button>
      </div>
    </div>
  </div>
</div>
