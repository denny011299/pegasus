<style>
    .dash-home {
        background: #f3f6fb;
        border: 1px solid #e4ebf5;
        border-radius: 16px;
        padding: 1rem 1rem 1.1rem;
        overflow-x: hidden;
    }

    .dash-section-title {
        margin: 0 0 0.55rem 0;
        font-size: 0.93rem;
        font-weight: 700;
        color: #0f172a;
        letter-spacing: 0.01em;
    }

    .dash-card {
        background: #fff;
        border: 1px solid #e8edf6;
        border-radius: 14px;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
        padding: 0.95rem;
        min-width: 0;
        overflow: hidden;
    }

    /* h-100 pada kartu: hanya kolom dengan satu kartu — hindari tumpang tindih jika satu kolom berisi dua+ kartu */
    .dash-card.dash-card-fill {
        height: 100%;
    }

    .dash-kpi-title {
        font-size: 0.78rem;
        color: #64748b;
        text-transform: uppercase;
        font-weight: 700;
        line-height: 1.35;
        margin-bottom: 0.25rem;
    }

    .dash-kpi-value {
        font-size: 1.45rem;
        font-weight: 800;
        color: #1e3a8a;
        line-height: 1.2;
        overflow-wrap: anywhere;
    }

    .dash-kpi-sub {
        font-size: 0.82rem;
        color: #64748b;
        line-height: 1.35;
        overflow-wrap: anywhere;
        margin-top: 0.2rem;
    }

    .dash-table {
        table-layout: auto;
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .dash-table thead th {
        background: #eef3ff !important;
        font-size: 0.77rem;
        color: #334155;
    }

    .dash-table td,
    .dash-table th {
        vertical-align: top;
    }

    /* Log persetujuan: jangan remuk kolom — scroll horizontal di dalam kartu */
    .dash-approval-table {
        min-width: 640px;
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
        min-width: 9.5rem;
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

    .dash-scroll {
        max-height: 245px;
        overflow: auto;
    }

    .dash-scroll-tall {
        max-height: 340px;
        overflow: auto;
    }

    .dash-bahan-alert-table tr.dash-bahan-row-critical td {
        background-color: #fee2e2 !important;
    }

    .dash-bahan-alert-table tr.dash-bahan-row-warn td {
        background-color: #fef9c3 !important;
    }

    .dash-bahan-alert-table td {
        vertical-align: middle;
    }

    #dash_overstock_list {
        padding-left: 1rem;
        margin-bottom: 0;
        max-height: 190px;
        overflow: auto;
    }

    #dash_overstock_list li {
        margin-bottom: 0.35rem;
        line-height: 1.35;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    #dash_overstock_list li:last-child {
        margin-bottom: 0;
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
        color: #64748b;
        font-size: 0.78rem;
    }

    @media (max-width: 991.98px) {
        .dash-home {
            padding: 0.8rem;
        }

        .dash-kpi-value {
            font-size: 1.2rem;
        }

        #dash_filter_label {
            text-align: left !important;
            margin-top: 0.35rem;
        }

        .dash-scroll {
            max-height: none;
        }
    }
</style>

