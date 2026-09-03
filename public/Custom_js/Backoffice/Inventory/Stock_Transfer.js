/**
 * Stock Transfer — FE
 * Autocomplete staff/gudang + input produk pola Pembelian (select / scan).
 * Pending: form default locked; unlock via Edit Data (butuh hak edit).
 */
var mode = 1;
var table = null;
var transferItems = [];
var transferItemsSnapshot = [];
var transferHeaderSnapshot = null;
var transferFormLocked = false;
var transferCanEdit = false;
var transferCanShip = false;
var transferCanReject = false;
var transferCanApproveQc = false;
var transferCanApproveOps = false;
var transferRequiresApproval = false;
var transferQcRequired = false;
var transferOpsRequired = false;
var transferQcApproved = false;
var transferOpsApproved = false;
var transferPhotoProofRequired = false; // bukti foto wajib (GitHub #140) di #modalKonfirmasi saat ini
var transferKeepPhotoProofOnClose = false; // true = #modalKonfirmasi ditutup sementara untuk buka kamera
var transferThenShipProofBase64 = ""; // bukti foto utk kombo Simpan+Kirim (edit mode, tanpa #modalKonfirmasi)
var transferPendingThenShip = false; // true = habis ambil foto, lanjutkan otomatis Simpan+Kirim
var transferCreateRequestMode = false; // eceran create: label request / penerima = aktif
var transferIsRetailRequest = false; // create ATAU edit retail_request
var transferScanMode = false;
var stockLoadPending = 0;
var retailUnitValidationPending = 0;
var retailValidationRun = 0;
var stockValidationPending = 0;
var stockValidationRun = 0;
var transferDraft = {
    raw: null,
    stock: null,
    loading: false,
    requireDefaultUnit: false,
    defaultUnitInvalid: false,
};
var transferDraftRun = 0;
var transferDraftSelectGuard = false;
var transferRouteRun = 0;

function stockTransferOriginBadge(sourceType) {
    if (sourceType === "production") {
        return '<span class="badge ms-1" style="background:#ede9fe;color:#6d28d9;border:1px solid #ddd6fe;padding:6px 8px;">Produksi</span>';
    }
    if (sourceType === "customer_return") {
        return '<span class="badge ms-1" style="background:#fff7ed;color:#c2410c;border:1px solid #ffedd5;padding:6px 8px;">Pengembalian</span>';
    }
    return "";
}
var transferScanLookupRun = 0;
var transferStockLoadRun = 0;
var transferStockLoads = {};
var retailSetupPrompts = {};
/** Filter list Stock Transfer */
var stFilterState = {
    date_from: "",
    date_to: "",
};
/** Konfirmasi/tolak dari modal detail: hide dulu, restore jika cancel. */
var transferOverlayState = {
    parent: "#add_stock_transfer",
    shouldRestore: false,
    done: false,
};

function transferParentModalOpen() {
    var $parent = $(transferOverlayState.parent);
    return $parent.length > 0 && ($parent.hasClass("show") || $parent.is(":visible"));
}

function beginTransferOverlay(parentSelector) {
    transferOverlayState.parent = parentSelector || "#add_stock_transfer";
    transferOverlayState.done = false;
    transferOverlayState.shouldRestore = false;
    if (!transferParentModalOpen()) {
        return false;
    }
    transferOverlayState.shouldRestore = true;
    $(transferOverlayState.parent).modal("hide");
    return true;
}

function markTransferOverlayDone() {
    transferOverlayState.done = true;
    transferOverlayState.shouldRestore = false;
}

function restoreTransferOverlayIfNeeded() {
    if (
        !transferOverlayState.shouldRestore ||
        transferOverlayState.done ||
        !transferOverlayState.parent
    ) {
        return;
    }
    var sel = transferOverlayState.parent;
    transferOverlayState.shouldRestore = false;
    $(sel).modal("show");
}

/** Konfirmasi di atas detail ST / accept: hide parent → show konfirmasi; cancel → buka lagi parent. */
function showTransferModalKonfirmasi(text, buttonId, dataId, parentSelector, danger, requirePhoto) {
    transferOverlayState.done = false;
    var wasOpen = beginTransferOverlay(parentSelector || "#add_stock_transfer");
    function openConfirm() {
        if (typeof showModalKonfirmasi === "function") {
            showModalKonfirmasi(text, buttonId, danger);
        }
        // Harus SETELAH showModalKonfirmasi (button id baru di-assign di situ)
        if (dataId != null && dataId !== "") {
            $("#modalKonfirmasi #" + buttonId).attr("data-id", dataId);
            $("#modalKonfirmasi").attr("data-transfer-id", dataId);
        }
        if (requirePhoto) {
            showKonfirmasiPhotoProof();
        } else {
            hideKonfirmasiPhotoProof();
        }
    }
    if (wasOpen) {
        $(transferOverlayState.parent).one("hidden.bs.modal", openConfirm);
    } else {
        openConfirm();
    }
}

/**
 * Bukti foto wajib saat Pending → Kirim (GitHub #140) — manual (tombol Kirim) maupun
 * auto-Kirim (approval terakhir request eceran). Field disuntik ke #modalKonfirmasi
 * yang dipakai bersama banyak halaman lain, jadi selalu di-reset & disembunyikan lagi
 * setelah dipakai supaya tidak "nempel" ke konfirmasi lain yang tidak terkait.
 */
function resetKonfirmasiPhotoProof() {
    $("#konfirmasi_photo_proof_base64").val("");
    $("#konfirmasi-photo-preview").addClass("d-none").attr("src", "");
    $("#konfirmasi-photo-proof-error").hide();
}
function showKonfirmasiPhotoProof() {
    transferPhotoProofRequired = true;
    resetKonfirmasiPhotoProof();
    $("#konfirmasi-photo-proof").removeClass("d-none");
}
function hideKonfirmasiPhotoProof() {
    transferPhotoProofRequired = false;
    $("#konfirmasi-photo-proof").addClass("d-none");
    resetKonfirmasiPhotoProof();
}
function getKonfirmasiPhotoProof() {
    return $("#konfirmasi_photo_proof_base64").val() || "";
}
function assertKonfirmasiPhotoProof() {
    if (!transferPhotoProofRequired) return true;
    if (getKonfirmasiPhotoProof()) return true;
    $("#konfirmasi-photo-proof-error").show();
    if (typeof toastr !== "undefined") {
        toastr.error("", "Bukti foto wajib diunggah sebelum Kirim");
    }
    return false;
}

/** Request eceran: apakah approval `type` ini yang melengkapi approval → auto-Kirim. */
function willTransferAutoShip(type) {
    var qcOk = !transferQcRequired || type === "qc" || transferQcApproved;
    var opsOk = !transferOpsRequired || type === "ops" || transferOpsApproved;
    return qcOk && opsOk;
}

$(document).on("click", "#btn-konfirmasi-photo-proof", function () {
    modeCamera = 5;
    rotationAngle = 0;
    camRotation = 0;
    photoData = "";
    inputFile = "#konfirmasi_photo_proof_base64";
    cameraReturnModal = "#modalKonfirmasi";
    $("#video").removeClass("rot90 rot180 rot270");
    $("#preview-box").hide();
    $("#camera").show();
    startCamera();
    transferKeepPhotoProofOnClose = true;
    $("#modalKonfirmasi").modal("hide");
    $("#modalPhoto").modal("show");
});

$(document).on("shown.bs.modal", "#modalKonfirmasi", function () {
    var val = getKonfirmasiPhotoProof();
    if (val) {
        $("#konfirmasi-photo-preview").attr("src", val).removeClass("d-none");
        $("#konfirmasi-photo-proof-error").hide();
    }
});

function showProductionRejectOverTransfer(id) {
    transferOverlayState.done = false;
    $("#reject_production_transfer").attr("data-id", id);
    $("#production_reject_notes").val("");
    var wasOpen = beginTransferOverlay("#add_stock_transfer");
    function openReject() {
        $("#reject_production_transfer").modal("show");
    }
    if (wasOpen) {
        $(transferOverlayState.parent).one("hidden.bs.modal", openReject);
    } else {
        openReject();
    }
}

$(document).on("hidden.bs.modal", "#modalKonfirmasi", function () {
    $("#modalKonfirmasi").removeAttr("data-transfer-id");
    $("#modalKonfirmasi .btn-konfirmasi").removeAttr("data-id");
    if (transferKeepPhotoProofOnClose) {
        transferKeepPhotoProofOnClose = false;
    } else {
        hideKonfirmasiPhotoProof();
    }
    restoreTransferOverlayIfNeeded();
});

$(document).on("hidden.bs.modal", "#reject_production_transfer", function () {
    restoreTransferOverlayIfNeeded();
});

function transferUnitLabel(unit) {
    unit = unit || {};
    var name = String(unit.unit_name || "").trim();
    var shortName = String(unit.unit_short_name || "").trim();
    if (!name) return shortName || "-";
    if (!shortName) return name;
    return name + " (" + shortName + ")";
}

function transferDefaultUnit(raw) {
    raw = raw || {};
    var id = parseInt(raw.default_unit_id || raw.unit_id, 10) || 0;
    if (!id) return null;
    return {
        unit_id: id,
        unit_name: raw.default_unit_name || raw.unit_name || raw.default_unit_short_name || "-",
        unit_short_name:
            raw.default_unit_short_name || raw.unit_short_name || raw.default_unit_name || "-",
    };
}

/** Satuan dari data autocomplete (pr_unit) — tanpa nunggu fetch stok. */
function transferUnitsFromRaw(raw) {
    raw = raw || {};
    var units = [];
    var seen = {};
    var list = Array.isArray(raw.pr_unit) ? raw.pr_unit : [];
    list.forEach(function (u) {
        var id = parseInt(u && u.unit_id, 10) || 0;
        if (!id || seen[id]) return;
        seen[id] = true;
        units.push({
            unit_id: id,
            unit_name: u.unit_name || "-",
            unit_short_name: u.unit_short_name || u.unit_name || "-",
        });
    });
    var def = transferDefaultUnit(raw);
    if (def && !seen[def.unit_id]) {
        units.unshift(def);
        seen[def.unit_id] = true;
    }
    return { units: units, defaultUnit: def };
}

function renderTransferDraftDefault(raw) {
    var packed = transferUnitsFromRaw(raw);
    var $unit = $("#transfer_unit_input").empty().removeClass("is-invalid");
    transferDraft.defaultUnitInvalid = false;

    if (!packed.units.length) {
        $unit.prop("disabled", true).append('<option value="">Unit produk belum diatur</option>');
        $("#transfer_stock_available")
            .text("Unit produk belum diatur")
            .addClass("text-danger");
        transferDraft.defaultUnitInvalid = true;
        return false;
    }

    $unit.append('<option value="">Pilih satuan</option>');
    packed.units.forEach(function (unit) {
        $unit.append(
            $("<option>", {
                value: unit.unit_id,
                text: transferUnitLabel(unit),
            })
        );
    });

    var selected = packed.defaultUnit || packed.units[0];
    $unit.prop("disabled", false).val(String(selected.unit_id));
    // Seed sementara supaya +Tambah bisa dipakai sebelum stok selesai load
    transferDraft.stock = { units: packed.units };
    $("#transfer_stock_available")
        .text("Memuat stok...")
        .removeClass("text-danger");
    return true;
}

function hasMissingRetailUnitRows() {
    return transferItems.some(function (item) {
        return item.retail_invalid === true;
    });
}

function hasInsufficientStockRows() {
    return transferItems.some(function (item) {
        return item.stock_invalid === true;
    });
}

function hasPendingTransferRows() {
    return transferItems.some(function (item) {
        return item.stock_loading === true;
    });
}

function beginTransferStockLoad() {
    var id = ++transferStockLoadRun;
    transferStockLoads[id] = true;
    stockLoadPending = Object.keys(transferStockLoads).length;
    return id;
}

function finishTransferStockLoad(id) {
    delete transferStockLoads[id];
    stockLoadPending = Object.keys(transferStockLoads).length;
}

function clearTransferStockLoads() {
    transferStockLoads = {};
    stockLoadPending = 0;
}

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
    $btn.prop(
        "disabled",
        isTransferWarehouseRouteInvalid() ||
            hasPendingTransferRows() ||
            retailUnitValidationPending > 0 ||
            stockValidationPending > 0 ||
            hasMissingRetailUnitRows() ||
            hasInsufficientStockRows()
    );
}

$(document).ready(function () {
    initStockTransferFilters();
    inisialisasi();
});

function getStockTransferFilterParams() {
    return {
        date_from: stFilterState.date_from || "",
        date_to: stFilterState.date_to || "",
        status: $("#st_filter_status").val() || "",
        from_warehouse_id: $("#st_filter_from_warehouse").val() || "",
    };
}

function reloadStockTransferTable() {
    if (typeof table !== "undefined" && table && table.ajax) {
        table.ajax.reload(null, false);
    }
}

/** Cell nama orang di list: request / pengirim / penerima */
function renderStockTransferPersonCell(name, role) {
    if (!name || name === "-") {
        return '<span class="text-muted">-</span>';
    }
    var tone = {
        request: { bg: "#f5f3ff", border: "#ddd6fe", color: "#6d28d9", icon: "fe-user-plus" },
        sender: { bg: "#eff6ff", border: "#bfdbfe", color: "#2563eb", icon: "fe-user" },
        receiver: { bg: "#ecfdf5", border: "#a7f3d0", color: "#059669", icon: "fe-user-check" },
    }[role] || { bg: "#f8fafc", border: "#e2e8f0", color: "#64748b", icon: "fe-user" };
    return `<div style="display:flex;align-items:center;gap:10px;min-width:0;">
                <div style="width:32px;height:32px;border-radius:8px;background:${tone.bg};border:1px solid ${tone.border};display:flex;align-items:center;justify-content:center;color:${tone.color};flex-shrink:0;">
                    <i class="fe ${tone.icon}"></i>
                </div>
                <span class="fw-semibold text-dark text-nowrap">${name}</span>
            </div>`;
}

function initStockTransferFilters() {
    if (!$("#st_filter_date").length) return;

    var $date = $("#st_filter_date");
    if (typeof $date.daterangepicker === "function" && typeof moment === "function") {
        $date.daterangepicker(
            {
                autoUpdateInput: false,
                alwaysShowCalendars: true,
                showDropdowns: true,
                locale: {
                    format: "DD-MM-YYYY",
                    separator: " — ",
                    applyLabel: "Terapkan",
                    cancelLabel: "Hapus",
                    fromLabel: "Dari",
                    toLabel: "Sampai",
                    customRangeLabel: "Kustom",
                    daysOfWeek: ["Mg", "Sn", "Sl", "Rb", "Km", "Jm", "Sb"],
                    monthNames: [
                        "Januari",
                        "Februari",
                        "Maret",
                        "April",
                        "Mei",
                        "Juni",
                        "Juli",
                        "Agustus",
                        "September",
                        "Oktober",
                        "November",
                        "Desember",
                    ],
                    firstDay: 1,
                },
                ranges: {
                    "Hari Ini": [moment(), moment()],
                    Kemarin: [moment().subtract(1, "days"), moment().subtract(1, "days")],
                    "7 Hari Terakhir": [moment().subtract(6, "days"), moment()],
                    "30 Hari Terakhir": [moment().subtract(29, "days"), moment()],
                    "Bulan Ini": [moment().startOf("month"), moment().endOf("month")],
                    "Bulan Lalu": [
                        moment().subtract(1, "month").startOf("month"),
                        moment().subtract(1, "month").endOf("month"),
                    ],
                },
            },
            function (startDate, endDate) {
                stFilterState.date_from = startDate.format("YYYY-MM-DD");
                stFilterState.date_to = endDate.format("YYYY-MM-DD");
            }
        );
        $date.on("apply.daterangepicker", function (ev, picker) {
            stFilterState.date_from = picker.startDate.format("YYYY-MM-DD");
            stFilterState.date_to = picker.endDate.format("YYYY-MM-DD");
            $(this).val(
                picker.startDate.format("DD-MM-YYYY") +
                    " — " +
                    picker.endDate.format("DD-MM-YYYY")
            );
            reloadStockTransferTable();
        });
        $date.on("cancel.daterangepicker", function () {
            stFilterState.date_from = "";
            stFilterState.date_to = "";
            $(this).val("");
            reloadStockTransferTable();
        });
        $date.val("");
    }

    if (typeof autocompleteWarehouse === "function") {
        autocompleteWarehouse("#st_filter_from_warehouse", "body", {
            placeholder: "Pilih gudang dari",
            allowClear: true,
            mainFirst: true,
        });
    }

    // Filter jalan saat input berubah (tanpa tombol Filter)
    var stFilterClearing = false;
    $(document).on("change", "#st_filter_status, #st_filter_from_warehouse", function () {
        if (stFilterClearing) return;
        reloadStockTransferTable();
    });
    $(document).on("click", ".btn-clear-st-filter", function (e) {
        e.preventDefault();
        stFilterClearing = true;
        stFilterState.date_from = "";
        stFilterState.date_to = "";
        $("#st_filter_status").val("");
        $("#st_filter_date").val("");
        $("#st_filter_from_warehouse").val(null).trigger("change");
        reloadStockTransferTable();
        setTimeout(function () {
            stFilterClearing = false;
        }, 0);
    });
}

/** Tombol Tambah: show/hide dari Blade (page-header). Guard klik tetap di sini. */
function syncStockTransferAddButton() {
    // no-op — visibility di-render server-side biar tidak kedip saat F5
}

