<!--- modal Delete -->
<div class="modal fade" id="modalDelete" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-5">
                <p id="text-delete" class="text-center" style="font-size:12pt"></p>
            </div>
            <div class="modal-footer text-center" style="justify-content: flex-end;">
                <button type="button" class="btn btn-primary btn-rounded btn-sm btn-cancel" data-dismiss="modal"
                    onclick="closeModalDelete()">Close</button>
                <button type="button" class="btn btn-danger btn-rounded btn-sm btn-konfirmasi">Delete</button>
            </div>
        </div>
    </div>
</div>

<!--- modal Notification -->
<div class="modal fade" id="modalNotification" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-bell text-warning me-2"></i>
                    Notification
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="notification-content">
                    <!-- Template for single notification -->
                    <div class="notification-template d-none">
                        <div class="notification-item">
                            <p class="mb-2 notification-text" style="font-size: 14px; line-height: 1.5;"></p>
                            <small class="text-muted notification-meta">
                                <i class="fa fa-calendar me-1"></i>
                                <span class="notification-created">Created: </span>
                                <span class="notification-store-info"></span>
                            </small>
                        </div>
                    </div>
                    <!-- Notifications will be populated here -->
                </div>
            </div>
            <div class="modal-footer ms-auto">
                <button type="button" class="btn btn-primary btn-rounded btn-sm" data-bs-dismiss="modal">
                    OK
                </button>
            </div>
        </div>
    </div>
</div>

<!--- modal Tampilkan Harga -->
<div class="modal fade" id="modalTampilkanHarga" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tampilkan Harga</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-5">
                <p class="text-center" style="font-size:12pt">Apakah ingin menampilkan harga pada PDF?</p>
            </div>
            <div class="modal-footer text-center" style="justify-content: flex-end;">
                <button type="button" class="btn btn-secondary btn-rounded btn-sm" data-bs-dismiss="modal"
                    id="btn-tidak">Tidak</button>
                <button type="button" class="btn btn-primary btn-rounded btn-sm" id="btn-ya">Ya</button>
            </div>
        </div>
    </div>
</div>


@if (Route::is(['category']))
    <!-- Add Category -->
    <div class="modal fade" id="add-category">
        <div class="modal-dialog modal-dialog-centered custom-modal-two">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4 class="modal-title">Create Category</h4>
                            </div>
                            <button type="button" class="close ms-auto" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <input type="text" class="form-control fill" id="category_name">
                            </div>
                            <div class="modal-footer-btn">
                                <button type="button" class="btn btn-cancel me-2"
                                    data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-save">Create Category</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Add Category -->
    <!-- /Edit Category -->
@endif
@if (Route::is(['categoryInventory']))
    <!-- Add Category -->
    <div class="modal fade" id="add-category">
        <div class="modal-dialog modal-dialog-centered custom-modal-two">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4 class="modal-title">Tambah Kategori Inventory</h4>
                            </div>
                            <button type="button" class="close ms-auto" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <form action="{{ url('category-list') }}">
                                <div class="mb-3">
                                    <label class="form-label">Nama Kategori</label>
                                    <input type="text" class="form-control fill" id="category_name">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Singkatan</label>
                                    <input type="text" class="form-control fill" id="category_short_name">
                                </div>
                                <div class="modal-footer-btn">
                                    <button type="button" class="btn btn-cancel me-2"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-save">Tambah Kategori</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Add Category -->
    <!-- /Edit Category -->
@endif
@if (Route::is(['addProduct']))
    <!-- Add Variant -->
    <div class="modal fade" id="add-variation">
        <div class="modal-dialog modal-dialog-centered custom-modal-two">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>Edit Varian</h4>
                            </div>
                            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <div class="modal-title-head people-cust-avatar">
                                <h6>Gambar</h6>
                            </div>
                            <div class="new-employee-field">
                                <div class="profile-pic-upload">
                                    <div class="profile-pic">
                                        <span><i data-feather="plus-circle" class="plus-down-add"></i> Ubah
                                            Gambar</span>
                                    </div>
                                    <div class="mb-3">
                                        <div class="image-upload mb-0">
                                            <input type="file">
                                            <div class="image-uploads">
                                                <h4>Change Image</h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="mb-3">
                                        <label class="form-label">Nama Varian</label>
                                        <input type="text" class="form-control"
                                            placeholder="Masukkan nama varian">
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <label class="form-label">Kuantitas</label>
                                        <input type="number" class="form-control" placeholder="0" min="0">
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <label class="form-label">Harga</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" class="form-control" placeholder="0">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <label class="form-label">Harga Grosir</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" class="form-control" placeholder="0">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer-btn">
                                <a href="javascript:void(0);" class="btn btn-cancel me-2"
                                    data-bs-dismiss="modal">Cancel</a>
                                <a href="{{ url('add-product') }}" class="btn btn-submit">Simpan</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Add Variant -->
@endif
@if (Route::is(['barcode']))
    <!-- Print Barcode -->
    <div class="modal fade" id="prints-barcode">
        <div class="modal-dialog modal-dialog-centered stock-adjust-modal">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>Barcode</h4>
                            </div>
                            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <div class="d-flex justify-content-end">
                                <a href="javascript:void(0);" class="btn btn-cancel close-btn">
                                    <span><i class="fas fa-print me-2"></i></span>
                                    Print Barcode</a>
                            </div>

                            <div class="barcode-scan-header">
                                <h5>Nike Jordan</h5>
                            </div>
                            <div class="row">
                                <div class="col-sm-4">
                                    <div class="barcode-scanner-link text-center">
                                        <h6>Grocery Alpha</h6>
                                        <p>
                                            Nike Jordan
                                        </p>
                                        <p>Price: $400</p>
                                        <div class="barscaner-img">
                                            <img src="{{ URL::asset('/build/img/barcode/barcode-01.png') }}"
                                                alt="Barcode" class="img-fluid">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="barcode-scan-header">
                                <h5>Apple Series 5 Watch</h5>
                            </div>
                            <div class="row">
                                <div class="col-sm-4">
                                    <div class="barcode-scanner-link text-center">
                                        <h6>Grocery Alpha</h6>
                                        <p>
                                            Apple Series 5 Watch
                                        </p>
                                        <p>Price: $300</p>
                                        <div class="barscaner-img">
                                            <img src="{{ URL::asset('/build/img/barcode/barcode-02.png') }}"
                                                alt="Barcode" class="img-fluid">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="barcode-scanner-link text-center">
                                        <h6>Grocery Alpha</h6>
                                        <p>
                                            Apple Series 5 Watch
                                        </p>
                                        <p>Price: $300</p>
                                        <div class="barscaner-img">
                                            <img src="{{ URL::asset('/build/img/barcode/barcode-02.png') }}"
                                                alt="Barcode" class="img-fluid">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="barcode-scanner-link text-center">
                                        <h6>Grocery Alpha</h6>
                                        <p>
                                            Apple Series 5 Watch
                                        </p>
                                        <p>Price: $300</p>
                                        <div class="barscaner-img">
                                            <img src="{{ URL::asset('/build/img/barcode/barcode-02.png') }}"
                                                alt="Barcode" class="img-fluid">
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Print Barcode -->
@endif

@if (Route::is(['promo']))
    <!-- Add coupons -->
    <div class="modal fade" id="add-units">
        <div class="modal-dialog modal-dialog-centered custom-modal-two">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div
                            class="modal-header border-0 custom-modal-header d-flex justify-content-between align-items-center">
                            <div class="page-title">
                                <h4 class="modal-title">Buat Promo</h4>
                            </div>
                            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <form action="coupons">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Nama Promo</label>
                                            <input type="text" class="form-control fill" id='nama_promo'>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Kode</label>
                                            <input type="text" class="form-control fill" id="kode_promo">
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Tipe</label>
                                            <select class="select" id="tipe_promo">
                                                <option value="Potongan Tetap">Potongan Tetap</option>
                                                <option value="Persentase">Persentase</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Besar Potongan</label>
                                            <input type="text" class="number-only form-control fill"
                                                id="besar_potongan_promo">
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="input-blocks">
                                            <label>Tanggal Mulai</label>
                                            <div class="input-groupicon calender-input">
                                                <i data-feather="calendar" class="info-img"></i>
                                                <input type="text" class="datetimepicker form-control"
                                                    placeholder="Select Date" id="tanggal_mulai_promo">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="input-blocks">
                                            <label>Tanggal Berakhir</label>

                                            <div class="input-groupicon calender-input">
                                                <i data-feather="calendar" class="info-img"></i>
                                                <input type="text" class="datetimepicker form-control"
                                                    placeholder="Select Date" id="tanggal_berakhir_promo">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="input-blocks">
                                            <label class="form-label">Store</label>
                                            <select class=" form-control fill select2" id="store_id">
                                            </select>
                                        </div>
                                    </div>
                                </div>


                                <div class="modal-footer-btn">
                                    <button type="button" class="btn btn-cancel me-2"
                                        data-bs-dismiss="modal">Batal</button>
                                    <button type="button" class="btn btn-save">Buat Promo</button>
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

@if (Route::is(['bundling']))
    <!-- Add Bundling -->
    <div class="modal fade" id="add-units">
        <div class="modal-dialog modal-dialog-centered custom-modal-two modal-lg">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div
                            class="modal-header border-0 custom-modal-header d-flex justify-content-between align-items-center">
                            <div class="page-title modal-title">
                                <h4>Buat Bundling</h4>
                            </div>
                            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <form action="coupons">
                                <div class="row">
                                    <div class="col-lg-3">
                                        <label>Gambar</label>
                                        <div class="profile-pic-upload mb-1">
                                            <div class="profile-pic brand-pic">
                                                <span id="add_image_text"><i data-feather="image"
                                                        class="plus-down-add"></i> Add
                                                    Image</span>
                                                <img id="bundling_image_preview" class="p-1" src="#"
                                                    alt="Preview"
                                                    style="display:none; max-width: 100%; max-height: 150px; object-fit: cover;border-radius:15px" />
                                            </div>
                                        </div>
                                        <div class="image-upload btn btn-submit"
                                            style="justify-content: center; height: 2rem;text-align: center;width: 70%">
                                            <input type="file" id="bundling_image" name="bundling_image"
                                                accept="image/*">
                                            <div class="image-uploads"
                                                style="margin-top: -0.7rem; margin-left: -1rem; text-align: center; ">
                                                <h4 style="color: white">Ubah Gambar</h4>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="mb-3">
                                            <label class="form-label">Nama Bundling</label>
                                            <input type="text" class="form-control fill" id='nama_bundling'>
                                        </div>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="mb-3">
                                            <label class="form-label">Harga</label>
                                            <div class="input-group">
                                                <span class="input-group-text">Rp</span>
                                                <input type="text" class="form-control nominal_only"
                                                    placeholder="0" id="harga_bundling">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="mb-3">
                                            <label class="form-label">Store</label>
                                            <select class="select fill" id="store_bundling">
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-3">
                                    </div>
                                    <div class="col-lg-9" style="margin-top: -7rem">
                                        <div class="row">
                                            <div class="col-lg-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Tanggal Mulai</label>
                                                    <div class="input-groupicon calender-input"
                                                        style="cursor: pointer;">
                                                        {{-- <i data-feather="calendar" class="info-img"></i> --}}
                                                        <input type="text" class="datetimepicker form-control"
                                                            placeholder="Select Date" id="tanggal_mulai_bundling"
                                                            style="cursor: pointer;">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Tanggal Akhir</label>
                                                    <div class="input-groupicon calender-input">
                                                        {{-- <i data-feather="calendar" class="info-img"></i> --}}
                                                        <input type="text" class="datetimepicker form-control"
                                                            placeholder="Select Date" id="tanggal_berakhir_bundling">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class = "col-lg-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Deskripsi</label>
                                                    <input type="text" class="form-control"
                                                        id="deskripsi_bundling">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-lg-12 mt-3">
                                        <div class="product-list">
                                            <h5>Produk dalam Bundling</h5>
                                            <input type="text" class="form-control" id="search_bundling_product">
                                        </div>
                                        <div class="table-responsive mt-2 w-full">
                                            <table class="table datanew" id="bundling-product-table">
                                                <thead>
                                                    <tr>
                                                        <th>Produk</th>
                                                        <th>Varian</th>
                                                        <th>SKU</th>
                                                        <th>Jumlah</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="modal-footer-btn">
                                        <button type="button" class="btn btn-cancel me-2"
                                            data-bs-dismiss="modal">Batal</button>
                                        <div type="" class="btn btn-save">Simpan</div>
                                    </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Add Bundling -->
@endif

@if (Route::is(['stores']))
    <!-- Add Store -->
    <div class="modal fade" id="add-store">
        <div class="modal-dialog modal-dialog-centered custom-modal-two">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>Tambahkan Toko</h4>
                            </div>
                            <button type="button" class="close ms-auto" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <div class="container-fluid">
                                <form action="store-list">
                                    <div class="mb-3">
                                        <label class="form-label">Nama Toko</label>
                                        <input type="text" class="form-control fill" id="store_name">
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <div class="input-blocks">
                                                <label>Contact Person</label>
                                                <select class="form-control  select2" id="staff_id">
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-title-head">
                                            <h6><span><i data-feather="map-pin"></i></span>Location</h6>
                                        </div>
                                        <div class="col-lg-12">
                                            <div class="mb-3">
                                                <label class="form-label">Address *</label>
                                                <input type="text" class="form-control fill" id="store_address">
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="mb-3">
                                                <label class="form-label">State*</label>
                                                <select class="form-control select2 fill" id="state_id"></select>
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="mb-3 mb-0">
                                                <label class="form-label">City*</label>
                                                <select class="form-control select2 fill" id="city_id"></select>
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="mb-3 mb-0">
                                                <label class="form-label">Zipcode*</label>
                                                <input type="text" class="form-control fill" id="store_zip_code">
                                            </div>
                                        </div>
                                    </div>
                            </div>
                            <div class="modal-footer-btn">
                                <button type="button" class="btn btn-cancel me-2"
                                    data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-save">Create</button>
                            </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    <!-- /Add Store -->
@endif
@if (Route::is(['customers']))
    <!-- Add Customer -->
    <div class="modal fade" id="add-units">
        <div class="modal-dialog modal-dialog-centered custom-modal-two">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>Daftarkan Customer Baru</h4>
                            </div>
                            <button type="button" class="close ms-auto" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <form action="suppliers">
                                <div class="row">
                                    <div class="col-lg-13 pe-0">
                                        <div class="mb-3">
                                            <label class="form-label">Nama Customer</label>
                                            <input type="text" class="form-control fill" id="nama_customer">
                                        </div>
                                    </div>
                                    <div class="col-lg-6 pe-0">
                                        <div class="mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control fill" id="email_customer">
                                        </div>
                                    </div>
                                    <div class="col-lg-6 pe-0">
                                        <div class="input-blocks">
                                            <label class="mb-2">Telepon</label>
                                            <input type="text" class="form-control fill" id="telepon_customer">
                                        </div>
                                    </div>
                                    <div class="col-lg-12 pe-0">
                                        <div class="mb-3">
                                            <label class="form-label">Alamat</label>
                                            <input type="text" class="form-control fill" id="alamat_customer">
                                        </div>
                                    </div>
                                    <div class="col-lg-6 pe-0">
                                        <div class="mb-3">
                                            <label class="form-label">Provinsi</label>
                                            <select class=" form-control fill select2" id="state_id">
                                            </select>

                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3 mb-0">
                                            <label class="form-label">Kota</label>
                                            <select class="form-control fill select2" id="city_id"></select>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 pe-0">
                                        <div class="mb-3">
                                            <label class="form-label">Nama Bank</label>
                                            <input type="text" class="form-control" id="nama_bank_customer">
                                        </div>
                                    </div>
                                    <div class="col-lg-6 pe-0">
                                        <div class="mb-3">
                                            <label class="form-label">Nama Rekening</label>
                                            <input type="text" class="form-control" id="nama_rekening_customer">
                                        </div>
                                    </div>
                                    <div class="col-lg-12 pe-0">
                                        <div class="mb-3">
                                            <label class="form-label">Nomor Rekening</label>
                                            <input type="text" class="form-control" id="nomor_rekening_customer">
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="mb-3 input-blocks">
                                            <label class="form-label">Notes</label>
                                            <textarea class="form-control mb-1" id="deskripsi_customer"></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="modal-footer-btn">
                                    <button type="button" class="btn btn-cancel me-2"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-submit"
                                        id='btn-submit-add-customer'>Simpan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Add Customer -->
    <!-- Edit Customer -->
    <div class="modal fade" id="edit-customer">
        <div class="modal-dialog modal-dialog-centered custom-modal-two">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>Edit Customer</h4>
                            </div>
                            <button type="button" class="close ms-auto" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <form action="suppliers">
                                <div class="row">
                                    <div class="col-lg-13 pe-0">
                                        <div class="mb-3">
                                            <label class="form-label">Nama Customer</label>
                                            <input type="text" class="form-control" id="edit_nama_customer">
                                        </div>
                                    </div>
                                    <div class="col-lg-6 pe-0">
                                        <div class="mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control" id="edit_email_customer">
                                        </div>
                                    </div>
                                    <div class="col-lg-6 pe-0">
                                        <div class="input-blocks">
                                            <label class="mb-2">Telepon</label>
                                            <input type="text" class="form-control" id="edit_telepon_customer">
                                        </div>
                                    </div>
                                    <div class="col-lg-12 pe-0">
                                        <div class="mb-3">
                                            <label class="form-label">Alamat</label>
                                            <input type="text" class="form-control" id="edit_alamat_customer">
                                        </div>
                                    </div>
                                    <div class="col-lg-6 pe-0">
                                        <div class="mb-3">
                                            <label class="form-label">Provinsi</label>
                                            <select class=" form-control fill select2" id="edit_state_id">
                                            </select>

                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3 mb-0">
                                            <label class="form-label">Kota</label>
                                            <select class="form-control fill select2" id="edit_city_id"></select>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 pe-0">
                                        <div class="mb-3">
                                            <label class="form-label">Nama Bank</label>
                                            <input type="text" class="form-control" id="edit_nama_bank_customer">
                                        </div>
                                    </div>
                                    <div class="col-lg-6 pe-0">
                                        <div class="mb-3">
                                            <label class="form-label">Nama Rekening</label>
                                            <input type="text" class="form-control"
                                                id="edit_nama_rekening_customer">
                                        </div>
                                    </div>
                                    <div class="col-lg-12 pe-0">
                                        <div class="mb-3">
                                            <label class="form-label">Nomor Rekening</label>
                                            <input type="text" class="form-control"
                                                id="edit_nomor_rekening_customer">
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="mb-3 input-blocks">
                                            <label class="form-label">Notes</label>
                                            <textarea class="form-control mb-1" id="edit_deskripsi_customer"></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="modal-footer-btn">
                                    <button type="button" class="btn btn-cancel me-2"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-submit"
                                        id='btn-submit-edit-customer'>Simpan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Edit Customer -->
