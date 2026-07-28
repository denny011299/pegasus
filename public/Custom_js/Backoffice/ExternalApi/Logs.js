var table;

$(document).ready(function () {
    inisialisasi();
    isiStrategiPembersihan();
    refreshExternalApiLog();
    refreshRingkasan();
});

function inisialisasi() {
    table = $('#tableExternalApiLog').DataTable({
        bFilter: true,
        sDom: 'fBtlpi',
        lengthMenu: [10, 25, 50, 100],
        ordering: true,
        scrollX: true,
        order: [],
        language: {
            search: ' ',
            sLengthMenu: '_MENU_',
            searchPlaceholder: "Cari Log",
            info: "_START_ - _END_ of _TOTAL_ items",
            paginate: {
                next: ' <i class=" fa fa-angle-right"></i>',
                previous: '<i class="fa fa-angle-left"></i> '
            },
        },
        columns: [
            { data: "requested_text" },
            { data: "application_name", defaultContent: "-" },
            { data: "key_name", defaultContent: "-" },
            { data: "method_badge", class: "text-center align-middle" },
            { data: "endpoint" },
            { data: "status_badge", class: "text-center align-middle" },
            { data: "duration_text", class: "text-end align-middle" },
            { data: "ip_address", defaultContent: "-" },
            { data: "user_agent_short", defaultContent: "-" },
        ],
        initComplete: (settings, json) => {
            $('.dataTables_filter').appendTo('#tableSearch');
            $('.dataTables_filter').appendTo('.search-input');
            $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
        },
    });
}

function refreshExternalApiLog() {
    $.ajax({
        url: "/getExternalApiLog",
        method: "get",
        data: {
            external_application_id: $('#filter_log_application').val(),
            method: $('#filter_log_method').val(),
            status_code: $('#filter_log_status').val(),
            endpoint: $('#filter_log_endpoint').val(),
            start_date: $('#filter_log_start_date').val(),
            end_date: $('#filter_log_end_date').val(),
        },
        success: function (e) {
            if (!Array.isArray(e)) {
                e = e.original || [];
            }
            table.clear().draw();
            for (let i = 0; i < e.length; i++) {
                e[i].requested_text = e[i].requested_at
                    ? moment(e[i].requested_at).format('D MMM YYYY HH:mm:ss')
                    : '-';
                e[i].method_badge = '<span class="badge ' + methodBadgeClass(e[i].method) + '">' + e[i].method + '</span>';
                e[i].status_badge = '<span class="badge ' + statusBadgeClass(e[i].status_code) + '">' + e[i].status_code + '</span>';
                e[i].duration_text = e[i].duration_ms + ' ms';
                e[i].endpoint = '<code>' + e[i].endpoint + '</code>';
                e[i].user_agent_short = e[i].user_agent
                    ? '<span title="' + e[i].user_agent + '">' + potong(e[i].user_agent, 40) + '</span>'
                    : '-';
            }

            table.rows.add(e).draw();
            feather.replace();
        },
        error: function (err) {
            console.error("Gagal load Log API Eksternal:", err);
        }
    });
}

function refreshRingkasan() {
    $.ajax({
        url: "/getExternalApiLogSummary",
        method: "get",
        success: function (e) {
            $('#statTotal').html(e.total ? e.total.toLocaleString('id-ID') : '0');
            $('#statSize').html(e.estimated_size);
            $('#statOldest').html(e.oldest_at ? moment(e.oldest_at).format('D MMM YYYY HH:mm') : '-');
            $('#statNewest').html(e.newest_at ? moment(e.newest_at).format('D MMM YYYY HH:mm') : '-');
        },
        error: function (err) {
            console.error("Gagal load ringkasan log:", err);
        }
    });
}

function methodBadgeClass(method) {
    if (method == 'POST') return 'badge-soft-success';
    if (method == 'PUT' || method == 'PATCH') return 'badge-soft-warning';
    if (method == 'DELETE') return 'badge-soft-danger';
    return 'badge-soft-info';
}

function statusBadgeClass(code) {
    if (code >= 500) return 'badge-soft-danger';
    if (code >= 400) return 'badge-soft-warning';
    if (code >= 200 && code < 300) return 'badge-soft-success';
    return 'badge-soft-secondary';
}

