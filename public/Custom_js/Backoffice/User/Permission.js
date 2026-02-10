
    var table = $('.datatable').DataTable();
    
    $(document).ready(function(){
        console.log(perm)
        $('.role_name').html(data.role_name)
        if(perm.length>0){
            perm.forEach(item => {
                $(table.rows().nodes()).each(function(){
                    console.log(item.name)
                    console.log($(this).attr("module"))
                    if(item.name == $(this).attr("module")){
                      
                        if(item.akses.length==4){
                            $(this).find(".all").prop("checked",true).trigger("change");
                            item.akses.forEach(element => {
                                $(this).find("."+element).prop("checked",true);
                            });
                        }
                        else{
                            item.akses.forEach(element => {
                                $(this).find("."+element).prop("checked",true);
                            });
                        }
                    }
                }) 
            });
        }

    });
    
       
    $(document).on("change",".all",function(){
        var isChecked = $(this).is(':checked');
        $(this).closest('.row-module').find('input[type="checkbox"]').prop('checked', isChecked);
    });

    $(document).on("change",".all_check",function(){
        var isChecked = $(this).is(":checked");
        table.rows().every(function () {
            $(this.node()).find("input[type=checkbox]").prop("checked", isChecked);
        });
    });

    $(document).on("change", ".table input[type=checkbox]", function () {
        if (!$(this).is(":checked")) {
            $(".all_check").prop("checked", false);
        } else {
            let allNodes = table.rows().nodes();
            let total = $(allNodes).find("input[type=checkbox]").length;
            let checked = $(allNodes).find("input[type=checkbox]:checked").length;
            if (total === checked) {
                $(".all_check").prop("checked", true);
            }
        }
    });

    $(document).on("click",".btn-save",function(){
       LoadingButton(this);
        $('.is-invalid').removeClass('is-invalid');
        var valid=1;

        perm= [];
        $(table.rows().nodes()).each(function(){

            if($(this).find(".checkbox").is(":checked")){
                var param = {
                    "name":$(this).attr("module"),
                    "akses":[]
                };

                if($(this).find('.create').is(":checked")) param.akses.push("create");
                if($(this).find('.edit').is(":checked")) param.akses.push("edit");
                if($(this).find('.delete').is(":checked")) param.akses.push("delete");
                if($(this).find('.view').is(":checked")) param.akses.push("view");
                if($(this).find('.others').is(":checked")) param.akses.push("others");
                valid=1;
                perm.push(param);
            }
            
        });

        if(perm.length<=0) valid=-1;

        if(valid==-1){
            notifikasi('error', "Gagal Insert", 'Silahkan pilih minimal 1 permissions yang ingin ditambahkan');
            ResetLoadingButton('.btn-save', 'Perbarui');
            return false;
        };
        

        param = {
            role_id:data.role_id,
            role_name:data.role_name,
            role_access:JSON.stringify(perm),
            _token:token
        };


        LoadingButton($(this));
        $.ajax({
            url:"/updatePermission",
            data: param,
            method:"post",
            headers: {
                'X-CSRF-TOKEN': token
            },
            success:function(e){      
                ResetLoadingButton(".btn-save", 'Perbarui');      
                afterInsert();
            },
            error:function(e){
                ResetLoadingButton(".btn-save", 'Perbarui');
                console.log(e);
            }
        });
    });

    function afterInsert() {
        $(".modal").modal("hide");
        notifikasi('success', "Berhasil Update", "Berhasil Update Perizinan");
        window.open('/role', "_self");
    }
