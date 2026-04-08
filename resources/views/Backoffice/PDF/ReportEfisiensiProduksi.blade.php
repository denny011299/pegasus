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
        .table-data, .table-detail { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .table-data > thead > tr > th { border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 8px 4px; text-align: left; font-size: 9px; font-weight: bold; color: #555; text-transform: uppercase; letter-spacing: .5px; }
        .row-parent > td { padding: 12px 6px; font-size: 11px; font-weight: bold; vertical-align: middle; }
        .row-child-container > td { padding: 0 4px 24px 30px; border-bottom: 1px solid #ccc; }
        .table-detail th { border-bottom: 1px solid #eee; padding: 6px 4px; font-size: 9px; color: #888; font-weight: normal; text-align: left; }
        .table-detail td { padding: 6px 4px; font-size: 11px; color: #444; border-bottom: 1px solid #f5f5f5; }
        .table-detail tr:last-child td { border-bottom: none; }
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        .text-green { color: #059669; }
        .text-red { color: #dc2626; }
        .text-yellow { color: #b45309; }
        .text-gray { color: #777; }
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
    @endphp
    <table class="params-table">
        <tr><td class="params-label">PERIODE</td><td class="params-val">: {{ $startFormatted }} s/d {{ $endFormatted }}</td></tr>
        <tr><td class="params-label">SUPPLIER</td><td class="params-val">: {{ strtoupper($supplier_name ?? 'Semua Supplier') }}</td></tr>
        <tr><td class="params-label">PRODUK</td><td class="params-val">: {{ strtoupper($product_name ?? 'Semua Produk') }}</td></tr>
    </table>

    <table class="table-data">
        <thead>
            <tr>
                <th style="width:5%" class="text-center">NO</th>
                <th style="width:24%">PRODUK</th>
                <th style="width:16%" class="text-right">TOTAL PRODUKSI</th>
                <th style="width:16%" class="text-right">TOTAL REJECT</th>
                <th style="width:13%" class="text-right">RASIO REJECT</th>
                <th style="width:13%" class="text-right">WASTE BAHAN</th>
                <th style="width:13%" class="text-right">EFISIENSI</th>
            </tr>
        </thead>
        @forelse(($data ?? []) as $i => $row)
            @php
                $rej = (float)($row['reject_ratio'] ?? 0);
                $wst = (float)($row['material_waste_ratio'] ?? 0);
                $eff = (float)($row['efficiency_ratio'] ?? 0);
                $rejClass = $rej <= 5 ? 'text-green' : ($rej <= 15 ? 'text-yellow' : 'text-red');
                $wstClass = $wst <= 5 ? 'text-green' : ($wst <= 15 ? 'text-yellow' : 'text-red');
                $effClass = $eff >= 90 ? 'text-green' : ($eff >= 75 ? 'text-yellow' : 'text-red');
            @endphp
            <tbody>
                <tr class="row-parent">
                    <td class="text-center">{{ $i + 1 }}.</td>
                    <td>{{ $row['product_name'] ?? '-' }}</td>
                    <td class="text-right">{{ (int)($row['production_count'] ?? 0) }} Batch ({{ (int)($row['total_qty'] ?? 0) }} qty)</td>
                    <td class="text-right">{{ (int)($row['total_reject_count'] ?? 0) }} Batch ({{ (int)($row['total_reject_qty'] ?? 0) }} qty)</td>
                    <td class="text-right"><span class="{{ $rejClass }}">{{ number_format($rej, 2, ',', '.') }}%</span></td>
                    <td class="text-right"><span class="{{ $wstClass }}">{{ number_format($wst, 2, ',', '.') }}%</span></td>
                    <td class="text-right"><span class="{{ $effClass }}">{{ number_format($eff, 2, ',', '.') }}%</span></td>
                </tr>
                <tr class="row-child-container">
                    <td colspan="7">
                        <table class="table-detail">
                            <thead>
                                <tr>
                                    <th style="width:15%">TANGGAL</th>
                                    <th style="width:15%">KODE PRODUKSI</th>
                                    <th style="width:14%">QTY PRODUKSI</th>
                                    <th style="width:12%">STATUS</th>
                                    <th style="width:22%">PEMAKAIAN BAHAN</th>
                                    <th style="width:22%">BAHAN TERBUANG</th>
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
                                                <span class="text-red">REJECT</span>
                                            @elseif((int)($d['status'] ?? 0) === 2)
                                                <span class="text-green">SELESAI</span>
                                            @else
                                                <span class="text-gray">PENDING</span>
                                            @endif
                                        </td>
                                        <td>{{ $d['material_usage_text'] ?? '-' }}</td>
                                        <td>{{ $d['material_waste_text'] ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-gray">Tidak ada detail</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </td>
                </tr>
            </tbody>
        @empty
            <tbody><tr class="row-parent"><td colspan="7" class="text-center text-gray">Tidak ada data efisiensi produksi</td></tr></tbody>
        @endforelse
    </table>
</body>
</html>
