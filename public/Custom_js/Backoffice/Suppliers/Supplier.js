    var mode=1;
    var table;
    $(document).ready(function(){
        inisialisasi();
        refreshSupplier();
    });
    
    function inisialisasi() {
        table = $('#tableSupplier').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
            autoWidth: false,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Search Supplier",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "supplier_name" },
                { data: "supplier_code" },
                { data: "supplier_phone" },
                { data: "city_name" },
                { data: "supplier_payment" },
                { data: "created" },
                { data: "action", class: "d-flex align-items-center" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });
    }

    function refreshSupplier() {
        $.ajax({
            url: "/getSupplier",
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }

                table.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].created = moment(e[i].created_at).format('D MMM YYYY');
                    e[i].action = `
                        <a class="me-2 btn-action-icon p-2 btn_view" href="/supplierDetail/${e[i].supplier_id}" data-bs-target="#view-supplier">
                            <i data-feather="view" class="fe fe-eye"></i>
                        </a>
                        <a class="me-2 btn-action-icon p-2 btn_edit" href="/updateSupplier/${e[i].supplier_id}" data-bs-target="#edit-supplier">
                            <i data-feather="edit" class="fe fe-edit"></i>
                        </a>
                        <a class="p-2 btn-action-icon btn_delete" data-id="${e[i].supplier_id}" href="javascript:void(0);">
                            <i data-feather="trash-2" class="fe fe-trash-2"></i>
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

    // $(document).on("keyup","#filter_supplier_name",function(){
    //     refreshSupplier();
    // });

    //delete
    $(document).on("click",".btn_delete",function(){
        var data = $('#tableSupplier').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        showModalDelete("Apakah yakin ingin mengahapus supplier ini?","btn-delete-supplier");
        $('#btn-delete-supplier').attr("supplier_id", data.supplier_id);
    });


    $(document).on("click","#btn-delete-supplier",function(){
        $.ajax({
            url:"/deleteSupplier",
            data:{
                supplier_id:$('#btn-delete-supplier').attr('supplier_id'),
                _token:token
            },
            method:"post",
            success:function(e){
                $('.modal').modal("hide");
                refreshSupplier();
                notifikasi('success', "Berhasil Delete", "Berhasil delete supplier");
                
            },
            error:function(e){
                console.log(e);
            }
        });
    });
