    var mode=1;
    var table;
    var idUnits = [];
    $(document).ready(function(){
        inisialisasi();
        refreshSupplies();
        autocompleteUnit('#supplies_unit', '#add_supplies');
    });
    
    $(document).on('click','.btnAdd',function(){
        mode=1;
        idUnits = [];
        $('#add_supplies .modal-title').html("Create Supplies");
        $('#add_supplies input').val("");
        $('#supplies_desc').val("");
        $('.is-invalid').removeClass('is-invalid');
        $('#supplies_unit').val(null);
        $('#add_supplies').modal("show");
        $('#supplies_unit').trigger('change');
    });
    
    function inisialisasi() {
        table = $('#tableSupplies').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Search Supplies",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "supplies_name" },
                { data: "unit_text" },
                { data: "desc" },
                { data: "supplies_stock" },
                { data: "action", class: "d-flex align-items-center" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });
    }

    function refreshSupplies() {
        $.ajax({
            url: "/getSupplies",
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }

                table.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    if (e[i].supplies_desc == null) e[i].desc = '-';
                    else e[i].desc = e[i].supplies_desc;

                    e[i].unit_values = "";
                    e[i].action = `
                        <a class="me-2 btn-action-icon p-2 btn_edit" data-id="${e[i].supplies_id}" data-bs-target="#edit-supplies">
                            <i data-feather="edit" class="feather-edit"></i>
                        </a>
                        <a class="p-2 btn-action-icon btn_delete" data-id="${e[i].supplies_id}" href="javascript:void(0);">
                            <i data-feather="trash-2" class="feather-trash-2"></i>
                        </a>
                    `;
                }

                table.rows.add(e).draw();
                feather.replace(); // Biar icon feather muncul lagi
            },
            error: function (err) {
                console.error("Gagal load:", err);
            }
        });
    }

    $(document).on('change', '#supplies_unit', function(){
        let selectedUnits = $(this).val(); // ambil array value dari multiselect
        idUnits = [];
        selectedUnits.forEach(function(name) {
            getUnit(name, function(id) {
                idUnits.push(id);
            })
        });
        console.log(selectedUnits);

        // kosongkan dulu container biar gak dobel2
        $(".relationContainer").empty(); 

        // loop data terpilih
        selectedUnits.forEach((item, index) => {
            let html = '';
            let nextItem = (index + 1 < selectedUnits.length) ? selectedUnits[index + 1] : "-"; 
            
            html = `
            <div class="col-2 pb-3">
                <label id="pu_id_${index+1}">${item}</label>
                <input type="text" class="form-control fill" id="supplies_stock${index+1}" 
                placeholder="Enter Stock">
            </div>
            `;
            if (nextItem != '-'){
                html += `
                    <div class="col-1 pt-4 fs-3 px-0 mx-0 text-center">
                    =
                    </div>
                `;
            }
            $(".relationContainer").append(html);
        });
    })

    $(document).on("click",".btn-save",function(){
       LoadingButton(this);
        $('.is-invalid').removeClass('is-invalid');
        var url ="/insertSupplies";
        var valid=1;

        $("#add_supplies .fill").each(function(){
            if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                valid=-1;
                $(this).addClass('is-invalid');
            }
        });

        if(valid==-1){
            notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
            ResetLoadingButton('.btn-save', 'Save changes');
            return false;
        };

        param = {
            supplies_name:$('#supplies_name').val(),
            supplies_desc:$('#supplies_desc').val(),
            supplies_unit:JSON.stringify($('#supplies_unit').val()),
             _token:token
        };

        if(mode==2){
            url="/updateSupplies";
            param.supplies_id = $('#add_supplies').attr("supplies_id");
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
                let supplies_id = e;

                // 1. Insert Supplies Unit
                $.ajax({
                    url: "/insertSuppliesUnit",
                    method: "post",
                    headers: { 'X-CSRF-TOKEN': token },
                    data: {
                        supplies_id: supplies_id,
                        units: JSON.stringify(idUnits)
                    },
                    success: function (unitResp) {
                        console.log(unitResp)
                        let relations = [];
                        let ids = unitResp.id_units;

                        for (let i = 0; i < idUnits.length - 1; i++) {
                            let nilai1 = parseFloat($(`#supplies_stock${i+1}`).val()) || 1;
                            let nilai2 = parseFloat($(`#supplies_stock${i+2}`).val()) || 1;

                            let sr_value_1 = 1;
                            let sr_value_2 = nilai2 / nilai1;

                            relations.push({
                                su_id_1: ids[i],
                                su_id_2: ids[i+1],
                                sr_value_1: sr_value_1,
                                sr_value_2: sr_value_2
                            });
                        }

                        // 2. Insert Supplies Relation
                        $.ajax({
                            url: "/insertSuppliesRelation",
                            method: "post",
                            headers: { 'X-CSRF-TOKEN': token },
                            data: {
                                supplies_id: supplies_id,
                                relations: JSON.stringify(relations)
                            },
                            success: function () {
                                ResetLoadingButton(".btn-save", 'Save changes');
                            },
                            error: function (e) {
                                console.log(e);
                                ResetLoadingButton(".btn-save", 'Save changes');
                            }
                        });
                    },
                    error: function (e) {
                        console.log(e);
                        ResetLoadingButton(".btn-save", 'Save changes');
                    }
                });
                ResetLoadingButton(".btn-save", 'Save changes');   
                afterInsert();
            },
            error:function(e){
                ResetLoadingButton(".btn-save", 'Save changes');
                console.log(e);
            }
        });
    });

    function afterInsert() {
        $(".modal").modal("hide");
        if(mode==1)notifikasi('success', "Successful Insert", "Successful Supply Added");
        else if(mode==2)notifikasi('success', "Successful Update", "Successful Supply Updated");
        refreshSupplies();
    }

    function getUnit(unitName, callback) {
        $.ajax({
            url: "/getUnit",
            method: "get",
            headers: { "X-CSRF-TOKEN": token },
            data: { unit_name: unitName },
            success: function(resp) {
                callback(resp[0].unit_id);
            }
        });
    }

    // $(document).on("keyup","#filter_supplies_name",function(){
    //     refreshSupplies();
    // });

    //edit
    $(document).on("click",".btn_edit",function(){
        var data = $('#tableSupplies').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        mode=2;
        idUnits = [];
        $('#add_supplies .modal-title').html("Update Supplies");
        $('#add_supplies input').empty().val("");
        $('#supplies_unit').val(null);
        $('#supplies_name').val(data.supplies_name);
        $('#supplies_desc').val(data.supplies_desc);

        let units = [];
        units = data.unit;
        units.forEach(val => {
            if ($("#supplies_unit option[value='" + val.unit_id + "']").length === 0) {
                let newOption = new Option(val.unit_name, val.unit_id, true, true);
                $("#supplies_unit").append(newOption).trigger('change');
            }
        });
        units.forEach(function(u){
            getUnit(u, function(id){
                idUnits.push(id);
            });
        });

        $.ajax({
            url: "/getSuppliesRelation",
            method: "get",
            data: { supplies_id: data.supplies_id },
            headers: { "X-CSRF-TOKEN": token },
            success: function(resp){
                // resp misalnya [{su_id_1, su_id_2, sr_value_1, sr_value_2}]
                $(".relationContainer").empty();

                // render input stok berdasarkan units
                units.forEach((item, index) => {
                    let html = '';
                    html = `
                        <div class="col-2 pb-3">
                            <label id="pu_id_${index+1}">${item}</label>
                            <input type="text" class="form-control fill" id="supplies_stock${index+1}" 
                            placeholder="Enter Stock">
                        </div>
                    `;
                    if (index < units.length - 1) {
                        html += `
                            <div class="col-1 pt-4 fs-3 px-0 mx-0 text-center">
                                =
                            </div>
                        `;
                    }
                    $(".relationContainer").append(html);
                });

                // isi nilai stock sesuai relasi
                if(resp && resp.length > 0){
                    let total = 1;
                    for (let i = 0; i < resp.length; i++) {
                        let nilai1 = resp[i].sr_value_1;
                        let nilai2 = resp[i].sr_value_2;

                        $(`#supplies_stock${i+1}`).val(nilai1 * total);
                        total = total * nilai2;
                        $(`#supplies_stock${i+2}`).val(total);
                    }
                }
            }
        });
        
        $('#add_supplies').modal("show");
        $('#add_supplies').attr("supplies_id", data.supplies_id);
    });

    //delete
    $(document).on("click",".btn_delete",function(){
        var data = $('#tableSupplies').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        showModalDelete("Apakah yakin ingin mengahapus supply ini?","btn-delete-supplies");
        $('#btn-delete-supplies').attr("supplies_id", data.supplies_id);
    });


    $(document).on("click","#btn-delete-supplies",function(){
        $.ajax({
            url:"/deleteSupplies",
            data:{
                supplies_id:$('#btn-delete-supplies').attr('supplies_id'),
                _token:token
            },
            method:"post",
            success:function(e){
                $('.modal').modal("hide");
                refreshSupplies();
                notifikasi('success', "Berhasil Delete", "Berhasil delete supply");
                
            },
            error:function(e){
                console.log(e);
            }
        });
    });
