    var mode=1;
    var tableReturn, tableDamage;
    $(document).ready(function(){
        inisialisasi();
        refreshProductIssues();
    });
    
    function inisialisasi() {
        tableReturn = $('#tableReturn').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Search Returned Product",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "product" },
                { data: "pi_sku" },
                { data: "date" },
                { data: "pi_qty" },
                { data: "pi_notes" },
                { data: "action", class: "d-flex align-items-center" },
            ]
        });

        tableDamage = $('#tableDamage').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Search Damaged Product",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            autoWidth: false,
            columns: [
                { data: "product", class: "width: 12%" },
                { data: "pi_sku", class: "width: 18%" },
                { data: "date", class: "width: 15%" },
                { data: "pi_qty", class: "width: 15%" },
                { data: "pi_notes", class: "width: 15%" },
                { data: "action", class: "d-flex align-items-center width: 25%" },
            ]
        });
    }

    function refreshProductIssues() {
        $.ajax({
            url: "/getProductIssue",
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                console.log(e);
                // Manipulasi data sebelum masuk ke tabel
                e.forEach(item => {
                    item.product = `<img src="${public+item.pi_image}" class="me-2" style="width:30px">`+item.pi_product;
                    item.date = moment(item.pi_date).format('D MMM YYYY');
                    item.action = `
                        <a class="me-2 btn-action-icon p-2 btn_edit" data-id="${item.product_id}">
                            <i data-feather="edit"></i>
                        </a>
                        <a class="p-2 btn-action-icon btn_delete" data-id="${item.product_id}" href="javascript:void(0);">
                            <i data-feather="trash-2"></i>
                        </a>
                    `;
                });

                let returnProduct = e.filter(item => item.pi_status == 'Return');
                let damageProduct = e.filter(item => item.pi_status == 'Damage');
                tableReturn.clear().rows.add(returnProduct).draw();
                tableDamage.clear().rows.add(damageProduct).draw();
                
                feather.replace(); // Biar icon feather muncul lagi
            },
            error: function (err) {
                console.error("Gagal load:", err);
            }
        });
    }