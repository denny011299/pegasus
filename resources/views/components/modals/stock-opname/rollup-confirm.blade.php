<style>
  /* Tabel rincian gulung satuan (App\Support\StockOpname\OpnameLifecycle::detectRollupOpportunities()):
     dikunci ke TEPAT 4 baris tinggi lalu scroll -- sengaja TIDAK memakai
     PG_POPUP_TABLE.MAX_VISIBLE_ROWS (public/Custom_js/Shared/popup-table.js), permintaan khusus
     modal ini (2026-09-04), bukan aturan tabel input-baris app-wide yang dipakai modal lain.
     Baris = padding 12px atas+bawah + line-height ~20px = ~44px; 4 baris = ~176px. */
  #modalRollupConfirm .modal-dialog {
    max-width: 640px;
  }
  #modalRollupConfirm .rollup-confirm-table-scroll {
    max-height: 176px;
    overflow-y: auto;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
  }
  #modalRollupConfirm table {
    width: 100%;
    margin-bottom: 0;
  }
  #modalRollupConfirm thead th {
    position: sticky;
    top: 0;
    z-index: 1;
    background: #f1f5f9;
    color: #64748b;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .4px;
    padding: 10px 14px;
    border-bottom: 1px solid #e2e8f0;
  }
  #modalRollupConfirm tbody td {
    padding: 10px 14px;
    vertical-align: middle;
    color: #334155;
    font-size: 13px;
    border-bottom: 1px solid #f1f5f9;
  }
  #modalRollupConfirm tbody tr:last-child td {
    border-bottom: 0;
  }
  #modalRollupConfirm .rollup-unit-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
    border-radius: 6px;
    padding: 3px 8px;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
  }
  /* Panah dan angka "before" adalah DUA span terpisah (2026-09-05) -- coret cuma boleh menembus
     angkanya sendiri, bukan ikut menembus panah &larr; di sebelahnya. */
  #modalRollupConfirm .rollup-unit-chip .rollup-arrow {
    color: #94a3b8;
  }
  #modalRollupConfirm .rollup-unit-chip .rollup-unit-before {
    color: #94a3b8;
    font-weight: 500;
    text-decoration: line-through;
  }
</style>

<div class="modal fade pg-modal--confirm" id="modalRollupConfirm" tabindex="-1" role="dialog" style="z-index: 1065;">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <div class="d-flex align-items-center gap-3">
          <div class="pg-modal-icon"><i class="fe fe-refresh-cw"></i></div>
          <div>
            <h5 class="modal-title mb-0">Stock Produk Ini Akan Di Roll Up</h5>
            <small class="modal-subtitle">Satuan kecil yang tidak dihitung ulang akan dilipat ke satuan besar yang baru dikoreksi</small>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <div class="table-responsive rollup-confirm-table-scroll">
          <table>
            <thead>
              <tr>
                <th style="width:40%;">Produk</th>
                <th>Perubahan Satuan</th>
              </tr>
            </thead>
            <tbody id="rollup-confirm-rows"></tbody>
          </table>
        </div>
        <p class="text-muted mb-0 mt-3" style="font-size:12px;">Lanjutkan roll up stock untuk semua produk di atas?</p>
      </div>
      <div class="modal-footer pg-modal-footer">
        <button type="button" class="btn pg-btn-cancel" id="btn-rollup-confirm-batal">Batal</button>
        <button type="button" class="btn pg-btn-confirm" id="btn-rollup-confirm-lanjut"><i class="fe fe-check-circle me-1"></i>Lanjut</button>
      </div>
    </div>
  </div>
</div>
