<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Form Sisa Barang</title>
    <style>
        @page { size: 147.81mm 210.26mm; margin: 12mm 14mm 12mm 14mm; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111;
            margin: 0;
        }
        .title {
            font-size: 20px;
            font-weight: bold;
            margin: 0 0 14px 0;
        }
        .meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .meta td {
            padding: 5px 0;
            vertical-align: bottom;
            font-size: 11px;
        }
        .meta .label {
            white-space: nowrap;
            width: 1%;
            padding-right: 4px;
        }
        .meta .colon {
            width: 10px;
            padding-right: 6px;
        }
        .meta .dots {
            border-bottom: 1px solid #111;
            font-weight: bold;
            padding: 0 4px 2px;
        }
        .items {
            width: 100%;
            border-collapse: collapse;
        }
        .items th {
            font-weight: bold;
            font-size: 11px;
            padding: 6px 8px;
            border-top: 1px solid #111;
            border-bottom: 1px solid #111;
        }
        .items th.nama { text-align: left; }
        .items th.qty,
        .items td.qty {
            width: 90px;
            text-align: center;
        }
        .items td {
            padding: 7px 8px;
            font-size: 11px;
            border-bottom: 1px solid #111;
            height: 20px;
        }
        .known {
            margin-top: 18px;
            margin-bottom: 10px;
            font-size: 11px;
            font-weight: bold;
        }
        .sign {
            width: 100%;
            border-collapse: collapse;
        }
        .sign td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 16px;
        }
        .sign .role {
            font-weight: bold;
            font-size: 11px;
        }
        .sign .space {
            height: 88px;
        }
        .sign .line {
            border-top: 1px solid #111;
            width: 80%;
            margin: 0 auto;
        }
        .sign .name {
            font-size: 11px;
            margin-top: 4px;
            min-height: 14px;
        }
    </style>
</head>
<body>
    <div class="title">Form Sisa Barang</div>

    <table class="meta">
        <tr>
            <td class="label">Tanggal</td>
            <td class="colon">:</td>
            <td class="dots">{{ $tanggal !== '' ? $tanggal : ' ' }}</td>
            <td class="label" style="padding-left:16px;">Jam</td>
            <td class="colon">:</td>
            <td class="dots">{{ $jam !== '' ? $jam : ' ' }}</td>
        </tr>
        <tr>
            <td class="label">Nama PIC (Yang Angkat Barang)</td>
            <td class="colon">:</td>
            <td class="dots" colspan="4">{{ $pic_name !== '' ? $pic_name : ' ' }}</td>
        </tr>
        <tr>
            <td class="label">Nomor Dokumen</td>
            <td class="colon">:</td>
            <td class="dots" colspan="4">{{ $nomor_dokumen !== '' ? $nomor_dokumen : ' ' }}</td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th class="nama">Nama Produk</th>
                <th class="qty">QTY</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr>
                    <td>{{ $item['nama'] !== '' ? $item['nama'] : ' ' }}</td>
                    <td class="qty">
                        @if ($item['qty'] !== null)
                            {{ $item['qty'] }}{{ $item['unit'] !== '' ? ' ' . $item['unit'] : '' }}
                        @else
                            {{ ' ' }}
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="known">Diketahui Oleh</div>
    <table class="sign">
        <tr>
            <td>
                <div class="role">Kepala Operasional</div>
                <div class="space"></div>
                <div class="line"></div>
                <div class="name">{{ $kepala_operasional !== '' ? $kepala_operasional : ' ' }}</div>
            </td>
            <td>
                <div class="role">Sopir</div>
                <div class="space"></div>
                <div class="line"></div>
                <div class="name">{{ $sopir_name !== '' ? $sopir_name : ' ' }}</div>
            </td>
        </tr>
    </table>
</body>
</html>
