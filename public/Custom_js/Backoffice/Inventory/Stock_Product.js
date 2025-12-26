    var mode=1;
    var table;
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
                searchPlaceholder: "Cari Produk",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "product_variant_sku" },
                { data: "pr_name" },
                { data: "product_variant_name" },
                { data: "product_category" },
                { data: "product_variant_stock_text" },
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
            url: "/getStock",
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                console.log(e);
                
                table.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].product_variant_stock_text="";
                    e[i].stock.forEach((element,index) => {
                            e[i].product_variant_stock_text += `${element.ps_stock} ${element.unit_name}`;
                            if(index<e[i].stock.length-1)e[i].product_variant_stock_text +=", ";
                        });
                    if (e[i].product_variant_stock_text=="") e[i].product_variant_stock_text="-"
                    e[i].action = `
                        <a class="me-2 btn-action-icon p-2 btn_edit" data-id="${e[i].stk_id}" data-bs-target="#edit-category">
                            <i class="fe fe-edit"></i>
                        </a>
                        <a class="p-2 btn-action-icon btn_delete" data-id="${e[i].stk_id}" href="javascript:void(0);">
                            <i class="fe fe-trash-2"></i>
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

    $(document).on('click', '#tableStock tbody tr', function(){
        let table = $('#tableStock').DataTable();
        let data = table.row(this).data();

        getLog(data.product_variant_id);
    })

    function getLog(id){
        $.ajax({
            url: '/getLog',
            method: 'get',
            data: {
                notes: 'Produk',
                id: id
            },
            success: function(e){
                viewHistory(e);
            },
            error: function(e){
                console.error("Gagal load:", err);
            }
        });
    }

    function viewHistory(data){
        if (data.length > 0){
            $('.empty-log').remove();
            data.forEach(e => {
                $('#tableLog tbody').append(`
                    <tr data-id="${e.log_id}">
                        <td>${moment(e.log_date).format('D MMM YYYY, HH:mm')}</td>
                        <td>${e.log_kode}</td>
                        <td>${e.log_notes}</td>
                        <td>${e.log_jumlah} ${e.unit_name}</td>
                    </tr>
                `)
            })
        } else {
            $('#tableLog tbody').append(`
                <tr class="empty-log">
                    <td colspan="4" class="text-center text-muted py-4">
                        Produk ini belum ada riwayat perubahan stok
                    </td>
                </tr>
            `);
        }

        $('#add_stock_product .modal-title').html("Lihat Histori Produk");
        $("#add_stock_product").modal("show");
    }