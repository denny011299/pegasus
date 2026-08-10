var product = [];
var productSubmit = [];
var savedValues = {};
var stockOpnameXhr = null;
var stockOpnameReqSeq = 0;
var searchProdukDebounce = null;
var canEditDraft = false;
autocompleteCategory("#kategori", null, 1);

$(document).ready(function () {
    loadStaff();
    if (mode == 1) {
        refreshStockOpname();
        var yesterday = moment().format("YYYY-MM-DD");
        console.log(yesterday);
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
        $("#tanggal").val(data.sto_date);
        $("#catatan").val(data.sto_notes);
        product = data.item;

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
            // daftar produk penuh (seperti mode create) supaya draft bisa
            // ditambah/dikurangi itemnya, tanpa kehilangan input lama.
            seedSavedValuesFromItems(data.item);
            refreshStockOpname();
        } else {
            $("#tanggal,#penanggung-jawab,#catatan").prop("disabled", true);
            renderMode2(data.item);
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
        var key = item.product_id + "_" + item.product_variant_id;
        var stocks = {};
        (item.units || []).forEach(function (u) {
            stocks[u.unit_id] = u.real_qty;
        });
        savedValues[key] = { notes: item.stod_notes || "", stocks: stocks };
    });
}

function loadStaff() {
    $("#penanggung-jawab").empty();
    autocompleteStaff("#penanggung-jawab");
}
function refreshStockOpname(callback) {
    // Simpan value yang sudah diinput sebelum refresh
    $(".row-stock").each(function () {
        var productId = $(this).data("product-id");
        var variantId = $(this).data("variant-id");
        var key = productId + "_" + variantId;
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
    console.log(savedValues);

    // Batalkan request pencarian sebelumnya yang belum selesai, dan tandai
    // request ini dengan nomor urut supaya respons yang datang belakangan
    // (out-of-order) tidak menimpa hasil pencarian yang lebih baru.
    if (stockOpnameXhr) stockOpnameXhr.abort();
    var reqId = ++stockOpnameReqSeq;

    stockOpnameXhr = $.ajax({
        url: "/getProductVariant",
        method: "get",
        data: {
            search_product: $("#filter_pr_name").val(),
            _token: "{{ csrf_token() }}",
        },
        success: function (e) {
            if (reqId !== stockOpnameReqSeq) return; // respons basi, abaikan
            product = e;
            console.log(e);

            $("#tbStock").html("");
            e.forEach((item, indexProduct) => {
                var st = "";
                var rl_stock = "";
                var system = "";
                var selisihArr = [];

                item.stock.forEach((element, index) => {
                    selisihArr.push("0 " + element.unit_short_name);
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
                                data-system-qty="${element.ps_stock}">
                            <span class="input-group-text">${element.unit_short_name}</span>
                        `;
                    system +=
                        element.ps_stock + " " + element.unit_short_name + ", ";
                });

                $("#tbStock").append(`
                        <tr class="row-stock" data-product-id="${item.product_id}" data-variant-id="${item.product_variant_id}">
                            <td>${item.product_variant_sku}</td>
                            <td>${item.pr_name} ${item.product_variant_name}</td>
                            <td class="text-center">
                                <div class="input-group mb-3 rstock">
                                    ${rl_stock}
                                </div>
                                <input type="hidden" class="data">
                                <input type="hidden" class="stod_id">
                            </td>
                            <td class="">
                                <input type="text" class="form-control notes" placeholder="Catatan.." value="${mode == 2 ? (item.stod_notes ?? "") : ""}">
                                <input type="hidden" class="form-control input-selesih" placeholder="Catatan..">
                            </td>
                        </tr>
                    `);
            });
            // Restore value yang sudah diinput
            $(".row-stock").each(function () {
                var productId = $(this).data("product-id");
                var variantId = $(this).data("variant-id");
                var key = productId + "_" + variantId;
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
                    `<tr><td colspan="6" class="text-center">Produk tidak ditemukan</td></tr>`,
                );
            }
            if (mode == 2 && !canEditDraft) {
                $(".real-stock, .notes").attr("disabled", "disabled");
            }

            if (typeof callback === "function") callback();
        },
        error: function (e) {
            if (handlePermissionError(e)) return;
            if (reqId !== stockOpnameReqSeq) return;
            console.log(e);
        },
    });
}

function renderMode2(items) {
    $("#tbStock").html("");
    items.forEach((item) => {
        console.log(item);
        var rl_stock = "";

        item.units.forEach((element, index) => {
            if (index % 4 === 0) {
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
                <tr class="row-stock" data-product-id="${item.product_id}" data-variant-id="${item.product_variant_id}">
                    <td>${item.product_variant_sku}</td>
                    <td>${item.pr_name} ${item.product_variant_name}</td>
                    <td class="text-center">
                        <div class="input-group mb-3 rstock">
                            ${rl_stock}
                        </div>
                        <input type="hidden" class="data">
                        <input type="hidden" class="stod_id">
                    </td>
                    <td class="">
                        <input type="text" class="form-control notes" placeholder="Catatan.." value="${item.stod_notes ?? ""}">
                        <input type="hidden" class="form-control input-selesih" placeholder="Catatan..">
                    </td>
                </tr>
            `);
    });

    if (items.length == 0) {
        $("#tbStock").html(
            `<tr><td colspan="6" class="text-center">Produk tidak ditemukan</td></tr>`,
        );
    }
}

$(document).on("keyup", "#filter_pr_name", function () {
    if (mode == 1) {
        clearTimeout(searchProdukDebounce);
        searchProdukDebounce = setTimeout(function () {
            refreshStockOpname();
        }, 500);
    } else {
        let keyword = $(this).val().toLowerCase();
        let filtered = data.item.filter((item) =>
            (
                (item.pr_name ?? "") +
                " " +
                (item.product_variant_name ?? "") +
                " " +
                (item.product_variant_sku ?? "")
            )
                .toLowerCase()
                .includes(keyword),
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
    clearTimeout(searchProdukDebounce);
    $("#filter_pr_name").val("");
    refreshStockOpname(function () {
        insertData({ btnSelector: ".btn-save", doneText: "Tambah Stok Opname" });
    });
});

$(document).on("click", ".btn-save-draft", function () {
    LoadingButton(this);
    clearTimeout(searchProdukDebounce);
    $("#filter_pr_name").val("");
    refreshStockOpname(function () {
        insertData({
            isDraft: true,
            keepSparse: true,
            btnSelector: ".btn-save-draft",
            doneText: "Simpan sebagai Draft",
            onSuccess: function (e) {
                toastr.success("", "Draft berhasil disimpan");
                ResetLoadingButton(".btn-save-draft", "Simpan sebagai Draft");
                var stoId = mode == 1 ? e && e.sto_id : data.sto_id;
                window.location.href = stoId
                    ? "/detailStockOpname/" + stoId
                    : "/stockOpname";
            },
        });
    });
});

$(document).on("click", ".btn-ajukan", function () {
    LoadingButton(this);
    clearTimeout(searchProdukDebounce);
    $("#filter_pr_name").val("");
    refreshStockOpname(function () {
        insertData({
            isDraft: true,
            btnSelector: ".btn-ajukan",
            doneText: "Ajukan",
            onSuccess: function () {
                $.ajax({
                    url: "/submitStockOpname",
                    data: { sto_id: data.sto_id, _token: token },
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
                        window.location.href = "/stockOpname";
                    },
                    error: function (e) {
                        ResetLoadingButton(".btn-ajukan", "Ajukan");
                        if (handlePermissionError(e)) return;
                        console.log(e);
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
        url: "/deleteStockOpname",
        data: { sto_id: data.sto_id, _token: token },
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
            window.location.href = "/stockOpname";
        },
        error: function (e) {
            ResetLoadingButton(".btn-konfirmasi", "Delete");
            if (handlePermissionError(e)) return;
            console.log(e);
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
    // Draft: hanya simpan produk yang benar-benar diisi user. Kalau tidak,
    // seluruh katalog produk (yang cuma tampil karena pencarian kosong) ikut
    // tersimpan dengan real_qty otomatis = stok sistem, dan saat draft dibuka
    // lagi user akan melihat angka yang tidak pernah mereka masukkan sendiri.
    var keepSparse = !!options.keepSparse;

    $(".is-invalid").removeClass("is-invalid");
    $(".invalid").removeClass("invalid");
    var url = "/insertStockOpname";
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

    productSubmit = [];
    $(".row-stock").each(function () {
        let row = $(this);
        let item = {};

        item.product_id = row.data("product-id");
        item.product_variant_id = row.data("variant-id");
        item.stod_notes = row.find(".notes").val() ?? "";

        let units = [];
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

            units.push({
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

        // Draft: produk yang belum disentuh sama sekali tidak ikut disimpan.
        if (keepSparse && !hasValue) {
            return;
        }

        item.units = units;
        item.stod_system = systemArr.join(", ");
        item.stod_real = realArr.join(", ");
        item.stod_selisih = selisihArr.join(", ");

        productSubmit.push(item);
    });
    console.log(productSubmit);

    param = {
        sto_date: $("#tanggal").val(),
        staff_id: $("#penanggung-jawab").val(),
        category_id: -1,
        sto_notes: $("#catatan").val(),
        item: JSON.stringify(productSubmit),
        is_draft: isDraft ? 1 : 0,
        _token: token,
    };
    if (mode == 2) {
        url = "/updateStockOpname";
        param.sto_id = data.sto_id;
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
            window.location.href = "/stockOpname";
        },
        error: function (e) {
            ResetLoadingButton(btnSelector, doneText);
            if (handlePermissionError(e)) return;
            toastr.success("", "Terjadi Kesalahan Saat Tambah Stok Opname");
            console.log(e);
        },
    });
}

$(document).on("click", ".btnBack", function () {
    window.open("/stockOpname", "_self");
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
        "btn-acc-sto",
    );
    $("#btn-acc-sto").html("Konfirmasi");
});

$(document).on("click", "#btn-acc-sto", function () {
    LoadingButton(this);

    $("#filter_pr_name").val("");
    renderMode2(data.item);

    productSubmit = [];
    $(".row-stock").each(function () {
        let row = $(this);
        let item = {};

        item.product_id = row.data("product-id");
        item.product_variant_id = row.data("variant-id");
        item.stod_notes = row.find(".notes").val() ?? "";

        let units = [];
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

            units.push({
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

        item.units = units;
        item.stod_system = systemArr.join(", ");
        item.stod_real = realArr.join(", ");
        item.stod_selisih = selisihArr.join(", ");

        productSubmit.push(item);
    });

    $.ajax({
        url: "/accStockOpname",
        data: {
            sto_id: data.sto_id,
            item: JSON.stringify(productSubmit),
            _token: token,
        },
        method: "post",
        success: function (e) {
            ResetLoadingButton(".btn-konfirmasi", "Konfirmasi");
            if (typeof e === "object" && e !== null) {
                notifikasi("error", e.header, e.message);
                return false;
            }
            $("#modalDelete .modal-body").html("");
            $(".modal").modal("hide");
            notifikasi(
                "success",
                "Berhasil Approve",
                "Berhasil approve stock opname",
            );
            window.open("/stockOpname", "_self");
        },
        error: function (e) {
            ResetLoadingButton(".btn-konfirmasi", "Konfirmasi");
            if (handlePermissionError(e)) return;
            console.log(e);
        },
    });
});

$(document).on("click", ".save-tolak", function () {
    showModalDelete(
        "Apakah yakin ingin menolak stock opname ini?",
        "btn-tolak-sto",
    );
    $("#btn-tolak-sto").html("Delete");
});

$(document).on("click", "#btn-tolak-sto", function () {
    LoadingButton(this);
    $.ajax({
        url: "/tolakStockOpname",
        data: {
            sto_id: data.sto_id,
            _token: token,
        },
        method: "post",
        success: function (e) {
            $("#modalDelete .modal-body").html("");
            $(".modal").modal("hide");
            ResetLoadingButton(".btn-konfirmasi", "Delete");
            notifikasi(
                "success",
                "Berhasil Tolak",
                "Berhasil tolak Stock Opname",
            );
            window.open("/stockOpname", "_self");
        },
        error: function (e) {
            ResetLoadingButton(".btn-konfirmasi", "Delete");
            if (handlePermissionError(e)) return;
            console.log(e);
        },
    });
});
