<?php $page = 'product_issues'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">

             <!-- Page Header -->
            @component('components.page-header')
                @slot('title')
                    Production
                @endslot
            @endcomponent
            <!-- /Page Header -->

            <!-- Search Filter -->
            @component('components.search-filter')
            @endcomponent
            <!-- /Search Filter -->

            <!-- Table -->
            <div class="row" style="margin-top: -7vh">
                <div class="col-sm-12">
                    <div class=" card-table">
                        <div class="card-body">
							<div class="tab-content">
								<div class="tab-pane show active" id="all">
									<div class="table-responsive">
                                        <table class="table table-center table-hover" id="tableAll">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Product</th>
                                                    <th>SKU</th>
                                                    <th>Qty</th>
                                                    <th>Status</th>
                                                    <th class="no-sort">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>2025-08-25</td>
                                                    <td>Botol Plastik 600ml</td>
                                                    <td>SKU-PL600</td>
                                                    <td>1,200</td>
                                                    <td><span class="badge bg-success">Selesai</span></td>
                                                     <td>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>2025-08-25</td>
                                                    <td>Kardus Kemasan</td>
                                                    <td>SKU-KDS01</td>
                                                    <td>500</td>
                                                    <td><span class="badge bg-info">Proses</span></td>
                                                     <td>
                                                        <button class="btn btn-sm btn-success"><i class="fa-solid fa-box"></i></button>
                                                        <button class="btn btn-sm btn-danger"><i class="fa-solid fa-ban"></i></button>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>2025-08-24</td>
                                                    <td>Label Stiker</td>
                                                    <td>SKU-LBL10</td>
                                                    <td>2,000</td>
                                                    <td><span class="badge bg-success">Selesai</span></td>
                                                    <td>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>2025-08-23</td>
                                                    <td>Tutup Botol</td>
                                                    <td>SKU-TBP05</td>
                                                    <td>1,500</td>
                                                    <td><span class="badge bg-secondary">Pending</span></td>
                                                    <td>
                                                         <button class="btn btn-sm btn-success"><i class="fa-solid fa-screwdriver-wrench"></i></button>
                                                        <button class="btn btn-sm btn-danger"><i class="fa-solid fa-ban"></i></button>
                                                    </td>
                                                </tr>
                                            </tbody>

                                        </table>
                                    </div>
								</div>
								<div class="tab-pane" id="pending">
									<div class="table-responsive">
                                        <table class="table table-center table-hover" id="tablePending">
                                            <thead class="thead-light">
                                                <tr>
                                                   <th>Date</th>
                                                    <th>Product</th>
                                                    <th>SKU</th>
                                                    <th>Qty</th>
                                                    <th>Status</th>
                                                    <th class="no-sort">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>2025-08-25</td>
                                                    <td>Botol Plastik 600ml</td>
                                                    <td>SKU-PL600</td>
                                                    <td>800</td>
                                                    <td><span class="badge bg-secondary">Pending</span></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-success"><i class="fa-solid fa-screwdriver-wrench"></i></button>
                                                        <button class="btn btn-sm btn-danger"><i class="fa-solid fa-ban"></i></button>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>2025-08-24</td>
                                                    <td>Kardus Kemasan</td>
                                                    <td>SKU-KDS01</td>
                                                    <td>300</td>
                                                    <td><span class="badge bg-secondary">Pending</span></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-success"><i class="fa-solid fa-screwdriver-wrench"></i></button>
                                                        <button class="btn btn-sm btn-danger"><i class="fa-solid fa-ban"></i></button>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>2025-08-23</td>
                                                    <td>Label Stiker</td>
                                                    <td>SKU-LBL10</td>
                                                    <td>1,200</td>
                                                    <td><span class="badge bg-secondary">Pending</span></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-success"><i class="fa-solid fa-screwdriver-wrench"></i></button>
                                                        <button class="btn btn-sm btn-danger"><i class="fa-solid fa-ban"></i></button>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>2025-08-22</td>
                                                    <td>Tutup Botol</td>
                                                    <td>SKU-TBP05</td>
                                                    <td>650</td>
                                                    <td><span class="badge bg-secondary">Pending</span></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-success"><i class="fa-solid fa-screwdriver-wrench"></i></button>
                                                        <button class="btn btn-sm btn-danger"><i class="fa-solid fa-ban"></i></button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
								</div>
								<div class="tab-pane" id="progress">
									<div class="table-responsive">
                                        <table class="table table-center table-hover" id="tableProgress">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Product</th>
                                                    <th>SKU</th>
                                                    <th>Qty</th>
                                                    <th>Status</th>
                                                    <th class="no-sort">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>2025-08-25</td>
                                                    <td>Botol Plastik 1500ml</td>
                                                    <td>SKU-PL1500</td>
                                                    <td>1,000</td>
                                                    <td><span class="badge bg-info">Progress</span></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-success"><i class="fa-solid fa-box"></i></button>
                                                        <button class="btn btn-sm btn-danger"><i class="fa-solid fa-ban"></i></button>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>2025-08-24</td>
                                                    <td>Kardus Besar</td>
                                                    <td>SKU-KDB01</td>
                                                    <td>700</td>
                                                    <td><span class="badge bg-info">Progress</span></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-success"><i class="fa-solid fa-box"></i></button>
                                                        <button class="btn btn-sm btn-danger"><i class="fa-solid fa-ban"></i></button>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>2025-08-23</td>
                                                    <td>Label Botol Premium</td>
                                                    <td>SKU-LBL20</td>
                                                    <td>1,500</td>
                                                    <td><span class="badge bg-info">Progress</span></td>
                                                    <td>
                                                       <button class="btn btn-sm btn-success"><i class="fa-solid fa-box"></i></button>
                                                        <button class="btn btn-sm btn-danger"><i class="fa-solid fa-ban"></i></button>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>2025-08-22</td>
                                                    <td>Tutup Botol Fliptop</td>
                                                    <td>SKU-TBF10</td>
                                                    <td>950</td>
                                                    <td><span class="badge bg-info">Progress</span></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-success"><i class="fa-solid fa-box"></i></button>
                                                        <button class="btn btn-sm btn-danger"><i class="fa-solid fa-ban"></i></button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
								</div>
								<div class="tab-pane" id="packing">
									<div class="table-responsive">
                                        <table class="table table-center table-hover" id="tablePacking">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Product</th>
                                                    <th>SKU</th>
                                                    <th>Qty</th>
                                                    <th>Status</th>
                                                    <th class="no-sort">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>2025-08-25</td>
                                                    <td>Botol Plastik 1500ml</td>
                                                    <td>SKU-PL1500</td>
                                                    <td>1,000</td>
                                                    <td><span class="badge bg-primary">Packing</span></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-success"><i class="fa-solid fa-check"></i></button>
                                                        <button class="btn btn-sm btn-danger"><i class="fa-solid fa-ban"></i></button>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>2025-08-24</td>
                                                    <td>Kardus Besar</td>
                                                    <td>SKU-KDB01</td>
                                                    <td>700</td>
                                                    <td><span class="badge bg-primary">Packing</span></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-success"><i class="fa-solid fa-check"></i></button>
                                                        <button class="btn btn-sm btn-danger"><i class="fa-solid fa-ban"></i></button>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>2025-08-23</td>
                                                    <td>Label Botol Premium</td>
                                                    <td>SKU-LBL20</td>
                                                    <td>1,500</td>
                                                    <td><span class="badge bg-primary">Packing</span></td>
                                                    <td>
                                                       <button class="btn btn-sm btn-success"><i class="fa-solid fa-check"></i></button>
                                                    <button class="btn btn-sm btn-danger"><i class="fa-solid fa-ban"></i></button>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>2025-08-22</td>
                                                    <td>Tutup Botol Fliptop</td>
                                                    <td>SKU-TBF10</td>
                                                    <td>950</td>
                                                    <td><span class="badge bg-primary">Packing</span></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-success"><i class="fa-solid fa-check"></i></button>
                                                    <button class="btn btn-sm btn-danger"><i class="fa-solid fa-ban"></i></button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
								</div>
								<div class="tab-pane" id="done">
									<div class="table-responsive">
                                        <table class="table table-center table-hover" id="tableDone">
                                            <thead class="thead-light">
                                                <tr>
                                                     <th>Date</th>
                                                    <th>Product</th>
                                                    <th>SKU</th>
                                                    <th>Qty</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                           <tbody>
                                                <tr>
                                                    <td>2025-08-25</td>
                                                    <td>Botol Plastik 600ml</td>
                                                    <td>SKU-PL600</td>
                                                    <td>1,200</td>
                                                    <td><span class="badge bg-success">Done</span></td>
                                                    
                                                </tr>
                                                <tr>
                                                    <td>2025-08-24</td>
                                                    <td>Kardus Kemasan</td>
                                                    <td>SKU-KDS01</td>
                                                    <td>500</td>
                                                    <td><span class="badge bg-success">Done</span></td>
                                                </tr>
                                                <tr>
                                                    <td>2025-08-23</td>
                                                    <td>Label Stiker</td>
                                                    <td>SKU-LBL10</td>
                                                    <td>2,000</td>
                                                   <td><span class="badge bg-success">Done</span></td>
                                                </tr>
                                                <tr>
                                                    <td>2025-08-22</td>
                                                    <td>Tutup Botol</td>
                                                    <td>SKU-TBP05</td>
                                                    <td>1,500</td>
                                                    <td><span class="badge bg-success">Done</span></td>
                                                </tr>
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
    <script src="{{asset('Custom_js/Backoffice/Production/Production.js')}}"></script>
@endsection