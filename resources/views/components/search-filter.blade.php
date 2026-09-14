@php
    $akses = Session::has('user') && Session::get('user')?->role_access 
        ? collect(json_decode(Session::get('user')->role_access)) 
        : collect();
@endphp
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
<!-- Filter Pencarian & Ringkasan Aksi -->
<div class="mt-3 px-0">
    <div class="row g-3 align-items-stretch">
        <!-- Card Filter Pencarian -->
        <div class="col-12 col-xl-8 col-lg-7">
            <div class="card p-3 h-100 shadow-sm border-0" style="border: 1px solid #e2e8f0 !important; border-radius: 12px;">
                <div class="d-flex align-items-center justify-content-between border-bottom pb-2 mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <div style="width: 28px; height: 28px; border-radius: 6px; background: #eff6ff; display: flex; align-items: center; justify-content: center; color: #2563eb;">
                            <i class="fe fe-filter" style="font-size: 13px;"></i>
                        </div>
                        <h6 class="card-title mb-0 fw-bold text-dark" style="font-size: 14px;">Filter Pencarian</h6>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-clear d-inline-flex align-items-center gap-1 px-2.5 py-1" style="border-radius: 6px; font-size: 12px; font-weight: 600;">
                        <i class="fe fe-rotate-ccw" style="font-size: 11px;"></i> Reset Filter
                    </button>
                </div>
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-4">
                        <div class="input-block mb-0">
                            <label class="form-label mb-1 fw-semibold text-secondary" style="font-size: 12px;">Bank Account</label>
                            <select class="form-select fill" id="bank_kode"></select>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="input-block mb-0">
                            <label class="form-label mb-1 fw-semibold text-secondary" style="font-size: 12px;">Supplier</label>
                            <select class="form-select fill" id="supplier"></select>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="input-block mb-0">
                            <label class="form-label mb-1 fw-semibold text-secondary" style="font-size: 12px;">Status</label>
                            <select class="form-select fill" id="status" style="height: 40px; font-size: 13px;">
                                <option value="" selected>Semua Status</option>
                                <option value="1">Belum Terbayar</option>
                                <option value="3">Menunggu Tanda Terima</option>
                                <option value="2">Terbayar</option>
                                <option value="5">Ditolak</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-6 col-md-6">
                        <div class="input-block mb-0">
                            <label class="form-label mb-1 fw-semibold text-secondary" style="font-size: 12px;">Dari Tanggal</label>
                            <input type="date" class="form-control" id="start_date" style="height: 40px; font-size: 13px;">
                        </div>
                    </div>
                    <div class="col-6 col-md-6">
                        <div class="input-block mb-0">
                            <label class="form-label mb-1 fw-semibold text-secondary" style="font-size: 12px;">Sampai Tanggal</label>
                            <input type="date" class="form-control" id="end_date" style="height: 40px; font-size: 13px;">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card Aksi & Ringkasan -->
        <div class="col-12 col-xl-4 col-lg-5">
            <div class="card p-3 h-100 shadow-sm border-0 d-flex flex-column justify-content-between" style="border: 1px solid #e2e8f0 !important; border-radius: 12px;">
                <div>
                    <div class="d-flex align-items-center justify-content-between border-bottom pb-2 mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <div style="width: 28px; height: 28px; border-radius: 6px; background: #ecfdf5; display: flex; align-items: center; justify-content: center; color: #059669;">
                                <i class="fe fe-activity" style="font-size: 13px;"></i>
                            </div>
                            <h6 class="card-title mb-0 fw-bold text-dark" style="font-size: 14px;">Ringkasan & Aksi</h6>
                        </div>
                        <span id="jumlah_terpilih" class="badge jumlah_terpilih border cursor-pointer" style="background: #eff6ff; color: #2563eb; border-color: #bfdbfe !important; font-size: 11px; font-weight: 600; padding: 5px 10px; border-radius: 20px;" title="Klik untuk reset pilihan">
                            0 Selected <i class="fe fe-refresh-cw ms-1"></i>
                        </span>
                    </div>

                    <!-- Mini KPI Summary -->
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="rounded-3" style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 8px 12px;">
                                <div class="d-flex align-items-center gap-1.5">
                                    <i class="fe fe-file-text text-primary" style="font-size: 13px;"></i>
                                    <small class="text-muted fw-semibold" style="font-size: 11px;">Jml. Invoice</small>
                                </div>
                                <div class="fw-bold text-dark fs-15 mt-1" id="totalInvoice">0</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="rounded-3" style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 8px 12px;">
                                <div class="d-flex align-items-center gap-1.5">
                                    <i class="fe fe-dollar-sign text-danger" style="font-size: 13px;"></i>
                                    <small class="text-muted fw-semibold" style="font-size: 11px;">Total Hutang</small>
                                </div>
                                <div class="fw-bold text-danger fs-14 mt-1 text-truncate" id="totalHutang" title="Rp 0">Rp 0</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="row g-2 mt-auto">
                    <div class="col-12 col-sm-6 col-lg-6">
                        <button type="button" class="btn btn-print w-100 d-inline-flex align-items-center justify-content-center fw-semibold" style="height: 40px; font-size: 13px; border-radius: 8px;">
                            <i class="fe fe-printer me-2" style="font-size: 14px;"></i>
                            <span>Print Hutang</span>
                        </button>
                    </div>
                    @if ($akses->firstWhere('name', 'Hutang') && in_array('others', $akses->firstWhere('name', 'Hutang')->akses))
                        <div class="col-12 col-sm-6 col-lg-6">
                            <button type="button" class="btn btn-create w-100 d-inline-flex align-items-center justify-content-center fw-semibold text-white" style="height: 40px; font-size: 13px; border-radius: 8px;">
                                <i class="fe fe-file-plus me-2 text-white" style="font-size: 14px;"></i>
                                <span class="text-white">Buat Tanda Terima</span>
                            </button>
                        </div>
                    @endif
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

