{{-- Modal eksekusi WO per PIC — dipakai dari Production Planning (embed) & Monitor Work Orders. --}}
<style>
    #modalWoExecution.pg-modal--confirm .modal-dialog {
        max-width: 960px;
        height: auto !important;
        margin: 0.75rem auto;
    }
    #modalWoExecution .modal-content {
        max-height: calc(100dvh - 1.5rem);
        min-height: 0;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        border-radius: 16px;
        border: none;
        box-shadow: 0 20px 45px rgba(0, 0, 0, 0.2);
    }
    #modalWoExecution .modal-header,
    #modalWoExecution .modal-footer {
        flex: 0 0 auto;
    }
    #modalWoExecution.pg-modal--confirm .modal-header {
        background: linear-gradient(135deg, #064e3b 0%, #059669 100%) !important;
        padding: 18px 24px;
        border: 0;
    }
    #modalWoExecution .modal-body {
        min-height: 0;
        overflow-y: auto;
        background: #fff;
        padding: 1.25rem 1.5rem;
    }
    #modalWoExecution .select2-dropdown {
        z-index: 1065;
    }
    .wo-section-head {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 14px;
    }
    .wo-section-head-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: #ecfdf5;
        color: #059669;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .wo-section-head h6 {
        margin: 0;
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
    }
    .wo-fg-box {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 12px 14px;
        background: #f8fafc;
        margin-top: 1rem;
    }
    .wo-fg-box .wo-fg-status {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .wo-table-custom thead th {
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
    .wo-table-custom tbody td {
        padding: 10px 12px !important;
        vertical-align: middle !important;
        font-size: 13px !important;
        border-bottom: 1px solid #f1f5f9 !important;
        background: #ffffff;
    }
    .wo-table-custom tbody tr:hover td {
        background-color: #f8fafc !important;
    }
    .wo-table-custom .form-control,
    .wo-table-custom .form-select {
        height: 38px !important;
        font-size: 13px !important;
        border-color: #cbd5e1 !important;
        border-radius: 6px !important;
    }
    .wo-table-custom .form-control:focus,
    .wo-table-custom .form-select:focus {
        border-color: #059669 !important;
        box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15) !important;
    }

    #modalWoExecution .select2-container .select2-selection--single {
        height: 38px !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 6px !important;
        display: flex !important;
        align-items: center !important;
    }
    #modalWoExecution .select2-container .select2-selection__rendered {
        line-height: 36px !important;
        padding-left: 10px !important;
        font-size: 13px !important;
    }
    #modalWoExecution .select2-container .select2-selection__arrow {
        height: 36px !important;
    }
    /* Footer Buttons */
    #modalWoExecution .pg-modal-footer .btn {
        height: 42px !important;
        border-radius: 8px !important;
        font-size: 13px !important;
        font-weight: 600 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 6px !important;
    }
    #modalWoExecution .wo-print-btn {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 6px !important;
        border-radius: 8px !important;
        color: #475569 !important;
        background: #ffffff !important;
        border: 1px solid #cbd5e1 !important;
        text-decoration: none !important;
        font-weight: 600 !important;
        font-size: 13px !important;
        height: 42px !important;
        padding: 9px 18px !important;
        transition: all 0.15s ease-in-out !important;
    }
    #modalWoExecution .wo-print-btn:hover {
        background: #f8fafc !important;
        border-color: #94a3b8 !important;
        color: #0f172a !important;
    }
    #modalWoExecution .wo-print-btn.btn-sm {
        height: 34px !important;
        padding: 6px 12px !important;
        font-size: 12px !important;
    }
</style>

<div class="modal custom-modal fade pg-modal--confirm wo-touch" id="modalWoExecution" tabindex="-1"
    aria-hidden="true" data-bs-backdrop="static" data-bs-focus="false">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content d-flex flex-column">

            <div class="modal-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="pg-modal-icon">
                        <i class="fe fe-clipboard"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-bold text-white modal-title" id="wo-title" style="font-size: 16px;">Work Order</h5>
                        <small class="d-block text-white-50 modal-subtitle" id="wo-subtitle">—</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="wo-section-head">
                    <div class="wo-section-head-icon">
                        <i class="fe fe-check-circle"></i>
                    </div>
                    <h6>Hasil Produksi</h6>
                </div>

                <div class="table-responsive border rounded-3">
                    <table class="table table-hover mb-0 align-middle wo-table-custom">
                        <thead>
                            <tr>
                                <th style="min-width: 200px;">Produk</th>
                                <th style="min-width: 110px;">Target</th>
                                <th style="min-width: 140px;">Hasil Tercatat</th>
                                <th style="min-width: 120px;">Qty Hasil</th>
                                <th style="min-width: 140px;">Satuan</th>
                            </tr>
                        </thead>
                        <tbody id="wo-output-rows"></tbody>
                    </table>
                </div>

                <div id="wo-fg-panel" class="wo-fg-box" style="display:none;">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                        <div>
                            <div class="fw-bold text-dark">Form Gudang <span id="wo-fg-number" class="text-muted"></span></div>
                            <div class="wo-fg-status text-muted" id="wo-fg-status">—</div>
                            <div class="small text-muted mt-1" id="wo-fg-times"></div>
                        </div>
                        <div class="d-flex flex-wrap gap-2" id="wo-fg-actions"></div>
                    </div>
                </div>
            </div>

            {{-- Tutup → Print WO → Sudah Diproduksi (aksi utama paling kanan) --}}
            <div class="modal-footer d-flex justify-content-end align-items-center gap-2 pg-modal-footer">
                <button type="button" class="btn pg-btn-cancel" data-bs-dismiss="modal">Tutup</button>
                <a class="btn wo-print-btn" id="wo-print" href="#" target="_blank" rel="noopener" title="Print Work Order">
                    <i class="fe fe-printer me-1"></i> Print Work Order
                </a>
                <button type="button" class="btn pg-btn-confirm" id="wo-report">
                    <i class="fe fe-check me-1"></i> Sudah Diproduksi
                </button>
            </div>
        </div>
    </div>
</div>
