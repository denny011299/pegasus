    var tableReturn, tableDamage;
    var mode = 1; //1 = insert, 2 = edit, 3 = view
    var type = 1; //1 = all, 2 = In, 3 = Out
    var items = [];
    var ids = []; // Untuk get data dari invoice (by po_id)
    var suppliesIds = []; // untuk get data dari PO detail (by supplies_variant_id)

    $(document).ready(function(){
        inisialisasi();
        refreshProductIssues();
    });
    $(document).on("click", ".nav-jenis", function () {
        type = $(this).attr("tipe");
        afterInsert();
    });
    //   $(document).on("change", "#product_id" , function () {
    //     var data = $(this).select2("data")[0];
    //     console.log(data);
    //     if(data){
    //          $('#unit_id').html("");
    //          if (data.pr_unit){
    //              data.pr_unit.forEach(element => {
    //                   $('#unit_id').append(`<option value="${element.unit_id}">${element.unit_name}</option>`) 
    //              });
    //          }
    //          $('#unit_id').val(data.unit_id).trigger("change");
    //           $('#pi_unit option').first().prop('selected', true);
    //     }
       
    // });

    $(document).on('click','.btnAdd',function(){
        mode=1;
        items = [];
        ids = [];
        suppliesIds = [];
        $('#add-product-issues .modal-title').html("Tambah Produk Bermasalah");
        $('#add-product-issues input').val("");
        $('#pi_type').html(`
            <option value="1">Dikembalikan</option>
            <option value="2" selected>Rusak</option>    
        `);
        $('#supplies_id').empty();
        $('#tableProduct tr.row-product').remove();
        $('#tableProduct tr.row-supplies').remove();
        $('#add-product-issues select2').empty();
        $('#tipe_return').val(1).trigger('change');
        // $('#add-product-issues select').val(1);

        $('.is-invalid').removeClass('is-invalid');
        $("#pi_date, #pi_type, #pi_notes, #tipe_return, #ref_num").prop("disabled", false);
        $('.add, .btn-save, .btn_delete_row_pr, .btn_delete_row_sp').show();
        $('.btn-save').html(mode == 1?"Tambah Produk" : "Update Produk");
        $('.cancel-btn').html(mode == 3?"Kembali" : "Batal");
        $("#product_id,#supplies_id,#unit_id").prop("disabled", false);
        
        $('#btn-foto-bukti').show();
        $('#btn-lihat-bukti').hide();
        $('#check_foto').hide();

        let today = new Date();
        let yyyy = today.getFullYear();
        let mm = String(today.getMonth() + 1).padStart(2, '0');
        let dd = String(today.getDate()).padStart(2, '0');
        let todayStr = dd + '-' + mm + '-' + yyyy;
        $("#pi_date").val(todayStr);

        $('#add-product-issues').modal("show");
    });

    function inisialisasi() {
        tableReturn = $('#tableReturn').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: false,
            autoWidth: false,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Cari Produk Dikembalikan",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "date", width: "15%" },
                { data: "pi_code", class: "width: 15%" },
                { data: "pi_notes", width: "20%" },
                { data: "action", class: "d-flex align-items-center" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });

        tableDamage = $('#tableDamage').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: false,
            autoWidth: false,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Cari Produk Rusak",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "date", class: "width: 15%" },
                { data: "pi_code", class: "width: 15%" },
                { data: "ref_num_text", width: "10%"},
                { data: "pi_notes", class: "width: 20%" },
                { data: "action", class: "d-flex align-items-center" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });
    }

    function refreshProductIssues() {
        $.ajax({
            url: "/getProductIssue",
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                console.log(e);
                // Manipulasi data sebelum masuk ke tabel
                e.forEach(item => {
                    item.date = moment(item.pi_date).format('D MMM YYYY');
                    item.ref_num_text = item.poi_code || "-";
                    item.action = `
                        <a class="me-2 btn-action-icon p-2 btn_view" data-id="${item.product_id}">
                            <i class="fe fe-eye"></i>
                        </a>
                        <a class="me-2 btn-action-icon p-2 btn_edit" data-id="${item.product_id}">
                            <i class="fe fe-edit"></i>
                        </a>
                        <a class="p-2 btn-action-icon btn_delete" data-id="${item.product_id}" href="javascript:void(0);">
                            <i class="fe fe-trash-2"></i>
                        </a>
                    `;
                });
                console.log(e);
                
                let returnProduct = e.filter(item => item.pi_type == 1);
                let damageProduct = e.filter(item => item.pi_type == 2);
                tableReturn.clear().rows.add(returnProduct).draw();
                tableDamage.clear().rows.add(damageProduct).draw();
                
                feather.replace(); // Biar icon feather muncul lagi
            },
            error: function (err) {
                console.error("Gagal load:", err);
            }
        });
    }

    function afterInsert() {
       refreshProductIssues();
       ResetLoadingButton('.btn-save', mode == 1?"Tambah Produk" : "Update Produk"); 
    }

    $(document).on('change','#product_id',function(){
        var data = $(this).select2("data")[0];
        console.log(data);
        $('#unit_product_id').empty();
        
        data.pr_unit.forEach(element => {
            $('#unit_product_id').append(`<option value="${element.unit_id}">${element.unit_name}</option>`);
        });
    });
    $(document).on('change','#supplies_id',function(){
        var data = $(this).select2("data")[0];
        console.log(data);
        $('#unit_supplies_id').empty();
        
        data.supplies_unit.forEach(element => {
            $('#unit_supplies_id').append(`<option value="${element.unit_id}">${element.unit_name}</option>`);
        });
    });

    $(document).on('change', '#tipe_return', function(){
        items = [];
        ids = [];
        suppliesIds = [];
        $('#ref_num').empty();
        if ($(this).val() == 1) {
            $(".input_table").html(`
                <div class="col-12 col-lg-4 add">
                    <div class="input-block mb-3" id="row-supplies">
                        <label>Nama Bahan Mentah<span class="text-danger">*</span></label>
                        <select class="form-select fill_supplies" id="supplies_id"></select>
                    </div>
                </div>
                <div class="col-6 col-lg-3 add">
                    <div class="input-block mb-3">
                        <label>Qty<span class="text-danger">*</span></label>
                        <input type="text"
                            class="form-control fill_supplies number-only"
                            id="pid_qty"
                            placeholder="Qty Bahan">
                    </div>
                </div>
                <div class="col-6 col-lg-4 add">
                    <div class="input-block mb-3">
                        <label>Nama Satuan<span class="text-danger">*</span></label>
                        <select class="form-select fill_supplies" id="unit_supplies_id"></select>
                    </div>
                </div>
                <div class="col-12 col-md-12 col-lg-1 add">
                    <button type="button" class="btn btn-primary w-100 btn-add-supplies mb-3">
                        +
                    </button>
                </div>    
            `);
            autocompleteSuppliesVariantOnly("#supplies_id", "#add-product-issues");
            autocompletePO("#ref_num", "#add-product-issues");
            $('#pi_type').val(2);
            $('#tableProduct tr.row-supplies').remove();
            $('#tableProduct tr.row-product').remove();
            $('#tableProduct #header_name').html("Nama Bahan Mentah");
            // Untuk show ref Invoice PO
            $('.ref').show();
            $("#ref_num").addClass("fill");
        }
        else if ($(this).val() == 2) {
            $(".input_table").html(`
                <div class="col-12 col-lg-4 add">
                    <div class="input-block mb-3" id="row-product">
                        <label>Nama Produk<span class="text-danger">*</span></label>
                        <select class="form-select fill_product" id="product_id"></select>
                    </div>
                </div>
                <div class="col-6 col-lg-3 add">
                    <div class="input-block mb-3">
                        <label>Qty<span class="text-danger">*</span></label>
                        <input type="text"
                            class="form-control fill_product number-only"
                            id="pid_qty"
                            placeholder="Qty Produk">
                    </div>
                </div>
                <div class="col-6 col-lg-4 add">
                    <div class="input-block mb-3">
                        <label>Nama Satuan<span class="text-danger">*</span></label>
                        <select class="form-select fill_product" id="unit_product_id"></select>
                    </div>
                </div>
                <div class="col-12 col-md-12 col-lg-1 add">
                    <button type="button" class="btn btn-primary w-100 btn-add-product mb-3">
                        +
                    </button>
                </div>    
            `);
            autocompleteProductVariantOnly("#product_id", "#add-product-issues");
            $('#pi_type').val(1);
            $('#tableProduct tr.row-supplies').remove();
            $('#tableProduct tr.row-product').remove();
            $('#tableProduct #header_name').html("Nama Produk");
            // Untuk hide ref Invoice PO
            $('.ref').hide();
            $("#ref_num").removeClass("fill");
        }
        loadPiType();
    })

    
