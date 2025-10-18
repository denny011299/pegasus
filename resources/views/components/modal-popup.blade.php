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
                        <h4 class="mb-0 modal-title">Tambah Kategori</h4>
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
                                        <label>Nama Kategori<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="category_name"
                                            placeholder="Input Nama Kategori">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Tambah Kategori</button>
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
                        <h4 class="mb-0 modal-title">Tambah Satuan</h4>
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
                                        <label>Nama Satuan<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="unit_name"
                                            placeholder="Input Nama Satuan">
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-12">
                                    <div class="input-block mb-3">
                                        <label>Singkatan<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="unit_short_name"
                                            placeholder="Input Singkatan">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Tambah Satuan</button>
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
                        <h4 class="mb-0 modal-title">Tambah Variasi</h4>
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
                                        <label>Nama<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="variant_name"
                                            placeholder="Input Nama Variasi">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="input-block mb-3">
                                        <label>Variasi<span class="text-danger">*</span></label>
                                        <select class="form-control tagging fill" id="variant_attribute" multiple="multiple">
											
										</select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Tambah Variasi</button>
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
                                <h4 class="moda-title">Tambah Produk Bermasalah</h4>
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
                                            <label class="form-label">Produk</label>
                                            <select class="form-select  select2 fill select2Input" id="product_id">
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="input-blocks mb-3">
                                            <label>Tanggal</label>

                                            <div class="input-groupicon calender-input">
                                                <input type="text" class="datetimepicker form-control fill"
                                                    id="pi_date" placeholder="Pilih Tanggal">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Jenis Retur</label>
                                            <select class="select" id="tipe_return">
                                                <option value="1" selected>Retur ke Supplier / Rusak Gudang</option>
                                                <option value="2">Pengembalian Pelanggan</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Tipe</label>
                                            <select class="select" id="pi_type">
                                                
                                            </select>
                                        </div>
                                    </div>
                                   <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Jumlah</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control number-only fill" id="pi_qty">
                                                <select class="form-select w-25 fill" id="unit_id">
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Catatan</label>
                                            <input type="text" class="form-control" id="pi_notes">
                                        </div>
                                    </div>
                                </div>


                                <div class="modal-footer">
                                     <button type="button" data-bs-dismiss="modal"
                                        class="btn btn-back cancel-btn me-2">Batal</button>
                                    <button type="button" class="btn btn-primary paid-continue-btn btn-save">Tambah Produk
                                        </button>
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
                        <h4 class="mb-0 modal-title">Tambah Produksi</h4>
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
                                        <label class="form-label">Tanggal</label>
                                        <input type="date" class="form-control fill" id="production_date">
                                    </div>
                                </div>
                                <div class="col-lg-6"></div>
                                <div class="col-lg-6 col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Produk</label>
                                        <select class="form-select fill" id="product_id"></select>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Jumlah Produksi</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control fill number-only" id="production_qty" placeholder="Jumlah Produksi" value="1">
                                            <select class="form-select w-25" id="unit_id">
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 py-3 mb-3">
                                    <table class="table table-center" id="tableSupply" style="min-height: 15vh">
                                        <thead>
                                            <th>Nama Bahan Mentah</th>
                                            <th class="text-center">Jumlah</th>
                                            <th>Satuan</th>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a class="btn btn-secondary-outline btn-cancel me-2" data-bs-dismiss="modal">Batal</a>
                        <a class="btn btn-primary btn-save">Tambah Produksi</a>
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
                        <h4 class="mb-0 modal-title">Tambah Bahan Mentah</h4>
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
                                        <label>Nama<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="supplies_name"
                                            placeholder="Input Nama Bahan Mentah">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="input-block mb-3">
                                        <label>Satuan<span class="text-danger">*</span></label>
                                        <select id="supplies_unit" class="form-select fill"></select>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="input-block mb-3">
                                        <label>Deskripsi<span class="text-danger">*</span></label>
                                        <textarea class="form-control fill" id="supplies_desc" cols="30" rows="5"></textarea>
                                    </div>
                                </div>
                                <hr>
                                <div class="row mb-3">
                                    <div class="col-8">
                                        <label>Variasi Bahan</label>
                                    </div>
                                    <div class="col-4 text-end">
                                        <div class="row">
                                            <div class="col-9">
                                                <select name="" id="supplies_variant" class="form-select select2">
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
                                                <td>Nama Variasi<span class="text-danger">*</span></td>
                                                <td>SKU<span class="text-danger">*</span></td>
                                                <td>Harga<span class="text-danger">*</span></td>
                                                <td>Barcode</td>
                                                <td class="text-center" style="width:15%">Aksi</td>
                                            </tr>
                                        </thead>
                                        <tbody id="tbVariant">
                                           
                                        </tbody>
                                    </table>
                                </div>
                                {{-- 
                                <div class="col-12">
                                    <div class="input-block">
                                        <label>Relation Unit<span class="text-danger">*</span></label>
                                        <div class="input-block mb-3 row relationContainer">
                                            <div class="col-2">
                                                <label id="pu_id_1">-</label>
                                                <input type="text" class="form-control fill" id="supplies_stock1"
                                                placeholder="Input Stock Bahan">
                                            </div>
                                            <div class="col-1 pt-4 fs-3 px-0 mx-0 text-center">
                                                =
                                            </div>
                                            <div class="col-2">
                                                <label id="pu_id_2">-</label>
                                                <input type="text" class="form-control fill" id="supplies_stock2"
                                                placeholder="Input Stock Bahan">
                                            </div>
                                        </div>
                                    </div>
                                </div>--}}
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Tambah Bahan Mentah</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if (Route::is(['salesOrder']))
    <!-- modal -->
    <div class="modal modal-lg custom-modal fade" id="add_sales_order" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Tambah Pesanan Penjualan</h4>
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
                                        <div class="input-block mb-3">
                                            <label>Tanggal<span class="text-danger">*</span></label>
                                            <input type="date" class="form-control fill" id="so_date">
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="input-block mb-3">
                                            <label>Metode Pembayaran<span class="text-danger">*</span></label>
                                            <select id="so_payment" class="form-select fill">
                                                <option value="1" checked>Tunai</option>
                                                <option value="2">Transfer Bank</option>
                                                <option value="3">Cek</option>
                                                <option value="4">Kredit</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="input-block mb-3">
                                            <label>Nama Pelanggan<span class="text-danger">*</span></label>
                                            <select id="so_customer" class="form-control fill"></select>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="input-block mb-3">
                                            <label>Nama Sales<span class="text-danger">*</span></label>
                                            <select id="sales_id" class="form-control fill"></select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 row">
                                    <div class="col-4">
                                        <div class="input-block">
                                            <label>Diskon<span class="text-danger">*</span></label>
                                            <div class="input-group mb-3">
                                                <input type="text" class="form-control fill number-only" id="so_discount" 
                                                placeholder="Input Diskon" value="0">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="input-block">
                                            <label>PPN<span class="text-danger">*</span></label>
                                            <div class="input-group mb-3">
                                                <input type="text" class="form-control fill number-only" id="so_ppn" 
                                                placeholder="Input PPN" value="0">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="input-block mb-3">
                                            <label>Biaya Pengiriman<span class="text-danger">*</span></label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text">Rp </span>
                                                <input type="text" class="form-control fill number-only nominal_only" id="so_cost" value="0" placeholder="Input Biaya Pengiriman">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 row">
                                    <div class="col-6">
                                        <div class="input-block mb-3">
                                            <label>SKU/Barcode<span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="so_barcode"
                                            placeholder="SKU Produk">
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        
                                    </div>
                                </div>
                                <div class="col-12">
                                    <table class="table table-center">
                                        <thead>
                                            <th>Produk</th>
                                            <th>Variasi</th>
                                            <th>SKU</th>
                                            <th class="text-center">Jumlah</th>
                                            <th class="text-end">Harga Satuan</th>
                                            <th class="text-end">Subtotal</th>
                                        </thead>
                                        <tbody id="tableSalesModal">
                                            
                                        </tbody>
                                    </table>
                                </div>
                                <div class="col-12 row pt-3">
                                    <div class="col-6"></div>
                                    <div class="col-6">
                                        <div class="d-flex justify-content-between">
                                            <p>Total</p>
                                            <p id="value_total">0</p>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <p>Ppn</p>
                                            <p id="value_ppn">0</p>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <p>Diskon</p>
                                            <p id="value_discount">0</p>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <p>Biaya Pengiriman</p>
                                            <p id="value_cost">0</p>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <b>Grand Total</b>
                                            <b id="value_grand">0</b>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Tambah Pesanan Penjualan</button>
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
                        <h4 class="mb-0 modal-title">Tambah Catatan Pengiriman</h4>
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
                                            <label>Nama Penerima<span class="text-danger">*</span></label>
                                            <input type="text" class="form-control fill" id="sod_name" placeholder="Nama Penerima">
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="input-block mb-3">
                                            <label>Tanggal<span class="text-danger">*</span></label>
                                            <input type="date" class="form-control fill" id="sod_date">
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="input-block mb-3">
                                            <label>Nomor Telepon<span class="text-danger">*</span></label>
                                            <input type="text" class="form-control fill number-only" id="sod_phone">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 row">
                                    <div class="col-6">
                                        <div class="input-block mb-3">
                                            <label>Alamat<span class="text-danger">*</span></label>
                                            <textarea class="form-control fill" id="sod_address" cols="30" rows="5"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="input-block mb-3">
                                            <label>Deskripsi<span class="text-danger">*</span></label>
                                            <textarea class="form-control fill" id="sod_desc" cols="30" rows="5"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <table class="table table-center" id="tableSalesDelivery">
                                        <thead>
                                            <th>Produk</th>
                                            <th>SKU</th>
                                            <th>Kategori</th>
                                            <th>Jumlah</th>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Tambah Catatan Pengiriman</button>
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
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="mb-0 modal-title">Tambah Faktur Penjualan</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-12">
                                    <div class="input-block">
                                        <label>Nomor Faktur<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="soi_code">
                                    </div>
                                </div>
                                <div class="col-12 row">
                                    <div class="col-6">
                                        <div class="input-block mb-3">
                                            <label>Tanggal Faktur<span class="text-danger">*</span></label>
                                            <input type="date" class="form-control fill" id="soi_date">
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="input-block mb-3">
                                            <label>Jatuh Tempo<span class="text-danger">*</span></label>
                                            <input type="date" class="form-control fill" id="soi_due">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="input-block">
                                        <label>Jumlah<span class="text-danger">*</span></label>
                                        <div class="input-group mb-3">
                                            <span class="input-group-text">Rp </span>
                                            <input type="text" class="form-control fill number-only nominal_only" id="soi_total" value="0" placeholder="20.000">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Tambah Faktur</button>
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
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="mb-0 modal-title">Tambah Purchase Order</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-12 row">
                                    <div class="col-6">
                                        <div class="input-block">
                                            <label>Nama Pemasok<span class="text-danger">*</span></label>
                                            <select id="po_supplier" class="form-control fill"></select>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="input-block mb-3">
                                            <label>Tanggal<span class="text-danger">*</span></label>
                                            <input type="date" class="form-control fill" id="po_date">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 row">
                                    <div class="col-4">
                                        <div class="input-block">
                                            <label>Diskon<span class="text-danger">*</span></label>
                                            <div class="input-group mb-3">
                                                <input type="text" class="form-control fill number-only" id="po_discount" 
                                                placeholder="Input Diskon" value="0">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="input-block">
                                            <label>PPN<span class="text-danger">*</span></label>
                                            <div class="input-group mb-3">
                                                <input type="text" class="form-control fill number-only" id="po_ppn" 
                                                placeholder="Input PPN" value="0">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="input-block mb-3">
                                            <label>Biaya Pengiriman<span class="text-danger">*</span></label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text">Rp </span>
                                                <input type="text" class="form-control fill number-only nominal_only" id="po_cost" value="0" placeholder="Input Biaya Pengiriman">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="input-block mb-3">
                                        <label>SKU/Barcode Produk<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control " id="po_sku"
                                        placeholder="SKU/Barcode Produk">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <table class="table table-center" id="tablePurchaseModal">
                                        <thead>
                                            <th>Produk</th>
                                            <th>Variasi</th>
                                            <th>SKU</th>
                                            <th>Qty</th>
                                            <th class="text-end">Harga Beli</th>
                                            <th class="text-end">Subtotal</th>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                                <div class="col-12 row pt-3">
                                    <div class="col-6"></div>
                                    <div class="col-6">
                                        <div class="d-flex justify-content-between">
                                            <p>Total</p>
                                            <p id="value_total">Rp. 0</p>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <p>Diskon</p>
                                            <p id="value_discount">Rp.0</p>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <p>Ppn</p>
                                            <p id="value_ppn">Rp. 0</p>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <p>Biaya Pengiriman</p>
                                            <p id="value_cost">Rp. 0</p>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <b>Grand Total</b>
                                            <b id="value_grand">Rp. 0</b>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Tambah Purchase Order</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@if (Route::is(['purchaseOrderDetail']))
    <!-- modal: Tambah Delivery Notes -->
    <div class="modal fade custom-modal" id="add_purchase_delivery" role="dialog" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="mb-0 modal-title">Tambah Delivery Notes</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row g-3">
                                <div class="col-12 col-md-4">
                                    <div class="input-block">
                                        <label>Nama Penerima<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="pdo_receiver" placeholder="Input nama penerima">
                                    </div>
                                </div>
                                <div class="col-12 col-md-4">
                                    <div class="input-block">
                                        <label>Tanggal<span class="text-danger">*</span></label>
                                        <input type="date" class="form-control fill" id="pdo_date">
                                    </div>
                                </div>
                                <div class="col-12 col-md-4">
                                    <div class="input-block">
                                        <label>No. Telepon<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill number-only" id="pdo_phone" placeholder="Input nomor telepon">
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="input-block">
                                        <label>Alamat<span class="text-danger">*</span></label>
                                        <textarea class="form-control fill" id="pdo_address" rows="3" placeholder="Alamat penerima"></textarea>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="input-block">
                                        <label>Keterangan<span class="text-danger">*</span></label>
                                        <textarea class="form-control fill" id="pdo_desc" rows="3" placeholder="Keterangan pengiriman"></textarea>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="table-responsive">
                                        <table class="table table-center table-bordered align-middle" id="tablePurchaseDelivery">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Supplies</th>
                                                    <th>SKU</th>
                                                    <th>Qty</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex justify-content-end">
                        <button type="button" data-bs-dismiss="modal" class="btn btn-outline-secondary me-2">Batal</button>
                        <button type="button" class="btn btn-primary btn-save-delivery">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- modal: Tambah Faktur Pembelian -->
    <div class="modal fade custom-modal" id="add_purchase_invoice" role="dialog" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="mb-0 modal-title">Tambah Faktur Pembelian</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="input-block">
                                        <label>Nomor Faktur<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="poi_code" placeholder="Masukkan nomor faktur">
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="input-block">
                                        <label>Tanggal Faktur<span class="text-danger">*</span></label>
                                        <input type="date" class="form-control fill" id="poi_date">
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="input-block">
                                        <label>Jatuh Tempo<span class="text-danger">*</span></label>
                                        <input type="date" class="form-control fill" id="poi_due">
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="input-block">
                                        <label>Jumlah<span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" class="form-control fill number_only" id="poi_total" value="0" placeholder="Masukkan jumlah">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex justify-content-end">
                        <button type="button" data-bs-dismiss="modal" class="btn btn-outline-secondary me-2">Batal</button>
                        <button type="button" class="btn btn-primary btn-save-invoice">Tambah Faktur</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif


