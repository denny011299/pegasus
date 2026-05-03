<style>
    .dash-home {
        --dash-surface: #ffffff;
        --dash-canvas: #f1f5f9;
        --dash-border: rgba(15, 23, 42, 0.08);
        --dash-text: #0f172a;
        --dash-muted: #64748b;
        --dash-accent: #1d4ed8;
        --dash-radius: 12px;
        --dash-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 4px 12px rgba(15, 23, 42, 0.06);

        background: var(--dash-canvas);
        border: 1px solid var(--dash-border);
        border-radius: var(--dash-radius);
        padding: 1.25rem 1.35rem 1.5rem;
        overflow-x: hidden;
    }

    .dash-toolbar {
        background: var(--dash-surface);
        border: 1px solid var(--dash-border);
        border-radius: var(--dash-radius);
        box-shadow: var(--dash-shadow);
        padding: 0.9rem 1.1rem;
        margin-bottom: 1.35rem;
    }

    .dash-toolbar-period {
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--dash-text);
        letter-spacing: 0.01em;
    }

    .dash-toolbar-hint {
        font-size: 0.75rem;
        color: var(--dash-muted);
        line-height: 1.45;
        max-width: 920px;
    }

    .dash-section {
        margin-bottom: 1.35rem;
    }

    .dash-section-head {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid var(--dash-border);
    }

    .dash-section-head::before {
        content: "";
        width: 4px;
        height: 1.1rem;
        border-radius: 2px;
        background: var(--dash-accent);
        flex-shrink: 0;
    }

    .dash-section-title {
        margin: 0;
        font-size: 0.9375rem;
        font-weight: 700;
        color: var(--dash-text);
        letter-spacing: -0.01em;
    }

    .dash-card {
        background: var(--dash-surface);
        border: 1px solid var(--dash-border);
        border-radius: var(--dash-radius);
        box-shadow: var(--dash-shadow);
        padding: 1.1rem 1.15rem;
        min-width: 0;
        overflow: hidden;
    }

    .dash-card.dash-card-fill {
        height: 100%;
    }

    .dash-card-title {
        font-size: 0.8125rem;
        font-weight: 700;
        color: var(--dash-text);
        margin: 0 0 0.65rem 0;
        letter-spacing: -0.01em;
    }

    .dash-card-title-sub {
        font-size: 0.8125rem;
        font-weight: 700;
        color: var(--dash-text);
        margin: 0 0 0.35rem 0;
    }

    .dash-kpi-title {
        font-size: 0.6875rem;
        color: var(--dash-muted);
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.06em;
        line-height: 1.35;
        margin-bottom: 0.35rem;
    }

    .dash-kpi-title .dash-info-tip {
        margin-left: 0.25rem;
        color: #94a3b8;
        cursor: help;
        font-size: 0.75rem;
        vertical-align: baseline;
    }

    .dash-kpi-title .dash-info-tip:hover {
        color: #1d4ed8;
    }

    .dash-kpi-value {
        font-size: 1.375rem;
        font-weight: 700;
        color: #1e40af;
        line-height: 1.2;
        letter-spacing: -0.02em;
        overflow-wrap: anywhere;
    }

    .dash-kpi-sub {
        font-size: 0.75rem;
        color: var(--dash-muted);
        line-height: 1.4;
        overflow-wrap: anywhere;
        margin-top: 0.35rem;
    }

    /* Satu zona scroll saja (bukan table-responsive + dash-scroll — itu bikin scroll-x “nyangkut”).
       Lebar mengikuti isi tabel agar scroll horizontal benar. */
    .dash-table-wrap {
        width: max-content;
        min-width: 100%;
        box-sizing: border-box;
        border-radius: 8px;
        border: 1px solid var(--dash-border);
        overflow: visible;
    }

    .dash-scroll {
        max-height: 245px;
        overflow-x: auto;
        overflow-y: auto;
        overscroll-behavior: contain;
        -webkit-overflow-scrolling: touch;
        scrollbar-gutter: stable;
    }

    .dash-scroll-tall {
        max-height: 340px;
        overflow-x: auto;
        overflow-y: auto;
        overscroll-behavior: contain;
        -webkit-overflow-scrolling: touch;
        scrollbar-gutter: stable;
    }

    .dash-table {
        table-layout: auto;
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-bottom: 0;
    }

    .dash-table thead th {
        background: #f8fafc !important;
        font-size: 0.6875rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #475569 !important;
        border-bottom: 1px solid var(--dash-border) !important;
        padding: 0.55rem 0.65rem !important;
    }

    .dash-table tbody td {
        font-size: 0.8125rem;
        color: #334155;
        padding: 0.55rem 0.65rem !important;
        border-bottom: 1px solid rgba(15, 23, 42, 0.06);
        vertical-align: middle;
    }

    .dash-table tbody tr:last-child td {
        border-bottom: none;
    }

    .dash-table-hover tbody tr:hover td {
        background-color: #f8fafc;
    }

    .dash-table td,
    .dash-table th {
        vertical-align: middle;
    }

    /* Log: min-width moderat — scroll hanya saat kolom benar-benar sempit */
    .dash-approval-table {
        min-width: 520px;
    }

    .dash-approval-table thead th {
        white-space: nowrap;
    }

    .dash-approval-table td {
        word-break: break-word;
        overflow-wrap: anywhere;
    }

    .dash-approval-table td.dash-col-actions {
        width: 1%;
        min-width: 7.25rem;
        white-space: nowrap;
        vertical-align: middle;
        text-align: right;
    }

    .dash-approval-table td.dash-col-actions .btn {
        display: inline-block;
        max-width: 100%;
        white-space: normal;
        text-align: center;
        line-height: 1.2;
    }

    .dash-freeze-head thead th {
        position: sticky;
        top: 0;
        z-index: 5;
        background: #f8fafc !important;
    }

    .dash-payable-customer {
        max-width: 300px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .dash-payable-amount {
        font-weight: 600;
    }

    .dash-log-btn {
        border-radius: 8px;
        font-size: 0.72rem;
        font-weight: 600;
        padding: 0.28rem 0.62rem;
        border: 1px solid #cbd5e1;
        color: #1e3a8a;
        background: #f8fafc;
    }

    .dash-log-btn:hover {
        background: #e2e8f0;
        border-color: #94a3b8;
        color: #1e3a8a;
    }

    /* Top 5: nama produk boleh wrap; qty tetap rapat kanan */
    .dash-top5-table {
        min-width: 280px;
    }

    .dash-top5-table th:first-child,
    .dash-top5-table td.dash-col-rank {
        width: 2.25rem;
        white-space: nowrap;
        vertical-align: top;
    }

    .dash-top5-table td.dash-col-prod {
        word-break: break-word;
        overflow-wrap: anywhere;
        hyphens: auto;
    }

    .dash-top5-table th:last-child,
    .dash-top5-table td.dash-col-qty {
        width: 1%;
        white-space: nowrap;
        vertical-align: top;
        text-align: right;
    }

    .dash-stock-aging-table td:nth-child(2) {
        word-break: break-word;
        overflow-wrap: anywhere;
    }

    .dash-stock-aging-table th:last-child,
    .dash-stock-aging-table td.dash-aging-actions {
        width: 1%;
        white-space: nowrap;
        vertical-align: middle;
        text-align: center;
    }

    .dash-bahan-alert-table tr.dash-bahan-row-critical td {
        background-color: #fef2f2 !important;
        box-shadow: inset 3px 0 0 #dc2626;
    }

    .dash-bahan-alert-table tr.dash-bahan-row-warn td {
        background-color: #fffbeb !important;
        box-shadow: inset 3px 0 0 #d97706;
    }

    .dash-bahan-alert-table td {
        vertical-align: middle;
    }

    .dash-bahan-toolbar {
        border-bottom: 1px solid var(--dash-border);
        padding-bottom: 0.85rem;
        margin-bottom: 0.85rem;
    }

    .dash-bahan-legend {
        font-size: 0.75rem;
        color: var(--dash-muted);
        line-height: 1.45;
        margin-top: 0.45rem;
        max-width: 48rem;
    }

    .dash-badge-pill {
        font-size: 0.6875rem;
        font-weight: 600;
        padding: 0.28em 0.65em;
        vertical-align: middle;
    }

    .dash-bahan-filter-btn {
        line-height: 1.25;
        transition: box-shadow 0.15s ease, transform 0.12s ease;
    }

    .dash-bahan-filter-btn:hover:not(:disabled) {
        filter: brightness(1.05);
    }

    .dash-bahan-filter-btn:focus {
        box-shadow: 0 0 0 0.2rem rgba(29, 78, 216, 0.25);
    }

    .dash-bahan-filter-btn.dash-bahan-badge-active {
        box-shadow: 0 0 0 2px #fff, 0 0 0 4px #1d4ed8;
        transform: scale(1.02);
    }

    .dash-bahan-filter-btn.btn-warning.dash-bahan-badge-active {
        box-shadow: 0 0 0 2px #fff, 0 0 0 4px #d97706;
    }

    .dash-bahan-filter-hint {
        font-size: 0.6875rem;
        color: var(--dash-muted);
        margin-top: 0.35rem;
    }

    .dash-chart-caption {
        font-size: 0.75rem;
        color: var(--dash-muted);
        line-height: 1.45;
    }

    .dash-prose-note {
        font-size: 0.75rem;
        color: var(--dash-muted);
        line-height: 1.55;
        max-width: 960px;
    }

    .dash-modal-table {
        border-collapse: separate;
        border-spacing: 0;
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 10px;
        overflow: hidden;
        background: #fff;
    }

    .dash-modal-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: #f8fafc;
        color: #475569;
        font-size: 0.6875rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 700;
        border-bottom: 1px solid rgba(15, 23, 42, 0.1);
        padding: 0.62rem 0.72rem;
        white-space: nowrap;
    }

    .dash-modal-table tbody td {
        padding: 0.56rem 0.72rem;
        border-bottom: 1px solid rgba(15, 23, 42, 0.06);
        color: #1e293b;
        vertical-align: middle;
    }

    .dash-modal-table tbody tr:nth-child(even) td {
        background: #fcfdff;
    }

    .dash-modal-table tbody tr:hover td {
        background: #eef6ff;
    }

    .dash-modal-table tbody tr:last-child td {
        border-bottom: none;
    }

    .dash-aging-item-cell {
        max-width: 25rem;
        overflow-wrap: anywhere;
        word-break: break-word;
        font-weight: 500;
    }

    .dash-aging-qty-cell,
    .dash-aging-value-cell,
    .dash-aging-age-cell {
        font-variant-numeric: tabular-nums;
    }

    .dash-kind-badge {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 0.18rem 0.58rem;
        font-size: 0.6875rem;
        font-weight: 700;
        letter-spacing: 0.01em;
        line-height: 1.35;
        border: 1px solid transparent;
        white-space: nowrap;
    }

    .dash-kind-badge-bahan {
        color: #92400e;
        background: #fffbeb;
        border-color: #fcd34d;
    }

    .dash-kind-badge-product {
        color: #1e3a8a;
        background: #eff6ff;
        border-color: #bfdbfe;
    }

    .dash-kind-badge-other {
        color: #334155;
        background: #f1f5f9;
        border-color: #cbd5e1;
    }

    #dash_overstock_list {
        margin-bottom: 0;
        max-height: 220px;
        overflow: auto;
    }

    #dash_overstock_list li {
        padding: 0.5rem 0;
        line-height: 1.45;
        overflow-wrap: anywhere;
        word-break: break-word;
        border-bottom: 1px solid rgba(15, 23, 42, 0.06);
        color: #334155;
    }

    #dash_overstock_list li:last-child {
        border-bottom: none;
    }

    .dash-reco-table {
        min-width: 260px;
    }

    .dash-reco-table th:first-child,
    .dash-reco-table td.dash-col-rank {
        width: 2.25rem;
        white-space: nowrap;
        vertical-align: top;
    }

    .dash-reco-table td.dash-col-prod {
        word-break: break-word;
        overflow-wrap: anywhere;
    }

    .dash-reco-table th:last-child,
    .dash-reco-table td.dash-col-qty {
        width: 1%;
        white-space: nowrap;
        vertical-align: top;
        text-align: right;
    }

    #dash_main_chart {
        min-height: 300px;
        overflow: hidden;
    }

    .dash-muted-note {
        color: var(--dash-muted);
        font-size: 0.75rem;
        line-height: 1.45;
    }

    #dashBahanToast {
        border: 1px solid var(--dash-border) !important;
        box-shadow: var(--dash-shadow) !important;
    }

    #dashBahanToast .toast-body {
        color: var(--dash-text);
    }

    @media (max-width: 991.98px) {
        .dash-home {
            padding: 1rem;
        }

        .dash-kpi-value {
            font-size: 1.15rem;
        }

        #dash_filter_label {
            text-align: left !important;
            margin-top: 0.35rem;
        }

        .dash-scroll {
            max-height: none;
        }

        .dash-toolbar-period {
            display: block;
            margin-top: 0.35rem;
        }
    }
