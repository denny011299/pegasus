<?php

namespace App\Services\AiAssistant\Tools;

use App\Services\AiAssistant\ReadOnlyDb;
use App\Services\AiAssistant\Tools\Concerns\ItemMatcher;
use App\Services\AiAssistant\Tools\Concerns\TrackingHelpers;
use App\Services\AiAssistant\Vocabulary;

/**
 * Kartu stok satu barang: stok sekarang per gudang/satuan, rekap masuk &
 * keluar per sumber (keterangan riwayat stok) dalam periode, dan mutasi
 * terakhir beserta saldo — untuk "stok X berapa / kenapa berkurang".
 */
class ItemStockTool implements ToolInterface
{
    use ItemMatcher;
    use TrackingHelpers;

    public function __construct(protected ReadOnlyDb $db, protected Vocabulary $vocab) {}

    public function name(): string
    {
        return 'item_stock';
    }

    public function description(): string
    {
        return 'Stock card of ONE product/bahan: current stock per gudang & satuan, masuk/keluar totals in a period grouped by source (opname, transfer, pengiriman, produksi, pembelian...), and the latest movements with running balance. Use for "stok X berapa", "kenapa stok X berkurang/bertambah". Suggests similar items when not exact. Read-only.';
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'item' => ['type' => 'string', 'description' => 'Nama barang, SKU atau barcode'],
                'jenis' => ['type' => 'string', 'enum' => ['semua', 'produk', 'bahan']],
                'gudang' => ['type' => 'string', 'description' => 'Nama gudang (opsional)'],
                'date_from' => ['type' => 'string', 'description' => 'YYYY-MM-DD, default 30 hari terakhir'],
                'date_to' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
                'limit' => ['type' => 'integer', 'description' => 'Jumlah mutasi terakhir, default 15, max 50'],
            ],
            'required' => ['item'],
        ];
    }

    public function execute(array $args): array
    {
        $keyword = (string) ($args['item'] ?? $args['keyword'] ?? '');
        $jenis = in_array($args['jenis'] ?? null, ['produk', 'bahan'], true) ? $args['jenis'] : 'semua';
        $match = $this->matchItems($keyword, $jenis);
        // Kartu stok hanya untuk SATU barang; lebih dari satu → minta user memilih.
        if (($notFound = $this->itemNotFound($keyword, $match, 1)) !== null) {
            return $notFound;
        }

        $item = $match['items'][0];
        $isProduk = $item['jenis'] === 'produk';
        $warehouseIds = $this->warehouseFilter((string) ($args['gudang'] ?? ''));
        [$from, $to] = $this->period($args, 30);
        $limit = max(1, min((int) ($args['limit'] ?? 15), 50));

        $logs = fn () => $this->activeOnly($this->db->table('log_stocks'), 'log_stocks')
            ->where('log_type', $isProduk ? 1 : 2)
            ->where('log_item_id', $item['id'])
            ->when($warehouseIds !== null, fn ($q) => $q->whereIn('warehouse_id', $warehouseIds))
            ->whereBetween('log_date', [$from.' 00:00:00', $to.' 23:59:59']);

        $latest = $logs()
            ->orderByDesc('log_date')->orderByDesc('log_id')
            ->limit($limit)
            ->get(['log_date', 'log_kode', 'log_category', 'log_jumlah', 'log_saldo', 'unit_id', 'warehouse_id', 'log_notes', 'staff_id'])
            ->map(fn ($r) => [
                'Tanggal' => $r->log_date,
                'Kode' => $r->log_kode,
                'Arah' => (int) $r->log_category === 1 ? 'Masuk' : 'Keluar',
                'Qty' => $this->qtyFmt((float) $r->log_jumlah).' '.$this->nameOf('unit', $r->unit_id),
                'Saldo' => $this->qtyFmt((float) $r->log_saldo),
                'Gudang' => $this->nameOf('gudang', $r->warehouse_id),
                'Keterangan' => (string) $r->log_notes,
                'Oleh' => $this->nameOf('staff', $r->staff_id),
            ])->all();

        return [
            'ditemukan' => true,
            'barang' => $this->presentItem($item),
            'stok_sekarang' => $this->currentStock($isProduk, $item['id'], $warehouseIds),
            'periode_mutasi' => $this->periodLabel($from, $to),
            'rekap_mutasi' => $this->summarize($logs()),
            'mutasi_terakhir' => $latest,
            'catatan' => 'Saldo = sisa stok di gudang & satuan baris itu setelah mutasi. Dokumen dihapus tidak dihitung.',
        ];
    }

    /** @param list<int>|null $warehouseIds */
    private function currentStock(bool $isProduk, int $id, ?array $warehouseIds): array
    {
        [$table, $itemCol, $qtyCol] = $isProduk
            ? ['product_stocks', 'product_variant_id', 'ps_stock']
            : ['supplies_stocks', 'supplies_id', 'ss_stock'];

        $rows = $this->db->table($table)
            ->where($itemCol, $id)
            ->when($warehouseIds !== null, fn ($q) => $q->whereIn('warehouse_id', $warehouseIds))
            ->selectRaw('warehouse_id, unit_id, SUM(`'.$qtyCol.'`) as total')
            ->groupBy('warehouse_id', 'unit_id')
            ->get();

        $perWarehouse = [];
        foreach ($rows as $r) {
            if ((float) $r->total == 0.0) {
                continue;
            }
            $perWarehouse[$this->nameOf('gudang', $r->warehouse_id)][] = $r;
        }
        if ($perWarehouse === []) {
            return [['Gudang' => 'Semua gudang', 'Stok' => '0']];
        }

        $out = [];
        foreach ($perWarehouse as $warehouse => $unitRows) {
            $out[] = ['Gudang' => $warehouse, 'Stok' => $this->qtyByUnit($unitRows)];
        }

        return $out;
    }

    /**
     * Masuk/keluar per sumber. Keterangan riwayat stok memuat kode dokumen
     * (mis. "Serah terima bahan WO-261006-002"), jadi kode dibuang supaya
     * baris dengan sumber sama tergabung.
     */
    private function summarize($query): array
    {
        $groups = $query
            ->selectRaw('log_category, unit_id, log_notes, SUM(log_jumlah) as total, COUNT(*) as n')
            ->groupBy('log_category', 'unit_id', 'log_notes')
            ->limit(3000)
            ->get();

        $bucket = [];
        foreach ($groups as $g) {
            $dir = (int) $g->log_category === 1 ? 'Masuk' : 'Keluar';
            $source = trim(preg_replace('/\s+/', ' ', preg_replace('/\b[A-Z]{1,6}[-\/]?\d[\w\/-]*/', '', (string) $g->log_notes) ?? '') ?? '');
            $source = $source !== '' ? $source : 'Lainnya';
            $key = $source.'|'.$g->unit_id;
            $bucket[$dir][$key] ??= ['sumber' => $source, 'unit_id' => $g->unit_id, 'total' => 0.0, 'n' => 0];
            $bucket[$dir][$key]['total'] += (float) $g->total;
            $bucket[$dir][$key]['n'] += (int) $g->n;
        }

        $out = [];
        foreach (['Masuk', 'Keluar'] as $dir) {
            $rows = array_values($bucket[$dir] ?? []);
            usort($rows, fn ($a, $b) => $b['total'] <=> $a['total']);
            $out[$dir] = array_map(
                fn ($r) => $r['sumber'].': '.$this->qtyFmt($r['total']).' '.$this->nameOf('unit', $r['unit_id']).' ('.$r['n'].'x)',
                array_slice($rows, 0, 8),
            );
            if ($out[$dir] === []) {
                $out[$dir] = ['Tidak ada'];
            }
        }

        return $out;
    }

    /** @return list<int>|null null = semua gudang */
    private function warehouseFilter(string $name): ?array
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }
        $ids = $this->db->table('warehouses')
            ->where('warehouse_name', 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], $name).'%')
            ->pluck('id')->map(fn ($id) => (int) $id)->all();

        return $ids === [] ? [-1] : $ids;
    }
}
