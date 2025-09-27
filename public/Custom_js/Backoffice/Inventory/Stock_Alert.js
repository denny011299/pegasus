    var mode=1;
    var tableLow, tableOut;
    $(document).ready(function(){
        inisialisasi();
        refreshStockAlert();
    });
    
    function inisialisasi() {
        tableLow = $('#tableStockAlertLow').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
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
                { data: "product_name_text" },
                { data: "product_category" },
                { data: "product_variant_sku" },
                { data: "product_variant_stock_text" },
                { data: "product_alert_text" },
                { data: "minim_order" },
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
                { data: "product_name_text" },
                { data: "product_category" },
                { data: "product_variant_sku" },
                { data: "product_variant_stock_text" },
                { data: "product_alert_text" },
                { data: "minim_order" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });
    }

    function refreshStockAlert() {
        $.ajax({
            url: "/getStockAlert",
            method: "get",
            data:{
                mode:mode
            },
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                // Manipulasi data sebelum masuk ke tabel
                e.forEach((item,index) => {
                    var def = -1;

                    item.product_name_text = item.product_name + " " +item.product_variant_name;
                    item.product_alert_text = item.product_alert+" " +item.product_unit;
                    
                    item.product_variant_stock_text="";
                    var habis = 1;
                    item.stock.forEach((element,index) => {
                        item.product_variant_stock_text += `${element.ps_stock} ${element.unit_name}`;
                        if(index<item.stock.length-1)item.product_variant_stock_text +=", ";
                        if(item.unit_id == element.unit_id){
                            def=index;
                        }
                        
                        if(element.ps_stock>0) {
                            habis=-1;
                            
                        }
                    });
                    if (item.product_variant_stock_text == "") item.product_variant_stock_text = "-"
                    item.habis=habis;

                    if(def>0){
                        //default dituker ke 0
                        var tmp = item.stock[0];
                        item.stock[0] = item.stock[def];
                        item.stock[def] =tmp;
                    }
                    
                    item.product = `<img src="${public+item.stal_image}" class="me-2" style="width:30px">`+item.stal_name;
                    item.min_order = `${item.product_alert - item.product_variant_stock} ${item.product_unit}`;
                    item.action = `
                        <a class="me-2 btn-action-icon p-2 btn_edit" data-id="${item.product_id}">
                            <i class="fe fe-edit"></i>
                        </a>
                        <a class="p-2 btn-action-icon btn_delete" data-id="${item.product_id}" href="javascript:void(0);">
                            <i class="fe fe-trash-2"></i>
                        </a>
                    `;
                    // Asumsi 'item' adalah objek produk lengkap dengan relasi dan stok.
                    // item.stock sudah diurutkan dari unit terbesar ke terkecil.

                    let stocks = item.stock?.[0]?.ps_stock || 0;
                    let unit_name = item?.stock[0]?.unit_name || "-";
                    if (item.relation.length <= 1) {
                        // Logika untuk produk dengan 1 varian atau tanpa relasi
                        let needed = Math.max(0, item.product_alert - stocks);
                        item.minim_order = needed + " " + unit_name;
                    } else {
                        // Logika untuk produk dengan banyak relasi/varian
                        // 1. Konversi semua stok ke satuan terkecil (base unit)
                        let totalStockInSmallestUnit = stocks;
                        
                        // Cari faktor konversi untuk setiap unit
                        let conversionFactors = {};
                        let tempFactor = 1;
                        for (let i = item.relation.length - 1; i >= 0; i--) {
                            tempFactor *= item.relation[i].pr_unit_value_2;
                            conversionFactors[item.relation[i].pr_unit_id_2] = tempFactor;
                        }
                        conversionFactors[item.product_unit_id] = 1;

                        for (const stockItem of item.stock) {
                            let factor = conversionFactors[stockItem.unit_id] || 1;

                            totalStockInSmallestUnit = stockItem.ps_stock + totalStockInSmallestUnit * factor;
                        }
                        
                        // 2. Konversi product_alert ke satuan terkecil
                        let totalAlertInSmallestUnit = item.product_alert;
                        // Asumsi: product_alert dalam satuan unit terbesar
                        for (const relation of item.relation) {
                            totalAlertInSmallestUnit *= relation.pr_unit_value_2;
                        }

                        // 3. Hitung selisih dalam satuan terkecil
                        let neededInSmallestUnit = totalAlertInSmallestUnit - totalStockInSmallestUnit;
                        console.log("totalAlertInSmallestUnit : "+totalAlertInSmallestUnit);
                        console.log("totalStockInSmallestUnit : "+totalStockInSmallestUnit);
                        console.log("neededInSmallestUnit : "+neededInSmallestUnit);
                        
                        // 4. Konversi balik kekurangan ke format satuan yang paling efisien
                        if (neededInSmallestUnit <= 0) {
                            item.minim_order = item.stock.map(s => `0 ${s.unit_name}`).join(", ");
                        } else {
                            let resultText = [];
                            let remainingNeeded = neededInSmallestUnit;
                            
                            // Loop dari relasi terbesar ke terkecil
                            for (let i = item.relation.length - 1; i >= 0; i--) {
                                const unitValue = item.relation[i].pr_unit_value_2;
                                const unitName = item.relation[i].pr_unit_name_2;
                                
                                let count = Math.floor(remainingNeeded % unitValue);
                                console.log(count);
                                
                                if (count > 0) {
                                    resultText.push(count + " " + unitName);
                                }
                                remainingNeeded  = Math.floor(remainingNeeded/unitValue);
                            }
                            
                            // Tambahkan sisa terakhir (unit terkecil)
                            if (remainingNeeded > 0) {
                                resultText.push(remainingNeeded + " " + item.product_unit);
                            }
                            
                            item.minim_order = resultText.reverse().join(", ");
                        }
                    }
                });
                
                let stockLow = e.filter(item => ((item.stock[0]?.ps_stock || 0) <= item.product_alert) && item.habis == -1);
                let stockOut = e.filter(item => item.habis==1);

                tableLow.clear().rows.add(stockLow).draw();
                tableOut.clear().rows.add(stockOut).draw();
                $("#total_low").text(stockLow.length);
                $("#total_out").text(stockOut.length);
                
                feather.replace(); // Biar icon feather muncul lagi
            },
            error: function (err) {
                console.error("Gagal load:", err);
            }
        });
    }
