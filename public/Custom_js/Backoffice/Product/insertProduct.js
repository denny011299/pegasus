var dataRelasi=[];
var canAdd = true;
var rowId = 0;
var relasi =[];

autocompleteVariant("#product_variant");
autocompleteCategory("#product_category");
autocompleteUnit("#product_unit");

$(document).ready(function() {
    if (mode == 1) {
        canAdd=true;
        $('#tbVariant').html("")
        addRow();
        $('#tbRelasi').html("");
        reset();
    }

    if (mode == 2) {
        $('#product_name').val(data.product_name);
        $('#product_category').empty().append(`<option value="${data.category_id}">${data.product_category}</option>`);
        $('#tbVariant').html("");
        data.pr_variant.forEach((element,index) => {
            
            addRow(element.product_variant_name);
            $('.row-variant').last().find('.variant_sku').val(element.product_variant_sku);
            $('.row-variant').last().find('.variant_price').val(formatRupiah(element.product_variant_price));
            $('.row-variant').last().find('.variant_barcode').val(element.product_variant_barcode);
            $('.row-variant').last().find('.variant_id').val(element.product_variant_id);
            $('.row-variant').last().find('.variant_alert').val(element.product_variant_alert);
          
            
        });

        $('#tbRelasi').html("");
        dataRelasi.forEach((element,index) => {
            addRowRelasi(element,element);
            canAdd=true;
        })
        reset();
        
        $('#product_unit').empty();
        $('#unit_id').empty(); 
        data.pr_unit.forEach(element => {
            var newOption = new Option(element.unit_short_name, element.unit_id, true, true);
            $('#product_unit').append(newOption).trigger("change");
        });
        $('#unit_id').val(data.unit_id).trigger("change");
        
        relasi = [];
        data.pr_variant.forEach((element,index) => {
            relasi.push([]);

            element.relasi.forEach(rl => {
                relasi[index].push({
                    ...rl,
                    unit_id_1: rl.pr_unit_id_1,
                    unit_value_1: rl.pr_unit_value_1,
                    unit_id_2: rl.pr_unit_id_2,
                    unit_value_2: rl.pr_unit_value_2,
                });
            });
            if(element.relasi.length==0){
              syncRelasi();
            }
        });
    }
})
function syncRelasi() {
    var idx = relasi.length-1;
    dataRelasi.forEach((element,index) => {
         if(index>0){
             relasi[idx].push({
                 pvr_id : null,
                 unit_id_1: element.id,
                 unit_value_1: 1,
                 unit_id_2: element.id,
                 unit_value_2: 0,
             });
         }
     });

}
$('#unit_id').on('click', function() {
   $('.select2-search__field').remove();
});
$('#unit_id').on('change', function() {
   $('.select2-search__field').remove();
});

$(document).on('click','.btnAddRow',function(){
    
    if($('#product_variant').val()!=""&&$('#product_variant').val()!=null) {
        $('#tbVariant').html("")
        relasi = [];
        var data = $('#product_variant').select2('data')[0];
        data.name = JSON.parse(data.variant_attribute);
        data.name.forEach((element,idx) => {
            relasi.push([]);
            addRow(element,idx);
        });
        $('#product_variant').empty();
    }
   else{ 
        relasi.push([]);
        addRow();
    }
});

