/**
 * Work Order execution — modal di Production Planning (embed) atau monitor fullscreen.
 * Alur: Sudah Diproduksi → Form Gudang → ACC Ops → ACC QC → stok + Tally.
 */
var woTable = null;
var woDetail = null;
var woRequestId = null;
var woBusy = false;

function woEscape(value) {
    return $("<div>")
        .text(value == null ? "" : value)
        .html();
}
function woKey() {
    return "wo-" + Date.now() + "-" + Math.random().toString(36).slice(2);
}
function woState(state) {
    return (
        {
            inprod: "In Production",
            done: "Completed",
        }[state] || state
    );
}

function woFgStatusLabel(status) {
    return (
        {
            awaiting_ops: "Menunggu ACC Kepala Operasional",
            awaiting_qc: "Menunggu ACC Staf QC Gudang",
            approved: "Disetujui — stok masuk & Tally siap",
            cancelled: "Dibatalkan",
        }[status] || status || "—"
    );
}

function woRefreshLists() {
    if (woTable) woTable.ajax.reload(null, false);
    if (typeof refreshPpJobTable === "function") refreshPpJobTable();
}

function woFgDoc(d) {
    var list = (d && d.documents) || [];
    for (var i = list.length - 1; i >= 0; i--) {
        if (list[i].type === "warehouse" && list[i].document_status !== "cancelled") {
            return list[i];
        }
    }
    return null;
}

function woMatStatusLabel(status) {
    return (
        {
            awaiting_pic: "Draft",
            awaiting_ops: "Menunggu ACC Kepala Ops",
            awaiting_qc: "Menunggu ACC QC",
            approved: "Disetujui (stok potong)",
            cancelled: "Dibatalkan",
        }[status] || status || "—"
    );
}

function woMatStatusBadge(status) {
    var map = {
        awaiting_pic: { text: "Draft", cls: "bg-light text-dark border" },
        awaiting_ops: { text: "Menunggu ACC Ops", cls: "bg-warning-subtle text-warning border border-warning-subtle" },
        awaiting_qc: { text: "Menunggu ACC QC", cls: "bg-info-subtle text-info border border-info-subtle" },
        approved: { text: "Disetujui (Stok Potong)", cls: "bg-success-subtle text-success border border-success-subtle" },
        cancelled: { text: "Dibatalkan", cls: "bg-danger-subtle text-danger border border-danger-subtle" },
    };
    var s = map[status] || { text: status || "—", cls: "bg-light text-muted border" };
    return '<span class="badge ' + s.cls + ' fw-semibold" style="font-size:11px;padding:5px 8px;">' + woEscape(s.text) + '</span>';
}

function woParseDate(str) {
    if (!str) return null;
    var m = String(str).match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})(?::(\d{2}))?/);
    if (m) {
        return {
            year: m[1],
            month: parseInt(m[2], 10),
            day: m[3],
            hours: m[4],
            minutes: m[5],
            seconds: m[6] || "00"
        };
    }
    try {
        var d = new Date(str);
        if (isNaN(d.getTime())) return null;
        return {
            year: d.getFullYear(),
            month: d.getMonth() + 1,
            day: String(d.getDate()).padStart(2, "0"),
            hours: String(d.getHours()).padStart(2, "0"),
            minutes: String(d.getMinutes()).padStart(2, "0"),
            seconds: String(d.getSeconds()).padStart(2, "0")
        };
    } catch (e) {
        return null;
    }
}

