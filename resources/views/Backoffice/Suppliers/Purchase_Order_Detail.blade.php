<?php $page = 'purchase_detail'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    Detail Pembelian
                @endslot
            @endcomponent
            <!-- /Page Header -->

            <!-- Search Filter -->
            @component('components.search-filter')
            @endcomponent
            <!-- /Search Filter -->

            <!-- Table -->
            <div class="row">
                <div class="col-sm-12">
                    <div class="card-body">
                        <div class="accordion" id="accordionPanelsStayOpenExample">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseOne" aria-expanded="true" aria-controls="panelsStayOpen-collapseOne">
                                        Ringkasan
                                    </button>
                                </h2>
                                <div id="panelsStayOpen-collapseOne" class="accordion-collapse collapse show">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-4">
                                                <div class="input-block">
                                                    <label>Nama Supplier</label>
                                                    <select id="po_supplier" class="form-control fill">
                                                        <option value="{{$data["po_supplier"]}}">{{$data["po_supplier_name"]}}</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="input-block mb-3">
                                                    <label>Tanggal</label>
                                                    <input type="date" class="form-control fill" id="po_date" value="{{$data["po_date"]}}">
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="input-block mb-3">
                                                    <label>Status</label>
                                                    <select id="po_status" class="form-control fill">
                                                        <option value="" checked>Pilih Status</option>
                                                        <option value="1" checked>Dibuat</option>
                                                        <option value="2" checked>Diterima</option>
                                                        <option value="3" checked>Pembayaran</option>
                                                        <option value="4" checked>Selesai</option>
                                                        <option value="-1" checked>Cancel</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="input-block mb-3">
                                                    <label>Total</label>
                                                    <div class="input-group mb-3">
                                                        <span class="input-group-text">Rp </span>
                                                        <input type="text" class="form-control fill" id="po_total" value="{{$data["po_total"]}}">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="input-block mb-3">
                                                    <label>Dibayar</label>
                                                    <div class="input-group mb-3">
                                                        <span class="input-group-text">Rp </span>
                                                        <input type="text" class="form-control fill" id="po_paid" value="0">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="input-block mb-3">
                                                    <label>Sisa Pembayaran</label>
                                                    <div class="input-group mb-3">
                                                        <span class="input-group-text">Rp </span>
                                                        <input type="text" class="form-control fill" id="po_remain" value="0">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 g-3 row">
                                            <div class="col-6">
                                                <div class="card p-0">
                                                    <div class="card-body">
                                                        <h6>Pengirim</h6>
                                                        <p>Nama Pengirim: CV Maju Lancar</p>
                                                        <p>Alamat: Jl. Maju Jaya 2 no. 12</p>
                                                        <p>No. Telepon: 081273289917</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="card p-0">
                                                    <div class="card-body">
                                                        <h6>Penerima</h6>
                                                        <p>Nama Penerima: Budianto</p>
                                                        <p>Alamat: Jl. Makin Maju 5 no.18</p>
                                                        <p>No. Telepon: 081756378192</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <table class="table table-center table-hover" id="tableProduct">
                                                <thead>
                                                    <th>SKU</th>
                                                    <th>Produk</th>
                                                    <th>Qty</th>
                                                    <th>Harga Beli (Rp)</th>
                                                    <th>Subtotal</th>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                        <div class="col-12 row pt-3">
                                            <div class="col-6"></div>
                                            <div class="col-6">
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
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseTwo" aria-expanded="false" aria-controls="panelsStayOpen-collapseTwo">
                                        Catatan Pengiriman
                                    </button>
                                </h2>
                                <div id="panelsStayOpen-collapseTwo" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <div class="row pb-3">
                                            <div class="col-8"></div>
                                            <div class="col-4 text-end">
                                                <a class="btn btn-primary btnAddDn"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Tambah Catatan Pengiriman</a>
                                            </div>
                                        </div>
                                        <div class="col-12 pb-5">
                                            <table class="table table-center table-hover" id="tableDelivery">
                                                <thead>
                                                    <th>No. Catatan Pengiriman</th>
                                                    <th>Tanggal Pengiriman</th>
                                                    <th>Penerima</th>
                                                    <th>Alamat</th>
                                                    <th>No. Telepon</th>
                                                    <th>Status</th>
                                                    <th class="no-sort">Aksi</th>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseThree" aria-expanded="false" aria-controls="panelsStayOpen-collapseThree">
                                        Faktur dan Pembayaran
                                    </button>
                                </h2>
                                <div id="panelsStayOpen-collapseThree" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <div class="row pb-3">
                                            <div class="col-8"></div>
                                            <div class="col-4 text-end">
                                                <a class="btn btn-primary btnAddInv"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Tambah Faktur</a>
                                            </div>
                                        </div>
                                        <div class="col-12 pb-5">
                                            <table class="table table-center table-hover" id="tableInvoice">
                                                <thead>
                                                    <th style="width:15%">Tgl. Pesanan</th>
                                                    <th style="width:15%">Tgl. Jatuh Tempo</th>
                                                    <th>No. Faktur</th>
                                                    <th>Status</th>
                                                    <th>Total</th>
                                                    <th class="no-sort text-center">Aksi</th>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseFour" aria-expanded="false" aria-controls="panelsStayOpen-collapseFour">
                                        Penerimaan Barang
                                    </button>
                                </h2>
                                <div id="panelsStayOpen-collapseFour" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <div class="row pb-3">
                                            <div class="col-8"></div>
                                            <div class="col-4 text-end">
                                                <a class="btn btn-primary btnAddRcp"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Tambah Penerimaan Barang</a>
                                            </div>
                                        </div>
                                        <div class="col-12 pb-5">
                                            <table class="table table-center table-hover" id="tableReceipt">
                                                <thead>
                                                    <th>Tanggal Terima</th>
                                                    <th>Referensi Catatan Pengiriman</th>
                                                    <th>Penerima</th>
                                                    <th>Status</th>
                                                    <th class="no-sort">Aksi</th>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class=" card-table">
                        <div class="card-body">
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection

@section('custom_js')
    <script>
        var public = "{{ asset('') }}";    
        var data = @json($data);
    </script>
    <script src="{{asset('Custom_js/Backoffice/Suppliers/Purchase_Order_Detail.js')}}"></script>
@endsection