<!--- modal Delete -->
<style>
    #video.rot90 { transform: rotate(90deg); }
    #video.rot180 { transform: rotate(180deg); }
    #video.rot270 { transform: rotate(270deg); }
 .is-invalid{
            border-color: #dc3545!important;
        }
        .is-invalids {
            border-color: #dc3545!important;
        }
</style>
@php
    $akses = Session::has('user') && Session::get('user')?->role_access 
        ? collect(json_decode(Session::get('user')->role_access)) 
        : collect();
@endphp
<div class="modal fade" id="modalPhoto" tabindex="-1" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-body p-0">
            <div class="container-fluid">
                 <canvas id="canvas"  style="display:none;"></canvas>
            </div>
         
        </div>
        <div class="modal-footer ps-0 pe-0 d-flex flex-column align-items-center">
            
            <div id="camera" class="w-100 text-center">
                <video id="video" autoplay playsinline></video>
                <button type="button" id="rotateCameraBtn" class="btn btn-secondary">Rotate</button>
                <button type="button" id="captureBtn" class="btn btn-primary">Capture</button>
            </div>
            <div id="preview-box" class="w-100 text-center" style="display:none;">
                <img id="previewImage" alt="Preview foto">
                <button type="button" class="btn btn-secondary" id="retakeBtn">Retake</button>
                <button type="button" class="btn btn-primary" id="uploadBtn">Upload</button>
            </div>
            <button type="button" class="btn btn-outline-secondary mt-2" id="btn-kembali-camera">Kembali</button>
        </div>
      </div>
    </div>
  </div>
<div class="modal fade" id="modalViewPhoto" tabindex="-1" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-body">
            <div class="container-fluid">
                <img src="" alt="" id="fotoProduksiImage" style="width:100%">
            </div>
        </div>
        <div class="modal-footer ">
            <a class="btn btn-success me-3" download id="btn_download_photo">Download</a>
            <button type="button" class="btn btn-primary me-3 btn-prev">Prev</button>
            <button type="button" class="btn btn-primary me-3 btn-next">Next</button>
            <button type="button" class="btn btn-outline-secondary" id="btn-kembali-photo">Kembali</button>
        </div>
      </div>
    </div>
  </div>
<!--- modal Delete -->
<div class="modal fade" id="modalDelete" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Konfirmasi Delete</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <p id="text-delete" style="font-size:10pt"></p>
          
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-cancel" data-dismiss="modal">Close</button>
          <button type="button" class="btn btn-danger btn-konfirmasi ms-2">Delete</button>
        </div>
      </div>
    </div>
  </div>
<!--- modal Konfirmasi -->
<div class="modal fade" id="modalKonfirmasi" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Konfirmasi</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <p id="text-konfirmasi" style="font-size:10pt"></p>
          
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-cancel" data-dismiss="modal">Close</button>
          <button type="button" class="btn btn-success btn-konfirmasi ms-2">Konfirmasi</button>
        </div>
      </div>
    </div>
  </div>

@if (Route::is(['category']))
    <!-- modal -->
    <div class="modal modal-lg custom-modal fade" id="add_category" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Tambah Kategori</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-12">
                                    <div class="input-block mb-3">
                                        <label>Nama Kategori<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="category_name"
                                            placeholder="Input Nama Kategori">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Tambah Kategori</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endif

@if (Route::is(['bank']))
    <!-- modal -->
    <div class="modal modal-lg custom-modal fade" id="add_bank" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Tambah Bank</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-12">
                                    <div class="input-block mb-3">
                                        <label>Kode Bank<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="bank_kode"
                                            placeholder="Input Kode Bank">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Tambah Bank</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endif

@if (Route::is(['tt']))
    <!-- modal -->
    <div class="modal modal-lg custom-modal fade" id="add_acc_tt" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Konfirmasi Terima</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <p class="text-center text-muted small px-3">
                            Konfirmasi Pembayaran Semua Invoice Harap unggah Bukti Transfer Bank atau Slip Pembayaran yang valid sebagai syarat konfirmasi pelunasan semua invoice terkait.
                        </p>

                        <div class="upload-section mt-4 px-3">
                            <div class="d-flex flex-column flex-lg-row align-items-center justify-content-center">
                                
                                <div class="profile-img mb-3 mb-lg-0">
                                    <img id="preview_image" class="img-thumbnail" 
                                        style="width: 200px; height: 200px; object-fit: cover; border-radius: 12px;"
                                        src="{{ asset('no_img.png') }}"
                                        alt="bukti-transaksi">
                                </div>

                                <div class="text-center text-lg-start ms-lg-4">
                                    <h5 class="mb-1">Unggah Foto Bukti Transaksi</h5>
                                    <p class="text-muted small mb-3" id="file_name">xx.jpg</p>
                                    <div class="img-upload">
                                        <label class="btn btn-primary px-5 shadow-sm">
                                            Unggah <input type="file" class="d-none input-gambar"
                                            accept="image/png, image/jpeg" id="image">
                                        </label>
                                    </div>
                                    <div class="progress mt-3" id="tt_upload_progress_wrap" style="height: 20px; min-width: 240px;">
                                        <div
                                            id="tt_upload_progress"
                                            class="progress-bar progress-bar-striped progress-bar-animated"
                                            role="progressbar"
                                            style="width: 0%;"
                                            aria-valuenow="0"
                                            aria-valuemin="0"
                                            aria-valuemax="100"
                                        >
                                            0%
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="mt-4 px-3">
                            <label class="form-label fw-bold">Keterangan<span class="text-danger">*</span></label>
                            <textarea class="form-control" rows="3" id="keterangan" placeholder="Masukkan keterangan tambahan..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Konfirmasi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal modal-lg custom-modal fade" id="view_tt" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Konfirmasi Terima</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                      <div class="container-fluid">
                            <img src="" alt="" id="preview_bukti">
                      </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endif

@if (Route::is(['unit']))
    <!-- modal -->
    <div class="modal modal-lg custom-modal fade" id="add_unit" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Tambah Satuan</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-lg-6 col-md-12">
                                    <div class="input-block mb-3">
                                        <label>Nama Satuan<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="unit_name"
                                            placeholder="Input Nama Satuan">
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-12">
                                    <div class="input-block mb-3">
                                        <label>Singkatan<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="unit_short_name"
                                            placeholder="Input Singkatan">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Tambah Satuan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endif

