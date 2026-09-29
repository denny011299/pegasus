@extends('layout.fullscreen')
@section('custom_css')
<style>
    .wo-monitor #tableWo { font-size:1.35rem; }
    #tableWo-wrap { position:relative; min-height:420px; }
    #tableWo-wrap.dt-pending .dt-skeleton { display:block; }
    #tableWo-wrap.dt-ready .dt-skeleton { display:none; }
    #tableWo-wrap.is-loading tbody { opacity:.45; pointer-events:none; }
</style>
@endsection
@section('content')
{{-- Monitor fullscreen saja — operasional WO lewat modal di Production Planning --}}
<div class="page-wrapper wo-touch wo-monitor"><div class="content container-fluid">
    <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
        <div>
            <h3>Monitor Work Order</h3>
            <p class="text-muted mb-0">Tampilan besar semua WO aktif — diperbarui tiap 30 detik</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="{{ route('productionPlanning', ['tab'=>'job']) }}">Kembali ke Job Order</a>
            <button type="button" class="btn btn-primary" id="wo-fullscreen">Layar Penuh</button>
        </div>
    </div>
    <div class="card"><div class="card-body row g-2">
        <div class="col-md-3"><label>SPKP / PP</label><select class="form-select" id="wo-filter-line"><option value="">Semua</option></select></div>
        <div class="col-md-3"><label>Status</label><select class="form-select" id="wo-filter-state"><option value="">Semua status</option><option value="inprod">In Production</option><option value="done">Completed / Ditutup</option></select></div>
        <div class="col-md-3"><label>Dari tanggal</label><input type="date" class="form-control" id="wo-filter-from"></div>
        <div class="col-md-3"><label>Sampai tanggal</label><input type="date" class="form-control" id="wo-filter-to"></div>
    </div></div>
    <div class="card"><div class="card-body table-responsive dt-pending" id="tableWo-wrap">
        <div class="dt-skeleton p-4"><span class="skel-text" style="width:100%;height:180px;"></span></div>
        <table class="table table-hover" id="tableWo"><thead><tr><th>WO / SPKP</th><th>Tanggal</th><th>SPV / PIC</th><th>SPKP/PP</th><th>Status</th><th>Aksi</th></tr></thead><tbody></tbody></table>
    </div></div>
</div></div>
@include('components.modals.production.wo-execution')
@endsection
@section('custom_js')
<script>window.woMonitor = true; window.woEmbed = false;</script>
<script src="{{ asset('Custom_js/Backoffice/Production/WorkOrders.js') }}?v={{ time() }}"></script>
@endsection
