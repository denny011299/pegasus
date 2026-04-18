<?php $page = 'cash'; ?>
@extends('layout.mainlayout')
@section('content')
    <style>
        .report-retur-armada-filter [class*="col-"] {
            min-width: 0;
        }

        .report-retur-armada-filter .select2-container {
            width: 100% !important;
            max-width: 100%;
        }

        .report-retur-armada-filter .select2-container .select2-selection--single {
            width: 100%;
            overflow: hidden;
        }

        .report-retur-armada-filter .select2-container .select2-selection__rendered {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        #tableReportReturArmada thead th {
            background-color: #e8f1ff !important;
        }

        #tableReportReturArmada .report-retur-armada-child thead th {
            background-color: #f5f7fb !important;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        #tableReportReturArmada th.col-item-name,
        #tableReportReturArmada td.col-item-name {
            white-space: normal !important;
            word-break: break-word;
            overflow-wrap: anywhere;
            vertical-align: middle;
        }
    </style>
    <div class="page-wrapper">
        <div class="content container-fluid">

            @component('components.page-header')
                @slot('title')
                    Laporan Retur Produk (Armada)
                @endslot
            @endcomponent

            @component('components.search-filter')
            @endcomponent

            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-center table-hover" id="tableReportReturArmada">
                                    <thead class="thead-light">
                                        <tr>
                                            <th></th>
                                            <th>Nama Produk</th>
                                            <th>Total Baris Retur</th>
                                            <th>Akumulasi Qty Retur</th>
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
    <script src="{{ asset('Custom_js/Backoffice/Reports/report_datatable_loading.js') }}?v=1"></script>
    <script src="{{ asset('Custom_js/Backoffice/Reports/ReportReturProdukArmada.js') }}?v=1"></script>
@endsection
