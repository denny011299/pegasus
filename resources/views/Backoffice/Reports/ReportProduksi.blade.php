<?php $page = 'cash'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    Production Report
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
                                <table class="table table-center table-hover" id="tableProduct">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Production Date</th>
                                            <th>Product Name</th>
                                            <th>Qty</th>
                                            <th>Status</th>
                                            <th>Input by</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>02 August 2025</td>
                                            <td>Hand Sanitizer 250ml</td>
                                            <td>1,200</td>
                                            <td><span class="badge bg-success-light">Completed</span></td>
                                            <td>Admin</td>
                                        </tr>
                                        <tr>
                                            <td>06 August 2025</td>
                                            <td>Liquid Soap 1L</td>
                                            <td>500</td>
                                            <td><span class="badge bg-success-light">Completed</span></td>
                                            <td>Operator</td>
                                        </tr>
                                        <tr>
                                            <td>08 August 2025</td>
                                            <td>Bleaching Powder 25kg</td>
                                            <td>200</td>
                                            <td><span class="badge bg-success-light">Completed</span></td>
                                            <td>Admin</td>
                                        </tr>
                                        <tr>
                                            <td>09 August 2025</td>
                                            <td>Alcohol 70% 5L</td>
                                            <td>300</td>
                                            <td><span class="badge bg-danger-light">Cancelled</span></td>
                                            <td>Manager</td>
                                        </tr>
                                    </tbody>

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
    <script src="{{asset('Custom_js/Backoffice/Reports/Cash.js')}}"></script>
@endsection
