<?php

namespace App\Services\AiAssistant;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use RuntimeException;

/**
 * Strict SELECT-only access for AI tools. Never expose the write connection.
 */
class ReadOnlyDb
{
    public function connection(): Connection
    {
        return DB::connection('ai_readonly');
    }

    public function table(string $table): Builder
    {
        $this->assertTable($table);
        $this->assertSelectOnly();

        return $this->connection()->table($table);
    }

    public function assertTable(string $table): void
    {
        if (!isset(config('ai_assistant.tables')[$table])) {
            // Message reaches the model: keep it free of storage names.
            throw new InvalidArgumentException('Data ini tidak tersedia untuk asisten.');
        }
    }

    public function primaryKey(string $table): string
    {
        $this->assertTable($table);

        return config("ai_assistant.tables.{$table}.pk");
    }

    /** @return list<string> */
    public function allowedColumns(string $table): array
    {
        $this->assertTable($table);
        $cols = config("ai_assistant.tables.{$table}.columns", []);
        $hidden = array_map('strtolower', config('ai_assistant.hidden_columns', []));

        // Only columns that actually exist on the local table (schema drift safe).
        $existing = [];
        try {
            $existing = Schema::connection('ai_readonly')->getColumnListing($table);
        } catch (\Throwable) {
            $existing = [];
        }
        if ($existing !== []) {
            $cols = array_values(array_intersect($cols, $existing));
        }

        return array_values(array_filter($cols, function ($col) use ($hidden) {
            $lower = strtolower((string) $col);
            foreach ($hidden as $h) {
                if ($lower === $h || str_contains($lower, $h)) {
                    return false;
                }
            }

            return true;
        }));
    }

    /** @param list<string> $requested */
    public function filterColumns(string $table, array $requested = []): array
    {
        $allowed = $this->allowedColumns($table);
        if ($requested === []) {
            return $allowed;
        }

        $out = [];
        foreach ($requested as $col) {
            if (!in_array($col, $allowed, true)) {
                throw new InvalidArgumentException('Informasi ini tidak tersedia untuk asisten.');
            }
            $out[] = $col;
        }

        return $out;
    }

    public function assertSelectOnly(): void
    {
        // Defense in depth: refuse if someone swapped the connection name.
        if ($this->connection()->getName() !== 'ai_readonly') {
            throw new RuntimeException('AI tools must use the ai_readonly connection.');
        }
    }
}
