var mode = 1;
var table;

$(document).ready(function () {
    inisialisasi();
});

$(document).on("click", ".btnAdd", function () {
    mode = 1;
    $("#add_warehouse_type .modal-title").html("Tambah Tipe Gudang");
    $("#warehouse_type_name").val("");
    $("#is_main_warehouse").prop("checked", false);
    $(".is-invalid").removeClass("is-invalid");
    $(".btn-save").html(mode == 1 ? "Tambah Tipe Gudang" : "Update Tipe Gudang");
    $("#add_warehouse_type").removeAttr("data-id").modal("show");
});

function inisialisasi() {
    table = $("#tableWarehouseType").DataTable({
        ajax: {
            url: "/getWarehouseType",
            dataSrc: function (json) {
                if (!Array.isArray(json)) {
                    json = json.original || json.data || [];
                }
                return json.map(function (row) {
                    row.warehouse_type_date = row.created_at
                        ? moment(row.created_at).format("D MMM YYYY")
                        : "-";
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
            searchPlaceholder: "Cari Tipe Gudang",
            info: "_START_ - _END_ of _TOTAL_ items",
            emptyTable: "Belum ada data tipe gudang",
            zeroRecords: "Data tidak ditemukan",
            loadingRecords: "Sedang memuat data...",
            paginate: {
                next: ' <i class=" fa fa-angle-right"></i>',
                previous: '<i class="fa fa-angle-left"></i> ',
            },
        },
        columns: [
            {
                data: "warehouse_type_name",
                className: "text-start align-middle",
                width: "40%",
                render: function (data, type, row) {
                    if (type !== "display") return data;
                    if (!data) return '<span class="text-muted">-</span>';

                    var isUtama = parseInt(row.is_main_warehouse) === 1;
                    var iconClass = isUtama ? "fas fa-building" : "fas fa-store";
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
                        ';padding:6px 14px;border-radius:20px;font-weight:600;font-size:12px;white-space:nowrap;text-align:left;">' +
                        data +
                        "</span></div>"
                    );
                },
            },
            {
                data: "warehouse_type_date",
                className: "text-start align-middle",
                width: "20%",
                render: function (data, type) {
                    if (type !== "display") return data;
                    return (
                        '<div style="color:#64748b;font-size:13px;font-weight:500;text-align:left;">' +
                        '<i class="far fa-calendar-alt me-1 text-muted"></i> ' +
                        (data || "-") +
                        "</div>"
                    );
                },
            },
            {
                data: "created_by_name",
                defaultContent: "-",
                className: "text-start align-middle",
                width: "25%",
                render: function (data, type) {
                    if (type !== "display") return data;
                    if (!data || data === "-") return '<span class="text-muted">-</span>';
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
                data: null,
                orderable: false,
                searchable: false,
                className: "text-center align-middle",
                width: "15%",
                render: function (data, type, row) {
                    if (type !== "display") return "";
                    return buildActionButtons(row);
                },
            },
        ],
        initComplete: function () {
            $(".dataTables_filter").appendTo("#tableSearch");
            $(".dataTables_filter").appendTo(".search-input");
            $(".dataTables_filter input[type='search']").attr("placeholder", "Cari tipe gudang...");
            table.columns.adjust();
            $("#warehouse-type-table-wrap").removeClass("dt-pending").addClass("dt-ready");
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
        typeof hasAccessAction === "function" && hasAccessAction("Tipe Gudang", "edit");
    var canDelete =
        typeof hasAccessAction === "function" && hasAccessAction("Tipe Gudang", "delete");
    var html = "";

    if (canEdit) {
        html +=
            '<a class="btn-action-icon btn_edit" style="background:#eff6ff;border:1px solid #bfdbfe;color:#2563eb;" data-id="' +
            row.id +
            '" data-bs-toggle="tooltip" title="Edit Tipe Gudang"><i class="far fa-edit" style="font-size:14px;"></i></a>';
    }

    if (canDelete) {
        html +=
            '<a class="btn-action-icon btn_delete" style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;" data-id="' +
            row.id +
            '" href="javascript:void(0);" data-bs-toggle="tooltip" title="Hapus Tipe Gudang"><i class="far fa-trash-alt" style="font-size:14px;"></i></a>';
    }

    if (!html) return '<span class="text-muted small">—</span>';
    return '<div style="display:flex;gap:6px;justify-content:center;">' + html + "</div>";
}

function refreshWarehouseType() {
    if (!table) return;
    table.ajax.reload(null, false);
}

$(document).on("click", ".btn-save", function () {
    LoadingButton(this);
    $(".is-invalid").removeClass("is-invalid");
    var url = "/insertWarehouseType";
    var valid = 1;

    $("#add_warehouse_type .fill").each(function () {
        if ($(this).val() == null || $(this).val() == "null" || $(this).val() == "") {
            valid = -1;
            $(this).addClass("is-invalid");
        }
    });

    if (valid == -1) {
        notifikasi("error", "Gagal Insert", "Silahkan cek kembali inputan anda");
        ResetLoadingButton(".btn-save", mode == 1 ? "Tambah Tipe Gudang" : "Update Tipe Gudang");
        return false;
    }

    param = {
        warehouse_type_name: $("#warehouse_type_name").val(),
        is_main_warehouse: $("#is_main_warehouse").is(":checked") ? 1 : 0,
        _token: token,
    };

    if (mode == 2) {
        url = "/updateWarehouseType";
        param.id = $("#add_warehouse_type").attr("data-id");
    }

    LoadingButton($(this));
    $.ajax({
        url: url,
        data: param,
        method: "post",
        headers: {
            "X-CSRF-TOKEN": token,
        },
        success: function (e) {
            ResetLoadingButton(".btn-save", mode == 1 ? "Tambah Tipe Gudang" : "Update Tipe Gudang");
            if (e == -2) {
                notifikasi("error", "Gagal", "Nama tipe gudang sudah terdaftar!");
                $("#warehouse_type_name").addClass("is-invalid");
                return;
            }
            if (e == -4) {
                notifikasi(
                    "error",
                    "Gagal",
                    "sudah ada gudang yang menjadi gudang utama.",
                );
                $("#is_main_warehouse").prop("checked", false);
                return;
            }
            afterInsert();
        },
        error: function (e) {
            ResetLoadingButton(".btn-save", mode == 1 ? "Tambah Tipe Gudang" : "Update Tipe Gudang");
            notifikasi("error", "Gagal", "Terjadi kesalahan saat menyimpan");
            console.log(e);
        },
    });
});

function afterInsert() {
    $(".modal").modal("hide");
    if (mode == 1) notifikasi("success", "Berhasil Insert", "Berhasil Tambah Tipe Gudang");
    else if (mode == 2) notifikasi("success", "Berhasil Update", "Berhasil Update Tipe Gudang");
    refreshWarehouseType();
}

$(document).on("keypress", "#add_warehouse_type input", function (e) {
    if (e.which == 13) {
        e.preventDefault();
        $(".btn-save").trigger("click");
    }
});

$(document).on("click", ".btn_edit", function () {
    var data = table.row($(this).parents("tr")).data();
    mode = 2;
    $("#add_warehouse_type .modal-title").html("Update Tipe Gudang");
    $("#warehouse_type_name").val("");
    $("#is_main_warehouse").prop("checked", false);
    $(".is-invalid").removeClass("is-invalid");
    $(".btn-save").html(mode == 1 ? "Tambah Tipe Gudang" : "Update Tipe Gudang");
    $("#warehouse_type_name").val(data.warehouse_type_name);
    $("#is_main_warehouse").prop("checked", data.is_main_warehouse == 1 || data.is_main_warehouse === true);
    $("#add_warehouse_type").attr("data-id", data.id).modal("show");
});

$(document).on("click", ".btn_delete", function () {
    var data = table.row($(this).parents("tr")).data();
    showModalDelete("Apakah yakin ingin menghapus tipe gudang ini?", "btn-delete-warehouse-type");
    $("#btn-delete-warehouse-type").attr("data-id", data.id);
});

$(document).on("click", "#btn-delete-warehouse-type", function () {
    var id = $(this).attr("data-id");
    $.ajax({
        url: "/deleteWarehouseType",
        data: {
            id: id,
            _token: token,
        },
        method: "post",
        success: function (e) {
            if (e && e.status == -3) {
                $(".modal").modal("hide");
                var msg =
                    (e.message || "Masih ada gudang yang memakai tipe ini") +
                    ". Apakah benar-benar mau menghapus tipe gudang ini?";
                $("#modalKonfirmasi .modal-title").text("Konfirmasi Hapus Lanjutan");
                $("#modalKonfirmasi .btn-konfirmasi").removeClass("btn-success pg-btn-confirm").addClass("btn-danger pg-btn-confirm--danger");
                showModalKonfirmasi(msg, "btn-force-delete-warehouse-type");
                $("#btn-force-delete-warehouse-type").attr("data-id", id);
                return;
            }
            $(".modal").modal("hide");
            refreshWarehouseType();
            notifikasi("success", "Berhasil Delete", "Berhasil hapus tipe gudang");
        },
        error: function (e) {
            notifikasi("error", "Gagal", "Terjadi kesalahan saat menghapus");
            console.log(e);
        },
    });
});

$(document).on("click", "#btn-force-delete-warehouse-type", function () {
    $.ajax({
        url: "/deleteWarehouseType",
        data: {
            id: $(this).attr("data-id"),
            force: 1,
            _token: token,
        },
        method: "post",
        success: function () {
            $(".modal").modal("hide");
            refreshWarehouseType();
            notifikasi("success", "Berhasil Delete", "Berhasil hapus tipe gudang");
        },
        error: function (e) {
            notifikasi("error", "Gagal", "Terjadi kesalahan saat menghapus");
            console.log(e);
        },
    });
});
