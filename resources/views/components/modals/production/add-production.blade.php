<style>
  #addProduction.modal {
    overflow: hidden !important;
  }
  html:has(#addProduction.show),
  body:has(#addProduction.show) {
    overflow: hidden !important;
  }
  #addProduction .modal-dialog {
    height: auto !important;
    max-height: calc(100dvh - 2rem) !important;
    margin: 1rem auto !important;
    display: flex !important;
    align-items: center !important;
  }
  #addProduction .modal-content {
    height: auto !important;
    max-height: calc(100dvh - 2rem) !important;
    min-height: 0 !important;
    display: flex !important;
    flex-direction: column !important;
    overflow: hidden !important;
  }
  #addProduction form {
    flex: 1 1 auto !important;
    min-height: 0 !important;
    height: auto !important;
    max-height: none !important;
    display: flex !important;
    flex-direction: column !important;
    overflow: hidden !important;
  }
  #addProduction .modal-header,
  #addProduction .modal-footer {
    flex: 0 0 auto !important;
  }
  #addProduction .modal-body {
    flex: 1 1 auto !important;
    min-height: 0 !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
  }
  /* Hint "1 pallet = N dos" di bawah field Qty diposisikan absolute; beri ruang
     ekstra di bawah kartu input supaya teksnya tidak keluar dari kartu. */
  #addProduction .pg-popup-table-input {
    padding-bottom: 24px;
  }
  #production-dest-mode-switch.pg-dest-toggle {
    display: inline-flex;
    align-items: center;
    padding: 2px;
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    border-radius: 999px;
    box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.04);
    gap: 2px;
  }
  #production-dest-mode-switch.pg-dest-toggle.d-none {
    display: none !important;
  }
  #production-dest-mode-switch .pg-dest-toggle__btn {
    border: 0;
    background: transparent;
    color: #64748b;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .3px;
    text-transform: uppercase;
    padding: 2px 8px;
    border-radius: 999px;
    line-height: 1.2;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    outline: none;
  }
  #production-dest-mode-switch .pg-dest-toggle__btn:hover:not(.is-active) {
    color: #1e293b;
    background: rgba(255, 255, 255, 0.6);
  }
  #production-dest-mode-switch .pg-dest-toggle__btn:focus-visible {
    outline: 2px solid #2563eb !important;
    outline-offset: 1px !important;
  }
  #production-dest-mode-switch .pg-dest-toggle__btn i,
  #production-dest-mode-switch .pg-dest-toggle__btn svg {
    font-size: 10px;
    width: 10px;
    height: 10px;
    flex-shrink: 0;
  }
  #production-dest-mode-switch .pg-dest-toggle__btn.is-active {
    background: #ffffff;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.12), 0 1px 2px rgba(15, 23, 42, 0.08);
  }
  #production-dest-mode-switch .pg-dest-toggle__btn[data-dest-mode="stock"].is-active {
    color: #2563eb;
  }
  #production-dest-mode-switch .pg-dest-toggle__btn[data-dest-mode="retail"].is-active {
    color: #059669;
  }
  /* Nama Produk / Gudang Tujuan autocompletes are appended to <body> (not
     .modal-content) so their dropdown can't get clipped by the modal's own
     overflow:hidden. Bootstrap's .modal is z-index 1055, above select2's
     default 1051 - bump it so the dropdown isn't hidden behind the modal. */
  .select2-dropdown {
    z-index: 1065 !important;
  }
  /* Select2 dropdown di-append ke body; pastikan kolom search selalu tampil & bisa difokus. */
  .select2-dropdown .select2-search--dropdown {
    display: block !important;
    padding: 8px;
  }
  .select2-dropdown .select2-search__field {
    width: 100% !important;
    min-height: 38px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 6px 10px;
  }
