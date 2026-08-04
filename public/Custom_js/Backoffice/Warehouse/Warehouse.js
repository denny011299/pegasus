var mode = 1;
var table;

$(document).ready(function () {
    inisialisasi();
    autocompleteWarehouseType("#warehouse_type_id", "#add_warehouse");
});

$(document).on("click", ".btnAdd", function () {
    mode = 1;
    $("#add_warehouse .modal-title").html("Tambah Gudang");
    $("#add_warehouse input:not([type=checkbox]), #add_warehouse textarea").val(
        "",
    );
    $("#warehouse_type_id").val(null).trigger("change");
    setWarehouseSidebarMenus(null);
    $(".is-invalid").removeClass("is-invalid");
    $("#add_warehouse .btn-save").html(
        mode == 1 ? "Tambah Gudang" : "Update Gudang",
    );
    $("#add_warehouse").removeAttr("data-id").modal("show");
});

function getWarehouseSidebarMenus() {
    var menus = [];
    $(".warehouse-sidebar-menu:checked").each(function () {
        menus.push($(this).val());
    });
    return menus.length ? menus : null;
}

function setWarehouseSidebarMenus(menus) {
    $(".warehouse-sidebar-menu").prop("checked", false);
    if (!menus || !Array.isArray(menus) || !menus.length) {
        return;
    }
    var set = {};
    menus.forEach(function (m) {
        if (m) set[String(m).toLowerCase()] = true;
    });
    $(".warehouse-sidebar-menu").each(function () {
        var val = String($(this).val() || "").toLowerCase();
        if (set[val]) $(this).prop("checked", true);
    });
    updateAllModuleChecks();
}

function updateAllModuleChecks() {
    $(".module-check-all").each(function () {
        var $container = $(this).closest(".menu-card");
        var total = $container.find(".warehouse-sidebar-menu").length;
        var checked = $container.find(".warehouse-sidebar-menu:checked").length;
        $(this).prop("checked", total > 0 && total === checked);
        $(this).prop("indeterminate", checked > 0 && checked < total);
    });
}

$(document).on("change", ".module-check-all", function () {
    var isChecked = $(this).is(":checked");
    $(this)
        .closest(".menu-card")
        .find(".warehouse-sidebar-menu")
        .prop("checked", isChecked);
});

$(document).on("change", ".warehouse-sidebar-menu", function () {
    var $container = $(this).closest(".menu-card");
    var total = $container.find(".warehouse-sidebar-menu").length;
    var checked = $container.find(".warehouse-sidebar-menu:checked").length;
    var $headerCheck = $container.find(".module-check-all");

    $headerCheck.prop("checked", total > 0 && total === checked);
    $headerCheck.prop("indeterminate", checked > 0 && checked < total);
});

$(document).on("click", "#btn-sidebar-menus-all", function () {
    $(".warehouse-sidebar-menu").prop("checked", true);
    updateAllModuleChecks();
});

$(document).on("click", "#btn-sidebar-menus-none", function () {
    $(".warehouse-sidebar-menu").prop("checked", false);
    updateAllModuleChecks();
});

