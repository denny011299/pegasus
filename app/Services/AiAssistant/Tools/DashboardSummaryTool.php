<?php

namespace App\Services\AiAssistant\Tools;

use App\Http\Controllers\ReportController;
use Illuminate\Http\Request;

/**
 * Angka dashboard untuk asisten. Memanggil endpoint dashboard yang sama
 * dengan halaman Dashboard (bukan hitung ulang), supaya jawaban AI selalu
 * sama dengan yang dilihat staf. Endpoint itu hanya SELECT + cache.
 */
class DashboardSummaryTool implements ToolInterface
{
    private const SECTIONS = ['ringkasan', 'top produk', 'stock aging', 'peringatan stok', 'hutang jatuh tempo', 'antrian acc', 'grafik'];

    /** Max baris per daftar yang diteruskan ke model. */
    private const MAX_LIST = 10;

    /** Kunci output dashboard => label UI. Kunci lain ikut apa adanya. */
    private const LABELS = [
        'sales_growth_pct' => 'Pertumbuhan Penjualan (%)',
        'sales_qty_current' => 'Qty Terjual Periode Ini',
        'sales_qty_previous' => 'Qty Terjual Periode Sebelumnya',
        'inventory_turnover' => 'Perputaran Persediaan',
        'dio_days' => 'Lama Persediaan Habis (hari, bukan rupiah)',
        'return_rate_product_pct' => 'Tingkat Retur Produk (%)',
        'return_rate_bahan_pct' => 'Tingkat Retur Bahan (%)',
        'total' => 'Total',
        'product' => 'Produk',
        'bahan' => 'Bahan',
        'changelog_pending' => 'Log Perubahan Belum Dibaca',
        'confirmation_log' => 'Menunggu ACC',
        'revision_log' => 'Menunggu Revisi',
        'yearly' => 'Tahun Ini',
        'accumulative' => 'Bulan Ini',
        'name' => 'Nama',
        'qty' => 'Qty',
        'unit' => 'Satuan',
        'qty_text' => 'Qty',
        'age_days' => 'Umur (hari)',
        'bucket' => 'Kelompok Umur',
        'status' => 'Status',
        'value' => 'Nilai',
        'kind' => 'Jenis',
        'stock_text' => 'Stok',
        'alert_qty' => 'Batas Peringatan',
        'alert_unit' => 'Satuan Peringatan',
        'saran' => 'Saran',
        'level' => 'Level',
        'invoice' => 'No. Invoice',
        'reference' => 'Referensi',
        'customer' => 'Pemasok',
        'due_text' => 'Jatuh Tempo',
        'due_badge' => 'Keterangan',
        'amount' => 'Nominal',
        'module_label' => 'Modul',
        'date' => 'Tanggal',
        'summary' => 'Ringkasan',
        'what_changed' => 'Perubahan',
        'title' => 'Judul',
        'subtitle' => 'Keterangan',
        'primary' => 'Jumlah',
        'primary_label' => 'Satuan Jumlah',
        'secondary' => 'Info',
        'mom_pct' => 'Perubahan vs Bulan Lalu (%)',
        'overstock_alerts' => 'Stok Menumpuk',
        'recommended_production' => 'Rekomendasi Produksi',
        'recommended_note' => 'Catatan Rekomendasi',
        'count_critical' => 'Jumlah Kritis',
        'count_warn' => 'Jumlah Waspada',
        'rows' => 'Daftar',
        'labels' => 'Periode',
        'sales_qty' => 'Qty Terjual',
        'return_qty' => 'Qty Retur',
        'sales_count' => 'Jumlah Pengiriman',
        'sales_total' => 'Nilai Pengiriman',
        'production_count' => 'Jumlah Produksi',
        'purchase_count' => 'Jumlah Pembelian',
        'purchase_total' => 'Nilai Pembelian',
    ];

    /** Kunci teknis yang tidak berguna untuk jawaban (link, ikon, id). */
    private const DROP = ['url', 'urls', 'href', 'meta', 'url_label', 'queue_key', 'hint', 'days_diff', 'due_date', 'sales_growth_pct_by_bucket', 'range_yearly', 'range_accum', 'top_yearly_caption', 'top_accum_caption'];

    public function name(): string
    {
        return 'dashboard_summary';
    }