@if (Route::is(['staff']))
    <!-- Hapus User Modal -->
    <div class="modal custom-modal fade" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Hapus User</h3>
                        <p>Apakah kamu yakin ingin menghapus?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <div class="col-6">
                                <a href="#" class="btn btn-primary paid-continue-btn">Hapus</a>
                            </div>
                            <div class="col-6">
                                <a href="#" data-bs-dismiss="modal"
                                    class="btn btn-primary paid-cancel-btn">Batal</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Hapus User Modal -->

    
    <!-- Tambah User -->
    <div class="modal custom-modal modal-lg fade" id="add_user" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title text-start mb-0">
                        <h4 class="mb-0">Tambah Staf</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup">

                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card-body">
                                    <div class="form-groups-item">
                                        <h5 class="form-title">Foto Profil</h5>
                                        <div class="profile-picture">
                                            <div class="upload-profile">
                                                <div class="profile-img">
                                                    <img id="blah" class="avatar"
                                                        src="{{ URL::asset('/assets/img/profiles/avatar-10.jpg')}}" alt="profile-img">
                                                </div>
                                                <div class="add-profile">
                                                    <h5>Upload Foto Baru</h5>
                                                    <span>Profile-pic.jpg</span>
                                                </div>
                                            </div>
                                            <div class="img-upload">
                                                <a class="btn btn-primary me-2">Upload</a>
                                                <a class="btn btn-remove">Hapus</a>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="input-block mb-3">
                                                    <label>Nama Depan</label>
                                                    <input type="text" class="form-control"
                                                        placeholder="Masukkan Nama Depan">
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="input-block mb-3">
                                                    <label>Nama Belakang</label>
                                                    <input type="text" class="form-control"
                                                        placeholder="Masukkan Nama Belakang">
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="input-block mb-3">
                                                    <label>Username</label>
                                                    <input type="text" class="form-control"
                                                        placeholder="Masukkan Username">
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="input-block mb-3">
                                                    <label>Email</label>
                                                    <input type="email" class="form-control"
                                                        placeholder="Masukkan Email">
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="input-block mb-3">
                                                    <label>No. Telepon</label>
                                                    <input type="text" class="form-control"
                                                        placeholder="Masukkan Nomor Telepon" name="name">
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="input-block mb-3">
                                                    <label>Peran</label>
                                                    <select class="select">
                                                        <option>Pilih Peran</option>
                                                        <option>Peran 1</option>
                                                        <option>Peran 2</option>
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
                                                        <label>Konfirmasi Password</label>
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
                                                        <option>Pilih Status</option>
                                                        <option>Aktif</option>
                                                        <option>Tidak Aktif</option>
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
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="submit" data-bs-dismiss="modal"
                            class="btn btn-primary paid-continue-btn">Tambah User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Tambah User -->
