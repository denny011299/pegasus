<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Stock Aging</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 10px; color: #222; line-height: 1.35; margin: 0; padding: 24px 18px; }
        .report-header { border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 18px; width: 100%; }
        .report-title { font-size: 18px; font-weight: bold; color: #000; margin: 0 0 4px 0; text-transform: uppercase; letter-spacing: .5px; }
        .report-meta { color: #666; font-size: 9px; }
        .params-table { width: 100%; margin-bottom: 16px; }
        .params-table td { padding: 2px 0; font-size: 10px; }
        .params-label { color: #555; width: 110px; }
        .params-val { color: #000; font-weight: bold; }
        .table-detail { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .table-detail thead { display: table-header-group; }
        .table-detail thead th {
            border-top: 1px solid #000; border-bottom: 1px solid #000;
            padding: 6px 4px; font-size: 8px; font-weight: bold; color: #3d4f62;
            text-transform: uppercase; letter-spacing: 0.04em; text-align: left;
        }
        .table-detail td { padding: 5px 4px; font-size: 9px; border-bottom: 1px solid #eef0f4; vertical-align: top; }
        .table-detail tbody tr:nth-child(even) td { background-color: #fafbfd; }
        .text-right { text-align: right !important; }
        .empty-msg { padding: 14px; text-align: center; color: #777; font-size: 10px; }
    </style>
</head>
<body>
    <div class="report-header">
        <h1 class="report-title">Laporan Stock Aging</h1>
        <div class="report-meta">TANGGAL CETAK: {{ strtoupper($printed_at ?? now()->format('d/m/Y H:i')) }}</div>
    </div>

    <table class="params-table">
        <tr>
            <td class="params-label">Tanggal acuan umur</td>
            <td class="params-val">{{ $as_of_label ?? '-' }}</td>
        </tr>
        <tr>
            <td class="params-label">Tipe</td>
            <td class="params-val">{{ $type_label ?? '-' }}</td>
        </tr>
        <tr>
            <td class="params-label">Item</td>
            <td class="params-val">{{ $item_label ?? '-' }}</td>
        </tr>
        <tr>
            <td class="params-label">Dicetak oleh</td>
            <td class="params-val">{{ $printed_by ?? '-' }}</td>
        </tr>
    </table>

    @php
        $rows = $data ?? [];
        if (!is_array($rows)) {
            $rows = [];
        }
    @endphp

    @if (count($rows) === 0)
        <p class="empty-msg">Tidak ada data stock aging</p>
    @else
        <table class="table-detail">
            <thead>
                <tr>
                    <th style="width:8%;">Sumber</th>
                    <th style="width:26%;">Item</th>
                    <th style="width:8%;">Satuan</th>
                    <th class="text-right" style="width:7%;">Qty</th>
                    <th class="text-right" style="width:9%;">Umur (hari)</th>
                    <th style="width:11%;">Kelompok</th>
                    <th style="width:11%;">Lot tertua</th>
                    <th class="text-right" style="width:10%;">Harga/unit</th>
                    <th class="text-right" style="width:10%;">Nilai</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $r)
                    @php
                        $r = is_array($r) ? $r : (array) $r;
                        $s = strtolower((string)($r['sumber'] ?? ''));
                        $src = $s === 'bahan' ? 'Bahan' : 'Produk';
                    @endphp
                    <tr>
                        <td>{{ $src }}</td>
                        <td>{{ $r['item_label'] ?? '-' }}</td>
                        <td>{{ $r['unit_name'] ?? '-' }}</td>
                        <td class="text-right">{{ $r['qty_display'] ?? '-' }}</td>
                        <td class="text-right">{{ isset($r['weighted_age_days']) ? number_format((float)$r['weighted_age_days'], 1, ',', '.') : '-' }}</td>
                        <td>{{ $r['bucket'] ?? '-' }}</td>
                        <td>{{ !empty($r['oldest_layer_date']) ? \Carbon\Carbon::parse($r['oldest_layer_date'])->format('d/m/Y') : '-' }}</td>
                        <td class="text-right">Rp {{ number_format((float)($r['unit_price'] ?? 0), 0, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format((float)($r['stock_value'] ?? 0), 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
