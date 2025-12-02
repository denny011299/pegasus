<?php $page = 'salesDetail'; ?>

@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content">
            @component('components.breadcrumb')
                @slot('title')
                    Edit Stock Opname
                @endslot
                @slot('li_1')
                    Opname ID: {{ $id }}
                @endslot
            @endcomponent
            <form action="add-product">
                <div class="card pb-5">
                    <div class="card-body add-product pb-0">
                        <div class="accordion-card-one accordion mt-4" id="accordionExample1">
                            <div class="accordion-item">
                                <div class="accordion-header" id="headingOne">
                                    <div class="accordion-button" data-bs-toggle="collapse" data-bs-target="#collapseOne"
                                        aria-controls="collapseOne">
                                        <div class="text-editor add-list">
                                            <div class="addproduct-icon list icon">
                                                <h5><i data-feather="life-buoy" class="add-info"></i><span>Detail Stock
                                                        Opname</span>
                                                </h5>
                                                <a href="javascript:void(0);"><i data-feather="chevron-down"
                                                        class="chevron-down-add"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne"
                                    data-bs-parent="#accordionExample1">
                                    <div class="accordion-body">
                                        <div class="row mb-5">
                                            <div class="col-lg-4">
                                                <div class="input-blocks">
                                                    <label>Nama Penaggung jawab</label>
                                                    <input type="text" class="form-control" value="Shawn" readonly>
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="input-blocks">
                                                    <label>Tanggal</label>
                                                    <input type="date" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="add-newplus">
                                                    <label class="form-label">Kategori</label>
                                                </div>
                                                <select class="select" id="kategori" name="kategori[]" multiple>
                                                </select>
                                            </div>
                                            <div class="col-lg-12">
                                                <div class="input-blocks">
                                                    <label>Catatan</label>
                                                    <textarea class="form-control" placeholder="Masukkan catatan"></textarea>
                                                </div>
                                            </div>
                                            <div class="col-lg-12 d-flex">
                                                <div class="ms-auto btnCustom">
                                                    <button type="submit" class="btn btn-submit">Simpan Hasil
                                                        Opname</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table  datanew">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>SKU</th>
                                        <th>Nama Produk</th>
                                        <th>Jumlah Stok</th>
                                        <th>Jumlah Nyata</th>
                                        <th>Selisih</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td>NK-JRD-001</td>
                                        <td>
                                            <div class="productimgname">
                                                <a href="javascript:void(0);" class="product-img stock-img">
                                                    <img src="{{ URL::asset('/build/img/products/stock-img-02.png') }}"
                                                        alt="product">
                                                </a>
                                                <a href="javascript:void(0);">Nike Jordan</a>
                                            </div>
                                        </td>
                                        <td>120</td>
                                        <td>118</td>
                                        <td>
                                            <div class="product-quantity">
                                                <span class="quantity-btn"><i data-feather="minus-circle"
                                                        class="feather-search"></i></span>
                                                <input type="text" class="quntity-input" value="2">
                                                <span class="quantity-btn">+<i data-feather="plus-circle"
                                                        class="plus-circle"></i></span>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" placeholder="Tambahkan catatan...">
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
@section('custom_js')
    <script>
        var public = "{{ asset('') }}";
    </script>
    <script src="{{ asset('/Custom_js/Backoffice/Order/Invoice.js') }}?v={{ time() }}"></script>
@endsection
