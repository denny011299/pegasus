    var mode=1;
    var tablePr, tableDn, tableInv, tablePrModal;
    let detail_delivery = [];


    autocompleteStaff("#pdo_receiver",null);
    autocompleteSupplier("#po_supplier",null);

    $(document).ready(function(){
        inisialisasi();
        refresh();
        refreshSummary();
        $('#po_status').val(data.status).trigger('change');
   
        $('.save-terima,.save-tolak,.save-qty').hide();
        if(data.status==1){
            $('.save-tolak,.save-terima,.save-qty').show();
        }
        else if(data.status==2){
            $('.save-tolak').show();
            $('.save-terima,.save-qty').hide();
        }
       
        if(data.pembayaran==3){
            $('.save-tolak,.save-terima,.save-qty').hide();
        }
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
    }

    function refresh() {
        tablePr.clear().draw(); 
        // Manipulasi data sebelum masuk ke tabel
        console.log(data);
        data.items.forEach((element, index) => {
            element.qty = `
                <input type="number" class="form-control text-center qtySummary" data-price="${element.pod_harga}" index="${index}" value="${element.pod_qty}" min="0">
            `;
            element.pod_harga_text = formatRupiah(element.pod_harga,"Rp.");
            element.pod_subtotal_text = formatRupiah(element.pod_subtotal,"Rp.");
        });

        tablePr.rows.add(data.items).draw();
        feather.replace(); // Biar icon feather muncul lagi

        $.ajax({
            url: "/getPoDelivery",
            method: "get",
            data:{
                po_id: data.po_id
            },
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
                    } else if (e[i].status == 2){
                        e[i].status_text = `<span class="badge bg-success" style="font-size: 12px">Barang Diterima</span>`;
                    }
                    else if (e[i].status == 0){
                        e[i].status_text = `<span class="badge bg-danger" style="font-size: 12px">Ditolak</span>`;
                    }
                    
                    e[i].action = `
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

        refreshInvoice();
    }
    
    function refreshInvoice() {
         $.ajax({
            url: "/getPoInvoice",
            method: "get",
            data:{
                po_id: data.po_id
            },
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                console.log(e);
                var total = 0;
                tableInv.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].date = moment(e[i].poi_date).format('D MMM YYYY');
                    e[i].date_due_date = moment(e[i].poi_due).format('D MMM YYYY');
                    e[i].poi_total_text = formatRupiah(e[i].poi_total,"Rp ");
                    if (e[i].status == 1){
                        e[i].status_text = `<span class="badge bg-warning" style="font-size: 12px">Dibuat</span>`;
                    } else if (e[i].status == 2){
                        e[i].status_text = `<span class="badge bg-success" style="font-size: 12px">Diterima</span>`;
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
                    if(e[i].status==2)total += e[i].poi_total;
                }
                console.log(total);
                
                var sisa = convertToAngka($('#po_total').val()) - total;
                $('#po_paid').val(formatRupiah(total,""));
                $('#po_remain').val(formatRupiah(sisa,""));
                
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
        $('#pdo_date').val(moment().format('YYYY-MM-DD'));
        $('.is-invalid').removeClass('is-invalid');
        $('.btn-decline,.btn-approve').hide();
        $('.btn-save-delivery').show();
        $('.btn-save-delivery').html('Tambah Catatan Pengiriman');
        $('#pdo_receiver').empty();
        tablePurchaseDelivery();
        refreshTableProduct(data.items);
        $('#add_purchase_delivery').modal("show");
    })

    $(document).on('click', '.btnAddInv', function(){
        mode=1;
        var dt = tableDn.rows().data().toArray();
        var ada=-1;
        dt.forEach(element => {
            if(element.status==2) ada=1;
        });
        if(ada==-1){
            notifikasi("error","Gagal Buat Invoice","Barang harus diterima dan diacc oleh pihak gudang terlebih dahulu");
            return false;
        }
        $('#add_purchase_invoice .modal-title   ').html("Tambah Faktur");
        $('#add_purchase_invoice input').val("");
        $('#poi_date').val(moment().format('YYYY-MM-DD'));
        $('.row-acc-invoice').hide();
        $('.is-invalid').removeClass('is-invalid');
        $('.btn-save-invoice').show();
        $('.btn-save-invoice').html('Tambah Faktur');
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
                        <input type="number" class="form-control qtyDn" index="${i + 1}" value="${e[i].pod_qty || e[i].pdod_qty}">
                        <span class="input-group-text">${data.items[i].unit_name}</span>
                    </div>
                `;
                e[i].name = e[i].pod_nama || `${e[i].supplies_name} ${e[i].supplies_variant_name}`;
                console.log(e[i])
                e[i].sku = e[i].pod_sku || e[i].pdod_sku;
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
            pod_id = search.pod_id;

            let item = data.items.find(i => i.pod_id == pod_id);
            if (item) {
                console.log(item);
                
                item.pod_qty = qty;
                item.pod_subtotal = parseInt(item.pod_harga) * parseInt(qty);
            }
        });
        console.log(data.items);
        param = {
            po_id: data.po_id,
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
        detail_delivery = [];
        $('#tablePurchaseDelivery tbody tr').each(function(index) {
            var dataDelivery = $('#tablePurchaseDelivery').DataTable().row(this).data(); // pakai this saja
            
            //if (mode == 1) dataDelivery = dataDelivery.supplies_variant;
            
            let qty = parseInt($(this).find('.qtyDn').val()) || 0;
            console.log(index);

            let item = {
                ...dataDelivery,
                supplies_variant_id: dataDelivery.supplies_variant_id,
                pdod_sku: dataDelivery.supplies_variant_sku || dataDelivery.pod_sku,
                pdod_qty: qty,
                unit_id: data.items[index].unit_id
            };
            
            if(mode==2){
                item.pdod_id = dataDelivery.pdod_id;
            }
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
        console.log(data.po_id);
        
        param = {
            po_id: data.po_id,
            pdo_receiver: $('#pdo_receiver option:selected').text().trim(),
            staff_id: $('#pdo_receiver').val(),
            pdo_date: $('#pdo_date').val(),
            pdo_phone: $('#pdo_phone').val(),
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
                 $('#po_status').val(e).trigger('change');     
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
        var url ="/accPoDelivery";
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
        console.log(data);
        
        param = {
            po_id: data.po_id,
            pdo_id: data.pdo_id,
            pdo_receiver: $('#pdo_receiver option:selected').text().trim(),
            staff_id: $('#pdo_receiver').val(),
            pdo_date: $('#pdo_date').val(),
            pdo_phone: $('#pdo_phone').val(),
            pdo_desc: $('#pdo_desc').val(),
            pdo_detail: JSON.stringify(detail_delivery),
            pdo_id: $('#add_purchase_delivery').attr("pdo_id"),
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
                if(e==1){
                    afterInsertDelivery(e);
                }     
                else{
                    notifikasi('error', "Gagal Insert", e.message);
                }
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
        var url ="/declinePoDelivery";
        var valid=1;

        $("#add_purchase_delivery .fill").each(function(){
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
            po_id: data.po_id,
            pdo_id: data.pdo_id,
            pdo_receiver: $('#pdo_receiver option:selected').text().trim(),
            staff_id: $('#pdo_receiver').val(),
            pdo_date: $('#pdo_date').val(),
            pdo_phone: $('#pdo_phone').val(),
            pdo_desc: $('#pdo_desc').val(),
            pdo_detail: JSON.stringify(detail_delivery),
            pdo_id: $('#add_purchase_delivery').attr("pdo_id"),
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
                afterInsertDelivery(e);
               
            },
            error:function(e){
                ResetLoadingButton(".btn-decline", 'Tolak');
                console.log(e);
            }
        });
    })

    function afterInsertDelivery(e) {
        $(".modal").modal("hide");
        if(mode==1)notifikasi('success', "Berhasil Insert", "Berhasil Tambah Catatan Pengiriman");
        else if(mode==2)notifikasi('success', "Berhasil Update", "Berhasil Update Catatan Pengiriman");
        refresh();
            
      
    }

    $(document).on('change', '#pdo_receiver', function(){
        var data = $(this).select2('data')[0];
        $('#pdo_phone').val(data.staff_phone || '');
    });
    $(document).on('click', '.btn_edit_dn', function(){
        var data = $('#tableDelivery').DataTable().row($(this).parents('tr')).data();
        console.log(data);
        mode = 2;
        if(data.status == 1){
            $('.btn-decline').show();
            $('.btn-approve').show();
        }
        else if(data.status == 2){
            $('.btn-decline').show();
            $('.btn-approve').hide();
            $('.btn-save-delivery').hide();
        }
        else if(data.status == 0){
            $('.btn-decline').hide();
            $('.btn-approve').show();
            $('.btn-save-delivery').hide();
        }

        $('#add_purchase_delivery .modal-title').html("Update Catatan Pengiriman");
        $('#add_purchase_delivery input').val("");
        $('.btn-save-delivery').html('Update Catatan Pengiriman');
        $('.is-invalid').removeClass('is-invalid');
        $('#pdo_receiver').empty().append(`<option value="${data.staff_id}">${data.pdo_receiver}</option>`);
        $('#pdo_date').val(data.pdo_date);
        $('#pdo_phone').val(data.pdo_phone);
        $('#pdo_desc').val(data.pdo_desc);

        tablePurchaseDelivery();
        refreshTableProduct(data.items);
        console.log(data.status);
        
        
        $('#add_purchase_delivery').attr("pdo_id", data.pdo_id);
        $('#add_purchase_delivery').modal("show");
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

    function refreshSummary() {
        var total = 0;
        data.items.forEach(item => {
            console.log(item);
            
            total+=(item.pod_harga*item.pod_qty);
        });
        $('#value_total').html(formatRupiah(total,"Rp."))
        var diskon = Math.round(total * (parseInt(data.po_discount)/100));
        total -= diskon;
        var ppn = Math.round(total * (parseInt(data.po_ppn)/100));
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
            ResetLoadingButton('.btn-save-invoice', mode == 1?"Tambah Faktur" : "Update Faktur");
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
                ResetLoadingButton('.btn-save-invoice', mode == 1?"Tambah Faktur" : "Update Faktur");
                 $('#po_status').val(e).trigger('change');
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
        $('#poi_total').val(formatRupiah(data.poi_total))
        $('#poi_due').val(data.poi_due)
        $('#poi_date').val(data.poi_date)
        $('#poi_code').val(data.poi_code)
        $('#add_purchase_invoice .modal-title').html("Update Faktur");
        $('.btn-save-invoice').html('Update Faktur');
        $('.is-invalid').removeClass('is-invalid');
        console.log();
        
        if(data.status == 1){
            $('.btn-save-invoice').show();
            $('.row-acc-invoice').show();
        }
        else if(data.status == 0){
            $('.btn-decline-invoice').hide();
            $('.btn-approve-invoice').show();
            $('.btn-save-invoice').hide();
        }
        else if(data.status == 2){
            $('.btn-decline-invoice').show();
            $('.btn-approve-invoice').hide();
            $('.btn-save-invoice').hide();
        }

        $('.btn-approve-invoice').attr("poi_id", data.poi_id);
        $('.btn-decline-invoice').attr("poi_id", data.poi_id);
        $('#add_purchase_invoice').attr("poi_id", data.poi_id);
        $('#add_purchase_invoice').modal("show");
    });

    //delete invoice
    $(document).on("click",".btn_delete_invoice",function(){
        var data = $('#tableInvoice').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        showModalDelete("Apakah yakin ingin menghapus invoice ini?","btn-delete-invoice");
        $('#btn-delete-invoice').attr("poi_id", data.poi_id);

    });
    
    $(document).on("click","#btn-delete-invoice",function(){
        $.ajax({
            url:"/deleteInvoicePO",
            data:{
                poi_id:$('#btn-delete-invoice').attr('poi_id'),
                status:-1,
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
            url:"/declineInvoicePO",
            data:{
                poi_id:$('.btn-decline-invoice').attr('poi_id'),
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
            url:"/acceptInvoicePO",
            data:{
                poi_id:$('.btn-approve-invoice').attr('poi_id'),
                status:2,
                _token:token
            },
            method:"post",
            success:function(e){
                $('.modal').modal("hide");
                refreshInvoice();
                ResetLoadingButton(btn, 'Setujui');
                console.log(e);
                
                if(e==-1){
                      notifikasi(
                        "error",
                        "Gagal Setujui",
                        "Melebihi Sisa Pembayaran"
                    );
                    return false;
                }
                else{

                    notifikasi('success', "Berhasil Accept", "Berhasil accept invoice");
                }
            },
            error:function(e){
                 ResetLoadingButton(btn, 'Setujui');
                console.log(e);
            }
        });
    });
    
    
    $(document).on("click",".btn-approve-invoice",function(){
        var btn = $(this);
        LoadingButton($(this));
        $.ajax({
            url:"/acceptInvoicePO",
            data:{
                poi_id:$('.btn-approve-invoice').attr('poi_id'),
                status:2,
                _token:token
            },
            method:"post",
            success:function(e){
                $('.modal').modal("hide");
                refreshInvoice();
                ResetLoadingButton(btn, 'Setujui');
                console.log(e);
                
                if(e==-1){
                      notifikasi(
                        "error",
                        "Gagal Setujui",
                        "Melebihi Sisa Pembayaran"
                    );
                    return false;
                }
                else{

                    notifikasi('success', "Berhasil Accept", "Berhasil accept invoice");
                }
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


    //konfirmasi acc
$(document).on("click", ".save-terima", function () {
    var tbId = $(this).closest("table").attr("id");
    var data = $("#" + tbId)
        .DataTable()
        .row($(this).parents("tr"))
        .data(); //ambil data dari table
    showModalKonfirmasi(
        "Apakah yakin ingin Approve pembelian ini?",
        "btn-acc-po"
    );
    $("#btn-acc-po").html("Konfirmasi");
});

$(document).on("click", "#btn-acc-po", function () {

    $.ajax({
        url: "/accPO",
        data: {
            data:data,
            _token: token,
        },
        method: "post",
        success: function (e) {
            $('#modalDelete .modal-body').html('');
            $(".modal").modal("hide");
            $('#po_status').val(2).trigger('change');
            notifikasi(
                "success",
                "Berhasil Approve",
                "Berhasil approve pembatalan produksi"
            );
            window.open('/purchaseOrder', '_self');
        },
        error: function (e) {
            console.log(e);
        },
    });
});



    $(document).on('click', '.save-tolak', function(){
        showModalDelete("Apakah yakin ingin menolak po ini?","btn-tolak-po");
    })

    $(document).on("click","#btn-tolak-po",function(){
        $.ajax({
            url:"/tolakPO",
            data:{
                po_id:data.po_id,
                _token:token
            },
            method:"post",
            success:function(e){
                 $('#modalDelete .modal-body').html('');
                $(".modal").modal("hide");
                $('#po_status').val(-1).trigger('change');
                notifikasi(
                    "success",
                    "Berhasil Tolak",
                    "Berhasil tolak PO"
                );
                window.open('/purchaseOrder', '_self');
                
            },
            error:function(e){
                console.log(e);
            }
        });
    });