function inisialisasi() {
    table = $("#tableStockTransfer").DataTable({
        bFilter: true,
        sDom: "fBtlpi",
        lengthMenu: [10, 25, 50, 100],
        ordering: true,
        order: [[0, "desc"]],
        // scrollX + wrap overflow-x = header/body desync; scroll horizontal lewat #tableStockTransfer-wrap
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
            data: function () {
                return getStockTransferFilterParams();
            },
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
                width: "10%",
                render: function(data, type) {
                    if(!data || data === "-") return type === "sort" || type === "type" ? "" : "-";
                    if (type === "sort" || type === "type") {
                        var parts = String(data).split("-");
                        if (parts.length === 3) {
                            return parts[2] + parts[1] + parts[0];
                        }
                        return String(data);
                    }
                    return `<div style="display:flex;align-items:center;gap:10px;min-width:0;">
                                <div style="width:32px;height:32px;border-radius:8px;background:#f8fafc;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;color:#64748b;flex-shrink:0;">
                                    <i class="fe fe-calendar"></i>
                                </div>
                                <span class="fw-semibold text-dark text-nowrap">${data}</span>
                            </div>`;
                }
            },
            {
                data: "status",
                width: "10%",
                className: "text-center",
                render: function (data, type, row) {
                    var status = parseInt(data, 10);
                    var isRetailReq =
                        row.is_retail_request === true ||
                        row.is_retail_request === 1 ||
                        row.source_type === "retail_request";
                    var activeWh =
                        typeof getActiveWarehouseId === "function"
                            ? String(getActiveWarehouseId() || "")
                            : "";
                    var fromWh = String(row.from_warehouse_id || "");
                    // Request eceran status=1 di gudang besar:
                    // Requested → Need Approval → (approval lengkap auto-Kirim, jarang "Siap Kirim")
                    if (status === 1 && isRetailReq && activeWh && activeWh === fromWh) {
                        var phase = row.approval_phase || "";
                        if (!phase) {
                            var qcReq = row.qc_required === true || row.qc_required === 1;
                            var opsReq = row.ops_required === true || row.ops_required === 1;
                            var qcOk = row.qc_approved === true || row.qc_approved === 1;
                            var opsOk = row.ops_approved === true || row.ops_approved === 1;
                            if (qcReq && !qcOk) phase = "requested";
                            else if (opsReq && !opsOk) phase = "need_approval";
                            else phase = "ready";
                        }
                        if (phase === "need_approval") {
                            return '<span class="badge" style="background-color: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 12px; letter-spacing: 0.3px;"><i class="fe fe-alert-circle me-1"></i> Need Approval</span>';
                        }
                        if (phase === "ready") {
                            return '<span class="badge" style="background-color: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 12px; letter-spacing: 0.3px;"><i class="fe fe-check-circle me-1"></i> Siap Kirim</span>';
                        }
                        return '<span class="badge" style="background-color: #eef2ff; color: #4338ca; border: 1px solid #c7d2fe; padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 12px; letter-spacing: 0.3px;"><i class="fe fe-inbox me-1"></i> Requested</span>';
                    }
                    if (status == 1) {
                        return '<span class="badge" style="background-color: #fff7ed; color: #ea580c; border: 1px solid #ffedd5; padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 12px; letter-spacing: 0.3px;"><i class="fe fe-clock me-1"></i> Pending</span>';
                    }
                    if (data == 2) {
                        // Bukti foto pengiriman (GitHub #140) — hanya muncul saat status Kirim.
                        var proofBtn = row && row.ship_proof_url
                            ? ' <a href="javascript:void(0);" class="btnViewShipProof" data-url="' +
                              row.ship_proof_url +
                              '" title="Lihat bukti foto pengiriman"><i class="fe fe-camera" style="color:#1d4ed8;vertical-align:middle;"></i></a>'
                            : "";
                        return '<span class="badge" style="background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 12px; letter-spacing: 0.3px;"><i class="fe fe-truck me-1"></i> Kirim</span>' + proofBtn;
                    }
                    if (data == 3) {
                        return '<span class="badge" style="background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 12px; letter-spacing: 0.3px;"><i class="fe fe-x-circle me-1"></i> Cancel</span>';
                    }
                    if (data == 4) {
                        return '<span class="badge" style="background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0; padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 12px; letter-spacing: 0.3px;"><i class="fe fe-check-circle me-1"></i> Terkirim</span>';
                    }
                    if (data == 5) {
                        return '<span class="badge" style="background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 12px; letter-spacing: 0.3px;"><i class="fe fe-rotate-ccw me-1"></i> Cancel Kirim</span>';
                    }
                    return "-";
                },
            },
            {
                data: "transfer_code",
                width: "10%",
                render: function(data, type, row) {
                    if(!data || data === "-") return "-";
                    var origin = stockTransferOriginBadge(row && row.source_type);
                    return `<div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;min-width:0;"><span class="badge" style="background:#f1f5f9;color:#334155;border:1px solid #cbd5e1;padding:6px 10px;">${data}</span>${origin}</div>`;
                }
            },
            {
                // Request eceran: sender_id = pemohon. Transfer biasa: tidak ada role request terpisah.
                data: "sender_name",
                width: "11%",
                render: function (data, type, row) {
                    var isRetailReq =
                        row.is_retail_request === true ||
                        row.is_retail_request === 1 ||
                        row.source_type === "retail_request";
                    if (!isRetailReq) {
                        return '<span class="text-muted">-</span>';
                    }
                    return renderStockTransferPersonCell(data, "request");
                }
            },
            {
                // Retail: Ops / Acc Kirim. Transfer biasa: sender_id.
                data: "sender_name",
                width: "11%",
                render: function (data, type, row) {
                    var isRetailReq =
                        row.is_retail_request === true ||
                        row.is_retail_request === 1 ||
                        row.source_type === "retail_request";
                    var name = data;
                    if (isRetailReq) {
                        var opsName = row.ops_approved_by_name;
                        var shipName = row.ship_acc_by_name;
                        if (opsName && opsName !== "-") name = opsName;
                        else if (shipName && shipName !== "-") name = shipName;
                        else name = "-";
                    }
                    return renderStockTransferPersonCell(name, "sender");
                }
            },
            {
                data: "receiver_name",
                width: "11%",
                render: function (data) {
                    return renderStockTransferPersonCell(data, "receiver");
                }
            },
            {
                data: "from_warehouse_name",
                width: "14%",
                className: "st-col-warehouse",
                render: function(data) {
                    if(!data || data === "-") return "-";
                    return `<div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:32px;height:32px;border-radius:8px;background:#eff6ff;border:1px solid #bfdbfe;display:flex;align-items:center;justify-content:center;color:#2563eb;flex-shrink:0;">
                                    <i class="fe fe-arrow-up-right"></i>
                                </div>
                                <span class="text-dark st-warehouse-name">${data}</span>
                            </div>`;
                }
            },
            {
                data: "to_warehouse_name",
                width: "14%",
                className: "st-col-warehouse",
                render: function(data) {
                    if(!data || data === "-") return "-";
                    return `<div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:32px;height:32px;border-radius:8px;background:#ecfdf5;border:1px solid #a7f3d0;display:flex;align-items:center;justify-content:center;color:#059669;flex-shrink:0;">
                                    <i class="fe fe-arrow-down-left"></i>
                                </div>
                                <span class="text-dark st-warehouse-name">${data}</span>
                            </div>`;
                }
            },
            {
                data: null,
                width: "5%",
                orderable: false,
                searchable: false,
                className: "text-center",
                render: function (data, type, row) {
                    var status = parseInt(row.status, 10);
                    var canShip = row.can_ship === true || row.can_ship === 1;
                    var canAcc = row.can_acc === true || row.can_acc === 1;
                    var canEdit = row.can_edit === true || row.can_edit === 1;
                    var canDelete = row.can_delete === true || row.can_delete === 1;
                    var canReject = row.can_reject === true || row.can_reject === 1;
                    var canCancelKirim = row.can_cancel_kirim === true || row.can_cancel_kirim === 1;
                    var pending = status === 1;
                    // Pending: selalu bisa dibuka (view). Edit unlock di modal jika can_edit.
                    var canOpenPending = pending;

                    // Pending: satu tombol buka modal (view/edit/transfer/tolak di dalam modal)
                    var openBtn = canOpenPending
                        ? `<a href="javascript:void(0);" class="me-2 p-2 btn-action-icon btnOpenTransfer text-primary" title="${canEdit ? 'Lihat / Edit / Proses' : 'Lihat / Proses Transfer'}" data-id="${row.id}">
                                <i class="fe fe-eye"></i>
                           </a>`
                        : "";

                    var viewBtn =
                        !pending && !canEdit && !canAcc
                            ? `<a href="javascript:void(0);" class="me-2 p-2 btn-action-icon btnViewTransfer" title="Lihat Detail" data-id="${row.id}">
                                <i class="fe fe-eye"></i>
                           </a>`
                            : "";

                    var accBtn = canAcc
                        ? `<a href="javascript:void(0);" class="me-2 p-2 btn-action-icon btnAccept btn-action-approve" title="ACC Terkirim" data-id="${row.id}">
                                <i class="fe fe-check-circle"></i>
                           </a>`
                        : "";
                    var isRetailRequest =
                        row.is_retail_request === true ||
                        row.is_retail_request === 1 ||
                        row.source_type === "retail_request";
                    // Retail: Cancel = reject (can_reject). Non-retail: soft-delete (can_delete).
                    var delBtn = isRetailRequest
                        ? canReject
                            ? `<a href="javascript:void(0);" class="p-2 btn-action-icon btnDeleteTransfer text-danger" title="Cancel" data-id="${row.id}" data-status="${status}" data-retail-request="1">
                                <i class="fe fe-x-circle"></i>
                           </a>`
                            : ""
                        : canDelete
                        ? `<a href="javascript:void(0);" class="p-2 btn-action-icon btnDeleteTransfer text-danger" title="Hapus Transfer (Pending saja)" data-id="${row.id}" data-status="${status}" data-retail-request="0">
                                <i class="fe fe-trash-2"></i>
                           </a>`
                        : "";
                    var cancelKirimBtn = canCancelKirim
                        ? `<a href="javascript:void(0);" class="p-2 btn-action-icon btnCancelKirimTransfer text-danger" title="Cancel Kirim" data-id="${row.id}">
                                <i class="fe fe-rotate-ccw"></i>
                           </a>`
                        : "";
                    return `
                        <div class="d-flex justify-content-center gap-1">
                            ${openBtn}
                            ${viewBtn}
                            ${accBtn}
                            ${delBtn}
                            ${cancelKirimBtn}
                        </div>
                    `;
                },
            },
        ],
        drawCallback: function () {
            if (table) {
                try {
                    table.columns.adjust();
                } catch (e) {}
            }
        },
        initComplete: function () {
            $(".dataTables_filter").appendTo("#tableSearch");
            $(".dataTables_filter").appendTo(".search-input");
            $("#tableStockTransfer-wrap").removeClass("dt-pending").addClass("dt-ready");
            var api = this.api();
            setTimeout(function () {
                try {
                    api.columns.adjust();
                } catch (e) {}
            }, 50);
        },
    });
}

function isActiveWarehouseRetail() {
    return typeof isActiveMainWarehouse === "function" && isActiveMainWarehouse() === false;
}

function initTransferAutocompletes() {
    var parent = "#add_stock_transfer";
    autocompleteStaff("#transfer_sender_id", parent);
    if (mode === 1) {
        transferCreateRequestMode = isActiveWarehouseRetail();
        transferIsRetailRequest = transferCreateRequestMode;
    }
    initTransferWarehouseAutocompletes();
    applyTransferRouteLabels(transferIsRetailRequest);
}

/** Autocomplete gudang sesuai mode request vs transfer biasa. */
function initTransferWarehouseAutocompletes() {
    var parent = "#add_stock_transfer";
    if (transferIsRetailRequest) {
        // Gudang Request = pilih gudang utama (stok diminta)
        autocompleteWarehouse("#transfer_from_warehouse_id", parent, {
            mainOnly: true,
            placeholder: "Pilih gudang request",
        });
        autocompleteWarehouse("#transfer_to_warehouse_id", parent, { mainFirst: true });
    } else {
        autocompleteWarehouse("#transfer_from_warehouse_id", parent);
        autocompleteWarehouse("#transfer_to_warehouse_id", parent, { mainFirst: true });
    }
}

function applyTransferRouteLabels(requestMode) {
    var $modal = $("#add_stock_transfer");
    if (!$modal.length) return;
    var $row = $modal.find(".st-route-row");
    var $arrow = $modal.find(".st-route-arrow-icon");
    var $arrowText = $modal.find(".st-route-arrow-text");

    if (requestMode) {
        // UI: kiri = penerima (to), kanan = request/kirim (from) — card dibalik via order
        $row.addClass("st-request-mode");
        $modal.find(".st-card-tujuan").css("order", "1");
        $modal.find(".st-card-arrow").css("order", "2");
        $modal.find(".st-card-asal").css("order", "3");
        $arrow.removeClass("fe-arrow-right").addClass("fe-arrow-left");
        $arrowText.text("REQUEST");
        // Card BE-from (kanan visual) = Gudang Request (yang kirim)
        $modal.find(".st-label-from-card").text("Gudang Request");
        $modal.find(".st-label-from-field").text("Gudang Request");
        $modal.find(".st-label-sender-field").text("Yang Request");
        // Card BE-to (kiri visual) = Gudang Penerima
        $modal.find(".st-label-to-card").text("Gudang Penerima");
        $modal.find(".st-label-to-field").text("Gudang yang menerima");
    } else {
        $row.removeClass("st-request-mode");
        $modal.find(".st-card-asal, .st-card-arrow, .st-card-tujuan").css("order", "");
        $arrow.removeClass("fe-arrow-left").addClass("fe-arrow-right");
        $arrowText.text("TRANSFER");
        $modal.find(".st-label-from-card").text("Dari (Asal)");
        $modal.find(".st-label-from-field").html("Gudang / Toko Asal");
        $modal.find(".st-label-sender-field").text("Pengirim");
        $modal.find(".st-label-to-card").html("Ke (Tujuan)");
        $modal.find(".st-label-to-field").html("Gudang Tujuan");
    }
    syncSenderFieldPlacement(requestMode);
}

/** Mode request: Yang Request + tanggal di kiri (penerima); catatan di kanan (request). */
function syncSenderFieldPlacement(requestMode) {
    var $senderBlock = $("#st-sender-block");
    var $dateBlock = $("#st-date-block");
    var $noteBlock = $("#st-note-block");
    var $asalSender = $("#st-sender-slot-asal");
    var $tujuanSender = $("#st-sender-slot-tujuan");
    var $asalDate = $("#st-date-slot-asal");
    var $tujuanDate = $("#st-date-slot-tujuan");
    var $asalNote = $("#st-note-slot-asal");
    var $tujuanNote = $("#st-note-slot-tujuan");
    var $fromCol = $("#st-from-warehouse-col");
    var $toCol = $("#st-to-warehouse-col");
    var $sender = $("#transfer_sender_id");
    if (!$senderBlock.length || !$asalSender.length || !$tujuanSender.length) return;

    if ($sender.hasClass("select2-hidden-accessible")) {
        try {
            $sender.select2("destroy");
        } catch (e) {}
    }

    if (requestMode) {
        // Kiri (penerima): Yang Request + gudang penerima + tanggal
        $tujuanSender.removeClass("d-none").append($senderBlock);
        $asalSender.addClass("d-none");
        $tujuanDate.removeClass("d-none").append($dateBlock);
        $asalDate.addClass("d-none");
        // Kanan (request): gudang request + catatan
        $asalNote.removeClass("d-none").append($noteBlock);
        $tujuanNote.addClass("d-none");
        $fromCol.removeClass("col-6").addClass("col-12");
        $toCol.removeClass("col-12").addClass("col-6");
    } else {
        // Normal: pengirim+asal+tanggal kiri; tujuan+catatan kanan
        $asalSender.removeClass("d-none").append($senderBlock);
        $tujuanSender.addClass("d-none");
        $asalDate.removeClass("d-none").append($dateBlock);
        $tujuanDate.addClass("d-none");
        $tujuanNote.removeClass("d-none").append($noteBlock);
        $asalNote.addClass("d-none");
        $fromCol.removeClass("col-12").addClass("col-6");
        $toCol.removeClass("col-6").addClass("col-12");
    }

    if (typeof autocompleteStaff === "function") {
        autocompleteStaff("#transfer_sender_id", "#add_stock_transfer");
    }
    setDefaultSender();
}

function unlockTransferFromWarehouse() {
    var $el = $("#transfer_from_warehouse_id");
    if (!$el.length) return;
    $el.prop("disabled", false);
    if ($el.hasClass("select2-hidden-accessible")) {
        $el.trigger("change.select2");
    }
}

function lockTransferToWarehouse() {
    var $el = $("#transfer_to_warehouse_id");
    if (!$el.length) return;
    $el.prop("disabled", true);
    if ($el.hasClass("select2-hidden-accessible")) {
        $el.trigger("change.select2");
    }
}

function unlockTransferToWarehouse() {
    var $el = $("#transfer_to_warehouse_id");
    if (!$el.length || transferFormLocked) return;
    $el.prop("disabled", false);
    if ($el.hasClass("select2-hidden-accessible")) {
        $el.trigger("change.select2");
    }
}

