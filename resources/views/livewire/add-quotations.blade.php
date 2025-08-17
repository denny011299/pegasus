<div class="row">
    <div class="col-md-12">
        <div class="quotation-card">
            <div class="row">
                <div class="col-lg-4 col-md-6 col-sm-12">
                    <div class="input-block mb-3">
                        <div class="mb-2">
                            <label>Document Title</label>
                        </div>
                        <label class="custom_check me-3 mb-0">
                            <input type="checkbox" name="invoice">
                            <span class="checkmark"></span> Quotation
                        </label>
                        <label class="custom_check mb-0">
                            <input type="checkbox" name="re_invoice">
                            <span class="checkmark"></span> Estimate
                        </label>
                    </div>
                </div>
            </div>
            <div class="form-group-item border-0 mb-0">
                <div class="row align-item-center">
                    <div class="col-lg-4 col-md-6 col-sm-12">
                        <div class="input-block mb-3">
                            <label>Quotation Id</label>
                            <input type="text" class="form-control" placeholder="Enter Quotation ID">
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-12">
                        <div class="input-block mb-3">
                            <label>Select Customer</label>
                            <ul class="form-group-plus css-equal-heights">
                                <li>
                                    <select class="select">
                                        <option>Choose Customer</option>
                                        <option>Customer 1</option>
                                        <option>Customer 2</option>
                                        <option>Customer 3</option>
                                    </select>
                                </li>
                                <li>
                                    <a class="btn btn-primary form-plus-btn"
                                        href="{{ url('add-customer') }}"><i
                                            class="fas fa-plus-circle"></i></a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-12">
                        <div class="input-block mb-3">
                            <label>Quotation Date</label>
                            <input type="text" class="datetimepicker form-control"
                                placeholder="Select Date">
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-12">
                        <div class="input-block mb-3">
                            <label>Due Date</label>
                            <input type="text" class="datetimepicker form-control"
                                placeholder="Select Date">
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-12">
                        <div class="input-block mb-3">
                            <label>Reference No</label>
                            <input type="text" class="form-control"
                                placeholder="Enter Reference Number">
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-12">
                        <div class="input-block mb-3">
                            <label>Create Quotation with/without GST</label>
                            <select class="select">
                                <option>Without GST</option>
                                <option>With GST</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="input-block mb-3">
                            <label>Products</label>
                            <ul class="form-group-plus css-equal-heights">
                                <li>
                                    <select class="select">
                                        <option>Select Product</option>
                                        <option>Product 1</option>
                                        <option>Product 2</option>
                                        <option>Product 3</option>
                                    </select>
                                </li>
                                <li>
                                    <a class="btn btn-primary form-plus-btn"
                                        href="{{ url('add-products') }}"><i
                                            class="fas fa-plus-circle"></i></a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group-item">
                <div class="card-table">
                    <div class="card-body">
                        <div class="table-responsive no-pagination">
                            <table class="table table-center table-hover datatable">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Product / Service</th>
                                        <th>Quantity</th>
                                        <th>Unit</th>
                                        <th>Rate</th>
                                        <th>Discount</th>
                                        <th>Tax</th>
                                        <th>Amount</th>
                                        <th class="no-sort">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Lorem ipsum dolor sit amet</td>
                                        <td><input type="number" class="form-control" placeholder="0">
                                        </td>
                                        <td>Pcs</td>
                                        <td><input type="number" class="form-control"
                                                placeholder="120"></td>
                                        <td>0</td>
                                        <td>0</td>
                                        <td>$120.00</td>
                                        <td class="d-flex align-items-center">
                                            <a href="#" class="btn-action-icon me-2"
                                                data-bs-toggle="modal"
                                                data-bs-target="#add_discount"><span><i
                                                        class="fe fe-edit"></i></span></a>
                                            <a href="#" class="btn-action-icon"
                                                data-bs-toggle="modal"
                                                data-bs-target="#delete_discount"><span><i
                                                        class="fe fe-trash-2"></i></span></a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="row">
                        <div class="col-lg-7">
                            <div class="input-block mb-3">
                                <label>Discount Type</label>
                                <select class="select">
                                    <option>Percentage(%)</option>
                                    <option>Fixed</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="input-block mb-3">
                                <label>Discount(%)</label>
                                <input type="text" class="form-control" placeholder="10">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="input-block mb-3">
                        <label>Tax</label>
                        <select class="select">
                            <option>No Tax</option>
                            <option>IVA - (21%)</option>
                            <option>IRPF - (-15%)</option>
                            <option>PDV - (20%)</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4"></div>
            </div>
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
                            <div class="input-block mb-3 notes-form-group-info mb-0">
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
                                <div class="input-block service-upload service-upload-info mb-0">
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
            <div class="text-end">
                <a href="{{ url('quotations') }}" class="btn btn-primary cancel me-2">Cancel</a>
                <a href="{{ url('quotations') }}" class="btn btn-primary">Save</a>
            </div>
        </div>
    </div>
</div>
