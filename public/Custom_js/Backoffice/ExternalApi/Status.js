var table;

$(document).ready(function () {
    inisialisasi();
    refreshExternalApiStatus();
});

function inisialisasi() {
    // Filter Versi/Kelompok/Metode/Endpoint Aktif/Dokumentasi Publik — data
    // tabel ini sudah dimuat sekali lewat AJAX (tidak dipaginasi server),
    // jadi kelima filter ini bekerja langsung di rowData tiap baris, tanpa
    // permintaan baru ke server. Dijaga dengan cek id tabel karena predikat
    // ini berlaku global untuk seluruh instance DataTables di halaman.
    $.fn.dataTable.ext.search.push(function (settings, searchData, index, rowData) {
        if (settings.nTable.id !== 'tableApiStatus' || !rowData) {
            return true;
        }

        var version = $('#filter_status_version').val();
        var group = $('#filter_status_group').val();
        var method = $('#filter_status_method').val();
        var active = $('#filter_status_active').val();
        var publicDocs = $('#filter_status_public').val();

        if (version && rowData.version !== version) return false;
        if (group && rowData.group_title !== group) return false;
        if (method && rowData.method !== method) return false;
        if (active !== '' && String(rowData.is_active ? 1 : 0) !== active) return false;
        if (publicDocs !== '' && String(rowData.is_public_docs_show ? 1 : 0) !== publicDocs) return false;

        return true;
    });

    table = $('#tableApiStatus').DataTable({
        bFilter: true,
        sDom: 'fBtlpi',
        lengthMenu: [10, 25, 50, 100],
        pageLength: 25,
        ordering: true,
        order: [],
        language: {
            search: ' ',
            sLengthMenu: '_MENU_',
            searchPlaceholder: "Cari Endpoint",
            info: "_START_ - _END_ of _TOTAL_ items",
            paginate: {
                next: ' <i class="fa fa-angle-right"></i>',
                previous: '<i class="fa fa-angle-left"></i> '
            },
        },
        columns: [
            { data: "endpoint_cell", width: "34%" },
            { data: "version_badge", class: "text-center align-middle", width: "8%" },
            { data: "group_title", width: "14%" },
            { data: "active_control", width: "22%" },
            { data: "docs_control", width: "22%" },
        ],
        initComplete: function () {
            $('.dataTables_filter').appendTo('#tableSearch');
            $('.dataTables_filter').appendTo('.search-input');
            $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
        },
    });
}

function refreshExternalApiStatus() {
    $.ajax({
        url: "/getExternalApiStatus",
        method: "get",
        success: function (e) {
            if (!Array.isArray(e)) {
                e = e.original || [];
            }

            var canToggle = hasAccessAction('Status API Eksternal', 'others');
            table.clear().draw();
            isiFilterOptions(e);

            for (let i = 0; i < e.length; i++) {
                e[i].version_badge = '<span class="badge badge-soft-primary">' + e[i].version.toUpperCase() + '</span>';
                e[i].endpoint_cell = '<div class="d-flex align-items-center flex-wrap mb-1">'
                    + '<span class="badge ' + methodBadgeClass(e[i].method) + ' me-2">' + e[i].method + '</span>'
                    + '<code class="extapi-endpoint-path">' + e[i].full_path + '</code>'
                    + '</div><div class="fw-semibold">' + e[i].title + '</div>';
                e[i].active_control = toggleCellHtml(e[i].key, 'active', e[i].is_active, 'Aktif', 'Nonaktif', canToggle);
                e[i].docs_control = toggleCellHtml(e[i].key, 'docs', e[i].is_public_docs_show, 'Tampil', 'Tersembunyi', canToggle);
            }

            table.rows.add(e).draw();
            feather.replace();
        },
        error: function (err) {
            console.error("Gagal load Status API Eksternal:", err);
        }
    });
}

/**
 * Isi pilihan Versi/Kelompok/Metode dari data yang benar-benar ada, jadi
 * versi/kelompok/metode baru otomatis jadi pilihan tanpa menyentuh berkas
 * ini. Pilihan yang sedang dipakai dipertahankan kalau masih tersedia.
 */
function isiFilterOptions(rows) {
    isiSelectOptions('#filter_status_version', uniqueSorted(rows.map(function (r) { return r.version; })), function (v) { return v.toUpperCase(); });
    isiSelectOptions('#filter_status_group', uniqueSorted(rows.map(function (r) { return r.group_title; })));
    isiSelectOptions('#filter_status_method', uniqueSorted(rows.map(function (r) { return r.method; })));
}

