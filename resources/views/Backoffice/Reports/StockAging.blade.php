<?php $page = 'cash'; ?>
@extends('layout.mainlayout')
@section('content')
    <style>
        .report-stock-aging-filter [class*="col-"] {
            min-width: 0;
        }

        .report-stock-aging-filter .select2-container {
            width: 100% !important;
            max-width: 100%;
        }

        #tableStockAging thead th {
            background-color: #e8f1ff !important;
            font-weight: 600;
            font-size: 0.8125rem;
            border-bottom-width: 1px;
        }

        #tableStockAging tbody > tr td {
            vertical-align: middle;
        }

        #tableStockAging tbody > tr:hover {
            background-color: #f8fafc;
        }

        .aging-badge-bucket {
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.03em;
        }
    </style>
    <div class="page-wrapper">
        <div class="content container-fluid">
            @component('components.page-header')
                @slot('title')
                    Laporan Stock Aging
                @endslot
            @endcomponent

            @component('components.search-filter')
            @endcomponent

            <div class="alert alert-light border mb-3" role="alert" style="font-size: 0.875rem;">
                <strong>Rumus (FIFO dari <code>log_stocks</code>):</strong> mutasi <strong>masuk</strong> memakai
                <code>log_category = 1</code>, <strong>keluar</strong> <code>log_category = 2</code>, diurutkan
                <code>log_date</code> lalu <code>log_id</code>. Keluar mengonsumsi lot tertua dulu. Baris catatan
                berisi <em>Stock Opname</em> tidak dipakai (bukan delta FIFO). Jika saldo hasil FIFO kurang dari stok
                sekarang, sisanya dianggap saldo awal dengan tanggal acuan = <code>created_at</code> baris stok.
                <strong>Umur tertimbang (hari)</strong> = Σ(qty lot × hari sejak tanggal lot hingga tanggal acuan) /
                qty total. <strong>Nilai</strong> = stok × harga varian (produk) atau harga beli terakhir PO (bahan).
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-center table-hover" id="tableStockAging">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Sumber</th>
                                            <th>Item</th>
                                            <th>Satuan</th>
                                            <th class="text-end">Qty</th>
                                            <th class="text-end">Umur tertimbang (hari)</th>
                                            <th>Kelompok</th>
                                            <th>Tanggal lot tertua</th>
                                            <th class="text-end">Harga / unit</th>
                                            <th class="text-end">Nilai stok</th>
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
    <script src="{{ asset('Custom_js/Backoffice/Reports/StockAging.js') }}?v=1"></script>
@endsection
