<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Produksi</title>
    <style>
        /* Standar Font Korporat */
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #222222;
            line-height: 1.4;
            margin: 0;
            padding: 30px 20px;
        }

        /* Header Laporan */
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

        /* Parameter/Filter Laporan */
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
        .table-legend thead tr {
            border-top: none;
            border-bottom: none;
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

        /* Tabel Rincian (Detail) */
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
        .table-detail tbody tr:last-child td {
            border-bottom: none;
        }

        /* Utilities */
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        .font-normal { font-weight: normal; }
        .text-gray { color: #777777; }
        .product-name { word-break: break-word; line-height: 1.2; }

        /* Penomoran yang Halus */
        .col-no {
            width: 5%;
            color: #888888;
            font-size: 11px;
        }

        /* Status Text */
        .status-text {
            font-size: 10px;
            font-weight: bold;
            color: #059669;
            border: 1px solid #059669;
            padding: 2px 6px;
            border-radius: 3px;
        }
        .empty-msg { padding: 12px; text-align: center; color: #777777; font-size: 11px; }
    </style>
</head>
<body>

    <div class="report-header">
        <h1 class="report-title">Laporan Produksi</h1>
        <div class="report-meta">TANGGAL CETAK: {{ strtoupper(now()->format('d M Y H:i')) }}</div>
    </div>

    <table class="params-table">
        @php
            \Carbon\Carbon::setLocale('id');
            $startFormatted = '-';
            $endFormatted = '-';
            try {
                $startFormatted = \Carbon\Carbon::createFromFormat('d-m-Y', $start_date)->translatedFormat('j F Y');
            } catch (\Throwable $th) {
                if (!empty($start_date)) $startFormatted = \Carbon\Carbon::parse($start_date)->translatedFormat('j F Y');
            }
            try {
                $endFormatted = \Carbon\Carbon::createFromFormat('d-m-Y', $end_date)->translatedFormat('j F Y');
            } catch (\Throwable $th) {
                if (!empty($end_date)) $endFormatted = \Carbon\Carbon::parse($end_date)->translatedFormat('j F Y');
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
            <td class="params-label">PRODUK</td>
            <td class="params-val">: {{ strtoupper($product_name ?? 'Semua Produk') }}</td>
        </tr>
    </table>

    @if(count($rows) < 1)
        <p class="empty-msg font-normal">Tidak ada data laporan produksi</p>
    @else
        <table class="table-legend">
            <thead>
                <tr>
                    <th class="text-center col-no" scope="col" style="width:5%;">NO</th>
                    <th scope="col" style="width: 36%;">NAMA PRODUK</th>
                    <th class="text-right" scope="col" style="width: 20%;">TOTAL PRODUKSI</th>
                    <th class="text-right" scope="col" style="width: 20%;">BERHASIL</th>
                    <th class="text-right" scope="col" style="width: 19%;">DITOLAK</th>
                </tr>
            </thead>
        </table>

        @foreach($rows as $i => $row)
            @php
                $details = $row['details'] ?? [];
                $totalCount = count($details);
                $successCount = 0;
                $rejectCount = 0;
                $allQty = [];
                $successQty = [];
                $rejectQty = [];

                foreach ($details as $d) {
                    $unit = trim($d['unit_name'] ?? '');
                    if ($unit === '') $unit = 'unit';

                    if (!isset($allQty[$unit])) $allQty[$unit] = 0;
                    $allQty[$unit] += (int)($d['qty'] ?? 0);

                    if ((int)($d['status'] ?? 0) === 2) {
                        $successCount++;
                        if (!isset($successQty[$unit])) $successQty[$unit] = 0;
                        $successQty[$unit] += (int)($d['qty'] ?? 0);
                    }

                    if ((int)($d['status'] ?? 0) === 3) {
                        $rejectCount++;
                        if (!isset($rejectQty[$unit])) $rejectQty[$unit] = 0;
                        $rejectQty[$unit] += (int)($d['qty'] ?? 0);
                    }
                }
            @endphp
            <div class="report-group-block @if($loop->first) report-group-block-first @endif">
                <table class="group-summary">
                    <tr>
                        <td class="text-center text-gray font-normal" style="width:5%;">{{ $i + 1 }}.</td>
                        <td class="product-name" style="width:36%;">{{ $row['product_name'] ?? '-' }}</td>
                        <td class="text-right" style="width:20%;">{{ $totalCount }} Produksi <br> <span class="font-normal text-gray">({{ $fmt($allQty) }})</span></td>
                        <td class="text-right" style="width:20%;">{{ $successCount }} Berhasil <br> <span class="font-normal text-gray">({{ $fmt($successQty) }})</span></td>
                        <td class="text-right" style="width:19%;">
                            @if($rejectCount > 0)
                                {{ $rejectCount }} Tolak <br> <span class="font-normal text-gray">({{ $fmt($rejectQty) }})</span>
                            @else
                                <span class="font-normal text-gray">0 (-)</span>
                            @endif
                        </td>
                    </tr>
                </table>
                <table class="table-detail">
                    <thead>
                        <tr>
                            <th class="text-gray font-normal" style="width: 25%; border-bottom: 1px solid #eee; padding: 5px 4px; font-size: 9px;">TANGGAL</th>
                            <th class="text-gray font-normal" style="width: 25%; border-bottom: 1px solid #eee; padding: 5px 4px; font-size: 9px;">KODE PRODUKSI</th>
                            <th class="text-right text-gray font-normal" style="width: 25%; border-bottom: 1px solid #eee; padding: 5px 4px; font-size: 9px;">QTY</th>
                            <th class="text-center text-gray font-normal" style="width: 25%; border-bottom: 1px solid #eee; padding: 5px 4px; font-size: 9px;">STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($details as $d)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($d['production_date'])->format('d M Y') }}</td>
                                <td>{{ $d['production_code'] ?? '-' }}</td>
                                <td class="text-right">{{ $d['qty'] ?? 0 }} {{ $d['unit_name'] ?? '' }}</td>
                                <td class="text-center">
                                    @if((int)($d['status'] ?? 0) === 1)
                                        <span class="status-text" style="color:#7a7a7a;border-color:#7a7a7a;">PENDING</span>
                                    @elseif((int)($d['status'] ?? 0) === 2)
                                        <span class="status-text">BERHASIL</span>
                                    @elseif((int)($d['status'] ?? 0) === 3)
                                        <span class="status-text" style="color:#dc2626;border-color:#dc2626;">TOLAK</span>
                                    @elseif((int)($d['status'] ?? 0) === 4)
                                        <span class="status-text" style="color:#b45309;border-color:#b45309;">MENUNGGU BATAL</span>
                                    @else
                                        <span class="font-normal text-gray">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-gray">Tidak ada detail</td>
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
