<?php $page = 'stok'; ?>
@extends('layout.mainlayout')
@section('custom_css')
    <style>
        .table-scroll {
            max-height: 45vh;
            overflow-y: auto;
            overflow-x: hidden; 
        }

        #tableLog {
            width: 100%;
            border-collapse: collapse;
        }

        #tableLog thead th {
            position: sticky;
            top: 0;
            background-color: #e7f1ff; 
            z-index: 10;
            border-bottom: 2px solid #dee2e6;
            padding: 12px 8px;
        }

        #tableLog tbody td {
            padding: 10px 8px;
            vertical-align: middle;
        }
    </style>
@endsection
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    Stok Produk
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
                    <div class=" card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-center table-hover" id="tableStock">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>SKU</th>
                                            <th>Nama Produk</th>
                                            <th>Varian</th>
                                            <th>Kategori</th>
                                            <th>Stok</th>
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
            <!-- /Table -->

        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection

@section('custom_js')
    <script>
        var public = "{{ asset('') }}";    
    </script>
    <script src="{{asset('Custom_js/Backoffice/Inventory/Stock_Product.js')}}"></script>
@endsection