var table;
var filterType = "all";

$(document).ready(function () {
    inisialisasi();
    initTypeItemFilter();
    var today = moment().format("DD-MM-YYYY");
    $("#aging_as_of").val(today);
    refreshStockAging();
});

function inisialisasi() {
    table = $("#tableStockAging").DataTable({
        bFilter: true,
        sDom: "fBtlpi",
        lengthMenu: [10, 25, 50, 100],
        ordering: true,
        order: [[4, "desc"]],
        searching: false,
        language: {
            search: " ",
            sLengthMenu: "_MENU_",
            searchPlaceholder: "Cari aging",
            info: "_START_ - _END_ of _TOTAL_ items",
            paginate: {
                next: ' <i class=" fa fa-angle-right"></i>',
                previous: '<i class="fa fa-angle-left"></i> ',
            },
        },
        columns: [
            {
                data: "sumber",
                render: function (data) {
                    var t = (data || "").toString().toLowerCase();
                    var label = t === "bahan" ? "BAHAN" : "PRODUK";
                    return `<span class="badge bg-light text-dark border text-uppercase">${label}</span>`;
                },
            },
            { data: "item_label" },
            { data: "unit_name" },
            { data: "qty_display", className: "text-end text-nowrap" },
            { data: "weighted_age_days", className: "text-end text-nowrap" },
            {
                data: "bucket",
                render: function (data) {
                    return `<span class="badge bg-light text-primary border aging-badge-bucket">${data || "-"}</span>`;
                },
            },
            {
                data: "oldest_layer_date",
                render: function (data) {
                    if (!data) return "-";
                    return moment(data, "YYYY-MM-DD").format("D MMM YYYY");
                },
            },
            {
                data: "unit_price",
                className: "text-end text-nowrap",
                render: function (data) {
                    return "Rp " + formatCurrency(data || 0);
                },
            },
            {
                data: "stock_value",
                className: "text-end text-nowrap",
                render: function (data) {
                    return "Rp " + formatCurrency(data || 0);
                },
            },
        ],
        initComplete: function () {
            $(".dataTables_filter").appendTo("#tableSearch");
            $(".dataTables_filter").appendTo(".search-input");
            $(".dataTables_filter label").prepend('<i class="fa fa-search"></i> ');
        },
    });
}

function formatCurrency(value) {
    return new Intl.NumberFormat("id-ID").format(Math.round(value || 0));
}

function normalizeAsOf(value) {
    if (!value) return "";
    var parsed = moment(value, ["DD-MM-YYYY", "D MMM YYYY", "DD MMM YYYY", "YYYY-MM-DD"], true);
    if (!parsed.isValid()) return "";
    return parsed.format("DD-MM-YYYY");
}

function refreshStockAging() {
    var asOf = normalizeAsOf($("#aging_as_of").val());
    $.ajax({
        url: "/getReportStockAging",
        method: "get",
        data: {
            type: filterType,
            item_id: $("#aging_item_id").val(),
            as_of: asOf,
        },
        success: function (e) {
            if (!Array.isArray(e)) e = e.original || [];
            table.clear().draw();
            table.rows.add(e).draw();
            feather.replace();
        },
        error: function (err) {
            console.error("Gagal load stock aging:", err);
        },
    });
}

function initTypeItemFilter() {
    $("#aging_type").val("all");
    $("#aging_item_id").prop("disabled", true).empty();
}

function initItemAutocompleteByType(type) {
    var $item = $("#aging_item_id");
    $item.empty();
    if (type === "bahan") {
        $item.prop("disabled", false);
        autocompleteSupplies("#aging_item_id");
    } else if (type === "product") {
        $item.prop("disabled", false);
        autocompleteProductVariantOnly("#aging_item_id");
    } else {
        $item.prop("disabled", true);
    }
}

$(document).on("click", ".btn-clear", function () {
    filterType = "all";
    $("#aging_type").val("all").trigger("change");
    $("#aging_item_id").empty().trigger("change");
    var today = moment().format("DD-MM-YYYY");
    $("#aging_as_of").val(today);
    refreshStockAging();
});

$(document).on("change", "#aging_type", function () {
    filterType = $(this).val() || "all";
    initItemAutocompleteByType(filterType);
    refreshStockAging();
});

$(document).on("change", "#aging_item_id", function () {
    refreshStockAging();
});

$(document).on("change change.datetimepicker dp.change", "#aging_as_of", function () {
    refreshStockAging();
});

$(document).on("click", ".btn-export-pdf", function () {
    var asOf = normalizeAsOf($("#aging_as_of").val());
    var params = {
        type: filterType,
        item_id: $("#aging_item_id").val(),
        as_of: asOf,
    };
    window.open("/generateReportStockAgingPdf?" + $.param(params), "_blank");
});
