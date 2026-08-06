<div class="modal fade pg-modal--form custom-modal" id="modalViewPhoto" tabindex="-1" role="dialog" data-bs-backdrop="static"
  data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content d-flex flex-column" style="border-radius: 12px; border: none; overflow: hidden;">

      {{-- ── HEADER ── --}}
      <div class="modal-header">
        <div class="d-flex align-items-center gap-3">
          <div class="pg-modal-icon">
            <i class="fe fe-image"></i>
          </div>
          <h5 class="modal-title fw-bold m-0">Lihat Foto</h5>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"
          id="btn-close-photo-header"></button>
      </div>
      <div class="modal-body p-0" style="background: #f8fafc;">
        <div class="d-flex align-items-center justify-content-center p-3"
          style="min-height: 300px; max-height: 70vh; overflow: hidden;">
          <img src="" alt="Preview Foto" id="fotoProduksiImage"
            style="max-width:100%; max-height: 65vh; object-fit: contain; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
        </div>
      </div>
      {{-- ── FOOTER ── --}}
      <div class="modal-footer pg-modal-footer">
        <button type="button" class="btn pg-btn-cancel" id="btn-kembali-photo" data-bs-dismiss="modal">Tutup</button>
        <button type="button" class="btn btn-light border btn-prev"><i class="fe fe-chevron-left me-1"></i> Prev</button>
        <button type="button" class="btn btn-light border btn-next">Next <i class="fe fe-chevron-right ms-1"></i></button>
        <a class="btn pg-btn-save" download id="btn_download_photo"><i class="fe fe-download me-1"></i> Download</a>
      </div>
    </div>
  </div>
</div>
