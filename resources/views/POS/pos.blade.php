<?php $page = 'pos'; ?>
@extends('layout.mainlayout')
@section('content')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/dayjs@1.10.4/dayjs.min.js"></script>

    <style>
        .tprimary {
            color: #007aff !important;
        }

        .tprimary:hover {
            color: white !important;
        }

        .bprimary {
            background-color: #007aff !important;
            color: white !important;
        }

        .box-icon {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            color: #007aff;
            background-color: white;
        }

        .footer {
            position: fixed;
            bottom: 0px;
            background-color: white;
            width: 33%;
        }

        .product-wrap {
            height: auto;
            min-height: 25vh !important;
            overflow: auto;
        }

        .default-cover a:hover {
            color: #007aff !important;
        }

        .default-cover a:active {
            color: #007aff !important;
            border: 1px solid #007aff !important;
        }

        .icon {
            background-color: #c4c4c4;
            border-radius: 400px;
            width: 40px;
            height: 40px;
            text-align: center;
        }

        .icon-active {
            background-color: #007bff;
            border-radius: 400px;
            width: 40px;
            height: 40px;
            text-align: center;
        }

        .box-category-active {
            border-radius: 15px;
            border: 1px solid #007bff !important;
            color: #007bff !important;
            background-color: #fff !important;
            font-weight: bold;
            color: white !important;
            cursor: pointer;
        }

        .box-category {
            border-radius: 15px;
            border: 1px solid rgb(235, 235, 235);
            color: #a0a0a0;
            background-color: #ffffff;
            font-weight: bold;
            color: white;
            width: 100%px !important;
            cursor: pointer;
        }

        .text-default-active {
            color: #007bff !important;
        }

        .text-default {
            color: #a0a0a0;
        }

        /* Custom Swiper Navigation Buttons */
        .swiper-button-next,
        .swiper-button-prev {
            color: #007aff !important;
            width: 30px !important;
            height: 30px !important;
            margin-top: -15px !important;
            background: white;
            border-radius: 50%;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .swiper-button-next:after,
        .swiper-button-prev:after {
            font-size: 14px !important;
            font-weight: bold;
        }

        .swiper-button-next.swiper-button-disabled,
        .swiper-button-prev.swiper-button-disabled {
            opacity: 0.3;
        }

        /* Power Toggle Styles */
        .power-toggle-container {
            transition: background-color 0.3s ease !important;
        }

        .power-toggle-slider {
            transition: all 0.3s ease !important;
        }

        .toggle-text-left,
        .toggle-text-right {
            transition: opacity 0.3s ease !important;
            pointer-events: none;
        }

        .power-btn i {
            transition: color 0.3s ease !important;
        }

        .text-danger {
            color: #dc3545 !important;
        }

        /* Payment Method Styles */
        .payment-method-item {
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .payment-method-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .payment-method-active {
            background-color: #007aff !important;
            border: 2px solid #007aff !important;
        }

        .payment-method-active img {
            filter: brightness(0) invert(1);
        }

        .payment-method-active span {
            color: white !important;
            font-weight: bold;
        }

        /* Promo Swiper Styles */
        .promoSwiper .swiper-button-next,
        .promoSwiper .swiper-button-prev {
            color: #007aff !important;
            width: 30px !important;
            height: 30px !important;
            margin-top: -15px !important;
            background: white;
            border-radius: 50%;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .promoSwiper .swiper-button-next:after,
        .promoSwiper .swiper-button-prev:after {
            font-size: 14px !important;
            font-weight: bold;
        }

        .promo-card {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            margin-right: 10px;
        }

        .promo-card:hover {
            border-color: #007aff;
            box-shadow: 0 4px 12px rgba(0, 122, 255, 0.1);
        }

        .promo-card.selected {
            border-color: #007aff;
            background-color: #f8f9ff;
            box-shadow: 0 4px 12px rgba(0, 122, 255, 0.2);
        }

        .promo-card.selected .promo-name {
            color: #007aff;
            font-weight: bold;
        }

        .promo-name {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .promo-type {
            font-size: 12px;
            color: #666;
            margin-bottom: 3px;
        }

        .promo-discount {
            font-size: 16px;
            font-weight: bold;
            color: #007aff;
        }

        .promo-check-icon {
            position: absolute;
            top: 8px;
            right: 8px;
            color: #007aff;
            font-size: 16px;
            display: none;
        }

        .promo-card.selected .promo-check-icon {
            display: block;
        }

        /* Consistent promo card width - always 25% equivalent */
        .promoSwiper .swiper-slide {
            width: calc(25% - 11.25px) !important;
            /* 25% minus spacing compensation */
            flex-shrink: 0;
        }

        .promo-card {
            width: 100%;
        }

        /* Responsive adjustments */
        @media (max-width: 991px) {
            .promoSwiper .swiper-slide {
                width: calc(33.333% - 10px) !important;
                /* 3 cards on tablet */
            }
        }

        @media (max-width: 767px) {
            .promoSwiper .swiper-slide {
                width: calc(50% - 7.5px) !important;
                /* 2 cards on small tablet */
            }
        }

        @media (max-width: 575px) {
            .promoSwiper .swiper-slide {
                width: calc(100% - 0px) !important;
                /* 1 card on mobile */
            }
        }

        /* Promo Recap Styles */
        .promo-recap {
            background-color: #f8f9ff;
            border: 1px solid #e0e6ff;
            border-radius: 8px;
            padding: 15px;
        }

        .promo-recap h6 {
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .recap-content {
            font-size: 16px;
            font-weight: 500;
            color: #007aff;
            line-height: 1.4;
        }

        .recap-separator {
            margin: 0 5px;
            color: #666;
        }

        /* Modal Button Styles */
        .modal-footer-btn {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding: 20px 0 0 0;
            border-top: 1px solid #eee;
            margin-top: 24px !important;
            padding-top: 20px;
        }

        .modal-footer-btn .btn {
            padding: 12px 24px;
            font-weight: 500;
            font-size: 14px;
            border-radius: 8px !important;
            border: none;
            min-width: 120px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .modal-footer-btn .btn-cancel {
            background-color: #f8f9fa;
            color: #6c757d;
            border: 1px solid #dee2e6 !important;
        }

        .modal-footer-btn .btn-cancel:hover {
            background-color: #e9ecef;
            color: #495057;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .modal-footer-btn .btn-submit {
            background-color: #007aff;
            color: white;
            border: 1px solid #007aff !important;
        }

        .modal-footer-btn .btn-submit:hover {
            background-color: #0056b3;
            border-color: #0056b3;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 122, 255, 0.3);
        }

        .modal-footer-btn .btn:active {
            transform: translateY(0);
        }

        .modal-footer-btn .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        /* Customer Selection Styles */
        .customer-item {
            transition: all 0.3s ease;
            border-radius: 8px;
            margin-bottom: 2px;
        }

        .customer-item:hover {
            background-color: #f8f9fa;
        }

        .customer-item.selected {
            background-color: #e3f2fd;
            border-color: #007aff;
        }

        .customer-item.selected .select-customer-btn {
            background-color: #28a745;
            border-color: #28a745;
        }

        .customer-info h6 {
            color: #333;
            font-weight: 600;
        }

        .customer-info .feather-12 {
            width: 12px;
            height: 12px;
            margin-right: 4px;
        }

        .search-input {
            position: relative;
        }

        .search-addon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }

        #customer_btn {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            padding: 8px 16px;
        }

        #customer_btn i {
            width: 16px;
            height: 16px;
        }

        /* Pending Order Styles */
        .pending-order-card {
            border: 1px solid #e0e6ff;
            transition: all 0.3s ease;
        }

        .pending-order-card:hover {
            box-shadow: 0 4px 12px rgba(0, 122, 255, 0.1);
            border-color: #007aff;
        }

        .btn-complete-order {
            transition: all 0.3s ease;
        }

        .btn-complete-order:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
        }

        .btn-delete-pending {
            transition: all 0.3s ease;
        }

        .btn-delete-pending:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
        }

        #pending-count-badge {
            font-size: 0.7rem;
        }

        /* No Product Found Styles */
        .no-product-found {
            text-align: center;
            padding: 60px 20px;
            width: 100%;
        }

        .no-product-found i {
            width: 64px;
            height: 64px;
            color: #6c757d;
            margin-bottom: 16px;
        }

        .no-product-found h5 {
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .no-product-found p {
            font-size: 14px;
            margin-bottom: 0;
        }
    </style>
    <div class="page-wrapper pos-pg-wrapper ms-0">
        <div class="content pos-design p-0">
            <div class="row align-items-start pos-wrapper">
                <div class="col-md-12 col-lg-8">
                    <div class="pos-categories tabs_wrapper">
                        <div class="swiper mySwiper mb-2">
                            <div class="swiper-wrapper ">
                                <!-- Slide 1 -->
                                {{-- 
                                        <div class="swiper-slide">
                                            <div class="category p-2 text-center py-3">
                                                <i class="bi bi-fork-knife mx-auto" style="font-size:18pt"></i>
                                                <p class="my-0" style="font-size:10pt">All Food</p>
                                            </div>
                                        </div> --}}
                                @foreach ($category as $item)
                                    <div class="swiper-slide">
                                        <div class="box-category {{ $loop->first ? 'box-category-active' : '' }} p-4 ps-2 pt-2 pb-2 categorys"
                                            data-id="{{ $item->category_id }}">
                                            <div class="row">
                                                <div class="col-3">
                                                    <div class=" pt-2  icon {{ $loop->first ? 'icon-active' : '' }}"><i
                                                            class="bi bi-phone mx-auto" style="font-size:12pt"></i></div>
                                                </div>
                                                <div class="col-9">
                                                    <p class="mb-0 ps-2 text-default {{ $loop->first ? 'text-default-active' : '' }}"
                                                        style="font-size:10pt;">{{ $item->category_name }}</p>
                                                    @if ($item->category_id != -1)
                                                        <p class=" ps-2 text-muted" style="font-size: 9pt;">
                                                            {{ $item->count_product }} Items</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <!-- Navigation buttons -->
                            <div class="swiper-button-next"></div>
                            <div class="swiper-button-prev"></div>
                        </div>
                        <div class="pos-products">
                            <div class="d-flex align-items-center justify-content-between">
                                {{-- <h5 class="mb-3">Semua Produk</h5> --}}
                                <div class="search-product mb-3 ms-auto" style="width: 300px;">
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="searchbar_product"
                                            style="border-radius:50px; border:1px solid #007aff"
                                            placeholder="Cari produk...">
                                    </div>
                                </div>
                            </div>
                            <div class="tabs_container">
                                <div class="tab_content active" data-tab="all">
                                    <div class="row" id="list-product">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 col-lg-4 ps-0 pb-5" style="overflow-y: auto;position: relative;">
                    <aside class="product-order-list">
                        <div class="product-added block-section">
                            <div class="head-text ">
                                <div class="row">
                                    <div class="col-6">
                                        <h3 class="d-flex align-items-center mb-0 font-weight-bold"
                                            style="color: #007aff !important">Invoice</h3>
                                    </div>
                                    <div class="col-6">
                                        <div class="flex-grow-1">
                                            <button type="button" class="btn btn-outline-primary ms-auto"
                                                style="border: #007aff 1px solid; border-radius: 50px; color: #007aff;"
                                                id="customer_btn" data-bs-toggle="modal" data-bs-target="#selectCustomer">
                                                <i data-feather="user" class="me-2"></i>Walk in Customer
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="product-wrap" style="min-height: 150px">
                                <div class="cart-list" id='list-cart'>
                                </div>
                            </div>
                        </div>
                        {{-- <div class="order-total">
                            <table class="table table-responsive table-borderless">
                                <tr>
                                    <td>Sub Total</td>
                                    <td class="text-end" id="subtotal">Rp 0</td>
                                </tr>
                                <tr class="mb-10">
                                    <td class="danger">Total Potongan</td>
                                    <td class="danger text-end" id="discount">Rp 0</td>
                                </tr>
                                <tr>
                                    <td>Total</td>
                                    <td class="text-end fw-bold total" style="color:#007aff">Rp 0</td>
                                </tr>
                            </table>
                        </div> --}}
                    </aside>
                    <div class="footer p-3">
                        <div class="row mb-3">
                            <div class="col-7">
                                {{-- <button class="btn w-100" id="kode_promo" type="button"
                                    style="border: #007aff 1px solid; border-radius: 50px; color: #007aff;"
                                    data-bs-toggle="modal" data-bs-target="#add-promo">
                                    Pilih Kode Promo
                                </button> --}}
                                <p class="text-muted mb-0" style="font-size: 1rem;">Sales</p>
                                <select class="form-select" style="border-radius:100px" id="sales_id">
                                    <option value="">Pilih Sales</option>
                                </select>
                            </div>
                            <div class="col-5">
                                <p class="text-end text-muted mb-0" style="font-size: 0.8rem;">Total</p>
                                <h4 class="text-end fw-bold mt-0 total" style="color:#007aff">Rp 0</h4>
                            </div>
                        </div>
                        {{-- Pending Order --}}
                        <div class="row mb-2">
                            <div class="col-6">
                                <button class="btn w-100"
                                    style="background-color: #007aff; border-color: #007aff; border-radius: 8px; height: 3rem; color: white;"
                                    id="btn-pending-order">
                                    <i data-feather="clock" class="me-1 feather-16"></i>Pending
                                </button>
                            </div>
                            <div class="col-6">
                                <button class="btn w-100 position-relative"
                                    style="background-color: #007aff; border-color: #007aff; border-radius: 8px; height: 3rem; color: white;"
                                    id="btn-view-pending" data-bs-toggle="modal" data-bs-target="#pendingOrdersModal">
                                    <i data-feather="list" class="me-1 feather-16"></i>Lihat
                                    <span
                                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                        id="pending-count-badge" style="display: none;">
                                        0
                                    </span>
                                </button>
                            </div>
                        </div>
                        <div>
                            <button class="btn btnCustom text-white font-bold"
                                style="background-color: #007aff;width: 100%;height: 4rem;font-size: 1rem;"
                                id="btn-create-order">Buat Orderan</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Orders Modal -->
    <div class="modal fade" id="pendingOrdersModal" tabindex="-1" aria-labelledby="pendingOrdersModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pendingOrdersModalLabel">
                        <i data-feather="clock" class="me-2"></i>Pending Orders
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="pending-orders-list">
                        <div class="text-center py-4" id="no-pending-orders">
                            <i data-feather="inbox" class="mb-3"
                                style="width: 48px; height: 48px; color: #6c757d;"></i>
                            <p class="text-muted">Tidak ada pending order</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <input type="hidden" id="status">
@endsection

@section('custom_js')
    <script>
        var public = "{{ asset('') }}";
        var store = @json($store);
    </script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <script src="{{ asset('/Custom_js/Backoffice/Printer.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('/Custom_js/Backoffice/Pos.js') }}?v={{ time() }}"></script>
@endsection
