<!--- modal Delete -->
<style>
    #video.rot90 { transform: rotate(90deg); }
    #video.rot180 { transform: rotate(180deg); }
    #video.rot270 { transform: rotate(270deg); }
 .is-invalid{
            border-color: #dc3545!important;
        }
        .is-invalids {
            border-color: #dc3545!important;
        }
</style>
@include('components.modals.shared.modal-photo')

@include('components.modals.shared.modal-view-photo')

<!--- modal Delete -->
@include('components.modals.shared.modal-delete')

<!--- modal Konfirmasi -->
@include('components.modals.shared.modal-konfirmasi')


@if (Route::is(['category']))
    <!-- modal -->
    @include('components.modals.category.add-category')


@endif

@if (Route::is(['bank']))
    <!-- modal -->
    @include('components.modals.bank.add-bank')


@endif

@if (Route::is(['tt']))
    <!-- modal -->
    @include('components.modals.tt.add-acc-tt')

    @include('components.modals.tt.view-tt')


@endif

@if (Route::is(['unit']))
    <!-- modal -->
    @include('components.modals.unit.add-unit')


@endif

@if (Route::is(['variant']))
    <!-- modal -->
    @include('components.modals.variant.add-variant')

@endif

@if (Route::is(['productIssue']))
    <!-- Add coupons -->
    @include('components.modals.product-issue.add-product-issues')

    <!-- /Add Coupons -->
@endif
@if (Route::is(['production']))
    @include('components.modals.production.add-production')

@endif
@if (Route::is(['supplies']))
    <!-- modal -->
    @include('components.modals.supplies.add-supplies')

@endif

@if (Route::is(['salesOrder']))
    <!-- modal -->
    @include('components.modals.sales-order.add-sales-order')

@endif

@if (Route::is(['salesOrderDetail']))
    <!-- modal -->
    @include('components.modals.sales-order-detail.add-sales-delivery')


    <!-- modal -->
    @include('components.modals.sales-order-detail.add-sales-invoice')

@endif

@if (Route::is(['purchaseOrder']))
    <!-- modal -->
    @include('components.modals.purchase-order.add-purchase-order')

@endif
@if (Route::is(['purchaseOrderDetail']))
    @include('components.modals.purchase-order-detail.add-retur')


    <!-- modal: Tambah Delivery Notes -->
    @include('components.modals.purchase-order-detail.add-purchase-delivery')


    <!-- modal: Tambah Faktur Pembelian -->
    @include('components.modals.purchase-order-detail.add-purchase-invoice')

@endif


@if (Route::is(['staff']))
    <!-- Hapus User Modal -->
    @include('components.modals.staff.delete-modal')

    <!-- /Hapus User Modal -->

    
    <!-- Tambah User -->
    @include('components.modals.staff.add-user')

    <!-- /Tambah User -->
@endif

@if (Route::is(['cash']))
    <!-- modal -->
    @include('components.modals.cash.add-cash')


    @include('components.modals.cash.modal-detail-sales')

@endif

@if (Route::is(['pettyCash']))
    <!-- modal -->
    @include('components.modals.petty-cash.add-petty-cash')

@endif

@if (Route::is(['operationalCash']))
    <!-- modal -->
    @include('components.modals.operational-cash.add-cash-admin')

    @include('components.modals.operational-cash.add-cash-gudang')

    @include('components.modals.operational-cash.add-cash-armada')

    @include('components.modals.operational-cash.add-cash-sales')

@endif

@if (Route::is(['role']))
    <!-- modal -->
    @include('components.modals.role.add-role')

@endif

@if (Route::is(['bom']))
    <!-- modal -->
    @include('components.modals.bom.add-bom')

@endif

@if (Route::is(['area']))
    <!-- modal -->
    @include('components.modals.area.add-area')


@endif

@if (Route::is(['purchaseOrderDetail']))
    <!-- modal -->
    @include('components.modals.purchase-order-detail.modal-terima')


@endif

@if (Route::is(['cashCategory']))
    <!-- modal -->
    @include('components.modals.cash-category.add-cash-category')


@endif

@if (Route::is(['stockProduct']))
    <!-- Add coupons -->
    @include('components.modals.stock-product.add-stock-product')

    <!-- /Add Coupons -->
@endif

@if (Route::is(['stockSupplies']))
    <!-- Add coupons -->
    @include('components.modals.stock-supplies.add-stock-supplies')

    <!-- /Add Coupons -->
@endif

@if (Route::is(['supplier']))
    <!-- Add coupons -->
    @include('components.modals.supplier.view-supplier')

    <!-- /Add Coupons -->
@endif
