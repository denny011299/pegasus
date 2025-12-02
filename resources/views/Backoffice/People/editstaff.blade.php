<?php $page = 'edit-employee'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content">
            <div class="page-header">
                <div class="add-item d-flex">
                    <div class="page-title">
                        <h4>Ubah Karyawan</h4>
                        <h6>Ubah Karyawan</h6>
                    </div>
                </div>
                <ul class="table-top-head">
                    <li>
                        <div class="page-btn">
                            <a href="{{ url('/admin/staff') }}" class="btn btn-secondary"><i data-feather="arrow-left"
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
                            <div class="profile-pic-upload edit-pic">
                                <div class="profile-pic">
                                    <span><img src="{{ URL::asset('/build/img/users/user-01.jpg') }}" alt=""></span>
                                </div>
                                <div class="me-3 mb-0">
                                    <div class="image-upload mb-0">
                                        <input type="file">
                                        <div class="image-uploads">
                                            <h4>Ubah Gambar</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-4 col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Username</label>
                                        <input type="text" class="form-control" value="Mitchum">
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Nama Lengkap</label>
                                        <input type="text" class="form-control" value="Daniel">
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" value="mir34345@example.com">
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Nomor Kontak</label>
                                        <input type="text" class="form-control" value="+1 54554 54788">
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Kode Karyawan</label>
                                        <input type="text" class="form-control" value="POS001">
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Jenis Kelamin</label>
                                        <select class="select">
                                            <option>Laki-laki</option>
                                            <option>Perempuan</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <div class="input-blocks">
                                        <label class="form-label">Tanggal Lahir</label>

                                        <div class="input-groupicon calender-input">
                                            <i data-feather="calendar" class="info-img"></i>
                                            <input type="text" class="datetimepicker form-control"
                                                placeholder="Pilih Tanggal" value="13 Aug 1992">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <div class="input-blocks">
                                        <label class="form-label">Tanggal Bergabung</label>
                                        <div class="input-groupicon calender-input">
                                            <i data-feather="calendar" class="info-img"></i>
                                            <input type="text" class="datetimepicker form-control"
                                                placeholder="Pilih Tanggal">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <div class="mb-3">
                                        <div class="add-newplus">
                                            <label class="form-label">Shift</label>
                                            <a href="#"><span><i data-feather="plus-circle"
                                                        class="plus-down-add"></i>Tambah Baru</span></a>
                                        </div>
                                        <select class="select">
                                            <option>Reguler</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Departemen</label>
                                        <select class="select">
                                            <option>UI/UX</option>
                                            <option>Dukungan</option>
                                            <option>SDM</option>
                                            <option>Teknik</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Jabatan</label>
                                        <select class="select">
                                            <option>Desainer</option>
                                            <option>Developer</option>
                                            <option>Tester</option>
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
                                            <label class="form-label">No Darurat 1</label>
                                            <input type="text" class="form-control" value="+1 72368 89153">
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">No Darurat 2</label>
                                            <input type="text" class="form-control" value="+1 90563 60916">
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-6">
                                    </div>
                                    <div class="col-lg-4 col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Alamat</label>
                                            <input type="text" class="form-control" value="9S Quay Street">
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Provinsi</label>
                                            <input type="text" class="form-control" value="East London">
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Kota</label>
                                            <input type="text" class="form-control" value="Leeds">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="pass-info">
                                <div class="card-title-head">
                                    <h6><span><i data-feather="info" class="feather-edit"></i></span>Kata Sandi</h6>
                                </div>
                                <div class="row">
                                    <div class="col-lg-4 col-md-6">
                                        <div class="input-blocks mb-md-0 mb-sm-3">
                                            <label class="form-label">Kata Sandi</label>
                                            <div class="pass-group">
                                                <input type="password" class="pass-input" value="1234">
                                                <span class="fas toggle-password fa-eye-slash"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-6">
                                        <div class="input-blocks mb-0">
                                            <label class="form-label">Konfirmasi Kata Sandi</label>
                                            <div class="pass-group">
                                                <input type="password" class="pass-inputa" value="1234">
                                                <span class="fas toggle-passworda fa-eye-slash"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="text-end mb-3 mt-5">
                            <button type="button" class="btn btn-cancel me-2">Batal</button>
                            <button type="submit" class="btn btn-submit">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- /product list -->


        </div>
    </div>
@endsection
