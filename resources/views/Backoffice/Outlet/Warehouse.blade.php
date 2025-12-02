<?php $page = 'warehouse'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content">
            @component('components.breadcrumb')
                @slot('title')
                    Warehouse
                @endslot
                @slot('li_1')
                    Manage your warehouse
                @endslot
                @slot('li_2')
                    Add New Warehouse
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
                        <table class="table " id="tableWarehouse">
                            <thead>
                                <tr>
                                    <th>Warehouse</th>
                                    <th>Contact Person</th>
                                    <th>Phone</th>
                                    <th>Total Products</th>
                                    <th>Alamat</th>
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
    <script>
        var public = "{{ asset('') }}";
    </script>
    <script src="{{ asset('/Custom_js/Backoffice/Outlet/Warehouse.js') }}?v={{ time() }}"></script>
@endsection
