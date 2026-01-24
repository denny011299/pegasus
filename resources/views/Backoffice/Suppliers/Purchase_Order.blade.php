<?php $page = 'purchase_order'; ?>
@extends('layout.mainlayout')
@section('content')
<style>
    .badgeStatus{
        font-size:9pt!important;
    }
    .invalid{
        border: 1px solid red!important;
    }
    #tablePurchaseModal {
        table-layout: fixed;
        width: 100%;
    }
    #tablePurchaseModal td  {
        white-space: normal;
        word-break: break-word;
    }
</style>
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    Pesanan Pembelian
                @endslot
            @endcomponent
            <!-- /Page Header -->

            <!-- Filter Pencarian -->
            @component('components.search-filter')
            @endcomponent
            <!-- /Filter Pencarian -->

            <!-- Tabel -->
            <div class="row">
                <div class="col-sm-12">
                    <div class=" card-table">
                        <div class="card-body">
                            
                            <div class="table-responsive">
                                <table class="table table-center table-hover" id="tablePurchaseOrder">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>No. PO</th>
                                            <th>No. Invoice</th>
                                            <th>Nama Pemasok</th>
                                            <th>Total</th>
                                            <th>Status</th>
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
            <!-- /Tabel -->

        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection

@section('custom_js')
    <script>
        var public = "{{ asset('') }}";    
    </script>
    <script src="{{asset('Custom_js/Backoffice/Suppliers/Purchase_Order.js')}}?v=1"></script>
@endsection
