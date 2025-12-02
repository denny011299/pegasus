<?php $page = 'add-product'; ?>

@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content">
            <form action="add-product">
                 @component('components.breadcrumb')
                    @slot('title')
                        Supplier Detail
                    @endslot
                    @slot('li_1')
                        Manage your  supplier detail
                    @endslot
                    @slot('li_2')
                        Apply Leave
                    @endslot
                @endcomponent
               

                <div class="card">
                    <div class="card-body add-product pb-0">
                        <div class="accordion-card-one accordion" id="accordionExample">
                            <div class="accordion-item">
                                <div class="accordion-header" id="headingOne">
                                    <div class="accordion-button" data-bs-toggle="collapse" data-bs-target="#collapseOne"
                                        aria-controls="collapseOne">
                                        <div class="addproduct-icon">
                                            <h5><i data-feather="info" class="add-info"></i><span>Detail Supplier</span>
                                            </h5>
                                            <a href="javascript:void(0);"><i data-feather="chevron-down"
                                                    class="chevron-down-add"></i></a>
                                        </div>
                                    </div>
                                </div>
                                <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne"
                                    data-bs-parent="#accordionExample">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="new-employee-field">
                                                    <span>Avatar</span>
                                                    <div class="profile-pic-upload mb-2">
                                                        <div class="profile-pic">
                                                            <span><i data-feather="plus-circle" class="plus-down-add"></i>
                                                                Profile Photo</span>
                                                        </div>
                                                        <div class="input-blocks mb-0">
                                                            <div class="image-upload mb-0">
                                                                <input type="file">
                                                                <div class="image-uploads">
                                                                    <h4>Change Image</h4>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="input-blocks">
                                                    <label>Supplier Name</label>
                                                    <input type="text" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="input-blocks">
                                                    <label>Email</label>
                                                    <input type="email" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="input-blocks">
                                                    <label>Phone</label>
                                                    <input type="text" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <div class="input-blocks">
                                                    <label>Address</label>
                                                    <input type="text" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label class="form-label">State</label>
                                                <input type="text" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="mb-3 mb-0">
                                                <label class="form-label">City</label>
                                                <input type="text" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="mb-3 mb-0">
                                                <label class="form-label">Nama Bank </label>
                                                <input type="text" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="mb-3 mb-0">
                                                <label class="form-label">Nama Rekening</label>
                                                <input type="text" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-lg-12">
                                            <div class="mb-3 mb-0">
                                                <label class="form-label">Nomer Rekening</label>
                                                <input type="text" class="form-control">
                                            </div>
                                        </div>

                                            <div class="col-md-12">
                                                <div class="mb-0 input-blocks">
                                                    <label class="form-label">Descriptions</label>
                                                    <textarea class="form-control mb-1"></textarea>
                                                    <p>Maximum 600 Characters</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-card-one accordion mt-4" id="accordionExample2">
                            <div class="accordion-item">
                                <div class="accordion-header" id="headingTwo">
                                    <div class="accordion-button" data-bs-toggle="collapse" data-bs-target="#collapseTwo"
                                        aria-controls="collapseTwo">
                                        <div class="text-editor add-list">
                                            <div class="addproduct-icon list icon">
                                                <h5><i data-feather="life-buoy" class="add-info"></i><span> Purchase Order</span></h5>
                                                <a href="javascript:void(0);"><i data-feather="chevron-down"
                                                        class="chevron-down-add"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div id="collapseTwo" class="accordion-collapse collapse show"
                                    aria-labelledby="headingTwo" data-bs-parent="#accordionExample2">
                                    <div class="accordion-body">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>No.PO</th>
                                                    <th>Order Date</th>
                                                    <th>Total</th>
                                                    <th>Status</th>
                                                    <th class="no-sort">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-card-one accordion mt-4 mb-4" id="accordionExample4">
                            <div class="accordion-item">
                                <div class="accordion-header" id="headingFour">
                                    <div class="accordion-button" data-bs-toggle="collapse"
                                        data-bs-target="#collapseFour" aria-controls="collapseFour">
                                        <div class="text-editor add-list">
                                            <div class="addproduct-icon list">
                                                <h5><i data-feather="list" class="add-info"></i><span>Invoice</span>
                                                </h5>
                                                <a href="javascript:void(0);"><i data-feather="chevron-down"
                                                        class="chevron-down-add"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div id="collapseFour" class="accordion-collapse collapse show"
                                    aria-labelledby="headingFour" data-bs-parent="#accordionExample4">
                                    <div class="accordion-body">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>No. PO</th>
                                                    <th>Order Date</th>
                                                    <th>Inv. No</th>
                                                    <th>Total</th>
                                                    <th>Status</th>
                                                    <th class="no-sort">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-12">
                    <div class="btn-addproduct mb-4">
                        <button type="button" class="btn btn-cancel me-2">Batal</button>
                        <button type="submit" class="btn btn-submit">Simpan Produk</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
