<?php $page = 'purchase_detail'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">

            @component('components.page-header')
                @slot('title')
                    Detail Pembelian
                @endslot
            @endcomponent
            @component('components.search-filter')
            @endcomponent
            <div class="row">
                <div class="col-sm-12">
                    <div class="card-body">
                        <div class="accordion" id="accordionPanelsStayOpenExample">

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#panelsStayOpen-collapseOne" aria-expanded="true"
                                        aria-controls="panelsStayOpen-collapseOne">
                                        Ringkasan
                                    </button>
                                </h2>
                                <div id="panelsStayOpen-collapseOne" class="accordion-collapse collapse show">
                                    <div class="accordion-body">
                                        
                                        <div class="row g-3">
                                            <div class="col-12 col-md-4">
                                                <div class="input-block">
                                                    <label>Nama Supplier</label>
                                                    <select id="po_supplier" class="form-control fill">
                                                        <option value="{{ $data['po_supplier'] }}">{{ $data['po_supplier_name'] }}</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-4">
                                                <div class="input-block mb-3">
                                                    <label>Tanggal</label>
                                                    <input type="date" class="form-control fill" id="po_date"
                                                        value="{{ $data['po_date'] }}">
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-4">
                                                <div class="input-block mb-3">
                                                    <label>Status</label>
                                                    <select id="po_status" class="form-control fill">
                                                        <option value="">Pilih Status</option>
                                                        <option value="1">Dibuat</option>
                                                        <option value="2">Diterima</option>
                                                        <option value="3">Pembayaran</option>
                                                        <option value="4">Selesai</option>
                                                        <option value="-1">Cancel</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-12 col-md-4">
                                                <div class="input-block mb-3">
                                                    <label>Total</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">Rp</span>
                                                        <input type="text" class="form-control fill" id="po_total"
                                                            value="{{ $data['po_total'] }}">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-4">
                                                <div class="input-block mb-3">
                                                    <label>Dibayar</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">Rp</span>
                                                        <input type="text" class="form-control fill" id="po_paid"
                                                            value="0">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-4">
                                                <div class="input-block mb-3">
                                                    <label>Sisa Pembayaran</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">Rp</span>
                                                        <input type="text" class="form-control fill" id="po_remain"
                                                            value="0">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row g-3 mt-2">
                                            <div class="col-12 col-md-6">
                                                <div class="card">
                                                    <div class="card-body">
                                                        <h6>Pengirim</h6>
                                                        <p>Nama Pengirim: CV Maju Lancar</p>
                                                        <p>Alamat: Jl. Maju Jaya 2 no. 12</p>
                                                        <p>No. Telepon: 081273289917</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <div class="card">
                                                    <div class="card-body">
                                                        <h6>Penerima</h6>
                                                        <p>Nama Penerima: Budianto</p>
                                                        <p>Alamat: Jl. Makin Maju 5 no.18</p>
                                                        <p>No. Telepon: 081756378192</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12 mt-3">
                                            <div class="table-responsive">
                                                <table class="table table-center table-hover table-bordered w-100"
                                                    id="tableSupplies">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>SKU</th>
                                                            <th>Supplies</th>
                                                            <th>Qty</th>
                                                            <th>Harga Beli (Rp)</th>
                                                            <th>Subtotal</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody></tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <div class="row pt-3">
                                            <div class="col-12 col-md-6"></div>
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex justify-content-between">
                                                    <p>Total</p>
                                                    <p id="value_total">0</p>
                                                </div>
                                                <div class="d-flex justify-content-between">
                                                    <p>Ppn</p>
                                                    <p id="value_ppn">0</p>
                                                </div>
                                                <div class="d-flex justify-content-between">
                                                    <p>Diskon</p>
                                                    <p id="value_discount">0</p>
                                                </div>
                                                <div class="d-flex justify-content-between">
                                                    <p>Biaya Pengiriman</p>
                                                    <p id="value_cost">0</p>
                                                </div>
                                                <div class="d-flex justify-content-between border-top pt-2">
                                                    <b>Grand Total</b>
                                                    <b id="value_grand">0</b>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row pt-3">
                                            <div class="col-12 text-end">
                                                <button class="btn btn-primary save-qty">Simpan Perubahan</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#panelsStayOpen-collapseTwo" aria-expanded="false"
                                        aria-controls="panelsStayOpen-collapseTwo">
                                        Catatan Pengiriman
                                    </button>
                                </h2>
                                <div id="panelsStayOpen-collapseTwo" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <div class="row pb-3">
                                            <div class="col-12 text-end">
                                                <a class="btn btn-primary btnAddDn"><i
                                                        class="fa fa-plus-circle me-2"></i>Tambah Catatan Pengiriman</a>
                                            </div>
                                        </div>
                                        
                                        <div class="table-responsive pb-5">
                                            <table class="table table-center table-hover table-bordered w-100"
                                                id="tableDelivery">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>No. Catatan Pengiriman</th>
                                                        <th>Tanggal Pengiriman</th>
                                                        <th>Penerima</th>
                                                        <th>Alamat</th>
                                                        <th>No. Telepon</th>
                                                        <th>Status</th>
                                                        <th class="no-sort text-center">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#panelsStayOpen-collapseThree" aria-expanded="false"
                                        aria-controls="panelsStayOpen-collapseThree">
                                        Faktur dan Pembayaran
                                    </button>
                                </h2>
                                <div id="panelsStayOpen-collapseThree" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <div class="row pb-3">
                                            <div class="col-12 text-end">
                                                <a class="btn btn-primary btnAddInv"><i
                                                        class="fa fa-plus-circle me-2"></i>Tambah Faktur</a>
                                            </div>
                                        </div>
                                        {{-- (Assuming the table here will also use table-responsive) --}}
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