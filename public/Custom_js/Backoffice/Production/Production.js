function initProductionFormAutocompletes() {
    if (typeof autocompleteBom !== "function") {
        return;
    }
    autocompleteBom("#addProduction #product_id", "body");
}

function getSelect2FirstData($el) {
    if (!$el || !$el.length || !$el.hasClass("select2-hidden-accessible")) {
        return null;
    }
    var data = $el.select2("data");
    return data && data.length ? data[0] : null;
}

$(document).on("shown.bs.modal", "#addProduction", initProductionFormAutocompletes);

autocompleteSupplies("#fix_recipe_supplies_id", "#fixRecipeBom .modal-content");
var mode = 1; // 1 = insert; 2 = edit; 3 = view
var modeBahan = 1;
var table;
var items = [];
var list_photo = [];
var list_bahan = [];
var productionDraftPreserved = false;
var fixRecipeBahan = [];
var fixRecipeBomId = null;
var fixRecipeReturnToProduction = false;
var fixRecipeOpenSeq = 0;
/** Stash when + Tambah gagal karena satuan resep; di-retry setelah Update Resep sukses. */
var pendingProductionAdd = null;

function productionMainWarehouseName() {
    if (typeof getMainWarehouseName === "function") {
        var main = getMainWarehouseName();
        if (main) return main;
    }
    return "Gudang utama";
}

/** Gudang tujuan non-eceran: aktif jika gudang aktif = utama, else gudang utama pertama. */
function productionNonRetailDestinationName() {
    if (typeof isActiveMainWarehouse === "function" && isActiveMainWarehouse() === true) {
        var activeName = productionActiveWarehouseName();
        if (activeName) return activeName;
    }
    return productionMainWarehouseName();
}

function productionNonRetailDestinationId() {
    if (typeof isActiveMainWarehouse === "function" && isActiveMainWarehouse() === true) {
        if (typeof getActiveWarehouseId === "function") {
            var activeId = parseInt(getActiveWarehouseId() || 0, 10);
            if (activeId > 0) return activeId;
        }
        var wh = window.activeWarehouse || {};
        var fromWindow = parseInt(wh.id || wh.warehouse_id || 0, 10);
        if (fromWindow > 0) return fromWindow;
    }
    if (typeof getMainWarehouseId === "function") {
        var mainId = parseInt(getMainWarehouseId() || 0, 10);
        if (mainId > 0) return mainId;
    }
    return 0;
}

function productionActiveWarehouseName() {
    // Live dari top-bar (footer-scripts), lalu window.activeWarehouse, lalu fallback
    if (typeof getActiveWarehouseName === "function") {
        var live = getActiveWarehouseName();
        if (live) return live;
    }
    var wh = window.activeWarehouse || {};
    return wh.name || wh.warehouse_name || "Gudang utama aktif";
}

function syncProductionDestinationControl() {
    var $product = $("#addProduction #product_id");
    var product = getSelect2FirstData($product) || {};
    var isRetail =
        parseInt(product.retail_unit || 0, 10) > 0 &&
        parseInt($("#unit_id").val() || 0, 10) ===
            parseInt(product.retail_unit, 10);
    var $badge = $("#production-main-warehouse-badge");
    var $dest = $("#addProduction #production_destination_warehouse_id");
    var $destSelect2 = $dest.next(".select2-container");

    $badge.find("span").text(
        isRetail ? productionActiveWarehouseName() : productionNonRetailDestinationName(),
    );

    if (isRetail) {
        // Satuan eceran → pilih gudang eceran (d-none, bukan .hide — badge pakai d-flex !important)
        $badge.addClass("d-none").removeClass("d-flex");
        if (
            typeof autocompleteWarehouse === "function" &&
            !$dest.hasClass("select2-hidden-accessible")
        ) {
            autocompleteWarehouse(
                "#addProduction #production_destination_warehouse_id",
                "body",
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
        if ($dest.hasClass("select2-hidden-accessible")) {
            $dest.val(null).trigger("change");
            $dest.select2("destroy");
        } else {
            $dest.val(null);
        }
        if ($destSelect2.length) {
            $destSelect2.hide();
        }
        $dest.hide();
        $badge.removeClass("d-none").addClass("d-flex");
    }
}

function resetProductionProductUnitSelect() {
    $("#unit_id").html("");
    $("#unit_id").append("<option selected>Pilih Satuan</option>");
    $("#production_pallet_hint").text("");
    syncProductionDestinationControl();
}

function clearProductionProductSelect() {
    var $product = $("#addProduction #product_id");
    if ($product.hasClass("select2-hidden-accessible")) {
        $product.val(null).trigger("change");
        return;
    }
    $product.empty();
    resetProductionProductUnitSelect();
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
    }
    if (visible) {
        $btn.removeClass("d-none").addClass("d-inline-flex");
    } else {
        $btn.removeClass("d-inline-flex").addClass("d-none");
    }
}

function setProductionModalMode(kind) {
    var $modal = $("#addProduction");
    var $icon = $modal.find(".pg-modal-icon i");
    $modal.removeClass("pg-modal--form pg-modal--confirm");
    if (kind === "confirm") {
        $modal.addClass("pg-modal--confirm");
        $icon.attr("class", "fe fe-check-circle");
    } else {
        $modal.addClass("pg-modal--form");
        $icon.attr("class", "fe fe-layers");
    }
}

function showProductionApprovalActions(action, productionId, opts) {
    resetProductionApprovalActions();
    if (!hasAccessAction("Produksi", "others")) {
        return;
    }

    opts = opts || {};
    var $accept = $("#addProduction #btn-terima");
    var $decline = $("#addProduction #btn-tolak");
    if (action === "production") {
        $accept.addClass("btn_acc_produksi").html('<i class="fe fe-check-circle me-1"></i>Terima Produksi');
        $decline.addClass("btn_decline_produksi").html('<i class="fe fe-x me-1"></i>Tolak');
    } else if (action === "cancellation") {
        $accept.addClass("btn_acc").html('<i class="fe fe-check-circle me-1"></i>Terima Pembatalan');
        $decline.addClass("btn_cancel").html('<i class="fe fe-x me-1"></i>Tolak');
    } else {
        return;
    }

    $accept
        .add($decline)
        .attr("production_id", productionId)
        .removeClass("d-none");
    // ST sudah dikirim: sembunyikan Terima Pembatalan (backend tetap menolak); biarkan Tolak
    // supaya bisa lepas dari stuck Menunggu Batal.
    if (action === "cancellation" && opts.hideAccept) {
        $accept.addClass("d-none").removeAttr("production_id");
    }
    setProductionModalMode("confirm");
}

$("#addProduction").on("hidden.bs.modal", function () {
    if (
        productionDraftPreserved ||
        $("#modalBahan").hasClass("show") ||
        $("#fixRecipeBom").hasClass("show")
    ) {
        return;
    }
    resetProductionApprovalActions();
    setProductionModalMode("form");
    setProductionSaveVisible(true, "Simpan");
    $(this)
        .removeAttr("production_id revision_source_production_id")
        .removeData("approval-action");
});

function getTodayStr() {
    let today = new Date();
    let yyyy = today.getFullYear();
    let mm = String(today.getMonth() + 1).padStart(2, "0");
    let dd = String(today.getDate()).padStart(2, "0");
    return yyyy + "-" + mm + "-" + dd;
}

function getProductionDateMaxStr() {
    return moment().add(1, "days").format("YYYY-MM-DD");
}

/** Tanggal produksi: min hari ini, max +1 hari. Past dates disabled di picker. */
function syncProductionDateField(opts) {
    opts = opts || {};
    var today = getTodayStr();
    var $date = $("#production_date");
    $date.attr("min", today).attr("max", getProductionDateMaxStr());
    if (opts.value) {
        $date.val(opts.value);
    } else if (!$date.val() || moment($date.val()).isBefore(today, "day")) {
        $date.val(today);
    }
    if (opts.disabled === true) {
        $date.prop("disabled", true);
    } else if (opts.disabled === false) {
        $date.prop("disabled", false);
    }
}

function isProductionDateValid(dateStr) {
    if (!dateStr) return false;
    var m = moment(dateStr, "YYYY-MM-DD", true);
    if (!m.isValid()) {
        m = moment(dateStr);
    }
    if (!m.isValid()) return false;
    if (m.isBefore(moment().startOf("day"))) return false;
    if (m.isAfter(moment().add(1, "days"), "day")) return false;
    return true;
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
        url: "/getProductionBom",
        method: "get",
        data: { bom_id: bomId, with_details: 1 },
        success: function (response) {
            callback(response && response[0] ? response[0] : null);
        },
        error: function (xhr) {
            if (handlePermissionError(xhr)) return;
            callback(null);
        },
    });
}

