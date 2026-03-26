<?php $page = 'cash'; ?>
@extends('layout.mainlayout')
@section('content')
    <style>
        #tableReportProduction thead th {
            background-color: #e8f1ff !important;
        }

        #tableReportProduction .report-production-child thead th {
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
                    Laporan Produksi
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
                                <table class="table table-center table-hover" id="tableReportProduction">
                                    <thead class="thead-light">
                                        <tr>
                                            <th></th>
                                            <th>Nama Produk</th>
                                            <th>Total Produksi</th>
                                            <th>Berhasil Produksi</th>
                                            <th>Ditolak</th>
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
    <script src="{{asset('Custom_js/Backoffice/Reports/ReportProduction.js')}}"></script>
@endsection