    var tableReturn, tableDamage;
    var mode = 1; //1 = insert, 2 = edit, 3 = view
    var type = 1; //1 = all, 2 = In, 3 = Out
    var product = [];

    autocompleteProductVariantOnly("#product_id", "#add-product-issues");


    $(document).ready(function(){
        inisialisasi();
        refreshProductIssues();
        afterInsert();
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
        product = [];
        $('#add-product-issues .modal-title').html("Tambah Produk Bermasalah");
        $('#add-product-issues input').val("");
        $('#pi_type').html(`
            <option value="1" selected>Dikembalikan</option>
            <option value="2">Rusak</option>    
        `);
        $('#product_id').empty();
        $('#tableProduct tr.row-product').remove();
        $('#add-product-issues select2').empty();
        $('#tipe_return').val(1).trigger('change');
        // $('#add-product-issues select').val(1);

        $('.is-invalid').removeClass('is-invalid');
        $("#pi_date, #pi_type, #pi_notes, #tipe_return").prop("disabled", false);
        $('.add, .btn-save, .btn_delete_row').show();
        $('.btn-save').html(mode == 1?"Tambah Produk" : "Update Produk");
        $('.cancel-btn').html(mode == 3?"Kembali" : "Batal");
        $("#pi_type,#tipe_return,#product_id,#unit_id").prop("disabled", false);

        let today = new Date();
        let yyyy = today.getFullYear();
        let mm = String(today.getMonth() + 1).padStart(2, '0');
        let dd = String(today.getDate()).padStart(2, '0');
        let todayStr = dd + '-' + mm + '-' + yyyy;
        $("#pi_date").val(todayStr);

        $('#add-product-issues').modal("show");
    });

    $(document).on('change','#product_id',function(){
        var data = $(this).select2("data")[0];
        console.log(data);
        $('#unit_product_id').empty();
        
        data.pr_unit.forEach(element => {
            $('#unit_product_id').append(`<option value="${element.unit_id}">${element.unit_name}</option>`);
        });
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
                { data: "date", width: "20%" },
                { data: "pi_code", class: "width: 10%" },
                { data: "pi_notes", width: "30%" },
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
    }

    
function loadPiType() {
    $("#pi_type").empty();
    $("#pi_type").append(`<option value="1" selected>Dikembalikan</option>`);
    if ($("#tipe_return").val() == 1) {
        $("#pi_type").append(`<option value="2">Rusak</option>`);
    }
}

$(document).on("change", "#tipe_return", function () {
    loadPiType();
});



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
        // ResetLoadingButton('.btn-save', 'Save changes');
        return false;
    }

    if ($("#tableProduct tbody tr").length == 0) {
        notifikasi('error', "Gagal Insert", 'Minimal input 1 produk');
        ResetLoadingButton('.btn-save', mode == 1?"Tambah Produk" : "Update Produk"); 
        return false;
    }

    param = {
        pi_date: $("#pi_date").val(),
        pi_type: $("#pi_type").val(),
        pi_notes: $("#pi_notes").val(),
        tipe_return: $("#tipe_return").val(),
        product: JSON.stringify(product),
        _token: token,
    };
    console.log(param);
    //LoadingButton($(this));

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
            if (e == -1)
                notifikasi(
                    "error",
                    "Gagal Insert",
                    "Stock Product tidak mencukupi!"
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
        },
    });
});

    $(document).on('click', '.btn-add-product', function(){
        $('.is-invalid').removeClass('is-invalid');
        var valid=1;
        $("#add-product-issues .fill_product").each(function(){
            if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                valid=-1;
                $(this).addClass('is-invalid');
            }
        });

        if(valid==-1){
            notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
            ResetLoadingButton('.btn-save', mode == 1?"Tambah Produk" : "Update Produk"); 
            return false;
        };
        var temp = $('#product_id').select2("data")[0];
        var idx = -1;
        console.log(temp);
        product.forEach(element => {
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
            product.push(data);
        }
        addRow()

        $('#product_id ').empty();
        $('#unit_product_id').empty();
        $('#pid_qty').val("");
    })
    
    function addRow() {
        $('#tableProduct tr.row-product').html(" ");
        product.forEach(e => {
            $('#tableProduct tbody').append(`
                <tr class="row-product" data-id="${e.product_variant_id}">
                    <td>${e.product_name}</td>
                    <td>${e.pid_qty}</td>
                    <td>${e.unit_name}</td>
                    <td class="text-center d-flex align-items-center">
                        <a class="p-2 btn-action-icon btn_delete_row mx-auto"  href="javascript:void(0);">
                                <i class="fe fe-trash-2"></i>
                        </a>
                    </td>
                </tr>    
            `);
        });
         
    }

    $(document).on("click",".btn_delete_row",function(){
        let row = $(this).closest("tr");
        let productId = row.data("id");
        product = product.filter(e => e.product_variant_id != productId);
        console.log(product)
        row.remove();
    });


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
    $('#tipe_return').val(data.tipe_return).trigger('change');
    $("#pi_type").empty().append(
        `<option value="${data.pi_type}">${data.pi_type==1?"Dikembalikan":"Rusak"}</option>`
    );
    $('#tableProduct tr.row-product').remove();
    product = [];
    
    data.product.forEach(e => {
        var data  = {
            "product_variant_id": e.product_variant_id,
            "product_name": e.pr_name,
            "pid_qty": e.pid_qty,
            "unit_name": e.unit_name,
            "unit_id": e.unit_id,
        };
        product.push(data);
        addRow(data)
    });
    
    $("#pi_type,#tipe_return").prop("disabled", true);
    $("#pi_date, #pi_notes").prop("disabled", false);
    $('.add, .btn-save, .btn_delete_row').show();
    $('.is-invalid').removeClass('is-invalid');
    $('.btn-save').html(mode == 1?"Tambah Produk" : "Update Produk");
    $('.cancel-btn').html(mode == 3?"Kembali" : "Batal");
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
    product = [];

    data.product.forEach(e => {
        var data  = {
            "product_variant_id": e.product_variant_id,
            "product_name": e.pr_name,
            "pid_qty": e.pid_qty,
            "unit_name": e.unit_name,
            "unit_id": e.unit_id,
        };
        product.push(data);
        addRow(data)
    });

    $("#pi_date, #pi_type, #pi_notes, #tipe_return").prop("disabled", true);
    $('.add, .btn-save, .btn_delete_row').hide();
    $('.is-invalid').removeClass('is-invalid');
    $('.cancel-btn').html(mode == 3?"Kembali" : "Batal");
    $('#add-product-issues .modal-title').html("Detail Produk Bermasalah");
    $("#add-product-issues").modal("show");
    $("#add-product-issues").attr("pi_id", data.pi_id);
    $("#add-product-issues").attr("pi_code", data.pi_code);
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
            afterInsert();
            notifikasi(
                "success",
                "Berhasil Delete",
                "Berhasil delete masalah produk"
            );
        },
        error: function (e) {
            console.log(e);
        },
    });
});
