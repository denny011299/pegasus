<div class="page-header">
    <div class="add-item d-flex">
        <div class="page-title">
            <h4>{{ $title }}</h4>
            <h6>{{ $li_1 }}</h6>
        </div>
    </div>
    <ul class="table-top-head">
        @unless (Route::is([
                'salesDetail',
                'staff',
                'purchaseDetail',
                'stores',
                'bundling',
                'promo',
                'POSSession',
                'POSTransaction',
                'POSSessionDetail',
                'addProduct',
                'editProduct',
                'stockOpname',
                'sales',
                'purchase',
            ]))
        @endunless
        {{--
            <li>
                <a data-bs-toggle="tooltip" data-bs-placement="top" title="Pdf"><img
                        src="{{ URL::asset('/build/img/icons/pdf.svg') }}" alt="img"></a>
            </li>
            <li>
                <a data-bs-toggle="tooltip" data-bs-placement="top" title="Excel"><img
                        src="{{ URL::asset('/build/img/icons/excel.svg') }}" alt="img"></a>
            </li>
        @endunless
        @unless (Route::is(['supplierDetail']))
            <li>
                <a data-bs-toggle="tooltip" data-bs-placement="top" title="Collapse" id="collapse-header"><i
                        data-feather="chevron-up" class="feather-chevron-up"></i></a>
            </li>
        @endunless --}}
    </ul>
    @if (Route::is(['category']))
        <div class="page-btn">
            <a href="#" class="btn btn-added" data-bs-toggle="modal" data-bs-target="#add-category"><i
                    data-feather="plus-circle" class="me-2"></i>Add New Category</a>
        </div>
    @endif
    @if (Route::is(['products']))
        <div class="page-btn">
            <a href="{{ url('admin/edit-product/') }}" class="btn btn-added">
                <i data-feather="plus-circle" class="me-2"></i>{{ $li_3 }}
            </a>
        </div>
    @endif
    @if (Route::is(['sales']))
        <div class="page-btn">
            <a href="#" class="btn btn-added" data-bs-toggle="modal" data-bs-target="#add-sales-new"
                id="btn_add_sales"><i data-feather="plus-circle" class="me-2"></i>{{ $li_2 }}</a>
        </div>
    @endif
    @if (Route::is(['promo']))
        <div class="page-btn">
            <a href="#" class="btn btn-added" data-bs-toggle="modal" data-bs-target="#add-units"><i
                    data-feather="plus-circle" class="me-2"></i>Buat Promo Baru</a>
        </div>
    @endif
    @if (Route::is(['bundling']))
        <div class="page-btn">
            <a href="#" class="btn btn-added" data-bs-toggle="modal" data-bs-target="#add-units"><i
                    data-feather="plus-circle" class="me-2"></i>Buat Bundling</a>
        </div>
    @endif
    @if (Route::is(['stores']))
        <div class="page-btn">
            <a href="#" class="btn btn-added btnAdd"><i data-feather="plus-circle" class="me-2"></i>Tambahkan
                Toko</a>
        </div>
    @endif
    @if (Route::is(['customers']))
        <div class="page-btn">
            <a href="#" class="btn btn-added" data-bs-toggle="modal" data-bs-target="#add-units"><i
                    data-feather="plus-circle" class="me-2"></i>Tambah Customer</a>
        </div>
    @endif
    @if (Route::is(['staff']))
        <div class="page-btn">
            <a href="/admin/addStaff" class="btn btn-added"><i data-feather="plus-circle" class="me-2"></i>Tambah
                Staff</a>
        </div>
    @endif
    @if (Route::is(['units']))
        <div class="page-btn">
            <a href="#" class="btn btn-added" ><i
                    data-feather="plus-circle" class="me-2"></i> {{ $li_2 }}</a>
        </div>
    @endif
    @if (Route::is(['brand']))
        <div class="page-btn">
            <a href="#" class="btn btn-added" data-bs-toggle="modal" data-bs-target="#add-brand"><i
                    data-feather="plus-circle" class="me-2"></i>Add New Brand</a>
        </div>
    @endif
    @if (Route::is(['warehouse']))
        <div class="page-btn">
            <a href="#" class="btn btn-added btnAdd"><i data-feather="plus-circle"
                    class="me-2"></i>{{ $li_2 }}</a>
        </div>
    @endif
    @if (Route::is(['variant']))
        <div class="page-btn">
            <a href="#" class="btn btn-added btnAdd"><i data-feather="plus-circle" class="me-2"></i>
                {{ $li_2 }}</a>
        </div>
    @endif
    @if (Route::is(['suppliers']))
        <div class="page-btn">
            <a href="#" class="btn btn-added btnAdd"><i data-feather="plus-circle" class="me-2"></i>Add New
                Supplier</a>
        </div>
    @endif
    @if (Route::is(['ProductIssues']))
        <div class="page-btn">
            <a href="#" class="btn btn-added btnAdd"><i data-feather="plus-circle" class="me-2"></i>Add New
                Product Issue</a>
        </div>
    @endif
    @if (Route::is(['supplierDetail']))
        <div class="page-btn mb-3">
            <a href="/admin/suppliers" class="btn btn-secondary"><i data-feather="arrow-left" class="me-2"></i>Kembali
                ke daftar supplier</a>
        </div>
    @endif
    @if (Route::is(['inventaris']))
        <div class="page-btn">
            <a href="#" class="btn btn-added btnAdd"><i data-feather="plus-circle"
                    class="me-2"></i>{{ $li_2 }}</a>
        </div>
    @endif
    @if (Route::is(['categoryInventory']))
        <div class="page-btn">
            <a href="#" class="btn btn-added btnAdd"><i data-feather="plus-circle"
                    class="me-2"></i>{{ $li_2 }}</a>
        </div>
    @endif
    @if (Route::is(['workOrder']))
        <div class="page-btn">
            <a href="#" class="btn btn-added btnAdd"><i data-feather="plus-circle"
                    class="me-2"></i>{{ $li_2 }}</a>
        </div>
    @endif
    @if (Route::is(['categoryCoa']))
    <div class="page-btn">
        <a href="#" class="btn btn-added btnAdd"><i data-feather="plus-circle"
            class="me-2"></i>{{ $li_2 }}</a>
        </div>
    @endif
    @if (Route::is(['coa']))
        <div class="page-btn">
            <a href="#" class="btn btn-added" data-bs-toggle="modal" data-bs-target="#add-coa"><i
                    data-feather="plus-circle" class="me-2"></i>{{ $li_2 }}</a>
        </div>
    @endif
    @if (Route::is(['subCoa']))
        <div class="page-btn">
            <a href="#" class="btn btn-added" data-bs-toggle="modal" data-bs-target="#add-subcoa"><i
                    data-feather="plus-circle" class="me-2"></i>{{ $li_2 }}</a>
        </div>
    @endif
    @if (Route::is(['journal']))
        <div class="page-btn">
            <a href="#" class="btn btn-added btnAdd"><i data-feather="plus-circle"
                    class="me-2"></i>{{ $li_2 }}</a>
        </div>
    @endif
    @if (Route::is(['users']))
        <div class="page-btn">
            <a href="/admin/UserDetail" class="btn btn-added"><i data-feather="plus-circle" class="me-2"></i>Add
                New
                User</a>
        </div>
    @endif
    @if (Route::is(['rolesPermissions']))
        <div class="page-btn">
            <a href="#" class="btn btn-added btnAdd"><i data-feather="plus-circle" class="me-2"></i> Add New
                Role</a>
        </div>
    @endif
    @if (Route::is(['stockTransfer']))
        <div class="page-btn" id="btn_add_stock_transfer">
            <a href="#" class="btn btn-added" data-bs-toggle="modal" data-bs-target="#add-stock-transfer"><i
                    data-feather="plus-circle" class="me-2"></i>Transfer Stok</a>
        </div>
    @endif
    @if (Route::is(['addProduct']))
        <div class="page-btn mt-2">
            <a href="/admin/products" class="btn btn-secondary"><i data-feather="arrow-left"
                    class="me-2"></i>Kembali
                ke daftar produk</a>
        </div>
    @endif
    @if (Route::is(['editProduct']))
        <div class="page-btn mt-2">
            <a href="/admin/products" class="btn btn-secondary"><i data-feather="arrow-left"
                    class="me-2"></i>Kembali
                ke daftar produk</a>
        </div>
    @endif
    @if (Route::is(['shift']))
        <div class="page-btn">
            <a href="#" class="btn btn-added" data-bs-toggle="modal" data-bs-target="#add-units"><i
                    data-feather="plus-circle" class="me-2"></i>Add New Shift</a>
        </div>
    @endif
    @if (Route::is(['department']))
        <div class="page-btn">
            <a href="#" class="btn btn-added btnAdd" data-bs-toggle="modal"
                data-bs-target="#add-department"><i data-feather="plus-circle" class="me-2"></i>Add New
                Department</a>
        </div>
    @endif
    @if (Route::is(['stockOpname']))
        <div class="page-btn">
            <a href="{{ url('admin/newStockOpname') }}" class="btn btn-added">
                <i data-feather="plus-circle" class="me-2"></i>{{ $li_2 }}
            </a>
        </div>
    @endif
    @if (Route::is(['editStockOpname', 'newStockOpname']))
        <div class="page-btn mb-3">
            <a href="/admin/stockOpname" class="btn btn-secondary"><i data-feather="arrow-left"
                    class="me-2"></i>Kembali
                ke list stok opname</a>
        </div>
    @endif
    @if (Route::is(['salesDetail']))
        <div class="page-btn mb-3">
            <a href="/admin/sales" class="btn btn-secondary"><i data-feather="arrow-left" class="me-2"></i>Kembali
                ke daftar Sales Order</a>
        </div>
    @endif
    @if (Route::is(['purchaseDetail']))
        <div class="page-btn mb-3">
            <a href="/admin/purchase" class="btn btn-secondary"><i data-feather="arrow-left"
                    class="me-2"></i>Kembali
                ke daftar Purchase Order</a>
        </div>
    @endif
    @if (Route::is(['addProduct']))
        <div class="page-btn mt-2">
            <a href="/admin/products" class="btn btn-secondary"><i data-feather="arrow-left"
                    class="me-2"></i>Kembali
                ke daftar produk</a>
        </div>
    @endif
    @if (Route::is(['BestSeller']))
        <div class="page-btn">
            <a href="#" class="btn btn-added" data-bs-toggle="modal" data-bs-target="#add-best-seller"><i
                    data-feather="plus-circle" class="me-2"></i> {{ $li_2 }}</a>
        </div>
    @endif
    @if (Route::is(['productPrices']))
        {{-- <div class="page-btn">
            <a href="#" class="btn btn-added" data-bs-toggle="modal" data-bs-target="#add-product-price"><i
                    data-feather="plus-circle" class="me-2"></i>Add New Product Price</a>
        </div> --}}
    @endif
</div>
</ul>
