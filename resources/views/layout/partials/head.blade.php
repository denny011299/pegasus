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
    
    <!-- Custom Premium Theme CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/custom-premium.css') }}?v={{ time() }}">

    <style>
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

        /* Global Table Styling (Aesthetic) */
        .table-responsive {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow-x: auto;
        }
        
        .table {
            margin-bottom: 0 !important;
        }

        .table thead {
            background: #f1f5f9 !important;
            border-bottom: 1px solid #e2e8f0 !important;
        }

        .table thead th, .table thead td {
            background-color: #f1f5f9 !important;
            color: #64748b !important;
            font-size: 11px !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: .4px !important;
            padding: 14px 24px !important;
            border-bottom: none !important;
        }

        .table tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.2s ease;
        }

        .table tbody td {
            padding: 16px 24px !important;
            vertical-align: middle;
            color: #475569;
            font-size: 13px;
        }

        .table tbody tr:hover {
            background-color: #f8fafc !important;
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
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}?v={{ time() }}">

    <style>
        /* Select2 single — perbaikan tampilan autocomplete */
        .select2-container {
            width: 100% !important;
            max-width: 100%;
            box-sizing: border-box;
        }

        .select2-container--default .select2-selection--single {
            position: relative;
            max-width: 100%;
            box-sizing: border-box;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            display: block;
            line-height: 41px;
            padding-left: 15px;
            padding-right: 30px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered:has(.select2-selection__clear) {
            padding-right: 50px;
        }

        .select2-container--default .select2-selection--single .select2-selection__clear {
            position: absolute;
            right: 28px;
            top: 50%;
            transform: translateY(-50%);
            float: none;
            margin: 0;
            padding: 0;
            line-height: 1;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            z-index: 1;
        }

        /* Datatable Skeleton Custom Styles */
        .dt-pending table,
        .dt-pending .dataTables_wrapper {
            display: none !important;
        }
        .dt-ready .dt-skeleton {
            display: none !important;
        }
        .dt-skeleton {
            width: 100%;
            overflow: hidden;
            border-radius: 8px;
        }
        .dt-skeleton-head {
            display: grid;
            gap: 0;
            background: linear-gradient(90deg, #eff6ff 0%, #e0f2fe 100%);
            border-bottom: 2px solid #bfdbfe;
            border-radius: 8px 8px 0 0;
            padding: 16px 25px;
            margin-bottom: 0;
        }
        .dt-skeleton-head span {
            height: 12px;
            border-radius: 6px;
            background: rgba(30, 64, 175, 0.15);
        }
        .dt-skeleton-body {
            padding: 0 25px 8px;
        }
        .dt-skeleton-row {
            display: grid;
            gap: 0;
            align-items: center;
            min-height: 65px;
            border-bottom: 1px solid #f1f5f9;
        }
        .dt-skeleton-row span {
            background: #e2e8f0;
            background-image: linear-gradient(90deg, #e2e8f0 0%, #f1f5f9 40%, #e2e8f0 80%);
            background-size: 200% 100%;
            animation: dt-shimmer 1.5s ease-in-out infinite;
            display: inline-block;
        }
        .skel-icon { width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0; }
        .skel-avatar { width: 24px; height: 24px; border-radius: 50%; flex-shrink: 0; }
        .skel-badge { height: 26px; border-radius: 20px; }
        .skel-btn { width: 32px; height: 32px; border-radius: 8px; }
        .skel-text { height: 14px; border-radius: 6px; }

        .dt-skeleton-row:nth-child(2) span { animation-delay: 0.1s; }
        .dt-skeleton-row:nth-child(3) span { animation-delay: 0.2s; }
        .dt-skeleton-row:nth-child(4) span { animation-delay: 0.3s; }
        .dt-skeleton-row:nth-child(5) span { animation-delay: 0.4s; }

        @keyframes dt-shimmer {
            0% { background-position: 100% 0; }
            100% { background-position: -100% 0; }
        }

        /* Enhanced DataTable Processing Overlay & Skeleton Styles */
        div.dataTables_wrapper div.dataTables_processing {
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) !important;
            margin-top: 0 !important;
            margin-left: 0 !important;
            padding: 0 !important;
            border: none !important;
            background: transparent !important;
            box-shadow: none !important;
            z-index: 1050 !important;
        }

        .dt-skeleton-overlay {
            animation: fadeInOverlay 0.2s ease-in-out;
        }

        @keyframes fadeInOverlay {
            from { opacity: 0; transform: translateY(4px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>

    @if (!Route::is(['index-two', 'index-three', 'index-four', 'index-five']))
        <!-- Layout JS -->
        <script src="{{ asset('assets/js/layout.js') }}"></script>
    @endif
<link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet"/>
