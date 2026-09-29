<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductionPlanning;
use App\Models\ProductionPlanningItem;
use App\Models\ProductionSkala;
use App\Models\ProductionWorkOrder;
use App\Models\ProductVariant;
use App\Models\Staff;
use App\Models\StaffWarehouse;
use App\Models\Unit;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Demo dari foto SPK manual 28/8 (Pegasus Hikari).
 *
 *   php artisan db:seed --class=DemoSpkHandwrittenSeeder
 *
 * Idempotent via notes marker DEMO-SPK-HANDWRITTEN-2808.
 */
class DemoSpkHandwrittenSeeder extends Seeder
{
    private const MARKER = 'DEMO-SPK-HANDWRITTEN-2808';

    /** Tanggal di kertas; filter PP default minggu ini — pakai hari ini agar muncul di UI. */
    private const PAPER_DATE = '2026-08-28';

    public function run(): void
    {
        if (! Schema::hasTable('production_plannings') || ! Schema::hasTable('production_work_orders')) {
            $this->command?->error('Tabel produksi belum ada. Migrate dulu.');

            return;
        }

        $existing = ProductionPlanning::where('status', 1)
            ->where('notes', 'like', '%'.self::MARKER.'%')
            ->first();
        if ($existing) {
            $this->command?->warn('Sudah ada: '.$existing->pp_number.' (hapus dulu kalau mau seed ulang).');

            return;
        }

        $whId = (int) (Warehouse::firstMainId() ?: 1);
        $unitDos = Unit::where('status', 1)->where('unit_name', 'like', 'DOS%')->value('unit_id') ?: 7;
        $unitKg = Unit::where('status', 1)->where('unit_name', 'like', 'Kilogram%')->value('unit_id') ?: 1;
        $categoryId = (int) (Category::where('status', 1)->value('category_id') ?: 1);

        $skalaByCode = ProductionSkala::where('status', 1)->get()->keyBy(fn ($s) => strtoupper(trim($s->code)));
        $armadaId = (int) (Customer::where('status', 1)
            ->whereNotNull('customer_code')
            ->where('customer_code', '!=', '')
            ->orderBy('customer_id')
            ->value('customer_id') ?: 0);

        $pics = [
            'Supri', 'Aina', 'Aini', 'Saka', 'Riyanto', 'Linn', 'Aki',
        ];
        $staffIds = [];
        foreach ($pics as $name) {
            $staffIds[$name] = $this->ensureStaff($name, $whId);
        }

        // Baris dari foto SPK (hasil null = belum diisi)
        $rows = [
            ['name' => 'Hikari Greentech 13 kg', 'sku' => 'DEMO-SPK-GT13', 'skala' => 'M2', 'pic' => 'Supri', 'qty' => 3, 'hasil' => 3, 'unit_id' => $unitKg, 'unit' => 'KG'],
            ['name' => 'Hikari Super grease 24x4', 'sku' => 'DEMO-SPK-SG24', 'skala' => 'M2', 'pic' => 'Supri', 'qty' => 2, 'hasil' => 2, 'unit_id' => $unitDos, 'unit' => 'DOS'],
            ['name' => 'Hikari Tyre polish 30 x 600', 'sku' => 'DEMO-SPK-TP30', 'skala' => 'M4', 'pic' => 'Aina', 'qty' => 2, 'hasil' => 2, 'unit_id' => $unitDos, 'unit' => 'DOS'],
            ['name' => 'Pegasus Radiator Coolant 12x1', 'sku' => 'DEMO-SPK-RC12', 'skala' => '3', 'pic' => 'Aini', 'qty' => 250, 'hasil' => 228, 'unit_id' => $unitDos, 'unit' => 'DOS'],
            ['name' => 'Pegasus Air Accu 12x1500', 'sku' => 'DEMO-SPK-AA12', 'skala' => '4', 'pic' => 'Saka', 'qty' => 200, 'hasil' => 200, 'unit_id' => $unitDos, 'unit' => 'DOS'],
            ['name' => 'Hikari Air Accu 20x400', 'sku' => 'DEMO-SPK-AA20', 'skala' => '4', 'pic' => 'Saka', 'qty' => 300, 'hasil' => 8, 'unit_id' => $unitDos, 'unit' => 'DOS'],
            ['name' => 'Hikari Radiator Coolant 4x5 Car 1', 'sku' => 'DEMO-SPK-RC45', 'skala' => '4', 'pic' => 'Riyanto', 'qty' => 100, 'hasil' => 71, 'unit_id' => $unitDos, 'unit' => 'DOS'],
            ['name' => 'Pegasus Silicone 30x400', 'sku' => 'DEMO-SPK-SI30', 'skala' => '4', 'pic' => 'Linn', 'qty' => 60, 'hasil' => null, 'unit_id' => $unitDos, 'unit' => 'DOS'],
        ];

        $uiDate = Carbon::today()->toDateString();

        DB::transaction(function () use ($rows, $skalaByCode, $armadaId, $staffIds, $whId, $categoryId, $uiDate) {
            $pp = new ProductionPlanning();
            $pp->pp_number = ProductionPlanning::generatePpNumber($uiDate, $whId);
            $pp->pp_date = $uiDate;
            $pp->warehouse_id = $whId;
            $pp->pp_status = 'inprod';
            $pp->spkp_number = 'SPKP-'.Carbon::parse($uiDate)->format('ymd').'-DEMO';
            $pp->notes = self::MARKER.' | SPK kertas '.self::PAPER_DATE.' | Diterima: Aki';
            $pp->status = 1;
            $pp->approved_at = now();
            $pp->save();

            $byPic = [];
            foreach ($rows as $row) {
                $variant = $this->ensureVariant($row['sku'], $row['name'], $categoryId, (int) $row['unit_id']);
                $skala = $skalaByCode->get(strtoupper($row['skala']));
                if (! $skala) {
                    throw new \RuntimeException('Skala tidak ada: '.$row['skala']);
                }
                $needsArmada = (bool) preg_match('/^M[1-4]$/i', trim($skala->code));
                $picId = (int) $staffIds[$row['pic']];

                $item = new ProductionPlanningItem();
                $item->production_planning_id = $pp->production_planning_id;
                $item->product_variant_id = $variant->product_variant_id;
                $item->sku = $variant->product_variant_sku;
                $item->product_name = $row['name'];
                $item->qty = $row['qty'];
                $item->actual_qty = $row['hasil'];
                $item->unit_id = $row['unit_id'];
                $item->unit_label = $row['unit'];
                $item->production_skala_id = $skala->production_skala_id;
                $item->pic_staff_id = $picId;
                $item->armada_customer_id = $needsArmada ? ($armadaId ?: null) : null;
                $item->status = 1;
                $item->save();

                $byPic[$picId][] = ['item' => $item, 'row' => $row];
            }

            $allWoDone = true;
            foreach ($byPic as $picId => $group) {
                $wo = new ProductionWorkOrder();
                $wo->production_planning_id = $pp->production_planning_id;
                $wo->warehouse_id = $whId;
                $wo->pic_staff_id = $picId;
                $wo->production_line = 'Lini demo SPK';
                $wo->wo_number = ProductionWorkOrder::generateWoNumber($uiDate, $whId);
                $wo->wo_date = $uiDate;
                $wo->status = 1;
                $wo->execution_status = 'inprod';
                $wo->created_by = $picId;
                $wo->save();

                $complete = true;
                foreach ($group as $g) {
                    /** @var ProductionPlanningItem $item */
                    $item = $g['item'];
                    $item->production_work_order_id = $wo->production_work_order_id;
                    $item->save();
                    $hasil = $g['row']['hasil'];
                    if ($hasil === null || (float) $hasil + 0.0001 < (float) $g['row']['qty']) {
                        $complete = false;
                    }
                }

                if ($complete) {
                    $wo->execution_status = 'done';
                    $wo->production_completed_at = now();
                    $wo->closed_at = now();
                    $wo->save();
                } else {
                    $allWoDone = false;
                }
            }

            if ($allWoDone) {
                $pp->pp_status = 'done';
                $pp->save();
            }

            $this->command?->info('PP '.$pp->pp_number.' — '.count($rows).' item, '.count($byPic).' WO (PIC dari SPK).');
            $this->command?->info('Tanggal UI: '.$uiDate.' (kertas '.self::PAPER_DATE.' di notes).');
        });
    }

