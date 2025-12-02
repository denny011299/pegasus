<?php $page = 'category'; ?>
@extends('layout.mainlayout')
@section('content')
    <style>
body .select2-container--default .select2-selection--single.is-invalid {
    border-color: #dc3545 !important;
}
    </style>
    <div class="page-wrapper">
        <div class="content">
            @component('components.breadcrumb')
                @slot('title')
                    Journal
                @endslot
                @slot('li_1')
                    Manage your Journal
                @endslot
                @slot('li_2')
                    Add Journal Entry
                @endslot
            @endcomponent

            <!-- /product list -->
            <div class="card table-list-card">
                <div class="card-body">
                    <div class="table-top">
                        <div class="search-set text-end">
                            @php
                                $bulan = [
                                    1 => 'Januari',
                                    2 => 'Februari',
                                    3 => 'Maret',
                                    4 => 'April',
                                    5 => 'Mei',
                                    6 => 'Juni',
                                    7 => 'Juli',
                                    8 => 'Agustus',
                                    9 => 'September',
                                    10 => 'Oktober',
                                    11 => 'November',
                                    12 => 'Desember',
                                ];
                            @endphp
                            <select name="" id="month" class="form-select">
                                @foreach ($bulan as $num => $name)
                                    <option value="{{ $num }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            <input type="number" name="" class="form-control" id="year" min="0">
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
                        <table class="table " id="tableJournal">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Category</th>
                                    <th>Akun Bank</th>
                                    <th>Deskripsi</th>
                                    <th>Reference</th>
                                    <th class="text-center">Debit</th>
                                    <th class="text-center">Credit</th>
                                </tr>
                            </thead>
                            <tbody>

                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4"></td>
                                    <td class="text-center font-weight-bold">Total</td>
                                    <td class="text-center font-weight-bold" id="totalDebit">Rp.0</td>
                                    <td class="text-center font-weight-bold" id="totalCredit">Rp.0</td>
                                </tr>
                            </tfoot>
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
        $('#month').val(moment().format("M"));
        $('#year').val(moment().format("YYYY"));
    </script>
    <script src="{{ asset('/Custom_js/Backoffice/Finance/Journal.js') }}?v={{ time() }}"></script>
@endsection
