<?php $page = 'customers'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">
            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    Customers
                @endslot
            @endcomponent
            <!-- /Page Header -->

            <!-- Search Filter -->
            @component('components.search-filter')
            @endcomponent
            <!-- /Search Filter -->

            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-center table-hover datatable">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Customer Name</th>
                                            <th>Customer Code</th>
                                            <th>Telphone </th>
                                            <th>City</th>
                                            <th>Total Spent</th>
                                            <th>Created</th>
                                            <th class="no-sort">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                      <tr>
                                        <td>Andi Wijaya</td>
                                        <td>CUST001</td>
                                        <td>081234567890</td>
                                        <td>Jakarta</td>
                                        <td>Rp 15.000.000</td>
                                        <td>2025-08-01</td>
                                        <td class="d-flex align-items-center">
                                             <a class="me-2 btn-action-icon p-2 btn_view"  href="/customerDetail/1">
                                                <i data-feather="eye" class="feather-edit"></i>
                                            </a>
                                            <a class="me-2 btn-action-icon p-2 btn_edit"  data-bs-target="#edit-category">
                                                <i data-feather="edit" class="feather-edit"></i>
                                            </a>
                                            <a class="p-2 btn-action-icon btn_delete"  href="javascript:void(0);">
                                                <i data-feather="trash-2" class="feather-trash-2"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Siti Rahma</td>
                                        <td>CUST002</td>
                                        <td>082198765432</td>
                                        <td>Bandung</td>
                                        <td>Rp 7.500.000</td>
                                        <td>2025-07-20</td>
                                       <td class="d-flex align-items-center">
                                         <a class="me-2 btn-action-icon p-2 btn_view"  data-bs-target="#edit-category">
                                                <i data-feather="eye" class="feather-edit"></i>
                                            </a>
                                            <a class="me-2 btn-action-icon p-2 btn_edit"  data-bs-target="#edit-category">
                                                <i data-feather="edit" class="feather-edit"></i>
                                            </a>
                                            <a class="p-2 btn-action-icon btn_delete"  href="javascript:void(0);">
                                                <i data-feather="trash-2" class="feather-trash-2"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Budi Santoso</td>
                                        <td>CUST003</td>
                                        <td>081356789012</td>
                                        <td>Surabaya</td>
                                        <td>Rp 22.750.000</td>
                                        <td>2025-07-15</td>
                                       <td class="d-flex align-items-center">
                                         <a class="me-2 btn-action-icon p-2 btn_view"  data-bs-target="#edit-category">
                                                <i data-feather="eye" class="feather-edit"></i>
                                            </a>
                                            <a class="me-2 btn-action-icon p-2 btn_edit"  data-bs-target="#edit-category">
                                                <i data-feather="edit" class="feather-edit"></i>
                                            </a>
                                            <a class="p-2 btn-action-icon btn_delete"  href="javascript:void(0);">
                                                <i data-feather="trash-2" class="feather-trash-2"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Maria Angela</td>
                                        <td>CUST004</td>
                                        <td>081298765432</td>
                                        <td>Yogyakarta</td>
                                        <td>Rp 5.200.000</td>
                                        <td>2025-07-10</td>
                                        <td class="d-flex align-items-center">
                                             <a class="me-2 btn-action-icon p-2 btn_view"  data-bs-target="#edit-category">
                                                <i data-feather="eye" class="feather-edit"></i>
                                            </a>
                                            <a class="me-2 btn-action-icon p-2 btn_edit"  data-bs-target="#edit-category">
                                                <i data-feather="edit" class="feather-edit"></i>
                                            </a>
                                            <a class="p-2 btn-action-icon btn_delete"  href="javascript:void(0);">
                                                <i data-feather="trash-2" class="feather-trash-2"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Joko Prasetyo</td>
                                        <td>CUST005</td>
                                        <td>083812345678</td>
                                        <td>Medan</td>
                                        <td>Rp 18.300.000</td>
                                        <td>2025-07-05</td>
                                        <td class="d-flex align-items-center">
                                            <a class="me-2 btn-action-icon p-2 btn_view"  data-bs-target="#edit-category">
                                                <i data-feather="eye" class="feather-edit"></i>
                                            </a>
                                            <a class="me-2 btn-action-icon p-2 btn_edit"  data-bs-target="#edit-category">
                                                <i data-feather="edit" class="feather-edit"></i>
                                            </a>
                                            <a class="p-2 btn-action-icon btn_delete"  href="javascript:void(0);">
                                                <i data-feather="trash-2" class="feather-trash-2"></i>
                                            </a>
                                        </td>
                                    </tr>

                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection
@section('custom_js')
    <script>
        var public = "{{ asset('') }}";    
    </script>
    <script src="{{asset('Custom_js/Backoffice/Product/Product.js')}}"></script>
@endsection