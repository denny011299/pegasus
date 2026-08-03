var supplies = [];
var suppliesSubmit = [];
var savedValues = {};
var stockOpnameXhr = null;
var stockOpnameReqSeq = 0;
var searchBahanDebounce = null;
var canEditDraft = false;
autocompleteCategory("#kategori", null, 1);

$(document).ready(function () {
    //    if(data.category_id!=null)$('#category_id').append(`<option value="${data.category_id}">${data.category_name}</option>`).trigger("change");
    //    if(mode==2){
    //     $('#staff').val(data.created_by);
    //    }
    loadStaff();
    if (mode == 1) {
        refreshStockOpname();
        var yesterday = moment().format("YYYY-MM-DD");
        // Autofill ke input
        $("#tanggal").val(yesterday);
        $("#status").val("-");

        if (sessionUser) {
            let option = new Option(
                sessionUser.staff_name,
                sessionUser.staff_id,
                true,
                true,
            );
            $("#penanggung-jawab")
                .empty()
                .append(option)
                .attr("disabled", false)
                .trigger("change");
        }
    } else {
        console.log(data);
        $("#tanggal").val(data.stob_date);
        $("#catatan").val(data.stob_notes);
        supplies = data.item;

        // Draft cuma boleh diedit oleh staff pembuatnya (atau super admin) —
        // draft milik orang lain sudah difilter di server dan tidak akan
        // pernah sampai ke sini, jadi cabang ini murni soal "punyaku sendiri
        // atau bukan".
        var isOwner = !!(
            sessionUser && data.created_by == sessionUser.staff_id
        );
        var isSuperAdmin = window.userRoleId === -1;
        canEditDraft = !!data.is_draft && (isOwner || isSuperAdmin);

        let staffOption = new Option(
            data.staff_name,
            data.staff_id,
            true,
            true,
        );
        $("#penanggung-jawab").empty().append(staffOption).trigger("change");

        if (canEditDraft) {
            $("#tanggal,#penanggung-jawab,#catatan").prop("disabled", false);
            $("#status").val("Draft");
            $(".btn-save,.save-tolak,.save-terima").hide();
            $(".btn-save-draft,.btn-ajukan,.btn-delete-draft").show();

            // Isi dulu nilai yang sudah pernah disimpan, baru muat ulang
            // daftar bahan penuh (seperti mode create) supaya draft bisa
            // ditambah/dikurangi itemnya, tanpa kehilangan input lama.
            seedSavedValuesFromItems(data.item);
            refreshStockOpname();
        } else {
            $("#tanggal,#penanggung-jawab,#catatan").prop("disabled", true);
            renderMode2(data.item); // ← pakai renderMode2, bukan forEach langsung
            $("#tbStock input").prop("disabled", true);
            $(".btn-save,.btn-save-draft,.btn-ajukan,.btn-delete-draft").hide();

            if (data.is_draft) {
                // Jaga-jaga saja — seharusnya tidak pernah tercapai karena
                // draft orang lain sudah 404 di server.
                $("#status").val("Draft");
            } else if (data.status == 1) {
                $(".save-tolak,.save-terima").show();
                $("#status").val("Menunggu Approval");
            } else if (data.status == 2) {
                $(".save-tolak,.save-terima").hide();
                $("#status").val("Disetujui");
            } else if (data.status == 3) {
                $(".save-tolak,.save-terima").hide();
                $("#status").val("Ditolak");
            }
        }
    }
});

function seedSavedValuesFromItems(items) {
    (items || []).forEach(function (item) {
        var key = item.supplies_id;
        var realMap = {};
        if (item.stobd_real) {
            item.stobd_real.split(", ").forEach(function (str) {
                var parts = str.trim().split(" ");
                var qty = parseInt(parts[0]);
                var unitName = parts.slice(1).join(" ");
                realMap[unitName] = isNaN(qty) ? -1 : qty;
            });
        }
        var stocks = {};
        (item.units || []).forEach(function (u) {
            var realQty = realMap[u.unit_short_name];
            if (realQty !== undefined && realQty !== -1) {
                stocks[u.unit_id] = realQty;
            }
        });
        savedValues[key] = { notes: item.stobd_notes || "", stocks: stocks };
    });
}

