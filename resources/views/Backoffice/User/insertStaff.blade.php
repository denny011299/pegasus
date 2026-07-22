<?php $page = 'add-staff'; ?>
@extends('layout.mainlayout')
@section('custom_css')
    <style>
        .invalid{
            border: 1px solid red!important;
        }
        .is-invalids {
            border: 1px solid red !important;
            border-radius: 4px;
        }
        /* Harus lebih spesifik dari style global Select2 multiple di head.blade.php */
        #row-warehouse .select2-container--default .select2-selection--multiple.is-invalids,
        #row-position .select2-container--default .select2-selection--single.is-invalids,
        .select2-container--default .select2-selection.is-invalids {
            border: 1px solid #dc3545 !important;
            box-shadow: 0 0 0 0.15rem rgba(220, 53, 69, 0.15) !important;
        }
        
        /* Samakan tinggi select2 multiple dengan input lainnya (43px sesuai tema) */
        /* Samakan styling select2 multiple dengan select2 single bawaan template (pseudo-bootstrap) */
        .select2-container--default .select2-selection--multiple {
            min-height: 43px !important;
            padding-left: 10px;
            border: 1px solid rgba(145, 158, 171, 0.32) !important;
            border-radius: 5px !important;
        }
        .select2-container--default.select2-container--focus .select2-selection--multiple {
            border-color: #ff9b44 !important; /* warna focus template */
        }
        /* Vertikal center placeholder teks */
        .select2-container--default .select2-selection--multiple .select2-search__field {
            line-height: 41px;
            margin-top: 0px !important;
            color: #3F4254;
        }
        /* Vertikal center tag (pilihan) */
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            margin-top: 6px;
        }
    </style>
@endsection
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
                                <h5>Tambah Staf</h5>
                                <button class="btn btn-back">Kembali</button>
                            </div>
                        </div>
                    </div>
                    <!-- /Page Header -->
                    <div class="row">
                        <div class="col-md-12">
                            <form action="#">
                                <div class="form-group-item">
                                    {{-- <h5 class="form-title">Detail Dasar</h5> --}}
                                    {{-- <div class="profile-picture">
                                        <div class="upload-profile">
                                            <div class="profile-img">
                                                <img id="preview_image" class="avatar"
                                                    src="{{ URL::asset('/assets/img/profiles/avatar-14.jpg') }}"
                                                    alt="foto-profil">
                                            </div>
                                            <div class="add-profile">
                                                <h5>Unggah Foto Baru</h5>
                                                <span id="file_name">Profile-pic.jpg</span>
                                            </div>
                                        </div>
                                        <div class="img-upload">
                                            <label class="btn btn-upload">
                                                Unggah <input type="file" class="form-control fill input-gambar"
                                                accept="image/png, image/jpeg" id="staff_image">
                                            </label>
                                        </div>
                                    </div> --}}
                                    <div class="row">
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
                                        <div class="col-lg-3 col-md-6 col-sm-12">
                                            <div class="input-block mb-3">
                                                <label>Nomor Telepon <span class="text-danger">*</span></label>
                                                <input type="text" id="staff_phone" class="form-control fill include-nol"
                                                    placeholder="08xxx" name="name">
                                            </div>
                                        </div>
                                        {{-- <div class="col-lg-4 col-md-6 col-sm-12">
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
                                                <label>Tanggal Bergabung <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control fill" id="staff_join_date"
                                                    placeholder="Masukkan Tanggal Bergabung">
                                            </div>
                                        </div> --}}
                                        {{-- <div class="col-lg-4 col-md-6 col-sm-12">
                                            <div class="input-block mb-3">
                                                <label>Shift <span class="text-danger">*</span></label>
                                                <select class="form-select fill" id="staff_shift">
                                                    <option value="Regular">Reguler</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 col-sm-12">
                                            <div class="input-block mb-3">
                                                <label>Departemen <span class="text-danger">*</span></label>
                                                <select class="form-select fill" id="staff_departement">
                                                    <option value="Customer Service">Layanan Armada</option>
                                                </select>
                                            </div>
                                        </div> --}}
                                        <div class="col-lg-3 col-md-6 col-sm-12">
                                            <div class="input-block mb-3" id="row-position">
                                                <label>Posisi <span class="text-danger">*</span></label>
                                                <select class="form-select fill select2" id="staff_position">
                                                    <option value="">Pilih Posisi</option>
                                                    @foreach(($roles ?? []) as $role)
                                                        <option value="{{ $role->role_id }}"
                                                            @selected(isset($data['role_id']) && (int)$data['role_id'] === (int)$role->role_id)>
                                                            {{ $role->role_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 col-md-12 col-sm-12">
                                            <div class="input-block mb-3" id="row-warehouse">
                                                <label>Akses Gudang <span class="text-danger">*</span></label>
                                                <select class="form-select select2" id="staff_warehouses" multiple="multiple">
                                                    @if(isset($warehouses))
                                                        @foreach($warehouses as $wh)
                                                            <option value="{{ $wh->id }}">{{ $wh->warehouse_name ?? $wh->name }}</option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                                <div class="mt-1 text-end">
                                                    <a href="javascript:void(0)" id="btn_select_all_warehouses" data-state="all" class="text-primary" style="font-size: 13px;"><i class="fa fa-check-square"></i> Pilih Semua Gudang</a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 col-sm-12">
                                            <div class="input-block mb-3">
                                                <label>Alamat <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control fill" id="staff_address"
                                                    placeholder="Masukkan Alamat">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                {{-- <div class="form-group-item">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="billing-btn mb-2">
                                                <h5 class="form-title">Informasi Lainnya</h5>
                                            </div>
                                            <div class="row">
                                                <div class="col-lg-4 col-md-6 col-sm-12">
                                                    <div class="input-block mb-3">
                                                        <label>Nomor Darurat <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control fill number-only" id="staff_emergency1"
                                                            placeholder="Masukkan Nomor Darurat">
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
                                                        <label>Kota/Kabupaten <span class="text-danger">*</span></label>
                                                        <select class="form-select fill" id="city_id"></select>
                                                    </div>
                                                </div>
                                                <div class="col-lg-4 col-md-6 col-sm-12">
                                                    <div class="input-block mb-3">
                                                        <label>Kode Pos <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control fill number-only" id="staff_zipcode"
                                                            placeholder="Masukkan Kode Pos">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div> --}}
                                <div class="form-group-customer customer-additional-form">
                                    <div class="row">
                                        <h5 class="form-title">Data Keamanan</h5>
                                        <div class="col-lg-4 col-md-6 col-sm-12">
                                            <div class="input-block mb-3">
                                                <label>Username <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control fill" id="staff_username" placeholder="Masukkan Username">
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
                                    </div>
                                </div>
                                <div class="add-customer-btns text-end">
                                    <a href="{{ url('staff') }}" class="btn btn-outline-secondary btn-cancel">Batal</a>
                                    <a class="btn btn-primary btn-save">Tambah Staff</a>
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
        var mode = "{{$mode}}";
        var data = @json($data);
    </script>
    <script src="{{asset('Custom_js/Backoffice/User/insertStaff.js')}}"></script>
@endsection