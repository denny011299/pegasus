  <div class="modal modal-lg custom-modal fade pg-modal--form" id="add_unit" role="dialog" data-bs-backdrop="static"
    data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content d-flex flex-column" style="border-radius:16px;overflow:hidden;border:none;">
        {{-- ── HEADER ── --}}
        <div class="modal-header">
          <div class="d-flex align-items-center gap-3">
            <div class="pg-modal-icon">
              <i class="fe fe-package"></i>
            </div>
            <div>
              <h5 class="mb-0 fw-bold modal-title">Tambah Satuan</h5>
              <small class="text-muted modal-subtitle">Input data satuan produk baru</small>
            </div>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
            aria-label="Close"></button>
        </div>

        <form action="#">
          <div class="modal-body p-4" style="background:#f8fafc;">
            <div class="row">
              <div class="col-lg-6 col-md-12">
                {{-- Nama Satuan --}}
                <div class="mb-3">
                  <label class="text-muted mb-2"
                    style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Nama Satuan
                    <span class="text-danger">*</span></label>
                  <input type="text" class="form-control fill" id="unit_name" placeholder="Input Nama Satuan"
                    style="font-size:14px;border-radius:8px;height:42px;">
                </div>
              </div>
              <div class="col-lg-6 col-md-12">
                {{-- Singkatan --}}
                <div class="mb-3">
                  <label class="text-muted mb-2"
                    style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Singkatan
                    <span class="text-danger">*</span></label>
                  <input type="text" class="form-control fill" id="unit_short_name" placeholder="Input Singkatan"
                    style="font-size:14px;border-radius:8px;height:42px;">
                </div>
              </div>
            </div>
          </div>

          {{-- ── FOOTER ── --}}
        <div class="modal-footer pg-modal-footer">
          <button type="button" data-bs-dismiss="modal" class="btn pg-btn-cancel">Batal</button>
          <button type="button" class="btn pg-btn-save btn-save"><i class="fe fe-save me-1"></i>Tambah Satuan</button>
        </div>
      </form>
    </div>
  </div>
</div>