function inisialisasi() {
    table = $("#tableWarehouse").DataTable({
        ajax: {
            url: "/getWarehouse",
            dataSrc: function (json) {
                if (!Array.isArray(json)) {
                    json = json.original || json.data || [];
                }
                return json.map(function (row) {
                    row.warehouse_date = row.created_at
                        ? moment(row.created_at).format("D MMM YYYY")
                        : "-";
                    row.warehouse_address = row.warehouse_address || "-";
                    row.created_by_name = row.created_by_name || "-";
                    return row;
                });
            },
        },
        bFilter: true,
        sDom: "fBtlpi",
        lengthMenu: [10, 25, 50, 100],
        ordering: true,
        autoWidth: false,
        scrollX: false,
        language: {
            search: " ",
            sLengthMenu: "_MENU_",
            searchPlaceholder: "Cari Gudang",
            info: "_START_ - _END_ of _TOTAL_ items",
            emptyTable: "Belum ada data gudang",
            zeroRecords: "Data tidak ditemukan",
            loadingRecords: "Sedang memuat data...",
            paginate: {
                next: ' <i class=" fa fa-angle-right"></i>',
                previous: '<i class="fa fa-angle-left"></i> ',
            },
        },
        columns: [
            {
                data: "warehouse_name",
                className: "text-start align-middle",
                width: "22%",
                render: function (data, type, row) {
                    if (type !== "display") return data;
                    var isUtama =
                        row.type && parseInt(row.type.is_main_warehouse) === 1;
                    var iconClass = isUtama
                        ? "fas fa-building"
                        : "fas fa-store";
                    var iconBg = isUtama ? "#eff6ff" : "#f8fafc";
                    var iconColor = isUtama ? "#2563eb" : "#64748b";
                    var iconBorder = isUtama ? "#bfdbfe" : "#e2e8f0";
                    var badgeBg = isUtama ? "#dbeafe" : "#e0f2fe";
                    var badgeColor = isUtama ? "#1e40af" : "#0369a1";
                    var badgeBorder = isUtama ? "#bfdbfe" : "#bae6fd";
                    return (
                        '<div style="display:flex;align-items:center;justify-content:flex-start;gap:12px;">' +
                        '<div style="width:36px;height:36px;border-radius:10px;background:' +
                        iconBg +
                        ";border:1px solid " +
                        iconBorder +
                        ";display:flex;align-items:center;justify-content:center;color:" +
                        iconColor +
                        ';flex-shrink:0;"><i class="' +
                        iconClass +
                        '"></i></div>' +
                        '<span class="badge" style="background-color:' +
                        badgeBg +
                        ";color:" +
                        badgeColor +
                        ";border:1px solid " +
                        badgeBorder +
                        ';padding:6px 14px;border-radius:20px;font-weight:600;font-size:12px;">' +
                        data +
                        "</span></div>"
                    );
                },
            },
            {
                data: "type.warehouse_type_name",
                defaultContent: "-",
                className: "text-center align-middle",
                width: "14%",
                render: function (data, type, row) {
                    if (type !== "display") return data;
                    if (!data || data === "-")
                        return '<span class="text-muted">-</span>';
                    var isUtama =
                        row.type && parseInt(row.type.is_main_warehouse) === 1;
                    var bg = isUtama ? "#dbeafe" : "#e0f2fe";
                    var color = isUtama ? "#1e40af" : "#0369a1";
                    var border = isUtama ? "#bfdbfe" : "#bae6fd";
                    return (
                        '<span class="badge" style="background-color:' +
                        bg +
                        ";color:" +
                        color +
                        ";border:1px solid " +
                        border +
                        ';padding:6px 14px;border-radius:20px;font-weight:600;font-size:12px;">' +
                        data +
                        "</span>"
                    );
                },
            },
            {
                data: "warehouse_address",
                defaultContent: "-",
                className: "text-start align-middle",
                width: "20%",
                render: function (data, type) {
                    if (type !== "display") return data;
                    return (
                        '<span style="color:#475569;display:inline-block;text-align:left;">' +
                        (data || "-") +
                        "</span>"
                    );
                },
            },
            {
                data: "warehouse_date",
                className: "text-start align-middle",
                width: "12%",
                render: function (data, type) {
                    if (type !== "display") return data;
                    return (
                        '<div style="color:#64748b;font-size:13px;font-weight:500;text-align:left;">' +
                        '<i class="far fa-calendar-alt me-1 text-muted"></i> ' +
                        data +
                        "</div>"
                    );
                },
            },
            {
                data: "created_by_name",
                defaultContent: "-",
                className: "text-start align-middle",
                width: "14%",
                render: function (data, type) {
                    if (type !== "display") return data;
                    if (!data || data === "-")
                        return '<span class="text-muted">-</span>';
                    return (
                        '<div style="display:flex;align-items:center;justify-content:flex-start;gap:8px;">' +
                        '<div style="width:24px;height:24px;border-radius:50%;background:#f1f5f9;display:flex;align-items:center;justify-content:center;color:#64748b;font-size:10px;">' +
                        '<i class="fas fa-user"></i></div>' +
                        '<span style="color:#475569;font-weight:500;">' +
                        data +
                        "</span></div>"
                    );
                },
            },
            {
                data: "status",
                className: "text-center align-middle",
                width: "10%",
                render: function (data, type) {
                    if (type !== "display") return data;
                    if (data == 1) {
                        return (
                            '<span class="badge" style="background-color:#ecfdf5;color:#047857;border:1px solid #a7f3d0;padding:6px 14px;border-radius:20px;font-weight:600;font-size:12px;">' +
                            '<i class="fas fa-circle me-1" style="font-size:8px;"></i> Aktif</span>'
                        );
                    }
                    return (
                        '<span class="badge" style="background-color:#fef2f2;color:#b91c1c;border:1px solid #fecaca;padding:6px 14px;border-radius:20px;font-weight:600;font-size:12px;">' +
                        '<i class="fas fa-circle me-1" style="font-size:8px;"></i> Non Aktif</span>'
                    );
                },
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                className: "text-center align-middle",
                width: "8%",
                render: function (data, type, row) {
                    if (type !== "display") return "";
                    return buildActionButtons(row);
                },
            },
        ],
        initComplete: function () {
            $(".dataTables_filter").appendTo("#tableSearch");
            $(".dataTables_filter").appendTo(".search-input");
            $(".dataTables_filter input[type='search']").attr(
                "placeholder",
                "Cari gudang...",
            );
            table.columns.adjust();
            $("#warehouse-table-wrap")
                .removeClass("dt-pending")
                .addClass("dt-ready");
        },
        drawCallback: function () {
            if (typeof feather !== "undefined") feather.replace();
            $('[data-bs-toggle="tooltip"]').tooltip();
            if (table) table.columns.adjust();
        },
    });
}

