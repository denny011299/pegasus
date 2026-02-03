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
            <div class="row" style="margin-top: -4vh">
                <div class="col-sm-12">
                    <div class=" card-table">
                        <div class="card-body">
                            <div class="row total mt-3">
                                <div class="col-6"></div>
                                <div class="col-6">
                                    <div class="card p-3">
                                        <div class="row">
                                            <div class="col-6 fw-bold text-center p-0">
                                                <i class="fa fa-list-alt"></i> Jumlah Invoice : <span id="totalInvoice">0</span>
                                            </div>
                                            <div class="col-6 fw-bold text-center p-0">
                                                <i class="fe fe-dollar-sign"></i> Total Hutang : <span id="totalHutang">Rp 0</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
							<div class="tab-content pt-0">
								<div class="table-responsive">
                                    <table class="table table-center table-hover" id="tablePayables">
                                        <thead class="thead-light">
                                            <tr>
                                                <th class="text-center"><input type="checkbox" class="form-check-input" name="" id="selectAll"></th>
                                                <th>Bank</th>
                                                <th>Tgl. Pemesanan</th>
                                                <th>Tgl. Jatuh Tempo</th>
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