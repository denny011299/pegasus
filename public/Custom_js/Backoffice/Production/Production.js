autocompleteBom("#product_id", "#addProduction");
var mode = 1; // 1 = insert; 2 = edit; 3 = view
var modeBahan = 1;
var table;
var items = [];
var list_photo = [];
var list_bahan = [];

function productionActiveWarehouseName() {
    return (
        (window.activeWarehouse &&
            (window.activeWarehouse.name ||
                window.activeWarehouse.warehouse_name)) ||
        "Gudang utama aktif"
    );
}

function syncProductionDestinationControl() {
    var product = $("#product_id").select2("data")[0] || {};
    var isRetail =
        parseInt(product.retail_unit || 0, 10) > 0 &&
        parseInt($("#unit_id").val() || 0, 10) ===
            parseInt(product.retail_unit, 10);
    var $badge = $("#production-main-warehouse-badge");
    var $dest = $("#production_destination_warehouse_id");
    var $destSelect2 = $dest.next(".select2-container");

    $badge.find("span").text(productionActiveWarehouseName());

    if (isRetail) {
        // Satuan eceran → pilih gudang eceran (d-none, bukan .hide — badge pakai d-flex !important)
        $badge.addClass("d-none").removeClass("d-flex");
        if (typeof autocompleteWarehouse === "function") {
            autocompleteWarehouse(
                "#production_destination_warehouse_id",
                "#addProduction",
                { placeholder: "Pilih gudang eceran tujuan", retailOnly: true },
            );
        }
        $destSelect2 = $dest.next(".select2-container");
        if ($destSelect2.length) {
            $destSelect2.show();
        } else {
            $dest.show();
        }
    } else {
        $dest.val(null).trigger("change");
        if ($destSelect2.length) {
            $destSelect2.hide();
        }
        $dest.hide();
        $badge.removeClass("d-none").addClass("d-flex");
    }
}

function resetProductionApprovalActions() {
    $("#addProduction #btn-terima, #addProduction #btn-tolak")
        .addClass("d-none")
        .removeClass("btn_acc_produksi btn_decline_produksi btn_acc btn_cancel")
        .removeAttr("production_id");
}

function setProductionSaveVisible(visible, label) {
    var $btn = $("#addProduction .btn-save");
    if (label) {
        if ($btn.find(".btn-save-label").length) {
            $btn.find(".btn-save-label").text(label);
        } else {
            $btn.html(
                '<i class="fe fe-save"></i> <span class="btn-save-label">' +
                    label +
                    "</span>",
            );
        }
        $destSelect2 = $dest.next(".select2-container");
        if ($destSelect2.length) {
            $destSelect2.show();
        } else {
            $dest.show();
        }
    } else {
        $dest.val(null).trigger("change");
        if ($destSelect2.length) {
            $destSelect2.hide();
        }
        $dest.hide();
        $badge.removeClass("d-none").addClass("d-flex");
    }
}

function resetProductionApprovalActions() {
    $("#addProduction #btn-terima, #addProduction #btn-tolak")
        .addClass("d-none")
        .removeClass("btn_acc_produksi btn_decline_produksi btn_acc btn_cancel")
        .removeAttr("production_id");
}

function showProductionApprovalActions(action, productionId) {
    resetProductionApprovalActions();
    if (!hasAccessAction("Produksi", "others")) {
        return;
    }
    if (visible) {
        $btn.removeClass("d-none").addClass("d-inline-flex");
    } else {
        $btn.removeClass("d-inline-flex").addClass("d-none");
    }
}

function setProductionModalMode(kind) {
    var $modal = $("#addProduction");
    $modal.removeClass("pg-modal--form pg-modal--confirm");
    if (kind === "confirm") {
        $modal.addClass("pg-modal--confirm");
        $modal
            .find(".modal-header")
            .attr(
                "style",
                "background:linear-gradient(135deg,#064e3b 0%,#059669 100%);padding:18px 24px;",
            );
    } else {
        $modal.addClass("pg-modal--form");
        $modal
            .find(".modal-header")
            .attr(
                "style",
                "background:linear-gradient(135deg,#1e3a8a 0%,#3b82f6 100%);padding:18px 24px;",
            );
    }
}

function showProductionApprovalActions(action, productionId) {
    resetProductionApprovalActions();
    if (!hasAccessAction("Produksi", "others")) {
        return;
    }

    var $accept = $("#addProduction #btn-terima");
    var $decline = $("#addProduction #btn-tolak");
    if (action === "production") {
        $accept
            .addClass("btn_acc_produksi")
            .html('<i class="fe fe-check"></i> Terima Produksi');
        $decline
            .addClass("btn_decline_produksi")
            .html('<i class="fe fe-x"></i> Tolak');
    } else if (action === "cancellation") {
        $accept
            .addClass("btn_acc")
            .html('<i class="fe fe-check"></i> Terima Pembatalan');
        $decline.addClass("btn_cancel").html('<i class="fe fe-x"></i> Tolak');
    } else {
        return;
    }

    $accept
        .add($decline)
        .attr("production_id", productionId)
        .removeClass("d-none");
}

$("#addProduction").on("hidden.bs.modal", function () {
    resetProductionApprovalActions();
    setProductionModalMode("form");
    setProductionSaveVisible(true, "Simpan");
    if (!$("#modalBahan").hasClass("show")) {
        $(this)
            .removeAttr("production_id revision_source_production_id")
            .removeData("approval-action");
    }
});

function getTodayStr() {
    let today = new Date();
    let yyyy = today.getFullYear();
    let mm = String(today.getMonth() + 1).padStart(2, "0");
    let dd = String(today.getDate()).padStart(2, "0");
    return yyyy + "-" + mm + "-" + dd;
}

function convertQtyToSmallestUnit(qty, unitId, productData) {
    var multiplier = 1;
    var relations = productData.relasi || [];
    relations.forEach(function (relation) {
        if (parseInt(relation.pr_unit_id_2) !== parseInt(unitId)) {
            multiplier *= parseInt(relation.pr_unit_value_2);
        }
    });
    return qty * multiplier;
}

function cekQtyKelipatanResep(pdQty, unitId, bomData) {
    if (!bomData || !bomData.bom_qty) {
        return { valid: true };
    }
    var pdSmallest = convertQtyToSmallestUnit(pdQty, unitId, bomData);
    var bomSmallest = convertQtyToSmallestUnit(
        parseInt(bomData.bom_qty),
        parseInt(bomData.unit_id),
        bomData,
    );
    if (bomSmallest <= 0) {
        bomSmallest = parseInt(bomData.bom_qty);
    }
    return { valid: pdSmallest % bomSmallest === 0 };
}

function getBomDetailRows(bomData) {
    if (!bomData) {
        return [];
    }
    var details = bomData.details || bomData.items || [];
    return Array.isArray(details) ? details : [];
}

function bomDetailHasActiveUnits(bomData) {
    return getBomDetailRows(bomData).some(function (detail) {
        var activeUnits = detail.active_units || detail.units || [];
        return Array.isArray(activeUnits) && activeUnits.length > 0;
    });
}

