<?php

namespace App\Services\AiAssistant\Tools;

use App\Services\AiAssistant\ReadOnlyDb;
use App\Services\AiAssistant\Tools\Concerns\BusinessQuery;
use App\Services\AiAssistant\Vocabulary;

class SearchRecordsTool implements ToolInterface
{
    use BusinessQuery;

    public function __construct(protected ReadOnlyDb $db, protected Vocabulary $vocab) {}

    public function name(): string
    {
        return 'search_records';
    }

    public function description(): string
    {
        return 'List or filter records of one business module. Supports exact/contains filters, "any of" lists, numeric ranges and comparisons, a date range, keyword search, sorting (newest first by default) and a row limit. Read-only.';
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'module' => [
                    'type' => 'string',
                    'description' => 'Business module, e.g. pengiriman, pembelian, produksi, stok produk, kas gudang, customer, pemasok, riwayat stok.',
                ],
                'filters' => [
                    'type' => 'object',
                    'description' => 'Field => condition. Value forms: "abc" (exact), ["a","b"] (any of), {"contains":"abc"}, {"min":1,"max":9}, {"op":">=","value":1}. Fields use app wording, e.g. "No. Invoice", "status", "customer".',
                ],
                'search' => ['type' => 'string', 'description' => 'Keyword searched across names, codes and notes'],
                'date_from' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
                'date_to' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
                'order_by' => ['type' => 'string', 'description' => 'Field to sort by; defaults to the document date'],
                'order' => ['type' => 'string', 'enum' => ['terbaru', 'terlama', 'desc', 'asc'], 'description' => 'terbaru = newest first (default)'],
                'limit' => ['type' => 'integer', 'description' => 'Rows to return, max 50'],
                'include_inactive' => ['type' => 'boolean', 'description' => 'Also return deleted/rejected/cancelled records. Default false.'],
                'fields' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Optional subset of fields to return',
                ],
            ],
            'required' => ['module'],
        ];
    }

    public function execute(array $args): array
    {
        $table = $this->resolveTable($args);
        $requestedFields = $args['fields'] ?? $args['columns'] ?? [];
        $select = is_array($requestedFields) && $requestedFields !== []
            ? $this->selectFields($table, $requestedFields)
            : $this->db->allowedColumns($table);

        $q = $this->db->table($table);
        $applied = $this->applyFilters($q, $table, is_array($args['filters'] ?? null) ? $args['filters'] : []);
        $activeNote = $this->applyActiveOnly($q, $table, $applied, $args);
        $this->applyDateRange($q, $table, $args);
        if (isset($args['search']) && is_string($args['search'])) {
            $this->applySearch($q, $table, $args['search']);
        }

        $total = (clone $q)->count();

        $this->applyOrder($q, $table, $args);
        $limit = $this->resolveLimit($args['limit'] ?? null);
        $rows = $q->select($select)->limit($limit)->get()->map(fn ($r) => (array) $r)->all();

        $result = [
            'modul' => $this->vocab->moduleLabel($table),
            'total_cocok' => $total,
            'ditampilkan' => count($rows),
            'data' => $this->vocab->present($table, $rows),
        ];
        if ($total > count($rows)) {
            $result['catatan'] = 'Hanya '.count($rows).' dari '.$total.' data ditampilkan. Gunakan filter lebih spesifik atau summarize_records untuk rekap.';
        }
        if ($activeNote !== null) {
            $result['catatan_status'] = $activeNote;
        }

        return $result;
    }

    /** @param list<string> $fields */
    private function selectFields(string $table, array $fields): array
    {
        $pk = $this->db->primaryKey($table);
        $out = [$pk];
        foreach ($fields as $field) {
            $column = $this->vocab->column($table, (string) $field);
            if (!in_array($column, $out, true)) {
                $out[] = $column;
            }
        }

        return $out;
    }
}
