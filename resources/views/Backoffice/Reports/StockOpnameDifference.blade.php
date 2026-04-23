<?php $page = 'cash'; ?>
@extends('layout.mainlayout')
@section('content')
    <style>
        #tableSelisihOpname thead th {
            background-color: #e8f1ff !important;
            font-weight: 600;
            font-size: 0.8125rem;
            border-bottom-width: 1px;
        }

        #tableSelisihOpname tbody > tr:not(.child) td {
            vertical-align: middle;
        }

        #tableSelisihOpname tbody > tr:not(.child):hover {
            background-color: #f8fafc;
        }

        #tableSelisihOpname .child-row-wrapper {
            padding: 0.35rem 0 0.5rem 2.25rem;
            background: linear-gradient(90deg, #f0f4fa 0px, #f0f4fa 3px, transparent 3px);
        }

        #tableSelisihOpname .report-selisih-child {
            border: 1px solid #e0e4eb;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        }

        #tableSelisihOpname .report-selisih-child thead th {
            background-color: #f5f7fb !important;
            color: #475569;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: 1px solid #dde3ea !important;
            padding: 0.5rem 0.65rem;
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        #tableSelisihOpname .report-selisih-child tbody td {
            font-size: 0.8125rem;
            padding: 0.5rem 0.65rem;
            vertical-align: middle;
            border-color: #eef0f4 !important;
        }

        #tableSelisihOpname .report-selisih-child tbody tr:nth-child(even) {
            background-color: #fafbfd;
        }

        #tableSelisihOpname .selisih-badge-sumber {
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.03em;
        }

        #tableSelisihOpname {
            width: 100% !important;
            min-width: 800px;
        }

        #tableSelisihOpname td {
            white-space: normal !important;
            word-wrap: break-word;
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

            <div class="alert alert-light border mb-3" role="alert" style="font-size: 0.875rem;">
                <strong>Selisih</strong> = fisik − sistem (per satuan, sesuai input opname). Kalau <strong>stok sistem = 0</strong> untuk satuan itu, angka selisih akan sama dengan stok fisik — bukan error, artinya di sistem saldo tercatat nol saat opname.
                <strong>Nominal</strong> memakai harga satuan varian (produk) atau harga beli terakhir (bahan); harga 0 memberi nominal 0.
            </div>

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
    <script src="{{ asset('Custom_js/Backoffice/Reports/report_datatable_loading.js') }}?v=1"></script>
    <script src="{{ asset('Custom_js/Backoffice/Reports/StockOpnameDifference.js') }}?v=3"></script>
@endsection
