<!-- jQuery -->
<script src="{{ URL::asset('/assets/js/jquery-3.7.1.min.js') }}"></script>

{{-- Select2 --}}
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

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
      // Tooltip
    if ($('[data-bs-toggle="tooltip"]').length > 0) {
        var tooltipTriggerList = [].slice.call(
            document.querySelectorAll('[data-bs-toggle="tooltip"]')
        );
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
      feather.replace();
    function notifikasi(simbol,title,deskripsi) {
        Swal.fire({
            icon: simbol,
            title:title,
            text: deskripsi,
        });
    }
       //munculin modal delete
    function showModalDelete(text, button_id) {
        //button id ini, id button ketika dikofrimasi delete
        $("#text-delete").html(text);
        $(".btn-konfirmasi").attr("id", button_id);
        $('#modalDelete').modal("show");
    }
      
    function showModalKonfirmasi(text, button_id) {
        //button id ini, id button ketika dikofrimasi delete
        $("#text-konfirmasi").html(text);
        $(".btn-konfirmasi").attr("id", button_id);
        $('#modalKonfirmasi').modal("show");
    }
      
    $('.btn-cancel').on("click",function(){
        closeModalDelete();
        closeModalConfirm();
    })
    
    function closeModalDelete() {
        $('#modalDelete').modal("hide");
    }

    function closeModalConfirm() {
        $('#modalKonfirmasi').modal("hide");
    }

    $(document).on("input", ".number-only", function() {
        $(this).val($(this).val().replace(/[^0-9]/g, ''));
        if ($(this).val()[0] === '0'&&$(this).val().length>1) {
            $(this).val($(this).val().substring(1));
        }
    })

    $(document).on("keyup", ".nominal_only", function() {
        $(this).val(formatRupiah(convertToAngka($(this).val())));
        console.log($(this).val());
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

    function LoadingButton(id) {
        $(id).html(`
            <div class="text-center h-100">
                <div class="spinner-border" role="status">
                </div>
            </div>   
        `).attr("disabled", true);
    }

    function ResetLoadingButton(id, text = null) {
        $(id).html(`${text? text : 'Save Changes'}`).attr("disabled", false);
        console.log("success");
    }
    
    function autocompleteCity(id, modalParent = null,prov_id=null) {
        //search country dan city
        $(id).select2({
            ajax: {
                url: "/autocompleteCity",
                dataType: "json",
                type: "post",
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
        //search country dan city
        $(id).select2({
            ajax: {
                url: "/autocompleteProv",
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
            placeholder: "Pilih Provinsi",
            closeOnSelect: true,
            allowClear: true,
            width: "100%",
            dropdownParent: modalParent ? $(modalParent) : "",
        });
    }

    function autocompleteArea(id, modalParent = null) {
        //search country dan city
        $(id).select2({
            ajax: {
                url: "/autocompleteArea",
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
            placeholder: "Pilih Wilayah",
            closeOnSelect: true,
            allowClear: true,
            width: "100%",
            dropdownParent: modalParent ? $(modalParent) : "",
        });
    }
    
    function autocompleteDistrict(id, modalParent = null, city_id=null) {
        //search country dan city
        $(id).select2({
            ajax: {
                url: "/autocompleteDistrict",
                dataType: "json",
                type: "post",
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
        //search country dan city
        $(id).select2({
            ajax: {
                url: "/autocompleteCategory",
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
            placeholder: "Pilih Kategori",
            closeOnSelect: true,
            allowClear: true,
            width: "100%",
            dropdownParent: modalParent ? $(modalParent) : "",
        });
    }

    function autocompleteVariant(id, modalParent = null) {
        //search country dan city
        $(id).select2({
            ajax: {
                url: "/autocompleteVariant",
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
            placeholder: "Pilih Variasi",
            closeOnSelect: true,
            allowClear: true,
            width: "100%",
            dropdownParent: modalParent ? $(modalParent) : "",
        });
    }

    function autocompleteUnit(id, modalParent = null) {
        //search country dan city
        $(id).select2({
            ajax: {
                url: "/autocompleteUnit",
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
            placeholder: "Pilih Satuan",
            closeOnSelect: true,
            allowClear: true,
            multiple: true,
            tags: true, // Ini adalah properti utama untuk mengaktifkan tagging
            width: "100%",
            dropdownParent: modalParent ? $(modalParent) : "",
        });
    }

    function autocompleteBom(id, modalParent = null) {
        //search country dan city
        $(id).select2({
            ajax: {
                url: "/autocompleteBom",
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
            placeholder: "Pilih Produk",
            closeOnSelect: true,
            allowClear: true,
            width: "100%",
            dropdownParent: modalParent ? $(modalParent) : "",
        });
    }

    function autocompleteProduct(id, modalParent = null) {
        //search country dan city
        $(id).select2({
            ajax: {
                url: "/autocompleteProduct",
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
            placeholder: "Pilih Produk",
            closeOnSelect: true,
            allowClear: true,
            width: "100%",
            dropdownParent: modalParent ? $(modalParent) : "",
        });
    }

    function autocompleteSupplies(id, modalParent = null) {
        //search country dan city
        $(id).select2({
            ajax: {
                url: "/autocompleteSupplies",
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
            placeholder: "Pilih Bahan Mentah",
            closeOnSelect: true,
            allowClear: true,
            width: "100%",
            dropdownParent: modalParent ? $(modalParent) : "",
        });
    }

    function autocompleteSupplier(id, modalParent = null) {
        //search country dan city
        $(id).select2({
            ajax: {
                url: "/autocompleteSupplier",
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
            placeholder: "Pilih Supplier",
            closeOnSelect: true,
            allowClear: true,
            width: "100%",
            dropdownParent: modalParent ? $(modalParent) : "",
        });
    }

    function autocompleteSuppliesVariant(id, modalParent = null,supplier_id=null) {
        //search country dan city
        $(id).select2({
            ajax: {
                url: "/autocompleteSuppliesVariant",
                dataType: "json",
                type: "post",
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
    function autocompleteSuppliesVariantOnly(id, modalParent = null,supplier_id=null) {
        //search country dan city
        $(id).select2({
            ajax: {
                url: "/autocompleteSuppliesVariantOnly",
                dataType: "json",
                type: "post",
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

    function autocompleteProductVariant(id, modalParent = null) {
        //search country dan city
        $(id).select2({
            ajax: {
                url: "/autocompleteProductVariant",
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
            placeholder: "Pilih Produk",
            closeOnSelect: true,
            allowClear: true,
            width: "100%",
            dropdownParent: modalParent ? $(modalParent) : "",
        });
    }

    function autocompleteProductVariantOnly(id, modalParent = null) {
        //search country dan city
        $(id).select2({
            ajax: {
                url: "/autocompleteProductVariants",
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
            placeholder: "Pilih Produk",
            closeOnSelect: true,
            allowClear: true,
            width: "100%",
            dropdownParent: modalParent ? $(modalParent) : "",
        });
    }

    function autocompleteCustomer(id, modalParent = null) {
        //search country dan city
        $(id).select2({
            ajax: {
                url: "/autocompleteCustomer",
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
            placeholder: "Pilih Armada",
            closeOnSelect: true,
            allowClear: true,
            width: "100%",
            dropdownParent: modalParent ? $(modalParent) : "",
        });
    }
    
    function autocompleteStaffSales(id, modalParent = null) {
        //search country dan city
        $(id).select2({
            ajax: {
                url: "/autocompleteStaffSales",
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
            placeholder: "Pilih Sales",
            closeOnSelect: true,
            allowClear: true,
            width: "100%",
            dropdownParent: modalParent ? $(modalParent) : "",
        });
    }

    function autocompleteCashCategory(id, modalParent = null) {
        //search country dan city
        $(id).select2({
            ajax: {
                url: "/autocompleteCashCategory",
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
            placeholder: "Pilih Kategori Kas",
            closeOnSelect: true,
            allowClear: true,
            width: "100%",
            dropdownParent: modalParent ? $(modalParent) : "",
        });
    }

    
     function autocompleteStaff(id, modalParent = null) {
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
                     console.log(data);
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
         //search country dan city
         $(id).select2({
             ajax: {
                 url: "/autocompleteRole",
                 dataType: "json",
                 type: "post",
                 data: function data(params) {
                     return {
                         "keyword": params.term,
                         '_token': $('meta[name="csrf-token"]').attr('content')
                     };
                 },
                 processResults: function processResults(data) {
                     console.log(data);
                     return {
                         results: $.map(data.data, function(item) {
                             return item;
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
    function autocompleteRekening(id, modalParent = null) {
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
                     console.log(data);
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
                     console.log(data);
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
                     console.log(data);
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
let camRotation = 0;   // rotasi kamera
let photoData = "";
let currentStream = null;
var modeCamera = 1;//1= upload, 2 = savefile
var inputFile = null;
// =========================
// START CAMERA FUNCTION
// =========================
function startCamera() {
    let video = document.getElementById("video");

    // Stop camera old stream
    if (currentStream) {
        currentStream.getTracks().forEach(t => t.stop());
    }

    navigator.mediaDevices.getUserMedia({
    video: { facingMode: { ideal: "environment" } }
    }).catch(() => {
        return navigator.mediaDevices.getUserMedia({
            video: true
        });
    })
    .then(stream => {
        currentStream = stream;
        video.srcObject = stream;
    })
    .catch(function(err) {
        alert("Tidak bisa akses kamera: " + err);
    });
}

// =========================
// WHEN OPEN MODAL
// =========================
$(document).on('click', '.fotoProduksi', function() {
    modeCamera=1;
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
$(document).on("click", "#rotateCameraBtn", function () {
    camRotation = (camRotation + 90) % 360;

    $("#video")
        .removeClass("rot90 rot180 rot270")
        .addClass(
            camRotation === 90 ? "rot90" :
            camRotation === 180 ? "rot180" :
            camRotation === 270 ? "rot270" : ""
        );
          adjustModalHeight();
});

// =========================
// CAPTURE PHOTO
// =========================
$(document).on("click", "#captureBtn", function () {
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
});

// =========================
// RETAKE
// =========================
$(document).on("click", "#retakeBtn", function () {
    $("#preview-box").hide();
    $("#camera").show();

    camRotation = 0;
    $("#video").removeClass("rot90 rot180 rot270");

    startCamera();
});

// =========================
// UPLOAD
// =========================
$(document).on("click", "#uploadBtn", function () {
    if(modeCamera==1){
        $.ajax({
            url: "/uploadPhotoProduksi",
            type: "POST",
            data: JSON.stringify({ photo: photoData }),
            contentType: "application/json",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
            },
            success: function (response) {
                notifikasi("success", "Sukses", "Foto berhasil diupload");
                $('#modalPhoto').modal('hide');

                if (currentStream) currentStream.getTracks().forEach(t => t.stop());
            },
            error: function () {
                notifikasi("error", "Gagal", "Foto gagal diupload");
            }
        });
    }
    else if(modeCamera==3){
        var ipt = JSON.parse($(inputFile).val()||"[]");
        ipt.push(photoData);
        $(inputFile).val(JSON.stringify(ipt));
        $("#add_sales_order").modal("show");
        $('#modalPhoto').modal('hide');
    }
    else if(modeCamera==4){
        var ipt = JSON.parse($(inputFile).val()||"[]");
        ipt.push(photoData);
        $(inputFile).val(JSON.stringify(ipt));
        $("#add_purchase_order").modal("show");
        $('#modalPhoto').modal('hide');
    }
    else{
        $(inputFile).val(photoData);
        $("#add-product-issues").modal("show");
        $('#modalPhoto').modal('hide');
    }

});

function adjustModalHeight() {
    let video = document.getElementById("video");
        if (!video) return;

        let modalDialog = $("#modalPhoto .modal-body");
    
        // Tinggi modal mengikuti tinggi video
        modalDialog.css("height", (video.clientHeight/4)+ "px");
}
</script>
