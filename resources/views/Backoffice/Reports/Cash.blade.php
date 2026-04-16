<?php $page = 'cash'; ?>
@extends('layout.mainlayout')
@section('custom_css')
    <style>
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

        #tableCash {
            width: 100% !important;
            min-width: 900px;
        }

        #tableCash td, #tableCash th {
            white-space: nowrap; /* jangan wrap, biar scroll horizontal yang kerja */
            vertical-align: middle;
            padding: 8px 10px;
        }

        /* Khusus deskripsi boleh wrap */
        #tableCash td:nth-child(3),
        #tableCash th:nth-child(3) {
            white-space: normal;
            min-width: 150px;
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
                    Kas
                @endslot
            @endcomponent
            <!-- /Page Header -->

            <!-- Search Filter -->
            @component('components.search-filter')
            @endcomponent
            <!-- /Search Filter -->

            <!-- Table -->
            <div class="row">
                <div class="col-sm-12">
                    <div class=" card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-center table-hover" id="tableCash">
                                    <thead class="thead-light">
                                        <tr>
                                            <th style="width: 4%"></th>
                                            <th style="width: 9%">Tanggal</th>
                                            <th style="width: 18%">Deskripsi</th>
                                            <th style="width: 12%" class="text-end">Masuk</th>
                                            <th style="width: 12%" class="text-end">Keluar</th>
                                            <th style="width: 12%" class="text-end">Keluar 1</th>
                                            <th>Dibuat Oleh</th>
                                            <th>Diapprove/Ditolak Oleh</th>
                                            <th style="width: 18%">Status</th>
                                            <th style="width: 15%">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="text-end fw-bold">Total : </td>
                                            <td class="debits text-success"></td>
                                            <td class="credits1 text-danger"></td>
                                            <td class="credits2 text-danger"></td>
                                            <td colspan="4"></td>
                                        </tr>
                                        <tr>
                                            <td colspan="5" class="fw-bold text-end">Sisa Kas : </td>
                                            <td class="fw-bold text-end sisa">Rp 0</td>
                                            <td colspan="2"></td>
                                            <td class="fw-bold text-end">Total Setoran : </td>
                                            <td class="fw-bold text-end setor pe-4">Rp 0</td>
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
    <script src="{{asset('Custom_js/Backoffice/Reports/Cash.js')}}?v=1"></script>
@endsection