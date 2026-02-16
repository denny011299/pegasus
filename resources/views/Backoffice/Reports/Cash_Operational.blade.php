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
            <div class="row mb-4">
                <div class="col-md-4">
                    <select class="form-select" id="cashType">
                        <option value="admin">Kas Admin</option>
                        <option value="gudang">Kas Gudang</option>
                        <option value="armada">Dompet Virtual Armada</option>
                    </select>
                </div>
                <div class="col-md-8 text-end">
                    <button class="btn btn-primary btnAddCash">
                        <i class="fa fa-plus-circle me-2"></i>Tambah Aktivitas
                    </button>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-center table-hover" id="tableCash">
                                    <thead class="thead-light">
                                        <tr id="headers">
                                            <th></th>
                                            <th>Tanggal</th>
                                            <th>Staff</th>
                                            <th>Deskripsi</th>
                                            <th>Nominal</th>
                                            <th>Status</th>
                                            <th>Actions</th>
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
    <script src="{{asset('Custom_js/Backoffice/Reports/Cash_Operational.js')}}"></script>
@endsection