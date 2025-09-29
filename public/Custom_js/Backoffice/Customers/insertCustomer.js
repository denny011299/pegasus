autocompleteProv('#state_id');
autocompleteCity('#city_id');
autocompleteArea('#area_id');
autocompleteDistrict('#district_id');
autocompleteStaffSales('#sales_id');

$(document).ready(function(){
    if(mode==2) {
        console.log(data)
        $('#area_id').append(`<option value="${data.area_id}">${data.area_name}</option>`);
        $('#customer_name').val(data.customer_name);
        $('#customer_email').val(data.customer_email);
        $('#state_id').append(`<option value="${data.state_id}">${data.state_name}</option>`).trigger('change');
        $('#city_id').append(`<option value="${data.city_id}">${data.city_name}</option>`).trigger('change');
        $('#district_id').append(`<option value="${data.district_id}">${data.district_name}</option>`);
        $('#customer_address').val(data.customer_address);
        $('#customer_phone').val(data.customer_phone);
        $('#customer_pic').val(data.customer_pic);
        $('#customer_pic_phone').val(data.customer_pic_phone);
        $('#customer_notes').val(data.customer_notes);
        $('#sales_id').append(`<option value="${data.sales_id}">${data.staff_name}</option>`);
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
        ResetLoadingButton('.btn-save', 'Simpan Perubahan');
        return false;
    };

    // Siapkan data untuk dikirim
    param = {
        area_id: $('#area_id').val(),
        customer_name: $("#customer_name").val(),
        customer_email: $("#customer_email").val(),
        state_id: $("#state_id").val(),
        city_id: $("#city_id").val(),
        district_id: $("#district_id").val(),
        customer_address: $("#customer_address").val(),
        customer_phone: $("#customer_phone").val(),
        customer_pic: $("#customer_pic").val(),
        customer_pic_phone: $("#customer_pic_phone").val(),
        customer_notes: $("#customer_notes").val(),
        sales_id: $('#sales_id').val(),
        customer_payment: 0,
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
            ResetLoadingButton(".btn-save", 'Simpan Perubahan');
            if(mode==1)notifikasi('success', "Berhasil Insert", "Berhasil Tambah Pelanggan");
            else if(mode==2)notifikasi('success', "Berhasil Update", "Berhasil Update Pelanggan");
            afterInsert();
        },
        error: function (xhr) {
            // Re-enable button
            ResetLoadingButton(".btn-save", 'Simpan Perubahan');
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