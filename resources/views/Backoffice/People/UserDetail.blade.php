<?php $page = 'addStaff'; ?>
@extends('layout.mainlayout')
@section('content')
    <style>
        .is-invalid {
            border-color: #dc3545!important;
        }
    </style>
    <div class="page-wrapper">
        <div class="content">
            <div class="page-header">
                <div class="add-item d-flex">
                    <div class="page-title">
                        <h4>Tambah Karyawan</h4>
                    </div>
                </div>
                <ul class="table-top-head">
                    <li>
                        <div class="page-btn">
                            <a href="{{ url('/admin/users') }}" class="btn btn-secondary"><i data-feather="arrow-left"
                                    class="me-2"></i>Kembali ke Daftar Karyawan</a>
                        </div>
                    </li>
                    <li>
                        <a data-bs-toggle="tooltip" data-bs-placement="top" title="Collapse" id="collapse-header"><i
                                data-feather="chevron-up" class="feather-chevron-up"></i></a>
                    </li>
                </ul>
            </div>
            <!-- /product list -->
            <div class="card">
                <div class="card-body">
                    <form action="{{ url('edit-employee') }}">
                        <div class="new-employee-field">
                            <div class="card-title-head">
                                <h6><span><i data-feather="info" class="feather-edit"></i></span>Informasi Karyawan</h6>
                            </div>
                            <div class="profile-pic-upload">
                                <div class="profile-pic brand-pic">
                                    <span id="add_image_text"><i data-feather="plus-circle" class="plus-down-add"></i> Add
                                        Image</span>
                                    <img id="staff_image_preview" class="p-1" src="#" alt="Preview"
                                        style="display:none; max-width: 100%; max-height: 150px; object-fit: cover;border-radius:15px" />

                                </div>
                                <div class="me-3 mb-0">
                                    <div class="image-upload mb-0">
                                        <input type="file" id="staff_image" name="staff_image" accept="image/*">
                                        <div class="image-uploads">
                                            <h4>Ubah Gambar</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-4 col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Nama Depan*</label>
                                        <input type="text" class="form-control fill" id="staff_first_name">
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Nama Belakang*</label>
                                        <input type="text" class="form-control fill" id="staff_last_name">
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" id="staff_email">
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Nomor Kontak*</label>
                                        <input type="text" class="form-control number-only fill" id="staff_phone">
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <div class="mb-3 input-blocks add-product list">
                                        <label class="form-label">Kode Karyawan*</label>
                                        <input type="text" class="form-control list field-sku fill" id="staff_code">
                                        <button type="submit" class="btn btn-primaryadd" id="buat-kode-staff"
                                            data-fieldtocomplete="staff_code">
                                            Buat Kode
                                        </button>
                                        </input>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <div class="input-blocks">
                                        <label class="form-label">Tanggal Lahir*</label>

                                        <div class="input-groupicon calender-input">
                                            <i data-feather="calendar" class="info-img"></i>
                                            <input type="text" class="datetimepicker form-control fill"
                                                placeholder="Pilih Tanggal" id="staff_bod">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Jenis Kelamin</label>
                                        <select class="select" id="staff_jenis">
                                            <option>Laki-laki</option>
                                            <option>Perempuan</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <div class="input-blocks">
                                        <label class="form-label">Tanggal Bergabung</label>
                                        <div class="input-groupicon calender-input">
                                            <i data-feather="calendar" class="info-img"></i>
                                            <input type="text" class="datetimepicker form-control"
                                                placeholder="Pilih Tanggal" id="staff_date_join">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Departemen*</label>
                                        <select class="select fill" id="staff_department">
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <div class="mb-3 " id="row-role">
                                        <label class="form-label">Role*</label>
                                        <select class="select fill" id="role_id">
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Store/Warehouse*</label>
                                        <select class="select fill" id="staff_store_warehouse">
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="other-info">
                                <div class="card-title-head">
                                    <h6><span><i data-feather="info" class="feather-edit"></i></span>Informasi Lainnya
                                    </h6>
                                </div>
                                <div class="row">
                                    <div class="col-lg-4 col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Nama Kontak Darurat</label>
                                            <input type="text" class="form-control" id="staff_darurat_nama">
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">No Darurat</label>
                                            <input type="text" class="form-control" id="staff_darurat_nomer">
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-6">
                                    </div>
                                    <div class="col-lg-4 col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Alamat</label>
                                            <input type="text" class="form-control" id="staff_alamat">
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Provinsi</label>
                                            <select class="form-control select2" id="staff_provinsi">
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Kota</label>
                                            <select class="form-control select2" id="staff_kota">
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="pass-info">
                                <div class="card-title-head">
                                    <h6><span><i data-feather="info" class="feather-edit"></i></span>Pengaturan Akun</h6>
                                </div>
                                <div class="row">
                                    <div class="col-lg-4 col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Username*</label>
                                            <input type="text" class="form-control fill" id="staff_username">
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-6">
                                        <div class="input-blocks mb-md-0 mb-sm-3">
                                            <label class="form-label">Kata Sandi*</label>
                                            <div class="pass-group">
                                                <input type="password" class="pass-input fill" id="staff_password">
                                                <span class="fas toggle-password fa-eye-slash"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-6">
                                        <div class="input-blocks mb-0">
                                            <label class="form-label">Konfirmasi Kata Sandi*</label>
                                            <div class="pass-group">
                                                <input type="password" class="pass-inputa fill" id="staff_password_confirm">
                                                <span class="fas toggle-passworda fa-eye-slash"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="text-end mb-3 mt-5">
                            <button type="button" class="btn btn-cancel me-2">Batal</button>
                            <button type="button" class="btn btn-save">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- /product list -->
        </div>
    </div>
@endsection
@section('custom_js')
    <script>
        var public = "{{ asset('') }}";
    </script>
    <script src="{{ asset('/Custom_js/Backoffice/People/UserDetail.js') }}?v={{ time() }}"></script>
@endsection
