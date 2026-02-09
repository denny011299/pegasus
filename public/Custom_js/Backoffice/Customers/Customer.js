    var mode=1;
    var table;
    $(document).ready(function(){
        inisialisasi();
        refreshCustomer();
    });
    
    function inisialisasi() {
        // Cek apakah DataTable sudah diinisialisasi sebelumnya
        if ($.fn.DataTable.isDataTable("#tableCustomer")) {
            // Destroy DataTable yang sudah ada
            $("#tableCustomer").DataTable().destroy();
        }
        table = $('#tableCustomer').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            lengthMenu: [10, 25, 50, 100],
            ordering: true,
            autoWidth: false,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Cari Armada",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "customer_notes" },
                { data: "customer_pic" },
                { data: "customer_pic_phone" },
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

    function refreshCustomer() {
        $.ajax({
            url: "/getCustomer",
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original;
                }

                table.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].created = moment(e[i].created_at).format('D MMM YYYY');
                    // <a class="me-2 btn-action-icon p-2 btn_view" href="/customerDetail/${e[i].customer_id}" data-bs-target="#view-supplier">
                    //     <i class="fe fe-eye"></i>
                    // </a>
                    e[i].action = `
                        <a class="me-2 btn-action-icon p-2 btn_edit" href="/updateCustomer/${e[i].customer_id}" data-bs-target="#edit-supplier">
                            <i class="fe fe-edit"></i>
                        </a>
                        <a class="p-2 btn-action-icon btn_delete" data-id="${e[i].customer_id}" href="javascript:void(0);">
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

    // $(document).on("keyup","#filter_customer_name",function(){
    //     refreshCustomer();
    // });

    //delete
    $(document).on("click",".btn_delete",function(){
        var data = $('#tableCustomer').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        showModalDelete("Apakah yakin ingin menghapus Armada ini?","btn-delete-customer");
        $('#btn-delete-customer').attr("customer_id", data.customer_id);
    });


    $(document).on("click","#btn-delete-customer",function(){
        $.ajax({
            url:"/deleteCustomer",
            data:{
                customer_id:$('#btn-delete-customer').attr('customer_id'),
                _token:token
            },
            method:"post",
            success:function(e){
                $('.modal').modal("hide");
                refreshCustomer();
                notifikasi('success', "Berhasil Delete", "Berhasil delete Armada");
                
            },
            error:function(e){
                console.log(e);
            }
        });
    });
