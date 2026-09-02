(function ($) {
    "use strict";

    var crTable = null;
    var supplyLines = [];
    var productLines = [];
    var crContext = null;
    var crMode = "create";
    var crExistingProofUrl = "";
    var crXhr = null;
    var crScanMode = false;

    function can(action) {
        return typeof hasAccessAction === "function" && hasAccessAction("Pengiriman", action);
    }

    function setCrModalMode(kind) {
        var $modal = $("#customer-return-modal");
        var $icon = $modal.find(".pg-modal-icon i");
        $modal.removeClass("pg-modal--form pg-modal--confirm");
        if (kind === "confirm") {
            $modal.addClass("pg-modal--confirm");
            $icon.attr("class", "fe fe-check-circle");
        } else {
            $modal.addClass("pg-modal--form");
            $icon.attr("class", "fe fe-rotate-ccw");
        }
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
        } else if (typeof toastr !== "undefined") {
            toastr.error(errorMessage(xhr));
        }
    }

    function statusBadge(status) {
        status = parseInt(status, 10);
        if (status === 1) {
            return '<span class="badge" style="background-color:#fff7ed;color:#ea580c;border:1px solid #ffedd5;padding:6px 12px;border-radius:20px;font-weight:600;font-size:12px;"><i class="fe fe-clock me-1"></i> Pending</span>';
        }
        if (status === 2) {
            return '<span class="badge" style="background-color:#dcfce7;color:#166534;border:1px solid #bbf7d0;padding:6px 12px;border-radius:20px;font-weight:600;font-size:12px;"><i class="fe fe-check-circle me-1"></i> Diterima</span>';
        }
        if (status === 3) {
            return '<span class="badge" style="background-color:#fee2e2;color:#991b1b;border:1px solid #fecaca;padding:6px 12px;border-radius:20px;font-weight:600;font-size:12px;"><i class="fe fe-x-circle me-1"></i> Ditolak</span>';
        }
        return "-";
    }

    function typeBadge(type) {
        if (type === "mixed") {
            return '<span class="badge" style="background:#f5f3ff;color:#6d28d9;border:1px solid #ddd6fe;padding:6px 10px;border-radius:20px;font-weight:600;">Campuran</span>';
        }
        if (type === "product") {
            return '<span class="badge" style="background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;padding:6px 10px;border-radius:20px;font-weight:600;">Produk Jadi</span>';
        }
        return '<span class="badge" style="background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;padding:6px 10px;border-radius:20px;font-weight:600;">Bahan Mentah</span>';
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
        if (!crTable) return;
        crTable.columns.adjust();
        if (crTable.responsive && typeof crTable.responsive.recalc === "function") {
            crTable.responsive.recalc();
        }
    }

    function setActiveTableFilter(active) {
        var $search = $(".search-input").first();
        if (!$search.length) return;
        $search.find(".dataTables_filter").not(".cr-table-filter").addClass("shipping-table-filter");
        $(".cr-table-filter").toggle(active === "customer-return");
        $(".shipping-table-filter").toggle(active === "shipping");
    }

    function setTableLoading(loading) {
        $("#tableCustomerReturn-wrap").toggleClass("is-loading", !!loading);
    }

    function refreshCustomerReturn() {
        initTable();
        if (!crTable) return;
        crTable.ajax.reload(function () {
            adjustTable();
        }, true);
    }

    function customerReturnAjax(data, callback) {
        if (crXhr && crXhr.readyState !== 4) crXhr.abort();
        crXhr = $.ajax({
            url: "/customerReturns",
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
        var status = parseInt(row.status, 10);
        var pending = status === 1;
        var canView = can("view");
        var canConfirm = pending && can("others");
        var canEdit = pending && can("edit");
        var canDelete = pending && can("delete");
        var key = esc(row.doc_key);

        if (canConfirm) {
            html += '<a class="btn-action-icon cr-confirm" data-key="' + key + '" href="javascript:void(0);" style="background:#ecfdf5;border:1px solid #a7f3d0;color:#059669;" data-bs-toggle="tooltip" title="Konfirmasi"><i class="fe fe-check-circle" style="font-size:14px;"></i></a>';
        } else if (canView) {
            html += '<a class="btn-action-icon cr-view" data-key="' + key + '" href="javascript:void(0);" style="background:#eff6ff;border:1px solid #bfdbfe;color:#2563eb;" data-bs-toggle="tooltip" title="Lihat"><i class="fe fe-eye" style="font-size:14px;"></i></a>';
        }
        if (status === 2) {
            html += '<a class="btn-action-icon cr-print" data-key="' + key + '" href="javascript:void(0);" style="background:#f8fafc;border:1px solid #cbd5e1;color:#334155;" data-bs-toggle="tooltip" title="Print"><i class="fe fe-printer" style="font-size:14px;"></i></a>';
        }
        if (canEdit) {
            html += '<a class="btn-action-icon cr-edit" data-key="' + key + '" href="javascript:void(0);" style="background:#fffbeb;border:1px solid #fde68a;color:#d97706;" data-bs-toggle="tooltip" title="Edit"><i class="fe fe-edit-2" style="font-size:14px;"></i></a>';
        }
        if (canDelete) {
            html += '<a class="btn-action-icon cr-delete" data-key="' + key + '" href="javascript:void(0);" style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;" data-bs-toggle="tooltip" title="Hapus"><i class="fe fe-trash-2" style="font-size:14px;"></i></a>';
        }
        html += "</div>";
        if (!canConfirm && !canView && !canEdit && !canDelete) {
            return '<span class="text-muted small">—</span>';
        }
        return html;
    }

    function initTable() {
        if (crTable || !$("#tableCustomerReturn").length) return;
        $("#tableCustomerReturn")
            .off(".dt.cr")
            .on("preXhr.dt.cr processing.dt.cr", function (_event, _settings, processing) {
                setTableLoading(processing !== false);
            })
            .on("xhr.dt.cr error.dt.cr", function () {
                setTableLoading(false);
            });
        crTable = $("#tableCustomerReturn").DataTable({
            processing: true,
            serverSide: true,
            deferRender: true,
            bFilter: true,
            sDom: "fBtlpi",
            searchDelay: 400,
            order: [[0, "desc"]],
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
                emptyTable: "Belum ada pengembalian",
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
                    width: "130px",
                    className: "text-center align-middle",
                    render: function (value, type) {
                        if (type !== "display") return value;
                        if (!value || value === "-") return "-";
                        return '<span class="badge" style="background:#f1f5f9;color:#334155;border:1px solid #cbd5e1;padding:6px 10px;">' + esc(value) + "</span>";
                    },
                },
                {
                    data: "return_date",
                    width: "145px",
                    className: "text-start align-middle",
                    render: function (value, type) {
                        if (type !== "display") return value;
                        if (!value || value === "-") return '<span style="color:#64748b;">-</span>';
                        var dateFmt = moment(value).format("D MMM YYYY");
                        return '<div style="display:flex;align-items:center;gap:10px;">' +
                            '<div style="width:32px;height:32px;border-radius:8px;background:#f8fafc;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;color:#64748b;flex-shrink:0;"><i class="fe fe-calendar"></i></div>' +
                            '<span class="fw-semibold text-dark">' + dateFmt + "</span></div>";
                    },
                },
                {
                    data: "return_type",
                    width: "130px",
                    className: "text-center align-middle",
                    render: function (value, type) {
                        if (type !== "display") return value;
                        return typeBadge(value);
                    },
                },
                {
                    data: "ref_number",
                    width: "130px",
                    className: "text-center align-middle",
                    render: function (value, type) {
                        if (type !== "display") return value || "";
                        if (!value) return "-";
                        return '<span class="badge" style="background:#f8fafc;color:#475569;border:1px solid #e2e8f0;padding:6px 10px;">' + esc(value) + "</span>";
                    },
                },
                {
                    data: "customer_name",
                    width: "180px",
                    className: "text-start align-middle",
                    render: function (data, type) {
                        if (type !== "display") return data;
                        if (!data || data === "-") return '<span style="color:#64748b;">-</span>';
                        return '<div style="display:flex;align-items:center;gap:10px;">' +
                            '<div style="width:32px;height:32px;border-radius:8px;background:#eff6ff;border:1px solid #bfdbfe;display:flex;align-items:center;justify-content:center;color:#2563eb;flex-shrink:0;"><i class="fe fe-truck"></i></div>' +
                            '<span class="fw-semibold text-dark">' + esc(data) + "</span></div>";
                    },
                },
                { data: "status", className: "text-center align-middle", width: "120px", render: statusBadge },
                {
                    data: "created_by_name",
                    defaultContent: "-",
                    width: "150px",
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
                    width: "160px",
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
                var $filter = $("#tableCustomerReturn_wrapper .dataTables_filter");
                $filter.addClass("cr-table-filter").appendTo($(".search-input").first());
                if (!$filter.find("label .fa-search").length) {
                    $filter.find("label").prepend('<i class="fa fa-search"></i> ');
                }
                $("#tableCustomerReturn-wrap").removeClass("dt-pending").addClass("dt-ready");
                setTableLoading(false);
                setActiveTableFilter("customer-return");
                adjustTable();
            },
        });
    }

    function setupCustomerSelect() {
        var $select = $("#cr-customer");
        if ($select.hasClass("select2-hidden-accessible")) $select.select2("destroy");
        autocompleteCustomer("#cr-customer", "#customer-return-modal .modal-content");
    }

    function setCustomer(customerId, customerName) {
        var $select = $("#cr-customer");
        $select.empty();
        if (customerId) {
            $select.append(new Option(customerName || "-", customerId, true, true));
        }
        $select.trigger("change.select2");
        setSelectInvalid("#cr-customer", false);
    }

    function destroySelect($select) {
        if ($select.hasClass("select2-hidden-accessible")) $select.select2("destroy");
    }

    function setupLocalSelect(selector, placeholder) {
        var $select = $(selector);
        destroySelect($select);
        $select.select2({
            width: "100%",
            dropdownParent: $("#customer-return-modal"),
            placeholder: placeholder,
            allowClear: true,
        });
    }

    function setSelectInvalid(selector, invalid) {
        $(selector).next(".select2-container").find(".select2-selection")
            .toggleClass("is-invalids", !!invalid);
    }

    function crActiveWarehouseId() {
        if (typeof getActiveWarehouseId === "function") {
            var live = getActiveWarehouseId();
            if (live) return parseInt(live, 10);
        }
        var hidden = parseInt($("#cr-active-warehouse-id").val(), 10);
        if (hidden > 0) return hidden;
        if (crContext && crContext.active_warehouse) {
            return parseInt(crContext.active_warehouse.id, 10);
        }
        return 0;
    }

    function crMainWarehouse() {
        var warehouses = (crContext && crContext.supply_warehouses) || [];
        if (warehouses.length) {
            var activeId = crActiveWarehouseId();
            var activeMain = warehouses.find(function (warehouse) {
                return parseInt(warehouse.id, 10) === activeId;
            });
            return activeMain || warehouses[0];
        }
        if (typeof getMainWarehouseId === "function" && typeof getMainWarehouseName === "function") {
            var domId = parseInt(getMainWarehouseId(), 10);
            var domName = getMainWarehouseName();
            if (domId > 0 && domName) {
                return { id: domId, warehouse_name: domName };
            }
        }
        return null;
    }

    function crMainWarehouseName() {
        var main = crMainWarehouse();
        return main ? main.warehouse_name : "Gudang utama";
    }

    function isRetailUnit(product, unitId) {
        var retailUnitId = parseInt(product && product.retail_unit || 0, 10);
        return retailUnitId > 0 && parseInt(unitId, 10) === retailUnitId;
    }

    function isRetailProductLine(line) {
        return isRetailUnit(line, line && line.unit_id);
    }

    function updateProductRetailUnit(variantId, retailUnitId) {
        if (!crContext || !Array.isArray(crContext.products)) return;
        crContext.products.forEach(function (product) {
            if (parseInt(product.product_variant_id, 10) === parseInt(variantId, 10)) {
                product.retail_unit = parseInt(retailUnitId, 10) || null;
            }
        });
    }

    function resolveProductDestination(product, unitId) {
        if (!product || !unitId) {
            return null;
        }
        if (isRetailUnit(product, unitId)) {
            var activeId = crActiveWarehouseId();
            if (isRetailWarehouse(activeId)) {
                return { id: activeId, name: crActiveWarehouseName() };
            }
            var main = crMainWarehouse();
            if (!main) {
                return { error: "Gudang utama tidak ditemukan." };
            }
            return {
                id: parseInt(main.id, 10),
                name: main.warehouse_name,
                needsRetailDest: true,
            };
        }
        var mainWh = crMainWarehouse();
        if (!mainWh) {
            return { error: "Gudang utama tidak ditemukan." };
        }
        return { id: parseInt(mainWh.id, 10), name: mainWh.warehouse_name };
    }

    function crActiveWarehouseName() {
        if (typeof getActiveWarehouseName === "function") {
            var live = getActiveWarehouseName();
            if (live) return live;
        }
        if (crContext && crContext.active_warehouse) {
            return crContext.active_warehouse.warehouse_name || "Gudang aktif";
        }
        return "Gudang aktif";
    }

    function warehouseMetaById(warehouseId) {
        var id = parseInt(warehouseId, 10);
        if (!id || !crContext) return null;
        if (crContext.active_warehouse && parseInt(crContext.active_warehouse.id, 10) === id) {
            return crContext.active_warehouse;
        }
        return (crContext.product_warehouses || []).find(function (warehouse) {
            return parseInt(warehouse.id, 10) === id;
        }) || (crContext.supply_warehouses || []).find(function (warehouse) {
            return parseInt(warehouse.id, 10) === id;
        }) || null;
    }

    function syncCrActiveWarehouseBadge() {
        var $badge = $("#cr-active-warehouse-badge");
        if (!$badge.length) return;
        var label = crMainWarehouseName();
        if (crAddItemType() === "product") {
            var product = selectedProduct();
            var unitId = parseInt($("#cr-product-unit").val(), 10);
            if (product && unitId) {
                var dest = resolveProductDestination(product, unitId);
                if (dest && !dest.error) {
                    label = dest.name;
                }
            } else if (isRetailWarehouse(crActiveWarehouseId())) {
                label = crActiveWarehouseName();
            }
        }
        $badge.find("span").text("Tujuan: " + label);
    }

    function cameraPhotos() {
        var value = $("#cr-proof-camera").val();
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
        return cameraPhotos()[0] || crExistingProofUrl || "";
    }

    function refreshProofState() {
        var hasProof = !!currentProofUrl() || !!$("#cr-proof-file")[0].files.length;
        $("#cr-check-foto").toggleClass("d-none", !hasProof);
        $("#cr-btn-view-proof").toggleClass("d-none", !hasProof);
        $("#cr-btn-upload-proof").removeClass("border-danger text-danger");
    }

    function applyContext(context) {
        crContext = context || {};
        var $supply = $("#cr-supply").empty().append(new Option("Pilih bahan / kemasan", ""));
        (crContext.supplies || []).forEach(function (supply) {
            $supply.append(new Option(supply.supplies_name, supply.supplies_id));
        });
        setupLocalSelect("#cr-supply", "Cari bahan / kemasan");

        var $product = $("#cr-product").empty().append(new Option("Pilih produk / varian", ""));
        (crContext.products || []).forEach(function (product) {
            $product.append(new Option(product.product_label, product.product_variant_id));
        });
        setupLocalSelect("#cr-product", "Cari produk / varian");

        $("#cr-supply-unit,#cr-product-unit").html('<option value="">Pilih satuan</option>');
        setupLocalSelect("#cr-supply-unit", "Pilih satuan");
        setupLocalSelect("#cr-product-unit", "Pilih satuan");
        fillQcStaffOptions();
        syncCrRetailCreateMode();
    }

    /** Gudang eceran: hanya produk jadi satuan retail — sembunyikan tab bahan mentah. */
    function syncCrRetailCreateMode() {
        var retailOnly = isRetailWarehouse(crActiveWarehouseId());
        var editable = crMode === "create" || crMode === "edit";
        $("#cr-type-supply").closest(".nav-item").toggleClass("d-none", retailOnly);
        if (retailOnly && editable && crAddItemType() === "supply") {
            setCrAddItemType("product");
        }
        syncCrActiveWarehouseBadge();
    }

    function fillQcStaffOptions(selectedId, selectedName) {
        var $sel = $("#cr-qc-staff");
        var current = selectedId || $sel.val();
        destroySelect($sel);
        $sel.empty().append(new Option("Pilih Staff QC", ""));
        var rows = (crContext && crContext.qc_staff) || [];
        var seen = {};
        rows.forEach(function (row) {
            var id = String(row.id);
            seen[id] = true;
            $sel.append(new Option(row.text, row.id));
        });
        if (current && !seen[String(current)] && selectedName) {
            $sel.append(new Option(selectedName, current));
        }
        setupLocalSelect("#cr-qc-staff", "Pilih Staff QC");
        if (current) {
            $sel.val(String(current)).trigger("change.select2");
        }
        setSelectInvalid("#cr-qc-staff", false);
    }

    function loadContext(done) {
        $.get("/customerReturns/context")
            .done(function (context) {
                applyContext(context);
                if (typeof done === "function") done(context);
            })
            .fail(notifyError);
    }

    function lineTypeBadge(type) {
        if (type === "product") {
            return '<span class="badge" style="background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;padding:4px 8px;border-radius:6px;font-size:10px;font-weight:600;">Produk Jadi</span>';
        }
        return '<span class="badge" style="background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;padding:4px 8px;border-radius:6px;font-size:10px;font-weight:600;">Bahan Mentah</span>';
    }

    function crAddItemType() {
        return $('input[name="cr-item-type"]:checked').val() || "supply";
    }

    function setCrProductScanMode(on) {
        crScanMode = !!on && crAddItemType() === "product";
        var $toggle = $("#btn_toggle_scan_cr");
        if (crScanMode) {
            $("#cr_mode_select_product").hide();
            $("#cr_mode_scan_product").show();
            $("#cr-field-product-qty,#cr-field-product-unit,#cr-field-add-btn").addClass("d-none");
            $("#cr-field-product").removeClass("col-lg-5").addClass("col-lg-10");
            $toggle
                .html('<i class="fa fa-list me-1"></i> Mode Input')
                .removeClass("btn-outline-secondary")
                .addClass("btn-outline-primary");
            window.setTimeout(function () {
                $("#cr_scan_barcode").focus();
            }, 50);
            return;
        }
        $("#cr_mode_scan_product").hide();
        $("#cr_mode_select_product").show();
        $("#cr-field-product").removeClass("col-lg-10").addClass("col-lg-5");
        $toggle
            .html('<i class="fa fa-barcode me-1"></i> Mode Scan')
            .removeClass("btn-outline-primary")
            .addClass("btn-outline-secondary");
        if (crAddItemType() === "product") {
            $("#cr-field-product-qty,#cr-field-product-unit,#cr-field-add-btn").removeClass("d-none");
        } else {
            $("#cr-field-add-btn").removeClass("d-none");
        }
    }

    function doScanAddCr() {
        if (crMode === "view") return;
        var barcode = ($("#cr_scan_barcode").val() || "").trim();
        var qty = parseInt($("#cr_scan_qty").val(), 10) || 1;
        if (qty < 1) qty = 1;

        if (!barcode) {
            if (typeof toastr !== "undefined") {
                toastr.warning("Masukkan barcode/SKU terlebih dahulu");
            }
            return;
        }
        if (!crContext || !Array.isArray(crContext.products)) {
            if (typeof toastr !== "undefined") {
                toastr.error("Data produk belum siap. Tunggu sebentar.");
            }
            return;
        }

        $.ajax({
            url: "/searchProductVariantByScan",
            method: "post",
            data: {
                keyword: barcode,
                _token: csrf(),
            },
            success: function (res) {
                var results = res.data || [];
                if (!results.length) {
                    if (typeof toastr !== "undefined") {
                        toastr.error("Produk tidak ditemukan untuk barcode: " + barcode);
                    }
                    $("#cr_scan_barcode").val("").focus();
                    return;
                }

                var scanned = results[0];
                var variantId = parseInt(scanned.product_variant_id, 10);
                var product = (crContext.products || []).find(function (row) {
                    return parseInt(row.product_variant_id, 10) === variantId;
                });
                if (!product) {
                    if (typeof toastr !== "undefined") {
                        toastr.error("Produk tidak tersedia untuk pengembalian.");
                    }
                    $("#cr_scan_barcode").val("").focus();
                    return;
                }

                $("#cr-product").val(String(variantId)).trigger("change");
                $("#cr-product-qty").val(qty);
                var added = addProductLine();
                if (added && typeof toastr !== "undefined") {
                    toastr.success(
                        "Berhasil menambahkan: " +
                            (product.product_label || scanned.pr_name || "produk") +
                            " (x" +
                            qty +
                            ")",
                    );
                }
                $("#cr_scan_barcode").val("").focus();
                $("#cr_scan_qty").val(1);
            },
            error: function (xhr) {
                if (typeof handlePermissionError === "function" && handlePermissionError(xhr)) return;
                if (typeof toastr !== "undefined") {
                    toastr.error("Gagal mencari produk");
                }
                $("#cr_scan_barcode").val("").focus();
            },
        });
    }

    function setCrAddItemType(type) {
        type = type === "product" ? "product" : "supply";
        var isSupply = type === "supply";
        $("#cr-type-" + type).prop("checked", true);
        if (isSupply) {
            setCrProductScanMode(false);
        }
        $("#cr-field-supply,#cr-field-supply-qty,#cr-field-supply-unit").toggleClass("d-none", !isSupply);
        $("#cr-field-product,#cr-field-product-qty,#cr-field-product-unit").toggleClass("d-none", isSupply);
        $("#cr-field-add-btn").removeClass("d-none");
        if (!isSupply && crScanMode) {
            setCrProductScanMode(true);
        }
        var $btn = $("#cr-add-item");
        if (isSupply) {
            $btn.css("background", "linear-gradient(135deg,#3b82f6,#2563eb)");
        } else {
            $btn.css("background", "linear-gradient(135deg,#22c55e,#16a34a)");
        }
    }

    function updateCounts() {
        $("#cr-total-count").text((supplyLines.length + productLines.length) + " item");
    }

    function qtyCell(type, index, qty, unitName) {
        if (crMode === "view") {
            return esc(qty) + (unitName ? " " + esc(unitName) : "");
        }
        return '<div class="input-group input-group-sm" style="width: 140px; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">' +
            '<input type="number" min="0" step="1" class="form-control cr-line-qty text-center" ' +
            'data-type="' + type + '" data-index="' + index + '" value="' + esc(qty) + '" ' +
            'style="border-top-left-radius:6px;border-bottom-left-radius:6px;border-right:0;height:32px;padding:2px 6px;">' +
            '<span class="input-group-text bg-light text-muted" style="border-top-right-radius:6px;border-bottom-right-radius:6px;height:32px;font-size:11px;padding: 0 8px;">' + esc(unitName) + '</span>' +
            '</div>';
    }

    function lineActionCell(type, index, editable) {
        if (!editable) {
            return '<td class="text-center text-muted">—</td>';
        }
        return '<td class="text-center">' +
            '<a href="javascript:void(0);" class="btn-action-icon cr-remove-line" data-type="' + type + '" data-index="' + index + '" title="Hapus">' +
            '<i class="fe fe-trash-2" style="font-size:14px;"></i></a></td>';
    }

    function removeLineAt(type, index, $row) {
        if ($row && $row.length) {
            $row.css({ transition: "opacity .2s ease", opacity: 0 });
            window.setTimeout(function () {
                if (type === "supply") supplyLines.splice(index, 1);
                else productLines.splice(index, 1);
                renderAllLines();
            }, 200);
            return;
        }
        if (type === "supply") supplyLines.splice(index, 1);
        else productLines.splice(index, 1);
        renderAllLines();
    }

    function renderAllLines() {
        var html = "";
        var editable = crMode !== "view";
        supplyLines.forEach(function (line, index) {
            html += "<tr" + (editable ? "" : "") + ">" +
                '<td class="px-4">' + lineTypeBadge("supply") + "</td>" +
                '<td class="fw-semibold" style="max-width:160px;white-space:normal;">' + esc(line.supplies_name) + '</td>' +
                "<td>" + qtyCell("supply", index, line.qty, line.unit_name) + "</td>" +
                '<td class="px-4">' + esc(line.warehouse_name) + "</td>" +
                lineActionCell("supply", index, editable) +
                "</tr>";
        });
        productLines.forEach(function (line, index) {
            html += "<tr>" +
                '<td class="px-4">' + lineTypeBadge("product") + "</td>" +
                '<td class="fw-semibold" style="max-width:160px;white-space:normal;">' + esc(line.product_label) + '</td>' +
                "<td>" + qtyCell("product", index, line.qty, line.unit_name) + "</td>" +
                '<td class="px-4">' + productWarehouseCell(line, index, editable) + "</td>" +
                lineActionCell("product", index, editable) +
                "</tr>";
        });
        if (!html) {
            html = '<tr><td colspan="5" class="text-center text-muted py-3">Belum ada item. Tambahkan dari form di atas.</td></tr>';
        }
        $("#cr-all-lines").html(html);
        updateCounts();
        initCrRetailWarehouseSelects();
        syncCrSaveEnabled();
        if (typeof feather !== "undefined") feather.replace();
    }

    function productWarehouseCell(line, index, editable) {
        if (!isRetailProductLine(line)) {
            return '<span class="cr-main-warehouse"><i class="fe fe-home"></i> ' + esc(line.warehouse_name || crMainWarehouseName()) + "</span>";
        }
        if (isRetailWarehouse(line.warehouse_id)) {
            return '<span class="cr-retail-warehouse-locked"><i class="fe fe-map-pin me-1"></i>' +
                esc(line.warehouse_name || crActiveWarehouseName()) + "</span>";
        }
        if (!editable) {
            return esc(line.destination_warehouse_name || line.warehouse_name || "Gudang eceran");
        }
        var selected = parseInt(line.destination_warehouse_id || 0, 10);
        var label = esc(line.destination_warehouse_name || (selected ? ("Gudang #" + selected) : ""));
        if (selected && isRetailWarehouse(selected)) {
            return '<span class="cr-retail-warehouse-locked"><i class="fe fe-map-pin me-1"></i>' +
                (label || esc(crActiveWarehouseName())) + "</span>";
        }
        return '<select class="form-select form-select-sm cr-retail-warehouse" id="cr_retail_wh_' + index + '" data-index="' + index + '">' +
            (selected ? '<option value="' + selected + '" selected>' + label + "</option>" : "") +
            "</select>";
    }

    function markCrRetailWarehouseSelect($select, invalid) {
        $select.toggleClass("is-invalid", !!invalid);
        $select.next(".select2-container").find(".select2-selection")
            .toggleClass("is-invalids", !!invalid);
    }

    function initCrRetailWarehouseSelects() {
        $("#customer-return-modal .cr-retail-warehouse").each(function () {
            var $select = $(this);
            var index = parseInt($select.data("index"), 10);
            var selector = "#" + $select.attr("id");
            if (typeof autocompleteWarehouse === "function") {
                autocompleteWarehouse(selector, "#customer-return-modal", {
                    retailOnly: true,
                    placeholder: "Pilih gudang eceran",
                });
            }
            var line = productLines[index];
            var missing = !line || !parseInt(line.destination_warehouse_id || 0, 10);
            markCrRetailWarehouseSelect($select, missing);
        });
    }

    function missingRetailDestinations() {
        return productLines.some(function (line) {
            return (
                isRetailProductLine(line) &&
                !isRetailWarehouse(line.warehouse_id) &&
                !parseInt(line.destination_warehouse_id || 0, 10)
            );
        });
    }

    function syncCrSaveEnabled() {
        var $btn = $("#cr-save");
        if (!$btn.length || $btn.hasClass("d-none") || crMode === "view") return;
        $btn.prop("disabled", missingRetailDestinations());
    }

    function resetModal() {
        crMode = "create";
        supplyLines = [];
        productLines = [];
        crContext = null;
        crExistingProofUrl = "";
        $("#cr-doc-key,#cr-ref-number,#cr-notes,#cr-supply-qty,#cr-product-qty").val("");
        $("#cr-date").val(new Date().toISOString().slice(0, 10));
        $("#cr-proof-camera,#cr-proof-file").val("");
        $("#cr-check-foto,#cr-btn-view-proof").addClass("d-none");
        $("#cr-proof-preview").attr("src", "");
        $("#cr-proof-download").attr("href", "");
        destroySelect($("#cr-customer"));
        $("#cr-customer").empty().prop("disabled", false);
        setSelectInvalid("#cr-customer", false);
        destroySelect($("#cr-qc-staff"));
        $("#cr-qc-staff").empty().append(new Option("Pilih Staff QC", "")).prop("disabled", false);
        setSelectInvalid("#cr-qc-staff", false);
        ["#cr-supply", "#cr-supply-unit", "#cr-product", "#cr-product-unit"].forEach(function (selector) {
            destroySelect($(selector));
            $(selector).empty().prop("disabled", false);
            setSelectInvalid(selector, false);
        });
        $("#cr-supply-qty,#cr-product-qty").removeClass("is-invalid");
        $("#cr-add-strip,#cr-save").removeClass("d-none");
        $("#cr-save").text("Simpan").prop("disabled", false);
        $("#cr-btn-upload-proof").removeClass("d-none border-danger text-danger");
        $("#cr-accept,#cr-decline,#cr-print").addClass("d-none");
        $("#cr_scan_barcode").val("");
        $("#cr_scan_qty").val(1);
        setCrProductScanMode(false);
        setCrAddItemType("supply");
        setCrModalMode("form");
        $("#customer-return-modal .modal-title").text("Tambah Pengembalian");
        $("#customer-return-modal input, #customer-return-modal textarea").prop("readonly", false).prop("disabled", false);
        setupCustomerSelect();
        renderAllLines();
        loadContext();
    }

    function selectedSupply() {
        if (!crContext) return null;
        var id = parseInt($("#cr-supply").val(), 10);
        return (crContext.supplies || []).find(function (supply) {
            return parseInt(supply.supplies_id, 10) === id;
        }) || null;
    }

    function selectedProduct() {
        if (!crContext) return null;
        var id = parseInt($("#cr-product").val(), 10);
        return (crContext.products || []).find(function (product) {
            return parseInt(product.product_variant_id, 10) === id;
        }) || null;
    }

    function isRetailWarehouse(warehouseId) {
        var id = parseInt(warehouseId, 10) || crActiveWarehouseId();
        if (!id) return false;
        var warehouse = warehouseMetaById(id);
        return !!warehouse && parseInt(warehouse.is_main_warehouse, 10) === 0;
    }

    function retailUnitLabel(product) {
        if (!product || !product.retail_unit) return "satuan eceran";
        var match = (product.units || []).find(function (unit) {
            return parseInt(unit.unit_id, 10) === parseInt(product.retail_unit, 10);
        });
        return (match && (match.unit_name || match.unit_short_name)) || "satuan eceran";
    }

    function fillProductUnitOptions() {
        var product = selectedProduct();
        var retailOnly = isRetailWarehouse(crActiveWarehouseId());
        destroySelect($("#cr-product-unit"));
        $("#cr-product-unit").html('<option value="">Pilih satuan</option>');

        if (!product) {
            setupLocalSelect("#cr-product-unit", "Pilih satuan");
            return;
        }

        var units = product.units || [];
        if (retailOnly) {
            if (!product.retail_unit) {
                setupLocalSelect("#cr-product-unit", "Atur satuan eceran dulu");
                promptRetailUnitSetup(product);
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
            $("#cr-product-unit").append(new Option(unit.unit_name || unit.unit_short_name, unit.unit_id));
        });
        setupLocalSelect(
            "#cr-product-unit",
            retailOnly ? "Satuan eceran (wajib)" : "Pilih satuan",
        );

        if (retailOnly && product.retail_unit) {
            $("#cr-product-unit").val(String(product.retail_unit)).trigger("change.select2");
        } else if (product.default_unit_id) {
            $("#cr-product-unit").val(String(product.default_unit_id)).trigger("change.select2");
        } else if (units.length) {
            $("#cr-product-unit").val(String(units[0].unit_id)).trigger("change.select2");
        }
        syncCrActiveWarehouseBadge();
    }

    function promptRetailUnitSetup(product) {
        if (!product || typeof Swal === "undefined") return;
        var main = crMainWarehouse();
        var retailId = crActiveWarehouseId();
        if (!main || !isRetailWarehouse(retailId)) {
            if (typeof toastr !== "undefined") {
                toastr.warning("Pilih gudang eceran di menu atas untuk mengatur satuan eceran produk.");
            }
            return;
        }
        $.post("/getTransferRetailUnitSetup", {
            product_variant_id: product.product_variant_id,
            from_warehouse_id: main.id,
            to_warehouse_id: retailId,
            _token: csrf(),
        }).done(function (res) {
            if (!res || res.status !== 1) {
                if (typeof toastr !== "undefined") {
                    toastr.error((res && res.message) || "Gagal memeriksa satuan eceran");
                }
                return;
            }
            if (!res.requires_setup) {
                updateProductRetailUnit(product.product_variant_id, res.retail_unit_id);
                fillProductUnitOptions();
                return;
            }
            var units = res.units || [];
            if (!units.length) {
                if (typeof toastr !== "undefined") {
                    toastr.error(res.message || "Satuan produk tidak tersedia untuk dijadikan satuan eceran.");
                }
                return;
            }
            var options = {};
            units.forEach(function (unit) {
                options[String(unit.unit_id)] = unit.unit_name || unit.unit_short_name || "-";
            });
            Swal.fire({
                icon: "warning",
                title: "Satuan eceran belum diatur",
                html: "Pilih satuan eceran untuk <strong>" + esc(product.product_label || "produk ini") + "</strong>.",
                input: "select",
                inputOptions: options,
                inputPlaceholder: "Pilih satuan eceran",
                showCancelButton: true,
                confirmButtonText: "Simpan",
                cancelButtonText: "Batal",
                allowOutsideClick: false,
                inputValidator: function (value) {
                    return value ? undefined : "Satuan eceran wajib dipilih";
                },
                preConfirm: function (unitId) {
                    return $.post("/saveTransferRetailUnit", {
                        product_variant_id: product.product_variant_id,
                        unit_id: unitId,
                        from_warehouse_id: main.id,
                        to_warehouse_id: retailId,
                        _token: csrf(),
                    }).then(function (saveRes) {
                        if (!saveRes || saveRes.status !== 1) {
                            throw new Error((saveRes && saveRes.message) || "Gagal menyimpan satuan eceran");
                        }
                        return saveRes;
                    }).catch(function (xhr) {
                        Swal.showValidationMessage(
                            (xhr.responseJSON && xhr.responseJSON.message) ||
                            xhr.message ||
                            "Gagal menyimpan satuan eceran",
                        );
                    });
                },
            }).then(function (result) {
                if (!result.isConfirmed || !result.value) return;
                updateProductRetailUnit(product.product_variant_id, result.value.retail_unit_id);
                fillProductUnitOptions();
            });
        }).fail(notifyError);
    }

    function promptRetailUnitFix(product) {
        var label = retailUnitLabel(product);
        Swal.fire({
            icon: "warning",
            title: "Satuan tidak sesuai gudang eceran",
            html: "Gudang eceran wajib memakai <strong>" + esc(label) + "</strong>, bukan satuan besar (DOS/jerigen).",
            showCancelButton: true,
            confirmButtonText: "Pakai satuan eceran",
            cancelButtonText: "Batal",
        }).then(function (result) {
            if (!result.isConfirmed) return;
            fillProductUnitOptions();
            setSelectInvalid("#cr-product-unit", false);
            window.setTimeout(function () {
                $("#cr-product-unit").select2("open");
            }, 150);
        });
    }

    function setCrModalLoading(isLoading) {
        $("#customer-return-modal").toggleClass("is-loading", !!isLoading);
    }

    function openRecord(key, mode) {
        crMode = mode;
        setCrModalLoading(true);
        $("#customer-return-modal .modal-title").text(
            mode === "view" ? "Memuat detail pengembalian..." : "Memuat data pengembalian...",
        );
        $("#customer-return-modal").modal("show");

        $.get("/customerReturns/" + encodeURIComponent(key))
            .done(function (record) {
                resetModal();
                crMode = mode;
                $("#cr-doc-key").val(record.doc_key);
                $("#cr-date").val(String(record.return_date || "").slice(0, 10));
                $("#cr-ref-number").val(record.ref_number || "");
                $("#cr-notes").val(record.notes || "");
                applyContext(record.context || {});
                fillQcStaffOptions(record.qc_staff_id, record.qc_staff_name);
                setCustomer(record.customer_id, record.customer_name);
                supplyLines = (record.supply_details || []).map(function (detail) {
                    return {
                        supplies_id: parseInt(detail.supplies_id, 10),
                        supplies_name: detail.supplies_name,
                        unit_id: parseInt(detail.unit_id, 10),
                        unit_name: detail.unit_name || detail.unit_short_name || "-",
                        warehouse_id: parseInt(detail.warehouse_id, 10),
                        warehouse_name: detail.warehouse_name,
                        qty: parseInt(detail.qty, 10),
                    };
                });
                productLines = (record.product_details || []).map(function (detail) {
                    var retailUnit = parseInt(detail.retail_unit, 10) || 0;
                    var warehouseId = parseInt(detail.warehouse_id, 10);
                    var destId = parseInt(detail.destination_warehouse_id, 10) || 0;
                    var destName = detail.destination_warehouse_name || "";
                    var warehouseName = detail.warehouse_name;
                    return {
                        product_variant_id: parseInt(detail.product_variant_id, 10),
                        product_label: detail.product_label,
                        unit_id: parseInt(detail.unit_id, 10),
                        unit_name: detail.unit_name || detail.unit_short_name || "-",
                        warehouse_id: warehouseId,
                        warehouse_name: warehouseName,
                        retail_unit: retailUnit || null,
                        destination_warehouse_id: destId || null,
                        destination_warehouse_name: destName,
                        qty: parseInt(detail.qty, 10),
                    };
                });
                renderAllLines();
                if (record.proof_url) {
                    crExistingProofUrl = record.proof_url;
                    refreshProofState();
                }

                var number = record.return_number || key;
                if (mode === "confirm") {
                    $("#customer-return-modal .modal-title").text("Detail Pengembalian " + number);
                    $("#customer-return-modal input, #customer-return-modal textarea").prop("disabled", true);
                    $("#cr-customer,#cr-qc-staff,#cr-supply,#cr-supply-unit,#cr-product,#cr-product-unit").prop("disabled", true);
                    $("#cr-btn-upload-proof").addClass("d-none");
                    $("#cr-add-strip,#cr-save").addClass("d-none");
                    $("#cr-print").toggleClass("d-none", parseInt(record.status, 10) !== 2);
                    if (parseInt(record.status, 10) === 1 && can("others")) {
                        $("#cr-accept,#cr-decline").removeClass("d-none");
                        setCrModalMode("confirm");
                        $("#customer-return-modal .modal-title").text("Konfirmasi Pengembalian " + number);
                    } else {
                        $("#cr-accept,#cr-decline").addClass("d-none");
                        setCrModalMode("form");
                    }
                } else if (mode === "view") {
                    $("#customer-return-modal .modal-title").text("Detail Pengembalian " + number);
                    $("#customer-return-modal input, #customer-return-modal textarea").prop("disabled", true);
                    $("#cr-customer,#cr-qc-staff,#cr-supply,#cr-supply-unit,#cr-product,#cr-product-unit").prop("disabled", true);
                    $("#cr-btn-upload-proof").addClass("d-none");
                    $("#cr-add-strip,#cr-save").addClass("d-none");
                    $("#cr-accept,#cr-decline").addClass("d-none");
                    $("#cr-print").toggleClass("d-none", parseInt(record.status, 10) !== 2);
                    setCrModalMode("form");
                } else {
                    $("#customer-return-modal .modal-title").text("Edit Pengembalian " + number);
                    $("#cr-save").text("Update");
                    $("#cr-btn-upload-proof").removeClass("d-none");
                }
                setCrModalLoading(false);
            })
            .fail(function (xhr) {
                setCrModalLoading(false);
                $("#customer-return-modal").modal("hide");
                notifyError(xhr);
            });
    }

    function submitRecord() {
        var customerId = $("#cr-customer").val();
        var qcStaffId = $("#cr-qc-staff").val();
        setSelectInvalid("#cr-customer", !customerId);
        setSelectInvalid("#cr-qc-staff", !qcStaffId);
        if (!$("#cr-date").val() || !customerId || !qcStaffId || (!supplyLines.length && !productLines.length)) {
            if (typeof toastr !== "undefined") {
                toastr.error("Tanggal, Armada, Staff QC, dan minimal satu item wajib diisi.");
            }
            return;
        }
        var photos = cameraPhotos();
        var proofFile = $("#cr-proof-file")[0].files[0];
        if (crMode === "create" && !photos.length && !proofFile) {
            $("#cr-btn-upload-proof").addClass("border-danger text-danger");
            if (typeof toastr !== "undefined") toastr.error("Bukti foto wajib diunggah.");
            return;
        }
        if (missingRetailDestinations()) {
            $("#customer-return-modal .cr-retail-warehouse").each(function () {
                markCrRetailWarehouseSelect($(this), !$(this).val());
            });
            if (typeof toastr !== "undefined") {
                toastr.error("Pilih gudang eceran pada setiap produk satuan eceran.");
            }
            return;
        }
        var form = new FormData();
        form.append("_token", csrf());
        form.append("customer_id", customerId);
        form.append("qc_staff_id", qcStaffId);
        form.append("return_date", $("#cr-date").val());
        form.append("ref_number", $("#cr-ref-number").val());
        form.append("notes", $("#cr-notes").val());
        form.append("supply_details", JSON.stringify(supplyLines.filter(function (line) {
            return line.qty > 0;
        }).map(function (line) {
            return {
                supplies_id: line.supplies_id,
                unit_id: line.unit_id,
                warehouse_id: line.warehouse_id,
                qty: line.qty,
            };
        })));
        form.append("product_details", JSON.stringify(productLines.filter(function (line) {
            return line.qty > 0;
        }).map(function (line) {
            var payload = {
                product_variant_id: line.product_variant_id,
                unit_id: line.unit_id,
                warehouse_id: line.warehouse_id,
                qty: line.qty,
            };
            if (!isRetailWarehouse(line.warehouse_id)) {
                var destId = parseInt(line.destination_warehouse_id || 0, 10);
                if (destId > 0) {
                    payload.destination_warehouse_id = destId;
                }
            }
            return payload;
        })));
        if (photos.length) {
            form.append("proof_base64", photos[0]);
        } else if (proofFile) {
            form.append("proof", proofFile);
        }

        var key = $("#cr-doc-key").val();
        var url = crMode === "edit" ? "/customerReturns/" + encodeURIComponent(key) : "/customerReturns";
        $("#cr-save").prop("disabled", true);
        $.ajax({ url: url, method: "POST", data: form, processData: false, contentType: false })
            .done(function (response) {
                $("#customer-return-modal").modal("hide");
                if (typeof toastr !== "undefined") toastr.success(response.message || "Pengembalian berhasil disimpan.");
                window.setTimeout(refreshCustomerReturn, 200);
            })
            .fail(notifyError)
            .always(function () { $("#cr-save").prop("disabled", false); });
    }

    function processRecord(key, action, question) {
        if (!key) return;
        var btnId =
            action === "accept"
                ? "btn-accept-customer-return"
                : "btn-decline-customer-return";
        var detail =
            action === "accept"
                ? question +
                  '<br><small class="text-muted">Stok akan bertambah dan tindakan ini tidak dapat diulang.</small>'
                : question +
                  '<br><small class="text-muted">Penolakan tidak mengubah stok.</small>';
        if (typeof showModalKonfirmasi !== "function") {
            if (typeof Swal !== "undefined") {
                Swal.fire({
                    icon: action === "accept" ? "question" : "warning",
                    title: question,
                    showCancelButton: true,
                    confirmButtonText: "Ya, lanjutkan",
                    cancelButtonText: "Batal",
                }).then(function (result) {
                    if (!result.isConfirmed) return;
                    postProcessRecord(key, action, null);
                });
            }
            return;
        }
        $("#customer-return-modal").modal("hide");
        showModalKonfirmasi(detail, btnId);
        $("#" + btnId)
            .attr("data-key", key)
            .attr("data-action", action);
    }

    function postProcessRecord(key, action, $loadingBtn) {
        if ($loadingBtn && $loadingBtn.length && typeof LoadingButton === "function") {
            LoadingButton($loadingBtn);
        }
        $.post("/customerReturns/" + encodeURIComponent(key) + "/" + action, {
            _token: csrf(),
        })
            .done(function (response) {
                if (typeof closeModalConfirm === "function") closeModalConfirm();
                $("#customer-return-modal").modal("hide");
                if (typeof toastr !== "undefined") toastr.success(response.message);
                window.setTimeout(refreshCustomerReturn, 200);
            })
            .fail(function (xhr) {
                if ($loadingBtn && $loadingBtn.length) {
                    $loadingBtn.data("busy", false);
                    if (typeof ResetLoadingButton === "function") {
                        ResetLoadingButton(
                            $loadingBtn,
                            '<i class="fe fe-check-circle me-1"></i>Konfirmasi'
                        );
                    }
                }
                notifyError(xhr);
            });
    }

    $(document).on(
        "click",
        "#btn-accept-customer-return, #btn-decline-customer-return",
        function () {
            var $btn = $(this);
            if ($btn.data("busy")) return;
            var key = $btn.attr("data-key");
            var action = $btn.attr("data-action");
            if (!key || !action) return;
            $btn.data("busy", true);
            postProcessRecord(key, action, $btn);
        }
    );

    function addSupplyLine() {
        var supply = selectedSupply();
        var unitId = parseInt($("#cr-supply-unit").val(), 10);
        var main = crMainWarehouse();
        var qty = parseInt($("#cr-supply-qty").val(), 10);
        setSelectInvalid("#cr-supply", !supply);
        setSelectInvalid("#cr-supply-unit", !unitId);
        $("#cr-supply-qty").toggleClass("is-invalid", !qty || qty <= 0);
        if (!supply || !unitId || !qty || qty <= 0) {
            if (typeof toastr !== "undefined") toastr.error("Pilih bahan, satuan, dan qty positif.");
            return;
        }
        if (!main) {
            if (typeof toastr !== "undefined") toastr.error("Gudang utama tidak ditemukan.");
            return;
        }
        var warehouseId = parseInt(main.id, 10);
        var warehouseName = main.warehouse_name;
        var existing = supplyLines.find(function (line) {
            return line.supplies_id === parseInt(supply.supplies_id, 10) &&
                line.unit_id === unitId && line.warehouse_id === warehouseId;
        });
        if (existing) {
            existing.qty += qty;
        } else {
            supplyLines.push({
                supplies_id: parseInt(supply.supplies_id, 10),
                supplies_name: supply.supplies_name,
                unit_id: unitId,
                unit_name: $("#cr-supply-unit option:selected").text(),
                warehouse_id: warehouseId,
                warehouse_name: warehouseName,
                qty: qty,
            });
        }
        $("#cr-supply").val(null).trigger("change");
        $("#cr-supply-unit").val(null).trigger("change.select2");
        $("#cr-supply-qty").val("").removeClass("is-invalid");
        renderAllLines();
    }

    function addProductLine() {
        var product = selectedProduct();
        var unitId = parseInt($("#cr-product-unit").val(), 10);
        var qty = parseInt($("#cr-product-qty").val(), 10);
        setSelectInvalid("#cr-product", !product);
        setSelectInvalid("#cr-product-unit", !unitId);
        $("#cr-product-qty").toggleClass("is-invalid", !qty || qty <= 0);
        if (!product || !unitId || !qty || qty <= 0) {
            if (typeof toastr !== "undefined") toastr.error("Pilih produk, satuan, dan qty positif.");
            return false;
        }
        var dest = resolveProductDestination(product, unitId);
        if (!dest || dest.error) {
            if (typeof toastr !== "undefined") {
                toastr.error(dest ? dest.error : "Gudang tujuan tidak valid.");
            }
            return false;
        }
        var retail = isRetailUnit(product, unitId);
        if (retail && !product.retail_unit) {
            promptRetailUnitSetup(product);
            setSelectInvalid("#cr-product-unit", true);
            return false;
        }
        var destWhId = null;
        var destWhName = null;
        var existing = productLines.find(function (line) {
            return (
                line.product_variant_id === parseInt(product.product_variant_id, 10) &&
                line.unit_id === unitId &&
                line.warehouse_id === dest.id &&
                parseInt(line.destination_warehouse_id || 0, 10) === parseInt(destWhId || 0, 10)
            );
        });
        if (existing) {
            existing.qty += qty;
        } else {
            productLines.push({
                product_variant_id: parseInt(product.product_variant_id, 10),
                product_label: product.product_label,
                unit_id: unitId,
                unit_name: $("#cr-product-unit option:selected").text(),
                warehouse_id: dest.id,
                warehouse_name: dest.name,
                retail_unit: parseInt(product.retail_unit || 0, 10) || null,
                destination_warehouse_id: destWhId,
                destination_warehouse_name: destWhName,
                qty: qty,
            });
        }
        $("#cr-product").val(null).trigger("change");
        $("#cr-product-unit").val(null).trigger("change.select2");
        $("#cr-product-qty").val("").removeClass("is-invalid");
        renderAllLines();
        if (retail && missingRetailDestinations() && typeof toastr !== "undefined") {
            toastr.warning("Pilih gudang eceran untuk produk satuan eceran di daftar item.");
        }
        return true;
    }

    $(function () {
        if (!$("#customer-return-modal").length) return;

        setupCustomerSelect();
        setCrAddItemType("supply");
        $("#customer-return-modal").on("hidden.bs.modal", function () {
            setCrModalLoading(false);
        });
        $("#customer-return-modal").on("shown.bs.modal", function () {
            syncCrRetailCreateMode();
        });
        $("#customer-return-tab").on("shown.bs.tab", function () {
            $(".btnAdd").addClass("d-none");
            initTable();
            setActiveTableFilter("customer-return");
            window.setTimeout(adjustTable, 0);
        });
        $("#shipping-tab").on("shown.bs.tab", function () {
            $(".btnAdd").removeClass("d-none");
            setActiveTableFilter("shipping");
            if (window.table) window.table.columns.adjust();
        });

        $("#cr-add").on("click", function () {
            resetModal();
            syncCrActiveWarehouseBadge();
            $("#customer-return-modal").modal("show");
        });
        $("#cr-customer").on("change", function () {
            setSelectInvalid("#cr-customer", !$(this).val());
        });

        $('input[name="cr-item-type"]').on("change", function () {
            if (isRetailWarehouse(crActiveWarehouseId()) && crAddItemType() === "supply") {
                $("#cr-type-product").prop("checked", true);
            }
            setCrAddItemType(crAddItemType());
            syncCrRetailCreateMode();
        });

        $("#cr-supply").on("change", function () {
            var supply = selectedSupply();
            setSelectInvalid("#cr-supply", false);
            destroySelect($("#cr-supply-unit"));
            $("#cr-supply-unit").html('<option value="">Pilih satuan</option>');
            (supply ? supply.units : []).forEach(function (unit) {
                $("#cr-supply-unit").append(new Option(unit.unit_name || unit.unit_short_name, unit.unit_id));
            });
            setupLocalSelect("#cr-supply-unit", "Pilih satuan");
            if (supply && supply.default_unit_id) {
                $("#cr-supply-unit").val(String(supply.default_unit_id)).trigger("change.select2");
            }
            $("#cr-supply-qty").val(supply ? 1 : "");
        });

        $("#cr-product").on("change", function () {
            setSelectInvalid("#cr-product", false);
            fillProductUnitOptions();
            $("#cr-product-qty").val(selectedProduct() ? 1 : "");
        });
        $("#cr-product-unit").on("change", function () {
            setSelectInvalid("#cr-product-unit", false);
            syncCrActiveWarehouseBadge();
        });

        $("#cr-add-item").on("click", function () {
            if (crAddItemType() === "product") addProductLine();
            else addSupplyLine();
        });

        $(document).on("click", "#btn_toggle_scan_cr", function () {
            if (crMode === "view" || crAddItemType() !== "product") return;
            setCrProductScanMode(!crScanMode);
        });

        $(document).on("click", "#btn_scan_add_cr", function () {
            doScanAddCr();
        });

        $(document).on("keydown", "#cr_scan_barcode", function (e) {
            if (e.key === "Enter" || e.keyCode === 13) {
                e.preventDefault();
                doScanAddCr();
            }
        });

        $(document).on("change", "#customer-return-modal .cr-retail-warehouse", function () {
            var $select = $(this);
            var index = parseInt($select.data("index"), 10);
            var line = productLines[index];
            if (!line) return;
            var id = parseInt($select.val(), 10) || 0;
            var selected = $select.select2("data")[0] || {};
            line.destination_warehouse_id = id || null;
            line.destination_warehouse_name = selected.text || selected.warehouse_name || "";
            markCrRetailWarehouseSelect($select, id <= 0);
            syncCrSaveEnabled();
        });

        $(document).on("change blur", ".cr-line-qty", function () {
            if (crMode === "view") return;
            var type = $(this).data("type");
            var index = parseInt($(this).data("index"), 10);
            var qty = parseInt($(this).val(), 10);
            var lines = type === "supply" ? supplyLines : productLines;
            if (!lines[index]) return;
            if (!qty || qty <= 0) {
                removeLineAt(type, index, $(this).closest("tr"));
                return;
            }
            lines[index].qty = qty;
            $(this).removeClass("is-invalid");
            updateCounts();
        });

        $(document).on("click", ".cr-remove-line", function () {
            if (crMode === "view") return;
            var type = $(this).data("type");
            var index = parseInt($(this).data("index"), 10);
            removeLineAt(type, index, $(this).closest("tr"));
        });

        $(document).on("keydown", ".cr-line-qty", function (event) {
            if (event.key === "Enter") {
                event.preventDefault();
                $(this).blur();
            }
        });

        $("#cr-proof-file").on("change", function () {
            var file = this.files[0];
            if (!file) {
                refreshProofState();
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                if (typeof toastr !== "undefined") toastr.error("Ukuran bukti maksimal 5 MB.");
                $(this).val("");
                return;
            }
            $("#cr-proof-camera").val("");
            refreshProofState();
        });
        $("#cr-btn-upload-proof").on("click", function () {
            rotationAngle = 0;
            camRotation = 0;
            photoData = "";
            modeCamera = 3;
            inputFile = "#cr-proof-camera";
            cameraReturnModal = "#customer-return-modal";
            resetCameraModalUi();
            startCamera();
            $("#customer-return-modal").modal("hide");
            $("#modalPhoto").modal("show");
        });
        $(document).on("click", "#uploadBtn", function () {
            if (inputFile === "#cr-proof-camera") {
                window.setTimeout(function () {
                    var photos = cameraPhotos();
                    if (photos.length) {
                        $("#cr-proof-camera").val(JSON.stringify([photos[photos.length - 1]]));
                        $("#cr-proof-file").val("");
                    }
                    refreshProofState();
                }, 0);
            }
        });
        $("#cr-btn-view-proof").on("click", function () {
            var source = currentProofUrl();
            var file = $("#cr-proof-file")[0].files[0];
            if (!source && file) source = URL.createObjectURL(file);
            if (!source) {
                if (typeof toastr !== "undefined") toastr.error("Bukti foto belum tersedia.");
                return;
            }
            $("#cr-proof-preview").attr("src", source);
            $("#cr-proof-download").attr("href", source);
            $("#customer-return-modal").modal("hide");
            $("#cr-photo-preview-modal").modal("show");
        });
        $("#cr-photo-preview-modal").on("hidden.bs.modal", function () {
            $("#customer-return-modal").modal("show");
        });

        $("#cr-save").on("click", submitRecord);
        function printReturn(key) {
            if (!key) return;
            window.open("/customerReturns/" + encodeURIComponent(key) + "/print", "_blank");
        }
        $(document).on("click", ".cr-view", function () { openRecord($(this).data("key"), "view"); });
        $(document).on("click", ".cr-confirm", function () { openRecord($(this).data("key"), "confirm"); });
        $(document).on("click", ".cr-print", function (event) {
            event.preventDefault();
            event.stopPropagation();
            printReturn($(this).data("key"));
        });
        $("#cr-print").on("click", function () {
            printReturn($("#cr-doc-key").val());
        });
        $(document).on("click", ".cr-edit", function () { openRecord($(this).data("key"), "edit"); });
        $(document).on("click", ".cr-delete", function () {
            var key = $(this).data("key");
            Swal.fire({ icon: "warning", title: "Hapus pengembalian?", showCancelButton: true, confirmButtonText: "Hapus" })
                .then(function (result) {
                    if (!result.isConfirmed) return;
                    $.post("/customerReturns/" + encodeURIComponent(key) + "/delete", { _token: csrf() })
                        .done(function (response) {
                            if (typeof toastr !== "undefined") toastr.success(response.message);
                            refreshCustomerReturn();
                        }).fail(notifyError);
                });
        });
        $("#cr-accept").on("click", function () { processRecord($("#cr-doc-key").val(), "accept", "ACC pengembalian?"); });
        $("#cr-decline").on("click", function () { processRecord($("#cr-doc-key").val(), "decline", "Tolak pengembalian?"); });
    });
})(jQuery);
