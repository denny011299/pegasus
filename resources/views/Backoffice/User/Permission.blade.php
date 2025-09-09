<?php $page = 'permission'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            <div class="page-header">
                @component('components.page-header')
                    @slot('title')
                        Izin Akses
                    @endslot
                @endcomponent
                <div class="role-testing d-flex align-items-center justify-content-between">
                    <h6>Nama Role:<span class="ms-1 role_name">-</span></h6>
                    <p><label class="custom_check"><input type="checkbox" name="invoice" class="all_check"><span
                                class="checkmark"></span></label>Izinkan Semua Modul</p>
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
                                            <th>Modul</th>
                                            <th>Sub Modul</th>
                                            <th>Buat</th>
                                            <th>Edit</th>
                                            <th>Hapus</th>
                                            <th>Lihat</th>
                                            <th>Izinkan Semua</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $json = file_get_contents(public_path('../public/assets/json/permission.json'));
                                            $permissions = json_decode($json, true);
                                        @endphp
                                        @foreach ($permissions as $permission)
                                            <tr class="row-module" module="{{ $permission['SubModules'] }}">
                                                <td>{{ $permission['Id'] }}</td>
                                                <td class="role-data">{{ $permission['Modules'] }}</td>
                                                <td>{{ $permission['SubModules'] }}</td>
                                                <td>
                                                    <label class="custom_check">
                                                        <input type="checkbox" name="invoice" class="checkbox create">
                                                        <span class="checkmark"></span>
                                                    </label>
                                                </td>
                                                <td>
                                                    <label class="custom_check">
                                                        <input type="checkbox" name="invoice" class="checkbox edit">
                                                        <span class="checkmark"></span>
                                                    </label>
                                                </td>
                                                <td>
                                                    <label class="custom_check">
                                                        <input type="checkbox" name="invoice" class="checkbox delete">
                                                        <span class="checkmark"></span>
                                                    </label>
                                                </td>
                                                <td>
                                                    <label class="custom_check">
                                                        <input type="checkbox" name="invoice" class="checkbox view">
                                                        <span class="checkmark"></span>
                                                    </label>
                                                </td>
                                                <td>
                                                    <label class="custom_check">
                                                        <input type="checkbox" name="invoice" class="checkbox all">
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
                <a href="/role" class="btn btn-primary cancel me-2">Kembali</a>
                <button type="submit" class="btn btn-primary btn-save">Perbarui</button>
            </div>
        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection

@section('custom_js')
    <script>
        var mode=2;
        var data = @json($data);
        var perm = data?JSON.parse(data.role_access):[];
    </script>
    <script src="{{ asset('/Custom_js/Backoffice/User/Permission.js') }}"></script>
@endsection