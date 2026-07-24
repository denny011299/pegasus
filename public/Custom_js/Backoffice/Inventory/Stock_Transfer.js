/**
 * Stock Transfer — FE
 * Autocomplete staff/gudang + input produk pola Pembelian (select / scan).
 * Save/ACC backend masih stub.
 */
var mode = 1;
var table = null;
var transferItems = [];
var transferScanMode = false;
var stockLoadPending = 0;

function syncTransferSaveButton() {
    var $btn = $(".btn-save-transfer");
    if (!$btn.length) return;
    if (stockLoadPending > 0) {
        if (!$btn.data("stock-loading")) {
            $btn.data("stock-loading", 1);
            $btn.data("save-label", $btn.html());
            if (typeof LoadingButton === "function") {
                LoadingButton(".btn-save-transfer");
            } else {
                $btn
                    .prop("disabled", true)
                    .html(
                        '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Memuat stok...'
                    );
            }
        }
        return;
    }
    if ($btn.data("stock-loading")) {
        $btn.removeData("stock-loading");
        var label = $btn.data("save-label") || "Simpan";
        if (typeof ResetLoadingButton === "function") {
            ResetLoadingButton(".btn-save-transfer", label);
        } else {
            $btn.prop("disabled", false).html(label);
        }
    }
}

$(document).ready(function () {
    inisialisasi();
});

function inisialisasi() {
    table = $("#tableStockTransfer").DataTable({
        bFilter: true,
        sDom: "fBtlpi",
        lengthMenu: [10, 25, 50, 100],
        ordering: true,
        scrollX: false,
        autoWidth: false,
        language: {
            search: " ",
            sLengthMenu: "_MENU_",
            searchPlaceholder: "Cari Stock Transfer",
            info: "_START_ - _END_ of _TOTAL_ items",
            emptyTable: "Belum ada data Stock Transfer",
            zeroRecords: "Data tidak ditemukan",
            paginate: {
                next: ' <i class=" fa fa-angle-right"></i>',
                previous: '<i class="fa fa-angle-left"></i> ',
            },
        },
        ajax: {
            url: "/getStockTransfer",
            dataSrc: function (json) {
                if (!Array.isArray(json)) {
                    json = json.original || json.data || [];
                }
                return json;
            },
        },
        columns: [
            { 
                data: "transfer_date", 
                render: function(data) {
                    if(!data || data === "-") return "-";
                    return `<div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:32px;height:32px;border-radius:8px;background:#f8fafc;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;color:#64748b;flex-shrink:0;">
                                    <i class="fe fe-calendar"></i>
                                </div>
                                <span class="fw-semibold text-dark">${data}</span>
                            </div>`;
                }
            },
            { 
                data: "transfer_code", 
                render: function(data) {
                    if(!data || data === "-") return "-";
                    return `<span class="badge" style="background:#f1f5f9;color:#334155;border:1px solid #cbd5e1;padding:6px 10px;">${data}</span>`;
                }
            },
            { 
                data: "sender_name", 
                render: function(data) {
                    if(!data || data === "-") return "-";
                    return `<div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:32px;height:32px;border-radius:8px;background:#eff6ff;border:1px solid #bfdbfe;display:flex;align-items:center;justify-content:center;color:#2563eb;flex-shrink:0;">
                                    <i class="fe fe-user"></i>
                                </div>
                                <span class="fw-semibold text-dark">${data}</span>
                            </div>`;
                }
            },
            { 
                data: "from_warehouse_name", 
                render: function(data) {
                    if(!data || data === "-") return "-";
                    return `<div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:32px;height:32px;border-radius:8px;background:#eff6ff;border:1px solid #bfdbfe;display:flex;align-items:center;justify-content:center;color:#2563eb;flex-shrink:0;">
                                    <i class="fe fe-arrow-up-right"></i>
                                </div>
                                <span class="text-dark">${data}</span>
                            </div>`;
                }
            },
            { 
                data: "receiver_name", 
                render: function(data) {
                    if(!data || data === "-") return "-";
                    return `<div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:32px;height:32px;border-radius:8px;background:#ecfdf5;border:1px solid #a7f3d0;display:flex;align-items:center;justify-content:center;color:#059669;flex-shrink:0;">
                                    <i class="fe fe-user-check"></i>
                                </div>
                                <span class="fw-semibold text-dark">${data}</span>
                            </div>`;
                }
            },
            { 
                data: "to_warehouse_name", 
                render: function(data) {
                    if(!data || data === "-") return "-";
                    return `<div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:32px;height:32px;border-radius:8px;background:#ecfdf5;border:1px solid #a7f3d0;display:flex;align-items:center;justify-content:center;color:#059669;flex-shrink:0;">
                                    <i class="fe fe-arrow-down-left"></i>
                                </div>
                                <span class="text-dark">${data}</span>
                            </div>`;
                }
            },
            {
                data: "status",
                className: "text-center",
                render: function (data) {
                    if (data == 1) {
                        return '<span class="badge" style="background:#fff7ed;color:#c2410c;border:1px solid #fed7aa;">Pending</span>';
                    }
                    if (data == 2) {
                        return '<span class="badge" style="background:#ecfdf5;color:#047857;border:1px solid #a7f3d0;">Terkirim</span>';
                    }
                    if (data == 3) {
                        return '<span class="badge" style="background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;">Ditolak</span>';
                    }
                    return "-";
                },
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                className: "text-center",
                render: function (data, type, row) {
                    var pending = parseInt(row.status, 10) === 1;
                    var success = parseInt(row.status, 10) === 2;
                    var activeWh = String(
                        (typeof getActiveWarehouseId === "function" && getActiveWarehouseId()) ||
                            (window.activeWarehouse && window.activeWarehouse.id) ||
                            ""
                    );
                    var toWh = String(row.to_warehouse_id || "");
                    var fromWh = String(row.from_warehouse_id || "");
                    // ACC hanya jika gudang aktif = gudang tujuan (bukan gudang asal)
                    var canAcc =
                        pending &&
                        activeWh !== "" &&
                        toWh !== "" &&
                        activeWh === toWh &&
                        activeWh !== fromWh;
                    // Edit/Hapus hanya di gudang asal
                    var canEdit =
                        pending &&
                        activeWh !== "" &&
                        fromWh !== "" &&
                        activeWh === fromWh;

                    // View hanya jika tidak bisa edit (fungsi mirip) — biasanya status sukses/ditolak
                    var viewBtn =
                        !canEdit && !pending
                            ? `<a href="javascript:void(0);" class="me-2 p-2 btn-action-icon btnViewTransfer" title="Lihat Detail" data-id="${row.id}">
                                <i class="fe fe-eye"></i>
                           </a>`
                            : "";

                    var accBtn = canAcc
                        ? `<a href="javascript:void(0);" class="me-2 p-2 btn-action-icon btnAccept text-info" title="Terima Transfer" data-id="${row.id}">
                                <i class="fe fe-check-circle"></i>
                           </a>`
                        : "";
                    var editBtn = canEdit
                        ? `<a href="javascript:void(0);" class="me-2 p-2 btn-action-icon btnEditTransfer text-primary" title="Edit Transfer" data-id="${row.id}">
                                <i class="fe fe-edit"></i>
                           </a>`
                        : "";
                    var delBtn = canEdit
                        ? `<a href="javascript:void(0);" class="p-2 btn-action-icon btnDeleteTransfer text-danger" title="Hapus Transfer" data-id="${row.id}">
                                <i class="fe fe-trash-2"></i>
                           </a>`
                        : "";
                    return `
                        <div class="d-flex justify-content-center gap-1">
                            ${viewBtn}
                            ${accBtn}
                            ${editBtn}
                            ${delBtn}
                        </div>
                    `;
                },
            },
        ],
        initComplete: function () {
            $(".dataTables_filter").appendTo("#tableSearch");
            $(".dataTables_filter").appendTo(".search-input");
            $("#tableStockTransfer-wrap").removeClass("dt-pending").addClass("dt-ready");
        },
    });
}

