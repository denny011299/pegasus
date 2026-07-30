(function ($) {
    "use strict";

    var csrTable = null;
    var csrLines = [];
    var csrContext = null;
    var csrMode = "create";
    var csrExistingProofUrl = "";
    var csrHydrating = false;
    var csrXhr = null;

    function can(action) {
        return typeof hasAccessAction === "function" && hasAccessAction("Pengiriman", action);
    }

    function esc(value) {
        return $("<div>").text(value == null ? "" : value).html();
    }

    function csrf() {
        return $('meta[name="csrf-token"]').attr("content") || window.token;
    }

    function errorMessage(xhr) {
        if (xhr.responseJSON && xhr.responseJSON.errors) {
            return Object.values(xhr.responseJSON.errors).flat().join("<br>");
        }
        return (xhr.responseJSON && xhr.responseJSON.message) || "Terjadi kesalahan. Silakan coba kembali.";
    }

    function notifyError(xhr) {
        if (typeof notifikasi === "function") {
            notifikasi("error", "Gagal", errorMessage(xhr));
        } else {
            toastr.error(errorMessage(xhr));
        }
    }

    function statusBadge(status) {
        status = parseInt(status, 10);
        if (status === 1) {
            return '<span class="badge" style="background-color: #fff7ed; color: #ea580c; border: 1px solid #ffedd5; padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 12px; letter-spacing: 0.3px;"><i class="fe fe-clock me-1"></i> Pending</span>';
        }
        if (status === 2) {
            return '<span class="badge" style="background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0; padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 12px; letter-spacing: 0.3px;"><i class="fe fe-check-circle me-1"></i> Diterima</span>';
        }
        if (status === 3) {
            return '<span class="badge" style="background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 12px; letter-spacing: 0.3px;"><i class="fe fe-x-circle me-1"></i> Ditolak</span>';
        }
        return "-";
    }

    function originInfo(value) {
        var full = (value || "-").toString()
            .replace(/^\s*Sesuai produk:\s*/i, "")
            .trim();
        var products = full && full !== "-"
            ? full.split(/\s*,\s*/).filter(Boolean)
            : [];
        var first = products[0] || "-";
        var shortName = first.length > 36 ? first.slice(0, 35).trimEnd() + "…" : first;

        return {
            full: full || "-",
            summary: shortName + (products.length > 1 ? " +" + (products.length - 1) + " produk" : ""),
        };
    }

    function renderOrigin(value) {
        var origin = originInfo(value);
        return '<small class="csr-origin-summary text-muted" title="' + esc(origin.full) + '">' +
            esc(origin.summary) + "</small>";
    }

    function renderStaff(data, approved) {
        if (!data || data === "-") return '<span class="text-muted">-</span>';
        return '<div class="d-flex align-items-center gap-2 csr-staff">' +
            '<span class="csr-staff-icon' + (approved ? " csr-staff-icon-success" : "") + '">' +
            '<i class="fe ' + (approved ? "fe-user-check" : "fe-user") + '"></i></span>' +
            '<span class="fw-semibold text-dark csr-staff-name" title="' + esc(data) + '">' +
            esc(data) + "</span></div>";
    }

    function adjustTable() {
        if (!csrTable) return;
        csrTable.columns.adjust();
        if (csrTable.responsive && typeof csrTable.responsive.recalc === "function") {
            csrTable.responsive.recalc();
        }
    }

    function setActiveTableFilter(active) {
        var $search = $(".search-input").first();
        if (!$search.length) return;
        $search.find(".dataTables_filter").not(".csr-table-filter")
            .addClass("shipping-table-filter");
        $(".csr-table-filter").toggle(active === "return");
        $(".shipping-table-filter").toggle(active === "shipping");
    }

    function setTableLoading(loading) {
        $("#tableCustomerSupplyReturn-wrap").toggleClass("is-loading", !!loading);
    }

    function customerReturnAjax(data, callback) {
        if (csrXhr && csrXhr.readyState !== 4) csrXhr.abort();
        csrXhr = $.ajax({
            url: "/customerSupplyReturns",
            type: "GET",
            data: data,
            beforeSend: function () {
                setTableLoading(true);
            },
            success: callback,
            error: function (xhr) {
                if (xhr && xhr.statusText === "abort") return;
                callback({
                    draw: data.draw,
                    recordsTotal: 0,
                    recordsFiltered: 0,
                    data: [],
                });
                notifyError(xhr);
            },
            complete: function () {
                setTableLoading(false);
            },
        });
    }

    function actionButtons(row) {
        var html = '<div class="d-flex justify-content-center align-items-center gap-2">';
        var canView = can("view");
        var canEdit = parseInt(row.status, 10) === 1 && can("edit");
        var canDelete = parseInt(row.status, 10) === 1 && can("delete");

        if (canView) {
            html += '<a class="btn-action-icon csr-view" data-id="' + row.return_id + '" href="javascript:void(0);" style="background:#eff6ff;border:1px solid #bfdbfe;color:#2563eb;" data-bs-toggle="tooltip" title="Lihat"><i class="fe fe-eye" style="font-size:14px;"></i></a>';
        }
        if (canEdit) {
            html += '<a class="btn-action-icon csr-edit" data-id="' + row.return_id + '" href="javascript:void(0);" style="background:#fffbeb;border:1px solid #fde68a;color:#d97706;" data-bs-toggle="tooltip" title="Edit"><i class="fe fe-edit-2" style="font-size:14px;"></i></a>';
        }
        if (canDelete) {
            html += '<a class="btn-action-icon csr-delete" data-id="' + row.return_id + '" href="javascript:void(0);" style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;" data-bs-toggle="tooltip" title="Hapus"><i class="fe fe-trash-2" style="font-size:14px;"></i></a>';
        }
        html += '</div>';
        if (!canView && !canEdit && !canDelete) {
            return '<span class="text-muted small">—</span>';
        }
        return html;
    }

    function initTable() {
        if (csrTable || !$("#tableCustomerSupplyReturn").length) return;
        $("#tableCustomerSupplyReturn")
            .off(".dt.csr")
            .on("preXhr.dt.csr processing.dt.csr", function (_event, _settings, processing) {
                setTableLoading(processing !== false);
            })
            .on("xhr.dt.csr error.dt.csr", function () {
                setTableLoading(false);
            });
        csrTable = $("#tableCustomerSupplyReturn").DataTable({
            processing: true,
            serverSide: true,
            deferRender: true,
            bFilter: true,
            sDom: "fBtlpi",
            searchDelay: 400,
            order: [[1, "desc"], [0, "desc"]],
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            ordering: true,
            autoWidth: false,
            scrollX: false,
            ajax: customerReturnAjax,
            language: {
                search: " ",
                searchPlaceholder: "Cari Pengembalian",
                sLengthMenu: "_MENU_",
                info: "_START_ - _END_ of _TOTAL_ items",
                emptyTable: "Belum ada pengembalian customer",
                zeroRecords: "Pengembalian tidak ditemukan",
                processing: '<div><span class="spinner-border spinner-border-sm text-primary" role="status"></span><span>Memuat pengembalian...</span></div>',
                paginate: {
                    next: ' <i class="fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> ',
                },
            },
            columns: [
                {
                    data: "return_number",
                    defaultContent: "-",
                    width: "145px",
                    className: "text-center align-middle",
                    render: function (value, type) {
                        if (type !== "display") return value;
                        if (!value || value === '-') return '-';
                        return `<span class="badge" style="background:#f1f5f9;color:#334155;border:1px solid #cbd5e1;padding:6px 10px;">${esc(value)}</span>`;
                    },
                },
                {
                    data: "return_date",
                    width: "155px",
                    className: "text-start align-middle",
                    render: function (value, type) {
                        if (type !== "display") return value;
                        if (!value || value === '-') return '<span style="color:#64748b;">-</span>';
                        var dateFmt = moment(value).format('D MMM YYYY');
                        return `<div style="display:flex;align-items:center;gap:10px;">
                                    <div style="width:32px;height:32px;border-radius:8px;background:#f8fafc;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;color:#64748b;flex-shrink:0;">
                                        <i class="fe fe-calendar"></i>
                                    </div>
                                    <span class="fw-semibold text-dark">${dateFmt}</span>
                                </div>`;
                    },
                },
                {
                    data: "so_invoice_no",
                    width: "145px",
                    className: "text-center align-middle",
                    render: function (value, type, row) {
                        var raw = (value || row.so_number || "").toString().trim();
                        if (type !== "display") return raw;
                        if (!raw || raw === '-') return '-';
                        return `<span class="badge" style="background:#f8fafc;color:#475569;border:1px solid #e2e8f0;padding:6px 10px;">${esc(raw)}</span>`;
                    },
                },
                {
                    data: "customer_name",
                    width: "190px",
                    className: "text-start align-middle",
                    render: function (data, type) {
                        if (type !== "display") return data;
                        if (!data || data === '-') return '<span style="color:#64748b;">-</span>';
                        return `<div style="display:flex;align-items:center;gap:10px;">
                                    <div style="width:32px;height:32px;border-radius:8px;background:#eff6ff;border:1px solid #bfdbfe;display:flex;align-items:center;justify-content:center;color:#2563eb;flex-shrink:0;">
                                        <i class="fe fe-truck"></i>
                                    </div>
                                    <span class="fw-semibold text-dark">${esc(data)}</span>
                                </div>`;
                    },
                },
                { data: "status", className: "text-center align-middle", width: "125px", render: statusBadge },
                {
                    data: "created_by_name",
                    defaultContent: "-",
                    width: "165px",
                    className: "text-start align-middle",
                    render: function (data, type) {
                        if (type !== "display") return data;
                        return typeof renderCreatedByName === "function"
                            ? renderCreatedByName(data)
                            : renderStaff(data, false);
                    },
                },
                {
                    data: "acc_by_name",
                    defaultContent: "-",
                    width: "180px",
                    className: "text-start align-middle",
                    render: function (data, type) {
                        return type === "display" ? renderStaff(data, true) : data;
                    },
                },
                { data: null, orderable: false, searchable: false, className: "text-center align-middle", width: "90px", render: actionButtons },
            ],
            drawCallback: function () {
                setTableLoading(false);
                if (typeof feather !== "undefined") feather.replace();
                $('[data-bs-toggle="tooltip"]').tooltip();
                adjustTable();
            },
            initComplete: function () {
                var $filter = $("#tableCustomerSupplyReturn_wrapper .dataTables_filter");
                $filter.addClass("csr-table-filter").appendTo($(".search-input").first());
                if (!$filter.find("label .fa-search").length) {
                    $filter.find("label").prepend('<i class="fa fa-search"></i> ');
                }
                $("#tableCustomerSupplyReturn-wrap").removeClass("dt-pending").addClass("dt-ready");
                setTableLoading(false);
                setActiveTableFilter("return");
                adjustTable();
            },
        });
    }

    function setupSoSelect() {
        var $select = $("#csr-so");
        if ($select.hasClass("select2-hidden-accessible")) $select.select2("destroy");
        $select.select2({
            width: "100%",
            dropdownParent: $("#customer-supply-return-modal"),
            placeholder: "Pilih Sales Order diterima",
            allowClear: true,
            ajax: {
                url: "/customerSupplyReturns/sales-orders",
                dataType: "json",
                delay: 300,
                data: function (params) { return { q: params.term || "" }; },
                processResults: function (response) { return response; },
            },
        });
    }

    function setupCustomerSelect() {
        var $select = $("#csr-customer");
        if ($select.hasClass("select2-hidden-accessible")) $select.select2("destroy");
        autocompleteCustomer(
            "#csr-customer",
            "#customer-supply-return-modal .modal-content"
        );
    }

    function setCustomer(customerId, customerName) {
        var $select = $("#csr-customer");
        $select.empty();
        if (customerId) {
            $select.append(new Option(customerName || "-", customerId, true, true));
        }
        $select.trigger("change.select2");
        setSelectInvalid("#csr-customer", false);
    }

    function destroySelect($select) {
        if ($select.hasClass("select2-hidden-accessible")) $select.select2("destroy");
    }

    function setupLocalSelect(selector, placeholder) {
        var $select = $(selector);
        destroySelect($select);
        var config = {
            width: "100%",
            dropdownParent: $("#customer-supply-return-modal"),
            placeholder: placeholder,
            allowClear: true,
        };
        if (selector === "#csr-supply") {
            config.templateResult = function (option) {
                if (!option.id) return option.text;
                var origin = originInfo($(option.element).data("origin"));
                return $('<div><div class="fw-semibold"></div><small class="text-muted d-block text-truncate"></small></div>')
                    .attr("title", origin.full)
                    .find("div").text(option.text).end()
                    .find("small").text("Dari " + origin.summary).end();
            };
            config.templateSelection = function (option) {
                if (!option.id) return option.text;
                var origin = originInfo($(option.element).data("origin"));
                return $("<span>").attr("title", origin.full)
                    .text(option.text + " — dari " + origin.summary);
            };
        }
        $select.select2(config);
    }

    function setSelectInvalid(selector, invalid) {
        $(selector).next(".select2-container").find(".select2-selection")
            .toggleClass("is-invalids", !!invalid);
    }

    function cameraPhotos() {
        var value = $("#csr-proof-camera").val();
        if (typeof parsePhotoInputValue === "function") return parsePhotoInputValue(value);
        if (!value) return [];
        try {
            var parsed = JSON.parse(value);
            return Array.isArray(parsed) ? parsed.filter(Boolean) : [parsed];
        } catch (_error) {
            return [value];
        }
    }

    function currentProofUrl() {
        return cameraPhotos()[0] || csrExistingProofUrl || "";
    }

    function refreshProofState() {
        var hasProof = !!currentProofUrl() || !!$("#csr-proof-file")[0].files.length;
        $("#csr-check-foto").toggleClass("d-none", !hasProof);
        $("#csr-btn-view-proof").toggleClass("d-none", !hasProof);
        $("#csr-btn-upload-proof").removeClass("border-danger text-danger");
    }

    function resetModal() {
        csrMode = "create";
        csrLines = [];
        csrContext = null;
        csrExistingProofUrl = "";
        $("#csr-id,#csr-ref-number,#csr-notes,#csr-qty").val("");
        $("#csr-date").val(new Date().toISOString().slice(0, 10));
        $("#csr-proof-camera,#csr-proof-file").val("");
        $("#csr-check-foto,#csr-btn-view-proof,#csr-bom-warning").addClass("d-none");
        $("#csr-proof-preview").attr("src", "");
        $("#csr-proof-download").attr("href", "");
        $("#csr-so").empty().prop("disabled", false);
        destroySelect($("#csr-customer"));
        $("#csr-customer").empty().prop("disabled", false);
        setSelectInvalid("#csr-customer", false);
        ["#csr-supply", "#csr-unit", "#csr-warehouse"].forEach(function (selector) {
            destroySelect($(selector));
            $(selector).empty().prop("disabled", false).removeClass("csr-locked");
            setSelectInvalid(selector, false);
        });

        $("#csr-qty").removeClass("is-invalid");
        $("#csr-line-form").removeClass("d-none");
        $("#csr-save").removeClass("d-none").text("Simpan Pengembalian");
        $("#csr-btn-upload-proof").removeClass("d-none border-danger text-danger");
        $("#csr-accept,#csr-decline").addClass("d-none");
        $("#customer-supply-return-modal .modal-title").text("Tambah Pengembalian");
        $("#customer-supply-return-modal input, #customer-supply-return-modal textarea").prop("readonly", false);
        setupSoSelect();
        setupCustomerSelect();
        renderLines();
    }

    function applyContext(context) {
        csrContext = context;
        var so = context.sales_order || {};
        setCustomer(so.customer_id, so.customer_name);

        var $supply = $("#csr-supply").empty()
            .append(new Option("Pilih bahan / kemasan", ""));
        (context.supplies || []).forEach(function (supply) {
            var option = new Option(supply.supplies_name, supply.supplies_id);
            $(option).data("origin", supply.origin_text || "-");
            $supply.append(option);
        });
        setupLocalSelect("#csr-supply", "Cari dan pilih bahan / kemasan");

        var warehouseOptions = '<option value="">Pilih gudang utama</option>';
        (context.warehouses || []).forEach(function (warehouse) {
            warehouseOptions += '<option value="' + warehouse.id + '">' + esc(warehouse.warehouse_name) + "</option>";
        });
        $("#csr-warehouse").html(warehouseOptions);
        setupLocalSelect("#csr-warehouse", "Cari dan pilih gudang utama");
        if ((context.warehouses || []).length > 0) {
            $("#csr-warehouse").val(String(context.warehouses[0].id))
                .trigger("change.select2");
        }
        $("#csr-unit").html('<option value="">Pilih satuan</option>');
        setupLocalSelect("#csr-unit", "Pilih satuan");

        var missing = context.missing_bom_products || [];
        if (missing.length) {
            $("#csr-bom-warning").removeClass("d-none")
                .html("<strong>Produk tanpa resep aktif:</strong> " + esc(missing.join(", ")) + ". Produk tersebut tidak menyediakan pilihan bahan.");
        } else {
            $("#csr-bom-warning").addClass("d-none");
        }
    }

    function loadContext(soId, done) {
        $.get("/customerSupplyReturns/sales-orders/" + soId)
            .done(function (context) {
                if (parseInt($("#csr-so").val(), 10) !== parseInt(soId, 10)) return;
                applyContext(context);
                if (typeof done === "function") done(context);
            })
            .fail(notifyError);
    }

    function selectedSupply() {
        if (!csrContext) return null;
        var id = parseInt($("#csr-supply").val(), 10);
        return (csrContext.supplies || []).find(function (supply) {
            return parseInt(supply.supplies_id, 10) === id;
        }) || null;
    }

    function renderLines() {
        var html = "";
        csrLines.forEach(function (line, index) {
            html += "<tr>" +
                "<td class=\"fw-semibold\">" + esc(line.supplies_name) + "</td>" +
                "<td>" + renderOrigin(line.origin_text) + "</td>" +
                "<td>" + esc(line.unit_name) + "</td>" +
                "<td>" + esc(line.qty) + "</td>" +
                "<td>" + esc(line.warehouse_name) + "</td>" +
                '<td class="text-center">' + (csrMode === "view" ? "—" :
                    '<a href="javascript:void(0);" class="btn-action-icon csr-remove-line" data-index="' + index + '" style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;" title="Hapus"><i class="fe fe-trash-2" style="font-size:14px;"></i></a>') +
                "</td></tr>";
        });
        if (!html) html = '<tr><td colspan="6" class="text-center text-muted py-4">Belum ada bahan ditambahkan.</td></tr>';
        $("#csr-lines").html(html);
        if (typeof feather !== "undefined") feather.replace();
    }

    function openRecord(id, mode) {
        $.get("/customerSupplyReturns/" + id).done(function (record) {
            resetModal();
            csrMode = mode;
            $("#csr-id").val(record.return_id);
            $("#csr-date").val(String(record.return_date || "").slice(0, 10));
            $("#csr-ref-number").val(record.ref_number || "");
            $("#csr-notes").val(record.notes || "");
            csrHydrating = true;
            var soContext = (record.context || {}).sales_order || {};
            $("#csr-so").append(new Option(
                (record.so_invoice_no || record.so_number) + " — " + (soContext.customer_name || "-"),
                record.so_id,
                true,
                true
            )).trigger("change.select2");
            csrHydrating = false;
            applyContext(record.context || {});
            setCustomer(record.customer_id, record.customer_name);
            csrLines = (record.details || []).map(function (detail) {
                return {
                    supplies_id: parseInt(detail.supplies_id, 10),
                    supplies_name: detail.supplies_name,
                    origin_text: detail.origin_text || "-",
                    unit_id: parseInt(detail.unit_id, 10),
                    unit_name: detail.unit_name || detail.unit_short_name || "-",
                    warehouse_id: parseInt(detail.warehouse_id, 10),
                    warehouse_name: detail.warehouse_name,
                    qty: parseInt(detail.qty, 10),
                };
            });
            renderLines();
            if (record.proof_url) {
                csrExistingProofUrl = record.proof_url;
                refreshProofState();
            }

            if (mode === "view") {
                $("#customer-supply-return-modal .modal-title").text("Detail Pengembalian " + record.return_number);
                $("#customer-supply-return-modal input, #customer-supply-return-modal textarea").prop("disabled", true);
                $("#csr-so,#csr-customer,#csr-supply,#csr-unit,#csr-warehouse").prop("disabled", true);
                $("#csr-btn-upload-proof").addClass("d-none");
                $("#csr-line-form,#csr-save").addClass("d-none");
                if (parseInt(record.status, 10) === 1 && can("others")) {
                    $("#csr-accept,#csr-decline").removeClass("d-none");
                }
            } else {
                $("#customer-supply-return-modal .modal-title").text("Edit Pengembalian " + record.return_number);
                $("#csr-save").text("Update Pengembalian");
                $("#csr-btn-upload-proof").removeClass("d-none");
            }
            $("#customer-supply-return-modal").modal("show");
        }).fail(notifyError);
    }

    function submitRecord() {
        var customerId = $("#csr-customer").val();
        setSelectInvalid("#csr-customer", !customerId);
        if (!$("#csr-date").val() || !$("#csr-so").val() || !customerId || !csrLines.length) {
            toastr.error("Tanggal, referensi pengiriman, Armada, dan minimal satu bahan wajib diisi.");
            return;
        }
        var photos = cameraPhotos();
        var proofFile = $("#csr-proof-file")[0].files[0];
        if (csrMode === "create" && !photos.length && !proofFile) {
            $("#csr-btn-upload-proof").addClass("border-danger text-danger");
            toastr.error("Bukti foto wajib diunggah.");
            return;
        }
        var form = new FormData();
        form.append("_token", csrf());
        form.append("so_id", $("#csr-so").val());
        form.append("customer_id", customerId);
        form.append("return_date", $("#csr-date").val());
        form.append("ref_number", $("#csr-ref-number").val());
        form.append("notes", $("#csr-notes").val());
        form.append("details", JSON.stringify(csrLines.map(function (line) {
            return {
                supplies_id: line.supplies_id,
                unit_id: line.unit_id,
                warehouse_id: line.warehouse_id,
                qty: line.qty,
            };
        })));
        if (photos.length) {
            form.append("proof_base64", photos[0]);
        } else if (proofFile) {
            form.append("proof", proofFile);
        }

        var id = $("#csr-id").val();
        var url = csrMode === "edit" ? "/customerSupplyReturns/" + id : "/customerSupplyReturns";
        $("#csr-save").prop("disabled", true);
        $.ajax({ url: url, method: "POST", data: form, processData: false, contentType: false })
            .done(function (response) {
                $("#customer-supply-return-modal").modal("hide");
                toastr.success(response.message || "Pengembalian berhasil disimpan.");
                if (csrTable) csrTable.ajax.reload(null, false);
            })
            .fail(notifyError)
            .always(function () { $("#csr-save").prop("disabled", false); });
    }

    function processRecord(id, action, question) {
        Swal.fire({
            icon: action === "accept" ? "question" : "warning",
            title: question,
            text: action === "accept" ? "Stok bahan akan bertambah dan tindakan ini tidak dapat diulang." : "Penolakan tidak mengubah stok.",
            showCancelButton: true,
            confirmButtonText: "Ya, lanjutkan",
            cancelButtonText: "Batal",
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.post("/customerSupplyReturns/" + id + "/" + action, { _token: csrf() })
                .done(function (response) {
                    $("#customer-supply-return-modal").modal("hide");
                    toastr.success(response.message);
                    if (csrTable) csrTable.ajax.reload(null, false);
                }).fail(notifyError);
        });
    }

    $(function () {
        setupSoSelect();
        setupCustomerSelect();
        $("#customer-return-tab").on("shown.bs.tab", function () {
            $(".btnAdd").addClass("d-none");
            initTable();
            setActiveTableFilter("return");
            window.setTimeout(adjustTable, 0);
        });
        $("#shipping-tab").on("shown.bs.tab", function () {
            $(".btnAdd").removeClass("d-none");
            setActiveTableFilter("shipping");
            if (window.table) window.table.columns.adjust();
        });

        $("#csr-add").on("click", function () {
            resetModal();
            $("#customer-supply-return-modal").modal("show");
        });
        $("#csr-so").on("change", function () {
            var soId = parseInt($(this).val(), 10);
            if (csrMode === "view" || csrHydrating) return;
            csrLines = [];
            csrContext = null;
            renderLines();
            setCustomer(null, null);
            if (!soId) return;
            loadContext(soId);
        });
        $("#csr-customer").on("change", function () {
            setSelectInvalid("#csr-customer", !$(this).val());
        });
        $("#csr-supply").on("change", function () {
            var supply = selectedSupply();
            setSelectInvalid("#csr-supply", false);
            destroySelect($("#csr-unit"));
            $("#csr-unit").html('<option value="">Pilih satuan</option>');

            (supply ? supply.units : []).forEach(function (unit) {
                $("#csr-unit").append(new Option(unit.unit_name || unit.unit_short_name, unit.unit_id));
            });
            setupLocalSelect("#csr-unit", "Pilih satuan");
            if (supply && supply.default_unit_id) {
                $("#csr-unit")
                    .val(String(supply.default_unit_id))
                    .trigger("change.select2");
            }
            $("#csr-qty").val(supply ? 1 : "");
        });
        $("#csr-unit,#csr-warehouse").on("change", function () {
            setSelectInvalid("#" + this.id, false);
        });
        $("#csr-qty").on("input", function () {
            $(this).removeClass("is-invalid");
        });
        $("#csr-add-line").on("click", function () {
            var supply = selectedSupply();
            var unitId = parseInt($("#csr-unit").val(), 10);
            var warehouseId = parseInt($("#csr-warehouse").val(), 10);
            var qty = parseInt($("#csr-qty").val(), 10);
            setSelectInvalid("#csr-supply", !supply);
            setSelectInvalid("#csr-unit", !unitId);
            setSelectInvalid("#csr-warehouse", !warehouseId);
            $("#csr-qty").toggleClass("is-invalid", !qty || qty <= 0);
            if (!supply || !unitId || !warehouseId || !qty || qty <= 0) {
                toastr.error("Pilih bahan, satuan, qty positif, dan gudang utama.");
                return;
            }
            var keyMatch = function (line) {
                return line.supplies_id === parseInt(supply.supplies_id, 10) &&
                    line.unit_id === unitId && line.warehouse_id === warehouseId;
            };
            var existing = csrLines.find(keyMatch);
            if (existing) {
                existing.qty += qty;
            } else {
                csrLines.push({
                    supplies_id: parseInt(supply.supplies_id, 10),
                    supplies_name: supply.supplies_name,
                    origin_text: supply.origin_text,
                    unit_id: unitId,
                    unit_name: $("#csr-unit option:selected").text(),
                    warehouse_id: warehouseId,
                    warehouse_name: $("#csr-warehouse option:selected").text(),
                    qty: qty,
                });
            }
            $("#csr-supply").val(null).trigger("change");
            $("#csr-unit").val(null).trigger("change.select2");
            $("#csr-qty").val("").removeClass("is-invalid");
            renderLines();
        });
        $(document).on("click", ".csr-remove-line", function () {
            csrLines.splice(parseInt($(this).data("index"), 10), 1);
            renderLines();
        });
        $("#csr-proof-file").on("change", function () {
            var file = this.files[0];
            if (!file) {
                refreshProofState();
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                toastr.error("Ukuran bukti maksimal 5 MB.");
                $(this).val("");
                return;
            }
            $("#csr-proof-camera").val("");
            refreshProofState();
        });
        $("#csr-btn-upload-proof").on("click", function () {
            rotationAngle = 0;
            camRotation = 0;
            photoData = "";
            modeCamera = 3;
            inputFile = "#csr-proof-camera";
            cameraReturnModal = "#customer-supply-return-modal";
            resetCameraModalUi();
            startCamera();
            $("#customer-supply-return-modal").modal("hide");
            $("#modalPhoto").modal("show");
        });
        $(document).on("click", "#uploadBtn", function () {
            if (inputFile === "#csr-proof-camera") {
                window.setTimeout(function () {
                    var photos = cameraPhotos();
                    if (photos.length) {
                        $("#csr-proof-camera").val(JSON.stringify([photos[photos.length - 1]]));
                        $("#csr-proof-file").val("");
                    }
                    refreshProofState();
                }, 0);
            }
        });
        $("#csr-btn-view-proof").on("click", function () {
            var source = currentProofUrl();
            var file = $("#csr-proof-file")[0].files[0];
            if (!source && file) source = URL.createObjectURL(file);
            if (!source) {
                toastr.error("Bukti foto belum tersedia.");
                return;
            }
            $("#csr-proof-preview").attr("src", source);
            $("#csr-proof-download").attr("href", source);
            $("#customer-supply-return-modal").modal("hide");
            $("#csr-photo-preview-modal").modal("show");
        });
        $("#csr-photo-preview-modal").on("hidden.bs.modal", function () {
            $("#customer-supply-return-modal").modal("show");
        });
        $("#csr-save").on("click", submitRecord);
        $(document).on("click", ".csr-view", function () { openRecord($(this).data("id"), "view"); });
        $(document).on("click", ".csr-edit", function () { openRecord($(this).data("id"), "edit"); });
        $(document).on("click", ".csr-delete", function () {
            var id = $(this).data("id");
            Swal.fire({ icon: "warning", title: "Hapus pengembalian?", showCancelButton: true, confirmButtonText: "Hapus" })
                .then(function (result) {
                    if (!result.isConfirmed) return;
                    $.post("/customerSupplyReturns/" + id + "/delete", { _token: csrf() })
                        .done(function (response) {
                            toastr.success(response.message);
                            csrTable.ajax.reload(null, false);
                        }).fail(notifyError);
                });
        });
        $("#csr-accept").on("click", function () { processRecord($("#csr-id").val(), "accept", "ACC pengembalian?"); });
        $("#csr-decline").on("click", function () { processRecord($("#csr-id").val(), "decline", "Tolak pengembalian?"); });
    });
})(jQuery);
