<?php $page = 'pos-transaction'; ?>

@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content">
            @component('components.breadcrumb')
                @slot('title')
                    POS Transaction
                @endslot
                @slot('li_1')
                    Transaksi POS
                @endslot
                @slot('li_2')
                    {{ url('admin/POS-Transaction') }}
                @endslot
                @slot('li_3')
                    POS Transaction
                @endslot
            @endcomponent

            <!-- /product list -->
            <div class="card table-list-card">
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
            <!-- /product list -->
        </div>
    </div>
@endsection
@section('custom_js')
    <script>
        var public = "{{ asset('') }}";
    </script>
    <script src="{{ asset('/Custom_js/Backoffice/Order/Transaction.js') }}?v={{ time() }}"></script>
@endsection
