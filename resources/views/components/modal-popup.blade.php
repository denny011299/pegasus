<!--- modal Delete -->
<div class="modal fade" id="modalDelete" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Konfirmasi Delete</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <p id="text-delete" style="font-size:10pt"></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-cancel" data-dismiss="modal">Close</button>
          <button type="button" class="btn btn-danger btn-konfirmasi ms-2">Delete</button>
        </div>
      </div>
    </div>
  </div>

@if (Route::is(['category']))
    <!-- modal -->
    <div class="modal modal-lg custom-modal fade" id="add_category" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Add Category</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-12">
                                    <div class="input-block mb-3">
                                        <label>Category Name<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="category_name"
                                            placeholder="Enter Category Name">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Cancel</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Add Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endif

@if (Route::is(['unit']))
    <!-- modal -->
    <div class="modal modal-lg custom-modal fade" id="add_unit" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Add Unit</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-lg-6 col-md-12">
                                    <div class="input-block mb-3">
                                        <label>Unit Name<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="unit_name"
                                            placeholder="Enter Unit Name">
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-12">
                                    <div class="input-block mb-3">
                                        <label>Short Name<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="unit_short_name"
                                            placeholder="Enter Short Name">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Cancel</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Add Unit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endif

@if (Route::is(['variant']))
    <!-- modal -->
    <div class="modal modal-lg custom-modal fade" id="add_variant" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Add Variant</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-12">
                                    <div class="input-block mb-3">
                                        <label>Name<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="variant_name"
                                            placeholder="Enter Variant Name">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="input-block mb-3">
                                        <label>Variant<span class="text-danger">*</span></label>
                                        <select class="form-control tagging fill" id="variant_attribute" multiple="multiple">
											
										</select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Cancel</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Add Variant</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if (Route::is(['product']))
    <!-- modal -->
    <div class="modal modal-lg custom-modal fade" id="add_product" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Add Product</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-4">
                                    <div class="input-block mb-3">
                                        <label>Name<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="pr_name"
                                            placeholder="Enter Product Name">
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="input-block mb-3">
                                        <label>Category<span class="text-danger">*</span></label>
                                        <select class="form-control fill" id="pr_category">
											<option value="">Padat</option>
											<option value="">Cair</option>
											<option value="">Gas</option>
										</select>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="input-block mb-3">
                                        <label>Unit<span class="text-danger">*</span></label>
                                        <select class="form-control tagging fill" id="pr_unit" multiple="multiple">
											
										</select>
                                    </div>
                                </div>
                                <hr>
                                <div class="row mb-3">
                                    <div class="col-8">
                                        <label>Variant Product</label>
                                    </div>
                                    <div class="col-4 text-end">
                                        <div class="row">
                                            <div class="col-9">
                                                <select name="" id="" class="form-select">
                                                    <option value="">250 Ml</option>
                                                    <option value="">500 Ml</option>
                                                </select>
                                            </div>
                                            <div class="col-3">
                                                 <button class="btn btn-primary"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="table-responsive">

                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <td>Name Variant</td>
                                                <td>SKU</td>
                                                <td>Price</td>
                                                <td>Barcode</td>
                                                <td class="text-center" style="width:15%">Action</td>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><input type="text" class="form-control" name="" id=""></td>
                                                <td><input type="text" class="form-control" name="" id=""></td>
                                                <td><input type="text" class="form-control" name="" id=""></td>
                                                <td><input type="text" class="form-control" name="" id=""></td>
                                                <td class="text-center d-flex align-items-center">
                                                    <a class="p-2 btn-action-icon btn_delete mx-auto" data-id="${e[i].pr_id}" href="javascript:void(0);">
                                                        <i data-feather="trash-2" class="feather-trash-2"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <hr>
                            <div class="col-12">
                                <div class="input-block mb-3">
                                    <label>Relasi Unit<span class="text-danger">*</span></label>
                                    <div class="row">
                                        <div class="col-2">
                                            <label>Dus</label>
                                            <input type="text" class="form-control fill" id="sup_stock1"
                                            placeholder="Enter Supplies Stock">
                                        </div>
                                        <div class="col-1 pt-4 fs-3 px-0 mx-0 text-center">
                                            =
                                        </div>
                                        <div class="col-2">
                                            <label>Botol</label>
                                            <input type="text" class="form-control fill" id="sup_stock2"
                                            placeholder="Enter Supplies Stock">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Cancel</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endif

