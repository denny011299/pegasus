  <div class="modal fade custom-modal" id="add_purchase_delivery" role="dialog" tabindex="-1"
    aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header border-0 pb-0">
          <div class="form-header modal-header-title text-start mb-0">
            <h4 class="mb-0 modal-title">Tambah Delivery Notes</h4>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <form action="#">
          <div class="modal-body">
            <div class="form-groups-item border-0 pb-0">
              <div class="row g-3">
                <div class="col-12 col-md-4">
                  <div class="input-block">
                    <label>Nama Penerima<span class="text-danger">*</span></label>
                    <select name="" id="pdo_receiver" class="form-select"></select>
                  </div>
                </div>
                <div class="col-12 col-md-4">
                  <div class="input-block">
                    <label>Tanggal<span class="text-danger">*</span></label>
                    <input type="date" class="form-control fill" id="pdo_date">
                  </div>
                </div>
                <div class="col-12 col-md-4">
                  <div class="input-block">
                    <label>No. Telepon<span class="text-danger">*</span></label>
                    <input type="text" class="form-control fill number-only" id="pdo_phone"
                      placeholder="Input nomor telepon">
                  </div>
                </div>

                {{--  <div class="col-12 col-md-6">

                                    <div class="input-block">
                                        <label>Alamat<span class="text-danger">*</span></label>
                                        <textarea class="form-control fill" id="pdo_address" rows="3" placeholder="Alamat penerima"></textarea>
                                    </div>
                                </div> --}}
                <div class="col-12 col-md-12">
                  <div class="input-block">
                    <label>Keterangan</label>
                    <textarea class="form-control" id="pdo_desc" rows="3" placeholder="Keterangan pengiriman"></textarea>
                  </div>
                </div>

                <div class="col-12">
                  <div class="table-responsive">
                    <table class="table table-center table-bordered align-middle" id="tablePurchaseDelivery">
                      <thead class="table-light">
                        <tr>
                          <th>Supplies</th>
                          <th>SKU</th>
                          <th>Qty</th>
                        </tr>
                      </thead>
                      <tbody></tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer d-flex justify-content-end">
            <div class="row-acc">
              <button class="btn btn-danger btn-decline me-2" type="button">Tolak</button>
              <button class="btn btn-success btn-approve me-3" type="button">Setujui</button>
            </div>
            <button type="button" data-bs-dismiss="modal" class="btn btn-outline-secondary me-2">Batal</button>
            <button type="button" class="btn btn-primary btn-save-delivery">Simpan Perubahan</button>
          </div>
        </form>
      </div>
    </div>
  </div>
