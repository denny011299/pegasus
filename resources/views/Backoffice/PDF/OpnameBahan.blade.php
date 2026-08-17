<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Stock Opname Bahan Mentah</title>
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
        .items-table th:nth-child(2),
        .items-table td:nth-child(2),
        .items-table th:nth-child(3),
        .items-table td:nth-child(3),
        .items-table th:nth-child(4),
        .items-table td:nth-child(4) {
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
                <td><strong>ID. STO</strong><br>{{ $stockOpname['stob_code'] }}</td>
                <td><strong>DATE</strong><br>{{ $stockOpname['stob_date'] }}</td>
                <td><strong>Penanggung Jawab</strong><br>{{ $staff_name['staff_name'] }}</td>
                <td><strong>Status</strong><br>{{ $status }}</td>
            </tr>
        </table>

        <!-- ITEMS -->
        <table class="items-table">
            <thead>
                <tr>
                    <th>Bahan Mentah</th>
                    <th>Stock Sistem</th>
                    <th>Stock Real</th>
                    <th>Selisih</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $fmtQty = fn (?string $s) => preg_replace_callback(
                        '/(-?\d+)/',
                        fn($m) => number_format((int)$m[1], 0, ',', '.'),
                        (string)($s ?? '-')
                    );
                @endphp
                @foreach ($detail as $item)
                    @php
                        $hasSelisih = false;
                        if (!empty($item['stobd_selisih'])) {
                            // Cek apakah ada angka yang bukan 0 di stobd_selisih
                            // Format: "0 DOS, 2 Piece" — cari angka selain 0
                            preg_match_all('/(-?\d+)/', $item['stobd_selisih'], $matches);
                            foreach ($matches[1] as $angka) {
                                if ((int)$angka !== 0) {
                                    $hasSelisih = true;
                                    break;
                                }
                            }
                        }
                        // GitHub #53: kuning kalau diisi dan ada selisih, hijau kalau diisi dan
                        // stok real cocok dengan sistem, tanpa highlight kalau memang tidak
                        // pernah diisi stok real-nya (bukan sekadar default = stok sistem).
                        $highlight = '';
                        if (!empty($item['stobd_touched'])) {
                            $highlight = $hasSelisih ? 'background-color: #FFF9C4;' : 'background-color: #C8E6C9;';
                        }
                    @endphp
                    <tr>
                        <td>{{ $item['supplies_name'] ?? '-' }}</td>
                        <td style="{{ $highlight }}">{{ $fmtQty($item['stobd_system'] ?? null) }}</td>
                        <td style="{{ $highlight }}">{{ $fmtQty($item['stobd_real'] ?? null) }}</td>
                        <td style="{{ $highlight }}">{{ $fmtQty($item['stobd_selisih'] ?? null) }}</td>
                        <td>{{ empty($item['stobd_notes']) ? '-' : $item['stobd_notes'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- FOOTER -->
        <div class="thank-you">CATATAN</div>
        <p>
            {{ empty($stockOpname['stob_notes']) ? '-' : $stockOpname['stob_notes'] }}
        </p>
        <div class="thank-you">PERINGATAN!</div>
        <p>
            Hasil stock opname ini digunakan untuk mencocokkan data stok sistem dengan kondisi real di gudang. Mohon
            segera tindak lanjuti selisih stok bila ada.
        </p>
    </div>
    @include('Backoffice.PDF.partials.dicetak_oleh')
</body>

</html>
