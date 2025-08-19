<?php $page = 'permission'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            <div class="page-header">
                <div class="content-page-header">
                    <h5>Permission</h5>
                </div>
                <div class="role-testing d-flex align-items-center justify-content-between">
                    <h6>Role Name:<span class="ms-1">Testings</span></h6>
                    <p><label class="custom_check"><input type="checkbox" name="invoice"><span
                                class="checkmark"></span></label>Allow All Modules</p>
                </div>
            </div>
            <!-- /Page Header -->

            <!-- Table -->
            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                               <table class="table table-center table-hover datatable">
                                    <thead class="thead-lightr">
                                        <tr>
                                            <th>#</th>
                                            <th>Modules</th>
                                            <th>Sub Modules</th>
                                            <th>Create</th>
                                            <th>Edit</th>
                                            <th>Delete</th>
                                            <th>View</th>
                                            <th>Allow all</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $json = file_get_contents(public_path('../public/assets/json/permission.json'));
                                            $permissions = json_decode($json, true);
                                        @endphp
                                        @foreach ($permissions as $permission)
                                            <tr>
                                                <td>{{ $permission['Id'] }}</td>
                                                <td class="role-data">{{ $permission['Modules'] }}</td>
                                                <td>{{ $permission['SubModules'] }}</td>
                                                <td>
                                                    <label class="custom_check">
                                                        <input type="checkbox" name="invoice">
                                                        <span class="checkmark"></span>
                                                    </label>
                                                </td>
                                                <td>
                                                    <label class="custom_check">
                                                        <input type="checkbox" name="invoice">
                                                        <span class="checkmark"></span>
                                                    </label>
                                                </td>
                                                <td>
                                                    <label class="custom_check">
                                                        <input type="checkbox" name="invoice">
                                                        <span class="checkmark"></span>
                                                    </label>
                                                </td>
                                                <td>
                                                    <label class="custom_check">
                                                        <input type="checkbox" name="invoice">
                                                        <span class="checkmark"></span>
                                                    </label>
                                                </td>
                                                <td>
                                                    <label class="custom_check">
                                                        <input type="checkbox" name="invoice">
                                                        <span class="checkmark"></span>
                                                    </label>
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

            <div class="btn-center my-4">
                <button type="submit" class="btn btn-primary cancel me-2">Back</button>
                <button type="submit" class="btn btn-primary">Update</button>
            </div>

        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection
