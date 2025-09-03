autocompleteProv('#state_id');
autocompleteCity('#city_id');

$(document).ready(function() {
    $('#company_name').val(data.company_name ? data.company_name : "");
    $('#company_email').val(data.company_email ? data.company_email : ""); 
    $('#company_phone').val(data.company_phone ? data.company_phone : ""); 
    $('#state_id').append(`<option value="${data.state_id}">${data.state_name}</option>`);
    $('#city_id').append(`<option value="${data.city_id}">${data.city_name}</option>`);
    $('#company_address').val(data.company_address ? data.company_address : "");
    $('#company_zipcode').val(data.company_zipcode ? data.company_zipcode : "");
    $('#preview_image1').attr("src", data.logo ? public+data.logo : dummyLogo);
    $('#preview_image2').attr("src", data.favicon ? public+data.favicon : dummyIcon);
})

$(document).on('click', '.btn-save', function(){
    $(".is-invalid").removeClass('is-invalid');
    var url = "/insertSetting";

    // Validation

    var formData = new FormData();
    formData.append('_token', token); 

    formData.append('company_name', $("#company_name").val());
    formData.append('company_email', $("#company_email").val());
    formData.append('company_phone', $("#company_phone").val());
    formData.append('state_id', $("#state_id").val());
    formData.append('state_name', $("#state_id option:selected").text().trim());
    formData.append('city_id', $("#city_id").val());
    formData.append('city_name', $("#city_id option:selected").text().trim());
    formData.append('company_address', $("#company_address").val());
    formData.append('company_zipcode', $("#company_zipcode").val());

    if ($('#preview_image1').attr("src") != dummyLogo){
        formData.append('logo', $('#company_logo')[0].files[0]);
    }
    if ($('#preview_image2').attr("src") != dummyIcon){
        formData.append('favicon', $('#company_icon')[0].files[0]);
    }

    $.ajax({
        url: url,
        method: "POST",
        data: formData, 
        processData: false, 
        contentType: false, 
        success: function(e) {
            console.log(e);
            notifikasi('success', "Berhasil Insert", "");
           
        },
        error: function(e) {
            notifikasi('error', "Terjadi Kesalahan", "");
            console.log(e);
        }
    });
})

$(document).on("change", "#company_logo", function () {
    let file = this.files[0];
    if (file) {
        // ganti preview gambar
        let reader = new FileReader();
        reader.onload = function (e) {
            $("#preview_image").attr("src", e.target.result);
        };
        reader.readAsDataURL(file);
    }
    console.log($('#company_logo')[0].files[0])
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

$(document).on("change", "#company_logo", function () {
    let file = this.files[0];
    if (file) {
        // ganti preview gambar
        let reader = new FileReader();
        reader.onload = function (e) {
            $("#preview_image1").attr("src", e.target.result);
        };
        reader.readAsDataURL(file);
    }
});

$(document).on("change", "#company_icon", function () {
    let file = this.files[0];
    if (file) {
        // ganti preview gambar
        let reader = new FileReader();
        reader.onload = function (e) {
            $("#preview_image2").attr("src", e.target.result);
        };
        reader.readAsDataURL(file);
    }
});