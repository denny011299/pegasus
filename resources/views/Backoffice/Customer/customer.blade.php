<?php $page = 'customers'; ?>
@extends('layout.mainlayout')
@section('custom_css')
    <style>
        #tableCustomer {
            width: 100% !important;
            table-layout: fixed;
        }

        #tableCustomer th,
        #tableCustomer td {
            white-space: normal !important;
            word-wrap: break-word;
            vertical-align: middle;
            box-sizing: border-box;
        }

        #tableCustomer thead th {
            padding: 14px 16px !important;
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .4px;
            background: #f1f5f9;
            border-bottom: 1px solid #e2e8f0;
        }

        #tableCustomer tbody td {
            padding: 14px 16px !important;
            color: #475569;
            font-size: 13px;
        }

        #tableCustomer td:last-child,
        #tableCustomer th:last-child {
            white-space: nowrap !important;
            width: 110px !important;
            text-align: center;
        }

        #tableCustomer td:last-child a {
            display: inline-flex !important;
            align-items: center;
        }

        #tableCustomer tbody tr {
            border-bottom: 1px solid #f1f5f9;
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
                    Armada
                @endslot
            @endcomponent
            <!-- /Page Header -->

            <!-- Search Filter -->
            @component('components.search-filter')
            @endcomponent
            <!-- /Search Filter -->

            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive dt-pending" id="tableCustomer-wrap" style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
                                <table class="table table-center table-hover mb-0" id="tableCustomer">
                                    <thead style="background:#f1f5f9; border-bottom: 1px solid #e2e8f0;">
                                        <tr>
                                            <th style="color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing:.4px; padding: 12px 16px;">No Pol</th>
                                            <th style="color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing:.4px; padding: 12px 16px;">Nama PIC</th>
                                            <th style="color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing:.4px; padding: 12px 16px;">Telepon PIC</th>
                                            <th style="color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing:.4px; padding: 12px 16px;">Saldo Armada</th>
                                            <th style="color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing:.4px; padding: 12px 16px;">Dibuat</th>
                                            <th style="color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing:.4px; padding: 12px 16px;">Dibuat Oleh</th>
                                            <th class="no-sort text-center" style="color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing:.4px; padding: 12px 16px;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection
@section('custom_js')
    <script>
        var public = "{{ asset('') }}";    
    </script>
    <script src="{{asset('Custom_js/Backoffice/Customers/Customer.js')}}?v={{time()}}"></script>
@endsection
