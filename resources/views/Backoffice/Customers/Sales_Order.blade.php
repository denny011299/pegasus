<?php $page = 'sales_order'; ?>
@extends('layout.mainlayout')
@section('custom_css')
    <link rel="stylesheet" href="{{ asset('assets/plugins/daterangepicker/daterangepicker.css') }}">
    <style>
        /* Theme default .tab-content padding-top: 32px — tighten gap under Pengiriman/Pengembalian tabs */
        .tab-content {
            padding-top: 0px !important;
            margin-top: 10px !important;
        }
        .daterangepicker {
            z-index: 1060 !important;
        }
        .sales-order-filter .cal-icon:after,
        .customer-return-filter .cal-icon:after {
            pointer-events: none;
        }

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

        /* Filter Card Styling */
        .sales-order-filter,
        .customer-return-filter {
            border: 1px solid #e2e8f0 !important;
            border-radius: 12px !important;
            background: #ffffff !important;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04) !important;
            margin-bottom: 16px !important;
        }
        .sales-order-filter .input-block label,
        .customer-return-filter .input-block label {
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 6px;
        }
        .sales-order-filter .form-control,
        .sales-order-filter .form-select,
        .customer-return-filter .form-control,
        .customer-return-filter .form-select {
            height: 40px !important;
            border-radius: 8px !important;
            border: 1.5px solid #e2e8f0 !important;
            font-size: 13px !important;
            color: #1e293b !important;
            background-color: #f8fafc !important;
        }
        .sales-order-filter .form-control:focus,
        .sales-order-filter .form-select:focus,
        .customer-return-filter .form-control:focus,
        .customer-return-filter .form-select:focus {
            border-color: #2563eb !important;
            background-color: #ffffff !important;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12) !important;
        }

        .sales-order-filter .btn-clear-so-filter,
        .customer-return-filter .btn-clear-cr-filter {
            height: 42px !important;
            min-height: 42px !important;
            max-height: 42px !important;
            padding: 0 12px !important;
            line-height: 1 !important;
            box-sizing: border-box !important;
        }

        #tableSalesOrder tbody td a.btn-action-icon,
        #tableCustomerReturn tbody td a.btn-action-icon {
            width: 34px !important;
            height: 34px !important;
            min-width: 34px !important;
            padding: 0 !important;
            border-radius: 8px !important;
        }

        /* DataTable styling — match standard Pegasus / Kanakku theme */
        #tableSalesOrder,
        #tableCustomerReturn {
            width: 100% !important;
            min-width: 950px;
            table-layout: fixed;
        }

        #tableSalesOrder th,
        #tableSalesOrder td,
        #tableCustomerReturn th,
        #tableCustomerReturn td {
            white-space: normal !important;
            word-wrap: break-word;
            vertical-align: middle;
            box-sizing: border-box;
        }

        #tableSalesOrder tbody td,
        #tableCustomerReturn tbody td {
            color: #475569;
            font-size: 13px;
        }

        #tableSalesOrder td:last-child,
        #tableSalesOrder th:last-child,
        #tableCustomerReturn td:last-child,
        #tableCustomerReturn th:last-child {
            white-space: nowrap !important;
            width: 110px !important;
            text-align: center;
        }

        #tableSalesOrder td:last-child a,
        #tableCustomerReturn td:last-child a {
            display: inline-flex !important;
            align-items: center;
        }

        #tableSalesOrder tbody tr,
        #tableCustomerReturn tbody tr {
            border-bottom: 1px solid #f1f5f9;
        }

        #tableSalesOrder-wrap,
        #tableCustomerReturn-wrap {
            position: relative;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow-x: auto;
            overflow-y: hidden;
        }

        #tableSalesOrder-wrap.is-loading tbody,
        #tableCustomerReturn-wrap.is-loading tbody {
            opacity: 0.45;
            pointer-events: none;
        }

        #tableSalesOrder_wrapper .dataTables_processing,
        #tableCustomerReturn_wrapper .dataTables_processing {
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

        #tableSalesOrder-wrap:not(.is-loading) .dataTables_processing,
        #tableCustomerReturn-wrap:not(.is-loading) .dataTables_processing {
            display: none !important;
        }

        #tableSalesOrder-wrap.is-loading .dataTables_processing,
        #tableCustomerReturn-wrap.is-loading .dataTables_processing {
            display: flex !important;
        }

        #tableSalesOrder_wrapper .dataTables_processing > div,
        #tableCustomerReturn_wrapper .dataTables_processing > div {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            border-radius: 10px;
            background: #fff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        }

        /* Select2 invalid (Armada / Gudang Eceran) */
        #add_sales_order .select2-container--default .select2-selection.is-invalids,
        #add_sales_order .select2-container--default .select2-selection--single.is-invalids,
        #row-RetailWarehouse .select2-container--default .select2-selection.is-invalids,
        #row-Armada .select2-container--default .select2-selection.is-invalids {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.15) !important;
        }

        #tableSalesModal .so-retail-warehouse + .select2-container {
            min-width: 200px;
        }

        #tableSalesModal .so-main-warehouse {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 7px 10px;
            border: 1px solid #dbeafe;
            border-radius: 8px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 600;
        }



        .csr-staff-icon {
            display: inline-flex;
            width: 32px;
            height: 32px;
            flex: 0 0 32px;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 14px;
        }
        .csr-staff-icon-success {
            border: 1px solid #a7f3d0;
            background: #ecfdf5;
            color: #059669;
        }
        .csr-staff { min-width: 0; }
        .csr-staff-name {
            display: block;
            max-width: 125px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        #customer-return-modal .modal-content {
            max-height: 94vh;
            overflow: hidden;
        }
        #customer-return-modal .modal-body { overflow-y: auto; }
        #customer-return-modal .modal-header,
        #customer-return-modal .modal-footer { flex-shrink: 0; }
        #customer-return-modal .select2-container { width: 100% !important; }
        #customer-return-modal .select2-selection--single {
            height: 38px !important;
            border-radius: 8px !important;
            display: flex;
            align-items: center;
        }
        #customer-return-modal .select2-selection__arrow { height: 36px !important; }
        #customer-return-modal .select2-selection.is-invalids {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 .2rem rgba(220, 53, 69, .15) !important;
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

            <div class="d-flex mb-2 overflow-x-auto" style="scrollbar-width: none; -ms-overflow-style: none;">
                <ul class="nav custom-premium-tabs flex-nowrap" id="customer-return-tabs" role="tablist">
                    <li class="nav-item flex-shrink-0" role="presentation">
                        <button class="nav-link active d-flex align-items-center gap-2 text-nowrap" id="shipping-tab" data-bs-toggle="tab"
                            data-bs-target="#shipping-pane" type="button" role="tab">
                            <i class="fe fe-truck"></i> Pengiriman
                        </button>
                    </li>
                    <li class="nav-item flex-shrink-0" role="presentation">
                        <button class="nav-link d-flex align-items-center gap-2 text-nowrap" id="customer-return-tab" data-bs-toggle="tab"
                            data-bs-target="#customer-return-pane" type="button" role="tab">
                            <i class="fe fe-rotate-ccw"></i> Pengembalian
                        </button>
                    </li>
                </ul>
            </div>

            {{-- Filter di bawah tabs (sama pola Product Issues); kartu SO/CR di-toggle per tab --}}
            @component('components.search-filter')
            @endcomponent

            <div class="tab-content">
                <div class="tab-pane fade show active" id="shipping-pane" role="tabpanel">
            <!-- Table -->
            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive position-relative dt-pending" id="tableSalesOrder-wrap">
                                <div class="dt-skeleton" aria-hidden="true">
                                    <div style="padding: 16px 25px;">
                                        <span class="skel-text" style="width: 250px; height: 38px; border-radius: 20px;"></span>
                                    </div>
                                    <div class="dt-skeleton-head" style="grid-template-columns: 17% 11% 10% 15% 15% 11% 11% 10%;">
                                        <span style="width:50%"></span>
                                        <span style="width:60%"></span>
                                        <span style="width:50%"></span>
                                        <span style="width:60%"></span>
                                        <span style="width:50%"></span>
                                        <span style="width:60%"></span>
                                        <span style="width:60%"></span>
                                        <span style="width:50%"></span>
                                    </div>
                                    <div class="dt-skeleton-body">
                                        @for ($i = 0; $i < 5; $i++)
                                            <div class="dt-skeleton-row" style="grid-template-columns: 17% 11% 10% 15% 15% 11% 11% 10%;">
                                                <span class="skel-text" style="width:70%"></span>
                                                <span class="skel-text" style="width:70%"></span>
                                                <span class="skel-text" style="width:60%"></span>
                                                <span class="skel-text" style="width:80%"></span>
                                                <span class="skel-badge" style="width:60%;justify-self:center"></span>
                                                <span class="skel-text" style="width:60%"></span>
                                                <span class="skel-text" style="width:60%"></span>
                                                <span class="skel-text" style="width:50%"></span>
                                            </div>
                                        @endfor
                                    </div>
                                </div>
                                <table class="table table-center table-hover" id="tableSalesOrder">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Nama Armada</th>
                                            <th>Tanggal</th>
                                            <th class="text-center">No. Invoice</th>
                                            <th class="text-center">No. Referensi</th>
                                            <th class="text-center">Status</th>
                                            <th>Dibuat Oleh</th>
                                            <th>Disetujui Oleh</th>
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

                <div class="tab-pane fade" id="customer-return-pane" role="tabpanel">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="card-table">
                                <div class="card-body">
                                    <div class="table-responsive position-relative dt-pending" id="tableCustomerReturn-wrap">
                                <div class="dt-skeleton" aria-hidden="true">
                                    <div style="padding:16px 25px;">
                                        <span class="skel-text" style="width:250px;height:38px;border-radius:20px;"></span>
                                    </div>
                                    <div class="dt-skeleton-head" style="grid-template-columns: 11% 12% 12% 12% 14% 11% 12% 12% 9%;">
                                        @for ($i = 0; $i < 9; $i++)
                                            <span style="width:55%;height:12px;border-radius:6px;"></span>
                                        @endfor
                                    </div>
                                    <div class="dt-skeleton-body">
                                        @for ($row = 0; $row < 5; $row++)
                                            <div class="dt-skeleton-row" style="grid-template-columns: 11% 12% 12% 12% 14% 11% 12% 12% 9%;">
                                                @for ($col = 0; $col < 9; $col++)
                                                    <span class="skel-text" style="width:65%;height:14px;border-radius:6px;"></span>
                                                @endfor
                                            </div>
                                        @endfor
                                    </div>
                                </div>
                                <table class="table table-center table-hover" id="tableCustomerReturn">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Nomor</th>
                                            <th>Tanggal</th>
                                            <th class="text-center">Tipe</th>
                                            <th>No. Ref</th>
                                            <th>Armada</th>
                                            <th class="text-center">Status</th>
                                            <th>Dibuat Oleh</th>
                                            <th>Diproses Oleh</th>
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

        </div>
    </div>
    <!-- /Page Wrapper -->

    @include('components.modals.customer-return.add-customer-return')

@endsection

@section('custom_js')
    <script>
        var public = "{{ asset('') }}";

        $(document).ready(function() {
            function syncHeaderButtons(targetId) {
                $('#btn-container-pengiriman').toggle(targetId === 'shipping-tab');
                $('#btn-container-pengembalian').toggle(targetId === 'customer-return-tab');
            }
            syncHeaderButtons('shipping-tab');
            $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
                syncHeaderButtons($(e.target).attr('id'));
            });
        });
    </script>
    <script src="{{asset('Custom_js/Backoffice/Customers/Sales_Order.js')}}?v={{time()}}"></script>
    <script src="{{asset('Custom_js/Backoffice/Customers/Customer_Return.js')}}?v={{time()}}"></script>
@endsection
