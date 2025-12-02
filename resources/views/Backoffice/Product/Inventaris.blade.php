<?php $page = 'brand-list'; ?>
@extends('layout.mainlayout')
@section('content')
    <style>
        .is-invalid {
            border-color: #dc3545 !important;
        }
    </style>
    <div class="page-wrapper">
        <div class="content">
            @component('components.breadcrumb')
                @slot('title')
                    Inventaris
                @endslot
                @slot('li_1')
                    Manage Inventaris
                @endslot
                @slot('li_2')
                    Tambah Inventaris
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
                        <table class="table" id="tableInventaris">
                            <thead>
                                <tr>
                                    <th>Nama Barang</th>
                                    <th>Gambar</th>
                                    <th>Kategori</th>
                                    <th>Kode</th>
                                    <th>Store</th>
                                    <th>Qty</th>
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
    <script>
        var public = "{{ asset('') }}";
    </script>
    <script src="{{ asset('/Custom_js/Backoffice/Product/Inventaris.js') }}?v={{ time() }}"></script>
@endsection
