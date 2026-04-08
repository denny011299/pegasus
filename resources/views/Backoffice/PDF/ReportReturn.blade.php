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
        .table-data {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .table-data > thead > tr > th {
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
        .row-parent > td {
            padding: 12px 6px;
            font-size: 11px;
            color: #000000;
            font-weight: bold;
            vertical-align: middle;
        }
        .row-child-container > td {
            padding: 0 4px 24px 30px;
            border-bottom: 1px solid #cccccc;
        }
        .table-detail {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        .table-detail th {
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
        .table-detail tr:last-child td { border-bottom: none; }
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        .font-normal { font-weight: normal; }
        .text-gray { color: #777777; }
        .col-no { width: 5%; color: #888888; font-size: 11px; }
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

    @php
        $fmt = function($arr) {
            $parts = [];
            foreach ($arr as $u => $q) {
                if ($q > 0) $parts[] = $q . ' ' . $u;
            }
            return count($parts) ? implode(' ', $parts) : '-';
        };
    @endphp

    <table class="table-data">
        <thead>
            <tr>
                <th class="text-center col-no">NO</th>
                <th style="width: 50%;">BARANG RETUR</th>
                <th class="text-right" style="width: 20%;">TOTAL TRANSAKSI RETUR</th>
                <th class="text-right" style="width: 25%;">AKUMULASI QTY RETUR</th>
            </tr>
        </thead>
        @forelse(($data ?? []) as $i => $row)
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
            <tbody>
                <tr class="row-parent">
                    <td class="text-center text-gray font-normal">{{ $i + 1 }}.</td>
                    <td>{{ $row['item_name'] ?? '-' }}</td>
                    <td class="text-right">{{ (int)($row['transaction_count'] ?? 0) }} Transaksi</td>
                    <td class="text-right">{{ $fmt($qtyMap) }}</td>
                </tr>
                <tr class="row-child-container">
                    <td colspan="4">
                        <table class="table-detail">
                            <thead>
                                <tr>
                                    <th style="width: 20%;">TANGGAL RETUR</th>
                                    <th style="width: 20%;">REFERENSI PO</th>
                                    <th style="width: 30%;">SUPPLIER</th>
                                    <th class="text-right" style="width: 15%;">QTY</th>
                                    <th class="text-right" style="width: 15%;">SUBTOTAL</th>
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
                    </td>
                </tr>
            </tbody>
        @empty
            <tbody>
                <tr class="row-parent">
                    <td colspan="4" class="text-center text-gray font-normal">Tidak ada data laporan retur</td>
                </tr>
            </tbody>
        @endforelse
    </table>
</body>
</html>
