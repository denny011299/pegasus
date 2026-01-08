<?php $page = 'view_stock_opname'; ?>
@extends('layout.mainlayout')
@section('content')
    <style>
        .table-responsive {
            display: block;
            width: 100%;
            overflow-x: auto !important;
            overflow-y: hidden;
            white-space: nowrap; /* biar kolom tidak turun ke bawah */
        }

        .table {
         min-width: 1200px; /* paksa tabel jadi lebih lebar dari layar */
        }

        .invalid{
            border: 1px solid red!important;
        }
    </style>
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    Input Stok Opname
                @endslot
            @endcomponent
            <!-- /Page Header -->

            <!-- Search Filter -->
            @component('components.search-filter')
            @endcomponent
            <!-- /Search Filter -->

            <div class="accordion-card-one accordion mt-4" id="accordionExample1">
                <div class="accordion-item">
                    <div class="accordion-header" id="headingOne">
                        <div class="accordion-button" data-bs-toggle="collapse" data-bs-target="#collapseOne"
                            aria-controls="collapseOne">
                            <div class="text-editor add-list">
                                <div class="addproduct-icon list icon">
                                    <h6><i data-feather="life-buoy" class="add-info me-2"></i><span>Header Stok Opname</span>
                                    </h6>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne"
                        data-bs-parent="#accordionExample1">
                        <div class="accordion-body">
                            <div class="row mb-5">
                                <div class="col-md-4 col-sm-12">
                                    <div class="input-blocks row-staff">
                                        <label>Nama Penanggung Jawab<span class="text-danger">*</span></label>
                                        <select name="" id="penanggung-jawab" class="form-select fill"></select>
                                    </div>
                                </div>
                                <div class="col-md-4 col-sm-12">
                                    <div class="input-blocks">
                                        <label>Tanggal<span class="text-danger">*</span></label>
                                        <input type="date" class="form-control fill" id="tanggal">
                                    </div>
                                </div>
                                <div class="col-lg-12 mt-2">
                                    <div class="input-blocks">
                                        <label>Catatan</label>
                                        <textarea class="form-control" placeholder="Masukkan catatan" id="catatan"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body p-4">
                            <div class="row">
                                <div class="col-6 text-end">
                                </div>
                            </div>
                           <div class="table-responsive" style="overflow-x: auto;">
                                <table class="table mt-3" id="tb-stock-table" style="min-width: 800px;">
                                    <thead>
                                        <tr>
                                            <td>SKU</td>
                                            <td style="width:5%">Nama</td>
                                            {{-- <td class="text-center">Stok Komp.</td> --}}
                                            <td class="text-center" style="width:35%">Stok Real</td>
                                            {{-- <td class="text-center">Selisih</td> --}}
                                            <td style="width:25%">Catatan</td>
                                        </tr>
                                    </thead>
                                    <tbody id="tbStock"></tbody>
                                </table>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <!-- /Table -->

            <div class="text-end mt-3">
                <button class="btn btn-primary btn-save">Tambah Stok Opname</button>
            </div>
        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection

@section('custom_js')
    <script>
        var public = "{{ asset('') }}";    
        var data = @json($data);
        var mode = @json($mode);
    </script>
    <script src="{{asset('Custom_js/Backoffice/Inventory/CreateStockOpnameSupplies.js')}}?v=2"></script>
@endsection