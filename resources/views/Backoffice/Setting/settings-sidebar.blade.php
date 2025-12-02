<div class="sidebars settings-sidebar theiaStickySidebar" id="sidebar2">
    <div class="sidebar-inner slimscroll">
        <div id="sidebar-menu5" class="sidebar-menu">
            <ul>
                <li class="submenu-open">
                    <ul>
                        <li class="submenu">
                            <a href="javascript:void(0);"
                                class="{{ Request::is('general-settings', 'security-settings', 'notification', 'connected-apps') ? 'active subdrop' : '' }} "><i
                                    data-feather="settings"></i><span>General Settings</span><span
                                    class="menu-arrow"></span></a>
                            <ul>
                                <li><a href="/admin/company-settings"
                                        class="{{ Request::is('company-settings') ? 'active' : '' }}">Company
                                        Settings </a></li>
                            </ul>
                        </li>
                        
                        <li class="submenu">
                            <a href="javascript:void(0);"
                                class="{{ Request::is('payment-gateway-settings', 'bank-settings-grid', 'bank-settings-list', 'tax-rates', 'currency-settings') ? 'active subdrop' : '' }} "><i
                                    data-feather="credit-card"></i><span>Financial
                                    Settings</span><span class="menu-arrow"></span></a>
                            <ul>
                                <li><a href="/admin/bank-settings-grid"
                                        class="{{ Request::is('bank-settings-grid','bank-settings-list') ? 'active' : '' }}">Bank
                                        Accounts</a></li>
                            </ul>
                        </li>
                        
                    </ul>
                </li>

            </ul>
        </div>
    </div>
</div>
