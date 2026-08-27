    var mode=1;
    var table;
    $(document).ready(function(){
        inisialisasi();
        refreshCustomer();
    });
    
    function inisialisasi() {
        if ($.fn.DataTable.isDataTable("#tableCustomer")) {
            $("#tableCustomer").DataTable().destroy();
        }
        table = $('#tableCustomer').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            lengthMenu: [10, 25, 50, 100],
            ordering: true,
            order: [[4, "desc"]],
            autoWidth: false,
            scrollX: false,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Cari Armada",
                info: "_START_ - _END_ of _TOTAL_ items",
                emptyTable: "Tidak ada data armada",
                zeroRecords: "Armada tidak ditemukan",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "customer_notes", width: "16%" },
                { data: "customer_pic", width: "16%" },
                { data: "customer_pic_phone", width: "14%" },
                { data: "saldo", width: "14%" },
                {
                    data: "created",
                    width: "12%",
                    render: function (data, type, row) {
                        if (type === "sort" || type === "type") {
                            return row.created_at || "";
                        }
                        return data;
                    },
                },
                { data: "created_by_name", defaultContent: "-", width: "16%" , render: function(data, type, row) { return typeof renderCreatedBySync === "function" ? renderCreatedBySync(data, row) : data; } },
                {
                    data: "action",
                    className: "text-center align-middle",
                    width: "12%",
                    orderable: false,
                },
            ],
            initComplete: function () {
                var $filter = $('.dataTables_filter').last();
                $filter.appendTo('#tableSearch');
                $filter.appendTo('.search-input');
                if (!$filter.find('label .fa-search').length) {
                    $filter.find('label').prepend('<i class="fa fa-search"></i> ');
                }
                if (table) table.columns.adjust();
            },
            drawCallback: function () {
                if (table) table.columns.adjust();
            },
        });
    }

    function refreshCustomer() {
        $('#tableCustomer-wrap').addClass('dt-pending');
        $.ajax({
            url: "/getCustomer",
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original;
                }

                table.clear().draw(); 
                for (let i = 0; i < e.length; i++) {
                    e[i].created = moment(e[i].created_at).format('D MMM YYYY');
                    e[i].saldo = `Rp ${formatRupiah(e[i].customer_saldo)}`
                    var ce =
                        roleIconEdit(
                            "Armada",
                            "me-2 btn-action-icon p-2 btn_edit",
                            'href="/updateCustomer/' +
                                e[i].customer_id +
                                '" data-bs-target="#edit-supplier"'
                        ) +
                        roleIconDelete(
                            "Armada",
                            "p-2 btn-action-icon btn_delete",
                            'data-id="' +
                                e[i].customer_id +
                                '" href="javascript:void(0);"'
                        );
                    e[i].action =
                        ce ||
                        '<span class="text-muted small">—</span>';
                }

                table.rows.add(e).draw();
                if (typeof feather !== "undefined") feather.replace();
                if (table) table.columns.adjust();
                $('#tableCustomer-wrap').removeClass('dt-pending');
            },
            error: function (err) {
                if (handlePermissionError(err)) return;
                console.error("Gagal load:", err);
                $('#tableCustomer-wrap').removeClass('dt-pending');
            }
        });
    }

    $(document).on("click",".btn_delete",function(){
        var data = $('#tableCustomer').DataTable().row($(this).parents('tr')).data();
        showModalDelete("Apakah yakin ingin menghapus Armada ini?","btn-delete-customer");
        $('#btn-delete-customer').attr("customer_id", data.customer_id);
    });


    $(document).on("click","#btn-delete-customer",function(){
        $.ajax({
            url:"/deleteCustomer",
            data:{
                customer_id:$('#btn-delete-customer').attr('customer_id'),
                _token:token
            },
            method:"post",
            success:function(e){
                $('.modal').modal("hide");
                refreshCustomer();
                notifikasi('success', "Berhasil Delete", "Berhasil delete Armada");
                
            },
            error:function(e){
                if (handlePermissionError(e)) return;
                console.log(e);
            }
        });
    });
