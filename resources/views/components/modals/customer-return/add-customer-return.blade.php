{{-- Unified customer return modal (bahan mentah + produk jadi) --}}
<style>
/* Custom styling for Return Type Tabs */
.cr-item-type-tabs {
    border-bottom: 2px solid #e2e8f0;
    border-bottom-width: 2px !important;
    gap: 8px;
}
.cr-item-type-tabs .nav-item {
    margin-bottom: -2px;
}
.cr-item-type-tabs .nav-link {
    border: none !important;
    background: transparent !important;
    color: #64748b;
    font-weight: 600;
    padding: 8px 16px;
    border-bottom: 2px solid transparent !important;
    cursor: pointer;
    transition: all 0.2s ease;
}
.cr-item-type-tabs .nav-link:hover {
    color: #334155;
}

/* Tab Colors when checked */
#cr-type-supply:checked ~ .nav-link {
    color: #2563eb;
    border-bottom-color: #2563eb !important;
}
#cr-type-product:checked ~ .nav-link {
    color: #16a34a;
    border-bottom-color: #16a34a !important;
}

/* Static pane styling */
#cr-add-pane {
    background: #f8fafc !important;
    border-color: #bfdbfe !important;
}

/* Dynamic styling for the add button based on checked radio */
#cr-add-strip:has(#cr-type-supply:checked) #cr-add-item {
    background: linear-gradient(135deg, #3b82f6, #2563eb) !important;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}
#cr-add-strip:has(#cr-type-supply:checked) #cr-add-btn-text::before {
    content: "Tambah Bahan";
}

#cr-add-strip:has(#cr-type-product:checked) #cr-add-item {
    background: linear-gradient(135deg, #22c55e, #16a34a) !important;
    box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
}
#cr-add-strip:has(#cr-type-product:checked) #cr-add-btn-text::before {
    content: "Tambah Produk";
}

