<?php $page = 'profit-and-loss'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content">
            @component('components.breadcrumb')
                @slot('title')
                    General Ledgers
                @endslot
                @slot('li_1')
                    Manage your General Ledgers
                @endslot
            @endcomponent

            <div class="card table-list-card border-0 mb-0">
                <div class="card-body mb-3">
                    <div class="table-top mb-0 profit-table-top">
                        <div class="search-path profit-head ">
                           
                        </div>
                        <div class="position-relative daterange-wraper input-blocks mb-0">
                            <input type="text" name="datetimes" placeholder="From Month -  To Month "
                                class="form-control">
                            <i data-feather="calendar" class="feather-14 info-img"></i>
                        </div>
                        <div class="date-btn">
                            <a href="javascript:void(0);" class="btn btn-secondary d-flex align-items-center"><i
                                    data-feather="database" class="feather-14 info-img me-2"></i>Display Date</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table profit-table">
                   <thead class="profit-table-bg">
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th class="text-center">Debit</th>
                            <th class="text-center">Credit</th>
                            <th class="text-center">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="table-heading">
                            <td>101- Kas Besar</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        
                        <tr>
                            <td class="text-end">2025-07-10</td>
                            <td>Pendapatan Harian</td>
                            <td class="text-center">Rp 3.000.000</td>
                            <td class="text-center">Rp 0</td>
                            <td class="text-center">Rp 3.000.000</td>
                        </tr>
                       

                        <tr>
                            <td class="text-end">2025-06-10</td>
                            <td>Pendapatan</td>
                            <td class="text-center">Rp 6.000.000</td>
                            <td class="text-center">Rp 0</td>
                            <td class="text-center">Rp 9.000.000</td>
                        </tr>
                        <tr class="table-heading">
                            <td>102- Kas Kecil</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        
                        <tr>
                            <td class="text-end">2025-07-10</td>
                            <td>Pendapatan Harian</td>
                            <td class="text-center">Rp 0</td>
                            <td class="text-center">Rp 2.000.000</td>
                            <td class="text-center">Rp 3.000.000</td>
                        </tr>
                       

                        <tr>
                            <td class="text-end">2025-06-10</td>
                            <td>Pendapatan</td>
                            <td class="text-center">Rp 0</td>
                            <td class="text-center">Rp 2.000.000</td>
                            <td class="text-center">Rp 1.000.000</td>
                        </tr>
                       
                        
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
