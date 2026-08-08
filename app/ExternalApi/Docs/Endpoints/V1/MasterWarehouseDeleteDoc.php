<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi DELETE /api/external/v1/master/warehouses/{gudang_id} (API-002 lanjutan).
 */
class MasterWarehouseDeleteDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'master-warehouses-delete';
    }

    public function title(): string
    {
        return 'Hapus Gudang';
    }

    public function method(): string
    {
        return 'DELETE';
    }

    public function path(): string
    {
        return '/master/warehouses/{gudang_id}';
    }

    public function group(): string
    {
        return 'master';
    }

    public function description(): string
    {
        return 'Menghapus gudang (soft delete). Ditolak selama gudang masih punya stok produk atau bahan mentah, kecuali force=1 disertakan.';
    }

    public function pathParameters(): array
    {
        return [
            ['name' => 'gudang_id', 'type' => 'integer', 'required' => true, 'description' => 'id gudang yang akan dihapus, lihat GET /master/warehouses.'],
        ];
    }

    public function queryParameters(): array
    {
        return [
            ['name' => 'force', 'type' => 'boolean', 'required' => false, 'description' => 'Kirim force=1 untuk tetap menghapus gudang walau masih ada stok terdaftar di dalamnya.'],
        ];
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                'gudang_id' => 101,
            ],
        ];
    }

    public function errors(): array
    {
        return [
            ['code' => 'not_found', 'http_status' => 404, 'message' => 'Gudang dengan gudang_id tersebut tidak ditemukan (termasuk yang sudah dihapus sebelumnya).'],
            ['code' => 'warehouse_has_stock', 'http_status' => 409, 'message' => 'Gudang masih punya stok produk/bahan mentah terdaftar. Kirim ulang dengan force=1 untuk tetap menghapus.'],
        ];
    }

    public function notes(): array
    {
        return [
            'Ini soft delete: status gudang diubah menjadi 0, bukan baris yang dihapus dari basis data — sama seperti penghapusan lewat halaman admin.',
            'Akses staf ke gudang ini (staff_warehouses) ikut dilepas, dan baris stok produk/bahan mentah di gudang ini dinonaktifkan.',
            'Tanpa force=1, penghapusan ditolak (warehouse_has_stock) selama gudang masih punya baris stok bernilai lebih dari 0.',
        ];
    }
}
