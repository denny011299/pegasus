<?php

namespace App\Services\AiAssistant\Tools;

use App\Services\AiAssistant\ReadOnlyDb;
use App\Services\AiAssistant\Tools\Concerns\TrackingHelpers;
use App\Services\AiAssistant\Vocabulary;
use InvalidArgumentException;

/**
 * Rantai dokumen dari satu kode: Pengiriman → Invoice → Surat Jalan → Retur,
 * Pembelian → Tanda Terima → Penerimaan Barang → Invoice → Produk Bermasalah/Retur,
 * Produksi → Transfer Stok. Dokumen anak (invoice, surat jalan, transfer) dinaikkan
 * ke induknya dulu supaya alurnya utuh. Menandai tahap yang belum ada.
 */
class DocumentTraceTool implements ToolInterface
{
    use TrackingHelpers;

    public function __construct(protected ReadOnlyDb $db, protected Vocabulary $vocab) {}

    public function name(): string
    {
        return 'trace_document';
    }

    public function description(): string
    {
        return 'Trace the full chain of a document from any code in it: pengiriman/SO → invoice → surat jalan → retur; pembelian/PO → tanda terima → penerimaan barang (PDO) → invoice → produk bermasalah/retur; produksi → transfer stok. Shows each step with code, date, status and which steps are still missing. Use for "alur dokumen X", "PO ini sudah sampai mana", "SO ini sudah dikirim?". Read-only.';
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'code' => ['type' => 'string', 'description' => 'Kode dokumen apa pun di rantai (INV, SO, PO, TT, PDO, SDO, PR, ST, PBJ...)'],
            ],
            'required' => ['code'],
        ];
    }

    public function execute(array $args): array
    {
        $code = trim((string) ($args['code'] ?? ''));
        if ($code === '') {
            throw new InvalidArgumentException('code required');
        }

        $hit = (new FindDocumentTool($this->db, $this->vocab))->locateDocument($code);
        if ($hit === null) {
            return ['ditemukan' => false, 'kode' => $code, 'pesan' => 'Dokumen dengan kode tersebut tidak ditemukan.'];
        }
        [$table, $row] = $this->toRoot(...$hit);

        $steps = match ($table) {
            'sales_orders' => $this->salesChain($row),
            'purchase_orders' => $this->purchaseChain($row),
            'purchase_order_tts' => $this->ttChain($row),
            'productions' => $this->productionChain($row),
            'stock_transfers' => [$this->step('Transfer Stok', $table, $row)],
            'customer_product_returns' => $this->returnChain($row),
            default => [$this->step($this->vocab->moduleLabel($table), $table, $row)],
        };

        $missing = array_values(array_map(fn ($s) => $s['Tahap'], array_filter($steps, fn ($s) => ($s['Kode'] ?? null) === null)));

        return array_filter([
            'kode_dicari' => $code,
            'alur' => array_values(array_filter($steps, fn ($s) => ($s['Kode'] ?? null) !== null)),
            'tahap_belum_ada' => $missing ?: null,
            'catatan' => $missing ? 'Tahap yang belum ada = belum diproses di menunya (atau belum di-ACC). Sebutkan sebagai kemungkinan operasional.' : null,
        ]);
    }

    /** Dokumen anak dinaikkan ke induknya (invoice/surat jalan → SO, PDO/invoice beli/PI → PO, transfer dari produksi/retur → sumbernya). */
    private function toRoot(string $table, array $row): array
    {
        $parent = match ($table) {
            'sales_delivery_orders', 'sales_order_detail_invoices', 'customer_supply_returns' => ['sales_orders', 'so_id', $row['so_id'] ?? null],
            'purchase_delivery_orders', 'purchase_order_detail_invoices', 'product_issues', 'return_supplies' => ['purchase_orders', 'po_id', $row['po_id'] ?? null],
            'stock_transfers' => match ($row['source_type'] ?? null) {
                'production' => ['productions', 'production_id', $row['source_id'] ?? null],
                'customer_return' => ['customer_product_returns', 'return_id', $row['source_id'] ?? null],
                default => null,
            },
            default => null,
        };
        $p = $parent !== null && !empty($parent[2])
            ? $this->db->table($parent[0])->where($parent[1], $parent[2])->first()
            : null;
        if ($p) {
            return [$parent[0], (array) $p];
        }

        // Transfer tanpa source yang valid: sumber hanya terbaca dari kode "ST-<kode sumber>-n".
        if ($table === 'stock_transfers'
            && preg_match('/^ST-([A-Z]+\d+)-\d+$/i', (string) ($row['transfer_code'] ?? ''), $m)) {
            $src = (new FindDocumentTool($this->db, $this->vocab))->locateDocument($m[1]);
            if ($src !== null && $src[0] !== 'stock_transfers') {
                return $src;
            }
        }

        return [$table, $row];
    }

    private function salesChain(array $so): array
    {
        $id = $so['so_id'];

        return [
            $this->step('Pengiriman (SO)', 'sales_orders', $so, [
                'Customer' => $this->nameOf('customer', $so['so_customer'] ?? null),
                'Total' => $this->rupiah((float) ($so['so_total'] ?? 0)),
            ]),
            ...$this->children('Invoice Penjualan', 'sales_order_detail_invoices', 'so_id', $id),
            ...$this->children('Surat Jalan', 'sales_delivery_orders', 'so_id', $id),
            ...$this->children('Retur Bahan Customer', 'customer_supply_returns', 'so_id', $id, false),
        ];
    }

    private function purchaseChain(array $po): array
    {
        $id = $po['po_id'];
        $tt = !empty($po['tt_id']) ? $this->db->table('purchase_order_tts')->where('tt_id', $po['tt_id'])->first() : null;

        return [
            $this->step('Pembelian (PO)', 'purchase_orders', $po, [
                'Pemasok' => $this->nameOf('pemasok', $po['po_supplier'] ?? null),
                'Total' => $this->rupiah((float) ($po['po_total'] ?? 0)),
                // pembayaran 1 = belum lunas (aturan hutang dashboard).
                'Pembayaran' => (int) ($po['pembayaran'] ?? 0) === 1 ? 'Belum lunas' : 'Lunas',
            ]),
            $tt ? $this->step('Tanda Terima', 'purchase_order_tts', (array) $tt) : ['Tahap' => 'Tanda Terima'],
            ...$this->children('Penerimaan Barang (PDO)', 'purchase_delivery_orders', 'po_id', $id),
            ...$this->children('Invoice Pembelian', 'purchase_order_detail_invoices', 'po_id', $id),
            ...$this->children('Produk Bermasalah', 'product_issues', 'po_id', $id, false),
            ...$this->children('Retur ke Pemasok', 'return_supplies', 'po_id', $id, false),
        ];
    }

    private function ttChain(array $tt): array
    {
        $pos = $this->db->table('purchase_orders')->where('tt_id', $tt['tt_id'])->limit(10)->get();
        $steps = [$this->step('Tanda Terima', 'purchase_order_tts', $tt, ['Pemasok' => $this->nameOf('pemasok', $tt['supplier_id'] ?? null)])];
        foreach ($pos as $po) {
            $steps[] = $this->step('Pembelian (PO)', 'purchase_orders', (array) $po, [
                'Total' => $this->rupiah((float) $po->po_total),
                'Pembayaran' => (int) $po->pembayaran === 1 ? 'Belum lunas' : 'Lunas',
            ]);
        }

        return $pos->isEmpty() ? [...$steps, ['Tahap' => 'Pembelian (PO)']] : $steps;
    }

    private function productionChain(array $pr): array
    {
        return [
            $this->step('Produksi', 'productions', $pr, ['Gudang' => $this->nameOf('gudang', $pr['warehouse_id'] ?? null)]),
            ...$this->transfersFrom('production', $pr['production_id'], (string) ($pr['production_code'] ?? ''), 'Transfer Hasil Produksi'),
        ];
    }

    private function returnChain(array $ret): array
    {
        return [
            $this->step('Retur Produk Customer', 'customer_product_returns', $ret, ['Customer' => $this->nameOf('customer', $ret['customer_id'] ?? null)]),
            ...$this->transfersFrom('customer_return', $ret['return_id'], (string) ($ret['return_number'] ?? ''), 'Transfer Barang Retur', false),
        ];
    }

    /** Lewat source_type/source_id, atau kode "ST-<kode sumber>-n" untuk transfer lama. */
    private function transfersFrom(string $sourceType, mixed $sourceId, string $sourceCode, string $label, bool $required = true): array
    {
        $rows = $this->db->table('stock_transfers')
            ->where(function ($q) use ($sourceType, $sourceId, $sourceCode) {
                $q->where(fn ($w) => $w->where('source_type', $sourceType)->where('source_id', $sourceId));
                if ($sourceCode !== '') {
                    $q->orWhere('transfer_code', 'like', 'ST-'.str_replace(['%', '_'], ['\%', '\_'], $sourceCode).'-%');
                }
            })
            ->limit(10)->get();
        if ($rows->isEmpty()) {
            return $required ? [['Tahap' => $label]] : [];
        }

        return $rows->map(fn ($r) => $this->step($label, 'stock_transfers', (array) $r, [
            'Dari' => $this->nameOf('gudang', $r->from_warehouse_id),
            'Ke' => $this->nameOf('gudang', $r->to_warehouse_id),
        ]))->all();
    }

    /** Dokumen anak aktif; $required=false → tahap opsional, tidak dilaporkan "belum ada". */
    private function children(string $label, string $table, string $fk, mixed $id, bool $required = true): array
    {
        $rows = $this->activeOnly($this->db->table($table), $table)->where($fk, $id)->limit(10)->get();
        if ($rows->isEmpty()) {
            return $required ? [['Tahap' => $label]] : [];
        }

        return $rows->map(fn ($r) => $this->step($label, $table, (array) $r))->all();
    }

    private function step(string $label, string $table, array $row, array $extra = []): array
    {
        $code = null;
        foreach ($this->vocab->codeFields($table) as $f) {
            if (!empty($row[$f])) {
                $code = (string) $row[$f];
                break;
            }
        }
        $dateCol = $this->vocab->dateField($table);
        $status = array_key_exists('status', $row) ? (string) $this->vocab->statusLabel($table, $row['status']) : null;
        // Status tanpa arti terdaftar memancing AI menyebut "tidak dikenali sistem"; lebih baik tidak ditampilkan.
        if ($status !== null && str_contains($status, 'belum terdaftar')) {
            $status = null;
        }

        return array_filter([
            'Tahap' => $label,
            'Kode' => $code ?? '(tanpa kode)',
            'Tanggal' => $dateCol ? substr((string) ($row[$dateCol] ?? ''), 0, 10) : null,
            'Status' => $status,
            'Disetujui Oleh' => !empty($row['acc_by']) ? $this->nameOf('staff', $row['acc_by']) : null,
            ...$extra,
        ], fn ($v) => $v !== null && $v !== '');
    }
}
