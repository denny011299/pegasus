@if(Route::is(['profitLoss']))
<!-- Filter Pencarian -->
<div class="profit-menu card">
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
            <a class="btn btn-primary loss btn-filter" href="#">
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
        <div class="row card-body pb-0">
            <div class="col-lg-3 col-md-6 col-sm-12">
                <div class="input-block mb-3">
                    <label>Dari</label>
                    <div class="cal-icon cal-icon-info">
                        <input type="text" class="datetimepicker form-control " id="start_date" placeholder="01-01-2025">
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-12">
                <div class="input-block mb-3">
                    <label>Sampai</label>
                    <div class="cal-icon cal-icon-info">
                        <input type="text" class="datetimepicker form-control" id="end_date" placeholder="31-01-2025">
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 col-sm-0"></div>
            <div class="col-lg-2 col-md-6 col-sm-12">
                <a class="btn btn-primary loss btn-filter" href="#">
                    Jalankan
                </a>
            </div>
        </div>
    </div>
    <!-- /Filter Pencarian -->
@endif

@if(Route::is(['reportBahanBaku']))
<!-- Filter Pencarian -->
<div class="profit-menu card">
    <div class="row card-body pb-0">
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Dari</label>
                <div class="cal-icon cal-icon-info">
                    <input type="text" class="datetimepicker form-control" id="start_date">
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Sampai</label>
                <div class="cal-icon cal-icon-info">
                    <input type="text" class="datetimepicker form-control" id="end_date">
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-0"></div>
        <div class="col-lg-2 col-md-6 col-sm-12">
            <a class="btn btn-primary loss btn-filter"  href="#">
                Jalankan
            </a>
        </div>
    </div>
</div>
<!-- /Filter Pencarian -->
@endif

@if(Route::is(['reportProduksi']))
<!-- Filter Pencarian -->
<div class="profit-menu card">
    <div class="row card-body pb-0">
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Dari</label>
                <div class="cal-icon cal-icon-info">
                    <input type="text" class="datetimepicker form-control" id="start_date" placeholder="01 Jan 2023">
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Sampai</label>
                <div class="cal-icon cal-icon-info">
                    <input type="text" class="datetimepicker form-control" id="end_date" placeholder="31 Mar 2023">
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-0"></div>
        <div class="col-lg-3 col-md-6 col-sm-12 pt-4 text-end">
            <a class="btn btn-outline-secondary btn-clear">
                Clear
            </a>
            <a class="btn btn-primary btn-filter">
                Jalankan
            </a>
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

@if(Route::is(['tt']))
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
        
        {{-- New --}}
        {{-- <div class="col-12 col-md-6">
            <div class="card p-3">
                <div class="row g-2 align-items-center">
                    <!-- Supplier -->
                    <div class="col-md row-supplier">
                        <label class="form-label mb-1">Tanda Terima Supplier</label>
                        <select class="form-select" id="select_supplier"></select>
                    </div>
                    <div class="col-md row-rekening">
                        <label class="form-label mb-1">Bank Account</label>
                        <select class="form-select" id="bank_kode"></select>
                    </div>
                     <div class="col-md-auto d-flex align-items-end pt-4">
                        <button class="btn btn-primary w-100" id="generateTandaTerima">Buat Tanda Terima</button>
                    </div>
                </div>
            </div>
        </div> --}}
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

@if(Route::is(['payReceive']))
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
        
        <div class="col-12 col-md-12 mb-4">
            <div class="card p-3">
                <div class="row g-2 align-items-center">
                    <!-- Supplier -->
                    <div class="col-md-3 row-supplier">
                        <label class="form-label mb-1">Filter</label>
                         <div class="input-block mb-3">
                            <label>Bank Account</label>
                            <select class="form-select fill" id="bank_kode"></select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-1">  </label>
                         <div class="input-block mb-3">
                            <label>Status</label>
                            <select class="form-select fill" id="status">
                                <option value="" selected>Semua</option>
                                <option value="1">Belum Terbayar</option>
                                <option value="3">Menunggu Tanda Terima</option>
                                <option value="2">Terbayar</option>
                                <option value="5">Ditolak</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-1">  </label>
                         <div class="input-block mb-3">
                            <label>Supplier</label>
                            <select class="form-select fill" id="supplier">
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3 text-end">
                        <label class="form-label mb-1">Tanda Terima</label>
                         <div class="input-block mb-3">
                            <label id="jumlah_terpilih" class="jumlah_terpilih" style="cursor:pointer">0 Selected</label> <i  class="fe fe-refresh-ccw ms-2 jumlah_terpilih"  style="cursor:pointer"></i><br>
                            <button class="btn btn-primary btn-create">Buat Tanda Terima</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /Filter Pencarian -->
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