@endif
@if (Route::is(['customerDetail']))
    <!-- Edit Delivery Notes -->
    <div class="modal fade" id="edit-delivery-note">
        <div class="modal-dialog modal-dialog-centered custom-modal-two">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>Edit Delivery Notes</h4>
                            </div>
                            <button type="button" class="close ms-auto" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <form action="customers">
                                <div class="row">
                                    <div class="col-lg-12 pe-0">
                                        <div class="mb-3">
                                            <label class="form-label">Penerima</label>
                                            <input type="text" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-lg-6 pe-0">
                                        <div class="mb-3">
                                            <label class="form-label">Tanggal Pengiriman</label>
                                            <input type="email" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-lg-6 pe-0">
                                        <div class="input-blocks">
                                            <label class="mb-2">Telepon</label>
                                            <input type="text" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-lg-12 pe-0">
                                        <div class="mb-3">
                                            <label class="form-label">Alamat</label>
                                            <input type="text" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="mb-3 input-blocks">
                                            <label class="form-label">Deskripsi</label>
                                            <textarea class="form-control mb-1"></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="modal-footer-btn">
                                    <button type="button" class="btn btn-cancel me-2"
                                        data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-submit">Simpan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Edit Delivery Notes -->
    <!-- Lihat Bukti Pembayaran -->
    <div class="modal fade" id="lihat-bukti">
        <div class="modal-dialog modal-dialog-centered custom-modal-two">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>Lihat Bukti Pembayaran</h4>
                            </div>
                            <button type="button" class="close ms-auto" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="mb-3">
                                        <label class="form-label">Nama Pembayar</label>
                                        <input type="text" class="form-control" value="John Doe" readonly>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <label class="form-label">Metode Pembayaran</label>
                                        <input type="text" class="form-control" value="Transfer" readonly>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <label class="form-label">Dibayarkan Pada</label>
                                        <input type="text" class="form-control" value="11/08/2025" readonly>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="mb-3">
                                        <label class="form-label">Bukti Transfer</label>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer-btn">
                                <button type="button" class="btn btn-cancel me-2"
                                    data-bs-dismiss="modal">Tutup</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Lihat Bukti Pembayaran -->
    <!-- Bayar -->
    <div class="modal fade" id="bayar">
        <div class="modal-dialog modal-dialog-centered custom-modal-two">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>Lakukan Pembayaran</h4>
                            </div>
                            <button type="button" class="close ms-auto" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <form action="customers">
                                <div class="row">
                                    <div class="col-lg-12 pe-0">
                                        <div class="mb-3">
                                            <label class="form-label">Nama Pembayar</label>
                                            <input type="text" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-lg-12 pe-0">
                                        <div class="mb-3">
                                            <label class="form-label">Metode Pembayaran</label>
                                            <select class="form-control">
                                                <option value="cash">Cash</option>
                                                <option value="transfer">Transfer</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-12 pe-0" id="bukti-transfer" style="display: none;">
                                        <div class="mb-3">
                                            <div class="input-blocks image-upload-down">
                                                <div class="image-upload download">
                                                    <input type="file">
                                                    <div class="image-uploads">
                                                        <img src="{{ URL::asset('/build/img/download-img.png') }}"
                                                            alt="img">
                                                        <h4>Upload <span>Bukti Transfer</span></h4>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <script>
                                        document.addEventListener('DOMContentLoaded', function() {
                                            const metodePembayaran = document.querySelector('select.form-control');
                                            const buktiTransfer = document.getElementById('bukti-transfer');

                                            metodePembayaran.addEventListener('change', function() {
                                                if (this.value === 'transfer') {
                                                    buktiTransfer.style.display = 'block';
                                                } else {
                                                    buktiTransfer.style.display = 'none';
                                                }
                                            });
                                        });
                                    </script>
                                    <div class="modal-footer-btn">
                                        <button type="button" class="btn btn-cancel me-2"
                                            data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-submit">Bayar</button>
                                    </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Bayar -->
@endif
@if (Route::is(['units']))
    <!-- Add Adjustment -->
    <div class="modal fade" id="add-units">
        <div class="modal-dialog modal-dialog-centered stock-adjust-modal">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>Add Unit</h4>
                            </div>
                            <button type="button" class="close ms-auto" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="input-blocks">
                                        <label>Unit Name</label>
                                        <input type="text" class="form-control fill" id="unit_name">
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="input-blocks">
                                        <label>Short Name</label>
                                        <input type="text" class="form-control fill" id="unit_short_name">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-6">

                                </div>
                                <div class="col-lg-6">
                                    <div class="modal-footer-btn popup">
                                        <button type="button" class="btn btn-cancel me-2"
                                            data-bs-dismiss="modal">Batal</button>
                                        <a href="javascript:void(0);" class="btn btn-save">Simpan</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

@if (Route::is(['brand']))
    <!-- Add Brand -->
    <div class="modal fade" id="add-brand">
        <div class="modal-dialog modal-dialog-centered custom-modal-two">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>Create Brand</h4>
                            </div>
                            <button type="button" class="close ms-auto" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body new-employee-field">
                            <form action="{{ url('brand-list') }}">
                                <div class="mb-3">
                                    <label class="form-label">Brand</label>
                                    <input type="text" class="form-control fill" id="brand_name">
                                </div>
                                <label class="form-label">Logo</label>
                                <div class="profile-pic-upload mb-3">
                                    <div class="profile-pic brand-pic">
                                        <span id="add_image_text"><i data-feather="plus-circle"
                                                class="plus-down-add"></i> Add
                                            Image</span>
                                        <img id="brand_image_preview" class="p-1" src="#" alt="Preview"
                                            style="display:none; max-width: 100%; max-height: 150px; object-fit: cover;border-radius:15px" />

                                    </div>
                                    <div class="image-upload mb-0">
                                        <input type="file" id="brand_image" name="brand_image" accept="image/*">
                                        <div class="image-uploads">
                                            <h4>Change Image</h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer-btn">
                                    <button type="button" class="btn btn-cancel me-2"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-save">Create Brand</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Add Brand -->
@endif


@if (Route::is(['warehouse']))
    <!-- Add Warehouse -->
    <div class="modal fade" id="add-warehouse">
        <div class="modal-dialog modal-xl modal-dialog-centered ">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>Add Warehouse</h4>
                            </div>
                            <button type="button" class="close ms-auto" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <form action="warehouse">
                                <div class="modal-title-head">
                                    <h6><span><i data-feather="info" class="feather-edit"></i></span>Warehouse Info
                                    </h6>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Name*</label>
                                            <input type="text" class="form-control fill" id="warehouse_name">
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="input-blocks">
                                            <label>Contact Person</label>
                                            <select class="form-control  select2" id="staff_id">
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-title-head">
                                        <h6><span><i data-feather="map-pin"></i></span>Location</h6>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="mb-3">
                                            <label class="form-label">Address *</label>
                                            <input type="text" class="form-control fill" id="warehouse_address">
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label class="form-label">State*</label>
                                            <select class="form-control select2 fill" id="state_id"></select>
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="mb-3 mb-0">
                                            <label class="form-label">City*</label>
                                            <select class="form-control select2 fill" id="city_id"></select>
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="mb-3 mb-0">
                                            <label class="form-label">Zipcode*</label>
                                            <input type="text" class="form-control fill" id="warehouse_zip_code">
                                        </div>
                                    </div>
                                </div>


                                <div class="modal-footer-btn">
                                    <button type="button" class="btn btn-cancel me-2"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-save">Create Warehouse</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Add Warehouse -->
@endif


@if (Route::is(['variant']))
    <!-- Add Unit -->
    <div class="modal fade" id="add-variant">
        <div class="modal-dialog modal-dialog-centered custom-modal-two">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>Create Variant</h4>
                            </div>
                            <button type="button" class="close ms-auto" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control fill" id="variant_name">
                            </div>
                            <div class="input-blocks">
                                <label class="form-label">Variant</label>
                                <input class="form-control " type="text" data-role="tagsinput"
                                    name="specialist" id="variant_attribute" value="">
                                <span class="tag-text">Enter value separated by comma</span>
                            </div>
                            <div class="modal-footer-btn">
                                <button type="button" class="btn btn-cancel me-2"
                                    data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-save">Create Variant</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Add Unit -->
@endif

@if (Route::is(['sales']))
    <!--add popup -->
    <div class="modal fade" id="add-sales-new">
        <div class="modal-dialog add-centered">
            <div class="modal-content">
                <div class="page-wrapper p-0 m-0">
                    <div class="content p-0">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4 class="modal-title"> Buat Sales Order</h4>
                            </div>r
                            <button type="button" class="close ms-auto" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="card">
                            <div class="card-body">
                                <form action="sales-list">
                                    <div class="row">
                                        <div class="col-lg-4 col-sm-6 col-12">
                                            <div class="input-blocks">
                                                <label>Nama Customer</label>
                                                <select class="select fill select2" id="customer">
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-sm-6 col-12">
                                            <div class="input-blocks">
                                                <label>Tanggal</label>
                                                <div class="input-groupicon calender-input">
                                                    <i data-feather="calendar" class="info-img"></i>
                                                    <input type="text" class="datetimepicker fill"
                                                        placeholder="Pilih" id="tanggal">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-3 col-sm-6 col-12">
                                                <div class="input-blocks">
                                                    <label>Nilai Potongan</label>
                                                    <input type="text" class="form-control nominal_only" id="nilai-potongan" placeholder="0">
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-sm-6 col-12">
                                                <div class="input-blocks">
                                                    <label>Tipe Potongan</label>
                                                    <select class="form-control select" id="tipe-potongan">
                                                        <option value="persentase">Persentase (%)</option>
                                                        <option value="tetap">Potongan Tetap</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-sm-6 col-12">
                                                <div class="input-blocks">
                                                    <label>&nbsp;</label>
                                                    <button type="button" class="btn btn-primary w-100" id="tambah-potongan">
                                                    Tambahkan
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Discount Recap Section -->
                                        <div class="row" id="discount-recap-container" style="display: none;">
                                            <div class="col-lg-12">
                                                <div class="card mb-3">
                                                    <h6 class="mb-0">Rekap Potongan</h6>
                                                    <div class="card-body p-2">
                                                        <div id="discount-recap-list" class="list-group">
                                                            <!-- Discount items will be added here dynamically -->
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-4 col-sm-6 col-12">
                                                <div class="input-blocks">
                                                    <label>Biaya Pengiriman</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">Rp</span>
                                                        <input type="text" class="form-control nominal_only fill"
                                                            id="biaya_pengiriman">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-12 col-sm-6 col-12">
                                            <div class="input-blocks">
                                                <label>SKU/Barcode Produk</label>
                                                <div class="input-groupicon select-code">
                                                    <input type="text" class="ps-2"
                                                        placeholder="Ketik kode produk dan pilih" id='add-product'>
                                                    <div class="addonset">
                                                        <img src="{{ URL::asset('/build/img/icons/qrcode-scan.svg') }}"
                                                            alt="img">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="table-responsive no-pagination">
                                        <table class="table datanew" id="order-items-table">
                                            <thead>
                                                <tr>
                                                    <th>Product</th>
                                                    <th>Varian</th>
                                                    <th>SKU</th>
                                                    <th>Qty</th>
                                                    <th>Harga Satuan</th>
                                                    <th>Subtotal</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="row">
                                        <div class="col-lg-6 ms-auto">
                                            <div class="total-order w-100 max-widthauto m-auto mb-4">
                                                <ul>
                                                    <li>
                                                        <h4>Ppn</h4>
                                                        <h5 id='ppn-detail'></h5>
                                                    </li>
                                                    <li>
                                                        <h4>Diskon</h4>
                                                        <h5 id="discount-detail"></h5>
                                                    </li>
                                                    <li>
                                                        <h4>Biaya Pengiriman</h4>
                                                        <h5 id="shipping-cost-detail"></h5>
                                                    </li>
                                                    <li>
                                                        <h4>Grand Total</h4>
                                                        <h5 id="grand-total-detail"></h5>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">

                                        <div class="col-lg-12 text-end">
                                            <button type="button" class="btn btn-cancel add-cancel me-3"
                                                data-bs-dismiss="modal">Cancel</button>
                                            <button type="button" class="btn btn-save add-sale">Submit</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /add popup -->
@endif
@if (Route::is(['salesDetail']))
    <!-- Add Delivery Notes -->
    <div class="modal fade" id="add-delivery-note">
        <div class="modal-dialog add-centered">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>Buat Delivery Notes</h4>
                            </div>
                            <button type="button" class="close ms-auto" data-bs-dismiss="modal"
                                aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <form action="customers">
                                <div class="row">
                                    <div class="col-lg-4 pe-0">
                                        <div class="mb-3">
                                            <label class="form-label">Nama Penerima</label>
                                            <input type="text" class="form-control fill"
                                                id="nama_penerima_delivery">
                                        </div>
                                    </div>
                                    <div class="col-lg-4 pe-0">
                                        <div class="input-blocks">
                                            <label>Tanggal</label>
                                            <div class="input-groupicon calender-input">
                                                <i data-feather="calendar" class="info-img"></i>
                                                <input type="text" class="datetimepicker fill"
                                                    placeholder="Pilih" id="tanggal_delivery">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3 input-blocks">
                                            <label class="form-label">Alamat</label>
                                            <textarea class="form-control mb-1" id="alamat_delivery"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3 input-blocks">
                                            <label class="form-label">Deskripsi</label>
                                            <textarea class="form-control mb-1" id="deskripsi_delivery"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <table class="table" id="delivery-notes-detail-table">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Varian</th>
                                                <th>SKU</th>
                                                <th>Jumlah SO</th>
                                                <th>Jumlah Terkirim</th>
                                                <th>Jumlah yang akan dikirim</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="modal-footer-btn mt-5">
                                    <button type="button" class="btn btn-cancel me-2"
                                        data-bs-dismiss="modal">Batal</button>
                                    <button type="button" class="btn btn-submit"
                                        id="btn_simpan_delivery">Simpan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Add Delivery Notes -->
    <!-- Buat Invoice -->
    <div class="modal fade" id="add-invoice">
        <div class="modal-dialog modal-dialog-centered custom-modal-two">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>Buat invoice</h4>
                            </div>
                            <button type="button" class="close ms-auto" data-bs-dismiss="modal"
                                aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="input-blocks add-product list mb-3">
                                        <label>No Invoice</label>
                                        <input type="text" class="form-control list" id="invoice_no">
                                        <button type="submit" class="btn btn-primaryadd" id="buat-noInvoice"
                                            data-fieldtocomplete="invoice_no">
                                            Buat No Invoice
                                        </button>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <div class="input-blocks">
                                            <label>Tgl Invoice</label>
                                            <div class="input-groupicon calender-input">
                                                <i data-feather="calendar" class="info-img"></i>
                                                <input type="text" class="datetimepicker form-control"
                                                    placeholder="Select Date" id="tanggal_invoice">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <div class="input-blocks">
                                            <label>Tgl Jatuh Tempo</label>
                                            <div class="input-groupicon calender-input">
                                                <i data-feather="calendar" class="info-img"></i>
                                                <input type="text" class="datetimepicker form-control"
                                                    placeholder="Select Date" id="jatuh_tempo">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <label class="form-label">Jumlah</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" class="form-control nominal_only"
                                                id="nominal_invoice">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer-btn">
                                <button type="button" class="btn btn-cancel me-2"
                                    data-bs-dismiss="modal">Tutup</button>
                                <button type="button" class="btn btn-submit me-2">Buat Invoice</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Buat Invoice -->

    <!-- Lihat Bukti Pembayaran -->
    <div class="modal fade" id="lihat-bukti">
        <div class="modal-dialog add-centered modal-lg">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>Pembayaran Invoice</h4>
                            </div>
                            <button type="button" class="close ms-auto" data-bs-dismiss="modal"
                                aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>

                        <div class="modal-body custom-modal-body">
                            <div class="row">
                                <!-- Kolom Gambar -->
                                <div class="col-lg-2 col-md-3 mb-3">
                                    <label>Gambar</label>
                                    <div class="profile-pic-upload mb-1 text-center">
                                        <div class="profile-pic brand-pic">
                                            <span id="add_image_text">
                                                <i data-feather="image" class="plus-down-add"></i> Add Image
                                            </span>
                                            <img id="bukti_transfer_image_preview" class="p-1" src="#"
                                                alt="Preview"
                                                style="display:none; max-width: 100%; max-height: 150px; object-fit: cover; border-radius:15px" />
                                        </div>
                                    </div>
                                    <div class="image-upload btn btn-submit text-center" style="height: 2rem;">
                                        <input type="file" id="bukti_transfer_image"
                                            name="bukti_transfer_image" accept="image/*">
                                        <div class="image-uploads" style="margin-top: -0.7rem;">
                                            <h4 style="color: white; font-size: 14px;">Change Image</h4>
                                        </div>
                                    </div>
                                </div>

                                <!-- Kolom Form Input -->
                                <div class="col-lg-10 col-md-9">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="input-blocks mb-3">
                                                <label>Date</label>
                                                <div class="input-groupicon calender-input">
                                                    <i data-feather="calendar" class="info-img"></i>
                                                    <input type="text" class="datetimepicker form-control"
                                                        placeholder="Select Date" id="tanggal_pembayaran">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label class="form-label">Metode Pembayaran</label>
                                                <select class="form-control" id="metode-pembayaran">
                                                    <option value="cash">Cash</option>
                                                    <option value="transfer">Transfer</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-3" id="rekening">
                                            <div class="mb-3">
                                                <label class="form-label">Bank Name*</label>
                                                <select id="bank_id" class="form-select fill select2"></select>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label class="form-label">COA Name*</label>
                                                <select id="coa_id" class="form-select fill select2"></select>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label>Nominal</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">Rp</span>
                                                    <input type="text" class="form-control nominal_only"
                                                        id="nominal_pembayaran">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Notes</label>
                                                <input type="text" class="form-control" id="notes_pembayaran"
                                                    placeholder="Masukkan catatan pembayaran">
                                            </div>
                                        </div>

                                        <div class="col-md-3 pt-1">
                                            <div class="mt-4">
                                                <a href="#" class="btn btnCustom btn-sm btn-submit"
                                                    id="tambah-pembayaran"><i data-feather="plus-circle"
                                                        class="me-2"></i>Tambahkan Pembayaran </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tabel Pembayaran -->
                            <div class="table-responsive mt-4">
                                <table class="table table-striped" id="invoice-detail-table">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Date</th>
                                            <th>Metode Pembayaran</th>
                                            <th>Rekening Penerima</th>
                                            <th>Nominal</th>
                                            <th>Notes</th>
                                            <th class="no-sort">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>

                            <div class="modal-footer-btn text-end mt-3">
                                <button type="button" class="btn btn-cancel me-2"
                                    data-bs-dismiss="modal">Tutup</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

