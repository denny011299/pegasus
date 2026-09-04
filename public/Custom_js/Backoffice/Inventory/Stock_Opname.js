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
            { data: "sto_date" },
            { data: "warehouse_name", defaultContent: "-" },
            { data: "staff_name", defaultContent: "-" },
            { data: "sto_code" },
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
        url: "/getStockOpname",
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
                        <a href="/generateStockOpname/${item.sto_id}" class="me-2 btn-action-icon p-2 btn_download" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" title="Download Stock Opname">
                            <i class="fe fe-file-text"></i>
                        </a>`;

                return {
                    ...item,
                    sto_date: item.sto_date
                        ? moment(item.sto_date).format("D MMM YYYY")
                        : "-",
                    warehouse_name: item.warehouse_name || "-",
                    staff_name: item.staff_name || "-",
                    sto_code: item.sto_code || "-",
                    created_by_name: item.created_by_name || "-",
                    acc_by_name: item.acc_by_name || "-",
                    // Fitur "Clean Up Data" (2026-09-04): baris sto_type=2 cuma sampai di sini
                    // kalau requester memang berizin "Stok Opname - Bersihkan Data"|view -- lihat
                    // StockOpname::getStockOpname().
                    jenis_text:
                        item.sto_type == 2
                            ? `<span class="badge bg-info" style="font-size: 12px">Bersihkan Data</span>`
                            : `<span class="badge bg-light text-dark" style="font-size: 12px">Opname</span>`,
                    status_text: item.is_draft
                        ? `<span class="badge bg-warning" style="font-size: 12px">Draft</span>`
                        : statusTextMap[item.status] || "-",
                    action: `
                        ${downloadIcon}
                        <a href="/detailStockOpname/${item.sto_id}" class="me-2 btn-action-icon p-2 btn_view" data-id="${item.sto_id}"
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

function loadCategory() {
    $.ajax({
        url: "/admin/getCategory",
        method: "GET",
        success: function (data) {
            data = JSON.parse(data);
            if (data) {
                $("#kategori").empty();
                $("#kategori").append(
                    $("<option>", {
                        value: -1,
                        text: "Semua Kategori",
                    }),
                );
                data.forEach((element) => {
                    $("#kategori").append(
                        $("<option>", {
                            value: element.category_id,
                            text: element.category_name,
                        }),
                    );
                });
            }
        },
        error: function (e) {
            if (handlePermissionError(e)) return;
            console.log(e);
        },
    });
}
