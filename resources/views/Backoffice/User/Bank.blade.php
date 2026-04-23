<?php $page = 'bank'; ?>
@extends('layout.mainlayout')
@section('custom_css')
    <style>
        #tableBank {
            width: 100% !important;
            min-width: 800px;
        }

        #tableBank td {
            white-space: normal !important;
            word-wrap: break-word;
        }

        #tableBank td:last-child {
            white-space: nowrap !important;
        }

        #tableBank td:last-child a {
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
                    Bank
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
                                <table class="table table-center table-hover" id="tableBank">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Bank Kode</th>
                                            <th>Dibuat Pada</th>
                                            <th>Dibuat Oleh</th>
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
    <script src="{{asset('Custom_js/Backoffice/User/Bank.js')}}"></script>
@endsection