    var mode=1;
    var tableProfit, tableLoss;
    $(document).ready(function(){
        inisialisasi();
        refreshProfitLoss();
    });
    
    function inisialisasi() {
        tableProfit = $('#tableProfit').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
            searching: false,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Cari Laba (Low)",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "Income" },
                { data: "Name" },
                { data: "Year1" },
                { data: "Year2" },
                { data: "Year3" },
            ]
        });

        tableLoss = $('#tableLoss').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
            searching: false,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Cari Rugi",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            autoWidth: false,
            columns: [
                { data: "Expenses" },
                { data: "Name" },
                { data: "Year1" },
                { data: "Year2" },
                { data: "Year3" },
            ]
        });
    }

    function refreshProfitLoss() {
        $.ajax({
            url: "/getProfit",
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                console.log(e);
                // Manipulasi data sebelum masuk ke tabel
                e.forEach(item => {
                    
                });
                tableProfit.clear().rows.add(e).draw();
            },
            error: function (err) {
                console.error("Gagal load:", err);
            }
        });
        $.ajax({
            url: "/getLoss",
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                console.log(e);
                // Manipulasi data sebelum masuk ke tabel
                e.forEach(item => {
                    
                });

                tableLoss.clear().rows.add(e).draw();
            },
            error: function (err) {
                console.error("Gagal load:", err);
            }
        });
    }