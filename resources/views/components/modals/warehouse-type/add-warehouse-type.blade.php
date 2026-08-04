  <div class="modal modal-lg custom-modal fade" id="add_warehouse_type" role="dialog" data-bs-backdrop="static"
    data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-md">
      <div class="modal-content" style="border-radius: 16px; overflow: hidden; border: none;">
        {{-- ── HEADER ── --}}
        <div class="modal-header border-0"
          style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); padding: 18px 24px;">
          <div class="d-flex align-items-center gap-3">
            <div
              style="width:40px;height:40px;background:rgba(255,255,255,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;">
              <i class="fe fe-tag text-white" style="font-size:18px;"></i>
            </div>
            <div>
              <h5 class="mb-0 text-white fw-bold modal-title">Tambah Tipe Gudang</h5>
              <small class="text-white-50">Kelola kategori / tipe gudang</small>
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
          <div class="modal-footer border-top" style="background:#f8fafc;padding:14px 24px;">
            <button type="button" data-bs-dismiss="modal" class="btn"
              style="border:1px solid #e2e8f0;border-radius:8px;padding:9px 20px;font-size:13px;font-weight:600;color:#64748b;">Batal</button>
            <button type="button"
              class="btn btn-save d-inline-flex align-items-center justify-content-center gap-2"
              style="background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;border:none;border-radius:8px;padding:9px 28px;font-size:13px;font-weight:600;min-width:160px;height:42px;box-shadow:0 4px 12px rgba(59,130,246,.3);"><i
                class="fe fe-save me-1"></i>Simpan Tipe Gudang</button>
          </div>
        </form>
      </div>
    </div>
  </div>
