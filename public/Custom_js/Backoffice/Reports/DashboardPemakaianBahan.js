(function () {
    var chartMain = null;

    function fmtNum(n) {
        if (n === null || n === undefined || isNaN(n)) return "—";
        return Number(n).toLocaleString("id-ID", {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
        });
    }

    /** Stok / selisih: tampil bilangan bulat (hindari kesan desimal salah baca) */
    function fmtNumWhole(n) {
        if (n === null || n === undefined || isNaN(n)) return "—";
        return Math.round(Number(n)).toLocaleString("id-ID", {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        });
    }

    function escHtml(str) {
        return String(str == null ? "" : str)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
    }

    function qtyWithUnit(val, unit, formatFn) {
        var fmt = formatFn || fmtNum;
        var u = escHtml((unit && String(unit).trim()) || "-");
        return (
            '<td class="text-end text-nowrap"><span class="fw-semibold">' +
            fmt(val) +
            '</span> <span class="text-muted small">' +
            u +
            "</span></td>"
        );
    }

    function trendHtml(pct) {
        if (pct === null || pct === undefined || isNaN(pct)) return '<span class="text-muted">MoM: —</span>';
        var cls = pct >= 0 ? "trend-up" : "trend-down";
        var sign = pct > 0 ? "+" : "";
        return '<span class="' + cls + '">MoM: ' + sign + pct + "%</span>";
    }

    function destroyChart() {
        if (chartMain) {
            chartMain.destroy();
            chartMain = null;
        }
    }

    function renderChart(payload) {
        destroyChart();
        var el = document.querySelector("#chartPemakaianBahan");
        if (!el || typeof ApexCharts === "undefined") return;

        var labels = (payload.series && payload.series.labels) || [];
        var net = (payload.series && payload.series.net_qty) || [];
        var txn = (payload.series && payload.series.txn_count) || [];

        var catLabels = labels.slice();
        var netData = net.slice();
        var txnData = txn.slice();

        var options = {
            chart: {
                height: 360,
                type: "line",
                toolbar: { show: true },
                fontFamily: "inherit",
                zoom: { enabled: false },
            },
            stroke: {
                width: [0, 3],
                curve: "smooth",
            },
            series: [
                { name: "Qty net (log)", type: "column", data: netData },
                { name: "Jumlah transaksi", type: "line", data: txnData },
            ],
            xaxis: {
                categories: catLabels,
                labels: { rotate: -35, hideOverlappingLabels: true },
            },
            yaxis: [
                {
                    seriesName: "Qty net (log)",
                    title: { text: "Qty net" },
                    labels: { formatter: function (v) { return Math.round(v); } },
                },
                {
                    seriesName: "Jumlah transaksi",
                    opposite: true,
                    title: { text: "Transaksi" },
                    min: 0,
                    labels: { formatter: function (v) { return Math.round(v); } },
                },
            ],
            colors: ["#2D60FF", "#0ea5e9"],
            plotOptions: {
                bar: {
                    columnWidth: "52%",
                    borderRadius: 8,
                },
            },
            dataLabels: { enabled: false },
            legend: { position: "top", horizontalAlign: "right" },
            markers: { size: [0, 5] },
            tooltip: {
                shared: true,
                intersect: false,
                y: [
                    { formatter: function (val) { return fmtNum(val); } },
                    { formatter: function (val) { return fmtNum(val) + " trx"; } },
                ],
            },
        };

        try {
            chartMain = new ApexCharts(el, options);
            chartMain.render();
        } catch (e) {
            if (typeof console !== "undefined" && console.warn) {
                console.warn("chartPemakaianBahan:", e);
            }
        }
    }

    function fillKpis(payload) {
        var k = payload.kpis || {};
        $("#kpi_this_net").text(fmtNum(k.this_month_net));
        $("#kpi_mom_net").html(trendHtml(k.mom_net_pct));
        $("#kpi_this_txn").text(fmtNum(k.this_month_txn));
        $("#kpi_mom_txn").html(trendHtml(k.mom_txn_pct));

        $("#kpi_low_stock").text(fmtNum(payload.low_stock_count));
        $("#dash_range_label").text(
            (payload.range && payload.range.start && payload.range.end)
                ? payload.range.start + " → " + payload.range.end
                : ""
        );
        $("#dash_disclaimer").text(payload.disclaimer || "");
    }

    function fillTopOneTable(tbodySelector, rows) {
        var $tb = $(tbodySelector);
        if (!$tb.length) return;
        $tb.empty();
        if (!rows || !rows.length) {
            $tb.append('<tr><td colspan="3" class="text-center text-muted py-3">Tidak ada data</td></tr>');
            return;
        }
        for (var i = 0; i < rows.length; i++) {
            var r = rows[i];
            var nm = escHtml(r.name || "-");
            $tb.append(
                "<tr><td>" +
                    (i + 1) +
                    "</td><td>" +
                    nm +
                    "</td>" +
                    qtyWithUnit(r.net_qty, r.unit) +
                    "</tr>"
            );
        }
    }

    function fillTopTables(payload) {
        fillTopOneTable("#dash_top_body_kemasan", payload.top_materials_kemasan || []);
        fillTopOneTable("#dash_top_body_bahan", payload.top_materials_bahan || []);
    }

    function fillProcurementRows(tbodySelector, rows) {
        var $tb = $(tbodySelector);
        if (!$tb.length) return;
        $tb.empty();
        if (!rows || !rows.length) {
            $tb.append(
                '<tr><td colspan="7" class="text-center text-muted py-3">Tidak ada data di rentang ini.</td></tr>'
            );
            return;
        }
        for (var i = 0; i < rows.length; i++) {
            var r = rows[i];
            var nm = escHtml(r.name || "-");
            $tb.append(
                "<tr><td>" +
                    (i + 1) +
                    "</td><td>" +
                    nm +
                    "</td>" +
                    qtyWithUnit(r.window_total, r.unit) +
                    qtyWithUnit(r.avg_per_month, r.unit) +
                    qtyWithUnit(r.estimate_next_month, r.unit) +
                    qtyWithUnit(r.stock_agg, r.unit, fmtNumWhole) +
                    qtyWithUnit(r.gap_to_buy, r.unit, fmtNumWhole) +
                    "</tr>"
            );
        }
    }

    function fillProcurementTables(payload) {
        if (!$("#dash_procurement_body_kemasan").length) return;
        $("#dash_procurement_next_badge").text(payload.next_month_label || "—");
        $("#dash_procurement_disclaimer").text(payload.disclaimer || "");
        fillProcurementRows("#dash_procurement_body_kemasan", payload.rows_kemasan || []);
        fillProcurementRows("#dash_procurement_body_bahan", payload.rows_bahan || []);
    }

    function loadProcurementEstimate() {
        if (!$("#dash_procurement_body_kemasan").length) return;
        $.ajax({
            url: "/getDashboardProcurementEstimate",
            method: "get",
            data: {
                months: $("#dash_months").val(),
                top: 12,
            },
            success: function (payload) {
                fillProcurementTables(payload);
            },
            error: function () {
                var errFull =
                    '<tr><td colspan="7" class="text-center text-danger py-3">Gagal memuat estimasi pembelian.</td></tr>';
                var $a = $("#dash_procurement_body_kemasan");
                var $b = $("#dash_procurement_body_bahan");
                if ($a.length) $a.html(errFull);
                if ($b.length) $b.html(errFull);
            },
        });
    }

    function loadDashboard() {
        $.ajax({
            url: "/getDashboardPemakaianBahan",
            method: "get",
            data: {
                months: $("#dash_months").val(),
            },
            beforeSend: function () {
                var loading =
                    '<tr><td colspan="3" class="text-center text-muted py-3">Memuat…</td></tr>';
                $("#dash_top_body_kemasan").html(loading);
                $("#dash_top_body_bahan").html(loading);
                if ($("#dash_procurement_body_kemasan").length) {
                    var pl =
                        '<tr><td colspan="7" class="text-center text-muted py-3">Memuat…</td></tr>';
                    $("#dash_procurement_body_kemasan").html(pl);
                    $("#dash_procurement_body_bahan").html(pl);
                }
            },
            success: function (payload) {
                fillKpis(payload);
                fillTopTables(payload);
                renderChart(payload);
                loadProcurementEstimate();
            },
            error: function () {
                var err =
                    '<tr><td colspan="3" class="text-center text-danger py-3">Gagal memuat data</td></tr>';
                $("#dash_top_body_kemasan").html(err);
                $("#dash_top_body_bahan").html(err);
            },
        });
    }

    function initPemakaianBahanModule() {
        if (!$("#dash_months").length) {
            return;
        }
        $("#dash_apply").off("click").on("click", function () {
            loadDashboard();
        });
        $("#dash_months").off("change").on("change", loadDashboard);
        loadDashboard();
    }

    window.PemakaianBahanDashboard = {
        init: initPemakaianBahanModule,
    };
})();