function loadPiType() {
    $("#pi_type").empty();
    if ($("#tipe_return").val() == 1) {
        $("#pi_type").append(`<option value="2" selected>Rusak</option>`);
    }
    if ($("#tipe_return").val() == 2) {
        $("#pi_type").append(`<option value="1" selected>Dikembalikan</option>`);
    }
}


    $(document).on("click", ".btn-save", function () {
        //LoadingButton(this);
        $(".is-invalid").removeClass("is-invalid");
        var url = "/insertProductIssues";
        var valid = 1;
        $(".fill").each(function () {
            if (
                $(this).val() == null ||
                $(this).val() == "null" ||
                $(this).val() == ""
            ) {
                valid = -1;
                $(this).addClass("is-invalid");
            }
        });

        if (valid == -1) {
            notifikasi(
                "error",
                "Gagal Insert",
                "Silahkan cek kembali inputan anda"
            );
            ResetLoadingButton('.btn-save', mode == 1?"Tambah Produk" : "Update Produk"); 
            return false;
        }

        if ($("#tableProduct tbody tr").length == 0) {
            notifikasi('error', "Gagal Insert", 'Minimal input 1 produk');
            ResetLoadingButton('.btn-save', mode == 1?"Tambah Produk" : "Update Produk"); 
            return false;
        }

        if ($('#bukti').val() == ""|| $('#bukti').val() == null || $('#bukti').val() == "null"){
            notifikasi('error', "Gagal Insert", 'Harus ada 1 bukti foto');
            ResetLoadingButton('.btn-save', mode == 1?"Tambah Produk" : "Update Produk");
            return false;
        }

        param = {
            pi_date: $("#pi_date").val(),
            pi_type: $("#pi_type").val(),
            ref_num: $("#ref_num").val(),
            pi_notes: $("#pi_notes").val(),
            tipe_return: $("#tipe_return").val(),
            photo:$('#bukti').val(),
            items: JSON.stringify(items),
            _token: token,
        };
        console.log(param);
        LoadingButton($(this));

        if (mode == 2) {
            url = "/updateProductIssues";
            param.pi_id = $("#add-product-issues").attr("pi_id");
            param.pi_code = $("#add-product-issues").attr("pi_code");
        }

        $.ajax({
            url: url,
            data: param,
            method: "post",
            headers: {
                "X-CSRF-TOKEN": token,
            },
            success: function (e) {
                console.log(e);
                if (typeof e === "object") {
                    notifikasi(
                        "error",
                        "Gagal Insert",
                        e.message
                    );
                }
                else if (e == -1)
                    notifikasi(
                        "error",
                        "Gagal Insert",
                        "Stock tidak mencukupi!"
                    );
                else {
                    $(".modal").modal("hide");
                    if (mode == 1)
                        notifikasi(
                            "success",
                            "Berhasil Insert",
                            "Berhasil Data Ditambahkan"
                        );
                    else if (mode == 2)
                        notifikasi(
                            "success",
                            "Berhasil Update",
                            "Berhasil Data Diupdate"
                        );
                }

                afterInsert();
            },
            error: function (e) {
                console.log(e);
                ResetLoadingButton('.btn-save', mode == 1?"Tambah Produk" : "Update Produk"); 
            },
        });
    });

    $(document).on('click', '.btn-add-product', function(){
        $('.is-invalid').removeClass('is-invalid');
        $('.is-invalids').removeClass('is-invalids');
        var valid=1;
        $("#add-product-issues .fill_product").each(function(){
            if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                valid=-1;
                $(this).addClass('is-invalid');
            }
        });
        if($('#product_id').val()==null||$('#product_id').val()=="null"||$('#product_id').val()==""){
            valid=-1;
            $('#row-product .select2-selection--single').addClass('is-invalids');
        }
        if(valid==-1){
            notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
            ResetLoadingButton('.btn-save', mode == 1?"Tambah Produk" : "Update Produk"); 
            return false;
        };
        var temp = $('#product_id').select2("data")[0];
        var idx = -1;
        items.forEach(element => {
            if (element.product_variant_id == temp.product_variant_id && element.unit_id == $('#unit_product_id').val()) {
                element.pid_qty += parseInt($('#pid_qty').val());
                idx = 1;
            }
        }); 

        if(idx==-1){
            var data  = {
                "product_variant_id": temp.product_variant_id,
                "product_name": `${temp.pr_name} ${temp.product_variant_name}`,
                "pid_qty": parseInt($('#pid_qty').val()),
                "unit_name": $('#unit_product_id option:selected').text(),
                "unit_id": $('#unit_product_id').val(),
            };
            items.push(data);
        }
        addRow(1)

        $('#product_id ').empty();
        $('#unit_product_id').empty();
        $('#pid_qty').val("");
    })
    $(document).on('click', '.btn-add-supplies', function(){
        $('.is-invalid').removeClass('is-invalid');
        $('.is-invalids').removeClass('is-invalids');
        var valid=1;
        $("#add-product-issues .fill_supplies").each(function(){
            if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                valid=-1;
                $(this).addClass('is-invalid');
            }
        });
        if($('#supplies_id').val()==null||$('#supplies_id').val()=="null"||$('#supplies_id').val()==""){
            valid=-1;
            $('#row-supplies .select2-selection--single').addClass('is-invalids');
        }
        if(valid==-1){
            notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
            ResetLoadingButton('.btn-save', mode == 1?"Tambah Produk" : "Update Produk"); 
            return false;
        };
        var temp = $('#supplies_id').select2("data")[0];
        var idx = -1;
        items.forEach(element => {
            if (element.supplies_variant_id == temp.supplies_variant_id && element.unit_id == $('#unit_supplies_id').val()) {
                element.pid_qty += parseInt($('#pid_qty').val());
                idx = 1;
            }
        }); 

        if(idx==-1){
            var data  = {
                "supplies_variant_id": temp.supplies_variant_id,
                "supplies_id": temp.supplies_id,
                "supplies_name": `${temp.supplies_name} ${temp.supplies_variant_name}`,
                "pid_qty": parseInt($('#pid_qty').val()),
                "unit_name": $('#unit_supplies_id option:selected').text(),
                "unit_id": $('#unit_supplies_id').val(),
            };
            items.push(data);
        }
        addRow(2)

        $('#supplies_id ').empty();
        $('#unit_supplies_id').empty();
        $('#pid_qty').val("");
    })
    
    // 1 = produk, 2 = bahan mentah
    function addRow(define) {
        if (define == 1){
            $('#tableProduct tr.row-product').html(" ");
            items.forEach(e => {
                $('#tableProduct tbody').append(`
                    <tr class="row-product" data-id="${e.product_variant_id}">
                        <td>${e.product_name}</td>
                        <td>${e.pid_qty}</td>
                        <td>${e.unit_name}</td>
                        <td class="text-center d-flex align-items-center">
                            <a class="p-2 btn-action-icon btn_delete_row_pr mx-auto"  href="javascript:void(0);">
                                    <i class="fe fe-trash-2"></i>
                            </a>
                        </td>
                    </tr>    
                `);
            });
        }
        if (define == 2){
            suppliesIds = [];
            $('#tableProduct tr.row-supplies').html(" ");
            items.forEach(e => {
                $('#tableProduct tbody').append(`
                    <tr class="row-supplies" data-id="${e.supplies_variant_id}">
                        <td>${e.supplies_name || e.sup_name}</td>
                        <td>${e.pid_qty}</td>
                        <td>${e.unit_name}</td>
                        <td class="text-center d-flex align-items-center">
                            <a class="p-2 btn-action-icon btn_delete_row_sp mx-auto"  href="javascript:void(0);">
                                    <i class="fe fe-trash-2"></i>
                            </a>
                        </td>
                    </tr>    
                `);
                getInvoice(e.supplies_variant_id);
            });
        }
         
    }

    $(document).on("click",".btn_delete_row_pr",function(){
        let row = $(this).closest("tr");
        let productId = row.data("id");
        items = items.filter(e => e.product_variant_id != productId);
        row.remove();
    });
    $(document).on("click",".btn_delete_row_sp",function(){
        let row = $(this).closest("tr");
        let suppliesId = row.data("id");
        items = items.filter(e => e.supplies_variant_id != suppliesId);
        suppliesIds = suppliesIds.filter(e => e != suppliesId);
        ids = ids.filter(e => e.supplies_variant_id != suppliesId);
        row.remove();
        getInvoice();
    });

    function getInvoice(id = null) {
        if (id != null){
            suppliesIds.push(id);
            $.ajax({
                url: '/getPurchaseOrderDetail',
                method: 'get',
                data: {
                    suppliesIds : suppliesIds
                },
                success: function (e) {
                    e.forEach(element => {
                        console.log(element);
                        var detail = {
                            "supplies_variant_id" : element.supplies_variant_id,
                            "po_id" : element.po_id
                        }
                        ids.push(detail);
                    }); 

                    let poIds = [];
                    ids.forEach(element => {
                        poIds.push(element.po_id);
                    });
                    autocompletePO('#ref_num', '#add-product-issues', poIds);
                }
            })
        } else {
            let poIds = [];
            ids.forEach(element => {
                poIds.push(element.po_id);
            });
            autocompletePO('#ref_num', '#add-product-issues', poIds);
        }
    }