</style>

<div class="dash-home">
    <div class="dash-toolbar">
        <div class="row g-2 align-items-center">
            <div class="col-sm-auto">
                <label class="form-label small text-muted mb-0 d-block">Periode</label>
                <select id="dash_filter_period" class="form-select form-select-sm" style="min-width: 9.5rem;">
                    <option value="week">Minggu</option>
                    <option value="month">Bulan</option>
                    <option value="year" selected>Tahun</option>
                </select>
            </div>
            <div class="col-sm-auto align-self-end">
                <button type="button" class="btn btn-primary btn-sm px-3" id="dash_refresh_btn">
                    <i class="fe fe-refresh-cw me-1"></i>Terapkan
                </button>
            </div>
            <div class="col text-md-end align-self-end pt-2 pt-sm-0">
                <span class="dash-toolbar-period" id="dash_filter_label">—</span>
            </div>
        </div>
        {{-- <p class="dash-toolbar-hint mb-0 mt-2 pt-2 border-top" id="dash_filter_hint" style="border-color: rgba(15,23,42,.08) !important;">Memuat penjelasan filter…</p> --}}
    </div>

    <div class="dash-section" data-dash-widget="kpi_ringkasan">
        <div class="dash-section-head">
            <h2 class="dash-section-title">Ringkasan changelog &amp; KPI</h2>
        </div>
    </div>
    <div class="row g-3 mb-3" data-dash-widget="kpi_ringkasan">
        <div class="col-lg-3 col-md-6">
            <div class="dash-card dash-card-fill">
                <div class="dash-kpi-title">Changelog
                    <i class="fe fe-info dash-info-tip" data-bs-toggle="tooltip" data-bs-placement="top" title="Jumlah permintaan perubahan (retur/penyesuaian) yang masih menunggu ACC Direktur sesuai periode."></i>
                </div>
                <div class="dash-kpi-value" id="kpi_changelog">0</div>
                <div class="dash-kpi-sub">Retur &amp; penyesuaian kas · tunggu ACC</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="dash-card dash-card-fill">
                <div class="dash-kpi-title">Confirmation Log
                    <i class="fe fe-info dash-info-tip" data-bs-toggle="tooltip" data-bs-placement="top" title="Jumlah transaksi baru (SO/PO/Produksi) yang menunggu konfirmasi/ACC pada periode aktif."></i>
                </div>
                <div class="dash-kpi-value" id="kpi_confirmation">0</div>
                <div class="dash-kpi-sub">SO / PO / Produksi menunggu ACC</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="dash-card dash-card-fill">
                <div class="dash-kpi-title">Revision Log
                    <i class="fe fe-info dash-info-tip" data-bs-toggle="tooltip" data-bs-placement="top" title="Jumlah transaksi berstatus ditolak (perlu perbaikan/input ulang) pada periode aktif."></i>
                </div>
                <div class="dash-kpi-value text-danger" id="kpi_revision">0</div>
                <div class="dash-kpi-sub">Ditolak · perlu perbaikan</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="dash-card dash-card-fill">
                <div class="dash-kpi-title">Inventory Value
                    <i class="fe fe-info dash-info-tip" data-bs-toggle="tooltip" data-bs-placement="top" title="Rumus: Σ (Qty stok × harga/unit). Ditampilkan sebagai total nilai stok Produk + Bahan."></i>
                </div>
                <div class="dash-kpi-value" id="kpi_inventory_value">Rp 0</div>
                <div class="dash-kpi-sub" id="kpi_inventory_split">Produk Rp 0 · Bahan Rp 0</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-5" data-dash-widget="kpi_ringkasan">
        <div class="col-lg-3 col-md-6">
            <div class="dash-card dash-card-fill">
                <div class="dash-kpi-title">Sales Growth %
                    <i class="fe fe-info dash-info-tip" data-bs-toggle="tooltip" data-bs-placement="top" title="Rumus: ((Qty periode ini - Qty periode sebelumnya) / Qty periode sebelumnya) × 100%."></i>
                </div>
                <div class="dash-kpi-value" id="kpi_sales_growth">0%</div>
                <div class="dash-kpi-sub" id="kpi_sales_growth_sub">Output pengiriman vs periode sebelumnya</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="dash-card dash-card-fill">
                <div class="dash-kpi-title">Inventory Turnover
                    <i class="fe fe-info dash-info-tip" data-bs-toggle="tooltip" data-bs-placement="top" title="Rumus dashboard: (Qty keluar / stok saat ini), lalu diannualisasi: × (365 / jumlah hari periode)."></i>
                </div>
                <div class="dash-kpi-value" id="kpi_turnover">0</div>
                <div class="dash-kpi-sub" id="kpi_turnover_sub">Keluar stok vs stok sekarang, annualized · sesuai filter</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="dash-card dash-card-fill">
                <div class="dash-kpi-title">Days Inventory Outstanding
                    <i class="fe fe-info dash-info-tip" data-bs-toggle="tooltip" data-bs-placement="top" title="Rumus: DIO = 365 / Inventory Turnover (annualized). Makin kecil umumnya makin cepat perputaran stok."></i>
                </div>
                <div class="dash-kpi-value" id="kpi_dio">0 hari</div>
                <div class="dash-kpi-sub" id="kpi_dio_sub">Dari turnover annualized<br>sesuai filter</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="dash-card dash-card-fill">
                <div class="dash-kpi-title">Return Rate %
                    <i class="fe fe-info dash-info-tip" data-bs-toggle="tooltip" data-bs-placement="top" title="Rumus: (Qty retur / Qty transaksi terkait) × 100%. Ditampilkan terpisah untuk Produk dan Bahan."></i>
                </div>
                <div class="dash-kpi-value" id="kpi_return_rate">0%</div>
                <div class="dash-kpi-sub" id="kpi_return_split">Produk: 0% · Bahan: 0%<br>sesuai filter</div>
            </div>
        </div>
    </div>

    <div class="dash-section mt-3" data-dash-widget="approval_logs">
        <div class="dash-section-head">
            <h2 class="dash-section-title">Changelog &amp; log persetujuan</h2>
        </div>
        {{-- <p class="dash-prose-note">
            <strong>Changelog</strong>: <em>Retur Produk</em> &amp; <em>Kas Sales</em> menunggu ACC.
            <strong>Confirmation</strong>: SO / PO / Produksi perlu konfirmasi.
            <strong>Revision</strong>: transaksi ditolak & perlu diperbaiki.
        </p> --}}
    </div>
    <div class="row g-3 mb-5" data-dash-widget="approval_logs">
        <div class="col-12">
            <div class="dash-card dash-card-fill">
                <h3 class="dash-card-title">Changelog</h3>
                <div class="dash-scroll">
                    <div class="dash-table-wrap">
                    <table class="table table-sm dash-table dash-table-hover dash-approval-table mb-0">
                        <thead>
                            <tr>
                                <th class="text-nowrap">Modul</th>
                                <th class="text-nowrap">Ref</th>
                                <th>Perubahan / status</th>
                                <th class="text-end text-nowrap">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="dash_changelog_body">
                            <tr><td colspan="4" class="text-center text-muted">Memuat…</td></tr>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="dash-card dash-card-fill">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                    <h3 class="dash-card-title mb-0">Confirmation log</h3>
                    <select id="dash_confirmation_module_filter" class="form-select form-select-sm" style="min-width: 180px; max-width: 280px;">
                        <option value="all">Semua modul</option>
                    </select>
                </div>
                <div class="dash-scroll">
                    <div class="dash-table-wrap">
                    <table class="table table-sm dash-table dash-table-hover dash-approval-table mb-0">
                        <thead>
                            <tr>
                                <th class="text-nowrap">Modul</th>
                                <th class="text-nowrap">Ref</th>
                                <th>Perlu ACC</th>
                                <th class="text-end text-nowrap">Buka</th>
                            </tr>
                        </thead>
                        <tbody id="dash_confirmation_body">
                            <tr><td colspan="4" class="text-center text-muted">Memuat…</td></tr>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="dash-card dash-card-fill">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                    <h3 class="dash-card-title mb-0">Revision log</h3>
                    <select id="dash_revision_module_filter" class="form-select form-select-sm" style="min-width: 180px; max-width: 280px;">
                        <option value="all">Semua modul</option>
                    </select>
                </div>
                <div class="dash-scroll">
                    <div class="dash-table-wrap">
                    <table class="table table-sm dash-table dash-table-hover dash-approval-table mb-0">
                        <thead>
                            <tr>
                                <th class="text-nowrap">Modul</th>
                                <th class="text-nowrap">Ref</th>
                                <th>Alasan</th>
                                <th class="text-end text-nowrap">Buka</th>
                            </tr>
                        </thead>
                        <tbody id="dash_revision_body">
                            <tr><td colspan="4" class="text-center text-muted">Memuat…</td></tr>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12" data-dash-widget="jatuh_tempo_hutang">
            <div class="dash-card dash-card-fill">
                <h3 class="dash-card-title mb-2">Jatuh tempo hutang customer</h3>
                {{-- <p class="dash-muted-note mb-2">Menampilkan invoice belum dibayar dengan status: Akan jatuh tempo dalam 1-2 hari, Hari ini, atau sudah lewat jatuh tempo.</p> --}}
                <div class="dash-scroll">
                    <div class="dash-table-wrap">
                    <table class="table table-sm dash-table dash-table-hover dash-approval-table dash-freeze-head mb-0">
                        <thead>
                            <tr>
                                <th class="text-nowrap">Jatuh Tempo</th>
                                <th class="text-nowrap">Invoice</th>
                                <th>Customer</th>
                                <th class="text-end text-nowrap">Total Hutang</th>
                                <th class="text-end text-nowrap">Buka</th>
                            </tr>
                        </thead>
                        <tbody id="dash_payables_due_body">
                            <tr><td colspan="5" class="text-center text-muted">Memuat…</td></tr>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="dash-section" data-dash-widget="delivery_chart">
        <div class="dash-section-head">
            <h2 class="dash-section-title">Grafik &amp; top produk pengiriman</h2>
        </div>
    </div>

    <div class="row g-3 mb-3" data-dash-widget="delivery_chart">
        <div class="col-12">
            <div class="dash-card dash-card-fill">
                <h3 class="dash-card-title-sub">Output pengiriman
                    <i class="fe fe-info dash-info-tip" data-bs-toggle="tooltip" data-bs-placement="top" title="Pengiriman (qty): total qty terkirim per bucket waktu. Retur armada (qty): total qty retur per bucket."></i>
                </h3>
                <p class="dash-chart-caption mb-3" id="dash_chart_caption">Ringkasan bulanan: batang menunjukkan total qty pengiriman &amp; retur.</p>
                <div id="dash_main_chart"></div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-5" data-dash-widget="delivery_chart">
        <div class="col-lg-6">
            <div class="dash-card mb-3">
                <h3 class="dash-card-title-sub" id="dash_top_yearly_title">Top 5 pengiriman tahunan</h3>
                <p class="dash-muted-note mb-2" id="dash_top_yearly_sub">—</p>
                <div class="dash-scroll">
                    <div class="dash-table-wrap">
                    <table class="table table-sm dash-table dash-table-hover dash-top5-table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Produk</th>
                                <th class="text-end">Qty</th>
                            </tr>
                        </thead>
                        <tbody id="dash_top_yearly">
                            <tr>
                                <td colspan="3" class="text-center text-muted">Memuat...</td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="dash-card">
                <h3 class="dash-card-title-sub" id="dash_top_accum_title">Top 5 pengiriman bulan ini</h3>
                <p class="dash-muted-note mb-2" id="dash_top_accum_sub">—</p>
                <div class="dash-scroll">
                    <div class="dash-table-wrap">
                    <table class="table table-sm dash-table dash-table-hover dash-top5-table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Produk</th>
                                <th class="text-end">Qty</th>
                            </tr>
                        </thead>
                        <tbody id="dash_top_accum">
                            <tr>
                                <td colspan="3" class="text-center text-muted">Memuat...</td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="dash-section mt-3" data-dash-widget="stock_aging">
        <div class="dash-section-head">
            <h2 class="dash-section-title">Stock aging &amp; peringatan</h2>
        </div>
    </div>
    <div class="row g-3" data-dash-widget="stock_aging">
        <div class="col-12">
            <div class="dash-card dash-card-fill">
                <h3 class="dash-card-title">Stock aging (FIFO)</h3>
                {{-- <p class="dash-muted-note mb-3">Umur lapisan stok belum keluar (sampai akhir periode filter). Gunakan <strong>Lihat</strong> untuk rincian barang jadi &amp; bahan per kelompok.</p> --}}
                <div class="dash-scroll">
                    <div class="dash-table-wrap">
                    <table class="table table-sm dash-table dash-table-hover dash-stock-aging-table mb-0">
                        <thead>
                            <tr>
                                <th class="text-nowrap">Umur Stok</th>
                                <th>Status</th>
                                <th class="text-end text-nowrap">Qty</th>
                                <th class="text-end text-nowrap">Nilai</th>
                                <th class="text-nowrap">Rincian</th>
                            </tr>
                        </thead>
                        <tbody id="dash_stock_aging_body">
                            <tr>
                                <td colspan="5" class="text-center text-muted">Memuat...</td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-3" data-dash-widget="stock_alert_bahan">
        <div class="col-12">
            <div class="dash-card dash-card-fill">
                <div class="dash-bahan-toolbar d-flex flex-wrap align-items-start justify-content-between gap-3">
                    <div class="flex-grow-1" style="min-width: 220px;">
                        <h3 class="dash-card-title mb-2">Stock alert — bahan mentah</h3>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <button type="button" class="btn btn-sm rounded-pill bg-danger text-white border-0 dash-badge-pill dash-bahan-filter-btn" id="dash_bahan_badge_crit" data-bahan-filter="critical" title="Tampilkan hanya bahan habis stok">
                                0 habis
                            </button>
                            <button type="button" class="btn btn-sm rounded-pill bg-warning text-dark border-0 dash-badge-pill dash-bahan-filter-btn" id="dash_bahan_badge_warn" data-bahan-filter="warn" title="Tampilkan hanya bahan mendekati batas">
                                0 mendekati batas
                            </button>
                        </div>
                        <p class="dash-bahan-filter-hint mb-0" id="dash_bahan_filter_hint"></p>
                        {{-- <p class="dash-bahan-legend mb-0 mt-1">
                            <strong class="text-dark">Habis</strong>: semua satuan 0.
                            <strong class="text-dark">Perlu order</strong>: stok satuan default ≤ batas alert. Barang jadi tidak ditampilkan.
                        </p> --}}
                    </div>  
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="dash_bahan_notif_perm" title="Izinkan notifikasi browser">
                            <i class="fe fe-bell"></i><span class="d-none d-sm-inline ms-1">Notifikasi</span>
                        </button>
                        <a class="btn btn-primary btn-sm" id="dash_bahan_link_alert" href="{{ url('stockAlertSupplies') }}">Halaman peringatan</a>
                        <a class="btn btn-outline-primary btn-sm" id="dash_bahan_link_po" href="{{ url('purchaseOrder') }}">Purchase order</a>
                    </div>
                </div>
                <div class="dash-scroll-tall">
                    <div class="dash-table-wrap">
                    <table class="table table-sm dash-table dash-table-hover mb-0 dash-bahan-alert-table">
                        <thead>
                            <tr>
                                <th class="text-nowrap">Status</th>
                                <th>Bahan</th>
                                <th>Stok</th>
                                <th class="text-end text-nowrap">Batas min.</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody id="dash_bahan_alert_body">
                            <tr>
                                <td colspan="5" class="text-center text-muted">Memuat…</td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-3" data-dash-widget="overstock_rekomendasi">
        <div class="col-lg-6">
            <div class="dash-card dash-card-fill">
                <h3 class="dash-card-title">Overstock (aging &gt; 90 hari)</h3>
                <ul class="mb-0 small list-unstyled" id="dash_overstock_list" style="font-size: 0.8125rem;">
                    <li class="text-muted">Memuat...</li>
                </ul>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="dash-card dash-card-fill">
                <h3 class="dash-card-title">Rekomendasi stok produksi</h3>
                <p class="dash-muted-note mb-2" id="dash_recommended_note"></p>
                <div class="dash-scroll">
                    <div class="dash-table-wrap">
                    <table class="table table-sm dash-table dash-table-hover dash-reco-table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Produk</th>
                                <th class="text-end text-nowrap">Rekomendasi</th>
                            </tr>
                        </thead>
                        <tbody id="dash_recommended_body">
                            <tr>
                                <td colspan="3" class="text-center text-muted">Memuat…</td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 10800">
        <div id="dashBahanToast" class="toast align-items-center bg-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body small py-3" id="dashBahanToastBody"></div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Tutup"></button>
            </div>
        </div>
    </div>

    <div class="modal fade" id="dashAgingDetailModal" tabindex="-1" aria-labelledby="dashAgingDetailTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 shadow" style="border-radius: 12px;">
                <div class="modal-header border-bottom py-3 px-3">
                    <h5 class="modal-title fw-semibold" style="font-size: 1rem;" id="dashAgingDetailTitle">Detail stok</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-2 align-items-end mb-2">
                        <div class="col-md-4">
                            <label class="form-label small text-muted mb-1" for="dash_aging_kind_filter">Jenis</label>
                            <select id="dash_aging_kind_filter" class="form-select form-select-sm">
                                <option value="all">All</option>
                                <option value="bahan">Bahan</option>
                                <option value="product">Product</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small text-muted mb-1" for="dash_aging_name_filter">Cari nama item (like)</label>
                            <input type="text" id="dash_aging_name_filter" class="form-control form-control-sm" placeholder="Ketik nama item...">
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2 my-3">
                        <small class="text-muted">Filter berlaku untuk data di bucket yang sedang dibuka.</small>
                        <small class="text-muted" id="dash_aging_detail_count">0 item</small>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0 dash-modal-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Jenis</th>
                                    <th>Item</th>
                                    <th class="text-end text-nowrap">Qty</th>
                                    <th class="text-end text-nowrap">Nilai</th>
                                    <th class="text-end text-nowrap">Umur (hari)</th>
                                </tr>
                            </thead>
                            <tbody id="dash_aging_detail_body"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
