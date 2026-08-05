    <div class="modal modal-lg custom-modal fade" id="add_cash_sales" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Tambah Aktivitas Sales</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-12 col-lg-6">
                                    <div class="input-block mb-3">
                                        <label>Jenis Aktivitas</label>
                                        <select class="form-select" id="jenis_input_sales">
                                            <option value="saldo" selected>Manajemen Saldo Kas</option>
                                            <option value="operasional">Aktivitas Operasional</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-lg-6"></div>
                                <div class="row p-0 m-0" id="inputModal">
                                    <div class="col-12 col-lg-6 saldo_kas">
                                        <div class="input-block mb-3">
                                            <label>Aksi Dana<span class="text-danger">*</span></label>
                                            <select class="form-select fill" id="aksi_sales">
                                                <option value=1>Pemasukan</option>
                                                <option value=2>Setor ke Bank</option>
                                                <option value=3>Pengembalian</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-6">
                                        <div class="input-block mb-3">
                                            <label>Tanggal<span class="text-danger">*</span></label>
                                            <input type="date" class="form-control fill" id="date_sales"></input>
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-6">
                                        <div class="input-block mb-3" id="row-cash">
                                            <label>Nama Sales<span class="text-danger">*</span></label>
                                            <select class="form-select fill" id="staff_id_sales"></select>
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-6 foto operasional mb-3">
                                        <label class="form-label d-flex">
                                            Bukti Foto<span class="text-danger">*</span> 
                                            <span id="check_foto_sales" style="display: none" class="ms-2">
                                                <div class="d-flex g-3">
                                                    <i class="fa fa-check-circle text-success mt-1"></i>
                                                    <p class="text-muted ms-1"><span id="jumlahFotoSales">1</span> gambar terunggah</p>
                                                </div>
                                            </span>
                                        </label>
                                        <div class="d-grid d-md-block gap-2">
                                            <button type="button" class="btn btn-outline-primary" id="btn-foto-bukti-sales">Foto Bukti</button>
                                            <button type="button" class="btn btn-outline-primary" id="btn-lihat-bukti-sales" style="display: none">Lihat Bukti</button>
                                        </div>
                                        <input type="hidden" name="" id="bukti_sales">
                                    </div>
                                    <div class="col-12 col-lg-6 saldo_kas banks">
                                        <div class="input-block mb-3" id="row-bank">
                                            <label>Bank Account<span class="text-danger">*</span></label>
                                            <select class="form-select fill" id="bank_account"></select>
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-6 saldo_kas">
                                        <div class="input-block mb-3">
                                            <label>Nominal<span class="text-danger">*</span></label>
                                            <div class="input-group fix-nominal">
                                                <span class="input-group-text">Rp </span>
                                                <input class="form-control fill number-minus nominal_minus saldos" id="oc_nominal_sales" placeholder="Contoh: 10.000"></input>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-6 saldo_kas">
                                        <div class="input-block mb-3">
                                            <label>Keterangan<span class="text-danger">*</span></label>
                                            <input type="text" class="form-control fill saldos" id="oc_notes_sales" placeholder="Contoh: Pengembalian kas harian">
                                        </div>
                                    </div>
                                    <div class="col-12 px-2 mb-3 operasional">
                                        <div class="row input_table g-3 align-items-end px-1">
                                            <div class="col-lg-6 col-12">
                                                <div class="input-block mb-3" id="row-product">
                                                    <label>Nama Pencatatan<span class="text-danger">*</span></label>
                                                    <select class="form-select fill_catatan" id="cc_id_sales"></select>
                                                </div>
                                            </div>
                                            <div class="col-12 col-lg-5 add">
                                                <div class="input-block mb-3">
                                                    <label>Nominal<span class="text-danger">*</span></label>
                                                    <div class="input-group fix-nominal">
                                                        <span class="input-group-text">Rp </span>
                                                        <input class="form-control fill_catatan number-minus nominal_minus" id="csd_nominal" placeholder="Contoh: 10.000"></input>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-12 col-lg-1 add">
                                                <button type="button" class="btn btn-primary w-100 btn-add-sales mb-3">
                                                    +
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 py-3 mb-3 operasional">
                                        <div class="table-responsive">
                                            <table class="table table-center" id="tableDetailSales" style="min-height: 15vh">
                                                <thead>
                                                    <th>No</th>
                                                    <th>Tipe</th>
                                                    <th style="width: 25%">Keterangan</th>
                                                    <th class="text-end">Nominal</th>
                                                    <th class="no-sort text-center">Aksi</th>
                                                </thead>
                                                <tbody></tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td colspan="3" class="text-end fw-bold">Total : </td>
                                                        <td class="total_sales text-end fw-bold">Rp 0</td>
                                                        <td></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save-sales">Tambah Aktivitas</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
