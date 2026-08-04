  <div class="modal custom-modal fade" id="reject_production_transfer" role="dialog" data-bs-backdrop="static"
    data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-md">
      <div class="modal-content" style="border:0;border-radius:16px;overflow:hidden;">
        <div class="modal-header border-0"
          style="background:linear-gradient(135deg,#991b1b,#ef4444);padding:18px 24px;">
          <div class="d-flex align-items-center gap-3">
            <span
              style="width:40px;height:40px;border-radius:10px;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;">
              <i class="fe fe-alert-triangle text-white"></i>
            </span>
            <div>
              <h5 class="mb-0 text-white fw-bold">Tolak Stock Transfer Produksi</h5>
              <small class="text-white-50">ST dibatalkan, stok tetap di gudang asal</small>
            </div>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
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
        <div class="modal-footer border-top d-flex justify-content-end align-items-center gap-2"
          style="background:#fff;padding:14px 24px;">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal"
            style="min-width:120px;">Batal</button>
          <button type="button" class="btn btn-danger btn-confirm-production-reject" style="min-width:130px;">
            <i class="fe fe-x-circle me-1"></i> Tolak ST
          </button>
        </div>
      </div>
    </div>
  </div>
