
    var product = [];
    var productSubmit = [];
    var savedValues = {};
     autocompleteCategory("#kategori",null,1);
     
    $(document).ready(function () {
        loadStaff();
        if(mode==1){
            refreshStockOpname();
            var yesterday = moment().format('YYYY-MM-DD');
            console.log(yesterday);
            $('#tanggal').val(yesterday);
            $('#status').val('-');
        } else {
            console.log(data);
            $('#tanggal').val(data.sto_date);
            $('#penanggung-jawab').append(`<option value="${data.staff_id}">${data.staff_name}</option>`);
            $('#kategori').append(`<option value="${data.category_id}">${data.category_name}</option>`);
            $('#catatan').val(data.sto_notes);
            $('#tanggal,#penanggung-jawab,#kategori,#catatan').prop("disabled",true);
            product = data.item;
            renderMode2(data.item);
            $('#tbStock input').prop("disabled",true);
            $('.btn-save').hide();

            if (data.status == 1){
                $('.save-tolak,.save-terima').show();
                $('#status').val('Menunggu Approval');
            } else if (data.status == 2) {
                $('.save-tolak,.save-terima').hide();
                $('#status').val('Disetujui');
            } else if (data.status == 3) {
                $('.save-tolak,.save-terima').hide();
                $('#status').val('Ditolak');
            }
        }
    })
    
    function loadStaff() {
        $("#penanggung-jawab").empty();
        autocompleteStaff("#penanggung-jawab");
    }
    function refreshStockOpname(callback) {
        // Simpan value yang sudah diinput sebelum refresh
        $('.row-stock').each(function() {
            var productId = $(this).data('product-id');
            var variantId = $(this).data('variant-id');
            var key = productId + '_' + variantId;
            savedValues[key] = {
                notes: $(this).find('.notes').val(),
                stocks: {}
            };
            $(this).find('.real-stock').each(function() {
                var unitId = $(this).data('unit-id');
                savedValues[key].stocks[unitId] = $(this).val();
            });
        });
        console.log(savedValues)
        $.ajax({
            url:"/getProductVariant",
            method:"get",
            data:{
                search_product:$('#filter_pr_name').val(),
                _token:'{{ csrf_token() }}'
            },
            success:function(e){
                product = e;
                console.log(e);
                
                $('#tbStock').html("");
                e.forEach((item,indexProduct) => {
                    var st = "";
                    var rl_stock = "";
                    var system = "";
                    var selisihArr = [];
                    
                    item.stock.forEach((element,index) => {
                        selisihArr.push("0 " + element.unit_short_name);
                        if (index % 3 === 0) {
                            if (index > 0) rl_stock += `</div><div class="input-group mb-1 rstock">`;
                        }
                        rl_stock += `
                            <input type="number"
                                class="form-control real-stock include-nol"
                                value=""
                                data-unit-id="${element.unit_id}"
                                data-unit-name="${element.unit_short_name}"
                                data-system-qty="${element.ps_stock}">
                            <span class="input-group-text">${element.unit_short_name}</span>
                        `;
                        system += element.ps_stock + " " + element.unit_short_name + ", ";
                    });

                    $('#tbStock').append(`
                        <tr class="row-stock" data-product-id="${item.product_id}" data-variant-id="${item.product_variant_id}">
                            <td>${item.product_variant_sku}</td>
                            <td>${item.pr_name} ${item.product_variant_name}</td>
                            <td class="text-center">
                                <div class="input-group mb-3 rstock">
                                    ${rl_stock}
                                </div>
                                <input type="hidden" class="data">
                                <input type="hidden" class="stod_id">
                            </td>
                            <td class="">
                                <input type="text" class="form-control notes" placeholder="Catatan.." value="${mode == 2 ? (item.stod_notes ?? '') : ''}">
                                <input type="hidden" class="form-control input-selesih" placeholder="Catatan..">
                            </td>
                        </tr>
                    `);
                    $('.real-stock').trigger("keyup");
                });
                // Restore value yang sudah diinput
                $('.row-stock').each(function() {
                    var productId = $(this).data('product-id');
                    var variantId = $(this).data('variant-id');
                    var key = productId + '_' + variantId;
                    if (savedValues[key]) {
                        $(this).find('.notes').val(savedValues[key].notes);
                        $(this).find('.real-stock').each(function() {
                            var unitId = $(this).data('unit-id');
                            if (savedValues[key].stocks[unitId] != undefined) {
                                $(this).val(savedValues[key].stocks[unitId]);
                            }
                        });
                    }
                });
                if(e.length==0){
                    $('#tbStock').html(`<tr><td colspan="6" class="text-center">Produk tidak ditemukan</td></tr>`);
                }
                if(mode==2){
                    $(".real-stock, .notes").attr("disabled","disabled");
                }

                if (typeof callback === 'function') callback();
            },
            error:function(e){
                console.log(e);
            }
        });
    }

    function renderMode2(items) {
        $('#tbStock').html("");
        items.forEach((item) => {
            console.log(item);
            var rl_stock = "";

            item.units.forEach((element, index) => {
                if (index % 4 === 0) {
                    if (index > 0) rl_stock += `</div><div class="input-group mb-1 rstock">`;
                }
                rl_stock += `
                    <input type="number"
                        class="form-control real-stock include-nol"
                        value="${element.real_qty}"
                        data-unit-id="${element.unit_id}"
                        data-unit-name="${element.unit_short_name}"
                        data-system-qty="${element.system_qty}">
                    <span class="input-group-text">${element.unit_short_name}</span>
                `;
            });

            $('#tbStock').append(`
                <tr class="row-stock" data-product-id="${item.product_id}" data-variant-id="${item.product_variant_id}">
                    <td>${item.product_variant_sku}</td>
                    <td>${item.pr_name} ${item.product_variant_name}</td>
                    <td class="text-center">
                        <div class="input-group mb-3 rstock">
                            ${rl_stock}
                        </div>
                        <input type="hidden" class="data">
                        <input type="hidden" class="stod_id">
                    </td>
                    <td class="">
                        <input type="text" class="form-control notes" placeholder="Catatan.." value="${item.stod_notes ?? ''}">
                        <input type="hidden" class="form-control input-selesih" placeholder="Catatan..">
                    </td>
                </tr>
            `);
        });

        if (items.length == 0) {
            $('#tbStock').html(`<tr><td colspan="6" class="text-center">Produk tidak ditemukan</td></tr>`);
        }
    }

    $(document).on('keyup', '#filter_pr_name', function() {
        if (mode == 1) {
            refreshStockOpname();
        } else {
            let keyword = $(this).val().toLowerCase();
            let filtered = data.item.filter(item =>
                ((item.pr_name ?? '') + ' ' + (item.product_variant_name ?? '') + ' ' + (item.product_variant_sku ?? '')).toLowerCase().includes(keyword)
            );
            renderMode2(filtered);
            $('#tbStock input').prop("disabled", true);
        }
    });

    // $(document).on("click",".real-stock",function(){
    //     $(this).focus().select();
        
    // });

    // $(document).on('keyup change', '.real-stock', function () {
    //     let row = $(this).closest('.row-stock');
    //     let selisihArr = [];

    //     row.find('.real-stock').each(function () {

    //         let realQty   = parseInt($(this).val()) || 0;
    //         let systemQty = parseInt($(this).data('system-qty')) || 0;
    //         let unitName  = $(this).data('unit-name');

    //         selisihArr.push(((realQty != -1 ? realQty : systemQty) - systemQty) + ' ' + unitName);
    //     });

    //     row.find('.selisih').html(selisihArr.join(', '));
    // });


    $(document).on("change","#category_id",function(){
        refreshStockOpname();
    });

    $(document).on("click", ".btn-save", function() {
        LoadingButton(this);
        $('#filter_pr_name').val("");
        refreshStockOpname(function() {
            insertData();
        });
    });

