    <div class="modal fade" id="view_supplier" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content p-3">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Detail Pemasok</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row mb-3">
                                <div class="col-lg-4 col-6 mb-2">
                                    <p class="text-muted">Nama Supplier</p>
                                    <p class="text-black" id="supplier_name"></p>
                                </div>
                                <div class="col-lg-4 col-6 mb-2">
                                    <p class="text-muted">No. Telp</p>
                                    <p class="text-black" id="supplier_phone"></p>
                                </div>
                                <div class="col-lg-4 col-6 mb-2">
                                    <p class="text-muted">Alamat</p>
                                    <p class="text-black" id="supplier_address"></p>
                                </div>
                                <div class="col-lg-4 col-6 mb-2">
                                    <p class="text-muted">Keterangan</p>
                                    <p class="text-black" id="supplier_notes"></p>
                                </div>
                                <div class="col-lg-4 col-6 mb-2">
                                    <p class="text-muted">Total Hutang</p>
                                    <p class="text-black" id="supplier_payment"></p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12 pe-0 pt-3 row">
                                    <div class="col-lg-3 col-6">
                                        <div class="input-block mb-3">
                                            <label>Dari</label>
                                            <div>
                                                <input type="date" class="form-control" id="start_date">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-6">
                                        <div class="input-block mb-3">
                                            <label>Sampai</label>
                                            <div>
                                                <input type="date" class="form-control" id="end_date">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-12">
                                        <div class="input-block mb-3">
                                            <label>Status</label>
                                            <select class="form-select fill" id="status">
                                                <option value="">Semua</option>
                                                <option value="4">Menunggu Approval</option>
                                                <option value="1" selected>Belum Terbayar</option>
                                                <option value="3">Menunggu Tanda Terima</option>
                                                <option value="2">Terbayar</option>
                                                <option value="5">Ditolak</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-2 col-md-12 pt-lg-4 pt-2 pe-0 text-end mb-lg-0 mb-3">
                                        <a class="btn btn-outline-secondary btn-clear">
                                            Clear
                                        </a>
                                    </div>
                                </div>
                                <div class="col-12 pb-3 mb-3">
                                    <div class="table-scroll overflow-x-auto">
                                        <table class="table table-center" id="tablePo" style="min-height: 15vh">
                                            <thead>
                                                <th>Tanggal Pembelian</th>
                                                <th>Jatuh Tempo</th>
                                                <th>No. Invoice</th>
                                                <th>Jumlah</th>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer p-0 justify-content-between">
                                <div class="">
                                    <p class="text-black">Total</p>
                                    <p class="text-black fw-bold" id="supplier_payment_bawah"></p>
                                </div>
                                <button type="button" data-bs-dismiss="modal"
                                    class="btn btn-back cancel-btn">Kembali</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
