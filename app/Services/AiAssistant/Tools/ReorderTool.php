<?php

namespace App\Services\AiAssistant\Tools;

use App\Services\AiAssistant\ReadOnlyDb;
use App\Services\AiAssistant\Tools\Concerns\TrackingHelpers;
use App\Services\AiAssistant\Vocabulary;

/**
 * Saran beli ulang bahan mentah. Pemakaian harian = rata-rata pengurangan bahan
 * untuk produksi (bukan opname/transfer/pembatalan) di satuan default bahan.
 * Titik pesan = pemakaian × lead time + safety stock; saran qty = cukup untuk
 * lead time + target hari stok. Hanya saran — keputusan beli tetap di user.
 */
class ReorderTool implements ToolInterface
{
    use TrackingHelpers;

    /** log_stocks: 2 = bahan, kategori 2 = keluar. */
    private const LOG_BAHAN = 2;
    private const LOG_KELUAR = 2;

    /** Lead time bila master bahan kosong dan belum ada riwayat PO → penerimaan. */
    private const DEFAULT_LEAD_DAYS = 3;

    public function __construct(protected ReadOnlyDb $db, protected Vocabulary $vocab) {}

    public function name(): string
    {
        return 'reorder_suggestion';
    }

    public function description(): string
    {
        return 'Suggest which bahan mentah to buy again: current stock (satuan default, semua gudang), average daily usage for produksi, days of stock left, lead time, reorder point and suggested qty. Also flags bahan below its stock alert. Use for "bahan apa yang harus dibeli", "stok X cukup berapa hari", "kapan harus order X". Read-only suggestion.';
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'item' => ['type' => 'string', 'description' => 'Opsional: nama bahan (kosong = semua bahan yang perlu dibeli)'],
                'hari_pemakaian' => ['type' => 'integer', 'description' => 'Hitung rata-rata pemakaian dari N hari terakhir. Default 30.'],
                'target_hari' => ['type' => 'integer', 'description' => 'Stok setelah beli cukup untuk N hari (di luar lead time). Default 14.'],
                'limit' => ['type' => 'integer', 'description' => 'Default 15, max 50'],
            ],
        ];
    }

    public function execute(array $args): array
    {
        $usageDays = max(7, min((int) ($args['hari_pemakaian'] ?? 30), 180));
        $target = max(1, min((int) ($args['target_hari'] ?? 14), 90));
        $limit = max(1, min((int) ($args['limit'] ?? 15), 50));
        $item = trim((string) ($args['item'] ?? ''));

        $supplies = $this->activeOnly($this->db->table('supplies'), 'supplies')
            ->when($item !== '', fn ($q) => $q->where('supplies_name', 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], $item).'%'))
            ->get(['supplies_id', 'supplies_name', 'supplies_alert', 'lead_time_days', 'safety_stock', 'supplies_default_unit']);
        if ($supplies->isEmpty()) {
            return ['pesan' => $item !== '' ? 'Bahan "'.$item.'" tidak ditemukan.' : 'Tidak ada bahan aktif.'];
        }
        $ids = $supplies->pluck('supplies_id')->all();

        $stock = $this->db->table('supplies_stocks')->whereIn('supplies_id', $ids)
            ->selectRaw('supplies_id, unit_id, SUM(ss_stock) as total')->groupBy('supplies_id', 'unit_id')->get()
            ->groupBy('supplies_id');

        // Hanya pemakaian produksi; opname/pembatalan/retur bukan konsumsi nyata.
        $usage = $this->db->table('log_stocks')
            ->where('log_type', self::LOG_BAHAN)->where('log_category', self::LOG_KELUAR)
            ->whereIn('log_item_id', $ids)
            ->where('log_notes', 'like', 'Pengurangan bahan untuk produksi%')
            ->where('log_date', '>=', now()->subDays($usageDays)->toDateString())
            ->selectRaw('log_item_id as sid, unit_id, SUM(log_jumlah) as total')->groupBy('log_item_id', 'unit_id')->get()
            ->groupBy('sid');

        $rows = [];
        foreach ($supplies as $s) {
            $unit = (int) $s->supplies_default_unit;
            $have = (float) ($stock->get($s->supplies_id)?->firstWhere('unit_id', $unit)?->total ?? 0);
            $used = (float) ($usage->get($s->supplies_id)?->firstWhere('unit_id', $unit)?->total ?? 0);
            $daily = $used / $usageDays;
            $alert = (float) $s->supplies_alert;
            if ($daily <= 0 && ($alert <= 0 || $have > $alert) && $item === '') {
                continue;
            }

            [$lead, $leadSource] = $this->leadTime($s);
            $safety = (float) $s->safety_stock;
            $reorderPoint = $daily * $lead + $safety;
            $cover = $daily > 0 ? $have / $daily : null;
            $suggest = max(0, $daily * ($lead + $target) + $safety - $have);
            $belowAlert = $alert > 0 && $have <= $alert;
            $mustOrder = $belowAlert || ($daily > 0 && $have <= $reorderPoint);

            $rows[] = ['_urgent' => $mustOrder ? 0 : 1, '_cover' => $cover ?? INF, 'row' => array_filter([
                'Bahan' => $s->supplies_name,
                'Stok' => $this->qtyFmt($have).' '.$this->nameOf('unit', $unit),
                'Pemakaian/Hari' => $daily > 0 ? $this->qtyFmt(round($daily, 2)) : 'tidak ada pemakaian produksi',
                'Cukup Untuk' => $cover !== null ? $this->qtyFmt(round($cover, 1)).' hari' : null,
                'Lead Time' => $lead.' hari ('.$leadSource.')',
                'Batas Peringatan' => $alert > 0 ? $this->qtyFmt($alert).($belowAlert ? ' — DI BAWAH' : '') : null,
                'Status' => $mustOrder ? 'Perlu order sekarang' : 'Aman',
                'Saran Beli' => $suggest > 0 ? $this->qtyFmt(ceil($suggest)).' '.$this->nameOf('unit', $unit) : null,
            ], fn ($v) => $v !== null)];
        }

        usort($rows, fn ($a, $b) => [$a['_urgent'], $a['_cover']] <=> [$b['_urgent'], $b['_cover']]);
        $urgent = count(array_filter($rows, fn ($r) => $r['_urgent'] === 0));
        $rows = array_column(array_slice($rows, 0, $limit), 'row');

        return [
            'dasar_hitung' => 'Pemakaian produksi '.$usageDays.' hari terakhir; saran beli cukup untuk lead time + '.$target.' hari.',
            'perlu_order' => $urgent,
            'bahan' => $rows ?: 'Tidak ada bahan yang perlu dibeli.',
            'catatan' => 'Ini saran hitungan, bukan keputusan. Stok hanya di satuan default bahan; cek juga PO yang sedang berjalan sebelum order.',
        ];
    }

    /** Master bahan dulu; kalau kosong pakai rata-rata PO → penerimaan barang 180 hari; terakhir default. */
    private function leadTime(object $supply): array
    {
        if ((int) $supply->lead_time_days > 0) {
            return [(int) $supply->lead_time_days, 'master bahan'];
        }
        $this->db->assertTable('supplies_variants');
        $this->db->assertTable('purchase_orders');
        $avg = $this->db->table('purchase_orders_details')
            ->join('supplies_variants', 'supplies_variants.supplies_variant_id', '=', 'purchase_orders_details.supplies_variant_id')
            ->join('purchase_orders', 'purchase_orders.po_id', '=', 'purchase_orders_details.po_id')
            ->joinSub(
                $this->db->table('purchase_delivery_orders')->selectRaw('po_id, MIN(pdo_date) as first_pdo')->groupBy('po_id'),
                'pdo', 'pdo.po_id', '=', 'purchase_orders.po_id'
            )
            ->where('supplies_variants.supplies_id', $supply->supplies_id)
            ->where('purchase_orders.status', '>', 0)
            ->where('purchase_orders.po_date', '>=', now()->subDays(180)->toDateString())
            ->selectRaw('AVG(DATEDIFF(pdo.first_pdo, purchase_orders.po_date)) as lt')
            ->value('lt');

        return $avg !== null
            ? [max(1, (int) ceil((float) $avg)), 'riwayat penerimaan']
            : [self::DEFAULT_LEAD_DAYS, 'perkiraan'];
    }
}