function buildActionButtons(row) {
    var canEdit =
        typeof hasAccessAction === "function" &&
        hasAccessAction("Gudang", "edit");
    var canDelete =
        typeof hasAccessAction === "function" &&
        hasAccessAction("Gudang", "delete");
    var html = "";

    if (canEdit) {
        html +=
            '<a class="btn-action-icon btn_edit" style="background:#eff6ff;border:1px solid #bfdbfe;color:#2563eb;" data-id="' +
            row.id +
            '" data-bs-toggle="tooltip" title="Edit Gudang"><i class="far fa-edit" style="font-size:14px;"></i></a>';

        if (row.status == 1) {
            html +=
                '<a class="btn-action-icon btn_status" style="background:#fffbeb;border:1px solid #fde68a;color:#d97706;" data-id="' +
                row.id +
                '" data-status="2" data-bs-toggle="tooltip" title="Non-aktifkan Gudang"><i class="far fa-circle-dot" style="font-size:14px;"></i></a>';
        } else {
            html +=
                '<a class="btn-action-icon btn_status" style="background:#ecfdf5;border:1px solid #a7f3d0;color:#059669;" data-id="' +
                row.id +
                '" data-status="1" data-bs-toggle="tooltip" title="Aktifkan Gudang"><i class="far fa-check-circle" style="font-size:14px;"></i></a>';
        }
    }

    if (canDelete) {
        html +=
            '<a class="btn-action-icon btn_delete" style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;" data-id="' +
            row.id +
            '" href="javascript:void(0);" data-bs-toggle="tooltip" title="Hapus Gudang"><i class="far fa-trash-alt" style="font-size:14px;"></i></a>';
    }

    if (!html) return '<span class="text-muted small">—</span>';
    return (
        '<div style="display:flex;gap:6px;justify-content:center;">' +
        html +
        "</div>"
    );
}

function refreshWarehouse() {
    if (!table) return;
    table.ajax.reload(null, false);
}

