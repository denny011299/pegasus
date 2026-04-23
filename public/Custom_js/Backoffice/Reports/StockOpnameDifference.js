var table;
var dates = null;
var filterType = "all";

$(document).ready(function () {
    inisialisasi();
    initTypeItemFilter();
    var startMonth = moment().startOf("month").format("DD-MM-YYYY");
    var endMonth = moment().endOf("month").format("DD-MM-YYYY");
    $("#start_date").val(startMonth);
    $("#end_date").val(endMonth);
    dates = [startMonth, endMonth];
    refreshSelisihOpname();
});

function inisialisasi() {
    table = $("#tableSelisihOpname").DataTable({
        bFilter: true,
        sDom: "fBtlpi",
        lengthMenu: [10, 25, 50, 100],
        ordering: true,
        searching: false,
        scrollX: true,
        language: {
            search: " ",
            sLengthMenu: "_MENU_",
            searchPlaceholder: "Cari Selisih Opname",
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
            { data: "kode" },
            { data: "tanggal_text" },
            { data: "item_summary" },
            { data: "nominal_summary", className: "text-end" },
        ],
        initComplete: () => {
            $(".dataTables_filter").appendTo("#tableSearch");
            $(".dataTables_filter").appendTo(".search-input");
            $(".dataTables_filter label").prepend('<i class="fa fa-search"></i> ');
        },
    });
}

function formatCurrency(value) {
    return new Intl.NumberFormat("id-ID").format(Math.abs(value || 0));
}

function formatNominal(value) {
    var num = parseFloat(value || 0);
    if (num > 0) return `<span class="text-success">+ Rp ${formatCurrency(num)}</span>`;
    if (num < 0) return `<span class="text-danger">- Rp ${formatCurrency(num)}</span>`;
    return "Rp 0";
}

function formatDetailRow(data) {
    var html = `
        <div class="child-row-wrapper" style="display:none; max-height: 360px; overflow-y: auto; overflow-x: auto;">
            <table class="table table-sm mb-0 report-selisih-child">
                <thead>
                    <tr>
                        <th style="width:9%;">Sumber</th>
                        <th style="width:28%;">Item</th>
                        <th class="text-end" style="width:13%;">Stok sistem</th>
                        <th class="text-end" style="width:13%;">Stok fisik</th>
                        <th class="text-end" style="width:12%;">Selisih</th>
                        <th class="text-end" style="width:12%;">Harga satuan</th>
                        <th class="text-end" style="width:13%;">Nominal</th>
                    </tr>
                </thead>
                <tbody>
    `;

    if (data.details && data.details.length > 0) {
        for (let i = 0; i < data.details.length; i++) {
            const d = data.details[i];
            const itemName =
                d.variant_name && d.variant_name !== "-"
                    ? `${d.item_name} - ${d.variant_name}`
                    : d.item_name;
            const sumber = (d.sumber || "-").toString().toUpperCase();
            html += `
                <tr>
                    <td><span class="badge bg-light text-dark border text-uppercase selisih-badge-sumber">${sumber}</span></td>
                    <td>${itemName || "-"}</td>
                    <td class="text-end text-nowrap">${d.stock_system || "-"}</td>
                    <td class="text-end text-nowrap">${d.stock_fisik || "-"}</td>
                    <td class="text-end text-nowrap fw-medium">${d.selisih_text || "-"}</td>
                    <td class="text-end text-nowrap">Rp ${formatCurrency(d.harga_satuan || 0)}</td>
                    <td class="text-end text-nowrap">${formatNominal(d.nominal || 0)}</td>
                </tr>
            `;
        }
    } else {
        html += `<tr><td colspan="7" class="text-center text-muted py-3">Tidak ada detail selisih</td></tr>`;
    }

    html += `</tbody></table></div>`;
    return html;
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

function refreshSelisihOpname() {
    $.ajax({
        url: "/getReportSelisihOpname",
        method: "get",
        data: {
            date: dates,
            type: filterType,
            item_id: $("#selisih_item_id").val(),
        },
        beforeSend: function () {
            setReportDataTableLoading("#tableSelisihOpname", true);
        },
        complete: function () {
            setReportDataTableLoading("#tableSelisihOpname", false);
        },
        success: function (e) {
            if (!Array.isArray(e)) e = e.original || [];
            table.clear().draw();
            for (let i = 0; i < e.length; i++) {
                e[i].tanggal_text = moment(e[i].tanggal).format("D MMM YYYY");
                e[i].item_summary = `${e[i].total_item_selisih} Item Selisih`;
                e[i].nominal_summary = formatNominal(e[i].total_nominal || 0);
            }
            table.rows.add(e).draw();
            feather.replace();
        },
        error: function (err) {
            console.error("Gagal load report selisih opname:", err);
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
    refreshSelisihOpname();
}

function initTypeItemFilter() {
    $("#selisih_type").val("all");
    $("#selisih_item_id").prop("disabled", true).empty();
}

function initItemAutocompleteByType(type) {
    var $item = $("#selisih_item_id");
    $item.empty();
    if (type === "bahan") {
        $item.prop("disabled", false);
        autocompleteSupplies("#selisih_item_id");
    } else if (type === "product") {
        $item.prop("disabled", false);
        autocompleteProductVariantOnly("#selisih_item_id");
    } else {
        $item.prop("disabled", true);
    }
}

$(document).on("click", ".btn-clear", function () {
    dates = null;
    $("#start_date").val("");
    $("#end_date").val("");
    filterType = "all";
    $("#selisih_type").val("all").trigger("change");
    $("#selisih_item_id").empty().trigger("change");
    refreshSelisihOpname();
});

$(document).on("change", "#selisih_type", function () {
    filterType = $(this).val() || "all";
    initItemAutocompleteByType(filterType);
    refreshSelisihOpname();
});

$(document).on("change", "#selisih_item_id", function () {
    refreshSelisihOpname();
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
        type: filterType,
        item_id: $("#selisih_item_id").val(),
    };
    window.open("/generateReportSelisihOpnamePdf?" + $.param(params), "_blank");
});

$("#tableSelisihOpname tbody").on("click", "td.details-control .btn-toggle-detail", function (e) {
    e.preventDefault();
    e.stopPropagation();
    var tr = $(this).closest("tr");
    var row = table.row(tr);
    toggleDetailRow(tr, row);
});

$("#tableSelisihOpname tbody").on("click", "tr", function (e) {
    if ($(e.target).closest("tr.child").length) return;
    var tr = $(this).closest("tr");
    if (tr.hasClass("child")) return;
    var row = table.row(tr);
    if (!row.data()) return;
    toggleDetailRow(tr, row);
});