function isRecipeNeedsUpdateError(payload) {
    if (!payload) {
        return false;
    }
    if (payload.code === "recipe_needs_update") {
        return true;
    }
    var msg = String(payload.message || "");
    return msg.indexOf("Perbarui resep terlebih dahulu") !== -1;
}

/**
 * Tangani response error validasi produksi (status != 1) dari
 * checkProductionStock()/insertProduction() - dipakai baik saat menambah
 * baris produk (tiap klik +) maupun submit akhir (Tambah/Update Produksi),
 * supaya penanganannya konsisten di kedua titik. Lihat GitHub #101.
 */
function handleProductionValidationError(e, bomIdFallback) {
    if (!e) {
        showPgErrorModal("Gagal", "Terjadi kesalahan yang tidak diketahui.");
        return;
    }
    if (e.status == 0) {
        if (isRecipeNeedsUpdateError(e)) {
            var bomId = e.bom_id || bomIdFallback || null;
            if (bomId) {
                promptRecipeNeedsUpdate(bomId, {
                    returnToProduction: true,
                    title: e.header || "Satuan Resep Tidak Aktif",
                    message: e.message,
                });
                return;
            }
        }
        showPgErrorModal(e.header, e.message);
        return;
    }
    if (e.status == -1) {
        showPgErrorModal("Stock Tidak Mencukupi", e.message);
        return;
    }
    showPgErrorModal("Gagal", e.message || "Terjadi kesalahan.");
}

/**
 * Alert + swap: hide Tambah Produksi, open Update Resep, show brief error Swal.
 * Tutup/dismiss hanya menutup Swal — Update Resep tetap terbuka; restore produksi
 * lewat footer Batal/X di modal Update Resep.
 */
function promptRecipeNeedsUpdate(bomId, options) {
    options = options || {};
    var returnToProduction = options.returnToProduction !== false;
    var title = options.title || "Satuan Resep Tidak Aktif";
    var message =
        options.message ||
        "Satuan bahan pada resep sudah tidak aktif / tidak sinkron. Perbarui resep terlebih dahulu.";
    if (options.invalidLabels && options.invalidLabels.length) {
        message =
            "Satuan bahan pada resep sudah tidak aktif. Perbarui resep terlebih dahulu: " +
            options.invalidLabels.join(", ");
    }

    if (bomId) {
        openFixRecipeBom(bomId, { returnToProduction: returnToProduction });
    }

    Swal.fire({
        icon: "error",
        iconColor: "#ef4444",
        title: title,
        text: message,
        confirmButtonText: "Tutup",
        customClass: {
            confirmButton: "pg-btn-confirm pg-btn-confirm--danger",
            title: "fw-bold fs-4 text-dark",
            popup: "rounded-4",
        },
        buttonsStyling: false,
    });
}

function hideProductionPreserveDraft() {
    productionDraftPreserved = true;
    $("#addProduction").modal("hide");
}

function stashPendingProductionAdd(tempBom) {
    var product = tempBom || ($("#product_id").select2("data")[0] || {});
    var isRetailOutput =
        parseInt(product.retail_unit || 0, 10) > 0 &&
        parseInt($("#unit_id").val() || 0, 10) === parseInt(product.retail_unit, 10);
    var destData = $("#production_destination_warehouse_id").hasClass(
        "select2-hidden-accessible",
    )
        ? $("#production_destination_warehouse_id").select2("data")[0] || {}
        : {};
    pendingProductionAdd = {
        bom_id: product.bom_id || null,
        product_variant_id: product.product_variant_id || null,
        qty: $("#production_qty").val(),
        unit_id: $("#unit_id").val(),
        destination_warehouse_id: isRetailOutput
            ? $("#production_destination_warehouse_id").val() || null
            : productionNonRetailDestinationId() || null,
        destination_warehouse_name: isRetailOutput
            ? destData.text || productionActiveWarehouseName()
            : productionNonRetailDestinationName(),
    };
}

function clearPendingProductionAdd() {
    pendingProductionAdd = null;
}

function restoreProductionDraft(onShown) {
    productionDraftPreserved = false;
    fixRecipeReturnToProduction = false;
    var showProduction = function () {
        if (typeof onShown === "function") {
            $("#addProduction").one("shown.bs.modal", onShown);
        }
        $("#addProduction").modal("show");
    };
    if ($("#fixRecipeBom").hasClass("show")) {
        $("#fixRecipeBom")
            .one("hidden.bs.modal", showProduction)
            .modal("hide");
    } else {
        showProduction();
    }
}

function getActiveSuppliesUnitsFix(units) {
    if (!Array.isArray(units)) {
        return [];
    }
    return units
        .map(function (unit) {
            return {
                unit_id: unit.unit_id,
                unit_name: unit.unit_name || unit.unit_short_name || "",
                unit_short_name: unit.unit_short_name || unit.unit_name || "",
                status: unit.status,
            };
        })
        .filter(function (unit) {
            return (
                unit.status === undefined ||
                unit.status === null ||
                parseInt(unit.status, 10) === 1
            );
        });
}

function isUnitInActiveListFix(unitId, activeUnits) {
    return activeUnits.some(function (unit) {
        return String(unit.unit_id) === String(unitId);
    });
}

function renderFixRecipeProductUnitInfo(relasi, currentUnit, prUnits) {
    var $el = $("#fix_recipe_product_unit_info");
    var activeRelasi = (relasi || []).filter(function (r) {
        return (
            r.status === undefined ||
            r.status === null ||
            parseInt(r.status, 10) === 1
        );
    });

    if (activeRelasi.length === 0) {
        $el.html(
            '<span class="text-muted">Saat ini: <strong class="text-success">' +
                (currentUnit || "-") +
                "</strong></span>" +
                " &nbsp;|&nbsp; " +
                '<span class="text-muted">Default: <strong class="text-success">' +
                (currentUnit || "-") +
                "</strong></span>",
        ).show();
        return;
    }

    var unitId1List = activeRelasi.map(function (r) {
        return String(r.pr_unit_id_1 || r.su_id_1 || "");
    });
    var allUnitIds = (prUnits || []).map(function (u) {
        return String(u.unit_id);
    });
    var candidates = allUnitIds.filter(function (uid) {
        return !unitId1List.includes(uid);
    });
    var smallestName = "-";
    if (candidates.length > 0) {
        var found = prUnits.find(function (u) {
            return String(u.unit_id) === candidates[0];
        });
        if (found) {
            smallestName = found.unit_name || found.unit_short_name || "-";
        }
    } else {
        var smallest = activeRelasi[activeRelasi.length - 1];
        smallestName = smallest.pr_unit_name_2 || smallest.pr_unit_id_2 || "-";
    }
    var same =
        (currentUnit || "").trim().toLowerCase() ===
        (smallestName || "").trim().toLowerCase();
    var colorClass = same ? "text-success" : "text-danger";
    $el.html(
        '<span class="text-muted">Saat ini: <strong class="' +
            colorClass +
            '">' +
            (currentUnit || "-") +
            "</strong></span>" +
            " &nbsp;|&nbsp; " +
            '<span class="text-muted">Terkecil: <strong class="' +
            colorClass +
            '">' +
            smallestName +
            "</strong></span>",
    ).show();
}

