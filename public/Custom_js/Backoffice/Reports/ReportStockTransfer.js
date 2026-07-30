var table = null;
var stockTransferLogRows = {};

$(document).ready(function () {
    if ($(".datetimepicker").length > 0) {
        $(".datetimepicker").datetimepicker({
            format: "DD-MM-YYYY",
            icons: {
                up: "fas fa-angle-up",
                down: "fas fa-angle-down",
                next: "fas fa-angle-right",
                previous: "fas fa-angle-left",
            },
        });
    }

    // Set default filter range to start of month to today
    var dateNow = new Date();
    var mm = String(dateNow.getMonth() + 1).padStart(2, '0');
    var yyyy = dateNow.getFullYear();
    var dd = String(dateNow.getDate()).padStart(2, '0');

    var startOfMonth = "01-" + mm + "-" + yyyy;
    var todayStr = dd + "-" + mm + "-" + yyyy;
    
    if (!$("#start_date").val()) $("#start_date").val(startOfMonth);
    if (!$("#end_date").val()) $("#end_date").val(todayStr);

    initDataTable();

    $(".btn-filter-logs").on("click", function () {
        if (table) {
            setReportDataTableLoading("#tableStockTransferLogs", true);
            table.ajax.reload(function () {
                setReportDataTableLoading("#tableStockTransferLogs", false);
            }, false);
        }
    });
});

