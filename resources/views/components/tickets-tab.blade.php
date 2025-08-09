<!-- All Invoice -->
<div class="card invoices-tabs-card">
    <div class="invoices-main-tabs">
        <div class="row align-items-center">
            <div class="col-lg-12">
                <div class="invoices-tabs">
                    <ul>
                        <li><a href="{{ url('tickets-list') }}"
                                class="{{ Request::is('tickets-list') ? 'active' : '' }}">All</a></li>
                        <li><a href="{{ url('tickets-open') }}"
                                class="{{ Request::is('tickets-open') ? 'active' : '' }}">Open</a></li>
                        <li><a href="{{ url('tickets-resolved') }}"
                                class="{{ Request::is('tickets-resolved') ? 'active' : '' }}">Resolved</a></li>
                        <li><a href="{{ url('tickets-pending') }}"
                                class="{{ Request::is('tickets-pending') ? 'active' : '' }}">Pending</a></li>
                        <li><a href="{{ url('tickets-closed') }}"
                                class="{{ Request::is('tickets-closed') ? 'active' : '' }}">Closed</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /All Invoice -->
