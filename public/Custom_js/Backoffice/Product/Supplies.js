    var mode=1;
    var table;
    $(document).ready(function(){
        inisialisasi();
        refreshSupplies();
    });
    
    $(document).on('click','.btnAdd',function(){
        mode=1;
        $('#add_supplies .modal-title').html("Create Supplies");
        $('#add_supplies input').val("");
        $('.is-invalid').removeClass('is-invalid');
        $('#sup_unit').tagsinput('removeAll');
        $('#add_supplies').modal("show");
    });
    
    function inisialisasi() {
        table = $('#tableSupplies').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Search Supplies",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "sup_name" },
                { data: "unit_values" },
                { data: "sup_desc" },
                { data: "sup_stock" },
                { data: "action", class: "d-flex align-items-center" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });
    }

    function refreshSupplies() {
        $.ajax({
            url: "/getSupplies",
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }

                table.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].unit_values = "";
                    JSON.parse(e[i].sup_unit).forEach((element,index) => {
                         e[i].unit_values += element;
                         if(index< JSON.parse(e[i].sup_unit).length-1){
                            e[i].unit_values += ", ";
                         }
                    });
                    e[i].action = `
                        <a class="me-2 btn-action-icon p-2 btn_edit" data-id="${e[i].sup_id}" data-bs-target="#edit-category">
                            <i data-feather="edit" class="feather-edit"></i>
                        </a>
                        <a class="p-2 btn-action-icon btn_delete" data-id="${e[i].sup_id}" href="javascript:void(0);">
                            <i data-feather="trash-2" class="feather-trash-2"></i>
                        </a>
                    `;
                }

                table.rows.add(e).draw();
                feather.replace(); // Biar icon feather muncul lagi
            },
            error: function (err) {
                console.error("Gagal load:", err);
            }
        });
    }

    $(document).on("click",".btn-save",function(){
       // LoadingButton(this);
        $('.is-invalid').removeClass('is-invalid');
        var url ="/insertSupplies";
        var valid=1;

        $("#add_supplies .fill").each(function(){
            if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                valid=-1;
                $(this).addClass('is-invalid');
            }
        });

        if(valid==-1){
            // notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
            //ResetLoadingButton('.btn-save', 'Save changes');
            return false;
        };

        param = {
            // category_name:$('#category_name').val(),
             _token:token
        };

        if(mode==2){
            url="/updateSupplies";
            // param.category_id = $('#add_supplies').attr("category_id");
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
        refreshSupplies();
    }

    // $(document).on("keyup","#filter_category_name",function(){
    //     refreshSupplies();
    // });
    //edit
    $(document).on("click",".btn_edit",function(){
        var data = $('#tableSupplies').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        mode=2;
        $('#add_supplies .modal-title').html("Update Supplies");
        $('#add_supplies input').empty().val("");
        $('#sup_unit').tagsinput('removeAll');
        // $('#category_name').val(data.category_name);

        $('#add_supplies').modal("show");
        // $('#add_supplies').attr("category_id", data.category_id);
    });

    //delete
    $(document).on("click",".btn_delete",function(){
        var data = $('#tableSupplies').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        // showModalDelete("Apakah yakin ingin mengahapus category ini?","btn-delete-category");
        $('#modalDelete').modal("show");
        // $('#btn-delete-supplies').attr("category_id", data.category_id);
    });


    $(document).on("click","#btn-delete-supplies",function(){
        $.ajax({
            url:"/deleteSupplies",
            data:{
                // category_id:$('#btn-delete-supplies').attr('category_id'),
                _token:token
            },
            method:"post",
            success:function(e){
                $('.modal').modal("hide");
                refreshSupplies();
                // notifikasi('success', "Berhasil Delete", "Berhasil delete category");
                
            },
            error:function(e){
                console.log(e);
            }
        });
    });
