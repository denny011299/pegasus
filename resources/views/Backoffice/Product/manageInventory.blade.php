<?php $page = 'manage-stocks'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content">
            @component('components.breadcrumb')
                @slot('title')
                    Manage Inventory
                @endslot
                @slot('li_1')
                    Kelola inventaris kantor
                @endslot
            @endcomponent
            <div class="card table-list-card">
                <div class="card-body">
                    <div class="row p-3 ">
                        <div class="col-4">
                            <div class="mb-3">
                                <label class="form-label">Barcode Number</label>
                                <input type="text" class="form-control" value="">
                            </div>
                        </div>
                        <div class="col-2">
                            <div class="mb-3">
                                <label class="form-label">Qty</label>
                                <input type="number" value="1" min="0" class="form-control" value="">
                            </div>
                        </div>
                        <div class="col-2">
                            <div class="mb-3">
                                <label class="form-label">Type</label>
                                <select name="" id="" class="form-select">
                                    <option value="in">Product In</option>
                                    <option value="out">Product Out</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-4 text-end pe-5 pb-2 pt-3">
                            <ul class="nav nav-tabs tab-style-1 d-sm-flex d-block" role="tablist"
                                style="border-radius: 10px">

                                <li class="nav-item">
                                    <a class="nav-link active" data-bs-toggle="tab" data-bs-target="#in"
                                        href="#returned">Auto Scan</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" data-bs-target="#out" href="#damaged">Manual
                                        Entry</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card table-list-card">
                <div class="card-body">
                    <div class="table-top">
                        <div class="search-set pt-2">
                            <div class="search-input">
                                <a href="" class="btn btn-searchset"><i data-feather="search"
                                        class="feather-search"></i></a>
                            </div>
                        </div>
                        <div class="search-path pt-2">
                            <div class="d-flex align-items-center">
                                <a class="btn btn-filter" id="filter_search">
                                    <i data-feather="filter" class="filter-icon"></i>
                                    <span><img src="{{ URL::asset('/build/img/icons/closes.svg') }}" alt="img"></span>
                                </a>
                            </div>
                        </div>
                        <div class="form-sort" style="width: auto">
                            <ul class="nav nav-tabs tab-style-1 d-sm-flex d-block" role="tablist"
                                style="border-radius: 10px">
                                <li class="nav-item">
                                    <a class="nav-link active" data-bs-toggle="tab" data-bs-target="#all"
                                        aria-current="page" href="#all">All</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" data-bs-target="#in" href="#in">Product
                                        In</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" data-bs-target="#out" href="#out">Product
                                        Out</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <!-- /Filter -->
                    <div class="card" id="filter_inputs">
                        <div class="card-body pb-0">
                            <div class="row">
                                <div class="col-lg-3 col-sm-6 col-12">
                                    <div class="input-blocks">
                                        <i data-feather="calendar" class="info-img"></i>
                                        <div class="input-groupicon">
                                            <input type="text" class="datetimepicker" placeholder="Choose Date">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-sm-6 col-12 ms-auto">
                                    <div class="input-blocks">
                                        <a class="btn btn-filters ms-auto"> <i data-feather="search"
                                                class="feather-search"></i> Search </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /Filter -->
                    <div class="table-responsive">
                        <div class="tab-content">
                            <div class="tab-pane active" id="all" role="tabpanel">
                                <table class="table  datanew">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Product</th>
                                            <th>SKU</th>
                                            <th>Product In</th>
                                            <th>Product Out</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>19 Nov 2022</td>
                                            <td>
                                                <div class="productimgname">
                                                    <a href="javascript:void(0);" class="product-img stock-img">
                                                        <img src="{{ URL::asset('/build/img/products/stock-img-01.png') }}"
                                                            alt="product">
                                                    </a>
                                                    <a href="javascript:void(0);">Lenovo 3rd Generation</a>
                                                </div>
                                            </td>
                                            <td>PT001</td>
                                            <td>20 Pcs</td>
                                            <td>10 Pcs</td>
                                        </tr>
                                        <tr>
                                            <td>24 Nov 2022</td>
                                            <td>
                                                <div class="productimgname">
                                                    <a href="javascript:void(0);" class="product-img stock-img">
                                                        <img src="{{ URL::asset('/build/img/products/stock-img-02.png') }}"
                                                            alt="product">
                                                    </a>
                                                    <a href="javascript:void(0);">Nike Jordan</a>
                                                </div>
                                            </td>
                                            <td>PT002</td>
                                            <td>20 Pcs</td>
                                            <td>10 Pcs</td>
                                        </tr>
                                        <tr>
                                            <td>11 Dec 2022</td>
                                            <td>
                                                <div class="productimgname">
                                                    <a href="javascript:void(0);" class="product-img stock-img">
                                                        <img src="{{ URL::asset('/build/img/products/stock-img-03.png') }}"
                                                            alt="product">
                                                    </a>
                                                    <a href="javascript:void(0);">Apple Series 5 Watch</a>
                                                </div>
                                            </td>
                                            <td>PT003</td>
                                            <td>20 Pcs</td>
                                            <td>10 Pcs</td>
                                        </tr>
                                        <tr>
                                            <td>27 Dec 2022</td>
                                            <td>
                                                <div class="productimgname">
                                                    <a href="javascript:void(0);" class="product-img stock-img">
                                                        <img src="{{ URL::asset('/build/img/products/stock-img-04.png') }}"
                                                            alt="product">
                                                    </a>
                                                    <a href="javascript:void(0);">Amazon Echo Dot</a>
                                                </div>
                                            </td>
                                            <td>PT004</td>
                                            <td>20 Pcs</td>
                                            <td>10 Pcs</td>
                                        </tr>
                                        <tr>
                                            <td>08 Jan 2023</td>
                                            <td>
                                                <div class="productimgname">
                                                    <a href="javascript:void(0);" class="product-img stock-img">
                                                        <img src="{{ URL::asset('/build/img/products/stock-img-05.png') }}"
                                                            alt="product">
                                                    </a>
                                                    <a href="javascript:void(0);">Lobar Handy</a>
                                                </div>
                                            </td>
                                            <td>PT005</td>
                                            <td>20 Pcs</td>
                                            <td>10 Pcs</td>
                                        </tr>
                                        <tr>
                                            <td>17 Jan 2023</td>
                                            <td>
                                                <div class="productimgname">
                                                    <a href="javascript:void(0);" class="product-img stock-img">
                                                        <img src="{{ URL::asset('/build/img/products/expire-product-01.png') }}"
                                                            alt="product">
                                                    </a>
                                                    <a href="javascript:void(0);">Red Premium Handy</a>
                                                </div>
                                            </td>
                                            <td>PT006</td>
                                            <td>20 Pcs</td>
                                            <td>10 Pcs</td>
                                        </tr>
                                        <tr>
                                            <td>22 Feb 2023</td>
                                            <td>
                                                <div class="productimgname">
                                                    <a href="javascript:void(0);" class="product-img stock-img">
                                                        <img src="{{ URL::asset('/build/img/products/expire-product-02.png') }}"
                                                            alt="product">
                                                    </a>
                                                    <a href="javascript:void(0);">Red Premium Handy</a>
                                                </div>
                                            </td>
                                            <td>PT007</td>
                                            <td>20 Pcs</td>
                                            <td>10 Pcs</td>
                                        </tr>
                                        <tr>
                                            <td>18 Mar 2023</td>
                                            <td>
                                                <div class="productimgname">
                                                    <a href="javascript:void(0);" class="product-img stock-img">
                                                        <img src="{{ URL::asset('/build/img/products/expire-product-03.png') }}"
                                                            alt="product">
                                                    </a>
                                                    <a href="javascript:void(0);">Black Slim 200</a>
                                                </div>
                                            </td>
                                            <td>PT008</td>
                                            <td>20 Pcs</td>
                                            <td>10 Pcs</td>
                                        </tr>
                                        <tr>
                                            <td>29 Mar 2023</td>
                                            <td>
                                                <div class="productimgname">
                                                    <a href="javascript:void(0);" class="product-img stock-img">
                                                        <img src="{{ URL::asset('/build/img/products/expire-product-04.png') }}"
                                                            alt="product">
                                                    </a>
                                                    <a href="javascript:void(0);">Woodcraft Sandal</a>
                                                </div>
                                            </td>
                                            <td>PT009</td>
                                            <td>20 Pcs</td>
                                            <td>10 Pcs</td>
                                        </tr>

                                    </tbody>
                                </table>
                            </div>
                            <div class="tab-pane" id="in" role="tabpanel">
                                <table class="table  datanew">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Product</th>
                                            <th>SKU</th>
                                            <th>Product In</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>19 Nov 2022</td>
                                            <td>
                                                <div class="productimgname">
                                                    <a href="javascript:void(0);" class="product-img stock-img">
                                                        <img src="{{ URL::asset('/build/img/products/stock-img-01.png') }}"
                                                            alt="product">
                                                    </a>
                                                    <a href="javascript:void(0);">Lenovo 3rd Generation</a>
                                                </div>
                                            </td>
                                            <td>PT001</td>
                                            <td>10 Pcs</td>
                                        </tr>
                                        <tr>
                                            <td>24 Nov 2022</td>
                                            <td>
                                                <div class="productimgname">
                                                    <a href="javascript:void(0);" class="product-img stock-img">
                                                        <img src="{{ URL::asset('/build/img/products/stock-img-02.png') }}"
                                                            alt="product">
                                                    </a>
                                                    <a href="javascript:void(0);">Nike Jordan</a>
                                                </div>
                                            </td>
                                            <td>PT002</td>
                                            <td>10 Pcs</td>
                                        </tr>
                                        <tr>
                                            <td>11 Dec 2022</td>
                                            <td>
                                                <div class="productimgname">
                                                    <a href="javascript:void(0);" class="product-img stock-img">
                                                        <img src="{{ URL::asset('/build/img/products/stock-img-03.png') }}"
                                                            alt="product">
                                                    </a>
                                                    <a href="javascript:void(0);">Apple Series 5 Watch</a>
                                                </div>
                                            </td>
                                            <td>PT003</td>
                                            <td>20 Pcs</td>
                                        </tr>
                                        <tr>
                                            <td>27 Dec 2022</td>
                                            <td>
                                                <div class="productimgname">
                                                    <a href="javascript:void(0);" class="product-img stock-img">
                                                        <img src="{{ URL::asset('/build/img/products/stock-img-04.png') }}"
                                                            alt="product">
                                                    </a>
                                                    <a href="javascript:void(0);">Amazon Echo Dot</a>
                                                </div>
                                            </td>
                                            <td>PT004</td>
                                            <td>20 Pcs</td>
                                        </tr>
                                        <tr>
                                            <td>08 Jan 2023</td>
                                            <td>
                                                <div class="productimgname">
                                                    <a href="javascript:void(0);" class="product-img stock-img">
                                                        <img src="{{ URL::asset('/build/img/products/stock-img-05.png') }}"
                                                            alt="product">
                                                    </a>
                                                    <a href="javascript:void(0);">Lobar Handy</a>
                                                </div>
                                            </td>
                                            <td>PT005</td>
                                            <td>21 Pcs</td>
                                        </tr>
                                        <tr>
                                            <td>17 Jan 2023</td>
                                            <td>
                                                <div class="productimgname">
                                                    <a href="javascript:void(0);" class="product-img stock-img">
                                                        <img src="{{ URL::asset('/build/img/products/expire-product-01.png') }}"
                                                            alt="product">
                                                    </a>
                                                    <a href="javascript:void(0);">Red Premium Handy</a>
                                                </div>
                                            </td>
                                            <td>PT006</td>
                                            <td>6 Pcs</td>
                                        </tr>
                                        <tr>
                                            <td>22 Feb 2023</td>
                                            <td>
                                                <div class="productimgname">
                                                    <a href="javascript:void(0);" class="product-img stock-img">
                                                        <img src="{{ URL::asset('/build/img/products/expire-product-02.png') }}"
                                                            alt="product">
                                                    </a>
                                                    <a href="javascript:void(0);">Red Premium Handy</a>
                                                </div>
                                            </td>
                                            <td>PT007</td>
                                            <td>3 Pcs</td>
                                        </tr>
                                        <tr>
                                            <td>18 Mar 2023</td>
                                            <td>
                                                <div class="productimgname">
                                                    <a href="javascript:void(0);" class="product-img stock-img">
                                                        <img src="{{ URL::asset('/build/img/products/expire-product-03.png') }}"
                                                            alt="product">
                                                    </a>
                                                    <a href="javascript:void(0);">Black Slim 200</a>
                                                </div>
                                            </td>
                                            <td>PT008</td>
                                            <td>11 Pcs</td>
                                        </tr>
                                        <tr>
                                            <td>29 Mar 2023</td>
                                            <td>
                                                <div class="productimgname">
                                                    <a href="javascript:void(0);" class="product-img stock-img">
                                                        <img src="{{ URL::asset('/build/img/products/expire-product-04.png') }}"
                                                            alt="product">
                                                    </a>
                                                    <a href="javascript:void(0);">Woodcraft Sandal</a>
                                                </div>
                                            </td>
                                            <td>PT009</td>
                                            <td>2 Pcs</td>
                                        </tr>

                                    </tbody>
                                </table>
                            </div>
                            <div class="tab-pane" id="out" role="out">
                                <table class="table  datanew">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Product</th>
                                            <th>SKU</th>
                                            <th>Product Out</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>19 Nov 2022</td>
                                            <td>
                                                <div class="productimgname">
                                                    <a href="javascript:void(0);" class="product-img stock-img">
                                                        <img src="{{ URL::asset('/build/img/products/stock-img-01.png') }}"
                                                            alt="product">
                                                    </a>
                                                    <a href="javascript:void(0);">Lenovo 3rd Generation</a>
                                                </div>
                                            </td>
                                            <td>PT001</td>
                                            <td>10 Pcs</td>
                                        </tr>
                                        <tr>
                                            <td>24 Nov 2022</td>
                                            <td>
                                                <div class="productimgname">
                                                    <a href="javascript:void(0);" class="product-img stock-img">
                                                        <img src="{{ URL::asset('/build/img/products/stock-img-02.png') }}"
                                                            alt="product">
                                                    </a>
                                                    <a href="javascript:void(0);">Nike Jordan</a>
                                                </div>
                                            </td>
                                            <td>PT002</td>
                                            <td>10 Pcs</td>
                                        </tr>
                                        <tr>
                                            <td>11 Dec 2022</td>
                                            <td>
                                                <div class="productimgname">
                                                    <a href="javascript:void(0);" class="product-img stock-img">
                                                        <img src="{{ URL::asset('/build/img/products/stock-img-03.png') }}"
                                                            alt="product">
                                                    </a>
                                                    <a href="javascript:void(0);">Apple Series 5 Watch</a>
                                                </div>
                                            </td>
                                            <td>PT003</td>
                                            <td>20 Pcs</td>
                                        </tr>
                                        <tr>
                                            <td>27 Dec 2022</td>
                                            <td>
                                                <div class="productimgname">
                                                    <a href="javascript:void(0);" class="product-img stock-img">
                                                        <img src="{{ URL::asset('/build/img/products/stock-img-04.png') }}"
                                                            alt="product">
                                                    </a>
                                                    <a href="javascript:void(0);">Amazon Echo Dot</a>
                                                </div>
                                            </td>
                                            <td>PT004</td>
                                            <td>20 Pcs</td>
                                        </tr>
                                        <tr>
                                            <td>08 Jan 2023</td>
                                            <td>
                                                <div class="productimgname">
                                                    <a href="javascript:void(0);" class="product-img stock-img">
                                                        <img src="{{ URL::asset('/build/img/products/stock-img-05.png') }}"
                                                            alt="product">
                                                    </a>
                                                    <a href="javascript:void(0);">Lobar Handy</a>
                                                </div>
                                            </td>
                                            <td>PT005</td>
                                            <td>21 Pcs</td>
                                        </tr>
                                        <tr>
                                            <td>17 Jan 2023</td>
                                            <td>
                                                <div class="productimgname">
                                                    <a href="javascript:void(0);" class="product-img stock-img">
                                                        <img src="{{ URL::asset('/build/img/products/expire-product-01.png') }}"
                                                            alt="product">
                                                    </a>
                                                    <a href="javascript:void(0);">Red Premium Handy</a>
                                                </div>
                                            </td>
                                            <td>PT006</td>
                                            <td>6 Pcs</td>
                                        </tr>
                                        <tr>
                                            <td>22 Feb 2023</td>
                                            <td>
                                                <div class="productimgname">
                                                    <a href="javascript:void(0);" class="product-img stock-img">
                                                        <img src="{{ URL::asset('/build/img/products/expire-product-02.png') }}"
                                                            alt="product">
                                                    </a>
                                                    <a href="javascript:void(0);">Red Premium Handy</a>
                                                </div>
                                            </td>
                                            <td>PT007</td>
                                            <td>3 Pcs</td>
                                        </tr>
                                        <tr>
                                            <td>18 Mar 2023</td>
                                            <td>
                                                <div class="productimgname">
                                                    <a href="javascript:void(0);" class="product-img stock-img">
                                                        <img src="{{ URL::asset('/build/img/products/expire-product-03.png') }}"
                                                            alt="product">
                                                    </a>
                                                    <a href="javascript:void(0);">Black Slim 200</a>
                                                </div>
                                            </td>
                                            <td>PT008</td>
                                            <td>11 Pcs</td>
                                        </tr>
                                        <tr>
                                            <td>29 Mar 2023</td>
                                            <td>
                                                <div class="productimgname">
                                                    <a href="javascript:void(0);" class="product-img stock-img">
                                                        <img src="{{ URL::asset('/build/img/products/expire-product-04.png') }}"
                                                            alt="product">
                                                    </a>
                                                    <a href="javascript:void(0);">Woodcraft Sandal</a>
                                                </div>
                                            </td>
                                            <td>PT009</td>
                                            <td>2 Pcs</td>
                                        </tr>

                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /product list -->
        </div>
    @endsection
