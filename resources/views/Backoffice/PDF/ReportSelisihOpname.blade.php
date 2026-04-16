<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Selisih Stok Opname</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11px; color: #222; line-height: 1.4; margin: 0; padding: 30px 20px; }
        .report-header { border-bottom: 2px solid #000; padding-bottom: 12px; margin-bottom: 24px; width: 100%; }
        .report-title { font-size: 20px; font-weight: bold; color: #000; margin: 0 0 4px 0; text-transform: uppercase; letter-spacing: .5px; }
        .report-meta { color: #666; font-size: 10px; }
        .params-table { width: 100%; margin-bottom: 35px; }
        .params-table td { padding: 3px 0; font-size: 11px; }
        .params-label { color: #555; width: 90px; }
        .params-val { color: #000; font-weight: bold; }
        /*
         * Header 4 kolom hanya di awal laporan (gambar 2).
         * Pakai <thead> di tabel TERPISAH yang cuma 1 baris — isi tidak panjang sehingga tidak “ngikut” ke halaman berikutnya seperti thead tabel besar.
         * (Beberapa versi DomPDF merender <th> di <tbody> kurang konsisten.)
         */
        .table-legend {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto;
            margin-bottom: 6px;
            page-break-after: avoid;
        }
        .table-legend thead tr th {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            border-left: none;
            border-right: none;
            padding: 9px 5px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
            color: #3d4f62;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            vertical-align: middle;
        }
        .table-legend thead tr th.legend-th-right {
            text-align: right;
        }
        .opname-block {
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ccc;
            page-break-inside: auto;
        }
        /* Jaga header utama (legend) + awal grup pertama tetap berurutan di PDF */
        .opname-block-first {
            page-break-before: avoid;
        }
        .opname-block:last-of-type { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        /* Ringkasan kecil saja — hindari pisah dari awal tabel detail */
        .group-summary {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            border-bottom: 1px solid #e8e8e8;
            page-break-after: avoid;
            page-break-inside: avoid;
        }
        .group-summary td {
            padding: 10px 6px 8px 6px;
            font-size: 11px;
            color: #000;
            font-weight: bold;
            vertical-align: middle;
        }
        /* Tabel detail punya <thead> sendiri: yang diulang saat sambungan halaman hanya header kolom detail */
        .table-detail {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            table-layout: auto;
            border: 1px solid #e0e4eb;
        }
        .table-detail thead {
            display: table-header-group;
        }
        .table-detail thead th {
            background-color: #f5f7fb;
            border-bottom: 1px solid #dde3ea;
            padding: 7px 6px;
            font-size: 8px;
            color: #5c6573;
            font-weight: bold;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 0.35px;
        }
        .table-detail td {
            padding: 7px 6px;
            font-size: 10px;
            color: #333;
            border-bottom: 1px solid #eef0f4;
            vertical-align: top;
        }
        .table-detail tbody tr:nth-child(even) td { background-color: #fafbfd; }
        .table-detail tbody tr:last-child td { border-bottom: none; }
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        .font-normal { font-weight: normal; }
        .text-gray { color: #777; }
        .col-no { width: 5%; color: #888; font-size: 11px; }
        .text-green { color: #059669; }
        .text-red { color: #dc2626; }
        .opname-code { font-weight: bold; font-size: 11px; color: #111; }
        .opname-meta { font-weight: normal; color: #666; font-size: 9px; line-height: 1.35; }
        .cell-num { font-variant-numeric: tabular-nums; }
        .empty-msg { padding: 14px 6px; text-align: center; color: #777; font-size: 11px; }
    </style>
</head>
<body>
    <div class="report-header">
        <h1 class="report-title">Laporan Selisih Stok Opname</h1>
        <div class="report-meta">TANGGAL CETAK: {{ strtoupper(now()->format('d M Y H:i')) }}</div>
    </div>

    <table class="params-table">
        @php
            \Carbon\Carbon::setLocale('id');
            $startFormatted = '-';
            $endFormatted = '-';
            if (!empty($start_date) && $start_date !== '-') {
                try { $startFormatted = \Carbon\Carbon::createFromFormat('d-m-Y', $start_date)->translatedFormat('j F Y'); }
                catch (\Throwable $th) { try { $startFormatted = \Carbon\Carbon::parse($start_date)->translatedFormat('j F Y'); } catch (\Throwable $th2) { $startFormatted = '-'; } }
            }
            if (!empty($end_date) && $end_date !== '-') {
                try { $endFormatted = \Carbon\Carbon::createFromFormat('d-m-Y', $end_date)->translatedFormat('j F Y'); }
                catch (\Throwable $th) { try { $endFormatted = \Carbon\Carbon::parse($end_date)->translatedFormat('j F Y'); } catch (\Throwable $th2) { $endFormatted = '-'; } }
            }
            $rows = is_array($data ?? null) ? $data : [];
        @endphp
        <tr><td class="params-label">PERIODE</td><td class="params-val">: {{ $startFormatted }} s/d {{ $endFormatted }}</td></tr>
        <tr><td class="params-label">TYPE</td><td class="params-val">: {{ strtoupper($type_label ?? 'ALL') }}</td></tr>
        <tr><td class="params-label">ITEM</td><td class="params-val">: {{ strtoupper($item_name ?? 'SEMUA ITEM') }}</td></tr>
    </table>

    @if(count($rows) < 1)
        <p class="empty-msg font-normal">Tidak ada data selisih stok opname</p>
    @else
        <table class="table-legend">
            <thead>
                <tr>
                    <th scope="col" style="width:5%;">NO</th>
                    <th scope="col" style="width:36%;">KODE OPNAME</th>
                    <th scope="col" class="legend-th-right" style="width:29%;">TOTAL ITEM SELISIH</th>
                    <th scope="col" class="legend-th-right" style="width:30%;">NOMINAL (+/-)</th>
                </tr>
            </thead>
        </table>

        @foreach($rows as $i => $row)
            @php
                $tgl = '-';
                try { $tgl = \Carbon\Carbon::parse($row['tanggal'] ?? null)->format('d M Y'); } catch (\Throwable $th) {}
                $details = $row['details'] ?? [];
                $nom = (float)($row['total_nominal'] ?? 0);
                $nItems = (int)($row['total_item_selisih'] ?? count($details));
            @endphp
            <div class="opname-block @if($loop->first) opname-block-first @endif">
                <table class="group-summary">
                    <tr>
                        <td class="text-center text-gray font-normal" style="width:5%;">{{ $i + 1 }}.</td>
                        <td style="width:36%;">
                            <span class="opname-code">{{ $row['kode'] ?? '-' }}</span><br>
                            <span class="opname-meta">{{ $tgl }}</span>
                        </td>
                        <td class="text-right" style="width:29%;">{{ $nItems }} Item Selisih</td>
                        <td class="text-right" style="width:30%;">
                            @if($nom > 0)
                                <span class="text-green">+ Rp {{ number_format(abs($nom), 0, ',', '.') }}</span>
                            @elseif($nom < 0)
                                <span class="text-red">- Rp {{ number_format(abs($nom), 0, ',', '.') }}</span>
                            @else
                                Rp 0
                            @endif
                        </td>
                    </tr>
                </table>
                <table class="table-detail">
                    <thead>
                        <tr>
                            <th style="width:10%;">Sumber</th>
                            <th style="width:24%;">Item</th>
                            <th class="text-right" style="width:14%;">Stok sistem</th>
                            <th class="text-right" style="width:14%;">Stok fisik</th>
                            <th class="text-right" style="width:12%;">Selisih</th>
                            <th class="text-right" style="width:13%;">Harga satuan</th>
                            <th class="text-right" style="width:13%;">Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($details as $d)
                            @php
                                $item = ($d['item_name'] ?? '-') . ((isset($d['variant_name']) && $d['variant_name'] && $d['variant_name'] !== '-') ? ' - '.$d['variant_name'] : '');
                                $dnom = (float)($d['nominal'] ?? 0);
                                $sumLabel = strtoupper($d['sumber'] ?? '-');
                            @endphp
                            <tr>
                                <td><span style="font-size:9px;letter-spacing:0.02em;">{{ $sumLabel }}</span></td>
                                <td>{{ $item }}</td>
                                <td class="text-right cell-num">{{ $d['stock_system'] ?? '-' }}</td>
                                <td class="text-right cell-num">{{ $d['stock_fisik'] ?? '-' }}</td>
                                <td class="text-right cell-num">{{ $d['selisih_text'] ?? '-' }}</td>
                                <td class="text-right cell-num">Rp {{ number_format((float)($d['harga_satuan'] ?? 0), 0, ',', '.') }}</td>
                                <td class="text-right cell-num">
                                    @if($dnom > 0)
                                        <span class="text-green">+ Rp {{ number_format(abs($dnom), 0, ',', '.') }}</span>
                                    @elseif($dnom < 0)
                                        <span class="text-red">- Rp {{ number_format(abs($dnom), 0, ',', '.') }}</span>
                                    @else
                                        Rp 0
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-gray">Tidak ada detail selisih</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endforeach
    @endif
    @include('Backoffice.PDF.partials.dicetak_oleh')
</body>
</html>
