function clearStaffFormInvalid() {
    $(".is-invalid").removeClass("is-invalid");
    $("#row-position .select2-selection")
        .removeClass("is-invalids")
        .each(function () {
            this.style.removeProperty("border");
        });
    $(".warehouse-list-container").removeClass("border-danger");
}

function markSelect2Invalid($select) {
    var $selection = $select
        .next(".select2-container")
        .find(".select2-selection");
    if (!$selection.length) {
        $selection = $select
            .siblings(".select2-container")
            .find(".select2-selection");
    }
    $selection.addClass("is-invalids").each(function () {
        // Inline !important agar menang vs style global Select2 multiple
        this.style.setProperty("border", "1px solid #dc3545", "important");
    });
}

$(document).ready(function () {
    // Select2 lokal (bukan AJAX) — daftar role sudah di-render dari backend
    $("#staff_position").select2({
        placeholder: "Pilih Posisi",
        allowClear: true,
        width: "100%",
    });

    // Checkbox interaction: hapus border merah jika dicentang
    $(".chk-warehouse").on("change", function () {
        var $chk = $(this);
        if (!$chk.prop("checked") && isKepalaWarehouse($chk.val())) {
            $chk.prop("checked", true);
            notifikasi(
                "error",
                "Tidak bisa dinonaktifkan",
                "Staf ini Kepala Operasional gudang tersebut.",
            );
            return;
        }
        if ($(".chk-warehouse:checked").length > 0) {
            $(".warehouse-list-container").removeClass("border-danger");
        }
    });

    // Hapus border merah begitu user memilih nilai
    $("#staff_position").on("change", function () {
        if ($(this).val()) {
            $("#row-position .select2-selection")
                .removeClass("is-invalids")
                .each(function () {
                    this.style.removeProperty("border");
                });
        }
    });

    if (mode == 2 || mode === "2") {
        $(".content-page-header h5").text("Update Staf");
        $(".btn-save").text("Update Staf");
        $("#staff_password, #staff_confirm").removeClass("fill");
        $("#staff_password, #staff_confirm")
            .closest(".input-block")
            .find(".text-danger")
            .remove();

        var staffData =
            data && typeof data === "object" && !Array.isArray(data)
                ? data
                : {};

        let staffName = staffData.staff_name || "";
        let names = staffName.split(" ");
        $("#staff_first_name").val(names[0] || "");
        $("#staff_last_name").val(names.slice(1).join(" ") || "");
        $("#staff_email").val(staffData.staff_email || "");
        $("#staff_phone").val(staffData.staff_phone || "");
        $("#staff_username").val(staffData.staff_username || "");
        $("#staff_address").val(staffData.staff_address || "");
        setStaffEsignPreview(staffData.signature_data_uri || null);
        staffEsignSaved = staffData.signature_data_uri || null;
        staffEsignPending = null;

        if (staffData.role_id) {
            $("#staff_position")
                .val(String(staffData.role_id))
                .trigger("change");
        }

        if (staffData.staff_warehouses) {
            try {
                let selected_wh =
                    typeof staffData.staff_warehouses === "string"
                        ? JSON.parse(staffData.staff_warehouses)
                        : staffData.staff_warehouses;
                if (selected_wh && Array.isArray(selected_wh)) {
                    selected_wh.forEach(function (id) {
                        $("#wh_" + id).prop("checked", true);
                    });
                }
            } catch (e) {}
        }

        kepalaWarehouseIds().forEach(function (id) {
            $("#wh_" + id)
                .prop("checked", true)
                .attr("data-kepala", "1");
        });
    }
});

/** E-sign: preview tersimpan + pending dari modal (replace saat Update Staff). */
var staffEsignSaved = null;
var staffEsignPending = null;
var staffEsignModalPad = null;
var staffEsignUploadUri = null;

function setStaffEsignPreview(uri) {
    if (uri) {
        $("#staff_esign_preview").attr("src", uri);
        $("#staff_esign_preview_wrap").show();
        $("#btn_staff_esign_label").text("Ubah tanda tangan");
    } else {
        $("#staff_esign_preview").removeAttr("src");
        $("#staff_esign_preview_wrap").hide();
        $("#btn_staff_esign_label").text("Tambah tanda tangan");
    }
}

function staffEsignMode() {
    return $('input[name="staff_esign_mode"]:checked').val() || "draw";
}

function staffEsignShowMode(mode) {
    var draw = mode === "draw";
    $("#staff_esign_panel_draw").toggle(draw);
    $("#staff_esign_panel_upload").toggle(!draw);
}

