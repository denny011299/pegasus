<?php $page = 'profile-settings'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    Profile Settings
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
                        <h5 class="setting-menu">Profile Account</h5>
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
                                    Upload new picture <input type="file" class="form-control input-gambar"
                                                accept="image/png, image/jpeg" id="staff_image">
                                </label>
                                <p class="mt-1">Profile Should be Supported File format JPG,JPEG,PNG
                                </p>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="form-title">
                                <h5>User Information</h5>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <div class="input-block mb-3">
                                <label>First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control fill" id="staff_first_name" placeholder="Enter First Name">
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <div class="input-block mb-3">
                                <label>Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control fill" id="staff_last_name" placeholder="Enter Last Name">
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <div class="input-block mb-3">
                                <label>Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control fill" id="staff_email"
                                    placeholder="Enter Email Address">
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <div class="input-block mb-3">
                                <label>Phone Number<span class="text-danger">*</span></label>
                                <input type="text" id="staff_phone" class="form-control fill"
                                    placeholder="08xxx" name="name">
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <div class="input-block mb-3">
                                <label>Date Of Birth <span class="text-danger">*</span></label>
                                <input type="date" class="form-control fill" id="staff_birthdate"
                                    placeholder="Enter Birthdate">
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <div class="input-block mb-3">
                                <label>Gender <span class="text-danger">*</span></label>
                                <select class="form-select fill" id="staff_gender">
                                    <option value="1">Male</option>
                                    <option value="2">Female</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <div class="input-block mb-3">
                                <label>State<span class="text-danger">*</span></label>
                                <select class="form-select fill" id="state_id"></select>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <div class="input-block mb-3">
                                <label>City<span class="text-danger">*</span></label>
                                <select class="form-select fill" id="city_id"></select>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <div class="input-block mb-3">
                                <label>Blood Type <span class="text-danger">*</span></label>
                                <select class="form-select fill" id="staff_blood">
                                    <option value="O+">O+</option>
                                    <option value="O-">O-</option>
                                    <option value="A+">A+</option>
                                    <option value="A-">A-</option>
                                    <option value="AB+">AB+</option>
                                    <option value="AB-">AB-</option>
                                    <option value="B+">B+</option>
                                    <option value="B-">B-</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <div class="input-block mb-3">
                                <label>Password<span class="text-danger">*</span></label>
                                <input type="password" class="form-control fill" id="staff_password" placeholder="Enter Password">
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <div class="input-block mb-3">
                                <label>Confirm Password<span class="text-danger">*</span></label>
                                <input type="password" class="form-control fill" id="staff_confirm" placeholder="Enter Confirm Password">
                            </div>
                        </div>
                        <div class="col-lg-12 pt-3">
                            <div class="btn-path text-end">
                                <a href="/" class="btn btn-cancel bg-primary-light me-3">Cancel</a>
                                <a class="btn btn-primary btn-save">Save Changes</a>
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