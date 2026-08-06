  <div class="modal modal-lg custom-modal fade pg-modal--form" id="add_stock_transfer" role="dialog" data-bs-backdrop="static"
    data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-xl"
      style="max-width: 90vw; max-height: 92vh; margin: 1rem auto;">
      <form action="#" id="formStockTransfer" class="modal-content d-flex flex-column"
        style="border-radius: 16px; overflow: hidden; border: none; max-height: 92vh;">

        {{-- ── HEADER (fixed) ── --}}
        <div class="modal-header">
          <div class="d-flex align-items-center gap-3">
            <div class="pg-modal-icon">
              <i class="fe fe-shuffle"></i>
            </div>
            <div>
              <h5 class="mb-0 fw-bold modal-title">Buat Stock Transfer</h5>
              <small class="text-muted transfer-modal-subtitle">Pindahkan stok antar gudang / toko</small>
            </div>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
            aria-label="Close"></button>
        </div>

        {{-- ── BODY (scrollable only) ── --}}
        <div class="modal-body p-0 flex-grow-1 position-relative" style="overflow-y: auto; min-height: 0; background:#f8fafc;">
          <div class="pg-modal-loading" aria-live="polite" aria-busy="true">
            <div class="spinner-border text-primary" role="status"></div>
            <span class="text-muted fw-semibold" style="font-size:13px;">Memuat data...</span>
          </div>
          <div class="pg-modal-body-content">
          {{-- ROUTE PANEL --}}
          <div class="border-bottom position-relative" style="background:#f8fafc; padding: 16px 24px;">
            <button class="btn btn-light position-absolute" type="button" data-bs-toggle="collapse"
              data-bs-target="#collapseStockTransferForm" aria-expanded="true"
              aria-controls="collapseStockTransferForm"
              style="top: -18px; left: 50%; transform: translateX(-50%); border-radius: 50%; width: 36px; height: 36px; padding: 0; display: flex; align-items: center; justify-content: center; border: 1px solid #e2e8f0; color: #475569; z-index: 10; background: #fff; box-shadow: 0 4px 6px rgba(0,0,0,0.05);"
              onclick="this.querySelector('i').classList.toggle('fe-chevron-up'); this.querySelector('i').classList.toggle('fe-chevron-down');"
              title="Toggle Detail Gudang">
              <i class="fe fe-chevron-up" style="font-size: 16px;"></i>
            </button>

            <div class="collapse show" id="collapseStockTransferForm">
              <div class="row g-3 align-items-stretch mb-3">

                {{-- Card Asal --}}
                <div class="col-md-5">
                  <div class="d-flex flex-column"
                    style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:14px 16px; height:100%;">
                    <div class="d-flex align-items-center gap-2 mb-2">
                      <span
                        style="width:28px;height:28px;background:#eff6ff;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                        <i class="fe fe-log-out" style="font-size:13px;color:#3b82f6;"></i>
                      </span>
                      <span class="fw-semibold text-primary"
                        style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;">Dari (Asal)</span>
                    </div>
                    <div class="row g-3 mt-0 flex-grow-1">
                      <div class="col-6">
                        <label class="text-muted mb-1"
                          style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Pengirim</label>
                        <select class="form-select form-control fill select2 mt-1" id="transfer_sender_id"
                          style="border-radius:8px;font-size:13px;" disabled>
                          <option value="">Pilih Staff</option>
                        </select>
                      </div>
                      <div class="col-6">
                        <label class="text-muted mb-1"
                          style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Gudang /
                          Toko Asal</label>
                        <select class="form-select form-control fill select2 mt-1" id="transfer_from_warehouse_id"
                          style="border-radius:8px;font-size:13px;" disabled>
                          <option value="">Pilih toko atau gudang</option>
                        </select>
                      </div>
                      <div class="col-12 d-flex flex-column" style="margin-top: 1.5rem;">
                        <label class="text-muted mb-1"
                          style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;"><i
                            class="fe fe-calendar me-1"></i>Tanggal Pengiriman</label>
                        <div class="position-relative mt-1">
                          <input type="text" class="form-control datetimepicker" id="transfer_date"
                            placeholder="Pilih Tanggal"
                            style="border-radius:8px;font-size:13px;min-height:38px;padding-right:36px;">
                          <i class="fe fe-calendar position-absolute"
                            style="right:12px; top:50%; transform:translateY(-50%); color:#94a3b8; pointer-events:none; font-size:14px;"></i>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                {{-- Arrow --}}
                <div class="col-md-2 d-flex align-items-center justify-content-center">
                  <div style="display:flex;flex-direction:column;align-items:center;gap:6px;">
                    <div
                      style="width:44px;height:44px;background:linear-gradient(135deg,#3b82f6,#8b5cf6);border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(59,130,246,.3);">
                      <i class="fe fe-arrow-right text-white" style="font-size:18px;"></i>
                    </div>
                    <span class="text-muted"
                      style="font-size:10px;font-weight:600;letter-spacing:.5px;">TRANSFER</span>
                  </div>
                </div>

                {{-- Card Tujuan --}}
                <div class="col-md-5">
                  <div class="d-flex flex-column"
                    style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:14px 16px; height:100%;">
                    <div class="d-flex align-items-center gap-2 mb-2">
                      <span
                        style="width:28px;height:28px;background:#f0fdf4;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                        <i class="fe fe-log-in" style="font-size:13px;color:#22c55e;"></i>
                      </span>
                      <span class="fw-semibold"
                        style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:#16a34a;">Ke
                        (Tujuan)</span>
                    </div>
                    <div class="row g-3 mt-0 flex-grow-1">
                      <div class="col-12">
                        <label class="text-muted mb-1"
                          style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Gudang
                          Tujuan</label>
                        <select class="form-select form-control fill select2 mt-1" id="transfer_to_warehouse_id"
                          style="border-radius:8px;font-size:13px;">
                          <option value="">Pilih toko atau gudang</option>
                        </select>
                      </div>
                      <div class="col-12 d-flex flex-column" style="margin-top: 1.5rem;">
                        <label class="text-muted mb-1"
                          style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;"><i
                            class="fe fe-file-text me-1"></i>Catatan (Opsional)</label>
                        <textarea class="form-control flex-grow-1 mt-1" id="transfer_note" placeholder="Masukkan catatan tambahan..."
                          style="border-radius:8px;font-size:13px;resize:none; min-height:80px;"></textarea>
                      </div>
                    </div>
                  </div>
                </div>

              </div>
            </div>

            <div class="transfer-product-panel">
              <div class="transfer-product-grid">
                <div class="transfer-product-field transfer-product-select">
                  <label><i class="fe fe-box me-1"></i>Pilih Produk <span class="text-danger">*</span></label>
                  <div id="transfer_mode_select">
                    <select class="form-select form-control fill" id="transfer_sku">
                      <option value="" selected disabled>Pilih gudang asal terlebih dahulu</option>
                    </select>
                  </div>
                  <div id="transfer_mode_scan" style="display:none">
                    <div class="input-group">
                      <input type="text" class="form-control" id="transfer_scan_barcode"
                        placeholder="Scan / ketik barcode atau SKU">
                      <input type="number" class="form-control" id="transfer_scan_qty" value="1"
                        min="1" step="1" title="Qty scan" style="max-width:90px;">
                      <button type="button" class="btn btn-primary px-3" id="btn_scan_add_transfer"
                        title="Cari produk">
                        <i class="fe fe-search"></i>
                      </button>
                    </div>
                  </div>
                </div>
                <div class="transfer-product-field transfer-draft-only">
                  <label>Qty <span class="text-danger">*</span></label>
                  <input type="number" class="form-control" id="transfer_qty_input" placeholder="Qty"
                    value="1" min="1" step="1">
                </div>
                <div class="transfer-product-field transfer-draft-only">
                  <label>Satuan <span class="text-danger">*</span></label>
                  <select class="form-select" id="transfer_unit_input" disabled>
                    <option value="">Pilih produk dahulu</option>
                  </select>
                  <small id="transfer_stock_available" class="text-muted">Stok tersedia: -</small>
                </div>
                <div class="transfer-product-actions">
                  <button type="button" class="btn btn-primary transfer-draft-only" id="btn_add_transfer_product">
                    <i class="fe fe-plus me-1"></i>Tambah
                  </button>
                  <button type="button" class="btn btn-light border" id="btn_toggle_scan_transfer"
                    title="Ganti mode input">
                    <i class="fa fa-barcode me-1"></i>Scan
                  </button>
                </div>
              </div>
            </div>
          </div>

          {{-- PRODUCT TABLE --}}
          <div class="d-flex flex-column" style="background:#fff;">
            <div class="d-flex align-items-center justify-content-between px-4 py-2 border-bottom"
              style="background:#f8fafc;">
              <div class="d-flex align-items-center">
                <i class="fe fe-list text-primary me-2"></i>
                <span class="fw-semibold text-dark" style="font-size:13px;">Daftar Produk yang Akan Ditransfer</span>
              </div>
              <button type="button"
                class="btn btn-sm btn-outline-primary btn-enable-edit-transfer d-none align-items-center gap-1"
                style="border-radius:8px;font-size:12px;font-weight:600;padding:6px 14px;height:34px;">
                <i class="fe fe-edit-2"></i> Edit Data
              </button>
            </div>
            <div class="table-responsive" style="min-height:240px; background:#fff;">
              <table class="table table-hover mb-0" id="tableTransferItems" style="font-size:14px;">
                <thead style="background:#f1f5f9;">
                  <tr>
                    <th
                      style="color: #475569; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing:.5px; padding: 14px 16px;">
                      Produk</th>
                    <th
                      style="color: #475569; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing:.5px; padding: 14px 16px;">
                      Varian</th>
                    <th
                      style="color: #475569; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing:.5px; padding: 14px 16px;">
                      SKU</th>
                    <th
                      style="color: #475569; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing:.5px; padding: 14px 16px;">
                      Stok Asal</th>
                    <th
                      style="width: 140px; color: #475569; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing:.5px; padding: 14px 16px;">
                      Jumlah</th>
                    <th
                      style="width: 150px; color: #475569; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing:.5px; padding: 14px 16px;">
                      Satuan</th>
                    <th class="no-sort text-center"
                      style="width: 80px; color: #475569; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing:.5px; padding: 14px 16px;">
                      Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr class="empty-row">
                    <td colspan="7" class="text-center py-5">
                      <div style="color:#94a3b8;">
                        <i class="fe fe-inbox" style="font-size:36px;display:block;margin-bottom:8px;"></i>
                        <div class="fw-semibold" style="font-size:14px;">Belum ada produk</div>
                        <div style="font-size:12px;">Pilih gudang asal terlebih dahulu, lalu pilih atau scan produk
                        </div>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          </div>{{-- /.pg-modal-body-content --}}
        </div>

        {{-- ── FOOTER: semua aksi di kanan — Batal, Tolak, Simpan/Transfer ── --}}
        <div class="modal-footer pg-modal-footer">
          <button type="button" class="btn pg-btn-cancel btn-cancel-transfer">Batal</button>
          <button type="button" class="btn pg-btn-decline btn-reject-transfer d-none"><i class="fe fe-x me-1"></i>Tolak</button>
          <button type="button" class="btn pg-btn-accept btn-acc-transfer d-none"><i class="fe fe-send me-1"></i>Transfer</button>
          <button type="button" class="btn pg-btn-save btn-save-transfer"><i class="fe fe-save me-1"></i>Simpan</button>
        </div>
      </form>
    </div>
  </div>