function addRow(names="",idx=0) {
    $('#tbVariant').append(`
        <tr class="row-variant">
            <td><input type="text" class="form-control fill variant_name" name="" id="" placeholder="Masukan Nama" value="${names}"></td>
            <td><input type="text" class="form-control fill variant_sku" name="" id="" placeholder="Masukan Sku"></td>
            <td><input type="text" class="form-control fill variant_price nominal_only" name="" id="" placeholder="Masukan Harga*"></td>
            <td><input type="text" class="form-control variant_barcode" name="" id="" placeholder="Masukan Barcode"><input type="hidden" class="form-control variant_id" name="" id="" placeholder=""></td>
            <td>
                <div class="input-group">
                    <input type="text" class="form-control fill variant_alert" aria-describedby="basic-addon3">
                    <span class="input-group-text unit_alert">-</span>
                </div>
            </td>
            <td class="text-center d-flex align-items-center text-center">
                <a class="p-2 btn-action-icon btn_delete_row"  href="javascript:void(0);">
                    <i data-feather="trash-2" class="feather-trash-2"></i>
                </a>
                <a class="p-2 btn-action-icon btn_edit_relasi ms-2 "  href="javascript:void(0);">
                    <i data-feather="git-merge" class="feather-git"></i>
                </a>
            </td>
        </tr>
    `);

        
    dataRelasi.forEach((element,index) => {
        if(index>0){
            relasi[idx].push({
                pvr_id : null,
                unit_id_1: element.id,
                unit_value_1: 1,
                unit_id_2: element.id,
                unit_value_2: 0,
            });
        }
    });


    feather.replace();
    rowId++;
}

$(document).on("click",".btn-save",function(){
    LoadingButton(this);
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
        ResetLoadingButton('.btn-save', 'Simpan perubahan');
        return false;
    };
    
    param = {
         product_name:$('#product_name').val(),
         category_id:$('#product_category').val(),
         unit_id:$('#unit_id').val(),
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
            variant_alert: $(this).find('.variant_alert').val(),
            product_variant_id: $(this).find('.variant_id').val(),
        };
        temp.push(variant);
    });

   // var relasi = [];
    $('.row-relasi').each(function(){
        var tmp = {
            unit_id_1: $(this).find('.unit1').data('unit_id'),
            unit_value_1: $(this).find('.unit1').val(),
            unit_id_2: $(this).find('.unit2').data('unit_id'),
            unit_value_2: $(this).find('.unit2').val(),
        };
       // relasi.push(tmp);
    });

    param.product_variant = JSON.stringify(temp);
    param.product_relasi = JSON.stringify(relasi);

    if(mode==2){
        url="/updateProduct";
        param.product_id = data.product_id;
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
            ResetLoadingButton(".btn-save", 'Simpan Perubahan');
            if(mode==1)notifikasi('success', "Berhasil Insert", "Berhasil Tambah Produk");
            else if(mode==2)notifikasi('success', "Berhasil Update", "Berhasil Update Produk");
            afterInsert();
        },
        error:function(e){
            ResetLoadingButton(".btn-save", 'Simpan perubahan');
            console.log(e);
        }
    });
});

function afterInsert() {
    window.location.href = "/product";
}

$(document).on("change","#product_unit",function(){
    dataRelasi = $(this).select2("data");
    // Pengecekan apakah sudah selected atau belum
    var select = dataRelasi.length==1?1:$('#unit_id').val();
    
    $('#unit_id').html("");
    dataRelasi.forEach(item => {
        $('#unit_id').append(`<option value="${item.id}">${item.text}</option>`);
    });

    if(dataRelasi.length>1)$('#unit_id').val(select);
    else $('#unit_id').eq(select).prop('selected', true);

    $('#unit_id').trigger("change");
    
    if(canAdd==true){
        
        $('#tbRelasi').html("");
        dataRelasi.forEach((element,index) => {
            if(index>0)addRowRelasi(dataRelasi[index-1],element); 
        });

    }
    
    if (dataRelasi.length == 1) {
        $('#tbRelasi').html("");
    }

    else if (dataRelasi.length < 1) {
        $('#tbRelasi').html("");
        $('.unit_alert').each(function() {
            $(this).html("-");
        })
        $('#unit_id').val("");
    }
});

$(document).on("change","#unit_id",function(){
    $('.unit_alert').each(function() {
        $(this).html($('#unit_id option:selected').text().trim());
    });
    $('.select2-search__field').remove();
});

$('#unit_id').on('click', function() {
   $('.select2-search__field').remove();
});

