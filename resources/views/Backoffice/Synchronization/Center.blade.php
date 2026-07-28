<?php $page = 'synchronization'; ?>
@extends('layout.mainlayout')

@section('custom_css')
    <link rel="stylesheet" href="{{ asset('Custom_css/synchronization.css') }}">
@endsection

@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    Pusat Sinkronisasi
                @endslot
            @endcomponent
            <!-- /Page Header -->

            <div class="row">
                <div class="col-12">
                    <div class="sync-intro">
                        <p class="mb-0">
                            Halaman ini berisi seluruh proses sinkronisasi data master antara Pegasus dan PMO.
                            Baca keterangan setiap proses lebih dulu, lalu jalankan langkah-langkahnya secara
                            berurutan melalui panduan yang tersedia.
                        </p>
                    </div>
                </div>
            </div>

            @unless ($pmoConfigured)
                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-warning d-flex align-items-start" role="alert">
                            <i class="fe fe-alert-triangle me-2 mt-1"></i>
                            <div>
                                <strong>Server PMO belum dikonfigurasi.</strong>
                                Isi <code>PMO_BASE_URL</code> pada file <code>.env</code> agar sinkronisasi bisa
                                dijalankan. Halaman panduan tetap bisa dibuka untuk dipelajari.
                            </div>
                        </div>
                    </div>
                </div>
            @endunless

            <div class="row">
                @forelse ($flows as $flow)
                    <div class="col-xl-6 col-lg-12 d-flex">
                        <div class="card sync-flow-card w-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start mb-3">
                                    <span class="sync-flow-icon">
                                        <i class="fe fe-{{ $flow->icon() }}"></i>
                                    </span>
                                    <div class="ms-3">
                                        <h5 class="mb-1">{{ $flow->title() }}</h5>
                                        <p class="text-muted mb-0">{{ $flow->description() }}</p>
                                    </div>
                                </div>

                                <div class="sync-flow-section">
                                    <h6><i class="fe fe-target me-1"></i> Tujuan</h6>
                                    <p class="mb-0">{{ $flow->purpose() }}</p>
                                </div>

                                <div class="sync-flow-section">
                                    <h6><i class="fe fe-database me-1"></i> Data yang Disinkronkan</h6>
                                    <ul class="sync-flow-list mb-0">
                                        @foreach ($flow->dataSynced() as $data)
                                            <li>{{ $data }}</li>
                                        @endforeach
                                    </ul>
                                </div>

                                <div class="sync-flow-section">
                                    <h6><i class="fe fe-clock me-1"></i> Kapan Dijalankan</h6>
                                    <p class="mb-0">{{ $flow->whenToRun() }}</p>
                                </div>

                                @if ($flow->warnings())
                                    <div class="sync-flow-section sync-flow-warning">
                                        <h6><i class="fe fe-alert-triangle me-1"></i> Catatan Penting</h6>
                                        <ul class="sync-flow-list mb-0">
                                            @foreach ($flow->warnings() as $warning)
                                                <li>{{ $warning }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <div class="d-flex align-items-center justify-content-between mt-4">
                                    <span class="badge badge-soft-secondary">
                                        {{ count($flow->steps()) }} langkah
                                    </span>
                                    <a href="{{ url('synchronization/'.$flow->key()) }}" class="btn btn-primary">
                                        <i class="fe fe-play-circle me-2"></i>Mulai Sinkronisasi
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body text-center text-muted py-5">
                                Belum ada alur sinkronisasi yang terdaftar.
                            </div>
                        </div>
                    </div>
                @endforelse
            </div>

        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection
