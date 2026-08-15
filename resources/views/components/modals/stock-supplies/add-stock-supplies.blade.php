    <div class="modal fade" id="add_stock_supplies" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content p-3">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Riwayat Stok Bahan Mentah</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-lg-3 col-md-6 col-sm-12">
                                    <div class="input-block mb-3">
                                        <label>Dari</label>
                                        <div>
                                            <input type="date" class="form-control" id="start_date">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 col-sm-12">
                                    <div class="input-block mb-3">
                                        <label>Sampai</label>
                                        <div>
                                            <input type="date" class="form-control" id="end_date">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 col-sm-0"></div>
                                <div class="col-lg-3 col-md-6 col-sm-12 pt-4 text-end">
                                    <a class="btn btn-outline-secondary btn-clear">
                                        Clear
                                    </a>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12 py-3 mb-3">
                                    <div class="table-scroll overflow-x-auto">
                                        <table class="table table-center" id="tableLog" style="min-height: 15vh">
                                            <thead>
                                                <th style="width: 15%">Tanggal</th>
                                                <th style="width: 15%">Staff</th>
                                                <th style="width: 15%">No. Transaksi</th>
                                                <th style="width: 25%">Catatan</th>
                                                <th style="width: 15%" class="text-center">Masuk</th>
                                                <th style="width: 15%" class="text-center">Keluar</th>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>


                            <div class="modal-footer p-0">
                                <button type="button" data-bs-dismiss="modal"
                                    class="btn btn-back cancel-btn me-2">Kembali</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
