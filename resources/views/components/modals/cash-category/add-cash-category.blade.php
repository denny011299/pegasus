  <div class="modal modal-lg custom-modal fade" id="add_cash_category" role="dialog" data-bs-backdrop="static"
    data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-md">
      <div class="modal-content">
        <div class="modal-header border-0 pb-0">
          <div class="form-header modal-header-title  text-start mb-0">
            <h4 class="mb-0 modal-title">Tambah Kategori Kas</h4>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
          </button>
        </div>
        <form action="#">
          <div class="modal-body">
            <div class="form-groups-item border-0 pb-0">
              <div class="row">
                <div class="col-lg-6 col-12">
                  <div class="input-block mb-3">
                    <label>Nama Kategori<span class="text-danger">*</span></label>
                    <input type="text" class="form-control fill" id="cc_name" placeholder="ex Makan Siang">
                  </div>
                </div>
                <div class="col-lg-6 col-12">
                  <div class="input-block mb-3">
                    <label>Tipe Kategori<span class="text-danger">*</span></label>
                    <select class="form-select fill" id="cc_type">
                      <option value="" selected disabled>Pilih Tipe Kategori</option>
                      <option value="Keluar">Keluar</option>
                      <option value="Keluar 1">Keluar 1</option>
                      <option value="Masuk">Masuk / Setoran Tunai</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" data-bs-dismiss="modal" class="btn btn-back cancel-btn me-2">Batal</button>
            <button type="button" class="btn btn-primary paid-continue-btn btn-save">Tambah Kategori Kas</button>
          </div>
        </form>
      </div>
    </div>
  </div>
