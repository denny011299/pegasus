var mode = 1;
var table;
var products = [];
var list_photo;
var revisionSoId = null;
var revisionAutoOpened = false;
var confirmSoId = null;
var confirmAutoOpened = false;

function soHasAccess(moduleName, action) {
    return (
        typeof hasAccessAction === "function" &&
        hasAccessAction(moduleName, action)
    );
}

autocompleteCustomer("#so_customer", "#add_sales_order .modal-content");
autocompleteStaffSales("#sales_id", "#add_sales_order .modal-content");
autocompleteProductVariantOnly("#so_sku", "#add_sales_order .modal-content");
// Bootstrap d-inline-flex uses !important; toggle via d-none / d-inline-flex
function hideSoAccButtons() {
    $(".btn_acc, .btn_decline").removeClass("d-inline-flex").addClass("d-none");
    setSoModalMode("form");
}
function showSoAccButtons() {
    $(".btn_acc, .btn_decline").removeClass("d-none").addClass("d-inline-flex");
    setSoModalMode("confirm");
}
function setSoModalMode(kind) {
    var $modal = $("#add_sales_order");
    var $icon = $modal.find(".pg-modal-icon i");
    $modal.removeClass("pg-modal--form pg-modal--confirm");
    if (kind === "confirm") {
        $modal.addClass("pg-modal--confirm");
        $icon.attr("class", "fe fe-check-circle");
    } else {
        $modal.addClass("pg-modal--form");
        $icon.attr("class", "fe fe-shopping-cart");
    }
}
function hideSoSaveButton() {
    $(".btn-save").removeClass("d-inline-flex").addClass("d-none");
}
function showSoSaveButton(label) {
    if (label) {
        if ($("#btn_save_text").length) {
            $("#btn_save_text").text(label);
        } else {
            $(".btn-save").html(
                '<i class="fe fe-save"></i> <span id="btn_save_text">' +
                    label +
                    "</span>",
            );
        }
    }
    $(".btn-save").removeClass("d-none").addClass("d-inline-flex");
}
function setSoProductInputVisible(visible) {
    var $skuCol = $("#so_sku").closest(".col-lg-6, .col-md-6, .col-12");
    var $inputCol = $skuCol.next(".col-lg-6, .col-md-6, .col-12");
    var $row = $skuCol.closest(".col-12.row, .row");
    if (visible) {
        $row.show();
        $skuCol.show();
        $inputCol.show();
        $("#btn-add-product-so, #btn_scan_add_so, #btn_toggle_scan_so").show();
    } else {
        $row.hide();
    }
}
$(document).ready(function () {
    var query = new URLSearchParams(window.location.search);
    var qRevId = parseInt(query.get("rev_so_id"), 10);
    var qConfirmId = parseInt(query.get("confirm_so_id"), 10);
    if (!isNaN(qRevId) && qRevId > 0) revisionSoId = qRevId;
    if (!isNaN(qConfirmId) && qConfirmId > 0) confirmSoId = qConfirmId;
    initSalesOrderProductInput();
    inisialisasi();
});

function cleanRevisionQueryParam() {
    if (!window.history || !window.history.replaceState) return;
    var url = new URL(window.location.href);
    if (!url.searchParams.has("rev_so_id")) return;
    url.searchParams.delete("rev_so_id");
    window.history.replaceState({}, "", url.toString());
}

function cleanConfirmQueryParam() {
    if (!window.history || !window.history.replaceState) return;
    var url = new URL(window.location.href);
    if (!url.searchParams.has("confirm_so_id")) return;
    url.searchParams.delete("confirm_so_id");
    window.history.replaceState({}, "", url.toString());
}

function loadSalesOrderWithItems(soId, onSuccess, onError) {
    $.ajax({
        url: "/getSalesOrder",
        method: "get",
        data: { so_id: soId, with_items: 1 },
        success: function (resp) {
            var rows = Array.isArray(resp) ? resp : resp.original || [];
            if (rows.length) {
                onSuccess(rows[0]);
            } else if (typeof onError === "function") {
                onError();
            } else {
                notifikasi(
                    "error",
                    "Gagal",
                    "Data pengiriman tidak ditemukan.",
                );
            }
        },
        error: function (err) {
            if (handlePermissionError(err)) return;
            console.error("Gagal load detail SO:", err);
            if (typeof onError === "function") {
                onError(err);
            } else {
                notifikasi("error", "Gagal", "Gagal memuat detail pengiriman.");
            }
        },
    });
}

function openSalesOrderRevisionModal(data) {
    products = [];
    mode = 2;
    $("#add_sales_order .modal-title").html("Revisi Pengiriman");
    $("#add_sales_order input").empty().val("");
    $("#so_customer, #sales_id").empty();
    $("#so_discount").val(0).trigger("blur");
    $("#so_cost").val(0).trigger("blur");
    $("#so_ppn").val(0).trigger("blur");
    $(".form-select").not("#so_payment, #retail_warehouse_id").empty();

    $("#btn_bukti_foto").hide();
    $("#btn-lihat-bukti").show();

    var img =
        typeof parsePhotoInputValue === "function"
            ? parsePhotoInputValue(data.so_img)
            : [];
    list_photo = img;
    if (img.length > 0) {
        $("#modalViewPhoto .modal-footer").show();
        $("#fotoProduksiImage").attr("src", public + "issue/" + img[0]);
        $("#fotoProduksiImage").attr("index", 0);
        $("#btn_download_photo").attr("href", public + "issue/" + img[0]);
        $("#check_foto").show();
        $("#jumlahFoto").html(list_photo.length);
    } else {
        $("#btn-lihat-bukti").hide();
        $("#check_foto").hide();
    }

    $("#so_customer").append(
        `<option value="${data.so_customer}">${data.customer_name}</option>`,
    );
    if (data.so_cashier)
        $("#sales_id").append(
            `<option value="${data.so_cashier}">${data.staff_name}</option>`,
        );
    fillRetailWarehouse(data);
    $("#so_date").val(data.so_date);
    $("#so_invoice_no").val(data.so_invoice_no);
    $("#so_ref_number").val(data.so_ref_number || "");
    $("#so_payment").val(data.so_payment);
    $("#bukti").val(data.so_img);

    (data.items || []).forEach((e) => {
        products.push({
            sod_id: e.sod_id,
            product_variant_id: e.product_variant_id,
            product_name: e.sod_nama,
            product_variant_name: e.sod_variant,
            product_variant_sku: e.sod_sku,
            so_qty: e.sod_qty,
            product_variant_price: e.sod_harga,
            so_subtotal: e.sod_subtotal,
            unit_name: e.unit_name,
            unit_id: e.unit_id,
            pr_unit: e.pr_unit,
            retail_unit: e.retail_unit || 0,
            warehouse_id:
                e.warehouse_id ||
                (parseInt(e.unit_id, 10) === parseInt(e.retail_unit || 0, 10)
                    ? data.retail_warehouse_id || null
                    : null),
            warehouse_name:
                e.warehouse_name || data.retail_warehouse_name || null,
        });
    });
    products.sort(function (a, b) {
        return (a.sod_id || 0) - (b.sod_id || 0);
    });

    refreshTableProduct();

    // Khusus alur revisi: user boleh benarkan item langsung di modal.
    $("#so_sku, .so_qty, .so_unit, #so_unit_input, #so_qty_input").attr(
        "disabled",
        false,
    );
    $("#so_scan_barcode, #so_scan_qty").attr("disabled", false);
    $("#btn-add-product-so, #btn_scan_add_so, #btn_toggle_scan_so").show();
    $(".deleteRow").show();

    $("#so_ppn").trigger("blur");
    $("#so_discount").trigger("blur");
    $("#so_cost").trigger("blur");

    $(".is-invalid").removeClass("is-invalid");
    showSoSaveButton("Update Pengiriman");
    hideSoAccButtons();
    setSoProductInputVisible(true);
    $("#add_sales_order").modal("show");
    $("#add_sales_order").attr("so_id", data.so_id);
    $("#add_sales_order").attr("so_number", data.so_number);
    $("#add_sales_order").attr("so_invoice_no", data.so_invoice_no);
    $("#add_sales_order").attr("so_ref_number", data.so_ref_number || "");
}

function openSalesOrderDetailModal(data, intent) {
    intent = intent === "confirm" ? "confirm" : "view";
    var img =
        typeof parsePhotoInputValue === "function"
            ? parsePhotoInputValue(data.so_img)
            : [];

    products = [];
    mode = 3;
    $("#add_sales_order .modal-title").html("Detail Pengiriman");
    $("#add_sales_order input").empty().val("");
    $("#so_customer, #sales_id").empty();
    $(".form-select").not("#so_payment, #retail_warehouse_id").empty();
    $("#btn_bukti_foto").hide();
    $("#btn-lihat-bukti").show();

    list_photo = img;
    if (img.length > 0) {
        $("#modalViewPhoto .modal-footer").show();
        $("#fotoProduksiImage").attr("src", public + "issue/" + img[0]);
        $("#fotoProduksiImage").attr("index", 0);
        $("#btn_download_photo").attr("href", public + "issue/" + img[0]);
        $("#check_foto").show();
        $("#jumlahFoto").html(list_photo.length);
    } else {
        $("#btn-lihat-bukti").hide();
        $("#check_foto").hide();
    }

    $("#so_customer")
        .append(
            `<option value="${data.so_customer}">${data.customer_name}</option>`,
        )
        .attr("disabled", true);
    if (data.so_cashier)
        $("#sales_id").append(
            `<option value="${data.so_cashier}">${data.staff_name}</option>`,
        );
    fillRetailWarehouse(data);
    $("#retail_warehouse_id").prop("disabled", true);
    $("#so_date").val(data.so_date).attr("disabled", true);
    $("#so_invoice_no").val(data.so_invoice_no).attr("disabled", true);
    $("#so_ref_number")
        .val(data.so_ref_number || "")
        .attr("disabled", true);
    $("#so_payment").val(data.so_payment).attr("disabled", true);
    $("#bukti").val(data.so_img);

    (data.items || []).forEach((e) => {
        products.push({
            sod_id: e.sod_id,
            product_variant_id: e.product_variant_id,
            product_name: e.sod_nama,
            product_variant_name: e.sod_variant,
            product_variant_sku: e.sod_sku,
            so_qty: e.sod_qty,
            product_variant_price: e.sod_harga,
            so_subtotal: e.sod_subtotal,
            unit_name: e.unit_name,
            unit_id: e.unit_id,
            pr_unit: e.pr_unit,
            retail_unit: e.retail_unit || 0,
            warehouse_id:
                e.warehouse_id ||
                (parseInt(e.unit_id, 10) === parseInt(e.retail_unit || 0, 10)
                    ? data.retail_warehouse_id || null
                    : null),
            warehouse_name:
                e.warehouse_name || data.retail_warehouse_name || null,
        });
    });
    products.sort(function (a, b) {
        return (a.sod_id || 0) - (b.sod_id || 0);
    });

    refreshTableProduct();
    $(".deleteRow").hide();
    $("#so_sku, .so_qty, .so_unit, #so_unit_input, #so_qty_input").attr(
        "disabled",
        true,
    );
    $("#so_scan_barcode, #so_scan_qty").attr("disabled", true);
    setSoProductInputVisible(false);

    var soStatus = parseInt(data.status, 10);
    var confirmMode =
        intent === "confirm" &&
        soStatus === 1 &&
        soHasAccess("Pengiriman", "others");
    if (confirmMode) {
        showSoAccButtons();
        $("#add_sales_order .modal-title").html("Konfirmasi Pengiriman");
        $(".btn_acc").attr("so_id", data.so_id);
        $(".btn_acc").data("items", data.items);
        $(".btn_decline").attr("so_id", data.so_id);
        $(".btn_decline").data("items", data.items);
    } else {
        hideSoAccButtons();
    }

    $("#so_ppn").trigger("blur");
    $("#so_discount").trigger("blur");
    $("#so_cost").trigger("blur");

    $(".is-invalid").removeClass("is-invalid");
    hideSoSaveButton();
    $("#add_sales_order").modal("show");
    $("#add_sales_order").attr("so_id", data.so_id);
    $("#add_sales_order").attr("so_number", data.so_number);
    $("#add_sales_order").attr("so_invoice_no", data.so_invoice_no);
    $("#add_sales_order").attr("so_ref_number", data.so_ref_number || "");
}

