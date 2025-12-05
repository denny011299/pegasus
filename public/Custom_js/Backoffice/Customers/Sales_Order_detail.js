    var mode=1;
    var tablePr, tableDn, tableInv, tablePrModal;
    let detail_delivery = []
    // autocompleteStaff("#sdo_receiver",null);
    autocompleteCustomer("#so_customer",null);
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
                searchPlaceholder: "Cari Produk",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "sod_sku" },
                { data: "sod_nama" },
                { data: "qty",class:"text-center"   },
                { data: "sod_harga_text",class:"text-end"  },
                { data: "sod_subtotal_text",class:"text-end subtotal" },
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
                { data: "sdo_number" },
                { data: "date" },
                { data: "sdo_receiver" },
                { data: "sdo_phone" },
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
                { data: "soi_code" },
                { data: "soi_total_text" },
                { data: "status_text" },
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
        console.log(data);

        data.items.forEach((element, index) => {
            element.qty = `
                <input type="number" class="form-control text-center qtySummary" data-price="${element.sod_harga}" index="${index}" value="${element.sod_qty}" min="0">
            `;
            element.sod_harga_text = formatRupiah(element.sod_harga,"Rp.");
            element.sod_subtotal_text = formatRupiah(element.sod_subtotal,"Rp.");
        });

        tablePr.rows.add(data.items).draw();
        feather.replace(); // Biar icon feather muncul lagi

        $.ajax({
            url: "/getSoDelivery",
            method: "get",
            data:{
                so_id: data.so_id
            },
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                console.log(e);
                tableDn.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    
                    e[i].date = moment(e[i].sod_date).format('D MMM YYYY');
                    if (e[i].status == 1){
                        e[i].status_text = `<span class="badge bg-warning" style="font-size: 12px">Dibuat</span>`;
                    } else if (e[i].status == 2){
                        e[i].status_text = `<span class="badge bg-success" style="font-size: 12px">Diterima</span>`;
                    }
                    else if (e[i].status == 0){
                        e[i].status_text = `<span class="badge bg-danger" style="font-size: 12px">Ditolak</span>`;
                    }
                    
                    e[i].action = `
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

        refreshInvoice();
    }
    
    function refreshInvoice() {
         $.ajax({
            url: "/getSoInvoice",
            method: "get",
            data:{
                so_id: data.so_id
            },
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                console.log(e);
                tableInv.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].date = moment(e[i].soi_date).format('D MMM YYYY');
                    e[i].date_due_date = moment(e[i].soi_due).format('D MMM YYYY');
                    e[i].soi_total_text = formatRupiah(e[i].soi_total,"Rp ");
                    if (e[i].status == 1){
                        e[i].status_text = `<span class="badge bg-warning" style="font-size: 12px">Belum Terbayar</span>`;
                    } else if (e[i].status == 2){
                        e[i].status_text = `<span class="badge bg-success" style="font-size: 12px">Terbayar</span>`;
                    }
                    else if (e[i].status == 0){
                        e[i].status_text = `<span class="badge bg-danger" style="font-size: 12px">Ditolak</span>`;
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
        window.open('/salesOrder', '_self');
    })

    $(document).on('click', '.btnAddDn', function(){
        mode=1;
        $('#add_sales_delivery .modal-title').html("Tambah Catatan Pengiriman");
        $('#add_sales_delivery .form-control').val("");
        $('#sdo_date').val(moment().format('YYYY-MM-DD'));
        $('.is-invalid').removeClass('is-invalid');
        $('.row-acc').hide();
        $('.btn-save-delivery').show();
        $('#sdo_receiver').empty();
        tableSalesDelivery();
        refreshTableProduct(data.items);
        $('#add_sales_delivery').modal("show");
    })

    $(document).on('click', '.btnAddInv', function(){
        mode=1;
        $('#add_sales_invoice .modal-title').html("Tambah Faktur");
        $('#add_sales_invoice input').val("");
        $('#soi_date').val(moment().format('YYYY-MM-DD'));
        $('.row-acc-invoice').hide();
        $('.is-invalid').removeClass('is-invalid');
        $('.btn-save-invoice').show();
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
                { data: "sku", width: "30%" }, 
                { data: "stock", width: "30%" }, 
            ], 
            initComplete: (settings, json) => { 
                $('.dataTables_filter').appendTo('#tableSearch'); 
                $('.dataTables_filter').appendTo('.search-input'); 
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> '); 
            }, 
        }); 
    }

    function refreshTableProduct(e){
        tablePrModal.clear().draw();
        // Manipulasi data sebelum dimasukkan ke tabel
        if(e){
            console.log(e);
            console.log(data);
            
            for (let i = 0; i < e.length; i++) {
                e[i].stock = `
                    
                    <div class="input-group">
                        <input type="number" class="form-control qtyDn" index="${i + 1}" value="${e[i].sod_qty || e[i].sdod_qty}">
                        <span class="input-group-text">${data.items[i].unit_name}</span>
                    </div>
                `;
                e[i].name = e[i].sod_nama || `${e[i].product_name} ${e[i].product_variant_name}`;
                console.log(e[i])
                e[i].sku = e[i].sod_sku || e[i].sdod_sku;
            }
            tablePrModal.rows.add(e).draw();
        }

        feather.replace(); // biar icon feather muncul lagi
    }

    // Refresh Summary & Input qty
    $(document).on('change', '.qtySummary', function () {
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
            ResetLoadingButton('.save-qty', 'Simpan Perubahan');
            return false;
        }

        $(".qtySummary").each(function() {
            let qty = $(this).val();
            var search = $('#tableSupplies').DataTable().row($(this).parents('tr')).data()
            sod_id = search.sod_id;

            let item = data.items.find(i => i.sod_id == sod_id);
            if (item) {
                console.log(item);
                
                item.sod_qty = qty;
                item.sod_subtotal = parseInt(item.sod_harga) * parseInt(qty);
            }
        });
        console.log(data.items);
        param = {
            so_detail: JSON.stringify(data.items),
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
        detail_delivery = [];
        $('#tableSalesDelivery tbody tr').each(function(index) {
            var dataDelivery = $('#tableSalesDelivery').DataTable().row(this).data(); // pakai this saja
            
            //if (mode == 1) dataDelivery = dataDelivery.product_variant;
            
            let qty = parseInt($(this).find('.qtyDn').val()) || 0;
            console.log(index);

            let item = {
                ...dataDelivery,
                product_variant_id: dataDelivery.product_variant_id,
                sdod_sku: dataDelivery.product_variant_sku || dataDelivery.sod_sku,
                sdod_qty: qty,
                unit_id: data.items[index].unit_id
            };
            
            if(mode==2){
                item.sdod_id = dataDelivery.sdod_id;
            }
            detail_delivery.push(item);
        });
    };
    
    $(document).on('click', '.btn-save-delivery', function(){
        LoadingButton(this);
        $('.is-invalid').removeClass('is-invalid');
        var url ="/insertSoDelivery";
        var valid=1;

        $("#add_sales_delivery .fill").each(function(){
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
        console.log(data.so_id);
        
        param = {
            so_id: data.so_id,
            sdo_receiver: $('#sdo_receiver').val(),
            // staff_id: $('#sdo_receiver').val(),
            sdo_date: $('#sdo_date').val(),
            sdo_phone: $('#sdo_phone').val(),
            sdo_desc: $('#sdo_desc').val(),
            sdo_detail: JSON.stringify(detail_delivery),
            _token: token
        };

        if(mode==2){
            url="/updateSoDelivery";
            param.sdo_id = $('#add_sales_delivery').attr("sdo_id");
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
   
    $(document).on('click', '.btn-approve', function(){
        LoadingButton(this);
        $('.is-invalid').removeClass('is-invalid');
        var url ="/accSoDelivery";
        var valid=1;

        $("#add_sales_delivery .fill").each(function(){
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
        console.log(data);
        
        param = {
            so_id: data.so_id,
            sdo_id: data.sdo_id,
            sdo_receiver: $('#sdo_receiver').val(),
            sdo_date: $('#sdo_date').val(),
            sdo_phone: $('#sdo_phone').val(),
            sdo_desc: $('#sdo_desc').val(),
            sdo_detail: JSON.stringify(detail_delivery),
            sdo_id: $('#add_sales_delivery').attr("sdo_id"),
            status:2,
            _token: token
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
                ResetLoadingButton(".btn-approve", 'Setujui');      
                afterInsertDelivery();
            },
            error:function(e){
                ResetLoadingButton(".btn-approve", 'Setujui');
                console.log(e);
            }
        });
    })
   
    $(document).on('click', '.btn-decline', function(){
        LoadingButton(this);
        $('.is-invalid').removeClass('is-invalid');
        var url ="/declineSoDelivery";
        var valid=1;

        $("#add_sales_delivery .fill").each(function(){
            if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                valid=-1;
                $(this).addClass('is-invalid');
            }
        });

        if(valid==-1){
            notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
            ResetLoadingButton('.btn-decline', 'Tolak');
            return false;
        };

        insertDeliveryDetail();
        console.log(data);
        
        param = {
            so_id: data.so_id,
            sdo_id: data.sdo_id,
            sdo_receiver: $('#sdo_receiver').val(),
            sdo_date: $('#sdo_date').val(),
            sdo_phone: $('#sdo_phone').val(),
            sdo_desc: $('#sdo_desc').val(),
            sdo_detail: JSON.stringify(detail_delivery),
            sdo_id: $('#add_sales_delivery').attr("sdo_id"),
            status:0,
            _token: token
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
                ResetLoadingButton(".btn-decline", 'Tolak');      
                afterInsertDelivery();
            },
            error:function(e){
                ResetLoadingButton(".btn-decline", 'Tolak');
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

    // $(document).on('change', '#sdo_receiver', function(){
    //     var data2 = $(this).select2('data')[0];
    //     console.log(data2);
    //     $('#sdo_phone').val(data.staff_phone || '');
    // });
    $(document).on('click', '.btn_edit_dn', function(){
        var data = $('#tableDelivery').DataTable().row($(this).parents('tr')).data();
        console.log(data);
        mode = 2;
        $('#add_sales_delivery .modal-title').html("Update Catatan Pengiriman");
        $('#add_sales_delivery input').val("");
        $('.is-invalid').removeClass('is-invalid');
        $('#sdo_receiver').val(data.sdo_receiver);
        $('#sdo_date').val(data.sdo_date);
        $('#sdo_phone').val(data.sdo_phone);
        $('#sdo_desc').val(data.sdo_desc);

        tableSalesDelivery();
        refreshTableProduct(data.items);
        if(data.status == 1){
            $('.btn-save-delivery').show();
            $('.row-acc').show();
        }
        else if(data.status == 0 || data.status == 2){
            $('.btn-save-delivery').hide();
        }
        $('.btn-save-delivery').html('Simpan perubahan');
        $('#add_sales_delivery').modal("show");
        $('#add_sales_delivery').attr("sdo_id", data.sdo_id);
    })

    $(document).on('click', '.btn_delete_dn', function(){
        var data = $('#tableDelivery').DataTable().row($(this).parents('tr')).data();
        showModalDelete("Apakah yakin ingin menghapus catatan pengiriman ini?","btn-delete-delivery");
        $('#btn-delete-delivery').attr("sdo_id", data.sdo_id);
    })

    $(document).on("click","#btn-delete-delivery",function(){
        $.ajax({
            url:"/deleteSoDelivery",
            data:{
                sdo_id:$('#btn-delete-delivery').attr('sdo_id'),
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
        $('#add_sales_invoice .modal-title').html("Update Faktur");
        $('#add_sales_invoice input').val("");
        $('.is-invalid').removeClass('is-invalid');
        $('.btn-save-invoice').html('Simpan perubahan');
        $('#add_sales_invoice').modal("show");
    })

    function refreshSummary() {
        var total = 0;
        data.items.forEach(item => {
            console.log(item);
            
            total+=(item.sod_harga*item.sod_qty);
        });
        $('#value_total').html(formatRupiah(total,"Rp."))
        var diskon = data.so_discount;
        total -= diskon;
        var ppn = data.so_ppn;
        var cost = data.so_cost;
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
        var url = "/insertInvoiceSO";
        var valid = 1;
        $("#add_sales_invoice .fill").each(function () {
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
            ResetLoadingButton('.btn-save-invoice', 'Simpan Perubahan');
            return false;
        }

        param = {
            so_id: data.so_id,
            soi_date: $("#soi_date").val(),
            soi_due: $("#soi_due").val(),
            soi_total: convertToAngka($("#soi_total").val()),
            _token: token,
        };
        console.log(param);
        LoadingButton($(this));

        if (mode == 2) {
            url = "/updateInvoiceSO";
            param.soi_id = $("#add_sales_invoice").attr("soi_id");
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
                ResetLoadingButton('.btn-save-invoice', 'Simpan Perubahan');
            },
            error: function (e) {
                ResetLoadingButton('.btn-save-invoice', 'Simpan Perubahan');
                console.log(e);
            },
        });
    });

    //edit invoice
    $(document).on("click",".btn_edit_invoice",function(){
        mode=2;
        var data = $('#tableInvoice').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        $('#soi_total').val(formatRupiah(data.soi_total))
        $('#soi_due').val(data.soi_due)
        $('#soi_date').val(data.soi_date)
        $('#add_sales_invoice .modal-title').html("Update Faktur");
        console.log();
        
        if(data.status == 1){
            $('.btn-save-invoice').show();
            $('.row-acc-invoice').show();
        }
        else if(data.status == 0 || data.status == 2){
            $('.btn-save-invoice').hide();
        }

        $('.btn-approve-invoice').attr("soi_id", data.soi_id);
        $('.btn-decline-invoice').attr("soi_id", data.soi_id);
        $('#add_sales_invoice').attr("soi_id", data.soi_id);
        $('#add_sales_invoice').modal("show");
    });

    //delete invoice
    $(document).on("click",".btn_delete_invoice",function(){
        var data = $('#tableInvoice').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        showModalDelete("Apakah yakin ingin menghapus invoice ini?","btn-delete-invoice");
        $('#btn-delete-invoice').attr("soi_id", data.soi_id);
    });
    
    $(document).on("click","#btn-delete-invoice",function(){
  
        $.ajax({
            url:"/deleteInvoiceSO",
            data:{
                soi_id:$('#btn-delete-invoice').attr('soi_id'),
                _token:token,
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
    
    $(document).on("click",".btn-decline-invoice",function(){
        var btn = $(this);
        LoadingButton($(this));
        $.ajax({
            url:"/declineInvoiceSO",
            data:{
                soi_id:$('.btn-decline-invoice').attr('soi_id'),
                status:0,
                _token:token
            },
            method:"post",
            success:function(e){
                $('.modal').modal("hide");
                refreshInvoice();
                ResetLoadingButton(btn, 'Tolak');
                notifikasi('success', "Berhasil Decline", "Berhasil decline invoice");
            },
            error:function(e){
                ResetLoadingButton(btn, 'Tolak');
                console.log(e);
            }
        });
    });
    
    $(document).on("click",".btn-approve-invoice",function(){
        var btn = $(this);
        LoadingButton($(this));
        $.ajax({
            url:"/acceptInvoiceSO",
            data:{
                soi_id:$('.btn-approve-invoice').attr('soi_id'),
                status:2,
                _token:token
            },
            method:"post",
            success:function(e){
                $('.modal').modal("hide");
                refreshInvoice();
                ResetLoadingButton(btn, 'Setujui');
                notifikasi('success', "Berhasil Accept", "Berhasil accept invoice");
            },
            error:function(e){
                 ResetLoadingButton(btn, 'Setujui');
                console.log(e);
            }
        });
    });
    
    $(document).on("click","#btnAddTerima",function(){
        $('#modalTerima').modal("show");
    });