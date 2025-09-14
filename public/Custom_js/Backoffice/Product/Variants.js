    var mode=1;
    var table;
    $(document).ready(function(){
        inisialisasi();
        refreshVariant();
    });
    
    $(document).on('click','.btnAdd',function(){
        mode=1;
        $('#add_variant .modal-title').html("Tambah Variasi");
        $('#add_variant input').val("");
        $('.is-invalid').removeClass('is-invalid');
        $('#variant_attribute').tagsinput('removeAll');
        $('#add_variant').modal("show");
    });
    
    function inisialisasi() {
        table = $('#tableVariant').DataTable({
			"bFilter": true,
			"sDom": 'fBtlpi',  
			"ordering": true,
			"language": {
				search: ' ',
				sLengthMenu: '_MENU_',
				searchPlaceholder: "Cari Variasi",
				info: "_START_ - _END_ of _TOTAL_ items",
				paginate: {
					next: ' <i class=" fa fa-angle-right"></i>',
					previous: '<i class="fa fa-angle-left"></i> '
				},
			 },
            columns: [
                { data: "variant_name"},
                { data: "variant_values"},
                { data: "variant_date" },
                { data: "action",class:"d-flex align-items-center", },
            ],
			initComplete: (settings, json)=>{
				$('.dataTables_filter').appendTo('#tableSearch');
				$('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
			},	
		});
    }
    function refreshVariant() {
        var url = "/getVariant";
        $.ajax({
            url:url,
            method:"get",
            success:function(e){      
                table.clear().draw(); 
                console.log(e);
                
                for (let i = 0; i < e.length; i++) {
                    e[i].variant_date = moment(e[i].created_at).format('D MMM YYYY');
                    e[i].variant_values = "";
                    JSON.parse(e[i].variant_attribute).forEach((element,index) => {
                         e[i].variant_values += element;
                         if(index< JSON.parse(e[i].variant_attribute).length-1){
                            e[i].variant_values += ", ";
                         }
                    });
                    e[i].action = `
                            <a class="me-2 btn-action-icon p-2 btn_edit" data-id="${e[i].variant_id}" data-bs-target="#edit-unit">
                                <i class="fe fe-edit"></i>
                            </a>
                            <a class="p-2 btn-action-icon btn_delete" data-id="${e[i].variant_id}" href="javascript:void(0);">
                                <i class="fe fe-trash-2"></i>
                            </a>
                    `;
                }
                
                table.rows.add(e).draw();
                feather.replace();
            },
            error:function(e){
                ResetLoadingButton(".btn-save", 'Simpan Perubahan');
                console.log(e);
            }
        });
    }

    $(document).on("click",".btn-save",function(){
       LoadingButton(this);
        $('.is-invalid').removeClass('is-invalid');
        var url ="/insertVariant";
        var valid=1;

        $("#add_variant .fill").each(function(){
            if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                valid=-1;
                $(this).addClass('is-invalid');
            }
        });

        if(valid==-1){
            notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
            ResetLoadingButton('.btn-save', 'Simpan Perubahan');
            return false;
        };
        console.log($('#variant_attribute').val());
        
        param = {
            variant_name:$('#variant_name').val(),
            variant_attribute:JSON.stringify($('#variant_attribute').val()),
             _token:token
        };

        if(mode==2){
            url="/updateVariant";
            param.variant_id = $('#add_variant').attr("variant_id");
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
                ResetLoadingButton(".btn-save", 'Simpan Perubahan');      
                afterInsert();
            },
            error:function(e){
                ResetLoadingButton(".btn-save", 'Simpan Perubahan');
                console.log(e);
            }
        });
    });

    function afterInsert() {
        $(".modal").modal("hide");
        if(mode==1)notifikasi('success', "Berhasil Insert", "Berhasil Tambah Variasi");
        else if(mode==2)notifikasi('success', "Berhasil Update", "Berhasil Update Variasi");
        refreshVariant();
    }

    $(document).on("keyup","#filter_Unit_name",function(){
        refreshVariant();
    });
    //edit
    $(document).on("click",".btn_edit",function(){
        var data = $('#tableVariant').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        mode=2;
        $('#add_variant .modal-title').html("Update Variasi");
        $('#add_variant input').empty().val("");
        $('#variant_name').val(data.variant_name);
        $('.is-invalid').removeClass('is-invalid');
        $('#variant_attribute').tagsinput('removeAll');
        data.variant_values.split(',').forEach(function(item) {
            $('#variant_attribute').tagsinput('add', item.trim());
        });
        $('.btn-save').html('Simpan perubahan');
        $('#add_variant').modal("show");
        $('#add_variant').attr("variant_id", data.variant_id);
    });

    //delete
    $(document).on("click",".btn_delete",function(){
        var data = $('#tableVariant').DataTable().row($(this).parents('tr')).data();//ambil data dari table
         showModalDelete("Apakah yakin ingin menghapus variasi ini?","btn-delete-variant");
        $('#btn-delete-variant').attr("variant_id", data.variant_id);
    });


    $(document).on("click","#btn-delete-variant",function(){
        $.ajax({
            url:"/deleteVariant",
            data:{
                variant_id:$('#btn-delete-variant').attr('variant_id'),
                _token:token
            },
            method:"post",
            success:function(e){
                $('.modal').modal("hide");
                refreshVariant();
                notifikasi('success', "Berhasil Delete", "Berhasil delete variasi");
                
            },
            error:function(e){
                console.log(e);
            }
        });
    });
