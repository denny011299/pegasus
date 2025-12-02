<?php $page = 'expired-products'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content">
            @component('components.breadcrumb')
                @slot('title')
                    Products Prices
                @endslot
                @slot('li_1')
                    Manage your product prices
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
                        <div class="form-sort">
                            <i data-feather="sliders" class="info-img"></i>
                            <select class="select">
                                <option>Sort by Date</option>
                                <option>Newest</option>
                                <option>Oldest</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table " id="tableProductPrices">
                            <thead>
                                <tr>
                                    <thead>
                                        <tr>
                                            <th>SKU</th>
                                            <th>Nama Produk</th>
                                            <th>Varian</th>
                                            <th>Kategori</th>
                                            <th>Merek</th>
                                            <th class="no-sort">Edit</th>
                                        </tr>
                                    </thead>
                                </tr>
                            </thead>
                            <tbody id="">
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
    <script src="{{ asset('/Custom_js/Backoffice/Product/ProductPrices.js') }}?v={{ time() }}"></script>
@endsection
