    <div class="modal modal-xl custom-modal fade" id="add_petty_cash" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Tambah Kas Kecil</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-6">
                                    <div class="input-block mb-3">
                                        <label>Tanggal<span class="text-danger">*</span></label>
                                        <input type="date" class="form-control fill" id="pc_date">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="input-block mb-3">
                                        <label>Nama Staff<span class="text-danger">*</span></label>
                                        <select class="form-select fill" id="staff_id"></select>
                                    </div>
                                </div>
                                <div class="col-12 py-3 mb-3">
                                    <table class="table table-center" id="tableCash" style="min-height: 15vh">
                                        <thead>
                                            <th>Catatan</th>
                                            <th>Kategori</th>
                                            <th>Tipe</th>
                                            <th>Nominal</th>
                                            <th class="no-sort text-center">Aksi</th>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                                <div class="col-12 px-2 mb-3">
                                    <div class="row input_table g-3 align-items-end">
                                        <div class="col-12 col-lg-3 add">
                                            <div class="input-block mb-3" id="row-product">
                                                <label>Catatan<span class="text-danger">*</span></label>
                                                <input type="text" class="form-control fill_cash" id="pc_description"
                                                    placeholder="Masukkan Catatan">
                                            </div>
                                        </div>
                                        <div class="col-12 col-lg-3 add">
                                            <div class="input-block mb-3" id="row-cash">
                                                <label>Kategori Kas<span class="text-danger">*</span></label>
                                                <select class="form-select fill_cash" id="cc_id">
                                                    <option value="debit" checked>Masuk</option>
                                                    <option value="credit">Keluar</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-6 col-lg-2 add">
                                            <div class="input-block mb-3">
                                                <label>Tipe Kas<span class="text-danger">*</span></label>
                                                <input type="text" class="form-control fill_cash" id="cc_type" disabled>
                                            </div>
                                        </div>
                                        <div class="col-6 col-lg-3 add">
                                            <div class="input-block mb-3">
                                                <label>Nominal<span class="text-danger">*</span></label>
                                                <div class="input-group fix-nominal">
                                                    <span class="input-group-text">Rp.</span>
                                                    <input type="text" name="" id="pc_nominal" class="form-control fill_cash number-only nominal_only" placeholder="Contoh 10000">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-12 col-lg-1 add text-end">
                                            <button type="button" class="btn btn-primary w-100 btn-add-cash mb-3">
                                                +
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Tambah Kas</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
