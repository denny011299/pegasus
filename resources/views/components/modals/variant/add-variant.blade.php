  <div class="modal modal-lg custom-modal fade pg-modal--form" id="add_variant" role="dialog" data-bs-backdrop="static"
    data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content d-flex flex-column" style="border-radius:16px;overflow:hidden;border:none;">
        {{-- ── HEADER ── --}}
        <div class="modal-header">
          <div class="d-flex align-items-center gap-3">
            <div class="pg-modal-icon">
              <i class="fe fe-layers"></i>
            </div>
            <div>
              <h5 class="mb-0 fw-bold modal-title">Tambah Variasi</h5>
              <small class="text-muted modal-subtitle">Input data variasi produk baru</small>
            </div>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
            aria-label="Close"></button>
        </div>

        <form action="#">
          <div class="modal-body p-4" style="background:#f8fafc;">
            {{-- Nama --}}
            <div class="mb-3">
              <label class="text-muted mb-2"
                style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Nama
                <span class="text-danger">*</span></label>
              <input type="text" class="form-control fill" id="variant_name" placeholder="Input Nama Variasi"
                style="font-size:14px;border-radius:8px;height:42px;">
            </div>
            {{-- Variasi --}}
            <div class="mb-3">
              <label class="text-muted mb-2"
                style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Variasi
                <span class="text-danger">*</span></label>
              <select class="form-control tagging fill" id="variant_attribute" multiple="multiple"
                style="border-radius:8px;">

              </select>
            </div>
          </div>

          {{-- ── FOOTER ── --}}
        <div class="modal-footer pg-modal-footer">
          <button type="button" data-bs-dismiss="modal" class="btn pg-btn-cancel">Batal</button>
          <button type="button" class="btn pg-btn-save btn-save"><i class="fe fe-save me-1"></i>Tambah Variasi</button>
        </div>
      </form>
    </div>
  </div>
</div>
