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
            padding: 12px 16px !important;
        }

        #tableStock thead th,
        #tableStockRetail thead th {
            vertical-align: middle;
            white-space: nowrap;
            padding: 14px 16px !important;
        }

        #tableStock tbody tr,
        #tableStockRetail tbody tr {
            cursor: pointer;
        }
        #tableStock td.cell-safety,
        #tableStockRetail td.cell-safety {
            background: #fafbfc;
        }
        #tableStock td.cell-safety:hover,
        #tableStockRetail td.cell-safety:hover {
            background: #eff6ff;
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

        /* Overlay loading saat pagination / search / sort (server-side) */
        #tableStock-wrap,
        #tableStockRetail-wrap {
            position: relative;
        }

        #tableStock_wrapper .dataTables_processing,
        #tableStockRetail_wrapper .dataTables_processing {
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            width: 100% !important;
            height: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            border: 0 !important;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.72) !important;
            box-shadow: none !important;
            z-index: 20;
            display: flex !important;
            align-items: center;
            justify-content: center;
            color: #1e293b;
            font-weight: 600;
            font-size: 14px;
        }

        #tableStock_wrapper .dataTables_processing > div,
        #tableStockRetail_wrapper .dataTables_processing > div {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            border-radius: 10px;
            background: #fff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        }

        #tableStock-wrap.is-loading tbody,
        #tableStockRetail-wrap.is-loading tbody {
            opacity: 0.45;
            pointer-events: none;
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
                                <table class="table table-center table-hover" id="tableStockRetail">
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