function loadBomForValidation(bomId, callback) {
    $.ajax({
        url: "/getBom",
        method: "get",
        data: { bom_id: bomId, with_details: 1 },
        success: function (response) {
            callback(response && response[0] ? response[0] : null);
        },
        error: function () {
            callback(null);
        },
    });
}

function validateBomActiveUnits(bomData) {
    var details = getBomDetailRows(bomData);
    if (details.length === 0) {
        return { valid: true, invalid: [] };
    }

    var invalid = [];
    var hasUnitData = false;

    details.forEach(function (detail) {
        var activeUnits = detail.active_units || detail.units || [];
        if (!Array.isArray(activeUnits) || activeUnits.length === 0) {
            return;
        }

        hasUnitData = true;
        var unitId = detail.unit_id;
        var isActive = activeUnits.some(function (unit) {
            return parseInt(unit.unit_id, 10) === parseInt(unitId, 10);
        });

        if (!isActive) {
            var label =
                (detail.supplies_name || "-") +
                " (" +
                (detail.current_unit_name || detail.unit_name || "-") +
                ")";
            if (invalid.indexOf(label) === -1) {
                invalid.push(label);
            }
        }
    });

    // Data autocomplete belum punya active_units — validasi dibiarkan ke backend.
    if (!hasUnitData) {
        return { valid: true, invalid: [] };
    }

    return { valid: invalid.length === 0, invalid: invalid };
}

function resolveProductionInputQtyUnit(tempBom) {
    var rawQty = parseInt($("#production_qty").val(), 10) || 0;
    var selected = $("#unit_id option:selected");
    var unitVal = String($("#unit_id").val() || "");

    if (unitVal === "__PALLET__") {
        var perPallet =
            parseInt(selected.data("qty-per-pallet"), 10) ||
            parseInt(tempBom && tempBom.qty_per_pallet, 10) ||
            0;
        var defaultUnitId =
            parseInt(selected.data("default-unit-id"), 10) ||
            parseInt(
                tempBom && (tempBom.default_unit || tempBom.unit_id),
                10,
            ) ||
            0;
        var defaultUnitName =
            selected.data("default-unit-name") ||
            (tempBom && tempBom.default_unit_name) ||
            "DOS";
        if (perPallet <= 0 || defaultUnitId <= 0) {
            return {
                ok: false,
                message: "Isi per pallet belum diatur di master varian produk.",
            };
        }
        return {
            ok: true,
            pd_qty: rawQty * perPallet,
            unit_id: defaultUnitId,
            unit_name: defaultUnitName,
            from_pallet: true,
            pallet_qty: rawQty,
            qty_per_pallet: perPallet,
        };
    }

    return {
        ok: true,
        pd_qty: rawQty,
        unit_id: parseInt(unitVal, 10) || 0,
        unit_name: selected.text(),
        from_pallet: false,
    };
}

function continueAddProduct(tempBom) {
    var satuanResep = validateBomActiveUnits(tempBom);
    if (!satuanResep.valid) {
        notifikasi(
            "error",
            "Satuan Resep Tidak Aktif",
            "Satuan bahan pada resep sudah tidak aktif. Perbarui resep terlebih dahulu: " +
                satuanResep.invalid.join(", "),
        );
        return false;
    }

    var resolved = resolveProductionInputQtyUnit(tempBom);
    if (!resolved.ok) {
        notifikasi("error", "Pallet Tidak Valid", resolved.message);
        return false;
    }
    if (resolved.pd_qty <= 0 || !resolved.unit_id) {
        notifikasi(
            "error",
            "Qty Tidak Valid",
            "Qty / satuan produksi belum lengkap.",
        );
        return false;
    }

    var qtyKelipatan = cekQtyKelipatanResep(
        resolved.pd_qty,
        resolved.unit_id,
        tempBom,
    );
    if (!qtyKelipatan.valid) {
        notifikasi(
            "error",
            "Qty Tidak Valid",
            "Qty produksi harus kelipatan resep bahan mentah (" +
                tempBom.bom_qty +
                " " +
                (tempBom.unit_name || "") +
                ") untuk produk: " +
                tempBom.product_name,
        );
        return false;
    }

    var temp = $("#product_id").select2("data")[0];
    var destinationId = parseInt(
        $("#production_destination_warehouse_id").val() || 0,
        10,
    );
    var idx = -1;
    items.forEach(function (element) {
        if (
            element.product_variant_id == temp.product_variant_id &&
            element.unit_id == resolved.unit_id &&
            parseInt(element.destination_warehouse_id || 0, 10) ===
                destinationId
        ) {
            element.pd_qty += resolved.pd_qty;
            idx = 1;
        }
    });

    if (idx == 1) {
        var mergedItem = items.find(function (element) {
            return (
                element.product_variant_id == temp.product_variant_id &&
                element.unit_id == resolved.unit_id &&
                parseInt(element.destination_warehouse_id || 0, 10) ===
                    destinationId
            );
        });
        var qtyKelipatanGabung = cekQtyKelipatanResep(
            mergedItem.pd_qty,
            mergedItem.unit_id,
            tempBom,
        );
        if (!qtyKelipatanGabung.valid) {
            mergedItem.pd_qty -= resolved.pd_qty;
            notifikasi(
                "error",
                "Qty Tidak Valid",
                "Total qty produksi harus kelipatan resep bahan mentah (" +
                    tempBom.bom_qty +
                    " " +
                    (tempBom.unit_name || "") +
                    ") untuk produk: " +
                    tempBom.product_name,
            );
            return false;
        }
    }

    if (idx == -1) {
        var destinationData = $(
            "#production_destination_warehouse_id",
        ).hasClass("select2-hidden-accessible")
            ? $("#production_destination_warehouse_id").select2("data")[0] || {}
            : {};
        var data = {
            product_variant_id: temp.product_variant_id,
            product_name: temp.product_name,
            pd_qty: resolved.pd_qty,
            unit_name: resolved.unit_name,
            unit_id: resolved.unit_id,
            retail_unit: parseInt(temp.retail_unit || 0, 10) || null,
            default_unit: parseInt(temp.default_unit || 0, 10) || null,
            destination_warehouse_id: destinationId || null,
            destination_warehouse_name:
                destinationData.text || productionActiveWarehouseName(),
            bom_id: temp.bom_id,
        };
        items.push(data);
    }
    addRow(items);

    $("#product_id").empty();
    $("#unit_id").empty();
    $("#unit_id").append("<option selected>Pilih Satuan</option>");
    $("#production_qty").val("");
    $("#production_pallet_hint").text("");
    $("#production_destination_warehouse_id").val(null).trigger("change");
    syncProductionDestinationControl();
    return true;
}
$(document).ready(function () {
    // $('#date_production').val(moment().format('YYYY-MM-DD')).trigger("change");
    inisialisasi();
    refreshProduction();
});

