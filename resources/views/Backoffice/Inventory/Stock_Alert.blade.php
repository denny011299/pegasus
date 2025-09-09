<?php $page = 'stock_alert'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            <div class="d-flex justify-content-between m-0 p-0">
                @component('components.page-header')
                        @slot('title')
                            Peringatan Stok
                        @endslot
                @endcomponent
                <ul class="nav nav-pills navtab-bg">
                    <li class="nav-item">
                        <a href="#low" data-bs-toggle="tab" class="nav-link active" style="border-radius: 10px">
                            Stok Rendah <span class="badge text-bg-danger" id="total_low">0</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#out" data-bs-toggle="tab" class="nav-link" style="border-radius: 10px">
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
                                        <table class="table table-center table-hover" id="tableStockAlertLow">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Nama Produk</th>
                                                    <th>Kategori</th>
                                                    <th>SKU</th>
                                                    <th>Stok</th>
                                                    <th>Qty Peringatan</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                
                                            </tbody>
                                        </table>
                                    </div>
								</div>
								<div class="tab-pane" id="out">
									<div class="table-responsive">
                                        <table class="table table-center table-hover" id="tableStockAlertOut" style="width: 100%">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Nama Produk</th>
                                                    <th>Kategori</th>
                                                    <th>SKU</th>
                                                    <th>Stok</th>
                                                    <th>Qty Peringatan</th>
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
    <script src="{{asset('Custom_js/Backoffice/Inventory/Stock_Alert.js')}}"></script>
@endsection