function initTransferAutocompletes() {
    var parent = "#add_stock_transfer";
    autocompleteStaff("#transfer_sender_id", parent);
    autocompleteStaff("#transfer_receiver_id", parent);
    autocompleteWarehouse("#transfer_from_warehouse_id", parent);
    autocompleteWarehouse("#transfer_to_warehouse_id", parent);
}

function resetTransferSkuSelect(msg) {
    if ($("#transfer_sku").hasClass("select2-hidden-accessible")) {
        $("#transfer_sku").select2("destroy");
    }
    $("#transfer_sku")
        .empty()
        .append(
            '<option value="" selected disabled>' +
                (msg || "Pilih gudang asal terlebih dahulu") +
                "</option>"
        );
}

function enableTransferProductSelect() {
    var fromId = $("#transfer_from_warehouse_id").val();
    if (!fromId) {
        resetTransferSkuSelect();
        return;
    }
    autocompleteProductVariantOnly("#transfer_sku", "#add_stock_transfer");
}

function validateWarehousesDifferent() {
    var fromId = $("#transfer_from_warehouse_id").val();
    var toId = $("#transfer_to_warehouse_id").val();
    $("#transfer_from_warehouse_id, #transfer_to_warehouse_id")
        .next(".select2-container")
        .find(".select2-selection")
        .removeClass("is-invalid");

    if (fromId && toId && String(fromId) === String(toId)) {
        $("#transfer_to_warehouse_id")
            .next(".select2-container")
            .find(".select2-selection")
            .addClass("is-invalid");
        if (typeof toastr !== "undefined") {
            toastr.warning("", "Gudang tujuan tidak boleh sama dengan gudang asal");
        } else if (typeof notifikasi === "function") {
            notifikasi("warning", "Validasi", "Gudang tujuan tidak boleh sama dengan gudang asal");
        }
        return false;
    }
    return true;
}

function buildUnitOptions(item) {
    var units = item.units && item.units.length ? item.units : item.pr_unit || [];
    if (!units.length) {
        return '<option value="">-</option>';
    }
    var html = "";
    units.forEach(function (u) {
        var uid = u.unit_id;
        var label = u.unit_name || u.unit_short_name || "-";
        var selected = String(item.unit_id) === String(uid) ? " selected" : "";
        html +=
            '<option value="' +
            uid +
            '"' +
            selected +
            ">" +
            escapeHtml(label) +
            "</option>";
    });
    return html;
}

