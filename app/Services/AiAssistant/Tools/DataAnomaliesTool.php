<?php

namespace App\Services\AiAssistant\Tools;

use App\Services\AiAssistant\ReadOnlyDb;
use App\Services\AiAssistant\Tools\Concerns\CauseEstimates;
use App\Services\AiAssistant\Tools\Concerns\TrackingHelpers;
use App\Services\AiAssistant\Vocabulary;

/**
 * Cek data yang perlu perhatian: stok minus, dokumen menunggu ACC terlalu
 * lama, transfer stok sudah dikirim tapi belum diterima, selisih opname
 * besar, dan hutang lewat jatuh tempo. Tiap temuan membawa perkiraan
 * penyebab operasional dalam persen (lihat CauseEstimates).
 */
class DataAnomaliesTool implements ToolInterface
{
    use CauseEstimates;
    use TrackingHelpers;

    private const CHECKS = ['stok minus', 'pending lama', 'transfer belum diterima', 'selisih opname', 'hutang jatuh tempo'];

    /** Modul => [tabel, status yang berarti masih menunggu ACC]. */
    private const PENDING = [
        'Pengiriman' => ['sales_orders', [1]],
        'Pembelian' => ['purchase_orders', [1]],
        'Tanda Terima' => ['purchase_order_tts', [1]],
        'Produksi' => ['productions', [1, 4]],
        'Transfer Stok' => ['stock_transfers', [1]],
        'Stok Opname Produk' => ['stock_opnames', [1]],
        'Stok Opname Bahan' => ['stock_opname_bahans', [1]],
        'Retur Produk Customer' => ['customer_product_returns', [1]],
        'Retur Bahan Customer' => ['customer_supply_returns', [1]],
        'Kas Besar' => ['cashes', [1]],
        'Operasional Admin' => ['cash_admins', [1]],
        'Operasional Gudang' => ['cash_gudangs', [1]],
        'Operasional Armada' => ['cash_armadas', [1]],
        'Operasional Sales' => ['cash_sales', [1]],
    ];

    /** stock_transfers.status 2 = Kirim (sudah dikirim, belum diterima gudang tujuan). */
    private const TRANSFER_SENT = 2;

    public function __construct(protected ReadOnlyDb $db, protected Vocabulary $vocab) {}

    public function name(): string
    {
        return 'data_anomalies';
    }

