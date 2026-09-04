var mode = 1;
var table;
var stockOpnameXhr = null;

$(document).ready(function () {
    inisialisasi();
    refreshStockOpname();
});

function setStockOpnameTableLoading(isLoading) {
    var $wrap = $("#tableStockOpname-wrap");
    if (!$wrap.length) return;
    $wrap.toggleClass("is-loading", !!isLoading);
}

function inisialisasi() {
    if ($.fn.DataTable.isDataTable("#tableStockOpname")) {
        table = $("#tableStockOpname").DataTable();
        return;
    }

    table = $("#tableStockOpname").DataTable({
        processing: true,
        bFilter: true,
        sDom: "fBtlpi",
        lengthMenu: [10, 25, 50, 100],
        ordering: false,
        deferRender: true,
        autoWidth: false,
        scrollX: false,
        language: {
            search: " ",
            sLengthMenu: "_MENU_",
            searchPlaceholder: "Cari",
            info: "_START_ - _END_ of _TOTAL_ items",
            emptyTable: "Tidak ada data stok opname",
            zeroRecords: "Stok opname tidak ditemukan",
            processing:
                '<div><span class="spinner-border spinner-border-sm text-primary" role="status"></span><span>Memuat data...</span></div>',
            paginate: {
                next: ' <i class=" fa fa-angle-right"></i>',
                previous: '<i class="fa fa-angle-left"></i> ',
            },
        },
        columns: [
            { data: "stob_date" },
            { data: "warehouse_name", defaultContent: "-" },
            { data: "staff_name", defaultContent: "-" },
            { data: "stob_code" },
            {
                data: "created_by_name",
                defaultContent: "-",
                render: function (data) {
                    return typeof renderCreatedByName === "function"
                        ? renderCreatedByName(data)
                        : data;
                },
            },
            { data: "acc_by_name", defaultContent: "-" },
            { data: "jenis_text", defaultContent: "-" },
            { data: "status_text", defaultContent: "-" },
            {
                data: "action",
                defaultContent: "-",
                class: "text-center align-middle",
            },
        ],
        initComplete: function () {
            var $filter = $(".dataTables_filter").last();
            $filter.appendTo("#tableSearch");
            $filter.appendTo(".search-input");
            if (!$filter.find("label .fa-search").length) {
                $filter.find("label").prepend('<i class="fa fa-search"></i> ');
            }
            if (table) table.columns.adjust();
        },
        drawCallback: function () {
            if (typeof feather !== "undefined") feather.replace();
            if (table) table.columns.adjust();
        },
    });
}

function refreshStockOpname() {
    if (stockOpnameXhr && stockOpnameXhr.readyState !== 4) {
        stockOpnameXhr.abort();
    }

    $("#tableStockOpname-wrap").removeClass("dt-ready").addClass("dt-pending");
    setStockOpnameTableLoading(true);

    stockOpnameXhr = $.ajax({
        url: "/getStockOpnameBahan",
        method: "get",
        success: function (e) {
            if (!Array.isArray(e)) {
                e = e.original || [];
            }
            const processedRows = e.map((item) => {
                const statusTextMap = {
                    1: `<span class="badge bg-secondary" style="font-size: 12px">Menunggu</span>`,
                    2: `<span class="badge bg-success" style="font-size: 12px">Disetujui</span>`,
                    3: `<span class="badge bg-danger" style="font-size: 12px">Ditolak</span>`,
                };

                const downloadIcon = item.is_draft
                    ? ""
                    : `
                    <a href="/generateStockOpnameBahan/${item.stob_id}" class="me-2 btn-action-icon p-2 btn_download" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" title="Download Stock Opname">
                            <i class="fe fe-file-text"></i>
                        </a>`;

                return {
                    ...item,
                    stob_date: item.stob_date
                        ? moment(item.stob_date).format("D MMM YYYY")
                        : "-",
                    warehouse_name: item.warehouse_name || "-",
                    staff_name: item.staff_name || "-",
                    stob_code: item.stob_code || "-",
                    created_by_name: item.created_by_name || "-",
                    acc_by_name: item.acc_by_name || "-",
                    jenis_text:
                        item.sto_type == 2
                            ? `<span class="badge bg-info" style="font-size: 12px">Bersihkan Data</span>`
                            : `<span class="badge bg-light text-dark" style="font-size: 12px">Opname</span>`,
                    status_text: item.is_draft
                        ? `<span class="badge bg-warning" style="font-size: 12px">Draft</span>`
                        : statusTextMap[item.status] || "-",
                    action: `
                    ${downloadIcon}
                        <a href="/detailStockOpnameBahan/${item.stob_id}" class="me-2 btn-action-icon p-2 btn_view" data-id="${item.stob_id}"
                            data-bs-target="#view-opname" title="${item.is_draft ? "Lanjutkan Draft" : "Detail Stock Opname"}">
                            <i class="fe fe-eye"></i>
                        </a>
                    `,
                };
            });

            table.clear();
            table.rows.add(processedRows);
            table.draw(false);
            if (typeof feather !== "undefined") feather.replace();
        },
        error: function (err) {
            if (err && err.statusText === "abort") return;
            if (handlePermissionError(err)) return;
            console.error("Gagal load:", err);
        },
        complete: function () {
            setStockOpnameTableLoading(false);
            $("#tableStockOpname-wrap")
                .removeClass("dt-pending")
                .addClass("dt-ready");
        },
    });
}
