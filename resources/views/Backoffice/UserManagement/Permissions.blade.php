<?php $page = 'permissions'; ?>

@php
    $modules = [
        'Product',
        'Show Harga Mutasi',
        'Show Harga Surat Jalan',
        'Show Selisih Stockopname',
        'Category',
        'Satuan',
        'Brand',
        'Variant',
        'Barcode Management',
        'Promo',
        'Cabang',
        'Warehouse',
        'Bundling Product',
        'Best Seller',
        'Supplier',
        'Customer',
        'Sales Order',
        'Purchase Order',
        'Product Issues',
        'Mutasi Product',
        'Stock Alert',
        'Stock Transfer',
        'Stock Opname',
        'Barang Masuk & Keluar Product',
        'Work Order Letter',
        'Category Inventaris',
        'Inventaris',
        'Kelola Stock Inventaris',
        'POS Transactions',
        'POS Session',
        'Cashier',
        'Staff',
        'Departments',
        'Role',
        'Shifts',
        'Category COA',
        'SUBCOA',
        'COA',
        'Journal Entries',
        'General Ledgers',
        'Piutang & Hutang',
        'Setting',
        'Laporan Buku Besar',
        'Laporan Arus Kas',
        'Laporan Jurnal Umum',
        'Laporan Sales',
        'Laporan P/L',
        'Laporan Retur',
        'Laporan Barang Masuk-Keluar',
        'Laporan Stok Terakhir',
        'Laporan Absensi',
        'Laporan Stok Inventaris',
        'Multi Outlet',
        'Multi Warehouse',
    ];
    $viewOnly = [
        'Stock Alert',
        'Goods Receipt',
        'Stock Opname',
        'POS Transactions',
        'Cashier',
        'Laporan Buku Besar',
        'Laporan Arus Kas',
        'Laporan Jurnal Umum',
        'Laporan Sales',
        'Laporan P/L',
        'Laporan Retur',
        'Laporan Barang Masuk-Keluar',
        'Laporan Stok Terakhir',
        'Laporan Absensi',
        'Laporan Stok Inventaris',
    ];
@endphp
@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content">
            <div class="page-header">
                <div class="add-item d-flex">
                    <div class="page-title">
                        <h4>Permission</h4>
                        <h6>Manage your permissions</h6>
                    </div>
                </div>
                <div class="page-btn  pt-3">
                    <a href="/admin/rolesPermissions" class="btn btn-secondary"><i data-feather="arrow-left"
                            class="me-2"></i>Kembali
                        ke daftar role</a>
                </div>
            </div>

            <!-- /product list -->
            <div class="card table-list-card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="search-set w-100">
                                <input type="text" id="searchModule" class="form-control" placeholder="Cari module...">
                            </div>
                        </div>

                        <!-- ✅ bagian kanan -->
                        <div class="col-md-6 text-end">
                            <div class="d-inline-flex align-items-center gap-2 justify-content-end">
                                <label class="checkboxs mb-0">
                                    <input type="checkbox" class="allPermissions checkbox">
                                    <span class="checkmarks"></span>
                                </label>
                                <span class="fw-semibold mt-2 pt-2">All Permissions</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive" style="max-height: 70vh; overflow-y: auto; overflow-x: auto;">
                    <table class="table">
                        <thead class="table-light sticky-top" style="top: -25px; z-index: 1;">
                            <tr>
                                <th>Modules</th>
                                <th>Create</th>
                                <th>Edit</th>
                                <th>Delete</th>
                                <th>View</th>
                                <th class="no-sort">Allow all</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($modules as $module)
                                <tr class="row-module" module="{{ $module }}">
                                    <td>{{ $module }}</td>
                                    <td class="text-center">
                                        <label class="checkboxs">
                                            <input type="checkbox" class="create checkbox">
                                            <span class="checkmarks"></span>
                                        </label>
                                    </td>
                                    <td class="text-center">
                                        <label class="checkboxs">
                                            <input type="checkbox" class="edit checkbox">
                                            <span class="checkmarks"></span>
                                        </label>
                                    </td>
                                    <td class="text-center">
                                        <label class="checkboxs">
                                            <input type="checkbox" class="delete checkbox">
                                            <span class="checkmarks"></span>
                                        </label>
                                    </td>
                                    <td class="text-center">
                                        <label class="checkboxs">
                                            <input type="checkbox" class="view checkbox">
                                            <span class="checkmarks"></span>
                                        </label>
                                    </td>
                                    <td class="text-center">
                                        <label class="checkboxs">
                                            <input type="checkbox" class="all checkbox">
                                            <span class="checkmarks"></span>
                                        </label>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                </div>
                <div class="card-footer">
                    <div class="text-end mt-2">
                        <button type="button" class="btn btn-added btnAdd btn-save">Save Data</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- /product list -->
    </div>
    </div>
@endsection

@section('custom_js')
    <script>
        var mode = 2;
        var data = @json($data);
        var perm = data ? JSON.parse(data.role_access) : [];
    </script>
    <script src="{{ asset('/Custom_js/Backoffice/UserManagement/Permissions.js') }}?v={{ time() }}"></script>
@endsection
