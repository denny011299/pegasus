<?php

namespace App\Services\AiAssistant\Tools;

use App\Services\AiAssistant\ReadOnlyDb;
use App\Services\AiAssistant\Tools\Concerns\BusinessQuery;
use App\Services\AiAssistant\Vocabulary;
use InvalidArgumentException;

class RecordHistoryTool implements ToolInterface
{
    use BusinessQuery;

    public function __construct(protected ReadOnlyDb $db, protected Vocabulary $vocab) {}

    public function name(): string
    {
        return 'record_history';
    }

    public function description(): string
    {
        return 'Audit trail of one record: who created it, who approved it, when it changed, plus related stock movements. Accepts the document code or the Ref returned by other tools. Read-only.';
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'module' => ['type' => 'string', 'description' => 'Business module the record belongs to'],
                'code' => ['type' => 'string', 'description' => 'Document code as shown in the app'],
                'ref' => [
                    'description' => 'Ref value returned by another tool',
                    'oneOf' => [['type' => 'integer'], ['type' => 'string']],
                ],
            ],
            'required' => ['module'],
        ];
    }

    public function execute(array $args): array
    {
        $table = $this->resolveTable($args);
        $pk = $this->db->primaryKey($table);
        $columns = $this->db->allowedColumns($table);

        $ref = $args['ref'] ?? $args['id'] ?? null;
        $code = $args['code'] ?? null;
        if (($ref === null || $ref === '') && ($code === null || $code === '')) {
            throw new InvalidArgumentException('Sebutkan kode dokumen atau Ref-nya.');
        }

        $q = $this->db->table($table)->select($columns);
        if ($ref !== null && $ref !== '' && is_numeric($ref)) {
            $q->where($pk, '=', $ref);
        } else {
            $needle = (string) ($code ?? $ref);
            $fields = $this->vocab->codeFields($table);
            if ($fields === []) {
                throw new InvalidArgumentException('Data ini tidak punya kode dokumen.');
            }
            $q->where(function ($sub) use ($fields, $needle) {
                foreach ($fields as $field) {
                    $sub->orWhere($field, '=', $needle);
                }
            });
        }

        $record = $q->first();
        if ($record === null) {
            return [
                'modul' => $this->vocab->moduleLabel($table),
                'ditemukan' => false,
                'pesan' => 'Data tidak ditemukan.',
            ];
        }

        $row = (array) $record;
        $auditKeys = config('ai_assistant.audit_columns', []);
        $auditRow = array_intersect_key($row, array_flip($auditKeys));
        $auditRow[$pk] = $row[$pk] ?? null;

        $jejak = [];
        foreach (['created_at' => 'Waktu dibuat', 'created_by' => 'Pembuat'] as $key => $human) {
            if (!array_key_exists($key, $row) || $row[$key] === null || $row[$key] === '') {
                $jejak[] = $human.' tidak tercatat';
            }
        }

        $result = [
            'modul' => $this->vocab->moduleLabel($table),
            'ditemukan' => true,
            'dokumen' => $this->vocab->present($table, [$row])[0] ?? [],
            'jejak_audit' => $this->vocab->present($table, [$auditRow])[0] ?? [],
        ];
        if ($jejak !== []) {
            $result['keterbatasan_jejak'] = $jejak;
        }

        // Movements may be logged under any of the document's codes.
        $codes = [];
        foreach ($this->vocab->codeFields($table) as $field) {
            if (!empty($row[$field])) {
                $codes[] = (string) $row[$field];
            }
        }
        if ($codes !== []) {
            $movements = $this->db->table('log_stocks')
                ->select($this->db->allowedColumns('log_stocks'))
                ->where(function ($sub) use ($codes) {
                    foreach (array_unique($codes) as $code) {
                        $sub->orWhereRaw('log_kode REGEXP ?', [FindDocumentTool::exactCodePattern($code)]);
                    }
                })
                ->orderByDesc('log_date')
                ->limit(20)
                ->get()
                ->map(fn ($r) => (array) $r)
                ->all();
            if ($movements !== []) {
                $result['pergerakan_stok'] = $this->vocab->present('log_stocks', $movements, false);
            }
        }

        return $result;
    }
}
