<?php $page = 'view_stock_opname'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    Insert Stock Opname
                @endslot
            @endcomponent
            <!-- /Page Header -->

            <!-- Search Filter -->
            @component('components.search-filter')
            @endcomponent
            <!-- /Search Filter -->
                        <div class="accordion-card-one accordion mt-4" id="accordionExample1">
                            <div class="accordion-item">
                                <div class="accordion-header" id="headingOne">
                                    <div class="accordion-button" data-bs-toggle="collapse" data-bs-target="#collapseOne"
                                        aria-controls="collapseOne">
                                        <div class="text-editor add-list">
                                            <div class="addproduct-icon list icon">
                                                <h6><i data-feather="life-buoy" class="add-info"></i><span>Header Stock
                                                        Opname</span>
                                                </h6>
                                                <a href="javascript:void(0);"><i data-feather="chevron-down"
                                                        class="chevron-down-add"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne"
                                    data-bs-parent="#accordionExample1">
                                    <div class="accordion-body">
                                        <div class="row mb-5">
                                            <div class="col-lg-4">
                                                <div class="input-blocks">
                                                    <label>Nama Penaggung jawab</label>
                                                    <input type="text" class="form-control" value="Shawn" readonly>
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="input-blocks">
                                                    <label>Tanggal</label>
                                                    <input type="date" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="add-newplus">
                                                    <label class="form-label">Kategori</label>
                                                </div>
                                                <select class="select" id="kategori" name="kategori[]" multiple>
                                                </select>
                                            </div>
                                            <div class="col-lg-12 mt-2">
                                                <div class="input-blocks">
                                                    <label>Catatan</label>
                                                    <textarea class="form-control" placeholder="Masukkan catatan"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
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
            <div class="text-end mt-3">
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