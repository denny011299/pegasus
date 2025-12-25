<?php $page = 'tambah-pemasok'; ?>
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
                                <h5>Tambah Pemasok</h5>
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
                                    <div class="profile-picture">
                                        <div class="upload-profile">
                                            <div class="profile-img">
                                                <img id="preview_image" class="avatar"
                                                    src="{{ URL::asset('/assets/img/profiles/avatar-14.jpg') }}"
                                                    alt="profile-img">
                                            </div>
                                            <div class="add-profile">
                                                <h5>Unggah Foto Baru</h5>
                                                <span id="file_name">Profile-pic.jpg</span>
                                            </div>
                                        </div>
                                        <div class="img-upload">
                                            <label class="btn btn-upload">
                                                Unggah <input type="file" class="form-control  input-gambar"
                                                accept="image/png, image/jpeg" id="supplier_image">
                                            </label>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-4 col-md-6 col-sm-12">
                                            <div class="input-block mb-3">
                                                <label>Nama <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control fill" id="supplier_name" placeholder="Masukkan Nama">
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 col-sm-12">
                                            <div class="input-block mb-3">
                                                <label>Telepon <span class="text-danger">*</span></label>
                                                <input type="text" id="supplier_phone" class="form-control fill number-only"
                                                    placeholder="08xxx" name="name">
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 col-sm-12">
                                            <div class="input-block mb-3">
                                                <label>Nama PIC <span class="text-danger">*</span></label>
                                                <input type="text" id="supplier_pic" class="form-control fill"
                                                    placeholder="Input Nama PIC" name="name">
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 col-sm-12">
                                            <div class="input-block mb-3">
                                                <label>Catatan</label>
                                                <input type="text" class="form-control" id="supplier_notes" placeholder="Masukkan Catatan Anda">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group-item">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="billing-btn mb-2">
                                                <h5 class="form-title">Alamat Supplier</h5>
                                            </div>
                                            <div class="input-block mb-3">
                                                <label>Alamat <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control fill" id="supplier_address" placeholder="Masukkan Alamat">
                                            </div>
                                            <div class="row">
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
                                               
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group-customer customer-additional-form">
                                    <div class="row">
                                        <h5 class="form-title">Detail Bank</h5>
                                        <div class="col-lg-4 col-md-6 col-sm-12">
                                            <div class="input-block mb-3">
                                                <label>Bank Account <span class="text-danger">*</span></label>
                                                <select class="form-select fill" id="bank_kode"></select>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-12 col-sm-12">
                                            <div class="input-block">
                                                <label for="">Term of Payment (TOP)<span class="text-danger">*</span></label>
                                                <div class="input-group mb-3">
                                                    <input type="text" class="form-control fill number-only" id="supplier_top" value="30" aria-describedby="basic-addon3">
                                                    <span class="input-group-text" id="basic-addon2">Hari</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="add-supplier-btns text-end">
                                    <a href="{{ url('supplier') }}" class="btn btn-outline-secondary btn-cancel">Batal</a>
                                    <button class="btn btn-primary btn-save">Simpan Perubahan</button>
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
    <script src="{{asset('Custom_js/Backoffice/Suppliers/insertSupplier.js')}}"></script>
@endsection
