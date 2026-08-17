var mode = 1; // 1 = insert, 2 = update
var table;

$(document).ready(function () {
    inisialisasi();
    refreshExternalApiKey();
});

function inisialisasi() {
    table = $('#tableExternalApiKey').DataTable({
        bFilter: true,
        sDom: 'fBtlpi',
        lengthMenu: [10, 25, 50, 100],
        ordering: true,
        scrollX: true,
        language: {
            search: ' ',
            sLengthMenu: '_MENU_',
            searchPlaceholder: "Cari API Key",
            info: "_START_ - _END_ of _TOTAL_ items",
            paginate: {
                next: ' <i class=" fa fa-angle-right"></i>',
                previous: '<i class="fa fa-angle-left"></i> '
            },
        },
        columns: [
            { data: "key_name" },
            { data: "environment_badge", class: "text-center align-middle" },
            { data: "masked_key" },
            { data: "status_badge", class: "text-center align-middle" },
            { data: "expiry_text" },
            { data: "last_used_text" },
            { data: "created_by_name", defaultContent: "-" },
            { data: "action", class: "text-center align-middle" },
        ],
        initComplete: (settings, json) => {
            $('.dataTables_filter').appendTo('#tableSearch');
            $('.dataTables_filter').appendTo('.search-input');
            $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
        },
    });
}

function refreshExternalApiKey() {
    $.ajax({
        url: "/getExternalApiKey",
        method: "get",
        data: { external_application_id: externalApplicationId },
        success: function (e) {
            if (!Array.isArray(e)) {
                e = e.original || [];
            }
            table.clear().draw();
            for (let i = 0; i < e.length; i++) {
                e[i].environment_badge = '<span class="badge badge-soft-secondary">' + e[i].environment + '</span>';
                e[i].masked_key = '<code>' + e[i].masked_key + '</code>';
                e[i].status_badge = '<span class="badge ' + statusBadgeClass(e[i].effective_status) + '">'
                    + e[i].effective_status_label + '</span>';
                e[i].expiry_text = e[i].expires_at
                    ? moment(e[i].expires_at).format('D MMM YYYY HH:mm')
                    : '<span class="text-muted">Tidak pernah</span>';
                e[i].last_used_text = e[i].last_used_at
                    ? moment(e[i].last_used_at).format('D MMM YYYY HH:mm')
                    : '<span class="text-muted">Belum pernah</span>';

                // Mencabut kunci bukan operasi CRUD biasa, jadi izinnya
                // memakai ability "others" seperti aksi approve/decline lain.
                var be = "";
                if (e[i].effective_status == 'active' && hasAccessAction("Aplikasi Eksternal", "others")) {
                    be += '<a class="me-2 btn-action-icon p-2 btn_revoke" data-id="' + e[i].external_api_key_id
                        + '" href="javascript:void(0);" title="Cabut"><i class="fe fe-slash"></i></a>';
                }
                be += roleIconEdit(
                    "Aplikasi Eksternal",
                    "me-2 btn-action-icon p-2 btn_edit",
                    'data-id="' + e[i].external_api_key_id + '" href="javascript:void(0);"'
                );
                be += roleIconDelete(
                    "Aplikasi Eksternal",
                    "p-2 btn-action-icon btn_delete",
                    'data-id="' + e[i].external_api_key_id + '" href="javascript:void(0);"'
                );
                e[i].action = be || '<span class="text-muted small">—</span>';
            }

            table.rows.add(e).draw();
            feather.replace();
        },
        error: function (err) {
            if (handlePermissionError(err)) return;
            console.error("Gagal load API Key:", err);
        }
    });
}

function statusBadgeClass(status) {
    if (status == 'revoked') return 'badge-soft-danger';
    if (status == 'expired') return 'badge-soft-warning';
    return 'badge-soft-success';
}

$(document).on('change', '#never_expire', function () {
    $('#expiryWrapper').toggleClass('d-none', $(this).is(':checked'));
});

$(document).on('click', '.btnAddKey', function () {
    mode = 1;
    $('#add_external_api_key .modal-title').html("Buat API Key");
    $('#key_name').val("");
    $('#expires_at').val("");
    $('#environment').val('production').prop('disabled', false);
    $('#never_expire').prop('checked', true);
    $('#expiryWrapper').addClass('d-none');
    $('.is-invalid').removeClass('is-invalid');
    $('.btn-save-key').html("Buat API Key");
    $('#add_external_api_key').modal("show");
});

$(document).on('click', '.btn_edit', function () {
    mode = 2;
    var id = $(this).attr('data-id');

    $.ajax({
        url: "/getExternalApiKey",
        method: "get",
        data: { external_api_key_id: id },
        success: function (e) {
            if (!Array.isArray(e)) {
                e = e.original || [];
            }
            if (e.length == 0) {
                notifikasi('error', "Gagal", 'API Key tidak ditemukan');
                return false;
            }

            var row = e[0];
            $('#add_external_api_key .modal-title').html("Ubah API Key");
            $('#add_external_api_key').attr("external_api_key_id", row.external_api_key_id);
            $('#key_name').val(row.key_name);
            // Lingkungan ikut menentukan bentuk kunci yang sudah terlanjur
            // dipegang klien, jadi tidak bisa diubah setelah kunci dibuat.
            $('#environment').val(row.environment).prop('disabled', true);
            $('#never_expire').prop('checked', row.never_expire);
            $('#expiryWrapper').toggleClass('d-none', row.never_expire);
            $('#expires_at').val(row.expires_at ? moment(row.expires_at).format('YYYY-MM-DDTHH:mm') : '');
            $('.is-invalid').removeClass('is-invalid');
            $('.btn-save-key').html("Update API Key");
            $('#add_external_api_key').modal("show");
        },
        error: function (err) {
            if (handlePermissionError(err)) return;
            console.error("Gagal load API Key:", err);
        }
    });
});

