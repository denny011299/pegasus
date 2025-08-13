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
                searchPlaceholder: "Search Stock Alert (Low)",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "product" },
                { data: "stal_category" },
                { data: "stal_sku" },
                { data: "stal_stock" },
                { data: "stal_qty" },
                { data: "action", class: "d-flex align-items-center" },
            ]
        });

        tableOut = $('#tableStockAlertOut').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Search Stock Alert (Out)",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            autoWidth: false,
            columns: [
                { data: "product", class: "width: 12%" },
                { data: "stal_category", class: "width: 18%" },
                { data: "stal_sku", class: "width: 15%" },
                { data: "stal_stock", class: "width: 15%" },
                { data: "stal_qty", class: "width: 15%" },
                { data: "action", class: "d-flex align-items-center width: 25%" },
            ]
        });
    }

    function refreshStockAlert() {
        $.ajax({
            url: "/getStockAlert",
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                console.log(e);
                // Manipulasi data sebelum masuk ke tabel
                e.forEach(item => {
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

                let stockLow = e.filter(item => item.stal_stock > 0);
                let stockOut = e.filter(item => item.stal_stock == 0);
                tableLow.clear().rows.add(stockLow).draw();
                tableOut.clear().rows.add(stockOut).draw();
                
                feather.replace(); // Biar icon feather muncul lagi
            },
            error: function (err) {
                console.error("Gagal load:", err);
            }
        });
    }