$(document).on("click", ".btnAdd", function () {
    resetProductionApprovalActions();
    setProductionModalMode("form");
    mode = 1;
    modeBahan = 1;
    items = [];
    list_bahan = [];
    $("#addProduction .modal-title").html("Tambah Produksi");
    $("#addProduction input").val("");
    $("#product_id").empty();
    $("#production_qty").val("");
    $("#tableProduct tr.row-product").remove();
    $(".is-invalid").removeClass("is-invalid");
    $("#unit_id").html("");
    $("#unit_id").append("<option selected>Pilih Satuan</option>");
    $(".input_table, .add, .btn_delete_row_pr").show();
    setProductionSaveVisible(true, "Tambah Produksi");
    $("#production_desc").attr("disabled", false);
    $(".btn-cancel").html("Batal");
    $("#addProduction").modal("show");
    $(".dos").hide();
    $("#production_date").val(getTodayStr()).prop("disabled", true);
    $("#addProduction").removeAttr("revision_source_production_id");
});

$(document).on("keyup", "#production_qty", function () {
    var data = $("#product_id").select2("data")[0];

    var qty = $(this).val();
    if (qty == "" || qty == null || isNaN(qty)) {
        qty = 0;
    }
    $("#production_total").val(qty);
    updateProductionPalletHint();
});

$(document).on("change", "#unit_id", function () {
    updateProductionPalletHint();
});

function updateProductionPalletHint() {
    var $hint = $("#production_pallet_hint");
    if (!$hint.length) return;
    var selected = $("#unit_id option:selected");
    if (String($("#unit_id").val()) !== "__PALLET__") {
        $hint.text("");
        return;
    }
    var qty = parseInt($("#production_qty").val(), 10) || 0;
    var per = parseInt(selected.data("qty-per-pallet"), 10) || 0;
    var unitName = selected.data("default-unit-name") || "DOS";
    if (per <= 0) {
        $hint.text("");
        return;
    }
    $hint.text("= " + qty * per + " " + unitName);
}

$(document).on("change", "#product_id", function () {
    var data = $(this).select2("data")[0];
    console.log(data);

    // Blokir jika produk / varian sudah tidak aktif
    if (
        data &&
        (data.product_status == 0 || data.product_variant_status == 0)
    ) {
        var alasan = [];
        if (data.product_status == 0) alasan.push("produk sudah tidak aktif");
        if (data.product_variant_status == 0)
            alasan.push("varian produk sudah tidak aktif");
        notifikasi(
            "error",
            "Produk Tidak Aktif",
            "Tidak dapat memilih resep ini karena " +
                alasan.join(" & ") +
                ". Silakan hapus resep (BOM) ini di halaman Resep Bahan Mentah.",
        );
        // Clear pilihan agar user tidak bisa lanjut
        $(this).val(null).trigger("change");
        $("#unit_id").html("");
        return;
    }

    $("#unit_id").html("");
    data.pr_unit.forEach((element) => {
        $("#unit_id").append(
            `<option value="${element.unit_id}">${element.unit_name}</option>`,
        );
    });
    // Shortcut Produksi: input Pallet → convert ke satuan default (DOS/dll)
    var qtyPerPallet = parseInt(data.qty_per_pallet, 10) || 0;
    if (qtyPerPallet > 0) {
        var defaultUnitName = data.default_unit_name || "DOS";
        $("#unit_id").append(
            `<option value="__PALLET__" data-qty-per-pallet="${qtyPerPallet}" data-default-unit-id="${data.default_unit || data.unit_id}" data-default-unit-name="${defaultUnitName}">PALLET (1 = ${qtyPerPallet} ${defaultUnitName})</option>`,
        );
    }
    $("#unit_id")
        .val(data.default_unit || data.unit_id)
        .trigger("change");
    $("#pi_unit option").first().prop("selected", true);

    $("#production_qty").trigger("keyup");
});

$(document).on("change", "#unit_id", syncProductionDestinationControl);

// Cegah Enter menutup modal secara tidak sengaja (form action="#" menyebabkan page navigation)
$(document).on(
    "keydown",
    "#addProduction input, #addProduction select",
    function (e) {
        if (e.key === "Enter") {
            e.preventDefault();
        }
    },
);

var productionXhr = null;

function setProductionTableLoading(isLoading) {
    var $wrap = $("#tableProduction-wrap");
    if (!$wrap.length) return;
    $wrap.toggleClass("is-loading", !!isLoading);
}

function renderProductionStatus(status) {
    status = parseInt(status, 10);
    if (status === 1) {
        return '<span class="badge" style="background-color: #fff7ed; color: #ea580c; border: 1px solid #ffedd5; padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 12px; letter-spacing: 0.3px;"><i class="fe fe-clock me-1"></i> Pending</span>';
    }
    if (status === 2) {
        return '<span class="badge" style="background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0; padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 12px; letter-spacing: 0.3px;"><i class="fe fe-check-circle me-1"></i> Berhasil</span>';
    }
    if (status === 3) {
        return '<span class="badge" style="background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 12px; letter-spacing: 0.3px;"><i class="fe fe-x-circle me-1"></i> Tolak</span>';
    }
    if (status === 4) {
        return '<span class="badge" style="background-color: #fffbeb; color: #d97706; border: 1px solid #fde68a; padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 12px; letter-spacing: 0.3px;"><i class="fe fe-alert-circle me-1"></i> Menunggu Batal</span>';
    }
    return "-";
}

function renderProductionAction(row) {
    var isOldRow = moment(row.production_date).isBefore(
        moment().subtract(2, "days").format("YYYY-MM-DD"),
    );
    var prAct = "";
    var status = parseInt(row.status, 10);

    if (hasAccessAction("Produksi", "view")) {
        prAct +=
            '<a href="javascript:void(0);" class="btn-action-icon btn_view" style="background:#eff6ff;border:1px solid #bfdbfe;color:#2563eb;" data-bs-toggle="tooltip" title="Lihat Detail Produksi"><i class="fe fe-eye" style="font-size:14px;"></i></a>';
    }
    if (!isOldRow && status === 2 && hasAccessAction("Produksi", "delete")) {
        prAct +=
            '<a href="javascript:void(0);" class="btn-action-icon btn_delete" style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;" data-bs-toggle="tooltip" title="Batalkan Produksi"><i class="fe fe-x-circle" style="font-size:14px;"></i></a>';
    }
    if (isOldRow || (status !== 1 && status !== 2)) {
        prAct = hasAccessAction("Produksi", "view")
            ? '<a href="javascript:void(0);" class="btn-action-icon btn_view" style="background:#eff6ff;border:1px solid #bfdbfe;color:#2563eb;" data-bs-toggle="tooltip" title="Lihat Detail Produksi"><i class="fe fe-eye" style="font-size:14px;"></i></a>'
            : "";
    }

    if (!prAct) {
        return '<span class="text-muted small">—</span>';
    }
    return (
        '<div class="d-flex justify-content-center align-items-center gap-2">' +
        prAct +
        "</div>"
    );
}