@if (Route::is(['purchase']))
    <!--add popup -->
    <div class="modal fade" id="add-purchase-new">
        <div class="modal-dialog add-centered">
            <div class="modal-content">
                <div class="page-wrapper p-0 m-0">
                    <div class="content p-0">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4 class="modal-title"> Buat Purchase Order</h4>
                            </div>
                            <button type="button" class="close ms-auto" data-bs-dismiss="modal"
                                aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="card">
                            <div class="card-body">
                                <form action="sales-list">
                                    <div class="row">
                                        <div class="col-lg-6 col-sm-6 col-12">
                                            <div class="input-blocks">
                                                <label>Nama Supplier</label>
                                                <select class="select fill select2" id="supplier">
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-sm-6 col-12">
                                            <div class="input-blocks">
                                                <label>Tanggal</label>
                                                <div class="input-groupicon calender-input">
                                                    <i data-feather="calendar" class="info-img"></i>
                                                    <input type="text" class="datetimepicker fill"
                                                        placeholder="Pilih" id="tanggal">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-3 col-sm-6 col-12">
                                                <div class="input-blocks">
                                                    <label>Nilai Potongan</label>
                                                    <input type="text" class="form-control nominal_only" id="nilai-potongan" placeholder="0">
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-sm-6 col-12">
                                                <div class="input-blocks">
                                                    <label>Tipe Potongan</label>
                                                    <select class="form-control select" id="tipe-potongan">
                                                        <option value="persentase">Persentase (%)</option>
                                                        <option value="tetap">Potongan Tetap</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-sm-6 col-12">
                                                <div class="input-blocks">
                                                    <label>&nbsp;</label>
                                                    <button type="button" class="btn btn-primary w-100" id="tambah-potongan">
                                                    Tambahkan
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Discount Recap Section -->
                                        <div class="row" id="discount-recap-container" style="display: none;">
                                            <div class="col-lg-12">
                                                <div class="card mb-3">
                                                    <h6 class="mb-0">Rekap Potongan</h6>
                                                    <div class="card-body p-2">
                                                        <div id="discount-recap-list" class="list-group">
                                                            <!-- Discount items will be added here dynamically -->
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-4 col-sm-6 col-12">
                                                <div class="input-blocks">
                                                    <label>Biaya Pengiriman</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">Rp</span>
                                                        <input type="text" class="form-control nominal_only fill"
                                                            id="biaya_pengiriman">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-sm-6 col-12">
                                                <div class="input-blocks">
                                                    <label>Biaya Tambahan</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">Rp</span>
                                                        <input type="text" class="form-control nominal_only fill"
                                                            id="biaya_tambahan">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-12 col-sm-6 col-12">
                                            <div class="input-blocks">
                                                <label>SKU/Barcode Produk</label>
                                                <div class="input-groupicon select-code">
                                                    <input type="text" class="ps-2"
                                                        placeholder="Ketik kode produk dan pilih" id='add-product'>
                                                    <div class="addonset">
                                                        <img src="{{ URL::asset('/build/img/icons/qrcode-scan.svg') }}"
                                                            alt="img">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="table-responsive no-pagination">
                                        <table class="table datanew" id="order-items-table">
                                            <thead>
                                                <tr>
                                                    <th>Product</th>
                                                    <th>Varian</th>
                                                    <th>SKU</th>
                                                    <th>Qty</th>
                                                    <th>Harga Satuan</th>
                                                    <th>Subtotal</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="row">
                                        <div class="col-lg-6 ms-auto">
                                            <div class="total-order w-100 max-widthauto m-auto mb-4">
                                                <ul>
                                                    <li>
                                                        <h4>Ppn</h4>
                                                        <h5 id='ppn-detail'></h5>
                                                    </li>
                                                    <li>
                                                        <h4>Diskon</h4>
                                                        <h5 id="discount-detail"></h5>
                                                    </li>
                                                    <li>
                                                        <h4>Biaya Pengiriman</h4>
                                                        <h5 id="shipping-cost-detail"></h5>
                                                    </li>
                                                    <li>
                                                        <h4>Biaya Tambahan</h4>
                                                        <h5 id="additional-cost-detail"></h5>
                                                    </li>
                                                    <li>
                                                        <h4>Grand Total</h4>
                                                        <h5 id="grand-total-detail"></h5>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-12 text-end">
                                            <button type="button" class="btn btn-cancel add-cancel me-3"
                                                data-bs-dismiss="modal">Cancel</button>
                                            <button type="button" class="btn btn-save add-sale">Simpan</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /add popup -->
@endif
@if (Route::is(['purchaseDetail']))
    <!-- Add Delivery Notes -->
    <div class="modal fade" id="add-delivery-note">
        <div class="modal-dialog add-centered">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>Buat Good Receipt</h4>
                            </div>
                            <button type="button" class="close ms-auto" data-bs-dismiss="modal"
                                aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <form action="customers">
                                <div class="row">
                                    <div class="col-lg-4 pe-0">
                                        <div class="mb-3">
                                            <label class="form-label">Nama Pengirim</label>
                                            <input type="text" class="form-control fill"
                                                id="nama_pengirim_delivery">
                                        </div>
                                    </div>
                                    <div class="col-lg-4 pe-0">
                                        <div class="mb-3">
                                            <label class="form-label">Penerima</label>
                                            <select class="form-control fill select2" id="nama_penerima_delivery">
                                                <option value="">Pilih Penerima</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 pe-0">
                                        <div class="input-blocks">
                                            <label>Tanggal</label>
                                            <div class="input-groupicon calender-input">
                                                <i data-feather="calendar" class="info-img"></i>
                                                <input type="text" class="datetimepicker fill"
                                                    placeholder="Pilih" id="tanggal_delivery">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="mb-3 input-blocks">
                                            <label class="form-label">Deskripsi</label>
                                            <textarea class="form-control mb-1" id="deskripsi_delivery"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <table class="table" id="good-receipt-detail-table">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Varian</th>
                                                <th>SKU</th>
                                                <th>Jumlah PO</th>
                                                <th>Jumlah yang sudah terkirim</th>
                                                <th>Jumlah yang diterima</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="modal-footer-btn mt-5">
                                    <button type="button" class="btn btn-cancel me-2"
                                        data-bs-dismiss="modal">Batal</button>
                                    <button type="button" class="btn btn-submit"
                                        id="btn_simpan_delivery">Simpan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Add Delivery Notes -->
    <!-- Add Good Receipt -->
    <div class="modal fade" id="add-good-receipt">
        <div class="modal-dialog add-centered">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>Buat Good Receipt</h4>
                            </div>
                            <button type="button" class="close ms-auto" data-bs-dismiss="modal"
                                aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <form action="customers">
                                <div class="row">
                                    <div class="col-lg-4 pe-0">
                                        <div class="mb-3">
                                            <label class="form-label">Nama Penerima</label>
                                            <input type="text" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-lg-4 pe-0">
                                        <div class="input-blocks">
                                            <label>Tanggal</label>
                                            <div class="input-groupicon calender-input">
                                                <i data-feather="calendar" class="info-img"></i>
                                                <input type="text" class="datetimepicker" placeholder="Pilih">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 pe-0">
                                        <div class="input-blocks">
                                            <label class="mb-2">Telepon</label>
                                            <input type="text" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3 input-blocks">
                                            <label class="form-label">Alamat</label>
                                            <textarea class="form-control mb-1"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3 input-blocks">
                                            <label class="form-label">Deskripsi</label>
                                            <textarea class="form-control mb-1"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-body-table">
                                    <div class="table-responsive">
                                        <table class="table  datanew">
                                            <thead>
                                                <tr>
                                                    <th>Product</th>
                                                    <th>SKU</th>
                                                    <th>Category</th>
                                                    <th>Qty</th>
                                                    <th>Satuan</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <div class="productimgname">
                                                            <a href="javascript:void(0);"
                                                                class="product-img stock-img">
                                                                <img src="{{ URL::asset('/build/img/products/stock-img-02.png') }}"
                                                                    alt="product">
                                                            </a>
                                                            <a href="javascript:void(0);">Nike Jordan</a>
                                                        </div>
                                                    </td>
                                                    <td>PT002</td>
                                                    <td>Nike</td>
                                                    <td>
                                                        <div class="product-quantity">
                                                            <span class="quantity-btn"><i
                                                                    data-feather="minus-circle"
                                                                    class="feather-search"></i></span>
                                                            <input type="text" class="quntity-input"
                                                                value="2">
                                                            <span class="quantity-btn">+<i
                                                                    data-feather="plus-circle"
                                                                    class="plus-circle"></i></span>
                                                        </div>
                                                    </td>
                                                    <td>Pcs</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="modal-footer-btn">
                                    <button type="button" class="btn btn-cancel me-2"
                                        data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-submit">Simpan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Add Good Receipt-->
    <!-- Buat Invoice -->
    <div class="modal fade" id="add-invoice">
        <div class="modal-dialog modal-dialog-centered custom-modal-two">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>Tambah invoice</h4>
                            </div>
                            <button type="button" class="close ms-auto" data-bs-dismiss="modal"
                                aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="profile-pic-upload mb-3">
                                        <div class="profile-pic brand-pic">
                                            <span id="add_image_text"><i data-feather="image"
                                                    class="plus-down-add"></i> Add
                                                Image</span>
                                            <img id="invoice_image_preview" class="p-1" src="#"
                                                alt="Preview"
                                                style="display:none; max-width: 100%; max-height: 150px; object-fit: cover;border-radius:15px" />

                                        </div>
                                        <div class="image-upload btn btn-submit mt-4"
                                            style="justify-content: center; height: 2rem;text-align: center; width: 30%;">
                                            <input type="file" id="invoice_image" name="invoice_image"
                                                accept="image/*">
                                            <div class="image-uploads"
                                                style="margin-top: -0.7rem; width: 100%; margin-left: -1rem; text-align: center; ">
                                                <h4 style="color: white">Change Image</h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <div class="input-blocks">
                                            <label>Tgl Invoice</label>
                                            <div class="input-groupicon calender-input">
                                                <i data-feather="calendar" class="info-img"></i>
                                                <input type="text" class="datetimepicker form-control"
                                                    placeholder="Select Date" id="tanggal_invoice">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <div class="input-blocks">
                                            <label>Tgl Jatuh Tempo</label>
                                            <div class="input-groupicon calender-input">
                                                <i data-feather="calendar" class="info-img"></i>
                                                <input type="text" class="datetimepicker form-control"
                                                    placeholder="Select Date" id="jatuh_tempo">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <label class="form-label">Jumlah</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" class="form-control nominal_only"
                                                id="nominal_invoice">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer-btn">
                                <button type="button" class="btn btn-cancel me-2"
                                    data-bs-dismiss="modal">Tutup</button>
                                <button type="button" class="btn btn-submit me-2" id="submit-invoice">Tambah
                                    Invoice</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Buat Invoice -->

    <!-- Lihat Bukti Pembayaran -->
    <div class="modal fade" id="lihat-bukti">
        <div class="modal-dialog add-centered modal-lg">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>Pembayaran Invoice</h4>
                            </div>
                            <button type="button" class="close ms-auto" data-bs-dismiss="modal"
                                aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>

                        <div class="modal-body custom-modal-body">
                            <div class="row">
                                <!-- Kolom Gambar -->
                                <div class="col-lg-2 col-md-3 mb-3">
                                    <label>Gambar</label>
                                    <div class="profile-pic-upload mb-1 text-center">
                                        <div class="profile-pic brand-pic">
                                            <span id="add_image_text">
                                                <i data-feather="image" class="plus-down-add"></i> Add Image
                                            </span>
                                            <img id="bukti_transfer_image_preview" class="p-1" src="#"
                                                alt="Preview"
                                                style="display:none; max-width: 100%; max-height: 150px; object-fit: cover; border-radius:15px" />
                                        </div>
                                    </div>
                                    <div class="image-upload btn btn-submit text-center" style="height: 2rem;">
                                        <input type="file" id="bukti_transfer_image"
                                            name="bukti_transfer_image" accept="image/*">
                                        <div class="image-uploads" style="margin-top: -0.7rem;">
                                            <h4 style="color: white; font-size: 14px;">Change Image</h4>
                                        </div>
                                    </div>
                                </div>

                                <!-- Kolom Form Input -->
                                <div class="col-lg-10 col-md-9">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="input-blocks mb-3">
                                                <label>Date</label>
                                                <div class="input-groupicon calender-input">
                                                    <i data-feather="calendar" class="info-img"></i>
                                                    <input type="text" class="datetimepicker form-control"
                                                        placeholder="Select Date" id="tanggal_pembayaran">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label class="form-label">Metode Pembayaran</label>
                                                <select class="form-control" id="metode-pembayaran">
                                                    <option value="cash">Cash</option>
                                                    <option value="transfer">Transfer</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-3" id="rekening">
                                            <div class="mb-3">
                                                <label class="form-label">Bank Name</label>
                                                <select id="bank_id" class="form-select fill select2"></select>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label class="form-label">COA Name</label>
                                                <select id="coa_id" class="form-select fill select2"></select>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label>Nominal</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">Rp</span>
                                                    <input type="text" class="form-control nominal_only"
                                                        id="nominal_pembayaran">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Notes</label>
                                                <input type="text" class="form-control" id="notes_pembayaran"
                                                    placeholder="Masukkan catatan pembayaran">
                                            </div>
                                        </div>

                                        <div class="col-md-3 pt-1">
                                            <div class="mt-4">
                                                <a href="#" class="btn btnCustom btn-sm btn-submit"
                                                    id="tambah-pembayaran"><i data-feather="plus-circle"
                                                        class="me-2"></i>Tambahkan Pembayaran </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tabel Pembayaran -->
                            <div class="table-responsive mt-4">
                                <table class="table table-striped" id="invoice-detail-table">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Date</th>
                                            <th>Metode Pembayaran</th>
                                            <th>Rekening Penerima</th>
                                            <th>Nominal</th>
                                            <th>Notes</th>
                                            <th class="no-sort">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>

                            <div class="modal-footer-btn text-end mt-3">
                                <button type="button" class="btn btn-cancel me-2"
                                    data-bs-dismiss="modal">Tutup</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lihat Bukti Pembayaran -->
@endif

@if (Route::is(['ProductIssues']))
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
                            <button type="button" class="close ms-auto" data-bs-dismiss="modal"
                                aria-label="Close">
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
                                        <div class="input-blocks mb-3" style="position:relative; z-index:1051;">
                                            <label>Date</label>
                                            <div class="input-groupicon calender-input">
                                                <i data-feather="calendar" class="info-img"></i>
                                                <input type="text" class="datetimepicker form-control"
                                                    id="pi_date" placeholder="Select Date">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3" style="position:relative; z-index:1049;">
                                            <label class="form-label">Jenis Retur</label>
                                            <select class="select" id="tipe_return">
                                                <option value="1" selected>Retur ke Supplier / Rusak Gudang
                                                </option>
                                                <option value="2">Customer Refund</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3" style="position:relative; z-index:1049;">
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
                                            <input type="text" class="form-control number-only" id="pi_qty">
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3" style="position:relative; z-index:1049;">
                                            <label class="form-label">Store/Warehouse</label>
                                            <select name="" id="store_id"
                                                class="form-control select2 fill select2Input">
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Notes</label>
                                            <input type="text" class="form-control" id="pi_notes">
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer-btn">
                                    <button type="button" class="btn btn-cancel me-2"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-submit btn-save">Create Product
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
@if (Route::is(['stockAlert']))
    <!-- Add Unit -->
    <div class="modal fade" id="add-alert-new">
        <div class="modal-dialog modal-dialog-centered custom-modal-two">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>Tambah Peringatan Stok</h4>
                            </div>
                            <button type="button" class="close ms-auto" data-bs-dismiss="modal"
                                aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <div class="row">
                                <div class="col-12">
                                    <label class="form-label">Input SKU/Barcode Stock</label>
                                    <input type="text" class="form-control" id="find_stock">
                                </div>
                                <div class="col-12">
                                    <div class="card mb-1 mt-3">
                                        <div>
                                            <h5 class="card-title mb-3">Detail Produk</h5>
                                            <div class="row">
                                                <div class="col-6">
                                                    <p class="mb-1"><strong>Nama Produk:</strong></p>
                                                    <p id="nama_produk" class="mb-2"></p>
                                                    <p class="mb-1"><strong>Varian:</strong></p>
                                                    <p id="varian" class="mb-2"></p>
                                                </div>
                                                <div class="col-6">
                                                    <p class="mb-1"><strong>SKU:</strong></p>
                                                    <p id="sku" class="mb-2"></p>
                                                    <p class="mb-1"><strong>Barcode:</strong></p>
                                                    <p id="barcode" class="mb-2"></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="input-blocks">
                                                <label class="form-label">Peringatan Kuantitas</label>
                                                <input class="number-only" type="text"id="peringatan_kuantitas"
                                                    value="">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="input-blocks">
                                                <label class="form-label">Satuan</label>
                                                <select name="" id="satuan"
                                                    class="form-control select2 fill">
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="input-blocks">
                                        <label>Store</label>
                                        <select name="" id="store_id" class="form-control select2 fill">
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer-btn">
                                <button type="button" class="btn btn-cancel me-2"
                                    data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-save">Buat Peringatan Stok</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Add Unit -->
@endif
@if (Route::is(['suppliers']))
    <!-- Add Supplier -->
    <div class="modal fade" id="add-supplier">
        <div class="modal-dialog modal-dialog-centered custom-modal-two">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>Add Supplier</h4>
                            </div>
                            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <form action="suppliers">
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="new-employee-field">
                                            <span>Avatar</span>
                                            <div class="profile-pic-upload mb-2">
                                                <div class="profile-pic brand-pic">
                                                    <span id="add_image_text"><i data-feather="plus-circle"
                                                            class="plus-down-add"></i> Add
                                                        Image</span>
                                                    <img id="supplier_image_preview" class="p-1" src="#"
                                                        alt="Preview"
                                                        style="display:none; max-width: 100%; max-height: 150px; object-fit: cover;border-radius:15px" />

                                                </div>
                                                <div class="image-upload mb-0">
                                                    <input type="file" id="supplier_image" name="brand_image"
                                                        accept="image/*">
                                                    <div class="image-uploads">
                                                        <h4>Change Image</h4>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="input-blocks">
                                            <label>Supplier Name</label>
                                            <input type="text" class="form-control fill" id="supplier_name">
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="input-blocks">
                                            <label>Email</label>
                                            <input type="email" class="form-control fill" id="supplier_email">
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="input-blocks">
                                            <label>Telepon</label>
                                            <input type="text" class="form-control fill" id="supplier_phone">
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="input-blocks">
                                            <label class="form-label">Negara</label>
                                            <select class="form-control fill select2" id="country_id"></select>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Provinsi</label>
                                            <select class=" form-control fill select2" id="state_id">
                                            </select>

                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3 mb-0">
                                            <label class="form-label">Kota</label>
                                            <select class="form-control fill select2" id="city_id"></select>
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="input-blocks">
                                            <label>Alamat</label>
                                            <input type="text" class="form-control fill" id="supplier_address">
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3 mb-0">
                                            <label class="form-label">Nama Bank </label>
                                            <input type="text" class="form-control fill"
                                                id="supplier_bank_name">
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3 mb-0">
                                            <label class="form-label">Nama Rekening</label>
                                            <input type="text" class="form-control fill"
                                                id="supplier_nama_rekening">
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="mb-3 mb-0">
                                            <label class="form-label">Nomer Rekening</label>
                                            <input type="text" class="form-control fill number-only"
                                                id="supplier_nomer_rekening">
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="mb-0 input-blocks">
                                            <label class="form-label">Descriptions</label>
                                            <textarea class="form-control mb-1" id="supplier_deskripsi"></textarea>
                                            <p>Maximum 600 Characters</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="modal-footer-btn">
                                    <button type="button" class="btn btn-cancel me-2"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-save">Submit</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

@if (Route::is(['inventaris']))
    <!-- Add Supplier -->
    <div class="modal fade" id="add-inventaris">
        <div class="modal-dialog modal-dialog-centered custom-modal-two">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>Tambah Inventaris</h4>
                            </div>
                            <button type="button" class="close ms-auto" data-bs-dismiss="modal"
                                aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <form action="suppliers">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="input-blocks">
                                            <label>Nama Barang</label>
                                            <input type="text" class="form-control fill" id="inventaris_name">
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="input-blocks">
                                            <label>Kategori</label>
                                            <select class="form-control fill" id="inventaris_kategori">
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="input-blocks">
                                            <label>Tipe</label>
                                            <select class="form-control fill" id="inventaris_tipe">
                                                <option value="inventory">Inventory</option>
                                                <option value="holdings">Holdings</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-9">
                                        <div class="input-blocks">
                                            <label>Kode Inventaris</label>
                                            <input type="text" class="form-control list fill"
                                                id="inventaris_code">
                                            <button type="button" class="btn btn-primaryadd"
                                                id="buat-kode-inventaris" data-fieldtocomplete="inventaris_code">
                                                Buat Kode
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="input-blocks">
                                            <label>Qty</label>
                                            <input type="number" min="1" value="1"
                                                id="inventaris_qty" class="form-control fill number-only">
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="input-blocks">
                                            <label>Store</label>
                                            <select name="" id="store_id" class="form-control select2">
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="input-blocks">
                                            <label>Tempat Beli</label>
                                            <input type="text" id="inventaris_tempat_beli"
                                                class="form-control fill">
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="input-blocks">
                                            <label>Harga Beli</label>
                                            <div class="input-group">
                                                <span class="input-group-text">Rp</span>
                                                <input type="text" class="form-control nominal_only"
                                                    placeholder="0" id="inventaris_harga_beli">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="new-employee-field">
                                        <span>Gambar Inventaris</span>
                                        <div class="profile-pic-upload mb-2">
                                            <div class="profile-pic">
                                                <span id="add_image_text"><i data-feather="plus-circle"
                                                        class="plus-down-add"></i> Add
                                                    Image</span>
                                                <img id="inventaris_image_preview" class="p-1" src="#"
                                                    alt="Preview"
                                                    style="display:none; max-width: 100%; max-height: 150px; object-fit: cover;border-radius:15px" />

                                            </div>
                                            <div class="input-blocks mb-0">
                                                <div class="image-upload mb-0">
                                                    <input type="file" id="inventaris_image">
                                                    <div class="image-uploads">
                                                        <h4>Change Image</h4>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer-btn">
                                    <button type="button" class="btn btn-cancel me-2"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-save">Submit</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif


