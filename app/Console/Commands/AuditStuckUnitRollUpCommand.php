<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\Supplies;
use App\Models\SuppliesStock;
use App\Models\Unit;
use App\Support\UnitRollUp;
use Illuminate\Console\Command;

/**
 * READ-ONLY. GitHub #87 fixed the roll-up logic going forward, but the fix itself never touches a
 * stock row — it only self-heals a row the NEXT time a stock-in transaction credits it (see the
 * conversation this command was born from: HKCP60P self-healed because the user's own test
 * production WAS such a transaction, not because of any migration). A product/bahan with the same
 * stuck-over-ratio condition that never gets another stock-in transaction (or a Stock Opname, which
 * independently recomputes via the same collapse() primitive this command reuses) stays wrong
 * indefinitely and silently.
 *
 * This scans every product/bahan with more than one active stock row and asks
 * `UnitRollUp::collapseProduct()`/`collapseSupplies()` — the exact primitive Stock Opname already
 * uses to decide the canonical breakdown — "given what's ACTUALLY sitting in each unit's row right
 * now, what SHOULD it be?". Any row where the answer differs from what's stored is exactly
 * HKCP60P's situation, sitting unrepaired.
 *
 * Writes NOTHING. Safe to run directly against production. This is a diagnostic only; a --fix mode
 * is a deliberate separate step, not bundled here.
 */
class AuditStuckUnitRollUpCommand extends Command
{
    protected $signature = 'stock:audit-rollup {--json : Output as JSON instead of a table}';

    protected $description = 'READ-ONLY: find product/bahan stock rows stuck under-rolled from before GitHub #87 (writes nothing)';

