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
        $('#supplies_variant').empty();
        $('#tbVariant').html("")
        addRow();
        $('#add_supplies').modal("show");
        $('#supplies_unit').trigger('change');
    });

    $(document).on('click','.btnAddRow',function(){
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
                <td style="width:15%;">
                    <select class="form-select supplier_id select2" name="" id="" style="width:100%;">
                    </select>
                </td>
                <td><input type="text" class="form-control fill variant_name" name="" id="" value="${names}"></td>
                <td><input type="text" class="form-control fill variant_sku" name="" id=""></td>
                <td><input type="text" class="form-control fill variant_price nominal_only" name="" id=""></td>
                <td><input type="text" class="form-control variant_barcode" name="" id="" placeholder=""><input type="hidden" class="form-control variant_id" name="" id="" placeholder=""></td>
                <td class="text-center d-flex align-items-center">
                    <a class="p-2 btn-action-icon btn_delete_row mx-auto"  href="javascript:void(0);">
                            <i data-feather="trash-2" class="feather-trash-2"></i>
                        </a>
                    </td>
                </tr>    
        `);
         feather.replace();
         autocompleteSupplier('.supplier_id','#add_supplies');
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
                { data: "supplies_name", width: "25%" },
                { data: "variant_values", width: "20%" },
                { data: "unit_values", width: "15%" },
                { data: "desc", width: "25%" },
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
                    e[i].units.forEach((element,index) => {
                         e[i].unit_values += element.unit_name;
                         if(index< e[i].units.length-1){
                            e[i].unit_values += ", ";
                         }
                    });
                    e[i].action = `
                        <a class="me-2 btn-action-icon p-2 btn_edit" data-id="${e[i].supplies_id}" data-bs-target="#edit-supplies">
                            <i class="fe fe-edit"></i>
                        </a>
                        <a class="p-2 btn-action-icon btn_delete" data-id="${e[i].supplies_id}" href="javascript:void(0);">
                            <i class="fe fe-trash-2"></i>
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
            supplies_supplier:JSON.stringify($('#supplies_supplier').val()),
            supplies_unit:JSON.stringify($('#supplies_unit').val()),
             _token:token
        };

        var temp=[];
        $('.row-variant').each(function(){
            var variant = {
                supplier_id: $(this).find('.supplier_id').val(),
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

    // function getUnit(unitName, callback) {
    //     $.ajax({
    //         url: "/getUnit",
    //         method: "get",
    //         headers: { "X-CSRF-TOKEN": token },
    //         data: { unit_name: unitName },
    //         success: function(resp) {
    //             console.log(unitName)
    //             console.log(resp)
    //             callback(resp[0].unit_id);
    //         }
    //     });
    // }

    // $('#supplies_unit').on('click', function() {
    //    $('.select2-search__field').remove();
    // });
    // $('#supplies_unit').on('change', function() {
    //    $('.select2-search__field').remove();
    // });

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
        $('#supplies_name').val(data.supplies_name);
        $('#supplies_desc').val(data.supplies_desc);
        $('#supplies_supplier').empty();
        $('#supplies_unit').empty();
        $('#supplies_unit').append(`<option value="${data.supplies_unit}" selected>${data.unit_name}</option>`);
        $('#supplies_unit').val(data.supplies_unit).trigger('change');
        $('#tbVariant').html("");
        data.sup_variant.forEach(element => {
            addRow(element.supplies_variant_name);
            $('.row-variant').last().find('.variant_sku').val(element.supplies_variant_sku);
            $('.row-variant').last().find('.variant_price').val(formatRupiah(element.supplies_variant_price));
            $('.row-variant').last().find('.variant_barcode').val(element.supplies_variant_barcode);
            $('.row-variant').last().find('.variant_id').val(element.supplies_variant_id);
            if(element.supplier_id)$('.row-variant').last().find('.supplier_id').append(`<option value="${element.supplier_id}" selected>${element.supplier_name}</option>`);
        });
        data.units.forEach(u => {
            $('#supplies_unit').append(
                `<option value="${u.unit_id}">${u.unit_short_name}</option>`
            );
        });
        if(data.supplier){
            data.supplier.forEach(u => {
                console.log(u);
                var newOption = new Option(u.supplier_name, u.supplier_id, true, true);
                $('#supplies_supplier').append(newOption).trigger("change");
            });
        }
        let unitIds = data.units.map(u => u.unit_id);
        $('#supplies_unit').val(unitIds).trigger('change');

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
