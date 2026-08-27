<?php

namespace App\Support\StockOpname;

/**
 * Pembaca/penulis format string kuantitas dokumen Stock Opname VERSI LAMA ("16 DOS, 0 pcs",
 * dengan token "-" = tidak dihitung).
 *
 * Kelas ini sengaja dibatasi untuk data lama. Dokumen versi baru menyimpan angka di
 * stock_opname_lines dan tidak pernah lewat sini. Jangan dipakai untuk data baru -- seluruh
 * rancang ulang 2026-08-27 ada justru karena format ini tidak bisa mewakili "belum dihitung"
 * tanpa tambal-sulam (flag stod_touched, token "-", humanize, healer, migration backfill).
 *
 * Logikanya disalin apa adanya dari StockController::getQty()/buildQtyString()/
 * humanizeUntouchedForPdf() supaya dokumen lama tetap tercetak PERSIS seperti hari ini.
 */
class LegacyQtyString
{
    /** Ambil qty satu satuan dari string. NULL = tidak dihitung ("-") atau satuan tidak ada. */
    public static function get($string, string $unit): ?int
    {
        foreach (explode(',', (string) $string) as $part) {
            $part = trim($part);
            if ($part === '') continue;
            [$qty, $u] = array_pad(explode(' ', $part, 2), 2, '');
            if ($u === $unit) {
                return $qty === '-' ? null : (int) $qty;
            }
        }

        return null;
    }

    /** Kebalikan dari get() -- ['DOS' => 10, 'pcs' => null] jadi "10 DOS, - pcs". */
    public static function build(array $qtyByUnit): string
    {
        $parts = [];
        foreach ($qtyByUnit as $unit => $qty) {
            $parts[] = ($qty === null ? '-' : $qty).' '.$unit;
        }

        return implode(', ', $parts);
    }

    /** Urai string jadi ['DOS' => 16, 'pcs' => null], mempertahankan urutan satuan aslinya. */
    public static function parse($string): array
    {
        $out = [];
        foreach (explode(',', (string) $string) as $part) {
            $part = trim($part);
            if ($part === '') continue;
            [$qty, $u] = array_pad(explode(' ', $part, 2), 2, '');
            $out[$u] = $qty === '-' ? null : (int) $qty;
        }

        return $out;
    }
}
