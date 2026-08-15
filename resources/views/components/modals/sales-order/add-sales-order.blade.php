    <div class="modal modal-xl custom-modal fade" id="add_sales_order" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Tambah Pengiriman</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-12 row pe-0">
                                    <div class="col-lg-4 col-md-6 col-12 pe-0">
                                        <div class="input-block mb-3">
                                            <label>Tanggal<span class="text-danger">*</span></label>
                                            <input type="date" class="form-control fill" id="so_date">
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-6 col-12 pe-0">
                                        <div class="input-block mb-3 " id="row-Armada">
                                            <label>Nama Armada<span class="text-danger">*</span></label>
                                            <select id="so_customer" class="form-control fill"></select>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-12 col-12 pe-0">
                                        <div class="input-block mb-3">
                                            <label>Ref Number</label>
                                            <input id="so_ref_number" class="form-control" placeholder="Input Ref Number">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 row pe-0">
                                    <div class="col-lg-4 col-md-12 col-12 pe-0">
                                        <div class="input-block mb-3">
                                            <label class="form-label d-flex">Bukti Foto<span class="text-danger">*</span>
                                                <span id="check_foto" style="display: none" class="ms-2">
                                                    <div class="d-flex g-3">
                                                        <i class="fa fa-check-circle text-success mt-1"></i>
                                                        <p class="text-muted ms-1"><span id="jumlahFoto">1</span> gambar terunggah</p>
                                                    </div>
                                                </span>
                                            </label>
                                            <button class="btn btn-outline-primary btn-sm" id="btn_bukti_foto" type="button">Foto Bukti</button>
                                            <button type="button" class="btn btn-outline-primary" id="btn-lihat-bukti" style="display: none">Lihat Bukti</button>
                                            <input type="hidden" name="" id="bukti">
                                        </div>
                                    </div>
                                    {{-- 
                                    <div class="col-lg-4 col-md-6 col-12">
                                        <div class="input-block mb-3">
                                            <label>Nama Sales</label>
                                            <select id="sales_id" class="form-control"></select>
                                        </div>
                                    </div>--}}
                                </div>
                                <div class="col-12 row pe-0">
                                    {{-- <div class="col-lg-4 col-md-6 col-12">
                                        <div class="input-block">
                                            <label>Diskon</label>
                                            <div class="input-group mb-3">
                                                <input type="text" class="form-control  number-only" id="so_discount" 
                                                placeholder="Input Diskon" value="0">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-6 col-12">
                                        <div class="input-block">
                                            <label>PPN</label>
                                            <div class="input-group mb-3">
                                                <input type="text" class="form-control  number-only" id="so_ppn" 
                                                placeholder="Input PPN" value="0">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-12 col-12">
                                        <div class="input-block mb-3">
                                            <label>Biaya Pengiriman</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text">Rp </span>
                                                <input type="text" class="form-control  number-only nominal_only" id="so_cost" value="0" placeholder="Input Biaya Pengiriman">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-6 col-12">
                                        <div class="input-block mb-3">
                                            <label>No. Invoice</label>
                                            <input id="so_invoice_no" class="form-control" value="{{ $data['so_invoice_no'] || '-' }}" disabled>
                                        </div>
                                    </div> --}}
                                </div>
                                <div class="col-12 row pe-0">
                                    <div class="col-lg-6 col-md-12 col-12 pe-0">
                                        <div class="input-block mb-3">
                                            <label class="d-flex align-items-center gap-2">
                                                SKU
                                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" id="btn_toggle_scan_so" title="Ganti mode Scan">
                                                    <i class="fa fa-barcode"></i> Scan
                                                </button>
                                            </label>
                                            <div id="so_mode_select">
                                                <select class="form-select" id="so_sku"></select>
                                            </div>
                                            <div id="so_mode_scan" style="display:none">
                                                <div class="input-group mb-3" style="max-width: 600px;"> <input type="text" 
                                                        class="form-control" 
                                                        id="so_scan_barcode" 
                                                        placeholder="Scan / ketik barcode..." 
                                                        style="flex: 0 0 50%;"> <input type="number" 
                                                        class="form-control" 
                                                        id="so_scan_qty" 
                                                        placeholder="Qty" 
                                                        value="1" 
                                                        min="1" 
                                                        style="max-width:80px">
                                                    
                                                    <button type="button" 
                                                            class="btn btn-primary" 
                                                            id="btn_scan_add_so">
                                                        <i class="fa fa-plus"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-12 col-12 pe-0">
                                        
                                    </div>
                                </div>
                                <div class="col-12 overflow-x-auto mb-3">
                                    <table class="table table-center table-responsive">
                                        <thead>
                                            <th>Produk</th>
                                            <th>Variasi</th>
                                            <th>SKU</th>
                                            <th class="text-center">Jumlah</th>
                                            {{-- <th class="text-end">Harga Satuan</th>
                                            <th class="text-end">Subtotal</th> --}}
                                            <th class="text-center">Action</th>
                                        </thead>
                                        <tbody id="tableSalesModal">
                                            
                                        </tbody>
                                    </table>
                                </div>
                                {{-- <div class="col-12 row pt-3">
                                    <div class="col-lg-6 col-md-6 col-12"></div>
                                    <div class="col-lg-6 col-md-6 col-12">
                                        <div class="d-flex justify-content-between">
                                            <p>Total</p>
                                            <p id="value_total">0</p>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <p>Ppn</p>
                                            <p id="value_ppn">0</p>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <p>Diskon</p>
                                            <p id="value_discount">0</p>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <p>Biaya Pengiriman</p>
                                            <p id="value_cost">0</p>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <b>Grand Total</b>
                                            <b id="value_grand">0</b>
                                        </div>
                                    </div>
                                </div> --}}
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        @roleCan('Pengiriman', 'others')
                            <button type="button" class="btn btn-danger me-2 btn_decline" style="display: none">Tolak</button>
                            <button type="button" class="btn btn-success me-2 btn_acc" style="display: none">Terima</button>
                        @endroleCan
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Tambah Pengiriman</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
