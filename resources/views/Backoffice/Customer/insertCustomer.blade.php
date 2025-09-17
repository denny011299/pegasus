<?php $page = 'add-customer'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="card mb-0">
                <div class="card-body">
                    <!-- Page Header -->
                    <div class="page-header">
                        <div class="content-page-header">
                            <div class="d-flex justify-content-between w-100">
                                <h5>Tambah Pelanggan</h5>
                                <button class="btn btn-back">Kembali</button>
                            </div>
                        </div>
                    </div>
                    <!-- /Page Header -->
                    <div class="row">
                        <div class="col-md-12">
                            <form action="#">
                                <div class="form-group-item">
                                    <h5 class="form-title">Detail Dasar</h5>
                                    <div class="row">
                                        <div class="col-lg-4 col-md-12">
                                            <div class="input-block mb-3">
                                                <label>Wilayah<span class="text-danger">*</span></label>
                                                <select class="form-select fill" id="area_id"></select>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 col-sm-12">
                                            <div class="input-block mb-3">
                                                <label>Nama <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control fill" id="customer_name" placeholder="Masukkan Nama Customer">
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 col-sm-12">
                                            <div class="input-block mb-3">
                                                <label>Email <span class="text-danger">*</span></label>
                                                <input type="email" class="form-control fill" id="customer_email"
                                                    placeholder="Masukkan Alamat Email">
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-12">
                                            <div class="input-block mb-3">
                                                <label>Provinsi <span class="text-danger">*</span></label>
                                                <select class="form-select fill" id="state_id"></select>
                                            </div>
                                           
                                        </div>
                                        <div class="col-lg-4 col-md-12">
                                            <div class="input-block mb-3">
                                                <label>Kota <span class="text-danger">*</span></label>
                                                <select class="form-select fill" id="city_id"></select>
                                            </div> 
                                        </div>
                                        <div class="col-lg-4 col-md-12">
                                            <div class="input-block mb-3">
                                                <label>Kecamatan <span class="text-danger">*</span></label>
                                                <select class="form-select fill" id="subdistrict_id"></select>
                                            </div> 
                                        </div>
                                        <div class="input-block mb-3">
                                            <label>Alamat <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control fill" id="customer_address" placeholder="Masukkan Alamat">
                                        </div>
                                        <div class="col-lg-4 col-sm-12">
                                            <div class="input-block mb-3">
                                                <label>Nomor Telepon <span class="text-danger">*</span></label>
                                                <input type="text" id="customer_phone" class="form-control fill number-only"
                                                    placeholder="08xxx" name="name">
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 col-sm-12">
                                            <div class="input-block mb-3">
                                                <label>Nama PIC <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control fill" id="customer_pic"
                                                    placeholder="Masukkan Nama PIC">
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 col-sm-12">
                                            <div class="input-block mb-3">
                                                <label>Nomor Telepon PIC <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control fill number-only" id="customer_pic_phone"
                                                    placeholder="08xxx">
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 col-sm-12">
                                            <div class="input-block mb-3">
                                                <label>Keterangan</label>
                                                <input type="text" class="form-control" id="customer_notes" placeholder="Masukkan Catatan">
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 col-sm-12">
                                            <div class="input-block mb-3">
                                                <label>Sales <span class="text-danger">*</span></label>
                                                <select class="form-select fill" id="sales_id"></select>
                                            </div> 
                                        </div>
                                    </div>
                                </div>
                                <div class="add-customer-btns text-end">
                                    <a class="btn btn-outline-secondary btn-clear">Clear</a>
                                    <a class="btn btn-primary btn-save">Simpan Perubahan</a>
                                </div>
                            </form>
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
        var mode="{{$mode}}";
        var data=@json($data);
    </script>
    <script src="{{asset('Custom_js/Backoffice/Customers/insertCustomer.js')}}"></script>
@endsection