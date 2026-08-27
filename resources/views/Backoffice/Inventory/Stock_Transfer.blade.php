<?php $page = 'stock_transfer'; ?>
@extends('layout.mainlayout')
@section('custom_css')
    <link rel="stylesheet" href="{{ asset('assets/plugins/daterangepicker/daterangepicker.css') }}">
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
        /* Satu tabel (tanpa clone scrollX) — header & body ikut scroll yang sama */
        #tableStockTransfer {
            width: 100% !important;
            min-width: 1300px;
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
        .stock-transfer-filter .form-control,
        .stock-transfer-filter .form-select {
            height: 42px !important;
            border-radius: 8px !important;
            border-color: #cbd5e1 !important;
            font-size: 13px !important;
        }
        .stock-transfer-filter .select2-container .select2-selection--single {
            height: 42px !important;
            border-radius: 8px !important;
            border-color: #cbd5e1 !important;
            display: flex;
            align-items: center;
        }
        .stock-transfer-filter .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 40px !important;
            font-weight: 500;
            color: #1e293b;
            font-size: 13px;
        }
        .stock-transfer-filter .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px !important;
        }
        .stock-transfer-filter .input-block label {
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 6px;
        }
        .stock-transfer-filter .btn-clear-st-filter {
            height: 42px !important;
            border-radius: 8px !important;
            border-color: #cbd5e1 !important;
            color: #475569 !important;
            background: #ffffff !important;
            font-weight: 600 !important;
            transition: all 0.2s ease-in-out;
        }
        .stock-transfer-filter .btn-clear-st-filter:hover {
            background: #f1f5f9 !important;
            border-color: #94a3b8 !important;
            color: #0f172a !important;
        }
        .daterangepicker {
            z-index: 1060 !important;
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
            width: 18%;
            max-width: 220px;
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
            line-height: 1.35;
            font-size: 12px;
            color: #334155;
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
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            border-left: none;
            border-right: none;
            border-radius: 0;
            padding: 10px 24px;
        }
        #add_stock_transfer .transfer-product-grid {
            display: grid;
            grid-template-columns: minmax(240px, 2fr) minmax(80px, .45fr) minmax(160px, 1fr) auto;
            gap: 10px;
            align-items: end;
        }
        #add_stock_transfer .transfer-product-field label {
            display: block;
            margin-bottom: 4px;
            color: #475569;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: .4px;
            text-transform: uppercase;
        }
        #add_stock_transfer .transfer-product-field .form-control,
        #add_stock_transfer .transfer-product-field .form-select {
            height: 36px;
            min-height: 36px;
            border-radius: 6px;
            font-size: 12.5px;
            border-color: #cbd5e1;
        }
        #add_stock_transfer .transfer-product-actions {
            display: flex;
            gap: 6px;
            align-items: center;
        }
        #add_stock_transfer .transfer-product-actions .btn {
            height: 36px;
            border-radius: 6px;
            white-space: nowrap;
            font-size: 12.5px;
            font-weight: 600;
            padding: 0 14px;
        }
        #add_stock_transfer .st-route-row .form-control,
        #add_stock_transfer .st-route-row .form-select,
        #add_stock_transfer .st-route-row .select2-container .select2-selection {
            height: 34px !important;
            min-height: 34px !important;
            border-radius: 6px !important;
            font-size: 12.5px !important;
        }
        #add_stock_transfer .st-route-row .select2-container .select2-selection__rendered {
            line-height: 32px !important;
            font-size: 12.5px !important;
        }
        #add_stock_transfer .st-route-row .select2-container .select2-selection__arrow {
            height: 32px !important;
        }
        #add_stock_transfer #btn_add_transfer_product {
            background: linear-gradient(135deg, #1e40af, #2563eb) !important;
            border: none !important;
            color: #ffffff !important;
            box-shadow: 0 4px 10px rgba(37,99,235,0.25) !important;
            transition: all 0.2s ease-in-out;
        }
        #add_stock_transfer #btn_add_transfer_product:hover,
        #add_stock_transfer #btn_add_transfer_product:focus,
        #add_stock_transfer #btn_add_transfer_product:active {
            background: linear-gradient(135deg, #1d4ed8, #1e40af) !important;
            color: #ffffff !important;
            box-shadow: 0 6px 14px rgba(37,99,235,0.35) !important;
            transform: translateY(-1px);
        }
        #add_stock_transfer #btn_toggle_scan_transfer {
            background: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            color: #475569 !important;
            transition: all 0.2s ease-in-out;
        }
        #add_stock_transfer #btn_toggle_scan_transfer:hover,
        #add_stock_transfer #btn_toggle_scan_transfer:focus,
        #add_stock_transfer #btn_toggle_scan_transfer:active {
            background: #f1f5f9 !important;
            border-color: #94a3b8 !important;
            color: #0f172a !important;
        }
        #add_stock_transfer #btn_scan_add_transfer:hover,
        #add_stock_transfer #btn_scan_add_transfer:focus {
            background: #1d4ed8 !important;
            border-color: #1d4ed8 !important;
            color: #ffffff !important;
        }
        #add_stock_transfer button[data-bs-target="#collapseStockTransferForm"]:hover {
            background: #e2e8f0 !important;
            color: #0f172a !important;
        }
        #add_stock_transfer .pg-btn-save:hover,
        #add_stock_transfer .btn-save-transfer:hover {
            background: #1d4ed8 !important;
            border-color: #1d4ed8 !important;
            color: #ffffff !important;
        }
        #add_stock_transfer .pg-btn-cancel:hover,
        #add_stock_transfer .btn-cancel-transfer:hover {
            background: #f1f5f9 !important;
            border-color: #cbd5e1 !important;
            color: #334155 !important;
        }
        #add_stock_transfer #tableTransferItems thead th,
        #view_stock_transfer #tableViewItems thead th,
        #accept_stock_transfer #tableAcceptItems thead th {
            background: #ffffff !important;
            color: #1e3a8a !important;
            font-size: 11px !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
            padding: 16px 18px !important;
            border-bottom: 2px solid #e2e8f0 !important;
            border-top: 0 !important;
            white-space: nowrap !important;
            vertical-align: middle !important;
        }
        #add_stock_transfer #tableTransferItems tbody td,
        #view_stock_transfer #tableViewItems tbody td,
        #accept_stock_transfer #tableAcceptItems tbody td {
            padding: 14px 18px !important;
            vertical-align: middle !important;
            font-size: 13px !important;
        }
        #add_stock_transfer #tableTransferItems tbody tr:hover,
        #view_stock_transfer #tableViewItems tbody tr:hover,
        #accept_stock_transfer #tableAcceptItems tbody tr:hover {
            background-color: #f8fafc !important;
            color: inherit !important;
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
                                    <div class="dt-skeleton-head" style="grid-template-columns: 10% 10% 11% 11% 11% 12% 12% 9% 6%;">
                                        <span style="width:60%"></span>
                                        <span style="width:70%"></span>
                                        <span style="width:50%"></span>
                                        <span style="width:50%"></span>
                                        <span style="width:50%"></span>
                                        <span style="width:60%"></span>
                                        <span style="width:60%"></span>
                                        <span style="width:50%;justify-self:center"></span>
                                        <span style="width:40%;justify-self:center"></span>
                                    </div>
                                    <div class="dt-skeleton-body">
                                        @for ($i = 0; $i < 5; $i++)
                                            <div class="dt-skeleton-row" style="grid-template-columns: 10% 10% 11% 11% 11% 12% 12% 9% 6%;">
                                                <span class="skel-text" style="width:70%"></span>
                                                <span class="skel-text" style="width:80%"></span>
                                                <span class="skel-text" style="width:60%"></span>
                                                <span class="skel-text" style="width:60%"></span>
                                                <span class="skel-text" style="width:60%"></span>
                                                <span class="skel-text" style="width:90%"></span>
                                                <span class="skel-text" style="width:90%"></span>
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
                                            <th style="width:10%">Kode</th>
                                            <th style="width:11%">Request</th>
                                            <th style="width:11%">Pengirim</th>
                                            <th style="width:11%">Penerima</th>
                                            <th style="width:12%">Dari</th>
                                            <th style="width:12%">Ke</th>
                                            <th class="text-center" style="width:9%">Status</th>
                                            <th class="no-sort text-center" style="width:6%">Aksi</th>
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
