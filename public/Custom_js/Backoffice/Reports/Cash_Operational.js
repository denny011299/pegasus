    var mode=1;
    var table;
    var type;
    var items = [];

    $(document).ready(function(){
        $('#cashType').trigger('change');
    });

    $(document).on('change', '#cashType', function () {
        items = [];
        type = $(this).val();
        if (type === 'admin') {
            $('#headers th:nth-child(2)').text('Staff');
            inisialisasi();
            refreshCashAdmin();
        }
        
        else if (type === 'gudang') {
            $('#headers th:nth-child(2)').text('Staff');
            inisialisasi();
            refreshCashGudang();
        }
        
        else if (type === 'armada') {
            $('#headers th:nth-child(2)').text('Armada');
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
        $('#add_cash_operational input').val("");
        $('#staff_id').empty(null);
        $('.is-invalid').removeClass('is-invalid');
        $('#oc_transaksi').val(1);
        $('.total').html("Rp 0");
        autocompleteStaff('#staff_id', '#add_cash_operational');
        $('#btn-foto-bukti').show();
        $('#btn-lihat-bukti').hide();
        $('#check_foto').hide();

        if ($(this).val() == "operasional") $('#tableDetail tr.row-detail').remove();
    })

    $(document).on('click','.btnAddCash',function(){
        mode=1;
        items = [];

        if (type == "admin"){
            $('#row-cash').html(`
                <label>Nama Staff<span class="text-danger">*</span></label>
                <select class="form-select fill" id="staff_id"></select>
            `);

            $('#add_cash_operational .modal-title').html("Tambah Aktivitas Admin");
            $('#add_cash_operational input').val("");
            $('#staff_id').empty(null);
            $('.is-invalid').removeClass('is-invalid');
            $('#jenis_input').val("saldo").trigger('change');
            $('#oc_transaksi').val(1);
            autocompleteStaff('#staff_id', '#add_cash_operational');
            $('.total').html("Rp 0");

            $('#btn-foto-bukti').show();
            $('#btn-lihat-bukti').hide();
            $('#check_foto').hide();

            if ($('#jenis_input').val() == "operasional") $('#tableDetail tr.row-detail').remove();
            $('#add_cash_operational').modal("show");
            
        }
        else if (type == "gudang"){
            $('#row-cash').html(`
                <label>Nama Staff<span class="text-danger">*</span></label>
                <select class="form-select fill" id="staff_id"></select>
            `);

            $('#add_cash_operational .modal-title').html("Tambah Aktivitas Gudang");
            $('#add_cash_operational input').val("");
            $('#staff_id').empty(null);
            $('#oc_transaksi').val(1);
            $('.is-invalid').removeClass('is-invalid');
            autocompleteStaff('#staff_id', '#add_cash_operational')
            $('#add_cash_operational').modal("show");
        }
        else if (type == "armada"){
            $('#row-cash').html(`
                <label>Nama Armada<span class="text-danger">*</span></label>
                <select class="form-select fill" id="armada_id"></select>
            `);

            $('#add_cash_operational .modal-title').html("Tambah Aktivitas Armada");
            $('#add_cash_operational input').val("");
            $('#armada_id').empty(null);
            $('#oc_transaksi').val(1);
            $('.is-invalid').removeClass('is-invalid');
            autocompleteCustomer('#armada_id', '#add_cash_operational')
            $('#add_cash_operational').modal("show");
        }
    });
    
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
                    className: 'dt-control',
                    orderable: false,
                    data: null,
                    defaultContent: '<i class="fe fe-plus"></i>',
                    width: "20px"
                },
                { data: "date" },
                { data: "staff_name" },
                { data: "ca_notes", width: "25%" },
                { data: "nominal" },
                { data: "status_text" },
                { data: "action", className: "d-flex align-items-center" },
            ];
            searchText = "Cari Kas Admin";
        } 
        else if (type === "gudang") {
            column = [
                {
                    className: 'dt-control',
                    orderable: false,
                    data: null,
                    defaultContent: '<i class="fe fe-plus"></i>',
                    width: "20px"
                },
                { data: "date" },
                { data: "staff_name" },
                { data: "cg_notes", width: "25%" },
                { data: "nominal" },
                { data: "status_text" },
                { data: "action", className: "d-flex align-items-center" },
            ];
            searchText = "Cari Kas Gudang";
        }

        table = $('#tableCash').DataTable({
            destroy: true,
            bFilter: true,
            sDom: 'fBtlpi',
            lengthMenu: [10, 25, 50, 100],
            ordering: false,
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
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                table.clear().draw(); 

                // Manipulasi data sebelum masuk ke tabel
                console.log(e);
                for (let i = 0; i < e.length; i++) {
                    e[i].date = moment(e[i].created_at).format('D MMM YYYY');
                    e[i].nominal = "Rp " + formatRupiah(e[i].ca_nominal);

                    if (e[i].status == 1){
                        e[i].status_text = `<span class="badge bg-warning" style="font-size: 12px">Sedang Diajukan</span>`;
                    } else if (e[i].status == 2){
                        e[i].status_text = `<span class="badge bg-success" style="font-size: 12px">Diterima</span>`;
                    } else if (e[i].status == 3){
                        e[i].status_text = `<span class="badge bg-danger" style="font-size: 12px">Ditolak</span>`;
                    }
                    e[i].action = "";
                    if (e[i].status == 1){
                        e[i].action = `
                            <a class="me-2 btn-action-icon p-2 btn_edit" data-id="${e[i].ca_id}" data-bs-target="#edit-category">
                                <i class="fe fe-edit"></i>
                            </a>
                            <a class="p-2 btn-action-icon btn_delete" data-id="${e[i].ca_id}" href="javascript:void(0);">
                                <i class="fe fe-trash-2"></i>
                            </a>
                        `;
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

    function refreshCashGudang() {
        $.ajax({
            url: "/getCashGudang",
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                table.clear().draw(); 

                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].date = moment(e[i].created_at).format('D MMM YYYY');
                    e[i].nominal = "Rp " + formatRupiah(e[i].cg_nominal);

                    if (e[i].status == 1){
                        e[i].status_text = `<span class="badge bg-warning" style="font-size: 12px">Sedang Diajukan</span>`;
                    } else if (e[i].status == 2){
                        e[i].status_text = `<span class="badge bg-success" style="font-size: 12px">Diterima</span>`;
                    } else if (e[i].status == 3){
                        e[i].status_text = `<span class="badge bg-danger" style="font-size: 12px">Ditolak</span>`;
                    }

                    e[i].action = `
                        <a class="me-2 btn-action-icon p-2 btn_edit" data-id="${e[i].cg_id}" data-bs-target="#edit-category">
                            <i class="fe fe-edit"></i>
                        </a>
                        <a class="p-2 btn-action-icon btn_delete" data-id="${e[i].cg_id}" href="javascript:void(0);">
                            <i class="fe fe-trash-2"></i>
                        </a>
                    `;
                }
                table.rows.add(e).draw();
                feather.replace(); // Biar icon feather muncul lagi
            },
            error: function (err) {
                console.error("Gagal load kategori:", err);
            }
        });
    }

    function format(detailData) {
        if (!detailData || detailData.length === 0) {
            return `<div class="p-2 text-muted">Tidak ada detail</div>`;
        }

        let html = `
            <table class="table table-sm table-bordered mb-0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Catatan</th>
                        <th class="text-end">Nominal</th>
                    </tr>
                </thead>
                <tbody>
        `;

        let total = 0;
        detailData.forEach((d, index) => {
            html += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${d.cad_notes}</td>
                    <td class="text-end">Rp ${formatRupiah(d.cad_nominal)}</td>
                </tr>
            `;
            total += d.cad_nominal;
        });

        html += `
            </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2" class="text-end fw-bold">Total</th>
                        <th class="text-end fw-bold">Rp ${formatRupiah(total)}</th>
                    </tr>
                </tfoot>
            </table>
        `;
        return html;
    }

    $('#tableCash tbody').on('click', 'td.dt-control', function () {
        let tr = $(this).closest('tr');
        let row = table.row(tr);

        if (row.child.isShown()) {
            row.child.hide();
            tr.removeClass('shown');
        } else {
            row.child(format(row.data().detail)).show();
            tr.addClass('shown');
        }
    });


    $(document).on('click', '.btn-add-catatan', function(){
        $('.is-invalid').removeClass('is-invalid');
        $('.is-invalids').removeClass('is-invalids');
        var valid=1;
        $("#add_cash_operational .fill_catatan").each(function(){
            if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                valid=-1;
                $(this).addClass('is-invalid');
            }
        });

        if(valid==-1){
            notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
            ResetLoadingButton('.btn-save', mode == 1?"Tambah Aktivitas" : "Update Aktivitas"); 
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
        console.log(items);
        $('#tableDetail tr.row-detail').html(" ");
        items.forEach((e, index) => {
            $('#tableDetail tbody').append(`
                <tr class="row-detail" data-id="${index}">
                    <td>${index+1}</td>
                    <td>${e.cad_notes}</td>
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

    $(document).on("click",".btn-save",function(){
        LoadingButton(this);
        $('.is-invalid').removeClass('is-invalid');
        $('.is-invalids').removeClass('is-invalids');

        var url = "";
        var valid=1;
        var jenis_input = $('#jenis_input').val();

        $("#add_cash_operational .fill").each(function(){
            if (type == "admin" && jenis_input == "saldo"){
                if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                    if ($(this).attr('class') == "saldo_kas"){
                        valid=-1;
                        $(this).addClass('is-invalid');
                    }
                }
            }
            else if (type == "admin" && jenis_input == "operasional"){
                if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                    if ($(this).attr('class') == "operasional"){
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

        if (type == "armada"){

        } else {
            if($('#staff_id').val()==null||$('#staff_id').val()=="null"||$('#staff_id').val()==""){
                valid=-1;
                $('#row-cash .select2-selection--single').addClass('is-invalids');
            }
        }

        if(valid==-1){
            notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
            ResetLoadingButton('.btn-save', mode == 1?"Tambah Kas" : "Update Kas");
            return false;
        };

        if (type == "admin"){
            url = "/insertCashAdmin";
            param = {
                staff_id:$('#staff_id').val(),
                ca_notes:$('#oc_notes').val(),
                ca_nominal:convertToAngka($('#oc_nominal').val()),
                oc_transaksi: $('#oc_transaksi').val(),
                ca_type: jenis_input == "saldo" ? 1 : 2,
                jenis_input: jenis_input,
                items: JSON.stringify(items),
                _token:token
            };
        }
        else if (type == "gudang"){
            url ="/insertCashGudang";
            param = {
                staff_id:$('#staff_id').val(),
                cg_notes:$('#oc_notes').val(),
                cg_nominal:convertToAngka($('#oc_nominal').val()),
                _token:token
            };
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
                ResetLoadingButton(".btn-save", mode == 1?"Tambah Aktivitas" : "Update Aktivitas");      
                afterInsert();
            },
            error:function(e){
                ResetLoadingButton(".btn-save", mode == 1?"Tambah Aktivitas" : "Update Aktivitas");
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
        else if (type=="armada") refreshCashAdmin();
    }

    function imageValue(image){
        $('#fotoProduksiImage').attr('src', public+"issue/"+image);
        $('#fotoProduksiImage').attr('index', 0);
    }

    $(document).on("click", "#btn-lihat-bukti", function () {
        $("#add-product-issues").modal("hide");
        $('.btn-prev,.btn-next').hide();
        $('#modalViewPhoto').modal("show");
    });

    $(document).on("hidden.bs.modal", "#modalViewPhoto", function () {
        $("#add-product-issues").modal("show");
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
        $("#add_cash_operational").modal("hide");
        $('#modalPhoto').modal('show');
        console.log($('#bukti').val());
    });

    $(document).on('click', '#uploadBtn', function(){
        if ($('#bukti').val() != "" || $('#bukti').val() != "null" || $('#bukti').val() != null) {
            $('#check_foto').show();
        } else {
            $('#check_foto').hide();
        }
        $("#add_cash_operational").modal("show");
    })