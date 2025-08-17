<div class="col-md-6 col-sm-6">
    @if (Route::is(['index']))
        <div class="card mb-0">
    @endif
    @if (!Route::is(['index']))
        <div class="card">
    @endif
    <div class="card-header">
        <div class="row align-center">
            <div class="col">
                <h5 class="card-title">Recent Invoices</h5>
            </div>
            <div class="col-auto">
                <a href="{{ url('invoices') }}" class="btn-right btn btn-sm btn-outline-primary">
                    View All
                </a>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <div class="progress progress-md rounded-pill mb-3">
                <div class="progress-bar bg-success" role="progressbar" style="width: 47%" aria-valuenow="47"
                    aria-valuemin="0" aria-valuemax="100"></div>
                <div class="progress-bar bg-warning" role="progressbar" style="width: 28%" aria-valuenow="28"
                    aria-valuemin="0" aria-valuemax="100"></div>
                <div class="progress-bar bg-danger" role="progressbar" style="width: 15%" aria-valuenow="15"
                    aria-valuemin="0" aria-valuemax="100"></div>
                <div class="progress-bar bg-info" role="progressbar" style="width: 10%" aria-valuenow="10"
                    aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <div class="row">
                <div class="col-auto">
                    <i class="fas fa-circle text-success me-1"></i> Paid
                </div>
                <div class="col-auto">
                    <i class="fas fa-circle text-warning me-1"></i> Unpaid
                </div>
                <div class="col-auto">
                    <i class="fas fa-circle text-danger me-1"></i> Overdue
                </div>
                <div class="col-auto">
                    <i class="fas fa-circle text-info me-1"></i> Draft
                </div>
            </div>
        </div>

        <div class="table-responsive">

            <table class="table table-stripped table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $json = file_get_contents(public_path('../public/assets/json/index-invoices.json'));
                        $invoices = json_decode($json, true);
                    @endphp
                    @foreach ($invoices as $invoice)
                        <tr>
                            <td>
                                <h2 class="table-avatar">
                                    <a href="{{ $invoice['Customer']['profile_link'] }}"><img
                                            class="avatar avatar-sm me-2 avatar-img rounded-circle"
                                            src="{{ URL::asset('/assets/img/profiles/' . $invoice['Customer']['avatar']) }}"
                                            alt="User Image">{{ $invoice['Customer']['name'] }}</a>
                                </h2>
                            </td>
                            <td>{{ $invoice['Amount'] }}</td>
                            <td>{{ $invoice['DueDate'] }}</td>
                            <td><span class="{{ $invoice['Status']['class'] }}">{{ $invoice['Status']['text'] }}</span>
                            </td>
                            <td class="text-end">
                                <div class="dropdown dropdown-action">
                                    <a href="#" class="action-icon dropdown-toggle" data-bs-toggle="dropdown"
                                        aria-expanded="false"><i class="fas fa-ellipsis-h"></i></a>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a class="dropdown-item" href="{{ url('edit-invoice') }}"><i
                                                class="far fa-edit me-2"></i>Edit</a>
                                        <a class="dropdown-item" href="{{ url('invoice-details') }}"><i
                                                class="far fa-eye me-2"></i>View</a>
                                        <a class="dropdown-item" href="javascript:void(0);"><i
                                                class="far fa-trash-alt me-2"></i>Delete</a>
                                        <a class="dropdown-item" href="javascript:void(0);"><i
                                                class="far fa-check-circle me-2"></i>Mark as sent</a>
                                        <a class="dropdown-item" href="javascript:void(0);"><i
                                                class="far fa-paper-plane me-2"></i>Send Invoice</a>
                                        <a class="dropdown-item" href="javascript:void(0);"><i
                                                class="far fa-copy me-2"></i>Clone Invoice</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
