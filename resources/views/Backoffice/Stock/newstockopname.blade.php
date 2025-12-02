<?php $page = 'salesDetail'; ?>

@extends('layout.mainlayout')
@section('content')
    <style>
        .is-invalid {
            border-color: #dc3545!important;
        }
    </style>
    <div class="page-wrapper">
        <div class="content">
            @component('components.breadcrumb')
                @slot('title')
                    Stock Opname
                @endslot
                @slot('li_1')
                    Input Stock Opname Baru
                @endslot
            @endcomponent
            <form action="add-product">
                <div class="card pb-5">
                    <div class="card-body add-product pb-0">
                        <div class="accordion-card-one accordion mt-4" id="accordionExample1">
                            <div class="accordion-item">
                                <div class="accordion-header" id="headingOne">
                                    <div class="accordion-button" data-bs-toggle="collapse" data-bs-target="#collapseOne"
                                        aria-controls="collapseOne">
                                        <div class="text-editor add-list">
                                            <div class="addproduct-icon list icon">
                                                <h5><i data-feather="life-buoy" class="add-info"></i><span>Detail Stock
                                                        Opname</span>
                                                </h5>
                                                <a href="javascript:void(0);"><i data-feather="chevron-down"
                                                        class="chevron-down-add"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne"
                                    data-bs-parent="#accordionExample1">
                                    <div class="accordion-body">
                                        <div class="row mb-5">
                                            <div class="col-lg-4">
                                                <div class="input-blocks">
                                                    <label>Penaggung jawab</label>
                                                    <select class="fill select2" id="penanggung-jawab"></select>
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="input-blocks">
                                                    <label>Tanggal</label>
                                                    <div class="input-groupicon calender-input">
                                                        <i data-feather="calendar" class="info-img"></i>
                                                        <input type="text" class="datetimepicker" placeholder="Pilih"
                                                            id="tanggal">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="add-newplus">
                                                    <label class="form-label">Kategori</label>
                                                </div>
                                                <select class="fill select" id="kategori"></select>
                                            </div>
                                            <div class="col-lg-12">
                                                <div class="input-blocks">
                                                    <label>Catatan</label>
                                                    <textarea class="form-control" placeholder="Masukkan catatan" id="catatan"></textarea>
                                                </div>
                                                <div class="ms-auto btnCustom d-flex justify-content-end">
                                                    <button type="button" class="btn btn-submit"
                                                        id="mulai-stock-opname">Mulai Stock
                                                        Opname</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-3">
                                <div class="mb-3">
                                    <label class="form-label">Barcode Number</label>
                                    <input type="text" class="form-control" value="" id="input_barcode">
                                </div>
                            </div>
                            <div class="col-2">
                                <div class="mb-3">
                                    <label class="form-label">Qty</label>
                                    <input type="number" value="1" min="0" class="form-control fill"
                                        value="" id="input_qty">
                                </div>
                            </div>
                            <div class="col-2 pt-3">
                                <div class=" mt-3 btnCustom">
                                    <button type="button" class="btn btn-submit" id="btn-add-stock">Add Stock</button>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table" id="stock-opname-table">
                                <thead>
                                    <tr>
                                        <th>Barcode</th>
                                        <th>Nama Produk</th>
                                        <th>Varian</th>
                                        <th>SKU</th>
                                        <th class="col-stok">Jumlah Stok</th>
                                        <th>Jumlah Nyata</th>
                                        <th class="col-selisih">Selisih</th>
                                        <th style="width: 150px;">Catatan</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                    </div>
                    <div class="ms-auto btnCustom mt-5 me-3">

                        <button type="button" class="btn btn-cancel me-2" id="download-list-produk">Download List
                            Produk</button>


                        <button type="button" class="btn btn-submit" id="simpan-hasil-opname">Simpan Hasil Opname</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <form action="" id="form-template" method="post">
        @csrf
        <input type="hidden" name="items" id="items" value="">
        <input type="hidden" name="store" id="store" value="">
    </form>
@endsection
@section('custom_js')
    <script>
        var public = "{{ asset('') }}";
    </script>
    <script src="{{ asset('/Custom_js/Backoffice/Product/NewStockOpname.js') }}?v={{ time() }}"></script>
@endsection