function staffEsignNormalizeToDataUri(file, done) {
    if (!file || !file.type || file.type.indexOf("image/") !== 0) {
        notifikasi("error", "File", "Pilih file gambar (PNG/JPG/WebP).");
        return;
    }
    if (file.size > 2 * 1024 * 1024) {
        notifikasi("error", "Terlalu besar", "Maksimal 2 MB.");
        return;
    }
    var reader = new FileReader();
    reader.onload = function () {
        var img = new Image();
        img.onload = function () {
            var w = 560;
            var h = Math.round(w * (9 / 16));
            var canvas = document.createElement("canvas");
            canvas.width = w;
            canvas.height = h;
            var ctx = canvas.getContext("2d");
            ctx.fillStyle = "#ffffff";
            ctx.fillRect(0, 0, w, h);
            var scale = Math.min(w / img.width, h / img.height);
            var dw = img.width * scale;
            var dh = img.height * scale;
            ctx.drawImage(img, (w - dw) / 2, (h - dh) / 2, dw, dh);
            done(canvas.toDataURL("image/png"));
        };
        img.onerror = function () {
            notifikasi("error", "Gambar", "Tidak bisa membaca file gambar.");
        };
        img.src = reader.result;
    };
    reader.onerror = function () {
        notifikasi("error", "Gambar", "Gagal membaca file.");
    };
    reader.readAsDataURL(file);
}

$(document).on("change", 'input[name="staff_esign_mode"]', function () {
    staffEsignShowMode(staffEsignMode());
});

$(document).on("change", "#staff_esign_upload_file", function () {
    var file = this.files && this.files[0] ? this.files[0] : null;
    staffEsignUploadUri = null;
    $("#staff_esign_upload_preview_wrap").hide();
    if (!file) return;
    staffEsignNormalizeToDataUri(file, function (uri) {
        staffEsignUploadUri = uri;
        $("#staff_esign_upload_preview").attr("src", uri);
        $("#staff_esign_upload_preview_wrap").show();
    });
});

$(document).on("click", "#btn_staff_esign_open", function () {
    if (typeof EsignPad === "undefined") {
        notifikasi("error", "E-sign", "Komponen tanda tangan belum termuat.");
        return;
    }
    staffEsignUploadUri = null;
    $("#staff_esign_upload_file").val("");
    $("#staff_esign_upload_preview_wrap").hide();
    $("#staff_esign_mode_draw").prop("checked", true);
    staffEsignShowMode("draw");
    staffEsignModalPad = EsignPad.mount("#staff_esign_modal_pad", {
        large: true,
        value: null,
        hint: "Gambar tanda tangan baru (rasio 16:9). Atau ganti mode Upload gambar.",
    });
    $("#modalStaffEsign").modal("show");
});

$(document).on("click", "#btn_staff_esign_apply", function () {
    var mode = staffEsignMode();
    if (mode === "upload") {
        if (!staffEsignUploadUri) {
            notifikasi("error", "Kosong", "Pilih gambar tanda tangan dulu sebelum simpan.");
            return;
        }
        staffEsignPending = staffEsignUploadUri;
        setStaffEsignPreview(staffEsignPending);
        $("#modalStaffEsign").modal("hide");
        return;
    }
    var pad = staffEsignModalPad || EsignPad.get("#staff_esign_modal_pad");
    if (!pad || pad.isEmpty()) {
        notifikasi("error", "Kosong", "Gambar tanda tangan dulu sebelum simpan.");
        return;
    }
    staffEsignPending = pad.getValue();
    setStaffEsignPreview(staffEsignPending);
    $("#modalStaffEsign").modal("hide");
});

$(document).on("click", "#btn_select_all_warehouses", function () {
    let state = $(this).attr("data-state");
    if (state !== "clear") {
        $(".chk-warehouse").prop("checked", true);
        $(this).attr("data-state", "clear");
        $(this)
            .html('<i class="fa fa-times"></i> Hapus Semua')
            .removeClass("text-primary")
            .addClass("text-danger");
        $(".warehouse-list-container").removeClass("border-danger");
    } else {
        var $kepala = $(".chk-warehouse[data-kepala='1']");
        $(".chk-warehouse").not($kepala).prop("checked", false);
        $kepala.prop("checked", true);
        if ($kepala.length) {
            notifikasi(
                "error",
                "Tidak bisa dinonaktifkan",
                "Gudang Kepala Operasional tidak bisa dilepas dari staf ini.",
            );
        }
        $(this).attr("data-state", "all");
        $(this)
            .html('<i class="fa fa-check-square"></i> Pilih Semua')
            .removeClass("text-danger")
            .addClass("text-primary");
    }
});

