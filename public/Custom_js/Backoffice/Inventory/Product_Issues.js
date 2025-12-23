    var tableReturn, tableDamage;
    var mode = 1; //1 = auto scan, 2 = manual input
    var type = 1; //1 = all, 2 = In, 3 = Out

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
      $(document).on("change", "#product_id" , function () {
        var data = $(this).select2("data")[0];
        console.log(data);
        if(data){
             $('#unit_id').html("");
             if (data.pr_unit){
                 data.pr_unit.forEach(element => {
                      $('#unit_id').append(`<option value="${element.unit_id}">${element.unit_name}</option>`) 
                 });
             }
             $('#unit_id').val(data.unit_id).trigger("change");
              $('#pi_unit option').first().prop('selected', true);
        }
       
    });

    $(document).on('click','.btnAdd',function(){
        mode=1;
        $('#add-product-issues .modal-title').html("Tambah Produk Bermasalah");
        $('#add-product-issues input').val("");
        $('#pi_type').html(`
            <option value="1" selected>Dikembalikan</option>
            <option value="2">Rusak</option>    
        `);
        $('#product_id').empty();
        $('#add-product-issues select2').empty();
        $('#add-product-issues select').val(1);
        $('.is-invalid').removeClass('is-invalid');
        $("#pi_type,#tipe_return,#product_id,#unit_id").prop("disabled", false);
        $('#add-product-issues #unit_id').append("<option selected value=''>Pilih Satuan</option>");
        $('#add-product-issues').modal("show");
    });
    function inisialisasi() {
        tableReturn = $('#tableReturn').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
            
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
                { data: "pr_name", width: "20%" },
                { data: "pr_sku", width: "15%" },
                { data: "date", width: "20%" },
                { data: "pi_qty", width: "12%" },
                { data: "notes", width: "20%" },
                { data: "action", class: "d-flex align-items-center" },
            ]
        });

        tableDamage = $('#tableDamage').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
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
                { data: "pr_name", class: "width: 25%" },
                { data: "pr_sku", class: "width: 15%" },
                { data: "date", class: "width: 15%" },
                { data: "pi_qty", class: "width: 10%" },
                { data: "notes", class: "width: 20%" },
                { data: "action", class: "d-flex align-items-center" },
            ]
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
                    if (item.pi_notes == null) item.notes = "-";
                    else item.notes = item.pi_notes;

                    item.date = moment(item.pi_date).format('D MMM YYYY');
                    item.action = `
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

    param = {
        product_variant_id: $("#product_id").val(),
        pi_date: $("#pi_date").val(),
        pi_qty: $("#pi_qty").val(),
        pi_type: $("#pi_type").val(),
        pi_notes: $("#pi_notes").val(),
        unit_id: $("#unit_id").val(),
        tipe_return: $("#tipe_return").val(),
        _token: token,
    };
    console.log(param);
    //LoadingButton($(this));

    if (mode == 2) {
        url = "/updateProductIssues";
        param.pi_id = $("#add-product-issues").attr("pi_id");
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
    $("#unit_id").val(data.pi_qty);
    $("#pi_qty").val(data.pi_qty);
    $("#pi_notes").val(data.pi_notes);
    $("#product_id").empty().append(
        `<option value="${data.product_variant_id}">${data.pr_name}</option>`
    );
    $("#unit_id").empty().append(
        `<option value="${data.unit_id}" selected>${data.unit_name}</option>`
    ).trigger("change");
    $("#pi_type").empty().append(
        `<option value="${data.pi_type}">${data.pi_type==1?"Dikembalikan":"Rusak"}</option>`
    );
    $("#pi_type,#tipe_return,#product_id,#unit_id").prop("disabled", true);
    $('.is-invalid').removeClass('is-invalid');
    $('.btn-save').html('Simpan perubahan');
    $('#add-product-issues .modal-title').html("Update Produk Bermasalah");
    $("#add-product-issues").modal("show");
    $("#add-product-issues").attr("pi_id", data.pi_id);
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
                "Berhasil delete inventaris"
            );
        },
        error: function (e) {
            console.log(e);
        },
    });
});
