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
    <div class="modal modal-lg custom-modal fade" id="add_product" role="dialog" tabindex="-1">
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
                                        <input type="text" class="form-control fill" id="product_name"
                                            placeholder="Enter Product Name">
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="input-block mb-3">
                                        <label>Category<span class="text-danger">*</span></label>
                                        <select class="form-select fill select2" id="product_category">
										</select>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="input-block mb-3">
                                        <label>Unit<span class="text-danger">*</span></label>
                                        <select class="form-select select2  fill" id="product_unit"  name="product_unit[]" multiple="multiple">

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
                                                <select name="" id="product_variant" class="form-select select2">
                                                </select>
                                            </div>
                                            <div class="col-3">
                                                 <button type="button" class="btn btn-primary btnAddRow"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i></button>
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
                                        <tbody id="tbVariant">
                                           
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <hr>
                            <div class="col-12">
                                <div class="input-block mb-3">
                                    <label>Relasi Unit<span class="text-danger">*</span></label>
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <td>Name Unit 1</td>
                                                <td>Name Unit 2</td>
                                                <td class="text-center" style="width:15%">Action</td>
                                            </tr>
                                        </thead>
                                        <tbody id="tbVariant">
                                           
                                        </tbody>
                                    </table>
                                   
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

@if (Route::is(['productIssue']))
    <!-- Add coupons -->
    <div class="modal fade" id="add-product-issues">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content ">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>Add Product Issues</h4>
                            </div>
                            <button type="button" class="close ms-auto" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <form action="coupons">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Product</label>
                                            <select class="form-select  select2 fill select2Input" id="product_id">
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="input-blocks mb-3">
                                            <label>Date</label>

                                            <div class="input-groupicon calender-input">
                                                <input type="text" class="datetimepicker form-control fill"
                                                    id="pi_date" placeholder="Select Date">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Jenis Retur</label>
                                            <select class="select" id="tipe_return">
                                                <option value="1" selected>Retur ke Supplier / Rusak Gudang</option>
                                                <option value="2">Customer Refund</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Type</label>
                                            <select class="select" id="pi_type">
                                                <option value="1" selected>Returned</option>
                                                <option value="2">Damaged</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Qty</label>
                                            <input type="text number-only" class="form-control number-only fill" id="pi_qty">
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Notes</label>
                                            <input type="text" class="form-control" id="pi_notes">
                                        </div>
                                    </div>
                                </div>


                                <div class="modal-footer">
                                     <button type="button" data-bs-dismiss="modal"
                                        class="btn btn-back cancel-btn me-2">Cancel</button>
                                    <button type="button" class="btn btn-primary paid-continue-btn btn-save">Create Product
                                        Issues</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Add Coupons -->
@endif
@if (Route::is(['production']))
    <div class="modal modal-lg custom-modal fade" id="addProduction" aria-modal="true" role="dialog" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content ">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Add Production</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-lg-6 col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Date</label>
                                        <input type="date" class="form-control fill" id="production_date">
                                    </div>
                                </div>
                                <div class="col-lg-6"></div>
                                <div class="col-lg-6 col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Product</label>
                                        <select class="form-select fill" id="product_id"></select>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Qty Production</label>
                                        <input type="text" class="form-control fill number-only" id="production_qty" placeholder="Qty Production">
                                    </div>
                                </div>
                                <div class="col-12 border py-3 mb-3">
                                    <table class="table table-center table-hover" id="tableSupply" style="min-height: 15vh">
                                        <thead>
                                            <th>Supply Name</th>
                                            <th>Qty</th>
                                            <th>Unit</th>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a class="btn btn-secondary-outline btn-cancel me-2" data-bs-dismiss="modal">Cancel</a>
                        <a class="btn btn-primary btn-save">Add Production</a>
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
                                        <input type="text" class="form-control fill" id="supplies_name"
                                            placeholder="Enter Supplies Name">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="input-block mb-3">
                                        <label>Unit<span class="text-danger">*</span></label>
                                        <select id="supplies_unit" class="fill" multiple="multiple" name="units[]"></select>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="input-block mb-3">
                                        <label>Description</label>
                                        <textarea class="form-control" id="supplies_desc" cols="30" rows="5"></textarea>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="input-block">
                                        <label>Relation Unit<span class="text-danger">*</span></label>
                                        <div class="input-block mb-3 row relationContainer">
                                            <div class="col-2">
                                                <label id="pu_id_1">-</label>
                                                <input type="text" class="form-control fill" id="supplies_stock1"
                                                placeholder="Enter Supplies Stock">
                                            </div>
                                            <div class="col-1 pt-4 fs-3 px-0 mx-0 text-center">
                                                =
                                            </div>
                                            <div class="col-2">
                                                <label id="pu_id_2">-</label>
                                                <input type="text" class="form-control fill" id="supplies_stock2"
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