function initDataTable() {
    table = $("#tableStockTransferLogs").DataTable({
        bFilter: true,
        sDom: "fBtlpi",
        lengthMenu: [10, 25, 50, 100],
        ordering: true,
        order: [[0, "desc"]],
        searching: true,
        scrollX: true,
        autoWidth: false,
        serverSide: true,
        processing: false,
        language: {
            search: " ",
            sLengthMenu: "_MENU_",
            searchPlaceholder: "Cari Log...",
            info: "_START_ - _END_ of _TOTAL_ items",
            emptyTable: "Belum ada catatan log",
            zeroRecords: "Data tidak ditemukan",
            paginate: {
                next: ' <i class=" fa fa-angle-right"></i>',
                previous: '<i class="fa fa-angle-left"></i> ',
            },
        },
        ajax: {
            url: "/getStockTransferLogs",
            beforeSend: function () {
                $("#tableLogs-wrap").addClass("dt-pending");
            },
            complete: function () {
                setTimeout(function() {
                    $("#tableLogs-wrap").removeClass("dt-pending");
                }, 300);
            },
            data: function (d) {
                // Convert DD-MM-YYYY to YYYY-MM-DD for backend
                var start = $("#start_date").val() || "";
                var end = $("#end_date").val() || "";
                
                if (start.length === 10) {
                    var sp = start.split("-");
                    if (sp.length === 3) start = sp[2] + "-" + sp[1] + "-" + sp[0];
                }
                if (end.length === 10) {
                    var ep = end.split("-");
                    if (ep.length === 3) end = ep[2] + "-" + ep[1] + "-" + ep[0];
                }

                d.start_date = start;
                d.end_date = end;
            },
            dataSrc: function (json) {
                var rows = [];
                if (json && Array.isArray(json.data)) {
                    rows = json.data;
                } else if (Array.isArray(json)) {
                    rows = json;
                } else if (json && json.original) {
                    rows = Array.isArray(json.original.data) ? json.original.data : (Array.isArray(json.original) ? json.original : []);
                }
                
                stockTransferLogRows = {};
                rows.forEach(function (row) {
                    stockTransferLogRows[String(row.id)] = row;
                });
                return rows;
            }
        },
        columns: [
            {
                data: "created_at",
                render: function (data, type) {
                    if (!data || data === "-") return "-";
                    if (type === "sort" || type === "type") return String(data);
                    
                    var dateObj = new Date(data);
                    var dateStr = data;
                    var timeStr = "";
                    if (!isNaN(dateObj.getTime())) {
                        var d = String(dateObj.getDate()).padStart(2, '0');
                        var m = String(dateObj.getMonth() + 1).padStart(2, '0');
                        var y = dateObj.getFullYear();
                        var h = String(dateObj.getHours()).padStart(2, '0');
                        var min = String(dateObj.getMinutes()).padStart(2, '0');
                        var sec = String(dateObj.getSeconds()).padStart(2, '0');
                        dateStr = d + "-" + m + "-" + y;
                        timeStr = h + ":" + min + ":" + sec + " WIB";
                    }
                    
                    return `<div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:32px;height:32px;border-radius:8px;background:#f8fafc;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;color:#64748b;flex-shrink:0;">
                                    <i class="fe fe-calendar"></i>
                                </div>
                                <div style="display:flex;flex-direction:column;">
                                    <span class="fw-semibold text-dark" style="font-size:13px;">${dateStr}</span>
                                    <span style="font-size:12px;font-weight:700;color:#2563eb;">${timeStr}</span>
                                </div>
                            </div>`;
                }
            },
            {
                data: "action",
                render: function (data) {
                    var act = String(data || "").toLowerCase();
                    var bg = "background: #f8fafc; color: #475569; border: 1px solid #e2e8f0;";
                    var icon = "fe-activity";
                    
                    if (act === "create") { bg = "background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe;"; icon = "fe-file-plus"; }
                    else if (act === "update") { bg = "background: #f0f9ff; color: #0284c7; border: 1px solid #bae6fd;"; icon = "fe-edit"; }
                    else if (act === "delete") { bg = "background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;"; icon = "fe-trash-2"; }
                    else if (act === "ship") { bg = "background: #fffbeb; color: #d97706; border: 1px solid #fde68a;"; icon = "fe-truck"; }
                    else if (act === "accept") { bg = "background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0;"; icon = "fe-check-circle"; }
                    else if (act === "reject") { bg = "background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;"; icon = "fe-x-circle"; }

                    return `<span class="badge" style="${bg} font-size:11px; font-weight:700; padding:6px 10px; border-radius:6px; text-transform:uppercase; letter-spacing:0.5px;">
                                <i class="fe ${icon} me-1"></i> ${act}
                            </span>`;
                }
            },
            {
                data: null,
                render: function (row) {
                    var code = row.transfer_code || "-";
                    return `<span class="fw-semibold text-primary" style="font-size:13px;">${escapeHtml(code)}</span>`;
                }
            },
            {
                data: "created_by_name",
                render: function (data) {
                    var name = data || "-";
                    return `<div style="display:flex;align-items:center;gap:8px;">
                                <div style="width:24px;height:24px;border-radius:50%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;">
                                    <i class="fe fe-user" style="font-size:12px;color:#64748b;"></i>
                                </div>
                                <span class="fw-medium text-dark" style="font-size:13px;">${escapeHtml(name)}</span>
                            </div>`;
                }
            },
            {
                data: "what_changed",
                render: function (data, type, row) {
                    var summary = row.summary_human || data || "-";
                    return `<span style="font-size:13px; color:#475569; display:block; max-width:320px; white-space:normal;">${escapeHtml(summary)}</span>`;
                }
            },
            {
                data: null,
                className: "text-center",
                render: function (row) {
                    return `<a class="btn-action-icon btn-view-meta" href="javascript:void(0);" data-log-id="${row.id}"
                                style="background:#eff6ff;border:1px solid #bfdbfe;color:#2563eb;"
                                data-bs-toggle="tooltip" title="Lihat Detail Log">
                                <i class="far fa-eye" style="font-size:14px;"></i>
                            </a>`;
                }
            },
        ],
        drawCallback: function () {
            $("#tableLogs-wrap").removeClass("dt-pending");
            $('[data-bs-toggle="tooltip"]').tooltip();
        },
        initComplete: function () {
            $("#tableLogs-wrap").removeClass("dt-pending");
        },
    });
}

