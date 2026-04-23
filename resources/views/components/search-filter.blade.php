@if(Route::is(['profitLoss']))
<!-- Filter Pencarian -->
<div class="profit-menu card report-produksi-filter">
    <div class="row card-body pb-0">
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Dari</label>
                <div class="cal-icon cal-icon-info"> 
                    <input type="text" id="start_date" class="datetimepicker form-control" placeholder="01-01-2025">
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Sampai</label>
                <div class="cal-icon cal-icon-info"> 
                    <input type="text" id="end_date" class="datetimepicker form-control" placeholder="01-01-2025">
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-0"></div>
        <div class="col-lg-2 col-md-6 col-sm-12">
            <a class="btn btn-primary loss btn-filter">
                Jalankan
            </a>
        </div>
    </div>
</div>
<!-- /Filter Pencarian -->
@endif

@if(Route::is(['ProductReturn']))
    <!-- Filter Pencarian -->
<div class="profit-menu card">
    <div class="row card-body pb-0 g-3 align-items-end">
        <div class="col-xl-2 col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Dari</label>
                <div class="cal-icon cal-icon-info">
                    <input type="text" class="datetimepicker form-control" id="start_date" placeholder="01 Jan 2023">
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Sampai</label>
                <div class="cal-icon cal-icon-info">
                    <input type="text" class="datetimepicker form-control" id="end_date" placeholder="31 Mar 2023">
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Supplier</label>
                <select class="form-select" id="supplier"></select>
            </div>
        </div>
        <div class="col-xl-4 col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Bahan Mentah</label>
                <select class="form-select" id="supplies_id"></select>
            </div>
        </div>
        <div class="col-xl-1 col-lg-12 col-md-12 col-sm-12">
            <div class="d-flex gap-2 justify-content-xl-end justify-content-lg-end justify-content-md-end justify-content-end mb-3">
                <a class="btn btn-outline-secondary btn-clear">
                    Clear
                </a>
            </div>
        </div>
    </div>
</div>
    <!-- /Filter Pencarian -->
@endif

@if(Route::is(['reportBahanBaku']))
<!-- Filter Pencarian -->
<div class="profit-menu card report-bahan-filter">
    <div class="row card-body pb-0 g-3 align-items-end">
        <div class="col-xl-2 col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Dari</label>
                <div class="cal-icon cal-icon-info">
                    <input type="text" class="datetimepicker form-control" id="start_date">
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Sampai</label>
                <div class="cal-icon cal-icon-info">
                    <input type="text" class="datetimepicker form-control" id="end_date">
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Supplier</label>
                <select class="form-select" id="supplier"></select>
            </div>
        </div>
        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Bahan Mentah</label>
                <select class="form-select" id="supplies_id"></select>
            </div>
        </div>
        <div class="col-xl-1 col-lg-12 col-md-12 col-sm-12">
            <div class="d-flex gap-2 justify-content-xl-end justify-content-lg-end justify-content-md-end justify-content-end mb-3 w-100">
                <a class="btn btn-outline-secondary btn-clear">
                    Clear
                </a>
            </div>
        </div>
    </div>
</div>
<!-- /Filter Pencarian -->
@endif

@if(Route::is(['reportSelisihOpname']))
<!-- Filter Pencarian -->
<div class="profit-menu card report-bahan-filter">
    <div class="row card-body pb-0 g-3 align-items-end">
        <div class="col-xl-2 col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Dari</label>
                <div class="cal-icon cal-icon-info">
                    <input type="text" class="datetimepicker form-control" id="start_date" placeholder="01 Jan 2023">
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Sampai</label>
                <div class="cal-icon cal-icon-info">
                    <input type="text" class="datetimepicker form-control" id="end_date" placeholder="31 Mar 2023">
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Type</label>
                <select class="form-select" id="selisih_type">
                    <option value="all" selected>All</option>
                    <option value="bahan">Bahan</option>
                    <option value="product">Product</option>
                </select>
            </div>
        </div>
        <div class="col-xl-5 col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Item</label>
                <select class="form-select" id="selisih_item_id"></select>
            </div>
        </div>
        <div class="col-xl-1 col-lg-12 col-md-12 col-sm-12">
            <div class="d-flex gap-2 justify-content-xl-end justify-content-lg-end justify-content-md-end justify-content-end mb-3">
                <a class="btn btn-outline-secondary btn-clear">
                    Clear
                </a>
            </div>
        </div>
    </div>
