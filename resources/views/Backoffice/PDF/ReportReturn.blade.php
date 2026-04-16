<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Retur Product</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #222222;
            line-height: 1.4;
            margin: 0;
            padding: 30px 20px;
        }
        .report-header {
            border-bottom: 2px solid #000000;
            padding-bottom: 12px;
            margin-bottom: 24px;
            width: 100%;
        }
        .report-title {
            font-size: 20px;
            font-weight: bold;
            color: #000000;
            margin: 0 0 4px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .report-meta { color: #666666; font-size: 10px; }
        .params-table { width: 100%; margin-bottom: 35px; }
        .params-table td { padding: 3px 0; font-size: 11px; }
        .params-label { color: #555555; width: 90px; }
        .params-val { color: #000000; font-weight: bold; }
        .table-legend {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto;
            margin-bottom: 4px;
            page-break-after: avoid;
        }
        .table-legend thead tr th {
            border-top: 1px solid #000000;
            border-bottom: 1px solid #000000;
            padding: 8px 4px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
            color: #555555;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .report-group-block {
            padding: 0 0 12px 0;
            border-bottom: 1px solid #cccccc;
            page-break-inside: auto;
        }
        .report-group-block-first { page-break-before: avoid; }
        .group-summary { width: 100%; border-collapse: collapse; margin: 0 0 2px 0; page-break-after: avoid; page-break-inside: avoid; }
        .group-summary td {
            padding: 8px 6px;
            font-size: 11px;
            color: #000000;
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
            border-bottom: 1px solid #eeeeee;
            padding: 6px 4px;
            font-size: 9px;
            color: #888888;
            font-weight: normal;
            text-align: left;
        }
        .table-detail td {
            padding: 6px 4px;
            font-size: 11px;
            color: #444444;
            border-bottom: 1px solid #f5f5f5;
        }
        .table-detail tbody tr:last-child td { border-bottom: none; }
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        .font-normal { font-weight: normal; }
        .text-gray { color: #777777; }
        .col-no { width: 5%; color: #888888; font-size: 11px; }
        .empty-msg { padding: 12px; text-align: center; color: #777777; font-size: 11px; }
    </style>
</head>
<body>
    <div class="report-header">
        <h1 class="report-title">Laporan Retur Product</h1>
        <div class="report-meta">TANGGAL CETAK: {{ strtoupper(now()->format('d M Y H:i')) }}</div>
    </div>

    <table class="params-table">
        @php
            \Carbon\Carbon::setLocale('id');
            $startFormatted = '-';
            $endFormatted = '-';
            if (!empty($start_date) && $start_date !== '-') {
                try {
                    $startFormatted = \Carbon\Carbon::createFromFormat('d-m-Y', $start_date)->translatedFormat('j F Y');
                } catch (\Throwable $th) {
                    try {
                        $startFormatted = \Carbon\Carbon::parse($start_date)->translatedFormat('j F Y');
                    } catch (\Throwable $th2) {
                        $startFormatted = '-';
                    }
                }
            }
            if (!empty($end_date) && $end_date !== '-') {
                try {
                    $endFormatted = \Carbon\Carbon::createFromFormat('d-m-Y', $end_date)->translatedFormat('j F Y');
                } catch (\Throwable $th) {
                    try {
                        $endFormatted = \Carbon\Carbon::parse($end_date)->translatedFormat('j F Y');
                    } catch (\Throwable $th2) {
                        $endFormatted = '-';
                    }
                }
            }
            $fmt = function($arr) {
                $parts = [];
                foreach ($arr as $u => $q) {
                    if ($q > 0) $parts[] = $q . ' ' . $u;
                }
                return count($parts) ? implode(' ', $parts) : '-';
            };
            $rows = is_array($data ?? null) ? $data : [];
        @endphp
        <tr>
            <td class="params-label">PERIODE</td>
            <td class="params-val">: {{ $startFormatted }} s/d {{ $endFormatted }}</td>
        </tr>
        <tr>
            <td class="params-label">SUPPLIER</td>
            <td class="params-val">: {{ strtoupper($supplier_name ?? 'Semua Supplier') }}</td>
        </tr>
        <tr>
            <td class="params-label">BARANG</td>
            <td class="params-val">: {{ strtoupper($item_name ?? 'Semua Barang') }}</td>
        </tr>
    </table>

    @if(count($rows) < 1)
        <p class="empty-msg font-normal">Tidak ada data laporan retur</p>
    @else
        <table class="table-legend">
            <thead>
                <tr>
                    <th class="text-center col-no" scope="col" style="width:5%;">NO</th>
                    <th scope="col" style="width: 50%;">BARANG RETUR</th>
                    <th class="text-right" scope="col" style="width: 20%;">TOTAL TRANSAKSI RETUR</th>
                    <th class="text-right" scope="col" style="width: 25%;">AKUMULASI QTY RETUR</th>
                </tr>
            </thead>
        </table>

        @foreach($rows as $i => $row)
            @php
                $details = $row['details'] ?? [];
                $qtyMap = [];
                foreach ($details as $d) {
                    $unit = trim($d['unit_name'] ?? '');
                    if ($unit === '') $unit = 'unit';
                    if (!isset($qtyMap[$unit])) $qtyMap[$unit] = 0;
                    $qtyMap[$unit] += (int)($d['qty'] ?? 0);
                }
            @endphp
            <div class="report-group-block @if($loop->first) report-group-block-first @endif">
                <table class="group-summary">
                    <tr>
                        <td class="text-center text-gray font-normal" style="width:5%;">{{ $i + 1 }}.</td>
                        <td style="width:50%;">{{ $row['item_name'] ?? '-' }}</td>
                        <td class="text-right" style="width:20%;">{{ (int)($row['transaction_count'] ?? 0) }} Transaksi</td>
                        <td class="text-right" style="width:25%;">{{ $fmt($qtyMap) }}</td>
                    </tr>
                </table>
                <table class="table-detail">
                    <thead>
                        <tr>
                            <th class="text-gray font-normal" style="width: 20%; border-bottom: 1px solid #eee; padding: 5px 4px; font-size: 9px;">TANGGAL RETUR</th>
                            <th class="text-gray font-normal" style="width: 20%; border-bottom: 1px solid #eee; padding: 5px 4px; font-size: 9px;">REFERENSI PO</th>
                            <th class="text-gray font-normal" style="width: 30%; border-bottom: 1px solid #eee; padding: 5px 4px; font-size: 9px;">SUPPLIER</th>
                            <th class="text-right text-gray font-normal" style="width: 15%; border-bottom: 1px solid #eee; padding: 5px 4px; font-size: 9px;">QTY</th>
                            <th class="text-right text-gray font-normal" style="width: 15%; border-bottom: 1px solid #eee; padding: 5px 4px; font-size: 9px;">SUBTOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($details as $d)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($d['rs_date'])->format('d M Y') }}</td>
                                <td>{{ $d['po_number'] ?? '-' }}</td>
                                <td>{{ $d['supplier_name'] ?? '-' }}</td>
                                <td class="text-right">{{ $d['qty'] ?? 0 }} {{ $d['unit_name'] ?? '' }}</td>
                                <td class="text-right">Rp {{ number_format((float)($d['subtotal'] ?? 0), 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-gray">Tidak ada detail</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endforeach
    @endif
    @include('Backoffice.PDF.partials.dicetak_oleh')
</body>
</html>
