<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Label Barcode {{ strtoupper($paper_size ?? 'A4') }}</title>
    <style>
        @page {
            margin: 8mm 6mm;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        .grid {
            width: 100%;
        }

        .grid::after {
            content: "";
            display: table;
            clear: both;
        }

        .label {
            float: left;
            width: {{ ($paper_size ?? 'a4') === 'a5' ? '45mm' : '48mm' }};
            height: {{ ($paper_size ?? 'a4') === 'a5' ? '22mm' : '24mm' }};
            box-sizing: border-box;
            padding: 2mm 3mm;
            text-align: center;
            font-size: 3pt;
            border: 0.3pt dashed #ccc;
            overflow: hidden;
        }

        .nama {
            font-weight: bold;
            font-size: 6.5pt;
            line-height: 1.15;
            margin-bottom: 0.5mm;
            text-align: left;
            max-width: 100%;
            overflow: hidden;
            white-space: normal;
            word-break: break-word;
            max-height: 2.3em;
        }

        .barcode {
            margin: 0;
            width: 100%;
            height: 8mm;
            overflow: hidden;
            text-align: left;
            line-height: 0;
            margin-top: 0.3mm;
            margin-bottom: 0.5mm;
        }

        .barcode svg,
        .barcode img,
        .barcode > div {
            max-width: 100% !important;
            width: 100% !important;
            height: 8mm !important;
        }

        .NoBarcode {
            font-size: 7pt;
            letter-spacing: 0.3px;
            margin: 0;
            float: left;
            width: 58%;
            text-align: left;
            overflow: hidden;
            white-space: nowrap;
            line-height: 1;
        }

        .harga {
            font-weight: bold;
            font-size: 6.5pt;
            float: right;
            width: 42%;
            text-align: right;
            overflow: hidden;
            margin-right: 0;
            white-space: nowrap;
            line-height: 1;
        }

        .clear {
            clear: both;
            height: 0;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    @php
        $cols = ($paper_size ?? 'a4') === 'a5' ? 3 : 4;
        $rows_per_page = ($paper_size ?? 'a4') === 'a5' ? 8 : 11;
        $per_page = $cols * $rows_per_page;

        $all_labels = [];
        foreach ($list as $item) {
            for ($i = 0; $i < $item->qty_print; $i++) {
                $all_labels[] = $item;
            }
        }
        $total = count($all_labels);
    @endphp

    @for ($idx = 0; $idx < $total; $idx++)
        @php
            $item = $all_labels[$idx];
            $barcode = str_pad($item->barcode, 12, '0', STR_PAD_LEFT);
            $barcode = preg_replace('/\s+/', '', $barcode);
            $namaProduk = trim((string)($item->nama_produk ?? ''));
            $namaVarian = trim((string)($item->nama_varian ?? ''));
            $namaLabel = $namaProduk;
            if ($namaVarian !== '' && strcasecmp($namaProduk, $namaVarian) !== 0) {
                $namaLabel .= ' ' . $namaVarian;
            }
        @endphp

        <div class="label">
            @if(isset($nama) && $nama == 1)
                <div class="nama">{{ $namaLabel }}</div>
            @endif

            <div class="barcode">
                {!! DNS1D::getBarcodeHTML($barcode, 'C128', 1, 30) !!}
            </div>

            <div class="NoBarcode">{{ $barcode }}</div>

            @if(isset($harga) && $harga == 1)
                <div class="harga">(Rp. {{ number_format($item->harga, 0, ',', '.') }})</div>
            @endif
            <div class="clear"></div>
        </div>

        @if (($idx + 1) % $per_page == 0 && ($idx + 1) < $total)
            <div class="clear"></div>
            <div class="page-break"></div>
        @endif
    @endfor

    <div class="clear"></div>
</body>
</html>
