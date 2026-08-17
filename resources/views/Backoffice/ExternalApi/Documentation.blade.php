<?php $page = 'externalApiDocumentation'; ?>
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
                    Dokumentasi API Eksternal{{ $current ? ' — ' . $current['title'] : '' }}
                @endslot
            @endcomponent
            <!-- /Page Header -->

            @if (!$current)
                <div class="row">
                    <div class="col-12">
                        <div class="extapi-intro d-flex align-items-start">
                            <i class="fe fe-lock me-2 mt-1"></i>
                            <p class="mb-0">
                                Halaman ini hanya untuk internal dan tidak dapat diakses publik. Isinya dibangun
                                otomatis dari definisi dokumentasi di dalam kode, jadi setiap endpoint baru langsung
                                muncul di sini tanpa perlu menulis halaman terpisah.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="row">
                {{-- Navigasi antar halaman dokumentasi --}}
                <div class="col-xl-3 col-lg-4">
                    @include('Backoffice.ExternalApi.partials.doc-nav')
                </div>

                {{-- Isi halaman: Umum, atau satu modul --}}
                <div class="col-xl-9 col-lg-8">
                    @if ($current)
                        @include('Backoffice.ExternalApi.partials.doc-group')
                    @else
                        @include('Backoffice.ExternalApi.partials.doc-general')
                    @endif
                </div>
            </div>

        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection

@section('custom_js')
    <script>
        // Dipakai pemilih versi untuk tetap berada di modul yang sedang dibuka.
        var docGroupKey = @json($current['key'] ?? null);
        // Base path rute halaman Umum — beda antara versi admin dan publik.
        var docBasePath = @json(route('externalApiDocumentation'));
    </script>
    <script src="{{ asset('Custom_js/Backoffice/ExternalApi/Documentation.js') }}"></script>
@endsection