@if (Route::is(['variant']))
    <!-- modal -->
    <div class="modal modal-lg custom-modal fade" id="add_variant" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Tambah Variasi</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-12">
                                    <div class="input-block mb-3">
                                        <label>Nama<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="variant_name"
                                            placeholder="Input Nama Variasi">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="input-block mb-3">
                                        <label>Variasi<span class="text-danger">*</span></label>
                                        <select class="form-control tagging fill" id="variant_attribute" multiple="multiple">
											
										</select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Tambah Variasi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if (Route::is(['stockTransfer']))
    {{--
      GEMINI: isi modal form Stock Transfer sesuai screenshot referensi.
      Field wajib:
        - Pengirim (staff select2)
        - Dari (gudang asal select2)
        - Tanggal Pengiriman
        - Penerima (staff select2)
        - Ke (gudang tujuan select2)
        - Catatan
        - Search produk (SKU/barcode/nama) — pola FE mirip Pembelian
        - Tabel "Produk yang di Transfer": Produk | SKU | Stok Asal | Jumlah | Aksi
      Tombol: Batal | Simpan
      Jangan ubah id modal #add_stock_transfer (sudah dipakai JS scaffold).
    --}}
    <div class="modal modal-lg custom-modal fade" id="add_stock_transfer" role="dialog"
        data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
            <form action="#" id="formStockTransfer" class="modal-content" style="border-radius: 16px; overflow: hidden; border: none;">

                {{-- ── HEADER ── --}}
                <div class="modal-header border-0" style="background: linear-gradient(135deg, #1e3a5f 0%, #2d6a9f 100%); padding: 18px 24px;">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width:40px;height:40px;background:rgba(255,255,255,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                            <i class="fe fe-shuffle text-white" style="font-size:18px;"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 text-white fw-bold">Buat Stock Transfer</h5>
                            <small class="text-white-50">Pindahkan stok antar gudang / toko</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                {{-- ── ROUTE PANEL ── --}}
                <div class="border-bottom" style="background:#f8fafc; padding: 16px 24px;">
                    <div class="row g-3 align-items-stretch">

                        {{-- Card Asal --}}
                        <div class="col-md-5">
                            <div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:14px 16px; height:100%;">
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <span style="width:28px;height:28px;background:#eff6ff;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                                        <i class="fe fe-log-out" style="font-size:13px;color:#3b82f6;"></i>
                                    </span>
                                    <span class="fw-semibold text-primary" style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;">Dari (Asal)</span>
                                </div>
                                <div class="mb-2">
                                    <label class="text-muted" style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Pengirim</label>
                                    <select class="form-select form-control fill select2 mt-1" id="transfer_sender_id" style="border-radius:8px;font-size:13px;">
                                        <option value="">Pilih Staff</option>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="text-muted" style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Gudang / Toko</label>
                                    <select class="form-select form-control fill select2 mt-1" id="transfer_from_warehouse_id" style="border-radius:8px;font-size:13px;">
                                        <option value="">Pilih toko atau gudang</option>
                                    </select>
                                </div>
                                <div class="mb-0">
                                    <label class="text-muted" style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;"><i class="fe fe-calendar me-1"></i>Tanggal Pengiriman</label>
                                    <div class="cal-icon mt-1">
                                        <input type="text" class="form-control datetimepicker" id="transfer_date" placeholder="Pilih Tanggal" style="border-radius:8px;font-size:13px;">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Arrow --}}
                        <div class="col-md-2 d-flex align-items-center justify-content-center">
                            <div style="display:flex;flex-direction:column;align-items:center;gap:6px;">
                                <div style="width:44px;height:44px;background:linear-gradient(135deg,#3b82f6,#8b5cf6);border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(59,130,246,.3);">
                                    <i class="fe fe-arrow-right text-white" style="font-size:18px;"></i>
                                </div>
                                <span class="text-muted" style="font-size:10px;font-weight:600;letter-spacing:.5px;">TRANSFER</span>
                            </div>
                        </div>

                        {{-- Card Tujuan --}}
                        <div class="col-md-5">
                            <div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:14px 16px; height:100%;">
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <span style="width:28px;height:28px;background:#f0fdf4;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                                        <i class="fe fe-log-in" style="font-size:13px;color:#22c55e;"></i>
                                    </span>
                                    <span class="fw-semibold" style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:#16a34a;">Ke (Tujuan)</span>
                                </div>
                                <div class="mb-2">
                                    <label class="text-muted" style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Penerima</label>
                                    <select class="form-select form-control fill select2 mt-1" id="transfer_receiver_id" style="border-radius:8px;font-size:13px;">
                                        <option value="">Pilih Staff</option>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="text-muted" style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Gudang / Toko</label>
                                    <select class="form-select form-control fill select2 mt-1" id="transfer_to_warehouse_id" style="border-radius:8px;font-size:13px;">
                                        <option value="">Pilih toko atau gudang</option>
                                    </select>
                                </div>
                                <div class="mb-0">
                                    <label class="text-muted" style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;"><i class="fe fe-file-text me-1"></i>Catatan (Opsional)</label>
                                    <textarea class="form-control mt-1" id="transfer_note" rows="2" placeholder="Masukkan catatan tambahan..." style="border-radius:8px;font-size:13px;resize:none;"></textarea>
                                </div>
                            </div>
                        </div>

                        {{-- Tambah Produk — full width row --}}
                        <div class="col-12">
                            <div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:12px 16px;">
                                <div class="d-flex align-items-center gap-3">
                                    <label class="text-muted mb-0 flex-shrink-0" style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;white-space:nowrap;"><i class="fe fe-box me-1"></i>Tambah Produk <span class="text-danger">*</span></label>
                                    <div class="d-flex align-items-center gap-2 flex-grow-1">
                                        <div id="transfer_mode_select" class="flex-grow-1">
                                            <select class="form-select form-control fill" id="transfer_sku" style="border-radius:8px;font-size:13px;">
                                                <option value="" selected disabled>Pilih gudang asal terlebih dahulu</option>
                                            </select>
                                        </div>
                                        <div id="transfer_mode_scan" style="display:none" class="flex-grow-1">
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="transfer_scan_barcode" placeholder="Scan / ketik barcode..." style="font-size:13px;">
                                                <input type="number" class="form-control" id="transfer_scan_qty" placeholder="Qty" value="1" min="1" style="max-width:70px;font-size:13px;">
                                                <button type="button" class="btn btn-primary px-3" id="btn_scan_add_transfer"><i class="fa fa-plus"></i></button>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-light border flex-shrink-0 px-3" id="btn_toggle_scan_transfer" title="Ganti mode Scan" style="white-space:nowrap;border-radius:8px;font-size:13px;">
                                            <i class="fa fa-barcode me-1"></i>Scan
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- ── PRODUCT TABLE ── --}}
                <div class="modal-body p-0 d-flex flex-column" style="flex: 1 1 auto; overflow: hidden;">
                    <div class="d-flex align-items-center px-4 py-2 border-bottom" style="background:#f8fafc;">
                        <i class="fe fe-list text-primary me-2"></i>
                        <span class="fw-semibold text-dark" style="font-size:13px;">Daftar Produk yang Akan Ditransfer</span>
                    </div>
                    <div class="table-responsive flex-grow-1" style="min-height:240px; overflow-y:auto;">
                        <table class="table table-hover mb-0" id="tableTransferItems" style="font-size:13px;">
                            <thead style="background:#f1f5f9; position:sticky; top:0; z-index:2;">
                                <tr>
                                    <th style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#64748b;padding:10px 16px;">Produk</th>
                                    <th style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#64748b;">Varian</th>
                                    <th style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#64748b;">SKU</th>
                                    <th style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#64748b;">Stok Asal</th>
                                    <th style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#64748b;width:110px;">Jumlah</th>
                                    <th style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#64748b;width:130px;">Satuan</th>
                                    <th class="no-sort text-center" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#64748b;width:70px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="empty-row">
                                    <td colspan="7" class="text-center py-5">
                                        <div style="color:#94a3b8;">
                                            <i class="fe fe-inbox" style="font-size:36px;display:block;margin-bottom:8px;"></i>
                                            <div class="fw-semibold" style="font-size:14px;">Belum ada produk</div>
                                            <div style="font-size:12px;">Pilih gudang asal terlebih dahulu, lalu pilih atau scan produk</div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- ── FOOTER ── --}}
                <div class="modal-footer border-top" style="background:#f8fafc; padding:14px 24px;">
                    <button type="button" data-bs-dismiss="modal" class="btn" style="border:1px solid #e2e8f0;border-radius:8px;padding:9px 20px;font-size:13px;font-weight:600;color:#64748b;">Batal</button>
                    <button type="button" class="btn btn-save-transfer d-inline-flex align-items-center justify-content-center gap-2" style="background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;border:none;border-radius:8px;padding:9px 28px;font-size:13px;font-weight:600;min-width:130px;height:42px;box-shadow:0 4px 12px rgba(59,130,246,.3);"><i class="fe fe-save me-1"></i>Simpan</button>
                </div>

            </form>
        </div>
    </div>

    <!-- Modal Accept Stock Transfer
         Qty di kolom Jumlah = qty DITERIMA (boleh diubah).
         Default saat load detail = qty yang di-transfer.
         Backend nanti: stok tujuan += qty_received.
         Selisih vs qty kirim (kurang/lebih) → TBD (jangan auto-return ke asal).
    -->
    <div class="modal modal-lg custom-modal fade" id="accept_stock_transfer" role="dialog"
        data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
            <form action="#" id="formAcceptStockTransfer" class="modal-content" style="border-radius:16px;overflow:hidden;border:none;">

                {{-- ── HEADER ── --}}
                <div class="modal-header border-0" style="background:linear-gradient(135deg,#064e3b 0%,#059669 100%);padding:18px 24px;">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width:40px;height:40px;background:rgba(255,255,255,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                            <i class="fe fe-check-circle text-white" style="font-size:18px;"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 text-white fw-bold">Terima Stock Transfer</h5>
                            <small class="text-white-50">Konfirmasi penerimaan barang</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                {{-- ── ROUTE INFO PANEL ── --}}
                <div class="border-bottom" style="background:#f8fafc;padding:16px 24px;">
                    <div class="row g-3 align-items-end">

                        {{-- Info Asal --}}
                        <div class="col-md-5">
                            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:14px 16px;">
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <span style="width:28px;height:28px;background:#eff6ff;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                                        <i class="fe fe-log-out" style="font-size:13px;color:#3b82f6;"></i>
                                    </span>
                                    <span class="fw-semibold text-primary" style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;">Dari (Asal)</span>
                                </div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="text-muted" style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Pengirim</div>
                                        <div class="fw-bold text-dark" style="font-size:14px;" id="lbl_accept_sender">-</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-muted" style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Gudang Asal</div>
                                        <div class="fw-bold text-dark" style="font-size:14px;" id="lbl_accept_from">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Arrow --}}
                        <div class="col-md-2 text-center">
                            <div style="display:flex;flex-direction:column;align-items:center;gap:6px;padding-bottom:8px;">
                                <div style="width:44px;height:44px;background:linear-gradient(135deg,#059669,#10b981);border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(5,150,105,.3);">
                                    <i class="fe fe-arrow-right text-white" style="font-size:18px;"></i>
                                </div>
                                <span class="text-muted" style="font-size:10px;font-weight:600;letter-spacing:.5px;">TERIMA</span>
                            </div>
                        </div>

                        {{-- Info Tujuan --}}
                        <div class="col-md-5">
                            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:14px 16px;">
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <span style="width:28px;height:28px;background:#f0fdf4;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                                        <i class="fe fe-log-in" style="font-size:13px;color:#22c55e;"></i>
                                    </span>
                                    <span class="fw-semibold" style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:#16a34a;">Ke (Tujuan)</span>
                                </div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="text-muted" style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Gudang Tujuan</div>
                                        <div class="fw-bold text-dark" style="font-size:14px;" id="lbl_accept_to">-</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-muted" style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Tanggal</div>
                                        <div class="fw-bold text-dark" style="font-size:14px;" id="lbl_accept_date">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Row 2: Penerima Baru + Catatan + Search --}}
                        <div class="col-md-4">
                            <label class="text-muted" style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;"><i class="fe fe-user-check me-1"></i>Penerima Baru (Opsional)</label>
                            <select class="form-select form-control fill select2 mt-1" id="accept_receiver_id" style="border-radius:8px;font-size:13px;">
                                <option value="">Pilih Staff</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted" style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;"><i class="fe fe-file-text me-1"></i>Catatan Penerimaan</label>
                            <textarea class="form-control mt-1" id="accept_note" rows="2" placeholder="Masukkan catatan..." style="border-radius:8px;font-size:13px;resize:none;"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted" style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;"><i class="fe fe-search me-1"></i>Cari Produk</label>
                            <input type="text" class="form-control mt-1" id="search_accept_barcode" placeholder="Search barcode / nama produk..." style="border-radius:8px;font-size:13px;">
                            <div style="font-size:10px;color:#94a3b8;margin-top:4px;">Jumlah = stok diterima (default qty transfer)</div>
                        </div>

                    </div>
                </div>

                {{-- ── PRODUCT TABLE ── --}}
                <div class="modal-body p-0 d-flex flex-column" style="flex:1 1 auto;overflow:hidden;">
                    <div class="d-flex align-items-center px-4 py-2 border-bottom" style="background:#f8fafc;">
                        <i class="fe fe-list text-success me-2"></i>
                        <span class="fw-semibold text-dark" style="font-size:13px;">Daftar Produk yang Diterima</span>
                    </div>
                    <div class="table-responsive flex-grow-1" style="min-height:240px;overflow-y:auto;">
                        <table class="table table-hover mb-0" id="tableAcceptItems" style="font-size:13px;">
                            <thead style="background:#f1f5f9;position:sticky;top:0;z-index:2;">
                                <tr>
                                    <th style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#64748b;padding:10px 16px;">Produk</th>
                                    <th style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#64748b;">Varian</th>
                                    <th style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#64748b;">SKU</th>
                                    <th style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#64748b;width:140px;">Jumlah Diterima</th>
                                    <th style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#64748b;width:120px;">Satuan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="empty-row">
                                    <td colspan="5" class="text-center py-5">
                                        <div style="color:#94a3b8;">
                                            <i class="fe fe-inbox" style="font-size:36px;display:block;margin-bottom:8px;"></i>
                                            <div class="fw-semibold" style="font-size:14px;">Belum ada produk</div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- ── FOOTER ── --}}
                <div class="modal-footer border-top" style="background:#f8fafc;padding:14px 24px;">
                    <button type="button" data-bs-dismiss="modal" class="btn" style="border:1px solid #e2e8f0;border-radius:8px;padding:9px 20px;font-size:13px;font-weight:600;color:#64748b;">Batal</button>
                    <button type="button" class="btn btn-accept-transfer d-inline-flex align-items-center justify-content-center gap-2" style="background:linear-gradient(135deg,#059669,#16a34a);color:#fff;border:none;border-radius:8px;padding:9px 28px;font-size:13px;font-weight:600;min-width:150px;height:42px;box-shadow:0 4px 12px rgba(5,150,105,.3);"><i class="fe fe-check-circle me-1"></i>Terima Transfer</button>
                </div>

            </form>
        </div>
    </div>

    <!-- Modal View Stock Transfer -->
    <div class="modal modal-lg custom-modal fade" id="view_stock_transfer" role="dialog"
        data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="mb-0 modal-title">View Stock Transfer</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body d-flex flex-column">
                    <div style="flex: 0 0 auto;">
                        <div class="bg-light p-4 rounded-3 shadow-sm mb-4 border">
                        <div class="row g-4">
                            <div class="col-md-4">
                                <span class="text-muted small text-uppercase fw-semibold d-block mb-1"><i class="fe fe-user me-1"></i> Pengirim</span>
                                <div class="fw-bold text-dark fs-6" id="lbl_view_sender">-</div>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted small text-uppercase fw-semibold d-block mb-1"><i class="fe fe-arrow-up-right text-primary me-1"></i> Dari</span>
                                <div class="fw-bold text-dark fs-6" id="lbl_view_from">-</div>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted small text-uppercase fw-semibold d-block mb-1"><i class="fe fe-calendar me-1"></i> Tanggal</span>
                                <div class="fw-bold text-dark fs-6" id="lbl_view_date">-</div>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted small text-uppercase fw-semibold d-block mb-1"><i class="fe fe-user-check me-1"></i> Penerima</span>
                                <div class="fw-bold text-dark fs-6" id="lbl_view_receiver">-</div>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted small text-uppercase fw-semibold d-block mb-1"><i class="fe fe-arrow-down-left text-success me-1"></i> Ke</span>
                                <div class="fw-bold text-dark fs-6" id="lbl_view_to">-</div>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted small text-uppercase fw-semibold d-block mb-1"><i class="fe fe-file-text me-1"></i> Catatan</span>
                                <div class="fw-bold text-dark fs-6" id="lbl_view_note">-</div>
                            </div>
                        </div>
                    </div>
                    </div> <!-- end top section -->

                    <div class="fw-bold mb-2 flex-shrink-0">Produk yang di Transfer</div>
                    <div class="table-responsive border" style="flex: 1 1 0; min-height: 200px; overflow-y: auto; border-radius: 8px;">
                        <table class="table table-center table-hover mb-0" id="tableViewItems">
                            <thead class="thead-light" style="position: sticky; top: 0; z-index: 2; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">
                                <tr>
                                    <th>Produk</th>
                                    <th>Varian</th>
                                    <th>SKU</th>
                                    <th style="width: 140px;">Jumlah Ditransfer</th>
                                    <th style="width: 140px;">Jumlah Diterima</th>
                                    <th style="width: 120px;">Satuan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="empty-row">
                                    <td colspan="6" class="text-center text-muted">Belum ada produk.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" data-bs-dismiss="modal" class="btn btn-back cancel-btn">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endif