$(document).on("click", "#add_warehouse .btn-save", function () {
    var $modal = $("#add_warehouse");
    var $btn = $(this);
    LoadingButton(this);
    $modal.find(".is-invalid").removeClass("is-invalid");
    var url = "/insertWarehouse";
    var valid = 1;

    $modal.find(".fill").each(function () {
        var val = $(this).val();
        if (val == null || val === "" || val === "null") {
            valid = -1;
            $(this).addClass("is-invalid");
            if ($(this).hasClass("select2-hidden-accessible")) {
                $(this)
                    .next(".select2-container")
                    .find(".select2-selection")
                    .addClass("is-invalid");
            }
        }
    });

    if (valid == -1) {
        notifikasi(
            "error",
            "Gagal Insert",
            "Silahkan cek kembali inputan anda",
        );
        ResetLoadingButton(
            $btn,
            mode == 1 ? "Tambah Gudang" : "Update Gudang",
        );
        return false;
    }

    param = {
        warehouse_name: $modal.find("#warehouse_name").val(),
        warehouse_type_id: $modal.find("#warehouse_type_id").val(),
        warehouse_address: $modal.find("#warehouse_address").val(),
        sidebar_menus: JSON.stringify(getWarehouseSidebarMenus()),
        _token: token,
    };

    if (mode == 2) {
        url = "/updateWarehouse";
        param.id = $modal.attr("data-id");
    }

    LoadingButton($btn);
    $.ajax({
        url: url,
        data: param,
        method: "post",
        headers: {
            "X-CSRF-TOKEN": token,
        },
        success: function (e) {
            ResetLoadingButton(
                $btn,
                mode == 1 ? "Tambah Gudang" : "Update Gudang",
            );
            if (e == -2) {
                notifikasi("error", "Gagal", "Nama gudang sudah terdaftar!");
                $modal.find("#warehouse_name").addClass("is-invalid");
                return;
            }
            afterInsert();
        },
        error: function (e) {
            ResetLoadingButton(
                $btn,
                mode == 1 ? "Tambah Gudang" : "Update Gudang",
            );
            notifikasi("error", "Gagal", "Terjadi kesalahan saat menyimpan");
            console.log(e);
        },
    });
});

function afterInsert() {
    $(".modal").modal("hide");
    if (mode == 1)
        notifikasi("success", "Berhasil Insert", "Berhasil Tambah Gudang");
    else if (mode == 2)
        notifikasi("success", "Berhasil Update", "Berhasil Update Gudang");

    // Sidebar tergantung sidebar_menus gudang aktif → reload supaya menu ikut berubah
    var editedId = $("#add_warehouse").attr("data-id");
    var activeId =
        typeof getActiveWarehouseId === "function"
            ? getActiveWarehouseId()
            : null;
    if (
        mode == 2 &&
        editedId &&
        activeId &&
        String(editedId) === String(activeId)
    ) {
        setTimeout(function () {
            window.location.reload();
        }, 400);
        return;
    }

    refreshWarehouse();
}

$(document).on(
    "keypress",
    "#add_warehouse input, #add_warehouse textarea",
    function (e) {
        if (e.which == 13) {
            e.preventDefault();
            $("#add_warehouse .btn-save").trigger("click");
        }
    },
);

$(document).on("click", ".btn_edit", function () {
    var data = table.row($(this).parents("tr")).data();
    mode = 2;
    $("#add_warehouse .modal-title").html("Update Gudang");
    $("#add_warehouse input:not([type=checkbox]), #add_warehouse textarea").val(
        "",
    );
    $("#warehouse_type_id").val(null).trigger("change");
    $(".is-invalid").removeClass("is-invalid");
    $("#add_warehouse .btn-save").html(
        mode == 1 ? "Tambah Gudang" : "Update Gudang",
    );

    $("#warehouse_name").val(data.warehouse_name);
    $("#warehouse_address").val(
        data.warehouse_address && data.warehouse_address != "-"
            ? data.warehouse_address
            : "",
    );
    if (data.warehouse_type_id) {
        var typeName =
            data.type && data.type.warehouse_type_name
                ? data.type.warehouse_type_name
                : "-";
        if (
            data.type &&
            (data.type.is_main_warehouse == 1 ||
                data.type.is_main_warehouse === true)
        ) {
            typeName += " (Gudang Utama)";
        }
        var opt = new Option(typeName, data.warehouse_type_id, true, true);
        $("#warehouse_type_id").append(opt).trigger("change");
    }

    setWarehouseSidebarMenus(data.sidebar_menus);

    $("#add_warehouse").attr("data-id", data.id).modal("show");
});

