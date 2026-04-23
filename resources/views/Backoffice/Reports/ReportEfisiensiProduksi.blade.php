<?php $page = 'cash'; ?>
@extends('layout.mainlayout')
@section('content')
    <style>
        #tableReportEfisiensi thead th {
            background-color: #e8f1ff !important;
        }

        #tableReportEfisiensi .report-efisiensi-child thead th {
            background-color: #f5f7fb !important;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        #tableReportEfisiensi {
            width: 100% !important;
            min-width: 800px;
        }

        #tableReportEfisiensi td {
            white-space: normal !important;
            word-wrap: break-word;
        }

        #efisiensi-kpi-summary .card {
            border: 1px solid #e8ecf1;
        }

        #efisiensi-kpi-summary .kpi-val {
            font-size: 1.25rem;
            font-weight: 700;
        }

        #efisiensi-kpi-summary .kpi-label {
            font-size: 0.75rem;
            color: #5c6b7a;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
    </style>
    <div class="page-wrapper">
        <div class="content container-fluid">
            @component('components.page-header')
                @slot('title')
                    Laporan Efisiensi Produksi (Barang Cacat/Reject)
                @endslot
            @endcomponent

            @component('components.search-filter')
            @endcomponent

            <div class="row mb-3 d-none" id="efisiensi-kpi-summary">
                <div class="col-6 col-md-3 mb-2 mb-md-0">
                    <div class="card p-3 h-100">
                        <div class="kpi-label">Rata-rata skor operasional</div>
                        <div class="kpi-val text-primary" id="kpi-avg-score">—</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 mb-2 mb-md-0">
                    <div class="card p-3 h-100">
                        <div class="kpi-label">Total qty lulus</div>
                        <div class="kpi-val" id="kpi-good-qty">—</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 mb-2 mb-md-0">
                    <div class="card p-3 h-100">
                        <div class="kpi-label">Batch tanpa log bahan</div>
                        <div class="kpi-val text-warning" id="kpi-untracked">—</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card p-3 h-100">
                        <div class="kpi-label">Produk prioritas (risiko)</div>
                        <div class="kpi-val text-danger" id="kpi-high-risk">—</div>
                    </div>
                </div>
            </div>

            <div class="alert alert-light border mb-3" role="alert">
                <strong>Cara baca:</strong> status produksi di sistem hanya tiga: <strong>Pending</strong>, <strong>Berhasil</strong>, <strong>Tolak</strong> (laporan ini hanya memakai yang sudah final: Berhasil + Tolak).
                <strong>Yield</strong> = persen qty dari batch berhasil dibanding total qty (termasuk batch tolak).
                <strong>Skor operasional</strong> menggabungkan yield dan retensi bahan (semakin tinggi semakin baik).
                <strong>Batch tanpa log bahan</strong> = kode produksi tanpa <em>log stok</em> pemakaian bahan — periksa pencatatan agar metrik akurat.
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-center table-hover" id="tableReportEfisiensi">
                                    <thead class="thead-light">
                                        <tr>
                                            <th></th>
                                            <th title="Varian produk">Produk</th>
                                            <th title="Semua batch final: berhasil + tolak">Total produksi</th>
                                            <th title="Qty dari batch berstatus berhasil">Qty lulus</th>
                                            <th title="Batch &amp; qty dengan status Tolak">Tolak</th>
                                            <th title="% qty tolak dari total qty">Rasio tolak</th>
                                            <th title="% alokasi bahan ke batch tolak">Rasio waste bahan</th>
                                            <th title="Batch tanpa log pemakaian bahan di stok">Data bahan</th>
                                            <th title="Yield = % qty berhasil (bukan tolak)">Yield</th>
                                            <th title="(100−reject%)×(100−waste%)/100">Skor operasional</th>
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
    <script src="{{ asset('Custom_js/Backoffice/Reports/ReportEfisiensiProduksi.js') }}?v=3"></script>
@endsection
