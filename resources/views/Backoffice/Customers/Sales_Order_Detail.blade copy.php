<?php $page = 'sales_detail'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    Detail Penjualan
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
                                                    <label>Nama Armada</label>
                                                    <select id="so_name" class="form-control fill"></select>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="input-block mb-3">
                                                    <label>Tanggal</label>
                                                    <input type="date" class="form-control fill" id="so_date">
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="input-block mb-3">
                                                    <label>Status</label>
                                                    <select id="so_status" class="form-control fill">
                                                        <option value="created" checked>Dibuat</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="input-block mb-3">
                                                    <label>Biaya Pengiriman</label>
                                                    <div class="input-group mb-3">
                                                        <span class="input-group-text">Rp </span>
                                                        <input type="text" class="form-control fill" id="so_cost" value="0">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="input-block mb-3">
                                                    <label>Dibayar</label>
                                                    <div class="input-group mb-3">
                                                        <span class="input-group-text">Rp </span>
                                                        <input type="text" class="form-control fill" id="so_paid" value="0">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="input-block mb-3">
                                                    <label>Sisa Pembayaran</label>
                                                    <div class="input-group mb-3">
                                                        <span class="input-group-text">Rp </span>
                                                        <input type="text" class="form-control fill" id="so_remain" value="0">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 g-3 row">
                                            <div class="col-6">
                                                <div class="card p-0">
                                                    <div class="card-body">
                                                        <h6>Pengiriman</h6>
                                                        <p>Nama Pengirim: CV Maju Lancar</p>
                                                        <p>Alamat: Jl. Maju Jaya 2 no. 12</p>
                                                        <p>No. Telepon: 081273289917</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="card p-0">
                                                    <div class="card-body">
                                                        <h6>Detail Armada</h6>
                                                        <p>Nama: </p>
                                                        <p>Alamat: </p>
                                                        <p>No. Telepon: </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <table class="table table-center table-hover" id="tableProduct">
                                                <thead>
                                                    <th>Produk</th>
                                                    <th>Varian</th>
                                                    <th>SKU</th>
                                                    <th>Qty</th>
                                                    <th>Harga Satuan</th>
                                                    <th>Subtotal</th>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                        <div class="col-12 row pt-3">
                                            <div class="col-6"></div>
                                            <div class="col-6">
                                                <div class="d-flex justify-content-between">
                                                    <p>PPN</p>
                                                    <p>0</p>
                                                </div>
                                                <div class="d-flex justify-content-between">
                                                    <p>Diskon</p>
                                                    <p>0</p>
                                                </div>
                                                <div class="d-flex justify-content-between">
                                                    <p>Biaya Pengiriman</p>
                                                    <p>0</p>
                                                </div>
                                                <div class="d-flex justify-content-between">
                                                    <p>Total Keseluruhan</p>
                                                    <p>Rp 400000</p>
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
                                        Faktur & Pembayaran
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
                                                    <th>Tanggal Faktur</th>
                                                    <th>Jatuh Tempo</th>
                                                    <th>Kode Faktur</th>
                                                    <th>Status</th>
                                                    <th>Total</th>
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
                            <!-- Optional additional content -->
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
    </script>
    <script src="{{asset('Custom_js/Backoffice/Customers/Sales_Order_Detail.js')}}"></script>
@endsection