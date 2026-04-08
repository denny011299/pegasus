<?php $page = 'cash'; ?>
@extends('layout.mainlayout')
@section('content')
    <style>
        #tableSelisihOpname thead th {
            background-color: #e8f1ff !important;
        }

        #tableSelisihOpname .report-selisih-child thead th {
            background-color: #f5f7fb !important;
            position: sticky;
            top: 0;
            z-index: 2;
        }
    </style>
    <div class="page-wrapper">
        <div class="content container-fluid">
            @component('components.page-header')
                @slot('title')
                    Laporan Selisih Stok Opname
                @endslot
            @endcomponent

            @component('components.search-filter')
            @endcomponent

            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-center table-hover" id="tableSelisihOpname">
                                    <thead class="thead-light">
                                        <tr>
                                            <th></th>
                                            <th>Kode Opname</th>
                                            <th>Tanggal</th>
                                            <th>Item Selisih</th>
                                            <th>Nominal (+/-)</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('custom_js')
    <script>
        var public = "{{ asset('') }}";
    </script>
    <script src="{{ asset('Custom_js/Backoffice/Reports/StockOpnameDifference.js') }}?v=1"></script>
@endsection
