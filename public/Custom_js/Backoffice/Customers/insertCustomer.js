$(document).ready(function(){
    $('.btn-save').html(mode == 1?"Tambah Armada" : "Update Armada");
    if(mode==2) {
        console.log(data)
        $('#customer_pic').val(data.customer_pic);
        $('#customer_pic_phone').val(data.customer_pic_phone);
        $('#customer_notes').val(data.customer_notes);
    }
})

$(document).on("click", ".btn-save", function () {
    LoadingButton(this);
    $('.is-invalid').removeClass('is-invalid');
    var url = "/insertCustomer";

    var valid = 1;
    $(".fill").each(function(){
        if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
            console.log($(this))
            valid=-1;
            $(this).addClass('is-invalid');
        }
    });
    if(valid==-1){
        notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
        ResetLoadingButton('.btn-save', mode == 1?"Tambah Armada" : "Update Armada");
        return false;
    };

    // Siapkan data untuk dikirim
    param = {
        customer_pic: $("#customer_pic").val(),
        customer_pic_phone: $("#customer_pic_phone").val(),
        customer_notes: $("#customer_notes").val(),
        _token:token
    };

    if(mode==2){
        url = "/updateCustomer";
        param.customer_id = data.customer_id;
        param.customer_code = data.customer_code;
    }
    console.log(param);

    LoadingButton($(this));
    $.ajax({
        url: url,
        method: "POST",
        data: param,
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
        success: function (response) {
            // Re-enable button
            ResetLoadingButton(".btn-save", mode == 1?"Tambah Armada" : "Update Armada");
            if(mode==1)notifikasi('success', "Berhasil Insert", "Berhasil Tambah Armada");
            else if(mode==2)notifikasi('success', "Berhasil Update", "Berhasil Update Armada");
            afterInsert();
        },
        error: function (xhr) {
            // Re-enable button
            ResetLoadingButton(".btn-save", mode == 1?"Tambah Armada" : "Update Armada");
            console.log(xhr);
        },
    });
});

$('#state_id').on('change', function() {
    let prov_id = $(this).val();
    console.log(prov_id);
    if (prov_id) {
        // Panggil autocompleteCity dengan prov_id
        autocompleteCity('#city_id', null, prov_id);
    } else {
        $('#city_id').empty(); // kosongkan jika tidak ada provinsi
    }
});

$('#city_id').on('change', function() {
    let city_id = $(this).val();
    console.log(city_id);
    if (city_id) {
        // Panggil autocompleteDistrict dengan city_id
        autocompleteDistrict('#district_id', null, city_id);
    } else {
        $('#district_id').empty(); // kosongkan jika tidak ada city
    }
});

function afterInsert() {
    window.location.href = "/customer";
}

$(document).on('click', '.btn-back', function(){
    history.go(-1);
})

$(document).on('click', '.btn-clear', function(){
    $('.form-control').val("");
    $('.form-select').empty();
})