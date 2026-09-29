from pathlib import Path
p = Path(r"public/Custom_js/Backoffice/Production/WorkOrders.js")
t = p.read_text(encoding="utf-8")
old = """    await woSend(
        woDetail.wo.production_work_order_id,
        \"material_issue\",
        { request_id: woKey(), items: items },
        \"Simpan Ambil Bahan\",
        items.length +
            \" baris bahan akan dicatat. Stok potong setelah ACC QC. Bisa ambil lagi nanti.\",
        \"Ambil bahan tercatat. Menunggu ACC QC untuk potong stok.\"
    );
    $(\"#wo-mat-form\").hide();
});

$(document).on(\"click\", \".wo-mat-acc-qc\", function () {
    var docId = Number($(this).data(\"doc\"));
    if (!docId || !woDetail) return;
    var doc = ((woDetail.documents || []).find(function (d) {
        return Number(d.id) === docId;
    }) || {});
    var received = {};
    (doc.items || []).forEach(function (it, idx) {
        received[idx] = it.requested_qty;
    });
    woSend(
        docId,
        \"qc\",
        { received: received },
        \"ACC QC Ambil Bahan\",
        \"Stok gudang akan dipotong sesuai qty yang diambil PIC.\",
        \"ACC QC bahan OK — stok dipotong.\"
    );
});"""
new = """    await woSend(
        woDetail.wo.production_work_order_id,
        \"material_issue\",
        { request_id: woKey(), items: items },
        \"Simpan Ambil Bahan\",
        items.length +
            \" baris bahan dicatat. Selanjutnya ACC Kepala Ops lalu QC untuk potong stok.\",
        \"Ambil bahan tercatat. Menunggu ACC Kepala Ops → QC.\"
    );
    $(\"#wo-mat-form\").hide();
});

$(document).on(\"click\", \".wo-mat-acc-ops\", function () {
    var docId = Number($(this).data(\"doc\"));
    if (!docId) return;
    woSend(
        docId,
        \"ops\",
        {},
        \"ACC Kepala Operasional — Bahan\",
        \"Setelah ACC Ops, dokumen menunggu Staf QC.\",
        \"ACC Ops bahan OK. Menunggu QC.\"
    );
});

$(document).on(\"click\", \".wo-mat-acc-qc\", function () {
    var docId = Number($(this).data(\"doc\"));
    if (!docId) return;
    woSend(
        docId,
        \"qc\",
        {},
        \"ACC QC Ambil Bahan\",
        \"Stok gudang akan dipotong sesuai qty yang diambil PIC.\",
        \"ACC QC bahan OK — stok dipotong.\"
    );
});"""
# file may use actual quotes not escaped
old = old.replace('\\"', '"')
new = new.replace('\\"', '"')
if old not in t:
    i = t.find("Simpan Ambil Bahan")
    print("OLD NOT FOUND", i)
    print(repr(t[i-50:i+500]))
else:
    p.write_text(t.replace(old, new), encoding="utf-8")
    print("OK")
