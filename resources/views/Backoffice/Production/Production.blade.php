<?php $page = 'production'; ?>
@extends('layout.mainlayout')
@section('custom_css')
    <style>
        /* table-layout:fixed membuat lebar kolom (di bawah) benar-benar dipaksakan — tanpa ini,
           max-width/word-wrap di satu kolom cuma "saran" dan teks panjang tanpa jeda bisa
           meluber/tumpang tindih ke kolom sebelah (kolom Status jadi korban paling sering,
           karena badge-nya kecil dan gampang ketiban teks Keterangan yang membanjir). */
        #tableProduction {
            width: 100% !important;
            table-layout: fixed;
            min-width: 1250px;
        }
        #tableProduction thead th {
            color: #64748b !important;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .4px;
            background: #f1f5f9 !important;
            border-bottom: 1px solid #e2e8f0;
        }
        #tableProduction tbody td {
            color: #475569;
            font-size: 13px;
            vertical-align: middle;
        }
        #tableProduction tbody > tr {
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.2s ease;
        }
        #tableProduction tbody > tr:hover {
            background-color: #f8fafc;
        }
        #tableProduction td, #tableProduction th {
            word-wrap: break-word;
            overflow-wrap: break-word;
            white-space: normal;
        }
        #tableProduction td:nth-child(1), #tableProduction th:nth-child(1) { width: 10%; }
        #tableProduction td:nth-child(2), #tableProduction th:nth-child(2) { width: 10%; }
        #tableProduction td:nth-child(3), #tableProduction th:nth-child(3) { width: 19%; } /* Keterangan */
        #tableProduction td:nth-child(4), #tableProduction th:nth-child(4) { width: 8%; }  /* Status */
        #tableProduction td:nth-child(5), #tableProduction th:nth-child(5) { width: 12%; } /* Notes Pembatalan */
        #tableProduction td:nth-child(6), #tableProduction th:nth-child(6) { width: 11%; }
        #tableProduction td:nth-child(7), #tableProduction th:nth-child(7) { width: 11%; }
        #tableProduction td:nth-child(8), #tableProduction th:nth-child(8) { width: 11%; }
        #tableProduction td:nth-child(9), #tableProduction th:nth-child(9) { width: 8%; }  /* Aksi */
        #addProduction .select2-container {
            width: 100% !important;
        }

        #tableProduction thead th {
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .4px;
            background: #f1f5f9;
            border-bottom: 1px solid #e2e8f0;
        }

        #tableProduction tbody td {
            color: #475569;
            font-size: 13px;
        }

        #tableProduction td:last-child,
        #tableProduction th:last-child {
            white-space: nowrap !important;
            width: 11% !important;
            min-width: 110px !important;
        }

        #tableProduction td:last-child .d-flex {
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            gap: 8px;
            flex-wrap: nowrap;
        }

        #tableProduction td:last-child a {
            display: inline-flex !important;
            align-items: center;
            flex-shrink: 0;
        }

        #tableProduction tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.2s ease;
        }

        #tableProduction_wrapper .dataTables_scrollHead,
        #tableProduction_wrapper .dataTables_scrollBody {
            width: 100% !important;
        }

        #tableProduction-wrap {
            position: relative;
        }

        #tableProduction_wrapper .dataTables_processing {
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

        #tableProduction-wrap:not(.is-loading) .dataTables_processing {
            display: none !important;
        }

        #tableProduction-wrap.is-loading .dataTables_processing {
            display: flex !important;
        }

        #tableProduction_wrapper .dataTables_processing > div {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            border-radius: 10px;
            background: #fff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        }

        #tableProduction-wrap.is-loading tbody {
            opacity: 0.45;
            pointer-events: none;
        }

        #tableProduction .btn-action-icon {
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

        #tableProduction .btn-action-icon:hover {
            transform: scale(1.05);
            opacity: 0.9;
        }

        #addProduction .select2-container {
            width: 100% !important;
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
                    Produksi
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
                    <div class="row text-end ps-2 mb-2">
                        <div class="col-10 col-lg-11"></div>
                        <div class="col-lg-1 col-2">
                            <a class="btn btn-outline-primary LihatfotoProduksi" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Lihat Bukti Produksi">
                                <i class="fe fe-image"></i>
                            </a>
                        </div>
                    </div>
                    <div class="card card-table">
                        <div class="card-body">
                            <div class="table-responsive dt-pending" id="tableProduction-wrap" style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
                                <div class="dt-skeleton" aria-hidden="true">
                                    <div style="padding: 16px 25px;">
                                        <span class="skel-text" style="width: 250px; height: 38px; border-radius: 20px;"></span>
                                    </div>
                                    <div class="dt-skeleton-head" style="grid-template-columns: 11% 11% 13% 11% 11% 11% 11% 10% 11%;">
                                        <span style="width:60%"></span>
                                        <span style="width:70%"></span>
                                        <span style="width:50%"></span>
                                        <span style="width:60%"></span>
                                        <span style="width:70%"></span>
                                        <span style="width:50%"></span>
                                        <span style="width:60%"></span>
                                        <span style="width:50%"></span>
                                        <span style="width:40%"></span>
                                    </div>
                                    <div class="dt-skeleton-body">
                                        @for ($i = 0; $i < 5; $i++)
                                            <div class="dt-skeleton-row" style="grid-template-columns: 11% 11% 13% 11% 11% 11% 11% 10% 11%;">
                                                <span class="skel-text" style="width:70%"></span>
                                                <span class="skel-badge" style="width:60%;justify-self:center"></span>
                                                <span class="skel-text" style="width:60%"></span>
                                                <span class="skel-badge" style="width:55%;justify-self:center"></span>
                                                <span class="skel-text" style="width:70%"></span>
                                                <span class="skel-avatar" style="justify-self:start"></span>
                                                <span class="skel-avatar" style="justify-self:start"></span>
                                                <span class="skel-avatar" style="justify-self:start"></span>
                                                <span class="skel-btn" style="justify-self:center"></span>
                                            </div>
                                        @endfor
                                    </div>
                                </div>
                                <table class="table table-center table-hover mb-0" id="tableProduction">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Kode Produksi</th>
                                            <th>Keterangan</th>
                                            <th class="text-center">Status</th>
                                            <th>Notes Pembatalan</th>
                                            <th>Dibuat Oleh</th>
                                            <th>Diapprove Oleh</th>
                                            <th>Pengajuan Batal Oleh</th>
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

    {{-- modal --}}
    <div class="modal custom-modal fade" id="modalBahan" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content d-flex flex-column" style="max-height: 92vh;border:0;border-radius:16px;overflow:hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
                <div class="modal-header border-0 flex-shrink-0" style="background:linear-gradient(135deg,#1e3a8a 0%,#3b82f6 100%);padding:18px 24px;">
                    <div class="d-flex align-items-center gap-3">
                        <span style="width:40px;height:40px;display:flex;align-items:center;justify-content:center;border-radius:10px;background:rgba(255,255,255,.15);color:#fff;">
                            <i class="fe fe-box" style="font-size:18px;"></i>
                        </span>
                        <div>
                            <h5 class="mb-0 fw-bold text-white modal-title" style="font-size:16px;letter-spacing:.2px;">Detail Bahan</h5>
                            <small class="text-white-50">Daftar kebutuhan bahan mentah produksi</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white btn-close-bahan" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="#" class="d-flex flex-column h-100" style="margin: 0; min-height: 0;">
                    <div class="modal-body p-0 bg-light d-flex flex-column" style="overflow-y:auto;">
                        <div class="p-4" style="flex: 1 1 auto; background: #f8fafc;">
                            <div class="table-responsive rounded border bg-white">
                                <table class="table table-center custom-table-scroll mb-0" id="tableSupplies" style="min-height: 15vh">
                                    <thead style="background: #f1f5f9;">
                                        <tr>
                                            <th class="text-center" style="width: 15%; padding: 12px 16px; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase;">#</th>
                                            <th style="padding: 12px 16px; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase;">Nama Bahan</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top" style="background:#f8fafc;padding:14px 24px;">
                        <button type="button" class="btn btn-save-bahan ms-2 d-inline-flex align-items-center justify-content-center gap-2" style="background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;border:none;border-radius:8px;padding:9px 28px;font-size:13px;font-weight:600;min-width:160px;height:42px;box-shadow:0 4px 12px rgba(59,130,246,.3); cursor:pointer;"><i class="fe fe-save me-1"></i>Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('custom_js')
    <script>
        var public = "{{ asset('') }}";
    </script>
    <script src="{{asset('Custom_js/Backoffice/Production/Production.js')}}?v={{ time() }}"></script>
@endsection