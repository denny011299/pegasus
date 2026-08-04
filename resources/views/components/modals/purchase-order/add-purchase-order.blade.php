  <div class="modal modal-xl custom-modal fade" id="add_purchase_order" role="dialog" data-bs-backdrop="static"
    data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-xl">
      <div class="modal-content">
        <div class="modal-header border-0 pb-0">
          <div class="form-header modal-header-title text-start mb-0">
            <h4 class="mb-0 modal-title">Tambah Purchase Order</h4>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <form action="#">
          <div class="modal-body">
            <div class="form-groups-item border-0 pb-0">
              <div class="row">
                <div class="col-12 row">
                  <div class="col-lg-6 col-md-6 col-12">
                    <div class="input-block" id="row-pemasok">
                      <label>Nama Pemasok<span class="text-danger">*</span></label>
                      <select id="po_supplier" class="form-control fill"></select>
                    </div>
                  </div>
                  <div class="col-lg-6 col-md-6 col-12">
                    <div class="input-block mb-3">
                      <label>Tanggal<span class="text-danger">*</span></label>
                      <input type="date" class="form-control fill" id="po_date">
                    </div>
                  </div>
                </div>
                <div class="col-12 row">
                  <div class="col-lg-3 col-md-6 col-6">
                    <div class="input-block">
                      <label>Tipe Diskon</label>
                      <select class="form-select" id="jenis_disc">
                        <option value="persen">Persen</option>
                        <option value="nominal">Nominal</option>
                      </select>
                    </div>
                  </div>
                  <div class="col-lg-3 col-md-6 col-6">
                    <div class="input-block">
                      <label>Diskon</label>
                      <div class="input-group mb-3 discount">
                        <input type="text" class="form-control fill number-only" id="po_discount"
                          placeholder="Input Diskon" value="0">
                        <span class="input-group-text">%</span>
                      </div>
                    </div>
                  </div>
                  <div class="col-lg-3 col-md-6 col-6">
                    <div class="input-block">
                      <label>PPN</label>
                      <div class="input-group mb-3">
                        <input type="text" class="form-control fill number-only" id="po_ppn"
                          placeholder="Input PPN" value="0">
                        <span class="input-group-text">%</span>
                      </div>
                    </div>
                  </div>
                  <div class="col-lg-3 col-md-6 col-6">
                    <div class="input-block mb-3">
                      <label>Biaya Pengiriman</label>
                      <div class="input-group mb-3">
                        <span class="input-group-text">Rp </span>
                        <input type="text" class="form-control fill number-only nominal_only" id="po_cost"
                          value="0" placeholder="Masukkan Biaya Pengiriman">
                      </div>
                    </div>
                  </div>
                  <div class="col-lg-6 col-md-12 col-12">
                    <div class="input-block mb-3">
                      <label>Keterangan<span class="text-danger">*</span></label>
                      <input type="text" class="form-control fill" id="po_desc"
                        placeholder="Masukkan Keterangan">
                    </div>
                  </div>
                  <div class="col-lg-6 col-md-12 col-12">
                    <div class="input-block mb-3">
                      <label class="form-label d-flex">Foto Bukti<span class="text-danger">*</span>
                        <span id="check_foto" style="display: none" class="ms-2">
                          <div class="d-flex g-3">
                            <i class="fa fa-check-circle text-success mt-1"></i>
                            <p class="text-muted ms-1"><span id="jumlahFoto">1</span> gambar terunggah</p>
                          </div>
                        </span>
                      </label>
                      <button type="button" class="btn btn-outline-primary" id="btn-foto-bukti">Foto
                        Bukti</button>
                      <button type="button" class="btn btn-outline-primary" id="btn-lihat-bukti"
                        style="display: none">Lihat Bukti</button>
                      <input type="hidden" name="" id="bukti">
                    </div>
                  </div>
                </div>
                <div class="col-lg-6 col-md-12 col-12">
                  <div class="input-block mb-3">
                    <label class="d-flex align-items-center gap-2">
                      SKU/Barcode Produk<span class="text-danger">*</span>
                      <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2"
                        id="btn_toggle_scan" title="Ganti mode Scan">
                        <i class="fa fa-barcode"></i> Scan
                      </button>
                    </label>
                    <div id="po_mode_select">
                      <select class="form-select" id="po_sku">
                        <option value="" selected disabled>Pilih Supplier Terlebih Dahulu</option>
                      </select>
                    </div>
                    <div id="po_mode_scan" style="display:none">
                      <div class="input-group">
                        <input type="text" class="form-control" id="po_scan_barcode"
                          placeholder="Scan / ketik barcode...">
                        <input type="number" class="form-control" id="po_scan_qty" placeholder="Qty"
                          value="1" min="1" style="max-width:80px">
                        <button type="button" class="btn btn-primary" id="btn_scan_add"><i
                            class="fa fa-plus"></i></button>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-12 overflow-x-auto mb-3 table-po-wrap">
                  <table class="table table-center" id="tablePurchaseModal">
                    <thead>
                      <th style="width:16%">Produk</th>
                      <th style="width:20%">Variasi</th>
                      <th style="width:10%">SKU</th>
                      <th style="width:18%">Qty</th>
                      <th style="width:13%" class="text-end">Harga Beli</th>
                      <th style="width:14%" class="text-end">Subtotal</th>
                      <th style="width:9%" class="text-center">Action</th>
                    </thead>
                    <tbody></tbody>
                  </table>
                </div>
                <div class="col-12 row pt-3">
                  <div class="col-lg-6 col-md-6 col-12"></div>
                  <div class="col-lg-6 col-md-6 col-12">
                    <div class="d-flex justify-content-between">
                      <p>Total</p>
                      <p id="value_total">Rp. 0</p>
                    </div>
                    <div class="d-flex justify-content-between">
                      <p>Diskon</p>
                      <p id="value_discount">Rp.0</p>
                    </div>
                    <div class="d-flex justify-content-between">
                      <p>Ppn</p>
                      <p id="value_ppn">Rp. 0</p>
                    </div>
                    <div class="d-flex justify-content-between">
                      <p>Biaya Pengiriman</p>
                      <p id="value_cost">Rp. 0</p>
                    </div>
                    <div class="d-flex justify-content-between">
                      <b>Grand Total</b>
                      <b id="value_grand">Rp. 0</b>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" data-bs-dismiss="modal" class="btn btn-back cancel-btn me-2">Batal</button>
            <button type="button" class="btn btn-primary paid-continue-btn btn-save">Tambah Pembelian</button>
          </div>
        </form>
      </div>
    </div>
  </div>