@if(Route::is(['bom']))
<!-- Filter Pencarian -->
<div class="card mb-4 border-0 mt-3" style="background: linear-gradient(145deg, #ffffff, #f8fafc); box-shadow: 0 4px 15px rgba(0,0,0,0.03); border-radius: 12px;">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap align-items-end gap-3">
            <div style="flex: 1 1 220px; min-width: 200px; max-width: 100%;">
                <label class="form-label text-muted fw-semibold mb-2" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;"><i class="fe fe-box me-1"></i> Produk</label>
                <select class="form-select" id="filter_product_id" style="border-radius: 8px; font-weight: 600; color: #1e293b; font-size: 14px; height: 42px; box-shadow: 0 1px 2px rgba(0,0,0,0.02);"></select>
            </div>
            <div style="flex: 1 1 220px; min-width: 200px; max-width: 100%;">
                <label class="form-label text-muted fw-semibold mb-2" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;"><i class="fe fe-layers me-1"></i> Bahan Mentah</label>
                <select class="form-select" id="filter_supplies_id" style="border-radius: 8px; font-weight: 600; color: #1e293b; font-size: 14px; height: 42px; box-shadow: 0 1px 2px rgba(0,0,0,0.02);"></select>
            </div>
            <div class="ms-md-auto">
                <button type="button" class="btn btn-light btn-clear" style="padding: 0 24px; font-weight: 600; border-radius: 8px; height: 42px; display: flex; align-items: center; justify-content: center; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; color: #475569; transition: all 0.2s ease;">
                    <i class="fe fe-refresh-cw me-2" style="font-size: 14px;"></i> Clear
                </button>
            </div>
        </div>
    </div>
</div>
<!-- /Filter Pencarian -->
@endif

