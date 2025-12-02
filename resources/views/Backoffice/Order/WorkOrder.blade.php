<?php $page = 'sales-list'; ?>
@extends('layout.mainlayout')
@section('content')
    <style>
        .badge-lineinfo {
            padding: 5px 10px;
            border-radius: 8px;
            border: 1px solid #6c757d;
            color: #6c757d;
            border-radius: 8px;
            padding: 4px 10px;
            font-size: 14px;

        }
    </style>
    <div class="page-wrapper">
        <div class="content">
            @component('components.breadcrumb')
                @slot('title')
                    Surat Perintah Kerja
                @endslot
                @slot('li_1')
                    Kelola Surat Perintah Kerja
                @endslot
                @slot('li_2')
                    Tambah SPK Baru
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
                                <option>07 09 23</option>
                                <option>21 09 23</option>
                            </select>
                        </div>
                    </div>
                    <!-- /Filter -->
                    <div class="card" id="filter_inputs">
                        <div class="card-body pb-0">
                            <div class="row">
                                <div class="col-lg-3 col-sm-6 col-12">
                                    <div class="input-blocks">
                                        <i data-feather="user" class="info-img"></i>
                                        <select class="select">
                                            <option>Pilih Nama Customer</option>
                                            <option>Macbook pro</option>
                                            <option>Orange</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-sm-6 col-12">
                                    <div class="input-blocks">
                                        <i data-feather="stop-circle" class="info-img"></i>
                                        <select class="select">
                                            <option>Pilih Status</option>
                                            <option>Computers</option>
                                            <option>Fruits</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-sm-6 col-12">
                                    <div class="input-blocks">
                                        <i data-feather="file-text" class="info-img"></i>
                                        <input type="text" placeholder="Masukkan Referensi" class="form-control">
                                    </div>
                                </div>
                                <div class="col-lg-3 col-sm-6 col-12">
                                    <div class="input-blocks">
                                        <i data-feather="stop-circle" class="info-img"></i>
                                        <select class="select">
                                            <option>Pilih Status Pembayaran</option>
                                            <option>Computers</option>
                                            <option>Fruits</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-sm-6 col-12">
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
                        <table class="table " id="tableWorkOrder">
                            <thead>
                                <tr>
                                    <th>Store</th>
                                    <th>Nama Customer</th>
                                    <th>Tanggal</th>
                                    <th>Total Biaya</th>
                                    <th>Pekerja</th>
                                    <th>Status Pengerjaan</th>
                                    <th class="text-center">Aksi</th>
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
    <script src="{{ asset('/Custom_js/Backoffice/Order/WorkOrder.js') }}?v={{ time() }}"></script>
@endsection
