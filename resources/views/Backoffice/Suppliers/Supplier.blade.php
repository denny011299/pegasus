<?php $page = 'pemasok'; ?>
@extends('layout.mainlayout')
@section('custom_css')
    <style>
        .table-scroll {
            max-height: 45vh;
            overflow-y: auto;
            overflow-x: hidden; 
        }

        #tablePo {
            width: 100%;
            border-collapse: collapse;
        }

        #tablePo thead th {
            position: sticky;
            top: 0;
            background-color: #e7f1ff;
            z-index: 10;
            border-bottom: 2px solid #dee2e6;
            padding: 12px 8px;
        }

        #tablePo tbody td {
            padding: 10px 8px;
            vertical-align: middle;
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
                    Pemasok
                @endslot
            @endcomponent
            <!-- /Page Header -->

            <!-- Search Filter -->
            @component('components.search-filter')
            @endcomponent
            <!-- /Search Filter -->

            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-center table-hover" id="tableSupplier">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Nama Pemasok</th>
                                            <th>Kode Pemasok</th>
                                            <th>Telepon</th>
                                            <th>Kota</th>
                                            <th>Total Hutang</th>
                                            <th>Dibuat Pada</th>
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
        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection

@section('custom_js')
    <script>
        var public = "{{ asset('') }}";    
    </script>
    <script src="{{asset('Custom_js/Backoffice/Suppliers/Supplier.js')}}"></script>
@endsection
