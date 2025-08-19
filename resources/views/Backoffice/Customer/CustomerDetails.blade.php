<?php $page = 'customer-details'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            <div class="page-header">
                <div class="content-page-header">
                    <h5>Customer Details</h5>
                </div>
            </div>
            <!-- /Page Header -->

            <div class="card customer-details-group">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                            <div class="customer-details">
                                <div class="d-flex align-items-center">
                                    <span class="customer-widget-img d-inline-flex">
                                        <img class="rounded-circle"
                                            src="{{ URL::asset('/assets/img/profiles/avatar-14.jpg') }}" alt="profile-img">
                                    </span>
                                    <div class="customer-details-cont">
                                        <h6>John Smith</h6>
                                        <p>Cl-12345</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                            <div class="customer-details">
                                <div class="d-flex align-items-center">
                                    <span class="customer-widget-icon d-inline-flex">
                                        <i class="fe fe-mail"></i>
                                    </span>
                                    <div class="customer-details-cont">
                                        <h6>Email Address</h6>
                                        <p>john@example.com</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                            <div class="customer-details">
                                <div class="d-flex align-items-center">
                                    <span class="customer-widget-icon d-inline-flex">
                                        <i class="fe fe-phone"></i>
                                    </span>
                                    <div class="customer-details-cont">
                                        <h6>Phone Number</h6>
                                        <p>585-785-4840</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                            <div class="customer-details">
                                <div class="d-flex align-items-center">
                                    <span class="customer-widget-icon d-inline-flex">
                                        <i class="fe fe-briefcase"></i>
                                    </span>
                                    <div class="customer-details-cont">
                                        <h6>Company Address</h6>
                                        <p>4712 Cherry Ridge Drive Rochester, NY 14620.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Filter -->
            @component('components.search-filter')
            @endcomponent
            <!-- /Search Filter -->

            <!-- Inovices card -->
            @component('components.invoices-card')
            @endcomponent
            <!-- /Inovices card -->
           
            <!-- SO -->
            <h6 class="mb-2">Sales Order</h6>
            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-stripped table-hover datatable">
                                    <thead class="thead-light">
                                       <thead>
                                            <th>Customer Name</th>
                                            <th>Reference</th>
                                            <th>Date</th>
                                            <th>Total Amount</th>
                                            <th>Paid</th>
                                            <th>Difference</th>
                                            <th>Payment Status</th>
                                            <th>Handled By</th>
                                            <th class="no-sort">Action</th>
                                        </thead>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Andi Wijaya</td>
                                            <td>SO-1001</td>
                                            <td>2025-08-01</td>
                                            <td>Rp 15.000.000</td>
                                            <td>Rp 15.000.000</td>
                                            <td>Rp 0</td>
                                            <td><span class="badge bg-success-light">Done</span></td>
                                            <td>Admin 1</td>
                                            <td>
                                                <div class="dropdown dropdown-action">
                                                    <a href="#" class=" btn-action-icon " data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </a>
                                                    <div class="dropdown-menu dropdown-menu-end customer-dropdown">
                                                        <ul>
                                                            <li><a class="dropdown-item" href="#"><i class="far fa-edit me-2"></i>View</a></li>
                                                            <li><a class="dropdown-item" href="#"><i class="far fa-eye me-2"></i>Print</a></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Siti Rahma</td>
                                            <td>SO-1002</td>
                                            <td>2025-07-28</td>
                                            <td>Rp 7.500.000</td>
                                            <td>Rp 3.500.000</td>
                                            <td>Rp 4.000.000</td>
                                            <td><span class="badge bg-warning-light text-dark">Invoicing</span></td>
                                            <td>Kasir 2</td>
                                            <td>
                                                <div class="dropdown dropdown-action">
                                                    <a href="#" class=" btn-action-icon " data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </a>
                                                    <div class="dropdown-menu dropdown-menu-end customer-dropdown">
                                                        <ul>
                                                            <li><a class="dropdown-item" href="#"><i class="far fa-edit me-2"></i>View</a></li>
                                                            <li><a class="dropdown-item" href="#"><i class="far fa-eye me-2"></i>Print</a></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Budi Santoso</td>
                                            <td>SO-1003</td>
                                            <td>2025-07-25</td>
                                            <td>Rp 22.750.000</td>
                                            <td>Rp 0</td>
                                            <td>Rp 22.750.000</td>
                                            <td><span class="badge bg-danger-light">Cancled</span></td>
                                            <td>Kasir 1</td>
                                            <td>
                                                <div class="dropdown dropdown-action">
                                                    <a href="#" class=" btn-action-icon " data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </a>
                                                    <div class="dropdown-menu dropdown-menu-end customer-dropdown">
                                                        <ul>
                                                            <li><a class="dropdown-item" href="#"><i class="far fa-edit me-2"></i>View</a></li>
                                                            <li><a class="dropdown-item" href="#"><i class="far fa-eye me-2"></i>Print</a></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Maria Angela</td>
                                            <td>SO-1004</td>
                                            <td>2025-07-20</td>
                                            <td>Rp 5.200.000</td>
                                            <td>Rp 5.200.000</td>
                                            <td>Rp 0</td>
                                            <td><span class="badge bg-success-light">Done</span></td>
                                            <td>Kasir 3</td>
                                            <td>
                                                <div class="dropdown dropdown-action">
                                                    <a href="#" class=" btn-action-icon " data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </a>
                                                    <div class="dropdown-menu dropdown-menu-end customer-dropdown">
                                                        <ul>
                                                            <li><a class="dropdown-item" href="#"><i class="far fa-edit me-2"></i>View</a></li>
                                                            <li><a class="dropdown-item" href="#"><i class="far fa-eye me-2"></i>Print</a></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Joko Prasetyo</td>
                                            <td>SO-1005</td>
                                            <td>2025-07-15</td>
                                            <td>Rp 18.300.000</td>
                                            <td>Rp 10.000.000</td>
                                            <td>Rp 8.300.000</td>
                                            <td><span class="badge bg-warning-light text-dark">Invoicing</span></td>
                                            <td>Admin 2</td>
                                            <td>
                                                <div class="dropdown dropdown-action">
                                                    <a href="#" class=" btn-action-icon " data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </a>
                                                    <div class="dropdown-menu dropdown-menu-end customer-dropdown">
                                                        <ul>
                                                            <li><a class="dropdown-item" href="#"><i class="far fa-edit me-2"></i>View</a></li>
                                                            <li><a class="dropdown-item" href="#"><i class="far fa-eye me-2"></i>Print</a></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Table -->

             <h6 class="mt-4 mb-2">Invoice</h6>
            <!-- Table -->
            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-stripped table-hover datatable">
                                    <thead class="thead-light">
                                       <thead>
                                            <th>Invoice No.</th>
                                            <th>Invoice Date</th>
                                            <th>Due Date</th>
                                            <th>Total Invoice</th>
                                            <th>Total Paid</th>
                                            <th>Status</th>
                                            <th class="no-sort">Action</th>
                                        </thead>
                                    </thead>
                                    <tbody>
                                        @php
                                            $json = file_get_contents(public_path('../public/assets/json/customer-details.json'));
                                            $customers = json_decode($json, true);
                                        @endphp
                                        @foreach ($customers as $customer)
                                            <tr>
                                                <td>
                                                    <a href="{{ url('invoice-details') }}"
                                                        class="invoice-link">{{ $customer['InvoiceNo'] }}</a>
                                                </td>
                                                <td>{{ $customer['CreatedOn'] }}</td>
                                                <td>{{ $customer['DueDate'] }}</td>
                                                <td>{{ $customer['TotalAmount'] }}</td>
                                                <td>{{ $customer['PaidAmount'] }}</td>
                                                <td><span
                                                        class="{{ $customer['Class'] }}">{{ $customer['Status'] }}</span>
                                                </td>
                                                <td>
                                                    <div class="dropdown dropdown-action">
                                                        <a href="#" class=" btn-action-icon "
                                                            data-bs-toggle="dropdown" aria-expanded="false"><i
                                                                class="fas fa-ellipsis-v"></i></a>
                                                        <div class="dropdown-menu dropdown-menu-end customer-dropdown">
                                                            <ul>
                                                                <li>
                                                                    <a class="dropdown-item"
                                                                        href="{{ url('edit-customer') }}"><i
                                                                            class="far fa-edit me-2"></i>View</a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item"
                                                                        href="{{ url('customer-details') }}"><i
                                                                            class="far fa-eye me-2"></i>Print Invoice</a>
                                                                </li>
                                                            </ul>
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
            </div>
            <!-- /Table -->
        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection
