<!DOCTYPE html>
<html>
    <head>
        <title>Tanda Terima Supplier</title>
        <style>
            /* CSS Umum untuk Dompdf */
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                font-size: 10pt;
                margin: 0;
                padding: 30px;
                color: #333;
            }
            .container {
                width: 100%;
                margin: 0 auto;
            }
            
            /* HEADER - Dimodifikasi untuk Logo */
            .header-table {
                width: 100%;
                margin-bottom: 30px;
                border-collapse: collapse;
            }
            .header-table td {
                vertical-align: top;
                padding: 0;
            }
            .header-title h1 {
                color: #4a4a4a; /* Warna biru sebagai aksen */
                font-size: 24pt;
                margin: 0;
                text-transform: uppercase;
            }
            .header-logo {
                text-align: right;
            }
            .header-logo img {
                max-width: 150px; /* Atur ukuran logo */
                height: auto;
            }
            
            /* Informasi Section (Supplier, Kode) */
            .info-section {
                display: table;
                width: 100%;
                margin-bottom: 30px;
                border-bottom: 1px solid #eee;
                padding-bottom: 15px;
            }
            .info-row {
                display: table-row;
            }
            .info-label, .info-value {
                display: table-cell;
                padding: 5px 0;
            }
            .info-label {
                font-weight: bold;
                width: 35%;
            }
            .info-value {
                border-bottom: 1px dashed #ccc;
            }
            
            /* Tagihan/Tabel */
            .billing-section h3 {
                font-size: 14pt;
                color: #555;
                margin-bottom: 15px;
                border-left: 4px solid #4a4a4a;
                padding-left: 10px;
            }
            .billing-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 40px;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
            }
            .billing-table th, .billing-table td {
                padding: 12px;
                text-align: left;
            }
            .billing-table th {
                background-color: #4a4a4a;
                color: white;
                font-weight: normal;
                text-transform: uppercase;
                font-size: 10pt;
            }
            .billing-table td {
                border-bottom: 1px solid #eee;
                background-color: #fff;
            }
            
            /* Total Row */
            .billing-table .total-row td {
                border-top: 2px solid #4a4a4a;
                background-color: #f7f7f7;
                font-weight: bold;
                font-size: 11pt;
                text-align: right;
            }
            .billing-table .total-row .label-col {
                text-align: left;
            }

            /* Tanda Tangan Section */
            .signature-section {
                width: 100%;
                text-align: center;
            }
            .signature-section table {
                width: 100%;
                border-collapse: collapse;
            }
            .signature-section th, .signature-section td {
                width: 50%;
                padding-top: 10px;
                text-align: center;
                vertical-align: top;
            }
            .signature-name {
                padding-top: 60px;
                border-bottom: 1px solid #333;
                width: 80%;
                margin: 0 auto;
            }
            .signature-role {
                font-size: 9pt;
                padding-top: 5px;
            }
        </style>
    </head>
    <body>

        <div class="container">
            
            <table class="header-table">
                <tr>
                    <td class="header-title" style="width: 70%;">
                        <h1 style="margin-bottom: 10px">Tanda Terima</h1>
                    </td>
                    <td class="header-logo" style="width: 30%;">
                        {{--<img src="{{ public_path('images/logo_perusahaan.png') }}" alt="Logo Perusahaan">--}}
                    </td>
                </tr>
            </table>
            
            <div class="info-section">
                <div class="info-row">
                    <div class="info-label">Tanggal Surat Terima</div>
                    <div class="info-value">: {{ date('d F Y', strtotime($tt["tt_date"]))??null }}</div>
                </div>
               
                <div class="info-row">
                    <div class="info-label">Nama Supplier</div>
                    <div class="info-value">: {{$supplier["supplier_name"]}}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Kode Tanda Terima</div>
                    <div class="info-value">: {{$tt["tt_kode"]??null}}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Nama Bank</div>
                    <div class="info-value">: {{$supplier["supplier_bank"]}}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">No. Rekening Bank</div>
                    <div class="info-value">: {{$supplier["supplier_account_number"]}} - {{$supplier["supplier_account_name"]}}</div>
                </div>
            </div>
            
            <div class="billing-section">
                <h3>Rincian Tagihan</h3>
                <table class="billing-table">
                    <thead>
                        <tr>
                            <th style="width: 35%;">Nomor PO</th>
                            <th style="width: 65%; text-align: right;">Nominal Tagihan</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Loop data tagihan di sini --}}
                        @php
                            $total = 0;
                        @endphp
                        @foreach ($data as $item)     
                            @php
                                $total +=$item->po_total;
                            @endphp
                            <tr>
                                <td>{{$item->po_number}}</td>
                                <td style="text-align: right;">Rp {{number_format($item->po_total,0,",",".")}}</td>
                            </tr>
                        @endforeach
                        
                        {{-- End Loop --}}
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td class="label-col">Total</td>
                            <td style="text-align: right;">Rp {{number_format($total,0,",",".")}}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <div class="signature-section">
                <table>
                    <tr>
                        <td>
                            <div class="signature-role">Diserahkan oleh:</div>
                            <div class="signature-name"></div>
                            <div class="signature-names" style="margin-top: 15px">{{$tt["staff_name"]}}</div>
                            <div class="signature-role">Admin</div>
                        </td>
                        <td>
                            <div class="signature-role">Diterima oleh:</div>
                            <div class="signature-name"></div>
                             <div class="signature-names" style="margin-top: 15px">{{$tt["staffFinance_name"]??" "}}</div>
                            <div class="signature-role">Finance</div>
                        </td>
                    </tr>
                </table>
            </div>
            
        </div>

    </body>
</html>