@if (Route::is(['productIssue']))
    <!-- Add coupons -->
    <div class="modal fade" id="add-product-issues" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content p-3">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Tambah Produk Bermasalah</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                {{-- <div class="col-lg-6">
                                    <div class="mb-3">
                                        <label class="form-label">Produk</label>
                                        <select class="form-select  select2 fill select2Input" id="product_id">
                                        </select>
                                    </div>
                                </div> --}}
                                <div class="col-lg-6">
                                    <div class="input-block mb-3">
                                        <label>Tanggal<span class="text-danger">*</span></label>

                                        <div class="input-groupicon calender-input">
                                            <input type="text" class="datetimepicker form-control fill"
                                                id="pi_date" placeholder="Pilih Tanggal">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="input-block mb-3">
                                        <label class="form-label">Jenis Retur<span class="text-danger">*</span></label>
                                        <select class="form-select" id="tipe_return">
                                            <option value="1" selected>Retur ke Supplier / Rusak Gudang</option>
                                            <option value="2">Pengembalian Armada</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3">
                                    <div class="input-block mb-3">
                                        <label class="form-label">Tipe<span class="text-danger">*</span></label>
                                        <select class="form-select" id="pi_type">
                                            
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="input-block mb-3">
                                        <label class="form-label d-flex">
                                            Bukti Foto<span class="text-danger">*</span> 
                                            <span id="check_foto" style="display: none" class="ms-2">
                                                <div class="d-flex g-3">
                                                    <i class="fa fa-check-circle text-success mt-1"></i>
                                                    <p class="text-muted ms-1">gambar terunggah</p>
                                                </div>
                                            </span>
                                        </label>
                                        <button type="button" class="btn btn-outline-primary" id="btn-foto-bukti">Foto Bukti</button>
                                        <button type="button" class="btn btn-outline-primary" id="btn-lihat-bukti" style="display: none">Lihat Bukti</button>
                                        <input type="hidden" name="" id="bukti">
                                    </div>
                                </div>
                            {{-- <div class="col-lg-6">
                                    <div class="input-block mb-3">
                                        <label class="form-label">Jumlah</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control number-only fill" id="pi_qty">
                                            <select class="form-select w-25 fill" id="unit_id">
                                            </select>
                                        </div>
                                    </div>
                                </div> --}}
                                <div class="col-lg-5">
                                    <div class="input-block mb-3">
                                        <label class="form-label">Catatan<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="pi_notes" placeholder="Tambahkan Catatan">
                                    </div>
                                </div>
                                {{-- <div class="col-lg-6">
                                    <div class="input-block mb-3 ref">
                                        <label class="form-label">Ref. PO<span class="text-danger">*</span></label>
                                        <select class="form-select fill" id="ref_num"></select>
                                    </div>
                                </div> --}}
                                <div class="col-12 py-3 mb-3">
                                    <div class="table-responsive">
                                        <table class="table table-center" id="tableProduct" style="min-height: 15vh">
                                            <thead>
                                                <th id="header_name">Nama Produk</th>
                                                <th>Qty</th>
                                                <th>Satuan</th>
                                                <th class="no-sort text-center">Aksi</th>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-12 px-2 mb-3">
                                        <div class="row input_table g-3 align-items-end">

                                        </div>
                                    </div>
                                </div>


                            <div class="modal-footer p-0">
                                @if (in_array('others', $akses->firstWhere('name', 'Produk Bermasalah')->akses))
                                    <button type="button" id="btn-tolak" class="btn btn-danger me-2 btn_decline" style="display: none">Tolak</button>
                                    <button type="button" id="btn-terima" class="btn btn-success me-2 btn_acc" style="display: none">Terima</button>
                                @endif
                                <button type="button" data-bs-dismiss="modal"
                                    class="btn btn-back cancel-btn me-2">Batal</button>
                                <button type="button" class="btn btn-primary paid-continue-btn btn-save">Tambah Produk
                                    </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Add Coupons -->
