
    const hariIni = moment().format('DD-MM-YYYY');
    var dates = [];

    $(document).ready(function(){
        inisialisasi();
        $('#start_date').val(hariIni);
        $('#end_date').val(hariIni);
        $('.btn-filter').click();
    });

    $(document).on('click', '.btn-filter', function(){
        dates = [];
        var start = $('#start_date').val();
        var end = $('#end_date').val();
        dates.push(start);
        dates.push(end);

        refreshProduct();
    })

    $(document).on('click', '.btn-clear', function(){
        dates = null;
        $('#start_date').val();
        $('#end_date').val("");
        refreshProduct();
    })

    
    function inisialisasi() {
        table = $('#tableProduct').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Cari Product Return",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "pi_date" },
                { data: "pr_name" },
                { data: "pi_qty" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });
    }

    function refreshProduct() {
        $.ajax({
            url: "/getProductIssue",
            method: "get",
            data: {
                date: dates,
                tipe_return: 1,
                pi_type: 1,
            },
            success: function (e) {
                console.log(e);

                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                table.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].pi_date = moment(e[i].pi_date).format('D MMM YYYY');
                    e[i].pi_qty  =  e[i].pi_qty +" "+e[i].unit_name;
                }

                table.rows.add(e).draw();
                feather.replace(); // Biar icon feather muncul lagi
            },
            error: function (err) {
                console.error("Gagal load kas:", err);
            }
        });
    }
