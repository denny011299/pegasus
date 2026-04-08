var table;
var dates = null;
autocompleteSupplier("#supplier");
autocompleteProductVariantOnly("#product_id");

$(document).ready(function () {
    inisialisasi();
    var startMonth = moment().startOf("month").format("DD-MM-YYYY");
    var endMonth = moment().endOf("month").format("DD-MM-YYYY");
    $("#start_date").val(startMonth);
    $("#end_date").val(endMonth);
    dates = [startMonth, endMonth];
    refreshEfisiensi();
});

function inisialisasi() {
    table = $("#tableReportEfisiensi").DataTable({
        bFilter: true,
        sDom: "fBtlpi",
        lengthMenu: [10, 25, 50, 100],
        ordering: true,
        searching: false,
        language: {
            search: " ",
            sLengthMenu: "_MENU_",
            searchPlaceholder: "Cari Efisiensi",
            info: "_START_ - _END_ of _TOTAL_ items",
            paginate: {
                next: ' <i class=" fa fa-angle-right"></i>',
                previous: '<i class="fa fa-angle-left"></i> ',
            },
        },
        columns: [
            {
                data: null,
                className: "details-control text-center",
                orderable: false,
                defaultContent:
                    '<a href="javascript:void(0);" class="btn-action-icon p-1 btn-toggle-detail"><i class="fa fa-plus"></i></a>',
            },
            { data: "product_name" },
            { data: "total_summary" },
            { data: "good_qty_summary" },
            { data: "reject_summary" },
            { data: "reject_ratio_summary" },
            { data: "waste_ratio_summary" },
            { data: "data_bahan_summary" },
            { data: "yield_summary" },
            { data: "operational_summary" },
        ],
        initComplete: () => {
            $(".dataTables_filter").appendTo("#tableSearch");
            $(".dataTables_filter").appendTo(".search-input");
            $(".dataTables_filter label").prepend('<i class="fa fa-search"></i> ');
        },
    });
}

function statusBadge(status) {
    if (status == 3) return '<span class="badge bg-danger">Tolak</span>';
    if (status == 2) return '<span class="badge bg-success">Berhasil</span>';
    if (status == 1) return '<span class="badge bg-secondary">Pending</span>';
    if (status == 4) return '<span class="badge bg-warning text-dark">Menunggu batal</span>';
    return '<span class="badge bg-secondary">-</span>';
}

function ratioBadge(val, inverse) {
    var v = parseFloat(val || 0);
    if (inverse) {
        if (v >= 90) return `<span class="badge bg-success">${v.toFixed(2)}%</span>`;
        if (v >= 75) return `<span class="badge bg-warning">${v.toFixed(2)}%</span>`;
        return `<span class="badge bg-danger">${v.toFixed(2)}%</span>`;
    }
    if (v <= 5) return `<span class="badge bg-success">${v.toFixed(2)}%</span>`;
    if (v <= 15) return `<span class="badge bg-warning">${v.toFixed(2)}%</span>`;
    return `<span class="badge bg-danger">${v.toFixed(2)}%</span>`;
}

function formatDetailRow(data) {
    var html = `
        <div class="child-row-wrapper" style="display:none; max-height: 320px; overflow-y: auto; overflow-x: auto;">
            <table class="table table-sm table-bordered mb-0 report-efisiensi-child">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Kode Produksi</th>
                        <th>Qty Produksi</th>
                        <th>Status</th>
                        <th>Log bahan</th>
                        <th>Pemakaian Bahan</th>
                        <th>Bahan Terbuang</th>
                    </tr>
                </thead>
                <tbody>
    `;
    if (data.details && data.details.length > 0) {
        for (let i = 0; i < data.details.length; i++) {
            var d = data.details[i];
            var tracked =
                d.material_tracked === true
                    ? '<span class="badge bg-success">Terlacak</span>'
                    : '<span class="badge bg-warning text-dark">Belum terlacak</span>';
            html += `
                <tr>
                    <td>${moment(d.production_date).format("D MMM YYYY")}</td>
                    <td>${d.production_code || "-"}</td>
                    <td>${d.qty} ${d.unit_name || ""}</td>
                    <td>${statusBadge(d.status)}</td>
                    <td>${tracked}</td>
                    <td>${d.material_usage_text || "-"}</td>
                    <td>${d.material_waste_text || "-"}</td>
                </tr>
            `;
        }
    } else {
        html += '<tr><td colspan="7" class="text-center">Tidak ada detail</td></tr>';
    }
    html += "</tbody></table></div>";
    return html;
}

function updateKpiSummary(rows) {
    var wrap = $("#efisiensi-kpi-summary");
    if (!rows || rows.length === 0) {
        wrap.addClass("d-none");
        return;
    }
    wrap.removeClass("d-none");
    var n = rows.length;
    var sumScore = 0;
    var sumGood = 0;
    var sumUntracked = 0;
    var highRisk = 0;
    for (var i = 0; i < n; i++) {
        sumScore += parseFloat(rows[i].operational_score || 0);
        sumGood += parseInt(rows[i].good_qty || 0, 10);
        sumUntracked += parseInt(rows[i].untracked_batch_count || 0, 10);
        var risk = rows[i].risk_level || "";
        if (risk === "tinggi") highRisk++;
    }
    $("#kpi-avg-score").text((sumScore / n).toFixed(2) + "%");
    $("#kpi-good-qty").text(sumGood.toLocaleString("id-ID"));
    $("#kpi-untracked").text(sumUntracked.toLocaleString("id-ID"));
    $("#kpi-high-risk").text(highRisk + " / " + n);
}

