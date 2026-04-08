<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Efisiensi Produksi</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11px; color: #222; line-height: 1.4; margin: 0; padding: 30px 20px; }
        .report-header { border-bottom: 2px solid #000; padding-bottom: 12px; margin-bottom: 24px; width: 100%; }
        .report-title { font-size: 20px; font-weight: bold; margin: 0 0 4px 0; text-transform: uppercase; letter-spacing: .5px; }
        .report-meta { color: #666; font-size: 10px; }
        .params-table { width: 100%; margin-bottom: 30px; }
        .params-table td { padding: 3px 0; font-size: 11px; }
        .params-label { color: #555; width: 90px; }
        .params-val { color: #000; font-weight: bold; }
        .table-legend {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto;
            margin-bottom: 4px;
            page-break-after: avoid;
        }
        .table-legend thead tr th {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 8px 4px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
            color: #555;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        .report-group-block {
            padding: 0 0 12px 0;
            border-bottom: 1px solid #ccc;
            page-break-inside: auto;
        }
        .report-group-block-first { page-break-before: avoid; }
        .group-summary {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 2px 0;
            page-break-after: avoid;
            page-break-inside: avoid;
        }
        .group-summary td {
            padding: 8px 6px;
            font-size: 11px;
            color: #000;
            font-weight: bold;
            vertical-align: middle;
        }
        .table-detail {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0;
            table-layout: auto;
        }
        .table-detail thead { display: table-header-group; }
        .table-detail thead th {
            border-bottom: 1px solid #eee;
            padding: 6px 4px;
            font-size: 9px;
            color: #888;
            font-weight: normal;
            text-align: left;
        }
        .table-detail td { padding: 6px 4px; font-size: 11px; color: #444; border-bottom: 1px solid #f5f5f5; }
        .table-detail tbody tr:last-child td { border-bottom: none; }
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        .text-green { color: #059669; }
        .text-red { color: #dc2626; }
        .text-yellow { color: #b45309; }
        .text-gray { color: #777; }
        .font-normal { font-weight: normal; }
        .empty-msg { padding: 12px 6px; text-align: center; color: #777; font-size: 11px; }
    </style>
</head>
<body>
    <div class="report-header">
        <h1 class="report-title">Laporan Efisiensi Produksi</h1>
        <div class="report-meta">TANGGAL CETAK: {{ strtoupper(now()->format('d M Y H:i')) }}</div>
    </div>

    @php
        \Carbon\Carbon::setLocale('id');
        $startFormatted = '-';
        $endFormatted = '-';
        if (!empty($start_date) && $start_date !== '-') {
            try { $startFormatted = \Carbon\Carbon::createFromFormat('d-m-Y', $start_date)->translatedFormat('j F Y'); }
            catch (\Throwable $th) { try { $startFormatted = \Carbon\Carbon::parse($start_date)->translatedFormat('j F Y'); } catch (\Throwable $th2) {} }
        }
        if (!empty($end_date) && $end_date !== '-') {
            try { $endFormatted = \Carbon\Carbon::createFromFormat('d-m-Y', $end_date)->translatedFormat('j F Y'); }
            catch (\Throwable $th) { try { $endFormatted = \Carbon\Carbon::parse($end_date)->translatedFormat('j F Y'); } catch (\Throwable $th2) {} }
        }
        $rows = is_array($data ?? null) ? $data : [];
    @endphp
    <table class="params-table">
        <tr><td class="params-label">PERIODE</td><td class="params-val">: {{ $startFormatted }} s/d {{ $endFormatted }}</td></tr>
        <tr><td class="params-label">SUPPLIER</td><td class="params-val">: {{ strtoupper($supplier_name ?? 'Semua Supplier') }}</td></tr>
        <tr><td class="params-label">PRODUK</td><td class="params-val">: {{ strtoupper($product_name ?? 'Semua Produk') }}</td></tr>
    </table>

    <p class="text-gray" style="font-size:10px;margin:0 0 16px 0;">
        Status produksi: Pending, Berhasil, Tolak — laporan hanya Berhasil + Tolak (sudah final). Skor operasional = (100% − rasio tolak) × (100% − rasio waste bahan) / 100. Urutan: prioritas perbaikan.
    </p>

    @if(count($rows) < 1)
        <p class="empty-msg font-normal">Tidak ada data efisiensi produksi</p>
    @else
        <table class="table-legend">
            <thead>
                <tr>
                    <th style="width:4%" class="text-center" scope="col">NO</th>
                    <th style="width:20%" scope="col">PRODUK</th>
                    <th style="width:11%" class="text-right" scope="col">TOTAL</th>
                    <th style="width:9%" class="text-right" scope="col">QTY LULUS</th>
                    <th style="width:11%" class="text-right" scope="col">TOLAK</th>
                    <th style="width:9%" class="text-right" scope="col">% TOLAK</th>
                    <th style="width:9%" class="text-right" scope="col">% WASTE</th>
                    <th style="width:8%" class="text-right" scope="col">TANPA LOG</th>
                    <th style="width:9%" class="text-right" scope="col">YIELD</th>
                    <th style="width:10%" class="text-right" scope="col">SKOR</th>
                </tr>
            </thead>
        </table>

        @foreach($rows as $i => $row)
            @php
                $rej = (float)($row['reject_ratio'] ?? 0);
                $wst = (float)($row['material_waste_ratio'] ?? 0);
                $yld = (float)($row['yield_pct'] ?? $row['efficiency_ratio'] ?? 0);
                $ops = (float)($row['operational_score'] ?? 0);
                $unt = (int)($row['untracked_batch_count'] ?? 0);
                $rejClass = $rej <= 5 ? 'text-green' : ($rej <= 15 ? 'text-yellow' : 'text-red');
                $wstClass = $wst <= 5 ? 'text-green' : ($wst <= 15 ? 'text-yellow' : 'text-red');
                $yldClass = $yld >= 90 ? 'text-green' : ($yld >= 75 ? 'text-yellow' : 'text-red');
                $opsClass = $ops >= 90 ? 'text-green' : ($ops >= 75 ? 'text-yellow' : 'text-red');
            @endphp
            <div class="report-group-block @if($loop->first) report-group-block-first @endif">
                <table class="group-summary">
                    <tr>
                        <td class="text-center" style="width:4%;">{{ $i + 1 }}.</td>
                        <td style="width:20%;">{{ $row['product_name'] ?? '-' }}</td>
                        <td class="text-right" style="width:11%;">{{ (int)($row['production_count'] ?? 0) }} Batch ({{ (int)($row['total_qty'] ?? 0) }} qty)</td>
                        <td class="text-right" style="width:9%;">{{ (int)($row['good_qty'] ?? 0) }}</td>
                        <td class="text-right" style="width:11%;">{{ (int)($row['total_reject_count'] ?? 0) }} Batch ({{ (int)($row['total_reject_qty'] ?? 0) }} qty)</td>
                        <td class="text-right" style="width:9%;"><span class="{{ $rejClass }}">{{ number_format($rej, 2, ',', '.') }}%</span></td>
                        <td class="text-right" style="width:9%;"><span class="{{ $wstClass }}">{{ number_format($wst, 2, ',', '.') }}%</span></td>
                        <td class="text-right" style="width:8%;"><span class="{{ $unt > 0 ? 'text-yellow' : 'text-green' }}">{{ $unt }}</span></td>
                        <td class="text-right" style="width:9%;"><span class="{{ $yldClass }}">{{ number_format($yld, 2, ',', '.') }}%</span></td>
                        <td class="text-right" style="width:10%;"><span class="{{ $opsClass }}">{{ number_format($ops, 2, ',', '.') }}%</span></td>
                    </tr>
                </table>
                <table class="table-detail">
                    <thead>
                        <tr>
                            <th class="text-gray font-normal" style="width:13%; border-bottom: 1px solid #eee; padding: 5px 4px; font-size: 9px;">TANGGAL</th>
                            <th class="text-gray font-normal" style="width:13%; border-bottom: 1px solid #eee; padding: 5px 4px; font-size: 9px;">KODE PRODUKSI</th>
                            <th class="text-gray font-normal" style="width:11%; border-bottom: 1px solid #eee; padding: 5px 4px; font-size: 9px;">QTY PRODUKSI</th>
                            <th class="text-gray font-normal" style="width:10%; border-bottom: 1px solid #eee; padding: 5px 4px; font-size: 9px;">STATUS</th>
                            <th class="text-gray font-normal" style="width:10%; border-bottom: 1px solid #eee; padding: 5px 4px; font-size: 9px;">LOG BAHAN</th>
                            <th class="text-gray font-normal" style="width:21%; border-bottom: 1px solid #eee; padding: 5px 4px; font-size: 9px;">PEMAKAIAN BAHAN</th>
                            <th class="text-gray font-normal" style="width:22%; border-bottom: 1px solid #eee; padding: 5px 4px; font-size: 9px;">BAHAN TERBUANG</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($row['details'] ?? []) as $d)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($d['production_date'])->format('d M Y') }}</td>
                                <td>{{ $d['production_code'] ?? '-' }}</td>
                                <td>{{ $d['qty'] ?? 0 }} {{ $d['unit_name'] ?? '' }}</td>
                                <td>
                                    @if((int)($d['status'] ?? 0) === 3)
                                        <span class="text-red">TOLAK</span>
                                    @elseif((int)($d['status'] ?? 0) === 2)
                                        <span class="text-green">BERHASIL</span>
                                    @elseif((int)($d['status'] ?? 0) === 1)
                                        <span class="text-gray">PENDING</span>
                                    @elseif((int)($d['status'] ?? 0) === 4)
                                        <span class="text-yellow">MENUNGGU BATAL</span>
                                    @else
                                        <span class="text-gray">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!empty($d['material_tracked']))
                                        <span class="text-green">OK</span>
                                    @else
                                        <span class="text-yellow">—</span>
                                    @endif
                                </td>
                                <td>{{ $d['material_usage_text'] ?? '-' }}</td>
                                <td>{{ $d['material_waste_text'] ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-gray">Tidak ada detail</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endforeach
    @endif
</body>
</html>
