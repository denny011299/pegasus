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
            searching: false,
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
                { data: "date" },
                { data: "date_due_date" },
                { data: "po_code" },
                { data: "poi_code" },
                { data: "supplier_name" },
                { data: "poi_total_text" },
                { data: "status_text" },
                { data: "action", class: "d-flex align-items-center" },
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
            url: "/getPoInvoice",
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                console.log(e);
                tablePayables.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].date = moment(e[i].poi_date).format('D MMM YYYY');
                    e[i].date_due_date = moment(e[i].poi_due).format('D MMM YYYY');
                    e[i].poi_total_text = formatRupiah(e[i].poi_total,"Rp.");
                    console.log(e[i].pembayaran);
                    
                    if (e[i].pembayaran == 0){
                        e[i].status_text = `<span class="badge bg-warning" style="font-size: 12px">Belum Terbayar</span>`;
                    } else if (e[i].pembayaran == 1){
                        e[i].status_text = `<span class="badge bg-success" style="font-size: 12px">Terbayar</span>`;
                    } else {
                        e[i].status_text = `<span class="badge bg-danger" style="font-size: 12px">Tertolak</span>`;
                    }
                    e[i].action = `
                        <a href="/PurchaseOrderDetailHutang/${e[i].po_id}" class="me-2 btn-action-icon p-2 btn_edit_invoice" >
                            <i class="fe fe-eye"></i>
                        </a>
                    `;
                }

                tablePayables.rows.add(e).draw();
                feather.replace(); // Biar icon feather muncul lagi
            },
            error: function (err) {
                console.error("Gagal load:", err);
            }
        });
    }