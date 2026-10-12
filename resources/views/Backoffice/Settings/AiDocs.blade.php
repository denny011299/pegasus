<?php $page = 'settings-ai-docs'; ?>
@extends('layout.mainlayout')
@section('content')
    {{-- Isi dari AssistantGuide::sections(); JSON yang sama di /settings/ai-docs/data. Tampilan sengaja polos. --}}
    <div class="page-wrapper">
        <div class="content container-fluid">
            @component('components.page-header')
                @slot('title')
                    Dokumentasi Asisten AI
                @endslot
            @endcomponent

            @php $r = $guide['ringkasan']; @endphp
            <div class="card">
                <div class="card-body">
                    <h5>{{ $r['nama'] }}</h5>
                    <p class="mb-2">{{ $r['tujuan'] }}</p>
                    <ul class="mb-0">
                        <li>Akses: {{ $r['akses'] }}</li>
                        <li>Model teks: {{ $r['model'] }} · Model gambar: {{ $r['model_gambar'] }}</li>
                        <li>Baris data dikirim ke AI per pencarian: {{ $r['batas_baris_ke_model'] }} · Konteks: {{ $r['riwayat_konteks'] }}</li>
                        <li>Data mentah halaman ini: <a href="{{ url('settings/ai-docs/data') }}" target="_blank">JSON</a></li>
                    </ul>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5>Aturan Jawaban</h5>
                    <ol class="mb-0">
                        @foreach ($guide['aturan_jawaban'] as $rule)
                            <li>{{ $rule }}</li>
                        @endforeach
                    </ol>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5>Kemampuan ({{ count($guide['kemampuan']) }} tool, semua hanya membaca)</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered align-top">
                            <thead>
                                <tr><th>Fitur</th><th>Kegunaan</th><th>Contoh Pertanyaan</th><th>Parameter</th><th>Catatan</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($guide['kemampuan'] as $t)
                                    <tr>
                                        <td><strong>{{ $t['judul'] }}</strong><br><code>{{ $t['tool'] }}</code></td>
                                        <td>{{ $t['kegunaan'] }}</td>
                                        <td>
                                            @foreach ($t['contoh'] as $ex)
                                                <div>“{{ $ex }}”</div>
                                            @endforeach
                                        </td>
                                        <td>
                                            @foreach ($t['parameter'] as $p)
                                                <div><code>{{ $p['nama'] }}</code>@if ($p['wajib']) <span class="text-danger">*</span>@endif — {{ $p['keterangan'] }}</div>
                                            @endforeach
                                        </td>
                                        <td>{{ $t['catatan'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5>Perkiraan Penyebab Kejanggalan</h5>
                    <p>Persentase dasar per jenis kejanggalan, lalu digeser oleh sinyal di data. Total selalu 100%. Penyebab hanya operasional.</p>
                    @foreach ($guide['perkiraan_penyebab'] as $c)
                        <h6 class="mt-3">{{ $c['jenis'] }}</h6>
                        <table class="table table-sm table-bordered">
                            <thead><tr><th>Penyebab</th><th>Bobot Dasar</th><th>Penjelasan (bila user minta detail)</th></tr></thead>
                            <tbody>
                                @foreach ($c['penyebab'] as $p)
                                    <tr><td>{{ $p['penyebab'] }}</td><td>{{ $p['bobot_dasar'] }}%</td><td>{{ $p['alasan'] }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                        @if ($c['sinyal'])
                            <div class="small">Sinyal yang menggeser persentase:</div>
                            <ul class="small">
                                @foreach ($c['sinyal'] as $s)
                                    <li>{{ $s }}</li>
                                @endforeach
                            </ul>
                        @endif
                    @endforeach
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5>Panduan Menu yang Dipakai AI ({{ count($guide['panduan_menu']) }})</h5>
                    <p>Sumber jawaban "cara input". Setelah file panduan diubah, jalankan <code>php artisan ai:index-docs --force</code>.</p>
                    <table class="table table-sm table-bordered">
                        <thead><tr><th>Panduan</th><th>Bagian</th><th>Status</th></tr></thead>
                        <tbody>
                            @foreach ($guide['panduan_menu'] as $d)
                                <tr>
                                    <td>{{ $d['judul'] }}<br><small><code>{{ $d['file'] }}</code></small></td>
                                    <td>{{ implode(' · ', $d['bagian']) }}</td>
                                    <td>{{ $d['diindeks'] ? 'Dipakai' : 'Belum (masih [UNCERTAIN])' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5>Modul Data yang Bisa Dibaca ({{ count($guide['modul_data']) }})</h5>
                    <p class="mb-0">{{ implode(' · ', $guide['modul_data']) }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection
