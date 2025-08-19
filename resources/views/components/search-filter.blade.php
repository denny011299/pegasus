@if(Route::is(['profitLoss']))
<!-- Search Filter -->
<div class="profit-menu card">
    <div class="row card-body pb-0">
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>From</label>
                <div class="cal-icon cal-icon-info">
                    <input type="text" class="datetimepicker form-control" placeholder="01 Jan 2023">
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>To</label>
                <div class="cal-icon cal-icon-info">
                    <input type="text" class="datetimepicker form-control" placeholder="31 Mar 2023">
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-0"></div>
        <div class="col-lg-2 col-md-6 col-sm-12">
            <a class="btn btn-primary loss" href="#">
                Run</a>
        </div>
    </div>
</div>
<!-- /Search Filter -->
@endif

@if(Route::is(['SuppliesReturn']))
<!-- Search Filter -->
<div class="profit-menu card">
    <div class="row card-body pb-0">
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>From</label>
                <div class="cal-icon cal-icon-info">
                    <input type="text" class="datetimepicker form-control" placeholder="01 Jan 2023">
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>To</label>
                <div class="cal-icon cal-icon-info">
                    <input type="text" class="datetimepicker form-control" placeholder="31 Mar 2023">
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-0"></div>
        <div class="col-lg-2 col-md-6 col-sm-12">
            <a class="btn btn-primary loss" href="#">
                Run</a>
        </div>
    </div>
</div>
<!-- /Search Filter -->
@endif

@if(Route::is(['reportBahanBaku']))
<!-- Search Filter -->
<div class="profit-menu card">
    <div class="row card-body pb-0">
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>From</label>
                <div class="cal-icon cal-icon-info">
                    <input type="text" class="datetimepicker form-control" placeholder="01 Jan 2023">
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>To</label>
                <div class="cal-icon cal-icon-info">
                    <input type="text" class="datetimepicker form-control" placeholder="31 Mar 2023">
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-0"></div>
        <div class="col-lg-2 col-md-6 col-sm-12">
            <a class="btn btn-primary loss" href="#">
                Run</a>
        </div>
    </div>
</div>
<!-- /Search Filter -->
@endif

@if(Route::is(['reportProduksi']))
<!-- Search Filter -->
<div class="profit-menu card">
    <div class="row card-body pb-0">
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>From</label>
                <div class="cal-icon cal-icon-info">
                    <input type="text" class="datetimepicker form-control" placeholder="01 Jan 2023">
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>To</label>
                <div class="cal-icon cal-icon-info">
                    <input type="text" class="datetimepicker form-control" placeholder="31 Mar 2023">
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-0"></div>
        <div class="col-lg-2 col-md-6 col-sm-12">
            <a class="btn btn-primary loss" href="#">
                Run</a>
        </div>
    </div>
</div>
<!-- /Search Filter -->
@endif


@if(Route::is(['inwardOutward']))
<!-- Search Filter -->
<div class="container mt-3">
    <div class="card p-3">
        <div class="row g-2 align-items-center">
            <!-- Date Range -->
            <div class="col-md">
                <label class="form-label mb-1">Choose Date</label>
                <input type="text" class="form-control" id="filter_io_date" placeholder="Select date range">
            </div>
            <!-- Category -->
            <div class="col-md">
                <label class="form-label mb-1">Category</label>
                <select class="form-select" id="filter_io_category"></select>
            </div>
            <!-- Products -->
            <div class="col-md">
                <label class="form-label mb-1">Products</label>
                <select class="form-select" id="filter_io_products"></select>
            </div>

            <!-- Units -->
            <div class="col-md">
                <label class="form-label mb-1">Units</label>
                <select class="form-select" id="filter_io_units"></select>
            </div>

            <!-- Button -->
            <div class="col-md-auto d-flex align-items-end pt-4">
                <button class="btn btn-primary w-100" id="generateReport">Generate Report</button>
            </div>
        </div>
    </div>
</div>
<!-- /Search Filter -->
@endif