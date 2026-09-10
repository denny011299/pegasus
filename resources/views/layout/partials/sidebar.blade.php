<!-- Sidebar -->
<style>
    /* =============================================
       SIDEBAR — PREMIUM DARK BLUE THEME
       ============================================= */

    /* Increase specificity to override [data-sidebar=light] body .sidebar from style.css */
    #sidebar.sidebar,
    body.mini-sidebar #sidebar.sidebar,
    body.mini-sidebar.expand-menu #sidebar.sidebar {
        background: #ffffff !important;
        border-right: 1px solid #e2e8f0 !important;
        box-shadow: 2px 0 16px rgba(0, 0, 0, 0.05) !important;
    }
    .sidebar-inner {
        padding-bottom: 32px;
    }
    .sidebar-menu {
        padding: 8px 12px 16px !important;
    }

    /* ── Logo / Brand area ── */
    #sidebar .logo-box {
        background: transparent !important;
        border-bottom: 1px solid rgba(255,255,255,0.07) !important;
        padding: 16px 20px !important;
    }

    /* ── Section Titles ── */
    #sidebar .sidebar-menu .menu-title {
        color: #475569 !important;
        font-size: 10px !important;
        font-weight: 800 !important;
        letter-spacing: 0.12em !important;
        text-transform: uppercase !important;
        padding: 18px 8px 6px !important;
        margin: 0 !important;
        line-height: 1;
    }
    #sidebar .sidebar-menu .menu-title span {
        color: inherit !important;
    }

    /* ── Menu Items ── */
    #sidebar .sidebar-menu li > a {
        display: flex !important;
        align-items: center !important;
        border-radius: 10px !important;
        padding: 10px 12px !important;
        margin-bottom: 2px !important;
        color: #0f172a !important;
        font-size: 13.5px !important;
        font-weight: 600 !important;
        text-decoration: none !important;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        position: relative;
        gap: 10px;
    }

    /* Fix for Mini-Sidebar (Collapsed) Mode */
    body.mini-sidebar:not(.expand-menu) #sidebar .sidebar-menu li > a {
        justify-content: center !important;
        padding: 12px 10px !important;
    }
    body.mini-sidebar:not(.expand-menu) #sidebar .sidebar-menu li > a > i,
    body.mini-sidebar:not(.expand-menu) #sidebar .sidebar-menu li > a > svg {
        margin: 0 !important;
    }

    /* Stronger Active State for Mini-Sidebar */
    body.mini-sidebar:not(.expand-menu) #sidebar .sidebar-menu li > a.active {
        background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%) !important;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4) !important;
        border-radius: 12px !important;
        border: none !important;
    }
    body.mini-sidebar:not(.expand-menu) #sidebar .sidebar-menu li > a.active > i,
    body.mini-sidebar:not(.expand-menu) #sidebar .sidebar-menu li > a.active > svg {
        color: #ffffff !important;
    }

    /* Icons */
    #sidebar .sidebar-menu li > a > i,
    #sidebar .sidebar-menu li > a > svg {
        flex-shrink: 0;
        width: 18px !important;
        min-width: 18px !important;
        height: 18px !important;
        font-size: 15px !important;
        color: #334155 !important;
        text-align: center;
        transition: all 0.2s ease !important;
        margin-right: 0 !important;
    }

    /* Span text */
    #sidebar .sidebar-menu li > a > span {
        flex: 1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Arrow */
    #sidebar .sidebar-menu li > a > .menu-arrow {
        margin-left: auto !important;
        font-size: 10px !important;
        color: #475569 !important;
        transition: transform 0.25s ease, color 0.2s ease;
        flex-shrink: 0;
    }

    /* Hover */
    #sidebar .sidebar-menu li > a:hover {
        background: #f1f5f9 !important;
        color: #000000 !important;
    }
    #sidebar .sidebar-menu li > a:hover > i,
    #sidebar .sidebar-menu li > a:hover > svg {
        color: #2563eb !important;
    }
    #sidebar .sidebar-menu li > a:hover > .menu-arrow {
        color: #2563eb !important;
    }

    /* ── ACTIVE STATE — glowing pill ── */
    #sidebar .sidebar-menu li > a.active {
        background: #eff6ff !important;
        color: #1e40af !important;
        font-weight: 800 !important;
        box-shadow:
            inset 3px 0 0 0 #3b82f6 !important;
    }
    #sidebar .sidebar-menu li > a.active > i,
    #sidebar .sidebar-menu li > a.active > svg {
        color: #2563eb !important;
    }
    #sidebar .sidebar-menu li > a.active > .menu-arrow {
        color: #2563eb !important;
    }

    /* Remove default before/after decorations */
    #sidebar .sidebar-menu li > a::before,
    #sidebar .sidebar-menu li > a::after,
    #sidebar .sidebar-menu li > a.active::before,
    #sidebar .sidebar-menu li > a.active::after {
        display: none !important;
        content: none !important;
    }

    /* Submenu open — rotate arrow */
    #sidebar .sidebar-menu li.submenu.active > a > .menu-arrow,
    #sidebar .sidebar-menu li.submenu > a[aria-expanded="true"] > .menu-arrow {
        transform: rotate(90deg);
        color: #60a5fa !important;
    }

    /* ── Submenu List ── */
    #sidebar .sidebar-menu .submenu > ul,
    #sidebar .sidebar-menu .submenu > div > ul {
        border-left: 2px solid #bfdbfe !important;
        margin: 2px 0 4px 24px !important;
        padding: 4px 0 4px 10px !important;
        list-style: none;
    }

    /* Submenu items */
    #sidebar .sidebar-menu .submenu ul li > a {
        padding: 8px 12px !important;
        font-size: 13px !important;
        color: #334155 !important;
        font-weight: 500 !important;
        border-radius: 8px !important;
        margin-bottom: 2px !important;
        display: flex !important;
        align-items: center !important;
        box-shadow: none !important;
        position: relative;
        transition: all 0.2s ease !important;
        background: transparent !important;
    }
    #sidebar .sidebar-menu .submenu ul li > a::before,
    #sidebar .sidebar-menu .submenu ul li > a::after {
        display: none !important;
        content: none !important;
    }
    /* Hover */
    #sidebar .sidebar-menu .submenu ul li > a:hover {
        background: transparent !important;
        color: #2563eb !important;
    }
    /* Active Submenu Item */
    #sidebar .sidebar-menu .submenu ul li > a.active {
        background: transparent !important;
        color: #1e40af !important;
        font-weight: 800 !important;
        box-shadow: none !important;
    }

    /* Active submenu dot indicator */
    #sidebar .sidebar-menu .submenu ul li > a.active::before {
        display: block !important;
        content: '' !important;
        position: absolute;
        left: -15px;
        top: 50%;
        transform: translateY(-50%);
        width: 6px;
        height: 6px;
        background: #3b82f6;
        border-radius: 50%;
        box-shadow: 0 0 8px rgba(59, 130, 246, 0.6);
    }

    /* =============================================
       MINI / COLLAPSED SIDEBAR — icon rail polish
       (body.mini-sidebar without hover expand-menu)
       ============================================= */
    @media (min-width: 991.98px) {
        body.mini-sidebar:not(.expand-menu) #sidebar.sidebar {
            border-right: 1px solid #cbd5e1 !important;
            box-shadow: 4px 0 24px rgba(15, 23, 42, 0.09) !important;
        }

        body.mini-sidebar:not(.expand-menu) #sidebar .logo-box {
            padding: 14px 8px !important;
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            border-bottom: 1px solid #e2e8f0 !important;
        }

        body.mini-sidebar:not(.expand-menu) #sidebar .sidebar-menu {
            padding: 14px 10px 28px !important;
        }

        body.mini-sidebar:not(.expand-menu) #sidebar .sidebar-menu > ul {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            gap: 4px !important;
        }

        body.mini-sidebar:not(.expand-menu) #sidebar .sidebar-menu li > a {
            justify-content: center !important;
            width: 44px !important;
            height: 44px !important;
            min-height: 44px !important;
            max-width: 44px !important;
            padding: 0 !important;
            margin: 0 !important;
            gap: 0 !important;
            border-radius: 12px !important;
        }

        /* Kill theme's offset that pushes icons off-center */
        body.mini-sidebar:not(.expand-menu) #sidebar .sidebar-menu > ul > li > a i,
        body.mini-sidebar:not(.expand-menu) #sidebar .sidebar-menu > ul > li > a svg,
        body.mini-sidebar:not(.expand-menu) #sidebar .sidebar-menu li > a > i,
        body.mini-sidebar:not(.expand-menu) #sidebar .sidebar-menu li > a > svg {
            margin: 0 !important;
            margin-left: 0 !important;
            width: 20px !important;
            min-width: 20px !important;
            height: 20px !important;
            font-size: 17px !important;
            color: #475569 !important;
        }

        body.mini-sidebar:not(.expand-menu) #sidebar .sidebar-menu li > a > .menu-arrow,
        body.mini-sidebar:not(.expand-menu) #sidebar .sidebar-menu li > a > span {
            display: none !important;
        }

        body.mini-sidebar:not(.expand-menu) #sidebar .sidebar-menu li > a:hover {
            background: #f1f5f9 !important;
            color: #0f172a !important;
        }
        body.mini-sidebar:not(.expand-menu) #sidebar .sidebar-menu li > a:hover > i,
        body.mini-sidebar:not(.expand-menu) #sidebar .sidebar-menu li > a:hover > svg {
            color: #1e40af !important;
        }

        /* Active: soft navy tint, no inset bar */
        body.mini-sidebar:not(.expand-menu) #sidebar .sidebar-menu li > a.active {
            background: rgba(30, 58, 95, 0.1) !important;
            color: #1e3a5f !important;
            box-shadow: none !important;
            font-weight: 600 !important;
        }
        body.mini-sidebar:not(.expand-menu) #sidebar .sidebar-menu li > a.active > i,
        body.mini-sidebar:not(.expand-menu) #sidebar .sidebar-menu li > a.active > svg {
            color: #1e3a5f !important;
        }

        /* Parent of open/active submenu also reads as selected in rail */
        body.mini-sidebar:not(.expand-menu) #sidebar .sidebar-menu li.submenu.active > a,
        body.mini-sidebar:not(.expand-menu) #sidebar .sidebar-menu li.submenu > a.subdrop {
            background: rgba(30, 58, 95, 0.1) !important;
            color: #1e3a5f !important;
            box-shadow: none !important;
        }
        body.mini-sidebar:not(.expand-menu) #sidebar .sidebar-menu li.submenu.active > a > i,
        body.mini-sidebar:not(.expand-menu) #sidebar .sidebar-menu li.submenu.active > a > svg,
        body.mini-sidebar:not(.expand-menu) #sidebar .sidebar-menu li.submenu > a.subdrop > i,
        body.mini-sidebar:not(.expand-menu) #sidebar .sidebar-menu li.submenu > a.subdrop > svg {
            color: #1e3a5f !important;
        }
    }
