<?php $page = 'add-supplier'; ?>
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
                                <h5>Add Supplier</h5>
                                <button class="btn btn-back">Back</button>
                            </div>
                        </div>
                    </div>
                    <!-- /Page Header -->
                    <div class="row">
                        <div class="col-md-12">
                            <form action="#">
                                <div class="form-group-item">
                                    <h5 class="form-title">Basic Details</h5>
                                    <div class="profile-picture">
                                        <div class="upload-profile">
                                            <div class="profile-img">
                                                <img id="preview_image" class="avatar"
                                                    src="{{ URL::asset('/assets/img/profiles/avatar-14.jpg') }}"
                                                    alt="profile-img">
                                            </div>
                                            <div class="add-profile">
                                                <h5>Upload a New Photo</h5>
                                                <span id="file_name">Profile-pic.jpg</span>
                                            </div>
                                        </div>
                                        <div class="img-upload">
                                            <label class="btn btn-upload">
                                                Upload <input type="file" class="form-control fill input-gambar"
                                                accept="image/png, image/jpeg" id="supplier_image">
                                            </label>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-4 col-md-6 col-sm-12">
                                            <div class="input-block mb-3">
                                                <label>Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control fill" id="supplier_name" placeholder="Enter Name">
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 col-sm-12">
                                            <div class="input-block mb-3">
                                                <label>Email <span class="text-danger">*</span></label>
                                                <input type="email" class="form-control fill" id="supplier_email"
                                                    placeholder="Enter Email Address">
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 col-sm-12">
                                            <div class="input-block mb-3">
                                                <label>Phone <span class="text-danger">*</span></label>
                                                <input type="text" id="supplier_phone number-only" class="form-control fill"
                                                    placeholder="08xxx" name="name">
                                            </div>
                                        </div>
                                        
                                       
                                        <div class="col-lg-4 col-md-6 col-sm-12">
                                            <div class="input-block mb-3">
                                                <label>Notes</label>
                                                <input type="email" class="form-control" id="supplier_notes" placeholder="Enter Your Notes">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group-item">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="billing-btn mb-2">
                                                <h5 class="form-title">Billing Address</h5>
                                            </div>
                                            <div class="input-block mb-3">
                                                <label>Address</label>
                                                <input type="text" class="form-control fill" id="supplier_address" placeholder="Enter Address 1">
                                            </div>
                                            <div class="row">
                                                <div class="col-lg-4 col-md-12">
                                                    <div class="input-block mb-3">
                                                        <label>State</label>
                                                        <select class="form-select fill" id="state_id"></select>
                                                    </div>
                                                   
                                                </div>
                                                <div class="col-lg-4 col-md-12">
                                                    <div class="input-block mb-3">
                                                        <label>City</label>
                                                        <select class="form-select fill" id="city_id"></select>
                                                    </div>
                                                   
                                                </div>
                                                <div class="col-lg-4 col-md-12">
                                                    <div class="input-block mb-3">
                                                        <label>Zipcode</label>
                                                        <input type="text" class="form-control fill" id="supplier_zipcode"
                                                            placeholder="Enter Pincode">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group-customer customer-additional-form">
                                    <div class="row">
                                        <h5 class="form-title">Bank Details</h5>
                                        <div class="col-lg-4 col-md-6 col-sm-12">
                                            <div class="input-block mb-3">
                                                <label>Bank Name</label>
                                                <input type="text" class="form-control fill" id="supplier_bank" placeholder="Enter Bank Name">
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 col-sm-12">
                                            <div class="input-block mb-3">
                                                <label>Branch</label>
                                                <input type="text" class="form-control fill" id="supplier_branch"
                                                    placeholder="Enter Branch Name">
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-12 col-sm-12">
                                            <div class="input-block mb-3">
                                                <label>Account Holder Name</label>
                                                <input type="text" class="form-control fill" id="supplier_account_name"
                                                    placeholder="Enter Account Holder Name">
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-12 col-sm-12">
                                            <div class="input-block mb-3">
                                                <label>Account Number</label>
                                                <input type="text" class="form-control fill" id="supplier_account_number"
                                                    placeholder="Enter Account Number">
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-12 col-sm-12">
                                            <div class="input-block mb-3">
                                                <label>IFSC</label>
                                                <input type="text" class="form-control fill" id="supplier_ifsc" placeholder="Enter IFSC Code">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="add-supplier-btns text-end">
                                    <a href="{{ url('supplier') }}" class="btn btn-outline-secondary btn-cancel">Cancel</a>
                                    <button class="btn btn-primary btn-save">Save Changes</button>
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