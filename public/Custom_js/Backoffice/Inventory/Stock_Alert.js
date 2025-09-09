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
                { data: "product_alert" },
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
                { data: "product_alert" },
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
                console.log(e);
                // Manipulasi data sebelum masuk ke tabel
                e.forEach(item => {
                    item.product_name_text = item.product_name + " " +item.product_variant_name;
                    item.product_variant_stock_text = item.product_variant_stock +" "+item.product_unit;
                    item.product = `<img src="${public+item.stal_image}" class="me-2" style="width:30px">`+item.stal_name;
                    item.action = `
                        <a class="me-2 btn-action-icon p-2 btn_edit" data-id="${item.product_id}">
                            <i data-feather="edit"></i>
                        </a>
                        <a class="p-2 btn-action-icon btn_delete" data-id="${item.product_id}" href="javascript:void(0);">
                            <i data-feather="trash-2"></i>
                        </a>
                    `;
                });

                let stockLow = e.filter(item => item.product_variant_stock <= item.product_alert&&  item.product_variant_stock>0);
                let stockOut = e.filter(item => item.product_variant_stock == 0);

                tableLow.clear().rows.add(stockLow).draw();
                tableOut.clear().rows.add(stockOut).draw();
                $("#total_low").text(stockLow.length);
                $("#total_out").text(stockOut.length);
                console.log(stockLow);
                
                feather.replace(); // Biar icon feather muncul lagi
            },
            error: function (err) {
                console.error("Gagal load:", err);
            }
        });
    }