function renderFixRecipeSuppliesUnitInfo(suppliesRelasi, activeUnits, defaultUnitId) {
    var $el = $("#fix_recipe_supplies_unit_info");
    var units = activeUnits || [];
    var activeRelasi = (suppliesRelasi || []).filter(function (r) {
        return (
            r.status === undefined ||
            r.status === null ||
            parseInt(r.status, 10) === 1
        );
    });

    if (activeRelasi.length > 0) {
        var unitId1List = activeRelasi.map(function (r) {
            return String(r.pr_unit_id_1 || r.su_id_1 || "");
        });
        var allUnitIds = units.map(function (u) {
            return String(u.unit_id);
        });
        var candidates = allUnitIds.filter(function (uid) {
            return !unitId1List.includes(uid);
        });
        var smallestName = "-";
        if (candidates.length > 0) {
            var found = units.find(function (u) {
                return String(u.unit_id) === candidates[0];
            });
            if (found) {
                smallestName = found.unit_name || found.unit_short_name || "-";
            }
        }
        $el.html(
            '<span class="text-muted">Terkecil: <strong class="text-dark">' +
                smallestName +
                "</strong></span>",
        ).show();
        return;
    }

    var defaultUnit = "-";
    if (defaultUnitId) {
        var defFound = units.find(function (u) {
            return String(u.unit_id) === String(defaultUnitId);
        });
        if (defFound) {
            defaultUnit = defFound.unit_name || defFound.unit_short_name || "-";
        }
    } else if (units.length > 0) {
        defaultUnit = units[0].unit_name || units[0].unit_short_name || "-";
    }
    $el.html(
        '<span class="text-muted">Default: <strong class="text-dark">' +
            defaultUnit +
            "</strong></span>",
    ).show();
}

function buildFixRecipeUnitSelect(item, index) {
    var activeUnits = getActiveSuppliesUnitsFix(
        item.active_units || item.units || [],
    );
    var currentUnitId = item.current_unit_id || item.unit_id;
    var currentUnitName = item.current_unit_name || item.unit_name || "-";
    var currentInActive = isUnitInActiveListFix(currentUnitId, activeUnits);
    var selectedUnitId = item.unit_id || currentUnitId;

    var options = activeUnits
        .map(function (unit) {
            var selected =
                String(unit.unit_id) === String(selectedUnitId) ? "selected" : "";
            var label = unit.unit_name || unit.unit_short_name || unit.unit_id;
            return (
                '<option value="' +
                unit.unit_id +
                '" ' +
                selected +
                ">" +
                label +
                "</option>"
            );
        })
        .join("");

    var placeholder = "";
    var selectClass = "form-select form-select-sm fix-recipe-row-unit";
    if (!currentInActive && activeUnits.length > 0) {
        placeholder = '<option value="">Pilih satuan aktif</option>';
    } else {
        selectClass += " fix-recipe-fill";
    }

    var suppliesRelasi = item.supplies_relasi || [];
    var activeRelasi = suppliesRelasi.filter(function (r) {
        return (
            r.status === undefined ||
            r.status === null ||
            parseInt(r.status, 10) === 1
        );
    });
    var comparisonUnitId = null;
    var comparisonLabel = "";
    var comparisonName = "-";

    if (activeRelasi.length > 0) {
        var unitId1List = activeRelasi.map(function (r) {
            return String(r.pr_unit_id_1 || r.su_id_1 || "");
        });
        var allUnitIds = activeUnits.map(function (u) {
            return String(u.unit_id);
        });
        var candidates = allUnitIds.filter(function (uid) {
            return !unitId1List.includes(uid);
        });
        if (candidates.length > 0) {
            var found = activeUnits.find(function (u) {
                return String(u.unit_id) === candidates[0];
            });
            if (found) {
                comparisonUnitId = String(found.unit_id);
                comparisonName = found.unit_name || found.unit_short_name || "-";
            }
        }
        comparisonLabel = "Terkecil";
    } else {
        var defId = item.supplies_default_unit;
        if (defId) {
            var defFound = activeUnits.find(function (u) {
                return String(u.unit_id) === String(defId);
            });
            if (defFound) {
                comparisonUnitId = String(defFound.unit_id);
                comparisonName =
                    defFound.unit_name || defFound.unit_short_name || "-";
            }
        } else if (activeUnits.length > 0) {
            comparisonUnitId = String(activeUnits[0].unit_id);
            comparisonName =
                activeUnits[0].unit_name || activeUnits[0].unit_short_name || "-";
        }
        comparisonLabel = "Default";
    }

    var sameUnit =
        comparisonUnitId !== null
            ? String(currentUnitId) === comparisonUnitId
            : true;
    var colorClass = sameUnit ? "text-success" : "text-danger";
    var extraInfoHtml =
        comparisonUnitId !== null
            ? '&nbsp;|&nbsp;<span class="text-muted">' +
              comparisonLabel +
              ': <span class="' +
              colorClass +
              ' fw-medium">' +
              comparisonName +
              "</span></span>"
            : "";

    return (
        '<div class="fix-recipe-unit-cell">' +
        '<select class="' +
        selectClass +
        '" data-index="' +
        index +
        '">' +
        placeholder +
        options +
        "</select>" +
        '<small class="text-muted fix-recipe-unit-hint">Saat ini: <span class="' +
        colorClass +
        ' fw-medium">' +
        currentUnitName +
        "</span>" +
        extraInfoHtml +
        "</small>" +
        "</div>"
    );
}

function renderFixRecipeRows() {
    $("#fix_recipe_tableSupply tbody").html("");
    fixRecipeBahan.forEach(function (e, index) {
        $("#fix_recipe_tableSupply tbody").append(
            '<tr class="row-fix-recipe-supply" data-id="' +
                e.supplies_id +
                '" data-unit-id="' +
                e.unit_id +
                '" data-index="' +
                index +
                '">' +
                "<td>" +
                e.supplies_name +
                "</td>" +
                '<td><input type="text" class="form-control form-control-sm number-only fix-recipe-row-qty" data-index="' +
                index +
                '" value="' +
                e.bom_detail_qty +
                '"></td>' +
                "<td>" +
                buildFixRecipeUnitSelect(e, index) +
                "</td>" +
                '<td class="text-center">' +
                '<a class="btn-action-icon btn_fix_recipe_delete_row" href="javascript:void(0);">' +
                '<i class="fe fe-trash-2"></i>' +
                "</a>" +
                "</td>" +
                "</tr>",
        );
    });
    if (typeof feather !== "undefined") {
        feather.replace();
    }
}

