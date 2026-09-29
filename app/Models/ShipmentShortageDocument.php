<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

/**
 * Dokumen kekurangan stok — dibuat otomatis oleh POST /api/external/v1/shipments/scheduled
 * bila ada item shortage > 0 (flag auto_create_shortage_doc di body hanya kompatibilitas PMO).
 *
 * Dipakai list Pengiriman (warning kuning + print Form Kekurangan) dan PDF
 * GET /shipmentShortage/{soId}/print. Satu dokumen aktif per so_id; items = snapshot JSON.
 */
class ShipmentShortageDocument extends Model
{
    protected $table = 'shipment_shortage_documents';
    protected $primaryKey = 'id';
    public $timestamps = true;
    public $incrementing = true;

    protected $casts = [
        'items' => 'array',
    ];

    /**
     * @param  array<int, array{sku:string, unit_id:int, requested:int, available:int, shortage:int}>  $shortageItems
     */
    public static function createForShortage(
        int $soId,
        string $refShipmentId,
        array $shortageItems,
        ?int $createdBy,
    ): self {
        // doc_number dihasilkan dari max(id)+1, sama seperti generateSalesOrderID() -
        // race antar permintaan nyaris bersamaan ditangani lewat retry ringan di sini, bukan
        // lock/transaction terpisah, cukup untuk volume dokumen ini.
        $attempts = 0;
        while (true) {
            $attempts++;

            $doc = new self();
            $doc->doc_number = self::generateDocNumber();
            $doc->so_id = $soId;
            $doc->ref_shipment_id = $refShipmentId;
            $doc->items = $shortageItems;
            $doc->status = 1;
            $doc->created_by = $createdBy;

            try {
                $doc->save();

                // 1 shortage doc = 1 draft Production Planning (fase 1)
                try {
                    ProductionPlanning::createDraftFromShortage($doc);
                } catch (\Throwable $e) {
                    // Jangan gagalkan shortage doc kalau PP gagal
                    report($e);
                }

                return $doc;
            } catch (QueryException $e) {
                if ($attempts >= 3) {
                    throw $e;
                }
                // doc_number bentrok dengan dokumen yang baru dibuat permintaan lain - ulangi
                // dengan angka berikutnya.
            }
        }
    }

    private static function generateDocNumber(): string
    {
        $id = (int) self::max('id');
        $id++;

        return 'BG-'.str_pad((string) $id, 4, '0', STR_PAD_LEFT);
    }
}
