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
        $('.is-invalid').removeClass('is-invalid');
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
                    <td>${item.supplies_name}</td>
                    <td>${item.supplies_variant_name}</td>
                    <td>${item.supplies_variant_sku}</td>
                    <td style="width:30%">
                        <div class="row m-0">
                            <div class="col-6 p-0">
                                <input type="number" class="form-control qtyPesanan" index="${index}" value="${item.qty}">
                            </div>
                            
                            <div class="col-6 p-0">
                               <select class="form-select  units_id " >
                                    ${opsi}
                                </select>
                            </div>
                        </div>
                    </td>
                    <td class="text-end">${formatRupiah(item.supplies_variant_price+"")}</td>
                    <td class="text-end">${formatRupiah((item.supplies_variant_price*item.qty)+"")}</td>
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
                { data: "po_number" },
                { data: "po_supplier_name"},
                { data: "total" },
                { data: "status_po" },
                { data: "pembayaran_text" },
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
                "po_supplier": $('#filter_supplier').val(),
                "po_number": $('#filter_po').val(),
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
                    e[i].status_po = `<label class="badge bg-secondary badgeStatus">Dibuat</label>`;
                    
                    if(e[i].status == 2)e[i].status_po = `<label class="badge bg-primary badgeStatus">Barang Diterima</label>`;
                    if(e[i].status == 3)e[i].status_po = `<label class="badge bg-warning badgeStatus">Pembayaran</label>`;
                    if(e[i].status == 4)e[i].status_po = `<label class="badge bg-success badgeStatus">Selesai</label>`;
                    
                    e[i].pembayaran_text = `<label class="badge bg-secondary badgeStatus">Belum Lunas</label>`;
                    
                    if(e[i].pembayaran == 1)e[i].pembayaran_text = `<label class="badge bg-primary badgeStatus">Lunas</label>`;
                    var active = "disabled";
                    if(e[i].kodeTerima!=null) active="";
                    e[i].action = `
                        <a href="/purchaseOrderDetail/${e[i].po_id}" class="me-2 btn-action-icon p-2 btn_view" >
                            <i class="fe fe-eye"></i>
                        </a>
                        <a class="p-2 btn-action-icon btn_delete"  href="javascript:void(0);">
                            <i class="fe fe-trash-2"></i>
                        </a>
                    `;
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

    $(document).on("click",".btn-save",function(){
        LoadingButton(this);
        $('.is-invalid').removeClass('is-invalid');
        var url ="/insertPurchaseOrder";
        var valid=1;

        $("#add_purchase_order .fill").each(function(){
            if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                valid=-1;
                $(this).addClass('is-invalid');
            }
        });

        if(item.length==0){
            valid=-1;
            notifikasi('error', "Gagal Insert", 'Silahkan masukkan minimal 1 bahan');
            ResetLoadingButton('.btn-save', mode == 1?"Tambah Pesanan Pembelian" : "Update Pesanan Pembelian");     
            return false;
        }

        if(valid==-1){
            notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
            ResetLoadingButton('.btn-save', mode == 1?"Tambah Pesanan Pembelian" : "Update Pesanan Pembelian"); 
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
            po_ppn : $('#po_ppn').val(),
            po_cost : $('#po_cost').val(),
            po_total : grand,
            po_detail : JSON.stringify(item),
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
                ResetLoadingButton('.btn-save', mode == 1?"Tambah Pesanan Pembelian" : "Update Pesanan Pembelian");       
                afterInsert();
            },
            error:function(e){
                ResetLoadingButton('.btn-save', mode == 1?"Tambah Pesanan Pembelian" : "Update Pesanan Pembelian"); 
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
        $('.btn-save').html('Simpan perubahan');
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
        
        $.ajax({
            url:"/deletePurchaseOrder",
            data:{
                po_id:$('#btn-delete-purchase').attr('po_id'),
                _token:token
            },
            method:"post",
            success:function(e){
                $('.modal').modal("hide");
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
