    var mode=1;
    var table;
    var type, list_photo;
    var items = [];
    var sisa_kas = 0;
    var dates = null;

    $(document).ready(function(){
        $('#cashType').trigger('change');
    });

    $(document).on('change', '#cashType', function () {
        items = [];
        type = $(this).val();

        // Setting filter
        $('#filter_staff_id').empty(null);
        $('#filter_customer_id').empty(null);
        $('#filter_sales_id').empty(null);

        if (type === 'admin') {
            inisialisasi();
            $('#tableCash thead th').eq(2).text('Staff');
            $('.filter_person').html(`
                <label class="form-label mb-1">Staff</label>
                <select class="form-select" id="filter_staff_id"></select>
            `);
            autocompleteStaff('#filter_staff_id');
            $('.total-summary').hide();
            refreshCashAdmin();
        }
        
        else if (type === 'gudang') {
            inisialisasi();
            $('#tableCash thead th').eq(2).text('Staff');
            $('.filter_person').html(`
                <label class="form-label mb-1">Staff</label>
                <select class="form-select" id="filter_staff_id"></select>
            `);
            autocompleteStaff('#filter_staff_id');
            $('.total-summary').hide();
            refreshCashGudang();
        }
        
        else if (type === 'armada') {
            inisialisasi();
            $('#tableCash thead th').eq(2).text('Armada');
            $('.filter_person').html(`
                <label class="form-label mb-1">Armada</label>
                <select class="form-select" id="filter_customer_id"></select>
            `);
            $('.info_card').html(`
                <div class="fw-bold text-end text-black p-0">
                    <i class="fe fe-dollar-sign"></i> Kas Armada : <span id="totalArmada">Rp 0</span>
                </div>    
            `);
            autocompleteCustomer('#filter_customer_id');
            $('.total-summary').show();
            refreshCashArmada();
        }

        else if (type === 'sales') {
            inisialisasi();
            $('#tableCash thead th').eq(2).text('Sales');
            $('.filter_person').html(`
                <label class="form-label mb-1">Sales</label>
                <select class="form-select" id="filter_sales_id"></select>
            `);
            $('.info_card').html(`
                <div class="fw-bold text-end text-black p-0">
                    <i class="fe fe-dollar-sign"></i> Kas Sales : <span id="totalSales">Rp 0</span>
                </div>    
            `);
            autocompleteStaffSales('#filter_sales_id');
            $('.total-summary').show();
            refreshCashSales();
        }
    });

    $(document).on('change', '#jenis_input', function(){
        items = [];
        if ($(this).val() == "saldo") {
            $('.saldo_kas').show();
            $('.operasional').hide();
        } else if ($(this).val() == "operasional") {
            $('.saldo_kas').hide();
            $('.operasional').show();
        }
        $('#add_cash_admin input').val("");
        $('#staff_id').empty(null);
        $('.is-invalid').removeClass('is-invalid');
        $('.is-invalids').removeClass('is-invalids');
        $('#oc_transaksi').val(1);
        $('.total').html("Rp 0");
        autocompleteStaff('#staff_id', '#add_cash_admin');
        $('#btn-foto-bukti').show();
        $('#btn-lihat-bukti').hide();
        $('#check_foto').hide();

        if ($(this).val() == "operasional") $('#tableDetail tr.row-detail').remove();
    })

    $(document).on('change', '#jenis_input_gudang', function(){
        items = [];
        if ($(this).val() == "saldo") {
            $('.saldo_kas').show();
            $('.operasional').hide();
        } else if ($(this).val() == "operasional") {
            $('.saldo_kas').hide();
            $('.operasional').show();
        }
        $('#add_cash_gudang input').val("");
        $('#staff_id_gudang').empty(null);
        $('.is-invalid').removeClass('is-invalid');
        $('.is-invalids').removeClass('is-invalids');
        $('#oc_transaksi_gudang').val(1);
        $('.total_gudang').html("Rp 0");
        autocompleteStaff('#staff_id_gudang', '#add_cash_gudang');
        $('#btn-foto-bukti-gudang').show();
        $('#btn-lihat-bukti-gudang').hide();
        $('#check_foto_gudang').hide();

        if ($(this).val() == "operasional") $('#tableDetailGudang tr.row-detail').remove();
    })
    $(document).on('change', '#jenis_input_armada', function(){
        items = [];
        if ($(this).val() == "saldo") {
            $('.saldo_kas').show();
            $('.operasional').hide();
        } else if ($(this).val() == "operasional") {
            $('.saldo_kas').hide();
            $('.operasional').show();
        }
        $('#add_cash_armada input').val("");
        $('#customer_id_armada').empty(null).trigger('change');
        $('.is-invalid').removeClass('is-invalid');
        $('.is-invalids').removeClass('is-invalids');
        $('#oc_transaksi_armada').val(1);
        $('.total_armada').html("Rp 0");
        autocompleteCustomer('#customer_id_armada', '#add_cash_armada');
        $('#btn-foto-bukti-armada').show();
        $('#btn-lihat-bukti-armada').hide();
        $('#check_foto_armada').hide();

        if ($(this).val() == "operasional") $('#tableDetailArmada tr.row-detail').remove();
    })
    $(document).on('change', '#jenis_input_sales', function(){
        items = [];
        if ($(this).val() == "saldo") {
            $('.saldo_kas').show();
            $('.operasional').hide();
            $('#aksi_sales').val(1).trigger('change');
        } else if ($(this).val() == "operasional") {
            $('.saldo_kas').hide();
            $('.operasional').show();
        }
        $('#add_cash_sales input').val("");
        $('#staff_id_sales, #bank_account, #cc_id').empty(null);
        $('.is-invalid').removeClass('is-invalid');
        $('.is-invalids').removeClass('is-invalids');
        $('#oc_transaksi_sales').val(1);
        $('.total_sales').html("Rp 0");
        autocompleteStaffSales('#staff_id_sales', '#add_cash_sales');
        autocompleteRekening('#bank_account', '#add_cash_sales');
        $('#btn-foto-bukti-sales').show();
        $('#btn-lihat-bukti-sales').hide();
        $('#check_foto_sales').hide();

        if ($(this).val() == "operasional") $('#tableDetailSales tr.row-detail').remove();
    })

    $(document).on('click','.btnAddCash',function(){
        mode=1;
        items = [];
        
        if (type == "admin"){
            $('#add_cash_admin input').val("").attr('disabled', false);
            $('#jenis_input, #staff_id, #oc_transaksi').attr('disabled', false);
            $('#oc_transaksi').val(1).attr('disabled', false).trigger('change');

            $('#row-cash').html(`
                <label>Nama Pengaju<span class="text-danger">*</span></label>
                <select class="form-select fill" id="staff_id"></select>
            `);

            $('#add_cash_admin .modal-title').html("Tambah Aktivitas Admin");
            $('#staff_id').empty(null).attr('disabled', false);
            $('#jenis_input').val("saldo").attr('disabled', false).trigger('change');
            autocompleteStaff('#staff_id', '#add_cash_admin');
            $('.total').html("Rp 0");

            $('#btn-foto-bukti').show();
            $('#btn-lihat-bukti').hide();
            $('#check_foto').hide();

            if ($('#jenis_input').val() == "operasional") $('#tableDetail tr.row-detail').remove();
            
            $('#add_cash_admin').modal("show");
            $('.input_table, .btn_delete_row').show();
            $('.btn-save-admin').html('Tambah Aktivitas').show();
        }
        else if (type == "gudang"){
            $('#add_cash_gudang input').val("").attr('disabled', false);
            $('#jenis_input_gudang, #staff_id_gudang, #oc_transaksi_gudang').attr('disabled', false);
            $('#oc_transaksi_gudang').val(1).attr('disabled', false).trigger('change');
            $('#cgd_nominal').attr('disabled', true);
            $('.input_nominal').hide();
            $('#jenis_nominal').attr('disabled', false).val("");

            $('#add_cash_gudang .modal-title').html("Tambah Aktivitas Gudang");
            $('#staff_id_gudang').empty(null).attr('disabled', false);
            $('#customer_id').empty(null);
            $('#jenis_input_gudang').val("saldo").attr('disabled', false).trigger('change');
            autocompleteStaff('#staff_id_gudang', '#add_cash_gudang');
            autocompleteCustomer('#customer_id', '#add_cash_gudang');
            $('.total_gudang').html("Rp 0");

            $('#btn-foto-bukti-gudang').show();
            $('#btn-lihat-bukti-gudang').hide();
            $('#check_foto_gudang').hide();

            if ($('#jenis_input_gudang').val() == "operasional") $('#tableDetailGudang tr.row-detail').remove();
            $('#add_cash_gudang').modal("show");
            $('.input_table, .btn_delete_row_gudang').show();
            $('.btn-save-gudang').html('Tambah Aktivitas').show();
        }
        else if (type == "armada"){
            $('#add_cash_armada input').val("").attr('disabled', false);
            $('#jenis_input_armada').attr('disabled', false).trigger('change');
            $('#oc_transaksi_armada').val(1).attr('disabled', false).show().trigger('change');
            $('#cc_id').empty(null).attr('disabled', false);
            $('#add_cash_armada .modal-title').html("Tambah Aktivitas Armada");
            $('#customer_id_armada').empty(null).attr('disabled', false);
            $('#jenis_input_armada').val("saldo").attr('disabled', false).trigger('change');
            autocompleteCustomer('#customer_id_armada', '#add_cash_armada');
            autocompleteCashCategory('#cc_id', '#add_cash_armada');
            $('.total_armada').html("Rp 0");

            $('#btn-foto-bukti-armada').show();
            $('#btn-lihat-bukti-armada').hide();
            $('#check_foto_armada').hide();

            $('#tableDetailArmada tr.row-detail').remove();
            $('#add_cash_armada').modal("show");
            $('.input_table, .btn_delete_row_armada').show();
            $('.btn-save-armada').html('Tambah Aktivitas').show();
        }
        else if (type == "sales"){
            $('#add_cash_sales input').val("").attr('disabled', false);
            $('#jenis_input_sales').attr('disabled', false);
            $('#oc_transaksi_sales').val(1).attr('disabled', false).show().trigger('change');
            $('#add_cash_sales .modal-title').html("Tambah Aktivitas Sales");
            $('#staff_id_sales, #bank_account, #cc_id_sales').empty(null).attr('disabled', false);
            $('#jenis_input_sales').val("saldo").attr('disabled', false).trigger('change');
            $('#aksi_sales').val(1).attr('disabled', false).trigger('change');
            autocompleteStaffSales('#staff_id_sales', '#add_cash_sales');
            autocompleteCashCategory('#cc_id_sales', '#add_cash_sales');
            autocompleteRekening('#bank_account', '#add_cash_sales');
            $('.total_sales').html("Rp 0");

            $('#btn-foto-bukti-sales').show();
            $('#btn-lihat-bukti-sales').hide();
            $('#check_foto_sales').hide();

            $('#tableDetailSales tr.row-detail').remove();
            $('#add_cash_sales').modal("show");
            $('.input_table, .btn_delete_row_sales').show();
            $('.btn-save-sales').html('Tambah Aktivitas').show();
        }

        $('.is-invalid').removeClass('is-invalid');
        $('.is-invalids').removeClass('is-invalids');
        $('.cancel-btn').html('Batal');
    });

    $(document).on('change', '#oc_transaksi', function(){
        console.log($(this).val())
        if ($(this).val() == 1){
            $('#oc_nominal').val("").attr('disabled', false);
        } else {
            $('#oc_nominal').val(formatRupiah(sisa_kas)).attr('disabled', true);
        }
    })

    $(document).on('change', '#oc_transaksi_gudang', function(){
        if ($(this).val() == 1){
            $('#oc_nominal_gudang').val("").attr('disabled', false);
        } else {
            $('#oc_nominal_gudang').val(formatRupiah(sisa_kas)).attr('disabled', true);
        }
    })

    $(document).on('change', '#customer_id_armada', function(){
        if (mode == 1 && $('#jenis_input_armada').val() == "saldo"){
            if ($(this).val()){
                var temp = $('#customer_id_armada').select2("data")[0];
                $('#oc_nominal_armada').val(formatRupiahMinus(temp.customer_saldo)).attr('disabled', true);
            } else {
                $('#oc_nominal_armada').val("").attr('disabled', false);
            }
        }
    })

    $(document).on('change', '#aksi_sales', function(){
        if ($('#jenis_input_sales').val() == "saldo"){
            if ($(this).val() == 2) {
                $('.banks').show();
                $('#oc_notes_sales').val("").attr('disabled', true);
                $('#bank_account').trigger('change');
            } else {
                $('.banks').hide();
                $('#oc_notes_sales').val("").attr('disabled', false);
            }
            if ($(this).val() == 3 && $('#staff_id_sales').val()){
                var temp = $('#staff_id_sales').select2("data")[0];
                $('#oc_nominal_sales').val(formatRupiahMinus(temp.staff_saldo)).attr('disabled', true);
            } else if ($(this).val() == 2 && $('#staff_id_sales').val()){
                var temp = $('#staff_id_sales').select2("data")[0];
                $('#oc_nominal_sales').val(formatRupiahMinus(temp.staff_saldo)).attr('disabled', false);
            } else {
                $('#oc_nominal_sales').val("").attr('disabled', false);
            }
        }
    })

    $(document).on('change', '#bank_account', function(){
        if ($(this).val()){
            var temp = $('#bank_account').select2('data')[0];
            $('#oc_notes_sales').val(`Setor ke bank ${temp.bank_kode}`);
        } else {
            $('#oc_notes_sales').val("");
        }
    })

    // Khusus untuk saldo di dompet Sales
    $(document).on('change', '#staff_id_sales', function(){
        if ($('#jenis_input_sales').val() == "saldo" && $('#aksi_sales').val() >= 2){
            $('#aksi_sales').trigger('change');
        }
    })


    // Input nominal Operasional Gudang
    $(document).on('change', '#jenis_nominal', function() {
        if ($(this).val() == "manual"){
            $('#cgd_nominal').val("").attr('disabled', false);
            $('.input_nominal').show();
        }
        else if ($(this).val() == "" || $(this).val() == null || $(this).val() == "null") {
            $('.input_nominal').hide();
        }
        else {
            $('#cgd_nominal').val(formatRupiah($(this).val())).attr('disabled', true);
            $('.input_nominal').hide();
        }
    })

    $(document).on('change', '#filter_customer_id', function(){
        refreshCashArmada();
    })
    $(document).on('change', '#filter_sales_id', function(){
        refreshCashSales();
    })
    
    function inisialisasi() {
        if ($.fn.DataTable.isDataTable('#tableCash')) {
            $('#tableCash').DataTable().destroy();
            $('#tableCash tbody').empty();
        }

        let column;
        let searchText;

        if (type === "admin") {
            column = [
                {
                    className: 'dt-control text-center',
                    orderable: false,
                    data: null,
                    defaultContent: '<i class="fe fe-plus-circle text-primary"></i>',
                    width: "2.5rem"
                },
                { data: "date", width: "12%" },
                { data: "staff_name", width: "12%" },
                { data: "ca_notes", width: "22%" },
                { data: "debit_text", className: "text-end", width: "15%" },
                { data: "credit_text", className: "text-end", width: "15%" },
                { data: "status_text", width: "13%" },
                { data: "action", className: "d-flex align-items-center", width: "80px" },
            ];
            searchText = "Cari Kas Admin";
        } 
        else if (type === "gudang") {
            column = [
                {
                    className: 'dt-control text-center',
                    orderable: false,
                    data: null,
                    defaultContent: '<i class="fe fe-plus-circle text-primary"></i>',
                    width: "2.5rem"
                },
                { data: "date", width: "12%" },
                { data: "staff_name", width: "12%" },
                { data: "cg_notes", width: "22%" },
                { data: "debit_text", className: "text-end", width: "15%" },
                { data: "credit_text", className: "text-end", width: "15%" },
                { data: "status_text", width: "13%" },
                { data: "action", className: "d-flex align-items-center", width: "80px" },
            ];
            searchText = "Cari Kas Gudang";
        }
        else if (type === "armada") {
            column = [
                {
                    className: 'dt-control text-center',
                    orderable: false,
                    data: null,
                    defaultContent: '<i class="fe fe-plus-circle text-primary"></i>',
                    width: "2.5rem"
                },
                { data: "date", width: "12%" },
                { data: "customer_notes", width: "12%" },
                { data: "cr_notes", width: "22%" },
                { data: "debit_text", className: "text-end", width: "15%" },
                { data: "credit_text", className: "text-end", width: "15%" },
                { data: "status_text", width: "13%" },
                { data: "action", className: "d-flex align-items-center", width: "80px" },
            ];
            searchText = "Cari Kas Armada";
        }
        else if (type === "sales") {
            column = [
                {
                    className: 'dt-control text-center',
                    orderable: false,
                    data: null,
                    defaultContent: '<i class="fe fe-plus-circle text-primary"></i>',
                    width: "2.5rem"
                },
                { data: "date", width: "12%" },
                { data: "staff_name", width: "12%" },
                { data: "cs_notes", width: "22%" },
                { data: "debit_text", className: "text-end", width: "15%" },
                { data: "credit_text", className: "text-end", width: "15%" },
                { data: "status_text", width: "13%" },
                { data: "action", className: "d-flex align-items-center", width: "80px" },
            ];
            searchText = "Cari Kas Sales";
        }

        table = $('#tableCash').DataTable({
            destroy: true,
            bFilter: true,
            sDom: 'fBtlpi',
            lengthMenu: [10, 25, 50, 100],
            ordering: false,
            searching: false,
            responsive: false,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: searchText,
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: '<i class="fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i>'
                },
            },
            columns: column,
            initComplete: function () {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });
    }

    function refreshCashAdmin() {
        $.ajax({
            url: "/getCashAdmin",
            data: {
                dates: dates,
                staff_id: $('#filter_staff_id').val()
            },
            method: "get",
            success: function (data) {
                // if (!Array.isArray(e)) {
                //     e = e.original || [];
                // }
                table.clear().draw(); 

                // Manipulasi data sebelum masuk ke tabel
                console.log(data['data']);
                let e = data['data'];
                let debits = 0;
                let credits = 0;
                for (let i = 0; i < e.length; i++) {
                    e[i].date = moment(e[i].created_at).format('D MMM YYYY');
                    if (e[i].ca_aksi == 1){
                        e[i].debit = "Rp " + formatRupiah(e[i].ca_nominal);
                        e[i].credit = "Rp 0";
                        if (e[i].status == 2) debits += e[i].ca_nominal;
                    }
                    else{
                        e[i].debit = "Rp 0";
                        e[i].credit = "(Rp " + formatRupiah(e[i].ca_nominal) + ")";
                        if (e[i].status == 2) credits += e[i].ca_nominal;
                    }
                    e[i].debit_text =`<label class='text-success'>${e[i].debit}</label>`
                    e[i].credit_text =`<label class='text-danger'>${e[i].credit}</label>`

                    if (e[i].status == 1){
                        e[i].status_text = `<span class="badge bg-warning" style="font-size: 12px">Sedang Diajukan</span>`;
                    } else if (e[i].status == 2){
                        e[i].status_text = `<span class="badge bg-success" style="font-size: 12px">Diterima</span>`;
                    } else if (e[i].status == 3){
                        e[i].status_text = `<span class="badge bg-danger" style="font-size: 12px">Ditolak</span>`;
                    }
                    e[i].action = `
                        <a class="me-2 btn-action-icon p-2 btn_view_admin" data-id="${e[i].ca_id}" data-bs-target="#view-cash">
                            <i class="fe fe-eye"></i>
                        </a>
                    `;
                    if (e[i].status == 1){
                        if (e[i].ca_type == 1){
                            e[i].action += `
                                <a class="me-2 btn-action-icon p-2 btn_edit_admin" data-id="${e[i].ca_id}" data-bs-target="#edit-category">
                                    <i class="fe fe-edit"></i>
                                </a>
                                <a class="p-2 btn-action-icon btn_delete_admin" data-id="${e[i].ca_id}" href="javascript:void(0);">
                                    <i class="fe fe-trash-2"></i>
                                </a>
                            `;
                        } else if (e[i].ca_type == 2){
                            e[i].action += `
                                <a class="me-2 btn-action-icon p-2 btn_acc bg-success text-light" data-bs-toggle="tooltip"
                                data-bs-placement="bottom" title="Terima"  cash_id = "${e[i].cash_id}" >
                                    <i class="fe fe-check"></i>
                                </a>
                                <a  class="me-2 btn-action-icon p-2 btn_decline bg-danger text-light" data-bs-toggle="tooltip"
                                data-bs-placement="bottom" title="Tolak"  cash_id = "${e[i].cash_id}" >
                                    <i class="fe fe-x"></i>
                                </a>
                            `;
                        }
                    }
                }
                table.rows.add(e).draw();
                $('.debits').html(`Rp ${formatRupiah(debits)}`);
                $('.credits').html(`(Rp ${formatRupiah(credits)})`);
                $('.sisa').html(`Rp ${formatRupiah(data['sisa_kas'])}`);
                sisa_kas = data['sisa_kas'];

                // Expand child row
                setTimeout(function () {
                    $('#tableCash tbody td.dt-control').each(function () {
                        $(this).trigger('click');
                    });
                }, 100);

                feather.replace(); // Biar icon feather muncul lagi
            },
            error: function (err) {
                console.error("Gagal load kategori:", err);
            }
        });
    }

    function refreshCashGudang() {
        $.ajax({
            url: "/getCashGudang",
            data: {
                dates: dates,
                staff_id: $('#filter_staff_id').val()
            },
            method: "get",
            success: function (data) {
                // if (!Array.isArray(data)) {
                //     data = data.original || [];
                // }
                table.clear().draw(); 
                console.log(data['data']);
                e = data['data'];
                let debits = 0;
                let credits = 0;
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].date = moment(e[i].created_at).format('D MMM YYYY');
                    if (e[i].cg_aksi == 1){
                        e[i].debit = "Rp " + formatRupiah(e[i].cg_nominal);
                        e[i].credit = "Rp 0";
                        if (e[i].status == 2) debits += e[i].cg_nominal;
                    }
                    else{
                        e[i].debit = "Rp 0";
                        e[i].credit = "(Rp " + formatRupiah(e[i].cg_nominal) + ")";
                        if (e[i].status == 2) credits += e[i].cg_nominal;
                    }

                    e[i].debit_text =`<label class='text-success'>${e[i].debit}</label>`
                    e[i].credit_text =`<label class='text-danger'>${e[i].credit}</label>`

                    if (e[i].status == 1){
                        e[i].status_text = `<span class="badge bg-warning" style="font-size: 12px">Sedang Diajukan</span>`;
                    } else if (e[i].status == 2){
                        e[i].status_text = `<span class="badge bg-success" style="font-size: 12px">Diterima</span>`;
                    } else if (e[i].status == 3){
                        e[i].status_text = `<span class="badge bg-danger" style="font-size: 12px">Ditolak</span>`;
                    }

                    e[i].action = `
                        <a class="me-2 btn-action-icon p-2 btn_view_gudang" data-id="${e[i].cg_id}" data-bs-target="#view-cash">
                            <i class="fe fe-eye"></i>
                        </a>
                    `;
                    if (e[i].status == 1){
                        if (e[i].cg_type == 1){
                            e[i].action += `
                                <a class="me-2 btn-action-icon p-2 btn_edit_gudang" data-id="${e[i].cg_id}" data-bs-target="#edit-category">
                                    <i class="fe fe-edit"></i>
                                </a>
                                <a class="p-2 btn-action-icon btn_delete_gudang" data-id="${e[i].cg_id}" href="javascript:void(0);">
                                    <i class="fe fe-trash-2"></i>
                                </a>
                            `;
                        } else if (e[i].cg_type == 2){
                            e[i].action += `
                                <a class="me-2 btn-action-icon p-2 btn_acc bg-success text-light" data-bs-toggle="tooltip"
                                data-bs-placement="bottom" title="Terima"  cash_id = "${e[i].cash_id}" >
                                    <i class="fe fe-check"></i>
                                </a>
                                <a  class="me-2 btn-action-icon p-2 btn_decline bg-danger text-light" data-bs-toggle="tooltip"
                                data-bs-placement="bottom" title="Tolak"  cash_id = "${e[i].cash_id}" >
                                    <i class="fe fe-x"></i>
                                </a>
                            `;
                        }
                    }
                }
                table.rows.add(e).draw();
                feather.replace(); // Biar icon feather muncul lagi

                $('.debits').html(`Rp ${formatRupiah(debits)}`);
                $('.credits').html(`(Rp ${formatRupiah(credits)})`);
                $('.sisa').html(`Rp ${formatRupiah(data['sisa_kas'])}`);
                sisa_kas = data['sisa_kas'];

                // Expand child row
                setTimeout(function () {
                    $('#tableCash tbody td.dt-control').each(function () {
                        $(this).trigger('click');
                    });
                }, 100);
            },
            error: function (err) {
                console.error("Gagal load kategori:", err);
            }
        });
    }

    function refreshCashArmada() {
        $.ajax({
            url: "/getCashArmada",
            data: {
                dates: dates,
                customer_id: $('#filter_customer_id').val()
            },
            method: "get",
            success: function (data) {
                // if (!Array.isArray(e)) {
                //     e = e.original || [];
                // }
                table.clear().draw(); 

                // Manipulasi data sebelum masuk ke tabel
                let e = data['data'];
                console.log(e);
                let debits = 0;
                let credits = 0;
                let totalAll = 0;
                let totalSummary = 0;
                var sisa = 0;
                for (let i = 0; i < e.length; i++) {
                    totalAll = e[i].total_all;
                    e[i].date = moment(e[i].created_at).format('D MMM YYYY');
                    if (e[i].cr_type == 1){
                        e[i].debit = "Rp " + formatRupiahMinus(e[i].cr_nominal);
                        e[i].credit = "Rp 0";
                        if (e[i].status == 2) {
                            debits += e[i].cr_nominal;

                            if (e[i].cr_nominal < 0) sisa -= e[i].cr_nominal;
                            else sisa += e[i].cr_nominal;
                        }
                    }
                    else{
                        e[i].debit = "Rp 0";
                        e[i].credit = "(Rp " + formatRupiahMinus(e[i].cr_nominal) + ")";
                        if (e[i].status == 2) {
                            credits += e[i].cr_nominal;

                            if (e[i].cr_nominal < 0) sisa += e[i].cr_nominal;
                            else sisa -= e[i].cr_nominal;
                        }
                    }
                    e[i].debit_text =`<label class='text-success'>${e[i].debit}</label>`
                    e[i].credit_text =`<label class='text-danger'>${e[i].credit}</label>`

                    if (e[i].status == 1){
                        e[i].status_text = `<span class="badge bg-warning" style="font-size: 12px">Sedang Diajukan</span>`;
                    } else if (e[i].status == 2){
                        e[i].status_text = `<span class="badge bg-success" style="font-size: 12px">Diterima</span>`;
                    } else if (e[i].status == 3){
                        e[i].status_text = `<span class="badge bg-danger" style="font-size: 12px">Ditolak</span>`;
                    }
                    e[i].action = `
                        <a class="me-2 btn-action-icon p-2 btn_view_armada" data-id="${e[i].cr_id}" data-bs-target="#view-cash">
                            <i class="fe fe-eye"></i>
                        </a>
                    `;
                    if (e[i].status == 2 && e[i].cr_type == 1) e[i].action = "";
                    if (e[i].status == 1){
                        // if (e[i].cr_aksi == 1){
                        //     e[i].action += `
                        //         <a class="me-2 btn-action-icon p-2 btn_edit_armada" data-id="${e[i].cs_id}" data-bs-target="#edit-category">
                        //             <i class="fe fe-edit"></i>
                        //         </a>
                        //         <a class="p-2 btn-action-icon btn_delete_armada" data-id="${e[i].cs_id}" href="javascript:void(0);">
                        //             <i class="fe fe-trash-2"></i>
                        //         </a>
                        //     `;
                        // }
                        if (e[i].cr_aksi == 2){
                            e[i].action += `
                                <a class="me-2 btn-action-icon p-2 btn_acc bg-success text-light" data-bs-toggle="tooltip"
                                data-bs-placement="bottom" title="Terima"  cash_id = "${e[i].cash_id}" >
                                    <i class="fe fe-check"></i>
                                </a>
                                <a  class="me-2 btn-action-icon p-2 btn_decline bg-danger text-light" data-bs-toggle="tooltip"
                                data-bs-placement="bottom" title="Tolak"  cash_id = "${e[i].cash_id}" >
                                    <i class="fe fe-x"></i>
                                </a>
                            `;
                        }
                    }
                }
                table.rows.add(e).draw();
                
                $('.debits').html(`Rp ${formatRupiahMinus(debits)}`);
                $('.credits').html(`(Rp ${formatRupiahMinus(credits)})`);
                // $('#totalSeluruh').html(`Rp ${formatRupiahMinus(totalAll)}`);
                if ($('#filter_customer_id').val() != null) $('#totalArmada').html(`Rp ${formatRupiahMinus(data['customer_saldo'])}`);
                else $('#totalArmada').html('-');
                console.log(sisa);
                $('.sisa').html(`Rp ${formatRupiahMinus(sisa)}`);
                // $('.sisa').html(`Rp ${formatRupiahMinus(data['sisa_kas'])}`);

                // Expand child row
                setTimeout(function () {
                    $('#tableCash tbody td.dt-control').each(function () {
                        $(this).trigger('click');
                    });
                }, 100);

                feather.replace(); // Biar icon feather muncul lagi
            },
            error: function (err) {
                console.error("Gagal load kategori:", err);
            }
        });
    }

    function refreshCashSales() {
        $.ajax({
            url: "/getCashSales",
            data: {
                dates: dates,
                staff_id: $('#filter_sales_id').val()
            },
            method: "get",
            success: function (data) {
                // if (!Array.isArray(e)) {
                //     e = e.original || [];
                // }
                table.clear().draw(); 

                // Manipulasi data sebelum masuk ke tabel
                let e = data['data'];
                console.log(e);
                let debits = 0;
                let credits = 0;
                let totalAll = 0;
                let totalSummary = 0;
                var sisa = 0;
                for (let i = 0; i < e.length; i++) {
                    totalAll = e[i].total_all;
                    e[i].date = moment(e[i].created_at).format('D MMM YYYY');
                    if (e[i].cs_transaction == 1 && e[i].cs_aksi == 1){
                        e[i].debit = "Rp " + formatRupiahMinus(e[i].cs_nominal);
                        e[i].credit = "Rp 0";
                        if (e[i].status == 2) {
                            debits += e[i].cs_nominal;
                            if (e[i].cs_nominal < 0) sisa -= e[i].cs_nominal;
                            else sisa += e[i].cs_nominal;
                        }
                    }
                    else{
                        e[i].debit = "Rp 0";
                        e[i].credit = "(Rp " + formatRupiahMinus(e[i].cs_nominal) + ")";
                        if (e[i].status == 2) {
                            credits += e[i].cs_nominal;

                            if (e[i].cs_nominal < 0) sisa += e[i].cs_nominal;
                            else sisa -= e[i].cs_nominal;
                        }
                    }
                    e[i].debit_text =`<label class='text-success'>${e[i].debit}</label>`
                    e[i].credit_text =`<label class='text-danger'>${e[i].credit}</label>`

                    if (e[i].status == 1){
                        e[i].status_text = `<span class="badge bg-warning" style="font-size: 12px">Sedang Diajukan</span>`;
                    } else if (e[i].status == 2){
                        e[i].status_text = `<span class="badge bg-success" style="font-size: 12px">Diterima</span>`;
                    } else if (e[i].status == 3){
                        e[i].status_text = `<span class="badge bg-danger" style="font-size: 12px">Ditolak</span>`;
                    }
                    e[i].action = `
                        <a class="me-2 btn-action-icon p-2 btn_view_sales" data-id="${e[i].cs_id}" data-bs-target="#view-cash">
                            <i class="fe fe-eye"></i>
                        </a>
                    `;
                    if (e[i].status == 1){
                        if (e[i].cs_aksi == 1){
                            e[i].action += `
                                <a class="me-2 btn-action-icon p-2 btn_acc bg-success text-light" data-bs-toggle="tooltip"
                                data-bs-placement="bottom" title="Terima"  cash_id = "${e[i].cash_id}" >
                                    <i class="fe fe-check"></i>
                                </a>
                                <a  class="me-2 btn-action-icon p-2 btn_decline bg-danger text-light" data-bs-toggle="tooltip"
                                data-bs-placement="bottom" title="Tolak"  cash_id = "${e[i].cash_id}" >
                                    <i class="fe fe-x"></i>
                                </a>
                            `;
                        }
                    }
                }
                table.rows.add(e).draw();
                
                $('.debits').html(`Rp ${formatRupiahMinus(debits)}`);
                $('.credits').html(`(Rp ${formatRupiahMinus(credits)})`);
                // $('#totalSeluruh').html(`Rp ${formatRupiahMinus(totalAll)}`);
                if ($('#filter_sales_id').val() != null) $('#totalSales').html(`Rp ${formatRupiahMinus(data['staff_saldo'])}`);
                else $('#totalSales').html('-');
                $('.sisa').html(`Rp ${formatRupiahMinus(sisa)}`);
                // $('.sisa').html(`Rp ${formatRupiahMinus(data['sisa_kas'])}`);

                // Expand child row
                setTimeout(function () {
                    $('#tableCash tbody td.dt-control').each(function () {
                        $(this).trigger('click');
                    });
                }, 100);

                feather.replace(); // Biar icon feather muncul lagi
            },
            error: function (err) {
                console.error("Gagal load kategori:", err);
            }
        });
    }

    function format(detailData) {
        if (!detailData || detailData.length === 0) {
            return `
                <div class="p-3">
                    <em class="text-muted">Tidak ada detail</em>
                </div>
            `;
        }

        let total = 0;

        let html = `<div class="px-5">`;
        detailData.forEach((d) => {
            total += parseInt(d.cad_nominal);

            html += `
                <div class="child-item">
                    <div class="child-left d-flex g-3">
                        <div class="date me-3">
                            ${moment(d.created_at).format('D MMM YYYY')}
                        </div>
                        <div class="notes">
                            ${d.cad_notes}
                        </div>
                    </div>
                    <div class="child-right text-end">
                        Rp ${formatRupiah(d.cad_nominal)}
                    </div>
                </div>

            `;
        });

        html += `
            <div class="child-item fw-semibold pt-3 border-0">
                <div class="child-left-total">
                    Total
                </div>
                <div class="child-right text-end">
                    Rp ${formatRupiah(total)}
                </div>
            </div>
        `;

        html += `</div>`;
        return html;
    }

    function formatGudang(detailData) {
        if (!detailData || detailData.length === 0) {
            return `
                <div class="p-3">
                    <em class="text-muted">Tidak ada detail</em>
                </div>
            `;
        }

        let total = 0;

        let html = `<div class="px-5">`;
        detailData.forEach((d) => {
            total += parseInt(d.cgd_nominal);

            html += `
                <div class="child-item">
                    <div class="child-left d-flex g-3">
                        <div class="date me-3">
                            ${moment(d.created_at).format('D MMM YYYY')}
                        </div>
                        <div class="notes">
                            ${d.customer_notes}
                        </div>
                    </div>
                    <div class="child-right text-end">
                        Rp ${formatRupiah(d.cgd_nominal)}
                    </div>
                </div>

            `;
        });

        html += `
            <div class="child-item fw-semibold pt-3 border-0">
                <div class="child-left-total">
                    Total
                </div>
                <div class="child-right text-end">
                    Rp ${formatRupiah(total)}
                </div>
            </div>
        `;

        html += `</div>`;
        return html;
    }
    function formatArmada(detailData) {
        if (!detailData || detailData.length === 0) {
            return `
                <div class="p-3">
                    <em class="text-muted">Tidak ada detail</em>
                </div>
            `;
        }

        let total = 0;

        let html = `<div class="px-5">`;
        detailData.forEach((d) => {
            total += parseInt(d.crd_nominal);

            html += `
                <div class="child-item">
                    <div class="child-left d-flex g-3">
                        <div class="date me-3">
                            ${moment(d.created_at).format('D MMM YYYY')}
                        </div>
                        <div class="notes">
                            ${d.crd_notes}
                        </div>
                    </div>
                    <div class="child-right text-end">
                        Rp ${formatRupiahMinus(d.crd_nominal)}
                    </div>
                </div>

            `;
        });

        html += `
            <div class="child-item fw-semibold pt-3 border-0">
                <div class="child-left-total">
                    Total
                </div>
                <div class="child-right text-end">
                    Rp ${formatRupiahMinus(total)}
                </div>
            </div>
        `;

        html += `</div>`;
        return html;
    }
    function formatSales(detailData) {
        if (!detailData || detailData.length === 0) {
            return `
                <div class="p-3">
                    <em class="text-muted">Tidak ada detail</em>
                </div>
            `;
        }

        let total = 0;

        let html = `<div class="px-5">`;
        detailData.forEach((d) => {
            total += parseInt(d.csd_nominal);

            html += `
                <div class="child-item">
                    <div class="child-left d-flex g-3">
                        <div class="date me-3">
                            ${moment(d.created_at).format('D MMM YYYY')}
                        </div>
                        <div class="notes">
                            ${d.csd_notes}
                        </div>
                    </div>
                    <div class="child-right text-end">
                        Rp ${formatRupiahMinus(d.csd_nominal)}
                    </div>
                </div>

            `;
        });

        html += `
            <div class="child-item fw-semibold pt-3 border-0">
                <div class="child-left-total">
                    Total
                </div>
                <div class="child-right text-end">
                    Rp ${formatRupiahMinus(total)}
                </div>
            </div>
        `;

        html += `</div>`;
        return html;
    }

    $('#tableCash tbody').on('click', 'td.dt-control', function () {
        let tr = $(this).closest('tr');
        let row = table.row(tr);

        if (row.child.isShown()) {
            row.child.hide();
            tr.removeClass('shown');
        } else {
            if (type == "admin") row.child(format(row.data().detail)).show();
            else if (type == "gudang") row.child(formatGudang(row.data().detail)).show();
            else if (type == "armada") row.child(formatArmada(row.data().detail)).show();
            else if (type == "sales") row.child(formatSales(row.data().detail)).show();
            tr.addClass('shown');
        }
    });

    // -------------- ROW DETAIL ADMIN --------------
    $(document).on('click', '.btn-add-catatan', function(){
        $('.is-invalid').removeClass('is-invalid');
        $('.is-invalids').removeClass('is-invalids');
        var valid=1;
        $("#add_cash_admin .fill_catatan").each(function(){
            if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                valid=-1;
                $(this).addClass('is-invalid');
            }
        });

        if(valid==-1){
            notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
            ResetLoadingButton('.btn-save-admin', mode == 1?"Tambah Aktivitas" : "Update Aktivitas"); 
            return false;
        };

        var data  = {
            "cad_notes": $('#cad_notes').val(),
            "cad_nominal": convertToAngka($('#cad_nominal').val()),
        };
        items.push(data);

        var total = 0;
        items.forEach(element => {
            total += element.cad_nominal;
        });
        $('.total').html(`Rp ${formatRupiah(total)}`)

        addRow();

        $('#cad_notes').val("");
        $('#cad_nominal').val("");
    })

    function addRow() {
        $('#tableDetail tr.row-detail').html(" ");
        items.forEach((e, index) => {
            $('#tableDetail tbody').append(`
                <tr class="row-detail" data-id="${index}">
                    <td>${index+1}</td>
                    <td style="width: 25%">${e.cad_notes}</td>
                    <td class="text-end">Rp ${formatRupiah(e.cad_nominal)}</td>
                    <td class="text-center d-flex align-items-center">
                        <a class="p-2 btn-action-icon btn_delete_row mx-auto"  href="javascript:void(0);">
                                <i class="fe fe-trash-2"></i>
                        </a>
                    </td>
                </tr>    
            `);
        }); 
    }

    $(document).on("click", ".btn_delete_row", function() {
        let row = $(this).closest("tr");
        let index = row.data("id");

        items.splice(index, 1);

        var total = 0;
        items.forEach(element => {
            total += element.cad_nominal;
        });
        $('.total').html(`Rp ${formatRupiah(total)}`)

        addRow();
    });

    $(document).on("click",".btn-save-admin",function(){
        LoadingButton(this);
        $('.is-invalid').removeClass('is-invalid');
        $('.is-invalids').removeClass('is-invalids');

        var url = "";
        var valid=1;
        var jenis_input = $('#jenis_input').val();

        $("#add_cash_admin .fill").each(function(){
            if (jenis_input == "saldo"){
                if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                    if ($(this).hasClass('saldos')){
                        valid=-1;
                        $(this).addClass('is-invalid');
                    }
                }
            }
            else if (jenis_input == "operasional"){
                if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                    if ($(this).hasClass('operasional')){
                        valid=-1;
                        $(this).addClass('is-invalid');
                    }
                }
            } 
            else {
                if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                    valid=-1;
                    $(this).addClass('is-invalid');
                }
            }
        });

        if(valid==-1){
            notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
            ResetLoadingButton('.btn-save-admin', mode == 1?"Tambah Aktivitas" : "Update Aktivitas");
            return false;
        };

        if (($('#bukti').val() == ""|| $('#bukti').val() == null || $('#bukti').val() == "null") && jenis_input == "operasional"){
            notifikasi('error', "Gagal Insert", 'Harus ada 1 bukti foto');
            ResetLoadingButton('.btn-save-admin', mode == 1?"Tambah Aktivitas" : "Update Aktivitas");
            return false;
        }

        if ($("#tableDetail tbody tr").length == 0 && jenis_input == "operasional") {
            notifikasi('error', "Gagal Insert", 'Minimal input 1 aktivitas');
            ResetLoadingButton('.btn-save-admin', mode == 1?"Tambah Aktivitas" : "Update Aktivitas"); 
            return false;
        }

        let total = 0;
        items.forEach(element => {
            total += element.cad_nominal;
        });
        if (total > sisa_kas){
            notifikasi('error', "Gagal Insert", 'Sisa kas tidak mencukupi');
            ResetLoadingButton('.btn-save-admin', mode == 1?"Tambah Aktivitas" : "Update Aktivitas"); 
            return false;
        }

        if (type == "admin"){
            param = {
                staff_id:$('#staff_id').val(),
                ca_notes: $('#oc_notes').val(),
                ca_nominal: convertToAngka($('#oc_nominal').val()),
                oc_transaksi: $('#oc_transaksi').val(),
                ca_type: jenis_input == "saldo" ? 1 : 2,
                jenis_input: jenis_input,
                photo:$('#bukti').val(),
                items: JSON.stringify(items),
                _token:token
            };

            if (mode == 1) url = "/insertCashAdmin";
            else if (mode == 2) {
                url = "/updateCashAdmin";
                param.ca_id = $('#add_cash_admin').attr("ca_id");
            }
        }
        else if (type == "gudang"){
            param = {
                staff_id:$('#staff_id').val(),
                cg_notes:$('#oc_notes').val(),
                cg_nominal:convertToAngka($('#oc_nominal').val()),
                _token:token
            };
            if (mode == 1) url = "/insertCashGudang";
            else if (mode == 2) {
                url = "/updateCashGudang";
                param.cg_id = $('#add_cash_admin').attr("cg_id");
            }
        }
        else if (type == "armada"){
            url ="/insertCashAdmin";
            param = {
                staff_id:$('#staff_id').val(),
                ca_notes:$('#oc_notes').val(),
                ca_nominal:convertToAngka($('#oc_nominal').val()),
                _token:token
            };
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
                ResetLoadingButton(".btn-save-admin", mode == 1?"Tambah Aktivitas" : "Update Aktivitas");      
                afterInsert();
            },
            error:function(e){
                ResetLoadingButton(".btn-save-admin", mode == 1?"Tambah Aktivitas" : "Update Aktivitas");
                console.log(e);
            }
        });
    });

    // -------------- ROW DETAIL GUDANG --------------
    $(document).on('click', '.btn-add-gudang', function(){
        $('.is-invalid').removeClass('is-invalid');
        $('.is-invalids').removeClass('is-invalids');
        var valid=1;
        $("#add_cash_gudang .fill_catatan").each(function(){
            if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                valid=-1;
                $(this).addClass('is-invalid');
            }
        });

        if($('#customer_id').val()==null||$('#customer_id').val()=="null"||$('#customer_id').val()==""){
            valid=-1;
            $('#row-gudang .select2-selection--single').addClass('is-invalids');
        }

        if(valid==-1){
            notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
            ResetLoadingButton('.btn-save-gudang', mode == 1?"Tambah Aktivitas" : "Update Aktivitas"); 
            return false;
        };

        var temp = $('#customer_id').select2("data")[0];

        var data  = {
            "customer_id": $('#customer_id').val(),
            "customer_notes": temp.customer_notes,
            "cgd_notes": $('#cgd_notes').val(),
            "cgd_nominal": convertToAngka($('#cgd_nominal').val()),
        };
        items.push(data);

        var total = 0;
        items.forEach(element => {
            total += element.cgd_nominal;
        });
        $('.total_gudang').html(`Rp ${formatRupiah(total)}`)

        addRowGudang();

        $('#customer_id').empty(null);
        $('#cgd_notes').val("");
        $('#cgd_nominal').val("").attr('disabled', true);
        $('.input_nominal').hide();
        $('#jenis_nominal').val("").trigger('change');
    })

    function addRowGudang() {
        $('#tableDetailGudang tr.row-detail').html(" ");
        console.log(items);
        items.forEach((e, index) => {
            $('#tableDetailGudang tbody').append(`
                <tr class="row-detail" data-id="${index}">
                    <td>${index+1}</td>
                    <td>${e.customer_notes}</td>
                    <td style="width: 25%">${e.cgd_notes}</td>
                    <td class="text-end">Rp ${formatRupiah(e.cgd_nominal)}</td>
                    <td class="text-center d-flex align-items-center">
                        <a class="p-2 btn-action-icon btn_delete_row_gudang mx-auto"  href="javascript:void(0);">
                                <i class="fe fe-trash-2"></i>
                        </a>
                    </td>
                </tr>    
            `);
        }); 
    }

    $(document).on("click", ".btn_delete_row_gudang", function() {
        let row = $(this).closest("tr");
        let index = row.data("id");

        items.splice(index, 1);

        var total = 0;
        items.forEach(element => {
            total += element.cgd_nominal;
        });
        console.log(total);
        $('.total_gudang').html(`Rp ${formatRupiah(total)}`)

        addRowGudang();
    });

    $(document).on("click",".btn-save-gudang",function(){
        LoadingButton(this);
        $('.is-invalid').removeClass('is-invalid');
        $('.is-invalids').removeClass('is-invalids');

        var url = "";
        var valid=1;
        var jenis_input = $('#jenis_input_gudang').val();

        $("#add_cash_gudang .fill").each(function(){
            if (jenis_input == "saldo"){
                if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                    if ($(this).hasClass("saldos")){
                        valid=-1;
                        $(this).addClass('is-invalid');
                    }
                }
            }
            else if (jenis_input == "operasional"){
                if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                    if ($(this).hasClass('operasional')){
                        valid=-1;
                        $(this).addClass('is-invalid');
                    }
                }
            } 
            else {
                if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                    valid=-1;
                    $(this).addClass('is-invalid');
                }
            }
        });

        if($('#staff_id_gudang').val()==null||$('#staff_id_gudang').val()=="null"||$('#staff_id_gudang').val()==""){
            valid=-1;
            $('#row-cash .select2-selection--single').addClass('is-invalids');
        }

        if(valid==-1){
            notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
            ResetLoadingButton('.btn-save-gudang', mode == 1?"Tambah Aktivitas" : "Update Aktivitas");
            return false;
        };

        if (($('#bukti_gudang').val() == ""|| $('#bukti_gudang').val() == null || $('#bukti_gudang').val() == "null") && jenis_input == "operasional"){
            notifikasi('error', "Gagal Insert", 'Harus ada 1 bukti foto');
            ResetLoadingButton('.btn-save-gudang', mode == 1?"Tambah Aktivitas" : "Update Aktivitas");
            return false;
        }

        if ($("#tableDetailGudang tbody tr").length == 0 && jenis_input == "operasional") {
            notifikasi('error', "Gagal Insert", 'Minimal input 1 armada');
            ResetLoadingButton('.btn-save-gudang', mode == 1?"Tambah Aktivitas" : "Update Aktivitas"); 
            return false;
        }

        let total = 0;
        items.forEach(element => {
            total += element.cgd_nominal;
        });
        if (total > sisa_kas){
            notifikasi('error', "Gagal Insert", 'Sisa kas tidak mencukupi');
            ResetLoadingButton('.btn-save-gudang', mode == 1?"Tambah Aktivitas" : "Update Aktivitas"); 
            return false;
        }

        param = {
            staff_id:$('#staff_id_gudang').val(),
            cg_notes: $('#oc_notes_gudang').val(),
            cg_nominal: convertToAngka($('#oc_nominal_gudang').val()),
            oc_transaksi: $('#oc_transaksi_gudang').val(),
            cg_type: jenis_input == "saldo" ? 1 : 2,
            jenis_input: jenis_input,
            photo:$('#bukti_gudang').val(),
            items: JSON.stringify(items),
            _token:token
        };
        if (mode == 1) url = "/insertCashGudang";
        else if (mode == 2) {
            url = "/updateCashGudang";
            param.cg_id = $('#add_cash_gudang').attr("cg_id");
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
                ResetLoadingButton(".btn-save-gudang", mode == 1?"Tambah Aktivitas" : "Update Aktivitas");      
                afterInsert();
            },
            error:function(e){
                ResetLoadingButton(".btn-save-gudang", mode == 1?"Tambah Aktivitas" : "Update Aktivitas");
                console.log(e);
            }
        });
    });

    // -------------- ROW DETAIL ARMADA --------------
    $(document).on('click', '.btn-add-armada', function(){
        $('.is-invalid').removeClass('is-invalid');
        $('.is-invalids').removeClass('is-invalids');
        var valid=1;
        $("#add_cash_armada .fill_catatan").each(function(){
            if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                valid=-1;
                $(this).addClass('is-invalid');
                console.log(this);
            }
        });

        if(valid==-1){
            notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
            ResetLoadingButton('.btn-save-armada', mode == 1?"Tambah Aktivitas" : "Update Aktivitas"); 
            return false;
        };

        var temp = $('#cc_id').select2('data')[0];
        var newType = 0;
        if (temp.cc_type == "Masuk") newType = 1;
        else if (temp.cc_type == "Keluar") newType = 2;
        else if (temp.cc_type == "Keluar 1") newType = 3;
        var firstType = items.length > 0 ? items[0].crd_type : null;

        var data = {
            crd_type: newType,
            crd_notes: temp.cc_name,
            crd_nominal: convertToAngkaMinus($('#crd_nominal').val()),
        };

        if (items.length === 0) {
            items.push(data);
        } else {
            if (
                (newType == 1 && firstType == 1) ||
                (newType == 2 && firstType == 2) ||
                (newType == 3 && firstType == 3)
            ) {
                items.push(data);
            }
            else {
                $('#oc_transaksi_armada').addClass('is-invalid');
                notifikasi('error', "Gagal Insert", 'Tipe yang diinputkan wajib satu kategori');
                ResetLoadingButton('.btn-save-armada', mode == 1?"Tambah Aktivitas" : "Update Aktivitas"); 
                return false;
            }
        }

        var total = 0;
        items.forEach(element => {
            total += element.crd_nominal;
        });
        $('.total_armada').html(`Rp ${formatRupiahMinus(total)}`)

        addRowArmada();

        $('#customer_id').empty(null);
        $('#cc_id').empty(null);
        $('#crd_nominal').val("");
    })

    function addRowArmada() {
        $('#tableDetailArmada tr.row-detail').html(" ");
        console.log(items);
        items.forEach((e, index) => {
            let type = "";
            if (e.crd_type == 1) type = "Masuk"
            else if (e.crd_type == 2) type = "Keluar"
            else if (e.crd_type == 3) type = "Keluar 1"
            console.log(e.crd_nominal)
            console.log(formatRupiahMinus(e.crd_nominal))
            $('#tableDetailArmada tbody').append(`
                <tr class="row-detail" data-id="${index}">
                    <td>${index+1}</td>
                    <td>${type}</td>
                    <td style="width: 25%">${e.crd_notes}</td>
                    <td class="text-end">Rp ${formatRupiahMinus(e.crd_nominal)}</td>
                    <td class="text-center d-flex align-items-center">
                        <a class="p-2 btn-action-icon btn_delete_row_armada mx-auto"  href="javascript:void(0);">
                                <i class="fe fe-trash-2"></i>
                        </a>
                    </td>
                </tr>
            `);
        }); 
    }

    $(document).on("click", ".btn_delete_row_armada", function() {
        let row = $(this).closest("tr");
        let index = row.data("id");

        items.splice(index, 1);

        var total = 0;
        items.forEach(element => {
            total += element.crd_nominal;
        });
        console.log(total);
        $('.total_armada').html(`Rp ${formatRupiahMinus(total)}`)

        addRowArmada();
    });

    $(document).on("click",".btn-save-armada",function(){
        LoadingButton(this);
        $('.is-invalid').removeClass('is-invalid');
        $('.is-invalids').removeClass('is-invalids');
        var valid=1;

        var jenis_input = $('#jenis_input_armada').val();
        $("#add_cash_armada .fill").each(function(){
            if (jenis_input == "saldo"){
                if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                    if ($(this).hasClass("saldos")){
                        valid=-1;
                        $(this).addClass('is-invalid');
                    }
                }
            }
            else if (jenis_input == "operasional"){
                if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                    if ($(this).hasClass('operasional')){
                        valid=-1;
                        $(this).addClass('is-invalid');
                    }
                }
            } 
            else {
                if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                    valid=-1;
                    $(this).addClass('is-invalid');
                }
            }
        });

        if($('#customer_id_armada').val()==null||$('#customer_id_armada').val()=="null"||$('#customer_id_armada').val()==""){
            valid=-1;
            $('#row-cash .select2-selection--single').addClass('is-invalids');
        }

        if(valid==-1){
            notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
            ResetLoadingButton('.btn-save-armada', mode == 1?"Tambah Aktivitas" : "Update Aktivitas");
            return false;
        };

        if (($('#bukti_armada').val() == ""|| $('#bukti_armada').val() == null || $('#bukti_armada').val() == "null") && jenis_input == "operasional"){
            notifikasi('error', "Gagal Insert", 'Harus ada 1 bukti foto');
            ResetLoadingButton('.btn-save-armada', mode == 1?"Tambah Aktivitas" : "Update Aktivitas");
            return false;
        }

        if (($("#tableDetailArmada tbody tr").length == 0) && jenis_input == "operasional") {
            notifikasi('error', "Gagal Insert", 'Minimal input 1 armada');
            ResetLoadingButton('.btn-save-armada', mode == 1?"Tambah Aktivitas" : "Update Aktivitas"); 
            return false;
        }

        param = {
            customer_id:$('#customer_id_armada').val(),
            oc_transaksi: $('#jenis_input_armada').val(),
            cr_notes: $('#oc_notes_armada').val(),
            cr_nominal: convertToAngkaMinus($('#oc_nominal_armada').val()),
            items: JSON.stringify(items),
            _token:token
        };
        let url = "/insertCashArmada";
        if (mode == 2) {
            url = "/updateCashArmada";
            param.cr_id = $('#add_cash_armada').attr("cr_id");
            param.cash_id = $('#add_cash_armada').attr("cash_id");
        }
        else{
            param.photo = $('#bukti_armada').val();
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
                if (typeof e === "object"){
                    notifikasi('error', e.header, e.message);
                    ResetLoadingButton(".btn-save-armada", mode == 1?"Tambah Aktivitas" : "Update Aktivitas");   
                    return false;
                } else {
                    ResetLoadingButton(".btn-save-armada", mode == 1?"Tambah Aktivitas" : "Update Aktivitas");      
                    afterInsert();
                }
            },
            error:function(e){
                ResetLoadingButton(".btn-save-armada", mode == 1?"Tambah Aktivitas" : "Update Aktivitas");
                console.log(e);
            }
        });
    });

    // -------------- ROW DETAIL SALES --------------
    $(document).on('click', '.btn-add-sales', function(){
        $('.is-invalid').removeClass('is-invalid');
        $('.is-invalids').removeClass('is-invalids');
        var valid=1;
        $("#add_cash_sales .fill_catatan").each(function(){
            if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                valid=-1;
                $(this).addClass('is-invalid');
                console.log(this);
            }
        });

        if($('#cc_id_sales').val()==null||$('#cc_id_sales').val()=="null"||$('#cc_id_sales').val()==""){
            valid=-1;
            $('#row-product .select2-selection--single').addClass('is-invalids');
        }

        if(valid==-1){
            notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
            ResetLoadingButton('.btn-save-sales', mode == 1?"Tambah Aktivitas" : "Update Aktivitas"); 
            return false;
        };

        var temp = $('#cc_id_sales').select2('data')[0];
        var newType = 0;
        if (temp.cc_type == "Masuk") newType = 1;
        else if (temp.cc_type == "Keluar") newType = 2;
        else if (temp.cc_type == "Keluar 1") newType = 3;
        var firstType = items.length > 0 ? items[0].csd_type : null;

        var data = {
            csd_type: newType,
            csd_notes: temp.cc_name,
            csd_nominal: convertToAngkaMinus($('#csd_nominal').val()),
        };

        if (items.length === 0) {
            items.push(data);
        } else {
            if (
                (newType == 1 && firstType == 1) ||
                (newType == 2 && firstType == 2) ||
                (newType == 3 && firstType == 3)
            ) {
                items.push(data);
            }
            else {
                $('#oc_transaksi_sales').addClass('is-invalid');
                notifikasi('error', "Gagal Insert", 'Tipe yang diinputkan wajib satu kategori');
                ResetLoadingButton('.btn-save-sales', mode == 1?"Tambah Aktivitas" : "Update Aktivitas"); 
                return false;
            }
        }

        var total = 0;
        items.forEach(element => {
            total += element.csd_nominal;
        });
        $('.total_sales').html(`Rp ${formatRupiahMinus(total)}`)

        addRowSales();

        $('#customer_id').empty(null);
        $('#cc_id_sales').empty(null);
        $('#csd_nominal').val("");
    })

    function addRowSales() {
        $('#tableDetailSales tr.row-detail').html(" ");
        console.log(items);
        items.forEach((e, index) => {
            let type = "";
            if (e.csd_type == 1) type = "Masuk"
            else if (e.csd_type == 2) type = "Keluar"
            else if (e.csd_type == 3) type = "Keluar 1"
            $('#tableDetailSales tbody').append(`
                <tr class="row-detail" data-id="${index}">
                    <td>${index+1}</td>
                    <td>${type}</td>
                    <td style="width: 25%">${e.csd_notes}</td>
                    <td class="text-end">Rp ${formatRupiahMinus(e.csd_nominal)}</td>
                    <td class="text-center d-flex align-items-center">
                        <a class="p-2 btn-action-icon btn_delete_row_sales mx-auto"  href="javascript:void(0);">
                                <i class="fe fe-trash-2"></i>
                        </a>
                    </td>
                </tr>
            `);
        }); 
    }

    $(document).on("click", ".btn_delete_row_sales", function() {
        let row = $(this).closest("tr");
        let index = row.data("id");

        items.splice(index, 1);

        var total = 0;
        items.forEach(element => {
            total += element.csd_nominal;
        });
        console.log(total);
        $('.total_sales').html(`Rp ${formatRupiahMinus(total)}`)

        addRowSales();
    });

    $(document).on("click",".btn-save-sales",function(){
        LoadingButton(this);
        $('.is-invalid').removeClass('is-invalid');
        $('.is-invalids').removeClass('is-invalids');
        var valid=1;

        var jenis_input = $('#jenis_input_sales').val();
        $("#add_cash_sales .fill").each(function(){
            if (jenis_input == "saldo"){
                if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                    if ($(this).hasClass("saldos")){
                        valid=-1;
                        $(this).addClass('is-invalid');
                    }
                }
            }
            else if (jenis_input == "operasional"){
                if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                    if ($(this).hasClass('operasional')){
                        valid=-1;
                        $(this).addClass('is-invalid');
                    }
                }
            } 
            else {
                if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                    valid=-1;
                    $(this).addClass('is-invalid');
                }
            }
        });

        if($('#staff_id_sales').val()==null||$('#staff_id_sales').val()=="null"||$('#staff_id_sales').val()==""){
            valid=-1;
            $('#row-cash .select2-selection--single').addClass('is-invalids');
        }

        if(($('#bank_account').val()==null||$('#bank_account').val()=="null"||$('#bank_account').val()=="") && jenis_input == "saldo" && $('#aksi_sales').val() == 2){
            valid=-1;
            $('#row-bank .select2-selection--single').addClass('is-invalids');
        }

        if(valid==-1){
            notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
            ResetLoadingButton('.btn-save-sales', mode == 1?"Tambah Aktivitas" : "Update Aktivitas");
            return false;
        };

        // if ($('#aksi_sales').val() == 3 && convertToAngkaMinus($('#oc_nominal_sales').val()) < 0){
        //     notifikasi('error', "Gagal Insert", 'Pengembalian tidak boleh kurang dari 0');
        //     ResetLoadingButton('.btn-save-sales', mode == 1?"Tambah Aktivitas" : "Update Aktivitas");
        //     $('#oc_nominal_sales').addClass('is-invalid');
        //     return false;
        // }

        if (($('#bukti_sales').val() == ""|| $('#bukti_sales').val() == null || $('#bukti_sales').val() == "null") && jenis_input == "operasional"){
            notifikasi('error', "Gagal Insert", 'Harus ada 1 bukti foto');
            ResetLoadingButton('.btn-save-sales', mode == 1?"Tambah Aktivitas" : "Update Aktivitas");
            return false;
        }

        if (($("#tableDetailSales tbody tr").length == 0) && jenis_input == "operasional") {
            notifikasi('error', "Gagal Insert", 'Minimal input 1 pengeluaran');
            ResetLoadingButton('.btn-save-sales', mode == 1?"Tambah Aktivitas" : "Update Aktivitas"); 
            return false;
        }

        // if ($('#aksi_sales').val() != 1 && $('#jenis_input_sales').val() != "saldo"){
        //     let total = 0;
        //     items.forEach(element => {
        //         total += element.csd_nominal;
        //     });
        //     if (total > sisa_kas){
        //         notifikasi('error', "Gagal Insert", 'Sisa kas tidak mencukupi');
        //         ResetLoadingButton('.btn-save-sales', mode == 1?"Tambah Aktivitas" : "Update Aktivitas"); 
        //         return false;
        //     }
        // }

        param = {
            staff_id:$('#staff_id_sales').val(),
            oc_transaksi: $('#jenis_input_sales').val(),
            cs_aksi: $('#aksi_sales').val(),
            cs_notes: $('#oc_notes_sales').val(),
            cs_nominal: convertToAngkaMinus($('#oc_nominal_sales').val()),
            bank_id: ($('#aksi_sales').val() == 2 ? $('#bank_account').val() : 0),
            items: JSON.stringify(items),
            _token:token
        };
        let url = "/insertCashSales";
        if (mode == 2) {
            url = "/updateCashSales";
            param.cs_id = $('#add_cash_sales').attr("cs_id");
            param.cash_id = $('#add_cash_sales').attr("cash_id");
        }
        else{
            param.photo = $('#bukti_sales').val();
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
                if (typeof e === "object"){
                    notifikasi('error', e.header, e.message);
                    ResetLoadingButton(".btn-save-sales", mode == 1?"Tambah Aktivitas" : "Update Aktivitas");   
                    return false;
                } else {
                    ResetLoadingButton(".btn-save-sales", mode == 1?"Tambah Aktivitas" : "Update Aktivitas");      
                    afterInsert();
                }
            },
            error:function(e){
                ResetLoadingButton(".btn-save-sales", mode == 1?"Tambah Aktivitas" : "Update Aktivitas");
                console.log(e);
            }
        });
    });

    function afterInsert() {
        $(".modal").modal("hide");
        if(mode==1)notifikasi('success', "Berhasil Insert", "Berhasil Melakukan Pengajuan");
        else if(mode==2)notifikasi('success', "Berhasil Update", "Berhasil Update Pengajuan");
        if (type=="admin") refreshCashAdmin();
        else if (type=="gudang") refreshCashGudang();
        else if (type=="armada") refreshCashArmada();
        else if (type=="sales") refreshCashSales();
    }


    // ------------- Edit, View, Delete ADMIN -------------
    $(document).on('click', '.btn_edit_admin', function(){
        var data = $('#tableCash').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        mode=2;
        items = [];
        console.log(data);
        $('#add_cash_admin .modal-title').html("Update Aktivitas Admin");
        $('#add_cash_admin input').empty().val("");
        $('#staff_id').empty(null);
        
        if (data.detail?.length) {
            $('#jenis_input').val("operasional").trigger('change').attr('disabled', true);

            let total = 0;
            data.detail.forEach(e => {
                var temp = {
                    "cad_id" : e.cad_id,
                    "cad_notes" : e.cad_notes,
                    "cad_nominal" : e.cad_nominal,
                };
                items.push(temp);
                total += e.cad_nominal;
            })
            $('.total').html(`Rp ${formatRupiah(total)}`)
            addRow();

            $('#btn-foto-bukti').hide();
            $('#btn-lihat-bukti').show();
            $('#bukti').val(data.ca_img);
            $('#check_foto').show();
            imageValue(data.ca_img);
        }
        else {
            $('#jenis_input').val("saldo").trigger('change').attr('disabled', true);
            $('#oc_transaksi').val(data.ca_aksi).attr('disabled', true);
            $('#oc_nominal').val(data.ca_nominal).attr('disabled', false);
            $('#oc_notes').val(data.ca_notes).attr('disabled', false);
        }
        $('#staff_id').append(`<option value="${data.staff_id}">${data.staff_name}</option>`).attr('disabled', true);

        $('.is-invalid').removeClass('is-invalid');
        $('.is-invalids').removeClass('is-invalids');
        $('.btn-save-admin').html('Update Aktivitas').show();
        $('.cancel-btn').html('Batal');
        $('.input_table, .btn_delete_row').show();
        $('#add_cash_admin').modal("show");
        $('#add_cash_admin').attr("ca_id", data.ca_id);
    })

    $(document).on('click', '.btn_view_admin', function(){
        var data = $('#tableCash').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        mode=3;
        items = [];
        console.log(data);
        $('#add_cash_admin .modal-title').html("Lihat Aktivitas Admin");
        $('#add_cash_admin input').empty().val("");
        $('#staff_id').empty(null);
        
        if (data.detail?.length) {
            $('#jenis_input').val("operasional").trigger('change').attr('disabled', true);

            let total = 0;
            data.detail.forEach(e => {
                var temp = {
                    "cad_id" : e.cad_id,
                    "cad_notes" : e.cad_notes,
                    "cad_nominal" : e.cad_nominal,
                };
                items.push(temp);
                total += e.cad_nominal;
            })
            $('.total').html(`Rp ${formatRupiah(total)}`)
            addRow();

            $('#btn-foto-bukti').hide();
            $('#btn-lihat-bukti').show();
            $('#bukti').val(data.ca_img);
            $('#check_foto').show();
            imageValue(data.ca_img);
        }
        else {
            $('#jenis_input').val("saldo").trigger('change').attr('disabled', true);
            $('#oc_transaksi').val(data.ca_aksi).attr('disabled', true);
            $('#oc_nominal').val(data.ca_nominal).attr('disabled', true);
            $('#oc_notes').val(data.ca_notes).attr('disabled', true);
        }
        $('#staff_id').append(`<option value="${data.staff_id}">${data.staff_name}</option>`).attr('disabled', true);

        $('.is-invalid').removeClass('is-invalid');
        $('.is-invalids').removeClass('is-invalids');
        $('.input_table, .btn-save-admin, .btn_delete_row').hide();
        $('.cancel-btn').html('Kembali');
        $('#add_cash_admin').modal("show");
        $('#add_cash_admin').attr("ca_id", data.ca_id);
    })

    $(document).on('click', '.btn_delete_admin', function(){
        var data = $('#tableCash').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        showModalDelete("Apakah yakin ingin menghapus pengajuan ini?","btn-delete-admin");
        $('#btn-delete-admin').attr("ca_id", data.ca_id);
    })

    $(document).on("click","#btn-delete-admin",function(){
        LoadingButton(this);
        $.ajax({
            url:"/deleteCashAdmin",
            data:{
                ca_id:$('#btn-delete-admin').attr('ca_id'),
                _token:token
            },
            method:"post",
            success:function(e){
                ResetLoadingButton('#btn-delete-admin', "Delete");
                $('.modal').modal("hide");
                refreshCashAdmin();
                notifikasi('success', "Berhasil Delete", "Berhasil delete pengajuan");
            },
            error:function(e){
                ResetLoadingButton('#btn-delete-admin', "Delete");
                console.log(e);
            }
        });
    });

    // ------------- Edit, View, Delete GUDANG -------------
    $(document).on('click', '.btn_edit_gudang', function(){
        var data = $('#tableCash').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        mode=2;
        items = [];
        console.log(data);
        $('#add_cash_gudang .modal-title').html("Update Aktivitas Gudang");
        $('#add_cash_gudang input').empty().val("");
        $('#staff_id_gudang').empty(null);
        
        if (data.detail?.length) {
            $('#jenis_input').val("operasional").trigger('change').attr('disabled', true);

            let total = 0;
            data.detail.forEach(e => {
                var temp = {
                    "customer_id" : e.customer_id,
                    "customer_notes" : e.customer_notes,
                    "cgd_id" : e.cgd_id,
                    "cgd_notes" : e.cgd_notes,
                    "cgd_nominal" : e.cgd_nominal,
                };
                items.push(temp);
                total += e.cgd_nominal;
            })
            $('.total_gudang').html(`Rp ${formatRupiah(total)}`)
            addRowGudang();

            $('#btn-foto-bukti-gudang').hide();
            $('#btn-lihat-bukti-gudang').show();
            $('#bukti_gudang').val(data.cg_img);
            $('#check_foto_gudang').show();
            $('#jenis_nominal').val("").trigger('change');
            imageValue(data.cg_img);
        }
        else {
            $('#jenis_input_gudang').val("saldo").trigger('change').attr('disabled', true);
            $('#oc_transaksi_gudang').val(data.cg_aksi).attr('disabled', true);
            $('#oc_nominal_gudang').val(data.cg_nominal).attr('disabled', false);
            $('#oc_notes_gudang').val(data.cg_notes).attr('disabled', false);
        }
        $('#staff_id_gudang').append(`<option value="${data.staff_id}">${data.staff_name}</option>`).attr('disabled', true);

        $('.is-invalid').removeClass('is-invalid');
        $('.is-invalids').removeClass('is-invalids');
        $('.btn-save-gudang').html('Update Aktivitas').show();
        $('.cancel-btn').html('Batal');
        $('.input_table, .btn_delete_row_gudang').show();
        $('#add_cash_gudang').modal("show");
        $('#add_cash_gudang').attr("cg_id", data.cg_id);
    })

    $(document).on('click', '.btn_view_gudang', function(){
        var data = $('#tableCash').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        mode=3;
        items = [];
        console.log(data);
        $('#add_cash_gudang .modal-title').html("Lihat Aktivitas Gudang");
        $('#add_cash_gudang input').empty().val("");
        $('#staff_id_gudang').empty(null);
        
        if (data.detail?.length) {
            $('#jenis_input_gudang').val("operasional").trigger('change').attr('disabled', true);

            let total = 0;
            data.detail.forEach(e => {
                var temp = {
                    "customer_id" : e.customer_id,
                    "customer_notes" : e.customer_notes,
                    "cgd_id" : e.cgd_id,
                    "cgd_notes" : e.cgd_notes,
                    "cgd_nominal" : e.cgd_nominal,
                };
                items.push(temp);
                total += e.cgd_nominal;
            })
            $('.total_gudang').html(`Rp ${formatRupiah(total)}`)
            addRowGudang();

            $('#btn-foto-bukti-gudang').hide();
            $('#btn-lihat-bukti-gudang').show();
            $('#bukti_gudang').val(data.cg_img);
            $('#check_foto_gudang').show();
            imageValue(data.cg_img);
        }
        else {
            $('#jenis_input_gudang').val("saldo").trigger('change').attr('disabled', true);
            $('#oc_transaksi_gudang').val(data.cg_aksi).attr('disabled', true);
            $('#oc_nominal_gudang').val(data.cg_nominal).attr('disabled', true);
            $('#oc_notes_gudang').val(data.cg_notes).attr('disabled', true);
        }
        $('#staff_id_gudang').append(`<option value="${data.staff_id}">${data.staff_name}</option>`).attr('disabled', true);

        $('.is-invalid').removeClass('is-invalid');
        $('.is-invalids').removeClass('is-invalids');
        $('.input_table, .btn-save-gudang, .btn_delete_row_gudang').hide();
        $('.cancel-btn').html('Kembali');
        $('#add_cash_gudang').modal("show");
        $('#add_cash_gudang').attr("cg_id", data.cg_id);
    })

    $(document).on('click', '.btn_delete_gudang', function(){
        var data = $('#tableCash').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        showModalDelete("Apakah yakin ingin menghapus pengajuan ini?","btn-delete-gudang");
        $('#btn-delete-gudang').attr("cg_id", data.cg_id);
    })

    $(document).on("click","#btn-delete-gudang",function(){
        LoadingButton(this);
        $.ajax({
            url:"/deleteCashGudang",
            data:{
                cg_id:$('#btn-delete-gudang').attr('cg_id'),
                _token:token
            },
            method:"post",
            success:function(e){
                ResetLoadingButton('#btn-delete-gudang', "Delete");
                $('.modal').modal("hide");
                refreshCashAdmin();
                notifikasi('success', "Berhasil Delete", "Berhasil delete pengajuan");
            },
            error:function(e){
                ResetLoadingButton('#btn-delete-gudang', "Delete");
                console.log(e);
            }
        });
    });

    // ------------- Edit, View, Delete ARMADA -------------
    $(document).on('click', '.btn_edit_armada', function(){
        var data = $('#tableCash').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        mode=2;
        items = [];
        console.log(data);
        $('#add_cash_armada .modal-title').html("Update Aktivitas Armada");
        $('#add_cash_armada input').empty().val("");
        $('#customer_id_armada').empty(null);
        
        if (data.detail?.length){
            $('#jenis_input_armada').val("operasional").trigger('change').attr('disabled', true);
            let total = 0;
            data.detail.forEach(e => {
                var temp = {
                    "crd_id" : e.crd_id,
                    "crd_notes" : e.crd_notes,
                    "crd_nominal" : e.crd_nominal,
                };
                items.push(temp);
                total += e.crd_nominal;
            })
            $('.total_armada').html(`Rp ${formatRupiahMinus(total)}`)
            addRowArmada();
            
            $('.foto').show();
            $('#btn-foto-bukti-armada').hide();
            $('#btn-lihat-bukti-armada').show();
            var img = JSON.parse(data.cr_img);
            list_photo = img || null;
            console.log(list_photo);
    
            $('#modalViewPhoto .modal-footer').show();
            $('#fotoProduksiImage').attr('src', public+"kas_admin/armada/"+img[0]);
            $('#fotoProduksiImage').attr('index', 0);
            $('#btn_download_photo').attr('href', public+"kas_admin/armada/"+img[0]);
            $('#check_foto_armada').show();
            $('#jumlahFoto').html(list_photo.length);
            $('#bukti_armada').val(data.cr_img);
            
        } else {
            $('.foto').hide();
            $('#jenis_input_armada').val("saldo").trigger('change').attr('disabled', true);
            $('#oc_transaksi_armada').val(data.cr_aksi).attr('disabled', true);
            $('#oc_notes_armada').val(data.cr_notes).attr('disabled', false);
            $('#oc_nominal_armada').val(formatRupiahMinus(data.cr_nominal)).attr('disabled', false);
        }

        $('#customer_id_armada').append(`<option value="${data.customer_id}">${data.customer_notes}</option>`).attr('disabled', true);

        $('.is-invalid').removeClass('is-invalid');
        $('.is-invalids').removeClass('is-invalids');
        $('.btn-save-armada').html('Update Aktivitas').show();
        $('.cancel-btn').html('Batal');
        $('.input_table, .btn_delete_row_armada').show();
        $('#add_cash_armada').modal("show");
        $('#add_cash_armada').attr("cr_id", data.cr_id);
        $('#add_cash_armada').attr("cash_id", data.cash_id);
    })

    $(document).on('click', '.btn_view_armada', function(){
        var data = $('#tableCash').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        mode=3;
        items = [];
        console.log(data);
        $('#add_cash_armada .modal-title').html("Lihat Aktivitas Armada");
        $('#add_cash_armada input').empty().val("");
        $('#customer_id_armada').empty(null);
        
        if (data.detail?.length){
            $('#jenis_input_armada').val("operasional").trigger('change').attr('disabled', true);
            let total = 0;
            data.detail.forEach(e => {
                var temp = {
                    "crd_id" : e.crd_id,
                    "crd_notes" : e.crd_notes,
                    "crd_nominal" : e.crd_nominal,
                };
                items.push(temp);
                total += e.crd_nominal;
            })
            $('.total_armada').html(`Rp ${formatRupiahMinus(total)}`)
            addRowArmada();
            
            $('.foto').show();
            $('#btn-foto-bukti-armada').hide();
            $('#btn-lihat-bukti-armada').show();
            var img = JSON.parse(data.cr_img);
            list_photo = img || null;
            console.log(list_photo);
    
            $('#modalViewPhoto .modal-footer').show();
            $('#fotoProduksiImage').attr('src', public+"kas_admin/armada/"+img[0]);
            $('#fotoProduksiImage').attr('index', 0);
            $('#btn_download_photo').attr('href', public+"kas_admin/armada/"+img[0]);
            $('#check_foto_armada').show();
            $('#jumlahFoto').html(list_photo.length);
            $('#bukti_armada').val(data.cr_img);
            
        } else {
            $('.foto').hide();
            $('#jenis_input_armada').val("saldo").trigger('change').attr('disabled', true);
            $('#oc_transaksi_armada').val(data.cr_aksi).attr('disabled', true);
            $('#oc_notes_armada').val(data.cr_notes).attr('disabled', true);
            var nominalVal = data.cr_nominal.toString();
            var optionAda = $('#jenis_nominal option[value="' + nominalVal + '"]').length > 0;
            if (optionAda) {
                $('#jenis_nominal').val(nominalVal).trigger('change').attr('disabled', true);
            } else {
                $('#jenis_nominal').val('manual').trigger('change').attr('disabled', true);
                $('#oc_nominal_armada').val(formatRupiahMinus(data.cr_nominal)).attr('disabled', true);
            }
        }

        $('#customer_id_armada').append(`<option value="${data.customer_id}">${data.customer_notes}</option>`).attr('disabled', true);

        $('.is-invalid').removeClass('is-invalid');
        $('.is-invalids').removeClass('is-invalids');
        $('.input_table, .btn-save-armada, .btn_delete_row_armada').hide();
        $('.cancel-btn').html('Kembali');
        $('#add_cash_armada').modal("show");
        $('#add_cash_armada').attr("cr_id", data.cr_id);
    })

    $(document).on('click', '.btn_delete_armada', function(){
        var data = $('#tableCash').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        showModalDelete("Apakah yakin ingin menghapus pengajuan ini?","btn-delete-armada");
        $('#btn-delete-armada').attr("cr_id", data.cr_id);
    })

    $(document).on("click","#btn-delete-armada",function(){
        LoadingButton(this);
        $.ajax({
            url:"/deleteCashArmada",
            data:{
                cr_id:$('#btn-delete-armada').attr('cr_id'),
                _token:token
            },
            method:"post",
            success:function(e){
                ResetLoadingButton('#btn-delete-armada', "Delete");
                $('.modal').modal("hide");
                refreshCashArmada();
                notifikasi('success', "Berhasil Delete", "Berhasil delete pengajuan");
            },
            error:function(e){
                ResetLoadingButton('#btn-delete-armada', "Delete");
                console.log(e);
            }
        });
    });

    // ------------- Edit, View, Delete SALES -------------
    $(document).on('click', '.btn_edit_sales', function(){
        var data = $('#tableCash').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        mode=2;
        items = [];
        console.log(data);
        $('#add_cash_sales .modal-title').html("Update Aktivitas Sales");
        $('#add_cash_sales input').empty().val("");
        $('#staff_id_sales').empty(null);
        
        if (data.detail?.length){
            $('#jenis_input_sales').val("operasional").trigger('change').attr('disabled', true);
            let total = 0;
            data.detail.forEach(e => {
                var temp = {
                    "csd_id" : e.csd_id,
                    "csd_notes" : e.csd_notes,
                    "csd_nominal" : e.csd_nominal,
                };
                items.push(temp);
                total += e.csd_nominal;
            })
            $('.total_sales').html(`Rp ${formatRupiahMinus(total)}`)
            addRowSales();
            
            $('.foto').show();
            $('#btn-foto-bukti-sales').hide();
            $('#btn-lihat-bukti-sales').show();
            var img = JSON.parse(data.cs_img);
            list_photo = img || null;
            console.log(list_photo);
    
            $('#modalViewPhoto .modal-footer').show();
            $('#fotoProduksiImage').attr('src', public+"kas_admin/sales/"+img[0]);
            $('#fotoProduksiImage').attr('index', 0);
            $('#btn_download_photo').attr('href', public+"kas_admin/sales/"+img[0]);
            $('#check_foto_sales').show();
            $('#jumlahFoto').html(list_photo.length);
            $('#bukti_sales').val(data.cs_img);
            
        } else {
            $('.foto').hide();
            $('#jenis_input_sales').val("saldo").trigger('change').attr('disabled', true);
            $('#oc_transaksi_sales').val(data.cs_aksi).attr('disabled', true);
            $('#oc_notes_sales').val(data.cs_notes).attr('disabled', false);
            $('#oc_nominal_sales').val(formatRupiahMinus(data.cs_nominal)).attr('disabled', false);
        }

        $('#staff_id_sales').append(`<option value="${data.staff_id}">${data.staff_name}</option>`).attr('disabled', true);

        $('.is-invalid').removeClass('is-invalid');
        $('.is-invalids').removeClass('is-invalids');
        $('.btn-save-sales').html('Update Aktivitas').show();
        $('.cancel-btn').html('Batal');
        $('.input_table, .btn_delete_row_sales').show();
        $('#add_cash_sales').modal("show");
        $('#add_cash_sales').attr("cs_id", data.cs_id);
        $('#add_cash_sales').attr("cash_id", data.cash_id);
    })

    $(document).on('click', '.btn_view_sales', function(){
        var data = $('#tableCash').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        mode=3;
        items = [];
        console.log(data);
        $('#add_cash_sales .modal-title').html("Lihat Aktivitas Sales");
        $('#add_cash_sales input').empty().val("");
        $('#staff_id_sales').empty(null);
        
        if (data.detail?.length){
            $('#jenis_input_sales').val("operasional").trigger('change').attr('disabled', true);
            let total = 0;
            data.detail.forEach(e => {
                var temp = {
                    "csd_id" : e.csd_id,
                    "csd_notes" : e.csd_notes,
                    "csd_nominal" : e.csd_nominal,
                    "csd_type" : e.csd_type
                };
                items.push(temp);
                total += e.csd_nominal;
            })
            $('.total_sales').html(`Rp ${formatRupiahMinus(total)}`)
            addRowSales();
            
            $('.foto').show();
            $('#btn-foto-bukti-sales').hide();
            $('#btn-lihat-bukti-sales').show();
            var img = JSON.parse(data.cs_img);
            list_photo = img || null;
            console.log(list_photo);
    
            $('#modalViewPhoto .modal-footer').show();
            $('#fotoProduksiImage').attr('src', public+"kas_admin/sales/"+img[0]);
            $('#fotoProduksiImage').attr('index', 0);
            $('#btn_download_photo').attr('href', public+"kas_admin/sales/"+img[0]);
            $('#check_foto_sales').show();
            $('#jumlahFoto').html(list_photo.length);
            $('#bukti_sales').val(data.cs_img);
            
        } else {
            $('.foto').hide();
            $('#jenis_input_sales').val("saldo").trigger('change').attr('disabled', true);
            $('#oc_transaksi_sales').val(data.cs_aksi).attr('disabled', true);
            $('#oc_notes_sales').val(data.cs_notes).attr('disabled', true);
            $('#oc_nominal_sales').val(formatRupiahMinus(data.cs_nominal)).attr('disabled', true);
            $('#aksi_sales').val(data.cs_aksi).attr('disabled', true);

            if (data.cs_aksi != 2) {
                $('.banks').hide();
            } else {
                $('.banks').show();
                $('#bank_account').append(`<option value="${data.bank_id}">${data.bank_kode}</option>`).attr('disabled', true);
            }
        }

        $('#staff_id_sales').append(`<option value="${data.staff_id}">${data.staff_name}</option>`).attr('disabled', true);

        $('.is-invalid').removeClass('is-invalid');
        $('.is-invalids').removeClass('is-invalids');
        $('.input_table, .btn-save-sales, .btn_delete_row_sales').hide();
        $('.cancel-btn').html('Kembali');
        $('#add_cash_sales').modal("show");
        $('#add_cash_sales').attr("cs_id", data.cs_id);
    })

    $(document).on('click', '.btn_delete_sales', function(){
        var data = $('#tableCash').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        showModalDelete("Apakah yakin ingin menghapus pengajuan ini?","btn-delete-sales");
        $('#btn-delete-sales').attr("cs_id", data.cs_id);
    })

    $(document).on("click","#btn-delete-sales",function(){
        LoadingButton(this);
        $.ajax({
            url:"/deleteCashSales",
            data:{
                cs_id:$('#btn-delete-sales').attr('cs_id'),
                _token:token
            },
            method:"post",
            success:function(e){
                ResetLoadingButton('#btn-delete-sales', "Delete");
                $('.modal').modal("hide");
                refreshCashSales();
                notifikasi('success', "Berhasil Delete", "Berhasil delete pengajuan");
            },
            error:function(e){
                ResetLoadingButton('#btn-delete-sales', "Delete");
                console.log(e);
            }
        });
    });

    // --------- ACC KAS OPERASIONAL ---------
    $(document).on('click', '.btn_acc', function(){
        var data = $('#tableCash').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        showModalKonfirmasi(
            "Apakah yakin ingin Approve pengajuan ini?",
            "btn-accept-kas"
        );
        $('#btn-accept-kas').attr("cash_id", data.cash_id);
        if (type == "admin") $('#btn-accept-kas').attr("ca_id", data.ca_id);
        else if (type == "gudang") $('#btn-accept-kas').attr("cg_id", data.cg_id);
        else if (type == "armada") $('#btn-accept-kas').attr("cr_id", data.cr_id);
        else if (type == "sales") $('#btn-accept-kas').attr("cs_id", data.cs_id);
        $('#btn-accept-kas').html("Konfirmasi");
    })

    $(document).on('click', '#btn-accept-kas', function(){
        LoadingButton(this);
        let url = "";
        if (type == "admin") {
            url = "/acceptCashAdmin";
            param = {
                cash_id:$('#btn-accept-kas').attr('cash_id'),
                ca_id:$('#btn-accept-kas').attr('ca_id'),
                _token:token
            };
        }
        else if (type == "gudang") {
            url = "/acceptCashGudang";
            param = {
                cash_id:$('#btn-accept-kas').attr('cash_id'),
                cg_id:$('#btn-accept-kas').attr('cg_id'),
                _token:token
            };
        }
        else if (type == "armada") {
            url = "/acceptCashArmada";
            param = {
                cash_id:$('#btn-accept-kas').attr('cash_id'),
                cr_id:$('#btn-accept-kas').attr('cr_id'),
                _token:token
            };
        }
        else if (type == "sales") {
            url = "/acceptCashSales";
            param = {
                cash_id:$('#btn-accept-kas').attr('cash_id'),
                cs_id:$('#btn-accept-kas').attr('cs_id'),
                _token:token
            };
        }
        $.ajax({
            url:url,
            data:param,
            method:"post",
            success:function(e){
                ResetLoadingButton('.btn-konfirmasi', "Konfirmasi");
                if (type=="admin") refreshCashAdmin();
                else if (type=="gudang") refreshCashGudang();
                else if (type=="armada") refreshCashArmada();
                else if (type=="sales") refreshCashSales();
                $('.modal').modal("hide");
                notifikasi('success', "Berhasil Terima", "Berhasil Terima Pengajuan");
                
            },
            error:function(e){
                console.log(e);
                ResetLoadingButton('.btn-konfirmasi', "Konfirmasi");
            }
        });
    })

    $(document).on('click', '.btn_decline', function(){
        var data = $('#tableCash').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        showModalDelete("Apakah yakin ingin tolak pengajuan ini?","btn-decline-kas");
        $('#btn-decline-kas').attr("cash_id", data.cash_id);
        if (type == "admin") $('#btn-decline-kas').attr("ca_id", data.ca_id);
        else if (type == "gudang") $('#btn-decline-kas').attr("cg_id", data.cg_id);
        else if (type == "armada") $('#btn-decline-kas').attr("cr_id", data.cr_id);
        else if (type == "sales") $('#btn-decline-kas').attr("cs_id", data.cs_id);
        $('#btn-decline-kas').html("Konfirmasi");
    })

    $(document).on('click', '#btn-decline-kas', function(){
        LoadingButton(this);
        let url = "";
        if (type == "admin") {
            url = "/declineCashAdmin";
            param = {
                cash_id:$('#btn-decline-kas').attr('cash_id'),
                ca_id:$('#btn-decline-kas').attr('ca_id'),
                _token:token
            };
        }
        else if (type == "gudang") {
            url = "/declineCashGudang";
            param = {
                cash_id:$('#btn-decline-kas').attr('cash_id'),
                cg_id:$('#btn-decline-kas').attr('cg_id'),
                _token:token
            };
        }
        else if (type == "armada") {
            url = "/declineCashArmada";
            param = {
                cash_id:$('#btn-decline-kas').attr('cash_id'),
                cr_id:$('#btn-decline-kas').attr('cr_id'),
                _token:token
            };
        }
        else if (type == "sales") {
            url = "/declineCashSales";
            param = {
                cash_id:$('#btn-decline-kas').attr('cash_id'),
                cs_id:$('#btn-decline-kas').attr('cs_id'),
                _token:token
            };
        }
        $.ajax({
            url:url,
            data:param,
            method:"post",
            success:function(e){
                ResetLoadingButton('.btn-konfirmasi', "Konfirmasi");
                if (type=="admin") refreshCashAdmin();
                else if (type=="gudang") refreshCashGudang();
                else if (type=="armada") refreshCashArmada();
                else if (type=="sales") refreshCashSales();
                $('.modal').modal("hide");
                notifikasi('success', "Berhasil Tolak", "Berhasil Tolak Pengajuan");
                
            },
            error:function(e){
                console.log(e);
                ResetLoadingButton('.btn-konfirmasi', "Konfirmasi");
            }
        });
    })

    function imageValue(image){
        $('#fotoProduksiImage').attr('src', public+"kas_admin/"+type+"/"+image);
        $('#fotoProduksiImage').attr('index', 0);
    }

    $(document).on("click", "#btn-lihat-bukti", function () {
        $("#add_cash_admin").modal("hide");
        $('.btn-prev,.btn-next').hide();
        $('#modalViewPhoto').modal("show");
    });
    $(document).on("click", "#btn-lihat-bukti-gudang", function () {
        $("#add_cash_gudang").modal("hide");
        $('.btn-prev,.btn-next').hide();
        $('#modalViewPhoto').modal("show");
    });
    $(document).on("click", "#btn-lihat-bukti-armada", function () {
        $("#add_cash_armada").modal("hide");
        $('.btn-prev,.btn-next').show();
        $('#modalViewPhoto').modal("show");
    });
    $(document).on("click", "#btn-lihat-bukti-sales", function () {
        $("#add_cash_sales").modal("hide");
        $('.btn-prev,.btn-next').show();
        $('#modalViewPhoto').modal("show");
    });

    $(document).on("hidden.bs.modal", "#modalViewPhoto", function () {
        if (type == "admin") $("#add_cash_admin").modal("show");
        else if (type == "gudang") $("#add_cash_gudang").modal("show");
        else if (type == "armada") $("#add_cash_armada").modal("show");
        else if (type == "sales") $("#add_cash_sales").modal("show");
        $('#modalViewPhoto').modal("hide");
    });

    $(document).on('click', '#btn-foto-bukti', function() {
        rotationAngle = 0;
        camRotation = 0;
        photoData = "";
        modeCamera=2;
        inputFile ="#bukti";
        $("#video").removeClass("rot90 rot180 rot270");
        $("#preview-box").hide();
        $("#camera").show();

        startCamera();
        $("#add_cash_admin").modal("hide");
        $('#modalPhoto').modal('show');
    });

    $(document).on('click', '#btn-foto-bukti-gudang', function() {
        rotationAngle = 0;
        camRotation = 0;
        photoData = "";
        modeCamera=2;
        inputFile ="#bukti_gudang";
        $("#video").removeClass("rot90 rot180 rot270");
        $("#preview-box").hide();
        $("#camera").show();

        startCamera();
        $("#add_cash_gudang").modal("hide");
        $('#modalPhoto').modal('show');
    });
    $(document).on('click', '#btn-foto-bukti-armada', function() {
        rotationAngle = 0;
        camRotation = 0;
        photoData = "";
        modeCamera=3;
        inputFile ="#bukti_armada";
        $("#video").removeClass("rot90 rot180 rot270");
        $("#preview-box").hide();
        $("#camera").show();

        startCamera();
        $("#add_cash_armada").modal("hide");
        $('#modalPhoto').modal('show');
    });
    $(document).on('click', '#btn-foto-bukti-sales', function() {
        rotationAngle = 0;
        camRotation = 0;
        photoData = "";
        modeCamera=3;
        inputFile ="#bukti_sales";
        $("#video").removeClass("rot90 rot180 rot270");
        $("#preview-box").hide();
        $("#camera").show();

        startCamera();
        $("#add_cash_sales").modal("hide");
        $('#modalPhoto').modal('show');
    });

    $(document).on('click', '#uploadBtn', function(){
        if (type == "admin") {
            if ($('#bukti').val() != "" || $('#bukti').val() != "null" || $('#bukti').val() != null) {
                $('#check_foto').show();
            } else {
                $('#check_foto').hide();
            }
            $("#add_cash_admin").modal("show");
        }
        else if (type == "gudang") {
            if ($('#bukti_gudang').val() != "" || $('#bukti_gudang').val() != "null" || $('#bukti_gudang').val() != null) {
                $('#check_foto_gudang').show();
            } else {
                $('#check_foto_gudang').hide();
            }
            $("#add_cash_gudang").modal("show");
        }
        else if (type == "armada") {
            if ($('#bukti_armada').val() != "" || $('#bukti_armada').val() != "null" || $('#bukti_armada').val() != null) {
                $('#check_foto_armada').show();
            } else {
                $('#check_foto_armada').hide();
            }
            $("#add_cash_armada").modal("show");
        }
        else if (type == "sales") {
            if ($('#bukti_sales').val() != "" || $('#bukti_sales').val() != "null" || $('#bukti_sales').val() != null) {
                $('#check_foto_sales').show();
            } else {
                $('#check_foto_sales').hide();
            }
            $("#add_cash_sales").modal("show");
        }
    })

    $(document).on('click', '.btn-prev', function(){
        var index = parseInt($('#fotoProduksiImage').attr('index'));
        console.log("index : "+index);
        
        if(index > 0){
            index -= 1;
            if (type == "armada"){
                $('#fotoProduksiImage').attr('src', public+"kas_admin/armada/"+list_photo[index]);
                $('#fotoProduksiImage').attr('index', index);
                $('#btn_download_photo').attr('href', public+"kas_admin/armada/"+list_photo[index]);
            }
            else if (type == "sales"){
                $('#fotoProduksiImage').attr('src', public+"kas_admin/sales/"+list_photo[index]);
                $('#fotoProduksiImage').attr('index', index);
                $('#btn_download_photo').attr('href', public+"kas_admin/sales/"+list_photo[index]);
            }
        }
    });
    $(document).on('click', '.btn-next', function(){
        var index = parseInt($('#fotoProduksiImage').attr('index'));
        console.log("index : "+index);
        if(index < list_photo.length - 1){
            index += 1;
            if (type == "armada"){
                $('#fotoProduksiImage').attr('src', public+"kas_admin/armada/"+list_photo[index]);
                $('#fotoProduksiImage').attr('index', index);
                $('#btn_download_photo').attr('href', public+"kas_admin/armada/"+list_photo[index]);
            }
            else if (type == "sales"){
                $('#fotoProduksiImage').attr('src', public+"kas_admin/sales/"+list_photo[index]);
                $('#fotoProduksiImage').attr('index', index);
                $('#btn_download_photo').attr('href', public+"kas_admin/sales/"+list_photo[index]);
            }
        }
    });

    $(document).on('change', '#start_date', function(){
        dates = [];
        var start = $('#start_date').val();
        var end = $('#end_date').val();
        dates.push(start);
        dates.push(end);
        if (type=="admin") refreshCashAdmin();
        else if (type=="gudang") refreshCashGudang();
        else if (type=="armada") refreshCashArmada();
        else if (type=="sales") refreshCashSales();
    })
    $(document).on('change', '#end_date', function(){
        dates = [];
        var start = $('#start_date').val();
        var end = $('#end_date').val();
        dates.push(start);
        dates.push(end);
        if (type=="admin") refreshCashAdmin();
        else if (type=="gudang") refreshCashGudang();
        else if (type=="armada") refreshCashArmada();
        else if (type=="sales") refreshCashSales();
    })
    $(document).on('click', '.btn-clear', function(){
        dates = null;
        $('#start_date').val("");
        $('#end_date').val("");
        $('#filter_staff_id').empty();
        $('#filter_customer_id').empty();
        if (type=="admin") refreshCashAdmin();
        else if (type=="gudang") refreshCashGudang();
        else if (type=="armada") refreshCashArmada();
        else if (type=="sales") refreshCashSales();
    })
    $(document).on('change', '#filter_staff_id', function(){
        if (type=="admin") refreshCashAdmin();
        else if (type=="gudang") refreshCashGudang();
    })