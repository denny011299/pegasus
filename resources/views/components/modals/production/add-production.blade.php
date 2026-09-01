<style>
  #addProduction.modal {
    overflow: hidden !important;
  }
  html:has(#addProduction.show),
  body:has(#addProduction.show) {
    overflow: hidden !important;
  }
  #addProduction .modal-dialog {
    height: auto !important;
    max-height: calc(100dvh - 2rem) !important;
    margin: 1rem auto !important;
    display: flex !important;
    align-items: center !important;
  }
  #addProduction .modal-content {
    height: auto !important;
    max-height: 100% !important;
    min-height: 0 !important;
    display: flex !important;
    flex-direction: column !important;
    overflow: hidden !important;
  }
  #addProduction form {
    flex: 1 1 auto !important;
    min-height: 0 !important;
    height: auto !important;
    max-height: none !important;
    display: flex !important;
    flex-direction: column !important;
    overflow: hidden !important;
  }
  #addProduction .modal-header,
  #addProduction .modal-footer {
    flex: 0 0 auto !important;
  }
  #addProduction .modal-body {
    flex: 1 1 auto !important;
    min-height: 0 !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
  }
</style>
<div class="modal custom-modal fade pg-modal--form" id="addProduction" aria-modal="true" role="dialog" tabindex="-1"
  data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
    <div class="modal-content d-flex flex-column">
      <div class="modal-header">
        <div class="d-flex align-items-center gap-3">
          <div class="pg-modal-icon">
            <i class="fe fe-layers"></i>
          </div>
          <div>
            <h5 class="mb-0 fw-bold text-white modal-title d-inline-block" style="font-size:16px;letter-spacing:.2px;">Tambah
              Produksi</h5>
            <span id="production_status_badge_header" class="ms-2" style="display:none;"></span>
            <small class="d-block text-white-50">Kelola hasil produksi dan daftar produk</small>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="#" class="d-flex flex-column flex-grow-1" style="margin: 0; min-height: 0;">
        <div class="modal-body p-0 bg-light d-flex flex-column flex-grow-1">

          <div class="p-4 border-bottom bg-white shadow-sm" style="flex: 0 0 auto;">
            <div class="row g-4">
              <div class="col-lg-6 col-12">
                <label class="text-muted mb-2"
                  style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Tanggal <span
                    class="text-danger">*</span></label>
                <input type="date" class="form-control fill" id="production_date"
                  style="font-size:14px;border-radius:8px;height:42px;">
              </div>
              <div class="col-lg-6 col-12">
                <label class="text-muted mb-2"
                  style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Keterangan</label>
                <input type="text" class="form-control" id="production_desc"
                  placeholder="Masukkan Keterangan" style="font-size:14px;border-radius:8px;height:42px;">
              </div>
              <div class="col-12" id="row-production-detail-info" style="display:none;">
                <div class="row g-4">
                  <div class="col-lg-6 col-12">
                    <label class="text-muted mb-2"
                      style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Kode
                      Produksi</label>
                    <input type="text" class="form-control" id="production_code_display" disabled
                      style="font-size:14px;border-radius:8px;height:42px;">
                  </div>
                  <div class="col-lg-6 col-12">
                    <label class="text-muted mb-2"
                      style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Dibuat
                      Oleh</label>
                    <input type="text" class="form-control" id="production_created_by_display" disabled
                      style="font-size:14px;border-radius:8px;height:42px;">
                  </div>
                </div>
              </div>
              <div class="col-lg-6 col-12" id="row-production-acc-by" style="display:none;">
                <label class="text-muted mb-2"
                  style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Diapprove
                  Oleh</label>
                <input type="text" class="form-control" id="production_acc_by_name" disabled
                  style="font-size:14px;border-radius:8px;height:42px;">
              </div>
              <div class="col-12" id="row-production-cancel-info" style="display:none;">
                <div class="row g-4">
                  <div class="col-lg-6 col-12">
                    <label class="text-muted mb-2"
                      style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Pengajuan
                      Batal Oleh</label>
                    <input type="text" class="form-control" id="production_cancel_requested_by_display" disabled
                      style="font-size:14px;border-radius:8px;height:42px;">
                  </div>
                  <div class="col-lg-6 col-12">
                    <label class="text-muted mb-2"
                      style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Notes
                      Pembatalan</label>
                    <input type="text" class="form-control" id="production_cancel_notes_display" disabled
                      style="font-size:14px;border-radius:8px;height:42px;">
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="p-4" style="flex: 1 1 auto; background: #f8fafc;">
            <div class="d-flex align-items-center gap-2 mb-3">
              <i class="fe fe-list text-primary"></i>
              <span class="fw-bold text-dark" style="font-size:14px;">Daftar Produk</span>
            </div>

            <div class="table-responsive rounded border mb-3 bg-white">
              <table class="table table-center custom-table-scroll mb-0" id="tableProduct"
                style="min-width: 800px;">
                <thead style="background: #f1f5f9;">
                  <tr>
                    <th
                      style="width: 35%; padding: 12px 16px; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase;">
                      Nama Produk</th>
                    <th class="text-center"
                      style="width: 15%; padding: 12px 16px; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase;">
                      Qty</th>
                    <th
                      style="width: 20%; padding: 12px 16px; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase;">
                      Satuan</th>
                    <th
                      style="width: 20%; padding: 12px 16px; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase;">
                      Gudang Tujuan</th>
                    <th class="no-sort text-center"
                      style="width: 15%; padding: 12px 16px; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase;">
                      Aksi</th>
                  </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot class="dos" style="background: #f8fafc; border-top: 2px solid #e2e8f0;">
                  <tr>
                    <td class="fw-bold text-end" style="color: #334155; padding: 12px 16px;">Total Dos:</td>
                    <td class="fw-bold text-center" style="color: #334155; padding: 12px 16px;"><span
                        id="total_dos"
                        style="background: #e0f2fe; color: #0284c7; padding: 4px 12px; border-radius: 6px;">0</span>
                    </td>
                    <td class="fw-bold" style="color: #334155; padding: 12px 16px;">Dos</td>
                    <td></td>
                    <td></td>
                  </tr>
                </tfoot>
              </table>
            </div>
            <div class="row input_table g-3 align-items-end p-3 rounded bg-white"
              style="border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
              <div class="col-12 col-lg-3 add">
                <div class="input-block mb-0" id="row-product">
                  <label class="text-muted mb-2"
                    style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Nama Produk
                    <span class="text-danger">*</span></label>
                  <select class="form-select fill_product" id="product_id"
                    style="font-size:14px;border-radius:8px;height:42px;"></select>
                </div>
              </div>
              <div class="col-6 col-lg-2 add">
                <div class="input-block mb-0" style="position: relative;">
                  <label class="text-muted mb-2"
                    style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Qty <span
                      class="text-danger">*</span></label>
                  <input type="text" class="form-control fill_product number-only" id="production_qty"
                    placeholder="Qty" value="1" style="font-size:14px;border-radius:8px;height:42px;">
                  <small class="text-muted position-absolute" id="production_pallet_hint"
                    style="bottom: -18px; left: 2px; font-size: 10px; white-space: nowrap;"></small>
                </div>
              </div>
              <div class="col-6 col-lg-2 add">
                <div class="input-block mb-0">
                  <label class="text-muted mb-2"
                    style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Satuan
                    <span class="text-danger">*</span></label>
                  <select class="form-select fill_product" id="unit_id"
                    style="font-size:14px;border-radius:8px;height:42px;"></select>
                </div>
              </div>
              <div class="col-12 col-lg-4 add">
                <div class="input-block mb-0">
                  <label class="text-muted mb-2"
                    style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Gudang
                    Tujuan <span class="text-danger">*</span></label>
                  @php
                    $prodDestWh = $activeWarehouse ?? null;
                    $prodDestWhName = $prodDestWh
                      ? ($prodDestWh->warehouse_name ?? $prodDestWh->name ?? '')
                      : '';
                    $isActiveMainWh = $prodDestWh
                      && isset($prodDestWh->type)
                      && (int) ($prodDestWh->type->is_main_warehouse ?? 0) === 1;
                    $prodMainWhName = $prodDestWhName;
                    if (! $isActiveMainWh && isset($warehouses)) {
                        $prodMainWh = collect($warehouses)->first(function ($wh) {
                            return isset($wh->type) && (int) $wh->type->is_main_warehouse === 1;
                        });
                        if ($prodMainWh) {
                            $prodMainWhName = $prodMainWh->warehouse_name ?? $prodMainWh->name ?? $prodMainWhName;
                        }
                    }
                  @endphp
                  <div id="production-main-warehouse-badge" class="d-flex align-items-center px-3"
                    style="height:42px;border-radius:8px;background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8;font-size:13px;font-weight:600;">
                    <i class="fe fe-home me-2"></i><span>{{ $prodMainWhName !== '' ? $prodMainWhName : 'Gudang utama' }}</span>
                  </div>
                  <select class="form-select" id="production_destination_warehouse_id"
                    style="display:none;font-size:14px;border-radius:8px;height:42px;"></select>
                </div>
              </div>
              <div class="col-12 col-md-12 col-lg-1 add text-end">
                <button type="button"
                  class="btn btn-primary w-100 btn-add-product d-flex align-items-center justify-content-center"
                  style="background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;border:none;border-radius:8px;height:42px;box-shadow:0 4px 12px rgba(59,130,246,.3);">
                  <i class="fe fe-plus"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer pg-modal-footer">
          <button type="button" data-bs-dismiss="modal" class="btn pg-btn-cancel btn-cancel">Batal</button>
          @roleCan('Produksi', 'others')
            <button type="button" id="btn-tolak" class="btn pg-btn-decline d-none btn_decline"><i class="fe fe-x me-1"></i>Tolak</button>
            <button type="button" id="btn-terima" class="btn pg-btn-accept d-none btn_acc"><i class="fe fe-check-circle me-1"></i>Terima Produksi</button>
          @endroleCan
          <button type="button" class="btn pg-btn-save btn-save" id="btn-tambah-pesanan">
            <i class="fe fe-save"></i> <span class="btn-save-label">Simpan</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