@if (Route::is(['staff']))
    <!-- Delete Items Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Users</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <a href="#" class="btn btn-primary paid-continue-btn">Delete</a>
                            </div>
                            <div class="col-6">
                                <a href="#" data-bs-dismiss="modal"
                                    class="btn btn-primary paid-cancel-btn">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Delete Items Modal -->

    
    <!-- Add User -->
    <div class="modal custom-modal modal-lg fade" id="add_user" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="mb-0">Add Staff</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">

                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card-body">
                                    <div class="form-groups-item">
                                        <h5 class="form-title">Profile Picture</h5>
                                        <div class="profile-picture">
                                            <div class="upload-profile">
                                                <div class="profile-img">
                                                    <img id="blah" class="avatar"
                                                        src="{{ URL::asset('/assets/img/profiles/avatar-10.jpg')}}" alt="profile-img">
                                                </div>
                                                <div class="add-profile">
                                                    <h5>Upload a New Photo</h5>
                                                    <span>Profile-pic.jpg</span>
                                                </div>
                                            </div>
                                            <div class="img-upload">
                                                <a class="btn btn-primary me-2">Upload</a>
                                                <a class="btn btn-remove">Remove</a>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="input-block mb-3">
                                                    <label>First Name</label>
                                                    <input type="text" class="form-control"
                                                        placeholder="Enter First Name">
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="input-block mb-3">
                                                    <label>Last Name</label>
                                                    <input type="text" class="form-control"
                                                        placeholder="Enter Last Name">
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="input-block mb-3">
                                                    <label>User Name</label>
                                                    <input type="text" class="form-control"
                                                        placeholder="Enter User Name">
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="input-block mb-3">
                                                    <label>Email</label>
                                                    <input type="email" class="form-control"
                                                        placeholder="Enter Email Address">
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="input-block mb-3">
                                                    <label>Phone Number</label>
                                                    <input type="text" class="form-control"
                                                        placeholder="Enter Phone Number" name="name">
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="input-block mb-3">
                                                    <label>Role</label>
                                                    <select class="select">
                                                        <option>Select Role</option>
                                                        <option>Role 1</option>
                                                        <option>Role 2</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="pass-group" id="3">
                                                    <div class="input-block">
                                                        <label>Password</label>
                                                        <input type="password" class="form-control pass-input"
                                                            placeholder="">
                                                        <span class="toggle-password feather-eye"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="pass-group" id="passwordInput2">
                                                    <div class="input-block">
                                                        <label>Confirm Password</label>
                                                        <input type="password" class="form-control pass-input"
                                                            placeholder="">
                                                        <span class="toggle-password feather-eye"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="input-block ">
                                                    <label>Status</label>
                                                    <select class="select">
                                                        <option>Select Status</option>
                                                        <option>Active</option>
                                                        <option>Inactive</option>
                                                    </select>
                                                </div>
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
                        <button type="submit" data-bs-dismiss="modal"
                            class="btn btn-primary paid-continue-btn">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Add User -->
@endif

