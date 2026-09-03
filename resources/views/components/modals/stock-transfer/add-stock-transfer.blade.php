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
        <div class="modal-body p-0 flex-grow-1 position-relative" style="overflow-y: auto; min-height: 0; background:#ffffff;">
          <div class="pg-modal-loading" aria-live="polite" aria-busy="true">
            <div class="spinner-border text-primary" role="status"></div>
            <span class="text-muted fw-semibold" style="font-size:13px;">Memuat data...</span>
          </div>
          <div class="pg-modal-body-content">
          {{-- ROUTE PANEL --}}
          <div class="position-relative" style="background:#ffffff; padding: 14px 24px 8px 24px;">
            <div class="collapse show" id="collapseStockTransferForm">
              <div class="row g-3 align-items-center mb-1 st-route-row">

                {{-- Section Asal / Request (BE: from) — di mode request dipindah ke kanan via CSS order --}}
                <div class="col-md-5 st-card-asal">
                  <div class="d-flex flex-column h-100">
                    <div class="d-flex align-items-center gap-2 mb-1.5">
                      <span class="badge d-inline-flex align-items-center gap-1 px-2 py-1 rounded-2"
                        style="background:#eff6ff; color:#1d4ed8; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; border:1px solid #bfdbfe;">
                        <i class="fe fe-log-out st-card-asal-icon" style="font-size:11px;"></i>
                        <span class="st-label-from-card">Dari (Asal)</span>
                      </span>
                    </div>
                    <div class="row g-2 mt-0 flex-grow-1">
                      <div class="col-6" id="st-sender-slot-asal">
                        <div id="st-sender-block">
                          <label class="text-muted mb-0.5 st-label-sender-field"
                            style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:#475569;">Pengirim</label>
                          <select class="form-select form-control fill select2 mt-0.5" id="transfer_sender_id"
                            style="height:34px;border-radius:6px;font-size:12.5px;border-color:#cbd5e1;" disabled>
                            <option value="">Pilih Staff</option>
                          </select>
                        </div>
                      </div>
                      <div class="col-6" id="st-from-warehouse-col">
                        <label class="text-muted mb-0.5 st-label-from-field"
                          style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:#475569;">Gudang /
                          Toko Asal</label>
                        <select class="form-select form-control fill select2 mt-0.5" id="transfer_from_warehouse_id"
                          style="height:34px;border-radius:6px;font-size:12.5px;border-color:#cbd5e1;" disabled>
                          <option value="">Pilih toko atau gudang</option>
                        </select>
                      </div>
                      <div class="col-12" id="st-date-slot-asal">
                        <div id="st-date-block" class="d-flex flex-column">
                          <label class="text-muted mb-0.5"
                            style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:#475569;"><i
                              class="fe fe-calendar me-1 text-primary"></i>Tanggal Pengiriman</label>
                          <div class="position-relative mt-0.5">
                            <input type="text" class="form-control datetimepicker" id="transfer_date"
                              placeholder="Pilih Tanggal"
                              style="height:34px;border-radius:6px;font-size:12.5px;border-color:#cbd5e1;padding-right:32px;width:100%;">
                            <i class="fe fe-calendar position-absolute"
                              style="right:10px; top:50%; transform:translateY(-50%); color:#94a3b8; pointer-events:none; font-size:13px;"></i>
                          </div>
                        </div>
                      </div>
                      <div class="col-12 d-none" id="st-note-slot-asal"></div>
                    </div>
                  </div>
                </div>

                {{-- Arrow --}}
                <div class="col-md-2 d-flex align-items-center justify-content-center st-card-arrow">
                  <div style="display:flex;flex-direction:column;align-items:center;gap:3px;">
                    <div
                      style="width:34px;height:34px;background:linear-gradient(135deg,#3b82f6,#6366f1);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;box-shadow:0 3px 8px rgba(59,130,246,.25);">
                      <i class="fe fe-arrow-right text-white st-route-arrow-icon" style="font-size:14px;"></i>
                    </div>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-0.5 st-route-arrow-text"
                      style="font-size:9.5px;font-weight:700;letter-spacing:.4px;">TRANSFER</span>
                  </div>
                </div>

                {{-- Section Tujuan / Penerima (BE: to) — di mode request dipindah ke kiri via CSS order --}}
                <div class="col-md-5 st-card-tujuan">
                  <div class="d-flex flex-column h-100">
                    <div class="d-flex align-items-center gap-2 mb-1.5">
                      <span class="badge d-inline-flex align-items-center gap-1 px-2 py-1 rounded-2"
                        style="background:#f0fdf4; color:#15803d; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; border:1px solid #bbf7d0;">
                        <i class="fe fe-log-in" style="font-size:11px;"></i>
                        <span class="st-label-to-card">Ke (Tujuan)</span>
                      </span>
                    </div>
                    <div class="row g-2 mt-0 flex-grow-1">
                      <div class="col-6 d-none" id="st-sender-slot-tujuan"></div>
                      <div class="col-12" id="st-to-warehouse-col">
                        <label class="text-muted mb-0.5 st-label-to-field"
                          style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:#475569;">Gudang
                          Tujuan</label>
                        <select class="form-select form-control fill select2 mt-0.5" id="transfer_to_warehouse_id"
                          style="height:34px;border-radius:6px;font-size:12.5px;border-color:#cbd5e1;">
                          <option value="">Pilih toko atau gudang</option>
                        </select>
                      </div>
                      <div class="col-12 d-none" id="st-date-slot-tujuan"></div>
                      {{-- Bukti foto pengiriman (GitHub #140) — muncul hanya jika ada foto --}}
                      <div class="col-12 d-none" id="st-ship-proof-slot">
                        <label class="text-muted mb-0.5"
                          style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:#475569;"><i
                            class="fe fe-camera me-1 text-primary"></i>Bukti Foto Kirim</label>
                        <button type="button" class="btn w-100 p-0 btn-view-st-ship-proof" id="st-ship-proof-link"
                          data-parent="#add_stock_transfer"
                          style="border-radius:8px;height:42px;background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;border:none;font-weight:600;box-shadow:0 4px 12px rgba(59,130,246,.3);">
                          <div class="d-flex align-items-center justify-content-center w-100 h-100">
                            <i class="fe fe-image me-1"></i> Lihat Foto
                          </div>
                        </button>
                      </div>
                      <div class="col-12" id="st-note-slot-tujuan">
                        <div id="st-note-block" class="d-flex flex-column">
                          <label class="text-muted mb-0.5"
                            style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:#475569;"><i
                              class="fe fe-file-text me-1 text-success"></i>Catatan (Opsional)</label>
                          <input type="text" class="form-control mt-0.5" id="transfer_note" placeholder="Masukkan catatan tambahan..."
                            style="height:34px;border-radius:6px;font-size:12.5px;border-color:#cbd5e1;">
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

              </div>
            </div>

            <div class="d-flex justify-content-center mt-1">
              <button class="btn btn-sm btn-light text-muted border-0 d-inline-flex align-items-center gap-1 px-2.5 py-0.5" type="button" data-bs-toggle="collapse"
                data-bs-target="#collapseStockTransferForm" aria-expanded="true"
                aria-controls="collapseStockTransferForm"
                style="font-size: 10px; font-weight: 600; border-radius: 16px; background: #f8fafc; color: #64748b; height: 22px; line-height: 20px;"
                onclick="var icon = this.querySelector('i'); icon.classList.toggle('fe-chevron-up'); icon.classList.toggle('fe-chevron-down');"
                title="Toggle Detail Rute">
                <span>Rute Transfer</span>
                <i class="fe fe-chevron-up" style="font-size: 11px;"></i>
              </button>
            </div>
          </div>

          {{-- PRODUCT INPUT TOOLBAR --}}
          <div class="transfer-product-panel px-4 py-3" style="background:#f8fafc; border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;">
            <div class="transfer-product-grid">
              <div class="transfer-product-field transfer-product-select">
                <label class="form-label mb-1.5" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#475569;">
                  <i class="fe fe-box me-1 text-primary"></i>Pilih Produk <span class="text-danger">*</span>
                </label>
                <div id="transfer_mode_select">
                  <select class="form-select form-control fill" id="transfer_sku" style="height:40px;border-radius:8px;font-size:13px;border-color:#cbd5e1;">
                    <option value="" selected disabled>Pilih gudang asal terlebih dahulu</option>
                  </select>
                </div>
                <div id="transfer_mode_scan" style="display:none">
                  <div class="input-group">
                    <input type="text" class="form-control" id="transfer_scan_barcode"
                      placeholder="Scan / ketik barcode atau SKU" style="height:40px;border-radius:8px 0 0 8px;font-size:13px;">
                    <input type="number" class="form-control" id="transfer_scan_qty" value="1"
                      min="1" step="1" title="Qty scan" style="max-width:80px;height:40px;font-size:13px;">
                    <button type="button" class="btn btn-primary px-3" id="btn_scan_add_transfer"
                      title="Cari produk" style="height:40px;border-radius:0 8px 8px 0;">
                      <i class="fe fe-search"></i>
                    </button>
                  </div>
                </div>
              </div>
              <div class="transfer-product-field transfer-draft-only" style="min-width: 90px;">
                <label class="form-label mb-1.5" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#475569;">
                  Qty <span class="text-danger">*</span>
                </label>
                <input type="number" class="form-control" id="transfer_qty_input" placeholder="Qty"
                  value="1" min="1" step="1" style="height:40px;border-radius:8px;font-size:13px;border-color:#cbd5e1;font-weight:600;text-align:center;">
              </div>
              <div class="transfer-product-field transfer-draft-only" style="min-width: 170px;">
                <div class="d-flex align-items-center justify-content-between mb-1.5">
                  <label class="form-label mb-0" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#475569;">
                    Satuan <span class="text-danger">*</span>
                  </label>
                  <small id="transfer_stock_available" class="text-muted" style="font-size:11px;font-weight:500;">Stok: -</small>
                </div>
                <select class="form-select" id="transfer_unit_input" disabled style="height:40px;border-radius:8px;font-size:13px;border-color:#cbd5e1;">
                  <option value="">Pilih produk dahulu</option>
                </select>
              </div>
              <div class="transfer-product-actions d-flex gap-2">
                <button type="button" class="btn btn-primary transfer-draft-only d-inline-flex align-items-center justify-content-center gap-1.5" id="btn_add_transfer_product">
                  <i class="fe fe-plus"></i> Tambah
                </button>
                <button type="button" class="btn btn-light border d-inline-flex align-items-center justify-content-center gap-1.5" id="btn_toggle_scan_transfer"
                  title="Ganti mode input">
                  <i class="fa fa-barcode"></i> Scan
                </button>
              </div>
            </div>
          </div>

          {{-- PRODUCT TABLE --}}
          <div class="d-flex flex-column" style="background:#fff;">
            <div class="d-flex align-items-center justify-content-between border-bottom"
              style="background:#f8fafc; padding: 14px 28px;">
              <div class="d-flex align-items-center">
                <i class="fe fe-layers text-primary me-2" style="font-size:15px;"></i>
                <span class="fw-bold text-dark" style="font-size:12px; text-transform:uppercase; letter-spacing:.5px;">Daftar Produk yang Ditransfer</span>
              </div>
              <button type="button"
                class="btn btn-sm btn-outline-primary btn-enable-edit-transfer d-none align-items-center gap-1"
                style="border-radius:8px;font-size:12px;font-weight:600;padding:6px 14px;height:34px;">
                <i class="fe fe-edit-2"></i> Edit Data
              </button>
            </div>
            <div class="table-responsive" style="min-height:220px; background:#fff;">
              <table class="table table-hover mb-0" id="tableTransferItems" style="font-size:13px; table-layout:fixed; width:100%;">
                <thead style="background:#ffffff; border-bottom: 2px solid #e2e8f0;">
                  <tr>
                    <th
                      style="width: 24%; color: #1e3a8a; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing:.5px; padding: 12px 16px;">
                      Produk</th>
                    <th
                      style="width: 16%; color: #1e3a8a; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing:.5px; padding: 12px 16px;">
                      Varian</th>
                    <th
                      style="width: 12%; color: #1e3a8a; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing:.5px; padding: 12px 16px;">
                      SKU</th>
                    <th
                      style="width: 18%; color: #1e3a8a; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing:.5px; padding: 12px 16px;">
                      Stok Asal</th>
                    <th
                      style="width: 220px; color: #1e3a8a; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing:.5px; padding: 12px 16px;">
                      Qty / Satuan</th>
                    <th class="no-sort text-center"
                      style="width: 56px; color: #1e3a8a; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing:.5px; padding: 12px 16px;">
                      Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr class="empty-row">
                    <td colspan="6" class="text-center py-5">
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

        {{-- ── FOOTER: Batal, Tolak, Setujui QC/Ops, Kirim, Simpan ── --}}
        <div class="modal-footer pg-modal-footer">
          <button type="button" class="btn pg-btn-cancel btn-cancel-transfer">Batal</button>
          <button type="button" class="btn pg-btn-decline btn-reject-transfer d-none"><i class="fe fe-x me-1"></i>Tolak</button>
          <button type="button" class="btn pg-btn-accept btn-approve-qc-transfer d-none"><i class="fe fe-check me-1"></i>Setujui QC</button>
          <button type="button" class="btn pg-btn-accept btn-approve-ops-transfer d-none"><i class="fe fe-check-circle me-1"></i>Setujui Ops</button>
          <button type="button" class="btn pg-btn-accept btn-acc-transfer d-none"><i class="fe fe-truck me-1"></i>Kirim</button>
          <button type="button" class="btn pg-btn-save btn-save-transfer"><i class="fe fe-save me-1"></i>Simpan</button>
          {{-- Bukti foto Kirim (GitHub #140) untuk kombo Simpan+Kirim (edit mode, tanpa #modalKonfirmasi) --}}
          <input type="hidden" id="transfer_then_ship_proof_base64">
        </div>
      </form>
    </div>
  </div>
