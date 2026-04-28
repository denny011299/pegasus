<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Label Barcode (2 per Baris - Final Rapi)</title>
    <style>
        /* * PENGATURAN KERTAS DOMPDF/PRINTER */
        @page {
            margin: 0;
            /* Lebar 70mm, Tinggi 17mm, sudah sesuai */
            size: 70mm 17mm; 
        }
        
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        .label {
            width: 33mm;
            height: 10mm;
            text-align: center;
            font-size: 3pt;
            box-sizing: border-box;
            /* Padding atsas 0.5mm, sisa 1mm */ 
            margin-top:0.25cm;
            margin-left:0.1cm;
            float: left; 
        }
        
        /* Gaya Konten */
        .nama {
            font-weight: bold;
            /* Font lebih kecil agar muat */
            font-size: 5pt; 
            line-height: 1.05; /* Sedikit longgar supaya 2 baris tetap terbaca */
            margin-bottom: 0.2mm; /* Margin lebih kecil */
            text-align: left;
            max-width: 100%; 
            overflow: hidden;
            white-space: normal;
            word-break: break-word;
            max-height: 2.1em; /* Maksimal 2 baris */
        }
        
        .barcode {
            margin: 0;
            width: 100%;
            height: 4.8mm;
            overflow: hidden;
            text-align: left;
            line-height: 0;
            margin-top: 0.1mm;
            margin-bottom: 0.2mm;
        }

        .barcode svg,
        .barcode img,
        .barcode > div {
            max-width: 100% !important;
            width: 100% !important;
            height: 4.8mm !important;
        }
        
        .NoBarcode {
            font-size: 5.5pt;
            letter-spacing: 0.2px;
            margin: 0;
            float: left;
            width: 60%;
            text-align: left;
            overflow: hidden;
            white-space: nowrap;
            line-height: 1;
        }
        
        .harga {
            font-weight: bold;
            font-size: 5pt;
            float: right; 
            width: 40%;
            text-align: right;
            overflow: hidden;
            margin-right: 0;
            white-space: nowrap;
            line-height: 1;
        }
        
        .clear{
            clear: both;
            height: 0; 
        }

        .page-break {
            page-break-after: always;
        }
        .spacer-top {
            height: 0.0mm; /* Jarak yang diinginkan */
            width: 100%;
            display: block;
        }
        
    </style>
</head>
<body>
    @php
        $label_count = 0; 
    @endphp

    @foreach ($list as $item)
        @for ($i = 0; $i < $item->qty_print; $i++)
            @php
                $barcode = str_pad($item->barcode, 12, '0', STR_PAD_LEFT); 
                $barcode = preg_replace('/\s+/', '', $barcode); // Hapus whitespace
                $namaProduk = trim((string)($item->nama_produk ?? ''));
                $namaVarian = trim((string)($item->nama_varian ?? ''));
                $namaLabel = $namaProduk;
                if ($namaVarian !== '' && strcasecmp($namaProduk, $namaVarian) !== 0) {
                    $namaLabel .= ' ' . $namaVarian;
                }
                // Tentukan style margin kanan untuk label ini
                $margin_style = ($label_count % 2 == 0) ? 'margin-right: 4mm;' : 'margin-left: -1mm;';
            @endphp
            
            <div class="label" style="{{ $margin_style }}">
                
                @if(isset($nama) && $nama==1)
                    <div class="nama">{{ $namaLabel }}</div>
                @endif

                <div class="barcode">
                    {{-- DNS1D::getBarcodeHTML($barcode, 'C128', lebar=0.8, tinggi=20) --}}
                    {!! DNS1D::getBarcodeHTML($barcode, 'C128', 0.68, 20) !!} 
                </div>

                <div class="NoBarcode">{{$barcode}}</div>

                @if(isset($harga) && $harga==1)
                    <div class="harga">(Rp. {{number_format($item->harga,0,',','.')}})</div>
                @endif
                <div class="clear"></div>
            </div>

            @php
                $label_count++; 
            @endphp
            
            @if ($label_count % 2 == 0)
                <div class="clear"></div>
                        
                <div class="page-break"></div>
                <div class="spacer-top"></div>
            @endif
        @endfor 
    @endforeach
    
    @if ($label_count % 2 != 0)
        <div class="clear"></div>
    @endif
</body>
</html>