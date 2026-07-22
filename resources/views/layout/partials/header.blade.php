<!-- Header -->
<style>
    .search-expand {
        width: 15rem; /* ukuran default */
        transition: width 0.3s ease; /* animasi */
    }

    .search-expand:focus {
        width: 25rem; /* ukuran membesar saat diklik */
    }
    #camera, #preview-box {
        width: 100%;
        max-width: 600px;
        margin: auto;
        text-align: center;
    }
    video, img {
        width: 100%;
        border-radius: 10px;
    }
    .is-invalids {
        border-color: #dc3545!important;
    }
</style>

<style>
    /* =============================================
       PREMIUM NAVBAR DESIGN
       ============================================= */
    .custom-premium-header {
        background: linear-gradient(90deg, #0f172a 0%, #1e3a8a 100%) !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
        box-shadow: 0 4px 25px rgba(0, 0, 0, 0.15) !important;
    }
    .custom-premium-header .nav-item .nav-link,
    .custom-premium-header .toggle-switch,
    .custom-premium-header .win-maximize {
        color: #ffffff !important;
    }
    .custom-premium-header .dropdown-heads a {
        background: rgba(255, 255, 255, 0.12) !important;
        border: 1px solid rgba(255, 255, 255, 0.15) !important;
        color: #ffffff !important;
        border-radius: 12px !important;
        transition: all 0.25s ease;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    .custom-premium-header .dropdown-heads a i,
    .custom-premium-header .dropdown-heads a svg,
    .custom-premium-header .dropdown-heads a .fe,
    .custom-premium-header .dropdown-heads a .fa-solid {
        color: #ffffff !important;
        fill: #ffffff !important;
    }
    .custom-premium-header .dropdown-heads a:hover {
        background: rgba(255, 255, 255, 0.2) !important;
        border-color: rgba(255, 255, 255, 0.3) !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .custom-premium-header .user-menu .user-img img {
        border: 2px solid rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        padding: 2px;
        transition: all 0.2s ease;
        background: rgba(255,255,255,0.1);
    }
    .custom-premium-header .user-menu:hover .user-img img {
        border-color: rgba(255, 255, 255, 0.5);
        transform: scale(1.05);
    }
    .custom-premium-header .user-name {
        color: rgba(255, 255, 255, 0.95) !important;
        font-weight: 500 !important;
    }
    .custom-premium-header .user-details {
        color: rgba(255, 255, 255, 0.6) !important;
    }
    .custom-premium-header .toggle-bars .bar-icons {
        background-color: #ffffff !important;
        opacity: 0.8;
    }
    .custom-premium-header #toggle_btn:hover .bar-icons {
        opacity: 1;
    }
</style>

@if (!Route::is(['index-three', 'index-four', 'index-five']))
    @if (!Route::is(['index-two']))
        <div class="header header-one custom-premium-header">
    @endif
    @if (Route::is(['index-two']))
        <div class="header header-two custom-premium-header">
    @endif
    @if (!Route::is(['index-two']))
        
    @endif
    <!-- Sidebar Toggle -->
    <a href="javascript:void(0);" id="toggle_btn">
        <span class="toggle-bars">
            <span class="bar-icons"></span>
            <span class="bar-icons"></span>
            <span class="bar-icons"></span>
            <span class="bar-icons"></span>
        </span>
    </a>
    <!-- /Sidebar Toggle -->

    <!-- Custom Warehouse Dropdown -->
    <style>        /* =============================================
           WAREHOUSE DROPDOWN — PREMIUM INDIGO
           ============================================= */
        .warehouse-custom-dropdown .btn-warehouse {
            min-width: 250px;
            height: 36px;
            border-radius: 10px;
            padding: 0 14px;
            font-size: 13px;
            font-weight: 600;
            color: #1e40af;
            background: #eff6ff;
            border: 1.5px solid #bfdbfe;
            box-shadow: 0 1px 4px rgba(37, 99, 235, 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            transition: all 0.2s ease;
        }
        .warehouse-custom-dropdown .btn-warehouse:hover,
        .warehouse-custom-dropdown .btn-warehouse:focus {
            background: #dbeafe;
            border-color: #60a5fa;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.18);
            color: #1e40af;
        }
        .warehouse-custom-dropdown .btn-warehouse .fas {
            color: #2563eb !important;
            font-size: 13px;
        }
        /* Dropdown Menu */
        .warehouse-custom-dropdown .dropdown-menu {
            border-radius: 14px;
            border: 1px solid #dbeafe;
            background: #ffffff;
            box-shadow: 0 12px 32px rgba(37, 99, 235, 0.1), 0 2px 8px rgba(0,0,0,0.05);
            min-width: 280px;
            padding: 8px;
            margin-top: 6px !important;
        }
        /* Group header */
        .warehouse-custom-dropdown .dropdown-header {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.08em;
            color: #94a3b8;
            padding: 10px 10px 4px;
            text-transform: uppercase;
        }
        /* Items */
        .warehouse-custom-dropdown .dropdown-item {
            padding: 9px 12px;
            font-size: 13.5px;
            font-weight: 500;
            color: #374151;
            display: flex;
            align-items: center;
            gap: 10px;
            border-radius: 8px;
            transition: all 0.15s ease;
        }
        .warehouse-custom-dropdown .dropdown-item i {
            color: #3b82f6;
            font-size: 14px;
            width: 16px;
            text-align: center;
        }
        .warehouse-custom-dropdown .dropdown-item:hover {
            background: #eff6ff;
            color: #1d4ed8;
        }
        .warehouse-custom-dropdown .dropdown-item:hover i {
            color: #2563eb;
        }
        .warehouse-custom-dropdown .dropdown-item.active {
            background: #dbeafe;
            color: #1d4ed8;
            font-weight: 600;
        }
        .warehouse-custom-dropdown .dropdown-item.active i {
            color: #2563eb;
        }
        /* Separator between groups */
        .warehouse-custom-dropdown .dropdown-divider {
            border-color: #e0f0ff;
            margin: 4px 0;
        }

        /* ============================================================
           GLASSMORPHISM DATATABLE — PREMIUM DESIGN
           ============================================================ */

        /* Page background - clean gray */
        .page-wrapper {
            background: #f1f5f9 !important;
            min-height: 100vh;
        }
        .content.container-fluid {
            padding: 24px 28px;
        }

        /* Glass Card Container */
        .card-table {
            background: rgba(255, 255, 255, 0.55) !important;
            backdrop-filter: blur(20px) !important;
            -webkit-backdrop-filter: blur(20px) !important;
            border: 1px solid rgba(255, 255, 255, 0.7) !important;
            border-radius: 18px !important;
            box-shadow:
                0 8px 32px rgba(37, 99, 235, 0.08),
                0 2px 8px rgba(0, 0, 0, 0.04),
                inset 0 1px 0 rgba(255,255,255,0.9) !important;
            overflow: hidden;
        }

        /* Base Table Reset */
        table.dataTable {
            border-collapse: collapse !important;
            width: 100% !important;
            margin: 0 !important;
            border-bottom: none !important;
        }

        /* =============================================
           DATATABLE — PREMIUM REDESIGN
           ============================================= */
        
        /* 1. Base Reset */
        table.dataTable {
            border-collapse: collapse !important;
            width: 100% !important;
            margin: 0 !important;
            border-bottom: none !important;
        }

        /* 2. Wrapper */
        .dataTables_wrapper {
            font-size: 13px;
            color: #475569;
            width: 100%;
            position: relative;
        }
        .dataTables_wrapper::after {
            content: "";
            display: table;
            clear: both;
        }

        /* 3. Top Bar (Search & Length) */
        .dataTables_filter {
            float: left;
            padding: 16px 20px;
        }
        .dataTables_length {
            float: left;
            padding: 16px 20px;
        }
        .dataTables_length select {
            border: 1px solid #e2e8f0 !important;
            border-radius: 8px !important;
            padding: 5px 10px !important;
            font-size: 13px !important;
            color: #334155 !important;
            background: #ffffff !important;
            outline: none !important;
            cursor: pointer;
            box-shadow: 0 1px 2px rgba(0,0,0,0.02);
        }

        /* Search box — clean pill */
        .dataTables_filter label {
            display: flex !important;
            align-items: center !important;
            position: relative !important;
            margin-bottom: 0 !important;
        }
        .dataTables_filter label::before {
            content: '\f002';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            left: 14px;
            color: #94a3b8;
            font-size: 13px;
            z-index: 1;
            pointer-events: none;
        }
        .dataTables_filter label > span:first-child,
        .dataTables_filter label > *:not(input) {
            display: none !important;
        }
        .dataTables_filter input[type="search"] {
            border: 1px solid #e2e8f0 !important;
            border-radius: 30px !important;
            background: #f8fafc !important;
            padding: 9px 18px 9px 40px !important;
            font-size: 13px !important;
            width: 260px !important;
            color: #1e293b !important;
            outline: none !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.02) !important;
        }
        .dataTables_filter input[type="search"]:focus {
            background: #ffffff !important;
            border-color: #3b82f6 !important;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.12) !important;
            width: 320px !important;
        }

        /* 4. Fix Scroll Gap safely (Do NOT touch table margin-top) */
        .dataTables_scrollHead {
            border-bottom: 2px solid #bfdbfe !important;
            background: linear-gradient(90deg, #eff6ff 0%, #e0f2fe 100%) !important;
            border-radius: 0 !important;
        }
        .dataTables_scrollBody {
            border-bottom: 1px solid #e2e8f0 !important;
        }
        div.dataTables_scrollBody thead,
        div.dataTables_scrollBody table.dataTable thead tr,
        div.dataTables_scrollBody table.dataTable thead th,
        div.dataTables_scrollBody table.dataTable thead td {
            height: 0 !important;
            padding: 0 !important;
            margin: 0 !important;
            border: none !important;
            font-size: 0 !important;
            line-height: 0 !important;
            visibility: hidden !important;
            background: transparent !important;
        }
        
        /* 5. Header Styling */
        table.dataTable thead th {
            background: transparent !important;
            color: #1e40af !important;
            font-size: 11.5px !important;
            font-weight: 800 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.08em !important;
            border-bottom: none !important;
            border-top: none !important;
            padding: 16px 18px !important;
            vertical-align: middle !important;
            white-space: nowrap;
            text-shadow: 0 1px 1px rgba(255,255,255,0.7);
            border-radius: 0 !important;
        }
        table.dataTable thead th:first-child,
        table.dataTable thead th:last-child {
            border-radius: 0 !important;
        }

        /* 6. Body Styling */
        table.dataTable tbody td {
            background: #ffffff !important;
            color: #334155 !important;
            font-size: 13.5px;
            padding: 14px 18px !important;
            vertical-align: middle !important;
            border-top: none !important;
            border-bottom: 1px solid #f1f5f9 !important;
            transition: background 0.15s ease;
        }
        table.dataTable tbody tr:hover td {
            background: #f8fafc !important; /* Soft hover effect */
        }
        table.dataTable tbody tr:last-child td {
            border-bottom: none !important;
        }

        /* 7. Footer (Pagination & Info) */
        /* To fix the "Show 10 entries" overlapping with pagination if they are in the same row,
           we just let them float normally with clear: both */
        .dataTables_info {
            float: left;
            padding: 18px 20px !important;
            color: #64748b !important;
            font-size: 13px !important;
        }
        .dataTables_paginate {
            float: right;
            padding: 14px 20px !important;
        }
        
        /* Pagination buttons */
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border-radius: 6px !important;
            padding: 6px 12px !important;
            margin: 0 2px !important;
            border: none !important;
            background: transparent !important;
            color: #475569 !important;
            font-weight: 500;
            font-size: 13px;
            transition: all 0.15s ease;
            cursor: pointer;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #f1f5f9 !important;
            border-color: #cbd5e1 !important;
            color: #1e293b !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background: #3b82f6 !important;
            color: #ffffff !important;
            border-color: #3b82f6 !important;
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2) !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
            color: #94a3b8 !important;
            background: #f8fafc !important;
            border-color: #e2e8f0 !important;
            box-shadow: none !important;
            cursor: not-allowed;
        }

        /* === ACTION BUTTONS === */
        .btn-action-icon {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 32px !important;
            height: 32px !important;
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 8px !important;
            font-size: 13px !important;
            color: #64748b !important;
            transition: all 0.18s ease !important;
            box-shadow: 0 1px 2px rgba(0,0,0,0.04) !important;
            margin: 0 2px;
            cursor: pointer;
        }
        .btn-action-icon:hover {
            color: #2563eb !important;
            background: #eff6ff !important;
            border-color: #bfdbfe !important;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.15) !important;
            transform: translateY(-1px) !important;
        }
        /* DELETE — always red */
        .btn-action-icon.btn_delete,
        .btn-action-icon.text-danger {
            color: #ef4444 !important;
        }
        .btn-action-icon.btn_delete:hover,
        .btn-action-icon.text-danger:hover,
        .btn-action-icon.btn-danger-soft:hover {
            color: #dc2626 !important;
            background: #fef2f2 !important;
            border-color: #fca5a5 !important;
            box-shadow: 0 2px 8px rgba(220, 38, 38, 0.15) !important;
            transform: translateY(-1px) !important;
        }
        /* STATUS NON-AKTIF — orange/warning */
        .btn-action-icon.text-warning {
            color: #f59e0b !important;
        }
        .btn-action-icon.text-warning:hover {
            color: #d97706 !important;
            background: #fffbeb !important;
            border-color: #fcd34d !important;
            box-shadow: 0 2px 8px rgba(217, 119, 6, 0.15) !important;
            transform: translateY(-1px) !important;
        }
        /* STATUS AKTIFKAN — green */
        .btn-action-icon.text-success {
            color: #22c55e !important;
        }
        .btn-action-icon.text-success:hover {
            color: #16a34a !important;
            background: #f0fdf4 !important;
            border-color: #86efac !important;
            box-shadow: 0 2px 8px rgba(22, 163, 74, 0.15) !important;
            transform: translateY(-1px) !important;
        }
    </style>
    <div class="dropdown warehouse-custom-dropdown" style="float: left; margin-left: 45px; margin-top: 13px;">
        @php
            $activeWh = null;
            if(isset($warehouses)) {
                $activeWh = collect($warehouses)->firstWhere('id', session('active_warehouse_id'));
            }
        @endphp
        <button class="btn btn-warehouse dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <div class="d-flex align-items-center gap-2">
                <i class="fas fa-warehouse" style="color: #6366f1;"></i>
                <span>{{ $activeWh ? ($activeWh->warehouse_name ?? $activeWh->name) : 'Pilih Gudang...' }}</span>
            </div>
        </button>
        <ul class="dropdown-menu">
            @php
                // Mock grouped warehouses for UI, cursor will fix backend loop if needed
                $groupedWarehouses = collect($warehouses ?? [])->groupBy(function($wh) {
                    return strtoupper($wh->type->warehouse_type_name ?? 'DAFTAR GUDANG');
                });
            @endphp
            
            @foreach($groupedWarehouses as $type => $whs)
                <li><h6 class="dropdown-header">{{ $type }}</h6></li>
                @foreach($whs as $wh)
                    <li>
                        <a class="dropdown-item warehouse-dropdown-item {{ session('active_warehouse_id') == $wh->id ? 'active' : '' }}" href="javascript:void(0)" data-id="{{ $wh->id }}">
                            <i class="fas fa-warehouse"></i>
                            {{ $wh->warehouse_name ?? $wh->name }}
                        </a>
                    </li>
                @endforeach
                @if(!$loop->last)
                    <li><hr class="dropdown-divider"></li>
                @endif
            @endforeach
        </ul>
    </div>
    <style>
        @media (min-width: 992px) {
            .warehouse-select-container {
                display: block !important;
            }
        }
    </style>
    <!-- /Warehouse Select -->

    <!-- Mobile Menu Toggle -->
    <a class="mobile_btn" id="mobile_btn">
        <i class="fas fa-bars" style="color: white"></i>
    </a>
    <!-- /Mobile Menu Toggle -->

    <!-- Header Menu -->
    <ul class="nav nav-tabs user-menu">
        <li class="nav-item dropdown-heads">
            <a class="nav-link fotoProduksi" href="" style="width: 2rem; height: 2rem" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Foto Bukti Produksi">
                <i class="fe fe-camera"></i>
            </a>
        </li>
        <li class="nav-item dropdown-heads">
            <a class="nav-link position-relative" href="/stockAlert" style="width: 2rem; height: 2rem" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Stok Barang">
                <i class="fe fe-layers"></i>
                @if(isset($hasStockAlert))
                    <span class="position-absolute top-0 start-100 translate-middle
                                bg-danger rounded-circle mt-1"
                        style="width: 8px; height: 8px;">
                    </span>
                @endif
            </a>
        </li>
        <li class="nav-item dropdown-heads ">
            <a href="/production" class="nav-link" style="width: 2rem; height: 2rem" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Produksi">
                <i class="fa-solid fa-gear"></i>
            </a>
        </li>
        <li class="nav-item dropdown-heads ">
            <a href="/operationalCash" class="nav-link" style="width: 2rem; height: 2rem" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Kas Operasional">
                <i class="fe fe-dollar-sign"></i>
            </a>
        </li>
        <!-- User Menu -->
        <li class="nav-item dropdown">
            <a href="javascript:void(0)" class="user-link  nav-link" data-bs-toggle="dropdown">
                <span class="user-img">
                    <img src="{{ URL::asset('/assets/img/profiles/avatar-23.png') }}" alt="img"
                            class="profilesidebar">
                    <span class="animate-circle"></span>
                </span>
                <span class="user-content">
                    <span class="user-details" style="color: white">{{Session::get('user')["role_name"]}}</span>
                    <span class="user-name" style="color: white">{{Session::get('user')["staff_name"]}}</span>
                </span>
            </a>
            <div class="dropdown-menu menu-drop-user">
                <div class="profilemenu">
                    {{-- <div class="subscription-menu">
                        <ul>
                            <li>
                                <a class="dropdown-item" href="{{ url('profiles') }}">Profile</a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ url('settings') }}">Settings</a>
                            </li>
                        </ul>
                    </div> --}}
                    <div class="subscription-logout">
                        <ul>
                            <li class="pb-0">
                                <a class="dropdown-item" href="{{ url('login') }}">Log Out</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </li>
        <!-- /User Menu -->

    </ul>

    <!-- /Header Menu -->

    </div>
    <!-- /Header -->
