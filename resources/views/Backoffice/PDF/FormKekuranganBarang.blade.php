<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Form Kekurangan Barang &mdash; {{ $doc_number }}</title>
    <style>
        @page {
            size: 148mm 210mm;
            margin: 8mm 9mm 7mm 9mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 8.5px;
            color: #1e293b;
            line-height: 1.3;
            margin: 0;
            padding: 0;
            background: #ffffff;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Header block */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }
        .header-table td {
            vertical-align: middle;
            padding: 0;
        }
        .company-logo {
            max-height: 38px;
            max-width: 90px;
            display: block;
            filter: grayscale(100%);
            -webkit-filter: grayscale(100%);
        }
        .company-name {
            font-size: 12px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            margin-bottom: 1px;
        }
        .company-address, .company-contact {
            font-size: 7.5px;
            color: #475569;
            line-height: 1.25;
        }
        .header-right {
            text-align: right;
            vertical-align: middle;
        }
        .doc-code {
            font-size: 7.5px;
            font-weight: 700;
            color: #64748b;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .doc-title {
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.3px;
            margin: 0 0 2px 0;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .doc-no {
            font-size: 9.5px;
            font-weight: 600;
            color: #475569;
        }
        .doc-no span {
            color: #0f172a;
            font-weight: 800;
            letter-spacing: 0.3px;
        }

        /* Divider */
        .header-divider-thick {
            border-top: 2px solid #0f172a;
            margin-top: 6px;
            margin-bottom: 1px;
        }
        .header-divider-thin {
            border-top: 0.5px solid #cbd5e1;
            margin-bottom: 8px;
        }

        /* Meta Card */
        .meta-card {
            width: 100%;
            border-collapse: collapse;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            margin-bottom: 7px;
        }
        .meta-card td.meta-col {
            width: 50%;
            padding: 5px 8px;
            vertical-align: top;
        }
        .meta-list {
            width: 100%;
            border-collapse: collapse;
        }
        .meta-list td {
            padding: 1.5px 0;
            font-size: 8px;
            vertical-align: top;
        }
        .meta-label {
            width: 82px;
            color: #64748b;
            font-weight: 600;
            white-space: nowrap;
        }
        .meta-separator {
            width: 8px;
            color: #64748b;
            text-align: center;
        }
        .meta-value {
            color: #0f172a;
            font-weight: 600;
        }
        .meta-value.highlight {
            color: #0f172a;
            font-weight: 800;
        }
        .meta-value.mono {
            font-family: 'Courier New', Courier, monospace;
            font-size: 7.5px;
        }

        /* Notice banner */
        .notice-banner {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-left: 3px solid #0f172a;
            padding: 4px 7px;
            margin-bottom: 7px;
            border-radius: 3px;
            font-size: 7.5px;
            color: #334155;
            line-height: 1.3;
        }
        .notice-title {
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            margin-right: 3px;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 7px;
        }
        .items-table thead {
            display: table-header-group;
        }
        .items-table tr {
            page-break-inside: avoid;
        }
        .items-table th {
            background-color: #1e293b;
            color: #ffffff;
            font-size: 7.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 5px 4px;
            border: 1px solid #1e293b;
            text-align: left;
            white-space: nowrap;
        }
        .items-table th.col-no {
            width: 20px;
            text-align: center;
        }
        .items-table th.col-sku {
            width: 72px;
        }
        .items-table th.col-unit {
            width: 38px;
            text-align: center;
        }
        .items-table th.col-num {
            width: 44px;
            text-align: center;
        }
        .items-table th.col-shortage {
            width: 52px;
            text-align: center;
            background-color: #0f172a;
            border-color: #0f172a;
        }
        .items-table td {
            padding: 4px 4px;
            font-size: 8px;
            border: 1px solid #cbd5e1;
            color: #1e293b;
            vertical-align: middle;
        }
        .items-table tbody tr.row-even {
            background-color: #ffffff;
        }
        .items-table tbody tr.row-odd {
            background-color: #f8fafc;
        }
        .items-table td.col-no {
            text-align: center;
            color: #64748b;
            font-weight: 600;
        }
        .items-table td.col-sku {
            font-family: 'Courier New', Courier, monospace;
            font-weight: 700;
            color: #0f172a;
            font-size: 7.5px;
            white-space: nowrap;
        }
        .items-table td.col-unit {
            text-align: center;
            color: #475569;
        }
        .items-table td.col-num {
            text-align: center;
            font-weight: 600;
        }
        .items-table td.col-shortage {
            text-align: center;
            color: #000000;
            font-weight: 800;
        }

        /* Footer totals */
        .items-table tfoot td {
            background-color: #f1f5f9;
            font-weight: 800;
            font-size: 8px;
            border-top: 1.5px solid #0f172a;
            border-bottom: 1.5px solid #0f172a;
            padding: 4.5px 4px;
        }
        .items-table tfoot td.total-label {
            text-align: right;
            padding-right: 6px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #0f172a;
        }
        .items-table tfoot td.total-shortage {
            text-align: center;
            color: #000000;
            font-weight: 800;
        }

        /* Notes */
        .notes-box {
            background-color: #ffffff;
            border: 1px dashed #cbd5e1;
            border-radius: 3px;
            padding: 4px 6px;
            margin-bottom: 7px;
        }
        .notes-box-title {
            font-size: 7.5px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            margin-bottom: 1px;
        }
        .notes-box-content {
            font-size: 8px;
            color: #1e293b;
        }

        /* Signature — langsung di bawah tabel (bukan fixed ke bawah halaman) */
        .sign-container {
            width: 100%;
            margin-top: 10px;
            page-break-inside: avoid;
        }
        .sign-date-row {
            text-align: right;
            font-size: 8px;
            color: #475569;
            margin-bottom: 8px;
            padding-bottom: 2px;
        }
        .sign-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .sign-table td {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            padding: 0 4px;
        }
        .sign-role {
            font-weight: 800;
            font-size: 8.5px;
            color: #0f172a;
        }
        .sign-subrole {
            font-size: 7px;
            color: #64748b;
            margin-top: 1px;
        }
        .sign-space {
            height: 36px;
        }
        .sign-line {
            border-top: 1px solid #0f172a;
            width: 85%;
            margin: 0 auto;
        }
        .sign-name {
            font-size: 8px;
            font-weight: 700;
            color: #0f172a;
            margin-top: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Print metadata footer */
        .doc-footer {
            margin-top: 10px;
            border-top: 1px dashed #cbd5e1;
            padding-top: 3px;
            width: 100%;
            border-collapse: collapse;
        }
        .doc-footer td {
            font-size: 7px;
            color: #64748b;
            vertical-align: middle;
            padding: 0;
        }
        .doc-footer .footer-left {
            text-align: left;
        }
        .doc-footer .footer-right {
            text-align: right;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <!-- Company & Document Header -->
    <table class="header-table">
        <tr>
            <td style="width: 32px; vertical-align: middle; text-align: left; padding: 0;">
                @if (!empty($logo_base64))
                    <img src="{{ $logo_base64 }}" class="company-logo" alt="Logo">
                @endif
            </td>
            <td style="vertical-align: middle; padding-left: 6px; text-align: left;">
                <div class="company-name">{{ $company_name ?? 'PEGASUS HIKARI INDONESIA' }}</div>
                <div class="company-address">{{ $company_address ?? 'Pergudangan Meiko Abadi 2, Blok A8 - 9, Jl. Industri, Buduran, Sidoarjo' }}</div>
                <div class="company-contact">{{ $company_contact ?? 'Sidoarjo, Jawa Timur • Sistem ERP Pegasus' }}</div>
            </td>
            <td class="header-right" style="width: 220px; vertical-align: middle;">
                <div class="doc-code">FORM-OPS-BG</div>
                <div class="doc-title">FORM KEKURANGAN BARANG</div>
                <div class="doc-no">No. Dokumen: <span>{{ $doc_number }}</span></div>
            </td>
        </tr>
    </table>

    <!-- Header Divider Lines -->
    <div class="header-divider-thick"></div>
    <div class="header-divider-thin"></div>

    <!-- Metadata Card -->
    <table class="meta-card">
        <tr>
            <td class="meta-col" style="border-right: 1px dashed #cbd5e1;">
                <table class="meta-list">
                    <tr>
                        <td class="meta-label">No. Dokumen</td>
                        <td class="meta-separator">:</td>
                        <td class="meta-value highlight">{{ $doc_number }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Tanggal Dokumen</td>
                        <td class="meta-separator">:</td>
                        <td class="meta-value">{{ $doc_date }} @if(!empty($doc_time)) &bull; {{ $doc_time }} WIB @endif</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Gudang Pengeluaran</td>
                        <td class="meta-separator">:</td>
                        <td class="meta-value">{{ $warehouse_name ?? 'Gudang Pusat / Utama' }}</td>
                    </tr>
                </table>
            </td>
            <td class="meta-col">
                <table class="meta-list">
                    <tr>
                        <td class="meta-label">No. Pengiriman (SO)</td>
                        <td class="meta-separator">:</td>
                        <td class="meta-value highlight">{{ $so_number ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Tanggal Kirim</td>
                        <td class="meta-separator">:</td>
                        <td class="meta-value">{{ $so_date ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Armada / Pengemudi</td>
                        <td class="meta-separator">:</td>
                        <td class="meta-value">
                            {{ $armada_name ?? '—' }}
                            @if (!empty($armada_code))
                                <span style="font-weight: normal; color: #64748b;">({{ $armada_code }})</span>
                            @endif
                        </td>
                    </tr>
                    @if (!empty($ref_shipment_id) && $ref_shipment_id !== '—')
                    <tr>
                        <td class="meta-label">Ref. Pengiriman ID</td>
                        <td class="meta-separator">:</td>
                        <td class="meta-value mono">{{ $ref_shipment_id }}</td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <!-- Official Notice Callout -->
    <div class="notice-banner">
        <span class="notice-title">Perhatian:</span>
        Dokumen bukti pencatatan selisih fisik barang (stok kurang) saat penyiapan armada. Item di bawah ini <strong>belum terangkut</strong> dan akan dijadwalkan untuk pemenuhan susulan atau penyesuaian pesanan.
    </div>

    <!-- Items Shortage Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th class="col-no">No</th>
                <th class="col-sku">SKU</th>
                <th>Nama Produk / Barang</th>
                <th class="col-unit">Satuan</th>
                <th class="col-num">Diminta</th>
                <th class="col-num">Tersedia</th>
                <th class="col-shortage">Kurang</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $i => $item)
                <tr class="{{ $i % 2 === 0 ? 'row-even' : 'row-odd' }}">
                    <td class="col-no">{{ $i + 1 }}</td>
                    <td class="col-sku">{{ $item['sku'] }}</td>
                    <td><strong>{{ $item['nama'] }}</strong></td>
                    <td class="col-unit">{{ $item['unit'] }}</td>
                    <td class="col-num">{{ number_format($item['requested'], 0, ',', '.') }}</td>
                    <td class="col-num">{{ number_format($item['available'], 0, ',', '.') }}</td>
                    <td class="col-shortage">{{ number_format($item['shortage'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 12px; color: #64748b;">
                        Tidak ada item kekurangan yang tercatat pada dokumen ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if (count($items) > 0)
            <tfoot>
                <tr>
                    <td colspan="4" class="total-label">
                        Total ({{ count($items) }} Item Produk)
                    </td>
                    <td class="col-num">{{ number_format($total_requested ?? 0, 0, ',', '.') }}</td>
                    <td class="col-num">{{ number_format($total_available ?? 0, 0, ',', '.') }}</td>
                    <td class="col-shortage total-shortage">{{ number_format($total_shortage ?? 0, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <!-- Notes if available -->
    @if (!empty($notes))
        <div class="notes-box">
            <div class="notes-box-title">Catatan Khusus:</div>
            <div class="notes-box-content">{{ $notes }}</div>
        </div>
    @endif

    <!-- Signature — langsung setelah tabel -->
    <div class="sign-container">
        <div class="sign-date-row">
            {{ $company_city ?? 'Sidoarjo' }}, {{ $doc_date }}
        </div>
        <table class="sign-table">
            <tr>
                <td>
                    <div class="sign-role">Dibuat Oleh,</div>
                    <div class="sign-subrole">Admin Operasional / PPIC</div>
                    <div class="sign-space"></div>
                    <div class="sign-line"></div>
                    <div class="sign-name">( {{ $printed_by ?? '................................' }} )</div>
                </td>
                <td>
                    <div class="sign-role">Diketahui Oleh,</div>
                    <div class="sign-subrole">Kepala / Staf Gudang</div>
                    <div class="sign-space"></div>
                    <div class="sign-line"></div>
                    <div class="sign-name">( {{ !empty($kepala_gudang_name) ? $kepala_gudang_name : '................................' }} )</div>
                </td>
                <td>
                    <div class="sign-role">Diterima Oleh,</div>
                    <div class="sign-subrole">Pengemudi / Sopir Armada</div>
                    <div class="sign-space"></div>
                    <div class="sign-line"></div>
                    <div class="sign-name">( {{ !empty($armada_pic) ? $armada_pic : '................................' }} )</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="doc-footer">
        <tr>
            <td class="footer-left">
                Dicetak oleh: {{ $printed_by ?? '-' }} • {{ $printed_at ?? '' }} • Sistem ERP Pegasus
            </td>
            <td class="footer-right">
                FORM-OPS-BG • Dokumen Sah {{ $company_name ?? 'Pegasus' }}
            </td>
        </tr>
    </table>
</body>
</html>
