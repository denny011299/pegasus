var mode = 1; // 1 = insert, 2 = update
var table;

$(document).ready(function () {
    inisialisasi();
    refreshExternalApplication();
});

function inisialisasi() {
    table = $('#tableExternalApplication').DataTable({
        bFilter: true,
        sDom: 'fBtlpi',
        lengthMenu: [10, 25, 50, 100],
        ordering: true,
        scrollX: true,
        language: {
            search: ' ',
            sLengthMenu: '_MENU_',
            searchPlaceholder: "Cari Aplikasi",
            info: "_START_ - _END_ of _TOTAL_ items",
            paginate: {
                next: ' <i class=" fa fa-angle-right"></i>',
                previous: '<i class="fa fa-angle-left"></i> '
            },
        },
        columns: [
            { data: "application_name" },
            { data: "application_code" },
            { data: "company", defaultContent: "-" },
            { data: "contact", defaultContent: "-" },
            { data: "key_info", class: "text-center align-middle" },
            { data: "status_badge", class: "text-center align-middle" },
            { data: "created_by_name", defaultContent: "-" },
            { data: "created_date" },
            { data: "action", class: "text-center align-middle" },
        ],
        initComplete: (settings, json) => {
            $('.dataTables_filter').appendTo('#tableSearch');
            $('.dataTables_filter').appendTo('.search-input');
            $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
        },
    });
}

function refreshExternalApplication() {
    $.ajax({
        url: "/getExternalApplication",
        method: "get",
        data: {
            application_name: $('#filter_application_name').val(),
            application_status: $('#filter_application_status').val(),
        },
        success: function (e) {
            if (!Array.isArray(e)) {
                e = e.original || [];
            }
            table.clear().draw();
            for (let i = 0; i < e.length; i++) {
                e[i].created_date = moment(e[i].created_at).format('D MMM YYYY');
                e[i].application_code = '<code>' + e[i].application_code + '</code>';
                e[i].contact = e[i].contact_name
                    ? e[i].contact_name + (e[i].contact_email ? '<br><small class="text-muted">' + e[i].contact_email + '</small>' : '')
                    : (e[i].contact_email || '-');
                e[i].key_info = '<span class="badge badge-soft-info">' + e[i].total_key + ' kunci</span>';
                e[i].status_badge = e[i].application_status == 'active'
                    ? '<span class="badge badge-soft-success">Aktif</span>'
                    : '<span class="badge badge-soft-danger">Nonaktif</span>';

                // Ikon "kelola kunci" memakai roleIconView karena membuka
                // halaman rincian, bukan mengubah data aplikasi.
                var be =
                    roleIconView(
                        "Aplikasi Eksternal",
                        "me-2 btn-action-icon p-2",
                        'href="/externalApplication/' + e[i].external_application_id + '" title="Kelola API Key"'
                    ) +
                    roleIconEdit(
                        "Aplikasi Eksternal",
                        "me-2 btn-action-icon p-2 btn_edit",
                        'data-id="' + e[i].external_application_id + '" href="javascript:void(0);"'
                    ) +
                    roleIconDelete(
                        "Aplikasi Eksternal",
                        "p-2 btn-action-icon btn_delete",
                        'data-id="' + e[i].external_application_id + '" href="javascript:void(0);"'
                    );
                e[i].action = be || '<span class="text-muted small">—</span>';
            }

            table.rows.add(e).draw();
            feather.replace();
        },
        error: function (err) {
            if (handlePermissionError(err)) return;
            console.error("Gagal load Aplikasi Eksternal:", err);
        }
    });
}

$(document).on('change', '#filter_application_name, #filter_application_status', function () {
    refreshExternalApplication();
});

$(document).on('click', '.btn-clear', function () {
    $('#filter_application_name').val('');
    $('#filter_application_status').val('');
    refreshExternalApplication();
});

$(document).on('click', '.btnAdd', function () {
    mode = 1;
    $('#add_external_application .modal-title').html("Tambah Aplikasi");
    $('#add_external_application input').val("");
    $('#add_external_application textarea').val("");
    $('#application_status').val('active');
    $('#application_code').prop('readonly', false);
    $('.is-invalid').removeClass('is-invalid');
    $('.btn-save').html("Tambah Aplikasi");
    $('#add_external_application').modal("show");
});

