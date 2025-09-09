<?php $page = 'profit-loss-list'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">
            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    Laba & Rugi
                @endslot
            @endcomponent
            <!-- /Page Header -->

            <!-- Search Filter -->
            @component('components.search-filter')
            @endcomponent
            <!-- /Search Filter -->

            <div class="row">
                <div class="col-sm-12">
                    <div class="card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                {{-- Livewire --}}
                                <div class="table-profit-loss">
                                    <table class="table table-center ">
                                        <thead class="thead-light loss">
                                            <tr>
                                                <th>Informasi</th>
                                                <th>Tahun 1</th>
                                                <th>Tahun 2</th>
                                                <th>Tahun 3</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tr>
                                            <td class="profit space" colspan="5">
                                                <table class="table table-center" id="tableProfit">
                                                    <thead class="profitloss-heading">
                                                        <tr>
                                                            <th>Pendapatan</th>
                                                            <th>Nama</th>
                                                            <th>Tahun 1</th>
                                                            <th>Tahun 2</th>
                                                            <th>Tahun 3</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="loss-space" colspan="5">
                                                <table class="table table-center" id="tableLoss">
                                                    <thead class="profitloss-heading">
                                                        <tr>
                                                            <th>Pengeluaran</th>
                                                            <th>Nama</th>
                                                            <th>Tahun 1</th>
                                                            <th>Tahun 2</th>
                                                            <th>Tahun 3</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                    
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                {{-- /Livewire --}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection

@section('custom_js')
    <script>
        var public = "{{ asset('') }}";    
    </script>
    <script src="{{asset('Custom_js/Backoffice/Reports/Profit_Loss.js')}}"></script>
@endsection