function getData(id) {
    var ada=-1;
    console.log("Dari get Data");
    console.log(data);
    console.log(id);
    
    data.items.forEach((element,index) => {
        if(element.pr_id&&element.pr_id == id) ada= index;
        else if(element.sup_id&&element.sup_id == id) ada= index;
    });    
    return data.items[ada];
}

function insertData() {
    LoadingButton('.btn-save');
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid').removeClass('invalid');
    var url ="/insertStockOpname";
    var valid=1;

    $(".fill").each(function(){
        if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
            valid=-1;
            $(this).addClass('is-invalid');
        }
    });

    if ($('#penanggung-jawab').val() == null || $('#penanggung-jawab').val() == "") {
        $('.row-staff .select2-selection--single').addClass('invalid');
        valid = -1;
    }


    if(valid==-1){
        notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
        ResetLoadingButton('.btn-save', mode == 1?"Tambah Stok Opname" : "Update Stok Opname");
        return false;
    };

    productSubmit = [];
    $('.row-stock').each(function () {

        let row = $(this);
        let item = {};

        item.product_id = row.data('product-id');
        item.product_variant_id = row.data('variant-id');
        item.stod_notes = row.find('.notes').val() ?? '';

        let units = [];
        let systemArr  = [];
        let realArr    = [];
        let selisihArr = [];

        row.find('.real-stock').each(function () {

            let input = $(this);

            let unitId    = input.data('unit-id');
            let unitName  = input.data('unit-name');
            let systemQty = parseInt(input.data('system-qty')) || 0;
            let val = input.val();
            let realQty = (val === '' || val === null || val === undefined) ? -1 : parseInt(val);

            units.push({
                unit_id: unitId,
                system_qty: systemQty,
                real_qty: realQty != -1 ? realQty : systemQty
            });

            systemArr.push(systemQty + ' ' + unitName);
            realArr.push((realQty != -1 ? realQty : systemQty) + ' ' + unitName);
            selisihArr.push(((realQty != -1 ? realQty : systemQty) - systemQty) + ' ' + unitName);
        });

        item.units = units;
        item.stod_system  = systemArr.join(', ');
        item.stod_real    = realArr.join(', ');
        item.stod_selisih = selisihArr.join(', ');

        productSubmit.push(item);
    });
    console.log(productSubmit);
    
    param = {
        sto_date: $('#tanggal').val(),
        staff_id: $('#penanggung-jawab').val(),
        category_id: -1,
        sto_notes: $('#catatan').val(),
        item: JSON.stringify(productSubmit),
        _token: token
    };
    if(mode==2){
        url = "/updateStockOpname";
        param.sto_id = data.sto_id;
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
            toastr.success('', 'Berhasil Tambah Stock Opname');
            window.location.href="/stockOpname";
        },
        error:function(e){
            console.log(e);
        }
    });
}