function inisialisasi() {
    table = $("#tableProduction").DataTable({
        processing: true,
        deferRender: true,
        bFilter: true,
        sDom: "fBtlpi",
        lengthMenu: [10, 25, 50, 100],
        pageLength: 10,
        ordering: true,
        order: [],
        autoWidth: false,
        scrollX: false,
        language: {
            search: " ",
            sLengthMenu: "_MENU_",
            searchPlaceholder: "Cari Produksi",
            info: "_START_ - _END_ of _TOTAL_ items",
            emptyTable: "Tidak ada data produksi",
            zeroRecords: "Produksi tidak ditemukan",
            processing:
                '<div><span class="spinner-border spinner-border-sm text-primary" role="status"></span><span>Memuat produksi...</span></div>',
            paginate: {
                next: ' <i class=" fa fa-angle-right"></i>',
                previous: '<i class="fa fa-angle-left"></i> ',
            },
        },
        columns: [
            {
                data: "date",
                width: "12%",
                orderable: false,
            },
            {
                data: "production_code",
                width: "11%",
                orderable: false,
            },
            {
                data: "production_desc",
                defaultContent: "-",
                width: "14%",
                orderable: false,
                render: function (data) {
                    if (!data || data === "-") {
                        return '<span style="color:#64748b;">-</span>';
                    }
                    return data;
                },
            },
            {
                data: "status_text",
                width: "11%",
                className: "text-center",
                orderable: false,
            },
            {
                data: "notes",
                defaultContent: "-",
                width: "12%",
                orderable: false,
                render: function (data) {
                    if (!data || data === "-") {
                        return '<span style="color:#64748b;">-</span>';
                    }
                    return data;
                },
            },
            {
                data: "created_by_name",
                defaultContent: "-",
                width: "12%",
                orderable: false,
                render: function (data) {
                    return typeof renderCreatedByName === "function"
                        ? renderCreatedByName(data)
                        : data || "-";
                },
            },
            {
                data: "acc_by_name",
                defaultContent: "-",
                width: "12%",
                orderable: false,
                render: function (data) {
                    return typeof renderCreatedByName === "function"
                        ? renderCreatedByName(data)
                        : data || "-";
                },
            },
            {
                data: "cancel_requested_by_name",
                defaultContent: "-",
                width: "12%",
                orderable: false,
                render: function (data) {
                    return typeof renderCreatedByName === "function"
                        ? renderCreatedByName(data)
                        : data || "-";
                },
            },
            {
                data: "action",
                className: "text-center align-middle",
                width: "4%",
                orderable: false,
                searchable: false,
            },
        ],
        initComplete: function () {
            var $filter = $(".dataTables_filter").last();
            $filter.appendTo("#tableSearch");
            $filter.appendTo(".search-input");
            if (!$filter.find("label .fa-search").length) {
                $filter.find("label").prepend('<i class="fa fa-search"></i> ');
            }
            $("#tableProduction-wrap")
                .removeClass("dt-pending")
                .addClass("dt-ready");
            if (table) table.columns.adjust();
        },
        drawCallback: function () {
            setProductionTableLoading(false);
            if (typeof feather !== "undefined") feather.replace();
            if (table) table.columns.adjust();
        },
    });
}

function refreshProduction() {
    if (productionXhr && productionXhr.readyState !== 4) {
        productionXhr.abort();
    }

    $("#tableProduction-wrap").removeClass("dt-ready").addClass("dt-pending");
    setProductionTableLoading(true);

    productionXhr = $.ajax({
        url: "/getProduction",
        method: "get",
        data: {
            date: $("#date_production").val(),
            status: $("#status").val(),
        },
        success: function (e) {
            if (!Array.isArray(e)) {
                e = e.original || [];
            }
            table.clear().draw();
            for (let i = 0; i < e.length; i++) {
                e[i].date =
                    `<div style="display:flex;align-items:center;gap:10px;"><div style="width:32px;height:32px;border-radius:8px;background:#f8fafc;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;color:#64748b;flex-shrink:0;"><i class="fe fe-calendar"></i></div><span class="fw-semibold text-dark">${moment(e[i].production_date).format("D MMM YYYY")}</span></div>`;
                if (e[i].production_code) {
                    e[i].production_code =
                        `<span class="badge" style="background:#f0f9ff;color:#0284c7;border:1px solid #e0f2fe;padding:6px 10px;font-family:monospace;font-weight:700;">${e[i].production_code}</span>`;
                }
                e[i].status_text = renderProductionStatus(e[i].status);
                e[i].action = renderProductionAction(e[i]);
            }

            table.rows.add(e).draw();
            if (table) table.columns.adjust();
            if (typeof feather !== "undefined") feather.replace();
            $('[data-bs-toggle="tooltip"]').tooltip();
            openProductionRevisionFromDashboardLink();
            openProductionFromDashboardLink();
        },
        error: function (err) {
            if (err && err.statusText === "abort") return;
            console.error("Gagal load produksi:", err);
        },
        complete: function () {
            setProductionTableLoading(false);
            $("#tableProduction-wrap")
                .removeClass("dt-pending")
                .addClass("dt-ready");
        },
    });
}

/** Dari dashboard: /production?production_id=123 — buka modal detail batch tersebut */
function openProductionFromDashboardLink() {
    try {
        var params = new URLSearchParams(window.location.search);
        var pid = params.get("production_id");
        if (!pid || !table) {
            return;
        }
        var opened = false;
        table.rows().every(function () {
            var d = this.data();
            if (String(d.production_id) === String(pid)) {
                $(this.node()).find(".btn_view").first().trigger("click");
                opened = true;
                return false;
            }
        });
        if (opened) {
            params.delete("production_id");
            var q = params.toString();
            window.history.replaceState(
                {},
                "",
                window.location.pathname + (q ? "?" + q : ""),
            );
        }
    } catch (err) {
        console.warn("openProductionFromDashboardLink", err);
    }
}

/** Dari dashboard revisi: /production?rev_production_id=123 — buka modal revisi dengan data lama */
function openProductionRevisionFromDashboardLink() {
    try {
        var params = new URLSearchParams(window.location.search);
        var pid = params.get("rev_production_id");
        if (!pid || !table) {
            return;
        }

        var rowData = null;
        table.rows().every(function () {
            var d = this.data();
            if (String(d.production_id) === String(pid)) {
                rowData = d;
                return false;
            }
        });

        if (!rowData) return;

        resetProductionApprovalActions();
        mode = 1; // submit ulang sebagai pengajuan baru (pending ACC)
        modeBahan = 1;
        items = [];
        list_bahan = [];

        $("#addProduction .modal-title").html("Revisi Produksi");
        $("#addProduction input").val("");
        $("#product_id").empty();
        $("#production_qty").val("");
        $("#tableProduct tr.row-product").remove();
        $(".is-invalid").removeClass("is-invalid");
        $("#unit_id").html("");

        $("#production_date").val(getTodayStr()).prop("disabled", true);
        $("#production_desc")
            .val(rowData.production_desc || "")
            .attr("disabled", false);

        rowData.items.forEach(function (e) {
            var temp = {
                pd_id: e.pd_id,
                product_variant_id: e.product_variant_id,
                product_name: e.product_name,
                pd_qty: e.pd_qty,
                unit_name: e.unit_name,
                unit_id: e.unit_id,
                retail_unit: e.retail_unit,
                default_unit: e.default_unit,
                destination_warehouse_id: e.destination_warehouse_id,
                destination_warehouse_name: e.destination_warehouse_name,
                bom_id: e.bom_id,
            };
            items.push(temp);
            list_bahan.push(e.list_bahan);
        });

        addRow(items);
        $("#total_dos").html(rowData.total_dos || 0);
        $(".is-invalid").removeClass("is-invalid");
        setProductionModalMode("form");
        $(".input_table, .add, .btn_delete_row_pr").show();
        setProductionSaveVisible(true, "Simpan Revisi");
        $(".dos").show();
        $(".btn-cancel").html("Batal");
        $("#addProduction").removeAttr("production_id");
        $("#addProduction").attr(
            "revision_source_production_id",
            rowData.production_id,
        );
        $("#addProduction").modal("show");

        params.delete("rev_production_id");
        var q = params.toString();
        window.history.replaceState(
            {},
            "",
            window.location.pathname + (q ? "?" + q : ""),
        );
    } catch (err) {
        console.warn("openProductionRevisionFromDashboardLink", err);
    }
}