function initSalesOrderProductInput() {
    const $skuCol = $("#so_sku").closest(".col-lg-6, .col-md-6, .col-12");
    const $inputCol = $skuCol.next(".col-lg-6, .col-md-6, .col-12");
    if ($inputCol.length <= 0) return;
    if ($("#btn-add-product-so").length > 0) return;

    $inputCol.html(`
            <div class="row g-2 align-items-end mb-lg-0 mb-2">
                <div class="col-lg-3 col-md-4 col-5">
                    <div class="input-block mb-0">
                        <label class="form-label text-muted fw-semibold" style="font-size: 11px; text-transform:uppercase; letter-spacing:.4px;">Qty</label>
                        <input type="number" min="1" class="form-control" id="so_qty_input" value="1" placeholder="Qty" style="border-radius: 8px; height:42px;">
                    </div>
                </div>
                <div class="col-lg-5 col-md-4 col-7">
                    <div class="input-block mb-0">
                        <label class="form-label text-muted fw-semibold" style="font-size: 11px; text-transform:uppercase; letter-spacing:.4px;">Satuan</label>
                        <select class="form-select" id="so_unit_input" style="border-radius: 8px; height:42px;">
                            <option value="" selected>Pilih Satuan</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-4 col-md-4 col-12 text-lg-end mt-md-0 mt-2">
                    <button type="button" class="btn w-100 d-inline-flex justify-content-center align-items-center gap-2" id="btn-add-product-so" style="border-radius: 8px; height: 42px; font-weight: 600; background:linear-gradient(135deg,#3b82f6,#2563eb); color:#fff; border:none; box-shadow:0 4px 12px rgba(59,130,246,.3);"><i class="fe fe-plus"></i> Tambah</button>
                </div>
            </div>
        `);
}

var soScanMode = false;

$(document).on("click", "#btn_toggle_scan_so", function () {
    soScanMode = !soScanMode;
    var $skuCol = $("#so_sku").closest(".col-lg-6, .col-md-6, .col-12");
    var $qtyUnitCol = $skuCol.next(".col-lg-6, .col-md-6, .col-12");
    if (soScanMode) {
        $("#so_mode_select").hide();
        $("#so_mode_scan").show();
        $qtyUnitCol.hide();
        $skuCol.removeClass("col-lg-6").addClass("col-lg-12");
        $(this).html('<i class="fa fa-list"></i> Input');
        $(this)
            .removeClass("btn-outline-secondary")
            .addClass("btn-outline-primary");
        $("#so_scan_barcode").focus();
    } else {
        $("#so_mode_scan").hide();
        $("#so_mode_select").show();
        $qtyUnitCol.show();
        $skuCol.removeClass("col-lg-12").addClass("col-lg-6");
        $(this).html('<i class="fa fa-barcode"></i> Scan');
        $(this)
            .removeClass("btn-outline-primary")
            .addClass("btn-outline-secondary");
    }
});

function doScanAddSo() {
    var barcode = $("#so_scan_barcode").val().trim();
    var qty = parseInt($("#so_scan_qty").val()) || 1;
    if (qty < 1) qty = 1;

    if (!barcode) {
        toastr.warning("", "Masukkan barcode/SKU terlebih dahulu");
        return;
    }

    $.ajax({
        url: "/searchProductVariantByScan",
        method: "post",
        data: {
            keyword: barcode,
            _token: $('meta[name="csrf-token"]').attr("content"),
        },
        success: function (res) {
            var results = res.data || [];
            if (results.length === 0) {
                toastr.error(
                    "",
                    "Produk tidak ditemukan untuk barcode: " + barcode,
                );
                $("#so_scan_barcode").val("").focus();
                return;
            }

            var data = results[0];
            if (isActiveRetailWarehouse()) {
                var retailScan = pickRetailUnitInfo(data);
                if (!retailScan) {
                    promptSoRetailUnitSetup(data, function (updated) {
                        doScanAddSoProduct(updated, qty);
                    });
                    return;
                }
            }
            doScanAddSoProduct(data, qty);
        },
        error: function (xhr) {
            if (handlePermissionError(xhr)) return;
            toastr.error("", "Gagal mencari produk");
            $("#so_scan_barcode").val("").focus();
        },
    });
}

function doScanAddSoProduct(data, qty) {
    var defaultUnitId =
        data.unit_id ||
        (data.pr_unit && data.pr_unit.length > 0
            ? data.pr_unit[0].unit_id
            : 0);
    if (isActiveRetailWarehouse()) {
        var retailScan = pickRetailUnitInfo(data);
        if (!retailScan) {
            $("#so_scan_barcode").val("").focus();
            return;
        }
        defaultUnitId = retailScan.unit_id;
    }
    var defaultUnitName = "";
    if (data.pr_unit && Array.isArray(data.pr_unit)) {
        data.pr_unit.forEach(function (u) {
            if (u.unit_id == defaultUnitId) defaultUnitName = u.unit_name;
        });
    }

    var idx = -1;
    products.forEach(function (el, i) {
        if (
            parseInt(el.product_variant_id) ===
                parseInt(data.product_variant_id) &&
            parseInt(el.unit_id) === parseInt(defaultUnitId)
        ) {
            idx = i;
        }
    });

    function commitScanAdd() {
        if (idx === -1) {
            products.push(
                normalizeProductForActiveWarehouse(
                    applyDefaultMainWarehouse(
                        applyDefaultRetailWarehouse({
                            product_variant_id: data.product_variant_id,
                            product_name: data.pr_name || "-",
                            product_variant_name: data.product_variant_name,
                            product_variant_sku: data.product_variant_sku,
                            product_variant_price: data.product_variant_price || 0,
                            so_qty: qty,
                            unit_id: defaultUnitId,
                            unit_name: defaultUnitName,
                            pr_unit: data.pr_unit || [],
                            retail_unit: data.retail_unit || 0,
                            warehouse_id: null,
                            warehouse_name: null,
                        }),
                    ),
                ),
            );
        } else {
            products[idx].so_qty = (parseInt(products[idx].so_qty) || 0) + qty;
        }

        toastr.success(
            "",
            "Berhasil menambahkan: " +
                (data.pr_name || "") +
                " " +
                data.product_variant_name +
                " (x" +
                qty +
                ")",
        );
        refreshTableProduct();
        $("#so_scan_barcode").val("").focus();
    }

    // Sama seperti #btn-add-product-so - lihat catatan GitHub #116 di sana: baris eceran
    // yang gudangnya belum dipilih langsung masuk daftar tanpa cek, baris lain (gudang
    // sudah pasti) dicek SATU baris ini saja.
    var isPendingRetailWarehouse =
        !isActiveRetailWarehouse() &&
        parseInt(data.retail_unit || 0, 10) > 0 &&
        parseInt(defaultUnitId, 10) === parseInt(data.retail_unit, 10);
    if (isPendingRetailWarehouse) {
        commitScanAdd();
        return;
    }

    var whMeta = isActiveRetailWarehouse()
        ? activeRetailWarehouseMeta()
        : activeMainWarehouseMeta();
    var checkQty = idx === -1 ? qty : (parseInt(products[idx].so_qty) || 0) + qty;
    var checkLine = {
        product_variant_id: data.product_variant_id,
        unit_id: defaultUnitId,
        so_qty: checkQty,
        warehouse_id: (idx >= 0 && products[idx].warehouse_id) || whMeta.id || null,
    };

    checkSoStockThenAdd([checkLine], commitScanAdd);
}

$(document).on("click", "#btn_scan_add_so", function () {
    doScanAddSo();
});

$(document).on("keydown", "#so_scan_barcode", function (e) {
    if (e.key === "Enter" || e.keyCode === 13) {
        e.preventDefault();
        doScanAddSo();
    }
});

// Supaya bisa focus saat load modal
$("#add_sales_order").on("shown.bs.modal", function () {});

$(document).on("click", ".btnAdd", function () {
    initSalesOrderProductInput();
    mode = 1;
    products = [];
    $("#tableSalesModal").html("");
    refreshTableProduct();
    $("#add_sales_order .modal-title").html("Tambah Pengiriman");
    $("#add_sales_order input").val("").attr("disabled", false);
    $("#so_customer, #sales_id").empty().attr("disabled", false);
    resetRetailWarehouseSelect();
    $("#so_discount").val(0).trigger("blur");
    $("#so_cost").val(0).trigger("blur");
    $("#so_ppn").val(0).trigger("blur");
    $(".form-select")
        .not("#so_payment, #retail_warehouse_id")
        .empty()
        .attr("disabled", false);
    $("#so_sku, .so_qty, .so_unit, #so_unit_input, #so_qty_input").attr(
        "disabled",
        false,
    );
    $("#so_scan_barcode, #so_scan_qty").attr("disabled", false);
    $("#btn-add-product-so, #btn_scan_add_so, #btn_toggle_scan_so").show();
    $(".is-invalid").removeClass("is-invalid");
    showSoSaveButton(mode == 1 ? "Tambah Pengiriman" : "Update Pengiriman");
    $("#add_sales_order").modal("show");
    hideSoAccButtons();
    setSoProductInputVisible(true);
    // updateTotal();
    $("#btn_bukti_foto").show();
    $("#btn-lihat-bukti").hide();
    $("#check_foto").hide();
    soScanMode = false;
    $("#so_mode_scan").hide();
    $("#so_mode_select").show();
    var $skuCol = $("#so_sku").closest(
        ".col-lg-6, .col-lg-12, .col-md-6, .col-md-12, .col-12",
    );
    $skuCol.next(".col-lg-6, .col-md-6, .col-md-12, .col-12").show();
    $skuCol.removeClass("col-lg-12").addClass("col-lg-6");
    $("#btn_toggle_scan_so")
        .html('<i class="fa fa-barcode"></i> Scan')
        .removeClass("btn-outline-primary")
        .addClass("btn-outline-secondary");
    $("#so_scan_barcode").val("");
    $("#so_scan_qty").val(1);
    $("#so_qty_input").val(1);
    $("#so_unit_input").html('<option value="" selected>Pilih Satuan</option>');
    let today = new Date();
    let yyyy = today.getFullYear();
    let mm = String(today.getMonth() + 1).padStart(2, "0");
    let dd = String(today.getDate()).padStart(2, "0");
    let todayStr = yyyy + "-" + mm + "-" + dd;
    $("#so_date").val(todayStr).attr("disabled", false);
});