function loadStaff() {
    $("#penanggung-jawab").empty();
    autocompleteStaff("#penanggung-jawab");
}
function refreshStockOpname(callback) {
    // Simpan value yang sudah diinput sebelum refresh
    $(".row-stock").each(function () {
        var suppliesId = $(this).data("supplies-id");
        var key = suppliesId;
        savedValues[key] = {
            notes: $(this).find(".notes").val(),
            stocks: {},
        };
        $(this)
            .find(".real-stock")
            .each(function () {
                var unitId = $(this).data("unit-id");
                savedValues[key].stocks[unitId] = $(this).val();
            });
    });
    // Batalkan request pencarian sebelumnya yang belum selesai, dan tandai
    // request ini dengan nomor urut supaya respons yang datang belakangan
    // (out-of-order) tidak menimpa hasil pencarian yang lebih baru.
    if (stockOpnameXhr) stockOpnameXhr.abort();
    var reqId = ++stockOpnameReqSeq;

    stockOpnameXhr = $.ajax({
        url: "/getSupplies",
        method: "get",
        data: {
            supplies_name: $("#filter_sup_name").val(),
            _token: "{{ csrf_token() }}",
        },
        success: function (e) {
            if (reqId !== stockOpnameReqSeq) return; // respons basi, abaikan
            console.log(e);

            $("#tbStock").html("");
            e.forEach((item, indexProduct) => {
                var st = "";
                // if(mode==2)  {
                //     if(stp_type==1)st =  getData(item.pr_id);
                //     else if(stp_type==2)st =  getData(item.sup_id);
                // }
                var rl_stock = "";
                var system = "";
                var selisihArr = [];

                item.stock.forEach((element, index) => {
                    selisihArr.push("0 " + element.unit_short_name);
                    // Setiap 3 item, buka div baru
                    if (index % 3 === 0) {
                        if (index > 0)
                            rl_stock += `</div><div class="input-group mb-1 rstock">`;
                    }
                    rl_stock += `
                            <input type="text"
                                class="form-control real-stock nominal_only"
                                value=""
                                data-unit-id="${element.unit_id}"
                                data-unit-name="${element.unit_short_name}"
                                data-system-qty="${element.ss_stock}">
                            <span class="input-group-text">${element.unit_short_name}</span>
                        `;
                    system +=
                        element.ss_stock + " " + element.unit_short_name + ", ";
                });

                // Untuk superadmin
                // $('#tbStock').append(`
                //     <tr class="row-stock" data-supplies-id="${item.supplies_id}">
                //         <td>${item.pr_name}</td>
                //         <td class="text-center pt-2 pr_stock">${systemArr.join(', ')}</td>
                //         <td class="text-center" style="width:10%">
                //             <div class="input-group mb-3 rstock">
                //                 ${rl_stock}
                //             </div>
                //             <input type="hidden" class="data">
                //             <input type="hidden" class="stobd_id">
                //         </td>
                //         <td class="text-center pt-2 selisih">${selisihArr.join(', ')}</td>
                // Untuk Non Admin
                $("#tbStock").append(`
                        <tr class="row-stock" data-supplies-id="${item.supplies_id}">
                            <td>${item.supplies_name}</td>
                            <td class="text-center">
                                <div class="input-group mb-3 rstock">
                                    ${rl_stock}
                                </div>
                                <input type="hidden" class="data">
                                <input type="hidden" class="stobd_id">
                            </td>
                            <td class="">
                                <input type="text" class="form-control notes" placeholder="Catatan.." value="${mode == 2 ? (item.stobd_notes ?? "") : ""}">
                                <input type="hidden" class="form-control input-selesih" placeholder="Catatan.."  >
                            </td>
                        </tr>
                    `);
            });
            // Restore value yang sudah diinput
            $(".row-stock").each(function () {
                var suppliesId = $(this).data("supplies-id");
                var key = suppliesId;
                if (savedValues[key]) {
                    $(this).find(".notes").val(savedValues[key].notes);
                    $(this)
                        .find(".real-stock")
                        .each(function () {
                            var unitId = $(this).data("unit-id");
                            var sv = savedValues[key].stocks[unitId];
                            if (sv != undefined && sv !== '') {
                                $(this).val(formatRupiah(String(sv)));
                            }
                        });
                }
            });
            if (e.length == 0) {
                $("#tbStock").html(
                    `<tr><td colspan="3" class="text-center">Bahan tidak ditemukan</td></tr>`,
                );
            }
            if (mode == 2 && !canEditDraft) {
                $(".real-stock, .notes").attr("disabled", "disabled");
            }
            if (typeof callback === "function") callback();
            supplies = e;
        },
        error: function (e) {
            if (reqId !== stockOpnameReqSeq) return; // request lama yang dibatalkan (abort)
            console.log(e);
        },
    });
}

