    autocompleteSupplies('#supplies_id', '#add_bom');
    autocompleteProductVariant('#product_id', '#add_bom');

    var mode=1;
    var table;
    var bahan = [];
    $(document).ready(function(){
        inisialisasi();
        refreshBom();
    });

    $(document).on('change','#supplies_id',function(){
        var data = $(this).select2("data")[0];
        $('#unit_id').empty();
        
        data.unit.forEach(element => {
            $('#unit_id').append(`<option value="${element.unit_id}">${element.unit_name}</option>`);
        });
    });
    $(document).on('click','.btnAdd',function(){
        mode=1;
        $('#add_bom .modal-title').html("Create Bill of Material");
        $('#add_bom input').val("");
        $('#supplies_id').empty();
        $('#unit_id').empty();
        $('#product_id').empty();
        $('#tableSupply tr.row-supply').remove();
        $('.is-invalid').removeClass('is-invalid');
        $('#add_bom').modal("show");
        bahan = [];
    });
    
    function inisialisasi() {
        table = $('#tableBom').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
            autoWidth: false,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Search BoM",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "product_sku" },
                { data: "product_name" },
                { data: "supplies" },
                { data: "unit" },
                { data: "action", class: "d-flex align-items-center" },
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
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                console.log(e);
                table.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].bom_date = moment(e[i].created_at).format('D MMM YYYY');
                    e[i].unit = e[i].details[0].unit_name;
                    e[i].action = `
                        <a class="me-2 btn-action-icon p-2 btn_edit"  data-bs-target="#edit-category">
                            <i data-feather="edit" class="feather-edit"></i>
                        </a>
                        <a class="p-2 btn-action-icon btn_delete" data-id="${e[i].bom_id}  href="javascript:void(0);">
                            <i data-feather="trash-2" class="feather-trash-2"></i>
                        </a>
                    `;
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
            ResetLoadingButton('.btn-save', 'Save changes');
            return false;
        };
        console.log(bahan);
        param = {
            bom_qty:$('#bom_qty').val(),
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
                bahan = [];
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
        if(mode==1)notifikasi('success', "Successful Insert", "Successful BoM Added");
        else if(mode==2)notifikasi('success', "Successful Update", "Successful BoM Updated");
        refreshBom();
    }

    //edit
    $(document).on("click",".btn_edit",function(){
        bahan = [];
        var data = $('#tableBom').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        console.log(data);
        mode=2;
        $('#add_bom .modal-title').html("Update Bill of Material");
        $('#add_bom input').empty().val("");
        $('#supplies_id').empty();
        $('#unit_id').empty();
        $('#product_id').empty();
        $('#tableSupply tr.row-supply').remove();
        $('.is-invalid').removeClass('is-invalid');

        $('#product_id').append(`<option value="${data.product_id}">${data.product_name}</option>`);
        $('#bom_qty').val(data.bom_qty);
        data.details.forEach(e => {
            var data  = {
                "bom_detail_id": e.bom_detail_id,
                "supplies_id": e.supplies_id,
                "supplies_name": e.supplies_name,
                "bom_detail_qty": e.bom_detail_qty,
                "unit_name": e.unit_name,
                "unit_id": e.unit_id,
            };
            bahan.push(data);
            addRow(data)
        });
        
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
            ResetLoadingButton('.btn-save', 'Save changes');
            return false;
        };
        var temp = $('#supplies_id').select2("data")[0];
        var data  = {
            "supplies_id": temp.supplies_id,
            "supplies_name": temp.supplies_name,
            "bom_detail_qty": parseInt($('#bom_detail_qty').val()),
            "unit_name": $('#unit_id option:selected').text().trim(),
            "unit_id": $('#unit_id').val(),
        };
        bahan.push(data);
        addRow(data)

        $('#supplies_id').empty();
        $('#unit_id').empty();
        $('#bom_detail_qty').val("");
    })
    
    function addRow(e) {
        let qty = parseInt($('#bom_detail_qty').val());

        let row = $(`#tableSupply tr[data-id="${e.supplies_id}"]`);

        if (row.length > 0) {
            // update qty di row lama
            let currentQty = parseInt(row.find("td:eq(1)").text());
            row.find("td:eq(1)").text(currentQty + qty);

            // Update data qty di bahan untuk ajax
            bahan.forEach(element => {
                if (element.supplies_id == e.supplies_id){
                    element.bom_detail_qty += e.bom_detail_qty;
                }
            });
        }
        else{
            $('#tableSupply').append(`
                <tr class="row-supply" data-id="${e.supplies_id}">
                    <td>${e.supplies_name}</td>
                    <td>${e.bom_detail_qty}</td>
                    <td>${e.unit_name}</td>
                    <td class="text-center d-flex align-items-center">
                        <a class="p-2 btn-action-icon btn_delete_row mx-auto"  href="javascript:void(0);">
                                <i data-feather="trash-2" class="fe fe-trash"></i>
                        </a>
                    </td>
                </tr>    
            `)
        }
    }

    $(document).on("click",".btn_delete_row",function(){
        let row = $(this).closest("tr");
        let supplyId = row.data("id");
        bahan = bahan.filter(e => e.supplies_id != supplyId);
        console.log(bahan)
        row.remove();
    });

    //delete
    $(document).on("click",".btn_delete",function(){
        var data = $('#tableBom').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        showModalDelete("Apakah yakin ingin mengahapus BoM ini?","btn-delete-bom");
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
                notifikasi('success', "Berhasil Delete", "Berhasil delete BoM");
            },
            error:function(e){
                console.log(e);
            }
        });
    });