<?php

namespace App\Services\AiAssistant\Tools;

use App\Services\AiAssistant\ReadOnlyDb;
use App\Services\AiAssistant\Tools\Concerns\BusinessQuery;
use App\Services\AiAssistant\Vocabulary;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FindDuplicatesTool implements ToolInterface
{
    use BusinessQuery;

    public function __construct(protected ReadOnlyDb $db, protected Vocabulary $vocab) {}

    public function name(): string
    {
        return 'find_duplicates';
    }

    public function description(): string
    {
        return 'Find groups of records that share the same values (count > 1) on the given fields, optionally inside a date range, with who created them and how far apart they were created. Read-only. Max 50 groups.';
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'module' => ['type' => 'string', 'description' => 'Business module, e.g. pengiriman, pembelian, produksi'],
                'fields' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'minItems' => 1,
                    'description' => 'Fields that must match for a record to count as duplicate, in app wording',
                ],
                'date_from' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
                'date_to' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
            ],
            'required' => ['module', 'fields'],
        ];
    }

    public function execute(array $args): array
    {
        $table = $this->resolveTable($args);
        $requested = $args['fields'] ?? $args['columns'] ?? [];
        if (!is_array($requested) || $requested === []) {
            throw new InvalidArgumentException('fields required');
        }

        $groupCols = [];
        foreach ($requested as $field) {
            $column = $this->vocab->column($table, (string) $field);
            if (!in_array($column, $groupCols, true)) {
                $groupCols[] = $column;
            }
        }

        $allowed = $this->db->allowedColumns($table);
        $pk = $this->db->primaryKey($table);

        $select = array_merge($groupCols, [DB::raw('COUNT(*) as duplicate_count')]);
        if (in_array($pk, $allowed, true)) {
            $select[] = DB::raw("GROUP_CONCAT({$pk} ORDER BY {$pk} SEPARATOR ',') as record_refs");
        }
        if (in_array('created_at', $allowed, true)) {
            $select[] = DB::raw('MIN(created_at) as first_created_at');
            $select[] = DB::raw('MAX(created_at) as last_created_at');
            $select[] = DB::raw('TIMESTAMPDIFF(SECOND, MIN(created_at), MAX(created_at)) as gap_seconds');
        }
        if (in_array('created_by', $allowed, true)) {
            $select[] = DB::raw('COUNT(DISTINCT created_by) as distinct_creators');
        }

        $q = $this->db->table($table)->select($select);

        $range = is_array($args['date_range'] ?? null) ? $args['date_range'] : [];
        $this->applyDateRange($q, $table, [
            'date_from' => $args['date_from'] ?? ($range['from'] ?? null),
            'date_to' => $args['date_to'] ?? ($range['to'] ?? null),
        ]);

        foreach ($groupCols as $column) {
            $q->groupBy($column);
        }
        $q->having('duplicate_count', '>', 1)
            ->orderByDesc('duplicate_count');

        $rows = $q->limit((int) config('ai_assistant.max_rows', 50))->get();

        $groups = [];
        foreach ($rows as $row) {
            $raw = (array) $row;
            $group = [];
            foreach ($groupCols as $column) {
                $group[$this->vocab->label($column)] = $this->vocab->valueLabel($table, $column, $raw[$column] ?? null);
            }
            $group['Jumlah Kemunculan'] = (int) ($raw['duplicate_count'] ?? 0);
            if (isset($raw['first_created_at'])) {
                $group['Dibuat Pertama'] = $raw['first_created_at'];
                $group['Dibuat Terakhir'] = $raw['last_created_at'];
                $group['Selisih Waktu (detik)'] = (int) ($raw['gap_seconds'] ?? 0);
            }
            if (isset($raw['distinct_creators'])) {
                $group['Jumlah Pembuat Berbeda'] = (int) $raw['distinct_creators'];
            }
            if (isset($raw['record_refs'])) {
                $group['Ref'] = $raw['record_refs'];
            }
            $groups[] = $group;
        }

        return [
            'modul' => $this->vocab->moduleLabel($table),
            'dibandingkan' => array_map(fn ($c) => $this->vocab->label($c), $groupCols),
            'jumlah_grup' => count($groups),
            'data' => $groups,
        ];
    }
}
