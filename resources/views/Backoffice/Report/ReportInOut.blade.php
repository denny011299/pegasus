<?php $page = 'profit-and-loss'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content">
            <div>
                <div class="page-header">
                    <div class="add-item d-flex">
                        <div class="page-title">
                            <h4>Barang Masuk Keluar</h4>
                            <h6>View Reports of Barang Masuk Keluar</h6>
                        </div>
                    </div>
                    <ul class="table-top-head">
                        <li class="me-2">
                            <a data-bs-toggle="tooltip" data-bs-placement="top" title="Refresh"><i
                                    class="ti ti-refresh"></i></a>
                        </li>
                        <li>
                            <a data-bs-toggle="tooltip" data-bs-placement="top" title="Collapse" id="collapse-header"><i
                                    class="ti ti-chevron-up"></i></a>
                        </li>
                    </ul>
                </div>
                <div class="card">
                    <div class="card-body pb-1">
                        <form action="https://dreamspos.dreamstechnologies.com/laravel/template/public/customer-report">
                            <div class="row align-items-end">
                                <div class="col-lg-10">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label class="form-label">Choose Date</label>
                                                <div class="input-icon-start position-relative">
                                                    <input type="text" id="tanggal"
                                                        class="form-control date-range bookingrange"
                                                        placeholder="dd/mm/yyyy - dd/mm/yyyy">
                                                    <span class="input-icon-left">
                                                        <i class="ti ti-calendar"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label class="form-label">Type</label>
                                                <select class="select">
                                                    <option>All</option>
                                                    <option value="1">Product In</option>
                                                    <option value="2">Product Out</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-2">
                                    <div class="mb-3">
                                        <button class="btn btn-primary w-100" type="submit">Generate Report</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card no-search">
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                        <div>
                            <h4>Customer Report</h4>
                        </div>
                        <ul class="table-top-head">
                            <li class="me-2">
                                <a data-bs-toggle="tooltip" data-bs-placement="top" title="Pdf"><img
                                        src="https://dreamspos.dreamstechnologies.com/laravel/template/public/build/img/icons/pdf.svg"
                                        alt="img"></a>
                            </li>
                            <li class="me-2">
                                <a data-bs-toggle="tooltip" data-bs-placement="top" title="Excel"><img
                                        src="https://dreamspos.dreamstechnologies.com/laravel/template/public/build/img/icons/excel.svg"
                                        alt="img"></a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table" id="tableStock">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Outlet</th>
                                        <th>Tanggal</th>
                                        <th>SKU</th>
                                        <th>Product Name</th>
                                        <th>Type</th>
                                        <th>Qty</th>
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
        <div class="footer d-sm-flex align-items-center justify-content-between border-top bg-white p-3">
            <p class="mb-0">2014 - 2025 &copy; DreamsPOS. All Right Reserved</p>
            <p>Designed &amp; Developed by <a href="javascript:void(0);" class="text-orange">Dreams</a></p>
        </div>
    </div>
    </div>
@endsection
@section('custom_js')
    <script src="{{ asset('/Custom_js/Backoffice/Report/ReportInOut.js') }}?v={{ time() }}"></script>
@endsection
