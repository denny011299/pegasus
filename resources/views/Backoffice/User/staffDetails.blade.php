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
                @component('components.page-header')
                    @slot('title')
                        Detail Staff
                    @endslot
                @endcomponent
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
                                        <h6>Nama</h6>
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
                                        <h6>Alamat Email</h6>
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
                                        <h6>Nomor Telepon</h6>
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
                                        <h6>Alamat</h6>
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
                                        <h6>Tanggal Lahir</h6>
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
                                        <h6>Departemen</h6>
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
                                        <h6>Jabatan</h6>
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
                                        <h6>Tanggal Bergabung</h6>
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
           
            <h6 class="mt-4 mb-2">Kehadiran</h6>
            <!-- Table -->
            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-stripped table-hover datatable" id="tableAttendance">
                                    <thead class="thead-light">
                                       <thead>
                                            <th>Tanggal Kehadiran</th>
                                            <th>Jam Masuk</th>
                                            <th>Jam Pulang</th>
                                            <th>Lembur</th>
                                            <th>Status</th>
                                            <th class="no-sort">Aksi</th>
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

            <h6 class="mb-2">Penggantian Biaya</h6>
            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-stripped table-hover datatable">
                                    <thead class="thead-light">
                                       <thead>
                                            <th>Tanggal</th>
                                            <th>Nomor Reimburse</th>
                                            <th>Total Biaya</th>
                                            <th>Status Pembayaran</th>
                                            <th class="no-sort">Aksi</th>
                                        </thead>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>2025-08-01</td>
                                            <td>RB-1001</td>
                                            <td>Rp 15.000.000</td>
                                            <td><span class="badge bg-success-light">Selesai</span></td>
                                            <td>...</td>
                                        </tr>
                                        <tr>
                                            <td>2025-07-28</td>
                                            <td>RB-1002</td>
                                            <td>Rp 7.500.000</td>
                                            <td><span class="badge bg-warning-light text-dark">Proses Penagihan</span></td>
                                            <td>...</td>
                                        </tr>
                                        <tr>
                                            <td>2025-07-25</td>
                                            <td>RB-1003</td>
                                            <td>Rp 22.750.000</td>
                                            <td><span class="badge bg-danger-light">Dibatalkan</span></td>
                                            <td>...</td>
                                        </tr>
                                        <tr>
                                            <td>2025-07-20</td>
                                            <td>RB-1004</td>
                                            <td>Rp 5.200.000</td>
                                            <td><span class="badge bg-success-light">Selesai</span></td>
                                            <td>...</td>
                                        </tr>
                                        <tr>
                                            <td>2025-07-15</td>
                                            <td>RB-1005</td>
                                            <td>Rp 18.300.000</td>
                                            <td><span class="badge bg-warning-light text-dark">Proses Penagihan</span></td>
                                            <td>...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Table -->

            <h6 class="mt-4 mb-2">Gaji</h6>
            <!-- Table -->
            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-stripped table-hover datatable" id="tableSalary">
                                    <thead class="thead-light">
                                       <thead>
                                            <th>Nomor Slip</th>
                                            <th>Tanggal</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th class="no-sort">Aksi</th>
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
        var public = "{{ asset('') }}";    
    </script>
    <script src="{{asset('Custom_js/Backoffice/User/staffDetail.js')}}"></script>
@endsection