(function () {
    var mainChart = null;
    var dashAgingDetailByBucket = {};

    function fmtNum(n) {
        if (n === null || n === undefined || isNaN(n)) return "0";
        return Number(n).toLocaleString("id-ID");
    }

    function fmtRp(n) {
        if (n === null || n === undefined || isNaN(n)) return "Rp 0";
        return "Rp " + Number(n).toLocaleString("id-ID");
    }

    function escHtml(str) {
        return String(str == null ? "" : str)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
    }

    function renderTopRows(selector, rows) {
        var $tb = $(selector);
        if (!$tb.length) return;
        $tb.empty();
        if (!rows || !rows.length) {
            $tb.append('<tr><td colspan="3" class="text-center text-muted">Tidak ada data</td></tr>');
            return;
        }
        for (var i = 0; i < rows.length; i++) {
            var r = rows[i];
            $tb.append(
                '<tr><td class="dash-col-rank">' +
                    (i + 1) +
                    '</td><td class="dash-col-prod" title="' +
                    escHtml(r.name || "-") +
                    '">' +
                    escHtml(r.name || "-") +
                    '</td><td class="dash-col-qty">' +
                    fmtNum(r.qty) +
                    "</td></tr>"
            );
        }
    }

    function openAgingDetailModal(bucket, statusLabel) {
        var items = dashAgingDetailByBucket[bucket] || [];
        $("#dashAgingDetailTitle").text("Detail stok · " + bucket + " · " + (statusLabel || ""));
        var $bod = $("#dash_aging_detail_body");
        $bod.empty();
        if (!items.length) {
            $bod.append(
                '<tr><td colspan="5" class="text-center text-muted">Tidak ada item di kelompok ini.</td></tr>'
            );
        } else {
            for (var i = 0; i < items.length; i++) {
                var it = items[i];
                var u = it.unit ? " " + escHtml(it.unit) : "";
                $bod.append(
                    "<tr><td>" +
                        escHtml(it.kind || "-") +
                        '</td><td title="' +
                        escHtml(it.name || "") +
                        '">' +
                        escHtml(it.name || "-") +
                        '</td><td class="text-end text-nowrap">' +
                        fmtNum(it.qty) +
                        u +
                        '</td><td class="text-end">' +
                        fmtRp(it.value) +
                        '</td><td class="text-end">' +
                        fmtNum(it.age_days) +
                        "</td></tr>"
                );
            }
        }
        var el = document.getElementById("dashAgingDetailModal");
        if (el && window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(el).show();
        }
    }

    function renderAging(rows) {
        var $tb = $("#dash_stock_aging_body");
        if (!$tb.length) return;
        $tb.empty();
        if (!rows || !rows.length) {
            $tb.append('<tr><td colspan="5" class="text-center text-muted">Tidak ada data</td></tr>');
            return;
        }
        for (var i = 0; i < rows.length; i++) {
            var r = rows[i];
            var bkt = String(r.bucket || "");
            var st = String(r.status || "");
            var bEsc = bkt.replace(/"/g, "&quot;");
            var sEsc = st.replace(/"/g, "&quot;");
            $tb.append(
                "<tr><td>" +
                    escHtml(r.bucket) +
                    "</td><td>" +
                    escHtml(r.status) +
                    '</td><td class="text-end">' +
                    fmtNum(r.qty) +
                    '</td><td class="text-end">' +
                    fmtRp(r.value) +
                    '</td><td class="dash-aging-actions">' +
                    '<button type="button" class="btn btn-sm btn-outline-primary py-0 dash-aging-detail-btn" data-aging-bucket="' +
                    bEsc +
                    '" data-aging-status="' +
                    sEsc +
                    '">Lihat</button></td></tr>'
            );
        }
    }

    function renderApprovalTable(tbodySelector, rows, emptyMsg) {
        var $tb = $(tbodySelector);
        if (!$tb.length) return;
        $tb.empty();
        if (!rows || !rows.length) {
            $tb.append(
                '<tr><td colspan="4" class="text-center text-muted">' +
                    escHtml(emptyMsg) +
                    "</td></tr>"
            );
            return;
        }
        for (var i = 0; i < rows.length; i++) {
            var r = rows[i];
            var url = r.url ? String(r.url) : "#";
            var label = escHtml(r.url_label || "Buka");
            $tb.append(
                "<tr><td>" +
                    escHtml(r.module_label || "-") +
                    "</td><td>" +
                    escHtml(r.reference || "-") +
                    '</td><td><span class="d-inline-block" title="' +
                    escHtml(r.what_changed || r.summary || "") +
                    '">' +
                    escHtml(r.what_changed || r.summary || "-") +
                    '</span></td><td class="dash-col-actions"><a class="btn btn-sm btn-outline-primary py-1" href="' +
                    url.replace(/"/g, "&quot;") +
                    '">' +
                    label +
                    "</a></td></tr>"
            );
        }
    }

    function renderRecommendedRows(rows) {
        var $tb = $("#dash_recommended_body");
        if (!$tb.length) return;
        $tb.empty();
        if (!rows || !rows.length) {
            $tb.append(
                '<tr><td colspan="3" class="text-center text-muted">Belum ada rekomendasi produksi.</td></tr>'
            );
            return;
        }
        for (var i = 0; i < rows.length; i++) {
            var r = rows[i];
            $tb.append(
                '<tr><td class="dash-col-rank">' +
                    (i + 1) +
                    '</td><td class="dash-col-prod" title="' +
                    escHtml(r.name || "-") +
                    '">' +
                    escHtml(r.name || "-") +
                    '</td><td class="dash-col-qty">' +
                    fmtNum(r.recommend_qty) +
                    "</td></tr>"
            );
        }
    }

    function renderList(selector, rows, mapFn, emptyText) {
        var $ul = $(selector);
        if (!$ul.length) return;
        $ul.empty();
        if (!rows || !rows.length) {
            $ul.append('<li class="text-muted">' + escHtml(emptyText) + "</li>");
            return;
        }
        for (var i = 0; i < rows.length; i++) {
            $ul.append("<li>" + mapFn(rows[i]) + "</li>");
        }
    }

    function renderChart(payload) {
        var el = document.querySelector("#dash_main_chart");
        if (!el || typeof ApexCharts === "undefined") return;
        if (mainChart) {
            mainChart.destroy();
            mainChart = null;
        }
        var c = payload.chart || {};
        var growth = c.sales_growth_pct_by_bucket || [];
        var f = payload.filter || {};
        var cap =
            "Periode: " +
            (f.label || "-") +
            ". Batang: qty pengiriman & retur; garis: % pertumbuhan qty antar potongan waktu.";
        $("#dash_chart_caption").text(cap);

        var opts = {
            chart: { type: "line", height: 320, toolbar: { show: false }, zoom: { enabled: false } },
            series: [
                { name: "Pengiriman (qty)", type: "column", data: c.sales_qty || [] },
                { name: "Retur armada (qty)", type: "column", data: c.return_qty || [] },
                {
                    name: "Growth % vs potongan sebelumnya",
                    type: "line",
                    data: growth,
                    yAxisIndex: 1,
                },
            ],
            xaxis: { categories: c.labels || [] },
            stroke: { curve: "smooth", width: [0, 0, 3] },
            plotOptions: { bar: { columnWidth: "58%", borderRadius: 3 } },
            colors: ["#2563eb", "#f97316", "#0f766e"],
            dataLabels: { enabled: false },
            grid: { strokeDashArray: 4 },
            legend: { position: "top", horizontalAlign: "right", fontSize: "11px" },
            yaxis: [
                {
                    title: { text: "Qty" },
                    labels: {
                        formatter: function (v) {
                            return fmtNum(v);
                        },
                    },
                },
                {
                    opposite: true,
                    title: { text: "Growth %" },
                    labels: {
                        formatter: function (v) {
                            if (v === null || v === undefined || isNaN(v)) return "";
                            return fmtNum(v) + "%";
                        },
                    },
                },
            ],
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function (val, opts) {
                        if (opts.seriesIndex === 2) {
                            if (val === null || val === undefined || isNaN(val)) return "—";
                            return fmtNum(val) + "%";
                        }
                        return fmtNum(val);
                    },
                },
            },
        };
        mainChart = new ApexCharts(el, opts);
        mainChart.render();
    }

    function loadDashboard() {
        var period = $("#dash_filter_period").val() || "month";
        $.ajax({
            url: "/getDashboardOverview",
            method: "get",
            data: { period: period },
            success: function (data) {
                var f = data.filter || {};
                $("#dash_filter_label").text(
                    (f.period_label ? f.period_label + ": " : "") + (f.label || "-")
                );
                var hintBase =
                    "Pilih Minggu, Bulan, atau Tahun (periode berjalan menurut tanggal hari ini), lalu Terapkan.";
                $("#dash_filter_hint").text(f.hint || hintBase);
                $("#dash_top_yearly_sub").text(f.top_yearly_caption || "");
                $("#dash_top_accum_sub").text(f.top_accum_caption || "");
                var ch = data.changelog || {};
                $("#kpi_changelog").text(fmtNum(ch.changelog_pending));
                $("#kpi_confirmation").text(fmtNum(ch.confirmation_log));
                $("#kpi_revision").text(fmtNum(ch.revision_log));
                renderApprovalTable(
                    "#dash_changelog_body",
                    ch.changelog_items || [],
                    "Tidak ada changelog di periode ini."
                );
                renderApprovalTable(
                    "#dash_confirmation_body",
                    ch.confirmation_items || [],
                    "Tidak ada item yang perlu konfirmasi."
                );
                renderApprovalTable(
                    "#dash_revision_body",
                    ch.revision_items || [],
                    "Tidak ada revisi di periode ini."
                );
                $("#kpi_inventory_value").text(fmtRp(data.inventory_value && data.inventory_value.total));
                $("#kpi_inventory_split").text(
                    "Produk " +
                        fmtRp(data.inventory_value && data.inventory_value.product) +
                        " · Bahan " +
                        fmtRp(data.inventory_value && data.inventory_value.bahan)
                );
                var k = data.kpi || {};
                var sg = k.sales_growth_pct;
                if (sg === null || sg === undefined || isNaN(sg)) {
                    $("#kpi_sales_growth").text("—");
                } else {
                    $("#kpi_sales_growth").text(fmtNum(sg) + "%");
                }
                $("#kpi_sales_growth_sub").text(
                    "Qty periode ini " +
                        fmtNum(k.sales_qty_current) +
                        " vs sebelumnya " +
                        fmtNum(k.sales_qty_previous) +
                        " · " +
                        (f.label || "")
                );
                $("#kpi_turnover").text(fmtNum(k.inventory_turnover));
                $("#kpi_turnover_sub").text(
                    "Keluar stok (log) vs stok sekarang, annualized · " + (f.label || "")
                );
                var dio = k.dio_days;
                $("#kpi_dio").text(
                    dio === null || dio === undefined || isNaN(dio) ? "—" : fmtNum(dio) + " hari"
                );
                $("#kpi_dio_sub").text("Dari turnover · " + (f.label || ""));
                $("#kpi_return_rate").text(fmtNum(k.return_rate_product_pct || 0) + "%");
                $("#kpi_return_split").text(
                    "Barang jadi " +
                        fmtNum(k.return_rate_product_pct || 0) +
                        "% (retur÷pengiriman) · Bahan " +
                        fmtNum(k.return_rate_bahan_pct || 0) +
                        "% (retur÷PO) · " +
                        (f.label || "")
                );

                renderTopRows("#dash_top_yearly", (data.top_products && data.top_products.yearly) || []);
                renderTopRows("#dash_top_accum", (data.top_products && data.top_products.accumulative) || []);
                dashAgingDetailByBucket = data.stock_aging_detail || {};
                renderAging(data.stock_aging || []);
                renderList(
                    "#dash_overstock_list",
                    (data.warnings && data.warnings.overstock_alerts) || [],
                    function (r) {
                        return (
                            '<span title="' +
                            escHtml(r.name || "-") +
                            '">' +
                            escHtml(r.name || "-") +
                            "</span>" +
                            " - umur " +
                            fmtNum(r.age_days) +
                            " hari - qty " +
                            fmtNum(r.qty)
                        );
                    },
                    "Tidak ada overstock."
                );
                var w = data.warnings || {};
                $("#dash_recommended_note").text(w.recommended_note || "");
                renderRecommendedRows(w.recommended_production || []);
                renderChart(data);
            },
            error: function () {
                $("#dash_filter_label").text("Gagal memuat dashboard.");
            },
        });
    }

    $(document).ready(function () {
        loadDashboard();
        $("#dash_refresh_btn").on("click", loadDashboard);
        $("#dash_filter_period").on("change", loadDashboard);
        $(document).on("click", ".dash-aging-detail-btn", function () {
            var $btn = $(this);
            openAgingDetailModal(
                String($btn.attr("data-aging-bucket") || ""),
                String($btn.attr("data-aging-status") || "")
            );
        });
    });
})();
