(function () {
    var table = null;

    function fmtRp(n) {
        if (n === null || n === undefined || isNaN(n)) return "Rp 0";
        return "Rp " + Number(n).toLocaleString("id-ID");
    }

    function todayYmd() {
        var d = new Date();
        return d.toISOString().slice(0, 10);
    }

    function monthYm() {
        return todayYmd().slice(0, 7);
    }

    function yearY() {
        return String(new Date().getFullYear());
    }

    function initTable() {
        table = $("#tableCashOutReport").DataTable({
            bFilter: true,
            sDom: "fBtlpi",
            lengthMenu: [10, 25, 50, 100],
            ordering: false,
            language: {
                search: " ",
                sLengthMenu: "_MENU_",
                searchPlaceholder: "Cari deskripsi / tujuan...",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> ',
                },
            },
            columns: [
                { data: "cash_date_text" },
                { data: "cash_description" },
                { data: "cash_type_label" },
                { data: "cash_tujuan_label" },
                { data: "cash_nominal_text", className: "text-end" },
                { data: "created_by_name" },
                { data: "acc_by_name" },
            ],
        });
    }

    function updateFilterVisibility() {
        var mode = String($("#cash_out_filter_mode").val() || "month");
        $("#wrap_filter_day").toggle(mode === "day");
        $("#wrap_filter_month").toggle(mode === "month");
        $("#wrap_filter_year").toggle(mode === "year");
    }

    function buildFilterPayload() {
        return {
            filter_mode: $("#cash_out_filter_mode").val(),
            filter_day: $("#cash_out_filter_day").val(),
            filter_month: $("#cash_out_filter_month").val(),
            filter_year: $("#cash_out_filter_year").val(),
        };
    }

    function loadReport() {
        $.ajax({
            url: "/getReportCashOut",
            method: "get",
            data: buildFilterPayload(),
            success: function (res) {
                var rows = (res && res.rows) || [];
                var summary = (res && res.summary) || {};
                for (var i = 0; i < rows.length; i++) {
                    rows[i].cash_date_text = moment(rows[i].cash_date).format("D MMM YYYY");
                    rows[i].cash_nominal_text = fmtRp(rows[i].cash_nominal);
                }
                table.clear().rows.add(rows).draw();
                $("#cash_out_period_label").text(summary.period_label || "-");
                $("#cash_out_total_pengeluaran").text(fmtRp(summary.total_pengeluaran || 0));
                $("#cash_out_total_transaksi").text(String(summary.jumlah_transaksi || 0));
            },
            error: function () {
                table.clear().draw();
                $("#cash_out_period_label").text("Gagal memuat data");
                $("#cash_out_total_pengeluaran").text("Rp 0");
                $("#cash_out_total_transaksi").text("0");
            },
        });
    }

    $(document).ready(function () {
        $("#cash_out_filter_day").val(todayYmd());
        $("#cash_out_filter_month").val(monthYm());
        $("#cash_out_filter_year").val(yearY());
        initTable();
        updateFilterVisibility();
        loadReport();

        $("#cash_out_filter_mode").on("change", function () {
            updateFilterVisibility();
        });
        $("#btn_apply_cash_out_filter").on("click", loadReport);
        $("#btn_reset_cash_out_filter").on("click", function () {
            $("#cash_out_filter_mode").val("month");
            $("#cash_out_filter_day").val(todayYmd());
            $("#cash_out_filter_month").val(monthYm());
            $("#cash_out_filter_year").val(yearY());
            updateFilterVisibility();
            loadReport();
        });
    });
})();
