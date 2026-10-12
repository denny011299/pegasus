<?php $page = 'settings-ai-docs'; ?>
@extends('layout.mainlayout')
@section('content')
    {{-- Untuk user. Isi dari AssistantGuide::sections(); detail teknis ada di docs/AI_ASSISTANT_TOOLS.md. Tampilan sengaja polos. --}}
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
                        <li>{{ $r['cara_pakai'] }}</li>
                        <li>{{ $r['akses'] }}</li>
                    </ul>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5>Apa yang Bisa Ditanyakan</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered align-top">
                            <thead>
                                <tr><th>Kemampuan</th><th>Kegunaan</th><th>Contoh Pertanyaan</th><th>Catatan</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($guide['kemampuan'] as $t)
                                    <tr>
                                        <td><strong>{{ $t['judul'] }}</strong></td>
                                        <td>{{ $t['kegunaan'] }}</td>
                                        <td>
                                            @foreach ($t['contoh'] as $ex)
                                                <div>“{{ $ex }}”</div>
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
                    <h5>FAQ</h5>
                    @foreach ($guide['faq'] as $f)
                        <div class="mb-3">
                            <strong>{{ $f['tanya'] }}</strong>
                            <div>{{ $f['jawab'] }}</div>
                            @if (!empty($f['contoh_jawaban']))
                                <div class="small text-muted mt-1">Contoh jawaban AI:</div>
                                <div class="small" style="white-space: pre-line">{{ $f['contoh_jawaban'] }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5>Menu yang Bisa Diakses AI ({{ count($guide['menu']) }})</h5>
                    <p>AI bisa membaca data menu berikut dan memandu cara input sesuai panduan yang tersedia.</p>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-top">
                            <thead><tr><th>Menu</th><th>Data yang Dibaca AI</th><th>Panduan yang Tersedia</th><th>Catatan</th></tr></thead>
                            <tbody>
                                @foreach ($guide['menu'] as $m)
                                    <tr>
                                        <td><strong>{{ $m['menu'] }}</strong></td>
                                        <td>{{ $m['data'] ? implode(', ', $m['data']) : '-' }}</td>
                                        <td>{{ $m['panduan'] ? implode(' · ', $m['panduan']) : '-' }}</td>
                                        <td>{{ $m['catatan'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