function toggleDetailRow(tr, row) {
    var toggleBtn = tr.find(".btn-toggle-detail");
    var icon = toggleBtn.find("i");
    if (row.child.isShown()) {
        var wrapper = tr.next("tr").find(".child-row-wrapper");
        wrapper.stop(true, true).slideUp(140, function () {
            row.child.hide();
        });
        tr.removeClass("shown");
        icon.removeClass("fa-minus").addClass("fa-plus");
    } else {
        row.child(formatDetailRow(row.data())).show();
        tr.addClass("shown");
        var wrapperShow = tr.next("tr").find(".child-row-wrapper");
        wrapperShow.stop(true, true).slideDown(160);
        icon.removeClass("fa-plus").addClass("fa-minus");
    }
}

function refreshEfisiensi() {
    $.ajax({
        url: "/getReportEfisiensiProduksi",
        method: "get",
        data: {
            date: dates,
            supplier_id: $("#supplier").val(),
            product_variant_id: $("#product_id").val(),
        },
        success: function (e) {
            if (!Array.isArray(e)) e = e.original || [];
            table.clear().draw();
            for (let i = 0; i < e.length; i++) {
                e[i].total_summary = `${e[i].production_count} Batch (${e[i].total_qty} qty)`;
                e[i].good_qty_summary = (e[i].good_qty ?? 0).toLocaleString("id-ID");
                e[i].reject_summary = `${e[i].total_reject_count} batch tolak (${e[i].total_reject_qty} qty)`;
                e[i].reject_ratio_summary = ratioBadge(e[i].reject_ratio || 0, false);
                e[i].waste_ratio_summary = ratioBadge(e[i].material_waste_ratio || 0, false);
                var untracked = parseInt(e[i].untracked_batch_count || 0, 10);
                e[i].data_bahan_summary =
                    untracked > 0
                        ? `<span class="badge bg-warning text-dark" title="Tidak ada log stok pemakaian bahan">${untracked} batch</span>`
                        : '<span class="badge bg-light text-muted">OK</span>';
                e[i].yield_summary = ratioBadge(e[i].yield_pct ?? e[i].efficiency_ratio ?? 0, true);
                e[i].operational_summary = ratioBadge(e[i].operational_score || 0, true);
            }
            updateKpiSummary(e);
            table.rows.add(e).draw();
            feather.replace();
        },
        error: function (err) {
            console.error("Gagal load report efisiensi produksi:", err);
            updateKpiSummary([]);
        },
    });
}

function normalizeDateValue(value) {
    if (!value) return "";
    var parsed = moment(value, ["DD-MM-YYYY", "D MMM YYYY", "DD MMM YYYY", "YYYY-MM-DD"], true);
    if (!parsed.isValid()) return "";
    return parsed.format("DD-MM-YYYY");
}

function applyFilter() {
    dates = [];
    var start = normalizeDateValue($("#start_date").val());
    var end = normalizeDateValue($("#end_date").val());
    if (start && !end) end = start;
    if (!start && end) start = end;
    $("#start_date").val(start);
    $("#end_date").val(end);
    dates.push(start);
    dates.push(end);
    refreshEfisiensi();
}

$(document).on("click", ".btn-clear", function () {
    dates = null;
    $("#start_date").val("");
    $("#end_date").val("");
    $("#supplier").empty();
    $("#product_id").empty();
    refreshEfisiensi();
});

$(document).on("change", "#supplier", function () {
    applyFilter();
});

$(document).on("change", "#product_id", function () {
    applyFilter();
});

$(document).on("change change.datetimepicker dp.change", "#start_date, #end_date", function () {
    applyFilter();
});

$(document).on("click", ".btn-export-pdf", function () {
    var start = normalizeDateValue($("#start_date").val());
    var end = normalizeDateValue($("#end_date").val());
    if (start && !end) end = start;
    if (!start && end) start = end;
    var params = {
        date: [start, end],
        supplier_id: $("#supplier").val(),
        product_variant_id: $("#product_id").val(),
    };
    window.open("/generateReportEfisiensiProduksiPdf?" + $.param(params), "_blank");
});

$("#tableReportEfisiensi tbody").on("click", "td.details-control .btn-toggle-detail", function (e) {
    e.preventDefault();
    e.stopPropagation();
    var tr = $(this).closest("tr");
    var row = table.row(tr);
    toggleDetailRow(tr, row);
});

$("#tableReportEfisiensi tbody").on("click", "tr", function (e) {
    if ($(e.target).closest("tr.child").length) return;
    var tr = $(this).closest("tr");
    if (tr.hasClass("child")) return;
    var row = table.row(tr);
    if (!row.data()) return;
    toggleDetailRow(tr, row);
});
