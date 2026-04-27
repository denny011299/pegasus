<?php $page = 'supplies'; ?>
@extends('layout.mainlayout')
@section('custom_css')
    <style>
        #tableSupplies {
            width: 100% !important;
            min-width: 800px;
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
            top: 50% !important;
            transform: translateY(-50%) !important;
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
            top: 35% !important;
            transform: translateY(-50%) !important;
            margin: 0 !important;
            line-height: 1 !important;
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
                            <div class="table-responsive">
                                <table class="table table-center table-hover" id="tableSupplies">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Nama Bahan Mentah</th>
                                            <th>Variasi</th>
                                            <th>Satuan</th>
                                            <th>Deskripsi</th>
                                            <th>Dibuat Oleh</th>
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
            <!-- /Table -->

        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection

@section('custom_js')
    <script>
        var public = "{{ asset('') }}";    
    </script>
    <script src="{{asset('Custom_js/Backoffice/Product/Supplies.js')}}?v=1.2"></script>
@endsection