function populateFixRecipeUnitSelect(data) {
    var $unit = $("#fix_recipe_unit_id");
    $unit.empty().prop("disabled", false);
    var activeRelasi = (data.relasi || []).filter(function (r) {
        return (
            r.status === undefined ||
            r.status === null ||
            parseInt(r.status, 10) === 1
        );
    });

    if (activeRelasi.length > 0) {
        var unitId1List = activeRelasi.map(function (r) {
            return String(r.pr_unit_id_1 || "");
        });
        var allUnitIds = (data.pr_unit || []).map(function (u) {
            return String(u.unit_id);
        });
        var candidates = allUnitIds.filter(function (uid) {
            return !unitId1List.includes(uid);
        });
        var smallestId = "";
        var smallestShort = "";
        if (candidates.length > 0) {
            var found = (data.pr_unit || []).find(function (u) {
                return String(u.unit_id) === candidates[0];
            });
            if (found) {
                smallestId = found.unit_id;
                smallestShort = found.unit_name || found.unit_short_name;
            }
        }
        if (!smallestShort) {
            var last = activeRelasi[activeRelasi.length - 1];
            smallestId = last.pr_unit_id_2;
            smallestShort = last.pr_unit_name_2;
        }

        if (data.unit_name && String(data.unit_id) !== String(smallestId)) {
            $unit.append(
                '<option value="' +
                    data.unit_id +
                    '" disabled selected>' +
                    data.unit_name +
                    "</option>",
            );
            $unit.append(
                '<option value="' +
                    smallestId +
                    '">' +
                    smallestShort +
                    "</option>",
            );
        } else {
            $unit.append(
                '<option value="' +
                    smallestId +
                    '" selected>' +
                    smallestShort +
                    "</option>",
            );
        }
    } else {
        (data.pr_unit || []).forEach(function (element) {
            var active =
                String(element.unit_id) === String(data.unit_id) ? "selected" : "";
            $unit.append(
                '<option value="' +
                    element.unit_id +
                    '" ' +
                    active +
                    ">" +
                    (element.unit_name || element.unit_short_name) +
                    "</option>",
            );
        });
    }

    renderFixRecipeProductUnitInfo(data.relasi, data.unit_name, data.pr_unit);
}

function fillFixRecipeBomModal(data) {
    fixRecipeBahan = [];
    $("#fix_recipe_product_id").empty();
    $("#fix_recipe_unit_id").empty();
    $("#fix_recipe_supplies_id").empty();
    $("#fix_recipe_unit_supplies_id").empty();
    $("#fix_recipe_bom_detail_qty").val("");
    $("#fix_recipe_bom_qty").val("");
    $("#fix_recipe_product_label").text("");
    $("#fix_recipe_product_unit_info").hide().html("");
    $("#fix_recipe_supplies_unit_info").hide().html("");
    $("#fix_recipe_tableSupply tbody").html("");
    $("#fixRecipeBom .is-invalid").removeClass("is-invalid");

    var productLabel = data.product_name || "";
    var sku = data.product_variant_sku || data.product_sku || "";
    if (sku && sku !== "-") {
        productLabel = productLabel ? sku + " | " + productLabel : sku;
    }
    $("#fix_recipe_product_label").text(productLabel || "-");
    $("#fix_recipe_product_id").append(
        '<option value="' +
            data.product_id +
            '" selected>' +
            productLabel +
            "</option>",
    );
    $("#fix_recipe_bom_qty").val(data.bom_qty);
    populateFixRecipeUnitSelect(data);

    (data.details || data.items || []).forEach(function (e) {
        fixRecipeBahan.push({
            bom_detail_id: e.bom_detail_id,
            supplies_id: e.supplies_id,
            supplies_name: e.supplies_name,
            bom_detail_qty: e.bom_detail_qty,
            unit_name: e.current_unit_name || e.unit_name,
            unit_id: e.current_unit_id || e.unit_id,
            current_unit_id: e.current_unit_id || e.unit_id,
            current_unit_name: e.current_unit_name || e.unit_name,
            active_units: e.active_units || e.units || [],
            units: e.active_units || e.units || [],
            supplies_relasi: e.supplies_relasi || [],
            supplies_default_unit: e.supplies_default_unit || null,
        });
    });
    fixRecipeBahan.sort(function (a, b) {
        return (a.supplies_name || "").localeCompare(
            b.supplies_name || "",
            "id",
            { sensitivity: "base" },
        );
    });
    renderFixRecipeRows();
    $("#fixRecipeBom").modal("show");
}