</div>
<!-- /Filter Pencarian -->
@endif

@if(Route::is(['reportStockAging']))
<!-- Filter Pencarian -->
<div class="profit-menu card report-stock-aging-filter">
    <div class="row card-body pb-0 g-3 align-items-end">
        <div class="col-xl-2 col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Tanggal acuan umur</label>
                <div class="cal-icon cal-icon-info">
                    <input type="text" class="datetimepicker form-control" id="aging_as_of" placeholder="18-04-2026">
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Type</label>
                <select class="form-select" id="aging_type">
                    <option value="all" selected>All</option>
                    <option value="bahan">Bahan</option>
                    <option value="product">Product</option>
                </select>
            </div>
        </div>
        <div class="col-xl-5 col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Item</label>
                <select class="form-select" id="aging_item_id"></select>
            </div>
        </div>
        <div class="col-xl-1 col-lg-12 col-md-12 col-sm-12">
            <div class="d-flex gap-2 justify-content-xl-end justify-content-lg-end justify-content-md-end justify-content-end mb-3">
                <a class="btn btn-outline-secondary btn-clear">
                    Clear
                </a>
            </div>
        </div>
    </div>
</div>
<!-- /Filter Pencarian -->
@endif

@if(Route::is(['reportProduksi']))
<!-- Filter Pencarian -->
<div class="profit-menu card">
    <div class="row card-body pb-0 g-3 align-items-end">
        <div class="col-xl-2 col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Dari</label>
                <div class="cal-icon cal-icon-info">
                    <input type="text" class="datetimepicker form-control" id="start_date" placeholder="01 Jan 2023">
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Sampai</label>
                <div class="cal-icon cal-icon-info">
                    <input type="text" class="datetimepicker form-control" id="end_date" placeholder="31 Mar 2023">
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Supplier</label>
                <select class="form-select" id="supplier"></select>
            </div>
        </div>
        <div class="col-xl-4 col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Produk</label>
                <select class="form-select" id="product_id"></select>
            </div>
        </div>
        <div class="col-xl-1 col-lg-12 col-md-12 col-sm-12">
            <div class="d-flex gap-2 justify-content-xl-end justify-content-lg-end justify-content-md-end justify-content-end mb-3">
                
                <a class="btn btn-outline-secondary btn-clear">
                    Clear
                </a>
            </div>
        </div>
    </div>
</div>
<!-- /Filter Pencarian -->
@endif

@if(Route::is(['reportReturProdukArmada']))
<div class="profit-menu card report-retur-armada-filter">
    <div class="row card-body pb-0 g-3 align-items-end">
        <div class="col-xl-2 col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Dari</label>
                <div class="cal-icon cal-icon-info">
                    <input type="text" class="datetimepicker form-control" id="start_date" placeholder="01 Jan 2023">
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Sampai</label>
                <div class="cal-icon cal-icon-info">
                    <input type="text" class="datetimepicker form-control" id="end_date" placeholder="31 Mar 2023">
                </div>
            </div>
        </div>
        <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12">
            <div class="input-block mb-3">
                <label>Produk</label>
                <select class="form-select" id="product_id"></select>
            </div>
        </div>
        <div class="col-xl-2 col-lg-12 col-md-12 col-sm-12">
            <div class="d-flex gap-2 justify-content-xl-end justify-content-lg-end justify-content-md-end justify-content-end mb-3">
                <a class="btn btn-outline-secondary btn-clear">Clear</a>
            </div>
        </div>
    </div>
