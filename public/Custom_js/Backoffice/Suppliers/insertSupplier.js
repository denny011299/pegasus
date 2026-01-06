autocompleteProv('#state_id');
autocompleteCity('#city_id');
autocompleteRekening("#bank_kode");

$(document).ready(function(){
    $('.btn-save').html(mode == 1?"Tambah Pemasok" : "Update Pemasok");
    if(mode==2) {
        console.log(data)
        $('#supplier_name').val(data.supplier_name);
        $('#supplier_email').val(data.supplier_email);
        $('#supplier_phone').val(data.supplier_phone);
        $('#supplier_pic').val(data.supplier_pic);
        $('#supplier_notes').val(data.supplier_notes);
        $('#supplier_address').val(data.supplier_address);
        $('#state_id').append(`<option value="${data.state_id}">${data.state_name}</option>`).trigger('change');
        $('#city_id').append(`<option value="${data.city_id}">${data.city_name}</option>`);
        $('#supplier_zipcode').val(data.supplier_zipcode);
        $('#supplier_bank').val(data.supplier_bank);
        $('#supplier_branch').val(data.supplier_branch);
        $('#supplier_account_name').val(data.supplier_account_name);
        $('#supplier_account_number').val(data.supplier_account_number);
        $('#supplier_ifsc').val(data.supplier_ifsc);
        $('#supplier_top').val(data.supplier_top);
        $('#bank_kode').append(`<option value="${data.bank_id}">${data.bank_name}</option>`);
        $('#preview_image').attr("src",public+data.supplier_image); 
    }
})

$(document).on("click", ".btn-save", function () {
    LoadingButton(this);
    $('.is-invalid').removeClass('is-invalid');
    $('.is-invalids').removeClass('is-invalids');
    var url = "/insertSupplier";


    var valid = 1;
    $(".fill").each(function(){
        if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
            console.log($(this))
            valid=-1;
            $(this).addClass('is-invalid');
        }
    });

    if($('#bank_kode').val()==null||$('#bank_kode').val()=="null"||$('#bank_kode').val()==""){
        valid=-1;
        $('#row-bank_kode .select2-selection--single').addClass('is-invalids');
    }
    if($('#state_id').val()==null||$('#state_id').val()=="null"||$('#state_id').val()==""){
        valid=-1;
        $('#row-province .select2-selection--single').addClass('is-invalids');
    }

    if($('#city_id').val()==null||$('#city_id').val()=="null"||$('#city_id').val()==""){
        valid=-1;
        $('#row-city .select2-selection--single').addClass('is-invalids');
    }

    if(valid==-1){
        notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
        ResetLoadingButton('.btn-save', mode == 1?"Tambah Pemasok" : "Update Pemasok");
        return false;
    };

    // Siapkan data untuk dikirim
    param = {
        supplier_name: $("#supplier_name").val(),
        supplier_email: $("#supplier_email").val(),
        supplier_phone: $("#supplier_phone").val(),
        supplier_pic: $("#supplier_pic").val(),
        supplier_address: $("#supplier_address").val(),
        supplier_notes: $("#supplier_notes").val(),
        state_id: $("#state_id").val(),
        city_id: $("#city_id").val(),
        supplier_zipcode: $("#supplier_zipcode").val(),
        supplier_bank: $("#supplier_bank").val(),
        supplier_branch: null,
        supplier_account_name: $("#supplier_account_name").val(),
        supplier_account_number: $("#supplier_account_number").val(),
        supplier_ifsc: null,
        supplier_top: $("#supplier_top").val(),
        bank_id: $("#bank_kode").val(),
        supplier_payment: 0,
        _token:token
    };

    if(mode==2){
        url = "/updateSupplier";
        param.supplier_id = data.supplier_id;
    }

    const fd = new FormData();
    for (const [key, value] of Object.entries(param)) {
        fd.append(key, value);
    }
    fd.append('image', $('#supplier_image')[0].files[0]);
    console.log($('#supplier_image')[0].files[0])

    LoadingButton($(this));
    $.ajax({
        url: url,
        method: "POST",
        data: fd,
        contentType: false,
        processData: false,
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
        success: function (response) {
            // Re-enable button
            ResetLoadingButton(".btn-save", mode == 1?"Tambah Pemasok" : "Update Pemasok");
            if(mode==1)notifikasi('success', "Berhasil Insert", "Berhasil Tambah Pemasok");
            else if(mode==2)notifikasi('success', "Berhasil Update", "Berhasil Update Pemasok");
            afterInsert();
        },
        error: function (xhr) {
            // Re-enable button
            ResetLoadingButton(".btn-save", mode == 1?"Tambah Pemasok" : "Update Pemasok");
            console.log(xhr);
        },
    });
});
    
$(document).on("change", "#supplier_image", function () {
    let file = this.files[0];
    if (file) {
        // ganti preview gambar
        let reader = new FileReader();
        reader.onload = function (e) {
            $("#preview_image").attr("src", e.target.result);
        };
        reader.readAsDataURL(file);
        // ganti nama file
        $("#file_name").text(file.name);
    }
});

$('#state_id').on('change', function() {
    let prov_id = $(this).val();

    if (prov_id) {
        // Panggil autocompleteCity dengan prov_id
        autocompleteCity('#city_id', null, prov_id);
    } else {
        $('#city_id').empty(); // kosongkan jika tidak ada provinsi
    }
});

function afterInsert() {
    window.location.href = "/supplier";
}

$(document).on('click', '.btn-back', function(){
    history.go(-1);
})