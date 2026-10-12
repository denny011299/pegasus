<?php

namespace App\Services\AiAssistant\Tools;

use App\Services\AiAssistant\ReadOnlyDb;
use App\Services\AiAssistant\Tools\Concerns\TrackingHelpers;
use App\Services\AiAssistant\Vocabulary;
use Carbon\Carbon;

/**
 * Ringkasan satu periode (hari/minggu/bulan/tahun atau rentang) untuk semua
 * modul utama + perbandingan dengan periode sebelumnya yang sama panjang.
 * Hanya data aktif (tanpa dihapus/ditolak/batal).
 */
class PeriodReportTool implements ToolInterface
{
    use TrackingHelpers;

    /** Label => [tabel, kolom jenis untuk dipecah (null = tidak dipecah)]. */
    private const MODULES = [
        'Pengiriman' => ['sales_orders', null],
        'Pembelian' => ['purchase_orders', null],
        'Produksi' => ['productions', null],
        'Transfer Stok' => ['stock_transfers', null],
        'Retur Produk Customer' => ['customer_product_returns', null],
        'Retur Bahan Customer' => ['customer_supply_returns', null],
        'Stok Opname Produk' => ['stock_opnames', null],
        'Stok Opname Bahan' => ['stock_opname_bahans', null],
        'Kas Besar' => ['cashes', 'cash_type'],
        'Operasional Admin' => ['cash_admins', 'ca_type'],
        'Operasional Gudang' => ['cash_gudangs', 'cg_type'],
        'Operasional Armada' => ['cash_armadas', 'cr_type'],
        'Operasional Sales' => ['cash_sales', 'cs_type'],
    ];

    public function __construct(protected ReadOnlyDb $db, protected Vocabulary $vocab) {}

    public function name(): string
    {
        return 'period_report';
    }

