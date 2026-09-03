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

    function abortPayablesLoad() {
        if (payablesXhr && payablesXhr.readyState !== 4) {
            payablesXhr.abort();
        }
        payablesXhr = null;
    }

    function beginPayablesTableLoad() {
        var $wrap = payablesWrap();
        $wrap.removeClass('dt-pending').addClass('dt-ready');
        setPayablesTableLoading(true);
    }

    function getPayablesExtraParams() {
        return {
            bank_id: $('#bank_kode').val(),
            status: $('#status').val(),
            po_supplier: $('#supplier').val(),
            dates: dates,
        };
    }

    function applyPayablesMeta(json) {
        var meta = json && json.meta ? json.meta : {};
        var totalInvoice = meta.total_invoice !== undefined
            ? meta.total_invoice
            : (json && json.recordsFiltered !== undefined ? json.recordsFiltered : 0);
        var totalHutang = meta.total_hutang !== undefined ? meta.total_hutang : 0;
        $('#totalInvoice').html(totalInvoice);
        $('#totalHutang').html(`Rp ${formatRupiah(totalHutang)}`);
        $('#totalHutang').attr('title', `Rp ${formatRupiah(totalHutang)}`);
    }

    // STATUS badges 1:1 — jangan ubah mapping ini
    function formatPayableRow(row) {
        row.can_tt = canSelectForTandaTerima(row);
        row.check = row.can_tt
            ? `<input type="checkbox" class="form-check-input chk ch${row.poi_id}" poi_id="${row.poi_id}" style="cursor:pointer;" />`
            : '';
        row.date = moment(row.po_date).format('D MMM YYYY');
        row.date_due_date = moment(row.poi_due).format('D MMM YYYY');
        row.poi_total_text = formatRupiah(row.poi_total, "Rp ");

        if (row.pembayaran == 1 && row.status == 1) {
            row.status_text = `<span class="badge" style="background:#fef3c7;color:#92400e;border:1px solid #fde68a;font-size:12px;font-weight:600;padding:5px 12px;border-radius:20px;">Belum Terbayar</span>`;
        } else if (row.pembayaran == 2) {
            row.status_text = `<span class="badge" style="background:#ecfdf5;color:#047857;border:1px solid #a7f3d0;font-size:12px;font-weight:600;padding:5px 12px;border-radius:20px;">Terbayar</span>`;
        } else if (row.pembayaran == 3) {
            row.status_text = `<span class="badge" style="background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;font-size:12px;font-weight:600;padding:5px 12px;border-radius:20px;">Menunggu Tanda Terima</span>`;
        } else {
            row.status_text = `<span class="badge" style="background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;font-size:12px;font-weight:600;padding:5px 12px;border-radius:20px;">Ditolak</span>`;
        }

        row.action = hasAccessAction("Hutang", "view")
            ? '<a href="/purchaseOrderDetailHutang/' +
              row.po_id +
              '" class="btn-action-icon btn_edit_invoice" style="width:32px;height:32px;border-radius:8px;background:#eff6ff;border:1px solid #bfdbfe;display:inline-flex;align-items:center;justify-content:center;color:#2563eb;transition:all 0.2s;" title="Lihat Detail"><i class="fe fe-eye"></i></a>'
            : '<span class="text-muted small">—</span>';

        return row;
    }

    function restorePayablesChecks() {
        $('#tablePayables tbody input.chk').each(function () {
            var id = String($(this).attr('poi_id'));
            $(this).prop('checked', tandaTerima.indexOf(id) !== -1);
        });
        var $chks = $('#tablePayables tbody input.chk');
        if ($chks.length) {
            $('#selectAll').prop('checked', $chks.length > 0 && $chks.filter(':checked').length === $chks.length);
        } else {
            $('#selectAll').prop('checked', false);
        }
    }

    function payablesAjax(dtData, callback) {
        abortPayablesLoad();
        payablesXhr = $.ajax({
            url: '/getPoInvoice',
            method: 'get',
            data: $.extend({}, dtData, getPayablesExtraParams()),
            beforeSend: function () {
                beginPayablesTableLoad();
            },
            success: function (json) {
                if (!json || typeof json !== 'object') {
                    callback({
                        draw: dtData.draw,
                        recordsTotal: 0,
                        recordsFiltered: 0,
                        data: [],
                    });
                    return;
                }
                var rows = Array.isArray(json.data) ? json.data : [];
                for (var i = 0; i < rows.length; i++) {
                    formatPayableRow(rows[i]);
                }
                json.data = rows;
                applyPayablesMeta(json);
                callback(json);
            },
            error: function (err) {
                if (err && err.statusText === 'abort') return;
                if (handlePermissionError(err)) return;
                console.error('Gagal load:', err);
                callback({
                    draw: dtData.draw,
                    recordsTotal: 0,
                    recordsFiltered: 0,
                    data: [],
                });
            },
            complete: function (_xhr, status) {
                if (status === 'abort') return;
                hidePayablesSkeleton();
                if (tablePayables) tablePayables.columns.adjust();
            },
        });
    }

    function refreshPayReceive(resetPaging) {
        if (!tablePayables) return;
        showPayablesSkeleton();
        tablePayables.ajax.reload(null, resetPaging !== false);
    }

    autocompleteRekening("#bank_kode");
    autocompleteSupplier("#supplier");
    $(document).ready(function(){
        showPayablesSkeleton();
        inisialisasi();
    });
    
    function inisialisasi() {
        if ($.fn.DataTable.isDataTable('#tablePayables')) {
            $('#tablePayables').DataTable().destroy();
            $('#tablePayables tbody').empty();
            payablesTableReady = false;
        }

        tablePayables = $('#tablePayables').DataTable({
            destroy: true,
            processing: true,
            serverSide: true,
            deferRender: true,
            bFilter: true,
            sDom: 'fBtlpi',
            lengthMenu: [10, 25, 50, 100],
            pageLength: 10,
            ordering: false, // pertahankan urutan SQL FIELD(pembayaran, 1, 3, 2)
            searching: false,
            autoWidth: false,
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
                            return row.po_date ?? data;
                        }
                        return data;
                    }
                },
                { 
                    data: "date_due_date",
                    className: "align-middle text-nowrap",
                    render: function(data, type, row) {
                        if (type === 'sort') {
                            return row.poi_due ?? data;
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
            ajax: function (data, callback) {
                payablesAjax(data, callback);
            },
            initComplete: function () {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
                hidePayablesSkeleton();
            },
            drawCallback: function () {
                if (typeof feather !== 'undefined') feather.replace();
                restorePayablesChecks();
            },
        });
    }

    $(document).on("change", "#bank_kode,#status,#supplier", function () {
        $('.jumlah_terpilih').trigger("click");
        refreshPayReceive(true);
    });

    $(document).on("click", ".chk", function () {
        var kode = String($(this).attr("poi_id"));
        var ada = tandaTerima.indexOf(kode) !== -1;
        if (ada) {
            tandaTerima = tandaTerima.filter(item => item != kode);
        } else {
            tandaTerima.push(kode);
        }
        $('#jumlah_terpilih').text(tandaTerima.length + " Selected");
        restorePayablesChecks();
    });

    $(document).on("click", ".jumlah_terpilih", function () {
        tandaTerima=[];
        $('.chk').prop('checked', false);
        $('#selectAll').prop('checked', false);
        $('#jumlah_terpilih').text("0 Selected");
    });

   $(document).on("change", "#selectAll", function () {
        // SS: hanya halaman saat ini
        var rows = tablePayables.rows({ page: 'current' }).nodes();
        var pageData = tablePayables.rows({ page: 'current' }).data();

        if ($(this).is(":checked")) {
            $('input.chk', rows).prop('checked', true);
            pageData.each(function (data) {
                if (canSelectForTandaTerima(data)) {
                    var id = String(data.poi_id);
                    if (tandaTerima.indexOf(id) === -1) {
                        tandaTerima.push(id);
                    }
                }
            });
        } else {
            $('input.chk', rows).prop('checked', false);
            pageData.each(function (data) {
                var id = String(data.poi_id);
                tandaTerima = tandaTerima.filter(item => item != id);
            });
        }

        $('#jumlah_terpilih').text(tandaTerima.length + " Selected");
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
            if (!row) return false; // di luar halaman saat ini — server yang validasi
            return !canSelectForTandaTerima(row);
        });
    }

    var BTN_CREATE_HTML = '<i class="fe fe-file-plus me-2 text-white" style="font-size: 14px;"></i><span class="text-white">Buat Tanda Terima</span>';
    var BTN_PRINT_HTML = '<i class="fe fe-printer me-2" style="font-size: 14px;"></i><span>Print Hutang</span>';

    function resetHutangActionBtn($btn, html) {
        if (!$btn || !$btn.length) return;
        $btn.data("busy", false);
        ResetLoadingButton($btn, html);
    }

    $(document).on("click", ".btn-create", function () {
        var $btn = $(this);
        if ($btn.data("busy") || $btn.prop("disabled")) return;

       $('.invalid').removeClass('invalid');

        if(tandaTerima.length==0){
            notifikasi("error","Gagal Buat Surat Terima","Silahkan pilih minimal 1 faktur!");
            return false;
        }
        if (hasDitolakInSelection()) {
            notifikasi("error","Gagal Buat Surat Terima","Invoice ditolak tidak dapat dibuat tanda terima");
            return false;
        }

        $btn.data("busy", true);
        LoadingButton($btn);

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
                    resetHutangActionBtn($btn, BTN_CREATE_HTML);
                }
                else if(e.status&&e.status==1){
                    notifikasi("success","Berhasil Buat Surat Terima","Surat tanda terima berhasil dibuat");
                    refreshPayReceive();
                    window.location.href = '/viewTandaTerima/' + e.tt_id;
                } else {
                    resetHutangActionBtn($btn, BTN_CREATE_HTML);
                }

                tandaTerima=[];
                $('.chk').prop('checked', false);
                $('#selectAll').prop('checked', false);
                $('#jumlah_terpilih').text("0 Selected");
            },
            error:function(e){
                resetHutangActionBtn($btn, BTN_CREATE_HTML);
                if (handlePermissionError(e)) return;
                console.log(e);
            },
            complete: function (_xhr, textStatus) {
                if (textStatus === "abort") {
                    resetHutangActionBtn($btn, BTN_CREATE_HTML);
                }
            }
        });
    });

    // List filter: partial dates OK (start-only → today; end-only → from beginning)
    function syncHutangDates() {
        var start = $('#start_date').val() || '';
        var end = $('#end_date').val() || '';
        if (!start && !end) {
            dates = null;
        } else {
            dates = [start, end];
        }
        return { start: start, end: end };
    }

    $(document).on('click', '.btn-print', function(){
        var $btn = $(this);
        if ($btn.data("busy") || $btn.prop("disabled")) return;

        // Print tetap butuh keduanya (tidak diubah)
        var start = $('#start_date').val();
        var end = $('#end_date').val();
        if ((start && !end) || (!start && end)) {
            notifikasi('error', 'Gagal Print Hutang', 'Isi tanggal Dari dan Sampai terlebih dahulu');
            return;
        }

        var printDates = (start && end) ? [start, end] : null;
        let params = {
            bank_id: $('#bank_kode').val(),
            status: $('#status').val(),
            po_supplier: $('#supplier').val(),
            dates: printDates
        };

        $btn.data("busy", true);
        LoadingButton($btn);
        // Outline putih: text-light dari helper tidak terlihat
        $btn.find(".spinner-border").removeClass("text-light").addClass("text-primary");

        $.ajax({
            url: "/checkHutang",
            data: params,
            method: "get",
            success: function(e) {
                if (e.status === -1) {
                    notifikasi('error', 'Gagal Print Hutang', e.message);
                    resetHutangActionBtn($btn, BTN_PRINT_HTML);
                    return;
                }
                window.open('/generateHutang?' + $.param(params), '_self');
                resetHutangActionBtn($btn, BTN_PRINT_HTML);
            },
            error: function(e){
                resetHutangActionBtn($btn, BTN_PRINT_HTML);
                if (handlePermissionError(e)) return;
                console.error(e);
            },
            complete: function (_xhr, textStatus) {
                if (textStatus === "abort") {
                    resetHutangActionBtn($btn, BTN_PRINT_HTML);
                }
            }
        })
    })

    $(document).on('change', '#start_date', function(){
        syncHutangDates();
        refreshPayReceive(true);
    })
    $(document).on('change', '#end_date', function(){
        syncHutangDates();
        refreshPayReceive(true);
    })
    $(document).on('click', '.btn-clear', function(){
        dates = null;
        $('#start_date').val("");
        $('#end_date').val("");
        $('#status').val("");
        $('#bank_kode').empty();
        $('#supplier').empty();
        tandaTerima = [];
        $('#selectAll').prop('checked', false);
        $('#jumlah_terpilih').text("0 Selected");
        refreshPayReceive(true);
    })
