    autocompleteBom('#product_id', '#addProduction .modal-content')
    var mode=1; // 1 = insert; 2 = edit; 3 = view
    var modeBahan = 1;
    var table;
    var items = [];
    var list_photo =  [];
    var list_bahan=[];
    $(document).ready(function(){
        // $('#date_production').val(moment().format('YYYY-MM-DD')).trigger("change");
        inisialisasi();
        refreshProduction();
    });

    $(document).on('click', '.btnAdd', function(){
        mode=1;
        modeBahan = 1;
        items = [];
        list_bahan = [];
        $('#addProduction .modal-title').html("Tambah Produksi");
        $('#addProduction input').val("");
        $('#product_id').empty();
        $('#production_qty').val("");
        $('#tableProduct tr.row-product').remove();
        $('.is-invalid').removeClass('is-invalid');
        $('#unit_id').html("");
        $('#unit_id').append("<option selected>Pilih Satuan</option>");
        $('.add, .btn-save, .btn_delete_row_pr').show();
        $('#production_desc').attr('disabled', false);
        $('.btn-save').show();
        $('.btn-cancel').html("Batal");
        $('#addProduction').modal("show");
        $('.dos').hide();
        $('#btn-terima, #btn-tolak').hide();
        
        let today = new Date();
        let yyyy = today.getFullYear();
        let mm = String(today.getMonth() + 1).padStart(2, '0');
        let dd = String(today.getDate()).padStart(2, '0');
        let todayStr = yyyy + '-' + mm + '-' + dd;
        $("#production_date").val(todayStr).prop('disabled', true);
    })

    $(document).on('keyup', '#production_qty', function(){
        var data = $('#product_id').select2("data")[0];

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
        $('#unit_id').val(data.default_unit || data.unit_id).trigger("change");
        $('#pi_unit option').first().prop('selected', true);
        
        $('#production_qty').trigger('keyup');
    })

    function inisialisasi() {
        table = $('#tableProduction').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            lengthMenu: [10, 25, 50, 100],
            ordering: false,
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
                { data: "date", width: "15%" },
                { data: "production_code", width: "15%" },
                { data: "production_desc", width: "20%", defaultContent: "-" },
                { data: "status_text" },
                { data: "notes", defaultContent: "-", width: "30%"  },
                { data: "created_by_name", defaultContent: "-" },
                { data: "acc_by_name", defaultContent: "-" },
                { data: "cancel_requested_by_name", defaultContent: "-" },
                { data: "action", class: "text-center align-middle" },
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
                "date":$('#date_production').val(),
                status: $('#status').val()
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
                    const isOldRow = moment(e[i].production_date).isBefore(
                        moment().subtract(2, "days").format("YYYY-MM-DD")
                    );
                    let prAct = "";
                    if (hasAccessAction("Produksi", "view")) {
                        prAct +=
                            '<button class="btn btn-sm btn-info btn-action-icon btn_view me-2"><i class="fa-solid fa-eye"></i></button>';
                    }
                    if (
                        !isOldRow &&
                        e[i].status == 2 &&
                        hasAccessAction("Produksi", "delete")
                    ) {
                        prAct +=
                            '<button class="btn btn-sm btn-danger btn-action-icon btn_delete"><i class="fa-solid fa-ban"></i></button>';
                    }
                    if (isOldRow || (e[i].status != 1 && e[i].status != 2)) {
                        prAct = hasAccessAction("Produksi", "view")
                            ? '<button class="btn btn-sm btn-info btn-action-icon btn_view"><i class="fa-solid fa-eye"></i></button>'
                            : "";
                    }
                    e[i].action =
                        prAct ||
                        '<span class="text-muted small">—</span>';
                    // if(e[i].status == 3){

                    //      e[i].action = `
                    //         <button class="btn btn-sm btn-info btn-action-icon btn_view me-2"><i class="fa-solid fa-eye"></i></button>
                    //         <button class="btn btn-sm btn-danger btn-action-icon btn_cancel"><i class="fa-solid fa-x"></i></button>
                    //         <button class="btn btn-sm btn-success btn-action-icon btn_acc ms-2"><i class="fa-solid fa-check"></i></button>
                    //     `;
                    // } else if (e[i].status == 1){
                    //     e[i].action = `
                    //         <button class="btn btn-sm me-2 btn-info btn-action-icon btn_view"><i class="fa-solid fa-eye"></i></button>
                    //         <button class="btn btn-sm me-2 btn-success btn-action-icon btn_acc_produksi" data-bs-toggle="tooltip"
                    //         data-bs-placement="bottom" title="Terima"  production_id = "${e[i].production_id}" >
                    //             <i class="fa-solid fa-check"></i>
                    //         </button>
                    //         <button  class="btn btn-sm btn-danger btn-action-icon btn_decline_produksi" data-bs-toggle="tooltip"
                    //         data-bs-placement="bottom" title="Tolak"  production_id = "${e[i].production_id}" >
                    //             <i class="fa-solid fa-x"></i>
                    //         </button>
                    //     `;
                    // }
                    if (e[i].status == 1){
                        e[i].status_text = `<span class="badge bg-secondary" style="font-size: 12px">Pending</span>`;
                    } else if (e[i].status == 2){
                        e[i].status_text = `<span class="badge bg-success" style="font-size: 12px">Berhasil</span>`;
                    } else if (e[i].status == 3){
                        e[i].status_text = `<span class="badge bg-danger" style="font-size: 12px">Tolak</span>`;
                    } else if (e[i].status == 4){
                        e[i].status_text = `<span class="badge bg-warning text-dark" style="font-size: 12px">Menunggu batal</span>`;
                    }
                }

                table.rows.add(e).draw();
                feather.replace(); // Biar icon feather muncul lagi
                openProductionRevisionFromDashboardLink();
                openProductionFromDashboardLink();
            },
            error: function (err) {
                console.error("Gagal load kategori:", err);
            }
        });
    }

    /** Dari dashboard: /production?production_id=123 — buka modal detail batch tersebut */
    function openProductionFromDashboardLink() {
        try {
            var params = new URLSearchParams(window.location.search);
            var pid = params.get("production_id");
            if (!pid || !table) {
                return;
            }
            var opened = false;
            table.rows().every(function () {
                var d = this.data();
                if (String(d.production_id) === String(pid)) {
                    $(this.node()).find(".btn_view").first().trigger("click");
                    opened = true;
                    return false;
                }
            });
            if (opened) {
                params.delete("production_id");
                var q = params.toString();
                window.history.replaceState(
                    {},
                    "",
                    window.location.pathname + (q ? "?" + q : "")
                );
            }
        } catch (err) {
            console.warn("openProductionFromDashboardLink", err);
        }
    }

    /** Dari dashboard revisi: /production?rev_production_id=123 — buka modal revisi dengan data lama */
    function openProductionRevisionFromDashboardLink() {
        try {
            var params = new URLSearchParams(window.location.search);
            var pid = params.get("rev_production_id");
            if (!pid || !table) {
                return;
            }

            var rowData = null;
            table.rows().every(function () {
                var d = this.data();
                if (String(d.production_id) === String(pid)) {
                    rowData = d;
                    return false;
                }
            });

            if (!rowData) return;

            mode = 1; // submit ulang sebagai pengajuan baru (pending ACC)
            modeBahan = 1;
            items = [];
            list_bahan = [];

            $('#addProduction .modal-title').html("Revisi Produksi");
            $('#addProduction input').val("");
            $('#product_id').empty();
            $('#production_qty').val("");
            $('#tableProduct tr.row-product').remove();
            $('.is-invalid').removeClass('is-invalid');
            $('#unit_id').html("");

            $('#production_date').val(rowData.production_date).prop('disabled', true);
            $('#production_desc').val(rowData.production_desc || "").attr('disabled', false);

            rowData.items.forEach(function (e) {
                var temp = {
                    "pd_id": e.pd_id,
                    "product_variant_id": e.product_variant_id,
                    "product_name": e.product_name,
                    "pd_qty": e.pd_qty,
                    "unit_name": e.unit_name,
                    "unit_id": e.unit_id,
                    "bom_id": e.bom_id
                };
                items.push(temp);
                list_bahan.push(e.list_bahan);
            });

            addRow(items);
            $('#total_dos').html(rowData.total_dos || 0);
            $('.is-invalid').removeClass('is-invalid');
            $('.add, .btn-save, .btn_delete_row_pr').show();
            $('.dos').show();
            $('#btn-terima, #btn-tolak').hide();
            $('.btn-cancel').html("Batal");
            $('#addProduction').removeAttr("production_id");
            $('#addProduction').modal("show");

            params.delete("rev_production_id");
            var q = params.toString();
            window.history.replaceState({}, "", window.location.pathname + (q ? "?" + q : ""));
        } catch (err) {
            console.warn("openProductionRevisionFromDashboardLink", err);
        }
    }

    $(document).on("change","#date_production, #status",function(){
        refreshProduction();
    });

    $(document).on('click', '.btn-clear', function(){
        $('#date_production').val("");
        $('#status').val("");
        refreshProduction();
    })

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
        if(valid==-1){
            notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
            ResetLoadingButton('.btn-save', mode == 1?"Tambah Produksi" : "Update Produksi"); 
            return false;
        };
        if(moment($('#production_date').val()).isAfter(moment().add(1, 'days'), 'day')){
            $('#production_date').addClass('is-invalid');
            notifikasi('error', "Gagal Insert", 'Input tanggal maksimal 1 hari setelah hari ini');
            ResetLoadingButton('.btn-save', mode == 1?"Tambah Produksi" : "Update Produksi"); 
            return false;
        }
        if (items.length == 0){
            notifikasi('error', "Gagal Insert", 'Harus ada 1 produk dipilih');
            ResetLoadingButton('.btn-save', mode == 1?"Tambah Produksi" : "Update Produksi");
            return false;
        }
        param = {
            production_date:$('#production_date').val(),
            production_desc:$('#production_desc').val(),
            detail:JSON.stringify(items),
            list_bahan: JSON.stringify(list_bahan),
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
                console.log(e.length);      
                if (e.status == 0){
                    notifikasi('error', e.header, e.message);
                    return false;
                }
                else if(e.status == -1){
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
                console.log(items[0])
                for (let i = 0; i < e.length; i++) {
                    items[0].forEach(element => {
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
        items = [];
        $(".modal").modal("hide");
        if(mode==1)notifikasi('success', "Berhasil Insert", "Berhasil Tambah Produksi");
        refreshProduction();
    }

    function addRow(e) {
        $('#tableProduct tbody').html("");
        e.forEach((element, index) => {
            console.log(element);
            $('#tableProduct tbody').append(`
                <tr class="row-product" data-id="${element.product_variant_id}" data-bom="${element.bom_id}">
                    <td>${element.product_name}</td>
                    <td class="text-center">${element.pd_qty}</td>
                    <td>${element.unit_name}</td>
                    <td class="text-center align-middle">
                        <a class="p-2 btn-action-icon btn_delete_row_pr" href="javascript:void(0);">
                            <i class="fe fe-trash-2"></i>
                        </a>
                    </td>
                </tr>
            `)
            modeBahan = 1;
            if (mode != 3) getBom(element.bom_id, index);
        })
    }

    $(document).on('click', '.btn-add-product', function(){
        $('.is-invalid').removeClass('is-invalid');
        $('.is-invalids').removeClass('is-invalids');
        var valid=1;
        $("#addProduction .fill_product").each(function(){
            if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                valid=-1;
                $(this).addClass('is-invalid');
            }
        });
        if($('#product_id').val()==null||$('#product_id').val()=="null"||$('#product_id').val()==""){
            valid=-1;
            $('#row-product .select2-selection--single').addClass('is-invalids');
        }
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
        var temp = $('#product_id').select2("data")[0];
        var idx = -1;
        console.log(temp);
        items.forEach(element => {
            console.log(element);
            if (element.product_variant_id == temp.product_variant_id && element.unit_id == $('#unit_id').val()) {
                element.pd_qty += parseInt($('#production_qty').val());
                idx = 1;
            }
        });

        if(idx==-1){
            var data  = {
                "product_variant_id": temp.product_variant_id,
                "product_name": temp.product_name,
                "pd_qty": parseInt($('#production_qty').val()),
                "unit_name": $('#unit_id option:selected').text(),
                "unit_id": parseInt($('#unit_id').val()),
                "bom_id": temp.bom_id
            };
            items.push(data);
        }
        addRow(items);

        $('#product_id').empty();
        $('#unit_id').empty();
        $('#unit_id').append("<option selected>Pilih Satuan</option>");
        $('#production_qty').val("");
    })

    $(document).on("click",".btn_delete_row_pr",function(){
        let row = $(this).closest("tr");
        let productId = row.data("id");
        items = items.filter(e => e.product_variant_id != productId);

        let index = row.find('.btn_list_row').attr('index');
        if (index !== undefined) {
            list_bahan.splice(index, 1);
        }
        
        console.log(items)
        row.remove();
    });

    $(document).on('click', '.btn_view', function(){
        var data = $('#tableProduction').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        console.log(data);
        mode=3;
        modeBahan = 1;
        items = [];
        list_bahan = [];
        $('#addProduction .modal-title').html("Detail Produksi");
        $('#addProduction input').val("");
        $('#product_id').empty();
        $('#production_qty').val("");
        $('#tableProduct tr.row-product').remove();
        $('.is-invalid').removeClass('is-invalid');
        $('#unit_id').html("");
        $('#production_date').val(data.production_date);
        $('#production_desc').val(data.production_desc).attr('disabled', true);

        var total_dos = 0;

        data.items.forEach(e => {
            var temp  = {
                "pd_id": e.pd_id,
                "product_variant_id": e.product_variant_id,
                "product_name": e.product_name,
                "pd_qty": e.pd_qty,
                "unit_name": e.unit_name,
                "unit_id": e.unit_id,
                "bom_id": e.bom_id
            };
            items.push(temp);

            list_bahan.push(e.list_bahan);

            if (e.unit_name.toUpperCase().includes("DOS")){
                total_dos += e.pd_qty
            }
        })
        console.log(items);
        console.log(list_bahan);
        addRow(items);
        $('#total_dos').html(data.total_dos);
        if (data.status == 1){
            if (hasAccessAction("Produksi", "others")){
                $('#btn-terima, #btn-tolak').show();
            }
            $('#btn-terima').addClass('btn_acc_produksi');
            $('#btn-tolak').addClass('btn_decline_produksi');
            $('#btn-terima').removeClass('btn_acc');
            $('#btn-tolak').removeClass('btn_cancel');
            $('#btn-terima').attr('production_id', data.production_id);
            $('#btn-tolak').attr('production_id', data.production_id);
        } else if (data.status == 4) {
            if (hasAccessActionAny("Produksi", "others")){
            }
            $('#btn-terima, #btn-tolak').show();
            $('#btn-terima').addClass('btn_acc');
            $('#btn-tolak').addClass('btn_cancel');
            $('#btn-terima').removeClass('btn_acc_produksi');
            $('#btn-tolak').removeClass('btn_decline_produksi');
            $('#btn-terima').attr('production_id', data.production_id);
            $('#btn-tolak').attr('production_id', data.production_id);
        } else {
            $('#btn-terima, #btn-tolak').hide();
        }
        if (moment(data.production_date).isBefore(moment().subtract(3, 'days').format('YYYY-MM-DD'))) {
            $('#btn-terima, #btn-tolak').hide();
        }
        
        $('.is-invalid').removeClass('is-invalid');
        $('.add, .btn-save, .btn_delete_row_pr').hide();
        $('.dos').show();
        $('.btn-cancel').html("Kembali");
        $('#production_date').prop('disabled', true);
        $('#addProduction').attr("production_id", data.production_id);
        $('#addProduction').modal("show");
    })

    $(document).on('click', '.btn_list_row', function(){
        $('#addProduction').modal('hide');
        $('#modalBahan').modal('show');
        let row = $(this).closest("tr").data("bom");
        let index = $(this).attr('index');
        $('.btn-save-bahan').attr('index',index);
        modeBahan = 2;
        getBom(row, index);
        if (mode == 3) $('.btn-save-bahan').hide();
        else $('.btn-save-bahan').show();
    })

    $(document).on('click', '.btn-close-bahan', function(){
        $('#addProduction').modal('show');
        $('#modalBahan').modal('hide');
    })

    function getBom(id, index = null) {
        // kalau index sudah ada, maka akan balik
        if (modeBahan == 1 && list_bahan[index] !== undefined) {
            return; 
        }

        $.ajax({
            url: "/getBom",
            method: "get",
            data: { bom_id: id },
            success: function(e) {
                console.log(e);
                if (modeBahan == 1) {
                    var temp = [];
                    e[0].details.forEach(detail => {
                        temp.push(detail.supplies_id);
                    });
                    list_bahan[index] = temp;
                } 
                else if (modeBahan == 2) {
                    $('#tableSupplies tbody').html("");

                    let current_list = list_bahan[index];
                    // 1. Pastikan current_list jadi array murni (handle JSON string dari DB)
                    if (typeof current_list === 'string') {
                        try {
                            current_list = JSON.parse(current_list);
                        } catch (e) {
                            current_list = [];
                        }
                    }

                    e[0].details.forEach(b => {
                        let isChecked = false;
                        if (Array.isArray(current_list)) {
                            // Gunakan parseInt untuk memastikan perbandingan angka benar
                            isChecked = current_list.some(id => parseInt(id) == parseInt(b.supplies_id));
                        }
                        let isDisabled = (mode == 3) ? 'disabled' : '';
                        
                        $('#tableSupplies tbody').append(`
                            <tr class="row-bahan">
                                <td class="text-center">
                                    <input type="checkbox" ${isChecked ? 'checked' : ''} ${isDisabled}
                                    class="form-check-input chk" supplies_id="${b.supplies_id}" />
                                </td>
                                <td>${b.supplies_name}</td>
                            </tr>
                        `);
                    });
                }
                console.log(list_bahan);
            }
        });
    }

    

    $(document).on('click', '.btn-save-bahan', function(){
        var index = parseInt($(this).attr('index'));

        // Ambil semua id dari checkbox yang HANYA ada di tabel modal saat ini
        var temp = $('#tableSupplies tbody .chk:checked').map(function() {
            return parseInt($(this).attr('supplies_id'));
        }).get();

        var valid = 1;
        LoadingButton('.btn-save-bahan');

        if (temp.length === 0) {
            valid = -1;
        } else {
            list_bahan[index] = temp;
        }
        
        if(valid==-1){
            notifikasi('error', "Gagal Insert", 'Mohon input minimal 1 bahan');
            ResetLoadingButton('.btn-save-bahan', "Simpan Perubahan"); 
            return false;
        }

        $('#modalBahan').modal('hide');
        $('#addProduction').modal('show');
        modeBahan = 1;
        notifikasi('success', "Berhasil Simpan", 'Berhasil Simpan Detail Bahan');
        ResetLoadingButton('.btn-save-bahan', "Simpan Perubahan");
    });
    

//delete
$(document).on("click", ".btn_delete", function () {
    $('#modalDelete .modal-body #delete_reason').remove();
    var tbId = $(this).closest("table").attr("id");
    var data = $("#" + tbId)
        .DataTable()
        .row($(this).parents("tr"))
        .data(); //ambil data dari table
    showModalDelete(
        "Apakah yakin ingin batalkan produksi ini?",
        "btn-delete-production"
    );
    $('#modalDelete .modal-body').append(`<textarea class="form-control mt-2" id="delete_reason" placeholder="Alasan pembatalan produksi..." rows="3"></textarea>`);
    $("#btn-delete-production").html("Batal Produksi");
    $("#btn-delete-production").attr("production_id", data.production_id);
});

$(document).on("click", "#btn-delete-production", function () {
    LoadingButton(this);
    $('.is-invalid').removeClass('is-invalid');
    console.log($('#delete_reason').val());
    
    LoadingButton(this);
    $.ajax({
        url: "/deleteProduction",
        data: {
            production_id : $("#btn-delete-production").attr("production_id"),
            delete_reason: $('#delete_reason').val(),
            _token: token,
        },
        method: "post",
        success: function (e) {
            $('#modalDelete .modal-body').html(`<p id="text-delete" style="font-size:10pt"></p>`);
            ResetLoadingButton(".btn-konfirmasi", "Batal Produksi");
            $(".modal").modal("hide");
            afterInsert();
            notifikasi(
                "success",
                "Berhasil Batalkan",
                "Berhasil batalkan produksi"
            );
        },
        error: function (e) {
            ResetLoadingButton(".btn-konfirmasi", "Batal Produksi");
            console.log(e);
        },
    });
});

//konfirmasi acc
$(document).on("click", ".btn_acc", function () {
    // var tbId = $(this).closest("table").attr("id");
    // var data = $("#" + tbId)
    //     .DataTable()
    //     .row($(this).parents("tr"))
    //     .data(); //ambil data dari table
    var production_id = $(this).attr('production_id');
    $('.modal').modal('hide');
    showModalDelete(
        "Apakah yakin ingin Approve pembatalan produksi ini?",
        "btn-acc-delete-production"
    );
    $("#btn-acc-delete-production").attr("production_id", production_id);
    $(".btn-konfirmasi").html("Batal Produksi");
});

$(document).on("click", "#btn-acc-delete-production", function () {
    LoadingButton(this);
    $.ajax({
        url: "/accDeleteProduction",
        data: {
            production_id : $("#btn-acc-delete-production").attr("production_id"),
            _token: token,
        },
        method: "post",
        success: function (e) {
            $('#modalDelete .modal-body').html(`<p id="text-delete" style="font-size:10pt"></p>`);
            ResetLoadingButton(".btn-konfirmasi", "Batal Produksi");
            $(".modal").modal("hide");
            if(e.status == -1){
                notifikasi('error', "Stok Tidak Mencukupi", e.message);
                return false;
            }
            afterInsert();
            notifikasi(
                "success",
                "Berhasil Approve",
                "Berhasil approve pembatalan produksi"
            );
        },
        error: function (e) {
            ResetLoadingButton(".btn-konfirmasi", "Batal Produksi");
            console.log(e);
        },
    });
});

//konfirmasi acc
$(document).on("click", ".btn_cancel", function () {
    // var tbId = $(this).closest("table").attr("id");
    // var data = $("#" + tbId)
    //     .DataTable()
    //     .row($(this).parents("tr"))
    //     .data(); //ambil data dari table
    var production_id = $(this).attr('production_id');
    $('.modal').modal('hide');
    showModalKonfirmasi(
        "Apakah yakin ingin Tolak pembatalan produksi ini?",
        "btn-cancel-delete-production"
    );
    $(".btn-konfirmasi").html("Konfirmasi Batal Produksi");
    $("#btn-cancel-delete-production").attr("production_id", production_id);
});

$(document).on("click", "#btn-cancel-delete-production", function () {
    LoadingButton(this);
    $.ajax({
        url: "/tolakDeleteProduction",
        data: {
            production_id : $("#btn-cancel-delete-production").attr("production_id"),
            _token: token,
        },
        method: "post",
        success: function (e) {
            $('#modalDelete .modal-body').html(`<p id="text-delete" style="font-size:10pt"></p>`);
            ResetLoadingButton(".btn-konfirmasi", "Konfirmasi Batal Produksi");
            $(".modal").modal("hide");
            afterInsert();
            notifikasi(
                "success",
                "Berhasil Tolak",
                "Berhasil tolak pembatalan produksi"
            );
        },
        error: function (e) {
            ResetLoadingButton(".btn-konfirmasi", "Konfirmasi Batal Produksi");
            console.log(e);
        },
    });
});

    $(document).on('click', '.btn_acc_produksi', function(){
        // var data = $('#tableProduction').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        var production_id = $(this).attr('production_id');
        $('.modal').modal('hide');
        showModalKonfirmasi(
            "Apakah yakin ingin Approve produksi ini?",
            "btn-accept-production"
        );
        $('#btn-accept-production').attr("production_id", production_id);
        $('.btn-konfirmasi').html("Konfirmasi");
    })

    $(document).on('click', '#btn-accept-production', function(){
        LoadingButton(this);
        $.ajax({
            url:"/accProduction",
            data:{
                production_id:$('#btn-accept-production').attr('production_id'),
                _token:token
            },
            method:"post",
            success:function(e){
                if (e!=1){
                    if (typeof e === "object"){
                        notifikasi('error', e.header, e.message);
                        ResetLoadingButton('.btn-konfirmasi', "Konfirmasi");
                        return false;
                    } else {
                        ResetLoadingButton('.btn-konfirmasi', "Konfirmasi");
                        notifikasi("error", "Gagal Update", "Stock Product yang tidak mencukupi : "+e);
                    }
                }
                else{
                    ResetLoadingButton('.btn-konfirmasi', "Konfirmasi");
                    refreshProduction();
                    $('.modal').modal("hide");
                    notifikasi('success', "Berhasil Terima", "Berhasil Terima Produksi");
                }                
            },
            error:function(e){
                console.log(e);
                ResetLoadingButton('.btn-konfirmasi', "Konfirmasi");
            }
        });
    })

    $(document).on('click', '.btn_decline_produksi', function(){
        // var data = $('#tableProduction').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        var production_id = $(this).attr('production_id');
        $('.modal').modal('hide');
        showModalDelete("Apakah yakin ingin tolak produksi ini?","btn-decline-production");
        $('#btn-decline-production').attr("production_id", production_id);
        $('.btn-konfirmasi').html("Konfirmasi");
    })

    $(document).on('click', '#btn-decline-production', function(){
        LoadingButton(this);
        $.ajax({
            url:"/declineProduction",
            data:{
                production_id:$('#btn-decline-production').attr('production_id'),
                _token:token
            },
            method:"post",
            success:function(e){
                ResetLoadingButton('.btn-konfirmasi', "Konfirmasi");
                refreshProduction()
                $('.modal').modal("hide");
                notifikasi('success', "Berhasil Tolak", "Berhasil Tolak Pengajuan");
                
            },
            error:function(e){
                console.log(e);
                ResetLoadingButton('.btn-konfirmasi', "Konfirmasi");
            }
        });
    })

$(document).on('click', '.btn-prev', function(){
    var index = parseInt($('#fotoProduksiImage').attr('index'));
    if(index > 0){
        index -= 1;
        $('#fotoProduksiImage').attr('src', public+list_photo[index].pp_photo);
        $('#fotoProduksiImage').attr('index', index);
        $('#btn_download_photo').attr('href', public+list_photo[index].pp_photo);
    }
});
$(document).on('click', '.btn-next', function(){
    var index = parseInt($('#fotoProduksiImage').attr('index'));
    if(index < list_photo.length - 1){
        index += 1;
        $('#fotoProduksiImage').attr('src', public+list_photo[index].pp_photo);
        $('#fotoProduksiImage').attr('index', index);
        $('#btn_download_photo').attr('href', public+list_photo[index].pp_photo);
    }
});

$(document).on('click', '.LihatfotoProduksi', function(){
       list_photo = [];
       $('#fotoProduksiImage').attr('src', public+"no_img.png");
       $.ajax({
        url: "/getFotoProduksi",
        data: {
            pp_date: $('#date_production').val(),
            _token: token,
        },
        method: "get",
        success: function (e) {
            console.log(e);
 
            if(e.length > 0){
                list_photo = e;
                $('#modalViewPhoto .modal-footer').show();
                $('#fotoProduksiImage').attr('src', public+e[0].pp_photo);
                $('#fotoProduksiImage').attr('index', 0);
                $('#btn_download_photo').attr('href', public+e[0].pp_photo);
            }
            else{
                $('#modalViewPhoto .modal-footer').hide();
            }
            $('#modalViewPhoto').modal('show');
        },
        error: function (e) {
            console.log(e);
        },
    });
});
