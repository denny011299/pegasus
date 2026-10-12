<?php

namespace App\Services\AiAssistant;

use InvalidArgumentException;

/**
 * Translates between business wording (what the model and users speak) and
 * storage keys (what the read-only connection needs). Every tool passes its
 * rows through present() so raw keys and raw reference numbers never reach
 * the model. Config lives in config/ai_vocabulary.php.
 */
class Vocabulary
{
    /** @var array<string, array<string, string>> table => alias => column */
    private array $aliasCache = [];

    /** @var array<string, array<string|int, string>> "table.pk" => id => name */
    private array $nameCache = [];

    public function __construct(private ReadOnlyDb $db) {}

    /** Module name, alias, or raw table name => raw table name. */
    public function table(string $name): string
    {
        $needle = $this->normalize($name);
        $modules = config('ai_vocabulary.modules', []);

        foreach ($modules as $module => $def) {
            if ($this->normalize($module) === $needle || $this->normalize($def['table']) === $needle) {
                return $def['table'];
            }
            foreach ($def['aliases'] ?? [] as $alias) {
                if ($this->normalize($alias) === $needle) {
                    return $def['table'];
                }
            }
        }

        $tables = config('ai_assistant.tables', []);
        if (isset($tables[$name])) {
            return $name;
        }
        foreach (array_keys($tables) as $table) {
            if ($this->normalize($table) === $needle) {
                return $table;
            }
        }

        throw new InvalidArgumentException('Data tidak tersedia untuk: '.$name);
    }

    /** @return array<string, mixed> */
    public function module(string $table): array
    {
        foreach (config('ai_vocabulary.modules', []) as $def) {
            if ($def['table'] === $table) {
                return $def;
            }
        }

        return [];
    }

    /** Business module names the model is allowed to ask for. */
    public function moduleCatalog(): array
    {
        $out = [];
        foreach (config('ai_vocabulary.modules', []) as $module => $def) {
            $out[$module] = $def['label'] ?? $module;
        }

        return $out;
    }

    public function moduleLabel(string $table): string
    {
        return $this->module($table)['label'] ?? 'Data';
    }

    public function dateField(string $table): ?string
    {
        $field = $this->module($table)['date'] ?? null;
        $allowed = $this->db->allowedColumns($table);

        if ($field !== null && in_array($field, $allowed, true)) {
            return $field;
        }

        return in_array('created_at', $allowed, true) ? 'created_at' : null;
    }

    public function amountField(string $table): ?string
    {
        $field = $this->module($table)['amount'] ?? null;

        return $field !== null && in_array($field, $this->db->allowedColumns($table), true) ? $field : null;
    }

    /** @return list<string> */
    public function codeFields(string $table): array
    {
        $allowed = $this->db->allowedColumns($table);

        return array_values(array_intersect($this->module($table)['codes'] ?? [], $allowed));
    }

    /** @return array{table: string, fk: string}|null */
    public function detail(string $table): ?array
    {
        $detail = $this->module($table)['detail'] ?? null;
        if (!is_array($detail)) {
            return null;
        }

        try {
            $this->db->assertTable($detail['table']);
        } catch (InvalidArgumentException) {
            return null;
        }

        return $detail;
    }

    /** Field alias (business wording or raw key) => raw column. */
    public function column(string $table, string $key): string
    {
        $aliases = $this->columnAliases($table);
        $needle = $this->normalize($key);

        if (isset($aliases[$needle])) {
            return $aliases[$needle];
        }
        $compact = str_replace(' ', '', $needle);
        if (isset($aliases[$compact])) {
            return $aliases[$compact];
        }

        throw new InvalidArgumentException('Informasi "'.$key.'" tidak tersedia pada '.$this->moduleLabel($table));
    }

