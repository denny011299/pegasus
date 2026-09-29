<?php $page = 'production-planning'; ?>
@extends(!empty($ppFullscreen) ? 'layout.fullscreen' : 'layout.mainlayout')
@section('custom_css')
    <link rel="stylesheet" href="{{ asset('assets/plugins/daterangepicker/daterangepicker.css') }}">
<style>
    .daterangepicker {
        z-index: 1060 !important;
    }
    /* KPI Ringkas & Proporsional */
    .pp-kpi-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .pp-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
    }
    .pp-kpi-card .card-body {
        padding: 12px 14px !important;
    }
    .pp-kpi-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 42px;
        font-size: 18px;
    }
    .pp-kpi-violet { background: #f5f3ff; color: #7c3aed; }
    .pp-kpi-orange { background: #fff7ed; color: #ea580c; }
    .pp-kpi-blue   { background: #eff6ff; color: #2563eb; }
    .pp-kpi-sky    { background: #e0f2fe; color: #0284c7; }
    .pp-kpi-indigo { background: #f5f3ff; color: #7c3aed; }
    .pp-kpi-green  { background: #dcfce7; color: #166534; }
    .pp-kpi-label {
        font-size: 11px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.35px;
        margin-bottom: 2px;
        line-height: 1.25;
    }
    .pp-kpi-val {
        font-size: 20px;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.15;
    }
    .pp-kpi-sub {
        font-size: 11.5px;
        font-weight: 500;
        color: #94a3b8;
        margin-top: 2px;
    }

    /* Filter Bar — seragam, presisi, rapi & ter-align sempurna */
    .pp-filter-box {
        background: #ffffff !important;
        border-bottom: 1px solid #e2e8f0 !important;
        padding: 12px 16px !important;
        position: relative;
        z-index: 5;
        overflow: visible !important;
    }
    .pp-filter-box label {
        display: block !important;
        height: 14px !important;
        line-height: 14px !important;
        font-size: 11px !important;
        font-weight: 700 !important;
        color: #64748b !important;
        text-transform: uppercase !important;
        letter-spacing: 0.35px !important;
        margin-bottom: 6px !important;
        white-space: nowrap !important;
    }

    /* Select2 di filter halaman — dropdown ke body; jangan kepotong tab-pane */
    #pp-pane-planning,
    #pp-pane-planning .tab-pane,
    #pp-pane-job,
    #pp-pane-histori,
    .tab-content:has(#pp-pane-planning) {
        overflow: visible !important;
    }

    /* Standard Height & Sizing for ALL Filter Controls (Date, Select, Select2, Buttons) */
    .pp-filter-box .cal-icon input.form-control,
    .pp-filter-box .cal-icon input,
    .pp-filter-box input.form-control,
    .pp-filter-box .form-control,
    .pp-filter-box .form-select,
    .pp-filter-box .select2-container--default .select2-selection--single,
    .pp-filter-box .select2-container .select2-selection--single,
    .pp-filter-box .btn {
        height: 38px !important;
        min-height: 38px !important;
        max-height: 38px !important;
        font-size: 13px !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 8px !important;
        box-sizing: border-box !important;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.02) !important;
        transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease !important;
    }

    /* Date Range Input */
    .pp-filter-box .cal-icon {
        position: relative !important;
        width: 100% !important;
    }
    .pp-filter-box .cal-icon input.form-control,
    .pp-filter-box .cal-icon input,
    .pp-filter-box #pp_filter_date,
    .pp-filter-box #pp_job_filter_date,
    .pp-filter-box #pp_histori_filter_date {
        padding-left: 12px !important;
        padding-right: 38px !important;
        font-weight: 500 !important;
        color: #1e293b !important;
        background: #ffffff !important;
        cursor: pointer !important;
    }
    .pp-filter-box .cal-icon:after,
    .pp-filter-box .cal-icon-info:after {
        top: 50% !important;
        transform: translateY(-50%) !important;
        right: 12px !important;
        font-size: 15px !important;
        color: #64748b !important;
        pointer-events: none !important;
    }
    .pp-filter-box .cal-icon input.form-control:hover,
    .pp-filter-box .cal-icon input:hover,
    .pp-filter-box .form-select:hover,
    .pp-filter-box .select2-container--default .select2-selection--single:hover {
        border-color: #94a3b8 !important;
    }
    .pp-filter-box .cal-icon input.form-control:focus,
    .pp-filter-box .cal-icon input:focus,
    .pp-filter-box .form-select:focus,
    .pp-filter-box .select2-container--default.select2-container--open .select2-selection--single,
    .pp-filter-box .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: #2563eb !important;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12) !important;
        outline: none !important;
    }

    /* Standard Select Dropdown */
    .pp-filter-box .form-select {
        padding-left: 12px !important;
        padding-right: 32px !important;
        font-weight: 500 !important;
        color: #1e293b !important;
        background-color: #ffffff !important;
        cursor: pointer !important;
    }

    /* Select2 Single Selection */
    .pp-filter-box .select2-container {
        width: 100% !important;
        z-index: 6;
    }
    .pp-filter-box .select2-container--default .select2-selection--single,
    .pp-filter-box .select2-container .select2-selection--single {
        background-color: #ffffff !important;
        display: flex !important;
        align-items: center !important;
        padding: 0 !important;
    }
    .pp-filter-box .select2-container--default .select2-selection--single .select2-selection__rendered,
    .pp-filter-box .select2-container .select2-selection__rendered {
        line-height: 36px !important;
        padding-left: 12px !important;
        padding-right: 28px !important;
        color: #1e293b !important;
        font-size: 13px !important;
        font-weight: 500 !important;
        width: 100% !important;
        display: block !important;
    }
    .pp-filter-box .select2-container--default .select2-selection--single .select2-selection__placeholder,
    .pp-filter-box .select2-container .select2-selection__placeholder {
        color: #94a3b8 !important;
        font-weight: 400 !important;
        font-size: 13px !important;
    }
    .pp-filter-box .select2-container--default .select2-selection--single .select2-selection__arrow,
    .pp-filter-box .select2-container .select2-selection__arrow {
        height: 38px !important;
        width: 26px !important;
        top: 0 !important;
        right: 8px !important;
        position: absolute !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        pointer-events: none !important;
    }
    .pp-filter-box .select2-container--default .select2-selection--single .select2-selection__arrow b {
        position: static !important;
        display: inline-block !important;
        width: 6px !important;
        height: 6px !important;
        padding: 0 !important;
        margin: 0 !important;
        border: none !important;
        border-right: 1.5px solid #64748b !important;
        border-bottom: 1.5px solid #64748b !important;
        transform: rotate(45deg) !important;
        -webkit-transform: rotate(45deg) !important;
        transition: transform 0.2s ease, border-color 0.2s ease !important;
    }
    .pp-filter-box .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
        transform: rotate(-135deg) !important;
        -webkit-transform: rotate(-135deg) !important;
        border-color: #2563eb !important;
        margin-top: 2px !important;
    }
    .pp-filter-box .select2-container--default .select2-selection--single .select2-selection__clear,
    .pp-filter-box .select2-container .select2-selection__clear {
        height: 36px !important;
        line-height: 36px !important;
        margin-right: 20px !important;
        font-size: 14px !important;
        color: #94a3b8 !important;
    }

    /* Buttons (Reset & Action) */
    .pp-filter-box .btn {
        padding: 0 14px !important;
        font-weight: 600 !important;
        font-size: 12.5px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 6px !important;
        white-space: nowrap !important;
    }
    .pp-filter-box .btn.pp-btn-icon-square {
        width: 38px !important;
        min-width: 38px !important;
        max-width: 38px !important;
        padding: 0 !important;
        flex: 0 0 38px !important;
    }
    .pp-filter-box .btn-outline-secondary {
        background-color: #ffffff !important;
        color: #475569 !important;
        border-color: #cbd5e1 !important;
    }
    .pp-filter-box .btn-outline-secondary:hover {
        background-color: #f8fafc !important;
        border-color: #94a3b8 !important;
        color: #0f172a !important;
    }
    .pp-filter-box .btn-outline-primary {
        background-color: #eff6ff !important;
        border-color: #bfdbfe !important;
        color: #2563eb !important;
    }
    .pp-filter-box .btn-outline-primary:hover {
        background-color: #2563eb !important;
        border-color: #2563eb !important;
        color: #ffffff !important;
    }

    /* Segmented Control Tabs — proporsional & nyaman */
    .custom-premium-tabs {
        background-color: #f1f5f9;
        padding: 5px !important;
        border-radius: 12px !important;
        gap: 4px !important;
        box-shadow: inset 0 1px 3px rgba(0,0,0,0.02);
    }
    .custom-premium-tabs .nav-link {
        padding: 8px 18px !important;
        font-size: 13.5px !important;
        border-radius: 8px !important;
        font-weight: 600 !important;
        color: #64748b;
        transition: all 0.2s ease;
    }
    .custom-premium-tabs .nav-link:hover:not(.active) {
        color: #1e293b;
        background: rgba(255,255,255,0.6);
    }
    .custom-premium-tabs .nav-link.active {
        color: #2563eb !important;
        background-color: #ffffff !important;
        box-shadow: 0 2px 6px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04) !important;
    }
    .custom-premium-tabs .nav-link i {
        font-size: 14px !important;
    }

    .pp-status {
        display: inline-flex !important;
        align-items: center !important;
        gap: 5px !important;
        padding: 3px 8px !important;
        border-radius: 20px !important;
        font-size: 11px !important;
        font-weight: 600 !important;
        white-space: nowrap !important;
        line-height: 1.2 !important;
    }
    .pp-status::before {
        content: "";
        width: 6px;
        height: 6px;
        border-radius: 50%;
        display: inline-block;
        flex-shrink: 0;
    }
    .pp-status.draft {
        background: #fff7ed !important;
        color: #c2410c !important;
        border: 1px solid #fed7aa !important;
    }
    .pp-status.draft::before {
        background: #ea580c;
    }
    .pp-status.released {
        background: #eff6ff !important;
        color: #1d4ed8 !important;
        border: 1px solid #bfdbfe !important;
    }
    .pp-status.released::before {
        background: #2563eb;
    }
    .pp-status.work_order {
        background: #f5f3ff !important;
        color: #6d28d9 !important;
        border: 1px solid #ddd6fe !important;
    }
    .pp-status.work_order::before {
        background: #7c3aed;
    }
    .pp-status.inprod {
        background: #e0f2fe !important;
        color: #0369a1 !important;
        border: 1px solid #bae6fd !important;
    }
    .pp-status.inprod::before {
        background: #0284c7;
    }
    .pp-status.done {
        background: #f0fdf4 !important;
        color: #15803d !important;
        border: 1px solid #bbf7d0 !important;
    }
    .pp-status.done::before {
        background: #16a34a;
    }

    /* Badge merah total di tab (realtime) */
    .pp-tab-badge {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        min-width: 19px;
        height: 19px;
        padding: 0 6px;
        border-radius: 999px !important;
        background: #dc2626 !important;
        color: #fff !important;
        font-size: 10.5px !important;
        font-weight: 700 !important;
        line-height: 1 !important;
        margin-left: 4px;
    }
    .pp-tab-badge.is-empty {
        display: none !important;
    }

    /* Table spacing — selaras Stok Produk (#tableStock: th 14/16, td 12/16) */
    #tablePpPlanning,
    #tablePpJob,
    #tablePpHistori {
        width: 100% !important;
    }
    #tablePpPlanning th,
    #tablePpPlanning td,
    #tablePpJob th,
    #tablePpJob td,
    #tablePpHistori th,
    #tablePpHistori td {
        vertical-align: middle;
        padding: 12px 16px !important;
    }
    #tablePpPlanning thead th,
    #tablePpJob thead th,
    #tablePpHistori thead th {
        white-space: nowrap !important;
        color: #0F0033;
        font-size: 13px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .3px;
        background: linear-gradient(320deg, #ddeeff 0%, #DBECFF 100%);
        border-bottom: 0;
        padding: 14px 16px !important;
    }
    #tablePpPlanning tbody td,
    #tablePpJob tbody td,
    #tablePpHistori tbody td {
        color: #334155;
        font-size: 13px;
        font-family: inherit;
        border-bottom: 1px solid #eef2f7;
        background: #fff;
    }
    #tablePpPlanning .pp-cell-text,
    #tablePpPlanning .pp-item-count,
    #tablePpPlanning .pp-qty-num,
    #tablePpPlanning .pp-qty-unit,
    #tablePpHistori .pp-cell-text,
    #tablePpHistori .pp-item-count,
    #tablePpHistori .pp-qty-num,
    #tablePpHistori .pp-qty-unit,
    #tablePpJob .pp-cell-text,
    #tablePpJob .pp-item-count,
    #tablePpJob .pp-qty-num,
    #tablePpJob .pp-qty-unit {
        font-size: 13px !important;
        font-family: inherit !important;
        font-weight: 600;
        line-height: 1.35;
        color: #0f172a;
    }
    #tablePpPlanning .pp-cell-code,
    #tablePpHistori .pp-cell-code,
    #tablePpJob .pp-cell-code {
        color: #2563eb !important;
    }
    #tablePpPlanning .pp-qty-line,
    #tablePpHistori .pp-qty-line,
    #tablePpJob .pp-qty-line {
        display: flex;
        justify-content: center;
        align-items: baseline;
        gap: 5px;
        white-space: nowrap;
    }
    #tablePpPlanning .pp-qty-unit,
    #tablePpHistori .pp-qty-unit,
    #tablePpJob .pp-qty-unit {
        color: #0f172a !important;
        font-weight: 600 !important;
    }
    #tablePpPlanning .pp-item-count,
    #tablePpHistori .pp-item-count,
    #tablePpJob .pp-item-count {
        color: #334155 !important;
    }
    #tablePpPlanning tbody tr {
        transition: all 0.15s ease;
    }
    #tablePpPlanning tbody tr:hover td {
        background-color: #f8fafc !important;
    }
    #tablePpPlanning-wrap {
        position: relative;
        min-height: 0;
    }
    #tablePpPlanning-wrap.dt-pending {
        min-height: 280px;
    }
    #tablePpPlanning tbody td.dataTables_empty {
        height: 180px;
        vertical-align: middle !important;
        color: #94a3b8;
        font-size: 13px;
    }
    .pp-stages-card .pp-stage-pane .table-responsive,
    .pp-stage-card .table-responsive {
        min-height: 200px;
    }
    .pp-stage-table tbody td.dataTables_empty {
        height: 140px;
        vertical-align: middle !important;
    }
    #tablePpPlanning_wrapper .dataTables_processing {
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
        font-size: 13px;
    }
    #tablePpPlanning-wrap:not(.is-loading) .dataTables_processing {
        display: none !important;
    }
    #tablePpPlanning-wrap.is-loading .dataTables_processing {
        display: flex !important;
    }
    #tablePpPlanning-wrap.is-loading tbody {
        opacity: 0.45;
        pointer-events: none;
    }

    #tablePpPlanning .btn-action-icon,
    #tablePpHistori .btn-action-icon,
    #tablePpJob .btn-action-icon {
        width: 28px;
        height: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        color: #64748b;
        background: #fff;
        border: 1px solid #e2e8f0;
        transition: all 0.15s ease;
    }
    #tablePpPlanning .btn-action-icon.is-disabled,
    #tablePpPlanning .btn-action-icon[aria-disabled="true"] {
        opacity: 0.4;
        pointer-events: none;
        cursor: not-allowed;
        color: #94a3b8;
        background: #f8fafc;
    }
    #tablePpPlanning .btn-action-icon:hover,
    #tablePpHistori .btn-action-icon:hover,
    #tablePpJob .btn-action-icon:hover {
        color: #2563eb;
        background: #eff6ff;
        border-color: #bfdbfe;
    }
    /* Delete: hover merah (harus setelah rule biru umum) */
    #tablePpPlanning .btn-action-icon.btn-pp-delete:not(.is-disabled):hover,
    #tablePpHistori .btn-action-icon.btn-pp-delete:not(.is-disabled):hover {
        color: #dc2626 !important;
        background: #fef2f2 !important;
        border-color: #fecaca !important;
    }
    /* Kolom Aksi rata tengah */
    #tablePpPlanning th:last-child,
    #tablePpPlanning td:last-child,
    #tablePpJob th:last-child,
    #tablePpJob td:last-child,
    #tablePpHistori th:last-child,
    #tablePpHistori td:last-child {
        text-align: center !important;
        vertical-align: middle !important;
    }
    #tablePpPlanning td:last-child .d-flex,
    #tablePpJob td:last-child .d-flex,
    #tablePpHistori td:last-child .d-flex {
        justify-content: center !important;
    }

    /* Histori / Job — spacing sudah di shared block di atas; sisanya state & processing */
    #tablePpHistori tbody tr:hover td,
    #tablePpJob tbody tr:hover td,
    #tablePpPlanning tbody tr:hover td { background-color: #f8fafc !important; }
    #tablePpHistori-wrap,
    #tablePpJob-wrap {
        position: relative;
        min-height: 0;
    }
    #tablePpHistori-wrap.dt-pending,
    #tablePpJob-wrap.dt-pending {
        min-height: 280px;
    }
    #tablePpHistori tbody td.dataTables_empty,
    #tablePpJob tbody td.dataTables_empty {
        height: 180px;
        vertical-align: middle !important;
        color: #94a3b8;
        font-size: 13px;
    }
    #tablePpHistori_wrapper .dataTables_processing {
        position: absolute !important;
        top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important;
        width: 100% !important; height: 100% !important;
        margin: 0 !important; padding: 0 !important; border: 0 !important;
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.72) !important;
        z-index: 20;
        display: flex !important;
        align-items: center; justify-content: center;
        color: #1e293b; font-weight: 600; font-size: 13px;
    }
    #tablePpHistori-wrap:not(.is-loading) .dataTables_processing { display: none !important; }
    #tablePpHistori-wrap.is-loading .dataTables_processing { display: flex !important; }
    #tablePpHistori-wrap.is-loading tbody { opacity: 0.45; pointer-events: none; }

    #tablePpJob tbody tr {
        transition: all 0.15s ease;
    }
    #tablePpJob_wrapper .dataTables_processing {
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
    #tablePpJob-wrap:not(.is-loading) .dataTables_processing { display: none !important; }
    #tablePpJob-wrap.is-loading .dataTables_processing { display: flex !important; }
    #tablePpJob-wrap.is-loading tbody { opacity: 0.45; pointer-events: none; }
    #tablePpJob .btn-action-icon {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        color: #64748b;
        background: #fff;
        border: 1px solid #e2e8f0;
        transition: all 0.15s ease;
    }
    #tablePpJob .btn-action-icon:hover {
        color: #2563eb;
        background: #eff6ff;
        border-color: #bfdbfe;
    }

    /* 4 Stage Tables (Unified Card Table) */
    .pp-stage-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
    }
    .card.pp-stage-card .card-header,
    .pp-stage-card .card-header {
        background: #fff !important;
        border-bottom: 1px solid #e2e8f0 !important;
        padding: 8px 14px !important;
        min-height: auto !important;
    }
    .pp-stage-icon {
        width: 26px;
        height: 26px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        flex-shrink: 0;
    }
    .stage-icon-warning { background: #fef3c7; color: #d97706; }
    .stage-icon-primary { background: #eff6ff; color: #2563eb; }
    .stage-icon-info    { background: #e0f2fe; color: #0284c7; }
    .stage-icon-success { background: #dcfce7; color: #15803d; }

    .pp-stage-title {
        font-size: 13.5px;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
    }
    .pp-stage-badge {
        font-size: 11px !important;
        font-weight: 600 !important;
        padding: 3px 8px !important;
        border-radius: 20px !important;
        line-height: 1.2 !important;
    }
    .badge-stage-warning { background: #fef3c7 !important; color: #b45309 !important; border: 1px solid #fde68a !important; }
    .badge-stage-primary { background: #eff6ff !important; color: #1d4ed8 !important; border: 1px solid #bfdbfe !important; }
    .badge-stage-info    { background: #e0f2fe !important; color: #0369a1 !important; border: 1px solid #bae6fd !important; }
    .badge-stage-success { background: #dcfce7 !important; color: #15803d !important; border: 1px solid #bbf7d0 !important; }

    .pp-stage-table {
        width: 100% !important;
        table-layout: fixed;
        margin-bottom: 0 !important;
    }
    .pp-stage-table th,
    .pp-stage-table td {
        vertical-align: middle;
        padding: 8px 12px !important;
    }
    .pp-stage-table thead th {
        color: #475569;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .3px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .pp-stage-table tbody td {
        color: #334155;
        font-size: 12px;
        border-bottom: 1px solid #f1f5f9;
        background: #fff;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .pp-stage-table tbody tr:last-child td {
        border-bottom: none;
    }
    .pp-stage-table tbody tr:hover td {
        background-color: #f8fafc !important;
    }
    .pp-stage-table .btn-action-icon {
        width: 28px !important;
        height: 28px !important;
        min-width: 28px !important;
        font-size: 12px !important;
    }
    .pp-stage-card .table-responsive {
        min-height: 180px;
        overflow-x: hidden !important;
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
    }
    .pp-stage-card .dataTables_wrapper {
        display: flex !important;
        flex-direction: column;
        flex: 1 1 auto;
        min-height: 100%;
        width: 100%;
    }
    .pp-stage-card .dataTables_wrapper::after {
        display: none !important;
    }
    .pp-stage-card .dataTables_wrapper > table {
        order: 1;
        width: 100% !important;
    }
    .pp-stage-card .dataTables_paginate {
        order: 2;
        margin: auto 0 0 0 !important;
        padding: 8px 6px !important;
        border-top: 1px solid #f1f5f9 !important;
        background: #fff !important;
        display: flex !important;
        justify-content: center !important;
        align-items: center !important;
        gap: 2px !important;
        width: 100%;
        flex-shrink: 0;
        float: none !important;
        overflow-x: auto !important;
        overflow-y: hidden !important;
    }
    .pp-stage-card .dataTables_paginate .pagination {
        display: inline-flex !important;
        flex-wrap: nowrap !important;
        justify-content: center !important;
        align-items: center !important;
        gap: 3px !important;
        margin: 0 !important;
        white-space: nowrap !important;
        flex-shrink: 0 !important;
    }
    .pp-stage-card .dataTables_paginate .paginate_button,
    .pp-stage-card .dataTables_paginate .page-item {
        margin: 0 !important;
        float: none !important;
        flex: 0 0 auto !important;
    }
    .pp-stage-card .dataTables_paginate .paginate_button .page-link,
    .pp-stage-card .dataTables_paginate .page-link,
    .pp-stage-card .dataTables_paginate .paginate_button {
        min-width: 26px !important;
        width: auto !important;
        height: 26px !important;
        padding: 0 6px !important;
        line-height: 24px !important;
        font-size: 11px !important;
        font-weight: 600 !important;
        border-radius: 6px !important;
        margin: 0 !important;
        border: 1px solid #cbd5e1 !important;
        background: #fff !important;
        color: #0f172a !important;
        box-shadow: none !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        white-space: nowrap !important;
    }
    .pp-stage-card .dataTables_paginate .paginate_button.previous .page-link,
    .pp-stage-card .dataTables_paginate .paginate_button.next .page-link {
        margin: 0 !important;
    }
    .pp-stage-card .dataTables_paginate span {
        display: inline-flex !important;
        align-items: center !important;
        flex-wrap: nowrap !important;
        gap: 3px !important;
        flex: 0 0 auto !important;
        white-space: nowrap !important;
    }
    .pp-stage-card .dataTables_paginate .paginate_button.current,
    .pp-stage-card .dataTables_paginate .paginate_button.current .page-link,
    .pp-stage-card .dataTables_paginate .page-item.active .page-link {
        background: #eff6ff !important;
        color: #1d4ed8 !important;
        border-color: #2563eb !important;
        font-weight: 700 !important;
        box-shadow: inset 0 0 0 1px #2563eb !important;
    }
    .pp-stage-card .dataTables_paginate .paginate_button:hover:not(.current):not(.disabled) .page-link,
    .pp-stage-card .dataTables_paginate .paginate_button:hover:not(.current):not(.disabled),
    .pp-stage-card .dataTables_paginate .page-item:not(.active):not(.disabled) .page-link:hover {
        background: #f8fafc !important;
        color: #1d4ed8 !important;
        border-color: #93c5fd !important;
    }
    .pp-stage-card .dataTables_paginate .paginate_button.disabled,
    .pp-stage-card .dataTables_paginate .paginate_button.disabled .page-link,
    .pp-stage-card .dataTables_paginate .page-item.disabled .page-link {
        opacity: 0.45 !important;
        cursor: not-allowed !important;
        background: #f8fafc !important;
        color: #94a3b8 !important;
    }

    /* Footer DT: Show entries kiri + pagination kanan — satu baris di bawah tabel */
    #tablePpPlanning_wrapper,
    #tablePpJob_wrapper,
    #tablePpHistori_wrapper {
        display: grid !important;
        grid-template-columns: 1fr auto;
        grid-template-areas:
            "table table"
            "length paginate";
        align-items: center;
        column-gap: 12px;
        row-gap: 10px;
        width: 100%;
        min-height: 0 !important;
        padding-bottom: 10px;
    }
    #tablePpPlanning_wrapper::after,
    #tablePpJob_wrapper::after,
    #tablePpHistori_wrapper::after {
        display: none !important;
    }
    #tablePpPlanning_wrapper > .dataTables_filter,
    #tablePpJob_wrapper > .dataTables_filter,
    #tablePpHistori_wrapper > .dataTables_filter,
    #tablePpPlanning_wrapper > .dt-buttons,
    #tablePpJob_wrapper > .dt-buttons,
    #tablePpHistori_wrapper > .dt-buttons {
        display: none !important;
    }
    #tablePpPlanning_wrapper > table,
    #tablePpJob_wrapper > table,
    #tablePpHistori_wrapper > table {
        grid-area: table;
        width: 100% !important;
        margin: 0 !important;
    }
    #tablePpPlanning_wrapper > .dataTables_length,
    #tablePpJob_wrapper > .dataTables_length,
    #tablePpHistori_wrapper > .dataTables_length {
        grid-area: length;
        float: none !important;
        margin: 0 0 0 16px !important;
        padding: 0 !important;
        clear: none !important;
        justify-self: start;
    }
    #tablePpPlanning_wrapper > .dataTables_info,
    #tablePpJob_wrapper > .dataTables_info,
    #tablePpHistori_wrapper > .dataTables_info {
        display: none !important;
    }
    #tablePpPlanning_wrapper > .dataTables_paginate,
    #tablePpJob_wrapper > .dataTables_paginate,
    #tablePpHistori_wrapper > .dataTables_paginate {
        grid-area: paginate;
        float: none !important;
        margin: 0 16px 0 0 !important;
        padding: 0 !important;
        clear: none !important;
        width: auto !important;
        justify-self: end;
    }
    #tablePpPlanning_wrapper .dataTables_paginate .pagination,
    #tablePpJob_wrapper .dataTables_paginate .pagination,
    #tablePpHistori_wrapper .dataTables_paginate .pagination {
        display: flex !important;
        flex-wrap: nowrap !important;
        justify-content: flex-end !important;
        gap: 6px !important;
        margin: 0 !important;
        white-space: nowrap !important;
    }
    #tablePpPlanning_wrapper .dataTables_paginate .paginate_button,
    #tablePpJob_wrapper .dataTables_paginate .paginate_button,
    #tablePpHistori_wrapper .dataTables_paginate .paginate_button {
        margin: 0 !important;
        float: none !important;
    }
    #tablePpPlanning_wrapper .dataTables_paginate .page-link,
    #tablePpJob_wrapper .dataTables_paginate .page-link,
    #tablePpHistori_wrapper .dataTables_paginate .page-link {
        min-width: 34px !important;
        height: 34px !important;
        padding: 0 10px !important;
        line-height: 32px !important;
        font-size: 13px !important;
        font-weight: 600 !important;
        border-radius: 8px !important;
        border: 1px solid #cbd5e1 !important;
        background: #fff !important;
        color: #0f172a !important;
        margin: 0 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        box-shadow: none !important;
    }
    #tablePpPlanning_wrapper .dataTables_paginate .paginate_button.previous .page-link,
    #tablePpPlanning_wrapper .dataTables_paginate .paginate_button.next .page-link,
    #tablePpJob_wrapper .dataTables_paginate .paginate_button.previous .page-link,
    #tablePpJob_wrapper .dataTables_paginate .paginate_button.next .page-link,
    #tablePpHistori_wrapper .dataTables_paginate .paginate_button.previous .page-link,
    #tablePpHistori_wrapper .dataTables_paginate .paginate_button.next .page-link {
        margin: 0 !important;
    }
    #tablePpPlanning_wrapper .dataTables_paginate .page-item.active .page-link,
    #tablePpPlanning_wrapper .dataTables_paginate .paginate_button.current .page-link,
    #tablePpPlanning_wrapper .dataTables_paginate .paginate_button.current,
    #tablePpJob_wrapper .dataTables_paginate .page-item.active .page-link,
    #tablePpJob_wrapper .dataTables_paginate .paginate_button.current .page-link,
    #tablePpJob_wrapper .dataTables_paginate .paginate_button.current,
    #tablePpHistori_wrapper .dataTables_paginate .page-item.active .page-link,
    #tablePpHistori_wrapper .dataTables_paginate .paginate_button.current .page-link,
    #tablePpHistori_wrapper .dataTables_paginate .paginate_button.current {
        background: #eff6ff !important;
        color: #1d4ed8 !important;
        border-color: #2563eb !important;
        font-weight: 700 !important;
        box-shadow: inset 0 0 0 1px #2563eb !important;
    }
    #tablePpPlanning_wrapper .dataTables_paginate .page-item:not(.active):not(.disabled) .page-link:hover,
    #tablePpJob_wrapper .dataTables_paginate .page-item:not(.active):not(.disabled) .page-link:hover,
    #tablePpHistori_wrapper .dataTables_paginate .page-item:not(.active):not(.disabled) .page-link:hover {
        background: #f8fafc !important;
        color: #1d4ed8 !important;
        border-color: #93c5fd !important;
    }
    #tablePpPlanning_wrapper .dataTables_paginate .page-item.disabled .page-link,
    #tablePpJob_wrapper .dataTables_paginate .page-item.disabled .page-link,
    #tablePpHistori_wrapper .dataTables_paginate .page-item.disabled .page-link {
        opacity: 0.45 !important;
        background: #f8fafc !important;
        color: #94a3b8 !important;
    }

    /* Theme default .tab-content padding-top: 32px */
    .tab-content {
        padding-top: 0 !important;
        margin-top: 10px !important;
    }

    /* Modal Buat PP */
    #modalAddPlanning.modal { overflow: hidden !important; }
    html:has(#modalAddPlanning.show),
    body:has(#modalAddPlanning.show) { overflow: hidden !important; }
    #modalAddPlanning .modal-dialog {
        height: auto !important;
        max-height: calc(100dvh - 2rem) !important;
        margin: 1rem auto !important;
        display: flex !important;
        align-items: center !important;
    }
    #modalAddPlanning .modal-content {
        height: auto !important;
        max-height: calc(100dvh - 2rem) !important;
        min-height: 0 !important;
        display: flex !important;
        flex-direction: column !important;
        overflow: hidden !important;
    }
    #modalAddPlanning form {
        flex: 1 1 auto !important;
        min-height: 0 !important;
        display: flex !important;
        flex-direction: column !important;
        overflow: hidden !important;
    }
    #modalAddPlanning .modal-header,
    #modalAddPlanning .modal-footer { flex: 0 0 auto !important; }
    #modalAddPlanning .modal-body {
        flex: 1 1 auto !important;
        min-height: 0 !important;
        overflow: hidden !important;
    }
    #modalAddPlanning #pp-table-items-scroll {
        flex: 1 1 auto;
        min-height: 140px;
    }
    #modalAddPlanning .pp-item-sku {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 11px;
        color: #64748b;
    }
