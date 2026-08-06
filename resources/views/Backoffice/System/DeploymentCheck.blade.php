<?php $page = 'deployment-check'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content">
            <div class="page-header">
                <div class="page-title">
                    <h4>Deployment Check</h4>
                    <h6>Cek kelengkapan file setelah upload manual ke server. Halaman internal -- tidak ditampilkan di sidebar.</h6>
                </div>
            </div>

            @if ($manifestMissing)
                <div class="card">
                    <div class="card-body">
                        <p class="mb-2"><strong>Belum ada <code>deploy/manifest.json</code> di server ini.</strong></p>
                        <p class="mb-0">
                            Manifest ini dibuat dari repo git (bukan di server production) dengan menjalankan:
                            <code>php artisan deploy:manifest</code>, lalu file <code>deploy/manifest.json</code> yang
                            dihasilkan WAJIB ikut di-upload bersama rilis berikutnya. Tanpa manifest, halaman ini
                            tidak punya patokan untuk tahu file mana yang seharusnya ada.
                        </p>
                    </div>
                </div>
            @else
                <div class="row">
                    <div class="col-lg-3 col-sm-6 col-12">
                        <div class="card">
                            <div class="card-body text-center">
                                <h3 class="mb-0">{{ $total }}</h3>
                                <p class="mb-0">Total file di manifest</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6 col-12">
                        <div class="card">
                            <div class="card-body text-center">
                                <h3 class="mb-0 text-success">{{ $ok }}</h3>
                                <p class="mb-0">Cocok (OK)</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6 col-12">
                        <div class="card">
                            <div class="card-body text-center">
                                <h3 class="mb-0 text-danger">{{ count($missing) }}</h3>
                                <p class="mb-0">Hilang (tidak ter-upload)</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6 col-12">
                        <div class="card">
                            <div class="card-body text-center">
                                <h3 class="mb-0 text-warning">{{ count($modified) }}</h3>
                                <p class="mb-0">Isinya beda dari manifest</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <p class="mb-0">
                            Manifest dibuat: <strong>{{ $manifest['generated_at'] ?? '-' }}</strong>
                            &middot; commit <code>{{ substr($manifest['commit'] ?? '-', 0, 8) }}</code>
                            &middot; branch <code>{{ $manifest['branch'] ?? '-' }}</code>
                        </p>
                    </div>
                </div>

                @if (count($missing) > 0)
                    <div class="card">
                        <div class="card-body">
                            <h5 class="text-danger mb-2">File yang hilang ({{ count($missing) }})</h5>
                            <p>File berikut ada di manifest (harusnya ada) tapi tidak ditemukan di server ini --
                                kemungkinan besar ke-skip saat upload manual:</p>
                            <ul class="mb-0">
                                @foreach ($missing as $file)
                                    <li><code>{{ $file }}</code></li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                @if (count($modified) > 0)
                    <div class="card">
                        <div class="card-body">
                            <h5 class="text-warning mb-2">File yang isinya beda ({{ count($modified) }})</h5>
                            <p>File berikut ada, tapi isinya tidak sama dengan yang tercatat di manifest --
                                bisa jadi versi lama yang belum ter-timpa saat upload, atau memang sengaja
                                diedit langsung di server:</p>
                            <ul class="mb-0">
                                @foreach ($modified as $file)
                                    <li><code>{{ $file }}</code></li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                @if (count($missing) === 0 && count($modified) === 0)
                    <div class="card">
                        <div class="card-body">
                            <p class="mb-0 text-success"><strong>Semua file cocok dengan manifest. Deployment ini lengkap.</strong></p>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>
@endsection
