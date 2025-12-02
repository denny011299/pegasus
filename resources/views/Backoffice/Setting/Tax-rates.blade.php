<?php $page = 'tax-rates'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content settings-content">
            <div class="page-header settings-pg-header">
                <div class="add-item d-flex">
                    <div class="page-title">
                        <h4>Settings</h4>
                        <h6>Manage your settings on portal</h6>
                    </div>
                </div>
                <ul class="table-top-head">
                    <li>
                        <a data-bs-toggle="tooltip" data-bs-placement="top" title="Refresh"><i data-feather="rotate-ccw"
                                class="feather-rotate-ccw"></i></a>
                    </li>
                    <li>
                        <a data-bs-toggle="tooltip" data-bs-placement="top" title="Collapse" id="collapse-header"><i
                                data-feather="chevron-up" class="feather-chevron-up"></i></a>
                    </li>
                </ul>
            </div>
            <div class="row">
                <div class="col-xl-12">
                    <div class="settings-wrapper d-flex">
                        @component('Backoffice.Setting.settings-sidebar')
                        @endcomponent
                        <div class="settings-page-wrap w-50">
                            <div class="setting-title">
                                <h4>Tax Rates</h4>
                            </div>
                            <div class="page-header bank-settings justify-content-end">
                                <div class="page-btn">
                                    <a href="#" class="btn btn-added btnAdd"><i data-feather="plus-circle"
                                            class="me-2"></i>Add New
                                        Tax Rate</a>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12">
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
                                                <table class="table" id="tableTax">
                                                    <thead>
                                                        <tr>
                                                            <th>Name</th>
                                                            <th>Tax rates% </th>
                                                            <th>Created On</th>
                                                            <th class="no-sort text-end">Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('custom_js')
    <script src="{{ asset('/Custom_js/Backoffice/Setting/Tax.js') }}?v={{ time() }}"></script>
@endsection
