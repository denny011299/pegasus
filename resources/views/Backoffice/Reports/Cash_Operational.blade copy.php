<?php $page = 'cash_operational'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    Kas Operasional
                @endslot
            @endcomponent
            <!-- /Page Header -->

            <!-- Search Filter -->
            @component('components.search-filter')
            @endcomponent
            <!-- /Search Filter -->

            <!-- Table -->
            <ul class="nav nav-tabs" id="cashTab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab1" type="button">
                        Kas Admin
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab2" type="button">
                        Kas Gudang
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab2" type="button">
                        Dompet Virtual Armada
                    </button>
                </li>
            </ul>
            <div class="tab-content pt-1">
                <div class="tab-pane fade show active" id="tab1">
                    <div class="row text-end my-3">
                        <li>
                            <a class="btn btn-primary btnAddAdmin"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Tambah
                                Kas Admin</a>
                        </li>
                    </div>
                    <div class="row">
                        <div class="col-sm-12">
                            <div class=" card-table">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-center table-hover" id="tableCash">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Tanggal</th>
                                                    <th>Deskripsi</th>
                                                    <th>Debit</th>
                                                    <th>Kredit 1</th>
                                                    <th>Kredit 2</th>
                                                    <th>Saldo</th>
                                                    <th>Actions</th>
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

                <div class="tab-pane fade" id="tab2">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class=" card-table">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-center table-hover" id="tableCash">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Tanggal</th>
                                                    <th>Deskripsi</th>
                                                    <th>Debit</th>
                                                    <th>Kredit</th>
                                                    <th>Saldo</th>
                                                    <th>Actions</th>
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
    <script src="{{asset('Custom_js/Backoffice/Reports/Cash_Operational.js')}}"></script>
@endsection