    public function handle(): int
    {
        $unitNames = Unit::pluck('unit_name', 'unit_id');

        $productFindings = $this->auditProducts($unitNames);
        $suppliesFindings = $this->auditSupplies($unitNames);

        if ($this->option('json')) {
            $this->line(json_encode([
                'products' => $productFindings,
                'supplies' => $suppliesFindings,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->reportProducts($productFindings);
        $this->reportSupplies($suppliesFindings);

        $total = count($productFindings) + count($suppliesFindings);
        $this->newLine();
        if ($total === 0) {
            $this->info('Tidak ditemukan baris stok yang stuck under-rolled. Semua sudah konsisten dengan tangga satuannya.');
        } else {
            $this->warn("Ditemukan {$total} baris produk/bahan yang stuck under-rolled (lihat detail di atas). Tidak ada yang ditulis -- ini murni laporan.");
        }

        return self::SUCCESS;
    }

    /** @return array<int, array{id:int,label:string,before:array<int,int>,after:array<int,int>}> */
    private function auditProducts($unitNames): array
    {
        $variantIds = ProductStock::where('status', 1)
            ->select('product_variant_id')
            ->groupBy('product_variant_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('product_variant_id');

        $findings = [];

        foreach ($variantIds->chunk(200) as $chunk) {
            $stocksByVariant = ProductStock::where('status', 1)
                ->whereIn('product_variant_id', $chunk)
                ->get()
                ->groupBy('product_variant_id');

            foreach ($stocksByVariant as $variantId => $stocks) {
                $qtyByUnit = $stocks->pluck('ps_stock', 'unit_id')
                    ->map(fn ($q) => (int) $q)
                    ->all();

                // collapse() returns ABSOLUTE canonical values (same contract Stock Opname writes
                // directly), not deltas -- diff straight against what's actually stored.
                $collapsed = UnitRollUp::collapseProduct((int) $variantId, $qtyByUnit);
                if ($collapsed === []) {
                    continue;
                }

                $after = $qtyByUnit;
                $changed = false;
                foreach ($collapsed as $credit) {
                    $unitId = (int) $credit['unit_id'];
                    if (($qtyByUnit[$unitId] ?? 0) !== (int) $credit['qty']) {
                        $changed = true;
                    }
                    $after[$unitId] = (int) $credit['qty'];
                }

                if (! $changed) {
                    continue;
                }

                $pv = ProductVariant::find($variantId);
                $product = $pv ? Product::find($pv->product_id) : null;
                $label = trim(($product->product_name ?? '').' '.($pv->product_variant_name ?? ''));
                $sku = $pv->product_variant_sku ?? '-';

                $findings[] = [
                    'id' => (int) $variantId,
                    'sku' => $sku,
                    'label' => $label !== '' ? $label : "id {$variantId}",
                    'before' => $this->withUnitNames($qtyByUnit, $unitNames),
                    'after' => $this->withUnitNames($after, $unitNames),
                ];
            }
        }

        return $findings;
    }

    /** @return array<int, array{id:int,label:string,before:array<int,int>,after:array<int,int>}> */
    private function auditSupplies($unitNames): array
    {
        $suppliesIds = SuppliesStock::where('status', 1)
            ->select('supplies_id')
            ->groupBy('supplies_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('supplies_id');

        $findings = [];

        foreach ($suppliesIds->chunk(200) as $chunk) {
            $stocksBySupplies = SuppliesStock::where('status', 1)
                ->whereIn('supplies_id', $chunk)
                ->get()
                ->groupBy('supplies_id');

            foreach ($stocksBySupplies as $suppliesId => $stocks) {
                $qtyByUnit = $stocks->pluck('ss_stock', 'unit_id')
                    ->map(fn ($q) => (int) $q)
                    ->all();

                $collapsed = UnitRollUp::collapseSupplies((int) $suppliesId, $qtyByUnit);
                if ($collapsed === []) {
                    continue;
                }

                $after = $qtyByUnit;
                $changed = false;
                foreach ($collapsed as $credit) {
                    $unitId = (int) $credit['unit_id'];
                    if (($qtyByUnit[$unitId] ?? 0) !== (int) $credit['qty']) {
                        $changed = true;
                    }
                    $after[$unitId] = (int) $credit['qty'];
                }

                if (! $changed) {
                    continue;
                }

                $supplies = Supplies::find($suppliesId);

                $findings[] = [
                    'id' => (int) $suppliesId,
                    'sku' => '-',
                    'label' => $supplies->supplies_name ?? "id {$suppliesId}",
                    'before' => $this->withUnitNames($qtyByUnit, $unitNames),
                    'after' => $this->withUnitNames($after, $unitNames),
                ];
            }
        }

        return $findings;
    }

    /** @param  array<int,int>  $qtyByUnit */
    private function withUnitNames(array $qtyByUnit, $unitNames): array
    {
        $out = [];
        foreach ($qtyByUnit as $unitId => $qty) {
            $out[$unitNames[$unitId] ?? "unit {$unitId}"] = $qty;
        }

        return $out;
    }

    private function reportProducts(array $findings): void
    {
        $this->info('=== Produk yang stuck under-rolled ===');
        if ($findings === []) {
            $this->line('(tidak ada)');

            return;
        }

        foreach ($findings as $f) {
            $this->line("- [{$f['sku']}] {$f['label']} (product_variant_id={$f['id']})");
            $this->line('    sekarang : '.$this->formatBreakdown($f['before']));
            $this->line('    seharusnya: '.$this->formatBreakdown($f['after']));
        }
    }

    private function reportSupplies(array $findings): void
    {
        $this->newLine();
        $this->info('=== Bahan yang stuck under-rolled ===');
        if ($findings === []) {
            $this->line('(tidak ada)');

            return;
        }

        foreach ($findings as $f) {
            $this->line("- {$f['label']} (supplies_id={$f['id']})");
            $this->line('    sekarang : '.$this->formatBreakdown($f['before']));
            $this->line('    seharusnya: '.$this->formatBreakdown($f['after']));
        }
    }

    private function formatBreakdown(array $breakdown): string
    {
        $parts = [];
        foreach ($breakdown as $unitName => $qty) {
            $parts[] = "{$qty} {$unitName}";
        }

        return implode(', ', $parts);
    }
}
