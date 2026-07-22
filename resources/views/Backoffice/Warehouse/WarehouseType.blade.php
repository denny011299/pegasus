<?php $page = 'warehouse-type'; ?>
@extends('layout.mainlayout')

@section('custom_css')
<style>
    #warehouse-type-table-wrap.dt-pending #tableWarehouseType,
    #warehouse-type-table-wrap.dt-pending #tableWarehouseType_wrapper {
        display: none !important;
    }
    #warehouse-type-table-wrap.dt-ready .dt-skeleton {
        display: none !important;
    }

    .dt-skeleton {
        width: 100%;
        overflow: hidden;
        border-radius: 8px;
    }
    .dt-skeleton-head {
        display: grid;
        grid-template-columns: 40% 20% 25% 15%;
        gap: 0;
        background: linear-gradient(90deg, #eff6ff 0%, #e0f2fe 100%);
        border-bottom: 2px solid #bfdbfe;
        border-radius: 8px 8px 0 0;
        padding: 16px 25px;
    }
    .dt-skeleton-head span {
        height: 12px;
        border-radius: 6px;
        background: rgba(30, 64, 175, 0.15);
    }
    .dt-skeleton-body {
        padding: 0 25px 8px;
    }
    .dt-skeleton-row {
        display: grid;
        grid-template-columns: 40% 20% 25% 15%;
        gap: 0;
        align-items: center;
        min-height: 65px;
        border-bottom: 1px solid #f1f5f9;
    }
    .dt-skeleton-row span {
        background: #e2e8f0;
        background-image: linear-gradient(90deg, #e2e8f0 0%, #f1f5f9 40%, #e2e8f0 80%);
        background-size: 200% 100%;
        animation: dt-shimmer 1.5s ease-in-out infinite;
        display: inline-block;
    }
    .skel-icon { width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0; }
    .skel-avatar { width: 24px; height: 24px; border-radius: 50%; flex-shrink: 0; }
    .skel-badge { height: 26px; border-radius: 20px; }
    .skel-btn { width: 32px; height: 32px; border-radius: 8px; }
    .skel-text { height: 14px; border-radius: 6px; }

    .dt-skeleton-row:nth-child(2) span { animation-delay: 0.1s; }
    .dt-skeleton-row:nth-child(3) span { animation-delay: 0.2s; }
    .dt-skeleton-row:nth-child(4) span { animation-delay: 0.3s; }
    .dt-skeleton-row:nth-child(5) span { animation-delay: 0.4s; }

    @keyframes dt-shimmer {
        0% { background-position: 100% 0; }
        100% { background-position: -100% 0; }
    }

    #tableWarehouseType {
        width: 100% !important;
        table-layout: auto;
    }
    #tableWarehouseType th,
    #tableWarehouseType td {
        vertical-align: middle !important;
        box-sizing: border-box;
    }
    #tableWarehouseType td {
        white-space: normal !important;
        word-wrap: break-word;
    }
    #tableWarehouseType td:last-child,
    #tableWarehouseType th:last-child {
        white-space: nowrap !important;
        width: 1%;
    }
    #tableWarehouseType td:last-child a {
        display: inline-flex !important;
        align-items: center;
    }
</style>
@endsection

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        @component('components.page-header')
            @slot('title') Tipe Gudang @endslot
        @endcomponent

        @component('components.search-filter')
        @endcomponent

        <div class="row">
            <div class="col-sm-12">
                <div class="card-table">
                    <div class="card-body">
                        <div class="table-responsive dt-pending" id="warehouse-type-table-wrap">
                            <div class="dt-skeleton" aria-hidden="true">
                                <div class="dt-skeleton-head">
                                    <span style="width:55%"></span>
                                    <span style="width:50%"></span>
                                    <span style="width:50%"></span>
                                    <span style="width:35%;justify-self:center"></span>
                                </div>
                                <div class="dt-skeleton-body">
                                    @for ($i = 0; $i < 5; $i++)
                                        <div class="dt-skeleton-row">
                                            <div style="display:flex;align-items:center;gap:12px;">
                                                <span class="skel-icon"></span>
                                                <span class="skel-badge" style="width:50%"></span>
                                            </div>
                                            <span class="skel-text" style="width:60%"></span>
                                            <div style="display:flex;align-items:center;gap:8px;">
                                                <span class="skel-avatar"></span>
                                                <span class="skel-text" style="width:70%"></span>
                                            </div>
                                            <div style="display:flex;align-items:center;gap:6px;justify-content:center;">
                                                <span class="skel-btn"></span>
                                                <span class="skel-btn"></span>
                                            </div>
                                        </div>
                                    @endfor
                                </div>
                            </div>

                            <table class="table table-hover" id="tableWarehouseType">
                                <thead>
                                    <tr>
                                        <th class="text-start">Nama Tipe</th>
                                        <th class="text-start">Dibuat Pada</th>
                                        <th class="text-start">Dibuat Oleh</th>
                                        <th class="no-sort text-center">Aksi</th>
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
<script src="{{ asset('Custom_js/Backoffice/Warehouse/WarehouseType.js') }}"></script>
@endsection
