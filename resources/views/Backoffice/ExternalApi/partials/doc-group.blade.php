{{--
    Halaman satu modul: seluruh endpoint dalam kelompok $current.

    Bagian yang berlaku umum (autentikasi, base URL, header, bentuk respons,
    error platform) sengaja TIDAK diulang di sini — tempatnya di halaman Umum,
    supaya tidak ada dua salinan yang bisa berbeda isi.
--}}

<div class="card">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between flex-wrap">
            <div>
                <h5 class="mb-1">{{ $current['title'] }}</h5>
                <p class="text-muted mb-0">
                    {{ count($current['endpoints']) }} endpoint pada versi {{ $version }}.
                    Seluruhnya memerlukan API Key — lihat
                    <a href="{{ route($docRoute['index']) }}#authentication">Umum → Autentikasi</a>.
                </p>
            </div>
            <pre class="extapi-code mb-0 mt-2">{{ $baseUrl }}</pre>
        </div>
    </div>
</div>

@foreach ($current['endpoints'] as $endpoint)
    @php $status = $endpointStatus[$endpoint->key()] ?? null; @endphp
    <div class="card extapi-endpoint" id="endpoint-{{ $endpoint->key() }}">
        <div class="card-body">
            <div class="d-flex align-items-center flex-wrap mb-2">
                <span class="badge {{ $endpoint->methodBadgeClass() }} me-2">{{ strtoupper($endpoint->method()) }}</span>
                <code class="extapi-endpoint-path">{{ $endpoint->fullPath() }}</code>
                @if (!$endpoint->requiresAuthentication())
                    <span class="badge badge-soft-secondary ms-2">Tanpa Autentikasi</span>
                @endif
                @if ($status)
                    <span class="badge {{ $status['is_active'] ? 'badge-soft-success' : 'badge-soft-danger' }} ms-2">
                        Endpoint {{ $status['is_active'] ? 'Aktif' : 'Nonaktif' }}
                    </span>
                    @if ($showVisibilityBadge ?? true)
                        <span class="badge {{ $status['is_public_docs_show'] ? 'badge-soft-success' : 'badge-soft-secondary' }} ms-2">
                            Dokumentasi {{ $status['is_public_docs_show'] ? 'Publik' : 'Privat' }}
                        </span>
                    @endif
                @endif
            </div>

            <h6 class="mb-1">{{ $endpoint->title() }}</h6>
            <p class="text-muted">{{ $endpoint->description() }}</p>

            @foreach ([['Parameter Path', $endpoint->pathParameters()], ['Parameter Query', $endpoint->queryParameters()], ['Field Body', $endpoint->bodyParameters()]] as $block)
                @if (!empty($block[1]))
                    <h6 class="extapi-sub-title">{{ $block[0] }}</h6>
                    <div class="table-responsive">
                        <table class="table table-center extapi-table">
                            <thead class="thead-light">
                                <tr>
                                    <th>Nama</th>
                                    <th>Tipe</th>
                                    <th>Wajib</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($block[1] as $param)
                                    <tr>
                                        <td><code>{{ $param['name'] }}</code></td>
                                        <td>{{ $param['type'] ?? 'string' }}</td>
                                        <td>
                                            @if (!empty($param['required']))
                                                <span class="badge badge-soft-danger">Ya</span>
                                            @else
                                                <span class="badge badge-soft-secondary">Tidak</span>
                                            @endif
                                        </td>
                                        <td class="text-muted">{{ $param['description'] ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @endforeach

            <h6 class="extapi-sub-title">Contoh Permintaan</h6>
            <pre class="extapi-code">curl -X {{ strtoupper($endpoint->method()) }} "{{ rtrim(config('app.url'), '/') }}{{ $endpoint->fullPath() }}" \
  -H "{{ $keyHeader }}: ext_live_xxxxxxxxxxxxxxxx"@if ($endpoint->requestExample()) \
  -H "Content-Type: application/json" \
  -d '{{ json_encode($endpoint->requestExample(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}'@endif</pre>

            <h6 class="extapi-sub-title">Contoh Respons</h6>
            <pre class="extapi-code">{{ json_encode($endpoint->responseExample(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>

            @if ($endpoint->errors())
                <h6 class="extapi-sub-title">Error Khusus Endpoint Ini</h6>
                <div class="table-responsive">
                    <table class="table table-center extapi-table">
                        <thead class="thead-light">
                            <tr>
                                <th>HTTP</th>
                                <th>Kode</th>
                                <th>Pesan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($endpoint->errors() as $error)
                                <tr>
                                    <td><span class="badge badge-soft-secondary">{{ $error['http_status'] ?? 400 }}</span></td>
                                    <td><code>{{ $error['code'] ?? '-' }}</code></td>
                                    <td>{{ $error['message'] ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if ($endpoint->notes())
                <h6 class="extapi-sub-title">Catatan</h6>
                <ul class="extapi-note-list mb-0">
                    @foreach ($endpoint->notes() as $note)
                        <li>{{ $note }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endforeach
