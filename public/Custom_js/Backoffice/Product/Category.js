    var mode=1;
    var table;
    $(document).ready(function(){
        inisialisasi();
        refreshCategory();
    });
    
    $(document).on('click','.btnAdd',function(){
        mode=1;
        $('#add_category .modal-title').html("Create Category");
        $('#add_category input').val("");
        $('.is-invalid').removeClass('is-invalid');
        $('#add_category').modal("show");
    });
    
    function inisialisasi() {
        table = $('#tableCategory').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Search Category Name",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "category_name" },
                { data: "category_date" },
                { data: "action", class: "action-table-data" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });
    }

    function refreshCategory() {
        $.ajax({
            url: "/getCategory",
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                console.log(e);
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].category_date = moment(e[i].created_at).format('D MMM YYYY');
                    e[i].action = `
                        <div class="edit-delete-action d-flex align-items-center">
                            <a class="me-2 p-2 btn_edit" data-id="${e[i].category_id}" data-bs-target="#edit-category">
                                <i data-feather="edit" class="feather-edit"></i>
                            </a>
                            <a class="p-2 btn_delete" data-id="${e[i].category_id}" href="javascript:void(0);">
                                <i data-feather="trash-2" class="feather-trash-2"></i>
                            </a>
                        </div>
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

    $(document).on("click",".btn-save",function(){
       // LoadingButton(this);
        $('.is-invalid').removeClass('is-invalid');
        var url ="/insertCategory";
        var valid=1;

        $("#add_category .fill").each(function(){
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
            category_name:$('#category_name').val(),
             _token:token
        };

        if(mode==2){
            url="/updateCategory";
            param.category_id = $('#add_category').attr("category_id");
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
        refreshCategory();
    }

    $(document).on("keyup","#filter_category_name",function(){
        refreshCategory();
    });
    //edit
    $(document).on("click",".btn_edit",function(){
        var data = $('#tableCategory').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        mode=2;
        $('#add_category .modal-title').html("Update Category");
        $('#add_category input').empty().val("");
        $('#category_name').val(data.category_name);

        $('#add_category').modal("show");
        $('#add_category').attr("category_id", data.category_id);
    });

    //delete
    $(document).on("click",".btn_delete",function(){
        var data = $('#tableCategory').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        // showModalDelete("Apakah yakin ingin mengahapus category ini?","btn-delete-category");
        $('#deleteModal').modal("show");
        $('#btn-delete-category').attr("category_id", data.category_id);
    });


    $(document).on("click","#btn-delete-category",function(){
        $.ajax({
            url:"/deleteCategory",
            data:{
                category_id:$('#btn-delete-category').attr('category_id'),
                _token:token
            },
            method:"post",
            success:function(e){
                $('.modal').modal("hide");
                refreshCategory();
                notifikasi('success', "Berhasil Delete", "Berhasil delete category");
                
            },
            error:function(e){
                console.log(e);
            }
        });
    });
