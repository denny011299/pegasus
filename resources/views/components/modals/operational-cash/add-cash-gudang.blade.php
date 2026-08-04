  <div class="modal modal-lg custom-modal fade" id="add_cash_gudang" role="dialog" data-bs-backdrop="static"
    data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-md">
      <div class="modal-content">
        <div class="modal-header border-0 pb-0">
          <div class="form-header modal-header-title  text-start mb-0">
            <h4 class="mb-0 modal-title">Tambah Aktivitas Gudang</h4>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup">
          </button>
        </div>
        <form action="#">
          <div class="modal-body">
            <div class="form-groups-item border-0 pb-0">
              <div class="row">
                <div class="col-lg-6 col-12 mb-4">
                  <div class="input-block">
                    <label>Jenis Aktivitas</label>
                    <select class="form-select" id="jenis_input_gudang">
                      <option value="saldo">Manajemen Saldo Kas</option>
                      <option value="operasional" selected>Aktivitas Operasional</option>
                    </select>
                  </div>
                </div>
                <div class="col-lg-6 col-12 mb-lg-4 mb-0"></div>
                <div class="row p-0 m-0" id="inputModal">
                  <div class="col-lg-6 col-12 saldo_kas">
                    <div class="input-block mb-3">
                      <label>Aksi Dana<span class="text-danger">*</span></label>
                      <select class="form-select fill" id="oc_transaksi_gudang">
                        <option value=1>Pengajuan</option>
                        <option value=2>Pengembalian</option>
                      </select>
                    </div>
                  </div>
                  <div class="col-12 col-lg-6 operasional">
                    <div class="input-block mb-3">
                      <label>Tanggal<span class="text-danger">*</span></label>
                      <input type="date" class="form-control fill" id="oc_date_gudang"></input>
                    </div>
                  </div>
                  <div class="col-lg-6 col-12">
                    <div class="input-block mb-3" id="row-cash">
                      <label>Nama Pengaju<span class="text-danger">*</span></label>
                      <select class="form-select fill" id="staff_id_gudang"></select>
                    </div>
                  </div>
                  <div class="col-lg-6 col-12 saldo_kas">
                    <div class="input-block mb-3">
                      <label>Nominal<span class="text-danger">*</span></label>
                      <div class="input-group fix-nominal">
                        <span class="input-group-text">Rp </span>
                        <input class="form-control fill number-only nominal_only saldos" id="oc_nominal_gudang"
                          placeholder="Contoh: 10.000"></input>
                      </div>
                    </div>
                  </div>
                  <div class="col-lg-6 col-12 operasional">
                    <label class="form-label d-flex">
                      Bukti Foto<span class="text-danger">*</span>
                      <span id="check_foto_gudang" style="display: none" class="ms-2">
                        <div class="d-flex g-3">
                          <i class="fa fa-check-circle text-success mt-1"></i>
                          <p class="text-muted ms-1">gambar terunggah</p>
                        </div>
                      </span>
                    </label>
                    <div class="d-grid d-md-block gap-2">
                      <button type="button" class="btn btn-outline-primary" id="btn-foto-bukti-gudang">Foto
                        Bukti</button>
                      <button type="button" class="btn btn-outline-primary" id="btn-lihat-bukti-gudang"
                        style="display: none">Lihat Bukti</button>
                    </div>
                    <input type="hidden" name="" id="bukti_gudang">
                  </div>
                  <div class="col-lg-6 col-12 saldo_kas">
                    <div class="input-block mb-3">
                      <label>Keterangan<span class="text-danger">*</span></label>
                      <input type="text" class="form-control fill saldos" id="oc_notes_gudang"
                        placeholder="Contoh: Untuk kas harian">
                    </div>
                  </div>
                  <div class="col-12 operasional mt-2">
                    <h5 class="form-title mb-2 text-black">Detail</h5>
                  </div>
                  <div class="col-12 px-2 mb-3 operasional">
                    <div class="row input_table g-3 align-items-end px-1">
                      <div class="col-12 col-lg-6 add">
                        <div class="input-block mb-3" id="row-gudang">
                          <label>Nama Armada<span class="text-danger">*</span></label>
                          <select class="form-select fill" id="customer_id"></select>
                        </div>
                      </div>
                      <div class="col-12 col-lg-6 add">
                        <div class="input-block mb-3">
                          <label>Keterangan<span class="text-danger">*</span></label>
                          <input type="text" class="form-control fill_catatan" id="cgd_notes"
                            placeholder="Contoh: Kas Harian">
                        </div>
                      </div>
                      <div class="col-12 col-lg-6 add">
                        <div class="input-block mb-3">
                          <label>Pilih Jumlah Nominal<span class="text-danger">*</span></label>
                          <select class="form-select fill_catatan" id="jenis_nominal">
                            <option value="" disabled selected>Pilih Jumlah Nominal</option>
                            <option value="500000">Rp 500.000</option>
                            <option value="1000000">Rp 1.000.000</option>
                            <option value="1500000">Rp 1.500.000</option>
                            <option value="2000000">Rp 2.000.000</option>
                            <option value="manual">Input Manual</option>
                          </select>
                        </div>
                      </div>
                      <div class="col-12 col-lg-5 add input_nominal">
                        <div class="input-block mb-3">
                          <label>Nominal<span class="text-danger">*</span></label>
                          <div class="input-group fix-nominal">
                            <span class="input-group-text">Rp </span>
                            <input class="form-control fill_catatan number-only nominal_only" disabled
                              id="cgd_nominal" placeholder="Contoh: 10.000"></input>
                          </div>
                        </div>
                      </div>
                      <div class="col-12 col-md-12 col-lg-1 add">
                        <button type="button" class="btn btn-primary w-100 btn-add-gudang mb-3">
                          +
                        </button>
                      </div>
                    </div>
                  </div>
                  <div class="col-12 py-3 mb-3 operasional">
                    <div class="table-responsive">
                      <table class="table table-center" id="tableDetailGudang" style="min-height: 15vh">
                        <thead>
                          <th>No</th>
                          <th>Armada</th>
                          <th style="width: 25%">Nama</th>
                          <th class="text-end">Nominal</th>
                          <th class="no-sort text-center">Aksi</th>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                          <tr>
                            <td colspan="3" class="text-end fw-bold">Total : </td>
                            <td class="total_gudang text-end fw-bold">Rp 0</td>
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
          <div class="modal-footer">
            <button type="button" data-bs-dismiss="modal" class="btn btn-back cancel-btn me-2">Batal</button>
            <button type="button" class="btn btn-primary paid-continue-btn btn-save-gudang">Tambah
              Aktivitas</button>
          </div>
        </form>
      </div>
    </div>
  </div>
