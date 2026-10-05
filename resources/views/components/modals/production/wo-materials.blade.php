{{-- Modal Ambil Bahan (Job) + ACC 1 STB (tab ACC Bahan). --}}
<style>
    #modalWoMaterials.pg-modal--confirm .modal-dialog,
    #modalWoMaterials.pg-modal--form .modal-dialog {
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
    /* Modal Konfirmasi ACC Bahan -> Header Hijau Standar Pegasus */
    #modalWoMaterials.pg-modal--confirm .modal-header {
        background: linear-gradient(135deg, #064e3b 0%, #059669 100%) !important;
        padding: 18px 24px;
        border: 0;
    }
    /* Modal Form Ambil Bahan -> Header Biru Standar Pegasus */
    #modalWoMaterials.pg-modal--form .modal-header {
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

    /* Info Bar Metadata (Tanpa Card Box) */
    #modalWoMaterials .wo-mat-meta-bar {
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 0.85rem;
        margin-bottom: 1.15rem;
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
        padding: 10px 14px !important;
        white-space: nowrap;
    }
    #modalWoMaterials .wo-table-custom tbody td {
        padding: 11px 14px !important;
        vertical-align: middle !important;
        font-size: 13px !important;
        border-bottom: 1px solid #f1f5f9 !important;
        background: #ffffff;
    }
    #modalWoMaterials .wo-table-custom tbody tr:hover td {
        background-color: #f8fafc !important;
    }

    /* Alur & Tahap Persetujuan (Flat Strip, Bukan Card dalam Card) */
    #modalWoMaterials .wo-timeline-step {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 10px 12px;
        height: 100%;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    #modalWoMaterials .wo-timeline-step .icon-box {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        flex-shrink: 0;
    }
    #modalWoMaterials .wo-timeline-step .step-title {
        font-size: 10.5px;
        font-weight: 700;
        text-transform: uppercase;
        color: #64748b;
        letter-spacing: 0.4px;
        margin-bottom: 2px;
        line-height: 1.2;
    }
    #modalWoMaterials .wo-timeline-step .step-val {
        font-size: 12.5px;
        font-weight: 700;
        line-height: 1.2;
    }

    /* Status Pill Badges (Pegasus Unified Theme) */
    #modalWoMaterials .pp-status {
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        padding: 3.5px 11px !important;
        border-radius: 999px !important;
        font-size: 12px !important;
        font-weight: 600 !important;
        white-space: nowrap !important;
        line-height: 1.25 !important;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04) !important;
        letter-spacing: 0.2px !important;
    }
    #modalWoMaterials .pp-status::before {
        content: "";
        width: 7px;
        height: 7px;
        border-radius: 50%;
        display: inline-block;
        flex-shrink: 0;
    }
    #modalWoMaterials .pp-status.awaiting_ops,
    #modalWoMaterials .pp-status.waiting_ops,
    #modalWoMaterials .pp-status.warning {
        background: #fefce8 !important;
        color: #854d0e !important;
        border: 1px solid #fef08a !important;
    }
    #modalWoMaterials .pp-status.awaiting_ops::before,
    #modalWoMaterials .pp-status.waiting_ops::before,
    #modalWoMaterials .pp-status.warning::before {
        background: #eab308;
        box-shadow: 0 0 0 2px rgba(234, 179, 8, 0.25);
    }
    #modalWoMaterials .pp-status.awaiting_qc {
        background: #f0f9ff !important;
        color: #0284c7 !important;
        border: 1px solid #7dd3fc !important;
    }
    #modalWoMaterials .pp-status.awaiting_qc::before {
        background: #0284c7;
        box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.25);
    }
    #modalWoMaterials .pp-status.done {
        background: #f0fdf4 !important;
        color: #15803d !important;
        border: 1px solid #86efac !important;
    }
    #modalWoMaterials .pp-status.done::before {
        background: #16a34a;
        box-shadow: 0 0 0 2px rgba(22, 163, 74, 0.25);
    }
    #modalWoMaterials .pp-status.draft {
        background: #fff7ed !important;
        color: #c2410c !important;
        border: 1px solid #fdba74 !important;
    }
    #modalWoMaterials .pp-status.draft::before {
        background: #ea580c;
        box-shadow: 0 0 0 2px rgba(234, 88, 12, 0.25);
    }

    #modalWoMaterials .pg-modal-footer .btn {
        height: 42px !important;
        border-radius: 8px !important;
        font-size: 13px !important;
        font-weight: 600 !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }
    #modalWoMaterials .pg-modal-footer .btn[style*="display: none"],
    #modalWoMaterials .pg-modal-footer .btn[style*="display:none"],
    #modalWoMaterials .pg-modal-footer .btn.d-none {
        display: none !important;
    }
    /* Di modal ACC Bahan, tombol Simpan Ambil Bahan wajib tersembunyi */
    #modalWoMaterials.pg-modal--confirm #wo-mat-save {
        display: none !important;
    }

    #modalWoMaterials .wo-actions-wrap .pg-btn-confirm,
    #modalWoMaterials #wo-mat-save.pg-btn-save {
        min-width: 160px !important;
    }

    /* Tombol Cetak STB Secondary: Soft Emerald saat modal konfirmasi hijau */
    #modalWoMaterials.pg-modal--confirm .pg-btn-print,
    #modalWoMaterials.pg-modal--confirm #wo-mat-print {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        background: #ecfdf5 !important;
        color: #065f46 !important;
        border: 1px solid #a7f3d0 !important;
        border-radius: 8px !important;
        padding: 9px 20px !important;
        font-size: 13px !important;
        font-weight: 600 !important;
        height: 42px !important;
        box-shadow: 0 1px 2px rgba(5, 150, 105, 0.06) !important;
        transition: all 0.18s ease-in-out !important;
    }
    #modalWoMaterials.pg-modal--confirm .pg-btn-print:hover,
    #modalWoMaterials.pg-modal--confirm #wo-mat-print:hover {
        background: #d1fae5 !important;
        border-color: #6ee7b7 !important;
        color: #047857 !important;
        box-shadow: 0 2px 6px rgba(5, 150, 105, 0.16) !important;
        transform: translateY(-1px) !important;
    }

    /* Tombol Cetak STB saat modal biru */
    #modalWoMaterials.pg-modal--form .pg-btn-print,
    #modalWoMaterials.pg-modal--form #wo-mat-print {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        background: #eff6ff !important;
        color: #1d4ed8 !important;
        border: 1px solid #bfdbfe !important;
        border-radius: 8px !important;
        padding: 9px 20px !important;
        font-size: 13px !important;
        font-weight: 600 !important;
        height: 42px !important;
        transition: all 0.18s ease-in-out !important;
    }
    #modalWoMaterials.pg-modal--form .pg-btn-print:hover,
    #modalWoMaterials.pg-modal--form #wo-mat-print:hover {
        background: #dbeafe !important;
        border-color: #93c5fd !important;
        color: #1e40af !important;
    }
