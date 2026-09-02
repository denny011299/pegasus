  <style>
    /* Rapikan tampilan chip bootstrap-tagsinput khusus di modal Variasi — style global
       .bootstrap-tagsinput (style.css) pakai display:flex tanpa flex-wrap + overflow-x:auto,
       jadi chip yang kepanjangan ikut menyempit/wrap dua baris di dalam pill-nya sendiri
       alih-alih pindah baris sebagai chip utuh. Di-scope ke #add_variant supaya penggunaan
       bootstrap-tagsinput lain di luar modal ini tidak ikut berubah. */
    #add_variant .bootstrap-tagsinput {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      align-content: flex-start;
      gap: 8px;
      overflow: visible;
      min-height: 46px;
      padding: 10px;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      background: #fff;
    }
    #add_variant .bootstrap-tagsinput input {
      flex: 1 1 140px;
      min-width: 140px;
      padding: 4px 2px;
    }
    #add_variant .bootstrap-tagsinput .tag {
      display: inline-flex;
      align-items: center;
      flex: 0 0 auto;
      white-space: nowrap;
      margin: 0;
      padding: 6px 10px;
      border-radius: 999px;
      font-size: 13px;
      font-weight: 600;
      line-height: 1.2;
    }
    #add_variant .bootstrap-tagsinput .tag [data-role="remove"] {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 18px;
      height: 18px;
      margin-left: 8px;
      padding: 0;
      border-radius: 50%;
      background: rgba(255, 255, 255, .25);
    }
    #add_variant .bootstrap-tagsinput .tag [data-role="remove"]:after {
      font-size: 10px;
    }
  </style>
  <div class="modal modal-lg custom-modal fade pg-modal--form" id="add_variant" role="dialog" data-bs-backdrop="static"
    data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content d-flex flex-column" style="border-radius:16px;overflow:hidden;border:none;">
        {{-- ── HEADER ── --}}
        <div class="modal-header">
          <div class="d-flex align-items-center gap-3">
            <div class="pg-modal-icon">
              <i class="fe fe-layers"></i>
            </div>
            <div>
              <h5 class="mb-0 fw-bold modal-title">Tambah Variasi</h5>
              <small class="text-muted modal-subtitle">Input data variasi produk baru</small>
            </div>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
            aria-label="Close"></button>
        </div>

        <form action="#">
          <div class="modal-body p-4" style="background:#f8fafc;">
            {{-- Nama --}}
            <div class="mb-3">
              <label class="text-muted mb-2"
                style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Nama
                <span class="text-danger">*</span></label>
              <input type="text" class="form-control fill" id="variant_name" placeholder="Input Nama Variasi"
                style="font-size:14px;border-radius:8px;height:42px;">
            </div>
            {{-- Variasi --}}
            <div class="mb-3">
              <label class="text-muted mb-2"
                style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Variasi
                <span class="text-danger">*</span></label>
              <select class="form-control tagging fill" id="variant_attribute" multiple="multiple"
                style="border-radius:8px;">

              </select>
            </div>
          </div>

          {{-- ── FOOTER ── --}}
        <div class="modal-footer pg-modal-footer">
          <button type="button" data-bs-dismiss="modal" class="btn pg-btn-cancel">Batal</button>
          <button type="button" class="btn pg-btn-save btn-save"><i class="fe fe-save me-1"></i>Tambah Variasi</button>
        </div>
      </form>
    </div>
  </div>
</div>
