{{-- Indikator Stock Opname — style mengikuti dash-toolbar / premium card --}}
@php
    $opnameWhId = (int) \App\Models\ProductStock::resolveWarehouseId(null);
    $opnameSnap = $opnameWhId > 0
        ? app(\App\Support\StockOpname\OpenOpnameGuard::class)->statusForWarehouse($opnameWhId)
        : [
            'product' => ['open' => false, 'code' => null, 'url' => url('/stockOpname')],
            'supplies' => ['open' => false, 'code' => null, 'url' => url('/stockOpnameBahan')],
            'any_open' => false,
        ];
    $opnameProductOpen = !empty($opnameSnap['product']['open']);
    $opnameSuppliesOpen = !empty($opnameSnap['supplies']['open']);
    $opnameProductLabel = $opnameProductOpen ? 'Opname Produk aktif' : 'Opname Produk nonaktif';
    $opnameSuppliesLabel = $opnameSuppliesOpen ? 'Opname Bahan aktif' : 'Opname Bahan nonaktif';
@endphp
<div class="opname-status-badges" id="opname-lamp-group" role="status" aria-label="Status Stock Opname"
     data-warehouse-id="{{ $opnameWhId }}">
    <a href="/stockOpname"
       class="opname-status-badge {{ $opnameProductOpen ? 'is-on' : '' }}"
       id="opname-lamp-product" data-domain="product"
       data-bs-toggle="tooltip" data-bs-placement="bottom" title="{{ $opnameProductLabel }}">
        <span class="opname-status-dot" aria-hidden="true"></span>
        <span class="opname-status-label">{{ $opnameProductLabel }}</span>
    </a>
    <a href="/stockOpnameBahan"
       class="opname-status-badge {{ $opnameSuppliesOpen ? 'is-on' : '' }}"
       id="opname-lamp-supplies" data-domain="supplies"
       data-bs-toggle="tooltip" data-bs-placement="bottom" title="{{ $opnameSuppliesLabel }}">
        <span class="opname-status-dot" aria-hidden="true"></span>
        <span class="opname-status-label">{{ $opnameSuppliesLabel }}</span>
    </a>
</div>
