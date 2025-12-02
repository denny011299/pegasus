<?php $page = 'salesDetail'; ?>

@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content">
            @component('components.breadcrumb')
                @slot('title')
                    PO Detail
                @endslot
                @slot('li_1')
                    <span id="po_code">Purchase Order:</span>
                @endslot
            @endcomponent
            <form action="add-product">
                <div class="card">
                    <div class="card-body add-product pb-0">
                        <div class="accordion-card-one accordion mt-4" id="accordionExample1">
                            <div class="accordion-item">
                                <div class="accordion-header" id="headingOne">
                                    <div class="accordion-button" data-bs-toggle="collapse" data-bs-target="#collapseOne"
                                        aria-controls="collapseOne">
                                        <div class="text-editor add-list">
                                            <div class="addproduct-icon list icon">
                                                <h5><i data-feather="life-buoy" class="add-info"></i><span>Summary</span>
                                                </h5>
                                                <a href="javascript:void(0);"><i data-feather="chevron-down"
                                                        class="chevron-down-add"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne"
                                    data-bs-parent="#accordionExample1">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-lg-4">
                                                <div class="input-blocks">
                                                    <label>Nama Supplier</label>
                                                    <input type="text" id="nama_supplier" class="form-control" readonly>
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="input-blocks">
                                                    <label>Tanggal</label>
                                                    <input type="date" id="tanggal" class="form-control" readonly>
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="input-blocks">
                                                    <label>Status</label>
                                                    <select class="form-control" id="status">
                                                        <option value="created">Created</option>
                                                        <option value="delivery">Delivery</option>
                                                        <option value="invoicing">Invoicing</option>
                                                        <option value="done">Done</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-3">
                                                <div class="input-blocks">
                                                    <label>Total</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">Rp</span>
                                                        <input type="text" class="form-control nominal_only" readonly
                                                            id="total">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-3">
                                                <div class="input-blocks">
                                                    <label>Dibayar</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">Rp</span>
                                                        <input type="text" class="form-control nominal_only" readonly
                                                            id="dibayar">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-3">
                                                <div class="input-blocks">
                                                    <label>Sisa Bayar</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">Rp</span>
                                                        <input type="text" class="form-control nominal_only" readonly
                                                            id="sisa_bayar">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-3">
                                                <div class="input-blocks">
                                                    <label>Lebih Bayar</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">Rp</span>
                                                        <input type="text" class="form-control nominal_only" readonly
                                                            id="lebih_bayar">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="input-blocks">
                                                    <div class="border rounded p-3">
                                                        <h4 class="mb-3">Detail Supplier</h4>
                                                        <div class="mb-2">
                                                            <strong>Nama: </strong><span id="nama_supplier_pengirim"></span>
                                                        </div>
                                                        <div class="mb-2">
                                                            <strong>Alamat: </strong><span
                                                                id="alamat_supplier_pengirim"></span>
                                                        </div>
                                                        <div class="mb-0">
                                                            <strong>No Tlp: </strong><span
                                                                id="no_tlp_supplier_pengirim"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="input-blocks">
                                                    <div class="border rounded p-3">
                                                        <h4 class="mb-3">Penerima</h4>
                                                        <div class="mb-2">
                                                            <strong>Penerima: </strong> <span id="nama_store"></span>
                                                        </div>
                                                        <div class="mb-2">
                                                            <strong>Alamat: </strong> <span id="alamat_store"></span>
                                                        </div>
                                                        <div class="mb-0">
                                                            <strong>Pembuat PO: </strong> <span id="kasir_po"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="table-responsive no-pagination">
                                            <table class="table datanew" id="order-items-table">
                                                <thead>
                                                    <tr>
                                                        <th>Product</th>
                                                        <th>Varian</th>
                                                        <th>SKU</th>
                                                        <th>Jumlah</th>
                                                        <th>Harga Satuan</th>
                                                        <th>Subtotal</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-6 ms-auto">
                                                <div class="total-order w-100 max-widthauto m-auto mb-4">
                                                    <ul>
                                                        <li>
                                                            <h4>Ppn</h4>
                                                            <h5 id='ppn-detail'></h5>
                                                        </li>
                                                        <li>
                                                            <h4>Diskon</h4>
                                                            <h5 id="discount-detail"></h5>
                                                        </li>
                                                        <li>
                                                            <h4>Biaya Pengiriman</h4>
                                                            <h5 id="shipping-cost-detail"></h5>
                                                        </li>
                                                        <li>
                                                            <h4>Grand Total</h4>
                                                            <h5 id="grand-total-detail"></h5>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-card-one accordion mt-4" id="accordionExample2">
                            <div class="accordion-item">
                                <div class="accordion-header" id="headingTwo">
                                    <div class="accordion-button" data-bs-toggle="collapse" data-bs-target="#collapseTwo"
                                        aria-controls="collapseTwo">
                                        <div class="text-editor add-list">
                                            <div class="addproduct-icon list icon">
                                                <h5><i data-feather="life-buoy" class="add-info"></i><span> Good
                                                        Receipts</span>
                                                </h5>
                                                <a href="javascript:void(0);"><i data-feather="chevron-down"
                                                        class="chevron-down-add"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div id="collapseTwo" class="accordion-collapse collapse show"
                                    aria-labelledby="headingTwo" data-bs-parent="#accordionExample2">
                                    <div class="accordion-body">
                                        <div class="page-header ">
                                            <div class="page-btn ms-auto">
                                                <a href="#" class="btn btn-added" data-bs-toggle="modal"
                                                    id="btn-add-delivery" data-bs-target="#add-delivery-note"><i
                                                        data-feather="plus-circle" class="me-2"></i>Buat Good Receipt
                                                    Baru</a>
                                            </div>
                                        </div>
                                        <table class="table" id="good-receipt-table">
                                            <thead>
                                                <tr>
                                                    <th>Good Receipt No</th>
                                                    <th>Tanggal Penerimaan</th>
                                                    <th>Penerima</th>
                                                    <th>Pengirim</th>
                                                    <th class="no-sort">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-card-one accordion mt-4" id="accordionExample3">
                            <div class="accordion-item">
                                <div class="accordion-header" id="headingThree">
                                    <div class="accordion-button" data-bs-toggle="collapse"
                                        data-bs-target="#collapseThree" aria-controls="collapseThree">
                                        <div class="text-editor add-list">
                                            <div class="addproduct-icon list icon">
                                                <h5><i data-feather="life-buoy" class="add-info"></i><span> Invoice dan
                                                        Pembayaran</span>
                                                </h5>
                                                <a href="javascript:void(0);"><i data-feather="chevron-down"
                                                        class="chevron-down-add"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div id="collapseThree" class="accordion-collapse collapse show"
                                    aria-labelledby="headingThree" data-bs-parent="#accordionExample3">
                                    <div class="accordion-body">
                                        <div class="page-header ">
                                            <div class="page-btn ms-auto">
                                                <a href="#" class="btn btn-added" data-bs-toggle="modal"
                                                    data-bs-target="#add-invoice"><i data-feather="plus-circle"
                                                        class="me-2"></i>Tambah Invoice</a>
                                            </div>
                                        </div>
                                        <table class="table" id="invoice-table">
                                            <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>Tgl Invoice</th>
                                                    <th>Jatuh Tempo</th>
                                                    <th>Status</th>
                                                    <th>Total</th>
                                                    <th>Dibayar</th>
                                                    <th>Sisa Bayar</th>
                                                    <th class="no-sort">Action</th>
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
                </div>
            </form>
        </div>
    </div>
@endsection
@section('custom_js')
    <script>
        var public = "{{ asset('') }}";
    </script>
    <script src="{{ asset('/Custom_js/Backoffice/Order/PurchaseDetail.js') }}?v={{ time() }}"></script>
@endsection
