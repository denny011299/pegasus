    autocompleteBom('#product_id', '#addProduction')
    var mode=1;
    var table;
    var detail_supply = [];
    $(document).ready(function(){
        $('#date_production').val(moment().format('YYYY-MM-DD'));
        inisialisasi();
        refreshProduction();
    });

    $(document).on('click', '.btnAdd', function(){
        mode=1;
        $('#addProduction .modal-title').html("Tambah Produksi");
        $('#addProduction input').val("");
        $('#product_id').empty();
        $('#production_qty').val(1);
        $('#tableSupply tr.row-supply').remove();
        $('.is-invalid').removeClass('is-invalid');
        $('#unit_id').html("");
        $('#unit_id').append("<option selected>Pilih Satuan</option>");
        $('#addProduction').modal("show");
        
        let today = new Date();
        let yyyy = today.getFullYear();
        let mm = String(today.getMonth() + 1).padStart(2, '0');
        let dd = String(today.getDate()).padStart(2, '0');
        let todayStr = yyyy + '-' + mm + '-' + dd;
        $("#production_date").val(todayStr);
    })

    $(document).on('keyup', '#production_qty', function(){
        var data = $('#product_id').select2("data")[0];
        console.log(data);
        var qty = $(this).val();
        if(qty==""||qty==null||isNaN(qty)){
            qty=0;
        }
        $('#production_total').val(qty*data.bom_qty);
    });

    $(document).on('change', '#product_id', function(){
        var data = $(this).select2("data")[0];
        console.log(data);
        
        $('#unit_id').html("");
         data.pr_unit.forEach(element => {
              $('#unit_id').append(`<option value="${element.unit_id}">${element.unit_name}</option>`) 
         });
         $('#unit_id').val(data.unit_id).trigger("change");
        $('#pi_unit option').first().prop('selected', true);
        
        detail_supply = [];
        $('#tableSupply tbody').html(`
            <tr class="text-center pt-4">
                <td class="pt-4" colspan="3">
                
                    <div class="text-center h-100">
                        <div class="spinner-border" role="status">
                        </div>
                    </div>
                </td>
            </tr>       
        `);
        $('#production_qty').trigger('keyup');
        $.ajax({
            url: "/getBom",
            method: "get",
            data: {
                bom_id: data.bom_id
            },
            success: function (e) {
                console.log(e[0])
                detail_supply.push(e[0].details);
                
                addRow(e[0]);
            },
            error: function (err) {
                console.error("Gagal load produksi:", err);
            }
        });
    })

    function inisialisasi() {
        table = $('#tableProduction').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
            searching: false,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Cari Produksi",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "date" },
                { data: "product_name" },
                { data: "product_sku" },
                { data: "production_qty" },
                { data: "action", class: "d-flex align-items-center" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });
    }

    function refreshProduction() {
        $.ajax({
            url: "/getProduction",
            method: "get",
            data:{
                "date":$('#date_production').val()
            },
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                console.log(e);
                table.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].date = moment(e[i].production_date).format('D MMM YYYY');
                    e[i].action = `
                        
                        <button class="btn btn-sm btn-danger btn-action-icon btn_delete"><i class="fa-solid fa-ban"></i></button>
                    `;
                    if(moment(e[i].production_date).isBefore(moment().format('YYYY-MM-DD'))){
                        e[i].action = `
                            
                        `;
                    }

                    if (e[i].status == 1){
                        e[i].production_status = `<span class="badge bg-secondary" style="font-size: 12px">Tertunda</span>`;
                    } else if (e[i].status == 2){
                        e[i].production_status = `<span class="badge bg-primary" style="font-size: 12px">Diproses</span>`;
                    } else if (e[i].status == 3){
                        e[i].production_status = `<span class="badge bg-success" style="font-size: 12px">Selesai</span>`;
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

    $(document).on("change","#date_production",function(){
        refreshProduction();
    });
    $(document).on("click",".btn-save",function(){
       LoadingButton(this);
        $('.is-invalid').removeClass('is-invalid');
        var url ="/insertProduction";
        var valid=1;
        var dt = $('#product_id').select2("data")[0];

        $("#addProduction .fill").each(function(){
            if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                valid=-1;
                $(this).addClass('is-invalid');
            }
        });
        if($('#production_qty').val()<=0){
            valid=-1;
            $('#production_qty').addClass('is-invalid');
            notifikasi('error', "Qty Tidak Valid", 'Qty produksi harus lebih dari 0');
            ResetLoadingButton('.btn-save', mode == 1?"Tambah Produksi" : "Update Produksi"); 
            return false;
        }
        if(valid==-1){
            notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
            ResetLoadingButton('.btn-save', mode == 1?"Tambah Produksi" : "Update Produksi"); 
            return false;
        };
        param = {
            production_date:$('#production_date').val(),
            production_bom_id:$('#product_id').val(),
            production_product_id:dt.product_id,
            production_qty:$('#production_qty').val(),
            unit_id:$('#unit_id').val(),
            detail:JSON.stringify(detail_supply),
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
                ResetLoadingButton('.btn-save', mode == 1?"Tambah Produksi" : "Update Produksi");       
                if(e.status == -1){
                    notifikasi('error', "Stock Tidak Mencukupi", e.message);
                    return false;
                }
                afterInsert();
            },
            error:function(a){
                ResetLoadingButton('.btn-save', mode == 1?"Tambah Produksi" : "Update Produksi"); 
                console.log(a);
            }
        });
        /*
        // Cek stock supplies
        var qtyInput = $('#production_qty').val();
        var validQty = 1;
        var bahanKurang = [];
        $.ajax({
            url: "/getSupplies",
            method: "get",
            headers: {
                'X-CSRF-TOKEN': token
            },
            success:function(e){
                console.log(detail_supply[0])
                for (let i = 0; i < e.length; i++) {
                    detail_supply[0].forEach(element => {
                        if (e[i].supplies_id == element.supplies_id){
                            var need = element.bom_detail_qty * qtyInput;
                            console.log(need)
                            if (e[i].supplies_stock < need){
                                console.log('masuk')
                                validQty = -1;
                                bahanKurang.push(e[i].supplies_name);
                            }
                        }
                    });
                }

                if (validQty == -1){
                    notifikasi('error', "Stock Tidak Mencukupi", `Mohon cek stock ${bahanKurang.map(d => d).join(", ")}`);
                    ResetLoadingButton('.btn-save', mode == 1?"Tambah Produksi" : "Update Produksi"); 
                    return false;
                } else{
                    
            },
            error:function(e){
                console.log(e)
            }
        })*/
    });

    function afterInsert() {
        $(".modal").modal("hide");
        if(mode==1)notifikasi('success', "Berhasil Insert", "Berhasil Tambah Produksi");
        refreshProduction();
    }

    function addRow(e) {
        $('#tableSupply tbody').html("");
      
        e.details.forEach(element => {
            $('#tableSupply tbody').append(`
                <tr class="row-supply" data-id="${element.supplies_id}">
                    <td>${element.supplies_name}</td>
                    <td class="text-center">${element.bom_detail_qty}</td>
                    <td>${element.unit_name}</td>
                </tr>    
            `)
        });
    }
    

//delete
$(document).on("click", ".btn_delete", function () {
    var tbId = $(this).closest("table").attr("id");
    var data = $("#" + tbId)
        .DataTable()
        .row($(this).parents("tr"))
        .data(); //ambil data dari table
    showModalDelete(
        "Apakah yakin ingin batalkan produksi ini?",
        "btn-delete-production"
    );
    $("#btn-delete-production").html("Batal Produksi");
    $("#btn-delete-production").attr("production_id", data.production_id);
});

$(document).on("click", "#btn-delete-production", function () {
    $.ajax({
        url: "/deleteProduction",
        data: {
            production_id : $("#btn-delete-production").attr("production_id"),
            _token: token,
        },
        method: "post",
        success: function (e) {
            $(".modal").modal("hide");
            afterInsert();
            notifikasi(
                "success",
                "Berhasil Batalkan",
                "Berhasil batalkan produksi"
            );
        },
        error: function (e) {
            console.log(e);
        },
    });
});