</style>
@endsection

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">

        @component('components.page-header')
            @slot('title')
                Production Planning
            @endslot
        @endcomponent

        <div class="row g-2 mb-3" id="pp-summary-cards">
            <div class="col-xl col-md-4 col-sm-6">
                <div class="card pp-kpi-card mb-0 h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="pp-kpi-icon pp-kpi-violet"><i class="fe fe-calendar"></i></div>
                            <div class="overflow-hidden">
                                <div class="pp-kpi-label text-truncate">Rekomendasi</div>
                                <div class="d-flex align-items-baseline gap-1">
                                    <span class="pp-kpi-val" data-pp-sum="rekomendasi">0</span>
                                    <span class="pp-kpi-sub">Order</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl col-md-4 col-sm-6">
                <div class="card pp-kpi-card mb-0 h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="pp-kpi-icon pp-kpi-orange"><i class="fe fe-edit-3"></i></div>
                            <div class="overflow-hidden">
                                <div class="pp-kpi-label text-truncate">Draft</div>
                                <div class="d-flex align-items-baseline gap-1">
                                    <span class="pp-kpi-val" data-pp-sum="draft">0</span>
                                    <span class="pp-kpi-sub">Order</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl col-md-4 col-sm-6">
                <div class="card pp-kpi-card mb-0 h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="pp-kpi-icon pp-kpi-blue"><i class="fe fe-clipboard"></i></div>
                            <div class="overflow-hidden">
                                <div class="pp-kpi-label text-truncate">Released to Production</div>
                                <div class="d-flex align-items-baseline gap-1">
                                    <span class="pp-kpi-val" data-pp-sum="released">0</span>
                                    <span class="pp-kpi-sub">Order</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl col-md-4 col-sm-6">
                <div class="card pp-kpi-card mb-0 h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="pp-kpi-icon pp-kpi-indigo"><i class="fe fe-truck"></i></div>
                            <div class="overflow-hidden">
                                <div class="pp-kpi-label text-truncate">Work Order</div>
                                <div class="d-flex align-items-baseline gap-1">
                                    <span class="pp-kpi-val" data-pp-sum="work_order">0</span>
                                    <span class="pp-kpi-sub">Order</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl col-md-4 col-sm-6">
                <div class="card pp-kpi-card mb-0 h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="pp-kpi-icon pp-kpi-sky"><i class="fe fe-activity"></i></div>
                            <div class="overflow-hidden">
                                <div class="pp-kpi-label text-truncate">In Production</div>
                                <div class="d-flex align-items-baseline gap-1">
                                    <span class="pp-kpi-val" data-pp-sum="inprod">0</span>
                                    <span class="pp-kpi-sub">Order</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl col-md-4 col-sm-6">
                <div class="card pp-kpi-card mb-0 h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="pp-kpi-icon pp-kpi-green"><i class="fe fe-check-circle"></i></div>
                            <div class="overflow-hidden">
                                <div class="pp-kpi-label text-truncate">Completed</div>
                                <div class="d-flex align-items-baseline gap-1">
                                    <span class="pp-kpi-val" data-pp-sum="done">0</span>
                                    <span class="pp-kpi-sub">Order</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex mb-3">
            <ul class="nav custom-premium-tabs" id="pp-main-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active d-flex align-items-center gap-2" id="pp-tab-planning" data-bs-toggle="tab"
                        data-bs-target="#pp-pane-planning" type="button" role="tab">
                        <i class="fe fe-list"></i> Daftar Planning
                        <span class="pp-tab-badge is-empty" data-pp-tab-badge="planning">0</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link d-flex align-items-center gap-2" id="pp-tab-job" data-bs-toggle="tab"
                        data-bs-target="#pp-pane-job" type="button" role="tab">
                        <i class="fe fe-file-text"></i> Job Order
                        <span class="pp-tab-badge is-empty" data-pp-tab-badge="job">0</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link d-flex align-items-center gap-2" id="pp-tab-bahan" data-bs-toggle="tab"
                        data-bs-target="#pp-pane-bahan" type="button" role="tab">
                        <i class="fe fe-package"></i> ACC Bahan
                        <span class="pp-tab-badge is-empty" data-pp-tab-badge="bahan">0</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link d-flex align-items-center gap-2" id="pp-tab-histori" data-bs-toggle="tab"
                        data-bs-target="#pp-pane-histori" type="button" role="tab">
                        <i class="fe fe-clock"></i> Histori Produksi
                        <span class="pp-tab-badge is-empty" data-pp-tab-badge="histori">0</span>
                    </button>
                </li>
            </ul>
        </div>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="pp-pane-planning" role="tabpanel">
                <div class="card card-table mb-2">
                    <div class="pp-filter-box">
                        <div class="row g-2 align-items-end">
                            <div class="col-xl-3 col-md-6">
                                <label for="pp_filter_date">Tanggal</label>
                                <div class="cal-icon cal-icon-info">
                                    <input type="text" class="form-control" id="pp_filter_date" placeholder="DD-MM-YYYY — DD-MM-YYYY" readonly>
                                </div>
                            </div>
                            <div class="col-xl-2 col-md-6">
                                <label for="pp_filter_status">Status</label>
                                <select class="form-select" id="pp_filter_status">
                                    <option value="" selected>Semua</option>
                                    <option value="draft">Draft</option>
                                    <option value="released">Released to Production</option>
                                    <option value="inprod">In Production</option>
                                </select>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <label for="pp_filter_product">Produk</label>
                                <select class="form-select" id="pp_filter_product">
                                    <option value=""></option>
                                </select>
                            </div>
                            <div class="col-xl-2 col-md-6">
                                <label for="pp_filter_supervisor">Supervisor</label>
                                <select class="form-select" id="pp_filter_supervisor">
                                    <option value=""></option>
                                </select>
                            </div>
                            <div class="col-xl-2 col-md-12">
                                <label class="d-none d-md-block opacity-0 user-select-none" aria-hidden="true">&nbsp;</label>
                                <button type="button" class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center gap-1" id="pp_filter_clear">
                                    <i class="fe fe-rotate-ccw"></i> Reset
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive dt-pending" id="tablePpPlanning-wrap">
                            <div class="dt-skeleton" aria-hidden="true">
                                <div style="padding: 16px 25px;">
                                    <span class="skel-text" style="width: 250px; height: 38px; border-radius: 20px;"></span>
                                </div>
                                <div class="dt-skeleton-head" style="grid-template-columns: 13% 11% 22% 14% 14% 10% 10% 6%;">
                                    <span style="width:60%"></span>
                                    <span style="width:60%"></span>
                                    <span style="width:70%"></span>
                                    <span style="width:50%"></span>
                                    <span style="width:60%"></span>
                                    <span style="width:50%"></span>
                                    <span style="width:60%"></span>
                                    <span style="width:40%"></span>
                                </div>
                                <div class="dt-skeleton-body">
                                    @for ($i = 0; $i < 5; $i++)
                                        <div class="dt-skeleton-row" style="grid-template-columns: 13% 11% 22% 14% 14% 10% 10% 6%;">
                                            <span class="skel-text" style="width:70%"></span>
                                            <span class="skel-text" style="width:60%"></span>
                                            <span class="skel-text" style="width:80%"></span>
                                            <span class="skel-text" style="width:50%"></span>
                                            <span class="skel-badge" style="width:65%"></span>
                                            <span class="skel-badge" style="width:55%"></span>
                                            <span class="skel-text" style="width:60%"></span>
                                            <span class="skel-btn" style="justify-self:center"></span>
                                        </div>
                                    @endfor
                                </div>
                            </div>
                            <table class="table table-center table-hover mb-0" id="tablePpPlanning">
                                <thead>
                                    <tr>
                                        <th>Kode Produksi</th>
                                        <th>Tanggal</th>
                                        <th>Item</th>
                                        <th class="text-center">Rekomendasi QTY</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">WO</th>
                                        <th class="text-center">Sumber</th>
                                        <th>Dibuat Oleh</th>
                                        <th class="no-sort text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="pp-pane-job" role="tabpanel">
                <div class="card card-table mb-2">
                    <div class="pp-filter-box">
                        <div class="row g-2 align-items-end">
                            <div class="col-xl-3 col-md-6">
                                <label for="pp_job_filter_date">Tanggal</label>
                                <div class="cal-icon cal-icon-info">
                                    <input type="text" class="form-control" id="pp_job_filter_date"
                                        placeholder="DD-MM-YYYY — DD-MM-YYYY" readonly>
                                </div>
                            </div>
                            <div class="col-xl-2 col-md-6">
                                <label for="pp_job_filter_status">Status</label>
                                <select class="form-select" id="pp_job_filter_status">
                                    <option value="" selected>Semua (aktif)</option>
                                    <option value="inprod">In Production</option>
                                    <option value="done">Completed</option>
                                </select>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <label for="pp_job_filter_product">Produk</label>
                                <select class="form-select" id="pp_job_filter_product">
                                    <option value=""></option>
                                </select>
                            </div>
                            <div class="col-xl-2 col-md-6">
                                <label for="pp_job_filter_supervisor">PIC</label>
                                <select class="form-select" id="pp_job_filter_supervisor">
                                    <option value=""></option>
                                </select>
                            </div>
                            <div class="col-xl-2 col-md-12">
                                <label class="d-none d-md-block opacity-0 user-select-none" aria-hidden="true">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="button"
                                        class="btn btn-outline-secondary flex-grow-1 d-flex align-items-center justify-content-center gap-1"
                                        id="pp_job_filter_clear">
                                        <i class="fe fe-rotate-ccw"></i> Reset
                                    </button>
                                    <a class="btn pg-btn-print pp-btn-icon-square d-flex align-items-center justify-content-center"
                                        href="{{ route('productionWorkOrders', ['monitor'=>1]) }}" target="_blank" rel="noopener"
                                        title="Monitor fullscreen">
                                        <i class="fe fe-maximize"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive dt-pending" id="tablePpJob-wrap">
                            <div class="dt-skeleton" aria-hidden="true">
                                <div style="padding: 16px 25px;">
                                    <span class="skel-text" style="width: 250px; height: 38px; border-radius: 20px;"></span>
                                </div>
                                <div class="dt-skeleton-head" style="grid-template-columns: 16% 12% 28% 20% 14% 10%;">
                                    <span style="width:60%"></span>
                                    <span style="width:60%"></span>
                                    <span style="width:70%"></span>
                                    <span style="width:50%"></span>
                                    <span style="width:60%"></span>
                                    <span style="width:40%"></span>
                                </div>
                                <div class="dt-skeleton-body">
                                    @for ($i = 0; $i < 5; $i++)
                                        <div class="dt-skeleton-row" style="grid-template-columns: 16% 12% 28% 20% 14% 10%;">
                                            <span class="skel-text" style="width:70%"></span>
                                            <span class="skel-text" style="width:60%"></span>
                                            <span class="skel-text" style="width:80%"></span>
                                            <span class="skel-text" style="width:60%"></span>
                                            <span class="skel-badge" style="width:65%"></span>
                                            <span class="skel-btn" style="justify-self:center"></span>
                                        </div>
                                    @endfor
                                </div>
                            </div>
                            <table class="table table-center table-hover mb-0" id="tablePpJob">
                                <thead>
                                    <tr>
                                        <th>Kode WO</th>
                                        <th>Tanggal</th>
                                        <th>PIC</th>
                                        <th>SPKP / PP</th>
                                        <th class="text-center">Status</th>
                                        <th class="no-sort text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="pp-pane-bahan" role="tabpanel">
                <div class="card card-table mb-2">
                    <div class="pp-filter-box">
                        <div class="row g-2 align-items-end">
                            <div class="col-xl-3 col-md-6">
                                <label for="pp_bahan_filter_stage">Tahap ACC</label>
                                <select class="form-select" id="pp_bahan_filter_stage">
                                    <option value="" selected>Semua (menunggu)</option>
                                    <option value="awaiting_ops">Menunggu Kepala Ops</option>
                                    <option value="awaiting_qc">Menunggu QC</option>
                                </select>
                            </div>
                            <div class="col-xl-2 col-md-12">
                                <label class="d-none d-md-block opacity-0 user-select-none" aria-hidden="true">&nbsp;</label>
                                <button type="button"
                                    class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center gap-1"
                                    id="pp_bahan_filter_clear">
                                    <i class="fe fe-rotate-ccw"></i> Reset
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive dt-pending" id="tablePpBahan-wrap">
                            <div class="dt-skeleton" aria-hidden="true">
                                <div style="padding: 16px 25px;">
                                    <span class="skel-text" style="width: 250px; height: 38px; border-radius: 20px;"></span>
                                </div>
                                <div class="dt-skeleton-head" style="grid-template-columns: 16% 16% 18% 16% 16% 18%;">
                                    <span style="width:60%"></span><span style="width:60%"></span>
                                    <span style="width:70%"></span><span style="width:50%"></span>
                                    <span style="width:60%"></span><span style="width:40%"></span>
                                </div>
                                <div class="dt-skeleton-body">
                                    @for ($i = 0; $i < 5; $i++)
                                        <div class="dt-skeleton-row" style="grid-template-columns: 16% 16% 18% 16% 16% 18%;">
                                            <span class="skel-text" style="width:70%"></span>
                                            <span class="skel-text" style="width:60%"></span>
                                            <span class="skel-text" style="width:80%"></span>
                                            <span class="skel-text" style="width:60%"></span>
                                            <span class="skel-badge" style="width:65%"></span>
                                            <span class="skel-btn" style="justify-self:center"></span>
                                        </div>
                                    @endfor
                                </div>
                            </div>
                            <table class="table table-center table-hover mb-0" id="tablePpBahan">
                                <thead>
                                    <tr>
                                        <th>No. STB</th>
                                        <th>WO</th>
                                        <th>PIC</th>
                                        <th>PP</th>
                                        <th class="text-center">Tahap</th>
                                        <th class="no-sort text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="pp-pane-histori" role="tabpanel">
                <div class="card card-table mb-2">
                    <div class="pp-filter-box">
                        <div class="row g-2 align-items-end">
                            <div class="col-xl-4 col-md-6">
                                <label for="pp_histori_filter_date">Tanggal Selesai</label>
                                <div class="cal-icon cal-icon-info">
                                    <input type="text" class="form-control" id="pp_histori_filter_date"
                                        placeholder="DD-MM-YYYY — DD-MM-YYYY" readonly>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <label for="pp_histori_filter_product">Produk</label>
                                <select class="form-select" id="pp_histori_filter_product">
                                    <option value=""></option>
                                </select>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <label for="pp_histori_filter_supervisor">Supervisor</label>
                                <select class="form-select" id="pp_histori_filter_supervisor">
                                    <option value=""></option>
                                </select>
                            </div>
                            <div class="col-xl-2 col-md-12">
                                <label class="d-none d-md-block opacity-0 user-select-none" aria-hidden="true">&nbsp;</label>
                                <button type="button"
                                    class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center gap-1"
                                    id="pp_histori_filter_clear">
                                    <i class="fe fe-rotate-ccw"></i> Reset
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive dt-pending" id="tablePpHistori-wrap">
                            <div class="dt-skeleton" aria-hidden="true">
                                <div style="padding: 16px 25px;">
                                    <span class="skel-text" style="width: 250px; height: 38px; border-radius: 20px;"></span>
                                </div>
                                <div class="dt-skeleton-head" style="grid-template-columns: 14% 12% 28% 14% 14% 12% 6%;">
                                    <span style="width:60%"></span>
                                    <span style="width:60%"></span>
                                    <span style="width:70%"></span>
                                    <span style="width:50%"></span>
                                    <span style="width:60%"></span>
                                    <span style="width:60%"></span>
                                    <span style="width:40%"></span>
                                </div>
                                <div class="dt-skeleton-body">
                                    @for ($i = 0; $i < 5; $i++)
                                        <div class="dt-skeleton-row" style="grid-template-columns: 14% 12% 28% 14% 14% 12% 6%;">
                                            <span class="skel-text" style="width:70%"></span>
                                            <span class="skel-text" style="width:60%"></span>
                                            <span class="skel-text" style="width:80%"></span>
                                            <span class="skel-text" style="width:50%"></span>
                                            <span class="skel-text" style="width:60%"></span>
                                            <span class="skel-badge" style="width:65%"></span>
                                            <span class="skel-btn" style="justify-self:center"></span>
                                        </div>
                                    @endfor
                                </div>
                            </div>
                            <table class="table table-center table-hover mb-0" id="tablePpHistori">
                                <thead>
                                    <tr>
                                        <th>Kode Produksi</th>
                                        <th>Tanggal Selesai</th>
                                        <th>Item</th>
                                        <th class="text-center">Qty</th>
                                        <th>Supervisor</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">WO</th>
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

        <!-- Stage Tables: 4 card (tanpa Draft — draft ada di Daftar Planning) -->
        <div class="row g-3 mb-4" id="pp-stage-tables">
            <!-- 1. Released -->
            <div class="col-xl-3 col-lg-6 col-md-12 d-flex flex-column">
                <div class="card card-table pp-stage-card h-100 mb-0 d-flex flex-column">
                    <div class="card-header d-flex align-items-center justify-content-between py-2 px-3 bg-white border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <span class="pp-stage-icon stage-icon-primary"><i class="fe fe-clipboard"></i></span>
                            <h6 class="pp-stage-title mb-0">Released</h6>
                        </div>
                        <span class="badge pp-stage-badge badge-stage-primary" id="countPpReleased">0 Data</span>
                    </div>
                    <div class="card-body p-0 d-flex flex-column justify-content-between flex-grow-1">
                        <div class="table-responsive dt-pending flex-grow-1" id="tablePpReleased-wrap">
                            <div class="dt-skeleton" aria-hidden="true">
                                <div class="dt-skeleton-head" style="grid-template-columns: 38% 34% 28%;">
                                    <span style="width:60%"></span><span style="width:50%"></span><span style="width:40%"></span>
                                </div>
                                <div class="dt-skeleton-body">
                                    @for ($i = 0; $i < 3; $i++)
                                        <div class="dt-skeleton-row" style="grid-template-columns: 38% 34% 28%;">
                                            <span class="skel-text" style="width:70%"></span>
                                            <span class="skel-text" style="width:50%"></span>
                                            <span class="skel-btn" style="justify-self:end;width:28px;height:28px;"></span>
                                        </div>
                                    @endfor
                                </div>
                            </div>
                            <table class="table table-hover mb-0 pp-stage-table" id="tablePpReleased">
                                <thead>
                                    <tr>
                                        <th>Kode</th>
                                        <th>Tanggal</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Work Order -->
            <div class="col-xl-3 col-lg-6 col-md-12 d-flex flex-column">
                <div class="card card-table pp-stage-card h-100 mb-0 d-flex flex-column">
                    <div class="card-header d-flex align-items-center justify-content-between py-2 px-3 bg-white border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <span class="pp-stage-icon stage-icon-primary"><i class="fe fe-truck"></i></span>
                            <h6 class="pp-stage-title mb-0">Work Order</h6>
                        </div>
                        <span class="badge pp-stage-badge badge-stage-primary" id="countPpWorkOrder">0 Data</span>
                    </div>
                    <div class="card-body p-0 d-flex flex-column justify-content-between flex-grow-1">
                        <div class="table-responsive dt-pending flex-grow-1" id="tablePpWorkOrder-wrap">
                            <div class="dt-skeleton" aria-hidden="true">
                                <div class="dt-skeleton-head" style="grid-template-columns: 38% 34% 28%;">
                                    <span style="width:60%"></span><span style="width:50%"></span><span style="width:40%"></span>
                                </div>
                                <div class="dt-skeleton-body">
                                    @for ($i = 0; $i < 3; $i++)
                                        <div class="dt-skeleton-row" style="grid-template-columns: 38% 34% 28%;">
                                            <span class="skel-text" style="width:70%"></span>
                                            <span class="skel-text" style="width:50%"></span>
                                            <span class="skel-btn" style="justify-self:end;width:28px;height:28px;"></span>
                                        </div>
                                    @endfor
                                </div>
                            </div>
                            <table class="table table-hover mb-0 pp-stage-table" id="tablePpWorkOrder">
                                <thead>
                                    <tr>
                                        <th>Kode</th>
                                        <th>Tanggal</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Produksi -->
            <div class="col-xl-3 col-lg-6 col-md-12 d-flex flex-column">
                <div class="card card-table pp-stage-card h-100 mb-0 d-flex flex-column">
                    <div class="card-header d-flex align-items-center justify-content-between py-2 px-3 bg-white border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <span class="pp-stage-icon stage-icon-info"><i class="fe fe-activity"></i></span>
                            <h6 class="pp-stage-title mb-0">Produksi</h6>
                        </div>
                        <span class="badge pp-stage-badge badge-stage-info" id="countPpProduksi">0 Data</span>
                    </div>
                    <div class="card-body p-0 d-flex flex-column justify-content-between flex-grow-1">
                        <div class="table-responsive dt-pending flex-grow-1" id="tablePpProduksi-wrap">
                            <div class="dt-skeleton" aria-hidden="true">
                                <div class="dt-skeleton-head" style="grid-template-columns: 38% 34% 28%;">
                                    <span style="width:60%"></span><span style="width:50%"></span><span style="width:40%"></span>
                                </div>
                                <div class="dt-skeleton-body">
                                    @for ($i = 0; $i < 3; $i++)
                                        <div class="dt-skeleton-row" style="grid-template-columns: 38% 34% 28%;">
                                            <span class="skel-text" style="width:70%"></span>
                                            <span class="skel-text" style="width:50%"></span>
                                            <span class="skel-btn" style="justify-self:end;width:28px;height:28px;"></span>
                                        </div>
                                    @endfor
                                </div>
                            </div>
                            <table class="table table-hover mb-0 pp-stage-table" id="tablePpProduksi">
                                <thead>
                                    <tr>
                                        <th>Kode</th>
                                        <th>Tanggal</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Selesai Produksi -->
            <div class="col-xl-3 col-lg-6 col-md-12 d-flex flex-column">
                <div class="card card-table pp-stage-card h-100 mb-0 d-flex flex-column">
                    <div class="card-header d-flex align-items-center justify-content-between py-2 px-3 bg-white border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <span class="pp-stage-icon stage-icon-success"><i class="fe fe-check-circle"></i></span>
                            <h6 class="pp-stage-title mb-0">Selesai Produksi</h6>
                        </div>
                        <span class="badge pp-stage-badge badge-stage-success" id="countPpSelesai">0 Data</span>
                    </div>
                    <div class="card-body p-0 d-flex flex-column justify-content-between flex-grow-1">
                        <div class="table-responsive dt-pending flex-grow-1" id="tablePpSelesai-wrap">
                            <div class="dt-skeleton" aria-hidden="true">
                                <div class="dt-skeleton-head" style="grid-template-columns: 38% 34% 28%;">
                                    <span style="width:60%"></span><span style="width:50%"></span><span style="width:40%"></span>
                                </div>
                                <div class="dt-skeleton-body">
                                    @for ($i = 0; $i < 3; $i++)
                                        <div class="dt-skeleton-row" style="grid-template-columns: 38% 34% 28%;">
                                            <span class="skel-text" style="width:70%"></span>
                                            <span class="skel-text" style="width:50%"></span>
                                            <span class="skel-btn" style="justify-self:end;width:28px;height:28px;"></span>
                                        </div>
                                    @endfor
                                </div>
                            </div>
                            <table class="table table-hover mb-0 pp-stage-table" id="tablePpSelesai">
                                <thead>
                                    <tr>
                                        <th>Kode</th>
                                        <th>Tanggal</th>
                                        <th class="text-end">Aksi</th>
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

