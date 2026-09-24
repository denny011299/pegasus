@extends('layout.fullscreen')
@section('custom_css')
<style>
    .opname-live .ol-stat { min-width: 120px; }
    .opname-live .ol-idle-ok { color: #198754; }
    .opname-live .ol-idle-warn { color: #fd7e14; }
    .opname-live .ol-idle-danger { color: #dc3545; }
    .opname-live table { font-size: 0.95rem; }
    .opname-live .ol-empty { color: #64748b; font-style: italic; }
</style>
@endsection
@section('content')
<div class="page-wrapper opname-live"><div class="content container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h3 class="mb-1">Monitor Stock Opname Live</h3>
            <p class="text-muted mb-0">
                Siapa yang sedang buka halaman Input + dokumen open hari ini.
                Auto-refresh tiap <span id="ol-interval-label">5</span> detik.
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="badge bg-primary ol-stat" id="ol-count-locks">Lock: 0</span>
            <span class="badge bg-warning text-dark ol-stat" id="ol-count-docs">Dokumen: 0</span>
            <span class="text-muted small" id="ol-server-time">—</span>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="ol-refresh">Refresh</button>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Page lock (sedang Input)</strong>
            <span class="small text-muted">TTL {{ \App\Support\StockOpname\OpnamePageLock::TTL_SECONDS }}s · heartbeat ~10s</span>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0" id="ol-table-locks">
                <thead class="table-light">
                    <tr>
                        <th>Gudang</th>
                        <th>Domain</th>
                        <th>Staff</th>
                        <th>Mulai lock</th>
                        <th>Last seen</th>
                        <th>Sisa TTL</th>
                    </tr>
                </thead>
                <tbody><tr><td colspan="6" class="ol-empty text-center py-4">Memuat…</td></tr></tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>Dokumen open (status menunggu / draft hari ini)</strong></div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0" id="ol-table-docs">
                <thead class="table-light">
                    <tr>
                        <th>Kode</th>
                        <th>Domain</th>
                        <th>Gudang</th>
                        <th>Pembuat</th>
                        <th>Draft?</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody><tr><td colspan="6" class="ol-empty text-center py-4">Memuat…</td></tr></tbody>
            </table>
        </div>
    </div>
</div></div>
@endsection
@section('custom_js')
<script>
(function () {
    const POLL_MS = 5000;
    const esc = (s) => String(s ?? '')
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');

    function ttlClass(sec) {
        if (sec == null) return '';
        if (sec <= 10) return 'ol-idle-danger fw-semibold';
        if (sec <= 20) return 'ol-idle-warn fw-semibold';
        return 'ol-idle-ok';
    }

    function renderLocks(rows) {
        const $tb = $('#ol-table-locks tbody');
        if (!rows || !rows.length) {
            $tb.html('<tr><td colspan="6" class="ol-empty text-center py-4">Tidak ada yang sedang Input</td></tr>');
            return;
        }
        $tb.html(rows.map((r) => {
            const left = r.expires_in_seconds;
            const leftLabel = left == null ? '—' : (left + 's');
            return `<tr>
                <td>${esc(r.warehouse_name)} <span class="text-muted">#${esc(r.warehouse_id)}</span></td>
                <td>${esc(r.domain_label)}</td>
                <td><strong>${esc(r.staff_name)}</strong> <span class="text-muted">#${esc(r.staff_id)}</span></td>
                <td>${esc(r.locked_at || '—')}</td>
                <td>${esc(r.last_seen_at || '—')}</td>
                <td class="${ttlClass(left)}">${esc(leftLabel)}</td>
            </tr>`;
        }).join(''));
    }

    function renderDocs(rows) {
        const $tb = $('#ol-table-docs tbody');
        if (!rows || !rows.length) {
            $tb.html('<tr><td colspan="6" class="ol-empty text-center py-4">Tidak ada dokumen open hari ini</td></tr>');
            return;
        }
        $tb.html(rows.map((r) => {
            const draft = r.is_draft
                ? '<span class="badge bg-secondary">Draft</span>'
                : '<span class="badge bg-info text-dark">Menunggu ACC</span>';
            const link = r.url
                ? `<a class="btn btn-sm btn-outline-primary" href="${esc(r.url)}" target="_blank" rel="noopener">Buka</a>`
                : '';
            return `<tr>
                <td><strong>${esc(r.code || '—')}</strong></td>
                <td>${esc(r.domain_label)}</td>
                <td>${esc(r.warehouse_name)}</td>
                <td>${esc(r.staff_name)}</td>
                <td>${draft}</td>
                <td class="text-end">${link}</td>
            </tr>`;
        }).join(''));
    }

    function load() {
        $.ajax({
            url: '{{ route("getOpnameLiveStatus") }}',
            method: 'GET',
            dataType: 'json',
            cache: false,
            success(res) {
                if (!res || !res.ok) return;
                const c = res.counts || {};
                $('#ol-count-locks').text('Lock: ' + (c.page_locks ?? 0));
                $('#ol-count-docs').text('Dokumen: ' + (c.open_documents ?? 0));
                $('#ol-server-time').text('Server: ' + (res.server_time || '—'));
                renderLocks(res.page_locks || []);
                renderDocs(res.open_documents || []);
            },
            error() {
                $('#ol-table-locks tbody').html(
                    '<tr><td colspan="6" class="text-danger text-center py-3">Gagal memuat data</td></tr>'
                );
            }
        });
    }

    $('#ol-interval-label').text(String(POLL_MS / 1000));
    $('#ol-refresh').on('click', load);
    load();
    setInterval(load, POLL_MS);
})();
</script>
@endsection
