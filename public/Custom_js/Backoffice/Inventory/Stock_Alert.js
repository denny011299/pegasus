    var mode=1;
    var tableLow, tableOut;

    $(document).ready(function(){
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
            bFilter: true,
            sDom: 'fBtlpi',
            lengthMenu: [10, 25, 50, 100],
            ordering: true,
            autoWidth: false,
            scrollX: true,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: searchPlaceholder,
                info: "_START_ - _END_ of _TOTAL_ items",
                emptyTable: "Tidak ada data stok",
                zeroRecords: "Data stok tidak ditemukan",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "product_name_text", width: "26%", defaultContent: "—" },
                { data: "product_category", width: "12%", defaultContent: "—" },
                { data: "product_variant_sku", width: "12%", defaultContent: "—" },
                { data: "min_order_text", width: "14%", defaultContent: "—" },
                { data: "product_alert_text", width: "30%", defaultContent: "—" },
            ],
            initComplete: function (settings) {
                prepareStockAlertFilter(settings);
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
            tableLow.clear().draw();
            tableOut.clear().draw();
            return;
        }
        $.ajax({
            url: "/getStockAlert",
            method: "get",
            data:{
                mode: mode,
                warehouse_id: warehouseId
            },
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                // Manipulasi data sebelum masuk ke tabel
                console.log("data");
                e.forEach((item,index) => {
                    var def = -1;
                    item.product_name_text = item.product_name + " " + item.product_variant_name;
                    item.product_alert_text =
                        item.reorder_point + " " + item.product_unit +
                        `<div class="small text-muted">Rata-rata: ${formatLeadTimeQty(item.avg_daily)}/hari · Lead time: ${item.lead_time_days} hari · Safety: ${formatLeadTimeQty(item.safety_stock)}</div>`;
                    // Pemesanan Min. = max(0, Peringatan Stok − Stok saat ini)
                    var minOrderQty = Math.max(
                        0,
                        (parseFloat(item.product_variant_alert) || 0) - (parseFloat(item.current_stock) || 0)
                    );
                    item.min_order_text =
                        formatLeadTimeQty(minOrderQty) + " " + (item.product_unit || "");

                    var habis = 1;
                    if (item.stock && item.stock.length) {
                        item.stock.forEach((element,index) => {
                            if(item.unit_id == element.unit_id){
                                def=index;
                            }
                            if(element.ps_stock>0) {
                                habis=-1;
                            }
                        });
                    }
                    item.habis=habis;

                    if(def>0 && item.stock && item.stock.length){
                        //default dituker ke 0
                        var tmp = item.stock[0];
                        item.stock[0] = item.stock[def];
                        item.stock[def] =tmp;
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
                            'data-id="' +
                                item.product_id +
                                '" href="javascript:void(0);"'
                        );
                    item.action =
                        sa ||
                        '<span class="text-muted small">—</span>';
                });
                console.log(e);
                
                // Stok rendah = menyentuh/melewati Peringatan Stok manual (ps_alert_stock).
                // Kolom "Stok Minimum Rekomendasi" tetap rumus lead time (reorder_point).
                let stockLow = e.filter(item =>
                    item.current_stock <= (parseFloat(item.product_variant_alert) || 0) &&
                    item.habis == -1
                );
                let stockOut = e.filter(item => item.habis==1);

                tableLow.clear().rows.add(stockLow).draw();
                tableOut.clear().rows.add(stockOut).draw();
                tableLow.columns.adjust();
                if ($("#out").hasClass("active")) {
                    tableOut.columns.adjust();
                }
                $("#total_low").text(stockLow.length);
                $("#total_out").text(stockOut.length);
                
                feather.replace(); // Biar icon feather muncul lagi
            },
            error: function (err) {
                console.error("Gagal load:", err);
            }
        });
    }

    function formatLeadTimeQty(value) {
        var number = parseFloat(value) || 0;
        return Number.isInteger(number) ? number.toString() : number.toFixed(2).replace(/\.?0+$/, "");
    }
