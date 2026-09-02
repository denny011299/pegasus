<?php $page = 'roles-permission'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">
            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    Peran & Izin
                @endslot
            @endcomponent
            <!-- /Page Header -->

            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-center table-hover" id="tableRole">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nama Peran</th>
                                            <th>Jumlah Pengguna</th>
                                            <th>Dibuat Pada</th>
                                            <th class="no-sort">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="dashboard_widgets_modal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Widget Dashboard Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Role: <strong id="dash_widget_role_name">-</strong></p>
                    <div class="mb-3">
                        <label class="checkboxs mb-0">
                            <input type="checkbox" id="check_all_dash_widgets">
                            <span class="checkmarks"></span>
                        </label>
                        <span class="ms-2">Centang semua widget</span>
                    </div>
                    <div class="row g-2" id="dash_widget_checkbox_wrap">
                        <div class="col-md-6">
                            <div class="border rounded p-2">
                                <label class="checkboxs mb-0">
                                    <input type="checkbox" class="dash-widget-checkbox" value="kpi_ringkasan">
                                    <span class="checkmarks"></span>
                                </label>
                                <span class="ms-2">Ringkasan changelog & KPI</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-2">
                                <label class="checkboxs mb-0">
                                    <input type="checkbox" class="dash-widget-checkbox" value="approval_logs">
                                    <span class="checkmarks"></span>
                                </label>
                                <span class="ms-2">Changelog & log persetujuan</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-2">
                                <label class="checkboxs mb-0">
                                    <input type="checkbox" class="dash-widget-checkbox" value="delivery_chart">
                                    <span class="checkmarks"></span>
                                </label>
                                <span class="ms-2">Grafik & top produk pengiriman</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-2">
                                <label class="checkboxs mb-0">
                                    <input type="checkbox" class="dash-widget-checkbox" value="stock_aging">
                                    <span class="checkmarks"></span>
                                </label>
                                <span class="ms-2">Stock aging</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-2">
                                <label class="checkboxs mb-0">
                                    <input type="checkbox" class="dash-widget-checkbox" value="stock_alert_bahan">
                                    <span class="checkmarks"></span>
                                </label>
                                <span class="ms-2">Stock alert bahan mentah</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-2">
                                <label class="checkboxs mb-0">
                                    <input type="checkbox" class="dash-widget-checkbox" value="overstock_rekomendasi">
                                    <span class="checkmarks"></span>
                                </label>
                                <span class="ms-2">Overstock & rekomendasi stok produksi</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-2">
                                <label class="checkboxs mb-0">
                                    <input type="checkbox" class="dash-widget-checkbox" value="jatuh_tempo_hutang">
                                    <span class="checkmarks"></span>
                                </label>
                                <span class="ms-2">Jatuh tempo hutang customer</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="btn_save_dash_widgets">Simpan Widget</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Select2 peran-pengganti di-append ke body (dropdownParent) supaya tidak
           kepotong overflow modal — samakan pola dengan add-production.blade.php. */
        #role_reassign_modal .select2-dropdown {
            z-index: 1065 !important;
        }
        #tableReassignRoleUsers tbody td {
            padding: 10px 16px;
            vertical-align: middle;
        }
    </style>
    <div class="modal custom-modal fade pg-modal--danger" id="role_reassign_modal" aria-modal="true" role="dialog"
        tabindex="-1" data-bs-backdrop="static" data-bs-focus="false">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="pg-modal-icon">
                            <i class="fe fe-trash-2"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 modal-title">Hapus Peran "<span id="reassign_role_name">-</span>"</h5>
                            <small class="modal-subtitle">Pilih peran pengganti untuk tiap pengguna sebelum peran ini dihapus</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0 bg-light">
                    <div class="p-4">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="fe fe-users text-primary"></i>
                            <span class="fw-bold text-dark" style="font-size:14px;">Pengguna yang memakai peran ini</span>
                        </div>
                        <div class="table-responsive rounded border bg-white">
                            <table class="table table-center custom-table-scroll mb-0" id="tableReassignRoleUsers">
                                <thead style="background: #f1f5f9;">
                                    <tr>
                                        <th style="width: 45%; padding: 12px 16px; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase;">
                                            Nama Pengguna</th>
                                        <th style="width: 55%; padding: 12px 16px; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase;">
                                            Peran Pengganti</th>
                                    </tr>
                                </thead>
                                <tbody id="reassign_role_users_body">
                                    <tr class="pg-popup-table-empty">
                                        <td colspan="2">Memuat daftar pengguna...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer pg-modal-footer">
                    <button type="button" data-bs-dismiss="modal" class="btn pg-btn-cancel btn-cancel">Batal</button>
                    <button type="button" class="btn pg-btn-confirm pg-btn-confirm--danger" id="btn-confirm-reassign-delete-role">
                        <i class="fe fe-trash-2"></i> Lepas & Hapus Peran
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- /Page Wrapper -->
@endsection

@section('custom_js')
    <script src="{{ asset('/Custom_js/Backoffice/User/Role.js') }}"></script>
@endsection
