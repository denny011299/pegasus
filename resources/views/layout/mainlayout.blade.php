<!DOCTYPE html>
@if (!Route::is(['index-two', 'index-three', 'index-four', 'index-five']))
  <html lang="en" data-layout="vertical" data-topbar="light" data-sidebar="light" data-sidebar-size="lg"
    data-sidebar-image="none">
@endif
@if (Route::is(['index-two', 'index-three', 'index-four', 'index-five']))
  <html lang="en">
@endif

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description"
    content="Kanakku provides clean Admin Templates for managing Sales, Payment, Invoice, Accounts and Expenses in HTML, Bootstrap 5, ReactJs, Angular, VueJs and Laravel.">
  <meta name="keywords"
    content="admin, estimates, bootstrap, business, corporate, creative, management, minimal, modern, accounts, invoice, html5, responsive, CRM, Projects">
  <meta name="author" content="Dreamguys - Bootstrap Admin Template">
  <!-- Twitter -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:site" content="@dreamstechnologies">
  <meta name="twitter:title" content="Internal Pegasus Management">
  <meta name="twitter:description"
    content="Kanakku is a Sales, Invoices & Accounts Admin template for Accountant or Companies/Offices with various features for all your needs. Try Demo and Buy Now.">
  <meta name="twitter:image" content="https://kanakku.dreamstechnologies.com/assets/img/kanakku.jpg">
  <meta name="twitter:image:alt" content="Kanakku">

  <!-- Facebook -->
  <meta property="og:url" content="https://kanakku.dreamstechnologies.com/">
  <meta property="og:title" content="Finance & Accounting Admin Website Templates | Kanakku">
  <meta property="og:description"
    content="Kanakku is a Sales, Invoices & Accounts Admin template for Accountant or Companies/Offices with various features for all your needs. Try Demo and Buy Now.">
  <meta property="og:image" content="https://kanakku.dreamstechnologies.com/assets/img/kanakku.jpg">
  <meta property="og:image:secure_url" content="https://kanakku.dreamstechnologies.com/assets/img/kanakku.jpg">
  <meta property="og:image:type" content="image/png">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="600">
  <title>Internal Pegasus Management</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
  <!-- Favicon -->
  <link rel="shortcut icon" href="{{ URL::asset('/assets/pegasus_logo.jpg') }}">

  {{-- Token --}}
  <meta name="csrf-token" content="{{ csrf_token() }}">

  @yield('custom_css')
  @include('layout.partials.head')
</head>
@if (
    !Route::is([
        'chat',
        'mail-pay-invoice',
        'cashreceipt-1',
        'cashreceipt-2',
        'cashreceipt-3',
        'cashreceipt-4',
        'invoice-five',
        'invoice-four-a',
        'invoice-three',
        'invoice-two',
        'invoice-one-a',
        'error-404',
    ]))

  <body>
@endif
@if (Route::is(['error-404']))

  <body class="error-page">
@endif
<!-- Main Wrapper -->
@if (
    !Route::is([
        'index-five',
        'mail-pay-invoice',
        'cashreceipt-1',
        'cashreceipt-2',
        'cashreceipt-3',
        'cashreceipt-4',
        'invoice-four-a',
        'invoice-one-a',
        'invoice-three',
        'invoice-two',
        'forgot-password',
        'lock-screen',
        'login',
        'register',
    ]))
  <div class="main-wrapper">
@endif
@if (Route::is(['forgot-password', 'lock-screen', 'login', 'register']))
  <div class="main-wrapper login-body">
@endif
@if (
    !Route::is([
        'signature-preview-invoice',
        'mail-pay-invoice',
        'pay-online',
        'login',
        'register',
        'saas-login',
        'invoice-subscription',
        'saas-register',
        'forgot-password',
        'lock-screen',
        'error-404',
        'invoice-one-a',
        'invoice-two',
        'invoice-three',
        'invoice-four-a',
        'invoice-five',
        'cashreceipt-1',
        'cashreceipt-2',
        'cashreceipt-3',
        'cashreceipt-4',
        'apiDocsPublic',
        'apiDocsPublicGroup',
    ]))
  @include('layout.partials.header')
@endif
@if (
    !Route::is([
        'signature-preview-invoice',
        'mail-pay-invoice',
        'pay-online',
        'login',
        'register',
        'saas-login',
        'invoice-subscription',
        'saas-register',
        'forgot-password',
        'lock-screen',
        'error-404',
        'invoice-one-a',
        'invoice-two',
        'invoice-three',
        'invoice-four-a',
        'invoice-five',
        'cashreceipt-1',
        'cashreceipt-2',
        'cashreceipt-3',
        'cashreceipt-4',
        'apiDocsPublic',
        'apiDocsPublicGroup',
    ]))
  @include('layout.partials.sidebar')
@endif
@yield('content')
@if (Route::is(['index-three']))
  </div>
@endif
@component('components.modal-popup')
@endcomponent
@if (!Route::is(['mail-pay-invoice', 'cashreceipt-1', 'cashreceipt-2', 'cashreceipt-3', 'cashreceipt-4']))
  </div>
