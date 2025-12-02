<?php $page = 'add-product'; ?>

@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content">
            <form action="add-product">

                <div class="page-btn mb-3">
                    <a href="/admin/customers" class="btn btn-secondary"><i data-feather="arrow-left" class="me-2"></i>Kembali
                        ke daftar customer</a>
                </div>

                <div class="card">
                    <div class="card-body add-product pb-0">
                        <div class="accordion-card-one accordion" id="accordionExample">
                            <div class="accordion-item">
                                <div class="accordion-header" id="headingOne">
                                    <div class="accordion-button" data-bs-toggle="collapse" data-bs-target="#collapseOne"
                                        aria-controls="collapseOne">
                                        <div class="addproduct-icon">
                                            <h5><i data-feather="info" class="add-info"></i><span>Detail Customers</span>
                                            </h5>
                                            <a href="javascript:void(0);"><i data-feather="chevron-down"
                                                    class="chevron-down-add"></i></a>
                                        </div>
                                    </div>
                                </div>
                                <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne"
                                    data-bs-parent="#accordionExample">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-lg-4">
                                                <div class="input-blocks">
                                                    <label>Nama Customer</label>
                                                    <input type="text" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="input-blocks">
                                                    <label>Email</label>
                                                    <input type="email" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="input-blocks">
                                                    <label>Telepon</label>
                                                    <input type="text" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="input-blocks">
                                                    <label>Alamat</label>
                                                    <input type="text" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-lg-3">
                                                <div class="mb-3">
                                                    <label class="form-label">Provinsi</label>
                                                    <input type="text" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-lg-3">
                                                <div class="mb-3 mb-0">
                                                    <label class="form-label">Kota</label>
                                                    <input type="text" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="mb-3 mb-0">
                                                    <label class="form-label">Nama Bank </label>
                                                    <input type="text" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="mb-3 mb-0">
                                                    <label class="form-label">Nama Rekening</label>
                                                    <input type="text" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="mb-3 mb-0">
                                                    <label class="form-label">Nomer Rekening</label>
                                                    <input type="text" class="form-control">
                                                </div>
                                            </div>

                                            <div class="col-md-12">
                                                <div class="mb-0 input-blocks">
                                                    <label class="form-label">Notes</label>
                                                    <textarea class="form-control mb-1"></textarea>
                                                    <p>Maksimal 600 Karakter</p>
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
                                                <h5><i data-feather="life-buoy" class="add-info"></i><span> Invoice dan
                                                        Pembayaran</span>
                                                </h5>
                                                <a href="javascript:void(0);"><i data-feather="chevron-down"
                                                        class="chevron-down-add"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div id="collapseTwo" class="accordion-collapse collapse show" aria-labelledby="headingTwo"
                                    data-bs-parent="#accordionExample2">
                                    <div class="accordion-body">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>No. PO</th>
                                                    <th>Order Date</th>
                                                    <th>Inv. No</th>
                                                    <th>Total</th>
                                                    <th>Status</th>
                                                    <th class="no-sort">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>PO-0001</td>
                                                    <td>2023-10-01</td>
                                                    <td>INV-0001</td>
                                                    <td>$1000</td>
                                                    <td><span class="badge bg-success">Lunas</span></td>
                                                    <td>
                                                        <div type="submit" class="btn btn-submit" data-bs-toggle="modal"
                                                            data-bs-target="#lihat-bukti">Lihat
                                                            Pembayaran</div>
                                                    </td>
                                            </tbody>
                                            <tbody>
                                                <tr>
                                                    <td>PO-0002</td>
                                                    <td>2023-10-01</td>
                                                    <td>INV-0001</td>
                                                    <td>$1000</td>
                                                    <td><span class="badge bg-warning">Belum Dibayarkan</span></td>
                                                    <td>
                                                        <div type="submit" class="btn btn-submit" data-bs-toggle="modal"
                                                            data-bs-target="#bayar">Bayar
                                                            Sekarang</div>

                                                    </td>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-card-one accordion mt-4 mb-4" id="accordionExample4">
                            <div class="accordion-item">
                                <div class="accordion-header" id="headingFour">
                                    <div class="accordion-button" data-bs-toggle="collapse"
                                        data-bs-target="#collapseFour" aria-controls="collapseFour">
                                        <div class="text-editor add-list">
                                            <div class="addproduct-icon list">
                                                <h5><i data-feather="list" class="add-info"></i><span>Delivery
                                                        Notes</span>
                                                </h5>
                                                <a href="javascript:void(0);"><i data-feather="chevron-down"
                                                        class="chevron-down-add"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div id="collapseFour" class="accordion-collapse collapse show"
                                    aria-labelledby="headingFour" data-bs-parent="#accordionExample4">
                                    <div class="accordion-body">
                                        {{-- <div class="page-header ">
                                            <div class="page-btn ms-auto">
                                                <a href="#" class="btn btn-added" data-bs-toggle="modal"
                                                    data-bs-target="#add-delivery-notes"><i data-feather="plus-circle"
                                                        class="me-2"></i>Buat Delivery Notes Baru</a>
                                            </div>
                                        </div> --}}
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Delivery Note No</th>
                                                    <th>Tanggal Pengiriman</th>
                                                    <th>Penerima</th>
                                                    <th>Alamat</th>
                                                    <th>Telepon</th>
                                                    <th>Status</th>
                                                    <th class="no-sort">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>DN-0001</td>
                                                    <td>2023-10-01</td>
                                                    <td>John Doe</td>
                                                    <td>08123456789</td>
                                                    <td>123 Main St, City, State</td>
                                                    <td><span class="badge bg-success">Delivered</span></td>
                                                    <td class="action-table-data">
                                                        <div class="edit-delete-action">
                                                            <a class="me-2 p-2" data-bs-toggle="modal"
                                                                data-bs-target="#edit-delivery-note">
                                                                <i data-feather="edit" class="feather-edit"></i>
                                                            </a>
                                                            <a class="confirm-text p-2" href="javascript:void(0);">
                                                                <i data-feather="trash-2" class="feather-trash-2"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
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
