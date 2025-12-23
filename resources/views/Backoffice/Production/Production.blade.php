<?php $page = 'production'; ?>
@extends('layout.mainlayout')
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
                                            <th>Produk</th>
                                            <th>SKU</th>
                                            <th>Qty</th>
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
@endsection

@section('custom_js')
    <script>
        var public = "{{ asset('') }}";
    </script>
    <script src="{{asset('Custom_js/Backoffice/Production/Production.js')}}?v={{ time() }}"></script>
@endsection