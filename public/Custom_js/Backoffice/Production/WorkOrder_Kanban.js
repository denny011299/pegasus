/**
 * Kanban WO fullscreen — board grup: status | pic | line.
 * Status: In Production → Need ACC → Completed.
 * Pin localStorage; aksi card via menu ⋮ (Pin / Print).
 */
var wokXhr = null;
var wokTimer = null;
var wokRawRows = [];
var wokGroupMode = "status";
var wokDateFrom = null;
var wokDateTo = null;

function wokEsc(v) {
    return $("<div>")
        .text(v == null ? "" : String(v))
        .html();
}

function wokWh() {
    return typeof window.activeWarehouseId !== "undefined"
        ? String(window.activeWarehouseId)
        : "0";
}

function wokLoadGroupMode() {
    try {
        var m = localStorage.getItem("wok.group.v1." + wokWh());
        if (m === "pic" || m === "line" || m === "status") return m;
    } catch (e) { /* ignore */ }
    return "status";
}

function wokSaveGroupMode(mode) {
    wokGroupMode =
        mode === "pic" || mode === "line" || mode === "status" ? mode : "status";
    try {
        localStorage.setItem("wok.group.v1." + wokWh(), wokGroupMode);
    } catch (e) { /* ignore */ }
    $(".wok-group-btn")
        .removeClass("is-active active")
        .filter('[data-wok-group="' + wokGroupMode + '"]')
        .addClass("is-active active");
}

function wokDefaultDateRange() {
    return {
        start: moment().startOf("month"),
        end: moment().startOf("day"),
    };
}

function wokPaintDateInput() {
    var $date = $("#wok_filter_date");
    if (!$date.length || typeof moment !== "function") return;
    if (!wokDateFrom || !wokDateTo) {
        $date.val("");
        return;
    }
    var startM = moment(wokDateFrom, "YYYY-MM-DD");
    var endM = moment(wokDateTo, "YYYY-MM-DD");
    if (!startM.isValid() || !endM.isValid()) {
        $date.val("");
        return;
    }
    $date.val(
        startM.format("DD-MM-YYYY") + " — " + endM.format("DD-MM-YYYY")
    );
    var drp = $date.data("daterangepicker");
    if (drp) {
        drp.setStartDate(startM.clone());
        drp.setEndDate(endM.clone());
    }
}

function wokSyncUrl() {
    try {
        var u = new URL(window.location.href);
        if (wokDateFrom) u.searchParams.set("date_from", wokDateFrom);
        else u.searchParams.delete("date_from");
        if (wokDateTo) u.searchParams.set("date_to", wokDateTo);
        else u.searchParams.delete("date_to");
        u.searchParams.delete("tab");
        u.searchParams.delete("status");
        if (window.history && window.history.replaceState) {
            window.history.replaceState({}, "", u.pathname + u.search);
        }
    } catch (e) { /* ignore */ }
}

function wokSetDateRange(startYmd, endYmd, opts) {
    opts = opts || {};
    wokDateFrom = startYmd || null;
    wokDateTo = endYmd || null;
    wokPaintDateInput();
    wokSyncUrl();
    if (opts.reload !== false) {
        wokLoad(!!opts.soft);
    }
}

function wokInitDatePicker() {
    var $date = $("#wok_filter_date");
    if (!$date.length) return;
    if (typeof $date.daterangepicker !== "function" || typeof moment !== "function") {
        return;
    }

    var p = new URLSearchParams(window.location.search || "");
    var df = p.get("date_from");
    var dt = p.get("date_to");
    if (df && dt && moment(df, "YYYY-MM-DD", true).isValid() && moment(dt, "YYYY-MM-DD", true).isValid()) {
        wokDateFrom = df;
        wokDateTo = dt;
    } else {
        var def = wokDefaultDateRange();
        wokDateFrom = def.start.format("YYYY-MM-DD");
        wokDateTo = def.end.format("YYYY-MM-DD");
    }

    $date.daterangepicker({
        autoUpdateInput: false,
        autoApply: false,
        alwaysShowCalendars: true,
        showDropdowns: true,
        startDate: moment(wokDateFrom, "YYYY-MM-DD"),
        endDate: moment(wokDateTo, "YYYY-MM-DD"),
        locale: {
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
        },
        ranges: {
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
        },
    });

    $date.off(
        "apply.daterangepicker.wok cancel.daterangepicker.wok show.daterangepicker.wok"
    );
    $date.on("apply.daterangepicker.wok", function (ev, picker) {
        wokSetDateRange(
            picker.startDate.format("YYYY-MM-DD"),
            picker.endDate.format("YYYY-MM-DD"),
            { soft: true }
        );
    });
    $date.on("cancel.daterangepicker.wok", function () {
        var def = wokDefaultDateRange();
        wokSetDateRange(
            def.start.format("YYYY-MM-DD"),
            def.end.format("YYYY-MM-DD"),
            { soft: true }
        );
    });
    $date.on("show.daterangepicker.wok", function () {
        wokPaintDateInput();
    });

    wokPaintDateInput();
    wokSyncUrl();
}

