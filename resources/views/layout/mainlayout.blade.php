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
  <!-- Favicon (Company Setting) -->
  <link rel="icon" href="{{ \App\Models\Setting::assetUrl('favicon') }}">
  <link rel="shortcut icon" href="{{ \App\Models\Setting::assetUrl('favicon') }}">

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

<style>
  /* Badge status opname — Modern Glassmorphism & Radiant Radar Pulse */
  .opname-status-badges {
    display: inline-flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-end;
    gap: 0.5rem;
  }
  .opname-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.42rem 0.85rem;
    border-radius: 999px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    color: #64748b !important;
    text-decoration: none !important;
    font-size: 12px;
    font-weight: 500;
    letter-spacing: 0.015em;
    line-height: 1.25;
    max-width: 100%;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
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
    background: #ffffff;
    border-color: #cbd5e1;
    box-shadow: 0 3px 10px rgba(15, 23, 42, 0.08);
    transform: translateY(-1px);
  }
  .opname-status-dot {
    position: relative;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #94a3b8;
    flex-shrink: 0;
    transition: background 0.2s ease;
  }

  /* Active Opname Badge (Luminous Emerald Glow & Live Radar Beacon) */
  .opname-status-badge.is-on {
    color: #065f46 !important;
    font-weight: 600;
    background: linear-gradient(135deg, rgba(236, 253, 245, 0.95) 0%, rgba(209, 250, 229, 0.85) 100%);
    border: 1px solid rgba(16, 185, 129, 0.45);
    box-shadow: 0 2px 8px -1px rgba(16, 185, 129, 0.22), 0 1px 2px rgba(15, 23, 42, 0.04), inset 0 1px 0 rgba(255, 255, 255, 0.85);
  }
  .opname-status-badge.is-on:hover {
    color: #047857 !important;
    background: linear-gradient(135deg, #dcfce7 0%, #a7f3d0 100%);
    border-color: rgba(16, 185, 129, 0.7);
    box-shadow: 0 4px 14px -1px rgba(16, 185, 129, 0.32), inset 0 1px 0 #ffffff;
    transform: translateY(-1px);
  }
  .opname-status-badge.is-on .opname-status-dot {
    background: #10b981;
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.25);
  }
  .opname-status-badge.is-on .opname-status-dot::before {
    content: "";
    position: absolute;
    top: 50%;
    left: 50%;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: #10b981;
    transform: translate(-50%, -50%);
    animation: opname-soft-ripple 2.4s cubic-bezier(0.16, 1, 0.3, 1) infinite;
    pointer-events: none;
  }

  /* Lampu Opname Navbar (Ramah di Mata, Glow Lembut & Organik) */
  .opname-open-fab-dot {
    position: relative;
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #34d399;
    box-shadow: 0 0 6px rgba(52, 211, 153, 0.85), 0 0 1px #34d399;
    flex-shrink: 0;
    animation: opname-lamp-glow 2.4s ease-in-out infinite;
  }
  .opname-open-fab-dot::before {
    content: "";
    position: absolute;
    top: 50%;
    left: 50%;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: #34d399;
    transform: translate(-50%, -50%);
    animation: opname-soft-ripple 2.4s cubic-bezier(0.16, 1, 0.3, 1) infinite;
    pointer-events: none;
  }
  @keyframes opname-lamp-glow {
    0%, 100% {
      box-shadow: 0 0 4px rgba(52, 211, 153, 0.7), 0 0 1px #34d399;
    }
    50% {
      box-shadow: 0 0 8px rgba(52, 211, 153, 0.95), 0 0 2px #a7f3d0;
    }
  }
  @keyframes opname-soft-ripple {
    0% {
      transform: translate(-50%, -50%) scale(1);
      opacity: 0.6;
    }
    60%, 100% {
      transform: translate(-50%, -50%) scale(2.0);
      opacity: 0;
    }
  }

  /* Badge Stock Opname Navbar — Sleek Dark-Glassmorphism Pill */
  .header.custom-premium-header .opname-open-fab,
  .opname-open-fab {
    z-index: 20;
    position: absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    display: none;
    align-items: center;
    justify-content: center;
    gap: 7px;
    height: 28px;
    width: auto;
    max-width: min(320px, calc(100vw - 280px));
    padding: 0 12px;
    border-radius: 999px;
    text-decoration: none !important;
    color: #ecfdf5 !important;
    background: rgba(16, 185, 129, 0.12);
    border: 1px solid rgba(52, 211, 153, 0.32);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25), 0 0 10px rgba(16, 185, 129, 0.12), inset 0 1px 0 rgba(255, 255, 255, 0.08);
    font-size: 11.5px;
    font-weight: 500;
    letter-spacing: 0.01em;
    line-height: 1;
    white-space: nowrap;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  }
  .opname-open-fab:hover {
    color: #ffffff !important;
    background: rgba(16, 185, 129, 0.22);
    border-color: rgba(52, 211, 153, 0.55);
    box-shadow: 0 3px 12px rgba(0, 0, 0, 0.3), 0 0 14px rgba(16, 185, 129, 0.25), inset 0 1px 0 rgba(255, 255, 255, 0.15);
    transform: translate(-50%, -50%) translateY(-1px);
  }
  .opname-open-fab-text {
    overflow: hidden;
    text-overflow: ellipsis;
    color: #ecfdf5;
    font-weight: 500;
  }
  .opname-open-fab-short {
    display: none;
    overflow: hidden;
    text-overflow: ellipsis;
    color: #ecfdf5;
    font-weight: 500;
  }
  .opname-open-fab-cta {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    flex-shrink: 0;
    font-size: 11px;
    font-weight: 600;
    color: #6ee7b7;
    margin-left: 2px;
    padding-left: 6px;
    border-left: 1px solid rgba(52, 211, 153, 0.28);
  }
  .opname-open-fab-arrow {
    font-size: 10px;
    color: #6ee7b7;
    transition: transform 0.15s ease;
  }
  .opname-open-fab:hover .opname-open-fab-arrow {
    transform: translateX(2px);
    color: #a7f3d0;
  }
  @media (max-width: 1199.98px) {
    .header.custom-premium-header .opname-open-fab,
    .opname-open-fab {
      max-width: min(240px, calc(100vw - 220px));
      font-size: 11px;
      height: 26px;
      padding: 0 10px;
    }
    .opname-open-fab-cta {
      display: none;
    }
  }
  @media (max-width: 991.98px) {
    /* Pada layar tablet & HP (<= 991.98px):
       Tombol gudang menyusut jadi ikon bulat 36px di left: 45px (berakhir di 81px).
       Posisikan badge opname di samping kanan ikon gudang (left: 88px), BUKAN di left: 50%
       karena user-menu di kanan memakan ~220px sehingga left: 50% menabrak ikon menu. */
    .header.custom-premium-header .opname-open-fab,
    .opname-open-fab {
      left: 88px !important;
      right: auto !important;
      top: 50% !important;
      transform: translateY(-50%) !important;
      max-width: calc(100vw - 310px);
      height: 26px;
      padding: 0 10px;
      font-size: 11px;
      gap: 6px;
    }
    .header.custom-premium-header .opname-open-fab:hover,
    .opname-open-fab:hover {
      transform: translateY(-50%) translateY(-1px) !important;
    }
    .opname-open-fab-cta {
      display: none;
    }
  }
  @media (max-width: 575.98px) {
    /* Pada smartphone portrait (< 576px, misal iPhone/Android 360px-440px):
       Sembunyikan teks panjang ("Opname Produk aktif"), gunakan label ringkas "Opname"
       agar pas rapi di antara ikon gudang dan user-menu tanpa menabrak ikon kanan. */
    .header.custom-premium-header .opname-open-fab,
    .opname-open-fab {
      left: 88px !important;
      max-width: calc(100vw - 235px);
      height: 26px;
      padding: 0 9px;
      font-size: 11px;
      gap: 5px;
    }
    .opname-open-fab-text {
      display: none !important;
    }
    .opname-open-fab-short {
      display: inline !important;
    }
  }
  @media (max-width: 359.98px) {
    /* Pada layar ekstra kecil (< 360px): jadikan tombol bulat icon-only dengan lampu hijau */
    .header.custom-premium-header .opname-open-fab,
    .opname-open-fab {
      left: 86px !important;
      width: 26px !important;
      height: 26px !important;
      padding: 0 !important;
      justify-content: center !important;
      border-radius: 50% !important;
    }
    .opname-open-fab-short {
      display: none !important;
    }
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
