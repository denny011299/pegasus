<?php $page = 'cash'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    Laporan Penggunaan Bahan Baku
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
                                <table class="table table-center table-hover" id="tableCash">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Nama Bahan</th>
                                            <th>Kode Produksi</th>
                                            <th>Jumlah</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>01 Agustus 2025</td>
                                            <td>Alkohol 70%</td>
                                            <td>PROD001</td>
                                            <td>120</td>
                                        </tr>
                                        <tr>
                                            <td>03 Agustus 2025</td>
                                            <td>Natrium Klorida</td>
                                            <td>PROD002</td>
                                            <td>80</td>
                                        </tr>
                                        <tr>
                                            <td>05 Agustus 2025</td>
                                            <td>Hidrogen Peroksida</td>
                                            <td>PROD003</td>
                                            <td>50</td>
                                        </tr>
                                        <tr>
                                            <td>06 Agustus 2025</td>
                                            <td>Asam Asetat</td>
                                            <td>PROD004</td>
                                            <td>200</td>
                                        </tr>
                                        <tr>
                                            <td>07 Agustus 2025</td>
                                            <td>Asam Sulfat</td>
                                            <td>PROD005</td>
                                            <td>60</td>
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
    <script src="{{asset('Custom_js/Backoffice/Reports/Cash.js')}}"></script>
@endsection