    private function ensureStaff(string $name, int $warehouseId): int
    {
        $existing = Staff::where('status', 1)
            ->whereRaw('LOWER(TRIM(staff_name)) = ?', [strtolower($name)])
            ->first();
        if ($existing) {
            $this->ensureWarehouseLink((int) $existing->staff_id, $warehouseId);

            return (int) $existing->staff_id;
        }

        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '', $name) ?: 'spk');
        $username = 'demo.spk.'.$slug;
        $n = 0;
        while (Staff::where('staff_username', $username)->exists()) {
            $n++;
            $username = 'demo.spk.'.$slug.$n;
        }

        $staff = new Staff();
        $staff->staff_name = $name;
        $staff->staff_code = 'DEMO-SPK-'.strtoupper($slug);
        $staff->staff_email = $username.'@demo.local';
        $staff->staff_phone = '08000000000';
        $staff->staff_address = 'Demo SPK';
        $staff->staff_username = $username;
        $staff->staff_password = Hash::make('demo1234');
        $staff->role_id = 7; // Staf QC & Gudang
        $staff->status = 1;
        $staff->save();

        $this->ensureWarehouseLink((int) $staff->staff_id, $warehouseId);
        $this->command?->info('Staff baru: '.$name.' ('.$username.')');

        return (int) $staff->staff_id;
    }

    private function ensureWarehouseLink(int $staffId, int $warehouseId): void
    {
        if (! Schema::hasTable('staff_warehouses')) {
            return;
        }
        $exists = StaffWarehouse::where('staff_id', $staffId)->where('warehouse_id', $warehouseId)->exists();
        if ($exists) {
            return;
        }
        $row = [
            'staff_id' => $staffId,
            'warehouse_id' => $warehouseId,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('staff_warehouses', 'is_kepala_cabang')) {
            $row['is_kepala_cabang'] = 0;
        }
        StaffWarehouse::insert($row);
    }

    private function ensureVariant(string $sku, string $name, int $categoryId, int $unitId): ProductVariant
    {
        $existing = ProductVariant::where('product_variant_sku', $sku)->where('status', 1)->first();
        if ($existing) {
            return $existing;
        }

        $product = new Product();
        $product->forceFill([
            'product_name' => $name,
            'category_id' => $categoryId,
            'unit_id' => $unitId,
            'product_unit' => json_encode([$unitId]),
            'status' => 1,
        ]);
        $product->save();

        $variant = new ProductVariant();
        $variant->forceFill([
            'product_id' => $product->product_id,
            'product_variant_name' => $name,
            'product_variant_sku' => $sku,
            'product_variant_price' => 0,
            'status' => 1,
        ]);
        $variant->save();

        return $variant;
    }
}