function renderMode2(items) {
    $("#tbStock").html("");
    items.forEach((item) => {
        var rl_stock = "";
        let realMap = {};
        if (item.stobd_real) {
            item.stobd_real.split(", ").forEach((str) => {
                let parts = str.trim().split(" ");
                let qty = parseInt(parts[0]);
                let unitName = parts.slice(1).join(" ");
                realMap[unitName] = isNaN(qty) ? -1 : qty;
            });
        }

        let systemMap = {};
        if (item.stobd_system) {
            item.stobd_system.split(", ").forEach((str) => {
                let parts = str.trim().split(" ");
                let qty = parseInt(parts[0]);
                let unitName = parts.slice(1).join(" ");
                systemMap[unitName] = isNaN(qty) ? 0 : qty;
            });
        }

        let selisihMap = {};
        if (item.stobd_selisih) {
            item.stobd_selisih.split(", ").forEach((str) => {
                let parts = str.trim().split(" ");
                let qty = parseInt(parts[0]);
                let unitName = parts.slice(1).join(" ");
                selisihMap[unitName] = isNaN(qty) ? 0 : qty;
            });
        }

        item.sp_units = [];
        item.units.forEach((unit) => {
            let realQty = realMap[unit.unit_short_name] ?? -1; // -1 = tidak diinput
            let systemQty = systemMap[unit.unit_short_name] ?? 0;
            let selisihQty = selisihMap[unit.unit_short_name] ?? 0;

            item.sp_units.push({
                unit_id: unit.unit_id,
                unit_short_name: unit.unit_short_name,
                system_qty: systemQty,
                real_qty: realQty !== -1 ? realQty : systemQty, // 0 tetap 0, -1 baru fallback ke system
                selisih_qty: selisihQty,
            });
        });

        item.sp_units.forEach((element, index) => {
            if (index % 3 === 0) {
                if (index > 0)
                    rl_stock += `</div><div class="input-group mb-1 rstock">`;
            }
            rl_stock += `
                    <input type="text"
                        class="form-control real-stock nominal_only"
                        value="${formatRupiah(String(element.real_qty))}"
                        data-unit-id="${element.unit_id}"
                        data-unit-name="${element.unit_short_name}"
                        data-system-qty="${element.system_qty}">
                    <span class="input-group-text">${element.unit_short_name}</span>
                `;
        });

        $("#tbStock").append(`
                <tr class="row-stock" data-supplies-id="${item.supplies_id}">
                    <td>${item.supplies_name}</td>
                    <td class="text-center">
                        <div class="input-group mb-3 rstock">
                            ${rl_stock}
                        </div>
                        <input type="hidden" class="data">
                        <input type="hidden" class="stobd_id">
                    </td>
                    <td class="">
                        <input type="text" class="form-control notes" placeholder="Catatan.." value="${item.stobd_notes ?? ""}">
                        <input type="hidden" class="form-control input-selesih" placeholder="Catatan..">
                    </td>
                </tr>
            `);
    });

    if (items.length == 0) {
        $("#tbStock").html(
            `<tr><td colspan="3" class="text-center">Bahan tidak ditemukan</td></tr>`,
        );
    }
}

$(document).on("keyup", "#filter_sup_name", function () {
    if (mode == 1) {
        clearTimeout(searchBahanDebounce);
        searchBahanDebounce = setTimeout(function () {
            refreshStockOpname(); // mode 1 tetap AJAX
        }, 500);
    } else {
        // mode 2 filter dari data.item langsung, TANPA AJAX
        let keyword = $(this).val().toLowerCase();
        let filtered = data.item.filter((item) =>
            (item.supplies_name ?? "").toLowerCase().includes(keyword),
        );
        renderMode2(filtered);
        $("#tbStock input").prop("disabled", true);
    }
});

