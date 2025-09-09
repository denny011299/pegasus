<?php $page = 'manage_stock'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            @component('components.page-header')
                    @slot('title')
                        Kelaur Masuk Produk
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
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <h6>Masukkan Data</h6>
                                </div>
                            </div>
                            
                            <div class="row mt-2 row-input">
                                <div class="col-3">
                                    <label for="input_barcode">Nomor Barcode</label>
                                    <input type="text" id="input_barcode" class="form-control" aria-describedby="emailHelp" placeholder="Nomor Barcode">
                                </div>
                                <div class="col-2">
                                    <label for="input_sku">Qty</label>
                                    <input type="text" id="input_qty" class="form-control number-only" aria-describedby="emailHelp" placeholder="Qty" value="1">
                                </div>
                                <div class="col-2">
                                    <label for="input_sku">Tipe</label>
                                    <select name="" id="input-type" class="form-select">
                                        <option value="1">Produk Masuk</option>
                                        <option value="2">Produk Keluar</option>
                                    </select>
                                </div>
                                <div class="col-5 text-end pt-4">
                                    <div class="btn-group" role="group" aria-label="Basic radio toggle button group" >
                                        <input type="radio" class="btn-check btn_scan" name="btnradio" id="btn_scan" value="1" autocomplete="off" checked="" style="border-radius: 100px;">
                                        <label class="btn btn-outline-primary" for="btn_scan">Pindai Otomatis</label>

                                        <input type="radio" class="btn-check btn_scan " name="btnradio" id="btn_manual" value="2" autocomplete="off" style="border-radius: 100px;">
                                        <label class="btn btn-outline-primary" for="btn_manual">Entri Manual</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body p-4">
                            <div class="row">
                                <div class="col-3">
                                    <input type="date" class="form-control" id="filter_start_date" aria-describedby="emailHelp">
                                </div>
                                <div class="col-3">
                                    <input type="date" class="form-control" id="filter_end_date" aria-describedby="emailHelp">
                                </div>
                                <div class="col-6 text-end">
                                    <ul class="nav nav-pills  justify-content-end d-flex row-type" role="tablist">
                                        <li class="nav-item nav-jenis" tipe="1" role="presentation">
                                            <a class="nav-link nav-jenis active"  tipe="1" data-bs-toggle="tab" href="#navpills-home" role="tab" aria-selected="false" tabindex="-1">
                                                <span class="d-none d-sm-block"><span class="mdi mdi-archive-check-outline me-1"></span> Semua</span> 
                                            </a>
                                        </li>
                                        <li class="nav-item nav-jenis" tipe="2" role="presentation">
                                            <a class="nav-link nav-jenis" tipe="2" data-bs-toggle="tab" href="#navpills-profile" role="tab" aria-selected="false" tabindex="-1">
                                                <span class="d-none d-sm-block"><span class="mdi mdi-archive-plus-outline me-1"></span> Masuk</span> 
                                            </a>
                                        </li>
                                        <li class="nav-item nav-jenis" tipe="3" role="presentation">
                                            <a class="nav-link nav-jenis " tipe="3" data-bs-toggle="tab" href="#navpills-messages" role="tab" aria-selected="true">
                                                <span class="d-none d-sm-block"><span class="mdi mdi-archive-minus-outline me-1"></span> Keluar</span>   
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="tab-content p-3 text-muted">
                                <div class="tab-pane active show" id="navpills-home" role="tabpanel">
                                <table class="table mt-3" id="tableProductAll">
                                        <thead>
                                            <tr>
                                                <td>Tanggal</td>
                                                <td>SKU</td>
                                                <td>Nama Produk</td>
                                                <td>Qty Masuk</td>
                                                <td>Qty Keluar</td>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div><!-- end tab pane -->
                                <div class="tab-pane  " id="navpills-profile" role="tabpanel">
                                <table class="table mt-3" id="tableProductIn">
                                        <thead>
                                            <tr>
                                                <td>Tanggal</td>
                                                <td>SKU</td>
                                                <td>Nama Produk</td>
                                                <td>Qty Masuk</td>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div><!-- end tab pane -->
                                <div class="tab-pane" id="navpills-messages" role="tabpanel">
                                <table class="table mt-3" id="tableProductOut">
                                        <thead>
                                            <tr>
                                                <td>Tanggal</td>
                                                <td>SKU</td>
                                                <td>Nama Produk</td>
                                                <td>Qty Keluar</td>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div><!-- end tab pane -->
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
    <script src="{{asset('Custom_js/Backoffice/Inventory/Manage_Stock.js')}}"></script>
@endsection