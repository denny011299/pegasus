
    var supplies = [];
    var suppliesSubmit = [];
     autocompleteCategory("#kategori",null,1);
     
     $(document).ready(function () {
         //    if(data.category_id!=null)$('#category_id').append(`<option value="${data.category_id}">${data.category_name}</option>`).trigger("change");
         //    if(mode==2){
            //     $('#staff').val(data.created_by);
            //    }
            loadStaff();
        if(mode==1){
            refreshStockOpname();
            var yesterday = moment().format('YYYY-MM-DD');
            // Autofill ke input
            $('#tanggal').val(yesterday);
            $('#status').val('-');
        }
        else{
            console.log(data);
            $('#tanggal').val(data.stob_date);
            $('#penanggung-jawab').append(`<option value="${data.staff_id}">${data.staff_name}</option>`);
            $('#kategori').append(`<option value="${data.category_id}">${data.category_name}</option>`);
            $('#catatan').val(data.stob_notes);
            $('#tanggal,#penanggung-jawab,#kategori,#catatan').prop("disabled",true);
            supplies = data.item;
            data.item.forEach((item,indexProduct) => {
                // var selisihArr = [];
                // var systemArr = [];
                let systemArr  = item.stobd_system ? item.stobd_system.split(', ') : [];
                let realArr    = item.stobd_real ? item.stobd_real.split(', ') : [];
                let selisihArr = item.stobd_selisih ? item.stobd_selisih.split(', ') : [];
                console.log(item);
                var rl_stock = "";
                var rl = item.stobd_real.split(", ");
                
                item.sp_units = [];
                item.units.forEach((unit, idx) => {
                    let systemQty  = parseInt(systemArr[idx])  || 0;
                    let realQty    = parseInt(realArr[idx])    || -1;
                    let selisihQty = parseInt(selisihArr[idx]) || 0;
                    item.sp_units.push({
                        unit_id: unit.unit_id,
                        unit_short_name: unit.unit_short_name,
                        system_qty: systemQty,
                        real_qty: realQty != -1 ? realQty : systemQty,
                        selisih_qty: selisihQty
                    });
                });

                item.sp_units.forEach((element, index) => {
                    // selisihArr.push(element.stobd_selisih + " " + element.unit_short_name);
                    // systemArr.push(element.system_qty + " " + element.unit_short_name);
                    rl_stock += `
                        <input type="number"
                            class="form-control real-stock"
                            value="${element.real_qty}"
                            data-unit-id="${element.unit_id}"
                            data-unit-name="${element.unit_short_name}"
                            data-system-qty="${element.system_qty}">
                        <span class="input-group-text">${element.unit_short_name}</span>
                    `;
                });

                // Untuk superadmin
                // $('#tbStock').append(`
                //     <tr class="row-stock" data-supplies-id="${item.supplies_id}">
                //         <td>${item.supplies_name}</td>
                //         <td class="text-center pt-2 pr_stock">${systemArr.join(', ')}</td>
                //         <td class="text-center" style="width:10%">
                //             <div class="input-group mb-3 rstock">
                //                 ${rl_stock}
                //             </div>
                //             <input type="hidden" class="data">
                //             <input type="hidden" class="stobd_id">
                //         </td>
                //         <td class="text-center pt-2 selisih">${selisihArr.join(', ')}</td>
                //         <td class="">
                //             <input type="text" class="form-control notes" placeholder="Catatan.." value="${mode==2?item.stobd_notes:''}">
                //             <input type="hidden" class="form-control input-selesih" placeholder="Catatan.."  >
                //         </td>
                //     </tr>
                // `);

                // Untuk Non Admin
                $('#tbStock').append(`
                    <tr class="row-stock" data-supplies-id="${item.supplies_id}">
                        <td>${item.supplies_name}</td>
                        <td class="text-center" style="width:10%">
                            <div class="input-group mb-3 rstock">
                                ${rl_stock}
                            </div>
                            <input type="hidden" class="data">
                            <input type="hidden" class="stobd_id">
                        </td>
                        <td class="">
                            <input type="text" class="form-control notes" placeholder="Catatan.." value="${mode==2?item.stobd_notes:''}">
                            <input type="hidden" class="form-control input-selesih" placeholder="Catatan.."  >
                        </td>
                    </tr>
                `);
            });
            $('#tbStock input').prop("disabled",true);
            $('.btn-save').hide();
            
            // Button terima tolak
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
    function refreshStockOpname() {
        $.ajax({
            url:"/getSupplies",
            method:"get",
            data:{
                // category_id:$('#kategori').val(),
                _token:'{{ csrf_token() }}'
            },
            success:function(e){
                
                console.log(e);
                
                $('#tbStock').html("");
                e.forEach((item,indexProduct) => {
                    var st = "";
                    // if(mode==2)  {
                    //     if(stp_type==1)st =  getData(item.pr_id);
                    //     else if(stp_type==2)st =  getData(item.sup_id);
                    // }
                    console.log(item);
                    var rl_stock = "";
                    var system = "";
                    var selisihArr = [];
                    
                    item.stock.forEach((element,index) => {
                        selisihArr.push("0 " + element.unit_short_name);
                        rl_stock += `
                            <input type="number"
                                class="form-control real-stock"
                                value=""
                                data-unit-id="${element.unit_id}"
                                data-unit-name="${element.unit_short_name}"
                                data-system-qty="${element.ss_stock}">
                            <span class="input-group-text">${element.unit_short_name}</span>
                        `;
                        system += element.ss_stock + " " + element.unit_short_name + ", ";
                    });

                    // Untuk superadmin
                    // $('#tbStock').append(`
                    //     <tr class="row-stock" data-supplies-id="${item.supplies_id}">
                    //         <td>${item.pr_name}</td>
                    //         <td class="text-center pt-2 pr_stock">${systemArr.join(', ')}</td>
                    //         <td class="text-center" style="width:10%">
                    //             <div class="input-group mb-3 rstock">
                    //                 ${rl_stock}
                    //             </div>
                    //             <input type="hidden" class="data">
                    //             <input type="hidden" class="stobd_id">
                    //         </td>
                    //         <td class="text-center pt-2 selisih">${selisihArr.join(', ')}</td>
                    //         <td class="">
                    //             <input type="text" class="form-control notes" placeholder="Catatan.." value="${mode==2?item.stobd_notes:''}">
                    //             <input type="hidden" class="form-control input-selesih" placeholder="Catatan.."  >
                    //         </td>
                    //     </tr>
                    // `);

                    // Untuk Non Admin
                    $('#tbStock').append(`
                        <tr class="row-stock" data-supplies-id="${item.supplies_id}">
                            <td>${item.supplies_name}</td>
                            <td class="text-center" style="width:10%">
                                <div class="input-group mb-3 rstock">
                                    ${rl_stock}
                                </div>
                                <input type="hidden" class="data">
                                <input type="hidden" class="stobd_id">
                            </td>
                            <td class="">
                                <input type="text" class="form-control notes" placeholder="Catatan.." value="${mode==2?item.stobd_notes:''}">
                                <input type="hidden" class="form-control input-selesih" placeholder="Catatan.."  >
                            </td>
                        </tr>
                    `);
                    $('.real-stock').trigger("keyup");
                });
                if(e.length==0){
                    $('#tbStock').html(`<tr><td colspan="3" class="text-center">Tidak ada produk pada kategori ini</td></tr>`);
                }
                if(mode==2){
                    $(".real-stock, .notes").attr("disabled","disabled");
                }
                supplies = e;
            },
            error:function(e){
                console.log(e);
            }
        });
    }

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

    $(document).on("click",".btn-save",function(){
        insertData();
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
    LoadingButton(this);
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid').removeClass('invalid');
    var url ="/insertStockOpnameBahan";
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

    suppliesSubmit = [];
    $('.row-stock').each(function () {

        let row = $(this);
        let item = {};

        item.supplies_id = row.data('supplies-id');
        item.stobd_notes = row.find('.notes').val() ?? '';

        let sp_units = [];
        let systemArr  = [];
        let realArr    = [];
        let selisihArr = [];

        row.find('.real-stock').each(function () {

            let input = $(this);

            let unitId    = input.data('unit-id');
            let unitName  = input.data('unit-name');
            let systemQty = parseInt(input.data('system-qty')) || 0;
            let realQty   = parseInt(input.val()) || -1;

            sp_units.push({
                unit_id: unitId,
                system_qty: systemQty,
                real_qty: realQty != -1 ? realQty : systemQty
            });

            systemArr.push(systemQty + ' ' + unitName);
            realArr.push((realQty != -1 ? realQty : systemQty) + ' ' + unitName);
            selisihArr.push(((realQty != -1 ? realQty : systemQty) - systemQty) + ' ' + unitName);
        });

        item.sp_units = sp_units;
        item.stobd_system  = systemArr.join(', ');
        item.stobd_real    = realArr.join(', ');
        item.stobd_selisih = selisihArr.join(', ');

        suppliesSubmit.push(item);
    });
    console.log(suppliesSubmit);
    
    param = {
        stob_date: $('#tanggal').val(),
        staff_id: $('#penanggung-jawab').val(),
        // category_id: -1,
        stob_notes: $('#catatan').val(),
        item: JSON.stringify(suppliesSubmit),
        _token: token
    };
    if(mode==2){
        url = "/updateStockOpnameBahan";
        param.stob_id = data.stob_id;
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
            window.location.href="/stockOpnameBahan";
        },
        error:function(e){
            console.log(e);
        }
    });
}

$(document).on('click', '.btnBack', function(){
    window.open('/stockOpnameBahan', '_self');
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
        "btn-acc-stob"
    );
    $("#btn-acc-stob").html("Konfirmasi");
});

$(document).on("click", "#btn-acc-stob", function () {
    LoadingButton(this);

    suppliesSubmit = [];
    $('.row-stock').each(function () {

        let row = $(this);
        let item = {};

        item.supplies_id = row.data('supplies-id');
        item.stobd_notes = row.find('.notes').val() ?? '';

        let sp_units = [];
        let systemArr  = [];
        let realArr    = [];
        let selisihArr = [];

        row.find('.real-stock').each(function () {

            let input = $(this);

            let unitId    = input.data('unit-id');
            let unitName  = input.data('unit-name');
            let systemQty = parseInt(input.data('system-qty')) || 0;
            let realQty   = parseInt(input.val()) || -1;

            sp_units.push({
                unit_id: unitId,
                system_qty: systemQty,
                real_qty: realQty != -1 ? realQty : systemQty
            });

            systemArr.push(systemQty + ' ' + unitName);
            realArr.push((realQty != -1 ? realQty : systemQty) + ' ' + unitName);
            selisihArr.push(((realQty != -1 ? realQty : systemQty) - systemQty) + ' ' + unitName);
        });

        item.sp_units = sp_units;
        item.stobd_system  = systemArr.join(', ');
        item.stobd_real    = realArr.join(', ');
        item.stobd_selisih = selisihArr.join(', ');

        suppliesSubmit.push(item);
    });

    $.ajax({
        url: "/accStockOpnameBahan",
        data: {
            stob_id: data.stob_id,
            item: JSON.stringify(suppliesSubmit),
            _token: token,
        },
        method: "post",
        success: function (e) {
            console.log("masuk");
            $('#modalDelete .modal-body').html('');
            ResetLoadingButton(".btn-konfirmasi", "Konfirmasi");
            $(".modal").modal("hide");
            notifikasi(
                "success",
                "Berhasil Approve",
                "Berhasil approve stock opname"
            );
            window.open('/stockOpnameBahan', '_self');
        },
        error: function (e) {
            console.log(e);
            ResetLoadingButton(".btn-konfirmasi", "Konfirmasi");
        },
    });
});


    $(document).on('click', '.save-tolak', function(){
        showModalDelete("Apakah yakin ingin menolak stock opname ini?","btn-tolak-stob");
        $("#btn-tolak-stob").html("Delete");
    })

    $(document).on("click","#btn-tolak-stob",function(){
        LoadingButton(this);
        $.ajax({
            url:"/tolakStockOpnameBahan",
            data:{
                stob_id:data.stob_id,
                _token:token
            },
            method:"post",
            success:function(e){
                $('#modalDelete .modal-body').html('');
                ResetLoadingButton(".btn-konfirmasi", "Delete");
                $(".modal").modal("hide");
                notifikasi(
                    "success",
                    "Berhasil Tolak",
                    "Berhasil tolak Stock Opname"
                );
                window.open('/stockOpnameBahan', '_self');
                
            },
            error:function(e){
                ResetLoadingButton(".btn-konfirmasi", "Delete");
                console.log(e);
            }
        });
    });