//edit
$(document).on("click", ".btn_edit", function () {
    var tbId = $(this).closest("table").attr("id");
    var data = $("#" + tbId)
        .DataTable()
        .row($(this).parents("tr"))
        .data(); //ambil data dari table
    console.log(data);

    mode = 2;
    $("add-product-issues input").empty().val("");
    $("#pi_date").val(moment(data.pi_date).format("DD-MM-YYYY"));
    $("#pi_notes").val(data.pi_notes);
    $("#pi_type").empty();
    $('#tipe_return').val(data.tipe_return).trigger('change');
    $('#ref_num').append(`<option value="${data.ref_num}">${data.supplier_name} - ${data.poi_code}</option>`);
    $('#tableProduct tr.row-product').remove();
    $('#tableProduct tr.row-supplies').remove();
    items = [];
    ids = [];
    
    if (data.tipe_return == 1) {
        data.items.forEach(e => {
            console.log(e);
            var data  = {
                "pid_id": e.pid_id,
                "supplies_variant_id": e.item_id,
                "supplies_id": e.supplies_id,
                "supplies_name": e.sup_name,
                "pid_qty": e.pid_qty,
                "unit_name": e.unit_name,
                "unit_id": e.unit_id,
            };
            items.push(data);
            addRow(2)
        });
    }
    else if (data.tipe_return == 2) {
        data.items.forEach(e => {
            var data  = {
                "pid_id": e.pid_id,
                "product_variant_id": e.item_id,
                "product_name": e.pr_name,
                "pid_qty": e.pid_qty,
                "unit_name": e.unit_name,
                "unit_id": e.unit_id,
            };
            items.push(data);
            addRow(1)
        });
    }
    
    $("#pi_type,#tipe_return, #ref_num").prop("disabled", true);
    $("#pi_date, #pi_notes").prop("disabled", false);
    $('.add, .btn-save, .btn_delete_row_pr, .btn_delete_row_sp').show();
    $('.is-invalid').removeClass('is-invalid');
    $('.btn-save').html(mode == 1?"Tambah Produk" : "Update Produk");
    $('.cancel-btn').html(mode == 3?"Kembali" : "Batal");
    $('#btn-foto-bukti').show();
    $('#btn-lihat-bukti').show();
    imageValue(data.pi_img);
    $('#add-product-issues .modal-title').html("Update Produk Bermasalah");
    $("#add-product-issues").modal("show");
    $("#add-product-issues").attr("pi_id", data.pi_id);
    $("#add-product-issues").attr("pi_code", data.pi_code);
});