$(document).on("click", ".btn-view-meta", function () {
    var row = stockTransferLogRows[String($(this).attr("data-log-id"))];
    if (!row) return;

    var meta = row.meta || {};
    var before = meta.before || null;
    var after = meta.after || null;
    var activeSnapshot = after || before || {};
    var actionLabels = {
        create: "Pembuatan",
        update: "Perubahan",
        delete: "Penghapusan",
        ship: "Pengiriman",
        accept: "Penerimaan",
        reject: "Penolakan",
    };

    $("#metaModalTitle").text((actionLabels[row.action] || "Detail") + " Stock Transfer");
    $("#metaModalSubtitle").text((row.transfer_code || "-") + " • oleh " + (row.created_by_name || "-"));

    var html = renderTransferRoute(activeSnapshot);
    if (before && after) {
        html += renderHeaderChanges(before.header || {}, after.header || {});
    }

    if (row.action === "update" && before && after) {
        html += '<div class="row g-3 mt-1">';
        html += '<div class="col-lg-6">' + renderItemsTable(
            before.items || [],
            "Daftar Produk Sebelumnya",
            "before",
            after.items || []
        ) + "</div>";
        html += '<div class="col-lg-6">' + renderItemsTable(
            after.items || [],
            "Daftar Produk Sesudahnya",
            "after",
            before.items || []
        ) + "</div></div>";
    } else {
        var items = (after && after.items) || (before && before.items) || [];
        html += renderItemsTable(items, "Daftar Produk", "current", []);
    }

    $("#metaContent").html(html);
    $("#modalViewMeta").modal("show");
});

function renderTransferRoute(snapshot) {
    var h = snapshot && snapshot.header ? snapshot.header : {};
    return `
        <div class="log-transfer-route">
            <div class="log-route-card">
                <div class="log-route-title text-primary"><i class="fe fe-log-out"></i> Dari (Asal)</div>
                ${renderInfoGrid([
                    ["Pengirim", h.sender_name],
                    ["Gudang Asal", h.from_warehouse_name],
                    ["Tanggal", formatLogDate(h.transfer_date)],
                    ["Catatan", h.note],
                ])}
            </div>
            <div class="log-route-arrow">
                <span><i class="fe fe-arrow-right"></i></span>
                <small>TRANSFER</small>
            </div>
            <div class="log-route-card">
                <div class="log-route-title text-success"><i class="fe fe-log-in"></i> Ke (Tujuan)</div>
                ${renderInfoGrid([
                    ["Gudang Tujuan", h.to_warehouse_name],
                    ["Penerima", h.receiver_name],
                    ["Status", statusLabel(h.status)],
                    ["Catatan Penerimaan", h.accept_note],
                ])}
            </div>
        </div>`;
}

function renderInfoGrid(rows) {
    return '<div class="row g-3 mt-1">' + rows.map(function (row) {
        return `<div class="col-6">
            <div class="log-info-label">${escapeHtml(row[0])}</div>
            <div class="log-info-value">${escapeHtml(valueOrDash(row[1]))}</div>
        </div>`;
    }).join("") + "</div>";
}

function renderHeaderChanges(before, after) {
    var fields = [
        ["Tanggal Transfer", "transfer_date", formatLogDate],
        ["Pengirim", "sender_name"],
        ["Gudang Asal", "from_warehouse_name"],
        ["Gudang Tujuan", "to_warehouse_name"],
        ["Penerima", "receiver_name"],
        ["Catatan Pengiriman", "note"],
        ["Catatan Penerimaan", "accept_note"],
        ["Status", "status", statusLabel],
        ["ACC Oleh", "acc_by_name"],
    ];
    var changes = fields.filter(function (field) {
        return normalizeCompare(before[field[1]]) !== normalizeCompare(after[field[1]]);
    });
    if (!changes.length) return "";

    return `<div class="log-section mt-3">
        <div class="log-section-title"><i class="fe fe-edit-3 text-primary"></i> Perubahan Header</div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0 log-change-table">
                <thead><tr><th>Data</th><th>Sebelumnya</th><th>Menjadi</th></tr></thead>
                <tbody>${changes.map(function (field) {
                    var formatter = field[2] || valueOrDash;
                    return `<tr>
                        <td class="fw-semibold">${escapeHtml(field[0])}</td>
                        <td><span class="log-value-before">${escapeHtml(formatter(before[field[1]]))}</span></td>
                        <td><span class="log-value-after"><i class="fe fe-arrow-right me-1"></i>${escapeHtml(formatter(after[field[1]]))}</span></td>
                    </tr>`;
                }).join("")}</tbody>
            </table>
        </div>
    </div>`;
}

