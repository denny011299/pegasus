{{--
    Navigasi dokumentasi.

    Daftar halaman dibangun dari registry, jadi kelompok endpoint baru langsung
    muncul di sini begitu didaftarkan — tidak ada daftar menu yang perlu
    disunting manual.

    Pilihan versi ikut dibawa antar halaman lewat query string agar pengguna
    tidak terlempar balik ke versi bawaan saat berpindah modul.
--}}
@php
    $versionQuery = count($versions) > 1 ? '?version=' . urlencode($version) : '';
    $isUmum = $current === null;
@endphp

<div class="card extapi-toc">
    <div class="card-body">
        @if (count($versions) > 1)
            <div class="input-block mb-3">
                <label>Versi API</label>
                <select class="form-control" id="docVersion">
                    @foreach ($versions as $v)
                        <option value="{{ $v }}" @selected($v === $version)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <h6 class="extapi-toc-title">Dokumentasi</h6>
        <ul class="extapi-toc-list">
            <li>
                <a class="{{ $isUmum ? 'active' : '' }}"
                    href="{{ url('externalApiDocumentation') . $versionQuery }}">
                    <i class="fe fe-book-open me-1"></i> Umum
                </a>
            </li>
            @foreach ($groups as $group)
                @php $isActive = $current !== null && $current['key'] === $group['key']; @endphp
                <li>
                    <a class="{{ $isActive ? 'active' : '' }}"
                        href="{{ url('externalApiDocumentation/' . $group['key']) . $versionQuery }}">
                        <i class="fe fe-layers me-1"></i> {{ $group['title'] }}
                        <span class="badge badge-soft-secondary ms-1">{{ count($group['endpoints']) }}</span>
                    </a>
                </li>
            @endforeach
        </ul>

        {{-- Isi halaman yang sedang dibuka, sebagai penanda posisi baca. --}}
        @if ($isUmum)
            <h6 class="extapi-toc-title">Isi Halaman</h6>
            <ul class="extapi-toc-list">
                <li><a href="#overview">Ringkasan</a></li>
                <li><a href="#authentication">Autentikasi</a></li>
                <li><a href="#base-url">Base URL</a></li>
                <li><a href="#headers">Header</a></li>
                <li><a href="#response-format">Format Respons</a></li>
                <li><a href="#errors">Respons Error</a></li>
            </ul>
        @else
            <h6 class="extapi-toc-title">Isi Halaman</h6>
            <ul class="extapi-toc-list">
                @foreach ($current['endpoints'] as $endpoint)
                    <li>
                        <a href="#endpoint-{{ $endpoint->key() }}">
                            <span
                                class="badge {{ $endpoint->methodBadgeClass() }} extapi-method-sm">{{ strtoupper($endpoint->method()) }}</span>
                            {{ $endpoint->title() }}
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
