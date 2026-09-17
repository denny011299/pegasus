<?php

namespace App\ExternalApi\Support;

use App\ExternalApi\Support\Exceptions\AmbiguousNameMatchException;
use App\Synchronization\Support\ReferenceMatcher;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Resolusi kategori lewat nama saja, dipakai MasterProductController saat body
 * produk mengirim category_name (bukan category_id Pegasus) — kategori yang
 * belum pernah disinkronkan lewat jalur manapun.
 *
 * Logikanya SENGAJA disalin dari
 * App\Synchronization\Steps\ProductFlow\SyncCategoryStep: PMO tidak pernah
 * menerbitkan id kategori sama sekali (beda dengan satuan/produk), jadi
 * pencocokan murni lewat category_name lewat ReferenceMatcher (dinormalisasi,
 * tidak membedakan huruf besar/kecil) — cocok tepat satu, category_id itu yang
 * dipakai apa adanya (SyncCategoryStep juga tidak menulis apa pun ke baris
 * yang cocok, statusnya TIDAK dipaksa aktif); cocok lebih dari satu, dilempar
 * AmbiguousNameMatchException; tidak ada yang cocok, kategori baru dibuat
 * (aktif).
 */
class CategoryAutoSync
{
    public function resolve(string $categoryName): int
    {
        $name = trim($categoryName);

        $matcher = (new ReferenceMatcher('categories', 'category_id', null, 'category_name'))->load();
        $match = $matcher->match(null, $name);

        if ($match->isAmbiguous()) {
            throw new AmbiguousNameMatchException('Kategori', $name, $match->candidates);
        }

        if ($match->found()) {
            return $match->localId;
        }

        $now = Carbon::now();

        return (int) DB::table('categories')->insertGetId([
            'category_name' => mb_substr($name, 0, 250),
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ], 'category_id');
    }
}
