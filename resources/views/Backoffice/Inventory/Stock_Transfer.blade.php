<?php $page = 'stock_transfer'; ?>
@extends('layout.mainlayout')
@section('custom_css')
    {{-- GEMINI: isi style modal / tabel transfer di sini bila perlu --}}
    <style>
        #add_stock_transfer .select2-container--default .select2-selection.is-invalid,
        #add_stock_transfer .select2-container--default .select2-selection.is-invalids,
        #add_stock_transfer .select2-container--default .select2-selection--single.is-invalid,
        #add_stock_transfer .select2-container--default .select2-selection--single.is-invalids {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.15) !important;
        }
        #tableStockTransfer-wrap {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
        }
        #tableStockTransfer {
            width: max-content !important;
            min-width: 1400px;
            table-layout: auto !important;
        }
        #tableStockTransfer th,
        #tableStockTransfer td {
            white-space: nowrap !important;
            vertical-align: middle !important;
        }
        #tableStockTransfer td:last-child,
        #tableStockTransfer th:last-child {
            white-space: nowrap !important;
            overflow: visible !important;
        }
        #tableStockTransfer td > div {
            min-width: 0;
        }
        #add_stock_transfer #tableTransferItems .transfer-row-retail-error > td,
        #add_stock_transfer #tableTransferItems .transfer-row-stock-error > td {
            background: #fff1f2;
            color: #7f1d1d;
            border-color: #fecdd3;
        }
        #add_stock_transfer #tableTransferItems .transfer-row-retail-error:hover > td,
        #add_stock_transfer #tableTransferItems .transfer-row-stock-error:hover > td {
            background: #ffe4e6;
        }
        #add_stock_transfer #tableTransferItems .col-stock-asal {
            width: 110px;
            max-width: 140px;
            white-space: nowrap;
            vertical-align: middle;
        }
        #add_stock_transfer #tableTransferItems .col-qty-unit {
            vertical-align: middle;
        }
        #add_stock_transfer #tableTransferItems .transfer-qty-unit-wrap {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            min-width: 0;
        }
        #add_stock_transfer #tableTransferItems .transfer-qty {
            width: 78px;
            flex: 0 0 78px;
            max-width: 78px;
            font-size: 14px;
            height: 34px;
        }
        #add_stock_transfer #tableTransferItems .transfer-unit-wrap {
            flex: 1 1 auto;
            min-width: 0;
        }
        #add_stock_transfer #tableTransferItems .transfer-stock-check-spinner {
            width: 1rem;
            height: 1rem;
            border-width: 0.15em;
            vertical-align: middle;
        }
        #add_stock_transfer #tableTransferItems .transfer-unit {
            min-width: 0;
            width: 100%;
            max-width: 100%;
            height: 34px;
        }
        #add_stock_transfer #tableTransferItems .transfer-retail-unit {
            min-width: 0;
            width: 100%;
            max-width: 100%;
            height: 34px;
            border-color: #ef4444;
            background-color: #fff;
            color: #7f1d1d;
        }
        #add_stock_transfer #tableTransferItems .transfer-retail-error-text,
        #add_stock_transfer #tableTransferItems .transfer-stock-error-text {
            display: block;
            margin-top: 5px;
            color: #b91c1c;
            font-size: 11px;
            line-height: 1.3;
            white-space: normal;
        }
        #add_stock_transfer .transfer-product-panel {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 16px;
        }
        #add_stock_transfer .transfer-product-grid {
            display: grid;
            grid-template-columns: minmax(280px, 2fr) minmax(90px, .55fr) minmax(180px, 1fr) auto;
            gap: 10px;
            align-items: start;
        }
        #add_stock_transfer .transfer-product-field label {
            display: block;
            margin-bottom: 5px;
            color: #64748b;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .4px;
            text-transform: uppercase;
        }
        #add_stock_transfer .transfer-product-field .form-control,
        #add_stock_transfer .transfer-product-field .form-select {
            min-height: 38px;
            border-radius: 8px;
            font-size: 13px;
        }
        #add_stock_transfer .transfer-product-actions {
            display: flex;
            gap: 8px;
            padding-top: 21px;
        }
        #add_stock_transfer .transfer-product-actions .btn {
            height: 38px;
            border-radius: 8px;
            white-space: nowrap;
            font-size: 13px;
        }
        #add_stock_transfer #transfer_stock_available {
            display: block;
            margin-top: 4px;
            font-size: 11px;
        }
        @media (max-width: 991.98px) {
            #add_stock_transfer .transfer-product-grid {
                grid-template-columns: minmax(0, 2fr) minmax(90px, .7fr) minmax(150px, 1fr);
            }
            #add_stock_transfer .transfer-product-actions {
                grid-column: 1 / -1;
                padding-top: 0;
            }
        }
        @media (max-width: 575.98px) {
            #add_stock_transfer .transfer-product-grid {
                grid-template-columns: 1fr 1fr;
            }
            #add_stock_transfer .transfer-product-select,
            #add_stock_transfer .transfer-product-actions {
                grid-column: 1 / -1;
            }
            #add_stock_transfer .transfer-product-actions .btn:first-child {
                flex: 1;
            }
        }
    </style>
