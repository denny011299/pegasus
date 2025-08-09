<div class="widget settings-menu mb-0">
    <ul>
        <li class="nav-item">
            <a href="{{ url('settings') }}" class="nav-link {{ Request::is('settings') ? 'active' : '' }}">
                <i class="fe fe-user"></i> <span>Account Settings</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ url('company-settings') }}"
                class="nav-link {{ Request::is('company-settings') ? 'active' : '' }}">
                <i class="fe fe-settings"></i> <span>Company Settings</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ url('invoice-settings') }}"
                class="nav-link {{ Request::is('invoice-settings') ? 'active' : '' }}">
                <i class="fe fe-file"></i> <span>Invoice Settings</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ url('template-invoice') }}"
                class="nav-link {{ Request::is('template-invoice') ? 'active' : '' }}">
                <i class="fe fe-layers"></i> <span>Invoice Templates</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ url('payment-settings') }}"
                class="nav-link {{ Request::is('payment-settings') ? 'active' : '' }}">
                <i class="fe fe-credit-card"></i> <span>Payment Methods</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ url('bank-account') }}" class="nav-link {{ Request::is('bank-account') ? 'active' : '' }}">
                <i class="fe fe-aperture"></i> <span>Bank Settings</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ url('tax-rates') }}" class="nav-link {{ Request::is('tax-rates') ? 'active' : '' }}">
                <i class="fe fe-file-text"></i> <span>Tax Rates</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ url('plan-billing') }}" class="nav-link {{ Request::is('plan-billing') ? 'active' : '' }}">
                <i class="fe fe-credit-card"></i> <span>Plan & Billing</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ url('two-factor') }}" class="nav-link {{ Request::is('two-factor') ? 'active' : '' }}">
                <i class="fe fe-aperture"></i> <span>Two Factor</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ url('custom-filed') }}" class="nav-link {{ Request::is('custom-filed') ? 'active' : '' }}">
                <i class="fe fe-file-text"></i> <span>Custom Fields</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ url('email-settings') }}"
                class="nav-link {{ Request::is('email-settings') ? 'active' : '' }}">
                <i class="fe fe-mail"></i> <span>Email Settings</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ url('preferences') }}" class="nav-link {{ Request::is('preferences') ? 'active' : '' }}">
                <i class="fe fe-settings"></i> <span>Preference Settings</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{url('email-template')}}" class="nav-link {{ Request::is('email-template') ? 'active' : '' }}">
                <i class="fe fe-airplay"></i> <span>Email Templates</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{url('seo-settings')}}" class="nav-link {{ Request::is('seo-settings') ? 'active' : '' }}">
                <i class="fe fe-send"></i> <span>SEO Settings</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{url('saas-settings')}}" class="nav-link {{ Request::is('saas-settings') ? 'active' : '' }}">
                <i class="fe fe-target"></i> <span>SaaS Settings</span>
            </a>
        </li>
    </ul>
</div>
