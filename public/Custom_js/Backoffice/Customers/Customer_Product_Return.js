(function ($) {
    "use strict";

    var cprTable = null;
    var cprLines = [];
    var cprContext = null;
    var cprMode = "create";
    var cprExistingProofUrl = "";
    var cprXhr = null;

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

    function renderStaff(data, approved) {
        if (!data || data === "-") return '<span class="text-muted">-</span>';
        return '<div class="d-flex align-items-center gap-2 csr-staff">' +
            '<span class="csr-staff-icon' + (approved ? " csr-staff-icon-success" : "") + '">' +
            '<i class="fe ' + (approved ? "fe-user-check" : "fe-user") + '"></i></span>' +
            '<span class="fw-semibold text-dark csr-staff-name" title="' + esc(data) + '">' +
            esc(data) + "</span></div>";
    }

    function adjustTable() {
        if (!cprTable) return;
        cprTable.columns.adjust();
        if (cprTable.responsive && typeof cprTable.responsive.recalc === "function") {
            cprTable.responsive.recalc();
        }
    }

    function setActiveTableFilter(active) {
        var $search = $(".search-input").first();
        if (!$search.length) return;
        $search.find(".dataTables_filter").not(".csr-table-filter").not(".cpr-table-filter")
            .addClass("shipping-table-filter");
        $(".csr-table-filter").toggle(active === "supply-return");
        $(".cpr-table-filter").toggle(active === "product-return");
        $(".shipping-table-filter").toggle(active === "shipping");
    }

    function setTableLoading(loading) {
        $("#tableCustomerProductReturn-wrap").toggleClass("is-loading", !!loading);
    }

    function productReturnAjax(data, callback) {
        if (cprXhr && cprXhr.readyState !== 4) cprXhr.abort();
        cprXhr = $.ajax({
            url: "/customerProductReturns",
            type: "GET",
            data: data,
            beforeSend: function () { setTableLoading(true); },
            success: callback,
            error: function (xhr) {
                if (xhr && xhr.statusText === "abort") return;
                callback({ draw: data.draw, recordsTotal: 0, recordsFiltered: 0, data: [] });
                notifyError(xhr);
            },
            complete: function () { setTableLoading(false); },
        });
    }

    function actionButtons(row) {
        var html = '<div class="d-flex justify-content-center align-items-center gap-2">';
        var canView = can("view");
        var canEdit = parseInt(row.status, 10) === 1 && can("edit");
        var canDelete = parseInt(row.status, 10) === 1 && can("delete");

        if (canView) {
            html += '<a class="btn-action-icon cpr-view" data-id="' + row.return_id + '" href="javascript:void(0);" style="background:#eff6ff;border:1px solid #bfdbfe;color:#2563eb;" data-bs-toggle="tooltip" title="Lihat"><i class="fe fe-eye" style="font-size:14px;"></i></a>';
        }
        if (canEdit) {
            html += '<a class="btn-action-icon cpr-edit" data-id="' + row.return_id + '" href="javascript:void(0);" style="background:#fffbeb;border:1px solid #fde68a;color:#d97706;" data-bs-toggle="tooltip" title="Edit"><i class="fe fe-edit-2" style="font-size:14px;"></i></a>';
        }
        if (canDelete) {
            html += '<a class="btn-action-icon cpr-delete" data-id="' + row.return_id + '" href="javascript:void(0);" style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;" data-bs-toggle="tooltip" title="Hapus"><i class="fe fe-trash-2" style="font-size:14px;"></i></a>';
        }
        html += '</div>';
        if (!canView && !canEdit && !canDelete) {
            return '<span class="text-muted small">—</span>';
        }
        return html;
    }

    function initTable() {
        if (cprTable || !$("#tableCustomerProductReturn").length) return;
        $("#tableCustomerProductReturn")
            .off(".dt.cpr")
            .on("preXhr.dt.cpr processing.dt.cpr", function (_event, _settings, processing) {
                setTableLoading(processing !== false);
            })
            .on("xhr.dt.cpr error.dt.cpr", function () {
                setTableLoading(false);
            });
        cprTable = $("#tableCustomerProductReturn").DataTable({
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
            ajax: productReturnAjax,
            language: {
                search: " ",
                searchPlaceholder: "Cari Pengembalian Produk",
                sLengthMenu: "_MENU_",
                info: "_START_ - _END_ of _TOTAL_ items",
                emptyTable: "Belum ada pengembalian produk jadi",
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
                    data: "ref_number",
                    width: "145px",
                    className: "text-center align-middle",
                    render: function (value, type) {
                        if (type !== "display") return value || "";
                        if (!value) return '-';
                        return `<span class="badge" style="background:#f8fafc;color:#475569;border:1px solid #e2e8f0;padding:6px 10px;">${esc(value)}</span>`;
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
                var $filter = $("#tableCustomerProductReturn_wrapper .dataTables_filter");
                $filter.addClass("cpr-table-filter").appendTo($(".search-input").first());
                if (!$filter.find("label .fa-search").length) {
                    $filter.find("label").prepend('<i class="fa fa-search"></i> ');
                }
                $("#tableCustomerProductReturn-wrap").removeClass("dt-pending").addClass("dt-ready");
                setTableLoading(false);
                setActiveTableFilter("product-return");
                adjustTable();
            },
        });
    }

    function setupCustomerSelect() {
        var $select = $("#cpr-customer");
        if ($select.hasClass("select2-hidden-accessible")) $select.select2("destroy");
        autocompleteCustomer("#cpr-customer", "#customer-product-return-modal .modal-content");
    }

    function setCustomer(customerId, customerName) {
        var $select = $("#cpr-customer");
        $select.empty();
        if (customerId) {
            $select.append(new Option(customerName || "-", customerId, true, true));
        }
        $select.trigger("change.select2");
        setSelectInvalid("#cpr-customer", false);
    }

    function destroySelect($select) {
        if ($select.hasClass("select2-hidden-accessible")) $select.select2("destroy");
    }

    function setupLocalSelect(selector, placeholder) {
        var $select = $(selector);
        destroySelect($select);
        $select.select2({
            width: "100%",
            dropdownParent: $("#customer-product-return-modal"),
            placeholder: placeholder,
            allowClear: true,
        });
    }

    function setSelectInvalid(selector, invalid) {
        $(selector).next(".select2-container").find(".select2-selection")
            .toggleClass("is-invalids", !!invalid);
    }

    function cameraPhotos() {
        var value = $("#cpr-proof-camera").val();
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
        return cameraPhotos()[0] || cprExistingProofUrl || "";
    }

    function refreshProofState() {
        var hasProof = !!currentProofUrl() || !!$("#cpr-proof-file")[0].files.length;
        $("#cpr-check-foto").toggleClass("d-none", !hasProof);
        $("#cpr-btn-view-proof").toggleClass("d-none", !hasProof);
        $("#cpr-btn-upload-proof").removeClass("border-danger text-danger");
    }

    function setupProductSelect() {
        destroySelect($("#cpr-product"));
        $("#cpr-product").empty();
        autocompleteProductVariantOnly("#cpr-product", "#customer-product-return-modal .modal-content");
    }

    function applyContext(context) {
        cprContext = context || {};
        setupProductSelect();

        var warehouseOptions = '<option value="">Pilih gudang</option>';
        (cprContext.warehouses || []).forEach(function (warehouse) {
            warehouseOptions += '<option value="' + warehouse.id + '">' + esc(warehouse.warehouse_name) + "</option>";
        });
        $("#cpr-warehouse").html(warehouseOptions);
        setupLocalSelect("#cpr-warehouse", "Cari dan pilih gudang");
        if ((cprContext.warehouses || []).length > 0) {
            $("#cpr-warehouse").val(String(cprContext.warehouses[0].id)).trigger("change.select2");
        }
        $("#cpr-unit").html('<option value="">Pilih satuan</option>');
        setupLocalSelect("#cpr-unit", "Pilih satuan");
    }

    function loadContext(done) {
        $.get("/customerProductReturns/context")
            .done(function (context) {
                applyContext(context);
                if (typeof done === "function") done(context);
            })
            .fail(notifyError);
    }

    function resetModal() {
        cprMode = "create";
        cprLines = [];
        cprContext = null;
        cprExistingProofUrl = "";
        $("#cpr-id,#cpr-ref-number,#cpr-notes,#cpr-qty").val("");
        $("#cpr-date").val(new Date().toISOString().slice(0, 10));
        $("#cpr-proof-camera,#cpr-proof-file").val("");
        $("#cpr-check-foto,#cpr-btn-view-proof").addClass("d-none");
        $("#cpr-proof-preview").attr("src", "");
        $("#cpr-proof-download").attr("href", "");
        destroySelect($("#cpr-customer"));
        $("#cpr-customer").empty().prop("disabled", false);
        setSelectInvalid("#cpr-customer", false);
        ["#cpr-product", "#cpr-unit", "#cpr-warehouse"].forEach(function (selector) {
            destroySelect($(selector));
            $(selector).empty().prop("disabled", false);
            setSelectInvalid(selector, false);
        });

        $("#cpr-qty").removeClass("is-invalid");
        $("#cpr-line-form").removeClass("d-none");
        $("#cpr-save").removeClass("d-none").text("Simpan Pengembalian");
        $("#cpr-btn-upload-proof").removeClass("d-none border-danger text-danger");
        $("#cpr-accept,#cpr-decline").addClass("d-none");
        $("#customer-product-return-modal .modal-title").text("Tambah Pengembalian Produk Jadi");
        $("#customer-product-return-modal input, #customer-product-return-modal textarea").prop("readonly", false).prop("disabled", false);
        setupCustomerSelect();
        renderLines();
        loadContext();
    }

    function isRetailWarehouse(warehouseId) {
        var id = parseInt(warehouseId, 10);
        if (!id || !cprContext) return false;
        var warehouse = (cprContext.warehouses || []).find(function (row) {
            return parseInt(row.id, 10) === id;
        });
        return !!warehouse && parseInt(warehouse.is_main_warehouse, 10) === 0;
    }

    function selectedProduct() {
        var data = $("#cpr-product").select2("data")[0];
        if (!data || !data.id) return null;
        var units = Array.isArray(data.pr_unit) ? data.pr_unit : [];
        var label =
            typeof formatProductVariantSelect2Label === "function"
                ? formatProductVariantSelect2Label(data)
                : data.text || "-";
        var retailUnitId = parseInt(data.retail_unit || 0, 10) || null;
        return {
            product_variant_id: parseInt(data.product_variant_id || data.id, 10),
            product_label: label,
            default_unit_id: parseInt(data.default_unit || data.default_unit_id || data.unit_id || 0, 10) || null,
            retail_unit: retailUnitId,
            units: units.map(function (unit) {
                return {
                    unit_id: parseInt(unit.unit_id, 10),
                    unit_name: unit.unit_name || unit.unit_short_name || "-",
                    unit_short_name: unit.unit_short_name || unit.unit_name || "-",
                };
            }),
        };
    }

    function fillUnitOptions() {
        var product = selectedProduct();
        var warehouseId = parseInt($("#cpr-warehouse").val(), 10);
        var retailOnly = isRetailWarehouse(warehouseId);
        destroySelect($("#cpr-unit"));
        $("#cpr-unit").html('<option value="">Pilih satuan</option>');

        if (!product) {
            setupLocalSelect("#cpr-unit", "Pilih satuan");
            return;
        }

        var units = product.units || [];
        if (retailOnly) {
            if (!product.retail_unit) {
                setupLocalSelect("#cpr-unit", "Produk tanpa satuan eceran");
                toastr.warning("Produk ini tidak punya satuan eceran; pilih gudang utama atau produk lain.");
                return;
            }
            units = units.filter(function (unit) {
                return parseInt(unit.unit_id, 10) === parseInt(product.retail_unit, 10);
            });
            if (!units.length) {
                units = [{
                    unit_id: product.retail_unit,
                    unit_name: "Satuan eceran",
                    unit_short_name: "Eceran",
                }];
            }
        }

        units.forEach(function (unit) {
            $("#cpr-unit").append(new Option(unit.unit_name || unit.unit_short_name, unit.unit_id));
        });
        setupLocalSelect(
            "#cpr-unit",
            retailOnly ? "Satuan eceran (wajib)" : "Pilih satuan",
        );

        if (retailOnly && product.retail_unit) {
            $("#cpr-unit").val(String(product.retail_unit)).trigger("change.select2");
        } else if (product.default_unit_id) {
            $("#cpr-unit").val(String(product.default_unit_id)).trigger("change.select2");
        } else if (units.length) {
            $("#cpr-unit").val(String(units[0].unit_id)).trigger("change.select2");
        }
    }

    function renderLines() {
        var html = "";
        cprLines.forEach(function (line, index) {
            html += "<tr>" +
                "<td class=\"fw-semibold\">" + esc(line.product_label) + "</td>" +
                "<td>" + esc(line.unit_name) + "</td>" +
                "<td>" + esc(line.qty) + "</td>" +
                "<td>" + esc(line.warehouse_name) + "</td>" +
                '<td class="text-center">' + (cprMode === "view" ? "—" :
                    '<a href="javascript:void(0);" class="btn-action-icon btn_delete cpr-remove-line" data-index="' + index + '" title="Hapus"><i class="fe fe-trash-2" style="font-size:14px;"></i></a>') +
                "</td></tr>";
        });
        if (!html) html = '<tr><td colspan="5" class="text-center text-muted py-4">Belum ada produk ditambahkan.</td></tr>';
        $("#cpr-lines").html(html);
        if (typeof feather !== "undefined") feather.replace();
    }

    function setCprModalLoading(isLoading) {
        $("#customer-product-return-modal").toggleClass("is-loading", !!isLoading);
    }

    function openRecord(id, mode) {
        cprMode = mode;
        setCprModalLoading(true);
        $("#customer-product-return-modal .modal-title").text(
            mode === "view" ? "Memuat detail pengembalian..." : "Memuat data pengembalian...",
        );
        $("#customer-product-return-modal").modal("show");

        $.get("/customerProductReturns/" + id)
            .done(function (record) {
                resetModal();
                cprMode = mode;
                $("#cpr-id").val(record.return_id);
                $("#cpr-date").val(String(record.return_date || "").slice(0, 10));
                $("#cpr-ref-number").val(record.ref_number || "");
                $("#cpr-notes").val(record.notes || "");
                applyContext(record.context || {});
                setCustomer(record.customer_id, record.customer_name);
                cprLines = (record.details || []).map(function (detail) {
                    return {
                        product_variant_id: parseInt(detail.product_variant_id, 10),
                        product_label: detail.product_label || detail.product_name || "-",
                        unit_id: parseInt(detail.unit_id, 10),
                        unit_name: detail.unit_name || detail.unit_short_name || "-",
                        warehouse_id: parseInt(detail.warehouse_id, 10),
                        warehouse_name: detail.warehouse_name,
                        qty: parseInt(detail.qty, 10),
                    };
                });
                renderLines();
                if (record.proof_url) {
                    cprExistingProofUrl = record.proof_url;
                    refreshProofState();
                }

                if (mode === "view") {
                    $("#customer-product-return-modal .modal-title").text(
                        "Detail Pengembalian Produk " + record.return_number,
                    );
                    $("#customer-product-return-modal input, #customer-product-return-modal textarea").prop("disabled", true);
                    $("#cpr-customer,#cpr-product,#cpr-unit,#cpr-warehouse").prop("disabled", true);
                    $("#cpr-btn-upload-proof").addClass("d-none");
                    $("#cpr-line-form,#cpr-save").addClass("d-none");
                    if (parseInt(record.status, 10) === 1 && can("others")) {
                        $("#cpr-accept,#cpr-decline").removeClass("d-none");
                    }
                } else {
                    $("#customer-product-return-modal .modal-title").text(
                        "Edit Pengembalian Produk " + record.return_number,
                    );
                    $("#cpr-save").text("Update Pengembalian");
                    $("#cpr-btn-upload-proof").removeClass("d-none");
                }
                setCprModalLoading(false);
            })
            .fail(function () {
                setCprModalLoading(false);
                $("#customer-product-return-modal").modal("hide");
                notifyError();
            });
    }

    function submitRecord() {
        var customerId = $("#cpr-customer").val();
        setSelectInvalid("#cpr-customer", !customerId);
        if (!$("#cpr-date").val() || !customerId || !cprLines.length) {
            toastr.error("Tanggal, Armada, dan minimal satu produk wajib diisi.");
            return;
        }
        var photos = cameraPhotos();
        var proofFile = $("#cpr-proof-file")[0].files[0];
        if (cprMode === "create" && !photos.length && !proofFile) {
            $("#cpr-btn-upload-proof").addClass("border-danger text-danger");
            toastr.error("Bukti foto wajib diunggah.");
            return;
        }
        var form = new FormData();
        form.append("_token", csrf());
        form.append("customer_id", customerId);
        form.append("return_date", $("#cpr-date").val());
        form.append("ref_number", $("#cpr-ref-number").val());
        form.append("notes", $("#cpr-notes").val());
        form.append("details", JSON.stringify(cprLines.map(function (line) {
            return {
                product_variant_id: line.product_variant_id,
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

        var id = $("#cpr-id").val();
        var url = cprMode === "edit" ? "/customerProductReturns/" + id : "/customerProductReturns";
        $("#cpr-save").prop("disabled", true);
        $.ajax({ url: url, method: "POST", data: form, processData: false, contentType: false })
            .done(function (response) {
                $("#customer-product-return-modal").modal("hide");
                toastr.success(response.message || "Pengembalian berhasil disimpan.");
                if (cprTable) cprTable.ajax.reload(null, false);
            })
            .fail(notifyError)
            .always(function () { $("#cpr-save").prop("disabled", false); });
    }

    function processRecord(id, action, question) {
        Swal.fire({
            icon: action === "accept" ? "question" : "warning",
            title: question,
            text: action === "accept" ? "Stok produk akan bertambah dan tindakan ini tidak dapat diulang." : "Penolakan tidak mengubah stok.",
            showCancelButton: true,
            confirmButtonText: "Ya, lanjutkan",
            cancelButtonText: "Batal",
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.post("/customerProductReturns/" + id + "/" + action, { _token: csrf() })
                .done(function (response) {
                    $("#customer-product-return-modal").modal("hide");
                    toastr.success(response.message);
                    if (cprTable) cprTable.ajax.reload(null, false);
                }).fail(notifyError);
        });
    }

    $(function () {
        $("#customer-product-return-modal").on("hidden.bs.modal", function () {
            setCprModalLoading(false);
        });
        setupCustomerSelect();
        $("#product-return-tab").on("shown.bs.tab", function () {
            $(".btnAdd").addClass("d-none");
            initTable();
            setActiveTableFilter("product-return");
            window.setTimeout(adjustTable, 0);
        });

        $("#cpr-add").on("click", function () {
            resetModal();
            $("#customer-product-return-modal").modal("show");
        });
        $("#cpr-customer").on("change", function () {
            setSelectInvalid("#cpr-customer", !$(this).val());
        });
        $("#cpr-product").on("change", function () {
            setSelectInvalid("#cpr-product", false);
            fillUnitOptions();
            $("#cpr-qty").val(selectedProduct() ? 1 : "");
        });
        $("#cpr-unit").on("change", function () {
            setSelectInvalid("#cpr-unit", false);
        });
        $("#cpr-warehouse").on("change", function () {
            setSelectInvalid("#cpr-warehouse", false);
            fillUnitOptions();
        });
        $("#cpr-qty").on("input", function () {
            $(this).removeClass("is-invalid");
        });
        $("#cpr-add-line").on("click", function () {
            var product = selectedProduct();
            var unitId = parseInt($("#cpr-unit").val(), 10);
            var warehouseId = parseInt($("#cpr-warehouse").val(), 10);
            var qty = parseInt($("#cpr-qty").val(), 10);
            setSelectInvalid("#cpr-product", !product);
            setSelectInvalid("#cpr-unit", !unitId);
            setSelectInvalid("#cpr-warehouse", !warehouseId);
            $("#cpr-qty").toggleClass("is-invalid", !qty || qty <= 0);
            if (!product || !unitId || !warehouseId || !qty || qty <= 0) {
                toastr.error("Pilih produk, satuan, qty positif, dan gudang.");
                return;
            }
            if (isRetailWarehouse(warehouseId)) {
                if (!product.retail_unit) {
                    toastr.error("Produk tidak punya satuan eceran; tidak bisa ke gudang eceran.");
                    setSelectInvalid("#cpr-unit", true);
                    return;
                }
                if (unitId !== parseInt(product.retail_unit, 10)) {
                    toastr.error("Gudang eceran wajib memakai satuan eceran (bukan DOS/jerigen).");
                    setSelectInvalid("#cpr-unit", true);
                    fillUnitOptions();
                    return;
                }
            }
            var keyMatch = function (line) {
                return line.product_variant_id === parseInt(product.product_variant_id, 10) &&
                    line.unit_id === unitId && line.warehouse_id === warehouseId;
            };
            var existing = cprLines.find(keyMatch);
            if (existing) {
                existing.qty += qty;
            } else {
                cprLines.push({
                    product_variant_id: parseInt(product.product_variant_id, 10),
                    product_label: product.product_label,
                    unit_id: unitId,
                    unit_name: $("#cpr-unit option:selected").text(),
                    warehouse_id: warehouseId,
                    warehouse_name: $("#cpr-warehouse option:selected").text(),
                    qty: qty,
                });
            }
            $("#cpr-product").val(null).trigger("change");
            $("#cpr-unit").val(null).trigger("change.select2");
            $("#cpr-qty").val("").removeClass("is-invalid");
            renderLines();
        });
        $(document).on("click", ".cpr-remove-line", function () {
            cprLines.splice(parseInt($(this).data("index"), 10), 1);
            renderLines();
        });
        $("#cpr-proof-file").on("change", function () {
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
            $("#cpr-proof-camera").val("");
            refreshProofState();
        });
        $("#cpr-btn-upload-proof").on("click", function () {
            rotationAngle = 0;
            camRotation = 0;
            photoData = "";
            modeCamera = 3;
            inputFile = "#cpr-proof-camera";
            cameraReturnModal = "#customer-product-return-modal";
            resetCameraModalUi();
            startCamera();
            $("#customer-product-return-modal").modal("hide");
            $("#modalPhoto").modal("show");
        });
        $(document).on("click", "#uploadBtn", function () {
            if (inputFile === "#cpr-proof-camera") {
                window.setTimeout(function () {
                    var photos = cameraPhotos();
                    if (photos.length) {
                        $("#cpr-proof-camera").val(JSON.stringify([photos[photos.length - 1]]));
                        $("#cpr-proof-file").val("");
                    }
                    refreshProofState();
                }, 0);
            }
        });
        $("#cpr-btn-view-proof").on("click", function () {
            var source = currentProofUrl();
            var file = $("#cpr-proof-file")[0].files[0];
            if (!source && file) source = URL.createObjectURL(file);
            if (!source) {
                toastr.error("Bukti foto belum tersedia.");
                return;
            }
            $("#cpr-proof-preview").attr("src", source);
            $("#cpr-proof-download").attr("href", source);
            $("#customer-product-return-modal").modal("hide");
            $("#cpr-photo-preview-modal").modal("show");
        });
        $("#cpr-photo-preview-modal").on("hidden.bs.modal", function () {
            $("#customer-product-return-modal").modal("show");
        });
        $("#cpr-save").on("click", submitRecord);
        $(document).on("click", ".cpr-view", function () { openRecord($(this).data("id"), "view"); });
        $(document).on("click", ".cpr-edit", function () { openRecord($(this).data("id"), "edit"); });
        $(document).on("click", ".cpr-delete", function () {
            var id = $(this).data("id");
            Swal.fire({ icon: "warning", title: "Hapus pengembalian produk?", showCancelButton: true, confirmButtonText: "Hapus" })
                .then(function (result) {
                    if (!result.isConfirmed) return;
                    $.post("/customerProductReturns/" + id + "/delete", { _token: csrf() })
                        .done(function (response) {
                            toastr.success(response.message);
                            cprTable.ajax.reload(null, false);
                        }).fail(notifyError);
                });
        });
        $("#cpr-accept").on("click", function () { processRecord($("#cpr-id").val(), "accept", "ACC pengembalian produk?"); });
        $("#cpr-decline").on("click", function () { processRecord($("#cpr-id").val(), "decline", "Tolak pengembalian produk?"); });
    });
})(jQuery);
