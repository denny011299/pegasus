    var mode=1;
    var table;

    $(document).ready(function(){
        inisialisasi();
        refreshProduct();
    });
    
    function inisialisasi() {
        table = $('#tableProduct').DataTable({
            responsive: true,
            scrollX: true,
            autoWidth: false,
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
                { data: "product_name" },
                { data: "product_category" },
                { data: "unit_values" },
                { data: "variant_values" },
                { data: "action", class: "d-flex align-items-center" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });
         feather.replace();
    }

    function refreshProduct() {
        $.ajax({
            url: "/getProduct",
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }

                table.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].variant_values = "";
                    e[i].pr_variant.forEach((element,index) => {
                         e[i].variant_values += element.product_variant_name;
                         if(index< e[i].pr_variant.length-1){
                            e[i].variant_values += ", ";
                         }
                    });
                    e[i].unit_values = "";
                    e[i].pr_unit.forEach((element,index) => {
                         e[i].unit_values += element.unit_name;
                         if(index< e[i].pr_unit.length-1){
                            e[i].unit_values += ", ";
                         }
                    });
                    e[i].action = `
                        <a href="/updateProduct/${e[i].product_id}" class="me-2 btn-action-icon p-2 " >
                            <i class="fe fe-edit"></i>
                        </a>
                        <a class="p-2 btn-action-icon btn_delete" data-id="${e[i].pr_id}" href="javascript:void(0);">
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
    
    //delete
    $(document).on("click",".btn_delete",function(){
        var data = $('#tableProduct').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        showModalDelete("Apakah yakin ingin mengahapus produk ini?","btn-delete-product");
        $('#btn-delete-product').attr("product_id", data.product_id);
        $('#modalDelete').modal("show");
    });

    $(document).on("click","#btn-delete-product",function(){
        $.ajax({
            url:"/deleteProduct",
            data:{
                product_id:$('#btn-delete-product').attr('product_id'),
                _token:token
            },
            method:"post",
            success:function(e){
                $('.modal').modal("hide");
                refreshProduct();
                notifikasi('success', "Berhasil Delete", "Berhasil delete produk");
                
            },
            error:function(e){
                console.log(e);
            }
        });
    });