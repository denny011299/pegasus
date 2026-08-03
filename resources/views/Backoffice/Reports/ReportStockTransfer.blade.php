<?php $page = 'report-stock-transfer'; ?>
@extends('layout.mainlayout')

@section('custom_css')
    <style>
        #tableStockTransferLogs {
            width: 100% !important;
            min-width: 1100px;
        }
        #tableStockTransferLogs td {
            vertical-align: middle;
        }
        #tableStockTransferLogs thead th {
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .4px;
            background: #f1f5f9 !important;
            border-bottom: 1px solid #e2e8f0;
        }
        #tableStockTransferLogs tbody td {
            color: #475569;
            font-size: 13px;
        }
        #tableStockTransferLogs tbody > tr {
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.2s ease;
        }
        #tableStockTransferLogs tbody > tr:hover {
            background-color: #f8fafc;
        }
        .log-transfer-route {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 80px minmax(0, 1fr);
            align-items: stretch;
            gap: 16px;
        }
        .log-route-card, .log-section {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
        }
        .log-route-card {
            padding: 16px;
        }
        .log-route-title, .log-section-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .45px;
        }
        .log-route-arrow {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            color: #64748b;
        }
        .log-route-arrow span {
            width: 42px;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            color: #fff;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            box-shadow: 0 4px 12px rgba(59, 130, 246, .25);
        }
        .log-route-arrow small {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .5px;
        }
        .log-info-label {
            color: #94a3b8;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .4px;
        }
        .log-info-value {
            color: #1e293b;
            font-size: 13px;
            font-weight: 600;
            margin-top: 4px;
            word-break: break-word;
        }
        .log-section-title {
            padding: 12px 16px;
            color: #334155;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        .log-change-table th, .log-items-table th {
            padding: 10px 12px;
            color: #64748b;
            background: #f1f5f9;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .4px;
            white-space: nowrap;
        }
        .log-change-table td, .log-items-table td {
            padding: 11px 12px;
            font-size: 12px;
        }
        .log-value-before {
            color: #dc2626;
            text-decoration: line-through;
        }
        .log-value-after {
            color: #16a34a;
            font-weight: 600;
        }
        .log-sku {
            display: inline-block;
            padding: 3px 7px;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            background: #f8fafc;
            color: #475569;
            font-family: monospace;
            font-size: 11px;
        }
        .log-item-changed {
            background: #fffbeb;
        }
        @media (max-width: 991.98px) {
            .log-transfer-route {
                grid-template-columns: 1fr;
            }
            .log-route-arrow {
                transform: rotate(90deg);
                padding: 4px;
            }
        }
    </style>
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            @component('components.page-header')
                @slot('title')
                    Log Aksi Stock Transfer
                @endslot
            @endcomponent

            <div class="card mb-4 border-0" style="background: linear-gradient(145deg, #ffffff, #f8fafc); box-shadow: 0 4px 15px rgba(0,0,0,0.03); border-radius: 12px;">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap align-items-end gap-3">
                        <div style="flex: 1; min-width: 200px; max-width: 250px;">
                            <label class="form-label text-muted fw-semibold mb-2" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;"><i class="fe fe-calendar me-1"></i> Dari Tanggal</label>
                            <div class="cal-icon cal-icon-info" style="position: relative;">
                                <input type="text" class="datetimepicker form-control" id="start_date" placeholder="DD-MM-YYYY" style="border-radius: 8px; font-weight: 600; color: #1e293b; font-size: 14px; height: 42px; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
                            </div>
                        </div>
                        <div style="flex: 1; min-width: 200px; max-width: 250px;">
                            <label class="form-label text-muted fw-semibold mb-2" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;"><i class="fe fe-calendar me-1"></i> Sampai Tanggal</label>
                            <div class="cal-icon cal-icon-info" style="position: relative;">
                                <input type="text" class="datetimepicker form-control" id="end_date" placeholder="DD-MM-YYYY" style="border-radius: 8px; font-weight: 600; color: #1e293b; font-size: 14px; height: 42px; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
                            </div>
                        </div>
                        <div>
                            <button class="btn btn-primary btn-filter-logs" style="padding: 0 24px; font-weight: 600; border-radius: 8px; height: 42px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(59, 130, 246, 0.25); background: linear-gradient(135deg, #3b82f6, #2563eb); border: none;">
                                <i class="fe fe-filter me-2" style="font-size: 14px;"></i> Terapkan Filter
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive dt-pending" id="tableLogs-wrap" style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
                                <div class="dt-skeleton" aria-hidden="true">
                                    <div style="padding: 16px 25px;">
                                        <span class="skel-text" style="width: 250px; height: 38px; border-radius: 20px;"></span>
                                    </div>
                                    <div class="dt-skeleton-head" style="grid-template-columns: 15% 15% 15% 15% 25% 15%;">
                                        <span style="width:60%"></span>
                                        <span style="width:70%"></span>
                                        <span style="width:50%"></span>
                                        <span style="width:60%"></span>
                                        <span style="width:70%"></span>
                                        <span style="width:50%"></span>
                                    </div>
                                    <div class="dt-skeleton-body">
                                        @for ($i = 0; $i < 5; $i++)
                                            <div class="dt-skeleton-row" style="grid-template-columns: 15% 15% 15% 15% 25% 15%;">
                                                <span class="skel-text" style="width:70%"></span>
                                                <span class="skel-badge" style="width:60%;justify-self:center"></span>
                                                <span class="skel-text" style="width:60%"></span>
                                                <span class="skel-text" style="width:60%"></span>
                                                <span class="skel-text" style="width:80%"></span>
                                                <span class="skel-text" style="width:40%"></span>
                                            </div>
                                        @endfor
                                    </div>
                                </div>
                                <table class="table table-center table-hover mb-0" id="tableStockTransferLogs">
                                    <thead>
                                        <tr>
                                            <th>Waktu</th>
                                            <th class="text-center">Aksi</th>
                                            <th>Referensi</th>
                                            <th>Pelaku</th>
                                            <th>Ringkasan</th>
                                            <th class="text-center no-sort">Aksi</th>
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

    <!-- Modal Detail Log -->
    <div class="modal custom-modal fade" id="modalViewMeta" role="dialog" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width: 92vw;">
            <div class="modal-content d-flex flex-column" style="max-height: 92vh;border:0;border-radius:16px;overflow:hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
                <div class="modal-header border-0" style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); padding: 18px 24px;">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width:40px;height:40px;background:rgba(255,255,255,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                            <i class="fe fe-file-text text-white" style="font-size:18px;"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 text-white fw-bold modal-title" id="metaModalTitle" style="font-size:16px; letter-spacing:0.3px;">Detail Stock Transfer</h5>
                            <small class="text-white-50 mb-0 mt-1" id="metaModalSubtitle" style="font-size:13px; letter-spacing:0.3px;">-</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body flex-grow-1" style="overflow-y:auto;min-height:0;background:#f8fafc;padding:24px;">
                    <div id="metaContent"></div>
                </div>
                <div class="modal-footer border-top pt-3 pb-3 px-4" style="background:#f8fafc;">
                    <button type="button" data-bs-dismiss="modal" class="btn btn-back ms-auto" style="border-radius:8px; font-size:13px; font-weight:600; color:#64748b;">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('custom_js')
    <script src="{{ asset('Custom_js/Backoffice/Reports/report_datatable_loading.js') }}?v=1"></script>
    <script src="{{ asset('Custom_js/Backoffice/Reports/ReportStockTransfer.js') }}?v={{ time() }}"></script>
@endsection
