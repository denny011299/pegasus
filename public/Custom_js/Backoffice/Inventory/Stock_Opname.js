    var mode=1;
    var table;
    $(document).ready(function(){
        inisialisasi();
        refreshStockOpname();
    });
    
    $(document).on('click','.btnAdd',function(){
        mode=1;
        $('#add_category .modal-title').html("Create Category");
        $('#add_category input').val("");
        $('.is-invalid').removeClass('is-invalid');
        $('#add_category').modal("show");
    });
    
    function inisialisasi() {
        table = $('#tableStockOpname').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Search",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "stop_date" },
                { data: "stop_pic" },
                { data: "stop_category" },
                { data: "stop_id" },
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
                    e[i].stop_date = moment(e[i].created_at).format('D MMM YYYY');
                    e[i].action = `
                        <a href="/detailStockOpname/${e[i].stop_id}" class="me-2 btn-action-icon p-2 btn_view" data-id="${e[i].stop_id}" data-bs-target="#view-opname">
                            <i data-feather="view" class="fe fe-eye"></i>
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