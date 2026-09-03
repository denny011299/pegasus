<div class="modal modal-lg custom-modal fade pg-modal--form" id="view_stock_transfer" role="dialog" data-bs-backdrop="static"
    data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable" style="max-width: 90vw;">
      <div class="modal-content" style="border-radius:16px;overflow:hidden;border:none;">

        {{-- ── HEADER ── --}}
        <div class="modal-header d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-3">
            <div class="pg-modal-icon">
              <i class="fe fe-file-text"></i>
            </div>
            <div>
              <h5 class="mb-0 fw-bold modal-title text-white">Detail Stock Transfer</h5>
              <small style="color: rgba(255,255,255,0.75);">Informasi lengkap pengiriman stok</small>
            </div>
          </div>

          {{-- Approval & Acc Kirim di Header Kanan --}}
          <div class="d-flex align-items-center gap-2 ms-auto me-3 d-none" id="view_transfer_approval_block">
            <div class="d-flex align-items-center gap-1.5 text-white" id="view_qc_wrap"
              style="background: rgba(255,255,255,0.15); padding: 5px 12px; border-radius: 20px; font-size: 11.5px; border: 1px solid rgba(255,255,255,0.25);">
              <i class="fe fe-check" style="color:#86efac; font-size:12px;"></i>
              <span style="opacity: 0.85;">QC:</span>
              <span class="fw-bold" id="lbl_view_qc_by">-</span>
              <span style="opacity: 0.7; font-size: 10.5px;" id="lbl_view_qc_at">-</span>
            </div>
            <div class="d-flex align-items-center gap-1.5 text-white" id="view_ops_wrap"
              style="background: rgba(255,255,255,0.15); padding: 5px 12px; border-radius: 20px; font-size: 11.5px; border: 1px solid rgba(255,255,255,0.25);">
              <i class="fe fe-check-circle" style="color:#86efac; font-size:12px;"></i>
              <span style="opacity: 0.85;">Kepala Ops:</span>
              <span class="fw-bold" id="lbl_view_ops_by">-</span>
              <span style="opacity: 0.7; font-size: 10.5px;" id="lbl_view_ops_at">-</span>
            </div>
            <div class="d-flex align-items-center gap-1.5 text-white" id="view_ship_wrap"
              style="background: rgba(255,255,255,0.15); padding: 5px 12px; border-radius: 20px; font-size: 11.5px; border: 1px solid rgba(255,255,255,0.25);">
              <i class="fe fe-truck" style="color:#93c5fd; font-size:12px;"></i>
              <span style="opacity: 0.85;">Acc Kirim:</span>
              <span class="fw-bold" id="lbl_view_ship_by">-</span>
            </div>
          </div>

          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
            aria-label="Close"></button>
        </div>
        <div class="modal-body p-0 position-relative flex-grow-1"
          style="overflow-y: auto; min-height: 0; background:#ffffff;">
          <div id="view_stock_transfer_loading"
            style="display:none;position:absolute;inset:0;z-index:20;background:rgba(255,255,255,.92);flex-direction:column;align-items:center;justify-content:center;gap:12px;">
            <div class="spinner-border text-primary" style="width:2.5rem;height:2.5rem;" role="status"
              aria-hidden="true"></div>
            <div class="text-muted fw-semibold" style="font-size:13px;">Memuat detail transfer…</div>
          </div>
          <div class="border-bottom" style="background:#ffffff; padding: 14px 24px 10px 24px;">
            <div class="row g-3 align-items-start">
              {{-- Section Asal --}}
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
                          class="fe fe-box me-1 text-primary"></i> Gudang Asal</div>
                      <div class="fw-bold text-dark mt-0.5" id="lbl_view_from" style="font-size:12.5px;">-</div>
                    </div>
                    <div class="col-6">
                      <div class="text-muted"
                        style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:#475569;">
                        <i class="fe fe-user me-1 text-primary" id="icon_view_person"></i>
                        <span id="lbl_view_person_label">Pengirim</span>
                      </div>
                      <div class="fw-bold text-dark mt-0.5" id="lbl_view_sender" style="font-size:12.5px;">-</div>
                    </div>

                    <div class="col-6">
                      <div class="text-muted"
                        style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:#475569;"><i
                          class="fe fe-calendar me-1 text-primary"></i> Tanggal Pengiriman</div>
                      <div class="fw-bold text-dark mt-0.5" id="lbl_view_date" style="font-size:12.5px;">-</div>
                    </div>
                    {{-- Detail ST = #view_stock_transfer (bukan modal edit) --}}
                    <div class="col-6 d-none" id="view-ship-proof-slot">
                      <div class="text-muted"
                        style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:#475569;"><i
                          class="fe fe-camera me-1 text-primary"></i> Bukti Foto Kirim</div>
                      <button type="button" class="btn btn-sm w-100 btn-view-st-ship-proof mt-0.5 d-flex align-items-center justify-content-center gap-1.5 fw-semibold" id="view-ship-proof-link"
                        data-parent="#view_stock_transfer"
                        style="border-radius:6px;height:30px;font-size:12px;border:1px solid #bfdbfe;color:#1d4ed8;background:#eff6ff;transition:all 0.2s ease-in-out;">
                        <i class="fe fe-image" style="font-size:12px;"></i> <span>Lihat Foto</span>
                      </button>
                    </div>
                    <div class="col-12">
                      <div class="text-muted"
                        style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:#475569;"><i
                          class="fe fe-file-text me-1 text-primary"></i> Catatan Pengiriman</div>
                      <div class="fw-bold text-dark mt-0.5" id="lbl_view_ship_note" style="font-size:12.5px;word-break:break-word;">-</div>
                    </div>
                  </div>
                </div>
              </div>

              {{-- Arrow --}}
              <div class="col-md-2 d-flex align-items-center justify-content-center" style="padding-top: 40px;">
                <div style="display:flex;flex-direction:column;align-items:center;gap:3px;">
                  <div
                    style="width:34px;height:34px;background:linear-gradient(135deg,#3b82f6,#6366f1);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;box-shadow:0 3px 8px rgba(59,130,246,.25);">
                    <i class="fe fe-arrow-right text-white" style="font-size:14px;"></i>
                  </div>
                  <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-0.5"
                    style="font-size:9.5px;font-weight:700;letter-spacing:.4px;">TRANSFER</span>
                </div>
              </div>

              {{-- Section Tujuan --}}
              <div class="col-md-5">
                <div class="d-flex flex-column h-100">
                  <div class="d-flex align-items-center gap-2 mb-1.5">
                    <span class="badge d-inline-flex align-items-center gap-1 px-2 py-1 rounded-2"
                      style="background:#f0fdf4; color:#15803d; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; border:1px solid #bbf7d0;">
                      <i class="fe fe-log-in" style="font-size:11px;"></i>
                      <span>Ke (Tujuan)</span>
                    </span>
                  </div>
                  <div class="row g-2 mt-0">
                    <div class="col-6">
                      <div class="text-muted"
                        style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:#475569;"><i
                          class="fe fe-box me-1 text-success"></i> Gudang Tujuan</div>
                      <div class="fw-bold text-dark mt-0.5" id="lbl_view_to" style="font-size:12.5px;">-</div>
                    </div>
                    <div class="col-6">
                      <div class="text-muted"
                        style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:#475569;"><i
                          class="fe fe-user-check me-1 text-success"></i> Penerima</div>
                      <div class="fw-bold text-dark mt-0.5" id="lbl_view_receiver" style="font-size:12.5px;">-</div>
                    </div>
                    <div class="col-12">
                      <div class="text-muted"
                        style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:#475569;"><i
                          class="fe fe-file-text me-1 text-success"></i> Catatan Penerimaan</div>
                      <div class="fw-bold text-dark mt-0.5" id="lbl_view_accept_note" style="font-size:12.5px;word-break:break-word;">-</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>


          <div class="d-flex justify-content-between align-items-center border-bottom"
            style="background:#f8fafc; padding: 14px 28px;">
            <div class="d-flex align-items-center">
              <i class="fe fe-layers text-primary me-2" style="font-size:15px;"></i>
              <span class="fw-bold text-dark" style="font-size:12px; text-transform:uppercase; letter-spacing:.5px;">Produk yang di Transfer</span>
            </div>
            <div style="width: 420px; max-width: 100%; position:relative;">
              <i class="fe fe-search position-absolute"
                style="top:50%; transform:translateY(-50%); left:14px; color:#94a3b8; font-size:14px;"></i>
              <input type="text" class="form-control" id="search_view_barcode"
                placeholder="Ketik nama, SKU, atau scan barcode..."
                style="border-radius:20px; font-size:13px; padding-left:38px; padding-right:16px; height: 40px; border-color:#cbd5e1; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
            </div>
          </div>
          <div class="table-responsive" style="min-height: 240px; background:#fff;">
            <table class="table table-center table-hover mb-0" id="tableViewItems" style="font-size:13px; width:100%; table-layout:fixed;">
              <thead style="background:#ffffff; border-bottom: 2px solid #e2e8f0;">
                <tr>
                  <th style="width: 25%;">Produk</th>
                  <th style="width: 15%;">Varian</th>
                  <th style="width: 14%;">SKU</th>
                  <th style="width: 130px;" class="text-center">Kirim (Asli)</th>
                  {{-- Qty Terima & Selisih: tidak dipakai di view detail (retail = qty kirim) --}}
                  {{-- <th style="width: 130px;" class="text-center">Qty Terima</th> --}}
                  <th style="width: 140px;" class="text-center">Hasil Konversi</th>
                  {{-- <th style="width: 110px;" class="text-center">Selisih</th> --}}
                </tr>
              </thead>
              <tbody>
                <tr class="empty-row">
                  <td colspan="5" class="text-center text-muted py-5" style="font-size: 14px;">Belum ada produk.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        {{-- ── FOOTER: Tutup ── --}}
        <div class="modal-footer pg-modal-footer">
          <button type="button" data-bs-dismiss="modal" class="btn pg-btn-cancel">Tutup</button>
        </div>

      </div>
    </div>
  </div>
