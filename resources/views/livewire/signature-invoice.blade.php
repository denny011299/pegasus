<div class="form-group-item border-0 p-0">
    <div class="row">
        <div class="col-xl-6 col-lg-12">
            <div class="form-group-bank">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="input-block mb-3">
                            <label>Select Bank</label>
                            <select class="select">
                                <option>Select Bank</option>
                                <option>SBI</option>
                                <option>IOB</option>
                                <option>Canara</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-groups">
                            <a class="btn btn-primary" href="#"
                                data-bs-toggle="modal" data-bs-target="#bank_details">Add
                                Bank</a>
                        </div>
                    </div>
                </div>

                <div class="input-block mb-3 notes-form-group-info">
                    <label>Notes</label>
                    <textarea class="form-control" placeholder="Enter Notes"></textarea>
                </div>
                <div class="input-block  notes-form-group-info mb-0">
                    <label>Terms and Conditions</label>
                    <textarea class="form-control" placeholder="Enter Terms and Conditions"></textarea>
                </div>
            </div>
        </div>
        <div class="col-xl-6 col-lg-12">
            <div class="form-group-bank">
                <div class="invoice-total-box">
                    <div class="invoice-total-inner">
                        <p>Taxable Amount <span>$120.00</span></p>
                        <p>Discount <span>$13.20</span></p>
                        <p>Vat <span>$0.00</span></p>
                        <div class="status-toggle justify-content-between">
                            <div class="d-flex align-center">
                                <p>Round Off </p>
                                <input id="rating_1" class="check" type="checkbox"
                                    checked="">
                                <label for="rating_1"
                                    class="checktoggle checkbox-bg">checkbox</label>
                            </div>
                            <span>$0.00</span>
                        </div>
                    </div>
                    <div class="invoice-total-footer">
                        <h4>Total Amount <span>$107.80</span></h4>
                    </div>
                </div>
                <div>
                    <ul class="nav nav-pills" id="pills-tab1" role="tablist">
                        <li class="nav-item" role="presentation">
                            <span class="custom_radio me-4 mb-0 active" id="home-tab"
                                data-bs-toggle="tab" data-bs-target="#home"
                                role="tab" aria-controls="home" aria-selected="true">
                                <input type="radio" class="form-control"
                                    name="payment">
                                <span class="checkmark"></span> Manual Signature
                            </span>
                        </li>
                        <li class="nav-item" role="presentation">
                            <span class="custom_radio me-2 mb-0" id="profile-tab"
                                data-bs-toggle="tab" data-bs-target="#profile"
                                role="tab" aria-controls="profile"
                                aria-selected="false">
                                <input type="radio" class="form-control"
                                    name="payment">
                                <span class="checkmark"></span> eSignature
                            </span>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="home"
                            role="tabpanel" aria-labelledby="home-tab">
                            <div class="input-block mb-3">
                                <label>Select Signature Name</label>
                                <select class="select">
                                    <option>Allen</option>
                                    <option>Steven</option>
                                    <option>Ralph</option>
                                </select>
                            </div>
                            <div class="input-block mb-0">
                                <label>Signature Image</label>
                                <div
                                    class="input-block service-upload service-upload-info mb-0">
                                    <span><img
                                            src="{{ URL::asset('/assets/img/invoice-signature.png') }}"
                                            alt="signature"></span>
                                    <input type="file" multiple="" id="image_sign">
                                    <div id="frames"></div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="profile" role="tabpanel"
                            aria-labelledby="profile-tab">
                            <div class="input-block mb-3">
                                <label>Enter Signature Name</label>
                                <input type="text" class="form-control"
                                    placeholder="Enter Name">
                            </div>
                            <div class="input-block mb-0">
                                <ul class="nav nav-pills" id="pills-tab2" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <span class="custom_radio me-4 mb-0 active"
                                            id="upload-signature-tab" data-bs-toggle="tab"
                                            data-bs-target="#upload-signature"
                                            role="tab"
                                            aria-controls="upload-signature"
                                            aria-selected="true">
                                            <input type="radio" class="form-control p-0"
                                                name="payment"><span
                                                class="checkmark"></span>Upload Signature
                                        </span>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <span class="custom_radio me-2 mb-0"
                                            id="e-signature-tab" data-bs-toggle="tab"
                                            data-bs-target="#e-signature" role="tab"
                                            aria-controls="e-signature"
                                            aria-selected="false">
                                            <input type="radio" class="form-control p-0"
                                                name="payment">
                                            <span class="checkmark"></span> Draw your
                                            eSignature
                                        </span>
                                    </li>
                                </ul>
                                <div class="tab-content">
                                    <div class="tab-pane fade show active"
                                        id="upload-signature" role="tabpanel"
                                        aria-labelledby="upload-signature-tab">
                                        <div
                                            class="input-block service-upload service-upload-info mb-0">
                                            <span><i
                                                    class="fe fe-upload-cloud me-1"></i>Upload
                                                Signature</span>
                                            <input type="file" multiple=""
                                                id="image_sign2">
                                            <div id="frames2"></div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="e-signature"
                                        role="tabpanel" aria-labelledby="e-signature-tab">
                                        <div class="e-signature-block">
                                            <div class="signature-draw-area">

                                            </div>
                                            <a href="javascript:void(0);"
                                                class="signature-clear">Clear & draw
                                                again</a>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>