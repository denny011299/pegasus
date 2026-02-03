    var mode=1;
    var table;
    let dates = null;
    $(document).ready(function(){
        inisialisasi();
        refreshProduction();
    });
    
    
    function inisialisasi() {
        table = $('#tableReportProduction').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            lengthMenu: [10, 25, 50, 100],
            ordering: true,
            searching: false,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Cari Kas",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "date" },
                { data: "product_name" },
                { data: "qty" },
                { data: "status_production" },
                { data: "created" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });
    }

    function refreshProduction() {
        $.ajax({
            url: "/getProduction",
            method: "get",
            data: {
                date: dates,
                report: 1
            },
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }

                table.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].date = moment(e[i].production_date).format('D MMM YYYY');
                    e[i].qty = `${e[i].production_qty} ${e[i].unit_name}`;
                    if (e[i].status == 0) e[i].status_production = `<span class="badge bg-danger-light">Dibatalkan</span>`;
                    else if (e[i].status == 1) e[i].status_production = `<span class="badge bg-success-light">Selesai</span>`;
                    e[i].created ="Admin"
                }

                table.rows.add(e).draw();
                feather.replace(); // Biar icon feather muncul lagi
                dates = null;
            },
            error: function (err) {
                console.error("Gagal load produksi:", err);
            }
        });
    }

    $(document).on('click', '.btn-filter', function(){
        dates = [];
        var start = $('#start_date').val();
        var end = $('#end_date').val();
        dates.push(start);
        dates.push(end);
        console.log(dates);
        refreshProduction();
    })

    $(document).on('click', '.btn-clear', function(){
        dates = null;
        $('#start_date').val("");
        $('#end_date').val("");
        refreshProduction();
    })