function resolveUnitsFromRaw(raw) {
    if (Array.isArray(raw.pr_unit) && raw.pr_unit.length) {
        return raw.pr_unit.map(function (u) {
            return {
                unit_id: u.unit_id,
                unit_name: u.unit_name || u.unit_short_name || "-",
                unit_short_name: u.unit_short_name || u.unit_name || "-",
            };
        });
    }
    if (raw.unit_id) {
        return [
            {
                unit_id: raw.unit_id,
                unit_name: raw.unit_name || "-",
                unit_short_name: raw.unit_short_name || raw.unit_name || "-",
            },
        ];
    }
    return [];
}

function refreshTransferItemsTable() {
    var $tbody = $("#tableTransferItems tbody");
    $tbody.empty();
    if (!transferItems.length) {
        $tbody.html(`
            <tr class="empty-row">
                <td colspan="7" class="text-center text-muted">Belum ada produk. Pilih gudang asal terlebih dahulu, lalu pilih/scan produk.</td>
            </tr>
        `);
        return;
    }

    transferItems.forEach(function (item, index) {
        $tbody.append(`
            <tr data-index="${index}" data-variant-id="${item.product_variant_id}">
                <td>${escapeHtml(item.product_name || "-")}</td>
                <td>${escapeHtml(item.product_variant_name || "-")}</td>
                <td>${escapeHtml(item.product_variant_sku || "-")}</td>
                <td class="col-stock-asal">${escapeHtml(item.stock_text || "…")}</td>
                <td>
                    <input type="number" class="form-control form-control-sm transfer-qty" min="1"
                        value="${item.qty}" data-index="${index}" style="width:90px;">
                </td>
                <td>
                    <select class="form-select form-select-sm transfer-unit" data-index="${index}">
                        ${buildUnitOptions(item)}
                    </select>
                </td>
                <td class="text-center">
                    <a class="p-2 btn-action-icon text-danger btn-remove-transfer-item" href="javascript:void(0);" data-index="${index}">
                        <i class="fe fe-trash-2"></i>
                    </a>
                </td>
            </tr>
        `);
    });
}

