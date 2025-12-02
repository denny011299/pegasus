<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Jalan</title>
    <link href="https://fonts.googleapis.com/css2?family=Arial:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            background-color: #fff;
            box-sizing: border-box;
        }

        html {}

        .container {
            width: 100%;
        }

        .header-table {
            width: 100%;
            border-bottom: 1px solid #000;
            padding-bottom: 10px;
            margin-bottom: 25px;
        }

        .header-table td {
            vertical-align: top;
            padding: 0;
        }

        .logo {
            width: 120px;
            height: 70px;
            margin-right: 15px;
            padding-top: 20px;
            display: inline-block;
            position: relative;
            vertical-align: middle;
        }

        .logo::before,
        .logo::after {
            content: '';
            width: 100%;
            background-color: #000;
            transform: translate(-50%, -50%);
        }

        .logo::before {
            width: 100%;
            transform: translate(-50%, -50%) rotate(45deg);
        }

        .logo::after {

            transform: translate(-50%, -50%) rotate(-45deg);
        }

        .company-info {
            display: inline-block;
            vertical-align: middle;
        }

        .company-name {
            font-size: 1em;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .company-address,
        .company-tel,
        .company-web {
            font-size: 0.75em;
            margin-bottom: 1px;
            line-height: 1.3;
            width: 300px;
        }

        .company-address {
            margin-top: 5px;
        }

        .header-title {
            text-align: right;
            vertical-align: bottom;
        }

        .header-title h1 {
            padding-top: 20px;
            font-size: 1.8em;
            font-weight: bold;
            margin: 0;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .info-table {
            width: 100%;
            margin-bottom: 25px;
            font-size: 0.9em;
        }

        .info-table td {
            vertical-align: top;
            padding: 0;
            line-height: 1.6;
        }

        .info-table .label {
            display: inline-block;
            width: 120px;
        }

        .item-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            font-size: 0.9em;
            border: 1px solid #000;

        }

        .item-table th,
        .item-table td {
            padding: 8px 10px;
            text-align: left;
            border-right: 1px solid #000;
        }

        .item-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
        }

        .item-table .qty-col,
        .item-table .keterangan-col {
            text-align: center;
        }

        .item-table .qty-col {
            width: 10%;
        }

        .item-table .keterangan-col {
            width: 30%;
        }

        .item-table .item-col {
            width: 60%;
        }

        .notes-and-attention {
            width: 100%;
            margin-bottom: 30px;
            font-size: 0.85em;
        }

        .notes-and-attention td {
            vertical-align: top;
        }

        .attention-section {
            border: 1px solid #000;
            padding: 10px;
        }

        .attention-section .title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .attention-section ol {
            margin: 0;
            padding-left: 20px;
            list-style-type: decimal;
        }

        .attention-section li {
            margin-bottom: 5px;
            line-height: 1.5;
        }

        .signature-section {
            width: 100%;
            text-align: center;
            margin-top: 50px;
        }

        .signature-text {
            font-style: italic;
            margin-bottom: 40px;
            font-size: 0.85em;
        }

        .signature-roles-table {
            width: 100%;
            text-align: center;
            margin-top: 50px;
        }

        .signature-roles-table td {
            padding: 0;
        }

        .signature-line {
            border-bottom: 1px solid #000;
            width: 150px;
            margin: 0 auto;
            margin-top: 50px;
        }

        .signature-label {
            font-size: 0.8em;
            font-weight: bold;
            margin-top: 5px;
        }

        .item-table .nama-produk-col {
            width: 25%;
            /* Lebar kolom nama produk */
        }

        .item-table .nama-varian-col {
            width: 10%;
            /* Lebar kolom nama varian */
        }

        .item-table .qty-col {
            width: 8%;
            /* Lebar kolom qty */
        }

        .item-table .sku-col {
            width: 12%;
            /* Lebar kolom SKU */
        }

        .item-table .harga-col {
            width: 20%;
            /* Lebar kolom harga */
        }

        .item-table .keterangan-col {
            width: 25%;
            /* Lebar kolom keterangan */
        }
    </style>
</head>