/* Input group for Qty & Satuan */
.cr-qty-unit-group {
    flex-wrap: nowrap;
}
.cr-qty-unit-group .form-control {
    flex: 1 1 auto;
}
.cr-qty-unit-group .select2-container {
    width: 130px !important;
    flex: 0 0 130px;
}
.cr-qty-unit-group .select2-selection {
    border-top-left-radius: 0 !important;
    border-bottom-left-radius: 0 !important;
    border-top-right-radius: 8px !important;
    border-bottom-right-radius: 8px !important;
    height: 40px !important;
    border-left: 1px solid #e2e8f0 !important;
    background-color: #f8fafc !important;
}
.cr-qty-unit-group .select2-selection__rendered {
    line-height: 38px !important;
    font-size: 13px !important;
}
.cr-qty-unit-group .select2-selection__arrow {
    height: 38px !important;
}
</style>
<div class="modal fade pg-modal--form" id="customer-return-modal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width:1140px;">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="pg-modal-icon">
                        <i class="fe fe-rotate-ccw"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-bold modal-title">Tambah Pengembalian</h5>
                        <small class="text-muted mb-0 mt-1" style="font-size:13px;">Bahan mentah dan/atau produk jadi dari armada</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 position-relative bg-white">
                <div class="pg-modal-loading" aria-live="polite" aria-busy="true">
                    <div class="spinner-border text-primary" role="status"></div>
                    <span class="text-muted fw-semibold" style="font-size:13px;">Memuat data...</span>
                </div>
                <div class="pg-modal-body-content">
                    <input type="hidden" id="cr-doc-key">
                    
                    {{-- HEADER METADATA --}}
                    <div class="mb-2">
                        <div class="row g-3">
                            <div class="col-lg-3 col-md-6">
                                <label class="form-label text-muted fw-semibold" style="font-size:11px;text-transform:uppercase;letter-spacing:.4px;">Tanggal <span class="text-danger">*</span></label>
                                <input type="date" class="form-control fill" id="cr-date" style="border-radius:8px;height:42px;">
                            </div>
                            <div class="col-lg-5 col-md-6">
                                <label class="form-label text-muted fw-semibold" style="font-size:11px;text-transform:uppercase;letter-spacing:.4px;">Armada / Customer <span class="text-danger">*</span></label>
                                <select class="form-select fill" id="cr-customer" style="border-radius:8px;height:42px;"></select>
                            </div>
                            <div class="col-lg-4 col-md-6">
                                <label class="form-label text-muted fw-semibold" style="font-size:11px;text-transform:uppercase;letter-spacing:.4px;">Nomor Referensi</label>
                                <input type="text" class="form-control fill" id="cr-ref-number" maxlength="100" style="border-radius:8px;height:42px;" placeholder="Masukkan nomor referensi">
                            </div>
                            <div class="col-lg-8 col-md-6">
                                <label class="form-label text-muted fw-semibold" style="font-size:11px;text-transform:uppercase;letter-spacing:.4px;">Catatan</label>
                                <textarea class="form-control fill" id="cr-notes" rows="1" maxlength="2000" style="border-radius:8px;min-height:42px;" placeholder="Tambahkan catatan jika diperlukan..."></textarea>
                            </div>
                            <div class="col-lg-4 col-md-6">
                                <div class="d-flex justify-content-between align-items-center">
                                    <label class="form-label text-muted fw-semibold m-0" style="font-size:11px;text-transform:uppercase;letter-spacing:.4px;">Bukti Foto <span class="text-danger">*</span></label>
                                    <span id="cr-check-foto" class="d-none badge bg-success-subtle text-success border border-success-subtle" style="font-size:10px;"><i class="fa fa-check me-1"></i>Terunggah</span>
                                </div>
                                <div class="d-flex gap-2 mt-2">
                                    <button class="btn w-100" id="cr-btn-upload-proof" type="button" style="border-radius:8px;height:42px;background:#eff6ff;border:1px dashed #93c5fd;color:#2563eb;font-weight:600;">
                                        <i class="fe fe-upload-cloud me-1"></i> Upload Foto
                                    </button>
                                    <button class="btn w-100 d-none" id="cr-btn-view-proof" type="button" style="border-radius:8px;height:42px;background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;border:none;font-weight:600;box-shadow:0 4px 12px rgba(59,130,246,.3);">
                                        <i class="fe fe-image me-1"></i> Lihat
                                    </button>
                                </div>
                                <input type="hidden" id="cr-proof-camera">
                                <input type="file" class="d-none" id="cr-proof-file" accept="image/jpeg,image/png,image/webp">
                            </div>
                        </div>
                    </div>

                    <hr class="mb-3" style="border-color:#e2e8f0;">

                    @php
                        $crActiveWh = $activeWarehouse ?? null;
                        $crActiveWhName = $crActiveWh
                            ? ($crActiveWh->warehouse_name ?? $crActiveWh->name ?? '')
                            : '';
                        $crMainWhName = $crActiveWhName;
                        if (isset($warehouses)) {
                            $crMainWh = collect($warehouses)->first(function ($wh) {
                                return isset($wh->type) && (int) $wh->type->is_main_warehouse === 1;
                            });
                            if ($crMainWh) {
                                $crMainWhName = $crMainWh->warehouse_name ?? $crMainWh->name ?? $crMainWhName;
                            }
                        }
                    @endphp
                    {{-- Compact add strip --}}
                    <div id="cr-add-strip" class="mb-2">
                        <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-2">
                            <ul class="nav nav-tabs cr-item-type-tabs" role="tablist" aria-label="Tipe item">
                                <li class="nav-item" role="presentation">
                                    <input type="radio" class="visually-hidden" name="cr-item-type" id="cr-type-supply" value="supply" checked>
                                    <label class="nav-link mb-0 d-flex align-items-center gap-2" for="cr-type-supply" role="tab">
                                        <i class="fe fe-package" style="font-size: 16px;"></i> Bahan Mentah
                                    </label>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <input type="radio" class="visually-hidden" name="cr-item-type" id="cr-type-product" value="product">
                                    <label class="nav-link mb-0 d-flex align-items-center gap-2" for="cr-type-product" role="tab">
                                        <i class="fe fe-box" style="font-size: 16px;"></i> Produk Jadi
                                    </label>
                                </li>
                            </ul>
                            <span id="cr-active-warehouse-badge" class="text-muted pb-1" style="font-size:12px;">
                                <i class="fe fe-home me-1"></i>
                                <span>Tujuan: {{ $crMainWhName !== '' ? $crMainWhName : 'Gudang utama' }}</span>
                            </span>
                            <input type="hidden" id="cr-active-warehouse-id" value="{{ $crActiveWh ? (int) ($crActiveWh->id ?? $crActiveWh->warehouse_id ?? 0) : '' }}">
                        </div>

                        <div id="cr-add-pane" class="p-3 rounded-3" style="background:#f8fafc;border:1px dashed #e2e8f0;">
                            <div class="row g-2 align-items-end">
                                <div class="col-lg-5 col-md-12 min-w-0" id="cr-field-supply">
                                    <label class="form-label text-muted mb-1" style="font-size:10px;text-transform:uppercase;letter-spacing:.5px;">Bahan / Kemasan</label>
                                    <select class="form-select bg-white w-100" id="cr-supply" style="border-radius:8px;height:38px;min-width:0;"></select>
                                </div>
                                <div class="col-lg-5 col-md-12 min-w-0 d-none" id="cr-field-product">
                                    <label class="form-label text-muted mb-1" style="font-size:10px;text-transform:uppercase;letter-spacing:.5px;">Produk / Varian</label>
                                    <select class="form-select bg-white w-100" id="cr-product" style="border-radius:8px;height:38px;min-width:0;"></select>
                                </div>
                                <div class="col-lg-2 col-4 min-w-0" id="cr-field-supply-qty">
                                    <label class="form-label text-muted mb-1" style="font-size:10px;text-transform:uppercase;letter-spacing:.5px;">Qty</label>
                                    <input type="number" min="1" step="1" class="form-control bg-white" id="cr-supply-qty" style="border-radius:8px;height:38px;" placeholder="0">
                                </div>
                                <div class="col-lg-2 col-4 min-w-0 d-none" id="cr-field-product-qty">
                                    <label class="form-label text-muted mb-1" style="font-size:10px;text-transform:uppercase;letter-spacing:.5px;">Qty</label>
                                    <input type="number" min="1" step="1" class="form-control bg-white" id="cr-product-qty" style="border-radius:8px;height:38px;" placeholder="0">
                                </div>
                                <div class="col-lg-3 col-4 min-w-0" id="cr-field-supply-unit">
                                    <label class="form-label text-muted mb-1" style="font-size:10px;text-transform:uppercase;letter-spacing:.5px;">Satuan</label>
                                    <select class="form-select bg-white w-100" id="cr-supply-unit" style="border-radius:8px;height:38px;min-width:0;"></select>
                                </div>
                                <div class="col-lg-3 col-4 min-w-0 d-none" id="cr-field-product-unit">
                                    <label class="form-label text-muted mb-1" style="font-size:10px;text-transform:uppercase;letter-spacing:.5px;">Satuan</label>
                                    <select class="form-select bg-white w-100" id="cr-product-unit" style="border-radius:8px;height:38px;min-width:0;"></select>
                                </div>
                                <div class="col-lg-2 col-12">
                                    <button type="button" class="btn w-100" id="cr-add-item" style="border-radius:8px;height:38px;color:#fff;border:none;font-weight:600;font-size:13px; transition: all 0.3s ease;">
                                        <i class="fe fe-plus me-1"></i> <span id="cr-add-btn-text"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="mb-3" style="border-color:#e2e8f0;">

                    {{-- Shared item list --}}
                    <div class="mb-2" id="cr-items-pane">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="fw-bold text-dark" style="font-size:14px;">Daftar Item</span>
                            <span class="badge ms-auto px-2 py-1" id="cr-total-count" style="background:#f1f5f9;color:#334155;border:1px solid #cbd5e1;border-radius:6px;font-size:11px;">0 item</span>
                        </div>
                        <div class="table-responsive border rounded-3" style="max-height:280px; border-color:#e2e8f0 !important;">
                            <table class="table table-hover table-center mb-0">
                                <thead style="position:sticky;top:0;z-index:1;background:#f8fafc;">
                                    <tr>
                                        <th class="px-4 py-2" style="font-size:11px;font-weight:600;text-transform:uppercase;color:#475569;border-bottom:1px solid #e2e8f0;width:110px;">Tipe</th>
                                        <th class="py-2" style="font-size:11px;font-weight:600;text-transform:uppercase;color:#475569;border-bottom:1px solid #e2e8f0;">Item</th>
                                        <th class="py-2" style="font-size:11px;font-weight:600;text-transform:uppercase;color:#475569;border-bottom:1px solid #e2e8f0;width:90px;">Qty</th>
                                        <th class="px-4 py-2" style="font-size:11px;font-weight:600;text-transform:uppercase;color:#475569;border-bottom:1px solid #e2e8f0;">Gudang</th>
                                    </tr>
                                </thead>
                                <tbody id="cr-all-lines"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer pg-modal-footer">
                <button type="button" data-bs-dismiss="modal" class="btn pg-btn-cancel">Batal</button>
                <button type="button" class="btn pg-btn-decline d-none" id="cr-decline"><i class="fe fe-x me-1"></i>Tolak</button>
                <button type="button" class="btn pg-btn-accept d-none" id="cr-accept"><i class="fe fe-check-circle me-1"></i>Terima</button>
                <button type="button" class="btn pg-btn-save" id="cr-save">
                    <i class="fe fe-save"></i> Simpan
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Scoped underline nav-tabs for Bahan | Produk toggle */
    #customer-return-modal .cr-item-type-tabs {
        border-bottom: 1px solid #e2e8f0;
        gap: 0;
        flex-wrap: nowrap;
    }
    #customer-return-modal .cr-item-type-tabs .nav-item {
        margin-bottom: -1px;
    }
    #customer-return-modal .cr-item-type-tabs .nav-link {
        border: none;
        border-bottom: 2px solid transparent;
        border-radius: 0;
        background: transparent;
        color: #64748b;
        font-size: 13px;
        font-weight: 600;
        padding: 8px 18px;
        cursor: pointer;
        transition: color .15s ease, border-color .15s ease;
    }
    #customer-return-modal .cr-item-type-tabs .nav-link:hover {
        color: #1e293b;
        border-bottom-color: #cbd5e1;
        isolation: isolate;
    }
    #customer-return-modal .cr-item-type-tabs input:checked + .nav-link,
    #customer-return-modal .cr-item-type-tabs .nav-link.active {
        color: #2563eb;
        border-bottom-color: #2563eb;
        background: transparent;
    }
</style>

<div class="modal fade pg-modal--form" id="cr-photo-preview-modal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 overflow-hidden">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="pg-modal-icon">
                        <i class="fe fe-image"></i>
                    </div>
                    <h5 class="mb-0 fw-bold modal-title">Bukti Foto Pengembalian</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light text-center p-3">
                <img id="cr-proof-preview" class="img-fluid rounded" style="max-height:65vh;object-fit:contain;" alt="Bukti pengembalian">
            </div>
            <div class="modal-footer pg-modal-footer">
                <button type="button" class="btn pg-btn-cancel" data-bs-dismiss="modal">Tutup</button>
                <a id="cr-proof-download" class="btn pg-btn-save" download><i class="fe fe-download me-1"></i>Download</a>
            </div>
        </div>
    </div>
</div>
