    var mode=1;
    var table, tablePr;
    $(document).ready(function(){
        inisialisasi();
        refreshSalesOrder();
    });
    
    $(document).on('click','.btnAdd',function(){
        mode=1;
        tableSalesModal();
        refreshTableProduct();
        $('#add_sales_order .modal-title').html("Create Sales Order");
        $('#add_sales_order input').val("");
        $('.is-invalid').removeClass('is-invalid');
        $('#add_sales_order').modal("show");
    });
    
    function inisialisasi() {
        table = $('#tableSalesOrder').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Search Sales Order",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "so_name" },
                { data: "so_reference" },
                { data: "so_date" },
                { data: "so_total" },
                { data: "so_paid" },
                { data: "so_difference" },
                { data: "status" },
                { data: "so_cashier" },
                { data: "action", class: "d-flex align-items-center" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });
    }

    function refreshSalesOrder() {
        $.ajax({
            url: "/getSalesOrder",
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                console.log(e);
                table.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].so_date = moment(e[i].so_due_date).format('D MMM YYYY');
                    e[i].status = 'created';
                    e[i].action = `
                        <a href="/salesOrderDetail/${e[i].so_id}" class="me-2 btn-action-icon p-2 btn_view" data-id="${e[i].so_id}" data-bs-target="#view-sales">
                            <i data-feather="view" class="fe fe-eye"></i>
                        </a>
                        <a class="me-2 btn-action-icon p-2 btn_edit" data-id="${e[i].so_id}" data-bs-target="#edit-sales">
                            <i data-feather="edit" class="fe fe-edit"></i>
                        </a>
                        <a class="p-2 btn-action-icon btn_delete" data-id="${e[i].so_id}" href="javascript:void(0);">
                            <i data-feather="trash-2" class="fe fe-trash-2"></i>
                        </a>
                    `;
                }

                table.rows.add(e).draw();
                feather.replace(); // Biar icon feather muncul lagi
            },
            error: function (err) {
                console.error("Gagal load kategori:", err);
            }
        });
    }

    function tableSalesModal(){
        if ($.fn.DataTable.isDataTable('#tableSalesModal')) {
            tablePr = $('#tableSalesModal').DataTable();
            return;
        }
        tablePr = $('#tableSalesModal').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
            searching: false,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Search Sales Order",
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
                console.error("Gagal load kategori:", err);
            }
        });
    }

    $(document).on("click",".btn-save",function(){
       // LoadingButton(this);
        $('.is-invalid').removeClass('is-invalid');
        var url ="/insertSales";
        var valid=1;

        $("#add_sales_order .fill").each(function(){
            if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                valid=-1;
                $(this).addClass('is-invalid');
            }
        });

        if(valid==-1){
            notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
            //ResetLoadingButton('.btn-save', 'Save changes');
            return false;
        };

        param = {
            // category_name:$('#category_name').val(),
             _token:token
        };

        if(mode==2){
            url="/updateSalesOrder";
            // param.category_id = $('#add_sales_order').attr("category_id");
        }

        //LoadingButton($(this));
        $.ajax({
            url:url,
            data: param,
            method:"post",
            headers: {
                'X-CSRF-TOKEN': token
            },
            success:function(e){      
                //ResetLoadingButton(".btn-save", 'Save changes');      
                afterInsert();
            },
            error:function(e){
                //ResetLoadingButton(".btn-save", 'Save changes');
                console.log(e);
            }
        });
    });

    function afterInsert() {
        $(".modal").modal("hide");
        if(mode==1)notifikasi('success', "Successful Insert", "Successful Category Added");
        else if(mode==2)notifikasi('success', "Successful Update", "Successful Category Updated");
        refreshSalesOrder();
    }

    // $(document).on("keyup","#filter_category_name",function(){
    //     refreshSalesOrder();
    // });

    //edit
    $(document).on("click",".btn_edit",function(){
        var data = $('#tableSalesOrder').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        mode=2;
        $('#add_sales_order .modal-title').html("Update Category");
        $('#add_sales_order input').empty().val("");
        // $('#category_name').val(data.category_name);

        $('#add_sales_order').modal("show");
        // $('#add_sales_order').attr("so_id", data.so_id);
    });

    //delete
    $(document).on("click",".btn_delete",function(){
        var data = $('#tableSalesOrder').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        // showModalDelete("Apakah yakin ingin mengahapus category ini?","btn-delete-category");
        $('#modalDelete').modal("show");
        // $('#btn-delete-sales').attr("category_id", data.category_id);
    });


    $(document).on("click","#btn-delete-sales",function(){
        $.ajax({
            url:"/deleteSalesOrder",
            data:{
                category_id:$('#btn-delete-sales').attr('category_id'),
                _token:token
            },
            method:"post",
            success:function(e){
                $('.modal').modal("hide");
                refreshSalesOrder();
                notifikasi('success', "Berhasil Delete", "Berhasil delete sales order");
                
            },
            error:function(e){
                console.log(e);
            }
        });
    });
