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
@if (!Route::is(['index-three', 'index-four', 'index-five']))
    @if (!Route::is(['index-two']))
        <div class="header header-one" style="background-color: #102c5c">
    @endif
    @if (Route::is(['index-two']))
        <div class="header header-two">
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

    <!-- Search -->
    <div class="top-nav-search ps-2">
        <form>
            <input type="text" class="form-control search-expand" placeholder="Search here">
            <button class="btn" type="submit"><img src="{{ URL::asset('/assets/img/icons/search.svg') }}"
                    alt="img"></button>
        </form>
    </div>
    <!-- /Search -->

    <!-- Mobile Menu Toggle -->
    <a class="mobile_btn" id="mobile_btn">
        <i class="fas fa-bars" style="color: white"></i>
    </a>
    <!-- /Mobile Menu Toggle -->

    <!-- Header Menu -->
    <ul class="nav nav-tabs user-menu">
        <li class="nav-item dropdown-heads">
            <a class="nav-link fotoProduksi" href="#" style="width: 2rem; height: 2rem" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Foto Bukti Produksi">
                <i class="fe fe-camera"></i>
            </a>
        </li>
        <li class="nav-item dropdown-heads">
            <a class="nav-link" href="/stockProduct" style="width: 2rem; height: 2rem" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Stok Barang">
                <i class="fe fe-layers"></i>
            </a>
        </li>
        <li class="nav-item dropdown-heads ">
            <a href="/production" class="nav-link" style="width: 2rem; height: 2rem" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Produksi">
                <i class="fa-solid fa-gear"></i>
            </a>
        </li>
        <li class="nav-item dropdown-heads ">
            <a href="/pettyCash" class="nav-link" style="width: 2rem; height: 2rem" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Kas Kecil">
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
                    <span class="user-details" style="color: white">Admin</span>
                    <span class="user-name" style="color: white">{{Session::get('user')["staff_name"]}}</span>
                </span>
            </a>
            <div class="dropdown-menu menu-drop-user">
                <div class="profilemenu">
                    <div class="subscription-menu">
                        <ul>
                            <li>
                                <a class="dropdown-item" href="{{ url('profiles') }}">Profile</a>
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
