<?php $page = 'stok'; ?>
@extends('layout.mainlayout')
@section('custom_css')
    <style>
        /* ====================================================
           Gudang Utama — Base Table
        ==================================================== */
        #tableStock,
        #tableStockRetail {
            width: 100% !important;
        }
        #tableStock td,
        #tableStockRetail td {
            vertical-align: middle;
        }

        /* ====================================================
           Retail Table — Modern Card Row Design
        ==================================================== */

        /* Override DataTables row style for retail */
        #tableStockRetail.dataTable tbody tr {
            border: none !important;
            transition: background 0.18s ease, box-shadow 0.18s ease;
        }
        #tableStockRetail.dataTable tbody tr:hover {
            background: linear-gradient(90deg, #f0f5ff 0%, #f8faff 100%) !important;
            box-shadow: 0 2px 12px rgba(59, 130, 246, 0.07) inset;
        }
        #tableStockRetail.dataTable tbody td {
            padding: 0 !important;
            border-top: 2px solid #94a3b8 !important;
            border-bottom: none !important;
            vertical-align: top;
        }

        /* Product Column */
        .sretail-product-cell {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 10px 16px;
        }

        /* Avatar / Image */
        .sretail-avatar {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            flex-shrink: 0;
            overflow: hidden;
            background: linear-gradient(135deg, #dbeafe 0%, #ede9fe 100%);
            border: 2px solid #e0e7ff;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.12);
        }
        .sretail-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .sretail-avatar-initials {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
            color: #3730a3;
            font-weight: 700;
            font-size: 15px;
            border-radius: 50%;
            border: 1px solid #e0e7ff;
            box-shadow: inset 0 2px 4px rgba(255,255,255,0.4);
        }

        /* Product Info */
        .sretail-product-info {
            display: flex;
            flex-direction: column;
            gap: 3px;
            min-width: 0;
        }
        .sretail-product-name {
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            font-size: 14px;
            color: #0f172a;
            letter-spacing: -0.02em;
            line-height: 1.3;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .sretail-product-meta {
            font-size: 13px;
            color: #64748b;
            font-weight: 500;
            margin-top: 2px;
        }

        /* Retail Table Columns */
        .sretail-list-col {
            display: flex;
            flex-direction: column;
            padding: 8px 0;
        }
        .sretail-list-item {
            display: flex;
            align-items: center;
            height: 38px;
            font-size: 13.5px;
            font-weight: 600;
            color: #4b5563;
            border-bottom: 2px dashed #94a3b8;
            padding: 0 12px;
        }
        .sretail-list-item:last-child {
            border-bottom: none;
        }
        .sretail-list-item.qty-item {
            color: #0f172a;
            font-weight: 700;
            justify-content: center;
        }
        /* Log table */
        .table-scroll {
            max-height: 45vh;
            overflow-y: auto;
            overflow-x: hidden;
        }
        #tableLog { width: 100%; border-collapse: collapse; }
        #tableLog thead th {
            position: sticky; top: 0;
            background-color: #e7f1ff; z-index: 10;
            border-bottom: 2px solid #dee2e6; padding: 12px 8px;
        }
        #tableLog tbody td {
            padding: 10px 8px; vertical-align: middle;
            white-space: normal !important; word-wrap: break-word;
        }
    </style>
@endsection
@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">

            @component('components.page-header')
                @slot('title')
                    Stok Produk
                @endslot
            @endcomponent

            @component('components.search-filter')
            @endcomponent

            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">

                            {{-- Default tampilkan skeleton gudang utama segera (anti blank). JS ganti ke retail bila perlu. --}}
                            <div class="table-responsive dt-pending" id="tableStock-wrap">
                                <div class="dt-skeleton" aria-hidden="true">
                                    <div style="padding: 16px 25px;">
                                        <span class="skel-text" style="width: 250px; height: 38px; border-radius: 20px;"></span>
                                    </div>
                                    <div class="dt-skeleton-head" style="grid-template-columns: 15% 20% 20% 15% 15% 15%;">
                                        <span style="width:40%"></span>
                                        <span style="width:60%"></span>
                                        <span style="width:50%"></span>
                                        <span style="width:40%"></span>
                                        <span style="width:50%"></span>
                                        <span style="width:70%"></span>
                                    </div>
                                    <div class="dt-skeleton-body">
                                        @for ($i = 0; $i < 5; $i++)
                                            <div class="dt-skeleton-row" style="grid-template-columns: 15% 20% 20% 15% 15% 15%;">
                                                <span class="skel-text" style="width:60%"></span>
                                                <span class="skel-text" style="width:80%"></span>
                                                <span class="skel-text" style="width:70%"></span>
                                                <span class="skel-badge" style="width:60%;justify-self:center"></span>
                                                <span class="skel-text" style="width:70%"></span>
                                                <span class="skel-text" style="width:50%"></span>
                                            </div>
                                        @endfor
                                    </div>
                                </div>
                                <table class="table table-center table-hover" id="tableStock">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>SKU</th>
                                            <th>Nama Produk</th>
                                            <th>Varian</th>
                                            <th>Kategori</th>
                                            <th>Gudang</th>
                                            <th>Stok</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>

                            {{-- VIEW: Gudang Eceran (non-utama) --}}
                            <div class="table-responsive dt-pending" style="display: none;" id="tableStockRetail-wrap">
                                <div class="dt-skeleton" aria-hidden="true">
                                    <div style="padding: 16px 25px;">
                                        <span class="skel-text" style="width: 250px; height: 38px; border-radius: 20px;"></span>
                                    </div>
                                    <div class="dt-skeleton-head" style="grid-template-columns: 30% 18% 30% 22%;">
                                        <span style="width:50%"></span>
                                        <span style="width:30%"></span>
                                        <span style="width:30%; justify-self: flex-end;"></span>
                                        <span style="width:30%; justify-self: flex-end;"></span>
                                    </div>
                                    <div class="dt-skeleton-body">
                                        @for ($i = 0; $i < 5; $i++)
                                            <div class="dt-skeleton-row" style="grid-template-columns: 30% 18% 30% 22%;">
                                                <span class="skel-text" style="width:60%"></span>
                                                <span class="skel-text" style="width:40%"></span>
                                                <span class="skel-text" style="width:20%; justify-self: flex-end;"></span>
                                                <span class="skel-text" style="width:20%; justify-self: flex-end;"></span>
                                            </div>
                                        @endfor
                                    </div>
                                </div>
                                <table class="table table-center table-hover" id="tableStockRetail">
                                    <thead class="thead-light">
                                        <tr>
                                            <th style="width: 35%;">Nama Barang</th>
                                            <th style="width: 20%;">Satuan</th>
                                            <th class="text-center" style="width: 45%;">Stok Tersedia</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>

                            <script>
                                // Segera pilih wrap + skeleton (sebelum footer JS) biar tidak blank
                                (function () {
                                    var el = document.querySelector('.warehouse-dropdown-item.active');
                                    var isMain = !el || String(el.getAttribute('data-is-main')) === '1';
                                    var mainWrap = document.getElementById('tableStock-wrap');
                                    var retailWrap = document.getElementById('tableStockRetail-wrap');
                                    if (!mainWrap || !retailWrap) return;
                                    if (isMain) {
                                        mainWrap.style.display = '';
                                        retailWrap.style.display = 'none';
                                    } else {
                                        retailWrap.style.display = '';
                                        mainWrap.style.display = 'none';
                                    }
                                })();
                            </script>

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
    <script src="{{asset('Custom_js/Backoffice/Inventory/Stock_Product.js')}}?v={{time()}}"></script>
@endsection
