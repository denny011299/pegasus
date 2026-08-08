<?php $page = 'stock_alert'; ?>
@extends('layout.mainlayout')
@section('custom_css')
    <style>
        .content-page-header,
        .page-header {
            margin-bottom: 0 !important;
        }
        .tab-content {
            padding-top: 0 !important;
        }

        #tableStockAlertLow, #tableStockAlertOut {
            width: 100% !important;
            min-width: 900px;
        }
        #tableStockAlertLow td, #tableStockAlertOut td {
            white-space: normal !important;
            word-wrap: break-word;
            vertical-align: middle;
        }
        #tableStockAlertLow th:nth-child(2),
        #tableStockAlertOut th:nth-child(2),
        #tableStockAlertLow td:nth-child(2),
        #tableStockAlertOut td:nth-child(2) {
            white-space: nowrap !important;
        }

        /* Flatten wrap: global .table-responsive border/radius stacks with .card-table glass chrome */
        #tableStockAlertLow-wrap,
        #tableStockAlertOut-wrap {
            position: relative;
            border: none !important;
            border-radius: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
        }

        #tableStockAlertLow-wrap .dt-skeleton,
        #tableStockAlertOut-wrap .dt-skeleton {
            border-radius: 0;
            background: transparent;
            box-shadow: none;
        }

        #tableStockAlertLow_wrapper .dataTables_processing,
        #tableStockAlertOut_wrapper .dataTables_processing {
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

        #tableStockAlertLow-wrap:not(.is-loading) .dataTables_processing,
        #tableStockAlertOut-wrap:not(.is-loading) .dataTables_processing {
            display: none !important;
        }

        #tableStockAlertLow-wrap.is-loading .dataTables_processing,
        #tableStockAlertOut-wrap.is-loading .dataTables_processing {
            display: flex !important;
        }

        #tableStockAlertLow_wrapper .dataTables_processing > div,
        #tableStockAlertOut_wrapper .dataTables_processing > div {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            border-radius: 10px;
            background: #fff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        }

        #tableStockAlertLow-wrap.is-loading tbody,
        #tableStockAlertOut-wrap.is-loading tbody {
            opacity: 0.45;
            pointer-events: none;
        }
    </style>
@endsection
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            <div class="d-flex flex-wrap justify-content-between align-items-center m-0 p-0 mb-3">
                @component('components.page-header')
                    @slot('title')
                        Peringatan Stok Bahan Mentah
                        <div class="small text-muted fw-normal mt-1" id="stock-alert-supplies-wh-label" style="font-size:13px;"></div>
                    @endslot
                @endcomponent
                <ul class="nav nav-pills navtab-bg d-flex flex-nowrap mb-0" style="z-index: 10; position: relative;">
                    <li class="nav-item">
                        <a href="#low" data-bs-toggle="tab" class="nav-link active text-nowrap" style="border-radius: 10px">
                            <i class="fe fe-alert-circle me-1"></i> Stok Rendah <span class="badge text-bg-danger ms-1" id="total_low">0</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#out" data-bs-toggle="tab" class="nav-link text-nowrap" style="border-radius: 10px">
                            <i class="fe fe-x-circle me-1"></i> Stok Habis <span class="badge text-bg-danger ms-1" id="total_out">0</span>
                        </a>
                    </li>
                </ul>
            </div>
            <!-- /Page Header -->

            <!-- Search Filter -->
            @component('components.search-filter')
            @endcomponent
            <!-- /Search Filter -->

            <!-- Table -->
            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
							<div class="tab-content">
								<div class="tab-pane show active" id="low">
									<div class="table-responsive dt-pending" id="tableStockAlertLow-wrap">
                                        <div class="dt-skeleton" aria-hidden="true">
                                            <div style="padding: 16px 25px;">
                                                <span class="skel-text" style="width: 250px; height: 38px; border-radius: 20px;"></span>
                                            </div>
                                            <div class="dt-skeleton-head" style="grid-template-columns: 35% 25% 40%;">
                                                <span style="width:60%"></span>
                                                <span style="width:50%"></span>
                                                <span style="width:70%"></span>
                                            </div>
                                            <div class="dt-skeleton-body">
                                                @for ($i = 0; $i < 5; $i++)
                                                    <div class="dt-skeleton-row" style="grid-template-columns: 35% 25% 40%;">
                                                        <span class="skel-text" style="width:70%"></span>
                                                        <span class="skel-badge" style="width:55%;justify-self:center"></span>
                                                        <span class="skel-text" style="width:80%"></span>
                                                    </div>
                                                @endfor
                                            </div>
                                        </div>
                                        <table class="table table-center table-hover" id="tableStockAlertLow">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Nama Bahan Mentah</th>
                                                    <th>Pemesanan Min.</th>
                                                    <th>Stok Minimum Rekomendasi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                
                                            </tbody>
                                        </table>
                                    </div>
								</div>
								<div class="tab-pane" id="out">
									<div class="table-responsive dt-pending" id="tableStockAlertOut-wrap">
                                        <div class="dt-skeleton" aria-hidden="true">
                                            <div style="padding: 16px 25px;">
                                                <span class="skel-text" style="width: 250px; height: 38px; border-radius: 20px;"></span>
                                            </div>
                                            <div class="dt-skeleton-head" style="grid-template-columns: 35% 25% 40%;">
                                                <span style="width:60%"></span>
                                                <span style="width:50%"></span>
                                                <span style="width:70%"></span>
                                            </div>
                                            <div class="dt-skeleton-body">
                                                @for ($i = 0; $i < 5; $i++)
                                                    <div class="dt-skeleton-row" style="grid-template-columns: 35% 25% 40%;">
                                                        <span class="skel-text" style="width:70%"></span>
                                                        <span class="skel-badge" style="width:55%;justify-self:center"></span>
                                                        <span class="skel-text" style="width:80%"></span>
                                                    </div>
                                                @endfor
                                            </div>
                                        </div>
                                        <table class="table table-center table-hover" id="tableStockAlertOut" style="width: 100%">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Nama Bahan Mentah</th>
                                                    <th>Pemesanan Min.</th>
                                                    <th>Stok Minimum Rekomendasi</th>
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
    <script src="{{asset('Custom_js/Backoffice/Inventory/Stock_Alert_Supplies.js')}}?v=8"></script>
@endsection
