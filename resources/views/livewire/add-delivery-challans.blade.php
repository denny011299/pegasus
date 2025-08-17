<div class="form-group-item border-0 mb-0">
    <div class="row align-item-center">
        <div class="col-lg-4 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Delivery Challan Number</label>
                <input type="text" class="form-control"
                    placeholder="Enter Delivery Challan Number">
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-12">
            <div class="input-block mb-3">
                <label>Customer Name</label>
                <ul class="form-group-plus css-equal-heights">
                    <li>
                        <select class="select" id="customer-name">
                            <option>Choose Customer</option>
                            <option value="Customer 1">Customer 1</option>
                            <option value="Customer 2">Customer 2</option>
                            <option value="Customer 3">Customer 3</option>
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
                <label>Delivery Challan Date</label>
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
            <div class="input-block mb-3 customer-address">
                <label>Shipping Address</label>
                <ul class="form-group-plus css-equal-heights">
                    <li>
                        <select class="select">
                            <option>Address</option>
                            <option>Address 1</option>
                            <option>Address 2</option>
                            <option>Address 3</option>
                        </select>
                    </li>
                    <li>
                        <a class="btn btn-primary form-plus-btn"
                            href="javascript:void(0);"><i
                                class="fas fa-plus-circle"></i></a>
                    </li>
                </ul>
            </div>
        </div>
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
