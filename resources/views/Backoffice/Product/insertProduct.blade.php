@extends('layout.mainlayout')

@section('content')
<style>
    /* Variasi Produk: overflow-x + min-width agar kolom tidak tercrush */
    #productVariantTableWrap {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    #productVariantTable {
        min-width: 1400px;
        width: 100%;
        margin: 0;
        table-layout: auto;
    }
    #productVariantTable th,
    #productVariantTable td {
        white-space: nowrap;
        vertical-align: middle;
    }
    #productVariantTable th:nth-child(1),
    #productVariantTable td:nth-child(1) {
        min-width: 200px;
    }
    #productVariantTable th:nth-child(2),
    #productVariantTable td:nth-child(2) {
        min-width: 140px;
    }
    #productVariantTable .variant_name {
        min-width: 180px;
    }
    #productVariantTable .variant_sku {
        min-width: 130px;
    }
    #productVariantTable .input-group {
        flex-wrap: nowrap !important;
        min-width: 160px;
    }
</style>
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="card mb-0 p-3">
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
                                        <div class="col-12 col-md-4">
                                            <div class="input-block mb-3">
                                                <label>Nama<span class="text-danger">*</span></label>
                                                <input type="text" class="form-control fill" id="product_name"
                                                    placeholder="Input Nama Produk">
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <div class="input-block mb-3">
                                                <label>Kategori<span class="text-danger">*</span></label>
                                                <select class="form-select fill select2" id="product_category">
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <div class="input-block mb-3">
                                                <label>Satuan<span class="text-danger">*</span></label>
                                                <div class="container-satuan">
                                                    <select class="form-select fill" id="product_unit"  name="product_unit[]" ></select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <div class="input-block mb-3">
                                                <label>Default Unit<span class="text-danger">*</span></label>
                                                <select class="form-select fill select2" id="unit_id">
                                                </select>
                                            </div>
                                        </div>
                                        <hr>
                                       <div class="row mb-3">
                                            <div class="col-12 col-md-2 col-lg-2">
                                                <label>Variasi Produk</label>
                                            </div>

                                            <div class="col-0 col-md-4 col-lg-5"></div>

                                            <div class="col-12 col-md-3 col-lg-3 mb-2 mb-lg-0">
                                                <select id="product_variant" class="form-select select2">
                                                    <option value="">Pilih Variasi</option>
                                                </select>
                                            </div>

                                            <div class="col-12 col-md-3 col-lg-2 text-start text-lg-end">
                                                <button type="button" class="btn btn-primary btnAddRow w-100 w-lg-auto">
                                                    <i class="fa fa-plus-circle me-2"></i>
                                                    <span class="d-none d-lg-inline">Tambah</span>
                                                    <span class="d-inline d-lg-none">Tambah Variasi</span>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="table-responsive" id="productVariantTableWrap" style="border: 1px solid #e2e8f0; border-radius: 8px;">

                                            <table class="table table-center table-hover mb-0" id="productVariantTable">
                                                <thead style="background:#f1f5f9; border-bottom: 1px solid #e2e8f0;">
                                                    <tr>
                                                        <th style="min-width:200px; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing:.4px; padding: 12px 16px;">Nama Variasi<span class="text-danger">*</span></th>
                                                        <th style="min-width:140px; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing:.4px; padding: 12px 16px;">SKU<span class="text-danger">*</span></th>
                                                        <th style="min-width:150px; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing:.4px; padding: 12px 16px;">Barcode</th>
                                                        <th style="min-width:230px; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing:.4px; padding: 12px 16px;">
                                                            Peringatan Stok<span class="text-danger">*</span>
                                                            <div class="small text-muted fw-normal alert-stock-wh-label" style="font-size:10px;line-height:1.2;text-transform:none;letter-spacing:0;"></div>
                                                        </th>
                                                        <th style="min-width:160px; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing:.4px; padding: 12px 16px;">Satuan Eceran</th>
                                                        <th style="min-width:140px; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing:.4px; padding: 12px 16px;">Isi / Pallet<br><small class="text-muted fw-normal" style="font-size:10px;text-transform:none;letter-spacing:0;">opsional, utk Produksi</small></th>
                                                        <th class="col-safety-stock d-none" style="min-width:230px; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing:.4px; padding: 12px 16px;">
                                                            Safety Stock
                                                            <div class="small text-muted fw-normal safety-stock-wh-label" style="font-size:10px;line-height:1.2;text-transform:none;letter-spacing:0;"></div>
                                                        </th>
                                                        <th style="min-width:140px; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing:.4px; padding: 12px 16px;">Lead Time (Hari)</th>
                                                        <th class="text-center" style="min-width:100px; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing:.4px; padding: 12px 16px;">Aksi</th>
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
                                    <a class="btn btn-primary btn-save">Tambah Produk</a>
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
                        <div class="row">
                            <div class="col-lg-4 col-md-3 col-12">
                                <select name="" id="relasi1" class="form-select"></select>
                            </div>
                            <div class="col-lg-1 col-md-1 col-12"> <h6 class="text-center pt-2"> - </h6> </div>
                            <div class="col-lg-4 col-md-3 col-12">
                                <select name="" id="relasi2" class="form-select"></select>
                            </div>
                            <div class="col-lg-3 col-md-5 col-12 mt-md-0 mt-3">
                                <button class="btn btn-primary w-100 btn-sm" id="btnAddRowRelasi">Tambah Relasi</button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered mb-2 mt-4">
                                <thead>
                                    <tr>
                                        <td>Name Unit 1<span class="text-danger">*</span></td>
                                        <td>Name Unit 2<span class="text-danger">*</span></td>
                                        <td>Aksi</td>
                                    </tr>
                                </thead>
                                <tbody class="tbRelasi" id="tbRelasi">
                                </tbody>
                            </table>
                        </div>
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
        var mode = "{{ $mode }}";
        var data = {!! json_encode($data) !!};
        var canAccessSafetyStock = false;
    </script>
    <script src="{{asset('Custom_js/Backoffice/Product/insertProduct.js')}}?v={{time()}}"></script>
@endsection