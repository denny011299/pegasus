<?php $page = 'payReceive'; ?>
@extends('layout.mainlayout')
@section('content')
    <style>
        .page-header {
            margin-bottom: 0;
        }
    </style>
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            <div class="d-flex justify-content-between">
                @component('components.page-header')
                        @slot('title')
                            Hutang
                        @endslot
                @endcomponent
            </div>
            <!-- /Page Header -->

            <!-- Search Filter -->
            @component('components.search-filter')
            @endcomponent
            <!-- /Search Filter -->

            <!-- Table -->
            <div class="row" style="margin-top: -7vh">
                <div class="col-sm-12">
                    <div class=" card-table">
                        <div class="card-body">
							<div class="tab-content">
									<div class="table-responsive">
                                        <table class="table table-center table-hover" id="tablePayables">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th class="text-center">#</th>
                                                    <th>Bank</th>
                                                    <th>Tgl. Pemesanan</th>
                                                    <th>Tgl. Jatuh Tempo</th>
                                                    <th>No. PO</th>
                                                    <th>Nomor Faktur</th>
                                                    <th>Nama Pemasok</th>
                                                    <th>Total</th>
                                                    <th>Status</th>
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
    <script src="{{asset('Custom_js/Backoffice/Reports/Pay_Receive.js')}}"></script>
@endsection