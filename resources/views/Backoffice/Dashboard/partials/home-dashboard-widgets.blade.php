@php
    $akses = $aksesHome ?? collect();
    $showBahan = (bool) $akses->firstWhere('name', 'Pengelolaan Bahan Mentah');
@endphp
<style>
    :root {
        --dash-primary: #2d60ff;
        --dash-primary-soft: #e8efff;
        --dash-accent: #0ea5e9;
        --dash-bg: #f0f4fc;
        --dash-card: #ffffff;
        --dash-text: #1e293b;
        --dash-muted: #64748b;
        --dash-success: #10b981;
        --dash-radius: 20px;
    }

    .dash-pemakaian-page.dash-home-embed {
        background: var(--dash-bg);
        border-radius: var(--dash-radius);
        padding: 1.25rem 1.35rem;
        margin-bottom: 0.5rem;
        border: 1px solid rgba(45, 96, 255, 0.08);
    }

    .dash-pemakaian-page .dash-toolbar {
        background: var(--dash-card);
        border-radius: var(--dash-radius);
        box-shadow: 0 8px 24px rgba(45, 96, 255, 0.08);
        padding: 1rem 1.25rem;
        margin-bottom: 1.25rem;
    }

    .dash-pemakaian-page .dash-toolbar .form-select,
    .dash-pemakaian-page .dash-toolbar .btn {
        border-radius: 12px;
    }

    .dash-pemakaian-page .btn-dash-primary {
        background: var(--dash-primary);
        border: none;
        color: #fff;
        font-weight: 600;
        padding: 0.5rem 1.25rem;
    }

    .dash-pemakaian-page .btn-dash-primary:hover {
        background: #244bcc;
        color: #fff;
    }

    .dash-pemakaian-page .dash-kpi {
        background: var(--dash-card);
        border-radius: var(--dash-radius);
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
        padding: 1.15rem 1.25rem;
        height: 100%;
        border: 1px solid rgba(45, 96, 255, 0.06);
    }

    .dash-pemakaian-page .dash-kpi h6 {
        color: var(--dash-muted);
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 0.35rem;
    }

    .dash-pemakaian-page .dash-kpi .value {
        font-size: 1.65rem;
        font-weight: 700;
        color: var(--dash-primary);
        line-height: 1.2;
    }

    .dash-pemakaian-page .dash-kpi .sub {
        font-size: 0.85rem;
        color: var(--dash-muted);
    }

    .dash-pemakaian-page .dash-kpi .trend-up {
        color: var(--dash-success);
        font-weight: 600;
    }

    .dash-pemakaian-page .dash-kpi .trend-down {
        color: #ef4444;
        font-weight: 600;
    }

    .dash-pemakaian-page .dash-card {
        background: var(--dash-card);
        border-radius: var(--dash-radius);
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
        padding: 1.25rem 1.35rem;
        border: 1px solid rgba(45, 96, 255, 0.06);
    }

    .dash-pemakaian-page .dash-card h5 {
        color: var(--dash-text);
        font-weight: 700;
        font-size: 1.05rem;
    }

    .dash-pemakaian-page #chartPemakaianBahan {
        min-height: 340px;
    }

    .dash-pemakaian-page .dash-exec-chart {
        min-height: 300px;
        width: 100%;
    }

    .dash-pemakaian-page .dash-exec-chart .apexcharts-canvas,
    .dash-pemakaian-page .dash-exec-chart .apexcharts-svg {
        overflow: visible;
    }

    .dash-pemakaian-page .dash-disclaimer {
        font-size: 0.8rem;
        color: var(--dash-muted);
        border-left: 3px solid var(--dash-accent);
        padding-left: 0.75rem;
        margin-top: 0.75rem;
    }

    .dash-pemakaian-page .table-top-materials thead th {
        background: var(--dash-primary-soft) !important;
        color: var(--dash-text);
        font-weight: 600;
        font-size: 0.8rem;
    }

    .dash-pemakaian-page .dash-top-sales-scroll {
        max-height: 22rem;
        overflow-y: auto;
    }

    .dash-pemakaian-page .dash-top-sales-scroll thead th {
        position: sticky;
        top: 0;
        z-index: 1;
        box-shadow: 0 1px 0 rgba(15, 23, 42, 0.08);
    }

    .dash-pemakaian-page .badge-dash {
        background: var(--dash-primary-soft);
        color: var(--dash-primary);
        font-weight: 600;
        border-radius: 999px;
        padding: 0.35rem 0.65rem;
    }

    .dash-pemakaian-page .dash-cross-card {
        background: var(--dash-card);
        border-radius: var(--dash-radius);
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
        padding: 1.15rem 1.2rem;
        height: 100%;
        border: 1px solid rgba(45, 96, 255, 0.08);
        display: flex;
        flex-direction: column;
        gap: 0.65rem;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .dash-pemakaian-page .dash-cross-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 28px rgba(45, 96, 255, 0.12);
    }

    .dash-pemakaian-page .dash-cross-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem;
    }

    .dash-pemakaian-page .dash-cross-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #2d60ff 0%, #5b8cff 100%);
        color: #fff;
        font-size: 1.25rem;
        flex-shrink: 0;
    }

    .dash-pemakaian-page .dash-cross-title {
        font-weight: 700;
        font-size: 1.05rem;
        color: var(--dash-text);
        margin: 0;
    }

    .dash-pemakaian-page .dash-cross-sub {
        font-size: 0.78rem;
        color: var(--dash-muted);
        margin: 0;
    }

    .dash-pemakaian-page .dash-cross-value {
        font-size: 1.85rem;
        font-weight: 800;
        color: var(--dash-primary);
        line-height: 1.1;
    }

    .dash-pemakaian-page .dash-cross-secondary {
        font-size: 0.88rem;
        color: var(--dash-muted);
        line-height: 1.35;
    }

    .dash-pemakaian-page .dash-cross-foot {
        margin-top: auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.35rem;
    }

    .dash-pemakaian-page .dash-cross-link {
        font-size: 0.82rem;
        font-weight: 600;
        color: var(--dash-primary);
        text-decoration: none;
    }

    .dash-pemakaian-page .dash-cross-link:hover {
        text-decoration: underline;
    }
