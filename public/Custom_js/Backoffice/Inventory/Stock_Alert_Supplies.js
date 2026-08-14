    var mode = 1;
    var tableLow, tableOut;
    var stockAlertSuppliesXhr = null;

    $(document).ready(function () {
        var whName = typeof getActiveWarehouseName === "function" ? getActiveWarehouseName() : null;
        $("#stock-alert-supplies-wh-label").text(
            whName ? ("Gudang aktif: " + whName) : "Pilih gudang aktif di header terlebih dahulu"
        );
        inisialisasi();
        refreshStockAlert();

        $('a[data-bs-toggle="tab"][href="#low"], a[data-bs-toggle="tab"][href="#out"]')
            .off("shown.bs.tab.stockAlertSupplies")
            .on("shown.bs.tab.stockAlertSupplies", function (event) {
                var activeTable = $(event.target).attr("href") === "#out" ? tableOut : tableLow;
                if (activeTable) {
                    activeTable.columns.adjust().draw(false);
                }
            });
    });

    function stockAlertSuppliesWraps() {
        return $("#tableStockAlertLow-wrap, #tableStockAlertOut-wrap");
    }

    function setStockAlertSuppliesTableLoading(isLoading) {
        var $wraps = stockAlertSuppliesWraps();
        if (!$wraps.length) return;
        $wraps.toggleClass("is-loading", !!isLoading);
    }

    function showStockAlertSuppliesSkeleton() {
        stockAlertSuppliesWraps().removeClass("dt-ready").addClass("dt-pending");
        setStockAlertSuppliesTableLoading(true);
    }

    function hideStockAlertSuppliesSkeleton() {
        setStockAlertSuppliesTableLoading(false);
        stockAlertSuppliesWraps().removeClass("dt-pending").addClass("dt-ready");
    }

    function inisialisasi() {
        tableLow = initStockAlertSuppliesTable("#tableStockAlertLow", "Cari Stok (Rendah)");
        tableOut = initStockAlertSuppliesTable("#tableStockAlertOut", "Cari Stok (Habis)");
        tableLow.columns.adjust();
    }

    function initStockAlertSuppliesTable(selector, searchPlaceholder) {
        if ($.fn.DataTable.isDataTable(selector)) {
            return $(selector).DataTable();
        }

        return $(selector).DataTable({
            processing: true,
            bFilter: true,
            sDom: "fBtlpi",
            lengthMenu: [10, 25, 50, 100],
            ordering: true,
            autoWidth: false,
            // scrollX bikin header/body desync; horizontal scroll via .table-responsive
            scrollX: false,
            language: {
                search: " ",
                sLengthMenu: "_MENU_",
                searchPlaceholder: searchPlaceholder,
                info: "_START_ - _END_ of _TOTAL_ items",
                emptyTable: "Tidak ada data stok",
                zeroRecords: "Data stok tidak ditemukan",
                processing:
                    '<div><span class="spinner-border spinner-border-sm text-primary" role="status"></span><span>Memuat peringatan stok...</span></div>',
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> ',
                },
            },
            // Order MUST match thead:
            // Nama Bahan Mentah | Stok Saat Ini | Peringatan Stok | Pemesanan Min. | Stok Minimum Rekomendasi
            columns: [
                { data: "supplies_name", width: "24%", defaultContent: "—" },
                { data: "current_stock_text", width: "14%", defaultContent: "—" },
                { data: "master_alert_text", width: "16%", defaultContent: "—" },
                { data: "min_order_text", width: "22%", defaultContent: "—" },
                { data: "supplies_alert_text", width: "24%", defaultContent: "—" },
            ],
            initComplete: function (settings) {
                prepareStockAlertSuppliesFilter(settings);
            },
            drawCallback: function () {
                setStockAlertSuppliesTableLoading(false);
                if (typeof feather !== "undefined") feather.replace();
            },
        });
    }

    function prepareStockAlertSuppliesFilter(settings) {
        var filter = $(settings.nTableWrapper).find(".dataTables_filter");
        var label = filter.find("label");
        if (!label.children(".fa-search").length) {
            label.prepend('<i class="fa fa-search"></i> ');
        }
    }

    function refreshStockAlert() {
        var warehouseId = typeof getActiveWarehouseId === "function" ? getActiveWarehouseId() : null;
        if (!warehouseId) {
            if (tableLow) tableLow.clear().draw();
            if (tableOut) tableOut.clear().draw();
            hideStockAlertSuppliesSkeleton();
            return;
        }

        if (stockAlertSuppliesXhr && stockAlertSuppliesXhr.readyState !== 4) {
            stockAlertSuppliesXhr.abort();
        }

        showStockAlertSuppliesSkeleton();

        stockAlertSuppliesXhr = $.ajax({
            url: "/getStockAlertSupplies",
            method: "get",
            data: {
                mode: mode,
                warehouse_id: warehouseId,
            },
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                e.forEach(function (item) {
                    var def = -1;

                    // 1) Peringatan Stok (master) — read-only
                    item.master_alert_text =
                        '<span class="fw-semibold">' +
                        formatLeadTimeQty(item.supplies_alert) +
                        " " +
                        (item.default_unit || "") +
                        "<\/span>";

                    // 2) Pemesanan Min. tampil = max(0, threshold − stok);
                    //    threshold = supplies_min_stock (manual) ?? peringatan stok
                    var alertQty = parseFloat(item.supplies_alert) || 0;
                    var currentStock = parseFloat(item.current_stock) || 0;
                    item.current_stock_text =
                        '<span class="fw-semibold">' +
                        formatLeadTimeQty(currentStock) +
                        " " +
                        (item.default_unit || "") +
                        "<\/span>";
                    var orderThreshold =
                        item.min_order != null && item.min_order !== ""
                            ? parseFloat(item.min_order) || 0
                            : alertQty;
                    var calculatedMinOrder =
                        item.minim_order != null && item.minim_order !== ""
                            ? parseFloat(item.minim_order) || 0
                            : Math.max(0, orderThreshold - currentStock);
                    var isManual = !!item.min_order_is_manual;
                    item.min_order_text =
                        '<div class="d-flex align-items-center gap-2">' +
                        '<span class="fw-semibold">' +
                        formatLeadTimeQty(calculatedMinOrder) +
                        " " +
                        (item.default_unit || "") +
                        "<\/span>" +
                        '<button type="button" class="btn btn-link p-0 ms-1 btn-edit-min-order-supplies" style="font-size:13px;line-height:1;color:#6c757d;" ' +
                        'data-supplies-id="' +
                        item.supplies_id +
                        '" ' +
                        'data-min-order="' +
                        orderThreshold +
                        '" ' +
                        'data-calculated-min-order="' +
                        calculatedMinOrder +
                        '" ' +
                        'data-current-stock="' +
                        currentStock +
                        '" ' +
                        'data-alert-qty="' +
                        alertQty +
                        '" ' +
                        'data-unit-name="' +
                        (item.default_unit || "") +
                        '" ' +
                        'data-supplies-name="' +
                        (item.supplies_name || "") +
                        '" ' +
                        'title="Edit dasar pemesanan min.">' +
                        '<i data-feather="edit-2" style="width:13px;height:13px;"><\/i>' +
                        "<\/button>" +
                        "<\/div>" +
                        (isManual
                            ? '<div class="small text-muted" style="margin-top:2px;">Dasar manual: ' +
                              formatLeadTimeQty(orderThreshold) +
                              " " +
                              (item.default_unit || "") +
                              "<\/div>"
                            : "");

                    // 3) Stok Minimum Rekomendasi (rumus lead time)
                    item.supplies_alert_text =
                        item.reorder_point +
                        " " +
                        item.default_unit +
                        `<div class="small text-muted">Rata-rata: ${formatLeadTimeQty(item.avg_daily)}/hari · Lead time: ${item.lead_time_days} hari · Safety: ${formatLeadTimeQty(item.safety_stock)}</div>`;

                    var habis = 1;
                    if (item.stock && item.stock.length) {
                        item.stock.forEach(function (element, index) {
                            if (item.supplies_default_unit == element.unit_id) {
                                def = index;
                            }
                            if (element.ss_stock > 0) {
                                habis = -1;
                            }
                        });
                    }

                    item.habis = habis;

                    if (def > 0 && item.stock && item.stock.length) {
                        var tmp = item.stock[0];
                        item.stock[0] = item.stock[def];
                        item.stock[def] = tmp;
                    }

                    var sas =
                        roleIconEdit(
                            "Peringatan Stok Bahan Mentah",
                            "me-2 btn-action-icon p-2 btn_edit",
                            'data-id="' + item.product_id + '"'
                        ) +
                        roleIconDelete(
                            "Peringatan Stok Bahan Mentah",
                            "p-2 btn-action-icon btn_delete",
                            'data-id="' + item.product_id + '" href="javascript:void(0);"'
                        );
                    item.action = sas || '<span class="text-muted small">—</span>';
                });

                // Stok rendah = menyentuh/melewati supplies_alert manual.
                // Kolom "Stok Minimum Rekomendasi" tetap rumus lead time (reorder_point).
                var stockLow = e.filter(function (item) {
                    return (
                        item.current_stock <= (parseFloat(item.supplies_alert) || 0) &&
                        item.habis == -1
                    );
                });
                var stockOut = e.filter(function (item) {
                    return item.habis == 1;
                });

                tableLow.clear().rows.add(stockLow).draw();
                tableOut.clear().rows.add(stockOut).draw();
                $("#total_low").text(stockLow.length);
                $("#total_out").text(stockOut.length);

                feather.replace();
            },
            error: function (err) {
                if (err && err.statusText === "abort") return;
                console.error("Gagal load:", err);
            },
            complete: function (xhr, status) {
                if (status === "abort") return;
                hideStockAlertSuppliesSkeleton();
                if (tableLow) tableLow.columns.adjust();
                if (tableOut && $("#out").hasClass("active")) {
                    tableOut.columns.adjust();
                }
            },
        });
    }

    function formatLeadTimeQty(value) {
        var number = parseFloat(value) || 0;
        return Number.isInteger(number) ? number.toString() : number.toFixed(2).replace(/\.?0+$/, "");
    }

    // ── Modal Edit Pemesanan Min. (bahan) ──────────────────────────────────
    $(document).on("click", ".btn-edit-min-order-supplies", function () {
        var $btn = $(this);
        $("#emos-supplies-name").text($btn.data("supplies-name") || "—");
        $("#emos-min-order").val($btn.data("min-order") || 0);
        $("#emos-min-order-unit").val($btn.data("unit-name") || "");
        $("#emos-supplies-id").val($btn.data("supplies-id") || "");
        var unitName = $btn.data("unit-name") || "";
        var stock = parseFloat($btn.data("current-stock")) || 0;
        var alertQty = parseFloat($btn.data("alert-qty")) || 0;
        $("#emos-calculated-hint").text(
            "Tampil = dasar − stok (" +
                formatLeadTimeQty(stock) +
                "). Kosongkan override → pakai peringatan stok (" +
                formatLeadTimeQty(alertQty) +
                " " +
                unitName +
                ")."
        );
        var modal = new bootstrap.Modal(document.getElementById("modal-edit-min-order-supplies"));
        modal.show();
    });

    $("#emos-save-btn").on("click", function () {
        var minOrder = parseInt($("#emos-min-order").val(), 10);
        if (isNaN(minOrder) || minOrder < 0) {
            notifikasi("error", "Peringatan", "Nilai pemesanan minimum tidak valid.");
            return;
        }

        var $btn = $(this);
        var $spinner = $("#emos-save-spinner");
        $btn.prop("disabled", true);
        $spinner.removeClass("d-none");

        $.ajax({
            url: "/updateMinOrderSupplies",
            method: "POST",
            data: {
                _token: $('meta[name="csrf-token"]').attr("content"),
                supplies_id: $("#emos-supplies-id").val(),
                min_order: minOrder,
            },
            success: function (res) {
                if (!res.success) {
                    notifikasi("error", "Gagal", res.message || "Gagal menyimpan pemesanan minimum.");
                    return;
                }
                var modal = bootstrap.Modal.getInstance(
                    document.getElementById("modal-edit-min-order-supplies")
                );
                if (modal) modal.hide();
                refreshStockAlert();
                Swal.fire({
                    icon: "success",
                    title: "Berhasil",
                    text: res.message || "Pemesanan minimum berhasil diperbarui.",
                    confirmButtonText: "OK",
                    confirmButtonColor: "#3b82f6",
                });
            },
            error: function (err) {
                var msg =
                    err.responseJSON && err.responseJSON.message
                        ? err.responseJSON.message
                        : "Gagal menyimpan pemesanan minimum.";
                notifikasi("error", "Gagal", msg);
            },
            complete: function () {
                $btn.prop("disabled", false);
                $spinner.addClass("d-none");
            },
        });
    });

    // ── Modal Edit Peringatan Stok (bahan) ─────────────────────────────────
    $(document).on("click", ".btn-edit-stok-alert-supplies", function () {
        var $btn = $(this);
        $("#esas-supplies-name").text($btn.data("supplies-name") || "—");
        $("#esas-alert-stock").val($btn.data("alert-stock") || 0);
        $("#esas-alert-unit").val($btn.data("unit-name") || "");
        $("#esas-supplies-id").val($btn.data("supplies-id") || "");
        var modal = new bootstrap.Modal(document.getElementById("modal-edit-stok-alert-supplies"));
        modal.show();
    });

    $("#esas-save-btn").on("click", function () {
        var alertStock = parseInt($("#esas-alert-stock").val(), 10);
        if (isNaN(alertStock) || alertStock < 0) {
            notifikasi("error", "Peringatan", "Nilai stok alert tidak valid.");
            return;
        }

        var $btn = $(this);
        var $spinner = $("#esas-save-spinner");
        $btn.prop("disabled", true);
        $spinner.removeClass("d-none");

        $.ajax({
            url: "/updateStockAlertSupplies",
            method: "POST",
            data: {
                _token: $('meta[name="csrf-token"]').attr("content"),
                supplies_id: $("#esas-supplies-id").val(),
                alert_stock: alertStock,
            },
            success: function (res) {
                if (!res.success) {
                    notifikasi("error", "Gagal", res.message || "Gagal menyimpan peringatan stok.");
                    return;
                }
                var modal = bootstrap.Modal.getInstance(
                    document.getElementById("modal-edit-stok-alert-supplies")
                );
                if (modal) modal.hide();
                refreshStockAlert();
                Swal.fire({
                    icon: "success",
                    title: "Berhasil",
                    text: res.message || "Peringatan stok berhasil diperbarui.",
                    confirmButtonText: "OK",
                    confirmButtonColor: "#3b82f6",
                });
            },
            error: function (err) {
                var msg =
                    err.responseJSON && err.responseJSON.message
                        ? err.responseJSON.message
                        : "Gagal menyimpan peringatan stok.";
                notifikasi("error", "Gagal", msg);
            },
            complete: function () {
                $btn.prop("disabled", false);
                $spinner.addClass("d-none");
            },
        });
    });