$(document).on('click', '.btnBack', function(){
    window.open('/stockOpname', '_self');
})

//konfirmasi acc
$(document).on("click", ".save-terima", function () {
    var tbId = $(this).closest("table").attr("id");
    var data = $("#" + tbId)
        .DataTable()
        .row($(this).parents("tr"))
        .data(); //ambil data dari table
    showModalKonfirmasi(
        "Apakah yakin ingin Approve stock opname ini?",
        "btn-acc-sto"
    );
    $("#btn-acc-sto").html("Konfirmasi");
});

$(document).on("click", "#btn-acc-sto", function () {
    LoadingButton(this);

    $('#filter_pr_name').val("");
    renderMode2(data.item);

    productSubmit = [];
    $('.row-stock').each(function () {

        let row = $(this);
        let item = {};

        item.product_id = row.data('product-id');
        item.product_variant_id = row.data('variant-id');
        item.stod_notes = row.find('.notes').val() ?? '';

        let units = [];
        let systemArr  = [];
        let realArr    = [];
        let selisihArr = [];

        row.find('.real-stock').each(function () {

            let input = $(this);

            let unitId    = input.data('unit-id');
            let unitName  = input.data('unit-name');
            let systemQty = parseInt(input.data('system-qty')) || 0;
            let val = input.val();
            let realQty = (val === '' || val === null || val === undefined) ? -1 : parseInt(val);

            units.push({
                unit_id: unitId,
                system_qty: systemQty,
                real_qty: realQty != -1 ? realQty : systemQty
            });

            systemArr.push(systemQty + ' ' + unitName);
            realArr.push((realQty != -1 ? realQty : systemQty) + ' ' + unitName);
            selisihArr.push(((realQty != -1 ? realQty : systemQty) - systemQty) + ' ' + unitName);
        });

        item.units = units;
        item.stod_system  = systemArr.join(', ');
        item.stod_real    = realArr.join(', ');
        item.stod_selisih = selisihArr.join(', ');

        productSubmit.push(item);
    });

    $.ajax({
        url: "/accStockOpname",
        data: {
            sto_id: data.sto_id,
            item: JSON.stringify(productSubmit),
            _token: token,
        },
        method: "post",
        success: function (e) {
            $('#modalDelete .modal-body').html('');
            $(".modal").modal("hide");
            ResetLoadingButton(".btn-konfirmasi", "Konfirmasi");
            notifikasi(
                "success",
                "Berhasil Approve",
                "Berhasil approve stock opname"
            );
            window.open('/stockOpname', '_self');
        },
        error: function (e) {
            console.log(e);
            ResetLoadingButton(".btn-konfirmasi", "Konfirmasi");
        },
    });
});



    $(document).on('click', '.save-tolak', function(){
        showModalDelete("Apakah yakin ingin menolak stock opname ini?","btn-tolak-sto");
        $("#btn-tolak-sto").html("Delete");
    })

    $(document).on("click","#btn-tolak-sto",function(){
        LoadingButton(this);
        $.ajax({
            url:"/tolakStockOpname",
            data:{
                sto_id:data.sto_id,
                _token:token
            },
            method:"post",
            success:function(e){
                $('#modalDelete .modal-body').html('');
                $(".modal").modal("hide");
                ResetLoadingButton(".btn-konfirmasi", "Delete");
                notifikasi(
                    "success",
                    "Berhasil Tolak",
                    "Berhasil tolak Stock Opname"
                );
                window.open('/stockOpname', '_self');
                
            },
            error:function(e){
                console.log(e);
                ResetLoadingButton(".btn-konfirmasi", "Delete");
            }
        });
    });