</div>
@endif

@if(Route::is(['reportEfisiensiProduksi']))
<!-- Filter Pencarian -->
<div class="profit-menu card">
    <div class="row card-body pb-0 g-3 align-items-end">
        <div class="col-xl-2 col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Dari</label>
                <div class="cal-icon cal-icon-info">
                    <input type="text" class="datetimepicker form-control" id="start_date" placeholder="01 Jan 2023">
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Sampai</label>
                <div class="cal-icon cal-icon-info">
                    <input type="text" class="datetimepicker form-control" id="end_date" placeholder="31 Mar 2023">
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Supplier</label>
                <select class="form-select" id="supplier"></select>
            </div>
        </div>
        <div class="col-xl-4 col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Produk</label>
                <select class="form-select" id="product_id"></select>
            </div>
        </div>
        <div class="col-xl-1 col-lg-12 col-md-12 col-sm-12">
            <div class="d-flex gap-2 justify-content-xl-end justify-content-lg-end justify-content-md-end justify-content-end mb-3">
                <a class="btn btn-outline-secondary btn-clear">
                    Clear
                </a>
            </div>
        </div>
    </div>
</div>
<!-- /Filter Pencarian -->
@endif

@if(Route::is(['inwardOutward']))
<!-- Filter Pencarian -->
<div class="container mt-3">
    <div class="card p-3">
        <div class="row g-2 align-items-center">
            <!-- Rentang Tanggal -->
            <div class="col-md">
                <label class="form-label mb-1">Pilih Tanggal</label>
                <input type="text" class="form-control" id="filter_io_date" placeholder="Pilih rentang tanggal">
            </div>
            <!-- Kategori -->
            <div class="col-md">
                <label class="form-label mb-1">Kategori</label>
                <select class="form-select" id="filter_io_category"></select>
            </div>
            <!-- Produk -->
            <div class="col-md">
                <label class="form-label mb-1">Produk</label>
                <select class="form-select" id="filter_io_products"></select>
            </div>

            <!-- Satuan -->
            <div class="col-md">
                <label class="form-label mb-1">Satuan</label>
                <select class="form-select" id="filter_io_units"></select>
            </div>

            <!-- Tombol -->
            <div class="col-md-auto d-flex align-items-end pt-4">
                <button class="btn btn-primary w-100" id="generateReport">Buat Laporan</button>
            </div>
        </div>
    </div>
</div>
<!-- /Filter Pencarian -->
@endif

@if(Route::is(['pettyCash']))
<!-- Filter Pencarian -->
<div class="container mt-3 ps-0">
    <div class="row">
        {{-- 
        <div class="col-12 col-md-6">

            <div class="card p-3">
                <div class="row g-2 align-items-center">
                    <!-- Rentang Tanggal -->
                    <div class="col-md">
                        <label class="form-label mb-1">Search No Tanda terima</label>
                        <input type="text" class="form-control" id="filter_po" placeholder="No.PO">
                    </div>
                    
                    <!-- Supplier -->
                    <div class="col-md">
                        <label class="form-label mb-1">Search Supplier</label>
                        <select class="form-select" id="filter_supplier"></select>
                    </div>
                    
                </div>
            </div>
        </div>--}}
        
        <div class="col-12 col-md-6">
            <div class="card p-3">
                <div class="row g-2 align-items-center">
                    <!-- Supplier -->
                    <div class="col-md row-supplier">
                        <label class="form-label mb-1">Dari Tanggal</label>
                        <input type="date" name="" id="filter_tanggal_start" class="form-control">
                    </div>
                    <div class="col-md row-supplier">
                        <label class="form-label mb-1">Sampai Tanggal</label>
                        <input type="date" name="" id="filter_tanggal_end" class="form-control">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /Filter Pencarian -->
@endif