@if (Route::is(['cash']))
    <!-- modal -->
    <div class="modal modal-lg custom-modal fade" id="add_cash" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Add Cash</h4>
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
                                        <label>Date<span class="text-danger">*</span></label>
                                        <input type="date" class="form-control fill" id="cash_date">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="input-block mb-3">
                                        <label>Description<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="cash_description"
                                            placeholder="Enter Description">
                                    </div>
                                </div>
                                <div class="row input-block">
                                    <label>Type<span class="text-danger">*</span></label>
                                    <div class="col-4">
                                        <select class="form-select" id="cash_select">
                                            <option value="debit" checked>Debit</option>
                                            <option value="credit1">Credit 1</option>
                                            <option value="credit2">Credit 2</option>
                                        </select>
                                    </div>
                                    <div class="col-8">
                                        <div class="input-group fix-nominal">
                                            <span class="input-group-text">Rp.</span>
                                            <input type="text" name="" id="cash_nominal" class="form-control fill number-only nominal_only" placeholder="Ex 10000">
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
                            class="btn btn-primary paid-continue-btn btn-save">Add Cash</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if (Route::is(['pettyCash']))
    <!-- modal -->
    <div class="modal modal-lg custom-modal fade" id="add_petty_cash" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Add Petty Cash</h4>
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
                                        <label>Date<span class="text-danger">*</span></label>
                                        <input type="date" class="form-control fill" id="pc_date">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="input-block mb-3">
                                        <label>Description<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="pc_description"
                                            placeholder="Enter Description">
                                    </div>
                                </div>
                                <div class="row input-block">
                                    <label>Type<span class="text-danger">*</span></label>
                                    <div class="col-4">
                                        <select class="form-select" id="pc_select">
                                            <option value="debit" checked>Debit</option>
                                            <option value="credit">Credit</option>
                                        </select>
                                    </div>
                                    <div class="col-8">
                                        <div class="input-group fix-nominal">
                                            <span class="input-group-text">Rp.</span>
                                            <input type="text" name="" id="pc_nominal" class="form-control fill number-only nominal_only" placeholder="Ex 10000">
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
                            class="btn btn-primary paid-continue-btn btn-save">Add Cash</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if (Route::is(['role']))
    <!-- modal -->
    <div class="modal modal-lg custom-modal fade" id="add_role" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Add Role</h4>
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
                                        <label>Role Name<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="role_name"
                                            placeholder="Enter Role Name">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Cancel</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Add Role</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if (Route::is(['bom']))
    <!-- modal -->
    <div class="modal modal-lg custom-modal fade" id="add_bom" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Add Bill of Material</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-8">
                                    <div class="input-block mb-3">
                                        <label>Product<span class="text-danger">*</span></label>
                                        <select class="form-select fill" id="product_id"></select>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="input-block mb-3">
                                        <label>Qty Produksi<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill number-only" id="bom_qty" placeholder="Qty Produksi">
                                    </div>
                                </div>
                                <div class="col-12 border py-3 mb-3">
                                    <table class="table table-center table-hover" id="tableSupply" style="min-height: 15vh">
                                        <thead>
                                            <th>Supply Name</th>
                                            <th>Qty</th>
                                            <th>Unit</th>
                                            <th class="no-sort">Action</th>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                                <div class="col-4">
                                    <div class="input-block mb-3">
                                        <label>Supply Name<span class="text-danger">*</span></label>
                                        <select class="form-select fill_supply" id="supplies_id"></select>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="input-block mb-3">
                                        <label>Qty<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill_supply number-only" id="bom_detail_qty" placeholder="Qty Supply">
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="input-block mb-3">
                                        <label>Unit Name<span class="text-danger">*</span></label>
                                        <select class="form-select fill_supply" id="unit_id"></select>
                                    </div>
                                </div>
                                <div class="col-1 pt-4">
                                    <a class="btn btn-primary btn-add-supply">+</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Cancel</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Add BoM</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif