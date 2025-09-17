    var mode=1;
    var tablePr, tableDn, tableInv, tableRcp, tablePrModal;
    autocompleteSupplier("#po_supplier",null);
    $(document).ready(function(){
        inisialisasi();
        refresh();
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
                { data: "pr_qty" },
                { data: "pr_buy_price" },
                { data: "pr_discount" },
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
                searchPlaceholder: "Cari Pengiriman",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "pod_id" },
                { data: "date" },
                { data: "pod_receiver" },
                { data: "pod_address" },
                { data: "pod_phone" },
                { data: "status" },
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
                { data: "poi_code" },
                { data: "poi_total" },
                { data: "status" },
                { data: "action", class: "d-flex align-items-center" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });

        tableRcp = $('#tableReceipt').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
            searching: false,
            autoWidth: false,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Cari Penerimaan Barang",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "date" },
                { data: "por_ref" },
                { data: "por_receiver" },
                { data: "status" },
                { data: "action", class: "d-flex align-items-center" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });
    }

    function refresh() {
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
                    e[i].pr_buy_price = 100000;
                    e[i].pr_price = 80000;
                    e[i].pr_discount = 20000;
                    e[i].pr_subtotal = 160000;
                }

                tablePr.rows.add(e).draw();
                feather.replace(); // Biar icon feather muncul lagi
            },
            error: function (err) {
                console.error("Gagal load:", err);
            }
        });

        $.ajax({
            url: "/getPoDelivery",
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                console.log(e);
                tableDn.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].date = moment(e[i].pod_date).format('D MMM YYYY');
                    if (e[i].pod_status == 1){
                        e[i].status = `<span class="badge bg-warning" style="font-size: 12px">Tertunda</span>`;
                    } else if (e[i].pod_status == 2){
                        e[i].status = `<span class="badge bg-success" style="font-size: 12px">Terkirim</span>`;
                    }
                    e[i].action = `
                        <a class="me-2 btn-action-icon p-2 btn_print_dn" data-id="${e[i].pod_id}" data-bs-target="#print-sales">
                            <i class="fe fe-file"></i>
                        </a>
                        <a class="me-2 btn-action-icon p-2 btn_edit_dn" data-id="${e[i].pod_id}" data-bs-target="#edit-sales">
                            <i class="fe fe-edit"></i>
                        </a>
                        <a class="p-2 btn-action-icon btn_delete_dn" data-id="${e[i].pod_id}" href="javascript:void(0);">
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
            url: "/getPoInvoice",
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                console.log(e);
                tableInv.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].date = moment(e[i].poi_date).format('D MMM YYYY');
                    if (e[i].poi_status == 1){
                        e[i].status = `<span class="badge bg-warning" style="font-size: 12px">Tertunda</span>`;
                    } else if (e[i].poi_status == 2){
                        e[i].status = `<span class="badge bg-success" style="font-size: 12px">Terbayar</span>`;
                    }
                    e[i].action = `
                        <a class="me-2 btn-action-icon p-2 btn_print_inv" data-id="${e[i].poi_id}" data-bs-target="#print-sales">
                            <i class="fe fe-file"></i>
                        </a>
                        <a class="me-2 btn-action-icon p-2 btn_edit_inv" data-id="${e[i].poi_id}" data-bs-target="#edit-sales">
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

        $.ajax({
            url: "/getPoReceipt",
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                console.log(e);
                tableRcp.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].date = moment(e[i].por_date).format('D MMM YYYY');
                    if (e[i].por_status == 1){
                        e[i].status = `<span class="badge bg-warning" style="font-size: 12px">Sedang Dikirim</span>`;
                    } else if (e[i].por_status == 2){
                        e[i].status = `<span class="badge bg-success" style="font-size: 12px">Diterima</span>`;
                    }
                    e[i].action = `
                        <a class="me-2 btn-action-icon p-2 btn_edit_rcp" data-id="${e[i].por_id}" data-bs-target="#edit-sales">
                            <i class="fe fe-edit"></i>
                        </a>
                        <a class="p-2 btn-action-icon btn_delete_rcp" data-id="${e[i].pod_id}" href="javascript:void(0);">
                            <i class="fe fe-trash-2"></i>
                        </a>
                    `;
                }

                tableRcp.rows.add(e).draw();
                feather.replace(); // Biar icon feather muncul lagi
            },
            error: function (err) {
                console.error("Gagal load:", err);
            }
        });
    }

    $(document).on('click', '.btnBack', function(){
        window.open('/purchaseOrder', '_self');
    })

    $(document).on('click', '.btnAddDn', function(){
        $('#add_purchase_delivery .modal-title').html("Tambah Catatan Pengiriman");
        $('#add_purchase_delivery input').val("");
        $('.is-invalid').removeClass('is-invalid');
        tablePurchaseDelivery();
        refreshTableProduct();
        $('#add_purchase_delivery').modal("show");
    })

    $(document).on('click', '.btnAddInv', function(){
        $('#add_purchase_invoice .modal-title').html("Tambah Faktur");
        $('#add_purchase_invoice input').val("");
        $('.is-invalid').removeClass('is-invalid');
        $('#add_purchase_invoice').modal("show");
    })

    $(document).on('click', '.btnAddRcp', function(){
        $('#add_purchase_receipt .modal-title').html("Tambah Penerimaan Barang");
        $('#add_purchase_receipt input').val("");
        $('.is-invalid').removeClass('is-invalid');
        $('#add_purchase_receipt').modal("show");
    })

    function tablePurchaseDelivery(){
        if ($.fn.DataTable.isDataTable('#tablePurchaseDelivery')) {
            tablePrModal = $('#tablePurchaseDelivery').DataTable();
            return;
        }
        tablePrModal = $('#tablePurchaseDelivery').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
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
                console.error("Gagal load:", err);
            }
        });
    }

    $(document).on('click', '.btn_edit_dn', function(){
        var data = $('#tableDelivery').DataTable().row($(this).parents('tr')).data();
        mode = 2;
        $('#add_purchase_delivery .modal-title').html("Update Catatan Pengiriman");
        $('#add_purchase_delivery input').val("");
        $('.is-invalid').removeClass('is-invalid');
        tablePurchaseDelivery();
        refreshTableProduct();
        $('.btn-save').html('Simpan perubahan');
        $('#add_purchase_delivery').modal("show");
    })

    $(document).on('click', '.btn_delete_dn', function(){
        var data = $('#tableDelivery').DataTable().row($(this).parents('tr')).data();
        $('#modalDelete').modal("show");
    })

    $(document).on('click', '.btn_edit_inv', function(){
        var data = $('#tableInvoice').DataTable().row($(this).parents('tr')).data();
        mode = 2;
        $('#add_purchase_invoice .modal-title').html("Update Faktur");
        $('#add_purchase_invoice input').val("");
        $('.is-invalid').removeClass('is-invalid');
        $('.btn-save').html('Simpan perubahan');
        $('#add_purchase_invoice').modal("show");
    })

    $(document).on('click', '.btn_edit_rcp', function(){
        var data = $('#tableReceipt').DataTable().row($(this).parents('tr')).data();
        mode = 2;
        $('#add_purchase_receipt .modal-title').html("Update Penerimaan Barang");
        $('#add_purchase_receipt input').val("");
        $('.is-invalid').removeClass('is-invalid');
        $('.btn-save').html('Simpan perubahan');
        $('#add_purchase_receipt').modal("show");
    })

    $(document).on('click', '.btn_delete_rcp', function(){
        var data = $('#tableReceipt').DataTable().row($(this).parents('tr')).data();
        $('#modalDelete').modal("show");
    })
