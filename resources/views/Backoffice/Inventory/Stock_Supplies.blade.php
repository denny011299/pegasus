<?php $page = 'stok'; ?>
@extends('layout.mainlayout')
@section('custom_css')
    <style>
        #tableLog {
            width: 100% !important;
            border-collapse: collapse;
        }

        #tableLog thead th {
            position: sticky;
            top: 0;
            z-index: 10;
        }

        #tableLog tbody td {
            vertical-align: middle;
            white-space: normal !important;
            word-wrap: break-word;
        }

        #tableStock {
            width: 100% !important;
            min-width: 800px !important;
        }

        #tableStock td {
            white-space: normal !important;
            word-wrap: break-word;
        }

        #tableStock tbody tr {
            cursor: pointer;
        }

        #tableStock-wrap.is-loading tbody {
            opacity: 0.45;
            pointer-events: none;
        }

        #tableStock-wrap.dt-pending #tableStock {
            min-height: 180px;
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
                    Stok Bahan Mentah
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
                            <div class="table-responsive dt-pending" id="tableStock-wrap">
                                <div class="dt-skeleton" aria-hidden="true">
                                    <div style="padding: 16px 25px;">
                                        <span class="skel-text" style="width: 250px; height: 38px; border-radius: 20px;"></span>
                                    </div>
                                    <div class="dt-skeleton-head" style="grid-template-columns: 75% 25%;">
                                        <span style="width:40%"></span>
                                        <span style="width:60%"></span>
                                    </div>
                                    <div class="dt-skeleton-body">
                                        @for ($i = 0; $i < 5; $i++)
                                            <div class="dt-skeleton-row" style="grid-template-columns: 75% 25%;">
                                                <span class="skel-text" style="width:60%"></span>
                                                <span class="skel-badge" style="width:60%;justify-self:center"></span>
                                            </div>
                                        @endfor
                                    </div>
                                </div>
                                <table class="table table-center table-hover" id="tableStock">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Nama Bahan Mentah</th>
                                            <th>Stok</th>
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
    <script src="{{asset('Custom_js/Backoffice/Inventory/Stock_Supplies.js')}}?v={{time()}}"></script>
@endsection
