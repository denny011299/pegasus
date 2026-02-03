    var mode=1;
    var table;
    $(document).ready(function(){
        inisialisasi();
        refreshCashCategory();
    });
    
    $(document).on('click','.btnAdd',function(){
        mode=1;
        $('#add_cash_category .modal-title').html("Tambah Kategori Kas");
        $('#add_cash_category input').val("");
        $('#add_cash_category select').val("");
        $('.is-invalid').removeClass('is-invalid');
        $('.btn-save').html("Tambah Kategori Kas");
        $('#add_cash_category').modal("show");
    });
    
    function inisialisasi() {
        table = $('#tableCategory').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            lengthMenu: [10, 25, 50, 100],
            ordering: true,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Cari Kategori Kas",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "cc_name" },
                { data: "cc_type" },
                { data: "action", class: "d-flex align-items-center" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });
    }

    function refreshCashCategory() {
        $.ajax({
            url: "/getCashCategory",
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                console.log(e);
                table.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].action = `
                        <a class="me-2 btn-action-icon p-2 btn_edit" data-id="${e[i].cc_id}" data-bs-target="#edit-category">
                            <i class="fe fe-edit"></i>
                        </a>
                        <a class="p-2 btn-action-icon btn_delete" data-id="${e[i].cc_id}" href="javascript:void(0);">
                            <i class="fe fe-trash-2"></i>
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

    $(document).on("click",".btn-save",function(){
       LoadingButton(this);
        $('.is-invalid').removeClass('is-invalid');
        var url ="/insertCashCategory";
        var valid=1;

        $("#add_cash_category .fill").each(function(){
            if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                valid=-1;
                $(this).addClass('is-invalid');
            }
        });

        if(valid==-1){
            notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
            ResetLoadingButton('.btn-save', mode == 1?"Tambah Kategori Kas" : "Update Kategori Kas");  
            return false;
        };

        param = {
            cc_name:$('#cc_name').val(),
            cc_type:$('#cc_type').val(),
             _token:token
        };

        if(mode==2){
            url="/updateCashCategory";
            param.cc_id = $('#add_cash_category').attr("cc_id");
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
                ResetLoadingButton('.btn-save', mode == 1?"Tambah Kategori Kas" : "Update Kategori Kas");  
                afterInsert();
            },
            error:function(e){
                ResetLoadingButton('.btn-save', mode == 1?"Tambah Kategori Kas" : "Update Kategori Kas");  
                console.log(e);
            }
        });
    });

    function afterInsert() {
        $(".modal").modal("hide");
        if(mode==1)notifikasi('success', "Berhasil Insert", "Berhasil Tambah Kategori Kas");
        else if(mode==2)notifikasi('success', "Berhasil Update", "Berhasil Update Kategori Kas");
        refreshCashCategory();
    }

    $(document).on("keyup","#filter_cc_name",function(){
        refreshCashCategory();
    });
    //edit
    $(document).on("click",".btn_edit",function(){
        var data = $('#tableCategory').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        mode=2;
        $('#add_cash_category .modal-title').html("Update Kategori Kas");
        $('#add_cash_category input').empty().val("");
        $('#cc_name').val(data.cc_name);
        $('#cc_type').val(data.cc_type);
        $('.is-invalid').removeClass('is-invalid');
        $('.btn-save').html('Update Kategori Kas');
        $('#add_cash_category').modal("show");
        $('#add_cash_category').attr("cc_id", data.cc_id);
    });

    //delete
    $(document).on("click",".btn_delete",function(){
        var data = $('#tableCategory').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        showModalDelete("Apakah yakin ingin menghapus kategori kas ini?","btn-delete-cc");
        $('#btn-delete-cc').attr("cc_id", data.cc_id);
    });


    $(document).on("click","#btn-delete-cc",function(){
        $.ajax({
            url:"/deleteCashCategory",
            data:{
                cc_id:$('#btn-delete-cc').attr('cc_id'),
                _token:token
            },
            method:"post",
            success:function(e){
                $('.modal').modal("hide");
                refreshCashCategory();
                notifikasi('success', "Berhasil Delete", "Berhasil delete kategori kas");
                
            },
            error:function(e){
                console.log(e);
            }
        });
    });
