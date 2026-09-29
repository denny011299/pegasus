/**
 * Production Planning — list lokal dulu; form produk/satuan/staff dari sistem.
 * Layout: custom-premium-tabs + card-table + DT (tinggi tetap saat kosong).
 */
var ppTable = null;
var ppTableReady = false;
var ppFilterDateStart = null;
var ppFilterDateEnd = null;

/** Data planning dari API (page cache ringan untuk stage cards). */
var PP_DATA = [];
var PP_SKALA_OPTIONS = [];
var ppApprovePlanningId = null;
var ppWorkOrderPlanningId = null;
var ppViewPlanningId = null;
var ppLoadXhr = null;

/** Polling realtime ringan — skip saat modal/klik/loading. */
var ppLiveStamp = null;
var ppLiveXhr = null;
var ppLiveTimer = null;

/** Histori produksi selesai. */
var ppHistoriTable = null;
var ppHistoriTableReady = false;
var ppHistoriFilterDateStart = null;
var ppHistoriFilterDateEnd = null;
var ppHistoriLoadXhr = null;

/** Job Order Slip — work_order / inprod. */
var ppJobTable = null;
var ppJobTableReady = false;
var ppJobFilterDateStart = null;
var ppJobFilterDateEnd = null;
var ppJobLoadXhr = null;

/** ACC Bahan — STB menunggu Ops/QC. */
var ppBahanTable = null;
var ppBahanTableReady = false;
var ppBahanLoadXhr = null;

/** State form Buat PP (multi-item, manual saja — shortage auto via API). */
var ppFormItems = [];

function ppRowDateIso(r) {
    if (r.date_iso) return r.date_iso;
    if (!r.date || typeof moment !== "function") return null;
    var m = moment(r.date, ["D MMM YYYY", "DD MMM YYYY", "YYYY-MM-DD", "DD/MM/YYYY"], true);
    return m.isValid() ? m.format("YYYY-MM-DD") : null;
}

/** Senin ISO → hari ini (Minggu Ini). */
function ppThisWeekRange() {
    return {
        start: moment().startOf("isoWeek"),
        end: moment().startOf("day"),
    };
}

/** Default bersama semua tab: awal bulan → hari ini. */
function ppThisMonthToTodayRange() {
    return {
        start: moment().startOf("month"),
        end: moment().startOf("day"),
    };
}

function ppDefaultSharedRange() {
    return ppThisMonthToTodayRange();
}

/** Paint satu input daterangepicker dari YYYY-MM-DD. */
function ppPaintDateInput($date, startYmd, endYmd) {
    if (!$date || !$date.length || typeof moment !== "function") return;
    if (!startYmd || !endYmd) {
        $date.val("");
        return;
    }
    var startM = moment(startYmd, "YYYY-MM-DD");
    var endM = moment(endYmd, "YYYY-MM-DD");
    if (!startM.isValid() || !endM.isValid()) {
        $date.val("");
        return;
    }
    $date.val(startM.format("DD-MM-YYYY") + " — " + endM.format("DD-MM-YYYY"));
    var drp = $date.data("daterangepicker");
    if (drp) {
        drp.setStartDate(startM.clone());
        drp.setEndDate(endM.clone());
    }
}

/**
 * Satu sumber kebenaran tanggal untuk Planning / Job / Histori.
 * Ganti di satu tab → semua input + state ikut.
 */
function ppSetSharedDateRange(startYmd, endYmd, opts) {
    opts = opts || {};
    ppFilterDateStart = startYmd || null;
    ppFilterDateEnd = endYmd || null;
    ppJobFilterDateStart = startYmd || null;
    ppJobFilterDateEnd = endYmd || null;
    ppHistoriFilterDateStart = startYmd || null;
    ppHistoriFilterDateEnd = endYmd || null;

    ppPaintDateInput($("#pp_filter_date"), startYmd, endYmd);
    ppPaintDateInput($("#pp_job_filter_date"), startYmd, endYmd);
    ppPaintDateInput($("#pp_histori_filter_date"), startYmd, endYmd);

    if (opts.reload === false) return;

    if (typeof refreshPpTable === "function" && ppTable) refreshPpTable(!!opts.soft);
    if (typeof refreshPpJobTable === "function" && ppJobTable) refreshPpJobTable(!!opts.soft);
    if (typeof refreshPpHistoriTable === "function" && ppHistoriTable) {
        refreshPpHistoriTable(!!opts.soft);
    }
    if (typeof refreshPpStageMiniTables === "function") {
        window._ppStageMiniLoaded = false;
        refreshPpStageMiniTables();
    }
    if (typeof ppSyncJobViewUrl === "function" && ppJobTabIsActive && ppJobTabIsActive()) {
        ppSyncJobViewUrl();
    } else if (typeof ppReplacePageUrl === "function") {
        ppReplacePageUrl();
    }
}

function ppApplyDefaultSharedDate(opts) {
    var w = ppDefaultSharedRange();
    ppSetSharedDateRange(
        w.start.format("YYYY-MM-DD"),
        w.end.format("YYYY-MM-DD"),
        opts || {}
    );
}

function ppApplyWeekToDateInput($date, onStart, onEnd) {
    if (!$date || !$date.length || typeof moment !== "function") return;
    var w = ppThisWeekRange();
    onStart(w.start.format("YYYY-MM-DD"));
    onEnd(w.end.format("YYYY-MM-DD"));
    ppPaintDateInput($date, w.start.format("YYYY-MM-DD"), w.end.format("YYYY-MM-DD"));
}

function ppApplyMonthToDateInput($date, onStart, onEnd) {
    if (!$date || !$date.length || typeof moment !== "function") return;
    var w = ppThisMonthToTodayRange();
    onStart(w.start.format("YYYY-MM-DD"));
    onEnd(w.end.format("YYYY-MM-DD"));
    ppPaintDateInput($date, w.start.format("YYYY-MM-DD"), w.end.format("YYYY-MM-DD"));
}

function clearPpDateFilter() {
    ppApplyDefaultSharedDate({ reload: false });
}

/** Ranges + locale daterangepicker — sama di semua tab. */
function ppDatePickerRanges() {
    return {
        "Hari Ini": [moment().startOf("day"), moment().startOf("day")],
        Kemarin: [
            moment().subtract(1, "days").startOf("day"),
            moment().subtract(1, "days").startOf("day"),
        ],
        "Minggu Ini": [moment().startOf("isoWeek"), moment().startOf("day")],
        "7 Hari Terakhir": [
            moment().subtract(6, "days").startOf("day"),
            moment().startOf("day"),
        ],
        "Bulan Ini": [moment().startOf("month"), moment().startOf("day")],
        "Bulan Lalu": [
            moment().subtract(1, "month").startOf("month"),
            moment().subtract(1, "month").endOf("month"),
        ],
    };
}

function ppDatePickerLocale() {
    return {
        format: "DD-MM-YYYY",
        separator: " — ",
        applyLabel: "Terapkan",
        cancelLabel: "Reset",
        fromLabel: "Dari",
        toLabel: "Sampai",
        customRangeLabel: "Kustom",
        daysOfWeek: ["Mg", "Sn", "Sl", "Rb", "Km", "Jm", "Sb"],
        monthNames: [
            "Januari", "Februari", "Maret", "April", "Mei", "Juni",
            "Juli", "Agustus", "September", "Oktober", "November", "Desember",
        ],
        firstDay: 1,
    };
}

function ppBindSharedDatePicker($date) {
    if (!$date || !$date.length) return;
    if (typeof $date.daterangepicker !== "function" || typeof moment !== "function") return;

    var hasState = !!(ppFilterDateStart && ppFilterDateEnd);
    if (!hasState) {
        var def = ppDefaultSharedRange();
        ppFilterDateStart = def.start.format("YYYY-MM-DD");
        ppFilterDateEnd = def.end.format("YYYY-MM-DD");
        ppJobFilterDateStart = ppFilterDateStart;
        ppJobFilterDateEnd = ppFilterDateEnd;
        ppHistoriFilterDateStart = ppFilterDateStart;
        ppHistoriFilterDateEnd = ppFilterDateEnd;
    }
    var startM = moment(ppFilterDateStart, "YYYY-MM-DD");
    var endM = moment(ppFilterDateEnd, "YYYY-MM-DD");
    if (!startM.isValid() || !endM.isValid()) {
        var m = ppDefaultSharedRange();
        startM = m.start;
        endM = m.end;
        ppSetSharedDateRange(
            startM.format("YYYY-MM-DD"),
            endM.format("YYYY-MM-DD"),
            { reload: false }
        );
    }

    $date.daterangepicker({
        autoUpdateInput: false,
        autoApply: false,
        alwaysShowCalendars: true,
        showDropdowns: true,
        startDate: startM,
        endDate: endM,
        locale: ppDatePickerLocale(),
        ranges: ppDatePickerRanges(),
    });

    $date.off(
        "apply.daterangepicker.ppShared cancel.daterangepicker.ppShared show.daterangepicker.ppShared hide.daterangepicker.ppShared"
    );
    $date.on("apply.daterangepicker.ppShared", function (ev, picker) {
        ppSetSharedDateRange(
            picker.startDate.format("YYYY-MM-DD"),
            picker.endDate.format("YYYY-MM-DD"),
            { soft: true }
        );
    });
    $date.on("cancel.daterangepicker.ppShared", function () {
        ppApplyDefaultSharedDate({ soft: true });
    });
    $date.on("show.daterangepicker.ppShared hide.daterangepicker.ppShared", function () {
        ppPaintDateInput($date, ppFilterDateStart, ppFilterDateEnd);
    });

    ppPaintDateInput($date, ppFilterDateStart, ppFilterDateEnd);
}

function getPpFilterState() {
    var productSku = "";
    var productVariantId = "";
    var $prod = $("#pp_filter_product");
    if ($prod.length) {
        productVariantId = String($prod.val() || "");
        try {
            if ($prod.hasClass("select2-hidden-accessible")) {
                var pdata = ($prod.select2("data") || [])[0];
                if (pdata) {
                    productSku = String(
                        pdata.product_variant_sku || pdata.sku || ""
                    ).toLowerCase();
                }
            }
        } catch (e) { /* select2 belum siap */ }
    }
    var supervisorName = "";
    var $spv = $("#pp_filter_supervisor");
    if ($spv.length) {
        try {
            if ($spv.hasClass("select2-hidden-accessible")) {
                var sdata = ($spv.select2("data") || [])[0];
                supervisorName = sdata
                    ? String(sdata.text || sdata.staff_name || "").toLowerCase()
                    : "";
            } else {
                supervisorName = String($spv.val() || "").toLowerCase();
            }
        } catch (e) {
            supervisorName = String($spv.val() || "").toLowerCase();
        }
    }
    return {
        status: $("#pp_filter_status").length
            ? $("#pp_filter_status").val() || ""
            : "",
        product: productSku,
        product_variant_id: productVariantId,
        supervisor: supervisorName,
        dateFrom: ppFilterDateStart
            ? typeof ppFilterDateStart === "string"
                ? ppFilterDateStart
                : ppFilterDateStart.format("YYYY-MM-DD")
            : "",
        dateTo: ppFilterDateEnd
            ? typeof ppFilterDateEnd === "string"
                ? ppFilterDateEnd
                : ppFilterDateEnd.format("YYYY-MM-DD")
            : "",
    };
}

function buildPpQueryString() {
    var q = new URLSearchParams();
    // Tanggal selalu shared (satu value untuk semua tab).
    if (ppFilterDateStart && ppFilterDateEnd) {
        q.set("date_from", ppFilterDateStart);
        q.set("date_to", ppFilterDateEnd);
    }
    if (typeof ppJobTabIsActive === "function" ? ppJobTabIsActive() : $("#pp-tab-job").hasClass("active")) {
        q.set("tab", "job");
        var st = $("#pp_job_filter_status").val() || "";
        q.set("status", st);
        var ppid = $("#pp_job_filter_product").val();
        if (ppid) q.set("product_variant_id", ppid);
        var jsid = $("#pp_job_filter_supervisor").val();
        if (jsid) q.set("pic_staff_id", jsid);
    } else if ($("#pp-tab-bahan").hasClass("active") || $("#pp-pane-bahan").hasClass("show")) {
        q.set("tab", "bahan");
        var bst = $("#pp_bahan_filter_stage").val() || "";
        if (bst) q.set("status", bst);
    } else if ($("#pp-tab-histori").hasClass("active") || $("#pp-pane-histori").hasClass("show")) {
        q.set("tab", "histori");
        var hpid = $("#pp_histori_filter_product").val();
        if (hpid) q.set("product_variant_id", hpid);
        var hsid = $("#pp_histori_filter_supervisor").val();
        if (hsid) q.set("pic_staff_id", hsid);
    } else {
        // Default: Daftar Planning
        q.set("tab", "planning");
        var f = getPpFilterState();
        q.set("status", f.status || "");
        if (f.product_variant_id) q.set("product_variant_id", f.product_variant_id);
        var sid = $("#pp_filter_supervisor").val();
        if (sid) q.set("pic_staff_id", sid);
    }
    return q.toString();
}

/** URL halaman yang sedang dibuka (normal tetap normal; view tetap view). */
function buildPpPageUrl() {
    var base =
        typeof window.ppPageBaseUrl === "string" && window.ppPageBaseUrl
            ? window.ppPageBaseUrl
            : "/productionPlanning";
    var qs = buildPpQueryString();
    return qs ? base + "?" + qs : base;
}

/** URL mode View fullscreen — hanya untuk window.open tab baru. */
function buildPpViewUrl() {
    var base =
        typeof window.ppViewBaseUrl === "string" && window.ppViewBaseUrl
            ? window.ppViewBaseUrl
            : "/productionPlanning/view";
    var qs = buildPpQueryString();
    return qs ? base + "?" + qs : base;
}

/** Sync query ke address bar tanpa pindah mode (jangan rewrite ke /view). */
function ppReplacePageUrl() {
    try {
        if (window.history && window.history.replaceState) {
            window.history.replaceState({}, "", buildPpPageUrl());
        }
    } catch (e) { /* ignore */ }
}

