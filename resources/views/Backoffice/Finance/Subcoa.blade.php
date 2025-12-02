<?php $page = 'category'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content">
            @component('components.breadcrumb')
                @slot('title')
                    SUBCOA
                @endslot
                @slot('li_1')
                    Manage your Sub Coa
                @endslot
                @slot('li_2')
                    Add Sub Coa
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
                        <div class="form-sort">
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table" id="tableSubcoa">
                            <thead>
                                <tr>
                                    <th>Sub COA Code</th>
                                    <th>Sub COA</th>
                                    <th>COA</th>
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
    <script src="{{ asset('/Custom_js/Backoffice/Finance/Subcoa.js') }}?v={{ time() }}"></script>
@endsection
