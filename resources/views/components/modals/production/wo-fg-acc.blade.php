{{-- Modal ACC Form Gudang (QC → Kepala Ops) — terpisah dari modal Sudah Diproduksi. --}}
<style>
    #modalWoFgAcc.pg-modal--confirm .modal-dialog {
        max-width: 920px;
        height: auto !important;
        margin: 0.75rem auto;
    }
    #modalWoFgAcc .modal-content {
        max-height: calc(100dvh - 1.5rem);
        min-height: 0;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        border-radius: 16px;
        border: none;
        box-shadow: 0 20px 45px rgba(0, 0, 0, 0.2);
    }
    #modalWoFgAcc .modal-header,
    #modalWoFgAcc .modal-footer {
        flex: 0 0 auto;
    }
    #modalWoFgAcc.pg-modal--confirm .modal-header {
        background: linear-gradient(135deg, #064e3b 0%, #059669 100%) !important;
        padding: 18px 24px;
        border: 0;
    }
    #modalWoFgAcc .modal-body {
        min-height: 0;
        overflow-y: auto;
        background: #fff;
        padding: 1.25rem 1.5rem;
    }
    #modalWoFgAcc .wo-fg-meta-bar {
        margin-bottom: 1rem;
    }
    #modalWoFgAcc .wo-table-custom thead th {
        background: #f8fafc !important;
        color: #475569 !important;
        font-size: 11px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.4px !important;
        border-bottom: 2px solid #e2e8f0 !important;
        padding: 10px 12px !important;
        white-space: nowrap;
    }
    #modalWoFgAcc .wo-table-custom tbody td {
        padding: 10px 12px !important;
        vertical-align: middle !important;
        font-size: 13px !important;
        border-bottom: 1px solid #f1f5f9 !important;
        background: #ffffff;
    }
    #modalWoFgAcc .wo-table-custom tbody tr:hover td {
        background-color: #f8fafc !important;
    }
    #modalWoFgAcc .pp-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        white-space: nowrap;
    }
    #modalWoFgAcc .pp-status::before {
        content: "";
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: currentColor;
    }
    #modalWoFgAcc .pp-status.awaiting_qc {
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
    }
    #modalWoFgAcc .pp-status.awaiting_ops {
        background: #fffbeb;
        color: #b45309;
        border: 1px solid #fde68a;
    }
    #modalWoFgAcc .pp-status.done {
        background: #ecfdf5;
        color: #047857;
        border: 1px solid #a7f3d0;
    }
    #modalWoFgAcc .pg-modal-footer .btn {
        height: 42px !important;
        border-radius: 8px !important;
        font-size: 13px !important;
        font-weight: 600 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 6px !important;
    }
    #modalWoFgAcc .wo-print-btn {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 6px !important;
        border-radius: 8px !important;
        background: #ecfdf5 !important;
        color: #065f46 !important;
        border: 1px solid #a7f3d0 !important;
        text-decoration: none !important;
        font-weight: 600 !important;
        font-size: 13px !important;
        height: 42px !important;
        padding: 9px 20px !important;
    }
    #modalWoFgAcc .wo-print-btn:hover {
        background: #d1fae5 !important;
        border-color: #6ee7b7 !important;
        color: #047857 !important;
    }
    #modalWoFgAcc .wo-timeline-step {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 10px 12px;
        height: 100%;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    #modalWoFgAcc .wo-timeline-step .icon-box {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        background: #e2e8f0;
        color: #64748b;
    }
    #modalWoFgAcc .wo-timeline-step.is-done .icon-box {
        background: #d1fae5;
        color: #059669;
    }
    #modalWoFgAcc .wo-timeline-step.is-active .icon-box {
        background: #fef3c7;
        color: #d97706;
    }
    #modalWoFgAcc .wo-timeline-step .step-title {
        font-size: 11px;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 2px;
    }
    #modalWoFgAcc .wo-timeline-step .step-val {
        font-size: 11.5px;
        color: #64748b;
    }
</style>

<div class="modal custom-modal fade pg-modal--confirm wo-touch" id="modalWoFgAcc" tabindex="-1"
    aria-hidden="true" data-bs-backdrop="static" data-bs-focus="false">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content d-flex flex-column">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="pg-modal-icon">
                        <i class="fe fe-check-circle" id="wo-fg-acc-header-icon"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-bold text-white modal-title" id="wo-fg-acc-title" style="font-size: 16px;">ACC Form Gudang</h5>
                        <small class="d-block text-white-50 modal-subtitle" id="wo-fg-acc-subtitle">—</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="wo-fg-meta-bar" id="wo-fg-acc-meta"></div>

                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                    <div class="small text-uppercase text-muted fw-bold" style="letter-spacing:.5px; font-size:11px;">
                        <i class="fe fe-package text-success me-1"></i> HASIL PRODUKSI — FORM GUDANG
                    </div>
                    <span class="text-muted small" id="wo-fg-acc-hint" style="font-size: 11.5px;"></span>
                </div>

                <div class="table-responsive border rounded-3 mb-3">
                    <table class="table table-hover mb-0 align-middle wo-table-custom">
                        <thead>
                            <tr>
                                <th style="min-width: 200px;">Produk</th>
                                <th style="min-width: 110px;">Target</th>
                                <th style="min-width: 140px;">Hasil Tercatat</th>
                                <th style="min-width: 120px;">Keterangan</th>
                                <th class="text-center" style="min-width: 120px;">Status</th>
                            </tr>
                        </thead>
                        <tbody id="wo-fg-acc-rows"></tbody>
                    </table>
                </div>

                <div class="mt-2 pt-3 border-top">
                    <div class="small text-uppercase text-muted fw-bold mb-2" style="letter-spacing:.5px; font-size:11px;">
                        Alur &amp; tahap persetujuan
                    </div>
                    <div class="row g-2" id="wo-fg-acc-timeline"></div>
                </div>
            </div>

            {{-- Tutup → Print → ACC (aksi utama paling kanan) --}}
            <div class="modal-footer d-flex justify-content-end align-items-center gap-2 pg-modal-footer">
                <button type="button" class="btn pg-btn-cancel" data-bs-dismiss="modal">Tutup</button>
                <a class="btn wo-print-btn" id="wo-fg-acc-print" href="#" target="_blank" rel="noopener" style="display:none;">
                    <i class="fe fe-printer me-1"></i> Print Form Gudang
                </a>
                <a class="btn wo-print-btn" id="wo-fg-acc-print-tally" href="#" target="_blank" rel="noopener" style="display:none;">
                    <i class="fe fe-printer me-1"></i> Print Tally
                </a>
                <button type="button" class="btn pg-btn-confirm" id="wo-fg-acc-btn" style="display:none;">
                    <i class="fe fe-check me-1"></i> ACC
                </button>
            </div>
        </div>
    </div>
</div>
