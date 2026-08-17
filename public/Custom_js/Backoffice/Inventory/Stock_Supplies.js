    var mode = 1;
    var table = null;
    var dates = null;
    var activeId = 0;
    var warehouseWarnShown = false;
    var logXhr = null;
    var logLazy = {
        offset: 0,
        limit: 30,
        hasMore: true,
        loading: false,
    };

    $(document).ready(function () {
        inisialisasi();
    });

    function stockSuppliesAjax(data, callback) {
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
            return;
        }
        warehouseWarnShown = false;

        $.ajax({
            url: "/getStockSupplies",
            type: "GET",
            data: $.extend({}, data, { warehouse_id: warehouseId }),
            success: function (json) {
                callback(json);
            },
            error: function (err) {
                if (handlePermissionError(err)) return;
                console.error("Gagal load:", err);
                callback({
                    draw: data.draw,
                    recordsTotal: 0,
                    recordsFiltered: 0,
                    data: [],
                });
            },
        });
    }

    function setStockTableLoading(isLoading) {
        var $wrap = $("#tableStock-wrap");
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

    function inisialisasi() {
        table = $("#tableStock").DataTable({
            processing: true,
            serverSide: true,
            deferRender: true,
            autoWidth: false,
            bFilter: true,
            sDom: "fBtlpi",
            lengthMenu: [10, 25, 50, 100],
            pageLength: 10,
            ordering: true,
            scrollX: false,
            order: [[0, "asc"]],
            searchDelay: 400,
            language: {
                search: " ",
                sLengthMenu: "_MENU_",
                searchPlaceholder: "Cari Bahan Mentah",
                info: "_START_ - _END_ of _TOTAL_ items",
                emptyTable: "Tidak ada data stok untuk gudang ini",
                zeroRecords: "Bahan mentah tidak ditemukan",
                processing:
                    '<div><span class="spinner-border spinner-border-sm text-primary" role="status"></span><span>Memuat stok...</span></div>',
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> ',
                },
            },
            ajax: function (data, callback) {
                stockSuppliesAjax(data, callback);
            },
            columns: [
                { 
                    data: "supplies_name", 
                    width: "75%",
                    render: function (data, type, row) {
                        return '<span style="font-weight: 600; color: #334155; font-size: 13px;">' + (data || '-') + '</span>';
                    }
                },
                {
                    data: "supplies_variant_stock_text",
                    width: "25%",
                    orderable: false,
                    searchable: false,
                    render: function (data, type, row) {
                        return '<span style="background: #f8fafc; border: 1px solid #e2e8f0; color: #475569; padding: 4px 10px; border-radius: 6px; font-weight: 600; font-size: 12px; letter-spacing: 0.3px;">' + (data || '-') + '</span>';
                    }
                },
            ],
            initComplete: function () {
                $("#tableStock-wrap").removeClass("dt-pending").addClass("dt-ready");
                var $filter = $(".dataTables_filter").last();
                $filter.appendTo("#tableSearch");
                $filter.appendTo(".search-input");
                if (!$filter.find("label .fa-search").length) {
                    $filter.find("label").prepend('<i class="fa fa-search"></i> ');
                }
            },
            drawCallback: function () {
                setStockTableLoading(false);
                if (table) table.columns.adjust();
            },
        });

        bindStockLoadingEvents($("#tableStock"));
    }

    $(document).on("click", "#tableStock tbody tr", function () {
        var data = table.row(this).data();
        if (!data) return;
        activeId = data.supplies_id;
        getLog(data.supplies_id);
    });

    function resetLogLazy() {
        logLazy.offset = 0;
        logLazy.hasMore = true;
        logLazy.loading = false;
    }

    function bindLogScroll() {
        var $sc = $("#add_stock_supplies #tableLogScroll");
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
        var el = document.querySelector("#add_stock_supplies #tableLogScroll");
        if (!el || !activeId || !logLazy.hasMore || logLazy.loading) return;
        if (el.scrollHeight <= el.clientHeight + 20) {
            getLog(activeId, true);
        }
    }

    function setHistoryLogLoading(show) {
        var $scroll = $("#add_stock_supplies #tableLogScroll");
        if (!$scroll.length) return;
        $scroll.find(".log-loading-overlay").remove();
        if (!show) return;
        $("#add_stock_supplies #tableLog tbody").empty();
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
        $("#add_stock_supplies .modal-title").html("Lihat Histori Bahan Mentah");
        bindLogScroll();
        $("#add_stock_supplies").modal("show");
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
                    var saldoQty =
                        e.log_saldo == null || e.log_saldo === ""
                            ? null
                            : (function () {
                                  var n = parseFloat(e.log_saldo);
                                  return isNaN(n) ? null : String(Math.round(n));
                              })();
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
                    <tr class="row-log align-middle" data-id="${e.log_id}" style="border-bottom: 1px solid #f1f5f9;">
                        <td style="width:12%; padding: 14px 24px;">
                            <div class="d-flex align-items-center gap-2">
                                <div style="width:8px;height:8px;border-radius:50%;background-color:${e.log_category == 1 ? '#22c55e' : (e.log_category == 2 ? '#ef4444' : '#cbd5e1')}"></div>
                                <span style="color: #64748b; font-size: 13px; font-weight: 500;">${moment(e.log_date).format("D MMM YYYY")}</span>
                            </div>
                            <small style="color: #94a3b8; margin-left: 16px;">${moment(e.log_date).format("HH:mm")}</small>
                        </td>
                        <td style="width:12%; padding: 14px 24px;">
                            <div class="d-flex align-items-center gap-2">
                                <div style="width: 24px; height: 24px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; color: #64748b; font-size: 10px; font-weight: 700;">
                                    ${(e.staff_name || 'S').charAt(0).toUpperCase()}
                                </div>
                                <span style="font-weight: 600; color: #334155;">${e.staff_name || '-'}</span>
                            </div>
                        </td>
                        <td style="width:12%; padding: 14px 24px;">
                            <span style="background: #eff6ff; color: #3b82f6; padding: 4px 10px; border-radius: 6px; font-family: monospace; font-size: 12px; font-weight: 600; border: 1px solid #dbeafe;">
                                ${e.log_kode || '-'}
                            </span>
                        </td>
                        <td style="width:22%; padding: 14px 24px;">
                            <span style="color: #475569; font-size: 13px;">${e.log_notes || '-'}</span>
                        </td>
                        <td style="width:12%; padding: 14px 24px;" class="text-center">
                            ${e.log_category == 1 
                                ? '<span style="background: #ecfdf5; color: #10b981; padding: 4px 10px; border-radius: 20px; font-weight: 600; font-size: 11px; letter-spacing: 0.3px;"><i class="fe fe-arrow-down me-1"></i>' + e.log_jumlah + ' ' + e.unit_name + '</span>' 
                                : '<span style="color: #cbd5e1;">-</span>'}
                        </td>
                        <td style="width:12%; padding: 14px 24px;" class="text-center">
                            ${e.log_category == 2 
                                ? '<span style="background: #fef2f2; color: #ef4444; padding: 4px 10px; border-radius: 20px; font-weight: 600; font-size: 11px; letter-spacing: 0.3px;"><i class="fe fe-arrow-up me-1"></i>' + e.log_jumlah + ' ' + e.unit_name + '</span>' 
                                : '<span style="color: #cbd5e1;">-</span>'}
                        </td>
                        <td style="width:12%; padding: 14px 24px;" class="text-center">${saldoText}</td>
                    </tr>
                `;
            })
            .join("");
    }

    function setLogFooterLoading(show) {
        $("#add_stock_supplies #tableLog tr.row-log-more").remove();
        if (!show) return;
        $("#add_stock_supplies #tableLog tbody").append(`
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
            $("#add_stock_supplies #tableLogScroll").scrollTop(0);
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
                log_type: 2,
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
                        $("#add_stock_supplies #tableLog tbody").html(`
                            <tr class="empty-log">
                                <td colspan="7" class="text-center text-muted py-4">
                                    Bahan ini belum ada riwayat perubahan stok di gudang ini
                                </td>
                            </tr>
                        `);
                        return;
                    }
                    $("#add_stock_supplies #tableLog tbody").html(renderLogRows(rows));
                    setTimeout(maybeFillLogViewport, 50);
                    return;
                }

                if (rows.length) {
                    $("#add_stock_supplies #tableLog tbody").append(renderLogRows(rows));
                    setTimeout(maybeFillLogViewport, 50);
                }
            },
            error: function (xhr) {
                if (xhr.statusText === "abort") return;
                logLazy.loading = false;
                setLogFooterLoading(false);
                if (handlePermissionError(xhr)) return;
                console.error("Gagal load:", xhr);
                if (!append) {
                    setHistoryLogLoading(false);
                    $("#add_stock_supplies #tableLog tbody").html(`
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

    $(document).on("shown.bs.modal", "#add_stock_supplies", function () {
        bindLogScroll();
    });

    $(document).on("change", "#add_stock_supplies #start_date, #add_stock_supplies #end_date", function () {
        dates = [
            $("#add_stock_supplies #start_date").val(),
            $("#add_stock_supplies #end_date").val(),
        ];
        getLog(activeId);
    });

    $(document).on("click", "#add_stock_supplies .btn-clear", function () {
        dates = null;
        $("#add_stock_supplies #start_date").val("");
        $("#add_stock_supplies #end_date").val("");
        getLog(activeId);
    });