$(document).on("change", "#date_production, #status", function () {
    refreshProduction();
});

$(document).on("click", ".btn-clear", function () {
    $("#date_production").val("");
    $("#status").val("");
    refreshProduction();
});

$(document).on("click", ".btn-save", function () {
    LoadingButton(this);
    $(".is-invalid").removeClass("is-invalid");
    var url = "/insertProduction";
    var valid = 1;
    var dt = $("#product_id").select2("data")[0];

    $("#addProduction .fill").each(function () {
        if (
            $(this).val() == null ||
            $(this).val() == "null" ||
            $(this).val() == ""
        ) {
            valid = -1;
            $(this).addClass("is-invalid");
        }
    });
    if (valid == -1) {
        notifikasi(
            "error",
            "Gagal Insert",
            "Silahkan cek kembali inputan anda",
        );
        ResetLoadingButton(
            ".btn-save",
            mode == 1 ? "Tambah Produksi" : "Update Produksi",
        );
        return false;
    }
    if (
        moment($("#production_date").val()).isAfter(
            moment().add(1, "days"),
            "day",
        )
    ) {
        $("#production_date").addClass("is-invalid");
        notifikasi(
            "error",
            "Gagal Insert",
            "Input tanggal maksimal 1 hari setelah hari ini",
        );
        ResetLoadingButton(
            ".btn-save",
            mode == 1 ? "Tambah Produksi" : "Update Produksi",
        );
        return false;
    }
    if (items.length == 0) {
        notifikasi("error", "Gagal Insert", "Harus ada 1 produk dipilih");
        ResetLoadingButton(
            ".btn-save",
            mode == 1 ? "Tambah Produksi" : "Update Produksi",
        );
        return false;
    }
    var missingRetailDestination = items.some(function (item) {
        return (
            parseInt(item.retail_unit || 0, 10) > 0 &&
            parseInt(item.unit_id || 0, 10) ===
                parseInt(item.retail_unit, 10) &&
            !parseInt(item.destination_warehouse_id || 0, 10)
        );
    });
    if (missingRetailDestination) {
        notifikasi(
            "error",
            "Gudang Tujuan Wajib",
            "Pilih gudang tujuan untuk setiap hasil produksi bersatuan eceran.",
        );
        ResetLoadingButton(
            ".btn-save",
            mode == 1 ? "Tambah Produksi" : "Update Produksi",
        );
        return false;
    }
    param = {
        production_date: $("#production_date").val(),
        production_desc: $("#production_desc").val(),
        detail: JSON.stringify(items),
        list_bahan: JSON.stringify(list_bahan),
        _token: token,
    };
    var revisionSourceId = $("#addProduction").attr(
        "revision_source_production_id",
    );
    if (revisionSourceId) {
        param.revision_source_production_id = revisionSourceId;
    }
    LoadingButton($(this));
    $.ajax({
        url: url,
        data: param,
        method: "post",
        headers: {
            "X-CSRF-TOKEN": token,
        },
        success: function (e) {
            ResetLoadingButton(
                ".btn-save",
                mode == 1 ? "Tambah Produksi" : "Update Produksi",
            );
            console.log(e.length);
            if (e.status == 0) {
                notifikasi("error", e.header, e.message);
                return false;
            } else if (e.status == -1) {
                notifikasi("error", "Stock Tidak Mencukupi", e.message);
                return false;
            }
            afterInsert();
        },
        error: function (a) {
            ResetLoadingButton(
                ".btn-save",
                mode == 1 ? "Tambah Produksi" : "Update Produksi",
            );
            console.log(a);
        },
    });
    /*
        // Cek stock supplies
        var qtyInput = $('#production_qty').val();
        var validQty = 1;
        var bahanKurang = [];
        $.ajax({
            url: "/getSupplies",
            method: "get",
            headers: {
                'X-CSRF-TOKEN': token
            },
            success:function(e){
                console.log(items[0])
                for (let i = 0; i < e.length; i++) {
                    items[0].forEach(element => {
                        if (e[i].supplies_id == element.supplies_id){
                            var need = element.bom_detail_qty * qtyInput;
                            console.log(need)
                            if (e[i].supplies_stock < need){
                                console.log('masuk')
                                validQty = -1;
                                bahanKurang.push(e[i].supplies_name);
                            }
                        }
                    });
                }

                if (validQty == -1){
                    notifikasi('error', "Stock Tidak Mencukupi", `Mohon cek stock ${bahanKurang.map(d => d).join(", ")}`);
                    ResetLoadingButton('.btn-save', mode == 1?"Tambah Produksi" : "Update Produksi");
                    return false;
                } else{

            },
            error:function(e){
                console.log(e)
            }
        })*/
});

function afterInsert() {
    items = [];
    $(".modal").modal("hide");
    if (mode == 1)
        notifikasi("success", "Berhasil Insert", "Berhasil Tambah Produksi");
    refreshProduction();
}

function addRow(e) {
    $("#tableProduct tbody").html("");
    e.forEach((element, index) => {
        console.log(element);

        let btnAct = `<a class="btn_delete_row_pr d-inline-flex align-items-center justify-content-center" href="javascript:void(0);" style="width: 28px; height: 28px; background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; border-radius: 6px; transition: all 0.2s ease;" title="Hapus Produk"><i class="fe fe-trash-2" style="font-size: 13px;"></i></a>`;
        if (mode == 3) {
            btnAct = `<a href="javascript:void(0);" class="btn_list_row d-inline-flex align-items-center justify-content-center" index="${index}" style="width: 28px; height: 28px; background: #f0f9ff; color: #0ea5e9; border: 1px solid #bae6fd; border-radius: 6px; transition: all 0.2s ease;" title="Lihat Daftar Bahan"><i class="fe fe-list" style="font-size: 13px;"></i></a>`;
        }

        $("#tableProduct tbody").append(`
                <tr class="row-product" data-index="${index}" data-id="${element.product_variant_id}" data-bom="${element.bom_id}">
                    <td style="font-weight: 600; color: #334155;">${element.product_name}</td>
                    <td class="text-center" style="font-weight: 700; color: #1e293b;">${formatRupiah(element.pd_qty)}</td>
                    <td style="color: #64748b;">${element.unit_name}</td>
                    <td><span class="badge" style="background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;padding:6px 10px;"><i class="fe fe-map-pin me-1"></i>${element.destination_warehouse_name || productionActiveWarehouseName()}</span></td>
                    <td class="text-center align-middle">
                        ${btnAct}
                    </td>
                </tr>
            `);
        modeBahan = 1;
        if (mode != 3) getBom(element.bom_id, index);
    });
}

