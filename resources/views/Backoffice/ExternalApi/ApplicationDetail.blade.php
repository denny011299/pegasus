<?php $page = 'externalApplicationDetail'; ?>
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
                    API Key — {{ $application->application_name }}
                @endslot
            @endcomponent
            <!-- /Page Header -->

            <div class="row">
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="mb-3">Informasi Aplikasi</h6>
                            <dl class="extapi-detail mb-0">
                                <dt>Nama</dt>
                                <dd>{{ $application->application_name }}</dd>

                                <dt>Kode</dt>
                                <dd><code>{{ $application->application_code }}</code></dd>

                                <dt>Perusahaan</dt>
                                <dd>{{ $application->company ?: '-' }}</dd>

                                <dt>Kontak</dt>
                                <dd>{{ $application->contact_name ?: '-' }}</dd>

                                <dt>Email</dt>
                                <dd>{{ $application->contact_email ?: '-' }}</dd>

                                <dt>Status</dt>
                                <dd>
                                    @if ($application->application_status === 'active')
                                        <span class="badge badge-soft-success">Aktif</span>
                                    @else
                                        <span class="badge badge-soft-danger">Nonaktif</span>
                                    @endif
                                </dd>

                                <dt>Keterangan</dt>
                                <dd>{{ $application->description ?: '-' }}</dd>
                            </dl>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <h6 class="mb-2"><i class="fe fe-shield me-1"></i> Cara Memakai Kunci</h6>
                            <p class="text-muted mb-2">
                                Kunci dikirim pada setiap permintaan lewat header berikut:
                            </p>
                            <pre class="extapi-code mb-0">{{ $keyHeader }}: ext_live_xxxxxxxx</pre>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    @if ($application->application_status !== 'active')
                        <div class="alert alert-warning d-flex align-items-start" role="alert">
                            <i class="fe fe-alert-triangle me-2 mt-1"></i>
                            <div>
                                <strong>Aplikasi sedang dinonaktifkan.</strong>
                                Seluruh API Key di bawah ini ditolak saat dipakai, berapa pun jumlah kunci yang aktif.
                            </div>
                        </div>
                    @endif

                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-center table-hover" id="tableExternalApiKey">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Nama</th>
                                            <th>Lingkungan</th>
                                            <th>Kunci</th>
                                            <th>Status</th>
                                            <th>Kedaluwarsa</th>
                                            <th>Terakhir Dipakai</th>
                                            <th>Dibuat Oleh</th>
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

        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection

@section('custom_js')
    <script>
        var public = "{{ asset('') }}";
        var externalApplicationId = {{ (int) $application->external_application_id }};
    </script>
    <script src="{{ asset('Custom_js/Backoffice/ExternalApi/ApplicationDetail.js') }}"></script>
@endsection