    public function description(): string
    {
        return 'Find data that needs attention: stok minus, documents waiting for ACC too long (pending lama), stock transfers sent but not received, large stock opname differences, overdue payables (hutang jatuh tempo). Use for "ada yang janggal?", "apa yang belum di-ACC?", "cek masalah data hari ini". Read-only.';
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'cek' => ['type' => 'string', 'enum' => ['semua', ...self::CHECKS], 'description' => 'Default semua'],
                'min_hari' => ['type' => 'integer', 'description' => 'Pending/transfer dianggap lama setelah N hari. Default 3.'],
                'limit' => ['type' => 'integer', 'description' => 'Baris per pemeriksaan, default 5, max 30'],
            ],
        ];
    }

    public function execute(array $args): array
    {
        $check = in_array($args['cek'] ?? null, self::CHECKS, true) ? $args['cek'] : 'semua';
        $minDays = max(0, (int) ($args['min_hari'] ?? 3));
        $limit = max(1, min((int) ($args['limit'] ?? ($check === 'semua' ? 5 : 15)), 30));

        $result = ['tanggal_cek' => now()->toDateString()];
        $run = fn (string $name) => $check === 'semua' || $check === $name;

        if ($run('stok minus')) {
            $result['stok_minus'] = $this->negativeStock($limit);
        }
        if ($run('pending lama')) {
            $result['pending_lama'] = $this->pendingTooLong($minDays, $limit);
        }
        if ($run('transfer belum diterima')) {
            $result['transfer_belum_diterima'] = $this->transfersNotReceived($minDays, $limit);
        }
        if ($run('selisih opname')) {
            $result['selisih_opname'] = $this->opnameDifferences($limit);
        }
        if ($run('hutang jatuh tempo')) {
            $result['hutang_jatuh_tempo'] = $this->overduePayables($limit);
        }
        $result['catatan'] = 'Fakta data, bukan vonis. Sampaikan perkiraan_penyebab apa adanya (persen). Jangan sebut bug/error sistem. alasan_bila_diminta hanya dijelaskan kalau user minta detail.';

        return $result;
    }

    private function negativeStock(int $limit): array
    {
        $out = [];
        $small = 0;
        $doubled = 0;
        foreach ([
            ['product_stocks', 'ps_stock', 'product_variant_id', 'Produk', 1],
            ['supplies_stocks', 'ss_stock', 'supplies_id', 'Bahan', 2],
        ] as [$table, $qty, $itemCol, $kind, $logType]) {
            $rows = $this->db->table($table)->where($qty, '<', 0)->orderBy($qty)->limit($limit)
                ->get([$itemCol, 'warehouse_id', 'unit_id', $qty]);
            foreach ($rows as $r) {
                $small += abs((float) $r->{$qty}) <= 5 ? 1 : 0;
                $doubled += $this->hasDoubleOut($logType, (int) $r->{$itemCol}, (int) $r->warehouse_id) ? 1 : 0;
                $out[] = [
                    'Jenis' => $kind,
                    'Barang' => $kind === 'Produk' ? $this->variantName((int) $r->{$itemCol}) : $this->nameOf('bahan', $r->{$itemCol}),
                    'Gudang' => $this->nameOf('gudang', $r->warehouse_id),
                    'Stok' => $this->qtyFmt((float) $r->{$qty}).' '.$this->nameOf('unit', $r->unit_id),
                ];
            }
        }
        if ($out === []) {
            return ['jumlah' => 0, 'data' => 'Tidak ada stok minus.'];
        }

        // Minus kecil = biasanya urutan input; keluar dobel di log = klik ulang saat jaringan lambat.
        $n = count($out);

        return ['jumlah' => $n, 'data' => $out] + $this->estimateCauses('stok_minus', [
            'Urutan input (barang keluar dicatat/di-ACC sebelum barang masuk)' => (int) round(20 * $small / $n),
            'Input dobel (koneksi lambat / klik ulang)' => (int) round(30 * $doubled / $n),
        ]);
    }

    /** Ada 2+ log keluar dengan kode & qty sama untuk barang+gudang ini dalam 90 hari. */
    private function hasDoubleOut(int $logType, int $itemId, int $warehouseId): bool
    {
        return $this->db->table('log_stocks')
            ->where('log_type', $logType)->where('log_item_id', $itemId)->where('warehouse_id', $warehouseId)
            ->where('log_category', 2)
            ->where('log_date', '>=', now()->subDays(90)->toDateString())
            ->groupBy('log_kode', 'log_jumlah')
            ->havingRaw('COUNT(*) > 1')
            ->exists();
    }

    private function pendingTooLong(int $minDays, int $limit): array
    {
        $cutoff = now()->subDays($minDays)->toDateString();
        $out = [];
        $total = 0;
        $stale = 0;
        foreach (self::PENDING as $label => [$table, $statuses]) {
            $dateCol = $this->vocab->dateField($table);
            if ($dateCol === null) {
                continue;
            }
            $allowed = $this->db->allowedColumns($table);
            $q = $this->db->table($table)->whereIn('status', $statuses)->where($dateCol, '<', $cutoff.' 00:00:00');
            // Draft opname masih diisi petugas, belum diajukan ACC.
            if (in_array('is_draft', $allowed, true)) {
                $q->where(fn ($w) => $w->where('is_draft', 0)->orWhereNull('is_draft'));
            }
            $count = (clone $q)->count();
            if ($count === 0) {
                continue;
            }
            $total += $count;
            $stale += (clone $q)->where($dateCol, '<', now()->subDays(30)->toDateString().' 00:00:00')->count();
            $codeCol = $this->vocab->codeFields($table)[0] ?? null;
            // Kas tidak punya kode dokumen; nominal membantu user mengenali barisnya.
            $amountCol = $this->vocab->amountField($table);
            $cols = array_values(array_filter([$codeCol, $dateCol, $amountCol, in_array('created_by', $allowed, true) ? 'created_by' : null]));
            $oldest = $q->orderBy($dateCol)->limit($limit)->get($cols)->map(fn ($r) => array_filter([
                'Kode' => $codeCol ? (string) $r->{$codeCol} : null,
                'Tanggal' => substr((string) $r->{$dateCol}, 0, 10),
                'Umur' => $this->ageDays($r->{$dateCol}).' hari',
                'Nominal' => $amountCol ? $this->rupiah((float) $r->{$amountCol}) : null,
                'Dibuat Oleh' => isset($r->created_by) ? $this->nameOf('staff', $r->created_by) : null,
            ]))->all();
            $out[] = ['modul' => $label, 'jumlah' => $count, 'tertua' => $oldest];
        }
        usort($out, fn ($a, $b) => $b['jumlah'] <=> $a['jumlah']);

        if ($out === []) {
            return ['pesan' => 'Tidak ada dokumen menunggu ACC lebih dari '.$minDays.' hari.'];
        }

        // Pending > 30 hari jarang masih "menunggu"; lebih mungkin transaksi batal yang tidak dihapus.
        return ['lebih_dari_hari' => $minDays, 'per_modul' => $out] + $this->estimateCauses('pending_lama', [
            'Sebenarnya batal tapi tidak dihapus' => (int) round(30 * $stale / $total),
            'Lupa / belum sempat di-ACC' => (int) round(15 * ($total - $stale) / $total),
        ]);
    }

    private function transfersNotReceived(int $minDays, int $limit): array
    {
        $q = $this->db->table('stock_transfers')
            ->where('status', self::TRANSFER_SENT)
            ->where('transfer_date', '<=', now()->subDays($minDays)->toDateString().' 23:59:59');
        $count = (clone $q)->count();
        if ($count === 0) {
            return ['jumlah' => 0, 'data' => 'Tidak ada transfer yang belum diterima.'];
        }
        $oldestAge = $this->ageDays((clone $q)->min('transfer_date'));
        $rows = $q->orderBy('transfer_date')->limit($limit)
            ->get(['transfer_code', 'transfer_date', 'from_warehouse_id', 'to_warehouse_id', 'created_by'])
            ->map(fn ($r) => [
                'Kode' => $r->transfer_code,
                'Tanggal' => substr((string) $r->transfer_date, 0, 10),
                'Umur' => $this->ageDays($r->transfer_date).' hari',
                'Dari' => $this->nameOf('gudang', $r->from_warehouse_id),
                'Ke' => $this->nameOf('gudang', $r->to_warehouse_id),
                'Dibuat Oleh' => $this->nameOf('staff', $r->created_by),
            ])->all();

        // Transfer antar gudang biasanya sampai < 3 hari; lebih lama = hampir pasti lupa klik Terima.
        $boost = $oldestAge <= 3
            ? ['Barang masih di perjalanan' => 20]
            : ['Gudang tujuan lupa klik Terima' => 20, 'Barang masih di perjalanan' => -10];

        return ['jumlah' => $count, 'status' => 'Kirim (belum diterima)', 'data' => $rows] + $this->estimateCauses('transfer_belum_diterima', $boost);
    }

    /** Selisih fisik vs sistem terbesar di opname 30 hari terakhir (baris yang benar-benar dihitung). */
    private function opnameDifferences(int $limit): array
    {
        $since = now()->subDays(30)->toDateString().' 00:00:00';
        $out = [];
        foreach ([
            ['stock_opnames', 'sto_id', 'sto_code', 'sto_date', 'stock_opname_lines', 'sol', "CONCAT(COALESCE(sol_product_name,''),' ',COALESCE(sol_variant_name,''))"],
            ['stock_opname_bahans', 'stob_id', 'stob_code', 'stob_date', 'stock_opname_bahan_lines', 'sobl', 'sobl_supplies_name'],
        ] as [$header, $fk, $codeCol, $dateCol, $lines, $p, $nameExpr]) {
            $docs = $this->activeOnly($this->db->table($header), $header)
                ->where($dateCol, '>=', $since)
                ->pluck($codeCol, $fk)->all();
            if ($docs === []) {
                continue;
            }
            $this->db->assertTable($lines);
            $rows = $this->db->table($lines)
                ->whereIn($fk, array_keys($docs))
                ->whereNotNull($p.'_counted_qty')
                ->where(fn ($w) => $w->where($p.'_use_system_stock', 0)->orWhereNull($p.'_use_system_stock'))
                ->whereRaw("`{$p}_counted_qty` <> `{$p}_system_qty_final`")
                ->selectRaw("`{$fk}` as doc, {$nameExpr} as nama, `{$p}_unit_name` as satuan, `{$p}_counted_qty` as fisik, `{$p}_system_qty_final` as sistem")
                ->orderByRaw("ABS(`{$p}_counted_qty` - `{$p}_system_qty_final`) DESC")
                ->limit($limit)
                ->get();
            foreach ($rows as $r) {
                $diff = (float) $r->fisik - (float) $r->sistem;
                $rowBoost = $this->opnameRowBoost((float) $r->fisik, (float) $r->sistem);
                $out[] = ['_abs' => abs($diff), 'boost' => $rowBoost, 'row' => [
                    'Kode Opname' => $docs[$r->doc] ?? '-',
                    'Barang' => trim((string) $r->nama),
                    'Sistem' => $this->qtyFmt((float) $r->sistem).' '.$r->satuan,
                    'Fisik' => $this->qtyFmt((float) $r->fisik).' '.$r->satuan,
                    'Selisih' => $this->qtyFmt($diff),
                    'Dugaan Utama' => $this->estimateCauses('selisih_opname', $rowBoost)['perkiraan_penyebab'][0]['Penyebab'] ?? '-',
                ]];
            }
        }
        if ($out === []) {
            return ['periode' => '30 hari terakhir', 'data' => 'Tidak ada selisih opname.'];
        }
        usort($out, fn ($a, $b) => $b['_abs'] <=> $a['_abs']);
        $out = array_slice($out, 0, $limit);

        // Distribusi keseluruhan = rata-rata sinyal per baris yang ditampilkan.
        $boost = [];
        foreach (array_column($out, 'boost') as $b) {
            foreach ($b as $cause => $w) {
                $boost[$cause] = ($boost[$cause] ?? 0) + $w;
            }
        }
        $boost = array_map(fn ($w) => (int) round($w / count($out)), $boost);

        return ['periode' => '30 hari terakhir', 'data' => array_column($out, 'row')] + $this->estimateCauses('selisih_opname', $boost);
    }

    /** Sama dengan aturan dashboard: PO belum terbayar (pembayaran = 1), PO & invoice aktif. */
    private function overduePayables(int $limit): array
    {
        $today = now()->toDateString();
        $invoices = $this->db->table('purchase_order_detail_invoices')
            ->where('poi_due', '<', $today)
            ->where('status', '>=', 0)
            ->orderBy('poi_due')
            ->get(['po_id', 'poi_code', 'poi_due', 'poi_total']);
        if ($invoices->isEmpty()) {
            return ['jumlah' => 0, 'data' => 'Tidak ada hutang lewat jatuh tempo.'];
        }
        $pos = $this->db->table('purchase_orders')
            ->whereIn('po_id', $invoices->pluck('po_id')->unique()->all())
            ->where('pembayaran', 1)
            ->where('status', '>', 0)
            ->get(['po_id', 'po_number', 'po_supplier'])
            ->keyBy('po_id');

        $rows = $invoices->filter(fn ($i) => $pos->has($i->po_id))->values();
        if ($rows->isEmpty()) {
            return ['jumlah' => 0, 'data' => 'Tidak ada hutang lewat jatuh tempo.'];
        }
        $total = $rows->sum(fn ($i) => (float) $i->poi_total);
        // Telat > 60 hari jarang dibiarkan pemasok; lebih mungkin sudah dibayar tapi status belum diubah.
        $veryLate = $rows->filter(fn ($i) => $this->ageDays($i->poi_due) > 60)->count() / $rows->count();

        $causes = $this->estimateCauses('hutang_jatuh_tempo', [
            'Sudah dibayar tapi belum ditandai lunas' => (int) round(25 * $veryLate),
        ]);

        return [
            'jumlah' => $rows->count(),
            'total_nominal' => $this->rupiah($total),
            'data' => $rows->take($limit)->map(fn ($i) => [
                'No. Invoice' => $i->poi_code,
                'No. PO' => $pos[$i->po_id]->po_number,
                'Pemasok' => $this->nameOf('pemasok', $pos[$i->po_id]->po_supplier),
                'Jatuh Tempo' => $i->poi_due,
                'Telat' => $this->ageDays($i->poi_due).' hari',
                'Nominal' => $this->rupiah((float) $i->poi_total),
            ])->all(),
        ] + $causes;
    }

    private function variantName(int $variantId): string
    {
        $v = $this->db->table('product_variants')->where('product_variant_id', $variantId)
            ->first(['product_id', 'product_variant_name', 'product_variant_sku']);
        if (!$v) {
            return '-';
        }
        $product = $this->db->table('products')->where('product_id', $v->product_id)->value('product_name');

        return trim($product.' '.$v->product_variant_name.' ('.$v->product_variant_sku.')');
    }
}
