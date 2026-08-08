{{--
    Halaman "Umum" — hal yang berlaku untuk seluruh endpoint, apa pun modulnya.
    Rincian tiap endpoint ada di halaman modulnya masing-masing.
--}}

<div class="card" id="overview">
    <div class="card-body">
        <h5 class="extapi-section-title">Ringkasan</h5>
        <p>
            API Eksternal Pegasus ditujukan untuk integrasi antar-server dengan sistem pihak
            ketiga tepercaya, misalnya ERP, marketplace, atau aplikasi mitra. Setiap sistem
            didaftarkan sebagai satu Aplikasi Eksternal dan diberi satu atau beberapa API Key.
        </p>
        <p>
            Saat ini tersedia <strong>{{ $totalEndpoint }}</strong> endpoint pada versi
            <strong>{{ $version }}</strong>, terbagi dalam {{ count($groups) }} modul.
        </p>

        @if ($groups)
            <div class="row g-3 mt-1">
                @foreach ($groups as $group)
                    <div class="col-md-6">
                        <a class="extapi-module-card"
                            href="{{ route($docRoute['group'], $group['key']) . (count($versions) > 1 ? '?version=' . urlencode($version) : '') }}">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="mb-1">{{ $group['title'] }}</h6>
                                    <span class="text-muted small">{{ count($group['endpoints']) }} endpoint</span>
                                </div>
                                <i class="fe fe-chevron-right"></i>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center text-muted py-4">
                <i class="fe fe-file-text extapi-empty-icon"></i>
                <h6 class="mt-3 mb-1">Belum ada endpoint bisnis</h6>
                <p class="mb-0">
                    Endpoint yang ditambahkan akan muncul di sini dengan sendirinya setelah
                    didaftarkan pada <code>config/externalapi.php</code>.
                </p>
            </div>
        @endif
    </div>
</div>

<div class="card" id="authentication">
    <div class="card-body">
        <h5 class="extapi-section-title">Autentikasi</h5>
        <p>
            Seluruh endpoint dilindungi API Key. Kunci dikirim pada setiap permintaan lewat
            header <code>{{ $keyHeader }}</code>. Sebagai alternatif, kunci yang sama juga
            diterima lewat <code>Authorization: Bearer &lt;kunci&gt;</code>.
        </p>
        <pre class="extapi-code">curl {{ $baseUrl }}/contoh \
  -H "{{ $keyHeader }}: ext_live_xxxxxxxxxxxxxxxx"</pre>
        <div class="alert alert-warning mb-0" role="alert">
            <i class="fe fe-alert-triangle me-2"></i>
            API Key hanya ditampilkan sekali saat dibuat dan tidak bisa dilihat lagi
            sesudahnya. Kunci yang hilang harus dicabut lalu dibuat ulang.
        </div>
    </div>
</div>

<div class="card" id="base-url">
    <div class="card-body">
        <h5 class="extapi-section-title">Base URL</h5>
        <pre class="extapi-code mb-2">{{ $baseUrl }}</pre>
        <p class="text-muted mb-0">
            Versi API selalu menjadi bagian dari alamat. Versi lama tetap dilayani saat versi
            baru dirilis, sehingga klien punya waktu untuk berpindah.
        </p>
    </div>
</div>

<div class="card" id="headers">
    <div class="card-body">
        <h5 class="extapi-section-title">Header</h5>
        <div class="table-responsive">
            <table class="table table-center extapi-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Header</th>
                        <th>Wajib</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>{{ $keyHeader }}</code></td>
                        <td><span class="badge badge-soft-danger">Ya</span></td>
                        <td>API Key milik aplikasi pemanggil.</td>
                    </tr>
                    <tr>
                        <td><code>Accept</code></td>
                        <td><span class="badge badge-soft-secondary">Tidak</span></td>
                        <td>Disarankan diisi <code>application/json</code>.</td>
                    </tr>
                    <tr>
                        <td><code>Content-Type</code></td>
                        <td><span class="badge badge-soft-secondary">Tidak</span></td>
                        <td>Wajib <code>application/json</code> bila permintaan membawa body.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card" id="response-format">
    <div class="card-body">
        <h5 class="extapi-section-title">Format Respons</h5>
        <p>Seluruh respons memakai bentuk yang sama, baik berhasil maupun gagal.</p>

        <h6 class="extapi-sub-title">Berhasil</h6>
        <pre class="extapi-code">{
    "success": true,
    "data": { ... },
    "meta": {
        "pagination": {
            "page": 1,
            "per_page": 50,
            "total": 120,
            "total_pages": 3
        }
    }
}</pre>

        <h6 class="extapi-sub-title">Gagal</h6>
        <pre class="extapi-code">{
    "success": false,
    "error": {
        "code": "invalid_api_key",
        "message": "API Key tidak valid."
    }
}</pre>
        <p class="text-muted mb-0">
            <code>error.code</code> bersifat tetap dan aman dijadikan pegangan logika klien.
            <code>error.message</code> ditujukan untuk dibaca manusia dan bisa berubah
            sewaktu-waktu. Bagian <code>meta</code> hanya muncul bila endpoint memang
            mengirim informasi tambahan seperti paginasi. Nilai tanggal & waktu memakai
            format ISO 8601 dengan zona waktu server.
        </p>
    </div>
</div>

<div class="card" id="errors">
    <div class="card-body">
        <h5 class="extapi-section-title">Respons Error</h5>
        <p class="text-muted">
            Error berikut bisa muncul di endpoint mana pun karena berasal dari lapisan
            platform, bukan dari satu endpoint tertentu. Error yang khas untuk satu endpoint
            dicantumkan pada halaman modulnya masing-masing.
        </p>
        <div class="table-responsive">
            <table class="table table-center extapi-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>HTTP</th>
                        <th>Kode</th>
                        <th>Pesan</th>
                        <th>Penyebab</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($platformErrors as $error)
                        <tr>
                            <td><span class="badge badge-soft-secondary">{{ $error['http_status'] }}</span></td>
                            <td><code>{{ $error['code'] }}</code></td>
                            <td>{{ $error['message'] }}</td>
                            <td class="text-muted">{{ $error['cause'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