function woFormatDateOnly(str) {
    var p = woParseDate(str);
    if (!p) return str || "—";
    var months = ["", "Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];
    return p.day + " " + (months[p.month] || p.month) + " " + p.year;
}

function woFormatTimeOnly(str) {
    var p = woParseDate(str);
    if (!p) return "";
    return p.hours + ":" + p.minutes + " WIB";
}

function woFormatDateTimeFull(str) {
    var p = woParseDate(str);
    if (!p) return str || "—";
    var months = ["", "Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];
    return p.day + " " + (months[p.month] || p.month) + " " + p.year + ", " + p.hours + ":" + p.minutes + " WIB";
}

function woSetMatFormVisible() {
    return;
}

var woMatMode = null; // 'ambil' | 'acc'

function woShowMatMode(mode) {
    woMatMode = mode;
    var ambil = mode === "ambil";
    $("#wo-mat-ambil-panel").toggle(ambil);
    $("#wo-mat-acc-panel").toggle(!ambil);
    $("#wo-mat-save").toggle(ambil && !window.woMonitor);
    $("#wo-mat-print").hide();
    $("#wo-mat-acc-wrap").empty();
    $("#wo-mat-header-icon")
        .removeClass("fe-package fe-check-circle")
        .addClass(ambil ? "fe-package" : "fe-check-circle");
}

/** Modal Ambil Bahan — form qty dari resep WO (ikon package Job Order). */
function woOpenMatTake(id, show) {
    $.get("/productionWorkOrders/" + Number(id))
        .done(function (d) {
            var editable =
                typeof hasAccessAction === "function"
                    ? hasAccessAction("Produksi", "edit")
                    : true;
            d.can_materials = !!(d.can_materials && editable);
            woDetail = d;
            woShowMatMode("ambil");

            var woNum = (d.wo && d.wo.wo_number) || "—";
            $("#wo-mat-title").text("Ambil Bahan — " + woNum);
            $("#wo-mat-subtitle").text(
                [d.pic || "—", d.pp || "", d.wo && d.wo.production_line]
                    .filter(Boolean)
                    .join(" · ")
            );

            $("#wo-mat-ambil-meta").html(
                '<div class="wo-summary-item">' +
                    '<div class="wo-summary-icon"><i class="fe fe-clipboard"></i></div>' +
                    '<div class="wo-summary-info"><div class="lbl">Work Order</div>' +
                    '<div class="val font-monospace">' +
                    woEscape(woNum) +
                    "</div></div></div>" +
                    '<div class="wo-summary-item">' +
                    '<div class="wo-summary-icon"><i class="fe fe-user"></i></div>' +
                    '<div class="wo-summary-info"><div class="lbl">PIC</div>' +
                    '<div class="val">' +
                    woEscape(d.pic || "—") +
                    "</div></div></div>" +
                    '<div class="wo-summary-item">' +
                    '<div class="wo-summary-icon"><i class="fe fe-file-text"></i></div>' +
                    '<div class="wo-summary-info"><div class="lbl">Planning</div>' +
                    '<div class="val font-monospace">' +
                    woEscape(d.pp || "—") +
                    "</div></div></div>"
            );

            $("#wo-mat-save").toggle(!!d.can_materials && !window.woMonitor);
            $("#wo-mat-rows").html(
                '<tr><td colspan="6" class="text-center text-muted py-4">' +
                    '<span class="spinner-border spinner-border-sm me-2"></span>Memuat resep…</td></tr>'
            );

            if (show !== false) $("#modalWoMaterials").modal("show");
            woLoadMatRecipe(Number(id)).fail(woError);
            if (typeof feather !== "undefined") feather.replace();
        })
        .fail(woError);
}

/** Modal ACC 1 request STB — qty bahan yang diambil PIC. */
function woOpenMatAccRequest(doc) {
    if (!doc || !doc.id) return;
    woDetail = woDetail || {};
    woDetail._accDoc = doc;
    if (doc.wo_id && (!woDetail.wo || !woDetail.wo.production_work_order_id)) {
        woDetail.wo = { production_work_order_id: Number(doc.wo_id) };
    }
    woShowMatMode("acc");

    $("#wo-mat-title").text("ACC Bahan — " + (doc.number || "STB"));
    var subParts = [];
    if (doc.wo_number) subParts.push("WO: " + doc.wo_number);
    if (doc.pp_number) subParts.push("PP: " + doc.pp_number);
    if (doc.pic) subParts.push("PIC: " + doc.pic);
    $("#wo-mat-subtitle").text(subParts.length ? subParts.join(" • ") : "—");

    var submittedTime = doc.confirmed_at
        ? woFormatDateTimeFull(doc.confirmed_at)
        : doc.created_at
          ? woFormatDateTimeFull(doc.created_at)
          : "—";

    $("#wo-mat-meta").html(
        '<div class="wo-summary-item">' +
            '<div class="wo-summary-icon"><i class="fe fe-file-text"></i></div>' +
            '<div class="wo-summary-info">' +
            '<div class="lbl">No. STB</div>' +
            '<div class="val font-monospace text-primary">' +
            woEscape(doc.number || "—") +
            "</div>" +
            "</div>" +
            "</div>" +
            '<div class="wo-summary-item">' +
            '<div class="wo-summary-icon"><i class="fe fe-user"></i></div>' +
            '<div class="wo-summary-info">' +
            '<div class="lbl">PIC Pengambil</div>' +
            '<div class="val">' +
            woEscape(doc.pic || "—") +
            "</div>" +
            "</div>" +
            "</div>" +
            '<div class="wo-summary-item">' +
            '<div class="wo-summary-icon"><i class="fe fe-clipboard"></i></div>' +
            '<div class="wo-summary-info">' +
            '<div class="lbl">Work Order</div>' +
            '<div class="val font-monospace">' +
            woEscape(doc.wo_number || "—") +
            "</div>" +
            "</div>" +
            "</div>" +
            '<div class="wo-summary-item">' +
            '<div class="wo-summary-icon"><i class="fe fe-calendar"></i></div>' +
            '<div class="wo-summary-info">' +
            '<div class="lbl">Planning</div>' +
            '<div class="val font-monospace">' +
            woEscape(doc.pp_number || "—") +
            "</div>" +
            "</div>" +
            "</div>" +
            '<div class="wo-summary-item">' +
            '<div class="wo-summary-icon"><i class="fe fe-clock"></i></div>' +
            '<div class="wo-summary-info">' +
            '<div class="lbl">Waktu Diajukan</div>' +
            '<div class="val" style="font-size:12px;">' +
            woEscape(submittedTime) +
            "</div>" +
            "</div>" +
            "</div>" +
            '<div class="wo-summary-item">' +
            '<div class="wo-summary-icon"><i class="fe fe-check-circle"></i></div>' +
            '<div class="wo-summary-info">' +
            '<div class="lbl">Status Tahap</div>' +
            '<div class="val">' +
            woMatStatusBadge(doc.stage) +
            "</div>" +
            "</div>" +
            "</div>"
    );

    if ($.fn.DataTable.isDataTable("#tableWoMatAcc")) {
        $("#tableWoMatAcc").DataTable().clear().destroy();
    }
    var $tbl = $("#tableWoMatAcc");
    // Lepas sisa wrapper DT supaya tidak nyangkut "Memuat data..."
    if ($tbl.parent().hasClass("dataTables_wrapper") || $tbl.closest(".dataTables_wrapper").length) {
        $tbl.closest(".dataTables_wrapper").before($tbl.detach());
        $(".dataTables_wrapper", "#modalWoMaterials").remove();
        $(".table-responsive", "#modalWoMaterials").first().append($tbl);
    }
    var $body = $("#wo-mat-log-body").empty();
    var items = doc.items || [];
    $("#wo-mat-items-count").text(items.length + " Bahan");

    if (!items.length) {
        $body.html(
            '<tr><td colspan="5" class="text-center text-muted py-4"><i class="fe fe-inbox d-block mb-1 fs-24 opacity-50"></i>Tidak ada rincian bahan.</td></tr>'
        );
    } else {
        items.forEach(function (it, idx) {
            var req = it.requested_qty != null ? it.requested_qty : "—";
            var rec = it.received_qty != null ? it.received_qty : null;
            var prodName = it.product_name || "—";
            var prodCode = it.product_code || it.code || "";
            var unit = it.unit_label || it.unit_name || "—";

            var qcStatusHtml = "";
            if (rec != null) {
                qcStatusHtml = '<div class="d-inline-flex flex-column align-items-center">' +
                    '<span class="fw-bold text-success" style="font-size:14px;">' + woEscape(String(rec)) + '</span>' +
                    '<span class="badge bg-success-subtle text-success border border-success-subtle mt-1" style="font-size:10px;padding:2px 6px;"><i class="fe fe-check me-1"></i>Sesuai</span>' +
                    '</div>';
            } else if (doc.stage === "awaiting_qc") {
                qcStatusHtml = '<span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1" style="font-size:11px;"><i class="fe fe-clock me-1"></i>Menunggu QC</span>';
            } else if (doc.stage === "awaiting_ops") {
                qcStatusHtml = '<span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1" style="font-size:11px;"><i class="fe fe-hourglass me-1"></i>Menunggu Ops</span>';
            } else {
                qcStatusHtml = '<span class="text-muted">—</span>';
            }

            $body.append(
                '<tr>' +
                    '<td class="text-center text-muted fw-semibold">' + (idx + 1) + '</td>' +
                    '<td>' +
                        '<div class="d-flex align-items-center gap-2">' +
                            '<span class="d-inline-flex align-items-center justify-content-center rounded bg-light text-primary border" style="width:30px;height:30px;font-size:13px;flex-shrink:0;">' +
                                '<i class="fe fe-package"></i>' +
                            '</span>' +
                            '<div>' +
                                '<div class="fw-bold text-dark" style="font-size:13px;">' + woEscape(prodName) + '</div>' +
                                (prodCode ? '<div class="text-muted font-monospace" style="font-size:11px;">' + woEscape(prodCode) + '</div>' : '') +
                            '</div>' +
                        '</div>' +
                    '</td>' +
                    '<td class="text-center">' +
                        '<span class="badge bg-light text-secondary border fw-semibold px-2 py-1" style="font-size:11px;">' +
                            woEscape(unit) +
                        '</span>' +
                    '</td>' +
                    '<td class="text-center">' +
                        '<span class="fw-bold text-dark" style="font-size:14px;">' + woEscape(String(req)) + '</span>' +
                    '</td>' +
                    '<td class="text-center">' +
                        qcStatusHtml +
                    '</td>' +
                '</tr>'
            );
        });
    }

    // Dynamic Hint Text
    if (doc.stage === "awaiting_ops") {
        $("#wo-mat-acc-hint").html('<i class="fe fe-info me-1 text-primary"></i>Menunggu persetujuan Kepala Operasional sebelum diverifikasi QC.');
    } else if (doc.stage === "awaiting_qc") {
        $("#wo-mat-acc-hint").html('<i class="fe fe-alert-circle me-1 text-warning"></i>Menunggu ACC QC. Stok gudang akan dipotong otomatis setelah ACC.');
    } else if (doc.stage === "approved") {
        $("#wo-mat-acc-hint").html('<i class="fe fe-check-circle me-1 text-success"></i>Permintaan telah disetujui penuh & stok gudang telah dipotong.');
    } else {
        $("#wo-mat-acc-hint").html('<i class="fe fe-info me-1 text-primary"></i>Stok gudang akan dipotong otomatis setelah ACC QC.');
    }

    // Render Timeline Steps
    var step1Html = '<div class="col-12 col-md-4">' +
        '<div class="wo-timeline-card">' +
            '<div class="icon-box" style="background:#dcfce7;color:#16a34a;"><i class="fe fe-user-check"></i></div>' +
            '<div class="min-w-0 flex-grow-1">' +
                '<div class="step-title">1. Diajukan (PIC)</div>' +
                '<div class="step-val text-truncate text-success">' + woEscape(doc.pic || "PIC") + '</div>' +
                '<div class="text-muted text-truncate" style="font-size:11px;">' + woEscape(submittedTime) + '</div>' +
            '</div>' +
        '</div>' +
    '</div>';

    var step2Icon = '#f1f5f9';
    var step2Color = '#64748b';
    var step2IconClass = 'fe-minus';
    var step2Status = 'Menunggu';
    var step2StatusClass = 'text-muted';
    var step2Time = 'Belum diproses';

    if (doc.ops_approved_at || doc.stage === 'awaiting_qc' || doc.stage === 'approved') {
        step2Icon = '#dcfce7';
        step2Color = '#16a34a';
        step2IconClass = 'fe-check';
        step2Status = 'Disetujui Ops';
        step2StatusClass = 'text-success';
        step2Time = doc.ops_approved_at ? woFormatDateTimeFull(doc.ops_approved_at) : 'Sudah di-ACC';
    } else if (doc.stage === 'awaiting_ops') {
        step2Icon = '#fef3c7';
        step2Color = '#d97706';
        step2IconClass = 'fe-clock';
        step2Status = 'Menunggu Persetujuan';
        step2StatusClass = 'text-warning';
        step2Time = 'Dalam antrean';
    }

    var step2Html = '<div class="col-12 col-md-4">' +
        '<div class="wo-timeline-card">' +
            '<div class="icon-box" style="background:' + step2Icon + ';color:' + step2Color + ';"><i class="fe ' + step2IconClass + '"></i></div>' +
            '<div class="min-w-0 flex-grow-1">' +
                '<div class="step-title">2. ACC Kepala Ops</div>' +
                '<div class="step-val text-truncate ' + step2StatusClass + '">' + step2Status + '</div>' +
                '<div class="text-muted text-truncate" style="font-size:11px;">' + step2Time + '</div>' +
            '</div>' +
        '</div>' +
    '</div>';

    var step3Icon = '#f1f5f9';
    var step3Color = '#64748b';
    var step3IconClass = 'fe-minus';
    var step3Status = 'Menunggu Tahap Ops';
    var step3StatusClass = 'text-muted';
    var step3Time = 'Belum diproses';

    if (doc.qc_approved_at || doc.stage === 'approved') {
        step3Icon = '#dcfce7';
        step3Color = '#16a34a';
        step3IconClass = 'fe-check-circle';
        step3Status = 'Stok Dipotong';
        step3StatusClass = 'text-success';
        step3Time = doc.qc_approved_at ? woFormatDateTimeFull(doc.qc_approved_at) : 'Selesai';
    } else if (doc.stage === 'awaiting_qc') {
        step3Icon = '#eff6ff';
        step3Color = '#2563eb';
        step3IconClass = 'fe-refresh-cw';
        step3Status = 'Menunggu ACC QC';
        step3StatusClass = 'text-primary';
        step3Time = 'Siap potong stok';
    }

    var step3Html = '<div class="col-12 col-md-4">' +
        '<div class="wo-timeline-card">' +
            '<div class="icon-box" style="background:' + step3Icon + ';color:' + step3Color + ';"><i class="fe ' + step3IconClass + '"></i></div>' +
            '<div class="min-w-0 flex-grow-1">' +
                '<div class="step-title">3. ACC QC (Potong Stok)</div>' +
                '<div class="step-val text-truncate ' + step3StatusClass + '">' + step3Status + '</div>' +
                '<div class="text-muted text-truncate" style="font-size:11px;">' + step3Time + '</div>' +
            '</div>' +
        '</div>' +
    '</div>';

    $("#wo-mat-timeline-steps").html(step1Html + step2Html + step3Html);

    var $print = $("#wo-mat-print");
    if (doc.print_url) {
        $print.attr("href", doc.print_url).show();
    } else {
        $print.hide();
    }

    var $acc = $("#wo-mat-acc-wrap").empty();
    if (!window.woMonitor && doc.can_ops) {
        $acc.append(
            '<button type="button" class="btn pg-btn-confirm wo-mat-acc-ops" data-doc="' +
                Number(doc.id) +
                '"><i class="fe fe-check me-1"></i>ACC Kepala Ops</button>'
        );
    }
    if (!window.woMonitor && doc.can_qc) {
        $acc.append(
            '<button type="button" class="btn pg-btn-confirm wo-mat-acc-qc" data-doc="' +
                Number(doc.id) +
                '"><i class="fe fe-check me-1"></i>ACC QC (Potong Stok)</button>'
        );
    }

    $("#modalWoMaterials").modal("show");
    if (typeof feather !== "undefined") feather.replace();
}

/** Refresh ACC modal setelah ACC Ops/QC (bukan Ambil Bahan). */
function woLoadMaterials(id, show) {
    $.get("/productionWorkOrders/" + Number(id))
        .done(function (d) {
            woDetail = d;
            var preferId =
                woDetail && woDetail._accDoc && woDetail._accDoc.id
                    ? Number(woDetail._accDoc.id)
                    : null;
            var docs = ((d && d.documents) || []).filter(function (doc) {
                return (
                    (doc.type === "material_issue" || doc.type === "material_return") &&
                    (doc.document_status === "awaiting_ops" ||
                        doc.document_status === "awaiting_qc")
                );
            });
            if (!docs.length) {
                if ($("#modalWoMaterials").hasClass("show") && woMatMode === "acc") {
                    $("#modalWoMaterials").modal("hide");
                }
                if (typeof refreshPpBahanTable === "function") refreshPpBahanTable();
                return;
            }
            var doc =
                (preferId &&
                    docs.find(function (x) {
                        return Number(x.id) === preferId;
                    })) ||
                docs[docs.length - 1];
            if (show !== false || ($("#modalWoMaterials").hasClass("show") && woMatMode === "acc")) {
                woOpenMatAccRequest({
                    id: doc.id,
                    wo_id: Number(id),
                    number: doc.number,
                    stage: doc.document_status,
                    pic: d.pic,
                    wo_number: d.wo && d.wo.wo_number,
                    pp_number: d.pp,
                    items: doc.items || [],
                    can_ops: !!d.can_ops && doc.document_status === "awaiting_ops",
                    can_qc: !!d.can_qc && doc.document_status === "awaiting_qc",
                    print_url: "/printProductionDocument/" + doc.id + "/form",
                    confirmed_at: doc.confirmed_at || doc.created_at || "",
                    ops_approved_at: doc.ops_approved_at || "",
                    qc_approved_at: doc.qc_approved_at || "",
                    line: (d.wo && d.wo.production_line) || "",
                });
            }
        })
        .fail(woError);
}

function woLoadMatRecipe(woId) {
    return $.get("/productionWorkOrders/" + Number(woId) + "/materialRecipe").then(
        function (res) {
            var rows = (res && res.items) || [];
            var html = "";
            if (!rows.length) {
                html =
                    '<tr><td colspan="6" class="text-muted text-center py-4">Tidak ada bahan di resep produk WO ini.</td></tr>';
            } else {
                rows.forEach(function (it, idx) {
                    var short =
                        Number(it.available) + 0.0001 < Number(it.suggest_qty);
                    html +=
                        '<tr data-sid="' +
                        it.supplies_id +
                        '" data-uid="' +
                        it.unit_id +
                        '">';
                    html +=
                        '<td><div class="fw-semibold text-dark">' +
                        woEscape(it.name) +
                        "</div>" +
                        (it.is_trading
                            ? '<div class="mt-1"><span class="badge-trading">Trading</span></div>'
                            : "") +
                        "</td>";
                    html +=
                        '<td class="text-center"><span class="badge bg-light text-dark border fw-semibold">' +
                        woEscape(it.unit || "—") +
                        "</span></td>";
                    html += '<td class="text-center fw-semibold text-secondary">' + it.recipe_qty + "</td>";
                    html += '<td class="text-center">' + it.taken_qty + "</td>";
                    html +=
                        '<td class="text-center ' +
                        (short ? "text-danger fw-bold" : "text-muted") +
                        '">' +
                        it.available +
                        (short ? ' <i class="fe fe-alert-circle text-danger ms-1" title="Stok gudang kurang"></i>' : '') +
                        "</td>";
                    html +=
                        '<td class="text-center">' +
                        '<input type="number" min="0" step="1" class="form-control form-control-sm text-center fw-semibold wo-mat-qty" ' +
                        'placeholder="0" style="max-width:90px;margin:0 auto;" value="' +
                        (it.suggest_qty > 0 ? it.suggest_qty : "") +
                        '" data-avail="' +
                        it.available +
                        '" data-idx="' +
                        idx +
                        '"></td></tr>';
                });
            }
            $("#wo-mat-rows").html(html);
            var miss = (res && res.missing_bom) || [];
            $("#wo-mat-hint").text(
                miss.length
                    ? "Beberapa produk belum punya resep: " +
                          miss.join(", ") +
                          ". ACC: Kepala Ops → QC."
                    : "Qty disarankan dari resep WO. ACC: Kepala Ops → QC (stok potong setelah QC)."
            );
            if (typeof feather !== "undefined") feather.replace();
        }
    );
}

function woRenderFgPanel(d) {
    var fg = woFgDoc(d);
    var $panel = $("#wo-fg-panel");
    var $actions = $("#wo-fg-actions").empty();
    if (!fg) {
        $panel.hide();
        return;
    }
    $panel.show();
    $("#wo-fg-number").text(fg.number ? "(" + fg.number + ")" : "");
    $("#wo-fg-status").text(woFgStatusLabel(fg.document_status));
    var times = [];
    if (fg.confirmed_at) times.push("Konfirmasi PIC: " + fg.confirmed_at);
    if (fg.ops_approved_at) times.push("ACC Ops: " + fg.ops_approved_at);
    if (fg.qc_approved_at) times.push("ACC QC: " + fg.qc_approved_at);
    if (fg.warehouse_at) {
        times.push("Jam gudang: " + fg.warehouse_at);
    } else {
        times.push("Jam gudang: menunggu approval lengkap");
    }
    $("#wo-fg-times").html(times.map(woEscape).join(" · "));

    $actions.append(
        '<a class="btn wo-print-btn btn-sm" href="/printProductionDocument/' +
            Number(fg.id) +
            '/form" target="_blank" rel="noopener"><i class="fe fe-printer"></i> Print Form Gudang</a>'
    );
    if (fg.tally_number) {
        $actions.append(
            '<a class="btn wo-print-btn btn-sm" href="/printProductionDocument/' +
                Number(fg.id) +
                '/tally" target="_blank" rel="noopener"><i class="fe fe-printer"></i> Print Tally</a>'
        );
    }
    if (!window.woMonitor && fg.document_status === "awaiting_ops" && d.can_ops) {
        $actions.append(
            '<button type="button" class="btn btn-sm pg-btn-confirm" id="wo-acc-ops" data-doc="' +
                Number(fg.id) +
                '"><i class="fe fe-check me-1"></i> ACC Kepala Ops</button>'
        );
    }
    if (!window.woMonitor && fg.document_status === "awaiting_qc" && d.can_qc) {
        $actions.append(
            '<button type="button" class="btn btn-sm pg-btn-confirm" id="wo-acc-qc" data-doc="' +
                Number(fg.id) +
                '"><i class="fe fe-check me-1"></i> ACC Staf QC Gudang</button>'
        );
    }
}

$(function () {
    window.woEmbed = !!window.woEmbed;
    window.woMonitor = !!window.woMonitor;

    if ($("#tableWo").length) {
        woTable = $("#tableWo").DataTable({
            serverSide: true,
            processing: true,
            deferRender: true,
            pageLength: 20,
            lengthMenu: [10, 20, 50],
            searchDelay: 350,
            order: [[1, "desc"]],
            ajax: {
                url: "/getProductionWorkOrders",
                data: function (d) {
                    d.state = $("#wo-filter-state").val();
                    d.line = $("#wo-filter-line").val();
                    d.date_from = $("#wo-filter-from").val();
                    d.date_to = $("#wo-filter-to").val();
                },
                dataSrc: function (res) {
                    var selected = $("#wo-filter-line").val();
                    $("#wo-filter-line").html('<option value="">Semua lini</option>');
                    (res.lines || []).forEach(function (line) {
                        $("#wo-filter-line").append(new Option(line, line));
                    });
                    $("#wo-filter-line").val(selected);
                    return res.data;
                },
                beforeSend: function () {
                    $("#tableWo-wrap").addClass("is-loading");
                },
                complete: function () {
                    $("#tableWo-wrap")
                        .removeClass("dt-pending is-loading")
                        .addClass("dt-ready");
                },
            },
            columns: [
                {
                    data: "code",
                    render: function (v, t, r) {
                        return (
                            woEscape(v) +
                            '<div class="small text-muted">' +
                            (r.spkp_number ? woEscape(r.spkp_number) + " · " : "") +
                            woEscape(r.pp_number) +
                            "</div>"
                        );
                    },
                },
                { data: "date", render: woEscape },
                { data: "pic", render: woEscape },
                { data: "line", render: woEscape },
                {
                    data: "state",
                    render: function (v) {
                        return woEscape(woState(v));
                    },
                },
                {
                    data: "id",
                    orderable: false,
                    searchable: false,
                    render: function (id) {
                        return (
                            '<button type="button" class="btn btn-outline-primary wo-open" data-id="' +
                            Number(id) +
                            '" title="Lihat Work Order"><i class="fe fe-eye"></i></button>'
                        );
                    },
                },
            ],
            drawCallback: function () {
                if (typeof feather !== "undefined") feather.replace();
            },
            initComplete: function () {
                $("#tableWo-wrap").removeClass("dt-pending").addClass("dt-ready");
            },
        });
        $("#wo-filter-state,#wo-filter-line,#wo-filter-from,#wo-filter-to").on(
            "change",
            function () {
                woTable.ajax.reload();
            }
        );
        setInterval(function () {
            if (
                !document.hidden &&
                !$("#modalWoExecution").hasClass("show") &&
                woTable
            ) {
                woTable.ajax.reload(null, false);
            }
        }, 30000);
    }

    $("#wo-fullscreen").on("click", function () {
        if (document.documentElement.requestFullscreen) {
            document.documentElement.requestFullscreen();
        }
    });
});

function woLoad(id, show) {
    $.get("/productionWorkOrders/" + Number(id))
        .done(function (d) {
            var editable = hasAccessAction("Produksi", "edit");
            d.can_report = d.can_report && editable;
            woDetail = d;
            woRequestId = woKey();
            $("#wo-title").text(
                d.wo.wo_number + " - " + woState(d.wo.execution_status)
            );
            $("#wo-subtitle").text(
                (d.pic || "") +
                    " | " +
                    d.pp +
                    (d.wo.production_line ? " | " + d.wo.production_line : "")
            );
            $("#wo-print").attr(
                "href",
                "/printProductionWorkOrder/" + Number(id)
            );
            var can =
                d.can_report &&
                !d.wo.production_completed_at &&
                !d.wo.closed_at;
            var html = "";
            d.items.forEach(function (it) {
                var pallet = Number(it.qty_per_pallet) > 0;
                html +=
                    '<tr data-ppi="' +
                    it.id +
                    '"><td>' +
                    '<div class="fw-semibold text-dark">' +
                    woEscape(it.name) +
                    "</div></td><td>" +
                    '<span class="fw-semibold">' +
                    it.target +
                    "</span> " +
                    woEscape(it.unit) +
                    "</td><td>" +
                    it.actual +
                    " " +
                    woEscape(it.unit) +
                    ' <strong class="' +
                    (it.result === "OK" ? "text-success" : "text-danger") +
                    '">' +
                    it.result +
                    "</strong>" +
                    (it.excess > 0
                        ? '<div class="text-success small">Kelebihan ' +
                          it.excess +
                          "</div>"
                        : "") +
                    '</td><td><input class="form-control wo-qty" type="number" min="0" step="any" ' +
                    (!can ? "disabled" : "") +
                    ' value="' +
                    (can && pallet && it.result !== "OK" ? "1" : "") +
                    '"></td><td><select class="form-select wo-unit" ' +
                    (!can ? "disabled" : "") +
                    ">";
                if (pallet) {
                    html +=
                        '<option value="pallet" selected>Pallet (1 = ' +
                        it.qty_per_pallet +
                        " " +
                        woEscape(it.pallet_unit || it.unit || "") +
                        ")</option>";
                }
                it.units.forEach(function (u) {
                    html +=
                        '<option value="' +
                        u.id +
                        '" ' +
                        (!pallet && u.id == it.unit_id ? "selected" : "") +
                        ">" +
                        woEscape(u.name) +
                        "</option>";
                });
                html += "</select></td></tr>";
            });
            $("#wo-output-rows").html(html);
            $("#wo-report").toggle(can && !window.woMonitor);
            woRenderFgPanel(d);
            if (show) $("#modalWoExecution").modal("show");
            if (typeof feather !== "undefined") feather.replace();
        })
        .fail(woError);
}

function woError(xhr) {
    notifikasi(
        "error",
        "Tidak dapat diproses",
        (xhr.responseJSON || {}).message || "Gagal memuat/menyimpan data."
    );
}

async function woConfirm(title, text) {
    var first = await Swal.fire({
        title: title,
        text: text,
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Lanjut",
        cancelButtonText: "Periksa lagi",
    });
    if (!first.isConfirmed) return false;
    var second = await Swal.fire({
        title: "Konfirmasi akhir",
        text: "Simpan atas nama Anda, dengan tanda tangan serta tanggal/jam sekarang?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Ya, konfirmasi",
        cancelButtonText: "Batal",
    });
    return second.isConfirmed;
}

async function woSend(id, action, payload, title, summary, successMsg) {
    if (woBusy) return;
    woBusy = true;
    try {
        if (!(await woConfirm(title, summary))) return;
        var res = await $.ajax({
            url: "/productionExecution/" + id + "/" + action,
            type: "POST",
            data: Object.assign({ _token: token }, payload),
        });
        var msg = successMsg;
        if (!msg) {
            if (res && res.tally_number) {
                msg = "QC OK — stok masuk, Tally " + res.tally_number;
            } else if (res && res.wo_done) {
                msg = "Produksi selesai — Form Gudang terbit (menunggu ACC Ops).";
            } else {
                msg = "Konfirmasi berhasil dicatat.";
            }
        }
        notifikasi("success", "Tersimpan", msg);
        var woId =
            (woDetail && woDetail.wo && woDetail.wo.production_work_order_id) ||
            null;
        var matOpen = $("#modalWoMaterials").hasClass("show");
        if (matOpen && woMatMode === "ambil" && woId) {
            woOpenMatTake(woId, false);
        } else if (matOpen && woMatMode === "acc") {
            if (woId) {
                woLoadMaterials(woId, false);
            } else if (woDetail && woDetail._accDoc && woDetail._accDoc.wo_id) {
                woLoadMaterials(woDetail._accDoc.wo_id, false);
            } else {
                $("#modalWoMaterials").modal("hide");
            }
        } else if (woId) {
            woLoad(woId, false);
        }
        woRefreshLists();
        if (typeof refreshPpBahanTable === "function") refreshPpBahanTable();
        if (typeof refreshPpTable === "function") refreshPpTable();
        if (typeof refreshPpStageMiniTables === "function")
            refreshPpStageMiniTables();
    } catch (xhr) {
        woError(xhr);
    } finally {
        woBusy = false;
    }
}

$(document).on("click", ".wo-open, .btn-pp-wo-view", function (e) {
    e.preventDefault();
    woLoad($(this).data("id"), true);
});

$(document).on("click", ".btn-pp-wo-mat, .wo-open-mat", function (e) {
    e.preventDefault();
    woOpenMatTake($(this).data("id"), true);
});

$(document).on("click", ".btn-pp-bahan-view", function (e) {
    e.preventDefault();
    var raw = $(this).attr("data-doc-json") || "";
    var doc = null;
    try {
        doc = JSON.parse(decodeURIComponent(raw));
    } catch (err) {
        doc = null;
    }
    if (!doc || !doc.id) {
        notifikasi("error", "ACC Bahan", "Data request tidak valid.");
        return;
    }
    woOpenMatAccRequest(doc);
});
$("#wo-report").on("click", function () {
    var items = [];
    $("#wo-output-rows tr").each(function () {
        var q = $(this).find(".wo-qty").val();
        if (q !== "" && Number(q) > 0) {
            items.push({
                ppi_id: $(this).data("ppi"),
                qty: q,
                unit_id: $(this).find(".wo-unit").val(),
                pallet_number: $(this).find(".wo-pallet").val(),
            });
        }
    });
    if (!items.length) {
        notifikasi(
            "error",
            "Qty kosong",
            "Isi hasil produksi minimal satu barang."
        );
        return;
    }
    woSend(
        woDetail.wo.production_work_order_id,
        "report",
        { request_id: woRequestId, items: items },
        "Sudah Diproduksi",
        items.length +
            " baris hasil akan dicatat. Jika target tercapai, Form Gudang terbit (stok masuk setelah ACC Ops + QC)."
    );
});

$(document).on("click", "#wo-acc-ops", function () {
    var docId = Number($(this).data("doc"));
    if (!docId) return;
    woSend(
        docId,
        "ops",
        {},
        "ACC Kepala Operasional",
        "Form Gudang akan di-ACC. Setelah ini menunggu Staf QC Gudang.",
        "ACC Ops tersimpan. Menunggu Staf QC Gudang."
    );
});

$(document).on("click", "#wo-acc-qc", function () {
    var docId = Number($(this).data("doc"));
    if (!docId) return;
    woSend(
        docId,
        "qc",
        {},
        "ACC Staf QC Gudang",
        "Setelah ACC QC: stok masuk, jam gudang tercatat, dan Tally Produksi terbit.",
        null
    );
});

$(document).on("click", "#wo-mat-save", async function (e) {
    e.preventDefault();
    if (!woDetail || !woDetail.wo) return;
    var items = [];
    $("#wo-mat-rows tr[data-sid]").each(function () {
        var q = $(this).find(".wo-mat-qty").val();
        if (q === "" || Number(q) <= 0) return;
        items.push({
            supplies_id: $(this).data("sid"),
            unit_id: $(this).data("uid"),
            qty: q,
        });
    });
    if (!items.length) {
        notifikasi("error", "Qty kosong", "Isi qty ambil minimal satu bahan.");
        return;
    }
    await woSend(
        woDetail.wo.production_work_order_id,
        "material_issue",
        { request_id: woKey(), items: items },
        "Simpan Ambil Bahan",
        items.length +
            " baris bahan dicatat. Selanjutnya ACC Kepala Ops lalu QC untuk potong stok.",
        "Ambil bahan tercatat. Menunggu ACC di tab ACC Bahan."
    );
});

$(document).on("click", ".wo-mat-acc-ops", function () {
    var docId = Number($(this).data("doc"));
    if (!docId) return;
    woSend(
        docId,
        "ops",
        {},
        "ACC Kepala Operasional — Bahan",
        "Setelah ACC Ops, dokumen menunggu Staf QC.",
        "ACC Ops bahan OK. Menunggu QC."
    );
});

$(document).on("click", ".wo-mat-acc-qc", function () {
    var docId = Number($(this).data("doc"));
    if (!docId) return;
    // Dari tab ACC Bahan tidak ada woDetail — QC potong stok pakai qty requested di server.
    woSend(
        docId,
        "qc",
        {},
        "ACC QC Ambil Bahan",
        "Stok gudang akan dipotong sesuai qty yang diambil PIC.",
        "ACC QC bahan OK — stok dipotong."
    );
});