<?php $page = 'add-staff'; ?>
@extends('layout.mainlayout')
@section('custom_css')
  <style>
    .invalid {
      border: 1px solid red !important;
    }

    /* Staff E-sign Field (Minimalis & Tenang) */
    .staff-esign-field {
      position: relative;
      display: inline-block;
      width: 240px;
      height: 135px; /* Rasio 16:9 */
      padding: 0;
      border: 1.5px dashed #cbd5e1;
      border-radius: 10px;
      background: #f8fafc;
      overflow: hidden;
      cursor: pointer;
      flex-shrink: 0;
      transition: border-color 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
    }
    .staff-esign-field:hover,
    .staff-esign-field:focus-visible {
      border-color: #2563eb;
      background: #f0f7ff;
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
      outline: none;
    }
    .staff-esign-field.has-image {
      border-style: solid;
      border-width: 1px;
      border-color: #e2e8f0;
      background: #ffffff;
    }
    .staff-esign-field.has-image:hover {
      border-color: #2563eb;
    }
    /* Garis tanda tangan */
    .staff-esign-field.has-image::before {
      content: "";
      position: absolute;
      left: 18px;
      right: 18px;
      bottom: 24px;
      border-bottom: 1px dashed #cbd5e1;
      pointer-events: none;
    }
    .staff-esign-field.has-image::after {
      content: "\2715";
      position: absolute;
      left: 18px;
      bottom: 27px;
      font-size: 10px;
      color: #94a3b8;
      pointer-events: none;
    }
    .staff-esign-field-empty {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      width: 100%;
      height: 100%;
      padding: 10px;
      text-align: center;
      pointer-events: none;
    }
    .staff-esign-field-empty .esign-ico-simple {
      font-size: 22px;
      color: #3b82f6;
      margin-bottom: 6px;
      transition: transform 0.2s ease;
    }
    .staff-esign-field:hover .esign-ico-simple {
      transform: translateY(-2px);
    }
    .staff-esign-field-empty .esign-title {
      font-size: 12.5px;
      font-weight: 600;
      color: #1e293b;
    }
    .staff-esign-field-empty .esign-sub {
      font-size: 11px;
      color: #94a3b8;
      margin-top: 2px;
    }
    .staff-esign-field-img {
      position: relative;
      z-index: 1;
      width: 100%;
      height: 100%;
      object-fit: contain;
      display: block;
      padding: 8px 12px;
      background: transparent;
    }
    .staff-esign-field-hover {
      position: absolute;
      z-index: 2;
      inset: 0;
      display: none;
      align-items: center;
      justify-content: center;
      gap: 6px;
      background: rgba(15, 23, 42, 0.60);
      backdrop-filter: blur(2px);
      -webkit-backdrop-filter: blur(2px);
      color: #ffffff;
      font-size: 12px;
      font-weight: 600;
    }
    .staff-esign-field.has-image:hover .staff-esign-field-hover {
      display: flex;
    }
    .staff-esign-side-desc {
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    .staff-esign-chip {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 2px 8px;
      border-radius: 999px;
      font-size: 11px;
      font-weight: 600;
      line-height: 1.2;
    }
    .staff-esign-chip::before {
      content: "";
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: currentColor;
    }
    .staff-esign-chip.st-off {
      display: inline-flex;
      color: #64748b;
      background: #f1f5f9;
      border: 1px solid #e2e8f0;
    }
    .staff-esign-chip.st-on {
      display: none;
      color: #15803d;
      background: #dcfce7;
      border: 1px solid #bbf7d0;
    }
    .staff-esign-field.has-image ~ .staff-esign-side-desc .st-off {
      display: none !important;
    }
    .staff-esign-field.has-image ~ .staff-esign-side-desc .st-on {
      display: inline-flex !important;
    }

    /* Modal E-sign Segmented Control */
    .staff-esign-segmented {
      display: flex;
      background: #f1f5f9;
      padding: 5px;
      border-radius: 12px;
      border: 1px solid #e2e8f0;
      gap: 6px;
      margin-bottom: 1.25rem;
    }
    .staff-esign-segmented .seg-btn {
      flex: 1;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 10px 18px;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 600;
      color: #64748b;
      border: none;
      background: transparent;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    .staff-esign-segmented .seg-btn:hover {
      color: #1e293b;
      background: rgba(255, 255, 255, 0.5);
    }
    .staff-esign-segmented .seg-btn.active {
      background: #ffffff;
      color: #2563eb;
      font-weight: 700;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06), 0 1px 2px rgba(0, 0, 0, 0.04);
    }

    /* Modal E-sign Dropzone */
    .staff-esign-dropzone {
      border: 2px dashed #cbd5e1;
      border-radius: 14px;
      background: #f8fafc;
      padding: 2.5rem 1.5rem;
      text-align: center;
      cursor: pointer;
      transition: all 0.2s ease-in-out;
      outline: none;
    }
    .staff-esign-dropzone:hover,
    .staff-esign-dropzone:focus,
    .staff-esign-dropzone.is-dragover {
      border-color: #2563eb;
      background: #eff6ff;
      box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
    }
    .staff-esign-dropzone.is-dragover {
      transform: scale(1.005);
    }
    .staff-esign-dropzone .dropzone-icon-wrap {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      background: #ffffff;
      border: 1px solid #bfdbfe;
      color: #2563eb;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 26px;
      box-shadow: 0 2px 8px rgba(37, 99, 235, 0.1);
      margin: 0 auto;
      transition: transform 0.2s ease, background 0.2s ease, color 0.2s ease;
    }
    .staff-esign-dropzone:hover .dropzone-icon-wrap,
    .staff-esign-dropzone.is-dragover .dropzone-icon-wrap {
      transform: translateY(-2px);
      background: #2563eb;
      color: #ffffff;
    }

    /* Dropzone Preview Card */
    .staff-esign-preview-card {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 14px;
      padding: 16px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    }
    .staff-esign-preview-frame {
      width: 100%;
      max-width: 520px;
      aspect-ratio: 16/9;
      margin: 0 auto;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      background: #ffffff;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.03);
    }
    .staff-esign-preview-frame img {
      width: 100%;
      height: 100%;
      object-fit: contain;
    }

    /* Canvas / dropzone penuh lebar modal — jangan “gantung” di tengah */
    #modalStaffEsign .pg-esign,
    #modalStaffEsign .pg-esign.pg-esign--lg {
      max-width: 100% !important;
      width: 100% !important;
      margin: 0 !important;
    }
    /* 16:9 + cap tinggi — nyaman tanda tangan, modal tanpa scroll */
    #modalStaffEsign .pg-esign-frame {
      aspect-ratio: 16 / 9 !important;
      max-height: min(380px, 48vh) !important;
    }
    #modalStaffEsign .modal-body {
      padding: 1rem 1.25rem !important;
    }
    #modalStaffEsign .staff-esign-segmented {
      margin-bottom: 0.75rem;
    }
    #modalStaffEsign .staff-esign-dropzone {
      padding: 1.25rem 1rem;
    }
    #modalStaffEsign .staff-esign-preview-frame {
      aspect-ratio: 16 / 9;
      max-height: min(380px, 48vh);
    }
    #modalStaffEsign #staff_esign_modal_pad,
    #modalStaffEsign #staff_esign_panel_draw,
    #modalStaffEsign #staff_esign_panel_upload,
    #modalStaffEsign .staff-esign-dropzone,
    #modalStaffEsign .staff-esign-preview-card {
      width: 100%;
      max-width: 100%;
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
                        <label>Tanda Tangan Dokumen Produksi</label>
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                          <button type="button" id="btn_staff_esign_open" class="staff-esign-field" title="Klik untuk atur tanda tangan">
                            <span id="staff_esign_placeholder" class="staff-esign-field-empty">
                              <i class="fe fe-edit-3 esign-ico-simple"></i>
                              <span class="esign-title">Atur Tanda Tangan</span>
                              <span class="esign-sub">Klik untuk gambar / upload</span>
                            </span>
                            <img id="staff_esign_preview" alt="Tanda tangan" class="staff-esign-field-img" style="display:none;">
                            <span class="staff-esign-field-hover">
                              <i class="fe fe-edit-3"></i><span id="btn_staff_esign_label">Ubah Tanda Tangan</span>
                            </span>
                          </button>
                          <div class="staff-esign-side-desc">
                            <div class="d-flex align-items-center gap-2 mb-1">
                              <span class="staff-esign-chip st-off">Belum Diatur</span>
                              <span class="staff-esign-chip st-on">Aktif</span>
                              <a href="javascript:void(0)" id="btn_staff_esign_remove" class="text-danger small ms-1" style="display:none; text-decoration: none;">
                                <i class="fe fe-trash-2 me-1"></i>Hapus
                              </a>
                            </div>
                            <small class="text-muted d-block" style="max-width: 380px; line-height: 1.45;">
                              Tanda tangan digital untuk persetujuan dokumen produksi (PP, SPK, dan Bukti Pengeluaran).
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