@if(Route::is(['operationalCash']))
<!-- Filter Pencarian -->
<div class="container mt-3 px-0">
    <div class="row">
        {{-- 
        <div class="col-12 col-md-6">

            <div class="card p-3">
                <div class="row g-2 align-items-center">
                    <!-- Rentang Tanggal -->
                    <div class="col-md">
                        <label class="form-label mb-1">Search No Tanda terima</label>
                        <input type="text" class="form-control" id="filter_po" placeholder="No.PO">
                    </div>
                    
                    <!-- Supplier -->
                    <div class="col-md">
                        <label class="form-label mb-1">Search Supplier</label>
                        <select class="form-select" id="filter_supplier"></select>
                    </div>
                    
                </div>
            </div>
        </div>--}}
        
        <div class="col-12">
            <div class="card p-3">
                <div class="row g-2 align-items-center">
                    <!-- Supplier -->
                    <div class="col-md-4 row-supplier">
                        <label class="form-label mb-1">Dari Tanggal</label>
                        <input type="date" name="" id="start_date" class="form-control">
                    </div>
                    <div class="col-md-4 row-supplier">
                        <label class="form-label mb-1">Sampai Tanggal</label>
                        <input type="date" name="" id="end_date" class="form-control">
                    </div>
                    <div class="col-md-3 filter_person">
                        <label class="form-label mb-1">Staff</label>
                        <select class="form-select" id="filter_staff_id"></select>
                    </div>
                    <div class="col-md-1 pt-4 text-end">
                        <a class="btn btn-outline-secondary btn-clear">
                            Clear
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /Filter Pencarian -->
@endif

@if(Route::is(['cash']))
<!-- Filter Pencarian -->
<div class="container mt-3 px-0">
    <div class="row">
        <div class="col-12">
            <div class="card p-3">
                <div class="row g-2 align-items-center">
                    <!-- Supplier -->
                    <div class="col-md-4 row-supplier">
                        <label class="form-label mb-1">Dari Tanggal</label>
                        <input type="date" name="" id="start_date" class="form-control">
                    </div>
                    <div class="col-md-4 row-supplier">
                        <label class="form-label mb-1">Sampai Tanggal</label>
                        <input type="date" name="" id="end_date" class="form-control">
                    </div>
                    <div class="col-md-3"></div>
                    <div class="col-md-1 col-sm-12 pt-4 text-end">
                        <a class="btn btn-outline-secondary btn-clear">
                            Clear
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /Filter Pencarian -->
@endif

