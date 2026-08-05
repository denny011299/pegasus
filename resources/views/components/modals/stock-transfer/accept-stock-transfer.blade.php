  <div class="modal modal-lg custom-modal fade" id="accept_stock_transfer" role="dialog" data-bs-backdrop="static"
    data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable" style="max-width: 90vw;">
      <form action="#" id="formAcceptStockTransfer" class="modal-content"
        style="border-radius:16px;overflow:hidden;border:none;">

        {{-- ── HEADER ── --}}
        <div class="modal-header border-0"
          style="background:linear-gradient(135deg,#064e3b 0%,#059669 100%);padding:18px 24px;">
          <div class="d-flex align-items-center gap-3">
            <div
              style="width:40px;height:40px;background:rgba(255,255,255,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;">
              <i class="fe fe-check-circle text-white" style="font-size:18px;"></i>
            </div>
            <div>
              <h5 class="mb-0 text-white fw-bold">Terima Stock Transfer</h5>
              <small class="text-white-50">Konfirmasi penerimaan barang</small>
            </div>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
            aria-label="Close"></button>
        </div>

        {{-- ── ROUTE INFO PANEL ── --}}
        <div class="border-bottom" style="background:#f8fafc;padding:16px 24px;">
          <div class="row g-3 align-items-stretch">

            {{-- Info Asal --}}
            <div class="col-md-5">
              <div class="d-flex flex-column"
                style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:14px 16px;height:100%;box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
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
                        class="fe fe-user me-1"></i>Pengirim</div>
                    <div class="fw-bold text-dark mt-2" style="font-size:14px;" id="lbl_accept_sender">-</div>
                  </div>
                  <div class="col-6">
                    <div class="text-muted"
                      style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;"><i
                        class="fe fe-box me-1"></i>Gudang Asal</div>
                    <div class="fw-bold text-dark mt-2" style="font-size:14px;" id="lbl_accept_from">-</div>
                  </div>
                  <div class="col-6">
                    <div class="text-muted"
                      style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;"><i
                        class="fe fe-calendar me-1"></i>Tanggal Pengiriman</div>
                    <div class="fw-bold text-dark mt-2" style="font-size:14px;" id="lbl_accept_date">-</div>
                  </div>
                  <div class="col-6">
                    <div class="text-muted"
                      style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;"><i
                        class="fe fe-file-text me-1"></i>Catatan Pengiriman</div>
                    <div class="fw-bold text-dark mt-2" style="font-size:14px;" id="lbl_accept_ship_note">-</div>
                  </div>
                </div>
              </div>
            </div>

            {{-- Arrow --}}
            <div class="col-md-2 d-flex align-items-center justify-content-center">
              <div style="display:flex;flex-direction:column;align-items:center;gap:6px;">
                <div
                  style="width:44px;height:44px;background:linear-gradient(135deg,#059669,#10b981);border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(5,150,105,.3);">
                  <i class="fe fe-arrow-right text-white" style="font-size:18px;"></i>
                </div>
                <span class="text-muted" style="font-size:10px;font-weight:600;letter-spacing:.5px;">TERIMA</span>
              </div>
            </div>

            {{-- Info Tujuan --}}
            <div class="col-md-5">
              <div class="d-flex flex-column"
                style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:14px 16px;height:100%;">
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
                  <div class="col-6">
                    <label class="text-muted mb-1"
                      style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Gudang
                      Tujuan</label>
                    <div class="fw-bold text-dark mt-2" style="font-size:14px;" id="lbl_accept_to">-</div>
                  </div>
                  <div class="col-6">
                    <label class="text-muted mb-1"
                      style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Penerima</label>
                    <select class="form-select form-control fill select2 mt-1" id="accept_receiver_id"
                      style="border-radius:8px;font-size:13px;" disabled>
                      <option value="">-</option>
                    </select>
                  </div>
                  <div class="col-12 d-flex flex-column" style="margin-top: 1.5rem;">
                    <label class="text-muted mb-2"
                      style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Catatan
                      Penerimaan</label>
                    <textarea class="form-control flex-grow-1" id="accept_note" placeholder="Masukkan catatan tambahan bila ada..."
                      style="border-radius:8px;font-size:13px;resize:none; min-height:80px;"></textarea>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {{-- ── PRODUCT TABLE ── --}}
        <div class="modal-body p-0 d-flex flex-column" style="flex:1 1 auto;overflow:hidden;">
          <div class="d-flex justify-content-between align-items-center px-4 py-3 border-bottom"
            style="background:#f8fafc;">
            <div class="d-flex align-items-center">
              <i class="fe fe-list text-success me-2"></i>
              <span class="fw-semibold text-dark" style="font-size:13px;">Daftar Produk yang Diterima</span>
            </div>
            <div style="width: 320px; position:relative;">
              <i class="fe fe-search position-absolute"
                style="top:50%; transform:translateY(-50%); left:12px; color:#94a3b8; font-size:14px;"></i>
              <input type="text" class="form-control form-control-sm" id="search_accept_barcode"
                placeholder="Ketik nama, SKU, atau scan barcode..."
                style="border-radius:20px; font-size:13px; padding-left:36px; height: 36px; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
            </div>
          </div>
          <div class="table-responsive flex-grow-1" style="min-height:240px;overflow-y:auto;">
            <table class="table table-hover mb-0" id="tableAcceptItems" style="font-size:14px;">
              <thead style="background:#f1f5f9;position:sticky;top:0;z-index:2;">
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
                    class="text-center">Qty Kirim</th>
                  <th
                    style="width: 180px; color: #475569; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing:.5px; padding: 14px 16px;"
                    class="text-center">Qty Terima</th>
                  <th
                    style="width: 160px; color: #475569; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing:.5px; padding: 14px 16px;"
                    class="text-center">Hasil Konversi</th>
                  <th
                    style="width: 140px; color: #475569; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing:.5px; padding: 14px 16px;"
                    class="text-center">Selisih</th>
                </tr>
              </thead>
              <tbody>
                <tr class="empty-row">
                  <td colspan="7" class="text-center py-5">
                    <div style="color:#94a3b8;">
                      <i class="fe fe-inbox" style="font-size:36px;display:block;margin-bottom:8px;"></i>
                      <div class="fw-semibold" style="font-size:14px;">Belum ada produk</div>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        {{-- ── FOOTER: Batal, Terima (kanan) ── --}}
        <div class="modal-footer border-top d-flex justify-content-end align-items-center gap-2"
          style="background:#f8fafc;padding:14px 24px;">
          <button type="button" data-bs-dismiss="modal" class="btn"
            style="border:1px solid #e2e8f0;border-radius:8px;padding:9px 20px;font-size:13px;font-weight:600;color:#64748b;min-width:130px;height:42px;">Batal</button>
          <button type="button"
            class="btn btn-accept-transfer d-inline-flex align-items-center justify-content-center gap-2"
            style="background:linear-gradient(135deg,#059669,#16a34a);color:#fff;border:none;border-radius:8px;padding:9px 28px;font-size:13px;font-weight:600;min-width:150px;height:42px;box-shadow:0 4px 12px rgba(5,150,105,.3);"><i
              class="fe fe-check-circle me-1"></i>Terima Transfer</button>
        </div>

      </form>
    </div>
  </div>
