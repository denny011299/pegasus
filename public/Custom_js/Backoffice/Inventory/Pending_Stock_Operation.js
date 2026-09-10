var pendingStockTable = null;
var pendingStockTableReady = false;
var psoFilterState = { date_from: "", date_to: "" };
var psoFilterClearing = false;

$(function () {
    initPendingStockFilters();
    initPendingStockTable();
});

function reloadPendingStockTable(resetPaging) {
    if (pendingStockTable && pendingStockTable.ajax) {
        pendingStockTable.ajax.reload(null, !!resetPaging);
    }
}

function initPendingStockFilters() {
    if (!$("#pso_filter_date").length) return;

    var $date = $("#pso_filter_date");
    if (typeof $date.daterangepicker === "function" && typeof moment === "function") {
        $date.daterangepicker(
            {
                autoUpdateInput: false,
                alwaysShowCalendars: true,
                showDropdowns: true,
                locale: {
                    format: "DD-MM-YYYY",
                    separator: " — ",
                    applyLabel: "Terapkan",
                    cancelLabel: "Hapus",
                    fromLabel: "Dari",
                    toLabel: "Sampai",
                    customRangeLabel: "Kustom",
                    daysOfWeek: ["Mg", "Sn", "Sl", "Rb", "Km", "Jm", "Sb"],
                    monthNames: [
                        "Januari",
                        "Februari",
                        "Maret",
                        "April",
                        "Mei",
                        "Juni",
                        "Juli",
                        "Agustus",
                        "September",
                        "Oktober",
                        "November",
                        "Desember",
                    ],
                    firstDay: 1,
                },
                ranges: {
                    "Hari Ini": [moment(), moment()],
                    Kemarin: [moment().subtract(1, "days"), moment().subtract(1, "days")],
                    "7 Hari Terakhir": [moment().subtract(6, "days"), moment()],
                    "30 Hari Terakhir": [moment().subtract(29, "days"), moment()],
                    "Bulan Ini": [moment().startOf("month"), moment().endOf("month")],
                    "Bulan Lalu": [
                        moment().subtract(1, "month").startOf("month"),
                        moment().subtract(1, "month").endOf("month"),
                    ],
                },
            },
            function (startDate, endDate) {
                psoFilterState.date_from = startDate.format("YYYY-MM-DD");
                psoFilterState.date_to = endDate.format("YYYY-MM-DD");
            }
        );
        $date.on("apply.daterangepicker", function (_ev, picker) {
            psoFilterState.date_from = picker.startDate.format("YYYY-MM-DD");
            psoFilterState.date_to = picker.endDate.format("YYYY-MM-DD");
            $(this).val(
                picker.startDate.format("DD-MM-YYYY") +
                    " — " +
                    picker.endDate.format("DD-MM-YYYY")
            );
            reloadPendingStockTable(false);
        });
        $date.on("cancel.daterangepicker", function () {
            psoFilterState.date_from = "";
            psoFilterState.date_to = "";
            $(this).val("");
            reloadPendingStockTable(false);
        });
        $date.val("");
    }

    $(document).on("change", "#pso_filter_status, #pso_filter_source", function () {
        if (psoFilterClearing) return;
        reloadPendingStockTable(false);
    });

    $(document).on("click", ".btn-clear-pso-filter", function (e) {
        e.preventDefault();
        psoFilterClearing = true;
        psoFilterState.date_from = "";
        psoFilterState.date_to = "";
        $("#pso_filter_date").val("");
        $("#pso_filter_source").val("");
        $("#pso_filter_status").val("1");
        reloadPendingStockTable(true);
        setTimeout(function () {
            psoFilterClearing = false;
        }, 0);
    });
}

function setPendingStockTableLoading(isLoading) {
    var $wrap = $("#tablePendingStock-wrap");
    if (!$wrap.length) return;
    $wrap.toggleClass("is-loading", !!isLoading);
}

function showPendingSkeleton() {
    var $wrap = $("#tablePendingStock-wrap");
    if (!pendingStockTableReady || !pendingStockTable) {
        $wrap.removeClass("dt-ready is-loading").addClass("dt-pending");
    } else {
        $wrap.removeClass("dt-pending").addClass("dt-ready");
        setPendingStockTableLoading(true);
    }
}

function hidePendingSkeleton() {
    pendingStockTableReady = true;
    $("#tablePendingStock-wrap")
        .removeClass("dt-pending is-loading")
        .addClass("dt-ready");
    setPendingStockTableLoading(false);
}

