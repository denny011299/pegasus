  <div class="modal fade" id="add-product-issues" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content p-3">
        <div class="modal-header border-0 pb-0">
          <div class="form-header modal-header-title  text-start mb-0">
            <h4 class="mb-0 modal-title">Tambah Produk Bermasalah</h4>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
          </button>
        </div>
        <form action="#">
          <div class="modal-body">
            <div class="form-groups-item border-0 pb-0">
              <div class="row">
                {{-- <div class="col-lg-6">
                                    <div class="mb-3">
                                        <label class="form-label">Produk</label>
                                        <select class="form-select  select2 fill select2Input" id="product_id">
                                        </select>
                                    </div>
                                </div> --}}
                <div class="col-lg-6">
                  <div class="input-block mb-3">
                    <label>Tanggal<span class="text-danger">*</span></label>

                    <div class="input-groupicon calender-input">
                      <input type="text" class="datetimepicker form-control fill" id="pi_date"
                        placeholder="Pilih Tanggal">
                    </div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="input-block mb-3">
                    <label class="form-label">Jenis Retur<span class="text-danger">*</span></label>
                    <select class="form-select" id="tipe_return">
                      <option value="1" selected>Retur ke Supplier / Rusak Gudang</option>
                      <option value="2">Pengembalian Armada</option>
                    </select>
                  </div>
                </div>
                <div class="col-lg-3">
                  <div class="input-block mb-3">
                    <label class="form-label">Tipe<span class="text-danger">*</span></label>
                    <select class="form-select" id="pi_type">

                    </select>
                  </div>
                </div>
                <div class="col-lg-4">
                  <div class="input-block mb-3">
                    <label class="form-label d-flex">
                      Bukti Foto<span class="text-danger">*</span>
                      <span id="check_foto" style="display: none" class="ms-2">
                        <div class="d-flex g-3">
                          <i class="fa fa-check-circle text-success mt-1"></i>
                          <p class="text-muted ms-1">gambar terunggah</p>
                        </div>
                      </span>
                    </label>
                    <button type="button" class="btn btn-outline-primary" id="btn-foto-bukti">Foto Bukti</button>
                    <button type="button" class="btn btn-outline-primary" id="btn-lihat-bukti"
                      style="display: none">Lihat Bukti</button>
                    <input type="hidden" name="" id="bukti">
                  </div>
                </div>
                {{-- <div class="col-lg-6">
                                    <div class="input-block mb-3">
                                        <label class="form-label">Jumlah</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control number-only fill" id="pi_qty">
                                            <select class="form-select w-25 fill" id="unit_id">
                                            </select>
                                        </div>
                                    </div>
                                </div> --}}
                <div class="col-lg-5">
                  <div class="input-block mb-3">
                    <label class="form-label">Catatan<span class="text-danger">*</span></label>
                    <input type="text" class="form-control fill" id="pi_notes"
                      placeholder="Tambahkan Catatan">
                  </div>
                </div>
                {{-- <div class="col-lg-6">
                                    <div class="input-block mb-3 ref">
                                        <label class="form-label">Ref. PO<span class="text-danger">*</span></label>
                                        <select class="form-select fill" id="ref_num"></select>
                                    </div>
                                </div> --}}
                <div class="col-12 py-3 mb-3">
                  <div class="table-responsive">
                    <table class="table table-center" id="tableProduct" style="min-height: 15vh">
                      <thead>
                        <th id="header_name">Nama Produk</th>
                        <th>Qty</th>
                        <th>Satuan</th>
                        <th class="no-sort text-center">Aksi</th>
                      </thead>
                      <tbody></tbody>
                    </table>
                  </div>
                </div>
                <div class="col-12 px-2 mb-3">
                  <div class="row input_table g-3 align-items-end">

                  </div>
                </div>
              </div>


              <div class="modal-footer p-0">
                @if (in_array('others', $akses->firstWhere('name', 'Produk Bermasalah')->akses))
                  <button type="button" id="btn-tolak" class="btn btn-danger me-2 btn_decline"
                    style="display: none">Tolak</button>
                  <button type="button" id="btn-terima" class="btn btn-success me-2 btn_acc"
                    style="display: none">Terima</button>
                @endif
                <button type="button" data-bs-dismiss="modal" class="btn btn-back cancel-btn me-2">Batal</button>
                <button type="button" class="btn btn-primary paid-continue-btn btn-save">Tambah Produk
                </button>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
