    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">

    <!-- Font family -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">

    <!-- Fontawesome CSS -->
    <link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome/css/fontawesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome/css/all.min.css') }}">

    <!-- Feather CSS -->
    <link rel="stylesheet" href="{{ asset('assets/plugins/feather/feather.css') }}">

    <style>
        /* Tombol clear (x) select2 single — vertikal center */
        .select2-container--default .select2-selection--single {
            position: relative;
            display: flex !important;
            align-items: center !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            display: flex !important;
            align-items: center !important;
            flex: 1 1 auto !important;
            min-width: 0 !important;
            height: 100% !important;
            line-height: normal !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            white-space: nowrap !important;
            padding-right: 2.75rem !important;
            padding-left: 15px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__clear {
            position: absolute !important;
            right: 28px !important;
            top: 0 !important;
            bottom: 0 !important;
            height: auto !important;
            transform: none !important;
            float: none !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 0 !important;
            margin: 0 !important;
            line-height: 1 !important;
            font-size: 1rem;
            z-index: 1;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            top: 0 !important;
            bottom: 0 !important;
            height: auto !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow b {
            position: static !important;
            margin: 0 !important;
            transform: rotate(45deg) !important;
        }

        .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
            margin: 0 !important;
        }

        /* Select2 Multiple - Chip */
        .select2-container--default .select2-selection--multiple {
            position: relative;
            min-height: 38px !important;
            border: 1px solid #ccc !important;
            border-radius: 4px !important;
            padding: 2px 28px 2px 4px !important;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__rendered {
            display: block !important;
            padding: 0 !important;
            margin: 0 !important;
            overflow: visible !important;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #082a58 !important;
            color: #fff !important;
            border: none !important;
            border-radius: 0.4rem !important;
            padding: 2px 8px !important;
            margin: 3px 4px 3px 0 !important;
            float: left !important;
            display: inline-flex !important;
            align-items: center !important;
        }

        /* Teks di dalam chip */
        .select2-container--default .select2-selection--multiple .select2-selection__choice__display {
            color: #fff !important;
            font-weight: 500 !important;
            padding: 0 !important;
            margin-left: 4px !important;
        }

        /* Tombol hapus di chip */
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            background: none !important;
            border: none !important;
            color: #fff !important;
            cursor: pointer !important;
            font-size: 14px !important;
            margin: 0 !important;
            padding: 0 2px 0 0 !important;
            line-height: 1 !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        /* Clear all (x) pada select2 multiple */
        .select2-container--default .select2-selection--multiple .select2-selection__clear {
            position: absolute !important;
            right: 8px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            float: none !important;
            margin: 0 !important;
            padding: 0 !important;
            line-height: 1 !important;
            font-size: 1.1rem;
        }

        .select2-container {
            max-width: 100% !important;
            box-sizing: border-box !important;
        }

        .select2-container--default .select2-selection--multiple .select2-search--inline {
            float: left !important;
            margin-top: 3px !important;
        }

        /* Input search tetap inline */
        .select2-container .select2-selection--multiple .select2-search--inline .select2-search__field {
            height: 28px !important;
            margin: 0 !important;
            padding: 0 4px !important;
            min-width: 5em !important;
            box-sizing: border-box !important;
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
        <link rel="stylesheet" href="{{ asset('assets/css/feather.css') }}">
    @endif

    @if (!Route::is(['index-two']))
        <!-- Datepicker CSS -->
        <link rel="stylesheet" href="{{ asset('assets/css/bootstrap-datetimepicker.min.css') }}">
    @endif

    @if (!Route::is(['index-two', 'companies']))
        <!-- Datatables CSS -->
        <link rel="stylesheet" href="{{ asset('assets/plugins/datatables/datatables.min.css') }}">
    @endif

    @if (Route::is(['companies']))
        <link rel="stylesheet" href="assets/css/dataTables.bootstrap5.min.css">
    @endif

    <!-- Select2 CSS -->
    <link rel="stylesheet" href="{{ asset('assets/plugins/select2/css/select2.css') }}">

    @if (Route::is(['calendar']))
        <!-- Full Calander CSS -->
        <link rel="stylesheet" href="{{ asset('assets/plugins/fullcalendar/fullcalendar.min.css') }}">
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
        <link rel="stylesheet" href="{{ asset('assets/css/owl.carousel.min.css') }}">
    @endif

    @if (Route::is(['lightbox', 'template-invoice']))
        <!-- Lightbox CSS -->
        <link rel="stylesheet" href="{{ asset('assets/plugins/lightbox/glightbox.min.css') }}">
    @endif

    @if (Route::is(['drag-drop', 'clipboard']))
        <!-- Dragula CSS -->
        <link rel="stylesheet" href="{{ asset('assets/plugins/dragula/css/dragula.min.css') }}">
    @endif

    @if (Route::is(['text-editor']))
        <!-- Summernote CSS -->
        <link rel="stylesheet" href="{{ asset('assets/plugins/summernote/summernote-bs4.min.css') }}">
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
        <link rel="stylesheet" href="{{ asset('assets/plugins/summernote/summernote-lite.min.css') }}">
    @endif

    @if (Route::is(['icon-ionic']))
        <!-- Ionic CSS -->
        <link rel="stylesheet" href="{{ asset('assets/plugins/icons/ionic/ionicons.css') }}">
    @endif

    @if (Route::is(['icon-material']))
        <!-- Material CSS -->
        <link rel="stylesheet" href="{{ asset('assets/plugins/material/materialdesignicons.css') }}">
    @endif

    @if (Route::is(['icon-pe7']))
        <!-- Pe7 CSS -->
        <link rel="stylesheet" href="{{ asset('assets/plugins/icons/pe7/pe-icon-7.css') }}">
    @endif

    @if (Route::is(['icon-simpleline']))
        <!-- Simpleline CSS -->
        <link rel="stylesheet" href="{{ asset('assets/plugins/simpleline/simple-line-icons.css') }}">
    @endif

    @if (Route::is(['icon-themify']))
        <!-- Themify CSS -->
        <link rel="stylesheet" href="{{ asset('assets/plugins/icons/themify/themify.css') }}">
    @endif

    @if (Route::is(['icon-weather']))
        <!-- weathericons CSS -->
        <link rel="stylesheet" href="{{ asset('assets/plugins/icons/weather/weathericons.css') }}">
    @endif

    @if (Route::is(['icon-typicon']))
        <!-- typicons CSS typicon-->
        <link rel="stylesheet" href="{{ asset('assets/plugins/icons/typicons/typicons.css') }}">
    @endif

    @if (Route::is(['icon-flag']))
        <!-- flags CSS -->
        <link rel="stylesheet" href="{{ asset('assets/plugins/icons/flags/flags.css') }}">
    @endif

    @if (Route::is(['maps-vector']))
        <!-- Map CSS -->
        <link rel="stylesheet" href="{{ asset('assets/plugins/jvectormap/jquery-jvectormap-2.0.3.css') }}">
    @endif

    @if (Route::is(['chart-c3']))
        <link rel="stylesheet" href="{{ asset('assets/plugins/c3-chart/c3.min.css') }}">
    @endif

    @if (Route::is(['stickynote']))
        <!-- Sticky CSS -->
        <link rel="stylesheet" href="{{ asset('assets/plugins/stickynote/sticky.css') }}">
    @endif

    @if (Route::is(['notification']))
        <link rel="stylesheet" href="{{ asset('assets/plugins/alertify/alertify.min.css') }}">
    @endif

    @if (Route::is(['scrollbar']))
        <link rel="stylesheet" href="{{ asset('assets/plugins/scrollbar/scroll.min.css') }}">
    @endif

    @if (Route::is(['rangeslider']))
        <!-- Rangeslider CSS -->
        <link rel="stylesheet" href="{{ asset('assets/plugins/ion-rangeslider/css/ion.rangeSlider.min.css') }}">
    @endif

    <!-- Main CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">

    @if (!Route::is(['index-two', 'index-three', 'index-four', 'index-five']))
        <!-- Layout JS -->
        <script src="{{ asset('assets/js/layout.js') }}"></script>
    @endif
<link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet"/>
