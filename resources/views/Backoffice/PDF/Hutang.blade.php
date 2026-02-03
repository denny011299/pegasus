<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Hutang</title>

    <style>
        /* Setup Halaman */
        @page {
            margin: 1cm;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #334155;
            line-height: 1.5;
            font-size: 11pt;
        }

        /* ================= HEADER ================= */
        .report-header {
            width: 100%;
            border-bottom: 3px solid #e5e7eb;
            padding-bottom: 18px;
            margin-bottom: 28px;
        }

        .company-name {
            font-size: 12pt;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .report-title {
            font-size: 22pt;
            font-weight: bold;
            color: #1e293b;
            margin: 6px 0 4px 0;
        }

        .report-meta {
            font-size: 9pt;
            color: #64748b;
        }

        .total-wrapper {
            background-color: #eef2ff;
            padding: 12px 18px;
            border-radius: 10px;
            text-align: right;
        }

        .total-wrapper .label {
            font-size: 9pt;
            color: #6366f1;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .total-wrapper .amount {
            font-size: 18pt;
            font-weight: bold;
            color: #4338ca;
        }

        /* ================= FILTER INFO ================= */
        .filter-info {
            width: 100%;
            margin-bottom: 30px;
            background-color: #f8fafc;
            padding: 15px;
            border-radius: 8px;
        }

        .filter-label {
            font-size: 8pt;
            text-transform: uppercase;
            color: #94a3b8;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .filter-value {
            font-size: 10pt;
            color: #1e293b;
        }

        /* ================= TABLE ================= */
        table.main-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table.main-table th {
            background-color: #f8fafc;
            text-align: left;
            font-size: 9pt;
            text-transform: uppercase;
            color: #475569;
            padding: 12px 10px;
            border-bottom: 1px solid #e2e8f0;
        }

        table.main-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 10pt;
            vertical-align: middle;
        }

        /* ================= STATUS ================= */
        .status-badge {
            font-size: 8pt;
            padding: 4px 10px;
            background-color: #fef3c7;
            color: #92400e;
            border-radius: 10px;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <!-- HEADER -->
    <table class="report-header">
        <tr>
            <td width="65%" valign="top">
                <div class="company-name">LITMATH INDONESIA</div>
                <div class="report-title">Laporan Hutang</div>
                <div class="report-meta">
                    Periode: 01 Jan 2026 – 04 Feb 2026<br>
                    Dicetak: {{ date('d M Y H:i') }}
                </div>
            </td>
            <td width="35%" valign="middle">
                <div class="total-wrapper">
                    <div class="label">Total Hutang</div>
                    <div class="amount">Rp 335.000</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- FILTER INFO -->
    <table class="filter-info">
        <tr>
            <td width="33%">
                <div class="filter-label">Bank Account</div>
                <div class="filter-value">Semua Bank</div>
            </td>
            <td width="33%">
                <div class="filter-label">Supplier</div>
                <div class="filter-value">Pilih Supplier</div>
            </td>
            <td width="33%">
                <div class="filter-label">Periode</div>
                <div class="filter-value">01/01/2026 - 04/02/2026</div>
            </td>
        </tr>
    </table>

    <!-- TABLE DATA -->
    <table class="main-table">
        <thead>
            <tr>
                <th>Bank</th>
                <th>Tgl. Pesan</th>
                <th>Jatuh Tempo</th>
                <th>No. Faktur</th>
                <th>Pemasok</th>
                <th style="text-align: right;">Total</th>
                <th style="text-align: center;">Status</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>CH</td>
                <td>02 Feb 2026</td>
                <td style="color: #ef4444;">04 Mar 2026</td>
                <td style="color: #4f46e5; font-weight: bold;">INV0002</td>
                <td>PT. Global Kimia Sejahtera</td>
                <td style="text-align: right; font-weight: bold;">Rp 200.000</td>
                <td style="text-align: center;">
                    <span class="status-badge">Belum Terbayar</span>
                </td>
            </tr>

            <tr>
                <td>F</td>
                <td>02 Feb 2026</td>
                <td>04 Mar 2026</td>
                <td style="color: #4f46e5; font-weight: bold;">INV0001</td>
                <td>PT Rotama</td>
                <td style="text-align: right; font-weight: bold;">Rp 100.000</td>
                <td style="text-align: center;">
                    <span class="status-badge">Belum Terbayar</span>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- SIGNATURE -->
    <table style="width: 100%; margin-top: 50px;">
        <tr>
            <td width="70%"></td>
            <td style="text-align: center;">
                <p style="font-size: 10pt; color: #64748b; margin-bottom: 60px;">
                    Manager Keuangan,
                </p>
                <p style="font-weight: bold; text-decoration: underline;">
                    ( Admin Super )
                </p>
            </td>
        </tr>
    </table>

</body>
</html>