function wokLoadPins() {
    try {
        var arr = JSON.parse(
            localStorage.getItem("wok.pins.v1." + wokWh()) || "[]"
        );
        return Array.isArray(arr)
            ? arr.map(String).filter(Boolean)
            : [];
    } catch (e) {
        return [];
    }
}

function wokSavePins(ids) {
    try {
        localStorage.setItem(
            "wok.pins.v1." + wokWh(),
            JSON.stringify(ids || [])
        );
    } catch (e) { /* ignore */ }
}

function wokIsPinned(id) {
    return wokLoadPins().indexOf(String(id)) !== -1;
}

function wokTogglePin(id) {
    id = String(id);
    var pins = wokLoadPins();
    var i = pins.indexOf(id);
    if (i === -1) pins.unshift(id);
    else pins.splice(i, 1);
    wokSavePins(pins);
}

function wokSortByPin(list) {
    var pins = wokLoadPins();
    var rank = {};
    pins.forEach(function (id, i) {
        rank[id] = i;
    });
    var pinned = [];
    var rest = [];
    (list || []).forEach(function (r) {
        if (Object.prototype.hasOwnProperty.call(rank, String(r.id))) {
            pinned.push(r);
        } else {
            rest.push(r);
        }
    });
    pinned.sort(function (a, b) {
        return rank[String(a.id)] - rank[String(b.id)];
    });
    return pinned.concat(rest);
}

function wokFormatDate(dStr) {
    if (!dStr || dStr === "—" || dStr === "-") return "—";
    var p = String(dStr).split("-");
    if (p.length === 3) {
        var months = [
            "Jan", "Feb", "Mar", "Apr", "Mei", "Jun",
            "Jul", "Agu", "Sep", "Okt", "Nov", "Des",
        ];
        var mIdx = parseInt(p[1], 10) - 1;
        if (mIdx >= 0 && mIdx < 12) {
            return parseInt(p[2], 10) + " " + months[mIdx] + " " + p[0];
        }
    }
    return dStr;
}

function wokGetInitials(name) {
    if (!name || name === "—" || name === "-") return "?";
    var parts = String(name).trim().split(/\s+/);
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return String(name).substring(0, 2).toUpperCase();
}

/** Mirror Job Order: done | awaiting_acc | inprod */
function wokStatusKey(row) {
    var state = String(row.state || row.execution_status || "");
    if (state === "done" || state === "completed" || row.closed_at) {
        return "done";
    }
    if (row.completed_at || row.production_completed_at) {
        return "awaiting_acc";
    }
    return "inprod";
}

function wokPicLabel(row) {
    var pic = String(row.pic || "").trim();
    if (!pic || pic === "-" || pic === "—") return "Tanpa PIC";
    return pic;
}

function wokLineLabel(row) {
    var line = String(row.line || "").trim();
    if (!line || line === "-" || line === "—") return "Tanpa Lini";
    return line;
}

function wokSlug(prefix, label) {
    return (
        prefix +
        ":" +
        String(label || "")
            .toLowerCase()
            .replace(/\s+/g, "_")
            .replace(/[^\w:-]/g, "")
    );
}

function wokBucketKey(row) {
    if (wokGroupMode === "pic") return wokSlug("pic", wokPicLabel(row));
    if (wokGroupMode === "line") return wokSlug("line", wokLineLabel(row));
    return wokStatusKey(row);
}

function wokStatusColumns() {
    return [
        {
            key: "inprod",
            title: "In Production",
            cls: "sloth-board-col--inprod",
        },
        {
            key: "awaiting_acc",
            title: "Need ACC",
            cls: "sloth-board-col--awaiting",
        },
        {
            key: "done",
            title: "Completed",
            cls: "sloth-board-col--done",
        },
    ];
}

