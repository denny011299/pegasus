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
@if (
    !Route::is([
        'index-two',
        'index-three',
        'index-four',
        'index-five',
        'signature-preview-invoice',
        'mail-pay-invoice',
        'pay-online',
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
    ]))
    @include('layout.partials.theme-settings')
@endif

@include('layout.partials.footer-scripts')
@yield('custom_js')
</body>

</html>

<script>
    var token= "{{csrf_token()}}";
</script>
<script>
    var route = "{{ Route::currentRouteName() }}";
    window.userRoleId = @json(Session::has('user') ? (int) Session::get('user')->role_id : null);
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
            p => p.name.toLowerCase() === moduleName.toLowerCase()
        );
    }

    function hasAccessAction(moduleName, action) {
        if (window.userRoleId === -1) return true;
        if (!window.permissionList || !Array.isArray(window.permissionList)) return false;

        const found = window.permissionList.find(p =>
            p.name.toLowerCase() === moduleName.toLowerCase()
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
</script>