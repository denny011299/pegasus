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

        #tableVariant {
            width: 100% !important;
            min-width: 800px;
        }

        #tableVariant td {
            white-space: normal !important;
            word-wrap: break-word;
        }
        #tableVariant td:last-child {
            white-space: nowrap !important;
        }

        #tableVariant td:last-child a {
            display: inline-flex !important;
            align-items: center;
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
                    Variasi
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
                                            <th>Variasi</th>
                                            <th>Nilai</th>
                                            <th>Dibuat Pada</th>
                                            <th>Dibuat Oleh</th>
                                            {{-- <th>Diapprove Oleh</th> --}}
                                            <th class="no-sort">Aksi</th>
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