    public function hasColumn(string $table, string $key): bool
    {
        try {
            $this->column($table, $key);

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    public function label(string $column): string
    {
        $labels = config('ai_vocabulary.labels', []);
        if (isset($labels[$column])) {
            return $labels[$column];
        }

        return $this->humanize($column);
    }

    public function statusLabel(string $table, mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }
        $map = $this->statusMap($table);

        // Unknown code: say so instead of letting the model guess a meaning.
        return $map[(int) $value] ?? 'Kode status '.$value.' (arti belum terdaftar)';
    }

    /** @return array<int, string> */
    public function statusMap(string $table): array
    {
        return config('ai_vocabulary.status.tables.'.$table)
            ?? config('ai_vocabulary.status.default', []);
    }

    /**
     * Status codes whose label means the record no longer counts
     * (deleted, rejected, cancelled, inactive).
     *
     * @return list<int>
     */
    public function inactiveStatuses(string $table): array
    {
        $words = config('ai_vocabulary.status.inactive_words', []);
        $out = [];
        foreach ($this->statusMap($table) as $code => $label) {
            foreach ($words as $word) {
                if (str_contains(strtolower($label), $word)) {
                    $out[] = (int) $code;
                    break;
                }
            }
        }

        return $out;
    }

    public function valueLabel(string $table, string $column, mixed $value): mixed
    {
        if ($column === 'status') {
            return $this->statusLabel($table, $value);
        }
        $map = config('ai_vocabulary.values', [])[$table.'.'.$column] ?? null;
        if (is_array($map) && $value !== null && isset($map[(int) $value])) {
            return $map[(int) $value];
        }
        if (in_array($column, ['is_draft', 'is_old_version', 'is_main_warehouse', 'is_kepala_cabang', 'resolved_by_system', 'pr_default', 'sol_use_system_stock', 'sobl_use_system_stock'], true)) {
            return ((int) $value) === 1 ? 'Ya' : 'Tidak';
        }

        return $value;
    }

    /**
     * Re-key rows with UI labels, turn codes into badge text, and replace
     * internal references with the names users see.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    public function present(string $table, array $rows, bool $keepRef = true, array $hide = []): array
    {
        if ($rows === []) {
            return [];
        }

        $pk = $this->db->primaryKey($table);
        $lookups = $this->lookupsFor($table, $rows);
        $names = $this->resolveNames($rows, $lookups);
        $hide = array_merge($hide, config('ai_vocabulary.hide_fields', [])[$table] ?? []);

        $out = [];
        foreach ($rows as $row) {
            $item = [];
            foreach ($row as $column => $value) {
                if ($column === $pk) {
                    if ($keepRef) {
                        $item['Ref'] = $value;
                    }
                    continue;
                }
                if ($value === null || $value === '' || in_array($column, $hide, true)) {
                    continue;
                }
                // "Diperbarui" only matters when something actually changed.
                if ($column === 'updated_at' && ($row['created_at'] ?? null) === $value) {
                    continue;
                }

                $label = $this->label($column);
                $lookup = $this->lookupFor($lookups, $column, $row);

                if ($lookup !== null && is_numeric($value)) {
                    $resolved = $names[$lookup['table'].'.'.$lookup['pk']][(string) $value] ?? null;
                    $item[$label] = ($resolved !== null && $resolved !== '') ? $resolved : $value;
                    continue;
                }

                $item[$label] = $this->valueLabel($table, $column, $value);
            }
            $out[] = $item;
        }

        return $out;
    }

    /**
     * Reference definitions that apply to these rows, including the ones
     * whose target depends on another field in the same row.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, array<string, mixed>>
     */
    private function lookupsFor(string $table, array $rows): array
    {
        $lookups = config('ai_vocabulary.lookups', []);

        foreach (config('ai_vocabulary.conditional_lookups', []) as $path => $rule) {
            [$ruleTable, $column] = explode('.', $path);
            if ($ruleTable !== $table) {
                continue;
            }
            $lookups[$column] = ['conditional' => $rule];
        }

        return $lookups;
    }

    /**
     * @param  array<string, array<string, mixed>>  $lookups
     * @param  array<string, mixed>  $row
     * @return array{table: string, pk: string, name: string, fallback?: string}|null
     */
    private function lookupFor(array $lookups, string $column, array $row): ?array
    {
        $def = $lookups[$column] ?? null;
        if ($def === null) {
            return null;
        }
        if (!isset($def['conditional'])) {
            return $def;
        }

        $rule = $def['conditional'];
        $switch = $row[$rule['on']] ?? null;

        return $rule['map'][(int) $switch] ?? null;
    }

    /**
     * Batch-resolve every reference in the rows (one query per referenced
     * module) so presenting stays cheap.
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, array<string, mixed>>  $lookups
     * @return array<string, array<string, string>>
     */
    private function resolveNames(array $rows, array $lookups): array
    {
        $wanted = [];
        $defs = [];
        foreach ($rows as $row) {
            foreach ($row as $column => $value) {
                if (!is_numeric($value)) {
                    continue;
                }
                $def = $this->lookupFor($lookups, (string) $column, $row);
                if ($def === null) {
                    continue;
                }
                $key = $def['table'].'.'.$def['pk'];
                $defs[$key] = $def;
                $wanted[$key][(string) $value] = $value;
            }
        }

        $resolved = [];
        foreach ($wanted as $key => $ids) {
            $def = $defs[$key];
            $cached = $this->nameCache[$key] ?? [];
            $missing = array_values(array_diff_key($ids, $cached));

                if ($missing !== []) {
                $nameColumns = is_array($def['name']) ? $def['name'] : [$def['name']];
                $columns = array_merge([$def['pk']], $nameColumns);
                if (isset($def['fallback'])) {
                    $columns[] = $def['fallback'];
                }
                try {
                    $rowsFound = $this->db->table($def['table'])
                        ->select($columns)
                        ->whereIn($def['pk'], $missing)
                        ->limit(count($missing))
                        ->get();
                    foreach ($rowsFound as $found) {
                        $parts = [];
                        foreach ($nameColumns as $nameColumn) {
                            $part = (string) ($found->{$nameColumn} ?? '');
                            if ($part !== '') {
                                $parts[] = $part;
                            }
                        }
                        $name = implode(' - ', $parts);
                        if ($name === '' && isset($def['fallback'])) {
                            $name = (string) ($found->{$def['fallback']} ?? '');
                        }
                        $cached[(string) $found->{$def['pk']}] = $name;
                    }
                } catch (\Throwable) {
                    // Reference module unreadable: keep the plain value.
                }
                $this->nameCache[$key] = $cached;
            }
            $resolved[$key] = $cached;
        }

        return $resolved;
    }

    /** @return array<string, string> */
    private function columnAliases(string $table): array
    {
        if (isset($this->aliasCache[$table])) {
            return $this->aliasCache[$table];
        }

        $map = [];
        $add = function (string $alias, string $column) use (&$map) {
            $alias = $this->normalize($alias);
            if ($alias === '' || isset($map[$alias])) {
                return;
            }
            $map[$alias] = $column;
            $compact = str_replace(' ', '', $alias);
            if (!isset($map[$compact])) {
                $map[$compact] = $column;
            }
        };

        foreach ($this->db->allowedColumns($table) as $column) {
            $add($column, $column);
            $add($this->label($column), $column);
            // "so_invoice_no" is also reachable as "invoice no".
            if (str_contains($column, '_')) {
                $add(substr($column, strpos($column, '_') + 1), $column);
            }
        }

        $specials = [
            'tanggal' => $this->dateField($table),
            'date' => $this->dateField($table),
            'total' => $this->amountField($table),
            'nominal' => $this->amountField($table),
            'jumlah' => $this->amountField($table),
            'kode' => $this->codeFields($table)[0] ?? null,
            'nomor' => $this->codeFields($table)[0] ?? null,
            'no' => $this->codeFields($table)[0] ?? null,
            'code' => $this->codeFields($table)[0] ?? null,
            'no dokumen' => $this->codeFields($table)[0] ?? null,
            'pembuat' => 'created_by',
            'dibuat oleh' => 'created_by',
            'penyetuju' => 'acc_by',
            'disetujui oleh' => 'acc_by',
            'acc' => 'acc_by',
            'pelanggan' => $table === 'sales_orders' ? 'so_customer' : 'customer_id',
            'customer' => $table === 'sales_orders' ? 'so_customer' : 'customer_id',
            'pemasok' => $table === 'purchase_orders' ? 'po_supplier' : 'supplier_id',
            'supplier' => $table === 'purchase_orders' ? 'po_supplier' : 'supplier_id',
        ];
        $allowed = $this->db->allowedColumns($table);
        foreach ($specials as $alias => $column) {
            if ($column !== null && in_array($column, $allowed, true)) {
                $add($alias, $column);
            }
        }

        return $this->aliasCache[$table] = $map;
    }

    private function humanize(string $column): string
    {
        $words = [
            'id' => 'Ref', 'no' => 'No.', 'qty' => 'Qty', 'sku' => 'SKU', 'ppn' => 'PPN',
            'nama' => 'Nama', 'desc' => 'Keterangan', 'notes' => 'Catatan', 'note' => 'Catatan',
            'date' => 'Tanggal', 'total' => 'Total', 'harga' => 'Harga', 'nominal' => 'Nominal',
            'stock' => 'Stok', 'stok' => 'Stok', 'selisih' => 'Selisih', 'type' => 'Jenis',
            'kode' => 'Kode', 'number' => 'Nomor', 'name' => 'Nama', 'code' => 'Kode',
            'by' => 'Oleh', 'at' => 'Waktu', 'ref' => 'Referensi', 'num' => 'Nomor',
        ];

        $parts = [];
        foreach (explode('_', $column) as $part) {
            $parts[] = $words[$part] ?? ucfirst($part);
        }

        return trim(implode(' ', $parts));
    }

    private function normalize(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }
}
