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
                <div class="role-testing d-flex align-items-center justify-content-between mt-3 mb-2 px-3 py-3 rounded" style="background: #f8fafc; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                    <h6 class="mb-0 text-muted fw-semibold">Role Terpilih: <span class="ms-2 role_name text-primary fw-bold" style="font-size: 16px;">-</span></h6>
                    <div class="d-flex align-items-center">
                        <label class="custom_check mb-0 d-flex align-items-center" style="cursor: pointer;">
                            <input type="checkbox" name="invoice" class="all_check">
                            <span class="checkmark me-2"></span>
                            <span class="fw-bold text-dark" style="letter-spacing: 0.5px; text-transform: uppercase; font-size: 13px;">Izinkan Semua Modul</span>
                        </label>
                    </div>
                </div>
            </div>
            <!-- /Page Header -->

            <!-- Table -->
            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive dt-pending" id="tablePermission-wrap">
                                <div class="dt-skeleton" aria-hidden="true">
                                    <div style="padding: 16px 25px 16px 25px;">
                                        <span class="skel-text" style="width: 250px; height: 38px; border-radius: 20px;"></span>
                                    </div>
                                    <div class="dt-skeleton-head" style="grid-template-columns: 5% 15% 15% 10% 10% 10% 10% 15% 10%;">
                                        <span style="width:60%"></span>
                                        <span style="width:70%"></span>
                                        <span style="width:50%"></span>
                                        <span style="width:60%"></span>
                                        <span style="width:50%"></span>
                                        <span style="width:60%"></span>
                                        <span style="width:70%"></span>
                                        <span style="width:60%"></span>
                                        <span style="width:60%"></span>
                                    </div>
                                    <div class="dt-skeleton-body">
                                        @for ($i = 0; $i < 8; $i++)
                                            <div class="dt-skeleton-row" style="grid-template-columns: 5% 15% 15% 10% 10% 10% 10% 15% 10%;">
                                                <span class="skel-text" style="width:70%"></span>
                                                <span class="skel-text" style="width:80%"></span>
                                                <span class="skel-text" style="width:60%"></span>
                                                <span class="skel-text" style="width:40%"></span>
                                                <span class="skel-text" style="width:40%"></span>
                                                <span class="skel-text" style="width:40%"></span>
                                                <span class="skel-text" style="width:40%"></span>
                                                <span class="skel-text" style="width:40%"></span>
                                                <span class="skel-text" style="width:40%"></span>
                                            </div>
                                        @endfor
                                    </div>
                                </div>
                               <table class="table table-center table-hover" id="tablePermission" style="width: 100% !important;">
                                    <thead class="thead-lightr">
                                        <tr>
                                            <th>#</th>
                                            <th>Modul</th>
                                            <th>Sub Modul</th>
                                            <th>Buat</th>
                                            <th>Edit</th>
                                            <th>Hapus</th>
                                            <th>Lihat</th>
                                            <th>Acc / Lainnya</th>
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
                                                        <input type="checkbox" name="invoice" class="checkbox others">
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
                <a href="/role" class="btn btn-light cancel me-2" style="padding: 10px 24px; font-weight: 600; border-radius: 8px; border: 1px solid #cbd5e1;">Kembali</a>
                <button type="button" class="btn btn-primary btn-save" style="padding: 10px 24px; font-weight: 600; border-radius: 8px; box-shadow: 0 4px 10px rgba(59, 130, 246, 0.25);">Simpan Perizinan</button>
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