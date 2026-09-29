<?php $page = 'add-staff'; ?>
@extends('layout.mainlayout')
@section('custom_css')
  <style>
    .invalid {
      border: 1px solid red !important;
    }

    .staff-esign-preview-wrap {
      width: 160px;
      height: 90px;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      background: #fff;
      overflow: hidden;
      flex-shrink: 0;
    }
    .staff-esign-preview {
      width: 100%;
      height: 100%;
      object-fit: contain;
      display: block;
    }
    #modalStaffEsign .pg-esign {
      max-width: min(560px, 92vw);
      margin: 0 auto;
    }

    .is-invalids {
      border: 1px solid red !important;
      border-radius: 4px;
    }

    /* Harus lebih spesifik dari style global Select2 multiple di head.blade.php */
    #row-warehouse .select2-container--default .select2-selection--multiple.is-invalids,
    #row-position .select2-container--default .select2-selection--single.is-invalids,
    .select2-container--default .select2-selection.is-invalids {
      border: 1px solid #dc3545 !important;
      box-shadow: 0 0 0 0.15rem rgba(220, 53, 69, 0.15) !important;
    }

    /* Samakan tinggi select2 multiple dengan input lainnya (43px sesuai tema) */
    /* Samakan styling select2 multiple dengan select2 single bawaan template (pseudo-bootstrap) */
    .select2-container--default .select2-selection--multiple {
      min-height: 43px !important;
      padding-left: 10px;
      border: 1px solid rgba(145, 158, 171, 0.32) !important;
      border-radius: 5px !important;
    }

    .select2-container--default.select2-container--focus .select2-selection--multiple {
      border-color: #ff9b44 !important;
      /* warna focus template */
    }

    /* Vertikal center placeholder teks */
    .select2-container--default .select2-selection--multiple .select2-search__field {
      line-height: 41px;
      margin-top: 0px !important;
      color: #3F4254;
    }

    /* Vertikal center tag (pilihan) */
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
      margin-top: 6px;
    }

    /* PREMIUM WAREHOUSE CHECKBOX SYSTEM */
    .warehouse-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
      gap: 14px;
      margin-top: 10px;
    }

    .warehouse-card {
      position: relative;
    }

    .warehouse-checkbox {
      position: absolute;
      opacity: 0;
      width: 0;
      height: 0;
    }

    .warehouse-pill {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 14px 16px;
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      cursor: pointer;
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
      user-select: none;
      height: 100%;
    }

    .warehouse-pill:hover {
      border-color: #cbd5e1;
      background: #f8fafc;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    .check-icon {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 24px;
      height: 24px;
      border-radius: 6px;
      background: #f1f5f9;
      color: transparent;
      transition: all 0.2s ease;
      flex-shrink: 0;
    }

    .check-icon i {
      font-size: 13px;
    }

    .wh-name {
      font-size: 14px;
      font-weight: 500;
      color: #475569;
      transition: color 0.2s ease;
      line-height: 1.3;
    }

    /* Active State */
    .warehouse-checkbox:checked+.warehouse-pill {
      background: #eff6ff;
      border-color: #3b82f6;
      box-shadow: 0 4px 16px rgba(59, 130, 246, 0.15);
    }

    .warehouse-checkbox:checked+.warehouse-pill .check-icon {
      background: #3b82f6;
      color: #ffffff;
    }

    .warehouse-checkbox:checked+.warehouse-pill .wh-name {
      color: #1e40af;
      font-weight: 700;
    }
  </style>