$(document).on("blur", "#so_ppn", function () {
    var value = $(this).val();
    viewSummary(value, "ppn");
});

$(document).on("blur", "#so_cost", function () {
    var value = $(this).val();
    viewSummary(value, "cost");
});

$(document).on("blur", "#so_discount", function () {
    var value = $(this).val();
    viewSummary(value, "discount");
});

$("#so_sku").on("change", function () {
    var data = $(this).select2("data")[0] || null;
    fillSoUnitInput(data);
    $("#so_qty_input").val(data && data.product_variant_id ? 1 : "");
});

$(document).on("click", "#btn-add-product-so", function () {
    var temp = $("#so_sku").select2("data")[0];
    var qty = parseInt($("#so_qty_input").val() || 0);
    var unitId = parseInt($("#so_unit_input").val() || 0);

    if (!temp || !temp.product_variant_id) {
        notifikasi(
            "error",
            "Gagal Insert",
            "Silahkan pilih produk terlebih dahulu",
        );
        return false;
    }
    if (!qty || qty <= 0) {
        notifikasi("error", "Qty Tidak Valid", "Qty produk harus lebih dari 0");
        $("#so_qty_input").addClass("is-invalid");
        return false;
    }
    if (!unitId) {
        notifikasi("error", "Gagal Insert", "Silahkan pilih satuan produk");
        $("#so_unit_input").addClass("is-invalid");
        return false;
    }
    if (isActiveRetailWarehouse()) {
        var retailAdd = pickRetailUnitInfo(temp);
        if (!retailAdd) {
            promptSoRetailUnitSetup(temp);
            return false;
        }
        if (unitId !== retailAdd.unit_id) {
            notifikasi(
                "error",
                "Satuan tidak valid",
                "Gudang eceran hanya boleh memakai satuan eceran default",
            );
            return false;
        }
    }

    $("#so_qty_input").removeClass("is-invalid");
    $("#so_unit_input").removeClass("is-invalid");

    var unitText = $("#so_unit_input option:selected").text();
    var idx = -1;
    products.forEach((element, index) => {
        if (
            parseInt(element.product_variant_id) ==
                parseInt(temp.product_variant_id) &&
            parseInt(element.unit_id) == unitId
        ) {
            idx = index;
        }
    });

    function commitSoAdd() {
        if (idx == -1) {
            var data = {
                product_variant_id: temp.product_variant_id,
                product_name: temp.product_name || temp.pr_name || "-",
                product_variant_name: temp.product_variant_name,
                product_variant_sku: temp.product_variant_sku,
                product_variant_price: temp.product_variant_price || 0,
                so_qty: qty,
                unit_id: unitId,
                unit_name: unitText,
                pr_unit: temp.pr_unit || [],
                retail_unit: temp.retail_unit || 0,
                warehouse_id: null,
                warehouse_name: null,
            };
            products.push(
                normalizeProductForActiveWarehouse(
                    applyDefaultMainWarehouse(applyDefaultRetailWarehouse(data)),
                ),
            );
        } else {
            products[idx].so_qty = (parseInt(products[idx].so_qty) || 0) + qty;
        }

        toastr.success("", "Berhasil menambahkan Produk");
        refreshTableProduct();

        $("#so_sku").val(null).trigger("change");
        fillSoUnitInput(null);
        $("#so_qty_input").val(1);
    }

    // Satuan eceran, dan staf tidak sedang "berada" di gudang eceran (gudangnya sendiri
    // sudah pasti) - baris ini belum tahu gudang tujuannya (dipilih belakangan lewat
    // dropdown per baris), jadi TIDAK ada yang bisa dicek sekarang. Cukup masuk ke daftar;
    // cek stok baru jalan begitu dropdown gudang eceran baris itu diisi (lihat handler
    // .so-retail-warehouse) - GitHub #116.
    var isPendingRetailWarehouse =
        !isActiveRetailWarehouse() &&
        parseInt(temp.retail_unit || 0, 10) > 0 &&
        unitId === parseInt(temp.retail_unit, 10);
    if (isPendingRetailWarehouse) {
        commitSoAdd();
        return;
    }

    // Gudangnya sudah pasti di titik ini (gudang utama aktif, atau gudang eceran aktif
    // kalau staf sedang berada di situ) - cek stok baris INI SAJA, bukan seluruh daftar
    // (mengecek ulang baris lama tiap kali baris baru ditambah cuma bikin alert lama
    // nongol lagi berulang-ulang tanpa alasan baru).
    var whMeta = isActiveRetailWarehouse()
        ? activeRetailWarehouseMeta()
        : activeMainWarehouseMeta();
    var checkQty = idx == -1 ? qty : (parseInt(products[idx].so_qty) || 0) + qty;
    var checkLine = {
        product_variant_id: temp.product_variant_id,
        unit_id: unitId,
        so_qty: checkQty,
        warehouse_id: (idx >= 0 && products[idx].warehouse_id) || whMeta.id || null,
    };

    checkSoStockThenAdd([checkLine], commitSoAdd, "#btn-add-product-so");
});

/**
 * Cek stok bahan/produk sebelum baris BENAR-BENAR masuk ke `products` - GitHub #116,
 * meniru pola continueAddProduct()/checkProductionStock() di Produksi (GitHub #101/#105).
 * Dipanggil dengan salinan `products` yang SUDAH termasuk baris baru/gabungan; kalau
 * /checkSalesOrderStock lolos, `onOk()` dijalankan (baris itu dipanggil push oleh caller
 * ke `products` asli) - kalau tidak, popup error/rekomendasi gudang yang sama seperti ACC
 * ditampilkan dan baris TIDAK ditambahkan.
 *
 * Ini murni peringatan dini di UI (lihat catatan di checkSalesOrderStock() backend) -
 * submit akhir (Tambah/Update Pengiriman) tetap tidak diblokir oleh stok saat ini, sesuai
 * keputusan GitHub #99.
 */
function checkSoStockThenAdd(candidateProducts, onOk, $btn, onFail) {
    if ($btn) LoadingButton($btn);
    $.ajax({
        url: "/checkSalesOrderStock",
        method: "post",
        data: {
            products: JSON.stringify(candidateProducts),
            _token: token,
        },
        headers: {
            "X-CSRF-TOKEN": token,
        },
        success: function (e) {
            if ($btn) ResetLoadingButton($btn, '<i class="fe fe-plus"></i> Tambah');
            if (!e || e.status != 1) {
                if (e && e.recommendations && e.recommendations.length) {
                    showStockRecommendModal(e);
                } else {
                    showSoErrorModal(
                        (e && e.header) || "Stok tidak cukup",
                        (e && e.message) || "Stok tidak mencukupi",
                    );
                }
                if (onFail) onFail();
                return;
            }
            onOk();
        },
        error: function (a) {
            if ($btn) ResetLoadingButton($btn, '<i class="fe fe-plus"></i> Tambah');
            if (handlePermissionError(a)) return;
            console.log(a);
            notifikasi(
                "error",
                "Gagal Cek Stok",
                "Terjadi kesalahan saat memeriksa stok. Silakan coba lagi.",
            );
            if (onFail) onFail();
        },
    });
}

var soXhr = null;

function setSalesOrderTableLoading(isLoading) {
    var $wrap = $("#tableSalesOrder-wrap");
    if (!$wrap.length) return;
    $wrap.toggleClass("is-loading", !!isLoading);
}

function bindSalesOrderLoadingEvents($table) {
    $table
        .on("preXhr.dt", function () {
            setSalesOrderTableLoading(true);
        })
        .on("xhr.dt", function () {
            setTimeout(function () {
                setSalesOrderTableLoading(false);
            }, 0);
        });
}

function soMainWarehousesFromDom() {
    var list = [];
    document
        .querySelectorAll('.warehouse-dropdown-item[data-is-main="1"]')
        .forEach(function (el) {
            var id = parseInt(el.getAttribute("data-id"), 10);
            if (id > 0) {
                var span = el.querySelector("span");
                list.push({
                    id: id,
                    warehouse_name: span
                        ? span.textContent.trim()
                        : "Gudang #" + id,
                });
            }
        });
    return list;
}

/** Gudang utama untuk setup satuan eceran — sama logika crMainWarehouse di Pengembalian. */
function soMainWarehouse() {
    var warehouses = soMainWarehousesFromDom();
    if (warehouses.length) {
        var activeId = parseInt(getActiveWarehouseId() || 0, 10);
        var activeMain = warehouses.find(function (warehouse) {
            return parseInt(warehouse.id, 10) === activeId;
        });
        return activeMain || warehouses[0];
    }
    if (
        typeof getMainWarehouseId === "function" &&
        typeof getMainWarehouseName === "function"
    ) {
        var domId = parseInt(getMainWarehouseId(), 10);
        var domName = getMainWarehouseName();
        if (domId > 0 && domName) {
            return { id: domId, warehouse_name: domName };
        }
    }
    return null;
}

function activeMainWarehouseMeta() {
    if (
        typeof isActiveMainWarehouse !== "function" ||
        isActiveMainWarehouse() !== true
    ) {
        return { id: null, name: null };
    }
    var id =
        typeof getActiveWarehouseId === "function"
            ? parseInt(getActiveWarehouseId() || 0, 10)
            : 0;
    if (id <= 0) {
        return { id: null, name: null };
    }
    var wh = window.activeWarehouse || {};
    var name =
        (typeof getActiveWarehouseName === "function"
            ? getActiveWarehouseName()
            : null) ||
        wh.name ||
        wh.warehouse_name ||
        null;
    if (!name) {
        var el =
            typeof getActiveWarehouseEl === "function"
                ? getActiveWarehouseEl()
                : null;
        if (el) {
            var span = el.querySelector("span");
            name = span ? span.textContent.trim() : null;
        }
    }
    return {
        id: id,
        name: name || "Gudang #" + id,
    };
}

function applyDefaultMainWarehouse(product) {
    if (!product || isActiveRetailWarehouse()) {
        return product;
    }
    var retailUnit = parseInt(product.retail_unit || 0, 10);
    var unitId = parseInt(product.unit_id || 0, 10);
    if (retailUnit > 0 && unitId === retailUnit) {
        return product;
    }
    if (parseInt(product.warehouse_id || 0, 10) > 0) {
        return product;
    }
    var meta = activeMainWarehouseMeta();
    if (!meta.id) {
        return product;
    }
    product.warehouse_id = meta.id;
    product.warehouse_name = meta.name;
    return product;
}

function activeRetailWarehouseMeta() {
    if (
        typeof isActiveMainWarehouse === "function" &&
        isActiveMainWarehouse() === true
    ) {
        return { id: null, name: null };
    }
    var id =
        typeof getActiveWarehouseId === "function"
            ? parseInt(getActiveWarehouseId() || 0, 10)
            : 0;
    if (id <= 0) {
        return { id: null, name: null };
    }
    var wh = window.activeWarehouse || {};
    var name =
        (typeof getActiveWarehouseName === "function"
            ? getActiveWarehouseName()
            : null) ||
        wh.name ||
        wh.warehouse_name ||
        null;
    if (!name) {
        var el =
            typeof getActiveWarehouseEl === "function"
                ? getActiveWarehouseEl()
                : null;
        if (el) {
            var span = el.querySelector("span");
            name = span ? span.textContent.trim() : null;
        }
    }
    return {
        id: id,
        name: name || "Gudang #" + id,
    };
}

