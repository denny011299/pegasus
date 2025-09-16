<?php $page = 'cash'; ?>
@extends('layout.mainlayout')
@section('content')
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
                                            <th>Tanggal Produksi</th>
                                            <th>Nama Produk</th>
                                            <th>Jumlah</th>
                                            <th>Status</th>
                                            <th>Diinput oleh</th>
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