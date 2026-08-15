    <div class="modal modal-lg custom-modal fade" id="add_sales_invoice" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="mb-0 modal-title">Tambah Faktur Penjualan</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <div class="input-block mb-3">
                                        <label>Tanggal Faktur<span class="text-danger">*</span></label>
                                        <input type="date" class="form-control fill" id="soi_date">
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="input-block mb-3">
                                        <label>Jatuh Tempo<span class="text-danger">*</span></label>
                                        <input type="date" class="form-control fill" id="soi_due">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="input-block">
                                        <label>Jumlah<span class="text-danger">*</span></label>
                                        <div class="input-group mb-3">
                                            <span class="input-group-text">Rp </span>
                                            <input type="text" class="form-control fill number-only nominal_only" id="soi_total" value="0" placeholder="20.000">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="row-acc">
                            <button class="btn btn-danger btn-decline-invoice me-2" type="button">Tolak</button>
                            <button class="btn btn-success btn-approve-invoice me-3" type="button">Setujui</button>
                        </div>
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save-invoice">Tambah Faktur</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
