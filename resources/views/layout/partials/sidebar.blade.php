<!-- Sidebar -->
@if (!Route::is(['index-two', 'index-three', 'index-four', 'index-five']))
    <div class="sidebar" id="sidebar">
        <div class="sidebar-inner slimscroll">
            <div id="sidebar-menu" class="sidebar-menu">
                <nav class="greedys sidebar-horizantal">
                    <ul class="list-inline-item list-unstyled links">
                        <li class="menu-title"><span>Main</span></li>
                        <li class="submenu">
                            <a href="#"><i class="fe fe-home"></i> <span> Dashboard</span> <span
                                    class="menu-arrow"></span></a>
                            <ul>
                                <li><a href="{{ url('/') }}"
                                        class="{{ Request::is('index', '/') ? 'active' : '' }}">Admin Dashboard</a></li>
                            </ul>
                        </li>
                    </ul>
                </nav>        
            </div>
        </div>
    </div>
    <!-- /Sidebar -->
@endif
