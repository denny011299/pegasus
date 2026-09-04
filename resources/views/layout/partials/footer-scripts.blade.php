<!-- jQuery -->
<script src="{{ URL::asset('/assets/js/jquery-3.7.1.min.js') }}"></script>

<!-- Bootstrap Core JS -->
<script src="{{ URL::asset('/assets/js/bootstrap.bundle.min.js') }}"></script>
@if (Route::is(['variant']))
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tagsinput/0.8.0/bootstrap-tagsinput.min.js"></script>
@endif

<!-- Feather Icon JS -->
<script src="{{ URL::asset('/assets/js/feather.min.js') }}"></script>

<!-- Slimscroll JS -->
<script src="{{ URL::asset('/assets/plugins/slimscroll/jquery.slimscroll.min.js') }}"></script>

@if (!Route::is(['companies']))
  <!-- Datatable JS -->
  <script src="{{ URL::asset('/assets/plugins/datatables/datatables.min.js') }}"></script>
  <script>
    if ($.fn.dataTable) {
      $.extend(true, $.fn.dataTable.defaults, {
        deferRender: true,
        processing: true,
        autoWidth: false,
        pageLength: 10,
        lengthMenu: [
          [10, 25, 50, 100],
          [10, 25, 50, 100]
        ],
        language: {
          search: " ",
          searchPlaceholder: "Cari data...",
          lengthMenu: "Tampilkan _MENU_ data",
          info: "_START_ - _END_ dari _TOTAL_ data",
          infoEmpty: "Menampilkan 0 data",
          infoFiltered: "(disaring dari _MAX_ data)",
          emptyTable: '<div class="text-center py-4 text-muted"><i class="fe fe-inbox fs-2 mb-2 d-block opacity-50"></i>Tidak ada data tersedia</div>',
          zeroRecords: '<div class="text-center py-4 text-muted"><i class="fe fe-search fs-2 mb-2 d-block opacity-50"></i>Data tidak ditemukan</div>',
          paginate: {
            next: '<i class="fe fe-chevron-right"></i>',
            previous: '<i class="fe fe-chevron-left"></i>'
          },
          processing: '<div class="dt-skeleton-overlay d-flex flex-column align-items-center justify-content-center py-4 px-3" style="background: rgba(255, 255, 255, 0.92); backdrop-filter: blur(2px); border-radius: 12px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);">' +
            '<div class="spinner-border text-primary mb-2" style="width: 2.2rem; height: 2.2rem; border-width: 0.2em;" role="status">' +
            '<span class="visually-hidden">Memuat...</span>' +
            '</div>' +
            '<div class="fw-semibold text-secondary" style="font-size: 13px; letter-spacing: 0.3px;">Memuat data...</div>' +
            '</div>'
        }
      });
    }
  </script>
@endif

<!-- select Js -->
<script src="{{ URL::asset('/assets/plugins/select2/js/select2.full.js') }}"></script>

@if (Route::is([
        'chart-apex',
        'dashboard',
        'dashboard-admin',
        'index-five',
        'index-four',
        'index-three',
        'index-two',
        'index',
        '/',
    ]) || request()->routeIs(['index', 'dashboard-admin']))
  <!-- apexChart JS -->
  <script src="{{ URL::asset('/assets/plugins/apexchart/apexcharts.min.js') }}"></script>
  <script src="{{ URL::asset('/assets/plugins/apexchart/chart-data.js') }}"></script>
@endif

@if (Route::is(['chart-js']))
  <!-- Chart JS -->
  <script src="{{ URL::asset('/assets/plugins/chartjs/chart.min.js') }}"></script>
  <script src="{{ URL::asset('/assets/plugins/chartjs/chart-data.js') }}"></script>
@endif

@if (Route::is(['chart-morris']))
  <!-- morrisChart JS -->
  <script src="{{ URL::asset('/assets/plugins/morris/raphael-min.js') }}"></script>
  <script src="{{ URL::asset('/assets/plugins/morris/morris.min.js') }}"></script>
  <script src="{{ URL::asset('/assets/plugins/morris/chart-data.js') }}"></script>
@endif

@if (Route::is(['chart-flot']))
  <!-- flotChart JS -->
  <script src="{{ URL::asset('/assets/plugins/flot/jquery.flot.js') }}"></script>
  <script src="{{ URL::asset('/assets/plugins/flot/jquery.flot.fillbetween.js') }}"></script>
  <script src="{{ URL::asset('/assets/plugins/flot/jquery.flot.pie.js') }}"></script>
  <script src="{{ URL::asset('/assets/plugins/flot/chart-data.js') }}"></script>
@endif

@if (Route::is(['chart-peity']))
  <!-- peityChart JS -->
  <script src="{{ URL::asset('/assets/plugins/peity/jquery.peity.min.js') }}"></script>
  <script src="{{ URL::asset('/assets/plugins/peity/chart-data.js') }}"></script>
@endif

@if (Route::is(['chart-c3']))
  <!-- c3Chart JS -->
  <script src="{{ URL::asset('/assets/plugins/c3-chart/d3.v5.min.js') }}"></script>
  <script src="{{ URL::asset('/assets/plugins/c3-chart/c3.min.js') }}"></script>
  <script src="{{ URL::asset('/assets/plugins/c3-chart/chart-data.js') }}"></script>
@endif

@if (Route::is(['horizontal-timeline']))
  <!-- Timeline JS -->
  <script src="{{ URL::asset('/assets/plugins/timeline/horizontal-timeline.js') }}"></script>
@endif

@if (Route::is(['stickynote']))
  <!-- Stickynote JS -->
  <script src="{{ URL::asset('/assets/js/jquery-ui.min.js') }}"></script>
  <script src="{{ URL::asset('/assets/plugins/stickynote/sticky.js') }}"></script>
@endif

@if (Route::is(['notification']))
  <!-- Alertify JS -->
  <script src="{{ URL::asset('/assets/plugins/alertify/alertify.min.js') }}"></script>
  <script src="{{ URL::asset('/assets/plugins/alertify/custom-alertify.min.js') }}"></script>
@endif

@if (Route::is(['scrollbar']))
  <!-- Plyr JS -->
  <script src="{{ URL::asset('/assets/plugins/scrollbar/scrollbar.min.js') }}"></script>
  <script src="{{ URL::asset('/assets/plugins/scrollbar/custom-scroll.js') }}"></script>
@endif

@if (Route::is(['counter']))
  <!-- Counter JS -->
  <script src="{{ URL::asset('/assets/plugins/countup/jquery.counterup.min.js') }}"></script>
  <script src="{{ URL::asset('/assets/plugins/countup/jquery.waypoints.min.js') }}"></script>
  <script src="{{ URL::asset('/assets/plugins/countup/jquery.missofis-countdown.js') }}"></script>
@endif

@if (Route::is(['rating']))
  <!-- Raty JS -->
  <script src="{{ URL::asset('/assets/plugins/raty/jquery.raty.js') }}"></script>
  <script src="{{ URL::asset('/assets/plugins/raty/custom.raty.js') }}"></script>
@endif

@if (Route::is(['clipboard']))
  <!-- Clipboard JS -->
  <script src="{{ URL::asset('/assets/plugins/clipboard/clipboard.min.js') }}"></script>
@endif

@if (Route::is(['sweetalerts']))
  <!-- Sweetalert 2 -->
  <script src="{{ URL::asset('/assets/plugins/sweetalert/sweetalert2.all.min.js') }}"></script>
  <script src="{{ URL::asset('/assets/plugins/sweetalert/sweetalerts.min.js') }}"></script>
@endif

@if (Route::is(['rangeslider']))
  <!-- Rangeslider JS -->
  <script src="{{ URL::asset('/assets/plugins/ion-rangeslider/js/ion.rangeSlider.min.js') }}"></script>
  <script src="{{ URL::asset('/assets/plugins/ion-rangeslider/js/custom-rangeslider.js') }}"></script>
@endif

@if (Route::is(['plan-billing']))
  <!-- Owl Carousel JS -->
  <script src="{{ URL::asset('/assets/js/owl.carousel.min.js') }}"></script>
@endif

@if (Route::is(['form-select2']))
  <script src="{{ URL::asset('/assets/plugins/select2/js/custom-select.js') }}"></script>
@endif

<!-- multiselect JS -->
<script src="{{ URL::asset('/assets/js/jquery-ui.min.js') }}"></script>

