  <div class="modal fade" id="add_stock_supplies" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
      <div class="modal-content" style="border-radius: 16px; overflow: hidden; border: none;">
        <div class="modal-header border-0"
          style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); padding: 18px 24px;">
          <div class="d-flex align-items-center gap-3">
            <div
              style="width:40px;height:40px;background:rgba(255,255,255,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;">
              <i class="fe fe-clock text-white" style="font-size:18px;"></i>
            </div>
            <div>
              <h5 class="mb-0 text-white fw-bold modal-title">Riwayat Stok Bahan Mentah</h5>
              <small class="text-white-50 mb-0 mt-1" style="font-size:13px;">Histori per gudang aktif</small>
            </div>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
            aria-label="Close"></button>
        </div>
        <form action="#">
          <div class="modal-body p-0 d-flex flex-column"
            style="flex: 1 1 auto; overflow: hidden; background:#f8fafc;">
            <div class="border-0 pb-0 mb-0 w-100">
              <div class="px-4 py-3 border-bottom" style="background-color: #fff;">
                <div class="d-flex align-items-end flex-wrap gap-3">
                  <div style="flex: 1; min-width: 200px;">
                    <label class="form-label text-muted fw-semibold"
                      style="font-size: 11px; text-transform:uppercase; letter-spacing:.4px;">Periode Dari</label>
                    <input type="date" class="form-control form-control-sm" id="start_date"
                      style="border-radius: 8px;">
                  </div>
                  <div style="flex: 1; min-width: 200px;">
                    <label class="form-label text-muted fw-semibold"
                      style="font-size: 11px; text-transform:uppercase; letter-spacing:.4px;">Sampai Dengan</label>
                    <input type="date" class="form-control form-control-sm" id="end_date"
                      style="border-radius: 8px;">
                  </div>
                  <div>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-clear px-4"
                      style="font-weight: 500; min-height: 33px; border-radius: 8px;">
                      <i class="fa fa-refresh me-1"></i> Reset
                    </button>
                  </div>
                </div>
              </div>

              <div class="table-responsive flex-grow-1 position-relative" id="tableLogScroll"
                style="min-height:300px; max-height: 50vh; overflow-y:auto; background:#fff;">
                <table class="table table-center table-hover mb-0" id="tableLog" style="font-size:13px;">
                  <thead style="background:#f1f5f9; position: sticky; top: 0; z-index: 10;">
                    <tr>
                      <th
                        style="width:12%; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing:.4px; padding: 10px 24px;">
                        Tanggal</th>
                      <th
                        style="width:12%; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing:.4px; padding: 10px 24px;">
                        Staff</th>
                      <th
                        style="width:12%; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing:.4px; padding: 10px 24px;">
                        No. Transaksi</th>
                      <th
                        style="width:22%; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing:.4px; padding: 10px 24px;">
                        Catatan</th>
                      <th
                        style="width:12%; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing:.4px; padding: 10px 24px;"
                        class="text-center">Masuk</th>
                      <th
                        style="width:12%; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing:.4px; padding: 10px 24px;"
                        class="text-center">Keluar</th>
                      <th
                        style="width:12%; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing:.4px; padding: 10px 24px;"
                        class="text-center">Saldo</th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>
            </div>
          </div>
          <div class="modal-footer border-top d-flex justify-content-end align-items-center"
            style="background:#f8fafc; padding: 16px 24px; min-height: 70px;">
            <button type="button" data-bs-dismiss="modal"
              class="btn btn-save d-inline-flex align-items-center justify-content-center gap-2"
              style="background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;border:none;border-radius:8px;padding:9px 28px;font-size:13px;font-weight:600;min-width:140px;height:42px;box-shadow:0 4px 12px rgba(59,130,246,.3); margin-bottom: 0;">
              Selesai
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
