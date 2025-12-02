<?php $page = 'store-list'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content">
            @component('components.breadcrumb')
                @slot('title')
                    Daftar Toko
                @endslot
                @slot('li_1')
                    Kelola Toko Anda
                @endslot
                @slot('li_2')
                    Tambah Toko
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

                    <!-- /Filter -->
                    <div class="table-responsive">
                        <table class="table " id="tableStore">
                            <thead>
                                <tr>
                                    <th>Nama Toko</th>
                                    <th>Contact Person</th>
                                    <th>CP Telepon</th>
                                    <th>City Name</th>
                                    <th>Create On</th>
                                    <th>Status</th>
                                    <th class="no-sort">Aksi</th>
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
    <script src="{{ asset('/Custom_js/Backoffice/Outlet/Store.js') }}?v={{ time() }}"></script>
@endsection