function isActiveRetailWarehouse() {
    return (
        typeof isActiveMainWarehouse === "function" &&
        isActiveMainWarehouse() === false
    );
}

function pickRetailUnitInfo(data) {
    if (!data) return null;
    var retailUnit = parseInt(data.retail_unit || 0, 10);
    if (retailUnit <= 0) return null;
    var unitName = "";
    if (Array.isArray(data.pr_unit)) {
        data.pr_unit.forEach(function (u) {
            if (parseInt(u.unit_id, 10) === retailUnit) {
                unitName = u.unit_name || unitName;
            }
        });
    }
    if (!unitName && parseInt(data.unit_id, 10) === retailUnit) {
        unitName = data.unit_name || "";
    }
    return {
        unit_id: retailUnit,
        unit_name: unitName || "Satuan eceran",
    };
}

function soProductLabel(product) {
    if (!product) return "produk ini";
    var name = (
        (product.product_name || product.pr_name || "") +
        " " +
        (product.product_variant_name || "")
    ).trim();
    return name || "produk ini";
}

function updateSoProductRetailUnit(product, retailUnitId) {
    if (!product) return;
    product.retail_unit = parseInt(retailUnitId, 10) || null;
    var sel = $("#so_sku").select2("data");
    if (
        sel &&
        sel[0] &&
        parseInt(sel[0].product_variant_id, 10) ===
            parseInt(product.product_variant_id, 10)
    ) {
        sel[0].retail_unit = product.retail_unit;
    }
}

function promptSoRetailUnitSetup(product, onDone) {
    if (!product || typeof Swal === "undefined") return;
    var main = soMainWarehouse();
    var retailId = parseInt(getActiveWarehouseId() || 0, 10);
    if (!main || !main.id || !isActiveRetailWarehouse() || retailId <= 0) {
        if (typeof toastr !== "undefined") {
            toastr.warning(
                "",
                "Pilih gudang eceran di menu atas untuk mengatur satuan eceran produk.",
            );
        }
        return;
    }
    $.post("/getTransferRetailUnitSetup", {
        product_variant_id: product.product_variant_id,
        from_warehouse_id: main.id,
        to_warehouse_id: retailId,
        _token: $('meta[name="csrf-token"]').attr("content"),
    })
        .done(function (res) {
            if (!res || res.status !== 1) {
                if (typeof toastr !== "undefined") {
                    toastr.error(
                        "",
                        (res && res.message) ||
                            "Gagal memeriksa satuan eceran",
                    );
                }
                return;
            }
            if (!res.requires_setup) {
                updateSoProductRetailUnit(product, res.retail_unit_id);
                fillSoUnitInput(product);
                if (typeof onDone === "function") onDone(product);
                return;
            }
            var units = res.units || [];
            if (!units.length) {
                if (typeof toastr !== "undefined") {
                    toastr.error(
                        "",
                        res.message ||
                            "Satuan produk tidak tersedia untuk dijadikan satuan eceran.",
                    );
                }
                return;
            }
            var options = {};
            units.forEach(function (unit) {
                options[String(unit.unit_id)] =
                    unit.unit_name || unit.unit_short_name || "-";
            });
            Swal.fire({
                icon: "warning",
                title: "Satuan eceran belum diatur",
                html:
                    "Pilih satuan eceran untuk <strong>" +
                    escapeHtmlSo(soProductLabel(product)) +
                    "</strong>.",
                input: "select",
                inputOptions: options,
                inputPlaceholder: "Pilih satuan eceran",
                showCancelButton: true,
                confirmButtonText: "Simpan",
                cancelButtonText: "Batal",
                allowOutsideClick: false,
                inputValidator: function (value) {
                    return value ? undefined : "Satuan eceran wajib dipilih";
                },
                preConfirm: function (unitId) {
                    return $.post("/saveTransferRetailUnit", {
                        product_variant_id: product.product_variant_id,
                        unit_id: unitId,
                        from_warehouse_id: main.id,
                        to_warehouse_id: retailId,
                        _token: $('meta[name="csrf-token"]').attr("content"),
                    })
                        .then(function (saveRes) {
                            if (!saveRes || saveRes.status !== 1) {
                                throw new Error(
                                    (saveRes && saveRes.message) ||
                                        "Gagal menyimpan satuan eceran",
                                );
                            }
                            return saveRes;
                        })
                        .catch(function (xhr) {
                            Swal.showValidationMessage(
                                (xhr.responseJSON &&
                                    xhr.responseJSON.message) ||
                                    xhr.message ||
                                    "Gagal menyimpan satuan eceran",
                            );
                        });
                },
            }).then(function (result) {
                if (!result.isConfirmed || !result.value) return;
                updateSoProductRetailUnit(
                    product,
                    result.value.retail_unit_id,
                );
                fillSoUnitInput(product);
                if (typeof onDone === "function") onDone(product);
            });
        })
        .fail(function (xhr) {
            if (handlePermissionError(xhr)) return;
            if (typeof toastr !== "undefined") {
                toastr.error("", "Gagal memeriksa satuan eceran");
            }
        });
}

function normalizeProductForActiveWarehouse(product) {
    if (!product || !isActiveRetailWarehouse()) {
        return product;
    }
    var retail = pickRetailUnitInfo(product);
    if (!retail) {
        return product;
    }
    product.unit_id = retail.unit_id;
    product.unit_name = retail.unit_name;
    return applyDefaultRetailWarehouse(product);
}

function fillSoUnitInput(data) {
    var $unit = $("#so_unit_input");
    $unit.html('<option value="" selected>Pilih Satuan</option>');
    if (!data) {
        $unit.prop("disabled", false);
        return;
    }

    if (isActiveRetailWarehouse()) {
        if (!parseInt(data.retail_unit || 0, 10)) {
            $unit
                .html('<option value="">Atur satuan eceran dulu</option>')
                .prop("disabled", true);
            promptSoRetailUnitSetup(data);
            return;
        }
        var retail = pickRetailUnitInfo(data);
        if (!retail) {
            $unit
                .html('<option value="">Atur satuan eceran dulu</option>')
                .prop("disabled", true);
            promptSoRetailUnitSetup(data);
            return;
        }
        $unit.append(
            '<option value="' +
                retail.unit_id +
                '">' +
                escapeHtmlSo(retail.unit_name) +
                "</option>",
        );
        $unit.val(String(retail.unit_id)).prop("disabled", true);
        return;
    }

    $unit.prop("disabled", false);
    if (Array.isArray(data.pr_unit) && data.pr_unit.length > 0) {
        data.pr_unit.forEach(function (unit) {
            $unit.append(
                '<option value="' +
                    unit.unit_id +
                    '">' +
                    escapeHtmlSo(unit.unit_name) +
                    "</option>",
            );
        });
        $unit.val(
            String(data.default_unit || data.unit_id || data.pr_unit[0].unit_id),
        );
    } else if (data.unit_id && data.unit_name) {
        $unit.append(
            '<option value="' +
                data.unit_id +
                '">' +
                escapeHtmlSo(data.unit_name) +
                "</option>",
        );
        $unit.val(String(data.unit_id));
    }
}

function applyDefaultRetailWarehouse(product) {
    if (!product) return product;
    var retailUnit = parseInt(product.retail_unit || 0, 10);
    var unitId = parseInt(product.unit_id || 0, 10);
    if (retailUnit <= 0 || unitId !== retailUnit) {
        return product;
    }
    if (parseInt(product.warehouse_id || 0, 10) > 0) {
        return product;
    }
    var meta = activeRetailWarehouseMeta();
    if (!meta.id) {
        return product;
    }
    product.warehouse_id = meta.id;
    product.warehouse_name = meta.name;
    return product;
}

function salesOrderAjax(data, callback) {
    if (soXhr && soXhr.readyState !== 4) {
        soXhr.abort();
    }

    var warehouseId =
        typeof getActiveWarehouseId === "function"
            ? getActiveWarehouseId()
            : null;
    if (!warehouseId) {
        callback({
            draw: data.draw,
            recordsTotal: 0,
            recordsFiltered: 0,
            data: [],
        });
        return;
    }

    soXhr = $.ajax({
        url: "/getSalesOrder",
        type: "GET",
        data: $.extend({}, data, { active_warehouse_id: warehouseId }),
        beforeSend: function () {
            setSalesOrderTableLoading(true);
        },
        success: function (json) {
            callback(json);
        },
        error: function (err) {
            if (err && err.statusText === "abort") return;
            if (handlePermissionError(err)) return;
            console.error("Gagal load so:", err);
            callback({
                draw: data.draw,
                recordsTotal: 0,
                recordsFiltered: 0,
                data: [],
            });
        },
        complete: function () {
            setSalesOrderTableLoading(false);
        },
    });
}

function renderSoStatus(status) {
    status = parseInt(status, 10);
    if (status === 1) {
        return '<span class="badge" style="background-color: #fff7ed; color: #ea580c; border: 1px solid #ffedd5; padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 12px; letter-spacing: 0.3px;"><i class="fe fe-clock me-1"></i> Pending</span>';
    }
    if (status === 2) {
        return '<span class="badge" style="background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0; padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 12px; letter-spacing: 0.3px;"><i class="fe fe-check-circle me-1"></i> Diterima</span>';
    }
    if (status === 3) {
        return '<span class="badge" style="background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 12px; letter-spacing: 0.3px;"><i class="fe fe-x-circle me-1"></i> Ditolak</span>';
    }
    if (status === 4) {
        // Dijadwalkan lewat External API (POST /shipments/scheduled) — belum di-ACC, stok belum dipotong.
        return '<span class="badge" style="background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 12px; letter-spacing: 0.3px;"><i class="fe fe-calendar me-1"></i> Dijadwalkan</span>';
    }
    if (status === 5) {
        // Belum Terkirim, dipaksa lewat External API (PATCH /shipments/{ref}/change-status).
        return '<span class="badge" style="background-color: #fef9c3; color: #854d0e; border: 1px solid #fde68a; padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 12px; letter-spacing: 0.3px;"><i class="fe fe-truck me-1"></i> Belum Terkirim</span>';
    }
    if (status === 6) {
        // Sudah Terkirim, dipaksa lewat External API (PATCH /shipments/{ref}/change-status).
        return '<span class="badge" style="background-color: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 12px; letter-spacing: 0.3px;"><i class="fe fe-check-square me-1"></i> Sudah Terkirim</span>';
    }
    if (status === 7) {
        // Dibatalkan lewat External API (PUT /shipments/{ref}/cancel) — stok sudah dikembalikan kalau sebelumnya Berjalan.
        return '<span class="badge" style="background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 12px; letter-spacing: 0.3px;"><i class="fe fe-x-octagon me-1"></i> Dibatalkan</span>';
    }
    return "-";
}

