<?php $page = 'product'; ?>
@extends('layout.mainlayout')
@section('custom_css')
    <style>
        #tableProduct {
            width: 100% !important;
        }

        #tableProduct th,
        #tableProduct td {
            vertical-align: middle;
            white-space: normal !important;
            word-wrap: break-word;
        }

        #tableProduct td:last-child,
        #tableProduct th:last-child {
            white-space: nowrap !important;
            width: 10%;
        }

        #tableProduct td:last-child a {
            display: inline-flex !important;
            align-items: center;
        }

        /* Hindari DataTables clone header (scrollX) yang nyasar */
        #tableProduct-wrap .dataTables_scrollHead,
        #tableProduct-wrap .dataTables_scrollBody {
            width: 100% !important;
        }

        /* Ikuti event processing DataTables; override loader global yang memaksa display. */
        #tableProduct_wrapper:not(.is-processing) .dataTables_processing {
            display: none !important;
        }

        #tableProduct_wrapper.is-processing .dataTables_processing {
            display: flex !important;
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
                    Produk
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
                            <div class="table-responsive" id="tableProduct-wrap">
                                <table class="table table-center table-hover" id="tableProduct">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Nama Produk</th>
                                            <th>Kategori</th>
                                            <th>Satuan</th>
                                            <th>Variasi</th>
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
    <script src="{{asset('Custom_js/Backoffice/Product/Product.js')}}"></script>
@endsection