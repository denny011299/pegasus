<?php $page = 'pos-session'; ?>

@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content">
            @component('components.breadcrumb')
                @slot('title')
                    POS Session
                @endslot
                @slot('li_1')
                    Manajemen Sesi POS
                @endslot
                @slot('li_2')
                    {{ url('admin/POS-Session') }}
                @endslot
                @slot('li_3')
                    POS Session
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
                                <div class="col-lg-3 col-sm-6 col-12">
                                    <div class="input-blocks">
                                        <i data-feather="calendar" class="info-img"></i>
                                        <div class="input-groupicon">
                                            <input type="text" class="datetimepicker form-control"
                                                placeholder="Pilih Tanggal">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-sm-6 col-12">
                                    <div class="input-blocks">
                                        <i data-feather="stop-circle" class="info-img"></i>
                                        <select class="select">
                                            <option>Pilih Status</option>
                                            <option>Aktif</option>
                                            <option>Selesai</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-sm-6 col-12">
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
                        <table class="table datanew" id="pos-session-table">
                            <thead>
                                <tr>
                                    <th>Kasir</th>
                                    <th>Store</th>
                                    <th>Tanggal Mulai</th>
                                    <th>Tanggal Selesai</th>
                                    <th>Start Cash Drawer</th>
                                    <th>End Cash Drawer</th>
                                    <th>Total Penjualan</th>
                                    <th>Status</th>
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
    <input type="hidden" name="" id="status">
@endsection
@section('custom_js')
    <script>
        var public = "{{ asset('') }}";
    </script>
    <script src="{{ asset('/Custom_js/Backoffice/Order/Session.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('/Custom_js/Backoffice/Printer.js') }}?v={{ time() }}"></script>
@endsection
