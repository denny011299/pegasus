<?php $page = 'product'; ?>
@extends('layout.mainlayout')
@section('custom_css')
    <style>
        #tableProduct {
            width: 100% !important;
        }

        #tableProduct td {
            white-space: normal !important;
            word-wrap: break-word;
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
                    Produk
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
                                <table class="table table-center table-hover" id="tableProduct">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Nama Produk</th>
                                            <th>Kategori</th>
                                            <th>Satuan</th>
                                            <th>Variasi</th>
                                            <th class="no-sort">Aksi</th>
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
    <script src="{{asset('Custom_js/Backoffice/Product/Product.js')}}"></script>
@endsection