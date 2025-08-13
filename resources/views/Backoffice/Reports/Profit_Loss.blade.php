<?php $page = 'profit-loss-list'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">
            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    Profit & Loss
                @endslot
            @endcomponent
            <!-- /Page Header -->

            <div class="profit-menu">
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-sm-12">
                        <div class="input-block mb-3">
                            <label>From</label>
                            <div class="cal-icon cal-icon-info">
                                <input type="text" class="datetimepicker form-control" placeholder="01 Jan 2023">
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-12">
                        <div class="input-block mb-3">
                            <label>To</label>
                            <div class="cal-icon cal-icon-info">
                                <input type="text" class="datetimepicker form-control" placeholder="31 Mar 2023">
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-0"></div>
                    <div class="col-lg-2 col-md-6 col-sm-12">
                        <a class="btn btn-primary loss" href="#">
                            Run</a>
                    </div>
                </div>
            </div>

            <!-- Search Filter -->
            @component('components.search-filter')
            @endcomponent
            <!-- /Search Filter -->

            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                {{-- Livewire --}}
                                <div class="table-profit-loss">
                                    <table class="table table-center ">
                                        <thead class="thead-light loss">
                                            <tr>
                                                <th>Info</th>
                                                <th>Year 1</th>
                                                <th>Year 2</th>
                                                <th>Year 3</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tr>
                                            <td class="profit space" colspan="5">
                                                <table class="table table-center" id="tableProfit">
                                                    <thead class="profitloss-heading">
                                                        <tr>
                                                            <th>Income</th>
                                                            <th>Name</th>
                                                            <th>Year 1</th>
                                                            <th>Year 2</th>
                                                            <th>Year 3</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="loss-space" colspan="5">
                                                <table class="table table-center" id="tableLoss">
                                                    <thead class="profitloss-heading">
                                                        <tr>
                                                            <th>Expenses</th>
                                                            <th>Name</th>
                                                            <th>Year 1</th>
                                                            <th>Year 2</th>
                                                            <th>Year 3</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                    
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                {{-- /Livewire --}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection

@section('custom_js')
    <script>
        var public = "{{ asset('') }}";    
    </script>
    <script src="{{asset('Custom_js/Backoffice/Reports/Profit_Loss.js')}}"></script>
@endsection