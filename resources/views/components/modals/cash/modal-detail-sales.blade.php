  <div class="modal modal-lg custom-modal fade" id="modal-detail-sales" role="dialog" data-bs-backdrop="static"
    data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-md">
      <div class="modal-content">
        <div class="modal-header border-0 pb-0">
          <div class="form-header modal-header-title  text-start mb-0">
            <h4 class="mb-0 modal-title">Detail Operasional</h4>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup">
          </button>
        </div>
        <form action="#">
          <div class="modal-body">
            <div class="form-groups-item border-0 pb-0">
              <div class="row">
                <div class="col-12">
                  <table class="table table-bordered mb-0">
                    <thead class="">
                      <tr>
                        <th>Keterangan</th>
                        <th class="text-end">Nominal</th>
                      </tr>
                    </thead>
                    <tbody id="detail-sales-body"></tbody>
                    <tfoot class="table-light fw-bold">
                      <tr>
                        <td>Total</td>
                        <td class="text-end" id="detail-sales-total"></td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" data-bs-dismiss="modal" class="btn btn-back cancel-btn me-2">Kembali</button>
          </div>
        </form>
      </div>
    </div>
  </div>