function renderItemsTable(items, title, side, comparisonItems) {
    items = Array.isArray(items) ? items : [];
    comparisonItems = Array.isArray(comparisonItems) ? comparisonItems : [];
    var comparisonMap = {};
    comparisonItems.forEach(function (item) {
        comparisonMap[itemKey(item)] = item;
    });

    var rows = items.map(function (item) {
        var other = comparisonMap[itemKey(item)];
        var changed = other && (
            Number(other.qty || 0) !== Number(item.qty || 0)
            || normalizeCompare(other.qty_received) !== normalizeCompare(item.qty_received)
        );
        var badge = "";
        if (side === "before" && !other) badge = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">Dihapus</span>';
        if (side === "after" && !other) badge = '<span class="badge bg-success-subtle text-success border border-success-subtle">Ditambah</span>';
        if (changed) badge = '<span class="badge bg-warning-subtle text-warning border border-warning-subtle">Qty berubah</span>';

        return `<tr class="${changed ? "log-item-changed" : ""}">
            <td>
                <div class="fw-semibold text-dark">${escapeHtml(valueOrDash(item.product_name))}</div>
                <small class="text-muted">${escapeHtml(valueOrDash(item.variant_name))}</small>
            </td>
            <td><span class="log-sku">${escapeHtml(valueOrDash(item.sku))}</span></td>
            <td class="text-center fw-semibold">${escapeHtml(formatQty(item.qty, item.unit_name))}</td>
            <td class="text-center">${escapeHtml(
                item.qty_received == null ? "-" : formatQty(item.qty_received, item.unit_name)
            )}</td>
            <td class="text-center">${badge || '<span class="text-muted">—</span>'}</td>
        </tr>`;
    }).join("");

    if (!rows) {
        rows = '<tr><td colspan="5" class="text-center text-muted py-4">Tidak ada produk.</td></tr>';
    }

    return `<div class="log-section ${side === "current" ? "mt-3" : ""}">
        <div class="log-section-title"><i class="fe fe-list text-primary"></i> ${escapeHtml(title)}</div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 log-items-table">
                <thead><tr><th>Produk</th><th>SKU</th><th class="text-center">Qty Kirim</th><th class="text-center">Qty Diterima</th><th class="text-center">Perubahan</th></tr></thead>
                <tbody>${rows}</tbody>
            </table>
        </div>
    </div>`;
}

function itemKey(item) {
    return String(item.product_variant_id || 0) + ":" + String(item.unit_id || 0);
}

function formatQty(value, unit) {
    var number = Number(value || 0);
    var text = Number.isInteger(number) ? String(number) : String(number);
    return text + " " + valueOrDash(unit);
}

function formatLogDate(value) {
    if (!value) return "-";
    var parts = String(value).split("-");
    return parts.length === 3 ? parts[2] + "-" + parts[1] + "-" + parts[0] : String(value);
}

function statusLabel(value) {
    var labels = {
        0: "Dihapus",
        1: "Pending",
        2: "Dikirim",
        3: "Ditolak",
        4: "Diterima",
    };
    return labels[Number(value)] || "-";
}

function valueOrDash(value) {
    return value == null || value === "" ? "-" : String(value);
}

function normalizeCompare(value) {
    return value == null || value === "" ? "" : String(value);
}

