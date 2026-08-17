<div class="modal custom-modal fade pg-modal--danger" id="reject_production_transfer" role="dialog" data-bs-backdrop="static"
  data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content d-flex flex-column" style="border-radius:16px;overflow:hidden;border:none;">

      {{-- ── HEADER ── --}}
      <div class="modal-header">
        <div class="d-flex align-items-center gap-3">
          <div class="pg-modal-icon">
            <i class="fe fe-alert-triangle"></i>
          </div>
          <div>
            <h5 class="mb-0 fw-bold modal-title">Tolak Stock Transfer</h5>
            <small class="text-muted">Proses penolakan pengiriman stok</small>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
          aria-label="Close"></button>
      </div>
        <div class="modal-body p-4" style="background:#f8fafc;">
          <div class="alert alert-light border mb-3" style="font-size:13px;">
            Hasil produksi sudah ada di gudang asal. Menolak ST hanya membatalkan pengiriman — stok tidak dipotong.
          </div>
          <div>
            <label class="form-label fw-semibold">Catatan</label>
            <textarea class="form-control" id="production_reject_notes" rows="3"
              placeholder="Alasan atau keterangan tambahan"></textarea>
          </div>
        </div>
      {{-- ── FOOTER ── --}}
      <div class="modal-footer pg-modal-footer">
        <button type="button" class="btn pg-btn-cancel" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn pg-btn-confirm pg-btn-confirm--danger btn-confirm-production-reject">
          <i class="fe fe-x-circle me-1"></i> Tolak ST
        </button>
      </div>
    </div>
  </div>
</div>