<style>
    #modalAddPlanning .modal-dialog {
        max-width: 840px;
        margin: 1.5rem auto;
    }
    #modalAddPlanning .modal-content {
        border-radius: 16px;
        overflow: hidden;
        border: none;
        box-shadow: 0 20px 45px rgba(0, 0, 0, 0.2);
    }
    #modalAddPlanning .modal-header {
        background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%) !important;
        padding: 18px 24px;
        border: 0;
    }
    #modalAddPlanning .modal-body {
        background: #ffffff;
        padding: 24px !important;
    }
    #modalAddPlanning .select2-container {
        width: 100% !important;
    }
    #modalAddPlanning .select2-container .select2-selection--single {
        height: 38px !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 6px !important;
        display: flex !important;
        align-items: center !important;
    }
    #modalAddPlanning .select2-container .select2-selection__rendered {
        line-height: 36px !important;
        padding-left: 10px !important;
        font-size: 13px !important;
    }
    #modalAddPlanning .select2-container .select2-selection__arrow {
        height: 36px !important;
    }
    #modalAddPlanning .select2-dropdown {
        z-index: 1065 !important;
    }
    #tablePpFormItems thead th {
        background: #f8fafc !important;
        color: #475569 !important;
        font-size: 11px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.4px !important;
        border-bottom: 2px solid #e2e8f0 !important;
        padding: 9px 12px !important;
    }
    #tablePpFormItems tbody td {
        padding: 8px 12px !important;
        vertical-align: middle !important;
        font-size: 13px !important;
        border-bottom: 1px solid #f1f5f9 !important;
    }
    #tablePpFormItems tbody tr:hover td {
        background-color: #f8fafc !important;
    }
