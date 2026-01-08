    var mode=1;
    var table;
    $(document).ready(function(){
        inisialisasi();
        refreshStockOpname();
    });
    
    function inisialisasi() {
        table = $('#tableStockOpname').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Cari",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "stob_date"},
                { data: "staff_name" },
                { data: "stob_id_text" },
                { data: "action", class: "d-flex align-items-center" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });
    }

    function refreshStockOpname() {
        $.ajax({
            url: "/getStockOpnameBahan",
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                console.log(e);
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].stob_date = moment(e[i].stob_date).format('D MMM YYYY');
                    e[i].stob_id_text = "SPN"+(e[i].stob_id+"").padStart(3,"0");
                    e[i].action = `
                        <a href="/detailStockOpnameBahan/${e[i].stob_id}" class="me-2 btn-action-icon p-2 btn_view" data-id="${e[i].stop_id}" data-bs-target="#view-opname">
                            <i class="fe fe-eye"></i>
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
