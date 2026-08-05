  <div class="modal fade custom-modal" id="modal_safety_stock" data-bs-backdrop="static"
    data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content" style="border-radius: 16px; overflow: hidden; border: none;">
        <div class="modal-header border-0"
          style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); padding: 18px 24px;">
          <div class="d-flex align-items-center gap-3">
            <div
              style="width:40px;height:40px;background:rgba(255,255,255,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;">
              <i class="fe fe-shield text-white" style="font-size:18px;"></i>
            </div>
            <div>
              <h5 class="mb-0 text-white fw-bold modal-title">Safety Stock</h5>
              <p class="text-white-50 mb-0 mt-1" id="safety_modal_subtitle" style="font-size:13px;"></p>
            </div>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
            aria-label="Close"></button>
        </div>
        <div class="modal-body p-0 d-flex flex-column"
          style="background:#f8fafc; flex: 1 1 auto; overflow: hidden;">

          {{-- Section 1: Edit Safety Stock --}}
          <div class="d-flex align-items-center px-4 py-3 border-bottom" style="background:#fff;">
            <i class="fe fe-edit-2 text-primary me-2" style="font-size: 16px;"></i>
            <h6 class="fw-bold mb-0 text-dark" style="font-size: 14px;">Edit Safety Stock</h6>
          </div>
          <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
            <table class="table table-hover table-center mb-0" id="table_safety_edit" style="font-size: 13px;">
              <thead style="background:#f1f5f9; position: sticky; top: 0; z-index: 10;">
                <tr>
                  <th
                    style="color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing:.4px; padding: 10px 24px;">
                    Satuan</th>
                  <th
                    style="width:40%; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing:.4px; padding: 10px 24px;">
                    Safety Stock</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
          <div class="text-end px-4 py-3 border-bottom" style="background:#fff;">
            <button type="button"
              class="btn btn-save d-inline-flex align-items-center justify-content-center gap-2"
              id="btn_save_safety"
              style="background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;border:none;border-radius:8px;padding:9px 28px;font-size:13px;font-weight:600;min-width:160px;height:42px;box-shadow:0 4px 12px rgba(59,130,246,.3);">
              <i class="fe fe-save me-1"></i> Simpan Safety Stock
            </button>
          </div>

          {{-- Section 2: Transfer ke Stok Produk --}}
          <div class="d-flex flex-column px-4 py-3 border-bottom" style="background:#f8fafc;">
            <div class="d-flex align-items-center mb-1">
              <i class="fe fe-repeat text-success me-2" style="font-size: 16px;"></i>
              <h6 class="fw-bold mb-0 text-dark" style="font-size: 14px;">Transfer ke Stok Produk</h6>
            </div>
            <p class="text-muted mb-0 ms-4" style="font-size:13px;">
              Kurangi safety stock dan tambahkan ke stok produk (per satuan).
            </p>
          </div>
          <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
            <table class="table table-hover table-center mb-0" id="table_safety_transfer"
              style="font-size: 13px;">
              <thead style="background:#f1f5f9; position: sticky; top: 0; z-index: 10;">
                <tr>
                  <th
                    style="color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing:.4px; padding: 10px 24px;">
                    Satuan</th>
                  <th class="text-center"
                    style="color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing:.4px; padding: 10px 24px;">
                    Safety</th>
                  <th
                    style="width:35%; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing:.4px; padding: 10px 24px;">
                    Qty Transfer</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
          <div class="text-end px-4 py-3" style="background:#fff;">
            <button type="button"
              class="btn btn-success d-inline-flex align-items-center justify-content-center gap-2"
              id="btn_transfer_safety"
              style="background:linear-gradient(135deg,#10b981,#059669);color:#fff;border:none;border-radius:8px;padding:9px 28px;font-size:13px;font-weight:600;min-width:140px;height:42px;box-shadow:0 4px 12px rgba(16,185,129,.3);">
              <i class="fe fe-repeat me-1"></i> Transfer
            </button>
          </div>
        </div>
        <div class="modal-footer border-top pt-3 pb-3 px-4" style="background:#f8fafc;">
          <button type="button" class="btn btn-back cancel-btn" data-bs-dismiss="modal"
            style="border-radius:8px; font-size:13px; font-weight:600;">Tutup</button>
        </div>
      </div>
    </div>
  </div>
