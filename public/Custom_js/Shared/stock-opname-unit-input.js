/**
 * Singkatan label satuan untuk UI Opname yang sempit.
 * Pakai unit_short_name; kalau >4 karakter, ringkas (huruf awal + konsonan).
 */
function opnameUnitAbbrev(shortName, fullName) {
    var short = String(shortName || "").trim();
    var name = String(fullName || "").trim();
    var label = short || name || "-";
    if (label.length > 4) {
        var letters = label.replace(/[^a-zA-Z0-9]/g, "");
        if (letters.length > 4) {
            var first = letters.charAt(0);
            var rest = letters.slice(1).replace(/[aeiouAEIOU]/g, "");
            label = (first + rest).substring(0, 4).toUpperCase();
        } else {
            label = letters.toUpperCase() || label;
        }
    }
    return label;
}

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
    var unitName =
        opts.unitName ||
        opnameUnitAbbrev(opts.unitShortName, opts.unitFullName) ||
        "";
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
        ? '<span class="input-group-text unit-use-system-wrap" title="Centang untuk menggunakan stock sistem">' +
          '<input type="checkbox" class="form-check-input m-0 use-system-stock"' +
          chkAttr +
          "></span>"
        : "";

    return (
        '<div class="input-group unit-qty-group">' +
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
        '<span class="input-group-text">' +
        (typeof escapeHtml === "function" ? escapeHtml(unitName) : unitName) +
        "</span>" +
        "</div>"
    );
}

function applyOpnameUseSystemStockState($cb) {
    var $group = $cb.closest(".unit-qty-group");
    var $input = $group.find(".real-stock");
    if ($cb.is(":checked")) {
        if (!$input.data("prev-val-saved")) {
            $input.data("prev-val", $input.val());
            $input.data("prev-ph", $input.attr("placeholder") || "");
            $input.data("prev-val-saved", 1);
        }
        $input.val("").attr("placeholder", "ikut stock sistem").prop("disabled", true);
    } else {
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
