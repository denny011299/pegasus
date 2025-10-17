    var mode=1;
    var table;
    $(document).ready(function(){
        inisialisasi();
        refreshPettyCash();
    });
    
    $(document).on('click','.btnAdd',function(){
        mode=1;
        $('#add_petty_cash .modal-title').html("Tambah Kas Kecil");
        $('#add_petty_cash input').val("");
        $('.is-invalid').removeClass('is-invalid');
        $('#add_petty_cash').modal("show");

        let today = new Date();
        let yyyy = today.getFullYear();
        let mm = String(today.getMonth() + 1).padStart(2, '0');
        let dd = String(today.getDate()).padStart(2, '0');
        let todayStr = yyyy + '-' + mm + '-' + dd;
        $("#pc_date").val(todayStr);
    });
    
    function inisialisasi() {
        table = $('#tablePettyCash').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: false,
            autoWidth: false,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Cari Kas Kecil",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "date" },
                { data: "pc_description" },
                { data: "debit" },
                { data: "credit_text" },
                { data: "balance_text" },
                { data: "status_text" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });
    }

    function refreshPettyCash() {
        $.ajax({
            url: "/getPettyCash",
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                table.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].date = moment(e[i].pc_date).format('D MMM YYYY');
                    if (e[i].pc_type == 1) {
                        e[i].debit = "Rp " + formatRupiah(e[i].pc_nominal);
                        e[i].credit = "Rp " + 0;
                    }
                    else if (e[i].pc_type == 2) {
                        e[i].credit = "(Rp " + formatRupiah(e[i].pc_nominal) + ")";
                        e[i].debit = "Rp " + 0;
                    }
                    e[i].credit_text =`<label class='text-danger'>${e[i].credit}</label>`
                    if (e[i].balance < 0) {
                        e[i].balances = "(Rp " + formatRupiah(e[i].balance) + ")";
                        e[i].balance_text = `<label class='text-danger'>${e[i].balances}</label>`
                    }
                    else e[i].balance_text = "Rp " + formatRupiah(e[i].balance);

                    if (e[i].status == 1){
                        e[i].status_text = `<span class="badge bg-warning" style="font-size: 12px">Menunggu</span>`;
                    } else if (e[i].status == 2){
                        e[i].status_text = `<span class="badge bg-success" style="font-size: 12px">Diterima</span>`;
                    } else if (e[i].status == 3){
                        e[i].status_text = `<span class="badge bg-danger" style="font-size: 12px">Ditolak</span>`;
                    }
                }

                table.rows.add(e).draw();
                feather.replace(); // Biar icon feather muncul lagi
            },
            error: function (err) {
                console.error("Gagal load kategori:", err);
            }
        });
    }

    $(document).on("click",".btn-save",function(){
        LoadingButton(this);
        $('.is-invalid').removeClass('is-invalid');
        var url ="/insertPettyCash";
        var valid=1;

        $("#add_petty_cash .fill").each(function(){
            if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                valid=-1;
                $(this).addClass('is-invalid');
            }
        });

        if(valid==-1){
            notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
            ResetLoadingButton('.btn-save', 'Simpan perubahan');
            return false;
        };

        let type;
        if ($('#pc_select').val() == "debit"){
            type = 1;
        } else if ($('#pc_select').val() == "credit") {
            type = 2;
        }

        param = {
            pc_date:$('#pc_date').val(),
            staff_id:$('#staff_id').val(),
            pc_description:$('#pc_description').val(),
            pc_type:type,
            pc_nominal:convertToAngka($('#pc_nominal').val()),
            _token:token
        };

        LoadingButton($(this));
        $.ajax({
            url:url,
            data: param,
            method:"post",
            headers: {
                'X-CSRF-TOKEN': token
            },
            success:function(e){      
                ResetLoadingButton(".btn-save", 'Simpan perubahan');      
                afterInsert();
            },
            error:function(e){
                ResetLoadingButton(".btn-save", 'Simpan perubahan');
                console.log(e);
            }
        });
    });

    function afterInsert() {
        $(".modal").modal("hide");
        if(mode==1)notifikasi('success', "Berhasil Insert", "Berhasil Tambah Kas Kecil");
        else if(mode==2)notifikasi('success', "Berhasil Update", "Berhasil Update Kas Kecil");
        refreshPettyCash();
    }