    var mode = 1;
    var table = null;
    var dates = null;
    var activeId = 0;
    var warehouseWarnShown = false;
    var viewMode = "main";
    var canViewSafetyStock = false;
    var stockXhr = null;

    function resolveSafetyStockAccess() {
        return typeof hasAccessAction === "function"
            && (hasAccessAction("Safety Stock", "view") || hasAccessAction("Safety Stock", "edit"));
    }

    $(document).ready(function () {
        canViewSafetyStock = resolveSafetyStockAccess();
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
            pageLength: 25,
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
                processing: "Memuat data...",
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
        if (!canViewSafetyStock) return;
        if ($table.find("thead th.col-safety").length) return;
        var cls = centered ? "col-safety text-center" : "col-safety";
        $table.find("thead tr").append('<th class="' + cls + '">Safety Stock</th>');
    }

    function bindRowClick($table) {
        $table.find("tbody").off("click.stockRow").on("click.stockRow", "tr", function () {
            if (!table) return;
            var data = table.row(this).data();
            if (!data) return;
            activeId = data.product_variant_id;
            getLog(data.product_variant_id);
        });
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
    }

    function renderRetailUnits(units, field) {
        if (!units || !units.length) {
            return '<div class="sretail-list-col">-</div>';
        }
        var divs = units
            .map(function (u) {
                var text = "-";
                if (field === "unit") {
                    text = u.unit_name || "-";
                } else if (field === "stock") {
                    text = u.ps_stock_text != null ? String(u.ps_stock_text) : "0";
                } else if (field === "safety") {
                    text =
                        u.ps_safety_stock_text != null
                            ? String(u.ps_safety_stock_text)
                            : u.ps_safety_stock != null
                              ? String(u.ps_safety_stock)
                              : "0";
                }
                var qtyCls = field === "unit" ? "" : " qty-item";
                return (
                    '<div class="sretail-list-item' +
                    qtyCls +
                    '">' +
                    escapeHtml(text) +
                    "</div>"
                );
            })
            .join("");
        return '<div class="sretail-list-col">' + divs + "</div>";
    }

    // =========================
    // VIEW: Gudang Utama
    // =========================
    function inisialisasiMain() {
        $("#tableStock-wrap").show();
        $("#tableStockRetail-wrap").hide();
        ensureSafetyHeader($("#tableStock"), false);

        var columns = [
            { 
                data: "product_variant_sku", 
                width: canViewSafetyStock ? "12%" : "15%",
                render: function (data) {
                    return `<span class="badge" style="background:#f1f5f9;color:#334155;border:1px solid #cbd5e1;padding:6px 10px;">${data || "-"}</span>`;
                }
            },
            { 
                data: "pr_name", 
                width: canViewSafetyStock ? "16%" : "20%",
                render: function(data, type, row) {
                    if (type !== "display") return data;
                    var name = data || "-";
                    var initials = name.substring(0, 2).toUpperCase();
                    var words = name.trim().split(/\s+/);
                    if (words.length >= 2) {
                        initials = (words[0][0] + words[1][0]).toUpperCase();
                    }
                    var avatarHtml = row.image_url
                        ? `<img src="${row.image_url}" alt="${escapeHtml(name)}" style="width:32px;height:32px;border-radius:8px;object-fit:cover;border:1px solid #e2e8f0;">`
                        : `<div style="width:32px;height:32px;border-radius:8px;background:#eff6ff;border:1px solid #bfdbfe;color:#2563eb;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:12px;flex-shrink:0;">${escapeHtml(initials)}</div>`;
                    
                    return `<div style="display:flex;align-items:center;gap:10px;">
                                ${avatarHtml}
                                <span class="fw-semibold text-dark">${escapeHtml(name)}</span>
                            </div>`;
                }
            },
            { 
                data: "product_variant_name", 
                width: canViewSafetyStock ? "16%" : "20%",
                render: function(data) {
                    return `<span class="text-muted fw-medium">${escapeHtml(data || "-")}</span>`;
                }
            },
            { 
                data: "product_category", 
                width: canViewSafetyStock ? "12%" : "15%",
                render: function(data) {
                    return `<div style="display:flex;align-items:center;gap:6px;">
                                <i class="fe fe-tag text-muted" style="font-size:14px;"></i>
                                <span class="text-dark">${escapeHtml(data || "-")}</span>
                            </div>`;
                }
            },
            {
                data: "warehouse_name",
                width: canViewSafetyStock ? "12%" : "15%",
                orderable: false,
                searchable: false,
                render: function(data) {
                    return `<div style="display:flex;align-items:center;gap:6px;">
                                <i class="fe fe-home text-muted" style="font-size:14px;"></i>
                                <span class="fw-semibold text-dark">${escapeHtml(data || "-")}</span>
                            </div>`;
                }
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
                class: "fw-bold",
                width: "16%",
                orderable: false,
                searchable: false,
            });
        }

        var opts = dtBaseOptions("Cari Produk", [[1, "asc"]]);
        opts.columns = columns;
        opts.initComplete = function () {
            moveSearchFilter();
            $("#tableStock-wrap").removeClass("dt-pending").addClass("dt-ready");
        };

        table = $("#tableStock").DataTable(opts);
        bindRowClick($("#tableStock"));
    }

    // =========================
    // VIEW: Gudang Eceran
    // =========================
    function inisialisasiRetail() {
        $("#tableStockRetail-wrap").show();
        $("#tableStock-wrap").hide();
        ensureSafetyHeader($("#tableStockRetail"), true);

        var columns = [
            {
                data: null,
                width: canViewSafetyStock ? "35%" : "40%",
                orderable: true,
                render: function (data, type, row) {
                    if (type !== "display") return row.pr_name || "";
                    var words = (row.pr_name || "P").trim().split(/\s+/);
                    var initials =
                        words.length >= 2
                            ? (words[0][0] + words[1][0]).toUpperCase()
                            : (row.pr_name || "P").substring(0, 2).toUpperCase();

                    var avatarHtml = row.image_url
                        ? '<img src="' + row.image_url + '" alt="' + escapeHtml(row.pr_name || "") + '">'
                        : '<span class="sretail-avatar-initials">' + escapeHtml(initials) + "</span>";

                    return (
                        '<div class="sretail-product-cell">' +
                        '<div class="sretail-avatar">' +
                        avatarHtml +
                        "</div>" +
                        '<div class="sretail-product-info">' +
                        '<span class="sretail-product-name">' +
                        escapeHtml(row.pr_name || "-") +
                        "</span>" +
                        '<span class="sretail-product-meta">' +
                        escapeHtml(row.product_category || "-") +
                        "</span>" +
                        "</div>" +
                        "</div>"
                    );
                },
            },
            {
                data: "units",
                orderable: false,
                searchable: false,
                width: canViewSafetyStock ? "15%" : "20%",
                render: function (units) {
                    return renderRetailUnits(units, "unit");
                },
            },
            {
                data: "units",
                orderable: false,
                searchable: false,
                className: "text-center",
                width: canViewSafetyStock ? "25%" : "40%",
                render: function (units) {
                    return renderRetailUnits(units, "stock");
                },
            },
        ];

        if (canViewSafetyStock) {
            columns.push({
                data: "units",
                orderable: false,
                searchable: false,
                className: "text-center",
                width: "25%",
                render: function (units) {
                    return renderRetailUnits(units, "safety");
                },
            });
        }

        var opts = dtBaseOptions("Cari barang...", [[0, "asc"]]);
        opts.columns = columns;
        opts.initComplete = function () {
            moveSearchFilter();
            $("#tableStockRetail-wrap").removeClass("dt-pending").addClass("dt-ready");
        };

        table = $("#tableStockRetail").DataTable(opts);
        bindRowClick($("#tableStockRetail"));
    }

    function refreshStock() {
        if (table) {
            table.ajax.reload(null, false);
        }
    }

    function getLog(id) {
        if (!id) return;
        $.ajax({
            url: "/getLog",
            method: "get",
            data: {
                log_type: 1,
                log_item_id: id,
                date: dates,
            },
            success: function (e) {
                viewHistory(e);
            },
            error: function (e) {
                console.error("Gagal load:", e);
            },
        });
    }

    function viewHistory(data) {
        $("#tableLog tr.row-log").remove();
        $("#tableLog tr.empty-log").remove();
        if (data.length > 0) {
            $(".empty-log").remove();
            var rowsHtml = data
                .map(function (e) {
                    return `
                    <tr class="row-log" data-id="${e.log_id}">
                        <td style="width:15%; color: #64748b; font-size: 13px;">${moment(e.log_date).format("D MMM YYYY, HH:mm")}</td>
                        <td style="width:15%; font-weight: 500;">${e.staff_name}</td>
                        <td style="width:15%; color: #3b82f6; font-family: monospace;">${e.log_kode}</td>
                        <td style="width:25%">${e.log_notes}</td>
                        <td style="width:15%" class="text-center">
                            ${e.log_category == 1 ? '<span class="badge px-2 py-1" style="background-color: #dcfce7; color: #166534; font-size: 12px; font-weight: 600; border: 1px solid #bbf7d0;">+ ' + e.log_jumlah + ' ' + e.unit_name + '</span>' : '<span style="color: #cbd5e1;">-</span>'}
                        </td>
                        <td style="width:15%" class="text-center">
                            ${e.log_category == 2 ? '<span class="badge px-2 py-1" style="background-color: #fee2e2; color: #991b1b; font-size: 12px; font-weight: 600; border: 1px solid #fecaca;">- ' + e.log_jumlah + ' ' + e.unit_name + '</span>' : '<span style="color: #cbd5e1;">-</span>'}
                        </td>
                    </tr>
                `;
                })
                .join("");
            $("#tableLog tbody").append(rowsHtml);
        } else {
            $("#tableLog tbody").append(`
                <tr class="empty-log">
                    <td colspan="6" class="text-center text-muted py-4">
                        Produk ini belum ada riwayat perubahan stok
                    </td>
                </tr>
            `);
        }

        $("#add_stock_product .modal-title").html("Lihat Histori Produk");
        $("#add_stock_product").modal("show");
    }

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