$(document).on("click", ".btn_view", function () {
    var tbId = $(this).closest("table").attr("id");
    var data = $("#" + tbId)
        .DataTable()
        .row($(this).parents("tr"))
        .data(); //ambil data dari table
    console.log(data);

    mode = 3;
    $("add-product-issues input").empty().val("");
    $("#pi_date").val(moment(data.pi_date).format("DD-MM-YYYY"));
    $("#pi_notes").val(data.pi_notes);
    $('#tipe_return').val(data.tipe_return).trigger('change');
    $("#pi_type").empty().append(
        `<option value="${data.pi_type}">${data.pi_type==1?"Dikembalikan":"Rusak"}</option>`
    );
    $('#tableProduct tr.row-product').remove();
    items = [];

    if (data.tipe_return == 1) {
        data.items.forEach(e => {
            var data  = {
                "supplies_variant_id": e.item_id,
                "supplies_id": e.supplies_id,
                "supplies_name": e.sup_name,
                "pid_qty": e.pid_qty,
                "unit_name": e.unit_name,
                "unit_id": e.unit_id,
            };
            items.push(data);
            addRow(2)
        });
    }
    else if (data.tipe_return == 2) {
        data.items.forEach(e => {
            var data  = {
                "product_variant_id": e.item_id,
                "product_name": e.pr_name,
                "pid_qty": e.pid_qty,
                "unit_name": e.unit_name,
                "unit_id": e.unit_id,
            };
            items.push(data);
            addRow(1)
        });
    }

    $("#pi_date, #pi_type, #pi_notes, #tipe_return").prop("disabled", true);
    $('.add, .btn-save, .btn_delete_row_pr, .btn_delete_row_sp').hide();
    $('.is-invalid').removeClass('is-invalid');
    $('.cancel-btn').html(mode == 3?"Kembali" : "Batal");
    $('#btn-foto-bukti').hide();
    $('#btn-lihat-bukti').show();
   
    $('#fotoProduksiImage').attr('src', public+"issue/"+data.pi_img);
    $('#fotoProduksiImage').attr('index', 0);
    $('#btn_download_photo').attr('href', public+"issue/"+data.pi_img);

    $('#add-product-issues .modal-title').html("Detail Produk Bermasalah");
    $("#add-product-issues").modal("show");
    $("#add-product-issues").attr("pi_id", data.pi_id);
    $("#add-product-issues").attr("pi_code", data.pi_code);
});

