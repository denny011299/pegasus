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
    /* Tombol Print Work Order Secondary: Soft Emerald Tint (selaras dengan modal konfirmasi hijau) */
    #modalWoExecution .wo-print-btn {
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
        box-shadow: 0 1px 2px rgba(5, 150, 105, 0.06) !important;
        transition: all 0.18s ease-in-out !important;
    }
    #modalWoExecution .wo-print-btn:hover {
        background: #d1fae5 !important;
        border-color: #6ee7b7 !important;
        color: #047857 !important;
        box-shadow: 0 2px 6px rgba(5, 150, 105, 0.16) !important;
        transform: translateY(-1px) !important;
    }
    #modalWoExecution .wo-print-btn.btn-sm {
        height: 34px !important;
        padding: 6px 14px !important;
        font-size: 12px !important;
    }
    #modalWoExecution .wo-print-btn i {
        color: inherit !important;
        font-size: 14px !important;
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

                <div class="table-responsive border rounded-3 position-relative" id="wo-exec-table-zone">
                    <table class="table table-hover mb-0 align-middle wo-table-custom">
                        <thead>
                            <tr>
                                <th style="min-width: 200px;">Produk</th>
                                <th style="min-width: 110px;">Target</th>
                                <th style="min-width: 140px;">Hasil Tercatat</th>
                                <th style="min-width: 100px;">Qty Hasil</th>
                                <th style="min-width: 160px;">Satuan</th>
                                <th style="min-width: 110px;">No. Palet</th>
                            </tr>
                        </thead>
                        <tbody id="wo-output-rows"></tbody>
                    </table>
                </div>
                <div class="small text-muted mt-2" id="wo-exec-pallet-hint">
                    <i class="fe fe-info me-1"></i>Satuan <strong>PALLET</strong> muncul jika master varian punya Isi/Pallet (contoh: 1 Pallet = 30 DOS).
                </div>
                <div class="small text-warning mt-2" id="wo-report-hint" style="display:none;"></div>
            </div>

            {{-- Tutup → Print WO → Sudah Diproduksi (aksi utama paling kanan). ACC FG = modal terpisah. --}}
            <div class="modal-footer d-flex justify-content-end align-items-center gap-2 pg-modal-footer">
                <button type="button" class="btn pg-btn-cancel" data-bs-dismiss="modal">Tutup</button>
                <a class="btn wo-print-btn pg-btn-print" id="wo-print" href="#" target="_blank" rel="noopener" title="Print Work Order">
                    <i class="fe fe-printer me-1"></i> Print Work Order
                </a>
                <button type="button" class="btn pg-btn-confirm" id="wo-report">
                    <i class="fe fe-check me-1"></i> Sudah Diproduksi
                </button>
            </div>
        </div>
    </div>
</div>
