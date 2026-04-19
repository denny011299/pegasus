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
                    @roleCan('Kategori', 'create')
                    <li>
                        <a class="btn btn-primary btnAdd" href="javascript:void(0);"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Tambah
                            Kategori</a>
                    </li>
                    @endroleCan
                @endif
                @if (Route::is(['unit']))
                    @roleCan('Satuan', 'create')
                    <li>
                        <a class="btn btn-primary btnAdd"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Tambah
                            Satuan</a>
                    </li>
                    @endroleCan
                @endif
                @if (Route::is(['variant']))
                    @roleCan('Variasi', 'create')
                    <li>
                        <a class="btn btn-primary btnAdd"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Tambah
                            Variasi</a>
                    </li>
                    @endroleCan
                @endif
                @if (Route::is(['product']))
                    @roleCan('Daftar Produk', 'create')
                    <li>
                        <a class="btn btn-primary" href="/insertProduct"><i class="fa fa-plus-circle me-2"
                                aria-hidden="true"></i>Tambah Produk</a>
                    </li>
                    @endroleCan
                @endif
                @if (Route::is(['supplies']))
                    @roleCan('Daftar Bahan Mentah', 'create')
                    <li>
                        <a class="btn btn-primary btnAdd"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Tambah
                            Bahan Mentah</a>
                    </li>
                    @endroleCan
                @endif
                @if (Route::is(['salesOrder']))
                    @roleCan('Pengiriman', 'create')
                    <li>
                        <a class="btn btn-primary btnAdd"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Tambah
                            Pengiriman</a>
                    </li>
                    @endroleCan
                @endif
                @if (Route::is(['salesOrderDetail']))
                    <li>
                        <a class="btn btn-primary btnBack"><i class="fa fa-chevron-left me-2" aria-hidden="true"></i>Kembali</a>
                    </li>
                @endif
                @if (Route::is(['purchaseOrder']))
                    @roleCan('Pembelian', 'create')
                    <li>
                        <a class="btn btn-primary btnAdd"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Tambah
                            Pesanan Pembelian</a>
                    </li>
                    @endroleCan
                @endif
                @if (Route::is(['reportProduksi']))
                    @roleCan('Laporan Produksi', 'view')
                    <li>
                        <a class="btn btn-outline-success btn-export-pdf">
                            Export PDF
                        </a>
                    </li>
                    @endroleCan
                @endif
                @if (Route::is(['reportEfisiensiProduksi']))
                    @roleCan('Laporan Produksi', 'view')
                    <li>
                        <a class="btn btn-outline-success btn-export-pdf">
                            Export PDF
                        </a>
                    </li>
                    @endroleCan
                @endif
                @if (Route::is(['ProductReturn']))
                    @roleCan('Retur Produk', 'view')
                    <li>
                        <a class="btn btn-outline-success btn-export-pdf">
                            Export PDF
                        </a>
                    </li>
                    @endroleCan
                @endif
                @if (Route::is(['reportReturProdukArmada']))
                    @roleCan('Retur Produk', 'view')
                    <li>
                        <a class="btn btn-outline-success btn-export-pdf">
                            Export PDF
                        </a>
                    </li>
                    @endroleCan
                @endif
                @if (Route::is(['reportBahanBaku']))
                    @roleCan('Pengelolaan Bahan Mentah', 'view')
                    <li>
                        <a class="btn btn-outline-success btn-export-pdf">
                            Export PDF
                        </a>
                    </li>
                    @endroleCan
                @endif
                @if (Route::is(['reportSelisihOpname']))
                    @roleCanAny(['Stok Opname Produk', 'Stok Opname Bahan Mentah'], 'view')
                    <li>
                        <a class="btn btn-outline-success btn-export-pdf">
                            Export PDF
                        </a>
                    </li>
                    @endroleCanAny
                @endif
                @if (Route::is(['reportStockAging']))
                    @roleCan('Laporan Stock Aging', 'view')
                    <li>
                        <a class="btn btn-outline-success btn-export-pdf">
                            Export PDF
                        </a>
                    </li>
                    @endroleCan
                @endif
                @if (Route::is(['purchaseOrderDetail']))
                    <li>
                        <a class="btn btn-primary btnBack"><i class="fa fa-chevron-left me-2" aria-hidden="true"></i>Kembali</a>
                    </li>
                @endif
                @if (Route::is(['purchaseOrderDetailHutang']))
                    <li>
                        <a class="btn btn-primary btnBackHutang"><i class="fa fa-chevron-left me-2" aria-hidden="true"></i>Kembali</a>
                    </li>
                @endif
                @if (Route::is(['customer']))
                    @roleCan('Armada', 'create')
                    <li>
                        <a class="btn btn-primary" href="/insertCustomer"><i class="fa fa-plus-circle me-2"
                                aria-hidden="true"></i>Tambah Armada</a>
                    </li>
                    @endroleCan
                @endif
               
                @if (Route::is(['bank']))
                    @roleCan('Bank Account', 'create')
                    <li>
                        <a class="btn btn-primary btnAdd" href="#" data-bs-toggle="modal"
                            data-bs-target="#add_bank"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Tambah
                            Bank</a>
                    </li>
                    @endroleCan
                @endif
                @if (Route::is(['role']))
                    @roleCan('Peran & Perizinan', 'create')
                    <li>
                        <a class="btn btn-primary btnAdd" href="#" data-bs-toggle="modal"
                            data-bs-target="#add_role"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Tambah
                            Peran</a>
                    </li>
                    @endroleCan
                @endif
            
                @if (Route::is(['stockOpname']))
                    @roleCan('Stok Opname Produk', 'create')
                    <li>
                        <a class="btn btn-primary" href="/detailStockOpname/-1" ><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Tambah
                            Stok Opname</a>
                    </li>
                    @endroleCan
                @endif
                @if (Route::is(['stockOpnameBahan']))
                    @roleCan('Stok Opname Bahan Mentah', 'create')
                    <li>
                        <a class="btn btn-primary" href="/detailStockOpnameBahan/-1" ><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Tambah
                            Stok Opname</a>
                    </li>
                    @endroleCan
                @endif

                @if (Route::is(['detailStockOpname']))
                    <li>
                        <a class="btn btn-primary btnBack"><i class="fa fa-chevron-left me-2" aria-hidden="true"></i>Kembali</a>
                    </li>
                @endif
                @if (Route::is(['detailStockOpnameBahan']))
                    <li>
                        <a class="btn btn-primary btnBack"><i class="fa fa-chevron-left me-2" aria-hidden="true"></i>Kembali</a>
                    </li>
                @endif

                @if (Route::is(['supplier']))
                    @roleCan('Pemasok', 'create')
                    <li>
                        <a class="btn btn-primary" href="/insertSupplier"><i class="fa fa-plus-circle me-2"
                                aria-hidden="true"></i>Tambah Pemasok</a>
                    </li>
                    @endroleCan
                @endif
                @if (Route::is(['cash']))
                    @roleCanAny(['Kas', 'Kas Operasional'], 'create')
                    <li>
                        <a class="btn btn-primary btnAdd"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Tambah
                            Pencatatan</a>
                    </li>
                    @endroleCanAny
                @endif
                @if (Route::is(['pettyCash']))
                    @roleCanAny(['Kas', 'Kas Operasional'], 'create')
                    <li>
                        <a class="btn btn-primary btnAdd"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Tambah
                            Kas Kecil</a>
                    </li>
                    @endroleCanAny
                @endif
                @if (Route::is(['operationalCash']))
                    @roleCanAny(['Kas', 'Kas Operasional'], 'create')
                    <li>
                        <a class="btn btn-primary btnAddCash"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Tambah
                            Aktivitas</a>
                    </li>
                    @endroleCanAny
                @endif
                @if (Route::is(['staff']))
                    @roleCan('Pengguna', 'create')
                    <li>
                        <a class="btn btn-primary" href="/insertStaff"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Tambah
                            Staff</a>
                    </li>
                    @endroleCan
                @endif
                @if (Route::is(['bom']))
                    @roleCan('Resep Bahan Mentah', 'create')
                    <li>
                        <a class="btn btn-primary btnAdd" ><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Tambah
                            Resep</a>
                    </li>
                    @endroleCan
                @endif
                @if (Route::is(['production']))
                    @roleCan('Produksi', 'create')
                    <li>
                        <a class="btn btn-primary btnAdd" ><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>
                            Tambah Produksi</a>
                    </li>
                    @endroleCan
                @endif
                @if (Route::is(['productIssue']))
                    @roleCan('Produk Bermasalah', 'create')
                    <li>
                        <a class="btn btn-primary btnAdd" ><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>
                            Tambah Produk Bermasalah</a>
                    </li>
                    @endroleCan
                @endif
                @if (Route::is(['staffDetail']))
                    <li>
                        <a class="btn btn-outline-secondary btnBack"><i class="fa fa-chevron-left me-2" aria-hidden="true"></i>Kembali</a>
                    </li>
                @endif
                @if (Route::is(['customerDetail']))
                    <li>
                        <a class="btn btn-outline-secondary btnBack"><i class="fa fa-chevron-left me-2" aria-hidden="true"></i>Kembali</a>
                    </li>
                @endif
                @if (Route::is(['supplierDetail']))
                    <li>
                        <a class="btn btn-outline-secondary btnBack"><i class="fa fa-chevron-left me-2" aria-hidden="true"></i>Kembali</a>
                    </li>
                @endif
                @if (Route::is(['area']))
                    @roleCanAny(['Kategori', 'Satuan', 'Variasi'], 'create')
                    <li>
                        <a class="btn btn-primary btnAdd" ><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Tambah
                            Wilayah</a>
                    </li>
                    @endroleCanAny
                @endif
                @if (Route::is(['cashCategory']))
                    @roleCan('Kategori Kas', 'create')
                    <li>
                        <a class="btn btn-primary btnAdd" href="javascript:void(0);"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Tambah
                            Kategori Kas</a>
                    </li>
                    @endroleCan
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
