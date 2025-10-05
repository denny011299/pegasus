    var mode=1;
    var tablePr, tableDn, tableInv, tableRcp, tablePrModal;
    let detail_delivery = []

    autocompleteSupplier("#po_supplier",null);
    $(document).ready(function(){
        inisialisasi();
        refresh();
        refreshSummary();
    });
    
    function inisialisasi() {
        tablePr = $('#tableSupplies').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
            searching: false,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Cari Supplies",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "pod_sku" },
                { data: "pod_nama" },
                { data: "qty",class:"text-center"   },
                { data: "pod_harga_text",class:"text-end"  },
                { data: "pod_subtotal_text",class:"text-end subtotal" },
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
                { data: "pdo_number" },
                { data: "date" },
                { data: "pdo_receiver" },
                { data: "pdo_address" },
                { data: "pdo_phone" },
                { data: "status_text" },
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
                { data: "date_due_date" },
                { data: "poi_code" },
                { data: "poi_total_text" },
                { data: "status_text" },
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
        tablePr.clear().draw(); 
        // Manipulasi data sebelum masuk ke tabel

        data.items.forEach((element, index) => {
            element.qty = `
                <input type="number" class="form-control text-center qtySummary" data-price="${element.pod_harga}" index="${index}" value="${element.pod_qty}">
            `;
            element.pod_harga_text = formatRupiah(element.pod_harga,"Rp.");
            element.pod_subtotal_text = formatRupiah(element.pod_subtotal,"Rp.");
        });

        tablePr.rows.add(data.items).draw();
        feather.replace(); // Biar icon feather muncul lagi

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
                    if (e[i].status == 1){
                        e[i].status_text = `<span class="badge bg-warning" style="font-size: 12px">Dibuat</span>`;
                    } else if (e[i].pod_status == 2){
                        e[i].status_text = `<span class="badge bg-success" style="font-size: 12px">Diterima</span>`;
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
        refreshInvoice();
    }
    
    function refreshInvoice() {
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
                    e[i].date_due_date = moment(e[i].poi_due).format('D MMM YYYY');
                    e[i].poi_total_text = formatRupiah(e[i].poi_total,"Rp.");
                    if (e[i].status == 1){
                        e[i].status_text = `<span class="badge bg-warning" style="font-size: 12px">Belum Terbayar</span>`;
                    } else if (e[i].status == 2){
                        e[i].status_text = `<span class="badge bg-success" style="font-size: 12px">Terbayar</span>`;
                    }
                    e[i].action = `
                        <a class="me-2 btn-action-icon p-2 btn_edit_invoice" >
                            <i class="fe fe-edit"></i>
                        </a>
                        <a class="me-2 btn-action-icon p-2 btn_delete_invoice">
                            <i class="fe fe-trash"></i>
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
        window.open('/purchaseOrder', '_self');
    })

    $(document).on('click', '.btnAddDn', function(){
        mode=1;
        $('#add_purchase_delivery .modal-title').html("Tambah Catatan Pengiriman");
        $('#add_purchase_delivery .form-control').val("");
        $('.is-invalid').removeClass('is-invalid');
        tablePurchaseDelivery();
        refreshTableProduct(data.items);
        $('#add_purchase_delivery').modal("show");
    })

    $(document).on('click', '.btnAddInv', function(){
        mode=1;
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
            autoWidth: false, 
            searching: false, 
            language: { 
                search: ' ', 
                sLengthMenu: '_MENU_', 
                searchPlaceholder: "Cari Supplies", 
                info: "_START_ - _END_ of _TOTAL_ items", 
                paginate: { 
                    next: ' <i class=" fa fa-angle-right"></i>', 
                    previous: '<i class="fa fa-angle-left"></i> ' 
                }, 
            }, 
            columns: [ 
                { data: "name", width: "40%" }, 
                { data: "sku", width: "40%" }, 
                { data: "stock", width: "20%" }, 
            ], 
            initComplete: (settings, json) => { 
                $('.dataTables_filter').appendTo('#tableSearch'); 
                $('.dataTables_filter').appendTo('.search-input'); 
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> '); 
            }, 
        }); 
    }

    function refreshTableProduct(e){
        console.log(e);
        tablePrModal.clear().draw();
        // Manipulasi data sebelum dimasukkan ke tabel
        for (let i = 0; i < e.length; i++) {
            e[i].stock = `<input type="number" class="form-control qtyDn" index="${i + 1}" value="${e[i].pod_qty || e[i].pdod_qty}">`;
            e[i].name = e[i].pod_nama || `${e[i].supplies_name} ${e[i].supplies_variant_name}`;
            console.log(e[i])
            e[i].sku = e[i].pod_sku || e[i].pdod_sku;
        }

        tablePrModal.rows.add(e).draw();
        feather.replace(); // biar icon feather muncul lagi
    }

    // Refresh Summary & Input qty
    $(document).on('blur', '.qtySummary', function () {
        const index = $(this).data('index');
        let qty = parseInt($(this).val());
        let price = parseInt($(this).data('price'));
        let subtotal = qty * price;
        console.log($(this).closest('tr').find('.subtotal'))
        $(this).closest('tr').find('.subtotal').html(formatRupiah(subtotal, 'Rp '));
        updateTotal();
    });

    function updateTotal() {
        let total = 0;
        $(".subtotal").each(function () {
            total += parseInt($(this).text().replace(/[^0-9]/g, "")) || 0;
            console.log(total)
        });
        $("#value_total").html(formatRupiah(total, 'Rp '));
        grandTotal()
    }

    function grandTotal(){
        var total = convertToAngka($('#value_total').html());
        var ppn = convertToAngka($('#value_ppn').html());
        var discount = convertToAngka($('#value_discount').html());
        var cost = convertToAngka($('#value_cost').html());
        var grand = total + ppn - discount + cost;
        $('#value_grand').html(`Rp ${formatRupiah(grand)}`)
    }

    $(document).on('click', '.save-qty', function(){
        LoadingButton(this);
        $(".is-invalid").removeClass("is-invalid");
        console.log(data)
        var url = "/updatePurchaseOrderDetail";
        var valid = 1;
        $(".qtySummary").each(function () {
            if (
                $(this).val() == null ||
                $(this).val() == "null" ||
                $(this).val() == ""
            ) {
                valid = -1;
                $(this).addClass("is-invalid");
            }
        });

        if (valid == -1) {
            notifikasi(
                "error",
                "Gagal Insert",
                "Silahkan cek kembali inputan anda"
            );
            ResetLoadingButton('.save-qty', 'Save changes');
            return false;
        }

        $(".qtySummary").each(function() {
            let qty = $(this).val();
            var search = $('#tableSupplies').DataTable().row($(this).parents('tr')).data()
            pod_id = search.pod_id;

            let item = data.items.find(i => i.pod_id == pod_id);
            if (item) {
                item.pod_qty = qty;
                item.pod_subtotal = parseInt(item.pod_harga) * parseInt(qty);
            }
        });
        console.log(data.items);
        param = {
            po_detail: JSON.stringify(data.items),
            _token:token
        };

        LoadingButton($(this));
        $.ajax({
            url:url,
            data: param,
            method:"post",
            headers: {
                'X-CSRF-TOKEN': token
            },
            success:function(e){      
                ResetLoadingButton(".save-qty", 'Simpan perubahan');      
                notifikasi('success', 'Berhasil Update', 'Berhasil Update Qty');
                refresh();
            },
            error:function(e){
                ResetLoadingButton(".save-qty", 'Simpan perubahan');
                console.log(e);
            }
        });
    })

    function insertDeliveryDetail(){
        $('#tablePurchaseDelivery tbody tr').each(function() {
            var dataDelivery = $('#tablePurchaseDelivery').DataTable().row(this).data(); // pakai this saja
            if (mode == 1) dataDelivery = dataDelivery.supplies_variant;

            let qty = parseInt($(this).find('.qtyDn').val()) || 0;

            let item = {
                supplies_variant_id: dataDelivery.supplies_variant_id,
                pdod_sku: dataDelivery.supplies_variant_sku || dataDelivery.pdod_sku,
                pdod_qty: qty
            };

            detail_delivery.push(item);
        });
    };
    
    $(document).on('click', '.btn-save-delivery', function(){
        LoadingButton(this);
        $('.is-invalid').removeClass('is-invalid');
        var url ="/insertPoDelivery";
        var valid=1;

        $("#add_purchase_delivery .fill").each(function(){
            if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                valid=-1;
                $(this).addClass('is-invalid');
            }
        });

        if(valid==-1){
            notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
            ResetLoadingButton('.btn-save-delivery', 'Simpan perubahan');
            return false;
        };

        insertDeliveryDetail();

        param = {
            pdo_receiver: $('#pdo_receiver').val(),
            pdo_date: $('#pdo_date').val(),
            pdo_phone: $('#pdo_phone').val(),
            pdo_address: $('#pdo_address').val(),
            pdo_desc: $('#pdo_desc').val(),
            pdo_detail: JSON.stringify(detail_delivery),
            _token: token
        };

        if(mode==2){
            url="/updatePoDelivery";
            param.pdo_id = $('#add_purchase_delivery').attr("pdo_id");
        }

        LoadingButton($(this));
        $.ajax({
            url:url,
            data: param,
            method:"post",
            headers: {
                'X-CSRF-TOKEN': token
            },
            success:function(e){      
                ResetLoadingButton(".btn-save-delivery", 'Simpan perubahan');      
                afterInsertDelivery();
            },
            error:function(e){
                ResetLoadingButton(".btn-save-delivery", 'Simpan perubahan');
                console.log(e);
            }
        });
    })

    function afterInsertDelivery() {
        $(".modal").modal("hide");
        if(mode==1)notifikasi('success', "Berhasil Insert", "Berhasil Tambah Catatan Pengiriman");
        else if(mode==2)notifikasi('success', "Berhasil Update", "Berhasil Update Catatan Pengiriman");
        refresh();
    }

    $(document).on('click', '.btn_edit_dn', function(){
        var data = $('#tableDelivery').DataTable().row($(this).parents('tr')).data();
        console.log(data);
        mode = 2;
        $('#add_purchase_delivery .modal-title').html("Update Catatan Pengiriman");
        $('#add_purchase_delivery input').val("");
        $('.is-invalid').removeClass('is-invalid');

        $('#pdo_receiver').val(data.pdo_receiver);
        $('#pdo_date').val(data.pdo_date);
        $('#pdo_phone').val(data.pdo_phone);
        $('#pdo_address').val(data.pdo_address);
        $('#pdo_desc').val(data.pdo_desc);

        tablePurchaseDelivery();
        refreshTableProduct(data.items);
        
        $('.btn-save').html('Simpan perubahan');
        $('#add_purchase_delivery').modal("show");
        $('#add_purchase_delivery').attr("pdo_id", data.pdo_id);
    })

    $(document).on('click', '.btn_delete_dn', function(){
        var data = $('#tableDelivery').DataTable().row($(this).parents('tr')).data();
        showModalDelete("Apakah yakin ingin menghapus catatan pengiriman ini?","btn-delete-delivery");
        $('#btn-delete-delivery').attr("pdo_id", data.pdo_id);
    })

    $(document).on("click","#btn-delete-delivery",function(){
        $.ajax({
            url:"/deletePoDelivery",
            data:{
                pdo_id:$('#btn-delete-delivery').attr('pdo_id'),
                _token:token
            },
            method:"post",
            success:function(e){
                $('.modal').modal("hide");
                refresh();
                notifikasi('success', "Berhasil Delete", "Berhasil delete catatan pengiriman");
                
            },
            error:function(e){
                console.log(e);
            }
        });
    });

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


    function refreshSummary() {
        var total = 0;
        data.items.forEach(item => {
            console.log(item);
            
            total+=(item.pod_harga*item.pod_qty);
        });
        $('#value_total').html(formatRupiah(total,"Rp."))
        var diskon = data.po_discount;
        total -= diskon;
        var ppn = data.po_ppn;
        var cost = data.po_cost;
        total +=ppn +cost;
        grand = total;
        console.log(grand);
        
        $('#value_discount').html(formatRupiah(diskon,"Rp."))
        $('#value_ppn').html(formatRupiah(ppn,"Rp."))
        $('#value_cost').html(formatRupiah(cost,"Rp."))
        $('#value_grand').html(formatRupiah(grand,"Rp."))
    }


    
$(document).on("click", ".btn-save-invoice", function () {
    LoadingButton(this);
    $(".is-invalid").removeClass("is-invalid");
    var url = "/insertInvoicePO";
    var valid = 1;
    $("#add_purchase_invoice .fill").each(function () {
        if (
            $(this).val() == null ||
            $(this).val() == "null" ||
            $(this).val() == ""
        ) {
            valid = -1;
            $(this).addClass("is-invalid");
        }
    });

    if (valid == -1) {
        notifikasi(
            "error",
            "Gagal Insert",
            "Silahkan cek kembali inputan anda"
        );
        ResetLoadingButton('.btn-save', 'Save changes');
        return false;
    }

    param = {
        po_id: data.po_id,
        poi_date: $("#poi_date").val(),
        poi_code: $("#poi_code").val(),
        poi_due: $("#poi_due").val(),
        poi_total: convertToAngka($("#poi_total").val()),
        _token: token,
    };
    console.log(param);
    LoadingButton($(this));

    if (mode == 2) {
        url = "/updateInvoicePO";
        param.poi_id = $("#add_purchase_invoice").attr("poi_id");
    }

    $.ajax({
        url: url,
        data: param,
        method: "post",
        headers: {
            "X-CSRF-TOKEN": token,
        },
        success: function (e) {
            $(".modal").modal("hide");
            refreshInvoice();
            if (mode == 1){
                notifikasi(
                    "success",
                    "Berhasil Insert",
                    "Berhasil Data Ditambahkan"
                );
            }
            else if (mode == 2){
                notifikasi(
                    "success",
                    "Berhasil Update",
                    "Berhasil Data Diupdate"
                );
            }

        },
        error: function (e) {
            console.log(e);
        },
    });
});
    //edit invoice
    $(document).on("click",".btn_edit_invoice",function(){
        mode=2;
        var data = $('#tableInvoice').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        showModalDelete("Apakah yakin ingin mengahapus resep ini?","btn-delete-invoice");
        $('#poi_total').val(formatRupiah(data.poi_total))
        $('#poi_due').val(data.poi_due)
        $('#poi_date').val(data.poi_date)
        $('#poi_code').val(data.poi_code)
        $('#add_purchase_invoice').attr("poi_id", data.poi_id);
        $('#add_purchase_invoice').modal("show");
    });
 //delete invoice
    $(document).on("click",".btn_delete_invoice",function(){
        var data = $('#tableInvoice').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        showModalDelete("Apakah yakin ingin mengahapus resep ini?","btn-delete-invoice");
        $('#btn-delete-invoice').attr("poi_id", data.poi_id);

    });
    
    $(document).on("click","#btn-delete-invoice",function(){
        $.ajax({
            url:"/deleteInvoicePO",
            data:{
                poi_id:$('#btn-delete-invoice').attr('poi_id'),
                _token:token
            },
            method:"post",
            success:function(e){
                $('.modal').modal("hide");
                refreshInvoice();
                notifikasi('success', "Berhasil Delete", "Berhasil delete invoice");
            },
            error:function(e){
                console.log(e);
            }
        });
    });
    
    $(document).on("click","#btnAddTerima",function(){
        $('#modalTerima').modal("show");
    });