autocompleteProv('#state_id');
autocompleteCity('#city_id');

$(document).ready(function(){
    if(mode==2) {
        console.log(data)
        $('#customer_name').val(data.customer_name);
        $('#customer_email').val(data.customer_email);
        $('#customer_birthdate').val(data.customer_birthdate);
        $('#customer_phone').val(data.customer_phone);
        $('#customer_notes').val(data.customer_notes);
        $('#customer_address').val(data.customer_address);
        $('#state_id').append(`<option value="${data.state_id}">${data.state_name}</option>`);
        $('#city_id').append(`<option value="${data.city_id}">${data.city_name}</option>`);
        $('#customer_zipcode').val(data.customer_zipcode);
        $('#customer_bank').val(data.customer_bank);
        $('#customer_branch').val(data.customer_branch);
        $('#customer_account_name').val(data.customer_account_name);
        $('#customer_account_number').val(data.customer_account_number);
        $('#customer_ifsc').val(data.customer_ifsc);
        $('#preview_image').attr("src",public+data.customer_image); 
    }
})

$(document).on("click", ".btn-save", function () {
    LoadingButton(this);
    $('.is-invalid').removeClass('is-invalid');
    var url = "/insertCustomer";

    // check image
    if (mode==2)$('#customer_image').removeClass('fill');
    else if (mode==1) $('#customer_image').addClass('fill');

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
        ResetLoadingButton('.btn-save', 'Save changes');
        return false;
    };

    // Siapkan data untuk dikirim
    param = {
        customer_name: $("#customer_name").val(),
        customer_email: $("#customer_email").val(),
        customer_birthdate: $("#customer_birthdate").val(),
        customer_phone: $("#customer_phone").val(),
        customer_address: $("#customer_address").val(),
        customer_notes: $("#customer_notes").val(),
        state_id: $("#state_id").val(),
        city_id: $("#city_id").val(),
        customer_zipcode: $("#customer_zipcode").val(),
        customer_bank: $("#customer_bank").val(),
        customer_branch: $("#customer_branch").val(),
        customer_account_name: $("#customer_account_name").val(),
        customer_account_number: $("#customer_account_number").val(),
        customer_ifsc: $("#customer_ifsc").val(),
        customer_payment: 0,
        _token:token
    };

    if(mode==2){
        url = "/updateCustomer";
        param.customer_id = data.customer_id;
    }

    const fd = new FormData();
    for (const [key, value] of Object.entries(param)) {
        fd.append(key, value);
    }
    fd.append('image', $('#customer_image')[0].files[0]);
    console.log($('#customer_image')[0].files[0])

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
            ResetLoadingButton(".btn-save", 'Save changes');
            if(mode==1)notifikasi('success', "Successful Insert", "Successful Supplier Added");
            else if(mode==2)notifikasi('success', "Successful Update", "Successful Supplier Updated");
            afterInsert();
        },
        error: function (xhr) {
            // Re-enable button
            ResetLoadingButton(".btn-save", 'Save changes');
            console.log(xhr);
        },
    });
});
    
$(document).on("change", "#customer_image", function () {
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
    console.log($('#customer_image')[0].files[0])
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
    window.location.href = "/customer";
}

$(document).on('click', '.btn-back', function(){
    history.go(-1);
})