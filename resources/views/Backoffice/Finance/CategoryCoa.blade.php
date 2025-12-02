<?php $page = 'category'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content">
            @component('components.breadcrumb')
                @slot('title')
                    Category COA
                @endslot
                @slot('li_1')
                    Manage your Category COA
                @endslot
                @slot('li_2')
                    Add Category COA
                @endslot
            @endcomponent

            <!-- /product list -->
            <div class="card table-list-card">
                <div class="card-body">
                    <div class="table-top">
                        <div class="search-set">
                            <div class="search-input">
                                <a href="" class="btn btn-searchset"><i data-feather="search"
                                        class="feather-search"></i></a>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table" id="tableCategoryCoa">
                            <thead>
                                <tr>
                                    <th>Category Code</th>
                                    <th>Category</th>
                                    <th>Created On</th>
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
            <!-- /product list -->
        </div>
    </div>
@endsection

@section('custom_js')
    <script></script>

    <script src="{{ asset('/Custom_js/Backoffice/Finance/CategoryCoa.js') }}?v={{ time() }}"></script>
@endsection