@endsection
@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">

            @component('components.page-header')
                @slot('title')
                    Stock Transfer
                @endslot
            @endcomponent

            @component('components.search-filter')
            @endcomponent

            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive dt-pending" id="tableStockTransfer-wrap">
                                <div class="dt-skeleton" aria-hidden="true">
                                    <div style="padding: 16px 25px 16px 25px;">
                                        <span class="skel-text" style="width: 250px; height: 38px; border-radius: 20px;"></span>
                                    </div>
                                    <div class="dt-skeleton-head" style="grid-template-columns: 11% 14% 12% 13% 12% 13% 11% 8% 6%;">
                                        <span style="width:60%"></span>
                                        <span style="width:70%"></span>
                                        <span style="width:50%"></span>
                                        <span style="width:60%"></span>
                                        <span style="width:50%"></span>
                                        <span style="width:60%"></span>
                                        <span style="width:70%"></span>
                                        <span style="width:50%;justify-self:center"></span>
                                        <span style="width:40%;justify-self:center"></span>
                                    </div>
                                    <div class="dt-skeleton-body">
                                        @for ($i = 0; $i < 5; $i++)
                                            <div class="dt-skeleton-row" style="grid-template-columns: 11% 14% 12% 13% 12% 13% 11% 8% 6%;">
                                                <span class="skel-text" style="width:70%"></span>
                                                <span class="skel-text" style="width:80%"></span>
                                                <span class="skel-text" style="width:60%"></span>
                                                <span class="skel-text" style="width:90%"></span>
                                                <span class="skel-text" style="width:60%"></span>
                                                <span class="skel-text" style="width:90%"></span>
                                                <span class="skel-text" style="width:80%"></span>
                                                <span class="skel-badge" style="width:70%;justify-self:center"></span>
                                                <div style="display:flex;align-items:center;gap:6px;justify-content:center;">
                                                    <span class="skel-btn"></span>
                                                </div>
                                            </div>
                                        @endfor
                                    </div>
                                </div>
                                <table class="table table-hover" id="tableStockTransfer">
                                    <thead class="thead-light">
                                        <tr>
                                            <th style="width:10%">Tanggal</th>
                                            <th style="width:13%">Kode</th>
                                            <th style="width:11%">Pengirim</th>
                                            <th style="width:12%">Dari</th>
                                            <th style="width:11%">Penerima</th>
                                            <th style="width:12%">Ke</th>
                                            <th style="width:10%">ACC Kirim</th>
                                            <th class="text-center" style="width:8%">Selisih</th>
                                            <th class="text-center" style="width:8%">Status</th>
                                            <th class="no-sort text-center" style="width:5%">Aksi</th>
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
        window.currentStaff = {
            id: @json(Session::has('user') ? (int) (Session::get('user')->staff_id ?? 0) : 0),
            name: @json(Session::has('user') ? (string) (Session::get('user')->staff_name ?? '') : '')
        };
        @php
            $aw = $activeWarehouse ?? null;
            $awType = $aw && isset($aw->type) ? ($aw->type->warehouse_type_name ?? null) : null;
            $awName = $aw ? ($aw->warehouse_name ?? $aw->name ?? '') : '';
            $awLabel = ($awName && $awType) ? ($awName . ' (' . $awType . ')') : $awName;
        @endphp
        window.activeWarehouse = {
            id: @json($aw ? (int) $aw->id : 0),
            name: @json($awName),
            text: @json($awLabel)
        };
    </script>
    <script src="{{ asset('Custom_js/Backoffice/Inventory/Stock_Transfer.js') }}?v={{ time() }}"></script>
@endsection
