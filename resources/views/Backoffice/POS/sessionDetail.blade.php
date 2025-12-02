<?php $page = 'pos-session-detail'; ?>

@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content">
            @component('components.breadcrumb')
                @slot('title')
                    Detail Sesi POS
                @endslot
                @slot('li_1')
                @endslot
                @slot('li_2')
                    {{ url('admin/POS-Session') }}
                @endslot
                @slot('li_3')
                    POS Session
                @endslot
            @endcomponent

            <div class="row">
                <!-- Session Info Card -->
                <div class="col-xl-6 col-lg-12 col-sm-12 col-12 d-flex">
                    <div class="card flex-fill default-cover w-100 mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="card-title mb-0">Informasi Sesi</h4>
                            <div class="dropdown">
                                {{-- <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false" class="dropset">
                                    <i class="fa fa-ellipsis-v"></i>
                                </a> --}}
                                {{-- <ul class="dropdown-menu">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item" data-bs-toggle="modal"
                                            data-bs-target="#edit-session">
                                            <i data-feather="edit" class="info-img"></i>Edit Sesi
                                        </a>
                                    </li>
                                </ul> --}}
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="total-order-info mb-2">
                                        <h6 class="mb-1">ID Sesi</h6>
                                        <p class="id-sesi"></p>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="total-order-info mb-2">
                                        <h6 class="mb-1">Kasir</h6>
                                        <p class="kasir"></p>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="total-order-info mb-2">
                                        <h6 class="mb-1">Tanggal Mulai</h6>
                                        <p class="tanggal-mulai"></p>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="total-order-info mb-2">
                                        <h6 class="mb-1">Tanggal Selesai</h6>
                                        <p class="tanggal-selesai"></p>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="total-order-info mb-2">
                                        <h6 class="mb-1">Status</h6>
                                        <p id="status"></p>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="total-order-info mb-2">
                                        <h6 class="mb-1">Durasi Sesi</h6>
                                        <p id="durasi-sesi"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Financial Summary Card -->
                <div class="col-xl-6 col-lg-12 col-sm-12 col-12 d-flex">
                    <div class="card flex-fill default-cover w-100 mb-4">
                        <div class="card-header">
                            <h4 class="card-title mb-0">Ringkasan Keuangan</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="total-order-info mb-2">
                                        <h6 class="mb-1">Cash Drawer Awal</h6>
                                        <p id="cash-drawer-awal" class="text-success"></p>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="total-order-info mb-2">
                                        <h6 class="mb-1">Cash Drawer Akhir</h6>
                                        <p id="cash-drawer-akhir" class="text-success"></p>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="total-order-info mb-2">
                                        <h6 class="mb-1">Total Penjualan</h6>
                                        <p id="total-penjualan"></p>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="total-order-info mb-2">
                                        <h6 class="mb-1">Total Transaksi</h6>
                                        <p id="total-transaksi"></p>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="total-order-info mb-2">
                                        <h6 class="mb-1">Pembayaran Cash</h6>
                                        <p id="pembayaran-tunai" class="text-info"></p>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="total-order-info mb-2">
                                        <h6 class="mb-1">Pembayaran Debit</h6>
                                        <p id="pembayaran-debit" class="text-info"></p>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="total-order-info mb-2">
                                        <h6 class="mb-1">Pembayaran Credit Card</h6>
                                        <p id="pembayaran-kredit" class="text-info"></p>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="total-order-info mb-2">
                                        <h6 class="mb-1">Pembayaran QRIS</h6>
                                        <p id="pembayaran-qris" class="text-info"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transaction List -->
            <div class="card table-list-card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Daftar Transaksi</h4>
                </div>
                <div class="card-body">
                    <div class="table-top">
                        <div class="search-set">
                            <div class="search-input">
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
                                <option>Urutkan berdasarkan Waktu</option>
                                <option>Terbaru</option>
                                <option>Terlama</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive" id="history-table-container">
                        <table class="table" id="history-table">
                            <thead>
                                <tr>
                                    <th>Invoice ID</th>
                                    <th>Tanggal</th>
                                    <th>Kasir</th>
                                    <th>Customer</th>
                                    <th>Total</th>
                                    <th>Metode Bayar</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be populated here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Payment Methods Chart -->
            <div class="row">
                <!-- Cash Drawer Records Card -->
                <div class="col-xl-6 col-lg-6 col-sm-6 col-6 d-flex">
                    <div class="card flex-fill default-cover w-100 mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="card-title mb-0">Pencatatan Aktifitas Cash Drawer</h4>
                            {{-- <div class="page-header ">
                                <div class="page-btn ms-auto">
                                    <a href="#" class="btn btn-added" data-bs-toggle="modal"
                                        data-bs-target="#add-expense"><i data-feather="plus-circle"
                                            class="me-2"></i>Tambah Aktifitas</a>
                                </div>
                            </div> --}}
                        </div>
                        <div class="card-body">
                            <div class="cash-drawer-list" id="cash-drawer-list">
                                <!-- Cash drawer activities will be populated here -->
                                <div class="text-center p-4" id="no-cash-activities" style="display: none;">
                                    <p class="text-muted">Belum ada aktivitas cash drawer</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6 col-lg-6 col-sm-6 col-6 d-flex">
                    <div class="card flex-fill default-cover w-100 mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="card-title mb-0">Pencatatan Pending Order Tidak Terselesaikan</h4>
                            {{-- <div class="page-header ">
                                <div class="page-btn ms-auto">
                                    <a href="#" class="btn btn-added" data-bs-toggle="modal"
                                        data-bs-target="#add-expense"><i data-feather="plus-circle"
                                            class="me-2"></i>Tambah Aktifitas</a>
                                </div>
                            </div> --}}
                        </div>
                        <div class="card-body">
                            <div class="pending-list" id="pending-list">
                                <!-- Cash drawer activities will be populated here -->
                                <div class="text-center p-4" id="no-cash-activities" style="display: none;">
                                    <p class="text-muted">Belum ada Pending Order</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('custom_js')
    <script>
        var public = "{{ asset('') }}";
    </script>
    <script src="{{ asset('/Custom_js/Backoffice/Order/SessionDetail.js') }}?v={{ time() }}"></script>
@endsection
