    var mode=1;
    var table;
    $(document).ready(function(){
        inisialisasi();
        refreshStockOpname();
    });
    
    function inisialisasi() {
    if ($.fn.DataTable.isDataTable('#tableStockOpname')) {
        table = $('#tableStockOpname').DataTable();
        return;
    }

        table = $('#tableStockOpname').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            lengthMenu: [10, 25, 50, 100],
            ordering: false,
            deferRender: true,
            scrollX: true,
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
                { data: "staff_name", defaultContent: "-" },
                { data: "sto_code" },
                { data: "created_by_name", defaultContent: "-" },
                { data: "acc_by_name", defaultContent: "-" },
                { data: "status_text", defaultContent: "-" },
                { data: "action", defaultContent: "-", class: "text-center align-middle" },
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
            const processedRows = e.map((item) => {
                const statusTextMap = {
                    1: `<span class="badge bg-secondary" style="font-size: 12px">Menunggu</span>`,
                    2: `<span class="badge bg-success" style="font-size: 12px">Disetujui</span>`,
                    3: `<span class="badge bg-danger" style="font-size: 12px">Ditolak</span>`,
                };

                return {
                    ...item,
                    sto_date: item.sto_date ? moment(item.sto_date).format('D MMM YYYY') : '-',
                    staff_name: item.staff_name || '-',
                    sto_code: item.sto_code || '-',
                    created_by_name: item.created_by_name || '-',
                    acc_by_name: item.acc_by_name || '-',
                    status_text: statusTextMap[item.status] || '-',
                    action: `
                        <a href="/generateStockOpname/${item.sto_id}" class="me-2 btn-action-icon p-2 btn_download" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" title="Download Stock Opname">
                            <i class="fe fe-file-text"></i>
                        </a>
                        <a href="/detailStockOpname/${item.sto_id}" class="me-2 btn-action-icon p-2 btn_view" data-id="${item.sto_id}"
                            data-bs-target="#view-opname" title="Detail Stock Opname">
                            <i class="fe fe-eye"></i>
                        </a>
                    `,
                };
            });

            table.clear();
            table.rows.add(processedRows);
            table.draw(false);
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