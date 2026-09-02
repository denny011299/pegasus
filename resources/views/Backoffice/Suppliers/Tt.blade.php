<?php $page = 'purchase_order'; ?>
@extends('layout.mainlayout')
@section('custom_css')
    <style>
        .badgeStatus{
            font-size:9pt!important;
        }
        .invalid{
            border: 1px solid red!important;
        }
        #filter_supplier,
        #filter_supplier + .select2-container {
            width: 100% !important;
            max-width: 100% !important;
        }
        #tableTTPurchaseOrder{
            width: 100% !important;
        }
        #tableTTPurchaseOrder td {
            white-space: normal !important;
            word-wrap: break-word;
        }
        #tableTTPurchaseOrder td:last-child {
            white-space: nowrap !important;
        }
        #tableTTPurchaseOrder td:last-child a {
            display: inline-flex !important;
            align-items: center;
        }
        #filter_supplier,
        #filter_supplier + .select2-container {
            width: 100% !important;
            max-width: 100% !important;
        }

        /* Tambah ini */
        .select2-container {
            max-width: 100% !important;
        }

        #tableTTPurchaseOrder-wrap .dt-skeleton {
            border-radius: 0;
            background: transparent;
            box-shadow: none;
        }

        #tableTTPurchaseOrder_wrapper .dataTables_processing {
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

        #tableTTPurchaseOrder-wrap:not(.is-loading) .dataTables_processing {
            display: none !important;
        }

        #tableTTPurchaseOrder-wrap.is-loading .dataTables_processing {
            display: flex !important;
        }

        #tableTTPurchaseOrder_wrapper .dataTables_processing > div {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            border-radius: 10px;
            background: #fff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        }

        #tableTTPurchaseOrder-wrap.is-loading tbody {
            opacity: 0.45;
            pointer-events: none;
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
                    Tanda Terima
                @endslot
            @endcomponent
            <!-- /Page Header -->

            <!-- Filter Pencarian -->
            @component('components.search-filter')
            @endcomponent
            <!-- /Filter Pencarian -->

            <!-- Tabel -->
            <div class="row">
                <div class="col-sm-12">
                    <div class=" card-table">
                        <div class="card-body">
                            
                            <div class="table-responsive dt-pending" id="tableTTPurchaseOrder-wrap">
                                <div class="dt-skeleton" aria-hidden="true">
                                    <div style="padding: 16px 25px;">
                                        <span class="skel-text" style="width: 250px; height: 38px; border-radius: 20px;"></span>
                                    </div>
                                    <div class="dt-skeleton-head" style="grid-template-columns: 10% 12% 18% 12% 12% 10% 10% 10% 6%;">
                                        <span style="width:60%"></span>
                                        <span style="width:60%"></span>
                                        <span style="width:55%"></span>
                                        <span style="width:50%"></span>
                                        <span style="width:50%"></span>
                                        <span style="width:50%"></span>
                                        <span style="width:55%"></span>
                                        <span style="width:55%"></span>
                                        <span style="width:40%"></span>
                                    </div>
                                    <div class="dt-skeleton-body">
                                        @for ($i = 0; $i < 5; $i++)
                                            <div class="dt-skeleton-row" style="grid-template-columns: 10% 12% 18% 12% 12% 10% 10% 10% 6%;">
                                                <span class="skel-text" style="width:70%"></span>
                                                <span class="skel-text" style="width:60%"></span>
                                                <span class="skel-text" style="width:80%"></span>
                                                <span class="skel-text" style="width:60%"></span>
                                                <span class="skel-text" style="width:70%"></span>
                                                <span class="skel-badge" style="width:60%;justify-self:center"></span>
                                                <span class="skel-text" style="width:60%"></span>
                                                <span class="skel-text" style="width:60%"></span>
                                                <span class="skel-badge" style="width:50%;justify-self:center"></span>
                                            </div>
                                        @endfor
                                    </div>
                                </div>
                                <table class="table table-center table-hover" id="tableTTPurchaseOrder">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>No.Tanda Terima </th>
                                            <th>Nama Pemasok</th>
                                            <th>Keterangan</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th>Dibuat Oleh</th>
                                            <th>Diapprove/Ditolak Oleh</th>
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
            <!-- /Tabel -->

        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection

@section('custom_js')
    <script>
        var public = "{{ asset('') }}";    
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/compressorjs/1.2.1/compressor.min.js"></script>
    <script src="{{asset('Custom_js/Backoffice/Suppliers/tt.js')}}?v={{ time() }}"></script>
@endsection
