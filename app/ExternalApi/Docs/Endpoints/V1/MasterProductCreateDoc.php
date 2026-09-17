<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi POST /api/external/v1/produk (Data Produk).
 */
class MasterProductCreateDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'produk-create';
    }

    public function title(): string
    {
        return 'Buat Produk';
    }

    public function method(): string
    {
        return 'POST';
    }

    public function path(): string
    {
        return '/produk';
    }

    public function group(): string
    {
        return 'produk';
    }

    public function description(): string
    {
        return 'Membuat produk baru di Pegasus, dihubungkan dengan id produk pada sistem PMO (ref_product_id). Selalu membuat baris baru — bukan upsert; ref_product_id yang sudah dipakai produk lain ditolak.';
    }

    public function bodyParameters(): array
    {
        return [
            ['name' => 'ref_product_id', 'type' => 'integer', 'required' => true, 'description' => 'id produk yang sama pada sistem PMO. Wajib belum pernah dipakai produk lain di Pegasus.'],
            ['name' => 'product_name', 'type' => 'string', 'required' => true, 'description' => 'Nama produk.'],
            ['name' => 'category_id', 'type' => 'integer', 'required' => false, 'description' => 'id kategori produk di Pegasus, kalau sudah diketahui. Wajib diisi kalau category_name tidak dikirim — boleh dikirim BERSAMA category_name (lihat catatan kelas untuk urutan resolusinya).'],
            ['name' => 'category_name', 'type' => 'string', 'required' => false, 'description' => 'Nama kategori — dipakai kalau category_id tidak dikirim, atau dikirim tapi tidak menunjuk kategori aktif. Dicocokkan lewat nama (tidak membedakan huruf besar/kecil) ke kategori Pegasus yang ada; tidak ada yang cocok, kategori baru dibuat otomatis. Wajib diisi kalau category_id tidak dikirim.'],
            ['name' => 'unit_id', 'type' => 'integer ATAU objek', 'required' => true, 'description' => 'Satuan default produk ini. Angka polos = id satuan Pegasus yang SUDAH ADA & aktif (lihat GET /master/units). Objek {ref_unit_id?, unit_name?, unit_short_name?} = satuan yang mungkin belum pernah disinkronkan — ref_unit_id dan unit_name BOLEH dikirim bersamaan (salah satunya wajib ada), lihat catatan kelas untuk urutan resolusinya.'],
            ['name' => 'product_unit', 'type' => 'array of (integer ATAU objek)', 'required' => true, 'description' => 'Daftar satuan yang boleh dipakai untuk produk ini, minimal satu. Tiap unsurnya menerima bentuk yang sama dengan unit_id, boleh dicampur dalam satu array.'],
        ];
    }

    public function requestExample(): ?array
    {
        return [
            'ref_product_id' => 12,
            'product_name' => 'AIR AKI HIKARI',
            'category_id' => 4,
            'category_name' => 'Aki & Baterai',
            'unit_id' => ['ref_unit_id' => 1042, 'unit_name' => 'Dus', 'unit_short_name' => 'dus'],
            'product_unit' => [1, ['ref_unit_id' => 1042, 'unit_name' => 'Dus', 'unit_short_name' => 'dus']],
        ];
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                'id' => 81,
                'ref_product_id' => 12,
                'product_name' => 'AIR AKI HIKARI',
                'category_id' => 4,
                'unit_id' => 15,
                'product_unit' => [1, 15],
            ],
        ];
    }

    public function errors(): array
    {
        return [
            ['code' => 'VALIDATION_FAILED', 'http_status' => 422, 'message' => 'ref_product_id/product_name kosong, category_id maupun category_name sama-sama tidak dikirim, category_id (kalau dikirim) tidak menunjuk kategori aktif, unit_id/unsur product_unit berbentuk angka tapi tidak menunjuk satuan aktif, atau berbentuk objek tapi ref_unit_id/unit_name-nya tidak valid.'],
            ['code' => 'DUPLICATE_REF_ID', 'http_status' => 422, 'message' => 'ref_product_id sudah dipakai produk lain (baik yang masih aktif maupun yang sudah dihapus lewat DELETE produk) — pakai PUT untuk memperbarui produk yang sudah ada.'],
            ['code' => 'AMBIGUOUS_NAME_MATCH', 'http_status' => 422, 'message' => 'unit_id/unsur product_unit atau category_name berbentuk nama, dan ada lebih dari satu satuan/kategori Pegasus dengan nama sama yang belum tersambung — tidak bisa ditebak mana yang dimaksud.'],
        ];
    }

    public function notes(): array
    {
        return [
            'product_name wajib diisi. Dari category_id/category_name, salah satu wajib diisi — boleh dua-duanya sekaligus. unit_id dan product_unit selalu wajib; unsur objeknya boleh mengirim ref_unit_id dan unit_name sekaligus juga.',
            'id pada respons adalah id produk yang dibuat Pegasus sendiri (auto-increment) — SIMPAN nilai ini kalau nanti perlu memanggil PATCH /produk/connect (yang butir connections-nya memakai id Pegasus, bukan ref_product_id).',
            'Bukan upsert: mengirim ref_product_id yang sudah dipakai (aktif maupun yang produknya sudah dihapus) selalu ditolak dengan DUPLICATE_REF_ID, tidak pernah menimpa data yang sudah ada. Pakai PUT /produk/{ref_product_id} untuk memperbarui produk yang rujukannya sudah ada.',
            'Untuk menghubungkan ref_product_id ke produk Pegasus yang SUDAH ADA (dibuat lewat halaman admin atau Pusat Sinkronisasi, misalnya), pakai PATCH /produk/connect — bukan POST ini, yang selalu membuat produk baru.',
            'SINKRONISASI OTOMATIS satuan — urutan resolusi tiap unsur unit_id/product_unit kalau dikirim sebagai objek {ref_unit_id?, unit_name?, unit_short_name?}: (1) ref_unit_id dikirim & sudah ada di Pegasus -> baris itu dipakai/diperbarui langsung, unit_name (kalau ikut dikirim) hanya memperbarui namanya; (2) ref_unit_id tidak dikirim, atau dikirim tapi belum ada -> unit_name (kalau dikirim) dicoba diadopsi ke satuan Pegasus yang belum tersambung PMO, atau satuan baru dibuat kalau tidak ada yang cocok; (3) tidak ada satu pun yang berhasil (ref_unit_id salah & unit_name tidak dikirim) -> VALIDATION_FAILED. Persis logika PUT /master/units/{ref_unit_id} dan SyncUnitStep (Pusat Sinkronisasi).',
            'SINKRONISASI OTOMATIS kategori — urutan resolusi sama: (1) category_id dikirim & menunjuk kategori aktif -> dipakai langsung, category_name (kalau ikut dikirim) diabaikan; (2) category_id tidak dikirim, atau dikirim tapi tidak aktif -> category_name (kalau dikirim) dicocokkan MURNI lewat nama (PMO tidak pernah menerbitkan id kategori) — cocok, dipakai; tidak cocok, kategori baru dibuat; (3) tidak ada satu pun yang berhasil -> VALIDATION_FAILED. Persis logika SyncCategoryStep (Pusat Sinkronisasi).',
            'ref_product_id juga ditulis Pusat Sinkronisasi (menarik data produk dari PMO) — endpoint ini adalah jalur tulis kedua ke kolom yang sama, disengaja karena keduanya melayani sistem yang sama (PMO).',
        ];
    }
}
