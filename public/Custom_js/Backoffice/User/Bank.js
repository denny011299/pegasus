    var mode=1;
    var table;
    $(document).ready(function(){
        inisialisasi();
        refreshbank();
    });
    
    $(document).on('click','.btnAdd',function(){
        mode=1;
        $('#add_bank .modal-title').html("Tambah Bank");
        $('#add_bank input').val("");
        $('.is-invalid').removeClass('is-invalid');
        $('#add_bank').modal("show");
    });
    
    function inisialisasi() {
        table = $('#tableBank').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Cari Bank",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "bank_kode" },
                { data: "bank_date" },
                { data: "action", class: "d-flex align-items-center" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });
    }

    function refreshbank() {
        $.ajax({
            url: "/getBank",
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                console.log(e);
                table.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].bank_date = moment(e[i].created_at).format('D MMM YYYY');
                    e[i].action = `
                        <a class="me-2 btn-action-icon p-2 btn_edit" data-id="${e[i].bank_id}" data-bs-target="#edit-bank">
                            <i class="fe fe-edit"></i>
                        </a>
                        <a class="p-2 btn-action-icon btn_delete" data-id="${e[i].bank_id}" href="javascript:void(0);">
                            <i class="fe fe-trash-2"></i>
                        </a>
                    `;
                }

                table.rows.add(e).draw();
                feather.replace(); // Biar icon feather muncul lagi
            },
            error: function (err) {
                console.error("Gagal load Bank:", err);
            }
        });
    }

    $(document).on("click",".btn-save",function(){
       LoadingButton(this);
        $('.is-invalid').removeClass('is-invalid');
        var url ="/insertBank";
        var valid=1;

        $("#add_bank .fill").each(function(){
            if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                valid=-1;
                $(this).addClass('is-invalid');
            }
        });

        if(valid==-1){
            notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
            ResetLoadingButton('.btn-save', mode == 1?"Tambah Bank" : "Update Bank");
            return false;
        };

        param = {
            bank_kode:$('#bank_kode').val(),
             _token:token
        };

        if(mode==2){
            url="/updateBank";
            param.bank_id = $('#add_bank').attr("bank_id");
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
                ResetLoadingButton('.btn-save', mode == 1?"Tambah Bank" : "Update Bank");   
                afterInsert();
            },
            error:function(e){
                ResetLoadingButton('.btn-save', mode == 1?"Tambah Bank" : "Update Bank");
                console.log(e);
            }
        });
    });

    function afterInsert() {
        $(".modal").modal("hide");
        if(mode==1)notifikasi('success', "Berhasil Insert", "Berhasil Tambah Bank");
        else if(mode==2)notifikasi('success', "Berhasil Update", "Berhasil Update Bank");
        refreshbank();
    }

    $(document).on("keyup","#filter_bank_name",function(){
        refreshbank();
    });
    //edit
    $(document).on("click",".btn_edit",function(){
        var data = $('#tableBank').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        mode=2;
        $('#add_bank .modal-title').html("Update Bank");
        $('#add_bank input').empty().val("");
        $('#bank_kode').val(data.bank_kode);
        $('.is-invalid').removeClass('is-invalid');
        $('.btn-save').html('Tambah Bank');
        $('#add_bank').modal("show");
        $('#add_bank').attr("bank_id", data.bank_id);
    });

    //delete
    $(document).on("click",".btn_delete",function(){
        var data = $('#tableBank').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        showModalDelete("Apakah yakin ingin menghapus Bank ini?","btn-delete-bank");
        $('#btn-delete-bank').attr("bank_id", data.bank_id);
    });


    $(document).on("click","#btn-delete-bank",function(){
        $.ajax({
            url:"/deleteBank",
            data:{
                bank_id:$('#btn-delete-bank').attr('bank_id'),
                _token:token
            },
            method:"post",
            success:function(e){
                $('.modal').modal("hide");
                refreshbank();
                notifikasi('success', "Berhasil Delete", "Berhasil delete Bank");
                
            },
            error:function(e){
                console.log(e);
            }
        });
    });
