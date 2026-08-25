<?php $page = 'stok'; ?>
@extends('layout.mainlayout')
@section('custom_css')
    <style>
        #tableLog {
            width: 100% !important;
            border-collapse: collapse;
        }

        #tableLog thead th {
            position: sticky;
            top: 0;
            z-index: 10;
        }

        #tableLog tbody td {
            vertical-align: middle;
            white-space: normal !important;
            word-wrap: break-word;
        }

        #tableStock {
            width: 100% !important;
            min-width: 800px !important;
        }

        #tableStock td {
            white-space: normal !important;
            word-wrap: break-word;
        }

        #tableStock tbody tr {
            cursor: pointer;
        }

        #tableStock td.cell-min-order {
            background: #fafbfc;
        }
        #tableStock td.cell-min-order:hover {
            background: #eff6ff;
        }

        #tableStock-wrap.is-loading tbody {
            opacity: 0.45;
            pointer-events: none;
        }

        #tableStock-wrap.dt-pending #tableStock {
            min-height: 180px;
        }
    </style>
@endsection
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    Stok Bahan Mentah
                @endslot
            @endcomponent
            <!-- /Page Header -->

            <!-- Search Filter -->
            @component('components.search-filter')
            @endcomponent
            <!-- /Search Filter -->

            <!-- Table -->
            <div class="row">
                <div class="col-sm-12">
                    <div class=" card-table">
                        <div class="card-body">
                            <div class="table-responsive dt-pending" id="tableStock-wrap">
                                <div class="dt-skeleton" aria-hidden="true">
                                    <div style="padding: 16px 25px;">
                                        <span class="skel-text" style="width: 250px; height: 38px; border-radius: 20px;"></span>
                                    </div>
                                    <div class="dt-skeleton-head" style="grid-template-columns: 75% 25%;">
                                        <span style="width:40%"></span>
                                        <span style="width:60%"></span>
                                    </div>
                                    <div class="dt-skeleton-body">
                                        @for ($i = 0; $i < 5; $i++)
                                            <div class="dt-skeleton-row" style="grid-template-columns: 75% 25%;">
                                                <span class="skel-text" style="width:60%"></span>
                                                <span class="skel-badge" style="width:60%;justify-self:center"></span>
                                            </div>
                                        @endfor
                                    </div>
                                </div>
                                <table class="table table-center table-hover" id="tableStock">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Nama Bahan Mentah</th>
                                            <th>Stok</th>
                                            <th class="col-min-order">Dasar Pemesanan Min.</th>
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
            <!-- /Table -->

        </div>
    </div>
    <!-- /Page Wrapper -->

    <!-- Modal Edit Pemesanan Min. Bahan -->
    <div class="modal fade custom-modal pg-modal--form" id="modal-edit-min-order-supplies" tabindex="-1" aria-labelledby="modalEditMinOrderSuppliesLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
            <div class="modal-content d-flex flex-column" style="border-radius: 16px; overflow: hidden; border: none;">
                <div class="modal-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="pg-modal-icon">
                            <i class="fe fe-shopping-cart"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 fw-bold modal-title" id="modalEditMinOrderSuppliesLabel">Edit Pemesanan Min.</h5>
                            <small class="modal-subtitle">Atur jumlah pembelian minimum ke supplier</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-4">
                    <div class="mb-4">
                        <label class="form-label text-muted fw-semibold mb-1" style="font-size:11px; text-transform:uppercase; letter-spacing:.4px;">Nama Bahan</label>
                        <div class="fw-semibold" id="emos-supplies-name" style="font-size:14px; color:#0f172a;">—</div>
                    </div>
                    <div class="row g-3">
                        <div class="col-8">
                            <label class="form-label fw-semibold" style="font-size:12px;" for="emos-min-order">Dasar Pemesanan Min.</label>
                            <input type="number" id="emos-min-order" class="form-control" min="0" step="1" placeholder="0" style="border-radius:8px;">
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-semibold" style="font-size:12px;">Satuan</label>
                            <input type="text" id="emos-min-order-unit" class="form-control" readonly style="background:#f1f5f9; border-radius:8px; color:#64748b;">
                        </div>
                    </div>
                    <div class="small text-muted mt-3" id="emos-calculated-hint" style="font-size:12px;"></div>
                    <input type="hidden" id="emos-supplies-id">
                </div>
                <div class="modal-footer pg-modal-footer">
                    <button type="button" class="btn pg-btn-cancel" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn pg-btn-save" id="emos-save-btn">
                        <span id="emos-save-spinner" class="spinner-border spinner-border-sm me-1 d-none" role="status"></span>
                        Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('custom_js')
    <script>
        var public = "{{ asset('') }}";    
    </script>
    <script src="{{asset('Custom_js/Backoffice/Inventory/Stock_Supplies.js')}}?v={{time()}}"></script>
@endsection
