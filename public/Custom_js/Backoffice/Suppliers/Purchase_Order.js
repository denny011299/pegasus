    var mode=1;
    var table, tablePr;
    $(document).ready(function(){
        inisialisasi();
        refreshPurchaseOrder();
    });
    
    $(document).on('click','.btnAdd',function(){
        mode=1;
        tablePurchaseModal();
        refreshTableProduct();
        $('#add_purchase_order .modal-title').html("Tambah Pesanan Pembelian");
        $('#add_purchase_order input').val("");
        $('.is-invalid').removeClass('is-invalid');
        $('#add_purchase_order').modal("show");
    });
    
    function inisialisasi() {
        table = $('#tablePurchaseOrder').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Cari Pesanan Pembelian",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "po_name" },
                { data: "po_reference" },
                { data: "date" },
                { data: "po_total" },
                { data: "po_paid" },
                { data: "po_payables" },
                { data: "status" },
                { data: "po_created_by" },
                { data: "action", class: "d-flex align-items-center" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });
    }

    function refreshPurchaseOrder() {
        $.ajax({
            url: "/getPurchaseOrder",
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                console.log(e);
                table.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].date = moment(e[i].po_date).format('D MMM YYYY');
                    if (e[i].po_status == 1){
                        e[i].status = `<span class="badge bg-success" style="font-size: 12px">Lunas</span>`;
                    } else if (e[i].po_status == 2){
                        e[i].status = `<span class="badge bg-warning" style="font-size: 12px">Tertunda</span>`;
                    }
                    e[i].action = `
                        <a href="/purchaseOrderDetail/${e[i].po_id}" class="me-2 btn-action-icon p-2 btn_view" data-id="${e[i].po_id}" data-bs-target="#view-purchase">
                            <i data-feather="view" class="fe fe-eye"></i>
                        </a>
                        <a class="me-2 btn-action-icon p-2 btn_edit" data-id="${e[i].po_id}" data-bs-target="#edit-purchase">
                            <i data-feather="edit" class="fe fe-edit"></i>
                        </a>
                        <a class="p-2 btn-action-icon btn_delete" data-id="${e[i].po_id}" href="javascript:void(0);">
                            <i data-feather="trash-2" class="fe fe-trash-2"></i>
                        </a>
                    `;
                }

                table.rows.add(e).draw();
                feather.replace(); // Biar icon feather muncul lagi
            },
            error: function (err) {
                console.error("Gagal load Pesanan Pembelian:", err);
            }
        });
    }

    function tablePurchaseModal(){
        if ($.fn.DataTable.isDataTable('#tablePurchaseModal')) {
            tablePr = $('#tablePurchaseModal').DataTable();
            return;
        }
        tablePr = $('#tablePurchaseModal').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
            searching: false,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Cari Pesanan Pembelian",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "pr_name" },
                { data: "pr_qty", className: "text-center"},
                { data: "pr_buy_price", className: "text-end"},
                { data: "pr_discount", className: "text-end"},
                { data: "pr_price", className: "text-end"},
                { data: "pr_subtotal", className: "text-end"},
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
                console.error("Gagal load Pesanan Pembelian:", err);
            }
        });
    }

    $(document).on("click",".btn-save",function(){
       // LoadingButton(this);
        $('.is-invalid').removeClass('is-invalid');
        var url ="/insertSales";
        var valid=1;

        $("#add_purchase_order .fill").each(function(){
            if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                valid=-1;
                $(this).addClass('is-invalid');
            }
        });

        if(valid==-1){
            notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
            ResetLoadingButton('.btn-save', 'Simpan perubahan');
            return false;
        };

        param = {
            // category_name:$('#category_name').val(),
             _token:token
        };

        if(mode==2){
            url="/updateSalesOrder";
            // param.category_id = $('#add_purchase_order').attr("category_id");
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
                ResetLoadingButton(".btn-save", 'Simpan perubahan');      
                afterInsert();
            },
            error:function(e){
                ResetLoadingButton(".btn-save", 'Simpan perubahan');
                console.log(e);
            }
        });
    });

    function afterInsert() {
        $(".modal").modal("hide");
        if(mode==1)notifikasi('success', "Berhasil Insert", "Berhasil Tambah Pesanan Pembelian");
        else if(mode==2)notifikasi('success', "Berhasil Update", "Berhasil Update Pesanan Pembelian");
        refreshPurchaseOrder();
    }

    // $(document).on("keyup","#filter_category_name",function(){
    //     refreshPurchaseOrder();
    // });

    //edit
    $(document).on("click",".btn_edit",function(){
        var data = $('#tablePurchaseOrder').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        mode=2;
        $('#add_purchase_order .modal-title').html("Update Pesanan Pembelian");
        $('#add_purchase_order input').empty().val("");
        // $('#category_name').val(data.category_name);
        $('.is-invalid').removeClass('is-invalid');
        $('#add_purchase_order').modal("show");
        // $('#add_purchase_order').attr("so_id", data.so_id);
    });

    //delete
    $(document).on("click",".btn_delete",function(){
        var data = $('#tablePurchaseOrder').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        showModalDelete("Apakah yakin ingin mengahapus pesanan pembelian ini?","btn-delete-purchase");
        $('#btn-delete-purchase').attr("category_id", data.category_id);
    });


    $(document).on("click","#btn-delete-purchase",function(){
        $.ajax({
            url:"/deletePurchaseOrder",
            data:{
                category_id:$('#btn-delete-purchase').attr('category_id'),
                _token:token
            },
            method:"post",
            success:function(e){
                $('.modal').modal("hide");
                refreshPurchaseOrder();
                notifikasi('success', "Berhasil Delete", "Berhasil delete pesanan pembelian");
                
            },
            error:function(e){
                console.log(e);
            }
        });
    });