<!-- PG Popup Table: konstanta + perilaku standar tabel input di dalam modal -->
<script src="{{ URL::asset('/Custom_js/Shared/popup-table.js') }}"></script>

@if (Route::is(['lightbox', 'template-invoice']))
  <!-- lightbox JS -->
  <script src="{{ URL::asset('/assets/plugins/lightbox/glightbox.min.js') }}"></script>
  <script src="{{ URL::asset('/assets/plugins/lightbox/lightbox.js') }}"></script>
@endif

@if (Route::is(['drag-drop']))
  <!-- Dragula JS -->
  <script src="{{ URL::asset('/assets/plugins/dragula/js/dragula.min.js') }}"></script>
  <script src="{{ URL::asset('/assets/plugins/dragula/js/drag-drop.min.js') }}"></script>
@endif

@if (Route::is(['text-editor']))
  <!-- Summernote JS -->
  <script src="{{ URL::asset('/assets/plugins/summernote/summernote-bs4.min.js') }}"></script>
@endif

@if (Route::is([
        'add-products',
        'all-blogs',
        'contact-details',
        'edit-products',
        'edit-units',
        'expenses',
        'pages',
        'inactive-blog',
        'email-template',
        'seo-settings',
        'saas-settings',
    ]))
  <script src="{{ URL::asset('/assets/plugins/summernote/summernote-lite.min.js') }}"></script>
@endif

@if (Route::is(['form-mask']))
  <!-- Mask JS -->
  <script src="{{ URL::asset('/assets/js/jquery.maskedinput.min.js') }}"></script>
  <script src="{{ URL::asset('/assets/js/mask.js') }}"></script>
@endif

@if (Route::is(['form-fileupload']))
  <!-- Fileupload JS -->
  <script src="{{ URL::asset('/assets/plugins/fileupload/fileupload.min.js') }}"></script>
@endif

@if (Route::is(['form-validation']))
  <!-- Form Validation JS -->
  <script src="{{ URL::asset('/assets/js/form-validation.js') }}"></script>
@endif

@if (Route::is(['maps-vector']))
  <!-- Map JS -->
  <script src="{{ URL::asset('/assets/plugins/slimscroll/jquery.slimscroll.min.js') }}"></script>
  <script src="{{ URL::asset('/assets/plugins/jvectormap/jquery-jvectormap-2.0.3.min.js') }}"></script>
  <script src="{{ URL::asset('/assets/plugins/jvectormap/jquery-jvectormap-world-mill.js') }}"></script>
  <script src="{{ URL::asset('/assets/plugins/jvectormap/jquery-jvectormap-ru-mill.js') }}"></script>
  <script src="{{ URL::asset('/assets/plugins/jvectormap/jquery-jvectormap-us-aea.js') }}"></script>
  <script src="{{ URL::asset('/assets/plugins/jvectormap/jquery-jvectormap-uk_countries-mill.js') }}"></script>
  <script src="{{ URL::asset('/assets/plugins/jvectormap/jquery-jvectormap-in-mill.js') }}"></script>
  <script src="{{ URL::asset('/assets/js/jvectormap.js') }}"></script>
@endif

<!-- Datetimepicker JS -->
<script src="{{ URL::asset('/assets/plugins/moment/moment.min.js') }}"></script>
<script src="{{ URL::asset('/assets/js/bootstrap-datetimepicker.min.js') }}"></script>

@if (Route::is(['income-report', 'low-stock-report', 'payment-report', 'tax-purchase', 'tax-sales', 'stockTransfer', 'salesOrder']))
  <script src="{{ URL::asset('/assets/plugins/daterangepicker/daterangepicker.js') }}"></script>
@endif

@if (Route::is(['calendar']))
  <!-- Full Calendar JS -->
  <script src="{{ URL::asset('/assets/js/jquery-ui.min.js') }}"></script>
  <script src="{{ URL::asset('/assets/plugins/fullcalendar/fullcalendar.min.js') }}"></script>
  <script src="{{ URL::asset('/assets/plugins/fullcalendar/jquery.fullcalendar.js') }}"></script>
@endif

@if (Route::is(['add-customer', 'edit-customer', 'testimonials']))
  <!-- Intl Tell Input js -->
  <script src="{{ URL::asset('/assets/plugins/intlTelInput/js/intlTelInput-jquery.min.js') }}"></script>
@endif

@if (Route::is(['companies']))
  <script src="{{ URL::asset('/assets/js/jquery.dataTables.min.js') }}"></script>

  <script src="{{ URL::asset('/assets/js/dataTables.bootstrap5.min.js') }}"></script>

  <!-- Mobile Input -->
  <script src="{{ URL::asset('/assets/plugins/intltelinput/js/intlTelInput.js') }}"></script>
@endif

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Custom JS -->
<script src="{{ URL::asset('/assets/js/script.js') }}"></script>
<script src="
https://cdn.jsdelivr.net/npm/toastr@2.1.4/toastr.min.js
"></script>

