<!--- modal Delete -->
<style>
    #video.rot90 { transform: rotate(90deg); }
    #video.rot180 { transform: rotate(180deg); }
    #video.rot270 { transform: rotate(270deg); }
 .is-invalid{
            border-color: #dc3545!important;
        }
        .is-invalids {
            border-color: #dc3545!important;
        }
</style>
<div class="modal fade" id="modalPhoto" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-body p-0">
            <div class="container-fluid">
                 <canvas id="canvas"  style="display:none;"></canvas>
            </div>
         
        </div>
        <div class="modal-footer ps-0 pe-0">
            
            <div id="camera">
                <video id="video" autoplay playsinline></video>
                <button id="rotateCameraBtn" class="btn btn-secondary">Rotate</button>
                <button id="captureBtn" class="btn btn-primary">Capture</button>
            </div>
            <div id="preview-box" style="display:none;">
                <img id="previewImage">
                <button class="btn btn-secondary" id="retakeBtn">Retake</button>
                <button class="btn btn-primary" id="uploadBtn">Upload</button>
            </div>
        </div>
      </div>
    </div>
  </div>
<div class="modal fade" id="modalViewPhoto" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-body">
            <div class="container-fluid">
                <img src="" alt="" id="fotoProduksiImage" style="width:100%">
            </div>
        </div>
        <div class="modal-footer ">
            <a class="btn btn-success me-3" download id="btn_download_photo">Download</a>
            <button class="btn btn-primary me-3 btn-prev">Prev</button>
            <button class="btn btn-primary btn-next">Next</button>
        </div>
      </div>
    </div>
  </div>
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
<!--- modal Konfirmasi -->
<div class="modal fade" id="modalKonfirmasi" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Konfirmasi</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <p id="text-konfirmasi" style="font-size:10pt"></p>
          
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-cancel" data-dismiss="modal">Close</button>
          <button type="button" class="btn btn-success btn-konfirmasi ms-2">Konfirmasi</button>
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

@if (Route::is(['bank']))
    <!-- modal -->
    <div class="modal modal-lg custom-modal fade" id="add_bank" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Tambah Bank</h4>
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
                                        <label>Kode Bank<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="bank_kode"
                                            placeholder="Input Kode Bank">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Tambah Bank</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endif

