<?php $page = 'purchase-list'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content">
            <div class="page-header transfer">
                <div class="add-item d-flex">
                    <div class="page-title">
                        <h4>Purchase Order</h4>
                        <h6>Kelola PO Order Anda</h6>
                    </div>
                </div>
                <ul class="table-top-head">
                    <li>
                        <a data-bs-toggle="tooltip" data-bs-placement="top" title="Pdf"><img
                                src="{{ URL::asset('/build/img/icons/pdf.svg') }}" alt="img"></a>
                    </li>
                    <li>
                        <a data-bs-toggle="tooltip" data-bs-placement="top" title="Excel"><img
                                src="{{ URL::asset('/build/img/icons/excel.svg') }}" alt="img"></a>
                    </li>
                    <li>
                        <a data-bs-toggle="tooltip" data-bs-placement="top" title="Collapse" id="collapse-header"><i
                                data-feather="chevron-up" class="feather-chevron-up"></i></a>
                    </li>
                </ul>
                <div class="d-flex purchase-pg-btn">
                    <div class="page-btn" id="btn_add_purchase">
                        <a href="#" class="btn btn-added" data-bs-toggle="modal" data-bs-target="#add-purchase-new"><i
                                data-feather="plus-circle" class="me-2"></i>Buat PO baru</a>
                    </div>
                </div>
            </div>
            <!-- /product list -->
            <div class="card table-list-card">
                <div class="card-body">
                    <div class="table-top">
                        <div class="search-set">
                            <div class="search-input">
                                <a href="" class="btn btn-searchset"><i data-feather="search"
                                        class="feather-search"></i></a>
                            </div>
                        </div>
                        <div class="search-path">
                            <a class="btn btn-filter" id="filter_search">
                                <i data-feather="filter" class="filter-icon"></i>
                                <span><img src="{{ URL::asset('/build/img/icons/closes.svg') }}" alt="img"></span>
                            </a>
                        </div>
                        <div class="form-sort">
                            <i data-feather="sliders" class="info-img"></i>
                            <select class="select">
                                <option>Urutkan berdasarkan Tanggal</option>
                                <option>Terbaru</option>
                                <option>Terlama</option>
                            </select>
                        </div>
                    </div>
                    <!-- /Filter -->
                    <div class="card" id="filter_inputs">
                        <div class="card-body pb-0">
                            <div class="row">
                                <div class="col-lg-2 col-sm-6 col-12">
                                    <div class="input-blocks">
                                        <i data-feather="user" class="info-img"></i>
                                        <select class="select">
                                            <option>Pilih Nama Supplier</option>
                                            <option>Apex Computers</option>
                                            <option>Beats Headphones</option>
                                            <option>Dazzle Shoes</option>
                                            <option>Best Accessories</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-sm-6 col-12">
                                    <div class="input-blocks">
                                        <i data-feather="stop-circle" class="info-img"></i>
                                        <select class="select">
                                            <option>Pilih Status</option>
                                            <option>Diterima</option>
                                            <option>Dipesan</option>
                                            <option>Tertunda</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-sm-6 col-12">
                                    <div class="input-blocks">
                                        <i data-feather="file" class="info-img"></i>
                                        <select class="select">
                                            <option>Masukkan Referensi</option>
                                            <option>PT001</option>
                                            <option>PT002</option>
                                            <option>PT003</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-sm-6 col-12">
                                    <div class="input-blocks">
                                        <i class="fas fa-money-bill info-img"></i>
                                        <select class="select">
                                            <option>Pilih Status Pembayaran</option>
                                            <option>Lunas</option>
                                            <option>Sebagian</option>
                                            <option>Belum Dibayar</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-sm-6 col-12 ms-auto">
                                    <div class="input-blocks">
                                        <a class="btn btn-filters ms-auto"> <i data-feather="search"
                                                class="feather-search"></i> Cari </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /Filter -->
                    <div class="table-responsive product-list">
                        <table class="table datanew list" id="purchaseTable">
                            <thead>
                                <tr>
                                    <th>Nama Supplier</th>
                                    <th>Referensi</th>
                                    <th>Tanggal</th>
                                    <th>Total</th>
                                    <th>Dibayar</th>
                                    <th>Selisih Bayar</th>
                                    <th>Status</th>
                                    <th>Kasir</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="sales-list">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- /product list -->
        </div>
    </div>
@endsection
@section('custom_js')
    <script>
        var public = "{{ asset('') }}";
    </script>
    <script src="{{ asset('/Custom_js/Backoffice/Order/Purchase.js') }}?v={{ time() }}"></script>
@endsection
