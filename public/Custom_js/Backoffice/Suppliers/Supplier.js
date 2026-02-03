    var mode=1;
    var table;
    var activeId = 0;
    var dates = null;
    $(document).ready(function(){
        inisialisasi();
        refreshSupplier();
    });
    
    function inisialisasi() {
        table = $('#tableSupplier').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            lengthMenu: [10, 25, 50, 100],
            ordering: true,
            autoWidth: false,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Cari Pemasok",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "supplier_name" },
                { data: "supplier_code" },
                { data: "supplier_phone" },
                { data: "city_name" },
                { data: "pay" },
                { data: "created" },
                { data: "action", class: "d-flex align-items-center" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });
    }

    function refreshSupplier() {
        $.ajax({
            url: "/getSupplier",
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }

                table.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].created = moment(e[i].created_at).format('D MMM YYYY');
                    e[i].pay = `Rp ${formatRupiah(e[i].payment)}`;
                    // <a class="me-2 btn-action-icon p-2 btn_view" href="/supplierDetail/${e[i].supplier_id}" data-bs-target="#view-supplier">
                    //     <i class="fe fe-eye"></i>
                    // </a>
                    e[i].action = `
                        <a class="me-2 btn-action-icon p-2 btn_view" data-id="${e[i].supplier_id}" data-bs-target="#view-supplier">
                            <i class="fe fe-eye"></i>
                        </a>
                        <a class="me-2 btn-action-icon p-2 btn_edit" href="/updateSupplier/${e[i].supplier_id}" data-bs-target="#edit-supplier">
                            <i class="fe fe-edit"></i>
                        </a>
                        <a class="p-2 btn-action-icon btn_delete" data-id="${e[i].supplier_id}" href="javascript:void(0);">
                            <i class="fe fe-trash-2"></i>
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

    // $(document).on("keyup","#filter_supplier_name",function(){
    //     refreshSupplier();
    // });

    // View
    $(document).on('click', '.btn_view', function(){
        let data = $('#tableSupplier').DataTable().row($(this).parents('tr')).data();
        console.log(data);
        $('#supplier_name').html(data.supplier_name);
        $('#supplier_phone').html(data.supplier_phone);
        $('#supplier_address').html(data.supplier_address);
        $('#supplier_notes').html(data.supplier_notes || "-" );
        $('#supplier_payment').html(`Rp ${formatRupiah(data.payment)}`);
        $('#tablePo tr.row-po').remove();
        $('#tablePo tr.empty-data').remove();
        $('#supplier_payment_bawah').html("Rp 0");
        $('#status').val("");
        activeId = data.supplier_id;
        // Menghindari bug tampilan
        if (data.supplier_id != 0){
            getPo(data.supplier_id);
        }
    })

    function getPo(id) {
        $.ajax({
            url: '/getPurchaseOrder',
            method: 'get',
            data: {
                po_supplier: id,
                dates: dates,
                pembayaran: $('#status').val()
            },
            success: function(e){
                console.log(e);
                viewHistory(e);
            },
            error: function(e){
                console.error("Gagal load:", e);
            }
        });
    }

    function viewHistory(data){
        $('#tablePo tr.row-po').remove();
        $('#tablePo tr.empty-data').remove();
        let hutang = 0;
        if (data.length > 0){
            $('.empty-data').remove();
            data.forEach(e => {
                $('#tablePo tbody').append(`
                    <tr class="row-po" data-id="${e.po_id}">
                        <td>${moment(e.po_date).format('D MMM YYYY')}</td>
                        <td>${e.poi_due != "-" ? moment(e.poi_due).format('D MMM YYYY') : "-"}</td>
                        <td>${e.poi_code || "-"}</td>
                        <td class="fw-bold">Rp ${formatRupiah(e.po_total)}</td>
                    </tr>
                `)
                hutang += e.po_total;
            })
        } else {
            $('#tablePo tbody').append(`
                <tr class="empty-data">
                    <td colspan="4" class="text-center text-muted py-4">
                        Invoice tidak ditemukan
                    </td>
                </tr>
            `);
        }

        $('#view_supplier .modal-title').html("Lihat Detail Supplier");
        $('#supplier_payment_bawah').html(`Rp ${formatRupiah(hutang)}`);
        $("#view_supplier").modal("show");
    }

    //delete
    $(document).on("click",".btn_delete",function(){
        var data = $('#tableSupplier').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        showModalDelete("Apakah yakin ingin menghapus pemasok ini?","btn-delete-supplier");
        $('#btn-delete-supplier').attr("supplier_id", data.supplier_id);
    });


    $(document).on("click","#btn-delete-supplier",function(){
        $.ajax({
            url:"/deleteSupplier",
            data:{
                supplier_id:$('#btn-delete-supplier').attr('supplier_id'),
                _token:token
            },
            method:"post",
            success:function(e){
                $('.modal').modal("hide");
                refreshSupplier();
                notifikasi('success', "Berhasil Delete", "Berhasil delete pemasok");
                
            },
            error:function(e){
                console.log(e);
            }
        });
    });

    $(document).on('change', '#status', function(){
        getPo(activeId);
    })

    $(document).on('change', '#start_date', function(){
        dates = [];
        var start = $('#start_date').val();
        var end = $('#end_date').val();
        dates.push(start);
        dates.push(end);
        getPo(activeId);
    })
    $(document).on('change', '#end_date', function(){
        dates = [];
        var start = $('#start_date').val();
        var end = $('#end_date').val();
        dates.push(start);
        dates.push(end);
        getPo(activeId);
    })

    $(document).on('click', '.btn-clear', function(){
        dates = null;
        $('#start_date').val("");
        $('#end_date').val("");
        getPo(activeId);
    })