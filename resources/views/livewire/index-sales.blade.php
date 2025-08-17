<div class="row">
    <div class="col-xl-7 d-flex">
        <div class="card flex-fill">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title">Sales Analytics</h5>

                    <div class="dropdown main">
                        <button class="btn btn-white btn-sm dropdown-toggle" type="button" id="dropdownMenuButton"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            Monthly
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item">Weekly</a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item">Monthly</a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item">Yearly</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between flex-wrap flex-md-nowrap">
                    <div class="w-md-100 d-flex align-items-center mb-3 flex-wrap flex-md-nowrap">
                        <div>
                            <span>Total Sales</span>
                            <p class="h3 text-primary me-5">$1000</p>
                        </div>
                        <div>
                            <span>Receipts</span>
                            <p class="h3 text-success me-5">$1000</p>
                        </div>
                        <div>
                            <span>Expenses</span>
                            <p class="h3 text-danger me-5">$300</p>
                        </div>
                        <div>
                            <span>Earnings</span>
                            <p class="h3 text-dark me-5">$700</p>
                        </div>
                    </div>
                </div>

                <div id="sales_chart"></div>
            </div>
        </div>
    </div>
    <div class="col-xl-5 d-flex">
        <div class="card flex-fill">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title">Invoice Analytics</h5>

                    <div class="dropdown main">
                        <button class="btn btn-white btn-sm dropdown-toggle" type="button" id="dropdownMenuButton1"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            Monthly
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item">Weekly</a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item">Monthly</a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item">Yearly</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div id="invoice_chart"></div>
                <div class="text-center text-muted">
                    <div class="row">
                        <div class="col-4">
                            <div class="mt-4">
                                <p class="mb-2 text-truncate"><i class="fas fa-circle text-primary me-1"></i> Invoiced
                                </p>
                                <h5>$2,132</h5>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="mt-4">
                                <p class="mb-2 text-truncate"><i class="fas fa-circle text-success me-1"></i> Received
                                </p>
                                <h5>$1,763</h5>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="mt-4">
                                <p class="mb-2 text-truncate"><i class="fas fa-circle text-danger me-1"></i> Pending</p>
                                <h5>$973</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
