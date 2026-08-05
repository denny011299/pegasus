    <div class="modal modal-xl custom-modal fade" id="add_supplies" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Tambah Bahan Mentah</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-12">
                                    <div class="input-block mb-3">
                                        <label>Nama<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="supplies_name"
                                            placeholder="Input Nama Bahan Mentah">
                                    </div>
                                </div>
                                <div class="col-12 col-lg-4">
                                    <div class="input-block mb-3" id="row-satuan">
                                        <label>Satuan<span class="text-danger">*</span></label>
                                        <select id="supplies_unit" class="form-select fill"></select>
                                    </div>
                                </div>
                                <div class="col-6 col-lg-4">
                                    <div class="input-block mb-3">
                                        <label>Default Unit<span class="text-danger">*</span></label>
                                        <select class="form-select fill select2" id="unit_id">
                                        </select>
                                    </div>
                                </div>
                                <div class="col-6 col-lg-4">
                                    <div class="input-block mb-3">
                                        <label>Stock Alert<span class="text-danger">*</span></label>
                                        <div class="input-group mb-3">
                                            <input type="number" class="form-control number-only fill" id="alert" value="0" min="0" step="1" aria-describedby="basic-addon3">
                                            <span class="input-group-text" id="satuan_alert">-</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="input-block mb-3">
                                        <label>Deskripsi</label>
                                        <textarea class="form-control " id="supplies_desc" cols="30" rows="5"></textarea>
                                    </div>
                                </div>
                                <hr>
                                <div class="row mb-3">
                                    <div class="col-lg-8 col-md-6 col-4">
                                        <label>Variasi Bahan</label>
                                    </div>
                                    <div class="col-lg-4 col-md-6 col-8 text-end">
                                        <div class="row">
                                            <div class="col-9">
                                                <select name="" id="supplies_variant" class="form-select select2">
                                                </select>
                                            </div>
                                            <div class="col-3">
                                                 <button type="button" class="btn btn-primary btnAddRow"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table" id="productVariantTable">
                                        <thead>
                                            <tr>
                                                <td>Supplier<span class="text-danger"  style="width:23%">*</span></td>
                                                <td>Nama Variasi<span class="text-danger">*</span></td>
                                                <td>SKU<span class="text-danger">*</span></td>
                                                <td>Harga<span class="text-danger">*</span></td>
                                                <td>Barcode</td>
                                                <td class="text-center" style="width:10%">Aksi</td>
                                            </tr>
                                        </thead>
                                        <tbody id="tbVariant">
                                           
                                        </tbody>
                                    </table>
                                </div>
                                <hr>
                                <label class="mb-3">Atur Relasi</label>
                                <div class="row">
                                    <div class="col-lg-4 col-5">
                                        <select name="" id="relasi1" class="form-select"></select>
                                    </div>
                                    <div class="col-lg-1 col-2"> <h6 class="text-center pt-2"> - </h6> </div>
                                    <div class="col-lg-4 col-5">
                                        <select name="" id="relasi2" class="form-select"></select>
                                    </div>
                                    <div class="col-lg-3 col-12 text-end mx-0">
                                        <div class="d-flex justify-content-end mt-3 mt-lg-0">
                                            <button class="btn btn-primary btn-sm" type="button" id="btnAddRowRelasi">Tambah Row Relasi</button>
                                        </div>
                                    </div>
                                </div>
                                 <table class="table table-bordered mb-2 mt-4">
                                    <thead>
                                        <tr>
                                            <td>Name Unit 1<span class="text-danger">*</span></td>
                                            <td>Name Unit 2<span class="text-danger">*</span></td>
                                            <td class="text-center">Aksi</td>
                                        </tr>
                                    </thead>
                                    <tbody class="tbRelasi" id="tbRelasi">
                                    </tbody>
                                </table>
                                {{-- 
                                <div class="col-12">
                                    <div class="input-block">
                                        <label>Relation Unit<span class="text-danger">*</span></label>
                                        <div class="input-block mb-3 row relationContainer">
                                            <div class="col-2">
                                                <label id="pu_id_1">-</label>
                                                <input type="text" class="form-control fill" id="supplies_stock1"
                                                placeholder="Input Stock Bahan">
                                            </div>
                                            <div class="col-1 pt-4 fs-3 px-0 mx-0 text-center">
                                                =
                                            </div>
                                            <div class="col-2">
                                                <label id="pu_id_2">-</label>
                                                <input type="text" class="form-control fill" id="supplies_stock2"
                                                placeholder="Input Stock Bahan">
                                            </div>
                                        </div>
                                    </div>
                                </div>--}}
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Tambah Bahan Mentah</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