function syncPendingStockSidebarBadge() {
    $.get("/getPendingStockOperationCount", function (res) {
        var count = res && res.status === 1 ? parseInt(res.count, 10) || 0 : 0;
        var $link = $('#sidebar a[href*="pendingStockOperation"]').first();
        if (!$link.length) return;
        var $badge = $link.find(".pso-pending-badge");
        if (count <= 0) {
            $badge.remove();
            return;
        }
        var label = count > 99 ? "99+" : String(count);
        if ($badge.length) {
            $badge.text(label);
        } else {
            $link.append(
                '<span class="pso-pending-badge" id="pso-pending-badge">' +
                    label +
                    "</span>"
            );
        }
    });
}

function bindPendingStockLoadingEvents($table) {
    $table
        .on("preXhr.dt", function () {
            showPendingSkeleton();
        })
        .on("xhr.dt", function (_e, _settings, json) {
            hidePendingSkeleton();
            syncPendingStockSidebarBadge();
            if (json && typeof json === "object" && json.draw == null && json.data == null) {
                // Bukan payload DataTables (mis. HTML/403 JSON lain)
                notifikasi(
                    "error",
                    "Gagal Memuat",
                    "Respons antrian tidak valid. Refresh halaman."
                );
            }
        })
        .on("error.dt", function (_e, _settings, techNote, message) {
            hidePendingSkeleton();
            console.error("Pending stock DT error:", techNote, message);
        });
}

function initPendingStockTable() {
    if ($.fn.DataTable.isDataTable("#tablePendingStock")) {
        pendingStockTable = $("#tablePendingStock").DataTable();
        return;
    }

    showPendingSkeleton();

    pendingStockTable = $("#tablePendingStock").DataTable({
        processing: true,
        serverSide: true,
        deferRender: true,
        bFilter: true,
        sDom: "fBtlpi",
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        ordering: false,
        autoWidth: false,
        scrollX: false,
        searchDelay: 400,
        language: {
            search: " ",
            sLengthMenu: "_MENU_",
            searchPlaceholder: "Cari kode / jenis…",
            info: "_START_ - _END_ of _TOTAL_ items",
            emptyTable: "Tidak ada antrian menunggu (cek filter Status → Applied untuk riwayat)",
            zeroRecords: "Tidak ada data untuk filter ini",
            processing:
                '<div><span class="spinner-border spinner-border-sm text-primary" role="status"></span><span>Memuat data...</span></div>',
            paginate: {
                next: ' <i class=" fa fa-angle-right"></i>',
                previous: '<i class="fa fa-angle-left"></i> ',
            },
        },
        ajax: {
            url: "/getPendingStockOperation",
            data: function (d) {
                d.warehouse_id =
                    typeof getActiveWarehouseId === "function"
                        ? getActiveWarehouseId()
                        : null;
                d.status = $("#pso_filter_status").length
                    ? $("#pso_filter_status").val()
                    : "1";
                d.source_type = $("#pso_filter_source").val() || "";
                d.date_from = psoFilterState.date_from || "";
                d.date_to = psoFilterState.date_to || "";
            },
            error: function (xhr) {
                hidePendingSkeleton();
                if (typeof handlePermissionError === "function" && handlePermissionError(xhr)) {
                    return;
                }
                notifikasi(
                    "error",
                    "Gagal Memuat",
                    "Tidak bisa memuat Antrian Mutasi Stok. Coba refresh."
                );
            },
        },
        columns: [
            { data: "created_at", defaultContent: "-" },
            { data: "source_type_label", defaultContent: "-" },
            { data: "source_code", defaultContent: "-" },
            { data: "warehouse_name", defaultContent: "-" },
            { data: "domain_label", defaultContent: "-" },
            { data: "opname", defaultContent: "-" },
            {
                data: "status_label",
                defaultContent: "-",
                className: "text-center",
                render: function (data, _type, row) {
                    var status = parseInt(row.status, 10);
                    var label = data || "-";
                    if (status === 1) {
                        return (
                            '<span class="badge bg-secondary" style="font-size:12px">' +
                            label +
                            "</span>"
                        );
                    }
                    if (status === 2) {
                        return (
                            '<span class="badge bg-success" style="font-size:12px">' +
                            label +
                            "</span>"
                        );
                    }
                    if (status === 3) {
                        return (
                            '<span class="badge bg-danger" style="font-size:12px">' +
                            label +
                            "</span>"
                        );
                    }
                    return label;
                },
            },
            {
                data: "pso_id",
                orderable: false,
                searchable: false,
                className: "text-center",
                render: function (id) {
                    return (
                        '<a href="javascript:void(0);" class="me-2 btn-action-icon p-2 btn-view-pending-stock" data-id="' +
                        id +
                        '" title="Detail"><i class="fe fe-eye"></i></a>'
                    );
                },
            },
        ],
        initComplete: function () {
            hidePendingSkeleton();
            // Sama Master Product: search tetap di dalam .dataTables_wrapper (sDom "f…")
            var $filter = $("#tablePendingStock_wrapper .dataTables_filter");
            if ($filter.length && !$filter.find("label .fa-search").length) {
                $filter.find("label").prepend('<i class="fa fa-search"></i> ');
            }
            if (pendingStockTable) {
                try {
                    pendingStockTable.columns.adjust();
                } catch (e) {}
            }
        },
        drawCallback: function () {
            setPendingStockTableLoading(false);
            if (typeof feather !== "undefined") feather.replace();
            if (pendingStockTable) {
                try {
                    pendingStockTable.columns.adjust();
                } catch (e) {}
            }
        },
    });

    bindPendingStockLoadingEvents($("#tablePendingStock"));
}

