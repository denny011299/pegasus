
    var product = [];
    var productSubmit = [];
     autocompleteCategory("#kategori",null,1);
     
     $(document).ready(function () {
         //    if(data.category_id!=null)$('#category_id').append(`<option value="${data.category_id}">${data.category_name}</option>`).trigger("change");
         //    if(mode==2){
            //     $('#staff').val(data.created_by);
            //    }
            loadStaff();
        if(mode==1){
            refreshStockOpname();
            var yesterday = moment().format('DD-MM-YYYY');
            // Autofill ke input
            console.log(yesterday);
            
            $('#tanggal').val(yesterday);
        }
        else{
            console.log(data);
            $('#tanggal').val(data.sto_date);
            $('#penanggung-jawab').append(`<option value="${data.staff_id}">${data.staff_name}</option>`);
            $('#kategori').append(`<option value="${data.category_id}">${data.category_name}</option>`);
            $('#catatan').val(data.sto_notes);
            $('#tanggal,#penanggung-jawab,#kategori,#catatan').prop("disabled",true);
            product = data.item;
            data.item.forEach((item,indexProduct) => {
                var selisihArr = [];
                console.log(item);
                    var rl_stock = "";
                    var system = "";
                    var rl = item.stod_real.split(", ");
                    item.units.forEach((element, index) => {
                    selisihArr.push(element.selisih_qty + " " + element.unit_short_name);
                    rl_stock += `
                        <input type="number"
                            class="form-control real-stock"
                            value="${element.real_qty}"
                            data-unit-id="${element.unit_id}"
                            data-unit-name="${element.unit_short_name}"
                            data-system-qty="${element.system_qty}">
                        <span class="input-group-text">${element.unit_short_name}</span>
                    `;
                    system += element.system_qty + " " + element.unit_short_name + ", ";
                });

                    $('#tbStock').append(`
                        <tr class="row-stock" data-product-id="${item.product_id}" data-variant-id="${item.product_variant_id}">
                            <td>${item.product_variant_sku}</td>
                            <td>${item.pr_name} ${item.product_variant_name}</td>
                            <td class="text-center pt-2 pr_stock">${system}</td>
                            <td class="text-center" style="width:10%">
                                <div class="input-group mb-3 rstock">
                                    ${rl_stock}
                                </div>
                                <input type="hidden" class="data">
                                <input type="hidden" class="stod_id">
                            </td>
                            <td class="text-center pt-2 selisih">${selisihArr.join(', ')}</td>
                            <td class="">
                                <input type="text" class="form-control notes" placeholder="Catatan.." value="${mode==2?item.stod_notes:''}">
                                <input type="hidden" class="form-control input-selesih" placeholder="Catatan.."  >
                            </td>
                        </tr>
                    `);
          
            });
            $('#tbStock input').prop("disabled",true);
            $('.btn-save').hide();
        }
        
    })
    
    function loadStaff() {
        $("#penanggung-jawab").empty();
        autocompleteStaff("#penanggung-jawab");
    }
    function refreshStockOpname() {
        $.ajax({
            url:"/getProductVariant",
            method:"get",
            data:{
                category_id:$('#kategori').val(),
                _token:'{{ csrf_token() }}'
            },
            success:function(e){
                 product = e;
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
                                value="0"
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
                            <td class="text-center pt-2 pr_stock">${system}</td>
                            <td class="text-center" style="width:10%">
                                <div class="input-group mb-3 rstock">
                                    ${rl_stock}
                                </div>
                                <input type="hidden" class="data">
                                <input type="hidden" class="stod_id">
                            </td>
                            <td class="text-center pt-2 selisih">${selisihArr.join(', ')}</td>
                            <td class="">
                                <input type="text" class="form-control notes" placeholder="Catatan.." value="${mode==2?st.stpd_note:''}">
                                <input type="hidden" class="form-control input-selesih" placeholder="Catatan.."  >
                            </td>
                        </tr>
                    `);
                    $('.real-stock').trigger("keyup");
                });
                if(e.length==0){
                    $('#tbStock').html(`<tr><td colspan="6" class="text-center">Tidak ada produk pada kategori ini</td></tr>`);
                }
                if(mode==2){
                    $(".real-stock, .notes").attr("disabled","disabled");
                }
            },
            error:function(e){
                console.log(e);
            }
        });
    }

    $(document).on("click",".real-stock",function(){
        $(this).focus().select();
        
    });

    $(document).on('keyup change', '.real-stock', function () {
        let row = $(this).closest('.row-stock');
        let selisihArr = [];

        row.find('.real-stock').each(function () {

            let realQty   = parseInt($(this).val()) || 0;
            let systemQty = parseInt($(this).data('system-qty')) || 0;
            let unitName  = $(this).data('unit-name');

            selisihArr.push((realQty - systemQty) + ' ' + unitName);
        });

        row.find('.selisih').html(selisihArr.join(', '));
    });


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
    var url ="/insertStockOpname";
    var valid=1;

    $(".fill").each(function(){
        if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
            valid=-1;
            $(this).addClass('is-invalid');
        }
    });

    if(valid==-1){
        notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
        ResetLoadingButton('.btn-save', `<span class="fe fe-save"></span> Simpan Perubahan`);
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
            let realQty   = parseInt(input.val()) || 0;

            units.push({
                unit_id: unitId,
                system_qty: systemQty,
                real_qty: realQty
            });

            systemArr.push(systemQty + ' ' + unitName);
            realArr.push(realQty + ' ' + unitName);
            selisihArr.push((realQty - systemQty) + ' ' + unitName);
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

$(document).on("change", "#kategori", async function () {

    var param = {
    };
    if ($("#kategori").val() != -1) {
        param.category_id = $("#kategori").val();
    }
    $.ajax({
        url: "/getProductVariant",
        type: "GET",
        dataType: "json",
        data: param,
         headers: {
            'X-CSRF-TOKEN': token
        },
        success: function (data) {
            data.forEach((element) => {
                element.real_stock = 0;
            });
            StockOpname = data;
            refreshStockOpname();
        },
    });
});