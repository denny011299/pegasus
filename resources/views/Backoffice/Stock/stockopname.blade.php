<?php $page = 'manage-stocks'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content">
            @component('components.breadcrumb')
                @slot('title')
                    Stock Opname
                @endslot
                @slot('li_1')
                    Pengaturan Stock Opname
                @endslot
                @slot('li_2')
                    Lakukan Stock Opname
                @endslot
            @endcomponent

            <!-- /product list -->
            <div class="card table-list-card">
                <div class="card-body">
                    <div class="table-top">
                        <div class="search-set">
                            <div class="search-input">
                                <a href="" class="btn btn-searchset"><i data-feather="search"
                                        class="feather-search"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table  datanew" id='stock-opname-table'>
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Kode</th>
                                    <th>Store</th>
                                    <th>Tanggal Opname</th>
                                    <th>Penanggung Jawab</th>
                                    <th>ID Opname</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- /product list -->
        </div>
    </div>
@endsection
@section('custom_js')
    <script>
        var public = "{{ asset('') }}";
    </script>
    <script src="{{ asset('/Custom_js/Backoffice/Product/StockOpname.js') }}?v={{ time() }}"></script>
@endsection
