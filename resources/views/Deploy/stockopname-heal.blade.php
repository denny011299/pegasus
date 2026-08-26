<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Stock Opname Heal Console</title>
<meta name="robots" content="noindex, nofollow">
<style>
  body { font-family: -apple-system, Segoe UI, Arial, sans-serif; max-width: 900px; margin: 40px auto; padding: 0 16px; color: #222; }
  h1 { font-size: 20px; }
  h2 { font-size: 15px; margin-top: 32px; border-top: 1px solid #ddd; padding-top: 16px; }
  .card { border: 1px solid #ddd; border-radius: 8px; padding: 16px; margin: 10px 0; }
  .card.danger { border-color: #d33; background: #fff5f5; }
  .card.ok { border-color: #2a2; background: #f4fff4; }
  button, a.btn { display: inline-block; background: #2563eb; color: #fff; border: none; border-radius: 6px; padding: 8px 14px; font-size: 14px; cursor: pointer; text-decoration: none; }
  .danger button { background: #d33; }
  input[type=text], input[type=number] { padding: 6px 8px; border: 1px solid #bbb; border-radius: 4px; font-size: 14px; }
  label { display: inline-block; margin: 4px 8px 4px 0; }
  small { color: #666; }
  table { border-collapse: collapse; width: 100%; font-size: 13px; margin-top: 12px; }
  th, td { border: 1px solid #ddd; padding: 4px 8px; text-align: left; }
  th { background: #f4f4f4; }
  .status-TIER1_UNTOUCHED_ROW, .status-TIER2_CONVERTED { background: #fff9c4; }
  .status-KEPT_GENUINE { background: #e8f5e9; }
  .status-UNRESOLVED_NO_HISTORY { background: #ffe0e0; }
</style>
</head>
<body>
<h1>Stock Opname Heal Console — GitHub #78</h1>
<p><small>Token-gated. Lihat <code>App\Support\StockOpnameUntouchedUnitHealer</code> untuk rasional lengkap dua-tier-nya.</small></p>

<div class="card">
  <form method="GET" action="{{ url('/deploy/heal-stock-opname/preview') }}">
    <input type="hidden" name="token" value="{{ $token }}">
    <label>sto_id / stob_id: <input type="number" name="id" value="{{ $id }}" required></label>
    <label><input type="checkbox" name="bahan" value="1" {{ $bahan ? 'checked' : '' }}> Dokumen Bahan Mentah (bukan Produk)</label>
    <button type="submit">Preview (dry-run, aman diklik berkali-kali)</button>
  </form>
</div>

@if ($result)
    @php
        $report = $result['report'] ?? [];
        $updates = $result['updates'] ?? [];
        $isError = isset($report[0]['status']) && $report[0]['status'] === 'ERROR';
        $counts = collect($report)->countBy('status');
    @endphp

    @if ($isError)
        <div class="card danger"><strong>{{ $report[0]['detail'] }}</strong></div>
    @else
        <div class="card {{ $applied ? 'ok' : '' }}">
            <strong>{{ $applied ? 'Selesai ditulis' : 'Hasil dry-run' }}</strong> untuk
            {{ $bahan ? 'stob_id' : 'sto_id' }} {{ $id }} — {{ count($updates) }} baris
            {{ $applied ? 'ditulis ulang' : 'akan diubah' }}.
            <ul>
                @foreach ($counts as $status => $count)
                    <li>{{ $status }}: {{ $count }}</li>
                @endforeach
            </ul>
            @if (($counts['UNRESOLVED_NO_HISTORY'] ?? 0) > 0)
                <p><small>⚠️ {{ $counts['UNRESOLVED_NO_HISTORY'] }} satuan tidak punya riwayat log_stocks sebelum dokumen dibuat — TIDAK diubah, butuh review manual.</small></p>
            @endif
        </div>

        @if ($report !== [])
            <table>
                <thead>
                    <tr><th>detail_id</th><th>item_id</th><th>unit</th><th>stored_real</th><th>system_at_creation</th><th>status</th></tr>
                </thead>
                <tbody>
                    @foreach ($report as $r)
                        <tr class="status-{{ $r['status'] }}">
                            <td>{{ $r['detail_id'] }}</td>
                            <td>{{ $r['item_id'] }}</td>
                            <td>{{ $r['unit'] }}</td>
                            <td>{{ $r['stored_real'] }}</td>
                            <td>{{ $r['reconstructed_system_at_creation'] ?? '(tidak ada riwayat)' }}</td>
                            <td>{{ $r['status'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if (! $applied && count($updates) > 0)
            <div class="card danger">
                <strong>Tulis perubahan di atas ke database</strong>
                <p><small>Hanya menulis ulang <code>stod_real</code>/<code>stod_selisih</code> (atau <code>stobd_*</code>) pada baris di atas yang berstatus TIER1/TIER2 — TIDAK PERNAH menyentuh <code>ps_stock</code>/<code>ss_stock</code> atau header dokumen.</small></p>
                <form method="POST" action="{{ url('/deploy/heal-stock-opname/apply?token='.$token) }}"
                      onsubmit="return confirm('Ini akan menulis ulang {{ count($updates) }} baris stock_opname_detail{{ $bahan ? '_bahan' : '' }}s. Lanjutkan?');">
                    @csrf
                    <input type="hidden" name="id" value="{{ $id }}">
                    <input type="hidden" name="bahan" value="{{ $bahan ? '1' : '0' }}">
                    <p><label>Ketik <code>HEAL</code> untuk konfirmasi: <input type="text" name="confirm" autocomplete="off"></label></p>
                    <button type="submit">Tulis perubahan</button>
                </form>
            </div>
        @endif
    @endif
@endif

</body>
</html>