@if(Route::is(['production']))
<!-- Filter Pencarian -->
<div class="card mb-4 border-0 mt-3" style="background: linear-gradient(145deg, #ffffff, #f8fafc); box-shadow: 0 4px 15px rgba(0,0,0,0.03); border-radius: 12px;">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap align-items-end gap-3">
            <div style="flex: 1 1 220px; min-width: 200px; max-width: 100%;">
                <label class="form-label text-muted fw-semibold mb-2" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;"><i class="fe fe-calendar me-1"></i> Tanggal</label>
                <input type="date" class="form-control fill" id="date_production" style="border-radius: 8px; font-weight: 600; color: #1e293b; font-size: 14px; height: 42px; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
            </div>
            <div style="flex: 1 1 220px; min-width: 200px; max-width: 100%;">
                <label class="form-label text-muted fw-semibold mb-2" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;"><i class="fe fe-activity me-1"></i> Status</label>
                <select class="form-select fill" id="status" style="border-radius: 8px; font-weight: 600; color: #1e293b; font-size: 14px; height: 42px; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
                    <option value="">Semua</option>
                    <option value="1">Pending</option>
                    <option value="2">Berhasil</option>
                    <option value="4">Menunggu Batal</option>
                    <option value="3">Tolak</option>
                </select>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2 ms-md-auto">
                <a href="javascript:void(0);" class="btn btn-outline-primary LihatfotoProduksi d-inline-flex align-items-center justify-content-center gap-2" style="padding: 0 20px; font-weight: 600; border-radius: 8px; height: 42px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); transition: all 0.2s ease;" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Lihat Bukti Produksi">
                    <i class="fe fe-image" style="font-size: 14px;"></i> Lihat Gambar
                </a>
                <button type="button" class="btn btn-light btn-clear" style="padding: 0 24px; font-weight: 600; border-radius: 8px; height: 42px; display: flex; align-items: center; justify-content: center; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; color: #475569; transition: all 0.2s ease;">
                    <i class="fe fe-refresh-cw me-2" style="font-size: 14px;"></i> Clear
                </button>
            </div>
        </div>
    </div>
</div>
<!-- /Filter Pencarian -->
@endif
@if (Route::is(['externalApplication']))
    <!-- Filter Pencarian -->
    <div class="row">
        <div class="col-12">
            <div class="card p-3">
                <div class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <div class="input-block">
                            <label>Nama Aplikasi</label>
                            <input type="text" class="form-control" id="filter_application_name"
                                placeholder="Cari nama aplikasi">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="input-block">
                            <label>Status</label>
                            <select class="form-select" id="filter_application_status">
                                <option value="">Semua</option>
                                <option value="active">Aktif</option>
                                <option value="disabled">Nonaktif</option>
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
    <!-- /Filter Pencarian -->
@endif

