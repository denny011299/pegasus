    autocompleteSupplies('#supplies_id', '#add_bom .modal-content');
    autocompleteProductVariantOnly('#product_id', '#add_bom .modal-content');
    autocompleteProductVariantOnly('#filter_product_id');
    autocompleteSupplies('#filter_supplies_id');

    var mode=1;
    var table;
    var bahan = [];
    $(document).ready(function(){
        inisialisasi();
        refreshBom();
    });

    $(document).on('change','#supplies_id',function(){
        var data = $(this).select2("data")[0];
        console.log(data);
        $('#unit_supplies_id').empty();
        
        getActiveSuppliesUnits(data.units).forEach(element => {
            console.log(element);
            
            $('#unit_supplies_id').append(`<option value="${element.unit_id}">${element.unit_name}</option>`);
        });
    });

    $(document).on('change','#product_id',function(){
        var data = $(this).select2("data")[0];
        console.log(data);
        $('#unit_id').empty();
        if (data.relasi.length == 0) {
            $('#unit_id').append(`<option value="${data.unit_id}" selected>${data.product_unit}</option>`);
            $('#unit_id').prop('disabled', true);
        } else {
            // Ambil satuan terkecil
            data.relasi.forEach((element, index) => {
                if (index == data.relasi.length - 1){
                    $('#unit_id').append(`<option value="${element.pr_unit_id_2}" selected>${element.pr_unit_name_2}</option>`);
                    $('#unit_id').prop('disabled', true);
                }
            });
        }
        $('#bom_qty').val(1);
        $('#bom_qty').prop('disabled', false);
    });
    
    $(document).on('click','.btnAdd',function(){
        mode=1;
        $('#add_bom .modal-title').html("Tambah Resep Bahan Mentah");
        $('#add_bom input').val("");
        $('#supplies_id').empty();
        $('#unit_id').empty();
        $('#unit_supplies_id').empty();
        $('#product_id').empty();
        $('#bom_qty').val("").prop('disabled', true);
        $('#unit_id').prop('disabled', true);
        $('#product_id').prop('disabled', false);
        $('#tableSupply tr.row-supply').remove();
        $('.is-invalid').removeClass('is-invalid');
        $('.btn-save').html('Tambah Resep');
        $('#add_bom').modal("show");
        bahan = [];
    });
    
    function inisialisasi() {
        table = $('#tableBom').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            lengthMenu: [10, 25, 50, 100],
            ordering: true,
            autoWidth: false,
            scrollX: true,
            searching: false,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Cari Resep",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "product_sku", width: '10%' },
                { data: "product_name", width: "20%" },
                { data: "supplies", width: '35%' },
                { data: "unit_text", width: '10%' },
                { data: "created_by_name", defaultContent: "-", width: '12%' },
                { data: "action", class: "text-center align-middle", width: "13" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });
    }

    function refreshBom() {
        $.ajax({
            url: "/getBom",
            method: "get",
            data: {
                product_id: $('#filter_product_id').val(),
                supplies_id: $('#filter_supplies_id').val(),
            },
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                console.log(e);
                table.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].bom_date = moment(e[i].created_at).format('D MMM YYYY');
                    console.log(e[i]);
                    e[i].unit_text = e[i].bom_qty + " " + e[i].unit_name;
                    var bo =
                        roleIconEdit(
                            "Resep Bahan Mentah",
                            "me-2 btn-action-icon p-2 btn_edit",
                            'data-bs-target="#edit-category"'
                        ) +
                        roleIconDelete(
                            "Resep Bahan Mentah",
                            "p-2 btn-action-icon btn_delete",
                            'data-id="' +
                                e[i].bom_id +
                                '" href="javascript:void(0);"'
                        );
                    e[i].action =
                        bo ||
                        '<span class="text-muted small">—</span>';
                    e[i].supplies = e[i].details.map(d => d.supplies_name).join(", ");
                }

                table.rows.add(e).draw();
                feather.replace(); // Biar icon feather muncul lagi
            },
            error: function (err) {
                console.error("Gagal load bom:", err);
            }
        });
    }

    $(document).on('change', '#filter_product_id, #filter_supplies_id', function () {
        refreshBom();
    });

    $(document).on('click', '.btn-clear', function () {
        $('#filter_product_id').empty();
        $('#filter_supplies_id').empty();
        refreshBom();
    });

    $(document).on("click",".btn-save",function(){
       LoadingButton(this);
        $('.is-invalid').removeClass('is-invalid');
        var url ="/insertBom";
        var valid=1;

        $("#add_bom .fill").each(function(){
            if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                valid=-1;
                $(this).addClass('is-invalid');
            }
        });

        if(valid==-1){
            notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
            ResetLoadingButton('.btn-save', mode == 1?"Tambah Resep" : "Update Resep"); 
            return false;
        };

        if ($("#tableSupply tbody tr").length == 0) {
            notifikasi('error', "Gagal Insert", 'Minimal input 1 bahan mentah');
            ResetLoadingButton('.btn-save', mode == 1?"Tambah Resep" : "Update Resep"); 
            return false;
        }
        console.log(bahan);
        param = {
            bom_qty:$('#bom_qty').val(),
            unit_id:$('#unit_id').val(),
            product_id:$('#product_id').val(),
            bahan : JSON.stringify(bahan),
             _token:token
        };

        if(mode==2){
            url="/updateBom";
            param.bom_id = $('#add_bom').attr("bom_id");
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
                if (typeof e === "object") {
                    notifikasi(
                        "error",
                        "Gagal Insert",
                        e.message
                    );
                } else {
                    bahan = [];
                    afterInsert();
                }
                ResetLoadingButton('.btn-save', mode == 1?"Tambah Resep" : "Update Resep");       
            },
            error:function(e){
                ResetLoadingButton('.btn-save', mode == 1?"Tambah Resep" : "Update Resep"); 
                console.log(e);
            }
        });
    });

    function afterInsert() {
        $(".modal").modal("hide");
        if(mode==1)notifikasi('success', "Berhasil Insert", "Berhasil Tambah Resep");
        else if(mode==2)notifikasi('success', "Berhasil Update", "Berhasil Update Resep");
        refreshBom();
    }

    //edit
    $(document).on("click",".btn_edit",function(){
        bahan = [];
        var data = $('#tableBom').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        console.log(data);
        mode=2;
        $('#add_bom .modal-title').html("Update Resep Bahan Mentah");
        $('#add_bom input').empty().val("");
        $('#supplies_id').empty();
        $('#unit_id').empty();
        $('#product_id').empty();
        $('#bom_qty').val(1);
        $('#unit_id, #product_id').prop('disabled', true);
        $('#bom_qty').prop('disabled', false);
        $('#tableSupply tr.row-supply').remove();
        $('.is-invalid').removeClass('is-invalid');

        $('#product_id').append(`<option value="${data.product_id}">${data.product_name}</option>`);
        $('#bom_qty').val(data.bom_qty);
        data.details.forEach(e => {
            var rowData  = {
                "bom_detail_id": e.bom_detail_id,
                "supplies_id": e.supplies_id,
                "supplies_name": e.supplies_name,
                "bom_detail_qty": e.bom_detail_qty,
                "unit_name": e.current_unit_name || e.unit_name,
                "unit_id": e.current_unit_id || e.unit_id,
                "current_unit_id": e.current_unit_id || e.unit_id,
                "current_unit_name": e.current_unit_name || e.unit_name,
                "active_units": e.active_units || e.units || [],
                "units": e.active_units || e.units || [],
            };
            bahan.push(rowData);
        });
        addRow();

        $('#unit_id').empty();
        data.pr_unit.forEach(element => {
            var active = "";
            if(element.unit_id == data.unit_id) active = "selected";
            $('#unit_id').append(`<option value="${element.unit_id}" ${active}>${element.unit_short_name}</option>`);
        });
        $('.btn-save').html('Update Resep');
        $('#add_bom').modal("show");
        $('#add_bom').attr("bom_id", data.bom_id);
    });
    $(document).on('click', '.btn-add-supply', function(){
        $('.is-invalid').removeClass('is-invalid');
        var valid=1;
        $("#add_bom .fill_supply").each(function(){
            if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                valid=-1;
                $(this).addClass('is-invalid');
            }
        });

        if(valid==-1){
            notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
            ResetLoadingButton('.btn-save', mode == 1?"Tambah Resep" : "Update Resep"); 
            return false;
        };
        var temp = $('#supplies_id').select2("data")[0];
        var idx = -1;

        bahan.forEach(element => {
            if (element.supplies_id == temp.supplies_id && element.unit_id == $('#unit_supplies_id').val()) {
                element.bom_detail_qty += parseInt($('#bom_detail_qty').val());
                idx = 1;
            }
        });

        if(idx==-1){
            var activeUnits = getActiveSuppliesUnits(temp.units);
            var data  = {
                "supplies_id": temp.supplies_id,
                "supplies_name": temp.supplies_name,
                "bom_detail_qty": parseInt($('#bom_detail_qty').val()),
                "unit_name": $('#unit_supplies_id option:selected').text(),
                "unit_id": $('#unit_supplies_id').val(),
                "current_unit_id": $('#unit_supplies_id').val(),
                "current_unit_name": $('#unit_supplies_id option:selected').text(),
                "active_units": activeUnits,
                "units": activeUnits,
            };
            bahan.push(data);
        }
        addRow()

        $('#supplies_id ').empty();
        $('#unit_supplies_id').empty();
        $('#bom_detail_qty').val("");
    })
    
    function getActiveSuppliesUnits(units) {
        return normalizeSuppliesUnits(units).filter(function (unit) {
            return unit.status === undefined || unit.status === null || parseInt(unit.status, 10) === 1;
        });
    }

    function normalizeSuppliesUnits(units) {
        if (!Array.isArray(units)) {
            return [];
        }
        return units.map(function (unit) {
            return {
                unit_id: unit.unit_id,
                unit_name: unit.unit_name || unit.unit_short_name || '',
                unit_short_name: unit.unit_short_name || unit.unit_name || '',
                status: unit.status,
            };
        });
    }

    function isUnitInActiveList(unitId, activeUnits) {
        return activeUnits.some(function (unit) {
            return String(unit.unit_id) === String(unitId);
        });
    }

    function buildUnitSelect(item, index) {
        var activeUnits = getActiveSuppliesUnits(item.active_units || item.units || []);
        var currentUnitId = item.current_unit_id || item.unit_id;
        var currentUnitName = item.current_unit_name || item.unit_name || '-';
        var currentInActive = isUnitInActiveList(currentUnitId, activeUnits);
        var selectedUnitId = item.unit_id || currentUnitId;

        var options = activeUnits.map(function (unit) {
            var selected = String(unit.unit_id) === String(selectedUnitId) ? 'selected' : '';
            var label = unit.unit_name || unit.unit_short_name || unit.unit_id;
            return `<option value="${unit.unit_id}" ${selected}>${label}</option>`;
        }).join('');

        var placeholder = '';
        var selectClass = 'form-select form-select-sm bom-row-unit';
        if (!currentInActive && activeUnits.length > 0) {
            placeholder = '<option value="">Pilih satuan aktif</option>';
        } else {
            selectClass += ' fill';
        }

        return `
            <div class="d-flex flex-column gap-1">
                <small class="text-muted">Saat ini: <span class="text-dark fw-medium">${currentUnitName}</span></small>
                <select class="${selectClass}" data-index="${index}">
                    ${placeholder}
                    ${options}
                </select>
            </div>
        `;
    }

    function addRow() {
        $('#tableSupply tbody').html("");
        bahan.forEach(function (e, index) {
            $('#tableSupply tbody').append(`
                <tr class="row-supply" data-id="${e.supplies_id}" data-unit-id="${e.unit_id}" data-index="${index}">
                    <td>${e.supplies_name}</td>
                    <td>${e.bom_detail_qty}</td>
                    <td>${buildUnitSelect(e, index)}</td>
                    <td class="text-center d-flex align-items-center">
                        <a class="p-2 btn-action-icon btn_delete_row mx-auto" href="javascript:void(0);">
                                <i class="fe fe-trash-2"></i>
                        </a>
                    </td>
                </tr>    
            `);
        });
        feather.replace();
    }

    $(document).on('change', '.bom-row-unit', function () {
        var index = parseInt($(this).data('index'), 10);
        if (isNaN(index) || !bahan[index]) {
            return;
        }
        var selectedValue = $(this).val();
        if (!selectedValue) {
            return;
        }
        bahan[index].unit_id = selectedValue;
        bahan[index].unit_name = $(this).find('option:selected').text().trim();
        $(this).closest('tr').attr('data-unit-id', bahan[index].unit_id);
    });

    $(document).on("click",".btn_delete_row",function(){
        let row = $(this).closest("tr");
        let index = row.data("index");
        if (index !== undefined && index !== "") {
            bahan.splice(parseInt(index, 10), 1);
        } else {
            let supplyId = row.data("id");
            let unitId = row.data("unit-id");
            bahan = bahan.filter(function (e) {
                return !(String(e.supplies_id) === String(supplyId) && String(e.unit_id) === String(unitId));
            });
        }
        addRow();
    });

    //delete
    $(document).on("click",".btn_delete",function(){
        var data = $('#tableBom').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        showModalDelete("Apakah yakin ingin menghapus resep ini?","btn-delete-bom");
        $('#btn-delete-bom').attr("bom_id", data.bom_id);
        $('#modalDelete').modal("show");
    });
    
    $(document).on("click","#btn-delete-bom",function(){
        $.ajax({
            url:"/deleteBom",
            data:{
                bom_id:$('#btn-delete-bom').attr('bom_id'),
                _token:token
            },
            method:"post",
            success:function(e){
                $('.modal').modal("hide");
                refreshBom();
                notifikasi('success', "Berhasil Delete", "Berhasil delete resep bahan mentah");
            },
            error:function(e){
                console.log(e);
            }
        });
    });