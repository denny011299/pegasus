<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Work Order &mdash; {{ $wo_number }}</title>
    <style>
        /* A6 landscape 148×105mm — reserve bottom for signatures */
        @page { size: 148mm 105mm; margin: 3mm 3.5mm 22mm; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 7pt; line-height: 1.2; color: #1e293b; margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: middle; padding: 0; }
        .company-logo { max-height: 7mm; max-width: 7mm; }
        .company-name { font-size: 6.5pt; font-weight: bold; color: #0f172a; }
        .company-address, .company-contact { font-size: 4.5pt; color: #475569; }
        .header-right { text-align: right; }
        .doc-code { font-size: 4.5pt; color: #64748b; }
        .doc-title { font-size: 9pt; font-weight: bold; margin: 0.5mm 0; }
        .header-divider-thick { border-top: 0.8pt solid #0f172a; margin-top: 1mm; }
        .meta-row td { padding: 0.8mm 0; font-size: 6.5pt; }
        .items-table { table-layout: fixed; }
        .items-table thead { display: table-header-group; }
        .items-table tr { page-break-inside: avoid; }
        .items-table th { background: #1e293b; color: #fff; font-size: 5pt; padding: 0.7mm 0.5mm; border: 0.4pt solid #1e293b; text-align: left; }
        .items-table td { border: 0.4pt solid #cbd5e1; padding: 0.35mm 0.5mm; font-size: 6.5pt; vertical-align: middle; overflow-wrap: break-word; }
        .items-table .col-no { width: 5mm; text-align: center; }
        .items-table .col-qty { width: 11mm; text-align: center; }
        .items-table .col-skala { width: 8mm; text-align: center; }
        .items-table .col-status { width: 16mm; text-align: center; }
        .items-table td.col-status { height: 5mm; }
        .col-name, .col-qty { font-weight: bold; }
        .row-odd { background: #f8fafc; }
        .page-bottom { position: fixed; left: 0; right: 0; bottom: -19mm; height: 19mm; }
        .sign-table { table-layout: fixed; }
        .sign-table td { width: 50%; text-align: center; vertical-align: top; padding: 0 1.5mm; }
        .sign-role { font-weight: bold; font-size: 6.5pt; }
        .sign-space { height: 8mm; }
        .sign-line { border-top: 0.4pt solid #0f172a; width: 90%; margin: 0 auto; }
        .sign-name { font-size: 5pt; font-weight: bold; margin-top: 0.5mm; overflow-wrap: break-word; }
        .doc-footer { margin-top: 1mm; border-top: 0.4pt dashed #cbd5e1; }
        .doc-footer td { font-size: 4pt; color: #64748b; padding-top: 0.5mm; }
    </style>
</head>
<body>
    <div class="page-bottom">
    <div class="sign-container">
        <table class="sign-table">
            <tr>
                <td>
                    <div class="sign-role">PIC</div>
                    <div class="sign-space"></div>
                    <div class="sign-line"></div>
                    <div class="sign-name">( {{ !empty($pic_name) ? $pic_name : '................................' }} )</div>
                </td>
                <td>
                    <div class="sign-role">Wakil Kepala Operasional</div>
                    <div class="sign-space"></div>
                    <div class="sign-line"></div>
                    <div class="sign-name">( ........................................ )</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="doc-footer" style="font-size: 5pt; padding-top: 0.5mm;">
        <div style="font-size:4.5pt">Referensi: {{ $wo_number }} | {{ $pp_number }}</div>
        <strong>OK</strong> = Tercapai &nbsp; | &nbsp; <strong>NOK</strong> = Tidak tercapai; tuliskan kekurangan Dus / QTY.
    </div>
    </div>
    <table class="header-table">
        <tr>
            <td style="width: 8mm;">
                @if (!empty($logo_base64))<img src="{{ $logo_base64 }}" class="company-logo" alt="Logo">@endif
            </td>
            <td style="padding-left: 1mm;">
                <div class="company-name">{{ $company_name ?? 'Pegasus Hikari Group' }}</div>
                <div class="company-address">{{ $company_address ?? '' }}</div>
                <div class="company-contact">{{ $company_contact ?? '' }}</div>
            </td>
            <td class="header-right" style="width: 32mm;">
                <div class="doc-code">FORM-OPS-09, Rev.00</div>
                <div class="doc-title">Work Order (WO)</div>
            </td>
        </tr>
    </table>
    <div class="header-divider-thick"></div>
    <table class="meta-row" style="margin: 1.5mm 0 1mm;">
        <tr>
            <td><strong>Nama / PIC:</strong> {{ $pic_name }}</td>
            <td style="text-align: right;"><strong>Tanggal:</strong> {{ $wo_date }}</td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th class="col-no" style="width: 6%;">No.</th>
                <th style="width: 34%;">Nama Produk</th>
                <th class="col-qty" style="width: 14%;">QTY</th>
                <th class="col-qty" style="width:16%;">Hasil QTY</th>
                <th class="col-skala" style="width: 12%;">Skala</th>
                <th class="col-status" style="width: 18%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $row)
                <tr class="{{ $loop->even ? 'row-even' : 'row-odd' }}">
                    <td class="col-no">{{ $row['no'] }}</td>
                    <td class="col-name">{{ $row['product_name'] }}</td>
                    <td class="col-qty">{{ rtrim(rtrim(number_format($row['qty'], 2, '.', ''), '0'), '.') }}{{ $row['unit_label'] ? ' '.$row['unit_label'] : '' }}</td>
                    <td class="col-qty">{{ $row['actual'] ?? '' }}</td>
                    <td class="col-skala">{{ $row['skala'] }}</td>
                    <td class="col-status">{{ $row['status'] }} @if(($row['excess'] ?? 0)>0)<br>Kelebihan {{ $row['excess'] }}@endif</td>
                </tr>
            @empty
                <tr class="row-even">
                    <td colspan="6" style="text-align: center; color: #64748b; padding: 6px;">Tidak ada item produksi</td>
                </tr>
            @endforelse

            @php $padFrom = max(1, count($items)); @endphp
            @for ($i = $padFrom; $i < max(4, $padFrom); $i++)
                <tr class="{{ $i % 2 === 0 ? 'row-odd' : 'row-even' }}">
                    <td class="col-no">{{ $i + 1 }}</td>
                    <td class="col-name">&nbsp;</td>
                    <td class="col-qty">&nbsp;</td>
                    <td class="col-qty">&nbsp;</td>
                    <td class="col-skala">&nbsp;</td>
                    <td class="col-status">&nbsp;</td>
                </tr>
            @endfor
        </tbody>
    </table>

</body>
</html>
