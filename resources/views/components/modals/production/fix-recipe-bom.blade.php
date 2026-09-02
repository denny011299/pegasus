{{-- Scoped recipe fix modal for Production (unique IDs — avoids clash with #addProduction / #add_bom) --}}
<style>
  #fixRecipeBom .modal-body {
    padding: 12px 16px 8px;
  }
  #fixRecipeBom .modal-header {
    padding: 12px 16px 4px;
  }
  #fixRecipeBom .modal-footer {
    padding: 10px 16px;
  }
  #fixRecipeBom .modal-title {
    font-size: 1.05rem;
  }
  #fixRecipeBom #fix_recipe_product_label {
    font-size: 0.95rem;
    line-height: 1.3;
  }
  #fixRecipeBom #fix_recipe_product_unit_info {
    font-size: 0.7rem;
    line-height: 1.25;
    margin-top: 2px !important;
  }
  #fixRecipeBom #fix_recipe_bom_qty {
    max-width: none;
  }
  #fixRecipeBom #fix_recipe_tableSupply {
    margin-bottom: 0;
    font-size: 0.875rem;
  }
  #fixRecipeBom #fix_recipe_tableSupply thead th {
    padding: 6px 8px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    color: #64748b;
    white-space: nowrap;
  }
  #fixRecipeBom #fix_recipe_tableSupply tbody td {
    padding: 6px 8px;
    vertical-align: middle;
  }
  #fixRecipeBom .fix-recipe-unit-cell {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
  }
  #fixRecipeBom .fix-recipe-unit-hint {
    font-size: 0.7rem;
    line-height: 1.25;
    margin: 0;
  }
  #fixRecipeBom .fix-recipe-row-qty {
    max-width: 72px;
    padding: 0.25rem 0.4rem;
  }
  #fixRecipeBom .btn_fix_recipe_delete_row {
    padding: 0.25rem !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }
  #fixRecipeBom .fix-recipe-add-strip {
    padding: 8px 10px;
    margin-top: 8px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #f8fafc;
  }
  #fixRecipeBom .fix-recipe-add-strip .input-block {
    margin-bottom: 0;
  }
  #fixRecipeBom .fix-recipe-add-strip label {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: #64748b;
    margin-bottom: 2px;
  }
  #fixRecipeBom #fix_recipe_supplies_unit_info {
    font-size: 0.7rem;
    line-height: 1.25;
    margin-top: 2px !important;
  }
  #fixRecipeBom .btn-fix-recipe-add-supply {
    height: 31px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }
  #fixRecipeBom .select2-container .select2-selection--single {
    height: 31px !important;
    min-height: 31px;
  }
  #fixRecipeBom .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 29px !important;
    font-size: 0.875rem;
    padding-left: 8px;
  }
  #fixRecipeBom .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 29px !important;
  }
</style>
<div class="modal modal-lg custom-modal fade" id="fixRecipeBom" role="dialog" data-bs-backdrop="static"
  data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered modal-md">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <div class="form-header modal-header-title text-start mb-0">
          <h4 class="mb-0 modal-title">Update Resep Bahan Mentah</h4>
        </div>
        <button type="button" class="btn-close btn-close-fix-recipe" aria-label="Tutup">
        </button>
      </div>
      <form action="#">
        <div class="modal-body">
          <div class="form-groups-item border-0 pb-0">
            <div class="row g-2">
              <div class="col-12 col-md-7 mb-1">
                <div class="input-block mb-0">
                  <label>Produk<span class="text-danger">*</span></label>
                  <div id="fix_recipe_product_label" class="fw-semibold form-control-plaintext py-1"></div>
                  {{-- Product fixed in fix-from-error flow; keep select for updateProductionBom payload --}}
                  <select class="d-none fix-recipe-fill" id="fix_recipe_product_id" disabled></select>
                </div>
              </div>

              <div class="col-12 col-md-5 mb-1">
                <div class="input-block mb-0">
                  <label>Qty Produksi<span class="text-danger">*</span></label>
                  <div class="input-group input-group-sm">
                    <input type="text" class="form-control fix-recipe-fill number-only" id="fix_recipe_bom_qty"
                      placeholder="Qty" value="">
                    <select class="form-select fix-recipe-fill" id="fix_recipe_unit_id" style="max-width:42%;"></select>
                  </div>
                  <div id="fix_recipe_product_unit_info" class="mt-1" style="display:none;"></div>
                </div>
              </div>

              <div class="col-12">
                <div class="table-responsive">
                  <table class="table table-center table-sm mb-0" id="fix_recipe_tableSupply">
                    <thead>
                      <tr>
                        <th>Nama Bahan</th>
                        <th style="width: 14%;">Qty</th>
                        <th style="width: 28%;">Satuan</th>
                        <th class="no-sort text-center" style="width: 8%;">Aksi</th>
                      </tr>
                    </thead>
                    <tbody></tbody>
                  </table>
                </div>
              </div>

              <div class="col-12">
                <div class="row g-2 align-items-end fix-recipe-add-strip">
                  <div class="col-12 col-md-5 col-lg-5">
                    <div class="input-block">
                      <label>Nama Bahan<span class="text-danger">*</span></label>
                      <select class="form-select form-select-sm fix-recipe-fill-supply" id="fix_recipe_supplies_id"></select>
                    </div>
                  </div>

                  <div class="col-5 col-md-2 col-lg-2">
                    <div class="input-block">
                      <label>Qty<span class="text-danger">*</span></label>
                      <input type="text" class="form-control form-control-sm fix-recipe-fill-supply number-only"
                        id="fix_recipe_bom_detail_qty" placeholder="Qty">
                    </div>
                  </div>

                  <div class="col-5 col-md-4 col-lg-4">
                    <div class="input-block">
                      <label>Satuan<span class="text-danger">*</span></label>
                      <select class="form-select form-select-sm fix-recipe-fill-supply" id="fix_recipe_unit_supplies_id"></select>
                      <div id="fix_recipe_supplies_unit_info" class="mt-1"
                        style="display:none;"></div>
                    </div>
                  </div>

                  <div class="col-2 col-md-1 col-lg-1">
                    <a class="btn btn-primary btn-sm btn-fix-recipe-add-supply w-100">+</a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer justify-content-end">
          <button type="button" class="btn btn-back cancel-btn me-2 btn-close-fix-recipe">Batal</button>
          <button type="button" class="btn btn-primary paid-continue-btn btn-fix-recipe-save">Update Resep</button>
        </div>
      </form>
    </div>
  </div>
</div>
