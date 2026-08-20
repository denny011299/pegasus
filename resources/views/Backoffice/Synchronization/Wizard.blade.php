<?php $page = 'synchronizationWizard'; ?>
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
                    {{ $flow->title() }}
                @endslot
            @endcomponent
            <!-- /Page Header -->

            @unless ($pmoConfigured)
                <div class="alert alert-warning d-flex align-items-start" role="alert">
                    <i class="fe fe-alert-triangle me-2 mt-1"></i>
                    <div>
                        <strong>Server PMO belum dikonfigurasi.</strong>
                        Tombol eksekusi dinonaktifkan sampai <code>PMO_BASE_URL</code> diisi pada file
                        <code>.env</code>. Seluruh panduan di bawah tetap bisa dibaca.
                    </div>
                </div>
            @endunless

            <div class="row">
                <!-- Daftar langkah + progres -->
                <div class="col-xl-4 col-lg-5">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h6 class="mb-0">Progres Sinkronisasi</h6>
                                <span class="text-muted" id="syncProgressText">
                                    {{ $state['completed'] }} / {{ $state['total'] }} selesai
                                </span>
                            </div>
                            <div class="progress sync-progress mb-4">
                                <div class="progress-bar bg-success" id="syncProgressBar" role="progressbar"
                                    style="width: {{ $state['progress'] }}%"
                                    aria-valuenow="{{ $state['progress'] }}" aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>

                            <ul class="sync-stepper" id="syncStepper">
                                @foreach ($steps as $index => $step)
                                    <li data-step="{{ $step->key }}" data-index="{{ $index }}">
                                        <span class="sync-stepper-marker">{{ $index + 1 }}</span>
                                        <span class="sync-stepper-title">{{ $step->title }}</span>
                                        <span class="sync-stepper-status">Belum Dijalankan</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <h6 class="mb-2">Cara Kerja Panduan Ini</h6>
                            <p class="text-muted mb-2" style="font-size: 13px;">
                                Setiap langkah dijalankan manual satu per satu. Menjalankan satu langkah tidak
                                akan otomatis melanjutkan ke langkah berikutnya — perpindahan langkah
                                sepenuhnya Anda yang menentukan.
                            </p>
                            <p class="text-muted mb-0" style="font-size: 13px;">
                                Hasil eksekusi tersimpan, jadi tetap terlihat meskipun halaman ini dibuka ulang.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Panel langkah aktif -->
                <div class="col-xl-8 col-lg-7">
                    @foreach ($steps as $index => $step)
                        <div class="sync-step-pane {{ $index === 0 ? 'is-active' : '' }}"
                            data-step="{{ $step->key }}" data-index="{{ $index }}"
                            data-paginated="{{ $step->paginated ? '1' : '0' }}">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex align-items-start justify-content-between">
                                        <div>
                                            <span class="sync-step-index">
                                                Langkah {{ $index + 1 }} dari {{ count($steps) }}
                                            </span>
                                            <h5 class="mt-1 mb-1">{{ $step->title }}</h5>
                                            <p class="text-muted mb-0">{{ $step->description }}</p>
                                        </div>
                                        <span class="badge badge-soft-secondary sync-step-badge">Belum Dijalankan</span>
                                    </div>

                                    <div class="sync-info-grid">
                                        <div class="sync-info-box">
                                            <h6>Data yang Disinkronkan</h6>
                                            <p>{{ $step->dataSynced }}</p>
                                        </div>
                                        <div class="sync-info-box">
                                            <h6>Kenapa Dibutuhkan</h6>
                                            <p>{{ $step->why }}</p>
                                        </div>
                                        <div class="sync-info-box">
                                            <h6>Data yang Bergantung</h6>
                                            <p>{{ $step->dependents }}</p>
                                        </div>
                                        <div class="sync-info-box">
                                            <h6>Setelah Dijalankan</h6>
                                            <p>{{ $step->expectation }}</p>
                                        </div>
                                    </div>

                                    @if ($step->notes)
                                        <div class="alert alert-light border mt-3 mb-0" style="font-size: 13px;">
                                            <ul class="sync-flow-list mb-0">
                                                @foreach ($step->notes as $note)
                                                    <li>{{ $note }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Prasyarat -->
                            <div class="card sync-prereq-card">
                                <div class="card-body">
                                    <h6 class="mb-2">Prasyarat</h6>
                                    @if ($step->prerequisites)
                                        <ul class="sync-prereq-list"></ul>
                                    @else
                                        <p class="text-muted mb-0" style="font-size: 13px;">
                                            Langkah ini tidak memiliki prasyarat dan bisa dijalankan kapan saja.
                                        </p>
                                    @endif
                                    <div class="alert alert-warning mt-3 mb-0 sync-blocked-reason d-none"
                                        style="font-size: 13px;"></div>
                                </div>
                            </div>

                            <!-- Eksekusi -->
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                        <div>
                                            <h6 class="mb-1">Status Saat Ini</h6>
                                            <span class="badge badge-soft-secondary sync-step-badge">Belum Dijalankan</span>
                                        </div>
                                        <button type="button" class="btn btn-primary btn-execute-step"
                                            data-step="{{ $step->key }}">
                                            <i class="fe fe-refresh-cw me-2"></i>Jalankan Sinkronisasi
                                        </button>
                                    </div>

                                    @if ($step->paginated)
                                        <!-- Progres per halaman (PMO berbasis halaman) -->
                                        <div class="sync-page-progress d-none mt-4">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <span class="sync-page-progress-text text-muted">Mengambil halaman…</span>
                                                <span class="sync-page-progress-rows text-muted">-</span>
                                            </div>
                                            <div class="progress sync-progress">
                                                <div class="progress-bar bg-info sync-page-progress-bar"
                                                    role="progressbar" style="width: 0%"
                                                    aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Hasil eksekusi -->
                                    <div class="sync-result d-none mt-4">
                                        <div class="sync-result-message mb-3"></div>

                                        <h6 class="mb-2">Ringkasan Eksekusi</h6>
                                        <div class="table-responsive mb-3">
                                            <table class="table table-sm table-borderless sync-summary-table mb-0">
                                                <tbody>
                                                    <tr>
                                                        <th>Dimulai</th>
                                                        <td class="sync-started-at">-</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Selesai</th>
                                                        <td class="sync-finished-at">-</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Durasi</th>
                                                        <td class="sync-duration">-</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Dijalankan Oleh</th>
                                                        <td class="sync-executed-by">-</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <h6 class="mb-2">Hasil Sinkronisasi</h6>
                                        <div class="sync-result-metrics mb-3">
                                            <div class="sync-metric">
                                                <span class="sync-metric-value sync-total-processed">0</span>
                                                <span class="sync-metric-label">Diproses</span>
                                            </div>
                                            <div class="sync-metric is-inserted">
                                                <span class="sync-metric-value sync-total-inserted">0</span>
                                                <span class="sync-metric-label">Ditambahkan</span>
                                            </div>
                                            <div class="sync-metric is-updated">
                                                <span class="sync-metric-value sync-total-updated">0</span>
                                                <span class="sync-metric-label">Diperbarui</span>
                                            </div>
                                            <div class="sync-metric is-failed">
                                                <span class="sync-metric-value sync-total-failed">0</span>
                                                <span class="sync-metric-label">Gagal</span>
                                            </div>
                                            <div class="sync-metric is-skipped">
                                                <span class="sync-metric-value sync-total-skipped">0</span>
                                                <span class="sync-metric-label">Dilewati</span>
                                            </div>
                                        </div>

                                        <div class="sync-details d-none">
                                            <h6 class="mb-2">Informasi Tambahan dari PMO</h6>
                                            <div class="table-responsive mb-3">
                                                <table class="table table-sm table-borderless sync-summary-table mb-0">
                                                    <tbody class="sync-details-body"></tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <div class="sync-notices d-none">
                                            <h6 class="mb-2">Catatan</h6>
                                            <ul class="sync-notice-list"></ul>
                                        </div>

                                        <div class="sync-errors d-none">
                                            <h6 class="mb-2">Rincian Masalah</h6>
                                            <ul class="sync-error-list"></ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <!-- Navigasi antar langkah -->
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <button type="button" class="btn btn-outline-secondary" id="btnPrevStep">
                            <i class="fe fe-chevron-left me-2"></i>Langkah Sebelumnya
                        </button>
                        <span class="text-muted" id="syncStepCounter">1 / {{ count($steps) }}</span>
                        <button type="button" class="btn btn-outline-primary" id="btnNextStep">
                            Langkah Berikutnya<i class="fe fe-chevron-right ms-2"></i>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection

@section('custom_js')
    <script>
        var syncFlowKey = @json($flow->key());
        var syncStepKeys = @json(collect($steps)->pluck('key')->values());
        var syncInitialState = @json($state);
    </script>
    <script src="{{ asset('Custom_js/Backoffice/Synchronization/Wizard.js') }}"></script>
@endsection
