<?php $page = 'externalApplication'; ?>
@extends('layout.mainlayout')

@section('custom_css')
    <link rel="stylesheet" href="{{ asset('Custom_css/external-api.css') }}">
@endsection

@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    Aplikasi Eksternal
                @endslot
            @endcomponent
            <!-- /Page Header -->

            <div class="row">
                <div class="col-12">
                    <div class="extapi-intro">
                        <p class="mb-0">
                            Daftar sistem pihak ketiga yang boleh memakai API Pegasus. Setiap aplikasi punya
                            kode unik yang tidak berubah walau namanya diganti, dan bisa memiliki beberapa
                            API Key sekaligus untuk lingkungan yang berbeda.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Search Filter -->
            @component('components.search-filter')
            @endcomponent
            <!-- /Search Filter -->

            <!-- Table -->
            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-center table-hover" id="tableExternalApplication">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Nama Aplikasi</th>
                                            <th>Kode</th>
                                            <th>Perusahaan</th>
                                            <th>Kontak</th>
                                            <th>API Key</th>
                                            <th>Status</th>
                                            <th>Dibuat Oleh</th>
                                            <th>Dibuat Pada</th>
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
    <script src="{{ asset('Custom_js/Backoffice/ExternalApi/Applications.js') }}"></script>
@endsection
