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
    </script>
    <script src="{{ asset('Custom_js/Backoffice/ExternalApi/Logs.js') }}"></script>
@endsection
