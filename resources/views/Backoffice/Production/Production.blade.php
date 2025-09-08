<?php $page = 'product_issues'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

             <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    Production
                @endslot
            @endcomponent
            <!-- /Page Header -->

            <!-- Search Filter -->
            @component('components.search-filter')
            @endcomponent
            <!-- /Search Filter -->

            <!-- Table -->
            <div class="row">
                <div class="col-sm-12">
                    <div class=" card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-center table-hover" id="tableProduction">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Product</th>
                                            <th>SKU</th>
                                            <th>Qty</th>
                                            <th class="no-sort">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Table -->

        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection

@section('custom_js')
    <script>
        var public = "{{ asset('') }}";
    </script>
    <script src="{{asset('Custom_js/Backoffice/Production/Production.js')}}"></script>
@endsection