@endsection
@section('content')
  <!-- Page Wrapper -->
  <div class="page-wrapper">
    <div class="content container-fluid">
      <div class="card mb-0">
        <div class="card-body">
          <!-- Page Header -->
          <div class="page-header">
            <div class="content-page-header">
              <div class="d-flex justify-content-between w-100">
                <h5>Tambah Staf</h5>
                <button class="btn btn-back">Kembali</button>
              </div>
            </div>
          </div>
          <!-- /Page Header -->
          <div class="row">
            <div class="col-md-12">
              <form action="#">
                <div class="form-group-item">
                  <h5 class="form-title mb-3">Detail Dasar</h5>
                  {{-- <div class="profile-picture">
                                        <div class="upload-profile">
                                            <div class="profile-img">
                                                <img id="preview_image" class="avatar"
                                                    src="{{ URL::asset('/assets/img/profiles/avatar-14.jpg') }}"
                                                    alt="foto-profil">
                                            </div>
                                            <div class="add-profile">
                                                <h5>Unggah Foto Baru</h5>
                                                <span id="file_name">Profile-pic.jpg</span>
                                            </div>
                                        </div>
                                        <div class="img-upload">
                                            <label class="btn btn-upload">
                                                Unggah <input type="file" class="form-control fill input-gambar"
                                                accept="image/png, image/jpeg" id="staff_image">
                                            </label>
                                        </div>
                                    </div> --}}
                  <div class="row">
                    <div class="col-lg-4 col-md-6 col-sm-12">
                      <div class="input-block mb-3">
                        <label>Nama Depan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control fill" id="staff_first_name"
                          placeholder="Masukkan Nama Depan">
                      </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-12">
                      <div class="input-block mb-3">
                        <label>Nama Belakang <span class="text-danger">*</span></label>
                        <input type="text" class="form-control fill" id="staff_last_name"
                          placeholder="Masukkan Nama Belakang">
                      </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-12">
                      <div class="input-block mb-3">
                        <label>Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control fill" id="staff_email"
                          placeholder="Masukkan Alamat Email">
                      </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-12">
                      <div class="input-block mb-3">
                        <label>Nomor Telepon <span class="text-danger">*</span></label>
                        <input type="text" id="staff_phone" class="form-control fill include-nol" placeholder="08xxx"
                          name="name">
                      </div>
                    </div>
                    {{-- <div class="col-lg-4 col-md-6 col-sm-12">
                                            <div class="input-block mb-3">
                                                <label>Tanggal Lahir <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control fill" id="staff_birthdate"
                                                    placeholder="Masukkan Tanggal Lahir">
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 col-sm-12">
                                            <div class="input-block mb-3">
                                                <label>Jenis Kelamin <span class="text-danger">*</span></label>
                                                <select class="form-select fill" id="staff_gender">
                                                    <option value="1">Laki-laki</option>
                                                    <option value="2">Perempuan</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 col-sm-12">
                                            <div class="input-block mb-3">
                                                <label>Tanggal Bergabung <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control fill" id="staff_join_date"
                                                    placeholder="Masukkan Tanggal Bergabung">
                                            </div>
                                        </div> --}}
                    {{-- <div class="col-lg-4 col-md-6 col-sm-12">
                                            <div class="input-block mb-3">
                                                <label>Shift <span class="text-danger">*</span></label>
                                                <select class="form-select fill" id="staff_shift">
                                                    <option value="Regular">Reguler</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 col-sm-12">
                                            <div class="input-block mb-3">
                                                <label>Departemen <span class="text-danger">*</span></label>
                                                <select class="form-select fill" id="staff_departement">
                                                    <option value="Customer Service">Layanan Armada</option>
                                                </select>
                                            </div>
                                        </div> --}}
                    <div class="col-lg-4 col-md-6 col-sm-12">
                      <div class="input-block mb-3" id="row-position">
                        <label>Posisi <span class="text-danger">*</span></label>
                        <select class="form-select fill select2" id="staff_position">
                          <option value="">Pilih Posisi</option>
                          @foreach ($roles ?? [] as $role)
                            <option value="{{ $role->role_id }}" @selected(isset($data['role_id']) && (int) $data['role_id'] === (int) $role->role_id)>
                              {{ $role->role_name }}
                            </option>
                          @endforeach
                        </select>
                      </div>
                    </div>
                    <div class="col-lg-6 col-md-12 col-sm-12">
                      <div class="input-block mb-3">
                        <label>Alamat <span class="text-danger">*</span></label>
                        <input type="text" class="form-control fill" id="staff_address" placeholder="Masukkan Alamat">
                      </div>
                    </div>
                  </div>
                </div>
                <div class="form-group-item mt-4">
                  <div class="row">
                    <div class="d-flex align-items-center mb-3">
                      <h5 class="form-title mb-0">Data Keamanan</h5>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-12">
                      <div class="input-block mb-3">
                        <label>Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control fill" id="staff_username"
                          placeholder="Masukkan Username">
                      </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-12">
                      <div class="input-block mb-3">
                        <label>Kata Sandi @if ($mode != 'update')
                            <span class="text-danger">*</span>
                          @endif
                        </label>
                        <input type="password" class="form-control fill" id="staff_password"
                          placeholder="Masukkan Kata Sandi">
                      </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-12">
                      <div class="input-block mb-3">
                        <label>Konfirmasi Kata Sandi @if ($mode != 'update')
                            <span class="text-danger">*</span>
                          @endif
                        </label>
                        <input type="password" class="form-control fill" id="staff_confirm"
                          placeholder="Masukkan Ulang Kata Sandi">
                      </div>
                    </div>
                    <div class="col-12">
                      <div class="input-block mb-3">
                        <label>Tanda tangan dokumen produksi</label>
                        <div class="d-flex flex-wrap align-items-start gap-3">
                          <div id="staff_esign_preview_wrap" class="staff-esign-preview-wrap" style="display:none;">
                            <img id="staff_esign_preview" alt="Tanda tangan" class="staff-esign-preview">
                          </div>
                          <div>
                            <button type="button" class="btn btn-outline-primary" id="btn_staff_esign_open">
                              <i class="fe fe-edit-3 me-1"></i><span id="btn_staff_esign_label">Tambah tanda tangan</span>
                            </button>
                            <small class="text-muted d-block mt-2" style="max-width:320px;">
                              E-sign dipakai saat menyetujui dokumen produksi. Gambar baru mengganti yang lama.
                            </small>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                {{-- <div class="form-group-item">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="billing-btn mb-2">
                                                <h5 class="form-title">Informasi Lainnya</h5>
                                            </div>
                                            <div class="row">
                                                <div class="col-lg-4 col-md-6 col-sm-12">
                                                    <div class="input-block mb-3">
                                                        <label>Nomor Darurat <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control fill number-only" id="staff_emergency1"
                                                            placeholder="Masukkan Nomor Darurat">
                                                    </div>
                                                </div>

                                                <div class="col-lg-4 col-md-6 col-sm-12">
                                                    <div class="input-block mb-3">
                                                        <label>Provinsi <span class="text-danger">*</span></label>
                                                        <select class="form-select fill" id="state_id"></select>
                                                    </div>
                                                </div>
                                                <div class="col-lg-4 col-md-6 col-sm-12">
                                                    <div class="input-block mb-3">
                                                        <label>Kota/Kabupaten <span class="text-danger">*</span></label>
                                                        <select class="form-select fill" id="city_id"></select>
                                                    </div>
                                                </div>
                                                <div class="col-lg-4 col-md-6 col-sm-12">
                                                    <div class="input-block mb-3">
                                                        <label>Kode Pos <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control fill number-only" id="staff_zipcode"
                                                            placeholder="Masukkan Kode Pos">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div> --}}
                <div class="form-group-item mt-2" id="row-warehouse">
                  <div class="row">
                    <div class="col-md-12">
                      <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="form-title mb-0">Akses Gudang <span class="text-danger">*</span></h5>
                        <a href="javascript:void(0)" id="btn_select_all_warehouses" data-state="all"
                          class="text-primary fw-bold" style="font-size: 14px;"><i class="fa fa-check-square me-1"></i>
                          Pilih Semua</a>
                      </div>
                      <div class="warehouse-grid warehouse-list-container">
                        @if (isset($warehouses))
                          @foreach ($warehouses as $wh)
                            <div class="warehouse-card">
                              <input class="warehouse-checkbox chk-warehouse" type="checkbox" value="{{ $wh->id }}"
                                id="wh_{{ $wh->id }}">
                              <label class="warehouse-pill" for="wh_{{ $wh->id }}">
                                <div class="check-icon"><i class="fa fa-check"></i></div>
                                <span class="wh-name">{{ $wh->warehouse_name ?? $wh->name }}</span>
                              </label>
                            </div>
                          @endforeach
                        @endif
                      </div>
                    </div>
                  </div>
                </div>
                <div class="add-customer-btns text-end">
                  <a href="{{ url('staff') }}" class="btn btn-outline-secondary btn-cancel">Batal</a>
                  <a class="btn btn-primary btn-save">Tambah Staff</a>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- /Page Wrapper -->

