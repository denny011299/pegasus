<?php $page = 'sales_order'; ?>
@extends('layout.mainlayout')
@section('custom_css')
    <style>
        #add_sales_order #so_qty_input {
            text-align: center;
        }

        #add_sales_order #so_unit_input {
            min-height: 38px;
        }

        /* Khusus modal Sales Order: body tabel scroll, header tetap terlihat */
        #add_sales_order .col-12.overflow-x-auto.mb-3 {
            max-height: 320px;
            overflow-y: auto;
            overflow-x: auto;
        }

        #add_sales_order .col-12.overflow-x-auto.mb-3 thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: #dce8f6;
        }

        #tableSalesOrder {
            width: 100% !important;
            table-layout: fixed;
        }

        #tableSalesOrder th,
        #tableSalesOrder td {
            white-space: normal !important;
            word-wrap: break-word;
            vertical-align: middle;
            box-sizing: border-box;
        }

        #tableSalesOrder thead th {
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .4px;
            background: #f1f5f9;
            border-bottom: 1px solid #e2e8f0;
        }

        #tableSalesOrder tbody td {
            color: #475569;
            font-size: 13px;
        }

        #tableSalesOrder td:last-child,
        #tableSalesOrder th:last-child {
            white-space: nowrap !important;
            width: 100px !important;
        }

        #tableSalesOrder td:last-child a {
            display: inline-flex !important;
            align-items: center;
        }
        
        #tableSalesOrder tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.2s ease;
        }

        /* Cegah DataTables scrollX clone header yang desync */
        #tableSalesOrder_wrapper .dataTables_scrollHead,
        #tableSalesOrder_wrapper .dataTables_scrollBody {
            width: 100% !important;
        }

        #tableSalesOrder-wrap {
            position: relative;
        }

        #tableSalesOrder_wrapper .dataTables_processing {
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

        #tableSalesOrder_wrapper .dataTables_processing > div {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            border-radius: 10px;
            background: #fff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        }

        #tableSalesOrder-wrap.is-loading tbody {
            opacity: 0.45;
            pointer-events: none;
        }

        /* Select2 invalid (Armada / Gudang Eceran) */
        #add_sales_order .select2-container--default .select2-selection.is-invalids,
        #add_sales_order .select2-container--default .select2-selection--single.is-invalids,
        #row-RetailWarehouse .select2-container--default .select2-selection.is-invalids,
        #row-Armada .select2-container--default .select2-selection.is-invalids {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.15) !important;
        }
    </style>
@endsection
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    Pengiriman
                @endslot
            @endcomponent
            <!-- /Page Header -->

            <!-- Search Filter -->
            @component('components.search-filter')
            @endcomponent
            <!-- /Search Filter -->

            <!-- Table -->
            <div class="row">
                <div class="col-sm-12">
                    <div class=" card-table">
                        <div class="card-body">
                            <div class="table-responsive dt-pending" id="tableSalesOrder-wrap" style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
                                <div class="dt-skeleton" aria-hidden="true">
                                    <div style="padding: 16px 25px;">
                                        <span class="skel-text" style="width: 250px; height: 38px; border-radius: 20px;"></span>
                                    </div>
                                    <div class="dt-skeleton-head" style="grid-template-columns: 15% 13% 12% 12% 15% 15% 15% 15%;">
                                        <span style="width:40%"></span>
                                        <span style="width:60%"></span>
                                        <span style="width:50%"></span>
                                        <span style="width:40%"></span>
                                        <span style="width:50%"></span>
                                        <span style="width:70%"></span>
                                        <span style="width:70%"></span>
                                        <span style="width:50%"></span>
                                    </div>
                                    <div class="dt-skeleton-body">
                                        @for ($i = 0; $i < 5; $i++)
                                            <div class="dt-skeleton-row" style="grid-template-columns: 15% 13% 12% 12% 15% 15% 15% 15%;">
                                                <span class="skel-text" style="width:60%"></span>
                                                <span class="skel-text" style="width:80%"></span>
                                                <span class="skel-text" style="width:70%"></span>
                                                <span class="skel-text" style="width:60%"></span>
                                                <span class="skel-badge" style="width:60%;justify-self:center"></span>
                                                <span class="skel-text" style="width:70%"></span>
                                                <span class="skel-text" style="width:70%"></span>
                                                <span class="skel-text" style="width:50%"></span>
                                            </div>
                                        @endfor
                                    </div>
                                </div>
                                <table class="table table-center table-hover mb-0" id="tableSalesOrder">
                                    <thead>
                                        <tr>
                                            <th>Nama Armada</th>
                                            <th>Tanggal</th>
                                            <th class="text-center">No. Invoice</th>
                                            <th class="text-center">Ref Number</th>
                                            <th class="text-center">Status</th>
                                            <th>Dibuat Oleh</th>
                                            <th>Diapprove/Ditolak Oleh</th>
                                            <th class="no-sort text-center">Aksi</th>
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
            <!-- /Table -->

        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection

@section('custom_js')
    <script>
        var public = "{{ asset('') }}";    
    </script>
    <script src="{{asset('Custom_js/Backoffice/Customers/Sales_Order.js')}}?v={{time()}}"></script>
@endsection