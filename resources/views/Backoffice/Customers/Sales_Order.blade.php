<?php $page = 'sales_order'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    Pengiriman
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
                                <table class="table table-center table-hover" id="tableSalesOrder">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Nama Armada</th>
                                            <th>Tanggal</th>
                                            <th>No. Invoice</th>
                                            <th>Status</th>
                                            {{-- <th>Jumlah Total</th> --}}
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
    <style>
        /* Khusus modal Sales Order: body tabel scroll, header tetap terlihat */
        #add_sales_order .col-12.overflow-x-auto.mb-3 {
            max-height: 320px;
            overflow-y: auto;
            overflow-x: auto;
        }

        #add_sales_order .col-12.overflow-x-auto.mb-3 thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: #dce8f6;
        }
    </style>
    <script>
        var public = "{{ asset('') }}";    
    </script>
    <script src="{{asset('Custom_js/Backoffice/Customers/Sales_Order.js')}}"></script>
@endsection