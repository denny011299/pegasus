<?php $page = 'production-skala'; ?>
@extends('layout.mainlayout')
@section('custom_css')
    <style>
        #tableProductionSkala { width: 100% !important; min-width: 800px; }
        #tableProductionSkala td { white-space: normal !important; word-wrap: break-word; }
        #tableProductionSkala th:last-child,
        #tableProductionSkala td:last-child {
            text-align: center !important;
            vertical-align: middle !important;
            white-space: nowrap !important;
        }
        #tableProductionSkala td:last-child a {
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
        }
    </style>
@endsection
@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            @component('components.page-header')
                @slot('title') Master Skala @endslot
            @endcomponent
            @component('components.search-filter')
            @endcomponent
            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive dt-pending" id="tableProductionSkala-wrap">
                                <div class="dt-skeleton" aria-hidden="true">
                                    <div style="padding: 16px 25px;">
                                        <span class="skel-text" style="width: 220px; height: 38px; border-radius: 20px;"></span>
                                    </div>
                                    <div class="dt-skeleton-head" style="grid-template-columns: 14% 26% 26% 20% 14%;">
                                        <span style="width:55%"></span>
                                        <span style="width:70%"></span>
                                        <span style="width:65%"></span>
                                        <span style="width:70%"></span>
                                        <span style="width:40%;justify-self:center"></span>
                                    </div>
                                    <div class="dt-skeleton-body">
                                        @for ($i = 0; $i < 5; $i++)
                                            <div class="dt-skeleton-row" style="grid-template-columns: 14% 26% 26% 20% 14%;">
                                                <span class="skel-text" style="width:50%"></span>
                                                <span class="skel-text" style="width:70%"></span>
                                                <span class="skel-text" style="width:65%"></span>
                                                <div style="display:flex;align-items:center;gap:8px;">
                                                    <span class="skel-avatar"></span>
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
                                <table class="table table-center table-hover" id="tableProductionSkala">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Kode</th>
                                            <th>Nama Skala</th>
                                            <th>Keterangan</th>
                                            <th>Dibuat Oleh</th>
                                            <th class="no-sort text-center">Aksi</th>
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
    <script src="{{ asset('Custom_js/Backoffice/Production/Production_Skala.js') }}?v=2"></script>
@endsection