<div class="dash-home">
    <div class="row g-2 mb-3">
        <div class="col-md-3">
            <select id="dash_filter_period" class="form-select form-select-sm">
                <option value="week">Minggu</option>
                <option value="month" selected>Bulan</option>
                <option value="year">Tahun</option>
            </select>
        </div>
        <div class="col-md-3">
            <button class="btn btn-primary btn-sm" id="dash_refresh_btn">
                <i class="fe fe-refresh-cw me-1"></i>Terapkan
            </button>
        </div>
        <div class="col-md-6 text-md-end text-muted small align-self-center" id="dash_filter_label">-</div>
    </div>
    <p class="dash-muted-note small mb-3 mb-md-4" id="dash_filter_hint">Memuat penjelasan filter…</p>

    <h6 class="dash-section-title">Ringkasan Changelog & KPI</h6>
    <div class="row g-3 mb-3">
        <div class="col-lg-3 col-md-6">
            <div class="dash-card dash-card-fill">
                <div class="dash-kpi-title">Changelog</div>
                <div class="dash-kpi-value" id="kpi_changelog">0</div>
                <div class="dash-kpi-sub">Retur &amp; penyesuaian kas · tunggu ACC</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="dash-card dash-card-fill">
                <div class="dash-kpi-title">Confirmation Log</div>
                <div class="dash-kpi-value" id="kpi_confirmation">0</div>
                <div class="dash-kpi-sub">SO / PO / Produksi menunggu ACC</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="dash-card dash-card-fill">
                <div class="dash-kpi-title">Revision Log</div>
                <div class="dash-kpi-value text-danger" id="kpi_revision">0</div>
                <div class="dash-kpi-sub">Ditolak · perlu perbaikan</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="dash-card dash-card-fill">
                <div class="dash-kpi-title">Inventory Value</div>
                <div class="dash-kpi-value" id="kpi_inventory_value">Rp 0</div>
                <div class="dash-kpi-sub" id="kpi_inventory_split">Produk Rp 0 · Bahan Rp 0</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-3 col-md-6">
            <div class="dash-card dash-card-fill">
                <div class="dash-kpi-title">Sales Growth %</div>
                <div class="dash-kpi-value" id="kpi_sales_growth">0%</div>
                <div class="dash-kpi-sub" id="kpi_sales_growth_sub">Output pengiriman vs periode sebelumnya (rentang sama)</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="dash-card dash-card-fill">
                <div class="dash-kpi-title">Inventory Turnover</div>
                <div class="dash-kpi-value" id="kpi_turnover">0</div>
                <div class="dash-kpi-sub" id="kpi_turnover_sub">Keluar stok (log) vs stok sekarang · annualized · sesuai filter</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="dash-card dash-card-fill">
                <div class="dash-kpi-title">Days Inventory Outstanding</div>
                <div class="dash-kpi-value" id="kpi_dio">0 hari</div>
                <div class="dash-kpi-sub" id="kpi_dio_sub">Dari turnover annualized · sesuai filter</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="dash-card dash-card-fill">
                <div class="dash-kpi-title">Return Rate %</div>
                <div class="dash-kpi-value" id="kpi_return_rate">0%</div>
                <div class="dash-kpi-sub" id="kpi_return_split">Barang jadi: retur÷pengiriman · Bahan: retur pembelian÷qty PO · sesuai filter</div>
            </div>
        </div>
    </div>

    <h6 class="dash-section-title">Detail Changelog &amp; log persetujuan</h6>
    <p class="dash-muted-note small mb-2">
        <strong>Changelog</strong>: data dari <em>Retur Produk</em> dan <em>Kas Sales</em> status menunggu ACC (bukan SO/PO baru).
        <strong>Confirmation</strong>: pengiriman, pembelian, produksi yang harus dikonfirmasi — link ke halaman detail untuk ACC.
        <strong>Revision</strong>: transaksi ditolak — buka halaman yang sama untuk perbaiki / input ulang.
        Permintaan ubah nominal / hapus pembelian khusus ke changelog bisa ditambah nanti lewat modul terpisah jika diperlukan.
    </p>
    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <div class="dash-card dash-card-fill">
                <h6 class="mb-2">Changelog — menunggu ACC Direktur</h6>
                <div class="table-responsive dash-scroll">
                    <table class="table table-sm dash-table dash-approval-table mb-0">
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
        <div class="col-lg-4">
            <div class="dash-card dash-card-fill">
                <h6 class="mb-2">Confirmation Log</h6>
                <div class="table-responsive dash-scroll">
                    <table class="table table-sm dash-table dash-approval-table mb-0">
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
        <div class="col-lg-4">
            <div class="dash-card dash-card-fill">
                <h6 class="mb-2">Revision Log</h6>
                <div class="table-responsive dash-scroll">
                    <table class="table table-sm dash-table dash-approval-table mb-0">
                        <thead>
                            <tr>
                                <th>Modul</th>
                                <th>Ref</th>
                                <th>Alasan</th>
                                <th class="text-end">Buka</th>
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

    <h6 class="dash-section-title">Grafik & Top Product Pengiriman</h6>
    <div class="row g-3 mb-3">
        <div class="col-xl-8">
            <div class="dash-card dash-card-fill">
                <h6 class="mb-1">Grafik output pengiriman</h6>
                <div class="dash-muted-note mb-2" id="dash_chart_caption">Qty pengiriman &amp; retur per potongan waktu; garis pertumbuhan % antar potongan mengikuti filter.</div>
                <div id="dash_main_chart"></div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="dash-card mb-3">
                <h6 class="mb-1" id="dash_top_yearly_title">Top 5 · reset tahun (YTD)</h6>
                <div class="dash-muted-note small mb-2" id="dash_top_yearly_sub">—</div>
                <div class="table-responsive dash-scroll">
                    <table class="table table-sm dash-table dash-top5-table mb-0">
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
            <div class="dash-card">
                <h6 class="mb-1" id="dash_top_accum_title">Top 5 · akumulasi</h6>
                <div class="dash-muted-note small mb-2" id="dash_top_accum_sub">—</div>
                <div class="table-responsive dash-scroll">
                    <table class="table table-sm dash-table dash-top5-table mb-0">
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

    <h6 class="dash-section-title">Stock Aging & Warning</h6>
    <div class="row g-3">
        <div class="col-12">
            <div class="dash-card dash-card-fill">
                <h6 class="mb-2">Stock Aging</h6>
                <p class="dash-muted-note small mb-2 mb-xl-3">Umur dari lapisan FIFO stok yang belum keluar (sampai tanggal akhir filter dashboard). Klik <strong>Lihat</strong> untuk rincian barang jadi &amp; bahan per kelompok umur.</p>
                <div class="table-responsive dash-scroll">
                    <table class="table table-sm dash-table dash-stock-aging-table mb-0">
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

    <div class="row g-3">
        <div class="col-12">
            <div class="dash-card dash-card-fill">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
                    <div>
                        <h6 class="mb-1">Stock Alert — Bahan mentah</h6>
                        <p class="dash-muted-note small mb-0">
                            <span class="badge bg-danger" id="dash_bahan_badge_crit">0 habis</span>
                            <span class="badge bg-warning text-dark ms-1" id="dash_bahan_badge_warn">0 mendekati batas</span>
                            Merah = stok habis (semua satuan 0). Kuning = stok default unit ≤ batas alert (waktunya order). Produk jadi tidak ditampilkan di sini.
                        </p>
                    </div>
                    <div class="d-flex flex-wrap gap-1 align-items-center">
                        <button type="button" class="btn btn-outline-secondary btn-sm py-1" id="dash_bahan_notif_perm" title="Izinkan notifikasi browser saat ada alert">
                            <i class="fe fe-bell"></i> Notifikasi
                        </button>
                        <a class="btn btn-primary btn-sm py-1" id="dash_bahan_link_alert" href="{{ url('stockAlertSupplies') }}">Peringatan lengkap</a>
                        <a class="btn btn-outline-primary btn-sm py-1" id="dash_bahan_link_po" href="{{ url('purchaseOrder') }}">Purchase Order</a>
                    </div>
                </div>
                <div class="table-responsive dash-scroll-tall">
                    <table class="table table-sm dash-table mb-0 dash-bahan-alert-table">
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

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="dash-card dash-card-fill">
                <h6 class="mb-2">Overstock (aging &gt; 90 hari)</h6>
                <ul class="mb-0 small" id="dash_overstock_list">
                    <li class="text-muted">Memuat...</li>
                </ul>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="dash-card dash-card-fill">
                <h6 class="mb-2">Warning - Recommended Stok Produksi Hari Ini</h6>
                <p class="dash-muted-note small mb-2" id="dash_recommended_note"></p>
                <div class="table-responsive dash-scroll">
                    <table class="table table-sm dash-table dash-reco-table mb-0">
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

    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 10800">
        <div id="dashBahanToast" class="toast align-items-center border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body small" id="dashBahanToastBody"></div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Tutup"></button>
            </div>
        </div>
    </div>

    <div class="modal fade" id="dashAgingDetailModal" tabindex="-1" aria-labelledby="dashAgingDetailTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h5 class="modal-title fs-6" id="dashAgingDetailTitle">Detail stok</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body pt-2">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
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
