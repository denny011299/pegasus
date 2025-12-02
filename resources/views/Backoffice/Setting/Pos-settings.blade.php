<?php $page = 'pos-settings'; ?>
@extends('layout.mainlayout')
@section('content')
    <style>
        .receipt {
            width: 80mm;
            background-color: white;
            border-radius: 10px;
            font-size: 9pt
        }
    </style>
    <div class="page-wrapper">
        <div class="content settings-content">
            <div class="page-header settings-pg-header">
                <div class="add-item d-flex">
                    <div class="page-title">
                        <h4>Settings</h4>
                        <h6>Manage your settings on portal</h6>
                    </div>
                </div>
                <ul class="table-top-head">
                    <li>
                        <a data-bs-toggle="tooltip" data-bs-placement="top" title="Refresh"><i data-feather="rotate-ccw"
                                class="feather-rotate-ccw"></i></a>
                    </li>
                    <li>
                        <a data-bs-toggle="tooltip" data-bs-placement="top" title="Collapse" id="collapse-header"><i
                                data-feather="chevron-up" class="feather-chevron-up"></i></a>
                    </li>
                </ul>
            </div>
            @php
                $data = Session::get('pengaturan');
            @endphp
            <div class="row">
                <div class="col-xl-12">
                    <div class="settings-wrapper d-flex">
                        <div class="settings-page-wrap">
                            <div class="row">
                                <div class="col-5 ">
                                    <div class="mt-3">
                                        <label for="">Company Name</label>
                                        <input type="text" id="r_name" class="form-control" placeholder="Company Name"
                                            value="{{ $data['r_name'] ?? '' }}">
                                    </div>
                                    <div class="mt-3">
                                        <label for="">Company Address</label>
                                        <input type="text" id="r_address" class="form-control"
                                            placeholder="Company Address" value="{{ $data['r_address'] ?? '' }}">
                                    </div>
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="mt-3">
                                                <label for="">Company Phone Number</label>
                                                <input type="text" id="r_phone_number" class="form-control number-only"
                                                    placeholder="Company Phone Number"
                                                    value="{{ $data['r_phone_number'] ?? '' }}">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="mt-3">
                                                <label for="">Font Size</label>
                                                <input type="number" min="0" class="form-control number-only"
                                                    id="r_font_size" placeholder="Ex 12"
                                                    value="{{ $data['r_font_size'] ?? '' }}">
                                            </div>
                                        </div>

                                    </div>
                                    <div class="mt-3">
                                        <div class="d-flex">
                                            <label for="">Footer</label>
                                        </div>
                                        <textarea name="" id="r_footer" cols="30" rows="10" class="form-control">{{ $data['r_footer'] ?? '' }}</textarea>
                                    </div>
                                    <div class="mt-3 ms-1 row">
                                        <div class="col-6 form-check form-switch ">
                                            <input class="form-check-input" type="checkbox" role="switch"
                                                id="r_show_customer" {{ $data['r_show_customer'] ? 'checked' : '' }}>
                                            <label class="form-check-label" for="">Customer Name</label>
                                        </div>
                                        <div class="col-6 form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch"
                                                id="r_show_invoice" {{ $data['r_show_invoice'] ? 'checked' : '' }}>
                                            <label class="form-check-label" for="">Invoice Number</label>
                                        </div>
                                    </div>
                                    <div class="mt-3 ms-1 row">
                                        <div class="col-6 form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" id="r_show_sales"
                                                {{ $data['r_show_sales'] ? 'checked' : '' }}>
                                            <label class="form-check-label" for="">Sales</label>
                                        </div>
                                        <div class="col-6 form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch"
                                                id="r_show_tanggal" {{ $data['r_show_tanggal'] ? 'checked' : '' }}>
                                            <label class="form-check-label" for="">Tanggal</label>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <label for="">Page Size</label>
                                        <select name="" id="r_page_size" class="form-select">
                                            <option value="58">58 mm</option>
                                            <option value="76">76 mm</option>
                                            <option value="80">80 mm</option>
                                        </select>
                                    </div>
                                    <div class="company-info border-0">

                                    </div>
                                    <div class="modal-footer-btn">
                                        <button type="button" class="btn btn-submit btn-save">Save Changes</button>
                                    </div>
                                </div>

                                <div class="col-6 p-4 offset-1 " id="print-receipt" style="background-color: #f8f9fa">
                                    <div class="modal-body">
                                        <div class="receipt p-4 mx-auto" id="">
                                            <div class="icon-head text-center">
                                                <a href="{{ url('pos-design') }}">
                                                    <img src="{{ asset($data['logo']) }}" width="100" height="30"
                                                        alt="Receipt Logo">
                                                </a>
                                            </div>
                                            <div class="text-center info text-center">
                                                <h6 id="title-header">INDORAYA</h6>
                                                <p class="mb-0"><label id="lb_address">123 Main St, Anytown, USA</label>
                                                </p>
                                                <p class="mb-0"><label id="lb_phone">+1 5656665656</label></p>
                                            </div>
                                            <div class="tax-invoice">
                                                <h6 class="text-center labelPembelian">Pembelian</h6>
                                                <div class="row">
                                                    <div class="col-sm-12 col-md-6">
                                                        <div class="invoice-user-name cust_name"><span>Nama:
                                                            </span><span>John Doe</span></div>
                                                        <div class="invoice-user-name inv_no"><span>Inv No:
                                                            </span><span>CS132453</span></div>
                                                    </div>
                                                    <div class="col-sm-12 col-md-6 ">
                                                        <div class="invoice-user-name sales_name"><span>Sales:
                                                            </span><span>Denny</span></div>
                                                        <div class="invoice-user-name tgl"><span>Tgl:
                                                            </span><span>01.07.2022</span></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <table class="table-borderless w-100 table-fit">
                                                <thead>
                                                    <tr>
                                                        <th>Qty</th>
                                                        <th>Item</th>
                                                        <th class="text-end">Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>3</td>
                                                        <td>Casing Samsung</td>
                                                        <td class="text-end">Rp 90000</td>
                                                    </tr>
                                                    <tr>
                                                        <td>2</td>
                                                        <td>Charger</td>
                                                        <td class="text-end">Rp 100000</td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="4">
                                                            <table class="table-borderless w-100 table-fit">
                                                                <tr>
                                                                    <td>Sub Total :</td>
                                                                    <td class="text-end">Rp 190.000</td>
                                                                </tr>
                                                                <tr>
                                                                    <td>Discount :</td>
                                                                    <td class="text-end">-Rp 10.000</td>
                                                                </tr>
                                                                <tr>
                                                                    <td>PPN (10%) :</td>
                                                                    <td class="text-end">Rp 18.000</td>
                                                                </tr>
                                                                <tr class="fw-bold">
                                                                    <td>Total:</td>
                                                                    <td class="text-end">Rp 198.000</td>
                                                                </tr>
                                                                <tr>
                                                                </tr>

                                                            </table>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <div class="text-center invoice-bar">
                                                <p id="lb_footer">Terimakasih Sudah Berbelanja di Indoraya</p>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
@section('custom_js')
    <script>
        $('#r_page_size').val("{{ $data['r_page_size'] ? $data['r_page_size'] : 80 }}").trigger('change');
    </script>
    <script src="{{ asset('/Custom_js/Backoffice/Setting/pos-setting.js') }}?v={{ time() }}"></script>
@endsection
