<?php $page = 'stock_opname'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    Bill of Materials
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
                    <div class=" card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-center table-hover" id="tableBom">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>SKU</th>
                                            <th>Product</th>
                                            <th>Material</th>
                                            <th>Unit</th>
                                            <th class="no-sort">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                         <tr>
                                            <td>MAT-WRP01</td>
                                            <td>Antis</td>
                                            <td>alcohol, Air, botol</td>
                                            <td>Kg</td>
                                            <td  class="d-flex align-items-center">
                                                 <a class="me-2 btn-action-icon p-2 btn_edit"  data-bs-target="#edit-category">
                                                <i data-feather="edit" class="feather-edit"></i>
                                                </a>
                                                <a class="p-2 btn-action-icon btn_delete"  href="javascript:void(0);">
                                                    <i data-feather="trash-2" class="feather-trash-2"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
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
    <script src="{{asset('Custom_js/Backoffice/Production/Bom.js')}}"></script>
@endsection