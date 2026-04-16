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

    #tablePurchaseModal {
        width: max-content;
        min-width: 100%;
    }
    #tablePurchaseModal td {
        white-space: normal;
        word-break: break-word;
        vertical-align: middle;
        overflow: hidden;
        overflow-x: auto;
    }
    #tablePurchaseModal td:nth-child(5),
    #tablePurchaseModal td:nth-child(6),
    #tablePurchaseModal td:nth-child(7) {
        white-space: nowrap;
        width: 1%;
    }
    #tablePurchaseModal td:nth-child(1),
    #tablePurchaseModal td:nth-child(2) {
        max-width: 200px;
        word-break: break-word;
    }
    .qty-cell-inner {
        display: flex;
        gap: 4px;
        align-items: center;
        flex-wrap: wrap; /* ini penting */
    }
    .qty-cell-inner input {
        min-width: 40px;
        max-width: 150px;
        width: 100%;
        flex: 1;
    }

    .qty-cell-inner select {
        min-width: 70px;
        max-width: 220px;
        width: 100%;
        flex: 1;
    }

    /* ← INI KUNCINYA: di layar sempit, tabel disembunyikan */
    /* dan diganti card list */
    @media (max-width: 600px) {
        #tablePurchaseModal thead { display: none; }
        #tablePurchaseModal, 
        #tablePurchaseModal tbody,
        #tablePurchaseModal tr,
        #tablePurchaseModal td { 
            display: block; 
            width: 100% !important;
        }
        #tablePurchaseModal tr {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 8px;
            padding: 8px;
        }
        #tablePurchaseModal td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 4px 8px;
            border: none;
        }
        #tablePurchaseModal td::before {
            content: attr(data-label);
            font-weight: 500;
            font-size: 12px;
            color: #6c757d;
            min-width: 80px;
        }
        .qty-cell-inner {
            justify-content: flex-end;
        }
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