// $(document).on("click",".real-stock",function(){
//     $(this).focus().select();

// });

// $(document).on('keyup change', '.real-stock', function () {
//     let row = $(this).closest('.row-stock');
//     let selisihArr = [];

//     row.find('.real-stock').each(function () {

//         let realQty   = parseInt($(this).val()) || 0;
//         let systemQty = parseInt($(this).data('system-qty')) || 0;
//         let unitName  = $(this).data('unit-name');

//         selisihArr.push(((realQty != -1 ? realQty : systemQty) - systemQty) + ' ' + unitName);
//     });

//     row.find('.selisih').html(selisihArr.join(', '));
// });

$(document).on("change", "#category_id", function () {
    refreshStockOpname();
});

$(document).on("click", ".btn-save", function () {
    LoadingButton(this);
    clearTimeout(searchBahanDebounce);
    $("#filter_sup_name").val("");
    refreshStockOpname(function () {
        insertData({ btnSelector: ".btn-save", doneText: "Tambah Stok Opname" });
    });
});

$(document).on("click", ".btn-save-draft", function () {
    LoadingButton(this);
    clearTimeout(searchBahanDebounce);
    $("#filter_sup_name").val("");
    refreshStockOpname(function () {
        insertData({
            isDraft: true,
            keepSparse: true,
            btnSelector: ".btn-save-draft",
            doneText: "Simpan sebagai Draft",
            onSuccess: function (e) {
                toastr.success("", "Draft berhasil disimpan");
                ResetLoadingButton(".btn-save-draft", "Simpan sebagai Draft");
                var stobId = mode == 1 ? e && e.stob_id : data.stob_id;
                window.location.href = stobId
                    ? "/detailStockOpnameBahan/" + stobId
                    : "/stockOpnameBahan";
            },
        });
    });
});

$(document).on("click", ".btn-ajukan", function () {
    LoadingButton(this);
    clearTimeout(searchBahanDebounce);
    $("#filter_sup_name").val("");
    refreshStockOpname(function () {
        insertData({
            isDraft: true,
            btnSelector: ".btn-ajukan",
            doneText: "Ajukan",
            onSuccess: function () {
                $.ajax({
                    url: "/submitStockOpnameBahan",
                    data: { stob_id: data.stob_id, _token: token },
                    method: "post",
                    success: function (e) {
                        if (e && e.status == -1) {
                            notifikasi(
                                "error",
                                "Gagal Mengajukan",
                                e.message || "Terjadi kesalahan",
                            );
                            ResetLoadingButton(".btn-ajukan", "Ajukan");
                            return;
                        }
                        toastr.success("", "Berhasil mengajukan Stock Opname");
                        window.location.href = "/stockOpnameBahan";
                    },
                    error: function (e) {
                        console.log(e);
                        ResetLoadingButton(".btn-ajukan", "Ajukan");
                    },
                });
            },
        });
    });
});

$(document).on("click", ".btn-delete-draft", function () {
    showModalDelete(
        "Apakah yakin ingin menghapus draft ini?",
        "btn-delete-draft-confirm",
    );
    $("#btn-delete-draft-confirm").html("Delete");
});

$(document).on("click", "#btn-delete-draft-confirm", function () {
    LoadingButton(this);
    $.ajax({
        url: "/deleteStockOpnameBahan",
        data: { stob_id: data.stob_id, _token: token },
        method: "post",
        success: function (e) {
            if (e && e.status == -1) {
                $(".modal").modal("hide");
                notifikasi("error", "Gagal Hapus", e.message || "Terjadi kesalahan");
                ResetLoadingButton(".btn-konfirmasi", "Delete");
                return;
            }
            $("#modalDelete .modal-body").html("");
            $(".modal").modal("hide");
            notifikasi("success", "Berhasil Hapus", "Draft berhasil dihapus");
            window.location.href = "/stockOpnameBahan";
        },
        error: function (e) {
            console.log(e);
            ResetLoadingButton(".btn-konfirmasi", "Delete");
        },
    });
});

