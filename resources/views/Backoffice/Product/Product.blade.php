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

        /* Overlay loading saat pagination / search / sort (server-side) */
        #tableProduct-wrap {
            position: relative;
        }

        #tableProduct-wrap.is-loading tbody {
            opacity: 0.45;
            pointer-events: none;
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
                            <div class="table-responsive dt-pending" id="tableProduct-wrap">
                                <div class="dt-skeleton" aria-hidden="true">
                                    <div style="padding: 16px 25px 16px 25px;">
                                        <span class="skel-text" style="width: 250px; height: 38px; border-radius: 20px;"></span>
                                    </div>
                                    <div class="dt-skeleton-head" style="grid-template-columns: 15% 10% 10% 40% 12% 13%;">
                                        <span style="width:40%"></span>
                                        <span style="width:50%"></span>
                                        <span style="width:40%"></span>
                                        <span style="width:60%"></span>
                                        <span style="width:50%"></span>
                                        <span style="width:40%;justify-self:center"></span>
                                    </div>
                                    <div class="dt-skeleton-body">
                                        @for ($i = 0; $i < 5; $i++)
                                            <div class="dt-skeleton-row" style="grid-template-columns: 15% 10% 10% 40% 12% 13%;">
                                                <span class="skel-text" style="width:60%"></span>
                                                <span class="skel-text" style="width:70%"></span>
                                                <span class="skel-text" style="width:60%"></span>
                                                <span class="skel-text" style="width:90%"></span>
                                                <div style="display:flex;align-items:center;gap:6px;">
                                                    <span class="skel-avatar" style="width:16px;height:16px;"></span>
                                                    <span class="skel-text" style="width:60%"></span>
                                                </div>
                                                <div style="display:flex;align-items:center;gap:6px;justify-content:center;">
                                                    <span class="skel-btn"></span>
                                                    <span class="skel-btn"></span>
                                                </div>
                                            </div>
                                        @endfor
                                    </div>
                                </div>
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