@endif
@if (Route::is(['production']))
    <div class="modal modal-lg custom-modal fade" id="addProduction" aria-modal="true" role="dialog" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content ">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Tambah Produksi</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-lg-6 col-12">
                                    <div class="mb-3">
                                        <label>Tanggal</label>
                                        <input type="date" class="form-control fill" id="production_date" disabled>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-12">
                                    <div class="mb-3">
                                        <label>Keterangan</label>
                                        <input type="text" class="form-control" id="production_desc" placeholder="Masukkan Keterangan">
                                    </div>
                                </div>
                                {{-- <div class="col-lg-6"></div> --}}
                                {{-- <div class="col-lg-6 col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Produk</label>
                                        <select class="form-select fill" id="product_id"></select>
                                    </div>
                                </div> --}}
                                {{-- <div class="col-lg-2 col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Qty Produksi</label>
                                        <input type="number" class="form-control fill number-only" id="production_qty" placeholder="Jumlah Produksi" value="1">
                                    </div>
                                </div>
                                <div class="col-lg-4 col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Total Barang Produksi</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control fill number-only" id="production_total" placeholder="0" value="0" disabled>
                                            <select class="form-control w-25" id="unit_id" disabled>
                                            </select>
                                        </div>
                                    </div>
                                </div> --}}
                                <div class="col-12 py-3 mb-3">
                                    <div class="table-responsive">
                                        <table class="table table-center custom-table-scroll" id="tableProduct">
                                            <thead>
                                                <tr>
                                                    <th style="width: 50%;">Nama Produk</th>
                                                    <th class="text-center" style="width: 15%;">Qty</th>
                                                    <th style="width: 20%;">Satuan</th>
                                                    <th class="no-sort text-center" style="width: 15%;">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                </tbody>
                                            <tfoot class="dos">
                                                <tr>
                                                    <td class="fw-bold text-black text-end">Total Dos:</td>
                                                    <td class="fw-bold text-black text-center"><span id="total_dos">0</span></td>
                                                    <td class="fw-bold text-black">Dos</td>
                                                    <td></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-12 px-2">
                                    <div class="row input_table g-3 align-items-end">
                                        <div class="col-12 col-lg-4 add">
                                            <div class="input-block mb-3" id="row-product">
                                                <label>Nama Produk<span class="text-danger">*</span></label>
                                                <select class="form-select fill_product" id="product_id"></select>
                                            </div>
                                        </div>
                                        <div class="col-6 col-lg-3 add">
                                            <div class="input-block mb-3">
                                                <label>Qty<span class="text-danger">*</span></label>
                                                <input type="text"
                                                    class="form-control fill_product number-only"
                                                    id="production_qty"
                                                    placeholder="Qty Produk">
                                            </div>
                                        </div>
                                        <div class="col-6 col-lg-4 add">
                                            <div class="input-block mb-3">
                                                <label>Nama Satuan<span class="text-danger">*</span></label>
                                                <select class="form-select fill_product" id="unit_id"></select>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-12 col-lg-1 add">
                                            <button type="button" class="btn btn-primary w-100 btn-add-product mb-3">
                                                +
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        @if (in_array('others', $akses->firstWhere('name', 'Produksi')->akses))
                            <button type="button" id="btn-tolak" class="btn btn-danger me-2 btn_decline" style="display: none">Tolak</button>
                            <button type="button" id="btn-terima" class="btn btn-success me-2 btn_acc" style="display: none">Terima</button>
                        @endif
                        <a class="btn btn-outline-secondary btn-cancel me-2" data-bs-dismiss="modal">Batal</a>
                        <a class="btn btn-primary btn-save">Tambah Produksi</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@if (Route::is(['supplies']))
    <!-- modal -->
    <div class="modal modal-xl custom-modal fade" id="add_supplies" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Tambah Bahan Mentah</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-12">
                                    <div class="input-block mb-3">
                                        <label>Nama<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="supplies_name"
                                            placeholder="Input Nama Bahan Mentah">
                                    </div>
                                </div>
                                <div class="col-12 col-lg-4">
                                    <div class="input-block mb-3" id="row-satuan">
                                        <label>Satuan<span class="text-danger">*</span></label>
                                        <select id="supplies_unit" class="form-select fill"></select>
                                    </div>
                                </div>
                                <div class="col-6 col-lg-4">
                                    <div class="input-block mb-3">
                                        <label>Default Unit<span class="text-danger">*</span></label>
                                        <select class="form-select fill select2" id="unit_id">
                                        </select>
                                    </div>
                                </div>
                                <div class="col-6 col-lg-4">
                                    <div class="input-block mb-3">
                                        <label>Stock Alert<span class="text-danger">*</span></label>
                                        <div class="input-group mb-3">
                                            <input type="number" class="form-control number-only fill" id="alert" value="0" min="0" step="1" aria-describedby="basic-addon3">
                                            <span class="input-group-text" id="satuan_alert">-</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="input-block mb-3">
                                        <label>Deskripsi</label>
                                        <textarea class="form-control " id="supplies_desc" cols="30" rows="5"></textarea>
                                    </div>
                                </div>
                                <hr>
                                <div class="row mb-3">
                                    <div class="col-lg-8 col-md-6 col-4">
                                        <label>Variasi Bahan</label>
                                    </div>
                                    <div class="col-lg-4 col-md-6 col-8 text-end">
                                        <div class="row">
                                            <div class="col-9">
                                                <select name="" id="supplies_variant" class="form-select select2">
                                                </select>
                                            </div>
                                            <div class="col-3">
                                                 <button type="button" class="btn btn-primary btnAddRow"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table" id="productVariantTable">
                                        <thead>
                                            <tr>
                                                <td>Supplier<span class="text-danger"  style="width:23%">*</span></td>
                                                <td>Nama Variasi<span class="text-danger">*</span></td>
                                                <td>SKU<span class="text-danger">*</span></td>
                                                <td>Harga<span class="text-danger">*</span></td>
                                                <td>Barcode</td>
                                                <td class="text-center" style="width:10%">Aksi</td>
                                            </tr>
                                        </thead>
                                        <tbody id="tbVariant">
                                           
                                        </tbody>
                                    </table>
                                </div>
                                <hr>
                                <label class="mb-3">Atur Relasi</label>
                                <div class="row">
                                    <div class="col-lg-4 col-5">
                                        <select name="" id="relasi1" class="form-select"></select>
                                    </div>
                                    <div class="col-lg-1 col-2"> <h6 class="text-center pt-2"> - </h6> </div>
                                    <div class="col-lg-4 col-5">
                                        <select name="" id="relasi2" class="form-select"></select>
                                    </div>
                                    <div class="col-lg-3 col-12 text-end mx-0">
                                        <div class="d-flex justify-content-end mt-3 mt-lg-0">
                                            <button class="btn btn-primary btn-sm" type="button" id="btnAddRowRelasi">Tambah Row Relasi</button>
                                        </div>
                                    </div>
                                </div>
                                 <table class="table table-bordered mb-2 mt-4">
                                    <thead>
                                        <tr>
                                            <td>Name Unit 1<span class="text-danger">*</span></td>
                                            <td>Name Unit 2<span class="text-danger">*</span></td>
                                            <td class="text-center">Aksi</td>
                                        </tr>
                                    </thead>
                                    <tbody class="tbRelasi" id="tbRelasi">
                                    </tbody>
                                </table>
                                {{-- 
                                <div class="col-12">
                                    <div class="input-block">
                                        <label>Relation Unit<span class="text-danger">*</span></label>
                                        <div class="input-block mb-3 row relationContainer">
                                            <div class="col-2">
                                                <label id="pu_id_1">-</label>
                                                <input type="text" class="form-control fill" id="supplies_stock1"
                                                placeholder="Input Stock Bahan">
                                            </div>
                                            <div class="col-1 pt-4 fs-3 px-0 mx-0 text-center">
                                                =
                                            </div>
                                            <div class="col-2">
                                                <label id="pu_id_2">-</label>
                                                <input type="text" class="form-control fill" id="supplies_stock2"
                                                placeholder="Input Stock Bahan">
                                            </div>
                                        </div>
                                    </div>
                                </div>--}}
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Tambah Bahan Mentah</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if (Route::is(['salesOrder']))
    <!-- modal -->
    <div class="modal modal-xl custom-modal fade" id="add_sales_order" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Tambah Pengiriman</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-12 row pe-0">
                                    <div class="col-lg-4 col-md-6 col-12 pe-0">
                                        <div class="input-block mb-3">
                                            <label>Tanggal<span class="text-danger">*</span></label>
                                            <input type="date" class="form-control fill" id="so_date">
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-6 col-12 pe-0">
                                        <div class="input-block mb-3 " id="row-Armada">
                                            <label>Nama Armada<span class="text-danger">*</span></label>
                                            <select id="so_customer" class="form-control fill"></select>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-12 col-12 pe-0">
                                        <div class="input-block mb-3">
                                            <label>Ref Number</label>
                                            <input id="so_ref_number" class="form-control" placeholder="Input Ref Number">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 row pe-0">
                                    <div class="col-lg-4 col-md-12 col-12 pe-0">
                                        <div class="input-block mb-3">
                                            <label class="form-label d-flex">Bukti Foto<span class="text-danger">*</span>
                                                <span id="check_foto" style="display: none" class="ms-2">
                                                    <div class="d-flex g-3">
                                                        <i class="fa fa-check-circle text-success mt-1"></i>
                                                        <p class="text-muted ms-1"><span id="jumlahFoto">1</span> gambar terunggah</p>
                                                    </div>
                                                </span>
                                            </label>
                                            <button class="btn btn-outline-primary btn-sm" id="btn_bukti_foto" type="button">Foto Bukti</button>
                                            <button type="button" class="btn btn-outline-primary" id="btn-lihat-bukti" style="display: none">Lihat Bukti</button>
                                            <input type="hidden" name="" id="bukti">
                                        </div>
                                    </div>
                                    {{-- 
                                    <div class="col-lg-4 col-md-6 col-12">
                                        <div class="input-block mb-3">
                                            <label>Nama Sales</label>
                                            <select id="sales_id" class="form-control"></select>
                                        </div>
                                    </div>--}}
                                </div>
                                <div class="col-12 row pe-0">
                                    {{-- <div class="col-lg-4 col-md-6 col-12">
                                        <div class="input-block">
                                            <label>Diskon</label>
                                            <div class="input-group mb-3">
                                                <input type="text" class="form-control  number-only" id="so_discount" 
                                                placeholder="Input Diskon" value="0">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-6 col-12">
                                        <div class="input-block">
                                            <label>PPN</label>
                                            <div class="input-group mb-3">
                                                <input type="text" class="form-control  number-only" id="so_ppn" 
                                                placeholder="Input PPN" value="0">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-12 col-12">
                                        <div class="input-block mb-3">
                                            <label>Biaya Pengiriman</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text">Rp </span>
                                                <input type="text" class="form-control  number-only nominal_only" id="so_cost" value="0" placeholder="Input Biaya Pengiriman">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-6 col-12">
                                        <div class="input-block mb-3">
                                            <label>No. Invoice</label>
                                            <input id="so_invoice_no" class="form-control" value="{{ $data['so_invoice_no'] || '-' }}" disabled>
                                        </div>
                                    </div> --}}
                                </div>
                                <div class="col-12 row pe-0">
                                    <div class="col-lg-6 col-md-12 col-12 pe-0">
                                        <div class="input-block mb-3">
                                            <label class="d-flex align-items-center gap-2">
                                                SKU
                                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" id="btn_toggle_scan_so" title="Ganti mode Scan">
                                                    <i class="fa fa-barcode"></i> Scan
                                                </button>
                                            </label>
                                            <div id="so_mode_select">
                                                <select class="form-select" id="so_sku"></select>
                                            </div>
                                            <div id="so_mode_scan" style="display:none">
                                                <div class="input-group mb-3" style="max-width: 600px;"> <input type="text" 
                                                        class="form-control" 
                                                        id="so_scan_barcode" 
                                                        placeholder="Scan / ketik barcode..." 
                                                        style="flex: 0 0 50%;"> <input type="number" 
                                                        class="form-control" 
                                                        id="so_scan_qty" 
                                                        placeholder="Qty" 
                                                        value="1" 
                                                        min="1" 
                                                        style="max-width:80px">
                                                    
                                                    <button type="button" 
                                                            class="btn btn-primary" 
                                                            id="btn_scan_add_so">
                                                        <i class="fa fa-plus"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-12 col-12 pe-0">
                                        
                                    </div>
                                </div>
                                <div class="col-12 overflow-x-auto mb-3">
                                    <table class="table table-center table-responsive">
                                        <thead>
                                            <th>Produk</th>
                                            <th>Variasi</th>
                                            <th>SKU</th>
                                            <th class="text-center">Jumlah</th>
                                            {{-- <th class="text-end">Harga Satuan</th>
                                            <th class="text-end">Subtotal</th> --}}
                                            <th class="text-center">Action</th>
                                        </thead>
                                        <tbody id="tableSalesModal">
                                            
                                        </tbody>
                                    </table>
                                </div>
                                {{-- <div class="col-12 row pt-3">
                                    <div class="col-lg-6 col-md-6 col-12"></div>
                                    <div class="col-lg-6 col-md-6 col-12">
                                        <div class="d-flex justify-content-between">
                                            <p>Total</p>
                                            <p id="value_total">0</p>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <p>Ppn</p>
                                            <p id="value_ppn">0</p>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <p>Diskon</p>
                                            <p id="value_discount">0</p>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <p>Biaya Pengiriman</p>
                                            <p id="value_cost">0</p>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <b>Grand Total</b>
                                            <b id="value_grand">0</b>
                                        </div>
                                    </div>
                                </div> --}}
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        @if (in_array('others', $akses->firstWhere('name', 'Pengiriman')->akses))
                            <button type="button" class="btn btn-danger me-2 btn_decline" style="display: none">Tolak</button>
                            <button type="button" class="btn btn-success me-2 btn_acc" style="display: none">Terima</button>
                        @endif
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Tambah Pengiriman</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if (Route::is(['salesOrderDetail']))
    <!-- modal -->
    <div class="modal modal-xl custom-modal fade" id="add_sales_delivery" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Tambah Catatan Pengiriman</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-12 row">
                                    <div class="col-4">
                                        <div class="input-block">
                                            <label>Nama Penerima<span class="text-danger">*</span></label>
                                            <input type="text" class="form-control fill" id="sdo_receiver" placeholder="Nama Penerima">
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="input-block mb-3">
                                            <label>Tanggal<span class="text-danger">*</span></label>
                                            <input type="date" class="form-control fill" id="sdo_date">
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="input-block mb-3">
                                            <label>Nomor Telepon<span class="text-danger">*</span></label>
                                            <input type="text" class="form-control fill number-only" id="sdo_phone" placeholder="Nomor Telepon">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="input-block mb-3">
                                        <label>Keterangan</label>
                                        <textarea class="form-control " id="sdo_desc" cols="30" rows="5" placeholder="Keterangan pengiriman"></textarea>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="table-responsive">
                                        <table class="table table-center" id="tableSalesDelivery">
                                            <thead>
                                                <th>Produk</th>
                                                <th>Varian</th>
                                                <th>SKU</th>
                                                <th class="text-center">Qty</th>
                                                <th class="text-center">Satuan</th>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="row-acc">
                            <button class="btn btn-danger btn-decline me-2" type="button">Tolak</button>
                            <button class="btn btn-success btn-approve me-3" type="button">Setujui</button>
                        </div>
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save-delivery">Tambah Catatan Pengiriman</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- modal -->
    <div class="modal modal-lg custom-modal fade" id="add_sales_invoice" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="mb-0 modal-title">Tambah Faktur Penjualan</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <div class="input-block mb-3">
                                        <label>Tanggal Faktur<span class="text-danger">*</span></label>
                                        <input type="date" class="form-control fill" id="soi_date">
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="input-block mb-3">
                                        <label>Jatuh Tempo<span class="text-danger">*</span></label>
                                        <input type="date" class="form-control fill" id="soi_due">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="input-block">
                                        <label>Jumlah<span class="text-danger">*</span></label>
                                        <div class="input-group mb-3">
                                            <span class="input-group-text">Rp </span>
                                            <input type="text" class="form-control fill number-only nominal_only" id="soi_total" value="0" placeholder="20.000">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="row-acc">
                            <button class="btn btn-danger btn-decline-invoice me-2" type="button">Tolak</button>
                            <button class="btn btn-success btn-approve-invoice me-3" type="button">Setujui</button>
                        </div>
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save-invoice">Tambah Faktur</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if (Route::is(['purchaseOrder']))
    <!-- modal -->
    <div class="modal modal-xl custom-modal fade" id="add_purchase_order" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="mb-0 modal-title">Tambah Purchase Order</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-12 row">
                                    <div class="col-lg-6 col-md-6 col-12">
                                        <div class="input-block" id="row-pemasok">
                                            <label>Nama Pemasok<span class="text-danger">*</span></label>
                                            <select id="po_supplier" class="form-control fill"></select>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-12">
                                        <div class="input-block mb-3">
                                            <label>Tanggal<span class="text-danger">*</span></label>
                                            <input type="date" class="form-control fill" id="po_date">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 row">
                                    <div class="col-lg-3 col-md-6 col-6">
                                        <div class="input-block">
                                            <label>Tipe Diskon</label>
                                            <select class="form-select" id="jenis_disc">
                                                <option value="persen">Persen</option>
                                                <option value="nominal">Nominal</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6 col-6">
                                        <div class="input-block">
                                            <label>Diskon</label>
                                            <div class="input-group mb-3 discount">
                                                <input type="text" class="form-control fill number-only" id="po_discount" 
                                                placeholder="Input Diskon" value="0">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6 col-6">
                                        <div class="input-block">
                                            <label>PPN</label>
                                            <div class="input-group mb-3">
                                                <input type="text" class="form-control fill number-only" id="po_ppn" 
                                                placeholder="Input PPN" value="0">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6 col-6">
                                        <div class="input-block mb-3">
                                            <label>Biaya Pengiriman</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text">Rp </span>
                                                <input type="text" class="form-control fill number-only nominal_only" id="po_cost" value="0" placeholder="Masukkan Biaya Pengiriman">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-12 col-12">
                                        <div class="input-block mb-3">
                                            <label>Keterangan<span class="text-danger">*</span></label>
                                            <input type="text" class="form-control fill" id="po_desc" placeholder="Masukkan Keterangan">
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-12 col-12">
                                        <div class="input-block mb-3">
                                            <label class="form-label d-flex">Foto Bukti<span class="text-danger">*</span>
                                                <span id="check_foto" style="display: none" class="ms-2">
                                                    <div class="d-flex g-3">
                                                        <i class="fa fa-check-circle text-success mt-1"></i>
                                                        <p class="text-muted ms-1"><span id="jumlahFoto">1</span> gambar terunggah</p>
                                                    </div>
                                                </span>
                                            </label>
                                            <button type="button" class="btn btn-outline-primary" id="btn-foto-bukti">Foto Bukti</button>
                                            <button type="button" class="btn btn-outline-primary" id="btn-lihat-bukti" style="display: none">Lihat Bukti</button>
                                            <input type="hidden" name="" id="bukti">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-12 col-12">
                                    <div class="input-block mb-3">
                                        <label class="d-flex align-items-center gap-2">
                                            SKU/Barcode Produk<span class="text-danger">*</span>
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" id="btn_toggle_scan" title="Ganti mode Scan">
                                                <i class="fa fa-barcode"></i> Scan
                                            </button>
                                        </label>
                                        <div id="po_mode_select">
                                            <select class="form-select" id="po_sku">
                                                <option value="" selected disabled>Pilih Supplier Terlebih Dahulu</option>
                                            </select>
                                        </div>
                                        <div id="po_mode_scan" style="display:none">
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="po_scan_barcode" placeholder="Scan / ketik barcode...">
                                                <input type="number" class="form-control" id="po_scan_qty" placeholder="Qty" value="1" min="1" style="max-width:80px">
                                                <button type="button" class="btn btn-primary" id="btn_scan_add"><i class="fa fa-plus"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 overflow-x-auto mb-3 table-po-wrap">
                                    <table class="table table-center" id="tablePurchaseModal">
                                        <thead>
                                            <th style="width:16%">Produk</th>
                                            <th style="width:20%">Variasi</th>
                                            <th style="width:10%">SKU</th>
                                            <th style="width:18%">Qty</th>
                                            <th style="width:13%" class="text-end">Harga Beli</th>
                                            <th style="width:14%" class="text-end">Subtotal</th>
                                            <th style="width:9%" class="text-center">Action</th>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                                <div class="col-12 row pt-3">
                                    <div class="col-lg-6 col-md-6 col-12"></div>
                                    <div class="col-lg-6 col-md-6 col-12">
                                        <div class="d-flex justify-content-between">
                                            <p>Total</p>
                                            <p id="value_total">Rp. 0</p>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <p>Diskon</p>
                                            <p id="value_discount">Rp.0</p>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <p>Ppn</p>
                                            <p id="value_ppn">Rp. 0</p>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <p>Biaya Pengiriman</p>
                                            <p id="value_cost">Rp. 0</p>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <b>Grand Total</b>
                                            <b id="value_grand">Rp. 0</b>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Tambah Pembelian</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@if (Route::is(['purchaseOrderDetail']))
    <div class="modal fade" id="add-retur" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content p-3">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Tambah Retur Pembelian</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-md-6 col-12">
                                    <div class="input-block mb-3">
                                        <label>Tanggal<span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="date" class="form-control fill" id="rs_date">
                                        </div>
                                    </div>
                                </div>
                                {{-- <div class="col-lg-4">
                                    <div class="input-block mb-3">
                                        <label class="form-label d-flex">
                                            Bukti Foto<span class="text-danger">*</span> 
                                            <span id="check_foto" style="display: none" class="ms-2">
                                                <div class="d-flex g-3">
                                                    <i class="fa fa-check-circle text-success mt-1"></i>
                                                    <p class="text-muted ms-1">gambar terunggah</p>
                                                </div>
                                            </span>
                                        </label>
                                        <button type="button" class="btn btn-outline-primary" id="btn-foto-bukti">Foto Bukti</button>
                                        <button type="button" class="btn btn-outline-primary" id="btn-lihat-bukti" style="display: none">Lihat Bukti</button>
                                        <input type="hidden" name="" id="bukti">
                                    </div>
                                </div> --}}
                                <div class="col-md-6 col-12">
                                    <div class="input-block mb-3">
                                        <label class="form-label">Keterangan<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="rs_notes" placeholder="Tambahkan Catatan">
                                    </div>
                                </div>
                                <div class="col-12 py-3 mb-3">
                                    <div style="max-height: 320px; overflow-y: auto; overflow-x: auto;">
                                        <table class="table table-center" id="tableSuppliesModal">
                                            <thead>
                                                <tr>
                                                    <th id="header_name" style="position: sticky; top: 0; z-index: 2; background: #dce8f6;">Nama Bahan</th>
                                                    <th class="text-center" style="position: sticky; top: 0; z-index: 2; background: #dce8f6;">Qty</th>
                                                    <th style="position: sticky; top: 0; z-index: 2; background: #dce8f6;">Satuan</th>
                                                    <th class="text-end" style="position: sticky; top: 0; z-index: 2; background: #dce8f6;">Harga</th>
                                                    <th class="text-end" style="position: sticky; top: 0; z-index: 2; background: #dce8f6;">Subtotal</th>
                                                    <th class="text-center" style="position: sticky; top: 0; z-index: 2; background: #dce8f6;">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                            <tfoot>
                                                <tr>
                                                    <td colspan="4" class="text-end fw-bold">Total : </td>
                                                    <td class="totals fw-bold text-end">Rp 0</td>
                                                    <td></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-12 px-2 mb-3">
                                    <div class="row input_table g-3 align-items-end">
                                        <div class="col-12 col-lg-4 add">
                                            <div class="input-block mb-3" id="row-supplies">
                                                <label>Nama Bahan Mentah<span class="text-danger">*</span></label>
                                                <select class="form-select fill_supplies" id="supplies_id"></select>
                                            </div>
                                        </div>
                                        <div class="col-6 col-lg-3 add">
                                            <div class="input-block mb-3">
                                                <label>Qty<span class="text-danger">*</span></label>
                                                <input type="text"
                                                    class="form-control fill_supplies number-only"
                                                    id="rsd_qty"
                                                    placeholder="Qty Bahan">
                                            </div>
                                        </div>
                                        <div class="col-6 col-lg-4 add">
                                            <div class="input-block mb-3">
                                                <label>Nama Satuan<span class="text-danger">*</span></label>
                                                <select class="form-select fill_supplies" id="unit_supplies_id"></select>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-12 col-lg-1 add">
                                            <button type="button" class="btn btn-primary w-100 btn-add-supplies mb-3">
                                                +
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer p-0">
                                <button type="button" data-bs-dismiss="modal"
                                    class="btn btn-back cancel-btn me-2">Batal</button>
                                <button type="button" class="btn btn-primary btn-save-retur">Tambah Retur
                                    </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- modal: Tambah Delivery Notes -->
    <div class="modal fade custom-modal" id="add_purchase_delivery" role="dialog" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="mb-0 modal-title">Tambah Delivery Notes</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row g-3">
                                <div class="col-12 col-md-4">
                                    <div class="input-block">
                                        <label>Nama Penerima<span class="text-danger">*</span></label>
                                        <select name="" id="pdo_receiver" class="form-select"></select>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4">
                                    <div class="input-block">
                                        <label>Tanggal<span class="text-danger">*</span></label>
                                        <input type="date" class="form-control fill" id="pdo_date">
                                    </div>
                                </div>
                                <div class="col-12 col-md-4">
                                    <div class="input-block">
                                        <label>No. Telepon<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill number-only" id="pdo_phone" placeholder="Input nomor telepon">
                                    </div>
                                </div>

                                {{--  <div class="col-12 col-md-6">
                                   
                                    <div class="input-block">
                                        <label>Alamat<span class="text-danger">*</span></label>
                                        <textarea class="form-control fill" id="pdo_address" rows="3" placeholder="Alamat penerima"></textarea>
                                    </div>
                                </div>--}}
                                <div class="col-12 col-md-12">
                                    <div class="input-block">
                                        <label>Keterangan</label>
                                        <textarea class="form-control" id="pdo_desc" rows="3" placeholder="Keterangan pengiriman"></textarea>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="table-responsive">
                                        <table class="table table-center table-bordered align-middle" id="tablePurchaseDelivery">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Supplies</th>
                                                    <th>SKU</th>
                                                    <th>Qty</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex justify-content-end">
                        <div class="row-acc">
                            <button class="btn btn-danger btn-decline me-2" type="button">Tolak</button>
                            <button class="btn btn-success btn-approve me-3" type="button">Setujui</button>
                        </div>
                        <button type="button" data-bs-dismiss="modal" class="btn btn-outline-secondary me-2">Batal</button>
                        <button type="button" class="btn btn-primary btn-save-delivery">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- modal: Tambah Faktur Pembelian -->
    <div class="modal fade custom-modal" id="add_purchase_invoice" role="dialog" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="mb-0 modal-title">Tambah Faktur Pembelian</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <div class="input-block">
                                        <label>Tanggal Faktur<span class="text-danger">*</span></label>
                                        <input type="date" class="form-control fill" id="poi_date">
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="input-block">
                                        <label>Jatuh Tempo<span class="text-danger">*</span></label>
                                        <input type="date" class="form-control fill" id="poi_due">
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="input-block">
                                        <label>Jumlah<span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" class="form-control fill number_only nominal_only" id="poi_total" value="0" placeholder="Masukkan jumlah">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex justify-content-end">
                         <div class="row-acc-invoice">
                            <button class="btn btn-danger btn-decline-invoice me-2" type="button">Tolak</button>
                            <button class="btn btn-success btn-approve-invoice me-3" type="button">Setujui</button>
                        </div>
                        <button type="button" data-bs-dismiss="modal" class="btn btn-outline-secondary me-2">Batal</button>
                        <button type="button" class="btn btn-primary btn-save-invoice">Tambah Faktur</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif


@if (Route::is(['staff']))
    <!-- Hapus User Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Hapus User</h3>
                        <p>Apakah kamu yakin ingin menghapus?</p>
                        
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <a href="#" class="btn btn-primary paid-continue-btn">Hapus</a>
                            </div>
                            <div class="col-6">
                                <a href="#" data-bs-dismiss="modal"
                                    class="btn btn-primary paid-cancel-btn">Batal</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Hapus User Modal -->

    
    <!-- Tambah User -->
    <div class="modal custom-modal modal-lg fade" id="add_user" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="mb-0">Tambah Staf</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup">

                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card-body">
                                    <div class="form-groups-item">
                                        <h5 class="form-title">Foto Profil</h5>
                                        <div class="profile-picture">
                                            <div class="upload-profile">
                                                <div class="profile-img">
                                                    <img id="blah" class="avatar"
                                                        src="{{ URL::asset('/assets/img/profiles/avatar-10.jpg')}}" alt="profile-img">
                                                </div>
                                                <div class="add-profile">
                                                    <h5>Upload Foto Baru</h5>
                                                    <span>Profile-pic.jpg</span>
                                                </div>
                                            </div>
                                            <div class="img-upload">
                                                <a class="btn btn-primary me-2">Upload</a>
                                                <a class="btn btn-remove">Hapus</a>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="input-block mb-3">
                                                    <label>Nama Depan</label>
                                                    <input type="text" class="form-control"
                                                        placeholder="Masukkan Nama Depan">
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="input-block mb-3">
                                                    <label>Nama Belakang</label>
                                                    <input type="text" class="form-control"
                                                        placeholder="Masukkan Nama Belakang">
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="input-block mb-3">
                                                    <label>Username</label>
                                                    <input type="text" class="form-control"
                                                        placeholder="Masukkan Username">
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="input-block mb-3">
                                                    <label>Email</label>
                                                    <input type="email" class="form-control"
                                                        placeholder="Masukkan Email">
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="input-block mb-3">
                                                    <label>No. Telepon</label>
                                                    <input type="text" class="form-control"
                                                        placeholder="Masukkan Nomor Telepon" name="name">
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="input-block mb-3">
                                                    <label>Peran</label>
                                                    <select class="select">
                                                        <option>Pilih Peran</option>
                                                        <option>Peran 1</option>
                                                        <option>Peran 2</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="pass-group" id="3">
                                                    <div class="input-block">
                                                        <label>Password</label>
                                                        <input type="password" class="form-control pass-input"
                                                            placeholder="">
                                                        <span class="toggle-password feather-eye"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="pass-group" id="passwordInput2">
                                                    <div class="input-block">
                                                        <label>Konfirmasi Password</label>
                                                        <input type="password" class="form-control pass-input"
                                                            placeholder="">
                                                        <span class="toggle-password feather-eye"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="input-block ">
                                                    <label>Status</label>
                                                    <select class="select">
                                                        <option>Pilih Status</option>
                                                        <option>Aktif</option>
                                                        <option>Tidak Aktif</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="submit" data-bs-dismiss="modal"
                            class="btn btn-primary paid-continue-btn">Tambah User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Tambah User -->
@endif