@if (Route::is(['bank-settings-grid']))
    <!-- Add Bank Account -->
    <div class="modal fade" id="add-bank">
        <div class="modal-dialog modal-dialog-centered custom-modal-two">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>Add Bank Account</h4>
                            </div>
                            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <form action="bank-settings-grid">
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="mb-3">
                                            <label class="form-label">Bank Name <span> *</span></label>
                                            <input type="text" class="form-control fill" id="bank_nama">
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="mb-3">
                                            <label class="form-label">Account Number <span> *</span></label>
                                            <input type="text" class="form-control fill" id="bank_nomer">
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="mb-3">
                                            <label class="form-label">Account Name <span> *</span></label>
                                            <input type="text" class="form-control fill"
                                                id="bank_nama_account">
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="mb-3">
                                            <label class="form-label">Branch <span> *</span></label>
                                            <input type="text" class="form-control fill" id="bank_branch">
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="mb-3">
                                            <label class="form-label">IFSC <span> *</span></label>
                                            <input type="text" class="form-control fill" id="bank_ifsc">
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div
                                            class="status-toggle modal-status d-flex justify-content-between align-items-center">
                                            <span class="status-label">Make as default</span>
                                            <input type="checkbox" id="bank_default" class="check">
                                            <label for="bank_default" class="checktoggle"></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer-btn">
                                    <button type="button" class="btn btn-cancel me-2"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-save">Submit</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Add Bank Account -->

    <!-- Edit Bank Account -->
    <div class="modal fade" id="edit-account">
        <div class="modal-dialog modal-dialog-centered custom-modal-two">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>Edit Bank Account</h4>
                            </div>
                            <div
                                class="status-toggle modal-status d-flex justify-content-between align-items-center ms-auto me-2">
                                <input type="checkbox" id="user4" class="check" checked>
                                <label for="user4" class="checktoggle"> </label>
                            </div>
                            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <form action="bank-settings-grid">
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="mb-3">
                                            <label class="form-label">Bank Name <span> *</span></label>
                                            <input type="text" class="form-control" value="HDFC">
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="mb-3">
                                            <label class="form-label">Account Number <span> *</span></label>
                                            <input type="text" class="form-control" value="**** **** 1832">
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="mb-3">
                                            <label class="form-label">Account Name <span> *</span></label>
                                            <input type="text" class="form-control" value="Mathew">
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="mb-3">
                                            <label class="form-label">Branch <span> *</span></label>
                                            <input type="text" class="form-control" value="Bringham">
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="mb-3">
                                            <label class="form-label">IFSC <span> *</span></label>
                                            <input type="text" class="form-control" value="124547">
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div
                                            class="status-toggle modal-status d-flex justify-content-between align-items-center mb-3">
                                            <span class="status-label">Status</span>
                                            <input type="checkbox" id="user5" class="check" checked="">
                                            <label for="user5" class="checktoggle"></label>
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div
                                            class="status-toggle modal-status d-flex justify-content-between align-items-center">
                                            <span class="status-label">Make as default</span>
                                            <input type="checkbox" id="user6" class="check" checked="">
                                            <label for="user6" class="checktoggle"></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer-btn">
                                    <button type="button" class="btn btn-cancel me-2"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-submit">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Edit Bank Account -->
@endif


@if (Route::is(['tax-rates']))
    <!-- Add Tax Rates -->
    <div class="modal fade" id="add-tax">
        <div class="modal-dialog modal-dialog-centered custom-modal-two">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>Add Tax Rates</h4>
                            </div>
                            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <form action="tax-rates">
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="mb-3">
                                            <label class="form-label">Name <span> *</span></label>
                                            <input type="text" class="form-control fill" id="tax_nama">
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="mb-0">
                                            <label class="form-label">Tax Rate % <span> *</span></label>
                                            <input type="text" class="form-control number-only fill "
                                                id="tax_value">
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer-btn">
                                    <button type="button" class="btn btn-cancel me-2"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-save">Submit</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Add Tax Rates -->

    <!-- Edit Tax Rates -->
    <div class="modal fade" id="edit-tax">
        <div class="modal-dialog modal-dialog-centered custom-modal-two">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>Edit Tax Rates</h4>
                            </div>
                            <div
                                class="status-toggle modal-status d-flex justify-content-between align-items-center ms-auto me-2">
                                <input type="checkbox" id="user4" class="check" checked>
                                <label for="user4" class="checktoggle"> </label>
                            </div>
                            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <form action="tax-rates">
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="mb-3">
                                            <label class="form-label">Name <span> *</span></label>
                                            <input type="text" class="form-control" value="VAT">
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="mb-0">
                                            <label class="form-label">Tax Rate % <span> *</span></label>
                                            <input type="text" class="form-control" value="16">
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer-btn">
                                    <button type="button" class="btn btn-cancel me-2"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-submit">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
@if (Route::is(['workOrder']))
    <!--add popup -->
    <div class="modal fade" id="add-spk">
        <div class="modal-dialog add-centered">
            <div class="modal-content">
                <div class="page-wrapper p-0 m-0" style="min-height:500px!important">
                    <div class="content p-0">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4> Tambahkan Surat Perintah Kerja</h4>
                            </div>
                            <button type="button" class="close ms-auto" data-bs-dismiss="modal"
                                aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-lg-4 col-sm-6 col-12">
                                        <div class="input-blocks">
                                            <label>Nama Customer</label>
                                            <div class="row">
                                                <div class="col-lg-12 col-sm-10 col-10">
                                                    <select class="select select2" id="customer_id">
                                                    </select>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6 col-12">
                                        <div class="input-blocks">
                                            <label>Tanggal</label>
                                            <div class="input-groupicon calender-input">
                                                <i data-feather="calendar" class="info-img"></i>
                                                <input type="text" class="datetimepicker" placeholder="Pilih"
                                                    id="work_order_date">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-3 col-sm-6 col-12">
                                        <div class="input-blocks">
                                            <label>Nama Pekerja</label>
                                            <input type="text" class="form-control fill"
                                                id="work_order_pekerja">
                                        </div>
                                    </div>

                                    <div class="col-lg-2 col-sm-6 col-12">
                                        <div class="input-blocks">
                                            <label>Status</label>
                                            <select class="select" id="work_order_status">
                                                <option value="Pending">Pending</option>
                                                <option value="Sedang Diproses">Sedang Diproses</option>
                                                <option value="Selesai">Selesai</option>
                                                <option value="Batalkan">Batalkan</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6 col-12">
                                        <div class="input-blocks">
                                            <label>Store</label>
                                            <select class="select select2" id="store_id">
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-12 col-sm-6 col-12">
                                        <div class="input-blocks">
                                            <label>Nama Produk</label>
                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="input-groupicon select-code">
                                                        <input type="text" placeholder="Ketik kode produk"
                                                            id="search">
                                                        <div class="addonset">
                                                            <img src="{{ URL::asset('/build/img/icons/qrcode-scan.svg') }}"
                                                                alt="img">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="spinner-border text-primary" role="status"
                                                        id="loading" style="display: none">
                                                        <span class="visually-hidden">Loading...</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                                <div class="table-responsive no-pagination">
                                    <table class="table  datanew">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Qty</th>
                                                <th class="text-end">Purchase Price(Rp)</th>
                                                <th class="text-end">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbProduct">
                                        </tbody>
                                    </table>
                                </div>

                                <div class="row">
                                    <div class="col-lg-6 ms-auto">
                                        <div class="total-order w-100 max-widthauto m-auto mb-4">
                                            <ul>
                                                <li>
                                                    <h4>Total</h4>
                                                    <h5 id="total">Rp 0.00</h5>
                                                </li>
                                                <li>
                                                    <h4>Ppn</h4>
                                                    <h5 id="ppn">Rp 0.00</h5>
                                                </li>
                                                <li>
                                                    <h4>Grand Total</h4>
                                                    <h5 id="grand-total">Rp 0.00</h5>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">

                                    <div class="col-lg-12 text-end">
                                        <button type="button" class="btn btn-cancel add-cancel me-3"
                                            data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit"
                                            class="btn btn-submit btn-save add-sale">Submit</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /add popup -->
@endif

@if (Route::is(['categoryCoa']))
    <!-- Add Category -->
    <div class="modal fade" id="add-category-coa">
        <div class="modal-dialog modal-dialog-centered custom-modal-two">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4 class="modal-title">Create Category COA</h4>
                            </div>
                            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <form action="{{ url('category-list') }}">
                                <div class="mb-3">
                                    <label class="form-label">Category Kode</label>
                                    <input type="text" class="form-control fill" id="cc_kode">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Category Name</label>
                                    <input type="text" class="form-control fill" id="cc_nama">
                                </div>
                                <div class="modal-footer-btn">
                                    <button type="button" class="btn btn-cancel me-2"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-save">Create Category</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Add Category -->
    <!-- /Edit Category -->
@endif

@if (Route::is(['coa']))
    <!-- Add Category -->
    <div class="modal fade" id="add-coa">
        <div class="modal-dialog modal-dialog-centered custom-modal-two">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4 class="modal-title">Create COA</h4>
                            </div>
                            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <form action="{{ url('category-list') }}">
                                <div class="mb-3">
                                    <label class="form-label">Category COA</label>
                                    <select name="" id="cc_id" class="form-select">
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Coa Kode</label>
                                    <input type="text" class="form-control fill" id="coa_kode">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Coa Name</label>
                                    <input type="text" class="form-control fill" id="coa_nama">
                                </div>
                                <div class="modal-footer-btn">
                                    <button type="button" class="btn btn-cancel me-2"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-save">Create Coa</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Add Category -->
    <!-- /Edit Category -->
@endif

@if (Route::is(['subCoa']))
    <!-- Add Category -->
    <div class="modal fade" id="add-subcoa">
        <div class="modal-dialog modal-dialog-centered custom-modal-two">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4 class="modal-title">Create Sub COA</h4>
                            </div>
                            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <form action="{{ url('category-list') }}">
                                <div class="mb-3">
                                    <label class="form-label">Subcoa Name</label>
                                    <input type="text" class="form-control fill" id="sc_nama">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">COA</label>
                                    <select name="" id="coa_id" class="form-select fill">
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Subcoa Kode</label>
                                    <input type="text" class="form-control fill" id="sc_kode">
                                </div>
                                <div class="modal-footer-btn">
                                    <button type="button" class="btn btn-cancel me-2"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-save">Create Subcoa</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Add Category -->
    <!-- /Edit Category -->
@endif

@if (Route::is(['journal']))
    <!-- Add coupons -->
    <div class="modal fade" id="add-journal">
        <div class="modal-dialog modal-dialog-centered custom-modal-two">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div
                            class="modal-header border-0 custom-modal-header d-flex justify-content-between align-items-center">
                            <div class="page-title">
                                <h4>Buat Journal</h4>
                            </div>
                            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <form action="coupons">
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="input-blocks">
                                            <label>Tanggal</label>
                                            <div class="input-groupicon calender-input">
                                                <i data-feather="calendar" class="info-img"></i>
                                                <input type="text" class="datetimepicker fill form-control"
                                                    placeholder="Select Date" id="je_date">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Jenis</label>
                                            <select class="select fill" id="je_jenis">
                                                <option selected value="Debit">Debit</option>
                                                <option value="Kredit">Kredit</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Nominal</label>
                                            <input type="text" class="form-control fill nominal_only"
                                                id="je_nominal">
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Coa Name</label>
                                            <select name="" id="coa_id"
                                                class="form-select fill select2">
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Bank Name</label>
                                            <select name="" id="bank_id"
                                                class="form-select fill select2">
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Reference</label>
                                            <input type="text" class="form-control" id="je_reference">
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="input-blocks summer-description-box transfer mb-3">
                                            <label>Deskripsi</label>
                                            <textarea class="form-control h-100" rows="5" id="je_description"></textarea>
                                        </div>
                                    </div>
                                </div>


                                <div class="modal-footer-btn">
                                    <button type="button" class="btn btn-cancel me-2"
                                        data-bs-dismiss="modal">Batal</button>
                                    <button type="button" class="btn btn-submit btn-save">Buat Journal</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif


@if (Route::is(['users']))
    <!-- Add User -->
    <div class="modal fade" id="add-units">
        <div class="modal-dialog modal-dialog-centered custom-modal-two">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>Add User</h4>
                            </div>
                            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <form action="users">
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="new-employee-field">
                                            <span>Avatar</span>
                                            <div class="profile-pic-upload mb-2">
                                                <div class="profile-pic">
                                                    <span><i data-feather="plus-circle" class="plus-down-add"></i>
                                                        Profile Photo</span>
                                                </div>
                                                <div class="input-blocks mb-0">
                                                    <div class="image-upload mb-0">
                                                        <input type="file">
                                                        <div class="image-uploads">
                                                            <h4>Change Image</h4>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="input-blocks">
                                            <label>User Name</label>
                                            <input type="text" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="input-blocks">
                                            <label>Phone</label>
                                            <input type="text" class="form-control">
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="input-blocks">
                                            <label>Email</label>
                                            <input type="email" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="input-blocks">
                                            <label>Role</label>
                                            <select class="select">
                                                <option>Choose</option>
                                                <option>Manager</option>
                                                <option>Admin</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="input-blocks">
                                            <label>Password</label>
                                            <div class="pass-group">
                                                <input type="password" class="pass-input">
                                                <span class="fas toggle-password fa-eye-slash"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="input-blocks">
                                            <label>Confirm Passworrd</label>
                                            <div class="pass-group">
                                                <input type="password" class="pass-input">
                                                <span class="fas toggle-password fa-eye-slash"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="mb-0 input-blocks">
                                            <label class="form-label">Descriptions</label>
                                            <textarea class="form-control mb-1">Type Message</textarea>
                                            <p>Maximum 600 Characters</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer-btn">
                                    <button type="button" class="btn btn-cancel me-2"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-submit">Submit</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Add User -->
@endif

@if (Route::is(['rolesPermissions']))
    <!-- Add Role -->
    <div class="modal fade" id="add-role">
        <div class="modal-dialog modal-dialog-centered custom-modal-two">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>Create Role</h4>
                            </div>
                            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <form action="roles-permissions">
                                <div class="mb-0">
                                    <label class="form-label">Role Name</label>
                                    <input type="text" class="form-control fill" id="role_name">
                                </div>
                                <div class="modal-footer-btn">
                                    <button type="button" class="btn btn-cancel me-2"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-save">Create Role</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Add Role -->
@endif
@if (Route::is(['stockTransfer']))

    <div class="modal fade modal-default" id="accepts-stock-transfer">
    <div class="modal-dialog add-centered">
        <div class="modal-content">
            <div class="page-wrapper-new p-0">
                <div class="content">

                    <!-- HEADER -->
                    <div class="modal-header border-0 custom-modal-header">
                        <div class="page-title">
                            <h4>Accept Stock Transfer</h4>
                        </div>
                        <button type="button" class="close ms-auto" data-bs-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <!-- BODY -->
                    <div class="modal-body custom-modal-body">

                        <div class="row">
                            <div class="col-lg-4 pe-0">
                                <div class="mb-3">
                                    <label class="form-label">Pengirim</label><br>
                                    <label class="fw-bold" id="acc-pengirim"></label>
                                </div>
                            </div>

                            <div class="col-lg-4 pe-0">
                                <div class="input-blocks">
                                    <label class="mb-2">Dari</label>
                                    <label class="fw-bold" id="acc-dari"></label>
                                </div>
                            </div>

                            <div class="col-lg-4 pe-0">
                                <div class="input-blocks">
                                    <label>Tanggal Pengiriman</label>
                                    <label class="fw-bold" id="acc-tanggal"></label>
                                </div>
                            </div>

                            <div class="col-lg-4 pe-0">
                                <div class="mb-3">
                                    <label class="form-label">Penerima</label><br>
                                    <label class="fw-bold" id="acc-penerima"></label>
                                </div>
                            </div>

                            <div class="col-lg-4 pe-0">
                                <div class="input-blocks">
                                    <label class="mb-2">Ke</label>
                                    <label class="fw-bold" id="acc-ke"></label>
                                </div>
                            </div>

                            <div class="col-lg-12">
                                <div class="mb-3 input-blocks">
                                    <label class="form-label">Catatan</label><br>
                                    <label class="fw-bold" id="acc-catatan"></label>
                                </div>
                            </div>
                        </div>

                        <h5 class="mt-3">Produk yang di Transfer</h5>
                        <div class="row col-3 ps-2 mt-2">
                            <input type="search" name="" id="searchProduct" class="form-control input-sm" placeholder="Search Barcode">
                        </div>
                        <!-- TABLE -->
                        <div class="row mt-2">
                            <div class="col-lg-12">
                                <div class="table-responsive product-list mt-2 w-full">
                                    <table class="table" id="acc-transfer-product-table">
                                        <thead>
                                            <tr>
                                                <th>Produk</th>
                                                <th>Varian</th>
                                                <th>SKU</th>
                                                <th>Jumlah</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbAccProduct"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- FOOTER -->
                        <div class="modal-footer-btn mt-3">
                            <button type="button" class="btn btn-cancel me-2" data-bs-dismiss="modal">Batal</button>
                            <button type="button" class="btn btn-submit" id="btn-acc-save">Simpan</button>
                        </div>

                    </div><!-- END BODY -->

                </div>
            </div>
        </div>
    </div>
</div>

    <!-- Add Stock Transfer -->
    <div class="modal fade" id="add-stock-transfer">
        <div class="modal-dialog add-centered">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>Stock Transfer</h4>
                            </div>
                            <button type="button" class="close ms-auto" data-bs-dismiss="modal"
                                aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <form action="customers">
                                <div class="row">
                                    <div class="col-lg-4 pe-0">
                                        <div class="mb-3">
                                            <label class="form-label">Pengirim</label>
                                            <select class="fill select2" id="pengirim"></select>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 pe-0">
                                        <div class="input-blocks">
                                            <label class="mb-2">Dari</label>
                                            <select class="select fill select2" id="dari">
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 pe-0">
                                        <div class="input-blocks">
                                            <label>Tanggal Pengiriman</label>
                                            <div class="input-groupicon calender-input">
                                                <i data-feather="calendar" class="info-img"></i>
                                                <input type="text" class="datetimepicker" placeholder="Pilih"
                                                    id="tanggal">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 pe-0">
                                        <div class="mb-3">
                                            <label class="form-label">Penerima</label>
                                            <select class="fill select2" id="penerima"></select>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 pe-0">
                                        <div class="input-blocks">
                                            <label class="mb-2">Ke</label>
                                            <select class="select fill select2" id="ke">
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="mb-3 input-blocks">
                                            <label class="form-label">Catatan</label>
                                            <textarea class="form-control mb-1" id="catatan"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <h5>Cari Produk</h5>
                                <div class="search-set mt-2">
                                    <div class="search-input">
                                        <a href="javascript:void(0);" class="btn btn-searchset"><i
                                                data-feather="search" class="feather-search"></i></a>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <div class="table-responsive">
                                        <div class="table-responsive product-list">
                                            <table class="table datanew" id="product-table">
                                                <thead>
                                                    <tr>
                                                        <th>Product</th>
                                                        <th>Varian</th>
                                                        <th>SKU</th>
                                                        <th>Kategori</th>
                                                        <th>Merk</th>
                                                        <th>Jumlah</th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="col-lg-12 mt-3">
                                        <div class="product-list">
                                            <h5>Produk yang di Transfer</h5>
                                        </div>
                                        <div class="table-responsive product-list mt-2 w-full">
                                            <table class="table" id='transfer-product-table'>
                                                <thead>
                                                    <tr>
                                                        <th>Produk</th>
                                                        <th>Varian</th>
                                                        <th>SKU</th>
                                                        <th>Jumlah</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="modal-footer-btn">
                                        <button type="button" class="btn btn-cancel me-2"
                                            data-bs-dismiss="modal">Batal</button>
                                        <button type="button" class="btn btn-submit"
                                            id="btn-save">Simpan</button>
                                    </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Stock Transfer -->

    
