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

                <div class="row">
                    <div class="col-lg-4 col-sm-6 col-12">
                        <div class="card">
                            <div class="card-body text-center">
                                <h3 class="mb-0 text-info">{{ count($added) }}</h3>
                                <p class="mb-0">File baru di rilis ini</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-6 col-12">
                        <div class="card">
                            <div class="card-body text-center">
                                <h3 class="mb-0">{{ count($changedRelease) }}</h3>
                                <p class="mb-0">File berubah di rilis ini</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-6 col-12">
                        <div class="card">
                            <div class="card-body text-center">
                                <h3 class="mb-0 text-secondary">{{ count($removedRelease) }}</h3>
                                <p class="mb-0">File dihapus di rilis ini</p>
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
                            @if (!empty($manifest['previous_commit']))
                                &middot; sebelumnya <code>{{ substr($manifest['previous_commit'], 0, 8) }}</code>
                            @endif
                        </p>
                    </div>
                </div>

                @if (count($added) > 0)
                    <div class="card border-info">
                        <div class="card-body">
                            <h5 class="text-info mb-2">File baru di rilis ini ({{ count($added) }})</h5>
                            <p>
                                Path berikut <strong>baru</strong> dibanding manifest sebelumnya — wajib ikut
                                di-upload ke server. Status di server saat ini:
                                <span class="text-success">OK {{ count($addedStatus['ok']) }}</span>,
                                <span class="text-danger">hilang {{ count($addedStatus['missing']) }}</span>,
                                <span class="text-warning">beda {{ count($addedStatus['modified']) }}</span>.
                            </p>
                            @if (count($addedStatus['missing']) > 0)
                                <p class="mb-1 fw-semibold text-danger">Belum ada di server:</p>
                                <ul>
                                    @foreach ($addedStatus['missing'] as $file)
                                        <li><code>{{ $file }}</code></li>
                                    @endforeach
                                </ul>
                            @endif
                            @if (count($addedStatus['modified']) > 0)
                                <p class="mb-1 fw-semibold text-warning">Ada tapi isinya beda:</p>
                                <ul>
                                    @foreach ($addedStatus['modified'] as $file)
                                        <li><code>{{ $file }}</code></li>
                                    @endforeach
                                </ul>
                            @endif
                            @if (count($addedStatus['ok']) > 0)
                                <details class="mb-0">
                                    <summary class="text-success" style="cursor:pointer;">
                                        Sudah OK di server ({{ count($addedStatus['ok']) }}) — klik untuk lihat
                                    </summary>
                                    <ul class="mt-2 mb-0">
                                        @foreach ($addedStatus['ok'] as $file)
                                            <li><code>{{ $file }}</code></li>
                                        @endforeach
                                    </ul>
                                </details>
                            @endif
                        </div>
                    </div>
                @elseif (!empty($manifest['previous_commit']) || array_key_exists('added', $manifest))
                    <div class="card">
                        <div class="card-body">
                            <p class="mb-0 text-muted">Tidak ada file <strong>baru</strong> di rilis ini (hanya update / hapus, atau sama dengan manifest sebelumnya).</p>
                        </div>
                    </div>
                @else
                    <div class="card">
                        <div class="card-body">
                            <p class="mb-0 text-muted">
                                Manifest lama belum punya daftar file baru. Generate ulang dengan
                                <code>php artisan deploy:manifest</code> lalu upload
                                <code>deploy/manifest.json</code> supaya delta rilis muncul di sini.
                            </p>
                        </div>
                    </div>
                @endif

                @if (count($changedRelease) > 0)
                    <div class="card">
                        <div class="card-body">
                            <h5 class="mb-2">File berubah di rilis ini ({{ count($changedRelease) }})</h5>
                            <p>
                                Hash berubah dibanding manifest sebelumnya — pastikan versi di server sudah
                                ter-timpa.
                                <span class="text-success">OK {{ count($changedStatus['ok']) }}</span>,
                                <span class="text-danger">hilang {{ count($changedStatus['missing']) }}</span>,
                                <span class="text-warning">beda {{ count($changedStatus['modified']) }}</span>.
                            </p>
                            @if (count($changedStatus['missing']) > 0 || count($changedStatus['modified']) > 0)
                                <ul class="mb-2">
                                    @foreach ($changedStatus['missing'] as $file)
                                        <li class="text-danger"><code>{{ $file }}</code> — hilang</li>
                                    @endforeach
                                    @foreach ($changedStatus['modified'] as $file)
                                        <li class="text-warning"><code>{{ $file }}</code> — belum ter-timpa</li>
                                    @endforeach
                                </ul>
                            @endif
                            @if (count($changedStatus['ok']) > 0)
                                <details class="mb-0">
                                    <summary style="cursor:pointer;">Sudah OK ({{ count($changedStatus['ok']) }})</summary>
                                    <ul class="mt-2 mb-0">
                                        @foreach ($changedStatus['ok'] as $file)
                                            <li><code>{{ $file }}</code></li>
                                        @endforeach
                                    </ul>
                                </details>
                            @endif
                        </div>
                    </div>
                @endif

                @if (count($removedRelease) > 0)
                    <div class="card">
                        <div class="card-body">
                            <h5 class="text-secondary mb-2">File dihapus di rilis ini ({{ count($removedRelease) }})</h5>
                            <p>Tidak lagi ada di manifest baru. Opsional: hapus manual di server jika masih tersisa.</p>
                            <ul class="mb-0">
                                @foreach ($removedRelease as $file)
                                    <li><code>{{ $file }}</code></li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

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
