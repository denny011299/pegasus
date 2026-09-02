<?php $page = 'masalah_produk'; ?>
@extends('layout.mainlayout')
@section('content')
<style>
    .content-page-header,.page-header {
        margin-bottom: 0px !important;
    }
    .tab-content {
        padding-top: 0px !important;
        margin-top: 10px !important;
    }

    #add-product-issues .form-select {
        width: 100% !important;
        max-width: 100% !important;
        display: block;
    }

    /* Jika browser masih memaksa melebar karena teks opsi yang panjang */
    #add-product-issues select option {
        width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    #add-product-issues .select2-container {
        width: 100% !important;
    }
    body.modal-open {
        overflow-y: scroll !important;
        padding-right: 0 !important;
    }
    
    #tableReturn, #tableDamage {
        width: 100% !important;
        min-width: 800px;
    }
    #tableReturn td, #tableDamage td {
        white-space: normal !important;
        word-wrap: break-word;
    }
    #tableReturn td:last-child, #tableDamage td:last-child {
        white-space: nowrap !important;
    }
    #tableReturn td:last-child a, #tableDamage td:last-child a {
        display: inline-flex !important;
        align-items: center;
    }

    #tableReturn-wrap,
    #tableDamage-wrap {
        position: relative;
        border: none !important;
        border-radius: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
    }

    #tableReturn-wrap .dt-skeleton,
    #tableDamage-wrap .dt-skeleton {
        border-radius: 0;
        background: transparent;
        box-shadow: none;
    }

    #tableReturn_wrapper .dataTables_processing,
    #tableDamage_wrapper .dataTables_processing {
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        width: 100% !important;
        height: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        border: 0 !important;
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.72) !important;
        box-shadow: none !important;
        z-index: 20;
        align-items: center;
        justify-content: center;
        color: #1e293b;
        font-weight: 600;
        font-size: 14px;
    }

    #tableReturn-wrap:not(.is-loading) .dataTables_processing,
    #tableDamage-wrap:not(.is-loading) .dataTables_processing {
        display: none !important;
    }

    #tableReturn-wrap.is-loading .dataTables_processing,
    #tableDamage-wrap.is-loading .dataTables_processing {
        display: flex !important;
    }

    #tableReturn_wrapper .dataTables_processing > div,
    #tableDamage_wrapper .dataTables_processing > div {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 10px 16px;
        border-radius: 10px;
        background: #fff;
        border: 1px solid #e2e8f0;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
    }

    #tableReturn-wrap.is-loading tbody,
    #tableDamage-wrap.is-loading tbody {
        opacity: 0.45;
        pointer-events: none;
    }
