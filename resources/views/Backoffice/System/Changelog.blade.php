<?php $page = 'changelog'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content">
            <div class="page-header">
                <div class="page-title">
                    <h4>Change Log</h4>
                    <h6>Riwayat perubahan aplikasi per rilis. Halaman internal -- tidak ditampilkan di sidebar.</h6>
                </div>
            </div>

            @if (empty($releases))
                <div class="card">
                    <div class="card-body">
                        <p class="mb-0">Belum ada catatan perubahan di <code>config/changelog.php</code>.</p>
                    </div>
                </div>
            @else
                @foreach ($releases as $release)
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between flex-wrap mb-2">
                                <h5 class="mb-0">{{ $release['title'] ?? '-' }}</h5>
                                <span class="badge bg-primary">{{ $release['date'] ?? '-' }}</span>
                            </div>
                            @if (!empty($release['changes']))
                                <ul class="mb-0" style="list-style: disc; padding-left: 1.2rem;">
                                    @foreach ($release['changes'] as $change)
                                        {{-- Each item is either a plain string, or ['text' => ..., 'refs' => [issue/PR numbers]]
                                             -- see the format note at the top of config/changelog.php. --}}
                                        @php
                                            $text = is_array($change) ? ($change['text'] ?? '') : $change;
                                            $refs = is_array($change) ? ($change['refs'] ?? []) : [];
                                        @endphp
                                        <li style="list-style: disc;">
                                            {{ $text }}
                                            @if (!empty($refs) && $repoUrl)
                                                @foreach ($refs as $ref)
                                                    <a href="{{ $repoUrl }}/issues/{{ $ref }}" target="_blank" rel="noopener noreferrer">#{{ $ref }}</a>{{ !$loop->last ? ' ' : '' }}
                                                @endforeach
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
@endsection
