<?php $page = 'cash_operational'; ?>
@extends('layout.mainlayout')
@section('custom_css')
    <style>
        #tableCash {
            width: 100% !important;
        }

        #tableCash td {
            white-space: normal !important;
            word-wrap: break-word;
            vertical-align: middle;
        }

        #tableCash td.d-flex a {
            display: inline-flex !important;
            align-items: center;
        }

        #tableCash td.d-flex {
            display: table-cell !important; /* override d-flex di td */
        }

        #tableCash td:last-child {
            white-space: nowrap !important;
        }

        #tableCash td:last-child a {
            display: inline-flex !important;
            align-items: center;
            margin-right: 4px;
        }

        .child-wrapper {
            margin-left: 60px; 
            max-width: 80%;
        }

        .child-item {
            display: flex;
            align-items: flex-start;
            gap: 40px;               /* jarak antar kolom */
            padding: 12px 0px 12px 36px;
            border-bottom: 1px solid #eee;
        }

        .child-left {
            flex: 0 0 50%;
            padding-left: 0.8rem
        }

        .child-right {
            flex: 0 0 34%;
            text-align: right;
            padding-right: 12rem;
        }

        .child-left-total {
            flex: 0 0 50%;
            padding-left: 6.8rem
        }

        .left-row {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .date {
            flex: 0 0 auto;
        }

        .notes {
            max-width: 30rem;
            white-space: normal;
            word-break: break-word;
        }

        body.modal-open {
            overflow: hidden !important; /* kembalikan ke default Bootstrap */
            padding-right: 0 !important; /* tapi hilangkan padding-right yang bikin geser */
        }

    </style>
@endsection
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    Kas Operasional
                @endslot
            @endcomponent
            <!-- /Page Header -->

            <!-- Search Filter -->
            @component('components.search-filter')
            @endcomponent
            <!-- /Search Filter -->

            <div class="row">
                <div class="row col-12 mx-1">
                    <div class="card">
                        <div class="card-body row">
                            <div class="col-md-6">
                                <div class="col-6">
                                    <select class="form-select" id="cashType">
                                        <option value="admin">Kas Admin</option>
                                        <option value="gudang">Kas Gudang</option>
                                        <option value="armada">Dompet Virtual Armada</option>
                                        <option value="sales">Dompet Virtual Sales</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6 row justify-content-end">
                                <div class="total-summary text-end w-75">
                                    <div class="px-3 py-2 mb-0">
                                        <div class="row info_card">
                                            <div class="fw-bold text-end text-black p-0">
                                                <i class="fe fe-dollar-sign"></i> Kas Armada : <span id="totalArmada">-</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-center table-hover" id="tableCash">
                                    <thead class="thead-light">
                                        <tr id="headers">
                                            <th width="20"></th>
                                            <th>Tanggal</th>
                                            <th>Staff</th>
                                            <th>Deskripsi</th>
                                            <th class="text-end">Masuk</th>
                                            <th class="text-end">Keluar</th>
                                            <th>Dibuat Oleh</th>
                                            <th>Diapprove/Ditolak Oleh</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="4" class="fw-bold">Total : </td>
                                            <td class="fw-bold text-end debits text-success">Rp 0</td>
                                            <td class="fw-bold text-end credits text-danger">Rp 0</td>
                                            <td colspan="4"></td>
                                        </tr>
                                        <tr>
                                            <td colspan="5" class="fw-bold text-end">Sisa Kas : </td>
                                            <td class="fw-bold text-end sisa">Rp 0</td>
                                            <td colspan="4"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- /Table -->

        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection

@section('custom_js')
    <script>
        var public = "{{ asset('') }}";    
    </script>
    <script src="{{asset('Custom_js/Backoffice/Reports/Cash_Operational.js')}}"></script>
@endsection