</style>

<div class="modal custom-modal fade pg-modal--form" id="modalAddPlanning" tabindex="-1"
    aria-labelledby="modalAddPlanningLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-focus="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content d-flex flex-column">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="pg-modal-icon">
                        <i class="fe fe-calendar"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-bold text-white modal-title" id="modalAddPlanningLabel"
                            style="font-size:16px;letter-spacing:.2px;">Buat Production Planning</h5>
                        <small class="d-block text-white-50">Rencana produksi manual</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formAddPlanning" class="d-flex flex-column flex-grow-1" style="margin:0;min-height:0;" onsubmit="return false;">
                <div class="modal-body p-4 bg-white d-flex flex-column flex-grow-1" style="overflow:hidden;">
                    <!-- Baris 1: Tanggal Rencana & Catatan -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-5 col-12">
                            <label class="form-label mb-1 text-muted fw-bold text-uppercase" style="font-size:11px;letter-spacing:.3px;" for="add_pp_date">
                                Tanggal Rencana <span class="text-danger">*</span>
                            </label>
                            <div class="cal-icon cal-icon-info">
                                <input type="text" class="form-control" id="add_pp_date"
                                    value="{{ date('d/m/Y') }}" required
                                    style="font-size:13.5px;border-radius:6px;height:38px;">
                            </div>
                        </div>
                        <div class="col-md-7 col-12">
                            <label class="form-label mb-1 text-muted fw-bold text-uppercase" style="font-size:11px;letter-spacing:.3px;" for="add_pp_notes">
                                Catatan (Opsional)
                            </label>
                            <input type="text" class="form-control" id="add_pp_notes"
                                placeholder="Contoh: Lonjakan pesanan, persiapan stok, promo..."
                                style="font-size:13.5px;border-radius:6px;height:38px;">
                        </div>
                    </div>

                    <!-- Divider Section Item -->
                    <div class="d-flex align-items-center justify-content-between mb-2 mt-1 pt-3 border-top">
                        <div class="d-flex align-items-center gap-2">
                            <span class="fw-bold text-dark text-uppercase" style="font-size:11px;letter-spacing:.4px;">
                                <i class="fe fe-layers text-primary me-1"></i> Produk yang Direncanakan
                            </span>
                        </div>
                        <span class="badge bg-light text-secondary border" id="pp-form-item-count">0 item</span>
                    </div>

                    <!-- Input card (tetap di atas) + tabel scroll internal (PG Popup Table) -->
                    <div id="pp-manual-add-wrap" class="pg-popup-table-input">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-5 col-12">
                                <label class="form-label mb-1 text-muted fw-bold text-uppercase" style="font-size:11px;letter-spacing:.3px;" for="add_pp_product">
                                    Produk Jadi <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="add_pp_product" style="height:38px;border-radius:6px;"></select>
                            </div>
                            <div class="col-md-2 col-6">
                                <label class="form-label mb-1 text-muted fw-bold text-uppercase" style="font-size:11px;letter-spacing:.3px;" for="add_pp_qty">
                                    Qty <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control text-center" id="add_pp_qty"
                                    placeholder="0" min="1" value="1"
                                    style="font-size:13.5px;border-radius:6px;height:38px;">
                            </div>
                            <div class="col-md-3 col-6">
                                <label class="form-label mb-1 text-muted fw-bold text-uppercase" style="font-size:11px;letter-spacing:.3px;" for="add_pp_unit">
                                    Satuan <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="add_pp_unit" style="font-size:13.5px;border-radius:6px;height:38px;">
                                    <option value="">Pilih satuan...</option>
                                </select>
                            </div>
                            <div class="col-md-2 col-12">
                                <label class="mb-1">&nbsp;</label>
                                <button type="button" class="btn btn-outline-primary w-100 d-inline-flex align-items-center justify-content-center gap-1 fw-semibold" id="btn-pp-add-item" style="height:38px;border-radius:6px;">
                                    <i class="fe fe-plus"></i> <span>Tambah</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive border rounded-3 pg-popup-table-scroll" id="pp-table-items-scroll">
                        <table class="table table-center table-hover table-sm mb-0" id="tablePpFormItems">
                            <thead>
                                <tr>
                                    <th style="width:40px;" class="text-center">No</th>
                                    <th>Produk / Variasi</th>
                                    <th class="text-center" style="width:110px;">Qty Rencana</th>
                                    <th style="width:120px;">Satuan</th>
                                    <th class="text-center no-sort" style="width:60px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="pp-form-items-body">
                                <tr class="pg-popup-table-empty">
                                    <td colspan="5" class="text-center text-muted py-4">
                                        Belum ada item. Pilih produk di atas lalu klik Tambah.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-end gap-2 pg-modal-footer">
                    <button type="button" class="btn pg-btn-cancel" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn pg-btn-save" id="btn-save-planning">
                        <i class="fe fe-check me-1"></i> Simpan Planning
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal View: draft = hijau (Release), released/lain = biru (detail / WO) --}}
<style>
    #modalViewPlanning .modal-dialog {
        width: calc(100% - 2rem) !important;
        max-width: 96vw !important;
        margin: 1.5rem auto !important;
    }
    @media (min-width: 992px) {
        #modalViewPlanning .modal-dialog {
            width: 95vw !important;
            max-width: 1140px !important;
            --bs-modal-width: 1140px !important;
        }
    }
    @media (min-width: 1400px) {
        #modalViewPlanning .modal-dialog {
            max-width: 1240px !important;
            --bs-modal-width: 1240px !important;
        }
    }
    #modalViewPlanning .modal-body {
        padding: 20px !important;
    }
    #modalViewPlanning .table {
        table-layout: auto !important;
        width: 100% !important;
        margin-bottom: 0 !important;
    }
    #modalViewPlanning .table th {
        font-size: 11px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.3px !important;
        padding: 9px 8px !important;
        white-space: nowrap !important;
        color: #475569 !important;
        background: #f8fafc !important;
    }
    #modalViewPlanning .table td {
        padding: 8px 8px !important;
        vertical-align: middle !important;
        font-size: 12.5px !important;
    }
    #modalViewPlanning .pp-pic-cell {
        background-color: #f8fafc !important;
        border-right: 1px solid #e2e8f0 !important;
    }
    @media (min-width: 992px) {
        #modalViewPlanning .table-responsive {
            overflow-x: visible !important;
        }
    }
    #modalViewPlanning tr.pp-row-stock-short > td {
        background-color: #fef2f2 !important;
    }
    #modalViewPlanning tr.pp-row-stock-short > td:first-child {
        box-shadow: inset 3px 0 0 #ef4444;
    }
    #modalViewPlanning .pp-stock-cell {
        font-size: 12px;
        line-height: 1.35;
        color: #334155;
    }
    #modalViewPlanning .pp-stock-cell.is-short {
        color: #b91c1c;
        font-weight: 600;
    }
    #modalViewPlanning #pp-view-stock-alert .alert {
        margin-bottom: 0;
        font-size: 13px;
        white-space: pre-wrap;
    }
    .pp-wo-chip {
        display: inline-flex;
        flex-direction: column;
        gap: 6px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 7px 13px;
        color: #1e293b;
        min-width: 200px;
        transition: all 0.18s ease;
    }
    .pp-wo-chip--done {
        background: #f0fdf4;
        border-color: #bbf7d0;
    }
    .pp-wo-chip-main {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        color: inherit;
        text-decoration: none;
    }
    .pp-wo-chip:hover {
        background: #eff6ff;
        border-color: #93c5fd;
        color: #1d4ed8;
        box-shadow: 0 2px 8px rgba(37, 99, 235, 0.1);
        transform: translateY(-1px);
    }
    .pp-wo-chip--done:hover {
        background: #ecfdf5;
        border-color: #86efac;
        color: #166534;
    }
    .pp-wo-chip-icon {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        color: #2563eb;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        flex-shrink: 0;
        transition: all 0.18s ease;
    }
    .pp-wo-chip:hover .pp-wo-chip-icon {
        background: #2563eb;
        color: #ffffff;
        border-color: #2563eb;
    }
    .pp-wo-chip-info {
        display: flex;
        flex-direction: column;
        line-height: 1.25;
        text-align: left;
    }
    .pp-wo-chip-name {
        font-weight: 600;
        font-size: 12.5px;
        color: #1e293b;
    }
    .pp-wo-chip:hover .pp-wo-chip-name {
        color: #1d4ed8;
    }
    .pp-wo-chip-code {
        font-size: 11px;
        color: #64748b;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    }
    .pp-wo-chip-action {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 11px;
        font-weight: 600;
        color: #2563eb;
        background: #ffffff;
        border: 1px solid #bfdbfe;
        padding: 2.5px 8px;
        border-radius: 6px;
        margin-left: 4px;
        transition: all 0.18s ease;
    }
    .pp-wo-chip:hover .pp-wo-chip-action {
        background: #2563eb;
        color: #ffffff;
        border-color: #2563eb;
    }
    .pp-wo-chip-status {
        display: flex;
        justify-content: flex-start;
    }

    /* Tombol Cetak WO di Modal Detail Planning */
    #modalViewPlanning .pp-btn-print-wo,
    #modalViewPlanning .btn-outline-primary {
        background-color: #ffffff !important;
        border: 1.5px solid #2563eb !important;
        color: #2563eb !important;
        font-weight: 600 !important;
        border-radius: 6px !important;
        padding: 5px 12px !important;
        font-size: 11.5px !important;
        line-height: 1.2 !important;
        transition: all 0.18s ease-in-out !important;
        box-shadow: 0 1px 2px rgba(37, 99, 235, 0.08) !important;
    }
    #modalViewPlanning .pp-btn-print-wo i,
    #modalViewPlanning .pp-btn-print-wo span,
    #modalViewPlanning .btn-outline-primary i,
    #modalViewPlanning .btn-outline-primary span {
        color: inherit !important;
        transition: color 0.18s ease-in-out !important;
    }
    #modalViewPlanning .pp-btn-print-wo:hover,
    #modalViewPlanning .pp-btn-print-wo:focus,
    #modalViewPlanning .pp-btn-print-wo:active,
    #modalViewPlanning .btn-outline-primary:hover,
    #modalViewPlanning .btn-outline-primary:focus,
    #modalViewPlanning .btn-outline-primary:active {
        background-color: #2563eb !important;
        border-color: #2563eb !important;
        color: #ffffff !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 4px 10px rgba(37, 99, 235, 0.28) !important;
    }
    #modalViewPlanning .pp-btn-print-wo:hover i,
    #modalViewPlanning .pp-btn-print-wo:hover span,
    #modalViewPlanning .btn-outline-primary:hover i,
    #modalViewPlanning .btn-outline-primary:hover span {
        color: #ffffff !important;
    }
