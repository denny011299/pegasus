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