@endif
@if (Route::is(['POS']))
    <!-- Payment Completed -->
    <div class="modal fade modal-default" id="payment-completed" aria-labelledby="payment-completed">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <form action="pos">
                        <div class="icon-head">
                            <a href="{{ url('pos-design') }}">
                                <i data-feather="check-circle" class="feather-40"></i>
                            </a>
                        </div>
                        <h4>Payment Completed</h4>
                        <p class="mb-0">Do you want to Print Receipt for the Completed Order</p>
                        <div class="modal-footer d-sm-flex justify-content-between">
                            <button type="button" class="btn btn-primary flex-fill " data-bs-toggle="modal"
                                data-bs-target="#print-receipt">Print Receipt<i
                                    class="feather-arrow-right-circle icon-me-5 mt-3 ms-1"></i></button>
                            <button type="button" class="btn btn-secondary flex-fill">Next Order<i
                                    class="feather-arrow-right-circle icon-me-5 mt-3 ms-1"></i></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- /Payment Completed -->

    <!-- Print Receipt -->
    <div class="modal fade modal-default" id="print-receipt" aria-labelledby="print-receipt">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="d-flex justify-content-end">
                    <button type="button" class="close ms-auto p-0" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="icon-head text-center">
                        <a href="{{ url('pos-design') }}">
                            <img src="{{ URL::asset('/assets/indoraya_logo.png') }}" width="100"
                                height="30" alt="Receipt Logo">
                        </a>
                    </div>
                    <div class="text-center info text-center">
                        <h6>INDORAYA</h6>
                        <p class="mb-0">Phone Number: +1 5656665656</p>
                        <p class="mb-0">Email: <a href="mailto:example@gmail.com">example@gmail.com</a></p>
                    </div>
                    <div class="tax-invoice">
                        <h6 class="text-center">Pembelian</h6>
                        <div class="row">
                            <div class="col-sm-12 col-md-6">
                                <div class="invoice-user-name"><span>Nama: </span><span>John Doe</span></div>
                                <div class="invoice-user-name"><span>Invoice No: </span><span>CS132453</span></div>
                            </div>
                            <div class="col-sm-12 col-md-6">
                                <div class="invoice-user-name"><span>Nama Sales: </span><span>Denny</span></div>
                                <div class="invoice-user-name"><span>Tanggal: </span><span>01.07.2022</span></div>
                            </div>
                        </div>
                    </div>
                    <table class="table-borderless w-100 table-fit">
                        <thead>
                            <tr>
                                <th># Item</th>
                                <th>Price</th>
                                <th>Qty</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1. Casing Samsung</td>
                                <td>Rp 30000</td>
                                <td>3</td>
                                <td class="text-end">Rp 90000</td>
                            </tr>
                            <tr>
                                <td>2. Charger</td>
                                <td>Rp 50000</td>
                                <td>2</td>
                                <td class="text-end">Rp 100000</td>
                            </tr>
                            <tr>
                                <td colspan="4">
                                    <table class="table-borderless w-100 table-fit">
                                        <tr>
                                            <td>Sub Total :</td>
                                            <td class="text-end">Rp 190000</td>
                                        </tr>
                                        <tr>
                                            <td>Discount :</td>
                                            <td class="text-end">-Rp 10000</td>
                                        </tr>
                                        <tr>
                                            <td>Total :</td>
                                            <td class="text-end">Rp 198000</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="text-center invoice-bar">
                        <p>Terimakasih Sudah Berbelanja di Indoraya</p>
                        <a href="javascript:void(0);" class="btn btn-primary">Cetak Struk</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Print Receipt -->

    <!-- Products -->
    <div class="modal fade modal-default pos-modal" id="products" aria-labelledby="products">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header p-4 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <h5 class="me-4">Products</h5>
                        <span class="badge bg-info d-inline-block mb-0">Order ID : #666614</span>
                    </div>
                    <button type="button" class="close ms-auto" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <form action="pos">
                        <div class="product-wrap">
                            <div class="product-list d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center flex-fill">
                                    <a href="javascript:void(0);" class="img-bg me-2">
                                        <img src="{{ URL::asset('/build/img/products/pos-product-16.png') }}"
                                            alt="Products">
                                    </a>
                                    <div class="info d-flex align-items-center justify-content-between flex-fill">
                                        <div>
                                            <span>PT0005</span>
                                            <h6><a href="javascript:void(0);">Red Nike Laser</a></h6>
                                        </div>
                                        <p>$2000</p>
                                    </div>
                                </div>

                            </div>
                            <div class="product-list d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center flex-fill">
                                    <a href="javascript:void(0);" class="img-bg me-2">
                                        <img src="{{ URL::asset('/build/img/products/pos-product-17.png') }}"
                                            alt="Products">
                                    </a>
                                    <div class="info d-flex align-items-center justify-content-between flex-fill">
                                        <div>
                                            <span>PT0235</span>
                                            <h6><a href="javascript:void(0);">Iphone 14</a></h6>
                                        </div>
                                        <p>$3000</p>
                                    </div>
                                </div>
                            </div>
                            <div class="product-list d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center flex-fill">
                                    <a href="javascript:void(0);" class="img-bg me-2">
                                        <img src="{{ URL::asset('/build/img/products/pos-product-16.png') }}"
                                            alt="Products">
                                    </a>
                                    <div class="info d-flex align-items-center justify-content-between flex-fill">
                                        <div>
                                            <span>PT0005</span>
                                            <h6><a href="javascript:void(0);">Red Nike Laser</a></h6>
                                        </div>
                                        <p>$2000</p>
                                    </div>
                                </div>
                            </div>
                            <div class="product-list d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center flex-fill">
                                    <a href="javascript:void(0);" class="img-bg me-2">
                                        <img src="{{ URL::asset('/build/img/products/pos-product-17.png') }}"
                                            alt="Products">
                                    </a>
                                    <div class="info d-flex align-items-center justify-content-between flex-fill">
                                        <div>
                                            <span>PT0005</span>
                                            <h6><a href="javascript:void(0);">Red Nike Laser</a></h6>
                                        </div>
                                        <p>$2000</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer d-sm-flex justify-content-end">
                            <button type="button" class="btn btn-secondary"
                                data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- /Products -->

    <div class="modal fade" id="create" tabindex="-1" aria-labelledby="create" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create</h5>
                    <button type="button" class="close ms-auto" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="pos">
                        <div class="row">
                            <div class="col-lg-6 col-sm-12 col-12">
                                <div class="input-blocks">
                                    <label>Customer Name</label>
                                    <input type="text" class="form-control">
                                </div>
                            </div>
                            <div class="col-lg-6 col-sm-12 col-12">
                                <div class="input-blocks">
                                    <label>Email</label>
                                    <input type="email" class="form-control">
                                </div>
                            </div>
                            <div class="col-lg-6 col-sm-12 col-12">
                                <div class="input-blocks">
                                    <label>Phone</label>
                                    <input type="text" class="form-control">
                                </div>
                            </div>
                            <div class="col-lg-6 col-sm-12 col-12">
                                <div class="input-blocks">
                                    <label>Country</label>
                                    <input type="text" class="form-control">
                                </div>
                            </div>
                            <div class="col-lg-6 col-sm-12 col-12">
                                <div class="input-blocks">
                                    <label>City</label>
                                    <input type="text">
                                </div>
                            </div>
                            <div class="col-lg-6 col-sm-12 col-12">
                                <div class="input-blocks">
                                    <label>Address</label>
                                    <input type="text">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer d-sm-flex justify-content-end">
                            <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-submit me-2">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Hold -->
    <div class="modal fade modal-default pos-modal" id="hold-order" aria-labelledby="hold-order">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header p-4">
                    <h5>Hold order</h5>
                    <button type="button" class="close ms-auto" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <form action="pos">
                        <h2 class="text-center p-4">4500.00</h2>
                        <div class="input-block">
                            <label>Order Reference</label>
                            <input class="form-control" type="text" value="" placeholder="">
                        </div>
                        <p>The current order will be set on hold. You can retreive this order from the pending order
                            button. Providing a reference to it might help you to identify the order more quickly.</p>
                        <div class="modal-footer d-sm-flex justify-content-end">
                            <button type="button" class="btn btn-secondary"
                                data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Confirm</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- /Hold -->

    <!-- Edit Product -->
    <div class="modal fade modal-default pos-modal" id="edit-product" aria-labelledby="edit-product">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header p-4">
                    <h5>Red Nike Laser</h5>
                    <button type="button" class="close ms-auto" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <form action="pos">
                        <div class="row">
                            <div class="col-lg-6 col-sm-12 col-12">
                                <div class="input-blocks add-product">
                                    <label>Product Name <span>*</span></label>
                                    <input type="text" placeholder="45">
                                </div>
                            </div>
                            <div class="col-lg-6 col-sm-12 col-12">
                                <div class="input-blocks add-product">
                                    <label>Tax Type <span>*</span></label>
                                    <select class="select">
                                        <option>Exclusive</option>
                                        <option>Inclusive</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-6 col-sm-12 col-12">
                                <div class="input-blocks add-product">
                                    <label>Tax <span>*</span></label>
                                    <input type="text" placeholder="% 15">
                                </div>
                            </div>
                            <div class="col-lg-6 col-sm-12 col-12">
                                <div class="input-blocks add-product">
                                    <label>Discount Type <span>*</span></label>
                                    <select class="select">
                                        <option>Percentage</option>
                                        <option>Early payment discounts</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-6 col-sm-12 col-12">
                                <div class="input-blocks add-product">
                                    <label>Discount <span>*</span></label>
                                    <input type="text" placeholder="15">
                                </div>
                            </div>
                            <div class="col-lg-6 col-sm-12 col-12">
                                <div class="input-blocks add-product">
                                    <label>Sale Unit <span>*</span></label>
                                    <select class="select">
                                        <option>Kilogram</option>
                                        <option>Grams</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer d-sm-flex justify-content-end">
                            <button type="button" class="btn btn-secondary"
                                data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- /Edit Product -->

    <!-- Recent Transactions -->
    <div class="modal fade pos-modal" id="recents" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header p-4">
                    <h5 class="modal-title">Recent Transactions</h5>
                    <button type="button" class="close ms-auto" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <div class="tabs-sets">
                        <ul class="nav nav-tabs" id="myTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="purchase-tab" data-bs-toggle="tab"
                                    data-bs-target="#purchase" type="button" aria-controls="purchase"
                                    aria-selected="true" role="tab">Purchase</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="payment-tab" data-bs-toggle="tab"
                                    data-bs-target="#payment" type="button" aria-controls="payment"
                                    aria-selected="false" role="tab">Payment</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="return-tab" data-bs-toggle="tab"
                                    data-bs-target="#return" type="button" aria-controls="return"
                                    aria-selected="false" role="tab">Return</button>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="purchase" role="tabpanel"
                                aria-labelledby="purchase-tab">
                                <div class="table-top">
                                    <div class="search-set">
                                        <div class="search-input">
                                            <a class="btn btn-searchset d-flex align-items-center h-100"><img
                                                    src="{{ URL::asset('/build/img/icons/search-white.svg') }}"
                                                    alt="img"></a>
                                        </div>
                                    </div>
                                    <div class="wordset">
                                        <ul>
                                            <li>
                                                <a class="d-flex align-items-center justify-content-center"
                                                    data-bs-toggle="tooltip" data-bs-placement="top"
                                                    title="Pdf"><img
                                                        src="{{ URL::asset('/build/img/icons/pdf.svg') }}"
                                                        alt="img"></a>
                                            </li>
                                            <li>
                                                <a class="d-flex align-items-center justify-content-center"
                                                    data-bs-toggle="tooltip" data-bs-placement="top"
                                                    title="Excel"><img
                                                        src="{{ URL::asset('/build/img/icons/excel.svg') }}"
                                                        alt="img"></a>
                                            </li>
                                            <li>
                                                <a class="d-flex align-items-center justify-content-center"
                                                    data-bs-toggle="tooltip" data-bs-placement="top"
                                                    title="Print"><i data-feather="printer"
                                                        class="feather-rotate-ccw"></i></a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table datanew">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Reference</th>
                                                <th>Customer</th>
                                                <th>Amount </th>
                                                <th class="no-sort">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>19 Jan 2023</td>
                                                <td>INV/SL0101</td>
                                                <td>Walk-in Customer</td>
                                                <td>$1500.00</td>
                                                <td class="action-table-data">
                                                    <div class="edit-delete-action">
                                                        <a class="me-2 p-2" href="javascript:void(0);"><i
                                                                data-feather="eye" class="feather-eye"></i></a>
                                                        <a class="me-2 p-2" href="javascript:void(0);"><i
                                                                data-feather="edit" class="feather-edit"></i></a>
                                                        <a class="p-2 confirm-text" href="javascript:void(0);"><i
                                                                data-feather="trash-2"
                                                                class="feather-trash-2"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>19 Jan 2023</td>
                                                <td>INV/SL0102</td>
                                                <td>Walk-in Customer</td>
                                                <td>$1500.00</td>
                                                <td class="action-table-data">
                                                    <div class="edit-delete-action">
                                                        <a class="me-2 p-2" href="javascript:void(0);"><i
                                                                data-feather="eye" class="feather-eye"></i></a>
                                                        <a class="me-2 p-2" href="javascript:void(0);"><i
                                                                data-feather="edit" class="feather-edit"></i></a>
                                                        <a class="p-2 confirm-text" href="javascript:void(0);"><i
                                                                data-feather="trash-2"
                                                                class="feather-trash-2"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>19 Jan 2023</td>
                                                <td>INV/SL0103</td>
                                                <td>Walk-in Customer</td>
                                                <td>$1500.00</td>
                                                <td class="action-table-data">
                                                    <div class="edit-delete-action">
                                                        <a class="me-2 p-2" href="javascript:void(0);"><i
                                                                data-feather="eye" class="feather-eye"></i></a>
                                                        <a class="me-2 p-2" href="javascript:void(0);"><i
                                                                data-feather="edit" class="feather-edit"></i></a>
                                                        <a class="p-2 confirm-text" href="javascript:void(0);"><i
                                                                data-feather="trash-2"
                                                                class="feather-trash-2"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>19 Jan 2023</td>
                                                <td>INV/SL0104</td>
                                                <td>Walk-in Customer</td>
                                                <td>$1500.00</td>
                                                <td class="action-table-data">
                                                    <div class="edit-delete-action">
                                                        <a class="me-2 p-2" href="javascript:void(0);"><i
                                                                data-feather="eye" class="feather-eye"></i></a>
                                                        <a class="me-2 p-2" href="javascript:void(0);"><i
                                                                data-feather="edit" class="feather-edit"></i></a>
                                                        <a class="p-2 confirm-text" href="javascript:void(0);"><i
                                                                data-feather="trash-2"
                                                                class="feather-trash-2"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>19 Jan 2023</td>
                                                <td>INV/SL0105</td>
                                                <td>Walk-in Customer</td>
                                                <td>$1500.00</td>
                                                <td class="action-table-data">
                                                    <div class="edit-delete-action">
                                                        <a class="me-2 p-2" href="javascript:void(0);"><i
                                                                data-feather="eye" class="feather-eye"></i></a>
                                                        <a class="me-2 p-2" href="javascript:void(0);"><i
                                                                data-feather="edit" class="feather-edit"></i></a>
                                                        <a class="p-2 confirm-text" href="javascript:void(0);"><i
                                                                data-feather="trash-2"
                                                                class="feather-trash-2"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>19 Jan 2023</td>
                                                <td>INV/SL0106</td>
                                                <td>Walk-in Customer</td>
                                                <td>$1500.00</td>
                                                <td class="action-table-data">
                                                    <div class="edit-delete-action">
                                                        <a class="me-2 p-2" href="javascript:void(0);"><i
                                                                data-feather="eye" class="feather-eye"></i></a>
                                                        <a class="me-2 p-2" href="javascript:void(0);"><i
                                                                data-feather="edit" class="feather-edit"></i></a>
                                                        <a class="p-2 confirm-text" href="javascript:void(0);"><i
                                                                data-feather="trash-2"
                                                                class="feather-trash-2"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>19 Jan 2023</td>
                                                <td>INV/SL0107</td>
                                                <td>Walk-in Customer</td>
                                                <td>$1500.00</td>
                                                <td class="action-table-data">
                                                    <div class="edit-delete-action">
                                                        <a class="me-2 p-2" href="javascript:void(0);"><i
                                                                data-feather="eye" class="feather-eye"></i></a>
                                                        <a class="me-2 p-2" href="javascript:void(0);"><i
                                                                data-feather="edit" class="feather-edit"></i></a>
                                                        <a class="p-2 confirm-text" href="javascript:void(0);"><i
                                                                data-feather="trash-2"
                                                                class="feather-trash-2"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="payment" role="tabpanel">
                                <div class="table-top">
                                    <div class="search-set">
                                        <div class="search-input">
                                            <a class="btn btn-searchset d-flex align-items-center h-100"><img
                                                    src="{{ URL::asset('/build/img/icons/search-white.svg') }}"
                                                    alt="img"></a>
                                        </div>
                                    </div>
                                    <div class="wordset">
                                        <ul>
                                            <li>
                                                <a class="d-flex align-items-center justify-content-center"
                                                    data-bs-toggle="tooltip" data-bs-placement="top"
                                                    title="Pdf"><img
                                                        src="{{ URL::asset('/build/img/icons/pdf.svg') }}"
                                                        alt="img"></a>
                                            </li>
                                            <li>
                                                <a class="d-flex align-items-center justify-content-center"
                                                    data-bs-toggle="tooltip" data-bs-placement="top"
                                                    title="Excel"><img
                                                        src="{{ URL::asset('/build/img/icons/excel.svg') }}"
                                                        alt="img"></a>
                                            </li>
                                            <li>
                                                <a class="d-flex align-items-center justify-content-center"
                                                    data-bs-toggle="tooltip" data-bs-placement="top"
                                                    title="Print"><i data-feather="printer"
                                                        class="feather-rotate-ccw"></i></a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table datanew">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Reference</th>
                                                <th>Customer</th>
                                                <th>Amount </th>
                                                <th class="no-sort">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>19 Jan 2023</td>
                                                <td>INV/SL0101</td>
                                                <td>Walk-in Customer</td>
                                                <td>$1500.00</td>
                                                <td class="action-table-data">
                                                    <div class="edit-delete-action">
                                                        <a class="me-2 p-2" href="javascript:void(0);"><i
                                                                data-feather="eye" class="feather-eye"></i></a>
                                                        <a class="me-2 p-2" href="javascript:void(0);"><i
                                                                data-feather="edit" class="feather-edit"></i></a>
                                                        <a class="p-2 confirm-text" href="javascript:void(0);"><i
                                                                data-feather="trash-2"
                                                                class="feather-trash-2"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>19 Jan 2023</td>
                                                <td>INV/SL0102</td>
                                                <td>Walk-in Customer</td>
                                                <td>$1500.00</td>
                                                <td class="action-table-data">
                                                    <div class="edit-delete-action">
                                                        <a class="me-2 p-2" href="javascript:void(0);"><i
                                                                data-feather="eye" class="feather-eye"></i></a>
                                                        <a class="me-2 p-2" href="javascript:void(0);"><i
                                                                data-feather="edit" class="feather-edit"></i></a>
                                                        <a class="p-2 confirm-text" href="javascript:void(0);"><i
                                                                data-feather="trash-2"
                                                                class="feather-trash-2"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>19 Jan 2023</td>
                                                <td>INV/SL0103</td>
                                                <td>Walk-in Customer</td>
                                                <td>$1500.00</td>
                                                <td class="action-table-data">
                                                    <div class="edit-delete-action">
                                                        <a class="me-2 p-2" href="javascript:void(0);"><i
                                                                data-feather="eye" class="feather-eye"></i></a>
                                                        <a class="me-2 p-2" href="javascript:void(0);"><i
                                                                data-feather="edit" class="feather-edit"></i></a>
                                                        <a class="p-2 confirm-text" href="javascript:void(0);"><i
                                                                data-feather="trash-2"
                                                                class="feather-trash-2"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>19 Jan 2023</td>
                                                <td>INV/SL0104</td>
                                                <td>Walk-in Customer</td>
                                                <td>$1500.00</td>
                                                <td class="action-table-data">
                                                    <div class="edit-delete-action">
                                                        <a class="me-2 p-2" href="javascript:void(0);"><i
                                                                data-feather="eye" class="feather-eye"></i></a>
                                                        <a class="me-2 p-2" href="javascript:void(0);"><i
                                                                data-feather="edit" class="feather-edit"></i></a>
                                                        <a class="p-2 confirm-text" href="javascript:void(0);"><i
                                                                data-feather="trash-2"
                                                                class="feather-trash-2"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>19 Jan 2023</td>
                                                <td>INV/SL0105</td>
                                                <td>Walk-in Customer</td>
                                                <td>$1500.00</td>
                                                <td class="action-table-data">
                                                    <div class="edit-delete-action">
                                                        <a class="me-2 p-2" href="javascript:void(0);"><i
                                                                data-feather="eye" class="feather-eye"></i></a>
                                                        <a class="me-2 p-2" href="javascript:void(0);"><i
                                                                data-feather="edit" class="feather-edit"></i></a>
                                                        <a class="p-2 confirm-text" href="javascript:void(0);"><i
                                                                data-feather="trash-2"
                                                                class="feather-trash-2"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>19 Jan 2023</td>
                                                <td>INV/SL0106</td>
                                                <td>Walk-in Customer</td>
                                                <td>$1500.00</td>
                                                <td class="action-table-data">
                                                    <div class="edit-delete-action">
                                                        <a class="me-2 p-2" href="javascript:void(0);"><i
                                                                data-feather="eye" class="feather-eye"></i></a>
                                                        <a class="me-2 p-2" href="javascript:void(0);"><i
                                                                data-feather="edit" class="feather-edit"></i></a>
                                                        <a class="p-2 confirm-text" href="javascript:void(0);"><i
                                                                data-feather="trash-2"
                                                                class="feather-trash-2"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>19 Jan 2023</td>
                                                <td>INV/SL0107</td>
                                                <td>Walk-in Customer</td>
                                                <td>$1500.00</td>
                                                <td class="action-table-data">
                                                    <div class="edit-delete-action">
                                                        <a class="me-2 p-2" href="javascript:void(0);"><i
                                                                data-feather="eye" class="feather-eye"></i></a>
                                                        <a class="me-2 p-2" href="javascript:void(0);"><i
                                                                data-feather="edit" class="feather-edit"></i></a>
                                                        <a class="p-2 confirm-text" href="javascript:void(0);"><i
                                                                data-feather="trash-2"
                                                                class="feather-trash-2"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="return" role="tabpanel">
                                <div class="table-top">
                                    <div class="search-set">
                                        <div class="search-input">
                                            <a class="btn btn-searchset d-flex align-items-center h-100"><img
                                                    src="{{ URL::asset('/build/img/icons/search-white.svg') }}"
                                                    alt="img"></a>
                                        </div>
                                    </div>
                                    <div class="wordset">
                                        <ul>
                                            <li>
                                                <a data-bs-toggle="tooltip" data-bs-placement="top" title="Pdf"
                                                    class="d-flex align-items-center justify-content-center"><img
                                                        src="{{ URL::asset('/build/img/icons/pdf.svg') }}"
                                                        alt="img"></a>
                                            </li>
                                            <li>
                                                <a data-bs-toggle="tooltip" data-bs-placement="top" title="Excel"
                                                    class="d-flex align-items-center justify-content-center"><img
                                                        src="{{ URL::asset('/build/img/icons/excel.svg') }}"
                                                        alt="img"></a>
                                            </li>
                                            <li>
                                                <a data-bs-toggle="tooltip" data-bs-placement="top" title="Print"
                                                    class="d-flex align-items-center justify-content-center"><i
                                                        data-feather="printer" class="feather-rotate-ccw"></i></a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table datanew">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Reference</th>
                                                <th>Customer</th>
                                                <th>Amount </th>
                                                <th class="no-sort">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>19 Jan 2023</td>
                                                <td>INV/SL0101</td>
                                                <td>Walk-in Customer</td>
                                                <td>$1500.00</td>
                                                <td class="action-table-data">
                                                    <div class="edit-delete-action">
                                                        <a class="me-2 p-2" href="javascript:void(0);"><i
                                                                data-feather="eye" class="feather-eye"></i></a>
                                                        <a class="me-2 p-2" href="javascript:void(0);"><i
                                                                data-feather="edit" class="feather-edit"></i></a>
                                                        <a class="p-2 confirm-text" href="javascript:void(0);"><i
                                                                data-feather="trash-2"
                                                                class="feather-trash-2"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>19 Jan 2023</td>
                                                <td>INV/SL0102</td>
                                                <td>Walk-in Customer</td>
                                                <td>$1500.00</td>
                                                <td class="action-table-data">
                                                    <div class="edit-delete-action">
                                                        <a class="me-2 p-2" href="javascript:void(0);"><i
                                                                data-feather="eye" class="feather-eye"></i></a>
                                                        <a class="me-2 p-2" href="javascript:void(0);"><i
                                                                data-feather="edit" class="feather-edit"></i></a>
                                                        <a class="p-2 confirm-text" href="javascript:void(0);"><i
                                                                data-feather="trash-2"
                                                                class="feather-trash-2"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>19 Jan 2023</td>
                                                <td>INV/SL0103</td>
                                                <td>Walk-in Customer</td>
                                                <td>$1500.00</td>
                                                <td class="action-table-data">
                                                    <div class="edit-delete-action">
                                                        <a class="me-2 p-2" href="javascript:void(0);"><i
                                                                data-feather="eye" class="feather-eye"></i></a>
                                                        <a class="me-2 p-2" href="javascript:void(0);"><i
                                                                data-feather="edit" class="feather-edit"></i></a>
                                                        <a class="p-2 confirm-text" href="javascript:void(0);"><i
                                                                data-feather="trash-2"
                                                                class="feather-trash-2"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>19 Jan 2023</td>
                                                <td>INV/SL0104</td>
                                                <td>Walk-in Customer</td>
                                                <td>$1500.00</td>
                                                <td class="action-table-data">
                                                    <div class="edit-delete-action">
                                                        <a class="me-2 p-2" href="javascript:void(0);"><i
                                                                data-feather="eye" class="feather-eye"></i></a>
                                                        <a class="me-2 p-2" href="javascript:void(0);"><i
                                                                data-feather="edit" class="feather-edit"></i></a>
                                                        <a class="p-2 confirm-text" href="javascript:void(0);"><i
                                                                data-feather="trash-2"
                                                                class="feather-trash-2"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>19 Jan 2023</td>
                                                <td>INV/SL0105</td>
                                                <td>Walk-in Customer</td>
                                                <td>$1500.00</td>
                                                <td class="action-table-data">
                                                    <div class="edit-delete-action">
                                                        <a class="me-2 p-2" href="javascript:void(0);"><i
                                                                data-feather="eye" class="feather-eye"></i></a>
                                                        <a class="me-2 p-2" href="javascript:void(0);"><i
                                                                data-feather="edit" class="feather-edit"></i></a>
                                                        <a class="p-2 confirm-text" href="javascript:void(0);"><i
                                                                data-feather="trash-2"
                                                                class="feather-trash-2"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>19 Jan 2023</td>
                                                <td>INV/SL0106</td>
                                                <td>Walk-in Customer</td>
                                                <td>$1500.00</td>
                                                <td class="action-table-data">
                                                    <div class="edit-delete-action">
                                                        <a class="me-2 p-2" href="javascript:void(0);"><i
                                                                data-feather="eye" class="feather-eye"></i></a>
                                                        <a class="me-2 p-2" href="javascript:void(0);"><i
                                                                data-feather="edit" class="feather-edit"></i></a>
                                                        <a class="p-2 confirm-text" href="javascript:void(0);"><i
                                                                data-feather="trash-2"
                                                                class="feather-trash-2"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>19 Jan 2023</td>
                                                <td>INV/SL0107</td>
                                                <td>Walk-in Customer</td>
                                                <td>$1500.00</td>
                                                <td class="action-table-data">
                                                    <div class="edit-delete-action">
                                                        <a class="me-2 p-2" href="javascript:void(0);"><i
                                                                data-feather="eye" class="feather-eye"></i></a>
                                                        <a class="me-2 p-2" href="javascript:void(0);"><i
                                                                data-feather="edit" class="feather-edit"></i></a>
                                                        <a class="p-2 confirm-text" href="javascript:void(0);"><i
                                                                data-feather="trash-2"
                                                                class="feather-trash-2"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Recent Transactions -->

    <!-- Recent Transactions -->
    <div class="modal fade pos-modal" id="orders" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header p-4">
                    <h5 class="modal-title">Orders</h5>
                    <button type="button" class="close ms-auto" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <div class="tabs-sets">
                        <ul class="nav nav-tabs" id="myTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="onhold-tab" data-bs-toggle="tab"
                                    data-bs-target="#onhold" type="button" aria-controls="onhold"
                                    aria-selected="true" role="tab">Onhold</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="unpaid-tab" data-bs-toggle="tab"
                                    data-bs-target="#unpaid" type="button" aria-controls="unpaid"
                                    aria-selected="false" role="tab">Unpaid</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="paid-tab" data-bs-toggle="tab"
                                    data-bs-target="#paid" type="button" aria-controls="paid"
                                    aria-selected="false" role="tab">Paid</button>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="onhold" role="tabpanel"
                                aria-labelledby="onhold-tab">
                                <div class="table-top">
                                    <div class="search-set w-100 search-order">
                                        <div class="search-input w-100">
                                            <a class="btn btn-searchset d-flex align-items-center h-100"><img
                                                    src="{{ URL::asset('/build/img/icons/search-white.svg') }}"
                                                    alt="img"></a>
                                        </div>
                                    </div>
                                </div>
                                <div class="order-body">
                                    <div class="default-cover p-4 mb-4">
                                        <span class="badge bg-secondary d-inline-block mb-4">Order ID : #666659</span>
                                        <div class="row">
                                            <div class="col-sm-12 col-md-6 record mb-3">
                                                <table>
                                                    <tr class="mb-3">
                                                        <td>Cashier</td>
                                                        <td class="colon">:</td>
                                                        <td class="text">admin</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Customer</td>
                                                        <td class="colon">:</td>
                                                        <td class="text">Botsford</td>
                                                    </tr>
                                                </table>
                                            </div>
                                            <div class="col-sm-12 col-md-6 record mb-3">
                                                <table>
                                                    <tr>
                                                        <td>Total</td>
                                                        <td class="colon">:</td>
                                                        <td class="text">$900</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Date</td>
                                                        <td class="colon">:</td>
                                                        <td class="text">29-08-2023 13:39:11</td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                        <p class="p-4">Customer need to recheck the product once</p>
                                        <div class="btn-row d-sm-flex align-items-center justify-content-between">
                                            <a href="javascript:void(0);"
                                                class="btn btn-info btn-icon flex-fill">Open</a>
                                            <a href="javascript:void(0);"
                                                class="btn btn-danger btn-icon flex-fill">Products</a>
                                            <a href="javascript:void(0);"
                                                class="btn btn-success btn-icon flex-fill">Print</a>
                                        </div>
                                    </div>
                                    <div class="default-cover p-4 mb-4">
                                        <span class="badge bg-secondary d-inline-block mb-4">Order ID : #666660</span>
                                        <div class="row">
                                            <div class="col-sm-12 col-md-6 record mb-3">
                                                <table>
                                                    <tr class="mb-3">
                                                        <td>Cashier</td>
                                                        <td class="colon">:</td>
                                                        <td class="text">admin</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Customer</td>
                                                        <td class="colon">:</td>
                                                        <td class="text">Smith</td>
                                                    </tr>
                                                </table>
                                            </div>
                                            <div class="col-sm-12 col-md-6 record mb-3">
                                                <table>
                                                    <tr>
                                                        <td>Total</td>
                                                        <td class="colon">:</td>
                                                        <td class="text">$15000</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Date</td>
                                                        <td class="colon">:</td>
                                                        <td class="text">30-08-2023 15:59:11</td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                        <p class="p-4">Customer need to recheck the product once</p>
                                        <div class="btn-row d-flex align-items-center justify-content-between">
                                            <a href="javascript:void(0);"
                                                class="btn btn-info btn-icon flex-fill">Open</a>
                                            <a href="javascript:void(0);"
                                                class="btn btn-danger btn-icon flex-fill">Products</a>
                                            <a href="javascript:void(0);"
                                                class="btn btn-success btn-icon flex-fill">Print</a>
                                        </div>
                                    </div>
                                    <div class="default-cover p-4">
                                        <span class="badge bg-secondary d-inline-block mb-4">Order ID : #666661</span>
                                        <div class="row">
                                            <div class="col-sm-12 col-md-6 record mb-3">
                                                <table>
                                                    <tr class="mb-3">
                                                        <td>Cashier</td>
                                                        <td class="colon">:</td>
                                                        <td class="text">admin</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Customer</td>
                                                        <td class="colon">:</td>
                                                        <td class="text">John David</td>
                                                    </tr>
                                                </table>
                                            </div>
                                            <div class="col-sm-12 col-md-6 record mb-3">
                                                <table>
                                                    <tr>
                                                        <td>Total</td>
                                                        <td class="colon">:</td>
                                                        <td class="text">$2000</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Date</td>
                                                        <td class="colon">:</td>
                                                        <td class="text">01-09-2023 13:15:00</td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                        <p class="p-4 mb-4">Customer need to recheck the product once</p>
                                        <div class="btn-row d-flex align-items-center justify-content-between">
                                            <a href="javascript:void(0);"
                                                class="btn btn-info btn-icon flex-fill">Open</a>
                                            <a href="javascript:void(0);"
                                                class="btn btn-danger btn-icon flex-fill">Products</a>
                                            <a href="javascript:void(0);"
                                                class="btn btn-success btn-icon flex-fill">Print</a>
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <div class="tab-pane fade" id="unpaid" role="tabpanel">
                                <div class="table-top">
                                    <div class="search-set w-100 search-order">
                                        <div class="search-input">
                                            <a class="btn btn-searchset d-flex align-items-center h-100"><img
                                                    src="{{ URL::asset('/build/img/icons/search-white.svg') }}"
                                                    alt="img"></a>
                                        </div>
                                    </div>

                                </div>
                                <div class="order-body">
                                    <div class="default-cover p-4 mb-4">
                                        <span class="badge bg-info d-inline-block mb-4">Order ID : #666662</span>
                                        <div class="row">
                                            <div class="col-sm-12 col-md-6 record mb-3">
                                                <table>
                                                    <tr class="mb-3">
                                                        <td>Cashier</td>
                                                        <td class="colon">:</td>
                                                        <td class="text">admin</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Customer</td>
                                                        <td class="colon">:</td>
                                                        <td class="text">Anastasia</td>
                                                    </tr>
                                                </table>
                                            </div>
                                            <div class="col-sm-12 col-md-6 record mb-3">
                                                <table>
                                                    <tr>
                                                        <td>Total</td>
                                                        <td class="colon">:</td>
                                                        <td class="text">$2500</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Date</td>
                                                        <td class="colon">:</td>
                                                        <td class="text">10-09-2023 17:15:11</td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                        <p class="p-4">Customer need to recheck the product once</p>
                                        <div class="btn-row d-flex align-items-center justify-content-between">
                                            <a href="javascript:void(0);"
                                                class="btn btn-info btn-icon flex-fill">Open</a>
                                            <a href="javascript:void(0);"
                                                class="btn btn-danger btn-icon flex-fill">Products</a>
                                            <a href="javascript:void(0);"
                                                class="btn btn-success btn-icon flex-fill">Print</a>
                                        </div>
                                    </div>
                                    <div class="default-cover p-4 mb-4">
                                        <span class="badge bg-info d-inline-block mb-4">Order ID : #666663</span>
                                        <div class="row">
                                            <div class="col-sm-12 col-md-6 record mb-3">
                                                <table>
                                                    <tr class="mb-3">
                                                        <td>Cashier</td>
                                                        <td class="colon">:</td>
                                                        <td class="text">admin</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Customer</td>
                                                        <td class="colon">:</td>
                                                        <td class="text">Lucia</td>
                                                    </tr>
                                                </table>
                                            </div>
                                            <div class="col-sm-12 col-md-6 record mb-3">
                                                <table>
                                                    <tr>
                                                        <td>Total</td>
                                                        <td class="colon">:</td>
                                                        <td class="text">$1500</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Date</td>
                                                        <td class="colon">:</td>
                                                        <td class="text">11-09-2023 14:50:11</td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                        <p class="p-4">Customer need to recheck the product once</p>
                                        <div class="btn-row d-flex align-items-center justify-content-between">
                                            <a href="javascript:void(0);"
                                                class="btn btn-info btn-icon flex-fill">Open</a>
                                            <a href="javascript:void(0);"
                                                class="btn btn-danger btn-icon flex-fill">Products</a>
                                            <a href="javascript:void(0);"
                                                class="btn btn-success btn-icon flex-fill">Print</a>
                                        </div>
                                    </div>
                                    <div class="default-cover p-4 mb-4">
                                        <span class="badge bg-info d-inline-block mb-4">Order ID : #666664</span>
                                        <div class="row">
                                            <div class="col-sm-12 col-md-6 record mb-3">
                                                <table>
                                                    <tr class="mb-3">
                                                        <td>Cashier</td>
                                                        <td class="colon">:</td>
                                                        <td class="text">admin</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Customer</td>
                                                        <td class="colon">:</td>
                                                        <td class="text">Diego</td>
                                                    </tr>
                                                </table>
                                            </div>
                                            <div class="col-sm-12 col-md-6 record mb-3">
                                                <table>
                                                    <tr>
                                                        <td>Total</td>
                                                        <td class="colon">:</td>
                                                        <td class="text">$30000</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Date</td>
                                                        <td class="colon">:</td>
                                                        <td class="text">12-09-2023 17:22:11</td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                        <p class="p-4 mb-4">Customer need to recheck the product once</p>
                                        <div class="btn-row d-flex align-items-center justify-content-between">
                                            <a href="javascript:void(0);"
                                                class="btn btn-info btn-icon flex-fill">Open</a>
                                            <a href="javascript:void(0);"
                                                class="btn btn-danger btn-icon flex-fill">Products</a>
                                            <a href="javascript:void(0);"
                                                class="btn btn-success btn-icon flex-fill">Print</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="paid" role="tabpanel">
                                <div class="table-top">
                                    <div class="search-set w-100 search-order">
                                        <div class="search-input">
                                            <a class="btn btn-searchset d-flex align-items-center h-100"><img
                                                    src="{{ URL::asset('/build/img/icons/search-white.svg') }}"
                                                    alt="img"></a>
                                        </div>
                                    </div>
                                </div>
                                <div class="order-body">
                                    <div class="default-cover p-4 mb-4">
                                        <span class="badge bg-primary d-inline-block mb-4">Order ID : #666665</span>
                                        <div class="row">
                                            <div class="col-sm-12 col-md-6 record mb-3">
                                                <table>
                                                    <tr class="mb-3">
                                                        <td>Cashier</td>
                                                        <td class="colon">:</td>
                                                        <td class="text">admin</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Customer</td>
                                                        <td class="colon">:</td>
                                                        <td class="text">Hugo</td>
                                                    </tr>
                                                </table>
                                            </div>
                                            <div class="col-sm-12 col-md-6 record mb-3">
                                                <table>
                                                    <tr>
                                                        <td>Total</td>
                                                        <td class="colon">:</td>
                                                        <td class="text">$5000</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Date</td>
                                                        <td class="colon">:</td>
                                                        <td class="text">13-09-2023 19:39:11</td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                        <p class="p-4">Customer need to recheck the product once</p>
                                        <div class="btn-row d-flex align-items-center justify-content-between">
                                            <a href="javascript:void(0);"
                                                class="btn btn-info btn-icon flex-fill">Open</a>
                                            <a href="javascript:void(0);"
                                                class="btn btn-danger btn-icon flex-fill">Products</a>
                                            <a href="javascript:void(0);"
                                                class="btn btn-success btn-icon flex-fill">Print</a>
                                        </div>
                                    </div>
                                    <div class="default-cover p-4 mb-4">
                                        <span class="badge bg-primary d-inline-block mb-4">Order ID : #666666</span>
                                        <div class="row">
                                            <div class="col-sm-12 col-md-6 record mb-3">
                                                <table>
                                                    <tr class="mb-3">
                                                        <td>Cashier</td>
                                                        <td class="colon">:</td>
                                                        <td class="text">admin</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Customer</td>
                                                        <td class="colon">:</td>
                                                        <td class="text">Antonio</td>
                                                    </tr>
                                                </table>
                                            </div>
                                            <div class="col-sm-12 col-md-6 record mb-3">
                                                <table>
                                                    <tr>
                                                        <td>Total</td>
                                                        <td class="colon">:</td>
                                                        <td class="text">$7000</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Date</td>
                                                        <td class="colon">:</td>
                                                        <td class="text">15-09-2023 18:39:11</td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                        <p class="p-4">Customer need to recheck the product once</p>
                                        <div class="btn-row d-flex align-items-center justify-content-between">
                                            <a href="javascript:void(0);"
                                                class="btn btn-info btn-icon flex-fill">Open</a>
                                            <a href="javascript:void(0);"
                                                class="btn btn-danger btn-icon flex-fill">Products</a>
                                            <a href="javascript:void(0);"
                                                class="btn btn-success btn-icon flex-fill">Print</a>
                                        </div>
                                    </div>
                                    <div class="default-cover p-4 mb-4">
                                        <span class="badge bg-primary d-inline-block mb-4">Order ID : #666667</span>
                                        <div class="row">
                                            <div class="col-sm-12 col-md-6 record mb-3">
                                                <table>
                                                    <tr class="mb-3">
                                                        <td>Cashier</td>
                                                        <td class="colon">:</td>
                                                        <td class="text">admin</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Customer</td>
                                                        <td class="colon">:</td>
                                                        <td class="text">MacQuoid</td>
                                                    </tr>
                                                </table>
                                            </div>
                                            <div class="col-sm-12 col-md-6 record mb-3">
                                                <table>
                                                    <tr>
                                                        <td>Total</td>
                                                        <td class="colon">:</td>
                                                        <td class="text">$7050</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Date</td>
                                                        <td class="colon">:</td>
                                                        <td class="text">17-09-2023 19:39:11</td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                        <p class="p-4 mb-4">Customer need to recheck the product once</p>
                                        <div class="btn-row d-flex align-items-center justify-content-between">
                                            <a href="javascript:void(0);"
                                                class="btn btn-info btn-icon flex-fill">Open</a>
                                            <a href="javascript:void(0);"
                                                class="btn btn-danger btn-icon flex-fill">Products</a>
                                            <a href="javascript:void(0);"
                                                class="btn btn-success btn-icon flex-fill">Print</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Recent Transactions -->
