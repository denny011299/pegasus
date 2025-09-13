    var mode=1;
    var table;
    var idUnits = [];
    $(document).ready(function(){
        inisialisasi();
        refreshSupplies();
        autocompleteUnit("#supplies_unit","#add_supplies");
        autocompleteVariant("#supplies_variant","#add_supplies");
    });
    
    $(document).on('click','.btnAdd',function(){
        mode=1;
        idUnits = [];
        $('#add_supplies .modal-title').html("Tambah Bahan Mentah");
        $('#add_supplies input').val("");
        $('#supplies_desc').val("");
        $('.is-invalid').removeClass('is-invalid');
        $('#supplies_unit').val(null);
        $('#tbVariant').html("")
        addRow();
        $('#add_supplies').modal("show");
        $('#supplies_unit').trigger('change');
    });

    $(document).on('click','.btnAddRow',function(){
        $('#tbVariant').html("")
        if($('#supplies_variant').val()!=""&&$('#supplies_variant').val()!=null) {
            var data = $('#supplies_variant').select2('data')[0];
            data.name = JSON.parse(data.variant_attribute);
            data.name.forEach(element => {
                addRow(element);    
            });
            console.log(data);
            $('#supplies_variant').empty();
        }
       else addRow();
    });
    function addRow(names="") {
        
        $('#tbVariant').append(`
            <tr class="row-variant">
                <td><input type="text" class="form-control variant_name" name="" id="" value="${names}"></td>
                <td><input type="text" class="form-control variant_sku" name="" id=""></td>
                <td><input type="text" class="form-control variant_price nominal_only" name="" id=""></td>
                <td><input type="text" class="form-control variant_barcode" name="" id="" placeholder=""><input type="hidden" class="form-control variant_id" name="" id="" placeholder=""></td>
                <td class="text-center d-flex align-items-center">
                    <a class="p-2 btn-action-icon btn_delete_row mx-auto"  href="javascript:void(0);">
                            <i data-feather="trash-2" class="feather-trash-2"></i>
                        </a>
                    </td>
                </tr>    
        `);
         feather.replace();
    }
    
    function inisialisasi() {
        table = $('#tableSupplies').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Cari Bahan Mentah",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "supplies_name" },
                { data: "unit_values" },
                { data: "variant_values" },
                { data: "desc" },
                { data: "supplies_stock" },
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
                    console.log(e[1]);
                    if (e[i].supplies_desc == null) e[i].desc = '-';
                    else e[i].desc = e[i].supplies_desc;
                    e[i].variant_values = "";
                    e[i].sup_variant.forEach((element,index) => {
                         e[i].variant_values += element.supplies_variant_name;
                         if(index< e[i].sup_variant.length-1){
                            e[i].variant_values += ", ";
                         }
                    });
                    e[i].unit_values = "";
                    e[i].sup_unit.forEach((element,index) => {
                         e[i].unit_values += element.unit_name;
                         if(index< e[i].sup_unit.length-1){
                            e[i].unit_values += ", ";
                         }
                    });
                    e[i].action = `
                        <a class="me-2 btn-action-icon p-2 btn_edit" data-id="${e[i].supplies_id}" data-bs-target="#edit-supplies">
                            <i data-feather="edit" class="feather-edit"></i>
                        </a>
                        <a class="p-2 btn-action-icon btn_delete" data-id="${e[i].supplies_id}" href="javascript:void(0);">
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

    $(document).on('change', '#supplies_unit', function(){
        let selectedUnits = $(this).val(); // ambil array value dari multiselect
        idUnits = [];
        selectedUnits.forEach(function(id) {
            idUnits.push(id);
        });
        console.log(selectedUnits);

        // kosongkan dulu container biar gak dobel2
        $(".relationContainer").empty(); 

        // loop data terpilih
        selectedUnits.forEach((item, index) => {
            let html = '';
            let nextItem = (index + 1 < selectedUnits.length) ? selectedUnits[index + 1] : "-"; 
            
            html = `
            <div class="col-2 pb-3">
                <label id="pu_id_${index+1}">${item}</label>
                <input type="text" class="form-control fill" id="supplies_stock${index+1}" 
                placeholder="Input Stok">
            </div>
            `;
            if (nextItem != '-'){
                html += `
                    <div class="col-1 pt-4 fs-3 px-0 mx-0 text-center">
                    =
                    </div>
                `;
            }
            $(".relationContainer").append(html);
        });
    })

    $(document).on("click",".btn-save",function(){
       LoadingButton(this);
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
            notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
            ResetLoadingButton('.btn-save', 'Simpan Perubahan');
            return false;
        };

        param = {
            supplies_name:$('#supplies_name').val(),
            supplies_desc:$('#supplies_desc').val(),
            supplies_unit:JSON.stringify($('#supplies_unit').val()),
             _token:token
        };

        var temp=[];
        $('.row-variant').each(function(){
            var variant = {
                supplies_variant_name: $(this).find('.variant_name').val(),
                supplies_variant_sku: $(this).find('.variant_sku').val(),
                supplies_variant_price: convertToAngka($(this).find('.variant_price').val()),
                supplies_variant_barcode: $(this).find('.variant_barcode').val(),
                supplies_variant_id: $(this).find('.variant_id').val(),
            };
            temp.push(variant);
        });
        param.supplies_variant = JSON.stringify(temp);

        if(mode==2){
            url="/updateSupplies";
            param.supplies_id = $('#add_supplies').attr("supplies_id");
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
        if(mode==1)notifikasi('success', "Berhasil Insert", "Berhasil Tambah Bahan Mentah");
        else if(mode==2)notifikasi('success', "Berhasil Update", "Berhasil Update Bahan Mentah");
        refreshSupplies();
    }

    function getUnit(unitName, callback) {
        $.ajax({
            url: "/getUnit",
            method: "get",
            headers: { "X-CSRF-TOKEN": token },
            data: { unit_name: unitName },
            success: function(resp) {
                console.log(unitName)
                console.log(resp)
                callback(resp[0].unit_id);
            }
        });
    }

    $('#supplies_unit').on('click', function() {
       $('.select2-search__field').remove();
    });
    $('#supplies_unit').on('change', function() {
       $('.select2-search__field').remove();
    });

    $(document).on("click",".btn_delete_row",function(){
        if($('.row-variant').length<2) {
            notifikasi('error', "Gagal Hapus", "Minimal 1 varian harus ada");
            return false;
        }
        $(this).closest("tr").remove();
    });

    // $(document).on("keyup","#filter_supplies_name",function(){
    //     refreshSupplies();
    // });

    //edit
    $(document).on("click",".btn_edit",function(){
        var data = $('#tableSupplies').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        console.log(data);
        mode=2;
        idUnits = [];
        $('#add_supplies .modal-title').html("Update Bahan Mentah");
        $('#add_supplies input').empty().val("");
        $('.is-invalid').removeClass('is-invalid');
        $('#supplies_unit').val(null);
        $('#supplies_name').val(data.supplies_name);
        $('#supplies_desc').val(data.supplies_desc);
        $('#tbVariant').html("");
        data.sup_variant.forEach(element => {
            addRow(element.supplies_variant_name);
            $('.row-variant').last().find('.variant_sku').val(element.supplies_variant_sku);
            $('.row-variant').last().find('.variant_price').val(formatRupiah(element.supplies_variant_price));
            $('.row-variant').last().find('.variant_barcode').val(element.supplies_variant_barcode);
            $('.row-variant').last().find('.variant_id').val(element.supplies_variant_id);
        });
        data.sup_unit.forEach(element => {
            var newOption = new Option(element.unit_short_name, element.unit_id, true, true);
            $('#supplies_unit').append(newOption).trigger('change');
        });


        // $.ajax({
        //     url: "/getSuppliesRelation",
        //     method: "get",
        //     data: { supplies_id: data.supplies_id },
        //     headers: { "X-CSRF-TOKEN": token },
        //     success: function(resp){
        //         // resp misalnya [{su_id_1, su_id_2, sr_value_1, sr_value_2}]
        //         $(".relationContainer").empty();

        //         // render input stok berdasarkan units
        //         units.forEach((item, index) => {
        //             let html = '';
        //             html = `
        //                 <div class="col-2 pb-3">
        //                     <label id="pu_id_${index+1}">${item}</label>
        //                     <input type="text" class="form-control fill" id="supplies_stock${index+1}" 
        //                     placeholder="Input Stock">
        //                 </div>
        //             `;
        //             if (index < units.length - 1) {
        //                 html += `
        //                     <div class="col-1 pt-4 fs-3 px-0 mx-0 text-center">
        //                         =
        //                     </div>
        //                 `;
        //             }
        //             $(".relationContainer").append(html);
        //         });

        //         // isi nilai stock sesuai relasi
        //         if(resp && resp.length > 0){
        //             let total = 1;
        //             for (let i = 0; i < resp.length; i++) {
        //                 let nilai1 = resp[i].sr_value_1;
        //                 let nilai2 = resp[i].sr_value_2;

        //                 $(`#supplies_stock${i+1}`).val(nilai1 * total);
        //                 total = total * nilai2;
        //                 $(`#supplies_stock${i+2}`).val(total);
        //             }
        //         }
        //     }
        // });
        $('.btn-save').html('Simpan perubahan');
        $('#add_supplies').modal("show");
        $('#add_supplies').attr("supplies_id", data.supplies_id);
    });

    //delete
    $(document).on("click",".btn_delete",function(){
        var data = $('#tableSupplies').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        showModalDelete("Apakah yakin ingin mengahapus bahan mentah ini?","btn-delete-supplies");
        $('#btn-delete-supplies').attr("supplies_id", data.supplies_id);
    });


    $(document).on("click","#btn-delete-supplies",function(){
        $.ajax({
            url:"/deleteSupplies",
            data:{
                supplies_id:$('#btn-delete-supplies').attr('supplies_id'),
                _token:token
            },
            method:"post",
            success:function(e){
                $('.modal').modal("hide");
                refreshSupplies();
                notifikasi('success', "Berhasil Delete", "Berhasil delete bahan mentah");
                
            },
            error:function(e){
                console.log(e);
            }
        });
    });
