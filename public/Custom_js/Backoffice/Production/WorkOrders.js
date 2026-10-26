/**
 * Work Order execution — modal di Production Planning (embed) atau monitor fullscreen.
 * Alur: Sudah Diproduksi → Form Gudang → ACC QC → ACC Ops → stok + Tally.
 * Modal hasil produksi (#modalWoExecution) terpisah dari modal ACC FG (#modalWoFgAcc).
 */
var woTable = null;
var woDetail = null;
var woRequestId = null;
var woBusy = false;

/** Cegah race: tutup modal / buka modal lain → abort fetch lama, jangan panggil .modal('show') lagi. */
var woFetch = { token: 0, xhrDetail: null, xhrRecipe: null };

function woAbortFetches() {
    [woFetch.xhrDetail, woFetch.xhrRecipe].forEach(function (x) {
        if (x && x.readyState !== 4) {
            try {
                x.abort();
            } catch (e) { /* ignore */ }
        }
    });
    woFetch.xhrDetail = null;
    woFetch.xhrRecipe = null;
}

function woBeginFetch() {
    woAbortFetches();
    return ++woFetch.token;
}

function woFetchAlive(token) {
    return token > 0 && token === woFetch.token;
}

function woIsAbort(xhr) {
    return !!(xhr && (xhr.statusText === "abort" || xhr.readyState === 0));
}

function woAnyModalOpen() {
    return (
        $("#modalWoExecution").hasClass("show") ||
        $("#modalWoFgAcc").hasClass("show") ||
        $("#modalWoMaterials").hasClass("show")
    );
}

function woEscape(value) {
    return $("<div>")
        .text(value == null ? "" : value)
        .html();
}

/** Meta bar WO / PIC / Planning — tampil langsung (tanpa spinner). */
function woPaintMatMetaBar(sel, hint) {
    hint = hint || {};
    var woNum = hint.wo_number || hint.code || "—";
    var pic = hint.pic || "—";
    var pp = hint.pp || hint.pp_number || "—";
    $(sel).html(
        '<div class="d-flex flex-wrap align-items-center gap-4 w-100">' +
            "<div>" +
            '<span class="text-muted small text-uppercase fw-bold d-block" style="font-size:11px;">Work Order</span>' +
            '<span class="fw-bold text-dark fs-6 font-monospace">' +
            woEscape(woNum) +
            "</span></div>" +
            '<div class="border-start ps-4">' +
            '<span class="text-muted small text-uppercase fw-bold d-block" style="font-size:11px;">PIC</span>' +
            '<span class="fw-bold text-dark fs-6">' +
            woEscape(pic) +
            "</span></div>" +
            '<div class="border-start ps-4">' +
            '<span class="text-muted small text-uppercase fw-bold d-block" style="font-size:11px;">Planning</span>' +
            '<span class="fw-bold text-dark fs-6 font-monospace">' +
            woEscape(pp) +
            "</span></div></div>"
    );
}

