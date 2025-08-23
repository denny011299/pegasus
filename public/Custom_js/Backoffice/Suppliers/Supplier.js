    var mode=1;
    var table;
    $(document).ready(function(){
        inisialisasi();
        refreshSupplier();
    });
    
    $(document).on('click','.btnAdd',function(){
        mode=1;
        $('#add_supplier .modal-title').html("Create Supplier");
        $('#add_supplier input').val("");
        $('.is-invalid').removeClass('is-invalid');
        $('#add_supplier').modal("show");
    });
    
    function inisialisasi() {
        table = $('#tableSupplier').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
            autoWidth: false,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Search Supplier",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "supplier_name" },
                { data: "unit_values" },
                { data: "supplier_desc" },
                { data: "supplier_stock" },
                { data: "action", class: "d-flex align-items-center" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });
    }

    function refreshSupplier() {
        $.ajax({
            url: "/getSupplier",
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }

                table.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].unit_values = "";
                    JSON.parse(e[i].supplier_unit).forEach((element,index) => {
                         e[i].unit_values += element;
                         if(index< JSON.parse(e[i].supplier_unit).length-1){
                            e[i].unit_values += ", ";
                         }
                    });
                    e[i].action = `
                        <a class="me-2 btn-action-icon p-2 btn_edit" data-id="${e[i].supplier_id}" data-bs-target="#edit-supplier">
                            <i data-feather="edit" class="feather-edit"></i>
                        </a>
                        <a class="p-2 btn-action-icon btn_delete" data-id="${e[i].supplier_id}" href="javascript:void(0);">
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
       LoadingButton(this);
        $('.is-invalid').removeClass('is-invalid');
        var url ="/insertSupplier";
        var valid=1;

        $("#add_supplier .fill").each(function(){
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
            supplier_name:$('#supplier_name').val(),
            supplier_desc:$('#supplier_desc').val(),
            supplier_unit:JSON.stringify($('#supplier_unit').val()),
             _token:token
        };

        if(mode==2){
            url="/updateSupplier";
            param.supplier_id = $('#add_supplier').attr("supplier_id");
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
        if(mode==1)notifikasi('success', "Successful Insert", "Successful Supplier Added");
        else if(mode==2)notifikasi('success', "Successful Update", "Successful Supplier Updated");
        refreshSupplier();
    }

    // $(document).on("keyup","#filter_supplier_name",function(){
    //     refreshSupplier();
    // });

    //edit
    $(document).on("click",".btn_edit",function(){
        var data = $('#tableSupplier').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        mode=2;
        $('#add_supplier .modal-title').html("Update Supplier");
        $('#add_supplier input').empty().val("");
        $('#supplier_unit').tagsinput('removeAll');
        $('#supplier_name').val(data.supplier_name);
        $('#supplier_desc').val(data.supplier_desc);
        data.supplier_unit.split(',').forEach(function(item) {
            $('#supplier_unit').tagsinput('add', item.trim());
        });

        $('#add_supplier').modal("show");
        $('#add_supplier').attr("supplier_id", data.supplier_id);
    });

    //delete
    $(document).on("click",".btn_delete",function(){
        var data = $('#tableSupplier').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        showModalDelete("Apakah yakin ingin mengahapus supplier ini?","btn-delete-supplier");
        $('#btn-delete-supplier').attr("supplier_id", data.supplier_id);
    });


    $(document).on("click","#btn-delete-supplier",function(){
        $.ajax({
            url:"/deleteSupplier",
            data:{
                supplier_id:$('#btn-delete-supplier').attr('supplier_id'),
                _token:token
            },
            method:"post",
            success:function(e){
                $('.modal').modal("hide");
                refreshSupplier();
                notifikasi('success', "Berhasil Delete", "Berhasil delete supplier");
                
            },
            error:function(e){
                console.log(e);
            }
        });
    });
