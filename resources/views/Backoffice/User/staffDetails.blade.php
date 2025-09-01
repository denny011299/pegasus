<?php $page = 'staff-details'; ?>
@extends('layout.mainlayout')
@section('custom_css')
    <style>
        .staff_image{
            width: 5rem;
        }

        .staff-widget-icon{
            padding-right: 1rem;
        }
    </style>
@endsection
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            <div class="page-header">
                <div class="content-page-header">
                    <h5>Staff Details</h5>
                </div>
            </div>
            <!-- /Page Header -->

            <div class="card staff-details-group">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                            <div class="staff-details">
                                <div class="d-flex align-items-center">
                                    <span class="staff-widget-img d-inline-flex pe-3">
                                        <img class="rounded-circle staff_image"
                                            src="{{ asset($data["staff_image"]) }}" alt="profile-img">
                                    </span>
                                    <div class="staff-details-cont">
                                        <h6>Name</h6>
                                        <p>{{ $data["staff_first_name"] . ' ' . $data["staff_last_name"] }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                            <div class="staff-details">
                                <div class="d-flex align-items-center">
                                    <span class="staff-widget-icon d-inline-flex">
                                        <i class="fe fe-mail"></i>
                                    </span>
                                    <div class="staff-details-cont">
                                        <h6>Email Address</h6>
                                        <p>{{$data["staff_email"]}}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                            <div class="staff-details">
                                <div class="d-flex align-items-center">
                                    <span class="staff-widget-icon d-inline-flex">
                                        <i class="fe fe-phone"></i>
                                    </span>
                                    <div class="staff-details-cont">
                                        <h6>Phone Number</h6>
                                        <p>{{$data["staff_phone"]}}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                            <div class="staff-details">
                                <div class="d-flex align-items-center">
                                    <span class="staff-widget-icon d-inline-flex">
                                        <i class="fe fe-briefcase"></i>
                                    </span>
                                    <div class="staff-details-cont">
                                        <h6>Address</h6>
                                        <p>{{$data["staff_address"]}}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-12 pt-3">
                            <div class="staff-details">
                                <div class="d-flex align-items-center">
                                    <span class="staff-widget-icon d-inline-flex">
                                        <i class="fe fe-calendar"></i>
                                    </span>
                                    <div class="staff-details-cont">
                                        <h6>Birthdate</h6>
                                        <p>{{ \Carbon\Carbon::parse($data["staff_birthdate"])->translatedFormat('d F Y') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-12 pt-3">
                            <div class="staff-details">
                                <div class="d-flex align-items-center">
                                    <span class="staff-widget-icon d-inline-flex">
                                        <i class="fe fe-user"></i>
                                    </span>
                                    <div class="staff-details-cont">
                                        <h6>Departement</h6>
                                        <p>{{$data["staff_departement"]}}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-12 pt-3">
                            <div class="staff-details">
                                <div class="d-flex align-items-center">
                                    <span class="staff-widget-icon d-inline-flex">
                                        <i class="fe fe-user"></i>
                                    </span>
                                    <div class="staff-details-cont">
                                        <h6>Position</h6>
                                        <p>{{$data["staff_position"]}}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-12 pt-3">
                            <div class="staff-details">
                                <div class="d-flex align-items-center">
                                    <span class="staff-widget-icon d-inline-flex">
                                        <i class="fe fe-calendar"></i>
                                    </span>
                                    <div class="staff-details-cont">
                                        <h6>Joining Date</h6>
                                        <p>{{ \Carbon\Carbon::parse($data["staff_join_date"])->translatedFormat('d F Y') }}</p>
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

            {{-- <!-- Inovices card -->
            @component('components.invoices-card')
            @endcomponent
            <!-- /Inovices card --> --}}
           
            <h6 class="mt-4 mb-2">Attendance</h6>
            <!-- Table -->
            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-stripped table-hover datatable" id="tableAttendance">
                                    <thead class="thead-light">
                                       <thead>
                                            <th>Attendance Date</th>
                                            <th>Entry Time</th>
                                            <th>Clock Out</th>
                                            <th>Overtime</th>
                                            <th>Status</th>
                                            <th class="no-sort">Action</th>
                                        </thead>
                                    </thead>
                                    <tbody>
                                        {{-- @php
                                            $json = file_get_contents(public_path('../public/assets/json/staff-details.json'));
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
                                                        <div class="dropdown-menu dropdown-menu-end staff-dropdown">
                                                            <ul>
                                                                <li>
                                                                    <a class="dropdown-item"
                                                                        href="{{ url('edit-customer') }}"><i
                                                                            class="far fa-edit me-2"></i>View</a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item"
                                                                        href="{{ url('staff-details') }}"><i
                                                                            class="far fa-eye me-2"></i>Print Invoice</a>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach --}}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Table -->

            <h6 class="mb-2">Reimburse</h6>
            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-stripped table-hover datatable">
                                    <thead class="thead-light">
                                       <thead>
                                            <th>Date</th>
                                            <th>Reimburse Number</th>
                                            <th>Total Amount</th>
                                            <th>Payment Status</th>
                                            <th class="no-sort">Action</th>
                                        </thead>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>2025-08-01</td>
                                            <td>RB-1001</td>
                                            <td>Rp 15.000.000</td>
                                            <td><span class="badge bg-success-light">Done</span></td>
                                            <td>
                                                <div class="dropdown dropdown-action">
                                                    <a href="#" class=" btn-action-icon " data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </a>
                                                    <div class="dropdown-menu dropdown-menu-end staff-dropdown">
                                                        <ul>
                                                            <li><a class="dropdown-item" href="#"><i class="far fa-edit me-2"></i>View</a></li>
                                                            <li><a class="dropdown-item" href="#"><i class="far fa-eye me-2"></i>Print</a></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>2025-07-28</td>
                                            <td>RB-1002</td>
                                            <td>Rp 7.500.000</td>
                                            <td><span class="badge bg-warning-light text-dark">Invoicing</span></td>
                                            <td>
                                                <div class="dropdown dropdown-action">
                                                    <a href="#" class=" btn-action-icon " data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </a>
                                                    <div class="dropdown-menu dropdown-menu-end staff-dropdown">
                                                        <ul>
                                                            <li><a class="dropdown-item" href="#"><i class="far fa-edit me-2"></i>View</a></li>
                                                            <li><a class="dropdown-item" href="#"><i class="far fa-eye me-2"></i>Print</a></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>2025-07-25</td>
                                            <td>RB-1003</td>
                                            <td>Rp 22.750.000</td>
                                            <td><span class="badge bg-danger-light">Cancled</span></td>
                                            <td>
                                                <div class="dropdown dropdown-action">
                                                    <a href="#" class=" btn-action-icon " data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </a>
                                                    <div class="dropdown-menu dropdown-menu-end staff-dropdown">
                                                        <ul>
                                                            <li><a class="dropdown-item" href="#"><i class="far fa-edit me-2"></i>View</a></li>
                                                            <li><a class="dropdown-item" href="#"><i class="far fa-eye me-2"></i>Print</a></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>2025-07-20</td>
                                            <td>RB-1004</td>
                                            <td>Rp 5.200.000</td>
                                            <td><span class="badge bg-success-light">Done</span></td>
                                            <td>
                                                <div class="dropdown dropdown-action">
                                                    <a href="#" class=" btn-action-icon " data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </a>
                                                    <div class="dropdown-menu dropdown-menu-end staff-dropdown">
                                                        <ul>
                                                            <li><a class="dropdown-item" href="#"><i class="far fa-edit me-2"></i>View</a></li>
                                                            <li><a class="dropdown-item" href="#"><i class="far fa-eye me-2"></i>Print</a></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>2025-07-15</td>
                                            <td>RB-1005</td>
                                            <td>Rp 18.300.000</td>
                                            <td><span class="badge bg-warning-light text-dark">Invoicing</span></td>
                                            <td>
                                                <div class="dropdown dropdown-action">
                                                    <a href="#" class=" btn-action-icon " data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </a>
                                                    <div class="dropdown-menu dropdown-menu-end staff-dropdown">
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

            <h6 class="mt-4 mb-2">Salary</h6>
            <!-- Table -->
            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-stripped table-hover datatable" id="tableSalary">
                                    <thead class="thead-light">
                                       <thead>
                                            <th>Slip Number</th>
                                            <th>Date</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th class="no-sort">Action</th>
                                        </thead>
                                    </thead>
                                    <tbody></tbody>
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

@section('custom_js')
    <script>
        var staff_id = "{{ $data['staff_id'] }}";  
        var public = "{{ asset('') }}";    
    </script>
    <script src="{{asset('Custom_js/Backoffice/User/staffDetail.js')}}"></script>
@endsection