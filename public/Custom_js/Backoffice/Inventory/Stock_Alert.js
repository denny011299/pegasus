    var mode = 1;
    var tableLow, tableOut;
    var stockAlertXhr = null;

    $(document).ready(function () {
        var whName = typeof getActiveWarehouseName === "function" ? getActiveWarehouseName() : null;
        $("#stock-alert-wh-label").text(
            whName ? ("Gudang aktif: " + whName) : "Pilih gudang aktif di header terlebih dahulu"
        );
        inisialisasi();
        refreshStockAlert();

        $('a[data-bs-toggle="tab"][href="#low"], a[data-bs-toggle="tab"][href="#out"]')
            .off("shown.bs.tab.stockAlert")
            .on("shown.bs.tab.stockAlert", function (event) {
                var activeTable = $(event.target).attr("href") === "#out" ? tableOut : tableLow;
                if (activeTable) {
                    activeTable.columns.adjust().draw(false);
                }
            });
    });

    function stockAlertWraps() {
        return $("#tableStockAlertLow-wrap, #tableStockAlertOut-wrap");
    }

    function setStockAlertTableLoading(isLoading) {
        var $wraps = stockAlertWraps();
        if (!$wraps.length) return;
        $wraps.toggleClass("is-loading", !!isLoading);
    }

    function showStockAlertSkeleton() {
        stockAlertWraps().removeClass("dt-ready").addClass("dt-pending");
        setStockAlertTableLoading(true);
    }

    function hideStockAlertSkeleton() {
        setStockAlertTableLoading(false);
        stockAlertWraps().removeClass("dt-pending").addClass("dt-ready");
    }

    function inisialisasi() {
        tableLow = initStockAlertTable("#tableStockAlertLow", "Cari Stok (Rendah)");
        tableOut = initStockAlertTable("#tableStockAlertOut", "Cari Stok (Habis)");
        tableLow.columns.adjust();
    }

    function initStockAlertTable(selector, searchPlaceholder) {
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
            // Nama Produk | Kategori | SKU | Stok Saat Ini | Peringatan Stok | Pemesanan Min. | Stok Minimum Rekomendasi
            columns: [
                { data: "product_name_text", width: "20%", defaultContent: "—" },
                { data: "product_category", width: "9%", defaultContent: "—" },
                { data: "product_variant_sku", width: "9%", defaultContent: "—" },
                { data: "current_stock_text", width: "12%", defaultContent: "—" },
                { data: "master_alert_text", width: "12%", defaultContent: "—" },
                { data: "min_order_text", width: "16%", defaultContent: "—" },
                { data: "product_alert_text", width: "22%", defaultContent: "—" },
            ],
            initComplete: function (settings) {
                prepareStockAlertFilter(settings);
            },
            drawCallback: function () {
                setStockAlertTableLoading(false);
                if (typeof feather !== "undefined") feather.replace();
            },
        });
    }

    function prepareStockAlertFilter(settings) {
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
            hideStockAlertSkeleton();
            return;
        }

        if (stockAlertXhr && stockAlertXhr.readyState !== 4) {
            stockAlertXhr.abort();
        }

        showStockAlertSkeleton();

        stockAlertXhr = $.ajax({
            url: "/getStockAlert",
            method: "get",
            data: {
                mode: mode,
                warehouse_id: warehouseId,
            },
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                if (e.length) console.log("[SA] getStockAlert first item min_order:", e[0].min_order, "| minim_order:", e[0].minim_order);
                e.forEach(function (item) {
                    var def = -1;
                    item.product_name_text = item.product_name + " " + item.product_variant_name;

                    // 1) Peringatan Stok (master) — read-only
                    item.master_alert_text =
                        '<span class="fw-semibold">' +
                        formatLeadTimeQty(item.product_variant_alert) +
                        " " +
                        (item.product_unit || "") +
                        "<\/span>";

                    // 3) Stok Minimum Rekomendasi (rumus lead time)
                    item.product_alert_text =
                        item.reorder_point +
                        " " +
                        item.product_unit +
                        `<div class="small text-muted">Rata-rata: ${formatLeadTimeQty(item.avg_daily)}/hari · Lead time: ${item.lead_time_days} hari · Safety: ${formatLeadTimeQty(item.safety_stock)}</div>`;

                    // 2) Pemesanan Min. tampil = max(0, threshold − stok);
                    //    threshold = ps_min_order (manual) ?? peringatan stok
                    var alertQty = parseFloat(item.product_variant_alert) || 0;
                    var currentStock = parseFloat(item.current_stock) || 0;
                    item.current_stock_text =
                        '<span class="fw-semibold">' +
                        formatLeadTimeQty(currentStock) +
                        ' ' +
                        (item.product_unit || '') +
                        '<\/span>';
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
                        '<span class="fw-semibold">' + formatLeadTimeQty(calculatedMinOrder) + ' ' + (item.product_unit || '') + '<\/span>' +
                        '<button type="button" class="btn btn-link p-0 ms-1 btn-edit-min-order" style="font-size:13px;line-height:1;color:#6c757d;" ' +
                            'data-product-id="' + item.product_id + '" ' +
                            'data-variant-id="' + item.product_variant_id + '" ' +
                            'data-min-order="' + orderThreshold + '" ' +
                            'data-calculated-min-order="' + calculatedMinOrder + '" ' +
                            'data-current-stock="' + currentStock + '" ' +
                            'data-alert-qty="' + alertQty + '" ' +
                            'data-unit-id="' + item.unit_id + '" ' +
                            'data-unit-name="' + (item.product_unit || '') + '" ' +
                            'data-product-name="' + (item.product_name + ' ' + item.product_variant_name).trim() + '" ' +
                            'data-warehouse-id="' + item.warehouse_id + '" ' +
                            'title="Edit dasar pemesanan min.">' +
                            '<i data-feather="edit-2" style="width:13px;height:13px;"><\/i>' +
                        '<\/button>' +
                        '<\/div>' +
                        (isManual
                            ? '<div class="small text-muted" style="margin-top:2px;">Dasar manual: ' + formatLeadTimeQty(orderThreshold) + ' ' + (item.product_unit || '') + '<\/div>'
                            : '');

                    var habis = 1;
                    if (item.stock && item.stock.length) {
                        item.stock.forEach(function (element, index) {
                            if (item.unit_id == element.unit_id) {
                                def = index;
                            }
                            if (element.ps_stock > 0) {
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

                    var sa =
                        roleIconEdit(
                            "Peringatan Stok Produk",
                            "me-2 btn-action-icon p-2 btn_edit",
                            'data-id="' + item.product_id + '"'
                        ) +
                        roleIconDelete(
                            "Peringatan Stok Produk",
                            "p-2 btn-action-icon btn_delete",
                            'data-id="' + item.product_id + '" href="javascript:void(0);"'
                        );
                    item.action = sa || '<span class="text-muted small">—</span>';
                });

                // Stok rendah = menyentuh/melewati Peringatan Stok manual (ps_alert_stock).
                // Kolom "Stok Minimum Rekomendasi" tetap rumus lead time (reorder_point).
                var stockLow = e.filter(function (item) {
                    return (
                        item.current_stock <= (parseFloat(item.product_variant_alert) || 0) &&
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
                hideStockAlertSkeleton();
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

    // ── Modal Edit Pemesanan Min. ──────────────────────────────────────────
    $(document).on("click", ".btn-edit-min-order", function () {
        var $btn = $(this);
        $("#emo-product-name").text($btn.data("product-name") || "—");
        $("#emo-min-order").val($btn.data("min-order") || 0);
        $("#emo-min-order-unit").val($btn.data("unit-name") || "");
        $("#emo-product-id").val($btn.data("product-id") || "");
        $("#emo-variant-id").val($btn.data("variant-id") || "");
        $("#emo-unit-id").val($btn.data("unit-id") || "");
        $("#emo-warehouse-id").val($btn.data("warehouse-id") || "");
        var unitName = $btn.data("unit-name") || "";
        var stock = parseFloat($btn.data("current-stock")) || 0;
        var alertQty = parseFloat($btn.data("alert-qty")) || 0;
        $("#emo-calculated-hint").text(
            "Tampil = dasar − stok (" +
                formatLeadTimeQty(stock) +
                "). Kosongkan override → pakai peringatan stok (" +
                formatLeadTimeQty(alertQty) +
                " " +
                unitName +
                ")."
        );
        var modal = new bootstrap.Modal(document.getElementById("modal-edit-min-order"));
        modal.show();
    });

    $("#emo-save-btn").on("click", function () {
        var minOrder = parseInt($("#emo-min-order").val(), 10);
        if (isNaN(minOrder) || minOrder < 0) {
            notifikasi("error", "Peringatan", "Nilai pemesanan minimum tidak valid.");
            return;
        }

        var $btn = $(this);
        var $spinner = $("#emo-save-spinner");
        $btn.prop("disabled", true);
        $spinner.removeClass("d-none");

        var postDataMO = {
                _token: $('meta[name="csrf-token"]').attr("content"),
                product_id: $("#emo-product-id").val(),
                product_variant_id: $("#emo-variant-id").val(),
                min_order: minOrder,
                min_order_unit_id: $("#emo-unit-id").val(),
                warehouse_id: $("#emo-warehouse-id").val(),
            };
        console.log("[EMO] Kirim updateMinOrder:", postDataMO);

        $.ajax({
            url: "/updateMinOrder",
            method: "POST",
            data: postDataMO,
            success: function (res) {
                console.log("[EMO] Response updateMinOrder:", res);
                if (!res.success) {
                    notifikasi("error", "Gagal", res.message || "Gagal menyimpan pemesanan minimum.");
                    return;
                }
                var modal = bootstrap.Modal.getInstance(document.getElementById("modal-edit-min-order"));
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

    // ── Modal Edit Stok Alert ──────────────────────────────────────────────
    $(document).on("click", ".btn-edit-stok-alert", function () {
        var $btn = $(this);
        $("#esa-product-name").text($btn.data("product-name") || "—");
        $("#esa-alert-stock").val($btn.data("alert-stock") || 0);
        $("#esa-alert-unit").val($btn.data("unit-name") || "");
        $("#esa-product-id").val($btn.data("product-id") || "");
        $("#esa-variant-id").val($btn.data("variant-id") || "");
        $("#esa-unit-id").val($btn.data("unit-id") || "");
        $("#esa-warehouse-id").val($btn.data("warehouse-id") || "");
        var modal = new bootstrap.Modal(document.getElementById("modal-edit-stok-alert"));
        modal.show();
    });

    $("#esa-save-btn").on("click", function () {
        var alertStock = parseInt($("#esa-alert-stock").val(), 10);
        if (isNaN(alertStock) || alertStock < 0) {
            notifikasi('error', "Peringatan", 'Nilai stok alert tidak valid.');
            return;
        }

        var $btn = $(this);
        var $spinner = $("#esa-save-spinner");
        $btn.prop("disabled", true);
        $spinner.removeClass("d-none");

        var postData = {
                _token: $('meta[name="csrf-token"]').attr("content"),
                product_id:          $("#esa-product-id").val(),
                product_variant_id:  $("#esa-variant-id").val(),
                alert_stock:         alertStock,
                alert_unit_id:       $("#esa-unit-id").val(),
                warehouse_id:        $("#esa-warehouse-id").val(),
            };

        $.ajax({
            url: "/updateStockAlert",
            method: "POST",
            data: postData,
            success: function (res) {
                var modal = bootstrap.Modal.getInstance(document.getElementById("modal-edit-stok-alert"));
                if (modal) modal.hide();

                refreshStockAlert();

                var swalResult = Swal.fire({
                    icon: "success",
                    title: "Berhasil",
                    text: res.message || "Peringatan stok berhasil diperbarui.",
                    confirmButtonText: "OK",
                    confirmButtonColor: "#3b82f6",
                });
            },
            error: function (err) {
                var msg = (err.responseJSON && err.responseJSON.message) ? err.responseJSON.message : "Gagal menyimpan stok alert.";
                notifikasi('error', "Gagal", msg);
            },
            complete: function () {
                $btn.prop("disabled", false);
                $spinner.addClass("d-none");
            },
        });
    });
