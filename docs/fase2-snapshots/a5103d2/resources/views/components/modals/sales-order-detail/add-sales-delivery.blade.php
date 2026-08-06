  <div class="modal modal-xl custom-modal fade" id="add_sales_delivery" role="dialog" data-bs-backdrop="static"
    data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-xl">
      <div class="modal-content">
        <div class="modal-header border-0 pb-0">
          <div class="form-header modal-header-title  text-start mb-0">
            <h4 class="mb-0 modal-title">Tambah Catatan Pengiriman</h4>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
          </button>
        </div>
        <form action="#">
          <div class="modal-body">
            <div class="form-groups-item border-0 pb-0">
              <div class="row">
                <div class="col-12 row">
                  <div class="col-4">
                    <div class="input-block">
                      <label>Nama Penerima<span class="text-danger">*</span></label>
                      <input type="text" class="form-control fill" id="sdo_receiver"
                        placeholder="Nama Penerima">
                    </div>
                  </div>
                  <div class="col-4">
                    <div class="input-block mb-3">
                      <label>Tanggal<span class="text-danger">*</span></label>
                      <input type="date" class="form-control fill" id="sdo_date">
                    </div>
                  </div>
                  <div class="col-4">
                    <div class="input-block mb-3">
                      <label>Nomor Telepon<span class="text-danger">*</span></label>
                      <input type="text" class="form-control fill number-only" id="sdo_phone"
                        placeholder="Nomor Telepon">
                    </div>
                  </div>
                </div>
                <div class="col-12">
                  <div class="input-block mb-3">
                    <label>Keterangan</label>
                    <textarea class="form-control " id="sdo_desc" cols="30" rows="5"
                      placeholder="Keterangan pengiriman"></textarea>
                  </div>
                </div>
                <div class="col-12 mb-3 delivery-product-picker">
                  <label class="form-label">Tambah Produk (bebas / tidak wajib dari SO)</label>
                  <div class="row g-2 align-items-end">
                    <div class="col-md-5">
                      <select class="form-select" id="sdo_product_sku"></select>
                    </div>
                    <div class="col-md-2">
                      <input type="number" min="1" class="form-control" id="sdo_product_qty"
                        placeholder="Qty" value="1">
                    </div>
                    <div class="col-md-3">
                      <select class="form-select" id="sdo_product_unit"></select>
                    </div>
                    <div class="col-md-2">
                      <button type="button" class="btn btn-primary w-100" id="btn_add_sdo_product">
                        <i class="fe fe-plus"></i> Tambah
                      </button>
                    </div>
                  </div>
                  <small class="text-muted">Catatan pengiriman bersifat rencana — stok tidak dipotong di sini.</small>
                </div>
                <div class="col-12">
                  <div class="table-responsive">
                    <table class="table table-center" id="tableSalesDelivery">
                      <thead>
                        <th>Produk</th>
                        <th>Varian</th>
                        <th>SKU</th>
                        <th class="text-center">Qty</th>
                        <th class="text-center">Satuan</th>
                        <th class="text-center">Aksi</th>
                      </thead>
                      <tbody></tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <div class="row-acc">
              <button class="btn btn-danger btn-decline me-2" type="button">Tolak</button>
              <button class="btn btn-success btn-approve me-3" type="button">Setujui</button>
            </div>
            <button type="button" data-bs-dismiss="modal" class="btn btn-back cancel-btn me-2">Batal</button>
            <button type="button" class="btn btn-primary paid-continue-btn btn-save-delivery">Tambah Catatan
              Pengiriman</button>
          </div>
        </form>
      </div>
    </div>
  </div>
