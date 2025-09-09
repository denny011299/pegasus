<?php $page = 'profile-settings'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    Pengaturan Profil
                @endslot
            @endcomponent
            <!-- /Page Header -->

            <!-- Search Filter -->
            @component('components.search-filter')
            @endcomponent
            <!-- /Search Filter -->

            <div class="card">
                <div class="card-body w-100">
                    <div class="content-page-header">
                        <h5 class="setting-menu">Akun Profil</h5>
                    </div>
                    <div class="row">
                        <div class="profile-picture">
                            <div class="upload-profile me-2">
                                <div class="profile-img">
                                    <img id="preview_image" class="avatar"
                                        src="{{ URL::asset('/assets/img/profiles/avatar-10.jpg') }}"
                                        alt="profile-img">
                                </div>
                            </div>
                            <div class="img-upload">
                                <label class="btn btn-primary">
                                    Unggah Foto Baru <input type="file" class="form-control input-gambar"
                                                accept="image/png, image/jpeg" id="staff_image">
                                </label>
                                <p class="mt-1">Profil harus menggunakan format file JPG, JPEG, atau PNG</p>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="form-title">
                                <h5>Informasi Pengguna</h5>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <div class="input-block mb-3">
                                <label>Nama Depan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control fill" id="staff_first_name" placeholder="Masukkan Nama Depan">
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <div class="input-block mb-3">
                                <label>Nama Belakang <span class="text-danger">*</span></label>
                                <input type="text" class="form-control fill" id="staff_last_name" placeholder="Masukkan Nama Belakang">
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <div class="input-block mb-3">
                                <label>Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control fill" id="staff_email"
                                    placeholder="Masukkan Alamat Email">
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <div class="input-block mb-3">
                                <label>Nomor Telepon <span class="text-danger">*</span></label>
                                <input type="text" id="staff_phone" class="form-control fill"
                                    placeholder="08xxx" name="name">
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <div class="input-block mb-3">
                                <label>Tanggal Lahir <span class="text-danger">*</span></label>
                                <input type="date" class="form-control fill" id="staff_birthdate"
                                    placeholder="Masukkan Tanggal Lahir">
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <div class="input-block mb-3">
                                <label>Jenis Kelamin <span class="text-danger">*</span></label>
                                <select class="form-select fill" id="staff_gender">
                                    <option value="1">Laki-laki</option>
                                    <option value="2">Perempuan</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <div class="input-block mb-3">
                                <label>Provinsi <span class="text-danger">*</span></label>
                                <select class="form-select fill" id="state_id"></select>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <div class="input-block mb-3">
                                <label>Kota <span class="text-danger">*</span></label>
                                <select class="form-select fill" id="city_id"></select>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <div class="input-block mb-3">
                                <label>Kata Sandi <span class="text-danger">*</span></label>
                                <input type="password" class="form-control fill" id="staff_password" placeholder="Masukkan Kata Sandi">
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <div class="input-block mb-3">
                                <label>Konfirmasi Kata Sandi <span class="text-danger">*</span></label>
                                <input type="password" class="form-control fill" id="staff_confirm" placeholder="Masukkan Ulang Kata Sandi">
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
    <!-- /Page Wrapper -->
@endsection

@section('custom_js')
    <script>
        var public = "{{ asset('') }}";
        var dummyLogo = "{{ URL::asset('/assets/img/profiles/avatar-10.jpg') }}";
        var data = @json($data);
        data = data[0];
    </script>
    <script src="{{asset('Custom_js/Backoffice/Settings/Profiles.js')}}"></script>
@endsection