@endif
@if (Route::is(['POSSession', 'POSSessionDetail', 'POSTransaction']))
    <!-- Edit Sesi POS -->
    <div class="modal fade" id="edit-session">
        <div class="modal-dialog modal-dialog-centered custom-modal-two">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>Edit Sesi POS</h4>
                            </div>
                            <button type="button" class="close ms-auto" data-bs-dismiss="modal"
                                aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="input-blocks">
                                        <label>Cash Drawer Awal</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" class="form-control nominal_only"
                                                id="start_cash_drawer">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="input-blocks">
                                        <label>Cash Drawer Akhir</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" class="form-control nominal_only"
                                                id="end_cash_drawer">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="input-blocks">
                                        <label>Status</label>
                                        <select class="select">
                                            <option value=0>Aktif</option>
                                            <option value=1 selected>Selesai</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer-btn">
                                    <button type="button" class="btn btn-cancel me-2"
                                        data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-submit"
                                        id="btn_update_session">Simpan</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Edit Sesi POS -->

    <!-- Add Cash Record -->
    <div class="modal fade" id="add-expense">
        <div class="modal-dialog modal-dialog-centered custom-modal-two">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>Tambah Aktifitas Cash Drawer</h4>
                            </div>
                            <button type="button" class="close ms-auto" data-bs-dismiss="modal"
                                aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <form>
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="input-blocks">
                                            <label>Kategori</label>
                                            <select class="select" id="activity_category">
                                                <option selected>Pemasukan</option>
                                                <option>Pengeluaran</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="input-blocks">
                                            <label>Deskripsi</label>
                                            <input type="text" class="form-control" id="deskripsi"
                                                placeholder="Masukkan deskripsi...">
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="input-blocks">
                                            <label>Jumlah</label>
                                            <div class="input-group">
                                                <input type="text" id="nominal"
                                                    class="form-control nominal_only" placeholder="Rp.xxx.xxx">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer-btn">
                                        <button type="button" class="btn btn-cancel me-2"
                                            data-bs-dismiss="modal">Batal</button>
                                        <div class="btn btn-submit" id="submit-btn-activity">Simpan</div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Add Cash Record -->
    <!--add popup -->
    <div class="modal fade" id="view-transaction">
        <div class="modal-dialog add-centered">
            <div class="modal-content">
                <div class="page-wrapper p-0 m-0">
                    <div class="content p-0">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4 id="transaction-title"> TRX-001</h4>
                            </div>
                            <button type="button" class="close ms-auto" data-bs-dismiss="modal"
                                aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="card">
                            <div class="card-body">
                                <form action="sales-list">
                                    <div class="row">
                                        <div class="col-lg-4 col-sm-6 col-12">
                                            <div class="input-blocks">
                                                <div class="row">
                                                    <div class="col-lg-10 col-sm-10 col-10">
                                                        <label class="form-label">Nama Customer</label>
                                                        <input type="text" class="form-control"
                                                            id="customer_id" readonly value="Walk in Customer">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-sm-6 col-12">
                                            <div class="input-blocks">
                                                <label>Tanggal dan Waktu Transaksi</label>
                                                <div class="input-groupicon calender-input">
                                                    <i data-feather="calendar" class="info-img"></i>
                                                    <input type="text" class="datetimepicker"
                                                        id="tanggal_waktu" placeholder="Pilih">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-sm-6 col-12">
                                            <div class="input-blocks">
                                                <label>Metode Pembayaran</label>
                                                {{-- <select class="select" id="metode_pembayaran" readonly>
                                                    <option value=1>Cash</option>
                                                    <option value=2>Card</option>
                                                    <option value=3>QRIS</option>
                                                </select> --}}
                                                <input type="text" class="form-control" id="metode_pembayaran"
                                                    readonly>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="table-responsive no-pagination">
                                        <table class="table datanew" id="items-table">
                                            <thead>
                                                <tr>
                                                    <th>Nama Produk</th>
                                                    <th>Varian</th>
                                                    <th>SKU</th>
                                                    <th>Qty</th>
                                                    <th>Harga Satuan</th>
                                                    <th>Subtotal</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="row">
                                        <div class="col-lg-6 ms-auto">
                                            <div class="total-order w-100 max-widthauto m-auto mb-4">
                                                <ul>
                                                    <li>
                                                        <h4>Potongan</h4>
                                                        <h5 id="potongan">Rp 0.00</h5>
                                                    </li>
                                                    <li>
                                                        <h4>Total</h4>
                                                        <h5 id="grand-total">Rp 0.00</h5>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <style>
                                        #update-transaction.disabled,
                                        #update-transaction[disabled] {
                                            opacity: 0.6;
                                            pointer-events: none;
                                            cursor: not-allowed;
                                        }
                                    </style>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /add popup -->
