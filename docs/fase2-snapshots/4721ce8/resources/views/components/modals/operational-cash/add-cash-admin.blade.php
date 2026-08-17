  <div class="modal modal-lg custom-modal fade" id="add_cash_admin" role="dialog" data-bs-backdrop="static"
    data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-md">
      <div class="modal-content">
        <div class="modal-header border-0 pb-0">
          <div class="form-header modal-header-title  text-start mb-0">
            <h4 class="mb-0 modal-title">Tambah Aktivitas Admin</h4>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup">
          </button>
        </div>
        <form action="#">
          <div class="modal-body">
            <div class="form-groups-item border-0 pb-0">
              <div class="row">
                <div class="col-12 col-md-6 mb-3">
                  <div class="input-block">
                    <label class="fw-bold">Jenis Aktivitas</label>
                    <select class="form-select" id="jenis_input">
                      <option value="saldo">Manajemen Saldo Kas</option>
                      <option value="operasional" selected>Aktivitas Operasional</option>
                    </select>
                  </div>
                </div>

                <div class="col-md-6 d-none d-md-block mb-3"></div>

                <div class="col-12">
                  <div class="row g-2" id="inputModal">

                    <div class="col-12 col-md-6 saldo_kas">
                      <div class="input-block mb-3">
                        <label>Aksi Dana<span class="text-danger">*</span></label>
                        <select class="form-select fill" id="oc_transaksi">
                          <option value="1">Pengajuan</option>
                          <option value="2">Pengembalian</option>
                        </select>
                      </div>
                    </div>

                    <div class="col-12 col-md-6 operasional">
                      <div class="input-block mb-3">
                        <label>Tanggal<span class="text-danger">*</span></label>
                        <input type="date" class="form-control fill" id="oc_date">
                      </div>
                    </div>

                    <div class="col-12 col-md-6">
                      <div class="input-block mb-3" id="row-cash">
                        <label>Nama Staff<span class="text-danger">*</span></label>
                        <select class="form-select fill" id="staff_id"></select>
                      </div>
                    </div>

                    <div class="col-12 col-md-6 saldo_kas">
                      <div class="input-block mb-3">
                        <label>Nominal<span class="text-danger">*</span></label>
                        <div class="input-group fix-nominal">
                          <span class="input-group-text">Rp </span>
                          <input class="form-control fill number-only nominal_only saldos" id="oc_nominal"
                            placeholder="Contoh: 10.000">
                        </div>
                      </div>
                    </div>

                    <div class="col-12 col-md-6 operasional">
                      <div class="input-block mb-3">
                        <label class="form-label d-flex align-items-center">
                          Bukti Foto<span class="text-danger">*</span>
                          <span id="check_foto" style="display: none" class="ms-2">
                            <small class="text-success"><i class="fa fa-check-circle"></i> terunggah</small>
                          </span>
                        </label>
                        <div class="d-grid d-md-block gap-2">
                          <button type="button" class="btn btn-outline-primary" id="btn-foto-bukti">Foto
                            Bukti</button>
                          <button type="button" class="btn btn-outline-primary" id="btn-lihat-bukti"
                            style="display: none">Lihat Bukti</button>
                        </div>
                        <input type="hidden" id="bukti">
                      </div>
                    </div>

                    <div class="col-12 col-md-6 saldo_kas">
                      <div class="input-block mb-3">
                        <label>Keterangan<span class="text-danger">*</span></label>
                        <input type="text" class="form-control fill saldos" id="oc_notes"
                          placeholder="Contoh: Untuk kas harian">
                      </div>
                    </div>

                    <div class="col-12 operasional mt-2">
                      <h5 class="form-title mb-2 text-black pb-2">Detail Pengeluaran</h5>
                    </div>

                    <div class="col-12 operasional mb-3" id="row-add-catatan-admin">
                      <div class="row g-2 align-items-end p-2 rounded">
                        <div class="col-12 col-lg-6">
                          <div class="input-block">
                            <label class="small">Nama Pencatatan<span class="text-danger">*</span></label>
                            <input type="text" class="form-control fill_catatan" id="cad_notes"
                              placeholder="Contoh: Makan Siang">
                          </div>
                        </div>
                        <div class="col-12 col-lg-5">
                          <div class="input-block">
                            <label class="small">Nominal<span class="text-danger">*</span></label>
                            <div class="input-group">
                              <span class="input-group-text">Rp</span>
                              <input class="form-control fill_catatan number-only nominal_only" id="cad_nominal"
                                placeholder="10.000">
                            </div>
                          </div>
                        </div>
                        <div class="col-12 col-lg-1">
                          <button type="button" class="btn btn-primary w-100 btn-add-catatan">
                            <i class="fa fa-plus"></i>
                          </button>
                        </div>
                      </div>
                    </div>

                    <div class="col-12 operasional">
                      <div class="table-responsive">
                        <table class="table table-center" id="tableDetail" style="min-width: 400px;">
                          <thead>
                            <tr>
                              <th width="50">No</th>
                              <th>Keterangan</th>
                              <th class="text-end">Nominal</th>
                              <th class="text-center">Aksi</th>
                            </tr>
                          </thead>
                          <tbody></tbody>
                          <tfoot>
                            <tr class="fw-bold">
                              <td colspan="2" class="text-end">Total :</td>
                              <td class="total text-end">Rp 0</td>
                              <td></td>
                            </tr>
                          </tfoot>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" data-bs-dismiss="modal" class="btn btn-back cancel-btn me-2">Batal</button>
            <button type="button" class="btn btn-primary paid-continue-btn btn-save-admin">Tambah
              Aktivitas</button>
          </div>
        </form>
      </div>
    </div>
  </div>
