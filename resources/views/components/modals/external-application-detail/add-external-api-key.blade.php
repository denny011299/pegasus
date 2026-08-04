  <div class="modal modal-lg custom-modal fade" id="add_external_api_key" role="dialog"
    data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-md">
      <div class="modal-content">
        <div class="modal-header border-0 pb-0">
          <div class="form-header modal-header-title text-start mb-0">
            <h4 class="mb-0 modal-title">Buat API Key</h4>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
          </button>
        </div>
        <form action="#">
          <div class="modal-body">
            <div class="form-groups-item border-0 pb-0">
              <div class="row">
                <div class="col-md-6">
                  <div class="input-block mb-3">
                    <label>Nama Kunci<span class="text-danger">*</span></label>
                    <input type="text" class="form-control fill" id="key_name"
                      placeholder="Contoh: Production">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="input-block mb-3">
                    <label>Lingkungan</label>
                    <select class="form-select" id="environment">
                      <option value="production">Production</option>
                      <option value="staging">Staging</option>
                      <option value="development">Development</option>
                    </select>
                  </div>
                </div>
                <div class="col-12">
                  <div class="input-block mb-2">
                    <label class="custom_check">
                      <input type="checkbox" id="never_expire" checked>
                      <span class="checkmark"></span>
                    </label>
                    <span class="ms-1">Tidak pernah kedaluwarsa</span>
                  </div>
                </div>
                <div class="col-12 d-none" id="expiryWrapper">
                  <div class="input-block mb-3">
                    <label>Kedaluwarsa Pada<span class="text-danger">*</span></label>
                    <input type="datetime-local" class="form-control" id="expires_at">
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" data-bs-dismiss="modal" class="btn btn-back cancel-btn me-2">Batal</button>
            <button type="button" class="btn btn-primary paid-continue-btn btn-save-key">Buat API
              Key</button>
          </div>
        </form>
      </div>
    </div>
  </div>