@if (Route::is(['cash']))
    <!-- modal -->
    <div class="modal modal-lg custom-modal fade" id="add_cash" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Tambah Pencatatan Kas</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-12">
                                    <div class="input-block mb-3">
                                        <label>Tanggal Pencatatan<span class="text-danger">*</span></label>
                                        <input type="date" class="form-control fill" id="cash_date">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="input-block mb-3">
                                        <label>Keterangan<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="cash_description"
                                            placeholder="Masukkan Keterangan">
                                    </div>
                                </div>
                                <div class="row input-block mb-3 pe-0">
                                    <div class="col-md-4 col-12 pe-0">
                                        <label>Tipe<span class="text-danger">*</span></label>
                                        <select class="form-select" id="cash_select">
                                            <option value="debit" checked>Masuk</option>
                                            <option value="credit1">Keluar</option>
                                            <option value="credit2">Keluar 1</option>
                                        </select>
                                    </div>
                                    <div class="col-md-8 col-12 mt-md-0 mt-3 pe-0">
                                        <label>Jumlah Nominal<span class="text-danger">*</span></label>
                                        <div class="input-group fix-nominal">
                                            <span class="input-group-text">Rp.</span>
                                            <input type="text" name="" id="cash_nominal" class="form-control fill number-only nominal_only" placeholder="Contoh 10000">
                                        </div> 
                                    </div>
                                </div>
                                {{-- <div class="col-12" id="tujuan">
                                    <div class="input-block mb-3">
                                        <label>Tujuan Keluar<span class="text-danger">*</span></label>
                                        <select class="form-select fill" id="cash_tujuan">
                                            <option value="" disabled selected>Pilih Tujuan</option>
                                            <option value="admin">Kas Admin</option>
                                            <option value="gudang">Kas Gudang</option>
                                        </select>
                                    </div>
                                </div> --}}
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Tambah Pencatatan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal modal-lg custom-modal fade" id="modal-detail-sales" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Detail Operasional</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-12">
                                    <table class="table table-bordered mb-0">
                                        <thead class="">
                                            <tr>
                                                <th>Keterangan</th>
                                                <th class="text-end">Nominal</th>
                                            </tr>
                                        </thead>
                                        <tbody id="detail-sales-body"></tbody>
                                        <tfoot class="table-light fw-bold">
                                            <tr>
                                                <td>Total</td>
                                                <td class="text-end" id="detail-sales-total"></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Kembali</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if (Route::is(['pettyCash']))
    <!-- modal -->
    <div class="modal modal-xl custom-modal fade" id="add_petty_cash" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Tambah Kas Kecil</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-6">
                                    <div class="input-block mb-3">
                                        <label>Tanggal<span class="text-danger">*</span></label>
                                        <input type="date" class="form-control fill" id="pc_date">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="input-block mb-3">
                                        <label>Nama Staff<span class="text-danger">*</span></label>
                                        <select class="form-select fill" id="staff_id"></select>
                                    </div>
                                </div>
                                <div class="col-12 py-3 mb-3">
                                    <table class="table table-center" id="tableCash" style="min-height: 15vh">
                                        <thead>
                                            <th>Catatan</th>
                                            <th>Kategori</th>
                                            <th>Tipe</th>
                                            <th>Nominal</th>
                                            <th class="no-sort text-center">Aksi</th>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                                <div class="col-12 px-2 mb-3">
                                    <div class="row input_table g-3 align-items-end">
                                        <div class="col-12 col-lg-3 add">
                                            <div class="input-block mb-3" id="row-product">
                                                <label>Catatan<span class="text-danger">*</span></label>
                                                <input type="text" class="form-control fill_cash" id="pc_description"
                                                    placeholder="Masukkan Catatan">
                                            </div>
                                        </div>
                                        <div class="col-12 col-lg-3 add">
                                            <div class="input-block mb-3" id="row-cash">
                                                <label>Kategori Kas<span class="text-danger">*</span></label>
                                                <select class="form-select fill_cash" id="cc_id">
                                                    <option value="debit" checked>Masuk</option>
                                                    <option value="credit">Keluar</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-6 col-lg-2 add">
                                            <div class="input-block mb-3">
                                                <label>Tipe Kas<span class="text-danger">*</span></label>
                                                <input type="text" class="form-control fill_cash" id="cc_type" disabled>
                                            </div>
                                        </div>
                                        <div class="col-6 col-lg-3 add">
                                            <div class="input-block mb-3">
                                                <label>Nominal<span class="text-danger">*</span></label>
                                                <div class="input-group fix-nominal">
                                                    <span class="input-group-text">Rp.</span>
                                                    <input type="text" name="" id="pc_nominal" class="form-control fill_cash number-only nominal_only" placeholder="Contoh 10000">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-12 col-lg-1 add text-end">
                                            <button type="button" class="btn btn-primary w-100 btn-add-cash mb-3">
                                                +
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Tambah Kas</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if (Route::is(['operationalCash']))
    <!-- modal -->
    <div class="modal modal-lg custom-modal fade" id="add_cash_admin" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Tambah Aktivitas Admin</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-12 col-md-6 mb-3">
                                    <div class="input-block">
                                        <label class="fw-bold">Jenis Aktivitas</label>
                                        <select class="form-select" id="jenis_input">
                                            <option value="saldo" selected>Manajemen Saldo Kas</option>
                                            <option value="operasional">Aktivitas Operasional</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 d-none d-md-block mb-3"></div>

                                <div class="col-12">
                                    <div class="row g-2" id="inputModal">
                                        
                                        <div class="col-12 col-md-6 saldo_kas">
                                            <div class="input-block mb-3">
                                                <label>Aksi Dana<span class="text-danger">*</span></label>
                                                <select class="form-select fill" id="oc_transaksi">
                                                    <option value="1">Pengajuan</option>
                                                    <option value="2">Pengembalian</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6 operasional">
                                            <div class="input-block mb-3">
                                                <label>Tanggal<span class="text-danger">*</span></label>
                                                <input type="date" class="form-control fill" id="oc_date">
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6">
                                            <div class="input-block mb-3" id="row-cash">
                                                <label>Nama Staff<span class="text-danger">*</span></label>
                                                <select class="form-select fill" id="staff_id"></select>
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6 saldo_kas">
                                            <div class="input-block mb-3">
                                                <label>Nominal<span class="text-danger">*</span></label>
                                                <div class="input-group fix-nominal">
                                                    <span class="input-group-text">Rp </span>
                                                    <input class="form-control fill number-only nominal_only saldos" id="oc_nominal" placeholder="Contoh: 10.000">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6 operasional">
                                            <div class="input-block mb-3">
                                                <label class="form-label d-flex align-items-center">
                                                    Bukti Foto<span class="text-danger">*</span> 
                                                    <span id="check_foto" style="display: none" class="ms-2">
                                                        <small class="text-success"><i class="fa fa-check-circle"></i> terunggah</small>
                                                    </span>
                                                </label>
                                                <div class="d-grid d-md-block gap-2">
                                                    <button type="button" class="btn btn-outline-primary" id="btn-foto-bukti">Foto Bukti</button>
                                                    <button type="button" class="btn btn-outline-primary" id="btn-lihat-bukti" style="display: none">Lihat Bukti</button>
                                                </div>
                                                <input type="hidden" id="bukti">
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6 saldo_kas">
                                            <div class="input-block mb-3">
                                                <label>Keterangan<span class="text-danger">*</span></label>
                                                <input type="text" class="form-control fill saldos" id="oc_notes" placeholder="Contoh: Untuk kas harian">
                                            </div>
                                        </div>

                                        <div class="col-12 operasional mt-2">
                                            <h5 class="form-title mb-2 text-black pb-2">Detail Pengeluaran</h5>
                                        </div>

                                        <div class="col-12 operasional mb-3" id="row-add-catatan-admin">
                                            <div class="row g-2 align-items-end p-2 rounded">
                                                <div class="col-12 col-lg-6">
                                                    <div class="input-block">
                                                        <label class="small">Nama Pencatatan<span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control fill_catatan" id="cad_notes" placeholder="Contoh: Makan Siang">
                                                    </div>
                                                </div>
                                                <div class="col-12 col-lg-5">
                                                    <div class="input-block">
                                                        <label class="small">Nominal<span class="text-danger">*</span></label>
                                                        <div class="input-group">
                                                            <span class="input-group-text">Rp</span>
                                                            <input class="form-control fill_catatan number-only nominal_only" id="cad_nominal" placeholder="10.000">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-lg-1">
                                                    <button type="button" class="btn btn-primary w-100 btn-add-catatan">
                                                        <i class="fa fa-plus"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12 operasional">
                                            <div class="table-responsive">
                                                <table class="table table-center" id="tableDetail" style="min-width: 400px;">
                                                    <thead>
                                                        <tr>
                                                            <th width="50">No</th>
                                                            <th>Keterangan</th>
                                                            <th class="text-end">Nominal</th>
                                                            <th class="text-center">Aksi</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody></tbody>
                                                    <tfoot>
                                                        <tr class="fw-bold">
                                                            <td colspan="2" class="text-end">Total :</td>
                                                            <td class="total text-end">Rp 0</td>
                                                            <td></td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>
                                    </div> 
                                </div>
                            </div> 
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save-admin">Tambah Aktivitas</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal modal-lg custom-modal fade" id="add_cash_gudang" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Tambah Aktivitas Gudang</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-lg-6 col-12 mb-4">
                                    <div class="input-block">
                                        <label>Jenis Aktivitas</label>
                                        <select class="form-select" id="jenis_input_gudang">
                                            <option value="saldo" selected>Manajemen Saldo Kas</option>
                                            <option value="operasional">Aktivitas Operasional</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-12 mb-lg-4 mb-0"></div>
                                <div class="row p-0 m-0" id="inputModal">
                                    <div class="col-lg-6 col-12 saldo_kas">
                                        <div class="input-block mb-3">
                                            <label>Aksi Dana<span class="text-danger">*</span></label>
                                            <select class="form-select fill" id="oc_transaksi_gudang">
                                                <option value=1>Pengajuan</option>
                                                <option value=2>Pengembalian</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-6 operasional">
                                        <div class="input-block mb-3">
                                            <label>Tanggal<span class="text-danger">*</span></label>
                                            <input type="date" class="form-control fill" id="oc_date_gudang"></input>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-12">
                                        <div class="input-block mb-3" id="row-cash">
                                            <label>Nama Pengaju<span class="text-danger">*</span></label>
                                            <select class="form-select fill" id="staff_id_gudang"></select>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-12 saldo_kas">
                                        <div class="input-block mb-3">
                                            <label>Nominal<span class="text-danger">*</span></label>
                                            <div class="input-group fix-nominal">
                                                <span class="input-group-text">Rp </span>
                                                <input class="form-control fill number-only nominal_only saldos" id="oc_nominal_gudang" placeholder="Contoh: 10.000"></input>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-12 operasional">
                                        <label class="form-label d-flex">
                                            Bukti Foto<span class="text-danger">*</span> 
                                            <span id="check_foto_gudang" style="display: none" class="ms-2">
                                                <div class="d-flex g-3">
                                                    <i class="fa fa-check-circle text-success mt-1"></i>
                                                    <p class="text-muted ms-1">gambar terunggah</p>
                                                </div>
                                            </span>
                                        </label>
                                        <div class="d-grid d-md-block gap-2">
                                            <button type="button" class="btn btn-outline-primary" id="btn-foto-bukti-gudang">Foto Bukti</button>
                                            <button type="button" class="btn btn-outline-primary" id="btn-lihat-bukti-gudang" style="display: none">Lihat Bukti</button>
                                        </div>
                                        <input type="hidden" name="" id="bukti_gudang">
                                    </div>
                                    <div class="col-lg-6 col-12 saldo_kas">
                                        <div class="input-block mb-3">
                                            <label>Keterangan<span class="text-danger">*</span></label>
                                            <input type="text" class="form-control fill saldos" id="oc_notes_gudang" placeholder="Contoh: Untuk kas harian">
                                        </div>
                                    </div>
                                    <div class="col-12 operasional mt-2">
                                        <h5 class="form-title mb-2 text-black">Detail</h5>
                                    </div>
                                    <div class="col-12 px-2 mb-3 operasional">
                                        <div class="row input_table g-3 align-items-end px-1">
                                            <div class="col-12 col-lg-6 add">
                                                <div class="input-block mb-3" id="row-gudang">
                                                    <label>Nama Armada<span class="text-danger">*</span></label>
                                                    <select class="form-select fill" id="customer_id"></select>
                                                </div>
                                            </div>
                                            <div class="col-12 col-lg-6 add">
                                                <div class="input-block mb-3">
                                                    <label>Keterangan<span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control fill_catatan" id="cgd_notes" placeholder="Contoh: Kas Harian">
                                                </div>
                                            </div>
                                            <div class="col-12 col-lg-6 add">
                                                <div class="input-block mb-3">
                                                    <label>Pilih Jumlah Nominal<span class="text-danger">*</span></label>
                                                    <select class="form-select fill_catatan" id="jenis_nominal">
                                                        <option value="" disabled selected>Pilih Jumlah Nominal</option>
                                                        <option value="500000">Rp 500.000</option>
                                                        <option value="1000000">Rp 1.000.000</option>
                                                        <option value="1500000">Rp 1.500.000</option>
                                                        <option value="2000000">Rp 2.000.000</option>
                                                        <option value="manual">Input Manual</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-12 col-lg-5 add input_nominal">
                                                <div class="input-block mb-3">
                                                    <label>Nominal<span class="text-danger">*</span></label>
                                                    <div class="input-group fix-nominal">
                                                        <span class="input-group-text">Rp </span>
                                                        <input class="form-control fill_catatan number-only nominal_only" disabled id="cgd_nominal" placeholder="Contoh: 10.000"></input>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-12 col-lg-1 add">
                                                <button type="button" class="btn btn-primary w-100 btn-add-gudang mb-3">
                                                    +
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 py-3 mb-3 operasional">
                                        <div class="table-responsive">
                                            <table class="table table-center" id="tableDetailGudang" style="min-height: 15vh">
                                                <thead>
                                                    <th>No</th>
                                                    <th>Armada</th>
                                                    <th style="width: 25%">Nama</th>
                                                    <th class="text-end">Nominal</th>
                                                    <th class="no-sort text-center">Aksi</th>
                                                </thead>
                                                <tbody></tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td colspan="3" class="text-end fw-bold">Total : </td>
                                                        <td class="total_gudang text-end fw-bold">Rp 0</td>
                                                        <td></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save-gudang">Tambah Aktivitas</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal modal-lg custom-modal fade" id="add_cash_armada" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Tambah Aktivitas Armada</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-lg-6 col-12 mb-4">
                                    <div class="input-block">
                                        <label>Jenis Aktivitas</label>
                                        <select class="form-select" id="jenis_input_armada">
                                            <option value="saldo" selected>Pengembalian Dana Langsung</option>
                                            <option value="operasional">Aktivitas Operasional</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-12 mb-lg-4 mb-0"></div>
                                <div class="row p-0 m-0" id="inputModal">
                                    <div class="col-12 col-lg-6">
                                        <div class="input-block mb-3">
                                            <label>Tanggal<span class="text-danger">*</span></label>
                                            <input type="date" class="form-control fill" id="oc_date_armada"></input>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-12">
                                        <div class="input-block mb-3" id="row-cash">
                                            <label>Nama Armada<span class="text-danger">*</span></label>
                                            <select class="form-select fill" id="customer_id_armada"></select>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-12 foto operasional mb-3">
                                        <label class="form-label d-flex">
                                            Bukti Foto<span class="text-danger">*</span> 
                                            <span id="check_foto_armada" style="display: none" class="ms-2">
                                                <div class="d-flex g-3">
                                                    <i class="fa fa-check-circle text-success mt-1"></i>
                                                    <p class="text-muted ms-1"><span id="jumlahFoto">1</span> gambar terunggah</p>
                                                </div>
                                            </span>
                                        </label>
                                        <div class="d-grid d-md-block gap-2">
                                            <button type="button" class="btn btn-outline-primary" id="btn-foto-bukti-armada">Foto Bukti</button>
                                            <button type="button" class="btn btn-outline-primary" id="btn-lihat-bukti-armada" style="display: none">Lihat Bukti</button>
                                        </div>
                                        <input type="hidden" name="" id="bukti_armada">
                                    </div>
                                    <div class="col-lg-6 col-12 saldo_kas">
                                        <div class="input-block mb-3">
                                            <label>Nominal Pengembalian<span class="text-danger">*</span></label>
                                            <div class="input-group fix-nominal">
                                                <span class="input-group-text">Rp </span>
                                                <input class="form-control fill number-minus nominal_minus saldos" id="oc_nominal_armada" placeholder="Contoh: 10.000"></input>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-12 saldo_kas">
                                        <div class="input-block mb-3">
                                            <label>Keterangan<span class="text-danger">*</span></label>
                                            <input type="text" class="form-control fill saldos" id="oc_notes_armada" placeholder="Contoh: Pengembalian kas harian">
                                        </div>
                                    </div>
                                    <div class="col-12 px-2 mb-3 operasional">
                                        <div class="row input_table g-3 align-items-end px-1">
                                            {{-- <div class="col-12 col-lg-3">
                                                <div class="input-block mb-3">
                                                    <label>Tipe<span class="text-danger">*</span></label>
                                                    <select class="form-select fill_catatan" id="oc_transaksi_armada">
                                                        <option value=1>Masuk</option>
                                                        <option value=2>Keluar</option>
                                                        <option value=3>Keluar 1</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-12 col-lg-4 add">
                                                <div class="input-block mb-3" id="row-product">
                                                    <label>Nama Pencatatan<span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control fill_catatan" id="crd_notes" placeholder="Contoh: Makan Siang">
                                                </div>
                                            </div> --}}
                                            <div class="col-lg-6 col-12">
                                                <div class="input-block mb-3" id="row-product">
                                                    <label>Nama Pencatatan<span class="text-danger">*</span></label>
                                                    <select class="form-select fill_catatan" id="cc_id"></select>
                                                </div>
                                            </div>
                                            <div class="col-12 col-lg-5 add">
                                                <div class="input-block mb-3">
                                                    <label>Nominal<span class="text-danger">*</span></label>
                                                    <div class="input-group fix-nominal">
                                                        <span class="input-group-text">Rp </span>
                                                        <input class="form-control fill_catatan number-minus nominal_minus" id="crd_nominal" placeholder="Contoh: 10.000"></input>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-12 col-lg-1 add">
                                                <button type="button" class="btn btn-primary w-100 btn-add-armada mb-3">
                                                    +
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 py-3 mb-3 operasional">
                                        <div class="table-responsive">
                                            <table class="table table-center" id="tableDetailArmada" style="min-height: 15vh">
                                                <thead>
                                                    <th>No</th>
                                                    <th>Tipe</th>
                                                    <th style="width: 25%">Keterangan</th>
                                                    <th class="text-end">Nominal</th>
                                                    <th class="no-sort text-center">Aksi</th>
                                                </thead>
                                                <tbody></tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td colspan="3" class="text-end fw-bold">Total : </td>
                                                        <td class="total_armada text-end fw-bold">Rp 0</td>
                                                        <td></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save-armada">Tambah Aktivitas</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal modal-lg custom-modal fade" id="add_cash_sales" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Tambah Aktivitas Sales</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-12 col-lg-6">
                                    <div class="input-block mb-3">
                                        <label>Jenis Aktivitas</label>
                                        <select class="form-select" id="jenis_input_sales">
                                            <option value="saldo" selected>Manajemen Saldo Kas</option>
                                            <option value="operasional">Aktivitas Operasional</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-lg-6"></div>
                                <div class="row p-0 m-0" id="inputModal">
                                    <div class="col-12 col-lg-6 saldo_kas">
                                        <div class="input-block mb-3">
                                            <label>Aksi Dana<span class="text-danger">*</span></label>
                                            <select class="form-select fill" id="aksi_sales">
                                                <option value=1>Pemasukan</option>
                                                <option value=2>Setor ke Bank</option>
                                                <option value=3>Pengembalian</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-6">
                                        <div class="input-block mb-3">
                                            <label>Tanggal<span class="text-danger">*</span></label>
                                            <input type="date" class="form-control fill" id="date_sales"></input>
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-6">
                                        <div class="input-block mb-3" id="row-cash">
                                            <label>Nama Sales<span class="text-danger">*</span></label>
                                            <select class="form-select fill" id="staff_id_sales"></select>
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-6 foto operasional mb-3">
                                        <label class="form-label d-flex">
                                            Bukti Foto<span class="text-danger">*</span> 
                                            <span id="check_foto_sales" style="display: none" class="ms-2">
                                                <div class="d-flex g-3">
                                                    <i class="fa fa-check-circle text-success mt-1"></i>
                                                    <p class="text-muted ms-1"><span id="jumlahFotoSales">1</span> gambar terunggah</p>
                                                </div>
                                            </span>
                                        </label>
                                        <div class="d-grid d-md-block gap-2">
                                            <button type="button" class="btn btn-outline-primary" id="btn-foto-bukti-sales">Foto Bukti</button>
                                            <button type="button" class="btn btn-outline-primary" id="btn-lihat-bukti-sales" style="display: none">Lihat Bukti</button>
                                        </div>
                                        <input type="hidden" name="" id="bukti_sales">
                                    </div>
                                    <div class="col-12 col-lg-6 saldo_kas banks">
                                        <div class="input-block mb-3" id="row-bank">
                                            <label>Bank Account<span class="text-danger">*</span></label>
                                            <select class="form-select fill" id="bank_account"></select>
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-6 saldo_kas">
                                        <div class="input-block mb-3">
                                            <label>Nominal<span class="text-danger">*</span></label>
                                            <div class="input-group fix-nominal">
                                                <span class="input-group-text">Rp </span>
                                                <input class="form-control fill number-minus nominal_minus saldos" id="oc_nominal_sales" placeholder="Contoh: 10.000"></input>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-6 saldo_kas">
                                        <div class="input-block mb-3">
                                            <label>Keterangan<span class="text-danger">*</span></label>
                                            <input type="text" class="form-control fill saldos" id="oc_notes_sales" placeholder="Contoh: Pengembalian kas harian">
                                        </div>
                                    </div>
                                    <div class="col-12 px-2 mb-3 operasional">
                                        <div class="row input_table g-3 align-items-end px-1">
                                            <div class="col-lg-6 col-12">
                                                <div class="input-block mb-3" id="row-product">
                                                    <label>Nama Pencatatan<span class="text-danger">*</span></label>
                                                    <select class="form-select fill_catatan" id="cc_id_sales"></select>
                                                </div>
                                            </div>
                                            <div class="col-12 col-lg-5 add">
                                                <div class="input-block mb-3">
                                                    <label>Nominal<span class="text-danger">*</span></label>
                                                    <div class="input-group fix-nominal">
                                                        <span class="input-group-text">Rp </span>
                                                        <input class="form-control fill_catatan number-minus nominal_minus" id="csd_nominal" placeholder="Contoh: 10.000"></input>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-12 col-lg-1 add">
                                                <button type="button" class="btn btn-primary w-100 btn-add-sales mb-3">
                                                    +
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 py-3 mb-3 operasional">
                                        <div class="table-responsive">
                                            <table class="table table-center" id="tableDetailSales" style="min-height: 15vh">
                                                <thead>
                                                    <th>No</th>
                                                    <th>Tipe</th>
                                                    <th style="width: 25%">Keterangan</th>
                                                    <th class="text-end">Nominal</th>
                                                    <th class="no-sort text-center">Aksi</th>
                                                </thead>
                                                <tbody></tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td colspan="3" class="text-end fw-bold">Total : </td>
                                                        <td class="total_sales text-end fw-bold">Rp 0</td>
                                                        <td></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save-sales">Tambah Aktivitas</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if (Route::is(['role']))
    <!-- modal -->
    <div class="modal modal-lg custom-modal fade" id="add_role" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Tambah Peran</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-12">
                                    <div class="input-block mb-3">
                                        <label>Nama Peran<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="role_name"
                                            placeholder="Masukkan Nama Peran">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Tambah Peran</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if (Route::is(['bom']))
    <!-- modal -->
    <div class="modal modal-lg custom-modal fade" id="add_bom" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Tambah Resep Bahan</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-12 col-md-7 col-lg-7">
                                    <div class="input-block mb-3">
                                        <label>Produk<span class="text-danger">*</span></label>
                                        <select class="form-select fill" id="product_id"></select>
                                    </div>
                                </div>

                                <div class="col-12 col-md-5 col-lg-5">
                                    <div class="input-block mb-3">
                                        <label>Qty Produksi<span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="text" class="form-control fill number-only" id="bom_qty" placeholder="Qty">
                                            <select class="form-select w-25 fill" id="unit_id"></select>
                                        </div>
                                        <div id="product_unit_info" class="mt-1" style="display:none; font-size:0.82rem; line-height:1.4;"></div>

                                    </div>
                                </div>

                                <div class="col-12 py-3 mb-3">
                                    <div class="table-responsive">
                                        <table class="table table-center" id="tableSupply" style="min-height: 15vh">
                                            <thead>
                                                <tr>
                                                    <th>Nama Bahan</th>
                                                    <th>Qty</th>
                                                    <th>Satuan</th>
                                                    <th class="no-sort text-center">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="col-12 col-md-12 col-lg-4">
                                    <div class="input-block mb-3">
                                        <label>Nama Bahan<span class="text-danger">*</span></label>
                                        <select class="form-select fill_supply" id="supplies_id"></select>
                                    </div>
                                </div>

                                <div class="col-6 col-md-6 col-lg-3">
                                    <div class="input-block mb-3">
                                        <label>Qty<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill_supply number-only" id="bom_detail_qty" placeholder="Qty">
                                    </div>
                                </div>

                                <div class="col-6 col-md-6 col-lg-4">
                                    <div class="input-block mb-3">
                                        <label>Satuan<span class="text-danger">*</span></label>
                                        <select class="form-select fill_supply" id="unit_supplies_id"></select>
                                        <div id="supplies_unit_info" class="mt-1" style="display:none; font-size:0.82rem; line-height:1.4;"></div>
                                    </div>
                                </div>

                                <div class="col-12 col-md-12 col-lg-1 pt-4">
                                    <a class="btn btn-primary btn-add-supply w-100">+</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Tambah Resep</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if (Route::is(['area']))
    <!-- modal -->
    <div class="modal modal-lg custom-modal fade" id="add_area" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Tambah Wilayah</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-6">
                                    <div class="input-block mb-3">
                                        <label>Kode Wilayah<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="area_code"
                                            placeholder="Input Kode Wilayah">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="input-block mb-3">
                                        <label>Nama Wilayah<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="area_name"
                                            placeholder="Input Nama Wilayah">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Tambah Wilayah</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endif

