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
                            <h5>Add Supplier</h5>
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
                                                <img id="blah" class="avatar"
                                                    src="{{ URL::asset('/assets/img/profiles/avatar-14.jpg') }}"
                                                    alt="profile-img">
                                            </div>
                                            <div class="add-profile">
                                                <h5>Upload a New Photo</h5>
                                                <span>Profile-pic.jpg</span>
                                            </div>
                                        </div>
                                        <div class="img-upload">
                                            <label class="btn btn-upload">
                                                Upload <input type="file" class="form-control fill" id="supplier_image">
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
                                                <input type="email" class="form-control fill" id="supplier_address"
                                                    placeholder="Enter Email Address">
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 col-sm-12">
                                            <div class="input-block mb-3">
                                                <label>Phone <span class="text-danger">*</span></label>
                                                <input type="text" id="mobile_code" class="form-control"
                                                    placeholder="08xxx" name="name">
                                            </div>
                                        </div>
                                        
                                       
                                        <div class="col-lg-4 col-md-6 col-sm-12">
                                            <div class="input-block mb-3">
                                                <label>Notes</label>
                                                <input type="email" class="form-control" placeholder="Enter Your Notes">
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
                                                <input type="text" class="form-control" placeholder="Enter Address 1">
                                            </div>
                                            <div class="row">
                                                <div class="col-lg-4 col-md-12">
                                                    <div class="input-block mb-3">
                                                        <label>State</label>
                                                        <input type="text" class="form-control"
                                                            placeholder="Enter State">
                                                    </div>
                                                   
                                                </div>
                                                <div class="col-lg-4 col-md-12">
                                                    <div class="input-block mb-3">
                                                        <label>City</label>
                                                        <input type="text" class="form-control" placeholder="Enter City">
                                                    </div>
                                                   
                                                </div>
                                                <div class="col-lg-4 col-md-12">
                                                    <div class="input-block mb-3">
                                                        <label>Zipcode</label>
                                                        <input type="text" class="form-control"
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
                                                <input type="text" class="form-control" placeholder="Enter Bank Name">
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 col-sm-12">
                                            <div class="input-block mb-3">
                                                <label>Branch</label>
                                                <input type="text" class="form-control"
                                                    placeholder="Enter Branch Name">
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-12 col-sm-12">
                                            <div class="input-block mb-3">
                                                <label>Account Holder Name</label>
                                                <input type="text" class="form-control"
                                                    placeholder="Enter Account Holder Name">
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-12 col-sm-12">
                                            <div class="input-block mb-3">
                                                <label>Account Number</label>
                                                <input type="text" class="form-control"
                                                    placeholder="Enter Account Number">
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-12 col-sm-12">
                                            <div class="input-block mb-3">
                                                <label>IFSC</label>
                                                <input type="text" class="form-control" placeholder="Enter IFSC Code">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="add-customer-btns text-end">
                                    <a href="{{ url('supplier') }}" class="btn customer-btn-cancel">Cancel</a>
                                    <a href="{{ url('supplier') }}" class="btn customer-btn-save">Save Changes</a>
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
