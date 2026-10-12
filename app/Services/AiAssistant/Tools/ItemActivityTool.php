<?php

namespace App\Services\AiAssistant\Tools;

use App\Services\AiAssistant\ReadOnlyDb;
use App\Services\AiAssistant\Tools\Concerns\ItemMatcher;
use App\Services\AiAssistant\Vocabulary;
use Illuminate\Database\Query\Builder;
use InvalidArgumentException;

/**
 * Telusuri satu barang di dokumen transaksi: berapa dokumen dan total qty
 * per satuan di pengiriman, transfer stok, stok opname, pembelian,
 * produksi dan retur — plus daftar dokumennya.
 *
 * Barang yang tidak cocok persis tidak pernah ditebak: tool mengembalikan
 * saran dan asisten wajib konfirmasi ke user dulu.
 */
class ItemActivityTool implements ToolInterface
{
    use ItemMatcher;

    /**
     * Modul => header/detail. item = kolom barang di detail; jenis = produk
     * (varian produk) atau bahan (bahan mentah). Pembelian mencatat varian
     * bahan per pemasok, jadi id bahan dipetakan ke varian bahannya dulu.
     */
    private const SOURCES = [
        'pengiriman' => ['label' => 'Pengiriman', 'header' => 'sales_orders', 'date' => 'so_date', 'detail' => 'sales_order_details', 'fk' => 'so_id', 'item' => 'product_variant_id', 'jenis' => 'produk', 'qty' => 'sod_qty',
            'fields' => ['so_number', 'so_invoice_no', 'so_date', 'so_customer', 'retail_warehouse_id', 'status']],
        'transfer stok' => ['label' => 'Transfer Stok', 'header' => 'stock_transfers', 'date' => 'transfer_date', 'detail' => 'stock_transfer_details', 'fk' => 'st_id', 'item' => 'product_variant_id', 'jenis' => 'produk', 'qty' => 'qty',
            'fields' => ['transfer_code', 'transfer_date', 'from_warehouse_id', 'to_warehouse_id', 'source_type', 'status']],
        'stok opname produk' => ['label' => 'Stok Opname Produk', 'header' => 'stock_opnames', 'date' => 'sto_date', 'detail' => 'stock_opname_lines', 'fk' => 'sto_id', 'item' => 'product_variant_id', 'jenis' => 'produk', 'qty' => 'sol_counted_qty',
            'fields' => ['sto_code', 'sto_date', 'warehouse_id', 'status']],
        'stok opname bahan' => ['label' => 'Stok Opname Bahan', 'header' => 'stock_opname_bahans', 'date' => 'stob_date', 'detail' => 'stock_opname_bahan_lines', 'fk' => 'stob_id', 'item' => 'supplies_id', 'jenis' => 'bahan', 'qty' => 'sobl_counted_qty',
            'fields' => ['stob_code', 'stob_date', 'warehouse_id', 'status']],
        'pembelian' => ['label' => 'Pembelian', 'header' => 'purchase_orders', 'date' => 'po_date', 'detail' => 'purchase_orders_details', 'fk' => 'po_id', 'item' => 'supplies_variant_id', 'jenis' => 'bahan', 'qty' => 'pod_qty',
            'fields' => ['po_number', 'po_date', 'po_supplier', 'warehouse_id', 'status']],
        'produksi' => ['label' => 'Produksi', 'header' => 'productions', 'date' => 'production_date', 'detail' => 'production_details', 'fk' => 'production_id', 'item' => 'product_variant_id', 'jenis' => 'produk', 'qty' => 'pd_qty',
            'fields' => ['production_code', 'production_date', 'warehouse_id', 'status']],
        'retur produk' => ['label' => 'Retur Produk Customer', 'header' => 'customer_product_returns', 'date' => 'return_date', 'detail' => 'customer_product_return_details', 'fk' => 'return_id', 'item' => 'product_variant_id', 'jenis' => 'produk', 'qty' => 'qty',
            'fields' => ['return_number', 'return_date', 'customer_id', 'ref_number', 'status']],
        'retur bahan' => ['label' => 'Retur Bahan Customer', 'header' => 'customer_supply_returns', 'date' => 'return_date', 'detail' => 'customer_supply_return_details', 'fk' => 'return_id', 'item' => 'supplies_id', 'jenis' => 'bahan', 'qty' => 'qty',
            'fields' => ['return_number', 'return_date', 'customer_id', 'ref_number', 'status']],
    ];

    /** Kata yang dipakai user => modul di SOURCES. */
    private const MODULE_ALIASES = [
        'pengiriman' => ['pengiriman'], 'penjualan' => ['pengiriman'], 'sales order' => ['pengiriman'], 'so' => ['pengiriman'],
        'transfer stok' => ['transfer stok'], 'transfer' => ['transfer stok'], 'stock transfer' => ['transfer stok'], 'stok transfer' => ['transfer stok'], 'mutasi gudang' => ['transfer stok'], 'st' => ['transfer stok'],
        'stok opname' => ['stok opname produk', 'stok opname bahan'], 'opname' => ['stok opname produk', 'stok opname bahan'], 'stock opname' => ['stok opname produk', 'stok opname bahan'],
        'stok opname produk' => ['stok opname produk'], 'stok opname bahan' => ['stok opname bahan'],
        'pembelian' => ['pembelian'], 'purchase order' => ['pembelian'], 'po' => ['pembelian'],
        'produksi' => ['produksi'],
        'retur' => ['retur produk', 'retur bahan'], 'pengembalian' => ['retur produk', 'retur bahan'], 'retur produk' => ['retur produk'], 'retur bahan' => ['retur bahan'],
    ];

