    var mode=1;
    var tablePayables, tableReceiveables;
    $(document).ready(function(){
        inisialisasi();
        refreshPayReceive();
    });
    
    function inisialisasi() {
        tablePayables = $('#tablePayables').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Cari Hutang",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: 'po_date' },
                { data: 'po_reference' },
                { data: 'po_invoice_number' },
                { data: 'po_name' },
                { data: 'po_total' },
                { data: 'status_bayar' },
                { data: "action", class: "d-flex align-items-center" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });

        tableReceiveables = $('#tableReceiveables').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Cari Piutang",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            autoWidth: false,
            columns: [
                { data: 'so_order_date' },
                { data: 'so_due_date' },
                { data: 'so_reference' },
                { data: 'so_invoice_number' },
                { data: 'so_name' },
                { data: 'so_total' },
                { data: 'status_bayar' },
                { data: 'action', class: "d-flex align-items-center" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });
    }

    function refreshPayReceive() {
        $.ajax({
            url: "/getPurchaseOrder",
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                console.log(e);
                // Manipulasi data sebelum masuk ke tabel
                e.forEach(item => {
                    if (item.po_status == 1){
                        item.status_bayar = `<span class="badge bg-warning" style="font-size: 12px">Belum Dibayar</span>`;
                    } else if (item.po_status == 2){
                        item.status_bayar = `<span class="badge bg-warning" style="font-size: 12px">Menunggu Pembayaran</span>`;
                    } else if (item.po_status == 3){
                        item.status_bayar = `<span class="badge bg-danger" style="font-size: 12px">Jatuh Tempo</span>`;
                    }
                    item.action = `
                        <a class="me-2 btn-action-icon p-2 btn_view" data-bs-target="#view-opname">
                            <i class="fe fe-eye"></i>
                        </a>
                    `;
                });
                tablePayables.clear().rows.add(e).draw();
            },
            error: function (err) {
                console.error("Gagal load:", err);
            }
        });
        $.ajax({
            url: "/getSalesOrder",
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                console.log(e);
                // Manipulasi data sebelum masuk ke tabel
                e.forEach(item => {
                    if (item.so_status == 1){
                        item.status_bayar = `<span class="badge bg-warning" style="font-size: 12px">Belum Dibayar</span>`;
                    } else if (item.so_status == 2){
                        item.status_bayar = `<span class="badge bg-warning" style="font-size: 12px">Menunggu Pembayaran</span>`;
                    } else if (item.so_status == 3){
                        item.status_bayar = `<span class="badge bg-danger" style="font-size: 12px">Jatuh Tempo</span>`;
                    }
                    item.action = `
                        <a class="me-2 btn-action-icon p-2 btn_view" data-bs-target="#view-opname">
                            <i class="fe fe-eye"></i>
                        </a>
                    `;
                });

                tableReceiveables.clear().rows.add(e).draw();
            },
            error: function (err) {
                console.error("Gagal load:", err);
            }
        });
    }