<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice</title>
    <!-- Hapus link Google Fonts karena Dompdf tidak akan menggunakannya -->
    <!-- <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700&family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet"> -->

    <style>
        body {
            /* Pastikan font-family utama adalah Arial atau font generik lainnya */
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #fff;
            line-height: 1.5;
            color: #333;
        }

        .invoice-container {
            width: 100%;
            margin: 0;
            background-color: #fff;
            box-shadow: none;
            /* Hapus box-shadow untuk versi PDF */
            border-radius: 0;
        }

        /* Invoice Header (Berbasis Tabel) */
        .header-table {
            width: 100%;
            margin-bottom: 45px;
        }

        .header-table td {
            vertical-align: top;
        }

        .header-table .header-left {
            width: 50%;
        }

        .header-table .header-right {
            width: 50%;
            text-align: right;
        }

        .header-left h1 {
            font-size: 3em;
            margin: 0;
            font-weight: 200;
            color: #222;
            letter-spacing: -1px;
            font-family: Arial, sans-serif;
            /* Pastikan ini juga Arial */
        }

        .header-left p {
            margin: 0;
            color: #888;
            font-size: 0.8em;
            font-weight: 400;
            margin-top: -5px;
        }

        .logo-name {
            display: inline-block;
            text-align: right;
        }

        .logo-name img {
            width: 200px;
            vertical-align: middle;
            margin-left: 10px;
        }

        .logo-name h2 {
            margin: 0;
            font-size: 1.4em;
            font-weight: 700;
            color: #222;
        }

        .logo-name p {
            margin: 0;
            font-size: 0.7em;
            color: #afafaf;
        }

        /* Address Section (Berbasis Tabel) */
        .address-table {
            width: 100%;
            margin-bottom: 35px;
            line-height: 1.4;
        }

        .address-table td {
            vertical-align: top;
            width: 50%;
        }

        .address-table h3 {
            margin: 0 0 8px 0;
            font-weight: 600;
            color: #333;
            font-size: 1.05em;
        }

        .address-table p {
            margin: 0;
            color: #555;
            font-size: 0.85em;
        }

        .address-table .from-address {
            text-align: right;
        }

        /* Item Table */
        .item-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 35px;
        }

        .item-table th,
        .item-table td {
            text-align: left;
            padding: 15px 12px;
            vertical-align: top;
            border-bottom: 1px solid #e0e0e0;
            /* Border untuk setiap baris */
        }

        .item-table th {
            font-weight: 600;
            color: #555;
            font-size: 0.8em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e5e5e5;
        }

        .item-table td {
            color: #444;
            font-size: 0.85em;
        }

        .item-table .item-name {
            display: block;
            font-weight: 600;
            margin-bottom: 4px;
            color: #333;
        }

        .item-table .item-desc {
            display: block;
            font-size: 0.75em;
            color: #999;
            line-height: 1.2;
        }

        /* Penyesuaian lebar kolom tabel */
        .item-table th:nth-child(1),
        .item-table td:nth-child(1) {
            width: 45%;
        }

        .item-table th:nth-child(2),
        .item-table td:nth-child(2) {
            width: 20%;
            text-align: right;
        }

        .item-table th:nth-child(3),
        .item-table td:nth-child(3) {
            width: 10%;
            text-align: right;
        }

        .item-table th:nth-child(4),
        .item-table td:nth-child(4) {
            width: 25%;
            text-align: right;
        }

        .item-table .total-price {
            text-align: right;
        }

        .item-table .unit-price {
            text-align: right;
        }

        .item-table .quantity {
            text-align: right;
        }

        /* Summary & Payment (Berbasis Tabel) */
        .summary-and-payment-table {
            width: 100%;
            margin-bottom: 50px;
        }

        .summary-and-payment-table td {
            vertical-align: top;
        }

        .summary-and-payment-table .payment-td {
            width: 50%;
            padding-right: 20px;
        }

        .summary-and-payment-table .summary-td {
            width: 50%;
        }

        .summary-details {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-details td {
            padding: 3px 0;
            font-size: 0.9em;
            color: #555;
        }

        .summary-details .label-col {
            text-align: left;
        }

        .summary-details .value-col {
            text-align: right;
            font-weight: 600;
            color: #333;
        }

        .summary-details .total-due-row td {
            font-size: 1.0em;
            font-weight: 500;
            padding-top: 10px;
            border-top: 1px solid lightgray;
        }

        .summary-details .total-due-row .label-col {
            text-transform: uppercase;
            font-size: 0.85em;
            letter-spacing: 0.5px;
            font-weight: 500;
        }

        .summary-details .discount-row td {
            color: #d9534f;
            font-weight: 600;
            font-size: 0.9em;
        }

        /* Payment Method Block */
        .payment-method-block h3 {
            margin: 0 0 8px 0;
            font-weight: 600;
            font-size: 1em;
            color: #333;
            text-transform: uppercase;
        }

        .payment-method-block p {
            margin: 0 0 5px 0;
            color: #888;
            font-size: 0.8em;
            line-height: 1.3;
        }

        /* Thank You Section (Berbasis Tabel) */
        .thank-you-table {
            width: 100%;
            margin-top: 40px;
            border-top: 1px solid #e0e0e0;
            padding-top: 30px;
            text-align: center;
        }

        .thank-you-table td {
            text-align: center;
        }

        .thank-you-table h2 {
            font-size: 1.8em;
            font-weight: 700;
            margin: 0 0 15px 0;
            color: #3f5f54;
        }

        .thank-you-table p {
            font-size: 0.9em;
            margin: 0 auto;
            max-width: 80%;
            line-height: 1.4;
            color: #3f5f54;
        }
    </style>
</head>

<body>
    <div class="invoice-container">
        <table class="header-table">
            <tr>
                <td class="header-left">
                    <h1>INVOICE</h1>
                    <p><b>{{ $invoice['invoice_no'] }}</b></p>
                    <p>{{ \Carbon\Carbon::parse($invoice['tanggal'])->format('d F Y') }}</p>
                </td>
                <td class="header-right">
                    <div class="logo-name">
                        <img src="{{ public_path('assets/indoraya_logo.png') }}" alt="Company Logo">
                    </div>
                </td>
            </tr>
        </table>

        <table class="address-table">
            <tr>
                <td class="to-address">
                    <p>Kepada:</p>
                    <h3>{{ $customer['nama'] }}</h3>
                    <p style="margin-top:-5px">{{ $customer['alamat'] }}</p>
                    <p>{{ $customer['telepon'] }}</p>
                </td>
                <td class="from-address">
                    <p>Alamat Toko</p>
                    <p>Jl. Raya Nginden No.1, RT.001, Nginden Jangkungan</p>
                    <p>Telp. 0838-5618-5437</p>
                </td>
            </tr>
        </table>

        <table class="item-table">
            <thead>
                <tr>
                    <th>ITEM DESCRIPTION</th>
                    <th class="unit-price">UNIT PRICE</th>
                    <th class="quantity">QNT</th>
                    <th class="total-price">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $subtotal = 0;
                @endphp
                @foreach ($sales_order as $item)
                    @php
                        $subtotal += $item['jumlah'] * $item['harga'];
                    @endphp
                    <tr>
                        <td>
                            <span class="item-name">{{ $item['nama_produk'] }}</span>
                            <span class="item-desc">{{ $item['nama_varian'] }} - {{ $item['SKU'] }}</span>
                        </td>
                        <td>Rp {{ number_format($item['harga'], 0, ',', '.') }}</td>
                        <td>{{ $item['jumlah'] }}</td>
                        <td>Rp {{ number_format($item['jumlah'] * $item['harga'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="summary-and-payment-table">
            <tr>
                <td class="payment-td">
                    <div class="payment-method-block">
                        <h3>Metode Pembayaran</h3>
                        <p>Transfer BCA: 1928373645</p>
                        <p>Menerima Pembayaran Tunai,Non-Tunai,QRIS</p>
                    </div>
                </td>
                <td class="summary-td">
                    <table class="summary-details">
                        <tr>
                            <td class="label-col">Sub Total</td>
                            <td class="value-col">Rp{{ number_format($subtotal, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="label-col">PPN</td>
                            <td class="value-col">Rp {{ number_format($so_header['ppn'], 0, ',', '.') }}</td>
                        </tr>
                        <tr class="discount-row">
                            <td class="label-col">Potongan</td>
                            <td class="value-col">Rp {{ number_format($so_header['diskon'], 0, ',', '.') }}</td>
                        </tr>
                        <tr class="discount-row">
                            <td class="label-col">Biaya Pengiriman</td>
                            <td class="value-col">Rp {{ number_format($so_header['biaya_pengiriman'], 0, ',', '.') }}
                            </td>
                        </tr>
                        <tr class="total-due-row">
                            <td class="label-col">Total Pembelian</td>
                            <td class="value-col">Rp {{ number_format($so_header['total_keseluruhan'], 0, ',', '.') }}
                            </td>
                        </tr>
                        <tr class="total-due-row">
                            <td class="label-col">Tagihan Invoice Ini</td>
                            <td class="value-col">Rp {{ number_format($invoice['nominal'], 0, ',', '.') }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="thank-you-table">
            <tr>
                <td>
                    <h2>TERIMA KASIH!</h2>
                    <p>Hubungi kami jika Anda memiliki pertanyaan (0812-3456-7890)</p>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
