<?php

namespace App\Services\AiAssistant\Tools;

use App\Services\AiAssistant\ReadOnlyDb;
use App\Services\AiAssistant\Tools\Concerns\BusinessQuery;
use App\Services\AiAssistant\Vocabulary;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Totals, counts and recaps. Aggregation happens in SQL but stays inside the
 * whitelist: only whitelisted numeric fields can be summed and only
 * whitelisted fields (or the document month/date) can be grouped.
 */
class SummarizeRecordsTool implements ToolInterface
{
    use BusinessQuery;

    private const METRICS = ['count', 'sum', 'avg', 'min', 'max'];

    public function __construct(protected ReadOnlyDb $db, protected Vocabulary $vocab) {}

    public function name(): string
    {
        return 'summarize_records';
    }

    public function description(): string
    {
        return 'Count, total, average, minimum or maximum for one business module, optionally grouped by a field, by status, or by month/day. Use this for "berapa total", "berapa banyak", monthly recaps and rankings. Read-only.';
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'module' => ['type' => 'string', 'description' => 'Business module, e.g. pengiriman, pembelian, kas gudang'],
                'metric' => ['type' => 'string', 'enum' => self::METRICS, 'description' => 'count (default), sum, avg, min, max'],
                'field' => ['type' => 'string', 'description' => 'Numeric field for sum/avg/min/max, e.g. "total", "qty", "nominal"'],
                'group_by' => ['type' => 'string', 'description' => 'Field to group by, or "bulan", "tanggal", "status"'],
                'filters' => ['type' => 'object', 'description' => 'Same condition forms as search_records'],
                'date_from' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
                'date_to' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
                'limit' => ['type' => 'integer', 'description' => 'Max groups returned, default 12, max 50'],
                'include_inactive' => ['type' => 'boolean', 'description' => 'Also count deleted/rejected/cancelled records. Default false.'],
            ],
            'required' => ['module'],
        ];
    }

    public function execute(array $args): array
    {
        $table = $this->resolveTable($args);
        $metric = strtolower((string) ($args['metric'] ?? 'count'));
        if (!in_array($metric, self::METRICS, true)) {
            throw new InvalidArgumentException('Perhitungan tidak didukung: '.$metric);
        }

        $field = null;
        if ($metric !== 'count') {
            $raw = $args['field'] ?? null;
            $field = $raw !== null && $raw !== ''
                ? $this->vocab->column($table, (string) $raw)
                : $this->vocab->amountField($table);
            if ($field === null) {
                throw new InvalidArgumentException('Sebutkan nilai yang mau dihitung (misal total atau qty).');
            }
        }

        $group = $this->groupExpression($table, $args['group_by'] ?? null);

        $q = $this->db->table($table);
        $applied = $this->applyFilters($q, $table, is_array($args['filters'] ?? null) ? $args['filters'] : []);
        // A per-status recap must show every status, including the inactive ones.
        if ($group !== null && $group['column'] === 'status') {
            $applied[] = 'status';
        }
        $activeNote = $this->applyActiveOnly($q, $table, $applied, $args);
        $this->applyDateRange($q, $table, $args);

        $aggregate = $metric === 'count'
            ? 'COUNT(*)'
            : strtoupper($metric).'(`'.$field.'`)';

        if ($group === null) {
            $row = (clone $q)->select(DB::raw($aggregate.' as nilai'), DB::raw('COUNT(*) as jumlah_data'))->first();

            return array_filter([
                'modul' => $this->vocab->moduleLabel($table),
                'perhitungan' => $this->metricLabel($metric, $field),
                'jumlah_data' => (int) ($row->jumlah_data ?? 0),
                'nilai' => $this->normalizeNumber($row->nilai ?? null),
                'catatan_status' => $activeNote,
            ], fn ($v, $k) => $k !== 'catatan_status' || $v !== null, ARRAY_FILTER_USE_BOTH);
        }

        $limit = max(1, min((int) ($args['limit'] ?? 12), (int) config('ai_assistant.max_rows', 50)));
        // Period recaps read best newest-first; other groupings rank by value.
        $order = in_array($group['label'], ['Bulan', 'Tanggal', 'Tahun'], true)
            ? $group['expression']
            : ($metric === 'count' ? 'COUNT(*)' : $aggregate);

        $rows = $q->select(
            DB::raw($group['expression'].' as grup'),
            DB::raw($aggregate.' as nilai'),
            DB::raw('COUNT(*) as jumlah_data'),
        )
            ->groupBy(DB::raw($group['expression']))
            ->orderByDesc(DB::raw($order))
            ->limit($limit)
            ->get();

        $groups = [];
        foreach ($rows as $row) {
            $groups[] = [
                $group['label'] => $group['translate']
                    ? $this->vocab->valueLabel($table, $group['column'], $row->grup)
                    : $row->grup,
                'Jumlah Data' => (int) $row->jumlah_data,
                $this->metricLabel($metric, $field) => $this->normalizeNumber($row->nilai),
            ];
        }

        return array_filter([
            'modul' => $this->vocab->moduleLabel($table),
            'dikelompokkan' => $group['label'],
            'jumlah_grup' => count($groups),
            'data' => $groups,
            'catatan_status' => $activeNote,
        ], fn ($v, $k) => $k !== 'catatan_status' || $v !== null, ARRAY_FILTER_USE_BOTH);
    }

    /** @return array{expression: string, label: string, column: string, translate: bool}|null */
    private function groupExpression(string $table, mixed $groupBy): ?array
    {
        if ($groupBy === null || $groupBy === '') {
            return null;
        }

        $key = strtolower(trim((string) $groupBy));
        $dateField = $this->vocab->dateField($table);

        if (in_array($key, ['bulan', 'month', 'per bulan', 'monthly'], true)) {
            if ($dateField === null) {
                throw new InvalidArgumentException('Data ini tidak punya tanggal.');
            }

            return ['expression' => "DATE_FORMAT(`{$dateField}`, '%Y-%m')", 'label' => 'Bulan', 'column' => $dateField, 'translate' => false];
        }
        if (in_array($key, ['tanggal', 'hari', 'day', 'date', 'per hari'], true)) {
            if ($dateField === null) {
                throw new InvalidArgumentException('Data ini tidak punya tanggal.');
            }

            return ['expression' => "DATE(`{$dateField}`)", 'label' => 'Tanggal', 'column' => $dateField, 'translate' => false];
        }
        if (in_array($key, ['tahun', 'year'], true)) {
            if ($dateField === null) {
                throw new InvalidArgumentException('Data ini tidak punya tanggal.');
            }

            return ['expression' => "YEAR(`{$dateField}`)", 'label' => 'Tahun', 'column' => $dateField, 'translate' => false];
        }

        $column = $this->vocab->column($table, (string) $groupBy);

        return [
            'expression' => '`'.$column.'`',
            'label' => $this->vocab->label($column),
            'column' => $column,
            'translate' => true,
        ];
    }

    private function metricLabel(string $metric, ?string $field): string
    {
        if ($metric === 'count') {
            return 'Jumlah Data';
        }
        $name = $field !== null ? $this->vocab->label($field) : 'Nilai';

        return match ($metric) {
            'sum' => str_starts_with($name, 'Total') ? $name : 'Total '.$name,
            'avg' => 'Rata-rata '.$name,
            'min' => $name.' Terkecil',
            'max' => $name.' Terbesar',
            default => $name,
        };
    }

    private function normalizeNumber(mixed $value): mixed
    {
        if ($value === null || !is_numeric($value)) {
            return $value;
        }
        $float = (float) $value;

        return $float === floor($float) ? (int) $float : round($float, 2);
    }
}
