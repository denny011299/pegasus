var table;
var dates = null;

autocompleteProductVariantOnly("#product_id");

$(document).ready(function () {
    inisialisasi();
    var startMonth = moment().startOf('month').format('DD-MM-YYYY');
    var endMonth = moment().endOf('month').format('DD-MM-YYYY');
    $('#start_date').val(startMonth);
    $('#end_date').val(endMonth);
    dates = [startMonth, endMonth];
    refreshReturArmada();
});

function inisialisasi() {
    table = $('#tableReportReturArmada').DataTable({
        bFilter: true,
        sDom: 'fBtlpi',
        lengthMenu: [10, 25, 50, 100],
        ordering: true,
        searching: false,
        autoWidth: false,
        language: {
            search: ' ',
            sLengthMenu: '_MENU_',
            searchPlaceholder: 'Cari retur',
            info: '_START_ - _END_ of _TOTAL_ items',
            paginate: {
                next: ' <i class=" fa fa-angle-right"></i>',
                previous: '<i class="fa fa-angle-left"></i> ',
            },
        },
        columns: [
            {
                data: null,
                className: 'details-control text-center',
                orderable: false,
                width: '4%',
                defaultContent:
                    '<a href="javascript:void(0);" class="btn-action-icon p-1 btn-toggle-detail"><i class="fa fa-plus"></i></a>',
            },
            { data: 'item_name', className: 'col-item-name', width: '46%' },
            { data: 'transaction_summary', width: '25%' },
            { data: 'qty_summary', width: '25%' },
        ],
        initComplete: function () {
            $('.dataTables_filter').appendTo('#tableSearch');
            $('.dataTables_filter').appendTo('.search-input');
            $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
        },
    });
}

function buildQtyByUnitText(details) {
    var unitMap = {};
    if (!Array.isArray(details)) return '-';
    for (var i = 0; i < details.length; i++) {
        var d = details[i];
        var unit = (d.unit_name || '').toString().trim();
        if (unit === '') unit = 'unit';
        if (!unitMap[unit]) unitMap[unit] = 0;
        unitMap[unit] += parseInt(d.qty || 0, 10);
    }
    var parts = [];
    Object.keys(unitMap).forEach(function (unit) {
        if (unitMap[unit] > 0) parts.push(unitMap[unit] + ' ' + unit);
    });
    return parts.length ? parts.join(' ') : '-';
}

function escapeHtml(s) {
    if (s == null || s === '') return '';
    return String(s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function formatDetailRow(data) {
    var html =
        '<div class="child-row-wrapper" style="display:none; max-height: 320px; overflow-y: auto; overflow-x: auto;">' +
        '<table class="table table-sm table-bordered mb-0 report-retur-armada-child">' +
        '<thead><tr>' +
        '<th>Tanggal</th><th>Kode Issue</th><th>Qty</th><th>Keterangan</th>' +
        '</tr></thead><tbody>';

    if (data.details && data.details.length > 0) {
        for (var i = 0; i < data.details.length; i++) {
            var d = data.details[i];
            var note = escapeHtml(d.pi_notes || '-');
            html +=
                '<tr>' +
                '<td>' +
                moment(d.pi_date).format('D MMM YYYY') +
                '</td>' +
                '<td>' +
                escapeHtml(d.pi_code || '-') +
                '</td>' +
                '<td>' +
                parseInt(d.qty || 0, 10) +
                ' ' +
                escapeHtml((d.unit_name || '').toString()) +
                '</td>' +
                '<td>' +
                note +
                '</td>' +
                '</tr>';
        }
    } else {
        html += '<tr><td colspan="4" class="text-center">Tidak ada detail</td></tr>';
    }

    html += '</tbody></table></div>';
    return html;
}

function toggleDetailRow(tr, row) {
    var toggleBtn = tr.find('.btn-toggle-detail');
    var icon = toggleBtn.find('i');

    if (row.child.isShown()) {
        var wrapper = tr.next('tr').find('.child-row-wrapper');
        wrapper.stop(true, true).slideUp(140, function () {
            row.child.hide();
        });
        tr.removeClass('shown');
        icon.removeClass('fa-minus').addClass('fa-plus');
    } else {
        row.child(formatDetailRow(row.data())).show();
        tr.addClass('shown');
        var wrapperShow = tr.next('tr').find('.child-row-wrapper');
        wrapperShow.stop(true, true).slideDown(160);
        icon.removeClass('fa-plus').addClass('fa-minus');
    }
}

function refreshReturArmada() {
    $.ajax({
        url: '/getReportReturProdukArmada',
        method: 'get',
        data: {
            date: dates,
            product_variant_id: $('#product_id').val(),
        },
        beforeSend: function () {
            setReportDataTableLoading('#tableReportReturArmada', true);
        },
        complete: function () {
            setReportDataTableLoading('#tableReportReturArmada', false);
        },
        success: function (e) {
            if (!Array.isArray(e)) {
                e = e.original || [];
            }
            table.clear().draw();
            for (var i = 0; i < e.length; i++) {
                e[i].transaction_summary = (e[i].transaction_count || 0) + ' Baris';
                e[i].qty_summary = buildQtyByUnitText(e[i].details);
            }
            table.rows.add(e).draw();
            if (typeof feather !== 'undefined') feather.replace();
        },
        error: function (err) {
            console.error('Gagal load laporan retur armada:', err);
        },
    });
}

function normalizeDateValue(value) {
    if (!value) return '';
    var parsed = moment(value, ['DD-MM-YYYY', 'D MMM YYYY', 'DD MMM YYYY', 'YYYY-MM-DD'], true);
    if (!parsed.isValid()) return '';
    return parsed.format('DD-MM-YYYY');
}

function applyReturArmadaFilter() {
    dates = [];
    var start = normalizeDateValue($('#start_date').val());
    var end = normalizeDateValue($('#end_date').val());
    if (start && !end) end = start;
    if (!start && end) start = end;
    $('#start_date').val(start);
    $('#end_date').val(end);
    dates.push(start);
    dates.push(end);
    refreshReturArmada();
}

$(document).on('click', '.btn-clear', function () {
    dates = null;
    $('#start_date').val('');
    $('#end_date').val('');
    $('#product_id').empty();
    refreshReturArmada();
});

$(document).on('change', '#product_id', function () {
    applyReturArmadaFilter();
});

$(document).on('change change.datetimepicker dp.change', '#start_date, #end_date', function () {
    applyReturArmadaFilter();
});

$(document).on('click', '.btn-export-pdf', function () {
    var start = normalizeDateValue($('#start_date').val());
    var end = normalizeDateValue($('#end_date').val());
    if (start && !end) end = start;
    if (!start && end) start = end;

    var params = {
        date: [start, end],
        product_variant_id: $('#product_id').val(),
    };
    window.open('/generateReportReturProdukArmadaPdf?' + $.param(params), '_blank');
});

$('#tableReportReturArmada tbody').on('click', 'td.details-control .btn-toggle-detail', function (e) {
    e.preventDefault();
    e.stopPropagation();
    var tr = $(this).closest('tr');
    var row = table.row(tr);
    toggleDetailRow(tr, row);
});

$('#tableReportReturArmada tbody').on('click', 'tr', function (e) {
    if ($(e.target).closest('tr.child').length) return;
    var tr = $(this).closest('tr');
    if (tr.hasClass('child')) return;
    var row = table.row(tr);
    if (!row.data()) return;
    toggleDetailRow(tr, row);
});