$(document).on("click", ".btn-save-key", function () {
    LoadingButton(this);
    $('.is-invalid').removeClass('is-invalid');
    var url = "/insertExternalApiKey";
    var valid = 1;

    $("#add_external_api_key .fill").each(function () {
        if ($(this).val() == null || $(this).val() == "null" || $(this).val() == "") {
            valid = -1;
            $(this).addClass('is-invalid');
        }
    });

    if (!$('#never_expire').is(':checked') && !$('#expires_at').val()) {
        valid = -1;
        $('#expires_at').addClass('is-invalid');
    }

    if (valid == -1) {
        notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
        ResetLoadingButton('.btn-save-key', mode == 1 ? "Buat API Key" : "Update API Key");
        return false;
    }

    param = {
        external_application_id: externalApplicationId,
        key_name: $('#key_name').val(),
        environment: $('#environment').val(),
        never_expire: $('#never_expire').is(':checked') ? 1 : 0,
        expires_at: $('#expires_at').val(),
        _token: token
    };

    if (mode == 2) {
        url = "/updateExternalApiKey";
        param.external_api_key_id = $('#add_external_api_key').attr("external_api_key_id");
    }

    $.ajax({
        url: url,
        method: "post",
        data: param,
        success: function (e) {
            $('#add_external_api_key').modal("hide");
            ResetLoadingButton('.btn-save-key', mode == 1 ? "Buat API Key" : "Update API Key");

            // Satu-satunya saat kunci polos ada di tangan pengguna. Setelah
            // dialog ini ditutup, nilainya tidak bisa diambil kembali dari
            // mana pun — yang tersimpan di server hanyalah hash-nya.
            if (mode == 1 && e && e.plain_key) {
                $('#generated_key').val(e.plain_key);
                $('#show_external_api_key').modal("show");
            } else {
                notifikasi('success', "Berhasil Update", "API Key berhasil diperbarui");
            }

            refreshExternalApiKey();
        },
        error: function (err) {
            ResetLoadingButton('.btn-save-key', mode == 1 ? "Buat API Key" : "Update API Key");
            if (handlePermissionError(err)) return;
            console.error("Gagal simpan API Key:", err);
            notifikasi('error', "Gagal", 'Terjadi kesalahan saat menyimpan');
        }
    });
});

$(document).on('click', '.btn-copy-key', function () {
    var value = $('#generated_key').val();

    // navigator.clipboard hanya tersedia di konteks aman (HTTPS/localhost),
    // jadi disiapkan cara lama sebagai cadangan agar tombol tetap berfungsi
    // saat aplikasi diakses lewat HTTP di jaringan lokal.
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(value).then(function () {
            notifikasi('success', "Tersalin", "API Key berhasil disalin");
        });
    } else {
        $('#generated_key').select();
        document.execCommand('copy');
        notifikasi('success', "Tersalin", "API Key berhasil disalin");
    }
});

$(document).on('click', '.btn-close-key-dialog', function () {
    // Nilai dibersihkan dari DOM begitu dialog ditutup.
    $('#generated_key').val('');
    $('#show_external_api_key').modal("hide");
});

$(document).on('click', '.btn_revoke', function () {
    var id = $(this).attr('data-id');
    showModalDelete(
        "Mencabut kunci ini akan langsung menghentikan seluruh permintaan yang memakainya. Lanjutkan?",
        "btn-revoke-external-api-key"
    );
    $('#btn-revoke-external-api-key').attr('data-id', id);
});

$(document).on('click', '#btn-revoke-external-api-key', function () {
    var id = $(this).attr('data-id');

    $.ajax({
        url: "/revokeExternalApiKey",
        method: "post",
        data: { external_api_key_id: id, _token: token },
        success: function (e) {
            $('.modal').modal("hide");
            refreshExternalApiKey();
            notifikasi('success', "Berhasil Dicabut", "API Key sudah tidak berlaku");
        },
        error: function (err) {
            if (handlePermissionError(err)) return;
            console.error("Gagal cabut API Key:", err);
            notifikasi('error', "Gagal", 'Terjadi kesalahan saat mencabut kunci');
        }
    });
});

$(document).on('click', '.btn_delete', function () {
    var id = $(this).attr('data-id');
    showModalDelete("Apakah yakin ingin menghapus API Key ini?", "btn-delete-external-api-key");
    $('#btn-delete-external-api-key').attr('data-id', id);
});

$(document).on('click', '#btn-delete-external-api-key', function () {
    var id = $(this).attr('data-id');

    $.ajax({
        url: "/deleteExternalApiKey",
        method: "post",
        data: { external_api_key_id: id, _token: token },
        success: function (e) {
            $('.modal').modal("hide");
            refreshExternalApiKey();
            notifikasi('success', "Berhasil Delete", "API Key berhasil dihapus");
        },
        error: function (err) {
            if (handlePermissionError(err)) return;
            console.error("Gagal delete API Key:", err);
            notifikasi('error', "Gagal", 'Terjadi kesalahan saat menghapus');
        }
    });
});