</style>
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
              @component('components.page-header')
                        @slot('title')
                            Masalah Produk
                        @endslot
                @endcomponent
            <!-- /Page Header -->
             <ul class="nav nav-pills navtab-bg mt-md-0 mt-3">
                    <li class="nav-item nav-jenis" tipe="1" >
                        <a href="#return" data-bs-toggle="tab" class="nav-link active"style="border-radius: 10px">
                            Dikembalikan
                        </a>
                    </li>
                    <li class="nav-item nav-jenis"  tipe="2">
                        <a href="#damage" data-bs-toggle="tab" class="nav-link" style="border-radius: 10px">
                            Rusak / Hangus
                        </a>
                    </li>
                </ul>
            <!-- Search Filter -->
            @component('components.search-filter')
            @endcomponent
            <!-- /Search Filter -->

            <!-- Table -->
            <div class="row" style="">
                <div class="col-sm-12">
                    <div class=" card-table">
                        <div class="card-body">
							<div class="tab-content">
								<div class="tab-pane show active" id="return">
									<div class="table-responsive dt-pending" id="tableReturn-wrap">
                                        <div class="dt-skeleton" aria-hidden="true">
                                            <div style="padding: 16px 25px;">
                                                <span class="skel-text" style="width: 250px; height: 38px; border-radius: 20px;"></span>
                                            </div>
                                            <div class="dt-skeleton-head" style="grid-template-columns: 10% 10% 40% 15% 10% 10% 5%;">
                                                <span style="width:60%"></span>
                                                <span style="width:50%"></span>
                                                <span style="width:40%"></span>
                                                <span style="width:50%"></span>
                                                <span style="width:55%"></span>
                                                <span style="width:55%"></span>
                                                <span style="width:40%"></span>
                                            </div>
                                            <div class="dt-skeleton-body">
                                                @for ($i = 0; $i < 5; $i++)
                                                    <div class="dt-skeleton-row" style="grid-template-columns: 10% 10% 40% 15% 10% 10% 5%;">
                                                        <span class="skel-text" style="width:70%"></span>
                                                        <span class="skel-text" style="width:55%"></span>
                                                        <span class="skel-text" style="width:85%"></span>
                                                        <span class="skel-badge" style="width:55%;justify-self:center"></span>
                                                        <span class="skel-text" style="width:60%"></span>
                                                        <span class="skel-text" style="width:60%"></span>
                                                        <span class="skel-badge" style="width:40%;justify-self:center"></span>
                                                    </div>
                                                @endfor
                                            </div>
                                        </div>
                                        <table class="table table-center table-hover" id="tableReturn">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Tanggal Pengembalian</th>
                                                    <th>Kode</th>
                                                    <th>Catatan</th>
                                                    <th>Status</th>
                                                    <th>Dibuat Oleh</th>
                                                    <th>Diapprove/Ditolak Oleh</th>
                                                    <th class="no-sort">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                
                                            </tbody>
                                        </table>
                                    </div>
								</div>
								<div class="tab-pane" id="damage">
									<div class="table-responsive dt-pending" id="tableDamage-wrap">
                                        <div class="dt-skeleton" aria-hidden="true">
                                            <div style="padding: 16px 25px;">
                                                <span class="skel-text" style="width: 250px; height: 38px; border-radius: 20px;"></span>
                                            </div>
                                            <div class="dt-skeleton-head" style="grid-template-columns: 10% 10% 10% 25% 15% 10% 10% 10%;">
                                                <span style="width:60%"></span>
                                                <span style="width:50%"></span>
                                                <span style="width:50%"></span>
                                                <span style="width:40%"></span>
                                                <span style="width:50%"></span>
                                                <span style="width:55%"></span>
                                                <span style="width:55%"></span>
                                                <span style="width:40%"></span>
                                            </div>
                                            <div class="dt-skeleton-body">
                                                @for ($i = 0; $i < 5; $i++)
                                                    <div class="dt-skeleton-row" style="grid-template-columns: 10% 10% 10% 25% 15% 10% 10% 10%;">
                                                        <span class="skel-text" style="width:70%"></span>
                                                        <span class="skel-text" style="width:55%"></span>
                                                        <span class="skel-badge" style="width:55%;justify-self:center"></span>
                                                        <span class="skel-text" style="width:85%"></span>
                                                        <span class="skel-badge" style="width:55%;justify-self:center"></span>
                                                        <span class="skel-text" style="width:60%"></span>
                                                        <span class="skel-text" style="width:60%"></span>
                                                        <span class="skel-badge" style="width:40%;justify-self:center"></span>
                                                    </div>
                                                @endfor
                                            </div>
                                        </div>
                                        <table class="table table-center table-hover" id="tableDamage">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Tanggal Pengembalian</th>
                                                    <th>Kode</th>
                                                    <th>Referensi</th>
                                                    <th>Catatan</th>
                                                    <th>Status</th>
                                                    <th>Dibuat Oleh</th>
                                                    <th>Diapprove/Ditolak Oleh</th>
                                                    <th class="no-sort">Aksi</th>
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
            </div>
            <!-- /Table -->

        </div>
    </div>
    <!-- /Page Wrapper -->
@endsection

@section('custom_js')
    <script>
        var public = "{{ asset('') }}";
    </script>
    <script src="{{asset('Custom_js/Backoffice/Inventory/Product_Issues.js')}}?v={{ time() }}"></script>
@endsection