function uniqueSorted(values) {
    return values
        .filter(function (v, i) { return v && values.indexOf(v) === i; })
        .sort();
}

function isiSelectOptions(selector, values, labelFn) {
    var $select = $(selector);
    if (!$select.length) return;

    var current = $select.val();
    var html = '<option value="">Semua</option>';

    for (let i = 0; i < values.length; i++) {
        var label = labelFn ? labelFn(values[i]) : values[i];
        html += '<option value="' + values[i] + '">' + label + '</option>';
    }

    $select.html(html);
    $select.val(current);
}

function toggleCellHtml(key, kind, checked, onLabel, offLabel, canToggle) {
    var badgeClass = checked ? 'badge-soft-success' : 'badge-soft-secondary';
    var label = checked ? onLabel : offLabel;
    var html = '<div class="d-flex align-items-center gap-2">';
    html += '<span class="badge ' + badgeClass + '" data-status-badge="' + kind + '-' + key + '">' + label + '</span>';
    if (canToggle) {
        html += '<div class="form-check form-switch mb-0">';
        html += '<input class="form-check-input toggle-endpoint-' + kind + '" type="checkbox" data-key="' + key + '"' + (checked ? ' checked' : '') + '>';
        html += '</div>';
    }
    html += '</div>';
    return html;
}

function methodBadgeClass(method) {
    if (method == 'POST') return 'badge-soft-success';
    if (method == 'PUT' || method == 'PATCH') return 'badge-soft-warning';
    if (method == 'DELETE') return 'badge-soft-danger';
    return 'badge-soft-info';
}

$(document).on('change', '#filter_status_version, #filter_status_group, #filter_status_method, #filter_status_active, #filter_status_public', function () {
    if (table) table.draw();
});

$(document).on('click', '.btn-clear', function () {
    $('#filter_status_version, #filter_status_group, #filter_status_method, #filter_status_active, #filter_status_public').val('');
    if (table) table.draw();
});

$(document).on('change', '.toggle-endpoint-active', function () {
    var $checkbox = $(this);
    var key = $checkbox.data('key');
    var enabled = $checkbox.is(':checked');

    $.ajax({
        url: "/toggleExternalApiEndpointActive",
        method: "post",
        data: { key: key, enabled: enabled ? 1 : 0, _token: token },
        success: function (e) {
            if (e.status == -1) {
                notifikasi('error', "Gagal", e.message);
                $checkbox.prop('checked', !enabled);
                return;
            }

            $('[data-status-badge="active-' + key + '"]')
                .removeClass('badge-soft-success badge-soft-secondary')
                .addClass(enabled ? 'badge-soft-success' : 'badge-soft-secondary')
                .html(enabled ? 'Aktif' : 'Nonaktif');

            notifikasi('success', "Berhasil", enabled
                ? "Endpoint diaktifkan"
                : "Endpoint dinonaktifkan");
        },
        error: function (err) {
            console.error("Gagal ubah status endpoint:", err);
            notifikasi('error', "Gagal", 'Terjadi kesalahan saat mengubah status endpoint');
            $checkbox.prop('checked', !enabled);
        }
    });
});

$(document).on('change', '.toggle-endpoint-docs', function () {
    var $checkbox = $(this);
    var key = $checkbox.data('key');
    var enabled = $checkbox.is(':checked');

    $.ajax({
        url: "/toggleExternalApiEndpointPublicDocs",
        method: "post",
        data: { key: key, enabled: enabled ? 1 : 0, _token: token },
        success: function (e) {
            if (e.status == -1) {
                notifikasi('error', "Gagal", e.message);
                $checkbox.prop('checked', !enabled);
                return;
            }

            $('[data-status-badge="docs-' + key + '"]')
                .removeClass('badge-soft-success badge-soft-secondary')
                .addClass(enabled ? 'badge-soft-success' : 'badge-soft-secondary')
                .html(enabled ? 'Tampil' : 'Tersembunyi');

            notifikasi('success', "Berhasil", enabled
                ? "Dokumentasi publik endpoint ditampilkan"
                : "Dokumentasi publik endpoint disembunyikan");
        },
        error: function (err) {
            console.error("Gagal ubah status dokumentasi publik:", err);
            notifikasi('error', "Gagal", 'Terjadi kesalahan saat mengubah status dokumentasi publik');
            $checkbox.prop('checked', !enabled);
        }
    });
});