function resetPendingStockDetailLabels() {
    $("#lbl_pso_jenis, #lbl_pso_kode, #lbl_pso_gudang, #lbl_pso_domain, #lbl_pso_opname, #lbl_pso_dibuat, #lbl_pso_applied, #lbl_pso_error")
        .text("-");
    $("#lbl_pso_status").html("-");
    $("#pso_error_wrap, #pso_applied_wrap").hide();
}

function setPendingStockDetailLoading(isLoading) {
    var $el = $("#pending_stock_detail_loading");
    if (!$el.length) return;
    if (isLoading) {
        $el.css("display", "flex");
    } else {
        $el.hide();
    }
}

function renderPendingStockStatusBadge(status, label) {
    var s = parseInt(status, 10);
    var text = label || "-";
    if (s === 1) {
        return (
            '<span class="badge" style="background:#f1f5f9;color:#475569;border:1px solid #cbd5e1;font-size:12px;padding:6px 10px;">' +
            text +
            "</span>"
        );
    }
    if (s === 2) {
        return (
            '<span class="badge" style="background:#dcfce7;color:#166534;border:1px solid #bbf7d0;font-size:12px;padding:6px 10px;">' +
            text +
            "</span>"
        );
    }
    if (s === 3) {
        return (
            '<span class="badge" style="background:#fee2e2;color:#991b1b;border:1px solid #fecaca;font-size:12px;padding:6px 10px;">' +
            text +
            "</span>"
        );
    }
    return '<span class="fw-bold text-dark">' + text + "</span>";
}

$(document).on("click", ".btn-view-pending-stock", function () {
    var id = $(this).data("id");
    resetPendingStockDetailLabels();
    setPendingStockDetailLoading(true);
    $("#modalPendingStockDetail").modal("show");
    $.get("/getPendingStockOperationDetail", { id: id }, function (res) {
        setPendingStockDetailLoading(false);
        if (!res || res.status !== 1) {
            notifikasi(
                "error",
                "Gagal Memuat",
                (res && res.message) || "Data tidak ditemukan"
            );
            $("#modalPendingStockDetail").modal("hide");
            return;
        }
        var d = res.data || {};
        $("#lbl_pso_jenis").text(d.source_type_label || "-");
        $("#lbl_pso_kode").text(d.source_code || "-");
        $("#lbl_pso_gudang").text(d.warehouse_name || "-");
        $("#lbl_pso_domain").text(d.domain_label || "-");
        $("#lbl_pso_opname").text(
            ((d.opname_type || "") + " #" + (d.opname_id || "-")).trim()
        );
        $("#lbl_pso_status").html(
            renderPendingStockStatusBadge(d.pso_status, d.status_label)
        );
        $("#lbl_pso_dibuat").text(d.created_at || "-");
        if (d.applied_at) {
            $("#lbl_pso_applied").text(d.applied_at);
            $("#pso_applied_wrap").show();
        }
        if (d.error_message) {
            $("#lbl_pso_error").text(d.error_message);
            $("#pso_error_wrap").show();
        }
        if (typeof feather !== "undefined") feather.replace();
    }).fail(function (xhr) {
        setPendingStockDetailLoading(false);
        if (typeof handlePermissionError === "function" && handlePermissionError(xhr)) {
            $("#modalPendingStockDetail").modal("hide");
            return;
        }
        notifikasi("error", "Gagal Memuat", "Tidak bisa memuat detail antrian.");
        $("#modalPendingStockDetail").modal("hide");
    });
});
