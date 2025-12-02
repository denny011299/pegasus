<?php $page = 'add-product'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content">
            @component('components.breadcrumb')
                @slot('title')
                    Tambah Produk
                @endslot
                @slot('li_1')
                    Input Produk Baru
                @endslot
            @endcomponent
            <form action="add-product">
                <div class="card">
                    <div class="card-body add-product pb-5">
                        <div class="accordion-card-one accordion" id="accordionExample">
                            <div class="accordion-item">
                                <div class="accordion-header" id="headingOne">
                                    <div class="accordion-button" data-bs-toggle="collapse" data-bs-target="#collapseOne"
                                        aria-controls="collapseOne">
                                        <div class="addproduct-icon">
                                            <h5><i data-feather="info" class="add-info"></i><span>Informasi Produk</span>
                                            </h5>
                                            <a href="javascript:void(0);"><i data-feather="chevron-down"
                                                    class="chevron-down-add"></i></a>
                                        </div>
                                    </div>
                                </div>
                                <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne"
                                    data-bs-parent="#accordionExample">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-lg-4 col-sm-6 col-12">
                                                <div class="mb-3 add-product">
                                                    <label class="form-label">Nama Produk</label>
                                                    <input type="text" class="form-control" id="nama_produk"
                                                        name="nama_produk">
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-sm-6 col-12">
                                                <div class="mb-3 add-product">
                                                    <div class="add-newplus">
                                                        <label class="form-label">Kategori</label>
                                                    </div>
                                                    <select class="select" id="kategori" name="kategori">
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-sm-6 col-12">
                                                <div class="mb-3 add-product">
                                                    <div class="add-newplus">
                                                        <label class="form-label">Merek</label>
                                                    </div>
                                                    <select class="select" id="merek" name="merek">
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-sm-6 col-12">
                                                <div class="mb-3 add-product">

                                                    <div class="add-newplus">
                                                        <label class="form-label">Satuan</label>
                                                    </div>
                                                    <select class="select" id="satuan" name="satuan[]" multiple>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <div class="input-blocks summer-description-box transfer mb-3">
                                                    <label>Deskripsi</label>
                                                    <textarea class="form-control h-100" rows="5" id="deskripsi" name="deskripsi"></textarea>
                                                    <p class="mt-1">Maksimal 60 Karakter</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-card-one accordion" id="accordionExample2">
                            <div class="accordion-item">
                                <div class="accordion-header" id="headingTwo">
                                    <div class="accordion-button" data-bs-toggle="collapse" data-bs-target="#collapseTwo"
                                        aria-controls="collapseTwo">
                                        <div class="text-editor add-list">
                                            <div class="addproduct-icon list icon">
                                                <h5><i data-feather="life-buoy" class="add-info"></i><span>Edit
                                                        Detail</span></h5>
                                                <a href="javascript:void(0);"><i data-feather="chevron-down"
                                                        class="chevron-down-add"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div id="collapseTwo" class="accordion-collapse collapse show" aria-labelledby="headingTwo"
                                    data-bs-parent="#accordionExample2">
                                    <div class="accordion-body">
                                        <div class="input-blocks add-products">
                                            <label class="d-block">Tipe Produk</label>
                                            <div class="single-pill-product">
                                                <ul class="nav nav-pills" id="pills-tab1" role="tablist">
                                                    <li class="nav-item" role="presentation">
                                                        <span class="custom_radio me-4 mb-0 active" id="pills-home-tab"
                                                            data-bs-toggle="pill" data-bs-target="#pills-home"
                                                            role="tab" aria-controls="pills-home" aria-selected="true">
                                                            <input type="radio" class="form-control" name="product_type"
                                                                value="tunggal" id="tunggal">
                                                            <span class="checkmark"></span> Produk Tunggal</span>
                                                    </li>
                                                    <li class="nav-item" role="presentation">
                                                        <span class="custom_radio me-2 mb-0" id="pills-profile-tab"
                                                            data-bs-toggle="pill" data-bs-target="#pills-profile"
                                                            role="tab" aria-controls="pills-profile"
                                                            aria-selected="false">
                                                            <input type="radio" class="form-control"
                                                                name="product_type" value="bervarian" id="bervarian">
                                                            <span class="checkmark"></span> Produk Bervarian</span>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="tab-content" id="pills-tabContent">
                                            <div class="tab-pane fade show active" id="pills-home" role="tabpanel"
                                                aria-labelledby="pills-home-tab">
                                                <div class="row">
                                                    <div class="col-lg-4 col-sm-6 col-12">
                                                        <div class="input-blocks add-product">
                                                            <label>Harga</label>
                                                            <div class="input-group">
                                                                <span class="input-group-text">Rp</span>
                                                                <input type="text" class="form-control"
                                                                    placeholder="0" id="harga_single">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6 col-sm-6 col-12">
                                                        <div class="input-blocks add-product">
                                                            <label>Harga Grosir</label>
                                                            <button type="button" class="btn btn-primary"
                                                                id="btn-atur-harga-grosir" style="border-radius: 10px">
                                                                <i data-feather="tag"></i> Atur Harga Grosir
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-4 col-sm-6 col-12">
                                                        <div class="input-blocks add-product list">
                                                            <label>SKU</label>
                                                            <input type="text" class="form-control list"
                                                                id="sku_single">
                                                            <button type="submit" class="btn btn-primaryadd"
                                                                id="buat-sku" data-fieldtocomplete="sku_single">
                                                                Buat SKU
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-4 col-sm-6 col-8">
                                                        <div class="input-blocks add-product list">
                                                            <label>Kode Barcode</label>
                                                            <input type="text" class="form-control list"
                                                                id="barcode_single">
                                                            <button type="submit" class="btn btn-primaryadd"
                                                                id="buat-barcode" data-fieldtocomplete="barcode_single">
                                                                Buat Barcode
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="accordion-card-one accordion" id="accordionExample3">
                                                    <div class="accordion-item">
                                                        <div class="accordion-header" id="headingThree">
                                                            <div class="accordion-button" data-bs-toggle="collapse"
                                                                data-bs-target="#collapseThree"
                                                                aria-controls="collapseThree">
                                                                <div class="addproduct-icon list">
                                                                    <h5><i data-feather="image"
                                                                            class="add-info"></i><span>Gambar</span></h5>
                                                                    <a href="javascript:void(0);"><i
                                                                            data-feather="chevron-down"
                                                                            class="chevron-down-add"></i></a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div id="collapseThree" class="accordion-collapse collapse show"
                                                            aria-labelledby="headingThree"
                                                            data-bs-parent="#accordionExample3">
                                                            <div class="accordion-body">
                                                                <div class="text-editor add-list add">
                                                                    <div class="col-lg-12">
                                                                        <div class="add-choosen">
                                                                            <div class="input-blocks">
                                                                                <div class="image-upload">
                                                                                    <input type="file">
                                                                                    <div class="image-uploads">
                                                                                        <i data-feather="plus-circle"
                                                                                            class="plus-down-add me-0"></i>
                                                                                        <h4>Ganti Gambar</h4>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="phone-img">
                                                                                <img src="{{ URL::asset('/build/img/products/phone-add-2.png') }}"
                                                                                    alt="gambar">
                                                                                <a href="javascript:void(0);"><i
                                                                                        data-feather="x"
                                                                                        class="x-square-add remove-product"></i></a>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="accordion-card-one accordion" id="accordionExample4">
                                                    <div class="accordion-item">
                                                        <div class="accordion-header" id="headingFour">
                                                            <div class="accordion-button" data-bs-toggle="collapse"
                                                                data-bs-target="#collapseFour"
                                                                aria-controls="collapseFour">
                                                                <div class="text-editor add-list">
                                                                    <div class="addproduct-icon list">
                                                                        <h5><i data-feather="list"
                                                                                class="add-info"></i><span>Kolom
                                                                                Kustom</span>
                                                                        </h5>
                                                                        <a href="javascript:void(0);"><i
                                                                                data-feather="chevron-down"
                                                                                class="chevron-down-add"></i></a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div id="collapseFour" class="accordion-collapse collapse show"
                                                            aria-labelledby="headingFour"
                                                            data-bs-parent="#accordionExample4">
                                                            <div class="accordion-body">
                                                                <div class="text-editor add-list add">
                                                                    <div class="custom-filed">
                                                                        <div class="input-block add-lists">
                                                                            <label class="checkboxs">
                                                                                <input type="checkbox"
                                                                                    id="checkbox-garansi">
                                                                                <span class="checkmarks"></span>Garansi
                                                                            </label>
                                                                            <label class="checkboxs">
                                                                                <input type="checkbox"
                                                                                    id="checkbox-peringatan">
                                                                                <span class="checkmarks"></span>Peringatan
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row" id="field-garansi-peringatan"
                                                                        style="display: none;">
                                                                        <div class="col-lg-4 col-sm-4 col-12"
                                                                            id="field-garansi" style="display: none;">
                                                                            <div class="input-blocks add-product">
                                                                                <label>Garansi</label>
                                                                                <input type="text" id="garansi"
                                                                                    class="form-control"
                                                                                    style="display: inline-block; width: auto;">
                                                                                <span style="margin-left: 8px;">Hari</span>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-lg-4 col-sm-4 col-12"
                                                                            id="field-peringatan" style="display: none;">
                                                                            <div class="input-blocks add-product">
                                                                                <label>Peringatan Kuantitas</label>
                                                                                <div
                                                                                    class="d-flex align-items-center gap-2">
                                                                                    <input type="number"
                                                                                        class="form-control"
                                                                                        id="peringatan_kuantitas">
                                                                                    <select class="form-control"
                                                                                        id="peringatan_satuan">
                                                                                    </select>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="tab-pane fade" id="pills-profile" role="tabpanel"
                                                aria-labelledby="pills-profile-tab">
                                                <div class="row select-color-add">
                                                    <div class="col-lg-6 col-sm-6 col-12">
                                                        <div class="input-blocks add-product">
                                                            <label>Tambah Varian Baru</label>
                                                            <div class="row">
                                                                <div class="col-lg-10 col-sm-10 col-10">
                                                                    <input type="text" class="form-control"
                                                                        placeholder="Nama Varian" id="newVariant">
                                                                </div>
                                                                <div class="col-lg-2 col-sm-2 col-2 ps-0">
                                                                    <div class="add-icon tab">
                                                                        <a class="btn" id="addVariant"><i
                                                                                class="feather feather-plus-circle"></i></a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="table-responsive">
                                                    <table class="table" id="tableProductVariants">
                                                        <thead>
                                                            <tr>
                                                                <th>Nama Varian</th>
                                                                <th>SKU</th>
                                                                <th>Barcode</th>
                                                                <th>Harga Eceran</th>
                                                                <th class="no-sort">Aksi</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-12">
                    <div class="btn-addproduct mb-4">
                        <button type="button" class="btn btn-cancel me-2">Batal</button>
                        <button type="button" class="btn btn-submit" id="saveProduct">Simpan Produk</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
@section('custom_js')
    <script>
        var public = "{{ asset('') }}";
    </script>
    <script src="{{ asset('/Custom_js/Backoffice/Product/AddProduct.js') }}?v={{ time() }}"></script>
@endsection
