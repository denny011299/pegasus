  <div class="modal custom-modal fade pg-modal--form" id="add_production_skala" role="dialog"
    data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px; margin: 1.25rem auto;">
      <div class="modal-content d-flex flex-column" style="border-radius:16px;overflow:hidden;border:none;box-shadow:0 10px 30px rgba(0,0,0,0.15);">
        <div class="modal-header">
          <div class="d-flex align-items-center gap-3">
            <div class="pg-modal-icon">
              <i class="fe fe-layers"></i>
            </div>
            <div>
              <h5 class="mb-0 fw-bold modal-title">Tambah Skala</h5>
              <small class="text-muted modal-subtitle">Referensi kode skala SPK (1, M1, 2, M2, …)</small>
            </div>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form action="#">
          <div class="modal-body p-4" style="background:#ffffff;">
            <div class="row g-3">
              <div class="col-6">
                <div>
                  <label class="form-label text-muted mb-1.5"
                    style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;">
                    Kode Skala <span class="text-danger">*</span>
                  </label>
                  <input type="text" class="form-control fill" id="skala_code" placeholder="M1"
                    style="font-size:13.5px;border-radius:8px;height:40px;border-color:#cbd5e1;">
                </div>
              </div>
              <div class="col-6">
                <div>
                  <label class="form-label text-muted mb-1.5"
                    style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;">
                    Nama Skala <span class="text-danger">*</span>
                  </label>
                  <input type="text" class="form-control fill" id="skala_name" placeholder="Muat Pagi"
                    style="font-size:13.5px;border-radius:8px;height:40px;border-color:#cbd5e1;">
                </div>
              </div>
              <div class="col-12">
                <div>
                  <label class="form-label text-muted mb-1.5"
                    style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;">
                    Keterangan
                  </label>
                  <input type="text" class="form-control" id="skala_combo_label" placeholder="Keterangan skala"
                    style="font-size:13.5px;border-radius:8px;height:40px;border-color:#cbd5e1;">
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer pg-modal-footer">
            <button type="button" data-bs-dismiss="modal" class="btn pg-btn-cancel">Batal</button>
            <button type="button" class="btn pg-btn-save btn-save-skala"><i class="fe fe-save me-1"></i>Tambah Skala</button>
          </div>
        </form>
      </div>
    </div>
  </div>
