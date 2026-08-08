<?php $page = 'apiDocsPublic'; ?>
@extends('layout.mainlayout')

{{--
    Versi publik dari Backoffice.ExternalApi.Documentation — dipakai pengembang
    pihak ketiga yang belum/tidak punya akun Pegasus, jadi tanpa login dan tanpa
    chrome admin (sidebar + topbar). Isinya sama persis (partials doc-nav /
    doc-general / doc-group), hanya headernya beda: bar minimal berlogo, bukan
    topbar admin. mainlayout.blade.php sudah diberi tahu untuk menyembunyikan
    header & sidebar untuk rute 'apiDocsPublic' / 'apiDocsPublicGroup'.
--}}

@section('custom_css')
    <link rel="stylesheet" href="{{ asset('Custom_css/external-api.css') }}">
    <style>
        /* Tanpa sidebar/topbar admin, .page-wrapper tidak perlu menyisakan
           ruang untuk keduanya. */
        .public-docs-wrapper .page-wrapper {
            margin-left: 0;
            padding-top: 0;
        }

        .public-docs-header {
            display: flex;
            align-items: center;
            padding: 16px 24px;
            border-bottom: 1px solid #e5e9f2;
            background: #fff;
        }

        .public-docs-header img {
            height: 32px;
        }

        .public-docs-header span {
            margin-left: 12px;
            font-weight: 600;
            font-size: 16px;
            color: #202c4b;
        }
    </style>
@endsection

@section('content')
    <div class="public-docs-wrapper">
        <div class="public-docs-header">
            <img src="{{ asset('assets/pegasus_banner_small.png') }}" alt="Pegasus">
            <span>Dokumentasi API Eksternal</span>
        </div>

        <div class="page-wrapper">
            <div class="content container-fluid">

                @component('components.page-header')
                    @slot('title')
                        Dokumentasi API Eksternal{{ $current ? ' — ' . $current['title'] : '' }}
                    @endslot
                @endcomponent

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
    </div>
@endsection

@section('custom_js')
    <script>
        // Dipakai pemilih versi untuk tetap berada di modul yang sedang dibuka.
        var docGroupKey = @json($current['key'] ?? null);
        // Base path rute halaman Umum — beda antara versi admin dan publik.
        var docBasePath = @json(route('apiDocsPublic'));
    </script>
    <script src="{{ asset('Custom_js/Backoffice/ExternalApi/Documentation.js') }}"></script>
@endsection
