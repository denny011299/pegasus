    var mode=1;
    var tableLow, tableOut;

    $(document).ready(function(){
        var whName = typeof getActiveWarehouseName === "function" ? getActiveWarehouseName() : null;
        $("#stock-alert-supplies-wh-label").text(
            whName ? ("Gudang aktif: " + whName) : "Pilih gudang aktif di header terlebih dahulu"
        );
        inisialisasi();
        refreshStockAlert();
        
    });
    
    function inisialisasi() {
        tableLow = $('#tableStockAlertLow').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            lengthMenu: [10, 25, 50, 100],
            ordering: true,
            scrollX: true,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Cari Stok (Rendah)",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "supplies_name", width: "35%" },
                { data: "supplies_alert_text", width: "20%" },
                { data: "minim_order", width: "20%" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });

        tableOut = $('#tableStockAlertOut').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            lengthMenu: [10, 25, 50, 100],
            ordering: true,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Cari Stok (Habis)",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            autoWidth: false,
            columns: [
                { data: "supplies_name", width: "35%" },
                { data: "supplies_alert_text", width: "20%" },
                { data: "minim_order", width: "20%" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });
    }

    function refreshStockAlert() {
        var warehouseId = typeof getActiveWarehouseId === "function" ? getActiveWarehouseId() : null;
        if (!warehouseId) {
            tableLow.clear().draw();
            tableOut.clear().draw();
            return;
        }
        $.ajax({
            url: "/getStockAlertSupplies",
            method: "get",
            data:{
                mode:mode,
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
                    item.supplies_alert_text =
                        item.reorder_point + " " + item.default_unit +
                        `<div class="small text-muted">Rata-rata: ${formatLeadTimeQty(item.avg_daily)}/hari · Lead time: ${item.lead_time_days} hari · Safety: ${formatLeadTimeQty(item.safety_stock)}</div>`;
                    
                    var habis = 1;
                    if (item.stock && item.stock.length) {
                        item.stock.forEach((element, index) => {
                            if (item.supplies_default_unit == element.unit_id) {
                                def = index;
                            }
                            if (element.ss_stock > 0) {
                                habis = -1;
                            }
                        });
                    }

                    item.habis = habis;

                    // tukar default variant ke index 0
                    if (def > 0 && item.stock && item.stock.length) {
                        let tmp = item.stock[0];
                        item.stock[0] = item.stock[def];
                        item.stock[def] = tmp;
                    }
                    
                    // item.product = `<img src="${public+item.stal_image}" class="me-2" style="width:30px">`+item.stal_name;
                    // item.min_order = `${item.supplies_alert - item.supplies_variant_stock} ${item.product_unit}`;
                    var sas =
                        roleIconEdit(
                            "Peringatan Stok Bahan Mentah",
                            "me-2 btn-action-icon p-2 btn_edit",
                            'data-id="' + item.product_id + '"'
                        ) +
                        roleIconDelete(
                            "Peringatan Stok Bahan Mentah",
                            "p-2 btn-action-icon btn_delete",
                            'data-id="' +
                                item.product_id +
                                '" href="javascript:void(0);"'
                        );
                    item.action =
                        sas ||
                        '<span class="text-muted small">—</span>';
                    item.minim_order = `${formatLeadTimeQty(item.recommended_order)} ${item.default_unit}`;

                });
                console.log(e);
                
                let stockLow = e.filter(item => item.current_stock <= item.reorder_point && item.habis == -1);
                let stockOut = e.filter(item => item.habis==1);

                tableLow.clear().rows.add(stockLow).draw();
                tableOut.clear().rows.add(stockOut).draw();
                $("#total_low").text(stockLow.length);
                $("#total_out").text(stockOut.length);
                
                feather.replace(); // Biar icon feather muncul lagi
            },
            error: function (err) {
                if (handlePermissionError(err)) return;
                console.error("Gagal load:", err);
            }
        });
    }

    function formatLeadTimeQty(value) {
        var number = parseFloat(value) || 0;
        return Number.isInteger(number) ? number.toString() : number.toFixed(2).replace(/\.?0+$/, "");
    }