/** Hint dari cache woDetail atau data-* tombol baris. */
function woHintFromDetailOrBtn(id, $btn) {
    var hint = {};
    if (
        woDetail &&
        woDetail.wo &&
        Number(woDetail.wo.production_work_order_id) === Number(id)
    ) {
        hint.wo_number = woDetail.wo.wo_number;
        hint.pic = woDetail.pic;
        hint.pp = woDetail.pp;
        hint.line = woDetail.wo.production_line;
    }
    if ($btn && $btn.length) {
        if ($btn.data("code")) hint.wo_number = hint.wo_number || $btn.data("code");
        if ($btn.data("pic")) hint.pic = hint.pic || $btn.data("pic");
        if ($btn.data("pp")) hint.pp = hint.pp || $btn.data("pp");
    }
    return hint;
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

function woFgStatusLabel(status, short) {
    var full = {
        awaiting_ops: "Menunggu ACC Kepala Operasional",
        awaiting_qc: "Menunggu ACC Staf QC Gudang",
        approved: "Disetujui — stok masuk & Tally siap",
        cancelled: "Dibatalkan",
    };
    var brief = {
        awaiting_ops: "Menunggu Ops",
        awaiting_qc: "Menunggu QC",
        approved: "Disetujui",
        cancelled: "Dibatalkan",
    };
    var map = short ? brief : full;
    return map[status] || status || "—";
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
        awaiting_pic: { text: "Draft", cls: "draft" },
        awaiting_ops: { text: "Menunggu ACC Ops", cls: "awaiting_ops" },
        awaiting_qc: { text: "Menunggu ACC QC", cls: "awaiting_qc" },
        approved: { text: "Disetujui (Stok Potong)", cls: "done" },
        cancelled: { text: "Dibatalkan", cls: "draft" },
    };
    var s = map[status] || { text: status || "—", cls: "draft" };
    return '<span class="pp-status ' + s.cls + '">' + woEscape(s.text) + '</span>';
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

var woMatMode = null; // 'ambil' | 'acc' | 'return'

function woShowMatMode(mode) {
    woMatMode = mode;
    var ambil = mode === "ambil";
    var ret = mode === "return";
    var acc = mode === "acc";
    var $modal = $("#modalWoMaterials");
    $modal.removeClass("pg-modal--confirm pg-modal--form");
    if (ambil || ret) {
        $modal.addClass("pg-modal--form");
    } else {
        $modal.addClass("pg-modal--confirm");
    }
    $("#wo-mat-ambil-panel").toggle(ambil);
    $("#wo-mat-acc-panel").toggle(acc);
    $("#wo-mat-return-panel").toggle(ret);
    $("#wo-mat-save").toggle(ambil && !window.woMonitor);
    $("#wo-mat-return-save").toggle(ret && !window.woMonitor);
    $("#wo-mat-print").hide();
    $("#wo-mat-acc-wrap").empty();
    $("#wo-mat-header-icon")
        .removeClass("fe-package fe-check-circle fe-corner-up-left")
        .addClass(ambil ? "fe-package" : ret ? "fe-corner-up-left" : "fe-check-circle");
}

/** Modal serah terima — header langsung; spinner hanya di isi tabel. */
function woOpenMatReturn(id, show, hint) {
    var token = woBeginFetch();
    hint = hint || woHintFromDetailOrBtn(id);
    woShowMatMode("return");
    var woNum = hint.wo_number || hint.code || "";
    $("#wo-mat-title").text(
        woNum ? "Serah Terima Bahan — " + woNum : "Serah Terima Bahan"
    );
    $("#wo-mat-subtitle").text(
        "Produksi selesai · isi QTY Kembali (sisa tidak terpakai)"
    );
    woPaintMatMetaBar("#wo-mat-return-meta", hint);
    $("#wo-mat-return-rows").html(
        '<tr><td colspan="5" class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2"></span>Memuat detail bahan…</td></tr>'
    );
    $("#wo-mat-return-save").hide();
    $("#wo-mat-print")
        .attr("href", "/printProductionMaterialHandover/" + Number(id))
        .show()
        .html('<i class="fe fe-printer me-1"></i> Cetak Serah Terima');
    if (show !== false) $("#modalWoMaterials").modal("show");
    if (typeof feather !== "undefined") feather.replace();

    woFetch.xhrDetail = $.get("/productionWorkOrders/" + Number(id))
        .done(function (d) {
            if (!woFetchAlive(token)) return;
            if (show !== false && !$("#modalWoMaterials").hasClass("show")) return;
            woDetail = d;
            if (!d.can_return_materials && !window.woMonitor) {
                $("#modalWoMaterials").modal("hide");
                notifikasi(
                    "info",
                    "Serah Terima",
                    "Pengembalian bahan tersedia setelah produksi selesai."
                );
                return;
            }
            var balance = (d.material_balance || []).filter(function (b) {
                return Number(b.qty) > 0.0001;
            });
            var num = (d.wo && d.wo.wo_number) || woNum || "—";
            $("#wo-mat-title").text("Serah Terima Bahan — " + num);
            $("#wo-mat-subtitle").text(
                "Produksi selesai · isi QTY Kembali (sisa tidak terpakai)"
            );
            woPaintMatMetaBar("#wo-mat-return-meta", {
                wo_number: num,
                pic: d.pic,
                pp: d.pp,
            });
            var body = "";
            if (!balance.length) {
                body =
                    '<tr><td colspan="5" class="text-center text-muted py-4">Tidak ada bahan yang masih dipegang PIC (belum ACC ambil / sudah dikembalikan).</td></tr>';
                $("#wo-mat-return-save").hide();
            } else {
                $("#wo-mat-return-save").show();
                balance.forEach(function (b) {
                    var held = Number(b.qty) || 0;
                    var used = Number(b.used_qty) || 0;
                    var suggest =
                        b.suggest_return_qty != null
                            ? Number(b.suggest_return_qty)
                            : Math.max(0, held - used);
                    if (suggest > held) suggest = held;
                    if (suggest < 0) suggest = 0;
                    body +=
                        '<tr data-sid="' +
                        Number(b.supplies_id) +
                        '" data-uid="' +
                        Number(b.unit_id || b.unitId || 0) +
                        '" data-max="' +
                        held +
                        '">' +
                        "<td>" +
                        woEscape(b.name || "—") +
                        "</td>" +
                        '<td class="text-center">' +
                        woEscape(b.unit || "—") +
                        "</td>" +
                        '<td class="text-center fw-semibold">' +
                        woEscape(String(held)) +
                        "</td>" +
                        '<td class="text-center text-muted" title="Dari hasil produksi × resep">' +
                        woEscape(String(used)) +
                        "</td>" +
                        '<td class="text-center"><input type="number" min="0" max="' +
                        held +
                        '" step="1" class="form-control form-control-sm text-center wo-mat-return-qty" value="' +
                        suggest +
                        '" style="max-width:100px;margin:0 auto;"></td></tr>';
                });
            }
            $("#wo-mat-return-rows").html(body);
            $("#wo-mat-print")
                .attr("href", "/printProductionMaterialHandover/" + Number(id))
                .show()
                .html('<i class="fe fe-printer me-1"></i> Cetak Serah Terima');
            if (typeof feather !== "undefined") feather.replace();
        })
        .fail(function (xhr) {
            if (woIsAbort(xhr) || !woFetchAlive(token)) return;
            $("#modalWoMaterials").modal("hide");
            woError(xhr);
        });
}

/** Modal Ambil Bahan — header langsung; spinner hanya resep di tabel. */
function woOpenMatTake(id, show, hint) {
    var token = woBeginFetch();
    hint = hint || woHintFromDetailOrBtn(id);
    woShowMatMode("ambil");
    var woNum = hint.wo_number || hint.code || "";
    $("#wo-mat-title").text(woNum ? "Ambil Bahan — " + woNum : "Ambil Bahan");
    $("#wo-mat-subtitle").text(
        [hint.pic, hint.pp, hint.line].filter(Boolean).join(" · ") ||
            "Isi qty ambil sesuai resep"
    );
    woPaintMatMetaBar("#wo-mat-ambil-meta", hint);
    $("#wo-mat-save").hide();
    $("#wo-mat-rows").html(
        '<tr><td colspan="7" class="text-center text-muted py-4">' +
            '<span class="spinner-border spinner-border-sm me-2"></span>Memuat resep…</td></tr>'
    );
    if (show !== false) $("#modalWoMaterials").modal("show");
    if (typeof feather !== "undefined") feather.replace();

    woFetch.xhrDetail = $.get("/productionWorkOrders/" + Number(id))
        .done(function (d) {
            if (!woFetchAlive(token)) return;
            if (show !== false && !$("#modalWoMaterials").hasClass("show")) return;
            var editable =
                typeof hasAccessAction === "function"
                    ? hasAccessAction("Produksi", "edit")
                    : true;
            d.can_materials = !!(d.can_materials && editable);
            woDetail = d;

            var num = (d.wo && d.wo.wo_number) || woNum || "—";
            $("#wo-mat-title").text("Ambil Bahan — " + num);
            $("#wo-mat-subtitle").text(
                [d.pic || "—", d.pp || "", d.wo && d.wo.production_line]
                    .filter(Boolean)
                    .join(" · ")
            );
            woPaintMatMetaBar("#wo-mat-ambil-meta", {
                wo_number: num,
                pic: d.pic,
                pp: d.pp,
            });

            $("#wo-mat-save").toggle(!!d.can_materials && !window.woMonitor);
            woLoadMatRecipe(Number(id), token);
            if (typeof feather !== "undefined") feather.replace();
        })
        .fail(function (xhr) {
            if (woIsAbort(xhr) || !woFetchAlive(token)) return;
            $("#modalWoMaterials").modal("hide");
            woError(xhr);
        });
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
        '<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 w-100">' +
            '<div class="d-flex flex-wrap align-items-center gap-4">' +
                '<div>' +
                    '<span class="text-muted small text-uppercase fw-bold d-block" style="font-size: 11px; letter-spacing: 0.5px;">No. STB</span>' +
                    '<span class="fw-bold text-primary fs-6 font-monospace">' + woEscape(doc.number || "—") + '</span>' +
                '</div>' +
                '<div class="border-start ps-3 ps-md-4">' +
                    '<span class="text-muted small text-uppercase fw-bold d-block" style="font-size: 11px; letter-spacing: 0.5px;">PIC Pengambil</span>' +
                    '<span class="fw-bold text-dark fs-6">' + woEscape(doc.pic || "—") + '</span>' +
                '</div>' +
                '<div class="border-start ps-3 ps-md-4">' +
                    '<span class="text-muted small text-uppercase fw-bold d-block" style="font-size: 11px; letter-spacing: 0.5px;">Work Order</span>' +
                    '<span class="fw-bold text-dark fs-6 font-monospace">' + woEscape(doc.wo_number || "—") + '</span>' +
                '</div>' +
                '<div class="border-start ps-3 ps-md-4">' +
                    '<span class="text-muted small text-uppercase fw-bold d-block" style="font-size: 11px; letter-spacing: 0.5px;">Planning</span>' +
                    '<span class="fw-bold text-dark fs-6 font-monospace">' + woEscape(doc.pp_number || "—") + '</span>' +
                '</div>' +
                '<div class="border-start ps-3 ps-md-4">' +
                    '<span class="text-muted small text-uppercase fw-bold d-block" style="font-size: 11px; letter-spacing: 0.5px;">Waktu Diajukan</span>' +
                    '<span class="fw-bold text-dark fs-6" style="font-size: 13px;">' + woEscape(submittedTime) + '</span>' +
                '</div>' +
            '</div>' +
            '<div class="d-flex align-items-center gap-2">' +
                '<span class="text-muted small text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">Status:</span>' +
                '<div>' + woMatStatusBadge(doc.stage) + '</div>' +
            '</div>' +
        '</div>'
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
            // Qty PIC = Qty QC (tidak berubah) — satu kolom, tampilan sama di semua tahap.
            var qty =
                it.requested_qty != null
                    ? it.requested_qty
                    : it.received_qty != null
                      ? it.received_qty
                      : "—";
            var prodName = it.product_name || "—";
            var prodCode = it.product_code || it.code || "";
            var unit = it.unit_label || it.unit_name || "—";

            var statusHtml = "";
            if (doc.stage === "awaiting_qc") {
                statusHtml =
                    '<span class="pp-status awaiting_qc" style="font-size:11px;padding:3px 10px;">Menunggu QC</span>';
            } else if (doc.stage === "awaiting_ops") {
                statusHtml =
                    '<span class="pp-status awaiting_ops" style="font-size:11px;padding:3px 10px;">Menunggu Ops</span>';
            } else if (doc.stage === "approved" || it.received_qty != null) {
                statusHtml =
                    '<span class="pp-status done" style="font-size:11px;padding:3px 10px;">Sesuai</span>';
            } else {
                statusHtml = '<span class="text-muted">—</span>';
            }

            $body.append(
                '<tr>' +
                    '<td class="text-center text-muted fw-semibold">' +
                    (idx + 1) +
                    "</td>" +
                    "<td>" +
                    '<div class="d-flex align-items-center gap-2">' +
                    '<span class="d-inline-flex align-items-center justify-content-center rounded bg-light text-primary border" style="width:30px;height:30px;font-size:13px;flex-shrink:0;">' +
                    '<i class="fe fe-package"></i>' +
                    "</span>" +
                    "<div>" +
                    '<div class="fw-bold text-dark" style="font-size:13px;">' +
                    woEscape(prodName) +
                    "</div>" +
                    (prodCode
                        ? '<div class="text-muted font-monospace" style="font-size:11px;">' +
                          woEscape(prodCode) +
                          "</div>"
                        : "") +
                    "</div>" +
                    "</div>" +
                    "</td>" +
                    '<td class="text-center">' +
                    '<span class="fw-bold text-dark" style="font-size:14px;">' +
                    woEscape(String(qty)) +
                    "</span>" +
                    "</td>" +
                    '<td class="text-center">' +
                    '<span class="badge bg-light text-secondary border fw-semibold px-2 py-1" style="font-size:11px;">' +
                    woEscape(unit) +
                    "</span>" +
                    "</td>" +
                    '<td class="text-center">' +
                    statusHtml +
                    "</td>" +
                    "</tr>"
            );
        });
    }

    // Dynamic Hint Text — alur: PIC → QC → Kepala Ops (potong stok di Ops)
    if (doc.stage === "awaiting_qc") {
        $("#wo-mat-acc-hint").html('<i class="fe fe-alert-circle me-1 text-warning"></i>Menunggu verifikasi Staf QC sebelum ACC Kepala Operasional.');
    } else if (doc.stage === "awaiting_ops") {
        $("#wo-mat-acc-hint").html('<i class="fe fe-info me-1 text-primary"></i>Menunggu ACC Kepala Ops. Stok gudang dipotong otomatis setelah ACC.');
    } else if (doc.stage === "approved") {
        $("#wo-mat-acc-hint").html('<i class="fe fe-check-circle me-1 text-success"></i>Permintaan telah disetujui penuh & stok gudang telah dipotong.');
    } else {
        $("#wo-mat-acc-hint").html('<i class="fe fe-info me-1 text-primary"></i>Stok gudang akan dipotong otomatis setelah ACC Kepala Ops.');
    }

    // Render Timeline Steps: 1 PIC → 2 QC → 3 Ops
    var step1Html = '<div class="col-12 col-md-4">' +
        '<div class="wo-timeline-step">' +
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

    if (doc.qc_approved_at || doc.stage === 'awaiting_ops' || doc.stage === 'approved') {
        step2Icon = '#dcfce7';
        step2Color = '#16a34a';
        step2IconClass = 'fe-check';
        step2Status = 'Disetujui QC';
        step2StatusClass = 'text-success';
        step2Time = doc.qc_approved_at ? woFormatDateTimeFull(doc.qc_approved_at) : 'Sudah di-ACC';
    } else if (doc.stage === 'awaiting_qc') {
        step2Icon = '#fefce8';
        step2Color = '#a16207';
        step2IconClass = 'fe-clock';
        step2Status = 'Menunggu QC';
        step2StatusClass = 'text-warning';
        step2Time = 'Dalam antrean';
    }

    var step2Html = '<div class="col-12 col-md-4">' +
        '<div class="wo-timeline-step">' +
            '<div class="icon-box" style="background:' + step2Icon + ';color:' + step2Color + ';"><i class="fe ' + step2IconClass + '"></i></div>' +
            '<div class="min-w-0 flex-grow-1">' +
                '<div class="step-title">2. ACC QC</div>' +
                '<div class="step-val text-truncate ' + step2StatusClass + '">' + step2Status + '</div>' +
                '<div class="text-muted text-truncate" style="font-size:11px;">' + step2Time + '</div>' +
            '</div>' +
        '</div>' +
    '</div>';

    var step3Icon = '#f1f5f9';
    var step3Color = '#64748b';
    var step3IconClass = 'fe-minus';
    var step3Status = 'Menunggu Tahap QC';
    var step3StatusClass = 'text-muted';
    var step3Time = 'Belum diproses';

    if (doc.ops_approved_at || doc.stage === 'approved') {
        step3Icon = '#dcfce7';
        step3Color = '#16a34a';
        step3IconClass = 'fe-check-circle';
        step3Status = 'Stok Dipotong';
        step3StatusClass = 'text-success';
        step3Time = doc.ops_approved_at ? woFormatDateTimeFull(doc.ops_approved_at) : 'Selesai';
    } else if (doc.stage === 'awaiting_ops') {
        step3Icon = '#f0f9ff';
        step3Color = '#0284c7';
        step3IconClass = 'fe-refresh-cw';
        step3Status = 'Menunggu ACC Ops';
        step3StatusClass = 'text-primary';
        step3Time = 'Siap potong stok';
    }

    var step3Html = '<div class="col-12 col-md-4">' +
        '<div class="wo-timeline-step">' +
            '<div class="icon-box" style="background:' + step3Icon + ';color:' + step3Color + ';"><i class="fe ' + step3IconClass + '"></i></div>' +
            '<div class="min-w-0 flex-grow-1">' +
                '<div class="step-title">3. ACC Kepala Ops (Potong Stok)</div>' +
                '<div class="step-val text-truncate ' + step3StatusClass + '">' + step3Status + '</div>' +
                '<div class="text-muted text-truncate" style="font-size:11px;">' + step3Time + '</div>' +
            '</div>' +
        '</div>' +
    '</div>';

    $("#wo-mat-timeline-steps").html(step1Html + step2Html + step3Html);

    $("#wo-mat-print").hide(); // Cetak serah terima = setelah produksi selesai, bukan saat ACC ambil

    var $acc = $("#wo-mat-acc-wrap").empty();
    if (!window.woMonitor && doc.can_qc) {
        $acc.append(
            '<button type="button" class="btn pg-btn-confirm wo-mat-acc-qc" data-doc="' +
                Number(doc.id) +
                '"><i class="fe fe-check me-1"></i>ACC QC</button>'
        );
    }
    if (!window.woMonitor && doc.can_ops) {
        $acc.append(
            '<button type="button" class="btn pg-btn-confirm wo-mat-acc-ops" data-doc="' +
                Number(doc.id) +
                '"><i class="fe fe-check me-1"></i>ACC Kepala Ops (Potong Stok)</button>'
        );
    }

    $("#modalWoMaterials").modal("show");
    if (typeof feather !== "undefined") feather.replace();
}

