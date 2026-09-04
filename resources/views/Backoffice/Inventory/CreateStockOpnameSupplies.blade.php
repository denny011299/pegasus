<?php $page = 'view_stock_opname'; ?>
@extends('layout.mainlayout')
@section('content')
    <style>
        .table-responsive {
            display: block;
            width: 100%;
            overflow-x: auto !important;
        }

        #tb-stock-table {
            width: 100% !important;
            border-collapse: separate;
            border-spacing: 0;
        }

        #tb-stock-table thead,
        #tb-stock-table thead th,
        #tb-stock-table .thead-light th {
            background: #ffffff !important;
            color: #1e3a8a !important;
            font-size: 11px !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
            padding: 16px 18px !important;
            border-bottom: 1px solid #e2e8f0 !important;
            border-top: 0 !important;
        }

        #tb-stock-table td {
            padding: 12px 16px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            font-size: 13px;
        }

        #tb-stock-table tbody tr {
            transition: background-color 0.15s ease;
        }

        #tb-stock-table th:nth-child(1),
        #tb-stock-table td:nth-child(1) {
            width: 24% !important;
        }

        #tb-stock-table th:nth-child(2),
        #tb-stock-table td:nth-child(2) {
            width: 48% !important;
        }

        #tb-stock-table th:nth-child(3),
        #tb-stock-table td:nth-child(3) {
            width: 28% !important;
        }

        #tb-stock-table .rstock {
            flex-wrap: nowrap !important;
            display: flex !important;
            width: 100% !important;
        }

        #tb-stock-table .rstock .form-control {
            height: 42px !important;
            font-size: 14px !important;
            font-weight: 500 !important;
            border-color: #cbd5e1 !important;
            flex: 1 1 0% !important;
            min-width: 0 !important;
            border-radius: 0 !important;
        }

        #tb-stock-table .rstock .form-control:first-child {
            border-top-left-radius: 8px !important;
            border-bottom-left-radius: 8px !important;
        }

        #tb-stock-table .rstock .input-group-text {
            height: 42px !important;
            font-size: 13px !important;
            font-weight: 500 !important;
            background-color: #f8fafc !important;
            border-color: #cbd5e1 !important;
            color: #334155 !important;
            padding: 0 14px !important;
            flex: 0 0 auto !important;
            border-radius: 0 !important;
        }

        #tb-stock-table .rstock .input-group-text:last-child {
            border-top-right-radius: 8px !important;
            border-bottom-right-radius: 8px !important;
        }

        #tb-stock-table input.notes {
            height: 42px !important;
            border-radius: 8px !important;
            font-size: 13px !important;
            border-color: #cbd5e1 !important;
        }

        .invalid {
            border: 1px solid #ef4444 !important;
            background-color: #fef2f2 !important;
        }

        /* Page Header Back Button */
        .page-header .btnBack,
        .btn-back {
            background: #0f172a !important;
            border: 1px solid #1e293b !important;
            color: #fff !important;
            border-radius: 8px !important;
            font-weight: 600 !important;
            padding: 8px 18px !important;
            font-size: 13px !important;
            transition: all 0.2s ease;
            display: inline-flex !important;
            align-items: center;
            gap: 6px;
        }

        .page-header .btnBack:hover,
        .btn-back:hover {
            background: #1e293b !important;
            color: #fff !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);
        }

        /* Sticky Action / Search bar */
        .stock-opname-toolbar {
            position: sticky;
            top: 70px;
            z-index: 10;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            border-radius: 12px;
            padding: 14px 18px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        }

        .stock-opname-fab {
            position: fixed;
            right: 20px;
            bottom: 20px;
            z-index: 1040;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .stock-opname-fab-menu {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 8px;
            margin-bottom: 10px;
        }

        .stock-opname-fab-menu .btn {
            white-space: nowrap;
            border-radius: 50px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .2);
        }

        .stock-opname-fab-toggle {
            width: 44px;
            height: 44px;
            padding: 0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 14px rgba(0, 0, 0, .3);
        }
    </style>
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    Input Stok Opname
                @endslot
            @endcomponent
            <!-- /Page Header -->

            <!-- Search Filter -->
            @component('components.search-filter')
            @endcomponent
            <!-- /Search Filter -->

            <!-- Header Card: Header Stok Opname -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between" style="border-radius: 12px 12px 0 0;">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width:36px;height:36px;border-radius:10px;background:#eff6ff;border:1px solid #bfdbfe;color:#2563eb;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fe fe-clipboard" style="font-size:16px;"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold text-dark" style="font-size:15px;letter-spacing:.2px;">Header Stok Opname</h6>
                            <small class="text-muted" style="font-size:12px;">Informasi gudang, penanggung jawab, tanggal, dan status stok opname bahan mentah</small>
                        </div>
                    </div>
                    <button class="btn btn-sm btn-icon btn-light rounded-circle" type="button" data-bs-toggle="collapse" data-bs-target="#collapseHeaderOpname" aria-expanded="true" aria-controls="collapseHeaderOpname" style="width:32px;height:32px;">
                        <i class="fe fe-chevron-down"></i>
                    </button>
                </div>
                <div id="collapseHeaderOpname" class="collapse show">
                    <div class="card-body p-4">
                        <div class="row g-3">
                            @php
                                $opnameWarehouseName = trim((string) (
                                    ($warehouse_name ?? null)
                                    ?: (is_object($data ?? null) ? ($data->warehouse_name ?? null) : null)
                                    ?: (is_array($data ?? null) ? ($data['warehouse_name'] ?? null) : null)
                                    ?: ''
                                ));
                            @endphp
                            <div class="col-lg-3 col-md-6 col-12">
                                <label class="text-muted mb-2" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;">Gudang</label>
                                <input type="text" class="form-control" id="warehouse_name" value="{{ $opnameWarehouseName !== '' ? $opnameWarehouseName : '-' }}" disabled readonly
                                    style="height:42px;border-radius:8px;font-size:13px;background:#f8fafc;font-weight:600;color:#1e40af;cursor:not-allowed;">
                            </div>
                            {{-- Fitur "Clean Up Data" (2026-09-04): kembaran persis CreateStockOpname.blade.php
                                 (Produk) -- lihat komentarnya untuk alasan lengkap. --}}
                            @if (($mode ?? 1) == 1 && ($is_main_warehouse ?? false) && \App\Support\RoleAccess::can(Session::get('user'), 'Stok Opname - Bersihkan Data', 'create'))
                                <div class="col-lg-3 col-md-6 col-12">
                                    <label class="text-muted mb-2" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;">Jenis Opname</label>
                                    <select id="jenis_opname" class="form-select" style="height:42px;border-radius:8px;font-size:13px;">
                                        <option value="1" selected>Stock Opname</option>
                                        <option value="2">Clean Up Data</option>
                                    </select>
                                </div>
                            @else
                                <input type="hidden" id="jenis_opname" value="{{ ($mode ?? 1) == 2 ? (int) data_get($data ?? [], 'sto_type', 1) : 1 }}">
                            @endif
                            <div class="col-lg-3 col-md-6 col-12">
                                <label class="text-muted mb-2" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;">Nama Penanggung Jawab <span class="text-danger">*</span></label>
                                <div class="row-staff">
                                    <select name="" id="penanggung-jawab" class="form-select fill" style="height:42px;border-radius:8px;font-size:13px;"></select>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 col-12">
                                <label class="text-muted mb-2" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;">Tanggal <span class="text-danger">*</span></label>
                                <input type="date" class="form-control fill" id="tanggal" style="height:42px;border-radius:8px;font-size:13px;">
                            </div>
                            <div class="col-lg-3 col-md-6 col-12">
                                <label class="text-muted mb-2" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;">Status</label>
                                <input type="text" class="form-control fill" id="status" disabled style="height:42px;border-radius:8px;font-size:13px;background:#f8fafc;font-weight:600;color:#475569;">
                            </div>
                            <div class="col-12 mt-3">
                                <label class="text-muted mb-2" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;">Catatan</label>
                                <textarea class="form-control" placeholder="Masukkan catatan stok opname jika ada..." id="catatan" rows="2" style="border-radius:8px;font-size:13px;resize:vertical;"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

      {{-- Fitur "Clean Up Data" (2026-09-04): kembaran CreateStockOpname.blade.php (Produk). --}}
      <div id="cleanup-description" class="card border-0 shadow-sm rounded-3 mb-4 d-none">
        <div class="card-body p-4">
          <div class="d-flex align-items-start gap-3">
            <div style="width:36px;height:36px;border-radius:10px;background:#fff7ed;border:1px solid #fed7aa;color:#c2410c;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="fe fe-refresh-cw" style="font-size:16px;"></i>
            </div>
            <div>
              <h6 class="mb-1 fw-bold text-dark" style="font-size:14px;">Clean Up Data</h6>
              <p class="mb-0 text-muted" style="font-size:13px;line-height:1.6;">
                Tidak perlu menghitung stok bahan mentah secara manual. Saat disetujui, sistem akan
                menyusun ulang stok yang sudah ada di Gudang Utama ke satuan yang seharusnya tanpa
                mengubah jumlah fisik barang sama sekali -- cuma representasinya di antar satuan
                yang diperbaiki.
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Table Card (Consistent with Master Gudang .card-table) -->
      <div class="row" id="opname-count-section">
        <div class="col-sm-12">
          <!-- Toolbar (Search + Action Buttons) -->
          <div class="stock-opname-toolbar d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div style="max-width: 360px; width: 100%;">
              <div class="position-relative">
                <i class="fe fe-search position-absolute text-muted" style="left: 14px; top: 50%; transform: translateY(-50%); font-size: 15px;"></i>
                <input type="text" class="form-control ps-5" id="filter_sup_name" placeholder="Cari Nama Bahan Mentah..." style="height: 42px; border-radius: 8px; font-size: 13px; border: 1px solid #cbd5e1;">
              </div>
            </div>

            <!-- Action Buttons for Desktop -->
            <div class="d-none d-md-flex align-items-center gap-2 flex-wrap stock-opname-actions-desktop">
              @php
                $akses = collect(json_decode(Session::get('user')->role_access));
              @endphp
              @if (($akses->firstWhere('name', 'Stok Opname Bahan Mentah') && in_array('others', $akses->firstWhere('name', 'Stok Opname Bahan Mentah')->akses)) || \App\Support\RoleAccess::can(Session::get('user'), 'Stok Opname - Bersihkan Data', 'others'))
                <button class="btn btn-outline-danger save-tolak" style="display: none; height: 40px; border-radius: 8px; font-weight: 600; padding: 0 18px;">
                  <i class="fe fe-x me-1"></i> Tolak
                </button>
                <button class="btn btn-success save-terima" style="display: none; height: 40px; border-radius: 8px; font-weight: 600; padding: 0 18px; background: linear-gradient(135deg, #16a34a, #15803d); border: none; box-shadow: 0 4px 12px rgba(22,163,74,0.25);">
                  <i class="fe fe-check me-1"></i> Terima
                </button>
              @endif
              <button class="btn btn-outline-danger btn-delete-draft" style="display: none; height: 40px; border-radius: 8px; font-weight: 600; padding: 0 18px;">
                <i class="fe fe-trash-2 me-1"></i> Hapus Draft
              </button>
              <button class="btn btn-save-draft" style="height: 40px; border-radius: 8px; font-weight: 600; padding: 0 18px; background: #eff6ff; color: #1d4ed8; border: 1.5px solid #bfdbfe;">
                <i class="fe fe-file-text me-1"></i> Simpan sebagai Draft
              </button>
              <button class="btn btn-success btn-ajukan" style="display: none; height: 40px; border-radius: 8px; font-weight: 600; padding: 0 18px; background: linear-gradient(135deg, #16a34a, #15803d); border: none; box-shadow: 0 4px 12px rgba(22,163,74,0.25);">
                <i class="fe fe-send me-1"></i> Ajukan
              </button>
              <button class="btn btn-primary btn-save" style="height: 40px; border-radius: 8px; font-weight: 600; padding: 0 20px; background: linear-gradient(135deg, #1e40af, #2563eb); border: none; box-shadow: 0 4px 12px rgba(37,99,235,0.25);">
                <i class="fe fe-plus-circle me-1"></i> Tambah Stok Opname
              </button>
            </div>
          </div>
          <div class="card-table">
            <div class="card-body">

                            <!-- Table Container -->
                            <div class="table-responsive" style="overflow-x: auto;">
                                <table class="table table-hover mb-0" id="tb-stock-table">
                                    <thead class="thead-light">
                                        <tr>
                                            <th class="text-start" style="width:24%;">Nama Bahan Mentah</th>
                                            <th class="text-center" style="width:48%;">Stok Real</th>
                                            <th class="text-start" style="width:28%;">Catatan</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbStock"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Table -->

            {{-- Mobile: tombol aksi jadi FAB (floating action button) di pojok kanan bawah --}}
            <div class="stock-opname-fab d-md-none">
                <div class="collapse stock-opname-fab-menu" id="stockOpnameFabMenu">
                    @php
                        $akses = collect(json_decode(Session::get('user')->role_access));
                    @endphp
                    @if (($akses->firstWhere('name', 'Stok Opname Bahan Mentah') && in_array('others', $akses->firstWhere('name', 'Stok Opname Bahan Mentah')->akses)) || \App\Support\RoleAccess::can(Session::get('user'), 'Stok Opname - Bersihkan Data', 'others'))
                        <button class="btn btn-danger save-tolak" style="display: none">Tolak</button>
                        <button class="btn btn-success save-terima" style="display: none">Terima</button>
                    @endif
                    <button class="btn btn-outline-danger btn-delete-draft" style="display: none">Hapus Draft</button>
                    <button class="btn btn-outline-primary btn-save-draft">Simpan sebagai Draft</button>
                    <button class="btn btn-success btn-ajukan" style="display: none">Ajukan</button>
                    <button class="btn btn-primary btn-save">Tambah Stok Opname</button>
                </div>
                <button type="button" class="btn btn-primary stock-opname-fab-toggle" data-bs-toggle="collapse"
                    data-bs-target="#stockOpnameFabMenu" aria-expanded="false" aria-controls="stockOpnameFabMenu">
                    <i class="fe fe-more-vertical"></i>
                </button>
            </div>
        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection

@section('custom_js')
    <script>
        var public = "{{ asset('') }}";
        var data = @json($data);
        var mode = @json($mode);
        var sessionUser = @json(Session::get('user'));
        // Fitur "Clean Up Data" (2026-09-04): lihat catatan di CreateStockOpname.blade.php (Produk).
        var canOthersNormal = @json(\App\Support\RoleAccess::can(Session::get('user'), 'Stok Opname Bahan Mentah', 'others'));
        var canOthersCleanUp = @json(\App\Support\RoleAccess::can(Session::get('user'), 'Stok Opname - Bersihkan Data', 'others'));
    </script>
    <script src="{{asset('Custom_js/Backoffice/Inventory/CreateStockOpnameSupplies.js')}}?v=12"></script>
@endsection