$(document).on("click", ".btn-view-meta-legacy", function () {
    var metaStr = $(this).attr("data-meta") || "{}";
    var meta = {};
    try { meta = JSON.parse(metaStr); } catch (e) {}

    var html = "";
    
    if (meta.before || meta.after) {
        var beforeObj = meta.before || {};
        var afterObj = meta.after || {};
        var allKeys = [];
        
        if (typeof beforeObj === 'object' && beforeObj !== null) Object.keys(beforeObj).forEach(k => { if (allKeys.indexOf(k) === -1) allKeys.push(k); });
        if (typeof afterObj === 'object' && afterObj !== null) Object.keys(afterObj).forEach(k => { if (allKeys.indexOf(k) === -1) allKeys.push(k); });
        
        if (allKeys.length > 0) {
            html += `<table class="table table-bordered table-striped" style="font-size:13px; border-radius: 8px; overflow: hidden;">
                        <thead style="background:#f8fafc;">
                            <tr>
                                <th style="color:#475569; font-weight:700; width: 30%;">Data / Atribut</th>
                                <th style="color:#475569; font-weight:700; width: 35%;">Sebelumnya</th>
                                <th style="color:#475569; font-weight:700; width: 35%;">Menjadi</th>
                            </tr>
                        </thead>
                        <tbody>`;
            allKeys.forEach(key => {
                var vBefore = beforeObj[key] !== undefined ? beforeObj[key] : "-";
                var vAfter = afterObj[key] !== undefined ? afterObj[key] : "-";
                if (typeof vBefore === 'object' && vBefore !== null) vBefore = JSON.stringify(vBefore, null, 2);
                if (typeof vAfter === 'object' && vAfter !== null) vAfter = JSON.stringify(vAfter, null, 2);
                
                var bgClass = "";
                var iconAfter = "";
                if (vBefore !== vAfter && vBefore !== "-" && vAfter !== "-") {
                    bgClass = "background: #f0fdf4;"; // highlight light green for changed values
                    iconAfter = '<i class="fe fe-arrow-right text-success me-1"></i> ';
                }
                
                var showBefore = escapeHtml(String(vBefore));
                var showAfter = escapeHtml(String(vAfter));
                // Make JSON objects readable if any
                if (showBefore.indexOf("{") !== -1 || showBefore.indexOf("[") !== -1) showBefore = '<pre style="white-space:pre-wrap;margin:0;font-size:11px;">'+showBefore+'</pre>';
                if (showAfter.indexOf("{") !== -1 || showAfter.indexOf("[") !== -1) showAfter = '<pre style="white-space:pre-wrap;margin:0;font-size:11px;">'+showAfter+'</pre>';

                html += `<tr>
                            <td class="fw-semibold text-dark">${escapeHtml(key)}</td>
                            <td class="text-danger" style="word-break: break-all;">${showBefore}</td>
                            <td class="text-success fw-medium" style="word-break: break-all; ${bgClass}">${iconAfter}${showAfter}</td>
                         </tr>`;
            });
            html += `   </tbody>
                     </table>`;
        } else {
             html += `<div class="alert alert-info border-0" style="background:#f0f9ff; color:#0369a1;"><i class="fe fe-info me-2"></i> Tidak ada data perubahan spesifik (before/after kosong).</div>`;
        }
    } else {
        var keys = Object.keys(meta);
        if (keys.length > 0) {
             html += `<table class="table table-bordered table-striped" style="font-size:13px; border-radius: 8px; overflow: hidden;">
                        <thead style="background:#f8fafc;">
                            <tr>
                                <th style="color:#475569; font-weight:700; width: 40%;">Key / Properti</th>
                                <th style="color:#475569; font-weight:700; width: 60%;">Value</th>
                            </tr>
                        </thead>
                        <tbody>`;
            keys.forEach(k => {
                var v = meta[k];
                if (typeof v === 'object' && v !== null) v = JSON.stringify(v, null, 2);
                
                var showV = escapeHtml(String(v));
                if (showV.indexOf("{") !== -1 || showV.indexOf("[") !== -1) showV = '<pre style="white-space:pre-wrap;margin:0;font-size:11px;">'+showV+'</pre>';

                html += `<tr>
                            <td class="fw-semibold text-dark">${escapeHtml(k)}</td>
                            <td style="word-break: break-all;">${showV}</td>
                         </tr>`;
            });
            html += `   </tbody>
                     </table>`;
        } else {
            html += `<div class="alert alert-info border-0" style="background:#f0f9ff; color:#0369a1;"><i class="fe fe-info me-2"></i> Tidak ada rincian data JSON untuk log ini.</div>`;
        }
    }

    $("#metaContent").html(html);
    $("#modalViewMeta").modal("show");
});

function escapeHtml(text) {
    if (text == null) return "";
    var map = {
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#039;",
    };
    return String(text).replace(/[&<>"']/g, function (m) {
        return map[m];
    });
}
