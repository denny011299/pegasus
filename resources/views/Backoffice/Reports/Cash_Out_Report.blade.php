<?php $page = 'report_cash_out'; ?>
@extends('layout.mainlayout')

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            @component('components.page-header')
                @slot('title')
                    Laporan Pengeluaran Kas
                @endslot
            @endcomponent

            <div class="card mb-3">
                <div class="card-body">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Mode Filter</label>
                            <select id="cash_out_filter_mode" class="form-select">
                                <option value="day">Harian</option>
                                <option value="month" selected>Bulanan</option>
                                <option value="year">Tahunan</option>
                            </select>
                        </div>
                        <div class="col-md-3" id="wrap_filter_day" style="display:none;">
                            <label class="form-label">Tanggal</label>
                            <input type="date" id="cash_out_filter_day" class="form-control">
                        </div>
                        <div class="col-md-3" id="wrap_filter_month">
                            <label class="form-label">Bulan</label>
                            <input type="month" id="cash_out_filter_month" class="form-control">
                        </div>
                        <div class="col-md-2" id="wrap_filter_year" style="display:none;">
                            <label class="form-label">Tahun</label>
                            <input type="number" id="cash_out_filter_year" class="form-control" min="2000" max="2100" step="1">
                        </div>
                        <div class="col-md-2">
                            <button type="button" id="btn_apply_cash_out_filter" class="btn btn-primary w-100">Terapkan</button>
                        </div>
                        <div class="col-md-2">
                            <button type="button" id="btn_reset_cash_out_filter" class="btn btn-outline-secondary w-100">Reset</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="card mb-0">
                        <div class="card-body py-3">
                            <div class="text-muted small">Periode</div>
                            <div class="fw-semibold" id="cash_out_period_label">-</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card mb-0">
                        <div class="card-body py-3">
                            <div class="text-muted small">Total Pengeluaran</div>
                            <div class="fw-bold text-danger" id="cash_out_total_pengeluaran">Rp 0</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card mb-0">
                        <div class="card-body py-3">
                            <div class="text-muted small">Jumlah Transaksi</div>
                            <div class="fw-bold" id="cash_out_total_transaksi">0</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-table">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-center table-hover" id="tableCashOutReport">
                            <thead class="thead-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Deskripsi</th>
                                    <th>Tipe</th>
                                    <th>Tujuan</th>
                                    <th class="text-end">Nominal</th>
                                    <th>Dibuat Oleh</th>
                                    <th>Di-ACC Oleh</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('custom_js')
    <script src="{{ asset('Custom_js/Backoffice/Reports/Cash_Out_Report.js') }}?v={{ time() }}"></script>
@endsection
