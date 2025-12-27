    var mode=1;
    var tablePayables, tableReceiveables;
    var tandaTerima=[];

    autocompleteRekening("#bank_kode");
    $(document).ready(function(){
        inisialisasi();
        refreshPayReceive();
    });
    
    function inisialisasi() {
        tablePayables = $('#tablePayables').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
            searching: false,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Cari Hutang",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            createdRow: function (row, data, dataIndex) {
                $(row).addClass('row-payables');
            },
            columns: [
                { data: "check" },
                { data: "bank_kode" },
                { data: "date" },
                { data: "date_due_date" },
                { data: "po_code" },
                { data: "poi_code" },
                { data: "supplier_name" },
                { data: "poi_total_text" },
                { data: "status_text" },
                { data: "action", class: "d-flex align-items-center" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });

    }

    function refreshPayReceive() {
         $.ajax({
            url: "/getPoInvoice",
            method: "get",
            data: {
                bank_id: $('#bank_kode').val(),
            },
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                console.log(e);
                tablePayables.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].check = `<input type="checkbox" class="form-check-input chk ch${e[i].poi_id}"  poi_id="${e[i].poi_id}" />`;
                    e[i].date = moment(e[i].poi_date).format('D MMM YYYY');
                    e[i].date_due_date = moment(e[i].poi_due).format('D MMM YYYY');
                    e[i].poi_total_text = formatRupiah(e[i].poi_total,"Rp.");
                    console.log(e[i].pembayaran);
                    
                    if (e[i].pembayaran == 0){
                        e[i].status_text = `<span class="badge bg-warning" style="font-size: 12px">Belum Terbayar</span>`;
                    } else if (e[i].pembayaran == 1){
                        e[i].status_text = `<span class="badge bg-success" style="font-size: 12px">Terbayar</span>`;
                    } else {
                        e[i].status_text = `<span class="badge bg-primary" style="font-size: 12px">Menunggu Approval</span>`;
                    }
                    e[i].action = `
                        <a href="/purchaseOrderDetailHutang/${e[i].po_id}" class="me-2 btn-action-icon p-2 btn_edit_invoice" >
                            <i class="fe fe-eye"></i>
                        </a>
                    `;
                }

                tablePayables.rows.add(e).draw();
                feather.replace(); // Biar icon feather muncul lagi
            },
            error: function (err) {
                console.error("Gagal load:", err);
            }
        });
    }
    $(document).on("change", "#bank_kode", function () {
        refreshPayReceive();
    });

    $(document).on("click", ".chk", function () {
        var kode = $(this).attr("poi_id");
        var ada=false;
        tandaTerima.forEach(item => {
            if(item == kode){
                ada=true;
            }
        });
        if(ada){
            tandaTerima = tandaTerima.filter(item => item != kode);
        } else {
            tandaTerima.push(kode);
        }
        console.log(tandaTerima);
        $('#jumlah_terpilih').text(tandaTerima.length + " Selected");
    });
    $(document).on("click", "#jumlah_terpilih", function () {
        tandaTerima=[];
        $('.chk').prop('checked', false);
        $('#jumlah_terpilih').text("0 Selected");
    });
    $(document).on("click", ".btn-create", function () {
       $('.invalid').removeClass('invalid');

        if(tandaTerima.length==0){
            notifikasi("error","Gagal Buat Surat Terima","Silahkan pilih minimal 1 faktur!");
            return false;
        }
        console.log(tandaTerima);
        
        var url = '/generateTandaTerimaInvoice';
        $.ajax({
            url:url,
            method:"get",
            data:{
                poi_id:tandaTerima,
            },
            success:function(e){
                if(e.status&&e.status==-1){
                    notifikasi("error","Gagal Buat Surat Terima",e.message)
                }
                else if(e.status&&e.status==1){
                    notifikasi("success","Berhasil Buat Surat Terima","Surat tanda terima berhasil dibuat");
                    refreshPayReceive();
                    window.location.href = '/viewTandaTerima/' + e.tt_id;
                }

                tandaTerima=[];
                $('.chk').prop('checked', false);
                $('#jumlah_terpilih').text("0 Selected");
            },
            error:function(e){
                console.log(e);
            }
        });
    });
    /*
    $(document).on('click', '.row-payables', function(){
        alert("test")
        $(this).find('input[type="checkbox"]').trigger('click'); 
    });*/