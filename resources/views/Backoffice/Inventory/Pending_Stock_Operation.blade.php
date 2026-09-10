<?php $page = 'pending_stock_operation'; ?>
@extends('layout.mainlayout')
@section('custom_css')
    <link rel="stylesheet" href="{{ asset('assets/plugins/daterangepicker/daterangepicker.css') }}">
    <style>
        #tablePendingStock {
            width: 100% !important;
        }

        #tablePendingStock td:last-child {
            white-space: nowrap !important;
        }

        #tablePendingStock-wrap {
            position: relative;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow-x: auto;
        }

        .pending-stock-filter .form-control,
        .pending-stock-filter .form-select {
            height: 42px !important;
            border-radius: 8px !important;
            border-color: #cbd5e1 !important;
            font-size: 13px !important;
        }
        .pending-stock-filter .input-block label {
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 6px;
        }
        .pending-stock-filter .btn-clear-pso-filter {
            height: 42px !important;
            border-radius: 8px !important;
            border-color: #cbd5e1 !important;
            color: #475569 !important;
            background: #ffffff !important;
            font-weight: 600 !important;
            transition: all 0.2s ease-in-out;
        }
        .pending-stock-filter .btn-clear-pso-filter:hover {
            background: #f1f5f9 !important;
            border-color: #94a3b8 !important;
            color: #0f172a !important;
        }
        .pending-stock-filter .cal-icon:after {
            pointer-events: none;
        }
        .daterangepicker {
            z-index: 1060 !important;
        }

        #tablePendingStock_wrapper .dataTables_processing {
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
            transform: none !important;
        }

        #tablePendingStock-wrap:not(.is-loading) .dataTables_processing {
            display: none !important;
        }

        #tablePendingStock-wrap.is-loading .dataTables_processing {
            display: flex !important;
        }

        #tablePendingStock-wrap.is-loading tbody {
            opacity: 0.45;
            pointer-events: none;
        }

        #tablePendingStock_wrapper .dataTables_processing > div {
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
                    Antrian Mutasi Stok
                @endslot
            @endcomponent

            @component('components.search-filter')
            @endcomponent

            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive dt-pending" id="tablePendingStock-wrap">
                                <div class="dt-skeleton" aria-hidden="true">
                                    <div style="padding: 16px 25px;">
                                        <span class="skel-text" style="width: 250px; height: 38px; border-radius: 20px;"></span>
                                    </div>
                                    <div class="dt-skeleton-head" style="grid-template-columns: 12% 12% 12% 16% 10% 12% 10% 8%;">
                                        <span style="width:70%"></span>
                                        <span style="width:75%"></span>
                                        <span style="width:65%"></span>
                                        <span style="width:80%"></span>
                                        <span style="width:60%"></span>
                                        <span style="width:70%"></span>
                                        <span style="width:55%"></span>
                                        <span style="width:40%;justify-self:center"></span>
                                    </div>
                                    <div class="dt-skeleton-body">
                                        @for ($i = 0; $i < 5; $i++)
                                            <div class="dt-skeleton-row" style="grid-template-columns: 12% 12% 12% 16% 10% 12% 10% 8%;">
                                                <span class="skel-text" style="width:75%"></span>
                                                <span class="skel-text" style="width:80%"></span>
                                                <span class="skel-text" style="width:70%"></span>
                                                <span class="skel-text" style="width:85%"></span>
                                                <span class="skel-badge" style="width:70%;justify-self:center"></span>
                                                <span class="skel-text" style="width:65%"></span>
                                                <span class="skel-badge" style="width:70%;justify-self:center"></span>
                                                <div style="display:flex;align-items:center;gap:6px;justify-content:center;">
                                                    <span class="skel-btn"></span>
                                                </div>
                                            </div>
                                        @endfor
                                    </div>
                                </div>
                                <table class="table table-center table-hover" id="tablePendingStock">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Waktu</th>
                                            <th>Jenis</th>
                                            <th>Kode</th>
                                            <th>Gudang</th>
                                            <th>Domain</th>
                                            <th>Opname</th>
                                            <th>Status</th>
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

    <div class="modal custom-modal fade pg-modal--form" id="modalPendingStockDetail" role="dialog"
        data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="border-radius:16px;overflow:hidden;border:none;">
                <div class="modal-header d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <div class="pg-modal-icon">
                            <i class="fe fe-layers"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 fw-bold modal-title text-white">Detail Antrian Mutasi Stok</h5>
                            <small style="color: rgba(255,255,255,0.75);">Informasi antrian mutasi menunggu opname</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <div class="modal-body p-0 position-relative"
                    style="overflow-y:auto;min-height:0;background:#ffffff;">
                    <div id="pending_stock_detail_loading"
                        style="display:none;position:absolute;inset:0;z-index:20;background:rgba(255,255,255,.92);flex-direction:column;align-items:center;justify-content:center;gap:12px;">
                        <div class="spinner-border text-primary" style="width:2.5rem;height:2.5rem;" role="status"
                            aria-hidden="true"></div>
                        <div class="text-muted fw-semibold" style="font-size:13px;">Memuat detail antrian…</div>
                    </div>

                    <div style="padding: 18px 24px 8px 24px;">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="text-muted"
                                    style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:#475569;">
                                    <i class="fe fe-shuffle me-1 text-primary"></i> Jenis
                                </div>
                                <div class="fw-bold text-dark mt-0.5" id="lbl_pso_jenis" style="font-size:12.5px;">-</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted"
                                    style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:#475569;">
                                    <i class="fe fe-hash me-1 text-primary"></i> Kode
                                </div>
                                <div class="fw-bold text-dark mt-0.5" id="lbl_pso_kode" style="font-size:12.5px;">-</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted"
                                    style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:#475569;">
                                    <i class="fe fe-box me-1 text-primary"></i> Gudang
                                </div>
                                <div class="fw-bold text-dark mt-0.5" id="lbl_pso_gudang" style="font-size:12.5px;">-</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted"
                                    style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:#475569;">
                                    <i class="fe fe-layers me-1 text-primary"></i> Domain
                                </div>
                                <div class="fw-bold text-dark mt-0.5" id="lbl_pso_domain" style="font-size:12.5px;">-</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted"
                                    style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:#475569;">
                                    <i class="fe fe-clipboard me-1 text-primary"></i> Opname
                                </div>
                                <div class="fw-bold text-dark mt-0.5" id="lbl_pso_opname" style="font-size:12.5px;">-</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted"
                                    style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:#475569;">
                                    <i class="fe fe-flag me-1 text-primary"></i> Status
                                </div>
                                <div class="mt-0.5" id="lbl_pso_status" style="font-size:12.5px;">-</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted"
                                    style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:#475569;">
                                    <i class="fe fe-calendar me-1 text-primary"></i> Dibuat
                                </div>
                                <div class="fw-bold text-dark mt-0.5" id="lbl_pso_dibuat" style="font-size:12.5px;">-</div>
                            </div>
                            <div class="col-md-6" id="pso_applied_wrap" style="display:none;">
                                <div class="text-muted"
                                    style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:#475569;">
                                    <i class="fe fe-check-circle me-1 text-primary"></i> Applied
                                </div>
                                <div class="fw-bold text-dark mt-0.5" id="lbl_pso_applied" style="font-size:12.5px;">-</div>
                            </div>
                            <div class="col-12" id="pso_error_wrap" style="display:none;">
                                <div class="text-muted"
                                    style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:#475569;">
                                    <i class="fe fe-alert-triangle me-1 text-danger"></i> Error
                                </div>
                                <div class="fw-semibold text-danger mt-0.5" id="lbl_pso_error"
                                    style="font-size:12.5px;word-break:break-word;">-</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer pg-modal-footer">
                    <button type="button" data-bs-dismiss="modal" class="btn pg-btn-cancel">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('custom_js')
    <script src="{{ asset('Custom_js/Backoffice/Inventory/Pending_Stock_Operation.js') }}?v=6"></script>
@endsection