    public function description(): string
    {
        return 'Read the same numbers shown on the Dashboard page for a week/month/year: KPI penjualan & retur, nilai persediaan, widget pengiriman/produksi/pembelian, top produk, stock aging, peringatan stok bahan, hutang jatuh tempo, antrian ACC, grafik. Read-only.';
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'bagian' => [
                    'type' => 'string',
                    'enum' => self::SECTIONS,
                    'description' => 'Bagian dashboard. Default ringkasan.',
                ],
                'periode' => ['type' => 'string', 'enum' => ['minggu', 'bulan', 'tahun'], 'description' => 'Default bulan'],
                'tanggal' => ['type' => 'string', 'description' => 'YYYY-MM-DD di dalam periode yang diminta; default hari ini'],
            ],
        ];
    }

    public function execute(array $args): array
    {
        $section = in_array($args['bagian'] ?? null, self::SECTIONS, true) ? $args['bagian'] : 'ringkasan';
        $period = ['minggu' => 'week', 'tahun' => 'year'][$args['periode'] ?? ''] ?? 'month';
        $anchor = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($args['tanggal'] ?? '')) ? $args['tanggal'] : '';

        $report = app(ReportController::class);
        $overview = $report->getDashboardOverview(new Request(['period' => $period, 'anchor' => $anchor]))->getData(true);

        $data = match ($section) {
            'top produk' => ['top_products' => $overview['top_products'] ?? []],
            'stock aging' => [
                'stock_aging' => $overview['stock_aging'] ?? [],
                'stock_aging_detail' => $overview['stock_aging_detail'] ?? [],
            ],
            'peringatan stok' => [
                'warnings' => $overview['warnings'] ?? [],
                'bahan_stock_alerts' => $overview['bahan_stock_alerts'] ?? [],
            ],
            'hutang jatuh tempo' => ['payables_due' => $overview['payables_due'] ?? []],
            'antrian acc' => ['changelog' => $overview['changelog'] ?? []],
            'grafik' => [
                'chart' => $overview['chart'] ?? [],
                'exec_charts' => $report->getDashboardExecutiveWidgets(new Request([]))->getData(true)['exec_charts'] ?? [],
            ],
            default => $this->summary($overview, $report),
        };

        return [
            'bagian' => $section,
            'periode' => $overview['filter']['label'] ?? '',
            'data' => $this->clean($data),
            'catatan' => 'Angka sama dengan halaman Dashboard. Nilai persediaan = stok saat ini, tidak ikut periode.',
        ];
    }

    private function summary(array $overview, ReportController $report): array
    {
        $changelog = $overview['changelog'] ?? [];
        $alerts = $overview['bahan_stock_alerts'] ?? [];
        $rupiah = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.');
        $inventory = $overview['inventory_value'] ?? [];
        // Daftar hutang di dashboard dibatasi 30 baris; jangan laporkan 30 sebagai angka pasti.
        $payables = count($overview['payables_due'] ?? []);

        return [
            'kpi' => $overview['kpi'] ?? [],
            'nilai_persediaan' => [
                'Total' => $rupiah($inventory['total'] ?? 0),
                'Produk' => $rupiah($inventory['product'] ?? 0),
                'Bahan' => $rupiah($inventory['bahan'] ?? 0),
            ],
            'widget' => array_values($report->getDashboardExecutiveWidgets(new Request([]))->getData(true)['cross_widgets'] ?? []),
            'antrian' => array_intersect_key($changelog, array_flip(['changelog_pending', 'confirmation_log', 'revision_log'])),
            'jumlah_hutang_jatuh_tempo' => $payables >= 30 ? '30 atau lebih (daftar dashboard dibatasi 30)' : $payables,
            'peringatan_stok_bahan' => array_intersect_key($alerts, array_flip(['count_critical', 'count_warn'])),
        ];
    }

    /** Relabel keys, drop link/id noise, cap long lists. */
    private function clean(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            // Deret angka grafik (≤ 12 bulan / 53 minggu) dibiarkan utuh.
            if (!is_array($value[0] ?? null)) {
                return $value;
            }
            $total = count($value);
            $out = array_map(fn ($v) => $this->clean($v), array_slice($value, 0, self::MAX_LIST));
            if ($total > self::MAX_LIST) {
                $out[] = ['catatan' => 'Ditampilkan '.self::MAX_LIST.' dari '.$total.' baris.'];
            }

            return $out;
        }

        $out = [];
        foreach ($value as $key => $v) {
            if (in_array($key, self::DROP, true) || (is_string($key) && str_ends_with($key, '_id'))) {
                continue;
            }
            $out[self::LABELS[$key] ?? $key] = $this->clean($v);
        }

        return $out;
    }
}
