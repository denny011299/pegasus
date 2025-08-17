    var mode=1;
    var table;
    $(document).ready(function(){
        inisialisasi();
        refreshCash();
    });
    
    $(document).on('click','.btnAdd',function(){
        mode=1;
        $('#add_cash .modal-title').html("Create Cash");
        $('#add_cash input').val("");
        $('.is-invalid').removeClass('is-invalid');
        $('#add_cash').modal("show");
    });
    
    function inisialisasi() {
        table = $('#tableCash').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Search Cash",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "date" },
                { data: "keterangan" },
                { data: "masuk" },
                { data: "keluar1" },
                { data: "keluar2" },
                { data: "saldo" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });
    }

    function refreshCash() {
        $.ajax({
            url: "/getCash",
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                table.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].date = moment(e[i].tanggal).format('D MMM YYYY');
                    e[i].action = `
                        <a class="me-2 btn-action-icon p-2 btn_view" data-bs-target="#view-opname">
                            <i data-feather="view" class="fe fe-eye"></i>
                        </a>
                    `;
                }

                table.rows.add(e).draw();
                feather.replace(); // Biar icon feather muncul lagi
            },
            error: function (err) {
                console.error("Gagal load kategori:", err);
            }
        });
    }

    // $(document).on("click",".btn-save",function(){
    //    // LoadingButton(this);
    //     $('.is-invalid').removeClass('is-invalid');
    //     var url ="/insertCategory";
    //     var valid=1;

    //     $("#add_category .fill").each(function(){
    //         if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
    //             valid=-1;
    //             $(this).addClass('is-invalid');
    //         }
    //     });

    //     if(valid==-1){
    //         notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
    //         //ResetLoadingButton('.btn-save', 'Save changes');
    //         return false;
    //     };

    //     param = {
    //         category_name:$('#category_name').val(),
    //          _token:token
    //     };

    //     if(mode==2){
    //         url="/updateCategory";
    //         param.category_id = $('#add_category').attr("category_id");
    //     }

    //     //LoadingButton($(this));
    //     $.ajax({
    //         url:url,
    //         data: param,
    //         method:"post",
    //         headers: {
    //             'X-CSRF-TOKEN': token
    //         },
    //         success:function(e){      
    //             //ResetLoadingButton(".btn-save", 'Save changes');      
    //             afterInsert();
    //         },
    //         error:function(e){
    //             //ResetLoadingButton(".btn-save", 'Save changes');
    //             console.log(e);
    //         }
    //     });
    // });

    // function afterInsert() {
    //     $(".modal").modal("hide");
    //     if(mode==1)notifikasi('success', "Successful Insert", "Successful Category Added");
    //     else if(mode==2)notifikasi('success', "Successful Update", "Successful Category Updated");
    //     refreshCash();
    // }

    // $(document).on("keyup","#filter_category_name",function(){
    //     refreshCash();
    // });
    // //edit
    // $(document).on("click",".btn_edit",function(){
    //     var data = $('#tableCash').DataTable().row($(this).parents('tr')).data();//ambil data dari table
    //     mode=2;
    //     $('#add_category .modal-title').html("Update Category");
    //     $('#add_category input').empty().val("");
    //     $('#category_name').val(data.category_name);

    //     $('#add_category').modal("show");
    //     $('#add_category').attr("category_id", data.category_id);
    // });

    // //delete
    // $(document).on("click",".btn_delete",function(){
    //     var data = $('#tableCash').DataTable().row($(this).parents('tr')).data();//ambil data dari table
    //     showModalDelete("Apakah yakin ingin mengahapus category ini?","btn-delete-category");
    //     //$('#deleteModal').modal("show");
    //     $('#btn-delete-category').attr("category_id", data.category_id);
    // });


    // $(document).on("click","#btn-delete-category",function(){
    //     $.ajax({
    //         url:"/deleteCategory",
    //         data:{
    //             category_id:$('#btn-delete-category').attr('category_id'),
    //             _token:token
    //         },
    //         method:"post",
    //         success:function(e){
    //             $('.modal').modal("hide");
    //             refreshCash();
    //             notifikasi('success', "Berhasil Delete", "Berhasil delete category");
                
    //         },
    //         error:function(e){
    //             console.log(e);
    //         }
    //     });
    // });
