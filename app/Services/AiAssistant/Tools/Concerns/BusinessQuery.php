<?php

namespace App\Services\AiAssistant\Tools\Concerns;

use Illuminate\Database\Query\Builder;
use InvalidArgumentException;

/**
 * Shared argument handling for the read-only tools: resolves business
 * wording to storage keys and applies only safe, whitelisted conditions.
 */
trait BusinessQuery
{
    protected function resolveTable(array $args): string
    {
        $name = (string) ($args['module'] ?? $args['table'] ?? '');
        if ($name === '') {
            throw new InvalidArgumentException('module required');
        }

        return $this->vocab->table($name);
    }

    /**
     * Accepted filter shapes per field:
     *   "value"                  exact match ("%value%" switches to contains)
     *   [a, b]                   any of these values
     *   {"contains": "abc"}      text contains
     *   {"min": 1, "max": 9}     inclusive range
     *   {"op": ">=", "value": 1} comparison (= != > >= < <=)
     */
    protected function applyFilters(Builder $q, string $table, array $filters): array
    {
        $applied = [];
        foreach ($filters as $field => $value) {
            $column = $this->vocab->column($table, (string) $field);
            $applied[] = $column;

            if (is_array($value) && array_is_list($value)) {
                $q->whereIn($column, array_slice($value, 0, 50));
                continue;
            }

            if (is_array($value)) {
                $this->applyConditionObject($q, $column, $value);
                continue;
            }

            if (is_string($value) && str_contains($value, '%')) {
                $q->where($column, 'like', $value);
                continue;
            }

            $q->where($column, '=', $value);
        }

        return $applied;
    }

    private function applyConditionObject(Builder $q, string $column, array $value): void
    {
        $operators = ['=', '!=', '<>', '>', '>=', '<', '<='];

        if (isset($value['contains'])) {
            $q->where($column, 'like', '%'.$this->escapeLike((string) $value['contains']).'%');
        }
        if (isset($value['min'])) {
            $q->where($column, '>=', $value['min']);
        }
        if (isset($value['max'])) {
            $q->where($column, '<=', $value['max']);
        }
        if (isset($value['op']) && array_key_exists('value', $value)) {
            $op = (string) $value['op'];
            if (!in_array($op, $operators, true)) {
                throw new InvalidArgumentException('Perbandingan tidak didukung: '.$op);
            }
            $q->where($column, $op, $value['value']);
        }
    }

    /**
     * Leave out deleted / rejected / cancelled / inactive records unless the
     * user filtered on status or asked for them, so totals match the app.
     * Returns a note for the model when something was left out.
     *
     * @param  list<string>  $appliedColumns
     */
    protected function applyActiveOnly(Builder $q, string $table, array $appliedColumns, array $args): ?string
    {
        if (!empty($args['include_inactive']) || in_array('status', $appliedColumns, true)) {
            return null;
        }
        if (!in_array('status', $this->db->allowedColumns($table), true)) {
            return null;
        }

        $inactive = $this->vocab->inactiveStatuses($table);
        if ($inactive === []) {
            return null;
        }

        $q->where(function (Builder $sub) use ($inactive) {
            $sub->whereNotIn('status', $inactive)->orWhereNull('status');
        });

        $labels = array_unique(array_map(fn ($code) => $this->vocab->statusLabel($table, $code), $inactive));

        return 'Data berstatus '.implode(', ', $labels).' tidak ikut dihitung. Pakai include_inactive=true atau filter status bila user memintanya.';
    }

    protected function applyDateRange(Builder $q, string $table, array $args): ?string
    {
        $from = $args['date_from'] ?? null;
        $to = $args['date_to'] ?? null;
        if (($from === null || $from === '') && ($to === null || $to === '')) {
            return null;
        }

        $field = isset($args['date_field']) && $args['date_field'] !== ''
            ? $this->vocab->column($table, (string) $args['date_field'])
            : $this->vocab->dateField($table);

        if ($field === null) {
            throw new InvalidArgumentException('Data ini tidak punya tanggal untuk difilter.');
        }

        if ($from !== null && $from !== '') {
            $q->where($field, '>=', $this->startOfDay((string) $from));
        }
        if ($to !== null && $to !== '') {
            $q->where($field, '<=', $this->endOfDay((string) $to));
        }

        return $field;
    }

    /** Text search across the readable text columns of the module. */
    protected function applySearch(Builder $q, string $table, string $text): void
    {
        $text = trim($text);
        if ($text === '') {
            return;
        }

        $targets = [];
        foreach ($this->db->allowedColumns($table) as $column) {
            if (preg_match('/(name|nama|code|kode|number|no$|_no$|sku|notes|note|desc|description|receiver|invoice|reference|summary|label)/i', $column)) {
                $targets[] = $column;
            }
        }
        if ($targets === []) {
            throw new InvalidArgumentException('Data ini tidak bisa dicari dengan kata kunci.');
        }

        $like = '%'.$this->escapeLike($text).'%';
        $q->where(function (Builder $sub) use ($targets, $like) {
            foreach ($targets as $column) {
                $sub->orWhere($column, 'like', $like);
            }
        });
    }

    protected function applyOrder(Builder $q, string $table, array $args): void
    {
        $direction = strtolower((string) ($args['order'] ?? $args['direction'] ?? 'terbaru'));
        $descending = !in_array($direction, ['asc', 'terlama', 'naik', 'terkecil', 'ascending'], true);

        $field = $args['order_by'] ?? $args['sort_by'] ?? null;
        $column = $field !== null && $field !== ''
            ? $this->vocab->column($table, (string) $field)
            : $this->vocab->dateField($table);

        if ($column === null) {
            return;
        }

        $q->orderBy($column, $descending ? 'desc' : 'asc');
    }

    protected function resolveLimit(mixed $requested): int
    {
        $max = (int) config('ai_assistant.max_rows', 50);
        $limit = (int) ($requested ?: config('ai_assistant.rows_to_model', 15));

        return max(1, min($limit, $max));
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }

    private function startOfDay(string $value): string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value.' 00:00:00' : $value;
    }

    private function endOfDay(string $value): string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value.' 23:59:59' : $value;
    }
}