$(document).on("click", ".btn-add-product", function () {
    $(".is-invalid").removeClass("is-invalid");
    $(".is-invalids").removeClass("is-invalids");
    var valid = 1;
    $("#addProduction .fill_product").each(function () {
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
        $("#product_id").val() == null ||
        $("#product_id").val() == "null" ||
        $("#product_id").val() == ""
    ) {
        valid = -1;
        $("#row-product .select2-selection--single").addClass("is-invalids");
    }
    if ($("#production_qty").val() <= 0) {
        valid = -1;
        $("#production_qty").addClass("is-invalid");
        notifikasi(
            "error",
            "Qty Tidak Valid",
            "Qty produksi harus lebih dari 0",
        );
        return false;
    }
    if (valid == -1) {
        notifikasi(
            "error",
            "Gagal Insert",
            "Silahkan cek kembali inputan anda",
        );
        return false;
    }

    var tempBom = $("#product_id").select2("data")[0];
    var isRetailOutput =
        parseInt(tempBom.retail_unit || 0, 10) > 0 &&
        parseInt($("#unit_id").val() || 0, 10) ===
            parseInt(tempBom.retail_unit, 10);
    if (
        isRetailOutput &&
        !parseInt($("#production_destination_warehouse_id").val() || 0, 10)
    ) {
        $("#production_destination_warehouse_id")
            .next(".select2-container")
            .find(".select2-selection")
            .addClass("is-invalid");
        notifikasi(
            "error",
            "Gudang Tujuan Wajib",
            "Pilih gudang eceran untuk hasil produksi bersatuan eceran.",
        );
        return false;
    }

    // Guard: blokir jika produk / varian tidak aktif
    if (
        tempBom &&
        (tempBom.product_status == 0 || tempBom.product_variant_status == 0)
    ) {
        var alasan = [];
        if (tempBom.product_status == 0)
            alasan.push("produk sudah tidak aktif");
        if (tempBom.product_variant_status == 0)
            alasan.push("varian produk sudah tidak aktif");
        notifikasi(
            "error",
            "Produk Tidak Aktif",
            "Tidak dapat produksi karena " +
                alasan.join(" & ") +
                ". Silakan hapus resep (BOM) ini di halaman Resep Bahan Mentah.",
        );
        return false;
    }

    if (bomDetailHasActiveUnits(tempBom)) {
        continueAddProduct(tempBom);
        return;
    }

    LoadingButton(".btn-add-product");
    loadBomForValidation(tempBom.bom_id, function (fullBom) {
        ResetLoadingButton(".btn-add-product", "+");
        if (!fullBom) {
            notifikasi(
                "error",
                "Gagal Memuat Resep",
                "Tidak dapat memuat detail resep. Silakan coba lagi.",
            );
            return;
        }
        continueAddProduct(fullBom);
    });
});

$(document).on("click", ".btn_delete_row_pr", function () {
    let row = $(this).closest("tr");
    let index = parseInt(row.data("index"), 10);
    items.splice(index, 1);
    list_bahan.splice(index, 1);

    console.log(items);
    addRow(items);
});

$(document).on("click", ".btn_view", function () {
    var data = $("#tableProduction")
        .DataTable()
        .row($(this).parents("tr"))
        .data(); //ambil data dari table
    console.log(data);
    resetProductionApprovalActions();
    mode = 3;
    modeBahan = 1;
    items = [];
    list_bahan = [];
    $("#addProduction .modal-title").html("Detail Produksi");
    $("#addProduction input").val("");
    $("#product_id").empty();
    $("#production_qty").val("");
    $("#tableProduct tr.row-product").remove();
    $(".is-invalid").removeClass("is-invalid");
    $("#unit_id").html("");
    $("#production_date").val(data.production_date);
    $("#production_desc").val(data.production_desc).attr("disabled", true);

    var total_dos = 0;

    data.items.forEach((e) => {
        var temp = {
            pd_id: e.pd_id,
            product_variant_id: e.product_variant_id,
            product_name: e.product_name,
            pd_qty: e.pd_qty,
            unit_name: e.unit_name,
            unit_id: e.unit_id,
            retail_unit: e.retail_unit,
            default_unit: e.default_unit,
            destination_warehouse_id: e.destination_warehouse_id,
            destination_warehouse_name: e.destination_warehouse_name,
            bom_id: e.bom_id,
        };
        items.push(temp);

        list_bahan.push(e.list_bahan);

        if (e.unit_name.toUpperCase().includes("DOS")) {
            total_dos += e.pd_qty;
        }
    });
    console.log(items);
    console.log(list_bahan);
    addRow(items);
    $("#total_dos").html(formatRupiah(data.total_dos));
    var approvalAction = null;
    if (
        !moment(data.production_date).isBefore(
            moment().subtract(3, "days").format("YYYY-MM-DD"),
        )
    ) {
        if (data.status == 1) {
            approvalAction = "production";
        } else if (data.status == 4) {
            approvalAction = "cancellation";
        }
    }
    $("#addProduction").data("approval-action", approvalAction);
    showProductionApprovalActions(approvalAction, data.production_id);

    $(".is-invalid").removeClass("is-invalid");
    $(".input_table, .add, .btn_delete_row_pr").hide();
    setProductionSaveVisible(false);
    if (approvalAction) {
        setProductionModalMode("confirm");
        $("#addProduction .modal-title").html(
            approvalAction === "cancellation"
                ? "Konfirmasi Pembatalan Produksi"
                : "Konfirmasi Produksi",
        );
    } else {
        setProductionModalMode("form");
        $("#addProduction .modal-title").html("Detail Produksi");
    }
    $(".dos").show();
    $(".btn-cancel").html("Batal");
    $("#production_date").prop("disabled", true);
    $("#addProduction").attr("production_id", data.production_id);
    $("#addProduction").removeAttr("revision_source_production_id");
    $("#addProduction").modal("show");
});

$(document).on("click", ".btn_list_row", function () {
    $("#addProduction").modal("hide");
    $("#modalBahan").modal("show");
    let row = $(this).closest("tr").data("bom");
    let index = $(this).attr("index");
    $(".btn-save-bahan").attr("index", index);
    modeBahan = 2;
    getBom(row, index);
    if (mode == 3) $(".btn-save-bahan").hide();
    else $(".btn-save-bahan").show();
});

$(document).on("click", ".btn-close-bahan", function () {
    $("#addProduction").modal("show");
    $("#modalBahan").modal("hide");
    if (mode === 3) {
        showProductionApprovalActions(
            $("#addProduction").data("approval-action"),
            $("#addProduction").attr("production_id"),
        );
    }
});

function getBom(id, index = null) {
    // kalau index sudah ada, maka akan balik
    if (modeBahan == 1 && list_bahan[index] !== undefined) {
        return;
    }

    $.ajax({
        url: "/getBom",
        method: "get",
        data: { bom_id: id },
        success: function (e) {
            console.log(e);
            if (modeBahan == 1) {
                var temp = [];
                e[0].details.forEach((detail) => {
                    temp.push(detail.supplies_id);
                });
                list_bahan[index] = temp;
            } else if (modeBahan == 2) {
                $("#tableSupplies tbody").html("");

                let current_list = list_bahan[index];
                // 1. Pastikan current_list jadi array murni (handle JSON string dari DB)
                if (typeof current_list === "string") {
                    try {
                        current_list = JSON.parse(current_list);
                    } catch (e) {
                        current_list = [];
                    }
                }

                e[0].details.forEach((b) => {
                    let isChecked = false;
                    if (Array.isArray(current_list)) {
                        // Gunakan parseInt untuk memastikan perbandingan angka benar
                        isChecked = current_list.some(
                            (id) => parseInt(id) == parseInt(b.supplies_id),
                        );
                    }
                    let isDisabled = mode == 3 ? "disabled" : "";

                    $("#tableSupplies tbody").append(`
                            <tr class="row-bahan" style="border-bottom: 1px solid #f1f5f9;">
                                <td class="text-center" style="vertical-align: middle;">
                                    <input type="checkbox" ${isChecked ? "checked" : ""} ${isDisabled}
                                    class="form-check-input chk" supplies_id="${b.supplies_id}" style="width: 18px; height: 18px; cursor: pointer; border-radius: 4px;" />
                                </td>
                                <td style="font-weight: 600; color: #475569;">${b.supplies_name}</td>
                            </tr>
                        `);
                });
            }
            console.log(list_bahan);
        },
    });
}

$(document).on("click", ".btn-save-bahan", function () {
    var index = parseInt($(this).attr("index"));

    // Ambil semua id dari checkbox yang HANYA ada di tabel modal saat ini
    var temp = $("#tableSupplies tbody .chk:checked")
        .map(function () {
            return parseInt($(this).attr("supplies_id"));
        })
        .get();

    var valid = 1;
    LoadingButton(".btn-save-bahan");

    if (temp.length === 0) {
        valid = -1;
    } else {
        list_bahan[index] = temp;
    }

    if (valid == -1) {
        notifikasi("error", "Gagal Insert", "Mohon input minimal 1 bahan");
        ResetLoadingButton(".btn-save-bahan", "Simpan Perubahan");
        return false;
    }

    $("#modalBahan").modal("hide");
    $("#addProduction").modal("show");
    modeBahan = 1;
    notifikasi("success", "Berhasil Simpan", "Berhasil Simpan Detail Bahan");
    ResetLoadingButton(".btn-save-bahan", "Simpan Perubahan");
});

//delete
$(document).on("click", ".btn_delete", function () {
    $("#modalDelete .modal-body #delete_reason").remove();
    var tbId = $(this).closest("table").attr("id");
    var data = $("#" + tbId)
        .DataTable()
        .row($(this).parents("tr"))
        .data(); //ambil data dari table
    showModalDelete(
        "Apakah yakin ingin batalkan produksi ini?",
        "btn-delete-production",
    );
    $("#modalDelete .modal-body").append(
        `<textarea class="form-control mt-2" id="delete_reason" placeholder="Alasan pembatalan produksi..." rows="3"></textarea>`,
    );
    $("#btn-delete-production").html("Batal Produksi");
    $("#btn-delete-production").attr("production_id", data.production_id);
});

$(document).on("click", "#btn-delete-production", function () {
    LoadingButton(this);
    $(".is-invalid").removeClass("is-invalid");
    console.log($("#delete_reason").val());

    LoadingButton(this);
    $.ajax({
        url: "/deleteProduction",
        data: {
            production_id: $("#btn-delete-production").attr("production_id"),
            delete_reason: $("#delete_reason").val(),
            _token: token,
        },
        method: "post",
        success: function (e) {
            $("#modalDelete .modal-body").html(
                `<p id="text-delete" style="font-size:10pt"></p>`,
            );
            ResetLoadingButton(".btn-konfirmasi", "Batal Produksi");
            $(".modal").modal("hide");
            afterInsert();
            notifikasi(
                "success",
                "Berhasil Batalkan",
                "Berhasil batalkan produksi",
            );
        },
        error: function (e) {
            ResetLoadingButton(".btn-konfirmasi", "Batal Produksi");
            console.log(e);
        },
    });
});

//konfirmasi acc
$(document).on("click", ".btn_acc", function () {
    // var tbId = $(this).closest("table").attr("id");
    // var data = $("#" + tbId)
    //     .DataTable()
    //     .row($(this).parents("tr"))
    //     .data(); //ambil data dari table
    var production_id = $(this).attr("production_id");
    $(".modal").modal("hide");
    showModalDelete(
        "Apakah yakin ingin Approve pembatalan produksi ini?",
        "btn-acc-delete-production",
    );
    $("#btn-acc-delete-production").attr("production_id", production_id);
    $(".btn-konfirmasi").html("Batal Produksi");
});

$(document).on("click", "#btn-acc-delete-production", function () {
    LoadingButton(this);
    $.ajax({
        url: "/accDeleteProduction",
        data: {
            production_id: $("#btn-acc-delete-production").attr(
                "production_id",
            ),
            _token: token,
        },
        method: "post",
        success: function (e) {
            $("#modalDelete .modal-body").html(
                `<p id="text-delete" style="font-size:10pt"></p>`,
            );
            ResetLoadingButton(".btn-konfirmasi", "Batal Produksi");
            $(".modal").modal("hide");
            if (e.status == -1) {
                notifikasi("error", "Stok Tidak Mencukupi", e.message);
                return false;
            }
            if (e.status == -2) {
                notifikasi(
                    "error",
                    e.header || "Stok Tidak Mencukupi",
                    e.message,
                );
                if (e.header) {
                    refreshProduction();
                }
                return false;
            }
            afterInsert();
            notifikasi(
                "success",
                "Berhasil Approve",
                "Berhasil approve pembatalan produksi",
            );
        },
        error: function (e) {
            ResetLoadingButton(".btn-konfirmasi", "Batal Produksi");
            console.log(e);
        },
    });
});

//konfirmasi acc
$(document).on("click", ".btn_cancel", function () {
    // var tbId = $(this).closest("table").attr("id");
    // var data = $("#" + tbId)
    //     .DataTable()
    //     .row($(this).parents("tr"))
    //     .data(); //ambil data dari table
    var production_id = $(this).attr("production_id");
    $(".modal").modal("hide");
    showModalKonfirmasi(
        "Apakah yakin ingin Tolak pembatalan produksi ini?",
        "btn-cancel-delete-production",
    );
    $(".btn-konfirmasi").html("Konfirmasi Batal Produksi");
    $("#btn-cancel-delete-production").attr("production_id", production_id);
});

$(document).on("click", "#btn-cancel-delete-production", function () {
    LoadingButton(this);
    $.ajax({
        url: "/tolakDeleteProduction",
        data: {
            production_id: $("#btn-cancel-delete-production").attr(
                "production_id",
            ),
            _token: token,
        },
        method: "post",
        success: function (e) {
            $("#modalDelete .modal-body").html(
                `<p id="text-delete" style="font-size:10pt"></p>`,
            );
            ResetLoadingButton(".btn-konfirmasi", "Konfirmasi Batal Produksi");
            $(".modal").modal("hide");
            afterInsert();
            notifikasi(
                "success",
                "Berhasil Tolak",
                "Berhasil tolak pembatalan produksi",
            );
        },
        error: function (e) {
            ResetLoadingButton(".btn-konfirmasi", "Konfirmasi Batal Produksi");
            console.log(e);
        },
    });
});

$(document).on("click", ".btn_acc_produksi", function () {
    // var data = $('#tableProduction').DataTable().row($(this).parents('tr')).data();//ambil data dari table
    var production_id = $(this).attr("production_id");
    $(".modal").modal("hide");
    showModalKonfirmasi(
        "Apakah yakin ingin Approve produksi ini?",
        "btn-accept-production",
    );
    $("#btn-accept-production").attr("production_id", production_id);
    $(".btn-konfirmasi").html("Konfirmasi");
});

// Dipakai baik oleh konfirmasi approve awal maupun konfirmasi "buat baris stok baru" di bawah
// — accProduction bisa membalas status:-3 kalau ada satuan ladder yang baris ProductStock-nya
// belum ada, sebelum mengubah apa pun. confirmCreateStock=true dikirim setelah user setuju.
function submitAccProduction(productionId, confirmCreateStock) {
    LoadingButton($(".btn-konfirmasi"));
    $.ajax({
        url: "/accProduction",
        data: {
            production_id: productionId,
            confirm_create_stock: confirmCreateStock ? 1 : 0,
            _token: token,
        },
        method: "post",
        success: function (e) {
            var success = e === 1 || (typeof e === "object" && e.status == 1);
            if (!success) {
                if (typeof e === "object") {
                    if (e.status == -3) {
                        // Perlu konfirmasi tambahan: ada baris stok yang belum ada dan akan
                        // dibuat dengan stok awal 0 kalau user melanjutkan.
                        ResetLoadingButton(".btn-konfirmasi", "Konfirmasi");
                        showModalKonfirmasi(
                            e.message,
                            "btn-confirm-create-stock-production",
                        );
                        $("#btn-confirm-create-stock-production").attr(
                            "production_id",
                            productionId,
                        );
                        $(".btn-konfirmasi").html("Konfirmasi");
                        return false;
                    }
                    notifikasi("error", e.header, e.message);
                    if (e.status == -2) {
                        $(".modal").modal("hide");
                        refreshProduction();
                    }
                    ResetLoadingButton(".btn-konfirmasi", "Konfirmasi");
                    return false;
                } else {
                    ResetLoadingButton(".btn-konfirmasi", "Konfirmasi");
                    notifikasi(
                        "error",
                        "Gagal Update",
                        "Stock Product yang tidak mencukupi : " + e,
                    );
                }
            } else {
                ResetLoadingButton(".btn-konfirmasi", "Konfirmasi");
                refreshProduction();
                $(".modal").modal("hide");
                notifikasi(
                    "success",
                    "Berhasil Terima",
                    (e && e.message) || "Stock Transfer hasil produksi dibuat",
                );
            }
        },
        error: function (e) {
            console.log(e);
            ResetLoadingButton(".btn-konfirmasi", "Konfirmasi");
        },
    });
}

$(document).on("click", "#btn-accept-production", function () {
    // Baca lewat selector id (bukan $(this)) — .btn-konfirmasi juga dipakai tombol lain
    // (mis. #modalDelete) sehingga showModalKonfirmasi() bisa menaruh id yang sama di lebih
    // dari satu elemen; supaya konsisten dengan tempat penulisannya (juga lewat selector id),
    // pembacaan production_id ikut lewat selector id juga.
    submitAccProduction(
        $("#btn-accept-production").attr("production_id"),
        false,
    );
});

$(document).on("click", "#btn-confirm-create-stock-production", function () {
    submitAccProduction(
        $("#btn-confirm-create-stock-production").attr("production_id"),
        true,
    );
});

$(document).on("click", ".btn_decline_produksi", function () {
    // var data = $('#tableProduction').DataTable().row($(this).parents('tr')).data();//ambil data dari table
    var production_id = $(this).attr("production_id");
    $(".modal").modal("hide");
    showModalDelete(
        "Apakah yakin ingin tolak produksi ini?",
        "btn-decline-production",
    );
    $("#btn-decline-production").attr("production_id", production_id);
    $(".btn-konfirmasi").html("Konfirmasi");
});

$(document).on("click", "#btn-decline-production", function () {
    LoadingButton(this);
    $.ajax({
        url: "/declineProduction",
        data: {
            production_id: $("#btn-decline-production").attr("production_id"),
            _token: token,
        },
        method: "post",
        success: function (e) {
            ResetLoadingButton(".btn-konfirmasi", "Konfirmasi");
            $(".modal").modal("hide");
            if (e.status == -2) {
                notifikasi("error", e.header, e.message);
                refreshProduction();
                return false;
            }
            refreshProduction();
            notifikasi("success", "Berhasil Tolak", "Berhasil Tolak Pengajuan");
        },
        error: function (e) {
            console.log(e);
            ResetLoadingButton(".btn-konfirmasi", "Konfirmasi");
        },
    });
});

$(document).on("click", ".btn-prev", function () {
    var index = parseInt($("#fotoProduksiImage").attr("index"));
    if (index > 0) {
        index -= 1;
        $("#fotoProduksiImage").attr(
            "src",
            public + list_photo[index].pp_photo,
        );
        $("#fotoProduksiImage").attr("index", index);
        $("#btn_download_photo").attr(
            "href",
            public + list_photo[index].pp_photo,
        );
    }
});
$(document).on("click", ".btn-next", function () {
    var index = parseInt($("#fotoProduksiImage").attr("index"));
    if (index < list_photo.length - 1) {
        index += 1;
        $("#fotoProduksiImage").attr(
            "src",
            public + list_photo[index].pp_photo,
        );
        $("#fotoProduksiImage").attr("index", index);
        $("#btn_download_photo").attr(
            "href",
            public + list_photo[index].pp_photo,
        );
    }
});

$(document).on("click", ".LihatfotoProduksi", function () {
    list_photo = [];
    $("#fotoProduksiImage").attr("src", public + "no_img.png");
    $.ajax({
        url: "/getFotoProduksi",
        data: {
            pp_date: $("#date_production").val(),
            _token: token,
        },
        method: "get",
        success: function (e) {
            console.log(e);

            if (e.length > 0) {
                list_photo = e;
                $("#modalViewPhoto .modal-footer").show();
                $("#fotoProduksiImage").attr("src", public + e[0].pp_photo);
                $("#fotoProduksiImage").attr("index", 0);
                $("#btn_download_photo").attr("href", public + e[0].pp_photo);
            } else {
                $("#modalViewPhoto .modal-footer").hide();
            }
            $("#modalViewPhoto").modal("show");
        },
        error: function (e) {
            console.log(e);
        },
    });
});
