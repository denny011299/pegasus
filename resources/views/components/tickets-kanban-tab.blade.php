<!-- All Invoice -->
<div class="card invoices-tabs-card">
    <div class="invoices-main-tabs">
        <div class="row align-items-center">
            <div class="col-lg-12">
                <div class="invoices-tabs">
                    <ul>
                        <li><a href="{{ url('tickets-kanban') }}"
                                class="{{ Request::is('tickets-kanban') ? 'active' : '' }}">All</a></li>
                        <li><a href="{{ url('tickets-list-open') }}"
                                class="{{ Request::is('tickets-list-open') ? 'active' : '' }}">Open</a></li>
                        <li><a href="{{ url('tickets-list-resolved') }}"
                                class="{{ Request::is('tickets-list-resolved') ? 'active' : '' }}">Resolved</a></li>
                        <li><a href="{{ url('tickets-list-pending') }}"
                                class="{{ Request::is('tickets-list-pending') ? 'active' : '' }}">Pending</a></li>
                        <li><a href="{{ url('tickets-list-closed') }}"
                                class="{{ Request::is('tickets-list-closed') ? 'active' : '' }}">Closed</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /All Invoice -->
