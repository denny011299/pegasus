    var mode=1;
    var tablePayables, tableReceiveables;
    var tandaTerima=[];
    var dates = null;
    var payablesXhr = null;
    var payablesTableReady = false;

    function payablesWrap() {
        return $('#tablePayables-wrap');
    }

    function setPayablesTableLoading(isLoading) {
        var $wrap = payablesWrap();
        if (!$wrap.length) return;
        $wrap.toggleClass('is-loading', !!isLoading);
    }

    function showPayablesSkeleton() {
        var $wrap = payablesWrap();
        if (!payablesTableReady || !tablePayables) {
            $wrap.removeClass('dt-ready is-loading').addClass('dt-pending');
        } else {
            $wrap.removeClass('dt-pending').addClass('dt-ready');
            setPayablesTableLoading(true);
        }
    }

    function hidePayablesSkeleton() {
        payablesTableReady = true;
        setPayablesTableLoading(false);
        payablesWrap().removeClass('dt-pending is-loading').addClass('dt-ready');
    }

    autocompleteRekening("#bank_kode");
    autocompleteSupplier("#supplier");
    $(document).ready(function(){
        showPayablesSkeleton();
        inisialisasi();
        refreshPayReceive();
    });
    
    function inisialisasi() {
        tablePayables = $('#tablePayables').DataTable({
            processing: true,
            deferRender: true,
            bFilter: true,
            sDom: 'fBtlpi',
            lengthMenu: [10, 25, 50, 100],
            pageLength: 10,
            ordering: true,
            order: [], // QC#11: jangan sort kolom checkbox; pertahankan urutan SQL FIELD(pembayaran, 1, 3, 2)
            searching: false,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Cari Hutang",
                info: "_START_ - _END_ of _TOTAL_ items",
                processing: '<div class="d-flex align-items-center gap-2"><span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span><span>Memuat data...</span></div>',
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            createdRow: function (row, data, dataIndex) {
                $(row).addClass('row-payables');
            },
            columns: [
                { data: "check", orderable: false, className: "text-center align-middle" },
                { 
                    data: "bank_kode",
                    className: "align-middle",
                    render: function (data) {
                        if (!data || data === "-") return '<span class="text-muted">-</span>';
                        return `<div style="display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:6px;background:#f8fafc;border:1px solid #e2e8f0;font-weight:600;font-size:12px;color:#1e293b;">
                                    <i class="fe fe-credit-card text-primary" style="font-size:12px;"></i> ${data}
                                </div>`;
                    }
                },
                { 
                    data: "date",
                    className: "align-middle text-nowrap",
                    render: function(data, type, row) {
                        if (type === 'sort') {
                            return row.po_date ?? data; // pakai date_raw kalau ada
                        }
                        return data;
                    }
                },
                { 
                    data: "date_due_date",
                    className: "align-middle text-nowrap",
                    render: function(data, type, row) {
                        if (type === 'sort') {
                            return row.poi_due ?? data; // pakai date_raw kalau ada
                        }
                        return data;
                    }
                },
                { 
                    data: "poi_code",
                    className: "align-middle font-monospace fw-semibold text-dark"
                },
                { 
                    data: "supplier_name",
                    className: "align-middle fw-semibold text-dark"
                },
                { 
                    data: "poi_total_text",
                    className: "align-middle fw-bold text-dark text-nowrap"
                },
                { 
                    data: "status_text",
                    className: "align-middle text-center"
                },
                { data: "action", class: "text-center align-middle" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
            drawCallback: function () {
                if (typeof feather !== 'undefined') feather.replace();
            },
        });

    }

    function refreshPayReceive() {
        if (payablesXhr && payablesXhr.readyState !== 4) {
            payablesXhr.abort();
        }

        showPayablesSkeleton();

        payablesXhr = $.ajax({
            url: "/getPoInvoice",
            method: "get",
            data: {
                bank_id: $('#bank_kode').val(),
                status: $('#status').val(),
                po_supplier: $('#supplier').val(),
                dates: dates,
            },
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                let total = 0;
                console.log(e);
                tablePayables.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    // QC#9: checkbox hanya untuk Belum Terbayar (bukan Ditolak/Terbayar/Menunggu TT)
                    e[i].can_tt = canSelectForTandaTerima(e[i]);
                    e[i].check = e[i].can_tt
                        ? `<input type="checkbox" class="form-check-input chk ch${e[i].poi_id}" poi_id="${e[i].poi_id}" style="cursor:pointer;" />`
                        : '';
                    e[i].date = moment(e[i].po_date).format('D MMM YYYY');
                    e[i].date_due_date = moment(e[i].poi_due).format('D MMM YYYY');
                    e[i].poi_total_text = formatRupiah(e[i].poi_total,"Rp ");
                    total += parseInt(e[i].poi_total);
                    
                    if (e[i].pembayaran == 1 && e[i].status == 1){
                        e[i].status_text = `<span class="badge" style="background:#fef3c7;color:#92400e;border:1px solid #fde68a;font-size:12px;font-weight:600;padding:5px 12px;border-radius:20px;">Belum Terbayar</span>`;
                    } else if (e[i].pembayaran == 2){
                        e[i].status_text = `<span class="badge" style="background:#ecfdf5;color:#047857;border:1px solid #a7f3d0;font-size:12px;font-weight:600;padding:5px 12px;border-radius:20px;">Terbayar</span>`;
                    } else if (e[i].pembayaran == 3) {
                        e[i].status_text = `<span class="badge" style="background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;font-size:12px;font-weight:600;padding:5px 12px;border-radius:20px;">Menunggu Tanda Terima</span>`;
                    } else {
                        e[i].status_text = `<span class="badge" style="background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;font-size:12px;font-weight:600;padding:5px 12px;border-radius:20px;">Ditolak</span>`;
                    }
                    e[i].action = hasAccessAction("Hutang", "view")
                        ? '<a href="/purchaseOrderDetailHutang/' +
                          e[i].po_id +
                          '" class="btn-action-icon btn_edit_invoice" style="width:32px;height:32px;border-radius:8px;background:#eff6ff;border:1px solid #bfdbfe;display:inline-flex;align-items:center;justify-content:center;color:#2563eb;transition:all 0.2s;" title="Lihat Detail"><i class="fe fe-eye"></i></a>'
                        : '<span class="text-muted small">—</span>';
                }

                tablePayables.rows.add(e).draw();
                feather.replace(); // Biar icon feather muncul lagi

                $('#totalHutang').html(`Rp ${formatRupiah(total)}`);
                $('#totalInvoice').html(e.length);
            },
            error: function (err) {
                if (err && err.statusText === 'abort') return;
                if (handlePermissionError(err)) return;
                console.error("Gagal load:", err);
            },
            complete: function (_xhr, status) {
                if (status === 'abort') return;
                hidePayablesSkeleton();
                if (tablePayables) tablePayables.columns.adjust();
            }
        });
    }
    $(document).on("change", "#bank_kode,#status,#supplier", function () {
        $('.jumlah_terpilih').trigger("click");
        refreshPayReceive();
    });

    $(document).on("click", ".chk", function () {
        var kode = $(this).attr("poi_id");
        var ada=false;
        tandaTerima.forEach(item => {
            if(item == kode){
                ada=true;
            }
        });
        if(ada){
            tandaTerima = tandaTerima.filter(item => item != kode);
        } else {
            tandaTerima.push(kode);
        }
        console.log(tandaTerima);
        $('#jumlah_terpilih').text(tandaTerima.length + " Selected");
    });

    $(document).on("click", ".jumlah_terpilih", function () {
        tandaTerima=[];
        $('.chk').prop('checked', false);
        $('#jumlah_terpilih').text("0 Selected");
    });

   $(document).on("change", "#selectAll", function () {
        // Gunakan instance tablePayables yang sudah didefinisikan di awal
        var rows = tablePayables.rows({ 'search': 'applied' }).nodes();
        var allData = tablePayables.rows({ 'search': 'applied' }).data();
        
        tandaTerima = []; // Reset array agar tidak double saat select all

        if ($(this).is(":checked")) {
            // 1. Centang checkbox eligible saja (skip Ditolak / non-TT)
            $('input.chk', rows).prop('checked', true);

            // 2. Masukkan hanya poi_id yang boleh buat TT
            allData.each(function (data) {
                if (canSelectForTandaTerima(data)) {
                    tandaTerima.push(data.poi_id.toString());
                }
            });
        } 
        else {
            // 1. Uncheck semua secara visual
            $('input.chk', rows).prop('checked', false);
            
            // 2. Kosongkan array
            tandaTerima = [];
        }

        // Update label jumlah terpilih
        $('#jumlah_terpilih').text(tandaTerima.length + " Selected");
        console.log("Current IDs:", tandaTerima);
    });

    // Eligible TT = badge Belum Terbayar (pembayaran=1 & invoice status=1). Ditolak: status invoice 0 / PO -1.
    function canSelectForTandaTerima(row) {
        return row && String(row.pembayaran) === '1' && String(row.status) === '1';
    }

    function hasDitolakInSelection() {
        var allData = tablePayables.rows().data().toArray();
        return tandaTerima.some(function (id) {
            var row = allData.find(function (r) {
                return String(r.poi_id) === String(id);
            });
            return row && !canSelectForTandaTerima(row);
        });
    }

    $(document).on("click", ".btn-create", function () {
       $('.invalid').removeClass('invalid');

        if(tandaTerima.length==0){
            notifikasi("error","Gagal Buat Surat Terima","Silahkan pilih minimal 1 faktur!");
            return false;
        }
        if (hasDitolakInSelection()) {
            notifikasi("error","Gagal Buat Surat Terima","Invoice ditolak tidak dapat dibuat tanda terima");
            return false;
        }
        console.log(tandaTerima);
        
        var url = '/generateTandaTerimaInvoice';
        $.ajax({
            url:url,
            method:"get",
            data:{
                poi_id:tandaTerima,
            },
            success:function(e){
                if(e.status&&e.status==-1){
                    notifikasi("error","Gagal Buat Surat Terima",e.message)
                    refreshPayReceive();
                }
                else if(e.status&&e.status==1){
                    notifikasi("success","Berhasil Buat Surat Terima","Surat tanda terima berhasil dibuat");
                    refreshPayReceive();
                    window.location.href = '/viewTandaTerima/' + e.tt_id;
                }

                tandaTerima=[];
                $('.chk').prop('checked', false);
                $('#selectAll').prop('checked', false);
                $('#jumlah_terpilih').text("0 Selected");
            },
            error:function(e){
                if (handlePermissionError(e)) return;
                console.log(e);
            }
        });
    });

    function syncHutangDates() {
        var start = $('#start_date').val();
        var end = $('#end_date').val();
        // Filter list/print hanya jika Dari & Sampai keduanya terisi
        dates = (start && end) ? [start, end] : null;
        return { start: start, end: end };
    }

    $(document).on('click', '.btn-print', function(){
        var range = syncHutangDates();
        if ((range.start && !range.end) || (!range.start && range.end)) {
            notifikasi('error', 'Gagal Print Hutang', 'Isi tanggal Dari dan Sampai terlebih dahulu');
            return;
        }

        let params = {
            bank_id: $('#bank_kode').val(),
            status: $('#status').val(),
            po_supplier: $('#supplier').val(),
            dates: dates
        };

        $.ajax({
            url: "/checkHutang",
            data: params,
            method: "get",
            success: function(e) {
                if (e.status === -1) {
                    notifikasi('error', 'Gagal Print Hutang', e.message);
                    return;
                }
                window.open('/generateHutang?' + $.param(params), '_self');
            },
            error: function(e){
                if (handlePermissionError(e)) return;
                console.error(e);
            }
        })
    })

    $(document).on('change', '#start_date', function(){
        syncHutangDates();
        refreshPayReceive();
    })
    $(document).on('change', '#end_date', function(){
        syncHutangDates();
        refreshPayReceive();
    })
    $(document).on('click', '.btn-clear', function(){
        dates = null;
        $('#start_date').val("");
        $('#end_date').val("");
        $('#status').val("");
        $('#bank_kode').empty();
        $('#supplier').empty();
        refreshPayReceive();
    })
    /*
    $(document).on('click', '.row-payables', function(){
        alert("test")
        $(this).find('input[type="checkbox"]').trigger('click'); 
    });*/