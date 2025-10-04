@extends('layout.mainlayout')

@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="card mb-0">
                <div class="card-body">
                    <!-- Page Header -->
                    <div class="page-header">
                        <div class="content-page-header">
                            <div class="d-flex justify-content-between w-100">
                                <h4>{{$title}}</h4>
                                <button class="btn btn-back">Kembali</button>
                            </div>
                        </div>
                    </div>
                    <!-- /Page Header -->
                    <div class="row">
                        <div class="col-md-12">
                            <form action="#">
                                <div class="form-group-item">
                                    <div class="row">
                                        <div class="col-4">
                                            <div class="input-block mb-3">
                                                <label>Nama<span class="text-danger">*</span></label>
                                                <input type="text" class="form-control fill" id="product_name"
                                                    placeholder="Input Nama Produk">
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="input-block mb-3">
                                                <label>Kategori<span class="text-danger">*</span></label>
                                                <select class="form-select fill select2" id="product_category">
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="input-block mb-3">
                                                <label>Satuan<span class="text-danger">*</span></label>
                                                <div class="container-satuan">
                                                    <select class="form-control fill" id="product_unit"  name="product_unit[]" ></select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="input-block mb-3">
                                                <label>Default Unit<span class="text-danger">*</span></label>
                                                <select class="form-select fill select2" id="unit_id">
                                                </select>
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="row mb-3">
                                            <div class="col-8">
                                                <label>Variasi Produk</label>
                                            </div>
                                            <div class="col-4 text-end">
                                                <div class="row">
                                                    <div class="col-9">
                                                        <select name="" id="product_variant" class="form-select select2">
                                                        </select>
                                                    </div>
                                                    <div class="col-3">
                                                        <button type="button" class="btn btn-primary btnAddRow"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="table-responsive">

                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <td>Nama Variasi</td>
                                                        <td>SKU</td>
                                                        <td>Harga</td>
                                                        <td>Barcode</td>
                                                        <td>Stock Alert</td>
                                                        <td class="text-center" style="width:15%">Aksi</td>
                                                    </tr>
                                                </thead>
                                                <tbody id="tbVariant">
                                                
                                                </tbody>
                                            </table>
                                        </div>

                                        
                                    </div>
                                </div>
                                <div class="add-customer-btns text-end">
                                    <a class="btn btn-outline-secondary btn-clear">Clear</a>
                                    <a class="btn btn-primary btn-save">Simpan Perubahan</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal " tabindex="-1" id="modalRelasi" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Atur Relasi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="container-fluid">
                        <table class="table table-bordered mb-2">
                            <thead>
                                <tr>
                                    <td>Name Unit 1</td>
                                    <td>Name Unit 2</td>
                                </tr>
                            </thead>
                            <tbody class="tbRelasi" id="tbRelasi">
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary ms-2" id="btnSaveRelasi">Save changes</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('custom_js')
    <script>
        var public = "{{ asset('') }}";
        var mode="{{$mode}}";
        var data=@json($data);
    </script>
    <script src="{{asset('Custom_js/Backoffice/Product/insertProduct.js')}}"></script>
@endsection