function getData(id) {
    var ada = -1;
    console.log("Dari get Data");
    console.log(data);
    console.log(id);

    data.items.forEach((element, index) => {
        if (element.pr_id && element.pr_id == id) ada = index;
        else if (element.sup_id && element.sup_id == id) ada = index;
    });
    return data.items[ada];
}

function insertData(options) {
    options = options || {};
    var isDraft = !!options.isDraft;
    var btnSelector = options.btnSelector || ".btn-save";
    var doneText = options.doneText || "Tambah Stok Opname";
    var onSuccess = options.onSuccess;
    // Draft: hanya simpan bahan yang benar-benar diisi user. Kalau tidak,
    // seluruh katalog bahan (yang cuma tampil karena pencarian kosong) ikut
    // tersimpan dengan real_qty otomatis = stok sistem, dan saat draft dibuka
    // lagi user akan melihat angka yang tidak pernah mereka masukkan sendiri.
    var keepSparse = !!options.keepSparse;

    $(".is-invalid").removeClass("is-invalid");
    $(".invalid").removeClass("invalid");
    var url = "/insertStockOpnameBahan";
    var valid = 1;

    $(".fill").each(function () {
        if (
            $(this).val() == null ||
            $(this).val() == "null" ||
            $(this).val() == ""
        ) {
            valid = -1;
            $(this).addClass("is-invalid");
        }
    });

    if (
        $("#penanggung-jawab").val() == null ||
        $("#penanggung-jawab").val() == ""
    ) {
        $(".row-staff .select2-selection--single").addClass("invalid");
        valid = -1;
    }

    if (valid == -1) {
        notifikasi(
            "error",
            "Gagal Insert",
            "Silahkan cek kembali inputan anda",
        );
        ResetLoadingButton(btnSelector, doneText);
        return false;
    }

    suppliesSubmit = [];
    $(".row-stock").each(function () {
        let row = $(this);
        let item = {};

        item.supplies_id = row.data("supplies-id");
        item.stobd_notes = row.find(".notes").val() ?? "";

        let sp_units = [];
        let systemArr = [];
        let realArr = [];
        let selisihArr = [];
        let hasValue = false;

        row.find(".real-stock").each(function () {
            let input = $(this);

            let unitId = input.data("unit-id");
            let unitName = input.data("unit-name");
            let systemQty = parseInt(input.data("system-qty")) || 0;
            let val = input.val();
            if (val !== "" && val !== null && val !== undefined) {
                hasValue = true;
            }
            let realQty =
                val === "" || val === null || val === undefined
                    ? -1
                    : (convertToAngka(String(val)) || 0);

            sp_units.push({
                unit_id: unitId,
                system_qty: systemQty,
                real_qty: realQty != -1 ? realQty : systemQty,
            });

            systemArr.push(systemQty + " " + unitName);
            realArr.push(
                (realQty != -1 ? realQty : systemQty) + " " + unitName,
            );
            selisihArr.push(
                (realQty != -1 ? realQty : systemQty) -
                    systemQty +
                    " " +
                    unitName,
            );
        });

        // Draft: bahan yang belum disentuh sama sekali tidak ikut disimpan.
        if (keepSparse && !hasValue) {
            return;
        }

        item.sp_units = sp_units;
        item.stobd_system = systemArr.join(", ");
        item.stobd_real = realArr.join(", ");
        item.stobd_selisih = selisihArr.join(", ");

        suppliesSubmit.push(item);
    });
    console.log(suppliesSubmit);

    param = {
        stob_date: $("#tanggal").val(),
        staff_id: $("#penanggung-jawab").val(),
        // category_id: -1,
        stob_notes: $("#catatan").val(),
        item: JSON.stringify(suppliesSubmit),
        is_draft: isDraft ? 1 : 0,
        _token: token,
    };
    if (mode == 2) {
        url = "/updateStockOpnameBahan";
        param.stob_id = data.stob_id;
    }

    $.ajax({
        url: url,
        data: param,
        method: "post",
        headers: {
            "X-CSRF-TOKEN": token,
        },
        success: function (e) {
            if (e && e.status == -1) {
                notifikasi(
                    "error",
                    "Gagal Simpan",
                    e.message || "Terjadi kesalahan",
                );
                ResetLoadingButton(btnSelector, doneText);
                return;
            }
            if (typeof onSuccess === "function") {
                onSuccess(e);
                return;
            }
            toastr.success("", "Berhasil Tambah Stock Opname");
            ResetLoadingButton(btnSelector, doneText);
            window.location.href = "/stockOpnameBahan";
        },
        error: function (e) {
            toastr.success("", "Terjadi Kesalahan Saat Tambah Stok Opname");
            ResetLoadingButton(btnSelector, doneText);
            console.log(e);
        },
    });
}

