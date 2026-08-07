  <div class="modal modal-lg custom-modal fade" id="show_external_api_key" role="dialog"
    data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-md">
      <div class="modal-content">
        <div class="modal-header border-0 pb-0">
          <div class="form-header modal-header-title text-start mb-0">
            <h4 class="mb-0 modal-title">API Key Berhasil Dibuat</h4>
          </div>
        </div>
        <div class="modal-body">
          <div class="alert alert-warning d-flex align-items-start" role="alert">
            <i class="fe fe-alert-triangle me-2 mt-1"></i>
            <div>
              API Key ini hanya ditampilkan sekali. Simpan di tempat yang aman sebelum menutup
              dialog ini. Kunci yang hilang tidak bisa dipulihkan — Anda harus mencabutnya lalu
              membuat kunci baru.
            </div>
          </div>
          <div class="input-block mb-0">
            <label>API Key</label>
            <div class="input-group">
              <input type="text" class="form-control extapi-key-value" id="generated_key" readonly>
              <button class="btn btn-primary btn-copy-key" type="button">
                <i class="fa fa-copy me-1"></i>Salin
              </button>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary btn-close-key-dialog">Saya Sudah
            Menyimpannya</button>
        </div>
      </div>
    </div>
  </div>