$(document).on("click", ".btn-save", function () {
    LoadingButton(this);
    $(".is-invalid").removeClass("is-invalid");
    $(".is-invalids").removeClass("is-invalids");
    var url = "/insertStaff";

    // check image
    // if (mode==2)$('#staff_image').removeClass('fill');
    // else if (mode==1) $('#staff_image').addClass('fill');

    var valid = 1;
    var kepalaBlocked = false;
    $(".fill").each(function () {
        if (
            $(this).val() == null ||
            $(this).val() == "null" ||
            $(this).val() == ""
        ) {
            console.log($(this));
            valid = -1;
            $(this).addClass("is-invalid");
        }
    });
    if (
        $("#staff_position").val() == null ||
        $("#staff_position").val() == "null" ||
        $("#staff_position").val() == ""
    ) {
        valid = -1;
        $("#row-position .select2-selection--single").addClass("is-invalids");
    }

    var staff_warehouses = [];
    $(".chk-warehouse:checked").each(function () {
        staff_warehouses.push($(this).val());
    });
    if (!staff_warehouses || !staff_warehouses.length) {
        $(".warehouse-list-container").addClass("border-danger");
        valid = -1;
    } else if (mode == 2 || mode === "2") {
        var missingKepala = kepalaWarehouseIds().some(function (id) {
            return staff_warehouses.map(Number).indexOf(id) === -1;
        });
        if (missingKepala) {
            kepalaWarehouseIds().forEach(function (id) {
                $("#wh_" + id).prop("checked", true);
            });
            $(".warehouse-list-container").addClass("border-danger");
            valid = -1;
            kepalaBlocked = true;
            notifikasi(
                "error",
                "Tidak bisa dinonaktifkan",
                "Staf ini Kepala Operasional gudang tersebut.",
            );
        }
    }

    let pass = $("#staff_password").val();
    let conf = $("#staff_confirm").val();

    if (mode == 1 || (mode == 2 && (pass !== "" || conf !== ""))) {
        if (pass === "") {
            valid = -1;
            $("#staff_password").addClass("is-invalid");
        }
        if (conf === "") {
            valid = -1;
            $("#staff_confirm").addClass("is-invalid");
        }
        if (pass !== "" && conf !== "" && pass !== conf) {
            valid = -1;
            $("#staff_password").addClass("is-invalid");
            $("#staff_confirm").addClass("is-invalid");
        }
    }

    if (valid == -1) {
        if (!kepalaBlocked) {
            notifikasi(
                "error",
                "Gagal Insert",
                "Silahkan cek kembali inputan anda",
            );
        }
        ResetLoadingButton(
            ".btn-save",
            mode == 1 ? "Tambah Staff" : "Update Staff",
        );
        return false;
    }

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
        staff_warehouses: JSON.stringify(staff_warehouses),
        _token: token,
    };

    if (mode == 2) {
        url = "/updateStaff";
        param.staff_id = data.staff_id;
    }

    const fd = new FormData();
    for (const [key, value] of Object.entries(param)) {
        fd.append(key, value);
    }
    // fd.append('image', $('#staff_image')[0].files[0]);
    // E-sign baru dari modal mengganti yang lama (tanpa checkbox hapus)
    if (staffEsignPending) {
        fd.append("remove_signature", "0");
        fd.append("signature_data_uri", staffEsignPending);
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
            // Re-enable button
            ResetLoadingButton(
                ".btn-save",
                mode == 1 ? "Tambah Staff" : "Update Staff",
            );

            if (response == -1) {
                if (mode == 2)
                    notifikasi(
                        "error",
                        "Gagal Update",
                        "Mohon cek kembali password",
                    );
                $("#staff_password").addClass("is-invalid");
                $("#staff_confirm").addClass("is-invalid");
            } else if (response && response.status == -1) {
                notifikasi(
                    "error",
                    mode == 2 ? "Gagal Update" : "Gagal Insert",
                    response.message || "Silahkan cek kembali inputan anda",
                );
            } else {
                if (mode == 1)
                    notifikasi(
                        "success",
                        "Berhasil Insert",
                        "Berhasil Tambah Staff",
                    );
                else if (mode == 2)
                    notifikasi(
                        "success",
                        "Berhasil Update",
                        "Berhasil Update Staff",
                    );
                afterInsert();
            }
        },
        error: function (xhr) {
            // Re-enable button
            ResetLoadingButton(
                ".btn-save",
                mode == 1 ? "Tambah Staff" : "Update Staff",
            );
            if (handlePermissionError(xhr)) return;
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
    console.log($("#staff_image")[0].files[0]);
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

function kepalaWarehouseIds() {
    var raw =
        data && data.kepala_warehouse_ids ? data.kepala_warehouse_ids : [];
    if (typeof raw === "string") {
        try {
            raw = JSON.parse(raw);
        } catch (e) {
            raw = [];
        }
    }
    if (!Array.isArray(raw)) {
        return [];
    }
    return raw.map(Number).filter(function (id) {
        return id > 0;
    });
}

function isKepalaWarehouse(warehouseId) {
    return kepalaWarehouseIds().indexOf(Number(warehouseId)) !== -1;
}

function afterInsert() {
    window.location.href = "/staff";
}

$(document).on("click", ".btn-back", function () {
    history.go(-1);
});
