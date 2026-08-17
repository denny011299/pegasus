<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Terjadi Kesalahan</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{--
        Sengaja standalone (tidak @extends layout.mainlayout / tidak memuat
        asset lain): kalau yang hilang justru bagian dari layout/partial,
        halaman error ini sendiri tidak boleh ikut gagal render.
    --}}
    <style>
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: #f4f6f9;
            color: #1f2937;
            display: flex;
            min-height: 100vh;
            align-items: center;
            justify-content: center;
            padding: 24px;
            box-sizing: border-box;
        }
        .box {
            max-width: 640px;
            width: 100%;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,.1), 0 1px 2px rgba(0,0,0,.06);
            padding: 32px;
        }
        h1 { font-size: 20px; margin: 0 0 8px; }
        p { line-height: 1.55; }
        .badge {
            display: inline-block;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .03em;
            color: #92400e;
            background: #fef3c7;
            border-radius: 999px;
            padding: 3px 10px;
            margin-bottom: 14px;
        }
        code {
            background: #f3f4f6;
            border-radius: 4px;
            padding: 2px 6px;
            font-size: 13px;
            word-break: break-all;
        }
        .path-block {
            background: #111827;
            color: #e5e7eb;
            border-radius: 8px;
            padding: 14px 16px;
            margin: 16px 0;
            font-size: 14px;
            word-break: break-all;
        }
        .hint { color: #6b7280; font-size: 13px; }
    </style>
</head>
<body>
    <div class="box">
        @if ($detection)
            <span class="badge">Kemungkinan file belum ter-upload</span>
            <h1>Halaman ini gagal dimuat</h1>
            <p>{{ $detection['explanation'] }}</p>
            @if ($detection['expected_path'])
                <div class="path-block">{{ $detection['expected_path'] }}</div>
                <p class="hint">
                    Cek apakah file di atas benar-benar ada di server. Kalau tidak ada, upload ulang file
                    tersebut dari repo, lalu jalankan
                    <code>php artisan deploy:manifest</code> di repo dan upload ulang
                    <code>deploy/manifest.json</code> supaya <code>/system/deployment-check</code>
                    ikut ter-update.
                </p>
            @endif
            <p class="hint">Kategori: <code>{{ $detection['category'] }}</code> &middot; identifier: <code>{{ $detection['identifier'] }}</code></p>
        @else
            <h1>Terjadi kesalahan</h1>
            <p>Maaf, halaman ini sedang mengalami gangguan. Silakan coba lagi beberapa saat lagi, atau hubungi tim terkait jika masalah berlanjut.</p>
        @endif
    </div>
</body>
</html>
