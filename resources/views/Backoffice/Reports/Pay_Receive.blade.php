<?php $page = 'payReceive'; ?>
@extends('layout.mainlayout')
@section('content')
    <style>
        .page-header {
            margin-bottom: 0;
        }

        #tablePayables {
            width: 100% !important;
            min-width: 1000px;
        }

        #tablePayables thead th {
            padding: 14px 16px !important;
            color: #1e3a8a !important;
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: #f8fafc !important;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
            white-space: nowrap !important;
        }

        #tablePayables tbody td {
            padding: 14px 16px !important;
            vertical-align: middle;
            font-size: 13px;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
        }

        #tablePayables td {
            white-space: normal !important;
            word-wrap: break-word;
        }

        #tablePayables td:last-child {
            white-space: nowrap !important;
        }

        #tablePayables td:last-child a {
            display: inline-flex !important;
            align-items: center;
        }

        /* Primary Blue Button Theme */
        .btn-create,
        button.btn-create {
            background-color: #2563eb !important;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
            border-color: #1d4ed8 !important;
            color: #ffffff !important;
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.3) !important;
            transition: all 0.2s ease-in-out !important;
        }
        .btn-create:hover,
        button.btn-create:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%) !important;
            border-color: #1e40af !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(29, 78, 216, 0.4) !important;
        }
        .btn-print,
        button.btn-print {
            border: 1.5px solid #2563eb !important;
            color: #2563eb !important;
            background-color: #ffffff !important;
            background: #ffffff !important;
            transition: all 0.2s ease-in-out !important;
        }
        .btn-print:hover,
        button.btn-print:hover {
            background-color: #eff6ff !important;
            background: #eff6ff !important;
            color: #1d4ed8 !important;
            border-color: #1d4ed8 !important;
        }

        .card-table {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            overflow: hidden;
            background: #ffffff;
        }

        #tablePayables-wrap {
            position: relative;
            min-height: 280px;
        }

        #tablePayables_wrapper .dataTables_processing {
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            width: 100% !important;
            height: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            border: 0 !important;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.72) !important;
            box-shadow: none !important;
            z-index: 20;
            align-items: center;
            justify-content: center;
            color: #1e293b;
            font-weight: 600;
            font-size: 14px;
        }

        #tablePayables-wrap:not(.is-loading) .dataTables_processing {
            display: none !important;
        }

        #tablePayables-wrap.is-loading .dataTables_processing {
            display: flex !important;
        }

        #tablePayables_wrapper .dataTables_processing > div {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            border-radius: 10px;
            background: #fff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        }

        #tablePayables-wrap.is-loading tbody {
            opacity: 0.45;
            pointer-events: none;
        }
    </style>
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            <div class="d-flex justify-content-between">
                @component('components.page-header')
                        @slot('title')
                            Hutang
                        @endslot
                @endcomponent
            </div>
            <!-- /Page Header -->

            <!-- Search Filter -->
            @component('components.search-filter')
            @endcomponent
            <!-- /Search Filter -->

            <!-- Table -->
            <div class="row mt-3">
                <div class="col-sm-12">
                    <div class="card card-table border-0">
                        <div class="card-body p-0">
							<div class="tab-content pt-0">
								<div class="table-responsive position-relative dt-pending" id="tablePayables-wrap">
                                    <div class="dt-skeleton" aria-hidden="true">
                                        <div class="dt-skeleton-head" style="grid-template-columns: 5% 10% 12% 12% 12% 18% 12% 12% 7%;">
                                            <span style="width:40%"></span>
                                            <span style="width:55%"></span>
                                            <span style="width:60%"></span>
                                            <span style="width:60%"></span>
                                            <span style="width:55%"></span>
                                            <span style="width:70%"></span>
                                            <span style="width:50%"></span>
                                            <span style="width:55%"></span>
                                            <span style="width:40%"></span>
                                        </div>
                                        <div class="dt-skeleton-body">
                                            @for ($i = 0; $i < 6; $i++)
                                                <div class="dt-skeleton-row" style="grid-template-columns: 5% 10% 12% 12% 12% 18% 12% 12% 7%;">
                                                    <span class="skel-text" style="width:40%;justify-self:center"></span>
                                                    <span class="skel-badge" style="width:70%"></span>
                                                    <span class="skel-text" style="width:70%"></span>
                                                    <span class="skel-text" style="width:70%"></span>
                                                    <span class="skel-text" style="width:75%"></span>
                                                    <span class="skel-text" style="width:85%"></span>
                                                    <span class="skel-text" style="width:70%;justify-self:end"></span>
                                                    <span class="skel-badge" style="width:70%;justify-self:center"></span>
                                                    <span class="skel-btn" style="justify-self:center"></span>
                                                </div>
                                            @endfor
                                        </div>
                                    </div>
                                    <table class="table table-center table-hover mb-0" id="tablePayables">
                                        <thead>
                                            <tr>
                                                <th class="text-center" style="width: 40px;"><input type="checkbox" class="form-check-input" name="" id="selectAll" style="cursor: pointer;"></th>
                                                <th>Bank</th>
                                                <th>Tgl. Pemesanan</th>
                                                <th>Tgl. Jatuh Tempo</th>
                                                <th>Nomor Faktur</th>
                                                <th>Nama Pemasok</th>
                                                <th>Total</th>
                                                <th>Status</th>
                                                <th class="no-sort text-center" style="width: 60px;">Aksi</th>
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
            <!-- /Table -->

        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection

@section('custom_js')
    <script>
        var public = "{{ asset('') }}";
    </script>
    <script src="{{asset('Custom_js/Backoffice/Reports/Pay_Receive.js')}}?v={{ time() }}"></script>
@endsection