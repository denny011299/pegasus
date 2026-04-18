/**
 * Overlay loading di atas area tabel laporan (card-body yang membungkus DataTable).
 * @param {string} tableSelector contoh '#tableBahanBaku'
 * @param {boolean} show
 */
function setReportDataTableLoading(tableSelector, show) {
    var $table = $(tableSelector);
    if (!$table.length) return;
    var $host = $table.closest('.card-body');
    if (!$host.length) return;
    var safeId = 'js-rpt-loader-' + String(tableSelector).replace(/[^a-zA-Z0-9]/g, '');
    var $ovl = $host.find('#' + safeId);
    if (show) {
        $host.css('position', 'relative');
        if (!$ovl.length) {
            $host.append(
                '<div id="' +
                    safeId +
                    '" style="display:none;position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(255,255,255,0.82);z-index:25;flex-direction:column;align-items:center;justify-content:center;gap:10px;">' +
                    '<div class="spinner-border text-primary" style="width:2.5rem;height:2.5rem;" role="status" aria-hidden="true"></div>' +
                    '<span class="text-muted small">Memuat data laporan…</span></div>'
            );
            $ovl = $host.find('#' + safeId);
        }
        $ovl.css('display', 'flex');
    } else if ($ovl.length) {
        $ovl.css('display', 'none');
    }
}
