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

        .stock-alert-table {
            width: 100% !important;
            min-width: 1200px;
        }
        .stock-alert-table th,
        .stock-alert-table td {
            box-sizing: border-box !important;
            white-space: normal !important;
            overflow-wrap: anywhere;
            vertical-align: middle;
        }
        .stock-alert-table th:nth-child(1),
        .stock-alert-table td:nth-child(1) {
            width: 22% !important;
        }
        .stock-alert-table th:nth-child(2),
        .stock-alert-table td:nth-child(2) {
            width: 10% !important;
        }
        .stock-alert-table th:nth-child(3),
        .stock-alert-table td:nth-child(3) {
            width: 10% !important;
        }
        .stock-alert-table th:nth-child(4),
        .stock-alert-table td:nth-child(4) {
            width: 14% !important;
        }
        .stock-alert-table th:nth-child(5),
        .stock-alert-table td:nth-child(5) {
            width: 18% !important;
        }
        .stock-alert-table th:nth-child(6),
        .stock-alert-table td:nth-child(6) {
            width: 26% !important;
        }
        .stock-alert-table td.dataTables_empty {
            width: auto !important;
            text-align: center;
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

        /* Pencil button untuk edit stok alert / pemesanan min */
        .btn-edit-stok-alert,
        .btn-edit-min-order {
            opacity: 0.5;
            transition: opacity 0.15s, color 0.15s;
            vertical-align: middle;
        }
        .btn-edit-stok-alert:hover,
        .btn-edit-min-order:hover {
            opacity: 1;
            color: #3b82f6 !important;
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
                        Peringatan Stok Produk
                        <div class="small text-muted fw-normal mt-1" id="stock-alert-wh-label" style="font-size:13px;"></div>
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
                                            <div class="dt-skeleton-head" style="grid-template-columns: 20% 9% 9% 12% 12% 16% 22%;">
                                                <span style="width:60%"></span>
                                                <span style="width:50%"></span>
                                                <span style="width:50%"></span>
                                                <span style="width:55%"></span>
                                                <span style="width:55%"></span>
                                                <span style="width:60%"></span>
                                                <span style="width:70%"></span>
                                            </div>
                                            <div class="dt-skeleton-body">
                                                @for ($i = 0; $i < 5; $i++)
                                                    <div class="dt-skeleton-row" style="grid-template-columns: 20% 9% 9% 12% 12% 16% 22%;">
                                                        <span class="skel-text" style="width:70%"></span>
                                                        <span class="skel-text" style="width:60%"></span>
                                                        <span class="skel-text" style="width:55%"></span>
                                                        <span class="skel-text" style="width:50%"></span>
                                                        <span class="skel-text" style="width:50%"></span>
                                                        <span class="skel-badge" style="width:60%;justify-self:center"></span>
                                                        <span class="skel-text" style="width:80%"></span>
                                                    </div>
                                                @endfor
                                            </div>
                                        </div>
                                        <table class="table table-center table-hover stock-alert-table" id="tableStockAlertLow">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Nama Produk</th>
                                                    <th>Kategori</th>
                                                    <th>SKU</th>
                                                    <th>Stok Saat Ini</th>
                                                    <th>Peringatan Stok</th>
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
                                            <div class="dt-skeleton-head" style="grid-template-columns: 20% 9% 9% 12% 12% 16% 22%;">
                                                <span style="width:60%"></span>
                                                <span style="width:50%"></span>
                                                <span style="width:50%"></span>
                                                <span style="width:55%"></span>
                                                <span style="width:55%"></span>
                                                <span style="width:60%"></span>
                                                <span style="width:70%"></span>
                                            </div>
                                            <div class="dt-skeleton-body">
                                                @for ($i = 0; $i < 5; $i++)
                                                    <div class="dt-skeleton-row" style="grid-template-columns: 20% 9% 9% 12% 12% 16% 22%;">
                                                        <span class="skel-text" style="width:70%"></span>
                                                        <span class="skel-text" style="width:60%"></span>
                                                        <span class="skel-text" style="width:55%"></span>
                                                        <span class="skel-text" style="width:50%"></span>
                                                        <span class="skel-text" style="width:50%"></span>
                                                        <span class="skel-badge" style="width:60%;justify-self:center"></span>
                                                        <span class="skel-text" style="width:80%"></span>
                                                    </div>
                                                @endfor
                                            </div>
                                        </div>
                                        <table class="table table-center table-hover stock-alert-table" id="tableStockAlertOut">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Nama Produk</th>
                                                    <th>Kategori</th>
                                                    <th>SKU</th>
                                                    <th>Stok Saat Ini</th>
                                                    <th>Peringatan Stok</th>
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

    <!-- Modal Edit Pemesanan Min. -->
    <div class="modal fade custom-modal pg-modal--form" id="modal-edit-min-order" tabindex="-1" aria-labelledby="modalEditMinOrderLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
            <div class="modal-content d-flex flex-column" style="border-radius: 16px; overflow: hidden; border: none;">

                {{-- Header --}}
                <div class="modal-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="pg-modal-icon">
                            <i class="fe fe-shopping-cart"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 fw-bold modal-title" id="modalEditMinOrderLabel">Edit Pemesanan Min.</h5>
                            <small class="modal-subtitle">Atur jumlah pembelian minimum ke supplier</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                {{-- Body --}}
                <div class="modal-body px-4 py-4">
                    <div class="mb-4">
                        <label class="form-label text-muted fw-semibold mb-1" style="font-size:11px; text-transform:uppercase; letter-spacing:.4px;">Nama Produk</label>
                        <div class="fw-semibold" id="emo-product-name" style="font-size:14px; color:#0f172a;">—</div>
                    </div>
                    <div class="row g-3">
                        <div class="col-8">
                            <label class="form-label fw-semibold" style="font-size:12px;" for="emo-min-order">Dasar Pemesanan Min.</label>
                            <input type="number" id="emo-min-order" class="form-control" min="0" step="1" placeholder="0" style="border-radius:8px;">
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-semibold" style="font-size:12px;">Satuan</label>
                            <input type="text" id="emo-min-order-unit" class="form-control" readonly style="background:#f1f5f9; border-radius:8px; color:#64748b;">
                        </div>
                    </div>
                    <div class="small text-muted mt-3" id="emo-calculated-hint" style="font-size:12px;"></div>
                    <input type="hidden" id="emo-product-id">
                    <input type="hidden" id="emo-variant-id">
                    <input type="hidden" id="emo-unit-id">
                    <input type="hidden" id="emo-warehouse-id">
                </div>

                {{-- Footer --}}
                <div class="modal-footer pg-modal-footer">
                    <button type="button" class="btn pg-btn-cancel" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn pg-btn-save" id="emo-save-btn">
                        <span id="emo-save-spinner" class="spinner-border spinner-border-sm me-1 d-none" role="status"></span>
                        Simpan
                    </button>
                </div>

            </div>
        </div>
    </div>
    <!-- /Modal Edit Pemesanan Min. -->

    <!-- Modal Edit Stok Alert -->
    <div class="modal fade custom-modal pg-modal--form" id="modal-edit-stok-alert" tabindex="-1" aria-labelledby="modalEditStokAlertLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
            <div class="modal-content d-flex flex-column" style="border-radius: 16px; overflow: hidden; border: none;">

                {{-- Header --}}
                <div class="modal-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="pg-modal-icon">
                            <i class="fe fe-bell"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 fw-bold modal-title" id="modalEditStokAlertLabel">Edit Peringatan Stok</h5>
                            <small class="modal-subtitle">Atur jumlah minimum stok yang memicu peringatan</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                {{-- Body --}}
                <div class="modal-body px-4 py-4">
                    <div class="mb-4">
                        <label class="form-label text-muted fw-semibold mb-1" style="font-size:11px; text-transform:uppercase; letter-spacing:.4px;">Nama Produk</label>
                        <div class="fw-semibold" id="esa-product-name" style="font-size:14px; color:#0f172a;">—</div>
                    </div>
                    <div class="row g-3">
                        <div class="col-8">
                            <label class="form-label fw-semibold" style="font-size:12px;" for="esa-alert-stock">Peringatan Stok</label>
                            <input type="number" id="esa-alert-stock" class="form-control" min="0" step="1" placeholder="0" style="border-radius:8px;">
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-semibold" style="font-size:12px;">Satuan</label>
                            <input type="text" id="esa-alert-unit" class="form-control" readonly style="background:#f1f5f9; border-radius:8px; color:#64748b;">
                        </div>
                    </div>
                    <input type="hidden" id="esa-product-id">
                    <input type="hidden" id="esa-variant-id">
                    <input type="hidden" id="esa-unit-id">
                    <input type="hidden" id="esa-warehouse-id">
                </div>

                {{-- Footer --}}
                <div class="modal-footer pg-modal-footer">
                    <button type="button" class="btn pg-btn-cancel" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn pg-btn-save" id="esa-save-btn">
                        <span id="esa-save-spinner" class="spinner-border spinner-border-sm me-1 d-none" role="status"></span>
                        Simpan
                    </button>
                </div>

            </div>
        </div>
    </div>
    <!-- /Modal Edit Stok Alert -->

@endsection

@section('custom_js')
    <script>
        var public = "{{ asset('') }}";    
    </script>
    <script src="{{asset('Custom_js/Backoffice/Inventory/Stock_Alert.js')}}?v=26"></script>
@endsection
