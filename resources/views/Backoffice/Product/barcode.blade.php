<?php $page = 'barcode'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper notes-page-wrapper">
        <div class="content">


            <div class="page-header">
                <div class="add-item d-flex">
                    <div class="page-title">
                        <h4>Cetak Barcode</h4>
                        <h6>Cetak barcode produk</h6>
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    <ul class="table-top-head">
                        <li>
                            <a data-bs-toggle="tooltip" data-bs-placement="top" title="Ciutkan" id="collapse-header"><i
                                    data-feather="chevron-up" class="feather-chevron-up"></i></a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="barcode-content-list">

                <div class="row">
                    <div class="col-lg-5">

                        <div class="input-blocks search-form seacrh-barcode-item">
                            <div class="searchInput">
                                <label class="form-label">Produk</label>
                                <input type="text" class="form-control" placeholder="Cari Produk berdasarkan Nama Kode"
                                    id="search">
                                <div class="resultBox">
                                </div>
                                <div class="icon"><i class="fas fa-search"></i></div>
                            </div>
                        </div>

                    </div>
                    <div class="col-lg-6 pt-4 ">
                        <div class="spinner-border text-primary" role="status" id="loading" style="display: none">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="modal-body-table search-modal-header">
                        <div class="table-responsive">
                            <table class="table  ">
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th>SKU</th>
                                        <th>Barcode</th>
                                        <th>Jml</th>
                                        <th class="text-center no-sort">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="tbProduct">


                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

                <div class="paper-search-size">
                    <div class="row align-items-center">
                        <div class="col-lg-6 pt-3">
                            <div class="row">

                                <div class="col-sm-4">
                                    <div class="search-toggle-list">
                                        <p>Tampilkan Nama Produk</p>
                                        <div class="input-blocks m-0">
                                            <div
                                                class="status-toggle modal-status d-flex justify-content-between align-items-center">
                                                <input type="checkbox" id="nama" class="check" checked>
                                                <label for="nama" class="checktoggle mb-0"> </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>


                                <div class="col-sm-4">
                                    <div class="search-toggle-list">
                                        <p>Tampilkan Harga</p>
                                        <div class="input-blocks m-0">
                                            <div
                                                class="status-toggle modal-status d-flex justify-content-between align-items-center">
                                                <input type="checkbox" id="harga" class="check" checked>
                                                <label for="harga" class="checktoggle mb-0"> </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="search-barcode-button">
                    <a href="javascript:void(0);" class="btn btn-submit btn-print-barcode me-2">
                        <span><i class="fas fa-print me-2"></i></span>
                        Buat Barcode</a>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('custom_js')
    <script>
        var public = "{{ asset('') }}";
    </script>
    <script src="{{ asset('/Custom_js/Backoffice/Product/Barcode.js') }}?v={{ time() }}"></script>
@endsection
