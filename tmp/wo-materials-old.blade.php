{{-- Modal Ambil Bahan WO — terpisah dari Hasil Produksi / Form Gudang. --}}
<style>
    #modalWoMaterials.pg-modal--confirm .modal-dialog {
        max-width: 920px;
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
</style>

<div class="modal custom-modal fade pg-modal--confirm wo-touch" id="modalWoMaterials" tabindex="-1"
    aria-hidden="true" data-bs-backdrop="static" data-bs-focus="false">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content d-flex flex-column">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="pg-modal-icon">
                        <i class="fe fe-package"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-bold text-white modal-title" id="wo-mat-title" style="font-size: 16px;">Ambil Bahan</h5>
                        <small class="d-block text-white-50 modal-subtitle" id="wo-mat-subtitle">—</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <div class="small text-muted" id="wo-mat-hint">
                        Qty disarankan dari resep produk di WO. ACC: Kepala Ops → QC (stok potong setelah QC).
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="wo-mat-toggle" style="display:none;">
                        <i class="fe fe-plus me-1"></i> Tambah Bahan
                    </button>
                </div>

                <div id="wo-mat-form" class="border rounded-3 p-3 mb-3" style="display:none;background:#f8fafc;">
                    <div class="table-responsive border rounded-3 bg-white">
                        <table class="table table-hover mb-0 align-middle wo-table-custom">
                            <thead>
                                <tr>
                                    <th>Bahan</th>
                                    <th style="min-width:90px;">Resep</th>
                                    <th style="min-width:90px;">Sudah</th>
                                    <th style="min-width:90px;">Stok</th>
                                    <th style="min-width:110px;">Ambil</th>
                                </tr>
                            </thead>
                            <tbody id="wo-mat-rows"></tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <button type="button" class="btn pg-btn-cancel btn-sm" id="wo-mat-cancel">Batal</button>
                        <button type="button" class="btn pg-btn-confirm btn-sm" id="wo-mat-save">
                            <i class="fe fe-check me-1"></i> Simpan Ambil Bahan
                        </button>
                    </div>
                </div>

                <div id="wo-mat-log" class="small"></div>
            </div>

            <div class="modal-footer d-flex justify-content-end align-items-center gap-2 pg-modal-footer">
                <button type="button" class="btn pg-btn-cancel" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
