    var mode=1;
    var table;

    autocompleteCashCategory('#cc_id', '#add_petty_cash')
    autocompleteStaff('#staff_id', '#add_petty_cash')

    $(document).ready(function(){
        $('#filter_tanggal_start').val(moment().format('YYYY-MM-DD'));
        $('#filter_tanggal_end').val(moment().format('YYYY-MM-DD'));
        inisialisasi();
        refreshPettyCash();
    });
    $(document).on('change', '#filter_tanggal_start, #filter_tanggal_end', function(){
        refreshPettyCash();
    });
    $(document).on('click','.btnAdd',function(){
        mode=1;
        $('#add_petty_cash .modal-title').html("Tambah Kas Kecil");
        $('#add_petty_cash input').val("");
        $('#add_petty_cash select').empty(null);
        $('.is-invalid').removeClass('is-invalid');
        $('#add_petty_cash').modal("show");

        let today = new Date();
        let yyyy = today.getFullYear();
        let mm = String(today.getMonth() + 1).padStart(2, '0');
        let dd = String(today.getDate()).padStart(2, '0');
        let todayStr = yyyy + '-' + mm + '-' + dd;
        $("#pc_date").val(todayStr);
    });

    $(document).on('change', '#cc_id', function(){
        var data = $(this).select2('data')[0];
        console.log(data);
        $('#cc_type').val(data.cc_type);
    })
    
    function inisialisasi() {
        table = $('#tablePettyCash').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            lengthMenu: [10, 25, 50, 100],
            ordering: false,
            reponsive:false,
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
                { data: "pc_description",width:"30%" },
                { data: "staff_name" },
                { data: "status_text" },
                { data: "debit" },
                { data: "credit_text" },
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
            data:{
                "filter_tanggal_start": $('#filter_tanggal_start').val(),
                "filter_tanggal_end": $('#filter_tanggal_end').val()
            },
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                table.clear().draw(); 

                var debits = 0;
                var credits = 0;
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].date = moment(e[i].pc_date).format('D MMM YYYY');
                    if (e[i].pc_type == 1) {
                        e[i].debit = "Rp " + formatRupiah(e[i].pc_nominal);
                        e[i].credit = "Rp " + 0;
                        debits += e[i].pc_nominal;
                    }
                    else if (e[i].pc_type == 2) {
                        e[i].credit = "(Rp " + formatRupiah(e[i].pc_nominal) + ")";
                        e[i].debit = "Rp " + 0;
                        credits += e[i].pc_nominal;
                    }
                    e[i].credit_text =`<label class='text-danger'>${e[i].credit}</label>`

                    if (e[i].status == 1){
                        e[i].status_text = `<span class="badge bg-warning" style="font-size: 12px">Menunggu</span>`;
                    } else if (e[i].status == 2){
                        e[i].status_text = `<span class="badge bg-success" style="font-size: 12px">Diterima</span>`;
                    } else if (e[i].status == 3){
                        e[i].status_text = `<span class="badge bg-danger" style="font-size: 12px">Ditolak</span>`;
                    }
                }
                $('.debits').html(`Rp ${formatRupiah(debits)}`);
                $('.credits').html(`(Rp ${formatRupiah(credits)})`);
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
            ResetLoadingButton('.btn-save', mode == 1?"Tambah Kas" : "Update Kas");
            return false;
        };

        var data = $('#cc_id').select2('data')[0];
        var type = data.cc_type;
        if (type == 'Debit') type = 1;
        else type = 2;

        param = {
            pc_date:$('#pc_date').val(),
            staff_id:$('#staff_id').val(),
            pc_description:$('#pc_description').val(),
            pc_type:type,
            cc_id:$('#cc_id').val(),
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
                ResetLoadingButton(".btn-save", mode == 1?"Tambah Kas" : "Update Kas");      
                afterInsert();
            },
            error:function(e){
                ResetLoadingButton(".btn-save", mode == 1?"Tambah Kas" : "Update Kas");
                console.log(e);
            }
        });
    });

    $(document).on('click', '.btn-add-cash', function(){
        $('.is-invalid').removeClass('is-invalid');
        $('.is-invalids').removeClass('is-invalids');
        var valid=1;
        $("#add_petty_cash .fill_cash").each(function(){
            if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                valid=-1;
                $(this).addClass('is-invalid');
            }
        });

        if($('#cc_id').val()==null||$('#cc_id').val()=="null"||$('#cc_id').val()==""){
            valid=-1;
            $('#row-cash .select2-selection--single').addClass('is-invalids');
        }

        if(valid==-1){
            notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
            ResetLoadingButton('.btn-save', mode == 1?"Tambah Kas" : "Update Kas"); 
            return false;
        };
    })

    function afterInsert() {
        $(".modal").modal("hide");
        if(mode==1)notifikasi('success', "Berhasil Insert", "Berhasil Tambah Kas Kecil");
        else if(mode==2)notifikasi('success', "Berhasil Update", "Berhasil Update Kas Kecil");
        refreshPettyCash();
    }