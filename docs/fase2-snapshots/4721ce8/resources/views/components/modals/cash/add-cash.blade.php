  <div class="modal modal-lg custom-modal fade" id="add_cash" role="dialog" data-bs-backdrop="static"
    data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-md">
      <div class="modal-content">
        <div class="modal-header border-0 pb-0">
          <div class="form-header modal-header-title  text-start mb-0">
            <h4 class="mb-0 modal-title">Tambah Pencatatan Kas</h4>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup">
          </button>
        </div>
        <form action="#">
          <div class="modal-body">
            <div class="form-groups-item border-0 pb-0">
              <div class="row">
                <div class="col-12">
                  <div class="input-block mb-3">
                    <label>Tanggal Pencatatan<span class="text-danger">*</span></label>
                    <input type="date" class="form-control fill" id="cash_date">
                  </div>
                </div>
                <div class="col-12">
                  <div class="input-block mb-3">
                    <label>Keterangan<span class="text-danger">*</span></label>
                    <input type="text" class="form-control fill" id="cash_description"
                      placeholder="Masukkan Keterangan">
                  </div>
                </div>
                <div class="row input-block mb-3 pe-0">
                  <div class="col-md-4 col-12 pe-0">
                    <label>Tipe<span class="text-danger">*</span></label>
                    <select class="form-select" id="cash_select">
                      <option value="debit" checked>Masuk</option>
                      <option value="credit1">Keluar</option>
                      <option value="credit2">Keluar 1</option>
                    </select>
                  </div>
                  <div class="col-md-8 col-12 mt-md-0 mt-3 pe-0">
                    <label>Jumlah Nominal<span class="text-danger">*</span></label>
                    <div class="input-group fix-nominal">
                      <span class="input-group-text">Rp.</span>
                      <input type="text" name="" id="cash_nominal"
                        class="form-control fill number-only nominal_only" placeholder="Contoh 10000">
                    </div>
                  </div>
                </div>
                {{-- <div class="col-12" id="tujuan">
                                    <div class="input-block mb-3">
                                        <label>Tujuan Keluar<span class="text-danger">*</span></label>
                                        <select class="form-select fill" id="cash_tujuan">
                                            <option value="" disabled selected>Pilih Tujuan</option>
                                            <option value="admin">Kas Admin</option>
                                            <option value="gudang">Kas Gudang</option>
                                        </select>
                                    </div>
                                </div> --}}
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" data-bs-dismiss="modal" class="btn btn-back cancel-btn me-2">Batal</button>
            <button type="button" class="btn btn-primary paid-continue-btn btn-save">Tambah Pencatatan</button>
          </div>
        </form>
      </div>
    </div>
  </div>
