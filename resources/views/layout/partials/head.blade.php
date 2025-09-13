    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="{{ url('assets/css/bootstrap.min.css') }}">

    {{-- Select2 --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Font family -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">

    <!-- Fontawesome CSS -->
    <link rel="stylesheet" href="{{ url('assets/plugins/fontawesome/css/fontawesome.min.css') }}">
    <link rel="stylesheet" href="{{ url('assets/plugins/fontawesome/css/all.min.css') }}">

    <!-- Feather CSS -->
    <link rel="stylesheet" href="{{ url('assets/plugins/feather/feather.css') }}">

    <style>
        .select2-selection__clear {
            padding: 0.6rem 1.2rem 0 0 !important;
        }

        /* Select2 Multiple - Chip */
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #082a58 !important;
            color: #fff !important;
            border: none !important;
            border-radius: 0.4rem !important;
            padding: 2px 8px !important;
            margin-top: 4px !important;
            display: flex !important;
            align-items: center !important;
        }

        /* Teks di dalam chip */
        .select2-container--default .select2-selection--multiple .select2-selection__choice__display {
            color: #fff !important;
            font-weight: 500 !important;
            padding-left: 1rem !important;
        }

        /* Tombol hapus di chip */
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            background: none !important;
            border: none !important;
            color: #fff !important;
            cursor: pointer !important;
            font-size: 14px !important;
            margin-right: 4px !important;
            padding: 0.3rem 0 0 0.5rem !important;
            line-height: 1 !important;
        }

        /* Tombol clear select2 unit */
        button.select2-selection__clear[aria-describedby*="unit-container"] {
            padding: 0.1rem 0 0.8rem !important;
        }

        /* Biar tinggi select2 multiple lebih konsisten */
        .select2-container .select2-selection--multiple {
            min-height: 38px !important;
            border: 1px solid #ccc !important;
            border-radius: 4px !important;
            padding: 2px !important;
        }

        /* Container rendered → supaya chip & input sejajar */
        .select2-container--default .select2-selection--multiple .select2-selection__rendered {
            display: flex !important;
            flex-wrap: wrap !important;
            align-items: center !important;
            padding: 2px 4px !important;
        }

        /* Inline search wrapper → biar search ikut fleksibel */
        .select2-container--default .select2-selection--multiple .select2-search--inline {
            flex: 1 !important;
        }

        /* Input search tetap inline */
        .select2-container .select2-selection--multiple .select2-search--inline .select2-search__field {
            height: 20px !important;
            margin: 2px 0 !important;
            padding: 2px !important;
            min-width: 50px !important;
            box-sizing: border-box !important;
        }

        .select2 .select2-container .select2-container--default .select2-container--below .select2-container--focus .select2-container--open{
            height: 40px !important;
        }

    </style>

    @if (Route::is([
            'bus-ticket',
            'car-booking-invoice',
            'cashreceipt-1',
            'cashreceipt-2',
            'cashreceipt-3',
            'cashreceipt-4',
            'coffee-shop',
            'domain-hosting',
            'ecommerce',
            'fitness-center',
            'flight-booking',
            'General-invoice-1',
            'General-invoice-2',
            'General-invoice-3',
            'General-invoice-4',
            'General-invoice-5',
            'hotel-booking',
            'internet-billing',
            'invoice-five',
            'invoice-four-a',
            'invoice-four',
            'invoice-one-a',
            'invoice-one',
            'invoice-three',
            'invoice-two',
            'mail-pay-invoice',
            'medical',
            'moneyexchange',
            'movie-ticket-booking',
            'pay-online',
            'restuarent-billing',
            'signature-preview-invoice',
            'student-billing',
            'train-ticket-booking',
        ]))
        <link rel="stylesheet" href="{{ url('assets/css/feather.css') }}">
    @endif

    @if (!Route::is(['index-two']))
        <!-- Datepicker CSS -->
        <link rel="stylesheet" href="{{ url('assets/css/bootstrap-datetimepicker.min.css') }}">
    @endif

    @if (!Route::is(['index-two', 'companies']))
        <!-- Datatables CSS -->
        <link rel="stylesheet" href="{{ url('assets/plugins/datatables/datatables.min.css') }}">
    @endif

    @if (Route::is(['companies']))
        <link rel="stylesheet" href="assets/css/dataTables.bootstrap5.min.css">
    @endif

    <!-- Select2 CSS -->
    <link rel="stylesheet" href="{{ url('assets/plugins/select2/css/select2.min.css') }}">

    @if (Route::is(['calendar']))
        <!-- Full Calander CSS -->
        <link rel="stylesheet" href="{{ url('assets/plugins/fullcalendar/fullcalendar.min.css') }}">
    @endif

    @if (Route::is(['companies']))
        <!-- Mobile CSS-->
        <link rel="stylesheet" href="assets/plugins/intltelinput/css/intlTelInput.css">
        <link rel="stylesheet" href="assets/plugins/intltelinput/css/demo.css">
    @endif

    @if (Route::is(['add-customer', 'edit-customer', 'testimonials']))
        <!-- Mobile CSS-->
        <link rel="stylesheet" href="assets/plugins/intltelinput/css/intlTelInput.css">
    @endif

    @if (Route::is(['plan-billing']))
        <!-- Owl carousel CSS -->
        <link rel="stylesheet" href="{{ url('assets/css/owl.carousel.min.css') }}">
    @endif

    @if (Route::is(['lightbox', 'template-invoice']))
        <!-- Lightbox CSS -->
        <link rel="stylesheet" href="{{ url('assets/plugins/lightbox/glightbox.min.css') }}">
    @endif

    @if (Route::is(['drag-drop', 'clipboard']))
        <!-- Dragula CSS -->
        <link rel="stylesheet" href="{{ url('assets/plugins/dragula/css/dragula.min.css') }}">
    @endif

    @if (Route::is(['text-editor']))
        <!-- Summernote CSS -->
        <link rel="stylesheet" href="{{ url('assets/plugins/summernote/summernote-bs4.min.css') }}">
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
        <link rel="stylesheet" href="{{ url('assets/plugins/summernote/summernote-lite.min.css') }}">
    @endif

    @if (Route::is(['icon-ionic']))
        <!-- Ionic CSS -->
        <link rel="stylesheet" href="{{ url('assets/plugins/icons/ionic/ionicons.css') }}">
    @endif

    @if (Route::is(['icon-material']))
        <!-- Material CSS -->
        <link rel="stylesheet" href="{{ url('assets/plugins/material/materialdesignicons.css') }}">
    @endif

    @if (Route::is(['icon-pe7']))
        <!-- Pe7 CSS -->
        <link rel="stylesheet" href="{{ url('assets/plugins/icons/pe7/pe-icon-7.css') }}">
    @endif

    @if (Route::is(['icon-simpleline']))
        <!-- Simpleline CSS -->
        <link rel="stylesheet" href="{{ url('assets/plugins/simpleline/simple-line-icons.css') }}">
    @endif

    @if (Route::is(['icon-themify']))
        <!-- Themify CSS -->
        <link rel="stylesheet" href="{{ url('assets/plugins/icons/themify/themify.css') }}">
    @endif

    @if (Route::is(['icon-weather']))
        <!-- weathericons CSS -->
        <link rel="stylesheet" href="{{ url('assets/plugins/icons/weather/weathericons.css') }}">
    @endif

    @if (Route::is(['icon-typicon']))
        <!-- typicons CSS typicon-->
        <link rel="stylesheet" href="{{ url('assets/plugins/icons/typicons/typicons.css') }}">
    @endif

    @if (Route::is(['icon-flag']))
        <!-- flags CSS -->
        <link rel="stylesheet" href="{{ url('assets/plugins/icons/flags/flags.css') }}">
    @endif

    @if (Route::is(['maps-vector']))
        <!-- Map CSS -->
        <link rel="stylesheet" href="{{ url('assets/plugins/jvectormap/jquery-jvectormap-2.0.3.css') }}">
    @endif

    @if (Route::is(['chart-c3']))
        <link rel="stylesheet" href="{{ url('assets/plugins/c3-chart/c3.min.css') }}">
    @endif

    @if (Route::is(['stickynote']))
        <!-- Sticky CSS -->
        <link rel="stylesheet" href="{{ url('assets/plugins/stickynote/sticky.css') }}">
    @endif

    @if (Route::is(['notification']))
        <link rel="stylesheet" href="{{ url('assets/plugins/alertify/alertify.min.css') }}">
    @endif

    @if (Route::is(['scrollbar']))
        <link rel="stylesheet" href="{{ url('assets/plugins/scrollbar/scroll.min.css') }}">
    @endif

    @if (Route::is(['rangeslider']))
        <!-- Rangeslider CSS -->
        <link rel="stylesheet" href="{{ url('assets/plugins/ion-rangeslider/css/ion.rangeSlider.min.css') }}">
    @endif

    <!-- Main CSS -->
    <link rel="stylesheet" href="{{ url('assets/css/style.css') }}">

    @if (!Route::is(['index-two', 'index-three', 'index-four', 'index-five']))
        <!-- Layout JS -->
        <script src="{{ url('assets/js/layout.js') }}"></script>
    @endif
<link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet"/>