@endif

@if (Route::is(['index-three']))
    <!-- Header -->
    <div class="header header-five">

        <div class="container">
            <!-- Logo -->
            <div class="header-left header-left-five">
                <a href="{{ url('/') }}" class="logo">
                    <img src="{{ URL::asset('/assets/img/logo.png') }}" alt="Logo">
                </a>
                <a href="{{ url('/') }}" class="white-logo">
                    <img src="{{ URL::asset('/assets/img/logo-white.png') }}" alt="Logo">
                </a>
                <a href="{{ url('/') }}" class="logo logo-small">
                    <img src="{{ URL::asset('/assets/img/logo-small.png') }}" alt="Logo" width="30"
                        height="30">
                </a>
            </div>
            <!-- /Logo -->

            <!-- Sidebar Toggle -->
            <a href="javascript:void(0);" id="toggle_btn">
                <span class="toggle-bars">
                    <span class="bar-icons"></span>
                    <span class="bar-icons"></span>
                    <span class="bar-icons"></span>
                    <span class="bar-icons"></span>
                </span>
            </a>
            <!-- /Sidebar Toggle -->

            <!-- Search -->
            <div class="top-nav-search">
                <form>
                    <input type="text" class="form-control" placeholder="Search here">
                    <button class="btn" type="submit"><img
                            src="{{ URL::asset('/assets/img/icons/search.svg') }}" alt="img"></button>
                </form>
            </div>
            <!-- /Search -->

            <!-- Mobile Menu Toggle -->
            <a class="mobile_btn" id="mobile_btn">
                <i class="fas fa-bars"></i>
            </a>
            <!-- /Mobile Menu Toggle -->

            <!-- Header Menu -->
            <ul class="nav nav-tabs user-menu user-menu-five">
                <!-- Flag -->
                <li class="nav-item dropdown has-arrow flag-nav" style="color: white">
                    <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button">
                        <img src="{{ URL::asset('/assets/img/flags/us1.png') }}" alt="flag"><span>English</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <a href="javascript:void(0);" class="dropdown-item">
                            <img src="{{ URL::asset('/assets/img/flags/us.png') }}"
                                alt="flag"><span>English</span>
                        </a>
                        <a href="javascript:void(0);" class="dropdown-item">
                            <img src="{{ URL::asset('/assets/img/flags/fr.png') }}"
                                alt="flag"><span>French</span>
                        </a>
                        <a href="javascript:void(0);" class="dropdown-item">
                            <img src="{{ URL::asset('/assets/img/flags/es.png') }}"
                                alt="flag"><span>Spanish</span>
                        </a>
                        <a href="javascript:void(0);" class="dropdown-item">
                            <img src="{{ URL::asset('/assets/img/flags/de.png') }}"
                                alt="flag"><span>German</span>
                        </a>
                    </div>
                </li>
                <!-- /Flag -->


                <li class="nav-item  has-arrow dropdown-heads ">
                    <a href="javascript:void(0);" class="toggle-switch">
                        <i class="fe fe-moon"></i>
                    </a>
                </li>
                <li class="nav-item dropdown  flag-nav dropdown-heads">
                    <a class="nav-link" data-bs-toggle="dropdown" href="#" role="button">
                        <i class="fe fe-bell"></i> <span class="badge rounded-pill"></span>
                    </a>
                    <div class="dropdown-menu notifications">
                        <div class="topnav-dropdown-header">
                            <div class="notification-title">Notifications <a href="{{ url('notifications') }}">View
                                    all</a></div>
                            <a href="javascript:void(0)" class="clear-noti d-flex align-items-center">Mark all as
                                read <i class="fe fe-check-circle"></i></a>
                        </div>
                        <div class="noti-content">
                            <ul class="notification-list">
                                <li class="notification-message">
                                    <a href="{{ url('profile') }}">
                                        <div class="d-flex">
                                            <span class="avatar avatar-md active">
                                                <img class="avatar-img rounded-circle" alt="avatar-img"
                                                    src="{{ URL::asset('/assets/img/profiles/avatar-02.jpg') }}">
                                            </span>
                                            <div class="media-body">
                                                <p class="noti-details"><span class="noti-title">Lex Murphy</span>
                                                    requested access to <span class="noti-title">UNIX directory
                                                        tree hierarchy</span></p>
                                                <div class="notification-btn">
                                                    <span class="btn btn-primary">Accept</span>
                                                    <span class="btn btn-outline-primary">Reject</span>
                                                </div>
                                                <p class="noti-time"><span class="notification-time">Today at 9:42
                                                        AM</span></p>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <li class="notification-message">
                                    <a href="{{ url('profile') }}">
                                        <div class="d-flex">
                                            <span class="avatar avatar-md active">
                                                <img class="avatar-img rounded-circle" alt="avatar-img"
                                                    src="{{ URL::asset('/assets/img/profiles/avatar-10.jpg') }}">
                                            </span>
                                            <div class="media-body">
                                                <p class="noti-details"><span class="noti-title">Ray Arnold</span>
                                                    left 6 comments <span class="noti-title">on Isla Nublar SOC2
                                                        compliance report</span></p>
                                                <p class="noti-time"><span class="notification-time">Yesterday at
                                                        11:42 PM</span></p>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <li class="notification-message">
                                    <a href="{{ url('profile') }}">
                                        <div class="d-flex">
                                            <span class="avatar avatar-md">
                                                <img class="avatar-img rounded-circle" alt="avatar-img"
                                                    src="{{ URL::asset('/assets/img/profiles/avatar-13.jpg') }}">
                                            </span>
                                            <div class="media-body">
                                                <p class="noti-details"><span class="noti-title">Dennis
                                                        Nedry</span> commented on <span class="noti-title"> Isla
                                                        Nublar SOC2 compliance report</span></p>
                                                <blockquote>
                                                    “Oh, I finished de-bugging the phones, but the system's
                                                    compiling for eighteen minutes, or twenty. So, some minor
                                                    systems may go on and off for a while.”
                                                </blockquote>
                                                <p class="noti-time"><span class="notification-time">Yesterday at
                                                        5:42 PM</span></p>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <li class="notification-message">
                                    <a href="{{ url('profile') }}">
                                        <div class="d-flex">
                                            <span class="avatar avatar-md">
                                                <img class="avatar-img rounded-circle" alt="avatar-img"
                                                    src="{{ URL::asset('/assets/img/profiles/avatar-05.jpg') }}">
                                            </span>
                                            <div class="media-body">
                                                <p class="noti-details"><span class="noti-title">John
                                                        Hammond</span> created <span class="noti-title">Isla Nublar
                                                        SOC2 compliance report</span></p>
                                                <p class="noti-time"><span class="notification-time">Last
                                                        Wednesday at 11:15 AM</span></p>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="topnav-dropdown-footer">
                            <a href="#">Clear All</a>
                        </div>
                    </div>
                </li>
                <li class="nav-item  has-arrow dropdown-heads ">
                    <a href="javascript:void(0);" class="win-maximize">
                        <i class="fe fe-maximize"></i>
                    </a>
                </li>
                <!-- User Menu -->
                <li class="nav-item dropdown">
                    <a href="javascript:void(0)" class="user-link  nav-link" data-bs-toggle="dropdown">
                        <span class="user-img">
                            <img src="{{ URL::asset('/assets/img/profiles/avatar-07.jpg') }}" alt="img"
                                class="profilesidebar">
                            <span class="animate-circle"></span>
                        </span>
                        <span class="user-content">
                            <span class="user-details">Admin</span>
                            <span class="user-name">John Smith</span>
                        </span>
                    </a>
                    <div class="dropdown-menu menu-drop-user">
                        <div class="profilemenu">
                            <div class="subscription-menu">
                                <ul>
                                    <li>
                                        <a class="dropdown-item" href="{{ url('profile') }}">Profile</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ url('settings') }}">Settings</a>
                                    </li>
                                </ul>
                            </div>
                            <div class="subscription-logout">
                                <ul>
                                    <li class="pb-0">
                                        <a class="dropdown-item" href="{{ url('login') }}">Log Out</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </li>
                <!-- /User Menu -->

            </ul>
            <!-- /Header Menu -->
        </div>

    </div>
    <!-- /Header -->
