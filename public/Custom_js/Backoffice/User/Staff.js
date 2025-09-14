    var mode=1;
    var table;
    $(document).ready(function(){
        inisialisasi();
        refreshStaff();
    });
    
    function inisialisasi() {
        // Cek apakah DataTable sudah diinisialisasi sebelumnya
        if ($.fn.DataTable.isDataTable("#tableStaff")) {
            // Destroy DataTable yang sudah ada
            $("#tableStaff").DataTable().destroy();
        }
        table = $('#tableStaff').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
            autoWidth: false,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Cari Staff",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "staff_name" },
                { data: "staff_phone" },
                { data: "staff_email" },
                { data: "staff_position" },
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

    function refreshStaff() {
        $.ajax({
            url: "/getStaff",
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original;
                }

                table.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].staff_name = `${e[i].staff_first_name} ${e[i].staff_last_name}`;
                    e[i].created = moment(e[i].created_at).format('D MMM YYYY'); 
                    e[i].action = `
                        <a class="me-2 btn-action-icon p-2 btn_view" href="/staffDetail/${e[i].staff_id}" data-bs-target="#view-supplier">
                            <i class="fe fe-eye"></i>
                        </a>
                        <a class="me-2 btn-action-icon p-2 btn_edit" href="/updateStaff/${e[i].staff_id}" data-bs-target="#edit-supplier">
                            <i class="fe fe-edit"></i>
                        </a>
                        <a class="p-2 btn-action-icon btn_delete" data-id="${e[i].staff_id}" href="javascript:void(0);">
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

    // $(document).on("keyup","#filter_staff_name",function(){
    //     refreshStaff();
    // });

    //delete
    $(document).on("click",".btn_delete",function(){
        var data = $('#tableStaff').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        showModalDelete("Apakah yakin ingin menghapus staff ini?","btn-delete-staff");
        $('#btn-delete-staff').attr("staff_id", data.staff_id);
    });


    $(document).on("click","#btn-delete-staff",function(){
        $.ajax({
            url:"/deleteStaff",
            data:{
                staff_id:$('#btn-delete-staff').attr('staff_id'),
                _token:token
            },
            method:"post",
            success:function(e){
                $('.modal').modal("hide");
                refreshStaff();
                notifikasi('success', "Berhasil Delete", "Berhasil delete staff");
                
            },
            error:function(e){
                console.log(e);
            }
        });
    });