/** Baca query string View ke kontrol filter (id Select2, bukan label teks). */
function applyPpFiltersFromUrl() {
    var p = new URLSearchParams(window.location.search || "");
    if (!p.toString()) return;

    var tab = p.get("tab");
    var df = p.get("date_from");
    var dt = p.get("date_to");
    var vid = p.get("product_variant_id");
    var sid = p.get("pic_staff_id");
    var status = p.has("status") ? p.get("status") : "";

    // Tanggal URL → shared ke semua tab.
    if (df && dt && typeof moment === "function") {
        var start = moment(df, "YYYY-MM-DD", true);
        var end = moment(dt, "YYYY-MM-DD", true);
        if (start.isValid() && end.isValid()) {
            ppSetSharedDateRange(
                start.format("YYYY-MM-DD"),
                end.format("YYYY-MM-DD"),
                { reload: false }
            );
        }
    }

    if (tab === "job") {
        if ($("#pp_job_filter_status").length) {
            $("#pp_job_filter_status").val(status);
        }
        if (vid && $("#pp_job_filter_product").length) {
            ppEnsureSelect2Value("#pp_job_filter_product", vid, p.get("product_label") || vid);
        }
        if (sid && $("#pp_job_filter_supervisor").length) {
            ppEnsureSelect2Value("#pp_job_filter_supervisor", sid, p.get("pic_label") || sid);
        }
    } else if (tab === "bahan") {
        if ($("#pp_bahan_filter_stage").length && status) {
            $("#pp_bahan_filter_stage").val(status);
        }
    } else if (tab === "histori") {
        if (vid && $("#pp_histori_filter_product").length) {
            ppEnsureSelect2Value("#pp_histori_filter_product", vid, p.get("product_label") || vid);
        }
        if (sid && $("#pp_histori_filter_supervisor").length) {
            ppEnsureSelect2Value("#pp_histori_filter_supervisor", sid, p.get("pic_label") || sid);
        }
    } else {
        if ($("#pp_filter_status").length) {
            $("#pp_filter_status").val(status);
        }
        if (vid && $("#pp_filter_product").length) {
            ppEnsureSelect2Value("#pp_filter_product", vid, p.get("product_label") || vid);
        }
        if (sid && $("#pp_filter_supervisor").length) {
            ppEnsureSelect2Value("#pp_filter_supervisor", sid, p.get("pic_label") || sid);
        }
    }
}

