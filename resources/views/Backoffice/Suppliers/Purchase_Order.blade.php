<?php $page = 'purchase_order'; ?>
@extends('layout.mainlayout')
@section('content')
<style>
    .badgeStatus{
        font-size:9pt!important;
    }
    .invalid{
        border: 1px solid red!important;
    }
    table {
        table-layout: auto;
    }
    .table-po-wrap {
        overflow-x: auto;
    }

    #tablePurchaseOrder td {
        white-space: normal !important;
        word-wrap: break-word;
        vertical-align: middle;
    }

    /* Kolom yang tidak perlu wrap */
    #tablePurchaseOrder td:nth-child(1), /* Tanggal */
    #tablePurchaseOrder td:nth-child(2), /* No. PO */
    #tablePurchaseOrder td:nth-child(3), /* No. Invoice */
    #tablePurchaseOrder td:nth-child(6), /* Total */
    #tablePurchaseOrder td:nth-child(7), /* Status */
    #tablePurchaseOrder td:last-child {  /* Aksi */
        white-space: nowrap !important;
    }

    #tablePurchaseOrder td:last-child a {
        display: inline-flex !important;
        align-items: center;
    }
    
    .qty-cell-inner {
        display: flex;
        gap: 4px;
        align-items: center;
        flex-wrap: nowrap; /* ini penting */
    }
    
    .qty-cell-inner input {
        min-width: 40px;
        max-width: 60px; /* Reduce from 150px */
        width: 60px;
        flex-shrink: 0; /* Prevent shrinking */
    }

    .qty-cell-inner select {
        min-width: 70px;
        max-width: 100px; /* Reduce from 220px */
        width: 100px;
        flex-shrink: 0; /* Prevent shrinking */
    }

    /* Khusus modal Purchase Order: body tabel scroll, header tetap terlihat */
    #add_purchase_order .col-12.overflow-x-auto.mb-3 {
        max-height: 320px;
        overflow-y: auto;
        overflow-x: auto;
    }
    #add_purchase_order .col-12.overflow-x-auto.mb-3 thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: #dce8f6;
    }

    #tablePurchaseOrder-wrap {
        position: relative;
        border: none !important;
        border-radius: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
    }

    #tablePurchaseOrder-wrap .dt-skeleton {
        border-radius: 0;
        background: transparent;
        box-shadow: none;
    }

    #tablePurchaseOrder_wrapper .dataTables_processing {
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

    #tablePurchaseOrder-wrap:not(.is-loading) .dataTables_processing {
        display: none !important;
    }

    #tablePurchaseOrder-wrap.is-loading .dataTables_processing {
        display: flex !important;
    }

    #tablePurchaseOrder_wrapper .dataTables_processing > div {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 10px 16px;
        border-radius: 10px;
        background: #fff;
        border: 1px solid #e2e8f0;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
    }

    #tablePurchaseOrder-wrap.is-loading tbody {
        opacity: 0.45;
        pointer-events: none;
    }
</style>
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    Pesanan Pembelian
                @endslot
            @endcomponent
            <!-- /Page Header -->

            <!-- Filter Pencarian -->
            @component('components.search-filter')
            @endcomponent
            <!-- /Filter Pencarian -->

            <!-- Tabel -->
            <div class="row">
                <div class="col-sm-12">
                    <div class=" card-table">
                        <div class="card-body">
                            
                            <div class="table-responsive dt-pending" id="tablePurchaseOrder-wrap">
                                <div class="dt-skeleton" aria-hidden="true">
                                    <div style="padding: 16px 25px;">
                                        <span class="skel-text" style="width: 250px; height: 38px; border-radius: 20px;"></span>
                                    </div>
                                    <div class="dt-skeleton-head" style="grid-template-columns: 8% 8% 8% 18% 25% 8% 7% 7% 7% 4%;">
                                        <span style="width:60%"></span>
                                        <span style="width:50%"></span>
                                        <span style="width:50%"></span>
                                        <span style="width:55%"></span>
                                        <span style="width:40%"></span>
                                        <span style="width:50%"></span>
                                        <span style="width:55%"></span>
                                        <span style="width:55%"></span>
                                        <span style="width:55%"></span>
                                        <span style="width:40%"></span>
                                    </div>
                                    <div class="dt-skeleton-body">
                                        @for ($i = 0; $i < 5; $i++)
                                            <div class="dt-skeleton-row" style="grid-template-columns: 8% 8% 8% 18% 25% 8% 7% 7% 7% 4%;">
                                                <span class="skel-text" style="width:70%"></span>
                                                <span class="skel-text" style="width:55%"></span>
                                                <span class="skel-text" style="width:55%"></span>
                                                <span class="skel-text" style="width:80%"></span>
                                                <span class="skel-text" style="width:85%"></span>
                                                <span class="skel-text" style="width:60%"></span>
                                                <span class="skel-badge" style="width:55%;justify-self:center"></span>
                                                <span class="skel-text" style="width:60%"></span>
                                                <span class="skel-text" style="width:60%"></span>
                                                <span class="skel-badge" style="width:40%;justify-self:center"></span>
                                            </div>
                                        @endfor
                                    </div>
                                </div>
                                <table class="table table-center table-hover" id="tablePurchaseOrder">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>No. PO</th>
                                            <th>No. Invoice</th>
                                            <th>Nama Pemasok</th>
                                            <th>Keterangan</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th>Dibuat Oleh</th>
                                            <th>Diapprove/Ditolak Oleh</th>
                                            <th class="no-sort">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Tabel -->

        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection

@section('custom_js')
    <script>
        var public = "{{ asset('') }}";    
    </script>
    <script src="{{asset('Custom_js/Backoffice/Suppliers/Purchase_Order.js')}}?v={{ time() }}"></script>
@endsection
