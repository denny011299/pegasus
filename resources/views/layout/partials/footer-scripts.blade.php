<!-- jQuery -->
<script src="{{ URL::asset('/assets/js/jquery-3.7.1.min.js') }}"></script>

<!-- Bootstrap Core JS -->
<script src="{{ URL::asset('/assets/js/bootstrap.bundle.min.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tagsinput/0.8.0/bootstrap-tagsinput.min.js"></script>

<!-- Feather Icon JS -->
<script src="{{ URL::asset('/assets/js/feather.min.js') }}"></script>
<script src="{{ URL::asset('/assets/js/jspdf.min.js') }}"></script>

<!-- Slimscroll JS -->
<script src="{{ URL::asset('/assets/plugins/slimscroll/jquery.slimscroll.min.js') }}"></script>

@if (!Route::is(['companies']))
    <!-- Datatable JS -->
    <script src="{{ URL::asset('/assets/plugins/datatables/datatables.min.js') }}"></script>
@endif

<!-- select Js -->
<script src="{{ URL::asset('/assets/plugins/select2/js/select2.min.js') }}"></script>

@if (Route::is(['chart-apex', 'dashboard', 'index-five', 'index-four', 'index-three', 'index-two', 'index', '/']))
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

@if (Route::is(['income-report', 'low-stock-report', 'payment-report', 'tax-purchase', 'tax-sales']))
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
    <script src="{{ URL::asset('/assets/js/jquery.dataTables.min.js')}}"></script>

    <script src="{{ URL::asset('/assets/js/dataTables.bootstrap5.min.js')}}"></script>

    <!-- Mobile Input -->
    <script src="{{ URL::asset('/assets/plugins/intltelinput/js/intlTelInput.js')}}"></script>
@endif

<script src="{{ URL::asset('/assets/js/html2canvas.min.js') }}"></script>

@livewireScriptConfig

@if (!Route::is(['index-two', 'index-three', 'index-four', 'index-five']))
    <!-- Theme Settings JS -->
    <script src="{{ URL::asset('/assets/js/theme-settings.js') }}"></script>
    <script src="{{ URL::asset('/assets/js/greedynav.js') }}"></script>
@endif

<!-- Custom JS -->
<script src="{{ URL::asset('/assets/js/script.js') }}"></script>

<script>
       //munculin modal delete
    function showModalDelete(text, button_id) {
        //button id ini, id button ketika dikofrimasi delete
        $("#text-delete").html(text);
        $(".btn-konfirmasi").attr("id", button_id);
        $('#modalDelete').modal("show");
    }
      
    function closeModalDelete() {
        $('#modalDelete').modal("hide");
    }
</script>