<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Deploy Console</title>
<meta name="robots" content="noindex, nofollow">
<style>
  body { font-family: -apple-system, Segoe UI, Arial, sans-serif; max-width: 640px; margin: 40px auto; padding: 0 16px; color: #222; }
  h1 { font-size: 20px; }
  h2 { font-size: 15px; margin-top: 32px; border-top: 1px solid #ddd; padding-top: 16px; }
  .card { border: 1px solid #ddd; border-radius: 8px; padding: 16px; margin: 10px 0; }
  .card.danger { border-color: #d33; background: #fff5f5; }
  button, a.btn { display: inline-block; background: #2563eb; color: #fff; border: none; border-radius: 6px; padding: 8px 14px; font-size: 14px; cursor: pointer; text-decoration: none; }
  .danger button { background: #d33; }
  input[type=text] { padding: 6px 8px; border: 1px solid #bbb; border-radius: 4px; font-size: 14px; width: 140px; }
  select { padding: 6px 8px; border: 1px solid #bbb; border-radius: 4px; font-size: 14px; max-width: 100%; }
  small { color: #666; }
  pre { background: #111; color: #ddd; padding: 12px; overflow-x: auto; border-radius: 6px; }
</style>
</head>
<body>
<h1>Deploy Console</h1>
<p><small>Token-gated Artisan runner. See <code>cdocs/docs/deploy-shared-hosting.md</code>.</small></p>

<h2>Safe / read-only</h2>
<div class="card">
  <a class="btn" href="{{ url('/deploy/migrate-status?token=' . $token) }}" target="_blank">Cek status migrasi</a>
</div>

<h2>Aman dijalankan berkali-kali (idempotent)</h2>
<div class="card">
  <a class="btn" href="{{ url('/deploy/migrate?token=' . $token) }}" target="_blank">Jalankan migrate</a>
  <p><small>Hanya menjalankan migration yang belum pernah dijalankan.</small></p>
</div>
<div class="card">
  <a class="btn" href="{{ url('/deploy/optimize-clear?token=' . $token) }}" target="_blank">Clear cache (config/cache/view/route)</a>
</div>

<h2>⚠️ Destruktif — hapus data</h2>

<div class="card danger">
  <strong>Seed snapshot penuh</strong> (untuk staging/testing)
  <p><small>Meng-truncate semua tabel di snapshot terpilih lalu memuat ulang datanya. <u>Jangan jalankan di database produksi berisi data asli.</u></small></p>
  <form method="POST" action="{{ url('/deploy/seed?token=' . $token) }}"
        onsubmit="return confirm('Ini akan MENGHAPUS data di semua tabel snapshot \'' + this.snapshot.selectedOptions[0].dataset.name + '\' dan menggantinya dengan data snapshot itu. Lanjutkan?');">
    @csrf
    <p>
      <label>Snapshot:
        <select name="snapshot">
          @forelse ($snapshots as $snap)
            <option value="{{ $snap['name'] }}" data-name="{{ $snap['name'] }}">
              {{ $snap['name'] }}{{ $snap['label'] ? ' — ' . $snap['label'] : '' }}
              ({{ $snap['tables'] }} tabel, {{ $snap['rows'] }} baris{{ $snap['generated_at'] ? ', ' . $snap['generated_at'] : '' }})
            </option>
          @empty
            <option value="default" data-name="default">default</option>
          @endforelse
        </select>
      </label>
    </p>
    <p><label>Ketik <code>SEED</code> untuk konfirmasi: <input type="text" name="confirm" autocomplete="off"></label></p>
    <button type="submit">Jalankan seed</button>
  </form>
</div>

<div class="card danger">
  <strong>Fresh kosong</strong> (untuk setup awal DB baru/production sebelum ada data asli)
  <p><small>Drop semua tabel lalu jalankan ulang semua migration. Hasilnya schema kosong, <u>tanpa data sama sekali</u> (tidak seeding). Setelah ini bisa jalankan "Seed snapshot" terpisah kalau perlu data dev.</small></p>
  <form method="POST" action="{{ url('/deploy/fresh-empty?token=' . $token) }}"
        onsubmit="return confirm('Ini akan MENGHAPUS SELURUH TABEL dan datanya, lalu membuat ulang schema kosong. Lanjutkan?');">
    @csrf
    <label>Ketik <code>FRESH</code> untuk konfirmasi: <input type="text" name="confirm" autocomplete="off"></label>
    <button type="submit">Jalankan migrate:fresh</button>
  </form>
</div>

</body>
</html>
