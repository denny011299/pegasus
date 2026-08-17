  <div class="modal fade custom-modal pg-modal--form" id="add_stock_product" data-bs-backdrop="static"
    data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width: 90vw;">
      <div class="modal-content d-flex flex-column" style="border-radius: 16px; overflow: hidden; border: none; max-height: 92vh;">

        {{-- ── HEADER ── --}}
        <div class="modal-header">
          <div class="d-flex align-items-center gap-3">
            <div class="pg-modal-icon">
              <i class="fe fe-clock"></i>
            </div>
            <div>
              <h5 class="mb-0 fw-bold modal-title">Riwayat Stok Produk</h5>
              <small class="text-muted modal-subtitle">Detail mutasi dan penyesuaian stok produk</small>
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
                        class="text-center">Sisa</th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>
            </div>
          </div>
        </form>
        {{-- ── FOOTER ── --}}
        <div class="modal-footer pg-modal-footer">
          <button type="button" data-bs-dismiss="modal" class="btn pg-btn-cancel">
            Tutup
          </button>
        </div>
      </div>
    </div>
  </div>
