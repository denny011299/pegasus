<?php $page = 'purchase_detail'; ?>
@extends('layout.mainlayout')
@section('custom_css')
    <style>
        .child-wrapper {
            margin-left: 60px; 
            max-width: 80%;
        }

        .child-item {
            display: flex;
            align-items: flex-start;
            gap: 40px;               /* jarak antar kolom */
            padding: 12px 0px 12px 36px;
            border-bottom: 1px solid #eee;
        }

        .child-left {
            flex: 0 0 70%;
            padding-left: 0.8rem
        }

        .child-right {
            flex: 0 0 20%;
            text-align: right;
            padding-right: 2rem;
        }

        .child-left-total {
            flex: 0 0 70%;
            padding-left: 28rem
        }

        .left-row {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .name {
            max-width: 30rem;
            white-space: normal;
            word-break: break-word;
        }

        #add-retur .select2-container {
            width: 100% !important;
        }

    </style>
@endsection
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
                                                    <select id="po_supplier" class="form-control fill" disabled >
                                                        <option value="{{ $data['po_supplier'] }}">{{ $data['po_supplier_name'] }}</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-4">
                                                <div class="input-block">
                                                    <label>Nomor PO</label>
                                                    <input type="text" class="form-control fill" value="{{ $data['po_number'] }}" id="po_number" disabled>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-4">
                                                <div class="input-block">
                                                    <label>Nomor Invoice</label>
                                                    <input type="text" class="form-control fill" value="{{ $data['poi_code'] }}" id="po_number" disabled>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-4">
                                                <div class="input-block mb-3">
                                                    <label>Tanggal PO</label>
                                                    <input type="text" class="form-control fill" id="po_date" disabled>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-4">
                                                <div class="input-block mb-3">
                                                    <label>Jatuh Tempo</label>
                                                    <input type="text" class="form-control fill" id="poi_due" disabled>
                                                </div>
                                            </div>

                                            <div class="col-12 col-md-4">
                                                <div class="input-block mb-3">
                                                    <label>Status</label>
                                                    <select id="po_status" class="form-control fill" disabled>
                                                        <option value="">Pilih Status</option>
                                                        <option value="1">Belum Terbayar</option>
                                                        <option value="3">Menunggu Tanda Terima</option>
                                                        <option value="2">Terbayar</option>
                                                        <option value="-1">Ditolak</option>
                                                    </select>
                                                </div>
                                            </div>
                                        {{--
                                            <div class="col-12 col-md-4">
                                                <div class="input-block mb-3">
                                                    <label>Total</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">Rp</span>
                                                        <input type="text" class="form-control fill" id="po_total"
                                                            value="{{ number_format($data['po_total'],0,',','.') }}" disabled>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-4">
                                                <div class="input-block mb-3">
                                                    <label>Dibayar</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">Rp</span>
                                                        <input type="text" class="form-control fill" id="po_paid" disabled
                                                            value="0">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-4">
                                                <div class="input-block mb-3">
                                                    <label>Sisa Pembayaran</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">Rp</span>
                                                        <input type="text" class="form-control fill" id="po_remain" disabled
                                                            value="0">
                                                    </div>
                                                </div>
                                            </div> --}}
                                        </div>


                                        <div class="col-12 mt-3">
                                            <div class="table-responsive">
                                                <table class="table table-center table-bordered w-100"
                                                    id="tableSupplies">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>SKU</th>
                                                            <th>Bahan Mentah</th>
                                                            <th>Qty</th>
                                                            <th>Harga Beli (Rp)</th>
                                                            <th>Subtotal</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody></tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <div class="col-12 mt-5">
                                            <div class="d-flex justify-content-between mt-4 mb-3">
                                                <h5 class="pt-2 text-black">Retur</h5>
                                                <button type="button" class="btn btn-primary retur-bahan">Tambah Retur</button>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-center table-hover w-100" id="tableRetur">
                                                    <thead class="">
                                                        <tr>
                                                            <th></th>
                                                            <th>Tanggal</th>
                                                            <th>Keterangan</th>
                                                            <th class="text-end">Subtotal</th>
                                                            <th style="text-align: center">Aksi</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody></tbody>
                                                    <tfoot>
                                                        <tr>
                                                            <td></td>
                                                            <td class="fw-bold text-end" colspan="2">Total :</td>
                                                            <td class="total_akhir text-end"></td>
                                                        </tr>
                                                    </tfoot>
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
                                                    <p>Retur</p>
                                                    <p id="value_retur">0</p>
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
                                            <div class="col-6">
                                            </div>
                                            <div class="col-6 text-end">
                                                <button class="btn btn-danger save-tolak" style="display: none">Tolak</button>
                                                <button class="btn btn-success save-terima" style="display: none">Terima</button>
                                                <button type="button" class="btn btn-info text-light    " id="btn-lihat-bukti">Lihat Bukti Foto</button>
                                                <button class="btn btn-primary save-qty">Simpan Perubahan</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
{{-- 
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseTwo" aria-expanded="false" aria-controls="panelsStayOpen-collapseTwo">
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
                                            <table class="table table-center table-hover w-100" id="tableDelivery">
                                                <thead>
                                                    <th>No. Catatan Pengiriman</th>
                                                    <th>Tanggal Pengiriman</th>
                                                    <th>Penerima</th>
                                                    <th>No. Telepon</th>
                                                    <th>Status</th>
                                                    <th class="no-sort text-center">Aksi</th>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseThree" aria-expanded="false" aria-controls="panelsStayOpen-collapseThree">
                                        Faktur dan Pembayaran
                                    </button>
                                </h2>
                                <div id="panelsStayOpen-collapseThree" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <div class="row pb-3">
                                            <div class="col-12 text-end">
                                                <a class="btn btn-primary btnAddInv">
                                                    <i class="fa fa-plus-circle me-2"></i>Tambah Faktur
                                                </a>
                                            </div>
                                        </div>
                                        <div class="table-responsive pb-5">
                                            <table class="table table-center table-hover w-100" id="tableInvoice">
                                                <thead>
                                                    <th style="width:15%">Tgl. Pesanan</th>
                                                    <th style="width:15%">Tgl. Jatuh Tempo</th>
                                                    <th>No. Faktur</th>
                                                    <th>Total</th>
                                                    <th>Status</th>
                                                    <th class="no-sort text-center">Aksi</th>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>--}}
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@section('custom_js')
    <script>
        var public = "{{ asset('') }}";    
        var data = @json($data);
        console.log(data);
        
    </script>
    <script src="{{asset('Custom_js/Backoffice/Suppliers/Purchase_Order_Detail.js')}}?v={{ time() }}"></script>
@endsection