<?php $page = 'stock-transfer'; ?>
@extends('layout.mainlayout')
@section('content')
    <style>
        html {
            scroll-behavior: smooth;
        }
    </style>
    <div class="page-wrapper">
        <div class="content">
            @component('components.breadcrumb')
                @slot('title')
                    Transfer Stok
                @endslot
                @slot('li_1')
                    Kelola transfer stok Anda
                @endslot
                @slot('li_2')
                    Tambah Baru
                @endslot
                @slot('li_3')
                    Import Transfer
                @endslot
            @endcomponent

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
                            <div class="d-flex align-items-center">
                                <a class="btn btn-filter" id="filter_search">
                                    <i data-feather="filter" class="filter-icon"></i>
                                    <span><img src="{{ URL::asset('/build/img/icons/closes.svg') }}" alt="img"></span>
                                </a>
                            </div>
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
                                <div class="col-lg-3 col-sm-6 col-12">
                                    <div class="input-blocks">
                                        <i data-feather="archive" class="info-img"></i>
                                        <select class="select">
                                            <option>Gudang Asal</option>
                                            <option>Lobar Handy</option>
                                            <option>Quaint Warehouse</option>
                                            <option>Traditional Warehouse</option>
                                            <option>Cool Warehouse</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-sm-6 col-12">
                                    <div class="input-blocks">
                                        <i data-feather="user" class="info-img"></i>
                                        <select class="select">
                                            <option>Gudang Tujuan</option>
                                            <option>Selosy</option>
                                            <option>Logerro</option>
                                            <option>Vesloo</option>
                                            <option>Crompy</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-sm-6 col-12">
                                    <div class="input-blocks">
                                        <i data-feather="calendar" class="info-img"></i>
                                        <div class="input-groupicon">
                                            <input type="text" class="datetimepicker" placeholder="Pilih Tanggal">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-sm-6 col-12 ms-auto">
                                    <div class="input-blocks">
                                        <a class="btn btn-filters ms-auto"> <i data-feather="search"
                                                class="feather-search"></i> Cari </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /Filter -->
                    <div class="table-responsive">
                        <table class="table datanew" id="stock-transfer-table">
                            <thead>
                                <tr>
                                    <th>Asal</th>
                                    <th>Tujuan</th>
                                    <th>Jumlah Produk</th>
                                    <th>Kode Ref</th>
                                    <th>Tanggal</th>
                                    <th class="no-sort">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
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
    <script src="{{ asset('/Custom_js/Backoffice/Product/StockTransfer.js') }}?v={{ time() }}"></script>
@endsection
