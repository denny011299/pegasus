<?php $page = 'settings'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">
            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    Company Settings
                @endslot
            @endcomponent
            <!-- /Page Header -->

            <div class="row">
                <div class="card company-settings-new">
                    <div class="card-body w-100">
                        <div class="row">
                            <div class="col-lg-6 col-12">
                                <div class="input-block mb-3">
                                    <label>Company Name</label>
                                    <input type="text" class="form-control fill" id="company_name" placeholder="Enter Company Name">
                                </div>
                            </div>
                            <div class="col-lg-6 col-12">
                                <div class="input-block mb-3">
                                    <label>Company Address</label>
                                    <input type="text" class="form-control fill" id="company_address" placeholder="Enter Company Address">
                                </div>
                            </div>
                            <div class="col-lg-6 col-12">
                                <div class="input-block mb-3">
                                    <label>Phone Number</label>
                                    <input type="text" class="form-control fill number-only" id="company_phone" placeholder="Enter Phone Number">
                                </div>
                            </div>
                            <div class="col-lg-6 col-12">
                                <div class="input-block mb-3">
                                    <label>Company Email</label>
                                    <input type="text" class="form-control fill" id="company_email" placeholder="Enter Company Email">
                                </div>
                            </div>
                            <div class="col-lg-6 col-12">
                                <div class="input-block mb-3">
                                    <label>State</label>
                                    <select class="form-select fill" id="state_id"></select>
                                </div>
                            </div>
                            <div class="col-lg-6 col-12">
                                <div class="input-block mb-3">
                                    <label>City</label>
                                    <select class="form-select fill" id="city_id"></select>
                                </div>
                            </div>
                            <div class="col-lg-6 col-12">
                                <div class="input-block mb-3">
                                    <label>Zipcode</label>
                                    <input type="text" class="form-control fill" id="company_zipcode" placeholder="Enter Zipcode">
                                </div>
                            </div>
                            <div class="col-6"></div>
                            <div class="col-lg-6 col-12">
                                <div class="input-block mb-3">
                                    <label>Company Logo</label>
                                    <div class="input-block service-upload logo-upload mb-0">
                                        <div class="drag-drop">
                                            <h6 class="drop-browse align-center">
                                                <span class="text-info me-1">Click To Replace</span> or Drag and Drop
                                            </h6>
                                            <p class="text-muted"> Accept only PNG, JPG file</p>
                                            <input type="file" id="company_logo" accept="image/png, image/jpeg" multiple="">
                                        </div>
                                        <span class="companies-logo"><img
                                                src="{{ URL::asset('/assets/img/dummy_logo.jpg') }}"
                                                id="preview_image1" alt="upload"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6 col-12">
                                <div class="input-block mb-3">
                                    <label>Favicon</label>
                                    <div class="input-block service-upload logo-upload mb-0">
                                        <div class="drag-drop">
                                            <h6 class="drop-browse align-center">
                                                <span class="text-info me-1">Click To Replace</span> or Drag and Drop
                                            </h6>
                                            <p class="text-muted"> Accept only PNG, JPG file</p>
                                            <input type="file" id="company_icon" accept="image/png, image/jpeg" multiple="">
                                        </div>
                                        <span class="site-logo"><img
                                                src="{{ URL::asset('/assets/img/dummy_icons.jpg') }}"
                                                id="preview_image2" alt="upload"></span>
                                    </div>
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