</style>
<div class="modal custom-modal fade pg-modal--form" id="modalViewPlanning" tabindex="-1"
    aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content d-flex flex-column">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="pg-modal-icon" id="pp-view-icon-wrap"><i class="fe fe-calendar" id="pp-view-modal-icon"></i></div>
                    <div>
                        <h5 class="mb-0 fw-bold text-white modal-title" id="pp-view-modal-title" style="font-size:16px;">Detail Production Planning</h5>
                        <small class="d-block text-white-50" id="pp-view-subtitle">—</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-white" style="overflow-y:auto;">
                <div class="row g-2 mb-3 small">
                    <div class="col-md-4"><span class="text-muted">No. PP</span><div class="fw-bold" id="pp-view-code">—</div></div>
                    <div class="col-md-4"><span class="text-muted">Tanggal</span><div class="fw-bold" id="pp-view-date">—</div></div>
                    <div class="col-md-4"><span class="text-muted">Status</span><div id="pp-view-status">—</div></div>
                </div>
                <div class="table-responsive border rounded">
                    <table class="table table-hover mb-0 align-middle" id="pp-view-items-table">
                        <thead class="bg-light">
                            <tr id="pp-view-items-head">
                                <th style="width:18%;">PIC / Work Order</th>
                                <th style="width:24%;">Produk</th>
                                <th class="text-end" style="width:6%;">Qty</th>
                                <th style="width:6%;">Satuan</th>
                                <th style="width:12%;">Skala</th>
                                <th style="width:12%;">Armada</th>
                                <th class="text-center" style="width:11%;">Status</th>
                                <th class="text-center no-sort" style="width:11%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="pp-view-items-body"></tbody>
                    </table>
                </div>
                <div class="mt-2" id="pp-view-stock-alert" style="display:none;"></div>
                <div class="mt-3 small text-muted" id="pp-view-notes"></div>
                <div id="pp-view-work-orders" style="display:none !important;">
                    <div id="pp-view-work-orders-list"></div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-end gap-2 pg-modal-footer">
                <button type="button" class="btn pg-btn-cancel" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn pg-btn-confirm" id="btn-pp-view-release" style="display:none;">
                    <i class="fe fe-check me-1"></i> Release to Production
                </button>
                <a class="btn pg-btn-save" id="btn-pp-view-print-spk" href="#" target="_blank" rel="noopener" style="display:none;">
                    <i class="fe fe-printer me-1"></i> Cetak Surat Perintah Kerja
                </a>
                <button type="button" class="btn pg-btn-save" id="btn-pp-view-work-order" style="display:none;">
                    <i class="fe fe-truck me-1"></i> Buat Work Order
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal ACC Draft → Released (Release to Production) --}}
<style>
    #modalApprovePlanning .modal-dialog {
        max-width: 1100px;
        width: calc(100% - 2rem);
        margin: 1.5rem auto;
    }
    #modalApprovePlanning .modal-content {
        border-radius: 16px;
        overflow: hidden;
        border: none;
        box-shadow: 0 20px 45px rgba(0, 0, 0, 0.2);
    }
    #modalApprovePlanning .modal-header {
        background: linear-gradient(135deg, #064e3b 0%, #059669 100%) !important;
        padding: 18px 24px;
        border: 0;
    }
    #modalApprovePlanning .modal-body {
        background: #ffffff;
        padding: 24px !important;
    }
    .pp-approve-table thead th {
        background: #f8fafc !important;
        color: #475569 !important;
        font-size: 11px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.4px !important;
        border-top: none !important;
        border-bottom: 2px solid #e2e8f0 !important;
        padding: 12px 14px !important;
    }
    .pp-approve-table tbody td {
        padding: 12px 14px !important;
        vertical-align: middle !important;
        font-size: 13.5px !important;
        border-bottom: 1px solid #f1f5f9 !important;
        color: #1e293b;
        background: transparent !important;
    }
    .pp-approve-table tbody tr:hover td {
        background-color: #f8fafc !important;
    }
    .pp-approve-table .pp-release-skala {
        height: 38px !important;
        font-size: 13px !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 8px !important;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.02) !important;
    }
    .pp-approve-table .pp-release-skala:focus {
        border-color: #059669 !important;
        box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15) !important;
    }
    #modalApprovePlanning .select2-container {
        width: 100% !important;
    }
    #modalApprovePlanning .select2-container--default .select2-selection--single,
    #modalApprovePlanning .select2-container .select2-selection--single {
        height: 38px !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 8px !important;
        display: flex !important;
        align-items: center !important;
        background-color: #ffffff !important;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.02) !important;
    }
    #modalApprovePlanning .select2-container--default .select2-selection--single .select2-selection__rendered,
    #modalApprovePlanning .select2-container .select2-selection__rendered {
        line-height: 36px !important;
        padding-left: 12px !important;
        padding-right: 28px !important;
        font-size: 13px !important;
        color: #1e293b !important;
    }
    #modalApprovePlanning .select2-container--default .select2-selection--single .select2-selection__arrow,
    #modalApprovePlanning .select2-container .select2-selection__arrow {
        height: 38px !important;
        width: 26px !important;
        top: 0 !important;
        right: 8px !important;
        position: absolute !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        pointer-events: none !important;
    }
    #modalApprovePlanning .select2-container--default .select2-selection--single .select2-selection__arrow b {
        position: static !important;
        display: inline-block !important;
        width: 6px !important;
        height: 6px !important;
        padding: 0 !important;
        margin: 0 !important;
        border: none !important;
        border-right: 1.5px solid #64748b !important;
        border-bottom: 1.5px solid #64748b !important;
        transform: rotate(45deg) !important;
        -webkit-transform: rotate(45deg) !important;
        transition: transform 0.2s ease, border-color 0.2s ease !important;
    }
    #modalApprovePlanning .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
        transform: rotate(-135deg) !important;
        -webkit-transform: rotate(-135deg) !important;
        border-color: #059669 !important;
        margin-top: 2px !important;
    }
    #modalApprovePlanning .select2-dropdown {
        z-index: 1065 !important;
    }
