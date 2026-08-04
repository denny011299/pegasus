<div class="modal fade" id="modalPhoto" tabindex="-1" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content"
      style="border-radius: 16px; overflow: hidden; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
      <div class="modal-header border-0"
        style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); padding: 16px 24px;">
        <div class="d-flex align-items-center gap-3">
          <div
            style="width:36px;height:36px;background:rgba(255,255,255,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;">
            <i class="fe fe-camera text-white" style="font-size:16px;"></i>
          </div>
          <h5 class="mb-0 text-white fw-bold modal-title" style="font-size: 16px; letter-spacing: 0.3px;">Ambil Foto
            Bukti</h5>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"
          id="btn-close-camera"></button>
      </div>
      <div class="modal-body p-4" style="background:#f8fafc;">
        <div class="container-fluid p-0">
          <canvas id="canvas" style="display:none;"></canvas>
          <div id="camera" class="w-100 text-center"
            style="background: #000; border-radius: 12px; overflow: hidden; position: relative; min-height: 240px; display: flex; align-items: center; justify-content: center; box-shadow: inset 0 2px 10px rgba(0,0,0,0.5);">
            <video id="video" autoplay playsinline
              style="width: 100%; max-height: 60vh; object-fit: cover;"></video>
          </div>
          <div id="preview-box" class="w-100 text-center"
            style="display:none; background: #000; border-radius: 12px; overflow: hidden; position: relative; min-height: 240px; display: flex; align-items: center; justify-content: center; box-shadow: inset 0 2px 10px rgba(0,0,0,0.5);">
            <img id="previewImage" alt="Preview foto" style="width: 100%; max-height: 60vh; object-fit: cover;">
          </div>
        </div>
      </div>
      <div class="modal-footer px-4 py-3 border-top-0 d-flex justify-content-between" style="background:#fff;">
        <button type="button" class="btn btn-outline-secondary" id="btn-kembali-camera"
          style="border-radius:8px; font-weight:600; font-size:13px;"><i class="fe fe-x me-1"></i> Batal</button>

        <div class="d-flex gap-2" id="camera-actions">
          <button type="button" id="rotateCameraBtn" class="btn btn-light"
            style="border-radius:8px; font-weight:600; font-size:13px; color: #475569; border: 1px solid #e2e8f0;"><i
              class="fe fe-refresh-cw me-1"></i> Putar</button>
          <button type="button" id="captureBtn" class="btn btn-primary"
            style="background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;box-shadow:0 4px 12px rgba(59,130,246,.3);"><i
              class="fe fe-aperture me-1"></i> Ambil Foto</button>
        </div>

        <div class="d-flex gap-2" id="preview-actions" style="display: none !important;">
          <button type="button" class="btn btn-light" id="retakeBtn"
            style="border-radius:8px; font-weight:600; font-size:13px; color: #475569; border: 1px solid #e2e8f0;"><i
              class="fe fe-refresh-ccw me-1"></i> Ulangi</button>
          <button type="button" class="btn btn-success" id="uploadBtn"
            style="background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;box-shadow:0 4px 12px rgba(34,197,94,.3);"><i
              class="fe fe-check me-1"></i> Gunakan Foto</button>
        </div>
      </div>
    </div>
  </div>
</div>
