<?php $page = 'externalApiLog'; ?>
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
                    Log API Eksternal
                @endslot
            @endcomponent
            <!-- /Page Header -->

            {{-- Ringkasan --}}
            <div class="row">
                <div class="col-xl-3 col-md-6">
                    <div class="card extapi-stat">
                        <div class="card-body">
                            <span class="extapi-stat-label">Status Pencatatan</span>
                            <div class="d-flex align-items-center justify-content-between mt-2">
                                <span class="badge {{ $loggingEnabled ? 'badge-soft-success' : 'badge-soft-secondary' }}"
                                    id="loggingStatusBadge">
                                    {{ $loggingEnabled ? 'Aktif' : 'Nonaktif' }}
                                </span>
                                @roleCan('Log API Eksternal', 'others')
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" id="toggleLogging"
                                            @checked($loggingEnabled)>
                                    </div>
                                @endroleCan
                            </div>
                            <p class="extapi-stat-note mb-0">
                                Saat nonaktif, permintaan tidak disimpan sama sekali.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card extapi-stat">
                        <div class="card-body">
                            <span class="extapi-stat-label">Total Baris Log</span>
                            <h4 class="mt-2 mb-0" id="statTotal">-</h4>
                            <p class="extapi-stat-note mb-0">Perkiraan penyimpanan: <span id="statSize">-</span></p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card extapi-stat">
                        <div class="card-body">
                            <span class="extapi-stat-label">Log Terlama</span>
                            <h6 class="mt-2 mb-0" id="statOldest">-</h6>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card extapi-stat">
                        <div class="card-body">
                            <span class="extapi-stat-label">Log Terbaru</span>
                            <h6 class="mt-2 mb-0" id="statNewest">-</h6>
                        </div>
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
                                <table class="table table-center table-hover" id="tableExternalApiLog">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Waktu</th>
                                            <th>Aplikasi</th>
                                            <th>API Key</th>
                                            <th>Metode</th>
                                            <th>Endpoint</th>
                                            <th>Status</th>
                                            <th>Durasi</th>
                                            <th>IP</th>
                                            <th>User Agent</th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                    </tbody>
                                </table>
                                @if (app()->environment('local'))
                                    <p class="text-muted mb-0 mt-2" style="font-size: 12px;">
                                        <i class="fe fe-info"></i>
                                        Klik satu baris untuk melihat detail permintaan &amp; respons lengkap
                                        (hanya tersedia di lingkungan local).
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Table -->

            @if (app()->environment('local'))
                <!-- Detail satu baris log (request/response body) — hanya ada di lingkungan local,
                     lihat App\ExternalApi\Logging\RequestLogger::write() -->
                <div class="modal fade" id="modalLogDetail" tabindex="-1">
                    <div class="modal-dialog modal-lg modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Detail Permintaan API Eksternal</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <dl class="row mb-3" style="font-size: 13px;">
                                    <dt class="col-sm-3">Waktu</dt>
                                    <dd class="col-sm-9" id="logDetailWaktu">-</dd>
                                    <dt class="col-sm-3">Endpoint</dt>
                                    <dd class="col-sm-9"><code id="logDetailEndpoint">-</code></dd>
                                    <dt class="col-sm-3">Status</dt>
                                    <dd class="col-sm-9" id="logDetailStatus">-</dd>
                                    <dt class="col-sm-3">Aplikasi / API Key</dt>
                                    <dd class="col-sm-9" id="logDetailAplikasi">-</dd>
                                </dl>

                                <h6 class="mb-2">Request Body</h6>
                                <pre class="extapi-code" id="logDetailRequestBody">-</pre>

                                <h6 class="mb-2">Response Body</h6>
                                <pre class="extapi-code mb-0" id="logDetailResponseBody">-</pre>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection

@section('custom_js')
    @php
        // Strategi pembersihan berasal dari config, jadi menambah cara baru
        // tidak menuntut perubahan pada JS maupun Blade.
        $strategiJs = [];
        foreach ($cleanupStrategies as $strategi) {
            $strategiJs[] = [
                'key' => $strategi->key,
                'label' => $strategi->label,
                'needs_date' => $strategi->needsDate(),
                'deletes_everything' => $strategi->deletesEverything(),
            ];
        }
    @endphp

    <script>
        var public = "{{ asset('') }}";
        var logCleanupStrategies = {!! json_encode($strategiJs) !!};
        var externalApiLogIsLocal = {{ app()->environment('local') ? 'true' : 'false' }};
    </script>
    <script src="{{ asset('Custom_js/Backoffice/ExternalApi/Logs.js') }}"></script>
@endsection