function openFixRecipeBom(bomId, options) {
    options = options || {};
    var openSeq = ++fixRecipeOpenSeq;
    fixRecipeReturnToProduction = options.returnToProduction !== false;
    fixRecipeBomId = bomId;
    fixRecipeBahan = [];
    $("#fixRecipeBom").attr("bom_id", bomId);

    var showAfterLoad = function () {
        loadBomForValidation(bomId, function (data) {
            if (openSeq !== fixRecipeOpenSeq) {
                return;
            }
            if (!data) {
                notifikasi(
                    "error",
                    "Gagal Memuat Resep",
                    "Tidak dapat memuat detail resep. Silakan coba lagi.",
                );
                if (fixRecipeReturnToProduction) {
                    restoreProductionDraft();
                }
                return;
            }
            fillFixRecipeBomModal(data);
        });
    };

    if (fixRecipeReturnToProduction && $("#addProduction").hasClass("show")) {
        $("#addProduction")
            .one("hidden.bs.modal", function () {
                if (openSeq !== fixRecipeOpenSeq) {
                    return;
                }
                showAfterLoad();
            });
        hideProductionPreserveDraft();
        return;
    }

    if (fixRecipeReturnToProduction) {
        hideProductionPreserveDraft();
    } else {
        $(".modal.show").not("#fixRecipeBom").modal("hide");
    }
    showAfterLoad();
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

/**
 * Reset tombol "+" (btn-add-product) ke state semula setelah loading.
 * Tidak bisa pakai ResetLoadingButton(".btn-add-product", "+") polos:
 * - ResetLoadingButton mengosongkan inline height (`css({height:''})`), yang
 *   MENGHAPUS `height:42px` bawaan tombol (bukan mengembalikannya) begitu
 *   LoadingButton sempat menimpanya - tombol jadi lebih pendek dari input di
 *   sebelahnya (GitHub #111 follow-up).
 * - Teks "+" polos juga menghapus ikon feather (fe-plus), diganti karakter
 *   biasa.
 * Jadi kembalikan ikon + paksa lagi height 42px eksplisit di sini.
 */
function resetAddProductButton() {
    ResetLoadingButton(".btn-add-product", '<i class="fe fe-plus"></i>');
    $(".btn-add-product").css("height", "42px");
}

function continueAddProduct(tempBom) {
    var satuanResep = validateBomActiveUnits(tempBom);
    if (!satuanResep.valid) {
        stashPendingProductionAdd(tempBom);
        promptRecipeNeedsUpdate(tempBom.bom_id, {
            returnToProduction: true,
            invalidLabels: satuanResep.invalid,
        });
        return false;
    }

    // Resep sinkron — lanjut validasi qty / insert baris.
    var resolved = resolveProductionInputQtyUnit(tempBom);
    if (!resolved.ok) {
        clearPendingProductionAdd();
        notifikasi("error", "Pallet Tidak Valid", resolved.message);
        return false;
    }
    if (resolved.pd_qty <= 0 || !resolved.unit_id) {
        clearPendingProductionAdd();
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
        clearPendingProductionAdd();
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
    var isRetailOutput =
        parseInt(temp.retail_unit || 0, 10) > 0 &&
        parseInt(resolved.unit_id || 0, 10) === parseInt(temp.retail_unit, 10);
    var destinationId = isRetailOutput
        ? parseInt($("#production_destination_warehouse_id").val() || 0, 10)
        : productionNonRetailDestinationId();

    // Kerja di atas salinan `items` dulu (bukan `items` asli) - baris baru/gabungan
    // baru benar-benar masuk ke daftar kalau cek stok di bawah (GitHub #101) lolos.
    var candidateItems = items.map(function (element) {
        return $.extend({}, element);
    });
    var idx = -1;
    candidateItems.forEach(function (element) {
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
        var mergedItem = candidateItems.find(function (element) {
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
            clearPendingProductionAdd();
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
                destinationData.text ||
                (isRetailOutput
                    ? productionActiveWarehouseName()
                    : productionNonRetailDestinationName()),
            bom_id: temp.bom_id,
        };
        // Posisi baris baru (atas/bawah) ikut konstanta bersama PG_POPUP_TABLE
        // di public/Custom_js/Shared/popup-table.js (GitHub #111).
        pgPopupTableInsert(candidateItems, data);
    }
    var addedNewRow = idx == -1;

    // Cek stok bahan mentah agregat (termasuk baris ini) SEBELUM baris masuk ke
    // daftar - dulu cek ini cuma jalan pas klik "Tambah Produksi" di akhir, jadi
    // user baru tahu stok kurang setelah menyusun seluruh daftar. GitHub #101.
    LoadingButton(".btn-add-product");
    $.ajax({
        url: "/checkProductionStock",
        method: "post",
        data: {
            detail: JSON.stringify(candidateItems),
            _token: token,
        },
        headers: {
            "X-CSRF-TOKEN": token,
        },
        success: function (e) {
            resetAddProductButton();
            if (!e || e.status != 1) {
                handleProductionValidationError(e, temp.bom_id);
                return;
            }

            // list_bahan sejajar index dengan items — kalau baris baru masuk di
            // paling atas, geser juga isinya supaya getBom() tidak memakai daftar
            // bahan milik baris lain.
            if (addedNewRow && pgPopupTableInsertsAtTop()) {
                list_bahan.unshift(undefined);
            }
            items = candidateItems;
            addRow(items);
            // Baris baru langsung terlihat tanpa harus scroll manual, ikut
            // arah ROW_INSERT_POSITION (GitHub #111 follow-up). Cuma di sini,
            // bukan di titik addRow() lain (reset/delete/view) - lihat catatan
            // di pgPopupTableScrollToEdge().
            if (addedNewRow) {
                pgPopupTableScrollToEdge(
                    $("#tableProduct").closest(".pg-popup-table-scroll"),
                );
            }

            clearProductionProductSelect();
            $("#unit_id").empty();
            $("#unit_id").append("<option selected>Pilih Satuan</option>");
            $("#production_qty").val(1);
            $("#production_pallet_hint").text("");
            $("#production_destination_warehouse_id")
                .val(null)
                .trigger("change");
            syncProductionDestinationControl();
            clearPendingProductionAdd();
        },
        error: function (a) {
            resetAddProductButton();
            if (handlePermissionError(a)) return;
            console.log(a);
            notifikasi(
                "error",
                "Gagal Cek Stok",
                "Terjadi kesalahan saat memeriksa stok. Silakan coba lagi.",
            );
        },
    });
    return true;
}
$(document).ready(function () {
    initProductionFormAutocompletes();
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
    clearPendingProductionAdd();
    $("#addProduction .modal-title").html("Tambah Produksi");
    $("#addProduction input").val("");
    clearProductionProductSelect();
    $("#production_qty").val(1);
    addRow([]);
    $(".is-invalid").removeClass("is-invalid");
    $(".prod-detail-field").hide();
    $("#row-production-acc-by").hide();
    $(".prod-cancel-field").hide();
    $("#col-production-date").removeClass("col-lg-3").addClass("col-lg-6");
    $("#col-production-desc").removeClass("col-lg-3 col-lg-6 col-lg-9 col-12").addClass("col-lg-6");
    $("#production_status_display").html("");
    $("#unit_id").html("");
    $("#unit_id").append("<option selected>Pilih Satuan</option>");
    $(".input_table, .add, .btn_delete_row_pr").show();
    setProductionSaveVisible(true, "Tambah Produksi");
    $("#production_desc").attr("disabled", false);
    $(".btn-cancel").html("Batal");
    $("#addProduction").modal("show");
    $(".dos").hide();
    syncProductionDateField({ value: getTodayStr(), disabled: false });
    $("#addProduction").removeAttr("revision_source_production_id");
    syncProductionDestinationControl();
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

$(document).on("change", "#production_date", function () {
    var val = $(this).val();
    if (!isProductionDateValid(val)) {
        $(this).addClass("is-invalid");
        if (typeof toastr !== "undefined") {
            toastr.error("Tanggal tidak valid. Minimal hari ini.");
        } else {
            notifikasi("error", "Tanggal Tidak Valid", "Tanggal produksi minimal hari ini.");
        }
        syncProductionDateField({ value: getTodayStr() });
        return;
    }
    $(this).removeClass("is-invalid");
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

    if (!data || !data.bom_id) {
        resetProductionProductUnitSelect();
        return;
    }

    // Blokir jika produk / varian sudah tidak aktif
    if (data.product_status == 0 || data.product_variant_status == 0) {
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
        $(this).val(null).trigger("change");
        return;
    }

    var units = Array.isArray(data.pr_unit) ? data.pr_unit : [];
    $("#unit_id").html("");
    units.forEach(function (element) {
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

$(document).on("change", "#unit_id", function () {
    updateProductionPalletHint();
    syncProductionDestinationControl();
});

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

    var btnStyleView =
        "background:#eff6ff;border:1px solid #bfdbfe;color:#2563eb;flex-shrink:0;";
    var btnStyleDelete =
        "background:#fef2f2;border:1px solid #fecaca;color:#dc2626;flex-shrink:0;";

    if (hasAccessAction("Produksi", "view")) {
        prAct +=
            '<a href="javascript:void(0);" class="btn-action-icon btn_view" style="' +
            btnStyleView +
            '" data-bs-toggle="tooltip" title="Lihat Detail Produksi"><i class="fe fe-eye" style="font-size:14px;"></i></a>';
    }
    if (
        !isOldRow &&
        status === 2 &&
        row.can_cancel !== false &&
        !row.has_shipped_stock_transfer &&
        hasAccessAction("Produksi", "delete")
    ) {
        prAct +=
            '<a href="javascript:void(0);" class="btn-action-icon btn_delete" style="' +
            btnStyleDelete +
            '" data-bs-toggle="tooltip" title="Batalkan Produksi"><i class="fe fe-x-circle" style="font-size:14px;"></i></a>';
    }
    if (isOldRow || (status !== 1 && status !== 2)) {
        prAct = hasAccessAction("Produksi", "view")
            ? '<a href="javascript:void(0);" class="btn-action-icon btn_view" style="' +
              btnStyleView +
              '" data-bs-toggle="tooltip" title="Lihat Detail Produksi"><i class="fe fe-eye" style="font-size:14px;"></i></a>'
            : "";
    }

    if (!prAct) {
        return '<span class="text-muted small">—</span>';
    }
    return (
        '<div class="d-flex justify-content-center align-items-center" style="gap:8px;">' +
        prAct +
        "</div>"
    );
}

function escapeHtml(str) {
    if (str == null) return "";
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;");
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
                className: "align-middle",
                orderable: false,
            },
            {
                data: "production_code",
                className: "align-middle",
                orderable: false,
            },
            {
                data: "status_text",
                className: "text-center align-middle",
                orderable: false,
            },
            {
                data: "notes",
                defaultContent: "-",
                className: "align-middle",
                orderable: false,
                render: function (data) {
                    if (!data || data === "-" || String(data).trim() === "") {
                        return '<span class="text-muted">-</span>';
                    }
                    return '<span class="text-dark">' + escapeHtml(data) + '</span>';
                },
            },
            {
                data: "created_by_name",
                defaultContent: "-",
                className: "align-middle",
                orderable: false,
                render: function (data) {
                    return typeof renderCreatedByName === "function"
                        ? renderCreatedByName(data)
                        : (data ? '<span class="fw-semibold text-dark">' + escapeHtml(data) + '</span>' : '<span class="text-muted">-</span>');
                },
            },
            {
                data: "acc_by_name",
                defaultContent: "-",
                className: "align-middle",
                orderable: false,
                render: function (data) {
                    return typeof renderCreatedByName === "function"
                        ? renderCreatedByName(data)
                        : (data ? '<span class="fw-semibold text-dark">' + escapeHtml(data) + '</span>' : '<span class="text-muted">-</span>');
                },
            },
            {
                data: "cancel_requested_by_name",
                defaultContent: "-",
                className: "align-middle",
                orderable: false,
                render: function (data) {
                    return typeof renderCreatedByName === "function"
                        ? renderCreatedByName(data)
                        : (data ? '<span class="fw-semibold text-dark">' + escapeHtml(data) + '</span>' : '<span class="text-muted">-</span>');
                },
            },
            {
                data: "production_desc",
                defaultContent: "-",
                className: "align-middle",
                orderable: false,
                render: function (data) {
                    if (!data || data === "-" || String(data).trim() === "") {
                        return '<span class="text-muted">-</span>';
                    }
                    return '<span class="text-dark">' + escapeHtml(data) + '</span>';
                },
            },
            {
                data: "action",
                className: "text-center align-middle",
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
                    `<div style="display:flex;align-items:center;gap:10px;"><div style="width:32px;height:32px;border-radius:8px;background:#eff6ff;border:1px solid #bfdbfe;color:#2563eb;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="fe fe-calendar" style="font-size:14px;"></i></div><span class="fw-semibold text-dark">${moment(e[i].production_date).format("D MMM YYYY")}</span></div>`;
                if (e[i].production_code) {
                    var cleanCode = $('<div>').html(e[i].production_code).text();
                    e[i].production_code =
                        `<span class="badge" style="background:#f0f9ff;color:#0284c7;border:1px solid #bae6fd;padding:6px 12px;border-radius:8px;font-family:monospace;font-weight:700;font-size:12px;">${escapeHtml(cleanCode)}</span>`;
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
            if (handlePermissionError(err)) return;
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
        clearProductionProductSelect();
        $("#production_qty").val(1);
        addRow([]);
        $(".is-invalid").removeClass("is-invalid");
        $(".prod-detail-field").hide();
        $("#row-production-acc-by").hide();
        $(".prod-cancel-field").hide();
        $("#col-production-date").removeClass("col-lg-3").addClass("col-lg-6");
        $("#col-production-desc").removeClass("col-lg-3 col-lg-6 col-lg-9 col-12").addClass("col-lg-6");
        $("#production_status_display").html("");
        $("#unit_id").html("");

        syncProductionDateField({ value: getTodayStr(), disabled: true });
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
        syncProductionDestinationControl();

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
    if (!isProductionDateValid($("#production_date").val())) {
        $("#production_date").addClass("is-invalid");
        notifikasi(
            "error",
            "Tanggal Tidak Valid",
            "Tanggal produksi minimal hari ini (tanggal sebelumnya tidak diizinkan).",
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
            if (!e || e.status != 1) {
                handleProductionValidationError(
                    e,
                    items[0] && items[0].bom_id,
                );
                return false;
            }
            afterInsert();
        },
        error: function (a) {
            ResetLoadingButton(
                ".btn-save",
                mode == 1 ? "Tambah Produksi" : "Update Produksi",
            );
            if (handlePermissionError(a)) return;
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
    if (!Array.isArray(e)) e = [];
    $("#tableProduct tbody").html("");
    if (e.length === 0) {
        $("#tableProduct tbody").html(
            '<tr class="pg-popup-table-empty"><td colspan="5">Belum ada produk. Tambahkan lewat form di atas.</td></tr>',
        );
    }
    e.forEach((element, index) => {
        console.log(element);

        let btnAct = `<a class="btn_delete_row_pr d-inline-flex align-items-center justify-content-center" href="javascript:void(0);" style="width: 28px; height: 28px; background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; border-radius: 6px; transition: all 0.2s ease;" title="Hapus Produk"><i class="fe fe-trash-2" style="font-size: 13px;"></i></a>`;
        if (mode == 3) {
            btnAct = `<a href="javascript:void(0);" class="btn_list_row d-inline-flex align-items-center justify-content-center" index="${index}" style="width: 28px; height: 28px; background: #f0f9ff; color: #0ea5e9; border: 1px solid #bae6fd; border-radius: 6px; transition: all 0.2s ease;" data-bs-toggle="tooltip" title="Lihat Daftar Bahan"><i class="fe fe-list" style="font-size: 13px;"></i></a>`;
        }

        $("#tableProduct tbody").append(`
                <tr class="row-product" data-index="${index}" data-id="${element.product_variant_id}" data-bom="${element.bom_id}">
                    <td style="font-weight: 600; color: #334155;">${element.product_name}</td>
                    <td class="text-center" style="font-weight: 700; color: #1e293b;">${formatRupiah(element.pd_qty)}</td>
                    <td style="color: #64748b;">${element.unit_name}</td>
                    <td><span class="badge" style="background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;padding:6px 10px;"><i class="fe fe-map-pin me-1"></i>${element.destination_warehouse_name || (parseInt(element.retail_unit || 0, 10) > 0 && parseInt(element.unit_id || 0, 10) === parseInt(element.retail_unit || 0, 10) ? productionActiveWarehouseName() : productionNonRetailDestinationName())}</span></td>
                    <td class="text-center align-middle">
                        ${btnAct}
                    </td>
                </tr>
            `);
        modeBahan = 1;
        if (mode != 3) getBom(element.bom_id, index);
    });
    if (mode == 3) {
        $("#tableProduct [data-bs-toggle='tooltip']").tooltip();
    }
    pgPopupTableRefresh($("#tableProduct").closest(".pg-popup-table-scroll"));
}

/**
 * Validasi + tambah baris produk produksi (sama dengan klik + Tambah).
 * @param {{ forceReloadBom?: boolean }} options
 *   forceReloadBom: selalu fetch BOM dari server (wajib setelah Update Resep,
 *   agar tidak memakai active_units stale dari select2).
 */
function attemptAddProductionProduct(options) {
    options = options || {};
    var forceReloadBom = options.forceReloadBom === true;

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
    if (!tempBom) {
        notifikasi(
            "error",
            "Gagal Insert",
            "Silahkan cek kembali inputan anda",
        );
        return false;
    }

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
        tempBom.product_status == 0 ||
        tempBom.product_variant_status == 0
    ) {
        var alasan = [];
        if (tempBom.product_status == 0)
            alasan.push("produk sudah tidak aktif");
        if (tempBom.product_variant_status == 0)
            alasan.push("varian produk sudah tidak aktif");
        clearPendingProductionAdd();
        notifikasi(
            "error",
            "Produk Tidak Aktif",
            "Tidak dapat produksi karena " +
                alasan.join(" & ") +
                ". Silakan hapus resep (BOM) ini di halaman Resep Bahan Mentah.",
        );
        return false;
    }

    var bomId =
        (pendingProductionAdd && pendingProductionAdd.bom_id) ||
        tempBom.bom_id;

    if (!forceReloadBom && bomDetailHasActiveUnits(tempBom)) {
        continueAddProduct(tempBom);
        return;
    }

    LoadingButton(".btn-add-product");
    loadBomForValidation(bomId, function (fullBom) {
        resetAddProductButton();
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
}

function retryPendingProductionAdd() {
    if (!pendingProductionAdd) {
        return;
    }
    // Draft form masih terisi; force reload BOM agar cek satuan memakai data terbaru.
    attemptAddProductionProduct({ forceReloadBom: true });
}

$(document).on("click", ".btn-add-product", function () {
    attemptAddProductionProduct();
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
    clearProductionProductSelect();
    $("#production_qty").val(1);
    addRow([]);
    $(".is-invalid").removeClass("is-invalid");
    $("#unit_id").html("");
    $("#production_date").val(data.production_date);
    syncProductionDateField({ value: data.production_date, disabled: true });
    $("#production_desc").val(data.production_desc).attr("disabled", true);

    // Info umum (Kode Produksi/Dibuat Oleh/Status) — selalu tampil di mode lihat detail.
    $('#production_code_display').val($('<div>').html(data.production_code).text());
    $('#production_created_by_display').val(data.created_by_name || '-');
    $('.prod-detail-field').show();
    $('#col-production-date').removeClass('col-lg-6').addClass('col-lg-3');
    $('#production_status_display').html(data.status_text || '-');

    // "Dibatalkan" dibedakan dari sekadar "Tolak" lewat cancel_requested_by — hanya terisi
    // kalau produksi ini pernah lewat alur pengajuan batal (status 4) lalu disetujui
    // pembatalannya (berakhir di status 3 juga, sama seperti tolak langsung dari pending, tapi
    // beda alur). Notes Pembatalan pakai kolom `notes` yang sama dipakai declineProduction().
    var isDibatalkan = data.status == 3 && data.cancel_requested_by;

    // Diapprove Oleh: HANYA untuk status Berhasil (2) — acc_by_name bisa berisi "Sistem
    // (Auto-Timeout)" kalau produksi ini di-auto-timeout, bukan diputuskan staf sungguhan.
    if (data.status == 2 && data.acc_by_name && data.acc_by_name !== '-') {
        $('#production_acc_by_name').val(data.acc_by_name);
        $('#row-production-acc-by').show();
        $('#col-production-desc').removeClass('col-lg-6 col-12').addClass('col-lg-9');
    } else {
        $('#row-production-acc-by').hide();
        $('#col-production-desc').removeClass('col-lg-9 col-lg-6').addClass('col-12');
    }

    if (isDibatalkan) {
        $('#production_cancel_requested_by_display').val(data.cancel_requested_by_name || '-');
        $('#production_cancel_notes_display').val(data.notes || '-');
        $('.prod-cancel-field').show();
        $('#col-production-desc').removeClass('col-lg-9 col-12').addClass('col-lg-6');
    } else {
        $('.prod-cancel-field').hide();
    }

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
            // Tetap tampilkan alur cancellation; jika ST sudah kirim, hide Terima
            // (opts.hideAccept) tapi biarkan Tolak supaya lepas dari Menunggu Batal.
            approvalAction = "cancellation";
        }
    }
    $("#addProduction").data("approval-action", approvalAction);
    $("#addProduction").data(
        "hide-cancel-accept",
        !!data.has_shipped_stock_transfer,
    );
    showProductionApprovalActions(approvalAction, data.production_id, {
        hideAccept: !!data.has_shipped_stock_transfer,
    });

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

function setModalBahanViewOnly(viewOnly) {
    // d-inline-flex uses !important — must toggle d-none, not jQuery .hide()
    if (viewOnly) {
        $(".btn-save-bahan").addClass("d-none");
        $("#modalBahan .btn-close-bahan.pg-btn-cancel").text("Tutup");
        $("#tableSupplies .col-bahan-check").addClass("d-none");
    } else {
        $(".btn-save-bahan").removeClass("d-none");
        $("#modalBahan .btn-close-bahan.pg-btn-cancel").text("Batal");
        $("#tableSupplies .col-bahan-check").removeClass("d-none");
    }
}

$(document).on("click", ".btn_list_row", function () {
    $("#addProduction").modal("hide");
    $("#modalBahan").modal("show");
    let row = $(this).closest("tr").data("bom");
    let index = $(this).attr("index");
    $(".btn-save-bahan").attr("index", index);
    modeBahan = 2;
    setModalBahanViewOnly(mode == 3);
    getBom(row, index);
});

$(document).on("click", ".btn-close-bahan", function () {
    $("#addProduction").modal("show");
    $("#modalBahan").modal("hide");
    if (mode === 3) {
        showProductionApprovalActions(
            $("#addProduction").data("approval-action"),
            $("#addProduction").attr("production_id"),
            { hideAccept: !!$("#addProduction").data("hide-cancel-accept") },
        );
    }
});

// --- Fix recipe modal (opened when BOM units inactive / out of sync) ---
$(document).on("change", "#fix_recipe_supplies_id", function () {
    var data = $(this).select2("data")[0];
    if (!data) {
        return;
    }
    $("#fix_recipe_unit_supplies_id").empty();
    getActiveSuppliesUnitsFix(data.units).forEach(function (element) {
        $("#fix_recipe_unit_supplies_id").append(
            '<option value="' +
                element.unit_id +
                '">' +
                element.unit_name +
                "</option>",
        );
    });
    renderFixRecipeSuppliesUnitInfo(
        data.supplies_relasi,
        getActiveSuppliesUnitsFix(data.units),
        data.supplies_default_unit,
    );
});

$(document).on("click", ".btn-fix-recipe-add-supply", function () {
    $("#fixRecipeBom .is-invalid").removeClass("is-invalid");
    var valid = 1;
    $("#fixRecipeBom .fix-recipe-fill-supply").each(function () {
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
        notifikasi("error", "Gagal Insert", "Silahkan cek kembali inputan anda");
        return false;
    }

    var temp = $("#fix_recipe_supplies_id").select2("data")[0];
    var unitId = $("#fix_recipe_unit_supplies_id").val();
    var qty = parseInt($("#fix_recipe_bom_detail_qty").val(), 10) || 0;
    var idx = -1;

    fixRecipeBahan.forEach(function (element) {
        if (
            String(element.supplies_id) === String(temp.supplies_id) &&
            String(element.unit_id) === String(unitId)
        ) {
            element.bom_detail_qty += qty;
            idx = 1;
        }
    });

    if (idx === -1) {
        var activeUnits = getActiveSuppliesUnitsFix(temp.units);
        fixRecipeBahan.push({
            supplies_id: temp.supplies_id,
            supplies_name: temp.supplies_name,
            bom_detail_qty: qty,
            unit_name: $("#fix_recipe_unit_supplies_id option:selected").text(),
            unit_id: unitId,
            current_unit_id: unitId,
            current_unit_name: $(
                "#fix_recipe_unit_supplies_id option:selected",
            ).text(),
            active_units: activeUnits,
            units: activeUnits,
            supplies_relasi: temp.supplies_relasi || [],
            supplies_default_unit: temp.supplies_default_unit || null,
        });
    }
    renderFixRecipeRows();
    $("#fix_recipe_supplies_id").empty();
    $("#fix_recipe_unit_supplies_id").empty();
    $("#fix_recipe_bom_detail_qty").val("");
    $("#fix_recipe_supplies_unit_info").hide().html("");
});

$(document).on("change", ".fix-recipe-row-unit", function () {
    var index = parseInt($(this).data("index"), 10);
    if (isNaN(index) || !fixRecipeBahan[index]) {
        return;
    }
    var selectedValue = $(this).val();
    if (!selectedValue) {
        return;
    }
    fixRecipeBahan[index].unit_id = selectedValue;
    fixRecipeBahan[index].unit_name = $(this)
        .find("option:selected")
        .text()
        .trim();
    $(this).closest("tr").attr("data-unit-id", fixRecipeBahan[index].unit_id);
});

$(document).on("input change", ".fix-recipe-row-qty", function () {
    var index = parseInt($(this).data("index"), 10);
    if (isNaN(index) || !fixRecipeBahan[index]) {
        return;
    }
    fixRecipeBahan[index].bom_detail_qty = parseInt($(this).val(), 10) || 0;
});

$(document).on("click", ".btn_fix_recipe_delete_row", function () {
    var row = $(this).closest("tr");
    var index = row.data("index");
    if (index !== undefined && index !== "") {
        fixRecipeBahan.splice(parseInt(index, 10), 1);
    }
    renderFixRecipeRows();
});

$(document).on("click", ".btn-close-fix-recipe", function () {
    clearPendingProductionAdd();
    if (fixRecipeReturnToProduction || productionDraftPreserved) {
        restoreProductionDraft();
    } else {
        $("#fixRecipeBom").modal("hide");
    }
});

$("#fixRecipeBom").on("hidden.bs.modal", function () {
    if (productionDraftPreserved && !$("#addProduction").hasClass("show")) {
        clearPendingProductionAdd();
        restoreProductionDraft();
    }
});

$(document).on("click", ".btn-fix-recipe-save", function () {
    var $btn = $(this);
    LoadingButton($btn);
    $("#fixRecipeBom .is-invalid").removeClass("is-invalid");
    var valid = 1;

    $("#fixRecipeBom .fix-recipe-fill").each(function () {
        if (
            $(this).val() == null ||
            $(this).val() == "null" ||
            $(this).val() == ""
        ) {
            valid = -1;
            $(this).addClass("is-invalid");
        }
    });

    $(".fix-recipe-row-unit").each(function () {
        if (!$(this).val()) {
            valid = -1;
            $(this).addClass("is-invalid");
        }
    });

    $(".fix-recipe-row-qty").each(function () {
        var qty = parseInt($(this).val(), 10) || 0;
        if (qty <= 0) {
            valid = -1;
            $(this).addClass("is-invalid");
        }
    });

    if (valid == -1) {
        notifikasi("error", "Gagal Update", "Silahkan cek kembali inputan anda");
        ResetLoadingButton(".btn-fix-recipe-save", "Update Resep");
        return false;
    }

    if (fixRecipeBahan.length === 0) {
        notifikasi("error", "Gagal Update", "Minimal input 1 bahan mentah");
        ResetLoadingButton(".btn-fix-recipe-save", "Update Resep");
        return false;
    }

    // Sync qty from inputs before save
    $(".fix-recipe-row-qty").each(function () {
        var index = parseInt($(this).data("index"), 10);
        if (!isNaN(index) && fixRecipeBahan[index]) {
            fixRecipeBahan[index].bom_detail_qty =
                parseInt($(this).val(), 10) || 0;
        }
    });

    $.ajax({
        url: "/updateProductionBom",
        method: "post",
        data: {
            bom_id: fixRecipeBomId || $("#fixRecipeBom").attr("bom_id"),
            bom_qty: $("#fix_recipe_bom_qty").val(),
            unit_id: $("#fix_recipe_unit_id").val(),
            product_id: $("#fix_recipe_product_id").val(),
            bahan: JSON.stringify(fixRecipeBahan),
            _token: token,
        },
        headers: {
            "X-CSRF-TOKEN": token,
        },
        success: function (e) {
            ResetLoadingButton(".btn-fix-recipe-save", "Update Resep");
            if (typeof e === "object" && e !== null && e.message) {
                notifikasi("error", "Gagal Update", e.message);
                return;
            }
            notifikasi("success", "Berhasil Update", "Berhasil Update Resep");
            var shouldRetryAdd = !!pendingProductionAdd;
            restoreProductionDraft(function () {
                if (shouldRetryAdd) {
                    retryPendingProductionAdd();
                }
            });
        },
        error: function () {
            ResetLoadingButton(".btn-fix-recipe-save", "Update Resep");
            notifikasi(
                "error",
                "Gagal Update",
                "Gagal menyimpan resep. Pastikan Anda punya akses update resep / create produksi.",
            );
        },
    });
});

function getBom(id, index = null) {
    // kalau index sudah ada, maka akan balik
    if (modeBahan == 1 && list_bahan[index] !== undefined) {
        return;
    }

    $.ajax({
        url: "/getProductionBom",
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

                const viewOnly = mode == 3;
                e[0].details.forEach((b) => {
                    let isChecked = false;
                    if (Array.isArray(current_list)) {
                        // Gunakan parseInt untuk memastikan perbandingan angka benar
                        isChecked = current_list.some(
                            (id) => parseInt(id) == parseInt(b.supplies_id),
                        );
                    }
                    const checkTd = viewOnly
                        ? ""
                        : `<td class="text-center col-bahan-check" style="vertical-align: middle;">
                                    <input type="checkbox" ${isChecked ? "checked" : ""}
                                    class="form-check-input chk" supplies_id="${b.supplies_id}" title="Centang jika bahan ini dipakai"
                                    style="width: 18px; height: 18px; cursor: pointer; border-radius: 4px;" />
                                </td>`;

                    $("#tableSupplies tbody").append(`
                            <tr class="row-bahan" style="border-bottom: 1px solid #f1f5f9;">
                                ${checkTd}
                                <td style="font-weight: 600; color: #475569;">${b.supplies_name}</td>
                            </tr>
                        `);
                });
                setModalBahanViewOnly(viewOnly);
            }
            console.log(list_bahan);
        },
        error: function (xhr) {
            if (handlePermissionError(xhr)) return;
            console.error("Gagal load resep:", xhr);
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
            if (e && e.status === 0) {
                notifikasi(
                    "error",
                    e.header || "Pembatalan Tidak Diizinkan",
                    e.message || "Produksi tidak dapat dibatalkan.",
                );
                refreshProduction();
                return false;
            }
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
            if (handlePermissionError(e)) return;
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
            if (e && e.status === 0) {
                notifikasi(
                    "error",
                    e.header || "Pembatalan Tidak Diizinkan",
                    e.message || "Produksi tidak dapat dibatalkan.",
                );
                refreshProduction();
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
            if (handlePermissionError(e)) return;
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
    $(".btn-konfirmasi").html('<i class="fe fe-check-circle me-1"></i>Konfirmasi Batal Produksi');
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
            ResetLoadingButton(".btn-konfirmasi", '<i class="fe fe-check-circle me-1"></i>Konfirmasi Batal Produksi');
            $(".modal").modal("hide");
            afterInsert();
            notifikasi(
                "success",
                "Berhasil Tolak",
                "Berhasil tolak pembatalan produksi",
            );
        },
        error: function (e) {
            ResetLoadingButton(".btn-konfirmasi", '<i class="fe fe-check-circle me-1"></i>Konfirmasi Batal Produksi');
            if (handlePermissionError(e)) return;
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
    $(".btn-konfirmasi").html('<i class="fe fe-check-circle me-1"></i>Konfirmasi');
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
                        ResetLoadingButton(".btn-konfirmasi", '<i class="fe fe-check-circle me-1"></i>Konfirmasi');
                        showModalKonfirmasi(
                            e.message,
                            "btn-confirm-create-stock-production",
                        );
                        $("#btn-confirm-create-stock-production").attr(
                            "production_id",
                            productionId,
                        );
                        $(".btn-konfirmasi").html('<i class="fe fe-check-circle me-1"></i>Konfirmasi');
                        return false;
                    }
                    if (isRecipeNeedsUpdateError(e) && e.bom_id) {
                        ResetLoadingButton(".btn-konfirmasi", '<i class="fe fe-check-circle me-1"></i>Konfirmasi');
                        promptRecipeNeedsUpdate(e.bom_id, {
                            returnToProduction: false,
                            title: e.header || "Satuan Resep Tidak Aktif",
                            message: e.message,
                        });
                        return false;
                    }
                    notifikasi("error", e.header, e.message);
                    if (e.status == -2) {
                        $(".modal").modal("hide");
                        refreshProduction();
                    }
                    ResetLoadingButton(".btn-konfirmasi", '<i class="fe fe-check-circle me-1"></i>Konfirmasi');
                    return false;
                } else {
                    ResetLoadingButton(".btn-konfirmasi", '<i class="fe fe-check-circle me-1"></i>Konfirmasi');
                    notifikasi(
                        "error",
                        "Gagal Update",
                        "Stock Product yang tidak mencukupi : " + e,
                    );
                }
            } else {
                ResetLoadingButton(".btn-konfirmasi", '<i class="fe fe-check-circle me-1"></i>Konfirmasi');
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
            if (handlePermissionError(e)) return;
            console.log(e);
            ResetLoadingButton(".btn-konfirmasi", '<i class="fe fe-check-circle me-1"></i>Konfirmasi');
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
    $(".btn-konfirmasi").html('<i class="fe fe-check-circle me-1"></i>Konfirmasi');
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
            ResetLoadingButton(".btn-konfirmasi", '<i class="fe fe-check-circle me-1"></i>Konfirmasi');
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
            if (handlePermissionError(e)) return;
            console.log(e);
            ResetLoadingButton(".btn-konfirmasi", '<i class="fe fe-check-circle me-1"></i>Konfirmasi');
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
            if (handlePermissionError(e)) return;
            console.log(e);
        },
    });
});
