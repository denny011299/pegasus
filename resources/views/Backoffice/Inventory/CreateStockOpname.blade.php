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
      table-layout: fixed;
      border-collapse: separate;
      border-spacing: 0;
    }

    #tb-stock-wrap {
      position: relative;
    }

    #tb-stock-wrap.is-loading .stock-opname-table-loading {
      display: flex !important;
    }

    #tb-stock-wrap.is-loading #tb-stock-table tbody {
      opacity: 0.45;
      pointer-events: none;
    }

    .stock-opname-table-loading {
      display: none;
      position: absolute;
      inset: 0;
      z-index: 20;
      align-items: center;
      justify-content: center;
      background: rgba(255, 255, 255, 0.72);
      border-radius: 8px;
    }

    .stock-opname-table-loading > div {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 10px 16px;
      border-radius: 10px;
      background: #fff;
      border: 1px solid #e2e8f0;
      box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
      color: #1e293b;
      font-weight: 600;
      font-size: 14px;
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

    #tb-stock-table tbody tr:hover {
      background-color: #f8fafc;
    }

    #tb-stock-table th:nth-child(1),
    #tb-stock-table td:nth-child(1) {
      width: 12% !important;
      white-space: nowrap;
    }

    #tb-stock-table th:nth-child(2),
    #tb-stock-table td:nth-child(2) {
      width: 16% !important;
      max-width: 180px;
      word-break: break-word;
      white-space: normal;
    }

    #tb-stock-table th:nth-child(3),
    #tb-stock-table td:nth-child(3) {
      width: 52% !important;
    }

    #tb-stock-table th:nth-child(4),
    #tb-stock-table td:nth-child(4) {
      width: 20% !important;
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
              <small class="text-muted" style="font-size:12px;">Informasi gudang, penanggung jawab, tanggal, dan status stok opname</small>
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
                    ?: (is_array($data ?? null) ? ($data['warehouse_name'] ?? null) : null)
                    ?: ''
                ));
              @endphp
              <div class="col-lg-3 col-md-6 col-12">
                <label class="text-muted mb-2" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;">Gudang</label>
                <input type="text" class="form-control" id="warehouse_name" value="{{ $opnameWarehouseName !== '' ? $opnameWarehouseName : '-' }}" disabled readonly
                  style="height:42px;border-radius:8px;font-size:13px;background:#f8fafc;font-weight:600;color:#1e40af;cursor:not-allowed;">
              </div>
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

      <!-- Table Card (Consistent with Master Gudang .card-table) -->
      <div class="row">
        <div class="col-sm-12">
          <!-- Toolbar (Search + Action Buttons) - Sticky Floating Card -->
          <div class="stock-opname-toolbar d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div style="max-width: 360px; width: 100%;">
              <div class="position-relative">
                <i class="fe fe-search position-absolute text-muted" style="left: 14px; top: 50%; transform: translateY(-50%); font-size: 15px;"></i>
                <input type="text" class="form-control ps-5" id="filter_pr_name" placeholder="Cari Nama Produk / SKU..." style="height: 42px; border-radius: 8px; font-size: 13px; border: 1px solid #cbd5e1;">
              </div>
            </div>

            <!-- Action Buttons for Desktop -->
            <div class="d-none d-md-flex align-items-center gap-2 flex-wrap stock-opname-actions-desktop">
              @php
                $akses = collect(json_decode(Session::get('user')->role_access));
              @endphp
              @if (
                  $akses->firstWhere('name', 'Stok Opname Produk') &&
                      in_array('others', $akses->firstWhere('name', 'Stok Opname Produk')->akses))
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
              <div class="table-responsive" id="tb-stock-wrap" style="overflow-x: auto;">
                <div class="stock-opname-table-loading" aria-live="polite" aria-busy="true">
                  <div>
                    <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                    <span>Memuat produk...</span>
                  </div>
                </div>
                <table class="table table-hover mb-0" id="tb-stock-table">
                  <thead class="thead-light">
                    <tr>
                      <th class="text-start" style="width:12%;">SKU</th>
                      <th class="text-start" style="width:16%;">Nama Produk</th>
                      <th class="text-center" style="width:52%;">Stok Real</th>
                      <th class="text-start" style="width:20%;">Catatan</th>
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
          @if (
              $akses->firstWhere('name', 'Stok Opname Produk') &&
                  in_array('others', $akses->firstWhere('name', 'Stok Opname Produk')->akses))
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
  </script>
  <script src="{{ asset('Custom_js/Backoffice/Inventory/CreateStockOpname.js') }}?v=12"></script>
@endsection
