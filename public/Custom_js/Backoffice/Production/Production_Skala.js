var mode = 1;
var tableSkala;
var skalaTableReady = false;

$(document).ready(function () {
    inisialisasi();
    refreshProductionSkala();
});

function showSkalaSkeleton() {
    var $wrap = $("#tableProductionSkala-wrap");
    if (!$wrap.length) return;
    if (!skalaTableReady) {
        $wrap.removeClass("dt-ready is-loading").addClass("dt-pending");
        return;
    }
    $wrap.removeClass("dt-pending").addClass("dt-ready is-loading");
}

function hideSkalaSkeleton() {
    var $wrap = $("#tableProductionSkala-wrap");
    if (!$wrap.length) return;
    $wrap.removeClass("dt-pending is-loading").addClass("dt-ready");
    skalaTableReady = true;
}

$(document).on("click", ".btnAdd", function () {
    mode = 1;
    $("#add_production_skala .modal-title").html("Tambah Skala");
    $("#add_production_skala input").val("");
    $(".is-invalid").removeClass("is-invalid");
    $(".btn-save-skala").html('<i class="fe fe-save me-1"></i>Tambah Skala');
    $("#add_production_skala").modal("show");
});

function inisialisasi() {
    tableSkala = $("#tableProductionSkala").DataTable({
        bFilter: true,
        sDom: "fBtlpi",
        lengthMenu: [10, 25, 50, 100],
        pageLength: 10,
        ordering: true,
        autoWidth: false,
        deferRender: true,
        language: {
            search: " ",
            sLengthMenu: "_MENU_",
            searchPlaceholder: "Cari Skala",
            info: "_START_ - _END_ of _TOTAL_ items",
            processing:
                '<div class="d-flex align-items-center gap-2"><span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span><span>Memuat data...</span></div>',
            emptyTable: "Tidak ada data tersedia",
            zeroRecords: "Tidak ada data tersedia",
            paginate: {
                next: ' <i class=" fa fa-angle-right"></i>',
                previous: '<i class="fa fa-angle-left"></i> ',
            },
        },
        columns: [
            { data: "code", width: "14%" },
            { data: "name", width: "26%" },
            { data: "combo_label", defaultContent: "—", width: "26%" },
            {
                data: "created_by_name",
                defaultContent: "-",
                width: "20%",
                render: function (data, type, row) {
                    return typeof renderCreatedBySync === "function"
                        ? renderCreatedBySync(data, row)
                        : data;
                },
            },
            { data: "action", class: "text-center align-middle", width: "14%", orderable: false },
        ],
        initComplete: function () {
            $(".dataTables_filter").appendTo("#tableSearch");
            $(".dataTables_filter").appendTo(".search-input");
            $(".dataTables_filter label").prepend('<i class="fa fa-search"></i> ');
        },
    });
}

function refreshProductionSkala() {
    showSkalaSkeleton();
    $.ajax({
        url: "/getProductionSkala",
        method: "get",
        success: function (e) {
            if (!Array.isArray(e)) e = e.original || [];
            tableSkala.clear();
            for (var i = 0; i < e.length; i++) {
                var ue =
                    roleIconEdit(
                        "Satuan",
                        "btn-action-icon me-2 p-2 btn_edit_skala",
                        'data-id="' + e[i].production_skala_id + '"'
                    ) +
                    roleIconDelete(
                        "Satuan",
                        "btn-action-icon p-2 btn_delete btn_delete_skala",
                        'data-id="' + e[i].production_skala_id + '" href="javascript:void(0);"'
                    );
                e[i].action = ue
                    ? '<div class="edit-delete-action d-flex align-items-center justify-content-center gap-1">' +
                      ue +
                      "</div>"
                    : '<span class="text-muted small">—</span>';
            }
            tableSkala.rows.add(e).draw();
            hideSkalaSkeleton();
            if (typeof feather !== "undefined") feather.replace();
        },
        error: function (err) {
            hideSkalaSkeleton();
            if (handlePermissionError(err)) return;
            console.error("Gagal load skala:", err);
        },
    });
}

$(document).on("click", ".btn-save-skala", function () {
    LoadingButton(this);
    $(".is-invalid").removeClass("is-invalid");
    var url = "/insertProductionSkala";
    var valid = 1;
    $("#add_production_skala .fill").each(function () {
        if ($(this).val() == null || $(this).val() === "null" || $(this).val() === "") {
            valid = -1;
            $(this).addClass("is-invalid");
        }
    });
    if (valid === -1) {
        notifikasi("error", "Gagal Insert", "Silahkan cek kembali inputan anda");
        ResetLoadingButton(".btn-save-skala", mode == 1 ? '<i class="fe fe-save me-1"></i>Tambah Skala' : '<i class="fe fe-save me-1"></i>Update Skala');
        return false;
    }
    var param = {
        code: $("#skala_code").val(),
        name: $("#skala_name").val(),
        combo_label: $("#skala_combo_label").val(),
        _token: token,
    };
    if (mode == 2) {
        url = "/updateProductionSkala";
        param.production_skala_id = $("#add_production_skala").attr("production_skala_id");
    }
    $.ajax({
        url: url,
        data: param,
        method: "post",
        headers: { "X-CSRF-TOKEN": token },
        success: function () {
            ResetLoadingButton(".btn-save-skala", mode == 1 ? '<i class="fe fe-save me-1"></i>Tambah Skala' : '<i class="fe fe-save me-1"></i>Update Skala');
            $(".modal").modal("hide");
            notifikasi("success", mode == 1 ? "Berhasil Insert" : "Berhasil Update", mode == 1 ? "Berhasil Tambah Skala" : "Berhasil Update Skala");
            refreshProductionSkala();
        },
        error: function (e) {
            ResetLoadingButton(".btn-save-skala", mode == 1 ? '<i class="fe fe-save me-1"></i>Tambah Skala' : '<i class="fe fe-save me-1"></i>Update Skala');
            if (handlePermissionError(e)) return;
            var msg = (e.responseJSON && e.responseJSON.message) || "Silahkan cek kembali inputan anda";
            notifikasi("error", mode == 1 ? "Gagal Insert" : "Gagal Update", msg);
        },
    });
});

$(document).on("click", ".btn_edit_skala", function () {
    var data = $("#tableProductionSkala").DataTable().row($(this).parents("tr")).data();
    mode = 2;
    $("#add_production_skala .modal-title").html("Update Skala");
    $("#skala_code").val(data.code);
    $("#skala_name").val(data.name);
    $("#skala_combo_label").val(data.combo_label || "");
    $(".is-invalid").removeClass("is-invalid");
    $(".btn-save-skala").html('<i class="fe fe-save me-1"></i>Update Skala');
    $("#add_production_skala").attr("production_skala_id", data.production_skala_id);
    $("#add_production_skala").modal("show");
});

$(document).on("click", ".btn_delete_skala", function () {
    var data = $("#tableProductionSkala").DataTable().row($(this).parents("tr")).data();
    showModalDelete("Apakah yakin ingin menghapus skala ini?", "btn-delete-skala");
    $("#btn-delete-skala").attr("production_skala_id", data.production_skala_id);
});

$(document).on("click", "#btn-delete-skala", function () {
    $.ajax({
        url: "/deleteProductionSkala",
        data: {
            production_skala_id: $("#btn-delete-skala").attr("production_skala_id"),
            _token: token,
        },
        method: "post",
        success: function () {
            $(".modal").modal("hide");
            refreshProductionSkala();
            notifikasi("success", "Berhasil Delete", "Berhasil hapus skala");
        },
        error: function (e) {
            if (handlePermissionError(e)) return;
        },
    });
});
