<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Perintah Kerja - {{ $planning['pp_number'] }}</title>
    <style>
        /* A5 landscape 210×148mm — footer (TTD + skala lengkap) di margin bawah */
        @page { size: 210mm 148mm; margin: 4mm 5mm 42mm; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 8.5pt; line-height: 1.2; margin: 0; color: #1e293b; }
        table { width: 100%; border-collapse: collapse; }
        .header td { padding: 0; vertical-align: middle; }
        .logo { max-height: 9mm; max-width: 9mm; }
        .company { font-size: 8pt; font-weight: bold; color: #0f172a; }
        .address { font-size: 5pt; color: #475569; }
        .title { font-size: 9pt; font-weight: bold; color: #0f172a; margin-top: 0.5mm; line-height: 1.15; }
        .reference { font-size: 5.5pt; color: #64748b; }
        .divider { border-top: 1pt solid #0f172a; margin-top: 1.5mm; margin-bottom: 1.5mm; }
        .meta-bar { font-size: 8pt; color: #0f172a; margin-bottom: 1.5mm; }
        .items { table-layout: fixed; width: 100%; border-collapse: collapse; }
        .items thead { display: table-header-group; }
        .items tr { page-break-inside: avoid; }
        .items th { background: #1e293b; color: #ffffff; border: 0.4pt solid #334155; text-align: center; font-size: 6.5pt; font-weight: bold; padding: 1.2mm 0.8mm; text-transform: uppercase; letter-spacing: 0.3px; }
        .items th.col-name { text-align: left; padding-left: 2mm; }
        .items td { border: 0.4pt solid #cbd5e1; padding: 0.8mm 1mm; font-size: 7.5pt; height: 5.2mm; vertical-align: middle; overflow-wrap: break-word; }
        .items td.col-no { text-align: center; color: #64748b; font-weight: 600; }
        .items td.product { padding-left: 2mm; font-weight: 600; color: #0f172a; text-align: left; }
        .row-odd { background: #f8fafc; }
        .center { text-align: center; }
        .bottom { position: fixed; left: 0; right: 0; bottom: -38mm; height: 38mm; }
        .sign td { width: 33.33%; text-align: center; vertical-align: top; font-size: 8.5pt; font-weight: bold; color: #0f172a; padding: 0 2.5mm; }
        .sign-role { display: block; margin-bottom: 1mm; font-size: 8.5pt; }
        .sign-name { font-size: 8pt; font-weight: 600; }
        .sign-img { height: 12mm; max-width: 36mm; display: block; margin: 0 auto 0.5mm; }
        .sign-line { border-top: 0.7pt solid #0f172a; margin-bottom: 1mm; margin-top: 0.5mm; }
        .space { height: 13mm; text-align: center; }
        .legend { font-size: 5pt; margin-top: 2mm; border-top: 0.5pt dashed #cbd5e1; padding-top: 1mm; color: #334155; }
        .legend-title { font-weight: bold; font-size: 5.5pt; color: #0f172a; margin-bottom: 0.5mm; }
        .legend-grid td { width: 25%; vertical-align: top; padding: 0.2mm 1mm 0.2mm 0; line-height: 1.25; }
        .legend-ok { margin-top: 0.8mm; font-size: 5pt; }
    </style>
</head>
<body>
    <div class="bottom">
        <table class="sign">
            <tr>
                @php
                    $dibuatOleh = trim((string) ($planning['created_by'] ?? ''));
                    if ($dibuatOleh === '' || $dibuatOleh === '-') {
                        $dibuatOleh = '................................';
                    }
                    $kepalaOps = trim((string) ($planning['approval_snapshot']['name'] ?? ''));
                    if ($kepalaOps === '') {
                        $kepalaOps = '................................';
                    }
                @endphp
                @foreach (['Dibuat Oleh', 'Kepala Operasional', 'Diterima Oleh'] as $role)
                    <td>
                        <span class="sign-role">{{ $role }}</span>
                        <div class="space">
                            @if($loop->index === 1 && !empty($planning['approval_snapshot']['signature']))
                                <img class="sign-img" src="{{ $planning['approval_snapshot']['signature'] }}" alt="Tanda tangan">
                            @endif
                        </div>
                        <div class="sign-line"></div>
                        <span class="sign-name">( {{ $loop->index === 0 ? $dibuatOleh : ($loop->index === 1 ? $kepalaOps : '................................') }} )</span>
                    </td>
                @endforeach
            </tr>
        </table>
        <div class="legend">
            <div class="legend-title">Skala Prioritas</div>
            <table class="legend-grid">
                @php
                    $skalaList = collect($skalas ?? []);
                    $chunks = $skalaList->chunk(4);
                @endphp
                @forelse ($chunks as $group)
                    <tr>
                        @foreach ($group as $skala)
                            <td>
                                <strong>{{ $skala->code }}</strong> = {{ $skala->name }}@if(!empty($skala->combo_label)) ({{ $skala->combo_label }})@endif
                            </td>
                        @endforeach
                        @for ($pad = $group->count(); $pad < 4; $pad++)
                            <td></td>
                        @endfor
                    </tr>
                @empty
                    <tr><td colspan="4" style="color:#94a3b8;">Belum ada master skala</td></tr>
                @endforelse
            </table>
            <div class="legend-ok"><strong>OK</strong> = Sudah selesai produksi &nbsp; | &nbsp; <strong>NOK</strong> = Belum selesai produksi</div>
        </div>
    </div>
    <table class="header">
        <tr>
            <td style="width: 11mm;">
                @if (!empty($logo_base64))<img class="logo" src="{{ $logo_base64 }}" alt="Logo">@endif
            </td>
            <td style="padding-left: 1.5mm;">
                <div class="company">{{ $company_name }}</div>
                <div class="address">{{ $company_address ?? '' }}</div>
            </td>
            <td style="width: 65mm; text-align: right;">
                <div class="reference">{{ $planning['spkp_number'] ?? $planning['pp_number'] }}</div>
                <div class="title">SURAT PERINTAH KERJA<br>PRODUKSI</div>
            </td>
        </tr>
    </table>
    <div class="divider"></div>
    <div class="meta-bar"><strong>Tanggal:</strong> {{ $planning['date'] }}</div>
    <table class="items">
        <thead>
            <tr>
                <th style="width: 4%;">No.</th>
                <th class="col-name" style="width: 44%;">Nama Barang</th>
                <th style="width: 8%;">Skala</th>
                <th style="width: 16%;">PIC</th>
                <th style="width: 11%;">QTY</th>
                <th style="width: 8.5%;">Hasil</th>
                <th style="width: 8.5%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($planning['items'] as $item)
                <tr class="{{ $loop->odd ? 'row-odd' : '' }}">
                    <td class="col-no">{{ $loop->iteration }}</td>
                    <td class="product">{{ $item['product_name'] }}</td>
                    <td class="center">{{ $item['skala_code'] ?? '' }}</td>
                    <td class="center">{{ $item['pic_name'] ?? '' }}</td>
                    <td class="center">{{ rtrim(rtrim(number_format($item['qty'], 2, '.', ''), '0'), '.') }} {{ $item['unit_label'] }}</td>
                    <td class="center">@if(($item['actual_qty'] ?? null) !== null){{ rtrim(rtrim(number_format($item['actual_qty'], 2, '.', ''), '0'), '.') }}@endif</td>
                    <td class="center">@if(($item['actual_qty'] ?? null) !== null){{ (($item['actual_qty'] + 0.0001) >= $item['qty']) ? 'OK' : 'NOK' }}@endif</td>
                </tr>
            @endforeach
            @for ($i = count($planning['items']); $i < 8; $i++)
                <tr class="{{ $i % 2 === 0 ? 'row-odd' : '' }}">
                    <td class="col-no">{{ $i + 1 }}</td>
                    <td></td><td></td><td></td><td></td><td></td><td></td>
                </tr>
            @endfor
        </tbody>
    </table>
</body>
</html>
