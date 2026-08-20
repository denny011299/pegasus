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

        #tableProduct-wrap .dataTables_scrollHead,
        #tableProduct-wrap .dataTables_scrollBody {
            width: 100% !important;
        }

        #tableProduct-wrap {
            position: relative;
        }

        #tableProduct_wrapper .dataTables_processing {
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            width: 100% !important;
            height: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            border: 0 !important;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.72) !important;
            box-shadow: none !important;
            z-index: 20;
            display: flex !important;
            align-items: center;
            justify-content: center;
            color: #1e293b;
            font-weight: 600;
            font-size: 14px;
        }

        #tableProduct-wrap:not(.is-loading) .dataTables_processing {
            display: none !important;
        }

        #tableProduct-wrap.is-loading .dataTables_processing {
            display: flex !important;
        }

        #tableProduct-wrap.is-loading tbody {
            opacity: 0.45;
            pointer-events: none;
        }
    </style>
@endsection
@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">

            @component('components.page-header')
                @slot('title')
                    Produk
                @endslot
            @endcomponent

            @component('components.search-filter')
            @endcomponent

            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive dt-pending" id="tableProduct-wrap">
                                <div class="dt-skeleton" aria-hidden="true">
                                    <div style="padding: 16px 25px;">
                                        <span class="skel-text" style="width: 250px; height: 38px; border-radius: 20px;"></span>
                                    </div>
                                    <div class="dt-skeleton-head" style="grid-template-columns: 18% 12% 12% 35% 13% 10%;">
                                        <span style="width:70%"></span>
                                        <span style="width:65%"></span>
                                        <span style="width:60%"></span>
                                        <span style="width:55%"></span>
                                        <span style="width:70%"></span>
                                        <span style="width:40%;justify-self:center"></span>
                                    </div>
                                    <div class="dt-skeleton-body">
                                        @for ($i = 0; $i < 5; $i++)
                                            <div class="dt-skeleton-row" style="grid-template-columns: 18% 12% 12% 35% 13% 10%;">
                                                <span class="skel-text" style="width:75%"></span>
                                                <span class="skel-text" style="width:70%"></span>
                                                <span class="skel-text" style="width:65%"></span>
                                                <span class="skel-text" style="width:85%"></span>
                                                <div style="display:flex;align-items:center;gap:8px;">
                                                    <span class="skel-avatar"></span>
                                                    <span class="skel-text" style="width:65%"></span>
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
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@section('custom_js')
    <script>
        var public = "{{ asset('') }}";
    </script>
    <script src="{{ asset('Custom_js/Backoffice/Product/Product.js') }}"></script>
@endsection
