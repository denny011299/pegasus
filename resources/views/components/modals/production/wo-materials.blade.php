{{-- Modal Ambil Bahan (Job) + ACC 1 STB (tab ACC Bahan). --}}
<style>
    #modalWoMaterials.pg-modal--confirm .modal-dialog {
        max-width: 940px;
        height: auto !important;
        margin: 0.75rem auto;
    }
    #modalWoMaterials .modal-content {
        max-height: calc(100dvh - 1.5rem);
        min-height: 0;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        border-radius: 16px;
        border: none;
        box-shadow: 0 20px 45px rgba(0, 0, 0, 0.2);
    }
    #modalWoMaterials .modal-header,
    #modalWoMaterials .modal-footer {
        flex: 0 0 auto;
    }
    #modalWoMaterials.pg-modal--confirm .modal-header {
        background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%) !important;
        padding: 18px 24px;
        border: 0;
    }
    #modalWoMaterials .modal-body {
        min-height: 0;
        overflow-y: auto;
        background: #fff;
        padding: 1.25rem 1.5rem;
    }

    #modalWoMaterials .wo-mat-summary-banner {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 14px 16px;
        margin-bottom: 1.25rem;
    }
    #modalWoMaterials .wo-summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 12px 16px;
    }
    #modalWoMaterials .wo-summary-item {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    #modalWoMaterials .wo-summary-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        color: #2563eb;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    }
    #modalWoMaterials .wo-summary-info .lbl {
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        color: #64748b;
        margin-bottom: 2px;
        line-height: 1;
    }
    #modalWoMaterials .wo-summary-info .val {
        font-size: 13px;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.2;
    }

    #modalWoMaterials .wo-section-head-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: #eff6ff;
        color: #2563eb;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 15px;
    }

    #modalWoMaterials .wo-table-custom {
        margin-bottom: 0;
        width: 100% !important;
    }
    #modalWoMaterials .wo-table-custom thead th {
        background: #f8fafc !important;
        color: #475569 !important;
        font-size: 11px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.4px !important;
        border-bottom: 2px solid #e2e8f0 !important;
        padding: 11px 14px !important;
        white-space: nowrap;
    }
    #modalWoMaterials .wo-table-custom tbody td {
        padding: 12px 14px !important;
        vertical-align: middle !important;
        font-size: 13px !important;
        border-bottom: 1px solid #f1f5f9 !important;
        background: #ffffff;
    }
    #modalWoMaterials .wo-table-custom tbody tr:hover td {
        background-color: #f8fafc !important;
    }

    #modalWoMaterials .wo-timeline-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px 14px;
        height: 100%;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    #modalWoMaterials .wo-timeline-card .icon-box {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        flex-shrink: 0;
    }
    #modalWoMaterials .wo-timeline-card .step-title {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        color: #64748b;
        letter-spacing: 0.4px;
        margin-bottom: 2px;
    }
    #modalWoMaterials .wo-timeline-card .step-val {
        font-size: 12px;
        font-weight: 700;
    }

    #modalWoMaterials .pg-modal-footer .btn {
        height: 42px !important;
        border-radius: 8px !important;
        font-size: 13px !important;
        font-weight: 600 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 6px !important;
    }
    #modalWoMaterials .wo-actions-wrap .pg-btn-confirm {
        min-width: 160px !important;
    }
    #modalWoMaterials .pg-btn-print,
    #modalWoMaterials .wo-print-btn {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 6px !important;
        background: #ffffff !important;
        color: #475569 !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 8px !important;
        padding: 9px 18px !important;
        font-size: 13px !important;
        font-weight: 600 !important;
        height: 42px !important;
        transition: all 0.15s ease-in-out !important;
    }
    #modalWoMaterials .pg-btn-print:hover,
    #modalWoMaterials .wo-print-btn:hover {
        background: #f8fafc !important;
        border-color: #94a3b8 !important;
        color: #0f172a !important;
    }
</style>

