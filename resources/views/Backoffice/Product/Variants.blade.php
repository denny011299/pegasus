<?php $page = 'variants'; ?>
@extends('layout.mainlayout')
@section('custom_css')
    <style>
        .bootstrap-tagsinput .tag {
            margin-right: 2px;
            color: #fff;
            background-color: #082a58;
            padding: .2em .4em;
            border-radius: .2rem;
        }
        .bootstrap-tagsinput .tag [data-role="remove"] {
            color: #ffffff !important;
            margin-left: 5px;
            cursor: pointer;
        }
    </style>
@endsection
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    Variants
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
                                <table class="table table-center table-hover" id="tableVariant">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Variant</th>
                                            <th style="width: 40%">Values</th>
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
    <script src="{{asset('Custom_js/Backoffice/Product/Variants.js')}}"></script>
@endsection