function escapeHtml(str) {
    return String(str == null ? "" : str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;");
}

function fetchSourceStock(productVariantId, done) {
    var warehouseId = $("#transfer_from_warehouse_id").val();
    if (!warehouseId || !productVariantId) {
        done({ stock_text: "-", units: [] });
        return;
    }
    $.ajax({
        url: "/getTransferSourceStock",
        method: "get",
        data: {
            warehouse_id: warehouseId,
            product_variant_id: productVariantId,
        },
        success: function (res) {
            done(res || { stock_text: "0", units: [] });
        },
        error: function () {
            done({ stock_text: "-", units: [] });
        },
    });
}

function patchTransferItemStock(variantId, stock) {
    var changed = false;
    transferItems.forEach(function (item) {
        if (parseInt(item.product_variant_id, 10) !== parseInt(variantId, 10)) return;
        item.stock_text = stock.stock_text || "0";
        if (stock.units && stock.units.length) {
            item.units = stock.units;
            // Keep current unit if still valid; else default first
            var stillOk = stock.units.some(function (u) {
                return String(u.unit_id) === String(item.unit_id);
            });
            if (!stillOk) {
                item.unit_id = stock.units[0].unit_id;
                item.unit_name = stock.units[0].unit_name || stock.units[0].unit_short_name;
            }
        }
        changed = true;
    });
    if (changed) refreshTransferItemsTable();
}

function addTransferProduct(raw, qty) {
    qty = parseInt(qty, 10) || 1;
    if (qty < 1) qty = 1;

    var variantId = parseInt(raw.product_variant_id || raw.id, 10);
    if (!variantId) {
        if (typeof toastr !== "undefined") toastr.error("", "Produk tidak valid");
        return;
    }

    var fromId = $("#transfer_from_warehouse_id").val();
    if (!fromId) {
        if (typeof toastr !== "undefined") {
            toastr.warning("", "Pilih gudang asal terlebih dahulu");
        }
        return;
    }

    var units = resolveUnitsFromRaw(raw);
    var defaultUnitId =
        raw.unit_id ||
        (units.length ? units[0].unit_id : null);
    var defaultUnitName = "";
    units.forEach(function (u) {
        if (String(u.unit_id) === String(defaultUnitId)) {
            defaultUnitName = u.unit_name || u.unit_short_name || "";
        }
    });

    // Duplikat = variant + satuan sama → tambah qty
    var existing = -1;
    transferItems.forEach(function (el, idx) {
        if (
            parseInt(el.product_variant_id, 10) === variantId &&
            String(el.unit_id || "") === String(defaultUnitId || "")
        ) {
            existing = idx;
        }
    });

    if (existing === -1) {
        transferItems.push({
            product_variant_id: variantId,
            product_id: raw.product_id || null,
            product_name: raw.pr_name || raw.product_name || "-",
            product_variant_name: raw.product_variant_name || "-",
            product_variant_sku: raw.product_variant_sku || "-",
            qty: qty,
            stock_text: "…",
            units: units,
            pr_unit: units,
            unit_id: defaultUnitId,
            unit_name: defaultUnitName,
        });
    } else {
        transferItems[existing].qty =
            (parseInt(transferItems[existing].qty, 10) || 0) + qty;
    }

    // Langsung tampil (tanpa tunggu AJAX stok)
    refreshTransferItemsTable();
    if (typeof toastr !== "undefined") {
        toastr.success(
            "",
            "Berhasil menambahkan: " +
                (raw.pr_name || raw.product_name || "") +
                " " +
                (raw.product_variant_name || "")
        );
    }

    // Stok asal + opsi satuan dari gudang di-load async
    stockLoadPending++;
    syncTransferSaveButton();
    fetchSourceStock(variantId, function (stock) {
        patchTransferItemStock(variantId, stock);
        stockLoadPending = Math.max(0, stockLoadPending - 1);
        syncTransferSaveButton();
    });
}

function setDefaultSender() {
    var staff = window.currentStaff || {};
    if (!staff.id || !staff.name) return;
    var $el = $("#transfer_sender_id");
    if (!$el.length) return;
    var opt = new Option(staff.name, staff.id, true, true);
    $el.append(opt).trigger("change");
}

function setDefaultFromWarehouse() {
    // Utamakan gudang aktif dari header (live), fallback window.activeWarehouse
    var id = typeof getActiveWarehouseId === "function" ? getActiveWarehouseId() : null;
    var wh = window.activeWarehouse || {};
    if (!id) id = wh.id || null;
    if (!id) return;

    var text = null;
    if (String(id) === String(wh.id) && (wh.text || wh.name)) {
        text = wh.text || wh.name;
    }
    if (!text && typeof getActiveWarehouseName === "function") {
        text = getActiveWarehouseName();
    }
    if (!text) text = wh.text || wh.name || "Gudang #" + id;

    var $el = $("#transfer_from_warehouse_id");
    if (!$el.length) return;
    if ($el.find("option[value='" + id + "']").length === 0) {
        $el.append(new Option(text, id, true, true));
    }
    $el.val(String(id)).trigger("change");
}

function resetTransferForm() {
    transferItems = [];
    transferScanMode = false;
    stockLoadPending = 0;
    syncTransferSaveButton();
    $("#add_stock_transfer input:not([type=checkbox]), #add_stock_transfer textarea").val("");
    $("#transfer_scan_qty").val(1);
    $("#transfer_mode_scan").hide();
    $("#transfer_mode_select").show();
    $("#btn_toggle_scan_transfer")
        .html('<i class="fa fa-barcode"></i> Scan')
        .removeClass("btn-outline-primary")
        .addClass("btn-outline-secondary");
    // Default tanggal = hari ini (format datetimepicker project: DD-MM-YYYY)
    var today =
        typeof moment === "function"
            ? moment().format("DD-MM-YYYY")
            : (function () {
                  var d = new Date();
                  var dd = String(d.getDate()).padStart(2, "0");
                  var mm = String(d.getMonth() + 1).padStart(2, "0");
                  return dd + "-" + mm + "-" + d.getFullYear();
              })();
    $("#transfer_date").val(today);
    if ($("#transfer_date").data("DateTimePicker")) {
        $("#transfer_date").data("DateTimePicker").date(today);
    }
    $("#transfer_sender_id, #transfer_receiver_id, #transfer_from_warehouse_id, #transfer_to_warehouse_id")
        .val(null)
        .trigger("change");
    resetTransferSkuSelect();
    refreshTransferItemsTable();
    $(".is-invalid").removeClass("is-invalid");
}

$(document).on("click", ".btnAdd", function () {
    mode = 1;
    if (!$("#add_stock_transfer").length) return;
    $("#add_stock_transfer .modal-title").html("Stock Transfer");
    $("#add_stock_transfer").removeAttr("data-id");
    resetTransferForm();
    initTransferAutocompletes();
    setDefaultSender();
    setDefaultFromWarehouse();
    $("#add_stock_transfer").modal("show");
});

$(document).on("change", "#transfer_from_warehouse_id, #transfer_to_warehouse_id", function () {
    validateWarehousesDifferent();
});

$(document).on("change", "#transfer_from_warehouse_id", function () {
    transferItems = [];
    refreshTransferItemsTable();
    enableTransferProductSelect();
});

$(document).on("change", "#transfer_sku", function () {
    var data = $(this).select2("data")[0];
    if (!data || !data.id) return;
    addTransferProduct(data, 1);
    $(this).val(null).trigger("change");
});

$(document).on("click", "#btn_toggle_scan_transfer", function () {
    var fromId = $("#transfer_from_warehouse_id").val();
    if (!fromId) {
        if (typeof toastr !== "undefined") {
            toastr.warning("", "Pilih gudang asal terlebih dahulu");
        }
        return;
    }
    transferScanMode = !transferScanMode;
    if (transferScanMode) {
        $("#transfer_mode_select").hide();
        $("#transfer_mode_scan").show();
        $(this)
            .html('<i class="fa fa-list"></i> Input')
            .removeClass("btn-outline-secondary")
            .addClass("btn-outline-primary");
        $("#transfer_scan_barcode").focus();
    } else {
        $("#transfer_mode_scan").hide();
        $("#transfer_mode_select").show();
        $(this)
            .html('<i class="fa fa-barcode"></i> Scan')
            .removeClass("btn-outline-primary")
            .addClass("btn-outline-secondary");
    }
});

function doScanAddTransfer() {
    var barcode = ($("#transfer_scan_barcode").val() || "").trim();
    var qty = parseInt($("#transfer_scan_qty").val(), 10) || 1;
    if (qty < 1) qty = 1;

    if (!barcode) {
        if (typeof toastr !== "undefined") toastr.warning("", "Masukkan barcode/SKU terlebih dahulu");
        return;
    }
    if (!$("#transfer_from_warehouse_id").val()) {
        if (typeof toastr !== "undefined") toastr.warning("", "Pilih gudang asal terlebih dahulu");
        return;
    }

    $.ajax({
        url: "/searchProductVariantByScan",
        method: "post",
        data: {
            keyword: barcode,
            _token: token || $('meta[name="csrf-token"]').attr("content"),
        },
        success: function (res) {
            var results = res.data || [];
            if (!results.length) {
                if (typeof toastr !== "undefined") {
                    toastr.error("", "Produk tidak ditemukan untuk barcode: " + barcode);
                }
                $("#transfer_scan_barcode").val("").focus();
                return;
            }
            addTransferProduct(results[0], qty);
            $("#transfer_scan_barcode").val("").focus();
        },
        error: function () {
            if (typeof toastr !== "undefined") toastr.error("", "Gagal mencari produk");
            $("#transfer_scan_barcode").val("").focus();
        },
    });
}

$(document).on("click", "#btn_scan_add_transfer", function () {
    doScanAddTransfer();
});

$(document).on("keydown", "#transfer_scan_barcode", function (e) {
    if (e.key === "Enter" || e.keyCode === 13) {
        e.preventDefault();
        doScanAddTransfer();
    }
});

$(document).on("input", ".transfer-qty", function () {
    var idx = parseInt($(this).attr("data-index"), 10);
    var val = parseInt($(this).val(), 10) || 1;
    if (val < 1) val = 1;
    if (transferItems[idx]) transferItems[idx].qty = val;
});

$(document).on("change", ".transfer-unit", function () {
    var idx = parseInt($(this).attr("data-index"), 10);
    if (!transferItems[idx]) return;
    var unitId = $(this).val();
    transferItems[idx].unit_id = unitId;
    transferItems[idx].unit_name = $(this).find("option:selected").text();
});

$(document).on("click", ".btn-remove-transfer-item", function () {
    var idx = parseInt($(this).attr("data-index"), 10);
    if (isNaN(idx)) return;
    transferItems.splice(idx, 1);
    refreshTransferItemsTable();
});

$(document).on("click", ".btnViewTransfer", function () {
    var id = $(this).attr("data-id");
    if (!id || !$("#view_stock_transfer").length) return;

    $("#lbl_view_sender, #lbl_view_from, #lbl_view_date, #lbl_view_receiver, #lbl_view_to, #lbl_view_note").text("-");
    $("#tableViewItems tbody").html(
        '<tr class="empty-row"><td colspan="6" class="text-center text-muted">Memuat data...</td></tr>'
    );
    $("#view_stock_transfer").modal("show");

    $.ajax({
        url: "/getStockTransferDetail",
        method: "get",
        data: { id: id },
        success: function (res) {
            if (!res || !res.id) {
                $("#tableViewItems tbody").html(
                    '<tr class="empty-row"><td colspan="6" class="text-center text-muted">Data tidak ditemukan.</td></tr>'
                );
                if (typeof toastr !== "undefined") toastr.error("", "Data transfer tidak ditemukan");
                return;
            }

            $("#lbl_view_sender").text(res.sender_name || "-");
            $("#lbl_view_from").text(res.from_warehouse_name || "-");
            $("#lbl_view_date").text(res.transfer_date || "-");
            $("#lbl_view_receiver").text(res.receiver_name || "-");
            $("#lbl_view_to").text(res.to_warehouse_name || "-");
            $("#lbl_view_note").text(res.accept_note || res.note || "-");

            var items = res.items || [];
            if (!items.length) {
                $("#tableViewItems tbody").html(
                    '<tr class="empty-row"><td colspan="6" class="text-center text-muted">Belum ada produk.</td></tr>'
                );
                return;
            }

            var html = "";
            items.forEach(function (it) {
                var qtySend = formatTransferQty(it.qty);
                var qtyRecv =
                    it.qty_received != null && it.qty_received !== ""
                        ? formatTransferQty(it.qty_received)
                        : "-";
                html +=
                    "<tr>" +
                    "<td>" +
                    escapeHtml(it.product_name || "-") +
                    "</td>" +
                    "<td>" +
                    escapeHtml(it.product_variant_name || "-") +
                    "</td>" +
                    "<td>" +
                    escapeHtml(it.sku || "-") +
                    "</td>" +
                    '<td class="text-center">' +
                    qtySend +
                    "</td>" +
                    '<td class="text-center">' +
                    qtyRecv +
                    "</td>" +
                    "<td>" +
                    escapeHtml(it.unit_name || it.unit_short_name || "-") +
                    "</td>" +
                    "</tr>";
            });
            $("#tableViewItems tbody").html(html);
        },
        error: function () {
            $("#tableViewItems tbody").html(
                '<tr class="empty-row"><td colspan="6" class="text-center text-muted">Gagal memuat data.</td></tr>'
            );
            if (typeof toastr !== "undefined") toastr.error("", "Gagal memuat detail transfer");
        },
    });
});

function formatTransferQty(val) {
    var n = parseFloat(val);
    if (isNaN(n)) return "-";
    if (Math.abs(n - Math.round(n)) < 1e-9) return String(Math.round(n));
    return String(n);
}

$(document).on("click", ".btn-save-transfer", function () {
    if (stockLoadPending > 0) {
        if (typeof toastr !== "undefined") {
            toastr.info("", "Mohon tunggu, stok produk masih dimuat...");
        }
        return;
    }
    if (!validateWarehousesDifferent()) return;

    // reset validation
    $("#transfer_date").removeClass("is-invalid");
    $("#transfer_sender_id, #transfer_receiver_id, #transfer_from_warehouse_id, #transfer_to_warehouse_id")
        .next(".select2-container")
        .find(".select2-selection")
        .removeClass("is-invalid");

    var sender = $("#transfer_sender_id").val();
    var receiver = $("#transfer_receiver_id").val();
    var fromId = $("#transfer_from_warehouse_id").val();
    var toId = $("#transfer_to_warehouse_id").val();
    var date = $("#transfer_date").val();

    var valid = true;
    if (!sender) {
        $("#transfer_sender_id").next(".select2-container").find(".select2-selection").addClass("is-invalid");
        valid = false;
    }
    if (!receiver) {
        $("#transfer_receiver_id").next(".select2-container").find(".select2-selection").addClass("is-invalid");
        valid = false;
    }
    if (!fromId) {
        $("#transfer_from_warehouse_id").next(".select2-container").find(".select2-selection").addClass("is-invalid");
        valid = false;
    }
    if (!toId) {
        $("#transfer_to_warehouse_id").next(".select2-container").find(".select2-selection").addClass("is-invalid");
        valid = false;
    }
    if (!date) {
        $("#transfer_date").addClass("is-invalid");
        valid = false;
    }

    if (!valid) {
        if (typeof toastr !== "undefined") {
            toastr.error("", "Lengkapi pengirim, penerima, gudang, dan tanggal");
        }
        return;
    }
    if (!transferItems.length) {
        if (typeof toastr !== "undefined") toastr.error("", "Tambahkan minimal 1 produk");
        return;
    }

    var missingUnit = transferItems.some(function (it) {
        return !it.unit_id;
    });
    if (missingUnit) {
        if (typeof toastr !== "undefined") toastr.error("", "Pilih satuan untuk semua produk");
        return;
    }

    var $btn = $(this);
    var saveLabel = $btn.data("save-label") || $btn.html() || "Simpan";
    $btn.data("save-label", saveLabel);
    if (typeof LoadingButton === "function") {
        LoadingButton(".btn-save-transfer");
    } else {
        $btn
            .prop("disabled", true)
            .html(
                '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Menyimpan...'
            );
    }

    var itemsPayload = transferItems.map(function (it) {
        return {
            product_variant_id: it.product_variant_id,
            unit_id: it.unit_id,
            qty: it.qty,
            label:
                (it.product_name || "") +
                " " +
                (it.product_variant_name || "") +
                (it.unit_name ? " (" + it.unit_name + ")" : ""),
        };
    });

    var payload = {
        sender_id: sender,
        receiver_id: receiver,
        from_warehouse_id: fromId,
        to_warehouse_id: toId,
        transfer_date: date,
        note: $("#transfer_note").val(),
        items: itemsPayload,
        _token: token || $('meta[name="csrf-token"]').attr("content"),
    };
    var editId = $("#add_stock_transfer").attr("data-id");
    if (mode === 2 && editId) {
        payload.id = editId;
        payload.st_id = editId;
    }

    function resetSaveBtn() {
        if (typeof ResetLoadingButton === "function") {
            ResetLoadingButton(".btn-save-transfer", saveLabel);
        } else {
            $btn.prop("disabled", false).html(saveLabel);
        }
        $btn.removeData("stock-loading");
    }

    function doSave() {
        $.ajax({
            url: mode === 2 ? "/updateStockTransfer" : "/insertStockTransfer",
            method: "post",
            data: payload,
            success: function (saveRes) {
                resetSaveBtn();
                if (!saveRes || saveRes.status != 1) {
                    if (typeof toastr !== "undefined") {
                        toastr.error("", (saveRes && saveRes.message) || "Gagal menyimpan");
                    }
                    return;
                }
                if (typeof toastr !== "undefined") {
                    toastr.success("", saveRes.message || "Berhasil disimpan");
                }
                $("#add_stock_transfer").modal("hide");
                if (table) table.ajax.reload(null, false);
            },
            error: function () {
                resetSaveBtn();
                if (typeof toastr !== "undefined") toastr.error("", "Gagal menyimpan stock transfer");
            },
        });
    }

    // Edit: skip pre-check FE (stok sudah terpotong); validasi di transaksi update
    if (mode === 2) {
        doSave();
        return;
    }

    $.ajax({
        url: "/checkTransferStock",
        method: "post",
        data: {
            from_warehouse_id: fromId,
            items: itemsPayload,
            _token: payload._token,
        },
        success: function (res) {
            if (!res || !res.ok) {
                resetSaveBtn();
                if (typeof toastr !== "undefined") {
                    toastr.error("", (res && res.message) || "Stok tidak mencukupi");
                }
                return;
            }
            doSave();
        },
        error: function () {
            resetSaveBtn();
            if (typeof toastr !== "undefined") {
                toastr.error("", "Gagal cek stok");
            }
        },
    });
});

// Remove invalid class on change
$(document).on("change", "#transfer_sender_id, #transfer_receiver_id, #transfer_from_warehouse_id, #transfer_to_warehouse_id", function() {
    $(this).next(".select2-container").find(".select2-selection").removeClass("is-invalid");
});
$(document).on("change", "#transfer_date", function() {
    $(this).removeClass("is-invalid");
});

function fillSelectOption($el, id, text) {
    if (!id) return;
    if ($el.find("option[value='" + id + "']").length === 0) {
        $el.append(new Option(text || id, id, true, true));
    }
    $el.val(String(id)).trigger("change");
}

function loadTransferDetailForEdit(id) {
    $.ajax({
        url: "/getStockTransferDetail",
        method: "get",
        data: { id: id },
        success: function (res) {
            if (!res || !res.id) {
                if (typeof toastr !== "undefined") toastr.error("", "Data transfer tidak ditemukan");
                return;
            }
            mode = 2;
            resetTransferForm();
            initTransferAutocompletes();
            $("#add_stock_transfer").attr("data-id", res.id);
            $("#add_stock_transfer .modal-title").html("Edit Stock Transfer");
            $("#transfer_date").val(res.transfer_date);
            if ($("#transfer_date").data("DateTimePicker")) {
                $("#transfer_date").data("DateTimePicker").date(res.transfer_date);
            }
            $("#transfer_note").val(res.note || "");
            fillSelectOption($("#transfer_sender_id"), res.sender_id, res.sender_name);
            fillSelectOption($("#transfer_receiver_id"), res.receiver_id, res.receiver_name);
            fillSelectOption($("#transfer_from_warehouse_id"), res.from_warehouse_id, res.from_warehouse_name);
            fillSelectOption($("#transfer_to_warehouse_id"), res.to_warehouse_id, res.to_warehouse_name);
            enableTransferProductSelect();

            transferItems = (res.items || []).map(function (it) {
                return {
                    product_id: it.product_id,
                    product_variant_id: it.product_variant_id,
                    product_name: it.product_name,
                    product_variant_name: it.product_variant_name,
                    sku: it.sku,
                    qty: parseFloat(it.qty) || 1,
                    unit_id: it.unit_id,
                    unit_name: it.unit_name,
                    stock_text: it.stock_text || "-",
                    units: it.units || [],
                };
            });
            refreshTransferItemsTable();
            $("#add_stock_transfer").modal("show");
        },
        error: function () {
            if (typeof toastr !== "undefined") toastr.error("", "Gagal memuat detail");
        },
    });
}

function renderAcceptItems(items) {
    var $tb = $("#tableAcceptItems tbody");
    if (!items || !items.length) {
        $tb.html(
            '<tr class="empty-row"><td colspan="5" class="text-center text-muted">Belum ada produk.</td></tr>'
        );
        return;
    }
    var html = "";
    items.forEach(function (it, idx) {
        var qty = it.qty_received != null ? it.qty_received : it.qty;
        var unitLabel = it.unit_name || it.unit_short_name || "-";
        html +=
            "<tr data-std-id=\"" +
            it.std_id +
            "\">" +
            "<td>" +
            escapeHtml(it.product_name || "-") +
            "</td>" +
            "<td>" +
            escapeHtml(it.product_variant_name || "-") +
            "</td>" +
            "<td>" +
            escapeHtml(it.sku || "-") +
            "</td>" +
            '<td><input type="number" min="0" step="1" class="form-control accept-qty" data-index="' +
            idx +
            '" value="' +
            qty +
            '"></td>' +
            "<td>" +
            escapeHtml(unitLabel) +
            "</td>" +
            "</tr>";
    });
    $tb.html(html);
    $("#accept_stock_transfer").data("accept-items", items);
}

$(document).on("click", ".btnEditTransfer", function () {
    var id = $(this).attr("data-id");
    if (!id) return;
    loadTransferDetailForEdit(id);
});

$(document).on("click", ".btnDeleteTransfer", function () {
    var id = $(this).attr("data-id");
    if (!id) return;
    showModalDelete(
        "Apakah yakin ingin menghapus stock transfer ini? Stok gudang asal akan dikembalikan.",
        "btn-delete-stock-transfer"
    );
    $("#btn-delete-stock-transfer").attr("data-id", id);
});

$(document).on("click", "#btn-delete-stock-transfer", function () {
    var id = $(this).attr("data-id");
    if (!id) return;

    $.ajax({
        url: "/deleteStockTransfer",
        method: "post",
        data: {
            id: id,
            _token: token || $('meta[name="csrf-token"]').attr("content"),
        },
        success: function (res) {
            closeModalDelete();
            if (!res || res.status != 1) {
                if (typeof toastr !== "undefined") {
                    toastr.error("", (res && res.message) || "Gagal menghapus");
                }
                return;
            }
            if (typeof toastr !== "undefined") toastr.success("", res.message || "Berhasil dihapus");
            if (table) table.ajax.reload(null, false);
        },
        error: function () {
            closeModalDelete();
            if (typeof toastr !== "undefined") toastr.error("", "Gagal menghapus");
        },
    });
});

$(document).on("click", ".btnAccept", function () {
    var id = $(this).attr("data-id");
    if (!id || !$("#accept_stock_transfer").length) return;

    var row = table ? table.row($(this).closest("tr")).data() : null;
    var toWh = row ? String(row.to_warehouse_id || "") : "";
    var fromWh = row ? String(row.from_warehouse_id || "") : "";
    var activeWh =
        typeof getActiveWarehouseId === "function" ? String(getActiveWarehouseId() || "") : "";
    var staff = window.currentStaff || {};

    if (toWh && activeWh && toWh !== activeWh) {
        if (typeof toastr !== "undefined") {
            toastr.warning("", "Ganti gudang aktif ke gudang tujuan sebelum ACC");
        }
        return;
    }
    if (fromWh && activeWh && fromWh === activeWh) {
        if (typeof toastr !== "undefined") {
            toastr.warning("", "Gudang asal tidak bisa ACC. ACC hanya di gudang tujuan.");
        }
        return;
    }

    $("#accept_stock_transfer").attr("data-id", id);
    $("#accept_stock_transfer input, #accept_stock_transfer textarea").val("");
    $("#accept_stock_transfer select").val(null).trigger("change");
    $("#lbl_accept_sender, #lbl_accept_from, #lbl_accept_date, #lbl_accept_to").text("-");
    renderAcceptItems([]);
    autocompleteStaff("#accept_receiver_id", "#accept_stock_transfer");

    $.ajax({
        url: "/getStockTransferDetail",
        method: "get",
        data: { id: id },
        success: function (res) {
            if (!res || !res.id) {
                if (typeof toastr !== "undefined") toastr.error("", "Data transfer tidak ditemukan");
                return;
            }
            if (parseInt(res.status, 10) !== 1) {
                if (typeof toastr !== "undefined") toastr.warning("", "Transfer sudah diproses");
                return;
            }
            $("#lbl_accept_sender").text(res.sender_name || "-");
            $("#lbl_accept_from").text(res.from_warehouse_name || "-");
            $("#lbl_accept_date").text(res.transfer_date || "-");
            $("#lbl_accept_to").text(res.to_warehouse_name || "-");
            $("#accept_note").val(res.accept_note || "");
            // Default penerima = yang di-set saat transfer, fallback user login (bisa diganti)
            if (res.receiver_id) {
                fillSelectOption($("#accept_receiver_id"), res.receiver_id, res.receiver_name);
            } else if (staff.id && staff.name) {
                fillSelectOption($("#accept_receiver_id"), staff.id, staff.name);
            }
            $("#accept_receiver_id").prop("disabled", false);
            renderAcceptItems(res.items || []);
            $("#accept_stock_transfer").modal("show");
        },
        error: function () {
            if (typeof toastr !== "undefined") toastr.error("", "Gagal memuat detail");
        },
    });
});

$(document).on("hidden.bs.modal", "#accept_stock_transfer", function () {
    $("#accept_receiver_id").prop("disabled", false);
});

$(document).on("click", ".btn-minus", function () {
    var input = $(this).siblings("input");
    var val = parseInt(input.val(), 10) || 0;
    if (val > 0) input.val(val - 1);
});
$(document).on("click", ".btn-plus", function () {
    var input = $(this).siblings("input");
    var val = parseInt(input.val(), 10) || 0;
    input.val(val + 1);
});

$(document).on("click", ".btn-accept-transfer", function () {
    var id = $("#accept_stock_transfer").attr("data-id");
    if (!id) {
        if (typeof toastr !== "undefined") toastr.error("", "ID transfer tidak ditemukan");
        return;
    }
    var items = $("#accept_stock_transfer").data("accept-items") || [];
    var payloadItems = [];
    $("#tableAcceptItems tbody tr[data-std-id]").each(function () {
        var stdId = $(this).attr("data-std-id");
        var qty = parseFloat($(this).find(".accept-qty").val());
        if (isNaN(qty) || qty < 0) qty = 0;
        payloadItems.push({
            std_id: stdId,
            qty_received: qty,
        });
    });
    if (!payloadItems.length && items.length) {
        payloadItems = items.map(function (it) {
            return {
                std_id: it.std_id,
                qty_received: it.qty_received != null ? it.qty_received : it.qty,
            };
        });
    }

    if (!$("#accept_receiver_id").val()) {
        if (typeof toastr !== "undefined") toastr.warning("", "Pilih penerima terlebih dahulu");
        return;
    }

    var $btn = $(this);
    $btn.prop("disabled", true);
    $.ajax({
        url: "/accStockTransfer",
        method: "post",
        data: {
            id: id,
            receiver_id: $("#accept_receiver_id").val(),
            accept_note: $("#accept_note").val(),
            items: payloadItems,
            _token: token || $('meta[name="csrf-token"]').attr("content"),
        },
        success: function (res) {
            $btn.prop("disabled", false);
            if (!res || res.status != 1) {
                if (typeof toastr !== "undefined") {
                    toastr.error("", (res && res.message) || "Gagal ACC");
                }
                return;
            }
            if (typeof toastr !== "undefined") toastr.success("", res.message || "Berhasil ACC");
            $("#accept_stock_transfer").modal("hide");
            if (table) table.ajax.reload(null, false);
        },
        error: function () {
            $btn.prop("disabled", false);
            if (typeof toastr !== "undefined") toastr.error("", "Gagal ACC stock transfer");
        },
    });
});
