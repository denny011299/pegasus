    var mode = 1;
    var product = [];
    // autocompleteCategory("#category_id",null,1);
    refreshStockOpname();
    $(document).ready(function () {
    //    if(data.category_id!=null)$('#category_id').append(`<option value="${data.category_id}">${data.category_name}</option>`).trigger("change");
    //    if(mode==2){
    //     $('#staff').val(data.created_by);
    //    }
    })

    function refreshStockOpname() {
        $.ajax({
            url:"/getDetailStockOpname",
            method:"get",
            data:{
                category_id:$('#category_id').val(),
                _token:'{{ csrf_token() }}'
            },
            success:function(e){
                // product = JSON.parse(e);
                console.log(e);
                
                $('#tbStock').html("");
                e.forEach((item,index) => {
                    var st = "";
                    // if(mode==2)  {
                    //     if(stp_type==1)st =  getData(item.pr_id);
                    //     else if(stp_type==2)st =  getData(item.sup_id);
                    // }
                    console.log(item);
                    
                    $('#tbStock').append(`
                        <tr class="row-stock">
                            <td class="text-center">${index+1}</td>
                            <td>${item.pr_sku}</td>
                            <td>${item.pr_name}</td>
                            <td class="text-center pt-2 pr_stock">${mode==2?st.stpd_stock:item.pr_stock}</td>
                            <td class="text-center" style="width:10%">
                                <input type="text" class="form-control real-stock text-center" value="${mode==2?st.stpd_real_stock:0}">
                            </td>
                            <td class="text-center pt-2 selisih">0</td>
                            <td class="">
                                <input type="text" class="form-control notes" placeholder="Catatan.." value="${mode==2?st.stpd_note:''}">
                                <input type="hidden" class="form-control input-selesih" placeholder="Catatan.."  >
                            </td>
                        </tr>
                    `);
                });
                $('.real-stock').trigger("keyup");
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
        var rl = parseInt($(this).closest(".row-stock").find('.real-stock').val());
        var st = parseInt($(this).closest(".row-stock").find('.pr_stock').html());
        if(isNaN(rl)) rl=0;
        if(isNaN(st)) rl=0;
        var sh = rl-st;
        var sh_text = sh;
        if(sh>0) sh_text = "+"+sh;
        else if(sh<0) sh_text = sh;
        $(this).closest(".row-stock").find('.selisih').html(sh_text);
        $(this).closest(".row-stock").find('.input-selisih').val(sh);
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
    var url ="/admin/insertStockOpname";
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
        product[index]["real_stok"] = $(this).find('.real-stock').val();
        product[index]["selisih"] = $(this).find('.input-selesih').val();
        product[index]["notes"] = $(this).find('.notes').val();
    })
    param = {
        ms_type:$('#input-type').val(),//1 = In , 2 = Out
        category_id:$('#category_id').val(),
        created_by:$('#staff').val(),
        stp_type:stp_type,//1 = Product , 2 = supplies
        item:JSON.stringify(product),
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
            toastr.success('', 'Berhasil Tambah Stock Opname');
            if(stp_type==1)window.location.href="/admin/StockOpname";
            else if(stp_type==2)window.location.href="/admin/StockOpnameSupply";
        },
        error:function(e){
            console.log(e);
        }
    });
}
