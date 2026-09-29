const fs = require("fs");
const p = "public/Custom_js/Backoffice/Production/WorkOrders.js";
let t = fs.readFileSync(p, "utf8");
const start = t.indexOf("function woSetMatFormVisible(visible) {");
const end = t.indexOf("function woLoadMatRecipe(woId) {");
if (start < 0 || end < 0) {
  console.log("markers", start, end);
  process.exit(1);
}
const neu = `function woSetMatFormVisible() {
    return;
}

/** Modal ACC 1 request STB — qty bahan yang diambil PIC. */
function woOpenMatAccRequest(doc) {
    if (!doc || !doc.id) return;
    woDetail = woDetail || {};
    woDetail._accDoc = doc;

    $("#wo-mat-title").text("ACC Bahan — " + (doc.number || "STB"));
    $("#wo-mat-subtitle").text(
        [doc.pic || "—", doc.wo_number || "—", doc.pp_number || "—"]
            .filter(Boolean)
            .join(" · ")
    );

    $("#wo-mat-meta").html(
        '<div class="wo-mat-meta-card"><div class="lbl">No. STB</div><div class="val font-monospace">' +
            woEscape(doc.number || "—") +
            '</div></div>' +
            '<div class="wo-mat-meta-card"><div class="lbl">PIC</div><div class="val">' +
            woEscape(doc.pic || "—") +
            '</div></div>' +
            '<div class="wo-mat-meta-card"><div class="lbl">WO</div><div class="val">' +
            woEscape(doc.wo_number || "—") +
            '</div></div>' +
            '<div class="wo-mat-meta-card"><div class="lbl">PP</div><div class="val">' +
            woEscape(doc.pp_number || "—") +
            '</div></div>' +
            '<div class="wo-mat-meta-card"><div class="lbl">Tahap</div><div class="val">' +
            woMatStatusBadge(doc.stage) +
            "</div></div>"
    );

    if ($.fn.DataTable.isDataTable("#tableWoMatAcc")) {
        $("#tableWoMatAcc").DataTable().clear().destroy();
    }
    var $body = $("#wo-mat-log-body").empty();
    var items = doc.items || [];
    if (!items.length) {
        $body.html(
            '<tr><td colspan="5" class="text-center text-muted py-4">Tidak ada rincian bahan.</td></tr>'
        );
    } else {
        items.forEach(function (it, idx) {
            var req = it.requested_qty != null ? it.requested_qty : "—";
            var rec = it.received_qty != null ? it.received_qty : null;
            $body.append(
                "<tr>" +
                    '<td class="text-center text-muted">' +
                    (idx + 1) +
                    "</td>" +
                    '<td><span class="fw-semibold text-dark">' +
                    woEscape(it.product_name || "—") +
                    "</span></td>" +
                    '<td class="text-center"><span class="badge bg-light text-dark border fw-semibold">' +
                    woEscape(it.unit_label || "—") +
                    "</span></td>" +
                    '<td class="text-center fw-bold">' +
                    woEscape(String(req)) +
                    "</td>" +
                    '<td class="text-center ' +
                    (rec != null ? "text-success fw-bold" : "text-muted") +
                    '">' +
                    (rec != null ? woEscape(String(rec)) : "—") +
                    "</td>" +
                    "</tr>"
            );
        });
        $("#tableWoMatAcc").DataTable({
            paging: items.length > 8,
            pageLength: 8,
            searching: false,
            info: false,
            ordering: false,
            autoWidth: false,
            language: {
                emptyTable: "Tidak ada bahan",
                paginate: {
                    next: '<i class="fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i>',
                },
            },
        });
    }

    var $print = $("#wo-mat-print");
    if (doc.print_url) {
        $print.attr("href", doc.print_url).show();
    } else {
        $print.hide();
    }

    var $acc = $("#wo-mat-acc-wrap").empty();
    if (!window.woMonitor && doc.can_ops) {
        $acc.append(
            '<button type="button" class="btn btn-sm pg-btn-confirm wo-mat-acc-ops" data-doc="' +
                Number(doc.id) +
                '"><i class="fe fe-check me-1"></i>ACC Kepala Ops</button>'
        );
    }
    if (!window.woMonitor && doc.can_qc) {
        $acc.append(
            '<button type="button" class="btn btn-sm pg-btn-confirm wo-mat-acc-qc" data-doc="' +
                Number(doc.id) +
                '"><i class="fe fe-check me-1"></i>ACC QC (potong stok)</button>'
        );
    }

    $("#modalWoMaterials").modal("show");
    if (typeof feather !== "undefined") feather.replace();
}

function woRenderMatLog() {
    return;
}

/** Job Order package → tab ACC Bahan (list per request). */
function woLoadMaterials(id, show) {
    if (show) {
        var $tab = $('button[data-bs-target="#pp-pane-bahan"]');
        if ($tab.length) {
            $tab.tab("show");
            if (typeof refreshPpBahanTable === "function") refreshPpBahanTable();
            return;
        }
    }
    $.get("/productionWorkOrders/" + Number(id))
        .done(function (d) {
            woDetail = d;
            var docs = ((d && d.documents) || []).filter(function (doc) {
                return (
                    (doc.type === "material_issue" || doc.type === "material_return") &&
                    (doc.document_status === "awaiting_ops" ||
                        doc.document_status === "awaiting_qc")
                );
            });
            if (!docs.length) {
                notifikasi("info", "ACC Bahan", "Tidak ada request menunggu ACC untuk WO ini.");
                return;
            }
            var doc = docs[docs.length - 1];
            woOpenMatAccRequest({
                id: doc.id,
                number: doc.number,
                stage: doc.document_status,
                pic: d.pic,
                wo_number: d.wo && d.wo.wo_number,
                pp_number: d.pp,
                items: doc.items || [],
                can_ops: d.can_ops && doc.document_status === "awaiting_ops",
                can_qc: d.can_qc && doc.document_status === "awaiting_qc",
                print_url: "/printProductionDocument/" + doc.id + "/form",
            });
        })
        .fail(woError);
}

`;
fs.writeFileSync(p, t.slice(0, start) + neu + t.slice(end));
console.log("OK");
