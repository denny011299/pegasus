<?php $page = 'low-stocks'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content">

            <div class="page-header">
                <div class="page-title me-auto">
                    <h4>Stock Alert</h4>
                    <h6>Manage your low stocks</h6>
                </div>
                <ul class="table-top-head low-stock-top-head mt-2">
                    <li>
                        <div class="status-toggle d-flex justify-content-between align-items-center">
                            <input type="checkbox" id="user2" class="check" checked="">
                            <label for="user2" class="checktoggle">checkbox</label>
                            Notify
                        </div>
                    </li>
                    <li>
                        <a href="" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#send-email"><i
                                data-feather="mail" class="feather-mail"></i>Send Email</a>
                    </li>
                    <li>
                        <a data-bs-toggle="tooltip" data-bs-placement="top" title="Collapse" id="collapse-header"><i
                                data-feather="chevron-up" class="feather-chevron-up"></i></a>
                    </li>
                </ul>
                <div class="d-flex purchase-pg-btn">
                    <div class="page-btn" id="btn_add_purchase">
                        <a href="#" class="btn btn-added" data-bs-toggle="modal" id="btn_add_alert"
                            data-bs-target="#add-alert-new"><i data-feather="plus-circle" class="me-2"></i>Tambah
                            Peringatan Stok</a>
                    </div>
                </div>
            </div>
            <div class="table-tab">
                <ul class="nav nav-pills" id="pills-tab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active position-relative" id="pills-home-tab" data-bs-toggle="pill"
                            data-bs-target="#pills-home" type="button" role="tab" aria-controls="pills-home"
                            aria-selected="true">
                            Low Stocks
                            <span class="badge bg-danger position-absolute top-0 start-100 translate-middle"
                                id="low-notif">0</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation" style="margin-left: 1rem">
                        <button class="nav-link position-relative" id="pills-profile-tab" data-bs-toggle="pill"
                            data-bs-target="#pills-profile" type="button" role="tab" aria-controls="pills-profile"
                            aria-selected="false">
                            Out of Stocks
                            <span class="badge bg-danger position-absolute top-0 start-100 translate-middle"
                                id="out-notif">0</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation" style="margin-left: 1rem">
                        <button class="nav-link" id="pills-all-tab" data-bs-toggle="pill" data-bs-target="#pills-all"
                            type="button" role="tab" aria-controls="pills-all" aria-selected="false">All</button>
                    </li>

                </ul>
                <div class="tab-content" id="pills-tabContent">
                    <div class="tab-pane fade show active" id="pills-home" role="tabpanel" aria-labelledby="pills-home-tab">
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
                                            <span><img src="{{ URL::asset('/build/img/icons/closes.svg') }}"
                                                    alt="img"></span>
                                        </a>
                                    </div>
                                    <div class="form-sort">
                                        <i data-feather="sliders" class="info-img"></i>
                                        <select class="select">
                                            <option>Sort by Date</option>
                                            <option>Newest</option>
                                            <option>Oldest</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="card" id="filter_inputs">
                                    <div class="card-body pb-0">
                                        <div class="row">
                                            <div class="col-lg-3 col-sm-6 col-12">
                                                <div class="input-blocks">
                                                    <i data-feather="box" class="info-img"></i>
                                                    <select class="select">
                                                        <option>Choose Product</option>
                                                        <option>Lenovo 3rd Generation </option>
                                                        <option>Nike Jordan </option>
                                                        <option>Amazon Echo Dot </option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-sm-6 col-12">
                                                <div class="input-blocks">
                                                    <i data-feather="zap" class="info-img"></i>
                                                    <select class="select">
                                                        <option>Choose Category</option>
                                                        <option>Laptop</option>
                                                        <option>Shoe</option>
                                                        <option>Speaker</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-sm-6 col-12">
                                                <div class="input-blocks">
                                                    <i data-feather="archive" class="info-img"></i>
                                                    <select class="select">
                                                        <option>Choose Warehouse</option>
                                                        <option>Lavish Warehouse </option>
                                                        <option>Lobar Handy </option>
                                                        <option>Traditional Warehouse </option>
                                                    </select>
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
                                <div class="table-responsive">
                                    <table class="table datanew" id="low-stock-table">
                                        <thead>
                                            <tr>
                                                <th>Nama Produk</th>
                                                <th>Varian</th>
                                                <th>SKU</th>
                                                <th>Store</th>
                                                <th>Stok</th>
                                                <th>Alert</th>
                                                <th>Tanggal Restock Terakhir</th>
                                                <th class="no-sort">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="pills-profile" role="tabpanel" aria-labelledby="pills-profile-tab">
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
                                        <a class="btn btn-filter" id="filter_search1">
                                            <i data-feather="filter" class="filter-icon"></i>
                                            <span><img src="{{ URL::asset('/build/img/icons/closes.svg') }}"
                                                    alt="img"></span>
                                        </a>
                                    </div>
                                    <div class="form-sort">
                                        <i data-feather="sliders" class="info-img"></i>
                                        <select class="select">
                                            <option>Sort by Date</option>
                                            <option>Newest</option>
                                            <option>Oldest</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="card" id="filter_inputs1">
                                    <div class="card-body pb-0">
                                        <div class="row">
                                            <div class="col-lg-3 col-sm-6 col-12">
                                                <div class="input-blocks">
                                                    <i data-feather="box" class="info-img"></i>
                                                    <select class="select">
                                                        <option>Choose Product</option>
                                                        <option>Lenovo 3rd Generation </option>
                                                        <option>Nike Jordan </option>
                                                        <option>Amazon Echo Dot </option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-sm-6 col-12">
                                                <div class="input-blocks">
                                                    <i data-feather="zap" class="info-img"></i>
                                                    <select class="select">
                                                        <option>Choose Category</option>
                                                        <option>Laptop</option>
                                                        <option>Shoe</option>
                                                        <option>Speaker</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-sm-6 col-12">
                                                <div class="input-blocks">
                                                    <i data-feather="archive" class="info-img"></i>
                                                    <select class="select">
                                                        <option>Choose Warehouse</option>
                                                        <option>Lavish Warehouse </option>
                                                        <option>Lobar Handy </option>
                                                        <option>Traditional Warehouse </option>
                                                    </select>
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
                                <div class="table-responsive">
                                    <table class="table datanew" id="out-stock-table">
                                        <thead>
                                            <tr>
                                                <th>Nama Produk</th>
                                                <th>Varian</th>
                                                <th>SKU</th>
                                                <th>Store</th>
                                                <th>Stok</th>
                                                <th>Alert</th>
                                                <th>Tanggal Restock Terakhir</th>
                                                <th class="no-sort">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="pills-all" role="tabpanel" aria-labelledby="pills-all-tab">
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
                                        <a class="btn btn-filter" id="filter_search2">
                                            <i data-feather="filter" class="filter-icon"></i>
                                            <span><img src="{{ URL::asset('/build/img/icons/closes.svg') }}"
                                                    alt="img"></span>
                                        </a>
                                    </div>
                                    <div class="form-sort">
                                        <i data-feather="sliders" class="info-img"></i>
                                        <select class="select">
                                            <option>Sort by Date</option>
                                            <option>Newest</option>
                                            <option>Oldest</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="card" id="filter_inputs2">
                                    <div class="card-body pb-0">
                                        <div class="row">
                                            <div class="col-lg-3 col-sm-6 col-12">
                                                <div class="input-blocks">
                                                    <i data-feather="box" class="info-img"></i>
                                                    <select class="select">
                                                        <option>Choose Product</option>
                                                        <option>Lenovo 3rd Generation </option>
                                                        <option>Nike Jordan </option>
                                                        <option>Amazon Echo Dot </option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-sm-6 col-12">
                                                <div class="input-blocks">
                                                    <i data-feather="zap" class="info-img"></i>
                                                    <select class="select">
                                                        <option>Choose Category</option>
                                                        <option>Laptop</option>
                                                        <option>Shoe</option>
                                                        <option>Speaker</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-sm-6 col-12">
                                                <div class="input-blocks">
                                                    <i data-feather="archive" class="info-img"></i>
                                                    <select class="select">
                                                        <option>Choose Warehouse</option>
                                                        <option>Lavish Warehouse </option>
                                                        <option>Lobar Handy </option>
                                                        <option>Traditional Warehouse </option>
                                                    </select>
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
                                <div class="table-responsive">
                                    <table class="table datanew" id="all-stock-table">
                                        <thead>
                                            <tr>
                                                <th>Nama Produk</th>
                                                <th>Varian</th>
                                                <th>SKU</th>
                                                <th>Store</th>
                                                <th>Stok</th>
                                                <th>Alert</th>
                                                <th>Tanggal Restock Terakhir</th>
                                                <th class="no-sort">Action</th>
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
    </div>
@endsection
@section('custom_js')
    <script>
        var public = "{{ asset('') }}";
    </script>
    <script src="{{ asset('/Custom_js/Backoffice/Product/StockAlert.js') }}?v={{ time() }}"></script>
@endsection
