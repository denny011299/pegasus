(function () {
    var selected = [];
    var searchTimer = null;

    function escHtml(str) {
        return String(str == null ? "" : str)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
    }

    function fmtRp(n) {
        var val = Number(n || 0);
        return "Rp " + val.toLocaleString("id-ID");
    }

    function notify(type, title, msg) {
        if (typeof notifikasi === "function") {
            notifikasi(type, title, msg);
            return;
        }
        alert(msg);
    }

    function renderTable() {
        var $tb = $("#tbProduct");
        $tb.empty();
        if (!selected.length) {
            $tb.append('<tr><td colspan="5" class="text-center text-muted">Belum ada item dipilih</td></tr>');
            return;
        }
        for (var i = 0; i < selected.length; i++) {
            var row = selected[i];
            $tb.append(
                "<tr>" +
                    "<td>" +
                    escHtml(row.nama_produk + (row.nama_varian ? " " + row.nama_varian : "")) +
                    "</td>" +
                    "<td>" +
                    escHtml(row.sku || "-") +
                    "</td>" +
                    "<td>" +
                    escHtml(row.barcode || "-") +
                    "</td>" +
                    "<td style='max-width: 90px'><input type='number' min='1' class='form-control form-control-sm barcode-qty' data-idx='" +
                    i +
                    "' value='" +
                    row.qty_print +
                    "'></td>" +
                    "<td class='text-center'><button type='button' class='btn btn-sm btn-outline-danger barcode-remove' data-idx='" +
                    i +
                    "'>Hapus</button></td>" +
                "</tr>"
            );
        }
    }

    function addSelected(item) {
        var idx = -1;
        for (var i = 0; i < selected.length; i++) {
            if (String(selected[i].product_variant_id) === String(item.product_variant_id)) {
                idx = i;
                break;
            }
        }
        if (idx >= 0) {
            selected[idx].qty_print += 1;
        } else {
            selected.push({
                product_variant_id: item.product_variant_id,
                nama_produk: item.nama_produk || "-",
                nama_varian: item.nama_varian || "",
                sku: item.sku || "",
                barcode: item.barcode || item.sku || "",
                harga: Number(item.harga || 0),
                qty_print: 1,
            });
        }
        renderTable();
    }

    function renderResults(rows) {
        var $box = $(".resultBox");
        $box.empty();
        if (!rows || !rows.length) {
            $box.hide();
            return;
        }
        for (var i = 0; i < rows.length; i++) {
            var r = rows[i];
            var title = (r.nama_produk || "-") + " " + (r.nama_varian || "");
            $box.append(
                "<div class='barcode-result-item p-2 border-bottom' style='cursor:pointer' data-json='" +
                    escHtml(JSON.stringify(r)) +
                    "'>" +
                    "<div class='fw-semibold'>" +
                    escHtml(title.trim()) +
                    "</div>" +
                    "<small class='text-muted'>" +
                    escHtml(r.sku || "-") +
                    " · " +
                    escHtml(r.barcode || r.sku || "-") +
                    " · " +
                    escHtml(fmtRp(r.harga || 0)) +
                    "</small>" +
                "</div>"
            );
        }
        $box.show();
    }

    function searchProducts(q) {
        if (!q || q.trim().length < 2) {
            renderResults([]);
            return;
        }
        $("#loading").show();
        $.ajax({
            url: "/getBarcodeProducts",
            method: "get",
            data: { q: q.trim() },
            success: function (rows) {
                renderResults(rows || []);
            },
            error: function () {
                renderResults([]);
            },
            complete: function () {
                $("#loading").hide();
            },
        });
    }

    function buildSubmitForm() {
        var items = selected
            .filter(function (x) {
                return (Number(x.qty_print || 0) > 0) && String(x.barcode || "").trim() !== "";
            })
            .map(function (x) {
                return {
                    product_variant_id: x.product_variant_id,
                    nama_produk: x.nama_produk,
                    nama_varian: x.nama_varian,
                    sku: x.sku,
                    barcode: x.barcode,
                    harga: x.harga,
                    qty_print: Number(x.qty_print || 0),
                };
            });

        if (!items.length) {
            notify("error", "Gagal Cetak", "Tidak ada item valid untuk dicetak.");
            return;
        }

        var token = $('meta[name="csrf-token"]').attr("content") || "";
        var form = document.createElement("form");
        form.method = "POST";
        form.action = "/printBarcodePdf";
        form.target = "_blank";
        form.style.display = "none";

        function addInput(name, val) {
            var inp = document.createElement("input");
            inp.type = "hidden";
            inp.name = name;
            inp.value = val;
            form.appendChild(inp);
        }

        addInput("_token", token);
        addInput("items_json", JSON.stringify(items));
        addInput("nama", $("#nama").is(":checked") ? "1" : "0");
        addInput("harga", $("#harga").is(":checked") ? "1" : "0");

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }

    $(document).ready(function () {
        renderTable();
        $(".resultBox").hide();

        $("#search").on("input", function () {
            var q = $(this).val() || "";
            if (searchTimer) clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                searchProducts(q);
            }, 250);
        });

        $(document).on("click", ".barcode-result-item", function () {
            var json = $(this).attr("data-json") || "{}";
            try {
                addSelected(JSON.parse(json));
            } catch (e) {}
            $("#search").val("");
            renderResults([]);
        });

        $(document).on("click", ".barcode-remove", function () {
            var idx = Number($(this).attr("data-idx") || -1);
            if (idx >= 0) {
                selected.splice(idx, 1);
                renderTable();
            }
        });

        $(document).on("change blur", ".barcode-qty", function () {
            var idx = Number($(this).attr("data-idx") || -1);
            var qty = Number($(this).val() || 0);
            if (idx < 0 || idx >= selected.length) return;
            if (!qty || qty < 1) {
                qty = 1;
            }
            selected[idx].qty_print = qty;
            $(this).val(qty);
        });

        $(".btn-print-barcode").on("click", function () {
            buildSubmitForm();
        });
    });
})();
