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
function escapeHtml(str) {
    if (str == null) return "";
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;");
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

    $("#tb-stock-wrap").addClass("is-loading");

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
                var rl_stock = "";

                item.stock.forEach((element) => {
                    // GitHub #78 follow-up (merged from main's 54e564c): only hint the live stock
                    // on an EXISTING document being re-edited (mode 2, e.g. a draft reload) -- a
                    // brand-new create form (mode 1) stays fully blank, per the user's explicit
                    // request.
                    // GitHub #115: draft murni cuma boleh menampilkan apa yang sudah diinput
                    // sendiri -- stok sistem TIDAK ditampilkan sama sekali selama masih draft.
                    let createPlaceholder =
                        mode == 2 && !data.is_draft
                            ? ` placeholder="${element.ps_stock}"`
                            : "";
                    rl_stock += `
                        <input type="text"
                            class="form-control real-stock nominal_only text-end"
                            value=""${createPlaceholder}
                            data-unit-id="${element.unit_id}"
                            data-unit-name="${element.unit_short_name}"
                            data-system-qty="${data.is_draft ? "" : element.ps_stock}">
                        <span class="input-group-text">${escapeHtml(element.unit_short_name)}</span>
                    `;
                });

                $("#tbStock").append(`
                    <tr class="row-stock" data-product-id="${item.product_id}" data-variant-id="${item.product_variant_id}">
                        <td>
                            <span class="text-dark">${escapeHtml(item.product_variant_sku || "-")}</span>
                        </td>
                        <td>
                            <span class="fw-semibold text-dark d-block" style="font-size:13px;">${escapeHtml(item.pr_name)}</span>
                            ${item.product_variant_name ? `<span class="d-block mt-1" style="font-size:12px;font-weight:700;color:#475569;">${escapeHtml(item.product_variant_name)}</span>` : ''}
                        </td>
                        <td class="text-center">
                            <div class="input-group rstock">
                                ${rl_stock}
                            </div>
                            <input type="hidden" class="data">
                            <input type="hidden" class="stod_id">
                        </td>
                        <td>
                            <input type="text" class="form-control notes" placeholder="Catatan.." value="${mode == 2 ? escapeHtml(item.stod_notes ?? "") : ""}">
                            <input type="hidden" class="form-control input-selesih">
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
                    `<tr><td colspan="4" class="text-center py-4 text-muted">Produk tidak ditemukan</td></tr>`,
                );
            }
            if (mode == 2 && !canEditDraft) {
                $(".real-stock, .notes").attr("disabled", "disabled");
            }

            if (typeof callback === "function") callback();
        },
        error: function (e) {
            if (e && e.statusText === "abort") return;
            if (handlePermissionError(e)) return;
            if (reqId !== stockOpnameReqSeq) return;
            console.log(e);
        },
        complete: function () {
            if (reqId !== stockOpnameReqSeq) return;
            $("#tb-stock-wrap").removeClass("is-loading");
        },
    });
}

function renderMode2(items) {
    $("#tbStock").html("");
    items.forEach((item) => {
        console.log(item);
        var rl_stock = "";

        item.units.forEach((element) => {
            // GitHub #78 (merged from main's 54e564c): real_qty null = satuan ini tidak pernah
            // benar-benar dihitung staf -- input harus tampil KOSONG (bukan diisi angka fallback
            // lagi), TAPI tetap tampilkan stok live sebagai placeholder abu-abu supaya viewer punya
            // rujukan. Placeholder BUKAN value -- disabled + kosong tetap menunjukkan "-" (tidak
            // dihitung) di histori dokumen, tidak menghidupkan lagi bug fallback lama.
            let untouched = element.real_qty === null || element.real_qty === undefined;
            let prefill = untouched ? "" : formatRupiah(String(element.real_qty));
            // GitHub #115: draft tidak membawa live_qty/system_qty sama sekali (backend sengaja
            // tidak mengambilnya) -- tidak ada placeholder untuk ditampilkan.
            let systemHint = element.live_qty ?? element.system_qty;
            let placeholderAttr =
                untouched && systemHint !== null && systemHint !== undefined
                    ? ` placeholder="${formatRupiah(String(systemHint))}"`
                    : "";
            rl_stock += `
                <input type="text"
                    class="form-control real-stock nominal_only text-end"
                    value="${prefill}"${placeholderAttr}
                    disabled
                    data-unit-id="${element.unit_id}"
                    data-unit-name="${element.unit_short_name}"
                    data-system-qty="${element.system_qty}">
                <span class="input-group-text">${escapeHtml(element.unit_short_name)}</span>
            `;
        });

        $("#tbStock").append(`
            <tr class="row-stock" data-product-id="${item.product_id}" data-variant-id="${item.product_variant_id}">
                <td>
                    <span class="text-dark">${escapeHtml(item.product_variant_sku || "-")}</span>
                </td>
                <td>
                    <span class="fw-semibold text-dark d-block" style="font-size:13px;">${escapeHtml(item.pr_name)}</span>
                    ${item.product_variant_name ? `<span class="d-block mt-1" style="font-size:12px;font-weight:700;color:#475569;">${escapeHtml(item.product_variant_name)}</span>` : ''}
                </td>
                <td class="text-center">
                    <div class="input-group rstock">
                        ${rl_stock}
                    </div>
                    <input type="hidden" class="data">
                    <input type="hidden" class="stod_id">
                </td>
                <td>
                    <input type="text" class="form-control notes" placeholder="Catatan.." value="${escapeHtml(item.stod_notes ?? "")}" disabled>
                    <input type="hidden" class="form-control input-selesih">
                </td>
            </tr>
        `);
    });

    if (items.length == 0) {
        $("#tbStock").html(
            `<tr><td colspan="4" class="text-center py-4 text-muted">Produk tidak ditemukan</td></tr>`,
        );
    }
}

$(document).on("keyup", "#filter_pr_name", function () {
    // Edit draft = mode 2 + canEditDraft — tetap AJAX katalog, bukan filter lokal view-only.
    if (mode == 1 || canEditDraft) {
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
        // Keputusan user 2026-09-05: cek peluang gulung DULU (murni baca DOM + panggilan
        // read-only, tidak ada apa pun tertulis ke DB) sebelum insertData() beneran menyimpan --
        // kalau staf klik "Batal" pada popup, insertData() TIDAK PERNAH dipanggil sama sekali.
        previewStockOpnameRollup(
            collectStockOpnameItems(false),
            function (decision) {
                insertData({
                    btnSelector: ".btn-save",
                    doneText: "Tambah Stok Opname",
                    rollupDecision: decision,
                });
            },
            function () {
                ResetLoadingButton(".btn-save", "Tambah Stok Opname");
            },
        );
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
        // Sama seperti .btn-save: cek peluang gulung DULU dari DOM langsung, SEBELUM draft-nya
        // sendiri disimpan (insertData(isDraft:true)) atau diajukan (submitStockOpname()) --
        // klik "Batal" pada popup berarti benar-benar batal, tidak ada draft yang ikut tersimpan.
        //
        // keepSparse=false (BUKAN true) di SINI -- beda dari .btn-save-draft di atas (keepSparse
        // tetap true di sana, itu memang "simpan progres saja"). "Ajukan" berarti MENERBITKAN
        // dokumen ini, jadi harus memperlakukan SELURUH katalog yang sedang tampil seakan sudah
        // final -- sama persis semangatnya dengan .btn-save (create langsung) -- supaya popup
        // gulung konsisten baik diajukan langsung maupun sesudah sempat disimpan sebagai draft
        // lalu dibuka lagi (keputusan user 2026-09-05, bug report: draft yang cuma menyimpan
        // baris yang diisi tadinya membuat pratinjau kedua kalinya kehilangan kandidat gulung
        // yang sebelumnya muncul). Baris untuk produk yang tidak diisi tetap masuk dengan
        // real_qty null -- konsisten dengan bagaimana .btn-save selalu mengirim seluruh katalog.
        previewStockOpnameRollup(
            collectStockOpnameItems(false),
            function (decision) {
                insertData({
                    isDraft: true,
                    btnSelector: ".btn-ajukan",
                    doneText: "Ajukan",
                    onSuccess: function () {
                        submitStockOpname(decision);
                    },
                });
            },
            function () {
                ResetLoadingButton(".btn-ajukan", "Ajukan");
            },
        );
    });
});

/**
 * Ajukan draft (dipanggil setelah insertData/updateData yang menyimpan draft berhasil).
 * rollupDecision datang dari previewStockOpnameRollup() yang sudah dijawab staf SEBELUM draft-nya
 * sendiri disimpan -- lihat klik-handler .btn-ajukan di atas. Tetap mengerti status==2 (jaring
 * pengaman kalau backend somehow belum tahu keputusannya, lihat OpnameLifecycle::
 * detectRollupOpportunities()'s docblock) tapi jalur normal harusnya tidak pernah lewat situ lagi.
 */
function submitStockOpname(rollupDecision) {
    var param = { sto_id: data.sto_id, _token: token };
    if (rollupDecision) {
        param.rollup_decision = rollupDecision;
    }

    $.ajax({
        url: "/submitStockOpname",
        data: param,
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
            if (e && e.status == 2 && Array.isArray(e.rollup_candidates)) {
                showRollupConfirm(e.rollup_candidates, function (decision) {
                    submitStockOpname(decision);
                });
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
}

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

/**
 * Bangun array item (per varian x per satuan) langsung dari DOM tabel Stock Opname -- MURNI baca,
 * tidak ada validasi maupun AJAX di sini. Dipakai insertData() untuk payload SUNGGUHAN, dan
 * previewStockOpnameRollup() (dipanggil dari klik-handler .btn-save/.btn-ajukan, SEBELUM
 * insertData() pernah dipanggil) untuk cek peluang gulung tanpa menyentuh DB sama sekali --
 * "data bayangan", keputusan user 2026-09-05.
 */
function collectStockOpnameItems(keepSparse) {
    var items = [];
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
            // GitHub #78: satuan yang dibiarkan kosong TIDAK dianggap "dihitung = stok sistem" --
            // PM sudah konfirmasi fallback lama itu bukan perilaku yang wajib dipertahankan.
            // Kirim null / token "-" apa adanya supaya backend tahu ini belum pernah dihitung.
            let counted = realQty !== -1;

            units.push({
                unit_id: unitId,
                system_qty: systemQty,
                real_qty: counted ? realQty : null,
            });

            systemArr.push(systemQty + " " + unitName);
            realArr.push((counted ? realQty : "-") + " " + unitName);
            selisihArr.push(
                (counted ? realQty - systemQty : "-") + " " + unitName,
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
        // GitHub #53: baris ini pernah benar-benar diisi user, dibedakan dari yang cuma
        // ikut tersimpan dengan real qty auto = stok sistem karena dibiarkan kosong --
        // dipakai PDF (Backoffice/PDF/Opname.blade.php) untuk highlight.
        item.stod_touched = hasValue ? 1 : 0;

        items.push(item);
    });

    return items;
}

/**
 * Cek peluang gulung TANPA menulis apa pun ke DB (keputusan user 2026-09-05) -- panggil SEBELUM
 * insertData()/submitStockOpname() beneran menyimpan. items dari collectStockOpnameItems().
 * onProceed(decision) dipanggil kalau boleh lanjut ("full" kalau staf klik Lanjut pada popup,
 * "skip" kalau tidak ada peluang sama sekali -- popup tidak pernah muncul). onCancel() dipanggil
 * HANYA kalau staf klik Batal pada popup -- setelah itu TIDAK ADA AJAX submit apa pun yang boleh
 * jalan, jadi tidak ada database yang tersentuh sama sekali.
 */
function previewStockOpnameRollup(items, onProceed, onCancel) {
    $.ajax({
        url: "/previewStockOpnameRollup",
        data: { item: JSON.stringify(items), _token: token },
        method: "post",
        success: function (e) {
            var candidates = (e && e.rollup_candidates) || [];
            if (!Array.isArray(candidates) || candidates.length === 0) {
                onProceed("skip");
                return;
            }
            // show_popup: OpnameLifecycle::ROLLUP_PROJECTION_ENABLED (backend) CUMA mengatur
            // tampilan popup, bukan deteksi/gulungnya sendiri (koreksi 2026-09-06) -- kalau
            // false, langsung anggap staf sudah klik "Lanjut" tanpa menampilkan modal sama sekali.
            if (e && e.show_popup === false) {
                onProceed("full");
                return;
            }
            showRollupConfirm(candidates, function (decision) {
                if (decision === "full") {
                    onProceed("full");
                } else if (typeof onCancel === "function") {
                    onCancel();
                }
            });
        },
        error: function (e) {
            if (handlePermissionError(e)) return;
            console.log(e);
            // Preview gagal (mis. jaringan) -- jangan diam-diam memblokir submit, lanjut dengan
            // gulung PARSIAL yang aman seperti sebelum fitur konfirmasi ini ada.
            onProceed("skip");
        },
    });
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

    productSubmit = collectStockOpnameItems(keepSparse);
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
    // Keputusan gulung sudah dijawab staf lewat previewStockOpnameRollup() SEBELUM insertData()
    // ini pernah dipanggil (lihat klik-handler .btn-save/.btn-ajukan) -- endpoint di bawah ini
    // selalu single-shot, tidak ada lagi jalur "tulis dulu, tanya nanti".
    if (options.rollupDecision) {
        param.rollup_decision = options.rollupDecision;
    }
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
            toastr.error("", "Terjadi Kesalahan Saat Tambah Stok Opname");
            console.log(e);
        },
    });
}

/**
 * Popup konfirmasi gulung PENUH (keputusan user 2026-09-04, bug report "93 Dos, 104 Piece"):
 * ditampilkan HANYA kalau backend mendeteksi ada produk yang satuan kecilnya tidak disentuh staf
 * tapi bisa dilipat ke satuan besar yang staf koreksi. Modal PG bergradien (#modalRollupConfirm,
 * components/modals/stock-opname/rollup-confirm.blade.php) -- bukan SweetAlert2 -- dengan tabel
 * rincian per produk x per satuan (before -> after), dikunci 4 baris tinggi lalu scroll.
 * onDecision dipanggil dengan "full" (staf klik Lanjut) atau "skip" (Batal -- gulung parsial
 * otomatis yang aman tetap jalan seperti biasa).
 */
function showRollupConfirm(candidates, onDecision) {
    var $rows = $("#rollup-confirm-rows");
    $rows.empty();

    candidates.forEach(function (c) {
        // Backend sudah mengurutkan ch dari satuan BESAR ke KECIL (OpnameLifecycle::
        // buildRollupOpportunity()) -- di sini tinggal dicetak apa adanya. Panah (&larr;) SENGAJA
        // di LUAR <span class="rollup-unit-before"> supaya coret cuma menembus angkanya, bukan
        // ikut mencoret panahnya (2026-09-05, dulu satu span yang sama membungkus keduanya).
        var chips = (c.changes || [])
            .map(function (ch) {
                return (
                    '<span class="rollup-unit-chip">' +
                    escapeHtml(ch.unit_short_name || "") +
                    " " +
                    ch.after +
                    ' <span class="rollup-arrow">&larr;</span> ' +
                    '<span class="rollup-unit-before">' +
                    ch.before +
                    "</span></span>"
                );
            })
            .join(" ");

        $rows.append(
            $("<tr>").append(
                $("<td>").text(
                    c.product_name || "Produk#" + c.product_variant_id,
                ),
                $("<td>").html(
                    '<div class="d-flex flex-wrap gap-1">' + chips + "</div>",
                ),
            ),
        );
    });

    var $modal = $("#modalRollupConfirm");
    // decided dipegang di closure ini, bukan langsung memanggil onDecision di handler klik --
    // supaya menutup modal lewat X/backdrop/ESC (bukan klik salah satu tombol) juga sampai ke
    // onDecision (dianggap "Batal") lewat SATU titik (hidden.bs.modal), bukan dua jalur berbeda
    // yang bisa saling menumpuk. .off() dulu di semuanya supaya popup sebelumnya tidak ikut
    // memanggil onDecision dua kali.
    var decided = null;
    $("#btn-rollup-confirm-lanjut")
        .off("click")
        .on("click", function () {
            decided = "full";
            $modal.modal("hide");
        });
    $("#btn-rollup-confirm-batal")
        .off("click")
        .on("click", function () {
            decided = "skip";
            $modal.modal("hide");
        });
    $modal.off("hidden.bs.modal").on("hidden.bs.modal", function () {
        onDecision(decided || "skip");
    });

    $modal.modal("show");
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
            // GitHub #78: mirrors insertData() above -- blank stays blank, no fallback to system.
            let counted = realQty !== -1;

            units.push({
                unit_id: unitId,
                system_qty: systemQty,
                real_qty: counted ? realQty : null,
            });

            systemArr.push(systemQty + " " + unitName);
            realArr.push((counted ? realQty : "-") + " " + unitName);
            selisihArr.push(
                (counted ? realQty - systemQty : "-") + " " + unitName,
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