</style>

<div class="modal custom-modal fade pg-modal--confirm" id="modalApprovePlanning" tabindex="-1"
    aria-hidden="true" data-bs-backdrop="static" data-bs-focus="false">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="pg-modal-icon"><i class="fe fe-check-circle"></i></div>
                    <div>
                        <h5 class="mb-0 fw-bold text-white modal-title" style="font-size:16px;">Release to Production</h5>
                        <small class="d-block text-white-50 modal-subtitle">ACC draft → Released to Production</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-white">
                {{-- Info Dokumen & Hint (Tanpa Card Box) --}}
                <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted small text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">No. Perencanaan:</span>
                        <strong class="text-dark fs-6" id="pp-approve-code">—</strong>
                    </div>
                    <div class="text-muted small">
                        <i class="fe fe-info text-success me-1"></i> PIC &amp; Skala wajib (Armada opsional)
                    </div>
                </div>

                {{-- Tabel Item Planning (Tanpa Card Box) --}}
                <div class="table-responsive mb-0">
                    <table class="table table-hover mb-0 align-middle pp-approve-table">
                        <thead>
                            <tr>
                                <th style="width: 28%; min-width: 160px;">Barang</th>
                                <th style="width: 22%; min-width: 140px;">Skala <span class="text-danger">*</span></th>
                                <th style="width: 25%; min-width: 160px;">PIC <span class="text-danger">*</span></th>
                                <th style="width: 25%; min-width: 160px;">Armada</th>
                            </tr>
                        </thead>
                        <tbody id="pp-release-items"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-end gap-2 pg-modal-footer">
                <button type="button" class="btn pg-btn-cancel" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn pg-btn-confirm" id="btn-confirm-approve-planning">
                    <i class="fe fe-check me-1"></i> Release to Production
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Released → Work Order (form: Skala / PIC / Armada) --}}
<div class="modal custom-modal fade pg-modal--form" id="modalWorkOrderPlanning" tabindex="-1"
    aria-hidden="true" data-bs-backdrop="static" data-bs-focus="false">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content d-flex flex-column">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="pg-modal-icon"><i class="fe fe-truck"></i></div>
                    <div>
                        <h5 class="mb-0 fw-bold text-white modal-title" style="font-size:16px;">Work Order</h5>
                        <small class="d-block text-white-50 modal-subtitle">Isi Skala, PIC, Armada — simpan = In Production + cetak WO per PIC</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-white" style="overflow-y:auto;">
                <div class="mb-2 small"><span class="text-muted">No. PP / SPKP:</span> <strong id="pp-wo-code">—</strong></div>
                <div class="table-responsive border rounded">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th style="min-width:160px;">Produk</th>
                                <th class="text-end" style="width:70px;">Qty</th>
                                <th style="width:80px;">Satuan</th>
                                <th style="min-width:160px;">Skala <span class="text-danger">*</span></th>
                                <th style="min-width:160px;">PIC <span class="text-danger">*</span></th>
                                <th style="min-width:160px;">Armada <span class="text-danger">*</span></th>
                            </tr>
                        </thead>
                        <tbody id="pp-wo-items-body"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-end gap-2 pg-modal-footer">
                <button type="button" class="btn pg-btn-cancel" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn pg-btn-save" id="btn-confirm-work-order">
                    <i class="fe fe-check me-1"></i> Simpan Work Order
                </button>
            </div>
        </div>
    </div>
</div>

@include('components.modals.production.wo-execution')
@include('components.modals.production.wo-materials')

@endsection

@section('custom_js')
<script>
    window.ppFullscreen = @json(!empty($ppFullscreen));
    // Halaman aktif (normal ATAU view) — untuk replaceState filter, jangan campur.
    window.ppPageBaseUrl = @json(!empty($ppFullscreen) ? route('productionPlanning.view') : route('productionPlanning'));
    // Hanya untuk buka tab baru fullscreen.
    window.ppViewBaseUrl = @json(route('productionPlanning.view'));
    window.ppMainBaseUrl = @json(route('productionPlanning'));
    window.woEmbed = true;
    window.woMonitor = false;
</script>
<script src="{{ asset('Custom_js/Backoffice/Production/WorkOrders.js') }}?v={{ time() }}"></script>
<script src="{{ asset('Custom_js/Backoffice/Production/Production_Planning.js') }}?v={{ time() }}"></script>
@endsection
