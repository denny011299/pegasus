<?php $page = 'customer-details'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            <div class="page-header">
                @component('components.page-header')
                    @slot('title')
                        Detail Armada
                    @endslot
                @endcomponent
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
                                        <h6>Alamat Email</h6>
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
                                        <h6>Nomor Telepon</h6>
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
                                        <h6>Alamat Perusahaan</h6>
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
            <h6 class="mb-2">Pesanan Penjualan</h6>
            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-stripped table-hover datatable">
                                    <thead class="thead-light">
                                       <thead>
                                            <th>Nama Armada</th>
                                            <th>Referensi</th>
                                            <th>Tanggal</th>
                                            <th>Total</th>
                                            <th>Dibayar</th>
                                            <th>Selisih</th>
                                            <th>Status Pembayaran</th>
                                            <th>Ditangani Oleh</th>
                                            <th class="no-sort">Aksi</th>
                                        </thead>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Table -->

             <h6 class="mt-4 mb-2">Faktur</h6>
            <!-- Table -->
            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-stripped table-hover datatable">
                                    <thead class="thead-light">
                                       <thead>
                                            <th>No. Faktur</th>
                                            <th>Tanggal Faktur</th>
                                            <th>Jatuh Tempo</th>
                                            <th>Total Faktur</th>
                                            <th>Total Dibayar</th>
                                            <th>Status</th>
                                            <th class="no-sort">Aksi</th>
                                        </thead>
                                    </thead>
                                    <tbody>
                                        
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

@section('custom_js')
    <script>
        var public = "{{ asset('') }}";
    </script>
    <script src="{{asset('Custom_js/Backoffice/Customers/Customer_Detail.js')}}"></script>
@endsection