function renderSoAction(row) {
    var soa =
        '<div class="d-flex justify-content-center align-items-center gap-2">';
    var status = parseInt(row.status, 10);
    var pending = status === 1;
    var canView = soHasAccess("Pengiriman", "view");
    var canConfirm = pending && soHasAccess("Pengiriman", "others");
    var canDelete = pending && soHasAccess("Pengiriman", "delete");
    if (canConfirm) {
        soa +=
            '<a class="btn-action-icon btn_confirm btn-action-approve" data-id="' +
            row.so_id +
            '" href="javascript:void(0);" data-bs-toggle="tooltip" title="Konfirmasi"><i class="fe fe-check-circle" style="font-size:14px;"></i></a>';
    } else if (canView) {
        soa +=
            '<a class="btn-action-icon btn_view" data-id="' +
            row.so_id +
            '" href="javascript:void(0);" style="background:#eff6ff;border:1px solid #bfdbfe;color:#2563eb;" data-bs-toggle="tooltip" title="Lihat"><i class="fe fe-eye" style="font-size:14px;"></i></a>';
    }
    if (canDelete) {
        soa +=
            '<a class="btn-action-icon btn_delete" data-id="' +
            row.so_id +
            '" href="javascript:void(0);" style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;" data-bs-toggle="tooltip" title="Hapus"><i class="fe fe-trash-2" style="font-size:14px;"></i></a>';
    }
    soa += "</div>";
    if (!canConfirm && !canView && !canDelete) {
        return '<span class="text-muted small">—</span>';
    }
    return soa;
}

function maybeOpenDeepLinkModals() {
    if (!revisionAutoOpened && revisionSoId) {
        revisionAutoOpened = true;
        loadSalesOrderWithItems(revisionSoId, function (data) {
            cleanRevisionQueryParam();
            if (soHasAccess("Pengiriman", "edit")) {
                openSalesOrderRevisionModal(data);
            } else if (soHasAccess("Pengiriman", "view")) {
                notifikasi(
                    "error",
                    "Akses Ditolak",
                    "Role ini tidak punya akses edit untuk revisi pengiriman.",
                );
            }
        });
    }

    if (!confirmAutoOpened && confirmSoId) {
        confirmAutoOpened = true;
        loadSalesOrderWithItems(confirmSoId, function (data) {
            cleanConfirmQueryParam();
            if (soHasAccess("Pengiriman", "others")) {
                openSalesOrderDetailModal(data, "confirm");
            } else if (soHasAccess("Pengiriman", "view")) {
                openSalesOrderDetailModal(data, "view");
            } else {
                notifikasi(
                    "error",
                    "Akses Ditolak",
                    "Role ini tidak punya akses konfirmasi pengiriman.",
                );
            }
        });
    }
}

function inisialisasi() {
    table = $("#tableSalesOrder").DataTable({
        processing: true,
        serverSide: true,
        deferRender: true,
        bFilter: true,
        sDom: "fBtlpi",
        lengthMenu: [10, 25, 50, 100],
        pageLength: 10,
        ordering: true,
        order: [[4, "asc"]],
        autoWidth: false,
        scrollX: false,
        searchDelay: 400,
        language: {
            search: " ",
            sLengthMenu: "_MENU_",
            searchPlaceholder: "Cari Pengiriman",
            info: "_START_ - _END_ of _TOTAL_ items",
            emptyTable: "Tidak ada data pengiriman",
            zeroRecords: "Pengiriman tidak ditemukan",
            processing:
                '<div><span class="spinner-border spinner-border-sm text-primary" role="status"></span><span>Memuat pengiriman...</span></div>',
            paginate: {
                next: ' <i class=" fa fa-angle-right"></i>',
                previous: '<i class="fa fa-angle-left"></i> ',
            },
        },
        ajax: function (data, callback) {
            salesOrderAjax(data, callback);
        },
        columns: [
            {
                data: "customer_name",
                width: "14%",
                render: function (data) {
                    if (!data || data === "-")
                        return '<span style="color:#64748b;">-</span>';
                    return `<div style="display:flex;align-items:center;gap:10px;">
                                    <div style="width:32px;height:32px;border-radius:8px;background:#eff6ff;border:1px solid #bfdbfe;display:flex;align-items:center;justify-content:center;color:#2563eb;flex-shrink:0;">
                                        <i class="fe fe-truck"></i>
                                    </div>
                                    <span class="fw-semibold text-dark">${data}</span>
                                </div>`;
                },
            },
            {
                data: "so_date",
                width: "14%",
                render: function (data) {
                    if (!data || data === "-")
                        return '<span style="color:#64748b;">-</span>';
                    var dateFmt = moment(data).format("D MMM YYYY");
                    return `<div style="display:flex;align-items:center;gap:10px;">
                                    <div style="width:32px;height:32px;border-radius:8px;background:#f8fafc;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;color:#64748b;flex-shrink:0;">
                                        <i class="fe fe-calendar"></i>
                                    </div>
                                    <span class="fw-semibold text-dark">${dateFmt}</span>
                                </div>`;
                },
            },
            {
                data: "so_invoice_no",
                defaultContent: "-",
                width: "11%",
                className: "text-center",
                render: function (data) {
                    if (!data || data === "-") return "-";
                    return `<span class="badge" style="background:#f1f5f9;color:#334155;border:1px solid #cbd5e1;padding:6px 10px;">${data}</span>`;
                },
            },
            {
                data: "so_ref_number",
                defaultContent: "-",
                width: "10%",
                className: "text-center",
                render: function (data) {
                    var raw = (data || "").toString().trim();
                    if (!raw || raw === "-") return "-";
                    return `<span class="badge" style="background:#f8fafc;color:#475569;border:1px solid #e2e8f0;padding:6px 10px;">${raw}</span>`;
                },
            },
            {
                data: "status",
                width: "12%",
                className: "text-center",
                render: function (data) {
                    return renderSoStatus(data);
                },
            },
            {
                data: "created_by_name",
                defaultContent: "-",
                width: "14%",
                render: function (data) {
                    return typeof renderCreatedByName === "function"
                        ? renderCreatedByName(data)
                        : data;
                },
            },
            {
                data: "acc_by_name",
                defaultContent: "-",
                width: "14%",
                render: function (data) {
                    if (!data || data === "-")
                        return '<span style="color:#64748b;">-</span>';
                    return `<div style="display:flex;align-items:center;gap:10px;">
                                    <div style="width:32px;height:32px;border-radius:8px;background:#ecfdf5;border:1px solid #a7f3d0;display:flex;align-items:center;justify-content:center;color:#059669;flex-shrink:0;">
                                        <i class="fe fe-user-check"></i>
                                    </div>
                                    <span class="fw-semibold text-dark">${data}</span>
                                </div>`;
                },
            },
            {
                data: null,
                className: "text-center align-middle",
                width: "11%",
                orderable: false,
                searchable: false,
                render: function (data, type, row) {
                    return renderSoAction(row);
                },
            },
        ],
        initComplete: function () {
            var $filter = $(".dataTables_filter").last();
            $filter.appendTo("#tableSearch");
            $filter.appendTo(".search-input");
            if (!$filter.find("label .fa-search").length) {
                $filter.find("label").prepend('<i class="fa fa-search"></i> ');
            }
            $("#tableSalesOrder-wrap")
                .removeClass("dt-pending")
                .addClass("dt-ready");
            if (table) table.columns.adjust();
        },
        drawCallback: function () {
            setSalesOrderTableLoading(false);
            if (typeof feather !== "undefined") feather.replace();
            if (table) table.columns.adjust();
            maybeOpenDeepLinkModals();
        },
    });

    bindSalesOrderLoadingEvents($("#tableSalesOrder"));
}

function refreshSalesOrder() {
    if (!table) return;
    table.ajax.reload(null, false);
}

function refreshTableProduct() {
    $("#tableSalesModal").html("");
    var html = "";
    var isViewMode = mode === 3;
    products.forEach((p, index) => {
        if (!isViewMode) {
            normalizeProductForActiveWarehouse(p);
        }
        let options = "";
        var unitList;
        if (isViewMode) {
            unitList = (p.pr_unit || []).filter(function (u) {
                return parseInt(u.unit_id, 10) === parseInt(p.unit_id, 10);
            });
            if (unitList.length === 0 && p.unit_id && p.unit_name) {
                unitList = [{ unit_id: p.unit_id, unit_name: p.unit_name }];
            }
        } else {
            unitList =
                isActiveRetailWarehouse() && parseInt(p.retail_unit || 0, 10) > 0
                    ? (p.pr_unit || []).filter(function (u) {
                          return (
                              parseInt(u.unit_id, 10) ===
                              parseInt(p.retail_unit || 0, 10)
                          );
                      })
                    : p.pr_unit || [];
        }
        if (unitList && Array.isArray(unitList) && unitList.length > 0) {
            unitList.forEach((u) => {
                options += `<option value="${u.unit_id}" ${u.unit_id == p.unit_id ? "selected" : ""}>${escapeHtmlSo(u.unit_name)}</option>`;
            });
        } else {
            options = `<option value="${p.unit_id}" ${p.unit_id == p.unit_id ? "selected" : ""}>${escapeHtmlSo(p.unit_name)}</option>`;
        }
        var unitSelectDisabled =
            !isViewMode && isActiveRetailWarehouse() ? "disabled" : "";
        if (!isViewMode) {
            applyDefaultRetailWarehouse(p);
        }
        applyDefaultMainWarehouse(p);
        var isRetail =
            parseInt(p.retail_unit || 0, 10) > 0 &&
            parseInt(p.unit_id || 0, 10) === parseInt(p.retail_unit || 0, 10);
        var warehouseCell;
        if (isActiveRetailWarehouse() && !isViewMode) {
            var whMeta = activeRetailWarehouseMeta();
            if (whMeta.id) {
                p.warehouse_id = whMeta.id;
                p.warehouse_name = whMeta.name;
            }
            warehouseCell =
                '<span class="so-retail-warehouse-label"><i class="fe fe-home"></i> ' +
                escapeHtmlSo(whMeta.name || "Gudang aktif") +
                "</span>";
        } else if (isViewMode && isRetail) {
            var viewRetailMeta = activeRetailWarehouseMeta();
            warehouseCell =
                '<span class="so-retail-warehouse-label"><i class="fe fe-home"></i> ' +
                escapeHtmlSo(
                    p.warehouse_name ||
                        viewRetailMeta.name ||
                        "Gudang eceran",
                ) +
                "</span>";
        } else if (!isViewMode && isRetail) {
            warehouseCell = `<select class="form-control so-retail-warehouse" data-index="${index}">
                        ${
                            p.warehouse_id
                                ? `<option value="${p.warehouse_id}" selected>${escapeHtmlSo(p.warehouse_name || "Gudang #" + p.warehouse_id)}</option>`
                                : ""
                        }
                   </select>`;
        } else {
            var mainWhMeta = activeMainWarehouseMeta();
            warehouseCell =
                '<span class="so-main-warehouse"><i class="fe fe-home"></i> ' +
                escapeHtmlSo(
                    p.warehouse_name ||
                        mainWhMeta.name ||
                        "Gudang Utama",
                ) +
                "</span>";
        }

        var actionCell = isViewMode
            ? ""
            : `<td class="text-center" style="padding: 12px 24px;">
                        <a class="deleteRow d-inline-flex align-items-center justify-content-center mx-auto" index="${index}" href="javascript:void(0);" style="width: 32px; height: 32px; background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; border-radius: 8px; transition: all 0.2s ease;" title="Hapus Produk" onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='#fef2f2'">
                            <i class="fe fe-trash-2" style="font-size: 14px;"></i>
                        </a>
                    </td>`;

        html += `
                <tr class="align-middle" style="border-bottom: 1px solid #f1f5f9; transition: all 0.2s ease;">
                    <td style="padding: 12px 24px; font-weight: 600; color: #334155;">${escapeHtmlSo(p.product_name || p.pr_name || "-")}</td>
                    <td style="padding: 12px 24px; color: #64748b; font-size:12px;">${escapeHtmlSo(p.product_variant_name || "-")}</td>
                    <td style="padding: 12px 24px;">
                        <span style="background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; padding: 4px 10px; border-radius: 6px; font-family: monospace; font-size: 11px; font-weight: 700; letter-spacing: 0.5px;">
                            ${escapeHtmlSo(p.product_variant_sku || "-")}
                        </span>
                    </td>
                    <td style="padding: 12px 24px;">
                        <div class="d-flex justify-content-center align-items-center">
                            <input type="text" class="form-control fill number-only so_qty text-center px-2"
                                data-price="${p.product_variant_price}"
                                data-index="${index}" style="width: 4rem; border-radius: 8px 0 0 8px; border-right: none;" value="${p.so_qty || 1}">
                            <select class="form-select fill so_unit" style="width: 7.5rem; border-radius: 0 8px 8px 0; background-color: #f8fafc;" data-index="${index}" ${unitSelectDisabled}>${options}</select>
                        </div>
                    </td>
                    <td style="padding: 12px 16px;">${warehouseCell}</td>
                    ${actionCell}
                </tr>
            `;
    });
    $("#add_sales_order thead th:last-child").toggle(!isViewMode);
    $("#tableSalesModal").append(html);
    initSalesOrderWarehouseSelects();
    if (typeof feather !== "undefined") feather.replace();
    $(".so_qty").trigger("blur");
}

