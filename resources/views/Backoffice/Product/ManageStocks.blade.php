<?php $page = 'manage-stocks'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content">
            @component('components.breadcrumb')
                @slot('title')
                    Manage Stock
                @endslot
                @slot('li_1')
                    Manage your stock
                @endslot
                @slot('li_2')
                    Add New
                @endslot
            @endcomponent
            <div class="card table-list-card">
                <div class="card-body">
                    <div class="row p-3 ">
                        <div class="col-2">
                            <div class="mb-3">
                                <label class="form-label">Store/Warehouse</label>
                                <select name="" id="store_id" class="form-select fill">
                                </select>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="mb-3">
                                <label class="form-label">Barcode Number</label>
                                <input type="text" class="form-control" value="" id="input_barcode">
                            </div>
                        </div>
                        <div class="col-2">
                            <div class="mb-3">
                                <label class="form-label">Qty</label>
                                <input type="number" value="1" min="0" class="form-control fill" value=""
                                    id="input_qty">
                            </div>
                        </div>
                        <div class="col-2">
                            <div class="mb-3">
                                <label class="form-label">Type</label>
                                <select name="" id="input-type" class="form-select">
                                    <option value="1">Product In</option>
                                    <option value="2">Product Out</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-3 text-end pb-2 pt-3">
                            <ul class="nav nav-tabs tab-style-1 d-sm-flex d-block" role="tablist"
                                style="border-radius: 10px">

                                <li class="nav-item">
                                    <a class="nav-link active btn_scan" id="btn_scan" value="1" href="#returned">Auto
                                        Scan</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link btn_scan" id="btn_manual" value="2" href="#damaged">Manual
                                        Entry</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card table-list-card">
                <div class="card-body">
                    <div class="table-top">
                        <div class="search-set pt-2">
                            <div class="search-input">
                                <a href="" class="btn btn-searchset"><i data-feather="search"
                                        class="feather-search"></i></a>
                            </div>
                        </div>
                        <div class="search-path pt-2">
                            <div class="d-flex align-items-center">
                                <a class="btn btn-filter" id="filter_search">
                                    <i data-feather="filter" class="filter-icon"></i>
                                    <span><img src="{{ URL::asset('/build/img/icons/closes.svg') }}" alt="img"></span>
                                </a>
                            </div>
                        </div>
                        <div class="form-sort" style="width: auto">
                            <ul class="nav nav-tabs tab-style-1 d-sm-flex d-block" role="tablist"
                                style="border-radius: 10px">
                                <li class="nav-item">
                                    <a class="nav-link nav-jenis active" tipe="1" data-bs-toggle="tab"
                                        data-bs-target="#all" aria-current="page" href="#all">All</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link nav-jenis" tipe="2" data-bs-toggle="tab" data-bs-target="#in"
                                        href="#in">Product In</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link nav-jenis" tipe="3" data-bs-toggle="tab" data-bs-target="#out"
                                        href="#out">Product Out</a>
                                </li>
                            </ul>
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
                                            <input type="text" class="datetimepicker" placeholder="Choose Date">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-sm-6 col-12 ms-auto">
                                    <div class="input-blocks">
                                        <a class="btn btn-filters ms-auto"> <i data-feather="search"
                                                class="feather-search"></i> Search </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /Filter -->
                    <div class="table-responsive">
                        <div class="tab-content">
                            <div class="tab-pane active" id="all" role="tabpanel">
                                <table class="table" id="tableProductAll">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Outlet</th>
                                            <th>Product</th>
                                            <th>SKU</th>
                                            <th>Product In</th>
                                            <th>Product Out</th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                    </tbody>
                                </table>
                            </div>
                            <div class="tab-pane" id="in" role="tabpanel">
                                <table class="table " id="tableProductIn">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Outlet</th>
                                            <th>Product</th>
                                            <th>SKU</th>
                                            <th class="text-center">Product In</th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                    </tbody>
                                </table>
                            </div>
                            <div class="tab-pane" id="out" role="out">
                                <table class="table" id="tableProductOut">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Outlet</th>
                                            <th>Product</th>
                                            <th>SKU</th>
                                            <th>Product Out</th>
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
            <!-- /product list -->
        </div>
    @endsection
    @section('custom_js')
        <script>
            var public = "{{ asset('') }}";
            var store_id = $('#storeWarehouseSelect').val();
            console.log(store_id);
            
        </script>
        <script src="{{ asset('/Custom_js/Backoffice/Product/ManageStock.js') }}?v={{ time() }}"></script>
    @endsection
