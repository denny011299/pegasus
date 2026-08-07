<?php $page = 'bom'; ?>
@extends('layout.mainlayout')
@section('custom_css')
    <style>
        #add_bom .select2-container {
            width: 100% !important;
        }

        #filter_product_id + .select2-container,
        #filter_supplies_id + .select2-container {
            width: 100% !important;
        }

        /* Pola DataTable sama Produksi */
        #tableBom {
            width: 100% !important;
            table-layout: fixed;
        }

        #tableBom th,
        #tableBom td {
            white-space: normal !important;
            word-wrap: break-word;
            vertical-align: middle;
            box-sizing: border-box;
        }

        #tableBom thead th {
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .4px;
            background: #f1f5f9;
            border-bottom: 1px solid #e2e8f0;
        }

        #tableBom tbody td {
            color: #475569;
            font-size: 13px;
        }

        #tableBom td:last-child,
        #tableBom th:last-child {
            white-space: nowrap !important;
            width: 11% !important;
            min-width: 110px !important;
        }

        #tableBom td:last-child a {
            display: inline-flex !important;
            align-items: center;
            flex-shrink: 0;
        }

        #tableBom tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.2s ease;
        }

        #tableBom-wrap {
            position: relative;
        }

        #tableBom_wrapper .dataTables_processing {
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

        #tableBom-wrap:not(.is-loading) .dataTables_processing {
            display: none !important;
        }

        #tableBom-wrap.is-loading .dataTables_processing {
            display: flex !important;
        }

        #tableBom_wrapper .dataTables_processing > div {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            border-radius: 10px;
            background: #fff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        }

        #tableBom-wrap.is-loading tbody {
            opacity: 0.45;
            pointer-events: none;
        }

        #tableBom .btn-action-icon {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.2s;
            text-decoration: none;
            flex-shrink: 0;
        }

        #tableBom .btn-action-icon:hover {
            transform: scale(1.05);
            opacity: 0.9;
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
                    Resep Bahan Mentah
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
                    <div class="card card-table">
                        <div class="card-body">
                            <div class="table-responsive dt-pending" id="tableBom-wrap" style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
                                <div class="dt-skeleton" aria-hidden="true">
                                    <div style="padding: 16px 25px;">
                                        <span class="skel-text" style="width: 250px; height: 38px; border-radius: 20px;"></span>
                                    </div>
                                    <div class="dt-skeleton-head" style="grid-template-columns: 12% 22% 28% 12% 14% 12%;">
                                        <span style="width:60%"></span>
                                        <span style="width:70%"></span>
                                        <span style="width:80%"></span>
                                        <span style="width:55%"></span>
                                        <span style="width:60%"></span>
                                        <span style="width:40%"></span>
                                    </div>
                                    <div class="dt-skeleton-body">
                                        @for ($i = 0; $i < 5; $i++)
                                            <div class="dt-skeleton-row" style="grid-template-columns: 12% 22% 28% 12% 14% 12%;">
                                                <span class="skel-text" style="width:70%"></span>
                                                <span class="skel-text" style="width:80%"></span>
                                                <span class="skel-text" style="width:90%"></span>
                                                <span class="skel-badge" style="width:55%"></span>
                                                <span class="skel-avatar" style="justify-self:start"></span>
                                                <span class="skel-btn" style="justify-self:center"></span>
                                            </div>
                                        @endfor
                                    </div>
                                </div>
                                <table class="table table-center table-hover mb-0" id="tableBom">
                                    <thead>
                                        <tr>
                                            <th>SKU</th>
                                            <th>Produk</th>
                                            <th>Material</th>
                                            <th>Qty Produksi</th>
                                            <th>Dibuat Oleh</th>
                                            <th class="no-sort text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
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
    <script src="{{asset('Custom_js/Backoffice/Production/Bom.js')}}"></script>
@endsection
