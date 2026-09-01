<div class="modal modal-lg custom-modal fade pg-modal--confirm" id="accept_stock_transfer" role="dialog" data-bs-backdrop="static"
    data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width: 90vw;">
      <form action="#" id="formAcceptStockTransfer" class="modal-content d-flex flex-column"
        style="border-radius: 16px; overflow: hidden; border: none; max-height: 90vh;">

        {{-- ── HEADER ── --}}
        <div class="modal-header">
          <div class="d-flex align-items-center gap-3">
            <div class="pg-modal-icon">
              <i class="fe fe-check-circle"></i>
            </div>
            <div>
              <h5 class="mb-0 fw-bold modal-title">Terima Stock Transfer</h5>
              <small class="text-muted accept-modal-subtitle">Konfirmasi penerimaan produk dan stok akan
                bertambah</small>
            </div>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
            aria-label="Close"></button>
        </div>

        {{-- ── ROUTE INFO PANEL ── --}}
        <div class="border-bottom" style="background:#ffffff;padding:24px 28px 16px 28px;">
          <div class="row g-3 align-items-center">

            {{-- Info Asal --}}
            <div class="col-md-5">
              <div class="d-flex flex-column h-100">
                <div class="d-flex align-items-center gap-2 mb-1.5">
                  <span class="badge d-inline-flex align-items-center gap-1 px-2 py-1 rounded-2"
                    style="background:#eff6ff; color:#1d4ed8; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; border:1px solid #bfdbfe;">
                    <i class="fe fe-log-out" style="font-size:11px;"></i>
                    <span>Dari (Asal)</span>
                  </span>
                </div>
                <div class="row g-2 mt-0">
                  <div class="col-6">
                    <div class="text-muted"
                      style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:#475569;"><i
                        class="fe fe-user me-1 text-primary"></i>Pengirim</div>
                    <div class="fw-bold text-dark mt-0.5" style="font-size:12.5px;" id="lbl_accept_sender">-</div>
                  </div>
                  <div class="col-6">
                    <div class="text-muted"
                      style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:#475569;"><i
                        class="fe fe-box me-1 text-primary"></i>Gudang Asal</div>
                    <div class="fw-bold text-dark mt-0.5" style="font-size:12.5px;" id="lbl_accept_from">-</div>
                  </div>
                  <div class="col-6">
                    <div class="text-muted"
                      style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:#475569;"><i
                        class="fe fe-calendar me-1 text-primary"></i>Tanggal Pengiriman</div>
                    <div class="fw-bold text-dark mt-0.5" style="font-size:12.5px;" id="lbl_accept_date">-</div>
                  </div>
                  <div class="col-6">
                    <div class="text-muted"
                      style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:#475569;"><i
                        class="fe fe-file-text me-1 text-primary"></i>Catatan Pengiriman</div>
                    <div class="fw-bold text-dark mt-0.5" style="font-size:12.5px;" id="lbl_accept_ship_note">-</div>
                  </div>
                </div>
              </div>
            </div>

            {{-- Arrow --}}
            <div class="col-md-2 d-flex align-items-center justify-content-center">
              <div style="display:flex;flex-direction:column;align-items:center;gap:3px;">
                <div
                  style="width:34px;height:34px;background:linear-gradient(135deg,#059669,#10b981);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;box-shadow:0 3px 8px rgba(5,150,105,.25);">
                  <i class="fe fe-arrow-right text-white" style="font-size:14px;"></i>
                </div>
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5"
                  style="font-size:9.5px;font-weight:700;letter-spacing:.4px;">TERIMA</span>
              </div>
            </div>

            {{-- Info Tujuan --}}
            <div class="col-md-5">
              <div class="d-flex flex-column h-100">
                <div class="d-flex align-items-center gap-2 mb-1.5">
                  <span class="badge d-inline-flex align-items-center gap-1 px-2 py-1 rounded-2"
                    style="background:#f0fdf4; color:#15803d; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; border:1px solid #bbf7d0;">
                    <i class="fe fe-log-in" style="font-size:11px;"></i>
                    <span>Ke (Tujuan)</span>
                  </span>
                </div>
                <div class="row g-2 mt-0 flex-grow-1">
                  <div class="col-6">
                    <label class="text-muted mb-0.5"
                      style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:#475569;">Gudang
                      Tujuan</label>
                    <div class="fw-bold text-dark mt-0.5" style="font-size:12.5px;" id="lbl_accept_to">-</div>
                  </div>
                  <div class="col-6">
                    <label class="text-muted mb-0.5"
                      style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:#475569;">Penerima</label>
                    <select class="form-select form-control fill select2 mt-0.5" id="accept_receiver_id"
                      style="height:34px;border-radius:6px;font-size:12.5px;border-color:#cbd5e1;" disabled>
                      <option value="">-</option>
                    </select>
                  </div>
                  <div class="col-12">
                    <label class="text-muted mb-0.5"
                      style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:#475569;"><i
                        class="fe fe-file-text me-1 text-success"></i>Catatan Penerimaan</label>
                    <input type="text" class="form-control mt-0.5" id="accept_note" placeholder="Masukkan catatan tambahan bila ada..."
                      style="height:34px;border-radius:6px;font-size:12.5px;border-color:#cbd5e1;">
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {{-- ── PRODUCT TABLE ── --}}
        <div class="modal-body p-0 d-flex flex-column" style="flex:1 1 auto;overflow:hidden;">
          <div class="d-flex justify-content-between align-items-center border-bottom"
            style="background:#f8fafc; padding: 14px 28px;">
            <div class="d-flex align-items-center">
              <i class="fe fe-layers text-success me-2" style="font-size:15px;"></i>
              <span class="fw-bold text-dark" style="font-size:12px; text-transform:uppercase; letter-spacing:.5px;">Daftar Produk yang Diterima</span>
            </div>
            <div style="width: 420px; max-width: 100%; position:relative;">
              <i class="fe fe-search position-absolute"
                style="top:50%; transform:translateY(-50%); left:14px; color:#94a3b8; font-size:14px;"></i>
              <input type="text" class="form-control" id="search_accept_barcode"
                placeholder="Ketik nama, SKU, atau scan barcode..."
                style="border-radius:20px; font-size:13px; padding-left:38px; padding-right:16px; height: 40px; border-color:#cbd5e1; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
            </div>
          </div>
          <div class="table-responsive flex-grow-1" style="min-height:240px;overflow-y:auto;">
            <table class="table table-hover mb-0" id="tableAcceptItems" style="font-size:13px; width:100%; table-layout:fixed;">
              <thead style="background:#ffffff; border-bottom: 2px solid #e2e8f0; position:sticky; top:0; z-index:2;">
                <tr>
                  <th style="width: 25%;">Produk</th>
                  <th style="width: 15%;">Varian</th>
                  <th style="width: 14%;">SKU</th>
                  <th style="width: 130px;" class="text-center">Qty Kirim</th>
                  {{-- Sembunyikan sementara: terima langsung = qty kirim (toggle class d-none untuk tampilkan lagi) --}}
                  <th style="width: 140px;" class="text-center d-none st-col-qty-terima">Qty Terima</th>
                  <th style="width: 140px;" class="text-center">Hasil Konversi</th>
                  {{-- Sembunyikan sementara: terima langsung = qty kirim (toggle class d-none untuk tampilkan lagi) --}}
                  <th style="width: 110px;" class="text-center d-none st-col-selisih">Selisih</th>
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

        {{-- ── FOOTER ── --}}
        <div class="modal-footer pg-modal-footer">
          <button type="button" data-bs-dismiss="modal" class="btn pg-btn-cancel">Batal</button>
          {{-- Tolak terima: di-hide dulu (jangan hapus) — client belum pakai --}}
          <button type="button" class="btn pg-btn-decline btn-reject-accept-transfer d-none"><i class="fe fe-x me-1"></i>Tolak</button>
          <button type="button" class="btn pg-btn-accept btn-accept-transfer"><i class="fe fe-check-circle me-1"></i>Terima</button>
        </div>

      </form>
    </div>
  </div>