$(document).on("click", ".btnBack", function () {
    window.open("/stockOpnameBahan", "_self");
});

//konfirmasi acc
$(document).on("click", ".save-terima", function () {
    var tbId = $(this).closest("table").attr("id");
    var data = $("#" + tbId)
        .DataTable()
        .row($(this).parents("tr"))
        .data(); //ambil data dari table
    showModalKonfirmasi(
        "Apakah yakin ingin Approve stock opname ini?",
        "btn-acc-stob",
    );
    $("#btn-acc-stob").html("Konfirmasi");
});

$(document).on("click", "#btn-acc-stob", function () {
    LoadingButton(this);

    $("#filter_sup_name").val("");
    renderMode2(data.item);

    suppliesSubmit = [];
    $(".row-stock").each(function () {
        let row = $(this);
        let item = {};

        item.supplies_id = row.data("supplies-id");
        item.stobd_notes = row.find(".notes").val() ?? "";

        let sp_units = [];
        let systemArr = [];
        let realArr = [];
        let selisihArr = [];

        row.find(".real-stock").each(function () {
            let input = $(this);

            let unitId = input.data("unit-id");
            let unitName = input.data("unit-name");
            let systemQty = parseInt(input.data("system-qty")) || 0;
            let val = input.val();
            let realQty =
                val === "" || val === null || val === undefined
                    ? -1
                    : (convertToAngka(String(val)) || 0);

            sp_units.push({
                unit_id: unitId,
                system_qty: systemQty,
                real_qty: realQty != -1 ? realQty : systemQty,
            });

            systemArr.push(systemQty + " " + unitName);
            realArr.push(
                (realQty != -1 ? realQty : systemQty) + " " + unitName,
            );
            selisihArr.push(
                (realQty != -1 ? realQty : systemQty) -
                    systemQty +
                    " " +
                    unitName,
            );
        });

        item.sp_units = sp_units;
        item.stobd_system = systemArr.join(", ");
        item.stobd_real = realArr.join(", ");
        item.stobd_selisih = selisihArr.join(", ");

        suppliesSubmit.push(item);
    });

    $.ajax({
        url: "/accStockOpnameBahan",
        data: {
            stob_id: data.stob_id,
            item: JSON.stringify(suppliesSubmit),
            _token: token,
        },
        method: "post",
        success: function (e) {
            console.log("masuk");
            $("#modalDelete .modal-body").html("");
            ResetLoadingButton(".btn-konfirmasi", "Konfirmasi");
            $(".modal").modal("hide");
            notifikasi(
                "success",
                "Berhasil Approve",
                "Berhasil approve stock opname",
            );
            window.open("/stockOpnameBahan", "_self");
        },
        error: function (e) {
            console.log(e);
            ResetLoadingButton(".btn-konfirmasi", "Konfirmasi");
        },
    });
});

$(document).on("click", ".save-tolak", function () {
    showModalDelete(
        "Apakah yakin ingin menolak stock opname ini?",
        "btn-tolak-stob",
    );
    $("#btn-tolak-stob").html("Delete");
});

$(document).on("click", "#btn-tolak-stob", function () {
    LoadingButton(this);
    $.ajax({
        url: "/tolakStockOpnameBahan",
        data: {
            stob_id: data.stob_id,
            _token: token,
        },
        method: "post",
        success: function (e) {
            $("#modalDelete .modal-body").html("");
            ResetLoadingButton(".btn-konfirmasi", "Delete");
            $(".modal").modal("hide");
            notifikasi(
                "success",
                "Berhasil Tolak",
                "Berhasil tolak Stock Opname",
            );
            window.open("/stockOpnameBahan", "_self");
        },
        error: function (e) {
            ResetLoadingButton(".btn-konfirmasi", "Delete");
            console.log(e);
        },
    });
});