</style>

@if (!Route::is(['index-two', 'index-three', 'index-four', 'index-five']))
    <div class="sidebar" id="sidebar">
        <div class="sidebar-inner slimscroll">
            <div id="sidebar-menu" class="sidebar-menu">
                @if (false){{-- menu template Kanakku (horizontal), tidak dipakai layout vertical --}}
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

                        <li class="menu-title"><span>Inventory</span></li>
                        <li class="submenu">
                            <a href="#"><i class="fe fe-package"></i> <span> Products / Services</span> <span
                                    class="menu-arrow"></span></a>
                            <ul>
                                <li><a class="{{ Request::is('product-list', 'add-products', 'edit-products') ? 'active' : '' }}"
                                        href="{{ url('product-list') }}">Product List</a></li>
                                <li><a class="{{ Request::is('category') ? 'active' : '' }}"
                                        href="{{ url('category') }}">Category</a></li>

                                <li><a class="{{ Request::is('units') ? 'active' : '' }}"
                                        href="{{ url('units') }}">Units</a></li>
                            </ul>
                        </li>
                        <li>
                            <a class="{{ Request::is('inventory', 'inventory-history') ? 'active' : '' }}"
                                href="{{ url('inventory') }}"><i class="fe fe-user"></i> <span>Inventory</span></a>
                        </li>

                        <li class="submenu">
                            <a href="#"><i class="fe fe-file-plus"></i><span>Signature</span> <span
                                    class="menu-arrow"></span></a>
                            <ul>
                                <li><a
                                        class="{{ Request::is('signature-list') ? 'active' : '' }}"href="{{ url('signature-list') }}"><i
                                            class="fe fe-clipboard"></i> <span>List of
                                            Signature</span></a></li>
                                <li><a
                                        class="{{ Request::is('signature-invoice') ? 'active' : '' }}"href="{{ url('signature-invoice') }}"><i
                                            class="fe fe-box"></i> <span>Signature
                                            Invoice</span></a></li>

                            </ul>
                        </li>

                        <li class="menu-title"><span>Sales</span></li>
                        <li class="submenu">
                            <a class="{{ Request::is('invoices', 'invoices-paid', 'invoices-overdue', 'invoices-cancelled', 'invoices-recurring', 'invoices-unpaid', 'invoices-refunded', 'invoices-draft', 'invoice-details-admin', 'invoice-details', 'invoice-template') ? 'active' : '' }}"
                                href="{{ url('invoices') }}"><i class="fe fe-file"></i> <span>Invoices</span><span
                                    class="menu-arrow"></span></a>
                            <ul>
                                <li><a class="{{ Request::is('invoices', 'invoices-paid', 'invoices-overdue', 'invoices-cancelled', 'invoices-recurring', 'invoices-unpaid', 'invoices-refunded', 'invoices-draft') ? 'active' : '' }}"
                                        href="{{ url('invoices') }}">Invoices List</a></li>
                                <li><a class="{{ Request::is('invoice-details-admin') ? 'active' : '' }}"
                                        href="{{ url('invoice-details-admin') }}">Invoice Details (Admin)</a></li>
                                <li><a class="{{ Request::is('invoice-details') ? 'active' : '' }}"
                                        href="{{ url('invoice-details') }}">Invoice Details (Customer)</a></li>
                                <li><a class="{{ Request::is('invoice-template') ? 'active' : '' }}"
                                        href="{{ url('invoice-template') }}">Invoice Templates</a></li>
                            </ul>
                        </li>
                    </ul>
                    <button class="viewmoremenu">More Menu</button>
                    <ul class="hidden-links hidden">
                        <li>
                            <a class="{{ Request::is('recurring-invoices') ? 'active' : '' }}"
                                href="{{ url('recurring-invoices') }}"><i class="fe fe-clipboard"></i> <span>Recurring
                                    Invoices</span></a>
                        </li>
                        <li>
                            <a class="{{ Request::is('credit-notes', 'add-credit-notes', 'edit-credit-notes') ? 'active' : '' }}"
                                href="{{ url('credit-notes') }}"><i class="fe fe-edit"></i> <span>Credit
                                    Notes</span></a>
                        </li>
                        <li class="menu-title"><span>Purchases</span></li>
                        <li>
                            <a class="{{ Request::is('purchases', 'add-purchases', 'edit-purchases', 'add-purchase-return', 'edit-purchase-return') ? 'active' : '' }}"
                                href="{{ url('purchases') }}"><i class="fe fe-shopping-cart"></i>
                                <span>Purchases</span></a>
                        </li>
                        <li>
                            <a class="{{ Request::is('purchase-orders', 'add-purchases-order', 'edit-purchases-order') ? 'active' : '' }}"
                                href="{{ url('purchase-orders') }}"><i class="fe fe-shopping-bag"></i> <span>Purchase
                                    Orders</span></a>
                        </li>
                        <li>
                            <a class="{{ Request::is('debit-notes') ? 'active' : '' }}"
                                href="{{ url('debit-notes') }}"><i class="fe fe-file-text"></i> <span>Debit
                                    Notes</span></a>
                        </li>

                        <li class="menu-title"><span>Finance & Accounts</span></li>
                        <li>
                            <a class="{{ Request::is('expenses') ? 'active' : '' }}" href="{{ url('expenses') }}"><i
                                    class="fe fe-file-plus"></i> <span>Expenses</span></a>
                        </li>
                        <li>
                            <a class="{{ Request::is('payments') ? 'active' : '' }}" href="{{ url('payments') }}"><i
                                    class="fe fe-credit-card"></i> <span>Payments</span></a>
                        </li>

                        <li class="menu-title"><span>Quotations</span></li>
                        <li>
                            <a class="{{ Request::is('quotations', 'add-quotations', 'edit-quotations') ? 'active' : '' }}"
                                href="{{ url('quotations') }}"><i class="fe fe-clipboard"></i>
                                <span>Quotations</span></a>
                        </li>
                        <li>
                            <a class="{{ Request::is('delivery-challans', 'add-delivery-challans', 'edit-delivery-challans') ? 'active' : '' }}"
                                href="{{ url('delivery-challans') }}"><i class="fe fe-file-text"></i> <span>Delivery
                                    Challans</span></a>
                        </li>

                        <li class="menu-title"><span>Reports</span></li>
                        <li>
                            <a class="{{ Request::is('payment-summary') ? 'active' : '' }}"
                                href="{{ url('payment-summary') }}"><i class="fe fe-credit-card"></i> <span>Payment
                                    Summary</span></a>
                        <li class="submenu">
                            <a href="#"><i class="fe fe-box"></i><span>Reports</span> <span
                                    class="menu-arrow"></span></a>
                            <ul>
                                <li><a class="{{ Request::is('expense-report') ? 'active' : '' }}"
                                        href="{{ url('expense-report') }}">Expense Report</a></li>
                                <li><a class="{{ Request::is('purchase-report') ? 'active' : '' }}"
                                        href="{{ url('purchase-report') }}">Purchase Report</a></li>
                                <li><a class="{{ Request::is('purchase-return') ? 'active' : '' }}"
                                        href="{{ url('purchase-return') }}">Purchase Return Report</a></li>
                                <li><a class="{{ Request::is('sales-report') ? 'active' : '' }}"
                                        href="{{ url('sales-report') }}">Sales Report</a></li>
                                <li><a class="{{ Request::is('sales-return-report') ? 'active' : '' }}"
                                        href="{{ url('sales-return-report') }}">Sales Return Report</a></li>
                                <li><a class="{{ Request::is('quotation-report') ? 'active' : '' }}"
                                        href="{{ url('quotation-report') }}">Quotation Report</a></li>
                                <li><a class="{{ Request::is('payment-report') ? 'active' : '' }}"
                                        href="{{ url('payment-report') }}">Payment Report</a></li>
                                <li><a class="{{ Request::is('stock-report') ? 'active' : '' }}"
                                        href="{{ url('stock-report') }}">Stock Report</a></li>
                                <li><a class="{{ Request::is('low-stock-report') ? 'active' : '' }}"
                                        href="{{ url('low-stock-report') }}">Low Stock Report</a></li>
                                <li><a class="{{ Request::is('income-report') ? 'active' : '' }}"
                                        href="{{ url('income-report') }}">Income Report</a></li>
                                <li><a class="{{ Request::is('tax-purchase', 'tax-sales') ? 'active' : '' }}"
                                        href="{{ url('tax-purchase') }}">Tax Report</a></li>
                                <li><a class="{{ Request::is('profit-loss-list') ? 'active' : '' }}"
                                        href="{{ url('profit-loss-list') }}">Profit & Loss</a></li>
                            </ul>
                        </li>
                        </li>

                        <li class="menu-title"><span>User Management</span></li>
                        <li>
                            <a class="{{ Request::is('users') ? 'active' : '' }}" href="{{ url('users') }}"><i
                                    class="fe fe-user"></i> <span>Users</span></a>
                        </li>
                        <li>
                            <a class="{{ Request::is('roles-permission', 'permission') ? 'active' : '' }}"
                                href="{{ url('roles-permission') }}"><i class="fe fe-clipboard"></i> <span>Roles &
                                    Permission</span></a>
                        </li>
                        <li>
                            <a class="{{ Request::is('delete-account-request') ? 'active' : '' }}"
                                href="{{ url('delete-account-request') }}"><i class="fe fe-trash-2"></i> <span>Delete
                                    Account
                                    Request</span></a>
                        </li>

                        <li class="menu-title"><span>Membership</span></li>
                        <li class="submenu">
                            <a href="#"><i class="fe fe-book"></i> <span> Membership</span> <span
                                    class="menu-arrow"></span></a>
                            <ul>
                                <li><a class="{{ Request::is('membership-plans') ? 'active' : '' }}"
                                        href="{{ url('membership-plans') }}">Membership Plans</a></li>
                                <li><a class="{{ Request::is('membership-addons') ? 'active' : '' }}"
                                        href="{{ url('membership-addons') }}">Membership Addons</a></li>
                                <li><a class="{{ Request::is('subscribers') ? 'active' : '' }}"
                                        href="{{ url('subscribers') }}">Subscribers</a></li>
                                <li><a class="{{ Request::is('transactions') ? 'active' : '' }}"
                                        href="{{ url('transactions') }}">Transactions</a></li>
                            </ul>
                        </li>

                        <li class="menu-title"><span>Content (CMS)</span></li>
                        <li>
                            <a class="{{ Request::is('pages') ? 'active' : '' }}" href="{{ url('pages') }}"><i
                                    class="fe fe-folder"></i> <span>Pages</span></a>
                        </li>
                        <li class="submenu">
                            <a href="#"><i class="fe fe-book"></i> <span> Blog</span> <span
                                    class="menu-arrow"></span></a>
                            <ul>
                                <li><a class="{{ Request::is('all-blogs', 'inactive-blog') ? 'active' : '' }}"
                                        href="{{ url('all-blogs') }}">All Blogs</a></li>
                                <li><a class="{{ Request::is('categories') ? 'active' : '' }}"
                                        href="{{ url('categories') }}">Categories</a></li>
                                <li><a class="{{ Request::is('blog-comments') ? 'active' : '' }}"
                                        href="{{ url('blog-comments') }}">Blog Comments</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="#"><i class="fe fe-map-pin"></i> <span> Location</span> <span
                                    class="menu-arrow"></span></a>
                            <ul>
                                <li><a class="{{ Request::is('countries') ? 'active' : '' }}"
                                        href="{{ url('countries') }}">Countries</a></li>
                                <li><a class="{{ Request::is('states') ? 'active' : '' }}"
                                        href="{{ url('states') }}">States</a></li>
                                <li><a class="{{ Request::is('cities') ? 'active' : '' }}"
                                        href="{{ url('cities') }}">Cities</a></li>
                            </ul>
                        </li>
                        <li>
                            <a class="{{ Request::is('testimonials') ? 'active' : '' }}"
                                href="{{ url('testimonials') }}"><i class="fe fe-message-square"></i>
                                <span>Testimonials</span></a>
                        </li>
                        <li>
                            <a class="{{ Request::is('faq') ? 'active' : '' }}" href="{{ url('faq') }}"><i
                                    class="fe fe-alert-circle"></i> <span>FAQ</span></a>
                        </li>

                        <li class="menu-title"><span>Support</span></li>
                        <li>
                            <a class="{{ Request::is('contact-messages') ? 'active' : '' }}"
                                href="{{ url('contact-messages') }}"><i class="fe fe-printer"></i> <span>Contact
                                    Messages</span></a>
                        </li>
                        <li class="submenu">
                            <a href="#"><i class="fe fe-save"></i> <span> Tickets</span> <span
                                    class="menu-arrow"></span></a>
                            <ul>
                                <li><a class="{{ Request::is('tickets', 'tickets-list-open', 'tickets-list-resolved', 'tickets-list-pending', 'tickets-list-closed') ? 'active' : '' }}"
                                        href="{{ url('tickets') }}">Tickets</a></li>
                                <li><a class="{{ Request::is('tickets-list', 'tickets-open', 'tickets-resolved', 'tickets-pending', 'tickets-closed') ? 'active' : '' }}"
                                        href="{{ url('tickets-list') }}">Tickets List</a></li>
                                <li><a class="{{ Request::is('tickets-kanban') ? 'active' : '' }}"
                                        href="{{ url('tickets-kanban') }}">Tickets Kanban</a></li>
                                <li><a class="{{ Request::is('ticket-details') ? 'active' : '' }}"
                                        href="{{ url('ticket-details') }}">Ticket Overview</a></li>
                            </ul>
                        </li>

                        <li class="menu-title"><span>Pages</span></li>
                        <li>
                            <a class="{{ Request::is('profile') ? 'active' : '' }}" href="{{ url('profile') }}"><i
                                    class="fe fe-user"></i> <span>Profile</span></a>
                        </li>
                        <li class="submenu">
                            <a href="#"><i class="fe fe-lock"></i> <span> Authentication </span> <span
                                    class="menu-arrow"></span></a>
                            <ul>
                                <li><a class="{{ Request::is('login') ? 'active' : '' }}"
                                        href="{{ url('login') }}"> Login </a></li>
                                <li><a class="{{ Request::is('register') ? 'active' : '' }}"
                                        href="{{ url('register') }}"> Register </a></li>
                                <li><a class="{{ Request::is('forgot-password') ? 'active' : '' }}"
                                        href="{{ url('forgot-password') }}"> Forgot Password </a></li>
                                <li><a class="{{ Request::is('lock-screen') ? 'active' : '' }}"
                                        href="{{ url('lock-screen') }}"> Lock Screen </a></li>
                            </ul>
                        </li>
                        <li>
                            <a class="{{ Request::is('error-404') ? 'active' : '' }}"
                                href="{{ url('error-404') }}"><i class="fe fe-x-square"></i> <span>Error
                                    Pages</span></a>
                        </li>
                        <li>
                            <a class="{{ Request::is('blank-page') ? 'active' : '' }}"
                                href="{{ url('blank-page') }}"><i class="fe fe-file"></i> <span>Blank
                                    Page</span></a>
                        </li>
                        <li>
                            <a class="{{ Request::is('maps-vector') ? 'active' : '' }}"
                                href="{{ url('maps-vector') }}"><i class="fe fe-image"></i> <span>Vector
                                    Maps</span></a>
                        </li>

                        <li class="menu-title">
                            <span>UI Interface</span>
                        </li>
                        <li class="submenu">
                            <a href="#"><i class="fe fe-pocket"></i> <span>Base UI </span> <span
                                    class="menu-arrow"></span></a>
                            <ul>
                                <li><a class="{{ Request::is('alerts') ? 'active' : '' }}"
                                        href="{{ url('alerts') }}">Alerts</a></li>
                                <li><a class="{{ Request::is('accordions') ? 'active' : '' }}"
                                        href="{{ url('accordions') }}">Accordions</a></li>
                                <li><a class="{{ Request::is('avatar') ? 'active' : '' }}"
                                        href="{{ url('avatar') }}">Avatar</a></li>
                                <li><a class="{{ Request::is('badges') ? 'active' : '' }}"
                                        href="{{ url('badges') }}">Badges</a></li>
                                <li><a class="{{ Request::is('buttons') ? 'active' : '' }}"
                                        href="{{ url('buttons') }}">Buttons</a></li>
                                <li><a class="{{ Request::is('buttongroup') ? 'active' : '' }}"
                                        href="{{ url('buttongroup') }}">Button Group</a></li>
                                <li><a class="{{ Request::is('breadcrumbs') ? 'active' : '' }}"
                                        href="{{ url('breadcrumbs') }}">Breadcrumb</a></li>
                                <li><a class="{{ Request::is('cards') ? 'active' : '' }}"
                                        href="{{ url('cards') }}">Cards</a></li>
                                <li><a class="{{ Request::is('carousel') ? 'active' : '' }}"
                                        href="{{ url('carousel') }}">Carousel</a></li>
                                <li><a class="{{ Request::is('dropdowns') ? 'active' : '' }}"
                                        href="{{ url('dropdowns') }}">Dropdowns</a></li>
                                <li><a class="{{ Request::is('grid') ? 'active' : '' }}"
                                        href="{{ url('grid') }}">Grid</a></li>
                                <li><a class="{{ Request::is('images') ? 'active' : '' }}"
                                        href="{{ url('images') }}">Images</a></li>
                                <li><a class="{{ Request::is('lightbox') ? 'active' : '' }}"
                                        href="{{ url('lightbox') }}">Lightbox</a></li>
                                <li><a class="{{ Request::is('media') ? 'active' : '' }}"
                                        href="{{ url('media') }}">Media</a></li>
                                <li><a class="{{ Request::is('modal') ? 'active' : '' }}"
                                        href="{{ url('modal') }}">Modals</a></li>
                                <li><a class="{{ Request::is('offcanvas') ? 'active' : '' }}"
                                        href="{{ url('offcanvas') }}">Offcanvas</a></li>
                                <li><a class="{{ Request::is('pagination') ? 'active' : '' }}"
                                        href="{{ url('pagination') }}">Pagination</a></li>
                                <li><a class="{{ Request::is('popover') ? 'active' : '' }}"
                                        href="{{ url('popover') }}">Popover</a></li>
                                <li><a class="{{ Request::is('progress') ? 'active' : '' }}"
                                        href="{{ url('progress') }}">Progress Bars</a></li>
                                <li><a class="{{ Request::is('placeholders') ? 'active' : '' }}"
                                        href="{{ url('placeholders') }}">Placeholders</a></li>
                                <li><a class="{{ Request::is('rangeslider') ? 'active' : '' }}"
                                        href="{{ url('rangeslider') }}">Range Slider</a></li>
                                <li><a class="{{ Request::is('spinners') ? 'active' : '' }}"
                                        href="{{ url('spinners') }}">Spinner</a></li>
                                <li><a class="{{ Request::is('sweetalerts') ? 'active' : '' }}"
                                        href="{{ url('sweetalerts') }}">Sweet Alerts</a></li>
                                <li><a class="{{ Request::is('tab') ? 'active' : '' }}"
                                        href="{{ url('tab') }}">Tabs</a></li>
                                <li><a class="{{ Request::is('toastr') ? 'active' : '' }}"
                                        href="{{ url('toastr') }}">Toasts</a></li>
                                <li><a class="{{ Request::is('tooltip') ? 'active' : '' }}"
                                        href="{{ url('tooltip') }}">Tooltip</a></li>
                                <li><a class="{{ Request::is('typography') ? 'active' : '' }}"
                                        href="{{ url('typography') }}">Typography</a></li>
                                <li><a class="{{ Request::is('video') ? 'active' : '' }}"
                                        href="{{ url('video') }}">Video</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="#"><i class="fe fe-box"></i> <span>Elements </span> <span
                                    class="menu-arrow"></span></a>
                            <ul>
                                <li><a class="{{ Request::is('ribbon') ? 'active' : '' }}"
                                        href="{{ url('ribbon') }}">Ribbon</a></li>
                                <li><a class="{{ Request::is('clipboard') ? 'active' : '' }}"
                                        href="{{ url('clipboard') }}">Clipboard</a></li>
                                <li><a class="{{ Request::is('drag-drop') ? 'active' : '' }}"
                                        href="{{ url('drag-drop') }}">Drag & Drop</a></li>
                                <li><a class="{{ Request::is('rating') ? 'active' : '' }}"
                                        href="{{ url('rating') }}">Rating</a></li>
                                <li><a class="{{ Request::is('text-editor') ? 'active' : '' }}"
                                        href="{{ url('text-editor') }}">Text Editor</a></li>
                                <li><a class="{{ Request::is('counter') ? 'active' : '' }}"
                                        href="{{ url('counter') }}">Counter</a></li>
                                <li><a class="{{ Request::is('scrollbar') ? 'active' : '' }}"
                                        href="{{ url('scrollbar') }}">Scrollbar</a></li>
                                <li><a class="{{ Request::is('notification') ? 'active' : '' }}"
                                        href="{{ url('notification') }}">Notification</a></li>
                                <li><a class="{{ Request::is('stickynote') ? 'active' : '' }}"
                                        href="{{ url('stickynote') }}">Sticky Note</a></li>
                                <li><a class="{{ Request::is('timeline') ? 'active' : '' }}"
                                        href="{{ url('timeline') }}">Timeline</a></li>
                                <li><a class="{{ Request::is('horizontal-timeline') ? 'active' : '' }}"
                                        href="{{ url('horizontal-timeline') }}">Horizontal Timeline</a></li>
                                <li><a class="{{ Request::is('form-wizard') ? 'active' : '' }}"
                                        href="{{ url('form-wizard') }}">Form Wizard</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="#"><i class="fe fe-bar-chart"></i> <span> Charts </span> <span
                                    class="menu-arrow"></span></a>
                            <ul>
                                <li><a class="{{ Request::is('chart-apex') ? 'active' : '' }}"
                                        href="{{ url('chart-apex') }}">Apex Charts</a></li>
                                <li><a class="{{ Request::is('chart-js') ? 'active' : '' }}"
                                        href="{{ url('chart-js') }}">Chart Js</a></li>
                                <li><a class="{{ Request::is('chart-morris') ? 'active' : '' }}"
                                        href="{{ url('chart-morris') }}">Morris Charts</a></li>
                                <li><a class="{{ Request::is('chart-flot') ? 'active' : '' }}"
                                        href="{{ url('chart-flot') }}">Flot Charts</a></li>
                                <li><a class="{{ Request::is('chart-peity') ? 'active' : '' }}"
                                        href="{{ url('chart-peity') }}">Peity Charts</a></li>
                                <li><a class="{{ Request::is('chart-c3') ? 'active' : '' }}"
                                        href="{{ url('chart-c3') }}">C3 Charts</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="#"><i class="fe fe-award"></i> <span> Icons </span> <span
                                    class="menu-arrow"></span></a>
                            <ul>
                                <li><a class="{{ Request::is('icon-fontawesome') ? 'active' : '' }}"
                                        href="{{ url('icon-fontawesome') }}">Fontawesome Icons</a></li>
                                <li><a class="{{ Request::is('icon-feather') ? 'active' : '' }}"
                                        href="{{ url('icon-feather') }}">Feather Icons</a></li>
                                <li><a class="{{ Request::is('icon-ionic') ? 'active' : '' }}"
                                        href="{{ url('icon-ionic') }}">Ionic Icons</a></li>
                                <li><a class="{{ Request::is('icon-material') ? 'active' : '' }}"
                                        href="{{ url('icon-material') }}">Material Icons</a></li>
                                <li><a class="{{ Request::is('icon-pe7') ? 'active' : '' }}"
                                        href="{{ url('icon-pe7') }}">Pe7 Icons</a></li>
                                <li><a class="{{ Request::is('icon-simpleline') ? 'active' : '' }}"
                                        href="{{ url('icon-simpleline') }}">Simpleline Icons</a></li>
                                <li><a class="{{ Request::is('icon-themify') ? 'active' : '' }}"
                                        href="{{ url('icon-themify') }}">Themify Icons</a></li>
                                <li><a class="{{ Request::is('icon-weather') ? 'active' : '' }}"
                                        href="{{ url('icon-weather') }}">Weather Icons</a></li>
                                <li><a class="{{ Request::is('icon-typicon') ? 'active' : '' }}"
                                        href="{{ url('icon-typicon') }}">Typicon Icons</a></li>
                                <li><a class="{{ Request::is('icon-flag') ? 'active' : '' }}"
                                        href="{{ url('icon-flag') }}">Flag Icons</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="#"><i class="fe fe-sidebar"></i> <span> Forms </span> <span
                                    class="menu-arrow"></span></a>
                            <ul>
                                <li><a class="{{ Request::is('form-basic-inputs') ? 'active' : '' }}"
                                        href="{{ url('form-basic-inputs') }}">Basic Inputs </a></li>
                                <li><a class="{{ Request::is('form-input-groups') ? 'active' : '' }}"
                                        href="{{ url('form-input-groups') }}">Input Groups </a></li>
                                <li><a class="{{ Request::is('form-horizontal') ? 'active' : '' }}"
                                        href="{{ url('form-horizontal') }}">Horizontal Form </a></li>
                                <li><a class="{{ Request::is('form-vertical') ? 'active' : '' }}"
                                        href="{{ url('form-vertical') }}"> Vertical Form </a></li>
                                <li><a class="{{ Request::is('form-mask') ? 'active' : '' }}"
                                        href="{{ url('form-mask') }}">Form Mask </a></li>
                                <li><a class="{{ Request::is('form-validation') ? 'active' : '' }}"
                                        href="{{ url('form-validation') }}">Form Validation </a></li>
                                <li><a class="{{ Request::is('form-select2') ? 'active' : '' }}"
                                        href="{{ url('form-select2') }}">Form Select2 </a></li>
                                <li><a class="{{ Request::is('form-fileupload') ? 'active' : '' }}"
                                        href="{{ url('form-fileupload') }}">File Upload </a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="#"><i class="fe fe-layout"></i> <span> Tables </span> <span
                                    class="menu-arrow"></span></a>
                            <ul>
                                <li><a class="{{ Request::is('tables-basic') ? 'active' : '' }}"
                                        href="{{ url('tables-basic') }}">Basic Tables </a></li>
                                <li><a class="{{ Request::is('data-tables') ? 'active' : '' }}"
                                        href="{{ url('data-tables') }}">Data Table </a></li>
                            </ul>
                        </li>

                        <li class="menu-title"><span>Settings</span></li>
                        <li>
                            <a class="{{ Request::is('settings', 'company-settings', 'invoice-settings', 'template-invoice', 'payment-settings', 'bank-account', 'tax-rates', 'plan-billing', 'two-factor', 'custom-filed', 'email-settings', 'preferences', 'saas-settings', 'seo-settings', 'email-template') ? 'active' : '' }}"
                                href="{{ url('settings') }}"><i class="fe fe-settings"></i>
                                <span>Settings</span></a>
                        </li>
                        <li class="menu-title">
                            <span>Extras</span>
                        </li>
                        <li>
                            <a href="#"><i class="fe fe-file-text"></i> <span>Documentation</span></a>
                        </li>
                        <li>
                            <a href="javascript:void(0);"><i class="fe fe-lock"></i> <span>Change Log</span> <span
                                    class="badge badge-primary ms-auto">v2.0.4</span></a>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);"><i class="fa fa-list"></i> <span>Multi Level</span> <span
                                    class="menu-arrow"></span></a>
                            <ul style="display: none;">
                                <li class="submenu">
                                    <a href="javascript:void(0);"> <span>Level 1</span> <span
                                            class="menu-arrow"></span></a>
                                    <ul style="display: none;" class="level2">
                                        <li><a href="javascript:void(0);"><span>Level 2</span></a></li>
                                        <li class="submenu">
                                            <a href="javascript:void(0);"> <span> Level 2</span> <span
                                                    class="menu-arrow"></span></a>
                                            <ul style="display: none;" class="level3">
                                                <li><a href="javascript:void(0);">Level 3</a></li>
                                                <li><a href="javascript:void(0);">Level 3</a></li>
                                            </ul>
                                        </li>
                                        <li><a href="javascript:void(0);"> <span>Level 2</span></a></li>
                                    </ul>
                                </li>
                                <li>
                                    <a href="javascript:void(0);"> <span>Level 1</span></a>
                                </li>
                            </ul>
                        </li>
                        <li>
                            <a href="{{ route('logout') }}"><i class="fe fe-power"></i> <span>Logout</span></a>
                        </li>
                    </ul>
                    <!-- /Settings -->
                </nav>
                @endif
                <ul class="sidebar-vertical">
                    <!-- Main -->
    @php
        $akses = collect(json_decode(Session::get('user')->role_access ?? '[]'));
        $activeWh = $activeWarehouse ?? null;
        $canShow = function (string $name) use ($akses, $activeWh): bool {
            if (! $akses->firstWhere('name', $name)) {
                return false;
            }
            return \App\Support\WarehouseMenuAccess::allows($name, $activeWh);
        };
    @endphp
                    <li class="menu-title"><span>Menu Utama</span></li>
                    <li>
                        <a class="{{ Request::is('/', 'index') ? 'active' : '' }}" href="/">
                            <i class="fe fe-home"></i> <span>Dashboard</span>
                        </a>
                    </li>
                    <!-- /Main -->

                    <!-- Master -->
                    @php
                        $showMaster =
                        $canShow('Kategori') ||
                        $canShow('Satuan') ||
                        $canShow('Variasi');
                    @endphp
                    @php
                        $showGudangMenu =
                        $canShow('Gudang') ||
                        $canShow('Tipe Gudang');
                    @endphp
                    @php
                        $showProduk =
                        $canShow('Daftar Produk') ||
                        $canShow('Stok Produk');
                    @endphp
                    @php
                        $showBahan =
                        $canShow('Daftar Bahan Mentah') ||
                        $canShow('Stok Bahan Mentah');
                    @endphp
                    @php
                        $showInventory =
                        $canShow('Produk Bermasalah') ||
                        $canShow('Peringatan Stok Produk') ||
                        $canShow('Peringatan Stok Bahan Mentah') ||
                        $canShow('Stok Opname Produk') ||
                        $canShow('Stok Opname Bahan Mentah');
                    @endphp

                    @if ($showMaster || $showGudangMenu || $showProduk || $showBahan || $showInventory || $canShow('Armada') || $canShow('Pemasok'))
                        <li class="menu-title"><span>Master</span></li>
                        @if ($showMaster)
                            <li class="submenu">
                                <a href="#"><i class="fe fe-list"></i> <span> Master</span> <span
                                    class="menu-arrow"></span></a>
                                <ul style="display: none;">
                                    @if ($canShow('Kategori'))
                                        <li><a href="{{ url('category') }}"
                                            class="{{ Request::is('category') ? 'active' : '' }}">Kategori</a></li>
                                    @endif

                                    @if ($canShow('Satuan'))
                                        <li><a href="{{ url('unit') }}"
                                            class="{{ Request::is('unit') ? 'active' : '' }}">Satuan</a></li>
                                    @endif

                                    @if ($canShow('Variasi'))
                                        <li><a href="{{ url('variant') }}"
                                            class="{{ Request::is('variant') ? 'active' : '' }}">Variasi</a></li>
                                    @endif
                                </ul>
                            </li>
                        @endif

                        @if ($showGudangMenu)
                            <li class="submenu">
                                <a href="#"><i class="fas fa-warehouse"></i> <span> Gudang</span> <span
                                    class="menu-arrow"></span></a>
                                <ul style="display: none;">
                                    @if ($canShow('Gudang'))
                                        <li><a href="{{ url('warehouse') }}"
                                            class="{{ Request::is('warehouse') ? 'active' : '' }}">Daftar Gudang</a></li>
                                    @endif

                                    @if ($canShow('Tipe Gudang'))
                                        <li><a href="{{ url('warehouse-type') }}"
                                            class="{{ Request::is('warehouse-type') ? 'active' : '' }}">Tipe Gudang</a></li>
                                    @endif
                                </ul>
                            </li>
                        @endif

                        @if ($showProduk)
                            @if ($canShow('Daftar Produk'))
                                <li>
                                    <a href="{{ url('product') }}" class="{{ Request::is('product') ? 'active' : '' }}">
                                        <i class="fe fe-package"></i> <span>Daftar Produk</span>
                                    </a>
                                </li>
                            @endif

                            @if ($canShow('Stok Produk'))
                                <li>
                                    <a href="{{ url('stockProduct') }}" class="{{ Request::is('stockProduct') ? 'active' : '' }}">
                                        <i class="fa fa-boxes"></i> <span>Stok Produk</span>
                                    </a>
                                </li>
                            @endif

                            @if ($canShow('Daftar Produk'))
                                <li>
                                    <a href="{{ url('barcodePrint') }}" class="{{ Request::is('barcodePrint') ? 'active' : '' }}">
                                        <i class="fa fa-barcode"></i> <span>Cetak Barcode</span>
                                    </a>
                                </li>
                            @endif
                        @endif

                        @if ($showBahan)
                            <li class="submenu">
                                <a href="#"><i class="fa fa-cubes"></i> <span> Bahan Mentah</span> <span
                                    class="menu-arrow"></span></a>
                                <ul style="display: none;">
                                    @if ($canShow('Daftar Bahan Mentah'))
                                        <li><a href="{{ url('supplies') }}"
                                            class="{{ Request::is('supplies') ? 'active' : '' }}">Daftar Bahan Mentah</a></li>
                                    @endif

                                    @if ($canShow('Stok Bahan Mentah'))
                                        <li><a href="{{ url('stockSupplies') }}"
                                            class="{{ Request::is('stockSupplies') ? 'active' : '' }}">Stok Bahan Mentah</a></li>
                                    @endif
                                </ul>
                            </li>
                        @endif

                        <li class="submenu">
                            @if ($canShow('Armada'))
                                <li>
                                    <a class="{{ Request::is('customer') ? 'active' : '' }}" href="/customer"><i
                                        class="fe fe-users"></i> <span>Armada</span></a>
                                </li>
                            @endif

                            @if ($canShow('Pemasok'))
                                <li>
                                    <a class="{{ Request::is('supplier') ? 'active' : '' }}" href="/supplier"><i
                                        class="fe fe-truck"></i> <span>Pemasok</span></a>
                                </li>
                            @endif
                        </li>

                        @if ($showInventory)
                            <li class="submenu">
                                <a href="#"><i class="fe fe-briefcase"></i> <span> Inventaris</span> <span
                                    class="menu-arrow"></span></a>
                                <ul style="display: none;">
                                    @if ($canShow('Produk Bermasalah'))
                                        <li><a href="{{ url('productIssue') }}"
                                            class="{{ Request::is('productIssue') ? 'active' : '' }}">Produk Bermasalah</a></li>
                                    @endif

                                    @if ($canShow('Peringatan Stok Produk'))
                                        <li><a href="{{ url('stockAlert') }}"
                                            class="{{ Request::is('stockAlert') ? 'active' : '' }}">Peringatan Stok Produk</a></li>
                                    @endif

                                    @if ($canShow('Peringatan Stok Bahan Mentah'))
                                        <li><a href="{{ url('stockAlertSupplies') }}"
                                            class="{{ Request::is('stockAlertSupplies') ? 'active' : '' }}">Peringatan Stok Bahan Mentah</a></li>
                                    @endif

                                    @if ($canShow('Stok Opname Produk'))
                                        <li><a href="{{ url('stockOpname') }}"
                                            class="{{ Request::is('stockOpname') ? 'active' : '' }}">Stok Opname Produk</a></li>
                                    @endif

                                    @if ($canShow('Stok Opname Bahan Mentah'))
                                        <li><a href="{{ url('stockOpnameBahan') }}"
                                            class="{{ Request::is('stockOpnameBahan') ? 'active' : '' }}">Stok Opname Bahan Mentah</a></li>
                                    @endif
                                </ul>
                            </li>
                        @endif
                    @endif
                    <!-- /Master -->

                    {{-- Ordering --}}
                    @if ($canShow('Pengiriman') || $canShow('Pembelian') || $canShow('Tanda Terima PO') || $canShow('Stock Transfer'))
                        <li class="menu-title"><span>Penjualan & Pembelian</span></li>
                        <li class="submenu">
                            @if ($canShow('Pengiriman'))
                                <li>
                                    <a class="{{ Request::is('salesOrder') ? 'active' : '' }}" href="/salesOrder"><i
                                            class="fe fe-truck"></i> <span>Pengiriman</span></a>
                                </li>
                            @endif

                            @if ($canShow('Pembelian'))
                                <li>
                                    <a class="{{ Request::is('purchaseOrder') ? 'active' : '' }}" href="/purchaseOrder">
                                        <i class="fe fe-dollar-sign"></i> <span>Pembelian</span></a>
                                </li>
                            @endif

                            @if ($canShow('Tanda Terima PO'))
                                <li>
                                    <a class="{{ Request::is('tt') ? 'active' : '' }}" href="/tt">
                                        <i class="fe fe-file-text"></i> <span>Tanda Terima PO</span></a>
                                </li>
                            @endif

                            @if ($canShow('Stock Transfer'))
                                <li>
                                    <a class="{{ Request::is('stockTransfer') ? 'active' : '' }}" href="{{ url('stockTransfer') }}">
                                        <i class="fe fe-shuffle"></i> <span>Stock Transfer</span></a>
                                </li>
                            @endif
                        </li>
                    @endif
                    {{-- /Ordering --}}

                    @if ($canShow('Resep Bahan Mentah') || $canShow('Produksi'))
                        <li class="menu-title"><span>Produksi</span></li>
                        <li class="submenu">
                            @if ($canShow('Resep Bahan Mentah'))
                                <li>
                                    <a class="{{ Request::is('bom') ? 'active' : '' }}" href="/bom"><i
                                        class="fe fe-file-text"></i> <span>Resep Bahan Mentah</span></a>
                                </li>
                            @endif

                            @if ($canShow('Produksi'))
                                <li>
                                    <a class="{{ Request::is('production') ? 'active' : '' }}" href="/production">
                                        <i class="fa-solid fa-gear"></i> <span>Produksi</span></a>
                                </li>
                            @endif
                       </li>
                    @endif

                    {{-- Report --}}
                    @php
                        $hasKasOperasional =
                        $canShow('Kas Operasional Admin') ||
                        $canShow('Kas Operasional Gudang') ||
                        $canShow('Kas Operasional Armada') ||
                        $canShow('Kas Operasional Sales') ||
                        $canShow('Kas Operasional');
                    @endphp
                    @php
                        $showAccounting =
                        $canShow('Kategori Kas') ||
                        $hasKasOperasional ||
                        // $canShow('Kas Kecil') ||
                        $canShow('Kas');
                    @endphp
                    @php
                        $showLaporan =
                        $canShow('Pengelolaan Bahan Mentah') ||
                        $canShow('Retur Produk') ||
                        $canShow('Laporan Produksi') ||
                        // $canShow('Laporan Efisiensi Produksi') ||
                        $canShow('Laporan Stock Aging') ||
                        $canShow('Stock Transfer') ||
                        $canShow('Stok Opname Produk') ||
                        $canShow('Stok Opname Bahan Mentah') ||
                        $canShow('Untung & Rugi') ||
                        $canShow('Barang Masuk Keluar');
                    @endphp
                    @php
                        $showLaporanMenu = $showLaporan || $canShow('Kas');
                    @endphp

                    @if ($showAccounting || $showLaporanMenu || $canShow('Bank Account') || $canShow('Hutang'))
                        <li class="menu-title"><span>Akuntansi & Laporan</span></li>

                        @if ($canShow('Bank Account'))
                            <li>
                                <a class="{{ Request::is('bank') ? 'active' : '' }}" href="/bank">
                                    <i class="bi bi-bank"></i> <span>Bank Account</span></a>
                            </li>
                        @endif

                        @if ($canShow('Hutang'))
                            <li>
                                <a class="{{ Request::is('payReceive') ? 'active' : '' }}" href="/payReceive">
                                    <i class="bi bi-cash-coin"></i> <span>Hutang</span></a>
                            </li>
                        @endif

                        @if ($showAccounting)
                            <li class="submenu">
                                <a href="#"><i class="fe fe-book"></i> <span> Akuntansi</span> <span
                                    class="menu-arrow"></span></a>
                                <ul style="display: none;">
                                    @if ($canShow('Kategori Kas'))
                                        <li><a href="{{ url('cashCategory') }}"
                                        class="{{ Request::is('cashCategory') ? 'active' : '' }}">Kategori Kas</a></li>
                                    @endif

                                    @if ($hasKasOperasional)
                                        <li><a href="{{ url('operationalCash') }}"
                                        class="{{ Request::is('operationalCash') ? 'active' : '' }}">Kas Operasional</a></li>
                                    @endif

                                    {{-- @if ($canShow('Kas Kecil'))
                                        <li><a href="{{ url('pettyCash') }}"
                                            class="{{ Request::is('pettyCash') ? 'active' : '' }}">
                                            Kas Kecil</a></li>
                                    @endif --}}

                                    @if ($canShow('Kas'))
                                        <li><a href="{{ url('cash') }}"
                                            class="{{ Request::is('cash') ? 'active' : '' }}">
                                            Kas</a></li>
                                    @endif
                                </ul>
                            </li>
                        @endif

                        @if ($showLaporanMenu)
                            <li class="submenu">
                                <a href="#"><i class="fe fe-activity"></i> <span> Laporan</span> <span
                                    class="menu-arrow"></span></a>
                                <ul style="display: none;">
                                    @if ($canShow('Pengelolaan Bahan Mentah'))
                                        <li><a href="/reportBahanBaku"
                                            class="{{ Request::is('reportBahanBaku') ? 'active' : '' }}">
                                            Pengelolaan Bahan Mentah</a></li>
                                    @endif

                                    @if ($canShow('Retur Produk'))
                                        <li><a href="/ProductReturn"
                                            class="{{ Request::is('ProductReturn') ? 'active' : '' }}">
                                            Laporan Retur Bahan</a></li>
                                        <li><a href="/reportReturProdukArmada"
                                            class="{{ Request::is('reportReturProdukArmada') ? 'active' : '' }}">
                                            Laporan Retur Produk (Armada)</a></li>
                                    @endif

                                    @if ($canShow('Laporan Produksi'))
                                        <li><a href="/reportProduksi"
                                            class="{{ Request::is('reportProduksi') ? 'active' : '' }}">
                                            Laporan Produksi</a></li>
                                    @endif

                                    {{-- @if ($canShow('Laporan Efisiensi Produksi') || $canShow('Laporan Produksi'))
                                        <li><a href="/reportEfisiensiProduksi"
                                            class="{{ Request::is('reportEfisiensiProduksi') ? 'active' : '' }}">
                                            Laporan Efisiensi Produksi</a></li>
                                    @endif --}}

                                    @if ($canShow('Stok Opname Produk') || $canShow('Stok Opname Bahan Mentah'))
                                        <li><a href="/reportSelisihOpname"
                                            class="{{ Request::is('reportSelisihOpname') ? 'active' : '' }}">
                                            Laporan Selisih Stok Opname</a></li>
                                    @endif

                                    @if ($canShow('Laporan Stock Aging'))
                                        <li><a href="/reportStockAging"
                                            class="{{ Request::is('reportStockAging') ? 'active' : '' }}">
                                            Laporan Stock Aging</a></li>
                                    @endif

                                    @if ($canShow('Stock Transfer'))
                                        <li><a href="{{ url('reportStockTransfer') }}"
                                            class="{{ Request::is('reportStockTransfer') ? 'active' : '' }}">
                                            Laporan Stock Transfer</a></li>
                                    @endif

                                    @if ($canShow('Kas'))
                                        <li><a href="/reportCashOut"
                                            class="{{ Request::is('reportCashOut') ? 'active' : '' }}">
                                            Laporan Pengeluaran Kas</a></li>
                                    @endif

                                    {{-- @if ($canShow('Untung & Rugi'))
                                        <li><a href="{{ url('profitLoss') }}"
                                            class="{{ Request::is('profitLoss') ? 'active' : '' }}">
                                            Untung & Rugi</a></li>
                                    @endif --}}

                                    {{-- @if ($canShow('Barang Masuk Keluar'))
                                        <li><a href="{{ url('inwardOutward') }}"
                                            class="{{ Request::is('inwardOutward') ? 'active' : '' }}">
                                            Barang Masuk Keluar</a></li>
                                    @endif --}}
                                </ul>
                            </li>
                        @endif
                    @endif

                    @if ($canShow('Pengguna') || $canShow('Peran & Perizinan'))
                        <li class="menu-title"><span>Manajemen Pengguna</span></li>
                        @if ($canShow('Pengguna'))
                            <li>
                                <a class="{{ Request::is('staff') ? 'active' : '' }}" href="/staff"><i
                                    class="fe fe-user"></i> <span>Pengguna</span></a>
                            </li>
                        @endif

                        @if ($canShow('Peran & Perizinan'))
                            <li>
                                <a class="{{ Request::is('roles-permission', 'permission') ? 'active' : '' }}"
                                    href="/role"><i class="fe fe-clipboard"></i> <span>Peran &
                                        Perizinan</span></a>
                            </li>
                        @endif
                    @endif

                    @php
                        $showIntegrasi =
                        $canShow('Sinkronisasi') ||
                        $canShow('Status API Eksternal') ||
                        $canShow('Aplikasi Eksternal') ||
                        $canShow('Dokumentasi API Eksternal') ||
                        $canShow('Log API Eksternal');
                    @endphp

                    @if ($showIntegrasi)
                        <li class="menu-title"><span>Integrasi</span></li>
                        @if ($canShow('Sinkronisasi'))
                            <li>
                                <a class="{{ Request::is('synchronization*') ? 'active' : '' }}" href="/synchronization">
                                    <i class="fe fe-refresh-cw"></i> <span>Pusat Sinkronisasi</span>
                                </a>
                            </li>
                        @endif

                        @if ($canShow('Status API Eksternal'))
                            <li>
                                <a class="{{ Request::is('externalApiStatus*') ? 'active' : '' }}"
                                    href="/externalApiStatus">
                                    <i class="fe fe-sliders"></i> <span>Status API Eksternal</span>
                                </a>
                            </li>
                        @endif

                        @if ($canShow('Aplikasi Eksternal'))
                            <li>
                                <a class="{{ Request::is('externalApplication*') ? 'active' : '' }}"
                                    href="/externalApplication">
                                    <i class="fe fe-box"></i> <span>Aplikasi Eksternal</span>
                                </a>
                            </li>
                        @endif

                        @if ($canShow('Dokumentasi API Eksternal'))
                            <li>
                                <a class="{{ Request::is('externalApiDocumentation*') ? 'active' : '' }}"
                                    href="/externalApiDocumentation">
                                    <i class="fe fe-book-open"></i> <span>Dokumentasi API Eksternal</span>
                                </a>
                            </li>
                        @endif

                        @if ($canShow('Log API Eksternal'))
                            <li>
                                <a class="{{ Request::is('externalApiLog*') ? 'active' : '' }}" href="/externalApiLog">
                                    <i class="fe fe-activity"></i> <span>Log API Eksternal</span>
                                </a>
                            </li>
                        @endif
                    @endif

                    @php
                        $showSetting =
                        $canShow('Profil') ||
                        $canShow('Pengaturan');
                    @endphp

                    @if ($showSetting)
                        {{-- <li class="menu-title"><span>Pengaturan</span></li>
                        <li class="submenu">
                            <a href="#"><i class="fe fe-settings"></i> <span> Pengaturan</span> <span
                                class="menu-arrow"></span></a>
                            <ul style="display: none;">
                                @if ($canShow('Profil'))
                                    <li><a href="{{ url('profiles') }}"
                                        class="{{ Request::is('profiles') ? 'active' : '' }}">
                                        Profil</a></li>
                                @endif

                                @if ($canShow('Pengaturan'))
                                    <li><a href="{{ url('settings') }}"
                                        class="{{ Request::is('settings') ? 'active' : '' }}">
                                        Pengaturan</a></li>
                                @endif
                            </ul>
                        </li> --}}
                    @endif
                </ul>
            </div>
        </div>
    </div>
    <!-- /Sidebar -->
@endif
