    var mode=1;
    var table, tablePr;
    var item = [];
    var grand = 0;
    autocompleteSupplier("#filter_supplier");
    autocompleteSupplier("#po_supplier","#add_purchase_order");
    autocompleteSupplier("#select_supplier");
    autocompleteRekening("#bank_kode");
    autocompleteSuppliesVariant("#po_sku","#add_purchase_order");
    
    
    $(document).ready(function(){
        inisialisasi();
        refreshPurchaseOrder();
    });


 
    function inisialisasi() {
        table = $('#tableTTPurchaseOrder').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            order: [[0, 'desc']],
            searching:false,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Cari Pesanan Pembelian",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "date" },
                { data: "tt_kode" },
                { data: "supplier_name" },
                { data: "total" },
                { data: "status_po" },
                { data: "action", class: "d-flex align-items-center" },
            ],
            columnDefs: [
                {
                    targets: 0,
                    type: "date"
                }
            ],
            initComplete: (settings, json) => {
            },
        });
    }

    function refreshPurchaseOrder() {
        $.ajax({
            url: "/getTt",
            method: "get",
            
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                console.log(e);
                table.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].date = moment(e[i].tt_date).format('D MMM YYYY');
                    e[i].total = `Rp ${formatRupiah(e[i].tt_total)}`;
                    e[i].status_po = `<label class="badge bg-secondary badgeStatus">Menunggu</label>`;
                    
                    if(e[i].status == 0)e[i].status_po = `<label class="badge bg-danger badgeStatus">Ditolak</label>`;
                    if(e[i].status == 2)e[i].status_po = `<label class="badge bg-success badgeStatus">Dibayarkan</label>`;
               
                    e[i].action = `
                        <a href="/viewTandaTerima/${e[i].tt_id}" class="me-2 btn-action-icon p-2 btn_view_tt" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" title="Download Tanda Terima" kode = "${e[i].kodeTerima}" >
                            <i class="fe fe-file-text"></i>
                        </a>
                    `;
                    if(e[i].status==1){
                        e[i].action += `
                            <a class="me-2 btn-action-icon p-2 btn_acc_tt bg-success text-light" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" title="Terima"  tt_id = "${e[i].tt_id}" >
                                <i class="fe fe-check"></i>
                            </a>
                            <a  class="me-2 btn-action-icon p-2 btn_decline_tt bg-danger text-light" data-bs-toggle="tooltip"
                            data-bs-placement="bottom" title="Tolak"  tt_id = "${e[i].tt_id}" >
                                <i class="fe fe-x"></i>
                            </a>
                        `;
                    }
                    if(e[i].status==2){
                        e[i].action += `
                            <a class="me-2 btn-action-icon p-2 btn_view_bukti_tt"  data-bs-toggle="tooltip"
                            data-bs-placement="bottom" title="Lihat Bukti Transfer"  tt_id = "${e[i].tt_id}" >
                                <i class="fe fe-eye"></i>
                            </a>
                        `;
                    }
                }

                table.rows.add(e).draw();
                feather.replace(); // Biar icon feather muncul lagi
            },
            error: function (err) {
                console.error("Gagal load so:", err);
            }
        });
    }

   
 

    $(document).on("click",".btn-save",function(){
        console.log($('#image')[0].files[0]);
        
        if($('#image')[0].files[0]=="undefined"||$('#image')[0].files[0]==undefined){
            notifikasi('error', "Gagal terima", "Upload Bukti transfer terlebih dahulu");
            return false;
        }

        const fd = new FormData();
        fd.append('image', $('#image')[0].files[0]);
        fd.append('tt_id', $('#add_acc_tt').attr("tt_id"));

        $.ajax({
            url:"/accTt",
            contentType: false,
            processData: false,
            method:"post",
            data: fd,
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            success:function(e){
                $('.modal').modal("hide");
                refreshPurchaseOrder();
                notifikasi('success', "Berhasil Terima", "Berhasil Terima Surat Tanda Terima");
                
            },
            error:function(e){
                console.log(e);
            }
        });
    });
    $(document).on("click",".btn_acc_tt",function(){
        $('#preview_image').attr("src",public+"no_img.png")
        $('#image').val(null);
        $('#add_acc_tt').attr("tt_id",$(this).attr('tt_id'));
        $('#add_acc_tt').modal("show");
        
    });
    $(document).on("click",".btn_view_bukti_tt",function(){
        var data = $('#tableTTPurchaseOrder').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        console.log(data);
        
        $('#preview_bukti').attr("src",public+data.tt_image);
        $('#view_tt').modal("show");
        
    });

    $(document).on("click","#generateTandaTerima",function(){
        $('.invalid').removeClass('invalid');
        var valid = 1;
        if($('#select_supplier').val()==null||$('#select_supplier').val()==""){
            $('.row-supplier .select2-selection--single').addClass('invalid');
            valid=-1;
        }
        if($('#bank_kode').val()==null||$('#bank_kode').val()==""){
             $('.row-rekening .select2-selection--single').addClass('invalid');
            valid=-1;
        }
        
        if(valid==-1){
            notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
            return false;
        };
        var url = '/generateTandaTerima/'+$('#select_supplier').val()+"/"+$('#bank_kode').val();
        $.ajax({
            url:url,
            method:"get",
            success:function(e){
                if(e==-1){
                    notifikasi("error","Gagal Buat Surat Terima","Supplier tersebut tidak ada po selesai!")
                }
                else{
                    refreshPurchaseOrder();
                    window.location.href = '/viewTandaTerima/' + e;
                }
                $('#select_supplier').empty();
                $('#bank_kode').empty();
            },
            error:function(e){
                console.log(e);
            }
        });
       
    });

$(document).on("change", "#image", function () {
    let file = this.files[0];
    if (file) {
        // ganti preview gambar
        let reader = new FileReader();
        reader.onload = function (e) {
            $("#preview_image").attr("src", e.target.result);
        };
        reader.readAsDataURL(file);
        // ganti nama file
        $("#file_name").text(file.name);
    }
});

    $(document).on("click",".btn_view_tt",function(){
        var kode = $(this).attr('kode');
        if(kode==null||kode==""||kode=="null") {
            notifikasi('error', "Gagal View", 'Silahkan generate tanda terima terlebih dahulu!');
            return false;
        }
    
    });

     $(document).on("click",".btn_decline_tt",function(){
        var data = $('#tableTTPurchaseOrder').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        showModalDelete("Apakah yakin ingin tolak surat tanda terima ini?","btn-delete-supplier");
        $('#btn-delete-supplier').attr("tt_id", data.tt_id);
    });


    $(document).on("click","#btn-delete-supplier",function(){
        $.ajax({
            url:"/declineTt",
            data:{
                tt_id:$('#btn-delete-supplier').attr('tt_id'),
                _token:token
            },
            method:"post",
            success:function(e){
                refreshPurchaseOrder();
                $('.modal').modal("hide");
                notifikasi('success', "Berhasil Tolak", "Berhasil Totak Surat Tanda Terima ");
                
            },
            error:function(e){
                console.log(e);
            }
        });
    });