BadgeClass(kind) {
        var normalized = normalizeAgingKind(kind);
        if (normalized === "bahan") return "dash-kind-badge dash-kind-badge-bahan";
        if (normalized === "product") return "dash-kind-badge dash-kind-badge-product";
        return "dash-kind-badge dash-kind-badge-other";
    }

    function renderAgingDetailTable() {
        var $bod = $("#dash_aging_detail_body");
        $bod.empty();
        var items = getFilteredAgingDetailItems();
        if (!items.length) {
            $bod.append(
                '<tr><td colspan="5" class="text-center text-muted">Tidak ada item sesuai filter.</td></tr>'
            );
        } else {
            for (var i = 0; i < items.length; i++) {
                var it = items[i];
                var u = it.unit ? " " + escHtml(it.unit) : "";
                var kindText = escHtml(it.kind || "-");
                $bod.append(
                    '<tr><td class="dash-aging-kind-cell"><span class="' +
                        getAgingKindBadgeClass(it.kind) +
                        '">' +
                        kindText +
                        '</span></td><td class="dash-aging-item-cell" title="' +
                        escHtml(it.name || "") +
                        '">' +
                        escHtml(it.name || "-") +
                        '</td><td class="dash-aging-qty-cell text-end text-nowrap">' +
                        fmtNum(it.qty) +
                        u +
                        '</td><td class="dash-aging-value-cell text-end">' +
                        fmtRp(it.value) +
                        '</td><td class="dash-aging-age-cell text-end">' +
                        fmtNum(it.age_days) +
                        "</td></tr>"
                );
            }
        }
        $("#dash_aging_detail_count").text(fmtNum(items.length) + " item");
    }

    function openAgingDetailModal(bucket, statusLabel) {
        agingDetailItemsCurrent = dashAgingDetailByBucket[bucket] || [];
        agingDetailKindFilter = "all";
        agingDetailNameFilter = "";
        $("#dashAgingDetailTitle").text("Detail stok · " + bucket + " · " + (statusLabel || ""));
        $("#dash_aging_kind_filter").val("all");
        $("#dash_aging_name_filter").val("");
        renderAgingDetailTable();
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
                    '<button type="button" class="btn btn-sm btn-outline-secondary dash-aging-detail-btn" data-aging-bucket="' +
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
            var section =
                tbodySelector === "#dash_confirmation_body"
                    ? "confirmation"
                    : tbodySelector === "#dash_revision_body"
                      ? "revision"
                      : "changelog";
            var queueKey = String(r.queue_key || "");
            $tb.append(
                "<tr><td>" +
                    escHtml(r.module_label || "-") +
                    "</td><td>" +
                    escHtml(r.reference || "-") +
                    '</td><td><span class="d-inline-block" title="' +
                    escHtml(r.what_changed || r.summary || "") +
                    '">' +
                    escHtml(r.what_changed || r.summary || "-") +
                    '</span></td><td class="dash-col-actions">' +
                    '<div class="d-inline-flex gap-1 align-items-center">' +
                    '<a class="btn btn-sm dash-log-btn px-2" title="Buka" href="' +
                    url.replace(/"/g, "&quot;") +
                    '"><i class="fe fe-eye"></i></a>' +
                    '<button type="button" class="btn btn-sm dash-log-btn px-2 dash-queue-dismiss-btn" title="Hapus dari log" data-queue-section="' +
                    escHtml(section) +
                    '" data-queue-key="' +
                    escHtml(queueKey) +
                    '"><i class="fe fe-trash-2"></i></button>' +
                    "</div></td></tr>"
            );
        }
    }

    function dismissQueueItem(section, key, done) {
        $.ajax({
            url: "/dismissDashboardQueueItem",
            method: "post",
            data: {
                section: section,
                key: key,
                _token: token,
            },
            headers: {
                "X-CSRF-TOKEN": token,
            },
            success: function () {
                if (typeof done === "function") done(true);
            },
            error: function () {
                if (typeof done === "function") done(false);
            },
        });
    }

    function buildModuleKey(row) {
        var v = String((row && (row.module_label || row.kind)) || "");
        return v.trim().toLowerCase();
    }

    function getModuleFilterOptions(rows) {
        var map = {};
        var out = [];
        rows = rows || [];
        for (var i = 0; i < rows.length; i++) {
            var r = rows[i] || {};
            var key = buildModuleKey(r);
            if (!key || map[key]) continue;
            map[key] = 1;
            out.push({
                value: key,
                label: String(r.module_label || r.kind || "Lainnya"),
            });
        }
        out.sort(function (a, b) {
            return a.label.localeCompare(b.label, "id");
        });
        return out;
    }

    function renderModuleFilter(selectSelector, rows, selectedValue) {
        var $el = $(selectSelector);
        if (!$el.length) return;
        var options = getModuleFilterOptions(rows);
        $el.empty();
        $el.append('<option value="all">Semua modul</option>');
        for (var i = 0; i < options.length; i++) {
            $el.append(
                '<option value="' +
                    escHtml(options[i].value) +
                    '">' +
                    escHtml(options[i].label) +
                    "</option>"
            );
        }
        $el.val(selectedValue || "all");
    }

    function filterRowsByModule(rows, moduleValue) {
        rows = rows || [];
        if (!moduleValue || moduleValue === "all") return rows;
        return rows.filter(function (r) {
            return buildModuleKey(r) === moduleValue;
        });
    }

    function tryBrowserNotifyBahan(pack) {
        if (!pack || !("Notification" in window)) return;
        if (Notification.permission !== "granted") return;
        var c = pack.count_critical || 0;
        var w = pack.count_warn || 0;
        if (c + w === 0) return;
        var nKey = "dash_bahan_notify_" + c + "_" + w;
        try {
            if (sessionStorage.getItem(nKey)) return;
            sessionStorage.setItem(nKey, "1");
        } catch (e2) {}
        try {
            new Notification("Stock bahan mentah", {
                body: c + " habis, " + w + " mendekati batas. Buka dashboard untuk detail.",
            });
        } catch (err) {}
    }

    function maybeDashBahanToast(pack) {
        if (!pack) return;
        var c = pack.count_critical || 0;
        var w = pack.count_warn || 0;
        if (c + w === 0) return;
        var toastKey = "dash_bahan_toast_" + c + "_" + w;
        try {
            if (sessionStorage.getItem(toastKey)) return;
            sessionStorage.setItem(toastKey, "1");
        } catch (e) {}
        var el = document.getElementById("dashBahanToast");
        if (!el || !window.bootstrap || !bootstrap.Toast) return;
        var body =
            c > 0
                ? c + " bahan habis — order segera. " + w + " mendekati batas."
                : w + " bahan mendekati batas minimal — pertimbangkan PO.";
        $("#dashBahanToastBody").text(body);
        var t = bootstrap.Toast.getOrCreateInstance(el, { delay: 7000 });
        t.show();
    }

    function getBahanRowsForTable() {
        var pack = lastBahanPack;
        var rows = (pack && pack.rows) || [];
        if (bahanTableFilter === "critical") {
            return rows.filter(function (r) {
                return r.level === "critical";
            });
        }
        if (bahanTableFilter === "warn") {
            return rows.filter(function (r) {
                return r.level === "warn";
            });
        }
        return rows;
    }

    function updateBahanFilterBadgesActive() {
        $("#dash_bahan_badge_crit").toggleClass("dash-bahan-badge-active", bahanTableFilter === "critical");
        $("#dash_bahan_badge_warn").toggleClass("dash-bahan-badge-active", bahanTableFilter === "warn");
    }

    function updateBahanFilterHint() {
        var $h = $("#dash_bahan_filter_hint");
        if (!$h.length) return;
        if (bahanTableFilter === "all") {
            $h.text(
                "Klik badge merah/kuning untuk menyaring tabel. Klik badge yang sama lagi untuk tampilkan semua."
            );
        } else if (bahanTableFilter === "critical") {
            $h.text("Menampilkan hanya bahan habis. Klik badge merah lagi untuk kembali ke semua.");
        } else {
            $h.text("Menampilkan hanya bahan mendekati batas. Klik badge kuning lagi untuk kembali ke semua.");
        }
    }

    function renderBahanTableBody() {
        var $tb = $("#dash_bahan_alert_body");
 