/** Select2: pastikan option ada sebelum set value (fullscreen URL restore). */
function ppEnsureSelect2Value(sel, id, text) {
    var $el = $(sel);
    if (!$el.length || !id) return;
    if ($el.find('option[value="' + String(id).replace(/"/g, '\\"') + '"]').length === 0) {
        var opt = new Option(text || String(id), String(id), true, true);
        $el.append(opt);
    }
    $el.val(String(id)).trigger("change");
}

function ppInitials(name) {
    if (!name) return "—";
    var parts = name.trim().split(/\s+/);
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name.slice(0, 2).toUpperCase();
}

function ppStatusBadge(status) {
    var map = {
        draft: { cls: "draft", label: "Draft" },
        released: { cls: "released", label: "Released to Production" },
        work_order: { cls: "work_order", label: "Work Order" },
        inprod: { cls: "inprod", label: "In Production" },
        done: { cls: "done", label: "Completed" },
    };
    var s = map[status] || map.draft;
    return '<span class="pp-status ' + s.cls + '">' + s.label + "</span>";
}

/** Badge progress WO: hijau = semua selesai, kuning = masih jalan, abu = belum ada WO. */
function ppWoProgressBadge(r) {
    var total = Number((r && r.wo_total) || 0);
    if (!total) {
        return '<span class="text-muted small">—</span>';
    }
    var done = Number((r && r.wo_done) || 0);
    var allDone = done >= total;
    var bg = allDone ? "#dcfce7" : "#fef9c3";
    var fg = allDone ? "#166534" : "#854d0e";
    var bd = allDone ? "#bbf7d0" : "#fde68a";
    return (
        '<span class="badge" style="background:' +
        bg +
        ";color:" +
        fg +
        ";border:1px solid " +
        bd +
        ';padding:5px 10px;border-radius:20px;font-weight:600;font-size:11px;white-space:nowrap;" title="Work Order selesai / total">' +
        done +
        "/" +
        total +
        " WO</span>"
    );
}

/** Kolom Sumber: PMO = Form Kekurangan (API), IPM = buat manual di ERP. */
function ppSourceBadge(r) {
    var fromPmo = !!r.from_shipment || !!r.shipment_shortage_document_id;
    if (fromPmo) {
        return (
            '<span class="badge" style="background:#fef3c7;color:#b45309;border:1px solid #fde68a;padding:5px 10px;border-radius:20px;font-weight:600;font-size:11px;" title="Dari Form Kekurangan Pengiriman (API PMO)">' +
            '<i class="fe fe-alert-triangle me-1"></i>PMO</span>'
        );
    }
    return (
        '<span class="badge" style="background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;padding:5px 10px;border-radius:20px;font-weight:600;font-size:11px;" title="Dibuat manual di IPM">' +
        "IPM</span>"
    );
}

function ppPlaceholderImg() {
    return (
        "data:image/svg+xml," +
        encodeURIComponent(
            '<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 36 36"><rect fill="#f1f5f9" width="36" height="36" rx="8"/><path d="M12 25l4-5 3 3 5-7 5 9H12z" fill="#cbd5e1"/><circle cx="16" cy="14" r="2.5" fill="#94a3b8"/></svg>'
        )
    );
}

function ppFormatNumber(n) {
    return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

/** Qty + satuan font sama; multi satuan ditumpuk (pola stok). */
function ppQtyUnitHtml(r) {
    var lines = Array.isArray(r.qty_by_unit) ? r.qty_by_unit : null;
    if (!lines || !lines.length) {
        lines = [{ qty: r.qty, unit: r.unit && r.unit !== "multi" ? r.unit : "—" }];
    }
    return lines
        .map(function (line) {
            return (
                '<div class="pp-qty-line">' +
                '<span class="pp-qty-num">' +
                ppFormatNumber(line.qty) +
                "</span>" +
                '<span class="pp-qty-unit">' +
                $("<div>").text(line.unit || "—").html() +
                "</span></div>"
            );
        })
        .join("");
}

function ppItemCountHtml(r) {
    var n = Number(r.item_count);
    if (!n || n < 1) {
        n = r.items && r.items.length ? r.items.length : 1;
    }
    return (
        '<span class="pp-item-count">' +
        ppFormatNumber(n) +
        " Item</span>"
    );
}

function showPpSkeleton() {
    var $wrap = $("#tablePpPlanning-wrap");
    if (!ppTableReady || !ppTable) {
        $wrap.removeClass("dt-ready is-loading").addClass("dt-pending");
    } else {
        $wrap.removeClass("dt-pending").addClass("dt-ready is-loading");
    }
}

function hidePpSkeleton() {
    ppTableReady = true;
    $("#tablePpPlanning-wrap")
        .removeClass("dt-pending is-loading")
        .addClass("dt-ready");
}

/** Satu tombol cetak SPK (Surat Perintah Kerja) — level planning, bukan WO. */
function ppSpkPrintIcon(r) {
    var status = r.status || r.pp_status;
    if (["released", "work_order", "inprod", "done"].indexOf(status) === -1) {
        return "";
    }
    var id = Number(r.production_planning_id || 0);
    if (!id) return "";
    return (
        '<a href="/printProductionPlanning/' +
        id +
        '" target="_blank" rel="noopener" class="btn-action-icon" title="Cetak Surat Perintah Kerja">' +
        '<i class="fe fe-printer"></i></a>'
    );
}

function ppWorkOrderPrintIcons(r) {
    // Alias lama → hanya SPK (jangan cetak WO di daftar planning)
    return ppSpkPrintIcon(r);
}

function ppSkalaOptionLabel(sk) {
    var code = String((sk && sk.code) || "").trim();
    var name = String((sk && sk.name) || "").trim();
    if (code && name) return code + " | " + name;
    if (sk && sk.label) return String(sk.label);
    return code || name || "—";
}

function mapPpRows(rows) {
    return rows.map(function (r) {
        return {
            code:
                '<span class="pp-cell-text pp-cell-code">' +
                $("<div>").text(r.code).html() +
                "</span>",
            date:
                '<span class="pp-cell-text">' +
                $("<div>").text(r.date).html() +
                "</span>",
            product_html: ppItemCountHtml(r),
            qty: ppQtyUnitHtml(r),
            status_html: ppStatusBadge(r.status),
            wo_html: ppWoProgressBadge(r),
            source_html: ppSourceBadge(r),
            created_by:
                '<span class="pp-cell-text">' +
                $("<div>").text(r.created_by).html() +
                "</span>",
            action: (function () {
                var id = r.production_planning_id || "";
                var st = r.status || r.pp_status || "";
                var prints =
                    st === "released" || st === "inprod" || st === "work_order" || st === "done"
                        ? ppWorkOrderPrintIcons(r)
                        : "";
                var canDelete = !!r.can_delete;
                var fromShip = !!r.from_shipment || !!r.shipment_shortage_document_id;
                var delTitle = canDelete
                    ? "Hapus Planning"
                    : fromShip
                      ? "Tidak dapat dihapus: dari Form Kekurangan Pengiriman"
                      : "Tidak dapat dihapus";
                var delBtn = canDelete
                    ? '<a href="javascript:void(0);" class="btn-action-icon btn_delete btn-pp-delete" data-id="' +
                      id +
                      '" data-code="' +
                      $("<div>").text(r.code || "").html() +
                      '" title="' +
                      delTitle +
                      '"><i class="fe fe-trash-2"></i></a>'
                    : '<a href="javascript:void(0);" class="btn-action-icon btn_delete btn-pp-delete is-disabled" aria-disabled="true" tabindex="-1" title="' +
                      delTitle +
                      '"><i class="fe fe-trash-2"></i></a>';
                return (
                    '<div class="d-flex align-items-center justify-content-center gap-1">' +
                    '<a href="javascript:void(0);" class="btn-action-icon btn-pp-view" data-id="' +
                    id +
                    '" title="Lihat Detail"><i class="fe fe-eye"></i></a>' +
                    prints +
                    delBtn +
                    "</div>"
                );
            })(),
        };
    });
}

/** Update badge tab (partial OK). Badge = jumlah hasil filter tabel tab itu. */
function applyPpTabBadges(tabs) {
    if (!tabs) return;
    Object.keys(tabs).forEach(function (key) {
        if (tabs[key] == null) return;
        var n = Number(tabs[key] || 0);
        var $b = $('[data-pp-tab-badge="' + key + '"]');
        if (!$b.length) return;
        $b.text(n > 99 ? "99+" : String(n));
        $b.toggleClass("is-empty", n <= 0);
    });
}

/** Jangan ganggu user yang sedang interaksi / modal / ajax loading. */
function ppLiveBusyUi() {
    if (document.hidden) return true;
    if ($(".modal.show").length) return true;
    if ($(".select2-container--open").length) return true;
    if ($(".daterangepicker:visible").length) return true;
    if (
        $("#tablePpPlanning-wrap.is-loading, #tablePpJob-wrap.is-loading, #tablePpBahan-wrap.is-loading, #tablePpHistori-wrap.is-loading")
            .length
    ) {
        return true;
    }
    if (typeof woBusy !== "undefined" && woBusy) return true;
    return false;
}

function ppLiveSoftReloadActive() {
    var active = $(".tab-pane.active").attr("id") || $(".tab-pane.show.active").attr("id");
    if (active === "pp-pane-job") {
        refreshPpJobTable(true);
    } else if (active === "pp-pane-bahan") {
        if (typeof refreshPpBahanTable === "function") refreshPpBahanTable(true);
    } else if (active === "pp-pane-histori") {
        refreshPpHistoriTable(true);
    } else {
        refreshPpTable(true);
    }
    if (typeof refreshPpStageMiniTables === "function") {
        refreshPpStageMiniTables();
    }
}

function ppLiveTick() {
    if (ppLiveBusyUi()) return;
    if (ppLiveXhr && ppLiveXhr.readyState !== 4) return;
    ppLiveXhr = $.ajax({
        url: "/getProductionPlanningLive",
        method: "get",
        cache: false,
        success: function (res) {
            if (!res) return;
            // Polling: hanya badge ACC Bahan + stamp. KPI ikut filter dari reload tabel aktif.
            if (res.tabs && res.tabs.bahan != null) {
                applyPpTabBadges({ bahan: res.tabs.bahan });
            } else if (res.counts && res.counts.bahan_pending != null) {
                applyPpTabBadges({ bahan: res.counts.bahan_pending });
            }
            var stamp = res.stamp || "";
            if (ppLiveStamp === null) {
                ppLiveStamp = stamp;
                return;
            }
            if (stamp === ppLiveStamp) return;
            ppLiveStamp = stamp;
            if (ppLiveBusyUi()) return;
            ppLiveSoftReloadActive();
        },
        error: function () {
            /* diam — polling berikutnya */
        },
    });
}

function startPpLivePolling() {
    if (ppLiveTimer) return;
    ppLiveTick();
    ppLiveTimer = setInterval(ppLiveTick, 4000);
    document.addEventListener("visibilitychange", function () {
        if (!document.hidden) ppLiveTick();
    });
}

/**
 * KPI kartu atas (global, tanpa filter tanggal).
 * Badge nav-tab JANGAN diisi dari sini — ikut recordsFiltered tiap tabel
 * supaya sinkron dengan filter. Hanya badge "bahan" dari live (belum filter tanggal).
 */
function applyPpCounts(counts, tabs) {
    if (!counts) return;
    $('[data-pp-sum="draft"]').text(ppFormatNumber(counts.draft || 0));
    $('[data-pp-sum="released"]').text(ppFormatNumber(counts.released || 0));
    var woKpi =
        counts.wo_active != null
            ? counts.wo_active
            : (counts.work_order || 0) + (counts.inprod || 0);
    $('[data-pp-sum="work_order"]').text(ppFormatNumber(woKpi));
    $('[data-pp-sum="inprod"]').text(ppFormatNumber(counts.inprod || 0));
    $('[data-pp-sum="done"]').text(ppFormatNumber(counts.done || 0));
    $('[data-pp-sum="rekomendasi"]').text(
        ppFormatNumber(
            (counts.draft || 0) +
                (counts.released || 0) +
                (counts.work_order || 0) +
                (counts.inprod || 0) +
                (counts.done || 0)
        )
    );
    // Bahan: antrian ACC global (tidak ikut filter tanggal Job/Planning)
    var bahan =
        tabs && tabs.bahan != null
            ? tabs.bahan
            : counts.bahan_pending != null
              ? counts.bahan_pending
              : null;
    if (bahan != null) applyPpTabBadges({ bahan: bahan });
}

function getPpListExtraParams(stage) {
    var param = { stage: stage || "planning" };
    if (stage === "planning") {
        var f = getPpFilterState();
        if (f.status) param.pp_status = f.status;
        if (f.dateFrom) param.date_from = f.dateFrom;
        if (f.dateTo) param.date_to = f.dateTo;
        if (f.product_variant_id) param.product_variant_id = f.product_variant_id;
        var sid = $("#pp_filter_supervisor").val();
        if (sid) param.pic_staff_id = sid;
    } else if (stage === "job") {
        var st = $("#pp_job_filter_status").val() || "";
        if (st) param.pp_status = st;
        if (ppJobFilterDateStart) param.date_from = ppJobFilterDateStart;
        if (ppJobFilterDateEnd) param.date_to = ppJobFilterDateEnd;
        var $prod = $("#pp_job_filter_product");
        if ($prod.length && $prod.val()) param.product_variant_id = $prod.val();
        var $spv = $("#pp_job_filter_supervisor");
        if ($spv.length && $spv.val()) param.pic_staff_id = $spv.val();
    } else if (stage === "histori") {
        if (ppHistoriFilterDateStart) param.date_from = ppHistoriFilterDateStart;
        if (ppHistoriFilterDateEnd) param.date_to = ppHistoriFilterDateEnd;
        var $hp = $("#pp_histori_filter_product");
        if ($hp.length && $hp.val()) param.product_variant_id = $hp.val();
        var $hs = $("#pp_histori_filter_supervisor");
        if ($hs.length && $hs.val()) param.pic_staff_id = $hs.val();
    }
    return param;
}

function refreshPpTable(soft) {
    if (!ppTable) return;
    window._ppStageMiniLoaded = false;
    if (soft) {
        $("#tablePpPlanning-wrap").removeClass("dt-pending").addClass("dt-ready is-loading");
    } else {
        showPpSkeleton();
    }
    ppTable.ajax.reload(null, false);
}

function refreshPpJobTable(soft) {
    if (!$("#tablePpJob").length || !ppJobTable) return;
    if (soft) {
        $("#tablePpJob-wrap").removeClass("dt-pending").addClass("dt-ready is-loading");
    } else {
        showPpJobSkeleton();
    }
    ppJobTable.ajax.reload(null, false);
}

function refreshPpHistoriTable(soft) {
    if (!$("#tablePpHistori").length || !ppHistoriTable) return;
    if (soft) {
        $("#tablePpHistori-wrap").removeClass("dt-pending").addClass("dt-ready is-loading");
    } else {
        showPpHistoriSkeleton();
    }
    ppHistoriTable.ajax.reload(null, false);
}

function refreshPpStageMiniTables() {
    // Satu request untuk 4 kartu stage; ikut filter Planning biar sync.
    var f = typeof getPpFilterState === "function" ? getPpFilterState() : {};
    $.ajax({
        url: "/getProductionPlanningStageCards",
        method: "get",
        cache: false,
        data: {
            date_from: f.dateFrom || "",
            date_to: f.dateTo || "",
            product_variant_id: f.product_variant_id || "",
            pic_staff_id: $("#pp_filter_supervisor").val() || "",
        },
        success: function (res) {
            var cards = (res && res.cards) || {};
            var specs = [
                { status: "released", table: "tablePpReleased", wrap: "tablePpReleased-wrap", count: "countPpReleased" },
                { status: "work_order", table: "tablePpWorkOrder", wrap: "tablePpWorkOrder-wrap", count: "countPpWorkOrder" },
                { status: "inprod", table: "tablePpProduksi", wrap: "tablePpProduksi-wrap", count: "countPpProduksi" },
                { status: "done", table: "tablePpSelesai", wrap: "tablePpSelesai-wrap", count: "countPpSelesai" },
            ];
            specs.forEach(function (s) {
                var card = cards[s.status] || {};
                var rows = Array.isArray(card.data) ? card.data : [];
                try {
                    initPpStageTable(s.table, s.wrap, rows);
                    $("#" + s.count).text((card.total != null ? card.total : rows.length) + " Data");
                } catch (e) { /* ignore */ }
            });
        },
    });
}

function refreshPpStageTablesFromData() {
    refreshPpStageMiniTables();
}

function inisialisasiPpFilters() {
    // Sama Stock Transfer: dropdownParent "body"
    if (typeof autocompleteProductVariantOnly === "function") {
        autocompleteProductVariantOnly("#pp_filter_product", "body");
    }
    if (typeof autocompleteStaff === "function") {
        autocompleteStaff("#pp_filter_supervisor", "body");
    }

    $(document)
        .off("change.ppFilter", "#pp_filter_status, #pp_filter_product, #pp_filter_supervisor")
        .on(
            "change.ppFilter",
            "#pp_filter_status, #pp_filter_product, #pp_filter_supervisor",
            refreshPpTable
        );
}

function inisialisasiPp() {
    inisialisasiPpFilters();
    initPpDateFilter();

    applyPpFiltersFromUrl();
    if (!$("#pp_filter_status").val() && $("#pp_filter_status").length) {
        $("#pp_filter_status").val("");
    }

    showPpSkeleton();
    if ($.fn.DataTable.isDataTable("#tablePpPlanning")) {
        $("#tablePpPlanning").DataTable().destroy();
    }
    ppTable = $("#tablePpPlanning").DataTable({
        serverSide: true,
        processing: true,
        deferRender: true,
        paging: true,
        pageLength: 5,
        lengthMenu: [5, 10, 25, 50, 100],
        searching: false,
        info: true,
        order: [],
        sDom: "fBtlpi",
        language: {
            processing:
                '<div><span class="spinner-border spinner-border-sm me-2"></span>Memuat...</div>',
            emptyTable:
                '<div class="text-muted"><i class="fe fe-inbox d-block mb-2" style="font-size:28px;opacity:.5;"></i>Belum ada production planning<br><small>Buat planning manual atau otomatis dari Form Kekurangan (API)</small></div>',
            zeroRecords: "Tidak ada data yang cocok",
            sLengthMenu: "_MENU_",
            lengthMenu: "Tampilkan _MENU_ data",
            info: "_START_ - _END_ of _TOTAL_ items",
            infoEmpty: "Menampilkan 0 data",
            paginate: {
                next: ' <i class=" fa fa-angle-right"></i>',
                previous: '<i class="fa fa-angle-left"></i> ',
            },
        },
        ajax: function (dtData, callback) {
            if (ppLoadXhr && ppLoadXhr.readyState !== 4) ppLoadXhr.abort();
            showPpSkeleton();
            ppLoadXhr = $.ajax({
                url: "/getProductionPlanning",
                method: "get",
                data: $.extend({}, dtData, getPpListExtraParams("planning")),
                success: function (json) {
                    applyPpCounts(json.counts, json.tabs);
                    applyPpTabBadges({
                        planning: json.recordsFiltered || 0,
                        job: (json.counts && json.counts.wo_active) || 0,
                        histori: (json.counts && json.counts.done) || 0,
                        bahan: (json.counts && json.counts.bahan_pending) || 0,
                    });
                    if (json.stamp) ppLiveStamp = json.stamp;
                    PP_DATA = Array.isArray(json.data) ? json.data : [];
                    $("#pp_table_count").text(
                        (json.recordsFiltered || 0) + " Planning"
                    );
                    var qtySum = 0;
                    PP_DATA.forEach(function (r) {
                        qtySum += Number(r.qty) || 0;
                    });
                    $('[data-pp-sum="qty"]').text(ppFormatNumber(qtySum));
                    if (!window._ppStageMiniLoaded) {
                        window._ppStageMiniLoaded = true;
                        refreshPpStageMiniTables();
                    }
                    callback({
                        draw: json.draw,
                        recordsTotal: json.recordsTotal || 0,
                        recordsFiltered: json.recordsFiltered || 0,
                        data: mapPpRows(PP_DATA),
                    });
                    hidePpSkeleton();
                    if (typeof feather !== "undefined") feather.replace();
                },
                error: function (err) {
                    if (err && err.statusText === "abort") return;
                    hidePpSkeleton();
                    if (typeof handlePermissionError === "function" && handlePermissionError(err)) return;
                    console.error("Gagal load PP:", err);
                    callback({
                        draw: dtData.draw,
                        recordsTotal: 0,
                        recordsFiltered: 0,
                        data: [],
                    });
                },
            });
        },
        columns: [
            { data: "code", width: "12%" },
            { data: "date", width: "10%" },
            { data: "product_html", width: "16%", orderable: false },
            { data: "qty", width: "12%", className: "text-center" },
            { data: "status_html", width: "13%", className: "text-center", orderable: false },
            { data: "wo_html", width: "9%", className: "text-center", orderable: false },
            { data: "source_html", width: "9%", className: "text-center", orderable: false },
            { data: "created_by", width: "10%" },
            { data: "action", width: "9%", className: "text-center", orderable: false },
        ],
        initComplete: function () {
            hidePpSkeleton();
            if (typeof feather !== "undefined") feather.replace();
        },
    });

    inisialisasiPpStageTables();
    inisialisasiPpJob();
    inisialisasiPpBahan();
    inisialisasiPpHistori();
    applyPpFiltersFromUrl();
    // Setelah URL sync tanggal shared — pastikan semua tabel pakai range yang sama.
    if (ppTable) ppTable.ajax.reload(null, false);
    if (ppJobTable) ppJobTable.ajax.reload(null, false);
    if (ppHistoriTable) ppHistoriTable.ajax.reload(null, false);
    if (ppBahanTable) ppBahanTable.ajax.reload(null, false);
    window._ppStageMiniLoaded = false;
}

/** Date range — shared semua tab (default Bulan Ini → hari ini). */
function initPpDateFilter() {
    if (!$("#pp_filter_date").length) return;
    var $date = $("#pp_filter_date");
    if (typeof $date.daterangepicker !== "function" || typeof moment !== "function") {
        console.warn(
            "PP date filter: daterangepicker belum ter-load. Pastikan route productionPlanning ada di footer-scripts."
        );
        return;
    }
    ppBindSharedDatePicker($date);
}

function mapPpStageRows(rows) {
    return (rows || []).map(function (r) {
        var rawDt = r.datetime || r.date || "—";
        var dtParts = String(rawDt).split(" ");
        var dateStr = rawDt;
        var timeStr = "";
        if (dtParts.length >= 4) {
            dateStr = dtParts.slice(0, 3).join(" ");
            timeStr = dtParts[3] + " WIB";
        }
        var id = r.production_planning_id || "";
        var st = r.status || r.pp_status || "";
        var prints =
            st === "released" || st === "inprod" || st === "work_order" || st === "done"
                ? ppWorkOrderPrintIcons(r)
                : "";
        return {
            code:
                '<span class="fw-bold text-primary font-monospace" style="font-size: 12.5px;">' +
                $("<div>").text(r.code || r.pp_number || "—").html() +
                "</span>",
            datetime:
                '<div class="lh-sm">' +
                '<div class="fw-medium text-dark" style="font-size:11.5px;">' +
                $("<div>").text(dateStr).html() +
                "</div>" +
                (timeStr
                    ? '<div class="text-muted" style="font-size:10px;">' +
                      $("<div>").text(timeStr).html() +
                      "</div>"
                    : "") +
                "</div>",
            action:
                '<div class="d-flex align-items-center justify-content-end gap-1">' +
                '<a href="javascript:void(0);" class="btn-action-icon btn-pp-view" data-id="' +
                id +
                '" title="Lihat Detail"><i class="fe fe-eye"></i></a>' +
                prints +
                "</div>",
        };
    });
}

function initPpStageTable(tableId, wrapId, rows) {
    var $table = $("#" + tableId);
    var $wrap = $("#" + wrapId);
    if (!$table.length) return null;

    $wrap.removeClass("dt-ready is-loading").addClass("dt-pending");

    if ($.fn.DataTable.isDataTable("#" + tableId)) {
        $table.DataTable().destroy();
    }

    var prevNumbersLength =
        $.fn.dataTable && $.fn.dataTable.ext && $.fn.dataTable.ext.pager
            ? $.fn.dataTable.ext.pager.numbers_length
            : null;
    if (prevNumbersLength != null) {
        $.fn.dataTable.ext.pager.numbers_length = 3;
    }

    var dt = $table.DataTable({
        processing: false,
        deferRender: true,
        paging: true,
        pagingType: "simple_numbers",
        pageLength: 4,
        lengthChange: false,
        searching: false,
        info: false,
        ordering: false,
        bSort: false,
        order: [],
        sDom: "tp",
        autoWidth: false,
        language: {
            emptyTable: '<div class="text-center py-3 text-muted small"><i class="fe fe-inbox d-block mb-1" style="font-size:18px;"></i>Belum ada data</div>',
            zeroRecords: '<div class="text-center py-3 text-muted small">Tidak ada data</div>',
            paginate: {
                next: '<i class="fa fa-angle-right"></i>',
                previous: '<i class="fa fa-angle-left"></i>',
            },
        },
        columns: [
            { data: "code", width: "40%" },
            { data: "datetime", width: "32%" },
            { data: "action", width: "28%", className: "text-end", orderable: false },
        ],
        data: mapPpStageRows(rows),
        drawCallback: function () {
            if (typeof feather !== "undefined") feather.replace();
        },
        initComplete: function () {
            $wrap
                .removeClass("dt-pending is-loading")
                .addClass("dt-ready");
            if (typeof feather !== "undefined") feather.replace();
        },
    });

    if (prevNumbersLength != null) {
        $.fn.dataTable.ext.pager.numbers_length = prevNumbersLength;
    }

    return dt;
}

function inisialisasiPpStageTables() {
    // Kartu TV (McD board): skeleton kosong dulu, isi via refreshPpStageMiniTables (API).
    if ($("#tablePpReleased").length) {
        initPpStageTable("tablePpReleased", "tablePpReleased-wrap", []);
        $("#countPpReleased").text("0 Data");
    }
    if ($("#tablePpWorkOrder").length) {
        initPpStageTable("tablePpWorkOrder", "tablePpWorkOrder-wrap", []);
        $("#countPpWorkOrder").text("0 Data");
    }
    if ($("#tablePpProduksi").length) {
        initPpStageTable("tablePpProduksi", "tablePpProduksi-wrap", []);
        $("#countPpProduksi").text("0 Data");
    }
    if ($("#tablePpSelesai").length) {
        initPpStageTable("tablePpSelesai", "tablePpSelesai-wrap", []);
        $("#countPpSelesai").text("0 Data");
    }
}

function showPpHistoriSkeleton() {
    var $wrap = $("#tablePpHistori-wrap");
    if (!ppHistoriTableReady || !ppHistoriTable) {
        $wrap.removeClass("dt-ready is-loading").addClass("dt-pending");
    } else {
        $wrap.removeClass("dt-pending").addClass("dt-ready is-loading");
    }
}

function hidePpHistoriSkeleton() {
    ppHistoriTableReady = true;
    $("#tablePpHistori-wrap")
        .removeClass("dt-pending is-loading")
        .addClass("dt-ready");
}

function mapPpHistoriRows(rows) {
    return rows.map(function (r) {
        return {
            code:
                '<span class="pp-cell-text pp-cell-code">' +
                $("<div>").text(r.code || "—").html() +
                "</span>",
            date:
                '<span class="pp-cell-text">' +
                $("<div>").text(r.date || "—").html() +
                "</span>",
            product_html: ppItemCountHtml(r),
            qty: ppQtyUnitHtml(r),
            supervisor:
                '<span class="pp-cell-text">' +
                $("<div>").text(r.pic_name || r.supervisor || "—").html() +
                "</span>",
            status_html: ppStatusBadge(r.status || "done"),
            wo_html: ppWoProgressBadge(r),
            action:
                '<div class="d-flex align-items-center justify-content-center gap-1">' +
                '<a href="javascript:void(0);" class="btn-action-icon btn-pp-view" data-id="' +
                (r.production_planning_id || "") +
                '" title="Lihat Detail"><i class="fe fe-eye"></i></a>' +
                ppWorkOrderPrintIcons(r) +
                "</div>",
        };
    });
}

function initPpHistoriDateFilter() {
    ppBindSharedDatePicker($("#pp_histori_filter_date"));
}

function showPpJobSkeleton() {
    var $wrap = $("#tablePpJob-wrap");
    if (!ppJobTableReady || !ppJobTable) {
        $wrap.removeClass("dt-ready is-loading").addClass("dt-pending");
    } else {
        $wrap.removeClass("dt-pending").addClass("dt-ready is-loading");
    }
}

function hidePpJobSkeleton() {
    ppJobTableReady = true;
    $("#tablePpJob-wrap")
        .removeClass("dt-pending is-loading")
        .addClass("dt-ready");
}

function ppWoExecBadge(state) {
    var map = {
        inprod: { cls: "inprod", label: "In Production" },
        done: { cls: "done", label: "Completed" },
    };
    var s = map[state] || map.inprod;
    // Sama komponen badge Daftar Planning: .pp-status.inprod
    return '<span class="pp-status ' + s.cls + '">' + s.label + "</span>";
}

function mapPpJobRows(rows) {
    return rows.map(function (r) {
        var dateTxt = r.date || "—";
        if (r.date && typeof moment === "function") {
            var m = moment(r.date, ["YYYY-MM-DD", "YYYY-MM-DD HH:mm:ss"], true);
            if (m.isValid()) dateTxt = m.format("D MMM YYYY");
        }
        var ref =
            (r.spkp_number ? r.spkp_number + " · " : "") + (r.pp_number || "—");
        var woId = r.id || r.production_work_order_id || "";
        var printUrl =
            r.print_url ||
            (woId ? "/printProductionWorkOrder/" + woId : "#");
        var pic = r.pic || r.pic_name || "—";
        return {
            code:
                '<span class="pp-cell-text pp-cell-code">' +
                $("<div>").text(r.code || r.wo_number || "—").html() +
                "</span>",
            date:
                '<span class="pp-cell-text">' +
                $("<div>").text(dateTxt).html() +
                "</span>",
            pic_html:
                '<span class="pp-cell-text">' +
                $("<div>").text(pic).html() +
                "</span>",
            ref_html:
                '<span class="pp-cell-text">' +
                $("<div>").text(ref).html() +
                "</span>",
            status_html: ppWoExecBadge(r.state || r.execution_status || "inprod"),
            action:
                '<div class="d-flex align-items-center justify-content-center gap-1">' +
                '<a href="javascript:void(0);" class="btn-action-icon btn-pp-wo-view" data-id="' +
                woId +
                '" title="Lihat detail / hasil produksi"><i class="fe fe-eye"></i></a>' +
                (String(r.state || r.execution_status || "") === "inprod"
                    ? '<a href="javascript:void(0);" class="btn-action-icon btn-pp-wo-mat" data-id="' +
                      woId +
                      '" title="Ambil Bahan"><i class="fe fe-package"></i></a>'
                    : "") +
                '<a href="' +
                $("<div>").text(printUrl).html() +
                '" target="_blank" rel="noopener" class="btn-action-icon" title="Cetak WO A6"><i class="fe fe-printer"></i></a>' +
                "</div>",
        };
    });
}

function openPpWorkOrderPrints(workOrders) {
    (workOrders || []).forEach(function (wo, idx) {
        if (!wo || !wo.print_url) return;
        setTimeout(function () {
            window.open(wo.print_url, "_blank");
        }, idx * 250);
    });
}

function ppPaintJobDateInput() {
    ppPaintDateInput($("#pp_job_filter_date"), ppJobFilterDateStart, ppJobFilterDateEnd);
}

function initPpJobDateFilter() {
    ppBindSharedDatePicker($("#pp_job_filter_date"));
}

function ppJobTabIsActive() {
    return (
        $("#pp-tab-job").hasClass("active") ||
        $("#pp-pane-job").hasClass("active") ||
        $("#pp-pane-job").hasClass("show")
    );
}

function ppSyncJobViewUrl() {
    if (!ppJobTabIsActive()) return;
    ppReplacePageUrl();
}

function inisialisasiPpJob() {
    if (!$("#tablePpJob").length) return;

    if (typeof autocompleteProductVariantOnly === "function") {
        autocompleteProductVariantOnly("#pp_job_filter_product", "body");
    }
    if (typeof autocompleteStaff === "function") {
        autocompleteStaff("#pp_job_filter_supervisor", "body");
    }
    initPpJobDateFilter();

    $(document)
        .off("change.ppJobFilter", "#pp_job_filter_status, #pp_job_filter_product, #pp_job_filter_supervisor")
        .on(
            "change.ppJobFilter",
            "#pp_job_filter_status, #pp_job_filter_product, #pp_job_filter_supervisor",
            refreshPpJobTable
        );
    $("#pp_job_filter_clear")
        .off("click.ppJob")
        .on("click.ppJob", function () {
            ppApplyDefaultSharedDate({ soft: true });
            $("#pp_job_filter_status").val("");
            if ($("#pp_job_filter_product").hasClass("select2-hidden-accessible")) {
                $("#pp_job_filter_product").val(null).trigger("change");
            }
            if ($("#pp_job_filter_supervisor").hasClass("select2-hidden-accessible")) {
                $("#pp_job_filter_supervisor").val(null).trigger("change");
            }
        });

    showPpJobSkeleton();
    if ($.fn.DataTable.isDataTable("#tablePpJob")) {
        $("#tablePpJob").DataTable().destroy();
    }
    ppJobTable = $("#tablePpJob").DataTable({
        serverSide: true,
        processing: true,
        deferRender: true,
        paging: true,
        pageLength: 5,
        lengthMenu: [5, 10, 25, 50, 100],
        searching: false,
        info: true,
        order: [],
        sDom: "fBtlpi",
        language: {
            processing:
                '<div><span class="spinner-border spinner-border-sm me-2"></span>Memuat...</div>',
            emptyTable:
                '<div class="text-muted"><i class="fe fe-file-text d-block mb-2" style="font-size:28px;opacity:.5;"></i>Belum ada Work Order<br><small>Setelah Released dibagikan ke PIC, WO per SPV tampil di sini</small></div>',
            zeroRecords: "Tidak ada data yang cocok",
            sLengthMenu: "_MENU_",
            lengthMenu: "Tampilkan _MENU_ data",
            info: "_START_ - _END_ of _TOTAL_ items",
            infoEmpty: "Menampilkan 0 data",
            paginate: {
                next: ' <i class=" fa fa-angle-right"></i>',
                previous: '<i class="fa fa-angle-left"></i> ',
            },
        },
        ajax: function (dtData, callback) {
            if (ppJobLoadXhr && ppJobLoadXhr.readyState !== 4) ppJobLoadXhr.abort();
            showPpJobSkeleton();
            var state = $("#pp_job_filter_status").val() || "";
            var payload = $.extend({}, dtData, {
                active_only: state ? 0 : 1,
                state: state || undefined,
                date_from: ppJobFilterDateStart || "",
                date_to: ppJobFilterDateEnd || "",
                product_variant_id: $("#pp_job_filter_product").val() || "",
                pic_staff_id: $("#pp_job_filter_supervisor").val() || "",
            });
            ppJobLoadXhr = $.ajax({
                url: "/getProductionWorkOrders",
                method: "get",
                data: payload,
                success: function (json) {
                    callback({
                        draw: json.draw,
                        recordsTotal: json.recordsTotal || 0,
                        recordsFiltered: json.recordsFiltered || 0,
                        data: mapPpJobRows(json.data || []),
                    });
                    applyPpTabBadges({ job: json.recordsFiltered || 0 });
                    hidePpJobSkeleton();
                    if (typeof feather !== "undefined") feather.replace();
                },
                error: function (err) {
                    if (err && err.statusText === "abort") return;
                    hidePpJobSkeleton();
                    if (typeof handlePermissionError === "function" && handlePermissionError(err)) return;
                    callback({
                        draw: dtData.draw,
                        recordsTotal: 0,
                        recordsFiltered: 0,
                        data: [],
                    });
                },
            });
        },
        columns: [
            { data: "code", width: "16%" },
            { data: "date", width: "12%" },
            { data: "pic_html", width: "28%", orderable: false },
            { data: "ref_html", width: "20%", orderable: false },
            { data: "status_html", width: "14%", className: "text-center", orderable: false },
            { data: "action", width: "10%", className: "text-center", orderable: false },
        ],
        initComplete: function () {
            hidePpJobSkeleton();
            if (typeof feather !== "undefined") feather.replace();
        },
        drawCallback: function () {
            if (typeof feather !== "undefined") feather.replace();
        },
    });

    $('button[data-bs-toggle="tab"][data-bs-target="#pp-pane-job"]')
        .off("shown.bs.tab.ppJob")
        .on("shown.bs.tab.ppJob", function () {
            if (!ppJobFilterDateStart || !ppJobFilterDateEnd) {
                ppApplyDefaultSharedDate({ reload: false });
            } else {
                ppPaintDateInput(
                    $("#pp_job_filter_date"),
                    ppJobFilterDateStart,
                    ppJobFilterDateEnd
                );
            }
            if (ppJobTable) {
                ppJobTable.columns.adjust();
                if (!ppJobTableReady) showPpJobSkeleton();
                ppJobTable.ajax.reload(null, false);
            }
            ppSyncJobViewUrl();
        });
}

function showPpBahanSkeleton() {
    var $wrap = $("#tablePpBahan-wrap");
    if (!ppBahanTableReady || !ppBahanTable) {
        $wrap.removeClass("dt-ready is-loading").addClass("dt-pending");
    } else {
        $wrap.removeClass("dt-pending").addClass("dt-ready is-loading");
    }
}

function hidePpBahanSkeleton() {
    ppBahanTableReady = true;
    $("#tablePpBahan-wrap")
        .removeClass("dt-pending is-loading")
        .addClass("dt-ready");
}

function refreshPpBahanTable(soft) {
    if (!$("#tablePpBahan").length || !ppBahanTable) return;
    if (soft) {
        $("#tablePpBahan-wrap").removeClass("dt-pending").addClass("dt-ready is-loading");
    } else {
        showPpBahanSkeleton();
    }
    ppBahanTable.ajax.reload(null, false);
}

function ppBahanStageBadge(stage) {
    if (stage === "awaiting_ops") {
        return '<span class="pp-status work_order">Menunggu Ops</span>';
    }
    if (stage === "awaiting_qc") {
        return '<span class="pp-status inprod">Menunggu QC</span>';
    }
    return '<span class="pp-status draft">' + $("<div>").text(stage || "—").html() + "</span>";
}

function mapPpBahanRows(rows) {
    return (rows || []).map(function (r) {
        var docId = r.id || "";
        var payload = encodeURIComponent(JSON.stringify({
            id: r.id,
            wo_id: r.wo_id || null,
            number: r.number,
            stage: r.stage,
            pic: r.pic,
            wo_number: r.wo_number,
            pp_number: r.pp_number,
            items: r.items || [],
            can_ops: !!r.can_ops,
            can_qc: !!r.can_qc,
            print_url: r.print_url || "",
            confirmed_at: r.confirmed_at || "",
            ops_approved_at: r.ops_approved_at || "",
            qc_approved_at: r.qc_approved_at || "",
            line: r.line || "",
        }));
        var actions =
            '<div class="d-flex align-items-center justify-content-center gap-1">' +
            '<a href="javascript:void(0);" class="btn-action-icon btn-pp-bahan-view" data-doc-json="' +
            payload +
            '" title="Lihat request & ACC"><i class="fe fe-eye"></i></a>' +
            (r.print_url
                ? '<a href="' +
                  $("<div>").text(r.print_url).html() +
                  '" target="_blank" rel="noopener" class="btn-action-icon" title="Cetak STB"><i class="fe fe-printer"></i></a>'
                : "") +
            "</div>";
        return {
            number:
                '<span class="pp-cell-text pp-cell-code">' +
                $("<div>").text(r.number || "—").html() +
                "</span>",
            wo:
                '<span class="pp-cell-text">' +
                $("<div>").text(r.wo_number || "—").html() +
                "</span>",
            pic:
                '<span class="pp-cell-text">' +
                $("<div>").text(r.pic || "—").html() +
                "</span>",
            pp:
                '<span class="pp-cell-text">' +
                $("<div>").text(r.pp_number || "—").html() +
                "</span>",
            stage_html: ppBahanStageBadge(r.stage),
            action: actions,
        };
    });
}

function inisialisasiPpBahan() {
    if (!$("#tablePpBahan").length) return;

    $(document)
        .off("change.ppBahanFilter", "#pp_bahan_filter_stage")
        .on("change.ppBahanFilter", "#pp_bahan_filter_stage", function () {
            refreshPpBahanTable();
        });
    $("#pp_bahan_filter_clear")
        .off("click.ppBahan")
        .on("click.ppBahan", function () {
            $("#pp_bahan_filter_stage").val("");
            refreshPpBahanTable();
        });

    showPpBahanSkeleton();
    if ($.fn.DataTable.isDataTable("#tablePpBahan")) {
        $("#tablePpBahan").DataTable().destroy();
    }
    ppBahanTable = $("#tablePpBahan").DataTable({
        serverSide: true,
        processing: true,
        deferRender: true,
        paging: true,
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50],
        searching: false,
        info: true,
        order: [],
        sDom: "fBtlpi",
        language: {
            processing:
                '<div><span class="spinner-border spinner-border-sm me-2"></span>Memuat...</div>',
            emptyTable:
                '<div class="text-muted"><i class="fe fe-package d-block mb-2" style="font-size:28px;opacity:.5;"></i>Tidak ada bahan menunggu ACC<br><small>STB muncul di sini setelah PIC Ambil Bahan</small></div>',
            zeroRecords: "Tidak ada data yang cocok",
            sLengthMenu: "_MENU_",
            lengthMenu: "Tampilkan _MENU_ data",
            info: "_START_ - _END_ of _TOTAL_ items",
            infoEmpty: "Menampilkan 0 data",
            paginate: {
                next: ' <i class=" fa fa-angle-right"></i>',
                previous: '<i class="fa fa-angle-left"></i> ',
            },
        },
        ajax: function (dtData, callback) {
            if (ppBahanLoadXhr && ppBahanLoadXhr.readyState !== 4) ppBahanLoadXhr.abort();
            showPpBahanSkeleton();
            ppBahanLoadXhr = $.ajax({
                url: "/getProductionPendingMaterials",
                method: "get",
                data: $.extend({}, dtData, {
                    stage: $("#pp_bahan_filter_stage").val() || "",
                }),
                success: function (json) {
                    callback({
                        draw: json.draw,
                        recordsTotal: json.recordsTotal || 0,
                        recordsFiltered: json.recordsFiltered || 0,
                        data: mapPpBahanRows(json.data || []),
                    });
                    applyPpTabBadges({ bahan: json.recordsFiltered || 0 });
                    hidePpBahanSkeleton();
                    if (typeof feather !== "undefined") feather.replace();
                },
                error: function (err) {
                    if (err && err.statusText === "abort") return;
                    hidePpBahanSkeleton();
                    if (typeof handlePermissionError === "function" && handlePermissionError(err)) return;
                    callback({
                        draw: dtData.draw,
                        recordsTotal: 0,
                        recordsFiltered: 0,
                        data: [],
                    });
                    applyPpTabBadges({ bahan: 0 });
                },
            });
        },
        columns: [
            { data: "number", width: "14%" },
            { data: "wo", width: "14%" },
            { data: "pic", width: "18%", orderable: false },
            { data: "pp", width: "14%", orderable: false },
            { data: "stage_html", width: "16%", className: "text-center", orderable: false },
            { data: "action", width: "12%", className: "text-center", orderable: false },
        ],
        initComplete: function () {
            hidePpBahanSkeleton();
            if (typeof feather !== "undefined") feather.replace();
        },
        drawCallback: function () {
            if (typeof feather !== "undefined") feather.replace();
        },
    });

    $('button[data-bs-toggle="tab"][data-bs-target="#pp-pane-bahan"]')
        .off("shown.bs.tab.ppBahan")
        .on("shown.bs.tab.ppBahan", function () {
            if (ppBahanTable) {
                ppBahanTable.columns.adjust();
                if (!ppBahanTableReady) showPpBahanSkeleton();
                ppBahanTable.ajax.reload(null, false);
            }
        });
}

function inisialisasiPpHistori() {
    if (!$("#tablePpHistori").length) return;

    if (typeof autocompleteProductVariantOnly === "function") {
        autocompleteProductVariantOnly("#pp_histori_filter_product", "body");
    }
    if (typeof autocompleteStaff === "function") {
        autocompleteStaff("#pp_histori_filter_supervisor", "body");
    }
    initPpHistoriDateFilter();

    $(document)
        .off("change.ppHistoriFilter", "#pp_histori_filter_product, #pp_histori_filter_supervisor")
        .on(
            "change.ppHistoriFilter",
            "#pp_histori_filter_product, #pp_histori_filter_supervisor",
            refreshPpHistoriTable
        );
    $("#pp_histori_filter_clear")
        .off("click.ppHistori")
        .on("click.ppHistori", function () {
            ppApplyDefaultSharedDate({ soft: true });
            if ($("#pp_histori_filter_product").hasClass("select2-hidden-accessible")) {
                $("#pp_histori_filter_product").val(null).trigger("change");
            }
            if ($("#pp_histori_filter_supervisor").hasClass("select2-hidden-accessible")) {
                $("#pp_histori_filter_supervisor").val(null).trigger("change");
            }
        });

    showPpHistoriSkeleton();
    if ($.fn.DataTable.isDataTable("#tablePpHistori")) {
        $("#tablePpHistori").DataTable().destroy();
    }
    ppHistoriTable = $("#tablePpHistori").DataTable({
        serverSide: true,
        processing: true,
        deferRender: true,
        paging: true,
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
        searching: false,
        info: true,
        order: [],
        sDom: "fBtlpi",
        language: {
            processing:
                '<div><span class="spinner-border spinner-border-sm me-2"></span>Memuat...</div>',
            emptyTable:
                '<div class="text-muted"><i class="fe fe-clock d-block mb-2" style="font-size:28px;opacity:.5;"></i>Belum ada histori produksi<br><small>Batch selesai akan tampil di sini</small></div>',
            zeroRecords: "Tidak ada data yang cocok",
            sLengthMenu: "_MENU_",
            lengthMenu: "Tampilkan _MENU_ data",
            info: "_START_ - _END_ of _TOTAL_ items",
            infoEmpty: "Menampilkan 0 data",
            paginate: {
                next: ' <i class=" fa fa-angle-right"></i>',
                previous: '<i class="fa fa-angle-left"></i> ',
            },
        },
        ajax: function (dtData, callback) {
            if (ppHistoriLoadXhr && ppHistoriLoadXhr.readyState !== 4) ppHistoriLoadXhr.abort();
            showPpHistoriSkeleton();
            ppHistoriLoadXhr = $.ajax({
                url: "/getProductionPlanning",
                method: "get",
                data: $.extend({}, dtData, getPpListExtraParams("histori")),
                success: function (json) {
                    applyPpCounts(json.counts, json.tabs);
                    applyPpTabBadges({ histori: json.recordsFiltered || 0 });
                    if (json.stamp) ppLiveStamp = json.stamp;
                    callback({
                        draw: json.draw,
                        recordsTotal: json.recordsTotal || 0,
                        recordsFiltered: json.recordsFiltered || 0,
                        data: mapPpHistoriRows(json.data || []),
                    });
                    hidePpHistoriSkeleton();
                    if (typeof feather !== "undefined") feather.replace();
                },
                error: function (err) {
                    if (err && err.statusText === "abort") return;
                    hidePpHistoriSkeleton();
                    if (typeof handlePermissionError === "function" && handlePermissionError(err)) return;
                    callback({
                        draw: dtData.draw,
                        recordsTotal: 0,
                        recordsFiltered: 0,
                        data: [],
                    });
                },
            });
        },
        columns: [
            { data: "code", width: "13%" },
            { data: "date", width: "11%" },
            { data: "product_html", width: "22%", orderable: false },
            { data: "qty", width: "13%", className: "text-center" },
            { data: "supervisor", width: "13%" },
            { data: "status_html", width: "11%", className: "text-center", orderable: false },
            { data: "wo_html", width: "9%", className: "text-center", orderable: false },
            { data: "action", width: "8%", className: "text-center", orderable: false },
        ],
        initComplete: function () {
            hidePpHistoriSkeleton();
            if (typeof feather !== "undefined") feather.replace();
        },
    });

    $('button[data-bs-toggle="tab"][data-bs-target="#pp-pane-histori"]')
        .off("shown.bs.tab.ppHistori")
        .on("shown.bs.tab.ppHistori", function () {
            if (ppHistoriTable) {
                ppHistoriTable.columns.adjust().draw(false);
            }
        });
}

function ppEsc(s) {
    return $("<div>").text(s == null ? "" : String(s)).html();
}

function renderPpFormItems() {
    var $body = $("#pp-form-items-body");
    $("#pp-form-item-count").text(ppFormItems.length + " item");

    if (!ppFormItems.length) {
        $body.html(
            '<tr class="pg-popup-table-empty"><td colspan="5" class="text-center text-muted py-4">' +
                "Belum ada item. Pilih produk di atas lalu klik Tambah." +
                "</td></tr>"
        );
        return;
    }

    var rows = ppFormItems
        .map(function (it, i) {
            var qtyInput =
                '<input type="number" class="form-control form-control-sm text-center pp-form-qty" data-idx="' +
                i +
                '" min="1" value="' +
                (it.qty || 1) +
                '" style="height:32px;border-radius:6px;width:90px;display:inline-block;">';
            var unitOpts = "";
            var units = Array.isArray(it.units) ? it.units : [];
            if (!units.length && it.unit_id) {
                units = [
                    {
                        unit_id: it.unit_id,
                        unit_short_name: it.unit,
                        unit_name: it.unit,
                    },
                ];
            }
            units.forEach(function (u) {
                var id = String(u.unit_id || u.id || "");
                var label = u.unit_short_name || u.unit_name || u.text || id;
                var sel =
                    String(it.unit_id || "") === id ||
                    String(it.unit || "") === String(label)
                        ? " selected"
                        : "";
                unitOpts +=
                    '<option value="' +
                    ppEsc(label) +
                    '" data-unit-id="' +
                    ppEsc(id) +
                    '"' +
                    sel +
                    ">" +
                    ppEsc(label) +
                    "</option>";
            });
            var unitSelect =
                '<select class="form-select form-select-sm pp-form-unit" data-idx="' +
                i +
                '" style="height:32px;border-radius:6px;font-size:12.5px;">' +
                (unitOpts ||
                    '<option value="' +
                        ppEsc(it.unit || "") +
                        '" data-unit-id="' +
                        ppEsc(it.unit_id || "") +
                        '" selected>' +
                        ppEsc(it.unit || "—") +
                        "</option>") +
                "</select>";
            return (
                "<tr>" +
                '<td class="text-center">' +
                (i + 1) +
                "</td>" +
                "<td>" +
                '<div class="fw-semibold text-dark" style="font-size:13px;">' +
                ppEsc(it.product) +
                "</div>" +
                '<div class="pp-item-sku">' +
                ppEsc(it.sku) +
                "</div>" +
                "</td>" +
                '<td class="text-center">' +
                qtyInput +
                "</td>" +
                "<td>" +
                unitSelect +
                "</td>" +
                '<td class="text-center">' +
                '<a href="javascript:void(0);" class="btn-action-icon btn-pp-remove-item text-danger" data-idx="' +
                i +
                '" title="Hapus"><i class="fe fe-trash-2"></i></a>' +
                "</td>" +
                "</tr>"
            );
        })
        .join("");
    $body.html(rows);
    if (typeof feather !== "undefined") feather.replace();
}

function resetAddPlanningForm() {
    ppFormItems = [];
    $("#add_pp_notes").val("");
    $("#add_pp_qty").val(1);
    fillPpUnitOptions([]);
    if (typeof moment === "function") {
        $("#add_pp_date").val(moment().format("DD/MM/YYYY"));
    }
    if ($("#add_pp_product").hasClass("select2-hidden-accessible")) {
        $("#add_pp_product").val(null).trigger("change");
    }
    renderPpFormItems();
}

function ppCollectUnitsFromProductData(data) {
    var units = [];
    var seen = {};
    function pushUnit(id, name, shortName) {
        id = id != null && id !== "" ? String(id) : "";
        if (!id || seen[id]) return;
        seen[id] = true;
        units.push({
            unit_id: id,
            unit_name: name || shortName || id,
            unit_short_name: shortName || name || id,
        });
    }
    if (!data) return units;
    (data.pr_unit || []).forEach(function (u) {
        pushUnit(u.unit_id, u.unit_name, u.unit_short_name);
    });
    if (data.default_unit || data.default_unit_id) {
        pushUnit(
            data.default_unit || data.default_unit_id,
            data.default_unit_name,
            data.default_unit_short_name
        );
    }
    if (data.unit_id) {
        pushUnit(data.unit_id, data.unit_name, data.unit_short_name);
    }
    if (data.retail_unit) {
        pushUnit(data.retail_unit, data.retail_unit_name, data.retail_unit_name);
    }
    return units;
}

function fillPpUnitOptions(units, preferredId) {
    var $unit = $("#add_pp_unit");
    $unit.empty();
    var list = Array.isArray(units) ? units : [];
    if (!list.length) {
        $unit.append('<option value="">Pilih satuan...</option>');
        return;
    }
    list.forEach(function (u) {
        var id = u.unit_id || u.id || "";
        var label =
            u.unit_short_name || u.unit_name || u.text || String(id);
        $unit.append(
            $("<option>", {
                value: label,
                text: label,
                "data-unit-id": id,
            })
        );
    });
    if (preferredId) {
        var match = list.find(function (u) {
            return String(u.unit_id || u.id) === String(preferredId);
        });
        if (match) {
            $unit.val(match.unit_short_name || match.unit_name);
            return;
        }
    }
    $unit.prop("selectedIndex", 0);
}

function fillPpUnitsFromProductData(data) {
    if (!data) {
        fillPpUnitOptions([]);
        return;
    }
    fillPpUnitOptions(
        ppCollectUnitsFromProductData(data),
        data.default_unit || data.default_unit_id || data.unit_id
    );
}

function initPpFormAutocompletes() {
    var $modal = $("#modalAddPlanning");
    if (typeof autocompleteProductVariantOnly === "function") {
        autocompleteProductVariantOnly("#add_pp_product", $modal);
        $("#add_pp_product")
            .off("change.ppUnit")
            .on("change.ppUnit", function () {
                var data = $(this).select2("data")[0] || null;
                fillPpUnitsFromProductData(data);
            });
    }
}

function ppHandleStockCheckError(res, bomIdFallback) {
    if (!res) {
        if (typeof showPgErrorModal === "function") {
            showPgErrorModal("Gagal", "Terjadi kesalahan yang tidak diketahui.");
        }
        return;
    }
    if (res.status == -1) {
        if (typeof showPgStockShortageModal === "function") {
            showPgStockShortageModal(
                res.header || "Stock Tidak Mencukupi",
                res.shortages || [],
                res.message
            );
        } else if (typeof showPgErrorModal === "function") {
            showPgErrorModal("Stock Tidak Mencukupi", res.message);
        }
        return;
    }
    if (res.status == 0 && res.code === "recipe_needs_update") {
        var msg =
            (res.message || "Satuan / resep bahan mentah perlu diperbarui.") +
            "\n\nBuka halaman Produksi → Update Resep untuk memperbaiki bahan mentah secara realtime, lalu coba lagi.";
        if (typeof Swal !== "undefined") {
            Swal.fire({
                icon: "error",
                iconColor: "#ef4444",
                title: res.header || "Resep Perlu Diperbarui",
                html:
                    '<p class="text-start mb-0" style="font-size:14px;white-space:pre-wrap;">' +
                    $("<div>").text(msg).html() +
                    "</p>",
                confirmButtonText: "Tutup",
                customClass: {
                    confirmButton: "pg-btn-confirm pg-btn-confirm--danger",
                    title: "fw-bold fs-4 text-dark",
                    popup: "rounded-4",
                },
                buttonsStyling: false,
            });
        }
        return;
    }
    if (typeof showPgErrorModal === "function") {
        showPgErrorModal(res.header || "Gagal", res.message || "Validasi gagal.");
    }
}

function addManualPpItem(silent) {
    var $prod = $("#add_pp_product");
    var data = $prod.hasClass("select2-hidden-accessible")
        ? $prod.select2("data")[0]
        : null;
    if (!data || !data.id) {
        if (!silent && typeof notifikasi === "function") {
            notifikasi("error", "Validasi", "Pilih produk jadi terlebih dahulu.");
        }
        return false;
    }
    var qty = parseInt($("#add_pp_qty").val(), 10);
    if (!qty || qty < 1) {
        if (!silent && typeof notifikasi === "function") {
            notifikasi("error", "Validasi", "Qty rencana minimal 1.");
        }
        return false;
    }
    var unit = $("#add_pp_unit").val();
    if (!unit) {
        if (!silent && typeof notifikasi === "function") {
            notifikasi("error", "Validasi", "Pilih satuan produk.");
        }
        return false;
    }
    var unitId = $("#add_pp_unit option:selected").data("unit-id") || null;
    var sku = data.product_variant_sku || data.sku || String(data.id);
    var productName = [data.pr_name, data.product_variant_name]
        .filter(Boolean)
        .join(" ")
        .replace(/\s+/g, " ")
        .trim();
    if (!productName) {
        var text = String(data.text || "");
        var parts = text.split("|");
        productName = (parts.length > 1 ? parts.slice(1).join("|") : text).trim();
    }
    var variantId = data.product_variant_id || data.id;
    var units = ppCollectUnitsFromProductData(data);

    // Draft PP = rencana saja; cek resep/stok nanti saat Release to Production.
    var candidate = ppFormItems.map(function (it) {
        return $.extend({}, it);
    });
    var existIdx = candidate.findIndex(function (it) {
        return (
            String(it.product_variant_id) === String(variantId) &&
            String(it.unit_id || "") === String(unitId || "")
        );
    });
    if (existIdx >= 0) {
        candidate[existIdx].qty =
            (parseInt(candidate[existIdx].qty, 10) || 0) + qty;
    } else {
        var item = {
            sku: sku,
            product: productName || sku,
            product_variant_id: variantId,
            qty: qty,
            unit: unit,
            unit_id: unitId,
            units: units,
            retail_unit: data.retail_unit || null,
            default_unit: data.default_unit || data.default_unit_id || null,
        };
        if (typeof pgPopupTableInsert === "function") {
            pgPopupTableInsert(candidate, item);
        } else {
            candidate.push(item);
        }
    }

    ppFormItems = candidate;
    $prod.val(null).trigger("change");
    $("#add_pp_qty").val(1);
    renderPpFormItems();
    if (typeof pgPopupTableScrollToEdge === "function") {
        pgPopupTableScrollToEdge($("#pp-table-items-scroll"));
    }
    return true;
}

$(document).ready(function () {
    inisialisasiPp();
    initPpFormAutocompletes();

    var tab = new URLSearchParams(window.location.search || "").get("tab") || "planning";
    if (tab === "job") {
        var $jobTab = $('button[data-bs-target="#pp-pane-job"]');
        if ($jobTab.length) $jobTab.tab("show");
    } else if (tab === "bahan") {
        var $bahanTab = $('button[data-bs-target="#pp-pane-bahan"]');
        if ($bahanTab.length) $bahanTab.tab("show");
    } else if (tab === "histori") {
        var $histTab = $('button[data-bs-target="#pp-pane-histori"]');
        if ($histTab.length) $histTab.tab("show");
    } else {
        // Default: Daftar Planning
        var $planTab = $('button[data-bs-target="#pp-pane-planning"]');
        if ($planTab.length) $planTab.tab("show");
        if (typeof ppReplacePageUrl === "function") ppReplacePageUrl();
    }

    startPpLivePolling();

    $("#pp_filter_status").off("change.ppFilterStatus").on("change.ppFilterStatus", refreshPpTable);
    $("#pp_filter_clear").on("click", function () {
        ppApplyDefaultSharedDate({ soft: true });
        $("#pp_filter_status").val("");
        if ($("#pp_filter_product").hasClass("select2-hidden-accessible")) {
            $("#pp_filter_product").val(null).trigger("change");
        } else {
            $("#pp_filter_product").val("");
        }
        if ($("#pp_filter_supervisor").hasClass("select2-hidden-accessible")) {
            $("#pp_filter_supervisor").val(null).trigger("change");
        } else {
            $("#pp_filter_supervisor").val("");
        }
    });

    // View: tab baru (URL /view) — halaman normal tetap di /productionPlanning
    $(document).on("click", "#btn-pp-fullscreen-view", function (e) {
        e.preventDefault();
        window.open(buildPpViewUrl(), "_blank", "noopener");
    });

    // Kembali dari fullscreen → halaman normal + bawa filter aktif
    $(document).on("click", "a[href*='productionPlanning']:not(#btn-pp-fullscreen-view)", function (e) {
        var href = $(this).attr("href") || "";
        if (!window.ppFullscreen) return;
        if (href.indexOf("/productionPlanning/view") !== -1) return;
        if (href.indexOf("productionPlanning") === -1) return;
        // Hanya tombol Kembali di header view
        if (!$(this).closest(".page-header, .page-header-right, .list-inline").length) return;
        e.preventDefault();
        var base =
            typeof window.ppMainBaseUrl === "string" && window.ppMainBaseUrl
                ? window.ppMainBaseUrl
                : "/productionPlanning";
        var qs = buildPpQueryString();
        window.location.href = qs ? base + "?" + qs : base;
    });

    $(document).on("click", ".btn-pp-create", function (e) {
        e.preventDefault();
        resetAddPlanningForm();
        $("#modalAddPlanning").modal("show");
    });

    $("#modalAddPlanning").on("shown.bs.modal", function () {
        if (typeof feather !== "undefined") feather.replace();
    });

    $(document).on("click", "#btn-pp-add-item", function () {
        addManualPpItem();
    });

    $(document).on("click", ".btn-pp-remove-item", function () {
        var idx = parseInt($(this).data("idx"), 10);
        if (isNaN(idx)) return;
        ppFormItems.splice(idx, 1);
        renderPpFormItems();
    });

    $(document).on("change", ".pp-form-qty", function () {
        var idx = parseInt($(this).data("idx"), 10);
        var val = parseInt($(this).val(), 10);
        if (isNaN(idx) || !ppFormItems[idx]) return;
        if (!val || val < 1) {
            $(this).val(ppFormItems[idx].qty || 1);
            return;
        }
        ppFormItems[idx].qty = val;
    });

    $(document).on("change", ".pp-form-unit", function () {
        var idx = parseInt($(this).data("idx"), 10);
        if (isNaN(idx) || !ppFormItems[idx]) return;
        var $opt = $(this).find("option:selected");
        ppFormItems[idx].unit = $(this).val() || "";
        ppFormItems[idx].unit_id = $opt.data("unit-id") || null;
    });

    $(document).on("click", ".btn-pp-view", function (e) {
        e.preventDefault();
        e.stopPropagation();
        openPpViewModal($(this).data("id"));
    });

    $(document).on("click", ".btn-pp-delete:not(.is-disabled)", function (e) {
        e.preventDefault();
        e.stopPropagation();
        var id = $(this).data("id");
        var code = $(this).data("code") || "Planning";
        if (!id) return;
        $("#modalDelete .modal-body #delete_reason").remove();
        showModalDelete(
            "Hapus " + code + "? Planning akan diarsipkan. Isi alasan di bawah.",
            "btn-delete-pp"
        );
        $("#modalDelete .modal-body").append(
            '<textarea class="form-control mt-2" id="delete_reason" placeholder="Alasan penghapusan planning..." rows="3"></textarea>'
        );
        $("#btn-delete-pp").html("Hapus Planning").attr("data-id", id);
    });

    $(document).on("click", "#btn-delete-pp", function () {
        var $btn = $(this);
        var id = $btn.attr("data-id");
        var reason = ($("#delete_reason").val() || "").trim();
        if (!id) return;
        if (!reason) {
            $("#delete_reason").addClass("is-invalid").focus();
            notifikasi("error", "Catatan wajib", "Isi alasan penghapusan.");
            return;
        }
        if (typeof LoadingButton === "function") LoadingButton($btn);
        $.ajax({
            url: "/deleteProductionPlanning",
            method: "post",
            data: {
                production_planning_id: id,
                delete_reason: reason,
                _token: token,
            },
            success: function (res) {
                $(".modal").modal("hide");
                $("#modalDelete .modal-body").html(
                    '<p id="text-delete" style="font-size:10pt"></p>'
                );
                if (typeof ResetLoadingButton === "function") {
                    ResetLoadingButton($btn, "Hapus Planning");
                }
                if (res && res.status === -1) {
                    notifikasi("error", "Gagal Hapus", res.message || "Tidak bisa hapus planning");
                    return;
                }
                refreshPpTable();
                if (typeof refreshPpJobTable === "function") refreshPpJobTable();
                if (typeof refreshPpStageMiniTables === "function") refreshPpStageMiniTables();
                notifikasi(
                    "success",
                    "Terhapus",
                    (res.pp_number || "Planning") + " berhasil dihapus."
                );
            },
            error: function (err) {
                $(".modal").modal("hide");
                $("#modalDelete .modal-body").html(
                    '<p id="text-delete" style="font-size:10pt"></p>'
                );
                if (typeof ResetLoadingButton === "function") {
                    ResetLoadingButton($btn, "Hapus Planning");
                }
                if (typeof handlePermissionError === "function" && handlePermissionError(err)) return;
                notifikasi("error", "Gagal Hapus", "Tidak bisa hapus planning");
            },
        });
    });

    $(document).on("click", "#btn-pp-view-work-order", function () {
        $("#modalViewPlanning").modal("hide");
        if (ppViewPlanningId) openPpWorkOrderModal(ppViewPlanningId);
    });

    $(document).on("click", "#btn-pp-view-release", function () {
        if ($(this).prop("disabled") || $(this).hasClass("disabled")) return;
        $("#modalViewPlanning").modal("hide");
        openPpApproveModal(ppViewPlanningId);
    });

    $(document).on("click", "#btn-confirm-approve-planning", function () {
        submitPpApprove($(this));
    });

    $(document).on("click", "#btn-confirm-work-order", function () {
        submitPpWorkOrder($(this));
    });

    $("#btn-save-planning").on("click", function () {
        var $btn = $(this);

        var $prodCheck = $("#add_pp_product");
        var pdataCheck = $prodCheck.hasClass("select2-hidden-accessible")
            ? $prodCheck.select2("data")[0]
            : null;
        if (pdataCheck && pdataCheck.id) {
            if (typeof notifikasi === "function") {
                notifikasi(
                    "error",
                    "Belum Ditambahkan",
                    "Klik Tambah dulu untuk produk yang dipilih, baru Simpan."
                );
            }
            return;
        }

        var date = $("#add_pp_date").val() || "";
        var notes = $("#add_pp_notes").val() || "";

        $("#pp-form-items-body .pp-form-qty").each(function () {
            var idx = parseInt($(this).data("idx"), 10);
            var val = parseInt($(this).val(), 10);
            if (!isNaN(idx) && ppFormItems[idx] && val > 0) {
                ppFormItems[idx].qty = val;
            }
        });
        $("#pp-form-items-body .pp-form-unit").each(function () {
            var idx = parseInt($(this).data("idx"), 10);
            if (isNaN(idx) || !ppFormItems[idx]) return;
            var $opt = $(this).find("option:selected");
            ppFormItems[idx].unit = $(this).val() || ppFormItems[idx].unit;
            ppFormItems[idx].unit_id =
                $opt.data("unit-id") || ppFormItems[idx].unit_id;
        });

        if (!ppFormItems.length) {
            if (typeof notifikasi === "function") {
                notifikasi(
                    "error",
                    "Validasi Gagal",
                    "Tambahkan minimal 1 produk ke daftar rencana."
                );
            }
            return;
        }

        var dateIso = null;
        if (typeof moment === "function") {
            var dm = moment(
                date,
                ["D MMM YYYY", "DD MMM YYYY", "YYYY-MM-DD", "DD/MM/YYYY"],
                true
            );
            if (dm.isValid()) dateIso = dm.format("YYYY-MM-DD");
        }

        var items = ppFormItems.map(function (it) {
            return {
                sku: it.sku,
                product_name: it.product,
                product: it.product,
                product_variant_id: it.product_variant_id || null,
                qty: Number(it.qty) || 0,
                unit_id: it.unit_id || null,
                unit: it.unit || "—",
                unit_label: it.unit || "—",
            };
        });

        var payload = {
            pp_date: dateIso || date,
            notes: notes,
            items: items,
            _token: token,
        };

        var saveHtml = $btn.html();
        $btn.data("pp-btn-html", saveHtml);
        if (typeof LoadingButton === "function") LoadingButton($btn);
        $.ajax({
            url: "/insertProductionPlanning",
            method: "post",
            data: payload,
            headers: {
                "X-CSRF-TOKEN": token,
            },
            success: function (res) {
                if (typeof ResetLoadingButton === "function") {
                    ResetLoadingButton($btn, saveHtml);
                } else {
                    $btn.prop("disabled", false).html(saveHtml);
                }
                if (typeof feather !== "undefined") feather.replace();
                if (res && (res.status === 1 || res.status === true || res.production_planning_id)) {
                    $("#modalAddPlanning").modal("hide");
                    resetAddPlanningForm();
                    if (typeof notifikasi === "function") {
                        notifikasi(
                            "success",
                            "Berhasil",
                            "Planning " +
                                (res.pp_number || "") +
                                " dibuat (" +
                                items.length +
                                " produk)."
                        );
                    }
                    refreshPpTable();
                    if (typeof refreshPpStageMiniTables === "function") {
                        refreshPpStageMiniTables();
                    }
                    return;
                }
                if (typeof showPgErrorModal === "function") {
                    showPgErrorModal(
                        "Gagal Simpan",
                        (res && res.message) || "Gagal menyimpan planning."
                    );
                }
            },
            error: function (xhr) {
                if (typeof ResetLoadingButton === "function") {
                    ResetLoadingButton($btn, saveHtml);
                } else {
                    $btn.prop("disabled", false).html(saveHtml);
                }
                if (typeof feather !== "undefined") feather.replace();
                if (
                    typeof handlePermissionError === "function" &&
                    handlePermissionError(xhr)
                ) {
                    return;
                }
                if (typeof notifikasi === "function") {
                    notifikasi(
                        "error",
                        "Gagal",
                        "Tidak bisa simpan Production Planning."
                    );
                }
            },
        });
    });
});

function ensurePpSkalaOptions(done) {
    if (PP_SKALA_OPTIONS.length) {
        if (typeof done === "function") done(PP_SKALA_OPTIONS);
        return;
    }
    $.ajax({
        url: "/getProductionSkala",
        method: "get",
        success: function (e) {
            PP_SKALA_OPTIONS = Array.isArray(e) ? e : e.original || [];
            if (typeof done === "function") done(PP_SKALA_OPTIONS);
        },
        error: function () {
            PP_SKALA_OPTIONS = [];
            if (typeof done === "function") done([]);
        },
    });
}

function openPpViewModal(id) {
    if (!id) return;
    ppViewPlanningId = id;
    ppApprovePlanningId = id;
    $("#pp-view-stock-alert").hide().empty();
    $("#btn-pp-view-release").prop("disabled", false).removeClass("disabled");
    $.ajax({
        url: "/getProductionPlanningDetail",
        method: "get",
        data: { production_planning_id: id },
        success: function (d) {
            $("#pp-view-code").text(d.pp_number || d.code || "—");
            $("#pp-view-date").text(d.date || "—");
            $("#pp-view-status").html(ppStatusBadge(d.status || d.pp_status));
            if (Number(d.wo_total || 0) > 0) {
                $("#pp-view-status").append(
                    '<div class="mt-1">' + ppWoProgressBadge(d) + "</div>"
                );
            }
            $("#pp-view-subtitle").text(d.notes || "Detail rencana produksi");
            $("#pp-view-notes").text(d.notes ? "Catatan: " + d.notes : "");

            var st = d.status || d.pp_status;
            var isDraft = st === "draft";

            if (isDraft) {
                renderPpViewDraftItems(d.items || []);
            } else {
                renderPpViewGroupedItems(d);
            }

            $("#pp-view-work-orders-list").empty();
            $("#pp-view-work-orders").hide();

            $("#btn-pp-view-print-spk")
                .attr("href", "/printProductionPlanning/" + Number(d.production_planning_id))
                .toggle(["released", "work_order", "inprod", "done"].indexOf(st) !== -1);
            var canRelease = !!d.can_release;
            var canAssignWo = !!d.can_assign_wo;
            var $modal = $("#modalViewPlanning");
            $modal.removeClass("pg-modal--form pg-modal--confirm");

            $("#btn-pp-view-release").hide();
            $("#btn-pp-view-work-order").hide();

            if (isDraft) {
                $modal.addClass("pg-modal--confirm");
                $("#pp-view-modal-title").text("Release to Production");
                if ($("#pp-view-icon-wrap").length) {
                    $("#pp-view-icon-wrap").html('<i class="fe fe-check-circle" id="pp-view-modal-icon"></i>');
                } else {
                    $("#pp-view-modal-icon").attr("class", "fe fe-check-circle");
                }
                if (canRelease) {
                    $("#btn-pp-view-release").show().prop("disabled", true).addClass("disabled");
                    ppLoadDraftReleaseStockCheck(d.production_planning_id);
                }
            } else {
                $modal.addClass("pg-modal--form");
                $("#pp-view-modal-title").text("Detail Production Planning");
                if ($("#pp-view-icon-wrap").length) {
                    $("#pp-view-icon-wrap").html('<i class="fe fe-calendar" id="pp-view-modal-icon"></i>');
                } else {
                    $("#pp-view-modal-icon").attr("class", "fe fe-calendar");
                }
                if (st === "released" && canAssignWo) $("#btn-pp-view-work-order").show();
            }

            $modal.modal("show");
            if (typeof feather !== "undefined") feather.replace();
        },
        error: function (err) {
            if (typeof handlePermissionError === "function" && handlePermissionError(err)) return;
            notifikasi("error", "Gagal", "Tidak bisa load detail planning");
        },
    });
}

/** Draft: flat rows + kolom stok (tanpa grouping PIC). */
function renderPpViewDraftItems(items) {
    $("#pp-view-items-head").html(
        '<th style="min-width:220px;">Produk</th>' +
            '<th class="text-end" style="width:80px;">Qty</th>' +
            '<th style="width:80px;">Satuan</th>' +
            '<th style="min-width:160px;">Stok Gudang</th>'
    );
    var body = "";
    (items || []).forEach(function (it) {
        body +=
            '<tr data-ppi="' +
            Number(it.ppi_id || 0) +
            '">' +
            "<td>" +
            '<div class="fw-semibold text-dark">' +
            $("<div>").text(it.product || it.product_name || "—").html() +
            "</div>" +
            '<div class="small text-muted font-monospace">' +
            $("<div>").text(it.sku || "").html() +
            "</div></td>" +
            '<td class="text-end fw-semibold">' +
            ppFormatNumber(it.qty) +
            "</td>" +
            "<td>" +
            $("<div>").text(it.unit || it.unit_label || "—").html() +
            "</td>" +
            '<td class="pp-stock-cell text-muted"><span class="spinner-border spinner-border-sm me-1" role="status"></span>Cek stok…</td>' +
            "</tr>";
    });
    $("#pp-view-items-body").html(
        body || '<tr><td colspan="4" class="text-center text-muted">Tidak ada item</td></tr>'
    );
}

/** Released+: grouping PIC / Work Order. */
function renderPpViewGroupedItems(d) {
    $("#pp-view-items-head").html(
        '<th style="min-width:170px;">PIC / Work Order</th>' +
            '<th style="min-width:180px;">Produk</th>' +
            '<th class="text-end" style="width:70px;">Qty</th>' +
            '<th style="width:70px;">Satuan</th>' +
            '<th style="min-width:110px;">Skala</th>' +
            '<th style="min-width:130px;">Armada</th>' +
            '<th class="text-center" style="width:110px;">Status</th>' +
            '<th class="text-center no-sort" style="width:105px;">Aksi</th>'
    );

    var wos = d.work_orders || [];
    var woByPic = {};
    wos.forEach(function (wo) {
        if (wo.pic_staff_id) woByPic[Number(wo.pic_staff_id)] = wo;
    });

    var picGroups = [];
    var picMap = {};
    (d.items || []).forEach(function (it) {
        var key = it.pic_staff_id
            ? "pic_" + it.pic_staff_id
            : it.pic_name
              ? "name_" + it.pic_name
              : "unassigned";
        if (!picMap[key]) {
            picMap[key] = {
                pic_staff_id: it.pic_staff_id,
                pic_name:
                    it.pic_name ||
                    (it.pic_staff_id ? "PIC #" + it.pic_staff_id : "Belum ditentukan"),
                wo: it.pic_staff_id ? woByPic[Number(it.pic_staff_id)] : null,
                items: [],
            };
            picGroups.push(picMap[key]);
        }
        picMap[key].items.push(it);
    });

    var body = "";
    picGroups.forEach(function (group) {
        var groupRows = group.items.length;
        var wo = group.wo;
        var hasWo = !!(wo && (wo.print_url || wo.wo_number));
        var printUrl = $("<div>").text((wo && wo.print_url) || "#").html();
        var woNum = $("<div>").text((wo && wo.wo_number) || "").html();
        var closed = !!(wo && wo.is_closed);
        var statusBadge = wo
            ? ppWoExecBadge(closed ? "done" : wo.execution_status || "inprod")
            : '<span class="text-muted">—</span>';
        var picIcon = group.pic_staff_id ? "fe fe-user" : "fe fe-user-x text-muted";

        group.items.forEach(function (it, idx) {
            body += "<tr>";
            if (idx === 0) {
                body +=
                    '<td rowspan="' +
                    groupRows +
                    '" class="align-middle pp-pic-cell">' +
                    '<div class="fw-bold text-dark d-flex align-items-center gap-1" style="font-size:13px;">' +
                    '<i class="' +
                    picIcon +
                    '"></i> ' +
                    $("<div>").text(group.pic_name).html() +
                    "</div>" +
                    (woNum
                        ? '<div class="small font-monospace text-muted mt-0.5" style="font-size:11px;">' +
                          woNum +
                          "</div>"
                        : "") +
                    "</td>";
            }
            body +=
                "<td>" +
                '<div class="fw-semibold text-dark">' +
                $("<div>").text(it.product || it.product_name || "—").html() +
                "</div>" +
                '<div class="small text-muted font-monospace">' +
                $("<div>").text(it.sku || "").html() +
                "</div></td>" +
                '<td class="text-end fw-semibold">' +
                ppFormatNumber(it.qty) +
                "</td>" +
                "<td>" +
                $("<div>").text(it.unit || it.unit_label || "—").html() +
                "</td>" +
                "<td>" +
                $("<div>").text(it.skala_label || it.skala_code || "—").html() +
                "</td>" +
                "<td>" +
                $("<div>").text(it.armada_name || "—").html() +
                "</td>";
            if (idx === 0) {
                body +=
                    '<td rowspan="' +
                    groupRows +
                    '" class="text-center align-middle border-start">' +
                    statusBadge +
                    "</td>" +
                    '<td rowspan="' +
                    groupRows +
                    '" class="text-center align-middle border-start">' +
                    (hasWo
                        ? '<a href="' +
                          printUrl +
                          '" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary pp-btn-print-wo d-inline-flex align-items-center gap-1 px-2.5 py-1" style="font-size:11.5px;border-radius:6px;font-weight:600;white-space:nowrap;" title="Cetak Work Order ' +
                          woNum +
                          '">' +
                          '<i class="fe fe-printer"></i> <span>Cetak WO</span>' +
                          "</a>"
                        : '<span class="text-muted small">—</span>') +
                    "</td>";
            }
            body += "</tr>";
        });
    });

    $("#pp-view-items-body").html(
        body || '<tr><td colspan="8" class="text-center text-muted">Tidak ada item</td></tr>'
    );
}

/** Async: stok FG multi-satuan + cek bahan mentah → highlight & disable Release. */
function ppLoadDraftReleaseStockCheck(ppId) {
    var $btn = $("#btn-pp-view-release");
    var $alert = $("#pp-view-stock-alert");
    $alert
        .html(
            '<div class="alert alert-light border text-muted py-2 px-3 mb-0">' +
                '<span class="spinner-border spinner-border-sm me-2"></span>Memeriksa stok gudang &amp; bahan mentah…' +
                "</div>"
        )
        .show();

    $.ajax({
        url: "/checkProductionPlanningRelease",
        method: "get",
        data: { production_planning_id: ppId },
        success: function (res) {
            var byPpi = {};
            (res.items || []).forEach(function (it) {
                byPpi[Number(it.ppi_id)] = it;
            });

            $("#pp-view-items-body tr[data-ppi]").each(function () {
                var ppi = Number($(this).data("ppi"));
                var info = byPpi[ppi];
                var $cell = $(this).find(".pp-stock-cell");
                if (!info) {
                    $cell.html('<span class="text-muted">—</span>');
                    return;
                }
                var short = info.row_ok === false;
                $(this).toggleClass("pp-row-stock-short", short);
                $cell.toggleClass("is-short", short);
                var lines = "";
                if (info.stock_units && info.stock_units.length) {
                    lines = info.stock_units
                        .map(function (u) {
                            return (
                                "<div>" +
                                $("<div>")
                                    .text(
                                        (u.ps_stock_text || "0") +
                                            " " +
                                            (u.unit_short_name || u.unit_name || "")
                                    )
                                    .html() +
                                "</div>"
                            );
                        })
                        .join("");
                } else {
                    lines =
                        "<div>" +
                        $("<div>").text(info.stock_text || "0").html() +
                        "</div>";
                }
                if (info.available_text) {
                    lines +=
                        '<div class="small text-muted mt-1">≈ ' +
                        $("<div>").text(info.available_text).html() +
                        " (satuan rencana)</div>";
                }
                if (short && info.issue) {
                    lines +=
                        '<div class="small mt-1">' +
                        $("<div>").text(info.issue).html() +
                        "</div>";
                }
                $cell.html(lines);
            });

            var can = !!res.can_release;
            $btn.prop("disabled", !can).toggleClass("disabled", !can);

            if (can) {
                $alert
                    .html(
                        '<div class="alert alert-success border py-2 px-3 mb-0">' +
                            '<i class="fe fe-check-circle me-1"></i>Stok bahan mencukupi — siap Release to Production.' +
                            "</div>"
                    )
                    .show();
            } else {
                var msg = res.message || "Stok / resep tidak memenuhi syarat Release.";
                if (res.shortages && res.shortages.length) {
                    msg +=
                        "\n" +
                        res.shortages
                            .map(function (s) {
                                return (
                                    "• " +
                                    (s.supplies_name || s.label || "Bahan") +
                                    " — butuh " +
                                    (s.needed != null ? s.needed : "?") +
                                    ", stok " +
                                    (s.available != null ? s.available : "?")
                                );
                            })
                            .join("\n");
                }
                $alert
                    .html(
                        '<div class="alert alert-danger border py-2 px-3 mb-0">' +
                            '<div class="fw-bold mb-1">' +
                            $("<div>").text(res.header || "Tidak bisa Release").html() +
                            "</div>" +
                            '<div style="white-space:pre-wrap;">' +
                            $("<div>").text(msg).html() +
                            "</div></div>"
                    )
                    .show();
            }
        },
        error: function (xhr) {
            if (typeof handlePermissionError === "function" && handlePermissionError(xhr)) {
                return;
            }
            $btn.prop("disabled", true).addClass("disabled");
            $alert
                .html(
                    '<div class="alert alert-warning border py-2 px-3 mb-0">Gagal cek stok. Tutup &amp; buka ulang modal, atau coba lagi.</div>'
                )
                .show();
            $("#pp-view-items-body .pp-stock-cell").html(
                '<span class="text-danger">Gagal cek</span>'
            );
        },
    });
}

function openPpApproveModal(id) {
    if (!id) return;
    ppApprovePlanningId = id;
    $.ajax({
        url: "/getProductionPlanningDetail",
        method: "get",
        data: { production_planning_id: id },
        success: function (d) {
            if ((d.status || d.pp_status) !== "draft") {
                notifikasi("error", "Tidak bisa Release", "Hanya draft yang bisa di-release");
                return;
            }
            $("#pp-approve-code").text(d.pp_number || d.code || "—");
            ensurePpSkalaOptions(function(skalas) {
                $('#pp-release-items').html((d.items||[]).map(function(it,i){
                    var options = '<option value="">Pilih skala</option>';
                    skalas.forEach(function(sk){
                        var isSel = (it.production_skala_id && Number(it.production_skala_id) === Number(sk.production_skala_id)) ? ' selected' : '';
                        options += '<option value="'+Number(sk.production_skala_id)+'"'+isSel+'>'+$("<div>").text(ppSkalaOptionLabel(sk)).html()+'</option>';
                    });
                    var qtyText = (it.qty != null ? '<div class="small text-muted mt-0.5" style="font-size:11.5px;">Target: <strong class="text-dark">' + it.qty + ' ' + $("<div>").text(it.unit||it.unit_label||'').html() + '</strong></div>' : '');
                    return '<tr data-ppi="'+it.ppi_id+'">' +
                        '<td><div class="fw-bold text-dark" style="font-size:13.5px;">'+$("<div>").text(it.product||it.product_name).html()+'</div>' + qtyText + '</td>' +
                        '<td><select class="form-select pp-release-skala" id="pp-release-skala-'+i+'" style="width:100%;">'+options+'</select></td>' +
                        '<td><select class="form-select pp-release-pic" id="pp-release-pic-'+i+'" style="width:100%;"><option value=""></option></select></td>' +
                        '<td><select class="form-select pp-release-armada" id="pp-release-armada-'+i+'" style="width:100%;"></select></td>' +
                        '</tr>';
                }).join(''));
                var $modal = $("#modalApprovePlanning");
                $modal.modal("show");
                setTimeout(function () {
                    (d.items||[]).forEach(function(it,i){
                        var $skala = $("#pp-release-skala-" + i);
                        if ($skala.length && typeof $skala.select2 === "function") {
                            if ($skala.hasClass("select2-hidden-accessible")) {
                                $skala.select2("destroy");
                            }
                            $skala.select2({
                                width: "100%",
                                dropdownParent: $modal,
                                placeholder: "Pilih skala",
                                allowClear: true,
                            });
                            if (it.production_skala_id) {
                                $skala.val(String(it.production_skala_id)).trigger("change");
                            }
                        }
                        if (typeof autocompleteStaff === "function") {
                            autocompleteStaff("#pp-release-pic-" + i, $modal);
                            if (it.pic_staff_id) {
                                $("#pp-release-pic-" + i)
                                    .append(
                                        new Option(
                                            it.pic_name || "PIC #" + it.pic_staff_id,
                                            it.pic_staff_id,
                                            true,
                                            true
                                        )
                                    )
                                    .trigger("change");
                            }
                        }
                        autocompleteCustomer('#pp-release-armada-'+i, $modal);
                        if (it.armada_customer_id) {
                            $('#pp-release-armada-'+i).append(new Option(it.armada_name || ('Armada #' + it.armada_customer_id), it.armada_customer_id, true, true)).trigger('change');
                        }
                    });
                }, 150);
            });
        },
        error: function (err) {
            if (typeof handlePermissionError === "function" && handlePermissionError(err)) return;
            notifikasi("error", "Gagal", "Tidak bisa load detail untuk release");
        },
    });
}

function submitPpApprove($btn) {
    if (!ppApprovePlanningId) return;
    if (typeof LoadingButton === "function") LoadingButton($btn);
    var btnHtml = '<i class="fe fe-check me-1"></i> Release to Production';
    $.ajax({
        url: "/approveProductionPlanning",
        method: "post",
        data: {
            production_planning_id: ppApprovePlanningId,
            items: $('#pp-release-items tr').map(function(){
                return {
                    ppi_id: $(this).data('ppi'),
                    production_skala_id: $(this).find('.pp-release-skala').val(),
                    pic_staff_id: $(this).find('.pp-release-pic').val(),
                    armada_customer_id: $(this).find('.pp-release-armada').val()
                };
            }).get(),
            _token: token,
        },
        success: function (res) {
            if (typeof ResetLoadingButton === "function") {
                ResetLoadingButton($btn, btnHtml);
            }
            if (res && res.status == 1) {
                $("#modalViewPlanning").modal("hide");
                $("#modalApprovePlanning").modal("hide");
                refreshPpTable();
                if (typeof refreshPpJobTable === "function") refreshPpJobTable();
                notifikasi(
                    "success",
                    "Berhasil Release",
                    (res.pp_number || "Planning") +
                        " Released to Production. Surat Perintah Kerja dapat dicetak melalui ikon printer atau Detail Planning."
                );
                return;
            }
            // Stock / resep / satuan — modal sama produksi lama
            if (
                res &&
                (res.shortages ||
                    res.code === "recipe_needs_update" ||
                    res.header === "Stock Tidak Mencukupi" ||
                    res.header === "Satuan Resep Tidak Aktif" ||
                    res.header === "Resep Tidak Ditemukan" ||
                    res.header === "Gagal Insert" ||
                    res.header === "Item Planning Tidak Valid" ||
                    res.header === "Gudang Tidak Valid" ||
                    res.header === "Tujuan Hasil Produksi Tidak Valid")
            ) {
                ppHandleStockCheckError(res, res.bom_id || null);
                return;
            }
            notifikasi(
                "error",
                "Gagal Release",
                (res && res.message) || "Gagal release"
            );
        },
        error: function (err) {
            if (typeof ResetLoadingButton === "function") {
                ResetLoadingButton($btn, btnHtml);
            }
            if (typeof handlePermissionError === "function" && handlePermissionError(err)) return;
            notifikasi("error", "Gagal Release", "Tidak bisa release planning");
        },
    });
}

function openPpWorkOrderModal(id) {
    if (!id) return;
    ppWorkOrderPlanningId = id;
    ensurePpSkalaOptions(function (skalas) {
        $.ajax({
            url: "/getProductionPlanningDetail",
            method: "get",
            data: { production_planning_id: id },
            success: function (d) {
                if ((d.status || d.pp_status) !== "released") {
                    notifikasi("error", "Tidak bisa Work Order", "Hanya status Released yang bisa masuk Work Order");
                    return;
                }
                $("#pp-wo-code").text(
                    d.spkp_number
                        ? d.spkp_number + " · " + (d.pp_number || d.code || "")
                        : d.pp_number || d.code || "—"
                );
                var skalaOpts = '<option value="">Pilih skala...</option>';
                skalas.forEach(function (m) {
                    skalaOpts +=
                        '<option value="' +
                        m.production_skala_id +
                        '">' +
                        $("<div>").text(ppSkalaOptionLabel(m)).html() +
                        "</option>";
                });
                var body = "";
                (d.items || []).forEach(function (it, idx) {
                    body +=
                        '<tr data-ppi-id="' +
                        it.ppi_id +
                        '">' +
                        "<td>" +
                        $("<div>").text(it.product || it.product_name || "—").html() +
                        '<div class="small text-muted font-monospace">' +
                        $("<div>").text(it.sku || "").html() +
                        "</div></td>" +
                        '<td class="text-end">' +
                        ppFormatNumber(it.qty) +
                        "</td>" +
                        "<td>" +
                        $("<div>").text(it.unit || "—").html() +
                        "</td>" +
                        '<td><select class="form-select form-select-sm pp-wo-skala" id="pp_wo_skala_' +
                        idx +
                        '" style="width:100%;">' +
                        skalaOpts +
                        "</select></td>" +
                        '<td><select class="form-select form-select-sm pp-wo-pic" id="pp_wo_pic_' +
                        idx +
                        '" style="width:100%;"><option value=""></option></select></td>' +
                        '<td><select class="form-select form-select-sm pp-wo-armada" id="pp_wo_armada_' +
                        idx +
                        '" style="width:100%;"><option value=""></option></select></td>' +
                        "</tr>";
                });
                $("#pp-wo-items-body").html(
                    body || '<tr><td colspan="6" class="text-center text-muted">Tidak ada item</td></tr>'
                );
                $("#modalWorkOrderPlanning").modal("show");
                setTimeout(function () {
                    var $parent = $("#modalWorkOrderPlanning");
                    (d.items || []).forEach(function (it, idx) {
                        var $skala = $("#pp_wo_skala_" + idx);
                        if ($skala.length && typeof $skala.select2 === "function") {
                            if ($skala.hasClass("select2-hidden-accessible")) {
                                $skala.select2("destroy");
                            }
                            $skala.select2({
                                width: "100%",
                                dropdownParent: $parent,
                                placeholder: "Pilih skala...",
                                allowClear: true,
                            });
                        }
                        $skala.val(it.production_skala_id).trigger("change");
                        if (typeof autocompleteStaff === "function") {
                            autocompleteStaff("#pp_wo_pic_" + idx, $parent);
                            if (it.pic_staff_id) {
                                $("#pp_wo_pic_" + idx)
                                    .append(
                                        new Option(
                                            it.pic_name || "PIC #" + it.pic_staff_id,
                                            it.pic_staff_id,
                                            true,
                                            true
                                        )
                                    )
                                    .trigger("change");
                            }
                        }
                        if (typeof autocompleteCustomer === "function") {
                            autocompleteCustomer("#pp_wo_armada_" + idx, $parent);
                            if(it.armada_customer_id) $("#pp_wo_armada_"+idx).append(new Option(it.armada_label||it.armada_name||("Armada #"+it.armada_customer_id),it.armada_customer_id,true,true)).trigger("change");
                        }
                    });
                }, 200);
            },
            error: function (err) {
                if (typeof handlePermissionError === "function" && handlePermissionError(err)) return;
                notifikasi("error", "Gagal", "Tidak bisa load detail untuk Work Order");
            },
        });
    });
}

function submitPpWorkOrder($btn) {
    if (!ppWorkOrderPlanningId) return;
    var items = [];
    var valid = true;
    $("#pp-wo-items-body tr[data-ppi-id]").each(function () {
        var $tr = $(this);
        var ppiId = parseInt($tr.attr("data-ppi-id"), 10);
        var skalaId = $tr.find(".pp-wo-skala").val();
        var picId = $tr.find(".pp-wo-pic").val();
        var armadaId = $tr.find(".pp-wo-armada").val();
        if (!skalaId || !picId) {
            valid = false;
            $tr.find("select").each(function () {
                if (!$(this).val()) $(this).addClass("is-invalid");
                else $(this).removeClass("is-invalid");
            });
            return;
        }
        items.push({
            ppi_id: ppiId,
            production_skala_id: parseInt(skalaId, 10),
            pic_staff_id: parseInt(picId, 10),
            armada_customer_id: parseInt(armadaId, 10) || null,
        });
    });
    if (!valid || !items.length) {
        notifikasi("error", "Validasi Gagal", "Isi Skala, PIC, dan Armada untuk semua item");
        return;
    }
    if (typeof LoadingButton === "function") LoadingButton($btn);
    $.ajax({
        url: "/assignProductionPlanningWorkOrder",
        method: "post",
        data: {
            production_planning_id: ppWorkOrderPlanningId,
            items: items,
            _token: token,
        },
        success: function (res) {
            if (typeof ResetLoadingButton === "function") {
                ResetLoadingButton($btn, '<i class="fe fe-check me-1"></i> Simpan Work Order');
            }
            if (res && res.status === -1) {
                notifikasi("error", "Gagal Work Order", res.message || "Gagal simpan Work Order");
                return;
            }
            $("#modalWorkOrderPlanning").modal("hide");
            refreshPpTable();
            if (typeof refreshPpJobTable === "function") refreshPpJobTable();
            // Setelah assign → Job Order (1 baris per PIC)
            var $jobTab = $('button[data-bs-target="#pp-pane-job"]');
            if ($jobTab.length) $jobTab.tab("show");
            var woCount = (res.work_orders || []).length;
            notifikasi(
                "success",
                "In Production",
                (res.pp_number || "Planning") +
                    " masuk In Production" +
                    (woCount ? " · " + woCount + " Work Order (per PIC)" : "")
            );
            openPpWorkOrderPrints(res.work_orders || []);
        },
        error: function (err) {
            if (typeof ResetLoadingButton === "function") {
                ResetLoadingButton($btn, '<i class="fe fe-check me-1"></i> Simpan Work Order');
            }
            if (typeof handlePermissionError === "function" && handlePermissionError(err)) return;
            var msg =
                (err && err.responseJSON && err.responseJSON.message) ||
                "Tidak bisa simpan Work Order";
            notifikasi("error", "Gagal Work Order", msg);
        },
    });
}
