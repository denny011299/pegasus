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
var staffEsignRemoved = false;
var staffEsignModalPad = null;
var staffEsignUploadUri = null;
var staffEsignUploadFileName = null;
var staffEsignUploadFileSize = null;
var currentEsignMode = "draw";

function setStaffEsignPreview(uri) {
    if (uri) {
        $("#staff_esign_preview").attr("src", uri).show();
        $("#staff_esign_placeholder").hide();
        $("#btn_staff_esign_open").addClass("has-image");
        $("#btn_staff_esign_label").text("Ubah Tanda Tangan");
        $("#btn_staff_esign_remove").show();
    } else {
        $("#staff_esign_preview").removeAttr("src").hide();
        $("#staff_esign_placeholder").show();
        $("#btn_staff_esign_open").removeClass("has-image");
        $("#btn_staff_esign_label").text("Tambah Tanda Tangan");
        $("#btn_staff_esign_remove").hide();
    }
}

function staffEsignShowMode(mode) {
    currentEsignMode = mode;
    $(".seg-btn").removeClass("active");
    $('.seg-btn[data-mode="' + mode + '"]').addClass("active");
    var draw = mode === "draw";
    $("#staff_esign_panel_draw").toggle(draw);
    $("#staff_esign_panel_upload").toggle(!draw);
}

function staffEsignFormatBytes(bytes) {
    if (!bytes || bytes === 0) return "0 B";
    var k = 1024;
    var sizes = ["B", "KB", "MB"];
    var i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + " " + sizes[i];
}

function staffEsignNormalizeToDataUri(file, done) {
    if (!file || !file.type || file.type.indexOf("image/") !== 0) {
        notifikasi("error", "Format Tidak Valid", "Pilih file gambar berformat PNG, JPG, JPEG, atau WebP.");
        return;
    }
    if (file.size > 2 * 1024 * 1024) {
        notifikasi("error", "Ukuran Terlalu Besar", "Ukuran file gambar maksimal adalah 2 MB.");
        return;
    }
    var reader = new FileReader();
    reader.onload = function () {
        var img = new Image();
        img.onload = function () {
            var w = 560;
            var h = Math.round(w * (9 / 16)); // sama rasio canvas modal 16:9
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
            notifikasi("error", "Gagal Memuat", "Tidak dapat membaca file gambar.");
        };
        img.src = reader.result;
    };
    reader.onerror = function () {
        notifikasi("error", "Gagal Membaca", "Terjadi kesalahan saat membaca file.");
    };
    reader.readAsDataURL(file);
}

function staffEsignProcessFile(file) {
    if (!file) return;
    staffEsignNormalizeToDataUri(file, function (uri) {
        staffEsignUploadUri = uri;
        staffEsignUploadFileName = file.name || "tanda-tangan.png";
        staffEsignUploadFileSize = staffEsignFormatBytes(file.size);

        $("#staff_esign_upload_preview").attr("src", uri);
        $("#staff_esign_file_name").text(staffEsignUploadFileName);
        $("#staff_esign_file_size").text("(" + staffEsignUploadFileSize + ")");
        $("#staff_esign_dropzone").hide();
        $("#staff_esign_upload_preview_card").fadeIn(200);
    });
}

function staffEsignClearUpload() {
    staffEsignUploadUri = null;
    staffEsignUploadFileName = null;
    staffEsignUploadFileSize = null;
    $("#staff_esign_upload_file").val("");
    $("#staff_esign_upload_preview").removeAttr("src");
    $("#staff_esign_upload_preview_card").hide();
    $("#staff_esign_dropzone").show();
}

$(document).on("click", ".seg-btn", function () {
    staffEsignShowMode($(this).data("mode"));
});

function staffEsignOpenFilePicker() {
    var input = document.getElementById("staff_esign_upload_file");
    if (!input) return;
    // Reset supaya file yang sama bisa dipilih ulang
    try {
        input.value = "";
    } catch (e) { /* ignore */ }
    if (typeof input.showPicker === "function") {
        try {
            input.showPicker();
            return;
        } catch (e2) { /* fallback click */ }
    }
    input.click();
}

$(document).on("click", "#staff_esign_dropzone", function (e) {
    // Label[for] sudah buka picker — jangan double-trigger
    if ($(e.target).closest("label[for='staff_esign_upload_file']").length) return;
    e.preventDefault();
    staffEsignOpenFilePicker();
});

$(document).on("keydown", "#staff_esign_dropzone", function (e) {
    if (e.key === "Enter" || e.key === " ") {
        e.preventDefault();
        staffEsignOpenFilePicker();
    }
});

$(document).on("change", "#staff_esign_upload_file", function () {
    var file = this.files && this.files[0] ? this.files[0] : null;
    if (file) staffEsignProcessFile(file);
});

