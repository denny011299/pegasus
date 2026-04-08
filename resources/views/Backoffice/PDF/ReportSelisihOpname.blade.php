<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Selisih Stok Opname</title>
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
        .text-gray { color: #777; }
    </style>
</head>
<body>
    <div class="report-header">
        <h1 class="report-title">Laporan Selisih Stok Opname</h1>
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
        <tr><td class="params-label">TYPE</td><td class="params-val">: {{ strtoupper($type_label ?? 'ALL') }}</td></tr>
        <tr><td class="params-label">ITEM</td><td class="params-val">: {{ strtoupper($item_name ?? 'SEMUA ITEM') }}</td></tr>
    </table>

    <table class="table-data">
        <thead>
            <tr>
                <th style="width:5%" class="text-center">NO</th>
                <th style="width:20%">KODE OPNAME</th>
                <th style="width:20%">TANGGAL</th>
                <th style="width:25%" class="text-right">ITEM SELISIH</th>
                <th style="width:30%" class="text-right">NOMINAL (+/-)</th>
            </tr>
        </thead>
        @forelse(($data ?? []) as $i => $row)
            @php
                $tgl = '-';
                try { $tgl = \Carbon\Carbon::parse($row['tanggal'])->format('d M Y'); } catch (\Throwable $th) {}
                $nom = (float)($row['total_nominal'] ?? 0);
            @endphp
            <tbody>
                <tr class="row-parent">
                    <td class="text-center">{{ $i + 1 }}.</td>
                    <td>{{ $row['kode'] ?? '-' }}</td>
                    <td>{{ $tgl }}</td>
                    <td class="text-right">{{ (int)($row['total_item_selisih'] ?? 0) }} Item Selisih</td>
                    <td class="text-right">
                        @if($nom > 0)
                            <span class="text-green">+ Rp {{ number_format(abs($nom), 0, ',', '.') }}</span>
                        @elseif($nom < 0)
                            <span class="text-red">- Rp {{ number_format(abs($nom), 0, ',', '.') }}</span>
                        @else
                            Rp 0
                        @endif
                    </td>
                </tr>
                <tr class="row-child-container">
                    <td colspan="5">
                        <table class="table-detail">
                            <thead>
                                <tr>
                                    <th style="width:10%">SUMBER</th>
                                    <th style="width:24%">ITEM</th>
                                    <th style="width:14%">STOK SISTEM</th>
                                    <th style="width:14%">STOK FISIK</th>
                                    <th style="width:12%">SELISIH</th>
                                    <th style="width:13%" class="text-right">HARGA SATUAN</th>
                                    <th style="width:13%" class="text-right">NOMINAL</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($row['details'] ?? []) as $d)
                                    @php
                                        $item = ($d['item_name'] ?? '-') . ((isset($d['variant_name']) && $d['variant_name'] && $d['variant_name'] !== '-') ? ' - '.$d['variant_name'] : '');
                                        $dnom = (float)($d['nominal'] ?? 0);
                                    @endphp
                                    <tr>
                                        <td>{{ strtoupper($d['sumber'] ?? '-') }}</td>
                                        <td>{{ $item }}</td>
                                        <td>{{ $d['stock_system'] ?? '-' }}</td>
                                        <td>{{ $d['stock_fisik'] ?? '-' }}</td>
                                        <td>{{ $d['selisih_text'] ?? '-' }}</td>
                                        <td class="text-right">Rp {{ number_format((float)($d['harga_satuan'] ?? 0), 0, ',', '.') }}</td>
                                        <td class="text-right">
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
                    </td>
                </tr>
            </tbody>
        @empty
            <tbody><tr class="row-parent"><td colspan="5" class="text-center text-gray">Tidak ada data selisih stok opname</td></tr></tbody>
        @endforelse
    </table>
</body>
</html>
