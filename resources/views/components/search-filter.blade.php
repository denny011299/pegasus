@if(Route::is(['profitLoss']))
<!-- Filter Pencarian -->
<div class="profit-menu card">
    <div class="row card-body pb-0">
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Dari</label>
                <div class="cal-icon cal-icon-info">
                    <input type="text" class="datetimepicker form-control" placeholder="01 Jan 2023">
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Sampai</label>
                <div class="cal-icon cal-icon-info">
                    <input type="text" class="datetimepicker form-control" placeholder="31 Mar 2023">
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-0"></div>
        <div class="col-lg-2 col-md-6 col-sm-12">
            <a class="btn btn-primary loss" href="#">
                Jalankan
            </a>
        </div>
    </div>
</div>
<!-- /Filter Pencarian -->
@endif

@if(Route::is(['SuppliesReturn']))
<!-- Filter Pencarian -->
<div class="profit-menu card">
    <div class="row card-body pb-0">
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Dari</label>
                <div class="cal-icon cal-icon-info">
                    <input type="text" class="datetimepicker form-control" placeholder="01 Jan 2023">
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Sampai</label>
                <div class="cal-icon cal-icon-info">
                    <input type="text" class="datetimepicker form-control" placeholder="31 Mar 2023">
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-0"></div>
        <div class="col-lg-2 col-md-6 col-sm-12">
            <a class="btn btn-primary loss" href="#">
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
                    <input type="text" class="datetimepicker form-control" placeholder="01 Jan 2023">
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Sampai</label>
                <div class="cal-icon cal-icon-info">
                    <input type="text" class="datetimepicker form-control" placeholder="31 Mar 2023">
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-0"></div>
        <div class="col-lg-2 col-md-6 col-sm-12">
            <a class="btn btn-primary loss" href="#">
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
                    <input type="text" class="datetimepicker form-control" placeholder="01 Jan 2023">
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Sampai</label>
                <div class="cal-icon cal-icon-info">
                    <input type="text" class="datetimepicker form-control" placeholder="31 Mar 2023">
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-0"></div>
        <div class="col-lg-2 col-md-6 col-sm-12">
            <a class="btn btn-primary loss" href="#">
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