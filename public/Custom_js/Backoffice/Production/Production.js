    autocompleteBom('#product_id', '#addProduction')

    var table;
    var detail_supply = [];
    $(document).ready(function(){
        inisialisasi();
        refreshProduction();
    });

    $(document).on('click', '.btnAdd', function(){
        mode=1;
        $('#addProduction .modal-title').html("Create Production");
        $('#addProduction input').val("");
        $('#product_id').empty();
        $('#tableSupply tr.row-supply').remove();
        $('.is-invalid').removeClass('is-invalid');
        $('#addProduction').modal("show");

        let today = new Date();
        let yyyy = today.getFullYear();
        let mm = String(today.getMonth() + 1).padStart(2, '0');
        let dd = String(today.getDate()).padStart(2, '0');
        let todayStr = yyyy + '-' + mm + '-' + dd;
        $("#production_date").val(todayStr);
    })

    $(document).on('change', '#product_id', function(){
        var data = $(this).select2("data")[0];
        detail_supply = [];
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
                console.error("Gagal load bom:", err);
            }
        });
    })

    function inisialisasi() {
        table = $('#tableProduction').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Search Production",
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
                { data: "production_status" },
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
                        <button class="btn btn-sm btn-success"><i class="fa-solid fa-box"></i></button>
                        <button class="btn btn-sm btn-danger"><i class="fa-solid fa-ban"></i></button>
                    `;

                    if (e[i].status == 1){
                        e[i].production_status = `<span class="badge bg-secondary" style="font-size: 12px">Pending</span>`;
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

    $(document).on("click",".btn-save",function(){
       LoadingButton(this);
        $('.is-invalid').removeClass('is-invalid');
        var url ="/insertProduction";
        var valid=1;

        $("#add_production .fill").each(function(){
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
                    ResetLoadingButton('.btn-save', 'Save changes');
                    return false;
                } else{
                    param = {
                        production_date:$('#production_date').val(),
                        production_product_id:$('#product_id').val(),
                        production_qty:$('#production_qty').val(),
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
                        success:function(a){      
                            ResetLoadingButton(".btn-save", 'Save changes');      
                            afterInsert();
                        },
                        error:function(a){
                            ResetLoadingButton(".btn-save", 'Save changes');
                            console.log(a);
                        }
                    });
                }
            },
            error:function(e){
                console.log(e)
            }
        })
    });

    function afterInsert() {
        $(".modal").modal("hide");
        if(mode==1)notifikasi('success', "Successful Insert", "Successful Category Added");
        else if(mode==2)notifikasi('success', "Successful Update", "Successful Category Updated");
        refreshProduction();
    }

    function addRow(e) {
        e.details.forEach(element => {
            $('#tableSupply').append(`
                <tr class="row-supply" data-id="${element.supplies_id}">
                    <td>${element.supplies_name}</td>
                    <td>${element.bom_detail_qty}</td>
                    <td>${element.unit_name}</td>
                </tr>    
            `)
        });
    }