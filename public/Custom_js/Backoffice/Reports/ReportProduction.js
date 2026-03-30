    var mode=1;
    var table;
    let dates = null;
    autocompleteSupplier("#supplier");
    autocompleteProductVariantOnly("#product_id");
    $(document).ready(function(){
        inisialisasi();
        var startMonth = moment().startOf('month').format('DD-MM-YYYY');
        var endMonth = moment().endOf('month').format('DD-MM-YYYY');
        $('#start_date').val(startMonth);
        $('#end_date').val(endMonth);
        dates = [startMonth, endMonth];
        refreshProduction();
    });
    
    
    function inisialisasi() {
        table = $('#tableReportProduction').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            lengthMenu: [10, 25, 50, 100],
            ordering: true,
            searching: false,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Cari Produksi",
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
                    defaultContent: `<a href="javascript:void(0);" class="btn-action-icon p-1 btn-toggle-detail"><i class="fa fa-plus"></i></a>`
                },
                { data: "product_name" },
                { data: "total_summary" },
                { data: "production_summary" },
                { data: "reject_summary" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });
    }

    function getStatusBadge(status) {
        if (status == 1) return `<span class="badge bg-secondary">Pending Approval</span>`;
        if (status == 2) return `<span class="badge bg-success">Selesai</span>`;
        if (status == 3) return `<span class="badge bg-primary">Pending Cancel</span>`;
        if (status == 4) return `<span class="badge bg-danger">Ditolak</span>`;
        return `<span class="badge bg-secondary">-</span>`;
    }

    function buildQtyByUnitText(details, mode) {
        var unitMap = {};
        if (!Array.isArray(details)) return "-";

        for (let i = 0; i < details.length; i++) {
            let d = details[i];
            let status = parseInt(d.status);
            if (mode === "success" && status !== 2) continue;
            if (mode === "reject" && status !== 4) continue;
            if (mode === "all" && !(status === 1 || status === 2 || status === 3 || status === 4)) continue;

            let unitName = (d.unit_name || '').toString().trim();
            if (unitName === '') unitName = 'unit';
            if (!unitMap[unitName]) unitMap[unitName] = 0;
            unitMap[unitName] += parseInt(d.qty || 0);
        }

        var parts = [];
        Object.keys(unitMap).forEach(function (unit) {
            if (unitMap[unit] > 0) parts.push(unitMap[unit] + " " + unit);
        });

        return parts.length > 0 ? parts.join(" ") : "-";
    }

    function formatDetailRow(data) {
        let html = `
            <div class="child-row-wrapper" style="display:none; max-height: 320px; overflow-y: auto; overflow-x: auto;">
            <table class="table table-sm table-bordered mb-0 report-production-child">
                <thead>
                    <tr>
                        <th>Tanggal Produksi</th>
                        <th>Kode Produksi</th>
                        <th>Qty</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
        `;

        if (data.details && data.details.length > 0) {
            for (let i = 0; i < data.details.length; i++) {
                let d = data.details[i];
                html += `
                    <tr>
                        <td>${moment(d.production_date).format('D MMM YYYY')}</td>
                        <td>${d.production_code}</td>
                        <td>${d.qty} ${d.unit_name || ''}</td>
                        <td>${getStatusBadge(d.status)}</td>
                    </tr>
                `;
            }
        } else {
            html += `<tr><td colspan="4" class="text-center">Tidak ada detail</td></tr>`;
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

    function refreshProduction() {
        $.ajax({
            url: "/getReportProduksi",
            method: "get",
            data: {
                date: dates,
                supplier_id: $('#supplier').val(),
                product_variant_id: $('#product_id').val()
            },
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }

                table.clear().draw(); 
                for (let i = 0; i < e.length; i++) {
                    let totalCount = 0;
                    let successCount = 0;
                    let rejectCount = 0;
                    if (Array.isArray(e[i].details)) {
                        totalCount = e[i].details.length;
                        for (let j = 0; j < e[i].details.length; j++) {
                            let s = parseInt(e[i].details[j].status);
                            if (s === 2) successCount += 1;
                            if (s === 4) rejectCount += 1;
                        }
                    }

                    let totalQtyText = buildQtyByUnitText(e[i].details, "all");
                    let successQtyText = buildQtyByUnitText(e[i].details, "success");
                    let rejectQtyText = buildQtyByUnitText(e[i].details, "reject");

                    e[i].total_summary = `${totalCount} Produksi (${totalQtyText})`;
                    e[i].production_summary = `${successCount} Berhasil (${successQtyText})`;
                    e[i].reject_summary = `${e[i].total_reject_count} Ditolak (${rejectQtyText})`;
                    e[i].reject_summary = `${rejectCount} Ditolak (${rejectQtyText})`;
                }

                table.rows.add(e).draw();
                feather.replace(); // Biar icon feather muncul lagi
            },
            error: function (err) {
                console.error("Gagal load produksi:", err);
            }
        });
    }

    function normalizeDateValue(value) {
        if (!value) return "";
        var parsed = moment(value, ['DD-MM-YYYY', 'D MMM YYYY', 'DD MMM YYYY', 'YYYY-MM-DD'], true);
        if (!parsed.isValid()) return "";
        return parsed.format('DD-MM-YYYY');
    }

    function applyProductionFilter() {
        dates = [];
        var start = normalizeDateValue($('#start_date').val());
        var end = normalizeDateValue($('#end_date').val());

        if (start && !end) end = start;
        if (!start && end) start = end;

        $('#start_date').val(start);
        $('#end_date').val(end);

        dates.push(start);
        dates.push(end);
        console.log(dates);
        refreshProduction();
    }

    $(document).on('click', '.btn-filter', function(){
        applyProductionFilter();
    });

    $(document).on('click', '.btn-clear', function(){
        dates = null;
        $('#start_date').val("");
        $('#end_date').val("");
        $('#supplier').empty();
        $('#product_id').empty();
        refreshProduction();
    })

    $(document).on('change', '#supplier', function(){
        applyProductionFilter();
    });

    $(document).on('change', '#product_id', function(){
        applyProductionFilter();
    });

    $(document).on('change change.datetimepicker dp.change', '#start_date, #end_date', function(){
        applyProductionFilter();
    });

    $(document).on('click', '.btn-export-pdf', function(){
        var start = normalizeDateValue($('#start_date').val());
        var end = normalizeDateValue($('#end_date').val());
        if (start && !end) end = start;
        if (!start && end) start = end;

        var params = {
            date: [start, end],
            supplier_id: $('#supplier').val(),
            product_variant_id: $('#product_id').val()
        };

        window.open('/generateReportProduksiPdf?' + $.param(params), '_blank');
    });

    $('#tableReportProduction tbody').on('click', 'td.details-control .btn-toggle-detail', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var tr = $(this).closest('tr');
        var row = table.row(tr);
        toggleDetailRow(tr, row);
    });

    $('#tableReportProduction tbody').on('click', 'tr', function (e) {
        if ($(e.target).closest('tr.child').length) return;
        var tr = $(this).closest('tr');
        if (tr.hasClass('child')) return;
        var row = table.row(tr);
        if (!row.data()) return;
        toggleDetailRow(tr, row);
    });