<?php $page = 'externalApiStatus'; ?>
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
                    Status API Eksternal
                @endslot
            @endcomponent
            <!-- /Page Header -->

            <p class="mb-3">
                Kendali cepat per endpoint, lintas semua versi API. <strong>Endpoint Aktif</strong>
                mematikan endpoint tersebut untuk semua aplikasi &amp; API Key sekaligus — endpoint
                lain pada versi yang sama tidak terpengaruh. <strong>Dokumentasi Publik</strong>
                menampilkan/menyembunyikan endpoint tersebut di halaman dokumentasi tanpa login
                (<code>/api-docs</code>) untuk pengembang pihak ketiga &mdash; halaman dokumentasi di
                menu &ldquo;Dokumentasi API Eksternal&rdquo; tidak terpengaruh saklar ini.
            </p>

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
                                <table class="table table-center table-hover" id="tableApiStatus">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Endpoint</th>
                                            <th>Versi</th>
                                            <th>Kelompok</th>
                                            <th>Endpoint Aktif</th>
                                            <th>Dokumentasi Publik</th>
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
    <script src="{{ asset('Custom_js/Backoffice/ExternalApi/Status.js') }}"></script>
@endsection