</style>
<div class="dash-pemakaian-page dash-home-embed">
    <div id="dash_cross_widgets" class="row g-3 mb-1"></div>

    <div class="row align-items-center mb-2 g-2">
        <div class="col">
            <span class="small text-muted fw-semibold text-uppercase" style="letter-spacing: 0.04em;">Grafik tren</span>
        </div>
        <div class="col-auto">
            <select id="exec_chart_months" class="form-select form-select-sm" style="min-width: 130px;">
                <option value="3">3 bulan</option>
                <option value="6" selected>6 bulan</option>
                <option value="12">12 bulan</option>
            </select>
        </div>
    </div>
    <div class="row g-3 mb-3">
        <div class="col-xl-4 col-md-12">
            <div class="dash-card h-100">
                <h6 class="mb-0 fw-bold" style="color: var(--dash-text);">Penjualan</h6>
                <p class="small text-muted mb-2">Jumlah SO per bulan</p>
                <div id="chartExecSales" class="dash-exec-chart"></div>
            </div>
        </div>
        <div class="col-xl-4 col-md-12">
            <div class="dash-card h-100">
                <h6 class="mb-0 fw-bold" style="color: var(--dash-text);">Produksi</h6>
                <p class="small text-muted mb-2">Batch per bulan</p>
                <div id="chartExecProduction" class="dash-exec-chart"></div>
            </div>
        </div>
        <div class="col-xl-4 col-md-12">
            <div class="dash-card h-100">
                <h6 class="mb-0 fw-bold" style="color: var(--dash-text);">Pembelian</h6>
                <p class="small text-muted mb-2">Jumlah PO per bulan</p>
                <div id="chartExecPurchase" class="dash-exec-chart"></div>
            </div>
        </div>
    </div>

    @if ($showBahan)
       
        <div class="dash-toolbar">
            <div class="row g-3 align-items-end">
                <div class="col-md-4 col-sm-6">
                    <label class="form-label small text-muted mb-1">Rentang grafik</label>
                    <select class="form-select" id="dash_months">
                        <option value="6">6 bulan</option>
                        <option value="12" selected>12 bulan</option>
                        <option value="18">18 bulan</option>
                        <option value="24">24 bulan</option>
                    </select>
                </div>
                <div class="col-md-8 col-sm-12 d-flex flex-wrap gap-2 align-items-end justify-content-md-end">
                    <button type="button" class="btn btn-dash-primary" id="dash_apply">
                        <i class="fe fe-refresh-cw me-1"></i> Terapkan
                    </button>
                    <a href="{{ url('reportBahanBaku') }}" class="btn btn-outline-primary rounded-3">
                        <i class="fe fe-list me-1"></i> Laporan detail
                    </a>
                    <a href="{{ url('stockAlertSupplies') }}" class="btn btn-outline-warning rounded-3">
                        <i class="fe fe-alert-triangle me-1"></i> Peringatan stok
                    </a>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-lg-4 col-md-6">
                <div class="dash-kpi">
                    <h6>Qty net bulan ini</h6>
                    <div class="value" id="kpi_this_net">—</div>
                    <div class="sub" id="kpi_mom_net">MoM: —</div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="dash-kpi">
                    <h6>Transaksi bulan ini</h6>
                    <div class="value" id="kpi_this_txn">—</div>
                    <div class="sub" id="kpi_mom_txn">MoM: —</div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="dash-kpi">
                    <h6>Bahan di bawah alert</h6>
                    <div class="value" id="kpi_low_stock">—</div>
                    <div class="sub">Stok agregat &lt; batas alert</div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-xl-8">
                <div class="dash-card">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                        <div>
                            <h5 class="mb-0">Pemakaian per bulan</h5>
                            <span class="badge-dash mt-2 d-inline-block">Qty net + transaksi</span>
                        </div>
                        <span class="text-muted small" id="dash_range_label"></span>
                    </div>
                    <div id="chartPemakaianBahan"></div>
                    <p class="dash-disclaimer mb-0" id="dash_disclaimer"></p>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="dash-card h-100 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <h5 class="mb-0">Top penjualan</h5>
                    </div>
                    <span class="text-muted small mb-2 d-block" id="dash_top_sales_range">—</span>
                    <div class="table-responsive flex-grow-1 dash-top-sales-scroll">
                        <table class="table table-sm table-hover table-top-materials mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Produk / varian</th>
                                    <th class="text-end">Qty</th>
                                </tr>
                            </thead>
                            <tbody id="dash_top_sales_body">
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">Memuat…</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <a href="{{ url('salesOrder') }}" class="btn btn-sm btn-outline-primary rounded-3 mt-2">Sales order</a>
                </div>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-md-6">
                <div class="dash-card h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="mb-0">Pola kemasan &amp; wadah</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover table-top-materials mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nama</th>
                                    <th class="text-end">Net qty</th>
                                </tr>
                            </thead>
                            <tbody id="dash_top_body_kemasan">
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">Memuat…</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="dash-card h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="mb-0">Luar pola kemasan</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover table-top-materials mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nama</th>
                                    <th class="text-end">Net qty</th>
                                </tr>
                            </thead>
                            <tbody id="dash_top_body_bahan">
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">Memuat…</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mt-1" id="dash_procurement_section">
            <div class="col-12">
                <div class="dash-card">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                        <div>
                            <h5 class="mb-0">Estimasi pembelian bahan (produksi)</h5>
                        </div>
                        <span class="badge bg-light text-dark border" id="dash_procurement_next_badge">—</span>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="dash-card">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                        <h5 class="mb-0">Estimasi — pola kemasan &amp; wadah</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover table-top-materials mb-0" title="Format angka Indonesia: titik (.) = ribuan, koma (,) = desimal. Contoh 1.750 = seribu tujuh ratus lima puluh, bukan satu koma tujuh lima.">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Bahan</th>
                                    <th class="text-end">Total rentang</th>
                                    <th class="text-end">Rata/bln</th>
                                    <th class="text-end">Est. bln depan</th>
                                    <th class="text-end">Stok agregat</th>
                                    <th class="text-end">Kurang (indikatif)</th>
                                </tr>
                            </thead>
                            <tbody id="dash_procurement_body_kemasan">
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-3">Memuat…</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="dash-card">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                        <h5 class="mb-0">Estimasi — luar pola kemasan</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover table-top-materials mb-0" title="Format angka Indonesia: titik (.) = ribuan, koma (,) = desimal. Contoh 1.750 = seribu tujuh ratus lima puluh, bukan satu koma tujuh lima.">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Bahan</th>
                                    <th class="text-end">Total rentang</th>
                                    <th class="text-end">Rata/bln</th>
                                    <th class="text-end">Est. bln depan</th>
                                    <th class="text-end">Stok agregat</th>
                                    <th class="text-end">Kurang (indikatif)</th>
                                </tr>
                            </thead>
                            <tbody id="dash_procurement_body_bahan">
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-3">Memuat…</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="dash-disclaimer mb-0 mt-2" id="dash_procurement_disclaimer"></p>
                </div>
            </div>
        </div>
    @endif
</div>
