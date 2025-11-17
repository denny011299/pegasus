
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
        console.log(dates);
        refreshBahanBaku();
    })

    $(document).on('click', '.btn-clear', function(){
        dates = null;
        $('#start_date').val();
        $('#end_date').val("");
        refreshBahanBaku();
    })

    
    function inisialisasi() {
        table = $('#tableBahanBaku').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
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
                { data: "supplies_name" },
                { data: "kode_produksi" },
                { data: "qtyPemakaian" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });
    }

    function refreshBahanBaku() {
        $.ajax({
            url: "/getPemakaian",
            method: "get",
            data: {
                date: dates,
            },
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                table.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].date = moment(e[i].cash_date).format('D MMM YYYY');
                    
                }

                table.rows.add(e).draw();
                feather.replace(); // Biar icon feather muncul lagi
            },
            error: function (err) {
                console.error("Gagal load kas:", err);
            }
        });
    }
