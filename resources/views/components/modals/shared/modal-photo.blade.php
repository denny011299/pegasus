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
