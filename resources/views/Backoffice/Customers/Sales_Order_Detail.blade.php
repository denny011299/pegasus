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
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#panelsStayOpen-collapseOne" aria-expanded="true"
                                        aria-controls="panelsStayOpen-collapseOne">
                                        Ringkasan
                                    </button>
                                </h2>
                                <div id="panelsStayOpen-collapseOne" class="accordion-collapse collapse show">
                                    <div class="accordion-body">
                                        
                                        <div class="row g-3">
                                            <div class="col-12 col-md-4">
                                                <div class="input-block">
                                                    <label>Nama Armada</label>
                                                    <select id="so_customer" class="form-control fill">
                                                        <option value="{{ $data['so_customer'] }}">{{ $data['customer_name'] }}</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-4">
                                                <div class="input-block mb-3">
                                                    <label>Tanggal</label>
                                                    <input type="date" class="form-control fill" id="so_date"
                                                        value="{{ $data['so_date'] }}">
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-4">
                                                <div class="input-block mb-3">
                                                    <label>Status</label>
                                                    <select id="so_status" class="form-control fill">
                                                        <option value="">Pilih Status</option>
                                                        <option value="1">Dibuat</option>
                                                        <option value="2">Diterima</option>
                                                        <option value="3">Pembayaran</option>
                                                        <option value="4">Selesai</option>
                                                        <option value="-1">Cancel</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-12 col-md-4">
                                                <div class="input-block mb-3">
                                                    <label>Biaya Pengiriman</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">Rp</span>
                                                        <input type="text" class="form-control fill" id="so_cost"
                                                            value="{{ $data['so_cost'] }}">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-4">
                                                <div class="input-block mb-3">
                                                    <label>Dibayar</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">Rp</span>
                                                        <input type="text" class="form-control fill" id="so_paid"
                                                            value="{{ $data['so_paid'] }}">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-4">
                                                <div class="input-block mb-3">
                                                    <label>Sisa Pembayaran</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">Rp</span>
                                                        <input type="text" class="form-control fill" id="so_difference"
                                                            value="{{ $data['so_difference'] }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row g-3 mt-2">
                                            <div class="col-12 col-md-6">
                                                <div class="card">
                                                    <div class="card-body">
                                                        <h6>Pengiriman</h6>
                                                        <p>Nama Pengirim: CV Maju Lancar</p>
                                                        <p>Alamat: Jl. Maju Jaya 2 no. 12</p>
                                                        <p>No. Telepon: 081273289917</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <div class="card">
                                                    <div class="card-body">
                                                        <h6>Detail Armada</h6>
                                                        <p>Nama Penerima: Budianto</p>
                                                        <p>Alamat: Jl. Makin Maju 5 no.18</p>
                                                        <p>No. Telepon: 081756378192</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12 mt-3">
                                            <div class="table-responsive">
                                                <table class="table table-center table-hover table-bordered w-100"
                                                    id="tableSupplies">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>SKU</th>
                                                            <th>Produk</th>
                                                            <th>Qty</th>
                                                            <th>Harga Satuan</th>
                                                            <th>Subtotal</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody></tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <div class="row pt-3">
                                            <div class="col-12 col-md-6"></div>
                                            <div class="col-12 col-md-6">
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
                                                <div class="d-flex justify-content-between border-top pt-2">
                                                    <b>Grand Total</b>
                                                    <b id="value_grand">0</b>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row pt-3">
                                            <div class="col-12 text-end">
                                                <button class="btn btn-primary save-qty">Simpan Perubahan</button>
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
                                            <div class="col-12 text-end">
                                                <a class="btn btn-primary btnAddDn"><i
                                                        class="fa fa-plus-circle me-2"></i>Tambah Catatan Pengiriman</a>
                                            </div>
                                        </div>
                                        <div class="table-responsive pb-5">
                                            <table class="table table-center table-hover w-100" id="tableDelivery">
                                                <thead>
                                                    <th>No. Catatan Pengiriman</th>
                                                    <th>Tanggal Pengiriman</th>
                                                    <th>Penerima</th>
                                                    <th>No. Telepon</th>
                                                    <th>Status</th>
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
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseThree" aria-expanded="false" aria-controls="panelsStayOpen-collapseThree">
                                        Faktur dan Pembayaran
                                    </button>
                                </h2>
                                <div id="panelsStayOpen-collapseThree" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <div class="row pb-3">
                                            <div class="col-12 text-end">
                                                <a class="btn btn-primary btnAddInv">
                                                    <i class="fa fa-plus-circle me-2"></i>Tambah Faktur
                                                </a>
                                            </div>
                                        </div>
                                        <div class="table-responsive pb-5">
                                            <table class="table table-center table-hover w-100" id="tableInvoice">
                                                <thead>
                                                    <th style="width:15%">Tgl. Pesanan</th>
                                                    <th style="width:15%">Tgl. Jatuh Tempo</th>
                                                    <th>No. Faktur</th>
                                                    <th>Total</th>
                                                    <th>Status</th>
                                                    <th class="no-sort text-center">Aksi</th>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                    </div>
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
    <script src="{{asset('Custom_js/Backoffice/Customers/Sales_Order_Detail.js')}}"></script>
@endsection