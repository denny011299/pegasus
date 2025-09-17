    var mode=1;
    var table;
    var products = [];
    autocompleteCustomer('#so_customer', "#add_sales_order");
    autocompleteStaffSales('#sales_id', "#add_sales_order");
    $(document).ready(function(){
        inisialisasi();
        refreshSalesOrder();
    });

    // Supaya bisa focus saat load modal
    $('#add_sales_order').on('shown.bs.modal', function () {
        $('#so_barcode').trigger("focus");
    });
    
    $(document).on('click','.btnAdd',function(){
        mode=1;
        products = [];
        refreshTableProduct();
        $('#add_sales_order .modal-title').html("Tambah Pesanan Penjualan");
        $('#add_sales_order input').val("");
        $('#so_discount').val(0).trigger('blur');
        $('#so_cost').val(0).trigger('blur');
        $('#so_ppn').val(0).trigger('blur');
        $('.form-select').not("#so_payment").empty();
        $('.is-invalid').removeClass('is-invalid');
        $('#add_sales_order').modal("show");
        updateTotal();
        
        let today = new Date();
        let yyyy = today.getFullYear();
        let mm = String(today.getMonth() + 1).padStart(2, '0');
        let dd = String(today.getDate()).padStart(2, '0');
        let todayStr = yyyy + '-' + mm + '-' + dd;
        $("#so_date").val(todayStr);
    });

    $(document).on('blur', '#so_ppn', function(){
        var value = $(this).val();
        viewSummary(value, "ppn")
    })

    $(document).on('blur', '#so_cost', function(){
        var value = $(this).val();
        viewSummary(value, "cost")
    })

    $(document).on('blur', '#so_discount', function(){
        var value = $(this).val();
        viewSummary(value, "discount")
    })

    $(document).on('change', '#so_customer', function(){
        $('#so_barcode').trigger("focus");
    })

    $('#so_barcode').on('keyup', function(e) {
        if (e.which === 13) { // 13 = Enter
            const value = $(this).val();

            $.ajax({
                url:"/getProductVariant",
                method:"get",
                data:{
                    search_product: value
                },
                success: function(e) {
                    if (e.length == 1){
                        e.forEach(element => {
                            console.log(element)
                            products.push(element);
                        });
                        toastr.success('', 'Berhasil menambahkan produk');
                    } else{
                        toastr.error('', 'Produk tidak ditemukan');
                    }
                    refreshTableProduct();
                },
                error: function(e) {

                }
            })
            $('#so_barcode').val("");
            $('#so_barcode').trigger("focus");
        } 
    });
    
    function inisialisasi() {
        table = $('#tableSalesOrder').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            ordering: true,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Cari Pesanan Penjualan",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "customer_name" },
                { data: "date" },
                { data: "total" },
                { data: "so_paid" },
                { data: "so_difference" },
                { data: "status_so" },
                { data: "so_cashier" },
                { data: "action", class: "d-flex align-items-center" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });
    }

    function refreshSalesOrder() {
        $.ajax({
            url: "/getSalesOrder",
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                console.log(e);
                table.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].date = moment(e[i].so_date).format('D MMM YYYY');
                    e[i].total = `Rp ${formatRupiah(e[i].so_total)}`;
                    e[i].status_so = 'created';
                    e[i].action = `
                        <a href="/salesOrderDetail/${e[i].so_id}" class="me-2 btn-action-icon p-2 btn_view" data-id="${e[i].so_id}" data-bs-target="#view-sales">
                            <i class="fe fe-eye"></i>
                        </a>
                        <a class="me-2 btn-action-icon p-2 btn_edit" data-id="${e[i].so_id}" data-bs-target="#edit-sales">
                            <i class="fe fe-edit"></i>
                        </a>
                        <a class="p-2 btn-action-icon btn_delete" data-id="${e[i].so_id}" href="javascript:void(0);">
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

    function refreshTableProduct(){
        $('#tableSalesModal').html("");
        var html ="";
        console.log(products)
        products.forEach((p, index) => {
            html += `
                <tr>
                    <td style="width: 25%">${p.product_name}</td>
                    <td style="width: 15%">${p.product_variant_name}</td>
                    <td style="width: 15%">${p.product_variant_sku}</td>
                    <td style="width: 15%" class="text-center p-2 d-flex">
                        <input type="text" class="form-control fill number-only so_qty"
                            data-price="${p.product_variant_price}"
                            data-index="${index}" style="width: 3rem" value="${p.so_qty || 1}">
                        <span class="pt-2 ps-2">
                            <select class="form-select fill" id="unit_id"></select>
                        </span>
                    </td>
                    <td style="width: 15%" class="text-end">${p.product_variant_price}</td>
                    <td style="width: 15%" class="subtotal text-end">${p.so_subtotal || 0}</td>
                </tr>
            `;
        });
        $('#tableSalesModal').append(html);
        $('.so_qty').trigger('blur');
    }

    $(document).on('blur', '.so_qty', function () {
        const index = $(this).data('index');
        let qty = parseInt($(this).val());
        let price = parseInt($(this).data('price'));
        let subtotal = qty * price;

        $(this).closest('tr').find('.subtotal').html(subtotal);
        products[index].so_qty = qty;
        products[index].so_subtotal = subtotal;
        if (qty == 0){
            products.splice(index, 1);
            refreshTableProduct();
        }
        updateTotal();
        $('#so_barcode').trigger("focus");
        console.log(products);
    });

    function updateTotal() {
        let total = 0;
        $(".subtotal").each(function () {
            total += parseInt($(this).text().replace(/,/g, "")) || 0;
        });
        $("#value_total").html(`Rp ${formatRupiah(total)}`);
        // update summary
        $('#so_ppn').trigger('blur')
        $('#so_discount').trigger('blur')
        $('#so_cost').trigger('blur')
        grandTotal()
        $('#so_barcode').trigger("focus");
    }

    function viewSummary(value, from){
        var total = convertToAngka($('#value_total').html());
        var hasil = 0;
        value = convertToAngka(value);
        if (from == "cost") hasil = value;
        else hasil += total*(value/100);
        $(`#value_${from}`).html(`Rp ${formatRupiah(hasil)}`);
        grandTotal()
        $('#so_barcode').trigger("focus");
    }

    function grandTotal(){
        var total = convertToAngka($('#value_total').html());
        var ppn = convertToAngka($('#value_ppn').html());
        var discount = convertToAngka($('#value_discount').html());
        var cost = convertToAngka($('#value_cost').html());
        var grand = total + ppn - discount + cost;
        $('#value_grand').html(`Rp ${formatRupiah(grand)}`)

        
        $('#so_barcode').trigger("focus");
    }

    $(document).on("click",".btn-save",function(){
       LoadingButton(this);
        $('.is-invalid').removeClass('is-invalid');
        var url ="/insertSalesOrder";
        var valid=1;

        $("#add_sales_order .fill").each(function(){
            if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                valid=-1;
                $(this).addClass('is-invalid');
            }
        });

        if(valid==-1){
            notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
            ResetLoadingButton('.btn-save', 'Simpan perubahan');
            return false;
        };

        if ($('#tableSalesModal').html() == ""){
            notifikasi('error', "Gagal Insert", 'Harus ada 1 produk dipilih');
            ResetLoadingButton('.btn-save', 'Simpan perubahan');
            return false;
        }

        param = {
            so_customer: $('#so_customer').val(),
            sales_id: $('#sales_id').val(),
            so_date: $('#so_date').val(),
            so_total: convertToAngka($('#value_grand').html()),
            so_ppn: convertToAngka($('#so_ppn').val()),
            so_cost: convertToAngka($('#so_cost').val()),
            so_discount: convertToAngka($('#so_discount').val()),
            products: JSON.stringify(products),
            _token:token
        };

        if(mode==2){
            url="/updateSalesOrder";
            param.so_id = $('#add_sales_order').attr("so_id");
            param.so_number = $('#add_sales_order').attr("so_number");
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
                if (e == -1){
                    ResetLoadingButton(".btn-save", 'Simpan perubahan');   
                    if (mode == 1) notifikasi("error", "Gagal Insert", "Stock Product tidak mencukupi!");
                    else if (mode == 2) notifikasi("error", "Gagal Update", "Stock Product tidak mencukupi!");
                }
                else{
                    ResetLoadingButton(".btn-save", 'Simpan perubahan');      
                    afterInsert();
                }
            },
            error:function(e){
                ResetLoadingButton(".btn-save", 'Simpan perubahan');
                console.log(e);
            }
        });
    });

    function afterInsert() {
        $(".modal").modal("hide");
        if(mode==1)notifikasi('success', "Berhasil Insert", "Berhasil Tambah Pesanan Penjualan");
        else if(mode==2)notifikasi('success', "Berhasil Update", "Berhasil Update Pesanan Penjualan");
        refreshSalesOrder();
    }

    // $(document).on("keyup","#filter_category_name",function(){
    //     refreshSalesOrder();
    // });

    //edit
    $(document).on("click",".btn_edit",function(){
        var data = $('#tableSalesOrder').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        console.log(data);
        products = [];
        mode=2;
        $('#add_sales_order .modal-title').html("Update Pesanan Penjualan");
        $('#add_sales_order input').empty().val("");
        $('#so_customer').append(`<option value="${data.so_customer}">${data.customer_name}</option>`);
        $('#sales_id').append(`<option value="${data.sales_id}">${data.staff_name}</option>`);
        $('#so_date').val(data.so_date)
        $('#so_discount').val(data.so_discount)
        $('#so_ppn').val(data.so_ppn)
        $('#so_cost').val(data.so_cost)
        $('#so_payment').val(data.so_payment)
        data.items.forEach(e => {
            var temp = {
                "sod_id" : e.sod_id,
                "product_variant_id" : e.product_variant_id,
                "product_name" : e.sod_nama,
                "product_variant_name" : e.sod_variant,
                "product_variant_sku" : e.sod_sku,
                "so_qty" : e.sod_qty,
                "product_variant_price" : e.sod_harga,
                "so_subtotal" : e.sod_subtotal,
                "unit_name" : e.unit_name,
                "unit_id" : e.unit_id
            };
            products.push(temp);
        });
        refreshTableProduct();

        // update summary
        $('#so_ppn').trigger('blur')
        $('#so_discount').trigger('blur')
        $('#so_cost').trigger('blur')
        updateTotal()
        $('#so_barcode').trigger("focus");

        $('.is-invalid').removeClass('is-invalid');
        $('.btn-save').html('Simpan perubahan');
        $('#add_sales_order').modal("show");
        $('#add_sales_order').attr("so_id", data.so_id);
        $('#add_sales_order').attr("so_number", data.so_number);
    });

    //delete
    $(document).on("click",".btn_delete",function(){
        var data = $('#tableSalesOrder').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        showModalDelete("Apakah yakin ingin mengahapus pesanan penjualan ini?","btn-delete-sales");
        $('#btn-delete-sales').attr("so_id", data.so_id);
    });


    $(document).on("click","#btn-delete-sales",function(){
        $.ajax({
            url:"/deleteSalesOrder",
            data:{
                so_id:$('#btn-delete-sales').attr('so_id'),
                _token:token
            },
            method:"post",
            success:function(e){
                $('.modal').modal("hide");
                refreshSalesOrder();
                notifikasi('success', "Berhasil Delete", "Berhasil delete pesanan penjualan");
                
            },
            error:function(e){
                console.log(e);
            }
        });
    });