@endif
@if (Route::is(['editProduct', 'addProduct']))
    <!-- Add Grosir -->
    <div class="modal fade" id="grosir">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>Kelola Harga Grosir</h4>
                            </div>
                            <button type="button" class="close ms-auto" data-bs-dismiss="modal"
                                aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <form id="grosir-form">
                                <div class="row">
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label class="form-label">Minimal Pembelian</label>
                                            <input type="number" class="form-control fill" id="min_pembelian"
                                                min="1" placeholder="0">
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label class="form-label">Maksimal Pembelian</label>
                                            <input type="number" class="form-control fill" id="max_pembelian"
                                                min="1" placeholder="0">
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label class="form-label">Harga Grosir</label>
                                            <div class="input-group">
                                                <span class="input-group-text">Rp</span>
                                                <input type="text" class="form-control fill number-only"
                                                    id="harga_grosir" placeholder="0">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end mb-3">
                                    <button type="button" class="btn btn-submit btnCustom"
                                        id="tambah-harga-grosir">Tambahkan Harga Grosir</button>
                                </div>
                            </form>

                            <!-- Table -->
                            <table class="table mt-3">
                                <thead>
                                    <tr>
                                        <th>Minimal Pembelian</th>
                                        <th>Maksimal Pembelian</th>
                                        <th>Harga Grosir</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="grosir-table-body">
                                </tbody>
                            </table>

                            <div class="modal-footer-btn">
                                <button type="button" class="btn btn-submit" id="grosir-simpan">Simpan</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Add Grosir -->
    <!-- Edit Variant -->
    <div class="modal fade" id="edit-variation">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>Edit Varian</h4>
                            </div>
                            <button type="button" class="close ms-auto" data-bs-dismiss="modal"
                                aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <div class="row">
                                <div class="col-lg-4">
                                    <div class="profile-pic-upload mb-3">
                                        <div class="profile-pic brand-pic">
                                            <span id="add_image_text"><i data-feather="image"
                                                    class="plus-down-add"></i> Tambah
                                                Gambar</span>
                                            <img id="product_image_preview" class="p-1" src="#"
                                                alt="Preview"
                                                style="display:none; max-width: 100%; max-height: 150px; object-fit: cover;border-radius:15px" />

                                        </div>
                                        <div class="image-upload mb-0 btn btn-primary"
                                            style="justify-content: center; display: flex; align-items: center; width: 7rem; height: 3rem;">
                                            <input type="file" class="product_image" name="product_image"
                                                accept="image/*">
                                            <div class="image-uploads" style="margin-top: -0.4rem">
                                                <h4 style="color: white">Ubah Gambar
                                                </h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <div class="custom-filed ">
                                        <div class="input-block add-lists" style="display: flex; flex-wrap: wrap;">
                                            <label class="me-4">
                                                <input type="checkbox" id="checkbox-garansi"
                                                    style="border: 1px solid #adb5bd;">
                                                <span class="checkmarks"></span>Garansi
                                            </label>
                                            <label class="me-4">
                                                <input type="checkbox" id="checkbox-peringatan"
                                                    style="border: 1px solid #adb5bd;">
                                                <span class="checkmarks"></span>Peringatan
                                            </label>
                                        </div>
                                    </div>
                                    <div class="row mt-2" id="field-garansi-peringatan" style="display: none;">
                                        <div class="col-6" id="field-garansi" style="display: none;">
                                            <div class="input-blocks add-product">
                                                <label>Garansi</label>
                                                <input type="number" id="garansi"
                                                    class="form-control field-garansi"
                                                    style="display: inline-block; width: 70%;">
                                                <span style="margin-left: 8px;">Hari</span>
                                            </div>
                                        </div>
                                        <div class="col-6" id="field-peringatan" style="display: none;">
                                            <div class="input-blocks add-product">
                                                <label>Peringatan Kuantitas</label>
                                                <div class="d-flex align-items-center gap-2">
                                                    <input type="number"
                                                        class="form-control field-peringatan-kuantitas"
                                                        id="peringatan_kuantitas">
                                                    <select class="form-control field-peringatan-satuan"
                                                        id="peringatan_satuan">
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <h4>Atur Harga Grosir</h4>
                            <form id="grosir-form" class="mt-3">
                                <div class="row">
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label class="form-label">Minimal Pembelian</label>
                                            <input type="number" class="form-control" id="min_pembelian"
                                                min="1" placeholder="0">
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label class="form-label">Maksimal Pembelian</label>
                                            <input type="number" class="form-control" id="max_pembelian"
                                                min="1" placeholder="0">
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label class="form-label">Harga Grosir</label>
                                            <div class="input-group">
                                                <span class="input-group-text">Rp</span>
                                                <input type="text" class="form-control" id="harga_grosir"
                                                    placeholder="0">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end mb-3">
                                    <button type="button" class="btn btn-submit btnCustom"
                                        id="tambah-harga-grosir">Tambahkan Harga Grosir</button>
                                </div>
                            </form>

                            <!-- Table -->
                            <table class="table mt-3">
                                <thead>
                                    <tr>
                                        <th>Minimal Pembelian</th>
                                        <th>Maksimal Pembelian</th>
                                        <th>Harga Grosir</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="grosir-table-body">
                                </tbody>
                            </table>
                            <div class="modal-footer-btn">
                                <button type="button" class="btn btn-submit btnCustom btn-save-variation"
                                    id="grosir-simpan">Simpan
                                    Perubahan</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Edit Variant -->
@endif

@if (Route::is(['shift']))
    <!-- Add Shift -->
    <div class="modal fade" id="add-units">
        <div class="modal-dialog modal-dialog-centered custom-modal-two">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>Add New Shift</h4>
                            </div>
                            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <form action="shift">
                                <div class="tab-content" id="pills-tabContent">
                                    <div class="tab-pane fade show active" id="pills-add-shift-info"
                                        role="tabpanel" aria-labelledby="pills-add-shift-info-tab">
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="input-blocks">
                                                    <label>Nama Shift </label>
                                                    <input type="text" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="input-blocks">
                                                    <label>Dari</label>
                                                    <div class="form-icon">
                                                        <input type="text" class="form-control timepicker"
                                                            placeholder="Select Time">
                                                        <span class="cus-icon"><i data-feather="clock"
                                                                class="feather-clock"></i></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="input-blocks">
                                                    <label>Sampai</label>
                                                    <div class="form-icon">
                                                        <input type="text" class="form-control timepicker"
                                                            placeholder="Select Time">
                                                        <span class="cus-icon"><i data-feather="clock"
                                                                class="feather-clock"></i></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <label class="mb-2">Hari Kerja</label>
                                        <div class="row">
                                            <div class="col-3 mb-2">
                                                <div class="status-toggle modal-status d-flex align-items-center">
                                                    <input type="checkbox" id="senin" class="check">
                                                    <label for="day1" class="checktoggle"></label>
                                                    <span class="status-label ms-2">Senin</span>
                                                </div>
                                            </div>
                                            <div class="col-3 mb-2">
                                                <div class="status-toggle modal-status d-flex align-items-center">
                                                    <input type="checkbox" id="selasa" class="check">
                                                    <label for="day1" class="checktoggle"></label>
                                                    <span class="status-label ms-2">Selasa</span>
                                                </div>
                                            </div>
                                            <div class="col-3 mb-2">
                                                <div class="status-toggle modal-status d-flex align-items-center">
                                                    <input type="checkbox" id="rabu" class="check">
                                                    <label for="day1" class="checktoggle"></label>
                                                    <span class="status-label ms-2">Rabu</span>
                                                </div>
                                            </div>
                                            <div class="col-3 mb-2">
                                                <div class="status-toggle modal-status d-flex align-items-center">
                                                    <input type="checkbox" id="kamis" class="check">
                                                    <label for="day1" class="checktoggle"></label>
                                                    <span class="status-label ms-2">Kamis</span>
                                                </div>
                                            </div>
                                            <div class="col-3 mb-2">
                                                <div class="status-toggle modal-status d-flex align-items-center">
                                                    <input type="checkbox" id="jumat" class="check">
                                                    <label for="day1" class="checktoggle"></label>
                                                    <span class="status-label ms-2">Jumat</span>
                                                </div>
                                            </div>
                                            <div class="col-3 mb-2">
                                                <div class="status-toggle modal-status d-flex align-items-center">
                                                    <input type="checkbox" id="sabtu" class="check">
                                                    <label for="day1" class="checktoggle"></label>
                                                    <span class="status-label ms-2">Sabtu</span>
                                                </div>
                                            </div>
                                            <div class="col-3 mb-2">
                                                <div class="status-toggle modal-status d-flex align-items-center">
                                                    <input type="checkbox" id="minggu" class="check">
                                                    <label for="day1" class="checktoggle"></label>
                                                    <span class="status-label ms-2">Minggu</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="modal-footer-btn">
                                    <button type="button" class="btn btn-cancel me-2"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-submit">Submit</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Add Shift -->
@endif


@if (Route::is(['department']))
    <!-- Add Department -->
    <div class="modal fade" id="add-department">
        <div class="modal-dialog modal-dialog-centered custom-modal-two">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>Add Department</h4>
                            </div>
                            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <form action="department-grid">
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="mb-3">
                                            <label class="form-label">Department Name</label>
                                            <input type="text" class="form-control" id="department_name">
                                        </div>
                                    </div>


                                </div>
                                <div class="modal-footer-btn">
                                    <button type="button" class="btn btn-cancel me-2"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-save">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Add Department -->
@endif