@if (Route::is(['purchaseOrderDetail']))
    <!-- modal -->
    <div class="modal modal-lg custom-modal fade" id="modalTerima" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Penerbitan Tanda Terima</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="container-fluid">
                            <label>Detail Barang</label>
                            <small class="text-muted">Berikut adalah detail barang yang dipesan berdasarkan surat jalan</small>
                            
                            <table class="table table-center mt-2" id="tablePurchaseDelivery">
                                        <thead>
                                            <th>Supplies</th>
                                            <th>SKU</th>
                                            <th>Qty</th>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                            <label class="my-2">Detail Faktur</label>
                            <table class="table table-center table-hover" id="tableInvoice">
                                <thead>
                                    <th style="width:15%">Tgl. Pesanan</th>
                                    <th style="width:15%">Tgl. Jatuh Tempo</th>
                                    <th>No. Faktur</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th class="no-sort text-center">Aksi</th>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Konfirmasi Penerimaan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endif

@if (Route::is(['cashCategory']))
    <!-- modal -->
    <div class="modal modal-lg custom-modal fade" id="add_cash_category" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Tambah Kategori Kas</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-lg-6 col-12">
                                    <div class="input-block mb-3">
                                        <label>Nama Kategori<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="cc_name"
                                            placeholder="ex Makan Siang">
                                    </div>
                                </div>
                                <div class="col-lg-6 col-12">
                                    <div class="input-block mb-3">
                                        <label>Tipe Kategori<span class="text-danger">*</span></label>
                                        <select class="form-select fill" id="cc_type">
                                            <option value="" selected disabled>Pilih Tipe Kategori</option>
                                            <option value="Keluar">Keluar</option>
                                            <option value="Keluar 1">Keluar 1</option>
                                            <option value="Masuk">Masuk / Setoran Tunai</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Tambah Kategori Kas</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endif

@if (Route::is(['stockProduct']))
    <!-- Add coupons -->
    <div class="modal fade" id="add_stock_product" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content p-3">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Riwayat Stok Produk</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="d-flex align-items-end flex-wrap gap-3 mb-4 p-3 rounded" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                                <div style="flex: 1; min-width: 200px;">
                                    <label class="form-label text-muted fw-semibold" style="font-size: 13px;">Periode Dari</label>
                                    <input type="date" class="form-control form-control-sm" id="start_date">
                                </div>
                                <div style="flex: 1; min-width: 200px;">
                                    <label class="form-label text-muted fw-semibold" style="font-size: 13px;">Sampai Dengan</label>
                                    <input type="date" class="form-control form-control-sm" id="end_date">
                                </div>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-clear px-4" style="font-weight: 500; min-height: 33px;">
                                        <i class="fa fa-refresh me-1"></i> Reset
                                    </button>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12 py-3 mb-3">
                                    <div class="table-responsive" style="border: 1px solid #e2e8f0; border-radius: 8px; max-height: 50vh; overflow-y: auto;">
                                        <table class="table table-center table-hover mb-0" id="tableLog" style="min-height: 15vh;">
                                            <thead style="background-color: #f8faff; border-bottom: 2px solid #e2e8f0; position: sticky; top: 0; z-index: 10;">
                                                <tr>
                                                    <th style="width:15%; color: #475569; font-size: 13px; font-weight: 700;">Tanggal</th>
                                                    <th style="width:15%; color: #475569; font-size: 13px; font-weight: 700;">Staff</th>
                                                    <th style="width:15%; color: #475569; font-size: 13px; font-weight: 700;">No. Transaksi</th>
                                                    <th style="width:25%; color: #475569; font-size: 13px; font-weight: 700;">Catatan</th>
                                                    <th style="width:15%; color: #475569; font-size: 13px; font-weight: 700;" class="text-center">Masuk</th>
                                                    <th style="width:15%; color: #475569; font-size: 13px; font-weight: 700;" class="text-center">Keluar</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>


                            <div class="modal-footer p-0 mt-4 border-0">
                                        <button type="button" data-bs-dismiss="modal"
                                            class="btn btn-primary px-4 fw-semibold">Selesai</button>
                                    </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Add Coupons -->
@endif

