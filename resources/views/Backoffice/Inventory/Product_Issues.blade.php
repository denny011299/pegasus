<?php $page = 'masalah_produk'; ?>
@extends('layout.mainlayout')
@section('content')
<style>
    .content-page-header,.page-header {
        margin-bottom: 0px !important;
    }
    .tab-content {
        padding-top: 0px !important;
        margin-top: 10px !important;
    }
</style>
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
              @component('components.page-header')
                        @slot('title')
                            Masalah Produk
                        @endslot
                @endcomponent
            <!-- /Page Header -->
             <ul class="nav nav-pills navtab-bg">
                    <li class="nav-item nav-jenis" tipe="1" >
                        <a href="#return" data-bs-toggle="tab" class="nav-link active"style="border-radius: 10px">
                            Dikembalikan
                        </a>
                    </li>
                    <li class="nav-item nav-jenis"  tipe="2">
                        <a href="#damage" data-bs-toggle="tab" class="nav-link" style="border-radius: 10px">
                            Rusak
                        </a>
                    </li>
                </ul>
            <!-- Search Filter -->
            @component('components.search-filter')
            @endcomponent
            <!-- /Search Filter -->

            <!-- Table -->
            <div class="row" style="">
                <div class="col-sm-12">
                    <div class=" card-table">
                        <div class="card-body">
							<div class="tab-content">
								<div class="tab-pane show active" id="return">
									<div class="table-responsive">
                                        <table class="table table-center table-hover" id="tableReturn">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Produk</th>
                                                    <th>SKU</th>
                                                    <th>Tanggal Pengembalian</th>
                                                    <th>Jumlah</th>
                                                    <th>Catatan</th>
                                                    <th class="no-sort">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                
                                            </tbody>
                                        </table>
                                    </div>
								</div>
								<div class="tab-pane" id="damage">
									<div class="table-responsive">
                                        <table class="table table-center table-hover" id="tableDamage">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Produk</th>
                                                    <th>SKU</th>
                                                    <th>Tanggal</th>
                                                    <th>Jumlah</th>
                                                    <th>Catatan</th>
                                                    <th class="no-sort">Aksi</th>
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
    <script src="{{asset('Custom_js/Backoffice/Inventory/Product_Issues.js')}}"></script>
@endsection