<?php

namespace App\Services\AiAssistant\Tools\Concerns;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;

/**
 * Shared bits for the tracking/report tools: display names (gudang, staf,
 * satuan, customer, pemasok), period parsing, number formatting and the
 * "active rows only" filter. Requires $this->db and $this->vocab.
 */
trait TrackingHelpers
{
    /** @var array<string, array<int, string>> */
    private array $nameMaps = [];

    protected function nameOf(string $kind, mixed $id): string
    {
        if ($id === null || $id === '') {
            return '-';
        }
        $this->nameMaps[$kind] ??= $this->loadNames($kind);

        return $this->nameMaps[$kind][(int) $id] ?? '-';
    }

    /** @return array<int, string> */
    private function loadNames(string $kind): array
    {
        $pairs = match ($kind) {
            'unit' => $this->db->table('units')->pluck('unit_name', 'unit_id'),
            'gudang' => $this->db->table('warehouses')->pluck('warehouse_name', 'id'),
            'staff' => $this->db->table('staffs')->pluck('staff_name', 'staff_id'),
            'pemasok' => $this->db->table('suppliers')->pluck('supplier_name', 'supplier_id'),
            'bahan' => $this->db->table('supplies')->pluck('supplies_name', 'supplies_id'),
            // Sama dengan lookup Vocabulary: catatan customer (plat/nama armada) dulu, lalu nama.
            'customer' => $this->db->table('customers')->get(['customer_id', 'customer_name', 'customer_notes'])
                ->mapWithKeys(fn ($c) => [$c->customer_id => trim((string) $c->customer_notes) !== '' ? $c->customer_notes : $c->customer_name]),
            default => collect(),
        };

        return $pairs->map(fn ($n) => (string) $n)->all();
    }

    /**
     * Period from date_from/date_to (or a single "tanggal"); defaults to the
     * last $defaultDays days ending today.
     *
     * @return array{0: string, 1: string} Y-m-d
     */
    protected function period(array $args, int $defaultDays): array
    {
        $single = $this->ymd($args['tanggal'] ?? null);
        $to = $this->ymd($args['date_to'] ?? null) ?? $single ?? now()->toDateString();
        $from = $this->ymd($args['date_from'] ?? null) ?? $single ?? Carbon::parse($to)->subDays(max(1, $defaultDays) - 1)->toDateString();

        return $from <= $to ? [$from, $to] : [$to, $from];
    }

    protected function periodLabel(string $from, string $to): string
    {
        return $from === $to ? $from : $from.' s/d '.$to;
    }

    /** whereBetween over a whole day range; works for DATE and DATETIME columns. */
    protected function whereDay(Builder $q, string $column, string $from, string $to): Builder
    {
        return $q->whereBetween($column, [$from.' 00:00:00', $to.' 23:59:59']);
    }

    /** Hide rows whose status means deleted / rejected / cancelled. */
    protected function activeOnly(Builder $q, string $table): Builder
    {
        $inactive = $this->vocab->inactiveStatuses($table);
        if ($inactive !== [] && in_array('status', $this->db->allowedColumns($table), true)) {
            $q->where(fn (Builder $w) => $w->whereNotIn('status', $inactive)->orWhereNull('status'));
        }

        return $q;
    }

    protected function qtyFmt(float $n): string
    {
        return rtrim(rtrim(number_format($n, 2, ',', '.'), '0'), ',');
    }

    protected function rupiah(float $n): string
    {
        return 'Rp '.number_format($n, 0, ',', '.');
    }

    /** @param iterable<object{unit_id: mixed, total: mixed}> $rows */
    protected function qtyByUnit(iterable $rows): string
    {
        $parts = [];
        foreach ($rows as $r) {
            $parts[] = $this->qtyFmt((float) $r->total).' '.$this->nameOf('unit', $r->unit_id);
        }

        return $parts === [] ? '0' : implode(', ', $parts);
    }

    /** Whole days from $date to today (Carbon 3 diffInDays is signed float). */
    protected function ageDays(mixed $date): int
    {
        return (int) abs(Carbon::parse((string) $date)->startOfDay()->diffInDays(now()->startOfDay()));
    }

    protected function changePct(float $now, float $before): ?float
    {
        return $before == 0.0 ? null : round(($now - $before) / $before * 100, 1);
    }

    private function ymd(mixed $value): ?string
    {
        $value = trim((string) $value);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }
}
