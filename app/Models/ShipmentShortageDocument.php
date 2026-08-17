<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

/**
 * Dokumen kekurangan stok - dibuat opsional oleh POST /api/external/v1/shipments/scheduled
 * (App\Http\Controllers\ExternalApi\V1\ShipmentController::scheduled()) saat pemanggil mengirim
 * auto_create_shortage_doc: true DAN ada item yang shortage-nya > 0.
 *
 * Murni catatan untuk staf gudang/pembelian - satu dokumen per shipment yang kekurangan
 * (so_id), berisi snapshot item yang short saat itu. Tidak dikonsumsi modul lain, tidak ada
 * halaman admin untuk ini saat ini.
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