function wokDynamicColumns(rows, mode) {
    var seen = {};
    var cols = [];
    (rows || []).forEach(function (r) {
        var label = mode === "pic" ? wokPicLabel(r) : wokLineLabel(r);
        var key = wokSlug(mode, label);
        if (seen[key]) return;
        seen[key] = true;
        cols.push({ key: key, title: label, cls: "" });
    });
    cols.sort(function (a, b) {
        var tail = mode === "pic" ? "Tanpa PIC" : "Tanpa Lini";
        if (a.title === tail) return 1;
        if (b.title === tail) return -1;
        return a.title.localeCompare(b.title, "id");
    });
    return cols;
}

function wokCardHtml(row) {
    var pp = row.spkp_number || row.pp_number || "—";
    var date = row.date ? wokFormatDate(row.date) : "—";
    var pic = wokPicLabel(row);
    var line = wokLineLabel(row);
    var sk = wokStatusKey(row);
    var printUrl = row.print_url || "";
    var pinned = wokIsPinned(row.id);
    var initials = wokGetInitials(pic);

    // 3 segmen: In Production = biru+abu+abu | Need ACC = biru+kuning+abu | Completed = hijau×3
    var progressBar =
        sk === "done"
            ? '<div class="sloth-card-progress-bar" title="Completed">' +
              '<span class="sloth-progress-seg seg-green"></span>' +
              '<span class="sloth-progress-seg seg-green"></span>' +
              '<span class="sloth-progress-seg seg-green"></span></div>'
            : sk === "awaiting_acc"
              ? '<div class="sloth-card-progress-bar" title="Need ACC">' +
                '<span class="sloth-progress-seg seg-blue"></span>' +
                '<span class="sloth-progress-seg seg-amber"></span>' +
                '<span class="sloth-progress-seg seg-gray"></span></div>'
              : '<div class="sloth-card-progress-bar" title="In Production">' +
                '<span class="sloth-progress-seg seg-blue"></span>' +
                '<span class="sloth-progress-seg seg-gray"></span>' +
                '<span class="sloth-progress-seg seg-gray"></span></div>';

    var statusPill =
        sk === "done"
            ? '<span class="sloth-pill sloth-pill--green">Completed</span>'
            : sk === "awaiting_acc"
              ? '<span class="sloth-pill sloth-pill--orange">Need ACC</span>'
              : '<span class="sloth-pill sloth-pill--blue">In Production</span>';

    var showPic = wokGroupMode !== "pic";
    var showLine = wokGroupMode !== "line" && line !== "Tanpa Lini";

    var menu =
        '<div class="sloth-card-menu">' +
        '<button type="button" class="sloth-card-menu-btn" aria-label="Aksi" title="Aksi">' +
        '<i class="fe fe-more-vertical"></i></button>' +
        '<div class="sloth-card-menu-drop">' +
        '<button type="button" class="sloth-card-menu-item wok-menu-pin" data-id="' +
        wokEsc(row.id) +
        '"><i class="fe fe-bookmark"></i> ' +
        (pinned ? "Lepas pin" : "Pin ke atas") +
        "</button>" +
        (printUrl
            ? '<a class="sloth-card-menu-item wok-menu-print" href="' +
              wokEsc(printUrl) +
              '" target="_blank" rel="noopener"><i class="fe fe-printer"></i> Print</a>'
            : "") +
        "</div></div>";

    return (
        '<div class="sloth-kanban-card wok-st-' +
        sk +
        (pinned ? " is-pinned" : "") +
        '" data-id="' +
        wokEsc(row.id) +
        '">' +
        progressBar +
        '<div class="sloth-card-header">' +
        '<div class="sloth-card-title-group">' +
        '<span class="sloth-card-check-icon"><i class="fe ' +
        (sk === "done" ? "fe-check-circle" : sk === "awaiting_acc" ? "fe-clock" : "fe-settings") +
        '"></i></span>' +
        '<div style="min-width:0">' +
        '<h5 class="sloth-card-code-title">' +
        wokEsc(row.code || "—") +
        "</h5>" +
        '<span class="wok-card-sub">Work Order</span></div>' +
        "</div>" +
        (pinned ? '<span class="sloth-pinned-badge" title="Disematkan"><i class="fe fe-bookmark"></i> Pin</span>' : "") +
        menu +
        "</div>" +
        '<div class="sloth-card-meta-line">' +
        '<div class="wok-meta"><i class="fe fe-file-text"></i><span class="wok-meta-k">SPKP</span><span class="wok-meta-v" title="' +
        wokEsc(pp) +
        '">' +
        wokEsc(pp) +
        "</span></div>" +
        (showLine && line !== pp
            ? '<div class="wok-meta"><i class="fe fe-git-commit"></i><span class="wok-meta-k">Lini</span><span class="wok-meta-v" title="' +
              wokEsc(line) +
              '">' +
              wokEsc(line) +
              "</span></div>"
            : "") +
        "</div>" +
        '<div class="sloth-pills-row">' +
        statusPill +
        "</div>" +
        '<div class="sloth-card-footer-row">' +
        '<div class="sloth-footer-left">' +
        '<span class="sloth-user-av" title="' +
        wokEsc(pic) +
        '">' +
        wokEsc(initials) +
        "</span>" +
        (showPic
            ? '<span class="pic-name" title="' + wokEsc(pic) + '">' + wokEsc(pic) + "</span>"
            : "") +
        "</div>" +
        '<div class="sloth-footer-right">' +
        '<span class="sloth-footer-date"><i class="fe fe-calendar"></i>' +
        wokEsc(date) +
        "</span>" +
        "</div></div></div>"
    );
}

