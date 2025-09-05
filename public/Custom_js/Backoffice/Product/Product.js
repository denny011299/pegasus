    var mode=1;
    var table;
    
    autocompleteVariant("#product_variant","#add_product");
    autocompleteCategory("#product_category","#add_product");
    autocompleteUnit("#product_unit","#add_product");

    $(document).ready(function(){
        inisialisasi();
        refreshProduct();
    });
    
    $(document).on('click','.btnAdd',function(){
        mode=1;
        $('#add_product .modal-title').html("Create Product");
        $('#add_product input').val("");
        $('#add_product #product_unit').empty();
        $('#add_product #product_category').empty();
        $('.is-invalid').removeClass('is-invalid');
        $('#tbVariant').html("")
         addRow();
         
        $('#add_product').modal("show");
    });

    $(document).on('click','.btnAddRow',function(){
        if($('#product_variant').val()!=""&&$('#product_variant').val()!=null) {
            var data = $('#product_variant').select2('data')[0];
            data.name = JSON.parse(data.variant_attribute);
            data.name.forEach(element => {
                addRow(element);    
            });
            $('#product_variant').empty();
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
        table = $('#tableProduct').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Search Product",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "product_name" },
                { data: "product_category" },
                { data: "unit_values" },
                { data: "variant_values" },
                { data: "action", class: "d-flex align-items-center" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });
         feather.replace();
    }

    function refreshProduct() {
        $.ajax({
            url: "/getProduct",
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }

                table.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].variant_values = "";
                    e[i].pr_variant.forEach((element,index) => {
                         e[i].variant_values += element.product_variant_name;
                         if(index< e[i].pr_variant.length-1){
                            e[i].variant_values += ", ";
                         }
                    });
                    e[i].unit_values = "";
                    e[i].pr_unit.forEach((element,index) => {
                         e[i].unit_values += element.unit_name;
                         if(index< e[i].pr_unit.length-1){
                            e[i].unit_values += ", ";
                         }
                    });
                    e[i].action = `
                        <a class="me-2 btn-action-icon p-2 btn_edit" data-id="${e[i].pr_id}" data-bs-target="#edit-category">
                            <i data-feather="edit" class="feather-edit"></i>
                        </a>
                        <a class="p-2 btn-action-icon btn_delete" data-id="${e[i].pr_id}" href="javascript:void(0);">
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
       // LoadingButton(this);
        $('.is-invalid').removeClass('is-invalid');
        var url ="/insertProduct";
        var valid=1;

        $("#add_product .fill").each(function(){
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
             product_name:$('#product_name').val(),
             category_id:$('#product_category').val(),
             product_unit:JSON.stringify($('#product_unit').val()),
             _token:token
        };
        var temp=[];
        $('.row-variant').each(function(){
            
            var variant = {
                variant_name: $(this).find('.variant_name').val(),
                variant_sku: $(this).find('.variant_sku').val(),
                variant_price: convertToAngka($(this).find('.variant_price').val()),
                variant_barcode: $(this).find('.variant_barcode').val(),
                product_variant_id: $(this).find('.variant_id').val(),
            };
            temp.push(variant);
        });
        param.product_variant = JSON.stringify(temp);


        if(mode==2){
            url="/updateProduct";
            param.product_id = $('#add_product').attr("product_id");
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
        refreshProduct();
    }

    // $(document).on("keyup","#filter_category_name",function(){
    //     refreshProduct();
    // });

    //edit
    $(document).on("change","#product_unit",function(){
        var data = $(this).select2("data");
        $('#unit1').html("");
        data.forEach(element => {
            $('#unit1').append(`<li><a class="dropdown-item" href="#"></a></li>`);
        });
    });

    $(document).on("click",".btn_edit",function(){
        var data = $('#tableProduct').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        mode=2;
        $('#add_product .modal-title').html("Update Product");
        $('#add_product input').empty().val("");

        $('#product_name').val(data.product_name);
        $('#product_category').append(`<option value="${data.category_id}">${data.category_name}</option>`);
        // $('#category_name').val(data.category_name);
        $('#tbVariant').html("")
        data.pr_variant.forEach(element => {
            addRow(element.product_variant_name);
            $('.row-variant').last().find('.variant_sku').val(element.product_variant_sku);
            $('.row-variant').last().find('.variant_price').val(formatRupiah(element.product_variant_price));
            $('.row-variant').last().find('.variant_barcode').val(element.product_variant_barcode);
            $('.row-variant').last().find('.variant_id').val(element.product_variant_id);
        });
        $('#product_unit').empty();
        data.pr_unit.forEach(element => {
           const newOption = new Option(element.unit_name, element.unit_id, false, false);
           $('#product_unit').append(newOption).trigger('change');
        });
        $('#add_product').modal("show");
        // $('#add_product').attr("category_id", data.category_id);
    });
    
    //delete
    $(document).on("click",".btn_delete",function(){
        var data = $('#tableProduct').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        showModalDelete("Apakah yakin ingin mengahapus product ini?","btn-delete-product");
        $('#btn-delete-product').attr("product_id", data.product_id);
        $('#modalDelete').modal("show");
    });

    $(document).on("click",".btn_delete_row",function(){
        if($('.row-variant').length<2) {
            notifikasi('error', "Gagal Hapus", "Minimal 1 varian harus ada");
            return false;
        }
        $(this).closest("tr").remove();

    });


    $(document).on("click","#btn-delete-product",function(){
        $.ajax({
            url:"/deleteProduct",
            data:{
                product_id:$('#btn-delete-product').attr('product_id'),
                _token:token
            },
            method:"post",
            success:function(e){
                $('.modal').modal("hide");
                refreshProduct();
                notifikasi('success', "Berhasil Delete", "Berhasil delete category");
                
            },
            error:function(e){
                console.log(e);
            }
        });
    });
