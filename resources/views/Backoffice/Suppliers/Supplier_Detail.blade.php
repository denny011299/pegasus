<?php $page = 'detail-pemasok'; ?>
@extends('layout.mainlayout')
@section('custom_css')
    <style>
        .supplier-details-cont{
            padding: 20px;
        }

        i .fe{
            width: 100px
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
                        Detail Pemasok
                    @endslot
                @endcomponent
            </div>
            <!-- /Page Header -->

            <div class="card supplier-details-group">
                <div class="card-body">
                    <div class="row align-items-center pt-2">
                        <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                            <div class="supplier-details">
                                <div class="d-flex align-items-center">
                                    <span class="supplier-widget-img d-inline-flex" style="width: 60px">
                                        <img class="rounded-circle"
                                            src="{{ URL::asset('/assets/img/profiles/avatar-14.jpg') }}" alt="profile-img">
                                    </span>
                                    <div class="supplier-details-cont">
                                        <h6>John Smith</h6>
                                        <p>Cl-12345</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                            <div class="supplier-details">
                                <div class="d-flex align-items-center">
                                    <span class="supplier-widget-icon d-inline-flex">
                                        <i class="fe fe-mail"></i>
                                    </span>
                                    <div class="supplier-details-cont">
                                        <h6>Alamat Email</h6>
                                        <p>john@example.com</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                            <div class="supplier-details">
                                <div class="d-flex align-items-center">
                                    <span class="supplier-widget-icon d-inline-flex">
                                        <i class="fe fe-phone"></i>
                                    </span>
                                    <div class="supplier-details-cont">
                                        <h6>Nomor Telepon</h6>
                                        <p>585-785-4840</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                            <div class="supplier-details">
                                <div class="d-flex align-items-center">
                                    <span class="supplier-widget-icon d-inline-flex">
                                        <i class="fe fe-airplay"></i>
                                    </span>
                                    <div class="supplier-details-cont">
                                        <h6>Nama Perusahaan</h6>
                                        <p>Kanakku Corporation</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                            <div class="supplier-details">
                                <div class="d-flex align-items-center">
                                    <span class="supplier-widget-icon d-inline-flex">
                                        <i class="fe fe-globe"></i>
                                    </span>
                                    <div class="supplier-details-cont">
                                        <h6>Website</h6>
                                        <p class="supplier-mail">www.example.com</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                            <div class="supplier-details">
                                <div class="d-flex align-items-center">
                                    <span class="supplier-widget-icon d-inline-flex">
                                        <i class="fe fe-briefcase"></i>
                                    </span>
                                    <div class="supplier-details-cont">
                                        <h6>Alamat Perusahaan</h6>
                                        <p>4712 Cherry Ridge Drive Rochester, NY 14620.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Pencarian -->
            @component('components.search-filter')
            @endcomponent
            <!-- /Filter Pencarian -->

            <!-- Kartu Faktur -->
            @component('components.invoices-card')
            @endcomponent
            <!-- /Kartu Faktur -->

            <!-- Tabel -->
            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <div class="row pb-3">
                                    <div class="col-8"></div>
                                    <div class="col-4 text-end">
                                        <a class="btn btn-primary btnAddDn"><i class="fa fa-plus-circle me-2" aria-hidden="true"></i>Tambah Surat Jalan</a>
                                    </div>
                                </div>
                                <div class="col-12 pb-5">
                                    <table class="table table-center table-hover" id="tableDelivery">
                                        <thead>
                                            <th>No. Surat Jalan</th>
                                            <th>Tanggal Pengiriman</th>
                                            <th>Penerima</th>
                                            <th>Alamat</th>
                                            <th>Nomor Telepon</th>
                                            <th>Status</th>
                                            <th class="no-sort">Aksi</th>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Tabel -->
        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection

@section('custom_js')
    <script>
        var public = "{{ asset('') }}";
    </script>
    <script src="{{asset('Custom_js/Backoffice/Suppliers/Supplier_Detail.js')}}"></script>
@endsection
