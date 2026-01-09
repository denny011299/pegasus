    var mode=1;
    var table;
    var dates = null;
    var activeId = 0;
    $(document).ready(function(){
        inisialisasi();
        refreshStock();
    });
    
    function inisialisasi() {
        table = $('#tableStock').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Cari Bahan Mentah",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "supplies_name" },
                { data: "supplies_variant_stock_text" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });
    }

    function refreshStock() {
        $.ajax({
            url: "/getStockSupplies",
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                console.log(e);
                
                table.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].supplies_variant_stock_text = "";
                    for (let j = 0; j < e[i].stock.length; j++) {
                        e[i].supplies_variant_stock_text += e[i].stock[j].ss_stock + " " + e[i].stock[j].unit_short_name;
                        if (j < e[i].stock.length - 1) {
                            e[i].supplies_variant_stock_text += " , ";
                        }
                    }
                    if(e[i].stock.length == 0){
                        e[i].supplies_variant_stock_text = "-";
                    }
                }

                table.rows.add(e).draw();
                feather.replace(); // Biar icon feather muncul lagi
            },
            error: function (err) {
                console.error("Gagal load:", err);
            }
        });
    }

    $(document).on('click', '#tableStock tbody tr', function(){
        let table = $('#tableStock').DataTable();
        let data = table.row(this).data();
        activeId = data.supplies_id;

        getLog(data.supplies_id);
    })

    function getLog(id){
        $.ajax({
            url: '/getLog',
            method: 'get',
            data: {
                log_type: 2,
                log_item_id: id,
                date: dates
            },
            success: function(e){
                viewHistory(e);
            },
            error: function(e){
                console.error("Gagal load:", e);
            }
        });
    }

    function viewHistory(data){
        $('#tableLog tr.row-log').remove();
        $('#tableLog tr.empty-log').remove();
        if (data.length > 0){
            $('.empty-log').remove();
            data.forEach(e => {
                $('#tableLog tbody').append(`
                    <tr class="row-log" data-id="${e.log_id}">
                        <td>${moment(e.log_date).format('D MMM YYYY, HH:mm')}</td>
                        <td>${e.log_kode}</td>
                        <td>${e.log_notes}</td>
                        <td class="text-success text-center">${e.log_category == 1 ? e.log_jumlah : "-"} ${e.log_category == 1 ? e.unit_name : ""}</td>
                        <td class="text-danger text-center">${e.log_category == 2 ? e.log_jumlah : "-"} ${e.log_category == 2 ? e.unit_name : ""}</td>
                    </tr>
                `)
            })
        } else {
            $('#tableLog tbody').append(`
                <tr class="empty-log">
                    <td colspan="4" class="text-center text-muted py-4">
                        Bahan ini belum ada riwayat perubahan stok
                    </td>
                </tr>
            `);
        }

        $('#add_stock_supplies .modal-title').html("Lihat Histori Bahan Mentah");
        $("#add_stock_supplies").modal("show");
    }

    $(document).on('change', '#start_date', function(){
        dates = [];
        var start = $('#start_date').val();
        var end = $('#end_date').val();
        dates.push(start);
        dates.push(end);
        getLog(activeId);
    })
    $(document).on('change', '#end_date', function(){
        dates = [];
        var start = $('#start_date').val();
        var end = $('#end_date').val();
        dates.push(start);
        dates.push(end);
        getLog(activeId);
    })

    $(document).on('click', '.btn-clear', function(){
        dates = null;
        $('#start_date').val("");
        $('#end_date').val("");
        getLog(activeId);
    })