    /** @var array<int, string> */
    private array $unitNames = [];

    public function __construct(protected ReadOnlyDb $db, protected Vocabulary $vocab) {}

    public function name(): string
    {
        return 'item_activity';
    }

    public function description(): string
    {
        return 'Trace one product/bahan across transactions: how many documents and total qty per unit in pengiriman, transfer stok, stok opname, pembelian, produksi and retur, with the document list. Use for "berapa transfer stok produk X hari ini", "produk X dikirim ke mana saja". If the item is not found exactly it returns suggestions instead. Read-only.';
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'item' => ['type' => 'string', 'description' => 'Nama barang, SKU atau barcode'],
                'modul' => [
                    'type' => 'string',
                    'description' => 'semua (default), pengiriman, transfer stok, stok opname, pembelian, produksi, retur',
                ],
                'date_from' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
                'date_to' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
                'limit' => ['type' => 'integer', 'description' => 'Dokumen per modul, max 50'],
                'include_inactive' => ['type' => 'boolean', 'description' => 'Ikutkan dokumen dihapus/ditolak/batal. Default false.'],
            ],
            'required' => ['item'],
        ];
    }

    public function execute(array $args): array
    {
        $keyword = (string) ($args['item'] ?? $args['keyword'] ?? $args['sku'] ?? '');
        $modules = $this->resolveModules((string) ($args['modul'] ?? $args['module'] ?? 'semua'));
        $jenisNeeded = array_unique(array_map(fn ($m) => self::SOURCES[$m]['jenis'], $modules));
        $jenis = count($jenisNeeded) === 1 ? $jenisNeeded[0] : 'semua';

        $match = $this->matchItems($keyword, $jenis);
        if (!in_array($match['status'], ['persis', 'cocok'], true)) {
            return $this->notFound($keyword, $match);
        }
        if ($match['total'] > 10) {
            return [
                'ditemukan' => false,
                'kata_kunci' => $keyword,
                'pesan' => $match['total'].' barang cocok dengan "'.$keyword.'". Tanyakan ke user barang yang mana (sebut SKU).',
                'pilihan' => array_map(fn ($c) => $this->presentItem($c), $match['items']),
            ];
        }

        $ids = ['produk' => [], 'bahan' => []];
        foreach ($match['items'] as $c) {
            $ids[$c['jenis']][] = $c['id'];
        }

        $single = count($modules) === 1;
        $limit = max(1, min((int) ($args['limit'] ?? ($single ? 15 : 5)), (int) config('ai_assistant.max_rows', 50)));
        $from = $this->dayBound($args['date_from'] ?? null, '00:00:00');
        $to = $this->dayBound($args['date_to'] ?? null, '23:59:59');
        $withInactive = !empty($args['include_inactive']);

        $rekap = [];
        foreach ($modules as $module) {
            $src = self::SOURCES[$module];
            $itemIds = $src['item'] === 'supplies_variant_id'
                ? $this->suppliesVariantIds($ids['bahan'])
                : $ids[$src['jenis']];
            if ($itemIds === []) {
                continue;
            }
            $rekap[] = $this->traceModule($src, $itemIds, $from, $to, $withInactive, $limit);
        }

        $result = [
            'ditemukan' => true,
            'barang' => array_map(fn ($c) => $this->presentItem($c), $match['items']),
            'periode' => $from === null && $to === null
                ? 'Semua tanggal'
                : (substr((string) $from, 0, 10) ?: 'awal').' s/d '.(substr((string) $to, 0, 10) ?: 'sekarang'),
            'rekap' => $rekap,
        ];
        if ($match['status'] === 'cocok' && $match['total'] > 1) {
            $result['catatan'] = 'Kata kunci cocok dengan '.$match['total'].' barang; rekap menggabungkan semuanya. Sebutkan ini ke user.';
        }
        if (!$withInactive) {
            $result['catatan_status'] = 'Dokumen dihapus/ditolak/batal tidak ikut dihitung.';
        }

        return $result;
    }

    /** @param list<int> $itemIds */
    private function traceModule(array $src, array $itemIds, ?string $from, ?string $to, bool $withInactive, int $limit): array
    {
        $header = $src['header'];
        $detail = $src['detail'];
        $pk = $this->db->primaryKey($header);
        $this->db->assertTable($detail);

        $docs = $this->db->table($header)
            ->whereIn($pk, fn (Builder $q) => $this->activeLines($q->from($detail)->select($src['fk']), $detail)
                ->whereIn($src['item'], $itemIds));
        if (!$withInactive) {
            $this->activeLines($docs, $header);
        }
        if ($from !== null) {
            $docs->where($src['date'], '>=', $from);
        }
        if ($to !== null) {
            $docs->where($src['date'], '<=', $to);
        }

        $count = (clone $docs)->count();
        $out = ['modul' => $src['label'], 'jumlah_dokumen' => $count];
        if ($count === 0) {
            return $out;
        }

        $lines = fn () => $this->activeLines($this->db->table($detail), $detail)
            ->whereIn($src['item'], $itemIds)
            ->whereIn($src['fk'], (clone $docs)->select($pk));

        $totals = $lines()
            ->selectRaw('unit_id, SUM(`'.$src['qty'].'`) as total')
            ->groupBy('unit_id')
            ->get();
        $out['total_qty'] = $this->qtyText($totals);

        $rows = (clone $docs)
            ->select(array_values(array_unique([$pk, ...array_intersect($src['fields'], $this->db->allowedColumns($header))])))
            ->orderByDesc($src['date'])
            ->orderByDesc($pk)
            ->limit($limit)
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();

        $perDoc = [];
        $docLines = $lines()
            ->whereIn($src['fk'], array_column($rows, $pk))
            ->selectRaw('`'.$src['fk'].'` as doc, unit_id, SUM(`'.$src['qty'].'`) as total')
            ->groupBy($src['fk'], 'unit_id')
            ->get();
        foreach ($docLines as $l) {
            $perDoc[$l->doc][] = $l;
        }

        $presented = $this->vocab->present($header, $rows, false);
        foreach ($rows as $i => $row) {
            $presented[$i]['Qty Barang'] = $this->qtyText($perDoc[$row[$pk]] ?? []);
        }
        $out['dokumen'] = $presented;
        if ($count > count($rows)) {
            $out['catatan'] = 'Menampilkan '.count($rows).' dari '.$count.' dokumen terbaru.';
        }

        return $out;
    }

    /** Hide rows whose status means deleted / rejected / cancelled. */
    private function activeLines(Builder $q, string $table): Builder
    {
        $inactive = $this->vocab->inactiveStatuses($table);
        if ($inactive !== [] && in_array('status', $this->db->allowedColumns($table), true)) {
            $q->where(fn (Builder $w) => $w->whereNotIn('status', $inactive)->orWhereNull('status'));
        }

        return $q;
    }

    /** @return list<string> */
    private function resolveModules(string $modul): array
    {
        $key = trim(preg_replace('/\s+/', ' ', strtolower($modul)) ?? '');
        if ($key === '' || $key === 'semua' || $key === 'all') {
            return array_keys(self::SOURCES);
        }
        if (isset(self::SOURCES[$key])) {
            return [$key];
        }
        if (isset(self::MODULE_ALIASES[$key])) {
            return self::MODULE_ALIASES[$key];
        }

        throw new InvalidArgumentException('Modul "'.$modul.'" belum bisa ditelusuri per barang. Pilihan: semua, pengiriman, transfer stok, stok opname, pembelian, produksi, retur.');
    }

    /**
     * @param  list<int>  $suppliesIds
     * @return list<int>
     */
    private function suppliesVariantIds(array $suppliesIds): array
    {
        if ($suppliesIds === []) {
            return [];
        }

        return $this->db->table('supplies_variants')
            ->whereIn('supplies_id', $suppliesIds)
            ->pluck('supplies_variant_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function notFound(string $keyword, array $match): array
    {
        if ($match['status'] === 'saran') {
            return [
                'ditemukan' => false,
                'kata_kunci' => $keyword,
                'pesan' => 'Barang "'.$keyword.'" tidak ditemukan persis. Tanyakan ke user apakah maksudnya salah satu saran ini, lalu cari ulang dengan SKU-nya.',
                'saran' => array_map(fn ($c) => $this->presentItem($c), $match['items']),
            ];
        }

        return [
            'ditemukan' => false,
            'kata_kunci' => $keyword,
            'pesan' => 'Barang "'.$keyword.'" tidak ditemukan dan tidak ada nama/SKU yang mirip. Minta user mengecek ejaan atau SKU.',
        ];
    }

    /** @param iterable<object{unit_id: mixed, total: mixed}> $rows */
    private function qtyText(iterable $rows): string
    {
        $parts = [];
        foreach ($rows as $r) {
            $qty = (float) $r->total;
            $parts[] = rtrim(rtrim(number_format($qty, 2, ',', '.'), '0'), ',').' '.$this->unitName((int) $r->unit_id);
        }

        return $parts === [] ? '0' : implode(', ', $parts);
    }

    private function unitName(int $unitId): string
    {
        if ($this->unitNames === []) {
            $this->unitNames = $this->db->table('units')->pluck('unit_name', 'unit_id')->map(fn ($n) => (string) $n)->all();
        }

        return $this->unitNames[$unitId] ?? 'satuan';
    }

    private function dayBound(mixed $value, string $time): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value.' '.$time : $value;
    }
}
