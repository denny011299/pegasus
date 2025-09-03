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
                        <a class="btn btn-primary btnAdd" href="javascript:void(0);"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Add
                            Category</a>
                    </li>
                @endif
                @if (Route::is(['unit']))
                    <li>
                        <a class="btn btn-primary btnAdd"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Add
                            Units</a>
                    </li>
                @endif
                @if (Route::is(['variant']))
                    <li>
                        <a class="btn btn-primary btnAdd"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Add
                            Variants</a>
                    </li>
                @endif
                @if (Route::is(['product']))
                    <li>
                        <a class="btn btn-primary btnAdd"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Add
                            Products</a>
                    </li>
                @endif
                @if (Route::is(['supplies']))
                    <li>
                        <a class="btn btn-primary btnAdd"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Add
                            Supplies</a>
                    </li>
                @endif
                @if (Route::is(['salesOrder']))
                    <li>
                        <a class="btn btn-primary btnAdd"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Add
                            Sales Order</a>
                    </li>
                @endif
                @if (Route::is(['salesOrderDetail']))
                    <li>
                        <a class="btn btn-primary btnBack"><i class="fa fa-chevron-left me-2" aria-hidden="true"></i>Back</a>
                    </li>
                @endif
                @if (Route::is(['purchaseOrder']))
                    <li>
                        <a class="btn btn-primary btnAdd"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Add
                            Purchase Order</a>
                    </li>
                @endif
                @if (Route::is(['purchaseOrderDetail']))
                    <li>
                        <a class="btn btn-primary btnBack"><i class="fa fa-chevron-left me-2" aria-hidden="true"></i>Back</a>
                    </li>
                @endif
                @if (Route::is(['customer']))
                    <li>
                        <a class="btn btn-import" href="javascript:void(0);"><span><i
                                    class="fe fe-check-square me-2"></i>Import Customer</span></a>
                    </li>
                    <li>
                        <a class="btn btn-primary" href="/insertCustomer"><i class="fa fa-plus-circle me-2"
                                aria-hidden="true"></i>Add Customer</a>
                    </li>
                @endif
                @if (Route::is(['role']))
                    <li>
                        <a class="btn btn-primary btnAdd" href="#" data-bs-toggle="modal"
                            data-bs-target="#add_role"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Add
                            Roles</a>
                    </li>
                @endif
                @if (Route::is(['stockOpname']))
                    <li>
                        <a class="btn btn-primary" href="/detailStockOpname/1" ><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Add
                            Stock Opname</a>
                    </li>
                @endif
                @if (Route::is(['supplier']))
                    <li>
                        <a class="btn btn-import" href="javascript:void(0);"><span><i
                                    class="fe fe-check-square me-2"></i>Import Supplier</span></a>
                    </li>
                    <li>
                        <a class="btn btn-primary" href="/insertSupplier"><i class="fa fa-plus-circle me-2"
                                aria-hidden="true"></i>Add Supplier</a>
                    </li>
                @endif
                @if (Route::is(['cash']))
                    <li>
                        <a class="btn btn-primary btnAdd"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Add
                            Cash</a>
                    </li>
                @endif
                @if (Route::is(['pettyCash']))
                    <li>
                        <a class="btn btn-primary btnAdd"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Add
                            Petty Cash</a>
                    </li>
                @endif
                @if (Route::is(['staff']))
                    <li>
                        <a class="btn btn-primary" href="/insertStaff"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Add
                            Staff</a>
                    </li>
                @endif
                  @if (Route::is(['bom']))
                    <li>
                        <a class="btn btn-primary btnAdd" ><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Add
                            Bill of Materials</a>
                    </li>
                @endif
                @if (Route::is(['production']))
                    <li>
                        <a class="btn btn-primary btnAdd" ><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>
                            Add Production</a>
                    </li>
                @endif
                @if (Route::is(['staffDetail']))
                    <li>
                        <a class="btn btn-outline-secondary btnBack"><i class="fa fa-chevron-left me-2" aria-hidden="true"></i>Back</a>
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