<script>
  toastr.options = {
    "closeButton": false,
    "debug": false,
    "newestOnTop": false,
    "progressBar": false,
    "positionClass": "toast-top-right",
    "preventDuplicates": true,
    "onclick": null,
    "showDuration": "300",
    "hideDuration": "1000",
    "timeOut": "5000",
    "extendedTimeOut": "1000",
    "showEasing": "swing",
    "hideEasing": "linear",
    "showMethod": "fadeIn",
    "hideMethod": "fadeOut"
  }
  // Global DataTables defaults initialized above
  // Tooltip
  if ($('[data-bs-toggle="tooltip"]').length > 0) {
    var tooltipTriggerList = [].slice.call(
      document.querySelectorAll('[data-bs-toggle="tooltip"]')
    );
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
    });
  }
  requestAnimationFrame(function() {
    feather.replace();
  });

  function renderCreatedByName(data) {
    try {
      if (data == null || data === '' || data === '-' || data === false) {
        return '<span class="text-muted">-</span>';
      }
      // Guard: kadang DataTables/ajax kirim object
      if (typeof data === 'object') {
        data = data.name || data.text || data.staff_name || '-';
      }
      data = String(data);
      if (data === '-' || data.trim() === '') {
        return '<span class="text-muted">-</span>';
      }
      // Escape minimal supaya template literal aman
      var safe = data
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
      return '<div style="display:flex;align-items:center;gap:10px;">' +
        '<div style="width:32px;height:32px;border-radius:8px;background:#f1f5f9;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;color:#64748b;flex-shrink:0;">' +
        '<i class="fe fe-user"></i>' +
        '</div>' +
        '<span class="fw-semibold text-dark">' + safe + '</span>' +
        '</div>';
    } catch (err) {
      console.error('renderCreatedByName error:', err);
      return '<span class="text-muted">-</span>';
    }
  }

  /**
   * Kolom "Dibuat Oleh" untuk tabel yang barisnya BISA berasal dari Pusat
   * Sinkronisasi (Produk, Satuan, Armada). Dipakai menggantikan
   * renderCreatedByName pada tabel-tabel itu saja — tabel lain tetap
   * memakai renderCreatedByName apa adanya.
   *
   * Penandanya adalah kolom id rujukan PMO pada barisnya
   * (ref_product_id/ref_unit_id/ref_armada_id), BUKAN created_by: created_by
   * kosong juga terjadi pada data lama/data dari Platform API Eksternal,
   * jadi tidak bisa dipakai sebagai bukti. Tiga keadaan:
   *
   * - Ada id PMO, tanpa pembuat -> memang dibuat oleh sinkronisasi.
   * - Ada id PMO, ADA pembuat   -> dibuat manusia lalu diadopsi/disambungkan
   *                               ke PMO (lihat ReferenceMatcher fase adopsi).
   *                               Keduanya ditampilkan, jangan disembunyikan.
   * - Tanpa id PMO              -> perilaku lama, murni data lokal.
   *
   * Intinya untuk operator: baris bertanda PMO akan DITIMPA data PMO pada
   * sinkronisasi berikutnya, jadi menyuntingnya manual di sini percuma.
   */
  var PMO_REF_KEYS = ['ref_product_id', 'ref_unit_id', 'ref_armada_id'];

  function pmoRefIdOf(row) {
    if (!row || typeof row !== 'object') {
      return null;
    }
    for (var i = 0; i < PMO_REF_KEYS.length; i++) {
      var value = row[PMO_REF_KEYS[i]];
      if (value !== null && typeof value !== 'undefined' && value !== '' && value !== 0 && value !== '0') {
        return String(value);
      }
    }
    return null;
  }

  function renderCreatedBySync(data, row) {
    try {
      var refId = pmoRefIdOf(row);
      if (refId === null) {
        return renderCreatedByName(data);
      }

      // Id PMO SENGAJA tidak ditampilkan di sini: nilainya 16 digit dan
      // sebagian melewati Number.MAX_SAFE_INTEGER JavaScript (mis.
      // 9506012026014615 terbaca 9506012026014616), jadi yang tampil bisa
      // meleset satu digit. Untuk mendeteksi "ada/tidak" saja nilainya tetap
      // aman. Kalau id aslinya perlu dilihat, ambil dari database.
      var tip = 'Dikelola Sinkronisasi PMO. '
        + 'Perubahan manual akan ditimpa pada sinkronisasi berikutnya.';

      var creator = data;
      if (typeof creator === 'object' && creator !== null) {
        creator = creator.name || creator.text || creator.staff_name || '';
      }
      var hasCreator = !(creator == null || creator === '' || creator === '-'
        || creator === false || String(creator).trim() === '');

      if (!hasCreator) {
        return '<div style="display:flex;align-items:center;gap:10px;" title="' + tip + '">' +
          '<div style="width:32px;height:32px;border-radius:8px;background:#e0f2fe;border:1px solid #bae6fd;display:flex;align-items:center;justify-content:center;color:#0284c7;flex-shrink:0;">' +
          '<i class="fe fe-refresh-cw"></i>' +
          '</div>' +
          '<span class="fw-semibold text-dark">Sinkronisasi PMO</span>' +
          '</div>';
      }

      return '<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;" title="' + tip + '">' +
        renderCreatedByName(data) +
        '<span class="badge badge-soft-info"><i class="fe fe-refresh-cw me-1"></i>PMO</span>' +
        '</div>';
    } catch (err) {
      console.error('renderCreatedBySync error:', err);
      return renderCreatedByName(data);
    }
  }

  function notifikasi(simbol, title, deskripsi) {
    Swal.fire({
      icon: simbol,
      title: title,
      text: deskripsi,
    });
  }

  /**
   * Popup error bertema PG (rounded-16px, tombol pg-btn-confirm--danger, ikon merah) — dipakai
   * lintas modul supaya konsisten dengan pg-modal--danger, alih-alih notifikasi() polos
   * (SweetAlert2 default) yang sudah tidak sesuai desain modal sejak redesign Kanakku. Versi
   * generik dari showSoErrorModal() di Sales_Order.js — lihat commit e90f255. `.swal2-container`
   * sudah global z-index:2000 (pg-modal-styles.blade.php) jadi popup ini tetap tampil di depan
   * walau dipanggil sementara modal PG lain (pg-modal--form/--confirm/--danger) masih terbuka.
   */
  function showPgErrorModal(header, message) {
    Swal.fire({
      icon: "error",
      iconColor: "#ef4444",
      title: header || "Gagal",
      html:
        '<p class="text-start mb-0" style="font-size:14px;white-space:pre-wrap;">' +
        $("<div>").text(message || "").html() +
        "</p>",
      confirmButtonText: "Tutup",
      customClass: {
        confirmButton: "pg-btn-confirm pg-btn-confirm--danger",
        title: "fw-bold fs-4 text-dark",
        popup: "rounded-4",
      },
      buttonsStyling: false,
    });
  }

  /**
   * Panggil ini di awal setiap ajax error callback (terutama di popup) supaya pesan
   * 403 (ditolak middleware permission) konsisten di seluruh aplikasi dan tidak
   * membocorkan modul/permission apa yang kurang ke user.
   * Return true kalau sudah ditangani (403) — caller tinggal `return` tanpa
   * menampilkan notifikasi generiknya sendiri. Return false untuk error lain,
   * silakan lanjut pakai handling yang sudah ada di masing-masing pemanggil.
   */
  function handlePermissionError(xhr) {
    if (xhr && xhr.status === 403) {
      notifikasi('error', 'Akses Ditolak', 'Anda tidak memiliki akses terhadap aksi ini, mohon hubungi Admin!');
      return true;
    }
    return false;
  }
  //munculin modal delete
    function showModalDelete(text, button_id) {
        if ($('#modalDelete .modal-body').is(':empty')) {
            $('#modalDelete .modal-body').html('<p id="text-delete" style="font-size:10pt"></p>');
        }
        
        $("#text-delete").html(text);
        $("#modalDelete .btn-konfirmasi").attr("id", button_id);
        $('#modalDelete').modal("show");
    }
      
    function showModalKonfirmasi(text, button_id, danger) {
        //button id ini, id button ketika dikofrimasi delete
        //danger = true untuk aksi cancel/tolak/hapus → tema modal & tombol jadi merah
        $("#text-konfirmasi").html(text);
        $("#modalKonfirmasi")
            .toggleClass("pg-modal--danger", !!danger)
            .toggleClass("pg-modal--confirm", !danger);
        $("#modalKonfirmasi .btn-konfirmasi")
            .attr("id", button_id)
            .removeData("busy")
            .prop("disabled", false)
            .css({ "min-width": "", height: "" })
            .toggleClass("btn-danger pg-btn-confirm--danger", !!danger)
            .toggleClass("btn-success pg-btn-confirm", !danger)
            .html('<i class="fe fe-check-circle me-1"></i>Konfirmasi');
        $('#modalKonfirmasi').modal("show");
    }

  function showModalDanger(text, button_id) {
    $("#text-danger").html(text);
    $("#modalDanger .btn-konfirmasi").attr("id", button_id);
    $('#modalDanger').modal("show");
  }

  $('.btn-cancel').on("click", function() {
    closeModalDelete();
    closeModalConfirm();
    closeModalDanger();
  })

  function closeModalDelete() {
    $('#modalDelete').modal("hide");
  }

  function closeModalConfirm() {
    $('#modalKonfirmasi').modal("hide");
  }

  function closeModalDanger() {
    $('#modalDanger').modal("hide");
  }

  $(document).on('hidden.bs.modal', '#modalKonfirmasi', function () {
    $('#modalKonfirmasi .modal-title').text('Konfirmasi');
    $('#modalKonfirmasi')
      .removeClass('pg-modal--danger')
      .addClass('pg-modal--confirm');
    $('#modalKonfirmasi .btn-konfirmasi')
      .removeClass('btn-danger pg-btn-confirm--danger')
      .addClass('btn-success pg-btn-confirm')
      .removeData('busy')
      .prop('disabled', false)
      .css({ 'min-width': '', height: '' })
      .html('<i class="fe fe-check-circle me-1"></i>Konfirmasi');
  });

  $(document).on('click', '#btn-kembali-photo', function() {
    $('#modalViewPhoto').modal('hide');
  });

  $(document).on("input", ".number-only", function() {
    $(this).val($(this).val().replace(/[^0-9]/g, ''));
    if ($(this).val()[0] === '0' && $(this).val().length > 1) {
      $(this).val($(this).val().substring(1));
    }
  })

  $(document).on("keyup", ".nominal_only", function() {
    $(this).val(formatRupiah(convertToAngka($(this).val())));
  });

  $(document).on("input", ".include-nol", function() {
    let val = $(this).val();
    val = val.replace(/[^0-9]/g, '');
    $(this).val(val);
  });


  function formatRupiah(angka, prefix) {
    angka = angka.toString();
    var number_string = angka.replace(/[^,\d]/g, "").toString(),
      split = number_string.split(","),
      sisa = split[0].length % 3,
      rupiah = split[0].substr(0, sisa),
      ribuan = split[0].substr(sisa).match(/\d{3}/gi);
    if (ribuan) {
      separator = sisa ? "." : "";
      rupiah += separator + ribuan.join(".");
    }
    rupiah = split[1] != undefined ? rupiah + "," + split[1] : rupiah;
    return prefix == undefined ? rupiah : rupiah ? prefix + rupiah : "";
  }

  function convertToAngka(rupiah) {
    return parseInt(rupiah.replace(/,.*|[^0-9]/g, ""), 10);
  }

  $(document).on("input", ".number-minus", function() {
    let val = $(this).val();
    if (val === "-") return;

    // Izinkan minus hanya di awal
    let isNegative = val.startsWith("-");

    // Hapus semua karakter selain angka
    val = val.replace(/[^0-9]/g, '');

    // Hapus leading zero
    if (val[0] === '0' && val.length > 1) {
      val = val.substring(1);
    }

    // Pasang kembali minus jika ada
    if (isNegative && val.length > 0) {
      val = "-" + val;
    }

    $(this).val(val);
  });
  $(document).on("keyup", ".nominal_minus", function() {
    let val = $(this).val();
    if (val === "-") return;

    let formatted = formatRupiahMinus(convertToAngkaMinus(val));

    $(this).val(formatted);
  });

  function formatRupiahMinus(angka, prefix) {
    if (angka === null || angka === undefined || angka === "") {
      angka = 0;
    } else {
      angka = Number(angka);
    }
    if (isNaN(angka)) angka = 0;

    let isNegative = angka < 0;
    angka = Math.abs(angka).toString();

    var number_string = angka.replace(/[^,\d]/g, "").toString();
    var split = number_string.split(",");
    var sisa = split[0].length % 3;
    var rupiah = split[0].substr(0, sisa);
    var ribuan = split[0].substr(sisa).match(/\d{3}/gi);
    if (ribuan) {
      separator = sisa ? "." : "";
      rupiah += separator + ribuan.join(".");
    }
    rupiah = split[1] != undefined ? rupiah + "," + split[1] : rupiah;

    let result = prefix == undefined ? rupiah : rupiah ? prefix + rupiah : "";
    return isNegative && result ? "-" + result : result;
  }

  function convertToAngkaMinus(rupiah) {
    let isNegative = rupiah.toString().startsWith("-");
    let angka = parseInt(rupiah.replace(/,.*|[^0-9]/g, ""), 10);
    return isNegative ? -angka : angka;
  }

  function LoadingButton(id) {
    var $btn = $(id);
    if (!$btn.length) return;
    // Lock current size so button doesn't expand when text changes
    $btn.css({
      'min-width': $btn.outerWidth() + 'px',
      'height': $btn.outerHeight() + 'px'
    });
    // text-light: spinner terlihat di tombol hijau/merah (pg-btn-confirm)
    $btn.html('<span class="spinner-border spinner-border-sm text-light" role="status" aria-hidden="true"></span>')
      .prop("disabled", true);
  }

  function ResetLoadingButton(id, text = null) {
    var $btn = $(id);
    if (!$btn.length) return;
    $btn.css({
      'min-width': '',
      'height': ''
    });
    $btn.html(`${text ? text : 'Save Changes'}`).prop("disabled", false);
  }

  function setLoadingRow(table) {
    if (!table) return;
    table.clear().draw();
    $(".dataTables_empty").html(
      '<span class="spinner-border spinner-border-sm me-2 text-primary" role="status"></span> Sedang memuat data...'
    );
  }

  // Gudang aktif (navbar) — dari DOM (header sudah set class active dari session)
  var activeWarehouseId = (function() {
    var el = document.querySelector('.warehouse-dropdown-item.active');
    return el ? el.getAttribute('data-id') : null;
  })();

  function getActiveWarehouseEl() {
    return document.querySelector('.warehouse-dropdown-item.active') ||
      document.querySelector('.warehouse-dropdown-item[data-id="' + (activeWarehouseId || '') + '"]');
  }

  function getActiveWarehouseId() {
    if (activeWarehouseId !== null && activeWarehouseId !== undefined && activeWarehouseId !== '') {
      return String(activeWarehouseId);
    }
    var el = getActiveWarehouseEl();
    return el ? el.getAttribute('data-id') : null;
  }

  function getActiveWarehouseName() {
    var name = $('.btn-warehouse span').first().text().trim();
    if (!name || name === 'Pilih Gudang...') return null;
    return name;
  }

  /** true = gudang utama (is_main_warehouse), false = eceran/lain, null = belum pilih */
  function isActiveMainWarehouse() {
    var el = getActiveWarehouseEl();
    if (!el) return null;
    var v = el.getAttribute('data-is-main');
    if (v === null || v === undefined || v === '') return null;
    return String(v) === '1';
  }

  function getMainWarehouseEl() {
    return document.querySelector('.warehouse-dropdown-item[data-is-main="1"]');
  }

  function getMainWarehouseId() {
    var el = getMainWarehouseEl();
    return el ? el.getAttribute('data-id') : null;
  }

  function getMainWarehouseName() {
    var el = getMainWarehouseEl();
    if (!el) return null;
    var span = el.querySelector('span');
    return span ? span.textContent.trim() : null;
  }

  /** 'main' | 'retail' | null */
  function getStockViewMode() {
    var isMain = isActiveMainWarehouse();
    if (isMain === null) return null;
    return isMain ? 'main' : 'retail';
  }

  function autocompleteCity(id, modalParent = null, prov_id = null) {
    if ($(id).hasClass('select2-hidden-accessible')) {
      $(id).select2('destroy');
    }

    //search country dan city
    $(id).select2({
      ajax: {
        url: "/autocompleteCity",
        dataType: "json",
        type: "post",
        delay: 250,
        data: function data(params) {
          return {
            "keyword": params.term,
            "prov_id": prov_id,
            '_token': $('meta[name="csrf-token"]').attr('content')
          };
        },
        processResults: function processResults(data) {
          return {
            results: $.map(data.data, function(item) {
              return item;
            }),
          };
        },
      },
      placeholder: "Pilih Kota",
      closeOnSelect: true,
      allowClear: true,

      width: "100%",
      dir: "ltr",
      dropdownParent: modalParent ? $(modalParent) : "",
    });
  }

  function autocompleteProv(id, modalParent = null) {
    if ($(id).hasClass('select2-hidden-accessible')) {
      $(id).select2('destroy');
    }

    //search country dan city
    $(id).select2({
      ajax: {
        url: "/autocompleteProv",
        dataType: "json",
        type: "post",
        delay: 250,
        data: function data(params) {
          return {
            "keyword": params.term,
            '_token': $('meta[name="csrf-token"]').attr('content')
          };
        },
        processResults: function processResults(data) {
          return {
            results: $.map(data.data, function(item) {
              return item;
            }),
          };
        },
      },
      placeholder: "Pilih Provinsi",
      closeOnSelect: true,
      allowClear: true,
      width: "100%",
      dropdownParent: modalParent ? $(modalParent) : "",
    });
  }

  function autocompleteArea(id, modalParent = null) {
    if ($(id).hasClass('select2-hidden-accessible')) {
      $(id).select2('destroy');
    }

    //search country dan city
    $(id).select2({
      ajax: {
        url: "/autocompleteArea",
        dataType: "json",
        type: "post",
        delay: 250,
        data: function data(params) {
          return {
            "keyword": params.term,
            '_token': $('meta[name="csrf-token"]').attr('content')
          };
        },
        processResults: function processResults(data) {
          return {
            results: $.map(data.data, function(item) {
              return item;
            }),
          };
        },
      },
      placeholder: "Pilih Wilayah",
      closeOnSelect: true,
      allowClear: true,
      width: "100%",
      dropdownParent: modalParent ? $(modalParent) : "",
    });
  }

  function autocompleteDistrict(id, modalParent = null, city_id = null) {
    if ($(id).hasClass('select2-hidden-accessible')) {
      $(id).select2('destroy');
    }

    //search country dan city
    $(id).select2({
      ajax: {
        url: "/autocompleteDistrict",
        dataType: "json",
        type: "post",
        delay: 250,
        data: function data(params) {
          return {
            "keyword": params.term,
            "city_id": city_id,
            '_token': $('meta[name="csrf-token"]').attr('content')
          };
        },
        processResults: function processResults(data) {
          return {
            results: $.map(data.data, function(item) {
              return item;
            }),
          };
        },
      },
      placeholder: "Pilih Kecamatan",
      closeOnSelect: true,
      allowClear: true,
      width: "100%",
      dropdownParent: modalParent ? $(modalParent) : "",
    });
  }

  function autocompleteCategory(id, modalParent = null) {
    if ($(id).hasClass('select2-hidden-accessible')) {
      $(id).select2('destroy');
    }

    //search country dan city
    $(id).select2({
      ajax: {
        url: "/autocompleteCategory",
        dataType: "json",
        type: "post",
        delay: 250,
        data: function data(params) {
          return {
            "keyword": params.term,
            '_token': $('meta[name="csrf-token"]').attr('content')
          };
        },
        processResults: function processResults(data) {
          return {
            results: $.map(data.data, function(item) {
              return item;
            }),
          };
        },
      },
      placeholder: "Pilih Kategori",
      closeOnSelect: true,
      allowClear: true,
      width: "100%",
      dropdownParent: modalParent ? $(modalParent) : "",
    });
  }

  function autocompleteVariant(id, modalParent = null) {
    if ($(id).hasClass('select2-hidden-accessible')) {
      $(id).select2('destroy');
    }

    //search country dan city
    $(id).select2({
      ajax: {
        url: "/autocompleteVariant",
        dataType: "json",
        type: "post",
        delay: 250,
        data: function data(params) {
          return {
            "keyword": params.term,
            '_token': $('meta[name="csrf-token"]').attr('content')
          };
        },
        processResults: function processResults(data) {
          return {
            results: $.map(data.data, function(item) {
              return item;
            }),
          };
        },
      },
      placeholder: "Pilih Variasi",
      closeOnSelect: true,
      allowClear: true,
      width: "100%",
      dropdownParent: modalParent ? $(modalParent) : "",
    });
  }

  function autocompleteUnit(id, modalParent = null) {
    if ($(id).hasClass('select2-hidden-accessible')) {
      $(id).select2('destroy');
    }

    //search country dan city
    $(id).select2({
      ajax: {
        url: "/autocompleteUnit",
        dataType: "json",
        type: "post",
        delay: 250,
        data: function data(params) {
          return {
            "keyword": params.term,
            '_token': $('meta[name="csrf-token"]').attr('content')
          };
        },
        processResults: function processResults(data) {
          return {
            results: $.map(data.data, function(item) {
              return item;
            }),
          };
        },
      },
      placeholder: "Pilih Satuan",
      closeOnSelect: true,
      allowClear: true,
      multiple: true,
      tags: true, // Ini adalah properti utama untuk mengaktifkan tagging
      width: "100%",
      dropdownParent: modalParent ? $(modalParent) : "",
    });
  }

  /** Deteksi device touch (tablet/HP) — dipakai buat skip autofocus search Select2. */
  function pgIsTouchDevice() {
    return ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);
  }

  /** Fokus kolom search Select2 (modal + dropdownParent body / mobile). */
  function attachSelect2SearchOpenFix($el) {
    $el.off('select2:open.pgSearchFix');
    $el.on('select2:open.pgSearchFix', function() {
      setTimeout(function() {
        var $open = $('.select2-container--open').last();
        $open.find('.select2-search--dropdown').removeClass('select2-search--hide').show();
        var $search = $open.find('.select2-search__field');
        if ($search.length) {
          $search.prop('readonly', false).attr('inputmode', 'search');
          // Di tablet/touch, auto-focus di sini memicu keyboard on-screen yang
          // langsung menggeser posisi list opsi (browser scroll-into-view buat
          // input yang fokus). Kalau user udah mulai tap ke arah item pas layout
          // bergeser, tap-nya mendarat di opsi lain -> item yang ke-pilih beda
          // dari yang dimaksud user ("auto pilih" salah produk, GitHub #142).
          // Search field tetap kelihatan & bisa di-tap manual buat ketik cari;
          // cuma auto-focus-nya yang di-skip khusus touch device.
          if (!pgIsTouchDevice()) {
            $search.trigger('focus');
          }
        }
      }, 0);
    });
  }

  function autocompleteBom(id, modalParent = null) {
    if ($(id).hasClass('select2-hidden-accessible')) {
      $(id).select2('destroy');
    }

    //search country dan city
    $(id).select2({
      ajax: {
        url: "/autocompleteBom",
        dataType: "json",
        type: "post",
        delay: 300,
        data: function data(params) {
          return {
            "keyword": params.term,
            "page": params.page || 1,
            "limit": 30,
            '_token': $('meta[name="csrf-token"]').attr('content')
          };
        },
        processResults: function processResults(data, params) {
          params.page = params.page || 1;
          return {
            results: $.map(data.data || [], function(item) {
              return item;
            }),
            pagination: {
              more: !!(data.pagination && data.pagination.more)
            }
          };
        },
      },
      placeholder: "Pilih Produk",
      closeOnSelect: true,
      allowClear: true,
      minimumResultsForSearch: 0,
      width: "100%",
      dropdownParent: modalParent ? $(modalParent) : "",
    });
    attachSelect2SearchOpenFix($(id));
  }

  function autocompleteProduct(id, modalParent = null) {
    if ($(id).hasClass('select2-hidden-accessible')) {
      $(id).select2('destroy');
    }

    //search country dan city
    $(id).select2({
      ajax: {
        url: "/autocompleteProduct",
        dataType: "json",
        type: "post",
        delay: 250,
        data: function data(params) {
          return {
            "keyword": params.term,
            '_token': $('meta[name="csrf-token"]').attr('content')
          };
        },
        processResults: function processResults(data) {
          return {
            results: $.map(data.data, function(item) {
              return item;
            }),
          };
        },
      },
      placeholder: "Pilih Produk",
      closeOnSelect: true,
      allowClear: true,
      width: "100%",
      dropdownParent: modalParent ? $(modalParent) : "",
    });
  }

  function autocompleteSupplies(id, modalParent = null) {
    if ($(id).hasClass('select2-hidden-accessible')) {
      $(id).select2('destroy');
    }

    //search country dan city
    $(id).select2({
      ajax: {
        url: "/autocompleteSupplies",
        dataType: "json",
        type: "post",
        delay: 300,
        data: function data(params) {
          return {
            "keyword": params.term,
            "page": params.page || 1,
            "limit": 30,
            '_token': $('meta[name="csrf-token"]').attr('content')
          };
        },
        processResults: function processResults(data, params) {
          params.page = params.page || 1;
          return {
            results: $.map(data.data || [], function(item) {
              return item;
            }),
            pagination: {
              more: !!(data.pagination && data.pagination.more)
            }
          };
        },
      },
      placeholder: "Pilih Bahan Mentah",
      closeOnSelect: true,
      allowClear: true,
      width: "100%",
      dropdownParent: modalParent ? $(modalParent) : "",
    });
  }

  function autocompleteSupplier(id, modalParent = null) {
    if ($(id).hasClass('select2-hidden-accessible')) {
      $(id).select2('destroy');
    }

    //search country dan city
    $(id).select2({
      ajax: {
        url: "/autocompleteSupplier",
        dataType: "json",
        type: "post",
        delay: 250,
        data: function data(params) {
          return {
            "keyword": params.term,
            '_token': $('meta[name="csrf-token"]').attr('content')
          };
        },
        processResults: function processResults(data) {
          return {
            results: $.map(data.data, function(item) {
              return item;
            }),
          };
        },
      },
      placeholder: "Pilih Supplier",
      closeOnSelect: true,
      allowClear: true,
      width: "100%",
      dropdownParent: modalParent ? $(modalParent) : "",
    });
  }

  function autocompleteSuppliesVariant(id, modalParent = null, supplier_id = null) {
    if ($(id).hasClass('select2-hidden-accessible')) {
      $(id).select2('destroy');
    }
    //search country dan city
    $(id).select2({
      ajax: {
        url: "/autocompleteSuppliesVariant",
        dataType: "json",
        type: "post",
        delay: 250,
        data: function data(params) {
          return {
            "keyword": params.term,
            "supplier_id": supplier_id,
            '_token': $('meta[name="csrf-token"]').attr('content')
          };
        },
        processResults: function processResults(data) {
          return {
            results: $.map(data.data, function(item) {
              return item;
            }),
          };
        },
      },
      placeholder: "Pilih Bahan Mentah",
      closeOnSelect: true,
      allowClear: true,
      width: "100%",
      dropdownParent: modalParent ? $(modalParent) : "",
    });

  }

  function autocompleteSuppliesVariantOnly(id, modalParent = null, supplier_id = null) {
    // Destroy dulu kalau sudah pernah di-init
    if ($(id).hasClass('select2-hidden-accessible')) {
      $(id).select2('destroy');
    }
    //search country dan city
    $(id).select2({
      ajax: {
        url: "/autocompleteSuppliesVariantOnly",
        dataType: "json",
        type: "post",
        delay: 250,
        data: function data(params) {
          return {
            "keyword": params.term,
            "supplier_id": supplier_id,
            '_token': $('meta[name="csrf-token"]').attr('content')
          };
        },
        processResults: function processResults(data) {
          return {
            results: $.map(data.data, function(item) {
              return item;
            }),
          };
        },
      },
      placeholder: "Pilih Bahan Mentah",
      closeOnSelect: true,
      allowClear: true,
      width: "100%",
      dropdownParent: modalParent ? $(modalParent) : "",
    });
    $(id).on('select2:open', function() {
      // Pastikan modal tetap scrollable saat dropdown terbuka
      $('.modal').css('overflow', 'auto');
    });

    $(id).on('select2:close', function() {
      $('.modal').css('overflow', '');
    });
  }

  function formatProductVariantSelect2Label(item) {
    if (!item || item.loading) {
      return item && item.text ? item.text : "";
    }
    var sku = String(item.product_variant_sku || "").trim();
    var name = String(item.pr_name || "").trim();
    var variant = String(item.product_variant_name || "").trim();
    if (variant && name.indexOf(variant) === -1) {
      name = (name + " " + variant).replace(/\s+/g, " ").trim();
    }
    if (sku && sku !== "-") {
      return name ? (sku + " | " + name) : sku;
    }
    return name || item.text || "-";
  }

  function mapProductVariantSelect2Results(data) {
    return {
      results: $.map(data.data || [], function(item) {
        item.text = formatProductVariantSelect2Label(item);
        return item;
      }),
    };
  }

  function productVariantSelect2AjaxOptions(url) {
    return {
      url: url,
      dataType: "json",
      type: "post",
      delay: 400,
      data: function data(params) {
        return {
          "keyword": params.term,
          '_token': $('meta[name="csrf-token"]').attr('content')
        };
      },
      processResults: mapProductVariantSelect2Results,
    };
  }

  function autocompleteProductVariant(id, modalParent = null) {
    if ($(id).hasClass('select2-hidden-accessible')) {
      $(id).select2('destroy');
    }

    //search country dan city
    $(id).select2({
      ajax: productVariantSelect2AjaxOptions("/autocompleteProductVariant"),
      placeholder: "Pilih Produk",
      closeOnSelect: true,
      allowClear: true,
      width: "100%",
      dropdownParent: modalParent ? $(modalParent) : "",
    });
  }

  function autocompleteProductVariantOnly(id, modalParent = null) {
    if ($(id).hasClass('select2-hidden-accessible')) {
      $(id).select2('destroy');
    }

    //search country dan city
    $(id).select2({
      ajax: productVariantSelect2AjaxOptions("/autocompleteProductVariants"),
      placeholder: "Pilih Produk",
      closeOnSelect: true,
      allowClear: true,
      width: "100%",
      dropdownParent: modalParent ? $(modalParent) : "",
    });
  }

  function autocompleteCustomer(id, modalParent = null) {
    if ($(id).hasClass('select2-hidden-accessible')) {
      $(id).select2('destroy');
    }

    //search country dan city
    $(id).select2({
      ajax: {
        url: "/autocompleteCustomer",
        dataType: "json",
        type: "post",
        delay: 250,
        data: function data(params) {
          return {
            "keyword": params.term,
            '_token': $('meta[name="csrf-token"]').attr('content')
          };
        },
        processResults: function processResults(data) {
          return {
            results: $.map(data.data, function(item) {
              return item;
            }),
          };
        },
      },
      placeholder: "Pilih Armada",
      closeOnSelect: true,
      allowClear: true,
      width: "100%",
      dropdownParent: modalParent ? $(modalParent) : "",
    });
  }

  function autocompleteStaffSales(id, modalParent = null) {
    if ($(id).hasClass('select2-hidden-accessible')) {
      $(id).select2('destroy');
    }

    //search country dan city
    $(id).select2({
      ajax: {
        url: "/autocompleteStaffSales",
        dataType: "json",
        type: "post",
        delay: 250,
        data: function data(params) {
          return {
            "keyword": params.term,
            '_token': $('meta[name="csrf-token"]').attr('content')
          };
        },
        processResults: function processResults(data) {
          return {
            results: $.map(data.data, function(item) {
              return item;
            }),
          };
        },
      },
      placeholder: "Pilih Sales",
      closeOnSelect: true,
      allowClear: true,
      width: "100%",
      dropdownParent: modalParent ? $(modalParent) : "",
    });
  }

  function autocompleteCashCategory(id, modalParent = null) {
    if ($(id).hasClass('select2-hidden-accessible')) {
      $(id).select2('destroy');
    }

    //search country dan city
    $(id).select2({
      ajax: {
        url: "/autocompleteCashCategory",
        dataType: "json",
        type: "post",
        delay: 250,
        data: function data(params) {
          return {
            "keyword": params.term,
            '_token': $('meta[name="csrf-token"]').attr('content')
          };
        },
        processResults: function processResults(data) {
          return {
            results: $.map(data.data, function(item) {
              return item;
            }),
          };
        },
      },
      placeholder: "Pilih Kategori Kas",
      closeOnSelect: true,
      allowClear: true,
      width: "100%",
      dropdownParent: modalParent ? $(modalParent) : "",
    });
  }


  function autocompleteStaff(id, modalParent = null) {
    if ($(id).hasClass('select2-hidden-accessible')) {
      $(id).select2('destroy');
    }

    //search country dan city
    $(id).select2({
      ajax: {
        url: "/autocompleteStaff",
        dataType: "json",
        type: "post",
        data: function data(params) {
          return {
            "keyword": params.term,
            '_token': $('meta[name="csrf-token"]').attr('content')
          };
        },
        processResults: function processResults(data) {
          return {
            results: $.map(data.data, function(item) {
              return item;
            }),
          };
        },
      },
      placeholder: "Pilih Staff",
      closeOnSelect: true,
      allowClear: true,
      width: "100%",
      dropdownParent: modalParent ? $(modalParent) : "",
    });
  }

  function autocompleteRole(id, modalParent = null) {
    if (!$(id).length) return;

    if ($(id).hasClass('select2-hidden-accessible')) {
      $(id).select2('destroy');
    }

    $(id).select2({
      ajax: {
        url: "/autocompleteRole",
        dataType: "json",
        type: "post",
        delay: 250,
        data: function data(params) {
          return {
            "keyword": params.term || '',
            '_token': $('meta[name="csrf-token"]').attr('content') || (typeof token !== 'undefined' ? token : '')
          };
        },
        processResults: function processResults(data) {
          var rows = (data && data.data) ? data.data : [];
          return {
            results: $.map(rows, function(item) {
              return {
                id: item.id,
                text: item.text
              };
            }),
          };
        },
      },
      placeholder: "Pilih Posisi",
      closeOnSelect: true,
      allowClear: true,
      width: "100%",
      dropdownParent: modalParent ? $(modalParent) : "",
    });
  }

  function autocompleteWarehouseType(id, modalParent = null) {
    if ($(id).hasClass('select2-hidden-accessible')) {
      $(id).select2('destroy');
    }

    $(id).select2({
      ajax: {
        url: "/autocompleteWarehouseType",
        dataType: "json",
        type: "get",
        delay: 250,
        data: function data(params) {
          return {
            "keyword": params.term
          };
        },
        processResults: function processResults(data) {
          return {
            results: $.map(data.data, function(item) {
              return {
                id: item.id,
                text: item.text || item.warehouse_type_name,
                is_main_warehouse: item.is_main_warehouse
              };
            }),
          };
        },
      },
      placeholder: "Pilih Tipe Gudang...",
      closeOnSelect: true,
      allowClear: true,
      width: "100%",
      dropdownParent: modalParent ? $(modalParent) : "",
    });
  }

  function autocompleteWarehouse(id, modalParent = null, opts = null) {
    if ($(id).hasClass('select2-hidden-accessible')) {
      $(id).select2('destroy');
    }
    opts = opts || {};

    $(id).select2({
      ajax: {
        url: "/autocompleteWarehouse",
        dataType: "json",
        type: "post",
        delay: 250,
        data: function data(params) {
          var payload = {
            "keyword": params.term,
            '_token': $('meta[name="csrf-token"]').attr('content')
          };
          if (opts.retailOnly) payload.retail_only = 1;
          if (opts.mainFirst) payload.main_first = 1;
          if (opts.mainOnly) payload.main_only = 1;
          return payload;
        },
        processResults: function processResults(data) {
          return {
            results: $.map(data.data || [], function(item) {
              return item;
            }),
          };
        },
      },
      placeholder: opts.placeholder || "Pilih toko atau gudang",
      closeOnSelect: true,
      allowClear: true,
      minimumResultsForSearch: 0,
      width: "100%",
      dropdownParent: modalParent ? $(modalParent) : "",
    });
    attachSelect2SearchOpenFix($(id));
  }

  function autocompleteRekening(id, modalParent = null) {
    if ($(id).hasClass('select2-hidden-accessible')) {
      $(id).select2('destroy');
    }

    //search country dan city
    $(id).select2({
      ajax: {
        url: "/autocompleteRekening",
        dataType: "json",
        type: "post",
        data: function data(params) {
          return {
            "keyword": params.term,
            '_token': $('meta[name="csrf-token"]').attr('content')
          };
        },
        processResults: function processResults(data) {
          return {
            results: $.map(data.data, function(item) {
              return item;
            }),
          };
        },
      },
      placeholder: "Pilih Bank Account",
      closeOnSelect: true,
      allowClear: true,
      width: "100%",
      dropdownParent: modalParent ? $(modalParent) : "",
    });
  }

  function autocompletePO(id, modalParent = null, ids = null, suppliesIds = null) {
    if ($(id).hasClass('select2-hidden-accessible')) {
      $(id).select2('destroy');
    }

    //search country dan city
    $(id).select2({
      ajax: {
        url: "/autocompletePO",
        dataType: "json",
        type: "post",
        data: function data(params) {
          return {
            "keyword": params.term,
            "ids": ids,
            '_token': $('meta[name="csrf-token"]').attr('content')
          };
        },
        processResults: function processResults(data) {
          return {
            results: $.map(data.data, function(item) {
              return item;
            }),
          };
        },
      },
      placeholder: "Pilih Nomor PO",
      closeOnSelect: true,
      allowClear: true,
      width: "100%",
      dropdownParent: modalParent ? $(modalParent) : "",
    });
  }

  function autocompleteSO(id, modalParent = null) {
    if ($(id).hasClass('select2-hidden-accessible')) {
      $(id).select2('destroy');
    }

    //search country dan city
    $(id).select2({
      ajax: {
        url: "/autocompleteSO",
        dataType: "json",
        type: "post",
        data: function data(params) {
          return {
            "keyword": params.term,
            '_token': $('meta[name="csrf-token"]').attr('content')
          };
        },
        processResults: function processResults(data) {
          return {
            results: $.map(data.data, function(item) {
              return item;
            }),
          };
        },
      },
      placeholder: "Pilih Nomor SO",
      closeOnSelect: true,
      allowClear: true,
      width: "100%",
      dropdownParent: modalParent ? $(modalParent) : "",
    });
  }
