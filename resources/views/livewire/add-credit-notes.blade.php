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
                            <a class="btn btn-primary" href="#" data-bs-toggle="modal"
                                data-bs-target="#bank_details">Add Bank</a>
                        </div>
                    </div>
                </div>

                <div class="input-block mb-3 notes-form-group-info">
                    <label>Notes</label>
                    <textarea class="form-control" placeholder="Enter Notes"></textarea>
                </div>
                <div class="input-block mb-3  notes-form-group-info mb-0">
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
                <div class="input-block mb-3">
                    <label>Signature Name</label>
                    <input type="text" class="form-control"
                        placeholder="Enter Signature Name">
                </div>
                <div class="input-block mb-0">
                    <label>Signature Image</label>
                    <div class="input-block mb-3  service-upload service-upload-info mb-0">
                        <span><i class="fe fe-upload-cloud me-1"></i>Upload
                            Signature</span>
                        <input type="file" multiple="" id="image_sign">
                        <div id="frames"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>