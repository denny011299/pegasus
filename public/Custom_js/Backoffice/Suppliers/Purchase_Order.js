    var mode=1;
    var table, tablePr;
    var item = [];
    var grand = 0;
    var dates = null;
    autocompleteSupplier("#filter_supplier");
    autocompleteSupplier("#po_supplier","#add_purchase_order");
    autocompleteSupplier("#select_supplier");
    autocompleteRekening("#bank_kode");
 
    
    
    $(document).ready(function(){
        inisialisasi();
        refreshPurchaseOrder();
    });
 
    $(document).on('click','.btnAdd',function(){
        mode=1;
        console.log(moment().format('dd/MM/YYYY'));
        item  = [];
        $('#tablePurchaseModal tbody').html("");
        refreshSummary();
        $('#add_purchase_order .modal-title').html("Tambah Pesanan Pembelian");
        $('#add_purchase_order input').val("");
        $('#add_purchase_order select2').empty();
        $('#add_purchase_order #po_discount').val(0);
        $('#add_purchase_order #po_ppn').val(0);
        $('#add_purchase_order #po_cost').val(0);
        $('#add_purchase_order #po_date').val(moment().format('YYYY-MM-DD'));
        $('#add_purchase_order #po_supplier').empty();
        if ($('#po_sku').hasClass('select2-hidden-accessible')) {
            $('#po_sku').select2('destroy');
        }
        $('#add_purchase_order #po_sku').empty();
        $('#add_purchase_order #po_sku').append(`<option value="" selected disabled>Pilih Supplier Terlebih Dahulu</option>`);
        $('#btn_bukti_foto').show();
        $('#btn-lihat-bukti').hide();
        $('#check_foto').hide();
        $('.is-invalid').removeClass('is-invalid');
        $('.is-invalids').removeClass('is-invalids');
        $('#add_purchase_order').modal("show");
    });

    $(document).on('change','#po_supplier', function () {
        item = [];
        $('#tablePurchaseModal tbody').html("");
        refreshSummary();
        autocompleteSuppliesVariant('#po_sku', '#add_purchase_order',$(this).val());
    });

    $(document).on('keyup','#filter_po', function () {
        refreshPurchaseOrder();
    });

    $(document).on('change','#filter_supplier', function () {
        refreshPurchaseOrder();
    });

    $('#po_sku').on('change', function () {
        var data = $(this).select2('data')[0];

        var cari = -1;
        item.forEach((element, index) => {
            if (data.supplies_variant_id == element.supplies_variant_id) {
                cari = index;
            }
        });

        if (cari == -1) {
            data.qty = 1;
            item.push(data);
        } else {
            item[cari].qty++;
        }

        toastr.success('', 'Berhasil menambahkan Bahan');
        refreshItem();

        $('#po_sku').empty();
    });


    function refreshItem() {
        $('#tablePurchaseModal tbody').html("");
        item.forEach((item,index) => {
            console.log(item)
            var opsi = "";
            item.supplies_unit.forEach(element => {
                opsi += `<option value='${element.unit_id}'>${element.unit_short_name}</option>`;
            });
            
            $('#tablePurchaseModal tbody').append(`
                <tr>
                    <td style="width:15%">${item.supplies_name}</td>
                    <td style="width:20%">${item.supplies_variant_name}</td>
                    <td style="width:10%">${item.supplies_variant_sku}</td>
                    <td style="width:20%; padding:auto 0">
                        <div class="row m-0 p-0">
                            <div class="col-5 p-0">
                                <input type="number" class="form-control qtyPesanan" index="${index}" value="${item.qty}">
                            </div>
                            
                            <div class="col-7 p-0">
                               <select class="form-select  units_id " >
                                    ${opsi}
                                </select>
                            </div>
                        </div>
                    </td>
                    <td style="width:13%" class="text-end">Rp ${formatRupiah(item.supplies_variant_price+"")}</td>
                    <td style="width:13%" class="text-end">Rp ${formatRupiah((item.supplies_variant_price*item.qty)+"")}</td>
                    <td class="text-center text-danger" style="cursor:pointer"><i data-feather="trash-2" class="feather-trash-2 deleteRow"></i></td>
                </tr>    
            `);
        });
        feather.replace();
        refreshSummary();
    }
    
    $(document).on("click",".deleteRow",function(){
        var index = $(this).attr("index");
        item.splice(index,1);
        refreshItem();
    });

    $(document).on("change",".qtyPesanan",function(){
        var index = $(this).attr("index");
        if($(this).val()<=0){
            item.splice(index,1);
        }
        else item[index].qty = $(this).val();
        refreshItem();
    })

    
    $(document).on("keyup","#po_cost,#po_ppn,#po_discount",function(){
        refreshSummary();
    })

    function refreshSummary() {
        var total = 0;
        item.forEach(item => {
            total+=(item.supplies_variant_price*item.qty);
        });
        $('#value_total').html(formatRupiah(total,"Rp."))
        var diskon = Math.round(total * (parseInt($('#po_discount').val())/100));
        total -= diskon;
        var ppn = Math.round(total * (parseInt($('#po_ppn').val())/100));
        console.log((parseInt($('#po_ppn').val())/100));
        console.log(ppn);
        
        var cost = convertToAngka($('#po_cost').val());
        total +=ppn +cost;
        grand = total;
        $('#value_discount').html(formatRupiah(diskon,"Rp."))
        $('#value_ppn').html(formatRupiah(ppn,"Rp."))
        $('#value_cost').html(formatRupiah(cost,"Rp."))
        $('#value_grand').html(formatRupiah(grand,"Rp."))
    }

 
    function inisialisasi() {
        table = $('#tablePurchaseOrder').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            lengthMenu: [10, 25, 50, 100],
            ordering: false,
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
                { data: "po_number" },
                { data: "poi_code" },
                { data: "po_supplier_name"},
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
            url: "/getPurchaseOrder",
            method: "get",
            data:{
                po_supplier: $('#filter_supplier').val(),
                po_number: $('#filter_po').val(),
                hutang: 0,
                dates: dates,
                pembayaran: $('#status').val(),
            },
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                console.log(e);
                table.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].date = moment(e[i].po_date).format('D MMM YYYY');
                    e[i].total = `Rp ${formatRupiah(e[i].po_total)}`;

                    e[i].status_po = `<label class="badge bg-secondary badgeStatus">Menunggu Approval</label>`;
                    if(e[i].status == 2){
                        if (e[i].pembayaran == 1){
                            e[i].status_po = `<span class="badge bg-warning" style="font-size: 12px">Belum Terbayar</span>`;
                        } else if (e[i].pembayaran == 2){
                            e[i].status_po = `<span class="badge bg-success" style="font-size: 12px">Terbayar</span>`;
                        } else {
                            e[i].status_po = `<span class="badge bg-primary" style="font-size: 12px">Menunggu Tanda Terima</span>`;
                        }
                    } else if (e[i].status == -1){
                        e[i].status_po = `<span class="badge bg-danger" style="font-size: 12px">Ditolak</span>`;
                    }
                    
                    e[i].pembayaran_text = `<label class="badge bg-secondary badgeStatus">Belum Lunas</label>`;
                    
                    if(e[i].pembayaran == 1)e[i].pembayaran_text = `<label class="badge bg-primary badgeStatus">Lunas</label>`;
                    var active = "disabled";
                    if(e[i].kodeTerima!=null) active="";
                    e[i].action = `
                        <a href="/purchaseOrderDetail/${e[i].po_id}" class="me-2 btn-action-icon p-2 btn_view" >
                            <i class="fe fe-eye"></i>
                        </a>
                    `;
                    if (e[i].status == 1){
                        e[i].action += `
                            <a class="p-2 btn-action-icon btn_delete"  href="javascript:void(0);">
                                <i class="fe fe-trash-2"></i>
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

    $(document).on("click",".btn_view_tt",function(){
        var kode = $(this).attr('kode');
        if(kode==null||kode==""||kode=="null") {
            notifikasi('error', "Gagal View", 'Silahkan generate tanda terima terlebih dahulu!');
            return false;
        }
    
    });

    $(document).on('change', '#status', function(){
        console.log($(this).val());
        refreshPurchaseOrder();
    })

    $(document).on('change', '#start_date', function(){
        dates = [];
        var start = $('#start_date').val();
        var end = $('#end_date').val();
        dates.push(start);
        dates.push(end);
        refreshPurchaseOrder();
    })
    $(document).on('change', '#end_date', function(){
        dates = [];
        var start = $('#start_date').val();
        var end = $('#end_date').val();
        dates.push(start);
        dates.push(end);
        refreshPurchaseOrder();
    })

    $(document).on('click', '.btn-clear', function(){
        dates = null;
        $('#start_date').val("");
        $('#end_date').val("");
        $('#status').val("");
        refreshPurchaseOrder();
    })

    $(document).on("click",".btn-save",function(){
        LoadingButton(this);
        $('.is-invalid').removeClass('is-invalid');
        $('.is-invalids').removeClass('is-invalids');
        var url ="/insertPurchaseOrder";
        var valid=1;

        $("#add_purchase_order .fill").each(function(){
            if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                valid=-1;
                $(this).addClass('is-invalid');
            }
        });
        if($('#po_supplier').val()==null||$('#po_supplier').val()=="null"||$('#po_supplier').val()==""){
            valid=-1;
            $('#row-pemasok .select2-selection--single').addClass('is-invalids');
        }
        if ($('#bukti').val() == ""|| $('#bukti').val() == null || $('#bukti').val() == "null"){
            notifikasi('error', "Gagal Insert", 'Harus ada 1 bukti foto');
            ResetLoadingButton('.btn-save', mode == 1?"Tambah Penjualan" : "Update Penjualan");
            return false;
        }
        
        if(valid==-1){
            notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
            ResetLoadingButton('.btn-save', mode == 1?"Tambah Pembelian" : "Update Pembelian"); 
            return false;
        };

        if(item.length==0){
            valid=-1;
            notifikasi('error', "Gagal Insert", 'Silahkan masukkan minimal 1 bahan');
            ResetLoadingButton('.btn-save', mode == 1?"Tambah Pembelian" : "Update Pembelian");     
            return false;
        };
        
        $('.units_id').each(function(index){
            item[index].unit_id_select = $(this).val();
        });
        
        param = {
            // category_name:$('#category_name').val(),
            po_supplier : $('#po_supplier').val(),
            po_date : $('#po_date').val(),
            po_discount : $('#po_discount').val(),
            po_ppn : convertToAngka($('#po_ppn').val()),
            po_cost : convertToAngka($('#po_cost').val()),
            po_total : grand,
            po_detail : JSON.stringify(item),
            po_img : $('#bukti').val(),
             _token:token
        };

        if(mode==2){
            url="/updateSalesOrder";
            param.po_id = $('#add_purchase_order').attr("po_id");
        }

        LoadingButton($(this));
        $.ajax({
            url:url,
            data: param,
            method:"post",
            headers: {
                'X-CSRF-TOKEN': token
            },
            success:function(e){      
                ResetLoadingButton('.btn-save', mode == 1?"Tambah Pembelian" : "Update Pembelian");       
                afterInsert();
            },
            error:function(e){
                ResetLoadingButton('.btn-save', mode == 1?"Tambah Pembelian" : "Update Pembelian"); 
                console.log(e);
            }
        });
    });

    function afterInsert() {
        $(".modal").modal("hide");
        if(mode==1)notifikasi('success', "Berhasil Insert", "Berhasil Tambah Pesanan Pembelian");
        else if(mode==2)notifikasi('success', "Berhasil Update", "Berhasil Update Pesanan Pembelian");
        refreshPurchaseOrder();
    }
    
    // $(document).on("keyup","#filter_category_name",function(){
    //     refreshPurchaseOrder();
    // });

    //edit
    $(document).on("click",".btn_edit",function(){
        var data = $('#tablePurchaseOrder').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        mode=2;
        $('#add_purchase_order .modal-title').html("Update Pesanan Pembelian");
        $('#add_purchase_order input').empty().val("");
        // $('#category_name').val(data.category_name);
        $('.is-invalid').removeClass('is-invalid');
        $('.btn-save').html('Update Pembelian');
        $('#add_purchase_order').modal("show");
        // $('#add_purchase_order').attr("so_id", data.so_id);
    });

    //delete
    $(document).on("click",".btn_delete",function(){
        var data = $('#tablePurchaseOrder').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        showModalDelete("Apakah yakin ingin menghapus pesanan pembelian ini?","btn-delete-purchase");
        $('#btn-delete-purchase').attr("po_id", data.po_id);
    });


    $(document).on("click","#btn-delete-purchase",function(){
        LoadingButton(this);
        $.ajax({
            url:"/deletePurchaseOrder",
            data:{
                po_id:$('#btn-delete-purchase').attr('po_id'),
                _token:token
            },
            method:"post",
            success:function(e){
                $('.modal').modal("hide");
                ResetLoadingButton("#btn-delete-purchase", "Delete");
                refreshPurchaseOrder();
                notifikasi('success', "Berhasil Delete", "Berhasil delete pesanan pembelian");
                
            },
            error:function(e){
                console.log(e);
            }
        });
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


        var anchor = document.createElement('a');
        anchor.href = '/generateTandaTerima/'+$('#select_supplier').val()+"/"+$('#bank_kode').val();
        anchor.click();
    });


    
    
$(document).on('click', '#btn-foto-bukti', function() {
    rotationAngle = 0;
    camRotation = 0;
    photoData = "";
    modeCamera=4;
    inputFile ="#bukti";
    $("#video").removeClass("rot90 rot180 rot270");
    $("#preview-box").hide();
    $("#camera").show();

    startCamera();
    $("#add_purchase_order").modal("hide");
    $('#modalPhoto').modal('show');
    console.log($('#bukti').val());
});

$(document).on('click', '#uploadBtn', function(){
    if ($('#bukti').val() != "" || $('#bukti').val() != "null" || $('#bukti').val() != null) {
        $('#check_foto').show();
    } else {
        $('#check_foto').hide();
    }
})