@if (Route::is(['BestSeller']))
    <div class="modal fade" id="add-best-seller">
        <div class="modal-dialog add-centered">
            <div class="modal-content">
                <div class="page-wrapper p-0 m-0">
                    <div class="content p-0">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>Add Best Seller</h4>
                            </div>
                            <button type="button" class="close ms-auto" data-bs-dismiss="modal"
                                aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-lg-4 col-sm-6 col-12">
                                        <div class="input-blocks">
                                            <div class="row">
                                                <div class="col-lg-10 col-sm-10 col-10">
                                                    <label class="form-label">Store</label>
                                                    <select class="form-control select2" id="store_id">
                                                        <option value="">Select Store</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-sm-6 col-12">
                                        <div class="input-blocks">
                                            <div class="row">
                                                <div class="col-lg-10 col-sm-10 col-10">
                                                    <label class="form-label">Input SKU/Barcode</label>
                                                    <input type="text" class="form-control"
                                                        id="product-input-add"
                                                        placeholder="Enter SKU or Barcode and press Enter">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="table-responsive no-pagination">
                                    <table class="table datanew" id="items-table-add">
                                        <thead>
                                            <tr>
                                                <th>Nama Produk</th>
                                                <th>Varian</th>
                                                <th>SKU</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="modal-footer" style="justify-content: flex-end;">
                                    <button type="button" class="btn btn-cancel me-2"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-submit"
                                        id="save-best-seller">Simpan</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Edit Best Seller -->
    <div class="modal fade" id="edit-best-seller">
        <div class="modal-dialog add-centered">
            <div class="modal-content">
                <div class="page-wrapper p-0 m-0">
                    <div class="content p-0">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>Edit Best Seller</h4>
                            </div>
                            <button type="button" class="close ms-auto" data-bs-dismiss="modal"
                                aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="card">
                            <div class="card-body">
                                <form action="sales-list">
                                    <div class="row">
                                        <div class="col-lg-4 col-sm-6 col-12">
                                            <div class="input-blocks">
                                                <div class="row">
                                                    <div class="col-lg-10 col-sm-10 col-10">
                                                        <label class="form-label">Store</label>
                                                        <input type="text" class="form-control" id="store"
                                                            readonly>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-sm-6 col-12">
                                            <div class="input-blocks">
                                                <div class="row">
                                                    <div class="col-lg-10 col-sm-10 col-10">
                                                        <label class="form-label">Input SKU/Barcode</label>
                                                        <input type="text" class="form-control"
                                                            id="product-input"
                                                            placeholder="Enter SKU or Barcode and press Enter">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="table-responsive no-pagination">
                                        <table class="table datanew" id="items-table">
                                            <thead>
                                                <tr>
                                                    <th>Nama Produk</th>
                                                    <th>Varian</th>
                                                    <th>SKU</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            </tbody>
                                        </table>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Edit Best Seller -->
@endif

@if (Route::is(['productPrices']))
    <div class="modal fade" id="grosir">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>Kelola Harga Produk</h4>
                            </div>
                            <button type="button" class="close ms-auto" data-bs-dismiss="modal"
                                aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <!-- Product Information (Readonly) -->
                            <h5 class="card-title mb-3">Informasi Produk</h5>
                            <div class="row">
                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label class="form-label">SKU</label>
                                        <input type="text" class="form-control" id="product_sku" readonly>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label class="form-label">Nama Produk</label>
                                        <input type="text" class="form-control" id="product_name" readonly>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label class="form-label">Varian</label>
                                        <input type="text" class="form-control" id="product_variant" readonly>
                                    </div>
                                </div>
                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <label class="form-label">Kategori</label>
                                        <input type="text" class="form-control" id="product_category" readonly>
                                    </div>
                                </div>
                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <label class="form-label">Merek</label>
                                        <input type="text" class="form-control" id="product_brand" readonly>
                                    </div>
                                </div>
                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <label class="form-label">Harga Toko</label>
                                        <select class="form-control" id="store_id">
                                            <option value="">Pilih Store</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3">
                                    <div class="mb-3">
                                        <label class="form-label">Harga</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" class="form-control fill number-only"
                                                id="harga_produk" placeholder="0">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Grosir Price Form -->
                            <h5 class="card-title mt-3 mb-3">Edit Harga Produk</h5>
                            <form id="grosir-form">
                                <div class="row">
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label class="form-label">Minimal Pembelian</label>
                                            <input type="number" class="form-control fill" id="min_pembelian"
                                                min="1" placeholder="0">
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label class="form-label">Maksimal Pembelian</label>
                                            <input type="number" class="form-control fill" id="max_pembelian"
                                                min="1" placeholder="0">
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label class="form-label">Harga Grosir</label>
                                            <div class="input-group">
                                                <span class="input-group-text">Rp</span>
                                                <input type="text" class="form-control fill number-only"
                                                    id="harga_grosir" placeholder="0">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end mb-3">
                                    <button type="button" class="btn btn-submit btnCustom"
                                        id="tambah-harga-grosir">Tambahkan Harga Grosir</button>
                                </div>
                            </form>

                            <!-- Table -->
                            <table class="table mt-3">
                                <thead>
                                    <tr>
                                        <th>Minimal Pembelian</th>
                                        <th>Maksimal Pembelian</th>
                                        <th>Harga Grosir</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="grosir-table-body">
                                </tbody>
                            </table>

                            {{-- <div class="modal-footer-btn">
                                <button type="button" class="btn btn-submit btnCustom" id="grosir-simpan">Simpan</button>
                            </div> --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

@if (Route::is(['POS']))
    <!-- Add Expense -->
    <div class="modal fade" id="add-expense">
        <div class="modal-dialog modal-dialog-centered custom-modal-two">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>Add Activity</h4>
                            </div>
                            <button type="button" class="close ms-auto" data-bs-dismiss="modal"
                                aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <label class="form-label">Kategori</label>
                                        <select class="select" id="activity_category">
                                            <option selected>Pemasukan</option>
                                            <option>Pengeluaran</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <label class="form-label">Nominal</label>
                                        <input type="text" id="nominal" class="form-control nominal_only"
                                            placeholder="Rp.xxx.xxx">
                                    </div>
                                </div>
                                <!-- Editor -->
                                <div class="col-md-12">
                                    <div class="edit-add card">
                                        <div class="edit-add">
                                            <label class="form-label">Deskripsi</label>
                                        </div>
                                        <div class="card-body-list input-blocks mb-0">
                                            <textarea class="form-control" id="deskripsi"></textarea>
                                        </div>
                                        <p>Maximum 600 Characters</p>
                                    </div>
                                </div>
                                <!-- /Editor -->
                            </div>
                            <div class="modal-footer-btn">
                                <a class="btn btn-cancel me-2" data-bs-dismiss="modal">Cancel</a>
                                <a class="btn btn-submit" id="submit-btn-activity">Submit</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Add Expense -->
    <!-- Add Transaksi -->
    <div class="modal fade" id="add-history">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>History Transaksi</h4>
                            </div>
                            <button type="button" class="close ms-auto" data-bs-dismiss="modal"
                                aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <!-- Loading Spinner -->
                            <div id="history-loading-spinner" class="text-center py-5" style="display: none;">
                                <div class="spinner-border" role="status"
                                    style="width: 3rem; height: 3rem; color: #007bff;">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-3 mb-0 text-muted">Memuat history transaksi...</p>
                            </div>

                            <div class="table-responsive" id="history-table-container">
                                <table class="table" id="history-table">
                                    <thead>
                                        <tr>
                                            <th>Invoice ID</th>
                                            <th>Tanggal</th>
                                            <th>Kasir</th>
                                            <th>Customer</th>
                                            <th>Discount</th>
                                            <th>PPN</th>
                                            <th>Total</th>
                                            <th class="text-center">Metode Bayar</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data will be populated here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="session-start">
        <div class="modal-dialog modal-dialog-centered custom-modal-two">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>Start Session</h4>
                            </div>
                        </div>
                        <div class="modal-body custom-modal-body new-employee-field">
                            <form action="{{ url('brand-list') }}">
                                <div class="mb-3">
                                    <label class="form-label">Cash Drawer Awal</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="text" class="form-control fill nominal_only"
                                            id="cash_drawer_start" placeholder="Masukkan Cash Drawer">
                                    </div>
                                </div>
                                <div class="modal-footer-btn">
                                    <div class="btn btn-save" id="start-session-btn">Start Session</div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="session-end">
        <div class="modal-dialog modal-dialog-centered custom-modal-two">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>End Session</h4>
                            </div>
                        </div>
                        <div class="modal-body custom-modal-body new-employee-field">
                            <form action="{{ url('brand-list') }}">
                                <div class="mb-3">
                                    
                                    <label class="form-label mt-3">Cash Drawer Akhir (Nominal asli)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="text" class="form-control fill nominal_only"
                                            id="cash_drawer_end" placeholder="Masukkan Cash Drawer">
                                    </div>
                                </div>
                                <div class="modal-footer-btn">
                                    <div class="btn btn-cancel me-2" id="cancel-session-btn"
                                        data-bs-dismiss="modal">Cancel</div>
                                    <div class="btn btn-save" id="end-session-btn">End Session</div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- Select Customer --}}
    <div class="modal fade" id="selectCustomer" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Pilih Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="search-set">
                                <input type="text" placeholder="Cari customer..." class="form-control"
                                    id="customer-search">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div id="customer-loading" class="text-center py-4" style="display: none;">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">Memuat customer...</p>
                            </div>
                            <div id="customer-list" class="customer-list"
                                style="max-height: 400px; overflow-y: auto;">
                                <!-- Customer list will be populated here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- Timeout Modal --}}
    <div class="modal fade" id="timeoutModal" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">POS Timeout</h5>
                </div>
                <div class="modal-body p-4">
                    <div class="text-center mb-3">
                        <p>Sesi POS telah tidak aktif selama 5 menit. Silahkan masukkan password untuk melanjutkan.</p>
                    </div>
                    <form id="timeoutForm">
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" id="timeoutPassword"
                                placeholder="Masukkan password">
                        </div>
                    </form>
                </div>
                <div class="modal-footer ms-auto">
                    <button type="button" class="btn btn-primary btn-rounded btn-sm"
                        id="btnResumeSession">Lanjutkan Sesi</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal Otorisasi Delete -->
    <div class="modal fade" id="authDelete">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Otorisasi Pembatalan Produk</h5>
                    <button type="button" class="close ms-auto" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <form id="authDeleteForm">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" id="authUsername"
                                placeholder="Masukkan username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" id="authPassword"
                                placeholder="Masukkan password" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer ms-auto">
                    <button type="button" class="btn btn-cancel btn-rounded btn-sm"
                        data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary btn-rounded btn-sm"
                        id="btnOtorisasi">Otorisasi</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="add-promo">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>Atur Promo</h4>
                            </div>
                            <button type="button" class="close ms-auto" data-bs-dismiss="modal"
                                aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            {{-- Swiper --}}
                            <div class="swiper promoSwiper mb-4">
                                <div class="swiper-wrapper" id="promo-swiper-wrapper">
                                    <!-- Promo slides will be dynamically generated here -->
                                </div>
                            </div>
                            {{-- Rekap Diskon --}}
                            <div class="promo-recap mb-4" id="promo-recap" style="display: none;">
                                <h6 class="mb-2">Rekap Diskon:</h6>
                                <div class="recap-content" id="recap-content">
                                    <!-- Discount recap will be generated here -->
                                </div>
                            </div>
                            <div class="modal-footer-btn">
                                <button type="button" class="btn btn-submit" id="promo-simpan"
                                    data-bs-dismiss="modal">Terapkan</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="confirm-order">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="content">
                        <div class="modal-header border-0 custom-modal-header">
                            <div class="page-title">
                                <h4>Konfirmasi Pesanan</h4>
                            </div>
                            <button type="button" class="close ms-auto" data-bs-dismiss="modal"
                                aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body custom-modal-body">
                            <!-- Order Summary -->
                            <div class="order-summary mb-4">
                                <h6 class="mb-3">Atur Kertas</h6>
                                 <div class="row mb-2">
                                        <div class="col-6">Ukuran Kertas Printer : </div>
                                        <div class="col-6 text-end" style="font-size: 1rem;">
                                            <select name="" class="form-select" id="ukuran_kertas">
                                                <option value="58mm">58 MM</option>
                                                <option value="80mm">80 MM</option>
                                            </select>
                                        </div>
                                    </div>
                                <h6 class="mb-3">Ringkasan Pesanan</h6>
                                <div class="summary-details" style="font-size: 1rem;">
                                    <div class="row mb-2">
                                        <div class="col-6">Sub Total:</div>
                                        <div class="col-6 text-end" id="modal-subtotal" style="font-size: 1rem;">
                                            Rp 0</div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-6">Total Potongan:</div>
                                        <div class="col-6 text-end text-danger" id="modal-discount"
                                            style="font-size: 1rem;">Rp 0</div>
                                    </div>
                                    <hr>
                                    <div class="row mb-3">
                                        <div class="col-6"><strong>Total:</strong></div>
                                        <div class="col-6 text-end" id="modal-total"
                                            style="color:#007aff; font-size: 1.25rem;">
                                            <strong>Rp 0</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Method -->
                            <div class="block-section payment-method mb-4">
                                <h6 class="mb-3" style="font-weight: 600; color: #333;">Metode Pembayaran</h6>
                                <div class="row d-flex align-items-center justify-content-start methods">
                                    <div class="col-md-6 col-lg-3 item mb-3">
                                        <div class="default-cover payment-method-item" data-payment-value="1"
                                            style="
                                            border: 2px solid #e9ecef;
                                            border-radius: 10px;
                                            padding: 12px 8px;
                                            transition: all 0.3s ease;
                                            cursor: pointer;
                                            background: #fff;
                                            box-shadow: 0 1px 2px rgba(0,0,0,0.04);
                                            position: relative;
                                            overflow: hidden;
                                            max-width: 200px;
                                            margin: 0 auto;
                                        "
                                            onmouseover="this.style.borderColor='#007aff'; this.style.boxShadow='0 4px 12px rgba(0,122,255,0.15)'; this.style.transform='translateY(-2px)';"
                                            onmouseout="this.style.borderColor='#e9ecef'; this.style.boxShadow='0 1px 2px rgba(0,0,0,0.04)'; this.style.transform='translateY(0)';">
                                            <a href="javascript:void(0);"
                                                style="text-decoration: none; display: flex; flex-direction: column; align-items: center; color: #666;">
                                                <img src="{{ URL::asset('/build/img/icons/cash-pay.svg') }}"
                                                    alt="Cash"
                                                    style="width: 24px; height: 24px; margin-bottom: 6px;">
                                                <span style="font-size: 12px; font-weight: 500;">Cash</span>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-3 item mb-3">
                                        <div class="default-cover payment-method-item" data-payment-value="2"
                                            style="
                                            border: 2px solid #e9ecef;
                                            border-radius: 10px;
                                            padding: 12px 8px;
                                            transition: all 0.3s ease;
                                            cursor: pointer;
                                            background: #fff;
                                            box-shadow: 0 1px 2px rgba(0,0,0,0.04);
                                            position: relative;
                                            overflow: hidden;
                                            max-width: 200px;
                                            margin: 0 auto;
                                        "
                                            onmouseover="this.style.borderColor='#007aff'; this.style.boxShadow='0 4px 12px rgba(0,122,255,0.15)'; this.style.transform='translateY(-2px)';"
                                            onmouseout="this.style.borderColor='#e9ecef'; this.style.boxShadow='0 1px 2px rgba(0,0,0,0.04)'; this.style.transform='translateY(0)';">
                                            <a href="javascript:void(0);"
                                                style="text-decoration: none; display: flex; flex-direction: column; align-items: center; color: #666;">
                                                <img src="{{ URL::asset('/build/img/icons/credit-card.svg') }}"
                                                    alt="Debit Card"
                                                    style="width: 24px; height: 24px; margin-bottom: 6px;">
                                                <span style="font-size: 12px; font-weight: 500;">Debit</span>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-3 item mb-3">
                                        <div class="default-cover payment-method-item" data-payment-value="3"
                                            style="
                                            border: 2px solid #e9ecef;
                                            border-radius: 10px;
                                            padding: 12px 8px;
                                            transition: all 0.3s ease;
                                            cursor: pointer;
                                            background: #fff;
                                            box-shadow: 0 1px 2px rgba(0,0,0,0.04);
                                            position: relative;
                                            overflow: hidden;
                                            max-width: 200px;
                                            margin: 0 auto;
                                        "
                                            onmouseover="this.style.borderColor='#007aff'; this.style.boxShadow='0 4px 12px rgba(0,122,255,0.15)'; this.style.transform='translateY(-2px)';"
                                            onmouseout="this.style.borderColor='#e9ecef'; this.style.boxShadow='0 1px 2px rgba(0,0,0,0.04)'; this.style.transform='translateY(0)';">
                                            <a href="javascript:void(0);"
                                                style="text-decoration: none; display: flex; flex-direction: column; align-items: center; color: #666;">
                                                <img src="{{ URL::asset('/build/img/icons/credit-card.svg') }}"
                                                    alt="Debit Card"
                                                    style="width: 24px; height: 24px; margin-bottom: 6px;">
                                                <span style="font-size: 12px; font-weight: 500;">Credit Card</span>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-3 item mb-3">
                                        <div class="default-cover payment-method-item" data-payment-value="4"
                                            style="
                                            border: 2px solid #e9ecef;
                                            border-radius: 10px;
                                            padding: 12px 8px;
                                            transition: all 0.3s ease;
                                            cursor: pointer;
                                            background: #fff;
                                            box-shadow: 0 1px 2px rgba(0,0,0,0.04);
                                            position: relative;
                                            overflow: hidden;
                                            max-width: 200px;
                                            margin: 0 auto;
                                        "
                                            onmouseover="this.style.borderColor='#007aff'; this.style.boxShadow='0 4px 12px rgba(0,122,255,0.15)'; this.style.transform='translateY(-2px)';"
                                            onmouseout="this.style.borderColor='#e9ecef'; this.style.boxShadow='0 1px 2px rgba(0,0,0,0.04)'; this.style.transform='translateY(0)';">
                                            <a href="javascript:void(0);"
                                                style="text-decoration: none; display: flex; flex-direction: column; align-items: center; color: #666;">
                                                <img src="{{ URL::asset('/build/img/icons/qr-scan.svg') }}"
                                                    alt="QR Scan"
                                                    style="width: 24px; height: 24px; margin-bottom: 6px;">
                                                <span style="font-size: 12px; font-weight: 500;">QRIS</span>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-3 item mb-3">
                                        <div class="default-cover payment-method-item" data-payment-value="5"
                                            style="
                                            border: 2px solid #e9ecef;
                                            border-radius: 10px;
                                            padding: 12px 8px;
                                            transition: all 0.3s ease;
                                            cursor: pointer;
                                            background: #fff;
                                            box-shadow: 0 1px 2px rgba(0,0,0,0.04);
                                            position: relative;
                                            overflow: hidden;
                                            max-width: 200px;
                                            margin: 0 auto;
                                        "
                                            onmouseover="this.style.borderColor='#007aff'; this.style.boxShadow='0 4px 12px rgba(0,122,255,0.15)'; this.style.transform='translateY(-2px)';"
                                            onmouseout="this.style.borderColor='#e9ecef'; this.style.boxShadow='0 1px 2px rgba(0,0,0,0.04)'; this.style.transform='translateY(0)';">
                                            <a href="javascript:void(0);"
                                                style="text-decoration: none; display: flex; flex-direction: column; align-items: center; color: #666;">
                                                <img src="{{ URL::asset('/build/img/icons/payment-status.svg') }}"
                                                    alt="QR Scan"
                                                    style="width: 24px; height: 24px; margin-bottom: 6px;">
                                                <span style="font-size: 12px; font-weight: 500;">Transfer</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Cash Payment Input -->
                            <div class="cash-payment-section" id="cash-payment-section" style="display: none;">
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <label class="form-label">Uang Customer</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" class="form-control fill nominal_only"
                                                id="customer-cash" placeholder="Masukkan nominal uang customer">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <div class="change-amount p-3"
                                            style="background-color: #f8f9ff; border-radius: 8px; border: 1px solid #e0e6ff;">
                                            <div class="row">
                                                <div class="col-6">
                                                    <strong>Kembalian:</strong>
                                                </div>
                                                <div class="col-6 text-end">
                                                    <strong id="change-amount" style="color: #007aff;">Rp 0</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer-btn mt-4">
                                <button type="button" class="btn btn-cancel me-2"
                                    data-bs-dismiss="modal">Batal</button>
                                <button type="button" class="btn btn-submit" id="confirm-order-btn">Buat
                                    Pesanan</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
