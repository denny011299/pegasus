<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Stock Opname Produk</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            margin: 0;
        }

        .invoice-container {
            margin: -50px;
            padding: 40px 50px;
            box-sizing: border-box;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-title {
            font-size: 28px;
            font-weight: bold;
            color: #3a4c43;
            padding-bottom: 25px;
        }

        .header-info td {
            font-size: 12px;
            color: #333;
            padding-top: 5px;
        }

        .items-table th,
        .items-table td {
            border-top: 1px solid #3a4c43;
            padding: 8px;
            font-size: 13px;
            text-align: left;
        }

        .items-table th {
            font-weight: bold;
        }

        /* STOCK rata tengah */
        .items-table th:nth-child(4),
        .items-table td:nth-child(4),
        .items-table th:nth-child(5),
        .items-table td:nth-child(5),
        .items-table th:nth-child(6),
        .items-table td:nth-child(6) {
            text-align: center;
        }

        .thank-you {
            clear: both;
            margin-top: 50px;
            font-weight: bold;
        }

        .thank-you+p {
            font-size: 12px;
            margin-top: 6px;
            max-width: 520px;
        }

        .items-table {
            margin-top: 30px;
        }
    </style>
</head>

<body>
    <div class="invoice-container">
        <!-- HEADER -->
        <table>
            <tr>
                <td colspan="4" class="header-title">Stock Opname</td>
            </tr>
            <tr class="header-info">
                <td><strong>ID. STO</strong><br>{{ $stockOpname['sto_code'] }}</td>
                <td><strong>DATE</strong><br>{{ $stockOpname['sto_date'] }}</td>
                <td><strong>Penanggung Jawab</strong><br>{{ $staff_name['staff_name'] }}</td>
                <td><strong>Status</strong><br>{{ $status }}</td>
            </tr>
        </table>

        <!-- ITEMS -->
        <table class="items-table">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Produk</th>
                    <th>Varian</th>
                    <th>Stock Sistem</th>
                    {{-- @if (\App\Helpers\AccessHelper::hasAccess('Show Selisih Stockopname', 'view'))
                    @endif --}}
                    <th>Stock Real</th>
                    {{-- @if (\App\Helpers\AccessHelper::hasAccess('Show Selisih Stockopname', 'view'))
                    @endif --}}
                    <th>Selisih</th>
                    
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($detail as $item)
                    <tr>
                        <td>{{ empty($item['product_variant_sku']) ? '-' : $item['product_variant_sku'] }}</td>
                        <td>{{ $item['pr_name'] ?? '-' }}</td>
                        <td>{{ empty($item['product_variant_name']) ? '-' : $item['product_variant_name'] }}</td>
                        <td>{{ $item['stod_system'] ?? '-' }}</td>
                        {{-- @if (\App\Helpers\AccessHelper::hasAccess('Show Selisih Stockopname', 'view'))
                        @endif --}}
                        
                        <td>{{ $item['stod_real'] ?? '-' }}</td>

                        <td>{{ $item['stod_selisih'] ?? '-' }}</td>
                        {{-- @if (\App\Helpers\AccessHelper::hasAccess('Show Selisih Stockopname', 'view'))
                        @endif --}}
                        
                        <td>{{ empty($item['stod_notes']) ? '-' : $item['stod_notes'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- FOOTER -->
        <div class="thank-you">CATATAN</div>
        <p>
            {{ empty($stockOpname['sto_notes']) ? '-' : $stockOpname['sto_notes'] }}
        </p>
        <div class="thank-you">PERINGATAN!</div>
        <p>
            Hasil stock opname ini digunakan untuk mencocokkan data stok sistem dengan kondisi real di gudang. Mohon
            segera tindak lanjuti selisih stok bila ada.
        </p>
    </div>
</body>

</html>
