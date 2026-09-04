<?php $page = 'stock_opname'; ?>
@extends('layout.mainlayout')
@section('custom_css')
    <style>
        #tableStockOpname {
            width: 100% !important;
        }

        #tableStockOpname td:last-child {
            white-space: nowrap !important;
        }

        #tableStockOpname td:last-child a {
            display: inline-flex !important;
            align-items: center;
        }

        #tableStockOpname-wrap {
            position: relative;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow-x: auto;
        }

        #tableStockOpname_wrapper .dataTables_processing {
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
            align-items: center;
            justify-content: center;
            color: #1e293b;
            font-weight: 600;
            font-size: 14px;
        }

        #tableStockOpname-wrap:not(.is-loading) .dataTables_processing {
            display: none !important;
        }

        #tableStockOpname-wrap.is-loading .dataTables_processing {
            display: flex !important;
        }

        #tableStockOpname-wrap.is-loading tbody {
            opacity: 0.45;
            pointer-events: none;
        }

        #tableStockOpname_wrapper .dataTables_processing > div {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            border-radius: 10px;
            background: #fff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        }
    </style>
@endsection
@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">

            @component('components.page-header')
                @slot('title')
                    Stok Opname Produk
                @endslot
            @endcomponent

            @component('components.search-filter')
            @endcomponent

            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive dt-pending" id="tableStockOpname-wrap">
                                <div class="dt-skeleton" aria-hidden="true">
                                    <div style="padding: 16px 25px;">
                                        <span class="skel-text" style="width: 250px; height: 38px; border-radius: 20px;"></span>
                                    </div>
                                    <div class="dt-skeleton-head" style="grid-template-columns: 12% 18% 12% 10% 12% 14% 10% 12%;">
                                        <span style="width:70%"></span>
                                        <span style="width:80%"></span>
                                        <span style="width:75%"></span>
                                        <span style="width:60%"></span>
                                        <span style="width:70%"></span>
                                        <span style="width:65%"></span>
                                        <span style="width:55%"></span>
                                        <span style="width:40%;justify-self:center"></span>
                                    </div>
                                    <div class="dt-skeleton-body">
                                        @for ($i = 0; $i < 5; $i++)
                                            <div class="dt-skeleton-row" style="grid-template-columns: 12% 18% 12% 10% 12% 14% 10% 12%;">
                                                <span class="skel-text" style="width:75%"></span>
                                                <span class="skel-text" style="width:85%"></span>
                                                <span class="skel-text" style="width:80%"></span>
                                                <span class="skel-text" style="width:65%"></span>
                                                <div style="display:flex;align-items:center;gap:8px;">
                                                    <span class="skel-avatar"></span>
                                                    <span class="skel-text" style="width:65%"></span>
                                                </div>
                                                <span class="skel-text" style="width:70%"></span>
                                                <span class="skel-badge" style="width:70%;justify-self:center"></span>
                                                <div style="display:flex;align-items:center;gap:6px;justify-content:center;">
                                                    <span class="skel-btn"></span>
                                                    <span class="skel-btn"></span>
                                                </div>
                                            </div>
                                        @endfor
                                    </div>
                                </div>
                                <table class="table table-center table-hover" id="tableStockOpname">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Tanggal Opname</th>
                                            <th>Gudang</th>
                                            <th>Penanggung Jawab</th>
                                            <th>ID Opname</th>
                                            <th>Dibuat Oleh</th>
                                            <th>Diapprove/Ditolak Oleh</th>
                                            <th>Jenis</th>
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
        var public = "{{ asset('') }}";
    </script>
    <script src="{{ asset('Custom_js/Backoffice/Inventory/Stock_Opname.js') }}?v=2"></script>
@endsection