@endif

@if (Route::is(['index-four']))
    <!-- Header -->
    <div class="header header-three">

        <!-- Logo -->
        <div class="header-left header-left-three">
            <a href="{{ url('/') }}" class="logo">
                <img src="{{ URL::asset('/assets/img/logo-small.png') }}" alt="Logo">
            </a>
            <a href="{{ url('/') }}" class="logo logo-small">
                <img src="{{ URL::asset('/assets/img/logo-small.png') }}" alt="Logo" width="30"
                    height="30">
            </a>
        </div>
        <!-- /Logo -->

        <!-- Search -->
        <div class="top-nav-search top-nav-search-five">
            <form>
                <input type="text" class="form-control" placeholder="Search here">
                <button class="btn" type="submit"><img src="{{ URL::asset('/assets/img/icons/search.svg') }}"
                        alt="img"></button>
            </form>
        </div>
        <!-- /Search -->

        <!-- Mobile Menu Toggle -->
        <a class="mobile_btn" id="mobile_btn">
            <i class="fas fa-bars"></i>
        </a>
        <!-- /Mobile Menu Toggle -->

        <!-- Header Menu -->
        <ul class="nav nav-tabs user-menu">
            <!-- Flag -->
            <li class="nav-item dropdown has-arrow flag-nav">
                <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button">
                    <img src="{{ URL::asset('/assets/img/flags/us1.png') }}" alt="flag"><span>English</span>
                </a>
                <div class="dropdown-menu dropdown-menu-end">
                    <a href="javascript:void(0);" class="dropdown-item">
                        <img src="{{ URL::asset('/assets/img/flags/us.png') }}" alt="flag"><span>English</span>
                    </a>
                    <a href="javascript:void(0);" class="dropdown-item">
                        <img src="{{ URL::asset('/assets/img/flags/fr.png') }}" alt="flag"><span>French</span>
                    </a>
                    <a href="javascript:void(0);" class="dropdown-item">
                        <img src="{{ URL::asset('/assets/img/flags/es.png') }}" alt="flag"><span>Spanish</span>
                    </a>
                    <a href="javascript:void(0);" class="dropdown-item">
                        <img src="{{ URL::asset('/assets/img/flags/de.png') }}" alt="flag"><span>German</span>
                    </a>
                </div>
            </li>
            <!-- /Flag -->


            <li class="nav-item  has-arrow dropdown-heads ">
                <a href="javascript:void(0);" class="toggle-switch">
                    <i class="fe fe-moon"></i>
                </a>
            </li>
            <li class="nav-item dropdown  flag-nav dropdown-heads">
                <a class="nav-link" data-bs-toggle="dropdown" href="#" role="button">
                    <i class="fe fe-bell"></i> <span class="badge rounded-pill"></span>
                </a>
                <div class="dropdown-menu notifications">
                    <div class="topnav-dropdown-header">
                        <div class="notification-title">Notifications <a href="{{ url('notifications') }}">View
                                all</a></div>
                        <a href="javascript:void(0)" class="clear-noti d-flex align-items-center">Mark all as read <i
                                class="fe fe-check-circle"></i></a>
                    </div>
                    <div class="noti-content">
                        <ul class="notification-list">
                            <li class="notification-message">
                                <a href="{{ url('profile') }}">
                                    <div class="d-flex">
                                        <span class="avatar avatar-md active">
                                            <img class="avatar-img rounded-circle" alt="avatar-img"
                                                src="{{ URL::asset('/assets/img/profiles/avatar-02.jpg') }}">
                                        </span>
                                        <div class="media-body">
                                            <p class="noti-details"><span class="noti-title">Lex Murphy</span>
                                                requested access to <span class="noti-title">UNIX directory tree
                                                    hierarchy</span></p>
                                            <div class="notification-btn">
                                                <span class="btn btn-primary">Accept</span>
                                                <span class="btn btn-outline-primary">Reject</span>
                                            </div>
                                            <p class="noti-time"><span class="notification-time">Today at 9:42
                                                    AM</span></p>
                                        </div>
                                    </div>
                                </a>
                            </li>
                            <li class="notification-message">
                                <a href="{{ url('profile') }}">
                                    <div class="d-flex">
                                        <span class="avatar avatar-md active">
                                            <img class="avatar-img rounded-circle" alt="avatar-img"
                                                src="{{ URL::asset('/assets/img/profiles/avatar-10.jpg') }}">
                                        </span>
                                        <div class="media-body">
                                            <p class="noti-details"><span class="noti-title">Ray Arnold</span> left 6
                                                comments <span class="noti-title">on Isla Nublar SOC2 compliance
                                                    report</span></p>
                                            <p class="noti-time"><span class="notification-time">Yesterday at 11:42
                                                    PM</span></p>
                                        </div>
                                    </div>
                                </a>
                            </li>
                            <li class="notification-message">
                                <a href="{{ url('profile') }}">
                                    <div class="d-flex">
                                        <span class="avatar avatar-md">
                                            <img class="avatar-img rounded-circle" alt="avatar-img"
                                                src="{{ URL::asset('/assets/img/profiles/avatar-13.jpg') }}">
                                        </span>
                                        <div class="media-body">
                                            <p class="noti-details"><span class="noti-title">Dennis Nedry</span>
                                                commented on <span class="noti-title"> Isla Nublar SOC2 compliance
                                                    report</span></p>
                                            <blockquote>
                                                “Oh, I finished de-bugging the phones, but the system's compiling for
                                                eighteen minutes, or twenty. So, some minor systems may go on and off
                                                for a while.”
                                            </blockquote>
                                            <p class="noti-time"><span class="notification-time">Yesterday at 5:42
                                                    PM</span></p>
                                        </div>
                                    </div>
                                </a>
                            </li>
                            <li class="notification-message">
                                <a href="{{ url('profile') }}">
                                    <div class="d-flex">
                                        <span class="avatar avatar-md">
                                            <img class="avatar-img rounded-circle" alt="avatar-img"
                                                src="{{ URL::asset('/assets/img/profiles/avatar-05.jpg') }}">
                                        </span>
                                        <div class="media-body">
                                            <p class="noti-details"><span class="noti-title">John Hammond</span>
                                                created <span class="noti-title">Isla Nublar SOC2 compliance
                                                    report</span></p>
                                            <p class="noti-time"><span class="notification-time">Last Wednesday at
                                                    11:15 AM</span></p>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="topnav-dropdown-footer">
                        <a href="#">Clear All</a>
                    </div>
                </div>
            </li>
            <li class="nav-item  has-arrow dropdown-heads ">
                <a href="javascript:void(0);" class="win-maximize">
                    <i class="fe fe-maximize"></i>
                </a>
            </li>
            <!-- User Menu -->
            <li class="nav-item dropdown">
                <a href="javascript:void(0)" class="user-link  nav-link" data-bs-toggle="dropdown">
                    <span class="user-img">
                        <img src="{{ URL::asset('/assets/img/profiles/avatar-07.jpg') }}" alt="img"
                            class="profilesidebar">
                        <span class="animate-circle"></span>
                    </span>
                    <span class="user-content">
                        <span class="user-details">Admin</span>
                        <span class="user-name">John Smith</span>
                    </span>
                </a>
                <div class="dropdown-menu menu-drop-user">
                    <div class="profilemenu">
                        <div class="subscription-menu">
                            <ul>
                                <li>
                                    <a class="dropdown-item" href="{{ url('profile') }}">Profile</a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ url('settings') }}">Settings</a>
                                </li>
                            </ul>
                        </div>
                        <div class="subscription-logout">
                            <ul>
                                <li class="pb-0">
                                    <a class="dropdown-item" href="{{ url('login') }}">Log Out</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </li>
            <!-- /User Menu -->

        </ul>
        <!-- /Header Menu -->

    </div>
    <!-- /Header -->
