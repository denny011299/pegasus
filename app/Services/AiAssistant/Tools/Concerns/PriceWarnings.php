<?php

namespace App\Services\AiAssistant\Tools\Concerns;

/**
 * Peringatan harga Rp 0 di pembelian. Harga 0 di PO ikut terbawa ke Tanda
 * Terima, invoice, dan hutang — user sering bingung "kenapa nominalnya 0 terus"
 * padahal sumbernya harga PO yang belum diisi. Pengiriman (SO) sengaja tidak
 * diperiksa: header pengiriman memang bernilai 0 di alur PMI.
 * Requires $this->db.
 */
trait PriceWarnings
{
    protected function purchaseZeroPriceWarning(int $poId, ?float $poTotal = null): ?string
    {
        $lines = $this->db->table('purchase_orders_details')->where('po_id', $poId)
            ->where(fn ($q) => $q->where('status', '>', 0)->orWhereNull('status'))
            ->get(['pod_nama', 'pod_variant', 'pod_harga']);
        $zero = $lines->filter(fn ($l) => (float) $l->pod_harga == 0.0);
        $poTotal ??= (float) $this->db->table('purchase_orders')->where('po_id', $poId)->value('po_total');

        if ($zero->isEmpty() && $poTotal != 0.0) {
            return null;
        }
        // pod_variant sering sama dengan pod_nama; jangan ditulis dua kali.
        $names = $zero->take(3)->map(fn ($l) => str_contains((string) $l->pod_nama, (string) $l->pod_variant)
            ? trim((string) $l->pod_nama)
            : trim($l->pod_nama.' '.$l->pod_variant))->implode(', ');

        return 'Harga barang Rp 0'.($zero->isNotEmpty() ? ' ('.$zero->count().' dari '.$lines->count().' baris: '.$names.')' : '')
            .'. Karena itu total PO, Tanda Terima, invoice, dan hutangnya ikut Rp 0. '
            .'Minta admin pembelian cek: salah input harga atau memang barang bonus. Kalau salah input, perbaiki harga di PO dulu sebelum membuat Tanda Terima.';
    }
}
