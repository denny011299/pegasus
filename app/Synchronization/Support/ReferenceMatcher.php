<?php

namespace App\Synchronization\Support;

use Illuminate\Support\Facades\DB;

/**
 * Pencocokan dua fase antara baris PMO dan baris Pegasus.
 *
 * Fase 1 (adopsi) — selama kolom referensi masih kosong, baris dicocokkan lewat
 * nama. Cocok tepat satu → diadopsi (referensi ditulis ke baris itu). Tidak ada
 * yang cocok → baris baru. Cocok lebih dari satu → **tidak ditebak**; baris
 * dilaporkan gagal berikut id lokal yang bersaing, supaya operator merapikan
 * duplikatnya lebih dulu.
 *
 * Fase 2 (mapan) — begitu referensi terisi, pencocokan murni lewat referensi,
 * sehingga penggantian nama di PMO tidak lagi membuat duplikat.
 *
 * Seluruh isi tabel dimuat sekali di muka agar tidak ada query per baris.
 */
class ReferenceMatcher
{
    /** @var array<int|string, int> referensi PMO => id lokal */
    private array $byReference = [];

    /** @var array<string, array<int, int>> nama ternormalisasi => daftar id lokal yang belum punya referensi */
    private array $adoptableByName = [];

    private bool $loaded = false;

    /**
     * @param  string  $table  tabel Pegasus
     * @param  string  $primaryKey  kolom primary key
     * @param  string|null  $referenceColumn  kolom penyimpan id PMO; null bila PMO tidak menerbitkan id
     * @param  string  $nameColumn  kolom nama untuk adopsi
     * @param  string|null  $scopeColumn  pembatas cakupan, mis. product_id untuk varian
     */
    public function __construct(
        private readonly string $table,
        private readonly string $primaryKey,
        private readonly ?string $referenceColumn,
        private readonly string $nameColumn,
        private readonly ?string $scopeColumn = null,
    ) {
    }

    public function load(): self
    {
        if ($this->loaded) {
            return $this;
        }

        $columns = [$this->primaryKey, $this->nameColumn];
        if ($this->referenceColumn) {
            $columns[] = $this->referenceColumn;
        }
        if ($this->scopeColumn) {
            $columns[] = $this->scopeColumn;
        }

        foreach (DB::table($this->table)->select($columns)->get() as $row) {
            $localId = (int) $row->{$this->primaryKey};

            $reference = $this->referenceColumn ? $row->{$this->referenceColumn} : null;
            if ($reference !== null && $reference !== '') {
                $this->byReference[$this->referenceKey($reference)] = $localId;

                // Baris yang sudah terikat ke PMO tidak boleh diadopsi ulang.
                continue;
            }

            $scope = $this->scopeColumn ? $row->{$this->scopeColumn} : null;
            $key = $this->nameKey((string) $row->{$this->nameColumn}, $scope !== null ? (int) $scope : null);

            if ($key === null) {
                continue;
            }

            $this->adoptableByName[$key][] = $localId;
        }

        $this->loaded = true;

        return $this;
    }

    /**
     * @param  int|string|null  $reference  id PMO, atau null bila entitas tanpa id
     * @param  string  $name  nama dari PMO untuk adopsi
     * @param  int|null  $scopeId  id induk lokal bila matcher dibatasi cakupan
     */
    public function match(int|string|null $reference, string $name, ?int $scopeId = null): MatchResult
    {
        $this->load();

        if ($reference !== null && $reference !== '') {
            $key = $this->referenceKey($reference);
            if (isset($this->byReference[$key])) {
                return MatchResult::byReference($this->byReference[$key]);
            }
        }

        $nameKey = $this->nameKey($name, $scopeId);
        if ($nameKey === null || ! isset($this->adoptableByName[$nameKey])) {
            return MatchResult::notFound();
        }

        $candidates = $this->adoptableByName[$nameKey];

        if (count($candidates) > 1) {
            return MatchResult::ambiguous($candidates);
        }

        return MatchResult::adopted($candidates[0]);
    }

    /**
     * Catat pemetaan baru supaya baris berikutnya dalam eksekusi yang sama
     * tidak mengadopsi baris lokal yang sudah terpakai.
     */
    public function remember(int|string|null $reference, string $name, int $localId, ?int $scopeId = null): void
    {
        $this->load();

        if ($reference !== null && $reference !== '') {
            $this->byReference[$this->referenceKey($reference)] = $localId;
        }

        $nameKey = $this->nameKey($name, $scopeId);
        if ($nameKey === null) {
            return;
        }

        $remaining = array_values(array_filter(
            $this->adoptableByName[$nameKey] ?? [],
            static fn (int $id) => $id !== $localId
        ));

        if ($remaining === []) {
            unset($this->adoptableByName[$nameKey]);
        } else {
            $this->adoptableByName[$nameKey] = $remaining;
        }
    }

    public static function normalise(string $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $value) ?? ''));
    }

    private function referenceKey(int|string $reference): string
    {
        return (string) $reference;
    }

    private function nameKey(string $name, ?int $scopeId): ?string
    {
        $normalised = self::normalise($name);

        if ($normalised === '') {
            return null;
        }

        return $scopeId !== null ? $scopeId.'|'.$normalised : $normalised;
    }
}
