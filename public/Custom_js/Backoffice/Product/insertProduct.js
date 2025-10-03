var dataRelasi;
var canAdd = true;
var rowId = 0;
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
        
        data.pr_variant.forEach(element => {
            
            addRow(element.product_variant_name);
            $('.row-variant').last().find('.variant_sku').val(element.product_variant_sku);
            $('.row-variant').last().find('.variant_price').val(formatRupiah(element.product_variant_price));
            $('.row-variant').last().find('.variant_barcode').val(element.product_variant_barcode);
            $('.row-variant').last().find('.variant_id').val(element.product_variant_id);
            $('.row-variant').last().find('.variant_alert').val(element.product_variant_alert);
        
        });
        console.log(canAdd);
        
        $('#tbRelasi').html("");
        dataRelasi = data.pr_relasi;
        console.log(data.pr_relasi)
        dataRelasi.forEach((element,index) => {
            console.log(element)
            canAdd=false;
            addRowRelasi(element,element);
            canAdd=true;
        })
        reset();
        console.log(canAdd);
        
        $('#product_unit').empty();
        $('#unit_id').empty(); 
        data.pr_unit.forEach(element => {
            var newOption = new Option(element.unit_short_name, element.unit_id, true, true);
            $('#product_unit').append(newOption).trigger("change");
        });
        $('#unit_id').val(data.unit_id).trigger("change");
    }
})

$('#unit_id').on('click', function() {
   $('.select2-search__field').remove();
});
$('#unit_id').on('change', function() {
   $('.select2-search__field').remove();
});

$(document).on('click','.btnAddRow',function(){
    $('#tbVariant').html("")
    if($('#product_variant').val()!=""&&$('#product_variant').val()!=null) {
        var data = $('#product_variant').select2('data')[0];
        data.name = JSON.parse(data.variant_attribute);
        data.name.forEach(element => {
            addRow(element);
        });
        $('#product_variant').empty();
    }
   else addRow();
});

function addRow(names="") {
    $('#tbVariant').append(`
        <tr class="row-variant">
            <td><input type="text" class="form-control fill variant_name" name="" id="" placeholder="Masukan Nama" value="${names}"></td>
            <td><input type="text" class="form-control fill variant_sku" name="" id="" placeholder="Masukan Sku"></td>
            <td><input type="text" class="form-control fill variant_price nominal_only" name="" id="" placeholder="Masukan Harga"></td>
            <td><input type="text" class="form-control variant_barcode" name="" id="" placeholder="Masukan Barcode"><input type="hidden" class="form-control variant_id" name="" id="" placeholder=""></td>
            <td>
                <div class="input-group mb-3">
                    <input type="text" class="form-control fill variant_alert" aria-describedby="basic-addon3">
                    <span class="input-group-text unit_alert">-</span>
                </div>
            </td>
            <td class="text-center d-flex align-items-center">
                <a class="p-2 btn-action-icon btn_delete_row mx-auto"  href="javascript:void(0);">
                    <i data-feather="trash-2" class="feather-trash-2"></i>
                </a>
            </td>
        </tr>
        <tr class="row-relasi-wrapper" data-variant="${rowId}">
            <td colspan="6">
                <table class="table table-bordered mb-2">
                    <thead>
                        <tr>
                            <td>Name Unit 1</td>
                            <td>Name Unit 2</td>
                        </tr>
                    </thead>
                    <tbody class="tbRelasi" id="tbRelasi_${rowId}">
                    </tbody>
                </table>
            </td>
        </tr>
    `);
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

    var relasi = [];
    $('.row-relasi').each(function(){
        var tmp = {
            unit_id_1: $(this).find('.unit1').data('unit_id'),
            unit_value_1: $(this).find('.unit1').val(),
            unit_id_2: $(this).find('.unit2').data('unit_id'),
            unit_value_2: $(this).find('.unit2').val(),
        };
        relasi.push(tmp);
    });

    param.product_variant = JSON.stringify(temp);
    param.product_relasi = JSON.stringify(relasi);

    if(mode==2){
        url="/updateProduct";
        param.product_id = $('#add_product').attr("product_id");
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
            if(mode==1)notifikasi('success', "Berhasil Insert", "Berhasil Tambah Pelanggan");
            else if(mode==2)notifikasi('success', "Berhasil Update", "Berhasil Update Pelanggan");
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
        console.log(dataRelasi)
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
    console.log(element1)
    console.log(element2.pr_unit_value_2)
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
}

$(document).on("click",".btn_delete_row",function(){
    if($('.row-variant').length<2) {
        notifikasi('error', "Gagal Hapus", "Minimal 1 varian harus ada");
        return false;
    }
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