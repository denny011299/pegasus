<?php $page = 'supplies'; ?>
@extends('layout.mainlayout')
@section('custom_css')
    <style>
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #082a58 !important;
            color: #fff !important;
            border: none !important;
            border-radius: 0.4rem !important;
            padding: 2px 8px !important;
            margin-top: 4px !important;
            display: flex !important;
            align-items: center !important;
        }

        /* Teks di dalam chip */
        .select2-container--default .select2-selection--multiple .select2-selection__choice__display {
            color: #fff !important;
            font-weight: 500;
            margin-left: 5px !important;
        }

        /* Tombol hapus */
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            background: none !important;
            border: none !important;
            color: #fff !important;
            cursor: pointer !important;
            font-size: 14px !important;
            margin-right: 4px !important;
            padding: 0 !important;
            line-height: 1 !important;
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
                    Supplies
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
                                <table class="table table-center table-hover" id="tableSupplies">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Supply Name</th>
                                            <th>Unit</th>
                                            <th>Description</th>
                                            <th>Stock</th>
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
            <!-- /Table -->

        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection

@section('custom_js')
    <script>
        var public = "{{ asset('') }}";    
    </script>
    <script src="{{asset('Custom_js/Backoffice/Product/Supplies.js')}}"></script>
@endsection