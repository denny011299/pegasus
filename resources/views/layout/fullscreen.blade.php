{{-- Layout full-bleed: tanpa sidebar/top navbar (dipakai PP View mode). --}}
<!DOCTYPE html>
<html lang="en" data-layout="vertical" data-topbar="light" data-sidebar="light" data-sidebar-size="lg"
    data-sidebar-image="none">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internal Pegasus Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="icon" href="{{ \App\Models\Setting::assetUrl('favicon') }}">
    <link rel="shortcut icon" href="{{ \App\Models\Setting::assetUrl('favicon') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @yield('custom_css')
    @include('layout.partials.head')
    <style>
        body.pp-fullscreen-body {
            background: #f1f5f9;
        }
        body.pp-fullscreen-body .main-wrapper {
            margin: 0;
            padding: 0;
        }
        /* Kanakku default margin-left sidebar — hilangkan di mode view */
        body.pp-fullscreen-body .page-wrapper {
            margin-left: 0 !important;
            padding-top: 0 !important;
            min-height: 100vh;
            background: #f1f5f9 !important;
        }
        body.pp-fullscreen-body .page-wrapper .content.container-fluid {
            max-width: 100%;
            padding: 16px 22px 22px;
        }
    </style>
</head>
<body class="pp-fullscreen-body">
<div class="main-wrapper">
    @yield('content')
</div>
@component('components.modal-popup')
@endcomponent
@include('layout.partials.footer-scripts')
<script>
    var token = "{{ csrf_token() }}";
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
    window.permissionList = @json($permissionListForJs);
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
    function hasAccessActionAny(moduleNames, action) {
        if (window.userRoleId === -1) return true;
        if (!moduleNames || !moduleNames.length) return false;
        var a = String(action).toLowerCase();
        for (var i = 0; i < moduleNames.length; i++) {
            if (hasAccessAction(moduleNames[i], a)) return true;
        }
        return false;
    }
    function roleIconView(moduleName, className, dataAttrs) {
        if (!hasAccessAction(moduleName, "view")) return "";
        return '<a class="' + className + '" ' + (dataAttrs || "") + '><i class="fe fe-eye"></i></a>';
    }
    function roleIconEdit(moduleName, className, dataAttrs) {
        if (!hasAccessAction(moduleName, "edit")) return "";
        return '<a class="' + className + '" ' + (dataAttrs || "") + '><i class="fe fe-edit"></i></a>';
    }
    function roleIconDelete(moduleName, className, dataAttrs) {
        if (!hasAccessAction(moduleName, "delete")) return "";
        return '<a class="' + className + '" ' + (dataAttrs || "") + '><i class="fe fe-trash-2"></i></a>';
    }
</script>
@yield('custom_js')
</body>
</html>