function imageValue(image){
    $('#fotoProduksiImage').attr('src', public+"issue/"+image);
    $('#fotoProduksiImage').attr('index', 0);
}

$(document).on("click", "#btn-lihat-bukti", function () {
    $("#add-product-issues").modal("hide");
    $('.btn-prev,.btn-next').hide();
    $('#modalViewPhoto').modal("show");
});
$(document).on("hidden.bs.modal", "#modalViewPhoto", function () {
    $("#add-product-issues").modal("show");
     $('#modalViewPhoto').modal("hide");
});


//delete
$(document).on("click", ".btn_delete", function () {
    var tbId = $(this).closest("table").attr("id");
    var data = $("#" + tbId)
        .DataTable()
        .row($(this).parents("tr"))
        .data(); //ambil data dari table
    showModalDelete(
        "Apakah yakin ingin menghapus Masalah Produk ini?",
        "btn-delete-issues"
    );
    $("#btn-delete-issues").attr("pi_id", data.pi_id);
});

$(document).on("click", "#btn-delete-issues", function () {
    $.ajax({
        url: "/deleteProductIssues",
        data: {
            pi_id: $("#btn-delete-issues").attr("pi_id"),
            _token: token,
        },
        method: "post",
        success: function (e) {
            $(".modal").modal("hide");
            if (e == -1)
            notifikasi(
                "error",
                "Gagal Insert",
                "Stock tidak mencukupi!"
            );
            else {
                afterInsert();
                notifikasi(
                    "success",
                    "Berhasil Delete",
                    "Berhasil delete masalah produk"
                );
            }
        },
        error: function (e) {
            console.log(e);
        },
    });
});


$(document).on('click', '#btn-foto-bukti', function() {

    rotationAngle = 0;
    camRotation = 0;
    photoData = "";
    modeCamera=2;
    inputFile ="#bukti";
    $("#video").removeClass("rot90 rot180 rot270");
    $("#preview-box").hide();
    $("#camera").show();

    startCamera();
    $("#add-product-issues").modal("hide");
    $('#modalPhoto').modal('show');
    console.log($('#bukti').val());
});

$(document).on('click', '#uploadBtn', function(){
    if ($('#bukti').val() != "" || $('#bukti').val() != "null" || $('#bukti').val() != null) {
        $('#check_foto').show();
    } else {
        $('#check_foto').hide();
    }
})