</style>
<div class="modal custom-modal fade pg-modal--form" id="addProduction" aria-modal="true" role="dialog" tabindex="-1"
  data-bs-backdrop="static" data-bs-focus="false">
  <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
    <div class="modal-content d-flex flex-column">
      <div class="modal-header">
        <div class="d-flex align-items-center gap-3">
          <div class="pg-modal-icon">
            <i class="fe fe-layers"></i>
          </div>
          <div>
            <h5 class="mb-0 fw-bold text-white modal-title d-inline-block" style="font-size:16px;letter-spacing:.2px;">Tambah
              Produksi</h5>
            <span id="production_status_badge_header" class="ms-2" style="display:none;"></span>
            <small class="d-block text-white-50">Kelola hasil produksi dan daftar produk</small>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="#" class="d-flex flex-column flex-grow-1" style="margin: 0; min-height: 0;">
        <div class="modal-body p-0 bg-light d-flex flex-column flex-grow-1">

          <div class="p-4 border-bottom bg-white shadow-sm" style="flex: 0 0 auto;">
            <div class="row g-4">
              <div class="col-lg-6 col-12">
                <label class="text-muted mb-2"
                  style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Tanggal <span
                    class="text-danger">*</span></label>
                <input type="date" class="form-control fill" id="production_date"
                  style="font-size:14px;border-radius:8px;height:42px;">
              </div>
              <div class="col-lg-6 col-12">
                <label class="text-muted mb-2"
                  style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Keterangan</label>
                <input type="text" class="form-control" id="production_desc"
                  placeholder="Masukkan Keterangan" style="font-size:14px;border-radius:8px;height:42px;">
              </div>
              <div class="col-12" id="row-production-detail-info" style="display:none;">
                <div class="row g-4">
                  <div class="col-lg-6 col-12">
                    <label class="text-muted mb-2"
                      style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Kode
                      Produksi</label>
                    <input type="text" class="form-control" id="production_code_display" disabled
                      style="font-size:14px;border-radius:8px;height:42px;">
                  </div>
                  <div class="col-lg-6 col-12">
                    <label class="text-muted mb-2"
                      style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Dibuat
                      Oleh</label>
                    <input type="text" class="form-control" id="production_created_by_display" disabled
                      style="font-size:14px;border-radius:8px;height:42px;">
                  </div>
                </div>
              </div>
              <div class="col-lg-6 col-12" id="row-production-acc-by" style="display:none;">
                <label class="text-muted mb-2"
                  style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Diapprove
                  Oleh</label>
                <input type="text" class="form-control" id="production_acc_by_name" disabled
                  style="font-size:14px;border-radius:8px;height:42px;">
              </div>
              <div class="col-12" id="row-production-cancel-info" style="display:none;">
                <div class="row g-4">
                  <div class="col-lg-6 col-12">
                    <label class="text-muted mb-2"
                      style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Pengajuan
                      Batal Oleh</label>
                    <input type="text" class="form-control" id="production_cancel_requested_by_display" disabled
                      style="font-size:14px;border-radius:8px;height:42px;">
                  </div>
                  <div class="col-lg-6 col-12">
                    <label class="text-muted mb-2"
                      style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Notes
                      Pembatalan</label>
                    <input type="text" class="form-control" id="production_cancel_notes_display" disabled
                      style="font-size:14px;border-radius:8px;height:42px;">
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="p-4" style="flex: 1 1 auto; background: #f8fafc;">
            <div class="d-flex align-items-center gap-2 mb-3">
              <i class="fe fe-list text-primary"></i>
              <span class="fw-bold text-dark" style="font-size:14px;">Daftar Produk</span>
            </div>

            <div class="pg-popup-table-input input_table">
              <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-3 add">
                  <div class="input-block mb-0" id="row-product">
                    <label class="text-muted mb-2"
                      style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Nama Produk
                      <span class="text-danger">*</span></label>
                    <select class="form-select fill_product" id="product_id"
                      style="font-size:14px;border-radius:8px;height:42px;"></select>
                  </div>
                </div>
                <div class="col-6 col-lg-2 add">
                  <div class="input-block mb-0" style="position: relative;">
                    <label class="text-muted mb-2"
                      style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Qty <span
                        class="text-danger">*</span></label>
                    <input type="text" class="form-control fill_product number-only" id="production_qty"
                      placeholder="Qty" value="1" style="font-size:14px;border-radius:8px;height:42px;">
                    <small class="text-muted position-absolute" id="production_pallet_hint"
                      style="bottom: -18px; left: 2px; font-size: 10px; white-space: nowrap;"></small>
                  </div>
                </div>
                <div class="col-6 col-lg-2 add">
                  <div class="input-block mb-0">
                    <label class="text-muted mb-2"
                      style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Satuan
                      <span class="text-danger">*</span></label>
                    <select class="form-select fill_product" id="unit_id"
                      style="font-size:14px;border-radius:8px;height:42px;"></select>
                  </div>
                </div>
                <div class="col-12 col-lg-4 add">
                  <div class="input-block mb-0">
                    <div class="d-flex align-items-center gap-2 mb-2">
                      <label class="text-muted mb-0"
                        style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Gudang
                        Tujuan <span class="text-danger">*</span></label>
                      <div id="production-dest-mode-switch" class="pg-dest-toggle d-none ms-1" role="group"
                        aria-label="Mode gudang tujuan">
                        <button type="button" class="pg-dest-toggle__btn is-active" data-dest-mode="stock"
                          aria-pressed="true" tabindex="0">
                          <i class="fe fe-home"></i> Stok
                        </button>
                        <button type="button" class="pg-dest-toggle__btn" data-dest-mode="retail"
                          aria-pressed="false" tabindex="0">
                          <i class="fe fe-repeat"></i> Transfer
                        </button>
                      </div>
                    </div>
                    @php
                      $prodDestWh = $activeWarehouse ?? null;
                      $prodDestWhName = $prodDestWh
                        ? ($prodDestWh->warehouse_name ?? $prodDestWh->name ?? '')
                        : '';
                      $isActiveMainWh = $prodDestWh
                        && isset($prodDestWh->type)
                        && (int) ($prodDestWh->type->is_main_warehouse ?? 0) === 1;
                      $prodMainWhName = $prodDestWhName;
                      if (! $isActiveMainWh && isset($warehouses)) {
                          $prodMainWh = collect($warehouses)->first(function ($wh) {
                              return isset($wh->type) && (int) $wh->type->is_main_warehouse === 1;
                          });
                          if ($prodMainWh) {
                              $prodMainWhName = $prodMainWh->warehouse_name ?? $prodMainWh->name ?? $prodMainWhName;
                          }
                      }
                    @endphp
                    <div id="production-main-warehouse-badge" class="d-flex align-items-center px-3"
                      style="height:42px;border-radius:8px;background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8;font-size:13px;font-weight:600;">
                      <i class="fe fe-home me-2"></i><span>{{ $prodMainWhName !== '' ? $prodMainWhName : 'Gudang utama' }}</span>
                    </div>
                    <select class="form-select" id="production_destination_warehouse_id"
                      style="display:none;font-size:14px;border-radius:8px;height:42px;"></select>
                  </div>
                </div>
                <div class="col-12 col-md-12 col-lg-1 add">
                  <label class="text-muted mb-2 d-none d-lg-block"
                    style="font-size:11px;font-weight:600;">&nbsp;</label>
                  <button type="button"
                    class="btn btn-primary w-100 btn-add-product d-flex align-items-center justify-content-center"
                    style="background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;border:none;border-radius:8px;height:42px;box-shadow:0 4px 12px rgba(59,130,246,.3);">
                    <i class="fe fe-plus"></i>
                  </button>
                </div>
              </div>
            </div>
            <div class="table-responsive rounded border bg-white pg-popup-table-scroll">
              <table class="table table-center custom-table-scroll mb-0" id="tableProduct"
                style="min-width: 800px;">
                <thead style="background: #f1f5f9;">
                  <tr>
                    <th
                      style="width: 35%; padding: 12px 16px; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase;">
                      Nama Produk</th>
                    <th class="text-center"
                      style="width: 15%; padding: 12px 16px; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase;">
                      Qty</th>
                    <th
                      style="width: 20%; padding: 12px 16px; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase;">
                      Satuan</th>
                    <th
                      style="width: 20%; padding: 12px 16px; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase;">
                      Gudang Tujuan</th>
                    <th class="no-sort text-center"
                      style="width: 15%; padding: 12px 16px; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase;">
                      Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr class="pg-popup-table-empty">
                    <td colspan="5">Belum ada produk. Tambahkan lewat form di atas.</td>
                  </tr>
                </tbody>
                <tfoot class="dos" style="background: #f8fafc; border-top: 2px solid #e2e8f0;">
                  <tr>
                    <td class="fw-bold text-end" style="color: #334155; padding: 12px 16px;">Total Dos:</td>
                    <td class="fw-bold text-center" style="color: #334155; padding: 12px 16px;"><span
                        id="total_dos"
                        style="background: #e0f2fe; color: #0284c7; padding: 4px 12px; border-radius: 6px;">0</span>
                    </td>
                    <td class="fw-bold" style="color: #334155; padding: 12px 16px;">Dos</td>
                    <td></td>
                    <td></td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>
        <div class="modal-footer pg-modal-footer">
          <button type="button" data-bs-dismiss="modal" class="btn pg-btn-cancel btn-cancel">Batal</button>
          @roleCan('Produksi', 'others')
            <button type="button" id="btn-tolak" class="btn pg-btn-decline d-none btn_decline"><i class="fe fe-x me-1"></i>Tolak</button>
            <button type="button" id="btn-terima" class="btn pg-btn-accept d-none btn_acc"><i class="fe fe-check-circle me-1"></i>Terima Produksi</button>
          @endroleCan
          <button type="button" class="btn pg-btn-save btn-save" id="btn-tambah-pesanan">
            <i class="fe fe-save"></i> <span class="btn-save-label">Simpan</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