@if (Route::is(['supplies']))
    <!-- modal -->
    <div class="modal modal-lg custom-modal fade" id="add_supplies" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Add Supplies</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-12">
                                    <div class="input-block mb-3">
                                        <label>Name<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="sup_name"
                                            placeholder="Enter Supplies Name">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="input-block mb-3">
                                        <label>Unit<span class="text-danger">*</span></label>
                                        <select class="form-control tagging fill" id="sup_unit" multiple="multiple">
											
										</select>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="input-block mb-3">
                                        <label>Description</label>
                                        <textarea class="form-control" id="sup_desc" cols="30" rows="5"></textarea>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="input-block">
                                        <label>Relation Unit<span class="text-danger">*</span></label>
                                        <div class="input-block mb-3 d-flex">
                                            <div class="col-3">
                                                <label>Dus</label>
                                                <input type="text" class="form-control fill" id="sup_stock1"
                                                placeholder="Enter Supplies Stock">
                                            </div>
                                            <div class="col-1 pt-4 fs-2 text-center">
                                                =
                                            </div>
                                            <div class="col-3">
                                                <label>Botol</label>
                                                <input type="text" class="form-control fill" id="sup_stock2"
                                                placeholder="Enter Supplies Stock">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Cancel</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Add Supplies</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if (Route::is(['salesOrder']))
    <!-- modal -->
    <div class="modal modal-lg custom-modal fade" id="add_sales_order" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Add Sales Order</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-12 row">
                                    <div class="col-6">
                                        <div class="input-block">
                                            <label>Customer Name<span class="text-danger">*</span></label>
                                            <select id="so_name" class="form-control fill"></select>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="input-block mb-3">
                                            <label>Date<span class="text-danger">*</span></label>
                                            <input type="date" class="form-control fill" id="so_date">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 row">
                                    <div class="col-6">
                                        <div class="input-block">
                                            <label>Discount<span class="text-danger">*</span></label>
                                            <select id="so_discount" class="form-control fill">
                                                <option value="0" checked>0</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="input-block mb-3">
                                            <label>Delivery Cost<span class="text-danger">*</span></label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text">Rp </span>
                                                <input type="text" class="form-control fill" id="so_cost" value="0" placeholder="20.000">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="input-block mb-3">
                                        <label>SKU/Barcode<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="so_sku"
                                        placeholder="SKU Product">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <table class="table table-center table-hover" id="tableSalesModal">
                                        <thead>
                                            <th>Product</th>
                                            <th>Variant</th>
                                            <th>SKU</th>
                                            <th>Qty</th>
                                            <th>Unit Price</th>
                                            <th>Subtotal</th>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                                <div class="col-12 row pt-3">
                                    <div class="col-6"></div>
                                    <div class="col-6">
                                        <div class="d-flex justify-content-between">
                                            <p>Ppn</p>
                                            <p>0</p>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <p>Diskon</p>
                                            <p>0</p>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <p>Biaya Pengiriman</p>
                                            <p>0</p>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <p>Grand Total</p>
                                            <p>400000</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Cancel</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Add Sales Order</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if (Route::is(['salesOrderDetail']))
    <!-- modal -->
    <div class="modal modal-lg custom-modal fade" id="add_sales_delivery" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Add Delivery Notes</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-12 row">
                                    <div class="col-4">
                                        <div class="input-block">
                                            <label>Receiver Name<span class="text-danger">*</span></label>
                                            <input type="text" class="form-control fill" id="sod_name">
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="input-block mb-3">
                                            <label>Date<span class="text-danger">*</span></label>
                                            <input type="date" class="form-control fill" id="sod_date">
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="input-block mb-3">
                                            <label>Phone Number<span class="text-danger">*</span></label>
                                            <input type="text" class="form-control fill" id="sod_phone">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 row">
                                    <div class="col-6">
                                        <div class="input-block mb-3">
                                            <label>Address<span class="text-danger">*</span></label>
                                            <textarea class="form-control fill" id="sod_address" cols="30" rows="5"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="input-block mb-3">
                                            <label>Description<span class="text-danger">*</span></label>
                                            <textarea class="form-control fill" id="sod_desc" cols="30" rows="5"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <table class="table table-center table-hover" id="tableSalesDelivery">
                                        <thead>
                                            <th>Product</th>
                                            <th>SKU</th>
                                            <th>Category</th>
                                            <th>Qty</th>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Cancel</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- modal -->
    <div class="modal modal-lg custom-modal fade" id="add_sales_invoice" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Add Invoice</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-12">
                                    <div class="input-block">
                                        <label>Invoice Number<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="soi_code">
                                    </div>
                                </div>
                                <div class="col-12 row">
                                    <div class="col-6">
                                        <div class="input-block mb-3">
                                            <label>Invoice Date<span class="text-danger">*</span></label>
                                            <input type="date" class="form-control fill" id="soi_date">
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="input-block mb-3">
                                            <label>Due Date<span class="text-danger">*</span></label>
                                            <input type="date" class="form-control fill" id="soi_due">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="input-block">
                                        <label>Amount<span class="text-danger">*</span></label>
                                        <div class="input-group mb-3">
                                            <span class="input-group-text">Rp </span>
                                            <input type="text" class="form-control fill" id="soi_total" value="0" placeholder="20.000">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Cancel</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Add Invoice</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if (Route::is(['purchaseOrder']))
    <!-- modal -->
    <div class="modal modal-lg custom-modal fade" id="add_purchase_order" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Add Purchase Order</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-12 row">
                                    <div class="col-6">
                                        <div class="input-block">
                                            <label>Supplier Name<span class="text-danger">*</span></label>
                                            <select id="po_name" class="form-control fill"></select>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="input-block mb-3">
                                            <label>Date<span class="text-danger">*</span></label>
                                            <input type="date" class="form-control fill" id="po_date">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="input-block mb-3">
                                        <label>Notes<span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="po_notes" cols="30" rows="5"></textarea>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="input-block mb-3">
                                        <label>Product Name<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="po_sku"
                                        placeholder="Product Code">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <table class="table table-center table-hover" id="tablePurchaseModal">
                                        <thead>
                                            <th>Product</th>
                                            <th>Qty</th>
                                            <th>Purchase Price</th>
                                            <th>Discount</th>
                                            <th>Unit Cost</th>
                                            <th>Subtotal</th>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                                <div class="col-12 row pt-3">
                                    <div class="col-6"></div>
                                    <div class="col-6">
                                        <div class="d-flex justify-content-between">
                                            <p>Ppn</p>
                                            <p>0</p>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <p>Diskon</p>
                                            <p>0</p>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <p>Biaya Pengiriman</p>
                                            <p>0</p>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <p>Grand Total</p>
                                            <p>320000</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Cancel</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Add Purchase Order</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if (Route::is(['purchaseOrderDetail']))
    <!-- modal -->
    <div class="modal modal-lg custom-modal fade" id="add_purchase_delivery" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Add Delivery Notes</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-12 row">
                                    <div class="col-4">
                                        <div class="input-block">
                                            <label>Receiver Name<span class="text-danger">*</span></label>
                                            <input type="text" class="form-control fill" id="pod_name">
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="input-block mb-3">
                                            <label>Date<span class="text-danger">*</span></label>
                                            <input type="date" class="form-control fill" id="pod_date">
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="input-block mb-3">
                                            <label>Phone Number<span class="text-danger">*</span></label>
                                            <input type="text" class="form-control fill" id="pod_phone">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 row">
                                    <div class="col-6">
                                        <div class="input-block mb-3">
                                            <label>Address<span class="text-danger">*</span></label>
                                            <textarea class="form-control fill" id="pod_address" cols="30" rows="5"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="input-block mb-3">
                                            <label>Description<span class="text-danger">*</span></label>
                                            <textarea class="form-control fill" id="pod_desc" cols="30" rows="5"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <table class="table table-center table-hover" id="tablePurchaseDelivery">
                                        <thead>
                                            <th>Product</th>
                                            <th>SKU</th>
                                            <th>Category</th>
                                            <th>Qty</th>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Cancel</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- modal -->
    <div class="modal modal-lg custom-modal fade" id="add_purchase_invoice" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Add Invoice</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-12">
                                    <div class="input-block">
                                        <label>Invoice Number<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="poi_code">
                                    </div>
                                </div>
                                <div class="col-12 row">
                                    <div class="col-6">
                                        <div class="input-block mb-3">
                                            <label>Invoice Date<span class="text-danger">*</span></label>
                                            <input type="date" class="form-control fill" id="poi_date">
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="input-block mb-3">
                                            <label>Due Date<span class="text-danger">*</span></label>
                                            <input type="date" class="form-control fill" id="poi_due">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="input-block">
                                        <label>Amount<span class="text-danger">*</span></label>
                                        <div class="input-group mb-3">
                                            <span class="input-group-text">Rp </span>
                                            <input type="text" class="form-control fill" id="poi_total" value="0" placeholder="20.000">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Cancel</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Add Invoice</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif