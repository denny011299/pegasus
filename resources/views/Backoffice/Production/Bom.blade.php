<?php $page = 'bom'; ?>
@extends('layout.mainlayout')
@section('custom_css')
    <style>
        #add_bom .select2-container {
            width: 100% !important;
        }
        #tableBom {
            width: 100% !important;
        }

        #tableBom td {
            white-space: normal !important;
            word-wrap: break-word;
        }

        #tableBom td:last-child {
            white-space: nowrap !important;
        }

        #tableBom td:last-child a {
            display: inline-flex !important;
            align-items: center;
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
                    Resep Bahan Mentah
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
                                <table class="table table-center table-hover" id="tableBom">
                                    <thead class="thead-light">
                                        <tr>
                                            <th style="width: 10%">SKU</th>
                                            <th style="width: 25%">Produk</th>
                                            <th style="width: 40%">Material</th>
                                            <th style="width: 14%">Qty Produksi</th>
                                            <th style="width: 15%">Dibuat Oleh</th>
                                            <th style="width: 15%" class="no-sort text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
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
    <script src="{{asset('Custom_js/Backoffice/Production/Bom.js')}}"></script>
@endsection