<?php $page = 'roles-permission'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">
            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    Roles & Permission
                @endslot
            @endcomponent
            <!-- /Page Header -->

            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-center table-hover datatable">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Role Name</th>
                                            <th>Created at</th>
                                            <th Class="no-sort">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $json = file_get_contents(public_path('../public/assets/json/roles-permission.json'));
                                            $roles = json_decode($json, true);
                                        @endphp
                                        @foreach ($roles as $role)
                                            <tr>
                                                <td>{{ $role['Id'] }}</td>
                                                <td>{{ $role['RoleName'] }}</td>
                                                <td>{{ $role['Createdat'] }}</td>
                                                <td class="d-flex align-items-center">
                                                    <a href="#" class="btn btn-greys me-2" data-bs-toggle="modal"
                                                        data-bs-target="#edit_role"><i class="fa fa-edit me-1"></i> Edit
                                                        Role</a>
                                                    <a href="{{ url('permission') }}" class="btn btn-greys me-2"><i
                                                            class="fa fa-shield me-1"></i> Permissions</a>
                                                </td>
                                            </tr>
                                        @endforeach
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
