(function () {
    var execCharts = { sales: null, production: null, purchase: null };
    var EXEC_BLUE = "#2D60FF";

    function fmtNum(n) {
        if (n === null || n === undefined || isNaN(n)) return "—";
        return Number(n).toLocaleString("id-ID");
    }

    function momBadge(pct) {
        if (pct === null || pct === undefined || isNaN(pct)) {
            return '<span class="text-muted small">MoM —</span>';
        }
        var cls = pct >= 0 ? "trend-up" : "trend-down";
        var sign = pct > 0 ? "+" : "";
        return '<span class="small ' + cls + '">' + sign + pct + "% MoM</span>";
    }

    function escHtml(str) {
        return String(str == null ? "" : str)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
    }

    function renderCrossWidgets(w) {
        var $r = $("#dash_cross_widgets");
        if (!$r.length) return;

        var keys = ["sales", "production", "purchase"];
        var html = "";
        for (var i = 0; i < keys.length; i++) {
            var x = w && w[keys[i]];
            if (!x) continue;
            var rawIcon = x.meta && x.meta.icon ? String(x.meta.icon) : "fe-activity";
            // Font feather.css: wajib .fe + .fe-namaikon (bukan hanya .fe-namaikon)
            var iconClass = ("fe " + rawIcon.replace(/^\s*fe\s+/i, "").trim()).replace(/\s+/g, " ");
            var href = x.href || "#";
            html += '<div class="col-xl-4 col-md-6"><div class="dash-cross-card">';
            html += '<div class="dash-cross-head"><div>';
            html += '<p class="dash-cross-title">' + escHtml(x.title) + "</p>";
            html += '<p class="dash-cross-sub">' + escHtml(x.subtitle) + "</p></div>";
            html += '<div class="dash-cross-icon"><i class="' + escHtml(iconClass) + '"></i></div></div>';
            html += "<div><span class=\"dash-cross-value\">" + fmtNum(x.primary) + "</span>";
            if (x.primary_label) {
                html += ' <span class="text-muted small">' + escHtml(x.primary_label) + "</span>";
            }
            html += "</div>";
            html += '<div class="dash-cross-secondary">' + escHtml(x.secondary) + "</div>";
            html += '<div class="dash-cross-foot">' + momBadge(x.mom_pct);
            html += '<a class="dash-cross-link" href="' + String(href).replace(/"/g, "") + '">Selengkapnya →</a></div>';
            html += "</div></div>";
        }
        $r.html(html || '<div class="col-12 text-muted small">Widget tidak tersedia.</div>');
        if (typeof feather !== "undefined") {
            feather.replace();
        }
    }

    /** Tabel #dash_top_sales_body (kolom kanan blok pemakaian bahan) */
    function renderTopSalesWidget(ts) {
        var $tb = $("#dash_top_sales_body");
        if (!$tb.length) return;
        var rows = (ts && ts.rows) || [];
        var rg = ts && ts.range;
        $("#dash_top_sales_range").text(
            rg && rg.start && rg.end ? rg.start + " → " + rg.end : "—"
        );
        $tb.empty();
        if (!rows.length) {
            $tb.append(
                '<tr><td colspan="3" class="text-center text-muted py-3">Belum ada baris SO di rentang ini.</td></tr>'
            );
            return;
        }
        for (var i = 0; i < rows.length; i++) {
            var r = rows[i];
            var nm = escHtml(r.name || "-");
            var un = escHtml((r.unit && String(r.unit).trim()) || "-");
            $tb.append(
                "<tr><td>" +
                    (i + 1) +
                    '</td><td class="text-truncate" style="max-width: 7rem" title="' +
                    nm +
                    '">' +
                    nm +
                    '</td><td class="text-end text-nowrap">' +
                    '<span class="fw-semibold">' +
                    fmtNum(r.qty) +
                    '</span> <span class="text-muted small">' +
                    un +
                    "</span></td></tr>"
            );
        }
    }

    function destroyExecCharts() {
        ["sales", "production", "purchase"].forEach(function (k) {
            if (execCharts[k]) {
                execCharts[k].destroy();
                execCharts[k] = null;
            }
        });
    }

    function renderOneExecBar(elId, categories, name, data) {
        var el = document.querySelector(elId);
        if (!el || typeof ApexCharts === "undefined") return null;
        var cat = (categories || []).slice();
        var d = (data || []).slice();
        if (cat.length !== d.length) {
            var n = Math.min(cat.length, d.length);
            cat = cat.slice(0, n);
            d = d.slice(0, n);
        }
        var opts = {
            chart: {
                type: "bar",
                height: 300,
                fontFamily: "inherit",
                toolbar: { show: false },
                zoom: { enabled: false },
                animations: { enabled: true },
            },
            series: [{ name: name, data: d }],
            xaxis: {
                categories: cat,
                labels: {
                    rotate: cat.length > 6 ? -45 : 0,
                    hideOverlappingLabels: true,
                    trim: true,
                    maxHeight: 72,
                    style: { fontSize: "11px" },
                },
            },
            yaxis: {
                labels: { formatter: function (v) { return Math.round(v); } },
                min: 0,
                forceNiceScale: true,
            },
            colors: [EXEC_BLUE],
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    columnWidth: "62%",
                    horizontal: false,
                },
            },
            legend: { show: false },
            dataLabels: { enabled: false },
            grid: { strokeDashArray: 4, padding: { top: 8, right: 8, bottom: 0, left: 8 } },
            tooltip: {
                y: { formatter: function (val) { return fmtNum(val); } },
            },
        };
        try {
            var ch = new ApexCharts(el, opts);
            ch.render();
            return ch;
        } catch (e) {
            if (typeof console !== "undefined" && console.warn) {
                console.warn("exec chart " + elId + ":", e);
            }
            el.innerHTML =
                '<p class="small text-danger mb-0 px-1">Grafik gagal dimuat. Muat ulang halaman atau cek konsol (F12).</p>';
            return null;
        }
    }

    function renderExecCharts(c) {
        destroyExecCharts();
        if (!c || typeof ApexCharts === "undefined") return;
        var labels = c.labels || [];
        if (!labels.length) return;
        execCharts.sales = renderOneExecBar("#chartExecSales", labels, "Jumlah SO", c.sales_count || []);
        execCharts.production = renderOneExecBar("#chartExecProduction", labels, "Batch", c.production_count || []);
        execCharts.purchase = renderOneExecBar("#chartExecPurchase", labels, "Jumlah PO", c.purchase_count || []);
    }

    function loadExecutiveWidgets() {
        var $r = $("#dash_cross_widgets");
        if ($r.length) {
            $r.html('<div class="col-12 text-muted small py-2 px-1">Memuat widget…</div>');
        }
        var $tsb = $("#dash_top_sales_body");
        if ($tsb.length) {
            $tsb.html(
                '<tr><td colspan="3" class="text-center text-muted py-3">Memuat…</td></tr>'
            );
            $("#dash_top_sales_range").text("—");
        }
        destroyExecCharts();
        var chartMonths = 6;
        var $sel = $("#exec_chart_months");
        if ($sel.length) {
            var v = parseInt($sel.val(), 10);
            if ([3, 6, 12].indexOf(v) !== -1) {
                chartMonths = v;
            }
        }
        $.ajax({
            url: "/getDashboardExecutiveWidgets",
            method: "get",
            data: { chart_months: chartMonths },
            success: function (data) {
                renderCrossWidgets(data.cross_widgets || {});
                renderTopSalesWidget(data.top_sales || {});
                setTimeout(function () {
                    renderExecCharts(data.exec_charts || {});
                }, 50);
            },
            error: function () {
                if ($r.length) {
                    $r.html('<div class="col-12 text-danger small">Gagal memuat ringkasan penjualan/produksi/pembelian.</div>');
                }
                var $tsb = $("#dash_top_sales_body");
                if ($tsb.length) {
                    $tsb.html(
                        '<tr><td colspan="3" class="text-center text-danger py-3">Gagal memuat top penjualan.</td></tr>'
                    );
                }
            },
        });
    }

    $(document).ready(function () {
        loadExecutiveWidgets();
        $(document).on("change", "#exec_chart_months", function () {
            loadExecutiveWidgets();
        });
        if (window.PemakaianBahanDashboard && typeof window.PemakaianBahanDashboard.init === "function") {
            window.PemakaianBahanDashboard.init();
        }
    });
})();
