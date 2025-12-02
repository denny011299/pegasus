<?php $page = 'category'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content">
            @component('components.breadcrumb')
                @slot('title')
                    Kategori Inventaris
                @endslot
                @slot('li_1')
                    Atur Kategori Inventaris
                @endslot
                @slot('li_2')
                    Tambah Kategori Inventaris
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
                            <i data-feather="sliders" class="info-img"></i>
                            <select class="select">
                                <option>Sort by Date</option>
                                <option>Newest</option>
                                <option>Oldest</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table " id="tableCategory">
                            <thead>
                                <tr>
                                    <th>Kategori</th>
                                    <th>Singkatan</th>
                                    <th>Dibuat Pada</th>
                                    <th>Status</th>
                                    <th class="no-sort">Action</th>
                                </tr>
                            </thead>
                            <tbody id="">
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
    <script src="{{ asset('/Custom_js/Backoffice/Product/CategoryInventory.js') }}?v={{ time() }}"></script>
@endsection
