autocompleteProv('#state_id');
autocompleteCity('#city_id');
autocompleteRole('#staff_position');

$(document).ready(function(){
    if(mode==2) {
        console.log(data)
        let names = data.staff_name.split(" ");
        $('#staff_first_name').val(names[0]);
        $('#staff_last_name').val(names[1]);
        $('#staff_email').val(data.staff_email);
        $('#staff_phone').val(data.staff_phone);
        $('#staff_username').val(data.staff_username);
        // $('#staff_birthdate').val(data.staff_birthdate);
        // $('#staff_gender').val(data.staff_gender);
        // $('#staff_join_date').val(data.staff_join_date);
        // $('#staff_shift').append(`<option value="${data.staff_shift}">${data.staff_shift}</option>`);
        // $('#staff_departement').append(`<option value="${data.staff_departement}">${data.staff_departement}</option>`);
        $('#staff_position').append(`<option value="${data.role_id}">${data.role_name}</option>`);
        // $('#staff_emergency1').val(data.staff_emergency1);
        $('#staff_address').val(data.staff_address);
        // $('#country_id').append(`<option value="${data.country_id}">Indonesia</option>`);
        // $('#state_id').append(`<option value="${data.state_id}">${data.state_name}</option>`);
        // $('#city_id').append(`<option value="${data.city_id}">${data.city_name}</option>`);
        // $('#staff_zipcode').val(data.staff_zipcode);
        // $('#preview_image').attr("src", public+data.staff_image);
    }
})

$(document).on("click", ".btn-save", function () {
    LoadingButton(this);
    $('.is-invalid').removeClass('is-invalid');
    $('.is-invalids').removeClass('is-invalids');
    var url = "/insertStaff";

    // check image
    // if (mode==2)$('#staff_image').removeClass('fill');
    // else if (mode==1) $('#staff_image').addClass('fill');

    var valid = 1;
    $(".fill").each(function(){
        if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
            console.log($(this))
            valid=-1;
            $(this).addClass('is-invalid');
        }
    });
    if($('#staff_position').val()==null||$('#staff_position').val()=="null"||$('#staff_position').val()==""){
        valid=-1;
        $('#row-position .select2-selection--single').addClass('is-invalids');
    }

    if (mode == 1){
        if ($('#staff_confirm').val() != $('#staff_password').val()){
            valid = -1;
            $('#staff_password').addClass('is-invalid');
            $('#staff_confirm').addClass('is-invalid');
        }
    }

    if(valid==-1){
        notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
        ResetLoadingButton('.btn-save', mode == 1?"Tambah Staff" : "Update Staff");
        return false;
    };

    param = {
        staff_first_name: $("#staff_first_name").val(),
        staff_last_name: $("#staff_last_name").val(),
        staff_email: $("#staff_email").val(),
        staff_phone: $("#staff_phone").val(),
        staff_username: $("#staff_username").val(),
        // staff_birthdate: $("#staff_birthdate").val(),
        // staff_gender: $("#staff_gender").val(),
        // staff_join_date: $("#staff_join_date").val(),
        // staff_shift: $("#staff_shift").val(),
        // staff_departement: $("#staff_departement").val(),
        staff_position: $("#staff_position").val(),
        // staff_emergency1: $("#staff_emergency1").val(),
        staff_address: $("#staff_address").val(),
        // country_id: $("#country_id").val(),
        // state_id: $("#state_id").val(),
        // city_id: $("#city_id").val(),
        // staff_zipcode: $("#staff_zipcode").val(),
        staff_password: $("#staff_password").val(),
        _token: token
    };

    if(mode==2){
        url = "/updateStaff";
        param.staff_id = data.staff_id;
    }

    const fd = new FormData();
    for (const [key, value] of Object.entries(param)) {
        fd.append(key, value);
    }
    // fd.append('image', $('#staff_image')[0].files[0]);

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
            ResetLoadingButton(".btn-save", mode == 1?"Tambah Staff" : "Update Staff");

            if (response == -1) {
                if (mode==2) notifikasi('error', "Gagal Update", "Mohon cek kembali password");
                $('#staff_password').addClass('is-invalid');
                $('#staff_confirm').addClass('is-invalid');
            }
            
            if(mode==1)notifikasi('success', "Berhasil Insert", "Berhasil Tambah Staff");
            else if(mode==2)notifikasi('success', "Berhasil Update", "Berhasil Update Staff");
            afterInsert();
        },
        error: function (xhr) {
            // Re-enable button
            ResetLoadingButton(".btn-save", mode == 1?"Tambah Staff" : "Update Staff");
            console.log(xhr);
        },
    });
});
    
$(document).on("change", "#staff_image", function () {
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
    console.log($('#staff_image')[0].files[0])
});

// $('#state_id').on('change', function() {
//     let prov_id = $(this).val();

//     if (prov_id) {
//         // Panggil autocompleteCity dengan prov_id
//         autocompleteCity('#city_id', null, prov_id);
//     } else {
//         $('#city_id').empty(); // kosongkan jika tidak ada provinsi
//     }
// });

function afterInsert() {
    window.location.href = "/staff";
}

$(document).on('click', '.btn-back', function(){
    history.go(-1);
})