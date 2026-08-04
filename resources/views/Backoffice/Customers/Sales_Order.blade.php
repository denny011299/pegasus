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
            align-items: center;
            justify-content: center;
            color: #1e293b;
            font-weight: 600;
            font-size: 14px;
        }

        #tableSalesOrder-wrap:not(.is-loading) .dataTables_processing {
            display: none !important;
        }

        #tableSalesOrder-wrap.is-loading .dataTables_processing {
            display: flex !important;
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



        #tableCustomerSupplyReturn-wrap,
        #tableCustomerProductReturn-wrap {
            position: relative;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow-x: auto;
            overflow-y: hidden;
        }
        #tableCustomerProductReturn-wrap .dt-skeleton-head,
        #tableCustomerProductReturn-wrap .dt-skeleton-row {
            grid-template-columns: 11% 13% 13% 15% 11% 14% 14% 9%;
        }
        #tableCustomerProductReturn {
            width: 100% !important;
            min-width: 1200px;
            table-layout: auto;
        }
        #tableCustomerProductReturn th,
        #tableCustomerProductReturn td {
            vertical-align: middle !important;
            box-sizing: border-box;
        }
        #tableCustomerProductReturn thead th {
            padding: 14px 18px;
            color: #64748b;
            background: #f1f5f9;
            border-bottom: 1px solid #e2e8f0;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .4px;
            text-transform: uppercase;
            white-space: nowrap;
        }
        #tableCustomerProductReturn tbody td {
            padding: 14px 18px;
            color: #475569;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px;
            white-space: nowrap;
        }
        #tableCustomerProductReturn_wrapper {
            min-width: 1200px;
        }
        #tableCustomerProductReturn-wrap.is-loading tbody {
            opacity: .45;
            pointer-events: none;
        }
        #customer-product-return-modal .modal-content {
            max-height: 92vh;
            overflow: hidden;
        }
        #customer-product-return-modal .modal-body {
            overflow-y: auto;
        }
        #customer-product-return-modal .select2-container {
            width: 100% !important;
        }
        #customer-product-return-modal .select2-selection--single {
            height: 42px !important;
            border-radius: 8px !important;
            display: flex;
            align-items: center;
        }
        #customer-product-return-modal .select2-selection__arrow {
            height: 40px !important;
        }
        #customer-product-return-modal .select2-selection.is-invalids {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 .2rem rgba(220, 53, 69, .15) !important;
        }
        #tableCustomerSupplyReturn-wrap .dt-skeleton-head,
        #tableCustomerSupplyReturn-wrap .dt-skeleton-row {
            grid-template-columns: 11% 13% 13% 15% 11% 14% 14% 9%;
        }
        #tableCustomerSupplyReturn-wrap .csr-skeleton-center {
            justify-self: center;
        }
        #tableCustomerSupplyReturn-wrap .dt-skeleton span {
            display: inline-block;
            background: #e2e8f0;
            background-image: linear-gradient(90deg, #e2e8f0 0%, #f8fafc 40%, #e2e8f0 80%);
            background-size: 200% 100%;
            animation: csr-shimmer 1.5s ease-in-out infinite;
        }
        @keyframes csr-shimmer {
            0% { background-position: 100% 0; }
            100% { background-position: -100% 0; }
        }
        #tableCustomerSupplyReturn {
            width: 100% !important;
            min-width: 1200px;
            table-layout: auto;
        }
        #tableCustomerSupplyReturn th,
        #tableCustomerSupplyReturn td {
            vertical-align: middle !important;
            box-sizing: border-box;
        }
        #tableCustomerSupplyReturn thead th {
            padding: 14px 18px;
            color: #64748b;
            background: #f1f5f9;
            border-bottom: 1px solid #e2e8f0;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .4px;
            text-transform: uppercase;
            white-space: nowrap;
        }
        #tableCustomerSupplyReturn tbody td {
            padding: 14px 18px;
            color: #475569;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px;
            white-space: nowrap;
        }
        #tableCustomerSupplyReturn tbody tr {
            transition: background-color .2s ease;
        }
        #tableCustomerSupplyReturn tbody tr:hover {
            background: #f8fafc;
        }
        #tableCustomerSupplyReturn td:last-child,
        #tableCustomerSupplyReturn th:last-child {
            white-space: nowrap;
        }
        #tableCustomerSupplyReturn .btn-action-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            justify-content: center;
        }
        #tableCustomerSupplyReturn_wrapper {
            min-width: 1200px;
        }
        #tableCustomerSupplyReturn_wrapper .dataTables_processing {
            position: absolute !important;
            inset: 0 !important;
            width: 100% !important;
            height: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            border: 0 !important;
            border-radius: 8px;
            background: rgba(255, 255, 255, .72) !important;
            box-shadow: none !important;
            z-index: 20;
            align-items: center;
            justify-content: center;
            color: #1e293b;
            font-size: 14px;
            font-weight: 600;
        }
        #tableCustomerSupplyReturn-wrap:not(.is-loading) .dataTables_processing {
            display: none !important;
        }
        #tableCustomerSupplyReturn-wrap.is-loading .dataTables_processing {
            display: flex !important;
        }
        #tableCustomerSupplyReturn_wrapper .dataTables_processing > div {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .08);
        }
        #tableCustomerSupplyReturn-wrap.is-loading tbody {
            opacity: .45;
            pointer-events: none;
        }
        #tableCustomerSupplyReturn .dataTables_empty {
            height: 110px;
            color: #94a3b8;
            text-align: center;
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
        .csr-staff {
            min-width: 0;
        }
        .csr-staff-name {
            display: block;
            max-width: 125px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .csr-origin-summary {
            display: block;
            max-width: 230px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            cursor: help;
        }
        #csr-supply + .select2-container .select2-selection__rendered {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        #customer-supply-return-modal .modal-content {
            max-height: 92vh;
            overflow: hidden;
        }
        #customer-supply-return-modal .modal-body {
            overflow-y: auto;
        }
        #customer-supply-return-modal .modal-header,
        #customer-supply-return-modal .modal-footer {
            flex-shrink: 0;
        }
        #customer-supply-return-modal .select2-container {
            width: 100% !important;
        }
        #customer-supply-return-modal .select2-selection--single {
            height: 42px !important;
            border-radius: 8px !important;
            display: flex;
            align-items: center;
        }
        #customer-supply-return-modal .select2-selection__arrow {
            height: 40px !important;
        }
        #customer-supply-return-modal .select2-selection.is-invalids {
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

            <!-- Search Filter -->
            @component('components.search-filter')
            @endcomponent
            <!-- /Search Filter -->

            <div class="d-flex mb-2">
                <ul class="nav custom-premium-tabs" id="customer-return-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active d-flex align-items-center gap-2" id="shipping-tab" data-bs-toggle="tab"
                            data-bs-target="#shipping-pane" type="button" role="tab">
                            <i class="fe fe-truck"></i> Pengiriman
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link d-flex align-items-center gap-2" id="supply-return-tab" data-bs-toggle="tab"
                            data-bs-target="#supply-return-pane" type="button" role="tab">
                            <i class="fe fe-package"></i> Pengembalian Bahan Mentah
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link d-flex align-items-center gap-2" id="product-return-tab" data-bs-toggle="tab"
                            data-bs-target="#product-return-pane" type="button" role="tab">
                            <i class="fe fe-box"></i> Pengembalian Produk Jadi
                        </button>
                    </li>
                </ul>
            </div>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="shipping-pane" role="tabpanel">
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

                <div class="tab-pane fade" id="supply-return-pane" role="tabpanel">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="card-table">
                                <div class="card-body">
                            <div class="table-responsive position-relative dt-pending" id="tableCustomerSupplyReturn-wrap">
                                <div class="dt-skeleton" aria-hidden="true">
                                    <div style="padding:16px 25px;">
                                        <span class="skel-text" style="width:250px;height:38px;border-radius:20px;"></span>
                                    </div>
                                    <div class="dt-skeleton-head">
                                        @for ($i = 0; $i < 8; $i++)
                                            <span class="{{ in_array($i, [0, 2, 4, 7]) ? 'csr-skeleton-center' : '' }}" style="width:55%;height:12px;border-radius:6px;"></span>
                                        @endfor
                                    </div>
                                    <div class="dt-skeleton-body">
                                        @for ($row = 0; $row < 5; $row++)
                                            <div class="dt-skeleton-row">
                                                @for ($col = 0; $col < 8; $col++)
                                                    <span class="skel-text {{ in_array($col, [0, 2, 4, 7]) ? 'csr-skeleton-center' : '' }}" style="width:65%;height:14px;border-radius:6px;"></span>
                                                @endfor
                                            </div>
                                        @endfor
                                    </div>
                                </div>
                                <table class="table table-center table-hover mb-0" id="tableCustomerSupplyReturn">
                                    <thead>
                                        <tr>
                                            <th>Nomor</th>
                                            <th>Tanggal</th>
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

                <div class="tab-pane fade" id="product-return-pane" role="tabpanel">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="card-table">
                                <div class="card-body">
                            <div class="table-responsive position-relative dt-pending" id="tableCustomerProductReturn-wrap">
                                <div class="dt-skeleton" aria-hidden="true">
                                    <div style="padding:16px 25px;">
                                        <span class="skel-text" style="width:250px;height:38px;border-radius:20px;"></span>
                                    </div>
                                    <div class="dt-skeleton-head">
                                        @for ($i = 0; $i < 8; $i++)
                                            <span style="width:55%;height:12px;border-radius:6px;"></span>
                                        @endfor
                                    </div>
                                    <div class="dt-skeleton-body">
                                        @for ($row = 0; $row < 5; $row++)
                                            <div class="dt-skeleton-row">
                                                @for ($col = 0; $col < 8; $col++)
                                                    <span class="skel-text" style="width:65%;height:14px;border-radius:6px;"></span>
                                                @endfor
                                            </div>
                                        @endfor
                                    </div>
                                </div>
                                <table class="table table-center table-hover mb-0" id="tableCustomerProductReturn">
                                    <thead>
                                        <tr>
                                            <th>Nomor</th>
                                            <th>Tanggal</th>
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

    <div class="modal fade pg-modal--form" id="customer-supply-return-modal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 rounded-4">
                <div class="modal-header border-0" style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); padding: 18px 24px;">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width:40px;height:40px;background:rgba(255,255,255,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                            <i class="fe fe-package text-white" style="font-size:18px;"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 text-white fw-bold modal-title">Tambah Pengembalian Bahan Mentah</h5>
                            <small class="text-white-50 mb-0 mt-1" style="font-size:13px;">Bahan mentah atau kemasan kosong dari armada</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 position-relative">
                    <div class="pg-modal-loading" aria-live="polite" aria-busy="true">
                        <div class="spinner-border text-primary" role="status"></div>
                        <span class="text-muted fw-semibold" style="font-size:13px;">Memuat data...</span>
                    </div>
                    <div class="pg-modal-body-content">
                    <input type="hidden" id="csr-id">
                    <div class="row g-3">
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label text-muted fw-semibold" style="font-size: 11px; text-transform:uppercase; letter-spacing:.4px;">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" class="form-control fill" id="csr-date" style="border-radius: 8px; height:42px;">
                        </div>
                        <div class="col-lg-5 col-md-6">
                            <label class="form-label text-muted fw-semibold" style="font-size: 11px; text-transform:uppercase; letter-spacing:.4px;">Armada / Customer <span class="text-danger">*</span></label>
                            <select class="form-select fill" id="csr-customer" style="border-radius: 8px; height:42px;"></select>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <label class="form-label text-muted fw-semibold" style="font-size: 11px; text-transform:uppercase; letter-spacing:.4px;">Nomor Referensi</label>
                            <input type="text" class="form-control fill" id="csr-ref-number" maxlength="100" style="border-radius: 8px; height:42px;">
                        </div>
                        <div class="col-lg-8 col-md-6">
                            <label class="form-label text-muted fw-semibold" style="font-size: 11px; text-transform:uppercase; letter-spacing:.4px;">Catatan</label>
                            <textarea class="form-control fill" id="csr-notes" rows="1" maxlength="2000" style="border-radius: 8px; height:42px;"></textarea>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <label class="form-label text-muted fw-semibold" style="font-size: 11px; text-transform:uppercase; letter-spacing:.4px;">Bukti Foto <span class="text-danger">*</span></label>
                            <span id="csr-check-foto" class="ms-2 d-none">
                                <i class="fa fa-check-circle text-success"></i>
                                <small class="text-muted">Terunggah</small>
                            </span>
                            <div class="d-flex gap-2 mt-2">
                                <button class="btn w-100" id="csr-btn-upload-proof" type="button" style="border-radius:8px;height:42px;background:#eff6ff;border:1px solid #bfdbfe;color:#2563eb;font-weight:600;">
                                    <i class="fe fe-camera me-1"></i> Upload
                                </button>
                                <button class="btn w-100 d-none" id="csr-btn-view-proof" type="button" style="border-radius:8px;height:42px;background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;border:none;font-weight:600;box-shadow:0 4px 12px rgba(59,130,246,.3);">
                                    <i class="fe fe-image me-1"></i> Lihat
                                </button>
                            </div>
                            <input type="hidden" id="csr-proof-camera">
                            <input type="file" class="d-none" id="csr-proof-file" accept="image/jpeg,image/png,image/webp">
                        </div>
                    </div>

                    <hr class="mt-4 mb-4" style="border-color: #e2e8f0;">

                    <div class="row g-2 align-items-end mt-1" id="csr-line-form">
                        <div class="col-lg-4">
                            <label class="form-label text-muted fw-semibold" style="font-size: 11px; text-transform:uppercase; letter-spacing:.4px;">Bahan / Kemasan</label>
                            <select class="form-select fill" id="csr-supply" style="border-radius: 8px; height:42px;"></select>
                        </div>
                        <div class="col-lg-2">
                            <label class="form-label text-muted fw-semibold" style="font-size: 11px; text-transform:uppercase; letter-spacing:.4px;">Satuan</label>
                            <select class="form-select fill" id="csr-unit" style="border-radius: 8px; height:42px;"></select>
                        </div>
                        <div class="col-lg-2">
                            <label class="form-label text-muted fw-semibold" style="font-size: 11px; text-transform:uppercase; letter-spacing:.4px;">Qty</label>
                            <input type="number" min="1" step="1" class="form-control fill" id="csr-qty" style="border-radius: 8px; height:42px;">
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label text-muted fw-semibold" style="font-size: 11px; text-transform:uppercase; letter-spacing:.4px;">Gudang Tujuan</label>
                            <select class="form-select fill" id="csr-warehouse" style="border-radius: 8px; height:42px;"></select>
                        </div>
                        <div class="col-lg-1">
                            <button type="button" class="btn w-100 d-flex align-items-center justify-content-center" id="csr-add-line" style="border-radius:8px;height:42px;background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;border:none;box-shadow:0 4px 12px rgba(59,130,246,.3);"><i class="fe fe-plus"></i></button>
                        </div>
                    </div>

                    <div class="table-responsive border rounded-3 mt-3" style="max-height:300px; border-color: #e2e8f0 !important;">
                        <table class="table table-center mb-0">
                            <thead class="bg-light" style="position: sticky; top: 0; z-index: 1;">
                                <tr>
                                    <th style="font-size: 11px; text-transform:uppercase; letter-spacing:.4px; color:#64748b; font-weight:700;">Bahan / Kemasan</th>
                                    <th style="font-size: 11px; text-transform:uppercase; letter-spacing:.4px; color:#64748b; font-weight:700;">Satuan</th>
                                    <th style="font-size: 11px; text-transform:uppercase; letter-spacing:.4px; color:#64748b; font-weight:700;">Qty</th>
                                    <th style="font-size: 11px; text-transform:uppercase; letter-spacing:.4px; color:#64748b; font-weight:700;">Gudang</th>
                                    <th class="text-center" style="font-size: 11px; text-transform:uppercase; letter-spacing:.4px; color:#64748b; font-weight:700;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="csr-lines"></tbody>
                        </table>
                    </div>
                    </div>
                </div>
                <div class="modal-footer pg-modal-footer">
                    <button type="button" data-bs-dismiss="modal" class="btn pg-btn-cancel">Batal</button>
                    <button type="button" class="btn pg-btn-decline d-none" id="csr-decline"><i class="fe fe-x"></i> Tolak</button>
                    <button type="button" class="btn pg-btn-accept d-none" id="csr-accept"><i class="fe fe-check"></i> Terima</button>
                    <button type="button" class="btn pg-btn-save" id="csr-save">
                        <i class="fe fe-save"></i> Simpan Pengembalian
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade pg-modal--form" id="customer-product-return-modal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 rounded-4">
                <div class="modal-header border-0" style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); padding: 18px 24px;">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width:40px;height:40px;background:rgba(255,255,255,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                            <i class="fe fe-box text-white" style="font-size:18px;"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 text-white fw-bold modal-title">Tambah Pengembalian Produk Jadi</h5>
                            <small class="text-white-50 mb-0 mt-1" style="font-size:13px;">Produk jadi dari armada ke gudang</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 position-relative">
                    <div class="pg-modal-loading" aria-live="polite" aria-busy="true">
                        <div class="spinner-border text-primary" role="status"></div>
                        <span class="text-muted fw-semibold" style="font-size:13px;">Memuat data...</span>
                    </div>
                    <div class="pg-modal-body-content">
                    <input type="hidden" id="cpr-id">
                    <div class="row g-3">
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label text-muted fw-semibold" style="font-size: 11px; text-transform:uppercase; letter-spacing:.4px;">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" class="form-control fill" id="cpr-date" style="border-radius: 8px; height:42px;">
                        </div>
                        <div class="col-lg-5 col-md-6">
                            <label class="form-label text-muted fw-semibold" style="font-size: 11px; text-transform:uppercase; letter-spacing:.4px;">Armada / Customer <span class="text-danger">*</span></label>
                            <select class="form-select fill" id="cpr-customer" style="border-radius: 8px; height:42px;"></select>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <label class="form-label text-muted fw-semibold" style="font-size: 11px; text-transform:uppercase; letter-spacing:.4px;">Nomor Referensi</label>
                            <input type="text" class="form-control fill" id="cpr-ref-number" maxlength="100" style="border-radius: 8px; height:42px;">
                        </div>
                        <div class="col-lg-8 col-md-6">
                            <label class="form-label text-muted fw-semibold" style="font-size: 11px; text-transform:uppercase; letter-spacing:.4px;">Catatan</label>
                            <textarea class="form-control fill" id="cpr-notes" rows="1" maxlength="2000" style="border-radius: 8px; height:42px;"></textarea>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <label class="form-label text-muted fw-semibold" style="font-size: 11px; text-transform:uppercase; letter-spacing:.4px;">Bukti Foto <span class="text-danger">*</span></label>
                            <span id="cpr-check-foto" class="ms-2 d-none">
                                <i class="fa fa-check-circle text-success"></i>
                                <small class="text-muted">Terunggah</small>
                            </span>
                            <div class="d-flex gap-2 mt-2">
                                <button class="btn w-100" id="cpr-btn-upload-proof" type="button" style="border-radius:8px;height:42px;background:#eff6ff;border:1px solid #bfdbfe;color:#2563eb;font-weight:600;">
                                    <i class="fe fe-camera me-1"></i> Upload
                                </button>
                                <button class="btn w-100 d-none" id="cpr-btn-view-proof" type="button" style="border-radius:8px;height:42px;background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;border:none;font-weight:600;box-shadow:0 4px 12px rgba(59,130,246,.3);">
                                    <i class="fe fe-image me-1"></i> Lihat
                                </button>
                            </div>
                            <input type="hidden" id="cpr-proof-camera">
                            <input type="file" class="d-none" id="cpr-proof-file" accept="image/jpeg,image/png,image/webp">
                        </div>
                    </div>

                    <hr class="mt-4 mb-4" style="border-color: #e2e8f0;">

                    <div class="row g-2 align-items-end mt-1" id="cpr-line-form">
                        <div class="col-lg-4">
                            <label class="form-label text-muted fw-semibold" style="font-size: 11px; text-transform:uppercase; letter-spacing:.4px;">Produk / Varian</label>
                            <select class="form-select fill" id="cpr-product" style="border-radius: 8px; height:42px;"></select>
                        </div>
                        <div class="col-lg-2">
                            <label class="form-label text-muted fw-semibold" style="font-size: 11px; text-transform:uppercase; letter-spacing:.4px;">Satuan</label>
                            <select class="form-select fill" id="cpr-unit" style="border-radius: 8px; height:42px;"></select>
                        </div>
                        <div class="col-lg-2">
                            <label class="form-label text-muted fw-semibold" style="font-size: 11px; text-transform:uppercase; letter-spacing:.4px;">Qty</label>
                            <input type="number" min="1" step="1" class="form-control fill" id="cpr-qty" style="border-radius: 8px; height:42px;">
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label text-muted fw-semibold" style="font-size: 11px; text-transform:uppercase; letter-spacing:.4px;">Gudang Tujuan</label>
                            <select class="form-select fill" id="cpr-warehouse" style="border-radius: 8px; height:42px;"></select>
                        </div>
                        <div class="col-lg-1">
                            <button type="button" class="btn w-100 d-flex align-items-center justify-content-center" id="cpr-add-line" style="border-radius:8px;height:42px;background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;border:none;box-shadow:0 4px 12px rgba(59,130,246,.3);"><i class="fe fe-plus"></i></button>
                        </div>
                    </div>

                    <div class="table-responsive border rounded-3 mt-3" style="max-height:300px; border-color: #e2e8f0 !important;">
                        <table class="table table-center mb-0">
                            <thead class="bg-light" style="position: sticky; top: 0; z-index: 1;">
                                <tr>
                                    <th style="font-size: 11px; text-transform:uppercase; letter-spacing:.4px; color:#64748b; font-weight:700;">Produk / Varian</th>
                                    <th style="font-size: 11px; text-transform:uppercase; letter-spacing:.4px; color:#64748b; font-weight:700;">Satuan</th>
                                    <th style="font-size: 11px; text-transform:uppercase; letter-spacing:.4px; color:#64748b; font-weight:700;">Qty</th>
                                    <th style="font-size: 11px; text-transform:uppercase; letter-spacing:.4px; color:#64748b; font-weight:700;">Gudang</th>
                                    <th class="text-center" style="font-size: 11px; text-transform:uppercase; letter-spacing:.4px; color:#64748b; font-weight:700;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="cpr-lines"></tbody>
                        </table>
                    </div>
                    </div>
                </div>
                <div class="modal-footer pg-modal-footer">
                    <button type="button" data-bs-dismiss="modal" class="btn pg-btn-cancel">Batal</button>
                    <button type="button" class="btn pg-btn-decline d-none" id="cpr-decline"><i class="fe fe-x"></i> Tolak</button>
                    <button type="button" class="btn pg-btn-accept d-none" id="cpr-accept"><i class="fe fe-check"></i> Terima</button>
                    <button type="button" class="btn pg-btn-save" id="cpr-save">
                        <i class="fe fe-save"></i> Simpan Pengembalian
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="csr-photo-preview-modal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 overflow-hidden">
                <div class="modal-header border-0 text-white" style="background:linear-gradient(135deg,#1e3a8a,#3b82f6);">
                    <h5 class="modal-title text-white"><i class="fe fe-image me-2"></i>Bukti Foto Pengembalian</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light text-center p-3">
                    <img id="csr-proof-preview" class="img-fluid rounded" style="max-height:65vh;object-fit:contain;" alt="Bukti pengembalian">
                </div>
                <div class="modal-footer">
                    <a id="csr-proof-download" class="btn btn-outline-primary" download><i class="fe fe-download me-1"></i>Download</a>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="cpr-photo-preview-modal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 overflow-hidden">
                <div class="modal-header border-0 text-white" style="background:linear-gradient(135deg,#1e3a8a,#3b82f6);">
                    <h5 class="modal-title text-white"><i class="fe fe-image me-2"></i>Bukti Foto Pengembalian Produk</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light text-center p-3">
                    <img id="cpr-proof-preview" class="img-fluid rounded" style="max-height:65vh;object-fit:contain;" alt="Bukti pengembalian produk">
                </div>
                <div class="modal-footer">
                    <a id="cpr-proof-download" class="btn btn-outline-primary" download><i class="fe fe-download me-1"></i>Download</a>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('custom_js')
    <script>
        var public = "{{ asset('') }}";

        $(document).ready(function() {
            function syncHeaderButtons(targetId) {
                $('#btn-container-pengiriman').toggle(targetId === 'shipping-tab');
                $('#btn-container-pengembalian-bahan').toggle(targetId === 'supply-return-tab');
                $('#btn-container-pengembalian-produk').toggle(targetId === 'product-return-tab');
            }
            syncHeaderButtons('shipping-tab');
            $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
                syncHeaderButtons($(e.target).attr('id'));
            });
        });
    </script>
    <script src="{{asset('Custom_js/Backoffice/Customers/Sales_Order.js')}}?v={{time()}}"></script>
    <script src="{{asset('Custom_js/Backoffice/Customers/Customer_Supply_Return.js')}}?v={{time()}}"></script>
    <script src="{{asset('Custom_js/Backoffice/Customers/Customer_Product_Return.js')}}?v={{time()}}"></script>
@endsection