$(document).on('click', '.btn_edit', function () {
    mode = 2;
    var id = $(this).attr('data-id');

    $.ajax({
        url: "/getExternalApplication",
        method: "get",
        data: { external_application_id: id },
        success: function (e) {
            if (!Array.isArray(e)) {
                e = e.original || [];
            }
            if (e.length == 0) {
                notifikasi('error', "Gagal", 'Aplikasi tidak ditemukan');
                return false;
            }

            var row = e[0];
            $('#add_external_application .modal-title').html("Ubah Aplikasi");
            $('#add_external_application').attr("external_application_id", row.external_application_id);
            $('#application_name').val(row.application_name);
            // Kode adalah identitas yang dipegang sistem eksternal, jadi
            // ditampilkan tapi tidak boleh diubah.
            $('#application_code').val(row.application_code).prop('readonly', true);
            $('#company').val(row.company);
            $('#contact_name').val(row.contact_name);
            $('#contact_email').val(row.contact_email);
            $('#description').val(row.description);
            $('#application_status').val(row.application_status);
            $('.is-invalid').removeClass('is-invalid');
            $('.btn-save').html("Update Aplikasi");
            $('#add_external_application').modal("show");
        },
        error: function (err) {
            if (handlePermissionError(err)) return;
            console.error("Gagal load Aplikasi Eksternal:", err);
        }
    });
});

$(document).on("click", ".btn-save", function () {
    LoadingButton(this);
    $('.is-invalid').removeClass('is-invalid');
    var url = "/insertExternalApplication";
    var valid = 1;

    $("#add_external_application .fill").each(function () {
        if ($(this).val() == null || $(this).val() == "null" || $(this).val() == "") {
            valid = -1;
            $(this).addClass('is-invalid');
        }
    });

    if (valid == -1) {
        notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
        ResetLoadingButton('.btn-save', mode == 1 ? "Tambah Aplikasi" : "Update Aplikasi");
        return false;
    }

    param = {
        application_name: $('#application_name').val(),
        application_code: $('#application_code').val(),
        company: $('#company').val(),
        contact_name: $('#contact_name').val(),
        contact_email: $('#contact_email').val(),
        description: $('#description').val(),
        application_status: $('#application_status').val(),
        _token: token
    };

    if (mode == 2) {
        url = "/updateExternalApplication";
        param.external_application_id = $('#add_external_application').attr("external_application_id");
    }

    $.ajax({
        url: url,
        method: "post",
        data: param,
        success: function (e) {
            if (e.status == -1) {
                notifikasi('error', "Gagal", e.message);
                ResetLoadingButton('.btn-save', mode == 1 ? "Tambah Aplikasi" : "Update Aplikasi");
                return false;
            }
            $('.modal').modal("hide");
            refreshExternalApplication();
            notifikasi('success', mode == 1 ? "Berhasil Insert" : "Berhasil Update", "Data aplikasi berhasil disimpan");
            ResetLoadingButton('.btn-save', mode == 1 ? "Tambah Aplikasi" : "Update Aplikasi");
        },
        error: function (err) {
            ResetLoadingButton('.btn-save', mode == 1 ? "Tambah Aplikasi" : "Update Aplikasi");
            if (handlePermissionError(err)) return;
            console.error("Gagal simpan Aplikasi Eksternal:", err);
            notifikasi('error', "Gagal", 'Terjadi kesalahan saat menyimpan');
        }
    });
});

$(document).on('click', '.btn_delete', function () {
    var id = $(this).attr('data-id');
    showModalDelete(
        "Menghapus aplikasi ini juga mematikan seluruh API Key miliknya. Lanjutkan?",
        "btn-delete-external-application"
    );
    $('#btn-delete-external-application').attr('data-id', id);
});

$(document).on('click', '#btn-delete-external-application', function () {
    var id = $(this).attr('data-id');

    $.ajax({
        url: "/deleteExternalApplication",
        method: "post",
        data: { external_application_id: id, _token: token },
        success: function (e) {
            $('.modal').modal("hide");
            refreshExternalApplication();
            notifikasi('success', "Berhasil Delete", "Aplikasi eksternal berhasil dihapus");
        },
        error: function (err) {
            if (handlePermissionError(err)) return;
            console.error("Gagal delete Aplikasi Eksternal:", err);
            notifikasi('error', "Gagal", 'Terjadi kesalahan saat menghapus');
        }
    });
});
