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
    
    <div class="modal fade" id="dashboard_widgets_modal" tabindex="-1" aria-hidden="true">
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
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="btn_save_dash_widgets">Simpan Widget</button>
                </div>
            </div>
        </div>
    </div>

    <!-- /Page Wrapper -->
@endsection

@section('custom_js')
    <script src="{{ asset('/Custom_js/Backoffice/User/Role.js') }}"></script>
@endsection