</style>

<div class="modal custom-modal fade pg-modal--confirm wo-touch" id="modalWoMaterials" tabindex="-1"
    aria-hidden="true" data-bs-backdrop="static" data-bs-focus="false">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content d-flex flex-column">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="pg-modal-icon">
                        <i class="fe fe-check-circle" id="wo-mat-header-icon"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-bold text-white modal-title" id="wo-mat-title" style="font-size: 16px;">ACC Bahan</h5>
                        <small class="d-block text-white-50 modal-subtitle" id="wo-mat-subtitle">—</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                {{-- Mode AMBIL: form qty dari resep WO --}}
                <div id="wo-mat-ambil-panel" style="display:none;">
                    {{-- Info Dokumen (Tanpa Card Box) --}}
                    <div class="wo-mat-meta-bar" id="wo-mat-ambil-meta-wrap">
                        <div id="wo-mat-ambil-meta"></div>
                    </div>

                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                        <div class="small text-uppercase text-muted fw-bold" style="letter-spacing:.5px; font-size:11px;">
                            <i class="fe fe-package text-primary me-1"></i> AMBIL BAHAN DARI RESEP WO
                        </div>
                        <span class="text-muted small" id="wo-mat-hint" style="font-size: 11.5px;">
                            <i class="fe fe-info me-1 text-primary"></i>Qty saran dari BOM. Setelah simpan &rarr; ACC Ops &rarr; QC (potong stok).
                        </span>
                    </div>

                    <div class="table-responsive mb-2">
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
                    {{-- Info Dokumen (Tanpa Card Box) --}}
                    <div class="wo-mat-meta-bar" id="wo-mat-meta-wrap">
                        <div id="wo-mat-meta"></div>
                    </div>

                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="small text-uppercase text-muted fw-bold" style="letter-spacing:.5px; font-size:11px;">
                                <i class="fe fe-box text-success me-1"></i> DAFTAR BAHAN YANG DIAMBIL PIC
                            </div>
                            <span class="badge bg-light text-secondary border px-2 py-0.5 rounded-pill" id="wo-mat-items-count" style="font-size: 11px;">0 Bahan</span>
                        </div>
                        <span class="text-muted small" id="wo-mat-acc-hint" style="font-size: 11.5px;">
                            <i class="fe fe-info me-1 text-primary"></i>Stok gudang akan dipotong otomatis setelah ACC QC
                        </span>
                    </div>

                    <div class="table-responsive mb-3">
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

                    {{-- Alur Persetujuan (Tanpa Card Outer Box) --}}
                    <div class="mt-4 pt-3 border-top" id="wo-mat-timeline-box">
                        <div class="small text-uppercase text-muted fw-bold mb-2" style="letter-spacing:.5px; font-size:11px;">
                            <i class="fe fe-activity text-primary me-1"></i> ALUR &amp; TAHAP PERSETUJUAN
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
                <button type="button" class="btn pg-btn-save" id="wo-mat-save" style="display:none;">
                    <i class="fe fe-check me-1"></i> Simpan Ambil Bahan
                </button>
                <div class="wo-actions-wrap" id="wo-mat-acc-wrap"></div>
            </div>
        </div>
    </div>
</div>