@if (Route::is(['stockSupplies']))
    <!-- Add coupons -->
    <div class="modal fade" id="add_stock_supplies" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content p-3">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Riwayat Stok Bahan Mentah</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-lg-3 col-md-6 col-sm-12">
                                    <div class="input-block mb-3">
                                        <label>Dari</label>
                                        <div>
                                            <input type="date" class="form-control" id="start_date">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 col-sm-12">
                                    <div class="input-block mb-3">
                                        <label>Sampai</label>
                                        <div>
                                            <input type="date" class="form-control" id="end_date">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 col-sm-0"></div>
                                <div class="col-lg-3 col-md-6 col-sm-12 pt-4 text-end">
                                    <a class="btn btn-outline-secondary btn-clear">
                                        Clear
                                    </a>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12 py-3 mb-3">
                                    <div class="table-scroll overflow-x-auto">
                                        <table class="table table-center" id="tableLog" style="min-height: 15vh">
                                            <thead>
                                                <th style="width: 15%">Tanggal</th>
                                                <th style="width: 15%">Staff</th>
                                                <th style="width: 15%">No. Transaksi</th>
                                                <th style="width: 25%">Catatan</th>
                                                <th style="width: 15%" class="text-center">Masuk</th>
                                                <th style="width: 15%" class="text-center">Keluar</th>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>


                            <div class="modal-footer p-0">
                                <button type="button" data-bs-dismiss="modal"
                                    class="btn btn-back cancel-btn me-2">Kembali</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Add Coupons -->
@endif

@if (Route::is(['supplier']))
    <!-- Add coupons -->
    <div class="modal fade" id="view_supplier" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content p-3">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Detail Pemasok</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row mb-3">
                                <div class="col-lg-4 col-6 mb-2">
                                    <p class="text-muted">Nama Supplier</p>
                                    <p class="text-black" id="supplier_name"></p>
                                </div>
                                <div class="col-lg-4 col-6 mb-2">
                                    <p class="text-muted">No. Telp</p>
                                    <p class="text-black" id="supplier_phone"></p>
                                </div>
                                <div class="col-lg-4 col-6 mb-2">
                                    <p class="text-muted">Alamat</p>
                                    <p class="text-black" id="supplier_address"></p>
                                </div>
                                <div class="col-lg-4 col-6 mb-2">
                                    <p class="text-muted">Keterangan</p>
                                    <p class="text-black" id="supplier_notes"></p>
                                </div>
                                <div class="col-lg-4 col-6 mb-2">
                                    <p class="text-muted">Total Hutang</p>
                                    <p class="text-black" id="supplier_payment"></p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12 pe-0 pt-3 row">
                                    <div class="col-lg-3 col-6">
                                        <div class="input-block mb-3">
                                            <label>Dari</label>
                                            <div>
                                                <input type="date" class="form-control" id="start_date">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-6">
                                        <div class="input-block mb-3">
                                            <label>Sampai</label>
                                            <div>
                                                <input type="date" class="form-control" id="end_date">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-12">
                                        <div class="input-block mb-3">
                                            <label>Status</label>
                                            <select class="form-select fill" id="status">
                                                <option value="">Semua</option>
                                                <option value="4">Menunggu Approval</option>
                                                <option value="1" selected>Belum Terbayar</option>
                                                <option value="3">Menunggu Tanda Terima</option>
                                                <option value="2">Terbayar</option>
                                                <option value="5">Ditolak</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-2 col-md-12 pt-lg-4 pt-2 pe-0 text-end mb-lg-0 mb-3">
                                        <a class="btn btn-outline-secondary btn-clear">
                                            Clear
                                        </a>
                                    </div>
                                </div>
                                <div class="col-12 pb-3 mb-3">
                                    <div class="table-scroll overflow-x-auto">
                                        <table class="table table-center" id="tablePo" style="min-height: 15vh">
                                            <thead>
                                                <th>Tanggal Pembelian</th>
                                                <th>Jatuh Tempo</th>
                                                <th>No. Invoice</th>
                                                <th>Jumlah</th>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer p-0 justify-content-between">
                                <div class="">
                                    <p class="text-black">Total</p>
                                    <p class="text-black fw-bold" id="supplier_payment_bawah"></p>
                                </div>
                                <button type="button" data-bs-dismiss="modal"
                                    class="btn btn-back cancel-btn">Kembali</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Add Coupons -->
@endif

@if (Route::is(['warehouse']))
    <!-- modal -->
    <div class="modal modal-lg custom-modal fade" id="add_warehouse" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content" style="border-radius: 16px; overflow: hidden; border: none;">
                {{-- ── HEADER ── --}}
                <div class="modal-header border-0" style="background: linear-gradient(135deg, #1e3a5f 0%, #2d6a9f 100%); padding: 18px 24px;">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width:40px;height:40px;background:rgba(255,255,255,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                            <i class="fe fe-box text-white" style="font-size:18px;"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 text-white fw-bold modal-title">Tambah Gudang</h5>
                            <small class="text-white-50">Kelola master data gudang / toko</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form action="#">
                    <div class="modal-body p-0 bg-light d-flex flex-column">
                        {{-- Basic Info Panel --}}
                        <div class="p-4 border-bottom bg-white shadow-sm" style="flex: 0 0 auto;">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="text-muted mb-2" style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Nama Gudang <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control fill" id="warehouse_name" placeholder="Contoh: Gudang Pusat Jakarta" style="font-size:14px;border-radius:8px;height:42px;">
                                </div>
                                <div class="col-md-6">
                                    <label class="text-muted mb-2" style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Tipe Gudang <span class="text-danger">*</span></label>
                                    <select class="form-select form-control fill select2" id="warehouse_type_id" style="font-size:14px;height:42px;">
                                        <option value="">Pilih Tipe Gudang...</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="text-muted mb-2" style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;"><i class="fe fe-map-pin me-1"></i>Alamat Lengkap</label>
                                    <textarea class="form-control" id="warehouse_address" rows="2" placeholder="Masukkan alamat lengkap gudang..." style="font-size:14px;resize:none;border-radius:8px;"></textarea>
                                </div>
                            </div>
                        </div>

                        {{-- Permissions Panel --}}
                        <div class="p-4" style="flex: 1 1 auto; background: #f8fafc;">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                <div>
                                    <label class="mb-0 fw-bold text-dark" style="font-size:14px;"><i class="fe fe-shield me-1 text-primary"></i> Akses Menu Sidebar</label>
                                    <p class="text-muted mb-0 mt-1" style="font-size: 11px;">Centang menu yang diizinkan untuk diakses dari gudang ini. Kosongkan jika ingin membuka semua akses.</p>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm" style="background:#eff6ff;color:#3b82f6;border:1px solid #bfdbfe;font-weight:600;font-size:12px;border-radius:6px;padding:6px 12px;" id="btn-sidebar-menus-all">
                                        <i class="fa fa-check-square me-1"></i> Pilih Semua
                                    </button>
                                    <button type="button" class="btn btn-sm" style="background:#fef2f2;color:#ef4444;border:1px solid #fecaca;font-weight:600;font-size:12px;border-radius:6px;padding:6px 12px;" id="btn-sidebar-menus-none">
                                        <i class="fa fa-square-o me-1"></i> Kosongkan
                                    </button>
                                </div>
                            </div>
                            
                            @php
                                $warehousePermissionMenus = json_decode(
                                    file_get_contents(public_path('assets/json/permission.json')),
                                    true
                                ) ?: [];
                                $warehousePermissionGrouped = collect($warehousePermissionMenus)
                                    ->filter(fn ($p) => ($p['SubModules'] ?? '') !== 'Safety Stock')
                                    ->groupBy('Modules');
                            @endphp
                            <style>
                                .menu-masonry {
                                    column-count: 1;
                                    column-gap: 16px;
                                }
                                @media (min-width: 576px) { .menu-masonry { column-count: 2; } }
                                .menu-card {
                                    break-inside: avoid;
                                    margin-bottom: 16px;
                                    background: #ffffff;
                                    border: 1px solid #e2e8f0;
                                    border-radius: 8px;
                                    padding: 12px;
                                    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
                                    transition: all 0.2s ease;
                                }
                                .menu-card:hover {
                                    border-color: #cbd5e1;
                                    box-shadow: 0 4px 6px rgba(0,0,0,0.04);
                                }
                            </style>
                            <div id="warehouse_sidebar_menus" class="border p-3 bg-white" style="border-radius: 12px; max-height: 280px; overflow-y: auto;">
                                <div class="menu-masonry">
                                    @foreach ($warehousePermissionGrouped as $module => $items)
                                        <div class="menu-card">
                                            <div class="form-check m-0 mb-3 d-flex align-items-center gap-2" style="border-bottom: 1px dashed #cbd5e1; padding-bottom: 8px;">
                                                <input class="form-check-input module-check-all m-0" type="checkbox" id="mod_menu_{{ Str::slug($module) }}" style="cursor:pointer; width:16px; height:16px; margin-top:0;">
                                                <label class="form-check-label fw-bold text-dark mb-0" for="mod_menu_{{ Str::slug($module) }}" style="font-size:13.5px; cursor:pointer;">
                                                    {{ $module }}
                                                </label>
                                            </div>
                                            <div class="d-flex flex-column gap-2 px-1">
                                                @foreach ($items as $item)
                                                    <div class="form-check m-0 d-flex align-items-center gap-2">
                                                        <input class="form-check-input warehouse-sidebar-menu m-0" type="checkbox"
                                                            value="{{ $item['SubModules'] }}"
                                                            id="wh_menu_{{ $item['Id'] }}" style="cursor:pointer; width:14px; height:14px; margin-top:0;">
                                                        <label class="form-check-label text-secondary mb-0" for="wh_menu_{{ $item['Id'] }}" style="font-size:12.5px; cursor:pointer;">
                                                            {{ $item['SubModules'] }}
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- ── FOOTER ── --}}
                    <div class="modal-footer border-top" style="background:#f8fafc; padding:14px 24px;">
                        <button type="button" data-bs-dismiss="modal" class="btn" style="border:1px solid #e2e8f0;border-radius:8px;padding:9px 20px;font-size:13px;font-weight:600;color:#64748b;">Batal</button>
                        <button type="button" class="btn btn-save d-inline-flex align-items-center justify-content-center gap-2" style="background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;border:none;border-radius:8px;padding:9px 28px;font-size:13px;font-weight:600;min-width:140px;height:42px;box-shadow:0 4px 12px rgba(59,130,246,.3);"><i class="fe fe-save me-1"></i>Simpan Gudang</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if (Route::is(['warehouse-type']))
    <!-- modal -->
    <div class="modal modal-lg custom-modal fade" id="add_warehouse_type" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content" style="border-radius: 16px; overflow: hidden; border: none;">
                {{-- ── HEADER ── --}}
                <div class="modal-header border-0" style="background: linear-gradient(135deg, #1e3a5f 0%, #2d6a9f 100%); padding: 18px 24px;">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width:40px;height:40px;background:rgba(255,255,255,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                            <i class="fe fe-tag text-white" style="font-size:18px;"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 text-white fw-bold modal-title">Tambah Tipe Gudang</h5>
                            <small class="text-white-50">Kelola kategori / tipe gudang</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form action="#">
                    <div class="modal-body p-4" style="background:#f8fafc;">
                        {{-- Nama Tipe Gudang --}}
                        <div class="mb-3">
                            <label class="text-muted mb-2" style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Nama Tipe Gudang <span class="text-danger">*</span></label>
                            <input type="text" class="form-control fill" id="warehouse_type_name" placeholder="Contoh: Gudang Eceran, Gudang Utama..." style="font-size:14px;border-radius:8px;height:42px;">
                        </div>

                        {{-- Toggle Gudang Utama --}}
                        <div class="p-3" style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;">
                            <div class="d-flex align-items-start gap-3">
                                <div class="status-toggle mt-1" style="flex-shrink:0;">
                                    <input type="checkbox" id="is_main_warehouse" class="check">
                                    <label for="is_main_warehouse" class="checktoggle">checkbox</label>
                                </div>
                                <div>
                                    <label class="mb-1 fw-semibold text-dark" for="is_main_warehouse" style="cursor:pointer;font-size:14px;display:block;">
                                        <i class="fe fe-home me-1 text-primary"></i> Jadikan Gudang Utama
                                    </label>
                                    <p class="text-muted mb-0" style="font-size:12px;line-height:1.5;">
                                        Aktifkan opsi ini untuk menandai sebagai gudang pusat.<br>
                                        <span class="fw-semibold" style="color:#ef4444;"><i class="fas fa-exclamation-circle me-1"></i>Catatan: Hanya boleh ada maksimal 1 tipe gudang utama.</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ── FOOTER ── --}}
                    <div class="modal-footer border-top" style="background:#f8fafc;padding:14px 24px;">
                        <button type="button" data-bs-dismiss="modal" class="btn" style="border:1px solid #e2e8f0;border-radius:8px;padding:9px 20px;font-size:13px;font-weight:600;color:#64748b;">Batal</button>
                        <button type="button" class="btn btn-save d-inline-flex align-items-center justify-content-center gap-2" style="background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;border:none;border-radius:8px;padding:9px 28px;font-size:13px;font-weight:600;min-width:160px;height:42px;box-shadow:0 4px 12px rgba(59,130,246,.3);"><i class="fe fe-save me-1"></i>Simpan Tipe Gudang</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