@endif

@if (Route::is(['cash']))
    <!-- modal -->
    <div class="modal modal-lg custom-modal fade" id="add_cash" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Tambah Kas</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-12">
                                    <div class="input-block mb-3">
                                        <label>Tanggal<span class="text-danger">*</span></label>
                                        <input type="date" class="form-control fill" id="cash_date">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="input-block mb-3">
                                        <label>Keterangan<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="cash_description"
                                            placeholder="Masukkan Keterangan">
                                    </div>
                                </div>
                                <div class="row input-block">
                                    <label>Tipe<span class="text-danger">*</span></label>
                                    <div class="col-4">
                                        <select class="form-select" id="cash_select">
                                            <option value="debit" checked>Debit</option>
                                            <option value="credit1">Kredit 1</option>
                                            <option value="credit2">Kredit 2</option>
                                        </select>
                                    </div>
                                    <div class="col-8">
                                        <div class="input-group fix-nominal">
                                            <span class="input-group-text">Rp.</span>
                                            <input type="text" name="" id="cash_nominal" class="form-control fill number-only nominal_only" placeholder="Contoh 10000">
                                        </div> 
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Tambah Kas</button>
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
                        <h4 class="mb-0 modal-title">Tambah Kas Kecil</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-12">
                                    <div class="input-block mb-3">
                                        <label>Tanggal<span class="text-danger">*</span></label>
                                        <input type="date" class="form-control fill" id="pc_date">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="input-block mb-3">
                                        <label>Keterangan<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="pc_description"
                                            placeholder="Masukkan Keterangan">
                                    </div>
                                </div>
                                <div class="row input-block">
                                    <label>Tipe<span class="text-danger">*</span></label>
                                    <div class="col-4">
                                        <select class="form-select" id="pc_select">
                                            <option value="debit" checked>Debit</option>
                                            <option value="credit">Kredit</option>
                                        </select>
                                    </div>
                                    <div class="col-8">
                                        <div class="input-group fix-nominal">
                                            <span class="input-group-text">Rp.</span>
                                            <input type="text" name="" id="pc_nominal" class="form-control fill number-only nominal_only" placeholder="Contoh 10000">
                                        </div> 
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Tambah Kas</button>
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
                        <h4 class="mb-0 modal-title">Tambah Peran</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-12">
                                    <div class="input-block mb-3">
                                        <label>Nama Peran<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="role_name"
                                            placeholder="Masukkan Nama Peran">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Tambah Peran</button>
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
                        <h4 class="mb-0 modal-title">Tambah Resep Bahan</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-8">
                                    <div class="input-block mb-3">
                                        <label>Produk<span class="text-danger">*</span></label>
                                        <select class="form-select fill" id="product_id"></select>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="input-block mb-3">
                                        <label>Qty Produksi<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill number-only" id="bom_qty" placeholder="Qty Produksi">
                                    </div>
                                </div>
                                <div class="col-12 py-3 mb-3">
                                    <table class="table table-center" id="tableSupply" style="min-height: 15vh">
                                        <thead>
                                            <th>Nama Bahan</th>
                                            <th>Qty</th>
                                            <th>Satuan</th>
                                            <th class="no-sort text-center">Aksi</th>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                                <div class="col-4">
                                    <div class="input-block mb-3">
                                        <label>Nama Bahan<span class="text-danger">*</span></label>
                                        <select class="form-select fill_supply" id="supplies_id"></select>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="input-block mb-3">
                                        <label>Qty<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill_supply number-only" id="bom_detail_qty" placeholder="Qty Bahan">
                                    </div>
                                </div>
                                {{-- 
                                <div class="col-4">
                                    <div class="input-block mb-3">
                                        <label>Nama Satuan<span class="text-danger">*</span></label>
                                        <select class="form-select fill_supply" id="unit_id"></select>
                                    </div>
                                </div>--}}
                                <div class="col-1 pt-4">
                                    <a class="btn btn-primary btn-add-supply">+</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Tambah Resep</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if (Route::is(['area']))
    <!-- modal -->
    <div class="modal modal-lg custom-modal fade" id="add_area" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Tambah Wilayah</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-6">
                                    <div class="input-block mb-3">
                                        <label>Kode Wilayah<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="area_code"
                                            placeholder="Input Kode Wilayah">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="input-block mb-3">
                                        <label>Nama Wilayah<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="area_name"
                                            placeholder="Input Nama Wilayah">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Tambah Wilayah</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endif

@if (Route::is(['purchaseOrderDetail']))
    <!-- modal -->
    <div class="modal modal-lg custom-modal fade" id="modalTerima" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Penerbitan Tanda Terima</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="container-fluid">
                            <label>Detail Barang</label>
                            <small class="text-muted">Berikut adalah detail barang yang dipesan berdasarkan surat jalan</small>
                            
                            <table class="table table-center mt-2" id="tablePurchaseDelivery">
                                        <thead>
                                            <th>Supplies</th>
                                            <th>SKU</th>
                                            <th>Qty</th>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                            <label class="my-2">Detail Faktur</label>
                            <table class="table table-center table-hover" id="tableInvoice">
                                <thead>
                                    <th style="width:15%">Tgl. Pesanan</th>
                                    <th style="width:15%">Tgl. Jatuh Tempo</th>
                                    <th>No. Faktur</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th class="no-sort text-center">Aksi</th>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Konfirmasi Penerimaan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endif