function addRowRelasi(element1,element2) {
    $('#tbRelasi').append(`
        <tr class="row-relasi" left="${element1.pr_unit_id_1 ? element1.pr_unit_id_1 : element2.id}" right="${element2.pr_unit_id_2 ? dataRelasi[dataRelasi.length-1].pr_unit_id_2 : dataRelasi[dataRelasi.length-1].id}">
            <td>
                <div class="input-group">
                    <input type="text" class="form-control nominal-only unit1 fill" value="1"
                    data-unit_id="${element1.pr_unit_id_1 ? element1.pr_unit_id_1 : element1.id}" disabled>
                    <span class="input-group-text unit_text_1">
                        ${element1.pr_unit_name_1 ? element1.pr_unit_name_1 : element1.text}
                    </span>
                    <input type="hidden" class="form-control pr_id" value="${element1.pr_id??''}">
                </div>
            </td>
            <td>
                <div class="input-group">
                    <input type="text" class="form-control nominal-only unit2 fill" placeholder="Masukan Nilai"
                    data-unit_id="${element2.pr_unit_id_2 ? element2.pr_unit_id_2 : element2.id}" value="${element2.pr_unit_value_2 ? element2.pr_unit_value_2 : ""}">
                    <span class="input-group-text unit_text_2">
                        ${element2.pr_unit_name_2 ? element2.pr_unit_name_2 : element2.text}
                    </span>
                </div>
            </td>
        </tr>    
    `);      
    if($('.row-variant').length > relasi.length) {
        for (let i = 0; i < $('.row-variant').length - relasi.length ; i++) {
            relasi.push([]);
        }
    }
    console.log("length variant "+relasi.length);
    
    for (let i = 0; i < relasi.length; i++) {
        var cari=-1;
        
        relasi[i].forEach(element => {
            
            if(element.unit_id_1==element1.unit_id && element.unit_id_2==element2.unit_id) cari=i;
            
        });

         if(cari==-1&&element1.unit_id!=element2.unit_id) {
            relasi[i].push({
                pvr_id : null,
                unit_id_1: element1.id,
                unit_value_1: 1,
                unit_id_2: element2.id,
                unit_value_2: 0,
            });
        }
    }
   cekKembar();
}

function cekKembar() {
    relasi.forEach(element => {
        element.forEach((item,index) => {
            if(item.unit_id_1==item.unit_id_2) element.splice(index,1);
        });
    });
}
$(document).on("click",".btn_delete_row",function(){
    if($('.row-variant').length<2) {
        notifikasi('error', "Gagal Hapus", "Minimal 1 varian harus ada");
        return false;
    }
    var index = $(this).closest("tr").index();
    relasi.splice(index,1);
    $(this).closest("tr").remove();
});

function reset() {
    $('#tbRelasi').html(`
        <tr>
             <td class="text-center" colspan="2">
                 Pilih Minimal 2 unit untuk mengatur relasi unit
             </td>
        </tr>
    `);
}

$(document).on('click', '.btn-back', function(){
    history.go(-1);
})

$(document).on('click', '.btn-clear', function(){
    $('.form-control').val("");
    $('.form-select').empty();
})

$(document).on('click', '#btnSaveRelasi', function(){
    var index = $(this).attr('index');
    var valid=1;
    $('.is-invalid').removeClass('is-invalid');
    $(".unit2").each(function(){
        if($(this).val()==null||$(this).val()==0||$(this).val()==""){
            valid=-1;
            $(this).addClass('is-invalid');
        }
    });
    
    if(valid==-1){
        return false;
    }
    console.log(relasi[index]);
    
    if(relasi[index].length==0){
        syncRelasi();
    }
    relasi[index].forEach((element,idx) => {
        element.unit_value_2 = $('.unit2').eq(idx).val();
    }); 
    console.log(relasi[index]);

    $('#modalRelasi').modal('hide');
    notifikasi('success', "Berhasil Simpan", 'Berhasil Simpan Relasi Unit');
});

$(document).on('click', '.btn_edit_relasi', function(){
    $('.is-invalid').removeClass('is-invalid');
    var index = $(this).closest("tr").index();
    
    $('#btnSaveRelasi').attr('index',index);
    $('.unit2').each(function(indexRow) {
        if(relasi[index][[indexRow]])$(this).val(relasi[index][indexRow].unit_value_2);
        else  $(this).val(0);
    });
    
    $('#modalRelasi').modal('show');
})