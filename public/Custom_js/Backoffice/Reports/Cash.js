    var mode=1;
    var table;
    let debit = 0, credit1 = 0, credit2 = 0;
    var dates = null;
    var list_photo;
    $(document).ready(function(){
        inisialisasi();
        refreshCash();
    });
    
    $(document).on('click','.btnAdd',function(){
        mode=1;
        $('#add_cash .modal-title').html("Tambah Pencatatan Kas");
        $('#add_cash input').val("");
        $('.is-invalid').removeClass('is-invalid');
        $('#add_cash').modal("show");
        $('#cash_select').val("debit").trigger('change');
        $('#cash_tujuan').val("");

        let today = new Date();
        let yyyy = today.getFullYear();
        let mm = String(today.getMonth() + 1).padStart(2, '0');
        let dd = String(today.getDate()).padStart(2, '0');
        let todayStr = yyyy + '-' + mm + '-' + dd;
        $("#cash_date").val(todayStr);
    });
    
    function inisialisasi() {
        table = $('#tableCash').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            lengthMenu: [10, 25, 50, 100],
            ordering: false,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Cari Kas",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                {
                    className: 'dt-control text-center',
                    orderable: false,
                    data: null,
                    defaultContent: '<i class="fe fe-plus-circle text-primary"></i>',
                    width: "2.5rem"
                },
                { data: "date" },
                { data: "cash_description" },
                { data: "debit_text", className: "text-end" },
                { data: "credit_text1", className: "text-end" },
                { data: "credit_text2", className: "text-end" },
                { data: "status_text" },
                { data: "action", className: "d-flex align-items-center" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });
    }

    function refreshCash() {
        $.ajax({
            url: "/getCash",
            method: "get",
            data: {dates: dates},
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                console.log(e);
                table.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                var debits = 0;
                var credits1 = 0;
                var credits2 = 0;
                var sisa = 0;
                var setor = 0;
                for (let i = 0; i < e.length; i++) {
                    e[i].date = moment(e[i].cash_date).format('D MMM YYYY');
                    if (e[i].cash_type == 1) {
                        e[i].debit = "Rp " + formatRupiahMinus(e[i].cash_nominal);
                        e[i].credit1 = "Rp " + 0;
                        e[i].credit2 = "Rp " + 0;
                        if (e[i].status == 2) {
                            debits += e[i].cash_nominal;
                            sisa += e[i].cash_nominal;
                        }
                    }
                    else if (e[i].cash_type == 2) {
                        e[i].credit1 = "(Rp " + formatRupiahMinus(e[i].cash_nominal) + ")";
                        e[i].debit = "Rp " + 0;
                        e[i].credit2 = "Rp " + 0;
                        if (e[i].status == 2) {
                            credits1 += e[i].cash_nominal;
                            sisa -= e[i].cash_nominal;
                        }
                    }
                    else if (e[i].cash_type == 3) {
                        e[i].credit2 = "(Rp " + formatRupiahMinus(e[i].cash_nominal) + ")";
                        e[i].credit1 = "Rp " + 0;
                        e[i].debit = "Rp " + 0;

                        // Kalau ini sales dan ada keluar 1, jangan dihitung (setor ke bank)
                        if (e[i].status == 2) {
                            if (e[i].cash_tujuan != 4) {
                                sisa -= e[i].cash_nominal;
                            } else {
                                setor += e[i].cash_nominal;
                            }
                            credits2 += e[i].cash_nominal;
                        }
                    }
                    e[i].debit_text =`<label class='text-success'>${e[i].debit}</label>`
                    e[i].credit_text1 =`<label class='text-danger'>${e[i].credit1}</label>`
                    e[i].credit_text2 =`<label class='text-danger'>${e[i].credit2}</label>`

                    if (e[i].status == 1){
                        e[i].status_text = `<span class="badge bg-warning" style="font-size: 12px">Menunggu Konfirmasi</span>`;
                    } else if (e[i].status == 2){
                        e[i].status_text = `<span class="badge bg-success" style="font-size: 12px">Diterima</span>`;
                    } else if (e[i].status == 3){
                        e[i].status_text = `<span class="badge bg-danger" style="font-size: 12px">Ditolak</span>`;
                    }

                    e[i].action = "";
                    if (e[i].status == 1){
                        e[i].action = `
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
                $('.debits').html(`Rp ${formatRupiahMinus(debits)}`);
                $('.credits1').html(`(Rp ${formatRupiahMinus(credits1)})`);
                $('.credits2').html(`(Rp ${formatRupiahMinus(credits2)})`);
                $('.sisa').html(`Rp ${formatRupiahMinus(sisa)}`);
                $('.setor').html(`Rp ${formatRupiahMinus(setor)}`);
                table.rows.add(e).draw();
                feather.replace(); // Biar icon feather muncul lagi

                // Expand child row
                setTimeout(function () {
                    $('#tableCash tbody td.dt-control').each(function () {
                        $(this).trigger('click');
                    });
                }, 100);
            },
            error: function (err) {
                console.error("Gagal load kas:", err);
            }
        });
    }

    $('#tableCash tbody').on('click', 'td.dt-control', function () {
        let tr = $(this).closest('tr');
        let row = table.row(tr);

        if (row.child.isShown()) {
            row.child.hide();
            tr.removeClass('shown');
        } else {
            let rowData = row.data();
            if (rowData.armada_operasional) {
                row.child(formatArmada(rowData)).show();
                tr.addClass('shown');
            }
            else if (rowData.sales_operasional) {
                row.child(formatSales(rowData)).show();
                tr.addClass('shown');
            }
        }
    });

    function formatArmada(rowData) {
        console.log(rowData)
        let operasional = rowData.armada_operasional;
        let penyerahan = rowData.armada_penyerahan;

        if (!operasional || operasional.length === 0) {
            return `
                <div class="p-3">
                    <em class="text-muted">Tidak ada detail operasional armada</em>
                </div>
            `;
        }

        let total = 0;
        let html = `<div class="px-5">`;

        if (penyerahan && penyerahan.length > 0) {
            console.log(penyerahan)
            penyerahan.forEach((p) => {
                total += parseInt(p.cr_nominal);
                html += `
                    <div class="child-item">
                        <div class="child-left d-flex g-3">
                            <div class="date me-3">${moment(p.cr_date).format('D MMM YYYY')}</div>
                            <div class="notes">${p.cr_notes}</div>
                        </div>
                        <div class="child-right text-end text-success">+ Rp ${formatRupiahMinus(p.cr_nominal)}</div>
                    </div>
                `;
            });
        }

        operasional.forEach((d) => {
            if (d.cr_type == 1) total += parseInt(d.cr_nominal);
            else if (d.cr_type >= 2) total -= parseInt(d.cr_nominal);

            var img = JSON.parse(d.cr_img);
            list_photo = img || null;

            html += `
                <div class="child-item">
                    <div class="child-left">
                        <div class="d-flex g-3">
                            <div class="date me-3">
                                ${moment(d.cr_date).format('D MMM YYYY')}
                            </div>
                            <div class="notes">
                                ${d.cr_notes}
                            </div>
                        </div>
                    </div>
                    <div class="child-right text-end" style="color: ${d.cr_type <= 2 ? (d.cr_type == 1 ? '#22cc62' : '#ff0000') : '#e8bd10'}">
                        ${d.cr_type == 1 ? '+' : '-'} Rp ${formatRupiahMinus(d.cr_nominal)}
                    </div>
                    <div class="d-flex">
                        ${d.detail_armada && d.detail_armada.length > 0 ? `
                        <a class="me-2 btn-action-icon p-2 btn-detail-armada" 
                            data-detail='${JSON.stringify(d.detail_armada)}'
                            data-notes='${d.cr_notes}'>
                            <i class="fe fe-list"></i>
                        </a>` : ''}
                        ${d.cr_img ? `
                        <a class="me-2 btn-action-icon p-2 btn-lihat-bukti-armada" 
                            data-img='${d.cr_img}'>
                            <i class="fe fe-eye"></i>
                        </a>` : ''}
                    </div>
                </div>
            `;
        });

        html += `
            <div class="child-item fw-semibold pt-3 border-top">
                <div class="child-left-total">Total Akhir</div>
                <div class="child-right text-end ${total > 0 ? 'text-success' : 'text-danger'}">${total > 0 ? '+' : '-'}Rp ${formatRupiahMinus(total)}</div>
            </div>
        `;

        // // Info pengembalian
        // if (pengembalian) {
        //     html += `
        //         <div class="child-item text-success pt-2">
        //             <div class="child-left-total">
        //                 Dikembalikan (${moment(pengembalian.created_at).format('D MMM YYYY')})
        //             </div>
        //             <div class="child-right text-end">
        //                 Rp ${formatRupiahMinus(pengembalian.cr_nominal)}
        //             </div>
        //         </div>
        //     `;
        // } else {
        //     html += `
        //         <div class="child-item text-warning pt-2">
        //             <div class="child-left-total"><em>Belum dikembalikan</em></div>
        //             <div class="child-right text-end">-</div>
        //         </div>
        //     `;
        // }

        html += `</div>`;
        return html;
    }
    function formatSales(rowData) {
        console.log(rowData)
        let operasional = rowData.sales_operasional;
        let penyerahan = rowData.sales_penyerahan;

        if (!operasional || operasional.length === 0) {
            return `
                <div class="p-3">
                    <em class="text-muted">Tidak ada detail operasional sales</em>
                </div>
            `;
        }

        let total = 0;
        let html = `<div class="px-5">`;

        if (penyerahan && penyerahan.length > 0) {
            penyerahan.forEach((p) => {
                console.log(p)
                total += parseInt(p.cs_nominal);
                html += `
                    <div class="child-item">
                        <div class="child-left d-flex g-3">
                            <div class="date me-3">${moment(p.cs_date).format('D MMM YYYY')}</div>
                            <div class="notes">${p.cs_notes}</div>
                        </div>
                        <div class="child-right text-end text-success">+ Rp ${formatRupiahMinus(p.cs_nominal)}</div>
                    </div>
                `;
            });
        }

        operasional.forEach((d) => {
            if (d.cs_transaction == 1) total += parseInt(d.cs_nominal);
            else if (d.cs_transaction >= 2) total -= parseInt(d.cs_nominal);

            var img = JSON.parse(d.cs_img);
            list_photo = img || null;

            html += `
                <div class="child-item">
                    <div class="child-left">
                        <div class="d-flex g-3">
                            <div class="date me-3">
                                ${moment(d.cs_date).format('D MMM YYYY')}
                            </div>
                            <div class="notes">
                                ${d.cs_notes}
                            </div>
                        </div>
                        
                    </div>
                    <div class="child-right text-end" style="color : ${d.cs_transaction <= 2 ? (d.cs_transaction == 1 ? '#22cc62' : '#ff0000') : '#e8bd10'}">
                        ${d.cs_transaction == 1 ? '+' : '-'} Rp ${formatRupiahMinus(d.cs_nominal)}
                    </div>
                    <div class="d-flex">
                        ${d.detail_armada && d.detail_armada.length > 0 ? `
                        <a class=" me-2 btn-action-icon p-2 btn-detail-sales" 
                            data-detail='${JSON.stringify(d.detail_armada)}'
                            data-notes='${d.cs_notes}'>
                            <i class="fe fe-list"></i>
                        </a>` : ''}
                        ${d.cs_img ? `
                        <a class="me-2 btn-action-icon p-2 btn-lihat-bukti-sales" 
                            data-img='${d.cs_img}'>
                            <i class="fe fe-eye"></i>
                        </a>` : ''}
                    </div>
                </div>
            `;
        });

        html += `
            <div class="child-item fw-semibold pt-3 border-top">
                <div class="child-left-total">Total Akhir</div>
                <div class="child-right text-end ${total > 0 ? 'text-success' : 'text-danger'}">${total > 0 ? '+' : '-'}Rp ${formatRupiahMinus(total)}</div>
            </div>
        `;

        html += `</div>`;
        return html;
    }

    $(document).on('click', '.btn-detail-sales', function () {
        var detail = JSON.parse($(this).attr('data-detail'));
        var notes  = $(this).attr('data-notes');
        let rows = '';
        let total = 0;

        detail.forEach(item => {
            total += item.csd_nominal;
            rows += `
                <tr>
                    <td>${item.csd_notes ?? '-'}</td>
                    <td class="text-end">Rp ${formatRupiahMinus(item.csd_nominal)}</td>
                </tr>
            `;
        });

        $('#modal-detail-sales .modal-title').text(notes ?? 'Detail Operasional');
        $('#detail-sales-body').html(rows);
        $('#detail-sales-total').html(`Rp ${formatRupiahMinus(total)}`);
        $('#modal-detail-sales').modal('show');
    });
    $(document).on('click', '.btn-detail-armada', function () {
        var detail = JSON.parse($(this).attr('data-detail'));
        var notes  = $(this).attr('data-notes');
        let rows = '';
        let total = 0;

        detail.forEach(item => {
            total += item.crd_nominal;
            rows += `
                <tr>
                    <td>${item.crd_notes ?? '-'}</td>
                    <td class="text-end">Rp ${formatRupiahMinus(item.crd_nominal)}</td>
                </tr>
            `;
        });

        $('#modal-detail-sales .modal-title').text(notes ?? 'Detail Operasional');
        $('#detail-sales-body').html(rows);
        $('#detail-sales-total').html(`Rp ${formatRupiahMinus(total)}`);
        $('#modal-detail-sales').modal('show');
    });

    $(document).on("click",".btn-save",function(){
       LoadingButton(this);
        $('.is-invalid').removeClass('is-invalid');
        var url ="/insertCash";
        var valid=1;

        $("#add_cash .fill").each(function(){
            if ($(this).attr('id') == 'cash_tujuan' && $('#cash_select').val() != "credit1"){
                return true;
            }
            if ($(this).val()==null||$(this).val()=="null"||$(this).val()=="") {
                valid=-1;
                $(this).addClass('is-invalid');
            }
        });

        if(valid==-1){
            notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
            ResetLoadingButton('.btn-save', mode == 1?"Tambah Pencatatan" : "Update Pencatatan");
            return false;
        };

        let type;
        if ($('#cash_select').val() == "debit"){
            type = 1;
        } else if ($('#cash_select').val() == "credit1") {
            type = 2;
        } else if ($('#cash_select').val() == "credit2") {
            type = 3;
        }

        param = {
            cash_date:$('#cash_date').val(),
            cash_description:$('#cash_description').val(),
            cash_type:type,
            cash_nominal:convertToAngkaMinus($('#cash_nominal').val()),
            // cash_tujuan: type == 2 ? $('#cash_tujuan').val() : null,
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
            success:function(e){      
                ResetLoadingButton(".btn-save", mode == 1?"Tambah Pencatatan" : "Update Pencatatan");      
                afterInsert();
            },
            error:function(e){
                ResetLoadingButton(".btn-save", mode == 1?"Tambah Pencatatan" : "Update Pencatatan");
                console.log(e);
            }
        });
    });

    function afterInsert() {
        $(".modal").modal("hide");
        if(mode==1)notifikasi('success', "Berhasil Insert", "Berhasil Tambah Pencatatan Kas");
        else if(mode==2)notifikasi('success', "Berhasil Update", "Berhasil Update Pencatatan Kas");
        refreshCash();
    }

    $(document).on('change', '#cash_select', function(){
        if($(this).val() == "credit2"){
            $('#tujuan').show();
        } else {
            $('#tujuan').hide();
        }
    })

    $(document).on('click', '.btn_acc', function(){
        var data = $('#tableCash').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        showModalKonfirmasi(
            "Apakah yakin ingin Approve pencatatan kas ini?",
            "btn-accept-kas"
        );
        console.log(data);
        $('#btn-accept-kas').attr("cash_id", data.cash_id);
        $('#btn-accept-kas').attr("cash_tujuan", data.cash_tujuan);
        $('#btn-accept-kas').html("Konfirmasi");
    })

    $(document).on('click', '#btn-accept-kas', function(){
        LoadingButton(this);
        let tujuan = $('#btn-accept-kas').attr('cash_tujuan');
        console.log(tujuan);
        let url = "";
        if (tujuan == 1) url = "/acceptCashAdmin";
        else if (tujuan == 2) url = "/acceptCashGudang";
        else if (tujuan == 3) url = "/acceptCashArmada";
        else if (tujuan == 4) url = "/acceptCashSales";
        $.ajax({
            url:url,
            data:{
                cash_id:$('#btn-accept-kas').attr('cash_id'),
                _token:token
            },
            method:"post",
            success:function(e){
                if (typeof e === "object"){
                    notifikasi('error', e.header, e.message);
                    ResetLoadingButton(".btn-konfirmasi", "Konfirmasi");   
                    return false;
                } else {
                    ResetLoadingButton('.btn-konfirmasi', "Konfirmasi");
                    refreshCash();
                    $('.modal').modal("hide");
                    notifikasi('success', "Berhasil Terima", "Berhasil Terima Pencatatan Kas");
                }
                
            },
            error:function(e){
                ResetLoadingButton('.btn-konfirmasi', "Konfirmasi");
                console.log(e);
            }
        });
    })

    $(document).on('click', '.btn_decline', function(){
        var data = $('#tableCash').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        showModalDelete("Apakah yakin ingin tolak pencatatan kas ini?","btn-decline-kas");
        $('#btn-decline-kas').attr("cash_id", data.cash_id);
        $('#btn-decline-kas').attr("cash_tujuan", data.cash_tujuan);
        $('#btn-decline-kas').html("Konfirmasi");
    })

    $(document).on('click', '#btn-decline-kas', function(){
        LoadingButton(this);
        let tujuan = $('#btn-decline-kas').attr('cash_tujuan');
        let url = "";
        if (tujuan == 1) url = "/declineCashAdmin";
        else if (tujuan == 2) url = "/declineCashGudang";
        else if (tujuan == 3) url = "/declineCashArmada";
        else if (tujuan == 4) url = "/declineCashSales";
        $.ajax({
            url:url,
            data:{
                cash_id:$('#btn-decline-kas').attr('cash_id'),
                _token:token
            },
            method:"post",
            success:function(e){
                ResetLoadingButton('.btn-konfirmasi', "Konfirmasi");
                refreshCash();
                $('.modal').modal("hide");
                notifikasi('success', "Berhasil Tolak", "Berhasil Tolak Pencatatan Kas");
                
            },
            error:function(e){
                ResetLoadingButton('.btn-konfirmasi', "Konfirmasi");
                console.log(e);
            }
        });
    })

    $(document).on('change', '#start_date', function(){
        dates = [];
        var start = $('#start_date').val();
        var end = $('#end_date').val();
        dates.push(start);
        dates.push(end);
        refreshCash();
    })
    $(document).on('change', '#end_date', function(){
        dates = [];
        var start = $('#start_date').val();
        var end = $('#end_date').val();
        dates.push(start);
        dates.push(end);
        refreshCash();
    })
    $(document).on('click', '.btn-clear', function(){
        dates = null;
        $('#start_date').val("");
        $('#end_date').val("");
        refreshCash();
    })

    $(document).on("click", ".btn-lihat-bukti-armada", function () {
        var imgRaw = $(this).attr('data-img');
        
        try {
            list_photo = JSON.parse(imgRaw);
        } catch(e) {
            list_photo = [imgRaw];
        }

        if (!list_photo || list_photo.length === 0) return;
        $('#fotoProduksiImage').attr('src', public + "kas_admin/armada/" + list_photo[0]);
        $('#fotoProduksiImage').attr('index', 0);
        $('#btn_download_photo').attr('href', public + "kas_admin/armada/" + list_photo[0]);
        $('#jumlahFoto').html(list_photo.length);
        $('.btn-prev, .btn-next').show();
        $('#modalViewPhoto').modal("show");
    });
    $(document).on("click", ".btn-lihat-bukti-sales", function () {
        var imgRaw = $(this).attr('data-img');
        
        try {
            list_photo = JSON.parse(imgRaw);
        } catch(e) {
            list_photo = [imgRaw];
        }

        if (!list_photo || list_photo.length === 0) return;
        $('#fotoProduksiImage').attr('src', public + "kas_admin/sales/" + list_photo[0]);
        $('#fotoProduksiImage').attr('index', 0);
        $('#btn_download_photo').attr('href', public + "kas_admin/sales/" + list_photo[0]);
        $('#jumlahFoto').html(list_photo.length);
        $('.btn-prev, .btn-next').show();
        $('#modalViewPhoto').modal("show");
    });

    $(document).on('click', '.btn-prev', function(){
        var index = parseInt($('#fotoProduksiImage').attr('index'));
        console.log("index : "+index);
        
        if(index > 0){
            index -= 1;
            $('#fotoProduksiImage').attr('src', public+"kas_admin/armada/"+list_photo[index]);
            $('#fotoProduksiImage').attr('index', index);
            $('#btn_download_photo').attr('href', public+"kas_admin/armada/"+list_photo[index]);
        }
    });
    $(document).on('click', '.btn-next', function(){
        var index = parseInt($('#fotoProduksiImage').attr('index'));
        console.log("index : "+index);
        if(index < list_photo.length - 1){
            index += 1;
            $('#fotoProduksiImage').attr('src', public+"kas_admin/armada/"+list_photo[index]);
            $('#fotoProduksiImage').attr('index', index);
            $('#btn_download_photo').attr('href', public+"kas_admin/armada/"+list_photo[index]);
        }
    });