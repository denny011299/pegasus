/** Nama produk + varian (varian disembunyikan kalau sama / sudah termasuk di nama). */
function opnameProductTitleHtml(prName, variantName) {
    var p = String(prName || "").trim();
    var v = String(variantName || "").trim();
    var esc = typeof escapeHtml === "function" ? escapeHtml : function (s) { return s; };
    var style = "font-size:13px;line-height:1.35;";
    var html =
        '<div class="fw-bold text-dark" style="' + style + '">' + esc(p || "-") + "</div>";
    if (!v) return html;
    var pL = p.toLowerCase();
    var vL = v.toLowerCase();
    if (pL === vL || pL.endsWith(vL)) return html;
    html +=
        '<div class="fw-bold text-dark mt-0.5" style="' + style + '">' + esc(v) + "</div>";
    return html;
}

/**
 * Unit input Stock Opname: checkbox "ikut stok lama" per satuan (Produk + Bahan).
 */
function buildOpnameUnitInputHtml(opts) {
    var unitId = opts.unitId;
    var unitName = opts.unitName || "";
    var systemQty =
        opts.systemQty != null && opts.systemQty !== "" ? opts.systemQty : "";
    var placeholder = opts.placeholder || "";
    var value = opts.value || "";
    var disabled = !!opts.disabled;
    var checked = !!opts.checked;
    var showCheckbox = opts.showCheckbox !== false;

    var displayVal = checked ? "" : value;
    var displayPh = checked ? "ikut stock sistem" : placeholder;
    var phAttr =
        displayPh !== ""
            ? ' placeholder="' + String(displayPh).replace(/"/g, "&quot;") + '"'
            : "";
    var disAttr = disabled || checked ? " disabled" : "";
    var chkAttr = checked ? " checked" : "";

    var checkboxHtml = showCheckbox
        ? '<span class="input-group-text unit-use-system-wrap" title="Centang: gunakan stok sistem">' +
          '<input type="checkbox" class="form-check-input m-0 use-system-stock" title="Centang: gunakan stok sistem"' +
          chkAttr +
          "></span>"
        : "";

    return (
        '<div class="input-group unit-qty-group' + (checked ? ' is-using-system' : '') + '">' +
        checkboxHtml +
        '<input type="text" class="form-control real-stock nominal_only text-end" value="' +
        displayVal +
        '"' +
        phAttr +
        disAttr +
        ' data-unit-id="' +
        unitId +
        '" data-unit-name="' +
        String(unitName).replace(/"/g, "&quot;") +
        '" data-system-qty="' +
        systemQty +
        '">' +
        '<span class="input-group-text unit-label-addon" title="Satuan: ' +
        String(unitName).replace(/"/g, "&quot;") +
        '">' +
        (typeof escapeHtml === "function" ? escapeHtml(unitName) : unitName) +
        "</span>" +
        "</div>"
    );
}

function applyOpnameUseSystemStockState($cb) {
    var $group = $cb.closest(".unit-qty-group");
    var $input = $group.find(".real-stock");
    if ($cb.is(":checked")) {
        $group.addClass("is-using-system");
        if (!$input.data("prev-val-saved")) {
            $input.data("prev-val", $input.val());
            $input.data("prev-ph", $input.attr("placeholder") || "");
            $input.data("prev-val-saved", 1);
        }
        $input.val("").attr("placeholder", "ikut stock sistem").prop("disabled", true);
    } else {
        $group.removeClass("is-using-system");
        var prev = $input.data("prev-val");
        var prevPh = $input.data("prev-ph") || "";
        $input.data("prev-val-saved", 0);
        $input
            .val(prev != null ? prev : "")
            .attr("placeholder", prevPh)
            .prop("disabled", false);
    }
}

$(document).on("change", ".use-system-stock", function () {
    var $cb = $(this);
    // Satu baris tidak boleh semua satuan ikut stock sistem (= tidak di-opname).
    if ($cb.is(":checked") && opnameRowAllUseSystemChecked($cb.closest(".row-stock"))) {
        $cb.prop("checked", false);
        if (typeof notifikasi === "function") {
            notifikasi(
                "error",
                "Tidak diizinkan",
                "Satu baris tidak boleh semua satuan tercentang. Kosongkan minimal satu atau isi hitungan.",
            );
        } else if (typeof toastr !== "undefined") {
            toastr.error("", "Satu baris tidak boleh semua satuan tercentang");
        }
        return;
    }
    applyOpnameUseSystemStockState($cb);
});

// Klik pada kotak checkbox addon langsung toggle checkbox
$(document).on("click", ".unit-use-system-wrap", function (e) {
    if (e.target.tagName !== "INPUT") {
        var $cb = $(this).find(".use-system-stock");
        if (!$cb.prop("disabled")) {
            $cb.prop("checked", !$cb.prop("checked")).trigger("change");
        }
    }
});

// Klik pada label satuan langsung fokus ke input
$(document).on("click", ".unit-qty-group > .input-group-text:last-child", function () {
    $(this).closest(".unit-qty-group").find(".real-stock:not(:disabled)").focus();
});

/** True kalau semua checkbox .use-system-stock di baris ini tercentang. */
function opnameRowAllUseSystemChecked($row) {
    var $all = $row.find(".use-system-stock");
    return $all.length > 0 && $all.filter(":checked").length === $all.length;
}

/** Kunci input tabel/header saat simpan — penanggung jawab selalu disabled. */
var OPNAME_ACTION_BTNS =
    ".btn-save,.btn-save-draft,.btn-ajukan,.btn-delete-draft,.save-tolak,.save-terima";

function isOpnameTableLoading() {
    return $("#tb-stock-wrap").hasClass("is-loading");
}

/**
 * Saat tabel produk/bahan masih di-fetch: overlay loading + disable tombol aksi.
 * Jangan re-enable kalau form sedang submit (opname-submitting).
 */
function setOpnameTableBusy(busy) {
    $("#tb-stock-wrap").toggleClass("is-loading", !!busy);
    if (busy) {
        $(OPNAME_ACTION_BTNS).prop("disabled", true).attr("aria-busy", "true");
        return;
    }
    if ($("#tb-stock-wrap").hasClass("opname-submitting")) {
        return;
    }
    $(OPNAME_ACTION_BTNS).prop("disabled", false).removeAttr("aria-busy");
}

function guardOpnameNotBusy($btn, doneText) {
    if (!isOpnameTableLoading()) return true;
    if ($btn) ResetLoadingButton($btn, doneText);
    if (typeof notifikasi === "function") {
        notifikasi(
            "warning",
            "Mohon tunggu",
            "Data masih dimuat. Tunggu selesai lalu coba lagi.",
        );
    }
    return false;
}

function setStockOpnameFormLocked(locked) {
    $("#tbStock .real-stock, #tbStock .notes, #tbStock .use-system-stock").prop(
        "disabled",
        !!locked,
    );
    $("#filter_pr_name, #filter_sup_name, #tanggal, #catatan").prop(
        "disabled",
        !!locked,
    );
    $("#penanggung-jawab").prop("disabled", true).trigger("change");
    $(OPNAME_ACTION_BTNS).prop("disabled", !!locked);
    if (locked) {
        $("#tb-stock-wrap").addClass("opname-submitting");
    } else {
        $("#tb-stock-wrap").removeClass("opname-submitting");
        // Jangan buka tombol kalau tabel masih loading
        if (!isOpnameTableLoading()) {
            $(OPNAME_ACTION_BTNS).prop("disabled", false).removeAttr("aria-busy");
        }
    }
}