@if (Route::is(['externalApiLog']))
    <!-- Filter Pencarian -->
    <div class="row">
        <div class="col-12">
            <div class="card p-3">
                <div class="row g-2 align-items-end">
                    <div class="col-xl-2 col-md-4">
                        <div class="input-block">
                            <label>Aplikasi</label>
                            <select class="form-select" id="filter_log_application">
                                <option value="">Semua</option>
                                @foreach ($applications ?? [] as $app)
                                    <option value="{{ $app->external_application_id }}">{{ $app->application_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <div class="input-block">
                            <label>Metode</label>
                            <select class="form-select" id="filter_log_method">
                                <option value="">Semua</option>
                                <option value="GET">GET</option>
                                <option value="POST">POST</option>
                                <option value="PUT">PUT</option>
                                <option value="PATCH">PATCH</option>
                                <option value="DELETE">DELETE</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <div class="input-block">
                            <label>Kode Status</label>
                            <select class="form-select" id="filter_log_status">
                                <option value="">Semua</option>
                                <option value="2xx">2xx — Berhasil</option>
                                <option value="4xx">4xx — Kesalahan Klien</option>
                                <option value="5xx">5xx — Kesalahan Server</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <div class="input-block">
                            <label>Endpoint</label>
                            <input type="text" class="form-control" id="filter_log_endpoint" placeholder="/api/v1/...">
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <div class="input-block">
                            <label>Dari</label>
                            <input type="date" class="form-control" id="filter_log_start_date">
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <div class="input-block">
                            <label>Sampai</label>
                            <input type="date" class="form-control" id="filter_log_end_date">
                        </div>
                    </div>
                    <div class="col-12 text-end">
                        <a class="btn btn-primary btn-filter me-2">Terapkan</a>
                        <a class="btn btn-outline-secondary btn-clear">Clear</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Filter Pencarian -->
@endif

@if (Route::is(['externalApiStatus']))
    {{--
        Seluruh data tabel ini sudah dimuat sekali lewat AJAX (tidak
        dipaginasi server), jadi filter di sini bekerja langsung di sisi
        klien (lihat Status.js) — berubah, tabel langsung tersaring, tanpa
        tombol "Terapkan". Pilihan Versi/Kelompok/Metode diisi JS dari data
        yang benar-benar ada, jadi versi/kelompok/metode baru otomatis
        muncul sebagai pilihan tanpa menyentuh berkas ini.
    --}}
    <!-- Filter Pencarian -->
    <div class="row">
        <div class="col-12">
            <div class="card p-3">
                <div class="row g-2 align-items-end">
                    <div class="col-xl-2 col-md-4">
                        <div class="input-block">
                            <label>Versi</label>
                            <select class="form-select" id="filter_status_version">
                                <option value="">Semua</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <div class="input-block">
                            <label>Kelompok</label>
                            <select class="form-select" id="filter_status_group">
                                <option value="">Semua</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <div class="input-block">
                            <label>Metode</label>
                            <select class="form-select" id="filter_status_method">
                                <option value="">Semua</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <div class="input-block">
                            <label>Endpoint Aktif</label>
                            <select class="form-select" id="filter_status_active">
                                <option value="">Semua</option>
                                <option value="1">Aktif</option>
                                <option value="0">Nonaktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <div class="input-block">
                            <label>Dokumentasi Publik</label>
                            <select class="form-select" id="filter_status_public">
                                <option value="">Semua</option>
                                <option value="1">Tampil</option>
                                <option value="0">Tersembunyi</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <a class="btn btn-outline-secondary btn-clear w-100">Clear</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Filter Pencarian -->
@endif

@if(Route::is(['salesOrder']))
{{-- Tab Pengiriman: Tanggal + Status + Reset --}}
<div class="profit-menu card sales-order-filter">
    <div class="row card-body pb-0 g-3 align-items-end">
        <div class="col-xl-5 col-lg-5 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Tanggal</label>
                <div class="cal-icon cal-icon-info">
                    <input type="text" class="form-control" id="so_filter_date"
                        placeholder="Pilih rentang / hari" readonly>
                </div>
            </div>
        </div>
        <div class="col-xl-5 col-lg-5 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Status</label>
                <select class="form-select" id="so_filter_status">
                    <option value="">Semua Status</option>
                    <option value="1">Pending</option>
                    <option value="2">Diterima</option>
                    <option value="3">Ditolak</option>
                    <option value="4">Dijadwalkan</option>
                    <option value="5">Belum Terkirim</option>
                    <option value="6">Sudah Terkirim</option>
                    <option value="7">Dibatalkan</option>
                </select>
            </div>
        </div>
        <div class="col-xl-2 col-lg-2 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <a href="javascript:void(0);" class="btn btn-outline-secondary w-100 btn-clear-so-filter d-flex align-items-center justify-content-center gap-1.5"
                    style="height: 42px; border-radius: 8px; font-size: 13px; font-weight: 600;" title="Reset Filter">
                    <i class="fe fe-rotate-ccw" style="font-size: 13px;"></i>
                    <span>Reset</span>
                </a>
            </div>
        </div>
    </div>
</div>
{{-- Tab Pengembalian: Tanggal + Status + Tipe + Reset --}}
<div class="profit-menu card customer-return-filter" style="display:none;">
    <div class="row card-body pb-0 g-3 align-items-end">
        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Tanggal</label>
                <div class="cal-icon cal-icon-info">
                    <input type="text" class="form-control" id="cr_filter_date"
                        placeholder="Pilih rentang / hari" readonly>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Status</label>
                <select class="form-select" id="cr_filter_status">
                    <option value="">Semua Status</option>
                    <option value="1">Pending</option>
                    <option value="2">Diterima</option>
                    <option value="3">Ditolak</option>
                </select>
            </div>
        </div>
        <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Tipe</label>
                <select class="form-select" id="cr_filter_type">
                    <option value="">Semua Tipe</option>
                    <option value="product">Produk Jadi</option>
                    <option value="supply">Bahan Mentah</option>
                    <option value="mixed">Campuran</option>
                </select>
            </div>
        </div>
        <div class="col-xl-2 col-lg-2 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <a href="javascript:void(0);" class="btn btn-outline-secondary w-100 btn-clear-cr-filter d-flex align-items-center justify-content-center gap-1.5"
                    style="height: 42px; border-radius: 8px; font-size: 13px; font-weight: 600;" title="Reset Filter">
                    <i class="fe fe-rotate-ccw" style="font-size: 13px;"></i>
                    <span>Reset</span>
                </a>
            </div>
        </div>
    </div>
</div>
@endif

@if(Route::is(['stockTransfer']))
{{-- Filter urutan: Tanggal (3) + Gudang Dari (5) + Status (2) + Reset (2) = 12 Kolom Penuh --}}
<div class="profit-menu card stock-transfer-filter">
    <div class="row card-body pb-0 g-3 align-items-end">
        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Tanggal</label>
                <div class="cal-icon cal-icon-info">
                    <input type="text" class="form-control" id="st_filter_date"
                        placeholder="Pilih rentang / hari" readonly>
                </div>
            </div>
        </div>
        <div class="col-xl-5 col-lg-5 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Gudang Dari</label>
                <select class="form-select" id="st_filter_from_warehouse">
                    <option value="">Semua Gudang Asal</option>
                </select>
            </div>
        </div>
        <div class="col-xl-2 col-lg-2 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Status</label>
                <select class="form-select" id="st_filter_status">
                    <option value="">Semua Status</option>
                    <option value="1">Pending</option>
                    @php
                        // Fase request eceran hanya di gudang utama — render Blade (tanpa hide JS / tanpa blink).
                        $stFilterAw = $activeWarehouse ?? null;
                        $stFilterIsMain = $stFilterAw
                            && isset($stFilterAw->type)
                            && (int) ($stFilterAw->type->is_main_warehouse ?? 0) === 1;
                    @endphp
                    @if ($stFilterIsMain)
                    <option value="requested">Requested (tunggu QC)</option>
                    <option value="need_approval">Need Approval (tunggu Ops)</option>
                    @endif
                    <option value="2">Kirim</option>
                    <option value="4">Terkirim</option>
                    <option value="3">Cancel</option>
                    <option value="5">Cancel Kirim</option>
                </select>
            </div>
        </div>
        <div class="col-xl-2 col-lg-2 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <a href="javascript:void(0);" class="btn btn-outline-secondary w-100 btn-clear-st-filter d-flex align-items-center justify-content-center gap-1.5"
                    style="height: 42px; border-radius: 8px; font-size: 13px; font-weight: 600;" title="Reset Filter">
                    <i class="fe fe-rotate-ccw" style="font-size: 13px;"></i>
                    <span>Reset</span>
                </a>
            </div>
        </div>
    </div>
</div>
@endif
