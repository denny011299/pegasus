<?php $page = 'department-list'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content">
            <div class="page-header">
                <div class="add-item d-flex">
                    <div class="page-title">
                        <h4>Department</h4>
                        <h6>Manage your departments</h6>
                    </div>
                </div>
                <ul class="table-top-head">
                    <li>
                        <a data-bs-toggle="tooltip" data-bs-placement="top" title="Pdf"><img
                                src="{{ URL::asset('/build/img/icons/pdf.svg') }}" alt="img"></a>
                    </li>
                    <li>
                        <a data-bs-toggle="tooltip" data-bs-placement="top" title="Excel"><img
                                src="{{ URL::asset('/build/img/icons/excel.svg') }}" alt="img"></a>
                    </li>

                </ul>
                <div class="page-btn">
                    <a href="" class="btn btn-added" data-bs-toggle="modal" data-bs-target="#add-department"><i
                            data-feather="plus-circle" class="me-2"></i>Add New Department</a>
                </div>
            </div>
            <!-- /product list -->
            <div class="card table-list-card">
                <div class="card-body pb-0">
                    <div class="table-top table-top-new">

                        <div class="search-set mb-0">
                            <div class="total-employees">
                                <h6><i data-feather="users" class="feather-user"></i>Total Employees <span
                                        id="totalStaff">0</span></h6>
                            </div>
                            <div class="search-input">
                                <a href="" class="btn btn-searchset"><i data-feather="search"
                                        class="feather-search"></i></a>

                            </div>

                        </div>
                        <div class="search-path d-flex align-items-center search-path-new">

                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table " id="tableDepartment">
                            <thead>
                                <tr>
                                    <th>Department</th>
                                    <th>Total Members</th>
                                    <th>Created On</th>
                                    <th>Status</th>
                                    <th class="no-sort">Action</th>
                                </tr>
                            </thead>
                            <tbody>

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- /product list -->

        </div>
    </div>
@endsection

@section('custom_js')
    <script src="{{ asset('/Custom_js/Backoffice/UserManagement/Department.js') }}?v={{ time() }}"></script>
@endsection