@if (Route::is(['tt']))
    <!-- modal -->
    <div class="modal modal-lg custom-modal fade" id="add_acc_tt" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Konfirmasi Terima</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <p class="text-center">Konfirmasi Pembayaran Semua Invoice Harap unggah Bukti Transfer Bank atau Slip Pembayaran yang valid sebagai syarat konfirmasi pelunasan semua invoice terkait.</p>
                         <div class="profile-picture mt-3">
                            <div class="upload-profile">
                                <div class="profile-img">
                                    <img id="preview_image" class="avatar" style="min-height: 200px;width:100%;border-radius:0px"
                                        src="{{ asset('no_img.png') }}"
                                        alt="profile-img">
                                </div>
                                <div class="add-profile ms-3">
                                    <h5>Unggah Foto Bukti Transaksi</h5>
                                    <span id="file_name">xx.jpg</span>
                                </div>
                            </div>
                            <div class="img-upload">
                                <label class="btn btn-upload">
                                    Unggah <input type="file" class="form-control  input-gambar"
                                    accept="image/png, image/jpeg" id="image">
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Konfirmasi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal modal-lg custom-modal fade" id="view_tt" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Konfirmasi Terima</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                      <div class="container-fluid">
                            <img src="" alt="" id="preview_bukti">
                      </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Close</button>
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
            <div class="modal-content p-3">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Tambah Produk Bermasalah</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                {{-- <div class="col-lg-6">
                                    <div class="mb-3">
                                        <label class="form-label">Produk</label>
                                        <select class="form-select  select2 fill select2Input" id="product_id">
                                        </select>
                                    </div>
                                </div> --}}
                                <div class="col-lg-6">
                                    <div class="input-block mb-3">
                                        <label>Tanggal<span class="text-danger">*</span></label>

                                        <div class="input-groupicon calender-input">
                                            <input type="text" class="datetimepicker form-control fill"
                                                id="pi_date" placeholder="Pilih Tanggal">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="input-block mb-3">
                                        <label class="form-label">Jenis Retur<span class="text-danger">*</span></label>
                                        <select class="select" id="tipe_return">
                                            <option value="1" selected>Retur ke Supplier / Rusak Gudang</option>
                                            <option value="2">Pengembalian Pelanggan</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="input-block mb-3">
                                        <label class="form-label">Tipe<span class="text-danger">*</span></label>
                                        <select class="select" id="pi_type">
                                            
                                        </select>
                                    </div>
                                </div>
                            {{-- <div class="col-lg-6">
                                    <div class="input-block mb-3">
                                        <label class="form-label">Jumlah</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control number-only fill" id="pi_qty">
                                            <select class="form-select w-25 fill" id="unit_id">
                                            </select>
                                        </div>
                                    </div>
                                </div> --}}
                                <div class="col-lg-6">
                                    <div class="input-block mb-3">
                                        <label class="form-label">Catatan<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="pi_notes" placeholder="Tambahkan Catatan">
                                    </div>
                                </div>
                                <div class="col-12 py-3 mb-3">
                                    <table class="table table-center" id="tableProduct" style="min-height: 15vh">
                                        <thead>
                                            <th>Nama Produk</th>
                                            <th>Qty</th>
                                            <th>Satuan</th>
                                            <th class="no-sort text-center">Aksi</th>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                                <div class="col-4 add">
                                    <div class="input-block mb-3" id="row-product">
                                        <label>Nama Produk<span class="text-danger">*</span></label>
                                        <select class="form-select fill_product" id="product_id"></select>
                                    </div>
                                </div>
                                <div class="col-3 add">
                                    <div class="input-block mb-3">
                                        <label>Qty<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill_product number-only" id="pid_qty" placeholder="Qty Produk">
                                    </div>
                                </div>
                                
                                <div class="col-4 add">
                                    <div class="input-block mb-3">
                                        <label>Nama Satuan<span class="text-danger">*</span></label>
                                        <select class="form-select fill_product" id="unit_product_id"></select>
                                    </div>
                                </div>
                                <div class="col-1 pt-4 add">
                                    <a class="btn btn-primary btn-add-product">+</a>
                                </div>
                            </div>


                            <div class="modal-footer p-0">
                                <button type="button" data-bs-dismiss="modal"
                                    class="btn btn-back cancel-btn me-2">Batal</button>
                                <button type="button" class="btn btn-primary paid-continue-btn btn-save">Tambah Produk
                                    </button>
                            </div>
                        </div>
                    </div>
                </form>
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
                                <div class="col-lg-2 col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Qty Produksi</label>
                                        <input type="number" class="form-control fill number-only" id="production_qty" placeholder="Jumlah Produksi" value="1">
                                    </div>
                                </div>
                                <div class="col-lg-4 col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Total Barang Produksi</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control fill number-only" id="production_total" placeholder="0" value="0" disabled>
                                            <select class="form-control w-25" id="unit_id" disabled>
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
    <div class="modal modal-xl custom-modal fade" id="add_supplies" role="dialog">
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
                                <div class="col-4">
                                    <div class="input-block mb-3" id="row-satuan">
                                        <label>Satuan<span class="text-danger">*</span></label>
                                        <select id="supplies_unit" class="form-select fill"></select>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="input-block mb-3">
                                        <label>Default Unit<span class="text-danger">*</span></label>
                                        <select class="form-select fill select2" id="unit_id">
                                        </select>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="input-block mb-3">
                                        <label>Stock Alert</label>
                                        <div class="input-group mb-3">
                                            <input type="number" class="form-control number-only" id="alert" value="0" min="0" step="1" aria-describedby="basic-addon3">
                                            <span class="input-group-text" id="satuan_alert">-</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="input-block mb-3">
                                        <label>Deskripsi</label>
                                        <textarea class="form-control " id="supplies_desc" cols="30" rows="5"></textarea>
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
                                                <td>Supplier<span class="text-danger"  style="width:15%">*</span></td>
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
                                <hr>
                                <label class="mb-3">Atur Relasi</label>
                                <div class="row">
                                    <div class="col-3">
                                        <select name="" id="relasi1" class="form-select"></select>
                                    </div>
                                    <div class="col-1"> <h6 class="text-center pt-2"> - </h6> </div>
                                    <div class="col-3">
                                        <select name="" id="relasi2" class="form-select"></select>
                                    </div>
                                    <div class="col-3">
                                        <button class="btn btn-primary w-100 btn-sm" type="button" id="btnAddRowRelasi">Tambah Row Relasi</button>
                                    </div>
                                </div>
                                 <table class="table table-bordered mb-2 mt-4">
                                    <thead>
                                        <tr>
                                            <td>Name Unit 1<span class="text-danger">*</span></td>
                                            <td>Name Unit 2<span class="text-danger">*</span></td>
                                        </tr>
                                    </thead>
                                    <tbody class="tbRelasi" id="tbRelasi">
                                    </tbody>
                                </table>
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
                                    <div class="col-4">
                                        <div class="input-block mb-3">
                                            <label>Tanggal<span class="text-danger">*</span></label>
                                            <input type="date" class="form-control fill" id="so_date">
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="input-block mb-3 " id="row-pelanggan">
                                            <label>Nama Pelanggan<span class="text-danger">*</span></label>
                                            <select id="so_customer" class="form-control fill"></select>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="input-block mb-3">
                                            <label>Nama Sales</label>
                                            <select id="sales_id" class="form-control"></select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 row">
                                    <div class="col-4">
                                        <div class="input-block">
                                            <label>Diskon</label>
                                            <div class="input-group mb-3">
                                                <input type="text" class="form-control  number-only" id="so_discount" 
                                                placeholder="Input Diskon" value="0">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="input-block">
                                            <label>PPN</label>
                                            <div class="input-group mb-3">
                                                <input type="text" class="form-control  number-only" id="so_ppn" 
                                                placeholder="Input PPN" value="0">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="input-block mb-3">
                                            <label>Biaya Pengiriman</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text">Rp </span>
                                                <input type="text" class="form-control  number-only nominal_only" id="so_cost" value="0" placeholder="Input Biaya Pengiriman">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 row">
                                    <div class="col-6">
                                        <div class="input-block mb-3">
                                            <label>SKU</label>
                                            <select class="form-select" id="so_sku"></select>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        
                                    </div>
                                </div>
                                <div class="col-12">
                                    <table class="table table-center table-responsive">
                                        <thead>
                                            <th>Produk</th>
                                            <th>Variasi</th>
                                            <th>SKU</th>
                                            <th class="text-center">Jumlah</th>
                                            <th class="text-end">Harga Satuan</th>
                                            <th class="text-end">Subtotal</th>
                                            <th class="text-center">Action</th>
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
                            class="btn btn-primary paid-continue-btn btn-save">Tambah Penjualan</button>
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
                                            <input type="text" class="form-control fill" id="sdo_receiver" placeholder="Nama Penerima">
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="input-block mb-3">
                                            <label>Tanggal<span class="text-danger">*</span></label>
                                            <input type="date" class="form-control fill" id="sdo_date">
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="input-block mb-3">
                                            <label>Nomor Telepon<span class="text-danger">*</span></label>
                                            <input type="text" class="form-control fill number-only" id="sdo_phone" placeholder="Nomor Telepon">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="input-block mb-3">
                                        <label>Keterangan</label>
                                        <textarea class="form-control " id="sdo_desc" cols="30" rows="5" placeholder="Keterangan pengiriman"></textarea>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <table class="table table-center" id="tableSalesDelivery">
                                        <thead>
                                            <th>Produk</th>
                                            <th>SKU</th>
                                            <th>Qty</th>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="row-acc">
                            <button class="btn btn-danger btn-decline me-2" type="button">Tolak</button>
                            <button class="btn btn-success btn-approve me-3" type="button">Setujui</button>
                        </div>
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save-delivery">Tambah Catatan Pengiriman</button>
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
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <div class="input-block mb-3">
                                        <label>Tanggal Faktur<span class="text-danger">*</span></label>
                                        <input type="date" class="form-control fill" id="soi_date">
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="input-block mb-3">
                                        <label>Jatuh Tempo<span class="text-danger">*</span></label>
                                        <input type="date" class="form-control fill" id="soi_due">
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
                        <div class="row-acc">
                            <button class="btn btn-danger btn-decline-invoice me-2" type="button">Tolak</button>
                            <button class="btn btn-success btn-approve-invoice me-3" type="button">Setujui</button>
                        </div>
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save-invoice">Tambah Faktur</button>
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
                                        <div class="input-block" id="row-pemasok">
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
                                            <label>Diskon</label>
                                            <div class="input-group mb-3">
                                                <input type="text" class="form-control fill number-only" id="po_discount" 
                                                placeholder="Input Diskon" value="0">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="input-block">
                                            <label>PPN</label>
                                            <div class="input-group mb-3">
                                                <input type="text" class="form-control fill number-only" id="po_ppn" 
                                                placeholder="Input PPN" value="0">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="input-block mb-3">
                                            <label>Biaya Pengiriman</label>
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
                                        <select class="form-select" id="po_sku">
                                            <option value="" selected disabled>Pilih Supplier Terlebih Dahulu</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="table-responsive">
                                        <table class="table table-center" id="tablePurchaseModal">
                                            <thead>
                                                <th style="width: 15%">Produk</th>
                                                <th style="width: 20%">Variasi</th>
                                                <th style="width:11%">SKU</th>
                                                <th style="width: 22%">Qty</th>
                                                <th style="width:11%" class="text-end">Harga Beli</th>
                                                <th style="width:11%" class="text-end">Subtotal</th>
                                                <th class="text-center">Action</th>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
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
                            class="btn btn-primary paid-continue-btn btn-save">Tambah Pembelian</button>
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
                                        <select name="" id="pdo_receiver" class="form-select"></select>
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

                                {{--  <div class="col-12 col-md-6">
                                   
                                    <div class="input-block">
                                        <label>Alamat<span class="text-danger">*</span></label>
                                        <textarea class="form-control fill" id="pdo_address" rows="3" placeholder="Alamat penerima"></textarea>
                                    </div>
                                </div>--}}
                                <div class="col-12 col-md-12">
                                    <div class="input-block">
                                        <label>Keterangan</label>
                                        <textarea class="form-control" id="pdo_desc" rows="3" placeholder="Keterangan pengiriman"></textarea>
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
                        <div class="row-acc">
                            <button class="btn btn-danger btn-decline me-2" type="button">Tolak</button>
                            <button class="btn btn-success btn-approve me-3" type="button">Setujui</button>
                        </div>
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
                                            <input type="text" class="form-control fill number_only nominal_only" id="poi_total" value="0" placeholder="Masukkan jumlah">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex justify-content-end">
                         <div class="row-acc-invoice">
                            <button class="btn btn-danger btn-decline-invoice me-2" type="button">Tolak</button>
                            <button class="btn btn-success btn-approve-invoice me-3" type="button">Setujui</button>
                        </div>
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
                                <div class="col-6">
                                    <div class="input-block mb-3">
                                        <label>Tanggal<span class="text-danger">*</span></label>
                                        <input type="date" class="form-control fill" id="pc_date">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="input-block mb-3">
                                        <label>Nama Staff<span class="text-danger">*</span></label>
                                        <select class="form-select fill" id="staff_id"></select>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="input-block mb-3">
                                        <label>Catatan<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="pc_description"
                                            placeholder="Masukkan Catatan">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="row input-block mb-3">
                                        <div class="col-4">
                                            <label>Kategori Kas<span class="text-danger">*</span></label>
                                            <select class="form-select fill" id="cc_id">
                                                <option value="debit" checked>Debit</option>
                                                <option value="credit">Kredit</option>
                                            </select>
                                        </div>
                                        <div class="col-4">
                                            <label>Tipe Kas</label>
                                            <input type="text" class="form-control" id="cc_type" disabled>
                                        </div>
                                        <div class="col-4">
                                            <label>Nominal<span class="text-danger">*</span></label>
                                            <div class="input-group fix-nominal">
                                                <span class="input-group-text">Rp.</span>
                                                <input type="text" name="" id="pc_nominal" class="form-control fill number-only nominal_only" placeholder="Contoh 10000">
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
                                <div class="col-7">
                                    <div class="input-block mb-3">
                                        <label>Produk<span class="text-danger">*</span></label>
                                        <select class="form-select fill" id="product_id"></select>
                                    </div>
                                </div>
                                <div class="col-5">
                                    <div class="input-block mb-3">
                                        <label>Qty Produksi<span class="text-danger">*</span></label>
                                        
                                        <div class="input-group">
                                            <input type="text" class="form-control fill number-only" id="bom_qty" placeholder="Qty Produksi">
                                            <select class="form-select w-25 fill" id="unit_id">
                                            </select>
                                        </div>
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
                                
                                <div class="col-4">
                                    <div class="input-block mb-3">
                                        <label>Nama Satuan<span class="text-danger">*</span></label>
                                        <select class="form-select fill_supply" id="unit_supplies_id"></select>
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

