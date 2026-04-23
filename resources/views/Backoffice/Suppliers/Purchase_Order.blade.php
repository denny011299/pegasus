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
                            
                            <div class="table-responsive">
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
    <script src="{{asset('Custom_js/Backoffice/Suppliers/Purchase_Order.js')}}?v=1"></script>
@endsection
