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
            ordering: false,
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
                { data: "sto_date"},
                { data: "staff_name" },
                { data: "sto_code" },
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
            url: "/getStockOpname",
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                console.log(e);
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].sto_date = moment(e[i].sto_date).format('D MMM YYYY');
                    e[i].sto_id_text = "SPN"+(e[i].sto_id+"").padStart(3,"0");
                    e[i].action = `
                        <a href="/detailStockOpname/${e[i].sto_id}" class="me-2 btn-action-icon p-2 btn_view" data-id="${e[i].stop_id}" data-bs-target="#view-opname">
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

    
function loadCategory() {
    $.ajax({
        url: "/admin/getCategory",
        method: "GET",
        success: function (data) {
            data = JSON.parse(data);
            if (data) {
                $("#kategori").empty();
                $("#kategori").append(
                    $("<option>", {
                        value: -1,
                        text: "Semua Kategori",
                    })
                );
                data.forEach((element) => {
                    $("#kategori").append(
                        $("<option>", {
                            value: element.category_id,
                            text: element.category_name,
                        })
                    );
                });
            }
        },
        error: function (e) {
            console.log(e);
        },
    });
}