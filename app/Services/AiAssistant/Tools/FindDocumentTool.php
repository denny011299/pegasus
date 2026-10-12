<?php

namespace App\Services\AiAssistant\Tools;

use App\Services\AiAssistant\ReadOnlyDb;
use App\Services\AiAssistant\Tools\Concerns\BusinessQuery;
use App\Services\AiAssistant\Vocabulary;
use InvalidArgumentException;

/**
 * One-shot document lookup: finds a document by the code users read on
 * screen (INV..., SO..., PO..., TT..., kode produksi, kode transfer, ...)
 * and returns its header, its items and its stock movements together, so
 * the model does not need several rounds to answer "isi dokumen X apa".
 */
class FindDocumentTool implements ToolInterface
{
    use BusinessQuery;

    public function __construct(protected ReadOnlyDb $db, protected Vocabulary $vocab) {}

    public function name(): string
    {
        return 'find_document';
    }

    public function description(): string
    {
        return 'Find one document by the code shown in the app (invoice, SO, PO, tanda terima, produksi, opname, retur, transfer, surat jalan) and return its header, its items and related stock movements in a single call. Read-only.';
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'code' => ['type' => 'string', 'description' => 'Document code as shown to users, e.g. INV1098, SO1098, PO123'],
                'module' => ['type' => 'string', 'description' => 'Optional module to narrow the search'],
                'include_items' => ['type' => 'boolean', 'description' => 'Include line items, default true'],
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

        $tables = isset($args['module']) && $args['module'] !== ''
            ? [$this->vocab->table((string) $args['module'])]
            : $this->tablesForCode($code);

        $hit = $this->locate($tables, $code);
        if ($hit === null) {
            return [
                'ditemukan' => false,
                'kode' => $code,
                'pesan' => 'Dokumen dengan kode tersebut tidak ditemukan.',
            ];
        }

        [$table, $row] = $hit;
        $pk = $this->db->primaryKey($table);
        $ref = $row[$pk] ?? null;

        $result = [
            'ditemukan' => true,
            'modul' => $this->vocab->moduleLabel($table),
            'dokumen' => $this->vocab->present($table, [$row])[0] ?? [],
        ];

        if (($args['include_items'] ?? true) !== false) {
            $detail = $this->vocab->detail($table);
            if ($detail !== null && $ref !== null) {
                $items = $this->db->table($detail['table'])
                    ->select($this->db->allowedColumns($detail['table']))
                    ->where($detail['fk'], '=', $ref)
                    ->limit((int) config('ai_assistant.max_rows', 50))
                    ->get()
                    ->map(fn ($r) => (array) $r)
                    ->all();
                $result['jumlah_item'] = count($items);
                $result['item'] = $this->vocab->present($detail['table'], $items, false, [$detail['fk']]);
            }
        }

        // Movements may be logged under any of the document's codes
        // (a sales order's stock moves under its invoice number).
        $codes = [$code];
        foreach ($this->vocab->codeFields($table) as $field) {
            if (!empty($row[$field])) {
                $codes[] = (string) $row[$field];
            }
        }
        $movements = $this->stockMovements(array_values(array_unique($codes)));
        if ($movements !== []) {
            $result['pergerakan_stok'] = $movements;
        }

        return $result;
    }

    /**
     * Known prefixes are exclusive: ST* only Transfer Stok, PI* only Produk
     * Bermasalah, etc. Never fall through to unrelated modules — MySQL would
     * otherwise coerce "ST0101" to 0 against integer ref fields and hit noise.
     * Prefixes come from the code generators in the models; the list is
     * ordered longest-first so "PBJ" is never read as "PB…" or "PR…".
     *
     * @return list<string>
     */
    private function tablesForCode(string $code): array
    {
        $all = [];
        foreach (config('ai_vocabulary.modules', []) as $def) {
            if (!empty($def['codes'])) {
                $all[] = $def['table'];
            }
        }

        $upper = strtoupper($code);
        foreach (self::PREFIXES as $prefix => $tables) {
            if (str_starts_with($upper, $prefix)) {
                return array_values(array_intersect($tables, $all));
            }
        }

        return $all;
    }

    /** Document code prefix => modules that issue it (longest prefix first). */
    public const PREFIXES = [
        'INV' => ['sales_orders', 'sales_order_detail_invoices', 'purchase_order_detail_invoices'],
        'SDO' => ['sales_delivery_orders'],
        'PDO' => ['purchase_delivery_orders'],
        'PBJ' => ['customer_product_returns'],
        'PBM' => ['customer_supply_returns'],
        'SO' => ['sales_orders'],
        'PO' => ['purchase_orders'],
        'TT' => ['purchase_order_tts'],
        'SP' => ['stock_opnames'],
        'SB' => ['stock_opname_bahans'],
        'ST' => ['stock_transfers'],
        'PR' => ['productions'],
        'PI' => ['product_issues'],
    ];

    /**
     * MySQL REGEXP matching the code as a whole token, so SO1 never pulls in
     * the movements of SO10 or SO11.
     */
    public static function exactCodePattern(string $code): string
    {
        $safe = preg_replace('/[^A-Za-z0-9\/-]/', '', $code) ?? '';

        return '(^|[^A-Za-z0-9])'.$safe.'([^0-9]|$)';
    }

    /**
     * Exact string match only. No cross-module fuzzy fallthrough.
     *
     * @param  list<string>  $tables
     * @return array{0: string, 1: array<string, mixed>}|null
     */
    private function locate(array $tables, string $code): ?array
    {
        foreach ($tables as $table) {
            $row = $this->findInTable($table, $code);
            if ($row !== null) {
                return [$table, $row];
            }
        }

        return null;
    }

    /** @return array<string, mixed>|null */
    private function findInTable(string $table, string $code): ?array
    {
        $fields = $this->vocab->codeFields($table);
        if ($fields === []) {
            return null;
        }

        // CAST AS CHAR avoids MySQL turning "ST0101" into 0 against int columns.
        $q = $this->db->table($table)->select($this->db->allowedColumns($table));
        $q->where(function ($sub) use ($fields, $code) {
            foreach ($fields as $field) {
                $sub->orWhereRaw('CAST(`'.$field.'` AS CHAR) = ?', [$code]);
            }
        });

        $row = $q->orderByDesc($this->db->primaryKey($table))->first();

        return $row !== null ? (array) $row : null;
    }

    /** @return list<array<string, mixed>> */
    /** @param list<string> $codes */
    private function stockMovements(array $codes): array
    {
        try {
            $columns = $this->db->allowedColumns('log_stocks');
        } catch (\Throwable) {
            return [];
        }

        $rows = $this->db->table('log_stocks')
            ->select($columns)
            ->where(function ($sub) use ($codes) {
                foreach ($codes as $code) {
                    $sub->orWhereRaw('log_kode REGEXP ?', [self::exactCodePattern($code)]);
                }
            })
            ->orderByDesc('log_date')
            ->limit(10)
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();

        return $this->vocab->present('log_stocks', $rows, false);
    }
}
