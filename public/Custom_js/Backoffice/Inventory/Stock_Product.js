    var mode = 1;
    var table = null;
    var dates = null;
    var activeId = 0;
    var warehouseWarnShown = false;
    var viewMode = "main";
    var canViewSafetyStock = false;
    var canEditSafetyStock = false;
    var stockXhr = null;
    var safetyRow = null;

    function resolveSafetyStockAccess() {
        return typeof hasAccessAction === "function"
            && (hasAccessAction("Safety Stock", "view") || hasAccessAction("Safety Stock", "edit"));
    }

    function resolveSafetyStockEdit() {
        return typeof hasAccessAction === "function" && hasAccessAction("Safety Stock", "edit");
    }

    $(document).ready(function () {
        canViewSafetyStock = resolveSafetyStockAccess();
        canEditSafetyStock = resolveSafetyStockEdit();
        viewMode = (typeof getStockViewMode === "function" && getStockViewMode()) || "main";
        if (viewMode === "retail") {
            $("#tableStockRetail-wrap").show();
            $("#tableStock-wrap").hide();
            inisialisasiRetail();
        } else {
            $("#tableStock-wrap").show();
            $("#tableStockRetail-wrap").hide();
            inisialisasiMain();
        }
    });

    function stockAjax(data, callback) {
        var warehouseId = typeof getActiveWarehouseId === "function" ? getActiveWarehouseId() : null;
        if (!warehouseId) {
            if (!warehouseWarnShown) {
                warehouseWarnShown = true;
                if (typeof toastr !== "undefined") {
                    toastr.warning("Pilih gudang aktif di header terlebih dahulu");
                } else if (typeof notifikasi === "function") {
                    notifikasi("warning", "Gudang", "Pilih gudang aktif di header terlebih dahulu");
                }
            }
            callback({
                draw: data.draw,
                recordsTotal: 0,
                recordsFiltered: 0,
                data: [],
            });
            setStockTableLoading(false);
            return;
        }
        warehouseWarnShown = false;

        if (stockXhr && stockXhr.readyState !== 4) {
            stockXhr.abort();
        }

        stockXhr = $.ajax({
            url: "/getStock",
            type: "GET",
            data: $.extend({}, data, { warehouse_id: warehouseId }),
            beforeSend: function () {
                setStockTableLoading(true);
            },
            success: function (json) {
                callback(json);
            },
            error: function (err) {
                if (err && err.statusText === "abort") return;
                console.error("Gagal load:", err);
                callback({
                    draw: data.draw,
                    recordsTotal: 0,
                    recordsFiltered: 0,
                    data: [],
                });
            },
            complete: function (xhr, status) {
                if (status === "abort") return;
                setStockTableLoading(false);
            },
        });
    }

    function setStockTableLoading(isLoading) {
        var $wrap =
            viewMode === "retail" ? $("#tableStockRetail-wrap") : $("#tableStock-wrap");
        if (!$wrap.length) return;
        $wrap.toggleClass("is-loading", !!isLoading);
    }

    function bindStockLoadingEvents($table) {
        $table
            .on("preXhr.dt", function () {
                setStockTableLoading(true);
            })
            .on("xhr.dt", function () {
                setTimeout(function () {
                    setStockTableLoading(false);
                }, 0);
            });
    }

    function dtBaseOptions(searchPlaceholder, order) {
        return {
            processing: true,
            serverSide: true,
            deferRender: true,
            autoWidth: false,
            bFilter: true,
            sDom: "fBtlpi",
            lengthMenu: [10, 25, 50, 100],
            pageLength: 10,
            ordering: true,
            // scrollX bikin header/body desync + lebih berat; pakai .table-responsive
            scrollX: false,
            order: order,
            searchDelay: 400,
            language: {
                search: " ",
                sLengthMenu: "_MENU_",
                searchPlaceholder: searchPlaceholder || "Cari Produk",
                info: "_START_ - _END_ of _TOTAL_ items",
                emptyTable: "Tidak ada data stok untuk gudang ini",
                zeroRecords: "Produk tidak ditemukan",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> ',
                },
            },
            ajax: function (data, callback) {
                stockAjax(data, callback);
            },
            drawCallback: function () {
                setStockTableLoading(false);
            },
        };
    }

    function moveSearchFilter() {
        var $filter = $(".dataTables_filter").last();
        $filter.appendTo(".search-input");
        if (!$filter.find("label .fa-search").length) {
            $filter.find("label").prepend('<i class="fa fa-search"></i> ');
        }
    }

    function ensureSafetyHeader($table, centered) {
        var $th = $table.find("thead th.col-safety");
        if (!canViewSafetyStock) {
            $th.remove();
            return;
        }
        if ($th.length) {
            if (centered) $th.addClass("text-center");
            return;
        }
        var cls = centered ? "col-safety text-center" : "col-safety";
        $table.find("thead tr").append('<th class="' + cls + '">Safety Stock</th>');
    }

    function bindRowClick($table) {
        $table.find("tbody").off("click.stockRow").on("click.stockRow", "tr", function (e) {
            if (!table) return;
            var data = table.row(this).data();
            if (!data) return;
            if ($(e.target).closest("td.cell-safety").length) {
                openSafetyModal(data);
                return;
            }
            activeId = data.product_variant_id;
            getLog(data.product_variant_id);
        });
    }

    function renderSafetyLabel(text) {
        var label = text && text !== "-" ? text : "-";
        return (
            '<div class="safety-cell-label d-inline-flex align-items-center" style="cursor:pointer; min-height:20px;">' +
                '<span style="font-weight:500; color:#475569; display:inline-block; width:65px; text-align:inherit; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">' + escapeHtml(label) + '</span>' +
                '<i class="fe fe-edit-2 text-primary flex-shrink-0" style="font-size:13px; opacity:0.6; margin-left:4px;"></i>' +
            '</div>'
        );
    }

    function pickSafetyUnit(units) {
        if (!units || !units.length) return null;
        for (var i = 0; i < units.length; i++) {
            if (Number(units[i].ps_safety_stock) > 0) return units[i];
        }
        return units[0];
    }

    function openSafetyModal(row) {
        safetyRow = row;
        var title =
            (row.pr_name || "-") +
            (row.product_variant_name ? " — " + row.product_variant_name : "");
        $("#safety_modal_subtitle").text(title);

        var units = row.units || [];
        var active = pickSafetyUnit(units);
        if (!active) {
            $("#table_safety_edit tbody").html(
                '<tr><td colspan="2" class="text-muted text-center">Tidak ada satuan</td></tr>'
            );
            $("#table_safety_transfer tbody").html(
                '<tr><td colspan="3" class="text-muted text-center">Tidak ada satuan</td></tr>'
            );
            $("#btn_save_safety, #btn_transfer_safety").prop("disabled", true);
            $("#modal_safety_stock").modal("show");
            return;
        }

        var safety = Math.floor(Number(active.ps_safety_stock) || 0);
        var unitOpts = units
            .map(function (u) {
                return (
                    '<option value="' +
                    u.unit_id +
                    '"' +
                    (Number(u.unit_id) === Number(active.unit_id) ? " selected" : "") +
                    ">" +
                    escapeHtml(u.unit_name || "-") +
                    "</option>"
                );
            })
            .join("");

        $("#table_safety_edit tbody").html(
            "<tr>" +
                "<td style=\"padding: 12px 24px;\"><select class=\"form-select form-select-sm safety-edit-unit\" " +
                (canEditSafetyStock ? "" : "disabled") +
                ">" +
                unitOpts +
                "</select></td>" +
                "<td style=\"padding: 12px 24px;\"><input type=\"number\" min=\"0\" step=\"1\" class=\"form-control form-control-sm safety-edit-qty\" value=\"" +
                safety +
                "\" " +
                (canEditSafetyStock ? "" : "disabled") +
                "></td>" +
                "</tr>"
        );

        $("#table_safety_transfer tbody").html(
            '<tr data-unit-id="' +
                active.unit_id +
                '" data-max="' +
                safety +
                '">' +
                "<td style=\"padding: 12px 24px;\">" +
                escapeHtml(active.unit_name || "-") +
                "</td>" +
                '<td class="text-center fw-semibold" style="padding: 12px 24px;">' +
                safety +
                "</td>" +
                "<td style=\"padding: 12px 24px;\"><input type=\"number\" min=\"0\" max=\"" +
                safety +
                "\" step=\"1\" class=\"form-control form-control-sm safety-transfer-qty\" value=\"0\" " +
                (canEditSafetyStock && safety > 0 ? "" : "disabled") +
                "></td>" +
                "</tr>"
        );

        $("#btn_save_safety, #btn_transfer_safety").prop("disabled", !canEditSafetyStock);
        $("#modal_safety_stock").modal("show");
    }

    function csrfToken() {
        return $('meta[name="csrf-token"]').attr("content") || "";
    }

    $(document).on("click", "#btn_save_safety", function () {
        if (!safetyRow || !canEditSafetyStock) return;
        var unitId = Number($("#table_safety_edit .safety-edit-unit").val()) || 0;
        var qty = Math.max(0, parseInt($("#table_safety_edit .safety-edit-qty").val(), 10) || 0);
        if (unitId <= 0) return;
        var $btn = $(this);
        $btn.prop("disabled", true);
        $.ajax({
            url: "/updateProductSafetyStock",
            method: "post",
            data: {
                _token: csrfToken(),
                product_variant_id: safetyRow.product_variant_id,
                warehouse_id: safetyRow.warehouse_id || getActiveWarehouseId(),
                unit_id: unitId,
                ps_safety_stock: qty,
            },
            success: function (res) {
                if (res && res.status == 1) {
                    if (typeof toastr !== "undefined") toastr.success("", res.message || "Tersimpan");
                    $("#modal_safety_stock").modal("hide");
                    refreshStock();
                } else if (typeof toastr !== "undefined") {
                    toastr.error("", (res && res.message) || "Gagal menyimpan");
                }
            },
            error: function (xhr) {
                var msg =
                    (xhr.responseJSON && xhr.responseJSON.message) || "Gagal menyimpan";
                if (typeof toastr !== "undefined") toastr.error("", msg);
            },
            complete: function () {
                $btn.prop("disabled", !canEditSafetyStock);
            },
        });
    });

    $(document).on("click", "#btn_transfer_safety", function () {
        if (!safetyRow || !canEditSafetyStock) return;
        var items = [];
        $("#table_safety_transfer tbody tr[data-unit-id]").each(function () {
            var qty = Math.max(0, parseFloat($(this).find(".safety-transfer-qty").val()) || 0);
            var max = Number($(this).data("max")) || 0;
            if (qty <= 0) return;
            if (qty > max) {
                if (typeof toastr !== "undefined") {
                    toastr.warning("", "Qty transfer melebihi safety stock");
                }
                items = null;
                return false;
            }
            items.push({
                unit_id: Number($(this).data("unit-id")),
                qty: qty,
            });
        });
        if (items === null) return;
        if (!items.length) {
            if (typeof toastr !== "undefined") toastr.warning("", "Isi qty transfer");
            return;
        }
        var $btn = $(this);
        $btn.prop("disabled", true);
        $.ajax({
            url: "/transferSafetyToStock",
            method: "post",
            data: {
                _token: csrfToken(),
                product_variant_id: safetyRow.product_variant_id,
                warehouse_id: safetyRow.warehouse_id || getActiveWarehouseId(),
                items: JSON.stringify(items),
            },
            success: function (res) {
                if (res && res.status == 1) {
                    if (typeof toastr !== "undefined") toastr.success("", res.message || "Transfer berhasil");
                    $("#modal_safety_stock").modal("hide");
                    refreshStock();
                } else if (typeof toastr !== "undefined") {
                    toastr.error("", (res && res.message) || "Gagal transfer");
                }
            },
            error: function (xhr) {
                var msg =
                    (xhr.responseJSON && xhr.responseJSON.message) || "Gagal transfer";
                if (typeof toastr !== "undefined") toastr.error("", msg);
            },
            complete: function () {
                $btn.prop("disabled", !canEditSafetyStock);
            },
        });
    });

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
    }

    function renderProductNameCell(data, row) {
        var name = data || "-";
        var initials = name.substring(0, 2).toUpperCase();
        var words = name.trim().split(/\s+/);
        if (words.length >= 2) {
            initials = (words[0][0] + words[1][0]).toUpperCase();
        }
        var avatarHtml = row.image_url
            ? '<img src="' +
              escapeHtml(row.image_url) +
              '" alt="' +
              escapeHtml(name) +
              '" style="width:32px;height:32px;border-radius:8px;object-fit:cover;border:1px solid #e2e8f0;">'
            : '<div style="width:32px;height:32px;border-radius:8px;background:#eff6ff;border:1px solid #bfdbfe;color:#2563eb;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:12px;flex-shrink:0;">' +
              escapeHtml(initials) +
              "</div>";

        return (
            '<div style="display:flex;align-items:center;gap:10px;">' +
            avatarHtml +
            '<span class="fw-semibold text-dark">' +
            escapeHtml(name) +
            "</span></div>"
        );
    }

    function renderSkuBadge(data) {
        return (
            '<span class="badge" style="background:#f1f5f9;color:#334155;border:1px solid #cbd5e1;padding:6px 10px;">' +
            escapeHtml(data || "-") +
            "</span>"
        );
    }

    function buildStockTableColumns() {
        var columns = [
            {
                data: "product_variant_sku",
                width: canViewSafetyStock ? "12%" : "15%",
                render: function (data, type) {
                    if (type !== "display") return data || "";
                    return renderSkuBadge(data);
                },
            },
            {
                data: "pr_name",
                width: canViewSafetyStock ? "16%" : "20%",
                render: function (data, type, row) {
                    if (type !== "display") return data;
                    return renderProductNameCell(data, row);
                },
            },
            {
                data: "product_variant_name",
                width: canViewSafetyStock ? "16%" : "20%",
                render: function (data, type) {
                    if (type !== "display") return data || "";
                    return (
                        '<span class="text-dark fw-medium">' +
                        escapeHtml(data || "-") +
                        "</span>"
                    );
                },
            },
            {
                data: "product_category",
                width: canViewSafetyStock ? "12%" : "15%",
                render: function (data, type) {
                    if (type !== "display") return data || "";
                    return (
                        '<div style="display:flex;align-items:center;gap:6px;">' +
                        '<i class="fe fe-tag text-muted" style="font-size:14px;"></i>' +
                        '<span class="text-dark">' +
                        escapeHtml(data || "-") +
                        "</span></div>"
                    );
                },
            },
            {
                data: "warehouse_name",
                width: canViewSafetyStock ? "12%" : "15%",
                orderable: false,
                searchable: false,
                render: function (data, type) {
                    if (type !== "display") return data || "";
                    return (
                        '<div style="display:flex;align-items:center;gap:6px;">' +
                        '<i class="fe fe-home text-muted" style="font-size:14px;"></i>' +
                        '<span class="fw-semibold text-dark">' +
                        escapeHtml(data || "-") +
                        "</span></div>"
                    );
                },
            },
            {
                data: "product_variant_stock_text",
                class: "fw-bold",
                width: "15%",
                orderable: false,
                searchable: false,
            },
        ];

        if (canViewSafetyStock) {
            columns.push({
                data: "product_variant_safety_text",
                defaultContent: "-",
                className: "fw-bold cell-safety",
                width: "16%",
                orderable: false,
                searchable: false,
                render: function (data, type) {
                    if (type !== "display") return data || "-";
                    return renderSafetyLabel(data || "-");
                },
            });
        }

        return columns;
    }

    // =========================
    // VIEW: Gudang Utama
    // =========================
    function inisialisasiMain() {
        $("#tableStock-wrap").show();
        $("#tableStockRetail-wrap").hide();
        ensureSafetyHeader($("#tableStock"), false);

        var opts = dtBaseOptions("Cari Produk", [[1, "asc"]]);
        opts.columns = buildStockTableColumns();
        opts.initComplete = function () {
            moveSearchFilter();
            $("#tableStock-wrap").removeClass("dt-pending").addClass("dt-ready");
        };

        table = $("#tableStock").DataTable(opts);
        bindStockLoadingEvents($("#tableStock"));
        bindRowClick($("#tableStock"));
    }

    // =========================
    // VIEW: Gudang Eceran — layout sama gudang utama
    // =========================
    function inisialisasiRetail() {
        $("#tableStockRetail-wrap").show();
        $("#tableStock-wrap").hide();
        ensureSafetyHeader($("#tableStockRetail"), false);

        var opts = dtBaseOptions("Cari Produk", [[1, "asc"]]);
        opts.columns = buildStockTableColumns();
        opts.autoWidth = false;
        opts.initComplete = function () {
            moveSearchFilter();
            $("#tableStockRetail-wrap").removeClass("dt-pending").addClass("dt-ready");
            try {
                this.api().columns.adjust();
            } catch (e) {}
        };

        table = $("#tableStockRetail").DataTable(opts);
        bindStockLoadingEvents($("#tableStockRetail"));
        bindRowClick($("#tableStockRetail"));
    }

    function refreshStock() {
        if (table) {
            table.ajax.reload(null, false);
        }
    }

    var logXhr = null;
    var logLazy = {
        offset: 0,
        limit: 30,
        hasMore: true,
        loading: false,
    };

    function resetLogLazy() {
        logLazy.offset = 0;
        logLazy.hasMore = true;
        logLazy.loading = false;
    }

    function bindLogScroll() {
        var $sc = $("#tableLogScroll");
        if (!$sc.length) return;
        $sc.off("scroll.logLazy").on("scroll.logLazy", function () {
            if (!activeId || !logLazy.hasMore || logLazy.loading) return;
            var el = this;
            if (el.scrollTop + el.clientHeight >= el.scrollHeight - 100) {
                getLog(activeId, true);
            }
        });
    }

    function maybeFillLogViewport() {
        var el = document.getElementById("tableLogScroll");
        if (!el || !activeId || !logLazy.hasMore || logLazy.loading) return;
        // Konten pendek: auto-load sampai overflow / habis data
        if (el.scrollHeight <= el.clientHeight + 20) {
            getLog(activeId, true);
        }
    }

    function setHistoryLogLoading(show) {
        var $scroll = $("#add_stock_product #tableLogScroll");
        if (!$scroll.length) return;
        $scroll.find(".log-loading-overlay").remove();
        if (!show) return;
        $("#add_stock_product #tableLog tbody").empty();
        $scroll.css("position", "relative").append(`
            <div class="log-loading-overlay" style="position:absolute;inset:0;min-height:260px;display:flex;align-items:center;justify-content:center;background:#fff;z-index:15;">
                <div class="d-flex flex-column align-items-center justify-content-center text-center px-3">
                    <div class="spinner-border text-primary" style="width:2.5rem;height:2.5rem;border-width:0.25em;" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="mt-2 fw-semibold" style="color:#475569;font-size:13px;letter-spacing:0.3px;">Sedang memuat histori...</div>
                </div>
            </div>
        `);
    }

    function openHistoryModalLoading() {
        setHistoryLogLoading(true);
        $("#add_stock_product .modal-title").html("Lihat Histori Produk");
        bindLogScroll();
        $("#add_stock_product").modal("show");
    }

    function formatLogSaldoQty(val) {
        if (val == null || val === "") return null;
        var n = parseFloat(val);
        if (isNaN(n)) return null;
        return String(Math.round(n));
    }

    function renderLogRows(rows) {
        return (rows || [])
            .map(function (e) {
                var saldoText;
                if (e.log_saldo_text) {
                    saldoText =
                        '<span style="font-weight:700;color:#0f172a;">' +
                        $("<div>").text(e.log_saldo_text).html() +
                        "</span>";
                } else {
                    var saldoQty = formatLogSaldoQty(e.log_saldo);
                    saldoText =
                        saldoQty == null
                            ? '<span style="color: #cbd5e1;">-</span>'
                            : '<span style="font-weight:700;color:#0f172a;">' +
                              saldoQty +
                              " " +
                              (e.unit_name || "") +
                              "</span>";
                }
                return `
                    <tr class="row-log align-middle" data-id="${e.log_id}" style="border-bottom: 1px solid #f1f5f9; transition: all 0.2s ease;">
                        <td style="width:12%; padding: 14px 24px;">
                            <div class="d-flex align-items-center gap-2">
                                <div style="width:8px;height:8px;border-radius:50%;background-color:${e.log_category == 1 ? '#22c55e' : (e.log_category == 2 ? '#ef4444' : '#cbd5e1')}"></div>
                                <span style="color: #64748b; font-size: 13px; font-weight: 500;">${moment(e.log_date).format("D MMM YYYY")}</span>
                            </div>
                            <small style="color: #94a3b8; margin-left: 16px;">${moment(e.log_date).format("HH:mm")}</small>
                        </td>
                        <td style="width:12%; padding: 14px 24px;">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-light text-secondary d-flex justify-content-center align-items-center rounded-circle" style="width: 24px; height: 24px; font-size: 10px; font-weight: bold;">
                                    ${(e.staff_name || 'U').charAt(0).toUpperCase()}
                                </div>
                                <span style="font-weight: 600; color: #334155;">${e.staff_name || '-'}</span>
                            </div>
                        </td>
                        <td style="width:12%; padding: 14px 24px;">
                            <span style="background: #eff6ff; color: #3b82f6; padding: 4px 10px; border-radius: 6px; font-family: monospace; font-size: 12px; font-weight: 600; letter-spacing: 0.5px;">
                                ${e.log_kode || '-'}
                            </span>
                        </td>
                        <td style="width:22%; padding: 14px 24px;">
                            <span style="color: #475569; font-size: 13px;">${e.log_notes || '-'}</span>
                        </td>
                        <td style="width:12%; padding: 14px 24px;" class="text-center">
                            ${e.log_category == 1 ? '<span class="badge" style="background-color: #dcfce7; color: #166534; font-size: 12px; font-weight: 600; border: 1px solid #bbf7d0; padding: 6px 12px; border-radius: 20px;"><i class="fe fe-arrow-down-left me-1"></i>' + e.log_jumlah + ' ' + e.unit_name + '</span>' : '<span style="color: #cbd5e1;">-</span>'}
                        </td>
                        <td style="width:12%; padding: 14px 24px;" class="text-center">
                            ${e.log_category == 2 ? '<span class="badge" style="background-color: #fee2e2; color: #991b1b; font-size: 12px; font-weight: 600; border: 1px solid #fecaca; padding: 6px 12px; border-radius: 20px;"><i class="fe fe-arrow-up-right me-1"></i>' + e.log_jumlah + ' ' + e.unit_name + '</span>' : '<span style="color: #cbd5e1;">-</span>'}
                        </td>
                        <td style="width:12%; padding: 14px 24px;" class="text-center">${saldoText}</td>
                    </tr>
                `;
            })
            .join("");
    }

    function setLogFooterLoading(show) {
        $("#tableLog tr.row-log-more").remove();
        if (!show) return;
        $("#tableLog tbody").append(`
            <tr class="row-log-more">
                <td colspan="7" class="text-center py-3 text-muted">
                    <span class="spinner-border spinner-border-sm me-2" role="status"></span>Memuat lagi...
                </td>
            </tr>
        `);
    }

    function getLog(id, append) {
        if (!id) return;
        activeId = id;
        append = !!append;

        if (!append) {
            resetLogLazy();
            openHistoryModalLoading();
            $("#tableLogScroll").scrollTop(0);
        }

        if (logLazy.loading || (!logLazy.hasMore && append)) return;
        logLazy.loading = true;

        if (logXhr && logXhr.readyState !== 4) {
            if (!append) logXhr.abort();
            else {
                logLazy.loading = false;
                return;
            }
        }

        if (append) setLogFooterLoading(true);

        var warehouseId = typeof getActiveWarehouseId === "function" ? getActiveWarehouseId() : null;
        var reqOffset = append ? logLazy.offset : 0;
        logXhr = $.ajax({
            url: "/getLog",
            method: "get",
            data: {
                log_type: 1,
                log_item_id: id,
                date: dates,
                warehouse_id: warehouseId,
                lazy: 1,
                offset: reqOffset,
                limit: logLazy.limit,
            },
            success: function (res) {
                if (String(activeId) !== String(id)) return;
                var rows = Array.isArray(res) ? res : res.data || [];
                var hasMore = Array.isArray(res) ? false : !!res.has_more;
                logLazy.hasMore = hasMore;
                logLazy.offset = reqOffset + rows.length;
                logLazy.loading = false;
                setLogFooterLoading(false);

                if (!append) {
                    setHistoryLogLoading(false);
                    if (!rows.length) {
                        $("#add_stock_product #tableLog tbody").html(`
                            <tr class="empty-log">
                                <td colspan="7" class="text-center text-muted py-4">
                                    Produk ini belum ada riwayat perubahan stok
                                </td>
                            </tr>
                        `);
                        return;
                    }
                    $("#add_stock_product #tableLog tbody").html(renderLogRows(rows));
                    setTimeout(maybeFillLogViewport, 50);
                    return;
                }

                if (rows.length) {
                    $("#tableLog tbody").append(renderLogRows(rows));
                    setTimeout(maybeFillLogViewport, 50);
                }
            },
            error: function (xhr) {
                if (xhr.statusText === "abort") return;
                logLazy.loading = false;
                setLogFooterLoading(false);
                console.error("Gagal load:", xhr);
                if (!append) {
                    setHistoryLogLoading(false);
                    $("#add_stock_product #tableLog tbody").html(`
                        <tr class="empty-log">
                            <td colspan="7" class="text-center text-danger py-4">
                                Gagal memuat histori
                            </td>
                        </tr>
                    `);
                }
            },
        });
    }

    $(document).on("shown.bs.modal", "#add_stock_product", function () {
        bindLogScroll();
    });

    $(document).on("change", "#start_date, #end_date", function () {
        dates = [$("#start_date").val(), $("#end_date").val()];
        getLog(activeId);
    });

    $(document).on("click", ".btn-clear", function () {
        dates = null;
        $("#start_date").val("");
        $("#end_date").val("");
        getLog(activeId);
    });
