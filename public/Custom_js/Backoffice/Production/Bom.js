    autocompleteSupplies('#supplies_id', '#add_bom');
    autocompleteProduct('#product_id', '#add_bom');
    autocompleteUnit('#unit_id', '#add_bom');
    
    var mode=1;
    var table;
    $(document).ready(function(){
        inisialisasi();
        refreshBom();
    });
    
    $(document).on('click','.btnAdd',function(){
        mode=1;
        $('#add_bom .modal-title').html("Create Bill of Material");
        $('#add_bom input').val("");
        $('.is-invalid').removeClass('is-invalid');
        $('#add_bom').modal("show");
    });
    
    function inisialisasi() {
        table = $('#tableBom').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
            autoWidth: false,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Search Role",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "bom_id" },
                { data: "product_name" },
                { data: "bom_date" },
                { data: "action", class: "d-flex align-items-center" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });
    }

    function refreshBom() {
        $.ajax({
            url: "/getRole",
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                console.log(e);
                table.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].bom_date = moment(e[i].created_at).format('D MMM YYYY');
                    e[i].action = `
                        <a href="#" class="btn btn-greys btn_edit me-2" data-bs-toggle="modal"
                            data-bs-target="#edit_role"><i class="fa fa-edit me-1"></i> Edit Role</a>
                        <a href="/permission/${e[i].bom_id}" class="btn btn-greys me-2"><i
                            class="fa fa-shield me-1"></i> Permissions</a>
                    `;
                }

                table.rows.add(e).draw();
                feather.replace(); // Biar icon feather muncul lagi
            },
            error: function (err) {
                console.error("Gagal load bom:", err);
            }
        });
    }

    $(document).on("click",".btn-save",function(){
       LoadingButton(this);
        $('.is-invalid').removeClass('is-invalid');
        var url ="/insertBom";
        var valid=1;

        $("#add_bom .fill").each(function(){
            if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                valid=-1;
                $(this).addClass('is-invalid');
            }
        });

        if(valid==-1){
            notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
            ResetLoadingButton('.btn-save', 'Save changes');
            return false;
        };

        param = {
            bom_name:$('#bom_name').val(),
             _token:token
        };

        if(mode==2){
            url="/updateBom";
            param.bom_id = $('#add_bom').attr("bom_id");
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
                ResetLoadingButton(".btn-save", 'Save changes');      
                afterInsert();
            },
            error:function(e){
                ResetLoadingButton(".btn-save", 'Save changes');
                console.log(e);
            }
        });
    });

    function afterInsert() {
        $(".modal").modal("hide");
        if(mode==1)notifikasi('success', "Successful Insert", "Successful BoM Added");
        else if(mode==2)notifikasi('success', "Successful Update", "Successful BoM Updated");
        refreshBom();
    }

    $(document).on("keyup","#filter_bom_name",function(){
        refreshBom();
    });
    //edit
    $(document).on("click",".btn_edit",function(){
        var data = $('#tableBom').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        mode=2;
        $('#add_bom .modal-title').html("Update Role");
        $('#add_bom input').empty().val("");
        $('#bom_name').val(data.bom_name);

        $('#add_bom').modal("show");
        $('#add_bom').attr("bom_id", data.bom_id);
    });