    <div class="modal modal-lg custom-modal fade" id="modalTerima" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Penerbitan Tanda Terima</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="container-fluid">
                            <label>Detail Barang</label>
                            <small class="text-muted">Berikut adalah detail barang yang dipesan berdasarkan surat jalan</small>
                            
                            <table class="table table-center mt-2" id="tablePurchaseDelivery">
                                        <thead>
                                            <th>Supplies</th>
                                            <th>SKU</th>
                                            <th>Qty</th>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                            <label class="my-2">Detail Faktur</label>
                            <table class="table table-center table-hover" id="tableInvoice">
                                <thead>
                                    <th style="width:15%">Tgl. Pesanan</th>
                                    <th style="width:15%">Tgl. Jatuh Tempo</th>
                                    <th>No. Faktur</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th class="no-sort text-center">Aksi</th>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Konfirmasi Penerimaan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
