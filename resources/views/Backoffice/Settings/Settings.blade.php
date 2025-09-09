<?php $page = 'settings'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">
            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    Pengaturan Perusahaan
                @endslot
            @endcomponent
            <!-- /Page Header -->

            <div class="row">
                <div class="card company-settings-new">
                    <div class="card-body w-100">
                        <div class="row">
                            <div class="col-lg-6 col-12">
                                <div class="input-block mb-3">
                                    <label>Nama Perusahaan</label>
                                    <input type="text" class="form-control fill" id="company_name" placeholder="Masukkan Nama Perusahaan">
                                </div>
                            </div>
                            <div class="col-lg-6 col-12">
                                <div class="input-block mb-3">
                                    <label>Alamat Perusahaan</label>
                                    <input type="text" class="form-control fill" id="company_address" placeholder="Masukkan Alamat Perusahaan">
                                </div>
                            </div>
                            <div class="col-lg-6 col-12">
                                <div class="input-block mb-3">
                                    <label>Nomor Telepon</label>
                                    <input type="text" class="form-control fill number-only" id="company_phone" placeholder="Masukkan Nomor Telepon">
                                </div>
                            </div>
                            <div class="col-lg-6 col-12">
                                <div class="input-block mb-3">
                                    <label>Email Perusahaan</label>
                                    <input type="text" class="form-control fill" id="company_email" placeholder="Masukkan Email Perusahaan">
                                </div>
                            </div>
                            <div class="col-lg-6 col-12">
                                <div class="input-block mb-3">
                                    <label>Provinsi</label>
                                    <select class="form-select fill" id="state_id"></select>
                                </div>
                            </div>
                            <div class="col-lg-6 col-12">
                                <div class="input-block mb-3">
                                    <label>Kota</label>
                                    <select class="form-select fill" id="city_id"></select>
                                </div>
                            </div>
                            <div class="col-lg-6 col-12">
                                <div class="input-block mb-3">
                                    <label>Kode Pos</label>
                                    <input type="text" class="form-control fill" id="company_zipcode" placeholder="Masukkan Kode Pos">
                                </div>
                            </div>
                            <div class="col-6"></div>
                            <div class="col-lg-6 col-12">
                                <div class="input-block mb-3">
                                    <label>Logo Perusahaan</label>
                                    <div class="input-block service-upload logo-upload mb-0">
                                        <div class="drag-drop">
                                            <h6 class="drop-browse align-center">
                                                <span class="text-info me-1">Klik untuk Mengganti</span> atau Seret & Letakkan
                                            </h6>
                                            <p class="text-muted"> Hanya mendukung file PNG, JPG</p>
                                            <input type="file" id="company_logo" accept="image/png, image/jpeg" multiple="">
                                        </div>
                                        <span class="companies-logo"><img
                                                src="{{ URL::asset('/assets/img/dummy_logo.jpg') }}"
                                                id="preview_image1" alt="unggah"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6 col-12">
                                <div class="input-block mb-3">
                                    <label>Favicon</label>
                                    <div class="input-block service-upload logo-upload mb-0">
                                        <div class="drag-drop">
                                            <h6 class="drop-browse align-center">
                                                <span class="text-info me-1">Klik untuk Mengganti</span> atau Seret & Letakkan
                                            </h6>
                                            <p class="text-muted"> Hanya mendukung file PNG, JPG</p>
                                            <input type="file" id="company_icon" accept="image/png, image/jpeg" multiple="">
                                        </div>
                                        <span class="site-logo"><img
                                                src="{{ URL::asset('/assets/img/dummy_icons.jpg') }}"
                                                id="preview_image2" alt="unggah"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-12 pt-3">
                                <div class="btn-path text-end">
                                    <a href="/" class="btn btn-cancel bg-primary-light me-3">Batal</a>
                                    <a class="btn btn-primary btn-save">Simpan Perubahan</a>
                                </div>
                            </div>
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
        var data=@json($data);
        var public = "{{ asset('') }}";
        var dummyLogo = "{{ URL::asset('/assets/img/dummy_logo.jpg') }}";
        var dummyIcon = "{{ URL::asset('/assets/img/dummy_icons.jpg') }}";
    </script>
    <script src="{{asset('Custom_js/Backoffice/Settings/Settings.js')}}"></script>
@endsection