@endif
<!-- /Main Wrapper -->

@php
  $fabWhId = (int) \App\Models\ProductStock::resolveWarehouseId(null);
  $fabSnap = $fabWhId > 0
      ? app(\App\Support\StockOpname\OpenOpnameGuard::class)->statusForWarehouse($fabWhId)
      : ['product' => ['open' => false], 'supplies' => ['open' => false], 'any_open' => false];
  $fabAny = !empty($fabSnap['any_open']);
  $fabProduct = !empty($fabSnap['product']['open']);
  $fabSupplies = !empty($fabSnap['supplies']['open']);
  if ($fabProduct && !$fabSupplies) {
      $fabText = 'Opname Produk aktif';
      $fabHref = $fabSnap['product']['url'] ?? url('/stockOpname');
  } elseif ($fabSupplies && !$fabProduct) {
      $fabText = 'Opname Bahan aktif';
      $fabHref = $fabSnap['supplies']['url'] ?? url('/stockOpnameBahan');
  } elseif ($fabAny) {
      $fabText = 'Opname Produk & Bahan aktif';
      $fabHref = $fabSnap['product']['url'] ?? url('/stockOpname');
  } else {
      $fabText = 'Opname aktif';
      $fabHref = url('/stockOpname');
  }
@endphp
{{-- FAB indikator opname (slot ex theme-settings): tampil hanya jika ada opname open --}}
<a href="{{ $fabHref }}" id="opname-open-fab" class="opname-open-fab"
   title="{{ $fabText }}"
   aria-hidden="{{ $fabAny ? 'false' : 'true' }}"
   style="{{ $fabAny ? 'display:inline-flex' : 'display:none' }}">
  <span class="opname-open-fab-dot" aria-hidden="true"></span>
  <span class="opname-open-fab-text">{{ $fabText }}</span>
</a>
<style>
  /* Badge status opname — samakan dengan dash-toolbar / card premium */
  .opname-status-badges {
    display: inline-flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-end;
    gap: 0.4rem;
  }
  .opname-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.4rem 0.75rem;
    border-radius: 999px;
    background: #ffffff;
    border: 1px solid rgba(15, 23, 42, 0.1);
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    color: #64748b !important;
    text-decoration: none !important;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.01em;
    line-height: 1.25;
    max-width: 100%;
    transition: border-color 0.15s ease, color 0.15s ease, background 0.15s ease, box-shadow 0.15s ease;
  }
  .opname-status-label {
    white-space: nowrap;
  }
  @media (max-width: 575.98px) {
    .opname-status-label {
      white-space: normal;
    }
  }
  .opname-status-badge:hover {
    color: #0f172a !important;
    border-color: rgba(29, 78, 216, 0.35);
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
  }
  .opname-status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #94a3b8;
    flex-shrink: 0;
  }
  .opname-status-badge.is-on {
    color: #166534 !important;
    background: #f0fdf4;
    border-color: rgba(22, 163, 74, 0.35);
  }
  .opname-status-badge.is-on .opname-status-dot {
    background: #22c55e;
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.18);
    animation: opname-status-pulse 1.6s ease-in-out infinite;
  }
  @keyframes opname-status-pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.7; transform: scale(0.9); }
  }

  .opname-open-fab {
    z-index: 999;
    position: fixed;
    right: 20px;
    bottom: 20px;
    display: none;
    align-items: center;
    gap: 0.5rem;
    padding: 0.6rem 0.95rem 0.6rem 0.8rem;
    border-radius: 999px;
    text-decoration: none !important;
    color: #166534 !important;
    background: #ffffff;
    border: 1px solid rgba(22, 163, 74, 0.35);
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 8px 24px rgba(15, 23, 42, 0.1);
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 0.01em;
    transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
  }
  .opname-open-fab:hover {
    color: #14532d !important;
    transform: translateY(-1px);
    border-color: rgba(22, 163, 74, 0.55);
    box-shadow: 0 2px 4px rgba(15, 23, 42, 0.06), 0 12px 28px rgba(15, 23, 42, 0.12);
  }
  .opname-open-fab-dot {
    width: 9px;
    height: 9px;
    border-radius: 50%;
    background: #22c55e;
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.18);
    animation: opname-status-pulse 1.6s ease-in-out infinite;
    flex-shrink: 0;
  }
  @media (max-width: 991.98px) {
    .opname-open-fab { right: 12px; bottom: 12px; }
  }
</style>

@include('layout.partials.footer-scripts')
<script>
  var token = "{{ csrf_token() }}";
