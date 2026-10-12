<?php

namespace App\Services\AiAssistant\Tools;

use App\Services\AiAssistant\ReadOnlyDb;
use App\Services\AiAssistant\Tools\Concerns\TrackingHelpers;
use App\Services\AiAssistant\Vocabulary;

/**
 * Insight customer & pemasok: peringkat teratas, yang lama tidak transaksi,
 * dan untuk pemasok: lead time PO → penerimaan barang (PDO), produk bermasalah,
 * hutang belum lunas. Dengan "nama" → profil satu partner.
 */
class PartnerInsightTool implements ToolInterface
{
    use TrackingHelpers;

    public function __construct(protected ReadOnlyDb $db, protected Vocabulary $vocab) {}

    public function name(): string
    {
        return 'partner_insight';
    }

    public function description(): string
    {
        return 'Customer or supplier (pemasok) insight: top partners by transactions in a period, partners that stopped ordering (tidak aktif), and for pemasok the average lead time PO → barang diterima, produk bermasalah count and unpaid PO total. With "nama" returns a profile of one partner. Default last 90 days. Read-only.';
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'jenis' => ['type' => 'string', 'enum' => ['customer', 'pemasok'], 'description' => 'Default customer'],
                'nama' => ['type' => 'string', 'description' => 'Opsional: nama / kode / plat satu partner'],
                'date_from' => ['type' => 'string', 'description' => 'YYYY-MM-DD, default 90 hari lalu'],
                'date_to' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
                'tidak_aktif_hari' => ['type' => 'integer', 'description' => 'Dianggap tidak aktif bila transaksi terakhir lebih dari N hari. Default 60.'],
                'limit' => ['type' => 'integer', 'description' => 'Default 10, max 30'],
            ],
        ];
    }

    public function execute(array $args): array
    {
        [$from, $to] = $this->period($args, 90);
        $limit = max(1, min((int) ($args['limit'] ?? 10), 30));
        $idle = max(1, (int) ($args['tidak_aktif_hari'] ?? 60));
        $isSupplier = ($args['jenis'] ?? 'customer') === 'pemasok';
        $name = trim((string) ($args['nama'] ?? ''));

        $cfg = $isSupplier
            ? ['partners' => 'suppliers', 'pk' => 'supplier_id', 'label' => 'supplier_name', 'search' => ['supplier_name', 'supplier_code'],
                'docs' => 'purchase_orders', 'fk' => 'po_supplier', 'date' => 'po_date', 'amount' => 'po_total', 'kind' => 'pemasok']
            : ['partners' => 'customers', 'pk' => 'customer_id', 'label' => 'customer_name', 'search' => ['customer_name', 'customer_code', 'customer_notes'],
                'docs' => 'sales_orders', 'fk' => 'so_customer', 'date' => 'so_date', 'amount' => null, 'kind' => 'customer'];

        if ($name !== '') {
            $ids = $this->findPartner($cfg, $name);
            if (count($ids) !== 1) {
                return $ids === []
                    ? ['pesan' => ucfirst($cfg['kind']).' "'.$name.'" tidak ditemukan.']
                    : ['pesan' => 'Ada beberapa '.$cfg['kind'].' cocok. Tanyakan ke user yang mana.', 'pilihan' => array_map(fn ($id) => $this->nameOf($cfg['kind'], $id), array_slice($ids, 0, 10))];
            }

            return $this->profile($cfg, $ids[0], $from, $to, $isSupplier);
        }

        $docs = fn () => $this->activeOnly($this->db->table($cfg['docs']), $cfg['docs']);
        $top = $this->whereDay($docs(), $cfg['date'], $from, $to)
            ->selectRaw("`{$cfg['fk']}` as pid, COUNT(*) as n".($cfg['amount'] ? ", SUM(`{$cfg['amount']}`) as total" : ''))
            ->groupBy($cfg['fk'])->orderByDesc('n')->limit($limit)->get()
            ->map(fn ($r) => array_filter([
                ucfirst($cfg['kind']) => $this->nameOf($cfg['kind'], $r->pid),
                'Dokumen' => (int) $r->n,
                'Total' => !empty($r->total) ? $this->rupiah((float) $r->total) : null,
                'Lead Time Rata-rata' => $isSupplier ? $this->leadTime((int) $r->pid, $from, $to) : null,
            ]))->all();

        $cutoff = now()->subDays($idle)->toDateString();
        $inactive = $docs()->selectRaw("`{$cfg['fk']}` as pid, MAX(`{$cfg['date']}`) as last, COUNT(*) as n")
            ->groupBy($cfg['fk'])
            ->havingRaw("MAX(`{$cfg['date']}`) < ?", [$cutoff.' 00:00:00'])
            ->orderByDesc('n')->limit($limit)->get()
            ->map(fn ($r) => [
                ucfirst($cfg['kind']) => $this->nameOf($cfg['kind'], $r->pid),
                'Transaksi Terakhir' => substr((string) $r->last, 0, 10),
                'Sudah' => $this->ageDays($r->last).' hari',
                'Total Dokumen Sepanjang Waktu' => (int) $r->n,
            ])->all();

        return [
            'jenis' => $cfg['kind'],
            'periode' => $this->periodLabel($from, $to),
            'teratas' => $top ?: 'Tidak ada transaksi pada periode ini.',
            'tidak_aktif' => $inactive ?: 'Tidak ada '.$cfg['kind'].' yang berhenti transaksi lebih dari '.$idle.' hari.',
            'catatan' => 'Tidak aktif = pernah transaksi, tapi transaksi terakhir lebih dari '.$idle.' hari lalu. Diurutkan dari yang dulu paling sering.',
        ];
    }

    private function profile(array $cfg, int $id, string $from, string $to, bool $isSupplier): array
    {
        $docs = fn () => $this->activeOnly($this->db->table($cfg['docs']), $cfg['docs'])->where($cfg['fk'], $id);
        $inPeriod = $this->whereDay($docs(), $cfg['date'], $from, $to);
        $last = $docs()->max($cfg['date']);

        $out = [
            ucfirst($cfg['kind']) => $this->nameOf($cfg['kind'], $id),
            'periode' => $this->periodLabel($from, $to),
            'dokumen_periode' => (clone $inPeriod)->count(),
            'transaksi_terakhir' => $last ? substr((string) $last, 0, 10).' ('.$this->ageDays($last).' hari lalu)' : 'Belum pernah',
        ];

        if ($isSupplier) {
            $poIds = (clone $inPeriod)->pluck('po_id')->all();
            $out['total_pembelian'] = $this->rupiah((float) (clone $inPeriod)->sum('po_total'));
            $out['lead_time_rata_rata'] = $this->leadTime($id, $from, $to);
            $out['produk_bermasalah'] = $poIds === [] ? 0 : $this->activeOnly($this->db->table('product_issues'), 'product_issues')->whereIn('po_id', $poIds)->count();
            // pembayaran 1 = belum lunas (aturan hutang dashboard).
            $unpaid = $docs()->where('pembayaran', 1)->where('status', '>', 0);
            $out['hutang_belum_lunas'] = (clone $unpaid)->count().' PO ('.$this->rupiah((float) $unpaid->sum('po_total')).')';
        } else {
            $soIds = (clone $inPeriod)->pluck('so_id')->all();
            $out['barang_terbanyak'] = $soIds === [] ? [] : $this->db->table('sales_order_details')
                ->whereIn('so_id', $soIds)
                ->selectRaw("CONCAT(COALESCE(sod_nama,''),' ',COALESCE(sod_variant,'')) as nama, unit_id, SUM(sod_qty) as total")
                ->groupBy('sod_nama', 'sod_variant', 'unit_id')->orderByDesc('total')->limit(5)->get()
                ->map(fn ($r) => trim($r->nama).': '.$this->qtyFmt((float) $r->total).' '.$this->nameOf('unit', $r->unit_id))->all();
            $out['retur_produk_periode'] = $this->whereDay(
                $this->activeOnly($this->db->table('customer_product_returns'), 'customer_product_returns')->where('customer_id', $id),
                'return_date', $from, $to
            )->count();
        }

        return $out;
    }

    /** Rata-rata hari PO → penerimaan barang (PDO) pertama, untuk PO di periode ini. */
    private function leadTime(int $supplierId, string $from, string $to): string
    {
        $avg = $this->whereDay($this->db->table('purchase_orders'), 'po_date', $from, $to)
            ->where('po_supplier', $supplierId)
            ->where('purchase_orders.status', '>', 0)
            ->joinSub(
                $this->db->table('purchase_delivery_orders')->selectRaw('po_id, MIN(pdo_date) as first_pdo')->groupBy('po_id'),
                'pdo', 'pdo.po_id', '=', 'purchase_orders.po_id'
            )
            ->selectRaw('AVG(DATEDIFF(pdo.first_pdo, purchase_orders.po_date)) as d')
            ->value('d');

        return $avg === null ? 'belum ada penerimaan' : $this->qtyFmt(max(0, (float) $avg)).' hari';
    }

    /** @return list<int> */
    private function findPartner(array $cfg, string $name): array
    {
        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $name).'%';
        $rows = $this->db->table($cfg['partners'])
            ->where(function ($q) use ($cfg, $like) {
                foreach ($cfg['search'] as $col) {
                    $q->orWhere($col, 'like', $like);
                }
            })
            ->limit(20)->get([$cfg['pk'], ...$cfg['search']]);

        $exact = $rows->filter(function ($r) use ($cfg, $name) {
            foreach ($cfg['search'] as $col) {
                if (strcasecmp(trim((string) $r->{$col}), $name) === 0) {
                    return true;
                }
            }

            return false;
        });

        return ($exact->isNotEmpty() ? $exact : $rows)->pluck($cfg['pk'])->map(fn ($v) => (int) $v)->values()->all();
    }
}