{{-- Modal e-sign 16:9 (luas) — di luar form --}}
<div class="modal custom-modal fade pg-modal--form" id="modalStaffEsign" tabindex="-1"
    aria-labelledby="modalStaffEsignLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-focus="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="pg-modal-icon"><i class="fe fe-edit-3"></i></div>
                    <div>
                        <h5 class="mb-0 fw-bold text-white modal-title" id="modalStaffEsignLabel" style="font-size:16px;">
                            Tanda Tangan</h5>
                        <small class="d-block text-white-50 modal-subtitle">Gambar tangan atau upload gambar — simpan untuk mengganti e-sign</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-white">
                <div class="btn-group w-100 mb-3" role="group" aria-label="Mode tanda tangan">
                    <input type="radio" class="btn-check" name="staff_esign_mode" id="staff_esign_mode_draw" value="draw" autocomplete="off" checked>
                    <label class="btn btn-outline-primary" for="staff_esign_mode_draw">
                        <i class="fe fe-edit-3 me-1"></i> Gambar tangan
                    </label>
                    <input type="radio" class="btn-check" name="staff_esign_mode" id="staff_esign_mode_upload" value="upload" autocomplete="off">
                    <label class="btn btn-outline-primary" for="staff_esign_mode_upload">
                        <i class="fe fe-image me-1"></i> Upload gambar
                    </label>
                </div>
                <div id="staff_esign_panel_draw">
                    <div id="staff_esign_modal_pad" class="mx-auto"></div>
                </div>
                <div id="staff_esign_panel_upload" style="display:none;">
                    <label class="form-label fw-semibold">Pilih file gambar (PNG/JPG)</label>
                    <input type="file" class="form-control" id="staff_esign_upload_file" accept="image/png,image/jpeg,image/webp">
                    <div class="mt-3 text-center">
                        <div id="staff_esign_upload_preview_wrap" class="staff-esign-preview-wrap mx-auto" style="display:none;max-width:360px;">
                            <img id="staff_esign_upload_preview" alt="Preview upload" class="staff-esign-preview" style="max-width:100%;height:auto;">
                        </div>
                        <small class="text-muted d-block mt-2">Gambar dipotong/diskalakan ke rasio 16:9 saat disimpan.</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-end gap-2 pg-modal-footer">
                <button type="button" class="btn pg-btn-cancel" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn pg-btn-save" id="btn_staff_esign_apply">
                    <i class="fe fe-check me-1"></i> Simpan Tanda Tangan
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('custom_js')
  <script>
    var public = "{{ asset('') }}";
    var mode = "{{ $mode }}";
    var data = @json($data);
  </script>
  <script src="{{ asset('Custom_js/Backoffice/User/insertStaff.js') }}"></script>
@endsection
