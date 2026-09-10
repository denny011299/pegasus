<?php $page = 'supplies'; ?>
@extends('layout.mainlayout')
@section('custom_css')
    <style>
        #tableSupplies {
            width: 100% !important;
            min-width: 800px;
        }

        /* Badge styling untuk Jenis Bahan Mentah */
        .badge.bg-info-transparent,
        .badge-supplies-trading {
            background-color: #e0f2fe !important;
            color: #0369a1 !important;
            border: 1px solid #bae6fd !important;
            font-size: 11.5px !important;
            font-weight: 600 !important;
            padding: 5px 12px !important;
            border-radius: 50rem !important;
            letter-spacing: 0.3px !important;
            display: inline-block !important;
        }

        .badge.bg-secondary-transparent,
        .badge-supplies-supply {
            background-color: #f1f5f9 !important;
            color: #475569 !important;
            border: 1px solid #cbd5e1 !important;
            font-size: 11.5px !important;
            font-weight: 600 !important;
            padding: 5px 12px !important;
            border-radius: 50rem !important;
            letter-spacing: 0.3px !important;
            display: inline-block !important;
        }

        #tableSupplies td {
            white-space: normal !important;
            word-wrap: break-word;
        }
        #tableSupplies td:last-child {
            white-space: nowrap !important;
        }

        #tableSupplies td:last-child a {
            display: inline-flex !important;
            align-items: center;
        }

        #tableSupplies-wrap {
            position: relative;
        }

        #tableSupplies_wrapper .dataTables_processing {
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
            display: flex !important;
            align-items: center;
            justify-content: center;
            color: #1e293b;
            font-weight: 600;
            font-size: 14px;
        }

        #tableSupplies-wrap:not(.is-loading) .dataTables_processing {
            display: none !important;
        }

        #tableSupplies-wrap.is-loading .dataTables_processing {
            display: flex !important;
        }

        #tableSupplies-wrap.is-loading tbody {
            opacity: 0.45;
            pointer-events: none;
        }

        .is-invalid{
            border-color: #dc3545!important;
        }
        .is-invalids {
            border-color: #dc3545!important;
        }
        .td-supplier {
            width: 23%;
            min-width: 200px;
            overflow: hidden;
        }

        .td-supplier .input-block,
        .td-supplier #row-supplier {
            width: 100%;
            overflow: hidden;
        }

        .td-supplier .select2-container {
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            overflow: hidden !important;
        }

        /* ❌ Hapus override display:flex di selection - ini penyebab × ke kiri */
        .td-supplier .select2-selection--single {
            overflow: hidden;
            /* Jangan tambah display:flex di sini */
        }

        .td-supplier .select2-selection__rendered {
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            white-space: nowrap !important;
            max-width: calc(100% - 50px) !important;
            display: block !important; /* ✅ Paksa block bukan flex */
        }

        /* ✅ Pastikan clear button tetap di posisi default select2 */
        .td-supplier .select2-selection__clear {
            position: absolute !important;
            right: 25px !important;
            top: 0 !important;
            bottom: 0 !important;
            transform: none !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        .td-supplier .select2-selection--single {
            position: relative !important;
        }

        .td-supplier .select2-container--default .select2-selection--single {
            height: 38px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 0;
            position: relative;
        }

        .td-supplier .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 38px !important;
            padding-left: 8px !important;
            padding-right: 40px !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            white-space: nowrap !important;
            display: block !important;
        }

        .td-supplier .select2-container--default .select2-selection--single .select2-selection__clear {
            position: absolute !important;
            right: 25px !important;
            top: 0 !important;
            bottom: 0 !important;
            transform: none !important;
            margin: 0 !important;
            line-height: 1 !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        .td-supplier .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 38px !important;
            top: 0 !important;
            right: 4px !important;
        }

        /* ==========================================================
        KHUSUS TABEL VARIASI DINAMIS (#productVariantTable)
        ========================================================== */

        #productVariantTable {
            width: 100% !important;
            table-layout: auto !important; /* Agar min-width input bisa memaksa horizontal scroll */
            border-collapse: collapse;
        }

        /* Header Styling */
        #productVariantTable thead td {
            white-space: nowrap; /* Judul kolom tidak turun ke bawah */
        }

        /* Body Cell Styling */
        #productVariantTable tbody td {
            vertical-align: middle !important;
            padding: 8px !important;
        }

        /* ❌ Hapus ini - penyebab × ke kiri */
        #productVariantTable .select2-container--default .select2-selection--single {
            height: 38px !important;
            padding: 5px;
            border: 1px solid #ced4da;
            display: flex;
            align-items: center;
        }

        /* Input Styling */
        #productVariantTable .form-control {
            min-width: 150px; /* Mencegah input menciut di layar kecil */
            height: 38px;
        }

        /* Kolom Aksi */
        #productVariantTable td:last-child {
            width: 10%;
            min-width: 80px;
            text-align: center;
        }

        .btn_delete_row {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #dc3545;
            padding: 8px;
            transition: transform 0.2s;
        }

        .btn_delete_row:hover {
            color: #a71d2a;
            transform: scale(1.1);
        }

        /* Trading Select2 di-append ke body — jangan ketutup modal (z-index 1055) */
        .select2-dropdown {
            z-index: 1065 !important;
        }
        .select2-results__options {
            max-height: 260px !important;
            overflow-y: auto !important;
        }

        /* Responsive Handling */
        @media (max-width: 767.98px) {
            /* Container pembungkus harus ada class ini di HTML */
            .table-responsive {
                overflow-x: auto !important;
                -webkit-overflow-scrolling: touch;
            }

            /* Beri sedikit ruang antar baris di mobile */
            #productVariantTable tbody tr {
                border-bottom: 1px solid #eee;
            }
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
                    Bahan Mentah
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
                            <div class="table-responsive dt-pending" id="tableSupplies-wrap">
                                <div class="dt-skeleton" aria-hidden="true">
                                    <div style="padding: 16px 25px;">
                                        <span class="skel-text" style="width: 250px; height: 38px; border-radius: 20px;"></span>
                                    </div>
                                    <div class="dt-skeleton-head" style="grid-template-columns: 20% 10% 20% 12% 15% 10% 13%;">
                                        <span style="width:70%"></span>
                                        <span style="width:60%"></span>
                                        <span style="width:65%"></span>
                                        <span style="width:55%"></span>
                                        <span style="width:70%"></span>
                                        <span style="width:65%"></span>
                                        <span style="width:40%;justify-self:center"></span>
                                    </div>
                                    <div class="dt-skeleton-body">
                                        @for ($i = 0; $i < 5; $i++)
                                            <div class="dt-skeleton-row" style="grid-template-columns: 20% 10% 20% 12% 15% 10% 13%;">
                                                <span class="skel-text" style="width:75%"></span>
                                                <span class="skel-text" style="width:70%"></span>
                                                <span class="skel-text" style="width:80%"></span>
                                                <span class="skel-text" style="width:60%"></span>
                                                <span class="skel-text" style="width:70%"></span>
                                                <div style="display:flex;align-items:center;gap:8px;">
                                                    <span class="skel-avatar"></span>
                                                    <span class="skel-text" style="width:65%"></span>
                                                </div>
                                                <div style="display:flex;align-items:center;gap:6px;justify-content:center;">
                                                    <span class="skel-btn"></span>
                                                    <span class="skel-btn"></span>
                                                </div>
                                            </div>
                                        @endfor
                                    </div>
                                </div>
                                <table class="table table-center table-hover" id="tableSupplies">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Nama Bahan Mentah</th>
                                            <th>Jenis</th>
                                            <th>Variasi</th>
                                            <th>Satuan</th>
                                            <th>Deskripsi</th>
                                            <th>Dibuat Oleh</th>
                                            <th class="no-sort">Aksi</th>
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
    <script src="{{ asset('Custom_js/Backoffice/Product/Supplies.js') }}?v={{ time() }}"></script>
@endsection