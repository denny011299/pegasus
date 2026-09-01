/**
 * PG Popup Table — perilaku standar untuk tabel input di dalam modal popup.
 *
 * Dipakai oleh semua modal yang isinya "kartu input + tabel baris item"
 * (Produksi, Sales Order, Stock Transfer, PO, dst). Tujuannya supaya UX-nya
 * seragam: kartu input selalu di ATAS tabel, dan yang scroll adalah tabelnya
 * sendiri (setinggi N baris), bukan modal-body-nya.
 *
 * SATU-SATUNYA tempat mengubah konstanta ada di file ini.
 *
 * Cara pakai di blade:
 *   <div class="pg-popup-table-input"> ...kartu input... </div>
 *   <div class="table-responsive pg-popup-table-scroll ...">
 *     <table>...</table>
 *   </div>
 * Tinggi maksimal tabel dihitung otomatis (tidak perlu panggil apa pun).
 *
 * Cara pakai di JS halaman:
 *   pgPopupTableInsert(items, data);   // hormati ROW_INSERT_POSITION
 */
var PG_POPUP_TABLE = {
    /** Tinggi maksimal tabel = setinggi berapa baris item. */
    MAX_VISIBLE_ROWS: 4,

    /**
     * Posisi baris baru saat ditambahkan.
     * 'top'    → baris baru masuk paling atas (langsung terlihat tanpa scroll)
     * 'bottom' → baris baru masuk paling bawah
     */
    ROW_INSERT_POSITION: "bottom",
};
window.PG_POPUP_TABLE = PG_POPUP_TABLE;

/**
 * Sisipkan item ke array daftar baris sesuai ROW_INSERT_POSITION.
 * Dipakai juga untuk array pendamping (mis. list_bahan) supaya index-nya
 * tetap sejajar dengan array utama.
 *
 * @param {Array} list array daftar baris (dimodifikasi in-place)
 * @param {*} item item baru
 * @returns {Array} list yang sama
 */
function pgPopupTableInsert(list, item) {
    if (!Array.isArray(list)) return list;
    if (PG_POPUP_TABLE.ROW_INSERT_POSITION === "top") {
        list.unshift(item);
    } else {
        list.push(item);
    }
    return list;
}
window.pgPopupTableInsert = pgPopupTableInsert;

/** true kalau baris baru masuk di paling atas. */
function pgPopupTableInsertsAtTop() {
    return PG_POPUP_TABLE.ROW_INSERT_POSITION === "top";
}
window.pgPopupTableInsertsAtTop = pgPopupTableInsertsAtTop;

/**
 * Hitung ulang tinggi maksimal satu container scroll tabel popup.
 * Aman dipanggil berkali-kali; kalau elemennya belum terlihat (modal masih
 * tertutup) perhitungan dilewati supaya tidak menghasilkan tinggi 0.
 *
 * @param {Element|jQuery|string} el container ber-class .pg-popup-table-scroll
 */
function pgPopupTableRefresh(el) {
    var $scroll = $(el);
    if (!$scroll.length) return;

    $scroll.each(function () {
        var $s = $(this);
        if (!$s.is(":visible")) return;

        var $rows = $s.find("tbody > tr").not(".pg-popup-table-ignore");
        var max = parseInt(PG_POPUP_TABLE.MAX_VISIBLE_ROWS, 10) || 4;

        if ($rows.length <= max) {
            // Muat semua → tidak perlu scroll vertikal sama sekali. Pakai "none",
            // BUKAN "" — mengosongkan inline style hanya menjatuhkan balik ke
            // fallback CSS (--pg-popup-table-max-height, cuma placeholder
            // sebelum JS ini sempat jalan), yang jadi lebih pendek dari
            // MAX_VISIBLE_ROWS baris asli begitu row makin tinggi/banyak.
            $s.css("max-height", "none");
            return;
        }

        var height = 0;
        $s.find("thead").each(function () {
            height += this.offsetHeight || 0;
        });
        $s.find("tfoot").each(function () {
            height += this.offsetHeight || 0;
        });
        $rows.slice(0, max).each(function () {
            height += this.offsetHeight || 0;
        });

        if (height <= 0) return; // belum ter-layout, biarkan apa adanya
        $s.css("max-height", Math.ceil(height) + "px");
    });
}
window.pgPopupTableRefresh = pgPopupTableRefresh;

/** Hitung ulang semua container scroll tabel popup yang sedang terlihat. */
function pgPopupTableRefreshAll(root) {
    pgPopupTableRefresh($(root || document).find(".pg-popup-table-scroll"));
}
window.pgPopupTableRefreshAll = pgPopupTableRefreshAll;

/**
 * Scroll container tabel supaya baris yang baru saja ditambahkan langsung
 * terlihat, mengikuti arah ROW_INSERT_POSITION ('top' → scroll ke atas,
 * 'bottom' → scroll ke bawah).
 *
 * Sengaja TIDAK dipanggil otomatis dari MutationObserver di file ini — daftar
 * baris di modal ini di-render ulang lewat clear+append penuh (`addRow()`)
 * untuk insert MAUPUN delete/reset, jadi dari sisi DOM keduanya sama-sama
 * "node baru ditambahkan". Kalau di-auto-scroll dari situ, hapus baris pun
 * ikut melompat ke bawah/atas. Panggil ini secara eksplisit HANYA di titik
 * kode yang benar-benar berarti "user menambah baris baru" (bukan di titik
 * render ulang untuk alasan lain) — lihat pemanggilannya di
 * `continueAddProduct()` (Production.js) untuk contoh.
 *
 * @param {Element|jQuery|string} el container ber-class .pg-popup-table-scroll
 */
function pgPopupTableScrollToEdge(el) {
    var $s = $(el);
    if (!$s.length) return;
    $s.each(function () {
        var top =
            PG_POPUP_TABLE.ROW_INSERT_POSITION === "top"
                ? 0
                : this.scrollHeight;
        if (typeof this.scrollTo === "function") {
            this.scrollTo({ top: top, behavior: "smooth" });
        } else {
            // Browser lama tanpa smooth scroll (mis. Safari sebelum ~15.4) — langsung lompat.
            this.scrollTop = top;
        }
    });
}
window.pgPopupTableScrollToEdge = pgPopupTableScrollToEdge;

$(function () {
    // Baris tabel di modal ini di-render ulang lewat .html()/.append() dari JS
    // halaman (bukan DataTables), jadi pakai MutationObserver supaya halaman
    // pemakai tidak perlu memanggil refresh manual setiap render.
    if (window.MutationObserver) {
        var observer = new MutationObserver(function (mutations) {
            var touched = [];
            mutations.forEach(function (m) {
                var scroll = $(m.target).closest(".pg-popup-table-scroll")[0];
                if (scroll && touched.indexOf(scroll) === -1)
                    touched.push(scroll);
            });
            if (touched.length) pgPopupTableRefresh($(touched));
        });
        $(".pg-popup-table-scroll").each(function () {
            var tbody = this.querySelector("tbody");
            if (tbody) observer.observe(tbody, { childList: true });
        });
    }

    // Saat modal baru dibuka, elemennya baru punya tinggi nyata.
    $(document).on("shown.bs.modal", ".modal", function () {
        pgPopupTableRefreshAll(this);
    });

    $(window).on("resize", function () {
        pgPopupTableRefreshAll(document);
    });
});
