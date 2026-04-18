    var table;
    var dates = null;
    autocompleteSupplier("#supplier");
    autocompleteSupplies("#supplies_id");

    $(document).ready(function(){
        inisialisasi();
        var startMonth = moment().startOf('month').format('DD-MM-YYYY');
        var endMonth = moment().endOf('month').format('DD-MM-YYYY');
        $('#start_date').val(startMonth);
        $('#end_date').val(endMonth);
        dates = [startMonth, endMonth];
        refreshProductReturn();
    });

    function inisialisasi() {
        table = $('#tableProduct').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            lengthMenu: [10, 25, 50, 100],
            ordering: true,
            searching: false,
            autoWidth: false,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Cari Retur",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                {
                    data: null,
                    className: "details-control text-center",
                    orderable: false,
                    width: "4%",
                    defaultContent: `<a href="javascript:void(0);" class="btn-action-icon p-1 btn-toggle-detail"><i class="fa fa-plus"></i></a>`
                },
                { data: "item_name", className: "col-item-name", width: "38%" },
                { data: "supplier_summary", className: "col-supplier", width: "26%" },
                { data: "transaction_summary", width: "16%" },
                { data: "qty_summary", width: "16%" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });
    }

    function buildQtyByUnitText(details) {
        var unitMap = {};
        if (!Array.isArray(details)) return "-";
        for (let i = 0; i < details.length; i++) {
            let d = details[i];
            let unit = (d.unit_name || '').toString().trim();
            if (unit === '') unit = 'unit';
            if (!unitMap[unit]) unitMap[unit] = 0;
            unitMap[unit] += parseInt(d.qty || 0);
        }
        var parts = [];
        Object.keys(unitMap).forEach(function (unit) {
            if (unitMap[unit] > 0) parts.push(unitMap[unit] + " " + unit);
        });
        return parts.length ? parts.join(" ") : "-";
    }

    function formatCurrency(value) {
        return new Intl.NumberFormat('id-ID').format(value || 0);
    }

    function formatDetailRow(data) {
        let html = `
            <div class="child-row-wrapper" style="display:none; max-height: 320px; overflow-y: auto; overflow-x: auto;">
            <table class="table table-sm table-bordered mb-0 report-return-child">
                <thead>
                    <tr>
                        <th>Tanggal Retur</th>
                        <th>Referensi PO</th>
                        <th>Supplier</th>
                        <th>Qty</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
        `;

        if (data.details && data.details.length > 0) {
            for (let i = 0; i < data.details.length; i++) {
                let d = data.details[i];
                html += `
                    <tr>
                        <td>${moment(d.rs_date).format('D MMM YYYY')}</td>
                        <td>${d.po_number || '-'}</td>
                        <td>${d.supplier_name || '-'}</td>
                        <td>${d.qty} ${d.unit_name || ''}</td>
                        <td>Rp ${formatCurrency(d.subtotal)}</td>
                    </tr>
                `;
            }
        } else {
            html += `<tr><td colspan="5" class="text-center">Tidak ada detail</td></tr>`;
        }

        html += `</tbody></table></div>`;
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

    function refreshProductReturn() {
        $.ajax({
            url: "/getReportReturn",
            method: "get",
            data: {
                date: dates,
                supplier_id: $('#supplier').val(),
                supplies_id: $('#supplies_id').val()
            },
            beforeSend: function () {
                setReportDataTableLoading('#tableProduct', true);
            },
            complete: function () {
                setReportDataTableLoading('#tableProduct', false);
            },
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                table.clear().draw();
                for (let i = 0; i < e.length; i++) {
                    let supplierMap = {};
                    if (Array.isArray(e[i].details)) {
                        for (let j = 0; j < e[i].details.length; j++) {
                            let supplierName = (e[i].details[j].supplier_name || '').toString().trim();
                            if (supplierName !== "") supplierMap[supplierName] = true;
                        }
                    }
                    let supplierList = Object.keys(supplierMap);
                    e[i].transaction_summary = `${e[i].transaction_count} Transaksi`;
                    e[i].qty_summary = buildQtyByUnitText(e[i].details);
                    e[i].supplier_summary = supplierList.length ? supplierList.join(', ') : '-';
                }
                table.rows.add(e).draw();
                feather.replace();
            },
            error: function (err) {
                console.error("Gagal load retur:", err);
            }
        });
    }

    function normalizeDateValue(value) {
        if (!value) return "";
        var parsed = moment(value, ['DD-MM-YYYY', 'D MMM YYYY', 'DD MMM YYYY', 'YYYY-MM-DD'], true);
        if (!parsed.isValid()) return "";
        return parsed.format('DD-MM-YYYY');
    }

    function applyProductReturnFilter() {
        dates = [];
        var start = normalizeDateValue($('#start_date').val());
        var end = normalizeDateValue($('#end_date').val());
        if (start && !end) end = start;
        if (!start && end) start = end;
        $('#start_date').val(start);
        $('#end_date').val(end);
        dates.push(start);
        dates.push(end);
        refreshProductReturn();
    }

    $(document).on('click', '.btn-filter', function(){
        applyProductReturnFilter();
    });

    $(document).on('click', '.btn-clear', function(){
        dates = null;
        $('#start_date').val("");
        $('#end_date').val("");
        $('#supplier').empty();
        $('#supplies_id').empty();
        refreshProductReturn();
    });

    $(document).on('change', '#supplier', function(){
        applyProductReturnFilter();
    });

    $(document).on('change', '#supplies_id', function(){
        applyProductReturnFilter();
    });

    $(document).on('change change.datetimepicker dp.change', '#start_date, #end_date', function(){
        applyProductReturnFilter();
    });

    $(document).on('click', '.btn-export-pdf', function(){
        var start = normalizeDateValue($('#start_date').val());
        var end = normalizeDateValue($('#end_date').val());
        if (start && !end) end = start;
        if (!start && end) start = end;

        var params = {
            date: [start, end],
            supplier_id: $('#supplier').val(),
            supplies_id: $('#supplies_id').val()
        };
        window.open('/generateReportReturnPdf?' + $.param(params), '_blank');
    });

    $('#tableProduct tbody').on('click', 'td.details-control .btn-toggle-detail', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var tr = $(this).closest('tr');
        var row = table.row(tr);
        toggleDetailRow(tr, row);
    });

    $('#tableProduct tbody').on('click', 'tr', function (e) {
        if ($(e.target).closest('tr.child').length) return;
        var tr = $(this).closest('tr');
        if (tr.hasClass('child')) return;
        var row = table.row(tr);
        if (!row.data()) return;
        toggleDetailRow(tr, row);
    });
