<?php $page = 'stock_transfer'; ?>
@extends('layout.mainlayout')
@section('custom_css')
    {{-- GEMINI: isi style modal / tabel transfer di sini bila perlu --}}
    <style>
        #tableStockTransfer {
            width: 100% !important;
        }
        #tableStockTransfer td:last-child {
            white-space: nowrap !important;
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
                                    <div class="dt-skeleton-head" style="grid-template-columns: 12% 15% 15% 15% 15% 15% 8% 5%;">
                                        <span style="width:60%"></span>
                                        <span style="width:70%"></span>
                                        <span style="width:50%"></span>
                                        <span style="width:60%"></span>
                                        <span style="width:50%"></span>
                                        <span style="width:60%"></span>
                                        <span style="width:70%"></span>
                                        <span style="width:40%;justify-self:center"></span>
                                    </div>
                                    <div class="dt-skeleton-body">
                                        @for ($i = 0; $i < 5; $i++)
                                            <div class="dt-skeleton-row" style="grid-template-columns: 12% 15% 15% 15% 15% 15% 8% 5%;">
                                                <span class="skel-text" style="width:70%"></span>
                                                <span class="skel-text" style="width:80%"></span>
                                                <span class="skel-text" style="width:60%"></span>
                                                <span class="skel-text" style="width:90%"></span>
                                                <span class="skel-text" style="width:60%"></span>
                                                <span class="skel-text" style="width:90%"></span>
                                                <span class="skel-text" style="width:80%"></span>
                                                <div style="display:flex;align-items:center;gap:6px;justify-content:center;">
                                                    <span class="skel-btn"></span>
                                                </div>
                                            </div>
                                        @endfor
                                    </div>
                                </div>
                                <table class="table table-center table-hover" id="tableStockTransfer">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Kode</th>
                                            <th>Pengirim</th>
                                            <th>Dari</th>
                                            <th>Penerima</th>
                                            <th>Ke</th>
                                            <th>Status</th>
                                            <th class="no-sort">Aksi</th>
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