$(document).on("click", "#btn_staff_esign_change_file", function (e) {
    e.preventDefault();
    e.stopPropagation();
    staffEsignOpenFilePicker();
});

$(document).on("click", "#btn_staff_esign_clear_file", function (e) {
    e.stopPropagation();
    staffEsignClearUpload();
});

$(document).on("dragenter dragover", "#staff_esign_dropzone", function (e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).addClass("is-dragover");
});

$(document).on("dragleave dragend", "#staff_esign_dropzone", function (e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).removeClass("is-dragover");
});

$(document).on("drop", "#staff_esign_dropzone", function (e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).removeClass("is-dragover");
    var dt = e.originalEvent && e.originalEvent.dataTransfer ? e.originalEvent.dataTransfer : null;
    var file = dt && dt.files && dt.files[0] ? dt.files[0] : null;
    if (file) staffEsignProcessFile(file);
});

$(document).on("paste", function (e) {
    if (!$("#modalStaffEsign").hasClass("show")) return;
    var clipData = e.clipboardData || (e.originalEvent && e.originalEvent.clipboardData);
    if (!clipData || !clipData.items) return;
    for (var i = 0; i < clipData.items.length; i++) {
        if (clipData.items[i].type && clipData.items[i].type.indexOf("image") !== -1) {
            var file = clipData.items[i].getAsFile();
            if (file) {
                staffEsignShowMode("upload");
                staffEsignProcessFile(file);
                break;
            }
        }
    }
});

$(document).on("click", "#btn_staff_esign_open", function () {
    if (typeof EsignPad === "undefined") {
        notifikasi("error", "E-sign", "Komponen tanda tangan belum termuat.");
        return;
    }
    staffEsignClearUpload();
    staffEsignShowMode("draw");
    $("#modalStaffEsign").modal("show");
    // Mount setelah modal tampil supaya lebar canvas = lebar body modal
    $("#modalStaffEsign")
        .off("shown.bs.modal.staffEsign")
        .one("shown.bs.modal.staffEsign", function () {
            var padW = Math.round(
                $("#staff_esign_modal_pad").innerWidth() ||
                    $("#modalStaffEsign .modal-body").innerWidth() ||
                    720
            );
            staffEsignModalPad = EsignPad.mount("#staff_esign_modal_pad", {
                large: true,
                aspectRatio: 16 / 9,
                size: Math.max(480, Math.min(960, padW)),
                value: staffEsignPending || staffEsignSaved || null,
                hint: "Goreskan tanda tangan di area canvas. Anda juga dapat beralih ke tab Upload File.",
            });
        });
});

$(document).on("click", "#btn_staff_esign_remove", function () {
    if (typeof Swal !== "undefined") {
        Swal.fire({
            title: "Hapus Tanda Tangan?",
            text: "Tanda tangan digital staf ini akan dihapus saat data disimpan.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#ef4444",
            cancelButtonColor: "#64748b",
            confirmButtonText: "Ya, Hapus",
            cancelButtonText: "Batal",
        }).then(function (res) {
            if (res.isConfirmed) {
                staffEsignPending = null;
                staffEsignRemoved = true;
                setStaffEsignPreview(null);
                notifikasi("info", "E-sign", "Tanda tangan dihapus. Klik tombol Simpan/Update Staf untuk memperbarui data.");
            }
        });
    } else {
        if (confirm("Hapus tanda tangan digital staf ini?")) {
            staffEsignPending = null;
            staffEsignRemoved = true;
            setStaffEsignPreview(null);
        }
    }
});

$(document).on("click", "#btn_staff_esign_apply", function () {
    var mode = currentEsignMode;
    if (mode === "upload") {
        if (!staffEsignUploadUri) {
            notifikasi("error", "Gambar Belum Dipilih", "Silakan upload file gambar tanda tangan terlebih dahulu.");
            return;
        }
        staffEsignPending = staffEsignUploadUri;
        staffEsignRemoved = false;
        setStaffEsignPreview(staffEsignPending);
        $("#modalStaffEsign").modal("hide");
        notifikasi("success", "Tanda Tangan Siap", "File gambar berhasil dimuat. Klik Simpan/Update Staf untuk menyimpan.");
        return;
    }
    var pad = staffEsignModalPad || EsignPad.get("#staff_esign_modal_pad");
    if (!pad || pad.isEmpty()) {
        notifikasi("error", "Canvas Kosong", "Goreskan tanda tangan pada canvas terlebih dahulu sebelum simpan.");
        return;
    }
    staffEsignPending = pad.getValue();
    staffEsignRemoved = false;
    setStaffEsignPreview(staffEsignPending);
    $("#modalStaffEsign").modal("hide");
    notifikasi("success", "Tanda Tangan Siap", "Goresan tanda tangan berhasil dicatat. Klik Simpan/Update Staf untuk menyimpan.");
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
    } else if (staffEsignRemoved) {
        fd.append("remove_signature", "1");
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
