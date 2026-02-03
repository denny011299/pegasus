    var mode=1;
    var table;
    let debit = 0, credit1 = 0, credit2 = 0;
    $(document).ready(function(){
        inisialisasi();
        refreshCash();
    });
    
    $(document).on('click','.btnAdd',function(){
        mode=1;
        $('#add_cash .modal-title').html("Tambah Kas");
        $('#add_cash input').val("");
        $('.is-invalid').removeClass('is-invalid');
        $('#add_cash').modal("show");

        let today = new Date();
        let yyyy = today.getFullYear();
        let mm = String(today.getMonth() + 1).padStart(2, '0');
        let dd = String(today.getDate()).padStart(2, '0');
        let todayStr = yyyy + '-' + mm + '-' + dd;
        $("#cash_date").val(todayStr);
    });
    
    function inisialisasi() {
        table = $('#tableCash').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            lengthMenu: [10, 25, 50, 100],
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
                { data: "cash_description" },
                { data: "debit" },
                { data: "credit_text1" },
                { data: "credit_text2" },
                { data: "balance_text" },
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
                    e[i].date = moment(e[i].cash_date).format('D MMM YYYY');
                    if (e[i].cash_type == 1) {
                        e[i].debit = "Rp " + formatRupiah(e[i].cash_nominal);
                        e[i].credit1 = "Rp " + 0;
                        e[i].credit2 = "Rp " + 0;
                    }
                    else if (e[i].cash_type == 2) {
                        e[i].credit1 = "(Rp " + formatRupiah(e[i].cash_nominal) + ")";
                        e[i].debit = "Rp " + 0;
                        e[i].credit2 = "Rp " + 0;
                    }
                    else if (e[i].cash_type == 3) {
                        e[i].credit2 = "(Rp " + formatRupiah(e[i].cash_nominal) + ")";
                        e[i].credit1 = "Rp " + 0;
                        e[i].debit = "Rp " + 0;
                    }
                    e[i].credit_text1 =`<label class='text-danger'>${e[i].credit1}</label>`
                    e[i].credit_text2 =`<label class='text-danger'>${e[i].credit2}</label>`
                    if (e[i].cash_balance < 0) {
                        e[i].balance = "(Rp " + formatRupiah(e[i].cash_balance) + ")";
                        e[i].balance_text = `<label class='text-danger'>${e[i].balance}</label>`
                    }
                    else e[i].balance_text = "Rp " + formatRupiah(e[i].cash_balance);
                }

                table.rows.add(e).draw();
                feather.replace(); // Biar icon feather muncul lagi
            },
            error: function (err) {
                console.error("Gagal load kas:", err);
            }
        });
    }

    $(document).on("click",".btn-save",function(){
       LoadingButton(this);
        $('.is-invalid').removeClass('is-invalid');
        var url ="/insertCash";
        var valid=1;

        $("#add_cash .fill").each(function(){
            if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                valid=-1;
                $(this).addClass('is-invalid');
            }
        });

        if(valid==-1){
            notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
            ResetLoadingButton('.btn-save', mode == 1?"Tambah Kas" : "Update Kas");
            return false;
        };

        let type;
        if ($('#cash_select').val() == "debit"){
            type = 1;
        } else if ($('#cash_select').val() == "credit1") {
            type = 2;
        } else if ($('#cash_select').val() == "credit2") {
            type = 3;
        }

        param = {
            cash_date:$('#cash_date').val(),
            cash_description:$('#cash_description').val(),
            cash_type:type,
            cash_nominal:$('#cash_nominal').val(),
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
                ResetLoadingButton(".btn-save", mode == 1?"Tambah Kas" : "Update Kas");      
                afterInsert();
            },
            error:function(e){
                ResetLoadingButton(".btn-save", mode == 1?"Tambah Kas" : "Update Kas");
                console.log(e);
            }
        });
    });

    function afterInsert() {
        $(".modal").modal("hide");
        if(mode==1)notifikasi('success', "Berhasil Insert", "Berhasil Tambah Kas");
        else if(mode==2)notifikasi('success', "Berhasil Update", "Berhasil Update Kas");
        refreshCash();
    }