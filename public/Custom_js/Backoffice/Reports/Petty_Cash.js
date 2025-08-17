    var mode=1;
    var table;
    $(document).ready(function(){
        inisialisasi();
        refreshPettyCash();
    });
    
    // $(document).on('click','.btnAdd',function(){
    //     mode=1;
    //     $('#add_cash .modal-title').html("Create Cash");
    //     $('#add_cash input').val("");
    //     $('.is-invalid').removeClass('is-invalid');
    //     $('#add_cash').modal("show");
    // });
    
    function inisialisasi() {
        table = $('#tablePettyCash').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Search Petty Cash",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "date" },
                { data: "keterangan" },
                { data: "masuk" },
                { data: "keluar1" },
                { data: "keluar2" },
                { data: "saldo" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });
    }

    function refreshPettyCash() {
        $.ajax({
            url: "/getPettyCash",
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                table.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].date = moment(e[i].tanggal).format('D MMM YYYY');
                    e[i].action = `
                        <a class="me-2 btn-action-icon p-2 btn_view" data-bs-target="#view-opname">
                            <i data-feather="view" class="fe fe-eye"></i>
                        </a>
                    `;
                }

                table.rows.add(e).draw();
                feather.replace(); // Biar icon feather muncul lagi
            },
            error: function (err) {
                console.error("Gagal load kategori:", err);
            }
        });
    }