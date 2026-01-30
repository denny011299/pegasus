<?php $page = 'production'; ?>
@extends('layout.mainlayout')
@section('content')
    <style>
        table.dataTable td:nth-child(5) {
            max-width: 250px;       /* Batasi lebar maksimal */
            word-wrap: break-word;  /* Paksa teks turun */
            white-space: normal;    /* Pastikan teks tidak satu baris terus */
        }
        #addProduction .select2-container {
            width: 100% !important;
        }
    </style>
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
                    <div class="row text-end ps-2 mb-2 mt-2">
                        <div class="col-5 col-lg-8"></div>
                        <div class="col-lg-1 col-2">
                            <a class="btn btn-outline-primary LihatfotoProduksi" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Lihat Bukti Produksi">
                                <i class="fe fe-image"></i>
                            </a>
                        </div>
                        <div class="col-5 col-lg-3">
                            <input type="date" class="form-control fill" id="date_production" >
                        </div>
                    </div>
                    <div class=" card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-center table-hover" id="tableProduction">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Kode Produksi</th>
                                            <th>Status</th>
                                            <th>Notes Pembatalan</th>
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

    {{-- modal --}}
    <div class="modal modal-lg custom-modal fade" id="modalBahan" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content ">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Detail Bahan</h4>
                    </div>
                    <button type="button" class="btn-close btn-close-bahan" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                {{-- <div class="col-lg-6 col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Tanggal</label>
                                        <input type="date" class="form-control fill" id="production_date">
                                    </div>
                                </div> --}}
                                <div class="col-lg-6"></div>
                                <div class="col-12 py-3 mb-3">
                                    <table class="table table-center" id="tableSupplies" style="min-height: 15vh">
                                        <thead>
                                            <th class="text-center">#</th>
                                            <th>Nama Bahan</th>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a class="btn btn-primary btn-save-bahan">Simpan Perubahan</a>
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