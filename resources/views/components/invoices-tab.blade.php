 <!-- All Invoice -->
 <div class="card invoices-tabs-card">
     <div class="invoices-main-tabs">
         <div class="row align-items-center">
             <div class="col-lg-12">
                 <div class="invoices-tabs">
                     <ul>
                         <li><a href="{{ url('invoices') }}" class="{{ Request::is('invoices') ? 'active' : '' }}">All</a>
                         </li>
                         <li><a href="{{ url('invoices-paid') }}"
                                 class="{{ Request::is('invoices-paid') ? 'active' : '' }}">Paid</a></li>
                         <li><a href="{{ url('invoices-overdue') }}"
                                 class="{{ Request::is('invoices-overdue') ? 'active' : '' }}">Overdue</a></li>
                         <li><a href="{{ url('invoices-cancelled') }}"
                                 class="{{ Request::is('invoices-cancelled') ? 'active' : '' }}">Cancelled</a></li>
                         <li><a href="{{ url('invoices-recurring') }}"
                                 class="{{ Request::is('invoices-recurring') ? 'active' : '' }}">Partially Paid</a></li>
                         <li><a href="{{ url('invoices-unpaid') }}"
                                 class="{{ Request::is('invoices-unpaid') ? 'active' : '' }}">Unpaid</a></li>
                         <li><a href="{{ url('invoices-refunded') }}"
                                 class="{{ Request::is('invoices-refunded') ? 'active' : '' }}">Refunded</a></li>
                         <li><a href="{{ url('invoices-draft') }}"
                                 class="{{ Request::is('invoices-draft') ? 'active' : '' }}">Draft</a></li>
                     </ul>
                 </div>
             </div>
         </div>
     </div>
 </div>
 <!-- /All Invoice -->