@if(Route::is(['payReceive']))
<!-- Filter Pencarian -->
<div class="container mt-3 px-0">
    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card p-3 h-100">
                <h6 class="card-title border-bottom pb-2 mb-3">Filter Pencarian</h6>
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-4">
                        <div class="input-block">
                            <label class="form-label mb-1">Bank Account</label>
                            <select class="form-select fill" id="bank_kode"></select>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="input-block">
                            <label class="form-label mb-1">Supplier</label>
                            <select class="form-select fill" id="supplier"></select>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="input-block">
                            <label class="form-label mb-1">Status</label>
                            <select class="form-select fill" id="status">
                                <option value="" selected>Semua</option>
                                <option value="1">Belum Terbayar</option>
                                <option value="3">Menunggu Tanda Terima</option>
                                <option value="2">Terbayar</option>
                                <option value="5">Ditolak</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="input-block">
                            <label class="form-label mb-1">Dari</label>
                            <input type="date" class="form-control" id="start_date">
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="input-block">
                            <label class="form-label mb-1">Sampai</label>
                            <input type="date" class="form-control" id="end_date">
                        </div>
                    </div>
                    <div class="col-12 col-md-4 text-end">
                        <button class="btn btn-outline-secondary btn-clear w-100 w-md-auto">
                            Clear Filter
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card p-3 h-100">
                <h6 class="card-title border-bottom pb-2 mb-3">Aksi & Ringkasan</h6>
                <div class="row g-3">
                    <div class="col-12 col-md-6 col-lg-12">
                        <label class="form-label d-block mb-1">Laporan</label>
                        <button class="btn btn-outline-info btn-print w-100 mt-lg-0 mt-3">
                            Print Hutang <i class="fa fa-file ms-1"></i>
                        </button>
                    </div>
                    <div class="col-12 col-md-6 col-lg-12 text-md-end text-start text-lg-start">
                        <div class="d-flex justify-content-lg-start justify-content-end gap-2 align-items-center mb-2">
                            <label class="form-label mb-1">Tanda Terima</label>
                            <div>
                                <span id="jumlah_terpilih" class="badge bg-light text-dark p-2 border cursor-pointer">
                                    0 Selected <i class="fe fe-refresh-ccw ms-1"></i>
                                </span>
                            </div>
                        </div>
                        <button class="btn btn-primary btn-create w-100">
                            Buat Tanda Terima
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /Filter Pencarian -->
@endif
@if(Route::is(['tt']))
<div class="container mt-3 ps-0 pe-0" style="overflow-x: hidden; max-width: 100%;">
    <div class="row g-0 mx-0">  {{-- ✅ g-0 dan mx-0 hilangkan gutter yang bikin overflow --}}
        <div class="col-12 mb-4">
            <div class="card p-3" style="overflow: hidden;">
                <div class="row g-2 align-items-end mx-0">
                    <div class="col-lg-3 col-md-3 col-sm-6 col-12">
                        <div class="input-block">
                            <label>Dari</label>
                            <input type="date" class="form-control" id="start_date">
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-3 col-sm-6 col-12">
                        <div class="input-block">
                            <label>Sampai</label>
                            <input type="date" class="form-control" id="end_date">
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-4 col-sm-6 col-12">
                        <div class="input-block">
                            <label>Supplier</label>
                            <select class="form-select" id="filter_supplier"></select>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-2 col-sm-6 col-12">
                        <div class="input-block">
                            <a class="btn btn-outline-secondary btn-clear w-100">Clear</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@if(Route::is(['purchaseOrder']))
<!-- Filter Pencarian -->
<div class="container mt-3 ps-0">
    <div class="row">
        <div class="col-12 col-md-12 mb-4">
            <div class="card p-3">
                <div class="row g-2 align-items-center">
                    <div class="col-md-3">
                        <div class="input-block">
                            <label>Dari</label>
                            <div>
                                <input type="date" class="form-control" id="start_date">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="input-block">
                            <label>Sampai</label>
                            <div>
                                <input type="date" class="form-control" id="end_date">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="input-block">
                            <label>Status</label>
                            <select class="form-select fill" id="status">
                                <option value="">Semua</option>
                                <option value="4">Menunggu Approval</option>
                                <option value="1">Belum Terbayar</option>
                                <option value="3">Menunggu Tanda Terima</option>
                                <option value="2">Terbayar</option>
                                <option value="5">Ditolak</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-12 pt-4 text-end">
                        <a class="btn btn-outline-secondary btn-clear">
                            Clear
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /Filter Pencarian -->
@endif

@if(Route::is(['production']))
<!-- Filter Pencarian -->
<div class="container mt-3 ps-0">
    <div class="row">
        <div class="col-12 col-md-12 mb-4">
            <div class="card p-3">
                <div class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <label>Tanggal</label>
                        <input type="date" class="form-control fill" id="date_production" >
                    </div>
                    <div class="col-md-4">
                        <div class="input-block">
                            <label>Status</label>
                            <select class="form-select fill" id="status">
                                <option value="">Semua</option>
                                <option value="1">Pending</option>
                                <option value="2">Berhasil</option>
                                <option value="4">Menunggu Batal</option>
                                <option value="3">Tolak</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-12 pt-4 text-end">
                        <a class="btn btn-outline-secondary btn-clear">
                            Clear
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /Filter Pencarian -->
@endif