    public function description(): string
    {
        return 'Period report across all main modules (pengiriman, pembelian, produksi, transfer, retur, opname, kas besar & operasional): number of documents and total nominal, compared with the previous period of the same length (naik/turun %). Use for "laporan hari ini", "rekap minggu ini", "bulan ini dibanding bulan lalu". Read-only.';
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'periode' => ['type' => 'string', 'enum' => ['hari ini', 'kemarin', 'minggu ini', 'bulan ini', 'bulan lalu', 'tahun ini'], 'description' => 'Default hari ini; abaikan bila date_from/date_to diisi'],
                'date_from' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
                'date_to' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
                'modul' => ['type' => 'string', 'description' => 'Opsional, batasi ke satu modul (mis. "kas besar", "pengiriman")'],
            ],
        ];
    }

    public function execute(array $args): array
    {
        [$from, $to] = !empty($args['date_from']) || !empty($args['date_to'])
            ? $this->period($args, 1)
            : $this->namedPeriod((string) ($args['periode'] ?? 'hari ini'));
        [$prevFrom, $prevTo] = $this->previousPeriod($from, $to);

        $filter = mb_strtolower(trim((string) ($args['modul'] ?? '')));
        $rows = [];
        foreach (self::MODULES as $label => [$table, $typeCol]) {
            if ($filter !== '' && !str_contains(mb_strtolower($label), $filter)) {
                continue;
            }
            $now = $this->stats($table, $from, $to);
            $before = $this->stats($table, $prevFrom, $prevTo);
            if ($now['n'] === 0 && $before['n'] === 0) {
                continue;
            }
            $rows[] = array_filter([
                'Modul' => $label,
                'Dokumen' => $now['n'],
                'Sebelumnya' => $before['n'],
                'Perubahan' => $this->changeLabel((float) $now['n'], (float) $before['n']),
                // Header pengiriman sering bernilai 0 (harga di invoice); jangan tampilkan Rp 0 menyesatkan.
                'Nominal' => $now['sum'] || $before['sum'] ? $this->rupiah((float) $now['sum']) : null,
                'Nominal Sebelumnya' => $now['sum'] || $before['sum'] ? $this->rupiah((float) $before['sum']) : null,
                'Rincian Jenis' => $typeCol ? $this->byType($table, $typeCol, $from, $to) : null,
            ], fn ($v) => $v !== null && $v !== []);
        }

        return [
            'periode' => $this->periodLabel($from, $to),
            'dibanding' => $this->periodLabel($prevFrom, $prevTo),
            'ringkasan' => $rows ?: 'Tidak ada transaksi pada periode ini maupun sebelumnya.',
            'catatan' => 'Tidak termasuk data yang dihapus/ditolak/dibatalkan. Perubahan = jumlah dokumen dibanding periode sebelumnya.',
        ];
    }

    /** @return array{n: int, sum: float|null} */
    private function stats(string $table, string $from, string $to): array
    {
        $dateCol = $this->vocab->dateField($table);
        if ($dateCol === null) {
            return ['n' => 0, 'sum' => null];
        }
        $q = $this->activeOnly($this->whereDay($this->db->table($table), $dateCol, $from, $to), $table);
        $amount = $this->vocab->amountField($table);

        return [
            'n' => (clone $q)->count(),
            'sum' => $amount ? (float) $q->sum($amount) : null,
        ];
    }

    /** @return list<string> "Masuk: 12 (Rp ...)" per jenis kas */
    private function byType(string $table, string $typeCol, string $from, string $to): array
    {
        $amount = $this->vocab->amountField($table);
        if ($amount === null || !in_array($typeCol, $this->db->allowedColumns($table), true)) {
            return [];
        }

        return $this->activeOnly($this->whereDay($this->db->table($table), $this->vocab->dateField($table), $from, $to), $table)
            ->selectRaw("`{$typeCol}` as t, COUNT(*) as n, SUM(`{$amount}`) as s")
            ->groupBy($typeCol)
            ->get()
            ->map(fn ($r) => $this->vocab->valueLabel($table, $typeCol, $r->t).': '.$r->n.' ('.$this->rupiah((float) $r->s).')')
            ->all();
    }

    private function changeLabel(float $now, float $before): string
    {
        $pct = $this->changePct($now, $before);
        if ($pct === null) {
            return $now > 0 ? 'baru ada (sebelumnya 0)' : '-';
        }

        return ($pct > 0 ? 'naik ' : ($pct < 0 ? 'turun ' : 'sama ')).abs($pct).'%';
    }

    /**
     * Periode mulai tgl 1 → bulan kalender sebelumnya (1–12 Okt vs 1–12 Sep; Sep penuh vs Agu penuh).
     * Selain itu → rentang sama panjang tepat sebelum $from.
     *
     * @return array{0: string, 1: string}
     */
    private function previousPeriod(string $from, string $to): array
    {
        $f = Carbon::parse($from);
        $t = Carbon::parse($to);
        // "tahun ini" (1 Jan s/d hari ini) → rentang sama di tahun lalu.
        if ($f->dayOfYear === 1 && !$f->isSameMonth($t)) {
            return [$f->copy()->subYear()->toDateString(), $t->copy()->subYear()->toDateString()];
        }
        if ($f->day === 1) {
            $pf = $f->copy()->subMonthNoOverflow();
            $pt = $t->isSameDay($t->copy()->endOfMonth()) && $f->isSameMonth($t)
                ? $pf->copy()->endOfMonth()
                : $pf->copy()->addDays((int) $f->diffInDays($t))->min($pf->copy()->endOfMonth());

            return [$pf->toDateString(), $pt->toDateString()];
        }
        $days = (int) $f->diffInDays($t) + 1;
        // "minggu ini" (Senin s/d hari ini) → hari yang sama minggu lalu.
        if ($f->isMonday() && $days <= 7) {
            return [$f->copy()->subWeek()->toDateString(), $t->copy()->subWeek()->toDateString()];
        }

        return [$f->copy()->subDays($days)->toDateString(), $f->copy()->subDay()->toDateString()];
    }

    /** @return array{0: string, 1: string} */
    private function namedPeriod(string $name): array
    {
        $today = now();

        return match (mb_strtolower(trim($name))) {
            'kemarin' => [$today->copy()->subDay()->toDateString(), $today->copy()->subDay()->toDateString()],
            'minggu ini' => [$today->copy()->startOfWeek()->toDateString(), $today->toDateString()],
            'bulan ini' => [$today->copy()->startOfMonth()->toDateString(), $today->toDateString()],
            'bulan lalu' => [$today->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(), $today->copy()->subMonthNoOverflow()->endOfMonth()->toDateString()],
            'tahun ini' => [$today->copy()->startOfYear()->toDateString(), $today->toDateString()],
            default => [$today->toDateString(), $today->toDateString()],
        };
    }
}
