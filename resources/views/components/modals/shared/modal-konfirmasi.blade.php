<div class="modal fade pg-modal--confirm" id="modalKonfirmasi" tabindex="-1" role="dialog" style="z-index: 1065;">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <div class="d-flex align-items-center gap-3">
          <div class="pg-modal-icon"><i class="fe fe-check-circle"></i></div>
          <h5 class="modal-title mb-0">Konfirmasi</h5>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <p id="text-konfirmasi" class="mb-0"></p>
        {{-- Bukti foto (mis. wajib saat Kirim Stock Transfer, GitHub #140) — disembunyikan
             default, ditampilkan lewat JS (showKonfirmasiPhotoProof) hanya untuk aksi yang butuh. --}}
        <div id="konfirmasi-photo-proof" class="mt-3 d-none">
          <label class="form-label fw-semibold text-muted mb-2"
                 style="font-size:11px;text-transform:uppercase;letter-spacing:.4px;">
            Bukti Foto<span class="text-danger ms-1">*</span>
          </label>
          <div class="d-flex align-items-center gap-2">
            <img id="konfirmasi-photo-preview" src="" alt="Preview bukti foto" class="d-none"
                 style="width:56px;height:56px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0;">
            <button type="button" class="btn btn-outline-primary" id="btn-konfirmasi-photo-proof">
              <i class="fe fe-camera me-1"></i>Ambil/Upload Foto
            </button>
          </div>
          <div id="konfirmasi-photo-proof-error" class="text-danger mt-1" style="font-size:11px;display:none;">
            Bukti foto wajib diunggah
          </div>
          <input type="hidden" id="konfirmasi_photo_proof_base64">
        </div>
        {{-- Terima ST: lihat bukti Kirim yang sudah ada (bukan capture) --}}
        <div id="konfirmasi-photo-view" class="mt-3 d-none">
          <label class="form-label fw-semibold text-muted mb-2"
                 style="font-size:11px;text-transform:uppercase;letter-spacing:.4px;">
            Bukti Foto Kirim
          </label>
          <div class="d-flex align-items-center gap-2">
            <img id="konfirmasi-photo-view-preview" src="" alt="Bukti foto kirim" class="d-none"
                 style="width:56px;height:56px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0;">
            <button type="button" class="btn w-100 p-0 btn-view-st-ship-proof" id="konfirmasi-photo-view-btn"
              data-parent="#modalKonfirmasi"
              style="border-radius:8px;height:42px;background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;border:none;font-weight:600;box-shadow:0 4px 12px rgba(59,130,246,.3);">
              <div class="d-flex align-items-center justify-content-center w-100 h-100">
                <i class="fe fe-image me-1"></i> Lihat Foto
              </div>
            </button>
          </div>
        </div>
      </div>
      <div class="modal-footer pg-modal-footer">
        <button type="button" class="btn pg-btn-cancel btn-cancel">Batal</button>
        <button type="button" class="btn pg-btn-confirm btn-konfirmasi"><i class="fe fe-check-circle me-1"></i>Konfirmasi</button>
      </div>
    </div>
  </div>
</div>
