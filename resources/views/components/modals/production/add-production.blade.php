    <div class="modal modal-lg custom-modal fade" id="addProduction" aria-modal="true" role="dialog" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content ">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title d-inline-block">Tambah Produksi</h4>
                        <span id="production_status_badge_header" class="ms-2" style="display:none;"></span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-lg-6 col-12">
                                    <div class="mb-3">
                                        <label>Tanggal</label>
                                        <input type="date" class="form-control fill" id="production_date" disabled>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-12">
                                    <div class="mb-3">
                                        <label>Keterangan</label>
                                        <input type="text" class="form-control" id="production_desc" placeholder="Masukkan Keterangan">
                                    </div>
                                </div>
                                <div class="col-12" id="row-production-detail-info" style="display:none;">
                                    <div class="row">
                                        <div class="col-lg-6 col-12">
                                            <div class="mb-3">
                                                <label>Kode Produksi</label>
                                                <input type="text" class="form-control" id="production_code_display" disabled>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 col-12">
                                            <div class="mb-3">
                                                <label>Dibuat Oleh</label>
                                                <input type="text" class="form-control" id="production_created_by_display" disabled>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-12" id="row-production-acc-by" style="display:none;">
                                    <div class="mb-3">
                                        <label>Diapprove Oleh</label>
                                        <input type="text" class="form-control" id="production_acc_by_name" disabled>
                                    </div>
                                </div>
                                <div class="col-12" id="row-production-cancel-info" style="display:none;">
                                    <div class="row">
                                        <div class="col-lg-6 col-12">
                                            <div class="mb-3">
                                                <label>Pengajuan Batal Oleh</label>
                                                <input type="text" class="form-control" id="production_cancel_requested_by_display" disabled>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 col-12">
                                            <div class="mb-3">
                                                <label>Notes Pembatalan</label>
                                                <input type="text" class="form-control" id="production_cancel_notes_display" disabled>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                {{-- <div class="col-lg-6"></div> --}}
                                {{-- <div class="col-lg-6 col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Produk</label>
                                        <select class="form-select fill" id="product_id"></select>
                                    </div>
                                </div> --}}
                                {{-- <div class="col-lg-2 col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Qty Produksi</label>
                                        <input type="number" class="form-control fill number-only" id="production_qty" placeholder="Jumlah Produksi" value="1">
                                    </div>
                                </div>
                                <div class="col-lg-4 col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Total Barang Produksi</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control fill number-only" id="production_total" placeholder="0" value="0" disabled>
                                            <select class="form-control w-25" id="unit_id" disabled>
                                            </select>
                                        </div>
                                    </div>
                                </div> --}}
                                <div class="col-12 py-3 mb-3">
                                    <div class="table-responsive">
                                        <table class="table table-center custom-table-scroll" id="tableProduct">
                                            <thead>
                                                <tr>
                                                    <th style="width: 50%;">Nama Produk</th>
                                                    <th class="text-center" style="width: 15%;">Qty</th>
                                                    <th style="width: 20%;">Satuan</th>
                                                    <th class="no-sort text-center" style="width: 15%;">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                </tbody>
                                            <tfoot class="dos">
                                                <tr>
                                                    <td class="fw-bold text-black text-end">Total Dos:</td>
                                                    <td class="fw-bold text-black text-center"><span id="total_dos">0</span></td>
                                                    <td class="fw-bold text-black">Dos</td>
                                                    <td></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-12 px-2">
                                    <div class="row input_table g-3 align-items-end">
                                        <div class="col-12 col-lg-4 add">
                                            <div class="input-block mb-3" id="row-product">
                                                <label>Nama Produk<span class="text-danger">*</span></label>
                                                <select class="form-select fill_product" id="product_id"></select>
                                            </div>
                                        </div>
                                        <div class="col-6 col-lg-3 add">
                                            <div class="input-block mb-3">
                                                <label>Qty<span class="text-danger">*</span></label>
                                                <input type="text"
                                                    class="form-control fill_product number-only"
                                                    id="production_qty"
                                                    placeholder="Qty Produk">
                                            </div>
                                        </div>
                                        <div class="col-6 col-lg-4 add">
                                            <div class="input-block mb-3">
                                                <label>Nama Satuan<span class="text-danger">*</span></label>
                                                <select class="form-select fill_product" id="unit_id"></select>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-12 col-lg-1 add">
                                            <button type="button" class="btn btn-primary w-100 btn-add-product mb-3">
                                                +
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        @roleCan('Produksi', 'others')
                            <button type="button" id="btn-tolak" class="btn btn-danger me-2 btn_decline" style="display: none">Tolak</button>
                            <button type="button" id="btn-terima" class="btn btn-success me-2 btn_acc" style="display: none">Terima</button>
                        @endroleCan
                        <a class="btn btn-outline-secondary btn-cancel me-2" data-bs-dismiss="modal">Batal</a>
                        <a class="btn btn-primary btn-save">Tambah Produksi</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
