<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\ProductionPlanning;
use App\Models\ProductVariant;
use App\Models\SalesOrder;
use App\Models\SalesOrderDetail;
use App\Models\ShipmentShortageDocument;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * Demo lokal: SO Dijadwalkan + dokumen BG + draft Production Planning.
 *
 *   php artisan db:seed --class=DemoShipmentShortageSeeder
 *
 * Idempotent per ref DEMO-SHORTAGE-LOCAL-XXX.
 * List /salesOrder menampilkan icon print Form Kekurangan bila has_shortage_doc.
 */
class DemoShipmentShortageSeeder extends Seeder
{
    private const REF_PREFIX = 'DEMO-SHORTAGE-LOCAL-';

    /** Cukup untuk demo UI (print + PP), bukan bulk 25. */
    private const COUNT = 8;

    private const STATUS_SCHEDULED = 4;

    private const REF_NUMBER = 'DEMO';

    public function run(): void
    {
        if (! Schema::hasTable('shipment_shortage_documents')) {
            $this->command?->error('Tabel shipment_shortage_documents belum ada. Migrate path lokal dulu.');

            return;
        }
        if (! Schema::hasTable('production_plannings')) {
            $this->command?->error('Tabel production_plannings belum ada. Migrate PP dulu.');

            return;
        }

        $armadas = Customer::query()
            ->where('status', 1)
            ->whereNotNull('customer_code')
            ->where('customer_code', '!=', '')
            ->orderBy('customer_id')
            ->limit(30)
            ->get();
        if ($armadas->isEmpty()) {
            $this->command?->error('Tidak ada armada/customer aktif.');

            return;
        }

        $variants = ProductVariant::query()
            ->where('status', 1)
            ->whereNotNull('product_variant_sku')
            ->where('product_variant_sku', '!=', '')
            ->orderBy('product_variant_id')
            ->limit(20)
            ->get();
        if ($variants->isEmpty()) {
            $this->command?->error('Tidak ada product_variant aktif.');

            return;
        }

        $unit = Unit::query()->where('status', 1)->orderBy('unit_id')->first();
        $unitId = $unit?->unit_id ?? 1;
        $refUnitId = (int) ($unit?->ref_unit_id ?: $unitId);

        // Tanggal hari ini supaya kelihatan di filter default Minggu Ini di PP
        $soDate = Carbon::today()->toDateString();

        $created = 0;
        $skipped = 0;
        $ppLinked = 0;

        for ($i = 1; $i <= self::COUNT; $i++) {
            $ref = self::REF_PREFIX.str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            if (SalesOrder::where('ref_shipment_id', $ref)->exists()) {
                $skipped++;
                $so = SalesOrder::where('ref_shipment_id', $ref)->first();
                // Refresh tanggal + penanda supaya muncul di filter Minggu Ini / cari DEMO
                if ($so) {
                    $so->so_date = $soDate;
                    $so->so_ref_number = self::REF_NUMBER;
                    $so->status = self::STATUS_SCHEDULED;
                    $so->save();
                }
                $doc = $so
                    ? ShipmentShortageDocument::where('so_id', $so->so_id)->where('status', 1)->first()
                    : null;
                if (! $doc && $so) {
                    // BG hilang — buat ulang supaya icon print muncul
                    ShipmentShortageDocument::createForShortage(
                        (int) $so->so_id,
                        $ref,
                        [[
                            'sku' => (string) ($variants[0]->product_variant_sku ?? 'DEMO'),
                            'unit_id' => $refUnitId,
                            'requested' => 20,
                            'available' => 0,
                            'shortage' => 20,
                        ]],
                        null,
                    );
                    $doc = ShipmentShortageDocument::where('so_id', $so->so_id)->where('status', 1)->first();
                }
                if ($doc) {
                    $pp = ProductionPlanning::createDraftFromShortage($doc);
                    if ($pp) {
                        if ($pp->pp_date !== $soDate) {
                            $pp->pp_date = $soDate;
                            $pp->save();
                        }
                        $ppLinked++;
                    }
                }

                continue;
            }

            $armada = $armadas[$i % $armadas->count()];
            $variant = $variants[$i % $variants->count()];
            $variant2 = $variants[($i + 3) % $variants->count()];

            $requested = 12 + ($i * 3);
            $available = max(0, (int) floor($requested * 0.3) - ($i % 4));
            $shortage = max(1, $requested - $available);

            $so = (new SalesOrder())->insertSalesOrder([
                'so_customer' => (string) $armada->customer_id,
                'so_date' => $soDate,
                'so_total' => 0,
                'so_img' => json_encode([]),
            ]);
            $so->ref_shipment_id = $ref;
            $so->so_ref_number = self::REF_NUMBER;
            $so->status = self::STATUS_SCHEDULED;
            $so->created_at = now();
            $so->updated_at = now();
            $so->save();

            (new SalesOrderDetail())->insertSalesOrderDetail([
                'so_id' => $so->so_id,
                'product_variant_id' => $variant->product_variant_id,
                'product_name' => $variant->product_variant_name ?: 'Demo',
                'product_variant_name' => $variant->product_variant_name ?: '',
                'product_variant_sku' => $variant->product_variant_sku,
                'unit_id' => $unitId,
                'warehouse_id' => 1,
                'product_variant_price' => 0,
                'so_qty' => $requested,
                'so_subtotal' => 0,
            ]);

            $items = [
                [
                    'sku' => $variant->product_variant_sku,
                    'unit_id' => $refUnitId,
                    'requested' => $requested,
                    'available' => $available,
                    'shortage' => $shortage,
                ],
            ];
            if ($i % 2 === 0 && $variant2->product_variant_id !== $variant->product_variant_id) {
                $items[] = [
                    'sku' => $variant2->product_variant_sku,
                    'unit_id' => $refUnitId,
                    'requested' => 8 + $i,
                    'available' => 0,
                    'shortage' => 8 + $i,
                ];
            }

            // Hook di createForShortage → auto draft PP
            ShipmentShortageDocument::createForShortage(
                (int) $so->so_id,
                $ref,
                $items,
                null,
            );
            $created++;
            $ppLinked++;
        }

        // Backfill: semua BG aktif tanpa PP
        $backfill = 0;
        if (Schema::hasTable('production_plannings')) {
            $docs = ShipmentShortageDocument::query()
                ->where('status', 1)
                ->orderByDesc('id')
                ->limit(80)
                ->get();
            foreach ($docs as $doc) {
                $exists = ProductionPlanning::where('shipment_shortage_document_id', $doc->id)
                    ->where('status', 1)
                    ->exists();
                if ($exists) {
                    continue;
                }
                $pp = ProductionPlanning::createDraftFromShortage($doc);
                if ($pp) {
                    $pp->pp_date = $soDate;
                    $pp->save();
                    $backfill++;
                }
            }
        }

        $this->command?->info("Demo shortage: created={$created}, skipped={$skipped}, pp_linked≈{$ppLinked}, pp_backfill={$backfill}");
        $this->command?->info('Cek /salesOrder (ref DEMO) + /productionPlanning (draft dari shortage).');
    }
}
