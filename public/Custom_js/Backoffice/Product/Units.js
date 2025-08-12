    var mode=1;
    var table;
    $(document).ready(function(){
        inisialisasi();
        refreshUnits();
    });
    
    $(document).on('click','.btnAdd',function(){
        mode=1;
        $('#add_unit .modal-title').html("Create Unit");
        $('#add_unit input').val("");
        $('.is-invalid').removeClass('is-invalid');
        $('#add_unit').modal("show");
    });
    
    function inisialisasi() {
        table = $('#tableUnits').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Search Unit Name",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "unit_name" },
                { data: "unit_short_name" },
                { data: "product_count" },
                { data: "unit_date" },
                { data: "action", class: "d-flex align-items-center" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });
    }

    function refreshUnits() {
        $.ajax({
            url: "/getUnit",
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                console.log(e);
                table.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].unit_date = moment(e[i].created_at).format('D MMM YYYY');
                    e[i].action = `
                        <div class="edit-delete-action d-flex align-items-center">
                            <a class="btn-action-icon me-2 p-2 btn_edit" data-id="${e[i].unit_id}" data-bs-target="#edit-unit">
                                <i data-feather="edit" class="feather-edit"></i>
                            </a>
                            <a class="btn-action-icon p-2 btn_delete" data-id="${e[i].unit_id}" href="javascript:void(0);">
                                <i data-feather="trash-2" class="feather-trash-2"></i>
                            </a>
                        </div>
                    `;
                }

                table.rows.add(e).draw();
                feather.replace(); // Biar icon feather muncul lagi
            },
            error: function (err) {
                console.error("Gagal load unit:", err);
            }
        });
    }

    $(document).on("click",".btn-save",function(){
       // LoadingButton(this);
        $('.is-invalid').removeClass('is-invalid');
        var url ="/insertUnit";
        var valid=1;

        $("#add_unit .fill").each(function(){
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
            unit_name:$('#unit_name').val(),
            unit_short_name:$('#unit_short_name').val(),
             _token:token
        };

        if(mode==2){
            url="/updateUnit";
            param.unit_id = $('#add_unit').attr("unit_id");
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
        refreshUnits();
    }

    $(document).on("keyup","#filter_unit_name",function(){
        refreshUnits();
    });
    //edit
    $(document).on("click",".btn_edit",function(){
        var data = $('#tableUnits').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        mode=2;
        $('#add_unit .modal-title').html("Update Unit");
        $('#add_unit input').empty().val("");
        $('#unit_name').val(data.unit_name);
        $('#unit_short_name').val(data.unit_short_name);

        $('#add_unit').modal("show");
        $('#add_unit').attr("unit_id", data.unit_id);
    });

    //delete
    $(document).on("click",".btn_delete",function(){
        var data = $('#tableUnits').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        showModalDelete("Apakah yakin ingin mengahapus unit ini?","btn-delete-unit");
        //$('#deleteModal').modal("show");
        $('#btn-delete-unit').attr("unit_id", data.unit_id);
    });


    $(document).on("click","#btn-delete-unit",function(){
        $.ajax({
            url:"/deleteUnit",
            data:{
                unit_id:$('#btn-delete-unit').attr('unit_id'),
                _token:token
            },
            method:"post",
            success:function(e){
                $('.modal').modal("hide");
                refreshUnits();
                notifikasi('success', "Berhasil Delete", "Berhasil delete unit");
                
            },
            error:function(e){
                console.log(e);
            }
        });
    });
