<?php $page = 'view_stock_opname'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    View Stock Opname
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
                    <div class="card-table">
                        <div class="card-body p-4">
                            <div class="row">
                                {{-- @if($stp_type==1)
                                    <div class="col-3">
                                        <select name="" id="category_id" class="form-select" {{$mode==1?"":"disabled"}}></select>
                                    </div>
                                @endif --}}
                                {{-- <div class="col-3">
                                    <input type="text"  class="form-control fill" id="staff" aria-describedby="emailHelp" placeholder="Inventory Staff" {{$mode==1?"":"disabled"}}>
                                </div> --}}
                                <div class="col-6 text-end">
                                    
                                </div>
                            </div>
                            <table class="table mt-3" id="tableStockOpname">
                                <thead>
                                    <tr>
                                        <td  class="text-center">No.</td>
                                        <td>SKU</td>
                                        <td style="width:15%">Name</td>
                                        <td class="text-center">Stock Comp.</td>
                                        <td class="text-center">Stock Real</td>
                                        <td class="text-center">Difference</td>
                                        <td>Notes</td>
                                    </tr>
                                </thead>
                                <tbody id="tbStock"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Table -->
            <div class="text-end">
                <button class="btn bg-primary-subtle btn-save" style="border-radius: 100px"><span class="fe fe-save"></span> Save Change</button>
            </div>
        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection

@section('custom_js')
    <script>
        var public = "{{ asset('') }}";    
    </script>
    <script src="{{asset('Custom_js/Backoffice/Inventory/CreateStockOpname.js')}}"></script>
@endsection