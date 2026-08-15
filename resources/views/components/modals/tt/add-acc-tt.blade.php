    <div class="modal modal-lg custom-modal fade" id="add_acc_tt" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Konfirmasi Terima</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <p class="text-center text-muted small px-3">
                            Konfirmasi Pembayaran Semua Invoice Harap unggah Bukti Transfer Bank atau Slip Pembayaran yang valid sebagai syarat konfirmasi pelunasan semua invoice terkait.
                        </p>

                        <div class="upload-section mt-4 px-3">
                            <div class="d-flex flex-column flex-lg-row align-items-center justify-content-center">
                                
                                <div class="profile-img mb-3 mb-lg-0">
                                    <img id="preview_image" class="img-thumbnail" 
                                        style="width: 200px; height: 200px; object-fit: cover; border-radius: 12px;"
                                        src="{{ asset('no_img.png') }}"
                                        alt="bukti-transaksi">
                                </div>

                                <div class="text-center text-lg-start ms-lg-4">
                                    <h5 class="mb-1">Unggah Foto Bukti Transaksi</h5>
                                    <p class="text-muted small mb-3" id="file_name">xx.jpg</p>
                                    <div class="img-upload">
                                        <label class="btn btn-primary px-5 shadow-sm">
                                            Unggah <input type="file" class="d-none input-gambar"
                                            accept="image/png, image/jpeg" id="image">
                                        </label>
                                    </div>
                                    <div class="progress mt-3" id="tt_upload_progress_wrap" style="height: 20px; min-width: 240px;">
                                        <div
                                            id="tt_upload_progress"
                                            class="progress-bar progress-bar-striped progress-bar-animated"
                                            role="progressbar"
                                            style="width: 0%;"
                                            aria-valuenow="0"
                                            aria-valuemin="0"
                                            aria-valuemax="100"
                                        >
                                            0%
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="mt-4 px-3">
                            <label class="form-label fw-bold">Keterangan<span class="text-danger">*</span></label>
                            <textarea class="form-control" rows="3" id="keterangan" placeholder="Masukkan keterangan tambahan..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Batal</button>
                        <button type="button"
                            class="btn btn-primary paid-continue-btn btn-save">Konfirmasi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