$(document).on("click", ".btn_delete", function () {
    var data = table.row($(this).parents("tr")).data();
    showModalDelete(
        "Apakah yakin ingin menghapus gudang ini?",
        "btn-delete-warehouse",
    );
    $("#btn-delete-warehouse").attr("data-id", data.id);
});

$(document).on("click", "#btn-delete-warehouse", function () {
    var id = $(this).attr("data-id");
    $.ajax({
        url: "/deleteWarehouse",
        data: {
            id: id,
            _token: token,
        },
        method: "post",
        success: function (e) {
            // Step 2: masih ada stok (ps_stock > 0)
            if (e && e.status == -4) {
                $(".modal").modal("hide");
                var msg =
                    e.message ||
                    "Apakah anda yakin ingin menghapus gudang ini? Masih terdapat stock yang terdaftar di gudang ini";
                $("#modalKonfirmasi .modal-title").text(
                    "Konfirmasi Hapus Lanjutan",
                );
                $("#modalKonfirmasi .btn-konfirmasi")
                    .removeClass("btn-success pg-btn-confirm")
                    .addClass("btn-danger pg-btn-confirm--danger");
                showModalKonfirmasi(msg, "btn-force-delete-warehouse");
                $("#btn-force-delete-warehouse").attr("data-id", id);
                return;
            }

            $(".modal").modal("hide");
            refreshWarehouse();
            notifikasi("success", "Berhasil Delete", "Berhasil hapus gudang");
        },
        error: function (e) {
            notifikasi("error", "Gagal", "Terjadi kesalahan saat menghapus");
            console.log(e);
        },
    });
});

$(document).on("click", "#btn-force-delete-warehouse", function () {
    $.ajax({
        url: "/deleteWarehouse",
        data: {
            id: $(this).attr("data-id"),
            force: 1,
            _token: token,
        },
        method: "post",
        success: function () {
            $(".modal").modal("hide");
            refreshWarehouse();
            notifikasi("success", "Berhasil Delete", "Berhasil hapus gudang");
        },
        error: function (e) {
            notifikasi("error", "Gagal", "Terjadi kesalahan saat menghapus");
            console.log(e);
        },
    });
});

$(document).on("click", ".btn_status", function () {
    var id = $(this).attr("data-id");
    var status = $(this).attr("data-status");
    var text =
        status == 1
            ? "Apakah yakin ingin mengaktifkan gudang ini?"
            : "Apakah yakin ingin menon-aktifkan gudang ini? Gudang yang dinon-aktifkan tidak bisa digunakan dalam transaksi.";

    $("#modalKonfirmasi .modal-title").text(
        status == 1 ? "Konfirmasi Aktifkan" : "Konfirmasi Non Aktif",
    );

    if (status == 1) {
        $("#modalKonfirmasi .btn-konfirmasi")
            .removeClass("btn-danger pg-btn-confirm--danger")
            .addClass("btn-success pg-btn-confirm");
    } else {
        $("#modalKonfirmasi .btn-konfirmasi")
            .removeClass("btn-success pg-btn-confirm")
            .addClass("btn-danger pg-btn-confirm--danger");
    }

    showModalKonfirmasi(text, "btn-update-warehouse-status");
    $("#btn-update-warehouse-status")
        .attr("data-id", id)
        .attr("data-status", status);
});

$(document).on("click", "#btn-update-warehouse-status", function () {
    $.ajax({
        url: "/updateWarehouseStatus",
        method: "post",
        data: {
            id: $(this).attr("data-id"),
            status: $(this).attr("data-status"),
            _token: token,
        },
        success: function (e) {
            if (e == -1 || e === "-1") {
                notifikasi("error", "Gagal", "Gagal mengubah status gudang");
                return;
            }
            $(".modal").modal("hide");
            refreshWarehouse();
            notifikasi(
                "success",
                "Berhasil",
                "Berhasil mengubah status gudang",
            );
        },
        error: function (e) {
            notifikasi("error", "Gagal", "Gagal mengubah status gudang");
            console.log(e);
        },
    });
});
