  <div class="modal modal-lg custom-modal fade" id="view_stock_transfer" role="dialog" data-bs-backdrop="static"
    data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-xl"
      style="max-width: 90vw; max-height: 92vh; margin: 1rem auto;">
      <div class="modal-content d-flex flex-column"
        style="border-radius: 16px; overflow: hidden; border: none; max-height: 92vh;">
        <div class="modal-header border-0 flex-shrink-0"
          style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); padding: 18px 24px;">
          <div class="d-flex align-items-center gap-3">
            <div
              style="width:40px;height:40px;background:rgba(255,255,255,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;">
              <i class="fe fe-file-text text-white" style="font-size:18px;"></i>
            </div>
            <div>
              <h5 class="mb-0 text-white fw-bold modal-title">View Stock Transfer</h5>
              <small class="text-white-50 mb-0 mt-1" style="font-size:13px;">Detail data transfer stok</small>
            </div>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
            aria-label="Close"></button>
        </div>
        <div class="modal-body p-0 position-relative flex-grow-1"
          style="overflow-y: auto; min-height: 0; background:#f8fafc;">
          <div id="view_stock_transfer_loading"
            style="display:none;position:absolute;inset:0;z-index:20;background:rgba(248,250,252,.92);flex-direction:column;align-items:center;justify-content:center;gap:12px;">
            <div class="spinner-border text-primary" style="width:2.5rem;height:2.5rem;" role="status"
              aria-hidden="true"></div>
            <div class="text-muted fw-semibold" style="font-size:13px;">Memuat detail transfer…</div>
          </div>
          <div class="border-bottom" style="background:#f8fafc; padding: 16px 24px;">
            <div class="row g-3 align-items-stretch">
              {{-- Card Asal --}}
              <div class="col-md-5">
                <div
                  style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:14px 16px; height:100%; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                  <div class="d-flex align-items-center gap-2 mb-2">
                    <span
                      style="width:28px;height:28px;background:#eff6ff;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                      <i class="fe fe-log-out" style="font-size:13px;color:#3b82f6;"></i>
                    </span>
                    <span class="fw-semibold text-primary"
                      style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;">Dari (Asal)</span>
                  </div>
                  <div class="row g-3 mt-0">
                    <div class="col-6">
                      <div class="text-muted"
                        style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;"><i
                          class="fe fe-user me-1"></i> Pengirim</div>
                      <div class="fw-bold text-dark mt-2" id="lbl_view_sender" style="font-size:14px;">-</div>
                    </div>
                    <div class="col-6">
                      <div class="text-muted"
                        style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;"><i
                          class="fe fe-box me-1"></i> Gudang Asal</div>
                      <div class="fw-bold text-dark mt-2" id="lbl_view_from" style="font-size:14px;">-</div>
                    </div>
                    <div class="col-6">
                      <div class="text-muted"
                        style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;"><i
                          class="fe fe-calendar me-1"></i> Tanggal Pengiriman</div>
                      <div class="fw-bold text-dark mt-2" id="lbl_view_date" style="font-size:14px;">-</div>
                    </div>
                    <div class="col-6">
                      <div class="text-muted"
                        style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;"><i
                          class="fe fe-file-text me-1"></i> Catatan Pengiriman</div>
                      <div class="fw-bold text-dark mt-2" id="lbl_view_ship_note" style="font-size:14px;">-</div>
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
                  <span class="text-muted" style="font-size:10px;font-weight:600;letter-spacing:.5px;">TRANSFER</span>
                </div>
              </div>

              {{-- Card Tujuan --}}
              <div class="col-md-5">
                <div
                  style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:14px 16px; height:100%; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                  <div class="d-flex align-items-center gap-2 mb-2">
                    <span
                      style="width:28px;height:28px;background:#f0fdf4;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                      <i class="fe fe-log-in" style="font-size:13px;color:#22c55e;"></i>
                    </span>
                    <span class="fw-semibold"
                      style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:#16a34a;">Ke
                      (Tujuan)</span>
                  </div>
                  <div class="row g-3 mt-0">
                    <div class="col-6">
                      <div class="text-muted"
                        style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;"><i
                          class="fe fe-box me-1"></i> Gudang Tujuan</div>
                      <div class="fw-bold text-dark mt-2" id="lbl_view_to" style="font-size:14px;">-</div>
                    </div>
                    <div class="col-6">
                      <div class="text-muted"
                        style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;"><i
                          class="fe fe-user-check me-1"></i> Penerima</div>
                      <div class="fw-bold text-dark mt-2" id="lbl_view_receiver" style="font-size:14px;">-</div>
                    </div>
                    <div class="col-12">
                      <div class="text-muted"
                        style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;"><i
                          class="fe fe-check-square me-1"></i> Catatan Penerimaan</div>
                      <div class="fw-bold text-dark mt-2" id="lbl_view_accept_note" style="font-size:14px;">-</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="d-flex justify-content-between align-items-center px-4 py-3 border-bottom"
            style="background:#f8fafc;">
            <div class="d-flex align-items-center">
              <i class="fe fe-list text-primary me-2"></i>
              <span class="fw-semibold text-dark" style="font-size:13px;">Produk yang di Transfer</span>
            </div>
            <div style="width: 320px; position:relative;">
              <i class="fe fe-search position-absolute"
                style="top:50%; transform:translateY(-50%); left:12px; color:#94a3b8; font-size:14px;"></i>
              <input type="text" class="form-control form-control-sm" id="search_view_barcode"
                placeholder="Ketik nama, SKU, atau scan barcode..."
                style="border-radius:20px; font-size:13px; padding-left:36px; height: 36px; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
            </div>
          </div>
          <div class="table-responsive" style="min-height: 250px; background:#fff;">
            <table class="table table-center table-hover mb-0" id="tableViewItems" style="font-size:14px;">
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
                    style="width: 140px; color: #475569; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing:.5px; padding: 14px 16px;"
                    class="text-center">Kirim (Asli)</th>
                  <th
                    style="width: 140px; color: #475569; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing:.5px; padding: 14px 16px;"
                    class="text-center">Qty Terima</th>
                  <th
                    style="width: 140px; color: #475569; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing:.5px; padding: 14px 16px;"
                    class="text-center">Hasil Konversi</th>
                  <th
                    style="width: 130px; color: #475569; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing:.5px; padding: 14px 16px;"
                    class="text-center">Selisih</th>
                </tr>
              </thead>
              <tbody>
                <tr class="empty-row">
                  <td colspan="7" class="text-center text-muted py-5" style="font-size: 14px;">Belum ada produk.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer border-top flex-shrink-0" style="background:#f8fafc; padding:14px 24px;">
          <button type="button" data-bs-dismiss="modal" class="btn"
            style="background:#f1f5f9; border:1px solid #e2e8f0; color:#475569; border-radius: 8px; font-weight: 600; padding: 9px 24px; font-size: 13px;">Tutup</button>
        </div>
      </div>
    </div>
  </div>
