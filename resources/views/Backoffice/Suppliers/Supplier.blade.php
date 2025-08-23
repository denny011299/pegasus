<?php $page = 'suppliers'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">
            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    Suppliers
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
                                <table class="table table-center table-hover" id="tableSupplier">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Supplier Name</th>
                                            <th>Supplier Code</th>
                                            <th>Telphone </th>
                                            <th>City</th>
                                            <th>Total Buy</th>
                                            <th>Created</th>
                                            <th class="no-sort">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                      <tr>
                                        <td>CV Jaya Mulya</td>
                                        <td>SUP001</td>
                                        <td>085673627261</td>
                                        <td>Malang</td>
                                        <td>Rp 10.000.000</td>
                                        <td>2025-08-01</td>
                                        <td class="d-flex align-items-center">
                                             <a class="me-2 btn-action-icon p-2 btn_view"  href="/supplierDetail/1">
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
    <script src="{{asset('Custom_js/Backoffice/Suppliers/Supplier.js')}}"></script>
@endsection