@if (Route::is(['cashCategory']))
    <!-- modal -->
    <div class="modal modal-lg custom-modal fade" id="add_cash_category" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Tambah Kategori Kas</h4>
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
                                        <input type="text" class="form-control fill" id="cc_name"
                                            placeholder="ex Makan Siang">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="input-block mb-3">
                                        <label>Tipe Kategori<span class="text-danger">*</span></label>
                                        <select class="form-select fill" id="cc_type">
                                            <option value="" selected disabled>Pilih Tipe Kategori</option>
                                            <option value="Credit 1">Credit 1</option>
                                            <option value="Credit 2">Credit 2</option>
                                            <option value="Debit">Debit / Setoran Tunai</option>
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
                            class="btn btn-primary paid-continue-btn btn-save">Tambah Kategori Kas</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endif

@if (Route::is(['stockProduct']))
    <!-- Add coupons -->
    <div class="modal fade" id="add_stock_product">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content p-3">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Riwayat Stok Produk</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-12 py-3 mb-3">
                                    <div class="table-scroll">
                                        <table class="table table-center" id="tableLog" style="min-height: 15vh">
                                            <thead>
                                                <th>Tanggal</th>
                                                <th>No. Transaksi</th>
                                                <th>Catatan</th>
                                                <th>Jumlah</th>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>


                            <div class="modal-footer p-0">
                                <button type="button" data-bs-dismiss="modal"
                                    class="btn btn-back cancel-btn me-2">Kembali</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Add Coupons -->
@endif

@if (Route::is(['stockSupplies']))
    <!-- Add coupons -->
    <div class="modal fade" id="add_stock_supplies">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content p-3">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Riwayat Stok Bahan Mentah</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-12 py-3 mb-3">
                                    <div class="table-scroll">
                                        <table class="table table-center" id="tableLog" style="min-height: 15vh">
                                            <thead>
                                                <th>Tanggal</th>
                                                <th>No. Transaksi</th>
                                                <th>Catatan</th>
                                                <th>Jumlah</th>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>


                            <div class="modal-footer p-0">
                                <button type="button" data-bs-dismiss="modal"
                                    class="btn btn-back cancel-btn me-2">Kembali</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Add Coupons -->
@endif