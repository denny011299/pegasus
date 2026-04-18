<?php $page = 'cash'; ?>
@extends('layout.mainlayout')
@section('content')
    <style>
        #tableProduct thead th {
            background-color: #e8f1ff !important;
        }

        #tableProduct th.col-item-name,
        #tableProduct td.col-item-name,
        #tableProduct th.col-supplier,
        #tableProduct td.col-supplier {
            white-space: normal !important;
            word-break: break-word;
            overflow-wrap: anywhere;
            vertical-align: middle;
        }

        #tableProduct .report-return-child thead th {
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
                    Laporan Retur
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
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-center " id="tableProduct">
                                    <thead class="thead-light">
                                        <tr>
                                            <th></th>
                                            <th>Retur Bahan</th>
                                            <th>Supplier</th>
                                            <th>Total Transaksi Retur</th>
                                            <th>Akumulasi Qty Retur</th>
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
    <script src="{{ asset('Custom_js/Backoffice/Reports/report_datatable_loading.js') }}?v=1"></script>
    <script src="{{asset('Custom_js/Backoffice/Reports/ProductReturn.js')}}?v=1"></script>
@endsection