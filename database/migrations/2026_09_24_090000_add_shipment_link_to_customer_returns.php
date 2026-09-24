<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * GitHub #203 — POST /shipments/returns perlu terhubung ke shipment asal supaya retur per-nota
 * dari PMO (edit shipment "Berjalan" -> sebagian/semua nota jadi "Belum dikirim") bisa ditelusuri
 * balik ke sales_orders/sales_order_details yang sama dipakai /shipments/shipped, dan supaya
 * permintaan yang diulang (retry PMO) tidak membuat dokumen pengembalian dobel.
 *
 * - ref_shipment_id di header (customer_supply_returns/customer_product_returns, BUKAN detail) —
 *   satu dokumen retur dari PMO selalu berasal dari satu shipment, sama seperti ref_shipment_id
 *   pada sales_orders. Nullable: retur dari halaman admin (CustomerReturnController) tidak pernah
 *   mengisinya.
 * - idempotency_key di header, UNIK NULLABLE — dihitung ShipmentReturnController dari
 *   ref_shipment_id + return_date + isi items[] (lihat App\Http\Controllers\ExternalApi\V1\
 *   ShipmentReturnController::idempotencyKey()). Permintaan retry dengan payload identik
 *   menghasilkan key yang sama -> constraint unique inilah yang jadi jaring pengaman idempotensi
 *   di level DB, bukan cuma cek SELECT dulu (race dua request nyaris bersamaan tetap aman).
 * - ref_nota_id di detail (customer_supply_return_details/customer_product_return_details) — id
 *   nota (oms_order.id) PMO asal baris retur itu, pola SAMA dengan sales_order_details.ref_nota_id
 *   pada /shipments/shipped (GitHub #180). Murni untuk penelusuran, tidak memengaruhi logika, TAPI
 *   ikut dimasukkan ke unique index detail supaya dua baris item+satuan+gudang yang sama TAPI dari
 *   nota berbeda tetap boleh tersimpan sebagai baris terpisah (ShipmentReturnController::
 *   resolveItems() sudah menggabung qty per ref_nota_id sebelum insert, jadi baris yang benar-benar
 *   duplikat termasuk ref_nota_id tetap tidak akan pernah dikirim ke insert()).
 *   customer_product_return_details punya DUA kemungkinan nama unique index tergantung apakah
 *   migrasi 2026_08_15_221500_* (destination_warehouse_id) sudah jalan di database ini:
 *   'cpr_detail_item_dest_unique' (sudah, kolomnya termasuk destination_warehouse_id) atau
 *   'cpr_detail_item_unique' (belum) — dicek dinamis lewat indexNames(), sama pola migrasi itu,
 *   supaya migrasi ini benar di kedua kondisi.
 * - proof_path dilonggarkan jadi NULLABLE di kedua tabel header — retur yang dipicu dari PMO tidak
 *   membawa foto sama sekali (PMO tidak punya field upload di form edit pengirimannya untuk kasus
 *   ini), beda dengan retur dari halaman admin yang bukti fotonya tetap wajib divalidasi di
 *   ShipmentReturnController::validatePayload() kalau ref_shipment_id tidak dikirim.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_supply_returns', function (Blueprint $table) {
            if (! Schema::hasColumn('customer_supply_returns', 'ref_shipment_id')) {
                $table->string('ref_shipment_id', 100)->nullable()->index()->after('so_id');
            }
            if (! Schema::hasColumn('customer_supply_returns', 'idempotency_key')) {
                $table->string('idempotency_key', 64)->nullable()->unique()->after('ref_shipment_id');
            }
            $table->string('proof_path', 255)->nullable()->change();
        });

        Schema::table('customer_product_returns', function (Blueprint $table) {
            if (! Schema::hasColumn('customer_product_returns', 'ref_shipment_id')) {
                $table->string('ref_shipment_id', 100)->nullable()->index()->after('customer_id');
            }
            if (! Schema::hasColumn('customer_product_returns', 'idempotency_key')) {
                $table->string('idempotency_key', 64)->nullable()->unique()->after('ref_shipment_id');
            }
            $table->string('proof_path', 255)->nullable()->change();
        });

        if (! Schema::hasColumn('customer_supply_return_details', 'ref_nota_id')) {
            Schema::table('customer_supply_return_details', function (Blueprint $table) {
                $table->unsignedBigInteger('ref_nota_id')->nullable()->index()->after('warehouse_id');
            });
        }
        if (in_array('csr_detail_item_unique', $this->indexNames('customer_supply_return_details'), true)) {
            Schema::table('customer_supply_return_details', function (Blueprint $table) {
                $table->dropUnique('csr_detail_item_unique');
            });
        }
        if (! in_array('csr_detail_item_unique', $this->indexNames('customer_supply_return_details'), true)) {
            Schema::table('customer_supply_return_details', function (Blueprint $table) {
                $table->unique(
                    ['return_id', 'supplies_id', 'unit_id', 'warehouse_id', 'ref_nota_id'],
                    'csr_detail_item_unique'
                );
            });
        }

        if (! Schema::hasColumn('customer_product_return_details', 'ref_nota_id')) {
            Schema::table('customer_product_return_details', function (Blueprint $table) {
                $table->unsignedBigInteger('ref_nota_id')->nullable()->index()->after('warehouse_id');
            });
        }
        $productIndexes = $this->indexNames('customer_product_return_details');
        $hasDestUnique = in_array('cpr_detail_item_dest_unique', $productIndexes, true);
        $oldUniqueName = $hasDestUnique ? 'cpr_detail_item_dest_unique' : 'cpr_detail_item_unique';
        $newUniqueColumns = $hasDestUnique
            ? ['return_id', 'product_variant_id', 'unit_id', 'warehouse_id', 'destination_warehouse_id', 'ref_nota_id']
            : ['return_id', 'product_variant_id', 'unit_id', 'warehouse_id', 'ref_nota_id'];
        if (in_array($oldUniqueName, $productIndexes, true)) {
            Schema::table('customer_product_return_details', function (Blueprint $table) use ($oldUniqueName) {
                $table->dropUnique($oldUniqueName);
            });
        }
        if (! in_array($oldUniqueName, $this->indexNames('customer_product_return_details'), true)) {
            Schema::table('customer_product_return_details', function (Blueprint $table) use ($oldUniqueName, $newUniqueColumns) {
                $table->unique($newUniqueColumns, $oldUniqueName);
            });
        }
    }

    public function down(): void
    {
        $supplyIndexes = $this->indexNames('customer_supply_return_details');
        if (in_array('csr_detail_item_unique', $supplyIndexes, true)) {
            Schema::table('customer_supply_return_details', function (Blueprint $table) {
                $table->dropUnique('csr_detail_item_unique');
                $table->unique(
                    ['return_id', 'supplies_id', 'unit_id', 'warehouse_id'],
                    'csr_detail_item_unique'
                );
            });
        }
        if (Schema::hasColumn('customer_supply_return_details', 'ref_nota_id')) {
            Schema::table('customer_supply_return_details', function (Blueprint $table) {
                $table->dropColumn('ref_nota_id');
            });
        }

        $productIndexes = $this->indexNames('customer_product_return_details');
        $hasDestUnique = in_array('cpr_detail_item_dest_unique', $productIndexes, true);
        $uniqueName = $hasDestUnique ? 'cpr_detail_item_dest_unique' : 'cpr_detail_item_unique';
        $oldColumns = $hasDestUnique
            ? ['return_id', 'product_variant_id', 'unit_id', 'warehouse_id', 'destination_warehouse_id']
            : ['return_id', 'product_variant_id', 'unit_id', 'warehouse_id'];
        if (in_array($uniqueName, $productIndexes, true)) {
            Schema::table('customer_product_return_details', function (Blueprint $table) use ($uniqueName, $oldColumns) {
                $table->dropUnique($uniqueName);
                $table->unique($oldColumns, $uniqueName);
            });
        }
        if (Schema::hasColumn('customer_product_return_details', 'ref_nota_id')) {
            Schema::table('customer_product_return_details', function (Blueprint $table) {
                $table->dropColumn('ref_nota_id');
            });
        }

        Schema::table('customer_supply_returns', function (Blueprint $table) {
            if (Schema::hasColumn('customer_supply_returns', 'idempotency_key')) {
                $table->dropColumn('idempotency_key');
            }
            if (Schema::hasColumn('customer_supply_returns', 'ref_shipment_id')) {
                $table->dropColumn('ref_shipment_id');
            }
        });

        Schema::table('customer_product_returns', function (Blueprint $table) {
            if (Schema::hasColumn('customer_product_returns', 'idempotency_key')) {
                $table->dropColumn('idempotency_key');
            }
            if (Schema::hasColumn('customer_product_returns', 'ref_shipment_id')) {
                $table->dropColumn('ref_shipment_id');
            }
        });
    }

    /** @return array<int, string> */
    private function indexNames(string $table): array
    {
        return collect(DB::select('SHOW INDEX FROM '.$table))
            ->pluck('Key_name')
            ->map(fn ($name) => (string) $name)
            ->unique()
            ->values()
            ->all();
    }
};
