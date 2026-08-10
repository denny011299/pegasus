    var mode = 1;
    var table;

    $(document).ready(function () {
        inisialisasi();
    });

    function inisialisasi() {
        var $productTable = $("#tableProduct");

        $productTable.on("processing.dt", function (event, settings, processing) {
            $(settings.nTableWrapper).toggleClass("is-processing", processing);
        });

        table = $productTable.DataTable({
            processing: true,
            serverSide: true,
            deferRender: true,
            // responsive + scrollX sering bikin header/body tidak sinkron
            responsive: false,
            autoWidth: false,
            scrollX: false,
            bFilter: true,
            sDom: "fBtlpi",
            lengthMenu: [10, 25, 50, 100],
            pageLength: 10,
            ordering: true,
            order: [[0, "asc"]],
            searchDelay: 400,
            language: {
                search: " ",
                sLengthMenu: "_MENU_",
                searchPlaceholder: "Cari Produk",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> ',
                },
            },
            ajax: {
                url: "/getProduct",
                type: "GET",
            },
            columns: [
                { data: "product_name", width: "18%" },
                { data: "product_category", width: "12%" },
                { data: "unit_values", width: "12%" },
                { data: "variant_values", width: "35%" },
                { data: "created_by_name", defaultContent: "-", width: "13%" , render: function(data) { return typeof renderCreatedByName === "function" ? renderCreatedByName(data) : data; } },
                {
                    data: "action",
                    className: "text-center align-middle",
                    width: "10%",
                    orderable: false,
                    searchable: false,
                },
            ],
            initComplete: function () {
                var $filter = $(".dataTables_filter");
                $filter.appendTo(".search-input");
                $filter.find("label").prepend('<i class="fa fa-search"></i> ');
                this.api().columns.adjust();
            },
            drawCallback: function () {
                this.api().columns.adjust();
            },
        });
    }

    function refreshProduct() {
        if (table) table.ajax.reload(null, false);
    }

    // delete
    $(document).on("click", ".btn_delete", function () {
        var data = $("#tableProduct").DataTable().row($(this).parents("tr")).data();
        showModalDelete("Apakah yakin ingin menghapus produk ini?", "btn-delete-product");
        $("#btn-delete-product").attr("product_id", data.product_id);
        $("#modalDelete").modal("show");
    });

    $(document).on("click", "#btn-delete-product", function () {
        $.ajax({
            url: "/deleteProduct",
            data: {
                product_id: $("#btn-delete-product").attr("product_id"),
                _token: token,
            },
            method: "post",
            success: function (e) {
                $(".modal").modal("hide");
                refreshProduct();
                notifikasi("success", "Berhasil Delete", "Berhasil delete produk");
            },
            error: function (e) {
                if (handlePermissionError(e)) return;
                console.log(e);
            },
        });
    });
