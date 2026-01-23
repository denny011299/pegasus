    autocompleteBom('#product_id', '#addProduction')
    var mode=1;
    var table;
    var items = [];
    var list_photo =  [];
    $(document).ready(function(){
        $('#date_production').val(moment().format('YYYY-MM-DD')).trigger("change");
        inisialisasi();
        refreshProduction();
    });

    $(document).on('click', '.btnAdd', function(){
        mode=1;
        $('#addProduction .modal-title').html("Tambah Produksi");
        $('#addProduction input').val("");
        $('#product_id').empty();
        $('#production_qty').val("");
        $('#tableProduct tr.row-product').remove();
        $('.is-invalid').removeClass('is-invalid');
        $('#unit_id').html("");
        $('#unit_id').append("<option selected>Pilih Satuan</option>");
        $('#addProduction').modal("show");
        
        let today = new Date();
        let yyyy = today.getFullYear();
        let mm = String(today.getMonth() + 1).padStart(2, '0');
        let dd = String(today.getDate()).padStart(2, '0');
        let todayStr = yyyy + '-' + mm + '-' + dd;
        $("#production_date").val(todayStr);
    })

    $(document).on('keyup', '#production_qty', function(){
        var data = $('#product_id').select2("data")[0];
        console.log(data);
        var qty = $(this).val();
        if(qty==""||qty==null||isNaN(qty)){
            qty=0;
        }
        $('#production_total').val(qty*data.bom_qty);
    });

    $(document).on('change', '#product_id', function(){
        var data = $(this).select2("data")[0];
        console.log(data);
        
        $('#unit_id').html("");
        data.pr_unit.forEach(element => {
            $('#unit_id').append(`<option value="${element.unit_id}">${element.unit_name}</option>`) 
        });
        $('#unit_id').val(data.unit_id).trigger("change");
        $('#pi_unit option').first().prop('selected', true);
        
        $('#production_qty').trigger('keyup');
    })

    function inisialisasi() {
        table = $('#tableProduction').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
            searching: false,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Cari Produksi",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "date" },
                { data: "product_name" },
                { data: "product_sku" },
                { data: "production_qty" },
                { data: "status_text" },
                { data: "notes", defaultContent: "-",width: "50px"  },
                { data: "action", class: "d-flex align-items-center" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });
    }

    function refreshProduction() {
        $.ajax({
            url: "/getProduction",
            method: "get",
            data:{
                "date":$('#date_production').val()
            },
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                console.log(e);
                table.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].date = moment(e[i].production_date).format('D MMM YYYY');
                    e[i].action = `
                        <button class="btn btn-sm btn-danger btn-action-icon btn_delete"><i class="fa-solid fa-ban"></i></button>
                    `;

                    if(e[i].status != 1){
                        e[i].action = `
                        `;
                    }
                    if(e[i].status == 2){
                         e[i].action = `
                            <button class="btn btn-sm btn-danger btn-action-icon btn_cancel"><i class="fa-solid fa-x"></i></button>
                            <button class="btn btn-sm btn-success btn-action-icon btn_acc ms-3"><i class="fa-solid fa-check"></i></button>
                        `;
                    }
                    if(moment(e[i].production_date).isBefore(moment().format('YYYY-MM-DD'))){
                        e[i].action = `
                            
                        `;
                    }

                    if (e[i].status == 1){
                        e[i].status_text = `<span class="badge bg-success" style="font-size: 12px">Success</span>`;
                    } else if (e[i].status == 2){
                        e[i].status_text = `<span class="badge bg-primary" style="font-size: 12px">Pending Approval</span>`;
                    } else if (e[i].status == 3){
                        e[i].status_text = `<span class="badge bg-danger" style="font-size: 12px">Cancel</span>`;
                    }
                }

                table.rows.add(e).draw();
                feather.replace(); // Biar icon feather muncul lagi
            },
            error: function (err) {
                console.error("Gagal load kategori:", err);
            }
        });
    }

    $(document).on("change","#date_production",function(){
        refreshProduction();
    });

    $(document).on("click",".btn-save",function(){
       LoadingButton(this);
        $('.is-invalid').removeClass('is-invalid');
        var url ="/insertProduction";
        var valid=1;
        var dt = $('#product_id').select2("data")[0];

        $("#addProduction .fill").each(function(){
            if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                valid=-1;
                $(this).addClass('is-invalid');
            }
        });
        if($('#production_qty').val()<=0){
            valid=-1;
            $('#production_qty').addClass('is-invalid');
            notifikasi('error', "Qty Tidak Valid", 'Qty produksi harus lebih dari 0');
            ResetLoadingButton('.btn-save', mode == 1?"Tambah Produksi" : "Update Produksi"); 
            return false;
        }
        if(valid==-1){
            notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
            ResetLoadingButton('.btn-save', mode == 1?"Tambah Produksi" : "Update Produksi"); 
            return false;
        };
        param = {
            production_date:$('#production_date').val(),
            production_bom_id:$('#product_id').val(),
            production_product_id:dt.product_id,
            production_qty:$('#production_qty').val(),
            unit_id:$('#unit_id').val(),
            detail:JSON.stringify(items),
            _token:token
        };
        LoadingButton($(this));
        $.ajax({
            url:url,
            data: param,
            method:"post",
            headers: {
                'X-CSRF-TOKEN': token
            },
            success:function(e){ 
                ResetLoadingButton('.btn-save', mode == 1?"Tambah Produksi" : "Update Produksi");       
                if(e.status == -1){
                    notifikasi('error', "Stock Tidak Mencukupi", e.message);
                    return false;
                }
                afterInsert();
            },
            error:function(a){
                ResetLoadingButton('.btn-save', mode == 1?"Tambah Produksi" : "Update Produksi"); 
                console.log(a);
            }
        });
        /*
        // Cek stock supplies
        var qtyInput = $('#production_qty').val();
        var validQty = 1;
        var bahanKurang = [];
        $.ajax({
            url: "/getSupplies",
            method: "get",
            headers: {
                'X-CSRF-TOKEN': token
            },
            success:function(e){
                console.log(items[0])
                for (let i = 0; i < e.length; i++) {
                    items[0].forEach(element => {
                        if (e[i].supplies_id == element.supplies_id){
                            var need = element.bom_detail_qty * qtyInput;
                            console.log(need)
                            if (e[i].supplies_stock < need){
                                console.log('masuk')
                                validQty = -1;
                                bahanKurang.push(e[i].supplies_name);
                            }
                        }
                    });
                }

                if (validQty == -1){
                    notifikasi('error', "Stock Tidak Mencukupi", `Mohon cek stock ${bahanKurang.map(d => d).join(", ")}`);
                    ResetLoadingButton('.btn-save', mode == 1?"Tambah Produksi" : "Update Produksi"); 
                    return false;
                } else{
                    
            },
            error:function(e){
                console.log(e)
            }
        })*/
    });

    function afterInsert() {
        $(".modal").modal("hide");
        if(mode==1)notifikasi('success', "Berhasil Insert", "Berhasil Tambah Produksi");
        refreshProduction();
    }

    function addRow(e) {
        $('#tableProduct tbody').html("");
        e.forEach(element => {
            console.log(element);
            $('#tableProduct tbody').append(`
                <tr class="row-product" data-id="${element.product_variant_id}">
                    <td>${element.product_name}</td>
                    <td class="text-center">${element.production_qty}</td>
                    <td>${element.unit_name}</td>
                    <td class="text-center d-flex align-items-center">
                        <a class="p-2 btn-action-icon btn_delete_row_pr mx-auto"  href="javascript:void(0);">
                                <i class="fe fe-trash-2"></i>
                        </a>
                    </td>
                </tr>    
            `)
        })
    }

    $(document).on('click', '.btn-add-product', function(){
        $('.is-invalid').removeClass('is-invalid');
        $('.is-invalids').removeClass('is-invalids');
        var valid=1;
        $("#addProduction .fill_product").each(function(){
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
            ResetLoadingButton('.btn-save', mode == 1?"Tambah Produksi" : "Update Produksi"); 
            return false;
        };
        var temp = $('#product_id').select2("data")[0];
        var idx = -1;
        console.log(temp);
        items.forEach(element => {
            console.log(element);
            if (element.product_variant_id == temp.product_variant_id && element.unit_id == temp.unit_id) {
                element.production_qty += parseInt($('#production_qty').val());
                idx = 1;
            }
        });

        if(idx==-1){
            var data  = {
                "product_variant_id": temp.product_variant_id,
                "product_name": temp.product_name,
                "production_qty": parseInt($('#production_qty').val()),
                "unit_name": $('#unit_id option:selected').text(),
                "unit_id": parseInt($('#unit_id').val())
            };
            items.push(data);
        }
        addRow(items);

        $('#product_id').empty();
        $('#unit_id').empty();
        $('#unit_id').append("<option selected>Pilih Satuan</option>");
        $('#production_qty').val("");
    })

    $(document).on("click",".btn_delete_row_pr",function(){
        let row = $(this).closest("tr");
        let productId = row.data("id");
        items = items.filter(e => e.product_variant_id != productId);
        console.log(items)
        row.remove();
    });
    

//delete
$(document).on("click", ".btn_delete", function () {
    $('#modalDelete .modal-body #delete_reason').remove();
    var tbId = $(this).closest("table").attr("id");
    var data = $("#" + tbId)
        .DataTable()
        .row($(this).parents("tr"))
        .data(); //ambil data dari table
    showModalDelete(
        "Apakah yakin ingin batalkan produksi ini?",
        "btn-delete-production"
    );
    $('#modalDelete .modal-body').append(`<textarea class="form-control mt-2" id="delete_reason" placeholder="Alasan pembatalan produksi..." rows="3"></textarea>`);
    $("#btn-delete-production").html("Batal Produksi");
    $("#btn-delete-production").attr("production_id", data.production_id);
});

$(document).on("click", "#btn-delete-production", function () {
    $('.is-invalid').removeClass('is-invalid');
    console.log($('#delete_reason').val());
    
    if($('#delete_reason').val() == ""){
        $('#delete_reason').addClass('is-invalid'); 

        return false;
    }
    $.ajax({
        url: "/deleteProduction",
        data: {
            production_id : $("#btn-delete-production").attr("production_id"),
            delete_reason: $('#delete_reason').val(),
            _token: token,
        },
        method: "post",
        success: function (e) {
            $('#modalDelete .modal-body').html('');
            $(".modal").modal("hide");
            afterInsert();
            notifikasi(
                "success",
                "Berhasil Batalkan",
                "Berhasil batalkan produksi"
            );
        },
        error: function (e) {
            console.log(e);
        },
    });
});

//konfirmasi acc
$(document).on("click", ".btn_acc", function () {
    var tbId = $(this).closest("table").attr("id");
    var data = $("#" + tbId)
        .DataTable()
        .row($(this).parents("tr"))
        .data(); //ambil data dari table
    showModalKonfirmasi(
        "Apakah yakin ingin Approve pembatalan produksi ini?",
        "btn-acc-delete-production"
    );
    $("#btn-acc-delete-production").html("Konfirmasi Batal Produksi");
    $("#btn-acc-delete-production").attr("production_id", data.production_id);
});

$(document).on("click", "#btn-acc-delete-production", function () {

    $.ajax({
        url: "/accDeleteProduction",
        data: {
            production_id : $("#btn-acc-delete-production").attr("production_id"),
            _token: token,
        },
        method: "post",
        success: function (e) {
            $('#modalDelete .modal-body').html('');
            $(".modal").modal("hide");
            if(e.status == -1){
                notifikasi('error', "Stok Tidak Mencukupi", e.message);
                return false;
            }
            afterInsert();
            notifikasi(
                "success",
                "Berhasil Approve",
                "Berhasil approve pembatalan produksi"
            );
        },
        error: function (e) {
            console.log(e);
        },
    });
});

//konfirmasi acc
$(document).on("click", ".btn_cancel", function () {
    var tbId = $(this).closest("table").attr("id");
    var data = $("#" + tbId)
        .DataTable()
        .row($(this).parents("tr"))
        .data(); //ambil data dari table
    showModalKonfirmasi(
        "Apakah yakin ingin Tolak pembatalan produksi ini?",
        "btn-cancel-delete-production"
    );
    $("#btn-cancel-delete-production").html("Konfirmasi Total Batal Produksi");
    $("#btn-cancel-delete-production").attr("production_id", data.production_id);
});

$(document).on("click", "#btn-cancel-delete-production", function () {
    $.ajax({
        url: "/tolakDeleteProduction",
        data: {
            production_id : $("#btn-cancel-delete-production").attr("production_id"),
            _token: token,
        },
        method: "post",
        success: function (e) {
            $('#modalDelete .modal-body').html('');
            $(".modal").modal("hide");
            afterInsert();
            notifikasi(
                "success",
                "Berhasil Tolak",
                "Berhasil tolak pembatalan produksi"
            );
        },
        error: function (e) {
            console.log(e);
        },
    });
});

$(document).on('click', '.btn-prev', function(){
    var index = parseInt($('#fotoProduksiImage').attr('index'));
    if(index > 0){
        index -= 1;
        $('#fotoProduksiImage').attr('src', public+list_photo[index].pp_photo);
        $('#fotoProduksiImage').attr('index', index);
        $('#btn_download_photo').attr('href', public+list_photo[index].pp_photo);
    }
});
$(document).on('click', '.btn-next', function(){
    var index = parseInt($('#fotoProduksiImage').attr('index'));
    if(index < list_photo.length - 1){
        index += 1;
        $('#fotoProduksiImage').attr('src', public+list_photo[index].pp_photo);
        $('#fotoProduksiImage').attr('index', index);
        $('#btn_download_photo').attr('href', public+list_photo[index].pp_photo);
    }
});

$(document).on('click', '.LihatfotoProduksi', function(){
       list_photo = [];
       $('#fotoProduksiImage').attr('src', public+"no_img.png");
       $.ajax({
        url: "/getFotoProduksi",
        data: {
            pp_date: $('#date_production').val(),
            _token: token,
        },
        method: "get",
        success: function (e) {
            console.log(e);
 
            if(e.length > 0){
                list_photo = e;
                $('#modalViewPhoto .modal-footer').show();
                $('#fotoProduksiImage').attr('src', public+e[0].pp_photo);
                $('#fotoProduksiImage').attr('index', 0);
                $('#btn_download_photo').attr('href', public+e[0].pp_photo);
            }
            else{
                $('#modalViewPhoto .modal-footer').hide();
            }
            $('#modalViewPhoto').modal('show');
        },
        error: function (e) {
            console.log(e);
        },
    });
});
