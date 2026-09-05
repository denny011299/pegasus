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
        $input.val("").attr("placeholder", "ikutin stock lama").prop("disabled", true);
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
    applyOpnameUseSystemStockState($(this));
});

/** Kunci input tabel/header saat simpan — penanggung jawab selalu disabled. */
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
    if (locked) {
        $("#tb-stock-wrap").addClass("opname-submitting");
    } else {
        $("#tb-stock-wrap").removeClass("opname-submitting");
    }
}
