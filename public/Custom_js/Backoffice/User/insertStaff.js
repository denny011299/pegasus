function clearStaffFormInvalid() {
    $('.is-invalid').removeClass('is-invalid');
    $('#row-position .select2-selection, #row-warehouse .select2-selection')
        .removeClass('is-invalids')
        .each(function () {
            this.style.removeProperty('border');
        });
}

function markSelect2Invalid($select) {
    var $selection = $select.next('.select2-container').find('.select2-selection');
    if (!$selection.length) {
        $selection = $select.siblings('.select2-container').find('.select2-selection');
    }
    $selection.addClass('is-invalids').each(function () {
        // Inline !important agar menang vs style global Select2 multiple
        this.style.setProperty('border', '1px solid #dc3545', 'important');
    });
}

$(document).ready(function(){
    // Select2 lokal (bukan AJAX) — daftar role sudah di-render dari backend
    $('#staff_position').select2({
        placeholder: 'Pilih Posisi',
        allowClear: true,
        width: '100%'
    });

    $('#staff_warehouses').select2({
        placeholder: 'Pilih Gudang...',
        allowClear: true,
        width: '100%'
    });

    // Hapus border merah begitu user memilih nilai
    $('#staff_position').on('change', function () {
        if ($(this).val()) {
            $('#row-position .select2-selection').removeClass('is-invalids').each(function () {
                this.style.removeProperty('border');
            });
        }
    });
    $('#staff_warehouses').on('change', function () {
        var wh = $(this).val();
        if (wh && wh.length) {
            $('#row-warehouse .select2-selection').removeClass('is-invalids').each(function () {
                this.style.removeProperty('border');
            });
        }
    });

    if(mode==2 || mode==='2') {
        $('.content-page-header h5').text('Update Staf');
        $('.btn-save').text('Update Staf');
        $('#staff_password, #staff_confirm').removeClass('fill');
        $('#staff_password, #staff_confirm').closest('.input-block').find('.text-danger').remove();

        var staffData = (data && typeof data === 'object' && !Array.isArray(data)) ? data : {};
        
        let staffName = staffData.staff_name || "";
        let names = staffName.split(" ");
        $('#staff_first_name').val(names[0] || "");
        $('#staff_last_name').val(names.slice(1).join(" ") || "");
        $('#staff_email').val(staffData.staff_email || "");
        $('#staff_phone').val(staffData.staff_phone || "");
        $('#staff_username').val(staffData.staff_username || "");
        $('#staff_address').val(staffData.staff_address || "");

        if (staffData.role_id) {
            $('#staff_position').val(String(staffData.role_id)).trigger('change');
        }

        if (staffData.staff_warehouses) {
            try {
                let selected_wh = typeof staffData.staff_warehouses === 'string'
                    ? JSON.parse(staffData.staff_warehouses)
                    : staffData.staff_warehouses;
                $('#staff_warehouses').val(selected_wh).trigger('change');
            } catch(e) {}
        }
    }
});

$(document).on("click", "#btn_select_all_warehouses", function() {
    let state = $(this).attr('data-state');
    if (state !== 'clear') {
        let allOptions = [];
        $('#staff_warehouses option').each(function() {
            if($(this).val()) {
                allOptions.push($(this).val());
            }
        });
        $('#staff_warehouses').val(allOptions).trigger('change');
        $(this).attr('data-state', 'clear');
        $(this).html('<i class="fa fa-times"></i> Hapus Semua Gudang').removeClass('text-primary').addClass('text-danger');
    } else {
        $('#staff_warehouses').val([]).trigger('change');
        $(this).attr('data-state', 'all');
        $(this).html('<i class="fa fa-check-square"></i> Pilih Semua Gudang').removeClass('text-danger').addClass('text-primary');
    }
});

$(document).on("click", ".btn-save", function () {
    LoadingButton(this);
    clearStaffFormInvalid();
    var url = "/insertStaff";

    var valid = 1;
    $(".fill").each(function(){
        if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
            valid=-1;
            $(this).addClass('is-invalid');
        }
    });

    if($('#staff_position').val()==null||$('#staff_position').val()=="null"||$('#staff_position').val()==""){
        valid=-1;
        markSelect2Invalid($('#staff_position'));
    }

    let wh = $('#staff_warehouses').val();
    if (!wh || wh.length === 0) {
        valid = -1;
        markSelect2Invalid($('#staff_warehouses'));
    }

    let pass = $('#staff_password').val();
    let conf = $('#staff_confirm').val();

    if (mode == 1 || (mode == 2 && (pass !== "" || conf !== ""))) {
        if (pass === "") {
            valid = -1;
            $('#staff_password').addClass('is-invalid');
        }
        if (conf === "") {
            valid = -1;
            $('#staff_confirm').addClass('is-invalid');
        }
        if (pass !== "" && conf !== "" && pass !== conf) {
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
        staff_position: $("#staff_position").val(),
        staff_address: $("#staff_address").val(),
        staff_password: $("#staff_password").val(),
        staff_warehouses: JSON.stringify($("#staff_warehouses").val()),
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
            ResetLoadingButton(".btn-save", mode == 1?"Tambah Staff" : "Update Staff");

            if (response == -1) {
                if (mode==2) notifikasi('error', "Gagal Update", "Mohon cek kembali password");
                $('#staff_password').addClass('is-invalid');
                $('#staff_confirm').addClass('is-invalid');
            } else {
                if(mode==1)notifikasi('success', "Berhasil Insert", "Berhasil Tambah Staff");
                else if(mode==2)notifikasi('success', "Berhasil Update", "Berhasil Update Staff");
                afterInsert();
            }
        },
        error: function (xhr) {
            ResetLoadingButton(".btn-save", mode == 1?"Tambah Staff" : "Update Staff");
            console.log(xhr);
        },
    });
});
    
$(document).on("change", "#staff_image", function () {
    let file = this.files[0];
    if (file) {
        let reader = new FileReader();
        reader.onload = function (e) {
            $("#preview_image").attr("src", e.target.result);
        };
        reader.readAsDataURL(file);
        $("#file_name").text(file.name);
    }
});

function afterInsert() {
    window.location.href = "/staff";
}

$(document).on('click', '.btn-back', function(){
    history.go(-1);
})