function wokApplySearch() {
    var q = ($("#wok-search").val() || "").toLowerCase().trim();
    if (!q) {
        $(".sloth-kanban-card").show();
        return;
    }
    $(".sloth-kanban-card").each(function () {
        $(this).toggle($(this).text().toLowerCase().indexOf(q) !== -1);
    });
}

function wokPaintAvatars(rows) {
    var seen = {};
    var pics = [];
    (rows || []).forEach(function (r) {
        var p = wokPicLabel(r);
        if (p === "Tanpa PIC" || seen[p]) return;
        seen[p] = true;
        pics.push(p);
    });
    var html = "";
    var colors = ["av-1", "av-2", "av-3"];
    pics.slice(0, 3).forEach(function (p, i) {
        html +=
            '<span class="stack-avatar ' +
            colors[i] +
            '" title="' +
            wokEsc(p) +
            '">' +
            wokEsc(wokGetInitials(p)) +
            "</span>";
    });
    if (pics.length > 3) {
        html +=
            '<span class="stack-avatar av-more">+' +
            (pics.length - 3) +
            "</span>";
    }
    $(".sloth-avatar-stack").html(html || "");
}

function wokColShell(col) {
    return (
        '<div class="sloth-board-col ' +
        wokEsc(col.cls || "") +
        '" data-col="' +
        wokEsc(col.key) +
        '">' +
        '<div class="sloth-col-header-bar">' +
        '<div class="sloth-col-title-left">' +
        '<span class="sloth-col-dot-indicator"></span>' +
        '<h6 class="sloth-col-title-text">' +
        wokEsc(col.title) +
        "</h6>" +
        '<span class="sloth-col-count-num" data-wok-count="' +
        wokEsc(col.key) +
        '">0</span></div></div>' +
        '<div class="sloth-cards-container" data-wok-list="' +
        wokEsc(col.key) +
        '"></div></div>'
    );
}

function wokPaint(rows) {
    wokRawRows = rows || [];
    wokPaintAvatars(wokRawRows);

    // Ringkasan status di header (visual saja)
    var stat = { inprod: 0, awaiting_acc: 0, done: 0 };
    wokRawRows.forEach(function (r) {
        stat[wokStatusKey(r)]++;
    });
    Object.keys(stat).forEach(function (k) {
        $('[data-wok-stat="' + k + '"]').text(stat[k]);
    });

    var cols =
        wokGroupMode === "status"
            ? wokStatusColumns()
            : wokDynamicColumns(wokRawRows, wokGroupMode);

    if (!cols.length) {
        $("#wok-board").html(
            '<div class="sloth-empty-box w-100"><i class="fe fe-inbox fs-3 d-block mb-2 text-muted"></i>Tidak ada Work Order</div>'
        );
        return;
    }

    var buckets = {};
    cols.forEach(function (c) {
        buckets[c.key] = [];
    });
    wokRawRows.forEach(function (r) {
        var key = wokBucketKey(r);
        if (!buckets[key]) {
            buckets[key] = [];
            cols.push({
                key: key,
                title:
                    wokGroupMode === "pic"
                        ? wokPicLabel(r)
                        : wokGroupMode === "line"
                          ? wokLineLabel(r)
                          : key,
                cls: "",
            });
        }
        buckets[key].push(r);
    });

    if (wokGroupMode !== "status") {
        cols = wokDynamicColumns(wokRawRows, wokGroupMode);
    }

    $("#wok-board").html(cols.map(wokColShell).join(""));

    cols.forEach(function (col) {
        var list = wokSortByPin(buckets[col.key] || []);
        $('[data-wok-count="' + col.key + '"]').text(list.length);
        var $body = $('[data-wok-list="' + col.key + '"]');
        if (!list.length) {
            $body.html(
                '<div class="sloth-empty-box"><i class="fe fe-inbox fs-3 d-block mb-2 text-muted"></i>Kosong</div>'
            );
            return;
        }
        $body.html(list.map(wokCardHtml).join(""));
    });

    wokApplySearch();
    if (typeof feather !== "undefined") feather.replace();
}

