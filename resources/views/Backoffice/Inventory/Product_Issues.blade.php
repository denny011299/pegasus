<?php $page = 'product_issues'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            <div class="d-flex justify-content-between">
                @component('components.page-header')
                        @slot('title')
                            Product Issues
                        @endslot
                @endcomponent
                <ul class="nav nav-pills navtab-bg">
                    <li class="nav-item">
                        <a href="#return" data-bs-toggle="tab" class="nav-link active" style="border-radius: 10px">
                            Returned
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#damage" data-bs-toggle="tab" class="nav-link" style="border-radius: 10px">
                            Damaged
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
								<div class="tab-pane show active" id="return">
									<div class="table-responsive">
                                        <table class="table table-center table-hover" id="tableReturn">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Product</th>
                                                    <th>SKU</th>
                                                    <th>Return Date</th>
                                                    <th>Qty</th>
                                                    <th>Notes</th>
                                                    <th class="no-sort">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                
                                            </tbody>
                                        </table>
                                    </div>
								</div>
								<div class="tab-pane" id="damage">
									<div class="table-responsive">
                                        <table class="table table-center table-hover" id="tableDamage">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Product</th>
                                                    <th>SKU</th>
                                                    <th>Date</th>
                                                    <th>Qty</th>
                                                    <th>Notes</th>
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
    <script src="{{asset('Custom_js/Backoffice/Inventory/Product_Issues.js')}}"></script>
@endsection