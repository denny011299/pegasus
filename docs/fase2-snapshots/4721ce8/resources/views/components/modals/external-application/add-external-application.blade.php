  <div class="modal modal-lg custom-modal fade" id="add_external_application" role="dialog"
    data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-md">
      <div class="modal-content">
        <div class="modal-header border-0 pb-0">
          <div class="form-header modal-header-title text-start mb-0">
            <h4 class="mb-0 modal-title">Tambah Aplikasi</h4>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
            aria-label="Close"></button>
        </div>
        <form action="#">
          <div class="modal-body">
            <div class="form-groups-item border-0 pb-0">
              <div class="row">
                <div class="col-md-6">
                  <div class="input-block mb-3">
                    <label>Nama Aplikasi<span class="text-danger">*</span></label>
                    <input type="text" class="form-control fill" id="application_name"
                      placeholder="Contoh: ERP Gudang">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="input-block mb-3">
                    <label>Kode Unik</label>
                    <input type="text" class="form-control" id="application_code"
                      placeholder="Dibuat otomatis bila dikosongkan">
                    <small class="text-muted">Tidak bisa diubah setelah aplikasi dibuat.</small>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="input-block mb-3">
                    <label>Perusahaan</label>
                    <input type="text" class="form-control" id="company" placeholder="Nama perusahaan">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="input-block mb-3">
                    <label>Status</label>
                    <select class="form-select" id="application_status">
                      <option value="active">Aktif</option>
                      <option value="disabled">Nonaktif</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="input-block mb-3">
                    <label>Nama Kontak</label>
                    <input type="text" class="form-control" id="contact_name" placeholder="Penanggung jawab">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="input-block mb-3">
                    <label>Email Kontak</label>
                    <input type="email" class="form-control" id="contact_email"
                      placeholder="nama@perusahaan.com">
                  </div>
                </div>
                <div class="col-12">
                  <div class="input-block mb-3">
                    <label>Keterangan</label>
                    <textarea class="form-control" id="description" rows="2" placeholder="Kegunaan integrasi ini"></textarea>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" data-bs-dismiss="modal" class="btn btn-back cancel-btn me-2">Batal</button>
            <button type="button" class="btn btn-primary paid-continue-btn btn-save">Tambah Aplikasi</button>
          </div>
        </form>
      </div>
    </div>
  </div>