function wokFetchState(state) {
    var data = {
        draw: 1,
        start: 0,
        length: 100,
        light: 1,
        state: state,
    };
    if (wokDateFrom) data.date_from = wokDateFrom;
    if (wokDateTo) data.date_to = wokDateTo;
    return $.ajax({
        url: "/getProductionWorkOrders",
        method: "get",
        cache: false,
        data: data,
    });
}

function wokLoad(soft) {
    if (wokXhr && typeof wokXhr.abort === "function") {
        try {
            wokXhr.abort();
        } catch (e) { /* ignore */ }
    }
    if (!soft) $("#wok-board").addClass("sloth-board-loading");
    var req = $.when(wokFetchState("inprod"), wokFetchState("done"));
    wokXhr = req;
    req
        .done(function (a, b) {
            var inprod = (a && a[0] && a[0].data) || [];
            var done = (b && b[0] && b[0].data) || [];
            wokPaint(inprod.concat(done));
        })
        .fail(function (xhr) {
            if (xhr && xhr.statusText === "abort") return;
            $("#wok-board").html(
                '<div class="sloth-empty-box text-danger w-100"><i class="fe fe-alert-circle fs-3 d-block mb-2"></i>Gagal memuat data</div>'
            );
        })
        .always(function () {
            $("#wok-board").removeClass("sloth-board-loading");
        });
}

function wokStartLive() {
    var wh = Number(window.PEGASUS_WAREHOUSE_ID) || 0;
    var ok =
        window.PpLive &&
        PpLive.subscribe(wh, function () {
            if (document.hidden) return;
            if ($(".modal.show").length) return;
            wokLoad(true);
        });
    if (ok) {
        document.addEventListener("visibilitychange", function () {
            if (!document.hidden) wokLoad(true);
        });
        return;
    }
    // Fallback hanya jika Reverb down.
    if (wokTimer) return;
    console.warn("WokLive: Reverb/Echo tidak siap — fallback poll");
    wokTimer = setInterval(function () {
        if (document.hidden) return;
        wokLoad(true);
    }, 5000);
    document.addEventListener("visibilitychange", function () {
        if (!document.hidden) wokLoad(true);
    });
}

$(function () {
    wokSaveGroupMode(wokLoadGroupMode());
    wokInitDatePicker();
    wokLoad(false);
    wokStartLive();

    $(document).on("input", "#wok-search", function () {
        wokApplySearch();
    });

    $(document).on("click", ".wok-group-btn", function (e) {
        e.preventDefault();
        var mode = $(this).attr("data-wok-group");
        if (!mode || mode === wokGroupMode) return;
        wokSaveGroupMode(mode);
        wokPaint(wokRawRows);
    });

    $(document).on("click", ".sloth-card-menu-btn", function (e) {
        e.preventDefault();
        e.stopPropagation();
        var $menu = $(this).closest(".sloth-card-menu");
        var open = $menu.hasClass("open");
        $(".sloth-card-menu").removeClass("open");
        if (!open) $menu.addClass("open");
    });

    $(document).on("click", function () {
        $(".sloth-card-menu").removeClass("open");
    });

    $(document).on("click", ".sloth-card-menu-drop", function (e) {
        e.stopPropagation();
    });

    $(document).on("click", ".wok-menu-pin", function (e) {
        e.preventDefault();
        e.stopPropagation();
        var id = $(this).attr("data-id");
        if (!id) return;
        wokTogglePin(id);
        $(".sloth-card-menu").removeClass("open");
        wokPaint(wokRawRows);
    });
});