/** Mode request eceran: penerima = gudang aktif (terkunci). */
function setDefaultReceivingWarehouse() {
    var id = typeof getActiveWarehouseId === "function" ? getActiveWarehouseId() : null;
    var wh = window.activeWarehouse || {};
    if (!id) id = wh.id || null;
    if (!id) return;
    var text =
        (typeof getActiveWarehouseName === "function" && getActiveWarehouseName()) ||
        wh.text ||
        wh.name ||
        "Gudang #" + id;
    var $el = $("#transfer_to_warehouse_id");
    if (!$el.length) return;
    fillSelectOption($el, id, text);
    if ($el.hasClass("select2-hidden-accessible")) {
        var data = $el.select2("data") || [];
        if (data[0]) data[0].is_main_warehouse = 0;
    }
    lockTransferToWarehouse();
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

function resetTransferDraft(clearProduct) {
    transferDraftRun++;
    transferDraft.raw = null;
    transferDraft.stock = null;
    transferDraft.loading = false;
    transferDraft.requireDefaultUnit = false;
    transferDraft.defaultUnitInvalid = false;
    $("#transfer_qty_input").val(1).removeClass("is-invalid");
    $("#transfer_unit_input")
        .prop("disabled", true)
        .removeClass("is-invalid")
        .html('<option value="">Pilih produk dahulu</option>');
    $("#transfer_stock_available").text("Stok tersedia: -").removeClass("text-danger");
    $("#btn_add_transfer_product").prop("disabled", false);
    if (clearProduct !== false && $("#transfer_sku").hasClass("select2-hidden-accessible")) {
        transferDraftSelectGuard = true;
        $("#transfer_sku").val(null).trigger("change");
        transferDraftSelectGuard = false;
    }
}

function draftUnitById(unitId) {
    var units = (transferDraft.stock && transferDraft.stock.units) || [];
    return units.find(function (unit) {
        return String(unit.unit_id) === String(unitId);
    });
}

function updateTransferDraftAvailable() {
    var unit = draftUnitById($("#transfer_unit_input").val());
    if (!unit) {
        $("#transfer_stock_available").text("Stok tersedia: -");
        return;
    }
    // available_qty = ekuivalen (utama termasuk unpack ancestor); ps_stock = fisik satuan.
    var available =
        unit.available_qty != null ? unit.available_qty : unit.ps_stock != null ? unit.ps_stock : 0;
    $("#transfer_stock_available").text(
        "Stok tersedia: " +
            formatTransferQty(available) +
            " " +
            transferUnitLabel(unit)
    );
}

function renderTransferDraftStock(stock) {
    var units = Array.isArray(stock && stock.units) ? stock.units.slice() : [];
    var defaultUnit = transferDefaultUnit(transferDraft.raw);
    var defaultUnitId =
        (stock && stock.default_unit_id) || (defaultUnit && defaultUnit.unit_id) || null;
    var sourceIsMain = stock && stock.warehouse_is_main === true;
    if (!sourceIsMain && stock && stock.warehouse_is_main === false && stock.retail_unit_id) {
        units = units.filter(function (unit) {
            return String(unit.unit_id) === String(stock.retail_unit_id);
        });
    }
    var $unit = $("#transfer_unit_input")
        .empty()
        .removeClass("is-invalid")
        .append('<option value="">Pilih satuan</option>');

    units.forEach(function (unit) {
        $unit.append(
            $("<option>", {
                value: unit.unit_id,
                text: transferUnitLabel(unit),
            })
        );
    });

    transferDraft.defaultUnitInvalid = false;
    if (!units.length) {
        $unit.prop("disabled", true);
        $("#transfer_stock_available")
            .text((stock && stock.message) || "Stok/satuan valid tidak tersedia")
            .addClass("text-danger");
        transferDraft.defaultUnitInvalid = true;
        return;
    }

    var selectedUnit =
        units.find(function (unit) {
            return String(unit.unit_id) === String(defaultUnitId);
        }) || units[0];

    $unit.prop("disabled", false).val(String(selectedUnit.unit_id));
    $("#transfer_stock_available").removeClass("text-danger");
    updateTransferDraftAvailable();
}

function loadTransferDraftStock(raw, done) {
    var variantId = parseInt(raw && (raw.product_variant_id || raw.id), 10);
    var fromId = $("#transfer_from_warehouse_id").val();
    var toId = $("#transfer_to_warehouse_id").val();
    var runId = ++transferDraftRun;
    transferDraft.raw = raw;
    transferDraft.loading = true;
    transferDraft.requireDefaultUnit = false;
    var hasUnits = renderTransferDraftDefault(raw);
    // Jangan disable satuan/+Tambah — satuan sudah dari data produk
    $("#btn_add_transfer_product").prop("disabled", !hasUnits || transferDraft.defaultUnitInvalid);

    if (!variantId || !fromId || !toId || !validateWarehousesDifferent()) {
        transferDraft.loading = false;
        $("#transfer_stock_available")
            .text(!toId ? "Pilih gudang tujuan untuk memeriksa stok" : "Rute gudang tidak valid")
            .addClass("text-danger");
        if (typeof done === "function") done(false);
        return;
    }

    fetchSourceStock(variantId, function (stock) {
        if (runId !== transferDraftRun) return;
        transferDraft.loading = false;
        transferDraft.stock = stock || { units: [] };
        var prevUnitId = $("#transfer_unit_input").val();
        renderTransferDraftStock(transferDraft.stock);
        // Pertahankan satuan yang sudah dipilih user jika masih valid
        if (
            prevUnitId &&
            $("#transfer_unit_input option[value='" + prevUnitId + "']").length
        ) {
            $("#transfer_unit_input").val(String(prevUnitId));
            updateTransferDraftAvailable();
        }
        $("#btn_add_transfer_product").prop("disabled", transferDraft.defaultUnitInvalid);
        if (transferDraft.defaultUnitInvalid) {
            if (isRetailUnitSetupRequired(stock, raw)) {
                prepareRetailUnitForTransfer(raw, true, function (ok) {
                    if (
                        !ok &&
                        runId === transferDraftRun &&
                        typeof toastr !== "undefined"
                    ) {
                        toastr.error("", $("#transfer_stock_available").text());
                    }
                });
            } else if (typeof toastr !== "undefined") {
                toastr.error("", $("#transfer_stock_available").text());
            }
        }
        if (typeof done === "function") {
            done(
                !!(stock && stock.units && stock.units.length) &&
                    !transferDraft.defaultUnitInvalid
            );
        }
    });
}

function clearTransferWarehouseFieldInvalid($select) {
    $select.removeClass("is-invalid");
    $select
        .next(".select2-container")
        .find(".select2-selection")
        .removeClass("is-invalid is-invalids");
}

function markTransferToWarehouseInvalid() {
    var $to = $("#transfer_to_warehouse_id");
    $to.addClass("is-invalid");
    $to.next(".select2-container").find(".select2-selection").addClass("is-invalid is-invalids");
}

function isTransferWarehouseRouteInvalid() {
    var fromId = $("#transfer_from_warehouse_id").val();
    var toId = $("#transfer_to_warehouse_id").val();
    return !!(fromId && toId && String(fromId) === String(toId));
}

function validateWarehousesDifferent() {
    clearTransferWarehouseFieldInvalid($("#transfer_from_warehouse_id"));
    clearTransferWarehouseFieldInvalid($("#transfer_to_warehouse_id"));

    if (isTransferWarehouseRouteInvalid()) {
        markTransferToWarehouseInvalid();
        if (typeof toastr !== "undefined") {
            toastr.warning("", "Gudang tujuan tidak boleh sama dengan gudang asal");
        } else if (typeof notifikasi === "function") {
            notifikasi("warning", "Validasi", "Gudang tujuan tidak boleh sama dengan gudang asal");
        }
        $("#btn_add_transfer_product").prop("disabled", true);
        syncTransferSaveButton();
        return false;
    }
    syncTransferSaveButton();
    return true;
}

function selectedTransferWarehouseIsMain(selector) {
    var $el = $(selector);
    if (!$el.hasClass("select2-hidden-accessible")) return null;
    var selected = $el.select2("data") || [];
    if (selected.length && selected[0].is_main_warehouse != null) {
        return parseInt(selected[0].is_main_warehouse, 10) === 1;
    }
    var $opt = $el.find("option:selected");
    if ($opt.length && $opt.attr("data-is_main_warehouse") != null) {
        return parseInt($opt.attr("data-is_main_warehouse"), 10) === 1;
    }
    return null;
}

function isRetailUnitSetupRequired(stock, raw) {
    if (selectedTransferWarehouseIsMain("#transfer_to_warehouse_id") !== false) return false;
    if (raw && parseInt(raw.retail_unit, 10) > 0) return false;
    var msg = String((stock && stock.message) || "").toLowerCase();
    return msg.indexOf("satuan eceran") !== -1;
}

function transferRetailContext(item) {
    var mode =
        transferItems.indexOf(item) === -1 && transferDraft.raw === item ? "draft" : "row";
    return {
        item: item,
        mode: mode,
        variantId: parseInt(item.product_variant_id || item.id, 10),
        fromId: $("#transfer_from_warehouse_id").val(),
        toId: $("#transfer_to_warehouse_id").val(),
        routeRun: transferRouteRun,
        draftRun: transferDraftRun,
    };
}

function isCurrentRetailContext(context) {
    if (!context || context.routeRun !== transferRouteRun) return false;
    if (
        String($("#transfer_from_warehouse_id").val() || "") !== String(context.fromId || "") ||
        String($("#transfer_to_warehouse_id").val() || "") !== String(context.toId || "")
    ) {
        return false;
    }
    if (context.mode === "draft") {
        return context.draftRun === transferDraftRun && transferDraft.raw === context.item;
    }
    return transferItems.indexOf(context.item) !== -1;
}

function applySavedRetailUnit(item, retailUnitId, context) {
    transferItems.forEach(function (rowItem) {
        if (String(rowItem.product_variant_id) !== String(item.product_variant_id)) return;
        rowItem.retail_unit = retailUnitId;
        rowItem.retail_invalid = false;
        rowItem.retail_unit_options = [];
        rowItem.retail_error = null;
        validateOptimisticTransferRow(rowItem, false);
    });
    if (context && context.mode === "draft" && transferDraft.raw === item) {
        item.retail_unit = retailUnitId;
        transferDraft.defaultUnitInvalid = false;
        loadTransferDraftStock(item);
    } else {
        refreshTransferItemsTable();
        syncTransferSaveButton();
    }
}

function saveRetailUnitForTransfer(item, unitId, context) {
    return $.ajax({
        url: "/saveTransferRetailUnit",
        method: "post",
        data: {
            product_variant_id: item.product_variant_id,
            unit_id: unitId,
            from_warehouse_id: context.fromId,
            to_warehouse_id: context.toId,
            _token: token || $('meta[name="csrf-token"]').attr("content"),
        },
    }).then(function (res) {
        if (!isCurrentRetailContext(context)) {
            throw new Error("Rute gudang sudah berubah");
        }
        if (!res || res.status !== 1) {
            throw new Error((res && res.message) || "Gagal menyimpan satuan eceran");
        }
        applySavedRetailUnit(item, res.retail_unit_id, context);
        return res;
    });
}

function promptRetailUnitForTransfer(item, context) {
    var variantKey = String(item.product_variant_id);
    if (
        !isCurrentRetailContext(context) ||
        retailSetupPrompts[variantKey] ||
        typeof Swal === "undefined"
    ) {
        return;
    }
    var units = item.retail_unit_options || [];
    if (!units.length) return;

    var options = {};
    units.forEach(function (unit) {
        options[String(unit.unit_id)] = transferUnitLabel(unit);
    });
    retailSetupPrompts[variantKey] = true;

    Swal.fire({
        icon: "warning",
        title: "Satuan eceran belum diatur",
        html:
            "Pilih satuan eceran untuk <strong>" +
            escapeHtml(
                (item.product_name || item.pr_name || "") +
                    " " +
                    (item.product_variant_name || "")
            ) +
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
            if (!isCurrentRetailContext(context)) {
                Swal.showValidationMessage("Rute gudang sudah berubah");
                return false;
            }
            return saveRetailUnitForTransfer(item, unitId, context).catch(function (xhr) {
                var message =
                    (xhr.responseJSON && xhr.responseJSON.message) ||
                    xhr.message ||
                    "Gagal menyimpan satuan eceran";
                Swal.showValidationMessage(message);
            });
        },
    }).then(function () {
        delete retailSetupPrompts[variantKey];
        if (!isCurrentRetailContext(context) || item.retail_unit) return;
        if (context.mode === "draft") {
            transferDraft.defaultUnitInvalid = true;
            return;
        }
        item.retail_invalid = true;
        refreshTransferItemsTable();
        syncTransferSaveButton();
    });
}

function prepareRetailUnitForTransfer(item, promptAfterLoad, done) {
    var context = transferRetailContext(item);
    var variantId = context.variantId;
    var fromWarehouseId = context.fromId;
    var toWarehouseId = context.toId;
    if (!variantId || !fromWarehouseId || !toWarehouseId) {
        if (typeof done === "function") done(false);
        return;
    }
    if (
        item._retail_setup_request &&
        item._retail_setup_request.routeRun === context.routeRun
    ) {
        item._retail_setup_request.prompt =
            item._retail_setup_request.prompt || !!promptAfterLoad;
        if (typeof done === "function") item._retail_setup_request.callbacks.push(done);
        return;
    }

    var request = {
        routeRun: context.routeRun,
        prompt: !!promptAfterLoad,
        callbacks: typeof done === "function" ? [done] : [],
    };
    item._retail_setup_request = request;

    $.ajax({
        url: "/getTransferRetailUnitSetup",
        method: "post",
        data: {
            product_variant_id: variantId,
            from_warehouse_id: fromWarehouseId,
            to_warehouse_id: toWarehouseId,
            _token: token || $('meta[name="csrf-token"]').attr("content"),
        },
        success: function (res) {
            if (!isCurrentRetailContext(context)) return;
            if (!res || res.status !== 1) {
                item.retail_invalid = true;
                item.retail_error = (res && res.message) || "Gagal memeriksa satuan eceran";
                return;
            }
            if (!res.requires_setup) {
                item.retail_unit = res.retail_unit_id || null;
                item.retail_invalid = false;
                item.retail_unit_options = [];
                item.retail_error = null;
                return;
            }
            item.retail_unit = null;
            item.retail_invalid = true;
            item.retail_unit_options = Array.isArray(res.units) ? res.units : [];
            item.retail_error =
                res.message ||
                (item.retail_unit_options.length
                    ? "Satuan eceran wajib dilengkapi"
                    : "Satuan produk yang valid tidak tersedia");
            refreshTransferItemsTable();
            syncTransferSaveButton();
        },
        error: function (xhr) {
            if (!isCurrentRetailContext(context)) return;
            item.retail_invalid = true;
            item.retail_error =
                (xhr.responseJSON && xhr.responseJSON.message) ||
                "Gagal memeriksa satuan eceran";
        },
        complete: function () {
            if (item._retail_setup_request !== request) return;
            delete item._retail_setup_request;
            if (!isCurrentRetailContext(context)) return;
            refreshTransferItemsTable();
            syncTransferSaveButton();
            if (request.prompt && item.retail_invalid) {
                promptRetailUnitForTransfer(item, context);
            }
            request.callbacks.forEach(function (callback) {
                callback(!item.retail_invalid);
            });
        },
    });
}

