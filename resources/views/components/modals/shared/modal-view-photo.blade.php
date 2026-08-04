<div class="modal fade" id="modalViewPhoto" tabindex="-1" role="dialog" data-bs-backdrop="static"
  data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content"
      style="border-radius: 12px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.15); overflow: hidden;">
      <div class="modal-header border-0 d-flex align-items-center justify-content-between"
        style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); padding: 16px 24px;">
        <div class="d-flex align-items-center gap-3">
          <div
            style="width:36px;height:36px;background:rgba(255,255,255,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;">
            <i class="fe fe-image text-white" style="font-size:16px;"></i>
          </div>
          <h5 class="modal-title text-white m-0" style="font-size:16px;font-weight:600;letter-spacing:0.3px;">Lihat Foto
          </h5>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"
          id="btn-close-photo-header" style="opacity:0.8; box-shadow: none;"></button>
      </div>
      <div class="modal-body p-0" style="background: #f8fafc;">
        <div class="d-flex align-items-center justify-content-center p-3"
          style="min-height: 300px; max-height: 70vh; overflow: hidden;">
          <img src="" alt="Preview Foto" id="fotoProduksiImage"
            style="max-width:100%; max-height: 65vh; object-fit: contain; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
        </div>
      </div>
      <div class="modal-footer" style="border-top: 1px solid #f1f5f9; padding: 1rem 1.5rem; background: #fff;">
        <div class="w-100 d-flex justify-content-between align-items-center">
          <div>
            <a class="btn p-0" download id="btn_download_photo"
              style="background: #eff6ff; border: 1px solid #bfdbfe; color: #2563eb; border-radius: 8px; font-weight: 500; height: 38px; width: 120px;">
              <div class="d-flex align-items-center justify-content-center w-100 h-100"><i
                  class="fe fe-download me-1"></i> Download</div>
            </a>
          </div>
          <div class="d-flex gap-2">
            <button type="button" class="btn p-0" id="btn-kembali-photo"
              style="background:#f8fafc; border:1px solid #e2e8f0; color:#475569; border-radius: 8px; font-weight: 500; height: 38px; width: 80px;">
              <div class="d-flex align-items-center justify-content-center w-100 h-100">Tutup</div>
            </button>
            <button type="button" class="btn btn-prev p-0"
              style="background:#eff6ff; border:1px solid #bfdbfe; color:#2563eb; border-radius: 8px; font-weight: 500; height: 38px; width: 80px;">
              <div class="d-flex align-items-center justify-content-center w-100 h-100"><i
                  class="fe fe-chevron-left me-1"></i> Prev</div>
            </button>
            <button type="button" class="btn btn-next p-0"
              style="background:#eff6ff; border:1px solid #bfdbfe; color:#2563eb; border-radius: 8px; font-weight: 500; height: 38px; width: 80px;">
              <div class="d-flex align-items-center justify-content-center w-100 h-100">Next <i
                  class="fe fe-chevron-right ms-1"></i></div>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