@endif
@if (Route::is(['index-five']))
    <!-- Header -->
    <div class="header header-four">
        <div class="container-fluid">

            <!-- Logo -->
            <div class="header-left header-left-four">
                <a href="{{ url('/') }}" class="logo">
                    <img src="{{ URL::asset('/assets/img/logo-white.png') }}" alt="Logo">
                </a>
                <a href="{{ url('/') }}" class="dark-logo">
                    <img src="{{ URL::asset('/assets/img/logo.png') }}" alt="Logo">
                </a>
                <a href="{{ url('/') }}" class="logo logo-small">
                    <img src="{{ URL::asset('/assets/img/logo-small.png') }}" alt="Logo" width="30"
                        height="30">
                </a>
            </div>
            <!-- /Logo -->


            <!-- Mobile Menu Toggle -->
            <a class="mobile_btn mobile_btn-four" id="mobile_btn">
                <i class="fas fa-bars"></i>
            </a>
            <!-- /Mobile Menu Toggle -->

            <!-- Header Menu List -->
            <div class="sidebar sidebar-five">
                <div id="sidebar-menu" class="sidebar-menu sidebar-menu-five">
                    <ul class="nav">
                        <li class="submenu submenu-five nav-item dropdown">
                            <a href="#" class="dropdown-toggle"><i class="fe fe-home"></i> <span>Main </span>
                                <span class="menu-arrow"></span></a>
                            <ul class="header-four dropdown-menu dropdown-menu-five dropdown-menu-right">
                                <!-- Main -->
                                <li class="submenu">
                                    <a href="#"><i class="fe fe-home"></i> <span> Dashboard</span> <span
                                            class="menu-arrow"></span></a>
                                    <ul>
                                        <li><a class="{{ Request::is('index', '/') ? 'active' : '' }}"
                                                href="{{ url('/') }}"><span> Admin
                                                    Dashboard</span></a></li>
                                    </ul>
                                </li>

                                <li class="submenu {{ Request::is('chat', 'calendar', 'inbox') ? 'active' : '' }}">
                                    <a href="#"><i class="fe fe-grid"></i> <span> Applications</span> <span
                                            class="menu-arrow"></span></a>
                                    <ul>
                                        <li><a href="{{ url('chat') }}"
                                                class="{{ Request::is('chat') ? 'active' : '' }}"><span>
                                                    Chat</span></a></li>
                                        <li><a href="{{ url('calendar') }}"
                                                class="{{ Request::is('calendar') ? 'active' : '' }}"><span>
                                                    Calendar</span></a></li>
                                        <li><a href="{{ url('inbox') }}"
                                                class="{{ Request::is('inbox') ? 'active' : '' }}"><span>
                                                    Email</span></a></li>
                                    </ul>
                                </li>
                                <!-- /Main -->

                                <!-- Customers -->
                                <li class="submenu">
                                    <a href="#"><i class="fe fe-users"></i> <span> Customers</span> <span
                                            class="menu-arrow"></span></a>
                                    <ul>
                                        <li>
                                            <a href="{{ url('customers') }}"><span>Customers</span></a>
                                        </li>
                                        <li>
                                            <a href="{{ url('customer-details') }}"><span>Customer Details</span></a>
                                        </li>
                                        <li>
                                            <a href="{{ url('vendors') }}"><span>Vendors</span></a>
                                        </li>
                                    </ul>
                                </li>
                                <!-- /Customers -->

                                <!-- Inventory -->
                                <li class="submenu">
                                    <a href="#"><i class="fe fe-package"></i> <span> Products / Services</span>
                                        <span class="menu-arrow"></span></a>
                                    <ul>
                                        <li><a href="{{ url('product-list') }}"><span> Product List</span></a></li>
                                        <li><a href="{{ url('category') }}"><span> Category</span></a></li>

                                        <li><a href="{{ url('units') }}"><span> Units</span></a></li>
                                    </ul>
                                </li>
                                <li>
                                    <a href="{{ url('inventory') }}"><i class="fe fe-user"></i>
                                        <span>Inventory</span></a>
                                </li>
                                <!-- /Inventory -->

                                <!-- Sales -->
                                <li class="submenu">
                                    <a href="#"><i class="fe fe-file"></i> <span> Sales</span> <span
                                            class="menu-arrow"></span></a>
                                    <ul>
                                        <li class="submenu">
                                            <a href="{{ url('invoices') }}"><span>Invoices</span><span
                                                    class="menu-arrow"></span></a>
                                            <ul>
                                                <li><a href="{{ url('invoices') }}">Invoices List</a></li>
                                                <li><a href="{{ url('invoice-template') }}">Invoice-Template</a></li>


                                            </ul>
                                        </li>
                                        <li>
                                            <a href="{{ url('recurring-invoices') }}"><span>Recurring
                                                    Invoices</span></a>
                                        </li>
                                        <li>
                                            <a href="{{ url('credit-notes') }}"><span>Credit Notes</span></a>
                                        </li>
                                    </ul>
                                </li>
                                <!-- /Sales -->

                                <!-- Purchases -->
                                <li class="submenu">
                                    <a href="#"><i class="fe fe-shopping-cart"></i> <span> Purchases</span>
                                        <span class="menu-arrow"></span></a>
                                    <ul>
                                        <li>
                                            <a href="{{ url('purchases') }}"><span>Purchases</span></a>
                                        </li>
                                        <li>
                                            <a href="{{ url('purchase-orders') }}"><span>Purchase Orders</span></a>
                                        </li>
                                        <li>
                                            <a href="{{ url('debit-notes') }}"><span>Debit Notes</span></a>
                                        </li>
                                    </ul>
                                </li>
                                <!-- /Purchases -->

                                <!-- Finance & Accounts -->
                                <li class="submenu">
                                    <a href="#"><i class="fe fe-file-plus"></i> <span> Finance & Accounts</span>
                                        <span class="menu-arrow"></span></a>
                                    <ul>
                                        <li>
                                            <a href="{{ url('expenses') }}"><span>Expenses</span></a>
                                        </li>
                                        <li>
                                            <a href="{{ url('payments') }}"><span>Payments</span></a>
                                        </li>
                                    </ul>
                                </li>
                                <!-- /Finance & Accounts -->

                                <!-- Quotations -->
                                <li class="submenu">
                                    <a href="#"><i class="fe fe-clipboard"></i> <span> Quotations</span> <span
                                            class="menu-arrow"></span></a>
                                    <ul>
                                        <li>
                                            <a href="{{ url('quotations') }}"> <span>Quotations</span></a>
                                        </li>
                                        <li>
                                            <a href="{{ url('delivery-challans') }}"><span>Delivery
                                                    Challans</span></a>
                                        </li>
                                    </ul>
                                </li>
                                <!-- /Quotations -->

                                <!-- Reports -->
                                <li class="submenu">
                                    <a href="#"><i class="fe fe-file-text"></i> <span> Reports</span> <span
                                            class="menu-arrow"></span></a>
                                    <ul>
                                        <li>
                                            <a href="{{ url('quotations') }}"> <span>Quotations</span></a>
                                        </li>
                                        <li>
                                            <a href="{{ url('payment-summary') }}"> <span>Payment Summary</span></a>
                                        </li>
                                    </ul>
                                </li>
                                <!-- /Reports -->

                                <!-- User Management -->
                                <li class="submenu">
                                    <a href="#"><i class="fe fe-book"></i> <span> Membership</span> <span
                                            class="menu-arrow"></span></a>
                                    <ul>
                                        <li><a href="{{ url('membership-plans') }}"><span> Membership
                                                    Plans</span></a></li>
                                        <li><a href="{{ url('membership-addons') }}"><span> Membership
                                                    Addons</span></a></li>
                                        <li><a href="{{ url('subscribers') }}"><span> Subscribers</span></a></li>
                                        <li><a href="{{ url('transactions') }}"><span> Transactions</span></a></li>
                                    </ul>
                                </li>
                                <!-- /User Management -->

                                <!-- Content (CMS) -->
                                <li class="submenu">
                                    <a href="#"><i class="fe fe-folder"></i> <span> Content (CMS)</span> <span
                                            class="menu-arrow"></span></a>
                                    <ul>
                                        <li>
                                            <a href="{{ url('pages') }}"><i class="fe fe-folder"></i>
                                                <span>Pages</span></a>
                                        </li>
                                        <li class="submenu">
                                            <a href="#"><i class="fe fe-book"></i> <span> Blog</span> <span
                                                    class="menu-arrow"></span></a>
                                            <ul>
                                                <li><a href="{{ url('all-blogs') }}">All Blogs</a></li>
                                                <li><a href="{{ url('categories') }}">Categories</a></li>
                                                <li><a href="{{ url('blog-comments') }}">Blog Comments</a></li>
                                            </ul>
                                        </li>
                                        <li class="submenu">
                                            <a href="#"><i class="fe fe-map-pin"></i> <span> Location</span>
                                                <span class="menu-arrow"></span></a>
                                            <ul>
                                                <li><a href="{{ url('countries') }}">Countries</a></li>
                                                <li><a href="{{ url('states') }}">States</a></li>
                                                <li><a href="{{ url('cities') }}">Cities</a></li>
                                            </ul>
                                        </li>
                                        <li>
                                            <a href="{{ url('testimonials') }}"><i class="fe fe-message-square"></i>
                                                <span>Testimonials</span></a>
                                        </li>
                                        <li>
                                            <a href="{{ url('faq') }}"><i class="fe fe-alert-circle"></i>
                                                <span>FAQ</span></a>
                                        </li>
                                    </ul>
                                </li>
                                <!-- /Content (CMS) -->

                                <!-- Support -->
                                <li class="submenu">
                                    <a href="#"><i class="fe fe-printer"></i> <span> Support</span> <span
                                            class="menu-arrow"></span></a>
                                    <ul>
                                        <li>
                                            <a href="{{ url('contact-messages') }}"><span>Contact Messages</span></a>
                                        </li>
                                        <li class="submenu">
                                            <a href="#"><span> Tickets</span> <span
                                                    class="menu-arrow"></span></a>
                                            <ul>
                                                <li><a href="{{ url('tickets') }}"><span> Tickets</span></a></li>
                                                <li><a href="{{ url('tickets-list') }}"><span> Tickets
                                                            List</span></a></li>
                                                <li><a href="{{ url('tickets-kanban') }}"><span> Tickets
                                                            Kanban</span></a></li>
                                                <li><a href="{{ url('ticket-details') }}"><span> Ticket
                                                            Overview</span></a>
                                                </li>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                                <!-- /Support -->

                                <!-- Authentication -->
                                <li class="submenu">
                                    <a href="#"><i class="fe fe-lock"></i> <span> Authentication </span> <span
                                            class="menu-arrow"></span></a>
                                    <ul>
                                        <li><a href="{{ url('login') }}"> <span> Login </span></a></li>
                                        <li><a href="{{ url('register') }}"><span> Register </span></a></li>
                                        <li><a href="{{ url('forgot-password') }}"> <span>Forgot Password </span></a>
                                        </li>
                                        <li><a href="{{ url('lock-screen') }}"> <span>Lock Screen </span></a></li>
                                    </ul>
                                </li>
                                <!-- /Authentication -->

                                <!-- Settings -->
                                <li class="submenu">
                                    <a href="#"><i class="fe fe-settings"></i> <span> Settings </span> <span
                                            class="menu-arrow"></span></a>
                                    <ul>
                                        <li>
                                            <a href="{{ url('settings') }}"><span>Settings</span></a>
                                        </li>
                                        <li>
                                            <a href="{{ url('login') }}"><span>Logout</span></a>
                                        </li>
                                    </ul>
                                </li>
                                <!-- /Settings -->
                            </ul>
                        </li>

                        <li class="submenu submenu-five nav-item dropdown">
                            <a href="#" class="dropdown-toggle"><i class="fe fe-file"></i> <span>Pages </span>
                                <span class="menu-arrow"></span></a>
                            <ul class="header-four dropdown-menu dropdown-menu-five dropdown-menu-right">
                                <li>
                                    <a href="{{ url('profile') }}"><i class="fe fe-user"></i>
                                        <span>Profile</span></a>
                                </li>
                                <li>
                                    <a href="{{ url('error-404') }}"><i class="fe fe-x-square"></i> <span>Error
                                            Pages</span></a>
                                </li>
                                <li>
                                    <a href="{{ url('blank-page') }}"><i class="fe fe-file"></i> <span>Blank
                                            Page</span></a>
                                </li>
                                <li>
                                    <a href="{{ url('maps-vector') }}"><i class="fe fe-image"></i> <span>Vector
                                            Maps</span></a>
                                </li>
                            </ul>
                        </li>
                        <li class="submenu submenu-five nav-item dropdown">
                            <a href="#" class="dropdown-toggle"><i class="fe fe-layers"></i> <span>UI Interface
                                </span> <span class="menu-arrow"></span></a>
                            <!-- UI Interface -->
                            <ul class="header-four dropdown-menu dropdown-menu-five dropdown-menu-right">
                                <li class="submenu">
                                    <a href="#"><i class="fe fe-pocket"></i> <span>Base UI </span> <span
                                            class="menu-arrow"></span></a>
                                    <ul>
                                        <li><a href="{{ url('alerts') }}">Alerts</a></li>
                                        <li><a href="{{ url('accordions') }}">Accordions</a></li>
                                        <li><a href="{{ url('avatar') }}">Avatar</a></li>
                                        <li><a href="{{ url('badges') }}">Badges</a></li>
                                        <li><a href="{{ url('buttons') }}">Buttons</a></li>
                                        <li><a href="{{ url('buttongroup') }}">Button Group</a></li>
                                        <li><a href="{{ url('breadcrumbs') }}">Breadcrumb</a></li>
                                        <li><a href="{{ url('cards') }}">Cards</a></li>
                                        <li><a href="{{ url('carousel') }}">Carousel</a></li>
                                        <li><a href="{{ url('dropdowns') }}">Dropdowns</a></li>
                                        <li><a href="{{ url('grid') }}">Grid</a></li>
                                        <li><a href="{{ url('images') }}">Images</a></li>
                                        <li><a href="{{ url('lightbox') }}">Lightbox</a></li>
                                        <li><a href="{{ url('media') }}">Media</a></li>
                                        <li><a href="{{ url('modal') }}">Modals</a></li>
                                        <li><a href="{{ url('offcanvas') }}">Offcanvas</a></li>
                                        <li><a href="{{ url('pagination') }}">Pagination</a></li>
                                        <li><a href="{{ url('popover') }}">Popover</a></li>
                                        <li><a href="{{ url('progress') }}">Progress Bars</a></li>
                                        <li><a href="{{ url('placeholders') }}">Placeholders</a></li>
                                        <li><a href="{{ url('rangeslider') }}">Range Slider</a></li>
                                        <li><a href="{{ url('spinners') }}">Spinner</a></li>
                                        <li><a href="{{ url('sweetalerts') }}">Sweet Alerts</a></li>
                                        <li><a href="{{ url('tab') }}">Tabs</a></li>
                                        <li><a href="{{ url('toastr') }}">Toasts</a></li>
                                        <li><a href="{{ url('tooltip') }}">Tooltip</a></li>
                                        <li><a href="{{ url('typography') }}">Typography</a></li>
                                        <li><a href="{{ url('video') }}">Video</a></li>
                                    </ul>
                                </li>
                                <li class="submenu">
                                    <a href="#"><i class="fe fe-box"></i> <span>Elements </span> <span
                                            class="menu-arrow"></span></a>
                                    <ul>
                                        <li><a href="{{ url('ribbon') }}">Ribbon</a></li>
                                        <li><a href="{{ url('clipboard') }}">Clipboard</a></li>
                                        <li><a href="{{ url('drag-drop') }}">Drag & Drop</a></li>
                                        <li><a href="{{ url('rating') }}">Rating</a></li>
                                        <li><a href="{{ url('text-editor') }}">Text Editor</a></li>
                                        <li><a href="{{ url('counter') }}">Counter</a></li>
                                        <li><a href="{{ url('scrollbar') }}">Scrollbar</a></li>
                                        <li><a href="{{ url('notification') }}">Notification</a></li>
                                        <li><a href="{{ url('stickynote') }}">Sticky Note</a></li>
                                        <li><a href="{{ url('timeline') }}">Timeline</a></li>
                                        <li><a href="{{ url('horizontal-timeline') }}">Horizontal Timeline</a></li>
                                        <li><a href="{{ url('form-wizard') }}">Form Wizard</a></li>
                                    </ul>
                                </li>
                                <li class="submenu">
                                    <a href="#"><i class="fe fe-bar-chart"></i> <span> Charts </span> <span
                                            class="menu-arrow"></span></a>
                                    <ul>
                                        <li><a href="{{ url('chart-apex') }}">Apex Charts</a></li>
                                        <li><a href="{{ url('chart-js') }}">Chart Js</a></li>
                                        <li><a href="{{ url('chart-morris') }}">Morris Charts</a></li>
                                        <li><a href="{{ url('chart-flot') }}">Flot Charts</a></li>
                                        <li><a href="{{ url('chart-peity') }}">Peity Charts</a></li>
                                        <li><a href="{{ url('chart-c3') }}">C3 Charts</a></li>
                                    </ul>
                                </li>
                                <li class="submenu">
                                    <a href="#"><i class="fe fe-award"></i> <span> Icons </span> <span
                                            class="menu-arrow"></span></a>
                                    <ul>
                                        <li><a href="{{ url('icon-fontawesome') }}">Fontawesome Icons</a></li>
                                        <li><a href="{{ url('icon-feather') }}">Feather Icons</a></li>
                                        <li><a href="{{ url('icon-ionic') }}">Ionic Icons</a></li>
                                        <li><a href="{{ url('icon-material') }}">Material Icons</a></li>
                                        <li><a href="{{ url('icon-pe7') }}">Pe7 Icons</a></li>
                                        <li><a href="{{ url('icon-simpleline') }}">Simpleline Icons</a></li>
                                        <li><a href="{{ url('icon-themify') }}">Themify Icons</a></li>
                                        <li><a href="{{ url('icon-weather') }}">Weather Icons</a></li>
                                        <li><a href="{{ url('icon-typicon') }}">Typicon Icons</a></li>
                                        <li><a href="{{ url('icon-flag') }}">Flag Icons</a></li>
                                    </ul>
                                </li>
                                <li class="submenu">
                                    <a href="#"><i class="fe fe-sidebar"></i> <span> Forms </span> <span
                                            class="menu-arrow"></span></a>
                                    <ul>
                                        <li><a href="{{ url('form-basic-inputs') }}">Basic Inputs </a></li>
                                        <li><a href="{{ url('form-input-groups') }}">Input Groups </a></li>
                                        <li><a href="{{ url('form-horizontal') }}">Horizontal Form </a></li>
                                        <li><a href="{{ url('form-vertical') }}"> Vertical Form </a></li>
                                        <li><a href="{{ url('form-mask') }}">Form Mask </a></li>
                                        <li><a href="{{ url('form-validation') }}">Form Validation </a></li>
                                        <li><a href="{{ url('form-select2') }}">Form Select2 </a></li>
                                        <li><a href="{{ url('form-fileupload') }}">File Upload </a></li>
                                    </ul>
                                </li>
                                <li class="submenu">
                                    <a href="#"><i class="fe fe-layout"></i> <span> Tables </span> <span
                                            class="menu-arrow"></span></a>
                                    <ul>
                                        <li><a href="{{ url('tables-basic') }}">Basic Tables </a></li>
                                        <li><a href="{{ url('data-tables') }}">Data Table </a></li>
                                    </ul>
                                </li>
                            </ul>
                            <!-- /UI Interface -->
                        </li>
                    </ul>
                </div>
            </div>
            <!-- /Header Menu List -->

            <!-- Header Menu -->
            <ul class="nav nav-tabs user-menu user-menu-four">
                <!-- Flag -->
                <li class="nav-item dropdown has-arrow flag-nav">
                    <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button">
                        <img src="{{ URL::asset('/assets/img/flags/us1.png') }}" alt="flag"><span>English</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <a href="javascript:void(0);" class="dropdown-item">
                            <img src="{{ URL::asset('/assets/img/flags/us.png') }}"
                                alt="flag"><span>English</span>
                        </a>
                        <a href="javascript:void(0);" class="dropdown-item">
                            <img src="{{ URL::asset('/assets/img/flags/fr.png') }}"
                                alt="flag"><span>French</span>
                        </a>
                        <a href="javascript:void(0);" class="dropdown-item">
                            <img src="{{ URL::asset('/assets/img/flags/es.png') }}"
                                alt="flag"><span>Spanish</span>
                        </a>
                        <a href="javascript:void(0);" class="dropdown-item">
                            <img src="{{ URL::asset('/assets/img/flags/de.png') }}"
                                alt="flag"><span>German</span>
                        </a>
                    </div>
                </li>
                <!-- /Flag -->


                <li class="nav-item  has-arrow dropdown-heads ">
                    <a href="javascript:void(0);" class="toggle-switch">
                        <i class="fe fe-moon"></i>
                    </a>
                </li>
                <li class="nav-item dropdown  flag-nav dropdown-heads">
                    <a class="nav-link" data-bs-toggle="dropdown" href="#" role="button">
                        <i class="fe fe-bell"></i> <span class="badge rounded-pill"></span>
                    </a>
                    <div class="dropdown-menu notifications">
                        <div class="topnav-dropdown-header">
                            <div class="notification-title">Notifications <a href="{{ url('notifications') }}">View
                                    all</a>
                            </div>
                            <a href="javascript:void(0)" class="clear-noti d-flex align-items-center">Mark all as read
                                <i class="fe fe-check-circle"></i></a>
                        </div>
                        <div class="noti-content">
                            <ul class="notification-list">
                                <li class="notification-message">
                                    <a href="{{ url('profile') }}">
                                        <div class="d-flex">
                                            <span class="avatar avatar-md active">
                                                <img class="avatar-img rounded-circle" alt="avatar-img"
                                                    src="{{ URL::asset('/assets/img/profiles/avatar-02.jpg') }}">
                                            </span>
                                            <div class="media-body">
                                                <p class="noti-details"><span class="noti-title">Lex Murphy</span>
                                                    requested access to <span class="noti-title">UNIX directory tree
                                                        hierarchy</span></p>
                                                <div class="notification-btn">
                                                    <span class="btn btn-primary">Accept</span>
                                                    <span class="btn btn-outline-primary">Reject</span>
                                                </div>
                                                <p class="noti-time"><span class="notification-time">Today at 9:42
                                                        AM</span></p>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <li class="notification-message">
                                    <a href="{{ url('profile') }}">
                                        <div class="d-flex">
                                            <span class="avatar avatar-md active">
                                                <img class="avatar-img rounded-circle" alt="avatar-img"
                                                    src="{{ URL::asset('/assets/img/profiles/avatar-10.jpg') }}">
                                            </span>
                                            <div class="media-body">
                                                <p class="noti-details"><span class="noti-title">Ray Arnold</span>
                                                    left 6 comments <span class="noti-title">on Isla Nublar SOC2
                                                        compliance report</span></p>
                                                <p class="noti-time"><span class="notification-time">Yesterday at
                                                        11:42 PM</span></p>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <li class="notification-message">
                                    <a href="{{ url('profile') }}">
                                        <div class="d-flex">
                                            <span class="avatar avatar-md">
                                                <img class="avatar-img rounded-circle" alt="avatar-img"
                                                    src="{{ URL::asset('/assets/img/profiles/avatar-13.jpg') }}">
                                            </span>
                                            <div class="media-body">
                                                <p class="noti-details"><span class="noti-title">Dennis Nedry</span>
                                                    commented on <span class="noti-title"> Isla Nublar SOC2 compliance
                                                        report</span></p>
                                                <blockquote>
                                                    “Oh, I finished de-bugging the phones, but the system's compiling
                                                    for eighteen minutes, or twenty. So, some minor systems may go on
                                                    and off for a while.”
                                                </blockquote>
                                                <p class="noti-time"><span class="notification-time">Yesterday at 5:42
                                                        PM</span></p>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <li class="notification-message">
                                    <a href="{{ url('profile') }}">
                                        <div class="d-flex">
                                            <span class="avatar avatar-md">
                                                <img class="avatar-img rounded-circle" alt="avatar-img"
                                                    src="{{ URL::asset('/assets/img/profiles/avatar-05.jpg') }}">
                                            </span>
                                            <div class="media-body">
                                                <p class="noti-details"><span class="noti-title">John Hammond</span>
                                                    created <span class="noti-title">Isla Nublar SOC2 compliance
                                                        report</span></p>
                                                <p class="noti-time"><span class="notification-time">Last Wednesday at
                                                        11:15 AM</span></p>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="topnav-dropdown-footer">
                            <a href="#">Clear All</a>
                        </div>
                    </div>
                </li>
                <li class="nav-item  has-arrow dropdown-heads ">
                    <a href="javascript:void(0);" class="win-maximize">
                        <i class="fe fe-maximize"></i>
                    </a>
                </li>
                <!-- User Menu -->
                <li class="nav-item dropdown">
                    <a href="javascript:void(0)" class="user-link  nav-link" data-bs-toggle="dropdown">
                        <span class="user-img">
                            <img src="{{ URL::asset('/assets/img/profiles/avatar-07.jpg') }}" alt="img"
                                class="profilesidebar">
                            <span class="animate-circle"></span>
                        </span>
                        <span class="user-content">
                            <span class="user-details">Admin</span>
                            <span class="user-name">John Smith</span>
                        </span>
                    </a>
                    <div class="dropdown-menu menu-drop-user">
                        <div class="profilemenu">
                            <div class="subscription-menu">
                                <ul>
                                    <li>
                                        <a class="dropdown-item" href="{{ url('profile') }}">Profile</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ url('settings') }}">Settings</a>
                                    </li>
                                </ul>
                            </div>
                            <div class="subscription-logout">
                                <ul>
                                    <li class="pb-0">
                                        <a class="dropdown-item" href="{{ url('login') }}">Log Out</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </li>
                <!-- /User Menu -->

            </ul>
            <!-- /Header Menu -->
        </div>

    </div>
    <!-- /Header -->
@endif