function buildUnitOptions(item) {
    var units = item.units && item.units.length ? item.units : item.pr_unit || [];
    if (!units.length) {
        return '<option value="">-</option>';
    }
    var html = "";
    units.forEach(function (u) {
        var uid = u.unit_id;
        var label = transferUnitLabel(u);
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

function buildRetailUnitOptions(item) {
    var units = item.retail_unit_options || [];
    var html = '<option value="">Pilih satuan eceran</option>';
    units.forEach(function (unit) {
        html +=
            '<option value="' +
            unit.unit_id +
            '">' +
            escapeHtml(transferUnitLabel(unit)) +
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
                <td colspan="6" class="text-center text-muted py-5" style="font-size:14px;">Belum ada produk. Pilih gudang asal terlebih dahulu, lalu pilih/scan produk.</td>
            </tr>
        `);
        return;
    }

    var locked = transferFormLocked === true;
    transferItems.forEach(function (item, index) {
        var rowClass = item.retail_invalid
            ? " transfer-row-retail-error"
            : item.stock_invalid
              ? " transfer-row-stock-error"
              : "";
        var unitDisabled = locked || item.stock_loading ? "disabled" : "";
        var unitControl = item.retail_invalid
            ? `
                <select class="form-select form-select-sm transfer-retail-unit" data-index="${index}" ${locked ? "disabled" : ""}>
                    ${buildRetailUnitOptions(item)}
                </select>
                <small class="transfer-retail-error-text">
                    ${escapeHtml(item.retail_error || "Satuan eceran wajib dilengkapi")}
                </small>
            `
            : `
                <select class="form-select form-select-sm transfer-unit" data-index="${index}" ${unitDisabled}>
                    ${buildUnitOptions(item)}
                </select>
            `;
        $tbody.append(`
            <tr class="${rowClass}" data-index="${index}" data-variant-id="${item.product_variant_id}">
                <td style="padding: 14px 12px;">${escapeHtml(item.product_name || "-")}</td>
                <td style="padding: 14px 12px;">${escapeHtml(item.product_variant_name || "-")}</td>
                <td style="padding: 14px 12px;">${escapeHtml(item.product_variant_sku || "-")}</td>
                <td class="col-stock-asal" style="padding: 14px 8px;">
                    ${renderTransferStockAsalHtml(item)}
                </td>
                <td class="col-qty-unit" style="padding: 14px 8px;">
                    <div class="transfer-qty-unit-wrap">
                        <input type="number" class="form-control form-control-sm transfer-qty" min="1" step="1"
                            value="${item.qty}" data-index="${index}" ${locked ? "disabled" : ""}>
                        <div class="transfer-unit-wrap">
                            ${unitControl}
                        </div>
                    </div>
                </td>
                <td class="text-center" style="padding: 14px 8px;">
                    ${
                        locked
                            ? '<span class="text-muted">-</span>'
                            : `<a class="p-2 btn-action-icon text-danger btn-remove-transfer-item" href="javascript:void(0);" data-index="${index}">
                                <i class="fe fe-trash-2"></i>
                           </a>`
                    }
                </td>
            </tr>
        `);
    });
}

/** Update satu baris tanpa rebuild tbody (supaya input qty tidak kehilangan fokus). */
function patchTransferItemsRow(item) {
    var idx = transferItems.indexOf(item);
    if (idx < 0) return;
    var $row = $('#tableTransferItems tbody tr[data-index="' + idx + '"]');
    if (!$row.length) {
        refreshTransferItemsTable();
        return;
    }

    var hasRetailSelect = $row.find(".transfer-retail-unit").length > 0;
    if (!!item.retail_invalid !== hasRetailSelect) {
        var $focused = $(document.activeElement);
        var focusIdx = $focused.hasClass("transfer-qty")
            ? parseInt($focused.attr("data-index"), 10)
            : -1;
        var focusVal = focusIdx >= 0 ? $focused.val() : null;
        var selStart = focusIdx >= 0 ? $focused[0].selectionStart : null;
        var selEnd = focusIdx >= 0 ? $focused[0].selectionEnd : null;
        refreshTransferItemsTable();
        if (focusIdx >= 0) {
            var $qtyRestore = $(
                '#tableTransferItems .transfer-qty[data-index="' + focusIdx + '"]'
            );
            if ($qtyRestore.length) {
                if (focusVal != null) $qtyRestore.val(focusVal);
                $qtyRestore.trigger("focus");
                try {
                    if (selStart != null) {
                        $qtyRestore[0].setSelectionRange(selStart, selEnd);
                    }
                } catch (e) {}
            }
        }
        return;
    }

    var locked = transferFormLocked === true;
    $row
        .toggleClass("transfer-row-retail-error", !!item.retail_invalid)
        .toggleClass(
            "transfer-row-stock-error",
            !!item.stock_invalid && !item.retail_invalid
        );

    var stockHtml = renderTransferStockAsalHtml(item);
    $row.find(".col-stock-asal").html(stockHtml);

    var $qty = $row.find(".transfer-qty");
    if (!$qty.is(":focus")) {
        $qty.val(item.qty);
    }
    $qty.prop("disabled", locked);

    if (item.retail_invalid) {
        var $retail = $row.find(".transfer-retail-unit");
        if (!$retail.is(":focus")) {
            $retail.html(buildRetailUnitOptions(item));
        }
        $retail.prop("disabled", locked);
        $row
            .find(".transfer-retail-error-text")
            .text(item.retail_error || "Satuan eceran wajib dilengkapi");
    } else {
        var $unit = $row.find(".transfer-unit");
        if (!$unit.is(":focus")) {
            $unit.html(buildUnitOptions(item)).val(String(item.unit_id));
        }
        $unit.prop("disabled", locked || !!item.stock_loading);
        $unit.siblings("small.text-muted").remove();
    }
}

function escapeHtml(str) {
    return String(str == null ? "" : str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;");
}

function transferStockCheckSpinnerHtml() {
    return (
        '<span class="transfer-stock-check-spinner spinner-border spinner-border-sm text-primary" ' +
        'role="status" aria-label="Memeriksa stok"></span>'
    );
}

function renderTransferStockAsalHtml(item) {
    if (item && item.stock_loading) {
        return transferStockCheckSpinnerHtml();
    }
    var html = escapeHtml((item && item.stock_text) || "…");
    if (item && item.stock_invalid) {
        html +=
            '<small class="transfer-stock-error-text">' +
            escapeHtml(
                item.stock_error ||
                    "Stok tidak cukup. Tersedia: " +
                        formatTransferQty(item.available_qty || 0)
            ) +
            "</small>";
    }
    return html;
}

function abortTransferRowXhrs(item) {
    if (!item) return;
    ["_source_stock_xhr", "_check_stock_xhr"].forEach(function (key) {
        var xhr = item[key];
        if (xhr && xhr.readyState !== 4) {
            try {
                xhr.abort();
            } catch (e) {}
        }
        item[key] = null;
    });
}

function isAjaxAbort(xhr, textStatus) {
    return textStatus === "abort" || (xhr && xhr.statusText === "abort");
}

function fetchSourceStock(productVariantId, done, item) {
    var warehouseId = $("#transfer_from_warehouse_id").val();
    if (!warehouseId || !productVariantId) {
        done({ stock_text: "-", units: [] });
        return null;
    }
    if (item) {
        if (item._source_stock_xhr && item._source_stock_xhr.readyState !== 4) {
            try {
                item._source_stock_xhr.abort();
            } catch (e) {}
        }
        item._source_stock_xhr = null;
    }
    var xhr = $.ajax({
        url: "/getTransferSourceStock",
        method: "get",
        data: {
            warehouse_id: warehouseId,
            product_variant_id: productVariantId,
            to_warehouse_id: $("#transfer_to_warehouse_id").val() || null,
        },
        success: function (res) {
            done(res || { stock_text: "0", units: [] });
        },
        error: function (err, textStatus) {
            if (isAjaxAbort(err, textStatus)) return;
            done({ stock_text: "-", units: [] });
        },
        complete: function () {
            if (item && item._source_stock_xhr === xhr) {
                item._source_stock_xhr = null;
            }
        },
    });
    if (item) item._source_stock_xhr = xhr;
    return xhr;
}

function validateOptimisticTransferRow(item, showToast, promptRetailSetup) {
    if (!item || transferItems.indexOf(item) === -1) return;

    abortTransferRowXhrs(item);
    clearTimeout(item._validation_timer);
    item._validation_timer = null;

    var fromId = $("#transfer_from_warehouse_id").val();
    var toId = $("#transfer_to_warehouse_id").val();
    var routeRun = transferRouteRun;
    var rowRun = (item._validation_run || 0) + 1;
    var finished = false;
    item._validation_run = rowRun;
    item.stock_loading = true;
    item.stock_invalid = false;
    item.stock_error = null;
    if (
        selectedTransferWarehouseIsMain("#transfer_to_warehouse_id") !== false ||
        parseInt(item.retail_unit, 10) > 0
    ) {
        item.retail_invalid = false;
        item.retail_unit_options = [];
        item.retail_error = null;
    }
    patchTransferItemsRow(item);
    syncTransferSaveButton();

    function isCurrent() {
        return (
            transferItems.indexOf(item) !== -1 &&
            item._validation_run === rowRun &&
            routeRun === transferRouteRun &&
            String($("#transfer_from_warehouse_id").val() || "") === String(fromId || "") &&
            String($("#transfer_to_warehouse_id").val() || "") === String(toId || "")
        );
    }

    function finish() {
        if (finished) return;
        finished = true;
        if (isCurrent()) {
            item.stock_loading = false;
            patchTransferItemsRow(item);
        }
        syncTransferSaveButton();
    }

    function fail(message) {
        if (isCurrent()) {
            item.stock_invalid = true;
            item.stock_error = message;
        }
        finish();
    }

    function validateMatrix() {
        if (!isCurrent()) {
            finish();
            return;
        }
        if (item.stock_invalid || item.retail_invalid) {
            finish();
            return;
        }

        if (item._check_stock_xhr && item._check_stock_xhr.readyState !== 4) {
            try {
                item._check_stock_xhr.abort();
            } catch (e) {}
        }
        item._check_stock_xhr = $.ajax({
            url: "/checkTransferStock",
            method: "post",
            data: {
                from_warehouse_id: fromId,
                to_warehouse_id: toId,
                items: [
                    {
                        product_variant_id: item.product_variant_id,
                        unit_id: item.unit_id,
                        qty: item.qty,
                        label:
                            (item.product_name || "") + " " + (item.product_variant_name || ""),
                    },
                ],
                _token: token || $('meta[name="csrf-token"]').attr("content"),
            },
            success: function (res) {
                if (!isCurrent()) return;
                if (!res) {
                    item.stock_invalid = true;
                    item.stock_error = "Stok gagal divalidasi. Coba lagi.";
                    return;
                }
                if (res.matrix_error) {
                    item.stock_invalid = true;
                    item.stock_error =
                        res.message ||
                        "Satuan terpilih tidak valid untuk rute gudang ini";
                    return;
                }

                var shortages = Array.isArray(res.shortages) ? res.shortages : [];
                var shortage = shortages.find(function (row) {
                    return (
                        String(row.product_variant_id) ===
                            String(item.product_variant_id) &&
                        String(row.unit_id) === String(item.unit_id)
                    );
                });
                item.stock_invalid = !!shortage;
                item.available_qty = shortage ? parseFloat(shortage.available) || 0 : null;
                item.stock_error = shortage
                    ? "Stok tidak cukup. Tersedia: " +
                      formatTransferQty(shortage.available) +
                      " " +
                      transferUnitLabel(item)
                    : null;
                if (shortage && showToast && typeof toastr !== "undefined") {
                    toastr.error("", item.stock_error);
                }
            },
            error: function (xhr, textStatus) {
                if (isAjaxAbort(xhr, textStatus) || !isCurrent()) return;
                item.stock_invalid = true;
                item.stock_error =
                    (xhr.responseJSON && xhr.responseJSON.message) ||
                    "Stok gagal divalidasi. Coba lagi.";
            },
            complete: function (xhr, textStatus) {
                if (item._check_stock_xhr === xhr) {
                    item._check_stock_xhr = null;
                }
                if (isAjaxAbort(xhr, textStatus)) return;
                finish();
            },
        });
    }

    function validateRetailUnit() {
        if (!isCurrent()) {
            finish();
            return;
        }
        if (selectedTransferWarehouseIsMain("#transfer_to_warehouse_id") !== false) {
            validateMatrix();
            return;
        }
        if (parseInt(item.retail_unit, 10) > 0) {
            validateMatrix();
            return;
        }
        if (item.retail_invalid && (item.retail_unit_options || []).length) {
            finish();
            return;
        }
        prepareRetailUnitForTransfer(item, !!promptRetailSetup, function () {
            if (item.retail_invalid) finish();
            else validateMatrix();
        });
    }

    if (!fromId || !toId || String(fromId) === String(toId)) {
        fail("Rute gudang asal dan tujuan tidak valid");
        return;
    }
    if (
        promptRetailSetup &&
        selectedTransferWarehouseIsMain("#transfer_to_warehouse_id") === false &&
        !parseInt(item.retail_unit, 10)
    ) {
        prepareRetailUnitForTransfer(item, true);
    }

    fetchSourceStock(
        item.product_variant_id,
        function (stock) {
            if (!isCurrent()) {
                finish();
                return;
            }

            stock = stock || { units: [] };
            var units = Array.isArray(stock.units) ? stock.units.slice() : [];
            if (stock.warehouse_is_main === false && stock.retail_unit_id) {
                units = units.filter(function (unit) {
                    return String(unit.unit_id) === String(stock.retail_unit_id);
                });
            }
            var selectedUnit = units.find(function (unit) {
                return String(unit.unit_id) === String(item.unit_id);
            });
            var currentUnit = {
                unit_id: item.unit_id,
                unit_name: item.unit_name,
                unit_short_name: item.unit_short_name,
            };

            item.stock_text = stock.stock_text || "0";
            item.units = units.slice();
            if (
                !item.units.some(function (unit) {
                    return String(unit.unit_id) === String(currentUnit.unit_id);
                })
            ) {
                item.units.push(currentUnit);
            }
            item.pr_unit = item.units;
            item.stock_invalid = !selectedUnit;
            item.stock_error = !selectedUnit
                ? stock.message ||
                  "Unit terpilih tidak tersedia, relasi konversinya invalid, atau tidak dapat dipacking"
                : null;
            if (selectedUnit) {
                item.unit_name = selectedUnit.unit_name || selectedUnit.unit_short_name;
                item.unit_short_name = selectedUnit.unit_short_name || selectedUnit.unit_name;
            }
            validateRetailUnit();
        },
        item
    );
}

function revalidateAllTransferRows(showToast) {
    transferItems.slice().forEach(function (item) {
        validateOptimisticTransferRow(item, showToast);
    });
}

function scheduleTransferRowValidation(item) {
    if (!item) return;
    clearTimeout(item._validation_timer);
    abortTransferRowXhrs(item);
    item._validation_run = (item._validation_run || 0) + 1;
    item.stock_loading = true;
    item.stock_invalid = false;
    item.stock_error = null;
    var scheduledRun = item._validation_run;
    patchTransferItemsRow(item);
    item._validation_timer = setTimeout(function () {
        if (
            transferItems.indexOf(item) !== -1 &&
            item._validation_run === scheduledRun
        ) {
            validateOptimisticTransferRow(item, false);
        }
    }, 350);
    syncTransferSaveButton();
}

function patchTransferItemStock(variantId, stock) {
    var changed = false;
    var invalid = false;
    transferItems.forEach(function (item) {
        if (parseInt(item.product_variant_id, 10) !== parseInt(variantId, 10)) return;
        item.stock_loading = false;
        item.stock_text = stock.stock_text || "0";
        if (stock.units && stock.units.length) {
            item.units = stock.units;
            var stillOk = stock.units.some(function (u) {
                return String(u.unit_id) === String(item.unit_id);
            });
            if (!stillOk) {
                invalid = true;
                item.stock_invalid = true;
                item.stock_error =
                    stock.message ||
                    "Satuan terpilih tidak valid untuk rute ini; pilih satuan yang valid";
            }
        } else {
            invalid = true;
            item.stock_invalid = true;
            item.stock_error =
                stock.message || "Produk tidak memiliki satuan yang valid untuk gudang asal";
        }
        changed = true;
    });
    if (invalid) {
        if (typeof toastr !== "undefined") {
            toastr.error("", stock.message || "Ada satuan yang tidak valid untuk rute gudang");
        }
    }
    if (changed) refreshTransferItemsTable();
}

function reloadTransferRowStocks(done) {
    var variantIds = [];
    transferItems.forEach(function (item) {
        var id = parseInt(item.product_variant_id, 10);
        if (id && variantIds.indexOf(id) === -1) variantIds.push(id);
    });
    if (!variantIds.length) {
        if (typeof done === "function") done();
        return;
    }

    var pending = variantIds.length;
    variantIds.forEach(function (variantId) {
        fetchSourceStock(variantId, function (stock) {
            patchTransferItemStock(variantId, stock || { units: [] });
            pending--;
            if (pending === 0 && typeof done === "function") done();
        });
    });
}

function validateRetailUnitsForTransferRows() {
    var runId = ++retailValidationRun;
    var fromId = $("#transfer_from_warehouse_id").val();
    var toId = $("#transfer_to_warehouse_id").val();

    transferItems.forEach(function (item) {
        item.retail_invalid = false;
        item.retail_unit_options = [];
        item.retail_error = null;
    });
    refreshTransferItemsTable();
    retailUnitValidationPending = 0;

    if (!fromId || !toId || !transferItems.length) {
        refreshTransferItemsTable();
        syncTransferSaveButton();
        return;
    }

    var missingItems = transferItems.filter(function (item) {
        return !parseInt(item.retail_unit, 10);
    });
    if (!missingItems.length) {
        revalidateAllTransferRows(false);
        return;
    }

    retailUnitValidationPending = missingItems.length;
    syncTransferSaveButton();

    function finishRow() {
        if (runId !== retailValidationRun) return;
        retailUnitValidationPending = Math.max(0, retailUnitValidationPending - 1);
        if (retailUnitValidationPending > 0) return;

        refreshTransferItemsTable();
        syncTransferSaveButton();
        revalidateAllTransferRows(false);
        if (hasMissingRetailUnitRows()) {
            if (typeof toastr !== "undefined") {
                toastr.warning(
                    "",
                    "Ada produk yang belum memiliki satuan eceran. Lengkapi pada row merah sebelum menyimpan transfer."
                );
            }
            return;
        }
    }

    missingItems.forEach(function (item) {
        prepareRetailUnitForTransfer(item, false, finishRow);
    });
}

function validateCurrentTransferMatrix(done, showToast) {
    var fromId = $("#transfer_from_warehouse_id").val();
    var toId = $("#transfer_to_warehouse_id").val();
    if (
        !fromId ||
        !toId ||
        !transferItems.length ||
        retailUnitValidationPending > 0 ||
        hasMissingRetailUnitRows()
    ) {
        if (typeof done === "function") done(false);
        return;
    }

    var runId = ++stockValidationRun;
    var requestItems = transferItems.map(function (item) {
        return {
            item: item,
            signature:
                String(item.product_variant_id) +
                "|" +
                String(item.unit_id) +
                "|" +
                String(item.qty),
        };
    });
    stockValidationPending = 1;
    syncTransferSaveButton();
    $.ajax({
        url: "/checkTransferStock",
        method: "post",
        data: {
            from_warehouse_id: fromId,
            to_warehouse_id: toId,
            items: requestItems.map(function (entry) {
                var it = entry.item;
                return {
                    product_variant_id: it.product_variant_id,
                    unit_id: it.unit_id,
                    qty: it.qty,
                    label: (it.product_name || "") + " " + (it.product_variant_name || ""),
                };
            }),
            _token: token || $('meta[name="csrf-token"]').attr("content"),
        },
        success: function (res) {
            if (
                runId !== stockValidationRun ||
                String($("#transfer_from_warehouse_id").val() || "") !== String(fromId) ||
                String($("#transfer_to_warehouse_id").val() || "") !== String(toId)
            ) {
                return;
            }
            var hasStaleRow = requestItems.some(function (entry) {
                var item = entry.item;
                var currentSignature =
                    String(item.product_variant_id) +
                    "|" +
                    String(item.unit_id) +
                    "|" +
                    String(item.qty);
                return (
                    transferItems.indexOf(item) === -1 ||
                    currentSignature !== entry.signature
                );
            });
            if (hasStaleRow) {
                if (typeof done === "function") done(false);
                return;
            }
            if (!res) {
                requestItems.forEach(function (entry) {
                    var item = entry.item;
                    item.stock_invalid = true;
                    item.stock_error = "Stok gagal divalidasi. Coba lagi.";
                });
                refreshTransferItemsTable();
                if (typeof done === "function") done(false);
                return;
            }
            if (res.matrix_error) {
                var invalidIds = (res.invalid_variant_ids || []).map(String);
                requestItems.forEach(function (entry) {
                    var item = entry.item;
                    if (
                        !invalidIds.length ||
                        invalidIds.indexOf(String(item.product_variant_id)) !== -1
                    ) {
                        item.stock_invalid = true;
                        item.stock_error =
                            res.message ||
                            "Satuan terpilih tidak valid untuk rute gudang ini";
                    }
                });
                refreshTransferItemsTable();
                if (typeof toastr !== "undefined") {
                    toastr.error("", res.message || "Item tidak cocok dengan rute gudang");
                }
                if (typeof done === "function") done(false, res);
                return;
            }

            var shortages = Array.isArray(res.shortages) ? res.shortages : [];
            requestItems.forEach(function (entry) {
                var item = entry.item;
                var shortage = shortages.find(function (row) {
                    return (
                        String(row.product_variant_id) === String(item.product_variant_id) &&
                        String(row.unit_id) === String(item.unit_id)
                    );
                });
                item.stock_invalid = !!shortage;
                item.available_qty = shortage ? parseFloat(shortage.available) || 0 : null;
                item.stock_error = shortage
                    ? "Stok tidak cukup. Tersedia: " +
                      formatTransferQty(shortage.available) +
                      " " +
                      transferUnitLabel(item)
                    : null;
            });
            refreshTransferItemsTable();
            if (shortages.length && showToast !== false && typeof toastr !== "undefined") {
                toastr.error("", res.message || "Stok tidak mencukupi");
            }
            if (typeof done === "function") done(shortages.length === 0, res);
        },
        error: function () {
            if (runId !== stockValidationRun) return;
            requestItems.forEach(function (entry) {
                var item = entry.item;
                if (transferItems.indexOf(item) === -1) return;
                item.stock_invalid = true;
                item.available_qty = null;
                item.stock_error = "Stok gagal divalidasi. Coba lagi.";
            });
            refreshTransferItemsTable();
            if (typeof toastr !== "undefined") {
                toastr.error("", "Gagal memvalidasi stok transfer");
            }
            if (typeof done === "function") done(false);
        },
        complete: function () {
            if (runId !== stockValidationRun) return;
            stockValidationPending = 0;
            syncTransferSaveButton();
        },
    });
}

function addTransferProduct(raw, qty, requireDefaultUnit) {
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
    $("#transfer_qty_input").val(parseInt(qty, 10) > 0 ? parseInt(qty, 10) : 1);
    // Load stok + daftar satuan (utama = multi; eceran = satuan eceran saja)
    loadTransferDraftStock(raw);
}

function commitOptimisticTransferProduct(raw, qty, selectedUnit) {
    var variantId = parseInt(raw.product_variant_id || raw.id, 10);
    if (!selectedUnit) return false;
    var selectedUnitId = selectedUnit.unit_id;
    var unitName = selectedUnit.unit_name || selectedUnit.unit_short_name || "";
    var unitShortName = selectedUnit.unit_short_name || selectedUnit.unit_name || "";

    // Duplikat = variant + satuan sama â†’ tambah qty
    var existing = -1;
    transferItems.forEach(function (el, idx) {
        if (
            parseInt(el.product_variant_id, 10) === variantId &&
            String(el.unit_id || "") === String(selectedUnitId || "")
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
            units: [selectedUnit],
            pr_unit: [selectedUnit],
            unit_id: selectedUnitId,
            unit_name: unitName,
            unit_short_name: unitShortName,
            retail_unit: raw.retail_unit || null,
            stock_invalid: false,
            stock_error: null,
            stock_loading: true,
        });
        existing = transferItems.length - 1;
    } else {
        transferItems[existing].qty =
            (parseInt(transferItems[existing].qty, 10) || 0) + qty;
        transferItems[existing].stock_loading = true;
        transferItems[existing].stock_invalid = false;
        transferItems[existing].stock_error = null;
    }

    refreshTransferItemsTable();
    syncTransferSaveButton();
    return transferItems[existing];
}

function addTransferDraft() {
    var raw = transferDraft.raw;
    var qtyRaw = Number($("#transfer_qty_input").val());
    var fromId = $("#transfer_from_warehouse_id").val();
    var toId = $("#transfer_to_warehouse_id").val();

    $("#transfer_qty_input, #transfer_unit_input").removeClass("is-invalid");
    if (!raw || !parseInt(raw.product_variant_id || raw.id, 10)) {
        if (typeof toastr !== "undefined") toastr.error("", "Pilih produk terlebih dahulu");
        return;
    }
    if (!fromId || !toId || !validateWarehousesDifferent()) {
        if (typeof toastr !== "undefined") toastr.error("", "Pilih gudang asal dan tujuan yang valid");
        return;
    }
    if (!Number.isInteger(qtyRaw) || qtyRaw <= 0) {
        $("#transfer_qty_input").addClass("is-invalid");
        if (typeof toastr !== "undefined") toastr.error("", "Qty harus berupa bilangan bulat positif");
        return;
    }
    if (transferDraft.loading && !draftUnitById($("#transfer_unit_input").val()) && !transferDefaultUnit(raw)) {
        if (typeof toastr !== "undefined") toastr.info("", "Sedang memuat satuan/stok produk...");
        return;
    }
    if (
        transferDraft.defaultUnitInvalid &&
        isRetailUnitSetupRequired(transferDraft.stock, raw)
    ) {
        prepareRetailUnitForTransfer(raw, true);
        return;
    }
    var selectedUnit =
        draftUnitById($("#transfer_unit_input").val()) ||
        (function () {
            var packed = transferUnitsFromRaw(raw);
            var id = $("#transfer_unit_input").val();
            return (
                packed.units.find(function (u) {
                    return String(u.unit_id) === String(id);
                }) || packed.defaultUnit
            );
        })();
    if (!selectedUnit || !$("#transfer_unit_input").val()) {
        $("#transfer_unit_input").addClass("is-invalid");
        if (typeof toastr !== "undefined") {
            toastr.error(
                "",
                transferDraft.defaultUnitInvalid
                    ? $("#transfer_stock_available").text() || "Satuan tidak tersedia"
                    : "Pilih satuan terlebih dahulu"
            );
        }
        return;
    }

    var item = commitOptimisticTransferProduct(raw, qtyRaw, selectedUnit);
    if (!item) {
        if (typeof toastr !== "undefined") toastr.error("", "Produk atau satuan tidak valid");
        return;
    }
    // Isi opsi multi-satuan dari stok yang sudah diload (jika ada)
    if (transferDraft.stock && Array.isArray(transferDraft.stock.units)) {
        var units = transferDraft.stock.units.slice();
        if (
            transferDraft.stock.warehouse_is_main === false &&
            transferDraft.stock.retail_unit_id
        ) {
            units = units.filter(function (unit) {
                return String(unit.unit_id) === String(transferDraft.stock.retail_unit_id);
            });
        }
        if (units.length) {
            item.units = units;
            item.pr_unit = units;
        }
    }

    resetTransferDraft();
    validateOptimisticTransferRow(item, true, true);
}

function setDefaultSender() {
    var staff = window.currentStaff || {};
    if (!staff.id || !staff.name) return;
    var $el = $("#transfer_sender_id");
    if (!$el.length) return;
    $el.empty();
    var opt = new Option(staff.name, staff.id, true, true);
    $el.append(opt).trigger("change");
    $el.prop("disabled", true);
}

function setDefaultFromWarehouse() {
    // Mode request eceran: asal = gudang request (user pilih), jangan auto-isi aktif
    if (transferCreateRequestMode) {
        unlockTransferFromWarehouse();
        return;
    }
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
    lockTransferFromWarehouse();
}

/** Gudang asal selalu = gudang aktif / data transfer — tidak bisa diganti di form. */
function lockTransferFromWarehouse() {
    var $el = $("#transfer_from_warehouse_id");
    if (!$el.length) return;
    $el.prop("disabled", true);
    if ($el.hasClass("select2-hidden-accessible")) {
        $el.trigger("change.select2");
    }
}

function resetTransferForm() {
    transferItems = [];
    transferItemsSnapshot = [];
    transferHeaderSnapshot = null;
    transferFormLocked = false;
    transferCanEdit = false;
    transferCanShip = false;
    transferCanReject = false;
    transferCanApproveQc = false;
    transferCanApproveOps = false;
    transferRequiresApproval = false;
    transferQcRequired = false;
    transferOpsRequired = false;
    transferQcApproved = false;
    transferOpsApproved = false;
    transferCreateRequestMode = false;
    transferIsRetailRequest = false;
    transferScanMode = false;
    transferThenShipProofBase64 = "";
    transferPendingThenShip = false;
    $("#st-ship-proof-slot").addClass("d-none");
    $("#st-ship-proof-thumb").attr("src", "");
    clearTransferStockLoads();
    retailUnitValidationPending = 0;
    retailValidationRun++;
    stockValidationPending = 0;
    stockValidationRun++;
    transferRouteRun++;
    transferScanLookupRun++;
    resetTransferDraft(false);
    syncTransferSaveButton();
    $("#add_stock_transfer input:not([type=checkbox]), #add_stock_transfer textarea").val("");
    $("#transfer_qty_input").val(1);
    $("#transfer_mode_scan").hide();
    $("#transfer_mode_select").show();
    $(".transfer-draft-only").show();
    $("#transfer_scan_qty").val(1);
    $("#btn_toggle_scan_transfer")
        .html('<i class="fa fa-barcode"></i> Scan')
        .removeClass("btn-outline-primary")
        .addClass("btn-outline-secondary");
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
    $("#transfer_sender_id, #transfer_from_warehouse_id, #transfer_to_warehouse_id")
        .val(null)
        .trigger("change");
    resetTransferSkuSelect();
    refreshTransferItemsTable();
    $(".is-invalid").removeClass("is-invalid");
    $(".is-invalids").removeClass("is-invalids");
    syncTransferModalChrome();
}

function snapshotTransferForm() {
    transferItemsSnapshot = JSON.parse(JSON.stringify(transferItems || []));
    transferHeaderSnapshot = {
        transfer_date: $("#transfer_date").val(),
        note: $("#transfer_note").val(),
        from_warehouse_id: $("#transfer_from_warehouse_id").val(),
        from_warehouse_text: $("#transfer_from_warehouse_id").find("option:selected").text(),
        to_warehouse_id: $("#transfer_to_warehouse_id").val(),
        to_warehouse_text: $("#transfer_to_warehouse_id").find("option:selected").text(),
    };
}

function restoreTransferFormSnapshot() {
    if (!transferHeaderSnapshot) return;
    $("#transfer_date").val(transferHeaderSnapshot.transfer_date || "");
    if ($("#transfer_date").data("DateTimePicker") && transferHeaderSnapshot.transfer_date) {
        $("#transfer_date").data("DateTimePicker").date(transferHeaderSnapshot.transfer_date);
    }
    $("#transfer_note").val(transferHeaderSnapshot.note || "");
    fillSelectOption(
        $("#transfer_from_warehouse_id"),
        transferHeaderSnapshot.from_warehouse_id,
        transferHeaderSnapshot.from_warehouse_text
    );
    fillSelectOption(
        $("#transfer_to_warehouse_id"),
        transferHeaderSnapshot.to_warehouse_id,
        transferHeaderSnapshot.to_warehouse_text
    );
    transferItems = JSON.parse(JSON.stringify(transferItemsSnapshot || []));
    refreshTransferItemsTable();
}

function setTransferFormLocked(locked) {
    transferFormLocked = !!locked;
    var $modal = $("#add_stock_transfer");
    var selectors = [
        "#transfer_to_warehouse_id",
        "#transfer_date",
        "#transfer_note",
        "#transfer_sku",
        "#transfer_qty_input",
        "#transfer_unit_input",
        "#transfer_scan_barcode",
        "#transfer_scan_qty",
        "#btn_add_transfer_product",
        "#btn_toggle_scan_transfer",
        "#btn_scan_add_transfer",
    ];
    selectors.forEach(function (sel) {
        var $el = $modal.find(sel);
        $el.prop("disabled", transferFormLocked);
        if ($el.hasClass("select2-hidden-accessible")) {
            $el.trigger("change.select2");
        }
    });
    // pengirim selalu terkunci
    $("#transfer_sender_id").prop("disabled", true);
    if (transferFormLocked) {
        lockTransferFromWarehouse();
        lockTransferToWarehouse();
    } else if (transferIsRetailRequest) {
        // Request eceran: Gudang Request (from) boleh diubah; penerima (to) terkunci
        unlockTransferFromWarehouse();
        lockTransferToWarehouse();
    } else {
        lockTransferFromWarehouse();
        unlockTransferToWarehouse();
    }
    if (transferFormLocked) {
        $(".transfer-product-panel").addClass("d-none");
    } else {
        $(".transfer-product-panel").removeClass("d-none").css("opacity", "1");
    }
    refreshTransferItemsTable();
    syncTransferModalChrome();
}

function setTransferModalMode(kind) {
    var $modal = $("#add_stock_transfer");
    var $icon = $modal.find(".pg-modal-icon i").first();
    $modal.removeClass("pg-modal--form pg-modal--confirm");
    if (kind === "confirm") {
        $modal.addClass("pg-modal--confirm");
        $icon.attr("class", "fe fe-check-circle");
    } else {
        $modal.addClass("pg-modal--form");
        $icon.attr("class", "fe fe-shuffle");
    }
}

function syncTransferModalChrome() {
    var isCreate = mode === 1;
    var isEditing = mode === 2 && !transferFormLocked;
    var isViewing = mode === 2 && transferFormLocked;
    var $title = $("#add_stock_transfer .modal-title");
    var $sub = $("#add_stock_transfer .transfer-modal-subtitle");
    var $editBtn = $("#add_stock_transfer .btn-enable-edit-transfer");
    var $save = $("#add_stock_transfer .btn-save-transfer");
    var $reject = $("#add_stock_transfer .btn-reject-transfer");
    var $transfer = $("#add_stock_transfer .btn-acc-transfer");
    var $approveQc = $("#add_stock_transfer .btn-approve-qc-transfer");
    var $approveOps = $("#add_stock_transfer .btn-approve-ops-transfer");

    function hideApprovals() {
        $approveQc.addClass("d-none").removeClass("d-inline-flex");
        $approveOps.addClass("d-none").removeClass("d-inline-flex");
    }

    function approvalSubtitle() {
        if (!transferRequiresApproval && !transferQcRequired && !transferOpsRequired) return "";
        var parts = [];
        if (transferQcRequired) {
            parts.push(transferQcApproved ? "QC ✓" : "QC …");
        }
        if (transferOpsRequired) {
            parts.push(transferOpsApproved ? "Kepala Ops ✓" : "Kepala Ops …");
        }
        if (!parts.length) return "";
        return " Approval berurut: " + parts.join(" → ") + ".";
    }

    if (isCreate) {
        setTransferModalMode("form");
        $title.text("Buat Stock Transfer");
        $sub.text("Pindahkan stok antar gudang / toko");
        $editBtn.addClass("d-none").removeClass("d-inline-flex");
        $save.removeClass("d-none").addClass("d-inline-flex");
        $reject.addClass("d-none").removeClass("d-inline-flex");
        $transfer.addClass("d-none").removeClass("d-inline-flex");
        hideApprovals();
    } else if (isViewing) {
        // Ada aksi ACC (Kirim / Tolak / Approve) → chrome hijau confirm; view-only → biru form
        var hasAccAction =
            transferCanShip ||
            transferCanReject ||
            transferCanApproveQc ||
            transferCanApproveOps;
        setTransferModalMode(hasAccAction ? "confirm" : "form");
        $title.text("Detail Stock Transfer");
        $sub.text(
            (transferCanEdit
                ? "Default terkunci. Klik Edit Data untuk ubah, lalu Simpan / Kirim."
                : "Lihat data. Kirim atau tolak jika punya akses.") + approvalSubtitle()
        );
        if (transferCanEdit) {
            $editBtn.removeClass("d-none").addClass("d-inline-flex");
        } else {
            $editBtn.addClass("d-none").removeClass("d-inline-flex");
        }
        $save.addClass("d-none").removeClass("d-inline-flex");
        if (transferCanReject) {
            $reject.removeClass("d-none").addClass("d-inline-flex");
            var sourceType = $("#add_stock_transfer").attr("data-source-type") || "";
            var isRetailReq = sourceType === "retail_request";
            var activeWh =
                typeof getActiveWarehouseId === "function"
                    ? String(getActiveWarehouseId() || "")
                    : "";
            var fromWh = String($("#transfer_from_warehouse_id").val() || "");
            // Gudang besar (asal request): Tolak. Eceran pemohon: Cancel.
            if (isRetailReq && activeWh && fromWh && activeWh === fromWh) {
                $reject.html('<i class="fe fe-x me-1"></i>Tolak');
            } else if (isRetailReq) {
                $reject.html('<i class="fe fe-x me-1"></i>Cancel');
            } else {
                $reject.html('<i class="fe fe-x me-1"></i>Tolak');
            }
        } else {
            $reject.addClass("d-none").removeClass("d-inline-flex");
        }
        if (transferCanShip) {
            $transfer.removeClass("d-none").addClass("d-inline-flex");
        } else {
            $transfer.addClass("d-none").removeClass("d-inline-flex");
        }
        if (transferCanApproveQc) {
            $approveQc
                .removeClass("d-none")
                .addClass("d-inline-flex")
                .html('<i class="fe fe-check me-1"></i>Setujui QC');
        } else {
            $approveQc.addClass("d-none").removeClass("d-inline-flex");
        }
        if (transferCanApproveOps) {
            $approveOps
                .removeClass("d-none")
                .addClass("d-inline-flex")
                .html('<i class="fe fe-check-circle me-1"></i>Setujui Ops');
        } else {
            $approveOps.addClass("d-none").removeClass("d-inline-flex");
        }
    } else if (isEditing) {
        setTransferModalMode("form");
        $title.text("Edit Stock Transfer");
        $sub.text("Ubah data lalu simpan. Batal = batalkan edit.");
        $editBtn.addClass("d-none").removeClass("d-inline-flex");
        $save.removeClass("d-none").addClass("d-inline-flex");
        $reject.addClass("d-none").removeClass("d-inline-flex");
        $transfer.addClass("d-none").removeClass("d-inline-flex");
        hideApprovals();
    }
}

function syncTransferEditActions(canShip, canReject, canEdit, opts) {
    opts = opts || {};
    transferCanShip = !!canShip;
    transferCanReject = !!canReject;
    transferCanEdit = !!canEdit;
    transferCanApproveQc = !!opts.canApproveQc;
    transferCanApproveOps = !!opts.canApproveOps;
    transferRequiresApproval = !!opts.requiresApproval;
    transferQcRequired = !!opts.qcRequired;
    transferOpsRequired = !!opts.opsRequired;
    transferQcApproved = !!opts.qcApproved;
    transferOpsApproved = !!opts.opsApproved;
    syncTransferModalChrome();
}

$(document).on("show.bs.modal", "#add_stock_transfer", function () {
    $("#collapseStockTransferForm").collapse("show");
    var $icon = $('[data-bs-target="#collapseStockTransferForm"] i');
    $icon.removeClass('fe-chevron-down').addClass('fe-chevron-up');
});

$(document).on("click", ".btnAdd", function () {
    if (typeof isActiveMainWarehouse === "function" && isActiveMainWarehouse() === true) {
        if (typeof toastr !== "undefined") {
            toastr.warning("", "Tambah Stock Transfer dari gudang utama sementara dinonaktifkan");
        }
        return;
    }
    mode = 1;
    if (!$("#add_stock_transfer").length) return;
    $("#add_stock_transfer").removeAttr("data-id");
    $("#add_stock_transfer").removeAttr("data-source-type");
    setTransferModalLoading(false);
    resetTransferForm();
    initTransferAutocompletes();
    setDefaultSender();
    setDefaultFromWarehouse();
    if (transferCreateRequestMode) {
        setDefaultReceivingWarehouse();
        $("#add_stock_transfer .modal-title").text("Request Stock Transfer");
        $("#add_stock_transfer .transfer-modal-subtitle").text(
            "Request stok dari gudang lain ke gudang aktif"
        );
    }
    setTransferFormLocked(false);
    $("#add_stock_transfer").modal("show");
});

$(document).on("hidden.bs.modal", "#add_stock_transfer", function () {
    transferDetailLoadSeq++;
    setTransferModalLoading(false);
});

$(document).on("change", "#transfer_from_warehouse_id, #transfer_to_warehouse_id", function () {
    validateWarehousesDifferent();
});

$(document).on("change", "#transfer_from_warehouse_id", function () {
    var hadItems = transferItems.length > 0;
    transferItems = [];
    retailUnitValidationPending = 0;
    retailValidationRun++;
    stockValidationPending = 0;
    stockValidationRun++;
    transferRouteRun++;
    transferScanLookupRun++;
    clearTransferStockLoads();
    resetTransferDraft();
    refreshTransferItemsTable();
    syncTransferSaveButton();
    enableTransferProductSelect();
    if (hadItems && typeof toastr !== "undefined") {
        toastr.info("", "Daftar produk dikosongkan karena gudang asal berubah");
    }
});

$(document).on("change", "#transfer_to_warehouse_id", function () {
    transferRouteRun++;
    transferScanLookupRun++;
    clearTransferStockLoads();
    if (transferDraft.raw) {
        loadTransferDraftStock(transferDraft.raw);
    } else {
        resetTransferDraft(false);
    }
    if (selectedTransferWarehouseIsMain("#transfer_to_warehouse_id") === false) {
        validateRetailUnitsForTransferRows();
    } else {
        retailValidationRun++;
        retailUnitValidationPending = 0;
        transferItems.forEach(function (item) {
            item.retail_invalid = false;
            item.retail_unit_options = [];
            item.retail_error = null;
        });
        revalidateAllTransferRows(false);
    }
});

$(document).on("change", "#transfer_sku", function () {
    if (transferDraftSelectGuard) return;
    transferScanLookupRun++;
    var data = $(this).select2("data")[0];
    if (!data || !data.id) {
        resetTransferDraft(false);
        return;
    }
    addTransferProduct(data, $("#transfer_qty_input").val() || 1);
});

$(document).on("change", "#transfer_unit_input", updateTransferDraftAvailable);
$(document).on("input", "#transfer_qty_input", function () {
    $(this).removeClass("is-invalid");
});
$(document).on("click", "#btn_add_transfer_product", addTransferDraft);
$(document).on("keydown", "#transfer_qty_input, #transfer_unit_input", function (e) {
    if (e.key === "Enter" || e.keyCode === 13) {
        e.preventDefault();
        addTransferDraft();
    }
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
        $(".transfer-draft-only").hide();
        $(this)
            .html('<i class="fa fa-list"></i> Input')
            .removeClass("btn-outline-secondary")
            .addClass("btn-outline-primary");
        $("#transfer_scan_barcode").focus();
    } else {
        $("#transfer_mode_scan").hide();
        $("#transfer_mode_select").show();
        $(".transfer-draft-only").show();
        $(this)
            .html('<i class="fa fa-barcode"></i> Scan')
            .removeClass("btn-outline-primary")
            .addClass("btn-outline-secondary");
    }
});

function addScannedTransferRow(raw, qty, routeRun, fromId, toId) {
    var variantId = parseInt(raw.product_variant_id || raw.id, 10);
    var defaultUnit = transferDefaultUnit(raw);
    if (!variantId || !defaultUnit) {
        if (typeof toastr !== "undefined") {
            toastr.error("", "Produk tidak memiliki unit default yang valid");
        }
        return;
    }

    if (
        routeRun !== transferRouteRun ||
        String($("#transfer_from_warehouse_id").val() || "") !== String(fromId) ||
        String($("#transfer_to_warehouse_id").val() || "") !== String(toId)
    ) {
        return;
    }
    var item = commitOptimisticTransferProduct(raw, qty, defaultUnit);
    if (item) {
        validateOptimisticTransferRow(item, true, true);
    }
}

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

    var fromId = $("#transfer_from_warehouse_id").val();
    var toId = $("#transfer_to_warehouse_id").val();
    if (!toId || !validateWarehousesDifferent()) {
        if (typeof toastr !== "undefined") toastr.warning("", "Pilih gudang tujuan yang valid");
        return;
    }
    var routeRun = transferRouteRun;
    var lookupRun = ++transferScanLookupRun;
    $.ajax({
        url: "/searchProductVariantByScan",
        method: "post",
        data: {
            keyword: barcode,
            _token: token || $('meta[name="csrf-token"]').attr("content"),
        },
        success: function (res) {
            if (
                lookupRun !== transferScanLookupRun ||
                routeRun !== transferRouteRun ||
                String($("#transfer_from_warehouse_id").val() || "") !== String(fromId) ||
                String($("#transfer_to_warehouse_id").val() || "") !== String(toId)
            ) {
                return;
            }
            var results = res.data || [];
            if (!results.length) {
                if (typeof toastr !== "undefined") {
                    toastr.error("", "Produk tidak ditemukan untuk barcode: " + barcode);
                }
                $("#transfer_scan_barcode").val("").focus();
                return;
            }
            var raw = results[0];
            addScannedTransferRow(raw, qty, routeRun, fromId, toId);
            $("#transfer_scan_barcode").val("").focus();
        },
        error: function () {
            if (
                lookupRun !== transferScanLookupRun ||
                routeRun !== transferRouteRun ||
                String($("#transfer_from_warehouse_id").val() || "") !== String(fromId) ||
                String($("#transfer_to_warehouse_id").val() || "") !== String(toId)
            ) {
                return;
            }
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
    var val = Number($(this).val());
    if (!Number.isInteger(val) || val <= 0) {
        $(this).addClass("is-invalid");
        if (transferItems[idx]) {
            clearTimeout(transferItems[idx]._validation_timer);
            abortTransferRowXhrs(transferItems[idx]);
            transferItems[idx]._validation_run =
                (transferItems[idx]._validation_run || 0) + 1;
            transferItems[idx].qty = val;
            transferItems[idx].stock_loading = false;
            transferItems[idx].stock_invalid = true;
            transferItems[idx].stock_error = "Qty harus berupa bilangan bulat positif";
            patchTransferItemsRow(transferItems[idx]);
            syncTransferSaveButton();
        }
        return;
    }
    $(this).removeClass("is-invalid");
    if (transferItems[idx]) {
        transferItems[idx].qty = val;
        scheduleTransferRowValidation(transferItems[idx]);
    }
});

$(document).on("change", ".transfer-qty", function () {
    if ($(this).hasClass("is-invalid")) return;
    var idx = parseInt($(this).attr("data-index"), 10);
    if (transferItems[idx]) {
        clearTimeout(transferItems[idx]._validation_timer);
        transferItems[idx]._validation_timer = null;
        validateOptimisticTransferRow(transferItems[idx], true);
    }
});

$(document).on("change", ".transfer-unit", function () {
    var idx = parseInt($(this).attr("data-index"), 10);
    if (!transferItems[idx]) return;
    var unitId = $(this).val();
    transferItems[idx].unit_id = unitId;
    var selectedUnit = (transferItems[idx].units || []).find(function (unit) {
        return String(unit.unit_id) === String(unitId);
    });
    transferItems[idx].unit_name = selectedUnit
        ? selectedUnit.unit_name || selectedUnit.unit_short_name
        : "";
    transferItems[idx].unit_short_name = selectedUnit
        ? selectedUnit.unit_short_name || selectedUnit.unit_name
        : "";
    transferItems[idx].available_qty = selectedUnit
        ? parseFloat(selectedUnit.available_qty) || 0
        : 0;
    clearTimeout(transferItems[idx]._validation_timer);
    transferItems[idx]._validation_timer = null;
    validateOptimisticTransferRow(transferItems[idx], true);
});

$(document).on("change", ".transfer-retail-unit", function () {
    var $select = $(this);
    var idx = parseInt($select.attr("data-index"), 10);
    var item = transferItems[idx];
    var unitId = $select.val();
    if (!item || !unitId) return;
    var fromId = $("#transfer_from_warehouse_id").val();
    var toId = $("#transfer_to_warehouse_id").val();
    var routeRun = transferRouteRun;

    function isCurrentRetailSetup() {
        return (
            transferItems.indexOf(item) !== -1 &&
            routeRun === transferRouteRun &&
            String($("#transfer_from_warehouse_id").val() || "") === String(fromId || "") &&
            String($("#transfer_to_warehouse_id").val() || "") === String(toId || "")
        );
    }

    var unitName = $select.find("option:selected").text().trim();
    Swal.fire({
        icon: "question",
        title: "Simpan satuan eceran?",
        html:
            "Pastikan <strong>" +
            escapeHtml(unitName) +
            "</strong> benar sebagai satuan eceran untuk <strong>" +
            escapeHtml(
                (item.product_name || "") +
                    " " +
                    (item.product_variant_name || "")
            ) +
            "</strong>.<br><small class=\"text-muted\">Satuan ini akan digunakan sebagai acuan konversi produk.</small>",
        showCancelButton: true,
        confirmButtonText: "Ya, Simpan",
        cancelButtonText: "Batal",
        reverseButtons: true,
        allowOutsideClick: false,
    }).then(function (result) {
        if (!result.isConfirmed) {
            $select.val("");
            return;
        }

        if (!isCurrentRetailSetup()) return;
        $select.prop("disabled", true);
        var context = transferRetailContext(item);
        saveRetailUnitForTransfer(item, unitId, context).catch(function (xhr) {
            if (!isCurrentRetailSetup()) return;
            item.retail_invalid = true;
            item.retail_error =
                (xhr.responseJSON && xhr.responseJSON.message) ||
                xhr.message ||
                "Gagal menyimpan satuan eceran";
            refreshTransferItemsTable();
            syncTransferSaveButton();
            if (typeof toastr !== "undefined") toastr.error("", item.retail_error);
        });
    });
});

$(document).on("click", ".btn-remove-transfer-item", function () {
    var idx = parseInt($(this).attr("data-index"), 10);
    if (isNaN(idx)) return;
    transferItems.splice(idx, 1);
    refreshTransferItemsTable();
    syncTransferSaveButton();
});

function fillViewTransferApproval(res) {
    res = res || {};
    var isRetail =
        res.is_retail_request === 1 ||
        res.is_retail_request === true ||
        res.source_type === "retail_request";
    var qcReq = res.qc_required === 1 || res.qc_required === true;
    var opsReq = res.ops_required === 1 || res.ops_required === true;
    var qcName = (res.qc_approved_by_name || "").trim();
    var opsName = (res.ops_approved_by_name || "").trim();
    var shipName = (res.ship_acc_by_name || "").trim();
    var hasQc = !!res.qc_approved_by || (qcName && qcName !== "-");
    var hasOps = !!res.ops_approved_by || (opsName && opsName !== "-");
    var hasShip = !!res.ship_acc_by || (shipName && shipName !== "-");

    // Request eceran: di body — Kepala Ops | Acc QC (bukan pengirim pemohon)
    if (isRetail) {
        $("#lbl_view_person_label").text("Kepala Operasional");
        $("#icon_view_person").attr("class", "fe fe-check-circle me-1 text-primary");
        $("#lbl_view_sender").text(hasOps ? opsName || "-" : "Belum approve");
        $("#view_qc_body_wrap").removeClass("d-none");
        $("#lbl_view_qc_body").text(hasQc ? qcName || "-" : qcReq ? "Belum approve" : "-");
        // Ops Acc = auto Kirim → tidak perlu badge Acc Kirim di header
        $("#view_transfer_approval_block").addClass("d-none");
        return;
    }

    // Transfer biasa: Pengirim = sender
    $("#lbl_view_person_label").text("Pengirim");
    $("#icon_view_person").attr("class", "fe fe-user me-1 text-primary");
    $("#lbl_view_sender").text(res.sender_name || "-");
    $("#view_qc_body_wrap").addClass("d-none");

    var showBlock = hasQc || hasOps || hasShip || qcReq || opsReq;
    if (!showBlock) {
        $("#view_transfer_approval_block").addClass("d-none");
        return;
    }
    $("#view_transfer_approval_block").removeClass("d-none");
    if (qcReq || hasQc) {
        $("#view_qc_wrap").removeClass("d-none");
        $("#lbl_view_qc_by").text(hasQc ? qcName || "-" : "Belum approve");
        $("#lbl_view_qc_at").text(hasQc ? res.qc_approved_at || "-" : "-");
    } else {
        $("#view_qc_wrap").addClass("d-none");
    }
    if (opsReq || hasOps) {
        $("#view_ops_wrap").removeClass("d-none");
        $("#lbl_view_ops_by").text(hasOps ? opsName || "-" : "Belum approve");
        $("#lbl_view_ops_at").text(hasOps ? res.ops_approved_at || "-" : "-");
    } else {
        $("#view_ops_wrap").addClass("d-none");
    }
    if (hasShip || parseInt(res.status, 10) >= 2) {
        $("#view_ship_wrap").removeClass("d-none");
        $("#lbl_view_ship_by").text(hasShip ? shipName || "-" : "-");
    } else {
        $("#view_ship_wrap").addClass("d-none");
    }
}

$(document).on("click", ".btnViewTransfer", function () {
    var id = $(this).attr("data-id");
    if (!id || !$("#view_stock_transfer").length) return;

    $("#lbl_view_sender, #lbl_view_from, #lbl_view_date, #lbl_view_receiver, #lbl_view_to, #lbl_view_ship_note, #lbl_view_accept_note").text("-");
    $("#lbl_view_qc_by, #lbl_view_qc_at, #lbl_view_ops_by, #lbl_view_ops_at, #lbl_view_ship_by, #lbl_view_qc_body").text("-");
    $("#lbl_view_person_label").text("Pengirim");
    $("#view_qc_body_wrap").addClass("d-none");
    $("#view_transfer_approval_block").addClass("d-none");
    $("#tableViewItems tbody").html(
        '<tr class="empty-row"><td colspan="5" class="text-center text-muted">Memuat data...</td></tr>'
    );
    $("#view_stock_transfer_loading").css("display", "flex");
    $("#view_stock_transfer").modal("show");

    $.ajax({
        url: "/getStockTransferDetail",
        method: "get",
        data: { id: id },
        success: function (res) {
            if (!res || !res.id) {
                $("#tableViewItems tbody").html(
                    '<tr class="empty-row"><td colspan="5" class="text-center text-muted">Data tidak ditemukan.</td></tr>'
                );
                if (typeof toastr !== "undefined") toastr.error("", "Data transfer tidak ditemukan");
                return;
            }

            // sender diisi di fillViewTransferApproval (retail → Kepala Ops)
            $("#lbl_view_from").text(res.from_warehouse_name || "-");
            $("#lbl_view_date").text(res.transfer_date || "-");
            $("#lbl_view_receiver").text(res.receiver_name || "-");
            $("#lbl_view_to").text(res.to_warehouse_name || "-");
            $("#lbl_view_ship_note").text(res.note || "-");
            $("#lbl_view_accept_note").text(res.accept_note || "-");

            fillViewTransferApproval(res);

            var items = res.items || [];
            if (!items.length) {
                $("#tableViewItems tbody").html(
                    '<tr class="empty-row"><td colspan="5" class="text-center text-muted py-5">Belum ada produk.</td></tr>'
                );
                return;
            }

            var html = "";
            items.forEach(function (it) {
                var unitLabel = it.unit_name || it.unit_short_name || "";
                var targetUnitLabel =
                    it.target_unit_name || it.received_unit_name || it.default_unit_name || unitLabel;
                var qtySend = formatTransferQty(it.qty);
                var qtyRecvConv =
                    it.qty_received != null && it.qty_received !== ""
                        ? formatTransferQty(it.qty_received)
                        : "-";
                html +=
                    "<tr data-search=\"" +
                    escapeHtml(
                        [
                            it.product_name,
                            it.product_variant_name,
                            it.sku,
                            it.product_variant_barcode || it.barcode || "",
                            unitLabel,
                        ]
                            .join(" ")
                            .toLowerCase()
                    ) +
                    "\">" +
                    "<td style=\"padding: 14px 16px; font-weight: 600; color: #1e293b;\">" +
                    escapeHtml(it.product_name || "-") +
                    "</td>" +
                    "<td style=\"padding: 14px 16px;\">" +
                    escapeHtml(it.product_variant_name || "-") +
                    "</td>" +
                    "<td style=\"padding: 14px 16px;\">" +
                    '<span class="badge bg-light text-secondary border" style="font-weight:500; font-size:12px;">' + escapeHtml(it.sku || "-") + '</span>' +
                    "</td>" +
                    '<td class="text-center" style="padding: 14px 16px; font-weight: 500;">' +
                    formatTransferQtyWithUnit(qtySend, unitLabel) +
                    "</td>" +
                    // Qty Terima & Selisih di-hide di view detail (retail = qty kirim)
                    '<td class="text-center" style="padding: 14px 16px; font-weight: 500;">' +
                    (qtyRecvConv === "-"
                        ? "-"
                        : formatTransferQtyWithUnit(qtyRecvConv, targetUnitLabel)) +
                    "</td>" +
                    "</tr>";
            });
            $("#tableViewItems tbody").html(html);
        },
        error: function () {
            $("#tableViewItems tbody").html(
                '<tr class="empty-row"><td colspan="5" class="text-center text-muted py-5">Gagal memuat data.</td></tr>'
            );
            if (typeof toastr !== "undefined") toastr.error("", "Gagal memuat detail transfer");
        },
        complete: function () {
            $("#view_stock_transfer_loading").hide();
        },
    });
});

$(document).on("hidden.bs.modal", "#view_stock_transfer", function () {
    $("#view_stock_transfer_loading").hide();
});

function formatTransferQty(val) {
    var n = parseFloat(val);
    if (isNaN(n)) return "-";
    // Rapikan noise float (mis. 3312.0719999999997)
    n = Math.round(n * 1e6) / 1e6;
    if (Math.abs(n - Math.round(n)) < 1e-9) return String(Math.round(n));
    return String(n);
}

function formatTransferQtyWithUnit(qtyText, unitLabel) {
    if (qtyText == null || qtyText === "" || qtyText === "-") return "-";
    var unit = String(unitLabel || "").trim();
    if (!unit || unit === "-") return escapeHtml(String(qtyText));
    return (
        '<span class="fw-semibold">' +
        escapeHtml(String(qtyText)) +
        "</span> <span class=\"text-muted\">" +
        escapeHtml(unit) +
        "</span>"
    );
}

function formatTransferSelisih(val, unitLabel) {
    if (val == null || val === "" || isNaN(parseFloat(val))) {
        return '<span class="text-muted">-</span>';
    }
    var n = parseFloat(val);
    var text = formatTransferQty(n);
    var unit = String(unitLabel || "").trim();
    var unitHtml =
        unit && unit !== "-"
            ? ' <span class="text-muted">' + escapeHtml(unit) + "</span>"
            : "";
    if (Math.abs(n) < 1e-9) {
        return '<span class="text-muted">0' + unitHtml + "</span>";
    }
    if (n < 0) {
        return (
            '<span style="color:#dc2626;font-weight:600;">' +
            escapeHtml(text) +
            unitHtml +
            " (kurang)</span>"
        );
    }
    return (
        '<span style="color:#059669;font-weight:600;">+' +
        escapeHtml(text) +
        unitHtml +
        " (lebih)</span>"
    );
}

$(document).on("click", ".btn-save-transfer", function () {
    var $btn = $(this);
    if ($btn.data("busy")) return;
    if (transferFormLocked) {
        if (typeof toastr !== "undefined") {
            toastr.info("", "Klik Edit Data dulu sebelum menyimpan perubahan");
        }
        return;
    }
    var thenShip = $("#add_stock_transfer").data("then-ship") === true;
    $("#add_stock_transfer").removeData("then-ship");

    if (
        hasPendingTransferRows() ||
        stockLoadPending > 0 ||
        stockValidationPending > 0
    ) {
        if (typeof toastr !== "undefined") {
            toastr.info("", "Mohon tunggu, stok produk masih divalidasi...");
        }
        return;
    }
    if (hasInsufficientStockRows()) {
        if (typeof toastr !== "undefined") {
            toastr.error("", "Perbaiki row merah yang belum valid sebelum menyimpan.");
        }
        return;
    }
    if (retailUnitValidationPending > 0 || hasMissingRetailUnitRows()) {
        if (typeof toastr !== "undefined") {
            toastr.warning(
                "",
                "Lengkapi satuan eceran pada semua row merah sebelum menyimpan transfer."
            );
        }
        return;
    }
    if (!validateWarehousesDifferent()) return;

    // reset validation
    $("#transfer_date").removeClass("is-invalid");
    clearTransferWarehouseFieldInvalid($("#transfer_sender_id"));
    clearTransferWarehouseFieldInvalid($("#transfer_from_warehouse_id"));
    clearTransferWarehouseFieldInvalid($("#transfer_to_warehouse_id"));

    var sender = $("#transfer_sender_id").val();
    var fromId = $("#transfer_from_warehouse_id").val();
    var toId = $("#transfer_to_warehouse_id").val();
    var date = $("#transfer_date").val();

    var valid = true;
    if (!sender) {
        $("#transfer_sender_id").addClass("is-invalid");
        $("#transfer_sender_id")
            .next(".select2-container")
            .find(".select2-selection")
            .addClass("is-invalid is-invalids");
        valid = false;
    }
    if (!fromId) {
        $("#transfer_from_warehouse_id").addClass("is-invalid");
        $("#transfer_from_warehouse_id")
            .next(".select2-container")
            .find(".select2-selection")
            .addClass("is-invalid is-invalids");
        valid = false;
        if (typeof toastr !== "undefined") {
            toastr.warning(
                "",
                transferIsRetailRequest ? "Pilih gudang request" : "Pilih gudang asal"
            );
        }
    }
    if (!toId) {
        markTransferToWarehouseInvalid();
        valid = false;
    }
    if (!date) {
        $("#transfer_date").addClass("is-invalid");
        valid = false;
    }

    if (!valid) {
        if (typeof toastr !== "undefined") {
            toastr.error("", "Lengkapi pengirim, gudang, dan tanggal");
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
    var invalidQty = transferItems.some(function (it) {
        return !Number.isInteger(Number(it.qty)) || Number(it.qty) <= 0;
    });
    if (invalidQty) {
        if (typeof toastr !== "undefined") toastr.error("", "Qty semua produk harus bilangan bulat positif");
        return;
    }

    var saveLabel = $btn.data("save-label") || $btn.html() || '<i class="fe fe-save me-1"></i>Simpan';
    $btn.data("save-label", saveLabel);
    $btn.data("busy", true);
    var $accBtn = $("#add_stock_transfer .btn-acc-transfer");
    var accLabel = $accBtn.data("acc-label") || $accBtn.html() || '<i class="fe fe-truck me-1"></i>Kirim';
    $accBtn.data("acc-label", accLabel);
    if (thenShip) $accBtn.data("busy", true);
    if (typeof LoadingButton === "function") {
        LoadingButton(".btn-save-transfer");
        if (thenShip) LoadingButton(".btn-acc-transfer");
    } else {
        $btn
            .prop("disabled", true)
            .html(
                '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Menyimpan...'
            );
        if (thenShip) {
            $accBtn
                .prop("disabled", true)
                .html(
                    '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Mengirim...'
                );
        }
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
            if (thenShip) ResetLoadingButton(".btn-acc-transfer", accLabel);
        } else {
            $btn.prop("disabled", false).html(saveLabel);
            if (thenShip) $accBtn.prop("disabled", false).html(accLabel);
        }
        $btn.data("busy", false);
        $btn.removeData("stock-loading");
        $accBtn.data("busy", false);
        $accBtn.removeData("stock-loading");
    }

    function doShip(stId) {
        $.ajax({
            url: "/shipStockTransfer",
            method: "post",
            data: {
                id: stId,
                proof_base64: transferThenShipProofBase64,
                _token: token || $('meta[name="csrf-token"]').attr("content"),
            },
            success: function (shipRes) {
                resetSaveBtn();
                transferThenShipProofBase64 = "";
                $("#transfer_then_ship_proof_base64").val("");
                if (!shipRes || shipRes.status != 1) {
                    if (typeof toastr !== "undefined") {
                        toastr.error("", (shipRes && shipRes.message) || "Gagal ACC kirim");
                    }
                    if (table) table.ajax.reload(null, false);
                    return;
                }
                if (typeof toastr !== "undefined") {
                    toastr.success("", shipRes.message || "Berhasil ACC kirim");
                }
                $("#add_stock_transfer").modal("hide");
                if (table) table.ajax.reload(null, false);
            },
            error: function (xhr) {
                resetSaveBtn();
                var msg =
                    (xhr.responseJSON && xhr.responseJSON.message) ||
                    "Gagal ACC kirim stock transfer";
                if (typeof toastr !== "undefined") toastr.error("", msg);
                if (table) table.ajax.reload(null, false);
            },
        });
    }

    function doSave() {
        $.ajax({
            url: mode === 2 ? "/updateStockTransfer" : "/insertStockTransfer",
            method: "post",
            data: payload,
            success: function (saveRes) {
                if (!saveRes || saveRes.status != 1) {
                    resetSaveBtn();
                    if (typeof toastr !== "undefined") {
                        toastr.error("", (saveRes && saveRes.message) || "Gagal menyimpan");
                    }
                    return;
                }
                var savedId = editId || (saveRes.id || saveRes.st_id);
                if (thenShip && savedId) {
                    doShip(savedId);
                    return;
                }
                resetSaveBtn();
                if (typeof toastr !== "undefined") {
                    toastr.success("", saveRes.message || "Berhasil disimpan");
                }
                if (mode === 2) {
                    snapshotTransferForm();
                    setTransferFormLocked(true);
                    if (table) table.ajax.reload(null, false);
                    return;
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

    validateCurrentTransferMatrix(function (valid) {
        if (!valid) {
            resetSaveBtn();
            return;
        }
        setTimeout(doSave, 0);
    }, true);
});

$(document).on("click", ".btn-acc-transfer", function () {
    if ($(this).data("busy")) return;
    var id = $("#add_stock_transfer").attr("data-id");
    if (mode !== 2 || !id) {
        if (typeof toastr !== "undefined") {
            toastr.warning("", "Kirim hanya dari detail pending");
        }
        return;
    }
    // View mode: langsung ship. Edit mode: simpan dulu lalu ship.
    if (transferFormLocked) {
        showTransferModalKonfirmasi(
            "Kirim transfer ini? Stok gudang asal akan dipotong dan status menjadi Kirim.",
            "btn-ship-stock-transfer",
            id,
            null,
            false,
            true
        );
        return;
    }
    // Simpan + Kirim langsung (edit mode, tanpa konfirmasi teks) — bukti foto tetap wajib
    // (GitHub #140): ambil dulu lewat kamera, baru lanjutkan Simpan+Kirim otomatis.
    if (!transferThenShipProofBase64) {
        modeCamera = 5;
        rotationAngle = 0;
        camRotation = 0;
        photoData = "";
        inputFile = "#transfer_then_ship_proof_base64";
        cameraReturnModal = "#add_stock_transfer";
        $("#video").removeClass("rot90 rot180 rot270");
        $("#preview-box").hide();
        $("#camera").show();
        startCamera();
        transferPendingThenShip = true;
        $("#add_stock_transfer").modal("hide");
        $("#modalPhoto").modal("show");
        return;
    }
    $("#add_stock_transfer").data("then-ship", true);
    $(".btn-save-transfer").trigger("click");
});

$(document).on("shown.bs.modal", "#add_stock_transfer", function () {
    transferThenShipProofBase64 = $("#transfer_then_ship_proof_base64").val() || "";
    if (transferPendingThenShip && transferThenShipProofBase64) {
        transferPendingThenShip = false;
        $("#add_stock_transfer").data("then-ship", true);
        $(".btn-save-transfer").trigger("click");
    }
});

function approveStockTransfer(type) {
    var id =
        $("#modalKonfirmasi").attr("data-transfer-id") ||
        $("#modalKonfirmasi #btn-approve-" + type + "-stock-transfer").attr("data-id") ||
        $("#add_stock_transfer").attr("data-id");
    if (!id) {
        if (typeof toastr !== "undefined") toastr.error("", "ID transfer tidak ditemukan");
        return;
    }
    var label = type === "qc" ? "QC" : "Kepala Operasional";
    var $confirmBtn = $("#modalKonfirmasi #btn-approve-" + type + "-stock-transfer");
    if ($confirmBtn.data("busy")) return;
    if (!assertKonfirmasiPhotoProof()) return;

    // UX pre-check stok (QC22); gate wajib tetap di BE approveStockTransfer + shipLockedTransfer.
    function runApprove() {
        $confirmBtn.data("busy", true);
        if (typeof LoadingButton === "function") LoadingButton($confirmBtn);
        $.ajax({
            url: "/approveStockTransfer",
            method: "post",
            data: {
                id: id,
                type: type,
                proof_base64: getKonfirmasiPhotoProof(),
                _token: token || $('meta[name="csrf-token"]').attr("content"),
            },
            success: function (res) {
                $confirmBtn.data("busy", false);
                if (typeof ResetLoadingButton === "function") {
                    ResetLoadingButton(
                        $confirmBtn,
                        '<i class="fe fe-check-circle me-1"></i>Konfirmasi'
                    );
                }
                if (!res || res.status != 1) {
                    if (typeof closeModalConfirm === "function") closeModalConfirm();
                    if (typeof toastr !== "undefined") {
                        toastr.error("", (res && res.message) || "Gagal approve " + label);
                    }
                    return;
                }
                markTransferOverlayDone();
                if (typeof closeModalConfirm === "function") closeModalConfirm();
                if (typeof toastr !== "undefined") {
                    toastr.success("", res.message || "Approval " + label + " berhasil");
                }
                if (type === "qc") {
                    transferCanApproveQc = false;
                    transferQcApproved = true;
                } else {
                    transferCanApproveOps = false;
                    transferOpsApproved = true;
                    transferCanReject = false;
                }
                if (res.auto_shipped == 1 || res.auto_shipped === true) {
                    transferCanShip = false;
                    transferCanReject = false;
                }
                if (table) table.ajax.reload(null, false);
                // Auto-Kirim setelah Ops Acc: jangan buka ulang detail (status sudah Kirim).
                if (res.auto_shipped == 1 || res.auto_shipped === true) {
                    $("#add_stock_transfer").modal("hide");
                    return;
                }
                // QC saja: buka ulang detail supaya tombol Ops muncul
                loadTransferDetailForEdit(id);
            },
            error: function (xhr) {
                $confirmBtn.data("busy", false);
                if (typeof ResetLoadingButton === "function") {
                    ResetLoadingButton(
                        $confirmBtn,
                        '<i class="fe fe-check-circle me-1"></i>Konfirmasi'
                    );
                }
                if (typeof closeModalConfirm === "function") closeModalConfirm();
                var msg =
                    (xhr.responseJSON && xhr.responseJSON.message) ||
                    "Gagal approve " + label;
                if (typeof toastr !== "undefined") toastr.error("", msg);
            },
        });
    }

    if (typeof validateCurrentTransferMatrix === "function" && transferItems.length) {
        validateCurrentTransferMatrix(function (ok) {
            if (!ok) {
                if (typeof closeModalConfirm === "function") closeModalConfirm();
                return;
            }
            runApprove();
        });
        return;
    }
    runApprove();
}

$(document).on("click", ".btn-approve-qc-transfer", function () {
    var id = $("#add_stock_transfer").attr("data-id");
    if (!id) {
        if (typeof toastr !== "undefined") toastr.error("", "ID transfer tidak ditemukan");
        return;
    }
    showTransferModalKonfirmasi(
        "Setujui request ini (QC)? Setelah disetujui, menunggu approval Kepala Operasional.",
        "btn-approve-qc-stock-transfer",
        id,
        null,
        false,
        willTransferAutoShip("qc")
    );
});
$(document).on("click", ".btn-approve-ops-transfer", function () {
    var id = $("#add_stock_transfer").attr("data-id");
    if (!id) {
        if (typeof toastr !== "undefined") toastr.error("", "ID transfer tidak ditemukan");
        return;
    }
    showTransferModalKonfirmasi(
        "Setujui request ini (Kepala Operasional)? Stok gudang asal akan dipotong dan status menjadi Kirim — gudang eceran dapat menerima.",
        "btn-approve-ops-stock-transfer",
        id,
        null,
        false,
        willTransferAutoShip("ops")
    );
});

$(document).on("click", "#btn-approve-qc-stock-transfer", function () {
    approveStockTransfer("qc");
});
$(document).on("click", "#btn-approve-ops-stock-transfer", function () {
    approveStockTransfer("ops");
});

$(document).on("click", ".btn-enable-edit-transfer", function () {
    if (mode !== 2 || !transferCanEdit) return;
    snapshotTransferForm();
    setTransferFormLocked(false);
});

$(document).on("click", ".btn-cancel-transfer", function () {
    // Mode edit pending: batal edit, jangan close modal
    if (mode === 2 && !transferFormLocked) {
        restoreTransferFormSnapshot();
        setTransferFormLocked(true);
        return;
    }
    $("#add_stock_transfer").modal("hide");
});

$(document).on("click", ".btn-reject-transfer", function () {
    var id = $("#add_stock_transfer").attr("data-id");
    var sourceType = $("#add_stock_transfer").attr("data-source-type") || "";
    if (!id) return;
    if (sourceType === "production") {
        showProductionRejectOverTransfer(id);
        return;
    }
    showTransferModalKonfirmasi(
        transferRequiresApproval || transferQcRequired || transferOpsRequired
            ? "Tolak transfer ini? Status menjadi Cancel dan proses approval/Kirim dihentikan."
            : "Cancel transfer pending ini? Status menjadi Cancel (stok belum dipotong).",
        "btn-reject-stock-transfer",
        id,
        null,
        true
    );
});

// Remove invalid class on change (warehouse fields re-validated by validateWarehousesDifferent)
$(document).on("change", "#transfer_sender_id", function () {
    clearTransferWarehouseFieldInvalid($(this));
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

function setTransferModalLoading(isLoading) {
    var $modal = $("#add_stock_transfer");
    var loading = !!isLoading;
    $modal.toggleClass("is-loading", loading);
    $modal
        .find(
            ".pg-modal-footer .btn, .btn-enable-edit-transfer, .btn-save-transfer, .btn-acc-transfer, .btn-reject-transfer, .btn-cancel-transfer, .btn-approve-qc-transfer, .btn-approve-ops-transfer"
        )
        .prop("disabled", loading)
        .attr("aria-disabled", loading ? "true" : "false");
    if (loading) {
        // Sembunyikan aksi proses sampai data siap (Batal ikut disabled, tetap ada di DOM)
        $modal.find(".btn-acc-transfer, .btn-reject-transfer, .btn-save-transfer, .btn-enable-edit-transfer, .btn-approve-qc-transfer, .btn-approve-ops-transfer")
            .addClass("d-none")
            .removeClass("d-inline-flex");
    }
}

var transferDetailLoadSeq = 0;

function loadTransferDetailForEdit(id) {
    if (!$("#add_stock_transfer").length) return;

    var loadSeq = ++transferDetailLoadSeq;
    mode = 2;
    resetTransferForm();
    initTransferAutocompletes();
    $("#add_stock_transfer").attr("data-id", id);
    $("#add_stock_transfer").removeAttr("data-source-type");
    syncTransferEditActions(false, false, false);
    setTransferFormLocked(true);
    $("#add_stock_transfer .modal-title").text("Detail Stock Transfer");
    $("#add_stock_transfer .transfer-modal-subtitle").text("Memuat data...");
    setTransferModalLoading(true);
    $("#add_stock_transfer").modal("show");

    $.ajax({
        url: "/getStockTransferDetail",
        method: "get",
        data: { id: id },
        success: function (res) {
            if (loadSeq !== transferDetailLoadSeq) return;
            if (!res || !res.id) {
                setTransferModalLoading(false);
                $("#add_stock_transfer").modal("hide");
                if (typeof toastr !== "undefined") toastr.error("", "Data transfer tidak ditemukan");
                return;
            }
            $("#add_stock_transfer").attr("data-id", res.id);
            $("#add_stock_transfer").attr("data-source-type", res.source_type || "");
            var isRetailRequest =
                res.source_type === "retail_request" ||
                res.is_retail_request === 1 ||
                res.is_retail_request === true;
            transferIsRetailRequest = !!isRetailRequest;
            transferCreateRequestMode = false;
            initTransferWarehouseAutocompletes();
            applyTransferRouteLabels(transferIsRetailRequest);
            $("#transfer_date").val(res.transfer_date);
            if ($("#transfer_date").data("DateTimePicker")) {
                $("#transfer_date").data("DateTimePicker").date(res.transfer_date);
            }
            $("#transfer_note").val(res.note || "");
            // Bukti foto pengiriman (GitHub #140) — hanya muncul saat status Kirim.
            if (res.ship_proof_url) {
                $("#st-ship-proof-thumb").attr("src", res.ship_proof_url);
                $("#st-ship-proof-link").attr("href", res.ship_proof_url);
                $("#st-ship-proof-slot").removeClass("d-none");
            } else {
                $("#st-ship-proof-slot").addClass("d-none");
                $("#st-ship-proof-thumb").attr("src", "");
                $("#st-ship-proof-link").attr("href", "javascript:void(0);");
            }
            setDefaultSender();
            fillSelectOption($("#transfer_from_warehouse_id"), res.from_warehouse_id, res.from_warehouse_name);
            fillSelectOption($("#transfer_to_warehouse_id"), res.to_warehouse_id, res.to_warehouse_name);
            enableTransferProductSelect();

            transferItems = (res.items || []).map(function (it) {
                return {
                    product_id: it.product_id,
                    product_variant_id: it.product_variant_id,
                    product_name: it.product_name,
                    product_variant_name: it.product_variant_name,
                    product_variant_sku: it.sku || "-",
                    sku: it.sku,
                    qty: parseFloat(it.qty) || 1,
                    unit_id: it.unit_id,
                    unit_name: it.unit_name,
                    stock_text: it.stock_text || "-",
                    units: it.units || [],
                    stock_invalid: false,
                    stock_error: null,
                };
            });
            refreshTransferItemsTable();
            revalidateAllTransferRows(false);
            syncTransferEditActions(
                res.can_ship === true || res.can_ship === 1,
                res.can_reject === true || res.can_reject === 1,
                res.can_edit === true || res.can_edit === 1,
                {
                    canApproveQc: res.can_approve_qc === true || res.can_approve_qc === 1,
                    canApproveOps: res.can_approve_ops === true || res.can_approve_ops === 1,
                    requiresApproval: res.requires_approval === true || res.requires_approval === 1,
                    qcRequired: res.qc_required === true || res.qc_required === 1,
                    opsRequired: res.ops_required === true || res.ops_required === 1,
                    qcApproved: res.qc_approved === true || res.qc_approved === 1,
                    opsApproved: res.ops_approved === true || res.ops_approved === 1,
                }
            );
            snapshotTransferForm();
            setTransferFormLocked(true);
            setTransferModalLoading(false);
        },
        error: function () {
            if (loadSeq !== transferDetailLoadSeq) return;
            setTransferModalLoading(false);
            $("#add_stock_transfer").modal("hide");
            if (typeof toastr !== "undefined") toastr.error("", "Gagal memuat detail");
        },
    });
}

function renderAcceptItems(items) {
    var $tb = $("#tableAcceptItems tbody");
    if (!items || !items.length) {
        $tb.html(
            '<tr class="empty-row"><td colspan="7" class="text-center text-muted">Belum ada produk.</td></tr>'
        );
        return;
    }
    var html = "";
    items.forEach(function (it, idx) {
        var qtyKirim = parseFloat(it.qty) || 0;
        var qty = qtyKirim;
        if (isNaN(qty)) qty = qtyKirim;
        var unitLabel = it.unit_name || it.unit_short_name || "";
        var targetUnitLabel =
            it.target_unit_name || it.received_unit_name || it.default_unit_name || unitLabel;
        var factor = parseFloat(it.conversion_factor);
        if (isNaN(factor) || factor <= 0) factor = 1;
        var convertedSent = parseFloat(it.converted_sent_qty);
        if (isNaN(convertedSent)) convertedSent = qtyKirim * factor;
        var convertedPreview = qty * factor;
        var selisih = convertedPreview - convertedSent;
        html +=
            "<tr data-std-id=\"" +
            it.std_id +
            "\" data-qty-kirim=\"" +
            qtyKirim +
            "\" data-unit=\"" +
            escapeHtml(unitLabel) +
            "\" data-target-unit=\"" +
            escapeHtml(targetUnitLabel) +
            "\" data-conversion-factor=\"" +
            factor +
            "\" data-converted-sent=\"" +
            convertedSent +
            "\" data-search=\"" +
            escapeHtml(
                [
                    it.product_name,
                    it.product_variant_name,
                    it.sku,
                    it.product_variant_barcode || it.barcode || "",
                    unitLabel,
                ]
                    .join(" ")
                    .toLowerCase()
            ) +
            "\">" +
            "<td style=\"padding: 14px 16px; font-weight: 600; color: #1e293b;\">" +
            escapeHtml(it.product_name || "-") +
            "</td>" +
            "<td style=\"padding: 14px 16px;\">" +
            escapeHtml(it.product_variant_name || "-") +
            "</td>" +
            "<td style=\"padding: 14px 16px;\">" +
            '<span class="badge bg-light text-secondary border" style="font-weight:500; font-size:12px;">' + escapeHtml(it.sku || "-") + '</span>' +
            "</td>" +
            '<td class="text-center" style="padding: 14px 16px; font-weight: 500;">' +
            formatTransferQtyWithUnit(formatTransferQty(qtyKirim), unitLabel) +
            "</td>" +
            '<td class="d-none st-col-qty-terima" style="padding: 14px 16px;"><div class="input-group input-group-sm" style="max-width: 160px; box-shadow: 0 1px 2px rgba(0,0,0,0.03); border-radius: 6px; overflow: hidden;">' +
            '<input type="number" min="0" step="1" inputmode="numeric" class="form-control accept-qty text-center number-only" data-index="' +
            idx +
            '" value="' +
            Math.round(qty) +
            '" readonly tabindex="-1" aria-hidden="true" style="font-weight: 600; color: #334155; font-size: 14px; height: 34px;">' +
            (unitLabel
                ? '<span class="input-group-text" style="background: #f8fafc; border-color: #e2e8f0; color: #64748b; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .3px;">' +
                  escapeHtml(unitLabel) +
                  "</span>"
                : "") +
            "</div></td>" +
            '<td class="text-center accept-conversion" style="padding: 14px 16px; font-weight: 500;">' +
            formatTransferQtyWithUnit(formatTransferQty(convertedPreview), targetUnitLabel) +
            '<div class="text-muted mt-1" style="font-size:11px;">satuan stok tujuan</div>' +
            "</td>" +
            '<td class="text-center accept-selisih d-none st-col-selisih" style="padding: 14px 16px; font-weight: 500;">' +
            formatTransferSelisih(selisih, targetUnitLabel) +
            "</td>" +
            "</tr>";
    });
    $tb.html(html);
    $("#accept_stock_transfer").data("accept-items", items);
    $("#search_accept_barcode").val("");
}

$(document).on("input keyup", "#search_accept_barcode", function () {
    var q = String($(this).val() || "")
        .trim()
        .toLowerCase();
    var $rows = $("#tableAcceptItems tbody tr[data-std-id]");
    if (!$rows.length) return;
    $("#tableAcceptItems tbody tr.empty-search").remove();
    if (!q) {
        $rows.show();
        return;
    }
    var hasMatch = false;
    $rows.each(function () {
        var hay = String($(this).attr("data-search") || "");
        var isMatch = hay.indexOf(q) !== -1;
        $(this).toggle(isMatch);
        if (isMatch) hasMatch = true;
    });
    if (!hasMatch) {
        $("#tableAcceptItems tbody").append(
            '<tr class="empty-search"><td colspan="7" class="text-center py-5"><div style="color:#94a3b8;"><i class="fe fe-search" style="font-size:36px;display:block;margin-bottom:8px;"></i><div class="fw-semibold" style="font-size:14px;">Produk tidak ditemukan</div><div style="font-size:12px;">Pencarian "'+escapeHtml(q)+'" tidak membuahkan hasil.</div></div></td></tr>'
        );
    }
});

$(document).on("input keyup", "#search_view_barcode", function () {
    var q = String($(this).val() || "").trim().toLowerCase();
    var $rows = $("#tableViewItems tbody tr[data-search]");
    if (!$rows.length) return;
    $("#tableViewItems tbody tr.empty-search").remove();
    if (!q) {
        $rows.show();
        return;
    }
    var hasMatch = false;
    $rows.each(function () {
        var hay = String($(this).attr("data-search") || "");
        var isMatch = hay.indexOf(q) !== -1;
        $(this).toggle(isMatch);
        if (isMatch) hasMatch = true;
    });
    if (!hasMatch) {
        $("#tableViewItems tbody").append(
            '<tr class="empty-search"><td colspan="5" class="text-center py-5"><div style="color:#94a3b8;"><i class="fe fe-search" style="font-size:36px;display:block;margin-bottom:8px;"></i><div class="fw-semibold" style="font-size:14px;">Produk tidak ditemukan</div><div style="font-size:12px;">Pencarian "'+escapeHtml(q)+'" tidak membuahkan hasil.</div></div></td></tr>'
        );
    }
});

$(document).on("keydown", "#search_accept_barcode", function (e) {
    if (e.key !== "Enter") return;
    e.preventDefault();
    var q = String($(this).val() || "")
        .trim()
        .toLowerCase();
    if (!q) return;
    var $rows = $("#tableAcceptItems tbody tr[data-std-id]");
    $rows.removeClass("table-warning");
    var $match = $rows.filter(function () {
        return String($(this).attr("data-search") || "").indexOf(q) !== -1;
    });
    if ($match.length === 1) {
        $match.addClass("table-warning");
        // Qty terima di-hide (retail = qty kirim) — jangan fokus ke input tersembunyi
        var $qty = $match.find(".accept-qty");
        if ($qty.length && $qty.is(":visible")) {
            $qty.trigger("focus").select();
        } else if ($match[0] && typeof $match[0].scrollIntoView === "function") {
            $match[0].scrollIntoView({ block: "nearest", behavior: "smooth" });
        }
        $(this).val("");
    } else if ($match.length === 0 && typeof toastr !== "undefined") {
        toastr.warning("", "Produk tidak ada di daftar transfer ini");
    } else if ($match.length > 1) {
        $match.addClass("table-warning");
        if (typeof toastr !== "undefined") {
            toastr.info("", "Beberapa produk cocok — periksa baris yang di-highlight");
        }
    }
});

$(document).on("input change", "#tableAcceptItems .accept-qty", function () {
    var raw = String($(this).val() || "").replace(/[^\d]/g, "");
    if ($(this).val() !== raw) {
        $(this).val(raw);
    }
    var $tr = $(this).closest("tr");
    var qtyKirim = parseInt($tr.attr("data-qty-kirim"), 10) || 0;
    var qtyRecv = parseInt(raw, 10);
    if (isNaN(qtyRecv) || qtyRecv < 0) qtyRecv = 0;
    var factor = parseFloat($tr.attr("data-conversion-factor")) || 1;
    var convertedSent = parseFloat($tr.attr("data-converted-sent"));
    if (isNaN(convertedSent)) convertedSent = qtyKirim * factor;
    var targetUnitLabel = $tr.attr("data-target-unit") || $tr.attr("data-unit") || "";
    var convertedReceived = qtyRecv * factor;
    $tr.find(".accept-conversion").html(
        formatTransferQtyWithUnit(formatTransferQty(convertedReceived), targetUnitLabel) +
            '<div class="text-muted mt-1" style="font-size:11px;">satuan stok tujuan</div>'
    );
    $tr.find(".accept-selisih").html(
        formatTransferSelisih(convertedReceived - convertedSent, targetUnitLabel)
    );
});

$(document).on("click", ".btnOpenTransfer, .btnEditTransfer", function () {
    var id = $(this).attr("data-id");
    if (!id) return;
    loadTransferDetailForEdit(id);
});

$(document).on("click", ".btnDeleteTransfer", function () {
    var id = $(this).attr("data-id");
    var status = parseInt($(this).attr("data-status"), 10);
    var isRetailRequest =
        $(this).attr("data-retail-request") === "1" ||
        $(this).attr("data-retail-request") === "true";
    if (!id) return;
    if (status !== 1) {
        if (typeof toastr !== "undefined") {
            toastr.warning(
                "",
                isRetailRequest
                    ? "Hanya transfer berstatus Pending yang bisa di-cancel"
                    : "Hanya transfer berstatus Pending yang bisa dihapus"
            );
        }
        return;
    }
    if (isRetailRequest) {
        showTransferModalKonfirmasi(
            "Cancel request stock transfer ini? Status menjadi Cancel.",
            "btn-reject-stock-transfer",
            id,
            null,
            true
        );
        return;
    }
    showModalDelete(
        "Hapus stock transfer pending ini?",
        "btn-delete-stock-transfer"
    );
    $("#modalDelete #btn-delete-stock-transfer").attr("data-id", id);
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

$(document).on("click", ".btnViewShipProof", function (e) {
    e.stopPropagation();
    var url = $(this).attr("data-url");
    if (url) window.open(url, "_blank");
});

$(document).on("click", ".btnShipTransfer", function () {
    var id = $(this).attr("data-id");
    if (!id) return;
    var row = table ? table.row($(this).closest("tr")).data() : null;
    var fromWh = row ? String(row.from_warehouse_id || "") : "";
    var activeWh =
        typeof getActiveWarehouseId === "function" ? String(getActiveWarehouseId() || "") : "";

    if (fromWh && activeWh && fromWh !== activeWh) {
        if (typeof toastr !== "undefined") {
            toastr.warning("", "Ganti gudang aktif ke gudang asal sebelum kirim");
        }
        return;
    }

    showModalKonfirmasi(
        row && row.source_type === "production"
            ? "Kirim hasil produksi ini? Stok gudang asal akan dipotong dan status menjadi Kirim."
            : "Kirim transfer ini? Stok gudang asal akan dipotong dan status menjadi Kirim.",
        "btn-ship-stock-transfer"
    );
    $("#modalKonfirmasi #btn-ship-stock-transfer").attr("data-id", id);
    showKonfirmasiPhotoProof();
});

$(document).on("click", "#btn-ship-stock-transfer", function () {
    var $confirmBtn = $(this);
    if ($confirmBtn.data("busy")) return;
    var id = $confirmBtn.attr("data-id") || $("#modalKonfirmasi").attr("data-transfer-id");
    if (!id) {
        if (typeof toastr !== "undefined") {
            toastr.error("", "ID transfer tidak ditemukan");
        }
        return;
    }
    if (!assertKonfirmasiPhotoProof()) return;
    $confirmBtn.data("busy", true);
    LoadingButton($confirmBtn);
    $.ajax({
        url: "/shipStockTransfer",
        method: "post",
        data: {
            id: id,
            proof_base64: getKonfirmasiPhotoProof(),
            _token: token || $('meta[name="csrf-token"]').attr("content"),
        },
        success: function (res) {
            $confirmBtn.data("busy", false);
            ResetLoadingButton(
                $confirmBtn,
                '<i class="fe fe-check-circle me-1"></i>Konfirmasi'
            );
            if (!res || res.status != 1) {
                closeModalConfirm();
                if (typeof toastr !== "undefined") {
                    toastr.error("", (res && res.message) || "Gagal kirim transfer");
                }
                return;
            }
            markTransferOverlayDone();
            closeModalConfirm();
            if (typeof toastr !== "undefined") toastr.success("", res.message || "Berhasil dikirim");
            $("#add_stock_transfer").modal("hide");
            if (table) table.ajax.reload(null, false);
        },
        error: function (xhr) {
            $confirmBtn.data("busy", false);
            ResetLoadingButton(
                $confirmBtn,
                '<i class="fe fe-check-circle me-1"></i>Konfirmasi'
            );
            closeModalConfirm();
            var msg =
                (xhr.responseJSON && xhr.responseJSON.message) ||
                "Gagal kirim stock transfer";
            if (typeof toastr !== "undefined") toastr.error("", msg);
        },
    });
});

$(document).on("click", ".btnRejectTransfer, .btnRejectProductionTransfer", function () {
    var id = $(this).attr("data-id");
    if (!id) return;
    var sourceType = $(this).attr("data-source") || "";
    if (sourceType === "production") {
        $("#reject_production_transfer").attr("data-id", id);
        $("#production_reject_notes").val("");
        $("#reject_production_transfer").modal("show");
        return;
    }
    showModalKonfirmasi(
        "Cancel transfer pending ini? Status menjadi Cancel.",
        "btn-reject-stock-transfer",
        true
    );
    $("#modalKonfirmasi #btn-reject-stock-transfer").attr("data-id", id);
});

$(document).on("click", "#btn-reject-stock-transfer", function () {
    var $confirmBtn = $(this);
    if ($confirmBtn.data("busy")) return;
    var id = $confirmBtn.attr("data-id") || $("#modalKonfirmasi").attr("data-transfer-id");
    if (!id) return;
    $confirmBtn.data("busy", true);
    LoadingButton($confirmBtn);
    $.ajax({
        url: "/rejectStockTransfer",
        method: "post",
        data: {
            id: id,
            _token: token || $('meta[name="csrf-token"]').attr("content"),
        },
        success: function (res) {
            $confirmBtn.data("busy", false);
            ResetLoadingButton(
                $confirmBtn,
                '<i class="fe fe-check-circle me-1"></i>Konfirmasi'
            );
            if (!res || res.status != 1) {
                closeModalConfirm();
                if (typeof toastr !== "undefined") {
                    toastr.error("", (res && res.message) || "Gagal cancel transfer");
                }
                return;
            }
            markTransferOverlayDone();
            closeModalConfirm();
            if (typeof toastr !== "undefined") toastr.success("", res.message || "Berhasil di-cancel");
            $("#add_stock_transfer").modal("hide");
            if (table) table.ajax.reload(null, false);
        },
        error: function (xhr) {
            $confirmBtn.data("busy", false);
            ResetLoadingButton(
                $confirmBtn,
                '<i class="fe fe-check-circle me-1"></i>Konfirmasi'
            );
            closeModalConfirm();
            var msg =
                (xhr.responseJSON && xhr.responseJSON.message) ||
                "Gagal cancel stock transfer";
            if (typeof toastr !== "undefined") toastr.error("", msg);
        },
    });
});

$(document).on("click", ".btnCancelKirimTransfer", function () {
    var id = $(this).attr("data-id");
    if (!id) return;
    showModalDanger(
        "Cancel Kirim transfer ini? Stok akan dikembalikan ke gudang asal.",
        "btn-cancel-kirim-stock-transfer"
    );
    $("#modalDanger #btn-cancel-kirim-stock-transfer").attr("data-id", id);
});

$(document).on("click", ".btn-reject-accept-transfer", function () {
    var id = $("#accept_stock_transfer").attr("data-id");
    if (!id) {
        if (typeof toastr !== "undefined") toastr.error("", "ID transfer tidak ditemukan");
        return;
    }
    showModalDanger(
        "Tolak transfer? Stok akan dikembalikan ke gudang asal.",
        "btn-cancel-kirim-stock-transfer"
    );
    $("#modalDanger #btn-cancel-kirim-stock-transfer").attr("data-id", id);
});

$(document).on("click", "#btn-cancel-kirim-stock-transfer", function () {
    var $confirmBtn = $(this);
    if ($confirmBtn.data("busy")) return;
    var id = $confirmBtn.attr("data-id");
    if (!id) return;
    $confirmBtn.data("busy", true);
    LoadingButton($confirmBtn);
    $.ajax({
        url: "/cancelKirimStockTransfer",
        method: "post",
        data: {
            id: id,
            _token: token || $('meta[name="csrf-token"]').attr("content"),
        },
        success: function (res) {
            $confirmBtn.data("busy", false);
            ResetLoadingButton(
                $confirmBtn,
                '<i class="fe fe-check-circle me-1"></i>Konfirmasi'
            );
            if (typeof closeModalDanger === "function") closeModalDanger();
            if (!res || res.status != 1) {
                if (typeof toastr !== "undefined") {
                    toastr.error("", (res && res.message) || "Gagal cancel kirim");
                }
                return;
            }
            if (typeof toastr !== "undefined") toastr.success("", res.message || "Cancel Kirim berhasil");
            $("#accept_stock_transfer").modal("hide");
            $("#add_stock_transfer").modal("hide");
            if (table) table.ajax.reload(null, false);
        },
        error: function (xhr) {
            $confirmBtn.data("busy", false);
            ResetLoadingButton(
                $confirmBtn,
                '<i class="fe fe-check-circle me-1"></i>Konfirmasi'
            );
            if (typeof closeModalDanger === "function") closeModalDanger();
            var msg =
                (xhr.responseJSON && xhr.responseJSON.message) ||
                "Gagal cancel kirim stock transfer";
            if (typeof toastr !== "undefined") toastr.error("", msg);
        },
    });
});

$(document).on("click", ".btn-confirm-production-reject", function () {
    var id = $("#reject_production_transfer").attr("data-id");
    if (!id) {
        if (typeof toastr !== "undefined") toastr.error("", "ID transfer tidak ditemukan");
        return;
    }
    var $btn = $(this);
    if ($btn.data("busy")) return;
    $btn.data("busy", true);
    LoadingButton(this);
    $.ajax({
        url: "/rejectStockTransfer",
        method: "post",
        data: {
            id: id,
            disposition: "return_warehouse",
            notes: $("#production_reject_notes").val(),
            _token: token || $('meta[name="csrf-token"]').attr("content"),
        },
        success: function (res) {
            ResetLoadingButton(".btn-confirm-production-reject", '<i class="fe fe-x-circle me-1"></i> Tolak ST');
            $btn.data("busy", false);
            if (!res || res.status != 1) {
                if (typeof toastr !== "undefined") toastr.error("", (res && res.message) || "Gagal menolak transfer");
                return;
            }
            markTransferOverlayDone();
            $("#reject_production_transfer").modal("hide");
            $("#add_stock_transfer").modal("hide");
            if (typeof toastr !== "undefined") toastr.success("", res.message || "Stock transfer dibatalkan");
            if (table) table.ajax.reload(null, false);
        },
        error: function (xhr) {
            ResetLoadingButton(".btn-confirm-production-reject", '<i class="fe fe-x-circle me-1"></i> Tolak ST');
            $btn.data("busy", false);
            var message = (xhr.responseJSON && xhr.responseJSON.message) || "Gagal menolak transfer";
            if (typeof toastr !== "undefined") toastr.error("", message);
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
    if (row && row.source_type !== "production" && fromWh && activeWh && fromWh === activeWh) {
        if (typeof toastr !== "undefined") {
            toastr.warning("", "Gudang asal tidak bisa ACC. ACC hanya di gudang tujuan.");
        }
        return;
    }

    $("#accept_stock_transfer").attr("data-id", id);
    $("#accept_stock_transfer input, #accept_stock_transfer textarea").val("");
    $("#accept_stock_transfer select").val(null).trigger("change");
    $("#lbl_accept_sender, #lbl_accept_from, #lbl_accept_date, #lbl_accept_to, #lbl_accept_ship_note").text("-");
    $("#accept-ship-proof-slot").hide();
    $("#accept-ship-proof-thumb").attr("src", "");
    renderAcceptItems([]);
    // Jangan init autocomplete — penerima dikunci ke user login
    var $recv = $("#accept_receiver_id");
    if ($recv.hasClass("select2-hidden-accessible")) {
        try { $recv.select2("destroy"); } catch (e) {}
    }
    $recv.empty().prop("disabled", true);
    if (staff.id && staff.name) {
        fillSelectOption($recv, staff.id, staff.name);
    }
    $recv.prop("disabled", true);

    $.ajax({
        url: "/getStockTransferDetail",
        method: "get",
        data: { id: id },
        success: function (res) {
            if (!res || !res.id) {
                if (typeof toastr !== "undefined") toastr.error("", "Data transfer tidak ditemukan");
                return;
            }
            if (parseInt(res.status, 10) !== 2) {
                if (typeof toastr !== "undefined") {
                    toastr.warning("", "Transfer harus berstatus Kirim sebelum diterima");
                }
                return;
            }
            $("#lbl_accept_sender").text(res.sender_name || "-");
            $("#lbl_accept_from").text(res.from_warehouse_name || "-");
            $("#lbl_accept_date").text(res.transfer_date || "-");
            $("#lbl_accept_to").text(res.to_warehouse_name || "-");
            $("#lbl_accept_ship_note").text(res.note || "-");
            // Bukti foto pengiriman (GitHub #140) — muncul hanya saat status Kirim (jaminan di atas).
            if (res.ship_proof_url) {
                $("#accept-ship-proof-thumb").attr("src", res.ship_proof_url);
                $("#accept-ship-proof-link").attr("href", res.ship_proof_url);
                $("#accept-ship-proof-slot").show();
            } else {
                $("#accept-ship-proof-slot").hide();
            }
            $("#accept_note").val(res.accept_note || "");
            // Penerima ACC = user login, dikunci (tidak bisa diganti)
            var $recv = $("#accept_receiver_id");
            $recv.empty();
            if (staff.id && staff.name) {
                fillSelectOption($recv, staff.id, staff.name);
            }
            $recv.prop("disabled", true);
            if ($recv.hasClass("select2-hidden-accessible")) {
                $recv.trigger("change.select2");
            }
            renderAcceptItems(res.items || []);
            // Tolak terima: selalu hide dulu (kode tetap ada)
            $("#accept_stock_transfer .btn-reject-accept-transfer")
                .addClass("d-none")
                .removeClass("d-inline-flex");
            $("#accept_stock_transfer").modal("show");
        },
        error: function () {
            if (typeof toastr !== "undefined") toastr.error("", "Gagal memuat detail");
        },
    });
});

$(document).on("hidden.bs.modal", "#accept_stock_transfer", function () {
    $("#accept_receiver_id").prop("disabled", true);
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
    var staff = window.currentStaff || {};
    var receiverId = staff.id || $("#accept_receiver_id").val();
    if (!receiverId) {
        if (typeof toastr !== "undefined") toastr.warning("", "User login tidak ditemukan");
        return;
    }
    showTransferModalKonfirmasi(
        "Konfirmasi terima stock transfer ini? Stok gudang tujuan akan bertambah sesuai qty kirim.",
        "btn-confirm-accept-stock-transfer",
        id,
        "#accept_stock_transfer"
    );
});

$(document).on("click", "#btn-confirm-accept-stock-transfer", function () {
    var $confirmBtn = $(this);
    if ($confirmBtn.data("busy")) return;
    var id =
        $confirmBtn.attr("data-id") || $("#modalKonfirmasi").attr("data-transfer-id");
    if (!id) return;
    $confirmBtn.data("busy", true);
    LoadingButton($confirmBtn);
    submitAcceptStockTransfer(id, $confirmBtn);
});

function submitAcceptStockTransfer(id, $loadingBtn) {
    if (!id) {
        if (typeof toastr !== "undefined") toastr.error("", "ID transfer tidak ditemukan");
        return;
    }
    var staff = window.currentStaff || {};
    var receiverId = staff.id || $("#accept_receiver_id").val();
    if (!receiverId) {
        if ($loadingBtn && $loadingBtn.length) {
            $loadingBtn.data("busy", false);
            if (typeof ResetLoadingButton === "function") {
                ResetLoadingButton(
                    $loadingBtn,
                    '<i class="fe fe-check-circle me-1"></i>Konfirmasi'
                );
            }
        }
        if (typeof toastr !== "undefined") toastr.warning("", "User login tidak ditemukan");
        return;
    }
    var items = $("#accept_stock_transfer").data("accept-items") || [];
    var payloadItems = [];
    $("#tableAcceptItems tbody tr[data-std-id]").each(function () {
        var stdId = $(this).attr("data-std-id");
        var qtyKirimRow = parseFloat($(this).attr("data-qty-kirim") || 0);
        var qty = parseInt(String($(this).find(".accept-qty").val() || "").replace(/[^\d]/g, ""), 10);
        if (isNaN(qty) || qty < 0) qty = 0;
        if (qty <= 0 && qtyKirimRow > 0) qty = Math.round(qtyKirimRow);
        payloadItems.push({
            std_id: stdId,
            qty_received_sent_unit: qty,
        });
    });
    if (!payloadItems.length && items.length) {
        payloadItems = items.map(function (it) {
            return {
                std_id: it.std_id,
                qty_received_sent_unit: it.qty,
            };
        });
    }

    var fromConfirm = !!($loadingBtn && $loadingBtn.length);
    var $btn = fromConfirm
        ? $loadingBtn
        : $("#accept_stock_transfer .btn-accept-transfer");
    if (!fromConfirm) {
        if ($btn.data("busy")) return;
        $btn.data("busy", true);
        if (typeof LoadingButton === "function") LoadingButton($btn);
    }
    var idleLabel = fromConfirm
        ? '<i class="fe fe-check-circle me-1"></i>Konfirmasi'
        : '<i class="fe fe-check-circle me-1"></i>Terima';

    $.ajax({
        url: "/accStockTransfer",
        method: "post",
        data: {
            id: id,
            receiver_id: receiverId,
            accept_note: $("#accept_note").val(),
            items: payloadItems,
            _token: token || $('meta[name="csrf-token"]').attr("content"),
        },
        success: function (res) {
            $btn.data("busy", false);
            if (typeof ResetLoadingButton === "function") {
                ResetLoadingButton($btn, idleLabel);
            }
            if (!res || res.status != 1) {
                if (typeof toastr !== "undefined") {
                    toastr.error("", (res && res.message) || "Gagal ACC");
                }
                return;
            }
            markTransferOverlayDone();
            if (typeof closeModalConfirm === "function") closeModalConfirm();
            if (typeof toastr !== "undefined") toastr.success("", res.message || "Berhasil ACC");
            $("#accept_stock_transfer").modal("hide");
            if (table) table.ajax.reload(null, false);
        },
        error: function () {
            $btn.data("busy", false);
            if (typeof ResetLoadingButton === "function") {
                ResetLoadingButton($btn, idleLabel);
            }
            if (typeof toastr !== "undefined") toastr.error("", "Gagal ACC stock transfer");
        },
    });
}
