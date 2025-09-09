<?php $page = 'cash'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    Laporan Retur Bahan Baku
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
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-center table-hover" id="tableProduct">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Tanggal Retur</th>
                                            <th>Nama Bahan Baku</th>
                                            <th>Jumlah</th>
                                            <th>Diinput oleh</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>01 Agustus 2025</td>
                                            <td>Alkohol 70%</td>
                                            <td>120</td>
                                            <td>Admin</td>
                                        </tr>
                                        <tr>
                                            <td>03 Agustus 2025</td>
                                            <td>Natrium Klorida</td>
                                            <td>80</td>
                                            <td>Rina</td>
                                        </tr>
                                        <tr>
                                            <td>05 Agustus 2025</td>
                                            <td>Hidrogen Peroksida</td>
                                            <td>50</td>
                                            <td>Andi</td>
                                        </tr>
                                        <tr>
                                            <td>06 Agustus 2025</td>
                                            <td>Asam Asetat</td>
                                            <td>200</td>
                                            <td>Dewi</td>
                                        </tr>
                                        <tr>
                                            <td>07 Agustus 2025</td>
                                            <td>Asam Sulfat</td>
                                            <td>60</td>
                                            <td>Budi</td>
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