<div class="modal custom-modal fade pg-modal--confirm wo-touch" id="modalWoMaterials" tabindex="-1"
    aria-hidden="true" data-bs-backdrop="static" data-bs-focus="false">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content d-flex flex-column">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="pg-modal-icon">
                        <i class="fe fe-package" id="wo-mat-header-icon"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-bold text-white modal-title" id="wo-mat-title" style="font-size: 16px;">Ambil Bahan</h5>
                        <small class="d-block text-white-50 modal-subtitle" id="wo-mat-subtitle">—</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                {{-- Mode AMBIL: form qty dari resep WO --}}
                <div id="wo-mat-ambil-panel" style="display:none;">
                    <div class="wo-mat-summary-banner" id="wo-mat-ambil-meta-wrap">
                        <div class="wo-summary-grid" id="wo-mat-ambil-meta"></div>
                    </div>
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2 pb-1">
                        <div class="d-flex align-items-center gap-2">
                            <div class="wo-section-head-icon">
                                <i class="fe fe-package"></i>
                            </div>
                            <h6 class="mb-0 fw-bold text-dark" style="font-size: 14px;">Ambil Bahan dari Resep WO</h6>
                        </div>
                        <span class="text-muted small" id="wo-mat-hint" style="font-size: 12px;">
                            <i class="fe fe-info me-1 text-primary"></i>Qty saran dari BOM. Setelah simpan → ACC Ops → QC (potong stok).
                        </span>
                    </div>
                    <div class="table-responsive border rounded-3 mb-2">
                        <table class="table table-hover mb-0 align-middle wo-table-custom" style="width:100%;">
                            <thead>
                                <tr>
                                    <th>Nama Bahan</th>
                                    <th style="width:90px;" class="text-center">Satuan</th>
                                    <th style="width:90px;" class="text-center">Resep</th>
                                    <th style="width:90px;" class="text-center">Sudah</th>
                                    <th style="width:90px;" class="text-center">Stok</th>
                                    <th style="width:110px;" class="text-center">Ambil</th>
                                </tr>
                            </thead>
                            <tbody id="wo-mat-rows"></tbody>
                        </table>
                    </div>
                </div>

                {{-- Mode ACC: detail 1 request STB --}}
                <div id="wo-mat-acc-panel" style="display:none;">
                    <div class="wo-mat-summary-banner" id="wo-mat-meta-wrap">
                        <div class="wo-summary-grid" id="wo-mat-meta"></div>
                    </div>

                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2 pb-1">
                        <div class="d-flex align-items-center gap-2">
                            <div class="wo-section-head-icon">
                                <i class="fe fe-box"></i>
                            </div>
                            <h6 class="mb-0 fw-bold text-dark" style="font-size: 14px;">Daftar Bahan Yang Diambil PIC</h6>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 ms-1" id="wo-mat-items-count" style="font-size: 11px;">0 Bahan</span>
                        </div>
                        <span class="text-muted small" id="wo-mat-acc-hint" style="font-size: 12px;">
                            <i class="fe fe-info me-1 text-primary"></i>Stok gudang akan dipotong otomatis setelah ACC QC
                        </span>
                    </div>

                    <div class="table-responsive border rounded-3 mb-3">
                        <table class="table table-hover mb-0 align-middle wo-table-custom" id="tableWoMatAcc" style="width:100%;">
                            <thead>
                                <tr>
                                    <th style="width:45px;" class="text-center">#</th>
                                    <th>Nama Bahan Baku</th>
                                    <th style="width:110px;" class="text-center">Satuan</th>
                                    <th style="width:130px;" class="text-center">Qty Diambil PIC</th>
                                    <th style="width:160px;" class="text-center">Status / Qty QC</th>
                                </tr>
                            </thead>
                            <tbody id="wo-mat-log-body"></tbody>
                        </table>
                    </div>

                    <div class="p-3 border rounded-3" style="background:#f8fafc;" id="wo-mat-timeline-box">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="fe fe-activity text-primary" style="font-size:14px;"></i>
                            <span class="fw-bold text-dark" style="font-size: 12px;">Alur & Tahap Persetujuan:</span>
                        </div>
                        <div class="row g-2" id="wo-mat-timeline-steps"></div>
                    </div>
                </div>
            </div>

            <div class="modal-footer d-flex justify-content-end align-items-center gap-2 pg-modal-footer">
                <button type="button" class="btn pg-btn-cancel" data-bs-dismiss="modal" id="wo-mat-close">Tutup</button>
                <a href="#" target="_blank" rel="noopener" class="btn pg-btn-print" id="wo-mat-print" style="display:none;">
                    <i class="fe fe-printer me-1"></i> Cetak Form STB
                </a>
                <button type="button" class="btn pg-btn-confirm" id="wo-mat-save" style="display:none;">
                    <i class="fe fe-check me-1"></i> Simpan Ambil Bahan
                </button>
                <div class="wo-actions-wrap" id="wo-mat-acc-wrap"></div>
            </div>
        </div>
    </div>
</div>
