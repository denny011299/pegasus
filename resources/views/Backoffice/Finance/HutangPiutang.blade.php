<?php $page = 'expired-products'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content">
            @component('components.breadcrumb')
                @slot('title')
                    Hutang & Piutang
                @endslot
                @slot('li_1')
                    Manage your Hutang & Piutang
                @endslot
                @slot('li_2')
                @endslot
            @endcomponent

            <!-- /product list -->
            <div class="card table-list-card">
                <div class="card-body">
                    <div class="table-top">
                        <div class="search-set pt-2">
                            <div class="search-input">
                                <a href="" class="btn btn-searchset"><i data-feather="search"
                                        class="feather-search"></i></a>
                            </div>
                        </div>
                        <div class="search-path pt-2">
                            <div class="d-flex align-items-center">
                                <a class="btn btn-filter" id="filter_search">
                                    <i data-feather="filter" class="filter-icon"></i>
                                    <span><img src="{{ URL::asset('/build/img/icons/closes.svg') }}" alt="img"></span>
                                </a>
                            </div>
                        </div>
                        <div class="form-sort" style="width: auto">
                            <ul class="nav nav-tabs tab-style-1 d-sm-flex d-block" role="tablist"
                                style="border-radius: 10px">
                                <li class="nav-item">
                                    <a class="nav-link active" data-bs-toggle="tab" data-bs-target="#expired"
                                        aria-current="page" href="#expired">Hutang</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" data-bs-target="#returned"
                                        href="#returned">Piutang</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <!-- /Filter -->
                    <div class="card" id="filter_inputs">
                        <div class="card-body pb-0">
                            <div class="row">
                                <div class="col-lg-3 col-sm-6 col-12">
                                    <div class="input-blocks">
                                        <i data-feather="calendar" class="info-img"></i>
                                        <div class="input-groupicon">
                                            <input type="text" class="datetimepicker" placeholder="Choose Date">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-sm-6 col-12 ms-auto">
                                    <div class="input-blocks">
                                        <a class="btn btn-filters ms-auto"> <i data-feather="search"
                                                class="feather-search"></i> Search </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /Filter -->
                    <div class="table-responsive">
                        <div class="tab-content">
                            <div class="tab-pane active" id="expired" role="tabpanel">
                                <table class="table" id="tbHutang">
                                    <thead>
                                        <tr>
                                            <th>PO Date</th>
                                            <th>PO Number</th>
                                            <th>Due Date</th>
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
                            <div class="tab-pane" id="returned" role="tabpanel">
                                <table class="table" id="tbPiutang">
                                    <thead>
                                        <tr>
                                            <th>SO Date</th>
                                            <th>SO Number</th>
                                            <th>Due Date</th>
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
            <!-- /product list -->
        </div>
    </div>
@endsection
@section('custom_js')
    <script src="{{ asset('/Custom_js/Backoffice/Finance/HutangPiutang.js') }}?v={{ time() }}"></script>
@endsection
