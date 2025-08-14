<?php $page = 'payReceive'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            <div class="d-flex justify-content-between">
                @component('components.page-header')
                        @slot('title')
                            Payables & Receiveables
                        @endslot
                @endcomponent
                <ul class="nav nav-pills navtab-bg">
                    <li class="nav-item">
                        <a href="#payables" data-bs-toggle="tab" class="nav-link active" style="border-radius: 10px">
                            Payables
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#receiveables" data-bs-toggle="tab" class="nav-link" style="border-radius: 10px">
                            Receiveables
                        </a>
                    </li>
                </ul>
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
								<div class="tab-pane show active" id="payables">
									<div class="table-responsive">
                                        <table class="table table-center table-hover" id="tablePayables">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>PO Date</th>
                                                    <th>PO Number</th>
                                                    <th>Invoice Number</th>
                                                    <th>Supplier Name</th>
                                                    <th>Total</th>
                                                    <th>Status</th>
                                                    <th class="no-sort">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                
                                            </tbody>
                                        </table>
                                    </div>
								</div>
								<div class="tab-pane" id="receiveables">
									<div class="table-responsive">
                                        <table class="table table-center table-hover" id="tableReceiveables">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Order Date</th>
                                                    <th>Due Date</th>
                                                    <th>SO Number</th>
                                                    <th>Invoice Number</th>
                                                    <th>Customer Name</th>
                                                    <th>Total</th>
                                                    <th>Status</th>
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