/** Refresh ACC modal setelah ACC Ops/QC (bukan Ambil Bahan). */
function woLoadMaterials(id, show) {
    var token = woBeginFetch();
    woFetch.xhrDetail = $.get("/productionWorkOrders/" + Number(id))
        .done(function (d) {
            if (!woFetchAlive(token)) return;
            if (
                show !== false &&
                !$("#modalWoMaterials").hasClass("show") &&
                woMatMode === "acc"
            ) {
                return;
            }
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
            if (
                show !== false ||
                ($("#modalWoMaterials").hasClass("show") && woMatMode === "acc")
            ) {
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
        .fail(function (xhr) {
            if (woIsAbort(xhr) || !woFetchAlive(token)) return;
            woError(xhr);
        });
}

function woMatHistStatusBadge(st) {
    if (st === "approved") {
        return '<span class="pp-status" style="background:#ecfdf5;color:#047857;border:1px solid #a7f3d0;">Selesai ACC</span>';
    }
    if (st === "awaiting_ops") {
        return '<span class="pp-status" style="background:#fff7ed;color:#c2410c;border:1px solid #fdba74;">Menunggu Ops</span>';
    }
    if (st === "awaiting_qc") {
        return '<span class="pp-status" style="background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;">Menunggu QC</span>';
    }
    return (
        '<span class="pp-status" style="background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;">' +
        woEscape(st || "—") +
        "</span>"
    );
}

/** Render baris resep + accordion log per bahan (qty > 0 saja). */
function woLoadMatRecipe(woId, token) {
    token = token || woFetch.token;
    woFetch.xhrRecipe = $.get(
        "/productionWorkOrders/" + Number(woId) + "/materialRecipe"
    );
    return woFetch.xhrRecipe.then(
        function (res) {
            if (!woFetchAlive(token)) return;
            if (!$("#modalWoMaterials").hasClass("show") && woMatMode === "ambil") {
                return;
            }
            var rows = (res && res.items) || [];
            var html = "";
            if (!rows.length) {
                html =
                    '<tr><td colspan="7" class="text-muted text-center py-4">Tidak ada bahan di resep produk WO ini.</td></tr>';
            } else {
                rows.forEach(function (it, idx) {
                    var short =
                        Number(it.available) + 0.0001 < Number(it.suggest_qty);
                    var log = (it.take_log || []).filter(function (e) {
                        return Number(e.qty) > 0;
                    });
                    var hasLog = log.length > 0;
                    html +=
                        '<tr class="wo-mat-prod-row' +
                        (hasLog ? " has-log" : "") +
                        '" data-mat-idx="' +
                        idx +
                        '" data-sid="' +
                        it.supplies_id +
                        '" data-uid="' +
                        it.unit_id +
                        '"' +
                        (hasLog
                            ? ' title="Klik untuk lihat riwayat ambil"'
                            : "") +
                        ">";
                    html +=
                        '<td><div class="d-flex align-items-center flex-wrap gap-2">' +
                        '<div class="fw-semibold text-dark">' +
                        woEscape(it.name) +
                        "</div>" +
                        (it.is_trading
                            ? '<span class="badge-trading">Trading</span>'
                            : "") +
                        (hasLog
                            ? '<span class="wo-mat-log-badge">' +
                              log.length +
                              "x ambil</span>"
                            : "") +
                        "</div></td>";
                    html +=
                        '<td class="text-center fw-semibold text-secondary">' +
                        it.recipe_qty +
                        "</td>";
                    html +=
                        '<td class="text-center">' + it.taken_qty + "</td>";
                    html +=
                        '<td class="text-center ' +
                        (short ? "text-danger fw-bold" : "text-muted") +
                        '">' +
                        it.available +
                        (short
                            ? ' <i class="fe fe-alert-circle text-danger ms-1" title="Stok gudang kurang"></i>'
                            : "") +
                        "</td>";
                    html +=
                        '<td class="text-center"><span class="badge bg-light text-dark border fw-semibold">' +
                        woEscape(it.unit || "—") +
                        "</span></td>";
                    html +=
                        '<td class="text-center">' +
                        '<input type="number" min="0" step="1" class="form-control form-control-sm text-center fw-semibold wo-mat-qty" ' +
                        'placeholder="0" style="max-width:90px;margin:0 auto;" value="' +
                        (it.suggest_qty > 0 ? it.suggest_qty : "") +
                        '" data-avail="' +
                        it.available +
                        '" data-idx="' +
                        idx +
                        '"></td>';
                    html +=
                        '<td class="text-center">' +
                        (hasLog
                            ? '<i class="fe fe-chevron-down wo-mat-row-chevron"></i>'
                            : "") +
                        "</td></tr>";

                    if (hasLog) {
                        log.forEach(function (e) {
                            var when = e.taken_at
                                ? woFormatDateTimeFull(e.taken_at)
                                : "—";
                            html +=
                                '<tr class="wo-mat-log-row wo-mat-log-for-' +
                                idx +
                                '" style="display:none;">' +
                                '<td class="ps-4">' +
                                '<i class="fe fe-corner-down-right text-muted me-1"></i>' +
                                '<span class="fw-bold font-monospace text-primary">' +
                                woEscape(e.number || "—") +
                                "</span>" +
                                '<span class="wo-mat-log-when ms-2">' +
                                woEscape(when) +
                                "</span></td>" +
                                "<td></td>" +
                                '<td class="text-center fw-semibold text-dark">' +
                                woEscape(String(e.qty)) +
                                "</td>" +
                                "<td></td>" +
                                '<td class="text-center"><span class="badge bg-light text-dark border">' +
                                woEscape(e.unit || it.unit || "—") +
                                "</span></td>" +
                                '<td class="text-center">' +
                                woMatHistStatusBadge(e.status) +
                                "</td>" +
                                "<td></td></tr>";
                        });
                    }
                });
            }
            var $body = $("#wo-mat-rows");
            $body.html(html);
            $body
                .off("click.woMatLog", "tr.wo-mat-prod-row.has-log")
                .on("click.woMatLog", "tr.wo-mat-prod-row.has-log", function (e) {
                    if (
                        $(e.target).closest("input, button, a, select").length
                    ) {
                        return;
                    }
                    var $row = $(this);
                    var i = $row.data("mat-idx");
                    var $logs = $body.find(".wo-mat-log-for-" + i);
                    if ($row.hasClass("is-expanded")) {
                        $row.removeClass("is-expanded");
                        $logs.hide();
                    } else {
                        $row.addClass("is-expanded");
                        $logs.show();
                    }
                });
            var miss = (res && res.missing_bom) || [];
            $("#wo-mat-hint").html(
                miss.length
                    ? '<i class="fe fe-info me-1 text-primary"></i>Beberapa produk belum punya resep: ' +
                          woEscape(miss.join(", ")) +
                          "."
                    : '<i class="fe fe-info me-1 text-primary"></i>Klik baris bahan (badge <em>Nx ambil</em>) untuk lihat log. Qty 0 tidak dicatat. ACC: QC → Ops.'
            );
            if (typeof feather !== "undefined") feather.replace();
        },
        function (xhr) {
            if (woIsAbort(xhr) || !woFetchAlive(token)) return;
            $("#wo-mat-rows").html(
                '<tr><td colspan="7" class="text-center text-danger py-4">Gagal memuat resep.</td></tr>'
            );
        }
    );
}

function woFgStatusBadgeHtml(status, short) {
    var cls = "draft";
    if (status === "awaiting_qc") cls = "awaiting_qc";
    else if (status === "awaiting_ops") cls = "awaiting_ops";
    else if (status === "approved") cls = "done";
    return (
        '<span class="pp-status ' +
        cls +
        '">' +
        woEscape(woFgStatusLabel(status, !!short)) +
        "</span>"
    );
}

/** Modal ACC Form Gudang (QC / Kepala Ops) — bukan modal Sudah Diproduksi. */
function woShowFgAcc(d, show) {
    var fg = woFgDoc(d);
    if (!fg) return;
    woDetail = d;
    var stage = fg.document_status || "";
    var title =
        stage === "awaiting_ops"
            ? "ACC Form Gudang — Kepala Ops"
            : stage === "approved"
              ? "Form Gudang — Selesai"
              : "ACC Form Gudang — Staf QC";
    $("#wo-fg-acc-title").text(title);
    $("#wo-fg-acc-subtitle").text(
        (d.wo.wo_number || "—") +
            " · " +
            (fg.number || "FG") +
            " · " +
            (d.pic || "—") +
            " · " +
            (d.pp || "—")
    );

    $("#wo-fg-acc-meta").html(
        '<div class="row g-3">' +
            '<div class="col-md-3 col-6">' +
            '<span class="text-muted small text-uppercase fw-bold d-block" style="font-size:11px;letter-spacing:.5px;">Work Order</span>' +
            '<div class="fw-bold text-dark">' +
            woEscape(d.wo.wo_number || "—") +
            "</div></div>" +
            '<div class="col-md-3 col-6">' +
            '<span class="text-muted small text-uppercase fw-bold d-block" style="font-size:11px;letter-spacing:.5px;">No. Form Gudang</span>' +
            '<div class="fw-bold text-dark font-monospace">' +
            woEscape(fg.number || "—") +
            "</div></div>" +
            '<div class="col-md-3 col-6">' +
            '<span class="text-muted small text-uppercase fw-bold d-block" style="font-size:11px;letter-spacing:.5px;">PIC</span>' +
            '<div class="fw-semibold text-dark">' +
            woEscape(d.pic || "—") +
            "</div></div>" +
            '<div class="col-md-3 col-6">' +
            '<span class="text-muted small text-uppercase fw-bold d-block" style="font-size:11px;letter-spacing:.5px;">Status</span>' +
            '<div class="mt-1">' +
            woFgStatusBadgeHtml(stage, true) +
            "</div></div>" +
            "</div>"
    );

    if (stage === "awaiting_qc") {
        $("#wo-fg-acc-hint").html(
            '<i class="fe fe-alert-circle me-1 text-primary"></i>Verifikasi hasil produksi. Setelah ACC QC → menunggu Kepala Ops (stok masuk).'
        );
    } else if (stage === "awaiting_ops") {
        $("#wo-fg-acc-hint").html(
            '<i class="fe fe-info me-1 text-warning"></i>ACC Kepala Ops = kredit stok jadi + Tally.'
        );
    } else {
        $("#wo-fg-acc-hint").html(
            '<i class="fe fe-check-circle me-1 text-success"></i>Form Gudang sudah di-ACC lengkap.'
        );
    }

    var statusHtml = woFgStatusBadgeHtml(stage, true);
    var rowsHtml = "";
    (d.items || []).forEach(function (it) {
        var ket =
            Number(it.excess) > 0
                ? '<span class="text-success fw-semibold">Kelebihan ' +
                  woEscape(String(it.excess)) +
                  "</span>"
                : '<span class="text-muted">—</span>';
        rowsHtml +=
            "<tr><td><div class=\"fw-semibold text-dark\">" +
            woEscape(it.name) +
            "</div></td><td><span class=\"fw-semibold\">" +
            woEscape(String(it.target)) +
            "</span> " +
            woEscape(it.unit || "") +
            "</td><td>" +
            woEscape(String(it.actual)) +
            " " +
            woEscape(it.unit || "") +
            ' <strong class="' +
            (it.result === "OK" ? "text-success" : "text-danger") +
            '">' +
            woEscape(it.result || "") +
            "</strong></td><td>" +
            ket +
            '</td><td class="text-center">' +
            statusHtml +
            "</td></tr>";
    });
    if (!rowsHtml) {
        rowsHtml =
            '<tr><td colspan="5" class="text-center text-muted py-4">Tidak ada item hasil.</td></tr>';
    }
    $("#wo-fg-acc-rows").html(rowsHtml);

    // Timeline: PIC → QC → Ops
    var step1Cls = "is-done";
    var step1Val = fg.confirmed_at
        ? woFormatDateTimeFull(fg.confirmed_at)
        : "Produksi selesai";
    var step2Cls =
        stage === "awaiting_qc"
            ? "is-active"
            : fg.qc_approved_at || stage === "awaiting_ops" || stage === "approved"
              ? "is-done"
              : "";
    var step2Val = fg.qc_approved_at
        ? woFormatDateTimeFull(fg.qc_approved_at)
        : stage === "awaiting_qc"
          ? "Dalam antrean"
          : "—";
    var step3Cls =
        stage === "awaiting_ops"
            ? "is-active"
            : stage === "approved"
              ? "is-done"
              : "";
    var step3Val = fg.ops_approved_at
        ? woFormatDateTimeFull(fg.ops_approved_at)
        : stage === "awaiting_ops"
          ? "Menunggu ACC Ops"
          : stage === "awaiting_qc"
            ? "Menunggu tahap QC"
            : "—";
    $("#wo-fg-acc-timeline").html(
        '<div class="col-md-4"><div class="wo-timeline-step ' +
            step1Cls +
            '"><div class="icon-box"><i class="fe fe-user"></i></div><div><div class="step-title">1. Diajukan (PIC)</div><div class="step-val">' +
            woEscape(step1Val) +
            "</div></div></div></div>" +
            '<div class="col-md-4"><div class="wo-timeline-step ' +
            step2Cls +
            '"><div class="icon-box"><i class="fe fe-shield"></i></div><div><div class="step-title">2. ACC QC</div><div class="step-val">' +
            woEscape(step2Val) +
            "</div></div></div></div>" +
            '<div class="col-md-4"><div class="wo-timeline-step ' +
            step3Cls +
            '"><div class="icon-box"><i class="fe fe-check"></i></div><div><div class="step-title">3. ACC Kepala Ops</div><div class="step-val">' +
            woEscape(step3Val) +
            "</div></div></div></div>"
    );

    $("#wo-fg-acc-print")
        .attr("href", "/printProductionDocument/" + Number(fg.id) + "/form")
        .show();
    if (fg.tally_number) {
        $("#wo-fg-acc-print-tally")
            .attr("href", "/printProductionDocument/" + Number(fg.id) + "/tally")
            .show();
    } else {
        $("#wo-fg-acc-print-tally").hide();
    }

    var $btn = $("#wo-fg-acc-btn").hide().removeData("doc").removeData("stage");
    if (!window.woMonitor && stage === "awaiting_qc" && d.can_qc) {
        $btn.html('<i class="fe fe-check me-1"></i> ACC Staf QC Gudang')
            .data("doc", fg.id)
            .data("stage", "qc")
            .show();
    } else if (!window.woMonitor && stage === "awaiting_ops" && d.can_ops) {
        $btn.html('<i class="fe fe-check me-1"></i> ACC Kepala Ops (Stok Masuk)')
            .data("doc", fg.id)
            .data("stage", "ops")
            .show();
    }

    if (show !== false) {
        // Jangan buka lagi kalau user sudah tutup / ganti modal
        if (
            !$("#modalWoFgAcc").hasClass("show") &&
            !$("#modalWoExecution").hasClass("show")
        ) {
            return;
        }
        $("#modalWoExecution").modal("hide");
        $("#modalWoFgAcc").modal("show");
    }
    if (typeof feather !== "undefined") feather.replace();
}

function woShowExecution(d, show) {
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
        "/printProductionWorkOrder/" + Number(d.wo.production_work_order_id)
    );
    var can =
        d.can_report && !d.wo.production_completed_at && !d.wo.closed_at;
    var html = "";
    (d.items || []).forEach(function (it) {
        var perPallet = Number(it.qty_per_pallet) || 0;
        var pallet = perPallet > 0;
        var dis = can ? "" : " disabled";
        var qtyVal = can && pallet && it.result !== "OK" ? "1" : "";
        html +=
            '<tr data-ppi="' +
            it.id +
            '" data-has-pallet="' +
            (pallet ? "1" : "0") +
            '">';
        html +=
            '<td><div class="fw-semibold text-dark">' +
            woEscape(it.name) +
            "</div></td>";
        html +=
            '<td><span class="fw-semibold">' +
            it.target +
            "</span> " +
            woEscape(it.unit) +
            "</td>";
        html +=
            "<td>" +
            it.actual +
            " " +
            woEscape(it.unit) +
            ' <strong class="' +
            (it.result === "OK" ? "text-success" : "text-danger") +
            '">' +
            it.result +
            "</strong></td>";
        html +=
            '<td><input class="form-control wo-qty" type="number" min="0" step="any"' +
            dis +
            ' value="' +
            qtyVal +
            '"></td>';
        html += '<td><select class="form-select wo-unit"' + dis + ">";
        if (pallet) {
            html +=
                '<option value="pallet" selected>PALLET (1 = ' +
                perPallet +
                " " +
                woEscape(it.pallet_unit || it.unit || "") +
                ")</option>";
        }
        (it.units || []).forEach(function (u) {
            html +=
                '<option value="' +
                u.id +
                '"' +
                (!pallet && Number(u.id) === Number(it.unit_id)
                    ? " selected"
                    : "") +
                ">" +
                woEscape(u.name) +
                "</option>";
        });
        html += "</select>";
        html += "</td>";
        html +=
            '<td><input type="text" class="form-control form-control-sm wo-pallet" placeholder="Opsional"' +
            (can && pallet ? "" : " disabled") +
            ' maxlength="100"></td>';
        html += "</tr>";
    });
    $("#wo-output-rows").html(
        html ||
            '<tr><td colspan="6" class="text-center text-muted py-4">Tidak ada item.</td></tr>'
    );
    // Sudah Diproduksi: butuh ambil bahan dulu (kalau WO punya resep)
    var matsReady = d.materials_ready !== false;
    var showReport = can && matsReady && !window.woMonitor;
    $("#wo-report")
        .toggle(can && !window.woMonitor)
        .prop("disabled", !showReport)
        .toggleClass("disabled", !showReport);
    if (can && !matsReady && !window.woMonitor) {
        $("#wo-report-hint")
            .html(
                '<i class="fe fe-info me-1"></i>Ambil bahan mentah dulu (minimal 1× sesuai resep) sebelum <strong>Sudah Diproduksi</strong>.'
            )
            .show();
    } else {
        $("#wo-report-hint").hide().empty();
    }
    if (show !== false) {
        if (
            !$("#modalWoExecution").hasClass("show") &&
            !$("#modalWoFgAcc").hasClass("show")
        ) {
            return;
        }
        $("#modalWoFgAcc").modal("hide");
        $("#modalWoExecution").modal("show");
    }
    if (typeof feather !== "undefined") feather.replace();
}

$(function () {
    window.woEmbed = !!window.woEmbed;
    window.woMonitor = !!window.woMonitor;

    // Tutup modal WO → batalkan AJAX yang masih jalan (cegah modal muncul lagi)
    $(
        "#modalWoExecution, #modalWoFgAcc, #modalWoMaterials"
    ).on("hidden.bs.modal.woFetch", function () {
        setTimeout(function () {
            if (!woAnyModalOpen()) {
                woAbortFetches();
                woFetch.token++;
            }
        }, 0);
    });

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
                !$("#modalWoFgAcc").hasClass("show") &&
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

/** Buka modal dulu (loading), baru fetch — SOP modal instan. mode: exec|fg|auto */
function woLoad(id, show, mode) {
    mode = mode || "auto";
    var token = woBeginFetch();
    if (show !== false) {
        if (mode === "fg") {
            $("#modalWoExecution").modal("hide");
            $("#wo-fg-acc-title").text("ACC Form Gudang");
            $("#wo-fg-acc-subtitle").text("Memuat…");
            $("#wo-fg-acc-meta").html(
                '<div class="text-muted small"><span class="spinner-border spinner-border-sm me-2"></span>Memuat Form Gudang…</div>'
            );
            $("#wo-fg-acc-rows").html(
                '<tr><td colspan="5" class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2"></span>Memuat…</td></tr>'
            );
            $("#wo-fg-acc-timeline").empty();
            $("#wo-fg-acc-btn").hide();
            $("#modalWoFgAcc").modal("show");
        } else {
            $("#modalWoFgAcc").modal("hide");
            if (!$("#wo-title").text() || $("#wo-title").text() === "Work Order") {
                $("#wo-title").text("Work Order");
                $("#wo-subtitle").text("Memuat…");
            }
            $("#wo-report").hide();
            $("#wo-report-hint").hide().empty();
            $("#wo-output-rows").html(
                '<tr><td colspan="6" class="text-center text-muted py-5">' +
                    '<span class="spinner-border spinner-border-sm text-success me-2"></span>Memuat data WO…</td></tr>'
            );
            $("#modalWoExecution").modal("show");
        }
    }

    woFetch.xhrDetail = $.get("/productionWorkOrders/" + Number(id))
        .done(function (d) {
            if (!woFetchAlive(token)) return;
            // Tutup modal saat fetch jalan → jangan buka lagi / ganti isi
            if (show !== false && !woAnyModalOpen()) return;
            var editable = hasAccessAction("Produksi", "edit");
            d.can_report = d.can_report && editable;
            woDetail = d;
            var isFg = !!(d.wo.production_completed_at || woFgDoc(d));
            if (isFg) {
                if (show !== false && !$("#modalWoFgAcc").hasClass("show") && mode === "fg") {
                    return;
                }
                $("#modalWoExecution").modal("hide");
                woShowFgAcc(d, show);
            } else {
                if (
                    show !== false &&
                    !$("#modalWoExecution").hasClass("show") &&
                    mode !== "fg"
                ) {
                    return;
                }
                $("#modalWoFgAcc").modal("hide");
                woShowExecution(d, show === false ? false : true);
            }
        })
        .fail(function (xhr) {
            if (woIsAbort(xhr) || !woFetchAlive(token)) return;
            $("#modalWoExecution").modal("hide");
            $("#modalWoFgAcc").modal("hide");
            woError(xhr);
        });
}

// Toggle No. Palet saat ganti satuan
$(document).on("change", "#wo-output-rows .wo-unit", function () {
    var $tr = $(this).closest("tr");
    var isPallet = String($(this).val()) === "pallet";
    var canHas = $tr.attr("data-has-pallet") === "1";
    $tr.find(".wo-pallet").prop("disabled", !isPallet || !canHas);
});

function woError(xhr) {
    notifikasi(
        "error",
        "Tidak dapat diproses",
        (xhr.responseJSON || {}).message || "Gagal memuat/menyimpan data."
    );
}

/** Konfirmasi 2 langkah — Swal bertema PG (bukan default ungu). */
async function woConfirm(title, text) {
    var first = await Swal.fire({
        icon: "question",
        iconColor: "#2563eb",
        title: title || "Konfirmasi",
        html:
            '<p class="text-start mb-0" style="font-size:14px;white-space:pre-wrap;color:#475569;">' +
            $("<div>").text(text || "").html() +
            "</p>",
        showCancelButton: true,
        reverseButtons: true,
        confirmButtonText: '<i class="fe fe-check me-1"></i> Lanjut',
        cancelButtonText: "Periksa lagi",
        customClass: {
            popup: "rounded-4",
            title: "fw-bold fs-4 text-dark",
            confirmButton: "pg-btn-confirm",
            cancelButton: "pg-btn-cancel",
        },
        buttonsStyling: false,
    });
    if (!first.isConfirmed) return false;
    var second = await Swal.fire({
        icon: "warning",
        iconColor: "#f59e0b",
        title: "Konfirmasi akhir",
        html:
            '<p class="text-start mb-0" style="font-size:14px;white-space:pre-wrap;color:#475569;">' +
            "Simpan atas nama Anda, dengan tanda tangan serta tanggal/jam sekarang?" +
            "</p>",
        showCancelButton: true,
        reverseButtons: true,
        confirmButtonText: '<i class="fe fe-check-circle me-1"></i> Ya, konfirmasi',
        cancelButtonText: "Batal",
        customClass: {
            popup: "rounded-4",
            title: "fw-bold fs-4 text-dark",
            confirmButton: "pg-btn-confirm",
            cancelButton: "pg-btn-cancel",
        },
        buttonsStyling: false,
    });
    return second.isConfirmed;
}

/** Pending ACC lewat #modalKonfirmasi (bukan Swal). */
var woAccPending = null;
/** Hide modal ACC → konfirmasi; Batal → buka lagi (pola Stock Transfer). */
var woAccOverlay = { parent: null, shouldRestore: false, done: false };

function woAccParentSelector() {
    if ($("#modalWoMaterials").hasClass("show")) return "#modalWoMaterials";
    if ($("#modalWoFgAcc").hasClass("show")) return "#modalWoFgAcc";
    return null;
}

function woRestoreAccOverlayIfNeeded() {
    if (!woAccOverlay.shouldRestore || woAccOverlay.done || !woAccOverlay.parent) {
        return;
    }
    var sel = woAccOverlay.parent;
    woAccOverlay.shouldRestore = false;
    woAccPending = null;
    $(sel).modal("show");
}

/** ACC QC/Ops — tutup modal ACC dulu, konfirmasi PG; Batal → munculin ACC lagi. */
function woAskAccConfirm(title, summary, pending) {
    if (typeof showModalKonfirmasi !== "function") {
        woSend(
            pending.id,
            pending.action,
            pending.payload || {},
            title,
            summary,
            pending.successMsg
        );
        return;
    }
    woAccPending = pending || null;
    woAccOverlay.done = false;
    woAccOverlay.shouldRestore = false;
    woAccOverlay.parent = woAccParentSelector();

    function openConfirm() {
        showModalKonfirmasi(
            summary || "Lanjutkan aksi ini?",
            "btn-wo-acc-confirm",
            false,
            title || "Konfirmasi"
        );
    }

    if (woAccOverlay.parent) {
        woAccOverlay.shouldRestore = true;
        $(woAccOverlay.parent)
            .off("hidden.bs.modal.woAcc")
            .one("hidden.bs.modal.woAcc", openConfirm);
        $(woAccOverlay.parent).modal("hide");
    } else {
        openConfirm();
    }
}

async function woSendExecute(id, action, payload, successMsg, opts) {
    opts = opts || {};
    if (woBusy) return;
    woBusy = true;
    try {
        var res = await $.ajax({
            url: "/productionExecution/" + id + "/" + action,
            type: "POST",
            data: Object.assign({ _token: token }, payload || {}),
        });
        var msg = successMsg;
        if (!msg) {
            if (res && res.tally_number) {
                msg = "QC OK — stok masuk, Tally " + res.tally_number;
            } else if (res && res.wo_done) {
                msg = "Produksi selesai — Form Gudang terbit. Lanjut serah terima bahan.";
            } else {
                msg = "Konfirmasi berhasil dicatat.";
            }
        }
        // ACC QC/Ops: preferToastr -> toastr (ringan), preferPgSuccess -> showPgSuccessModal (animasi).
        if (opts.preferPgSuccess && typeof showPgSuccessModal === "function") {
            showPgSuccessModal("Berhasil", msg);
        } else if (opts.preferToastr && typeof toastr !== "undefined") {
            toastr.success("", msg);
        } else if (typeof showPgSuccessModal === "function") {
            showPgSuccessModal("Berhasil", msg);
        } else {
            notifikasi("success", "Tersimpan", msg);
        }
        var woId =
            (woDetail && woDetail.wo && woDetail.wo.production_work_order_id) ||
            null;
        // Setelah target produksi tercapai → modal kembalikan bahan (FORM-OPS-08)
        if (res && res.wo_done && woId && !window.woMonitor) {
            $("#modalWoExecution").modal("hide");
            setTimeout(function () {
                woOpenMatReturn(woId, true);
            }, 350);
            woRefreshLists();
            if (typeof refreshPpBahanTable === "function") refreshPpBahanTable();
            if (typeof refreshPpTable === "function") refreshPpTable();
            if (typeof refreshPpJobTable === "function") refreshPpJobTable();
            if (typeof refreshPpHistoriTable === "function") refreshPpHistoriTable();
            if (typeof refreshPpStageMiniTables === "function")
                refreshPpStageMiniTables();
            return;
        }
        var matOpen = $("#modalWoMaterials").hasClass("show");
        if (matOpen && woMatMode === "ambil" && woId) {
            woOpenMatTake(woId, false);
        } else if (matOpen && woMatMode === "return" && woId) {
            woOpenMatReturn(woId, false);
            $("#wo-mat-print")
                .attr("href", "/printProductionMaterialHandover/" + Number(woId))
                .show();
        } else if (matOpen && woMatMode === "acc") {
            if (woId) {
                woLoadMaterials(woId, false);
            } else if (woDetail && woDetail._accDoc && woDetail._accDoc.wo_id) {
                woLoadMaterials(woDetail._accDoc.wo_id, false);
            } else {
                $("#modalWoMaterials").modal("hide");
            }
        } else if ($("#modalWoFgAcc").hasClass("show") && woId) {
            woLoad(woId, false);
        } else if (woId) {
            woLoad(woId, false);
        }
        woRefreshLists();
        if (typeof refreshPpBahanTable === "function") refreshPpBahanTable();
        if (typeof refreshPpTable === "function") refreshPpTable();
        if (typeof refreshPpJobTable === "function") refreshPpJobTable();
        if (typeof refreshPpHistoriTable === "function") refreshPpHistoriTable();
        if (typeof refreshPpStageMiniTables === "function")
            refreshPpStageMiniTables();
    } catch (xhr) {
        woError(xhr);
    } finally {
        woBusy = false;
    }
}

/** Simpan Ambil/Kembali/Hasil — tetap 2 langkah Swal PG. ACC pakai woAskAccConfirm. */
async function woSend(id, action, payload, title, summary, successMsg, opts) {
    if (woBusy) return;
    if (!(await woConfirm(title, summary))) return;
    await woSendExecute(id, action, payload, successMsg, opts);
}

$(document).on("click", "#btn-wo-acc-confirm", async function () {
    var $btn = $(this);
    if ($btn.data("busy") || !woAccPending) return;
    var p = woAccPending;
    woAccPending = null;
    // Konfirmasi OK — jangan restore parent di hidden.bs.modal
    woAccOverlay.done = true;
    woAccOverlay.shouldRestore = false;
    var parent = woAccOverlay.parent;
    var btnHtml = $btn.html();
    $btn.data("busy", true);
    if (typeof LoadingButton === "function") LoadingButton($btn);
    try {
        var execOpts = {};
        if (p && p.preferPgSuccess) {
            execOpts.preferPgSuccess = true;
        } else if (p && p.preferToastr) {
            execOpts.preferToastr = true;
        } else if (parent === "#modalWoMaterials") {
            // Default ACC Bahan: preferToastr (ringan)
            execOpts.preferToastr = true;
        } else {
            // Default ACC Produksi (FG): preferPgSuccess (animasi)
            execOpts.preferPgSuccess = true;
        }

        await woSendExecute(p.id, p.action, p.payload || {}, p.successMsg, execOpts);
        if (typeof closeModalConfirm === "function") closeModalConfirm();
        else $("#modalKonfirmasi").modal("hide");
        // Parent sudah di-hide sebelum konfirmasi — buka + refresh ACC lagi
        var woId =
            (woDetail && woDetail.wo && woDetail.wo.production_work_order_id) ||
            (woDetail && woDetail._accDoc && woDetail._accDoc.wo_id) ||
            null;
        if (parent === "#modalWoMaterials" && woId) {
            setTimeout(function () {
                woLoadMaterials(woId, true);
            }, 280);
        } else if (parent === "#modalWoFgAcc" && woId) {
            setTimeout(function () {
                woLoad(woId, true);
            }, 280);
        }
    } finally {
        $btn.data("busy", false);
        if (typeof ResetLoadingButton === "function") {
            ResetLoadingButton($btn, btnHtml);
        } else {
            $btn.prop("disabled", false).html(btnHtml);
        }
    }
});

// Batal / X di #modalKonfirmasi → munculin lagi modal ACC Bahan / FG
$(document)
    .off("hidden.bs.modal.woAccRestore", "#modalKonfirmasi")
    .on("hidden.bs.modal.woAccRestore", "#modalKonfirmasi", function () {
        woRestoreAccOverlayIfNeeded();
    });

$(document).on("click", ".wo-open, .btn-pp-wo-view", function (e) {
    e.preventDefault();
    var $btn = $(this);
    var mode = $btn.attr("data-wo-mode") || "auto";
    var id = $btn.data("id");
    var hint = woHintFromDetailOrBtn(id, $btn);
    if (hint.wo_number || hint.code) {
        $("#wo-title").text(hint.wo_number || hint.code);
        $("#wo-subtitle").text(
            [hint.pic, hint.pp].filter(Boolean).join(" | ") || "Memuat…"
        );
    }
    woLoad(id, true, mode);
});

$(document).on("click", ".btn-pp-wo-mat, .wo-open-mat", function (e) {
    e.preventDefault();
    var $btn = $(this);
    var id = $btn.data("id");
    woOpenMatTake(id, true, woHintFromDetailOrBtn(id, $btn));
});

$(document).on("click", ".btn-pp-wo-return", function (e) {
    e.preventDefault();
    var $btn = $(this);
    var id = $btn.data("id");
    woOpenMatReturn(id, true, woHintFromDetailOrBtn(id, $btn));
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
    if ($(this).prop("disabled") || $(this).hasClass("disabled")) {
        notifikasi(
            "info",
            "Ambil bahan dulu",
            "Ambil bahan mentah (minimal 1× sesuai resep) sebelum Sudah Diproduksi."
        );
        return;
    }
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
            " baris hasil akan dicatat. Jika target tercapai → Form Gudang + modal kembalikan bahan."
    );
});

$(document).on("click", "#wo-fg-acc-btn", function () {
    var docId = Number($(this).data("doc"));
    var stage = String($(this).data("stage") || "");
    if (!docId || (stage !== "qc" && stage !== "ops")) return;
    if (stage === "qc") {
        woAskAccConfirm(
            "ACC QC",
            "Verifikasi QC Form Gudang. Setelah ini menunggu ACC Kepala Ops.",
            {
                id: docId,
                action: "qc",
                payload: {},
                successMsg: "ACC QC tersimpan. Menunggu Kepala Ops.",
                preferPgSuccess: true,
            }
        );
        return;
    }
    woAskAccConfirm(
        "ACC Ops",
        "Form Gudang: ACC final — stok masuk + Tally. WO menjadi Completed.",
        { id: docId, action: "ops", payload: {}, successMsg: null, preferPgSuccess: true }
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
            " baris bahan dicatat. Selanjutnya ACC QC lalu Kepala Ops (cek stok + potong).",
        "Ambil bahan tercatat. Menunggu ACC di tab ACC Bahan."
    );
});

$(document).on("click", "#wo-mat-return-save", async function (e) {
    e.preventDefault();
    if (!woDetail || !woDetail.wo) return;
    var items = [];
    var invalid = false;
    $("#wo-mat-return-rows tr[data-sid]").each(function () {
        var $tr = $(this);
        var q = Number($tr.find(".wo-mat-return-qty").val() || 0);
        var max = Number($tr.data("max") || 0);
        if (q < 0 || q > max + 0.0001) {
            invalid = true;
            $tr.find(".wo-mat-return-qty").addClass("is-invalid");
            return;
        }
        $tr.find(".wo-mat-return-qty").removeClass("is-invalid");
        if (q <= 0) return;
        items.push({
            supplies_id: $tr.data("sid"),
            unit_id: $tr.data("uid"),
            qty: q,
        });
    });
    if (invalid) {
        notifikasi("error", "Qty tidak valid", "QTY Kembali tidak boleh melebihi QTY Terima.");
        return;
    }
    if (!items.length) {
        // Tidak ada kembalian — tetap boleh cetak serah terima
        notifikasi(
            "success",
            "Serah Terima",
            "Tidak ada bahan dikembalikan. Anda bisa cetak laporan serah terima."
        );
        $("#wo-mat-print")
            .attr(
                "href",
                "/printProductionMaterialHandover/" +
                    Number(woDetail.wo.production_work_order_id)
            )
            .show();
        return;
    }
    await woSend(
        woDetail.wo.production_work_order_id,
        "material_return",
        { request_id: woKey(), items: items },
        "Simpan Pengembalian Bahan",
        items.length +
            " baris kembalian akan diajukan ke ACC Bahan (QC → Ops, stok kembali).",
        "Pengembalian tercatat. Cetak Serah Terima, lalu ACC di tab ACC Bahan."
    );
});

$(document).on("click", ".wo-mat-acc-ops", function () {
    var docId = Number($(this).data("doc"));
    if (!docId) return;
    woAskAccConfirm(
        "ACC Ops",
        "ACC final: stok digudang dipotong/dikredit (dicek cukup dulu).",
        {
            id: docId,
            action: "ops",
            payload: {},
            successMsg: "ACC Ops bahan OK — stok diproses.",
        }
    );
});

$(document).on("click", ".wo-mat-acc-qc", function () {
    var docId = Number($(this).data("doc"));
    if (!docId) return;
    woAskAccConfirm(
        "ACC QC",
        "Verifikasi QC. Setelah ini menunggu Kepala Ops.",
        {
            id: docId,
            action: "qc",
            payload: {},
            successMsg: "ACC QC bahan OK. Menunggu Kepala Ops.",
        }
    );
});