<body>
    <div class="container">
        <table class="header-table">
            <tr>
                <td>
                    <div class="logo">
                        <img src="{{ public_path('assets/indoraya_logo.png') }}" alt="Company Logo" style="width: 100%">
                    </div>
                    <div class="company-info">
                        <div class="company-name">INDORAYA GROSIR</div>
                        <div class="company-address">Jl. Raya Nginden No.1, RT.001, Nginden Jangkungan</div>
                        <div class="company-tel">Telp. 0838-5618-5437</div>
                    </div>
                </td>
                <td class="header-title">
                    <h1>SURAT PENERIMAAN BARANG</h1>
                    <h5><i>Good Receipt</i></h5>
                </td>
            </tr>
        </table>

        <table class="info-table">
            <tr>
                <td style="width: 60%;">
                    <p style="margin: 0;"><span class="label">Kepada Yth.</span></p>
                    <p style="margin: 0;"><span class="label">Supplier</span>:
                        {{ $delivery_so['supplier_nama'] ?? '-' }}</p>
                    <p style="margin: 0;"><span class="label">Nama Penerima</span>:
                        {{ $delivery_so['nama_penerima'] ?? '-' }}</p>
                </td>
                <td style="width: 40%;padding-top: 10px;">
                    <p style="margin: 0;"><span class="label">Kode Ref</span>:
                        {{ $delivery_so['good_receipt_kode'] ?? '-' }}</p>
                    <p style="margin: 0;"><span class="label">Tanggal</span>: {{ $delivery_so['tanggal'] ?? '-' }}</p>
                </td>
            </tr>
        </table>

        <table class="item-table">
            <thead>
                <tr>
                    <th class="nama-produk-col">Nama Barang</th>
                    <th class="nama-varian-col">Varian</th>
                    <th class="qty-col">Terkirim</th>
                    <th class="sku-col">SKU</th>
                    <th class="harga-col">Nominal</th>
                    <th class="harga-col">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($delivery_so_detail as $item)
                    <tr>
                        <td>{{ $item['nama_produk'] ?? '-' }}</td>
                        <td>{{ $item['nama_varian'] ?? '-' }}</td>
                        <td class="qty-col">{{ $item['terkirim'] ?? 0 }}</td>
                        <td>{{ $item['SKU'] ?? '-' }}</td>
                        <td>
                            @if (is_numeric($item['harga'] ?? null))
                                Rp {{ number_format($item['harga'], 0, ',', '.') }}
                            @else
                                <div style="text-align: center">
                                    {{ $item['harga'] ?? '-' }}
                                </div>
                            @endif
                        </td>
                        <td>
                            @if (is_numeric($item['harga'] ?? null) && is_numeric($item['jumlah'] ?? null))
                                Rp {{ number_format($item['harga'] * $item['jumlah'], 0, ',', '.') }}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @endforeach
                <tr style="font-weight: bold; background-color: #f9f9f9; border:#000 1px solid">
                    <td colspan="5">Total</td>
                    <td>
                        @php
                            $totalSubtotal = collect($delivery_so_detail)->sum(function ($item) {
                                return is_numeric($item['harga'] ?? null) && is_numeric($item['jumlah'] ?? null)
                                    ? $item['harga'] * $item['jumlah']
                                    : 0;
                            });
                        @endphp
                        <strong>Rp {{ number_format($totalSubtotal, 0, ',', '.') }}</strong>
                    </td>
                </tr>
            </tbody>
        </table>

        <table class="notes-and-attention">
            <tr>
                <td style="width: 50%;">
                    <div class="notes-section">
                        <div class="title" style="font-weight: bold">Catatan:</div>
                        <div class="text">{{ $delivery_so['deskripsi'] ?? '-' }}</div>
                    </div>
                </td>
                <td style="width: 50%;">
                    <div class="attention-section">
                        <div class="title">PERHATIAN:</div>
                        <ol>
                            <li>Surat Penerimaan ini merupakan bukti resmi penerimaan barang</li>
                            <li>Surat Penerimaan ini bukan bukti penjualan</li>
                        </ol>
                    </div>
                </td>
            </tr>
        </table>

        <div class="signature-section">
            <table class="signature-roles-table">
                <tr>
                    <td>
                        <div class="signature-line"></div>
                        <div class="signature-label">Pengirim / Supplier</div>
                    </td>
                    <td>
                        <div class="signature-line"></div>
                        <div class="signature-label">Penerima</div>
                    </td>
                    <td>
                        <div class="signature-line"></div>
                        <div class="signature-label">Petugas Gudang</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>

</html>
