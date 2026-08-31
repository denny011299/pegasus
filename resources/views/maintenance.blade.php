<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Pemeliharaan Sistem — Pegasus</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: linear-gradient(160deg, #082a58 0%, #0f3d7a 45%, #1e4a8c 100%);
            color: #0f172a;
        }
        .card {
            width: 100%;
            max-width: 480px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
            padding: 40px 32px 32px;
            text-align: center;
        }
        .logo {
            max-width: 220px;
            height: auto;
            margin: 0 auto 24px;
            display: block;
        }
        .icon {
            width: 56px;
            height: 56px;
            margin: 0 auto 16px;
            border-radius: 50%;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }
        h1 {
            margin: 0 0 12px;
            font-size: 1.35rem;
            font-weight: 700;
            color: #0f172a;
        }
        p {
            margin: 0;
            line-height: 1.6;
            color: #475569;
            font-size: 15px;
        }
        .hint {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            font-size: 13px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="card">
        <img class="logo" src="{{ asset('assets/pegasus_banner_small.png') }}" alt="Pegasus">
        <div class="icon" aria-hidden="true">&#9881;</div>
        <h1>Sedang Dalam Pemeliharaan</h1>
        <p>{{ $message ?? 'Sistem sedang dalam pemeliharaan. Silakan coba lagi beberapa saat lagi.' }}</p>
        <p class="hint">Anda telah keluar dari sistem. Akses sementara tidak tersedia.</p>
    </div>
</body>
</html>
