    <div class="modal modal-lg custom-modal fade" id="add_bom" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Tambah Resep Bahan</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-12 col-md-7 col-lg-7">
                                    <div class="input-block mb-3">
                                        <label>Produk<span class="text-danger">*</span></label>
                                        <select class="form-select fill" id="product_id"></select>
                                    </div>
                                </div>

                                <div class="col-12 col-md-5 col-lg-5">
                                    <div class="input-block mb-3">
                                        <label>Qty Produksi<span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="text" class="form-control fill number-only" id="bom_qty" placeholder="Qty">
                                            <select class="form-select w-25 fill" id="unit_id"></select>
                                        </div>
                                        <div id="product_unit_info" class="mt-1" style="display:none; font-size:0.82rem; line-height:1.4;"></div>

                                    </div>
                                </div>

                                <div class="col-12 py-3 mb-3">
                                    <div class="table-responsive">
                                        <table class="table table-center" id="tableSupply" style="min-height: 15vh">
                                            <thead>
                                                <tr>
                                                    <th>Nama Bahan</th>
                                                    <th>Qty</th>
                                                    <th>Satuan</th>
                                                    <th class="no-sort text-center">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="col-12 col-md-12 col-lg-4">
                                    <div class="input-block mb-3">
                                        <label>Nama Bahan<span class="text-danger">*</span></label>
                                        <select class="form-select fill_supply" id="supplies_id"></select>
                                    </div>
                                </div>

                                <div class="col-6 col-md-6 col-lg-3">
                                    <div class="input-block mb-3">
                                        <label>Qty<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill_supply number-only" id="bom_detail_qty" placeholder="Qty">
                                    </div>
                                </div>

                                <div class="col-6 col-md-6 col-lg-4">
                                    <div class="input-block mb-3">
                                        <label>Satuan<span class="text-danger">*</span></label>
                                        <select class="form-select fill_supply" id="unit_supplies_id"></select>
                                        <div id="supplies_unit_info" class="mt-1" style="display:none; font-size:0.82rem; line-height:1.4;"></div>
                                    </div>
                                </div>

                                <div class="col-12 col-md-12 col-lg-1 pt-4">
                                    <a class="btn btn-primary btn-add-supply w-100">+</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Tambah Resep</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