</script>
<script>
  var route = "{{ Route::currentRouteName() }}";
  window.userRoleId = @json(Session::has('user') ? (int) Session::get('user')->role_id : null);
  window.activeWarehouseId = {{ (int) \App\Models\ProductStock::resolveWarehouseId(null) }};
  @php
    $permissionListForJs = [];
    if (Session::has('user')) {
        $rawAccess = Session::get('user')->role_access ?? '[]';
        if (is_array($rawAccess)) {
            $permissionListForJs = $rawAccess;
        } elseif (is_string($rawAccess)) {
            $decodedAccess = json_decode($rawAccess, true);
            $permissionListForJs = is_array($decodedAccess) ? $decodedAccess : [];
        }
    }
  @endphp
  // kirim semua permission dari user ke JS (role_access di DB berupa JSON string)
  window.permissionList = @json($permissionListForJs);
  // === GLOBAL PERMISSION HELPER ===
  function hasMenuAccess(moduleName) {
    if (window.userRoleId === -1) return true;
    if (!window.permissionList || !Array.isArray(window.permissionList)) return false;

    return window.permissionList.some(
      p => p && typeof p.name === "string" && p.name.toLowerCase() === moduleName.toLowerCase()
    );
  }

  function hasAccessAction(moduleName, action) {
    if (window.userRoleId === -1) return true;
    if (!window.permissionList || !Array.isArray(window.permissionList)) return false;

    const found = window.permissionList.find(p =>
      p && typeof p.name === "string" && p.name.toLowerCase() === moduleName.toLowerCase()
    );

    if (!found) return false;

    const akses = found.akses;
    if (!Array.isArray(akses)) return false;

    return akses.map(a => String(a).toLowerCase()).includes(action.toLowerCase());
  }

  /** True jika salah satu modul punya akses tersebut (untuk Kas / Kas Operasional, Wilayah, dll.) */
  function hasAccessActionAny(moduleNames, action) {
    if (window.userRoleId === -1) return true;
    if (!moduleNames || !moduleNames.length) return false;
    var a = String(action).toLowerCase();
    for (var i = 0; i < moduleNames.length; i++) {
      if (hasAccessAction(moduleNames[i], a)) return true;
    }
    return false;
  }

  var KAS_OR_OP_MODS = ["Kas", "Kas Operasional"];
  var AREA_MASTER_MODS = ["Kategori", "Satuan", "Variasi"];

  /** Ikon lihat (fe fe-eye) jika modul punya akses view */
  function roleIconView(moduleName, className, dataAttrs) {
    if (!hasAccessAction(moduleName, "view")) return "";
    return (
      '<a class="' +
      className +
      '" ' +
      (dataAttrs || "") +
      '><i class="fe fe-eye"></i></a>'
    );
  }

  /** Ikon edit (fe fe-edit) jika modul punya akses edit */
  function roleIconEdit(moduleName, className, dataAttrs) {
    if (!hasAccessAction(moduleName, "edit")) return "";
    return (
      '<a class="' +
      className +
      '" ' +
      (dataAttrs || "") +
      '><i class="fe fe-edit"></i></a>'
    );
  }

  /** Ikon hapus (fe fe-trash-2) jika modul punya akses delete */
  function roleIconDelete(moduleName, className, dataAttrs) {
    if (!hasAccessAction(moduleName, "delete")) return "";
    return (
      '<a class="' +
      className +
      '" ' +
      (dataAttrs || "") +
      '><i class="fe fe-trash-2"></i></a>'
    );
  }

  // GitHub #53 follow-up: baris 'open' di dashboard_change_logs sebelumnya HANYA ditutup
  // secara pasif -- saat staf membuka menu lain (LogDashboardActivity::logOpen()). Kalau tab
  // ditutup (atau browser ditutup) tanpa navigasi lagi, baris itu nyangkut "Sedang dibuka"
  // selamanya. navigator.sendBeacon() dipilih karena request biasa (fetch/XHR) BOLEH dibatalkan
  // browser saat unload, sedangkan sendBeacon dijamin terkirim di background walau tab sudah
  // ditutup.
  //
  // 'pagehide' dipakai, bukan 'beforeunload'/'unload' -- keduanya legacy, mematikan bfcache
  // (halaman tidak bisa di-cache untuk tombol back/forward), dan sebagian browser mobile tidak
  // konsisten memanggilnya. 'visibilitychange' -> hidden ditambah sebagai fallback: di Safari
  // iOS, menutup tab/app-switch kadang hanya memicu ini, bukan pagehide.
  (function () {
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || "";
    var sent = false;
    function closeDashboardSession() {
      if (sent || !navigator.sendBeacon) return;
      sent = true;
      var data = new FormData();
      data.append("_token", csrfToken);
      // window.__dashboardActivityToken diisi oleh LogDashboardActivity::injectClientToken()
      // -- mengidentifikasi baris 'open' HALAMAN INI secara spesifik, supaya beacon tidak
      // salah tutup baris lain yang keburu dibuat navigasi berikutnya (race condition kalau
      // cuma mengandalkan "baris open terakhir").
      if (window.__dashboardActivityToken) {
        data.append("token", window.__dashboardActivityToken);
      }
      navigator.sendBeacon("{{ url('closeDashboardSession') }}", data);
    }
    window.addEventListener("pagehide", closeDashboardSession);
    document.addEventListener("visibilitychange", function () {
      if (document.visibilityState === "hidden") closeDashboardSession();
    });
  })();
</script>
@yield('custom_js')
</body>

</html>
