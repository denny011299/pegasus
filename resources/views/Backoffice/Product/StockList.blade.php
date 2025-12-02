<?php $page = 'suppliers'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content">
            @component('components.breadcrumb')
                @slot('title')
                    Stocks List
                @endslot
                @slot('li_1')
                    Lihat Stock
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
                                <option>Sort by Date</option>
                                <option>25 9 23</option>
                                <option>12 9 23</option>
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
                                            <option>Choose Supplier Name</option>
                                            <option>Dazzle Shoes</option>
                                            <option>A-Z Store</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-sm-6 col-12">
                                    <div class="input-blocks">
                                        <i data-feather="globe" class="info-img"></i>
                                        <select class="select">
                                            <option>Choose Country</option>
                                            <option>Mexico</option>
                                            <option>Italy</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-sm-6 col-12">
                                    <div class="input-blocks">
                                        <a class="btn btn-filters ms-auto"> <i data-feather="search"
                                                class="feather-search"></i> Search </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /Filter -->
                    <div class="tab-content">
                        <div class="tab-pane active show" role="tabpanel">
                            <table class="table datanew" id="tableProduct">
                                <thead>
                                    <tr>
                                        <th>Nama Produk</th>
                                        <th>Varian</th>
                                        <th>SKU</th>
                                        <th>Kategori</th>
                                        <th>Merek</th>
                                        <th>Stok</th>
                                        <th>Lokasi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                        <div class="tab-pane" id="allVariants" role="tabpanel">
                            <table class="table datanew" id="tableVariants">
                                <thead>
                                    <tr>
                                        <th>SKU</th>
                                        <th>Nama Produk</th>
                                        <th>Varian</th>
                                        <th>Kategori</th>
                                        <th>Merek</th>
                                        <th>Harga Eceran</th>
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
            <!-- /product list -->
        </div>
    </div>
@endsection
@section('custom_js')
    <script>
        var public = "{{ asset('') }}";
    </script>
    <script src="{{ asset('/Custom_js/Backoffice/Product/ListStock.js') }}?v={{ time() }}"></script>
@endsection
