<?php $page = 'barcode'; ?>
@extends('layout.mainlayout')
@section('content')
    <style>
        .barcode-page {
            --bc-border: #e2e8f0;
            --bc-muted: #64748b;
            --bc-text: #0f172a;
            --bc-primary: #1d4ed8;
            --bc-bg: #f8fafc;
        }

        .barcode-page .barcode-shell {
            background: var(--bc-bg);
            border: 1px solid var(--bc-border);
            border-radius: 14px;
            padding: 1.1rem;
        }

        .barcode-page .barcode-card {
            background: #fff;
            border: 1px solid var(--bc-border);
            border-radius: 12px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.05);
            padding: 1rem 1rem 1.1rem;
        }

        .barcode-page .barcode-head-title {
            font-size: 1.22rem;
            font-weight: 700;
            color: var(--bc-text);
            margin: 0;
        }

        .barcode-page .barcode-head-sub {
            color: var(--bc-muted);
            font-size: 0.82rem;
            margin: 0.2rem 0 0 0;
        }

        .barcode-page .barcode-form-label {
            font-size: 0.74rem;
            font-weight: 600;
            color: #475569;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            margin-bottom: 0.4rem;
        }

        .barcode-page .barcode-search-wrap {
            position: relative;
        }

        .barcode-page #search {
            padding-left: 2rem;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            height: 42px;
        }

        .barcode-page .barcode-search-icon {
            position: absolute;
            top: 39px;
            left: 12px;
            color: #94a3b8;
            pointer-events: none;
            font-size: 0.9rem;
        }

        .barcode-page .resultBox {
            position: absolute;
            left: 0;
            right: 0;
            top: 100%;
            margin-top: 6px;
            z-index: 20;
            background: #fff;
            border: 1px solid var(--bc-border);
            border-radius: 10px;
            box-shadow: 0 14px 32px rgba(15, 23, 42, 0.12);
            max-height: 280px;
            overflow: auto;
        }

        .barcode-page .barcode-result-item:hover {
            background: #f8fafc;
        }

        .barcode-page .barcode-table-wrap {
            border: 1px solid var(--bc-border);
            border-radius: 10px;
            overflow: hidden;
        }

        .barcode-page .barcode-table-wrap thead th {
            background: #f1f5f9;
            color: #475569;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            font-weight: 600;
            border-bottom-color: var(--bc-border);
            padding: 0.62rem 0.7rem;
        }

        .barcode-page .barcode-table-wrap td {
            color: #334155;
            font-size: 0.84rem;
            vertical-align: middle;
            padding: 0.62rem 0.7rem;
            border-bottom-color: #eef2f7;
        }

        .barcode-page .barcode-table-wrap tr:last-child td {
            border-bottom: none;
        }

        .barcode-page .barcode-qty {
            min-width: 74px;
        }

        .barcode-page .barcode-switch-group {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
        }

        .barcode-page .barcode-switch {
            min-width: 180px;
            padding: 0.55rem 0.7rem;
            border: 1px solid var(--bc-border);
            border-radius: 10px;
            background: #fff;
        }

        .barcode-page .barcode-switch .search-toggle-list p {
            font-size: 0.79rem;
            margin: 0;
            color: #334155;
            font-weight: 600;
        }

        .barcode-page .barcode-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.8rem;
            margin-top: 1rem;
        }

        .barcode-page .barcode-hint {
            font-size: 0.78rem;
            color: var(--bc-muted);
            margin: 0;
        }

        .barcode-page .btn-print-barcode {
            border-radius: 10px;
            padding: 0.55rem 1.05rem;
            font-weight: 600;
        }
    </style>
    <div class="page-wrapper notes-page-wrapper">
        <div class="content barcode-page">
            <div class="page-header">
                <div class="add-item d-flex">
                    <div class="page-title">
                        <h4 class="barcode-head-title">Cetak Barcode</h4>
                        <h6 class="barcode-head-sub">Buat label barcode produk dan cetak ke PDF</h6>
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    <ul class="table-top-head">
                        <li>
                            <a data-bs-toggle="tooltip" data-bs-placement="top" title="Ciutkan" id="collapse-header"><i
                                    data-feather="chevron-up" class="feather-chevron-up"></i></a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="barcode-shell">
                <div class="barcode-card">
                    <div class="row g-3 align-items-end">
                        <div class="col-lg-7 col-md-9">
                            <div class="barcode-search-wrap">
                                <label class="barcode-form-label" for="search">Cari produk / SKU / barcode</label>
                                <input type="text" class="form-control" placeholder="Contoh: Radiator, SKU123, 899..." id="search">
                                <div class="barcode-search-icon"><i class="fas fa-search"></i></div>
                                <div class="resultBox"></div>
                            </div>
                        </div>
                        <div class="col-lg-5 col-md-3 text-md-end">
                            <div class="spinner-border text-primary mt-2 mt-md-0" role="status" id="loading" style="display: none; width:1.4rem;height:1.4rem;">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>

                    <div class="barcode-table-wrap table-responsive mt-3">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th>SKU</th>
                                        <th>Barcode</th>
                                        <th>Jml</th>
                                        <th class="text-center no-sort">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="tbProduct">
                                </tbody>
                            </table>
                    </div>
                    <div class="barcode-actions">
                        <div class="barcode-switch-group">
                            <div class="barcode-switch">
                                <div class="search-toggle-list d-flex justify-content-between align-items-center">
                                    <p>Tampilkan Nama Produk</p>
                                    <div class="input-blocks m-0">
                                        <div class="status-toggle modal-status d-flex justify-content-between align-items-center">
                                            <input type="checkbox" id="nama" class="check" checked>
                                            <label for="nama" class="checktoggle mb-0"> </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="barcode-switch">
                                <div class="search-toggle-list d-flex justify-content-between align-items-center">
                                    <p>Tampilkan Harga</p>
                                    <div class="input-blocks m-0">
                                        <div class="status-toggle modal-status d-flex justify-content-between align-items-center">
                                            <input type="checkbox" id="harga" class="check" checked>
                                            <label for="harga" class="checktoggle mb-0"> </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <a href="javascript:void(0);" class="btn btn-primary btn-print-barcode">
                            <span><i class="fas fa-print me-2"></i></span>
                            Buat Barcode PDF
                        </a>
                    </div>
                    <div class="mt-2">
                        <p class="barcode-hint">Tip: pilih item dari hasil pencarian, atur jumlah cetak di kolom Jml, lalu klik <strong>Buat Barcode PDF</strong>.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('custom_js')
    <script>
        var public = "{{ asset('') }}";
    </script>
    <script src="{{ asset('/Custom_js/Backoffice/Product/Barcode.js') }}?v={{ time() }}"></script>
@endsection
