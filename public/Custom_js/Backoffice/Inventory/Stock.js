    var mode=1;
    var table;
    $(document).ready(function(){
        inisialisasi();
        refreshStock();
    });
    
    function inisialisasi() {
        table = $('#tableStock').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Search Product",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "product_variant_sku" },
                { data: "product_name" },
                { data: "product_variant_name" },
                { data: "product_category" },
                { data: "product_variant_stock_text" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });
    }

    function refreshStock() {
        $.ajax({
            url: "/getStock",
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }

                table.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].product_variant_stock_text = `${e[i].product_variant_stock} ${e[i].product_unit}`;
                    e[i].action = `
                        <a class="me-2 btn-action-icon p-2 btn_edit" data-id="${e[i].stk_id}" data-bs-target="#edit-category">
                            <i data-feather="edit" class="feather-edit"></i>
                        </a>
                        <a class="p-2 btn-action-icon btn_delete" data-id="${e[i].stk_id}" href="javascript:void(0);">
                            <i data-feather="trash-2" class="feather-trash-2"></i>
                        </a>
                    `;
                }

                table.rows.add(e).draw();
                feather.replace(); // Biar icon feather muncul lagi
            },
            error: function (err) {
                console.error("Gagal load:", err);
            }
        });
    }
