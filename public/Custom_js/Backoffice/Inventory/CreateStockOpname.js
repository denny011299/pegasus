
    var product = [];
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
                console.log(item);
                    var rl_stock = "";
                    var system = "";
                    var rl = item.stod_real.split(", ");
                    item.stock.forEach((element,index) => {
                        var dt = rl[index].split(" ");
                        rl_stock += `
                            <input type="text" class="form-control real-stock" placeholder="" index ="${indexProduct}" aria-label="Username" value="${dt[0]}">
                            <span class="input-group-text">${element.unit_short_name}</span>
                        `;
                        system += element.ps_stock + " "+element.unit_short_name+", ";
                    });
                    $('#tbStock').append(`
                        <tr class="row-stock">
                            <td>${item.product_variant_sku}</td>
                            <td>${item.pr_name} ${item.product_variant_name}</td>
                            <td class="text-center pt-2 pr_stock">${item.stod_system}</td>
                            <td class="text-center" style="width:10%">
                                <div class="input-group mb-3 rstock">
                                    ${rl_stock}
                                </div>
                                <input type="hidden" class="data" value="${system}">
                                <input type="hidden" class="stod_id"  value="${item.stod_id}">
                            </td>
                            <td class="text-center pt-2 selisih">${item.stod_selisih}</td>
                            <td class="" >
                                <input type="text" class="form-control notes" placeholder="Catatan.." value="${item.stod_notes}">
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
                    item.stock.forEach((element,index) => {
                        rl_stock += `
                            <input type="text" class="form-control real-stock" placeholder="" index ="${indexProduct}" aria-label="Username" value="0">
                            <span class="input-group-text">${element.unit_short_name}</span>
                        `;
                        system += element.ps_stock + " "+element.unit_short_name+", ";
                    });
                    $('#tbStock').append(`
                        <tr class="row-stock">
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
                            <td class="text-center pt-2 selisih">0</td>
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

    $(document).on("keyup",".real-stock",function(){
        var index = $(this).attr("index");
        var rowStock = $(this).closest(".row-stock");
        var row = $(this).closest(".rstock");

        var hasil = [];
        var real = [];
        if(product[index]){

            product[index].stock.forEach((element,index) => {
                var selisih = row.find('.real-stock').eq(index).val() - element.ps_stock;
                real.push(row.find('.real-stock').eq(index).val() + " "+element.unit_short_name);
                hasil.push(selisih + " "+element.unit_short_name);
            });
            
            console.log(hasil);
            $('.data').eq(index).val(real.join(', '));
            $('.selisih').eq(index).html(hasil.join(', '));
        }
        else{
           rowStock.find('.selisih').val(0);
        }
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
        ResetLoadingButton('.btn-save', 'Simpan perubahan');
        return false;
    };

    $('.row-stock').each(function(index){
        product[index]["stod_system"] = $(this).find('.pr_stock').html();
        product[index]["stod_real"] = $(this).find('.data').val();
        product[index]["stod_selisih"] = $(this).find('.selisih').html();
        product[index]["stod_notes"] = $(this).find('.notes').val();
        product[index]["stod_id"] = $(this).find('.stod_id').val();
    })
    console.log(product);
    
    param = {
        sto_date:$('#tanggal').val(),//1 = In , 2 = Out
        category_id:$('#kategori').val(),
        staff_id:$('#penanggung-jawab').val(),
        sto_notes:$('#catatan').val(),//1 = Product , 2 = supplies
        item:JSON.stringify(product),
         _token:token
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