{{-- Modal e-sign — di luar form --}}
<div class="modal custom-modal fade pg-modal--form" id="modalStaffEsign" tabindex="-1"
    aria-labelledby="modalStaffEsignLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-focus="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="pg-modal-icon"><i class="fe fe-edit-3"></i></div>
                    <div>
                        <h5 class="mb-0 fw-bold text-white modal-title" id="modalStaffEsignLabel" style="font-size:16px;">
                            Tanda Tangan Digital</h5>
                        <small class="d-block text-white-50 modal-subtitle">Gambar tangan langsung atau upload file gambar tanda tangan</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-white">
                <!-- Segmented Mode Switcher -->
                <div class="staff-esign-segmented" role="tablist">
                    <button type="button" class="seg-btn active" data-mode="draw">
                        <i class="fe fe-edit-3"></i> Gambar Tangan (Canvas)
                    </button>
                    <button type="button" class="seg-btn" data-mode="upload">
                        <i class="fe fe-upload-cloud"></i> Upload File (Dropzone)
                    </button>
                </div>

                <!-- Panel 1: Gambar Tangan -->
                <div id="staff_esign_panel_draw">
                    <div id="staff_esign_modal_pad" class="w-100"></div>
                </div>

                <!-- Panel 2: Upload File / Dropzone -->
                <div id="staff_esign_panel_upload" style="display:none;">
                    {{-- Input di luar dropzone — klik dalam dropzone + trigger("click") sering gagal di browser --}}
                    <input type="file" class="visually-hidden" id="staff_esign_upload_file"
                        accept="image/png,image/jpeg,image/webp" tabindex="-1" aria-hidden="true">
                    <div id="staff_esign_dropzone" class="staff-esign-dropzone" tabindex="0" role="button" aria-label="Upload gambar tanda tangan">
                        <div class="dropzone-content">
                            <div class="dropzone-icon-wrap mb-3">
                                <i class="fe fe-upload-cloud"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1 fs-14">
                                Tarik & letakkan file gambar di sini, atau
                                <label for="staff_esign_upload_file" class="text-primary text-decoration-underline mb-0" style="cursor:pointer;">Pilih File</label>
                            </h6>
                            <p class="text-muted small mb-3">
                                Mendukung format PNG, JPG, JPEG, atau WebP (Maksimal 2 MB)
                            </p>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1" style="font-size:11px;">
                                <i class="fe fe-maximize-2 me-1"></i> Otomatis dinormalisasi ke canvas putih
                            </span>
                        </div>
                    </div>

                    <!-- Dropzone Preview Card -->
                    <div id="staff_esign_upload_preview_card" class="staff-esign-preview-card" style="display:none;">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2 pb-2 border-bottom">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size:11px;">
                                    <i class="fe fe-check-circle me-1"></i>Gambar Terpilih
                                </span>
                                <span id="staff_esign_file_name" class="fw-semibold text-dark small text-truncate" style="max-width:260px;"></span>
                                <span id="staff_esign_file_size" class="text-muted small"></span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1" id="btn_staff_esign_change_file" style="height:32px;font-size:12px;border-radius:6px;">
                                    <i class="fe fe-refresh-cw"></i> Ganti File
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1" id="btn_staff_esign_clear_file" style="height:32px;font-size:12px;border-radius:6px;">
                                    <i class="fe fe-trash-2"></i> Hapus
                                </button>
                            </div>
                        </div>
                        <div class="staff-esign-preview-frame">
                            <img id="staff_esign_upload_preview" alt="Preview Tanda Tangan" class="staff-esign-preview-img">
                        </div>
                        <div class="text-center mt-2">
                            <small class="text-muted" style="font-size:11px;">
                                <i class="fe fe-info me-1 text-primary"></i>Gambar tanda tangan dipusatkan pada canvas berlatar belakang putih bersih.
                            </small>
                        </div>
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