function initSalesOrderWarehouseSelects() {
    $("#tableSalesModal .so-retail-warehouse").each(function () {
        var selector = "#" + ($(this).attr("id") || "");
        if (!$(this).attr("id")) {
            $(this).attr("id", "so_retail_warehouse_" + $(this).data("index"));
            selector = "#" + $(this).attr("id");
        }
        if (typeof autocompleteWarehouse === "function") {
            autocompleteWarehouse(selector, "#add_sales_order .modal-content", {
                retailOnly: true,
                placeholder: "Pilih gudang eceran",
            });
        }
        if (mode === 3) {
            $(this).prop("disabled", true);
        }
    });
}

$(document).on("blur", ".so_qty", function () {
    const index = $(this).data("index");
    let qty = parseInt($(this).val());
    let price = parseInt($(this).data("price"));
    let subtotal = qty * price;

    $(this).closest("tr").find(".subtotal").html(subtotal);
    products[index].so_qty = qty;
    products[index].so_subtotal = subtotal;
    if (qty == 0) {
        products.splice(index, 1);
        refreshTableProduct();
    }
    // updateTotal();
});

$(document).on("change", ".so_unit", function () {
    const index = $(this).data("index");
    let unit = parseInt($(this).val(), 10);
    if (isActiveRetailWarehouse()) {
        var retailUnit = parseInt(products[index].retail_unit || 0, 10);
        if (retailUnit > 0) {
            unit = retailUnit;
        }
    }
    products[index].unit_id = unit;
    // Selalu kosongkan gudang baris ini begitu satuannya berubah - termasuk saat BERUBAH
    // MENJADI satuan eceran, yang sebelumnya luput (kondisinya cuma cek "!== unit", jadi
    // warehouse_id lama - technicalnya gudang UTAMA dari satuan sebelumnya - malah ikut
    // kebawa dan tampil ke-pre-select di dropdown gudang eceran, padahal gudang itu bukan
    // gudang eceran sama sekali). User harus pilih manual lagi, yang otomatis memicu cek
    // stok baris itu lewat handler .so-retail-warehouse (GitHub #116).
    products[index].warehouse_id = null;
    products[index].warehouse_name = null;
    refreshTableProduct();
});

$(document).on("change", ".so-retail-warehouse", function () {
    var $select = $(this);
    var index = parseInt($select.data("index"), 10);
    if (!products[index]) return;

    var newWarehouseId = parseInt($select.val(), 10) || null;
    var newWarehouseName = $select.find("option:selected").text() || null;
    var previousWarehouseId = products[index].warehouse_id || null;
    var previousWarehouseName = products[index].warehouse_name || null;

    if (!newWarehouseId) {
        products[index].warehouse_id = null;
        products[index].warehouse_name = null;
        return;
    }

    $select
        .next(".select2-container")
        .find(".select2-selection")
        .removeClass("is-invalids");

    // Cek stok baris ini SAJA terhadap gudang eceran yang baru dipilih - GitHub #116.
    // Kalau gagal, kembalikan dropdown ke pilihan sebelumnya (bukan biarkan tampil
    // seolah pilihan tadi tersimpan) supaya user harus pilih gudang lain.
    checkSoStockThenAdd(
        [
            {
                product_variant_id: products[index].product_variant_id,
                unit_id: products[index].unit_id,
                so_qty: products[index].so_qty,
                warehouse_id: newWarehouseId,
            },
        ],
        function () {
            products[index].warehouse_id = newWarehouseId;
            products[index].warehouse_name = newWarehouseName;
        },
        null,
        function () {
            products[index].warehouse_id = previousWarehouseId;
            products[index].warehouse_name = previousWarehouseName;
            revertSoRetailWarehouseSelect(
                $select,
                previousWarehouseId,
                previousWarehouseName,
            );
        },
    );
});

/**
 * .so-retail-warehouse is an ajax/remote Select2 (autocompleteWarehouse()) - it keeps NO
 * static <option> list, only whatever was last picked, so a plain $select.val(id) can't find
 * an option to select back to on revert (silently no-ops, leaving the rejected pick visually
 * selected - the bug reported after the stock-scoping fix above). Rebuild the option element
 * for the previous pick instead, same technique Select2 itself recommends for a
 * programmatic/ajax option: `new Option(text, value, true, true)`.
 */
function revertSoRetailWarehouseSelect($select, warehouseId, warehouseName) {
    if (!warehouseId) {
        $select.val(null).trigger("change.select2");
        return;
    }
    var option = new Option(
        warehouseName || "Gudang #" + warehouseId,
        warehouseId,
        true,
        true,
    );
    $select.empty().append(option).trigger("change.select2");
}

function updateTotal() {
    let total = 0;
    $(".subtotal").each(function () {
        total += parseInt($(this).text().replace(/,/g, "")) || 0;
    });
    $("#value_total").html(`Rp ${formatRupiah(total)}`);
    // update summary
    $("#so_ppn").trigger("blur");
    $("#so_discount").trigger("blur");
    $("#so_cost").trigger("blur");
    grandTotal();
}

function viewSummary(value, from) {
    var total = convertToAngka($("#value_total").html());
    var hasil = 0;
    value = convertToAngka(value);
    if (from == "cost") hasil = value;
    else hasil += total * (value / 100);
    $(`#value_${from}`).html(`Rp ${formatRupiah(hasil)}`);
    grandTotal();
}

function grandTotal() {
    var total = convertToAngka($("#value_total").html());
    var ppn = convertToAngka($("#value_ppn").html());
    var discount = convertToAngka($("#value_discount").html());
    var cost = convertToAngka($("#value_cost").html());
    var grand = total + ppn - discount + cost;
    $("#value_grand").html(`Rp ${formatRupiah(grand)}`);
}

function getFallbackProductNames(items) {
    if (!Array.isArray(items)) return [];
    return [
        ...new Set(
            items
                .map(
                    (item) =>
                        item.product_variant_name ||
                        item.product_name ||
                        item.pr_name ||
                        item.sod_variant ||
                        item.sod_nama,
                )
                .filter(
                    (name) => typeof name === "string" && name.trim() !== "",
                ),
        ),
    ];
}

function normalizeProductErrorMessage(message, fallbackNames) {
    var text = (message || "").toString().trim();
    var names = (fallbackNames || []).filter(
        (name) => typeof name === "string" && name.trim() !== "",
    );

    if (text === "") {
        return names.length ? names.join(", ") : "-";
    }

    // Jika backend mengirim ".... :" tanpa nama produk, isi dari fallback.
    if (/:$/.test(text) && names.length) {
        return `${text} ${names.join(", ")}`;
    }

    return text;
}

function isEmptyVal(v) {
    return v == null || v === "null" || v === "";
}

function resetRetailWarehouseSelect() {
    var $sel = $("#retail_warehouse_id");
    if (!$sel.length) return;
    $sel.empty().val(null).prop("disabled", false);
    if ($sel.hasClass("select2-hidden-accessible")) {
        $sel.trigger("change.select2");
    }
}

function fillRetailWarehouse(data) {
    var $sel = $("#retail_warehouse_id");
    if (!$sel.length) return;
    $sel.empty();
    var wid = parseInt(data && data.retail_warehouse_id, 10);
    var wname = (data && data.retail_warehouse_name) || "";
    if (wid > 0) {
        $sel.append(
            '<option value="' +
                wid +
                '" selected>' +
                escapeHtmlSo(wname || "Gudang #" + wid) +
                "</option>",
        );
    }
    $sel.val(wid > 0 ? String(wid) : null);
    if ($sel.hasClass("select2-hidden-accessible")) {
        $sel.trigger("change.select2");
    }
}

function cartNeedsRetailWarehouse() {
    return (products || []).some(function (p) {
        var retailUnit = parseInt(p.retail_unit || 0, 10);
        var unitId = parseInt(p.unit_id || 0, 10);
        return retailUnit > 0 && unitId === retailUnit;
    });
}

function firstRetailWarehouseId() {
    var row = (products || []).find(function (p) {
        var retailUnit = parseInt(p.retail_unit || 0, 10);
        return (
            retailUnit > 0 &&
            parseInt(p.unit_id || 0, 10) === retailUnit &&
            parseInt(p.warehouse_id || 0, 10) > 0
        );
    });
    return row ? parseInt(row.warehouse_id, 10) : null;
}

function markSelect2Invalid($select, rowSelector) {
    $select.addClass("is-invalid");
    var $container = $select.next(".select2-container");
    if (!$container.length && rowSelector) {
        $container = $(rowSelector).find(".select2-container").first();
    }
    var $sel = $container.find(".select2-selection");
    if ($sel.length) {
        $sel.addClass("is-invalids");
    } else if (rowSelector) {
        $(rowSelector + " .select2-selection").addClass("is-invalids");
    }
}

