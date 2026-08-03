<?php $page = 'stock_alert'; ?>
@extends('layout.mainlayout')
@section('custom_css')
    <style>
        .stock-alert-table {
            width: 100% !important;
            min-width: 960px;
            table-layout: fixed;
        }
        .stock-alert-table th,
        .stock-alert-table td {
            box-sizing: border-box !important;
            white-space: normal !important;
            overflow-wrap: anywhere;
            vertical-align: middle;
        }
        .stock-alert-table th:nth-child(1),
        .stock-alert-table td:nth-child(1) {
            width: 30% !important;
        }
        .stock-alert-table th:nth-child(2),
        .stock-alert-table td:nth-child(2) {
            width: 15% !important;
        }
        .stock-alert-table th:nth-child(3),
        .stock-alert-table td:nth-child(3) {
            width: 15% !important;
        }
        .stock-alert-table th:nth-child(4),
        .stock-alert-table td:nth-child(4) {
            width: 25% !important;
        }
        .stock-alert-table th:nth-child(5),
        .stock-alert-table td:nth-child(5) {
            width: 15% !important;
        }
        .stock-alert-table td.dataTables_empty {
            width: auto !important;
            text-align: center;
        }
    </style>
@endsection
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            <div class="d-flex flex-wrap justify-content-between m-0 p-0 mb-3">
                @component('components.page-header')
                    @slot('title')
                        Peringatan Stok Produk
                        <div class="small text-muted fw-normal mt-1" id="stock-alert-wh-label" style="font-size:13px;"></div>
                    @endslot
                @endcomponent
                <ul class="nav nav-pills navtab-bg d-flex flex-nowrap mb-md-0 mb-3" style="z-index: 10; position: relative;">
                    <li class="nav-item">
                        <a href="#low" data-bs-toggle="tab" class="nav-link active text-nowrap" style="border-radius: 10px">
                            Stok Rendah <span class="badge text-bg-danger" id="total_low">0</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#out" data-bs-toggle="tab" class="nav-link text-nowrap" style="border-radius: 10px">
                            Stok Habis <span class="badge text-bg-danger" id="total_out">0</span>
                        </a>
                    </li>
                </ul>
            </div>
            <!-- /Page Header -->

            <!-- Search Filter -->
            @component('components.search-filter')
            @endcomponent
            <!-- /Search Filter -->

            <!-- Table -->
            <div class="row" style="margin-top: -6vh">
                <div class="col-sm-12">
                    <div class=" card-table">
                        <div class="card-body">
							<div class="tab-content">
								<div class="tab-pane show active" id="low">
									<div class="table-responsive">
                                        <table class="table table-center table-hover stock-alert-table" id="tableStockAlertLow">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Nama Produk</th>
                                                    <th>Kategori</th>
                                                    <th>SKU</th>
                                                    <th>Stok Minimum Rekomendasi</th>
                                                    <th>Pemesanan Min.</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                
                                            </tbody>
                                        </table>
                                    </div>
								</div>
								<div class="tab-pane" id="out">
									<div class="table-responsive">
                                        <table class="table table-center table-hover stock-alert-table" id="tableStockAlertOut">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Nama Produk</th>
                                                    <th>Kategori</th>
                                                    <th>SKU</th>
                                                    <th>Stok Minimum Rekomendasi</th>
                                                    <th>Pemesanan Min.</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                
                                            </tbody>
                                        </table>
                                    </div>
								</div>
							</div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Table -->

        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection

@section('custom_js')
    <script>
        var public = "{{ asset('') }}";    
    </script>
    <script src="{{asset('Custom_js/Backoffice/Inventory/Stock_Alert.js')}}?v=6"></script>
@endsection