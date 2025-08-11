<!-- Page Header -->
@if(!Route::is(['companies','subscription','packages','plans-list']))
<div class="page-header">

    <div class="content-page-header">
        <h5>{{ $title }}</h5>
        @if (Route::is(['custom-filed', 'profit-loss-list', 'sales-return-report', 'stock-report']))
            <div class="page-content">
        @endif
        <div class="list-btn">
            <ul class="filter-list">
                @if (Route::is(['category']))
                    <li>
                        <a class="btn btn-primary btnAdd" href="javascript:void(0);" data-bs-toggle="modal"
                            data-bs-target="#add_category"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Add
                            Category</a>
                    </li>
                @endif
                @if (Route::is(['unit']))
                    <li>
                        <a class="btn btn-primary btnAdd" href="javascript:void(0);" data-bs-toggle="modal"
                            data-bs-target="#add_unit"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Add
                            Units</a>
                    </li>
                @endif
                @if (Route::is(['variant']))
                    <li>
                        <a class="btn btn-primary btnAdd" href="javascript:void(0);" data-bs-toggle="modal"
                            data-bs-target="#add_variant"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Add
                            Variants</a>
                    </li>
                @endif
            </ul>
        </div>
        @if (Route::is(['custom-filed', 'profit-loss-list', 'sales-return-report', 'stock-report']))
    </div>
    @endif
</div>

</div>
<!-- /Page Header -->
@endif

@if (Route::is(['companies','subscription','plans-list','packages']))
    
<!-- Page Header -->
<div class="page-header">
    <div class="content-page-header">
        <h5>{{$title}}</h5>
        <div class="page-content">
            <div class="list-btn">
                <ul class="filter-list">
                    
                </ul>
            </div>
        </div>
    </div>
</div>
<!-- /Page Header -->
@endif
