    var mode = 1;
    var table = null;
    var dates = null;
    var activeId = 0;
    var warehouseWarnShown = false;

    $(document).ready(function () {
        inisialisasi();
    });

    function stockSuppliesAjax(data, callback) {
        var warehouseId = typeof getActiveWarehouseId === "function" ? getActiveWarehouseId() : null;
        if (!warehouseId) {
            if (!warehouseWarnShown) {
                warehouseWarnShown = true;
                if (typeof toastr !== "undefined") {
                    toastr.warning("Pilih gudang aktif di header terlebih dahulu");
                } else if (typeof notifikasi === "function") {
                    notifikasi("warning", "Gudang", "Pilih gudang aktif di header terlebih dahulu");
                }
            }
            callback({
                draw: data.draw,
                recordsTotal: 0,
                recordsFiltered: 0,
                data: [],
            });
            return;
        }
        warehouseWarnShown = false;

        $.ajax({
            url: "/getStockSupplies",
            type: "GET",
            data: $.extend({}, data, { warehouse_id: warehouseId }),
            success: function (json) {
                callback(json);
            },
            error: function (err) {
                console.error("Gagal load:", err);
                callback({
                    draw: data.draw,
                    recordsTotal: 0,
                    recordsFiltered: 0,
                    data: [],
                });
            },
        });
    }

    function inisialisasi() {
        table = $("#tableStock").DataTable({
            processing: true,
            serverSide: true,
            deferRender: true,
            autoWidth: false,
            bFilter: true,
            sDom: "fBtlpi",
            lengthMenu: [10, 25, 50, 100],
            pageLength: 10,
            ordering: true,
            scrollX: false,
            order: [[0, "asc"]],
            searchDelay: 400,
            language: {
                search: " ",
                sLengthMenu: "_MENU_",
                searchPlaceholder: "Cari Bahan Mentah",
                info: "_START_ - _END_ of _TOTAL_ items",
                processing: "Memuat data...",
                emptyTable: "Tidak ada data stok untuk gudang ini",
                zeroRecords: "Bahan mentah tidak ditemukan",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> ',
                },
            },
            ajax: function (data, callback) {
                stockSuppliesAjax(data, callback);
            },
            columns: [
                { data: "supplies_name", width: "75%" },
                {
                    data: "supplies_variant_stock_text",
                    class: "fw-bold",
                    width: "25%",
                    orderable: false,
                    searchable: false,
                },
            ],
            initComplete: function () {
                var $filter = $(".dataTables_filter").last();
                $filter.appendTo("#tableSearch");
                $filter.appendTo(".search-input");
                if (!$filter.find("label .fa-search").length) {
                    $filter.find("label").prepend('<i class="fa fa-search"></i> ');
                }
            },
            drawCallback: function () {
                if (table) table.columns.adjust();
            },
        });
    }

    $(document).on("click", "#tableStock tbody tr", function () {
        var data = table.row(this).data();
        if (!data) return;
        activeId = data.supplies_id;
        getLog(data.supplies_id);
    });

    function getLog(id) {
        $.ajax({
            url: "/getLog",
            method: "get",
            data: {
                log_type: 2,
                log_item_id: id,
                date: dates,
            },
            success: function (e) {
                viewHistory(e);
            },
            error: function (e) {
                console.error("Gagal load:", e);
            },
        });
    }

    function viewHistory(data) {
        $("#tableLog tr.row-log").remove();
        $("#tableLog tr.empty-log").remove();
        if (data.length > 0) {
            $(".empty-log").remove();
            data.forEach((e) => {
                $("#tableLog tbody").append(`
                    <tr class="row-log" data-id="${e.log_id}">
                        <td style="width: 15%">${moment(e.log_date).format("D MMM YYYY, HH:mm")}</td>
                        <td style="width: 15%">${e.staff_name ?? "-"}</td>
                        <td style="width: 15%">${e.log_kode}</td>
                        <td style="width: 25%">${e.log_notes}</td>
                    <td style="width: 15%" class="text-success text-center">${e.log_category == 1 ? e.log_jumlah : "-"} ${e.log_category == 1 ? e.unit_name : ""}</td>
                    <td style="width: 15%" class="text-danger text-center">${e.log_category == 2 ? e.log_jumlah : "-"} ${e.log_category == 2 ? e.unit_name : ""}</td>
                    </tr>
                `);
            });
        } else {
            $("#tableLog tbody").append(`
                <tr class="empty-log">
                    <td colspan="4" class="text-center text-muted py-4">
                        Bahan ini belum ada riwayat perubahan stok
                    </td>
                </tr>
            `);
        }

        $("#add_stock_supplies .modal-title").html("Lihat Histori Bahan Mentah");
        $("#add_stock_supplies").modal("show");
    }

    $(document).on("change", "#start_date", function () {
        dates = [];
        dates.push($("#start_date").val());
        dates.push($("#end_date").val());
        getLog(activeId);
    });
    $(document).on("change", "#end_date", function () {
        dates = [];
        dates.push($("#start_date").val());
        dates.push($("#end_date").val());
        getLog(activeId);
    });

    $(document).on("click", ".btn-clear", function () {
        dates = null;
        $("#start_date").val("");
        $("#end_date").val("");
        getLog(activeId);
    });
