@if(!Route::is(['companies']))
<!-- Search Filter -->
<div id="filter_inputs" class="card filter-card">
    <div class="card-body pb-0">
        <div class="row">
            <div class="col-sm-6 col-md-3">
                <div class="input-block mb-3">
                    <label>Name</label>
                    <input type="text" class="form-control">
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="input-block mb-3">
                    <label>Email</label>
                    <input type="text" class="form-control">
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="input-block mb-3">
                    <label>Phone</label>
                    <input type="text" class="form-control">
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /Search Filter -->
@endif


@if(Route::is(['companies']))
<!-- Search Filter -->
<div id="filter_inputs" class="card filter-card">
    <div class="card-body pb-0">
        <div class="row">
            <div class="col-sm-6 col-md-3">
                <div class="input-block mb-3">
                    <label>Name</label>
                    <input type="text" class="form-control">
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="input-block mb-3">
                    <label>Email</label>
                    <input type="text" class="form-control">
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="input-block mb-3">
                    <label>Phone</label>
                    <input type="text" class="form-control">
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /Search Filter -->
@endif