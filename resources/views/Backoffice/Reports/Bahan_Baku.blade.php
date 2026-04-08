<?php $page = 'cash'; ?>
@extends('layout.mainlayout')
@section('content')
    <style>
        .report-bahan-filter [class*="col-"] {
            min-width: 0;
        }

        .report-bahan-filter .select2-container {
            width: 100% !important;
            max-width: 100%;
        }

        .report-bahan-filter .select2-container .select2-selection--single {
            width: 100%;
            overflow: hidden;
        }

        .report-bahan-filter .select2-container .select2-selection__rendered {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        #tableBahanBaku thead th {
            background-color: #e8f1ff !important;
        }

        #tableBahanBaku .report-bahan-child thead th {
            background-color: #f5f7fb !important;
            position: sticky;
            top: 0;
            z-index: 2;
        }
    </style>
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    Laporan Pemakaian Bahan
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
                                <table class="table table-center table-hover" id="tableBahanBaku">
                                    <thead class="thead-light">
                                        <tr>
                                            <th></th>
                                            <th>Nama Bahan</th>
                                            <th>Supplier</th>
                                            <th>Total Transaksi Keluar</th>
                                            <th>Total Qty Keluar</th>
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
    <script src="{{asset('Custom_js/Backoffice/Reports/Bahan_Baku.js')}}"></script>
@endsection