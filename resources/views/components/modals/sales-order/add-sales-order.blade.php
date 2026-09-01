<div class="modal fade custom-modal pg-modal--form" id="add_sales_order" data-bs-backdrop="static"
    data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width: 90vw;">
      <div class="modal-content d-flex flex-column" style="border-radius: 16px; overflow: hidden; border: none; max-height: 92vh;">

        {{-- ── HEADER ── --}}
        <div class="modal-header">
          <div class="d-flex align-items-center gap-3">
            <div class="pg-modal-icon">
              <i class="fe fe-shopping-cart"></i>
            </div>
            <div>
              <h5 class="mb-0 fw-bold modal-title">Sales Order</h5>
              <small class="text-muted modal-subtitle">Buat penjualan produk jadi ke pelanggan</small>
            </div>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
            aria-label="Close"></button>
        </div>
        <form action="#" class="d-flex flex-column flex-grow-1 overflow-hidden">
          <div class="modal-body" style="overflow-y: auto; flex: 1 1 auto;">
            <div class="form-groups-item border-0 pb-0">
              <div class="row">
                <div class="col-12 row pe-0">
                  <div class="col-lg-3 col-md-6 col-12 pe-0">
                    <div class="input-block mb-3">
                      <label class="form-label text-muted fw-semibold"
                        style="font-size: 11px; text-transform:uppercase; letter-spacing:.4px;">Tanggal <span
                          class="text-danger">*</span></label>
                      <input type="date" class="form-control fill" id="so_date"
                        style="border-radius: 8px; height:42px;">
                    </div>
                  </div>
                  <div class="col-lg-4 col-md-6 col-12 pe-0">
                    <div class="input-block mb-3 " id="row-Armada">
                      <label class="form-label text-muted fw-semibold"
                        style="font-size: 11px; text-transform:uppercase; letter-spacing:.4px;">Nama Armada <span
                          class="text-danger">*</span></label>
                      <select id="so_customer" class="form-control fill"
                        style="border-radius: 8px; height:42px;"></select>
                    </div>
                  </div>
                  <div class="col-lg-3 col-md-6 col-12 pe-0">
                    <div class="input-block mb-3">
                      <label class="form-label text-muted fw-semibold"
                        style="font-size: 11px; text-transform:uppercase; letter-spacing:.4px;">Ref Number</label>
                      <input id="so_ref_number" class="form-control" placeholder="Input Ref Number"
                        style="border-radius: 8px; height:42px;">
                    </div>
                  </div>
                  <div class="col-lg-2 col-md-6 col-12 pe-0">
                    <div class="input-block mb-3">
                      <label class="form-label text-muted fw-semibold"
                        style="font-size: 11px; text-transform:uppercase; letter-spacing:.4px; display: block; margin-bottom: 8px;">Bukti
                        Foto<span class="text-danger ms-1">*</span>
                        <span id="check_foto" style="display: none; align-items:center;" class="ms-2">
                          <i class="fa fa-check-circle text-success mt-1"></i>
                        </span>
                      </label>
                      <div class="d-flex gap-2">
                        <button class="btn w-100 p-0" id="btn_bukti_foto" type="button"
                          style="border-radius:8px;height:42px;background:#eff6ff;border:1px solid #bfdbfe;color:#2563eb;font-weight:600;">
                          <div class="d-flex align-items-center justify-content-center w-100 h-100"><i
                              class="fe fe-camera me-1"></i> Upload</div>
                        </button>
                        <button type="button" class="btn w-100 p-0" id="btn-lihat-bukti"
                          style="display:none;border-radius:8px;height:42px;background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;border:none;font-weight:600;box-shadow:0 4px 12px rgba(59,130,246,.3);">
                          <div class="d-flex align-items-center justify-content-center w-100 h-100"><i
                              class="fe fe-image me-1"></i> Lihat</div>
                        </button>
                      </div>
                      <input type="hidden" name="" id="bukti">
                      <small class="text-muted d-block mt-1" style="display: none !important;"><span
                          id="jumlahFoto">1</span></small>
                    </div>
                  </div>
                </div>
                <div class="col-12 row pe-0">
                </div>
                <div class="col-12 row pe-0 mb-4 align-items-end">
                  <div class="col-lg-6 col-md-12 col-12 pe-0">
                    <div class="input-block mb-lg-0 mb-3">
                      <label class="form-label text-muted fw-semibold d-flex align-items-center gap-2"
                        style="font-size: 11px; text-transform:uppercase; letter-spacing:.4px;">
                        Pilih Produk (SKU)
                        <button type="button"
                          class="btn btn-sm btn-outline-secondary py-0 px-2 d-inline-flex align-items-center"
                          id="btn_toggle_scan_so" title="Ganti mode Scan"
                          style="border-radius: 4px; font-size: 10px; min-height: 20px;">
                          <i class="fa fa-barcode me-1"></i> Mode Scan
                        </button>
                      </label>
                      <div id="so_mode_select">
                        <select class="form-select" id="so_sku"></select>
                      </div>
                      <div id="so_mode_scan" style="display:none">
                        <div class="input-group"
                          style="border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.02); height: 42px;">
                          <input type="text" class="form-control" id="so_scan_barcode"
                            placeholder="Scan / ketik barcode..."
                            style="flex: 1 1 auto; border-right: 0; height: 42px;">
                          <input type="number" class="form-control" id="so_scan_qty" placeholder="Qty"
                            value="1" min="1"
                            style="max-width:90px; border-left: 1px solid #e2e8f0; height: 42px;">
                          <button type="button"
                            class="btn d-inline-flex align-items-center justify-content-center px-3"
                            id="btn_scan_add_so" title="Tambah Produk"
                            style="background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;border:none;">
                            <i class="fa fa-plus"></i>
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="col-lg-6 col-md-12 col-12 pe-0">
                  </div>
                </div>
                <div class="col-12 mb-3">
                  <div class="d-flex align-items-center px-4 py-2 border-bottom" style="background:#f8fafc;">
                    <i class="fe fe-list text-primary me-2"></i>
                    <span class="fw-semibold text-dark" style="font-size:13px;">Daftar Produk</span>
                  </div>
                  <div class="table-responsive flex-grow-1"
                    style="max-height: 300px; overflow-y: auto; border: 1px solid #e2e8f0; border-top: 0; border-radius: 0 0 8px 8px;">
                    <table class="table table-center table-hover mb-0" id="tableSalesItems"
                      style="font-size:13px;">
                      <thead
                        style="background:#f1f5f9; position: sticky; top: 0; z-index: 10; border-bottom: 1px solid #e2e8f0;">
                        <tr>
                          <th
                            style="color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing:.4px; padding: 10px 24px;">
                            Produk</th>
                          <th
                            style="color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing:.4px; padding: 10px 24px;">
                            Variasi</th>
                          <th
                            style="color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing:.4px; padding: 10px 24px;">
                            SKU</th>
                          <th class="text-center"
                            style="width: 140px; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing:.4px; padding: 10px 24px;">
                            Jumlah</th>
                          <th
                            style="width: 240px; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing:.4px; padding: 10px 16px;">
                            Gudang Stok</th>
                          <th class="text-center"
                            style="width: 100px; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing:.4px; padding: 10px 24px;">
                            Action</th>
                        </tr>
                      </thead>
                      <tbody id="tableSalesModal">

                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
            {{-- ── FOOTER ── --}}
            <div class="modal-footer pg-modal-footer">
              <button type="button" data-bs-dismiss="modal" class="btn pg-btn-cancel">Batal</button>
              @roleCan('Pengiriman', 'others')
                <button type="button" class="btn pg-btn-decline btn_decline d-none"><i class="fe fe-x me-1"></i>Tolak</button>
                <button type="button" class="btn pg-btn-accept btn_acc d-none"><i class="fe fe-check-circle me-1"></i>Terima</button>
              @endroleCan
              <button type="button" class="btn pg-btn-save paid-continue-btn btn-save">
                <i class="fe fe-save me-1"></i><span id="btn_save_text">Tambah Pengiriman</span>
              </button>
            </div>
        </form>
      </div>
    </div>
  </div>