function potong(text, panjang) {
    return text.length > panjang ? text.substring(0, panjang) + '…' : text;
}

$(document).on('click', '.btn-filter', function () {
    refreshExternalApiLog();
});

$(document).on('click', '.btn-clear', function () {
    $('#filter_log_application').val('');
    $('#filter_log_method').val('');
    $('#filter_log_status').val('');
    $('#filter_log_endpoint').val('');
    $('#filter_log_start_date').val('');
    $('#filter_log_end_date').val('');
    refreshExternalApiLog();
});

$(document).on('change', '#toggleLogging', function () {
    var enabled = $(this).is(':checked');

    $.ajax({
        url: "/toggleExternalApiLogging",
        method: "post",
        data: { enabled: enabled ? 1 : 0, _token: token },
        success: function (e) {
            $('#loggingStatusBadge')
                .removeClass('badge-soft-success badge-soft-secondary')
                .addClass(enabled ? 'badge-soft-success' : 'badge-soft-secondary')
                .html(enabled ? 'Aktif' : 'Nonaktif');
            notifikasi('success', "Berhasil", enabled
                ? "Pencatatan permintaan diaktifkan"
                : "Pencatatan permintaan dinonaktifkan");
        },
        error: function (err) {
            console.error("Gagal ubah status pencatatan:", err);
            notifikasi('error', "Gagal", 'Terjadi kesalahan saat mengubah status pencatatan');
            $('#toggleLogging').prop('checked', !enabled);
        }
    });
});

/**
 * Pilihan pembersihan dibangun dari daftar yang dikirim server, jadi strategi
 * baru cukup ditambahkan di config tanpa menyentuh file ini.
 */
function isiStrategiPembersihan() {
    var html = '';
    for (let i = 0; i < logCleanupStrategies.length; i++) {
        html += '<option value="' + logCleanupStrategies[i].key + '">' + logCleanupStrategies[i].label + '</option>';
    }
    $('#cleanup_strategy').html(html);
    perbaruiTampilanStrategi();
}

function strategiTerpilih() {
    var key = $('#cleanup_strategy').val();
    for (let i = 0; i < logCleanupStrategies.length; i++) {
        if (logCleanupStrategies[i].key == key) return logCleanupStrategies[i];
    }
    return null;
}

function perbaruiTampilanStrategi() {
    var s = strategiTerpilih();
    if (!s) return;

    $('#cleanupDateWrapper').toggleClass('d-none', !s.needs_date);
    $('#cleanupWarning').html(s.deletes_everything
        ? 'Seluruh isi log akan dihapus permanen dan tidak dapat dikembalikan.'
        : 'Log yang sudah dihapus tidak dapat dikembalikan.');
}

$(document).on('change', '#cleanup_strategy', function () {
    perbaruiTampilanStrategi();
});

$(document).on('click', '.btnCleanupLog', function () {
    $('#cleanup_before').val('');
    perbaruiTampilanStrategi();
    $('#cleanup_external_api_log').modal("show");
});

$(document).on('click', '.btn-confirm-cleanup', function () {
    var s = strategiTerpilih();
    $('.is-invalid').removeClass('is-invalid');

    if (s && s.needs_date && !$('#cleanup_before').val()) {
        $('#cleanup_before').addClass('is-invalid');
        notifikasi('error', "Gagal", 'Tanggal & waktu batas wajib diisi');
        return false;
    }

    LoadingButton(this);

    $.ajax({
        url: "/deleteExternalApiLog",
        method: "post",
        data: {
            strategy: $('#cleanup_strategy').val(),
            before: $('#cleanup_before').val(),
            _token: token
        },
        success: function (e) {
            ResetLoadingButton('.btn-confirm-cleanup', "Hapus Log");

            if (e.status == -1) {
                notifikasi('error', "Gagal", e.message);
                return false;
            }

            $('.modal').modal("hide");
            refreshExternalApiLog();
            refreshRingkasan();
            notifikasi('success', "Berhasil", e.message);
        },
        error: function (err) {
            console.error("Gagal bersihkan log:", err);
            notifikasi('error', "Gagal", 'Terjadi kesalahan saat membersihkan log');
            ResetLoadingButton('.btn-confirm-cleanup', "Hapus Log");
        }
    });
});
