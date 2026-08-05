  <div class="modal modal-lg custom-modal fade" id="add_warehouse" role="dialog" data-bs-backdrop="static"
    data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg"
      style="max-height:calc(100vh - 2rem);margin:1rem auto;">
      <div class="modal-content d-flex flex-column"
        style="max-height:calc(100vh - 2rem);border-radius:16px;overflow:hidden;border:none;">
        {{-- ── HEADER ── --}}
        <div class="modal-header border-0 flex-shrink-0"
          style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); padding: 18px 24px;">
          <div class="d-flex align-items-center gap-3">
            <div
              style="width:40px;height:40px;background:rgba(255,255,255,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;">
              <i class="fe fe-box text-white" style="font-size:18px;"></i>
            </div>
            <div>
              <h5 class="mb-0 text-white fw-bold modal-title">Tambah Gudang</h5>
              <small class="text-white-50">Kelola master data gudang / toko</small>
            </div>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
            aria-label="Close"></button>
        </div>

        <form action="#" class="d-flex flex-column flex-grow-1" style="min-height:0;overflow:hidden;">
          <div class="modal-body p-0 bg-light d-flex flex-column flex-grow-1" style="min-height:0;overflow-y:auto;">
            {{-- Basic Info Panel --}}
            <div class="p-4 border-bottom bg-white shadow-sm" style="flex: 0 0 auto;">
              <div class="row g-4">
                <div class="col-md-6">
                  <label class="text-muted mb-2"
                    style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Nama Gudang
                    <span class="text-danger">*</span></label>
                  <input type="text" class="form-control fill" id="warehouse_name"
                    placeholder="Contoh: Gudang Pusat Jakarta"
                    style="font-size:14px;border-radius:8px;height:42px;">
                </div>
                <div class="col-md-6">
                  <label class="text-muted mb-2"
                    style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Tipe Gudang
                    <span class="text-danger">*</span></label>
                  <select class="form-select form-control fill select2" id="warehouse_type_id"
                    style="font-size:14px;height:42px;">
                    <option value="">Pilih Tipe Gudang...</option>
                  </select>
                </div>
                <div class="col-12">
                  <label class="text-muted mb-2"
                    style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;"><i
                      class="fe fe-map-pin me-1"></i>Alamat Lengkap</label>
                  <textarea class="form-control" id="warehouse_address" rows="2"
                    placeholder="Masukkan alamat lengkap gudang..." style="font-size:14px;resize:none;border-radius:8px;"></textarea>
                </div>
              </div>
            </div>

            {{-- Permissions Panel --}}
            <div class="p-4" style="flex: 1 1 auto; background: #f8fafc;">
              <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div>
                  <label class="mb-0 fw-bold text-dark" style="font-size:14px;"><i
                      class="fe fe-shield me-1 text-primary"></i> Akses Menu Sidebar</label>
                  <p class="text-muted mb-0 mt-1" style="font-size: 11px;">Centang menu yang diizinkan untuk diakses
                    dari gudang ini. Kosongkan jika ingin membuka semua akses.</p>
                </div>
                <div class="d-flex gap-2">
                  <button type="button" class="btn btn-sm"
                    style="background:#eff6ff;color:#3b82f6;border:1px solid #bfdbfe;font-weight:600;font-size:12px;border-radius:6px;padding:6px 12px;"
                    id="btn-sidebar-menus-all">
                    <i class="fa fa-check-square me-1"></i> Pilih Semua
                  </button>
                  <button type="button" class="btn btn-sm"
                    style="background:#fef2f2;color:#ef4444;border:1px solid #fecaca;font-weight:600;font-size:12px;border-radius:6px;padding:6px 12px;"
                    id="btn-sidebar-menus-none">
                    <i class="fa fa-square-o me-1"></i> Kosongkan
                  </button>
                </div>
              </div>

              @php
                $warehousePermissionMenus =
                    json_decode(file_get_contents(public_path('assets/json/permission.json')), true) ?: [];
                $warehousePermissionGrouped = collect($warehousePermissionMenus)
                    ->filter(fn($p) => ($p['SubModules'] ?? '') !== 'Safety Stock')
                    ->groupBy('Modules');
              @endphp
              <style>
                .menu-masonry {
                  column-count: 1;
                  column-gap: 16px;
                }

                @media (min-width: 576px) {
                  .menu-masonry {
                    column-count: 2;
                  }
                }

                .menu-card {
                  break-inside: avoid;
                  margin-bottom: 16px;
                  background: #ffffff;
                  border: 1px solid #e2e8f0;
                  border-radius: 8px;
                  padding: 12px;
                  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
                  transition: all 0.2s ease;
                }

                .menu-card:hover {
                  border-color: #cbd5e1;
                  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.04);
                }
              </style>
              <div id="warehouse_sidebar_menus" class="border p-3 bg-white"
                style="border-radius: 12px; max-height: 280px; overflow-y: auto;">
                <div class="menu-masonry">
                  @foreach ($warehousePermissionGrouped as $module => $items)
                    <div class="menu-card">
                      <div class="form-check m-0 mb-3 d-flex align-items-center gap-2"
                        style="border-bottom: 1px dashed #cbd5e1; padding-bottom: 8px;">
                        <input class="form-check-input module-check-all m-0" type="checkbox"
                          id="mod_menu_{{ Str::slug($module) }}"
                          style="cursor:pointer; width:16px; height:16px; margin-top:0;">
                        <label class="form-check-label fw-bold text-dark mb-0"
                          for="mod_menu_{{ Str::slug($module) }}" style="font-size:13.5px; cursor:pointer;">
                          {{ $module }}
                        </label>
                      </div>
                      <div class="d-flex flex-column gap-2 px-1">
                        @foreach ($items as $item)
                          <div class="form-check m-0 d-flex align-items-center gap-2">
                            <input class="form-check-input warehouse-sidebar-menu m-0" type="checkbox"
                              value="{{ $item['SubModules'] }}" id="wh_menu_{{ $item['Id'] }}"
                              style="cursor:pointer; width:14px; height:14px; margin-top:0;">
                            <label class="form-check-label text-secondary mb-0" for="wh_menu_{{ $item['Id'] }}"
                              style="font-size:12.5px; cursor:pointer;">
                              {{ $item['SubModules'] }}
                            </label>
                          </div>
                        @endforeach
                      </div>
                    </div>
                  @endforeach
                </div>
              </div>
            </div>
          </div>

          {{-- ── FOOTER ── --}}
          <div class="modal-footer border-top flex-shrink-0 flex-wrap justify-content-end"
            style="background:#f8fafc;padding:14px 24px;">
            <button type="button" data-bs-dismiss="modal" class="btn"
              style="border:1px solid #e2e8f0;border-radius:8px;padding:9px 20px;font-size:13px;font-weight:600;color:#64748b;">Batal</button>
            <button type="button"
              class="btn btn-save d-inline-flex align-items-center justify-content-center gap-2"
              style="background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;border:none;border-radius:8px;padding:9px 28px;font-size:13px;font-weight:600;min-width:140px;height:42px;box-shadow:0 4px 12px rgba(59,130,246,.3);"><i
                class="fe fe-save me-1"></i>Simpan Gudang</button>
          </div>
        </form>
      </div>
    </div>
  </div>
