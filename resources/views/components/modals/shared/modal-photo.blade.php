<div class="modal fade pg-modal--form custom-modal" id="modalPhoto" tabindex="-1" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false" style="z-index: 1075;">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content d-flex flex-column" style="border-radius:16px;overflow:hidden;border:none;">

      {{-- ── HEADER ── --}}
      <div class="modal-header">
        <div class="d-flex align-items-center gap-3">
          <div class="pg-modal-icon">
            <i class="fe fe-camera"></i>
          </div>
          <h5 class="mb-0 fw-bold modal-title">Ambil Foto Bukti</h5>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"
          id="btn-close-camera"></button>
      </div>
      <div class="modal-body p-4" style="background:#f8fafc;">
        <div class="container-fluid p-0">
          <canvas id="canvas" style="display:none;"></canvas>
          <div id="camera" class="w-100 text-center"
            style="background: #000; border-radius: 12px; overflow: hidden; position: relative; min-height: 240px; display: flex; align-items: center; justify-content: center; box-shadow: inset 0 2px 10px rgba(0,0,0,0.5);">
            <video id="video" autoplay playsinline muted
              style="width: 100%; max-height: 60vh; object-fit: contain; background: #000;"></video>
          </div>
          <div id="preview-box" class="w-100 text-center"
            style="display:none; background: #000; border-radius: 12px; overflow: hidden; position: relative; min-height: 240px; display: flex; align-items: center; justify-content: center; box-shadow: inset 0 2px 10px rgba(0,0,0,0.5);">
            <img id="previewImage" alt="Preview foto" style="width: 100%; max-height: 60vh; object-fit: cover;">
          </div>
        </div>
      </div>
      {{-- ── FOOTER ── --}}
      <div class="modal-footer pg-modal-footer">
        <button type="button" class="btn pg-btn-cancel" id="btn-kembali-camera">Batal</button>

        <div class="d-flex gap-2 m-0" id="camera-actions">
          <button type="button" id="rotateCameraBtn" class="btn btn-light border"><i class="fe fe-refresh-cw me-1"></i> Putar</button>
          <button type="button" id="captureBtn" class="btn pg-btn-save"><i class="fe fe-aperture me-1"></i> Ambil Foto</button>
        </div>

        <div class="d-flex gap-2 m-0" id="preview-actions" style="display: none !important;">
          <button type="button" class="btn btn-light border" id="retakeBtn"><i class="fe fe-refresh-ccw me-1"></i> Ulangi</button>
          <button type="button" class="btn pg-btn-accept" id="uploadBtn"><i class="fe fe-check-circle me-1"></i>Gunakan Foto</button>
        </div>
      </div>
    </div>
  </div>
</div>
