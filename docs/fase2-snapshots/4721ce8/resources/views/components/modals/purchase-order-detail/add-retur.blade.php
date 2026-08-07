  <div class="modal fade" id="add-retur" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content p-3">
        <div class="modal-header border-0 pb-0">
          <div class="form-header modal-header-title  text-start mb-0">
            <h4 class="mb-0 modal-title">Tambah Retur Pembelian</h4>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
          </button>
        </div>
        <form action="#">
          <div class="modal-body">
            <div class="form-groups-item border-0 pb-0">
              <div class="row">
                <div class="col-md-6 col-12">
                  <div class="input-block mb-3">
                    <label>Tanggal<span class="text-danger">*</span></label>
                    <div class="input-group">
                      <input type="date" class="form-control fill" id="rs_date">
                    </div>
                  </div>
                </div>
                {{-- <div class="col-lg-4">
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
                                        <button type="button" class="btn btn-outline-primary" id="btn-lihat-bukti" style="display: none">Lihat Bukti</button>
                                        <input type="hidden" name="" id="bukti">
                                    </div>
                                </div> --}}
                <div class="col-md-6 col-12">
                  <div class="input-block mb-3">
                    <label class="form-label">Keterangan<span class="text-danger">*</span></label>
                    <input type="text" class="form-control fill" id="rs_notes"
                      placeholder="Tambahkan Catatan">
                  </div>
                </div>
                <div class="col-12 py-3 mb-3">
                  <div style="max-height: 320px; overflow-y: auto; overflow-x: auto;">
                    <table class="table table-center" id="tableSuppliesModal">
                      <thead>
                        <tr>
                          <th id="header_name" style="position: sticky; top: 0; z-index: 2; background: #dce8f6;">
                            Nama Bahan</th>
                          <th class="text-center"
                            style="position: sticky; top: 0; z-index: 2; background: #dce8f6;">Qty</th>
                          <th style="position: sticky; top: 0; z-index: 2; background: #dce8f6;">Satuan</th>
                          <th class="text-end" style="position: sticky; top: 0; z-index: 2; background: #dce8f6;">
                            Harga</th>
                          <th class="text-end" style="position: sticky; top: 0; z-index: 2; background: #dce8f6;">
                            Subtotal</th>
                          <th class="text-center"
                            style="position: sticky; top: 0; z-index: 2; background: #dce8f6;">Aksi</th>
                        </tr>
                      </thead>
                      <tbody></tbody>
                      <tfoot>
                        <tr>
                          <td colspan="4" class="text-end fw-bold">Total : </td>
                          <td class="totals fw-bold text-end">Rp 0</td>
                          <td></td>
                        </tr>
                      </tfoot>
                    </table>
                  </div>
                </div>
                <div class="col-12 px-2 mb-3">
                  <div class="row input_table g-3 align-items-end">
                    <div class="col-12 col-lg-4 add">
                      <div class="input-block mb-3" id="row-supplies">
                        <label>Nama Bahan Mentah<span class="text-danger">*</span></label>
                        <select class="form-select fill_supplies" id="supplies_id"></select>
                      </div>
                    </div>
                    <div class="col-6 col-lg-3 add">
                      <div class="input-block mb-3">
                        <label>Qty<span class="text-danger">*</span></label>
                        <input type="text" class="form-control fill_supplies number-only" id="rsd_qty"
                          placeholder="Qty Bahan">
                      </div>
                    </div>
                    <div class="col-6 col-lg-4 add">
                      <div class="input-block mb-3">
                        <label>Nama Satuan<span class="text-danger">*</span></label>
                        <select class="form-select fill_supplies" id="unit_supplies_id"></select>
                      </div>
                    </div>
                    <div class="col-12 col-md-12 col-lg-1 add">
                      <button type="button" class="btn btn-primary w-100 btn-add-supplies mb-3">
                        +
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <div class="modal-footer p-0">
                <button type="button" data-bs-dismiss="modal" class="btn btn-back cancel-btn me-2">Batal</button>
                <button type="button" class="btn btn-primary btn-save-retur">Tambah Retur
                </button>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