</script>
<script>
  let rotationAngle = 0; // rotasi foto
  let camRotation = 0; // rotasi kamera
  let photoData = "";
  let currentStream = null;
  let cameraRequestId = 0; // invalidates stale/overlapping getUserMedia() calls
  var modeCamera = 1; //1= upload, 2 = savefile
  var inputFile = null;
  var cameraReturnModal = null;

  function parsePhotoInputValue(value) {
    if (Array.isArray(value)) return value.filter(Boolean);
    if (value === null || value === undefined) return [];

    var raw = String(value).trim();
    if (raw === "" || raw === "null" || raw === "undefined") return [];

    try {
      var parsed = JSON.parse(raw);
      if (Array.isArray(parsed)) return parsed.filter(Boolean);
      if (typeof parsed === "string" && parsed.trim() !== "") return [parsed];
      return [];
    } catch (err) {
      return [raw];
    }
  }

  function hasPhotoInputValue(value) {
    return parsePhotoInputValue(value).length > 0;
  }

  function appendPhotoToInput(selector, photo) {
    var photos = parsePhotoInputValue($(selector).val());
    if (photo) photos.push(photo);
    $(selector).val(JSON.stringify(photos));
    return photos;
  }

  function showCameraReturnModal(defaultSelector) {
    var selector = cameraReturnModal || defaultSelector;
    cameraReturnModal = null;
    if (!selector) return;
    var $photo = $("#modalPhoto");
    function openReturn() {
      $(selector).modal("show");
    }
    if ($photo.hasClass("show") || $photo.hasClass("showing")) {
      $photo.one("hidden.bs.modal", openReturn);
      return;
    }
    openReturn();
  }

  function stopCameraStream() {
    cameraRequestId++; // invalidate any getUserMedia() call still in flight
    if (currentStream) {
      currentStream.getTracks().forEach(function(t) {
        t.stop();
      });
      currentStream = null;
    }
    var video = document.getElementById("video");
    if (video) video.srcObject = null;
  }

  function resetCameraModalUi() {
    photoData = "";
    camRotation = 0;
    rotationAngle = 0;
    $("#video").removeClass("rot90 rot180 rot270");
    $("#camera").css("min-height", "240px");
    $("#modalPhoto .modal-body").css("height", "");
    $("#preview-box").hide();
    $("#camera").show();
    $("#preview-actions").attr("style", "display: none !important;");
    $("#camera-actions").attr("style", "display: flex !important;");
    $("#previewImage").attr("src", "");
  }

  function getCameraReturnModalSelector() {
    if (cameraReturnModal) return cameraReturnModal;
    if (modeCamera == 3) return "#add_sales_order";
    if (modeCamera == 4) return "#add_purchase_order";
    if (modeCamera == 2) return "#add-product-issues";
    if (modeCamera == 5) return "#modalKonfirmasi";
    return null;
  }

  function closeCameraModal() {
    stopCameraStream();
    resetCameraModalUi();
    $('#modalPhoto').modal('hide');
    var returnModal = getCameraReturnModalSelector();
    if (returnModal) {
      showCameraReturnModal(returnModal);
    }
  }

  $(document).on('click', '#btn-kembali-camera', function() {
    closeCameraModal();
  });

  $(document).on('click', '#btn-close-camera', function(event) {
    event.preventDefault();
    closeCameraModal();
  });

  function getCameraErrorMessage(err) {
    if (!err || !err.name) {
      return "Tidak bisa mengakses kamera. Pastikan izin kamera aktif di browser.";
    }
    if (err.name === "NotAllowedError" || err.name === "PermissionDeniedError") {
      return "Izin kamera ditolak. Aktifkan akses kamera untuk situs ini di pengaturan browser.";
    }
    if (err.name === "NotFoundError" || err.name === "DevicesNotFoundError") {
      return "Kamera tidak ditemukan pada perangkat ini.";
    }
    if (err.name === "NotReadableError" || err.name === "TrackStartError") {
      return "Kamera sedang digunakan aplikasi lain. Tutup aplikasi tersebut lalu coba lagi.";
    }
    if (err.name === "OverconstrainedError") {
      return "Pengaturan kamera tidak didukung perangkat ini.";
    }
    if (err.name === "SecurityError") {
      return "Akses kamera diblokir. Buka aplikasi lewat HTTPS atau localhost.";
    }
    return "Tidak bisa mengakses kamera. Pastikan izin kamera aktif di browser.";
  }

  function attachCameraStream(video, stream) {
    currentStream = stream;
    video.srcObject = stream;
    video.muted = true;
    video.onloadedmetadata = function() {
      adjustCameraLayout();
    };
    return video.play().catch(function(playErr) {
      console.warn("Preview kamera menunggu modal siap:", playErr);
    });
  }

  function isCameraPreviewActive() {
    return $("#modalPhoto").hasClass("show") && $("#camera").is(":visible") && !$("#preview-box").is(":visible");
  }

  function ensureCameraPreviewPlaying() {
    var video = document.getElementById("video");
    if (!video || !isCameraPreviewActive()) return Promise.resolve(false);

    if (currentStream) {
      if (video.srcObject !== currentStream) {
        video.srcObject = currentStream;
      }
      video.muted = true;
      if (!video.paused && video.readyState >= 2) {
        return Promise.resolve(true);
      }
      return video.play().catch(function() {
        return startCamera();
      });
    }

    return startCamera();
  }

  // =========================
  // START CAMERA FUNCTION
  // =========================
  function startCamera() {
    let video = document.getElementById("video");
    if (!video) {
      console.warn("Element video kamera tidak ditemukan.");
      return Promise.resolve(false);
    }

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      var reason = (window.isSecureContext === false)
        ? "Akses kamera diblokir browser karena halaman ini dibuka lewat koneksi HTTP (tidak aman), bukan HTTPS. Ini bukan masalah di perangkat Anda — minta tim IT/developer mengaktifkan HTTPS untuk domain ini."
        : "Browser/perangkat tidak mendukung akses kamera.";
      notifikasi("error", "Gagal Kamera", reason);
      return Promise.resolve(false);
    }

    // Stop any previous stream and invalidate any getUserMedia() call that is
    // still pending, so it can't clobber/leak past this new request.
    stopCameraStream();
    var requestId = ++cameraRequestId;

    return navigator.mediaDevices.getUserMedia({
        video: {
          facingMode: {
            ideal: "environment"
          }
        }
      }).catch(function() {
        return navigator.mediaDevices.getUserMedia({
          video: true
        });
      })
      .then(stream => {
        // A newer startCamera()/stopCameraStream() happened while this
        // request was in flight (double click, modal closed, retake, ...).
        // Release this stream instead of leaking it or overwriting the
        // stream that's actually in use.
        if (requestId !== cameraRequestId) {
          stream.getTracks().forEach(t => t.stop());
          return false;
        }
        return attachCameraStream(video, stream);
      })
      .catch(function(err) {
        console.error("Tidak bisa akses kamera:", err);
        notifikasi("error", "Gagal Kamera", getCameraErrorMessage(err));
        return false;
      });
  }

  $("#modalPhoto").on("shown.bs.modal", function() {
    ensureCameraPreviewPlaying();
  });

  $("#modalPhoto").on("hidden.bs.modal", function() {
    stopCameraStream();
  });

  // =========================
  // WHEN OPEN MODAL
  // =========================
  $(document).on('click', '.fotoProduksi', function() {
    modeCamera = 1;
    rotationAngle = 0;
    camRotation = 0;
    photoData = "";

    $("#video").removeClass("rot90 rot180 rot270");
    $("#preview-box").hide();
    $("#camera").show();

    startCamera();
    $('#modalPhoto').modal('show');
  });


  // =========================
  // ROTATE CAMERA PREVIEW
  // =========================
  $(document).on("click", "#rotateCameraBtn", function() {
    camRotation = (camRotation + 90) % 360;

    $("#video")
      .removeClass("rot90 rot180 rot270")
      .addClass(
        camRotation === 90 ? "rot90" :
        camRotation === 180 ? "rot180" :
        camRotation === 270 ? "rot270" : ""
      );
    adjustCameraLayout();
  });

  // =========================
  // CAPTURE PHOTO
  // =========================
  $(document).on("click", "#captureBtn", function() {
    let video = document.getElementById("video");
    let canvas = document.getElementById("canvas");
    let ctx = canvas.getContext("2d");

    let vw = video.videoWidth;
    let vh = video.videoHeight;

    // CANVAS SIZE BERDASARKAN ROTASI KAMERA
    if (camRotation === 90 || camRotation === 270) {
      canvas.width = vh;
      canvas.height = vw;
    } else {
      canvas.width = vw;
      canvas.height = vh;
    }

    ctx.save();
    ctx.translate(canvas.width / 2, canvas.height / 2);
    ctx.rotate(camRotation * Math.PI / 180);
    ctx.drawImage(video, -vw / 2, -vh / 2);
    ctx.restore();

    photoData = canvas.toDataURL("image/png");
    $("#previewImage").attr("src", photoData);

    $("#camera").hide();
    $("#preview-box").show();
    $("#camera-actions").attr("style", "display: none !important;");
    $("#preview-actions").attr("style", "display: flex !important;");
  });

  // =========================
  // RETAKE
  // =========================
  $(document).on("click", "#retakeBtn", function() {
    $("#preview-box").hide();
    $("#camera").show();
    $("#preview-actions").attr("style", "display: none !important;");
    $("#camera-actions").attr("style", "display: flex !important;");

    camRotation = 0;
    $("#video").removeClass("rot90 rot180 rot270");

    startCamera();
  });

  // =========================
  // UPLOAD
  // =========================
  $(document).on("click", "#uploadBtn", function() {
    if (modeCamera != 1 && !photoData) {
      notifikasi("error", "Gagal Upload", "Ambil foto terlebih dahulu.");
      return;
    }

    if (modeCamera == 1) {
      $.ajax({
        url: "/uploadPhotoProduksi",
        type: "POST",
        data: JSON.stringify({
          photo: photoData
        }),
        contentType: "application/json",
        headers: {
          "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
        },
        success: function(response) {
          notifikasi("success", "Sukses", "Foto berhasil diupload");
          closeCameraModal();
        },
        error: function() {
          notifikasi("error", "Gagal", "Foto gagal diupload");
        }
      });
    } else if (modeCamera == 3) {
      appendPhotoToInput(inputFile, photoData);
      stopCameraStream();
      resetCameraModalUi();
      showCameraReturnModal(cameraReturnModal || "#add_sales_order");
      $('#modalPhoto').modal('hide');
    } else if (modeCamera == 4) {
      appendPhotoToInput(inputFile, photoData);
      stopCameraStream();
      resetCameraModalUi();
      showCameraReturnModal("#add_purchase_order");
      $('#modalPhoto').modal('hide');
    } else if (modeCamera == 5) {
      if (inputFile) $(inputFile).val(photoData);
      stopCameraStream();
      resetCameraModalUi();
      showCameraReturnModal("#modalKonfirmasi");
      $('#modalPhoto').modal('hide');
    } else {
      if (inputFile) $(inputFile).val(photoData);
      stopCameraStream();
      resetCameraModalUi();
      showCameraReturnModal("#add-product-issues");
      $('#modalPhoto').modal('hide');
    }

  });

  function adjustCameraLayout() {
    var video = document.getElementById("video");
    var camera = document.getElementById("camera");
    if (!video || !camera) return;

    // Jangan paksa tinggi modal-body — ini yang bikin preview hitam setelah Putar
    $("#modalPhoto .modal-body").css("height", "");

    var vw = video.videoWidth || 0;
    var vh = video.videoHeight || 0;
    var boxW = camera.clientWidth || 300;

    if (!vw || !vh) {
      camera.style.minHeight = "240px";
      return;
    }

    var displayH;
    if (camRotation === 90 || camRotation === 270) {
      displayH = boxW * (vw / vh);
    } else {
      displayH = boxW * (vh / vw);
    }

    displayH = Math.min(Math.max(240, displayH), window.innerHeight * 0.6);
    camera.style.minHeight = Math.round(displayH) + "px";
  }

  function showDataTableLoading(tableId) {
    var elem = tableId ? $(tableId + ' .dataTables_empty') : $('.dataTables_empty');
    elem.html(
      '<span class="spinner-border spinner-border-sm me-2 text-primary" role="status"></span> Sedang memuat data...');
  }

  $(document).ready(function() {
    function closeWarehouseDropdown() {
      var root = document.querySelector('.warehouse-custom-dropdown');
      if (!root) return;
      var toggle = root.querySelector('[data-bs-toggle="dropdown"]');
      var menu = root.querySelector('.dropdown-menu');
      if (toggle && window.bootstrap && bootstrap.Dropdown) {
        try {
          var inst = bootstrap.Dropdown.getInstance(toggle);
          if (inst) inst.hide();
        } catch (err) {}
      }
      if (toggle) {
        toggle.classList.remove('show');
        toggle.setAttribute('aria-expanded', 'false');
      }
      if (menu) menu.classList.remove('show');
      root.classList.remove('show');
      if (document.activeElement && root.contains(document.activeElement)) {
        document.activeElement.blur();
      }
    }

    // Chrome sering restore-focus ke tombol gudang setelah refresh → dropdown kebuka
    closeWarehouseDropdown();
    $(window).on('load pageshow', function() {
      closeWarehouseDropdown();
      setTimeout(closeWarehouseDropdown, 0);
      setTimeout(closeWarehouseDropdown, 100);
    });

    $('.warehouse-dropdown-item').on('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      var warehouseId = $(this).attr('data-id');
      if (!warehouseId) return;

      // Blur dulu supaya focus restoration tidak buka dropdown lagi setelah reload
      if (document.activeElement) document.activeElement.blur();
      closeWarehouseDropdown();

      $.ajax({
        url: '/set-active-warehouse',
        type: 'POST',
        data: {
          _token: $('meta[name="csrf-token"]').attr('content'),
          warehouse_id: warehouseId
        },
        success: function(response) {
          window.location.reload();
        },
        error: function() {
          notifikasi('error', 'Gagal', 'Terjadi kesalahan saat mengubah gudang aktif.');
        }
      });
    });
  });
</script>
