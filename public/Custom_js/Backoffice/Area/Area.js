    var mode=1;
    var table;
    $(document).ready(function(){
        inisialisasi();
        refreshArea();
    });
    
    $(document).on('click','.btnAdd',function(){
        mode=1;
        $('#add_area .modal-title').html("Tambah Wilayah");
        $('#add_area input').val("");
        $('.is-invalid').removeClass('is-invalid');
        $('.btn-save').html(mode == 1?"Tambah Wilayah" : "Update Wilayah");
        $('#add_area').modal("show");
    });
    
    function inisialisasi() {
        table = $('#tableArea').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Cari Wilayah",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "area_code" },
                { data: "area_name" },
                { data: "action", class: "d-flex align-items-center" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });
    }

    function refreshArea() {
        $.ajax({
            url: "/getArea",
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
                        <a class="me-2 btn-action-icon p-2 btn_edit" data-id="${e[i].area_id}" data-bs-target="#edit-area">
                            <i class="fe fe-edit"></i>
                        </a>
                        <a class="p-2 btn-action-icon btn_delete" data-id="${e[i].area_id}" href="javascript:void(0);">
                            <i class="fe fe-trash-2"></i>
                        </a>
                    `;
                }

                table.rows.add(e).draw();
                feather.replace(); // Biar icon feather muncul lagi
            },
            error: function (err) {
                console.error("Gagal load wilayah:", err);
            }
        });
    }

    $(document).on("click",".btn-save",function(){
       LoadingButton(this);
        $('.is-invalid').removeClass('is-invalid');
        var url ="/insertArea";
        var valid=1;

        $("#add_area .fill").each(function(){
            if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                valid=-1;
                $(this).addClass('is-invalid');
            }
        });

        if(valid==-1){
            notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
            ResetLoadingButton('.btn-save', mode == 1?"Tambah Wilayah" : "Update Wilayah"); 
            return false;
        };

        param = {
            area_name:$('#area_name').val(),
            area_code:$('#area_code').val(),
            _token:token
        };

        if(mode==2){
            url="/updateArea";
            param.area_id = $('#add_area').attr("area_id");
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
                ResetLoadingButton('.btn-save', mode == 1?"Tambah Wilayah" : "Update Wilayah");    
                afterInsert();
            },
            error:function(e){
                ResetLoadingButton('.btn-save', mode == 1?"Tambah Wilayah" : "Update Wilayah"); 
                console.log(e);
            }
        });
    });

    function afterInsert() {
        $(".modal").modal("hide");
        if(mode==1)notifikasi('success', "Berhasil Insert", "Berhasil Tambah Wilayah");
        else if(mode==2)notifikasi('success', "Berhasil Update", "Berhasil Update Wilayah");
        refreshArea();
    }

    // $(document).on("keyup","#filter_category_name",function(){
    //     refreshArea();
    // });
    //edit
    $(document).on("click",".btn_edit",function(){
        var data = $('#tableArea').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        mode=2;
        $('#add_area .modal-title').html("Update Wilayah");
        $('#add_area input').empty().val("");
        $('#area_code').val(data.area_code);
        $('#area_name').val(data.area_name);
        $('.is-invalid').removeClass('is-invalid');
        $('.btn-save').html(mode == 1?"Tambah Wilayah" : "Update Wilayah");
        $('#add_area').modal("show");
        $('#add_area').attr("area_id", data.area_id);
    });

    //delete
    $(document).on("click",".btn_delete",function(){
        var data = $('#tableArea').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        showModalDelete("Apakah yakin ingin menghapus wilayah ini?","btn-delete-area");
        $('#btn-delete-area').attr("area_id", data.area_id);
    });


    $(document).on("click","#btn-delete-area",function(){
        $.ajax({
            url:"/deleteArea",
            data:{
                area_id:$('#btn-delete-area').attr('area_id'),
                _token:token
            },
            method:"post",
            success:function(e){
                $('.modal').modal("hide");
                refreshArea();
                notifikasi('success', "Berhasil Delete", "Berhasil delete wilayah");
                
            },
            error:function(e){
                console.log(e);
            }
        });
    });