$(document).on("click", ".btn-save", function () {
    LoadingButton(this);
    $(".is-invalid").removeClass("is-invalid");
    $(".is-invalids").removeClass("is-invalids");
    $("#btn_bukti_foto").removeClass("border-danger text-danger");
    var url = "/insertSalesOrder";
    var missing = [];

    if (isEmptyVal($("#so_date").val())) {
        $("#so_date").addClass("is-invalid");
        missing.push("Tanggal");
    }

    if (isEmptyVal($("#so_customer").val())) {
        markSelect2Invalid($("#so_customer"), "#row-Armada");
        missing.push("Nama Armada");
    }

    var missingRetailWarehouse = false;
    var invalidRetailUnit = false;
    (products || []).forEach(function (p, index) {
        if (isActiveRetailWarehouse()) {
            var retailUnit = parseInt(p.retail_unit || 0, 10);
            if (retailUnit <= 0 || parseInt(p.unit_id || 0, 10) !== retailUnit) {
                invalidRetailUnit = true;
            }
        }
        var isRetail =
            parseInt(p.retail_unit || 0, 10) > 0 &&
            parseInt(p.unit_id || 0, 10) === parseInt(p.retail_unit || 0, 10);
        if (isRetail && parseInt(p.warehouse_id || 0, 10) <= 0) {
            missingRetailWarehouse = true;
            markSelect2Invalid($("#so_retail_warehouse_" + index));
        }
    });
    if (invalidRetailUnit) {
        missing.push("Satuan eceran default pada item produk");
    }
    if (missingRetailWarehouse) {
        missing.push("Gudang Eceran pada item produk");
    }

    var hasPhoto =
        typeof hasPhotoInputValue === "function"
            ? hasPhotoInputValue($("#bukti").val())
            : !isEmptyVal($("#bukti").val());
    if (!hasPhoto) {
        $("#btn_bukti_foto").addClass("border-danger text-danger");
        missing.push("Bukti Foto");
    }

    var needProduct = $("#tableSalesModal").html() == "" || !products.length;

    if (missing.length || needProduct) {
        var msgParts = [];
        if (missing.length === 1) {
            msgParts.push("Mohon isi input " + missing[0]);
        } else if (missing.length > 1) {
            var last = missing.pop();
            msgParts.push(
                "Mohon isi input " + missing.join(", ") + ", dan " + last,
            );
        }
        if (needProduct) {
            msgParts.push("Mohon tambahkan minimal 1 produk");
        }
        notifikasi("error", "Data belum lengkap", msgParts.join(". ") + ".");
        ResetLoadingButton(
            ".btn-save",
            mode == 1 ? "Tambah Pengiriman" : "Update Pengiriman",
        );
        return false;
    }

    param = {
        so_customer: $("#so_customer").val(),
        sales_id: $("#sales_id").val(),
        so_date: $("#so_date").val(),
        so_invoice_no: $("#so_invoice_no").val(),
        so_ref_number: $("#so_ref_number").val(),
        // Backward compatibility untuk data lama; routing baru memakai
        // warehouse_id pada masing-masing item produk.
        retail_warehouse_id: firstRetailWarehouseId(),
        so_total: 0,
        products: JSON.stringify(products),

        _token: token,
    };

    if (mode == 2) {
        url = "/updateSalesOrder";
        param.so_id = $("#add_sales_order").attr("so_id");
        param.so_number = $("#add_sales_order").attr("so_number");
        param.so_invoice_no = $("#add_sales_order").attr("so_invoice_no");
        param.so_ref_number = $("#add_sales_order").attr("so_ref_number") || "";
    } else {
        param.so_img = $("#bukti").val();
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
            console.log(e);
            if (e != 1) {
                if (typeof e === "object") {
                    ResetLoadingButton(
                        ".btn-save",
                        mode == 1 ? "Tambah Pengiriman" : "Update Pengiriman",
                    );
                    if (
                        Array.isArray(e.recommendations) &&
                        e.recommendations.length
                    ) {
                        showStockRecommendModal(e);
                    } else {
                        var fallbackNames = getFallbackProductNames(products);
                        var messageText = normalizeProductErrorMessage(
                            e.message,
                            fallbackNames,
                        );
                        notifikasi("error", e.header || "Gagal", messageText);
                    }
                    return false;
                } else {
                    ResetLoadingButton(
                        ".btn-save",
                        mode == 1 ? "Tambah Pengiriman" : "Update Pengiriman",
                    );
                    var fallbackNames = getFallbackProductNames(products);
                    var stockText = normalizeProductErrorMessage(
                        e,
                        fallbackNames,
                    );
                    notifikasi(
                        "error",
                        "Gagal Update",
                        "Stock Product yang tidak mencukupi : " + stockText,
                    );
                }
            } else {
                ResetLoadingButton(
                    ".btn-save",
                    mode == 1 ? "Tambah Pengiriman" : "Update Pengiriman",
                );
                afterInsert();
            }
        },
        error: function (e) {
            ResetLoadingButton(
                ".btn-save",
                mode == 1 ? "Tambah Pengiriman" : "Update Pengiriman",
            );
            if (handlePermissionError(e)) return;
            console.log(e);
        },
    });
});

function afterInsert() {
    $(".modal").modal("hide");
    if (mode == 1)
        notifikasi("success", "Berhasil Insert", "Berhasil Tambah Pengiriman");
    else if (mode == 2)
        notifikasi("success", "Berhasil Update", "Berhasil Update Pengiriman");
    refreshSalesOrder();
}

// $(document).on("keyup","#filter_category_name",function(){
//     refreshSalesOrder();
// });

$(document).on("click", ".deleteRow", function () {
    if (mode === 3) return;
    var index = $(this).attr("index");
    console.log(index);
    products.splice(index, 1);
    refreshTableProduct();
});

function openSalesOrderEditModal(data) {
    products = [];
    mode = 2;
    $("#add_sales_order .modal-title").html("Update Pengiriman");
    // reset
    $("#add_sales_order input").empty().val("");
    $("#so_customer, #sales_id").empty();
    $("#so_discount").val(0).trigger("blur");
    $("#so_cost").val(0).trigger("blur");
    $("#so_ppn").val(0).trigger("blur");
    $(".form-select").not("#so_payment, #retail_warehouse_id").empty();

    $("#btn_bukti_foto").hide();
    $("#btn-lihat-bukti").show();

    var img =
        typeof parsePhotoInputValue === "function"
            ? parsePhotoInputValue(data.so_img)
            : [];
    list_photo = img;

    if (img.length > 0) {
        $("#modalViewPhoto .modal-footer").show();
        $("#fotoProduksiImage").attr("src", public + "issue/" + img[0]);
        $("#fotoProduksiImage").attr("index", 0);
        $("#btn_download_photo").attr("href", public + "issue/" + img[0]);
        $("#check_foto").show();
        $("#jumlahFoto").html(list_photo.length);
    } else {
        $("#btn-lihat-bukti").hide();
        $("#check_foto").hide();
    }

    $("#so_customer").append(
        `<option value="${data.so_customer}">${data.customer_name}</option>`,
    );
    if (data.so_cashier)
        $("#sales_id").append(
            `<option value="${data.so_cashier}">${data.staff_name}</option>`,
        );
    fillRetailWarehouse(data);
    $("#so_date").val(data.so_date);
    $("#so_invoice_no").val(data.so_invoice_no);
    $("#so_ref_number").val(data.so_ref_number || "");
    $("#so_payment").val(data.so_payment);
    $("#bukti").val(data.so_img);
    (data.items || []).forEach((e) => {
        var temp = {
            sod_id: e.sod_id,
            product_variant_id: e.product_variant_id,
            product_name: e.sod_nama,
            product_variant_name: e.sod_variant,
            product_variant_sku: e.sod_sku,
            so_qty: e.sod_qty,
            product_variant_price: e.sod_harga,
            so_subtotal: e.sod_subtotal,
            unit_name: e.unit_name,
            unit_id: e.unit_id,
            pr_unit: e.pr_unit,
            retail_unit: e.retail_unit || 0,
            warehouse_id:
                e.warehouse_id ||
                (parseInt(e.unit_id, 10) === parseInt(e.retail_unit || 0, 10)
                    ? data.retail_warehouse_id || null
                    : null),
            warehouse_name:
                e.warehouse_name || data.retail_warehouse_name || null,
        };
        products.push(temp);
    });
    refreshTableProduct();
    $("#so_sku, .so_qty, .so_unit, #so_unit_input, #so_qty_input").attr(
        "disabled",
        true,
    );
    $("#so_scan_barcode, #so_scan_qty").attr("disabled", true);
    $("#btn-add-product-so, #btn_scan_add_so, #btn_toggle_scan_so").hide();

    // update summary
    $("#so_ppn").trigger("blur");
    $("#so_discount").trigger("blur");
    $("#so_cost").trigger("blur");

    $(".is-invalid").removeClass("is-invalid");
    showSoSaveButton(mode == 1 ? "Tambah Pengiriman" : "Update Pengiriman");
    hideSoAccButtons();
    setSoProductInputVisible(true);
    $("#add_sales_order").modal("show");
    $("#add_sales_order").attr("so_id", data.so_id);
    $("#add_sales_order").attr("so_number", data.so_number);
    $("#add_sales_order").attr("so_invoice_no", data.so_invoice_no);
    $("#add_sales_order").attr("so_ref_number", data.so_ref_number || "");
}

//edit
$(document).on("click", "#tableSalesOrder-wrap .btn_edit", function (e) {
    e.preventDefault();
    var soId = parseInt($(this).attr("data-id"), 10);
    if (!soId) return;
    loadSalesOrderWithItems(soId, openSalesOrderEditModal);
});

$(document).on("click", "#tableSalesOrder-wrap .btn_view", function (e) {
    e.preventDefault();
    var soId = parseInt($(this).attr("data-id"), 10);
    if (!soId) return;
    loadSalesOrderWithItems(soId, function (data) {
        openSalesOrderDetailModal(data, "view");
    });
});

$(document).on("click", "#tableSalesOrder-wrap .btn_confirm", function (e) {
    e.preventDefault();
    var soId = parseInt($(this).attr("data-id"), 10);
    if (!soId) return;
    loadSalesOrderWithItems(soId, function (data) {
        openSalesOrderDetailModal(data, "confirm");
    });
});

//delete
$(document).on("click", "#tableSalesOrder-wrap .btn_delete", function (e) {
    e.preventDefault();
    var soId = parseInt($(this).attr("data-id"), 10);
    if (!soId) return;
    showModalDelete(
        "Apakah yakin ingin menghapus pengiriman ini?",
        "btn-delete-sales",
    );
    $("#btn-delete-sales").attr("so_id", soId);
});

$(document).on("click", "#btn-delete-sales", function () {
    $.ajax({
        url: "/deleteSalesOrder",
        data: {
            so_id: $("#btn-delete-sales").attr("so_id"),
            _token: token,
        },
        method: "post",
        success: function (e) {
            $(".modal").modal("hide");
            refreshSalesOrder();
            notifikasi(
                "success",
                "Berhasil Delete",
                "Berhasil delete pengiriman",
            );
        },
        error: function (e) {
            if (handlePermissionError(e)) return;
            console.log(e);
        },
    });
});

$(document).on("click", ".btn_acc", function () {
    // var data = $('#tableSalesOrder').DataTable().row($(this).parents('tr')).data();//ambil data dari table
    var so_id = $(this).attr("so_id");
    var item = $(this).data("items");
    $(".modal").modal("hide");
    showModalKonfirmasi(
        "Apakah yakin ingin Approve pengiriman ini?",
        "btn-accept-so",
    );
    console.log(item);
    $("#btn-accept-so").attr("so_id", so_id);
    $("#btn-accept-so").data(
        "fallback_products",
        getFallbackProductNames(item || []),
    );
    $("#btn-accept-so").html("Konfirmasi");
});

