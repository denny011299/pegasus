<?php $page = 'roles-permissions'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content">
            @component('components.breadcrumb')
                @slot('title')
                    Roles & Permission
                @endslot
                @slot('li_1')
                    Manage your roles
                @endslot
                @slot('li_2')
                    Add New Role
                @endslot
            @endcomponent

            <!-- /product list -->
            <div class="card table-list-card">
                <div class="card-body">
                    <div class="table-top">
                        <div class="search-set">
                            <div class="search-input">
                                <a href="" class="btn btn-searchset"><i data-feather="search"
                                        class="feather-search"></i></a>
                            </div>
                        </div>

                    </div>

                    <div class="table-responsive">
                        <table class="table  " id="tableRole">
                            <thead>
                                <tr>
                                    <th>Role Name</th>
                                    <th>Created On</th>
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
    <script src="{{ asset('/Custom_js/Backoffice/UserManagement/Role.js') }}?v={{ time() }}"></script>
@endsection
