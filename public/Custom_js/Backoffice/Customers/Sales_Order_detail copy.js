    var mode=1;
    var tablePr, tableDn, tableInv, tablePrModal;
    $(document).ready(function(){
        inisialisasi();
        refreshSalesOrderDetail();
    });
    
    function inisialisasi() {
        tablePr = $('#tableProduct').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
            searching: false,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Cari Produk",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "pr_name" },
                { data: "variant" },
                { data: "pr_sku" },
                { data: "pr_qty" },
                { data: "pr_price" },
                { data: "pr_subtotal" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });

        tableDn = $('#tableDelivery').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
            searching: false,
            autoWidth: false,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Cari Pemesanan Penjualan",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "sod_id" },
                { data: "date" },
                { data: "sod_receiver" },
                { data: "sod_address" },
                { data: "action", class: "d-flex align-items-center" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });

        tableInv = $('#tableInvoice').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
            searching: false,
            autoWidth: false,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Cari Faktur",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "date" },
                { data: "due" },
                { data: "soi_code" },
                { data: "soi_status" },
                { data: "soi_total" },
                { data: "action", class: "d-flex align-items-center" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });
    }

    function refreshSalesOrderDetail() {
        $.ajax({
            url: "/getProduct",
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                console.log(e);
                tablePr.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].pr_qty = 2;
                    e[i].pr_price = 100000;
                    e[i].pr_subtotal = 200000;

                    e[i].variant = "";
                    JSON.parse(e[i].pr_variant).forEach((element,index) => {
                         e[i].variant += element;
                         if(index< JSON.parse(e[i].pr_variant).length-1){
                            e[i].variant += ", ";
                         }
                    });
                }

                tablePr.rows.add(e).draw();
                feather.replace(); // Biar icon feather muncul lagi
            },
            error: function (err) {
                console.error("Gagal load pemesanan penjualan:", err);
            }
        });

        $.ajax({
            url: "/getSoDelivery",
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                console.log(e);
                tableDn.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].date = moment(e[i].sod_date).format('D MMM YYYY');
                    e[i].action = `
                        <a class="me-2 btn-action-icon p-2 btn_print_dn" data-id="${e[i].sod_id}" data-bs-target="#print-sales">
                            <i class="fe fe-file"></i>
                        </a>
                        <a class="me-2 btn-action-icon p-2 btn_edit_dn" data-id="${e[i].sod_id}" data-bs-target="#edit-sales">
                            <i class="fe fe-edit"></i>
                        </a>
                        <a class="p-2 btn-action-icon btn_delete_dn" data-id="${e[i].sod_id}" href="javascript:void(0);">
                            <i class="fe fe-trash-2"></i>
                        </a>
                    `;
                }

                tableDn.rows.add(e).draw();
                feather.replace(); // Biar icon feather muncul lagi
            },
            error: function (err) {
                console.error("Gagal load:", err);
            }
        });

        $.ajax({
            url: "/getSoInvoice",
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                console.log(e);
                tableInv.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].date = moment(e[i].soi_date).format('D MMM YYYY');
                    e[i].due = moment(e[i].soi_due).format('D MMM YYYY');
                    e[i].action = `
                        <a class="me-2 btn-action-icon p-2 btn_view_inv" data-id="${e[i].soi_id}" data-bs-target="#view-sales">
                            <i class="fe fe-file"></i>
                        </a>
                        <a class="me-2 btn-action-icon p-2 btn_edit_inv" data-id="${e[i].soi_id}" data-bs-target="#edit-sales">
                            <i class="fe fe-edit"></i>
                        </a>
                    `;
                }

                tableInv.rows.add(e).draw();
                feather.replace(); // Biar icon feather muncul lagi
            },
            error: function (err) {
                console.error("Gagal load:", err);
            }
        });
    }

    $(document).on('click', '.btnBack', function(){
        window.open('/salesOrder', '_self');
    })

    $(document).on('click', '.btnAddDn', function(){
        $('#add_sales_delivery .modal-title').html("Tambah Catatan Pengiriman");
        $('#add_sales_delivery input').val("");
        $('.is-invalid').removeClass('is-invalid');
        tableSalesDelivery();
        refreshTableProduct();
        $('#add_sales_delivery').modal("show");
    })

    $(document).on('click', '.btnAddInv', function(){
        $('#add_sales_invoice .modal-title').html("Tambah Faktur");
        $('#add_sales_invoice input').val("");
        $('.is-invalid').removeClass('is-invalid');
        $('#add_sales_invoice').modal("show");
    })

    function tableSalesDelivery(){
        if ($.fn.DataTable.isDataTable('#tableSalesDelivery')) {
            tablePrModal = $('#tableSalesDelivery').DataTable();
            return;
        }
        tablePrModal = $('#tableSalesDelivery').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Search Product",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "pr_name" },
                { data: "pr_sku" },
                { data: "pr_category" },
                { data: "pr_qty" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });
    }

    function refreshTableProduct(){
        $.ajax({
            url: "/getProduct",
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                console.log(e);
                tablePrModal.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].pr_qty = 2;
                }

                tablePrModal.rows.add(e).draw();
                feather.replace(); // Biar icon feather muncul lagi
            },
            error: function (err) {
                console.error("Gagal load kategori:", err);
            }
        });
    }

    $(document).on('click', '.btn_edit_dn', function(){
        var data = $('#tableDelivery').DataTable().row($(this).parents('tr')).data();
        mode = 2;
        $('#add_sales_delivery .modal-title').html("Update Catatan Pengiriman");
        $('#add_sales_delivery input').val("");
        $('.is-invalid').removeClass('is-invalid');
        tableSalesDelivery();
        refreshTableProduct();
        $('.btn-save').html('Simpan perubahan');
        $('#add_sales_delivery').modal("show");
    })

    $(document).on('click', '.btn_delete_dn', function(){
        var data = $('#tableDelivery').DataTable().row($(this).parents('tr')).data();
        $('#modalDelete').modal("show");
    })

    $(document).on('click', '.btn_edit_inv', function(){
        var data = $('#tableInvoice').DataTable().row($(this).parents('tr')).data();
        mode = 2;
        $('#add_sales_invoice .modal-title').html("Update Faktur");
        $('#add_sales_invoice input').val("");
        $('.is-invalid').removeClass('is-invalid');
        $('.btn-save').html('Simpan perubahan');
        $('#add_sales_invoice').modal("show");
    })