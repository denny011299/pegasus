<div class="modal modal-lg custom-modal fade pg-modal--form" id="add_warehouse_type" role="dialog" data-bs-backdrop="static"
    data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content d-flex flex-column" style="border-radius:16px;overflow:hidden;border:none;">
        {{-- ── HEADER ── --}}
        <div class="modal-header">
          <div class="d-flex align-items-center gap-3">
            <div class="pg-modal-icon">
              <i class="fe fe-tag"></i>
            </div>
            <div>
              <h5 class="mb-0 fw-bold modal-title">Tambah Tipe Gudang / Toko</h5>
              <small class="text-muted modal-subtitle">Input data tipe gudang baru</small>
            </div>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
            aria-label="Close"></button>
        </div>

        <form action="#">
          <div class="modal-body p-4" style="background:#f8fafc;">
            {{-- Nama Tipe Gudang --}}
            <div class="mb-3">
              <label class="text-muted mb-2"
                style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Nama Tipe Gudang
                <span class="text-danger">*</span></label>
              <input type="text" class="form-control fill" id="warehouse_type_name"
                placeholder="Contoh: Gudang Eceran, Gudang Utama..."
                style="font-size:14px;border-radius:8px;height:42px;">
            </div>

            {{-- Toggle Gudang Utama --}}
            <div class="p-3" style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;">
              <div class="d-flex align-items-start gap-3">
                <div class="status-toggle mt-1" style="flex-shrink:0;">
                  <input type="checkbox" id="is_main_warehouse" class="check">
                  <label for="is_main_warehouse" class="checktoggle">checkbox</label>
                </div>
                <div>
                  <label class="mb-1 fw-semibold text-dark" for="is_main_warehouse"
                    style="cursor:pointer;font-size:14px;display:block;">
                    <i class="fe fe-home me-1 text-primary"></i> Jadikan Gudang Utama
                  </label>
                  <p class="text-muted mb-0" style="font-size:12px;line-height:1.5;">
                    Aktifkan opsi ini untuk menandai sebagai gudang pusat.<br>
                    <span class="fw-semibold" style="color:#ef4444;"><i
                        class="fas fa-exclamation-circle me-1"></i>Catatan: Hanya boleh ada maksimal 1 tipe gudang
                      utama.</span>
                  </p>
                </div>
              </div>
            </div>
          </div>

          {{-- ── FOOTER ── --}}
        <div class="modal-footer pg-modal-footer">
          <button type="button" data-bs-dismiss="modal" class="btn pg-btn-cancel">Batal</button>
          <button type="button" class="btn pg-btn-save btn-save"><i class="fe fe-save me-1"></i>Simpan Tipe Gudang</button>
        </div>
      </form>
    </div>
  </div>
</div>