$(document).on("click", "#btn-accept-so", function () {
    LoadingButton(this);
    $.ajax({
        url: "/accSO",
        data: {
            so_id: $("#btn-accept-so").attr("so_id"),
            _token: token,
        },
        method: "post",
        success: function (e) {
            if (e != 1) {
                if (typeof e === "object") {
                    ResetLoadingButton(".btn-konfirmasi", "Konfirmasi");
                    if (
                        Array.isArray(e.recommendations) &&
                        e.recommendations.length
                    ) {
                        showStockRecommendModal(e);
                    } else {
                        var fallbackNames =
                            $("#btn-accept-so").data("fallback_products") || [];
                        var messageText = normalizeProductErrorMessage(
                            e.message,
                            fallbackNames,
                        );
                        showSoErrorModal(e.header || "Gagal ACC", messageText);
                    }
                    if (e.status == -2) {
                        $(".modal").modal("hide");
                        refreshSalesOrder();
                    }
                }
            } else {
                ResetLoadingButton(".btn-konfirmasi", "Konfirmasi");
                refreshSalesOrder();
                $(".modal").modal("hide");
                notifikasi("success", "Berhasil Terima", "Berhasil Terima Pengiriman");
            }
        },
        error: function (e) {
            ResetLoadingButton(".btn-konfirmasi", "Konfirmasi");
            if (handlePermissionError(e)) return;
            console.log(e);
        }
        });
    })

function formatStockRecommendations(res) {
    var parts = [];
    (res.recommendations || []).forEach(function (r) {
        var line =
            (r.product || "-") +
            " / " +
            (r.unit || "-") +
            ": stok di " +
            (r.warehouse_name || "-") +
            " kurang (butuh " +
            (r.need || 0) +
            ", tersedia " +
            (r.available || 0) +
            ")";
        if (r.available_at && r.available_at.length) {
            var opts = r.available_at.map(function (a) {
                return (
                    (a.warehouse_name || "-") + " (" + (a.available || 0) + ")"
                );
            });
            line += ". Rekomendasi pindah: " + opts.join(", ");
        } else {
            line += ". Tidak ada gudang lain dengan stok cukup.";
        }
        parts.push(line);
    });
    return parts.length ? parts.join(" | ") : res.message || "Stok tidak cukup";
}

function collectRecommendWarehouses(res) {
    var seen = {};
    var opts = [];
    (res.recommendations || []).forEach(function (r) {
        (r.available_at || []).forEach(function (a) {
            var id = String(a.warehouse_id);
            if (!id || seen[id]) return;
            seen[id] = true;
            opts.push({
                warehouse_id: a.warehouse_id,
                warehouse_name: a.warehouse_name || "Gudang #" + a.warehouse_id,
                available: a.available || 0,
            });
        });
    });
    return opts;
}

// Popup error bertema PG (rounded-4/16px, tombol pg-btn-*) — dipakai di alur ACC Pengiriman
// supaya konsisten dengan pg-modal--danger, dan MUNCUL DI DEPAN #modalKonfirmasi (lihat
// .swal2-container z-index di pg-modal-styles.blade.php) alih-alih notifikasi() polos yang
// dulu tertutup modal konfirmasi hijau yang masih terbuka di belakangnya.
function showSoErrorModal(header, message) {
    Swal.fire({
        icon: "error",
        iconColor: "#ef4444",
        title: header || "Gagal ACC",
        html:
            '<p class="text-start mb-0" style="font-size:14px;white-space:pre-wrap;">' +
            escapeHtmlSo(message || "") +
            "</p>",
        confirmButtonText: "Tutup",
        customClass: {
            confirmButton: "pg-btn-confirm pg-btn-confirm--danger",
            title: "fw-bold fs-4 text-dark",
            popup: "rounded-4",
        },
        buttonsStyling: false,
    }).then(function () {
        // Tutup #modalKonfirmasi juga begitu popup error ditutup (tombol Tutup, klik luar,
        // atau Esc) — kalau tidak, user harus klik dua kali: sekali untuk popup error, sekali
        // lagi untuk modal konfirmasi hijau yang masih terbuka di belakangnya. Aman dipanggil
        // walau modal itu tidak sedang terbuka (mis. dipanggil dari alur tambah/update).
        $("#modalKonfirmasi").modal("hide");
    });
}

function showStockRecommendModal(res) {
    var opts = collectRecommendWarehouses(res);
    var summary = formatStockRecommendations(res);

    if (!opts.length) {
        showSoErrorModal(res.header || "Stok tidak cukup", summary);
        return;
    }

    var optionsHtml = opts
        .map(function (a, i) {
            return (
                '<option value="' +
                a.warehouse_id +
                '"' +
                (i === 0 ? " selected" : "") +
                ">" +
                escapeHtmlSo(a.warehouse_name) +
                " (stok " +
                a.available +
                ")</option>"
            );
        })
        .join("");

    var html =
        '<p class="text-start mb-3" style="font-size:14px;white-space:pre-wrap;">' +
        escapeHtmlSo(summary) +
        "</p>" +
        '<label class="form-label text-start d-block fw-semibold">Pilih gudang rekomendasi</label>' +
        '<select id="swal_retail_wh" class="form-select" style="width:100%">' +
        optionsHtml +
        "</select>";

    Swal.fire({
        icon: "error",
        iconColor: "#ef4444",
        title: res.header || "Stok tidak cukup",
        html: html,
        showCancelButton: true,
        reverseButtons: true, // Batal di kiri, aksi di kanan (SOP tombol footer modal)
        confirmButtonText: '<i class="fe fe-check"></i> Pakai Gudang Ini',
        cancelButtonText: "Batal",
        focusConfirm: false,
        customClass: {
            confirmButton: "pg-btn-confirm",
            cancelButton: "pg-btn-cancel",
            title: "fw-bold fs-4 text-dark",
            popup: "rounded-4",
        },
        buttonsStyling: false,
        didOpen: function () {
            var $sel = $("#swal_retail_wh");
            if ($sel.length && typeof $sel.select2 === "function") {
                $sel.select2({
                    width: "100%",
                    dropdownParent: $(".swal2-container"),
                    placeholder: "Pilih gudang",
                });
            }
        },
        preConfirm: function () {
            var val = $("#swal_retail_wh").val();
            if (!val) {
                Swal.showValidationMessage("Pilih gudang rekomendasi");
                return false;
            }
            return val;
        },
    }).then(function (result) {
        // Sama seperti showSoErrorModal() — tutup #modalKonfirmasi begitu popup ini ditutup,
        // apa pun jalan keluarnya (pilih gudang, Batal, klik luar, Esc), supaya user tidak
        // perlu klik dua kali. Aman dipanggil walau modal itu tidak sedang terbuka (mis.
        // dipanggil dari alur tambah/update, bukan ACC).
        $("#modalKonfirmasi").modal("hide");

        if (!result.isConfirmed || !result.value) return;
        var picked = opts.find(function (a) {
            return String(a.warehouse_id) === String(result.value);
        });
        if (!picked) return;
        var recommendation = (res.recommendations || []).find(function (r) {
            return (r.available_at || []).some(function (a) {
                return String(a.warehouse_id) === String(picked.warehouse_id);
            });
        });
        var productIndex = products.findIndex(function (p) {
            return (
                recommendation &&
                parseInt(p.product_variant_id, 10) ===
                    parseInt(recommendation.product_variant_id, 10) &&
                parseInt(p.unit_id, 10) === parseInt(recommendation.unit_id, 10)
            );
        });
        if (productIndex >= 0) {
            products[productIndex].warehouse_id = parseInt(
                picked.warehouse_id,
                10,
            );
            products[productIndex].warehouse_name = picked.warehouse_name;
            refreshTableProduct();
        }
    });
}

function escapeHtmlSo(str) {
    return String(str == null ? "" : str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;");
}

$(document).on("click", ".btn_decline", function () {
    // var data = $('#tableSalesOrder').DataTable().row($(this).parents('tr')).data();//ambil data dari table
    var so_id = $(this).attr("so_id");
    var item = $(this).data("items");
    $(".modal").modal("hide");
    showModalDelete(
        "Apakah yakin ingin tolak pengiriman ini?",
        "btn-decline-so",
    );
    $("#btn-decline-so").attr("so_id", so_id);
    $("#btn-decline-so").attr("item", item);
    $("#btn-decline-so").html("Konfirmasi");
});

$(document).on("click", "#btn-decline-so", function () {
    LoadingButton(this);
    $.ajax({
        url: "/declineSO",
        data: {
            so_id: $("#btn-decline-so").attr("so_id"),
            _token: token,
        },
        method: "post",
        success: function (e) {
            ResetLoadingButton(".btn-konfirmasi", "Konfirmasi");
            $(".modal").modal("hide");
            if (e.status == -2) {
                notifikasi("error", e.header, e.message);
                refreshProductIssues();
                return false;
            }
            refreshSalesOrder();
            notifikasi("success", "Berhasil Tolak", "Berhasil Tolak Pengajuan");
        },
        error: function (e) {
            ResetLoadingButton(".btn-konfirmasi", "Konfirmasi");
            if (handlePermissionError(e)) return;
            console.log(e);
        },
    });
});

$(document).on("click", "#btn_bukti_foto", function () {
    rotationAngle = 0;
    camRotation = 0;
    photoData = "";
    modeCamera = 3;
    inputFile = "#bukti";
    cameraReturnModal = "#add_sales_order";
    $("#video").removeClass("rot90 rot180 rot270");
    $("#preview-box").hide();
    $("#camera").show();

    startCamera();
    $("#add_sales_order").modal("hide");
    $("#modalPhoto").modal("show");
});

$(document).on("click", "#uploadBtn", function () {
    if (
        typeof hasPhotoInputValue === "function"
            ? hasPhotoInputValue($("#bukti").val())
            : $("#bukti").val()
    ) {
        $("#check_foto").show();
        $("#btn_bukti_foto").removeClass("border-danger text-danger");
        console.log(list_photo);
        // $('#jumlahFoto').html($('#bukti').length);
    } else {
        $("#check_foto").hide();
    }
});

$(document).on("change", "#so_customer", function () {
    $("#so_customer").removeClass("is-invalid");
    $(
        "#row-Armada .select2-selection--single, #row-Armada .select2-selection",
    ).removeClass("is-invalids");
});

$(document).on("change", "#so_date", function () {
    $("#so_date").removeClass("is-invalid");
});

$(document).on("click", "#btn-lihat-bukti", function () {
    if (!Array.isArray(list_photo) || list_photo.length <= 0) {
        notifikasi("error", "Gagal View", "Bukti foto belum tersedia.");
        return;
    }
    $("#add_sales_order").modal("hide");
    $("#modalViewPhoto").modal("show");
});

$(document).on("hidden.bs.modal", "#modalViewPhoto", function () {
    $("#add_sales_order").modal("show");
    $("#modalViewPhoto").modal("hide");
});

$(document).on("click", ".btn-prev", function () {
    var index = parseInt($("#fotoProduksiImage").attr("index"));
    console.log("index : " + index);

    if (Array.isArray(list_photo) && index > 0) {
        index -= 1;
        $("#fotoProduksiImage").attr(
            "src",
            public + "issue/" + list_photo[index],
        );
        $("#fotoProduksiImage").attr("index", index);
        $("#btn_download_photo").attr(
            "href",
            public + "issue/" + list_photo[index],
        );
    }
});
$(document).on("click", ".btn-next", function () {
    var index = parseInt($("#fotoProduksiImage").attr("index"));
    console.log("index : " + index);
    if (Array.isArray(list_photo) && index < list_photo.length - 1) {
        index += 1;
        $("#fotoProduksiImage").attr(
            "src",
            public + "issue/" + list_photo[index],
        );
        $("#fotoProduksiImage").attr("index", index);
        $("#btn_download_photo").attr(
            "href",
            public + "issue/" + list_photo[index],
        );
    }
});
